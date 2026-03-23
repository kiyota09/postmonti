<?php

namespace App\Http\Controllers\hrm\employee;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HolidayController extends Controller
{
    public function store(Request $request)
    {
        $isManager = Auth::user()->position === 'manager';
        $status = $isManager ? 'approved' : 'pending';

        $request->validate([
            'holiday_date' => 'required|date|unique:holidays,holiday_date',
            'holiday_name' => 'required|string|max:255',
            'holiday_type' => 'required|in:regular,special_non_working,special_working',
            'premium_rate' => 'nullable|numeric|min:0',
        ]);

        $holiday = Holiday::create([
            'holiday_date' => $request->holiday_date,
            'holiday_name' => $request->holiday_name,
            'holiday_type' => $request->holiday_type,
            'premium_rate' => $request->premium_rate ?? 1.00,
            'status' => $status,
        ]);

        if ($isManager && in_array($request->holiday_type, ['regular', 'special_non_working'])) {
            DB::table('employee_shifts')->where('effective_date', $request->holiday_date)->delete();
        }

        return redirect()->back()->with('success', $isManager ? 'Holiday added' : 'Holiday pending approval');
    }

    public function update(Request $request, Holiday $holiday)
    {
        $isManager = Auth::user()->position === 'manager';
        $status = $isManager ? 'approved' : 'pending';

        $request->validate([
            'holiday_date' => 'required|date|unique:holidays,holiday_date,'.$holiday->id,
            'holiday_name' => 'required|string|max:255',
            'holiday_type' => 'required|in:regular,special_non_working,special_working',
            'premium_rate' => 'nullable|numeric|min:0',
        ]);

        $holiday->update(array_merge($request->all(), ['status' => $status]));

        if ($isManager && in_array($request->holiday_type, ['regular', 'special_non_working'])) {
            DB::table('employee_shifts')->where('effective_date', $request->holiday_date)->delete();
        }

        return redirect()->back()->with('success', $isManager ? 'Holiday updated' : 'Holiday update pending approval');
    }

    public function destroy(Holiday $holiday)
    {
        $holiday->delete();

        return redirect()->back()->with('success', 'Holiday removed.');
    }
}
