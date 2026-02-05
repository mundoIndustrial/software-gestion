/**
 * BODEGA - Sistema de Gestión de Pedidos
 * JavaScript Vanilla • Operaciones AJAX
 * Febrero 2026
 */

/**
 * Obtener CSRF token del meta tag
 */
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

/**
 * Toggle menú de usuario
 */
function toggleUserMenu() {
    const menu = document.getElementById('userMenu');
    if (menu) {
        menu.classList.toggle('hidden');
    }
}

/**
 * Cerrar menú de usuario cuando se haga click fuera
 */
document.addEventListener('click', function(event) {
    const userMenu = document.getElementById('userMenu');
    const userMenuBtn = document.getElementById('userMenuBtn');
    
    if (userMenu && userMenuBtn && !userMenu.contains(event.target) && !userMenuBtn.contains(event.target)) {
        userMenu.classList.add('hidden');
    }
});

/**
 * Filtrar tabla según criterios
 */
function filterTable() {
    const searchInput = document.getElementById('searchInput');
    const searchValue = (searchInput?.value || '').toLowerCase().trim();

    const rows = document.querySelectorAll('.pedido-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const rowText = row.getAttribute('data-search') || '';

        let showRow = true;

        if (searchValue && !rowText.includes(searchValue)) showRow = false;

        row.style.display = showRow ? '' : 'none';
        if (showRow) visibleCount++;
    });
}

/**
 * Limpiar búsqueda
 */
function limpiarBuscador() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.value = '';
        filterTable();
    }
}

/**
 * Actualizar tabla sin recargar la página
 */
async function actualizarTabla() {
    const btnActualizar = document.getElementById('btnActualizar');
    if (btnActualizar) {
        btnActualizar.disabled = true;
        btnActualizar.innerHTML = '⏳ Actualizando...';
    }

    try {
        const url = window.location.pathname;
        
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }

        const html = await response.text();
        
        const parser = new DOMParser();
        const newDoc = parser.parseFromString(html, 'text/html');
        
        const newTableBody = newDoc.getElementById('pedidosTableBody');
        const currentTableBody = document.getElementById('pedidosTableBody');
        
        if (newTableBody && currentTableBody) {
            currentTableBody.innerHTML = newTableBody.innerHTML;
            
            inicializarColoresDelaPagina();
            
            limpiarBuscador();
            
            mostrarToast('✓ Tabla actualizada correctamente');
        }
    } catch (error) {
        console.error('Error al actualizar la tabla:', error);
        mostrarToast('❌ Error al actualizar la tabla');
    } finally {
        if (btnActualizar) {
            btnActualizar.disabled = false;
            btnActualizar.innerHTML = '🔄 Actualizar';
        }
    }
}

/**
 * Mostrar notificación Toast
 */
function mostrarToast(mensaje) {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');
    
    if (toast && toastMessage) {
        toastMessage.textContent = mensaje;
        toast.classList.remove('hidden');
        toast.style.display = 'flex';
        
        setTimeout(() => {
            toast.classList.add('hidden');
            toast.style.display = 'none';
        }, 3000);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // ==================== ELEMENTOS DOM ====================
    const searchInput = document.getElementById('searchInput');

    // ==================== INICIALIZACIÓN ====================
    initializeEventListeners();

    /**
     * Inicializar event listeners
     */
    function initializeEventListeners() {
        // Filtro de búsqueda
        if (searchInput) searchInput.addEventListener('input', filterTable);
    }

});

/**
 * Guardar detalles de un pedido completo
 */
