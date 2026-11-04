<?php

namespace App\Actions;

use App\Enums\ReviewStatus;
use App\Models\ReportPeriod;
use App\Models\ReportVersion;
use App\Models\Student;
use App\Models\WeeklyReport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class SubmitReportVersion
{
    /** @return array{report: WeeklyReport, version: ReportVersion} */
    public function handle(Student $student, ReportPeriod $period, UploadedFile $file, ?string $note = null): array
    {
        if ($period->internship->student_id !== $student->id) {
            abort(403);
        }

        if ($period->starts_on->isFuture()) {
            throw ValidationException::withMessages(['file' => 'Periode pelaporan belum dibuka.']);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType() ?: 'application/octet-stream';
        $allowed = config('magang.reports.allowed_types', ['pdf' => ['application/pdf'], 'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document']]);
        if (! isset($allowed[$extension]) || ! in_array($mimeType, $allowed[$extension], true) || $file->getSize() > (int) config('magang.reports.max_size_bytes', 10 * 1024 * 1024)) {
            throw ValidationException::withMessages(['file' => 'Format berkas tidak didukung atau ukuran berkas melebihi batas. Gunakan PDF/DOCX hingga 10 MB.']);
        }

        $path = 'reports/'.$student->id.'/'.$period->id.'/'.Str::uuid().'.'.$extension;
        $written = false;

        try {
            $written = Storage::disk('local')->putFileAs(dirname($path), $file, basename($path)) !== false;
            if (! $written) {
                throw new \RuntimeException('Report file could not be stored.');
            }

            $result = DB::transaction(function () use ($student, $period, $file, $note, $extension, $mimeType, $path): array {
                $lockedPeriod = ReportPeriod::query()->whereKey($period->id)->lockForUpdate()->firstOrFail();
                abort_if($lockedPeriod->internship->student_id !== $student->id, 403);

                $report = WeeklyReport::query()->where('report_period_id', $period->id)->lockForUpdate()->first();
                if (! $report) {
                    $report = WeeklyReport::create(['report_period_id' => $period->id, 'review_status' => ReviewStatus::PendingReview]);
                    $versionNumber = 1;
                } else {
                    abort_if($report->review_status === ReviewStatus::Approved, 409, 'Laporan yang disetujui tidak dapat diubah.');
                    $latestVersion = $report->versions()->orderByDesc('version_number')->lockForUpdate()->firstOrFail();
                    $hasReview = $latestVersion->review()->exists();
                    abort_if($report->review_status !== ReviewStatus::RevisionRequested && ! ($report->review_status === ReviewStatus::PendingReview && ! $hasReview), 409, 'Versi laporan ini tidak dapat diganti.');
                    $versionNumber = $latestVersion->version_number + 1;
                    $report->update(['review_status' => ReviewStatus::PendingReview]);
                }

                $version = $report->versions()->create([
                    'version_number' => $versionNumber,
                    'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                    'stored_path' => $path,
                    'disk' => 'local',
                    'extension' => $extension,
                    'mime_type' => $mimeType,
                    'size_bytes' => $file->getSize(),
                    'checksum_sha256' => hash_file('sha256', $file->getRealPath()),
                    'student_note' => $note,
                    'submitted_at' => now(),
                    'is_late' => now()->greaterThan($lockedPeriod->due_at),
                    'uploaded_by' => $student->user_id,
                ]);

                return ['report' => $report, 'version' => $version];
            });
        } catch (Throwable $exception) {
            if ($written) {
                Storage::disk('local')->delete($path);
            }

            throw $exception;
        }

        return $result;
    }
}
