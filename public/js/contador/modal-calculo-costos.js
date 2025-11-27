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
    
    // Obtener las prendas de la cotización
    fetch(`/contador/cotizacion/${cotizacionId}`)
        .then(response => response.text())
        .then(html => {
            // Parsear el HTML para extraer las prendas
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // Buscar todos los títulos de prendas (h3 con nombre de prenda)
            const prendasElements = doc.querySelectorAll('h3');
            const prendas = [];
            
            prendasElements.forEach((el, index) => {
                const nombrePrenda = el.textContent.trim();
                if (nombrePrenda && nombrePrenda !== 'ESPECIFICACIONES DE LA ORDEN' && 
                    nombrePrenda !== 'TÉCNICAS' && nombrePrenda !== 'OBSERVACIONES TÉCNICAS' &&
                    nombrePrenda !== 'OBSERVACIONES GENERALES' && nombrePrenda !== 'PRENDAS DETALLADAS') {
                    
                    // Obtener la descripción (párrafo siguiente al h3)
                    let descripcion = '';
                    let imagenes = [];
                    let detalles = {};
                    
                    let elemento = el.nextElementSibling;
                    
                    // Obtener descripción del párrafo (ya incluye especificaciones unidas)
                    if (elemento && elemento.tagName === 'P') {
                        descripcion = elemento.textContent.trim();
                        elemento = elemento.nextElementSibling;
                    }
                    
                    // Obtener detalles de la prenda (Color, Tela, Manga, Especificaciones)
                    // Buscar el div con clase o estilo que contenga los detalles
                    let elementoBusquedaDetalles = el.nextElementSibling;
                    while (elementoBusquedaDetalles) {
                        if (elementoBusquedaDetalles.tagName === 'H3') {
                            break; // Encontramos otra prenda
                        }
                        
                        // Buscar div con "Detalles de la Prenda"
                        if (elementoBusquedaDetalles.textContent.includes('Detalles de la Prenda')) {
                            // Extraer todos los detalles (Color, Tela, Manga, Especificaciones)
                            const detallesDivs = elementoBusquedaDetalles.querySelectorAll('div > div');
                            detallesDivs.forEach(detDiv => {
                                const label = detDiv.querySelector('div:first-child');
                                const valor = detDiv.querySelector('div:last-child');
                                if (label && valor) {
                                    const labelText = label.textContent.replace(':', '').trim();
                                    const valorText = valor.textContent.trim();
                                    if (labelText && valorText) {
                                        detalles[labelText] = valorText;
                                    }
                                }
                            });
                            break;
                        }
                        
                        elementoBusquedaDetalles = elementoBusquedaDetalles.nextElementSibling;
                    }
                    
                    // Obtener imágenes - buscar todas las imágenes después del h3
                    // Pueden estar en un div con data-producto-index o en cualquier contenedor
                    let elementoBusqueda = el.nextElementSibling;
                    while (elementoBusqueda) {
                        // Si encontramos otro h3, detenemos la búsqueda
                        if (elementoBusqueda.tagName === 'H3') {
                            break;
                        }
                        
                        // Buscar si tiene atributo data-todas-imagenes (JSON)
                        if (elementoBusqueda.hasAttribute('data-todas-imagenes')) {
                            try {
                                const imagenesJSON = JSON.parse(elementoBusqueda.getAttribute('data-todas-imagenes'));
                                if (Array.isArray(imagenesJSON)) {
                                    imagenes = imagenesJSON;
                                }
                            } catch (e) {
                                console.error('Error al parsear data-todas-imagenes:', e);
                            }
                            break;
                        }
                        
                        // Buscar imágenes en este elemento
                        const imgs = elementoBusqueda.querySelectorAll('img');
                        imgs.forEach(img => {
                            const src = img.getAttribute('src');
                            if (src && !imagenes.includes(src)) {
                                imagenes.push(src);
                            }
                        });
                        
                        // Si este elemento tiene imágenes, probablemente sea el contenedor de imágenes
                        if (imgs.length > 0) {
                            break;
                        }
                        
                        elementoBusqueda = elementoBusqueda.nextElementSibling;
                    }
                    
                    console.log(`Prenda: ${nombrePrenda}, Imágenes encontradas: ${imagenes.length}`, imagenes);
                    console.log(`Detalles de prenda: ${nombrePrenda}`, detalles);
                    
                    prendas.push({
                        id: index,
                        nombre: nombrePrenda,
                        descripcion: descripcion,
                        imagenes: imagenes,
                        detalles: detalles
                    });
                }
            });
            
            if (prendas.length === 0) {
                alert('No se encontraron prendas en esta cotización');
                return;
            }
            
            // Llenar el modal
            document.getElementById('modalCostosCliente').textContent = `Cliente: ${cliente}`;
            
            // Crear tabs de prendas
            const tabsContainer = document.getElementById('prendasTabs');
            tabsContainer.innerHTML = '';
            
            prendas.forEach((prenda, index) => {
                const tab = document.createElement('button');
                tab.style.cssText = `
                    padding: 0.75rem 1.5rem;
                    background: ${index === 0 ? '#1e5ba8' : '#f3f4f6'};
                    color: ${index === 0 ? 'white' : '#374151'};
                    border: none;
                    border-radius: 4px 4px 0 0;
                    cursor: pointer;
                    font-weight: 600;
                    transition: all 0.2s;
                    white-space: nowrap;
                `;
                tab.textContent = `📦 ${prenda.nombre}`;
                tab.onclick = () => cambiarPrendaTab(prenda.id, prendas);
                tabsContainer.appendChild(tab);
            });
            
            // Crear contenido de prendas
            const contentContainer = document.getElementById('prendasContent');
            contentContainer.innerHTML = '';
            
            prendas.forEach((prenda, index) => {
                const div = document.createElement('div');
                div.id = `prenda-content-${prenda.id}`;
                div.style.display = index === 0 ? 'block' : 'none';
                div.innerHTML = crearTablaPrecios(prenda.id, prenda.nombre, prenda.descripcion, prenda.imagenes, prenda.detalles || {});
                contentContainer.appendChild(div);
            });
            
            // Cargar costos guardados desde la BD
            cargarCostosGuardados(cotizacionId, prendas);
            
            // Mostrar modal
            document.getElementById('calculoCostosModal').style.display = 'flex';
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar las prendas');
        });
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
    // Ocultar todos los contenidos
    prendas.forEach(p => {
        document.getElementById(`prenda-content-${p.id}`).style.display = 'none';
    });
    
    // Mostrar el contenido seleccionado
    document.getElementById(`prenda-content-${prendaId}`).style.display = 'block';
    
    // Actualizar estilos de tabs
    const tabs = document.querySelectorAll('#prendasTabs button');
    tabs.forEach((tab, index) => {
        if (prendas[index].id === prendaId) {
            tab.style.background = '#1e5ba8';
            tab.style.color = 'white';
        } else {
            tab.style.background = '#f3f4f6';
            tab.style.color = '#374151';
        }
    });
}

