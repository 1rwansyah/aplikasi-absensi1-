import { extractFaceDescriptorFromFile } from './employee-profile-face.js';
import { UiModal } from './ui-modal.js';

/**
 * Employee Module
 * Handles employee CRUD operations via modal-based AJAX
 */

const EmployeeModule = {
    csrfToken: null,
    deleteTargetId: null,
    initialized: false,

    init() {
        if (this.initialized || !document.getElementById('create-modal')) {
            return;
        }

        this.initialized = true;
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        this.bindEvents();
    },

    bindEvents() {
        document.addEventListener('click', (e) => {
            const trigger = e.target.closest('[data-action]');
            if (!trigger) {
                return;
            }

            const action = trigger.dataset.action;
            const employeeId = trigger.dataset.employeeId;
            const modalActions = [
                'create-employee',
                'show-employee',
                'edit-employee',
                'delete-employee',
                'close-modal',
                'submit-delete',
            ];

            if (!modalActions.includes(action)) {
                return;
            }

            e.preventDefault();

            switch (action) {
                case 'create-employee':
                    this.openCreateModal();
                    break;
                case 'show-employee':
                    if (employeeId) {
                        this.openShowModal(employeeId);
                    }
                    break;
                case 'edit-employee':
                    if (employeeId) {
                        this.openEditModal(employeeId);
                    }
                    break;
                case 'delete-employee':
                    if (employeeId) {
                        this.openDeleteModal(employeeId, trigger.dataset.employeeName ?? '');
                    }
                    break;
                case 'close-modal': {
                    const modalId = trigger.dataset.modalId;
                    if (modalId) {
                        this.closeModal(modalId);
                    }
                    break;
                }
                case 'submit-delete':
                    this.submitDeleteForm();
                    break;
            }
        });

        // Form submissions
        const createForm = document.getElementById('create-form');
        if (createForm) {
            createForm.addEventListener('submit', (e) => this.handleCreateSubmit(e));
        }

        const editForm = document.getElementById('edit-form');
        if (editForm) {
            editForm.addEventListener('submit', (e) => this.handleEditSubmit(e));
        }

        // Escape key to close all modals
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAllModals();
            }
        });

        // Photo preview handlers
        document.addEventListener('change', (e) => {
            if (e.target.matches('input[data-photo-preview]')) {
                const mode = e.target.dataset.photoPreview;
                this.previewPhoto(e.target, mode);
            }
        });

        this.bindWorkScheduleAutoAssign();
    },

    bindWorkScheduleAutoAssign() {
        [
            { prefix: '', staffId: 'staff', positionId: 'position', scheduleId: 'default_work_schedule_id' },
            { prefix: 'edit_', staffId: 'edit_staff', positionId: 'edit_position', scheduleId: 'edit_default_work_schedule_id' },
        ].forEach(({ staffId, positionId, scheduleId }) => {
            const staffInput = document.getElementById(staffId);
            const positionInput = document.getElementById(positionId);
            const scheduleSelect = document.getElementById(scheduleId);

            if (!scheduleSelect) {
                return;
            }

            const sync = () => {
                const staff = (staffInput?.value ?? '').trim().toUpperCase();
                const position = (positionInput?.value ?? '').trim().toUpperCase();

                let targetCode = null;
                if (staff === 'OB' || position === 'OB') {
                    targetCode = 'ob';
                } else if (staff === 'SECURITY' || position === 'SECURITY') {
                    targetCode = 'security';
                } else if (staff === 'ENGINEERING' || position.includes('ENGINEERING')) {
                    targetCode = 'engineering';
                }

                if (!targetCode) {
                    return;
                }

                const option = scheduleSelect.querySelector(`option[data-code="${targetCode}"]`);
                if (option) {
                    scheduleSelect.value = option.value;
                }
            };

            staffInput?.addEventListener('input', sync);
            positionInput?.addEventListener('input', sync);
        });
    },

    // Modal Management
    openModal(id) {
        UiModal.open(id);
    },

    closeModal(id) {
        UiModal.close(id);
    },

    closeAllModals() {
        this.closeCreateModal();
        this.closeShowModal();
        this.closeEditModal();
        this.closeDeleteModal();
    },

    // Create Modal
    openCreateModal() {
        const form = document.getElementById('create-form');
        if (form) form.reset();

        // Set default basic salary
        const basicSalaryInput = document.querySelector('#create-form [name="basic_salary"]');
        if (basicSalaryInput) basicSalaryInput.value = '1000000';

        // Hide errors
        const errorBox = document.getElementById('create-errors');
        if (errorBox) errorBox.classList.add('hidden');
        const errorList = document.getElementById('create-error-list');
        if (errorList) errorList.innerHTML = '';

        this.openModal('create-modal');
    },

    closeCreateModal() {
        this.closeModal('create-modal');
    },

    async handleCreateSubmit(event) {
        event.preventDefault();

        const button = document.getElementById('create-submit-btn');
        const originalText = button?.textContent;

        if (button) {
            button.disabled = true;
            button.textContent = 'Menyimpan...';
        }

        const form = event.target;
        const data = new FormData(form);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json',
                },
                body: data,
            });

            if (response.ok) {
                window.location.reload();
                return;
            }

            this.handleErrorResponse(response, 'create');
        } catch (error) {
            console.error('Create error:', error);
        } finally {
            if (button) {
                button.disabled = false;
                button.textContent = originalText || 'Simpan';
            }
        }
    },

    // Show Modal
    openShowModal(id) {
        document.getElementById('show-loading')?.classList.remove('hidden');
        document.getElementById('show-content')?.classList.add('hidden');

        this.setProfilePhoto(
            document.getElementById('show_photo'),
            document.getElementById('show_photo_placeholder'),
            null,
        );

        this.openModal('show-modal');

        this.fetchEmployee(`/employees/${id}`)
            .then((employee) => this.populateShowModal(employee))
            .catch((error) => {
                console.error('Failed to fetch employee:', error);
                this.closeShowModal();
            });
    },

    closeShowModal() {
        this.closeModal('show-modal');
    },

    // Edit Modal
    openEditModal(id) {
        document.getElementById('edit-loading')?.classList.remove('hidden');
        document.getElementById('edit-form')?.classList.add('hidden');

        const errorBox = document.getElementById('edit-errors');
        if (errorBox) errorBox.classList.add('hidden');
        const errorList = document.getElementById('edit-error-list');
        if (errorList) errorList.innerHTML = '';

        this.openModal('edit-modal');

        this.fetchEmployee(`/employees/${id}/edit`)
            .then((employee) => this.populateEditForm(employee))
            .catch((error) => {
                console.error('Failed to fetch employee:', error);
                this.closeEditModal();
            });
    },

    async fetchEmployee(url) {
        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': this.csrfToken ?? '',
            },
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        return response.json();
    },

    populateEditForm(employee) {
        // Update subtitle
        const subtitle = document.getElementById('edit-modal-subtitle');
        if (subtitle) {
            subtitle.textContent = `${employee.employee_code} — ${employee.name}`;
        }

        // Update form action
        const form = document.getElementById('edit-form');
        if (form) form.action = `/employees/${employee.id}`;

        // Standard fields
        const fields = [
            'employee_code', 'name', 'position', 'staff', 'address',
            'nik', 'birth_place', 'birth_date', 'education', 'work_experience', 'join_date',
            'email', 'basic_salary', 'bank_name', 'bank_account_number',
            'bank_account_name', 'employment_status',
        ];

        fields.forEach((field) => {
            const input = document.getElementById(`edit_${field}`);
            if (input) input.value = employee[field] ?? '';
        });

        const scheduleSelect = document.getElementById('edit_default_work_schedule_id');
        if (scheduleSelect && employee.default_work_schedule_id) {
            scheduleSelect.value = String(employee.default_work_schedule_id);
        }

        // Salary components
        if (employee.salary_components) {
            Object.entries(employee.salary_components).forEach(([componentId, amount]) => {
                const input = document.getElementById(`edit_sc_${componentId}`);
                if (input) input.value = amount ?? 0;
            });
        }

        // Roles checkboxes
        const hrCheckbox = document.getElementById('edit_role_hr');
        if (hrCheckbox) {
            hrCheckbox.checked = (employee.roles || []).includes('hr');
        }

        // Photo preview
        this.updatePhotoPreview(employee.profile_photo_url);

        // Reset file input
        const fileInput = document.getElementById('edit_profile_photo');
        if (fileInput) fileInput.value = '';

        // Show form
        document.getElementById('edit-loading')?.classList.add('hidden');
        document.getElementById('edit-form')?.classList.remove('hidden');
    },

    populateShowModal(employee) {
        // Helper: Format currency to Rupiah
        const formatRupiah = (amount) => {
            if (!amount || amount === 0) return '-';
            return `Rp ${Number(amount).toLocaleString('id-ID')}`;
        };

        // Helper: Format date to Indonesian format
        const formatDate = (dateString) => {
            if (!dateString) return '-';
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return '-';
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
            return `${String(date.getDate()).padStart(2, '0')} ${months[date.getMonth()]} ${date.getFullYear()}`;
        };

        // Helper: Get status badge classes
        const getStatusBadgeClass = (status) => {
            const classes = {
                active: 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400',
                inactive: 'bg-gray-100 text-gray-700 dark:bg-gray-500/10 dark:text-gray-400',
                resigned: 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400',
            };
            return classes[status] || classes.inactive;
        };

        // Helper: Get status label
        const getStatusLabel = (status) => {
            const labels = {
                active: 'Aktif',
                inactive: 'Nonaktif',
                resigned: 'Resign',
            };
            return labels[status] || (status ? status.charAt(0).toUpperCase() + status.slice(1) : '-');
        };

        // Set basic text fields
        const textFields = {
            'show_employee_code': employee.employee_code,
            'show_name': employee.name,
            'show_email': employee.email,
            'show_education': employee.education,
            'show_position': employee.position,
            'show_staff': employee.staff,
            'show_nik': employee.nik,
            'show_bank_name': employee.bank_name,
            'show_bank_account_number': employee.bank_account_number,
            'show_bank_account_name': employee.bank_account_name,
            'show_address': employee.address,
            'show_work_experience': employee.work_experience,
        };

        Object.entries(textFields).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = value || '-';
            }
        });

        // Position and staff (header & employment sections)
        const positionText = document.getElementById('show_position_text');
        const staffText = document.getElementById('show_staff_text');
        if (positionText) positionText.textContent = employee.position || '-';
        if (staffText) staffText.textContent = employee.staff || '-';

        // Birth info combined
        const birthInfoElement = document.getElementById('show_birth_info');
        if (birthInfoElement) {
            const birthPlace = employee.birth_place || '';
            const birthDate = formatDate(employee.birth_date);
            if (birthPlace && birthDate !== '-') {
                birthInfoElement.textContent = `${birthPlace}, ${birthDate}`;
            } else if (birthPlace) {
                birthInfoElement.textContent = birthPlace;
            } else if (birthDate !== '-') {
                birthInfoElement.textContent = birthDate;
            } else {
                birthInfoElement.textContent = '-';
            }
        }

        // Dates
        const joinDateElement = document.getElementById('show_join_date');
        if (joinDateElement) {
            joinDateElement.textContent = formatDate(employee.join_date);
        }

        // Employment status badge
        const statusBadge = document.getElementById('show_employment_status_badge');
        const statusText = document.getElementById('show_employment_status_text');
        if (statusBadge) {
            statusBadge.className = `inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${getStatusBadgeClass(employee.employment_status)}`;
            statusBadge.textContent = getStatusLabel(employee.employment_status);
        }
        if (statusText) {
            statusText.className = `inline-flex w-fit items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${getStatusBadgeClass(employee.employment_status)}`;
            statusText.textContent = getStatusLabel(employee.employment_status);
        }

        // Admin Panel badge (visible if user has 'hr' or 'admin' role)
        const hrBadge = document.getElementById('show_hr_badge');
        if (hrBadge) {
            const roles = employee.roles || [];
            const hasAdminAccess = roles.includes('hr') || roles.includes('admin');
            if (hasAdminAccess) {
                hrBadge.classList.remove('hidden');
            } else {
                hrBadge.classList.add('hidden');
            }
        }

        // Basic salary
        const basicSalaryElement = document.getElementById('show_basic_salary');
        if (basicSalaryElement) {
            basicSalaryElement.textContent = formatRupiah(employee.basic_salary);
        }

        // Gross salary
        const grossSalaryElement = document.getElementById('show_gross_salary');
        if (grossSalaryElement) {
            grossSalaryElement.textContent = formatRupiah(employee.gross_salary);
        }

        // Salary components
        const componentsContainer = document.getElementById('show_salary_components');
        if (componentsContainer) {
            const components = employee.salary_component_details || [];
            if (components.length === 0) {
                componentsContainer.innerHTML = '<p class="text-sm text-gray-400 italic">Tidak ada komponen gaji</p>';
            } else {
                componentsContainer.innerHTML = components.map((comp) => {
                    const isAllowance = comp.type === 'allowance';
                    const typeClass = isAllowance
                        ? 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400'
                        : 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400';
                    const typeLabel = isAllowance ? 'Tunjangan' : 'Potongan';
                    const sign = isAllowance ? '+' : '-';

                    return `
                        <div class="flex items-center justify-between rounded-lg border border-gray-100 bg-white px-3 py-2 dark:border-gray-700 dark:bg-gray-800">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex rounded px-2 py-0.5 text-xs font-medium ${typeClass}">${typeLabel}</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">${comp.name || 'Komponen'}</span>
                            </div>
                            <span class="text-sm font-semibold ${isAllowance ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}">${sign} ${formatRupiah(comp.amount).replace('Rp ', '')}</span>
                        </div>
                    `;
                }).join('');
            }
        }

        // Photo
        this.setProfilePhoto(
            document.getElementById('show_photo'),
            document.getElementById('show_photo_placeholder'),
            employee.profile_photo_url,
        );

        // Show content
        document.getElementById('show-loading')?.classList.add('hidden');
        document.getElementById('show-content')?.classList.remove('hidden');
    },

    setProfilePhoto(imageEl, placeholderEl, url) {
        if (!imageEl || !placeholderEl) {
            return;
        }

        if (!url) {
            this.clearProfilePhoto(imageEl, placeholderEl);
            return;
        }

        imageEl.onload = () => {
            imageEl.classList.remove('hidden');
            placeholderEl.classList.add('hidden');
        };

        imageEl.onerror = () => {
            this.clearProfilePhoto(imageEl, placeholderEl);
        };

        imageEl.src = url;
    },

    clearProfilePhoto(imageEl, placeholderEl) {
        imageEl.onload = null;
        imageEl.onerror = null;
        imageEl.src = '';
        imageEl.classList.add('hidden');
        placeholderEl.classList.remove('hidden');
    },

    updatePhotoPreview(url) {
        this.setProfilePhoto(
            document.getElementById('edit_photo_preview'),
            document.getElementById('edit_photo_placeholder'),
            url,
        );
    },

    closeEditModal() {
        // Reset photo
        const preview = document.getElementById('edit_photo_preview');
        const placeholder = document.getElementById('edit_photo_placeholder');

        if (preview) {
            preview.src = '';
            preview.classList.add('hidden');
        }
        if (placeholder) {
            placeholder.classList.remove('hidden');
        }

        // Reset file input
        const fileInput = document.getElementById('edit_profile_photo');
        if (fileInput) fileInput.value = '';

        this.closeModal('edit-modal');
    },

    async handleEditSubmit(event) {
        event.preventDefault();

        const button = document.getElementById('edit-submit-btn');
        const originalText = button?.textContent;

        if (button) {
            button.disabled = true;
            button.textContent = 'Menyimpan...';
        }

        const form = event.target;
        const data = new FormData(form);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json',
                },
                body: data,
            });

            if (response.ok) {
                window.location.reload();
                return;
            }

            this.handleErrorResponse(response, 'edit');
        } catch (error) {
            console.error('Edit error:', error);
        } finally {
            if (button) {
                button.disabled = false;
                button.textContent = originalText || 'Simpan Perubahan';
            }
        }
    },

    // Delete Modal
    openDeleteModal(id, name) {
        this.deleteTargetId = id;

        const nameEl = document.getElementById('modal-employee-name');
        if (nameEl) nameEl.textContent = name;

        this.openModal('delete-modal');
    },

    closeDeleteModal() {
        this.deleteTargetId = null;
        this.closeModal('delete-modal');
    },

    submitDeleteForm() {
        if (!this.deleteTargetId) return;

        const form = document.getElementById(`delete-form-${this.deleteTargetId}`);
        if (form) form.submit();
    },

    // Photo Preview
    previewPhoto(input, mode) {
        if (!input.files || !input.files[0]) return;

        const file = input.files[0];
        const reader = new FileReader();

        reader.onload = (e) => {
            const previewId = mode === 'edit' ? 'edit_photo_preview' : 'photo_preview';
            const placeholderId = mode === 'edit' ? 'edit_photo_placeholder' : 'photo_placeholder';

            const preview = document.getElementById(previewId);
            const placeholder = document.getElementById(placeholderId);

            if (!preview) return;

            preview.src = e.target.result;
            preview.classList.remove('hidden');

            if (placeholder) placeholder.classList.add('hidden');
        };

        reader.readAsDataURL(file);
        extractFaceDescriptorFromFile(file, mode);
    },

    // Error Handling
    async handleErrorResponse(response, context) {
        const errorBox = document.getElementById(`${context}-errors`);
        const errorList = document.getElementById(`${context}-error-list`);

        if (!errorBox || !errorList) return;

        errorList.innerHTML = '';

        if (response.status === 422) {
            const json = await response.json();
            Object.values(json.errors).flat().forEach((message) => {
                const li = document.createElement('li');
                li.textContent = message;
                errorList.appendChild(li);
            });
        } else {
            const li = document.createElement('li');
            li.textContent = `Terjadi kesalahan server (HTTP ${response.status}). Coba lagi.`;
            errorList.appendChild(li);
        }

        errorBox.classList.remove('hidden');
        errorBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    },
};

function bootEmployeeModule() {
    EmployeeModule.init();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootEmployeeModule);
} else {
    bootEmployeeModule();
}

window.EmployeeModule = EmployeeModule;

export default EmployeeModule;
