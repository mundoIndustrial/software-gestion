/**
 * Modal de Cálculo de Costos por Prenda
 * Archivo: modal-calculo-costos.js
 * Descripción: Gestiona el modal para calcular costos por prenda en cotizaciones
 */

// Agregar estilos CSS para z-index alto en SweetAlert
if (!document.getElementById('swal-high-z-index-style')) {
    const style = document.createElement('style');
    style.id = 'swal-high-z-index-style';
    style.textContent = `
        .swal-high-z-index {
            z-index: 10000 !important;
        }
    `;
    document.head.appendChild(style);
}

// Variable global para rastrear la prenda actual
let prendaActualIndex = 0;

// Lista de prendas del modal (incluye prenda_cot_id real)
let prendasModalActuales = [];

// Almacenamiento temporal de costos de todas las prendas
let costosTodasPrendas = {};

/**
 * Abre el modal de cálculo de costos
 * @param {number} cotizacionId - ID de la cotización
 * @param {string} cliente - Nombre del cliente
 */
window.abrirModalCalculoCostos = function(cotizacionId, cliente) {
    // Guardar cotización ID para guardar después
    window.cotizacionIdActual = cotizacionId;
    prendaActualIndex = 0;
    prendasModalActuales = [];
    
    // Limpiar memoria de costos al abrir el modal
    costosTodasPrendas = {};
    
    // Obtener las prendas de la cotización
    fetch(`/contador/cotizacion/${cotizacionId}`)
        .then(response => response.json())
        .then(data => {
            // El endpoint retorna {cotizacion: {...}, prendas_cotizaciones: [...]}
            let prendas = [];
            
            if (data.prendas_cotizaciones && Array.isArray(data.prendas_cotizaciones)) {
                prendas = data.prendas_cotizaciones.map((prenda, index) => {
                    // Generar descripción exactamente igual a cotizacion.js
                    let descripcionCompleta = prenda.descripcion_formateada || prenda.descripcion || '';
                    
                    // Si hay técnicas de logo para esta prenda, agregar ubicaciones
                    const tecnicasPrendaArray = data.logo_cotizacion && data.logo_cotizacion.tecnicas_prendas 
                        ? data.logo_cotizacion.tecnicas_prendas.filter(tp => tp.prenda_id === prenda.id)
                        : [];
                    const esPrendaLogo = tecnicasPrendaArray && tecnicasPrendaArray.length > 0;
                    
                    if (tecnicasPrendaArray && tecnicasPrendaArray.length > 0) {
                        // Consolidar ubicaciones por técnica
                        const ubicacionesPorTecnica = {};
                        tecnicasPrendaArray.forEach(tp => {
                            const tecnicaNombre = tp.tipo_logo_nombre || 'Logo';
                            if (tp.ubicaciones) {
                                let ubicacionesArray = Array.isArray(tp.ubicaciones) ? tp.ubicaciones : [String(tp.ubicaciones)];
                                // Filtrar vacíos y remover corchetes
                                ubicacionesArray = ubicacionesArray
                                    .map(u => String(u).replace(/[\[\]]/g, '').trim())
                                    .filter(u => u);
                                if (ubicacionesArray.length > 0) {
                                    if (!ubicacionesPorTecnica[tecnicaNombre]) {
                                        ubicacionesPorTecnica[tecnicaNombre] = [];
                                    }
                                    ubicacionesPorTecnica[tecnicaNombre] = ubicacionesPorTecnica[tecnicaNombre].concat(ubicacionesArray);
                                }
                            }
                        });
                        
                        // Agregar ubicaciones a la descripción SIN corchetes
                        if (Object.keys(ubicacionesPorTecnica).length > 0) {
                            if (descripcionCompleta) {
                                descripcionCompleta += ', ';
                            }
                            const ubicacionesTexto = Object.entries(ubicacionesPorTecnica)
                                .map(([tecnica, ubicaciones]) => ubicaciones.join(', '))
                                .join(', ');
                            descripcionCompleta += ubicacionesTexto;
                        }
                    }
                    
                    // Agregar descripción y ubicaciones de prenda_cot_reflectivo
                    if (prenda.prenda_cot_reflectivo) {
                        const pcrRef = prenda.prenda_cot_reflectivo;
                        
                        // Agregar descripción del reflectivo
                        if (pcrRef.descripcion) {
                            if (descripcionCompleta) {
                                descripcionCompleta += ', ';
                            }
                            descripcionCompleta += pcrRef.descripcion;
                        }
                        
                        // Agregar ubicaciones del reflectivo
                        if (pcrRef.ubicaciones && Array.isArray(pcrRef.ubicaciones)) {
                            if (descripcionCompleta) {
                                descripcionCompleta += ', ';
                            }
                            const ubicacionesReflectivo = pcrRef.ubicaciones
                                .map(u => u.ubicacion ? u.ubicacion + (u.descripcion ? ': ' + u.descripcion : '') : '')
                                .filter(u => u)
                                .join(', ');
                            descripcionCompleta += ubicacionesReflectivo;
                        }
                    }
                    
                    const nombreBase = prenda.nombre_prenda || `Prenda ${index + 1}`;
                    return {
                        id: prenda.id,
                        nombre_base: nombreBase,
                        nombre: esPrendaLogo ? `${nombreBase} - logo` : nombreBase,
                        es_logo: esPrendaLogo,
                        descripcion: descripcionCompleta || prenda.descripcion_formateada || prenda.descripcion || ''
                    };
                });
            }

            prendasModalActuales = prendas;
            
            if (prendas.length === 0) {
                alert('No se encontraron prendas en esta cotización');
                return;
            }
            
            // Llenar tabs de prendas
            const tabsContainer = document.getElementById('prendasTabs');
            tabsContainer.innerHTML = '';
            
            prendas.forEach((prenda, index) => {
                const tab = document.createElement('button');
                tab.style.cssText = `
                    padding: 0.75rem 1.25rem;
                    background: ${index === 0 ? 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)' : '#374151'};
                    color: white;
                    border: none;
                    border-radius: 6px;
                    cursor: pointer;
                    font-weight: 600;
                    transition: all 0.2s;
                    white-space: nowrap;
                    text-transform: uppercase;
                    font-size: 0.8rem;
                    letter-spacing: 0.3px;
                `;
                tab.textContent = prenda.nombre;
                // Guardar nombre base para que el guardado/carga de costos no dependa del sufijo visual "- logo"
                tab.dataset.nombreBase = prenda.nombre_base || prenda.nombre;
                tab.dataset.prendaCotId = String(prenda.id);
                tab.onclick = () => cambiarPrendaTab(prenda.id, prendas);
                tabsContainer.appendChild(tab);
            });
            
            // Mostrar primera prenda
            window.cambiarPrendaTab(prendas[0].id, prendas);
            
            // Inicializar tabla vacía
            window.limpiarTablaPrecios();
            
            // Cargar items guardados si existen
            window.cargarItemsGuardados(cotizacionId);
            
            // Mostrar modal
            document.getElementById('calculoCostosModal').style.display = 'flex';
        })
        .catch(error => {

            alert('Error al cargar las prendas: ' + error.message);
        });
}

