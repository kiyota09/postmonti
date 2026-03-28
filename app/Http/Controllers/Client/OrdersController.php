<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class OrdersController extends Controller
{
    public function orders()
    {
        // TODO: Replace dummy stats with actual DB queries
        return Inertia::render('Client/Orders', [
            'stats' => [
                'pending_orders' => 0,
                'completed_orders' => 0,
                'recent_orders' => [],  // Will hold recent order objects
            ],
        ]);
    }
}
