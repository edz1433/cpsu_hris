<?php

namespace App\Services;

use App\Models\Application;
use App\Models\EteApplicantRating;
use App\Models\InterviewEvaluation;
use App\Models\InterviewRating;
use Illuminate\Support\Collection;
use RuntimeException;
use setasign\Fpdi\Fpdi;

class InterviewAssessmentReport
{
    private const PAGE_WIDTH = 215.9;

    private const PAGE_HEIGHT = 330.2;

    private const INTERVIEW_SCORE_Y = [
        'voice_speech' => 265.0,
        'appearance' => 269.25,
        'alertness' => 273.5,
        'present_ideas' => 277.75,
        'judgment' => 282.0,
        'emotional_stability' => 286.25,
        'self_confidence' => 290.5,
    ];

    private const POTENTIAL_PAGE_ONE_Y = [
        'adjust_personalities' => 184.5,
        'internalize_changes' => 190.8,
        'respond_superiors' => 201.25,
        'appraise_work_problems' => 209.55,
        'maintain_point_of_view' => 217.85,
        'peer_respect' => 228.35,
        'resolve_peer_conflict' => 232.6,
        'public_cordiality' => 243.05,
        'client_assistance' => 251.35,
        'encourage_participation' => 267.95,
        'influence_others' => 279.9,
        'external_group_leadership' => 291.45,
        'working_group_responsibility' => 302.7,
    ];

    private const POTENTIAL_PAGE_TWO_Y = [
        'critical_standards' => 44.95,
        'initiative_programs' => 55.25,
        'stress_tolerance' => 69.8,
        'control_emotions' => 78.1,
        'accept_criticism' => 86.4,
        'recommend_solutions' => 98.9,
        'quick_decisions' => 107.2,
    ];

    private const POTENTIAL_SCORE_X = [
        5 => 162.65,
        4 => 168.6,
        3 => 174.8,
        2 => 180.95,
        1 => 186.95,
    ];

    /**
     * Generate one official three-page assessment packet for every completed
     * panel rating, combined into a single PDF.
     */
    public function generate(
        InterviewEvaluation $interview,
        Application $application,
        Collection $ratings,
        ?EteApplicantRating $applicantProfile = null
    ): string {
        $interviewTemplatePath = public_path('INTERVIEWASSESSMENT FORM 6 (1) 3 2.pdf');
        $potentialTemplatePath = public_path('POTENTIAL ASSESSMENT FORM 1 (1) 3 2.pdf');
        $headerPath = public_path('Uploads/header-potinv.png');

        foreach ([$interviewTemplatePath, $potentialTemplatePath, $headerPath] as $path) {
            if (!is_file($path)) {
                throw new RuntimeException('Assessment report asset is missing: '.basename($path));
            }
        }

        $pdf = new InterviewAssessmentPdf('P', 'mm', [self::PAGE_WIDTH, self::PAGE_HEIGHT]);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetTitle('Interview and Potential Assessment Report');
        $pdf->SetAuthor('Central Philippines State University');

        $pdf->setSourceFile($interviewTemplatePath);
        $interviewTemplate = $pdf->importPage(1);

        $pdf->setSourceFile($potentialTemplatePath);
        $potentialPageOne = $pdf->importPage(1);
        $potentialPageTwo = $pdf->importPage(2);

        foreach ($ratings as $rating) {
            $this->addTemplatePage($pdf, $interviewTemplate);
            $this->replaceHeader($pdf, $headerPath);
            $this->fillApplicantDetails($pdf, $interview, $application, $applicantProfile, 46.0);
            $this->fillInterviewAssessment($pdf, $rating);

            $this->addTemplatePage($pdf, $potentialPageOne);
            $this->replaceHeader($pdf, $headerPath);
            $this->fillApplicantDetails($pdf, $interview, $application, $applicantProfile, 46.8);
            $this->fillPotentialSelections($pdf, $rating, self::POTENTIAL_PAGE_ONE_Y);

            $this->addTemplatePage($pdf, $potentialPageTwo);
            $this->replaceHeader($pdf, $headerPath);
            $this->fillPotentialSelections($pdf, $rating, self::POTENTIAL_PAGE_TWO_Y);
            $this->writeCentered($pdf, 171.8, 117.15, 11, (string) round((float) $rating->potential_total), 7, 'B');
            $this->writeCentered($pdf, 128.4, 143.9, 60.8, $this->panelName($rating), 8, 'B');
        }

        return $pdf->Output('S');
    }

