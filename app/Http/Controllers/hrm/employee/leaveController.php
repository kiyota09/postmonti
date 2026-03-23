<?php

namespace App\Http\Controllers\hrm\employee;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest; // FIX: Added missing model import
use App\Models\User;         // FIX: Added for employee search
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeaveController extends Controller
{
    /**
     * Display the HRM Staff Leave Management page.
     */
    public function leave()
    {
        return Inertia::render('Dashboard/HRM/Employee/leave', [
            // Fetch all requests with user info for the table
            'leave_requests' => LeaveRequest::with('user:id,name,employee_id')
                ->orderBy('created_at', 'desc')
                ->get(),
            // Fetch employees for the manual search feature
            'employees' => User::where('position', '!=', 'manager')
                ->select('id', 'name', 'employee_id')
                ->get(),
        ]);
    }

    /**
     * Update status (Approve/Reject) for an existing request.
     */
    public function update(Request $request, $id)
    {
        $leave = LeaveRequest::findOrFail($id);
        $leave->update([
            'status' => $request->status,
        ]);

        return redirect()->back()->with('message', 'Leave request '.$request->status.' successfully.');
    }

    /**
     * Store a manual leave request (Walk-ins).
     * Automatically sets status to 'approved'.
     */
    public function store_manual(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'leave_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
        ]);

        // Force status to approved since it is a manual entry by HRM
        $validated['status'] = 'approved';

        LeaveRequest::create($validated);

        return redirect()->back()->with('message', 'Manual leave request processed and approved.');
    }
}
