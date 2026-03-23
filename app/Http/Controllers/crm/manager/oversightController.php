<?php

namespace App\Http\Controllers\crm\manager;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\CrmInteraction;
use Carbon\Carbon;
use Inertia\Inertia;

class OversightController extends Controller
{
    public function oversight()
    {
        return Inertia::render('Dashboard/CRM/Manager/oversight', [
            'tickets' => CrmInteraction::where('type', 'System')->latest()->take(5)->get(),
            'atRiskClients' => Client::where('updated_at', '<', Carbon::now()->subDays(60))
                ->select('company_name', 'updated_at as lastOrder')
                ->get(),
        ]);
    }
}
