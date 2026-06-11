function getProgressColor(percentage) {
    if (percentage >= 80) return '#10b981';
    if (percentage >= 50) return '#f59e0b';
    return '#ef4444';
}

function formatFechaSalida(fechaSalida) {
    if (!fechaSalida) return '-';

    const fecha = new Date(fechaSalida);
    if (Number.isNaN(fecha.getTime())) {
        return String(fechaSalida);
    }

    return new Intl.DateTimeFormat('es-CO', {
        dateStyle: 'medium',
        timeStyle: 'short'
    }).format(fecha);
}

function formatFechaAcordeon(fecha) {
    if (!fecha) return '-';

    const texto = String(fecha).trim();
    const fechaPartes = texto.split(',');
    const fechaSolo = fechaPartes[0].trim();
    const horaTexto = fechaPartes.slice(1).join(',').trim();
    const partes = fechaSolo.split(/[/-]/).map(parte => parte.trim()).filter(Boolean);

    if (partes.length === 3) {
        if (partes[0].length === 4) {
            const [ano, mes, dia] = partes;
            return horaTexto
                ? `${dia.padStart(2, '0')}/${mes.padStart(2, '0')}/${ano} ${horaTexto}`
                : `${dia.padStart(2, '0')}/${mes.padStart(2, '0')}/${ano}`;
        }

        const [dia, mes, ano] = partes;
        return horaTexto
            ? `${dia.padStart(2, '0')}/${mes.padStart(2, '0')}/${ano} ${horaTexto}`
            : `${dia.padStart(2, '0')}/${mes.padStart(2, '0')}/${ano}`;
    }

    return horaTexto ? `${fechaSolo} ${horaTexto}` : fechaSolo;
}

function syncOrdenesTabButtons(activeTab) {
    const tabs = document.querySelectorAll('.ordenes-tab-btn');
    tabs.forEach((tabBtn) => {
        const isActive = tabBtn.dataset.tab === activeTab;
        tabBtn.classList.toggle('active', isActive);
    });
}

function initOrdenesTabs() {
    const tabs = document.querySelectorAll('.ordenes-tab-btn');
    const searchInput = document.getElementById('searchInput');

    tabs.forEach((tabBtn) => {
        tabBtn.addEventListener('click', () => {
            const activeTab = tabBtn.dataset.tab === 'bodega' ? 'bodega' : 'pedidos';
            const searchTerm = searchInput ? searchInput.value.trim() : '';
            const url = new URL(window.location.href);

            currentState.ordenesTab = activeTab;
            syncOrdenesTabButtons(activeTab);
            url.searchParams.set('view', 'ordenes');
            url.searchParams.set('tab', activeTab);
            url.searchParams.delete('status');
            if (searchTerm) url.searchParams.set('search', searchTerm);
            else url.searchParams.delete('search');
            url.searchParams.delete('page');

            window.history.pushState({ view: 'ordenes', tab: activeTab }, '', url.toString());
            showOrdenes(searchTerm, 1, activeTab);
        });
    });
}

