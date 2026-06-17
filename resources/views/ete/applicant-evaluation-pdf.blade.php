<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>ETE Evaluation - {{ $application->app_number }}</title>
    <style>
        @page { margin: 22px 28px 28px; }
        body { color: #111; font-family: DejaVu Sans, sans-serif; font-size: 8px; line-height: 1.2; }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        h1 { font-size: 12px; margin: 0; text-align: center; }
        .subtitle { font-size: 8px; margin: 2px 0 8px; text-align: center; }
        table { border-collapse: collapse; table-layout: fixed; width: 100%; }
        td, th { border: 1px solid #111; padding: 3px 4px; vertical-align: middle; }
        .no-border td { border: 0; padding: 2px 3px; }
        .section { background: #e7e7e7; font-weight: bold; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .check { border: 1px solid #111; display: inline-block; height: 8px; line-height: 8px; margin-right: 2px; text-align: center; width: 8px; }
        .small { font-size: 7px; }
        .signature { height: 24px; max-width: 125px; object-fit: contain; }
        .signature-line { border-top: 1px solid #111; margin: 2px auto 0; padding-top: 2px; width: 210px; }
        .footer { font-size: 6.5px; margin-top: 5px; }
        .score { font-size: 10px; font-weight: bold; }
    </style>
</head>
<body>
@php
    $educationItems = [
        'additional_four_year_course' => ['Additional 4-year course completed', 2],
        'masteral_1_18' => ['1-18 masteral units', 1],
        'masteral_19_30' => ['19-30 masteral units', 2],
        'masters_degree' => ["Master's degree completed", 4],
        'doctoral_1_18' => ['1-18 doctoral units', 5],
        'doctoral_19_36' => ['19-36 doctoral units', 6],
        'doctoral_degree' => ['Doctoral degree completed', 10],
    ];
    $applicantName = trim($application->first_name . ' ' . $application->middle_name . ' ' . $application->last_name);
@endphp

@foreach($ratings as $rating)
    @php
        $educationRatings = $rating->education_ratings ?? [];
        $trainingRatings = $rating->training_ratings ?? [];
        $experienceRatings = $rating->experience_year_ratings ?? [];
        $evaluatorName = trim(
            ($rating->evaluator->prefix ? $rating->evaluator->prefix . ' ' : '') .
            $rating->evaluator->fname . ' ' .
            $rating->evaluator->mname . ' ' .
            $rating->evaluator->lname . ' ' .
            $rating->evaluator->suffix
        );
    @endphp
    <div class="page">
        <h1>EVALUATION OF EDUCATION, TRAINING, AND EXPERIENCE</h1>
        <div class="subtitle">(To be accomplished by the HRMPSB Committee)</div>

        <table class="no-border">
            <tr>
                <td width="62%"><span class="bold">Name:</span> {{ $applicantName }}</td>
                <td><span class="bold">Date:</span> {{ optional($rating->evaluation_date ?? $ete->evaluation_date)->format('m/d/Y') }}</td>
            </tr>
            <tr>
                <td colspan="2"><span class="bold">Considered for the Position of:</span> {{ $ete->job->title ?? $application->position }}</td>
            </tr>
            <tr>
                <td><span class="bold">Present Position:</span> {{ $rating->present_position ?: 'N/A' }}</td>
                <td><span class="bold">College/Campus/Division/Department:</span> {{ $rating->college_department ?: 'N/A' }}</td>
            </tr>
        </table>

        <table>
            <tr><td colspan="4" class="section">Minimum requirements - 70 points (Refer to attached Qualification Standards for the position)</td></tr>
            @foreach([
                ['Education', 'education_met', $ete->job->education ?? ''],
                ['Experience', 'experience_met', $ete->job->experience ?? ''],
                ['Eligibility', 'eligibility_met', $ete->job->eligibility ?? ''],
                ['Training', 'training_met', $ete->job->training ?? ''],
            ] as [$label, $field, $requirement])
                <tr>
                    <td width="13%" class="bold">{{ $label }}</td>
                    <td width="57%">{{ $requirement ?: 'See attached Qualification Standards' }}</td>
                    <td width="15%" class="center"><span class="check">{{ $rating->{$field} === true ? 'X' : '' }}</span> Met</td>
                    <td width="15%" class="center"><span class="check">{{ $rating->{$field} === false ? 'X' : '' }}</span> Not met</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="3" class="right bold">For meeting all minimum admission requirements</td>
                <td class="center score">{{ number_format($rating->minimum_requirement_score, 2) }}</td>
            </tr>
        </table>

        <table>
            <tr><td colspan="3" class="section">Additional credits for EDUCATION AND TRAINING in excess of minimum requirements (30 points)</td></tr>
            <tr>
                <th width="60%">Education (total not to exceed 10)</th>
                <th width="20%">Credit</th>
                <th width="20%">Awarded</th>
            </tr>
            @foreach($educationItems as $key => [$label, $credit])
                <tr>
                    <td>{{ $label }}</td>
                    <td class="center">{{ $credit }}</td>
                    <td class="center">{{ !empty($educationRatings[$key]) ? $credit : '' }}</td>
                </tr>
            @endforeach
            <tr><td colspan="2" class="right bold">Education Total</td><td class="center bold">{{ number_format($rating->education_score, 2) }}</td></tr>
        </table>

        <table>
            <tr>
                <th width="60%">Training (total not to exceed 5)</th>
                <th width="20%">Credit</th>
                <th width="20%">Awarded</th>
            </tr>
            <tr>
                <td>Relevant study or scholarship grant</td>
                <td class="center">3</td>
                <td class="center">{{ !empty($trainingRatings['scholarship_grant']) ? '3' : '' }}</td>
            </tr>
            <tr>
                <td>Any comparable leadership seminar</td>
                <td class="center">2</td>
                <td class="center">{{ !empty($trainingRatings['leadership_seminar']) ? '2' : '' }}</td>
            </tr>
            <tr>
                <td>For every 50 hours consisting of one or more relevant in-service training</td>
                <td class="center">1 / 50 hrs</td>
                <td class="center">{{ floor(($trainingRatings['relevant_hours'] ?? 0) / 50) }}</td>
            </tr>
            <tr><td colspan="2" class="right bold">Training Total</td><td class="center bold">{{ number_format($rating->training_score, 2) }}</td></tr>
        </table>

        <table>
            <tr><td colspan="4" class="section">Experience (total not to exceed 15)</td></tr>
            <tr>
                <th width="15%">Year</th>
                <th width="35%">Length of Experience</th>
                <th width="30%">Level of Experience</th>
                <th width="20%">Credit</th>
            </tr>
            @foreach($years as $year)
                <tr>
                    <td class="center">{{ $year }}</td>
                    <td class="center">{{ $experienceRatings[$year]['length'] ?? '' }}</td>
                    <td class="center">{{ $experienceRatings[$year]['level'] ?? '' }}</td>
                    <td class="center">{{ $experienceRatings[$year]['credit'] ?? '' }}</td>
                </tr>
            @endforeach
            <tr><td colspan="3" class="right bold">Experience Total</td><td class="center bold">{{ number_format($rating->experience_score, 2) }}</td></tr>
        </table>

        <table>
            <tr><td colspan="3" class="section">Qualification over and above the minimum requirements</td></tr>
            <tr><td>Education</td><td width="22%" class="center">{{ number_format($rating->education_score, 2) }}</td><td rowspan="3" width="28%" class="center"><span class="bold">TOTAL RATING</span><br><span class="score">{{ number_format($rating->total_score, 2) }}</span></td></tr>
            <tr><td>Training</td><td class="center">{{ number_format($rating->training_score, 2) }}</td></tr>
            <tr><td>Experience</td><td class="center">{{ number_format($rating->experience_score, 2) }}</td></tr>
        </table>

        @if($rating->remarks)
            <div style="margin-top:4px;"><span class="bold">Remarks:</span> {{ $rating->remarks }}</div>
        @endif

        <div class="center" style="margin-top:8px;">
            <div class="bold">Rated by:</div>
            @if($rating->signature_data)
                <img class="signature" src="{{ $rating->signature_data }}">
            @else
                <div style="height:24px;"></div>
            @endif
            <div class="signature-line">
                <span class="bold">{{ $evaluatorName }}</span><br>
                HRMPSB's Signature over Printed Name
            </div>
        </div>

        <table class="footer">
            <tr>
                <td>Doc Control Code: CPSU-F-HRMO-06</td>
                <td class="center">Effective Date: 09/12/2018</td>
                <td class="right">Evaluator Form {{ $loop->iteration }} of {{ $ratings->count() }}</td>
            </tr>
        </table>
    </div>
@endforeach
</body>
</html>
