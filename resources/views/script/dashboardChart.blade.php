@php $curr_route = request()->route()->getName(); @endphp

@if($curr_route == "dashboard")
<script>
$(function () {
    var pieChartCanvas = $('#pieChart').get(0).getContext('2d')
    var empReg = $('#pieChart').data('reg');
    var empJo = $('#pieChart').data('jo');
    var empPt = $('#pieChart').data('pt');
    var empPtPt = $('#pieChart').data('ptpt');
    var pieData = {
        labels: [
            'Regular',
            'Job Order',
            'Part-Time',
            'Part-Time/Part-Time',
        ],
        datasets: [
            {
                data: [empReg, empJo, empPt, empPtPt],
                backgroundColor: ['#04a45c', '#3c8cbc', '#f46c54', '#f49c14'],
            }
        ]
    }
    var pieOptions = {
        legend: {
            display: false
        }
    }
    var pieChart = new Chart(pieChartCanvas, {
        type: 'pie',
        data: pieData,
        options: pieOptions
    });

    var barChartData = {
        labels: ['Main Campus', 'Candoni', 'Cauyan', 'Hinigaran', 'Hinoba-an', 'Ilog', 'San Carlos', 'Sipalay', 'Victorias', 'Murcia', 'Valladolid', 'Moises Padilla'],
        datasets: [
            {
                label: 'Regular',
                backgroundColor: '#04a45c',
                borderColor: '#FFFF',
                borderWidth: 1,
                data: [
                    {{ $chartEmployee->where('emp_status', 1)->where('camp_id', 1)->count() }},
                    {{ $chartEmployee->where('emp_status', 1)->where('camp_id', 2)->count() }},
                    {{ $chartEmployee->where('emp_status', 1)->where('camp_id', 3)->count() }},
                    {{ $chartEmployee->where('emp_status', 1)->where('camp_id', 4)->count() }},
                    {{ $chartEmployee->where('emp_status', 1)->where('camp_id', 5)->count() }},
                    {{ $chartEmployee->where('emp_status', 1)->where('camp_id', 6)->count() }},
                    {{ $chartEmployee->where('emp_status', 1)->where('camp_id', 7)->count() }},
                    {{ $chartEmployee->where('emp_status', 1)->where('camp_id', 8)->count() }},
                    {{ $chartEmployee->where('emp_status', 1)->where('camp_id', 9)->count() }},
                    {{ $chartEmployee->where('emp_status', 1)->where('camp_id', 10)->count() }},    
                    {{ $chartEmployee->where('emp_status', 1)->where('camp_id', 11)->count() }}, 
                    {{ $chartEmployee->where('emp_status', 1)->where('camp_id', 12)->count() }}, 
                ],
            },
            {
                label: 'Job-Order',
                backgroundColor: '#3c8cbc',
                borderColor: '#FFFF',
                borderWidth: 1,
                data: [
                    {{ $chartEmployee->where('emp_status', 4)->where('camp_id', 1)->count() }},
                    {{ $chartEmployee->where('emp_status', 4)->where('camp_id', 2)->count() }},
                    {{ $chartEmployee->where('emp_status', 4)->where('camp_id', 3)->count() }},
                    {{ $chartEmployee->where('emp_status', 4)->where('camp_id', 4)->count() }},
                    {{ $chartEmployee->where('emp_status', 4)->where('camp_id', 5)->count() }},
                    {{ $chartEmployee->where('emp_status', 4)->where('camp_id', 6)->count() }},
                    {{ $chartEmployee->where('emp_status', 4)->where('camp_id', 7)->count() }},
                    {{ $chartEmployee->where('emp_status', 4)->where('camp_id', 8)->count() }},
                    {{ $chartEmployee->where('emp_status', 4)->where('camp_id', 9)->count() }},
                    {{ $chartEmployee->where('emp_status', 4)->where('camp_id', 10)->count() }},    
                    {{ $chartEmployee->where('emp_status', 4)->where('camp_id', 11)->count() }}, 
                    {{ $chartEmployee->where('emp_status', 4)->where('camp_id', 12)->count() }}, 
                ],
            },

            {
                label: 'Part-Time',
                backgroundColor: '#f46c54',
                borderColor: '#FFFF',
                borderWidth: 1,
                data: [
                    {{ $chartEmployee->where('emp_status', 2)->where('camp_id', 1)->count() }},
                    {{ $chartEmployee->where('emp_status', 2)->where('camp_id', 2)->count() }},
                    {{ $chartEmployee->where('emp_status', 2)->where('camp_id', 3)->count() }},
                    {{ $chartEmployee->where('emp_status', 2)->where('camp_id', 4)->count() }},
                    {{ $chartEmployee->where('emp_status', 2)->where('camp_id', 5)->count() }},
                    {{ $chartEmployee->where('emp_status', 2)->where('camp_id', 6)->count() }},
                    {{ $chartEmployee->where('emp_status', 2)->where('camp_id', 7)->count() }},
                    {{ $chartEmployee->where('emp_status', 2)->where('camp_id', 8)->count() }},
                    {{ $chartEmployee->where('emp_status', 2)->where('camp_id', 9)->count() }},
                    {{ $chartEmployee->where('emp_status', 2)->where('camp_id', 10)->count() }},    
                    {{ $chartEmployee->where('emp_status', 2)->where('camp_id', 11)->count() }}, 
                    {{ $chartEmployee->where('emp_status', 2)->where('camp_id', 12)->count() }}, 
                ],
            },

            {
                label: 'Part-Time/Part-Time',
                backgroundColor: '#f49c14',
                borderColor: '#FFFF',
                borderWidth: 1,
                data: [
                    {{ $chartEmployee->where('emp_status', 4)->where('partime_rate', '>', 0)->where('camp_id', 1)->count() }},
                    {{ $chartEmployee->where('emp_status', 4)->where('partime_rate', '>', 0)->where('camp_id', 2)->count() }},
                    {{ $chartEmployee->where('emp_status', 4)->where('partime_rate', '>', 0)->where('camp_id', 3)->count() }},
                    {{ $chartEmployee->where('emp_status', 4)->where('partime_rate', '>', 0)->where('camp_id', 4)->count() }},
                    {{ $chartEmployee->where('emp_status', 4)->where('partime_rate', '>', 0)->where('camp_id', 5)->count() }},
                    {{ $chartEmployee->where('emp_status', 4)->where('partime_rate', '>', 0)->where('camp_id', 6)->count() }},
                    {{ $chartEmployee->where('emp_status', 4)->where('partime_rate', '>', 0)->where('camp_id', 7)->count() }},
                    {{ $chartEmployee->where('emp_status', 4)->where('partime_rate', '>', 0)->where('camp_id', 8)->count() }},
                    {{ $chartEmployee->where('emp_status', 4)->where('partime_rate', '>', 0)->where('camp_id', 9)->count() }},
                    {{ $chartEmployee->where('emp_status', 4)->where('partime_rate', '>', 0)->where('camp_id', 10)->count() }},    
                    {{ $chartEmployee->where('emp_status', 4)->where('partime_rate', '>', 0)->where('camp_id', 11)->count() }}, 
                    {{ $chartEmployee->where('emp_status', 4)->where('partime_rate', '>', 0)->where('camp_id', 12)->count() }}, 
                ],
            },
        ],
    };

    var stackedBarChartCanvas = $('#stackedBarChart').get(0).getContext('2d');
    var stackedBarChartData = $.extend(true, {}, barChartData);

    var stackedBarChartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            xAxes: [{
                stacked: true,
            }],
            yAxes: [{
                stacked: true,
            }],
        },
    };

    new Chart(stackedBarChartCanvas, {
        type: 'bar',
        data: stackedBarChartData,
        options: stackedBarChartOptions,
    });
});
</script>
@endif