/**
 * Cambia entre tabs de prendas
 * @param {number} prendaId - ID de la prenda
 * @param {array} prendas - Array de prendas
 */
window.cambiarPrendaTab = function(prendaId, prendas) {
    // Guardar los costos de la prenda actual antes de cambiar
    guardarCostosPrendaActual();
    
    // Guardar el índice de la prenda actual
    const prendaAnteriorIndex = prendaActualIndex;
    prendaActualIndex = prendas.findIndex(p => p.id === prendaId);
    
    // Actualizar descripción
    const prenda = prendas.find(p => p.id === prendaId);
    if (prenda) {
        document.getElementById('prendasDescripcion').innerHTML = prenda.descripcion || '';
    }
    
    // Actualizar estilos de tabs
    const tabs = document.querySelectorAll('#prendasTabs button');
    tabs.forEach((tab, index) => {
        if (prendas[index] && prendas[index].id === prendaId) {
            tab.style.background = 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)';
        } else if (tab) {
            tab.style.background = '#374151';
        }
    });
    
    // Limpiar tabla
    limpiarTablaPrecios();
    
    // Cargar costos guardados de esta prenda si existen
    const prendaActual = prendas[prendaActualIndex];
    cargarCostosPrendaDesdeMemoria(prendaActual ? prendaActual.id : null);
}

/**
 * Guarda los costos de la prenda actual en memoria temporal
 */
