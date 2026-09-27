/**
 * Admin Manual Attendance Modals (create + edit)
 * Mirrors employee modal open/close pattern (employees.js).
 */

import { initAttendanceEditors, setEditorHtml } from './attendance-ckeditor.js';

const ManualAttendanceModule = {
    createModalId: 'manual-attendance-create-modal',
    editModalId: 'manual-attendance-edit-modal',
    schedulePreviewUrl: '/admin/attendance/schedule-preview',
    initialized: false,
    csrfToken: null,
    editAttendanceId: null,

    init() {
        if (this.initialized) {
            return;
        }

        if (!document.getElementById(this.createModalId) && !document.getElementById(this.editModalId)) {
            return;
        }

        this.initialized = true;
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        this.bindEvents();

        const root = document.querySelector('[data-open-manual-attendance-modal="true"], [data-open-manual-edit-attendance-modal="true"]');

        if (root?.dataset.openManualEditAttendanceModal === 'true') {
            this.openEditModalFromPrefill();
            return;
        }

        if (root?.dataset.openManualAttendanceModal === 'true') {
            this.openModal(this.createModalId);
            this.refreshSchedulePreview('create');
        }
    },

    bindEvents() {
        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-action]');
            if (!trigger) {
                return;
            }

            const action = trigger.dataset.action;

            if (action === 'open-manual-attendance-create') {
                event.preventDefault();
                this.openModal(this.createModalId);
                this.refreshSchedulePreview('create');
                return;
            }

            if (action === 'open-manual-attendance-edit') {
                event.preventDefault();
                const attendanceId = trigger.dataset.attendanceId;
                if (attendanceId) {
                    this.openEditModal(attendanceId);
                }
                return;
            }

            if (action === 'close-modal') {
                const modalId = trigger.dataset.modalId;
                if (modalId === this.createModalId || modalId === this.editModalId) {
                    event.preventDefault();
                    this.closeModal(modalId);
                }
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                this.closeModal(this.createModalId);
                this.closeModal(this.editModalId);
            }
        });

        document.addEventListener('change', (event) => {
            if (event.target.id === 'manual_create_clock_in_date') {
                this.refreshSchedulePreview('create');
                return;
            }

            if (event.target.id === 'manual_edit_clock_in_date') {
                this.refreshSchedulePreview('edit', { applyDefaults: false });
            }
        });
    },

    openEditModalFromPrefill() {
        const prefillNode = document.getElementById('manual-attendance-edit-prefill');
        if (!prefillNode) {
            return;
        }

        try {
            const data = JSON.parse(prefillNode.textContent || 'null');
            if (data) {
                this.showEditForm(data);
                this.openModal(this.editModalId);
            }
        } catch (error) {
            console.error('Failed to parse edit prefill data:', error);
        }
    },

    async openEditModal(attendanceId) {
        const loading = document.getElementById('manual-attendance-edit-loading');
        const form = document.getElementById('manual-attendance-edit-form');

        if (loading) {
            loading.classList.remove('hidden');
        }
        if (form) {
            form.classList.add('hidden');
        }

        this.openModal(this.editModalId);

        try {
            const response = await fetch(`/admin/attendance/${attendanceId}/edit`, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
            });

            if (!response.ok) {
                const payload = await response.json().catch(() => ({}));
                throw new Error(payload.message || 'Gagal memuat data absensi.');
            }

            const data = await response.json();
            this.showEditForm(data);
        } catch (error) {
            console.error(error);
            alert(error.message || 'Gagal memuat data absensi.');
            this.closeModal(this.editModalId);
        } finally {
            if (loading) {
                loading.classList.add('hidden');
            }
        }
    },

    showEditForm(data) {
        const form = document.getElementById('manual-attendance-edit-form');
        const subtitle = document.getElementById('manual-attendance-edit-subtitle');
        const loading = document.getElementById('manual-attendance-edit-loading');

        if (!form) {
            return;
        }

        this.editAttendanceId = data.id;

        form.action = data.update_url;
        form.classList.remove('hidden');
        if (loading) {
            loading.classList.add('hidden');
        }

        if (subtitle) {
            subtitle.textContent = `${data.employee_label} — koreksi absensi manual`;
        }

        this.setFieldValue(form, 'manual_edit_employee_display', data.employee_label);
        this.setFieldValue(form, 'type', data.type, 'manual_edit_type');
        this.setFieldValue(form, 'status', data.status, 'manual_edit_status');
        this.setFieldValue(form, 'clock_in_date', data.clock_in_date, 'manual_edit_clock_in_date');
        this.setFieldValue(form, 'clock_in_time', data.clock_in_time, 'manual_edit_clock_in_time');
        this.setFieldValue(form, 'clock_out_date', data.clock_out_date, 'manual_edit_clock_out_date');
        this.setFieldValue(form, 'clock_out_time', data.clock_out_time, 'manual_edit_clock_out_time');
        this.setEditorFieldValue('manual_edit_clock_in_report', data.clock_in_report);
        this.setEditorFieldValue('manual_edit_clock_out_report', data.clock_out_report);
        this.setEditorFieldValue('manual_edit_leave_note', data.leave_note ?? '');
        this.setFieldValue(form, 'manual_reason', '', 'manual_edit_manual_reason');

        const alpine = window.Alpine?.$data(form);
        if (alpine) {
            alpine.type = data.type;
            alpine.clockInDate = data.clock_in_date;
            alpine.clockOutDateTouched = data.clock_out_date !== data.clock_in_date;
        }

        this.refreshSchedulePreview('edit', { applyDefaults: false });
    },

    async refreshSchedulePreview(mode, options = {}) {
        const prefix = mode === 'edit' ? 'manual_edit' : 'manual_create';
        const formId = mode === 'edit' ? 'manual-attendance-edit-form' : 'manual-attendance-create-form';
        const form = document.getElementById(formId);
        const dateField = document.getElementById(`${prefix}_clock_in_date`);
        const date = dateField?.value;

        let userId = form?.querySelector('[name="user_id"]')?.value ?? '';

        if (mode === 'create') {
            const alpine = window.Alpine?.$data(form);
            userId = alpine?.selectedUserId ?? userId;
        }

        if (!userId || !date) {
            this.hideSchedulePanel(mode);
            return;
        }

        const params = new URLSearchParams({
            user_id: userId,
            date,
        });

        if (mode === 'edit' && this.editAttendanceId) {
            params.set('attendance_id', String(this.editAttendanceId));
        }

        try {
            const response = await fetch(`${this.schedulePreviewUrl}?${params.toString()}`, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
            });

            if (!response.ok) {
                this.hideSchedulePanel(mode);
                return;
            }

            const data = await response.json();
            this.renderSchedulePanel(mode, data);

            const applyDefaults = options.applyDefaults ?? mode === 'create';
            if (applyDefaults && !data.is_off) {
                this.applyScheduleDefaults(mode, data);
            }
        } catch (error) {
            console.error('Failed to load schedule preview:', error);
            this.hideSchedulePanel(mode);
        }
    },

    renderSchedulePanel(mode, data) {
        const prefix = mode === 'edit' ? 'manual_edit' : 'manual_create';
        const panel = document.getElementById(`${prefix}_schedule_panel`);
        const nameEl = document.getElementById(`${prefix}_schedule_name`);
        const shiftTypeEl = document.getElementById(`${prefix}_schedule_shift_type`);
        const workHoursEl = document.getElementById(`${prefix}_schedule_work_hours`);
        const offNoticeEl = document.getElementById(`${prefix}_schedule_off_notice`);

        if (!panel) {
            return;
        }

        panel.classList.remove('hidden');

        if (nameEl) {
            nameEl.textContent = data.schedule_name ?? '—';
        }

        if (shiftTypeEl) {
            shiftTypeEl.textContent = data.is_off ? 'Libur' : (data.shift_type ?? '—');
        }

        if (workHoursEl) {
            workHoursEl.textContent = data.is_off ? '—' : (data.work_hours ?? '—');
        }

        if (offNoticeEl) {
            offNoticeEl.classList.toggle('hidden', !data.is_off);
        }
    },

    hideSchedulePanel(mode) {
        const prefix = mode === 'edit' ? 'manual_edit' : 'manual_create';
        const panel = document.getElementById(`${prefix}_schedule_panel`);

        if (panel) {
            panel.classList.add('hidden');
        }
    },

    applyScheduleDefaults(mode, data) {
        const prefix = mode === 'edit' ? 'manual_edit' : 'manual_create';
        const formId = mode === 'edit' ? 'manual-attendance-edit-form' : 'manual-attendance-create-form';
        const form = document.getElementById(formId);
        const defaults = data.defaults ?? {};

        if (!form || !defaults.clock_in_time) {
            return;
        }

        this.setFieldValue(form, 'clock_in_time', defaults.clock_in_time, `${prefix}_clock_in_time`);
        this.setFieldValue(form, 'clock_out_time', defaults.clock_out_time ?? '', `${prefix}_clock_out_time`);
        this.setFieldValue(form, 'clock_out_date', defaults.clock_out_date ?? '', `${prefix}_clock_out_date`);

        const alpine = window.Alpine?.$data(form);
        if (alpine) {
            alpine.clockOutDateTouched = Boolean(data.next_day_clock_out);
        }
    },

    setEditorFieldValue(id, value) {
        const field = document.getElementById(id);

        if (field) {
            setEditorHtml(field, value ?? '');
        }
    },

    setFieldValue(form, name, value, id = null) {
        const field = id ? document.getElementById(id) : form.querySelector(`[name="${name}"]`);
        if (field) {
            field.value = value ?? '';
        }
    },

    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) {
            return;
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

            initAttendanceEditors(modal);
        });
    },

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal || modal.style.display === 'none') {
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

        window.setTimeout(() => {
            modal.style.display = 'none';

            if (
                document.getElementById(this.createModalId)?.style.display !== 'flex'
                && document.getElementById(this.editModalId)?.style.display !== 'flex'
            ) {
                document.body.classList.remove('overflow-hidden');
            }
        }, 300);
    },
};

window.ManualAttendanceModule = ManualAttendanceModule;

export default ManualAttendanceModule;
