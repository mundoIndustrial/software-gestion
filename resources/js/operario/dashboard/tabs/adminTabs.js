function setActiveAdminTab(tab) {
    document.querySelectorAll('[data-admin-tab]').forEach((btn) => {
        btn.classList.toggle('badge-filtro-active', btn.dataset.adminTab === tab);
    });
}

async function cargarAdminTab(tab) {
    try {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);

        const resp = await fetch(url.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!resp.ok) {
            throw new Error('HTTP ' + resp.status);
        }

        const html = await resp.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const nuevoOrdenesList = doc.getElementById('ordenesList');
        const actualOrdenesList = document.getElementById('ordenesList');

        if (!nuevoOrdenesList || !actualOrdenesList) {
            throw new Error('No se encontró #ordenesList');
        }

        actualOrdenesList.innerHTML = nuevoOrdenesList.innerHTML;

        if (typeof window.__initDashboardSearch === 'function') {
            window.__initDashboardSearch();
        }

        // Actualizar URL sin recargar
        window.history.pushState({ tab }, '', url.toString());

        setActiveAdminTab(tab);
    } catch (e) {
        console.warn('[Operario Dashboard] Falló cargar tab admin, recargando página', e);
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.location.href = url.toString();
    }
}

export function initAdminTabs() {
    const botones = document.querySelectorAll('[data-admin-tab]');
    if (!botones.length) {
        return;
    }

    botones.forEach((btn) => {
        btn.addEventListener('click', function () {
            const tab = btn.dataset.adminTab;
            if (!tab) return;
            cargarAdminTab(tab);
        });
    });

    window.addEventListener('popstate', function () {
        const tab = new URL(window.location.href).searchParams.get('tab') || 'costura';
        setActiveAdminTab(tab);
    });
}
