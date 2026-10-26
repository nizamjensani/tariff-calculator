<?php

namespace App\Services;

use App\Enums\BuildingType;
use InvalidArgumentException;

/**
 * Calculates electricity bills using block (tiered) tariffs.
 *
 * All money is handled internally in sen (1 RM = 100 sen) as integers,
 * so 210 kWh x 89 sen is always exactly correct.
 */
class TariffCalculator
{
    /**
     * @param  array<string, list<array{up_to: int|null, rate_sen: int}>>  $tariffs
     */
    public function __construct(private readonly array $tariffs) {}

    /**
     * Total bill in sen.
     */
    public function billInSen(BuildingType $type, int $kwh): int
    {
        return array_sum(array_column($this->breakdown($type, $kwh), 'amount_sen'));
    }

    /**
     * Total bill in RM, rounded to 2 decimal places.
     */
    public function bill(BuildingType $type, int $kwh): float
    {
        return round($this->billInSen($type, $kwh) / 100, 2);
    }

    /**
     * How the usage is split across the tariff blocks.
     *
     * @return list<array{from: int, to: int|null, kwh: int, rate_sen: int, amount_sen: int}>
     */
    public function breakdown(BuildingType $type, int $kwh): array
    {
        if ($kwh < 0) {
            throw new InvalidArgumentException('Usage cannot be negative.');
        }

        $blocks = $this->blocksFor($type);
        $remaining = $kwh;
        $lower = 0;
        $lines = [];

        foreach ($blocks as $block) {
            $size = $block['up_to'] === null ? PHP_INT_MAX : $block['up_to'] - $lower;
            $used = min($remaining, $size);

            $lines[] = [
                'from' => $lower + 1,
                'to' => $block['up_to'],
                'kwh' => $used,
                'rate_sen' => $block['rate_sen'],
                'amount_sen' => $used * $block['rate_sen'],
            ];

            $remaining -= $used;
            $lower = $block['up_to'] ?? $lower;

            if ($block['up_to'] === null) {
                break;
            }
        }

        return $lines;
    }

    /**
     * @return list<array{up_to: int|null, rate_sen: int}>
     */
    public function blocksFor(BuildingType $type): array
    {
        $blocks = $this->tariffs[$type->value] ?? null;

        if (! is_array($blocks) || $blocks === []) {
            throw new InvalidArgumentException("No tariff configured for [{$type->value}].");
        }

        if (end($blocks)['up_to'] !== null) {
            throw new InvalidArgumentException("The last tariff block for [{$type->value}] must be open-ended.");
        }

        return array_values($blocks);
    }

    /**
     * Tariff table for display.
     *
     * @return list<array{building_type: string, label: string, blocks: list<array{from: int, to: int|null, rate: float}>}>
     */
    public function table(): array
    {
        return array_map(function (BuildingType $type) {
            $lower = 0;
            $blocks = [];

            foreach ($this->blocksFor($type) as $block) {
                $blocks[] = ['from' => $lower + 1, 'to' => $block['up_to'], 'rate' => $block['rate_sen'] / 100];
                $lower = $block['up_to'] ?? $lower;
            }

            return ['building_type' => $type->value, 'label' => $type->label(), 'blocks' => $blocks];
        }, [BuildingType::Residential, BuildingType::Commercial]);
    }
}