/**
 * Crea la tabla de precios para una prenda
 * @param {number} prendaId - ID de la prenda
 * @param {string} nombrePrenda - Nombre de la prenda
 * @param {string} descripcion - Descripción de la prenda
 * @param {array} imagenes - Array de URLs de imágenes
 * @returns {string} HTML de la tabla
 */
function crearTablaPrecios(prendaId, nombrePrenda, descripcion = '', imagenes = [], detalles = {}) {
    // Inicializar costos para esta prenda si no existen
    if (!window.costosPorPrenda[prendaId]) {
        window.costosPorPrenda[prendaId] = {
            nombre: nombrePrenda,
            descripcion: descripcion,
            imagenes: imagenes,
            detalles: detalles,
            items: [{ item: '', precio: '' }]
        };
    }
    
    const costos = window.costosPorPrenda[prendaId];
    
    // Crear HTML de detalles de la prenda (Color, Tela, Manga, Especificaciones)
    let detallesHTML = '';
    if (Object.keys(detalles).length > 0) {
        detallesHTML = `
            <div style="background-color: #f8f9fa; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; border-left: 4px solid #2b7ec9;">
                <div style="font-weight: 700; color: #1e5ba8; margin-bottom: 0.75rem; font-size: 0.9rem;">📋 Detalles de la Prenda:</div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
        `;
        
        Object.entries(detalles).forEach(([label, valor]) => {
            detallesHTML += `
                <div>
                    <div style="font-weight: 600; color: #333; font-size: 0.85rem; margin-bottom: 0.25rem;">${label}:</div>
                    <div style="color: #666; font-size: 0.9rem; word-wrap: break-word;">${valor}</div>
                </div>
            `;
        });
        
        detallesHTML += `
                </div>
            </div>
        `;
    }
    
    // Crear HTML de imágenes
    let imagenesHTML = '';
    if (imagenes && imagenes.length > 0) {
        imagenesHTML = `
            <div style="margin-bottom: 1.5rem;">
                <div style="font-size: 0.85rem; font-weight: 600; color: #333; margin-bottom: 0.75rem;">📸 Imágenes:</div>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 0.75rem;">
        `;
        
        imagenes.forEach((imagen, idx) => {
            imagenesHTML += `
                <div style="position: relative; cursor: pointer; border-radius: 4px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <img src="${imagen}" 
                         alt="Imagen ${idx + 1}" 
                         style="width: 100%; height: 120px; object-fit: cover; transition: all 0.2s;"
                         onmouseover="this.style.opacity='0.8'; this.style.transform='scale(1.05)'"
                         onmouseout="this.style.opacity='1'; this.style.transform='scale(1)'"
                         ondblclick="abrirImagenFullscreenModal('${imagen}')">
                </div>
            `;
        });
        
        imagenesHTML += `
                </div>
            </div>
        `;
    }
    
    // Hacer negrilla los títulos "Bolsillos:" y "Reflectivo:" en la descripción
    let descripcionFormato = descripcion;
    if (descripcionFormato) {
        descripcionFormato = descripcionFormato.replace(/Bolsillos:/g, '<strong>Bolsillos:</strong>');
        descripcionFormato = descripcionFormato.replace(/Reflectivo:/g, '<strong>Reflectivo:</strong>');
    }
    
    let html = `
        <div style="margin-bottom: 2rem;">
            <h3 style="color: #1e5ba8; margin-bottom: 0.5rem; font-size: 1.1rem;">📋 ${nombrePrenda}</h3>
            ${descripcionFormato ? `<p style="color: #666; font-size: 0.9rem; margin-bottom: 1rem; line-height: 1.5; font-style: italic;">${descripcionFormato}</p>` : ''}
            ${detallesHTML}
            ${imagenesHTML}
            
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 1rem;">
                <thead>
                    <tr style="background: linear-gradient(135deg, #1e5ba8 0%, #2b7ec9 100%); color: white;">
                        <th style="padding: 1rem; text-align: left; border: 1px solid #e5e7eb; font-weight: 600;">Items a Evaluar</th>
                        <th style="padding: 1rem; text-align: right; border: 1px solid #e5e7eb; font-weight: 600; width: 150px;">Precio ($)</th>
                        <th style="padding: 1rem; text-align: center; border: 1px solid #e5e7eb; font-weight: 600; width: 50px;">Acción</th>
                    </tr>
                </thead>
                <tbody id="tbody-prenda-${prendaId}">
    `;
    
    // Agregar filas de items
    costos.items.forEach((item, index) => {
        html += `
            <tr style="border-bottom: 1px solid #e5e7eb;">
                <td style="padding: 1rem; border: 1px solid #e5e7eb;">
                    <input type="text" 
                           value="${item.item}" 
                           placeholder="Ej: Corte, Confección, Bordado..."
                           style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.95rem;"
                           onchange="actualizarItem(${prendaId}, ${index}, this.value, 'item')">
                </td>
                <td style="padding: 1rem; border: 1px solid #e5e7eb; text-align: right;">
                    <input type="number" 
                           value="${item.precio}" 
                           placeholder="0.00"
                           step="0.01"
                           min="0"
                           style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.95rem; text-align: right;"
                           onchange="actualizarItem(${prendaId}, ${index}, this.value, 'precio')">
                </td>
                <td style="padding: 1rem; border: 1px solid #e5e7eb; text-align: center;">
                    <button onclick="eliminarFilaItem(${prendaId}, ${index})" 
                            style="background: #ef4444; color: white; border: none; padding: 0.5rem 0.75rem; border-radius: 4px; cursor: pointer; font-weight: 600; transition: all 0.2s; font-size: 0.85rem;"
                            onmouseover="this.style.background='#dc2626'"
                            onmouseout="this.style.background='#ef4444'"
                            title="Eliminar fila">
                        🗑️ Eliminar
                    </button>
                </td>
            </tr>
        `;
    });
    
    // Fila para agregar nuevo item
    html += `
        <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
            <td colspan="3" style="padding: 1rem; border: 1px solid #e5e7eb; text-align: center;">
                <button onclick="agregarFilaItem(${prendaId})" 
                        style="background: #10b981; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 4px; cursor: pointer; font-weight: 600; transition: all 0.2s;"
                        onmouseover="this.style.background='#059669'"
                        onmouseout="this.style.background='#10b981'">
                    + Agregar Item
                </button>
            </td>
        </tr>
    `;
    
    // Fila de total
    const total = costos.items.reduce((sum, item) => sum + (parseFloat(item.precio) || 0), 0);
    html += `
        <tr style="background: linear-gradient(135deg, #1e5ba8 0%, #2b7ec9 100%); color: white; font-weight: 700;">
            <td style="padding: 1rem; border: 1px solid #1e5ba8; text-align: right;">
                TOTAL COSTO:
            </td>
            <td style="padding: 1rem; border: 1px solid #1e5ba8; text-align: right; font-size: 1.1rem;">
                $${total.toFixed(2)}
            </td>
        </tr>
    `;
    
    html += `
            </tbody>
            </table>
        </div>
    `;
    
    return html;
}

