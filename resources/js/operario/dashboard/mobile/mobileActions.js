export function toggleMobileActions(prendaId) {
    const drawer = document.getElementById(`mobile-drawer-${prendaId}`);
    const toggleBtns = document.querySelectorAll(`.mobile-actions-toggle[onclick*="${prendaId}"]`);

    if (!drawer) {
        console.warn(`No se encontró el drawer mobile-drawer-${prendaId}`);
        return;
    }

    const isActive = drawer.classList.contains('active');

    document.querySelectorAll('.mobile-actions-drawer.active').forEach((d) => {
        if (d.id !== `mobile-drawer-${prendaId}`) {
            d.classList.remove('active');
        }
    });

    document.querySelectorAll('.mobile-actions-toggle.active').forEach((btn) => {
        if (!btn.onclick || !btn.onclick.toString().includes(prendaId)) {
            btn.classList.remove('active');
        }
    });

    if (!isActive) {
        drawer.classList.add('active');
        toggleBtns.forEach((btn) => btn.classList.add('active'));
    } else {
        drawer.classList.remove('active');
        toggleBtns.forEach((btn) => btn.classList.remove('active'));
    }
}

