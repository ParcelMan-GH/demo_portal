const TOKEN_KEYS = {
    vendor: 'parcelman_vendor_token',
    driver: 'parcelman_driver_token',
};

export function getToken(role) {
    const key = TOKEN_KEYS[role];
    return key ? localStorage.getItem(key) : null;
}

export function setToken(role, token) {
    const key = TOKEN_KEYS[role];
    if (!key) {
        return;
    }

    localStorage.setItem(key, token);
}

export function clearToken(role) {
    const key = TOKEN_KEYS[role];
    if (!key) {
        return;
    }

    localStorage.removeItem(key);
}

async function parseJsonSafe(response) {
    try {
        return await response.json();
    } catch {
        return null;
    }
}

export function getMessage(payload, fallback = 'Request failed. Please try again.') {
    if (payload && typeof payload.message === 'string' && payload.message.trim() !== '') {
        return payload.message;
    }

    return fallback;
}

export function isUnauthorized(response, payload) {
    return response.status === 401 || payload?.message === 'Unauthenticated.';
}

export async function apiRequest(url, options = {}) {
    const method = options.method || 'GET';
    const role = options.role || null;
    const data = options.data ?? null;
    const isFormData = typeof FormData !== 'undefined' && data instanceof FormData;
    const headers = {
        Accept: 'application/json',
        ...(options.headers || {}),
    };

    if (role) {
        const token = getToken(role);
        if (token) {
            headers.Authorization = `Bearer ${token}`;
        }
    }

    if (data !== null && !isFormData) {
        headers['Content-Type'] = 'application/json';
    }

    const response = await fetch(url, {
        method,
        headers,
        body: data !== null ? (isFormData ? data : JSON.stringify(data)) : undefined,
    });

    const payload = await parseJsonSafe(response);
    const success = payload?.success === true;

    return {
        response,
        payload,
        success,
        message: getMessage(payload),
        unauthorized: isUnauthorized(response, payload),
    };
}