/**
 * Actualiza un item de la tabla
 * @param {number} prendaId - ID de la prenda
 * @param {number} itemIndex - Índice del item
 * @param {string} valor - Nuevo valor
 * @param {string} tipo - Tipo de campo ('item' o 'precio')
 */
function actualizarItem(prendaId, itemIndex, valor, tipo) {
    if (!window.costosPorPrenda[prendaId]) {
        window.costosPorPrenda[prendaId] = { items: [] };
    }
    
    if (!window.costosPorPrenda[prendaId].items[itemIndex]) {
        window.costosPorPrenda[prendaId].items[itemIndex] = { item: '', precio: '' };
    }
    
    if (tipo === 'item') {
        window.costosPorPrenda[prendaId].items[itemIndex].item = valor;
    } else if (tipo === 'precio') {
        window.costosPorPrenda[prendaId].items[itemIndex].precio = valor;
    }
    
    // Actualizar el total en tiempo real
    const total = window.costosPorPrenda[prendaId].items.reduce((sum, item) => sum + (parseFloat(item.precio) || 0), 0);
    const totalCell = document.querySelector(`#tbody-prenda-${prendaId}`).parentElement.querySelector('tr:last-child td:last-child');
    if (totalCell) {
        totalCell.textContent = `$${total.toFixed(2)}`;
    }
}

