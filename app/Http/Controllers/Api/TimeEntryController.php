<?php

namespace App\Http\Controllers\Api;

use App\Models\Dtr;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TimeEntryController extends Controller
{
    public function listEmployees()
    {
        $employees = DB::table('employees')
            ->select('emp_ID', DB::raw("CONCAT(fname, ' ', COALESCE(mname, ''), ' ', lname) AS name"))
            ->orderBy('lname')
            ->get();
        return response()->json($employees);
    }    
    private $embeddingLimit = 9; // keep only last 9 embeddings
    public function register(Request $request)
    {
        $empId = $request->input('emp_ID');
        $embedding = $request->input('embedding'); // single embedding
        $embeddings = $request->input('embeddings'); // multiple embeddings
        if (!$empId) {
            return response()->json(['error' => 'Missing emp_ID'], 400);
        }
        $employee = DB::table('employees')->where('emp_ID', $empId)->first();
        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }
        $existing = json_decode($employee->face_embeddings ?? '[]', true);
        if ($embeddings && is_array($embeddings)) {
            // push multiple
            foreach ($embeddings as $e) {
                if (count($e) == 128) $existing[] = $e;
            }
        } elseif ($embedding && count($embedding) == 128) {
            // push single
            $existing[] = $embedding;
        } else {
            return response()->json(['error' => 'Invalid embedding(s)'], 400);
        }
        // limit
        $existing = array_slice($existing, -$this->embeddingLimit);
        DB::table('employees')
            ->where('emp_ID', $empId)
            ->update(['face_embeddings' => json_encode($existing)]);
        return response()->json([
            'success' => true,
            'emp_ID' => $empId,
            'total_embeddings' => count($existing)
        ]);
    }
    public function verify(Request $request)
    {
        $embedding = $request->input('embedding');
        if (!$embedding || count($embedding) != 128) {
            return response()->json(['error' => 'Invalid embedding'], 400);
        }
        static $cachedEmployees = null;
        if ($cachedEmployees === null) {
            $cachedEmployees = DB::table('employees')
                ->select('emp_ID', 'fname', 'mname', 'lname', 'face_embeddings')
                ->whereNotNull('face_embeddings')
                ->get();
        }
        $closestEmployee = null;
        $minDistance = INF;
        foreach ($cachedEmployees as $employee) {
            $embeddings = json_decode($employee->face_embeddings, true) ?? [];
            foreach ($embeddings as $storedEmbedding) {
                if (!$storedEmbedding || count($storedEmbedding) != 128) continue;
                $distance = $this->l2Distance($embedding, $storedEmbedding);
                if ($distance < $minDistance) {
                    $minDistance = $distance;
                    $closestEmployee = $employee;

                    // 🚀 early exit on very close match
                    if ($minDistance < 0.4) break 2;
                }
            }
        }
        if ($closestEmployee && $minDistance < 0.75) {
            return response()->json([
                'match' => true,
                'emp_id' => $closestEmployee->emp_ID,
                'name' => trim("{$closestEmployee->fname} {$closestEmployee->mname} {$closestEmployee->lname}"),
                'distance' => $minDistance
            ]);
        }
        return response()->json(['match' => false, 'distance' => $minDistance]);
    }
    private function l2Distance($a, $b)
    {
        $sum = 0;
        for ($i = 0; $i < 128; $i++) {
            $diff = $a[$i] - $b[$i];
            $sum += $diff * $diff;
        }
        return sqrt($sum);
    }
    public function listLogZones()
    {
        $zones = DB::table('logzones')->get()->map(function ($zone) {
            return [
                'id' => (int) $zone->id,
                'label' => $zone->label,
                'points' => json_decode($zone->points),
            ];
        });
        return response()->json($zones);
    }    
    public function logAttendance(Request $request)
    {
        try {
            $validated = $request->validate([
                'emp_id'  => 'required|string',
                'zone_id' => 'required|string',
                'action'  => 'required|integer|in:1,2,3', // 1=in, 2=out, 3=over
            ]);
            $empId = $validated['emp_id'];
            $employee = DB::table('employees')
            ->select('emp_ID')
            ->where('emp_ID', $empId)
            ->first();
        if (!$employee) {
            return response()->json(['error' => 'Unknown emp_ID'], 404);
        }
            // Preserve actual casing from DB
            $empId = $employee->emp_ID;
            $zoneId   = strtolower($validated['zone_id']);
            $action   = $validated['action'];
            $deviceId = $zoneId;
            $now      = now();
            $date     = $now->toDateString();
            $time     = $now->format('H:i:s');
            $timeField = match ($action) {
                1 => 'time_in',
                2 => 'time_out',
                3 => 'time_over',
            };
            $deviceField = match ($action) {
                1 => 'device_id_in',
                2 => 'device_id_out',
                3 => 'device_id_over',
            };
            $allowed     = true;
            $waitSeconds = 0;
            $record = Dtr::where('emp_ID', $empId)->where('date', $date)->first();
            if ($record) {
                $existingTimes   = $record->$timeField ? explode(',', $record->$timeField) : [];
                $existingDevices = $record->$deviceField ? explode(',', $record->$deviceField) : [];
                // Handle TIME IN specific logic (11PM time-out check)
                if ($action === 1 && !empty($record->time_out)) {
                    $existingOuts = explode(',', $record->time_out);
                    $validOuts = array_filter($existingOuts, fn($t) => strtotime($t) >= strtotime('11:00:00'));
                    if (!empty($validOuts)) {
                        $firstLateOut = reset($validOuts);
                        $lastTime = \Carbon\Carbon::createFromFormat('H:i:s', $firstLateOut);
                        $diffSec = $now->diffInSeconds($lastTime, false);
                        if ($diffSec < 60) {
                            $allowed = false;
                            $waitSeconds = 60 - $diffSec;
                        }
                    }
                }
                if ($allowed && !in_array($time, $existingTimes)) {
                    $existingTimes[]   = $time;
                    $existingDevices[] = $deviceId;
                    $record->update([
                        $timeField   => implode(',', $existingTimes),
                        $deviceField => implode(',', $existingDevices),
                    ]);
                }
            } else {
                Dtr::create([
                    'emp_ID'     => $empId,
                    'date'       => $date,
                    $timeField   => $time,
                    $deviceField => $deviceId,
                ]);
            }
            return response()->json([
                'success'      => true,
                'allowed'      => $allowed,
                'wait_seconds' => $waitSeconds,
                'time'         => $now->format('h:i:s A'),
                'type'         => match ($action) {
                    1 => 'TIME IN',
                    2 => 'TIME OUT',
                    3 => 'OVERTIME',
                },
                'emp_id'  => $empId,
                'zone_id' => $zoneId,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}