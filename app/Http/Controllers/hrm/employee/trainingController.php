<?php

namespace App\Http\Controllers\hrm\employee;

use App\Http\Controllers\Controller;
use App\Models\User;
// Ensure you create this Model
use Illuminate\Http\Request;
use Inertia\Inertia;

class TrainingController extends Controller
{
    public function training()
    {
        // Fetch trainees with their grades
        $trainees = User::where('position', 'trainee')
            ->with('traineeGrade') // Eager load the grades
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($trainee) {
                // Attach the percentage or 0 if no grade exists yet
                $trainee->grade_percent = $trainee->traineeGrade ? $trainee->traineeGrade->total_percentage : 0;

                return $trainee;
            });

        return Inertia::render('Dashboard/HRM/Employee/training', [
            'trainees' => $trainees,
        ]);
    }

    public function submitGrade(Request $request, $id)
    {
        $validated = $request->validate([
            'skills_performance' => 'required|integer|min:1|max:5',
            'behaviour' => 'required|integer|min:1|max:5',
            'technicals' => 'required|integer|min:1|max:5',
            'safety_awareness' => 'required|integer|min:1|max:5',
            'productivity' => 'required|integer|min:1|max:5',
        ]);

        $totalStars = array_sum($validated);
        $percentage = ($totalStars / 25) * 100;

        \App\Models\TraineeGrade::updateOrCreate(
            ['user_id' => $id],
            array_merge($validated, ['total_percentage' => $percentage])
        );

        return redirect()->back()->with('message', 'Grades updated successfully.');
    }

    public function suggestPromotion($id)
    {
        $trainee = User::with('traineeGrade')->findOrFail($id);

        // Safety check: Ensure 80% threshold is met
        $currentGrade = $trainee->traineeGrade ? $trainee->traineeGrade->total_percentage : 0;

        if ($currentGrade < 80) {
            return redirect()->back()->with('error', 'Trainee must have at least 80% grade to be suggested.');
        }

        $trainee->update([
            'promotion_suggested' => true,
            'suggested_at' => now(),
        ]);

        return redirect()->back()->with('message', 'Promotion suggestion sent to HR Manager.');
    }
}
