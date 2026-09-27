/**
 * CSRF-aware fetch helper for JSON API requests.
 * Reads a fresh token from <meta name="csrf-token"> on every request.
 */

const CSRF_EXPIRED_MESSAGE = 'Sesi halaman sudah kedaluwarsa. Halaman akan dimuat ulang.';

let csrfReloadScheduled = false;

export function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

export function handleCsrfExpired() {
    if (csrfReloadScheduled) {
        return;
    }

    csrfReloadScheduled = true;
    alert(CSRF_EXPIRED_MESSAGE);
    window.location.reload();
}

/**
 * @returns {Promise<Response|null>} null when CSRF expired (page reload scheduled)
 */
export async function csrfFetch(url, options = {}) {
    const csrfToken = getCsrfToken();
    const headers = {
        Accept: 'application/json',
        ...(options.headers ?? {}),
    };

    if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken;
    }

    const response = await fetch(url, {
        ...options,
        headers,
    });

    if (response.status === 419) {
        handleCsrfExpired();
        return null;
    }

    return response;
}

if (typeof window !== 'undefined') {
    window.getCsrfToken = getCsrfToken;
    window.handleCsrfExpired = handleCsrfExpired;
    window.csrfFetch = csrfFetch;
}
