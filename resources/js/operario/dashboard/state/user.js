function readUserFromDom() {
    const root = document.querySelector('.operario-dashboard');
    if (!root) return null;

    const id = root.dataset.userId ? Number(root.dataset.userId) : null;
    const rol = root.dataset.userRole ? String(root.dataset.userRole) : '';
    const nombre = root.dataset.userName ? String(root.dataset.userName) : '';

    if (!id) return null;

    return { id, rol, nombre };
}

export function initDashboardUser() {
    // Compatibilidad: si el Blade todavía define window.USUARIO_ACTUAL, respetarlo.
    if (window.USUARIO_ACTUAL && window.USUARIO_ACTUAL.id && window.USUARIO_ACTUAL.rol) {
        return;
    }

    const u = readUserFromDom();
    if (!u) return;

    window.USUARIO_ACTUAL = {
        ...(window.USUARIO_ACTUAL || {}),
        ...u,
    };
}


