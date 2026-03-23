<?php

namespace App\Http\Controllers\crm\manager;

use App\Http\Controllers\Controller;
use App\Models\CrmLead;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StrategyController extends Controller
{
    public function strategy()
    {
        return Inertia::render('Dashboard/CRM/Manager/strategy', [
            'lostReasons' => CrmLead::where('status', 'Lost')
                ->select('lost_reason as reason', DB::raw('count(*) as count'))
                ->groupBy('lost_reason')
                ->get(),
            'stats' => [
                'forecastedRevenue' => CrmLead::where('status', 'Negotiation')->sum('estimated_value'),
                'leadVelocity' => CrmLead::where('created_at', '>=', now()->subWeek())->count(),
            ],
        ]);
    }
}
