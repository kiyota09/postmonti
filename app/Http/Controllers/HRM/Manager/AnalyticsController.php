<?php

namespace App\Http\Controllers\HRM\Manager;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\User;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    public function analytics()
    {
        // 1. Core HR Stats
        $totalActive = User::where('is_active', 1)->count();
        $deactivatedCount = User::where('is_active', 0)->count();

        // Calculate Turnover Rate (Deactivated / Total ever registered)
        $turnoverRate = ($totalActive + $deactivatedCount) > 0
            ? round(($deactivatedCount / ($totalActive + $deactivatedCount)) * 100, 1)
            : 0;

        // Hiring metrics from Applicants table
        $totalApplicants = Applicant::count();
        $successfulHires = Applicant::where('status', 'Account Created')->count();

        // 2. Department Analysis Breakdown
        // This maps headcount and turnover percentage per role (HRM, SCM, etc.)
        $deptBreakdown = User::select('role')
            ->selectRaw('count(*) as headcount')
            ->selectRaw('SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as turnover_count')
            ->groupBy('role')
            ->get()
            ->map(function ($dept) {
                $turnover = $dept->headcount > 0
                    ? round(($dept->turnover_count / $dept->headcount) * 100, 1)
                    : 0;

                return [
                    'name' => $dept->role,
                    'headcount' => $dept->headcount,
                    'turnover' => $turnover.'%',
                    'status' => $turnover > 15 ? 'High' : ($turnover > 7 ? 'Stable' : 'Optimal'),
                ];
            });

        // 3. Trend Data (Example: Monthly Headcount Growth)
        // This generates data for the bar charts based on registration month
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $chartData = collect($months)->map(function ($month, $index) {
            $count = User::whereMonth('created_at', $index + 1)->count();
            // Scale to percentage for the bar height CSS
            $percentage = min(100, ($count * 10));

            return [
                'm' => $month,
                'h' => $percentage.'%',
            ];
        });

        return Inertia::render('Dashboard/HRM/Manager/Analytics', [
            'stats' => [
                'headcount' => $totalActive,
                'turnoverRate' => $turnoverRate.'%',
                'totalApplicants' => $totalApplicants,
                'hiringSuccess' => $successfulHires,
            ],
            'deptBreakdown' => $deptBreakdown,
            'chartData' => $chartData,
        ]);
    }
}
