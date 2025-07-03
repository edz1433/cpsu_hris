<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\Evidence;

class EvidenceController extends Controller
{
    public function uploadEvidence(Request $request)
    {
        $request->validate([
            'empid'        => 'required|exists:employees,id',
            'category'     => 'required|integer',
            'data_id'      => 'required|integer',
            'evidence_url' => 'required|url',
        ]);

        $url = $request->evidence_url;

        $evidence = Evidence::where([
            'empid'    => $request->empid,
            'category' => $request->category,
            'data_id'  => $request->data_id,
        ])->first();

        if ($evidence) {
            // Overwrite existing URL or clear old file reference
            $evidence->evidence = $url;
            $evidence->save();

            return response()->json([
                'message' => 'Evidence URL updated successfully',
                'url' => $url
            ]);
        }

        // Create new evidence entry
        $newEvidence = Evidence::create([
            'empid'    => $request->empid,
            'category' => $request->category,
            'data_id'  => $request->data_id,
            'evidence' => $url,
        ]);

        return response()->json([
            'message' => 'Evidence URL attached successfully',
            'url' => $url
        ]);
    }

}
