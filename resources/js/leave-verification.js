import { UiModal } from './ui-modal.js';

const LeaveVerificationModule = {
    targetLeaveId: null,
    targetAction: null,
    initialized: false,

    init() {
        if (this.initialized) {
            return;
        }

        if (!document.getElementById('leave-verify-confirm-modal')) {
            return;
        }

        this.initialized = true;
        UiModal.bindCloseTriggers('leave-verify-confirm-modal');
    },

    openApprove(leaveId, employeeName, date) {
        this.init();

        this.targetLeaveId = String(leaveId);
        this.targetAction = 'approve';

        this.renderModal({
            title: 'Setujui Pengajuan?',
            message: 'Notifikasi akan dikirim ke WhatsApp setelah disetujui.',
            employeeName,
            date,
            confirmText: 'Ya, Setujui',
            tone: 'success',
            requireReason: false,
        });

        UiModal.open('leave-verify-confirm-modal');
    },

    openReject(leaveId, employeeName, date, leaveType) {
        this.init();

        this.targetLeaveId = String(leaveId);
        this.targetAction = 'reject';

        const isSick = leaveType === 'sick';
        const message = isSick
            ? 'Pengajuan sakit akan ditolak. Karyawan tercatat sakit (ditolak), tidak mendapat uang makan, dan dapat mengajukan ulang.'
            : 'Pengajuan akan ditolak. Karyawan tercatat alfa dan dapat mengajukan ulang.';

        this.renderModal({
            title: 'Tolak Pengajuan?',
            message,
            employeeName,
            date,
            confirmText: 'Ya, Tolak',
            tone: 'danger',
            requireReason: true,
        });

        UiModal.open('leave-verify-confirm-modal');
        document.getElementById('leave-verify-rejection-reason')?.focus();
    },

    renderModal({ title, message, employeeName, date, confirmText, tone, requireReason }) {
        const titleEl = document.getElementById('leave-verify-modal-title');
        const messageEl = document.getElementById('leave-verify-modal-message');
        const employeeEl = document.getElementById('leave-verify-modal-employee');
        const dateEl = document.getElementById('leave-verify-modal-date');
        const confirmBtn = document.getElementById('leave-verify-confirm-btn');
        const iconWrap = document.getElementById('leave-verify-modal-icon-wrap');
        const icon = document.getElementById('leave-verify-modal-icon');
        const reasonWrap = document.getElementById('leave-verify-reject-fields');
        const reasonInput = document.getElementById('leave-verify-rejection-reason');
        const reasonError = document.getElementById('leave-verify-rejection-error');

        if (titleEl) titleEl.textContent = title;
        if (messageEl) messageEl.textContent = message;
        if (employeeEl) employeeEl.textContent = employeeName;
        if (dateEl) dateEl.textContent = date;

        if (reasonWrap && reasonInput) {
            reasonWrap.classList.toggle('hidden', !requireReason);
            reasonInput.value = '';
            if (reasonError) {
                reasonError.textContent = '';
                reasonError.classList.add('hidden');
            }
        }

        if (confirmBtn) {
            confirmBtn.textContent = confirmText;
            confirmBtn.className = tone === 'success'
                ? 'inline-flex min-h-11 w-full items-center justify-center gap-2 font-medium transition shadow-sm px-4 py-2 text-sm rounded-xl bg-green-600 text-white hover:bg-green-700 sm:w-auto'
                : 'inline-flex min-h-11 w-full items-center justify-center gap-2 font-medium transition shadow-sm px-4 py-2 text-sm rounded-xl bg-red-600 text-white hover:bg-red-700 sm:w-auto';
        }

        if (iconWrap && icon) {
            if (tone === 'success') {
                iconWrap.className = 'mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-green-50 dark:bg-green-900/30';
                icon.setAttribute('class', 'h-7 w-7 text-green-600 dark:text-green-400');
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />';
            } else {
                iconWrap.className = 'mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-red-50 dark:bg-red-900/30';
                icon.setAttribute('class', 'h-7 w-7 text-red-500 dark:text-red-400');
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />';
            }
        }
    },

    submit() {
        if (!this.targetLeaveId || !this.targetAction) {
            return;
        }

        const form = document.getElementById(`${this.targetAction}-form-${this.targetLeaveId}`);
        if (!form) {
            return;
        }

        if (this.targetAction === 'reject') {
            const reasonInput = document.getElementById('leave-verify-rejection-reason');
            const reasonError = document.getElementById('leave-verify-rejection-error');
            const reason = (reasonInput?.value || '').trim();

            if (reason.length < 10) {
                if (reasonError) {
                    reasonError.textContent = 'Alasan penolakan minimal 10 karakter.';
                    reasonError.classList.remove('hidden');
                }
                reasonInput?.focus();
                return;
            }

            let hiddenReason = form.querySelector('input[name="rejection_reason"]');
            if (!hiddenReason) {
                hiddenReason = document.createElement('input');
                hiddenReason.type = 'hidden';
                hiddenReason.name = 'rejection_reason';
                form.appendChild(hiddenReason);
            }
            hiddenReason.value = reason;
        }

        UiModal.close('leave-verify-confirm-modal');
        form.submit();

        this.targetLeaveId = null;
        this.targetAction = null;
    },
};

window.LeaveVerificationModule = LeaveVerificationModule;

export default LeaveVerificationModule;
