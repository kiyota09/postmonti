<?php

namespace App\Http\Controllers\scm\manager;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\VendorRegistration;
use App\Models\VendorRequirement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class VendorController extends Controller
{
    /**
     * Display the vendor page
     */
    public function vendor()
    {
        // Role is stored as 'SCM' (uppercase), position as 'manager' — NOT 'scm_manager'
        $isManager = auth()->check()
            && strtoupper(auth()->user()->role) === 'SCM'
            && strtolower(auth()->user()->position) === 'manager';

        $registrations = [];
        $myRegistration = null;

        if ($isManager) {
            $registrations = VendorRegistration::with('requirements')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'business_name' => $r->business_name,
                    'representative_name' => $r->representative_name,
                    'email' => $r->email,
                    'phone_number' => $r->phone_number,
                    'address' => $r->address,
                    'status' => $r->status,
                    'rejection_reason' => $r->rejection_reason,
                    'approved_at' => $r->approved_at ? \Carbon\Carbon::parse($r->approved_at)->format('M d, Y') : null,
                    'rejected_at' => $r->rejected_at ? \Carbon\Carbon::parse($r->rejected_at)->format('M d, Y') : null,
                    'created_at' => $r->created_at ? \Carbon\Carbon::parse($r->created_at)->format('M d, Y') : null,
                    'requirements' => collect($r->requirements ?? [])->map(fn ($req) => [
                        'id' => $req->id,
                        'requirement_name' => $req->requirement_name,
                        'description' => $req->description,
                        'value' => $req->value,
                    ])->toArray(),
                ])
                ->toArray();
        } else {
            $myRegistration = VendorRegistration::where('email', auth()->user()->email)
                ->with('requirements')
                ->first();
        }

        return Inertia::render('Dashboard/SCM/Manager/vendor', [
            'isManager' => $isManager,
            'registrations' => $registrations,
            'myRegistration' => $myRegistration,
        ]);
    }

    /**
     * Store a new vendor registration
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_name' => 'required|string|max:255',
            'representative_name' => 'required|string|max:255',
            'email' => 'required|email|unique:vendor_registrations,email',
            'phone_number' => 'required|string|max:20',
            'address' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors());
        }

        try {
            $supplier = Supplier::create([
                'business_name' => $request->business_name,
                'representative_name' => $request->representative_name,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'address' => $request->address,
                'password' => Hash::make($request->password),
            ]);

            VendorRegistration::create([
                'supplier_id' => $supplier->id,
                'business_name' => $request->business_name,
                'representative_name' => $request->representative_name,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'address' => $request->address,
                'status' => 'pending',
            ]);

            return back()->with('success', 'Registration submitted successfully! Awaiting approval.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error submitting registration: '.$e->getMessage());
        }
    }

    /**
     * Approve a vendor registration AND set initial requirements
     */
    public function approve(Request $request, $id)
    {
        $this->authorizeManager();

        $validator = Validator::make($request->all(), [
            'requirements' => 'nullable|array',
            'requirements.*.requirement_name' => 'required|string|max:255',
            'requirements.*.description' => 'nullable|string',
            'requirements.*.value' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors());
        }

        $registration = VendorRegistration::findOrFail($id);

        $registration->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        // Save requirements to make them an official vendor
        if ($request->has('requirements') && is_array($request->requirements)) {
            $registration->requirements()->delete(); // Clear any existing just in case
            foreach ($request->requirements as $req) {
                if (! empty($req['requirement_name'])) {
                    VendorRequirement::create([
                        'vendor_registration_id' => $registration->id,
                        'requirement_name' => $req['requirement_name'],
                        'description' => $req['description'] ?? null,
                        'value' => $req['value'] ?? null,
                    ]);
                }
            }
        }

        return back()->with('success', 'Vendor approved and requirements set successfully!');
    }

    /**
     * Reject a vendor registration
     */
    public function reject(Request $request, $id)
    {
        $this->authorizeManager();

        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors());
        }

        $registration = VendorRegistration::findOrFail($id);

        if ($registration->supplier_id) {
            Supplier::find($registration->supplier_id)?->delete();
        }

        $registration->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'rejected_at' => now(),
            'rejected_by' => auth()->id(),
        ]);

        return back()->with('success', 'Vendor registration rejected.');
    }

    /**
     * Set vendor requirements (Standalone method for editing existing requirements)
     */
    public function setRequirements(Request $request, $id)
    {
        $this->authorizeManager();

        $validator = Validator::make($request->all(), [
            'requirements' => 'required|array|min:1',
            'requirements.*.requirement_name' => 'required|string|max:255',
            'requirements.*.description' => 'nullable|string',
            'requirements.*.value' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors());
        }

        $registration = VendorRegistration::findOrFail($id);
        $registration->requirements()->delete();

        foreach ($request->requirements as $req) {
            VendorRequirement::create([
                'vendor_registration_id' => $registration->id,
                'requirement_name' => $req['requirement_name'],
                'description' => $req['description'] ?? null,
                'value' => $req['value'] ?? null,
            ]);
        }

        return back()->with('success', 'Vendor requirements updated successfully!');
    }

    /**
     * Get all vendor registrations as JSON (for manager)
     */
    public function getRegistrations()
    {
        $this->authorizeManager();

        $registrations = VendorRegistration::with('requirements')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($registrations);
    }

    /**
     * Get a specific registration as JSON
     */
    public function getRegistration($id)
    {
        $this->authorizeManager();

        $registration = VendorRegistration::with('requirements')->findOrFail($id);

        return response()->json($registration);
    }

    /**
     * Get requirements for a registration as JSON
     */
    public function getRequirements($id)
    {
        $registration = VendorRegistration::findOrFail($id);

        return response()->json($registration->requirements);
    }

    /**
     * Get current user's own registration as JSON (for supplier)
     */
    public function getMyRegistration()
    {
        $registration = VendorRegistration::where('email', auth()->user()->email)
            ->with('requirements')
            ->first();

        if (! $registration) {
            return response()->json(null, 404);
        }

        return response()->json($registration);
    }

    /**
     * Authorize SCM manager — role='SCM', position='manager'
     */
    private function authorizeManager()
    {
        if (
            ! auth()->check()
            || strtoupper(auth()->user()->role) !== 'SCM'
            || strtolower(auth()->user()->position) !== 'manager'
        ) {
            abort(403, 'Unauthorized action.');
        }
    }
}
