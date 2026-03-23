<?php

namespace App\Http\Controllers\eco\manager;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientVerificationController extends Controller
{
    /**
     * Display the partner verification dashboard.
     */
    public function index()
    {
        // Fetch pending applicants (case-insensitive)
        $pending = Client::whereIn('status', ['pending', 'PENDING', 'Pending'])
            ->latest()
            ->get();

        // Fetch everyone else (Active, Suspended, Rejected) to populate the directory
        $verified = Client::whereNotIn('status', ['pending', 'PENDING', 'Pending'])
            ->latest()
            ->get();

        return Inertia::render('Dashboard/ECO/Manager/index', [
            'pendingCompanies' => $pending,
            'verifiedCompanies' => $verified,
        ]);
    }

    /**
     * Update a client's status (active / suspended / rejected).
     */
    public function updateStatus(Request $request, Client $client)
    {
        $request->validate([
            'status' => 'required|in:active,suspended,rejected',
        ]);

        $client->update([
            'status' => $request->status,
        ]);

        return back()->with('success', "Status updated to {$request->status}.");
    }
}
