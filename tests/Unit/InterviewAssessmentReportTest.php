<?php

namespace Tests\Unit;

use App\Models\Application;
use App\Models\Employee;
use App\Models\EteApplicantRating;
use App\Models\InterviewEvaluation;
use App\Models\InterviewRating;
use App\Models\JobHiring;
use App\Services\InterviewAssessmentReport;
use Carbon\Carbon;
use Smalot\PdfParser\Parser;
use Tests\TestCase;

class InterviewAssessmentReportTest extends TestCase
{
    public function test_it_creates_three_official_form_pages_for_each_panel_rating(): void
    {
        $interview = new InterviewEvaluation([
            'interview_date' => Carbon::parse('2026-08-27 09:00:00'),
        ]);
        $interview->setRelation('job', new JobHiring(['title' => 'University Extension Specialist III']));

        $application = new Application([
            'app_number' => 'APP-TEST-001',
            'first_name' => 'Jane',
            'middle_name' => 'Quinn',
            'last_name' => 'Doe',
        ]);

        $profile = new EteApplicantRating([
            'present_position' => 'Extension Specialist II',
            'college_department' => 'Extension Unit',
        ]);

        $ratings = collect([
            $this->ratingFor('Alex', 'A', 'Panel'),
            $this->ratingFor('Blair', 'B', 'Member'),
        ]);

        $contents = (new InterviewAssessmentReport())->generate($interview, $application, $ratings, $profile);
        $document = (new Parser())->parseContent($contents);

        $this->assertStringStartsWith('%PDF-', $contents);
        $this->assertCount(6, $document->getPages());
        $this->assertStringContainsString('JANE QUINN DOE', $document->getText());
        $this->assertStringContainsString('ALEX A PANEL', $document->getText());
        $this->assertStringContainsString('BLAIR B MEMBER', $document->getText());
    }

    private function ratingFor(string $firstName, string $middleName, string $lastName): InterviewRating
    {
        $rating = new InterviewRating([
            'panel_employee_id' => 1,
            'interview_scores' => [
                'voice_speech' => 10,
                'appearance' => 9,
                'alertness' => 8,
                'present_ideas' => 7,
                'judgment' => 6,
                'emotional_stability' => 5,
                'self_confidence' => 4,
            ],
            'potential_scores' => [
                'adjust_personalities' => 5,
                'internalize_changes' => 4,
                'respond_superiors' => 3,
                'appraise_work_problems' => 2,
                'maintain_point_of_view' => 1,
                'peer_respect' => 5,
                'resolve_peer_conflict' => 4,
                'public_cordiality' => 3,
                'client_assistance' => 2,
                'encourage_participation' => 1,
                'influence_others' => 5,
                'external_group_leadership' => 4,
                'working_group_responsibility' => 3,
                'critical_standards' => 2,
                'initiative_programs' => 1,
                'stress_tolerance' => 5,
                'control_emotions' => 4,
                'accept_criticism' => 3,
                'recommend_solutions' => 2,
                'quick_decisions' => 1,
            ],
            'interview_total' => 49,
            'potential_total' => 61,
        ]);
        $rating->setRelation('panelEmployee', new Employee([
            'fname' => $firstName,
            'mname' => $middleName,
            'lname' => $lastName,
        ]));

        return $rating;
    }
}
