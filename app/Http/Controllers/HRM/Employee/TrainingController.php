<?php

namespace App\Http\Controllers\HRM\Employee;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\TraineeGrade; // Import this properly
use Illuminate\Http\Request;
use Inertia\Inertia;

class TrainingController extends Controller
{
    public function training()
    {
        $trainees = User::where('position', 'trainee')
            ->with('traineeGrade')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Dashboard/HRM/Employee/Training', [
            'trainees' => $trainees,
        ]);
    }

    public function submitGrade(Request $request, $id)
    {
        $validated = $request->validate([
            'skills_performance' => 'required|integer|min:1|max:5',
            'behaviour'          => 'required|integer|min:1|max:5',
            'technicals'         => 'required|integer|min:1|max:5',
            'safety_awareness'   => 'required|integer|min:1|max:5',
            'productivity'       => 'required|integer|min:1|max:5',
        ]);

        // Calculate percentage
        $totalStars = array_sum($validated);
        $percentage = ($totalStars / 25) * 100;

        // Fix: Ensure user_id is in the second array for the 'Create' part of the logic
        TraineeGrade::updateOrCreate(
            ['user_id' => $id],
            array_merge($validated, ['total_percentage' => $percentage])
        );

        return redirect()->back()->with('success', 'Grades updated successfully.');
    }

    public function suggestPromotion($id)
    {
        $trainee = User::with('traineeGrade')->findOrFail($id);

        $currentGrade = $trainee->traineeGrade->total_percentage ?? 0;

        if ($currentGrade < 80) {
            return redirect()->back()->with('error', 'Trainee must have at least 80% grade to be suggested.');
        }

        // Note: Ensure these exist in your User Model $fillable array
        $trainee->update([
            'promotion_suggested' => true,
            'suggested_at'        => now(),
        ]);

        return redirect()->back()->with('success', 'Promotion suggestion sent to HR Manager.');
    }
}