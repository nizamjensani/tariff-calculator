const rm = new Intl.NumberFormat('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const whole = new Intl.NumberFormat('en-MY', { maximumFractionDigits: 0 });

/** 1234.5 -> "1,234.50" */
export function formatMoney(value) {
    return rm.format(Number(value) || 0);
}

/** 1234 -> "1,234" */
export function formatKwh(value) {
    return whole.format(Number(value) || 0);
}

/** A tariff block's range, e.g. "1–200 kWh" or "Above 200 kWh". */
export function blockRange(block) {
    return block.to === null ? `Above ${formatKwh(block.from - 1)} kWh` : `${formatKwh(block.from)}–${formatKwh(block.to)} kWh`;
}