async function guardarPedidoCompleto(numeroPedido) {
    try {
        // Recolectar solo filas con datos válidos del pedido
        const detalles = [];
        const filas = document.querySelectorAll(`tr[data-numero-pedido="${numeroPedido}"]`);
        
        filas.forEach((fila, index) => {
            const areaSelect = fila.querySelector('.area-select');
            const estadoSelect = fila.querySelector('.estado-select');
            
            // Obtener talla desde el data-talla del selector (más confiable)
            const talla = estadoSelect?.getAttribute('data-talla');
            
            // Saltar si no hay talla
            if (!talla) {
                return;
            }
            
            // Obtener ASESOR desde data-asesor de la fila
            const asesor = fila.getAttribute('data-asesor') || '';
            
            // Obtener EMPRESA desde data-empresa (ir hacia arriba si es necesario por rowspan)
            let empresa = fila.getAttribute('data-empresa') || '';
            if (!empresa) {
                // Si no está en la fila actual, buscar en la celda td[data-empresa]
                const empresaCell = fila.parentElement.querySelector('td[data-empresa]');
                empresa = empresaCell?.getAttribute('data-empresa') || '';
            }
            
            // Obtener CANTIDAD desde la celda de cantidad
            const cantidadCell = fila.querySelector('td[data-cantidad]');
            const cantidad = cantidadCell?.getAttribute('data-cantidad') || '0';
            
            // Obtener nombre de la prenda/artículo desde la columna DESCRIPCIÓN
            // Buscar en el contexto del pedido actual (porque el td[data-prenda-nombre] puede tener rowspan)
            const numeroPedidoActual = fila.getAttribute('data-numero-pedido');
            let descripcionCell = fila.querySelector('td[data-prenda-nombre]');
            
            // Si no está en la fila actual (por rowspan), buscar en todo el grupo del pedido
            if (!descripcionCell) {
                descripcionCell = document.querySelector(`tr[data-numero-pedido="${numeroPedidoActual}"] td[data-prenda-nombre]`);
            }
            
            let nombrePrenda = '';
            if (descripcionCell) {
                // Para prendas: <div class="font-bold text-slate-900 mb-2">{{ $nombre }}</div>
                // Para EPPs: <div class="font-semibold text-slate-900">{{ $nombre }}</div>
                const nombreDiv = descripcionCell.querySelector('div.font-bold');
                if (nombreDiv) {
                    nombrePrenda = nombreDiv.textContent.trim();
                } else {
                    const nombreDivEpp = descripcionCell.querySelector('div.font-semibold');
                    if (nombreDivEpp) {
                        nombrePrenda = nombreDivEpp.textContent.trim();
                    }
                }
            }
            
            const pendientesInput = fila.querySelector('.pendientes-input');
            const observacionesInput = fila.querySelector('.observaciones-input');
            const fechaInput = fila.querySelector('.fecha-input');
            
            // Valores actuales
            const pendientes = pendientesInput?.value || '';
            const observaciones = observacionesInput?.value || '';
            const fecha = fechaInput?.value || '';
            const area = areaSelect?.value || '';
            const estado = estadoSelect?.value || '';
            
            // Valores originales (para comparar cambios)
            const areaOriginal = areaSelect?.getAttribute('data-original-area') || '';
            const estadoOriginal = estadoSelect?.getAttribute('data-original-estado') || '';
            
            // Incluir si: tiene datos en campos de texto O cambió area O cambió estado
            const tieneContenidoTexto = pendientes || observaciones || fecha;
            const areaEsCambiado = area !== areaOriginal;
            const estadoEsCambiado = estado !== estadoOriginal;
            
            // Incluir si hay cambios (verdaderos — son EPPs, válidos de incluir si cambiaron estado)
            if (tieneContenidoTexto || areaEsCambiado || estadoEsCambiado) {
                
                detalles.push({
                    talla: talla,  // Enviar talla tal como está (puede ser hash único para EPPs)
                    asesor: asesor,  // Guardar asesor
                    empresa: empresa,  // Guardar empresa
                    cantidad: parseInt(cantidad) || 0,  // Guardar cantidad como número
                    prenda_nombre: nombrePrenda,  // Guardar nombre de la prenda/artículo
                    pendientes: pendientes || null,
                    observaciones_bodega: observaciones || null,
                    fecha_entrega: fecha || null,
                    area: area || null,
                    estado_bodega: estado || null,
                });
            }
        });
        
        if (detalles.length === 0) {
            alert('No hay cambios para guardar');
            return;
        }
        
        // Preparar URL
        const url = `/gestion-bodega/pedidos/${numeroPedido}/guardar-completo`;
        
        // Enviar al servidor
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({
                numero_pedido: numeroPedido,
                detalles: detalles,
            }),
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            console.error('Error al guardar:', data);
            alert('Error: ' + (data.message || 'No se pudieron guardar los cambios'));
            return;
        }
        
        // Mostrar modal de éxito
        mostrarModalExito(data.message);
        
        // Recargar la página para mostrar los datos guardados
        setTimeout(() => {
            location.reload();
        }, 800);
    } catch (error) {
        console.error('Error en guardarPedidoCompleto:', error);
        alert('Error: ' + error.message);
    }
}



