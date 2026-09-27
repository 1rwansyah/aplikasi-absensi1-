function reportFieldName(form) {
    return form.dataset.type === 'clock-in' ? 'clock_in_report' : 'clock_out_report';
}

function reportTextarea(form) {
    if (form.dataset.dualAction === '1') {
        return form.querySelector('[data-attendance-ckeditor] textarea, textarea[data-attendance-report]');
    }

    return form.querySelector(`[name="${reportFieldName(form)}"]`);
}

function plainTextLength(html) {
    const div = document.createElement('div');
    div.innerHTML = html;

    return (div.textContent || '').replace(/\s+/g, ' ').trim().length;
}

export function getAttendanceReportHtml(form) {
    if (
        typeof window.getAttendanceReportHtml === 'function'
        && window.getAttendanceReportHtml !== getAttendanceReportHtml
    ) {
        return window.getAttendanceReportHtml(form);
    }

    return reportTextarea(form)?.value?.trim() || '';
}

export function getAttendanceReportPlainLength(form) {
    return plainTextLength(getAttendanceReportHtml(form));
}
