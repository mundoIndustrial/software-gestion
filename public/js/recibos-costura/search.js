/**
 * Búsqueda AJAX para recibos de costura/reflectivo
 * Funciona sin recargar la página y con paginación
 */

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('navSearchInput');
    const searchClearBtn = document.getElementById('navSearchClear');
    const tbody = document.getElementById('tablaRecibosBody');
    const paginationWrapper = document.getElementById('pagination-wrapper');
    const paginationInfo = document.querySelector('.pagination-info');
    
    let searchTimeout;
    let currentSearchTerm = '';
    let currentPage = 1;
    let originalTableHTML = '';
    let originalPaginationHTML = '';
    let originalPaginationInfoHTML = '';
    let searchUrl = '';

    // Determinar la URL de búsqueda según la página actual
    if (window.location.pathname === '/recibos-costura') {
        searchUrl = '/recibos-costura/search';
    } else if (window.location.pathname === '/recibos-reflectivo') {
        searchUrl = '/recibos-reflectivo/search';
    } else if (window.location.pathname === '/recibos-bordado-estampado') {
        searchUrl = '/recibos-bordado-estampado/search';
    } else {
        return; // No estamos en una página de recibos
    }

    // Guardar el HTML original de la tabla y paginación
    if (tbody) {
        originalTableHTML = tbody.innerHTML;
    }
    if (paginationWrapper) {
        originalPaginationHTML = paginationWrapper.innerHTML;
    }
    if (paginationInfo) {
        originalPaginationInfoHTML = paginationInfo.innerHTML;
    }

    if (!searchInput || !tbody) {
        console.warn('Elementos de búsqueda no encontrados');
        return;
    }

    // Función para realizar la búsqueda
    function performSearch(term, page = 1) {
        if (!term || term.trim() === '') {
            // Si no hay término, restaurar el HTML original sin recargar
            if (tbody) {
                tbody.innerHTML = originalTableHTML;
            }
            if (paginationWrapper) {
                paginationWrapper.innerHTML = originalPaginationHTML;
                paginationWrapper.style.display = originalPaginationHTML ? 'block' : 'none';
            }
            if (paginationInfo) {
                paginationInfo.innerHTML = originalPaginationInfoHTML;
                paginationInfo.style.display = originalPaginationInfoHTML ? 'block' : 'none';
            }
            currentSearchTerm = '';
            currentPage = 1;
            return;
        }

        // Mostrar indicador de carga
        tbody.innerHTML = `
            <tr>
                <td colspan="12" style="text-align: center; padding: 3rem;">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 12px; color: #3b82f6;">
                        <div class="spinner-border" role="status" style="width: 1.5rem; height: 1.5rem;"></div>
                        <span style="font-weight: 500;">Buscando...</span>
                    </div>
                </td>
            </tr>
        `;

        const params = new URLSearchParams({
            search: term,
            page: page
        });

        fetch(`${searchUrl}?${params}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentSearchTerm = term;
                currentPage = page;
                
                // Debug: Ver estructura de datos
                console.log('Datos recibidos:', data.recibos[0]);
                console.log('Cliente:', data.recibos[0].pedido_info?.cliente);
                console.log('Descripción:', data.recibos[0].descripcion_detallada);
                console.log('Fecha estimada:', data.recibos[0].fecha_estimada_de_entrega);
                console.log('Fecha creación:', data.recibos[0].created_at);
                console.log('Encargado:', data.recibos[0].encargado_orden);
                console.log('Novedades:', data.recibos[0].novedades);
                
                const paginationData = {
                    current_page: data.current_page || 1,
                    last_page: data.last_page || 1,
                    from: data.from || 0,
                    to: data.to || 0
                };
                
                renderResults(data.recibos, data.total, term, paginationData);
            } else {
                showError('Error al realizar la búsqueda');
            }
        })
        .catch(error => {
            console.error('Error en búsqueda:', error);
            showError('Error al realizar la búsqueda');
        });
    }

    // Función para renderizar los resultados
    function renderResults(recibos, total, searchTerm, paginationData = {}) {
        const tbody = document.getElementById('tablaRecibosBody');
        if (!tbody) return;

        if (!recibos || recibos.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="12" style="text-align: center; padding: 3rem;">
                        <div style="color: #64748b;">
                            <i class="fas fa-search" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                            <p style="margin: 0; font-weight: 500;">No se encontraron resultados para "${searchTerm}"</p>
                        </div>
                    </td>
                </tr>
            `;
            
            // Ocultar paginación
            if (paginationWrapper) paginationWrapper.style.display = 'none';
            if (paginationInfo) paginationInfo.style.display = 'none';
            return;
        }

        // Aquí necesitamos usar el mismo HTML que el blade
        // Por ahora, vamos a usar una versión simplificada
        // En una implementación completa, deberíamos reutilizar el componente blade
        tbody.innerHTML = recibos.map(recibo => {
            return generateRowHTML(recibo);
        }).join('');

        // Actualizar información de paginación
        if (paginationInfo && paginationData.from && paginationData.to) {
            paginationInfo.textContent = `Mostrando ${paginationData.from} a ${paginationData.to} de ${total} registros`;
            paginationInfo.style.display = 'block';
        }

        // Renderizar controles de paginación si hay más de 25 resultados
        if (paginationWrapper && total > 25) {
            renderPagination(paginationData, searchTerm);
            paginationWrapper.style.display = 'block';
        } else {
            paginationWrapper.style.display = 'none';
        }
    }

    // Función simplificada para generar HTML de fila (debería coincidir con el blade)
    function generateRowHTML(recibo) {
        // Si estamos en la página de bordado-estampado, usar un template diferente
        if (window.location.pathname === '/recibos-bordado-estampado') {
            return generateBordadoEstampadoRowHTML(recibo);
        }

        const diasClase = getDiasClass(recibo.dias_calculados);

        return `
            <tr class="${diasClase}"
                data-orden-id="${recibo.id || ''}"
                data-pedido-id="${recibo.pedido_produccion_id || ''}"
                data-numero-recibo="${recibo.consecutivo_actual || ''}">
                
                <!-- Acciones -->
                <td class="acciones-column" style="text-align: center; position: relative;">
                    <button class="btn-ver-dropdown" 
                        title="Ver Opciones" 
                        data-menu-id="menu-recibo-${recibo.id || ''}"
                        data-pedido-id="${recibo.pedido_produccion_id || ''}"
                        data-prenda-id="${recibo.prenda_id || ''}"
                        data-numero-recibo="${recibo.consecutivo_actual || ''}"
                        data-tipo-recibo="${recibo.tipo_recibo || 'COSTURA'}"
                        data-es-parcial="${(recibo.es_parcial || recibo.esParcial) ? 'true' : 'false'}"
                        data-pedido-parcial-id="${recibo.pedido_parcial_id || recibo.pedidoParcialId || recibo.parcial_id || ''}"
                        data-recibo-id="${recibo.id || ''}"
                        data-tiene-parciales="${recibo.tiene_parciales ? 'true' : 'false'}"
                        data-total-parciales="${recibo.total_parciales || 0}">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
                
                <!-- Estado del Recibo (Badge) -->
                <td style="white-space: nowrap;">
                    ${getEstadoBadgeHTML(recibo.estado)}
                </td>
                
                <!-- Área del Recibo -->
                <td>
                    ${getAreaBadgeHTML(recibo)}
                </td>
                
                <!-- Total de días -->
                <td style="text-align: center;">
                    ${getDiasBadgeHTML(recibo.dias_calculados)}
                </td>
                
                <!-- Número de recibo -->
                <td style="text-align: center; font-weight: 600;">
                    ${recibo.consecutivo_actual || '-'}
                </td>
                
                <!-- Cliente -->
                <td>
                    ${recibo.pedido_info?.cliente || '-'}
                </td>
                
                <!-- Descripción -->
                <td data-descripcion-detallada="${recibo.descripcion_detallada || ''}">
                    ${recibo.descripcion_detallada || '-'}
                </td>
                
                <!-- Cantidad -->
                <td style="text-align: center;">
                    ${recibo.cantidad_total || 0}
                </td>
                
                <!-- Novedades -->
                <td>
                    <div class="table-cell" style="flex: 0 0 120px;">
                        <div class="cell-content" style="justify-content: flex-start;">
                            <button 
                                class="btn-edit-novedades"
                                data-pedido-id="${recibo.pedido_produccion_id || ''}"
                                data-numero-recibo="${recibo.consecutivo_actual || ''}"
                                data-novedades="${(recibo.novedades || '').replace(/'/g, "\\'").replace(/"/g, '&quot;')}"
                                onclick="event.stopPropagation(); openNovedadesModalRecibo(this)"
                                title="Ver novedades del recibo"
                                type="button">
                                ${recibo.novedades && recibo.novedades !== 'Sin novedades' ? 
                                    `<span class="novedades-text">${recibo.novedades.length > 50 ? recibo.novedades.substring(0, 50) + '...' : recibo.novedades}</span>` : 
                                    `<span class="novedades-text empty">Sin novedades</span>`
                                }
                                <span class="material-symbols-rounded">edit</span>
                            </button>
                        </div>
                    </div>
                </td>
                
                <!-- Fecha creación -->
                <td>
                    ${formatFechaHTML(recibo.created_at)}
                </td>
                
                <!-- Fecha estimada entrega -->
                <td>
                    ${formatFechaEstimadaHTML(recibo.fecha_estimada_de_entrega)}
                </td>
                
                <!-- Encargado orden -->
                <td>
                    <span style="font-weight: 600; color: #374151;">${recibo.encargado_orden || '-'}</span>
                </td>
            </tr>
        `;
    }

    // Función para generar HTML de fila para bordado/estampado (8 columnas específicas)
    function generateBordadoEstampadoRowHTML(recibo) {
        // Determinación del tipo (BORDADO o ESTAMPADO)
        const tipo = recibo.tipo_recibo ? recibo.tipo_recibo.toUpperCase() : 'BORDADO';
        const tipoBadgeColors = { BORDADO: '#2563eb', ESTAMPADO: '#0f766e', DTF: '#7c3aed', SUBLIMADO: '#ea580c' };
        const tipoBadgeColor = tipoBadgeColors[tipo] || '#475569';

        // Determinación del área
        let areaRecibo = recibo.area || 'Pendiente';
        let areaBadge = 'bg-secondary';
        if (areaRecibo.includes('Corte')) {
            areaBadge = 'bg-success';
        } else if (areaRecibo.includes('Estampado')) {
            areaBadge = 'bg-warning';
        } else if (areaRecibo.includes('Bordado')) {
            areaBadge = 'bg-purple';
        } else if (areaRecibo.includes('Pendiente')) {
            areaBadge = 'bg-info';
        }

        return `
            <tr data-orden-id="${recibo.id || ''}"
                data-pedido-id="${recibo.pedido_produccion_id || ''}"
                data-numero-recibo="${recibo.consecutivo_actual || ''}">

                <!-- Acciones -->
                <td class="acciones-column" style="text-align: center; position: relative;">
                    <button class="btn-ver-dropdown"
                        title="Ver Opciones"
                        data-menu-id="menu-recibo-${recibo.id || ''}"
                        data-pedido-id="${recibo.pedido_produccion_id || ''}"
                        data-prenda-id="${recibo.prenda_id || ''}"
                        data-numero-recibo="${recibo.consecutivo_actual || ''}"
                        data-tipo-recibo="${tipo}"
                        data-es-parcial="false"
                        data-recibo-id="${recibo.id || ''}">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>

                <!-- Área -->
                <td>
                    <span class="badge ${areaBadge}" style="display: inline-block;">
                        ${areaRecibo}
                    </span>
                </td>

                <!-- N° Recibo -->
                <td style="text-align: center;">
                    <span style="font-weight: 600;">${recibo.consecutivo_actual || '-'}</span>
                </td>

                <!-- Tipo de Recibo -->
                <td style="text-align: center;">
                    <span style="display:inline-block;padding:3px 8px;border-radius:999px;background:${tipoBadgeColor};color:#fff;font-size:11px;font-weight:700;letter-spacing:.3px;">
                        ${tipo}
                    </span>
                </td>

                <!-- Cliente -->
                <td style="text-align: center;">
                    ${recibo.pedido_info?.cliente ? `<span>${recibo.pedido_info.cliente}</span>` : '<span class="text-muted">N/A</span>'}
                </td>

                <!-- Descripción -->
                <td data-descripcion-detallada="${recibo.descripcion_detallada || ''}">
                    <div class="table-cell" style="flex: 10;">
                        <div class="cell-content" style="justify-content: flex-start;">
                            <span style="color: #6b7280; font-size: 0.875rem; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                ${recibo.descripcion_detallada ? recibo.descripcion_detallada.substring(0, 50) + (recibo.descripcion_detallada.length > 50 ? '...' : '') : 'Sin prenda'}
                            </span>
                        </div>
                    </div>
                </td>

                <!-- Cantidad -->
                <td>
                    ${recibo.cantidad_total && recibo.cantidad_total > 0 ? `<span style="font-weight: 600; color: #059669;">${recibo.cantidad_total}</span>` : '<span class="text-muted">-</span>'}
                </td>

                <!-- Fecha de creación -->
                <td>
                    ${recibo.pedido_info && recibo.pedido_info.fecha_creacion_orden ? formatFechaHTML(recibo.pedido_info.fecha_creacion_orden) : '<span class="text-muted">-</span>'}
                </td>
            </tr>
        `;
    }

    // Función para obtener la clase CSS según los días
    function getDiasClass(dias) {
        if (!dias || dias === 0) return '';
        if (dias >= 14) return 'dias-mayor-15';
        if (dias >= 10) return 'dias-10-15';
        if (dias >= 5) return 'dias-5-9';
        return 'dias-0-4';
    }

    // Función para generar badge de estado (igual que el blade)
    function getEstadoBadgeHTML(estado) {
        let badgeClass = 'bg-secondary';
        let estadoTexto = estado || 'Desconocido';
        
        if (estado === 'Entregado') {
            badgeClass = 'bg-success';
        } else if (estado === 'En Ejecución') {
            badgeClass = 'bg-primary';
        } else if (estado === 'No iniciado') {
            badgeClass = 'bg-warning';
        } else if (estado === 'PENDIENTE_INSUMOS') {
            badgeClass = 'bg-info';
            estadoTexto = 'Pendiente';
        }
        
        return `<span class="badge ${badgeClass}" style="white-space: nowrap; display: inline-block;">${estadoTexto}</span>`;
    }

    // Función para generar badge de área (igual que el blade)
    function getAreaBadgeHTML(recibo) {
        const areaRecibo = recibo.area || recibo.pedido_info?.area || 'Insumos';
        const puedeAgregarProceso = areaRecibo.toLowerCase().includes('corte');
        let areaBadge = 'bg-secondary';
        
        if (areaRecibo.includes('Corte')) {
            areaBadge = 'bg-success';
        } else if (areaRecibo.includes('Insumos')) {
            areaBadge = 'bg-info';
        } else if (areaRecibo.includes('Costura')) {
            areaBadge = 'bg-primary';
        } else if (areaRecibo.includes('Estampado')) {
            areaBadge = 'bg-warning';
        } else if (areaRecibo.includes('Bordado')) {
            areaBadge = 'bg-purple';
        }
        
        const onclickAttr = puedeAgregarProceso ? 
            `onclick="abrirModalAgregarProcesoDesdeArea('${areaRecibo}', ${recibo.pedido_produccion_id || 'null'}, ${recibo.prenda_id || 'null'}, ${recibo.consecutivo_actual || 'null'})"` : '';
        const cursorStyle = puedeAgregarProceso ? 'pointer' : 'default';
        const titleAttr = puedeAgregarProceso ? 'Click para agregar proceso' : 'Área actual sin acción disponible';
        
        return `<span class="badge ${areaBadge} area-badge-clickable"
                  style="cursor: ${cursorStyle}; transition: all 0.2s ease;"
                  title="${titleAttr}"
                  ${onclickAttr}
                  onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.2)';"
                  onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none';">
                  ${areaRecibo}
              </span>`;
    }

    // Función para generar badge de días (igual que el blade)
    function getDiasBadgeHTML(dias) {
        if (!dias || dias === 0) {
            return '<span class="badge bg-secondary" style="font-weight: 600;">0 días</span>';
        }
        
        let badgeClass = 'bg-success';
        if (dias >= 14) {
            badgeClass = 'bg-danger';
        } else if (dias >= 10) {
            badgeClass = 'bg-warning';
        } else if (dias >= 5) {
            badgeClass = 'bg-info';
        }
        
        return `<span class="badge ${badgeClass}" style="font-weight: 600;">${dias} días</span>`;
    }

    // Función para generar badge de novedades (igual que el blade)
    function getNovedadesBadgeHTML(novedades) {
        if (!novedades || novedades === 'Sin novedades') {
            return '<span class="badge bg-light text-dark" style="font-weight: 500;">Sin novedades</span>';
        }
        
        let badgeClass = 'bg-warning';
        if (novedades.includes('Urgente') || novedades.includes('CRITICO')) {
            badgeClass = 'bg-danger';
        } else if (novedades.includes('Revisión')) {
            badgeClass = 'bg-info';
        }
        
        return `<span class="badge ${badgeClass}" style="font-weight: 500;">${novedades}</span>`;
    }

    // Función para formatear fecha (igual que el blade)
    function formatFechaHTML(fecha) {
        if (!fecha) return '<span class="text-muted">-</span>';
        
        try {
            const date = new Date(fecha);
            if (isNaN(date.getTime())) return '<span class="text-muted">-</span>';
            
            const opciones = { day: '2-digit', month: '2-digit', year: 'numeric' };
            return date.toLocaleDateString('es-ES', opciones);
        } catch (e) {
            return '<span class="text-muted">-</span>';
        }
    }

    // Función para formatear fecha estimada (igual que el blade)
    function formatFechaEstimadaHTML(fecha) {
        if (!fecha) return '<span class="fecha-estimada-span text-muted">-</span>';
        
        try {
            const date = new Date(fecha);
            if (isNaN(date.getTime())) return '<span class="fecha-estimada-span text-muted">-</span>';
            
            const opciones = { day: '2-digit', month: '2-digit', year: 'numeric' };
            const fechaFormateada = date.toLocaleDateString('es-ES', opciones);
            
            return `<span class="fecha-estimada-span" style="font-weight: 500; color: #374151;">${fechaFormateada}</span>`;
        } catch (e) {
            return '<span class="fecha-estimada-span text-muted">-</span>';
        }
    }

    // Función para renderizar paginación
    function renderPagination(paginationData, searchTerm) {
        if (!paginationWrapper) return;

        const currentPage = paginationData.current_page || 1;
        const lastPage = paginationData.last_page || 1;
        const totalPages = lastPage;

        let html = '<ul class="pagination">';

        // Botón anterior
        if (currentPage > 1) {
            html += `<li class="page-item">
                <a class="page-link" href="#" data-page="${currentPage - 1}" data-search="${searchTerm}">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>`;
        } else {
            html += `<li class="page-item disabled">
                <span class="page-link"><i class="fas fa-chevron-left"></i></span>
            </li>`;
        }

        // Páginas
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                if (i === currentPage) {
                    html += `<li class="page-item active">
                        <span class="page-link">${i}</span>
                    </li>`;
                } else {
                    html += `<li class="page-item">
                        <a class="page-link" href="#" data-page="${i}" data-search="${searchTerm}">${i}</a>
                    </li>`;
                }
            } else if (i === currentPage - 2 || i === currentPage + 2) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }

        // Botón siguiente
        if (currentPage < lastPage) {
            html += `<li class="page-item">
                <a class="page-link" href="#" data-page="${currentPage + 1}" data-search="${searchTerm}">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>`;
        } else {
            html += `<li class="page-item disabled">
                <span class="page-link"><i class="fas fa-chevron-right"></i></span>
            </li>`;
        }

        html += '</ul>';
        paginationWrapper.innerHTML = html;

        // Agregar event listeners a los enlaces de paginación
        paginationWrapper.querySelectorAll('a.page-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = this.dataset.page;
                const search = this.dataset.search;
                performSearch(search, page);
            });
        });
    }

    // Función para mostrar error
    function showError(message) {
        const tbody = document.getElementById('tablaRecibosBody');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="12" style="text-align: center; padding: 3rem;">
                        <div style="color: #ef4444;">
                            <i class="fas fa-exclamation-circle" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                            <p style="margin: 0; font-weight: 500;">${message}</p>
                        </div>
                    </td>
                </tr>
            `;
        }
    }

    // Event listener para input de búsqueda
    searchInput.addEventListener('input', function() {
        const term = this.value.trim();
        
        // Mostrar/ocultar botón de limpiar
        if (searchClearBtn) {
            searchClearBtn.style.display = term ? 'flex' : 'none';
        }

        // Debounce para evitar muchas peticiones
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            performSearch(term, 1);
        }, 300);
    });

    // Event listener para botón de limpiar
    if (searchClearBtn) {
        searchClearBtn.addEventListener('click', function() {
            searchInput.value = '';
            this.style.display = 'none';
            performSearch('', 1);
        });
    }

    // Event listener para Enter
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const term = this.value.trim();
            performSearch(term, 1);
        }
    });
});


