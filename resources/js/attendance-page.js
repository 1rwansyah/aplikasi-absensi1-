/**
 * Employee attendance page helpers.
 */

export function initAttendanceRealtimeClock() {
    const clockEl = document.getElementById('digital-clock');
    const dateEl = document.getElementById('digital-date');
    const timezone = document.querySelector('[data-app-timezone]')?.dataset.appTimezone || 'Asia/Jakarta';

    if (!clockEl || !dateEl) {
        return;
    }

    const localeOpts = { timeZone: timezone };

    const tick = () => {
        const now = new Date();
        clockEl.textContent = now.toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false,
            ...localeOpts,
        });
        dateEl.textContent = now.toLocaleDateString('id-ID', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            ...localeOpts,
        });
    };

    tick();
    window.setInterval(tick, 1000);
}

export function initLeaveDoctorNoteToggle() {
    // Note is always required now, so no toggle is needed.
}

export function initAttendancePage() {
    initAttendanceRealtimeClock();
    initLeaveDoctorNoteToggle();
}