window.guardarCostosPrendaActual = function() {
    const body = document.getElementById('tablaPreciosBody');
    const rows = body.querySelectorAll('div[style*="grid-template-columns"]');
    
    const items = [];
    rows.forEach(row => {
        const inputs = row.querySelectorAll('input');
        if (inputs.length >= 2) {
            const item = inputs[0].value.trim();
            const precio = parseFloat(inputs[1].value) || 0;
            
            // Guardar el item aunque el precio esté vacío (se guarda como 0)
            if (item) {
                items.push({ item, precio });
            }
        }
    });
    
    // Guardar en el objeto global si hay items
    if (items.length > 0) {
        const prendasTabs = document.querySelectorAll('#prendasTabs button');
        const tabActual = prendasTabs[prendaActualIndex];
        const prendaCotId = tabActual?.dataset?.prendaCotId ? parseInt(tabActual.dataset.prendaCotId, 10) : null;
        if (!prendaCotId) {
            return;
        }

        const nombrePrenda = tabActual?.dataset?.nombreBase?.trim() ||
            tabActual?.textContent.trim() ||
            `Prenda ${prendaActualIndex}`;
        const descripcion = document.getElementById('prendasDescripcion').textContent.trim();
        
        costosTodasPrendas[prendaCotId] = {
            prenda_cot_id: prendaCotId,
            nombre: nombrePrenda,
            descripcion: descripcion,
            items: items
        };
        

    } else {
        // Si no hay items, eliminar de la memoria
        const prendasTabs = document.querySelectorAll('#prendasTabs button');
        const tabActual = prendasTabs[prendaActualIndex];
        const prendaCotId = tabActual?.dataset?.prendaCotId ? parseInt(tabActual.dataset.prendaCotId, 10) : null;
        if (prendaCotId) {
            delete costosTodasPrendas[prendaCotId];
        }
    }
}

/**
 * Carga los costos de una prenda desde la memoria temporal
 */
function cargarCostosPrendaDesdeMemoria(prendaCotId) {
    if (prendaCotId && costosTodasPrendas[prendaCotId]) {
        const costos = costosTodasPrendas[prendaCotId];
        const body = document.getElementById('tablaPreciosBody');
        body.innerHTML = '';
        
        costos.items.forEach(item => {
            const row = document.createElement('div');
            row.style.cssText = `
                display: grid;
                grid-template-columns: 1fr 150px 80px;
                gap: 0;
                background: #f5f5f5;
                border-radius: 8px;
                align-items: center;
                overflow: hidden;
                border: 1px solid #e5e7eb;
                flex-shrink: 0;
                min-height: 50px;
            `;
            
            row.innerHTML = `
                <input type="text" 
                       placeholder="Ej: Corte, Confección, Bordado..."
                       value="${item.item || ''}"
                       style="padding: 0.75rem 1rem; border: none; border-right: 1px solid #e5e7eb; font-size: 0.9rem; background: white; width: 100%; box-sizing: border-box; outline: none; color: #0f172a;"
                       onchange="window.actualizarTotal()">
                <input type="number" 
                       placeholder="0.00"
                       value="${parseFloat(item.precio) || 0}"
                       step="0.01"
                       min="0"
                       style="padding: 0.75rem 1rem; border: none; border-right: 1px solid #e5e7eb; font-size: 0.9rem; background: white; text-align: center; width: 100%; box-sizing: border-box; outline: none; color: #0f172a;"
                       onchange="window.actualizarTotal()">
                <button onclick="window.eliminarFilaItem(this)" 
                        style="padding: 0.75rem; background: transparent; border: none; cursor: pointer; color: #ef4444; font-size: 1.2rem; transition: all 0.2s; width: 100%; height: 100%;"
                        onmouseover="this.style.background='#fee2e2'"
                        onmouseout="this.style.background='transparent'"
                        title="Eliminar item">
                    ×
                </button>
            `;
            
            body.appendChild(row);
        });
        
        window.actualizarTotal();

    }
}

/**
 * Limpia la tabla de precios
 */
window.limpiarTablaPrecios = function() {
    const body = document.getElementById('tablaPreciosBody');
    body.innerHTML = '';
    
    // Agregar una fila inicial
    window.agregarFilaItem();
}

/**
 * Agrega una nueva fila de item a la tabla
 */
