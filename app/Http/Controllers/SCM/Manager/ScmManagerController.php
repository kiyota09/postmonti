<?php

namespace App\Http\Controllers\SCM\Manager;

use App\Http\Controllers\Controller;
use App\Models\SCM\MaterialRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ScmManagerController extends Controller
{
    public function forwardMaterialRequest(Request $request, $id)
    {
        try {
            $materialRequest = MaterialRequest::findOrFail($id);

            if ($materialRequest->status !== 'pending') {
                return redirect()->back()->withErrors(['error' => 'Material request already processed.']);
            }

            $materialRequest->update([
                'status' => 'forwarded',
                'forwarded_at' => now(),
                'forwarded_by' => auth()->id(),
            ]);

            return redirect()->back()->with('success', 'Material request forwarded to Procurement.');
        } catch (\Exception $e) {
            Log::error('Forward material request failed: '.$e->getMessage());

            return redirect()->back()->withErrors(['error' => 'Failed to forward request.']);
        }
    }
}
