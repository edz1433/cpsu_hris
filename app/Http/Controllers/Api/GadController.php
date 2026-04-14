<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;

class GadController extends Controller
{
    public function genderCount() {
        $allcampus = Employee::query()
            ->where('stat_1', 1)
            ->select('sex')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('sex')
            ->get();

        $byCampus = Employee::query()
            ->where('employees.stat_1', 1)
            ->join('campuses', 'employees.camp_id', '=', 'campuses.id')
            ->select('campuses.campus_name', 'sex')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('campuses.campus_name', 'sex')
            ->get();

        return response()->json([
            'allcampus' => $allcampus,
            'bycampus' => $byCampus
        ]);
    }
}
