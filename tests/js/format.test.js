import { describe, expect, it } from 'vitest';
import { blockRange, formatKwh, formatMoney } from '../../resources/js/lib/format';

describe('format', () => {
    it('formats money with two decimals and thousands separators', () => {
        expect(formatMoney(189.3)).toBe('189.30');
        expect(formatMoney(0)).toBe('0.00');
        expect(formatMoney(1234.5)).toBe('1,234.50');
        expect(formatMoney(null)).toBe('0.00');
    });

    it('formats kWh as whole numbers', () => {
        expect(formatKwh(210)).toBe('210');
        expect(formatKwh(12345)).toBe('12,345');
    });

    it('describes tariff block ranges', () => {
        expect(blockRange({ from: 1, to: 200 })).toBe('1–200 kWh');
        expect(blockRange({ from: 201, to: null })).toBe('Above 200 kWh');
    });
});
