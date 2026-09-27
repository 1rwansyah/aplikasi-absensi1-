const TOAST_EVENT = 'app:toast';

const TOAST_STYLES_ID = 'toast-progress-styles';

const styleMap = {
    error: {
        card: 'border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200',
        bar: 'bg-red-500 dark:bg-red-400',
        track: 'bg-red-200/60 dark:bg-red-900/40',
    },
    warning: {
        card: 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200',
        bar: 'bg-amber-500 dark:bg-amber-400',
        track: 'bg-amber-200/60 dark:bg-amber-900/40',
    },
    success: {
        card: 'border-green-200 bg-green-50 text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200',
        bar: 'bg-green-500 dark:bg-green-400',
        track: 'bg-green-200/60 dark:bg-green-900/40',
    },
};

const iconMap = {
    error: '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    warning: '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    success: '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
};

function ensureToastStyles() {
    if (document.getElementById(TOAST_STYLES_ID)) {
        return;
    }

    const style = document.createElement('style');
    style.id = TOAST_STYLES_ID;
    style.textContent = `
        @keyframes toast-progress-shrink {
            from { transform: scaleX(1); }
            to { transform: scaleX(0); }
        }
    `;
    document.head.appendChild(style);
}

export function showToast(type, message, duration = 4000) {
    if (!message) {
        return;
    }

    window.dispatchEvent(new CustomEvent(TOAST_EVENT, {
        detail: { type, message, duration },
    }));
}

export function initToastStack() {
    const root = document.getElementById('toast-stack');
    if (!root || root.dataset.initialized === '1') {
        return;
    }

    root.dataset.initialized = '1';
    ensureToastStyles();

    const flashRaw = root.dataset.flash || '';

    if (flashRaw) {
        try {
            const flash = JSON.parse(flashRaw);
            if (flash.success) {
                showToast('success', flash.success);
            }
            if (flash.error) {
                showToast('error', flash.error);
            }
        } catch {
            // ignore invalid flash payload
        }
    }

    window.addEventListener(TOAST_EVENT, (event) => {
        const { type = 'success', message = '', duration = 4000 } = event.detail || {};

        if (!message) {
            return;
        }

        const theme = styleMap[type] ?? styleMap.success;

        const toast = document.createElement('div');
        toast.setAttribute('role', 'alert');
        toast.className = `pointer-events-auto w-full overflow-hidden rounded-2xl border shadow-lg transition-all duration-300 translate-x-0 opacity-100 ${theme.card}`;

        const body = document.createElement('div');
        body.className = 'flex min-h-[3.75rem] items-center gap-3 px-4 py-4 sm:min-h-[3.5rem] sm:py-3.5';

        const iconWrap = document.createElement('div');
        iconWrap.className = 'shrink-0';
        iconWrap.innerHTML = iconMap[type] ?? iconMap.success;

        const messageEl = document.createElement('p');
        messageEl.className = 'flex-1 text-sm font-medium leading-relaxed sm:text-sm';
        messageEl.textContent = message;

        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'shrink-0 rounded-md p-1 text-base leading-none opacity-60 transition hover:opacity-100';
        closeBtn.setAttribute('aria-label', 'Tutup');
        closeBtn.textContent = '×';

        body.append(iconWrap, messageEl, closeBtn);

        const track = document.createElement('div');
        track.className = `h-1 w-full ${theme.track}`;

        const progressBar = document.createElement('div');
        progressBar.className = `h-full origin-left ${theme.bar}`;
        progressBar.style.animation = `toast-progress-shrink ${duration}ms linear forwards`;

        track.appendChild(progressBar);
        toast.append(body, track);

        let dismissTimer = null;

        const dismiss = () => {
            if (dismissTimer) {
                clearTimeout(dismissTimer);
                dismissTimer = null;
            }

            progressBar.style.animationPlayState = 'paused';
            toast.classList.add('translate-x-4', 'opacity-0');
            setTimeout(() => toast.remove(), 300);
        };

        closeBtn.addEventListener('click', dismiss);
        root.appendChild(toast);

        dismissTimer = setTimeout(dismiss, duration);
    });
}

window.showToast = showToast;

export default { showToast, initToastStack };
