/**
 * Paginación AJAX para recibos de costura y reflectivo
 * Maneja la navegación entre páginas sin recargar la página completa
 */

document.addEventListener('DOMContentLoaded', function() {
    // Interceptar clics en enlaces de paginación
    const paginationWrapper = document.getElementById('pagination-wrapper');
    if (paginationWrapper) {
        paginationWrapper.addEventListener('click', function(e) {
            const link = e.target.closest('.page-link');
            if (link && !link.closest('.page-item.disabled') && !link.closest('.page-item.active')) {
                e.preventDefault();
                const url = link.getAttribute('href');
                if (url) {
                    loadPage(url);
                }
            }
        });
    }
});

/**
 * Cargar una página específica vía AJAX
 * @param {string} url - URL de la página a cargar
 */
function loadPage(url) {
    const tableBody = document.getElementById('tablaRecibosBody');
    const paginationContainer = document.querySelector('.pagination-container');

    // Mostrar estado de carga
    if (tableBody) tableBody.classList.add('pagination-loading');
    if (paginationContainer) paginationContainer.classList.add('pagination-loading');

    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'text/html'
        }
    })
    .then(response => response.text())
    .then(html => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        // Reemplazar el cuerpo de la tabla
        const newTableBody = doc.getElementById('tablaRecibosBody');
        if (newTableBody && tableBody) {
            tableBody.innerHTML = newTableBody.innerHTML;
            tableBody.classList.remove('pagination-loading');
        }

        // Reemplazar la paginación
        const newPagination = doc.querySelector('.pagination-wrapper');
        if (newPagination && paginationContainer) {
            const oldPagination = paginationContainer.querySelector('.pagination-wrapper');
            if (oldPagination) {
                oldPagination.innerHTML = newPagination.innerHTML;
            }
            paginationContainer.classList.remove('pagination-loading');

            // Actualizar información de registros
            const newInfo = doc.querySelector('.pagination-info');
            const oldInfo = paginationContainer.querySelector('.pagination-info');
            if (newInfo && oldInfo) {
                oldInfo.textContent = newInfo.textContent;
            }
        }

        // Re-inicializar event listeners para la nueva paginación
        reinitializePaginationListeners();

        // Cargar nombres de prendas para las nuevas filas
        loadPrendaNamesForNewRows();
    })
    .catch(error => {
        console.error('Error al cargar página:', error);
        if (tableBody) tableBody.classList.remove('pagination-loading');
        if (paginationContainer) paginationContainer.classList.remove('pagination-loading');
    });
}

/**
 * Re-inicializar los event listeners de paginación después de cargar nueva página
 */
function reinitializePaginationListeners() {
    const paginationWrapper = document.getElementById('pagination-wrapper');
    if (paginationWrapper) {
        // Remover listeners antiguos clonando
        const newWrapper = paginationWrapper.cloneNode(true);
        paginationWrapper.parentNode.replaceChild(newWrapper, paginationWrapper);

        // Agregar nuevo listener
        newWrapper.addEventListener('click', function(e) {
            const link = e.target.closest('.page-link');
            if (link && !link.closest('.page-item.disabled') && !link.closest('.page-item.active')) {
                e.preventDefault();
                const url = link.getAttribute('href');
                if (url) {
                    loadPage(url);
                }
            }
        });
    }
}

/**
 * Cargar nombres de prendas para las filas nuevas después de paginación
 */
function loadPrendaNamesForNewRows() {
    const filasRecibos = document.querySelectorAll('#tablaRecibosBody tr[data-orden-id]:not([data-nombres-cargados])');

    filasRecibos.forEach(fila => {
        const reciboId = fila.getAttribute('data-orden-id');
        const descripcionElemento = fila.querySelector('.descripcion-prenda-texto');

        if (descripcionElemento) {
            const enlacePedido = fila.querySelector('a[href*="/registros/"]');
            let pedidoProduccionId = null;

            if (enlacePedido) {
                const href = enlacePedido.getAttribute('href');
                const match = href.match(/\/registros\/(\d+)/);
                if (match) {
                    pedidoProduccionId = match[1];
                }
            }

            if (pedidoProduccionId) {
                fetch(`/api/pedidos/${pedidoProduccionId}/prendas`)
                    .then(response => response.json())
                    .then(datos => {
                        if (datos.data && typeof datos.data === 'object') {
                            datos = datos.data;
                        }

                        if (datos.prendas && Array.isArray(datos.prendas) && datos.prendas.length > 0) {
                            const primeraPrenda = datos.prendas[0];
                            const nombrePrenda = primeraPrenda.nombre || primeraPrenda.nombre_prenda || 'Sin nombre';
                            descripcionElemento.textContent = nombrePrenda;
                        } else {
                            descripcionElemento.textContent = 'Sin prendas';
                        }
                        fila.setAttribute('data-nombres-cargados', 'true');
                    })
                    .catch(error => {
                        console.error(`[CargarNombres] Error cargando prenda para recibo ${reciboId}:`, error);
                        descripcionElemento.textContent = 'Error';
                    });
            } else {
                descripcionElemento.textContent = 'Sin pedido';
                fila.setAttribute('data-nombres-cargados', 'true');
            }
        }
    });
}
