<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\VendorRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SupplierAuthController extends Controller
{
    /** Show the supplier login form */
    public function showLogin()
    {
        return Inertia::render('Auth/SupplierLogin');
    }

    /** Handle supplier login */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('supplier')->attempt($credentials, $request->boolean('remember'))) {

            $supplier = Auth::guard('supplier')->user();

            // CHECK IF THEY ARE APPROVED BY THE SCM MANAGER YET
            if (! $supplier->isApproved()) {
                // If not approved, log them right back out
                Auth::guard('supplier')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                // Send them back to the login page with an error
                return back()->withErrors([
                    'email' => 'Your vendor registration is still pending SCM approval or has been rejected.',
                ])->onlyInput('email');
            }

            // If approved, let them in normally
            $request->session()->regenerate();

            return redirect()->route('supplier.dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /** Show the supplier registration form */
    public function create()
    {
        return Inertia::render('Auth/SupplierRegister');
    }

    /** Handle supplier registration */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'representative_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'email' => ['required', 'email', 'unique:suppliers,email'],
            'phone_number' => ['required', 'string', 'max:50'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        // 1. Create the Supplier Account (Database record)
        $supplier = Supplier::create([
            'business_name' => $validated['business_name'],
            'representative_name' => $validated['representative_name'],
            'address' => $validated['address'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'],
            'password' => bcrypt($validated['password']),
        ]);

        // 2. Create the Vendor Registration Ticket (Flags them as Pending)
        VendorRegistration::create([
            'supplier_id' => $supplier->id,
            'business_name' => $supplier->business_name,
            'representative_name' => $supplier->representative_name,
            'email' => $supplier->email,
            'phone_number' => $supplier->phone_number,
            'address' => $supplier->address,
            'status' => 'pending',
        ]);

        // 3. DO NOT LOG THEM IN. Redirect them to the login page.
        return redirect()->route('supplier.login');
    }

    /** Log out the supplier */
    public function logout(Request $request)
    {
        Auth::guard('supplier')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
