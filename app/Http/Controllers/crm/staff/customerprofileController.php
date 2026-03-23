<?php

namespace App\Http\Controllers\crm\staff;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\CrmInteraction;
use App\Models\CrmLead;          // <-- Add this import
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerprofileController extends Controller
{
    /**
     * Display the Partner Ecosystem Grid.
     * Fetches all business partners (clients + won leads) and details for a selected partner.
     */
    public function customerprofile($id = null)
    {
        // 1. Fetch all active clients
        $clients = Client::where('status', 'active')->get();

        // 2. Fetch all won leads (Closed-Won) that are not yet converted
        $wonLeads = CrmLead::where('status', 'Closed-Won')->get();

        // 3. Combine into a single collection with a 'type' attribute
        $partners = collect();
        foreach ($clients as $client) {
            $partners->push([
                'type' => 'client',
                'data' => $client,
            ]);
        }
        foreach ($wonLeads as $lead) {
            $partners->push([
                'type' => 'lead',
                'data' => $lead,
            ]);
        }

        // 4. Identify the selected partner (if any)
        $selectedPartner = null;
        if ($id) {
            // Try to find in clients first
            $selectedClient = Client::find($id);
            if ($selectedClient) {
                $selectedPartner = ['type' => 'client', 'data' => $selectedClient];
            } else {
                // Then try in leads
                $selectedLead = CrmLead::find($id);
                if ($selectedLead && $selectedLead->status === 'Closed-Won') {
                    $selectedPartner = ['type' => 'lead', 'data' => $selectedLead];
                }
            }
        }

        // If no partner selected, pick the first one
        if (! $selectedPartner && $partners->isNotEmpty()) {
            $selectedPartner = $partners->first();
        }

        // 5. For clients, fetch interaction history and live production
        $interactionHistory = [];
        $liveProduction = [];
        if ($selectedPartner && $selectedPartner['type'] === 'client') {
            $interactionHistory = CrmInteraction::where('contactable_id', $selectedPartner['data']->id)
                ->where('contactable_type', Client::class)
                ->with('user:id,name')
                ->latest()
                ->get();

            $liveProduction = PurchaseOrder::where('client_id', $selectedPartner['data']->id)
                ->whereNotIn('status', ['approved', 'rejected'])
                ->get();
        }

        // 6. Return data to the Vue component
        return Inertia::render('Dashboard/CRM/customerprofile', [
            'partners' => $partners,
            'selectedPartner' => $selectedPartner,
            'interactionHistory' => $interactionHistory,
            'liveProduction' => $liveProduction,
        ]);
    }

    public function storeInteraction(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'type' => 'required|in:Call,Email,Meeting,System',
            'note' => 'required|string|max:1000',
        ]);

        CrmInteraction::create([
            'contactable_id' => $validated['client_id'],
            'contactable_type' => Client::class,
            'user_id' => auth()->id(),
            'type' => $validated['type'],
            'note' => $validated['note'],
        ]);

        return back()->with('message', 'Interaction successfully recorded.');
    }
}
