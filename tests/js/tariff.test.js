import { describe, expect, it } from 'vitest';
import { bill, billInSen, blocksFor, breakdown } from '../../resources/js/lib/tariff';

// Same shape the server sends (TariffCalculator::table()).
const tariffs = [
    {
        building_type: 'residential',
        label: 'Residential',
        blocks: [
            { from: 1, to: 200, rate: 0.45 },
            { from: 201, to: null, rate: 0.97 },
        ],
    },
    {
        building_type: 'commercial',
        label: 'Commercial',
        blocks: [
            { from: 1, to: 200, rate: 0.89 },
            { from: 201, to: null, rate: 1.13 },
        ],
    },
];

const residential = blocksFor(tariffs, 'residential');
const commercial = blocksFor(tariffs, 'commercial');

describe('tariff', () => {
    it.each([
        [residential, 0, 0],
        [residential, 150, 67.5],
        [residential, 200, 90],
        [residential, 201, 90.97],
        [residential, 1000, 866],
        [commercial, 100, 89],
        [commercial, 200, 178],
        [commercial, 210, 189.3],
        [commercial, 500, 517],
    ])('matches the server calculation (%#)', (blocks, kwh, expected) => {
        expect(bill(blocks, kwh)).toBe(expected);
    });

    it('works in whole sen, so there are no float errors', () => {
        expect(billInSen(residential, 203)).toBe(9291);
        expect(bill(residential, 203)).toBe(92.91);
    });

    it('splits usage across the blocks', () => {
        const lines = breakdown(commercial, 210);

        expect(lines.map((l) => l.kwh)).toEqual([200, 10]);
        expect(lines.map((l) => l.amountSen)).toEqual([17800, 1130]);
    });

    it('treats blank, negative and non-numeric usage as zero', () => {
        expect(bill(residential, '')).toBe(0);
        expect(bill(residential, -40)).toBe(0);
        expect(bill(residential, 'abc')).toBe(0);
    });

    it('accepts numeric strings from form inputs', () => {
        expect(bill(commercial, '210')).toBe(189.3);
    });

    it('returns no blocks for an unknown building type', () => {
        expect(blocksFor(tariffs, '')).toEqual([]);
        expect(bill(blocksFor(tariffs, 'industrial'), 100)).toBe(0);
    });
});
