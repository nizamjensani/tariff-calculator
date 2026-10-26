<?php

namespace Tests\Feature;

use App\Enums\BuildingType;
use App\Enums\Month;
use App\Models\ElectricityRecord;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * e) Simple report that calculates the bill.
 */
class BillReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // JOHN (commercial, June, 210), AHMAD (commercial, October, 100), MARY (residential, January, 150)
        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function it_shows_every_bill_with_totals(): void
    {
        $this->get('/report')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Report/Index')
                ->has('rows', 3)
                // Ordered by month: January, June, October
                ->where('rows.0.customer_name', 'MARY')
                ->where('rows.0.bill', 67.5)
                ->where('rows.1.customer_name', 'JOHN')
                ->where('rows.1.bill', 189.3)
                ->where('rows.2.customer_name', 'AHMAD')
                ->where('rows.2.bill', 89)
                ->where('totals.records', 3)
                ->where('totals.usability_kwh', 460)
                ->where('totals.bill', 345.8)
                ->has('tariffs', 2)
                ->has('options.months', 12)
                ->has('options.building_types', 2)
            );
    }

    #[Test]
    public function each_row_includes_the_tariff_breakdown(): void
    {
        $this->get('/report?month=6')
            ->assertInertia(fn (Assert $page) => $page
                ->has('rows', 1)
                ->where('rows.0.breakdown', [
                    ['from' => 1, 'to' => 200, 'kwh' => 200, 'rate' => 0.89, 'amount' => 178],
                    ['from' => 201, 'to' => null, 'kwh' => 10, 'rate' => 1.13, 'amount' => 11.3],
                ])
            );
    }

    #[Test]
    public function it_filters_by_month(): void
    {
        $this->get('/report?month=10')
            ->assertInertia(fn (Assert $page) => $page
                ->has('rows', 1)
                ->where('rows.0.customer_name', 'AHMAD')
                ->where('rows.0.month_label', 'October')
                ->where('filters.month', 10)
                ->where('totals.bill', 89)
            );
    }

    #[Test]
    public function it_filters_by_building_type(): void
    {
        $this->get('/report?building_type=commercial')
            ->assertInertia(fn (Assert $page) => $page
                ->has('rows', 2)
                ->where('rows.0.customer_name', 'JOHN')
                ->where('rows.1.customer_name', 'AHMAD')
                ->where('totals.bill', 278.3)
                ->where('filters.building_type', 'commercial')
            );
    }

    #[Test]
    public function it_filters_by_month_and_building_type_together(): void
    {
        ElectricityRecord::factory()->residential()->create([
            'customer_name' => 'SITI',
            'month' => Month::June,
            'usability_kwh' => 250, // 200*0.45 + 50*0.97 = 138.50
        ]);

        $this->get('/report?month=6&building_type=residential')
            ->assertInertia(fn (Assert $page) => $page
                ->has('rows', 1)
                ->where('rows.0.customer_name', 'SITI')
                ->where('rows.0.bill', 138.5)
                ->where('totals.bill', 138.5)
            );
    }

    #[Test]
    public function a_search_with_no_matches_returns_zero_totals(): void
    {
        $this->get('/report?month=3&building_type=residential')
            ->assertInertia(fn (Assert $page) => $page
                ->has('rows', 0)
                ->where('totals.records', 0)
                ->where('totals.usability_kwh', 0)
                ->where('totals.bill', 0)
            );
    }

    #[Test]
    public function invalid_report_filters_are_ignored(): void
    {
        $this->get('/report?month=13&building_type=factory')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('rows', 3)
                ->where('filters.month', null)
                ->where('filters.building_type', null)
            );
    }

    #[Test]
    public function the_report_reflects_updated_usage(): void
    {
        $john = ElectricityRecord::where('customer_name', 'JOHN')->sole();
        $john->update(['usability_kwh' => 200]);

        $this->get('/report?month=6')
            ->assertInertia(fn (Assert $page) => $page->where('rows.0.bill', 178));
    }

    #[Test]
    public function changing_the_tariff_config_changes_the_bills(): void
    {
        config(['tariff.commercial' => [
            ['up_to' => 100, 'rate_sen' => 50],
            ['up_to' => null, 'rate_sen' => 200],
        ]]);
        $this->app->forgetInstance(\App\Services\TariffCalculator::class);

        $this->get('/report?month=10&building_type=commercial')
            ->assertInertia(fn (Assert $page) => $page->where('rows.0.bill', 50));
    }

    #[Test]
    public function the_model_exposes_the_bill(): void
    {
        $record = ElectricityRecord::factory()->make([
            'building_type' => BuildingType::Residential,
            'usability_kwh' => 201,
        ]);

        $this->assertSame(90.97, $record->bill);
    }
}
