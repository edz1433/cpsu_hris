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
            DB::statement("SET SESSION group_concat_max_len = 4294967295");
    
            $dateList = array_keys($dates);
    
            // Fetch all merged data for given dates
            $mergedData = DtrTest::select(
                'emp_ID',
                DB::raw("MAX(id) as id"),
                DB::raw("MAX(device_id_in) AS device_id_in"),
                DB::raw("MAX(device_id_out) AS device_id_out"),
                DB::raw("MAX(device_id_over) AS device_id_over"),
                DB::raw("MAX(time_in) AS time_in"),
                DB::raw("MAX(time_out) AS time_out"),
                DB::raw("MAX(time_over) AS time_over")
            )
            ->whereIn('date', $dateList)
            ->groupBy('emp_ID', 'date')
            ->get();
    
            // Perform batch updates
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
    
            // Batch update instead of individual queries
            DB::table('dtrs_test')->upsert($updates, ['id'], ['device_id_in', 'device_id_out', 'device_id_over', 'time_in', 'time_out', 'time_over']);
    
            // Delete duplicates in a single query
            DB::table('dtrs_test')
                ->whereIn('date', $dateList)
                ->whereNotIn('id', function ($query) use ($dateList) {
                    $query->selectRaw('MAX(id)')
                        ->from('dtrs_test')
                        ->whereIn('date', $dateList)
                        ->groupBy('emp_ID', 'date');
                })
                ->delete();
        }
    
        return response()->json(['message' => 'DTR Sync Complete']);
    }
    
    
    
}
