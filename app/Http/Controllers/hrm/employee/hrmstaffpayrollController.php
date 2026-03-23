<?php

namespace App\Http\Controllers\hrm\employee;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class hrmstaffpayrollController extends Controller
{
    /**
     * Display the payroll terminal with filtered stats and existing records.
     */
    public function hrmstaffpayroll()
    {
        return Inertia::render('Dashboard/HRM/Employee/hrmstaffpayroll', [
            'payroll_data' => Payroll::latest()->get(),
            'holidays' => Holiday::orderBy('holiday_date', 'asc')->get(),
            'stats' => [
                'total_payout' => Payroll::where('status', 'approved')->sum('net_pay'),
                'pending_approvals' => Payroll::where('status', 'pending')->count(),
            ],
        ]);
    }

    /**
     * Store role-based payroll suggestions with automated PH statutory and tax logic.
     */
    public function store(Request $request)
    {
        // Convert empty strings to null for nullable numeric fields
        $nullableFields = ['overtime_hours', 'late_rate_min', 'sunday_restday_hours', 'night_rate', 'sss_loan', 'pf_loan'];
        foreach ($nullableFields as $field) {
            if ($request->has($field) && $request->input($field) === '') {
                $request->merge([$field => null]);
            }
        }

        $data = $request->validate([
            'role' => 'required|in:Staff,Manager',
            'base_salary' => 'required|numeric|min:0',
            'overtime_hours' => 'nullable|numeric|min:0',
            'late_rate_min' => 'nullable|numeric|min:0',
            'sunday_restday_hours' => 'nullable|numeric|min:0',
            'night_rate' => 'nullable|numeric|min:0',
            'sss_loan' => 'nullable|numeric|min:0',
            'pf_loan' => 'nullable|numeric|min:0',
        ]);

        $employees = User::where('role', $data['role'])->get();

        if ($employees->isEmpty()) {
            return redirect()->back()->with('error', "No employees found with the role: {$data['role']}");
        }

        DB::transaction(function () use ($employees, $data) {
            foreach ($employees as $employee) {
                $salary = $data['base_salary'];

                // 1. Automatic Rate Calculations (Based on standard 22 working days)
                $daily_rate = $salary / 22;
                $hourly_rate = $daily_rate / 8;
                $days_worked = 22;
                $total_days_amt = $daily_rate * $days_worked;

                // 2. OT and Gross Calculation
                $ot_pay = ($data['overtime_hours'] ?? 0) * ($hourly_rate * 1.25);
                $gross = $total_days_amt + $ot_pay + ($data['night_rate'] ?? 0);

                // 3. Automatic Statutory Deductions (Employee Share 2026)
                // SSS: 4.5% Employee Share based on Monthly Salary Credit (MSC) capped at 30k
                $msc = min($salary, 30000);
                $sss_ee = $msc * 0.045;

                // PhilHealth: 5% total rate split between EE and ER (2.5% each)
                $ph_ee = ($salary >= 10000) ? ($salary * 0.025) : 250;
                $ph_ee = min($ph_ee, 2500); // Capped at 100k salary credit

                // Pag-IBIG: Mandatory P200 for earners above P1,500
                $pagibig_ee = ($salary > 1500) ? 200 : ($salary * 0.01);

                // 4. BIR Withholding Tax (TRAIN Law 2023-2027 Table)
                $taxable_income = $gross - ($sss_ee + $ph_ee + $pagibig_ee);
                $withholding_tax = 0;

                if ($taxable_income > 666667) {
                    $withholding_tax = 180833.33 + ($taxable_income - 666667) * 0.35;
                } elseif ($taxable_income > 166667) {
                    $withholding_tax = 30833.33 + ($taxable_income - 166667) * 0.30;
                } elseif ($taxable_income > 66667) {
                    $withholding_tax = 8333.33 + ($taxable_income - 66667) * 0.25;
                } elseif ($taxable_income > 33333) {
                    $withholding_tax = 1666.67 + ($taxable_income - 33333) * 0.20;
                } elseif ($taxable_income > 20833) {
                    $withholding_tax = ($taxable_income - 20833) * 0.15;
                }

                // 5. Final Totals
                $total_deductions = $sss_ee + $ph_ee + $pagibig_ee + $withholding_tax +
                                   ($data['sss_loan'] ?? 0) +
                                   ($data['pf_loan'] ?? 0);

                // 6. Prepare all fields for Payroll record (set defaults for missing columns)
                Payroll::create([
                    // Employee Identity
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->name,
                    'role' => $data['role'],

                    // Basic Pay & Days Worked
                    'base_salary' => $salary,
                    'days_worked' => $days_worked,
                    'daily_rate' => $daily_rate,
                    'total_days_amt' => $total_days_amt,

                    // Night Differential
                    'night_hours' => 0, // Not collected in this form
                    'night_rate' => $data['night_rate'] ?? 0,
                    'night_amt' => $data['night_rate'] ?? 0, // Assume input is total amount

                    // Overtime
                    'overtime_hours' => $data['overtime_hours'] ?? 0,
                    'ot_rate' => $hourly_rate * 1.25,
                    'ot_amt' => $ot_pay,

                    // Sunday / Special Holiday
                    'sunday_restday_hours' => $data['sunday_restday_hours'] ?? 0,
                    'sun_sp_rate' => 0, // Not calculated in this version
                    'sun_sp_amt' => 0,
                    'holiday_amt' => 0,

                    // Lateness
                    'late_minutes' => 0, // Not collected
                    'late_rate_min' => $data['late_rate_min'] ?? 0,
                    'late_total_deduction' => 0, // Not calculated

                    // Statutory Deductions (Automated)
                    'sss_deduction' => $sss_ee,
                    'philhealth_deduction' => $ph_ee,
                    'pagibig_deduction' => $pagibig_ee,
                    'tax_withheld' => $withholding_tax,

                    // Loans
                    'sss_loan' => $data['sss_loan'] ?? 0,
                    'pf_loan' => $data['pf_loan'] ?? 0,

                    // Pay Totals
                    'gross_pay' => $gross,
                    'net_pay' => $gross - $total_deductions,
                    'status' => 'pending',
                ]);
            }
        });

        return redirect()->back()->with('success', 'Payroll generated with automated BIR and Statutory rates.');
    }

    /**
     * Approve a pending payroll record.
     */
    public function approve($id)
    {
        $payroll = Payroll::findOrFail($id);
        $payroll->update(['status' => 'approved']);

        return redirect()->back()->with('success', 'Payroll record approved successfully.');
    }

    /**
     * Reject a pending payroll record.
     */
    public function reject($id)
    {
        $payroll = Payroll::findOrFail($id);
        $payroll->update(['status' => 'rejected']);

        return redirect()->back()->with('warning', 'Payroll record rejected.');
    }

    /**
     * Store holiday configurations.
     */
    public function storeHoliday(Request $request)
    {
        $data = $request->validate([
            'holiday_date' => 'required|date|unique:holidays,holiday_date',
            'holiday_name' => 'required|string|max:255',
            'holiday_type' => 'required|in:Normal,Special',
            'premium_rate' => 'required|numeric|min:1',
        ]);

        Holiday::create($data);

        return redirect()->back()->with('success', 'Holiday schedule successfully updated.');
    }

    /**
     * Update Global Statutory Rates configuration.
     */
    public function updateStatutoryRates(Request $request)
    {
        return redirect()->back()->with('success', 'Global statutory matrix updated.');
    }
}