window.agregarFilaItem = function() {
    const body = document.getElementById('tablaPreciosBody');
    
    const row = document.createElement('div');
    row.style.cssText = `
        display: grid;
        grid-template-columns: 1fr 150px 80px;
        gap: 0;
        background: #f5f5f5;
        border-radius: 8px;
        align-items: center;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        flex-shrink: 0;
        min-height: 50px;
    `;
    
    const itemIndex = body.children.length;
    
    row.innerHTML = `
        <input type="text" 
               placeholder="Ej: Corte, Confección, Bordado..."
               style="padding: 0.75rem 1rem; border: none; border-right: 1px solid #e5e7eb; font-size: 0.9rem; background: white; width: 100%; box-sizing: border-box; outline: none; color: #0f172a;"
               onchange="window.actualizarTotal()">
        <input type="number" 
               placeholder="0.00"
               step="0.01"
               min="0"
               style="padding: 0.75rem 1rem; border: none; border-right: 1px solid #e5e7eb; font-size: 0.9rem; background: white; text-align: center; width: 100%; box-sizing: border-box; outline: none; color: #0f172a;"
               onchange="window.actualizarTotal()">
        <button onclick="window.eliminarFilaItem(this)" 
                style="background: #ef4444; color: white; border: none; padding: 0.5rem 0.75rem; cursor: pointer; font-weight: 600; transition: all 0.2s; font-size: 1rem; width: 100%; height: 100%; box-sizing: border-box; display: flex; align-items: center; justify-content: center;"
                onmouseover="this.style.background='#dc2626'"
                onmouseout="this.style.background='#ef4444'"
                title="Eliminar fila">
            🗑️
        </button>
    `;
    
    body.appendChild(row);
    
    // Auto-scroll hacia abajo si es necesario
    setTimeout(() => {
        body.scrollTop = body.scrollHeight;
    }, 50);
}

/**
 * Actualiza el total de costos
 */
window.actualizarTotal = function() {
    const tablaPreciosBody = document.getElementById('tablaPreciosBody');
    const filas = tablaPreciosBody.querySelectorAll('div[style*="grid-template-columns"]');
    
    let total = 0;
    filas.forEach((fila) => {
        const inputs = fila.querySelectorAll('input');
        if (inputs.length >= 2) {
            const precio = parseFloat(inputs[1].value) || 0;
            total += precio;
        }
    });
    
    document.getElementById('totalCosto').textContent = `$${total.toFixed(2)}`;
}

/**
 * Elimina una fila de item
 */
window.eliminarFilaItem = function(button) {
    button.closest('div').remove();
    window.actualizarTotal();
}

/**
 * Carga los items guardados desde la base de datos
 * @param {number} cotizacionId - ID de la cotización
 */
