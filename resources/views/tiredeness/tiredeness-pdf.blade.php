<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Tirdeness</title>
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
                border-collapse: collapse; /* Added to collapse table borders */
            }
            th, td {
                border: 1px solid black; /* Ensures all cells have borders */
                padding: 0px;
                text-align: left; /* Aligns text to the left */
            }
            .text-center{
                text-align: center;
            }
        </style>
    </head>
    <body>
        <table>
            <thead>
                <tr>
                    <th rowspan="2"></th>
                    <th colspan="4" class="text-center">TIREDNESS</th>
                    <th colspan="4" class="text-center">UNDERTIME</th>
                </tr>
                <tr>
                    <th colspan="2" class="text-center">MORNING</th>
                    <th colspan="2" class="text-center">NOON</th>
                    <th colspan="2" class="text-center">MORNING</th>
                    <th colspan="2" class="text-center">NOON</th>
                </tr>
                <tr>
                    <th class="text-center" width="120">NAME</th>
                    <th class="text-center" width="50">DAYS</th>
                    <th class="text-center" width="50">TIME</th>
                    <th class="text-center" width="50">DAYS</th>
                    <th class="text-center" width="50">TIME</th>
                    <th class="text-center" width="50">DAYS</th>
                    <th class="text-center" width="50">TIME</th>
                    <th class="text-center" width="50">DAYS</th>
                    <th class="text-center" width="50">TIME</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dtrRecords as $record)
                <tr>
                    <th>{{ $record->lname }} {{ $record->prefix }} {{ $record->fname }} {{ isset($record->mname) ? substr($record->mname, 0, 1).'.' : '' }}</th>
                    <td class="text-center">{{ $record->morning_count }}</td>
                    <td class="text-center">{{ $record->total_hours }} : {{ $record->remaining_minutes }}</td>
                    <td class="text-center">{{ $record->noon_count }}</td>
                    <td class="text-center">{{ floor($record->total_noon_minutes / 60) }} : {{ $record->total_noon_minutes % 60 }}</td>
                    <td class="text-center">{{ $record->undertime_count }}</td>
                    <td class="text-center">{{ floor($record->total_undertime_minutes / 60) }} : {{ $record->total_undertime_minutes % 60 }}</td>
                    <td class="text-center">{{ $record->afternoon_undertime_count }}</td>
                    <td class="text-center">{{ floor($record->total_afternoon_undertime_minutes / 60) }} : {{ $record->total_afternoon_undertime_minutes % 60 }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </body>
</html>
