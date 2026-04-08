function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

export async function httpJson(url, options = {}) {
    const headers = {
        'X-CSRF-TOKEN': csrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
        ...(options.headers || {}),
    };

    const resp = await fetch(url, {
        credentials: 'same-origin',
        ...options,
        headers,
    });

    return resp;
}

export async function httpJsonBody(url, method, bodyObj, options = {}) {
    const resp = await httpJson(url, {
        method,
        headers: {
            'Content-Type': 'application/json',
            ...(options.headers || {}),
        },
        body: JSON.stringify(bodyObj || {}),
    });

    return resp;
}