function showOrdenes(search = '', page = 1, tab = 'pedidos') {
    const ordenesTab = tab === 'bodega' ? 'bodega' : 'pedidos';
    currentState.ordenesTab = ordenesTab;
    switchView('ordenes');
    setTalleresTopNavVisible(true);
    syncOrdenesTabButtons(ordenesTab);
    const mainContainer = document.querySelector('.main-container');
    const ordenesContent = document.getElementById('ordenesContent');
    const ordenesTitle = document.querySelector('#viewOrdenes .card-header h2');
    const apiRoute = mainContainer.dataset.routeApiOrdenes;

    if (ordenesContent) {
        ordenesContent.innerHTML = '<div class="loading"><div class="loading-spinner"></div><p>Cargando órdenes...</p></div>';
    }
    if (ordenesTitle) {
        ordenesTitle.textContent = ordenesTab === 'bodega'
            ? 'Listado de Órdenes de Bodega'
            : 'Listado de Órdenes de Pedidos';
    }

    // Construir URL con parámetros
    const url = new URL(apiRoute, window.location.origin);
    url.searchParams.append('tab', ordenesTab);
    if (search) url.searchParams.append('search', search);
    url.searchParams.append('page', page);

    fetch(url.toString())
        .then(response => response.json())
        .then(data => {
            if (!data.ordenes || data.ordenes.length === 0) {
                let html = '<div class="empty-state"><div class="empty-state-icon">📦</div><p>No hay órdenes asignadas a talleres.</p></div>';
                
                // Agregar controles de paginación si hay búsqueda
                if (search || page > 1) {
                    html += renderPaginationControls(data.pagination, search, ordenesTab);
                }
                
                ordenesContent.innerHTML = html;
                return;
            }

            let html = '<div class="table-container"><table class="table-ordenes"><thead><tr><th class="col-acciones">ACCIONES</th><th class="col-numero">Nº ORDEN</th><th>DESCRIPCIÓN</th><th>FECHA SALIDA</th><th class="col-cantidad">CANT. TOTAL</th><th>PROGRESO TOTAL</th><th>ENCARGADO</th><th>DISTRIBUCIÓN</th></tr></thead><tbody>';

            data.ordenes.forEach(orden => {
                const rowClass = orden.es_dividido ? 'orden-dividida' : '';
                const tipoRecibo = String(orden.tipo_recibo || '').trim().toUpperCase();
                const etiquetaOrden = tipoRecibo === 'CORTE-PARA-BODEGA' ? 'Bodega' : 'Pedido';
                
                // Fila principal
                html += `
                    <tr class="${rowClass}" data-orden-id="${orden.id}">
                        <td class="col-acciones">
                            <button class="btn-ver-recibo-completo"
                                data-numero-recibo="${orden.numero_recibo}"
                                data-tipo-recibo="${orden.tipo_recibo}"
                                data-pedido-produccion-id="${orden.pedido_produccion_id ?? ''}"
                                data-prenda-id="${orden.prenda_id ?? ''}"
                                title="Ver recibo completo">
                                <span class="material-symbols-rounded">visibility</span>
                            </button>
                        </td>
                        <td class="col-numero"><strong>${etiquetaOrden} #${orden.numero_recibo}</strong></td>
                        <td>
                            <div class="prenda-nombre">${orden.descripcion}</div>
                            <p class="prenda-desc">${orden.cliente}</p>
                        </td>
                        <td class="col-fecha-salida">${formatFechaSalida(orden.fecha_salida)}</td>
                        <td class="col-cantidad">${orden.cantidad_total}</td>
                        <td>
                            <div class="progress-container">
                                <div class="progress-info">
                                    <span class="progress-text">${orden.cantidad_entregada} / ${orden.cantidad_total}</span>
                                    <span class="progress-percentage">${orden.porcentaje}%</span>
                                </div>
                                <div class="progress-bar-wrapper">
                                    <div class="progress-bar-fill" style="width: ${orden.porcentaje}%; background: ${getProgressColor(orden.porcentaje)}"></div>
                                </div>
                            </div>
                        </td>
                        <td class="col-encargado">
                            <span class="encargado-badge">${orden.encargado_display}</span>
                        </td>
                        <td class="col-distribucion">
                            ${orden.es_dividido ? 
                                `<button class="btn-ver-distribucion" data-orden-id="${orden.id}">
                                    <span class="material-symbols-rounded">expand_more</span>
                                    Ver Distribución
                                </button>` 
                                : 
                                `<span class="distribucion-badge completa">${orden.distribucion}</span>`
                            }
                        </td>
                    </tr>
                `;

                // Si está dividida, agregar fila expandible con distribución
                if (orden.es_dividido) {
                    html += `<tr class="distribucion-expandible" id="distribucion-${orden.id}" style="display: none;">
                        <td colspan="8">
                            <div class="distribucion-container">
                                <div class="distribucion-titulo">
                                    <span class="material-symbols-rounded">call_split</span>
                                    DISTRIBUCIÓN TÉCNICA DEL RECIBO ${orden.numero_recibo}
                                </div>
                                <div class="distribucion-ramas">`;
                    
                    // Agrupar por número de parte
                    const partesPorNumero = {};
                    orden.distribucion_detalles.forEach(detalle => {
                        if (!partesPorNumero[detalle.numero_recibo_parte]) {
                            partesPorNumero[detalle.numero_recibo_parte] = [];
                        }
                        partesPorNumero[detalle.numero_recibo_parte].push(detalle);
                    });
                    
                    // Renderizar cada parte con sus tallas como ramas
                    Object.keys(partesPorNumero).forEach(numeroParte => {
                        const tallas = partesPorNumero[numeroParte];
                        const reciboParcialId = tallas.find(t => t?.recibo_parcial_id)?.recibo_parcial_id || '';
                        const fechaSalidaParte = tallas.find(t => t?.fecha_salida)?.fecha_salida || '';
                        html += `
                            <div class="rama-parte">
                                <div class="rama-parte-header">
                                    <span class="rama-parte-numero">${numeroParte}</span>
                                    <span class="rama-parte-fecha" style="font-size: 12px; color: #64748b;">
                                        ${formatFechaSalida(fechaSalidaParte)}
                                    </span>
                                    ${reciboParcialId ? `
                                        <button
                                            type="button"
                                            class="btn-ver-recibo-parcial"
                                            data-recibo-parcial-id="${reciboParcialId}"
                                            data-pedido-produccion-id="${orden.pedido_produccion_id || ''}"
                                            data-prenda-id="${orden.prenda_id || ''}"
                                            data-numero-recibo="${orden.numero_recibo || ''}"
                                            data-tipo-recibo="${String(orden.tipo_recibo || '').trim().toUpperCase()}"
                                            title="Ver recibo parcial"
                                        >
                                            <span class="material-symbols-rounded">receipt_long</span>
                                        </button>
                                    ` : ''}
                                </div>
                                <div class="rama-tallas">`;
                        
                        tallas.forEach((detalle, index) => {
                            html += `
                                <div class="rama-talla-item">
                                    <div class="rama-talla-content">
                                        <span class="talla-nombre">${detalle.talla}</span>
                                        <span class="talla-cantidad">${detalle.cantidad}</span>
                                        <div class="talla-progreso">
                                            <span class="progreso-text">${detalle.cantidad_entregada} / ${detalle.cantidad}</span>
                                            <span class="progreso-percentage">${detalle.porcentaje}%</span>
                                            <div class="progress-bar-wrapper">
                                                <div class="progress-bar-fill" style="width: ${detalle.porcentaje}%; background: ${getProgressColor(detalle.porcentaje)}"></div>
                                            </div>
                                        </div>
                                        <span class="talla-encargado">${detalle.taller_nombre}</span>
                                    </div>
                                </div>
                            `;
                        });
                        
                        html += `
                                </div>
                            </div>
                        `;
                    });
                    
                    html += `
                                </div>
                            </div>
                        </td>
                    </tr>`;
                }
            });

            html += '</tbody></table></div>';
            
            // Agregar controles de paginación
            html += renderPaginationControls(data.pagination, search, ordenesTab);
            
            ordenesContent.innerHTML = html;

            // Inicializar eventos de distribución
            initDistribucionEvents();
            initReciboCompletoEvents();
            
            // Inicializar eventos de paginación
            initPaginationEvents(search, ordenesTab);
        })
        .catch(error => {
            console.error('Error:', error);
            ordenesContent.innerHTML = '<div class="empty-state"><p>Error al cargar las órdenes.</p></div>';
        });
}

