import { initDashboardUser } from './state/user';
import { initAdminTabs } from './tabs/adminTabs';
import { initOperarioTabs } from './tabs/operarioTabs';
import { initDashboardSearch } from './ui/search';
import { initReciboFilters } from './ui/filters';
import { initDashboardPagination } from './ui/pagination';
import { initGlobalModalClosers } from './ui/modalClosers';
import { injectDashboardStyles } from './ui/injectStyles';
import { registerDashboardGlobals } from './globals/registerGlobals';
import { initRealtimeListeners } from './realtime/realtime';
import './distribucion/distribucion'; // Importar funcionalidad de distribución

function isOperarioDashboardPage() {
    return !!document.querySelector('.operario-dashboard');
}

function initOperarioDashboard() {
    if (!isOperarioDashboardPage()) return;

    initDashboardUser();

    injectDashboardStyles();
    registerDashboardGlobals();

    initAdminTabs();
    initOperarioTabs();
    initDashboardSearch();
    initReciboFilters();
    initDashboardPagination();
    initGlobalModalClosers();

    initRealtimeListeners();
}

// Exponer init para debug manual si se requiere
window.__initOperarioDashboard = initOperarioDashboard;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initOperarioDashboard);
} else {
    initOperarioDashboard();
}

