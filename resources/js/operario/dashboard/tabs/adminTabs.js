function setActiveAdminTab(tab) {
    document.querySelectorAll('[data-admin-tab]').forEach((btn) => {
        btn.classList.toggle('badge-filtro-active', btn.dataset.adminTab === tab);
    });
}

function cargarAdminTab(tab) {
    const url = new URL(window.location.href);
    url.searchParams.set('tab', tab);
    url.searchParams.delete('page');
    Array.from(url.searchParams.keys()).forEach((key) => {
        if (key.startsWith('page_vc_')) {
            url.searchParams.delete(key);
        }
    });
    window.location.href = url.toString();
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
