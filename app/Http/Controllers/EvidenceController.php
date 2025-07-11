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
            'empid'          => 'required|exists:employees,id',
            'category'       => 'required|integer',
            'data_id'        => 'required|integer',
            'evidence_url'   => 'required|url',
            'evidence_title' => 'required|string|max:255',
        ]);

        $url   = $request->evidence_url;
        $title = $request->evidence_title;

        $evidence = Evidence::where([
            'empid'    => $request->empid,
            'category' => $request->category,
            'data_id'  => $request->data_id,
        ])->first();

        if ($evidence) {
            $evidence->evidence = $url;
            $evidence->title    = $title;
            $evidence->save();

            return response()->json([
                'message' => 'Evidence updated successfully',
                'url'     => $url,
                'title'   => $title
            ]);
        }

        $newEvidence = Evidence::create([
            'empid'    => $request->empid,
            'category' => $request->category,
            'data_id'  => $request->data_id,
            'evidence' => $url,
            'title'    => $title,
        ]);

        return response()->json([
            'message' => 'Evidence attached successfully',
            'url'     => $url,
            'title'   => $title
        ]);
    }

}
