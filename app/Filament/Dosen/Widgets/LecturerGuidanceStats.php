<?php

namespace App\Filament\Dosen\Widgets;

use App\Enums\AssessmentStatus;
use App\Enums\ReviewStatus;
use App\Models\Assessment;
use App\Models\GuidanceBooking;
use App\Models\GuidanceSlot;
use App\Models\WeeklyReport;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class LecturerGuidanceStats extends BaseWidget
{
    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $lecturerId = Auth::user()?->lecturer?->getKey();

        if (! $lecturerId) {
            return [
                Stat::make('Slot pembimbingan mendatang', 0),
                Stat::make('Pemesanan mendatang', 0),
                Stat::make('Mahasiswa bimbingan aktif', 0),
                Stat::make('Laporan menunggu pemeriksaan', 0),
                Stat::make('Penilaian draf', 0),
            ];
        }

        $upcomingSlots = GuidanceSlot::query()
            ->where('lecturer_id', $lecturerId)
            ->where('status', 'open')
            ->where('starts_at', '>=', now());

        return [
            Stat::make('Slot pembimbingan mendatang', (clone $upcomingSlots)->count()),
            Stat::make('Pemesanan mendatang', GuidanceBooking::query()
                ->where('status', 'booked')
                ->whereHas('slot', fn ($query) => $query->where('lecturer_id', $lecturerId)->where('starts_at', '>=', now()))
                ->count()),
            Stat::make('Mahasiswa bimbingan aktif', Auth::user()->lecturer->supervisorAssignments()->where('status', 'active')->distinct('student_id')->count('student_id')),
            Stat::make('Laporan menunggu pemeriksaan', WeeklyReport::query()
                ->where('review_status', ReviewStatus::PendingReview)
                ->whereHas('reportPeriod.internship.student.supervisorAssignments', fn (Builder $query) => $query
                    ->where('lecturer_id', $lecturerId)->where('status', 'active'))
                ->count()),
            Stat::make('Penilaian draf', Assessment::query()
                ->where('lecturer_id', $lecturerId)
                ->where('status', AssessmentStatus::Draft)
                ->whereHas('weeklyReport.reportPeriod.internship.student.supervisorAssignments', fn (Builder $query) => $query
                    ->where('lecturer_id', $lecturerId)->where('status', 'active'))
                ->count()),
        ];
    }
}
