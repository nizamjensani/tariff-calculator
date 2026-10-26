<?php

namespace App\Http\Controllers;

use App\Enums\BuildingType;
use App\Enums\Month;
use App\Enums\State;
use App\Http\Requests\ElectricityRecordRequest;
use App\Http\Requests\RecordFilterRequest;
use App\Models\ElectricityRecord;
use App\Services\TariffCalculator;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ElectricityRecordController extends Controller
{
    /**
     * List records with search criteria (building type, state) and sorting
     * (building type / usability, ascending or descending).
     */
    public function index(RecordFilterRequest $request): Response
    {
        $filters = $request->filters();

        $records = ElectricityRecord::query()
            ->ofBuildingType($filters['building_type'])
            ->inState($filters['state'])
            ->sorted($filters['sort'], $filters['direction'])
            ->paginate(15)
            ->withQueryString()
            ->through(fn (ElectricityRecord $record) => $record->toListItem());

        return Inertia::render('Records/Index', [
            'records' => $records,
            'filters' => $filters,
            'options' => $this->options(),
        ]);
    }

    public function create(TariffCalculator $calculator): Response
    {
        return Inertia::render('Records/Create', [
            'options' => $this->options(),
            'tariffs' => $calculator->table(),
        ]);
    }

    public function store(ElectricityRecordRequest $request): RedirectResponse
    {
        $record = ElectricityRecord::create($request->validated());

        return to_route('records.index')
            ->with('success', "Saved the record for {$record->customer_name}.");
    }

    public function edit(ElectricityRecord $record, TariffCalculator $calculator): Response
    {
        return Inertia::render('Records/Edit', [
            'record' => $record->toListItem(),
            'options' => $this->options(),
            'tariffs' => $calculator->table(),
        ]);
    }

    public function update(ElectricityRecordRequest $request, ElectricityRecord $record): RedirectResponse
    {
        $record->update($request->validated());

        return to_route('records.index')
            ->with('success', "Updated the record for {$record->customer_name}.");
    }

    public function destroy(ElectricityRecord $record): RedirectResponse
    {
        $record->delete();

        return back(fallback: route('records.index'))
            ->with('success', "Deleted the record for {$record->customer_name}.");
    }

    /**
     * @return array<string, list<array{value: int|string, label: string}>>
     */
    private function options(): array
    {
        return [
            'building_types' => BuildingType::options(),
            'months' => Month::options(),
            'states' => State::options(),
        ];
    }
}
