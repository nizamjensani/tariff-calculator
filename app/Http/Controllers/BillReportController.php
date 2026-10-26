<?php

namespace App\Http\Controllers;

use App\Enums\BuildingType;
use App\Enums\Month;
use App\Http\Requests\RecordFilterRequest;
use App\Models\ElectricityRecord;
use App\Services\TariffCalculator;
use Inertia\Inertia;
use Inertia\Response;

class BillReportController extends Controller
{
    /**
     * Bill report filtered by month and building type.
     */
    public function __invoke(RecordFilterRequest $request, TariffCalculator $calculator): Response
    {
        $filters = $request->filters();

        $rows = ElectricityRecord::query()
            ->forMonth($filters['month'])
            ->ofBuildingType($filters['building_type'])
            ->orderBy('month')
            ->orderBy('customer_name')
            ->orderBy('id')
            ->get()
            ->map(function (ElectricityRecord $record) use ($calculator) {
                $breakdown = $calculator->breakdown($record->building_type, $record->usability_kwh);

                return [
                    ...$record->toListItem(),
                    'breakdown' => array_map(fn (array $line) => [
                        'from' => $line['from'],
                        'to' => $line['to'],
                        'kwh' => $line['kwh'],
                        'rate' => $line['rate_sen'] / 100,
                        'amount' => $line['amount_sen'] / 100,
                    ], $breakdown),
                ];
            });

        $totalSen = $rows->sum(fn (array $row) => (int) round($row['bill'] * 100));

        return Inertia::render('Report/Index', [
            'rows' => $rows,
            'totals' => [
                'records' => $rows->count(),
                'usability_kwh' => $rows->sum('usability_kwh'),
                'bill' => $totalSen / 100,
            ],
            'filters' => [
                'month' => $filters['month'],
                'building_type' => $filters['building_type'],
            ],
            'options' => [
                'months' => Month::options(),
                'building_types' => BuildingType::options(),
            ],
            'tariffs' => $calculator->table(),
        ]);
    }
}
