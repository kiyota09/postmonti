<?php

namespace App\Http\Controllers\hrm\manager;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    /**
     * Display the onboarding pipeline with filtered applicants.
     */
    public function onboarding()
    {
        // Load applicants with interview relationship for pipeline filtering
        $applicants = Applicant::with('interview')
            ->get()
            ->map(function ($applicant) {
                return [
                    'id' => $applicant->id,
                    'first_name' => $applicant->first_name,
                    'last_name' => $applicant->last_name,
                    'email' => $applicant->email,
                    'phone_number' => $applicant->phone_number,
                    'position_applied' => $applicant->position_applied,
                    'status' => $applicant->status,
                    'created_at' => $applicant->created_at,
                    // Check if they have an interview record for the Vue filter
                    'has_interview' => $applicant->interview !== null,
                ];
            });

        return Inertia::render('Dashboard/HRM/Manager/onboarding', [
            'applicants' => $applicants,
        ]);
    }

    /**
     * Handle the drag-and-drop status updates from the pipeline.
     */
    public function updateStage(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string',
        ]);

        $applicant = Applicant::findOrFail($id);

        /** * Map UI Column names from onboarding.vue to Database statuses
         * FOR INTERVIEW -> 'Interview'
         * FINAL INTERVIEW -> 'final'
         * ONBOARDING -> 'onboard'
         */
        $statusMap = [
            'FOR INTERVIEW' => 'Interview',
            'FINAL INTERVIEW' => 'final',
            'ONBOARDING' => 'onboard',
        ];

        $newStatus = $statusMap[$request->status] ?? $request->status;

        $applicant->update(['status' => $newStatus]);

        return back()->with('success', 'Stage updated successfully');
    }

    /**
     * Create a system user account for the onboarded applicant.
     */
    public function createUser(Request $request, $id)
    {
        // Added validation for the new role selection and position
        $request->validate([
            'role' => 'required|in:HRM,SCM,FIN,MAN,INV,ORD,WAR,CRM,ECO',
            'position' => 'required|string',
        ]);

        $applicant = Applicant::findOrFail($id);

        // Check if the user already exists to avoid duplication errors
        $existingUser = User::where('email', $applicant->email)->first();
        if ($existingUser) {
            return back()->withErrors(['email' => 'An account with this email already exists.']);
        }

        // Create the user account using the dynamic role from the request
        User::create([
            'name' => $applicant->first_name.' '.$applicant->last_name,
            'email' => $applicant->email,
            'password' => Hash::make('password123'), // Default temporary password
            'role' => $request->role, // Uses selected role from Vue modal
            'position' => 'trainee', // Strictly forced to trainee as requested
            'is_active' => true,
            'join_date' => now(),
        ]);

        // Update applicant status to signify account creation is finished
        $applicant->update(['status' => 'Account Created']);

        return back()->with('success', "ERP Account created successfully for {$applicant->first_name} as Trainee in {$request->role}.");
    }
}
