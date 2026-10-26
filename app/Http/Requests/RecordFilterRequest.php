<?php

namespace App\Http\Requests;

use App\Enums\BuildingType;
use App\Enums\Month;
use App\Enums\State;
use App\Models\ElectricityRecord;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Query-string filters for the record list and the bill report.
 *
 * Unknown or tampered values are ignored (treated as "no filter") instead of
 * redirecting, so a bad bookmark or hand-edited URL still shows a page.
 */
class RecordFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * @return array{building_type: ?string, state: ?string, month: ?int, sort: ?string, direction: ?string}
     */
    public function filters(): array
    {
        $sort = $this->string('sort')->toString();
        $sort = in_array($sort, ElectricityRecord::SORTABLE, true) ? $sort : null;

        $direction = $this->string('direction')->toString();
        $direction = $sort === null ? null : ($direction === 'desc' ? 'desc' : 'asc');

        $month = filter_var($this->query('month'), FILTER_VALIDATE_INT);

        return [
            'building_type' => BuildingType::tryFrom($this->string('building_type')->toString())?->value,
            'state' => State::tryFrom($this->string('state')->toString())?->value,
            'month' => $month === false ? null : Month::tryFrom($month)?->value,
            'sort' => $sort,
            'direction' => $direction,
        ];
    }
}
