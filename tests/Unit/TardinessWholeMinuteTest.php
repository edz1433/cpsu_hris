<?php

namespace Tests\Unit;

use App\Http\Controllers\MasterController;
use App\Http\Controllers\TirednessController;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class TardinessWholeMinuteTest extends TestCase
{
    /** @dataProvider controllerProvider */
    public function test_seconds_and_milliseconds_do_not_create_tardiness(string $controllerClass): void
    {
        $controller = (new ReflectionClass($controllerClass))->newInstanceWithoutConstructor();

        $this->assertSame(0, $this->invoke($controller, 'minutesAfter', '08:00:59.999', '08:00:00'));
        $this->assertSame(0, $this->invoke($controller, 'minutesAfter', '13:00:59.999999', '13:00'));
        $this->assertSame(0, $this->invoke($controller, 'minutesBefore', '11:59:00.001', '11:59:59'));
    }

    /** @dataProvider controllerProvider */
    public function test_tardiness_starts_on_the_next_whole_minute(string $controllerClass): void
    {
        $controller = (new ReflectionClass($controllerClass))->newInstanceWithoutConstructor();

        $this->assertSame(1, $this->invoke($controller, 'minutesAfter', '08:01:00.000', '08:00:59.999'));
        $this->assertSame(1, $this->invoke($controller, 'minutesBefore', '11:59:59.999', '12:00:00'));
    }

    public function test_employee_dashboard_and_tardiness_report_return_the_same_total(): void
    {
        $dtr = (object) [
            'date' => '2026-09-07',
            'time_in' => '08:01:59.999,13:02:00.001',
            'time_out' => '11:59:59.999,16:58:00.001',
        ];

        $master = (new ReflectionClass(MasterController::class))->newInstanceWithoutConstructor();
        $report = (new ReflectionClass(TirednessController::class))->newInstanceWithoutConstructor();

        $dashboardSummary = $this->invoke($master, 'dtrTardinessSummary', collect([$dtr]));
        $reportSummary = $this->invoke($report, 'summarizeDtrRecords', collect([$dtr]), null);

        $this->assertSame(3, $dashboardSummary['late_minutes']);
        $this->assertSame(3, $reportSummary['morning_late_minutes'] + $reportSummary['afternoon_late_minutes']);
        $this->assertSame(3, $dashboardSummary['undertime_minutes']);
        $this->assertSame(3, $reportSummary['morning_undertime_minutes'] + $reportSummary['afternoon_undertime_minutes']);
    }

    public function controllerProvider(): array
    {
        return [
            'employee dashboard' => [MasterController::class],
            'tardiness module' => [TirednessController::class],
        ];
    }

    private function invoke(object $target, string $method, ...$arguments)
    {
        $reflection = new ReflectionMethod($target, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($target, ...$arguments);
    }
}