/**
 * Funciones de Modal
 */

/**
 * Abrir modal con la factura del pedido
 */
async function abrirModalFactura(pedidoId) {
    const modal = document.getElementById('modalFactura');
    const contenido = document.getElementById('facturaContenido');
    
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
    contenido.innerHTML = '<div class="flex justify-center items-center py-12"><span class="text-slate-500">⏳ Cargando factura...</span></div>';
    
    try {
        const response = await fetch(`/gestion-bodega/pedidos/${pedidoId}/factura-datos`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        
        if (data) {
            // Generar HTML de la factura
            const htmlFactura = generarHTMLFactura(data);
            contenido.innerHTML = htmlFactura;
        } else {
            contenido.innerHTML = '<div class="text-center text-red-600 py-6">❌ Error al cargar la factura</div>';
        }
    } catch (error) {
        console.error('Error cargando factura:', error);
        contenido.innerHTML = '<div class="text-center text-red-600 py-6">❌ Error: ' + error.message + '</div>';
    }
}

/**
 * Cerrar modal
 */
function cerrarModalFactura() {
    const modal = document.getElementById('modalFactura');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }
}

/**
 * Generar HTML de la factura
 */
function generarHTMLFactura(datos) {
    if (!datos || !datos.prendas || !Array.isArray(datos.prendas)) {
        return '<div style="color: #dc2626; padding: 1rem; border: 1px solid #fca5a5; border-radius: 6px; background: #fee2e2;">❌ Error: No se pudieron cargar las prendas del pedido.</div>';
    }

    // Generar las tarjetas de prendas
    const prendasHTML = datos.prendas.map((prenda, idx) => {
        // Variantes tabla
        let variantesHTML = '';
        if (prenda.variantes && Array.isArray(prenda.variantes) && prenda.variantes.length > 0) {
            // Verificar qué columnas tienen datos
            const tieneManga = prenda.variantes.some(v => v.manga);
            const tieneBroche = prenda.variantes.some(v => v.broche);
            const tieneBolsillos = prenda.variantes.some(v => v.bolsillos);
            
            variantesHTML = `
                <table style="width: 100%; font-size: 11px; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                            <th style="padding: 6px 8px; text-align: left; font-weight: 600; color: #374151;">Talla</th>
                            <th style="padding: 6px 8px; text-align: center; font-weight: 600; color: #374151;">Cantidad</th>
                            ${tieneManga ? `<th style="padding: 6px 8px; text-align: left; font-weight: 600; color: #374151;">Manga</th>` : ''}
                            ${tieneBroche ? `<th style="padding: 6px 8px; text-align: left; font-weight: 600; color: #374151;">Botón/Broche</th>` : ''}
                            ${tieneBolsillos ? `<th style="padding: 6px 8px; text-align: left; font-weight: 600; color: #374151;">Bolsillos</th>` : ''}
                        </tr>
                    </thead>
                    <tbody>
                        ${prenda.variantes.map((var_item, varIdx) => `
                            <tr style="background: ${varIdx % 2 === 0 ? '#ffffff' : '#f9fafb'}; border-bottom: 1px solid #f3f4f6;">
                                <td style="padding: 6px 8px; font-weight: 600; color: #374151;">${var_item.talla}</td>
                                <td style="padding: 6px 8px; text-align: center; color: #6b7280;">${var_item.cantidad}</td>
                                ${tieneManga ? `
                                    <td style="padding: 6px 8px; color: #6b7280; font-size: 11px;">
                                        ${var_item.manga ? `<strong>${var_item.manga}</strong>` : '—'}
                                        ${var_item.manga_obs ? `<br><em style="color: #9ca3af; font-size: 10px;">${var_item.manga_obs}</em>` : ''}
                                    </td>
                                ` : ''}
                                ${tieneBroche ? `
                                    <td style="padding: 6px 8px; color: #6b7280; font-size: 11px;">
                                        ${var_item.broche ? `<strong>${var_item.broche}</strong>` : '—'}
                                        ${var_item.broche_obs ? `<br><em style="color: #9ca3af; font-size: 10px;">${var_item.broche_obs}</em>` : ''}
                                    </td>
                                ` : ''}
                                ${tieneBolsillos ? `
                                    <td style="padding: 6px 8px; color: #6b7280; font-size: 11px;">
                                        ${var_item.bolsillos ? `<strong>Sí</strong>` : '—'}
                                        ${var_item.bolsillos_obs ? `<br><em style="color: #9ca3af; font-size: 10px;">${var_item.bolsillos_obs}</em>` : ''}
                                    </td>
                                ` : ''}
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        }

        // Tela y color
        let telaHTML = '';
        if (prenda.telas_array && Array.isArray(prenda.telas_array) && prenda.telas_array.length > 0) {
            telaHTML = `
                <div style="margin-bottom: 12px;">
                    ${prenda.telas_array.map(tela => `
                        <div style="padding: 6px 0; border-bottom: 1px solid #f3f4f6;">
                            <span style="font-size: 11px; color: #374151;">
                                <strong>Tela:</strong> ${tela.tela_nombre || '—'} 
                                <strong style="margin-left: 12px;">Color:</strong> ${tela.color_nombre || '—'}
                                ${tela.referencia ? `<strong style="margin-left: 12px;">Ref:</strong> ${tela.referencia}` : ''}
                            </span>
                        </div>
                    `).join('')}
                </div>
            `;
        } else if (prenda.tela || prenda.color) {
            telaHTML = `
                <div style="margin-bottom: 12px; font-size: 11px; color: #374151;">
                    <strong>Tela:</strong> ${prenda.tela || '—'} 
                    ${prenda.color ? `<strong style="margin-left: 12px;">Color:</strong> ${prenda.color}` : ''}
                </div>
            `;
        }

        // Procesos
        let procesosHTML = '';
        if (prenda.procesos && Array.isArray(prenda.procesos) && prenda.procesos.length > 0) {
            procesosHTML = `
                <div style="margin-bottom: 0;">
                    ${prenda.procesos.map(proc => `
                        <div style="padding: 8px 0; border-bottom: 1px solid #f3f4f6;">
                            <div style="font-weight: 600; color: #374151; margin-bottom: 4px; font-size: 11px;">${proc.nombre || proc.tipo}</div>
                            ${proc.ubicaciones && proc.ubicaciones.length > 0 ? `
                                <div style="font-size: 10px; color: #6b7280; margin-bottom: 2px;">
                                    📍 ${Array.isArray(proc.ubicaciones) ? proc.ubicaciones.join(' • ') : proc.ubicaciones}
                                </div>
                            ` : ''}
                            ${proc.tallas && (proc.tallas.dama && Object.keys(proc.tallas.dama).length > 0 || proc.tallas.caballero && Object.keys(proc.tallas.caballero).length > 0 || proc.tallas.unisex && Object.keys(proc.tallas.unisex).length > 0) ? `
                                <div style="font-size: 10px; color: #6b7280; margin-bottom: 2px;">
                                    ${[
                                        ...(proc.tallas.dama && Object.keys(proc.tallas.dama).length > 0 ? [`Dama: ${Object.entries(proc.tallas.dama).map(([talla, cantidad]) => `${talla}(${cantidad})`).join(', ')}`] : []),
                                        ...(proc.tallas.caballero && Object.keys(proc.tallas.caballero).length > 0 ? [`Caballero: ${Object.entries(proc.tallas.caballero).map(([talla, cantidad]) => `${talla}(${cantidad})`).join(', ')}`] : []),
                                        ...(proc.tallas.unisex && Object.keys(proc.tallas.unisex).length > 0 ? [`Unisex: ${Object.entries(proc.tallas.unisex).map(([talla, cantidad]) => `${talla}(${cantidad})`).join(', ')}`] : [])
                                    ].join(' • ')}
                                </div>
                            ` : ''}
                            ${proc.observaciones ? `
                                <div style="font-size: 10px; color: #6b7280;">
                                    ${proc.observaciones}
                                </div>
                            ` : ''}
                        </div>
                    `).join('')}
                </div>
            `;
        }

        return `
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 6px; margin-bottom: 16px; padding: 16px;">
                <!-- Header simple -->
                <div style="border-bottom: 1px solid #e5e7eb; padding-bottom: 8px; margin-bottom: 12px;">
                    <div style="font-size: 14px; font-weight: 600; color: #374151;">PRENDA ${idx + 1}: ${prenda.nombre}${prenda.de_bodega ? ' <span style="color: #ea580c; font-weight: bold;">- SE SACA DE BODEGA</span>' : ''}</div>
                    ${prenda.descripcion ? `<div style="font-size: 12px; color: #6b7280; margin-top: 2px;">${prenda.descripcion}</div>` : ''}
                </div>
                
                <!-- Telas (movido aquí) -->
                ${telaHTML}
                
                <!-- Imagen pequeña -->
                ${(prenda.imagenes && prenda.imagenes.length > 0) ? `
                    <div style="float: right; margin-left: 12px; margin-bottom: 8px;">
                        <img src="${prenda.imagenes[0].ruta || prenda.imagenes[0].url || prenda.imagenes[0]}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px; border: 1px solid #e5e7eb;">
                    </div>
                ` : ''}
                
                <!-- Contenido compacto -->
                <div style="${(prenda.imagenes && prenda.imagenes.length > 0) ? 'margin-right: 100px;' : ''}">
                    <!-- Variantes -->
                    ${variantesHTML}
                    
                    <!-- Procesos -->
                    ${procesosHTML}
                </div>
                
                <div style="clear: both;"></div>
            </div>
        `;
    }).join('');

    // EPPs
    const eppsHTML = (datos.epps && datos.epps.length > 0) ? `
        <div style="margin: 12px 0; padding: 0; background: #ffffff; border-radius: 6px; border: 1px solid #e0e7ff; overflow: hidden;">
            <div style="font-size: 12px !important; font-weight: 700; color: #1e40af; background: #f0f9ff; margin: 0; padding: 12px 12px; border-bottom: 2px solid #bfdbfe;">EPP (${datos.epps.length})</div>
            <div style="padding: 12px; space-y: 8px;">
                ${datos.epps.map(epp => `
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px; margin-bottom: 8px; border-left: 3px solid #3b82f6; border-radius: 2px; background: #f8fafc;">
                        <div style="flex: 1;">
                            <div style="font-weight: 700; color: #1e40af; margin-bottom: 4px;">${epp.nombre_completo || epp.nombre}</div>
                            ${epp.observaciones && epp.observaciones !== '—' && epp.observaciones !== '-' ? `<div style="font-size: 11px; color: #475569;">${epp.observaciones}</div>` : ''}
                        </div>
                        <div style="font-weight: 600; color: #1e40af; font-size: 14px; margin-left: 12px;">
                            ${epp.cantidad}
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
    ` : '';

    // Totales
    const totalHTML = `
        <div style="margin: 12px 0; padding: 12px; background: #f3f4f6; border-radius: 6px; border: 2px solid #d1d5db; text-align: right;">
            <div style="font-size: 12px; margin-bottom: 8px;">
                <strong>Total Ítems:</strong> ${datos.total_items || 0}
            </div>
            ${datos.valor_total ? `
                <div style="font-size: 12px; margin-bottom: 8px;">
                    <strong>Subtotal:</strong> $${parseFloat(datos.valor_total).toLocaleString('es-CO')}
                </div>
            ` : ''}
            ${datos.total_general ? `
                <div style="font-size: 14px; font-weight: 700; color: #1e40af; padding-top: 8px; border-top: 2px solid #d1d5db;">
                    <strong>Total:</strong> $${parseFloat(datos.total_general).toLocaleString('es-CO')}
                </div>
            ` : ''}
        </div>
    `;

    return `
        <div>
            <!-- Header factura -->
            <div style="background: #1e3a8a; color: white; padding: 16px; border-radius: 6px; margin-bottom: 12px; text-align: center;">
                <div style="font-size: 18px; font-weight: 700; margin-bottom: 8px;">FACTURA DE PEDIDO</div>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; font-size: 12px; margin-top: 12px;">
                    <div>
                        <div style="font-size: 10px; opacity: 0.8;">Número</div>
                        <div style="font-weight: 600;">${datos.numero_pedido}</div>
                    </div>
                    <div>
                        <div style="font-size: 10px; opacity: 0.8;">Cliente</div>
                        <div style="font-weight: 600;">${datos.cliente}</div>
                    </div>
                    <div>
                        <div style="font-size: 10px; opacity: 0.8;">Asesora</div>
                        <div style="font-weight: 600;">${datos.asesora || datos.asesor || 'N/A'}</div>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 12px; margin-top: 8px;">
                    <div>
                        <div style="font-size: 10px; opacity: 0.8;">Forma de Pago</div>
                        <div style="font-weight: 600;">${datos.forma_de_pago || 'N/A'}</div>
                    </div>
                    <div>
                        <div style="font-size: 10px; opacity: 0.8;">Fecha</div>
                        <div style="font-weight: 600;">${datos.fecha || 'N/A'}</div>
                    </div>
                </div>
            </div>

            ${datos.observaciones ? `
                <div style="background: #fef3c7; border: 1px solid #fcd34d; padding: 12px; border-radius: 6px; margin-bottom: 12px; font-size: 11px;">
                    <strong style="color: #92400e;">📋 Observaciones:</strong>
                    <div style="margin-top: 4px; white-space: pre-wrap; color: #666;">${datos.observaciones}</div>
                </div>
            ` : ''}

            <!-- Prendas -->
            ${prendasHTML}

            <!-- EPPs -->
            ${eppsHTML}

            <!-- Totales -->
            ${totalHTML}
        </div>
    `;
}

// Inicializar cuando el DOM esté listo (solo colorear filas, sin auto-save)
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar colores de todas las filas
    inicializarColoresDelaPagina();
    
    // Agregar listener para cambios de estado (solo colorea la fila afectada)
    const selectoresEstado = document.querySelectorAll('.estado-select');
    selectoresEstado.forEach(selector => {
        selector.addEventListener('change', function() {
            colorearFilaPorEstado(this);
        });
    });
});

/**
 * Configuración de colores para bodega
 */
if (typeof window.BodegaConfig === 'undefined') {
    window.BodegaConfig = {
        ESTADO_COLORES: {
            'entregado': 'rgba(59, 130, 246, 0.1)',  // Azul claro
            'pendiente': 'rgba(234, 179, 8, 0.1)',   // Amarillo claro
            'anulado': 'rgba(239, 68, 68, 0.1)'      // Rojo claro
        },
        SELECTOR_COLORES: {
            'entregado': { bg: '#dbeafe', text: '#0c4a6e' },  // Azul muy claro
            'pendiente': { bg: '#fef3c7', text: '#78350f' },  // Amarillo muy claro
            'anulado': { bg: '#fee2e2', text: '#991b1b' }     // Rojo muy claro
        }
    };
}

/**
 * Obtener todas las celdas de datos de una fila (excluyendo Asesor, Empresa, Artículo)
 */
function obtenerCeldasDatos(fila) {
    const todasLasCeldas = fila.querySelectorAll('td');
    const celdasDatos = [];
    
    todasLasCeldas.forEach(celda => {
        // Incluir solo si NO tiene atributos de agrupación
        if (!celda.hasAttribute('data-asesor') && 
            !celda.hasAttribute('data-empresa') && 
            !celda.hasAttribute('data-prenda-nombre')) {
            celdasDatos.push(celda);
        }
    });
    
    return celdasDatos;
}

/**
 * Colorear fila según estado del selector
 * Solo colorea la fila donde cambió el estado (sin afectar otras filas)
 */
function colorearFilaPorEstado(selectorEstado) {
    const fila = selectorEstado.closest('tr');
    if (!fila) return;
    
    const estado = selectorEstado.value.trim().toLowerCase();
    const celdasDatos = obtenerCeldasDatos(fila);
    
    // Limpiar color anterior de todas las celdas
    celdasDatos.forEach(celda => {
        celda.style.backgroundColor = '';
    });
    
    // No aplicar color si no hay estado seleccionado (valor vacío)
    if (!estado || estado === '') {
        aplicarColorAlSelector(selectorEstado, '');
        return;
    }
    
    // Aplicar nuevo color según estado
    if (window.BodegaConfig.ESTADO_COLORES.hasOwnProperty(estado)) {
        const color = window.BodegaConfig.ESTADO_COLORES[estado];
        celdasDatos.forEach(celda => {
            celda.style.backgroundColor = color;
        });
    }
    
    // Aplicar color al selector
    aplicarColorAlSelector(selectorEstado, estado);
}

/**
 * Aplicar color al selector basado en el estado
 */
function aplicarColorAlSelector(selector, estado) {
    const estadoNormalizado = estado.trim().toLowerCase();
    
    // Limpiar estilos previos
    selector.style.backgroundColor = '';
    selector.style.color = '';
    
    // Aplicar nuevo color si existe
    if (window.BodegaConfig.SELECTOR_COLORES.hasOwnProperty(estadoNormalizado)) {
        const colores = window.BodegaConfig.SELECTOR_COLORES[estadoNormalizado];
        selector.style.backgroundColor = colores.bg;
        selector.style.color = colores.text;
    }
}

/**
 * Colorear todas las filas según su estado (usada en DOMContentLoaded)
 */
function inicializarColoresDelaPagina() {
    const selectoresEstado = document.querySelectorAll('.estado-select');
    selectoresEstado.forEach(selector => {
        colorearFilaPorEstado(selector);
    });
}

/**
 * Mostrar modal de éxito
 */
function mostrarModalExito(mensaje) {
    const modal = document.getElementById('modalExito');
    const modalMensaje = document.getElementById('modalMensajeExito');
    
    if (modal && modalMensaje) {
        modalMensaje.textContent = mensaje || 'Cambios guardados correctamente';
        modal.style.display = 'flex';
        
        // Cerrar modal al hacer click en el botón
        const btnCerrar = document.getElementById('btnCerrarModalExito');
        if (btnCerrar) {
            btnCerrar.onclick = function() {
                modal.style.display = 'none';
            };
        }
        
        // Cerrar modal al hacer click fuera
        modal.onclick = function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        };
    }
}







