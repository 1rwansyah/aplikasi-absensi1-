/**
 * Face match percentage helpers (shared client + admin detail).
 * Maps face-api euclidean distance to a human-readable similarity %.
 */

const ANCHORS = [
    [0.0, 100],
    [0.2, 98],
    [0.3, 92],
    [0.4, 85],
    [0.5, 75],
    [0.65, 55],
    [0.8, 35],
    [1.0, 10],
];

export function distanceToMatchPercent(distance) {
    if (distance == null || Number.isNaN(Number(distance))) {
        return null;
    }

    const value = Math.max(0, Number(distance));

    if (value <= ANCHORS[0][0]) {
        return ANCHORS[0][1];
    }

    const last = ANCHORS[ANCHORS.length - 1];
    if (value >= last[0]) {
        return Math.max(0, last[1]);
    }

    for (let i = 0; i < ANCHORS.length - 1; i++) {
        const [loDist, loPct] = ANCHORS[i];
        const [hiDist, hiPct] = ANCHORS[i + 1];
        if (value <= hiDist) {
            const t = (value - loDist) / (hiDist - loDist);
            return Math.max(0, Math.min(100, Math.round(loPct + t * (hiPct - loPct))));
        }
    }

    return 0;
}

export function matchPercentBadgeClasses(percent) {
    if (percent == null) {
        return 'inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300';
    }
    if (percent >= 90) {
        return 'inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800 dark:bg-green-900/50 dark:text-green-200';
    }
    if (percent >= 75) {
        return 'inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-900/50 dark:text-amber-200';
    }
    return 'inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800 dark:bg-red-900/50 dark:text-red-200';
}

export function formatMatchPercent(percent) {
    if (percent == null) {
        return '—';
    }
    return `${percent}% cocok`;
}

export function meetsMinMatchPercent(percent, minPercent = 74) {
    return percent != null && percent >= minPercent;
}

export function faceMatchRowHtml(label, distance, percent) {
    const badgeClass = matchPercentBadgeClasses(percent);
    const text = percent != null ? formatMatchPercent(percent) : '—';
    const distNote = distance != null
        ? `<span class="text-[11px] text-gray-400 dark:text-gray-500">(jarak ${Number(distance).toFixed(4)})</span>`
        : '';

    return `
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-gray-600 dark:text-gray-300">${label}:</span>
            <span class="${badgeClass}">${text}</span>
            ${distNote}
        </div>
    `;
}
