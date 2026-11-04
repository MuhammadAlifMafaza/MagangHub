<?php

namespace App\Http\Controllers;

use App\Enums\InternshipStatus;
use App\Enums\ReviewStatus;
use App\Models\Assessment;
use App\Models\GuidanceSlot;
use App\Models\WeeklyReport;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $student = $request->user()->student;

        if ($student === null) {
            return view('dashboard', [
                'activeAssignment' => null,
                'currentPeriod' => null,
                'latestPublishedAssessment' => null,
                'reportsNeedingRevision' => collect(),
                'upcomingGuidanceBookings' => collect(),
            ]);
        }

        $activeAssignment = $student->activeSupervisorAssignment()
            ->with('lecturer.user')
            ->first();

        $activeInternship = $student->internships()
            ->where('status', InternshipStatus::Active)
            ->first();

        $currentPeriod = $activeInternship?->reportPeriods()
            ->with([
                'weeklyReport.latestVersion',
                'weeklyReport.assessment' => fn ($query) => $query->published(),
            ])
            ->whereDate('starts_on', '<=', today(config('magang.timezone')))
            ->whereDate('ends_on', '>=', today(config('magang.timezone')))
            ->first();

        $reportsNeedingRevision = WeeklyReport::query()
            ->with(['reportPeriod', 'latestVersion'])
            ->where('review_status', ReviewStatus::RevisionRequested)
            ->whereHas('reportPeriod.internship', fn ($query) => $query->where('student_id', $student->id))
            ->latest('updated_at')
            ->limit(5)
            ->get();

        $upcomingGuidanceBookings = $student->guidanceBookings()
            ->with('slot.lecturer.user')
            ->where('status', 'booked')
            ->whereHas('slot', fn ($query) => $query
                ->where('starts_at', '>=', now(config('magang.timezone')))
                ->where('status', 'open'))
            ->whereHas('slot.lecturer.supervisorAssignments', fn ($query) => $query
                ->where('student_id', $student->id)
                ->where('status', 'active'))
            ->orderBy(GuidanceSlot::query()->select('starts_at')->whereColumn('guidance_slots.id', 'guidance_bookings.guidance_slot_id'))
            ->limit(5)
            ->get();

        $latestPublishedAssessment = Assessment::query()
            ->published()
            ->with('weeklyReport.reportPeriod')
            ->whereHas('weeklyReport.reportPeriod.internship', fn ($query) => $query->where('student_id', $student->id))
            ->latest('published_at')
            ->first();

        return view('dashboard', [
            'activeAssignment' => $activeAssignment,
            'currentPeriod' => $currentPeriod,
            'latestPublishedAssessment' => $latestPublishedAssessment,
            'reportsNeedingRevision' => $reportsNeedingRevision,
            'upcomingGuidanceBookings' => $upcomingGuidanceBookings,
        ]);
    }
}
