/**
 * Shared modal open/close helpers (backdrop + scale animation).
 */
export const UiModal = {
    open(id) {
        const modal = document.getElementById(id);
        if (!modal) {
            return;
        }

        modal.style.display = 'flex';
        document.body.classList.add('overflow-hidden');

        requestAnimationFrame(() => {
            const backdrop = modal.querySelector('[data-backdrop]');
            const content = modal.querySelector('[data-modal-content]');

            backdrop?.classList.remove('opacity-0');
            if (content) {
                content.classList.remove('scale-95', 'opacity-0');
                content.classList.add('scale-100', 'opacity-100');
            }
        });
    },

    close(id) {
        const modal = document.getElementById(id);
        if (!modal) {
            return;
        }

        const backdrop = modal.querySelector('[data-backdrop]');
        const content = modal.querySelector('[data-modal-content]');

        backdrop?.classList.add('opacity-0');
        if (content) {
            content.classList.remove('scale-100', 'opacity-100');
            content.classList.add('scale-95', 'opacity-0');
        }

        setTimeout(() => {
            modal.style.display = 'none';
            document.body.classList.remove('overflow-hidden');
        }, 300);
    },

    bindCloseTriggers(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal || modal.dataset.closeBound === '1') {
            return;
        }

        modal.dataset.closeBound = '1';

        modal.querySelectorAll('[data-action="close-modal"]').forEach((trigger) => {
            trigger.addEventListener('click', (event) => {
                event.preventDefault();
                const targetId = trigger.dataset.modalId || modalId;
                UiModal.close(targetId);
            });
        });

        modal.querySelector('[data-backdrop]')?.addEventListener('click', (event) => {
            if (event.target.dataset.backdrop !== undefined) {
                UiModal.close(modalId);
            }
        });
    },
};

window.UiModal = UiModal;

export default UiModal;
