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
use Illuminate\Support\Facades\Crypt;

class EteEvaluationController extends Controller
{
    private function authorizeEteAdmin()
    {
        abort_unless(auth()->guard('web')->check(), 403, 'Only HR administrators can manage ETE evaluations.');
    }

    private function authorizeEvaluator(EmployeeEvaluate $rating)
    {
        if (auth()->guard('employee')->check()) {
            abort_unless(
                (int) auth()->guard('employee')->user()->id === (int) $rating->evaluator_id,
                403,
                'You can only submit your own ETE rating.'
            );
        }
    }

    private function authorizeEvaluatorPage($empId)
    {
        if (auth()->guard('employee')->check()) {
            abort_unless(
                (int) auth()->guard('employee')->user()->id === (int) $empId,
                403,
                'You can only open your own ETE rating page.'
            );
        }
    }

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
                    'evaluation_date' => optional($ete->evaluation_date)->toDateString(),
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
            || $rating->education_met !== null
            || $rating->experience_met !== null
            || $rating->eligibility_met !== null
            || $rating->training_met !== null
            || (float) $rating->education_score > 0
            || (float) $rating->training_score > 0
            || (float) $rating->experience_score > 0
            || !empty($rating->remarks);
    }

    public function eteEvaluationList()
    {
        $this->authorizeEteAdmin();

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
        $this->authorizeEteAdmin();

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
                    'evaluation_date' => Carbon::parse($request->evaluation_date)->toDateString(),
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
        $this->authorizeEteAdmin();

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
        $this->authorizeEteAdmin();

        $request->validate([
            'ete_id' => 'required|exists:ete_evaluations,id',
            'application_id' => 'required|exists:applications,id',
            'action' => 'required|in:cast,uncast',
        ]);

        $ete = EteEvaluation::with(['job', 'evaluators'])->findOrFail($request->ete_id);
        $application = Application::findOrFail($request->application_id);

        DB::transaction(function () use ($ete, $application, $request) {
            $this->syncApplicationEvaluators($ete, $application);

            if ($request->action === 'cast') {
                EteEvaluation::where('id', '!=', $ete->id)
                    ->whereNotNull('active_application_id')
                    ->update(['active_application_id' => null]);

                $ete->update([
                    'active_application_id' => $application->id,
                ]);
            } elseif ((int) $ete->active_application_id === (int) $application->id) {
                $ete->update(['active_application_id' => null]);
            }
        });

        return response()->json([
            'success' => true,
            'active_application_id' => $ete->fresh()->active_application_id,
        ]);
    }

    public function getActiveApplicant($id)
    {
        $ete = EteEvaluation::findOrFail($id);

        if (auth()->guard('employee')->check()) {
            abort_unless(
                $ete->evaluators()->where('emp_id', auth()->guard('employee')->user()->id)->exists(),
                403,
                'You are not assigned to this ETE evaluation.'
            );
        }

        return response()->json([
            'active_application_id' => $ete->active_application_id,
        ]);
    }

    public function myActiveEvaluation()
    {
        abort_unless(auth()->guard('employee')->check(), 403);

        $employeeId = auth()->guard('employee')->user()->id;

        $ete = EteEvaluation::with(['job', 'activeApplication'])
            ->whereNotNull('active_application_id')
            ->whereHas('evaluators', function ($query) use ($employeeId) {
                $query->where('emp_id', $employeeId);
            })
            ->whereHas('employeeEvaluates', function ($query) use ($employeeId) {
                $query->where('evaluator_id', $employeeId)
                    ->whereColumn(
                        'employee_evaluates.application_id',
                        'ete_evaluations.active_application_id'
                    );
            })
            ->latest('updated_at')
            ->first();

        if (!$ete || !$ete->activeApplication) {
            return response()->json(['active' => false]);
        }

        return response()->json([
            'active' => true,
            'ete_id' => $ete->id,
            'evaluator_id' => $employeeId,
            'application_id' => $ete->active_application_id,
            'applicant_name' => $this->applicantName($ete->activeApplication),
            'position' => $ete->job->title ?? $ete->activeApplication->position,
            'url' => route('eteMyEvaluatorRate', $ete->id),
        ]);
    }

    public function myEvaluatorRate($id)
    {
        abort_unless(auth()->guard('employee')->check(), 403);

        return $this->evaluatorRate($id, auth()->guard('employee')->user()->id);
    }

    public function evaluatorRate($id, $empId)
    {
        $this->authorizeEvaluatorPage($empId);

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
            'evaluation_date' => 'nullable|date',
            'present_position' => 'nullable|string|max:255',
            'college_department' => 'nullable|string|max:255',
            'education_met' => 'nullable|boolean',
            'experience_met' => 'nullable|boolean',
            'eligibility_met' => 'nullable|boolean',
            'training_met' => 'nullable|boolean',
            'education_ratings' => 'nullable|array',
            'education_ratings.*' => 'nullable|boolean',
            'training_ratings' => 'nullable|array',
            'training_ratings.scholarship_grant' => 'nullable|boolean',
            'training_ratings.leadership_seminar' => 'nullable|boolean',
            'training_ratings.relevant_hours' => 'nullable|numeric|min:0|max:10000',
            'remarks' => 'nullable|string',
            'experience_years' => 'nullable|array',
        ]);

        $rating = EmployeeEvaluate::with('eteEvaluation.evaluators')->findOrFail($request->evaluate_id);

        $this->authorizeEvaluator($rating);

        abort_unless(
            $rating->eteEvaluation
                && $rating->eteEvaluation->evaluators->contains('emp_id', (int) $rating->evaluator_id),
            403,
            'Invalid evaluator assignment.'
        );

        abort_unless(
            (int) $rating->eteEvaluation->active_application_id === (int) $rating->application_id,
            409,
            'This applicant is no longer the active ETE applicant.'
        );

        $educationCredits = [
            'additional_four_year_course' => 2,
            'masteral_1_18' => 1,
            'masteral_19_30' => 2,
            'masters_degree' => 4,
            'doctoral_1_18' => 5,
            'doctoral_19_36' => 6,
            'doctoral_degree' => 10,
        ];
        $educationRatings = [];
        $educationScore = 0;

        foreach ($educationCredits as $key => $credit) {
            $selected = $request->boolean("education_ratings.$key");
            $educationRatings[$key] = $selected;

            if ($selected) {
                $educationScore += $credit;
            }
        }

        $educationScore = min(10, $educationScore);

        $trainingRatings = [
            'scholarship_grant' => $request->boolean('training_ratings.scholarship_grant'),
            'leadership_seminar' => $request->boolean('training_ratings.leadership_seminar'),
            'relevant_hours' => max(0, (float) $request->input('training_ratings.relevant_hours', 0)),
        ];
        $trainingScore = ($trainingRatings['scholarship_grant'] ? 3 : 0)
            + ($trainingRatings['leadership_seminar'] ? 2 : 0)
            + floor($trainingRatings['relevant_hours'] / 50);
        $trainingScore = min(5, $trainingScore);
        $experienceScore = 0;

        if ($request->experience_years) {
            foreach ($request->experience_years as $year => $data) {
                $experienceScore += floatval($data['credit'] ?? 0);
            }
        }

        if ($experienceScore > 15) {
            $experienceScore = 15;
        }

        $minimumRequirements = [
            'education_met' => $request->filled('education_met') ? $request->boolean('education_met') : null,
            'experience_met' => $request->filled('experience_met') ? $request->boolean('experience_met') : null,
            'eligibility_met' => $request->filled('eligibility_met') ? $request->boolean('eligibility_met') : null,
            'training_met' => $request->filled('training_met') ? $request->boolean('training_met') : null,
        ];
        $minimumRequirementScore = collect($minimumRequirements)->every(fn ($value) => $value === true)
            ? 70
            : 0;

        $total = $minimumRequirementScore
            + $educationScore
            + $trainingScore
            + $experienceScore;

        $rating->update([
            'evaluation_date' => $request->evaluation_date,
            'present_position' => $request->present_position,
            'college_department' => $request->college_department,
            'education_met' => $minimumRequirements['education_met'],
            'experience_met' => $minimumRequirements['experience_met'],
            'eligibility_met' => $minimumRequirements['eligibility_met'],
            'training_met' => $minimumRequirements['training_met'],
            'minimum_requirement_score' => $minimumRequirementScore,
            'education_score' => $educationScore,
            'education_ratings' => $educationRatings,
            'training_score' => $trainingScore,
            'training_ratings' => $trainingRatings,
            'experience_score' => $experienceScore,
            'experience_year_ratings' => $request->experience_years,
            'total_score' => $total,
            'remarks' => $request->remarks,
        ]);

        return response()->json([
            'success' => true,
            'minimum_requirement_score' => number_format($minimumRequirementScore, 2),
            'education_score' => number_format($educationScore, 2),
            'training_score' => number_format($trainingScore, 2),
            'experience_score' => number_format($experienceScore, 2),
            'total_score' => number_format($total, 2),
        ]);
    }

    public function applicantEvaluationPdf($id, $applicationId)
    {
        $this->authorizeEteAdmin();

        $ete = EteEvaluation::with(['job', 'evaluators.employee'])->findOrFail($id);
        $application = Application::where('jid', $ete->jid)->findOrFail($applicationId);
        $this->syncApplicationEvaluators($ete, $application);

        $ratings = EmployeeEvaluate::with('evaluator')
            ->where('ete_id', $ete->id)
            ->where('application_id', $application->id)
            ->orderBy('evaluator_id')
            ->get()
            ->map(function ($rating) {
                $rating->signature_data = null;

                if ($rating->evaluator && $rating->evaluator->esign) {
                    try {
                        $decrypted = Crypt::decrypt($rating->evaluator->esign);
                        $rating->signature_data = 'data:image/png;base64,' . base64_encode($decrypted);
                    } catch (\Throwable $exception) {
                        $rating->signature_data = null;
                    }
                }

                return $rating;
            });

        $years = $this->experienceYears($ete->experience_years);
        $fileName = 'ETE-' . ($application->app_number ?: $application->id) . '.pdf';

        return \PDF::loadView('ete.applicant-evaluation-pdf', compact(
            'ete',
            'application',
            'ratings',
            'years'
        ))->setPaper('letter', 'portrait')->stream($fileName);
    }

    public function selectedApplicantConsolidated($id)
    {
        $this->authorizeEteAdmin();

        $applicationId = request()->integer('application_id');

        $ete = EteEvaluation::with([
            'activeApplication',
            'employeeEvaluates.application',
            'employeeEvaluates.evaluator',
            'evaluators.employee',
        ])->findOrFail($id);

        if (!$applicationId) {
            $applicationId = $ete->active_application_id;
        }

        if (!$applicationId) {
            return response()->json([
                'success' => false,
                'message' => 'No applicant selected.',
            ]);
        }

        $ratings = $ete->employeeEvaluates
            ->where('application_id', $applicationId);

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
            'active_application_id' => $ete->active_application_id,
            'is_active' => (int) $ete->active_application_id === (int) $app->id,
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
        $this->authorizeEteAdmin();

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
        $this->authorizeEteAdmin();

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
