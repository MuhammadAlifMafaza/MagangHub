<?php

namespace App\Actions;

use App\Enums\AssessmentStatus;
use App\Models\Assessment;
use Illuminate\Support\Facades\DB;

class PublishAssessment
{
    public function handle(Assessment $assessment): Assessment
    {
        $publishedAssessment = DB::transaction(function () use ($assessment): Assessment {
            $assessment = Assessment::query()->whereKey($assessment->id)->lockForUpdate()->firstOrFail();
            abort_unless($assessment->status === AssessmentStatus::Draft, 409, 'Hanya draf yang dapat dipublikasikan.');
            $assessment->update(['status' => AssessmentStatus::Published, 'published_at' => now()]);

            return $assessment;
        });

        $studentUser = $publishedAssessment->weeklyReport->reportPeriod->internship->student->user;
        app(SendWorkflowNotification::class)->handle(
            $studentUser,
            'assessment_published',
            'Nilai laporan diterbitkan',
            'Nilai laporan mingguan Anda sudah dapat dilihat.',
            route('mahasiswa.laporan.show', $publishedAssessment->weeklyReport, absolute: false),
            'Lihat nilai',
        );

        return $publishedAssessment;
    }
}
