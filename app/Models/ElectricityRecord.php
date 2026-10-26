<?php

namespace App\Models;

use App\Enums\BuildingType;
use App\Enums\Month;
use App\Enums\State;
use App\Services\TariffCalculator;
use Database\Factories\ElectricityRecordFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $customer_name
 * @property BuildingType $building_type
 * @property Month $month
 * @property int $usability_kwh
 * @property State $state
 * @property-read float $bill
 */
class ElectricityRecord extends Model
{
    /** @use HasFactory<ElectricityRecordFactory> */
    use HasFactory;

    /** Columns the list can be sorted by. */
    public const SORTABLE = ['building_type', 'usability_kwh'];

    protected $fillable = [
        'customer_name',
        'building_type',
        'month',
        'usability_kwh',
        'state',
    ];

    protected function casts(): array
    {
        return [
            'building_type' => BuildingType::class,
            'month' => Month::class,
            'usability_kwh' => 'integer',
            'state' => State::class,
        ];
    }

    /**
     * Customer names are stored trimmed and in upper case, as on the report.
     */
    protected function customerName(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => mb_strtoupper(trim(preg_replace('/\s+/', ' ', $value))),
        );
    }

    /**
     * Bill in RM, calculated from the configured tariff.
     */
    protected function bill(): Attribute
    {
        return Attribute::make(
            get: fn () => app(TariffCalculator::class)->bill($this->building_type, $this->usability_kwh),
        );
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOfBuildingType(Builder $query, ?string $type): void
    {
        $query->when($type, fn (Builder $q) => $q->where('building_type', $type));
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeInState(Builder $query, ?string $state): void
    {
        $query->when($state, fn (Builder $q) => $q->where('state', $state));
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeForMonth(Builder $query, int|string|null $month): void
    {
        $query->when($month, fn (Builder $q) => $q->where('month', (int) $month));
    }

    /**
     * Sort by an allowed column; falls back to newest first.
     *
     * @param  Builder<self>  $query
     */
    public function scopeSorted(Builder $query, ?string $column, ?string $direction): void
    {
        if (in_array($column, self::SORTABLE, true)) {
            $direction = $direction === 'desc' ? 'desc' : 'asc';
            $query->orderBy($column, $direction)->orderBy('id', $direction);

            return;
        }

        $query->latest('id');
    }

    /**
     * Shape sent to the Vue front end.
     *
     * @return array<string, mixed>
     */
    public function toListItem(): array
    {
        return [
            'id' => $this->id,
            'customer_name' => $this->customer_name,
            'building_type' => $this->building_type->value,
            'building_type_label' => $this->building_type->label(),
            'month' => $this->month->value,
            'month_label' => $this->month->label(),
            'usability_kwh' => $this->usability_kwh,
            'state' => $this->state->value,
            'bill' => $this->bill,
        ];
    }
}