    private function addTemplatePage(InterviewAssessmentPdf $pdf, string $template): void
    {
        $pdf->AddPage('P', [self::PAGE_WIDTH, self::PAGE_HEIGHT]);
        $pdf->useTemplate($template, 0, 0, self::PAGE_WIDTH, self::PAGE_HEIGHT);
    }

    private function replaceHeader(InterviewAssessmentPdf $pdf, string $headerPath): void
    {
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect(0, 8.5, self::PAGE_WIDTH, 20.7, 'F');
        $pdf->Image($headerPath, 0, 9, self::PAGE_WIDTH, 0, 'PNG');
    }

    private function fillApplicantDetails(
        InterviewAssessmentPdf $pdf,
        InterviewEvaluation $interview,
        Application $application,
        ?EteApplicantRating $applicantProfile,
        float $nameBaseline
    ): void {
        $name = trim(collect([
            $application->first_name,
            $application->middle_name,
            $application->last_name,
        ])->filter()->implode(' '));
        $date = optional($interview->interview_date)->format('m/d/Y') ?: now()->format('m/d/Y');
        $position = $interview->job->title ?? $application->position ?? '';
        $presentPosition = $applicantProfile->present_position ?? '';
        $collegeDepartment = $applicantProfile->college_department
            ?? optional(optional($interview->eteEvaluation)->office)->office_name
            ?? '';

        $this->writeFitted($pdf, 37.0, $nameBaseline, 89.0, strtoupper($name), 8);
        $this->writeFitted($pdf, 149.0, $nameBaseline, 41.0, $date, 8);
        $this->writeFitted($pdf, 73.0, $nameBaseline + 4.05, 117.0, strtoupper($position), 8);
        $this->writeFitted($pdf, 53.5, $nameBaseline + 8.1, 136.5, strtoupper($presentPosition), 8);
        $this->writeFitted($pdf, 86.5, $nameBaseline + 12.15, 103.5, strtoupper($collegeDepartment), 8);
    }

    private function fillInterviewAssessment(InterviewAssessmentPdf $pdf, InterviewRating $rating): void
    {
        $scores = $rating->interview_scores ?? [];

        foreach (self::INTERVIEW_SCORE_Y as $key => $baseline) {
            if (!isset($scores[$key]) || $scores[$key] === '') {
                continue;
            }

            $this->writeCentered($pdf, 81.5, $baseline, 18.5, (string) (int) $scores[$key], 8, 'B');
        }

        $this->writeCentered($pdf, 81.5, 296.0, 18.5, (string) round((float) $rating->interview_total), 8, 'B');
        $this->writeCentered($pdf, 130.0, 273.0, 70.0, $this->panelName($rating), 8, 'B');
    }

    private function fillPotentialSelections(InterviewAssessmentPdf $pdf, InterviewRating $rating, array $coordinates): void
    {
        $scores = $rating->potential_scores ?? [];
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.65);

        foreach ($coordinates as $key => $y) {
            $score = isset($scores[$key]) ? (int) $scores[$key] : 0;
            if (!isset(self::POTENTIAL_SCORE_X[$score])) {
                continue;
            }

            $pdf->Circle(self::POTENTIAL_SCORE_X[$score], $y, 2.25);
        }

