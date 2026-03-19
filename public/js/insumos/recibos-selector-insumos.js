/**
 * Recibos Selector & Tracking - FASE 4d
 * Maneja la selección de recibos, prendas y modal de seguimiento
 * 
 * Funciones extraídas:
 * - abrirSelectorRecibos()
 * - mostrarSelectorDePrendas()
 * - seleccionarPrendaRecibo()
 * - verSeguimiento() - Override
 * - abrirModalSeguimientoDirectoInsumos()
 * - cargarPedidosRecibosModule()
 * - cerrarSelectorPrendas()
 * - Dropdowns management
 */

document.addEventListener('DOMContentLoaded', function() {
    let dropdownAbierto = {};

    /**
     * Abre el selector de recibos - carga datos del pedido
     */
    window.abrirSelectorRecibos = function(pedidoId) {
        console.log('[abrirSelectorRecibos] Cargando lista de prendas con pedidoId:', pedidoId);
        
        fetch(`/pedidos-public/${pedidoId}/recibos-datos`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(datos => {
            console.log('[abrirSelectorRecibos] Datos recibidos:', datos);
            
            const datosReales = datos.data || datos;
            
            if (datosReales.prendas && datosReales.prendas.length > 0) {
                window.mostrarSelectorDePrendas(datosReales, pedidoId);
            } else {
                console.error('[abrirSelectorRecibos] No se encontraron prendas');
            }
        })
        .catch(error => {
            console.error('[abrirRecibos] Error al cargar datos:', error);
        });
    };
    
    /**
     * Muestra el modal con la lista de prendas para seleccionar
     */
    window.mostrarSelectorDePrendas = function(datos, pedidoId) {
        console.log('[mostrarSelectorDePrendas] Mostrando lista de prendas');
        
        const modal = document.createElement('div');
        modal.id = 'selector-prendas-modal';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            padding: 1rem;
        `;
        
        const modalContent = document.createElement('div');
        modalContent.style.cssText = `
            background: white;
            border-radius: 12px;
            padding: 2rem;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        `;
        
        modalContent.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="margin: 0; font-size: 1.5rem; font-weight: bold; color: #1f2937;">
                    Seleccionar Prenda - Pedido ${datos.numero_pedido}
                </h2>
                <button onclick="window.cerrarSelectorPrendas()" style="
                    background: none;
                    border: none;
                    font-size: 1.5rem;
                    cursor: pointer;
                    color: #6b7280;
                    padding: 0.5rem;
                    border-radius: 0.375rem;
                    transition: all 0.2s ease;
                " onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='none'">
                    ×
                </button>
            </div>
            
            <div style="margin-bottom: 1.5rem; color: #6b7280;">
                Cliente: ${datos.cliente || 'N/A'} | Asesor: ${datos.asesor || datos.asesora || 'N/A'}
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                ${datos.prendas.map((prenda, index) => `
                    <button onclick="window.seleccionarPrendaRecibo('${pedidoId}', ${index})" style="
                        background: white;
                        border: 2px solid #e5e7eb;
                        border-radius: 8px;
                        padding: 1.5rem;
                        text-align: left;
                        cursor: pointer;
                        transition: all 0.2s ease;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    " onmouseover="this.style.background='#f9fafb'; this.style.borderColor='#3b82f6'" onmouseout="this.style.background='white'; this.style.borderColor='#e5e7eb'">
                        <div>
                            <div style="font-weight: 700; color: #1f2937; margin-bottom: 0.5rem; font-size: 1.125rem;">
                                ${prenda.nombre || 'Prenda sin nombre'}
                            </div>
                            <div style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.25rem;">
                                ${prenda.descripcion || 'Sin descripción'}
                            </div>
                            <div style="font-size: 0.75rem; color: #9ca3af;">
                                Cantidad: ${prenda.cantidad || 'N/A'}
                            </div>
                        </div>
                        <div style="background: #3b82f6; color: white; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 700;">
                            Ver Recibo
                        </div>
                    </button>
                `).join('')}
            </div>
        `;
        
        modal.appendChild(modalContent);
        document.body.appendChild(modal);
        
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                window.cerrarSelectorPrendas();
            }
        });
        
        window.datosSelectorPrendas = datos;
        window.pedidoIdSelector = pedidoId;
    };
    
    /**
     * Selecciona una prenda y abre su recibo
     */
    window.seleccionarPrendaRecibo = function(pedidoId, prendaIndex) {
        console.log('[seleccionarPrendaRecibo] Seleccionada prenda:', prendaIndex);
        
        window.cerrarSelectorPrendas();
        
        if (typeof verRecibosDelPedido === 'function') {
            verRecibosDelPedido(null, pedidoId, prendaIndex);
        } else {
            console.error('[seleccionarPrendaRecibo] verRecibosDelPedido no está disponible');
        }
    };
    
    /**
     * Cierra el selector de prendas
     */
    window.cerrarSelectorPrendas = function() {
        const modal = document.getElementById('selector-prendas-modal');
        if (modal) {
            modal.remove();
        }
        
        window.datosSelectorPrendas = null;
        window.pedidoIdSelector = null;
    };

    /**
     * Override de verSeguimiento para insumos/materiales
     * Abre el modal de seguimiento directamente con la primera prenda
     */
    window.verSeguimiento = function(pedidoId, prendaIdTarget) {
        console.log('[Insumos verSeguimiento] Abriendo seguimiento directo para pedido:', pedidoId, 'prenda:', prendaIdTarget);

        if (typeof openOrderTracking !== 'function') {
            console.error('[Insumos verSeguimiento] openOrderTracking no disponible');
            alert('Sistema de seguimiento no disponible');
            return;
        }

        openOrderTracking(pedidoId, false).then(() => {
            console.log('[Insumos verSeguimiento] Datos inicializados, buscando prenda:', prendaIdTarget);

            let prendas = null;
            if (window.currentOrderData && window.currentOrderData.prendas) {
                prendas = window.currentOrderData.prendas;
            } else if (window.currentOrderData && window.currentOrderData.data && window.currentOrderData.data.prendas) {
                prendas = window.currentOrderData.data.prendas;
            } else if (window.prendasData && window.prendasData.length > 0) {
                prendas = window.prendasData;
            }

            if (prendas && prendas.length > 0) {
                let prendaSeleccionada = null;
                if (prendaIdTarget) {
                    prendaSeleccionada = prendas.find(p => 
                        String(p.id) === String(prendaIdTarget) || 
                        String(p.prenda_pedido_id) === String(prendaIdTarget)
                    );
                    console.log('[Insumos verSeguimiento] Prenda encontrada por ID:', prendaSeleccionada?.nombre_prenda || prendaSeleccionada?.nombre);
                }
                
                if (!prendaSeleccionada) {
                    prendaSeleccionada = prendas[0];
                    console.log('[Insumos verSeguimiento] Usando primera prenda como fallback');
                }
                
                window.currentPrendaData = prendaSeleccionada;
                window.abrirModalSeguimientoDirectoInsumos(pedidoId, prendaIdTarget);
            } else {
                console.warn('[Insumos verSeguimiento] No hay prendas, abriendo selector como fallback');
                if (typeof showPrendasSelector === 'function') {
                    showPrendasSelector();
                } else {
                    alert('No hay prendas disponibles para este pedido');
                }
            }
        }).catch(error => {
            console.error('[Insumos verSeguimiento] Error:', error);
            alert('Error al cargar los datos del pedido: ' + error.message);
        });
    };

    /**
     * Abre el modal de seguimiento directamente sin selector
     */
    window.abrirModalSeguimientoDirectoInsumos = function(pedidoId, prendaIdTarget) {
        const trackingOverlay = document.getElementById('trackingModalOverlay');
        if (trackingOverlay) {
            trackingOverlay.style.display = 'block';
        } else {
            console.warn('[Insumos] Modal de seguimiento no encontrado');
            alert('Modal de seguimiento no disponible');
            return;
        }

        const trackingModal = document.getElementById('orderTrackingModal');
        if (trackingModal) {
            trackingModal.style.display = 'flex';
            trackingModal.classList.add('show');

            let urlConsecutivo = `/registros/${pedidoId}/consecutivo-costura`;
            if (prendaIdTarget) {
                urlConsecutivo += `?prenda_id=${prendaIdTarget}`;
            }

            fetch(urlConsecutivo)
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.consecutivo) {
                        const reciboEl = document.getElementById('trackingOrderRecibo');
                        if (reciboEl) reciboEl.textContent = data.consecutivo;

                        const headerEl = document.getElementById('trackingPrendaReciboHeader');
                        if (headerEl) headerEl.textContent = `COSTURA #${data.consecutivo}`;
                    } else {
                        const reciboEl = document.getElementById('trackingOrderRecibo');
                        if (reciboEl) reciboEl.textContent = '-';
                        const headerEl = document.getElementById('trackingPrendaReciboHeader');
                        if (headerEl) headerEl.textContent = 'COSTURA #?';
                    }

                    if (data.fecha_creacion) {
                        const fechaEl = document.getElementById('trackingOrderDate');
                        if (fechaEl) {
                            const fecha = new Date(data.fecha_creacion);
                            fechaEl.textContent = fecha.toLocaleDateString('es-ES', {
                                day: '2-digit', month: '2-digit', year: 'numeric'
                            });
                        }
                    }

                    if (typeof showPrendaTracking === 'function' && window.currentPrendaData) {
                        // Marcar como readonly si el usuario tiene rol insumos
                        if (window.isInsumos) {
                            window.currentPrendaData.readonly = true;
                        }
                        showPrendaTracking(window.currentPrendaData);
                    }
                })
                .catch(error => {
                    console.error('[Insumos] Error al obtener consecutivo:', error);
                    if (typeof showPrendaTracking === 'function' && window.currentPrendaData) {
                        // Marcar como readonly si el usuario tiene rol insumos
                        if (window.isInsumos) {
                            window.currentPrendaData.readonly = true;
                        }
                        showPrendaTracking(window.currentPrendaData);
                    }
                });
        }
    };

    /**
     * Inicializa dropdown management para botones Ver
     */
    console.log('[Insumos Dropdowns] DOMContentLoaded iniciado');
    console.log('[Insumos Dropdowns] Buscando botones btn-ver-dropdown...');
    
    const botones = document.querySelectorAll('.btn-ver-dropdown');
    console.log(`[Insumos Dropdowns] Encontrados ${botones.length} botones`);
    
    setTimeout(() => {
        document.addEventListener('click', function(e) {
            const btnVerDropdown = e.target.closest('.btn-ver-dropdown');
            if (btnVerDropdown) {
                console.log('[Insumos Dropdowns] Clic en botón Ver');
                e.preventDefault();
                e.stopPropagation();
                
                const menuId = btnVerDropdown.getAttribute('data-menu-id');
                console.log(`[Insumos Dropdowns] menuId: ${menuId}`);
                
                let dropdown = document.getElementById(menuId);
                console.log(`[Insumos Dropdowns] Dropdown existe: ${dropdown !== null}`);
                
                if (!dropdown) {
                    console.log(`[Insumos Dropdowns] Creando dropdown ${menuId}...`);
                    if (typeof crearDropdownVer === 'function') {
                        console.log('[Insumos Dropdowns] Función crearDropdownVer disponible');
                        dropdown = window.crearDropdownVer(btnVerDropdown);
                        console.log(`[Insumos Dropdowns] Dropdown creado: ${dropdown !== null}`);
                        dropdownAbierto[menuId] = false;
                    } else {
                        console.error('[Insumos Dropdowns] Función crearDropdownVer NO disponible');
                    }
                }
                
                if (dropdown) {
                    console.log(`[Insumos Dropdowns] Estado actual: ${dropdownAbierto[menuId] ? 'ABIERTO' : 'CERRADO'}`);
                    
                    if (!dropdownAbierto[menuId]) {
                        const rect = btnVerDropdown.getBoundingClientRect();
                        dropdown.style.top = (rect.bottom + 5) + 'px';
                        dropdown.style.left = (rect.left) + 'px';
                        dropdown.style.display = 'block';
                        dropdown.style.pointerEvents = 'auto';
                        dropdownAbierto[menuId] = true;
                        console.log('[Insumos Dropdowns] Dropdown abierto');
                    } else {
                        dropdown.style.display = 'none';
                        dropdown.style.pointerEvents = 'none';
                        dropdownAbierto[menuId] = false;
                        console.log('[Insumos Dropdowns] Dropdown cerrado');
                    }
                }
            }
        });
        
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.btn-ver-dropdown') && !e.target.closest('.dropdown-menu')) {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    const id = menu.id;
                    if (dropdownAbierto[id]) {
                        menu.style.display = 'none';
                        menu.style.pointerEvents = 'none';
                        dropdownAbierto[id] = false;
                    }
                });
            }
        });
    }, 100);

    /**
     * Mostrar indicador de carga en paginación
     */
    document.querySelectorAll('.pagination-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!this.disabled) {
                document.getElementById('loadingOverlay').classList.add('active');
            }
        });
    });

    /**
     * Cierre de modal al hacer clic fuera
     */
    const insumosModal = document.getElementById('insumosModal');
    if (insumosModal) {
        insumosModal.addEventListener('click', function(e) {
            if (e.target === this) {
                window.cerrarModalInsumos();
            }
        });
    }
});
