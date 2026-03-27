<?php

namespace App\Http\Controllers\CRM\Staff;

use App\Http\Controllers\Controller;
use App\Models\CrmLead;
use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class StaffDayController extends Controller
{
    /**
     * Display the CRM Staff "My Day" dashboard with real-time metrics.
     */
    public function index()
    {
        $userId = Auth::id(); // Standardized to use currently logged-in CRM staff

        // 1. Fetch real notifications for new inquiries assigned to this staff member
        $notifications = CrmLead::where('assigned_staff_id', $userId)
            ->where('status', 'Inquiry')
            ->latest()
            ->take(5)
            ->get();

        // 2. Count active deals in the pipeline
        $myActiveLeadsCount = CrmLead::where('assigned_staff_id', $userId)
            ->whereNotIn('status', ['Closed-Won', 'Lost'])
            ->count();

        // 3. Calculate Personal Revenue from approved Purchase Orders
        $personalRevenue = PurchaseOrder::where('status', 'approved')
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('total_amount');

        return Inertia::render('Dashboard/CRM/Employee/Index', [
            'notifications' => $notifications,
            'myActiveLeadsCount' => $myActiveLeadsCount,
            'personalRevenue' => (float) $personalRevenue,
            'target' => 1200000,
        ]);
    }
}
