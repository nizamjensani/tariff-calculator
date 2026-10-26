<?php

namespace App\Enums;

/**
 * The 13 states and 3 federal territories of Malaysia.
 */
enum State: string
{
    case Johor = 'Johor';
    case Kedah = 'Kedah';
    case Kelantan = 'Kelantan';
    case Melaka = 'Melaka';
    case NegeriSembilan = 'Negeri Sembilan';
    case Pahang = 'Pahang';
    case PulauPinang = 'Pulau Pinang';
    case Perak = 'Perak';
    case Perlis = 'Perlis';
    case Sabah = 'Sabah';
    case Sarawak = 'Sarawak';
    case Selangor = 'Selangor';
    case Terengganu = 'Terengganu';
    case KualaLumpur = 'W.P. Kuala Lumpur';
    case Labuan = 'W.P. Labuan';
    case Putrajaya = 'W.P. Putrajaya';

    public function label(): string
    {
        return $this->value;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $state) => ['value' => $state->value, 'label' => $state->label()],
            self::cases(),
        );
    }
}
