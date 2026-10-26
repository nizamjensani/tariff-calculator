<?php

namespace Tests\Feature;

use App\Enums\BuildingType;
use App\Enums\Month;
use App\Enums\State;
use App\Models\ElectricityRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecordCrudTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return [
            'customer_name' => 'John',
            'building_type' => 'commercial',
            'month' => 6,
            'usability_kwh' => 210,
            'state' => 'Selangor',
            ...$overrides,
        ];
    }

    #[Test]
    public function the_home_page_redirects_to_the_records_list(): void
    {
        $this->get('/')->assertRedirect('/records');
    }

    #[Test]
    public function the_create_form_shows_every_option(): void
    {
        $this->get(route('records.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Records/Create')
                ->has('options.building_types', 2)
                ->has('options.months', 12)
                ->has('options.states', 16)
                ->has('tariffs', 2)
            );
    }

    // a) Save the data from the form into the database

    #[Test]
    public function it_saves_a_record_from_the_form(): void
    {
        $this->post(route('records.store'), $this->validPayload())
            ->assertRedirect(route('records.index'))
            ->assertSessionHas('success', 'Saved the record for JOHN.')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('electricity_records', 1);
        $this->assertDatabaseHas('electricity_records', [
            'customer_name' => 'JOHN',
            'building_type' => 'commercial',
            'month' => 6,
            'usability_kwh' => 210,
            'state' => 'Selangor',
        ]);
    }

    #[Test]
    public function customer_names_are_trimmed_and_upper_cased(): void
    {
        $this->post(route('records.store'), $this->validPayload(['customer_name' => '  mary   ann  ']));

        $this->assertSame('MARY ANN', ElectricityRecord::sole()->customer_name);
    }

    #[Test]
    public function it_accepts_zero_usage(): void
    {
        $this->post(route('records.store'), $this->validPayload(['usability_kwh' => 0]))
            ->assertSessionHasNoErrors();

        $this->assertSame(0, ElectricityRecord::sole()->usability_kwh);
    }

    /**
     * @return array<string, array{string, mixed}>
     */
    public static function invalidFields(): array
    {
        return [
            'missing customer name' => ['customer_name', ''],
            'customer name too long' => ['customer_name', str_repeat('A', 256)],
            'missing building type' => ['building_type', ''],
            'unknown building type' => ['building_type', 'industrial'],
            'missing month' => ['month', ''],
            'month zero' => ['month', 0],
            'month thirteen' => ['month', 13],
            'month as text' => ['month', 'June'],
            'missing usability' => ['usability_kwh', ''],
            'negative usability' => ['usability_kwh', -5],
            'decimal usability' => ['usability_kwh', 12.5],
            'text usability' => ['usability_kwh', 'abc'],
            'missing state' => ['state', ''],
            'unknown state' => ['state', 'Atlantis'],
        ];
    }

    #[Test]
    #[DataProvider('invalidFields')]
    public function it_rejects_invalid_input(string $field, mixed $value): void
    {
        $this->from(route('records.create'))
            ->post(route('records.store'), $this->validPayload([$field => $value]))
            ->assertRedirect(route('records.create'))
            ->assertSessionHasErrors($field);

        $this->assertDatabaseCount('electricity_records', 0);
    }

    #[Test]
    public function it_reports_every_missing_field_at_once(): void
    {
        $this->post(route('records.store'), [])
            ->assertSessionHasErrors(['customer_name', 'building_type', 'month', 'usability_kwh', 'state']);
    }

    // c) Update the record

    #[Test]
    public function the_edit_form_is_prefilled_with_the_record(): void
    {
        $record = ElectricityRecord::factory()->create([
            'customer_name' => 'AHMAD',
            'building_type' => BuildingType::Commercial,
            'month' => Month::October,
            'usability_kwh' => 100,
            'state' => State::Johor,
        ]);

        $this->get(route('records.edit', $record))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Records/Edit')
                ->where('record.id', $record->id)
                ->where('record.customer_name', 'AHMAD')
                ->where('record.building_type', 'commercial')
                ->where('record.month', 10)
                ->where('record.usability_kwh', 100)
                ->where('record.state', 'Johor')
                ->has('options')
                ->has('tariffs', 2)
            );
    }

    #[Test]
    public function it_updates_a_record(): void
    {
        $record = ElectricityRecord::factory()->commercial()->create(['customer_name' => 'AHMAD']);

        $this->put(route('records.update', $record), $this->validPayload([
            'customer_name' => 'Ahmad Faiz',
            'building_type' => 'residential',
            'month' => 1,
            'usability_kwh' => 321,
            'state' => 'Kedah',
        ]))
            ->assertRedirect(route('records.index'))
            ->assertSessionHas('success', 'Updated the record for AHMAD FAIZ.');

        $record->refresh();
        $this->assertSame('AHMAD FAIZ', $record->customer_name);
        $this->assertSame(BuildingType::Residential, $record->building_type);
        $this->assertSame(Month::January, $record->month);
        $this->assertSame(321, $record->usability_kwh);
        $this->assertSame(State::Kedah, $record->state);
    }

    #[Test]
    public function an_invalid_update_leaves_the_record_unchanged(): void
    {
        $record = ElectricityRecord::factory()->create(['usability_kwh' => 50]);

        $this->put(route('records.update', $record), $this->validPayload(['usability_kwh' => -1]))
            ->assertSessionHasErrors('usability_kwh');

        $this->assertSame(50, $record->fresh()->usability_kwh);
    }

    #[Test]
    public function editing_or_updating_a_missing_record_returns_404(): void
    {
        $this->get(route('records.edit', 999))->assertNotFound();
        $this->put(route('records.update', 999), $this->validPayload())->assertNotFound();
    }

    // b) Delete the record

    #[Test]
    public function it_deletes_a_record(): void
    {
        $keep = ElectricityRecord::factory()->create();
        $delete = ElectricityRecord::factory()->create(['customer_name' => 'MARY']);

        $this->from(route('records.index'))
            ->delete(route('records.destroy', $delete))
            ->assertRedirect(route('records.index'))
            ->assertSessionHas('success', 'Deleted the record for MARY.');

        $this->assertModelMissing($delete);
        $this->assertModelExists($keep);
    }

    #[Test]
    public function deleting_a_missing_record_returns_404(): void
    {
        $this->delete(route('records.destroy', 999))->assertNotFound();
    }

    #[Test]
    public function the_success_message_is_shared_with_the_next_page(): void
    {
        $this->post(route('records.store'), $this->validPayload());

        $this->get(route('records.index'))
            ->assertInertia(fn (Assert $page) => $page->where('flash.success', 'Saved the record for JOHN.'));
    }
}
