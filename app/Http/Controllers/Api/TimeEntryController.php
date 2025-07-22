<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TimeEntryController extends Controller
{
    private $embeddingLimit = 9; // keep only last 9 embeddings

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
                'emp_ID' => $closestEmployee->emp_ID,
                'name' => trim("{$closestEmployee->fname} {$closestEmployee->mname} {$closestEmployee->lname}"),
                'distance' => $minDistance
            ]);
        }

        return response()->json(['match' => false, 'distance' => $minDistance]);
    }

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

    private function l2Distance($a, $b)
    {
        $sum = 0;
        for ($i = 0; $i < 128; $i++) {
            $diff = $a[$i] - $b[$i];
            $sum += $diff * $diff;
        }
        return sqrt($sum);
    }

    public function listEmployees()
    {
        $employees = DB::table('employees')
            ->select('emp_ID', DB::raw("CONCAT(fname, ' ', COALESCE(mname, ''), ' ', lname) AS name"))
            ->orderBy('lname')
            ->get();

        return response()->json($employees);
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
}