function initDistribucionEvents() {
    const expandButtons = document.querySelectorAll('.btn-ver-distribucion');
    const reciboButtons = document.querySelectorAll('.btn-ver-recibo-parcial');
    
    expandButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const ordenId = this.dataset.ordenId;
            const expandibleRow = document.getElementById(`distribucion-${ordenId}`);
            
            if (expandibleRow) {
                const isVisible = expandibleRow.style.display !== 'none';
                expandibleRow.style.display = isVisible ? 'none' : 'table-row';
                this.classList.toggle('expanded');
            }
        });
    });

    reciboButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const parcialId = this.dataset.reciboParcialId;
            const tipoRecibo = String(this.dataset.tipoRecibo || '').trim().toUpperCase();
            const pedidoProduccionId = Number(this.dataset.pedidoProduccionId || 0);
            const prendaId = Number(this.dataset.prendaId || 0);
            const numeroRecibo = String(this.dataset.numeroRecibo || '').trim();

            if (!parcialId) return;

            const esTipoCostura = ['COSTURA', 'COSTURA-BODEGA'].includes(tipoRecibo);

            if (esTipoCostura && typeof window.pedidosRecibosModule?.abrirReciboParcial === 'function') {
                if (pedidoProduccionId > 0 && prendaId > 0) {
                    window.pedidosRecibosModule.abrirReciboParcial(
                        pedidoProduccionId,
                        prendaId,
                        'costura',
                        Number(parcialId),
                        numeroRecibo ? `COSTURA ANEXO ${numeroRecibo}` : 'COSTURA ANEXO'
                    );
                    return;
                }
            }

            if (tipoRecibo === 'CORTE-PARA-BODEGA' && typeof window.openReciboCorteBodegaParcialModal === 'function') {
                window.openReciboCorteBodegaParcialModal(parcialId, tipoRecibo);
            } else if (typeof window.openReciboCorteBodegaModal === 'function') {
                window.openReciboCorteBodegaModal(parcialId);
            } else {
                Swal.fire('Error', 'El modal de recibo no está disponible en esta vista.', 'error');
            }
        });
    });
}

