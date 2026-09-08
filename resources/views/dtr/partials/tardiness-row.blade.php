{{-- Late/undertime totals for the selected period, printed just above day 31. --}}
@if (!empty($showTardiness))
    <tr>
        <td colspan="7" style="font-size: 8px; text-align: center; padding: 1px 2px; white-space: nowrap;">
            <span style="padding-right: 28px;"><b>Late:</b> {{ $tardinessTotals['late_label'] }}</span>
            <span><b>Undertime:</b> {{ $tardinessTotals['undertime_label'] }}</span>
        </td>
    </tr>
@endif
