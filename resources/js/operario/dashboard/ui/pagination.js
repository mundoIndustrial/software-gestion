export function initDashboardPagination() {
    const PER_PAGE = 12; // Número de tarjetas por página
    let currentPage = 1;
    let visibleCards = [];

    const ordenesList = document.getElementById('ordenesList');
    if (!ordenesList) return;

    // Crear el contenedor de paginación si no existe
    let paginationContainer = document.getElementById('dashboardPagination');
    if (!paginationContainer) {
        paginationContainer = document.createElement('div');
        paginationContainer.id = 'dashboardPagination';
        paginationContainer.className = 'dashboard-pagination-container';
        ordenesList.after(paginationContainer);
    }

    function updatePagination() {
        const ordenCards = Array.from(ordenesList.querySelectorAll('.orden-card-simple'));
        
        // Obtenemos solo las tarjetas que no están ocultas por otros filtros (tipo_recibo, búsqueda, etc)
        // NOTA: Usamos getComputedStyle o simplemente verificamos que display no sea 'none'
        visibleCards = ordenCards.filter(card => card.style.display !== 'none');

        const totalPages = Math.ceil(visibleCards.length / PER_PAGE);
        
        if (currentPage > totalPages) currentPage = Math.max(1, totalPages);

        renderPaginationControls(totalPages);
        showPage(currentPage);
    }

    function showPage(page) {
        currentPage = page;
        
        // Ocultar todas las tarjetas visibles inicialmente
        visibleCards.forEach(card => card.classList.add('page-hidden'));

        // Mostrar solo las de la página actual
        const start = (currentPage - 1) * PER_PAGE;
        const end = start + PER_PAGE;
        const cardsToShow = visibleCards.slice(start, end);

        cardsToShow.forEach(card => card.classList.remove('page-hidden'));
    }

    function renderPaginationControls(totalPages) {
        if (totalPages <= 1) {
            paginationContainer.innerHTML = '';
            return;
        }

        const start = (currentPage - 1) * PER_PAGE + 1;
        const end = Math.min(currentPage * PER_PAGE, visibleCards.length);
        const countInPage = end - start + 1;

        let html = `
            <div class="pagination-info">
                Mostrando ${countInPage} de ${visibleCards.length} registros
            </div>
            <div class="pagination-buttons">
                <button class="pagination-btn ${currentPage === 1 ? 'disabled' : ''}" data-page="${currentPage - 1}">
                    <span class="material-symbols-rounded">chevron_left</span>
                </button>
        `;

        // Lógica de páginas (simplificada para mostrar hasta 5 números)
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
                showPage(parseInt(btn.dataset.page));
                renderPaginationControls(totalPages);
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
