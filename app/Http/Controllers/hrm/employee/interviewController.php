<?php

namespace App\Http\Controllers\hrm\employee;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Interview;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InterviewController extends Controller
{
    /**
     * Display the interview management dashboard with real-time data.
     */
    public function interview()
    {
        $today = Carbon::today();

        return Inertia::render('Dashboard/HRM/Employee/interview', [
            // Today's Agenda
            'todays_interviews' => Interview::with('applicant')
                ->whereDate('scheduled_at', $today)
                ->whereHas('applicant', function ($query) {
                    $query->whereIn('status', ['Pending', 'Interview']);
                })
                ->orderBy('scheduled_at', 'asc')
                ->get()
                ->map(fn ($i) => [
                    'id' => $i->id,
                    'applicant_id' => $i->applicant_id,
                    'name' => $i->applicant->first_name.' '.$i->applicant->last_name,
                    'email' => $i->applicant->email,
                    'phone' => $i->applicant->phone_number,
                    'time' => $i->scheduled_at->format('h:i A'),
                    'type' => $i->interview_type,
                    'position' => $i->applicant->position_applied,
                    'avatar' => strtoupper(substr($i->applicant->first_name, 0, 1).substr($i->applicant->last_name, 0, 1)),
                    'notes' => $i->notes,
                ]),

            // Upcoming Schedule
            'upcoming_applicants' => Interview::with('applicant')
                ->whereDate('scheduled_at', '>', $today)
                ->whereHas('applicant', function ($query) {
                    $query->whereIn('status', ['Pending', 'Interview']);
                })
                ->orderBy('scheduled_at', 'asc')
                ->get()
                ->map(fn ($i) => [
                    'id' => $i->id,
                    'name' => $i->applicant->first_name.' '.$i->applicant->last_name,
                    'email' => $i->applicant->email,
                    'phone' => $i->applicant->phone_number,
                    'date' => $i->scheduled_at->format('M d, Y'),
                    'raw_date' => $i->scheduled_at->format('Y-m-d'),
                    'raw_time' => $i->scheduled_at->format('H:i'),
                    'position' => $i->applicant->position_applied,
                    'type' => $i->interview_type,
                    'status' => $i->applicant->status,
                ]),

            // ✅ Fixed: Only applicants who passed the initial interview appear here
            'past_interviews' => Applicant::with('interview')
                ->where('status', 'Passed')  // 🔥 Only passed applicants
                ->orderBy('updated_at', 'desc')
                ->take(10)
                ->get()
                ->map(fn ($a) => [
                    'id' => $a->id,
                    'name' => $a->first_name.' '.$a->last_name,
                    'position' => $a->position_applied,
                    'status' => $a->status,
                    'evaluated_date' => $a->updated_at->format('M d, Y'),
                ]),
        ]);
    }

    /**
     * Reschedule an existing interview.
     */
    public function reschedule(Request $request, Interview $interview)
    {
        $request->validate([
            'new_date' => 'required|date',
            'new_time' => 'required',
        ]);

        $newSchedule = Carbon::parse($request->new_date.' '.$request->new_time);

        $interview->update([
            'scheduled_at' => $newSchedule,
        ]);

        return redirect()->back()->with('success', 'Interview rescheduled successfully.');
    }

    public function updateStatus(Request $request, Interview $interview)
    {
        $request->validate([
            'status' => 'required|in:Passed,Rejected,Pending',
            'notes' => 'nullable|string',
        ]);

        $interview->update(['notes' => $request->notes]);
        $interview->applicant->update(['status' => $request->status]);

        return redirect()->back()->with('success', 'Evaluation submitted successfully.');
    }

    public function submitStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:applicants,id',
            'status' => 'required|string',
        ]);

        Applicant::where('id', $request->id)
            ->update(['status' => $request->status]);

        return back()->with('success', 'Status updated successfully!');
    }
}
