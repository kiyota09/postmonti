<?php

namespace App\Http\Controllers\client;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class OrdersController extends Controller
{
    public function orders()
    {
        // TODO: Replace dummy stats with actual DB queries
        return Inertia::render('CLIENT/orders', [
            'stats' => [
                'pending_orders' => 0,
                'completed_orders' => 0,
                'recent_orders' => [],  // Will hold recent order objects
            ],
        ]);
    }
}
