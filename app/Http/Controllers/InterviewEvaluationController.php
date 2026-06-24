<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Employee;
use App\Models\EteApplicantRating;
use App\Models\EteEvaluation;
use App\Models\InterviewApplicant;
use App\Models\InterviewEvaluation;
use App\Models\InterviewPanel;
use App\Models\InterviewRating;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InterviewEvaluationController extends Controller
{
    private array $interviewCriteria = [
        'voice_speech' => [
            'label' => 'Voice and speech',
            'prompt' => 'Is his/her voice inviting or pleasant? Can you easily hear what he/she says? Is his/her speech clear and distinct? Is his/her voice resonant and well-modulated?',
            'levels' => [
                '1 - 2' => 'Irritating or indistinct',
                '3 - 4' => 'Understandable but rather unpleasant',
                '5 - 6' => 'Neither conspicuously pleasant or unpleasant',
                '7 - 8' => 'Definitely pleasant and distinct',
                '9 - 10' => 'Exceptionally clear and pleasing',
            ],
        ],
        'appearance' => [
            'label' => 'Appearance',
            'prompt' => 'Does he/she look like a well set-up, healthy, energetic person? Is he/she well-groomed or is he/she unattractive in appearance?',
            'levels' => [
                '1 - 2' => 'Unpleasant or unsuitable',
                '3 - 4' => 'Creates a rather unfavorable impression',
                '5 - 6' => 'Suitable, acceptable',
                '7 - 8' => 'Creates a distinctly favorable impression',
                '9 - 10' => 'Impressive, commands admiration',
            ],
        ],
        'alertness' => [
            'label' => 'Alertness',
            'prompt' => 'Does he/she readily grasp the meaning of a question? Is he/she slow to comprehend?',
            'levels' => [
                '1 - 2' => 'Slow in grasping obvious questions; often misunderstands meaning of questions',
                '3 - 4' => 'Slow to understand subtle points; requires explanation',
                '5 - 6' => 'Nearly grasps ideas',
                '7 - 8' => 'Rather quick in grasping questions and new ideas',
                '9 - 10' => 'Exceptionally keen and quick to understand',
            ],
        ],
        'present_ideas' => [
            'label' => 'Ability to present ideas',
            'prompt' => 'Does he/she speak logically and convincingly or does he/she tend to be vague, confused or illogical?',
            'levels' => [
                '1 - 2' => 'Confused and illogical',
                '3 - 4' => 'Tends to present ideas in a haphazard manner',
                '5 - 6' => 'Usually gets his/her ideas across well',
                '7 - 8' => 'Shows superior ability to express him/herself',
                '9 - 10' => 'Unusually logical, clear and convincing',
            ],
        ],
        'judgment' => [
            'label' => 'Judgment',
            'prompt' => 'Does he/she impress you as a person whose judgment would be dependable even under stress? Or is he/she hasty, erratic, biased, swayed by his/her feelings?',
            'levels' => [
                '1 - 2' => 'Confused and illogical',
                '3 - 4' => 'Tends to present ideas in a haphazard manner',
                '5 - 6' => 'Usually gets his/her ideas across well',
                '7 - 8' => 'Shows superior ability to express him/herself',
                '9 - 10' => 'Unusually logical, clear and convincing',
            ],
        ],
        'emotional_stability' => [
            'label' => 'Emotional stability',
            'prompt' => 'Is he/she emotionally mature? Is he/she touchy, sensitive to criticism, easily upset? Is he/she irritated or impatient when things go wrong? Or does he/she keep an even keel?',
            'levels' => [
                '1 - 2' => 'Over sensitive; easily disconcerted',
                '3 - 4' => 'Occasionally impatient or irritated',
                '5 - 6' => 'Well-poised most of the time',
                '7 - 8' => 'Superior self-command',
                '9 - 10' => 'Exceptional poise, calmness, and good humor under stress',
            ],
        ],
        'self_confidence' => [
            'label' => 'Self-confidence',
            'prompt' => 'Does he/she seem to be uncertain of him/herself, hesitant, lacking in assurance, easily bluffed? Or is he/she wholesomely self-confident and assured?',
            'levels' => [
                '1 - 2' => 'Timid, hesitant, easily influenced',
                '3 - 4' => 'Appears to be over self-conscious',
                '5 - 6' => 'Moderately confident of him/herself',
                '7 - 8' => 'Wholesomely self-confident',
                '9 - 10' => 'Shows superior self-assurance',
            ],
        ],
    ];

    private array $potentialCriteria = [
        'Human Relations' => [
            'adjust_personalities' => 'Ability to adjust to personalities, rank, and informal groups',
            'internalize_changes' => 'Internalizes work changes with ease and vigor',
            'respond_superiors' => 'Responds to requests, demands, and expectations',
            'appraise_work_problems' => 'Appraises work problems, causes, and corrective steps',
            'maintain_point_of_view' => 'Maintains individual point of view despite behavior differences',
            'peer_respect' => 'Has respect and acceptance of peers',
            'resolve_peer_conflict' => 'Helps peers resolve conflict',
            'public_cordiality' => 'Cordial and respectful with clientele/public',
            'client_assistance' => 'Shows enthusiasm in advising and assisting clients',
        ],
        'Leadership' => [
            'encourage_participation' => 'Encourages participation in problem-solving and decision-making',
            'influence_others' => 'Influences thinking, attitude, and behavior of peers',
            'external_group_leadership' => 'Leads ad hoc external groups to complete tasks/projects',
            'working_group_responsibility' => 'Assumes responsibility as leader/chair of a working group',
        ],
        'Personal Qualifications and Attributes' => [
            'critical_standards' => 'Intellectually critical of existing standards, systems, and policies',
            'initiative_programs' => 'Takes initiative to develop beneficial programs, systems, and procedures',
            'stress_tolerance' => 'Has high tolerance for tension, change, and conflict',
            'control_emotions' => 'Controls anger and negative emotions',
            'accept_criticism' => 'Accepts criticism objectively',
            'recommend_solutions' => 'Recommends solutions when help is sought',
            'quick_decisions' => 'Acts quickly and makes the best possible immediate decision',
        ],
    ];

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->guard('web')->check(), 403);
    }

    private function authorizeRankingAdmin(): void
    {
        $user = auth()->guard('web')->user();
        if (!$user || !in_array($user->role, ['Administrator', 'HR Administrator'], true)) {
            redirect()->route('dashboard')->with('error1', 'Only administrators can view rankings.')->send();
            exit;
        }
    }

    private function guard()
    {
        return auth()->guard('web')->check() ? 'web' : (auth()->guard('employee')->check() ? 'employee' : null);
    }

    private function currentPanelEmployeeId(): ?int
    {
        if (auth()->guard('employee')->check()) {
            return (int) auth()->guard('employee')->user()->id;
        }

        if (!auth()->guard('web')->check()) {
            return null;
        }

        $user = auth()->guard('web')->user();

        if (!empty($user->emp_ID)) {
            $employeeId = Employee::where('emp_ID', $user->emp_ID)->value('id');
            if ($employeeId) {
                return (int) $employeeId;
            }
        }

        if (!empty($user->username)) {
            $employeeId = Employee::where('username', $user->username)
                ->orWhere('org_email', $user->username)
                ->value('id');

            if ($employeeId) {
                return (int) $employeeId;
            }
        }

        if (!empty($user->fname) && !empty($user->lname)) {
            $employeeId = Employee::whereRaw('LOWER(fname) = ?', [strtolower($user->fname)])
                ->whereRaw('LOWER(lname) = ?', [strtolower($user->lname)])
                ->value('id');

            if ($employeeId) {
                return (int) $employeeId;
            }
        }

        return null;
    }

    private function employeeName($employee): string
    {
        return trim(collect([
            $employee->prefix ?? null,
            $employee->fname ?? null,
            $employee->mname ?? null,
            $employee->lname ?? null,
            $employee->suffix ?? null,
        ])->filter()->implode(' '));
    }

    private function applicantName($application): string
    {
        return trim(collect([
            $application->first_name ?? null,
            $application->middle_name ?? null,
            $application->last_name ?? null,
        ])->filter()->implode(' '));
    }

    private function eligibleApplicants(InterviewEvaluation $interview)
    {
        return Application::where('jid', $interview->jid)
            ->where('status', 2)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    private function syncApplicantRows(InterviewEvaluation $interview): void
    {
        $this->eligibleApplicants($interview)->each(function ($application) use ($interview) {
            InterviewApplicant::firstOrCreate([
                'interview_id' => $interview->id,
                'application_id' => $application->id,
            ]);
        });
    }

    private function createRatingRows(InterviewEvaluation $interview, int $applicationId): void
    {
        $interview->loadMissing('panels');

        $interview->panels->each(function ($panel) use ($interview, $applicationId) {
            InterviewRating::firstOrCreate([
                'interview_id' => $interview->id,
                'application_id' => $applicationId,
                'panel_employee_id' => $panel->emp_id,
            ]);
        });
    }

    private function isCurrentCastForPanel(InterviewEvaluation $interview, int $applicationId, int $employeeId): bool
    {
        return (int) $interview->active_application_id === (int) $applicationId
            && $interview->panels()->where('emp_id', $employeeId)->exists()
            && $interview->applicants()
                ->where('application_id', $applicationId)
                ->where('is_cast', true)
                ->exists();
    }

    private function sumScores(array $scores, array $allowedKeys, int $min, int $max): float
    {
        return collect($allowedKeys)->sum(function ($key) use ($scores, $min, $max) {
            $score = (int) ($scores[$key] ?? 0);
            return max($min, min($max, $score));
        });
    }

    private function sumProvidedScores(array $scores, array $allowedKeys, int $min, int $max): float
    {
        return collect($allowedKeys)->sum(function ($key) use ($scores, $min, $max) {
            if (!array_key_exists($key, $scores) || $scores[$key] === null || $scores[$key] === '') {
                return 0;
            }

            $score = (int) $scores[$key];
            return max($min, min($max, $score));
        });
    }

    private function maxInterviewScore(): int
    {
        return count($this->interviewCriteria) * 10;
    }

    private function maxPotentialScore(): int
    {
        return collect($this->potentialCriteria)->flatten()->count() * 5;
    }

    private function weightedScore(float $score, int $maxScore, float $weight): float
    {
        if ($maxScore <= 0) {
            return 0;
        }

        return ($score / $maxScore) * $weight;
    }

    private function rankingRows(InterviewEvaluation $interview)
    {
        $panelCount = max(1, $interview->panels->count());
        $ratingsByApplication = $interview->ratings->groupBy('application_id');
        $eteRatings = EteApplicantRating::where('ete_id', $interview->ete_id)
            ->whereIn('application_id', $interview->applicants->pluck('application_id'))
            ->get()
            ->keyBy('application_id');

        return $interview->applicants
            ->filter(fn ($row) => $row->application)
            ->map(function ($row) use ($interview, $ratingsByApplication, $panelCount, $eteRatings) {
                $ratings = $ratingsByApplication->get($row->application_id, collect());
                $startedRatings = $ratings->filter(function ($rating) {
                    return (float) $rating->total_score > 0
                        || !empty($rating->interview_scores)
                        || !empty($rating->potential_scores)
                        || !empty($rating->remarks);
                });
                $startedCount = $startedRatings->count();
                $submittedCount = $ratings->whereNotNull('submitted_at')->count();
                $interviewTotal = $startedCount ? (float) $startedRatings->avg('interview_total') : 0;
                $potentialTotal = $startedCount ? (float) $startedRatings->avg('potential_total') : 0;
                $totalScore = $startedCount ? (float) $startedRatings->avg('total_score') : 0;
                $eteTotal = (float) optional($eteRatings->get($row->application_id))->total_score;
                $qualificationScore = $eteTotal * 0.5;
                $interviewScore = $this->weightedScore($interviewTotal, $this->maxInterviewScore(), 25);
                $potentialScore = $this->weightedScore($potentialTotal, $this->maxPotentialScore(), 25);
                $finalScore = $qualificationScore + $potentialScore + $interviewScore;

                return [
                    'application_id' => $row->application_id,
                    'app_number' => $row->application->app_number,
                    'name' => $this->applicantName($row->application),
                    'qualification_score_raw' => $qualificationScore,
                    'potential_score_raw' => $potentialScore,
                    'interview_score_raw' => $interviewScore,
                    'final_score_raw' => $finalScore,
                    'raw_total_score' => number_format($totalScore, 2),
                    'raw_total_score_raw' => $totalScore,
                    'total_raw' => $finalScore,
                    'qualification_score' => number_format($qualificationScore, 2),
                    'interview_score' => number_format($interviewScore, 2),
                    'potential_score' => number_format($potentialScore, 2),
                    'raw_interview_score' => number_format($interviewTotal, 2),
                    'raw_potential_score' => number_format($potentialTotal, 2),
                    'weighted_interview_score' => number_format($interviewScore, 2),
                    'weighted_potential_score' => number_format($potentialScore, 2),
                    'total_score' => number_format($finalScore, 2),
                    'final_score' => number_format($finalScore, 2),
                    'started_count' => $startedCount,
                    'submitted_count' => $submittedCount,
                    'panel_count' => $panelCount,
                    'completed' => $submittedCount >= $panelCount,
                    'is_active' => (int) $interview->active_application_id === (int) $row->application_id && (bool) $row->is_cast,
                    'remarks' => '',
                ];
            })
            ->sortByDesc('final_score_raw')
            ->values()
            ->map(function ($item, $index) {
                $item['rank'] = $index + 1;
                return $item;
            });
    }

    public function index()
    {
        $this->authorizeAdmin();

        $interviews = InterviewEvaluation::with([
            'eteEvaluation.job',
            'job',
            'panels.employee',
            'activeApplication',
            'applicants.application',
            'ratings',
        ])->latest()->get();

        $etes = EteEvaluation::with(['job', 'office'])->latest()->get();
        $employees = Employee::orderBy('lname')->orderBy('fname')->get();

        return view('interview.index', compact('interviews', 'etes', 'employees'));
    }

    public function assignments()
    {
        $employeeId = $this->currentPanelEmployeeId();
        if (!$employeeId) {
            $ratings = collect();
            return view('interview.assignments', compact('ratings'));
        }

        $activeInterviews = InterviewEvaluation::with(['job', 'eteEvaluation.office', 'activeApplication'])
            ->whereNotNull('active_application_id')
            ->whereHas('panels', fn ($query) => $query->where('emp_id', $employeeId))
            ->whereHas('applicants', function ($query) {
                $query->where('is_cast', true)
                    ->whereColumn('interview_applicants.application_id', 'interview_evaluations.active_application_id');
            })
            ->latest()
            ->get();

        $ratings = $activeInterviews->map(function ($interview) use ($employeeId) {
            return InterviewRating::firstOrCreate([
                'interview_id' => $interview->id,
                'application_id' => $interview->active_application_id,
                'panel_employee_id' => $employeeId,
            ])->load(['interview.job', 'interview.eteEvaluation.office', 'application']);
        });

        if ($ratings->isNotEmpty()) {
            $rating = $ratings->first();
            $interview = $rating->interview->load(['job', 'eteEvaluation.office', 'panels.employee', 'activeApplication']);
            $application = $rating->application;
            $panelEmployee = Employee::findOrFail($employeeId);

            return view('interview.rate', [
                'interview' => $interview,
                'application' => $application,
                'rating' => $rating,
                'panelEmployee' => $panelEmployee,
                'interviewCriteria' => $this->interviewCriteria,
                'potentialCriteria' => $this->potentialCriteria,
            ]);
        }

        return view('interview.assignments', compact('ratings'));
    }

    public function assignmentStatus()
    {
        $employeeId = $this->currentPanelEmployeeId();
        if (!$employeeId) {
            return response()->json([
                'count' => 0,
                'url' => route('interviewAssignments'),
            ]);
        }

        $count = InterviewEvaluation::whereNotNull('active_application_id')
            ->whereHas('panels', fn ($query) => $query->where('emp_id', $employeeId))
            ->whereHas('applicants', function ($query) {
                $query->where('is_cast', true)
                    ->whereColumn('interview_applicants.application_id', 'interview_evaluations.active_application_id');
            })
            ->count();
        $activeInterview = InterviewEvaluation::whereNotNull('active_application_id')
            ->whereHas('panels', fn ($query) => $query->where('emp_id', $employeeId))
            ->whereHas('applicants', function ($query) {
                $query->where('is_cast', true)
                    ->whereColumn('interview_applicants.application_id', 'interview_evaluations.active_application_id');
            })
            ->latest()
            ->first();

        return response()->json([
            'count' => $count,
            'url' => route('interviewAssignments'),
            'active_form_url' => $activeInterview
                ? route('interviewRatingForm', [$activeInterview->id, $activeInterview->active_application_id])
                : null,
            'active_key' => $activeInterview
                ? $activeInterview->id . ':' . $activeInterview->active_application_id
                : '',
        ]);
    }

    public function ratingStatus($id, $applicationId)
    {
        $employeeId = $this->currentPanelEmployeeId();
        $statusUrl = auth()->guard('web')->check()
            ? route('interviewEvaluationShow', $id)
            : route('interviewAssignments');

        if (auth()->guard('web')->check()) {
            $employeeId = request()->integer('panel_id') ?: $employeeId;
        }

        if (!$employeeId) {
            return response()->json([
                'active' => false,
                'url' => $statusUrl,
            ]);
        }

        $active = InterviewEvaluation::where('id', $id)
            ->where('active_application_id', $applicationId)
            ->whereHas('panels', fn ($query) => $query->where('emp_id', $employeeId))
            ->whereHas('applicants', function ($query) use ($applicationId) {
                $query->where('application_id', $applicationId)
                    ->where('is_cast', true);
            })
            ->exists();
        $activeInterview = InterviewEvaluation::whereNotNull('active_application_id')
            ->whereHas('panels', fn ($query) => $query->where('emp_id', $employeeId))
            ->whereHas('applicants', function ($query) {
                $query->where('is_cast', true)
                    ->whereColumn('interview_applicants.application_id', 'interview_evaluations.active_application_id');
            })
            ->latest()
            ->first();

        return response()->json([
            'active' => $active,
            'url' => $statusUrl,
            'active_key' => $activeInterview
                ? $activeInterview->id . ':' . $activeInterview->active_application_id
                : '',
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $request->validate([
            'ete_id' => 'required|exists:ete_evaluations,id',
            'interview_date' => 'nullable|date',
            'panels' => 'required|array|min:1',
            'panels.*' => 'exists:employees,id',
        ]);

        DB::transaction(function () use ($request) {
            $ete = EteEvaluation::findOrFail($request->ete_id);
            $qualifiedApplicants = Application::where('jid', $ete->jid)->where('status', 2)->count();

            if ($qualifiedApplicants === 0) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'ete_id' => 'Cannot create interview assessment. No applicants are currently Qualified / Ready for Interview for this ETE position.',
                ]);
            }

            $interview = InterviewEvaluation::create([
                'ete_id' => $ete->id,
                'jid' => $ete->jid,
                'interview_date' => $request->interview_date ? Carbon::parse($request->interview_date) : now(),
            ]);

            collect($request->panels)->unique()->each(fn ($empId) => InterviewPanel::create([
                'interview_id' => $interview->id,
                'emp_id' => $empId,
            ]));

            $this->syncApplicantRows($interview);
        });

        return back()->with('success', 'Interview evaluation created successfully.');
    }

    public function show($id)
    {
        $this->authorizeAdmin();

        $interview = InterviewEvaluation::with(['eteEvaluation.office', 'job', 'panels.employee', 'activeApplication'])->findOrFail($id);
        $this->syncApplicantRows($interview);
        $interview->load(['applicants.application', 'ratings.panelEmployee']);
        $eligibleApplicants = $this->eligibleApplicants($interview);
        $ratingsByApplication = $interview->ratings->groupBy('application_id');

        return view('interview.show', compact('interview', 'eligibleApplicants', 'ratingsByApplication'));
    }

    public function cast($id, $applicationId)
    {
        $this->authorizeAdmin();

        DB::transaction(function () use ($id, $applicationId) {
            $interview = InterviewEvaluation::with('panels')->findOrFail($id);
            $application = Application::where('jid', $interview->jid)->where('status', 2)->findOrFail($applicationId);

            InterviewApplicant::where('interview_id', $interview->id)
                ->where('is_cast', true)
                ->update(['is_cast' => false, 'uncasted_at' => now()]);

            InterviewRating::where('interview_id', $interview->id)
                ->where('application_id', '!=', $application->id)
                ->whereNull('submitted_at')
                ->delete();

            InterviewApplicant::updateOrCreate(
                ['interview_id' => $interview->id, 'application_id' => $application->id],
                ['is_cast' => true, 'casted_at' => now(), 'uncasted_at' => null]
            );

            $interview->update(['active_application_id' => $application->id]);
            $this->createRatingRows($interview, $application->id);
        });

        return back()->with('success', 'Candidate cast to interview panel.');
    }

    public function uncast($id, $applicationId)
    {
        $this->authorizeAdmin();

        DB::transaction(function () use ($id, $applicationId) {
            $interview = InterviewEvaluation::findOrFail($id);
            InterviewApplicant::where('interview_id', $interview->id)
                ->where('application_id', $applicationId)
                ->update(['is_cast' => false, 'uncasted_at' => now()]);

            if ((int) $interview->active_application_id === (int) $applicationId) {
                $interview->update(['active_application_id' => null]);
            }

            InterviewRating::where('interview_id', $interview->id)
                ->where('application_id', $applicationId)
                ->whereNull('submitted_at')
                ->delete();
        });

        return back()->with('success', 'Candidate uncast successfully.');
    }

    public function rate($id, $applicationId = null)
    {
        $guard = $this->guard();
        abort_unless($guard, 403);

        $interview = InterviewEvaluation::with(['job', 'eteEvaluation.office', 'panels.employee', 'activeApplication'])->findOrFail($id);
        $applicationId = $applicationId ?: $interview->active_application_id;
        abort_unless($applicationId, 404, 'No cast candidate is active for this interview.');

        $application = Application::where('jid', $interview->jid)->findOrFail($applicationId);
        $employeeId = $this->currentPanelEmployeeId();

        if ($guard === 'web') {
            $employeeId = request()->integer('panel_id') ?: $employeeId ?: optional($interview->panels->first())->emp_id;
        }

        abort_unless($employeeId && $interview->panels->contains('emp_id', $employeeId), 403, 'You are not part of this interview panel.');

        if (!$this->isCurrentCastForPanel($interview, (int) $application->id, (int) $employeeId)) {
            $redirectRoute = $guard === 'web' ? 'interviewEvaluationShow' : 'interviewAssignments';

            return redirect()->route($redirectRoute, $guard === 'web' ? [$interview->id] : [])
                ->with('error', 'This applicant is not currently cast for your interview panel.');
        }

        $rating = InterviewRating::firstOrCreate([
            'interview_id' => $interview->id,
            'application_id' => $application->id,
            'panel_employee_id' => $employeeId,
        ]);

        $panelEmployee = Employee::findOrFail($employeeId);

        return view('interview.rate', [
            'interview' => $interview,
            'application' => $application,
            'rating' => $rating,
            'panelEmployee' => $panelEmployee,
            'interviewCriteria' => $this->interviewCriteria,
            'potentialCriteria' => $this->potentialCriteria,
        ]);
    }

    public function saveRating(Request $request, $id, $applicationId)
    {
        $guard = $this->guard();
        abort_unless($guard, 403);

        $interview = InterviewEvaluation::with('panels')->findOrFail($id);
        $application = Application::where('jid', $interview->jid)->findOrFail($applicationId);
        $employeeId = $this->currentPanelEmployeeId();
        if ($guard === 'web') {
            $employeeId = $request->integer('panel_employee_id') ?: $employeeId;
        }
        abort_unless($employeeId && $interview->panels->contains('emp_id', $employeeId), 403);

        if (!$this->isCurrentCastForPanel($interview, (int) $application->id, (int) $employeeId)) {
            $redirectUrl = $guard === 'web'
                ? route('interviewEvaluationShow', $interview->id)
                : route('interviewAssignments');

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'active' => false,
                    'redirect' => $redirectUrl,
                    'message' => 'This applicant is no longer cast for your interview panel.',
                ], 409);
            }

            return redirect()->to($redirectUrl)
                ->with('error', 'This applicant is no longer cast for your interview panel.');
        }

        $interviewKeys = array_keys($this->interviewCriteria);
        $potentialKeys = collect($this->potentialCriteria)
            ->flatMap(fn ($items) => array_keys($items))
            ->values()
            ->all();

        $isAutosave = $request->boolean('autosave');
        $rules = [
            'panel_employee_id' => 'nullable|exists:employees,id',
            'remarks' => 'nullable|string|max:2000',
            'autosave' => 'nullable|boolean',
            'interview_scores' => $isAutosave ? 'nullable|array' : 'required|array',
            'potential_scores' => $isAutosave ? 'nullable|array' : 'required|array',
        ];

        foreach ($interviewKeys as $key) {
            $rules["interview_scores.$key"] = ($isAutosave ? 'nullable' : 'required') . '|integer|min:1|max:10';
        }

        foreach ($potentialKeys as $key) {
            $rules["potential_scores.$key"] = ($isAutosave ? 'nullable' : 'required') . '|integer|min:1|max:5';
        }

        $validated = $request->validate($rules);
        $rating = null;
        $isComplete = false;

        DB::transaction(function () use (
            $interview,
            $application,
            $employeeId,
            $validated,
            $interviewKeys,
            $potentialKeys,
            $request,
            &$rating,
            &$isComplete
        ) {
            $keys = [
                'interview_id' => $interview->id,
                'application_id' => $application->id,
                'panel_employee_id' => $employeeId,
            ];
            $existingRating = InterviewRating::where($keys)->lockForUpdate()->first();
            $interviewScores = array_replace(
                $existingRating->interview_scores ?? [],
                $validated['interview_scores'] ?? []
            );
            $potentialScores = array_replace(
                $existingRating->potential_scores ?? [],
                $validated['potential_scores'] ?? []
            );
            $isComplete = collect($interviewKeys)->every(fn ($key) => isset($interviewScores[$key]) && $interviewScores[$key] !== '')
                && collect($potentialKeys)->every(fn ($key) => isset($potentialScores[$key]) && $potentialScores[$key] !== '');
            $interviewTotal = $isComplete
                ? $this->sumScores($interviewScores, $interviewKeys, 1, 10)
                : $this->sumProvidedScores($interviewScores, $interviewKeys, 1, 10);
            $potentialTotal = $isComplete
                ? $this->sumScores($potentialScores, $potentialKeys, 1, 5)
                : $this->sumProvidedScores($potentialScores, $potentialKeys, 1, 5);

            $rating = $existingRating ?: new InterviewRating($keys);
            $rating->fill([
                'interview_scores' => $interviewScores,
                'potential_scores' => $potentialScores,
                'interview_total' => $interviewTotal,
                'potential_total' => $potentialTotal,
                'total_score' => $interviewTotal + $potentialTotal,
                'remarks' => $request->remarks,
                'submitted_at' => $isComplete ? now() : null,
            ]);
            $rating->save();
        }, 3);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'complete' => $isComplete,
                'interview_total' => number_format($rating->interview_total, 2),
                'potential_total' => number_format($rating->potential_total, 2),
                'total_score' => number_format($rating->total_score, 2),
                'saved_at' => now()->format('M d, Y h:i A'),
                'message' => $isComplete ? 'Saved' : 'Draft saved',
            ]);
        }

        return back()->with('success', 'Interview assessment saved.');
    }

    public function consolidatedScreen($id)
    {
        $this->authorizeRankingAdmin();
        $interview = InterviewEvaluation::with(['job', 'eteEvaluation.office'])->findOrFail($id);
        $this->syncApplicantRows($interview);

        return view('interview.consolidated-screen', compact('interview'));
    }

    public function consolidatedData($id)
    {
        $this->authorizeRankingAdmin();

        $interview = InterviewEvaluation::with([
            'job',
            'panels',
            'applicants.application',
            'ratings.application',
        ])->findOrFail($id);

        $data = $this->rankingRows($interview);

        return response()->json(['success' => true, 'data' => $data])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function summaryRatingPdf($id)
    {
        $this->authorizeRankingAdmin();

        $interview = InterviewEvaluation::with([
            'job',
            'eteEvaluation.office',
            'panels',
            'applicants.application',
            'ratings.application',
        ])->findOrFail($id);
        $this->syncApplicantRows($interview);
        $interview->load(['applicants.application', 'ratings.application']);

        $rows = $this->rankingRows($interview)->take(5)->values();
        $fileName = 'summary-rating-applicants-' . $interview->id . '.pdf';
        $longBondPaper = [0, 0, 612, 936];

        return \PDF::loadView('interview.summary-rating-pdf', compact('interview', 'rows'))
            ->setPaper($longBondPaper, 'portrait')
            ->stream($fileName);
    }

    public function destroy($id)
    {
        $this->authorizeAdmin();
        InterviewEvaluation::findOrFail($id)->delete();

        return back()->with('success', 'Interview evaluation deleted.');
    }
}
