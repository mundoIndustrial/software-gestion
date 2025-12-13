/**
 * Modal de Cálculo de Costos por Prenda
 * Archivo: modal-calculo-costos.js
 * Descripción: Gestiona el modal para calcular costos por prenda en cotizaciones
 */

/**
 * Abre el modal de cálculo de costos
 * @param {number} cotizacionId - ID de la cotización
 * @param {string} cliente - Nombre del cliente
 */
function abrirModalCalculoCostos(cotizacionId, cliente) {
    // Guardar cotización ID para guardar después
    window.cotizacionIdActual = cotizacionId;
    
    // Obtener las prendas de la cotización
    fetch(`/contador/cotizacion/${cotizacionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.html) {
                // Parsear el HTML para extraer las prendas
                const parser = new DOMParser();
                const doc = parser.parseFromString(data.html, 'text/html');
                
                // Buscar todos los títulos de prendas (h5)
                const prendasElements = doc.querySelectorAll('h5');
                const prendas = [];
                
                prendasElements.forEach((el, index) => {
                    const nombrePrenda = el.textContent.trim();
                    if (nombrePrenda && nombrePrenda !== 'ESPECIFICACIONES DE LA ORDEN' && 
                        nombrePrenda !== 'TÉCNICAS' && nombrePrenda !== 'OBSERVACIONES TÉCNICAS' &&
                        nombrePrenda !== 'OBSERVACIONES GENERALES' && nombrePrenda !== 'PRENDAS DETALLADAS' &&
                        nombrePrenda !== 'Prenda sin nombre') {
                        
                        // Obtener descripción
                        let descripcion = '';
                        let elemento = el.nextElementSibling;
                        if (elemento && elemento.tagName === 'P') {
                            descripcion = elemento.textContent.trim();
                        }
                        
                        prendas.push({
                            id: index,
                            nombre: nombrePrenda,
                            descripcion: descripcion
                        });
                    }
                });
                
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
                    tab.onclick = () => cambiarPrendaTab(prenda.id, prendas);
                    tabsContainer.appendChild(tab);
                });
                
                // Mostrar primera prenda
                cambiarPrendaTab(0, prendas);
                
                // Inicializar tabla vacía
                limpiarTablaPrecios();
                
                // Cargar items guardados si existen
                cargarItemsGuardados(cotizacionId);
                
                // Mostrar modal
                document.getElementById('calculoCostosModal').style.display = 'flex';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar las prendas');
        });
}

/**
 * Cambia entre tabs de prendas
 * @param {number} prendaId - ID de la prenda
 * @param {array} prendas - Array de prendas
 */
function cambiarPrendaTab(prendaId, prendas) {
    // Actualizar descripción
    const prenda = prendas.find(p => p.id === prendaId);
    if (prenda) {
        document.getElementById('prendasDescripcion').textContent = prenda.descripcion || '';
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
}

/**
 * Limpia la tabla de precios
 */
function limpiarTablaPrecios() {
    const body = document.getElementById('tablaPreciosBody');
    body.innerHTML = '';
    
    // Agregar una fila inicial
    agregarFilaItem();
}

/**
 * Agrega una nueva fila de item a la tabla
 */
function agregarFilaItem() {
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
               style="padding: 0.75rem 1rem; border: none; border-right: 1px solid #e5e7eb; font-size: 0.9rem; background: white; width: 100%; box-sizing: border-box; outline: none; color: #000;"
               onchange="actualizarTotal()">
        <input type="number" 
               placeholder="0.00"
               step="0.01"
               min="0"
               style="padding: 0.75rem 1rem; border: none; border-right: 1px solid #e5e7eb; font-size: 0.9rem; background: white; text-align: center; width: 100%; box-sizing: border-box; outline: none; color: #000;"
               onchange="actualizarTotal()">
        <button onclick="eliminarFilaItem(this)" 
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
function actualizarTotal() {
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
function eliminarFilaItem(button) {
    button.closest('div').remove();
    actualizarTotal();
}

/**
 * Carga los items guardados desde la base de datos
 * @param {number} cotizacionId - ID de la cotización
 */
function cargarItemsGuardados(cotizacionId) {
    fetch(`/contador/costos/obtener/${cotizacionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.costos && data.costos.length > 0) {
                console.log('Costos cargados desde BD:', data.costos);
                
                // Obtener la prenda actual (tab activo)
                const prendasTabs = document.querySelectorAll('#prendasTabs button');
                let prendaActualNombre = '';
                
                prendasTabs.forEach((tab) => {
                    if (tab.style.background.includes('3b82f6')) {
                        prendaActualNombre = tab.textContent.trim();
                    }
                });
                
                // Buscar los costos de la prenda actual
                const costosPrenda = data.costos.find(costo => 
                    costo.nombre_prenda === prendaActualNombre || 
                    costo.nombre_prenda.includes(prendaActualNombre)
                );
                
                if (costosPrenda && costosPrenda.items) {
                    // Limpiar tabla
                    const tablaPreciosBody = document.getElementById('tablaPreciosBody');
                    tablaPreciosBody.innerHTML = '';
                    
                    // Parsear items si es string
                    let items = costosPrenda.items;
                    if (typeof items === 'string') {
                        items = JSON.parse(items);
                    }
                    
                    // Agregar cada item guardado
                    items.forEach(item => {
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
                                   style="padding: 0.75rem 1rem; border: none; border-right: 1px solid #e5e7eb; font-size: 0.9rem; background: white; width: 100%; box-sizing: border-box; outline: none; color: #000;"
                                   onchange="actualizarTotal()">
                            <input type="number" 
                                   placeholder="0.00"
                                   value="${parseFloat(item.precio) || 0}"
                                   step="0.01"
                                   min="0"
                                   style="padding: 0.75rem 1rem; border: none; border-right: 1px solid #e5e7eb; font-size: 0.9rem; background: white; text-align: center; width: 100%; box-sizing: border-box; outline: none; color: #000;"
                                   onchange="actualizarTotal()">
                            <button onclick="eliminarFilaItem(this)" 
                                    style="background: #ef4444; color: white; border: none; padding: 0.5rem 0.75rem; cursor: pointer; font-weight: 600; transition: all 0.2s; font-size: 1rem; width: 100%; height: 100%; box-sizing: border-box; display: flex; align-items: center; justify-content: center;"
                                    onmouseover="this.style.background='#dc2626'"
                                    onmouseout="this.style.background='#ef4444'"
                                    title="Eliminar fila">
                                🗑️
                            </button>
                        `;
                        
                        tablaPreciosBody.appendChild(row);
                    });
                    
                    // Actualizar total
                    actualizarTotal();
                }
            }
        })
        .catch(error => {
            console.error('Error al cargar items guardados:', error);
        });
}


/**
 * Cierra el modal de cálculo de costos
 */
function cerrarModalCalculoCostos() {
    document.getElementById('calculoCostosModal').style.display = 'none';
    window.costosPorPrenda = {};
}

/**
 * Guarda los costos calculados en la base de datos
 */
function guardarCalculoCostos() {
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
    
    // Validar que haya al menos un item
    if (items.length === 0) {
        Swal.fire({
            title: '⚠️ Campos Vacíos',
            html: `
                <div style="text-align: left; color: #4b5563;">
                    <p style="margin: 0; font-size: 0.95rem;">
                        Por favor, agrega al menos un item con nombre antes de guardar.
                    </p>
                </div>
            `,
            icon: 'warning',
            confirmButtonColor: '#f59e0b',
            confirmButtonText: 'Entendido'
        });
        return;
    }
    
    // Obtener información de la prenda actual
    const prendasDescripcion = document.getElementById('prendasDescripcion');
    const prendasTabs = document.querySelectorAll('#prendasTabs button');
    
    let prendaNombre = 'Prenda sin nombre';
    let prendaDescripcion = '';
    
    // Encontrar el tab activo (con el color azul)
    prendasTabs.forEach((tab) => {
        if (tab.style.background.includes('3b82f6')) {
            prendaNombre = tab.textContent.trim();
        }
    });
    
    prendaDescripcion = prendasDescripcion.textContent.trim();
    
    // Preparar datos para enviar en la estructura esperada por el controlador
    const costos = {
        0: {
            nombre: prendaNombre,
            descripcion: prendaDescripcion,
            items: items
        }
    };
    
    // Enviar al servidor
    fetch('/contador/costos/guardar', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify({
            cotizacion_id: window.cotizacionIdActual,
            costos: costos
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Costos guardados en BD:', costos);
            
            // Mostrar modal de éxito
            Swal.fire({
                title: '✓ Costos Guardados',
                html: `
                    <div style="text-align: left; color: #4b5563;">
                        <p style="margin: 0 0 0.75rem 0; font-size: 0.95rem;">
                            ✅ ${data.message}
                        </p>
                        <div style="background: #d1fae5; border-left: 4px solid #10b981; padding: 0.75rem; border-radius: 4px; margin: 0.75rem 0;">
                            <p style="margin: 0; font-size: 0.85rem; color: #065f46; font-weight: 600;">
                                📊 Los costos han sido registrados correctamente en la base de datos
                            </p>
                        </div>
                    </div>
                `,
                icon: 'success',
                confirmButtonColor: '#10b981',
                confirmButtonText: 'Entendido',
                didClose: () => {
                    cerrarModalCalculoCostos();
                }
            });
        } else {
            Swal.fire({
                title: '❌ Error',
                html: `
                    <div style="text-align: left; color: #4b5563;">
                        <p style="margin: 0; font-size: 0.95rem;">
                            ${data.message || 'No se pudieron guardar los costos'}
                        </p>
                    </div>
                `,
                icon: 'error',
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Entendido'
            });
        }
    })
    .catch(error => {
        console.error('Error al guardar costos:', error);
        
        Swal.fire({
            title: '❌ Error de Conexión',
            html: `
                <div style="text-align: left; color: #4b5563;">
                    <p style="margin: 0; font-size: 0.95rem;">
                        Error al guardar los costos: ${error.message}
                    </p>
                </div>
            `,
            icon: 'error',
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Entendido'
        });
    });
}


// ===== EVENT LISTENERS =====

// Cerrar modal al hacer clic fuera
document.addEventListener('click', function(event) {
    const modal = document.getElementById('calculoCostosModal');
    if (modal && event.target === modal) {
        cerrarModalCalculoCostos();
    }
});

// Cerrar modal con ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        cerrarModalCalculoCostos();
    }
});

