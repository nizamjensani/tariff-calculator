<?php

namespace Database\Seeders;

use App\Enums\BuildingType;
use App\Enums\Month;
use App\Enums\State;
use App\Models\ElectricityRecord;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Sample records matching the example report in the brief.
     */
    public function run(): void
    {
        $samples = [
            ['JOHN', BuildingType::Commercial, Month::June, 210, State::Selangor],
            ['AHMAD', BuildingType::Commercial, Month::October, 100, State::Johor],
            ['MARY', BuildingType::Residential, Month::January, 150, State::PulauPinang],
        ];

        foreach ($samples as [$name, $type, $month, $kwh, $state]) {
            ElectricityRecord::firstOrCreate(
                ['customer_name' => $name, 'month' => $month],
                ['building_type' => $type, 'usability_kwh' => $kwh, 'state' => $state],
            );
        }
    }
}
