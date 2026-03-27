/**
 * Supervisor Pedidos Frontend Entrypoint (Fase 2 - No Legacy)
 *
 * Objetivo inicial:
 * - Definir punto unico de entrada via Vite para este modulo.
 * - Ejecutar solo carga modular (sin inyeccion secuencial legacy).
 * - Exponer evento/estado para instrumentacion y futuros loaders.
 */

const entryState = {
    isReady: false,
    version: 'fase2-no-legacy-2026-03-27',
};

const SUPERVISOR_MODULE_IMPORTS = [
    '/js/ordersjs/tracking-modal-handler.js',
];

function checkCoreDependencies() {
    const hasShared = Boolean(window.shared?.isReady);
    const hasSupervisorCore = Boolean(window.supervisorPedidos?.isReady);

    if (!hasShared || !hasSupervisorCore) {
        console.warn('[SP Entry] Core no listo aun', {
            sharedReady: hasShared,
            supervisorCoreReady: hasSupervisorCore,
        });
    }
}

function isSupervisorIndexPage() {
    return Boolean(document.getElementById('supervisorPedidosIndexContent'));
}

async function loadSupervisorModules() {
    if (!isSupervisorIndexPage()) {
        return;
    }

    const imports = SUPERVISOR_MODULE_IMPORTS.map(async (modulePath) => {
        const moduleUrl = new URL(modulePath, window.location.origin).toString();
        try {
            await import(/* @vite-ignore */ moduleUrl);
        } catch (error) {
            console.error('[SP Entry] Error cargando modulo supervisor', { modulePath, moduleUrl, error });
        }
    });

    await Promise.all(imports);
}

function setupNoLegacyBridges() {
    // Compatibilidad mínima para código existente sin mantener loader legacy.
    if (typeof window.openOrderTrackingModal !== 'function' && typeof window.openOrderTracking === 'function') {
        window.openOrderTrackingModal = (ordenId) => window.openOrderTracking(ordenId, true);
    }
}

function initSupervisorEntry() {
    if (entryState.isReady) {
        return;
    }

    checkCoreDependencies();

    loadSupervisorModules()
        .then(() => {
            setupNoLegacyBridges();
        })
        .finally(() => {
            entryState.isReady = true;
            window.supervisorPedidosEntry = Object.freeze({
                ...entryState,
                initializedAt: new Date().toISOString(),
            });

            document.dispatchEvent(
                new CustomEvent('supervisor-pedidos:entry-ready', {
                    detail: window.supervisorPedidosEntry,
                }),
            );

            console.log('[SP Entry] listo', window.supervisorPedidosEntry);
        });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSupervisorEntry);
} else {
    initSupervisorEntry();
}