window.cargarItemsGuardados = function(cotizacionId) {
    fetch(`/contador/costos/obtener/${cotizacionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.costos && data.costos.length > 0) {

                
                // Obtener todos los tabs de prendas
                const prendasTabs = document.querySelectorAll('#prendasTabs button');
                
                // Cargar todos los costos en la memoria temporal
                data.costos.forEach(costoPrenda => {
                    const prendaCotId = costoPrenda.prenda_cot_id ? parseInt(costoPrenda.prenda_cot_id, 10) : null;
                    
                    if (prendaCotId && costoPrenda.items) {
                        // Parsear items si es string
                        let items = costoPrenda.items;
                        if (typeof items === 'string') {
                            items = JSON.parse(items);
                        }
                        
                        // Guardar en memoria temporal
                        costosTodasPrendas[prendaCotId] = {
                            prenda_cot_id: prendaCotId,
                            nombre: costoPrenda.nombre_prenda,
                            descripcion: '',
                            items: items
                        };
                        
                    }
                });
                
                // Cargar los costos de la prenda actual (primera prenda)
                const tabActual = prendasTabs[prendaActualIndex];
                const prendaCotIdActual = tabActual?.dataset?.prendaCotId ? parseInt(tabActual.dataset.prendaCotId, 10) : null;
                cargarCostosPrendaDesdeMemoria(prendaCotIdActual);
            }
        })
        .catch(error => {

        });
}


/**
 * Cierra el modal de cálculo de costos
 */
window.cerrarModalCalculoCostos = function() {
    document.getElementById('calculoCostosModal').style.display = 'none';
    window.costosPorPrenda = {};
}

/**
 * Guarda los costos calculados en la base de datos
 */
window.guardarCalculoCostos = function() {
    // Recopilar datos de las filas de la tabla
    const tablaPreciosBody = document.getElementById('tablaPreciosBody');
    const filas = tablaPreciosBody.querySelectorAll('div[style*="grid-template-columns"]');
    
    const items = [];
    filas.forEach((fila, index) => {
        const inputs = fila.querySelectorAll('input');
        if (inputs.length >= 2) {
            const itemNombre = inputs[0].value.trim();
            const itemPrecio = parseFloat(inputs[1].value) || 0;
            
            // Solo agregar si tiene nombre
            if (itemNombre) {
                items.push({
                    item: itemNombre,
                    precio: itemPrecio
                });
            }
        }
    });
    
    // Guardar los costos de la prenda actual antes de enviar
    guardarCostosPrendaActual();
    
    // Validar que haya costos para guardar
    if (Object.keys(costosTodasPrendas).length === 0) {
        Swal.fire({
            title: ' Sin Costos',
            html: `
                <div style="text-align: left; color: #4b5563;">
                    <p style="margin: 0; font-size: 0.95rem;">
                        No hay costos para guardar. Por favor, agrega items a las prendas antes de guardar.
                    </p>
                </div>
            `,
            icon: 'warning',
            confirmButtonColor: '#f59e0b',
            confirmButtonText: 'Entendido',
            customClass: {
                container: 'swal-high-z-index'
            }
        });
        return;
    }
    
    // Enviar al servidor todos los costos guardados (incluyendo los que se eliminaron)
    fetch('/contador/costos/guardar', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify({
            cotizacion_id: window.cotizacionIdActual,
            costos: costosTodasPrendas
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mostrar modal de éxito
            Swal.fire({
                title: '✓ Costos Guardados',
                html: `
                    <div style="text-align: left; color: #4b5563;">
                        <p style="margin: 0 0 0.75rem 0; font-size: 0.95rem;">
                             ${data.message}
                        </p>
                        <div style="background: #d1fae5; border-left: 4px solid #10b981; padding: 0.75rem; border-radius: 4px; margin: 0.75rem 0;">
                            <p style="margin: 0; font-size: 0.85rem; color: #065f46; font-weight: 600;">
                                 Los costos han sido actualizados correctamente en la base de datos
                            </p>
                        </div>
                    </div>
                `,
                icon: 'success',
                confirmButtonColor: '#10b981',
                confirmButtonText: 'Entendido',
                customClass: {
                    container: 'swal-high-z-index'
                },
                didClose: () => {
                    // Limpiar memoria de costos
                    costosTodasPrendas = {};
                    window.cerrarModalCalculoCostos();
                    // Recargar tabla de cotizaciones
                    if (window.location.href.includes('/contador')) {
                        location.reload();
                    }
                }
            });
        } else {
            Swal.fire({
                title: ' Error',
                html: `
                    <div style="text-align: left; color: #4b5563;">
                        <p style="margin: 0; font-size: 0.95rem;">
                            ${data.message || 'No se pudieron guardar los costos'}
                        </p>
                    </div>
                `,
                icon: 'error',
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Entendido',
                customClass: {
                    container: 'swal-high-z-index'
                }
            });
        }
    })
    .catch(error => {
        Swal.fire({
            title: ' Error de Conexión',
            html: `
                <div style="text-align: left; color: #4b5563;">
                    <p style="margin: 0; font-size: 0.95rem;">
                        Error al guardar los costos: ${error.message}
                    </p>
                </div>
            `,
            icon: 'error',
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Entendido',
            customClass: {
                container: 'swal-high-z-index'
            }
        });
    });
}


// ===== EVENT LISTENERS =====

// Cerrar modal al hacer clic fuera
document.addEventListener('click', function(event) {
    const modal = document.getElementById('calculoCostosModal');
    if (modal && event.target === modal) {
        window.cerrarModalCalculoCostos();
    }
});

// Cerrar modal con ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        window.cerrarModalCalculoCostos();
    }
});

// Exportar funciones al objeto global window para asegurar accesibilidad
if (typeof window !== 'undefined') {
    // Estas funciones se crean aquí nuevamente como window.* para máxima compatibilidad
    window.cambiarPrendaTab = window.cambiarPrendaTab || cambiarPrendaTab;
    window.guardarCostosPrendaActual = window.guardarCostosPrendaActual || guardarCostosPrendaActual;
    window.limpiarTablaPrecios = window.limpiarTablaPrecios || limpiarTablaPrecios;
    window.agregarFilaItem = window.agregarFilaItem || agregarFilaItem;
    window.actualizarTotal = window.actualizarTotal || actualizarTotal;
    window.eliminarFilaItem = window.eliminarFilaItem || eliminarFilaItem;
    window.cargarItemsGuardados = window.cargarItemsGuardados || cargarItemsGuardados;
    window.cerrarModalCalculoCostos = window.cerrarModalCalculoCostos || cerrarModalCalculoCostos;
    window.guardarCalculoCostos = window.guardarCalculoCostos || guardarCalculoCostos;
}

