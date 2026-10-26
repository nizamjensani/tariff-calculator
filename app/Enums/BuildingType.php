<?php

namespace App\Enums;

enum BuildingType: string
{
    case Commercial = 'commercial';
    case Residential = 'residential';

    public function label(): string
    {
        return match ($this) {
            self::Commercial => 'Commercial',
            self::Residential => 'Residential',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $type) => ['value' => $type->value, 'label' => $type->label()],
            self::cases(),
        );
    }
}
