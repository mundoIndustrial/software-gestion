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
                console.log(`✅ Paginación actualizada: ${newText}`);
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
                console.log(`✅ Paginación del backend utilizada`);
            } else {
                let paginationHtml = '<nav aria-label="Page navigation"><ul class="pagination">';

                if (currentPage > 1) {
                    paginationHtml += `<li class="page-item"><a class="page-link" href="${PaginationManager.getPaginationUrl(1, baseRoute)}">Primera</a></li>`;
                    paginationHtml += `<li class="page-item"><a class="page-link" href="${PaginationManager.getPaginationUrl(currentPage - 1, baseRoute)}">Anterior</a></li>`;
                } else {
                    paginationHtml += '<li class="page-item disabled"><span class="page-link">Primera</span></li>';
                    paginationHtml += '<li class="page-item disabled"><span class="page-link">Anterior</span></li>';
                }

                let startPage = Math.max(1, currentPage - 4);
                let endPage = Math.min(lastPage, currentPage + 5);

                if (startPage > 1) {
                    paginationHtml += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }

                for (let i = startPage; i <= endPage; i++) {
                    const isActive = i === currentPage ? 'active' : '';
                    paginationHtml += `<li class="page-item ${isActive}"><a class="page-link" href="${PaginationManager.getPaginationUrl(i, baseRoute)}">${i}</a></li>`;
                }

                if (endPage < lastPage) {
                    paginationHtml += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    paginationHtml += `<li class="page-item"><a class="page-link" href="${PaginationManager.getPaginationUrl(lastPage, baseRoute)}">Última</a></li>`;
                }

                if (currentPage < lastPage) {
                    paginationHtml += `<li class="page-item"><a class="page-link" href="${PaginationManager.getPaginationUrl(currentPage + 1, baseRoute)}">Siguiente</a></li>`;
                    paginationHtml += `<li class="page-item"><a class="page-link" href="${PaginationManager.getPaginationUrl(lastPage, baseRoute)}">Última</a></li>`;
                } else {
                    paginationHtml += '<li class="page-item disabled"><span class="page-link">Siguiente</span></li>';
                    paginationHtml += '<li class="page-item disabled"><span class="page-link">Última</span></li>';
                }

                paginationHtml += '</ul></nav>';

                controls.innerHTML = paginationHtml;
                console.log(`✅ Paginación simple generada: ${lastPage} página(s)`);
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
