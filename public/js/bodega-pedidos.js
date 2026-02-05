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

document.addEventListener('DOMContentLoaded', function() {
    // ==================== ELEMENTOS DOM ====================
    const searchInput = document.getElementById('searchInput');
    const asesorFilter = document.getElementById('asesorFilter');

    // ==================== INICIALIZACIÓN ====================
    initializeEventListeners();

    /**
     * Inicializar event listeners
     */
    function initializeEventListeners() {
        // Filtros
        if (searchInput) searchInput.addEventListener('input', filterTable);
        if (asesorFilter) asesorFilter.addEventListener('change', filterTable);
    }

    /**
     * Filtrar tabla según criterios
     */
    function filterTable() {
        const searchValue = (searchInput?.value || '').toLowerCase().trim();
        const asesorValue = (asesorFilter?.value || '').toLowerCase().trim();

        const rows = document.querySelectorAll('.pedido-row');
        let visibleCount = 0;

        rows.forEach(row => {
            const rowText = row.getAttribute('data-search') || '';
            const rowAsesor = (row.getAttribute('data-asesor') || '').toLowerCase();

            let showRow = true;

            if (searchValue && !rowText.includes(searchValue)) showRow = false;
            if (asesorValue && rowAsesor !== asesorValue) showRow = false;

            row.style.display = showRow ? '' : 'none';
            if (showRow) visibleCount++;
        });
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
 * Abrir modal con datos de factura
 */
async function abrirModalFactura(pedidoId) {
    try {
        // Obtener datos del servidor
        const response = await fetch(`/gestion-bodega/pedidos/${pedidoId}/factura-datos`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
            }
        });

        if (!response.ok) {
            throw new Error(`Error ${response.status}: No se pudieron obtener los datos`);
        }

        const datosFactura = await response.json();

        // Generar HTML de la factura
        const htmlFactura = generarHTMLFactura(datosFactura);

        // Mostrar en modal
        const modalContent = document.querySelector('.modal-factura-content');
        if (modalContent) {
            modalContent.innerHTML = htmlFactura;
        }

        // Mostrar modal
        const modal = document.getElementById('modalFactura');
        if (modal) {
            modal.style.display = 'flex';
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

/**
 * Cerrar modal
 */
function cerrarModalFactura() {
    const modal = document.getElementById('modalFactura');
    if (modal) {
        modal.style.display = 'none';
    }
}

/**
 * Generar HTML de factura
 */
function generarHTMLFactura(datos) {
    if (!datos || !datos.prendas) {
        return '<p class="text-red-500">No hay datos disponibles</p>';
    }

    let html = `
        <div class="p-4">
            <h2 class="text-lg font-bold mb-4">FACTURA - PEDIDO #${datos.numero_pedido}</h2>
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <p><strong>Cliente:</strong> ${datos.cliente}</p>
                    <p><strong>Asesor:</strong> ${datos.asesor}</p>
                </div>
                <div>
                    <p><strong>Fecha:</strong> ${datos.fecha_pedido}</p>
                    <p><strong>Entrega:</strong> ${datos.fecha_entrega}</p>
                </div>
            </div>
            
            <table class="w-full border-collapse border border-gray-300">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border p-2 text-left">Artículo</th>
                        <th class="border p-2 text-center">Talla</th>
                        <th class="border p-2 text-center">Cantidad</th>
                        <th class="border p-2 text-center">Botón/Broche</th>
                    </tr>
                </thead>
                <tbody>
    `;

    datos.prendas.forEach(prenda => {
        if (prenda.variantes && prenda.variantes.length > 0) {
            prenda.variantes.forEach(variante => {
                html += `
                    <tr>
                        <td class="border p-2">${prenda.nombre || 'N/A'}</td>
                        <td class="border p-2 text-center">${variante.talla || '—'}</td>
                        <td class="border p-2 text-center">${variante.cantidad || 0}</td>
                        <td class="border p-2 text-center">${variante.broche || '—'}</td>
                    </tr>
                `;
            });
        }
    });

    html += `
                </tbody>
            </table>
        </div>
    `;

    return html;
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







