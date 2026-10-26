/**
 * Client-side mirror of App\Services\TariffCalculator, used for the live
 * estimate on the form. The server remains the source of truth.
 *
 * `blocks` comes from the server as [{ from, to, rate }], rate in RM/kWh.
 */
export function breakdown(blocks, kwh) {
    const usage = Number.isFinite(Number(kwh)) ? Math.max(0, Math.floor(Number(kwh))) : 0;
    let remaining = usage;

    return blocks.map((block) => {
        const size = block.to === null ? Infinity : block.to - block.from + 1;
        const used = Math.min(remaining, size);
        const rateSen = Math.round(block.rate * 100);
        remaining -= used;

        return { ...block, kwh: used, amountSen: used * rateSen };
    });
}

export function billInSen(blocks, kwh) {
    return breakdown(blocks, kwh).reduce((sum, line) => sum + line.amountSen, 0);
}

export function bill(blocks, kwh) {
    return billInSen(blocks, kwh) / 100;
}

export function blocksFor(tariffs, buildingType) {
    return tariffs.find((t) => t.building_type === buildingType)?.blocks ?? [];
}
