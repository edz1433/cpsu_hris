<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller; // ✅ Import the base Controller
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Dtr;
use App\Models\DtrTest;
use Illuminate\Support\Facades\DB;

class DtrController extends Controller
{
    public function getGuard()
    {
        if (\Auth::guard('web')->check()) {
            return 'web';
        } elseif (\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }

    public function syncDtr(Request $request)
    {
        $data = json_decode($request->getContent(), true);
    
        if (!is_array($data)) {
            return response()->json(['error' => 'Invalid JSON data'], 400);
        }
    
        $insertData = [];
        $dates = [];
    
        foreach ($data as $item) {
            if (!isset($item['emp_ID'], $item['date'])) {
                continue;
            }
    
            $insertData[] = [
                'device_id_in' => $item['device_id_in'] ?? null,
                'device_id_out' => $item['device_id_out'] ?? null,
                'device_id_over' => $item['device_id_over'] ?? null,
                'emp_ID' => $item['emp_ID'],
                'time_in' => $item['time_in'] ?? null,
                'time_out' => $item['time_out'] ?? null,
                'time_over' => $item['time_over'] ?? null,
                'date' => $item['date'],
            ];
    
            $dates[$item['date']] = true;
    
            if (count($insertData) >= 100) {
                DtrTest::insert($insertData);
                $insertData = [];
            }
        }
    
        if (!empty($insertData)) {
            DtrTest::insert($insertData);
        }
    
        if (!empty($dates)) {
            foreach (array_keys($dates) as $date) {
                // Fetch and merge duplicate records for each date
                $mergedData = DtrTest::select(
                    'emp_ID',
                    DB::raw("MAX(id) as id"), // Keep the latest ID
                    DB::raw("GROUP_CONCAT(DISTINCT NULLIF(device_id_in, '') ORDER BY device_id_in SEPARATOR ',') AS device_id_in"),
                    DB::raw("GROUP_CONCAT(DISTINCT NULLIF(device_id_out, '') ORDER BY device_id_out SEPARATOR ',') AS device_id_out"),
                    DB::raw("GROUP_CONCAT(DISTINCT NULLIF(device_id_over, '') ORDER BY device_id_over SEPARATOR ',') AS device_id_over"),
                    DB::raw("GROUP_CONCAT(DISTINCT NULLIF(time_in, '') ORDER BY time_in SEPARATOR ',') AS time_in"),
                    DB::raw("GROUP_CONCAT(DISTINCT NULLIF(time_out, '') ORDER BY time_out SEPARATOR ',') AS time_out"),
                    DB::raw("GROUP_CONCAT(DISTINCT NULLIF(time_over, '') ORDER BY time_over SEPARATOR ',') AS time_over")
                )
                ->where('date', $date)
                ->groupBy('emp_ID')
                ->get();
    
                // Perform bulk updates
                $updates = [];
                foreach ($mergedData as $data) {
                    $updates[] = [
                        'id' => $data->id,
                        'device_id_in' => $data->device_id_in ?: null,
                        'device_id_out' => $data->device_id_out ?: null,
                        'device_id_over' => $data->device_id_over ?: null,
                        'time_in' => $data->time_in ?: null,
                        'time_out' => $data->time_out ?: null,
                        'time_over' => $data->time_over ?: null,
                    ];
                }
    
                // Perform batch updates
                DB::table('dtrs_test')->upsert($updates, ['id'], ['device_id_in', 'device_id_out', 'device_id_over', 'time_in', 'time_out', 'time_over']);
    
                // Delete duplicate entries except the latest ID
                DtrTest::where('date', $date)
                    ->whereNotIn('id', function ($query) use ($date) {
                        $query->selectRaw('MAX(id)')
                            ->from('dtrs_test')
                            ->where('date', $date)
                            ->groupBy('emp_ID');
                    })
                    ->delete();
            }
        }
    
        return 1;
    }
    
    
}
