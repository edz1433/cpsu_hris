<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DEPARTMENT PERFORMACNCE COMMITMENT AND REVIEW</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            margin: -10px 40px;
        }
        .center {
            text-align: center;
        }
        .bold {
            font-weight: bold;
        }
        .underline {
            text-decoration: underline;
        }
        .spaced {
            letter-spacing: 1px;
        }
        .section {
            margin-top: 40px;
        }
        .signature-block {
            margin-top: 60px;
        }
        .name-block {
            margin-top: 40px;
        }
        .label {
            margin-top: 10px;
        }
        .footer {
            position: absolute;
            bottom: 10px;
            left: 40px;
            right: 40px;
            font-size: 9pt;
            display: flex;
            justify-content: space-between;
        }
        .footer span {
            white-space: nowrap;
        }
        .f-right {
            float: right;
        }
        .page {
            page-break-after: always;
            position: relative;
            min-height: 1000px;
        }
        .last-page {
            page-break-after: auto;
        }

        p {
            margin-top: 2px;
            margin-bottom: 2px;
        }
    </style>
</head>
<body>
    <!-- PAGE 1 -->
    <div class="page">
        <img src="{{ $images['header'] }}" style="width: 100%; margin-bottom: 10px;" alt="Header Image">

        <div class="center bold spaced" style="margin-bottom: 10px;">
            DEPARTMENT PERFORMANCE COMMITMENT AND REVIEW (DPCR)
        </div>

        <p style="text-align: justify; line-height: 1.7;">
            I, <span class="underline bold">
            {{ strtoupper($employee->fname) }}
            @if(!empty($employee->mname))
                {{ strtoupper(substr($employee->mname,0,1)) }}.
            @endif
            {{ strtoupper($employee->lname) }} {{  strtoupper($employee->suffix) }}
            </span>, HEAD of <span class="underline">{{ strtoupper($office->office_abbr) }}</span> commit to
            deliver and agree to be rated on the attainment of the following targets in accordance with the
            indicated measures for the period 
            <span class="underline bold">
            @if($category == 1 || $category == 0)
                JANUARY – JUNE
            @elseif($category == 2)
                JULY – DECEMBER
            @endif
            </span>
            , <span class="underline bold">2025</span>.
        </p>

        <div class="f-right">
            <div class="name-block center bold underline">
                {{
                    strtoupper($employee->fname) . ' ' .
                    (isset($employee->mname) ? strtoupper(substr($employee->mname, 0, 1)) . '. ' : '') .
                    strtoupper($employee->lname) .
                    (!empty($employee->suffix) ? ' ' . $employee->suffix : '') .
                    (!empty($employee->prefix) ? ', ' . $employee->prefix : '')
                }}
            </div>
            <div class="center">Ratee</div>
        </div>

        <br><br><br><br>

        <div class="section">
            <div class="bold">Recommending Approval:</div>
            <div class="name-block bold underline">
                {{-- MARC ALEXEI CAESAR B. BADAJOS, Ph.D. --}}
                {{
                    strtoupper($supervisor->fname) . ' ' .
                    (isset($supervisor->mname) ? strtoupper(substr($supervisor->mname, 0, 1)) . '. ' : '') .
                    strtoupper($supervisor->lname) .
                    (!empty($supervisor->suffix) ? ' ' . $supervisor->suffix : '') .
                    (!empty($supervisor->prefix) ? ', ' . $supervisor->prefix : '')
                }}
            </div>
            <div>Immediate Supervisor</div>
            <div class="label">Date:</div>
        </div>

        <div class="section">
            <div class="bold">Reviewed:</div>

            <div class="name-block bold underline">
                {{-- MARIA CRISTINA I. CANSON-BADAJOS --}}
                {{
                    strtoupper($reviewsby[1]->fname) . ' ' .
                    (isset($reviewsby[1]->mname) ? strtoupper(substr($reviewsby[1]->mname, 0, 1)) . '. ' : '') .
                    strtoupper($reviewsby[1]->lname) .
                    (!empty($reviewsby[1]->suffix) ? ' ' . $reviewsby[1]->suffix : '') .
                    (!empty($reviewsby[1]->prefix) ? ', ' . $reviewsby[1]->prefix : '')
                }}
            </div>
            <div>
                Performance Management Team
            </div>
            <div class="label">Date:</div>

            <div class="name-block bold underline" style="margin-top: 30px;">
                {{-- GRENNY I. JUNCO, Ph. D. --}}
                {{
                    strtoupper($reviewsby[0]->fname) . ' ' .
                    (isset($reviewsby[0]->mname) ? strtoupper(substr($reviewsby[0]->mname, 0, 1)) . '. ' : '') .
                    strtoupper($reviewsby[0]->lname) .
                    (!empty($reviewsby[0]->suffix) ? ' ' . $reviewsby[0]->suffix : '') .
                    (!empty($reviewsby[0]->prefix) ? ', ' . $reviewsby[0]->prefix : '')
                }}
            </div>
            <div>
                Performance Management Team
            </div>
            <div class="label">Date:</div>
        </div>

        <div class="section center" style="margin-left: -10%;">
            <div class="bold" style="margin-left: -43%; margin-bottom: -3.5%;">APPROVED:</div>

            <div class="name-block bold underline">
                {{-- ALADINO C. MORACA, Ph.D. --}}
                {{
                    strtoupper($approveby[0]->fname) . ' ' .
                    (isset($approveby[0]->mname) ? strtoupper(substr($approveby[0]->mname, 0, 1)) . '. ' : '') .
                    strtoupper($approveby[0]->lname) .
                    (!empty($approveby[0]->suffix) ? ' ' . $approveby[0]->suffix : '') .
                    (!empty($approveby[0]->prefix) ? ', ' . $approveby[0]->prefix : '')
                }}
            </div>
            <div>President</div>
            <div class="label" style="margin-left: -3.5%;">Date:</div>
        </div>
            
        <div class="footer center">
            <span>Doc Control Code: &nbsp;&nbsp; CPSU-HRMO-21 &nbsp;&nbsp;</span>
            <span>Effective Date: &nbsp;&nbsp; 08/07/2024 &nbsp;&nbsp;</span>
            <span>Page No.: &nbsp;&nbsp; 1 of 5</span>
        </div>
    </div>

    <!-- PAGE 2 -->
    <div class="page">
        <img src="{{ $images['header'] }}" style="width: 100%; margin-bottom: 10px;" alt="Header Image">

        <div class="center bold spaced" style="margin-bottom: 10px;">
            ATTACHMENT
        </div>

        <p style="text-align: justify; line-height: 1.7; margin-bottom: 10px;">
            Kindly fill-in the following details. 
        </p>

        <div style="margin-left: 5%;">
            <p><b>I. For Faculty Members</b></p>
            <p>&nbsp;&nbsp;&nbsp;a. Academic Rank</p>
            <table border="1" cellpadding="2" cellspacing="0" style="border-collapse: collapse; width: 100%; font-size: 11pt;">
                <thead>
                <tr class="center">
                    <td style="width: 5%;">Please<br>check</td>
                    <td width="120">Rank</td>
                    <td>Instruction</td>
                    <td width="120">Research, Innovation<br>and/or Creative Work</td>
                    <td>Extension</td>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td class="center"><input type="checkbox" style="transform: scale(1.2);" disabled></td>
                    <td class="center">Instructor</td>
                    <td class="center">60%</td>
                    <td class="center">10%</td>
                    <td class="center"></td>
                </tr>
                <tr>
                    <td class="center"><input type="checkbox" style="transform: scale(1.2);" disabled></td>
                    <td class="center">Assistant Professor</td>
                    <td class="center">50%</td>
                    <td class="center">20%</td>
                    <td class="center">20%</td>
                </tr>
                <tr>
                    <td class="center"><input type="checkbox" style="transform: scale(1.2);" disabled></td>
                    <td class="center">Associate Professor</td>
                    <td class="center">40%</td>
                    <td class="center">20%</td>
                    <td class="center">30%</td>
                </tr>
                <tr>
                    <td class="center"><input type="checkbox" style="transform: scale(1.2);" disabled></td>
                    <td class="center">Professor</td>
                    <td class="center">30%</td>
                    <td class="center">20%</td>
                    <td class="center">40%</td>
                </tr>
                <tr>
                    <td class="center"><input type="checkbox" style="transform: scale(1.2);" disabled></td>
                    <td class="center">University Professor</td>
                    <td class="center">20%</td>
                    <td class="center">20%</td>
                    <td class="center">50%</td>
                </tr>
                </tbody>
            </table>
        </div>

        <div style="margin-left: 7.5%; margin-top: 10px;">
            <p>b. Teaching load (in units) <span style="margin-left: 28px; display: inline-block; width: 282px; border-bottom: 1px solid #000;">&nbsp;</span></p>
            <p>c. Designation load (in units) <span style="margin-left: 12px; display: inline-block; width: 282px; border-bottom: 1px solid #000;">&nbsp;</span></p>
            <p>d. Designation/s</p>
            <div style="margin-left: 5%;">
                <p>1. <span style="margin-left: 7px; display: inline-block; width: 430px; border-bottom: 1px solid #000;">&nbsp;</span></p>
                <p>2. <span style="margin-left: 7px; display: inline-block; width: 430px; border-bottom: 1px solid #000;">&nbsp;</span></p>
                <p>3. <span style="margin-left: 7px; display: inline-block; width: 430px; border-bottom: 1px solid #000;">&nbsp;</span></p>

            </div>
        </div>

        <div style="margin-left: 5%; margin-top: 10px;">
            <p><b>II. For Staff</b></p>
        </div>

        <div style="margin-left: 7.5%; margin-top: 10px;">
            <p>a. Position<span style="margin-left: 114px; display: inline-block; width: 300px; border-bottom: 1px solid #000;">&nbsp;</span></p>
            <p>b. Designation/s</p>
            <div style="margin-left: 5%;">
                <p>1. <span style="margin-left: 7px; display: inline-block; width: 430px; border-bottom: 1px solid #000;">&nbsp;</span></p>
                <p>2. <span style="margin-left: 7px; display: inline-block; width: 430px; border-bottom: 1px solid #000;">&nbsp;</span></p>
                <p>3. <span style="margin-left: 7px; display: inline-block; width: 430px; border-bottom: 1px solid #000;">&nbsp;</span></p>
            </div>
        </div>

        <div style="margin-left: 5%; margin-top: 10px;">
            <p><b>III. For Faculty and Staff</b></p>
            <p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Specify strategic targets for MFO 4 (Production) and MFO 5 (Good Governance)</p>
        </div>

        <div style="margin-left: 8.5%; margin-top: 10px;">
            <p>a. MFO4 Strategic Targets</p>
            <div style="margin-left: 5%;">
                <p>1. <span style="margin-left: 7px; display: inline-block; width: 430px; border-bottom: 1px solid #000;">&nbsp;</span></p>
                <p>2. <span style="margin-left: 7px; display: inline-block; width: 430px; border-bottom: 1px solid #000;">&nbsp;</span></p>
            </div>
            <p>b. MFO5</p>
            <div style="margin-left: 5%;">
                <p>1. <span style="margin-left: 7px; display: inline-block; width: 430px; border-bottom: 1px solid #000;">&nbsp;</span></p>
                <p>2. <span style="margin-left: 7px; display: inline-block; width: 430px; border-bottom: 1px solid #000;">&nbsp;</span></p>
            </div>
        </div>

        <div style="margin-left: 5%; margin-top: 10px;">
            <p><b>IV. Specify Personnel Category (refer to table 1 below):</b> <span style="margin-left: 50px; display: inline-block; width: 110px; border-bottom: 1px solid #000;">&nbsp;</span></p>
        </div>
        <b><i>Table 1. Weight Allocation by function</i></b>
        <img src="{{ $images['img1'] }}" style="width: 100%; margin-bottom: 10px;" alt="Header Image">

        <div class="footer center">
            <span>Doc Control Code: &nbsp;&nbsp; CPSU-HRMO-21 &nbsp;&nbsp;</span>
            <span>Effective Date: &nbsp;&nbsp; 08/07/2024 &nbsp;&nbsp;</span>
            <span>Page No.: &nbsp;&nbsp; 2 of 5</span>
        </div>
    </div>

    <!-- PAGE 3 (Example of reusable structure) -->
    <div class="page">
        <img src="{{ $images['header'] }}" style="width: 100%; margin-bottom: 10px;" alt="Header Image">
        <img src="{{ $images['img2'] }}" style="width: 100%; margin-bottom: 10px;" alt="Header Image">
        <div class="footer center">
            <span>Doc Control Code: &nbsp;&nbsp; CPSU-HRMO-21 &nbsp;&nbsp;</span>
            <span>Effective Date: &nbsp;&nbsp; 08/07/2024 &nbsp;&nbsp;</span>
            <span>Page No.: &nbsp;&nbsp; 3 of 5</span>
        </div>
    </div>

    <div class="page">
        <img src="{{ $images['header'] }}" style="width: 100%; margin-bottom: 10px;" alt="Header Image">
        <img src="{{ $images['img4'] }}" style="width: 100%; margin-bottom: 10px;" alt="Header Image">
        <div class="footer center">
            <span>Doc Control Code: &nbsp;&nbsp; CPSU-HRMO-21 &nbsp;&nbsp;</span>
            <span>Effective Date: &nbsp;&nbsp; 08/07/2024 &nbsp;&nbsp;</span>
            <span>Page No.: &nbsp;&nbsp; 4 of 5</span>
        </div>
    </div>

    <div class="page last-page">
        <img src="{{ $images['header'] }}" style="width: 100%; margin-bottom: 10px;" alt="Header Image">
        <div class="center bold spaced" style="margin-bottom: 10px;">
            Rating Computation Form
        </div>
        <img src="{{ $images['img3'] }}" style="width: 100%; margin-bottom: 10px;" alt="Header Image">
        <div class="footer center">
            <span>Doc Control Code: &nbsp;&nbsp; CPSU-HRMO-21 &nbsp;&nbsp;</span>
            <span>Effective Date: &nbsp;&nbsp; 08/07/2024 &nbsp;&nbsp;</span>
            <span>Page No.: &nbsp;&nbsp; 5 of 5</span>
        </div>
    </div>

</body>
</html>
