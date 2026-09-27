/**
 * WhatsApp attendance report confirmation modal (admin monitoring page).
 */

const MODAL_ID = 'whatsapp-report-modal';

function getModal() {
    return document.getElementById(MODAL_ID);
}

function getSelectedReportType() {
    const selected = document.querySelector('input[name="whatsapp-report-type"]:checked');
    return selected?.value ?? 'masuk';
}

function resetWhatsappReportFeedback() {
    const feedback = document.getElementById('whatsapp-report-feedback');
    if (!feedback) {
        return;
    }

    feedback.textContent = '';
    feedback.className = 'hidden rounded-xl px-4 py-3 text-sm';
}

function showWhatsappReportFeedback(message, type) {
    const feedback = document.getElementById('whatsapp-report-feedback');
    if (!feedback) {
        return;
    }

    const classes = type === 'success'
        ? 'rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300'
        : 'rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300';

    feedback.textContent = message;
    feedback.className = classes;
}

function setWhatsappReportSubmitting(isSubmitting) {
    const button = document.getElementById('whatsapp-report-submit-btn');
    if (!button) {
        return;
    }

    button.disabled = isSubmitting;
    button.textContent = isSubmitting ? 'Mengirim...' : 'Ya, Kirim';
}

function openWhatsappReportModal() {
    const modal = getModal();
    if (!modal) {
        return;
    }

    resetWhatsappReportFeedback();
    setWhatsappReportSubmitting(false);

    const masukRadio = document.querySelector('input[name="whatsapp-report-type"][value="masuk"]');
    if (masukRadio) {
        masukRadio.checked = true;
    }

    modal.style.display = 'flex';
    document.body.classList.add('overflow-hidden');

    requestAnimationFrame(() => {
        const backdrop = modal.querySelector('[data-backdrop]');
        const content = modal.querySelector('[data-modal-content]');
        if (backdrop) {
            backdrop.classList.remove('opacity-0');
        }
        if (content) {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }
    });
}

function closeWhatsappReportModal() {
    const modal = getModal();
    if (!modal) {
        return;
    }

    const backdrop = modal.querySelector('[data-backdrop]');
    const content = modal.querySelector('[data-modal-content]');

    if (backdrop) {
        backdrop.classList.add('opacity-0');
    }
    if (content) {
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
    }

    setTimeout(() => {
        modal.style.display = 'none';
        document.body.classList.remove('overflow-hidden');
        resetWhatsappReportFeedback();
        setWhatsappReportSubmitting(false);
    }, 300);
}

async function submitWhatsappReport() {
    const modal = getModal();
    if (!modal) {
        return;
    }

    const url = modal.dataset.sendUrl;
    const date = modal.dataset.reportDate;
    const type = getSelectedReportType();
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (!url || !date || !csrfToken) {
        showWhatsappReportFeedback('Konfigurasi pengiriman tidak lengkap.', 'error');
        return;
    }

    resetWhatsappReportFeedback();
    setWhatsappReportSubmitting(true);

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ date, type }),
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            const message = data.message
                || Object.values(data.errors ?? {}).flat().join(' ')
                || 'Gagal mengirim rekap absensi ke WhatsApp.';

            showWhatsappReportFeedback(message, 'error');
            return;
        }

        showWhatsappReportFeedback(data.message ?? 'Rekap absensi berhasil dikirim ke WhatsApp.', 'success');

        setTimeout(() => {
            closeWhatsappReportModal();
        }, 1500);
    } catch {
        showWhatsappReportFeedback('Gagal menghubungi server. Periksa koneksi internet Anda.', 'error');
    } finally {
        setWhatsappReportSubmitting(false);
    }
}

function bootAdminAttendanceWhatsappReport() {
    if (!getModal()) {
        return;
    }

    window.openWhatsappReportModal = openWhatsappReportModal;
    window.closeWhatsappReportModal = closeWhatsappReportModal;
    window.submitWhatsappReport = submitWhatsappReport;

    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') {
            return;
        }
        const modal = getModal();
        if (modal && modal.style.display === 'flex') {
            closeWhatsappReportModal();
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootAdminAttendanceWhatsappReport);
} else {
    bootAdminAttendanceWhatsappReport();
}

export { openWhatsappReportModal, closeWhatsappReportModal, submitWhatsappReport };
