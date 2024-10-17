<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Tirdeness & Undertime</title>
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
                    <th colspan="2" class="text-center">TIREDNESS</th>
                    <th colspan="2" class="text-center">UNDERTIME</th>
                </tr>
                <tr>
                    <th class="text-center" width="25%">MORNING</th>
                    <th class="text-center" width="25%">NOON</th>
                    <th class="text-center" width="25%">MORNING</th>
                    <th class="text-center" width="25%">AFTERNOON</th>
                </tr>
            
                @php
                    $totalMinutesMorning = 0;
                    $totalMinutesNoon = 0;
                    $totalUndertimeNoon = 0;
                    $totalUndertimeAfternoon = 0;
                @endphp
            
                @for($i = 1; $i <= 31; $i++)
                    @php
                        $rowData = $dtrRecords->firstWhere('date', '=', \Carbon\Carbon::parse("2024-10-$i")->format('Y-m-d'));
                    @endphp
            
                    <tr>
                        <th class="text-center" width="50">{{ $i }}</th>
            
                        @if($rowData && $rowData->time_in)
                            @php
                                $timeInArray = explode(',', $rowData->time_in);
                                $timeOutArray = explode(',', $rowData->time_out ?? '');
            
                                $firstTimeIn = $timeInArray[0] ?? '';
                                $formattedTimeInMorning = '';
                                if (\Carbon\Carbon::parse($firstTimeIn)->gt(\Carbon\Carbon::parse('08:00:00'))) {
                                    $timeInMorning = \Carbon\Carbon::parse($firstTimeIn);
                                    $eightAm = \Carbon\Carbon::parse('08:00:00');
                                    $differenceInMinutesMorning = $timeInMorning->diffInMinutes($eightAm);
                                    $totalMinutesMorning += $differenceInMinutesMorning;
                                    $formattedTimeInMorning = $timeInMorning->format('H:i');
                                }
            
                                $lastTimeIn = end($timeInArray);
                                $formattedTimeInNoon = '';
                                if (\Carbon\Carbon::parse($lastTimeIn)->gt(\Carbon\Carbon::parse('13:00:00'))) {
                                    $timeInNoon = \Carbon\Carbon::parse($lastTimeIn);
                                    $onePm = \Carbon\Carbon::parse('13:00:00');
                                    $differenceInMinutesNoon = $timeInNoon->diffInMinutes($onePm);
                                    $totalMinutesNoon += $differenceInMinutesNoon;
                                    $formattedTimeInNoon = $timeInNoon->format('H:i');
                                }
            
                                $firstTimeOut = $timeOutArray[0] ?? '';
                                $formattedUndertimeNoon = '';
                                if ($firstTimeOut && \Carbon\Carbon::parse($firstTimeOut)->lt(\Carbon\Carbon::parse('12:00:00'))) {
                                    $timeOutNoon = \Carbon\Carbon::parse($firstTimeOut);
                                    $noonEnd = \Carbon\Carbon::parse('12:00:00');
                                    $undertimeMinutesNoon = $noonEnd->diffInMinutes($timeOutNoon);
                                    $totalUndertimeNoon += $undertimeMinutesNoon;
                                    $formattedUndertimeNoon = $timeOutNoon->format('H:i');
                                }
            
                                $lastTimeOut = end($timeOutArray);
                                $formattedUndertimeAfternoon = '';
                                if ($lastTimeOut && \Carbon\Carbon::parse($lastTimeOut)->lt(\Carbon\Carbon::parse('17:00:00'))) {
                                    $timeOutAfternoon = \Carbon\Carbon::parse($lastTimeOut);
                                    $fivePm = \Carbon\Carbon::parse('17:00:00');
                                    $undertimeMinutesAfternoon = $fivePm->diffInMinutes($timeOutAfternoon);
                                    $totalUndertimeAfternoon += $undertimeMinutesAfternoon;
                                    $formattedUndertimeAfternoon = $timeOutAfternoon->format('H:i');
                                }
                            @endphp
            
                            <th class="text-center">{{ $formattedTimeInMorning }}</th>
            
                            <th class="text-center">{{ $formattedTimeInNoon }}</th>
            
                            <th class="text-center">{{ $formattedUndertimeNoon }}</th>
            
                            <th class="text-center">{{ $formattedUndertimeAfternoon }}</th>
                        @else
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                        @endif
                    </tr>
                @endfor
            
                    @php
                    $hoursMorning = floor($totalMinutesMorning / 60);
                    $minutesMorning = $totalMinutesMorning % 60;
                
                    $hoursNoon = floor($totalMinutesNoon / 60);
                    $minutesNoon = $totalMinutesNoon % 60;
                
                    $totalUndertimeNoon += 3; 
                    $hoursUndertimeNoon = floor($totalUndertimeNoon / 60);
                    $minutesUndertimeNoon = $totalUndertimeNoon % 60;
                
                    $totalUndertimeAfternoon += 3; 
                    $hoursUndertimeAfternoon = floor($totalUndertimeAfternoon / 60);
                    $minutesUndertimeAfternoon = $totalUndertimeAfternoon % 60;
                @endphp
                
                <tr>
                    <th class="text-center">TOTAL</th>
                    <th class="text-center">{{ sprintf('%02d:%02d', $hoursMorning, $minutesMorning) }}</th>
                    <th class="text-center">{{ sprintf('%02d:%02d', $hoursNoon, $minutesNoon) }}</th>
                    <th class="text-center">{{ sprintf('%02d:%02d', $hoursUndertimeNoon, $minutesUndertimeNoon) }}</th>
                    <th class="text-center">{{ sprintf('%02d:%02d', $hoursUndertimeAfternoon, $minutesUndertimeAfternoon) }}</th>
                </tr>
            </thead>
            
        </table>  
    </body>
</html>
