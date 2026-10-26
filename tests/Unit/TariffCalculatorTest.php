<?php

namespace Tests\Unit;

use App\Enums\BuildingType;
use App\Services\TariffCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TariffCalculatorTest extends TestCase
{
    private TariffCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new TariffCalculator(require __DIR__.'/../../config/tariff.php');
    }

    /**
     * @return array<string, array{BuildingType, int, float}>
     */
    public static function bills(): array
    {
        return [
            // Residential: first 200 kWh @ 0.45, above @ 0.97
            'residential zero usage' => [BuildingType::Residential, 0, 0.00],
            'residential 1 kWh' => [BuildingType::Residential, 1, 0.45],
            'residential 150 kWh' => [BuildingType::Residential, 150, 67.50],
            'residential exactly 200 kWh' => [BuildingType::Residential, 200, 90.00],
            'residential 201 kWh' => [BuildingType::Residential, 201, 90.97],
            'residential 300 kWh' => [BuildingType::Residential, 300, 187.00],
            'residential 1000 kWh' => [BuildingType::Residential, 1000, 866.00],

            // Commercial: first 200 kWh @ 0.89, above @ 1.13
            'commercial zero usage' => [BuildingType::Commercial, 0, 0.00],
            'commercial 100 kWh' => [BuildingType::Commercial, 100, 89.00],
            'commercial exactly 200 kWh' => [BuildingType::Commercial, 200, 178.00],
            'commercial 201 kWh' => [BuildingType::Commercial, 201, 179.13],
            'commercial 210 kWh' => [BuildingType::Commercial, 210, 189.30],
            'commercial 500 kWh' => [BuildingType::Commercial, 500, 517.00],
        ];
    }

    #[Test]
    #[DataProvider('bills')]
    public function it_calculates_the_bill(BuildingType $type, int $kwh, float $expected): void
    {
        $this->assertSame($expected, $this->calculator->bill($type, $kwh));
    }

    #[Test]
    public function it_calculates_in_sen_without_rounding_errors(): void
    {
        // 0.1 + 0.2 style float errors must not appear: 3 kWh * 97 sen above the first block.
        $this->assertSame(9291, $this->calculator->billInSen(BuildingType::Residential, 203));
        $this->assertSame(92.91, $this->calculator->bill(BuildingType::Residential, 203));
    }

    #[Test]
    public function it_breaks_usage_down_by_block(): void
    {
        $lines = $this->calculator->breakdown(BuildingType::Commercial, 210);

        $this->assertSame([
            ['from' => 1, 'to' => 200, 'kwh' => 200, 'rate_sen' => 89, 'amount_sen' => 17800],
            ['from' => 201, 'to' => null, 'kwh' => 10, 'rate_sen' => 113, 'amount_sen' => 1130],
        ], $lines);
    }

    #[Test]
    public function usage_within_the_first_block_charges_nothing_at_the_higher_rate(): void
    {
        $lines = $this->calculator->breakdown(BuildingType::Residential, 150);

        $this->assertSame(150, $lines[0]['kwh']);
        $this->assertSame(0, $lines[1]['kwh']);
        $this->assertSame(0, $lines[1]['amount_sen']);
    }

    #[Test]
    public function it_rejects_negative_usage(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->bill(BuildingType::Residential, -1);
    }

    #[Test]
    public function it_rejects_a_missing_tariff(): void
    {
        $calculator = new TariffCalculator(['residential' => [['up_to' => null, 'rate_sen' => 45]]]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No tariff configured for [commercial].');

        $calculator->bill(BuildingType::Commercial, 10);
    }

    #[Test]
    public function the_last_block_must_be_open_ended(): void
    {
        $calculator = new TariffCalculator(['residential' => [['up_to' => 200, 'rate_sen' => 45]]]);

        $this->expectException(InvalidArgumentException::class);

        $calculator->bill(BuildingType::Residential, 300);
    }

    #[Test]
    public function it_supports_more_than_two_blocks(): void
    {
        $calculator = new TariffCalculator(['residential' => [
            ['up_to' => 100, 'rate_sen' => 10],
            ['up_to' => 300, 'rate_sen' => 20],
            ['up_to' => null, 'rate_sen' => 50],
        ]]);

        // 100*10 + 200*20 + 50*50 = 1000 + 4000 + 2500
        $this->assertSame(7500, $calculator->billInSen(BuildingType::Residential, 350));
    }

    #[Test]
    public function it_describes_the_tariff_table(): void
    {
        $table = $this->calculator->table();

        $this->assertSame([
            [
                'building_type' => 'residential',
                'label' => 'Residential',
                'blocks' => [
                    ['from' => 1, 'to' => 200, 'rate' => 0.45],
                    ['from' => 201, 'to' => null, 'rate' => 0.97],
                ],
            ],
            [
                'building_type' => 'commercial',
                'label' => 'Commercial',
                'blocks' => [
                    ['from' => 1, 'to' => 200, 'rate' => 0.89],
                    ['from' => 201, 'to' => null, 'rate' => 1.13],
                ],
            ],
        ], $table);
    }
}
