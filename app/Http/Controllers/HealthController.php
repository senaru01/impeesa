<?php

namespace App\Http\Controllers;

use App\Models\Run;
use App\Models\WeightLog;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HealthController extends Controller
{
    /**
     * Ukuran area grafik berat badan (satuan viewBox SVG).
     */
    private const CHART_WIDTH = 600;

    private const CHART_HEIGHT = 160;

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $weights = $user->weightLogs()->latest('recorded_on')->latest('id')->get();
        $runs = $user->runs()->latest('run_on')->latest('id')->get();
        $lastHeight = $weights->firstWhere('height_cm', '!=', null)?->height_cm;

        return view('member.health.index', [
            'summary' => $user->healthSummary(),
            'weights' => $weights,
            'runs' => $runs,
            'weightChart' => $this->weightChart($weights->take(12)->reverse()->values()),
            'weeklyRuns' => $this->weeklyRuns($runs, CarbonImmutable::today()),
            'lastHeight' => $lastHeight,
        ]);
    }

    /**
     * Titik grafik garis untuk catatan berat badan terakhir, urut dari yang terlama.
     *
     * @param  EloquentCollection<int, WeightLog>  $weights
     * @return array{points: list<array{x: float, y: float, weight: float, label: string}>, min: float, max: float}|null
     */
    private function weightChart(EloquentCollection $weights): ?array
    {
        if ($weights->count() < 2) {
            return null;
        }

        $min = floor($weights->min('weight_kg') - 1);
        $max = ceil($weights->max('weight_kg') + 1);
        $step = self::CHART_WIDTH / ($weights->count() - 1);

        return [
            'points' => $weights->map(fn (WeightLog $log, int $index) => [
                'x' => round($index * $step, 1),
                'y' => round(self::CHART_HEIGHT - ($log->weight_kg - $min) / ($max - $min) * self::CHART_HEIGHT, 1),
                'weight' => $log->weight_kg,
                'label' => $log->recorded_on->translatedFormat('d M'),
            ])->all(),
            'min' => $min,
            'max' => $max,
        ];
    }

    /**
     * Total jarak lari per pekan selama tujuh pekan terakhir.
     *
     * @param  EloquentCollection<int, Run>  $runs
     * @return list<array{label: string, distance: float, current: bool}>
     */
    private function weeklyRuns(EloquentCollection $runs, CarbonImmutable $today): array
    {
        return collect(range(6, 0))
            ->map(function (int $weeksAgo) use ($runs, $today) {
                $start = $today->startOfWeek()->subWeeks($weeksAgo);
                $end = $start->endOfWeek();

                return [
                    'label' => $start->translatedFormat('d/m'),
                    'distance' => round($runs->filter(fn (Run $run) => $run->run_on->betweenIncluded($start, $end))->sum('distance_km'), 1),
                    'current' => $weeksAgo === 0,
                ];
            })
            ->all();
    }
}
