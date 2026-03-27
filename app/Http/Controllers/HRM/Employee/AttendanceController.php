<?php

namespace App\Http\Controllers\HRM\Employee;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\EmployeeShift;
use App\Models\Holiday;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    /**
     * Display the attendance and shift management page.
     */
    public function attendance(Request $request)
    {
        $now = Carbon::now('Asia/Manila');
        $today = $now->toDateString();

        $month = $request->input('month', $now->month);
        $year = $request->input('year', $now->year);

        $viewDate = Carbon::create($year, $month, 1, 0, 0, 0, 'Asia/Manila');
        $startOfMonth = $viewDate->copy()->startOfMonth()->toDateString();
        $endOfMonth = $viewDate->copy()->endOfMonth()->toDateString();

        $holidays = Holiday::whereBetween('holiday_date', [$startOfMonth, $endOfMonth])
            ->where('status', 'approved')
            ->get()
            ->map(fn($h) => [
                'id' => $h->id,
                'date' => $h->holiday_date,
                'name' => $h->holiday_name,
                'type' => $h->holiday_type,
                'premium_rate' => $h->premium_rate,
            ]);

        $pendingHolidays = Holiday::whereBetween('holiday_date', [$startOfMonth, $endOfMonth])
            ->where('status', 'pending')
            ->get();

        $monthlyShifts = EmployeeShift::whereBetween('effective_date', [$startOfMonth, $endOfMonth])
            ->where('status', 'approved')
            ->get()
            ->map(fn($shift) => [
                'user_id' => $shift->user_id,
                'shift_type' => $shift->shift_type,
                'effective_date' => Carbon::parse($shift->effective_date)->toDateString(),
            ]);

        $pendingShifts = EmployeeShift::with('user')
            ->whereBetween('effective_date', [$startOfMonth, $endOfMonth])
            ->where('status', 'pending')
            ->get()
            ->map(fn($shift) => [
                'id' => $shift->id,
                'user_id' => $shift->user_id,
                'shift_type' => $shift->shift_type,
                'effective_date' => Carbon::parse($shift->effective_date)->toDateString(),
                'schedule_range' => $shift->schedule_range,
                'dept_code' => $shift->dept_code,
                'status' => $shift->status,
                'user' => $shift->user ? ['id' => $shift->user->id, 'name' => $shift->user->name] : null,
            ]);

        return Inertia::render('Dashboard/HRM/Employee/Attendance', [
            'employee_attendance' => User::with(['latestAttendance' => function ($query) use ($today) {
                $query->where('date', $today);
            }, 'currentShift' => function ($query) use ($today) {
                $query->where('effective_date', $today);
            }])->get()->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'dept' => $user->role ?? 'N/A',
                    'shift' => $user->currentShift->shift_type ?? 'Not Set',
                    'clock_in' => $user->latestAttendance->clock_in ?? '---',
                    'status' => $user->latestAttendance->status ?? 'Absent',
                ];
            }),
            'monthly_shifts' => $monthlyShifts,
            'holidays' => $holidays,
            'pending_shifts' => $pendingShifts,
            'pending_holidays' => $pendingHolidays,
            'is_manager' => Auth::user()->position === 'manager',
            'current_month' => (int) $month,
            'current_year' => (int) $year,
            'attendance_status' => [
                'is_clocked_in' => false,
                'last_action' => '08:45 AM',
                'total_hours_today' => '0h 0m',
            ],
        ]);
    }

    /**
     * Handles both single-day updates and bulk scheduling (monthly/weekly/daily).
     */
    public function updateShift(Request $request)
    {
        $isManager = Auth::user()->position === 'manager';
        $status = $isManager ? 'approved' : 'pending';

        $isNonWorkingDay = function ($date) {
            $holiday = Holiday::where('holiday_date', $date)->where('status', 'approved')->first();
            if (! $holiday) {
                return false;
            }

            return in_array($holiday->holiday_type, ['regular', 'special_non_working']);
        };

        if ($request->is_bulk) {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'shift_type' => 'required|in:Morning,Afternoon,Graveyard',
                'dept_code' => 'required|string',
                'schedule_range' => 'required|string',
                'dates' => 'sometimes|array', // For weekly/daily bulk
                'month' => 'sometimes|integer|min:1|max:12',
                'year' => 'sometimes|integer',
            ]);

            $dates = $request->dates ?? [];
            if (empty($dates)) {
                // Fallback to monthly
                $dateContext = Carbon::create($request->year, $request->month, 1, 0, 0, 0, 'Asia/Manila');
                $daysInMonth = $dateContext->daysInMonth;
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $dates[] = Carbon::create($request->year, $request->month, $day, 0, 0, 0, 'Asia/Manila')->toDateString();
                }
            }

            foreach ($dates as $targetDate) {
                if ($isNonWorkingDay($targetDate)) {
                    continue;
                }

                $existing = EmployeeShift::withTrashed()
                    ->where('user_id', $request->user_id)
                    ->where('effective_date', $targetDate)
                    ->first();

                $data = [
                    'shift_type' => $request->shift_type,
                    'dept_code' => $request->dept_code,
                    'schedule_range' => $request->schedule_range,
                    'status' => $status,
                ];

                if ($existing) {
                    $existing->restore();
                    $existing->update($data);
                } else {
                    EmployeeShift::create(array_merge([
                        'user_id' => $request->user_id,
                        'effective_date' => $targetDate,
                    ], $data));
                }
            }

            return redirect()->route('hrm.employee.attendance', [
                'month' => $request->month ?? $dateContext->month,
                'year' => $request->year ?? $dateContext->year,
            ])->with('success', $isManager ? 'Schedule updated' : 'Schedule pending approval');
        }

        // Single day
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'shift_type' => 'required|in:Morning,Afternoon,Graveyard',
            'effective_date' => 'required|date',
            'schedule_range' => 'required|string',
            'dept_code' => 'nullable|string',
        ]);

        $targetDate = Carbon::parse($request->effective_date)->toDateString();

        if ($isNonWorkingDay($targetDate)) {
            return back()->withErrors(['effective_date' => 'Cannot assign shifts on a non-working holiday.']);
        }

        $existing = EmployeeShift::withTrashed()
            ->where('user_id', $request->user_id)
            ->where('effective_date', $targetDate)
            ->first();

        $data = [
            'shift_type' => $request->shift_type,
            'dept_code' => $request->dept_code,
            'schedule_range' => $request->schedule_range,
            'status' => $status,
        ];

        if ($existing) {
            $existing->restore();
            $existing->update($data);
        } else {
            EmployeeShift::create(array_merge([
                'user_id' => $request->user_id,
                'effective_date' => $targetDate,
            ], $data));
        }

        $date = Carbon::parse($targetDate)->timezone('Asia/Manila');

        return redirect()->route('hrm.employee.attendance', [
            'month' => $date->month,
            'year' => $date->year,
        ])->with('success', $isManager ? 'Shift updated' : 'Shift pending approval');
    }

    /**
     * Soft-delete a shift for a specific employee on a specific date.
     */
    public function destroyShift(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'effective_date' => 'required|date',
        ]);

        $date = Carbon::parse($request->effective_date)->format('Y-m-d');

        EmployeeShift::where('user_id', $request->user_id)
            ->where('effective_date', $date)
            ->delete(); // Soft delete

        return redirect()->back()->with('success', 'Shift removed.');
    }

    /**
     * Soft-delete all shifts for a specific employee for the ENTIRE given month.
     */
    public function destroyMonthlyShift(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer',
        ]);

        $dateContext = Carbon::create($request->year, $request->month, 1, 0, 0, 0, 'Asia/Manila');
        $startOfMonth = $dateContext->copy()->startOfMonth()->toDateString();
        $endOfMonth = $dateContext->copy()->endOfMonth()->toDateString();

        EmployeeShift::where('user_id', $request->user_id)
            ->whereBetween('effective_date', [$startOfMonth, $endOfMonth])
            ->delete(); // Soft delete

        return redirect()->back()->with('success', 'Monthly shifts removed.');
    }

    /**
     * Approve pending shift
     */
    public function approveShift(Request $request)
    {
        $shift = EmployeeShift::findOrFail($request->id);
        $shift->update(['status' => 'approved']);

        return back()->with('success', 'Shift approved');
    }

    /**
     * Reject pending shift
     */
    public function rejectShift(Request $request)
    {
        $shift = EmployeeShift::findOrFail($request->id);
        $shift->update(['status' => 'rejected']);

        return back()->with('success', 'Shift rejected');
    }

    /**
     * Approve pending holiday
     */
    public function approveHoliday(Request $request)
    {
        $holiday = Holiday::findOrFail($request->id);
        $holiday->update(['status' => 'approved']);

        if (in_array($holiday->holiday_type, ['regular', 'special_non_working'])) {
            EmployeeShift::where('effective_date', $holiday->holiday_date)->delete(); // Soft delete shifts
        }

        return back()->with('success', 'Holiday approved');
    }

    /**
     * Reject pending holiday
     */
    public function rejectHoliday(Request $request)
    {
        $holiday = Holiday::findOrFail($request->id);
        $holiday->update(['status' => 'rejected']);

        return back()->with('success', 'Holiday rejected');
    }

    /**
     * Toggle logic for local Clock captures.
     */
    public function toggle()
    {
        $user = Auth::user();
        $now = Carbon::now('Asia/Manila');
        $today = $now->toDateString();
        $timeString = $now->format('h:i A');

        $log = AttendanceLog::firstOrCreate(
            ['user_id' => $user->id, 'date' => $today]
        );

        if (! $log->clock_in) {
            $status = 'On-Time';
            $startTime = Carbon::createFromFormat('Y-m-d H:i:s', "$today 08:00:00", 'Asia/Manila');
            if ($now->gt($startTime)) {
                $status = 'Late';
            }

            $log->update([
                'clock_in' => $timeString,
                'status' => $status,
            ]);

            return back()->with('success', "Clocked in at $timeString. Status: $status");
        }

        if (! $log->clock_out) {
            $log->update(['clock_out' => $timeString]);

            return back()->with('success', "Clocked out at $timeString. Shift completed.");
        }

        return back()->with('error', 'You have already completed your shift logs for today.');
    }
}
