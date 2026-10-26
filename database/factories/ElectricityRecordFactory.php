<?php

namespace Database\Factories;

use App\Enums\BuildingType;
use App\Enums\Month;
use App\Enums\State;
use App\Models\ElectricityRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ElectricityRecord>
 */
class ElectricityRecordFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_name' => fake()->name(),
            'building_type' => fake()->randomElement(BuildingType::cases()),
            'month' => fake()->randomElement(Month::cases()),
            'usability_kwh' => fake()->numberBetween(1, 600),
            'state' => fake()->randomElement(State::cases()),
        ];
    }

    public function residential(): static
    {
        return $this->state(['building_type' => BuildingType::Residential]);
    }

    public function commercial(): static
    {
        return $this->state(['building_type' => BuildingType::Commercial]);
    }
}
