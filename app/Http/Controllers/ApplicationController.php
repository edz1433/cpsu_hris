<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;

class ApplicationController extends Controller
{
    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'age' => 'required|integer|min:18|max:65',
            'sex' => 'required|in:male,female,other',
            'mobile' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'address' => 'required|string',
            'education' => 'required|array',
            'education.*' => 'required|string',
            'elevel' => 'required|array',
            'elevel.*' => 'required|string',
            'eyear' => 'required|array',
            'eyear.*' => 'required|string',
            'eligibility' => 'required|array',
            'eligibility.*' => 'required|string',
            'pds' => 'required|file|mimes:pdf|max:20480',
            'wes' => 'required|file|mimes:pdf|max:20480',
            'ilf' => 'required|file|mimes:pdf|max:20480',
            'resume' => 'required|file|mimes:pdf|max:20480',
            'tor' => 'required|file|mimes:pdf|max:20480',
            'coe' => 'nullable|file|mimes:pdf|max:20480',
            'cot' => 'nullable|file|mimes:pdf|max:20480'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Create uploads directory if it doesn't exist
            $uploadPath = 'public/Uploads/applicant-files';
            if (!Storage::exists($uploadPath)) {
                Storage::makeDirectory($uploadPath);
            }

            // Process file uploads
            $pdsFile = $this->uploadFile($request->file('pds'), $uploadPath);
            $wesFile = $this->uploadFile($request->file('wes'), $uploadPath);
            $ilfFile = $this->uploadFile($request->file('ilf'), $uploadPath);
            $resumeFile = $this->uploadFile($request->file('resume'), $uploadPath);
            $torFile = $this->uploadFile($request->file('tor'), $uploadPath);
            $coeFile = $request->file('coe') ? $this->uploadFile($request->file('coe'), $uploadPath) : null;
            $cotFile = $request->file('cot') ? $this->uploadFile($request->file('cot'), $uploadPath) : null;

            // Create application record
            $application = Application::create([
                'job_id' => $request->job_id ?? 1, // Default to 1 if not provided
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name,
                'last_name' => $request->last_name,
                'age' => $request->age,
                'sex' => $request->sex,
                'mobile' => $request->mobile,
                'email' => $request->email,
                'address' => $request->address,
                'education' => json_encode($request->education),
                'elevel' => json_encode($request->elevel),
                'eyear' => json_encode($request->eyear),
                'eligibility' => json_encode($request->eligibility),
                'pds' => $pdsFile,
                'wes' => $wesFile,
                'ilf' => $ilfFile,
                'resume' => $resumeFile,
                'tor' => $torFile,
                'coe' => $coeFile,
                'cot' => $cotFile,
                'application_date' => Carbon::now(),
                'status' => 'pending'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Application submitted successfully!',
                'application_id' => $application->id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error submitting application: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle file upload with unique filename
     */
    private function uploadFile($file, $path)
    {
        if (!$file) return null;
        
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $filename = $originalName . '_' . time() . '.' . $extension;
        
        $file->storeAs($path, $filename);
        
        return $filename;
    }

    /**
     * Get application details
     */
    public function show($id)
    {
        $application = Application::findOrFail($id);
        return response()->json($application);
    }

    /**
     * Get all applications
     */
    public function index()
    {
        $applications = Application::orderBy('application_date', 'desc')->get();
        return response()->json($applications);
    }

    /**
     * Update application status
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,reviewed,accepted,rejected'
        ]);

        $application = Application::findOrFail($id);
        $application->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Application status updated successfully'
        ]);
    }
}