<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Dpipop;
use App\Models\Employee;
use App\Models\PrData;

class DpipopController extends Controller
{
    public function getFormData(Request $request)
    {
        $formdata = Dpipop::where('user_id', $request->user_id)->where('folder_id', $request->folder_id)->get();
        
        return response()->json(['formdata' => $formdata]);
    }
    
    public function createpr(Request $request)
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
                "mfo" => "<b>StG7. Information and Records Management<b>",
            ],


            [
                "pr_id" => $thirdInsertedRecordId,
                "mfo" => "MFO 5: Attainment of Good Governance (85%)",
            ],
            [
                "pr_id" => $thirdInsertedRecordId,
                "mfo" => "<b>SuG3. Financial Management<b>",
            ],
            [
                "pr_id" => $thirdInsertedRecordId,
                "mfo" => "SuG3a.Liquidation",
                "target" => "100% Liquidation of cash advances after return to official station for local travel 30 days after travel",
                "in_support" => "Certification from the Accountant",
                "div_account" => "All Personnel",
                "q" => "5 = liquidation was approved upon 1st submission<br>4 = approved upon 2nd submission with minor comments<br>3 = approved upon 2nd submission with major comments<br>2 = approved upon 3rd submission with minor comments<br>1 = approved upon 3rd submission with major comments", 
                "e" => "5 = liquidated 100% of cash advances<br>1 = failed to liquidated 100% of cash advances",
                "t" => "5 = submitted report 5 to 10 days early<br>4 = 1 to 4 days early<br>3 = on the deadline<br>2 = 1 to 4 days delayed<br>1 = 5 or more days delayed"
            ],
            [
                "pr_id" => $thirdInsertedRecordId,
                "mfo" => "",
                "target" => "100% Liquidation of cash advances for activities are conducted 15 days after activity",
                "in_support" => "Certification from accountant",
                "div_account" => "All Personnel",
                "q" => "5 = liquidation was approved upon 1st submission<br>4 = approved upon 2nd submission with minor comments<br>3 = approved upon 2nd submission with major comments<br>2 = approved upon 3rd submission with minor comments<br>1 = approved upon 3rd submission with major comments", 
                "e" => "5 = l5 = liquidated 100% of cash advances<br>1 = failed to liquidated 100% of cash advances",
                "t" => "5 = submitted report 5 to 10 days early<br>4 = 1 to 4 days early<br>3 = on the deadline<br>2 = 1 to 4 days delayed<br>1 = 5 or more days delayed"
            ],
            [
                "pr_id" => $thirdInsertedRecordId,
                "mfo" => "",
                "target" => "100% Liquidation of cash advances for activities are conducted 15 days after activity",
                "in_support" => "Certification from accountant",
                "report_sup" => "Accounting Office Report",
                "div_account" => "All Personnel",
                "q" => "5 = liquidation was approved upon 1st submission<br>4 = approved upon 2nd submission with minor comments<br>3 = approved upon 2nd submission with major comments<br>2 = approved upon 3rd submission with minor comments<br>1 = approved upon 3rd submission with major comments", 
                "e" => "5 = l5 = liquidated 100% of cash advances<br>1 = failed to liquidated 100% of cash advances",
                "t" => "5 = submitted report 5 to 10 days early<br>4 = 1 to 4 days early<br>3 = on the deadline<br>2 = 1 to 4 days delayed<br>1 = 5 or more days delayed"
            ],
        ];

        foreach ($prDataRows as $row) {
            PrData::create($row);
        }
    
        return redirect()->back()->with('success', 'Data saved successfully!');
    }
    
}
