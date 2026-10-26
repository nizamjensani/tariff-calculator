<?php

namespace Tests\Feature;

use App\Enums\BuildingType;
use App\Enums\Month;
use App\Enums\State;
use App\Models\ElectricityRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * d) Display the data from the database, with search criteria and sorting.
 */
class RecordListTest extends TestCase
{
    use RefreshDatabase;

    private function make(string $name, BuildingType $type, int $kwh, State $state = State::Selangor): ElectricityRecord
    {
        return ElectricityRecord::factory()->create([
            'customer_name' => $name,
            'building_type' => $type,
            'usability_kwh' => $kwh,
            'state' => $state,
            'month' => Month::June,
        ]);
    }

    /**
     * @return list<string>
     */
    private function namesFor(string $uri): array
    {
        $names = [];

        $this->get($uri)
            ->assertOk()
            ->assertInertia(function (Assert $page) use (&$names) {
                $page->component('Records/Index');
                $names = array_column($page->toArray()['props']['records']['data'], 'customer_name');
            });

        return $names;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->make('ALI', BuildingType::Commercial, 300, State::Johor);
        $this->make('BEN', BuildingType::Residential, 50, State::Selangor);
        $this->make('CHONG', BuildingType::Commercial, 120, State::Selangor);
        $this->make('DEVI', BuildingType::Residential, 410, State::Perak);
    }

    #[Test]
    public function it_lists_every_record_with_its_bill(): void
    {
        $this->get('/records')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Records/Index')
                ->has('records.data', 4)
                ->where('records.total', 4)
                ->has('records.data.0', fn (Assert $row) => $row
                    ->where('customer_name', 'DEVI')
                    ->where('building_type', 'residential')
                    ->where('building_type_label', 'Residential')
                    ->where('month', 6)
                    ->where('month_label', 'June')
                    ->where('usability_kwh', 410)
                    ->where('state', 'Perak')
                    ->where('bill', 293.7) // 200*0.45 + 210*0.97
                    ->etc()
                )
                ->where('filters', [
                    'building_type' => null,
                    'state' => null,
                    'month' => null,
                    'sort' => null,
                    'direction' => null,
                ])
                ->has('options.building_types', 2)
                ->has('options.states', 16)
            );
    }

    #[Test]
    public function newest_records_come_first_by_default(): void
    {
        $this->assertSame(['DEVI', 'CHONG', 'BEN', 'ALI'], $this->namesFor('/records'));
    }

    #[Test]
    public function it_filters_by_building_type(): void
    {
        $this->assertEqualsCanonicalizing(['ALI', 'CHONG'], $this->namesFor('/records?building_type=commercial'));
        $this->assertEqualsCanonicalizing(['BEN', 'DEVI'], $this->namesFor('/records?building_type=residential'));
    }

    #[Test]
    public function it_filters_by_state(): void
    {
        $this->assertEqualsCanonicalizing(['BEN', 'CHONG'], $this->namesFor('/records?state=Selangor'));
        $this->assertSame(['ALI'], $this->namesFor('/records?state=Johor'));
        $this->assertSame([], $this->namesFor('/records?state=Sabah'));
    }

    #[Test]
    public function state_names_with_spaces_and_dots_filter_correctly(): void
    {
        $this->make('ELLA', BuildingType::Residential, 10, State::KualaLumpur);

        $this->assertSame(['ELLA'], $this->namesFor('/records?'.http_build_query(['state' => 'W.P. Kuala Lumpur'])));
    }

    #[Test]
    public function it_combines_building_type_and_state(): void
    {
        $this->assertSame(['CHONG'], $this->namesFor('/records?building_type=commercial&state=Selangor'));
    }

    #[Test]
    public function it_orders_by_building_type_ascending(): void
    {
        $names = $this->namesFor('/records?sort=building_type&direction=asc');

        // Commercial before Residential; ties broken by id.
        $this->assertSame(['ALI', 'CHONG', 'BEN', 'DEVI'], $names);
    }

    #[Test]
    public function it_orders_by_building_type_descending(): void
    {
        $this->assertSame(['DEVI', 'BEN', 'CHONG', 'ALI'], $this->namesFor('/records?sort=building_type&direction=desc'));
    }

    #[Test]
    public function it_orders_by_usability_ascending(): void
    {
        $this->assertSame(['BEN', 'CHONG', 'ALI', 'DEVI'], $this->namesFor('/records?sort=usability_kwh&direction=asc'));
    }

    #[Test]
    public function it_orders_by_usability_descending(): void
    {
        $this->assertSame(['DEVI', 'ALI', 'CHONG', 'BEN'], $this->namesFor('/records?sort=usability_kwh&direction=desc'));
    }

    #[Test]
    public function sort_direction_defaults_to_ascending(): void
    {
        $this->get('/records?sort=usability_kwh')
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.sort', 'usability_kwh')
                ->where('filters.direction', 'asc')
            );
    }

    #[Test]
    public function it_combines_a_filter_with_sorting(): void
    {
        $this->assertSame(['DEVI', 'BEN'], $this->namesFor('/records?building_type=residential&sort=usability_kwh&direction=desc'));
    }

    #[Test]
    public function the_active_filters_are_returned_to_the_page(): void
    {
        $this->get('/records?building_type=commercial&state=Johor&sort=usability_kwh&direction=desc')
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.building_type', 'commercial')
                ->where('filters.state', 'Johor')
                ->where('filters.sort', 'usability_kwh')
                ->where('filters.direction', 'desc')
            );
    }

    #[Test]
    public function unknown_filter_values_are_ignored(): void
    {
        $uri = '/records?building_type=industrial&state=Atlantis&sort=customer_name;drop&direction=sideways';

        $this->assertSame(['DEVI', 'CHONG', 'BEN', 'ALI'], $this->namesFor($uri));

        $this->get($uri)->assertInertia(fn (Assert $page) => $page
            ->where('filters.building_type', null)
            ->where('filters.state', null)
            ->where('filters.sort', null)
            ->where('filters.direction', null)
        );
    }

    #[Test]
    public function the_list_is_paginated_and_keeps_the_query_string(): void
    {
        ElectricityRecord::factory()->count(20)->commercial()->create();

        $this->get('/records?building_type=commercial')
            ->assertInertia(fn (Assert $page) => $page
                ->has('records.data', 15)
                ->where('records.total', 22)
                ->where('records.current_page', 1)
                ->where('records.next_page_url', fn ($url) => str_contains($url, 'building_type=commercial') && str_contains($url, 'page=2'))
            );

        $this->get('/records?building_type=commercial&page=2')
            ->assertInertia(fn (Assert $page) => $page->has('records.data', 7));
    }

    #[Test]
    public function an_empty_database_shows_an_empty_list(): void
    {
        ElectricityRecord::query()->delete();

        $this->get('/records')->assertInertia(fn (Assert $page) => $page
            ->has('records.data', 0)
            ->where('records.total', 0)
        );
    }
}