function initReciboCompletoEvents() {
    const buttons = document.querySelectorAll('.btn-ver-recibo-completo');
    const mainContainer = document.querySelector('.main-container');
    const apiRoute = mainContainer?.dataset?.routeApiReciboCompleto;

    const aplicarNormalizacionModal = () => {
        normalizeCosturaModalForTalleres();
        requestAnimationFrame(() => normalizeCosturaModalForTalleres());
    };

    buttons.forEach(btn => {
        btn.addEventListener('click', async function() {
            const reciboId = String(this.dataset.reciboId || '').trim();
            const numeroRecibo = String(this.dataset.numeroRecibo || '').trim();
            const tipoRecibo = String(this.dataset.tipoRecibo || '').trim().toUpperCase();
            const pedidoProduccionId = String(this.dataset.pedidoProduccionId || '').trim();
            const prendaId = String(this.dataset.prendaId || '').trim();
            if (!numeroRecibo || !tipoRecibo) return;
            if (!['COSTURA', 'CORTE-PARA-BODEGA'].includes(tipoRecibo)) {
                Swal.fire('No disponible', 'Este recibo no se puede abrir desde la vista de Entrada.', 'info');
                return;
            }

            try {
                // COSTURA se abre con el modal completo de pedido (order-detail-modal-wrapper)
                if (tipoRecibo === 'COSTURA') {
                    if (
                        typeof window.pedidosRecibosModule !== 'undefined' &&
                        window.pedidosRecibosModule &&
                        typeof window.pedidosRecibosModule.abrirRecibo === 'function' &&
                        pedidoProduccionId &&
                        prendaId
                    ) {
                        window.pedidosRecibosModule.abrirRecibo(
                            Number(pedidoProduccionId),
                            Number(prendaId),
                            'costura'
                        );
                        applyReciboFechaToCosturaModal(numeroRecibo, tipoRecibo, apiRoute);
                        aplicarNormalizacionModal();
                        return;
                    }

                    if (typeof window.verFactura === 'function') {
                        window.verFactura(numeroRecibo);
                        applyReciboFechaToCosturaModal(numeroRecibo, tipoRecibo, apiRoute);
                        aplicarNormalizacionModal();
                        return;
                    }
                    const pedidoLimpio = numeroRecibo.replace('#', '');
                    let costuraResponse = await fetch(`/registros/${pedidoLimpio}`);
                    if (!costuraResponse.ok) {
                        costuraResponse = await fetch(`/orders/${pedidoLimpio}`);
                    }
                    if (!costuraResponse.ok) {
                        throw new Error('No se pudo cargar el recibo de costura');
                    }
                    const costuraData = await costuraResponse.json();
                    window.dispatchEvent(new CustomEvent('load-order-detail', { detail: costuraData }));
                    applyReciboFechaToCosturaModal(numeroRecibo, tipoRecibo, apiRoute);
                    aplicarNormalizacionModal();
                    return;
                }

                if (!apiRoute) {
                    throw new Error('Ruta de recibo completo no disponible');
                }

                const url = new URL(apiRoute, window.location.origin);
                if (reciboId) {
                    url.searchParams.set('recibo_id', reciboId);
                }
                url.searchParams.set('numero_recibo', numeroRecibo);
                url.searchParams.set('tipo_recibo', tipoRecibo);
                if (pedidoProduccionId) {
                    url.searchParams.set('pedido_produccion_id', pedidoProduccionId);
                }
                if (prendaId) {
                    url.searchParams.set('prenda_id', prendaId);
                }

                const response = await fetch(url.toString());
                const raw = await response.text();
                let data = null;
                try {
                    data = JSON.parse(raw);
                } catch (e) {
                    throw new Error(`Respuesta inválida del servidor (${response.status})`);
                }
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'No se pudo obtener el recibo');
                }

                if (typeof window.renderReciboCorteBodegaData === 'function') {
                    window.renderReciboCorteBodegaData(data);
                } else {
                    throw new Error('Modal no disponible');
                }
            } catch (error) {
                console.error('Error abriendo recibo completo:', error);
                Swal.fire('Error', error.message || 'No se pudo abrir el recibo', 'error');
            }
        });
    });
}

async function applyReciboFechaToCosturaModal(numeroRecibo, tipoRecibo, apiRoute) {
    if (!apiRoute) return;
    
    console.log('[applyReciboFechaToCosturaModal] Intentando aplicar fecha:', {
        numeroRecibo,
        tipoRecibo,
        apiRoute,
        esParcial: String(numeroRecibo || '').includes('.')
    });
    
    if (String(numeroRecibo || '').includes('.')) {
        console.log('[applyReciboFechaToCosturaModal] Es un recibo parcial - NO sobrescribir fecha (ya fue establecida por ReceiptRenderer)');
        return;
    }
    
    try {
        const url = new URL(apiRoute, window.location.origin);
        url.searchParams.set('numero_recibo', String(numeroRecibo || '').trim());
        url.searchParams.set('tipo_recibo', String(tipoRecibo || '').trim().toUpperCase());
        const response = await fetch(url.toString());
        const data = await response.json();
        if (!response.ok || !data?.success) return;

        const dia = String(data.dia || '').padStart(2, '0');
        const mes = String(data.mes || '').padStart(2, '0');
        const ano = String(data.ano || '');
        if (!dia || !mes || !ano) return;

        const paintFecha = () => {
            const wrapper = document.getElementById('order-detail-modal-wrapper');
            if (!wrapper) return false;

            const dayBox = wrapper.querySelector('.day-box');
            const monthBox = wrapper.querySelector('.month-box');
            const yearBox = wrapper.querySelector('.year-box');
            if (!dayBox || !monthBox || !yearBox) return false;

            dayBox.textContent = dia;
            monthBox.textContent = mes;
            yearBox.textContent = ano;
            return true;
        };

        paintFecha();
        setTimeout(paintFecha, 80);
        setTimeout(paintFecha, 220);
        setTimeout(paintFecha, 500);
        setTimeout(paintFecha, 900);
    } catch (error) {
        console.warn('No se pudo aplicar la fecha del recibo en modal de costura:', error);
    }
}

function normalizeCosturaModalForTalleres() {
    const rcbFloating = document.getElementById('rcb-floating-buttons');
    if (rcbFloating) {
        rcbFloating.classList.remove('is-visible');
    }

    const wrapper = document.getElementById('order-detail-modal-wrapper');
    if (wrapper) {
        wrapper.style.top = '50%';
        wrapper.style.maxHeight = '';
        wrapper.style.overflowY = 'visible';
        wrapper.style.overflowX = 'visible';
        wrapper.style.paddingRight = '0';
    }

    const btnFactura = document.getElementById('btn-factura');
    const btnGaleria = document.getElementById('btn-galeria');
    if (btnFactura) {
        btnFactura.title = 'Ver galería';
        btnFactura.innerHTML = '<i class="fas fa-images"></i>';
    }
    if (btnGaleria) {
        btnGaleria.style.display = 'none';
        btnGaleria.style.visibility = 'hidden';
        btnGaleria.style.zIndex = '-1';
    }
}

