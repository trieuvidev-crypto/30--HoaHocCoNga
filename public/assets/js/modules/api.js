/**
 * Thin fetch() wrapper shared by every module that talks to /api/v1.
 * Reads the CSRF token from the <meta name="csrf-token"> tag rendered
 * server-side (see resources/views/layouts/auth.php) and attaches it
 * to every state-changing request, per CsrfMiddleware's expectations.
 */

function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');

    return meta ? meta.getAttribute('content') : '';
}

/**
 * @param {string} path e.g. '/api/v1/auth/login'
 * @param {object} [options]
 * @returns {Promise<{ok: boolean, status: number, body: object}>}
 */
export async function apiRequest(path, options = {}) {
    const method = (options.method || 'GET').toUpperCase();
    const headers = {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(options.headers || {}),
    };

    if (method !== 'GET' && method !== 'HEAD') {
        headers['X-CSRF-Token'] = csrfToken();
    }

    const response = await fetch(path, {
        method,
        headers,
        credentials: 'same-origin',
        body: options.body ? JSON.stringify(options.body) : undefined,
    });

    let body;

    try {
        body = await response.json();
    } catch {
        body = { success: false, message: 'Phản hồi từ máy chủ không hợp lệ.', errors: {} };
    }

    return { ok: response.ok, status: response.status, body };
}
