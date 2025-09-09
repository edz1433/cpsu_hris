<?php

namespace App\Http\Controllers\Api;

use App\Models\Dtr;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class TimeEntryController extends Controller
{
    // ==== CONFIG ====
    private int   $embeddingLimit = 7;            // keep only latest embeddings (post-dedupe)
    // Squared thresholds (avoid sqrt in hot path)
    private float $dedupeThr2     = 0.28 * 0.28;  // drop near-duplicates on register
    private float $earlyExitThr2  = 0.40 * 0.40;  // early-accept boundary for centroid pass
    private float $acceptThr2     = 0.75 * 0.75;  // match acceptance threshold    
    // ====  Helpers (embeddings & matching) ====
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
        $acc = array_fill(0, 128, 0.0);
        $k = 0;
        foreach ($embs as $e) {
            if (!is_array($e) || count($e) !== 128) continue;
            $k++;
            for ($i = 0; $i < 128; $i++) { $acc[$i] += $e[$i]; }
        }
        if ($k === 0) return null;
        for ($i = 0; $i < 128; $i++) { $acc[$i] /= $k; }
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
        return Cache::rememberForever('face_embeddings_cache', function () {
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
        $closest = null; $minD2 = PHP_FLOAT_MAX;
        // Pass 1: scan all centroids
        foreach ($employees as $emp) {
            if (!$emp->centroid) continue;
            $d2 = $this->l2Distance2($probe, $emp->centroid);
            if ($d2 < $minD2) { $minD2 = $d2; $closest = $emp; }
        }
        if (!$closest) return [null, $minD2];
        // If already very close to this centroid, accept early
        if ($minD2 < $this->earlyExitThr2) return [$closest, $minD2];
        // Pass 2: refine on that employee’s individual vectors
        foreach ($closest->vecs as $v) {
            $d2 = $this->l2Distance2($probe, $v);
            if ($d2 < $minD2) { $minD2 = $d2; if ($minD2 < $this->earlyExitThr2) break; }
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
    public function adminPassVerify(Request $request): \Illuminate\Http\JsonResponse
    {
        // Only require password, with a sane length cap
        $request->validate([
            'password' => 'required|string|max:30',
        ]);
        try {
            // Read bcrypt hash directly
            $hash = DB::connection('mysql')
                ->table('settings')
                ->value('hrk_pw');
            if (!is_string($hash) || $hash === '') {
                return response()->json([
                    'ok'      => false,
                    'message' => 'Password not set.',
                ], 500);
            }
            $input = (string) $request->input('password');
            if (Hash::check($input, $hash)) {
                // Optional: keep hashes fresh if cost changes
                if (Hash::needsRehash($hash)) {
                    DB::connection('mysql')
                        ->table('settings')
                        ->limit(1)
                        ->update(['hrk_pw' => Hash::make($input)]);
                }
                return response()->json(['ok' => true], 200);
            }
            // Wrong password: still HTTP 200, ok=false
            return response()->json([
                'ok'      => false,
                'message' => 'Incorrect password.',
            ], 200);
        } catch (\Throwable $e) {
            // Generic server error
            return response()->json([
                'ok'      => false,
                'message' => 'Server error.',
            ], 500);
        }
    }
    public function checkRestrictionLevel(Request $request)
    {
        // $row   = DB::table('settings')->first();
        $ttl = 5; // seconds
        $row = Cache::remember('settings:te_rstrct', $ttl, function () {
            return DB::table('settings')->select('te_rstrct_lvl')->first();
        });
        $level = (int) ($row?->te_rstrct_lvl ?? 2);
        // Raw window in 24h
        $startStr = '11:00';
        $endStr   = '13:00';
        $tz    = 'Asia/Manila';
        $now   = Carbon::now($tz);
        $start = Carbon::createFromFormat('H:i', $startStr, $tz)->setDate($now->year, $now->month, $now->day);
        $end   = Carbon::createFromFormat('H:i', $endStr,   $tz)->setDate($now->year, $now->month, $now->day);
        // Handle overnight (e.g., 23:00–02:00)
        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }
        // ---- helper to format window in 12h and collapse AM/PM when same ----
        $fmtWindow = function (Carbon $s, Carbon $e) {
            $sTime = $s->format('g:i');
            $eTime = $e->format('g:i');
            $sMer  = $s->format('A');
            $eMer  = $e->format('A');
            // Collapse only when they are on the same calendar day AND same meridiem
            if ($s->isSameDay($e) && $sMer === $eMer) {
                return "{$sTime}–{$eTime} {$sMer}";
            }
            return "{$sTime} {$sMer}–{$eTime} {$eMer}";
        };
        $allowed = true;
        $message = null;
        if ($level === 2) {
            $allowed = false;
            $message = 'Not available.';
        } elseif ($level === 1) {
            $allowed = $now->between($start, $end); // inclusive bounds
            if (!$allowed) {
                $message = 'Only allowed from ' . $fmtWindow($start, $end) . '.';
            }
        }
        return response()->json([
            'level'       => $level,
            'allowed'     => $allowed,
            // Keep individual parts in 12h, plus a preformatted label that collapses AM/PM
            'window'      => [
                'start' => $start->format('g:i A'),
                'end'   => $end->format('g:i A'),
                'label' => $fmtWindow($start, $end),
            ],
            'server_time' => $now->toIso8601String(),
            'message'     => $allowed ? null : ($message ?? 'Action not available.'),
        ]);
    }
    public function faceRegister(Request $request) {
        $empId      = $request->input('emp_ID');
        $enrollerId = $request->input('enroller_ID');   // <-- required for atomic success
        $embedding  = $request->input('embedding');
        $embeddings = $request->input('embeddings');
        if (!$empId || !$enrollerId) {
            return response()->json(['error' => 'Missing emp_ID or enroller_ID'], 400);
        }
        // Validate both employees exist
        if (!DB::table('employees')->where('emp_ID', $empId)->exists()) {
            return response()->json(['error' => 'Employee not found'], 404);
        }
        if (!DB::table('employees')->where('emp_ID', $enrollerId)->exists()) {
            return response()->json(['error' => 'Invalid enroller_ID'], 400);
        }
        // Normalize incoming embeddings (same as yours)
        $incoming = [];
        if (is_array($embeddings)) {
            foreach ($embeddings as $e) if ($this->isValidVec($e)) $incoming[] = $this->l2Normalize($e);
        } elseif ($this->isValidVec($embedding)) {
            $incoming[] = $this->l2Normalize($embedding);
        } else {
            return response()->json(['error' => 'Invalid embedding(s)'], 400);
        }
        if (empty($incoming)) return response()->json(['error' => 'No valid embeddings'], 400);
        try {
            $result = DB::transaction(function () use ($empId, $enrollerId, $incoming) {
                $now     = now();
                $today   = $now->toDateString();
                $nowTime = $now->format('H:i:s');
                // ---- 1) Save embeddings (LOCK row) ----
                $row = DB::table('employees')
                    ->where('emp_ID', $empId)
                    ->lockForUpdate()
                    ->first();
                $parsed      = $this->readEmbObj($row->face_embeddings ?? null);
                $existingRaw = array_values(array_filter($parsed['vecs'], fn($v) => $this->isValidVec($v)));
                $existing    = array_map(fn($e) => $this->l2Normalize($e), $existingRaw);
                $merged = $this->dedupeEmbeddings(array_merge($existing, $incoming));
                $merged = array_slice($merged, -$this->embeddingLimit);
                $cent   = $this->centroid($merged);
                DB::table('employees')
                    ->where('emp_ID', $empId)
                    ->update(['face_embeddings' => json_encode(['vecs' => $merged, 'centroid' => $cent])]);
                // ---- 2) Append/Upsert registration history (concurrency-safe) ----
                DB::statement(
                    "INSERT INTO emp_timeentry_reghist (enroller_ID, `date`, enrolled_IDs, enrolled_times)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                    enrolled_IDs = IF(enrolled_IDs IS NULL OR enrolled_IDs='',
                                        VALUES(enrolled_IDs),
                                        CONCAT(enrolled_IDs, ',', VALUES(enrolled_IDs))),
                    enrolled_times = IF(enrolled_times IS NULL OR enrolled_times='',
                                        VALUES(enrolled_times),
                                        CONCAT(enrolled_times, ',', VALUES(enrolled_times)))",
                    [$enrollerId, $today, $empId, $nowTime]
                );
                return ['total' => count($merged)];
            });
            // outside the transaction (non-critical)
            Cache::forget('face_embeddings_cache');
            return response()->json([
                'success'          => true,
                'emp_ID'           => $empId,
                'total_embeddings' => $result['total'],
            ], 200);
        } catch (\Throwable $e) {
            // If either step fails, nothing is committed and frontend gets an error
            return response()->json([
                'error'   => 'Server error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function faceVerify(Request $request) {
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
    public function adminFaceVerify(Request $request) {
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
    // public function logAttendance(Request $request) {
    //     try {
    //         $validated = $request->validate([
    //             'emp_id'  => 'required|string',
    //             // accept "-1", "-2", "3", etc.
    //             'zone_id' => ['required','string','regex:/^-?\d+$/'],
    //             'action'  => 'required|integer|in:1,2,3',
    //         ]);

    //         // Validate employee exists and preserve DB casing
    //         $empRow = DB::table('employees')
    //             ->select('emp_ID')
    //             ->where('emp_ID', $validated['emp_id'])
    //             ->first();
    //         if (!$empRow) return response()->json(['error' => 'Unknown emp_ID'], 404);

    //         $empId    = $empRow->emp_ID;
    //         $zoneId   = $validated['zone_id'];
    //         $action   = (int) $validated['action'];
    //         $deviceId = $zoneId;

    //         $now   = now();
    //         $date  = $now->toDateString();
    //         $time  = $now->format('H:i:s'); // keep seconds precision

    //         $timeField = match ($action) { 1 => 'time_in', 2 => 'time_out', 3 => 'time_over' };
    //         $deviceField = match ($action) { 1 => 'device_id_in', 2 => 'device_id_out', 3 => 'device_id_over' };

    //         $allowed     = true;
    //         $waitSeconds = 0;
    //         $didUpdate   = false;

    //         $record = Dtr::where('emp_ID', $empId)->where('date', $date)->first();

    //         if ($record) {
    //             $existingTimes   = $record->$timeField ? explode(',', $record->$timeField) : [];
    //             $existingDevices = $record->$deviceField ? explode(',', $record->$deviceField) : [];

    //             // TIME IN rule: block if first OUT >= threshold was <60s ago
    //             if ($action === 1 && !empty($record->time_out)) {
    //                 $outs = array_map('trim', explode(',', $record->time_out));
    //                 $threshold = '11:00:00'; // policy: 11 AM (adjust if needed)
    //                 $validOuts = array_values(array_filter($outs, fn($t) => strtotime($t) >= strtotime($threshold)));
    //                 if (!empty($validOuts)) {
    //                     $firstQualOut = $validOuts[0];
    //                     $lastTime = \Carbon\Carbon::createFromFormat('H:i:s', $firstQualOut);
    //                     $elapsed  = $lastTime->diffInSeconds($now);
    //                     if ($elapsed < 60) {
    //                         $allowed     = false;
    //                         $waitSeconds = 60 - $elapsed;
    //                     }
    //                 }
    //             }

    //             // Append only if allowed and not an exact duplicate second
    //             if ($allowed && !in_array($time, $existingTimes, true)) {
    //                 $existingTimes[]   = $time;
    //                 $existingDevices[] = $deviceId;

    //                 $record->update([
    //                     $timeField   => implode(',', $existingTimes),
    //                     $deviceField => implode(',', $existingDevices),
    //                 ]);
    //                 $didUpdate = true;
    //             }
    //         } else {
    //             Dtr::create([
    //                 'emp_ID'     => $empId,
    //                 'date'       => $date,
    //                 $timeField   => $time,
    //                 $deviceField => $deviceId,
    //             ]);
    //             $didUpdate = true;
    //         }

    //         // 200 = updated/created, 202 = no change, 429 = rate rule blocked
    //         $status = $allowed ? ($didUpdate ? 200 : 202) : 429;

    //         return response()->json([
    //             'success'      => $didUpdate,
    //             'updated'      => $didUpdate,
    //             'allowed'      => $allowed,
    //             'wait_seconds' => $waitSeconds,
    //             'time'         => $now->format('h:i:s A'),
    //             'type'         => match ($action) { 1 => 'TIME IN', 2 => 'TIME OUT', 3 => 'OVERTIME' },
    //             'emp_id'       => $empId,
    //             'zone_id'      => $zoneId,
    //         ], $status);

    //     } catch (\Throwable $e) {
    //         return response()->json([
    //             'error'   => 'Server error',
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    public function logAttendance(Request $request)
    {
        try {
            $validated = $request->validate([
                'emp_id'  => 'required|string',
                'zone_id' => ['required','string','regex:/^-?\d+$/'],
                'action'  => 'required|integer|in:1,2,3',
            ]);
            // Preserve DB casing for emp_ID
            $empRow = DB::table('employees')
                ->select('emp_ID')
                ->where('emp_ID', $validated['emp_id'])
                ->first();
            if (!$empRow) {
                return response()->json(['error' => 'Unknown emp_ID'], 404);
            }
            $empId    = $empRow->emp_ID;
            $zoneId   = $validated['zone_id'];
            $action   = (int) $validated['action'];
            $deviceId = (string) $zoneId;
            $now  = now();                       // current server time
            $date = $now->toDateString();        // YYYY-MM-DD
            $time = $now->format('H:i:s');       // HH:MM:SS (seconds precision)
            $timeField   = match ($action) { 1 => 'time_in', 2 => 'time_out', 3 => 'time_over' };
            $deviceField = match ($action) { 1 => 'device_id_in', 2 => 'device_id_out', 3 => 'device_id_over' };
            $allowed     = true;
            $waitSeconds = 0;
            $didUpdate   = false;
            DB::transaction(function () use (
                $empId, $date, $time, $timeField, $deviceField, $deviceId, $now,
                &$allowed, &$waitSeconds, &$didUpdate, $action
            ) {
                // Lock all rows for this (emp_ID, date); newest first so ->first() is MAX(id)
                $rows = \App\Models\Dtr::where('emp_ID', $empId)
                    ->where('date', $date)
                    ->lockForUpdate()
                    ->orderByDesc('id')
                    ->get();

                $record = $rows->first();

                if (!$record) {
                    // No row yet: create the canonical row (it becomes MAX(id) by definition)
                    \App\Models\Dtr::create([
                        'emp_ID'     => $empId,
                        'date'       => $date,
                        $timeField   => $time,
                        $deviceField => $deviceId,
                    ]);
                    $didUpdate = true;
                    return;
                }

                // Throttle rule (unchanged semantics):
                // TIME IN blocked if the first OUT >= 11:00:00 was < 60s ago.
                if ($action === 1 && !empty($record->time_out)) {
                    $outs = array_values(array_filter(array_map('trim', explode(',', $record->time_out))));
                    if (!empty($outs)) {
                        $threshold = '11:00:00';
                        $validOuts = array_values(array_filter($outs, fn ($t) => strtotime($t) >= strtotime($threshold)));
                        if (!empty($validOuts)) {
                            $firstQualOut = $validOuts[0];
                            // Compare as "today HH:MM:SS" vs server "now"
                            $lastOut = Carbon::createFromFormat('Y-m-d H:i:s', $date.' '.$firstQualOut, $now->timezone);
                            $elapsed = $lastOut->diffInSeconds($now);
                            if ($elapsed < 60) {
                                $allowed     = false;
                                $waitSeconds = 60 - $elapsed;
                            }
                        }
                    }
                }

                // Append only to the canonical (MAX id) row and avoid exact duplicate second
                if ($allowed) {
                    $existingTimes   = $record->$timeField   ? array_values(array_filter(array_map('trim', explode(',', $record->$timeField))))   : [];
                    $existingDevices = $record->$deviceField ? array_values(array_filter(array_map('trim', explode(',', $record->$deviceField)))) : [];

                    if (!in_array($time, $existingTimes, true)) {
                        $existingTimes[]   = $time;
                        $existingDevices[] = $deviceId;

                        $record->update([
                            $timeField   => implode(',', $existingTimes),
                            $deviceField => implode(',', $existingDevices),
                        ]);
                        $didUpdate = true;
                    }
                }
            });

            // 200 = updated/created, 202 = no change, 429 = throttled
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
    public function fetchEmployees(Request $request) {
        $employees = DB::table('employees')
            ->select('emp_ID', 'fname', 'mname', 'lname')
            ->where('stat_1', 1)
            ->orderBy('lname')
            ->get()
            ->map(function ($emp) {
                $parts = array_filter([$emp->fname, $emp->mname, $emp->lname]);
                return [
                    'emp_ID' => $emp->emp_ID,
                    'name'   => implode(' ', $parts),
                ];
            });

        return response()->json($employees);
    }
    public function fetchLogzones(Request $request) {
        $ttl = 60; // seconds
        // Build payload from cache (DB touched at most once per $ttl)
        $zones = Cache::remember('logzones:payload', $ttl, function () {
            return DB::table('logzones')->get()->map(function ($zone) {
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
    public function fetchLatestLogs(Request $request) {
        $MAX_DATES = 35;
        $empId = $request->input('empId');
        if (!$empId) return response()->json(['error' => 'Missing empId'], 400);
        // Atomic burst gate (per empId)
        $key = 'latestlogs:' . $empId;
        $cooldown = 3;
        if (!Cache::add($key, 1, now()->addSeconds($cooldown))) {
            return response()
                ->json(['error' => 'Too many requests'], 429)
                ->header('Retry-After', (string) $cooldown);
        }
        // ---------- Lookups ----------
        $DEFAULT_CAMPUS_NAME  = 'TBD';
        $DEFAULT_ZONE_LABEL   = 'TBD';
        $DEFAULT_DEVICE_LABEL = 'TBD';
        $zoneById   = DB::table('logzones')->pluck('label', 'id')->toArray();
        $deviceById = DB::table('f_devices')->pluck('label', 'id')->toArray();
        $zoneCampusIdById   = DB::table('logzones')->pluck('camp_id', 'id')->toArray();
        $deviceCampusIdById = DB::table('f_devices')->pluck('camp_id', 'id')->toArray();
        $campusNameById = DB::table('campuses')->pluck('campus_name', 'id')->toArray();
        // ---------- Pickers ----------
        $pickCampusName = function (?int $id) use ($deviceCampusIdById, $zoneCampusIdById, $campusNameById, $DEFAULT_CAMPUS_NAME) {
            if ($id === null) return $DEFAULT_CAMPUS_NAME;
            $campId = $id > 0 ? ($deviceCampusIdById[$id] ?? null) : ($zoneCampusIdById[$id] ?? null);
            return $campId !== null ? ($campusNameById[$campId] ?? $DEFAULT_CAMPUS_NAME) : $DEFAULT_CAMPUS_NAME;
        };
        $pickPlace = function (?int $id) use ($deviceById, $zoneById, $DEFAULT_DEVICE_LABEL, $DEFAULT_ZONE_LABEL) {
            if ($id === null) return $DEFAULT_ZONE_LABEL;
            return $id > 0 ? ($deviceById[$id] ?? $DEFAULT_DEVICE_LABEL) : ($zoneById[$id] ?? $DEFAULT_ZONE_LABEL);
        };
        // ---------- Determine latest up to 5 dates with any logs ----------
        // "Any logs" means at least one of time_in / time_out / time_over is non-empty.
        $dates = DB::table('dtrs')
            ->where('emp_ID', $empId)
            ->where(function ($q) {
                $q->where(function ($q2) { $q2->whereNotNull('time_in')->where('time_in', '!=', ''); })
                ->orWhere(function ($q2) { $q2->whereNotNull('time_out')->where('time_out', '!=', ''); })
                ->orWhere(function ($q2) { $q2->whereNotNull('time_over')->where('time_over', '!=', ''); });
            })
            ->orderBy('date', 'desc')
            ->limit($MAX_DATES)
            ->pluck('date')
            ->toArray();

        if (empty($dates)) {
            return response()->json([
                'window_days'   => $MAX_DATES,
                'dates_included'=> [],
                'logs'          => [],
            ], 200);
        }
        // Fetch rows for those dates (includes employee name fields)
        $rows = DB::table('dtrs')
            ->join('employees', 'dtrs.emp_ID', '=', 'employees.emp_ID')
            ->where('dtrs.emp_ID', $empId)
            ->whereIn('dtrs.date', $dates)
            ->select('dtrs.*', 'employees.fname', 'employees.lname', 'employees.suffix')
            ->orderBy('dtrs.date', 'desc')
            ->get();
        $out = [];
        foreach ($rows as $r) {
            // TIME IN
            $ins = array_filter(explode(',', (string) $r->time_in));
            $zin = explode(',', (string) $r->device_id_in);
            foreach ($ins as $i => $t) {
                $dt = Carbon::parse($r->date.' '.$t);
                $zRaw = trim($zin[$i] ?? '');
                $id   = $zRaw === '' ? null : (int)$zRaw;
                $out[] = [
                    'type'         => 'time_in',
                    'date'         => $r->date,
                    'time'         => $t,
                    'fname'        => $r->fname,
                    'lname'        => $r->lname,
                    'suffix'       => $r->suffix,
                    'zone_id'      => $id,
                    'campus_name'  => $pickCampusName($id),
                    'zone_label'   => $pickPlace($id),
                    'ts'           => $dt->toIso8601String(),
                ];
            }
            // TIME OUT
            $outs = array_filter(explode(',', (string) $r->time_out));
            $zout = explode(',', (string) $r->device_id_out);
            foreach ($outs as $i => $t) {
                $dt = Carbon::parse($r->date.' '.$t);
                $zRaw = trim($zout[$i] ?? '');
                $id   = $zRaw === '' ? null : (int)$zRaw;
                $out[] = [
                    'type'         => 'time_out',
                    'date'         => $r->date,
                    'time'         => $t,
                    'fname'        => $r->fname,
                    'lname'        => $r->lname,
                    'suffix'       => $r->suffix,
                    'zone_id'      => $id,
                    'campus_name'  => $pickCampusName($id),
                    'zone_label'   => $pickPlace($id),
                    'ts'           => $dt->toIso8601String(),
                ];
            }
            // OVERTIME
            $overs = array_filter(explode(',', (string) $r->time_over));
            $zover = explode(',', (string) $r->device_id_over);
            foreach ($overs as $i => $t) {
                $dt = Carbon::parse($r->date.' '.$t);
                $zRaw = trim($zover[$i] ?? '');
                $id   = $zRaw === '' ? null : (int)$zRaw;
                $out[] = [
                    'type'         => 'time_over',
                    'date'         => $r->date,
                    'time'         => $t,
                    'fname'        => $r->fname,
                    'lname'        => $r->lname,
                    'suffix'       => $r->suffix,
                    'zone_id'      => $id,
                    'campus_name'  => $pickCampusName($id),
                    'zone_label'   => $pickPlace($id),
                    'ts'           => $dt->toIso8601String(),
                ];
            }
        }
        // Sort newest first by true timestamp
        usort($out, fn($a,$b) => strcmp($b['ts'], $a['ts']));
        foreach ($out as &$row) unset($row['ts']);
        return response()->json([
            'window_days'    => $MAX_DATES,
            'dates_included' => $dates, // newest -> oldest (as selected)
            'logs'           => $out,
        ], 200);
    }    
}