function renderPaginationControls(pagination, search, tab = 'pedidos') {
    const { current_page, last_page, total, per_page } = pagination;
    
    let html = '<div class="pagination-controls">';
    html += `<div class="pagination-info">Mostrando ${(current_page - 1) * per_page + 1} - ${Math.min(current_page * per_page, total)} de ${total} órdenes</div>`;
    html += '<div class="pagination-buttons">';
    
    if (current_page > 1) {
        html += `<button class="btn-pagination btn-prev" data-page="${current_page - 1}" data-search="${search}" data-tab="${tab}">
                    <span class="material-symbols-rounded">chevron_left</span>
                    Anterior
                </button>`;
    } else {
        html += `<button class="btn-pagination btn-prev" disabled>
                    <span class="material-symbols-rounded">chevron_left</span>
                    Anterior
                </button>`;
    }
    
    html += '<div class="pagination-numbers">';
    for (let i = 1; i <= last_page; i++) {
        if (i === current_page) {
            html += `<span class="page-number active">${i}</span>`;
        } else if (i === 1 || i === last_page || (i >= current_page - 1 && i <= current_page + 1)) {
            html += `<button class="page-number" data-page="${i}" data-search="${search}" data-tab="${tab}">${i}</button>`;
        } else if (i === 2 || i === last_page - 1) {
            html += `<span class="page-number">...</span>`;
        }
    }
    html += '</div>';
    
    if (current_page < last_page) {
        html += `<button class="btn-pagination btn-next" data-page="${current_page + 1}" data-search="${search}" data-tab="${tab}">
                    Siguiente
                    <span class="material-symbols-rounded">chevron_right</span>
                </button>`;
    } else {
        html += `<button class="btn-pagination btn-next" disabled>
                    Siguiente
                    <span class="material-symbols-rounded">chevron_right</span>
                </button>`;
    }
    
    html += '</div></div>';
    return html;
}

function initPaginationEvents(search, tab = 'pedidos') {
    const paginationButtons = document.querySelectorAll('#viewOrdenes .btn-pagination, #viewOrdenes .page-number:not(.active)');
    
    paginationButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const page = this.dataset.page;
            const searchTerm = this.dataset.search || '';
            const activeTab = this.dataset.tab || tab || 'pedidos';
            showOrdenes(searchTerm, page, activeTab);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });
}

function openNovedadesModal(reciboId, esParcial) {
    let modal = document.getElementById('novedadesModal');
    if (!modal) {
        modal = createNovedadesModal();
        document.body.appendChild(modal);
    }

    modal.style.display = 'flex';
    loadNovedades(reciboId, esParcial);
}

function createNovedadesModal() {
    const modal = document.createElement('div');
    modal.id = 'novedadesModal';
    modal.style.cssText = `
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(4px);
    `;

    modal.innerHTML = `
        <div style="
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            max-width: 700px;
            width: 90%;
            max-height: 85vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            animation: slideUp 0.3s ease-out;
        ">
            <div style="
                padding: 28px 32px;
                border-bottom: 1px solid #e2e8f0;
                display: flex;
                justify-content: space-between;
                align-items: center;
                background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            ">
                <div>
                    <h2 style="
                        margin: 0;
                        font-size: 22px;
                        font-weight: 800;
                        color: #0f172a;
                        display: flex;
                        align-items: center;
                        gap: 10px;
                    ">
                        <span style="font-size: 24px;">📝</span>
                        Novedades Registradas
                    </h2>
                    <p style="
                        margin: 6px 0 0 0;
                        font-size: 13px;
                        color: #64748b;
                        font-weight: 500;
                    ">Historial de eventos y observaciones</p>
                </div>
                <button onclick="closeNovedadesModal()" style="
                    background: white;
                    border: 1px solid #e2e8f0;
                    font-size: 20px;
                    cursor: pointer;
                    color: #94a3b8;
                    padding: 8px;
                    width: 40px;
                    height: 40px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 10px;
                    transition: all 0.2s;
                    flex-shrink: 0;
                " onmouseover="this.style.background='#f1f5f9'; this.style.borderColor='#cbd5e1'; this.style.color='#475569';" onmouseout="this.style.background='white'; this.style.borderColor='#e2e8f0'; this.style.color='#94a3b8';">
                    ✕
                </button>
            </div>

            <div id="novedadesContent" style="
                padding: 24px 32px;
                overflow-y: auto;
                flex: 1;
            ">
                <div style="text-align: center; padding: 40px 20px;">
                    <div style="
                        width: 48px;
                        height: 48px;
                        border: 3px solid #e2e8f0;
                        border-top-color: #3b82f6;
                        border-radius: 50%;
                        animation: spin 1s linear infinite;
                        margin: 0 auto 16px;
                    "></div>
                    <p style="color: #94a3b8; font-weight: 500;">Cargando novedades...</p>
                </div>
            </div>
        </div>
    `;

    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
        #novedadesContent::-webkit-scrollbar {
            width: 8px;
        }
        #novedadesContent::-webkit-scrollbar-track {
            background: transparent;
        }
        #novedadesContent::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        #novedadesContent::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    `;
    document.head.appendChild(style);

    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeNovedadesModal();
        }
    });

    return modal;
}

