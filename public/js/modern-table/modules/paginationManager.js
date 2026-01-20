/**
 * PaginationManager
 * Responsabilidad: Gestionar paginación
 * SOLID: Single Responsibility
 */
const PaginationManager = (() => {
    return {
        // Actualizar información de paginación
        updateInfo: (pagination) => {
            let info = document.getElementById('paginationInfo');
            if (!info) info = document.querySelector('.pagination-info span');
            
            if (info) {
                const newText = `Mostrando ${pagination.from}-${pagination.to} de ${pagination.total} registros`;
                info.textContent = newText;
                console.log(` Paginación actualizada: ${newText}`);
            }
        },

        // Actualizar controles de paginación
        updateControls: (html, pagination, baseRoute) => {
            const controls = document.querySelector('.pagination-controls');
            if (!controls) return;

            if (!pagination) {
                console.warn('⚠️ Datos de paginación no disponibles');
                return;
            }

            const currentPage = pagination.current_page || 1;
            const lastPage = pagination.last_page || 1;
            const total = pagination.total || 0;

            console.log(`📊 Actualizando paginación: Página ${currentPage} de ${lastPage} (Total: ${total})`);

            if (html && html.trim().length > 0) {
                controls.innerHTML = html;
                console.log(` Paginación del backend utilizada`);
            } else {
                let paginationHtml = '';

                if (currentPage > 1) {
                    paginationHtml += `<button class="pagination-btn" onclick="window.location.href='${PaginationManager.getPaginationUrl(1, baseRoute)}'"><i class="fas fa-angle-double-left"></i></button>`;
                    paginationHtml += `<button class="pagination-btn" onclick="window.location.href='${PaginationManager.getPaginationUrl(currentPage - 1, baseRoute)}'"><i class="fas fa-angle-left"></i></button>`;
                } else {
                    paginationHtml += '<button class="pagination-btn" disabled><i class="fas fa-angle-double-left"></i></button>';
                    paginationHtml += '<button class="pagination-btn" disabled><i class="fas fa-angle-left"></i></button>';
                }

                let startPage = Math.max(1, currentPage - 4);
                let endPage = Math.min(lastPage, currentPage + 5);

                if (startPage > 1) {
                    paginationHtml += '<span class="pagination-ellipsis">...</span>';
                }

                for (let i = startPage; i <= endPage; i++) {
                    const isActive = i === currentPage ? 'active' : '';
                    paginationHtml += `<button class="pagination-btn page-number ${isActive}" onclick="window.location.href='${PaginationManager.getPaginationUrl(i, baseRoute)}'">${i}</button>`;
                }

                if (endPage < lastPage) {
                    paginationHtml += '<span class="pagination-ellipsis">...</span>';
                    paginationHtml += `<button class="pagination-btn" onclick="window.location.href='${PaginationManager.getPaginationUrl(lastPage, baseRoute)}'">${lastPage}</button>`;
                }

                if (currentPage < lastPage) {
                    paginationHtml += `<button class="pagination-btn" onclick="window.location.href='${PaginationManager.getPaginationUrl(currentPage + 1, baseRoute)}'"><i class="fas fa-angle-right"></i></button>`;
                    paginationHtml += `<button class="pagination-btn" onclick="window.location.href='${PaginationManager.getPaginationUrl(lastPage, baseRoute)}'"><i class="fas fa-angle-double-right"></i></button>`;
                } else {
                    paginationHtml += '<button class="pagination-btn" disabled><i class="fas fa-angle-right"></i></button>';
                    paginationHtml += '<button class="pagination-btn" disabled><i class="fas fa-angle-double-right"></i></button>';
                }

                controls.innerHTML = paginationHtml;
                console.log(` Paginación simple generada: ${lastPage} página(s)`);
            }
        },

        // Obtener URL de paginación
        getPaginationUrl: (page, baseRoute) => {
            const url = new URL(globalThis.location);
            const params = new URLSearchParams(url.search);
            params.set('page', page);
            return `${baseRoute}?${params}`;
        },

        // Actualizar URL
        updateUrl: (queryString) => {
            globalThis.history.pushState(null, '', `${globalThis.location.pathname}?${queryString}`);
        }
    };
})();

globalThis.PaginationManager = PaginationManager;
