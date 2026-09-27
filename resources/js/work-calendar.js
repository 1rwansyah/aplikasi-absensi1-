const WorkCalendarModule = {
    modal: null,
    form: null,

    init() {
        this.modal = document.getElementById('calendar-edit-modal');
        this.form  = document.getElementById('calendar-edit-form');

        if (!this.modal || !this.form) return;

        document.querySelectorAll('[data-calendar-date]').forEach(btn => {
            btn.addEventListener('click', () => this.open(btn));
        });

        document.querySelectorAll('[data-calendar-close]').forEach(btn => {
            btn.addEventListener('click', () => this.close());
        });

        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) this.close();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.close();
        });
    },

    open(btn) {
        const date       = btn.dataset.calendarDate;
        const type       = btn.dataset.calendarType;
        const name       = btn.dataset.calendarName ?? '';
        const note       = btn.dataset.calendarNote ?? '';
        const updateUrl  = btn.dataset.updateUrl;

        this.modal.querySelector('#calendar-modal-date').textContent = btn.dataset.calendarDateLabel ?? date;
        this.modal.querySelector('#calendar-type').value  = type;
        this.modal.querySelector('#calendar-name').value  = name;
        this.modal.querySelector('#calendar-note').value  = note;
        this.form.action = updateUrl;

        this.modal.style.display = 'flex';
        document.body.classList.add('overflow-hidden');
    },

    close() {
        if (!this.modal) return;
        this.modal.style.display = 'none';
        document.body.classList.remove('overflow-hidden');
    },
};

export default WorkCalendarModule;