/**
 * Agrega una nueva fila de item a la tabla
 * @param {number} prendaId - ID de la prenda
 */
function agregarFilaItem(prendaId) {
    if (!window.costosPorPrenda[prendaId]) {
        window.costosPorPrenda[prendaId] = { items: [], descripcion: '', imagenes: [] };
    }
    
    window.costosPorPrenda[prendaId].items.push({ item: '', precio: '' });
    
    // Recrear la tabla
    const contentDiv = document.getElementById(`prenda-content-${prendaId}`);
    contentDiv.innerHTML = crearTablaPrecios(prendaId, window.costosPorPrenda[prendaId].nombre, window.costosPorPrenda[prendaId].descripcion, window.costosPorPrenda[prendaId].imagenes);
}

/**
 * Elimina una fila de item de la tabla
 * @param {number} prendaId - ID de la prenda
 * @param {number} itemIndex - Índice del item a eliminar
 */
function eliminarFilaItem(prendaId, itemIndex) {
    if (!window.costosPorPrenda[prendaId]) {
        return;
    }
    
    // Eliminar el item del array
    window.costosPorPrenda[prendaId].items.splice(itemIndex, 1);
    
    // Si no quedan items, agregar uno vacío
    if (window.costosPorPrenda[prendaId].items.length === 0) {
        window.costosPorPrenda[prendaId].items.push({ item: '', precio: '' });
    }
    
    // Recrear la tabla
    const contentDiv = document.getElementById(`prenda-content-${prendaId}`);
    contentDiv.innerHTML = crearTablaPrecios(prendaId, window.costosPorPrenda[prendaId].nombre, window.costosPorPrenda[prendaId].descripcion, window.costosPorPrenda[prendaId].imagenes);
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
            alert(data.message);
            cerrarModalCalculoCostos();
            console.log('Costos guardados en BD:', costosFiltrados);
        } else {
            alert('Error: ' + (data.message || 'No se pudieron guardar los costos'));
        }
    })
    .catch(error => {
        console.error('Error al guardar costos:', error);
        alert('Error al guardar los costos: ' + error.message);
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
