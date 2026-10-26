<?php

namespace Tests\Unit;

use App\Enums\BuildingType;
use App\Enums\Month;
use App\Enums\State;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class EnumsTest extends TestCase
{
    #[Test]
    public function building_types_are_commercial_and_residential(): void
    {
        $this->assertSame([
            ['value' => 'commercial', 'label' => 'Commercial'],
            ['value' => 'residential', 'label' => 'Residential'],
        ], BuildingType::options());
    }

    #[Test]
    public function months_run_from_january_to_december(): void
    {
        $options = Month::options();

        $this->assertCount(12, $options);
        $this->assertSame(['value' => 1, 'label' => 'January'], $options[0]);
        $this->assertSame(['value' => 12, 'label' => 'December'], $options[11]);
    }

    #[Test]
    public function states_cover_all_malaysian_states_and_federal_territories(): void
    {
        $values = array_column(State::options(), 'value');

        $this->assertCount(16, $values);
        $this->assertContains('Selangor', $values);
        $this->assertContains('W.P. Kuala Lumpur', $values);
        $this->assertContains('Sabah', $values);
        $this->assertSame($values, array_unique($values));
    }
}
