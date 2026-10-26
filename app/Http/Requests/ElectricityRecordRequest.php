<?php

namespace App\Http\Requests;

use App\Enums\BuildingType;
use App\Enums\Month;
use App\Enums\State;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ElectricityRecordRequest extends FormRequest
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
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'building_type' => ['required', Rule::enum(BuildingType::class)],
            'month' => ['required', 'integer', Rule::enum(Month::class)],
            'usability_kwh' => ['required', 'integer', 'min:0', 'max:10000000'],
            'state' => ['required', Rule::enum(State::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'customer_name' => 'customer name',
            'building_type' => 'building type',
            'usability_kwh' => 'usability (kWh)',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'building_type.required' => 'Choose Commercial or Residential.',
            'month.required' => 'Select a month.',
            'state.required' => 'Select a state.',
        ];
    }
}
