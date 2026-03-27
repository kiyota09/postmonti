<?php

namespace App\Http\Controllers\HRM\Manager;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ManagerEmployeeController extends Controller
{
    /**
     * Toggles an employee account active/inactive and writes to the existing audit_logs table.
     */
    public function toggleStatus(Request $request, $id)
    {
        // 1. Validate the incoming request securely
        $request->validate([
            'action' => 'required|in:deactivate,reactivate',
            'reason' => 'required|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $employee = User::findOrFail($id);
            $action = $request->input('action');
            $reason = $request->input('reason');
            $isActive = ($action === 'reactivate');

            // 2. Safely update the status based on your actual DB Schema
            // This fallback guarantees the database updates physically.
            $updateData = [];
            if (Schema::hasColumn('users', 'is_active')) {
                $employee->is_active = $isActive;
                $updateData['is_active'] = $isActive;
            }
            
            if (Schema::hasColumn('users', 'status')) {
                $employee->status = $isActive ? 'Active' : 'Inactive';
                $updateData['status'] = $isActive ? 'Active' : 'Inactive';
            }
            
            $employee->save();

            // Override Query: Guarantees update even if model mass-assignment blocks it
            if (!empty($updateData)) {
                DB::table('users')->where('id', $id)->update($updateData);
            }

            // 3. Write directly to your existing `audit_logs` table exactly as structured
            DB::table('audit_logs')->insert([
                'admin_id'    => auth()->id() ?? 1, // Fallback ID if not found
                'target_id'   => $employee->id,
                'action'      => $action,
                'reason'      => $reason,
                'target_name' => $employee->name,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            DB::commit();

            // 4. Return success response to Inertia/Vue
            return redirect()->back()->with('message', 'Account status updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            // Return actual error payload so Vue catches it in 'onError'
            return redirect()->back()->withErrors(['error' => 'Database operation failed: ' . $e->getMessage()]);
        }
    }
}