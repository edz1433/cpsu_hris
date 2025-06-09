@extends('layouts.master')

@section('body')
<div class="container mt-4">
    <h3>📊 System Performance Dashboard</h3>
    <p>Auto-refreshes every 15 seconds</p>

    <table class="table table-bordered table-striped mb-4">
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>Type</th>
                <th>URL / Description</th>
                <th>Duration (ms)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($entries as $entry)
                <tr>
                    <td>{{ $entry['timestamp'] }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $entry['type'])) }}</td>
                    <td>{{ $entry['type'] === 'request' ? $entry['url'] : 'Slow Query' }}</td>
                    <td>{{ $entry['time'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <canvas id="performanceChart"></canvas>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const entries = @json($entries);

    // Prepare data for the chart
    const labels = entries.map(e => e.timestamp);
    const requestTimes = entries.map(e => e.type === 'request' ? e.time : 0);
    const slowQueryTimes = entries.map(e => e.type === 'slow_query' ? e.time : 0);

    const ctx = document.getElementById('performanceChart').getContext('2d');

    const performanceChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Request Duration (ms)',
                    data: requestTimes,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                },
                {
                    label: 'Slow Query Duration (ms)',
                    data: slowQueryTimes,
                    backgroundColor: 'rgba(255, 99, 132, 0.6)',
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                x: { stacked: true },
                y: {
                    beginAtZero: true,
                    stacked: true,
                }
            }
        }
    });

    // Auto-refresh every 15 seconds
    setTimeout(() => {
        location.reload();
    }, 15000);
</script>
@endsection
