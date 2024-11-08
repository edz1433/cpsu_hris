@extends('layouts.master')

@section('body')
<style>  
    #table-form {
        width: 100%;
        font-size: 10px;
    }

    #table-form td, th{
        border: 1px solid rgb(92, 85, 85);
        padding: 1px;
    }
    .b-none{
        border: none !important;
        width: 18px !important;
    }

    .btn-outline-secondary {
        border-radius: 50px;
        width: 30px !important;
        height: 30px !important;
    }
    .border-b-n{
        border-bottom: none;
    }
</style>
<table id="table-form">
    <thead>
        <tr>
            <th rowspan="4" class="text-center" width="135">MFO/PAPs</th>
            <th rowspan="3" class="text-center" width="100">Success Indicators</th>
            <th colspan="2" class="text-center" width="100">Evidence</th>
            <th rowspan="4" class="text-center" width="70">Allotted Budget</th>
            <th rowspan="4" class="text-center" width="70">Division/ Individuals Accountable</th>
            <th rowspan="2" colspan="4" class="text-center border-b-n"></th>
            <th rowspan="4" class="text-center">Remarks/<br>Accomplishment</th>
        </tr>
        <tr>
            <th rowspan="3" class="text-center" width="80">Individual Support Documents</th>
            <th rowspan="3" class="text-center" width="80">Report of Supervisor/ Other Offices</th>
        </tr>
        <tr>
            <th class="b-none text-center" colspan="4" width="135" height="30">Rating Guide/Accomplishment</th>
        </tr>
        <tr>
            <th>(Targets + Measures)</th>
            <th class="text-center" width="135">Q</th>
            <th class="text-center" width="135">E</th>
            <th class="text-center" width="135">T</th>
            <th class="text-center" width="135">A</th>
        </tr>
    </thead>
    <tbody id="tbody-form">
        @foreach($prs as $pr)
        <tr>
            <td><b>{{ $pr->mfo }} ({{ $pr->percent  }}%)</b></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td class="b-none text-left"> <i class="fas fa-plus pl-1"></td>
        </tr>
        @endforeach
        <tr>
            <td>
                MFO 1: Provision of Accessible, Equitable, Quality, and Relevant Curricular Programs (0%)
            </td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>
                MFO 2: Excellence in Research and Creative Works (0%)
            </td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>
                MFO 3: Delivery of Extension and Community Services (5%)
            </td>
            <td>100% of the staff wil involve in the extension activities of the office.</td>
            <td>Attendance, Pictures</td>
            <td></td>
            <td></td>
            <td class="text-center">All Personnel</td>
            <td></td>
            <td class="text-center">
                5 = 100%, <br>
                4 = 90 - 99%,  <br>
                3 = 80% - 89%, <br>
                2 = 70% - 79%  <br>
                1 = Below 70%  <br>
            </td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
    </tbody>
</table>
@endsection