function closeNovedadesModal() {
    const modal = document.getElementById('novedadesModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function loadNovedades(reciboId, esParcial) {
    const content = document.getElementById('novedadesContent');
    
    fetch(`/entregas-talleres/historial/${reciboId}?es_parcial=${esParcial ? '1' : '0'}`)
        .then(response => response.json())
        .then(data => {
            const novedades = data.filter(item => item.es_novedad);
            
            if (novedades.length === 0) {
                content.innerHTML = `
                    <div style="text-align: center; padding: 60px 20px;">
                        <div style="
                            width: 80px;
                            height: 80px;
                            background: #f0fdf4;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            margin: 0 auto 20px;
                            font-size: 40px;
                        ">✓</div>
                        <h3 style="
                            margin: 0 0 8px 0;
                            font-size: 16px;
                            font-weight: 700;
                            color: #1e293b;
                        ">Sin novedades</h3>
                        <p style="
                            margin: 0;
                            font-size: 14px;
                            color: #64748b;
                        ">No hay novedades registradas para este recibo.</p>
                    </div>
                `;
                return;
            }

            let html = `<div style="display: flex; flex-direction: column; gap: 14px;">`;
            
            novedades.forEach((novedad) => {
                const iniciales = novedad.encargado
                    .split(' ')
                    .map(word => word[0])
                    .join('')
                    .toUpperCase()
                    .substring(0, 2);

                html += `
                    <div style="
                        background: white;
                        border: 1px solid #e2e8f0;
                        border-radius: 12px;
                        padding: 18px;
                        transition: all 0.2s ease;
                    " onmouseover="this.style.boxShadow='0 6px 18px rgba(15,23,42,0.08)'; this.style.borderColor='#cbd5e1';" onmouseout="this.style.boxShadow='none'; this.style.borderColor='#e2e8f0';">
                        <div style="
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            margin-bottom: 14px;
                        ">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="
                                    width: 40px;
                                    height: 40px;
                                    border-radius: 50%;
                                    background: #eff6ff;
                                    color: #2563eb;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    font-weight: 700;
                                    font-size: 14px;
                                    flex-shrink: 0;
                                ">${iniciales}</div>
                                <div>
                                    <div style="
                                        font-size: 14px;
                                        font-weight: 600;
                                        color: #0f172a;
                                    ">${novedad.encargado}</div>
                                    <div style="
                                        font-size: 12px;
                                        color: #64748b;
                                    ">${novedad.fecha}</div>
                                </div>
                            </div>
                            <span style="
                                background: #fef3c7;
                                color: #92400e;
                                padding: 4px 10px;
                                border-radius: 20px;
                                font-size: 11px;
                                font-weight: 600;
                                text-transform: uppercase;
                                letter-spacing: 0.3px;
                                flex-shrink: 0;
                            ">Novedad</span>
                        </div>

                        <div style="
                            background: #f8fafc;
                            border: 1px solid #e2e8f0;
                            border-radius: 10px;
                            padding: 14px;
                            color: #334155;
                            font-size: 14px;
                            line-height: 1.6;
                            word-break: break-word;
                        ">
                            ${escapeHtml(novedad.observaciones || 'Sin descripción')}
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            content.innerHTML = html;
        })
        .catch(error => {
            console.error('Error al cargar novedades:', error);
            content.innerHTML = `
                <div style="text-align: center; padding: 60px 20px;">
                    <div style="
                        width: 80px;
                        height: 80px;
                        background: #fee2e2;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        margin: 0 auto 20px;
                        font-size: 40px;
                    ">⚠</div>
                    <h3 style="
                        margin: 0 0 8px 0;
                        font-size: 16px;
                        font-weight: 700;
                        color: #1e293b;
                    ">Error al cargar</h3>
                    <p style="
                        margin: 0;
                        font-size: 14px;
                        color: #64748b;
                    ">No pudimos cargar las novedades. Intenta de nuevo.</p>
                </div>
            `;
        });
}

function cargarEntregasAcordeon(reciboId, esParcial, reciboNumero, tipoRecibo, contentDiv) {
    const mainContainer = document.querySelector('.main-container');
    const apiReciboCompletoBase = mainContainer?.dataset?.routeApiReciboCompleto;

    if (!apiReciboCompletoBase || !reciboNumero || !tipoRecibo) {
        contentDiv.innerHTML = '<div style="padding: 30px; text-align: center; color: #64748b;"><p>No hay datos de asignacion disponibles</p></div>';
        return;
    }

    const params = new URLSearchParams({
        recibo_id: String(reciboId || ''),
        numero_recibo: String(reciboNumero),
        tipo_recibo: String(tipoRecibo),
        es_parcial: String(esParcial || ''),
    });

    fetch(`${apiReciboCompletoBase}?${params.toString()}`)
        .then(async response => {
            let body = {};
            try {
                body = await response.json();
            } catch (error) {
                body = {};
            }

            if (!response.ok || body.success === false) {
                throw new Error(body.message || `HTTP ${response.status}`);
            }

            return body;
        })
        .then(data => {
            const tallas = Array.isArray(data.tallas) ? data.tallas : [];

            if (tallas.length === 0) {
                contentDiv.innerHTML = '<div style="padding: 30px; text-align: center; color: #64748b;"><p>No hay datos de asignacion disponibles</p></div>';
                return;
            }

            const fecha = data.fecha_salida
                ? formatFechaAcordeon(data.fecha_salida)
                : ((data.dia && data.mes && data.ano)
                    ? `${String(data.dia).padStart(2, '0')}/${String(data.mes).padStart(2, '0')}/${String(data.ano)}`
                    : 'N/A');
            const totalAsignado = Number(data.total || 0);

            const descripcion = data.descripcion || 'Sin descripcion';
            const historialId = `historial-entregas-${reciboId}-${esParcial ? 'parcial' : 'normal'}`;

            let html = '<div style="padding: 20px;">';
            html += `
                <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                            <th style="padding: 12px; text-align: left; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase;">Fecha salida</th>
                            <th style="padding: 12px; text-align: left; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase;">Descripcion</th>
                            <th style="padding: 12px; text-align: left; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase;">Talla</th>
                            <th style="padding: 12px; text-align: center; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase;">Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>`;

            tallas.forEach((row, index) => {
                const bgColor = index % 2 === 0 ? '#ffffff' : '#f8fafc';
                const talla = row.talla || 'N/A';
                const cantidad = Number(row.cantidad || 0);

                html += `<tr style="border-bottom: 1px solid #e2e8f0; background: ${bgColor};">`;
                if (index === 0) {
                    html += `<td rowspan="${tallas.length}" style="padding: 12px; font-size: 13px; color: #334155; vertical-align: top; font-weight: 600;">${escapeHtml(formatFechaAcordeon(fecha))}</td>`;
                    html += `<td rowspan="${tallas.length}" style="padding: 12px; font-size: 13px; color: #334155; vertical-align: top;">${escapeHtml(descripcion)}</td>`;
                }
                html += `<td style="padding: 12px; font-size: 13px; color: #334155;">${escapeHtml(talla)}</td>`;
                html += `<td style="padding: 12px; text-align: center; font-size: 14px; font-weight: 600; color: #2563eb;">${cantidad}</td>`;
                html += '</tr>';
            });

            html += `
                    </tbody>
                </table>
                <div style="margin-top: 15px; text-align: right; font-size: 14px; font-weight: 600; color: #475569;">
                    Total asignado: <span style="color: #2563eb;">${totalAsignado} unidades</span>
                </div>
            `;
            html += `
                <div style="margin-top: 14px; display: flex; justify-content: flex-end;">
                    <button
                        type="button"
                        class="btn-historial-entregas"
                        data-recibo-id="${reciboId}"
                        data-es-parcial="${esParcial ? '1' : '0'}"
                        data-target-id="${historialId}"
                        style="
                            display: inline-flex;
                            align-items: center;
                            gap: 8px;
                            padding: 8px 14px;
                            border: 1px solid #cbd5e1;
                            border-radius: 10px;
                            background: #ffffff;
                            color: #1e40af;
                            font-weight: 700;
                            font-size: 0.85rem;
                            cursor: pointer;
                        "
                    >
                        <span class="material-symbols-rounded" style="font-size: 16px;">unfold_more</span>
                        Ver historial de entregas
                    </button>
                </div>
                <div id="${historialId}" class="historial-entregas-panel" style="display:none; margin-top: 14px;"></div>
            `;
            html += '</div>';

            contentDiv.innerHTML = html;
            initHistorialEntregasAccordion(contentDiv);
        })
        .catch(error => {
            console.error('Error al cargar asignaciones del recibo:', error);
            contentDiv.innerHTML = '<div style="padding: 30px; text-align: center; color: #64748b;"><p>No hay datos de asignacion disponibles</p></div>';
        });
}

function initHistorialEntregasAccordion(contentDiv) {
    const buttons = contentDiv.querySelectorAll('.btn-historial-entregas');

    buttons.forEach((button) => {
        button.addEventListener('click', async () => {
            const targetId = button.dataset.targetId;
            const reciboId = button.dataset.reciboId;
            const esParcial = button.dataset.esParcial === '1';
            const panel = document.getElementById(targetId);

            if (!panel) return;

            const isVisible = panel.style.display !== 'none';
            if (isVisible) {
                panel.style.display = 'none';
                button.querySelector('.material-symbols-rounded').textContent = 'unfold_more';
                return;
            }

            panel.style.display = 'block';
            panel.innerHTML = `
                <div style="padding: 16px; text-align: center; color: #64748b;">
                    <div class="loading-spinner" style="margin: 0 auto 10px auto;"></div>
                    <p>Cargando historial de entregas...</p>
                </div>
            `;
            button.querySelector('.material-symbols-rounded').textContent = 'expand_less';

            try {
                const response = await fetch(`/entregas-talleres/historial/${reciboId}?es_parcial=${esParcial ? '1' : '0'}`);
                const data = await response.json();
                const entregas = Array.isArray(data) ? data.filter((item) => !item.es_novedad) : [];

                if (!entregas.length) {
                    panel.innerHTML = `
                        <div style="padding: 16px; text-align: center; color: #64748b;">
                            <p>No hay entregas registradas.</p>
                        </div>
                    `;
                    return;
                }

                let historyHtml = `
                    <div style="padding: 16px; border: 1px solid #e2e8f0; border-radius: 12px; background: #f8fafc;">
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom: 12px; gap: 12px; flex-wrap: wrap;">
                            <h4 style="margin:0; font-size: 14px; color: #0f172a;">Historial de entregas</h4>
                            <span style="font-size: 12px; color: #64748b;">${entregas.length} movimientos</span>
                        </div>
                        <div style="overflow:auto;">
                            <table style="width:100%; border-collapse: collapse; background: white; border-radius: 8px; overflow:hidden;">
                                <thead>
                                    <tr style="background:#f1f5f9; border-bottom:1px solid #e2e8f0;">
                                        <th style="padding:10px; text-align:left; font-size:12px; color:#475569; text-transform:uppercase;">Fecha entrada</th>
                                        <th style="padding:10px; text-align:left; font-size:12px; color:#475569; text-transform:uppercase;">Talla</th>
                                        <th style="padding:10px; text-align:left; font-size:12px; color:#475569; text-transform:uppercase;">Género</th>
                                        <th style="padding:10px; text-align:left; font-size:12px; color:#475569; text-transform:uppercase;">Cantidad</th>
                                        <th style="padding:10px; text-align:left; font-size:12px; color:#475569; text-transform:uppercase;">Observaciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                `;

                entregas.forEach((item) => {
                    historyHtml += `
                        <tr style="border-bottom:1px solid #e2e8f0;">
                            <td style="padding:10px; font-size:13px; color:#334155; vertical-align:top; white-space:nowrap;">${escapeHtml(item.fecha || '-')}</td>
                            <td style="padding:10px; font-size:13px; color:#334155; vertical-align:top;">${escapeHtml((item.talla || '-'))}</td>
                            <td style="padding:10px; font-size:13px; color:#334155; vertical-align:top;">${escapeHtml((item.genero || '-'))}</td>
                            <td style="padding:10px; font-size:13px; color:#0f172a; vertical-align:top; font-weight:700;">${escapeHtml(String(item.cantidad_total ?? 0))}</td>
                            <td style="padding:10px; font-size:13px; color:#334155; vertical-align:top;">${escapeHtml(item.observaciones || '-')}</td>
                        </tr>
                    `;
                });

                historyHtml += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;

                panel.innerHTML = historyHtml;
            } catch (error) {
                console.error('Error al cargar historial de entregas:', error);
                panel.innerHTML = `
                    <div style="padding: 16px; text-align: center; color: #b91c1c;">
                        <p>No se pudo cargar el historial de entregas.</p>
                    </div>
                `;
            }
        });
    });
}

function isOrdenesViewActive() {
    return currentState.view === 'ordenes' || new URLSearchParams(window.location.search).get('view') === 'ordenes';
}

function syncOrdenesUrl(searchTerm, page = 1, tab = currentState.ordenesTab || 'pedidos') {
    const url = new URL(window.location.href);
    url.searchParams.set('view', 'ordenes');
    url.searchParams.set('tab', tab);
    if (searchTerm) url.searchParams.set('search', searchTerm);
    else url.searchParams.delete('search');
    if (page && page > 1) url.searchParams.set('page', page);
    else url.searchParams.delete('page');
    return url;
}

function handleOrdenesSearch(searchTerm, page = 1) {
    if (!isOrdenesViewActive()) return false;

    const activeTab = currentState.ordenesTab || 'pedidos';
    const url = syncOrdenesUrl(searchTerm, page, activeTab);
    window.history.pushState({ view: 'ordenes', tab: activeTab }, '', url.toString());
    showOrdenes(searchTerm, page, activeTab);
    return true;
}

function handleOrdenesClearSearch() {
    if (!isOrdenesViewActive()) return false;
    return handleOrdenesSearch('', 1);
}

function handleOrdenesSidebarNavigation() {
    const activeTab = currentState.ordenesTab || 'pedidos';
    const url = syncOrdenesUrl('', 1, activeTab);
    window.history.pushState({ view: 'ordenes', tab: activeTab }, 'Órdenes', url.toString());
    showOrdenes('', 1, activeTab);
    return true;
}

function handleOrdenesPopstate(event) {
    if (!event || !event.state || event.state.view !== 'ordenes') return false;

    setSidebarActiveById('navOrdenes');
    showOrdenes('', 1, event.state.tab || 'pedidos');
    return true;
}

function handleOrdenesInitialUrl(urlParams, searchInput, clearButton) {
    if (!urlParams || urlParams.get('view') !== 'ordenes') return false;

    const searchVal = urlParams.get('search') || '';
    const tabVal = urlParams.get('tab') || 'pedidos';

    setSidebarActiveById('navOrdenes');
    currentState.ordenesTab = tabVal === 'bodega' ? 'bodega' : 'pedidos';
    syncOrdenesTabButtons(currentState.ordenesTab);

    if (searchInput) {
        searchInput.placeholder = 'Buscar número de orden...';
        if (clearButton) {
            clearButton.style.display = searchVal.length > 0 ? 'flex' : 'none';
        }
    }

    showOrdenes(searchVal, 1, currentState.ordenesTab);
    return true;
}

window.TalleresOrdenes = {
    initOrdenesTabs,
    showOrdenes,
    handleOrdenesSearch,
    handleOrdenesClearSearch,
    handleOrdenesSidebarNavigation,
    handleOrdenesPopstate,
    handleOrdenesInitialUrl,
    isOrdenesViewActive
};
