<?php

namespace App\Http\Controllers\crm\manager;

use App\Http\Controllers\Controller;
use App\Models\CrmApproval;
use App\Models\CrmLead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;   // <-- Add this import
use Inertia\Inertia;

class CrmApprovalController extends Controller
{
    public function index()
    {
        $pendingApprovals = CrmApproval::where('status', 'pending')->with('user')->get();

        return Inertia::render('Dashboard/CRM/Manager/ApprovalQueue', [
            'pendingApprovals' => $pendingApprovals,
        ]);
    }

    public function process(Request $request, $id)
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'reason' => 'nullable|string',
        ]);

        $approval = CrmApproval::findOrFail($id);

        if ($validated['action'] === 'approve') {
            // Execute the action based on the action type
            switch ($approval->action) {
                case 'add_note':
                    $lead = CrmLead::find($approval->data['lead_id']);
                    $lead->notes()->create([
                        'user_id' => $approval->user_id,
                        'note' => $approval->data['note'],
                    ]);
                    break;

                case 'schedule_interview':
                    $lead = CrmLead::find($approval->data['lead_id']);
                    $lead->interviews()->create([
                        'user_id' => $approval->user_id,
                        'scheduled_at' => $approval->data['scheduled_at'],
                        'location' => $approval->data['location'] ?? null,
                        'notes' => $approval->data['notes'] ?? null,
                    ]);
                    break;

                case 'upload_approval_file':
                    $lead = CrmLead::find($approval->data['lead_id']);
                    // Move file from temp to permanent location
                    $tempPath = $approval->data['file_path'];
                    $newPath = 'lead_approval_files/'.basename($tempPath);
                    Storage::disk('public')->move($tempPath, $newPath);
                    $lead->approvalFiles()->create([
                        'file_path' => $newPath,
                        'original_name' => $approval->data['original_name'],
                        'uploaded_by' => $approval->user_id,
                    ]);
                    break;

                case 'accept_lead':
                    $lead = CrmLead::find($approval->data['lead_id']);
                    $lead->update(['status' => 'Closed-Won']);
                    break;

                case 'reject_lead':
                    $lead = CrmLead::find($approval->data['lead_id']);
                    $lead->update(['status' => 'Lost', 'lost_reason' => $approval->data['lost_reason']]);
                    break;
            }

            $approval->update([
                'status' => 'approved',
                'manager_id' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            return back()->with('message', 'Approval processed successfully.');
        } else {
            // Reject the approval
            $approval->update([
                'status' => 'rejected',
                'manager_id' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            // Optionally, delete any temporary files if needed
            if ($approval->action === 'upload_approval_file' && isset($approval->data['file_path'])) {
                Storage::disk('public')->delete($approval->data['file_path']);
            }

            return back()->with('message', 'Approval rejected.');
        }
    }
}
