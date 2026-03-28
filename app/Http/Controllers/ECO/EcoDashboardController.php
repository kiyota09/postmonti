<?php

namespace App\Http\Controllers\ECO;

use App\Http\Controllers\Controller;
use App\Models\INV\Product as InvProduct;
use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Inertia\Inertia;

class EcoDashboardController extends Controller
{
    /**
     * Display the ECO manager dashboard with stats and pipeline.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $todaySales = PurchaseOrder::whereDate('created_at', Carbon::today())
            ->where('status', 'approved')
            ->sum('total_amount');
        $activeProducts = InvProduct::where('status', 'Active')->count();
        $monthlyRevenue = PurchaseOrder::whereMonth('created_at', Carbon::now()->month)
            ->where('status', 'approved')
            ->sum('total_amount');

        // Department pipeline data (counts of orders in each stage)
        $pipeline = [
            'credit_review' => PurchaseOrder::where('status', 'credit_review')->count(),
            'tier_assignment' => PurchaseOrder::where('status', 'tier_assignment')->count(),
            'pending_client_approval' => PurchaseOrder::where('status', 'pending_client_approval')->count(),
            'approved' => PurchaseOrder::where('status', 'approved')->count(),
        ];

        return Inertia::render('Dashboard/ECO/Manager/Dashboard', [
            'stats' => [
                'todaySales' => number_format($todaySales, 2),
                'activeProducts' => $activeProducts,
                'monthlyRevenue' => number_format($monthlyRevenue, 2),
            ],
            'pipeline' => $pipeline,
        ]);
    }
}
