<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function edit()
    {
        $client = auth('client')->user();

        return Inertia::render('Client/Profile', ['client' => $client]);
    }

    public function update(Request $request)
    {
        $client = auth('client')->user();

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'business_type' => 'required|string|max:255',
            'tin_number' => 'nullable|string|max:50',
            'contact_person' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'company_address' => 'required|string',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $client->update($validated);

        return redirect()->back()->with('success', 'Profile updated.');
    }
}
