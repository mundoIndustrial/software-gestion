export function initDashboardPagination() {
    const userRole = document.querySelector('.operario-dashboard')?.dataset?.userRole || '';
    const isCortador = userRole === 'cortador';
    const PER_PAGE = 12; // Paginacion de 12 items por pagina para todos los roles
    let currentPage = 1;
    let visibleCards = [];

    const ordenesList = document.getElementById('ordenesList');
    if (!ordenesList) return;

    // Crear el contenedor de paginacion si no existe
    let paginationContainer = document.getElementById('dashboardPagination');
    if (!paginationContainer) {
        paginationContainer = document.createElement('div');
        paginationContainer.id = 'dashboardPagination';
        paginationContainer.className = 'dashboard-pagination-container';
        ordenesList.after(paginationContainer);
    }

    function updatePagination() {
        const ordenCards = Array.from(ordenesList.querySelectorAll('.orden-card-simple'));

        // Obtenemos solo las tarjetas que no estan ocultas por otros filtros (tipo_recibo, busqueda, etc)
        visibleCards = ordenCards.filter(card => card.style.display !== 'none');

        const totalUnits = getTotalUnits();
        const totalPages = Math.ceil(totalUnits / PER_PAGE);

        if (currentPage > totalPages) currentPage = Math.max(1, totalPages);

        renderPaginationControls(totalPages, totalUnits);
        showPage(currentPage);
    }

    function getCardUnits(card) {
        if (!isCortador) return 1;

        const raw = parseInt(card.dataset.recibosCorteAsignados || '0', 10);
        if (Number.isNaN(raw) || raw < 0) return 0;
        return raw;
    }

    function getTotalUnits() {
        if (!isCortador) return visibleCards.length;
        return visibleCards.reduce((sum, card) => sum + getCardUnits(card), 0);
    }

    function getVisibleCardsForPage(page) {
        if (!isCortador) {
            const start = (page - 1) * PER_PAGE;
            const end = start + PER_PAGE;
            return visibleCards.slice(start, end);
        }

        const startUnit = (page - 1) * PER_PAGE + 1;
        const endUnit = page * PER_PAGE;
        let acumulado = 0;

        return visibleCards.filter(card => {
            const units = getCardUnits(card);
            const inicioCard = acumulado + 1;
            const finCard = acumulado + units;
            acumulado = finCard;

            if (units <= 0) return false;
            return finCard >= startUnit && inicioCard <= endUnit;
        });
    }

    function showPage(page) {
        currentPage = page;

        // Ocultar todas las tarjetas visibles inicialmente
        visibleCards.forEach(card => card.classList.add('page-hidden'));

        // Mostrar solo las de la pagina actual
        const cardsToShow = getVisibleCardsForPage(currentPage);
        cardsToShow.forEach(card => card.classList.remove('page-hidden'));
    }

    function renderPaginationControls(totalPages, totalUnits) {
        if (totalPages <= 1) {
            paginationContainer.innerHTML = '';
            return;
        }

        const start = (currentPage - 1) * PER_PAGE + 1;
        const end = Math.min(currentPage * PER_PAGE, totalUnits);
        const countInPage = end - start + 1;

        let html = `
            <div class="pagination-info">
                Mostrando ${countInPage} de ${totalUnits} registros
            </div>
            <div class="pagination-buttons">
                <button class="pagination-btn ${currentPage === 1 ? 'disabled' : ''}" data-page="${currentPage - 1}">
                    <span class="material-symbols-rounded">chevron_left</span>
                </button>
        `;

        // Logica de paginas (simplificada para mostrar hasta 5 numeros)
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, startPage + 4);
        if (endPage - startPage < 4) startPage = Math.max(1, endPage - 4);

        for (let i = startPage; i <= endPage; i++) {
            html += `
                <button class="pagination-btn ${i === currentPage ? 'active' : ''}" data-page="${i}">
                    ${i}
                </button>
            `;
        }

        html += `
                <button class="pagination-btn ${currentPage === totalPages ? 'disabled' : ''}" data-page="${currentPage + 1}">
                    <span class="material-symbols-rounded">chevron_right</span>
                </button>
            </div>
        `;

        paginationContainer.innerHTML = html;

        // Listeners para botones
        paginationContainer.querySelectorAll('.pagination-btn:not(.disabled)').forEach(btn => {
            btn.addEventListener('click', () => {
                showPage(parseInt(btn.dataset.page, 10));
                renderPaginationControls(totalPages, totalUnits);
            });
        });
    }

    // Exponer globalmente para que search.js y filters.js lo llamen
    window.__updateDashboardPagination = updatePagination;
    window.__resetDashboardPagination = () => {
        currentPage = 1;
        updatePagination();
    };

    // Inicializar
    updatePagination();
}