        $pdf->SetLineWidth(0.2);
    }

    private function panelName(InterviewRating $rating): string
    {
        $panel = $rating->panelEmployee;
        if (!$panel) {
            return 'PANEL MEMBER #'.$rating->panel_employee_id;
        }

        return strtoupper(trim(collect([
            $panel->fname,
            $panel->mname,
            $panel->lname,
            $panel->suffix,
        ])->filter()->implode(' ')));
    }

    private function writeFitted(
        InterviewAssessmentPdf $pdf,
        float $x,
        float $baseline,
        float $maxWidth,
        string $text,
        float $fontSize,
        string $style = ''
    ): void {
        $text = $this->pdfText($text);
        $size = $fontSize;
        $pdf->SetFont('Arial', $style, $size);

        while ($size > 5.5 && $pdf->GetStringWidth($text) > $maxWidth) {
            $size -= 0.25;
            $pdf->SetFont('Arial', $style, $size);
        }

        $pdf->Text($x, $baseline, $text);
    }

    private function writeCentered(
        InterviewAssessmentPdf $pdf,
        float $x,
        float $baseline,
        float $width,
        string $text,
        float $fontSize,
        string $style = ''
    ): void {
        $text = $this->pdfText($text);
        $size = $fontSize;
        $pdf->SetFont('Arial', $style, $size);

        while ($size > 5.5 && $pdf->GetStringWidth($text) > $width) {
            $size -= 0.25;
            $pdf->SetFont('Arial', $style, $size);
        }

        $textX = $x + max(0, ($width - $pdf->GetStringWidth($text)) / 2);
        $pdf->Text($textX, $baseline, $text);
    }

    private function pdfText(string $text): string
    {
        $converted = iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $text);

        return $converted === false ? $text : $converted;
    }
}

class InterviewAssessmentPdf extends Fpdi
{
    public function Circle(float $x, float $y, float $radius): void
    {
        $this->Ellipse($x, $y, $radius, $radius);
    }

    public function Ellipse(float $x, float $y, float $radiusX, float $radiusY, string $style = 'D'): void
    {
        $operation = $style === 'F' ? 'f' : ($style === 'FD' || $style === 'DF' ? 'B' : 'S');
        $controlX = 4 / 3 * (sqrt(2) - 1) * $radiusX;
        $controlY = 4 / 3 * (sqrt(2) - 1) * $radiusY;
        $scale = $this->k;
        $pageHeight = $this->h;

        $this->_out(sprintf('%.2F %.2F m', ($x + $radiusX) * $scale, ($pageHeight - $y) * $scale));
        $this->_out(sprintf(
            '%.2F %.2F %.2F %.2F %.2F %.2F c',
            ($x + $radiusX) * $scale,
            ($pageHeight - ($y - $controlY)) * $scale,
            ($x + $controlX) * $scale,
            ($pageHeight - ($y - $radiusY)) * $scale,
            $x * $scale,
            ($pageHeight - ($y - $radiusY)) * $scale
        ));
        $this->_out(sprintf(
            '%.2F %.2F %.2F %.2F %.2F %.2F c',
            ($x - $controlX) * $scale,
            ($pageHeight - ($y - $radiusY)) * $scale,
            ($x - $radiusX) * $scale,
            ($pageHeight - ($y - $controlY)) * $scale,
            ($x - $radiusX) * $scale,
            ($pageHeight - $y) * $scale
        ));
        $this->_out(sprintf(
            '%.2F %.2F %.2F %.2F %.2F %.2F c',
            ($x - $radiusX) * $scale,
            ($pageHeight - ($y + $controlY)) * $scale,
            ($x - $controlX) * $scale,
            ($pageHeight - ($y + $radiusY)) * $scale,
            $x * $scale,
            ($pageHeight - ($y + $radiusY)) * $scale
        ));
        $this->_out(sprintf(
            '%.2F %.2F %.2F %.2F %.2F %.2F c %s',
            ($x + $controlX) * $scale,
            ($pageHeight - ($y + $radiusY)) * $scale,
            ($x + $radiusX) * $scale,
            ($pageHeight - ($y + $controlY)) * $scale,
            ($x + $radiusX) * $scale,
            ($pageHeight - $y) * $scale,
            $operation
        ));
    }
}
