<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Tardiness & Undertime</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                margin: 0;
                padding: 0;
                text-align: center;
                font-size: 10px;
            }
            table {
                width: 100%;
                border: 1px solid rgb(255, 255, 255);
                border-collapse: collapse;
            }
            th, td {
                border: 1px solid black;
                padding: 0px;
                text-align: left;
            }
            .text-center{
                text-align: center;
            }
            .pl-2{
                padding-left: 3px;
            }
            .text-danger{
                color: red;
            }
            .p{
                padding: 1px;
            }
        </style>
    </head>
    <body>
        <table>
            <thead>
                <tr>
                    <th rowspan="2" width="20"></th>
                    <th colspan="2" class="text-center">TARDINESS</th>
                    <th colspan="2" class="text-center">UNDERTIME</th>
                </tr>
                <tr>
                    <th class="text-center" width="25%">MORNING</th>
                    <th class="text-center" width="25%">NOON</th>
                    <th class="text-center" width="25%">MORNING</th>
                    <th class="text-center" width="25%">AFTERNOON</th>
                </tr>
        
                @php
                    // Initialize total minutes for the different time periods
                    $totalMinutesMorning = 0;
                    $totalMinutesNoon = 0;
                    $totalUndertimeNoon = 0;
                    $totalUndertimeAfternoon = 0;
        
                    $morn_mon =  explode('-', $officialtimes->morn_mon);
                    $aft_mon =  explode('-', $officialtimes->aft_mon); 
        
                    $morn_tue =  explode('-', $officialtimes->morn_tue);
                    $aft_tue =  explode('-', $officialtimes->aft_tue);
        
                    $morn_wed = explode('-', $officialtimes->morn_wed);
                    $aft_wed =  explode('-', $officialtimes->aft_wed);
        
                    $morn_thu =  explode('-', $officialtimes->morn_thu);
                    $aft_thu =  explode('-', $officialtimes->aft_thu);
                    
                    $morn_fri =  explode('-', $officialtimes->morn_fri);
                    $aft_fri =  explode('-', $officialtimes->aft_fri);
        
                    $arraytime = [
                        'Monday' => [
                            'mornin' => $morn_mon[0],
                            'mornout' => $morn_mon[1],
                            'aftin' => $aft_mon[0],
                            'aftout' => $aft_mon[1],
                        ],
                        'Tuesday' => [
                            'mornin' => $morn_tue[0],
                            'mornout' => $morn_tue[1],
                            'aftin' => $aft_tue[0],
                            'aftout' => $aft_tue[1],
                        ],
                        'Wednesday' => [
                            'mornin' => $morn_wed[0],
                            'mornout' => $morn_wed[1],
                            'aftin' => $aft_wed[0],
                            'aftout' => $aft_wed[1],
                        ],
                        'Thursday' => [
                            'mornin' => $morn_thu[0],
                            'mornout' => $morn_thu[1],
                            'aftin' => $aft_thu[0],
                            'aftout' => $aft_thu[1],
                        ],
                        'Friday' => [
                            'mornin' => $morn_fri[0],
                            'mornout' => $morn_fri[1],
                            'aftin' => $aft_fri[0],
                            'aftout' => $aft_fri[1],
                        ],
                        'Saturday' => [
                            'mornin' => '08:00:00',
                            'mornout' => '12:00:00',
                            'aftin' => '13:00:00',
                            'aftout' => '17:00:00',
                        ],
                        'Sunday' => [
                            'mornin' => '08:00:00',
                            'mornout' => '12:00:00',
                            'aftin' => '13:00:00',
                            'aftout' => '17:00:00',
                        ],
                    ];
                @endphp
                
                @php
                    function convertMinutesToHoursAndMinutes($minutes) {
                        $hours = floor($minutes / 60);
                        $remainingMinutes = $minutes % 60;
                        return sprintf("%02d:%02d", $hours, $remainingMinutes);
                    }
                @endphp
                
                @php
                    // Initialize variables for total late and undertime
                    $totalLateMorning = $totalLateAfternoon = $totalUndertimeMorning = $totalUndertimeAfternoon = 0;
                @endphp
                
                @for($i = 1; $i <= 31; $i++)
                    @php
                        $currentDate = \Carbon\Carbon::parse("2024-$monthNumber-$i");
                        $dayOfWeek = $currentDate->format('l');
                        $rowData = $dtrRecords->firstWhere('date', '=', $currentDate->format('Y-m-d'));
                
                        // Get the schedule for the current day
                        $schedule = $arraytime[$dayOfWeek] ?? null;
                
                        // Initialize variables for late and undertime
                        $lateMorning = $lateAfternoon = $undertimeMorning = $undertimeAfternoon = null;
                
                        if ($schedule && $rowData) {
                            // Parse time_in and time_out into arrays and sort them
                            $timeInArray = $rowData->time_in ? explode(',', $rowData->time_in) : [];
                            $timeOutArray = $rowData->time_out ? explode(',', $rowData->time_out) : [];
                
                            // Sort the time arrays
                            usort($timeInArray, function($a, $b) {
                                return \Carbon\Carbon::parse($a)->timestamp - \Carbon\Carbon::parse($b)->timestamp;
                            });
                
                            usort($timeOutArray, function($a, $b) {
                                return \Carbon\Carbon::parse($a)->timestamp - \Carbon\Carbon::parse($b)->timestamp;
                            });
                
                            // Calculate late for morning
                            if (!empty($timeInArray[0])) {
                                $actualTimeInMorning = \Carbon\Carbon::parse($timeInArray[0]);
                                $scheduledTimeInMorning = \Carbon\Carbon::parse($schedule['mornin']);
                                if ($actualTimeInMorning->gt($scheduledTimeInMorning)) {
                                    $lateMorning = $actualTimeInMorning->diffInMinutes($scheduledTimeInMorning);
                                    $totalLateMorning += $lateMorning;
                                }
                            }
                
                            // Calculate undertime for morning
                            if (!empty($timeOutArray[0])) {
                                $actualTimeOutMorning = \Carbon\Carbon::parse($timeOutArray[0]);
                                $scheduledTimeOutMorning = \Carbon\Carbon::parse($schedule['mornout']);
                                if ($actualTimeOutMorning->lt($scheduledTimeOutMorning)) {
                                    $undertimeMorning = $scheduledTimeOutMorning->diffInMinutes($actualTimeOutMorning);
                                    $totalUndertimeMorning += $undertimeMorning;
                                }
                            }
                
                            // Calculate late for afternoon
                            if (!empty($timeInArray[1])) {
                                $actualTimeInAfternoon = \Carbon\Carbon::parse($timeInArray[1]);
                                $scheduledTimeInAfternoon = \Carbon\Carbon::parse($schedule['aftin']);
                                if ($actualTimeInAfternoon->gt($scheduledTimeInAfternoon)) {
                                    $lateAfternoon = $actualTimeInAfternoon->diffInMinutes($scheduledTimeInAfternoon);
                                    $totalLateAfternoon += $lateAfternoon;
                                }
                            }
                
                            // Calculate undertime for afternoon
                            if (!empty($timeOutArray[1])) {
                                $actualTimeOutAfternoon = \Carbon\Carbon::parse($timeOutArray[1]);
                                $scheduledTimeOutAfternoon = \Carbon\Carbon::parse($schedule['aftout']);
                                if ($actualTimeOutAfternoon->lt($scheduledTimeOutAfternoon)) {
                                    $undertimeAfternoon = $scheduledTimeOutAfternoon->diffInMinutes($actualTimeOutAfternoon);
                                    $totalUndertimeAfternoon += $undertimeAfternoon;
                                }
                            }
                        }
                    @endphp
                
                    <tr>
                        {{-- <th class="text-center">{{ $dayOfWeek }}</th> --}}
                        <th class="text-center">{{ $i }}</th>
                
                        <!-- Late and undertime values -->           
                        <th class="text-center">{{ $lateMorning ? number_format($lateMorning) : '' }}</th> {{-- On time --}}
                        <th class="text-center">{{ $undertimeMorning ? number_format($undertimeMorning) : '' }}</th>{{-- None --}}
                        <th class="text-center">{{ $lateAfternoon ? number_format($lateAfternoon) : '' }}</th>{{-- On time --}}
                        <th class="text-center">{{ $undertimeAfternoon ? number_format($undertimeAfternoon) : '' }}</th> {{-- None --}}
                    </tr>
                @endfor
                
                {{-- Add total row at the end --}}
                <tr>
                    <th class="text-center">&nbsp;TOTAL&nbsp;</th>
                    <th class="text-center">{{ $totalLateMorning ? number_format($totalLateMorning) : '' }}</th>
                    <th class="text-center">{{ $totalUndertimeMorning ? number_format($totalUndertimeMorning) : '' }}</th>
                    <th class="text-center">{{ $totalLateAfternoon ? number_format($totalLateAfternoon) : '' }}</th>
                    <th class="text-center">{{ $totalUndertimeAfternoon ? number_format($totalUndertimeAfternoon) : '' }}</th>
                </tr>
            
            </thead>
        </table>
        
    </body>
</html>
