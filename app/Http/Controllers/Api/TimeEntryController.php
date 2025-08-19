<?php

namespace App\Http\Controllers\Api;

use App\Models\Dtr;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class TimeEntryController extends Controller
{    
    public function fetchEmployees()
    {
        $employees = DB::table('employees')
            ->select('emp_ID', 'fname', 'mname', 'lname')
            ->where('stat_1', 1)
            ->orderBy('lname')
            ->get()
            ->map(function ($emp) {
                $parts = array_filter([$emp->fname, $emp->mname, $emp->lname]);
                $emp->name = implode(' ', $parts);
                unset($emp->fname, $emp->mname, $emp->lname);
                return $emp;
            });

        return response()->json($employees); // same JSON as before
    }
    private $embeddingLimit = 9; // keep only last 9 embeddings
    public function faceRegister(Request $request)
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

        // 🚨 Clear face embedding cache to allow immediate verification
        Cache::forget('face_embeddings_cache');

        return response()->json([
            'success' => true,
            'emp_ID' => $empId,
            'total_embeddings' => count($existing)
        ]);
    }
    public function faceVerify(Request $request)
    {
        $embedding = $request->input('embedding');
        if (!$embedding || count($embedding) != 128) {
            return response()->json(['error' => 'Invalid embedding'], 400);
        }

        $cachedEmployees = Cache::remember('face_embeddings_cache', 300, function () {
            return DB::table('employees')
                ->select('emp_ID', 'fname', 'mname', 'lname', 'face_embeddings')
                ->whereNotNull('face_embeddings')
                ->get();
        });

        $closestEmployee = null;
        $minDistance = PHP_FLOAT_MAX;

        foreach ($cachedEmployees as $employee) {
            $embeddings = json_decode($employee->face_embeddings, true) ?? [];
            foreach ($embeddings as $storedEmbedding) {
                if (count($storedEmbedding) !== 128) continue;

                $distance = $this->l2Distance($embedding, $storedEmbedding);
                if ($distance < $minDistance) {
                    $minDistance = $distance;
                    $closestEmployee = $employee;

                    if ($minDistance < 0.4) break 2; // early exit
                }
            }
        }

        if ($closestEmployee && $minDistance < 0.75) {
            return response()->json([
                'match'    => true,
                'emp_id'   => $closestEmployee->emp_ID,
                'name'     => trim("{$closestEmployee->fname} {$closestEmployee->mname} {$closestEmployee->lname}"),
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
    public function fetchLogzones()
    {
        $zones = DB::table('logzones')->get()->map(function ($zone) {
            $points = json_decode($zone->points, true);
            if (!is_array($points)) { // ensure always an array
                $points = [];
            }
            return [
                'id' => (int) $zone->id,
                'label' => $zone->label,
                'points' => $points,
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

            // Validate employee exists and preserve DB casing
            $empRow = DB::table('employees')
                ->select('emp_ID')
                ->where('emp_ID', $validated['emp_id'])
                ->first();
            if (!$empRow) {
                return response()->json(['error' => 'Unknown emp_ID'], 404);
            }

            $empId    = $empRow->emp_ID;
            $zoneId   = strtolower($validated['zone_id']);
            $action   = (int) $validated['action'];
            $deviceId = $zoneId;

            $now   = now();
            $date  = $now->toDateString();
            $time  = $now->format('H:i:s'); // keep seconds precision (no schema change)

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
            $didUpdate   = false;

            $record = Dtr::where('emp_ID', $empId)->where('date', $date)->first();

            if ($record) {
                $existingTimes   = $record->$timeField ? explode(',', $record->$timeField) : [];
                $existingDevices = $record->$deviceField ? explode(',', $record->$deviceField) : [];

                // TIME IN rule: block if first OUT >= threshold was <60s ago
                if ($action === 1 && !empty($record->time_out)) {
                    $outs = array_map('trim', explode(',', $record->time_out));

                    // Choose your threshold: '11:00:00' (11 AM) or '23:00:00' (11 PM)
                    $threshold = '11:00:00'; // <-- set as needed

                    // Keep only OUTs at/after threshold (same day, HH:mm:ss)
                    $validOuts = array_values(array_filter($outs, fn($t) => strtotime($t) >= strtotime($threshold)));

                    if (!empty($validOuts)) {
                        // Use the FIRST qualifying OUT (index 0), not the latest
                        $firstQualOut = $validOuts[0];

                        // Parse and compute elapsed seconds since that first qualifying OUT
                        $lastTime = \Carbon\Carbon::createFromFormat('H:i:s', $firstQualOut);
                        $elapsed  = $lastTime->diffInSeconds($now); // always >= 0

                        if ($elapsed < 60) {
                            $allowed     = false;
                            $waitSeconds = 60 - $elapsed; // 1..59
                        }
                    }
                }



                // Append only if allowed and not an exact duplicate second
                if ($allowed && !in_array($time, $existingTimes, true)) {
                    $existingTimes[]   = $time;
                    $existingDevices[] = $deviceId;

                    $record->update([
                        $timeField   => implode(',', $existingTimes),
                        $deviceField => implode(',', $existingDevices),
                    ]);
                    $didUpdate = true;
                }
            } else {
                Dtr::create([
                    'emp_ID'     => $empId,
                    'date'       => $date,
                    $timeField   => $time,
                    $deviceField => $deviceId,
                ]);
                $didUpdate = true;
            }

            // HTTP codes communicate effect:
            // 200 = updated/created, 202 = no change (duplicate), 429 = not allowed (rate rule)
            $status = $allowed ? ($didUpdate ? 200 : 202) : 429;

            return response()->json([
                'success'      => $didUpdate,
                'updated'      => $didUpdate,
                'allowed'      => $allowed,
                'wait_seconds' => $waitSeconds,
                'time'         => $now->format('h:i:s A'),
                'type'         => match ($action) { 1=>'TIME IN', 2=>'TIME OUT', 3=>'OVERTIME' },
                'emp_id'       => $empId,
                'zone_id'      => $zoneId,
            ], $status);
        } catch (\Throwable $e) {
            return response()->json([
                'error'   => 'Server error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function recentLogs(Request $request)
    {
        // Server-owned time window (adjust as needed; could also come from config('app.recent_logs_hours'))
        $WINDOW_HOURS = 24;

        $empId = $request->input('empId');
        if (!$empId) {
            return response()->json(['error' => 'Missing empId'], 400);
        }

        $cutoff = now()->subHours($WINDOW_HOURS);

        $DEFAULT_ZONE_LABEL   = 'TBD';
        $DEFAULT_DEVICE_LABEL = 'TBD';

        // Map zone id -> label
        $zoneById = \DB::table('logzones')->pluck('label', 'id')->toArray();

        // Map device id -> label  (adjust table name if different, e.g. 'f_devices')
        $deviceById = \DB::table('f_devices')->pluck('label', 'id')->toArray();

        // Pull only likely dates to reduce scan
        $minDate = $cutoff->copy()->subDay()->toDateString();

        $rows = \DB::table('dtrs')
            ->join('employees', 'dtrs.emp_ID', '=', 'employees.emp_ID')
            ->where('dtrs.emp_ID', $empId)
            ->where('dtrs.date', '>=', $minDate)
            ->select('dtrs.*', 'employees.fname', 'employees.lname') // suffix omitted
            ->orderBy('dtrs.date', 'desc')
            ->get();

        $pickPlace = function (?int $id) use ($deviceById, $zoneById, $DEFAULT_DEVICE_LABEL, $DEFAULT_ZONE_LABEL) {
            if ($id === null) return $DEFAULT_ZONE_LABEL;
            if ($id < 100) { // device
                return $deviceById[$id] ?? $DEFAULT_DEVICE_LABEL;
            }
            // zone
            return $zoneById[$id] ?? $DEFAULT_ZONE_LABEL;
        };

        $out = [];

        foreach ($rows as $r) {
            // TIME IN
            $ins = array_filter(explode(',', (string) $r->time_in));
            $zin = explode(',', (string) $r->device_id_in);
            foreach ($ins as $i => $t) {
                $dt = \Carbon\Carbon::parse($r->date.' '.$t);
                if ($dt->lt($cutoff)) continue;

                $zRaw   = trim($zin[$i] ?? '');
                $id     = $zRaw === '' ? null : (int) $zRaw;
                $label  = $pickPlace($id);

                $out[] = [
                    'type'       => 'time_in',
                    'date'       => $r->date,
                    'time'       => $t,
                    'fname'      => $r->fname,
                    'lname'      => $r->lname,
                    'zone_id'    => $id,
                    'zone_label' => $label,
                    'ts'         => $dt->toIso8601String(),
                ];
            }

            // TIME OUT
            $outs = array_filter(explode(',', (string) $r->time_out));
            $zout = explode(',', (string) $r->device_id_out);
            foreach ($outs as $i => $t) {
                $dt = \Carbon\Carbon::parse($r->date.' '.$t);
                if ($dt->lt($cutoff)) continue;

                $zRaw   = trim($zout[$i] ?? '');
                $id     = $zRaw === '' ? null : (int) $zRaw;
                $label  = $pickPlace($id);

                $out[] = [
                    'type'       => 'time_out',
                    'date'       => $r->date,
                    'time'       => $t,
                    'fname'      => $r->fname,
                    'lname'      => $r->lname,
                    'zone_id'    => $id,
                    'zone_label' => $label,
                    'ts'         => $dt->toIso8601String(),
                ];
            }

            // TIME OVER (OVERTIME)
            $overs = array_filter(explode(',', (string) $r->time_over));
            $zover = explode(',', (string) $r->device_id_over);
            foreach ($overs as $i => $t) {
                $dt = \Carbon\Carbon::parse($r->date.' '.$t);
                if ($dt->lt($cutoff)) continue;

                $zRaw   = trim($zover[$i] ?? '');
                $id     = $zRaw === '' ? null : (int) $zRaw;
                $label  = $pickPlace($id);

                $out[] = [
                    'type'       => 'time_over',
                    'date'       => $r->date,
                    'time'       => $t,
                    'fname'      => $r->fname,
                    'lname'      => $r->lname,
                    'zone_id'    => $id,
                    'zone_label' => $label,
                    'ts'         => $dt->toIso8601String(),
                ];
            }
        }

        usort($out, fn($a,$b) => strcmp($b['ts'], $a['ts'])); // newest first
        foreach ($out as &$row) unset($row['ts']); // don’t expose sort key

        // Optional: include the server-defined window for transparency/debugging (frontend can ignore)
        return response()->json([
            'window_hours' => $WINDOW_HOURS,
            'logs' => $out
        ], 200);
    }
    public function adminFaceVerify(Request $request)
    {
        $embedding = $request->input('embedding');
        if (!$embedding || count($embedding) != 128) {
            return response()->json(['error' => 'Invalid embedding'], 400);
        }

        // 1) Reuse the same matching logic you already have (can call a private method if you extract it).
        $cachedEmployees = Cache::remember('face_embeddings_cache', 300, function () {
            return DB::table('employees')
                ->select('emp_ID', 'fname', 'mname', 'lname', 'face_embeddings')
                ->whereNotNull('face_embeddings')
                ->get();
        });

        $closestEmployee = null;
        $minDistance = PHP_FLOAT_MAX;

        foreach ($cachedEmployees as $employee) {
            $embeddings = json_decode($employee->face_embeddings, true) ?? [];
            foreach ($embeddings as $storedEmbedding) {
                if (count($storedEmbedding) !== 128) continue;
                $d = $this->l2Distance($embedding, $storedEmbedding);
                if ($d < $minDistance) { $minDistance = $d; $closestEmployee = $employee; if ($d < 0.4) break 2; }
            }
        }

        if (!$closestEmployee || $minDistance >= 0.75) {
            return response()->json(['match' => false, 'is_admin' => false, 'distance' => $minDistance]);
        }

        // 2) Read hr_kiosk once from DB (no cache needed)
        $hrKioskCsv = DB::table('settings')->value('hr_kiosk'); // "EMP0039,EMP0033,..."
        $ids = array_filter(array_map('trim', explode(',', (string)$hrKioskCsv)));
        $isAdmin = in_array($closestEmployee->emp_ID, $ids, true);

        return response()->json([
            'match'    => true,
            'is_admin' => $isAdmin,
            'emp_id'   => $closestEmployee->emp_ID,
            'name'     => trim("{$closestEmployee->fname} {$closestEmployee->mname} {$closestEmployee->lname}"),
            'distance' => $minDistance,
        ]);
    }
}