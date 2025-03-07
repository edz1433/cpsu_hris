<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller; // ✅ Import the base Controller
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Dtr;
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
        $insertSuccess = false;
        
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
                if (Dtr::insert($insertData)) {
                    $insertSuccess = true;
                }
                $insertData = [];
            }
        }
    
        if (!empty($insertData) && Dtr::insert($insertData)) {
            $insertSuccess = true;
        }
    
        if ($insertSuccess) {
            DB::statement("SET SESSION group_concat_max_len = 102400");
        
            foreach (array_keys($dates) as $date) {
                $mergedData = Dtr::select(
                    'emp_ID',
                    DB::raw("GROUP_CONCAT(NULLIF(device_id_in, '') ORDER BY device_id_in SEPARATOR ',') AS device_id_in"),
                    DB::raw("GROUP_CONCAT(NULLIF(device_id_out, '') ORDER BY device_id_out SEPARATOR ',') AS device_id_out"),
                    DB::raw("GROUP_CONCAT(NULLIF(device_id_over, '') ORDER BY device_id_over SEPARATOR ',') AS device_id_over"),
                    DB::raw("GROUP_CONCAT(NULLIF(time_in, '') ORDER BY time_in SEPARATOR ',') AS time_in"),
                    DB::raw("GROUP_CONCAT(NULLIF(time_out, '') ORDER BY time_out SEPARATOR ',') AS time_out"),
                    DB::raw("GROUP_CONCAT(NULLIF(time_over, '') ORDER BY time_over SEPARATOR ',') AS time_over")
                )
                ->where('date', $date)
                ->groupBy('emp_ID', 'date')
                ->get();
        
                foreach ($mergedData as $data) {
                    Dtr::where('emp_ID', $data->emp_ID)
                        ->where('date', $date)
                        ->update([
                            'device_id_in' => $data->device_id_in ?: null,
                            'device_id_out' => $data->device_id_out ?: null,
                            'device_id_over' => $data->device_id_over ?: null,
                            'time_in' => $data->time_in ?: null,
                            'time_out' => $data->time_out ?: null,
                            'time_over' => $data->time_over ?: null,
                        ]);
                }
        
                Dtr::where('date', $date)
                    ->whereNotIn('id', function ($query) use ($date) {
                        $query->selectRaw('MIN(id)')
                            ->from('dtrs')
                            ->where('date', $date)
                            ->groupBy('emp_ID');
                    })
                    ->delete();
            }

            return 1;
        }
    
        // return response()->json(['error' => 'No records were inserted'], 500);
    }
    
}
