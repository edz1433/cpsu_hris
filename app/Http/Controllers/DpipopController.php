<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Dpipop;
use App\Models\Employee;
use App\Models\PrData;
use App\Models\Opcr;
use App\Models\Setting;

class DpipopController extends Controller
{
    public function getFormData(Request $request)
    {
        $formdata = Dpipop::where('user_id', $request->user_id)->where('folder_id', $request->folder_id)->get();
        
        return response()->json(['formdata' => $formdata]);
    }
    
    public function createpr_(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'folder_id' => 'required|exists:docu_folders,id',
            'mfo.*' => 'required|string',
            'percent.*' => 'required|integer|min:0|max:100',
        ]);
    
        $userId = $request->input('user_id');
        $folderId = $request->input('folder_id');
        $prnumber = rand(100000, 999999);

        $employee = Employee::find($userId);
    
        $data = [];
        foreach ($request->input('mfo') as $index => $mfo) {
            $data[] = [
                'user_id' => $userId,
                'off_id' => $employee->emp_dept,
                'folder_id' => $folderId,
                'mfo' => $mfo,
                'percent' => $request->input('percent')[$index],
                'pr_number' => $prnumber,
            ];
        }
    
        Dpipop::insert($data);

        // Retrieve the IDs of the inserted records
        $insertedRecords = Dpipop::where('user_id', $userId)
            ->orderBy('id', 'desc') // Order by descending id to get the most recent ones
            ->take(count($data)) // Ensure you fetch the same number of records as the inserted data
            ->pluck('id')
            ->toArray();
            
        $firstInsertedRecordId = $insertedRecords[0];
        $secondInsertedRecordId = $insertedRecords[1];
        $thirdInsertedRecordId = $insertedRecords[2];

        $prDataRows = [
            [
                "pr_id" => $firstInsertedRecordId,
                "mfo" => "MFO 1: Provision of Accessible, Equitable, Quality, and Relevant Curricular Programs (0%)",
            ],
            [
                "pr_id" => $secondInsertedRecordId,
                "mfo" => "MFO 2: Excellence in Research and Creative Works (0%)",
            ],
            [
                "pr_id" => $firstInsertedRecordId,
                "mfo" => "MFO 3: Delivery of Extension and Community Services (5%)",
                "target" => "100% of the staff wil involve in the extension activities of the office.",
                "in_support" => "Attendance, Pictures",
                "div_account" => "All Personnel",
                "e" => "5 = 100%,<br>4 = 90 - 99%,<br>3 = 80% - 89%,<br>2 = 70% - 79% <br>1 = Below 70%",
            ],

            [
                "pr_id" => $secondInsertedRecordId,
                "mfo" => "MFO 4: Production Activities (0%)",
            ],
            [
                "pr_id" => $secondInsertedRecordId,
                "mfo" => "MFO 5: Attainment of Good Governance (10%)",
            ],
            [
                "pr_id" => $secondInsertedRecordId,
                "mfo" => "StG7. Information and Records Management",
            ],


            [
                "pr_id" => $thirdInsertedRecordId,
                "mfo" => "MFO 5: Attainment of Good Governance (85%)",
            ],
            [
                "pr_id" => $thirdInsertedRecordId,
                "mfo" => "SuG3. Financial Management",
            ],
            [
                "pr_id" => $thirdInsertedRecordId,
                "mfo" => "SuG3a.Liquidation",
                "target" => "100% Liquidation of cash advances after return to official station for local travel 30 days after travel",
                "in_support" => "Certification from the Accountant",
                "div_account" => "All Personnel",
            ],
            [
                "pr_id" => $thirdInsertedRecordId,
                "mfo" => "",
                "target" => "100% Liquidation of cash advances for activities are conducted 15 days after activity",
                "in_support" => "Certification from accountant",
                "div_account" => "All Personnel",
            ],
            [
                "pr_id" => $thirdInsertedRecordId,
                "mfo" => "",
                "target" => "100% Liquidation of cash advances for activities are conducted 15 days after activity",
                "in_support" => "Certification from accountant",
                "report_sup" => "Accounting Office Report",
                "div_account" => "All Personnel",
            ],
            [
                "pr_id" => $thirdInsertedRecordId,
                "mfo" => "SuG4. Procurement Management",
            ],
            [
                "pr_id" => $thirdInsertedRecordId,
                "mfo" => "SuG5. Performance Management",
            ],
            [
                "pr_id" => $thirdInsertedRecordId,
                "mfo" => "Conduct of PBB Client Satisfaction Survey",
                "target" => "Conduct of PBB Client Satisfaction Survey",
                "in_support" => "Survey Forms",
                "div_account" => "All Personnel",
            ],
            [
                "pr_id" => $thirdInsertedRecordId,
                "mfo" => "",
                "target" => "100% #8888/CCB complaints resolution (w/in 1 yr)",
                "report_sup" => "8888 Center report",
                "div_account" => "All Personnel",
            ],
            [
                "pr_id" => $thirdInsertedRecordId,
                "mfo" => "",
                "target" => "100% #8888/CCB compliance within 72 hours",
                "report_sup" => "8888 Center report",
                "div_account" => "All Personnel",
            ],
            [
                "pr_id" => $thirdInsertedRecordId,
                "mfo" => "SuG5i. Attendance to meetings and university activities",
                "target" => "80% average attendance of employees to university activities (Graduation, Recognition, Charter Anniversary, Convocation, Foundation Anniversary, LGU Charter Anniversary)",
                "in_support" => "HRMO Report",
                "div_account" => "All Personnel",
            ],
            [
                "pr_id" => $thirdInsertedRecordId,
                "target" => "90% of personnel wear prescribed uniform (Monday, Tuesday, Thursday) (average, weekly)",
                "in_support" => "Office Head report",
                "div_account" => "All Personnel",
            ],
            [
                "pr_id" => $thirdInsertedRecordId,
                "mfo" => "SuG6. Human Resource Management",
            ],
            [
                "pr_id" => $thirdInsertedRecordId,
                "target" => "100% (__/__)  submission of Daily Time Record with complete attachments within 5 working days of the following month",
                "report_sup" => "OH Monitoring/ HR Monitoring",
                "div_account" => "All Personnel",
            ],
            [
                "pr_id" => $thirdInsertedRecordId,
                "mfo" => "SuG7a. Information Management",
            ],
        ];

        foreach ($prDataRows as $row) {
            PrData::create($row);
        }
    
        return redirect()->back()->with('success', 'Data saved successfully!');
    }
    
}
