<?php

namespace App\Http\Controllers;

use App\Models\EteEvaluation;
use App\Models\Evaluator;
use App\Models\EmployeeEvaluate;
use App\Models\JobHiring;
use App\Models\Employee;
use App\Models\Application;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EteEvaluationController extends Controller
{
    private function experienceYears($value)
    {
        if (empty($value)) {
            return [];
        }

        if (str_contains($value, '-')) {
            [$from, $to] = explode('-', $value, 2);
            $from = (int) trim($from);
            $to = (int) trim($to);

            if ($from > $to) {
                return [];
            }

            return range($from, $to);
        }

        return array_filter(array_map('trim', explode(',', $value)));
    }

    private function syncApplicationEvaluators(EteEvaluation $ete, Application $application)
    {
        if ((int) $application->jid !== (int) $ete->jid) {
            abort(422, 'Applicant does not belong to this ETE position.');
        }

        $ete->loadMissing(['job', 'evaluators']);

        foreach ($ete->evaluators as $panel) {
            EmployeeEvaluate::firstOrCreate(
                [
                    'ete_id' => $ete->id,
                    'application_id' => $application->id,
                    'evaluator_id' => $panel->emp_id,
                ],
                [
                    'jid' => $ete->jid,
                    'position' => $ete->job->title ?? $application->position,
                    'education_score' => 0,
                    'training_score' => 0,
                    'experience_score' => 0,
                    'experience_year_ratings' => null,
                    'total_score' => 0,
                    'remarks' => null,
                ]
            );
        }
    }

    private function syncReviewingApplicants(EteEvaluation $ete)
    {
        $applications = Application::where('jid', $ete->jid)
            ->where('status', 1)
            ->get();

        foreach ($applications as $application) {
            $this->syncApplicationEvaluators($ete, $application);
        }
    }

    private function applicantName($app)
    {
        return trim(collect([
            $app->first_name ?? null,
            $app->middle_name ?? null,
            $app->last_name ?? null,
        ])->filter()->implode(' '));
    }

    private function ratingCompleted($rating)
    {
        return ($rating->updated_at && $rating->created_at && $rating->updated_at->gt($rating->created_at))
            || (float) $rating->education_score > 0
            || (float) $rating->training_score > 0
            || (float) $rating->experience_score > 0
            || !empty($rating->remarks);
    }

    public function eteEvaluationList()
    {
        $eteEvaluations = EteEvaluation::with([
                'job',
                'evaluators.employee',
                'employeeEvaluates.application',
                'employeeEvaluates.evaluator',
            ])
            ->latest()
            ->get();

        $jobs = JobHiring::orderBy('title')->get();
        $employees = Employee::orderBy('lname')->get();

        return view('ete.index', compact(
            'eteEvaluations',
            'jobs',
            'employees'
        ));
    }

    public function eteEvaluationStore(Request $request)
    {
        $request->validate([
            'jid' => 'required|exists:job_hirings,id',
            'evaluators' => 'required|array',
            'evaluators.*' => 'exists:employees,id',
            'evaluation_date' => 'required|date',
            'experience_years' => 'required|string',
        ]);

        $job = JobHiring::findOrFail($request->jid);

        $applications = Application::where('jid', $request->jid)
            ->where('status', 1)
            ->get();

        if ($applications->count() == 0) {
            return redirect()->back()->with(
                'error',
                'No applicants with status Reviewing found for this position.'
            );
        }

        $ete = EteEvaluation::create([
            'jid' => $request->jid,
            'evaluation_date' => Carbon::parse($request->evaluation_date),
            'experience_years' => $request->experience_years,
            'active_application_id' => null,
        ]);

        foreach ($request->evaluators as $empId) {
            Evaluator::create([
                'ete_id' => $ete->id,
                'emp_id' => $empId,
            ]);
        }

        foreach ($applications as $application) {
            foreach ($request->evaluators as $empId) {
                EmployeeEvaluate::create([
                    'ete_id' => $ete->id,
                    'application_id' => $application->id,
                    'jid' => $request->jid,
                    'evaluator_id' => $empId,
                    'position' => $job->title,
                    'education_score' => 0,
                    'training_score' => 0,
                    'experience_score' => 0,
                    'experience_year_ratings' => null,
                    'total_score' => 0,
                    'remarks' => null,
                ]);
            }
        }

        return redirect()->back()->with('success', 'ETE Evaluation created successfully.');
    }

    public function eteEvaluationShow($id)
    {
        $eteForSync = EteEvaluation::with(['job', 'evaluators'])->findOrFail($id);
        $this->syncReviewingApplicants($eteForSync);

        $ete = EteEvaluation::with([
            'job',
            'evaluators.employee',
            'employeeEvaluates.application',
            'employeeEvaluates.evaluator',
        ])->findOrFail($id);

        $applicants = $ete->employeeEvaluates
            ->groupBy('application_id')
            ->map(function ($items) {
                return $items->first()->application;
            });

        return view('ete.show', compact('ete', 'applicants'));
    }

    public function splashApplicant(Request $request)
    {
        $request->validate([
            'ete_id' => 'required|exists:ete_evaluations,id',
            'application_id' => 'required|exists:applications,id',
        ]);

        $ete = EteEvaluation::with(['job', 'evaluators'])->findOrFail($request->ete_id);
        $application = Application::findOrFail($request->application_id);

        DB::transaction(function () use ($ete, $application, $request) {
            $this->syncApplicationEvaluators($ete, $application);

            EteEvaluation::where('id', '!=', $ete->id)
                ->whereNotNull('active_application_id')
                ->update(['active_application_id' => null]);

            $ete->update([
                'active_application_id' => $request->application_id,
            ]);
        });

        return response()->json([
            'success' => true,
            'active_application_id' => $request->application_id,
        ]);
    }

    public function getActiveApplicant($id)
    {
        $ete = EteEvaluation::findOrFail($id);

        return response()->json([
            'active_application_id' => $ete->active_application_id,
        ]);
    }

    public function evaluatorRate($id, $empId)
    {
        $ete = EteEvaluation::with(['job', 'evaluators'])->findOrFail($id);

        abort_unless(
            $ete->evaluators->contains('emp_id', (int) $empId),
            403,
            'This employee is not assigned as evaluator for this ETE.'
        );

        if ($ete->active_application_id) {
            $application = Application::find($ete->active_application_id);

            if ($application) {
                $this->syncApplicationEvaluators($ete, $application);
            }
        }

        $ratings = EmployeeEvaluate::with(['application'])
            ->where('ete_id', $id)
            ->where('evaluator_id', $empId)
            ->orderBy('application_id')
            ->get();

        $evaluator = Employee::findOrFail($empId);
        $years = $this->experienceYears($ete->experience_years);

        return view('ete.evaluator-rate', compact(
            'ete',
            'ratings',
            'evaluator',
            'years'
        ));
    }

    public function eteRatingUpdateAjax(Request $request)
    {
        $request->validate([
            'evaluate_id' => 'required|exists:employee_evaluates,id',
            'education_score' => 'required|numeric|min:0|max:10',
            'training_score' => 'required|numeric|min:0|max:5',
            'remarks' => 'nullable|string',
            'experience_years' => 'nullable|array',
        ]);

        $rating = EmployeeEvaluate::with('eteEvaluation.evaluators')->findOrFail($request->evaluate_id);

        abort_unless(
            $rating->eteEvaluation
                && $rating->eteEvaluation->evaluators->contains('emp_id', (int) $rating->evaluator_id),
            403,
            'Invalid evaluator assignment.'
        );

        $educationScore = min(10, max(0, (float) $request->education_score));
        $trainingScore = min(5, max(0, (float) $request->training_score));
        $experienceScore = 0;

        if ($request->experience_years) {
            foreach ($request->experience_years as $year => $data) {
                $experienceScore += floatval($data['credit'] ?? 0);
            }
        }

        if ($experienceScore > 15) {
            $experienceScore = 15;
        }

        $total = $educationScore
            + $trainingScore
            + $experienceScore;

        $rating->update([
            'education_score' => $educationScore,
            'training_score' => $trainingScore,
            'experience_score' => $experienceScore,
            'experience_year_ratings' => $request->experience_years,
            'total_score' => $total,
            'remarks' => $request->remarks,
        ]);

        return response()->json([
            'success' => true,
            'education_score' => number_format($educationScore, 2),
            'training_score' => number_format($trainingScore, 2),
            'experience_score' => number_format($experienceScore, 2),
            'total_score' => number_format($total, 2),
        ]);
    }

    public function selectedApplicantConsolidated($id)
    {
        $ete = EteEvaluation::with([
            'activeApplication',
            'employeeEvaluates.application',
            'employeeEvaluates.evaluator',
            'evaluators.employee',
        ])->findOrFail($id);

        if (!$ete->active_application_id) {
            return response()->json([
                'success' => false,
                'message' => 'No active applicant selected.',
            ]);
        }

        $ratings = $ete->employeeEvaluates
            ->where('application_id', $ete->active_application_id);

        $app = $ratings->first()->application ?? null;

        if (!$app) {
            return response()->json([
                'success' => false,
                'message' => 'Applicant not found.',
            ]);
        }

        return response()->json([
            'success' => true,
            'application_id' => $app->id,
            'app_number' => $app->app_number,
            'name' => $this->applicantName($app),
            'education_avg' => number_format($ratings->avg('education_score'), 2),
            'training_avg' => number_format($ratings->avg('training_score'), 2),
            'experience_avg' => number_format($ratings->avg('experience_score'), 2),
            'total_avg' => number_format($ratings->avg('total_score'), 2),
            'evaluator_count' => $ete->evaluators->count(),
            'completed_count' => $ratings->filter(fn ($rating) => $this->ratingCompleted($rating))->count(),
            'evaluators' => $ratings->values()->map(function ($rating) use ($id) {
                return [
                    'id' => $rating->evaluator_id,
                    'name' => trim(($rating->evaluator->lname ?? '') . ', ' . ($rating->evaluator->fname ?? '')),
                    'education_score' => number_format((float) $rating->education_score, 2),
                    'training_score' => number_format((float) $rating->training_score, 2),
                    'experience_score' => number_format((float) $rating->experience_score, 2),
                    'total_score' => number_format((float) $rating->total_score, 2),
                    'completed' => $this->ratingCompleted($rating),
                    'url' => route('eteEvaluatorRate', [$id, $rating->evaluator_id]),
                ];
            }),
        ]);
    }

    public function consolidatedScreen($id)
    {
        $eteForSync = EteEvaluation::with(['job', 'evaluators'])->findOrFail($id);
        $this->syncReviewingApplicants($eteForSync);

        $ete = EteEvaluation::with([
            'job',
            'employeeEvaluates.application',
        ])->findOrFail($id);

        return view('ete.consolidated-screen', compact('ete'));
    }

    public function consolidatedData($id)
    {
        $ete = EteEvaluation::with([
            'evaluators.employee',
            'employeeEvaluates.application',
            'employeeEvaluates.evaluator',
        ])->findOrFail($id);

        $data = $ete->employeeEvaluates
            ->groupBy('application_id')
            ->map(function ($ratings) {
                $app = $ratings->first()->application;

                if (!$app) {
                    return null;
                }

                $totalAvg = $ratings->avg('total_score');

                return [
                    'application_id' => $app->id,
                    'app_number' => $app->app_number,
                    'name' => $this->applicantName($app),
                    'education_avg' => number_format($ratings->avg('education_score'), 2),
                    'training_avg' => number_format($ratings->avg('training_score'), 2),
                    'experience_avg' => number_format($ratings->avg('experience_score'), 2),
                    'total_avg' => number_format($totalAvg, 2),
                    'total_raw' => $totalAvg,
                    'completed_count' => $ratings->filter(fn ($rating) => $this->ratingCompleted($rating))->count(),
                    'evaluator_count' => $ratings->count(),
                ];
            })
            ->filter()
            ->sortByDesc('total_raw')
            ->values()
            ->map(function ($item, $index) {
                $item['rank'] = $index + 1;
                return $item;
            });

        return response()->json([
            'success' => true,
            'active_application_id' => $ete->active_application_id,
            'evaluator_count' => $ete->evaluators->count(),
            'data' => $data,
        ]);
    }
}
