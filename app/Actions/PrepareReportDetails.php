<?php

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\ReportVersion;
use App\Models\User;
use App\Models\WeeklyReport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class PrepareReportDetails
{
    /**
     * @return array{report: WeeklyReport, fileAvailability: Collection<int, bool>}
     */
    public function handle(User $user, WeeklyReport $report): array
    {
        Gate::forUser($user)->authorize('view', $report);

        $report->load([
            'reportPeriod.internship.student.user',
            'versions.review',
            'reviews.lecturer.user',
            'assessment' => fn ($query) => $user->role === UserRole::Student ? $query->published() : $query,
        ]);

        $fileAvailability = $report->versions->mapWithKeys(fn (ReportVersion $version): array => [
            $version->getKey() => $this->isStoredReportFileAvailable($version),
        ]);

        return compact('report', 'fileAvailability');
    }

    private function isStoredReportFileAvailable(ReportVersion $version): bool
    {
        $disk = Storage::disk($version->disk);

        return $disk->exists($version->stored_path) && $disk->size($version->stored_path) === $version->size_bytes;
    }
}
