/**
 * Modal de Cálculo de Costos por Prenda
 * Archivo: modal-calculo-costos.js
 * Descripción: Gestiona el modal para calcular costos por prenda en cotizaciones
 */

// Variable global para almacenar los costos por prenda
window.costosPorPrenda = {};

/**
 * Abre el modal de cálculo de costos
 * @param {number} cotizacionId - ID de la cotización
 * @param {string} cliente - Nombre del cliente
 */
function abrirModalCalculoCostos(cotizacionId, cliente) {
    // Guardar cotización ID para guardar después
    window.cotizacionIdActual = cotizacionId;
    
    // Actualizar cliente en el header
    document.getElementById('modalCostosCliente').textContent = `CLIENTE: ${cliente}`;
    
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
    
    // Agregar una fila vacía
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
    `;
    
    const itemIndex = body.children.length;
    
    row.innerHTML = `
        <input type="text" 
               placeholder="Ej: Corte, Confección, Bordado..."
               style="padding: 0.75rem 1rem; border: none; border-right: 1px solid #e5e7eb; font-size: 0.9rem; background: white; width: 100%; box-sizing: border-box; outline: none;"
               onchange="actualizarItem(${itemIndex}, this.value, 'item')">
        <input type="number" 
               placeholder="0.00"
               step="0.01"
               min="0"
               style="padding: 0.75rem 1rem; border: none; border-right: 1px solid #e5e7eb; font-size: 0.9rem; background: white; text-align: center; width: 100%; box-sizing: border-box; outline: none;"
               onchange="actualizarItem(${itemIndex}, this.value, 'precio'); actualizarTotal()">
        <button onclick="eliminarFilaItem(this)" 
                style="background: #ef4444; color: white; border: none; padding: 0.5rem 0.75rem; cursor: pointer; font-weight: 600; transition: all 0.2s; font-size: 1rem; width: 100%; height: 100%; box-sizing: border-box; display: flex; align-items: center; justify-content: center;"
                onmouseover="this.style.background='#dc2626'"
                onmouseout="this.style.background='#ef4444'"
                title="Eliminar fila">
            🗑️
        </button>
    `;
    
    body.appendChild(row);
}

/**
 * Actualiza un item de la tabla
 */
function actualizarItem(itemIndex, valor, tipo) {
    if (!window.costosPorPrenda) {
        window.costosPorPrenda = {};
    }
    
    if (!window.costosPorPrenda[itemIndex]) {
        window.costosPorPrenda[itemIndex] = { item: '', precio: '' };
    }
    
    if (tipo === 'item') {
        window.costosPorPrenda[itemIndex].item = valor;
    } else if (tipo === 'precio') {
        window.costosPorPrenda[itemIndex].precio = parseFloat(valor) || 0;
    }
}

/**
 * Actualiza el total
 */
function actualizarTotal() {
    let total = 0;
    if (window.costosPorPrenda) {
        for (let key in window.costosPorPrenda) {
            total += window.costosPorPrenda[key].precio || 0;
        }
    }
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
 * Carga los costos guardados desde la base de datos
 * @param {number} cotizacionId - ID de la cotización
 * @param {array} prendas - Array de prendas
 */
function cargarCostosGuardados(cotizacionId, prendas) {
    fetch(`/contador/costos/obtener/${cotizacionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.costos && data.costos.length > 0) {
                console.log('Costos cargados desde BD:', data.costos);
                
                // Limpiar costos previos
                window.costosPorPrenda = {};
                
                // Cargar costos en la estructura global
                data.costos.forEach(costo => {
                    // Encontrar el índice de la prenda por nombre
                    const prendaIndex = prendas.findIndex(p => p.nombre === costo.nombre_prenda);
                    
                    if (prendaIndex !== -1) {
                        window.costosPorPrenda[prendaIndex] = {
                            nombre: costo.nombre_prenda,
                            descripcion: costo.descripcion,
                            imagenes: prendas[prendaIndex].imagenes,
                            items: costo.items || []
                        };
                        
                        // Recrear la tabla con los datos cargados
                        const contentDiv = document.getElementById(`prenda-content-${prendaIndex}`);
                        if (contentDiv) {
                            contentDiv.innerHTML = crearTablaPrecios(prendaIndex, costo.nombre_prenda, costo.descripcion, prendas[prendaIndex].imagenes);
                        }
                    }
                });
                
                console.log('Costos cargados en memoria:', window.costosPorPrenda);
            }
        })
        .catch(error => {
            console.error('Error al cargar costos guardados:', error);
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
 * Actualiza un item de la tabla
 */
function actualizarItem(itemIndex, valor, tipo) {
    if (!window.costosPorPrenda) {
        window.costosPorPrenda = {};
    }
    
    if (!window.costosPorPrenda[itemIndex]) {
        window.costosPorPrenda[itemIndex] = { item: '', precio: '' };
    }
    
    if (tipo === 'item') {
        window.costosPorPrenda[itemIndex].item = valor;
    } else if (tipo === 'precio') {
        window.costosPorPrenda[itemIndex].precio = parseFloat(valor) || 0;
    }
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
    // Filtrar items vacíos
    const costosFiltrados = {};
    for (let prendaId in window.costosPorPrenda) {
        costosFiltrados[prendaId] = {
            nombre: window.costosPorPrenda[prendaId].nombre,
            descripcion: window.costosPorPrenda[prendaId].descripcion,
            imagenes: window.costosPorPrenda[prendaId].imagenes,
            items: window.costosPorPrenda[prendaId].items.filter(item => item.item.trim() !== '')
        };
    }
    
    // Enviar al servidor
    fetch('/contador/costos/guardar', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify({
            cotizacion_id: window.cotizacionIdActual,
            costos: costosFiltrados
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Costos guardados en BD:', costosFiltrados);
            
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

/**
 * Abre una imagen en fullscreen
 * @param {string} src - URL de la imagen
 */
function abrirImagenFullscreenModal(src) {
    // Crear modal fullscreen si no existe
    let modal = document.getElementById('imagenFullscreenModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'imagenFullscreenModal';
        modal.style.cssText = `
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.95);
            z-index: 10000;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        `;
        
        modal.innerHTML = `
            <div style="position: relative; max-width: 90vw; max-height: 90vh;">
                <button onclick="cerrarImagenFullscreenModal()" style="position: absolute; top: -40px; right: 0; background: white; border: none; color: #333; font-size: 2rem; cursor: pointer; padding: 0; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.2s;" onmouseover="this.style.background='#f0f0f0'" onmouseout="this.style.background='white'">
                    ✕
                </button>
                <img id="imagenFullscreen" src="" alt="Imagen" style="max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 4px;">
            </div>
        `;
        
        document.body.appendChild(modal);
    }
    
    document.getElementById('imagenFullscreen').src = src;
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

/**
 * Cierra la imagen en fullscreen
 */
function cerrarImagenFullscreenModal() {
    const modal = document.getElementById('imagenFullscreenModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// ===== EVENT LISTENERS =====

// Cerrar modal al hacer clic fuera
document.addEventListener('click', function(event) {
    const modal = document.getElementById('calculoCostosModal');
    if (modal && event.target === modal) {
        cerrarModalCalculoCostos();
    }
    
    // Cerrar fullscreen al hacer clic fuera
    const fullscreenModal = document.getElementById('imagenFullscreenModal');
    if (fullscreenModal && event.target === fullscreenModal) {
        cerrarImagenFullscreenModal();
    }
});

// Cerrar modal con ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        cerrarModalCalculoCostos();
        cerrarImagenFullscreenModal();
    }
});

