<?php

namespace App\Http\Controllers\Api;

use App\Models\Dtr;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class TimeEntryController extends Controller
{
    // ==== CONFIG ====
    private int   $embeddingLimit = 7;            // keep only latest embeddings (post-dedupe)
    // Squared thresholds (avoid sqrt in hot path)
    private float $dedupeThr2     = 0.28 * 0.28;  // drop near-duplicates on register
    private float $earlyExitThr2  = 0.40 * 0.40;  // early-accept boundary for centroid pass
    private float $acceptThr2     = 0.75 * 0.75;  // match acceptance threshold    
    // Helpers (embeddings & matching)
    private function l2Normalize(array $v): array {
        $sum = 0.0;
        foreach ($v as $x) { $sum += $x * $x; }
        $norm = sqrt(max($sum, 1e-12));
        foreach ($v as $i => $x) { $v[$i] = $x / $norm; }
        return $v;
    }
    // L2 **squared** distance (faster; no sqrt)
    private function l2Distance2(array $a, array $b): float {
        $s = 0.0;
        for ($i = 0; $i < 128; $i++) {
            $d = $a[$i] - $b[$i];
            $s += $d * $d;
        }
        return $s;
    }
    // Keep order; drop vectors closer than $dedupeThr2 to any kept vector. Inputs expected normalized.
    private function dedupeEmbeddings(array $embs): array {
        $out = [];
        foreach ($embs as $e) {
            if (!is_array($e) || count($e) !== 128) continue;
            $keep = true;
            foreach ($out as $x) {
                if ($this->l2Distance2($e, $x) < $this->dedupeThr2) { $keep = false; break; }
            }
            if ($keep) $out[] = $e;
        }
        return $out;
    }
    private function centroid(array $embs): ?array {
        $n = count($embs);
        if ($n === 0) return null;
        $acc = array_fill(0, 128, 0.0);
        foreach ($embs as $e) {
            if (!is_array($e) || count($e) !== 128) continue;
            for ($i = 0; $i < 128; $i++) { $acc[$i] += $e[$i]; }
        }
        for ($i = 0; $i < 128; $i++) { $acc[$i] /= max($n, 1); }
        return $this->l2Normalize($acc);
    }
    // Strict new-schema reader. Expects: {"vecs":[[128],...], "centroid":[128]|null}
    // Returns ['vecs'=>array, 'centroid'=>array|null].
    private function readEmbObj(?string $json): array {
        if (!$json) return ['vecs' => [], 'centroid' => null];
        $raw  = json_decode($json, true);
        $vecs = $raw['vecs'] ?? [];
        $cent = $raw['centroid'] ?? null;
        // Light shape checks (defensive)
        $vecs = array_values(array_filter($vecs, fn($v) => is_array($v) && count($v) === 128));
        if (!(is_array($cent) && count($cent) === 128)) $cent = null;
        return ['vecs' => $vecs, 'centroid' => $cent];
    }
    // Cache decoded & normalized embeddings + centroid.
    // Cache objects: (emp_ID, fname, mname, lname, vecs[], centroid[])
    private function getCachedEmployees() {
        return Cache::remember('face_embeddings_cache', 300, function () {
            return DB::table('employees')
                ->select('emp_ID', 'fname', 'mname', 'lname', 'face_embeddings')
                ->where('stat_1', 1)
                ->whereNotNull('face_embeddings')
                ->get()
                ->map(function ($e) {
                    $p = $this->readEmbObj($e->face_embeddings);

                    // Drop any malformed / non-numeric vectors before normalizing
                    $vecs = array_values(array_filter($p['vecs'], fn($v) => $this->isValidVec($v)));
                    $vecs = array_map(fn($v) => $this->l2Normalize($v), $vecs);

                    // Use stored centroid if valid; otherwise derive from filtered vecs
                    $cent = (is_array($p['centroid']) && count($p['centroid']) === 128)
                        ? $this->l2Normalize($p['centroid'])
                        : $this->centroid($vecs);

                    if (empty($vecs)) return null; // skip empty/malformed rows

                    return (object)[
                        'emp_ID'   => $e->emp_ID,
                        'fname'    => $e->fname,
                        'mname'    => $e->mname,
                        'lname'    => $e->lname,
                        'vecs'     => $vecs,
                        'centroid' => $cent,
                    ];
                })
                ->filter()
                ->values();
        });
    }
    // Shared matcher (squared distances). Returns [closestEmployeeObj|null, minDistanceSquared(float)]
    private function findClosestEmployee(array $probe): array {
        $probe = $this->l2Normalize($probe);
        $employees = $this->getCachedEmployees();

        $closest = null;
        $minD2   = PHP_FLOAT_MAX;

        // 1) Fast centroid pass
        foreach ($employees as $emp) {
            if ($emp->centroid) {
                $d2 = $this->l2Distance2($probe, $emp->centroid);
                if ($d2 < $minD2) { $minD2 = $d2; $closest = $emp; }
                if ($minD2 < $this->earlyExitThr2) break;
            }
        }

        // 2) Fine pass on closest's vectors if still near boundary
        if ($closest && $minD2 >= $this->earlyExitThr2) {
            foreach ($closest->vecs as $v) {
                $d2 = $this->l2Distance2($probe, $v);
                if ($d2 < $minD2) { $minD2 = $d2; }
                if ($minD2 < $this->earlyExitThr2) break;
            }
        }

        return [$closest, $minD2];
    }
    private function isValidVec($v): bool {
        if (!is_array($v) || count($v) !== 128) return false;
        foreach ($v as $x) {
            if (!is_numeric($x)) return false;
            if (!is_finite((float)$x)) return false; // guard NaN/INF
        }
        return true;
    }
    // ==================== APIs ====================
    public function faceRegister(Request $request)
    {
        $empId      = $request->input('emp_ID');
        $embedding  = $request->input('embedding');   // [128]
        $embeddings = $request->input('embeddings');  // [[128],...]

        if (!$empId) {
            return response()->json(['error' => 'Missing emp_ID'], 400);
        }

        $employee = DB::table('employees')->where('emp_ID', $empId)->first();
        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        // Collect incoming normalized embeddings using the shared validator
        $incoming = [];
        if (is_array($embeddings)) {
            foreach ($embeddings as $e) {
                if ($this->isValidVec($e)) $incoming[] = $this->l2Normalize($e);
            }
        } elseif ($this->isValidVec($embedding)) {
            $incoming[] = $this->l2Normalize($embedding);
        } else {
            return response()->json(['error' => 'Invalid embedding(s)'], 400);
        }

        if (empty($incoming)) {
            return response()->json(['error' => 'No valid embeddings'], 400);
        }

        $merged = [];

        // Transaction & row lock to prevent lost updates
        DB::transaction(function () use ($empId, $incoming, &$merged) {
            $row = DB::table('employees')
                ->where('emp_ID', $empId)
                ->lockForUpdate()
                ->first();

            $parsed   = $this->readEmbObj($row->face_embeddings ?? null);

            // Filter stored vecs too, before normalizing
            $existingRaw = array_values(array_filter($parsed['vecs'], fn($v) => $this->isValidVec($v)));
            $existing    = array_map(fn($e) => $this->l2Normalize($e), $existingRaw);

            $merged = $this->dedupeEmbeddings(array_merge($existing, $incoming));
            $merged = array_slice($merged, -$this->embeddingLimit);

            $cent = $this->centroid($merged);

            DB::table('employees')
                ->where('emp_ID', $empId)
                ->update([
                    'face_embeddings' => json_encode(['vecs' => $merged, 'centroid' => $cent])
                ]);
        });

        // Clear cache so new vectors are used immediately
        Cache::forget('face_embeddings_cache');

        return response()->json([
            'success'          => true,
            'emp_ID'           => $empId,
            'total_embeddings' => count($merged),
        ]);
    }
    public function adminFaceVerify(Request $request)
    {
        $embedding = $request->input('embedding');
        if (!$this->isValidVec($embedding)) {
            return response()->json(['error' => 'Invalid embedding'], 400);
        }

        [$closest, $minD2] = $this->findClosestEmployee($embedding);

        // Clamp early on non-match / non-finite distance
        if (!$closest || is_infinite($minD2) || is_nan($minD2) || $minD2 >= $this->acceptThr2) {
            return response()->json([
                'match'    => false,
                'is_admin' => false,
                'distance' => ($closest && !is_infinite($minD2) && !is_nan($minD2)) ? sqrt($minD2) : 2.0,
            ]);
        }

        // Admin set (read once, no cache)
        $hrKioskCsv = DB::table('settings')->value('hr_kiosk'); // e.g. "EMP0039,EMP0033,..."
        $ids = array_filter(array_map('trim', explode(',', (string) $hrKioskCsv)));
        $isAdmin = in_array($closest->emp_ID, $ids, true);

        return response()->json([
            'match'    => true,
            'is_admin' => $isAdmin,
            'emp_id'   => $closest->emp_ID,
            'name'     => trim("{$closest->fname} {$closest->mname} {$closest->lname}"),
            'distance' => sqrt($minD2),
        ]);
    }
    public function faceVerify(Request $request)
    {
        $embedding = $request->input('embedding');
        if (!$this->isValidVec($embedding)) {
            return response()->json(['error' => 'Invalid embedding'], 400);
        }

        [$closest, $minD2] = $this->findClosestEmployee($embedding);

        // Clamp when there’s no candidate or distance is non-finite
        if (!$closest || is_infinite($minD2) || is_nan($minD2)) {
            // For unit-normalized vectors, max squared L2 is 4 -> sqrt(4)=2.0
            return response()->json(['match' => false, 'distance' => 2.0]);
        }

        if ($minD2 < $this->acceptThr2) {
            return response()->json([
                'match'    => true,
                'emp_id'   => $closest->emp_ID,
                'name'     => trim("{$closest->fname} {$closest->mname} {$closest->lname}"),
                'distance' => sqrt($minD2),
            ]);
        }

        return response()->json(['match' => false, 'distance' => sqrt($minD2)]);
    }
    public function logAttendance(Request $request)
    {
        try {
            $validated = $request->validate([
                'emp_id'  => 'required|string',
                // accept "-1", "-2", "3", etc.
                'zone_id' => ['required','string','regex:/^-?\d+$/'],
                'action'  => 'required|integer|in:1,2,3',
            ]);

            // Validate employee exists and preserve DB casing
            $empRow = DB::table('employees')
                ->select('emp_ID')
                ->where('emp_ID', $validated['emp_id'])
                ->first();
            if (!$empRow) return response()->json(['error' => 'Unknown emp_ID'], 404);

            $empId    = $empRow->emp_ID;
            $zoneId   = $validated['zone_id'];
            $action   = (int) $validated['action'];
            $deviceId = $zoneId;

            $now   = now();
            $date  = $now->toDateString();
            $time  = $now->format('H:i:s'); // keep seconds precision

            $timeField = match ($action) { 1 => 'time_in', 2 => 'time_out', 3 => 'time_over' };
            $deviceField = match ($action) { 1 => 'device_id_in', 2 => 'device_id_out', 3 => 'device_id_over' };

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
                    $threshold = '11:00:00'; // policy: 11 AM (adjust if needed)
                    $validOuts = array_values(array_filter($outs, fn($t) => strtotime($t) >= strtotime($threshold)));
                    if (!empty($validOuts)) {
                        $firstQualOut = $validOuts[0];
                        $lastTime = \Carbon\Carbon::createFromFormat('H:i:s', $firstQualOut);
                        $elapsed  = $lastTime->diffInSeconds($now);
                        if ($elapsed < 60) {
                            $allowed     = false;
                            $waitSeconds = 60 - $elapsed;
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

            // 200 = updated/created, 202 = no change, 429 = rate rule blocked
            $status = $allowed ? ($didUpdate ? 200 : 202) : 429;

            return response()->json([
                'success'      => $didUpdate,
                'updated'      => $didUpdate,
                'allowed'      => $allowed,
                'wait_seconds' => $waitSeconds,
                'time'         => $now->format('h:i:s A'),
                'type'         => match ($action) { 1 => 'TIME IN', 2 => 'TIME OUT', 3 => 'OVERTIME' },
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

        return response()->json($employees);
    }
    public function fetchLogzones(\Illuminate\Http\Request $request)
    {
        $ttl = 60; // freshness
        // Build payload from cache (DB touched at most once per $ttl)
        $zones = \Cache::remember('logzones:payload', $ttl, function () {
            return \DB::table('logzones')->get()->map(function ($zone) {
                $points = json_decode($zone->points, true);
                if (!is_array($points)) $points = [];
                return [
                    'id'     => (int) $zone->id,
                    'label'  => (string) $zone->label,
                    'points' => $points,
                ];
            })->values()->all(); // store as plain array
        });
        // Derive an ETag directly from the cached payload
        $etag = sha1(json_encode($zones, JSON_UNESCAPED_UNICODE));
        // If client already has this version, short-circuit
        if (in_array($etag, $request->getEtags() ?? [], true)) {
            return response()
                ->noContent(304)
                ->setEtag($etag)
                ->header('Cache-Control', 'public, max-age=15');
        }
        return response()
            ->json($zones, 200, [], JSON_UNESCAPED_UNICODE)
            ->setEtag($etag)
            ->header('Cache-Control', 'public, max-age=15');
    }
    public function fetchRecentLogs(Request $request)
    {
        $WINDOW_HOURS = 24;

        $empId = $request->input('empId');
        if (!$empId) return response()->json(['error' => 'Missing empId'], 400);

        $cutoff = now()->subHours($WINDOW_HOURS);

        $DEFAULT_ZONE_LABEL   = 'TBD';
        $DEFAULT_DEVICE_LABEL = 'TBD';

        $zoneById   = \DB::table('logzones')->pluck('label', 'id')->toArray();
        $deviceById = \DB::table('f_devices')->pluck('label', 'id')->toArray();

        $minDate = $cutoff->copy()->subDay()->toDateString();

        $rows = \DB::table('dtrs')
            ->join('employees', 'dtrs.emp_ID', '=', 'employees.emp_ID')
            ->where('dtrs.emp_ID', $empId)
            ->where('dtrs.date', '>=', $minDate)
            ->select('dtrs.*', 'employees.fname', 'employees.lname')
            ->orderBy('dtrs.date', 'desc')
            ->get();

        $pickPlace = function (?int $id) use ($deviceById, $zoneById, $DEFAULT_DEVICE_LABEL, $DEFAULT_ZONE_LABEL) {
            if ($id === null) return $DEFAULT_ZONE_LABEL;
            if ($id > 0) return $deviceById[$id] ?? $DEFAULT_DEVICE_LABEL; // device
            return $zoneById[$id] ?? $DEFAULT_ZONE_LABEL; // zone
        };

        $out = [];

        foreach ($rows as $r) {
            // TIME IN
            $ins = array_filter(explode(',', (string) $r->time_in));
            $zin = explode(',', (string) $r->device_id_in);
            foreach ($ins as $i => $t) {
                $dt = \Carbon\Carbon::parse($r->date . ' ' . $t);
                if ($dt->lt($cutoff)) continue;

                $zRaw  = trim($zin[$i] ?? '');
                $id    = $zRaw === '' ? null : (int) $zRaw;
                $label = $pickPlace($id);

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
                $dt = \Carbon\Carbon::parse($r->date . ' ' . $t);
                if ($dt->lt($cutoff)) continue;

                $zRaw  = trim($zout[$i] ?? '');
                $id    = $zRaw === '' ? null : (int) $zRaw;
                $label = $pickPlace($id);

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
                $dt = \Carbon\Carbon::parse($r->date . ' ' . $t);
                if ($dt->lt($cutoff)) continue;

                $zRaw  = trim($zover[$i] ?? '');
                $id    = $zRaw === '' ? null : (int) $zRaw;
                $label = $pickPlace($id);

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

        usort($out, fn($a, $b) => strcmp($b['ts'], $a['ts'])); // newest first
        foreach ($out as &$row) unset($row['ts']); // don’t expose sort key

        return response()->json([
            'window_hours' => $WINDOW_HOURS,
            'logs'         => $out
        ], 200);
    }
}
