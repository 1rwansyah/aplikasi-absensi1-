/**
 * Sidebar controller — single breakpoint: lg (1024px).
 *
 * Desktop (lg+):
 *   Open  → data-open=true (translate-x-0), main data-sidebar-open=true (ml-64)
 *   Closed → data-open=false, main data-sidebar-open=false
 *
 * Mobile (<lg):
 *   Open  → overlay + backdrop
 *   Closed → hidden off-canvas
 *
 * No inline transform/margin styles — avoids conflict with CSS media queries.
 */
const SIDEBAR_MQ = '(min-width: 1024px)';

const SidebarController = {
    storageKey: 'sidebarOpen',
    desktopMq: null,
    wasDesktop: null,

    elements() {
        return {
            shell: document.getElementById('sidebar-shell'),
            main: document.getElementById('main-wrapper'),
            backdrop: document.getElementById('sidebar-backdrop'),
            iconOpen: document.getElementById('sidebar-icon-open'),
            iconClose: document.getElementById('sidebar-icon-close'),
            toggleBtn: document.getElementById('sidebar-toggle-btn'),
        };
    },

    isDesktop() {
        return this.desktopMq?.matches ?? window.matchMedia(SIDEBAR_MQ).matches;
    },

    isOpen(shell) {
        return shell?.dataset.open === 'true';
    },

    clearInlineLayoutStyles(shell, main) {
        shell?.style.removeProperty('transform');
        main?.style.removeProperty('margin-left');
    },

    apply(open) {
        const { shell, main, backdrop, iconOpen, iconClose, toggleBtn } = this.elements();

        if (!shell || !main) {
            return;
        }

        this.clearInlineLayoutStyles(shell, main);

        const desktop = this.isDesktop();
        const resolvedOpen = Boolean(open);

        shell.dataset.open = resolvedOpen ? 'true' : 'false';
        // Content offset only when sidebar is docked on desktop
        main.dataset.sidebarOpen = desktop && resolvedOpen ? 'true' : 'false';

        if (backdrop) {
            const showBackdrop = resolvedOpen && !desktop;
            backdrop.classList.toggle('hidden', !showBackdrop);
            backdrop.setAttribute('aria-hidden', showBackdrop ? 'false' : 'true');
        }

        if (iconOpen) {
            iconOpen.classList.toggle('hidden', resolvedOpen);
        }
        if (iconClose) {
            iconClose.classList.toggle('hidden', !resolvedOpen);
        }

        shell.setAttribute('aria-hidden', resolvedOpen ? 'false' : 'true');
        if (toggleBtn) {
            toggleBtn.setAttribute('aria-expanded', resolvedOpen ? 'true' : 'false');
        }

        if (desktop) {
            localStorage.setItem(this.storageKey, resolvedOpen ? '1' : '0');
        }
    },

    toggle() {
        const { shell } = this.elements();
        if (!shell) {
            return;
        }
        this.apply(!this.isOpen(shell));
    },

    syncToViewport() {
        const desktop = this.isDesktop();

        if (this.wasDesktop === desktop) {
            // Still same mode: keep current open state, just re-apply offset rules
            const { shell } = this.elements();
            this.apply(this.isOpen(shell));
            return;
        }

        this.wasDesktop = desktop;

        if (desktop) {
            this.apply(localStorage.getItem(this.storageKey) !== '0');
        } else {
            this.apply(false);
        }
    },

    init() {
        const { shell, toggleBtn, backdrop } = this.elements();

        if (!shell) {
            return;
        }

        this.desktopMq = window.matchMedia(SIDEBAR_MQ);
        this.wasDesktop = this.isDesktop();

        const startOpen = this.isDesktop()
            ? localStorage.getItem(this.storageKey) !== '0'
            : false;
        this.apply(startOpen);

        toggleBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            this.toggle();
        });

        backdrop?.addEventListener('click', () => {
            this.apply(false);
        });

        const onViewportChange = () => this.syncToViewport();

        if (typeof this.desktopMq.addEventListener === 'function') {
            this.desktopMq.addEventListener('change', onViewportChange);
        } else if (typeof this.desktopMq.addListener === 'function') {
            this.desktopMq.addListener(onViewportChange);
        }

        let resizeTimer = null;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(onViewportChange, 100);
        });
    },
};

export default SidebarController;
