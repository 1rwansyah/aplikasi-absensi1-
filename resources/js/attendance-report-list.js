/**
 * Plain textarea attendance report → HTML bullet list (no rich editor).
 */

export function escapeHtml(text) {
    return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

/**
 * Split textarea content into trimmed non-empty lines.
 */
export function plainTextLinesFromTextarea(value) {
    return (value || '')
        .split(/\r?\n/)
        .map((line) => line.replace(/^[-•*]\s*/, '').trim())
        .filter(Boolean);
}

/**
 * Convert multiline plain text to an HTML unordered list.
 */
export function convertReportTextareaToHtml(value) {
    const lines = plainTextLinesFromTextarea(value);

    if (lines.length === 0) {
        return '';
    }

    const items = lines.map((line) => `<li>${escapeHtml(line)}</li>`).join('');

    return `<ul>${items}</ul>`;
}

export function getReportFieldName(form) {
    return form.dataset.type === 'clock-in' ? 'clock_in_report' : 'clock_out_report';
}

export function getReportTextarea(form) {
    if (form.dataset.dualAction === '1') {
        return form.querySelector('[data-attendance-report]');
    }

    const reportName = getReportFieldName(form);

    return form.querySelector(`[name="${reportName}"]`);
}

export function getPlainReportFromForm(form) {
    return getReportTextarea(form)?.value?.trim() || '';
}

/**
 * Read textarea, return HTML list for backend payload.
 */
export function getHtmlReportFromForm(form) {
    return convertReportTextareaToHtml(getPlainReportFromForm(form));
}

if (typeof window !== 'undefined') {
    window.convertReportTextareaToHtml = convertReportTextareaToHtml;
    window.getPlainReportFromForm = getPlainReportFromForm;
    window.getHtmlReportFromForm = getHtmlReportFromForm;
}
