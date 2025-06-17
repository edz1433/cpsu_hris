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
            'empid'    => 'required|exists:employees,id',
            'category' => 'required|integer',
            'data_id'  => 'required|integer',
            'evidence' => 'required|array',
        ]);

        $file = $request->file('evidence')[0];

        if (!$file->isValid()) {
            return response()->json(['error' => 'Uploaded file is not valid.'], 422);
        }

        $timestamp = Carbon::now()->format('mdYHis');
        $extension = $file->getClientOriginalExtension();
        $filename = $timestamp . '.' . $extension;

        $folder = 'Evidence';

        // Ensure the folder exists in storage/app/public
        $storagePath = storage_path("app/public/{$folder}");
        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0755, true); // Create folder with correct permissions
        }

        $file->storeAs($folder, $filename, 'public');

        $evidence = Evidence::where([
            'empid'    => $request->empid,
            'category' => $request->category,
            'data_id'  => $request->data_id,
        ])->first();

        if ($evidence) {
            if ($evidence->evidence) {
                $fullPath = storage_path("app/public/$folder/{$evidence->evidence}");
                if (file_exists($fullPath)) {
                    unlink($fullPath); // delete old file
                }
            }

            $evidence->evidence = $filename;
            $evidence->save();

            return response()->json([
                'message' => 'Evidence updated successfully',
                'file' => $filename
            ]);
        } else {
            // Create new record
            $newEvidence = Evidence::create([
                'empid'    => $request->empid,
                'category' => $request->category,
                'data_id'  => $request->data_id,
                'evidence' => $filename,
            ]);

            return response()->json([
                'message' => 'Evidence created successfully',
                'file' => $filename
            ]);
        }
    }
}
