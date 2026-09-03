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
        
                @foreach($dailyRows as $row)
                    <tr>
                        <th class="text-center">{{ $row['day'] }}</th>
                           
                        <!-- Morning Late -->
                        <th class="text-center">
                            @if (!$row['has_record'])

                            @elseif ($row['time_in_review'])
                                Review
                            @else
                                {{ $row['morning_late_minutes'] > 0 ? sprintf('%02d:%02d', floor($row['morning_late_minutes'] / 60), $row['morning_late_minutes'] % 60) : '' }}
                            @endif
                        </th>

                        <!-- Afternoon Late -->
                        <th class="text-center">
                            @if (!$row['has_record'])
                            
                            @elseif ($row['time_in_review'])
                                Review
                            @else
                                {{ $row['afternoon_late_minutes'] > 0 ? sprintf('%02d:%02d', floor($row['afternoon_late_minutes'] / 60), $row['afternoon_late_minutes'] % 60) : '' }}
                            @endif
                        </th>

                        <!-- Morning Undertime -->
                        <th class="text-center">
                            @if (!$row['has_record'])
                            
                            @elseif ($row['time_out_review'])
                                Review
                            @else
                                {{ $row['morning_undertime_minutes'] > 0 ? sprintf('%02d:%02d', floor($row['morning_undertime_minutes'] / 60), $row['morning_undertime_minutes'] % 60) : '' }}
                            @endif
                        </th>

                        <!-- Afternoon Undertime -->
                        <th class="text-center">
                            @if (!$row['has_record'])
                            
                            @elseif ($row['time_out_review'])
                                Review
                            @else
                                {{ $row['afternoon_undertime_minutes'] > 0 ? sprintf('%02d:%02d', floor($row['afternoon_undertime_minutes'] / 60), $row['afternoon_undertime_minutes'] % 60) : '' }}
                            @endif
                        </th>

                    </tr>
                @endforeach
                
                {{-- Add total row at the end --}}
                <tr>
                    <th class="text-center">&nbsp;TOTAL&nbsp;</th>
                    <th class="text-center">{{ $formattedSummary['morning_late_minutes'] ?? '00:00' }}</th>
                    <th class="text-center">{{ $formattedSummary['afternoon_late_minutes'] ?? '00:00' }}</th>
                    <th class="text-center">{{ $formattedSummary['morning_undertime_minutes'] ?? '00:00' }}</th>
                    <th class="text-center">{{ $formattedSummary['afternoon_undertime_minutes'] ?? '00:00' }}</th>
                </tr>
            </thead>
        </table>    
    </body>
</html>
