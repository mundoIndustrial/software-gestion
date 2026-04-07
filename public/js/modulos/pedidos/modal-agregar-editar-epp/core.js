// Variables globales
let productoSeleccionadoEPP = null;
let eppAgregadosList = []; // Lista de EPP agregados en el modal
globalThis.eppAgregadosList = eppAgregadosList; // Exponer para DragDropManager
let eppDisponiblesList = []; // Lista de EPP disponibles para mostrar en tabla
let eppYaAgregadosEnFormulario = []; // IDs de EPPs ya en el formulario

/**
 * Obtener los EPP IDs ya agregados en el formulario
 */
function obtenerEPPsYaAgregadosEnFormulario() {
    eppYaAgregadosEnFormulario = [];
    
    // 1) Buscar EPPs agregados en modo tabla (cotizacion) o modo tarjetas (pedido)
    const tablaItems = document.getElementById('tabla-items-pedido');
    const listaItems = document.getElementById('lista-items-pedido');
    console.log('[obtenerEPPsYaAgregadosEnFormulario] Buscando contenedores:', { tablaItems, listaItems });

    const idsDetectados = new Set();

    if (tablaItems) {
        // Modo tabla: filas tr.item-epp con data-item-id
        const filas = tablaItems.querySelectorAll('tr.item-epp');
        console.log('[obtenerEPPsYaAgregadosEnFormulario] Filas (tabla) encontradas:', filas.length);

        filas.forEach((fila, idx) => {
            const eppId = parseInt(fila.getAttribute('data-item-id'), 10);
            console.log(`[obtenerEPPsYaAgregadosEnFormulario] Fila tabla ${idx}: data-item-id=${eppId}`);

            if (Number.isFinite(eppId)) {
                idsDetectados.add(eppId);
            }
        });
    }

    if (listaItems) {
        // Modo tarjetas: .item-epp-card-nuevo con data-epp-id (y compatibilidad data-epp-original-id)
        const tarjetas = listaItems.querySelectorAll('.item-epp-card-nuevo, .item-epp-card');
        console.log('[obtenerEPPsYaAgregadosEnFormulario] Tarjetas encontradas:', tarjetas.length);

        tarjetas.forEach((tarjeta, idx) => {
            const idRaw = tarjeta.getAttribute('data-epp-id') || tarjeta.getAttribute('data-epp-original-id');
            const eppId = parseInt(idRaw, 10);
            console.log(`[obtenerEPPsYaAgregadosEnFormulario] Tarjeta ${idx}: epp-id=${eppId}`);

            if (Number.isFinite(eppId)) {
                idsDetectados.add(eppId);
            }
        });
    }

    eppYaAgregadosEnFormulario = Array.from(idsDetectados);

    if (!tablaItems && !listaItems) {
        console.warn('[obtenerEPPsYaAgregadosEnFormulario] No se encontró tabla-items-pedido ni lista-items-pedido');
    }
    
    // 2) MODO Edicion: tambien incluir EPPs del pedido existente (globalThis.datosEdicionPedido)
    if (globalThis.__EPP_AGREGAR_PEDIDO_EXISTENTE__ && globalThis.datosEdicionPedido && globalThis.datosEdicionPedido.epps) {
        const eppsExistentes = globalThis.datosEdicionPedido.epps;
        console.log('[obtenerEPPsYaAgregadosEnFormulario] MODO edicion - EPPs existentes en pedido:', eppsExistentes.length);
        
        eppsExistentes.forEach(epp => {
            const eppId = parseInt(epp.epp_id || epp.id);
            if (eppId && !eppYaAgregadosEnFormulario.includes(eppId)) {
                eppYaAgregadosEnFormulario.push(eppId);
                console.log(`[obtenerEPPsYaAgregadosEnFormulario] EPP existente en pedido: ${eppId} (${epp.nombre_completo || epp.nombre || ''})`);
            }
        });
    }
    
    console.log('[obtenerEPPsYaAgregadosEnFormulario] EPPs totales encontrados:', eppYaAgregadosEnFormulario);
    return eppYaAgregadosEnFormulario;
}

/**
 * Cargar EPP disponibles en la tabla de seleccion
 */
async function cargarEPPDisponibles() {
    try {
        console.log('[cargarEPPDisponibles] Iniciando carga de EPP...');
        
        // Llamar al endpoint API para obtener los primeros EPPs CON paginacion
        const response = await fetch('/api/epp');
        const result = await response.json();
        
        if (result.success && result.data) {
            eppDisponiblesList = result.data || [];
            console.log('[cargarEPPDisponibles] EPPs cargados:', eppDisponiblesList.length, 'Total en BD:', result.total);
        } else {
            console.warn('[cargarEPPDisponibles] Error en la respuesta del API');
            eppDisponiblesList = [];
        }
        
        // Renderizar dropdown inicial
        renderizarDropdownEPP(eppDisponiblesList);
        
    } catch (error) {
        console.error('[cargarEPPDisponibles] Error:', error);
        eppDisponiblesList = [];
    }
}

/**
 * Mostrar dropdown de EPP disponibles
 */
function mostrarDropdownEPP() {
    const dropdown = document.getElementById('dropdownEPP');
    dropdown.classList.remove('hidden');
    console.log('[mostrarDropdownEPP] Dropdown mostrado');
}

/**
 * Renderizar opciones en el dropdown
 */
function renderizarDropdownEPP(epps) {
    const container = document.getElementById('opcionesDropdownEPP');
    const mensajeSinResultados = document.getElementById('mensajeSinResultados');
    
    if (!container) return;
    
    console.log('[renderizarDropdownEPP] Renderizando con:', {
        eppsCounts: epps ? epps.length : 0,
        eppAgregadosModal: eppAgregadosList.length,
        eppEnFormulario: eppYaAgregadosEnFormulario.length
    });
    
    container.innerHTML = '';
    
    // Filtrar EPPs que ya estan agregados (EN EL MODAL O EN EL FORMULARIO)
    const eppsFiltrados = (epps || []).filter(epp => {
        const enModal = eppAgregadosList.find(item => item.id == epp.id);
        const enFormulario = eppYaAgregadosEnFormulario.includes(epp.id);
        
        if (enModal) {
            console.log(`  - EPP ${epp.id} (${epp.nombre_completo}) EXCLUIDO: en modal`);
        }
        if (enFormulario) {
            console.log(`  - EPP ${epp.id} (${epp.nombre_completo}) EXCLUIDO: en formulario`);
        }
        
        return !enModal && !enFormulario;
    });
    
    console.log('[renderizarDropdownEPP] EPPs despues de filtrado:', eppsFiltrados.length);
    
    if (!eppsFiltrados || eppsFiltrados.length === 0) {
        mensajeSinResultados.classList.remove('hidden');
        return;
    }
    
    mensajeSinResultados.classList.add('hidden');
    
    eppsFiltrados.forEach(epp => {
        const label = document.createElement('label');
        label.className = 'flex items-center p-2 hover:bg-blue-50 rounded cursor-pointer gap-2';
        
        label.innerHTML = `
            <input
                type="checkbox"
                class="checkbox-epp-dropdown w-4 h-4"
                data-epp-id="${epp.id}"
                data-epp-nombre="${epp.nombre_completo || epp.nombre}"
                onchange="agregarEPPDesdeDropdown(this)"
            >
            <div class="flex-1">
                <p class="font-medium text-gray-900 text-sm">${epp.nombre_completo || epp.nombre}</p>
                ${epp.marca ? `<p class="text-xs text-gray-500">${epp.marca}</p>` : ''}
            </div>
        `;
        
        container.appendChild(label);
    });
    
    console.log('[renderizarDropdownEPP] Dropdown renderizado con', epps.length, 'opciones');
}

/**
 * Filtrar dropdown segun busqueda (consulta al servidor sin limite)
 */
async function filtrarDropdownEPP(valor) {
    const dropdown = document.getElementById('dropdownEPP');
    const busqueda = valor.toLowerCase().trim();
    
    // Si no hay busqueda, ocultar dropdown
    if (!busqueda) {
        dropdown.classList.add('hidden');
        console.log('[filtrarDropdownEPP] Dropdown ocultado (sin busqueda)');
        return;
    }
    
    try {
        console.log('[filtrarDropdownEPP] Buscando:', busqueda, 'con EPPs en formulario:', eppYaAgregadosEnFormulario);
        
        // Buscar en el servidor SIN limite de paginacion
        const response = await fetch(`/api/epp?q=${encodeURIComponent(busqueda)}&per_page=10000`);
        const result = await response.json();
        
        let filtrados = [];
        if (result.success && result.data) {
            console.log('[filtrarDropdownEPP] Resultados del servidor:', result.data.length);
            
            // Filtrar EPPs que ya estan agregados (EN EL MODAL O EN EL FORMULARIO)
            filtrados = (result.data || []).filter(epp => {
                const enModal = eppAgregadosList.find(item => item.id == epp.id);
                const enFormulario = eppYaAgregadosEnFormulario.includes(epp.id);
                
                if (enModal) {
                    console.log(`  - EPP ${epp.id} EXCLUIDO (busqueda): en modal`);
                }
                if (enFormulario) {
                    console.log(`  - EPP ${epp.id} EXCLUIDO (busqueda): en formulario (${epp.nombre_completo})`);
                }
                
                return !enModal && !enFormulario;
            });
        }
        
        console.log('[filtrarDropdownEPP] busqueda:', busqueda, '- Resultados finales:', filtrados.length, 'de', (result.data || []).length);
        
        // Actualizar la lista local con los resultados de busqueda
        // Renderizar y mostrar
        renderizarDropdownEPP(filtrados);
        mostrarDropdownEPP();
        
    } catch (error) {
        console.error('[filtrarDropdownEPP] Error en busqueda:', error);
    }
}

/**
 * Agregar EPP desde el dropdown
 */
function agregarEPPDesdeDropdown(checkbox) {
    const eppId = parseInt(checkbox.getAttribute('data-epp-id'));
    const nombre = checkbox.getAttribute('data-epp-nombre');
    
    if (checkbox.checked) {
        // Agregar a la lista si no existe
        if (!eppAgregadosList.find(e => e.id == eppId)) {
            const epp = eppDisponiblesList.find(e => e.id == eppId);
            eppAgregadosList.push({
                id: eppId,
                epp_id: eppId,
                nombre: nombre,
                nombre_epp: nombre,
                nombre_completo: nombre,
                cantidad: 1,
                observaciones: '-',
                valor_unitario: 0,
                total: 0,
                imagenes: [],
                imagen: epp?.imagen || ''
            });
            console.log('[agregarEPPDesdeDropdown] EPP agregado:', eppId);
        }
    } else {
        // Quitar de la lista
        eppAgregadosList = eppAgregadosList.filter(e => e.id != eppId);
        console.log('[agregarEPPDesdeDropdown] EPP removido:', eppId);
    }
    
    actualizarSeleccionEPP();
    renderizarTablaEPPAgregados();
}



/**
 * Actualizar contador y estado de seleccion
 */
function actualizarSeleccionEPP() {
    const total = eppAgregadosList.length;
    document.getElementById('totalSeleccionados').textContent = total;
    document.getElementById('btnFinalizarAgregarEPP').disabled = total === 0;
    
    // Mostrar/ocultar tabla de agregados
    const tabla = document.getElementById('listaEPPAgregados');
    if (total > 0) {
        tabla.style.display = 'block';
        document.getElementById('contadorEPP').textContent = total;
    } else {
        tabla.style.display = 'none';
    }
    
    console.log('[actualizarSeleccionEPP] Total EPPs:', total);
}

/**
 * Cerrar dropdown cuando se hace clic fuera
 */
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('dropdownEPP');
    const input = document.getElementById('inputBuscadorEPPTabla');
    
    if (!dropdown || !input) return;
    
    if (e.target !== input && !dropdown.contains(e.target)) {
        dropdown.classList.add('hidden');
    }
});

/**
 * Manejo de Ctrl+V (paste) para agregar fotos en la tabla de EPP agregados
 * y en el modo edicion (contenedorFotosEPP)
 *
 * NOTA: El listener se registra dinamicamente en handlePasteEPP cuando se abre el modal
 * para evitar conflictos con otros listeners globales como DragDropManager
 */

/**
 * Soporte para click derecho en miniaturas de fotos para eliminar
 */
document.addEventListener('contextmenu', function(e) {
    const miniatura = e.target.closest('.foto-miniatura');
    if (miniatura) {
        e.preventDefault();
        const eppId = Number(miniatura.dataset.eppId);
        const fotoIndex = Number(miniatura.dataset.fotoIndex);
        
        if (confirm('¿Eliminar esta imagen?')) {
            eliminarFotoEPP(eppId, fotoIndex);
        }
    }
});

/**
 * Soporte para tecla Delete cuando esta enfocada una zona de fotos
 */
document.addEventListener('keydown', function(e) {
    // Si se presiona Delete y hay una zona de fotos enfocada
    if (e.key === 'Delete') {
        const fotoZona = document.activeElement?.closest('[id^="fotoZona_"]');
        if (fotoZona) {
            e.preventDefault();
            const eppId = Number(fotoZona.id.replace('fotoZona_', ''));
            const epp = eppAgregadosList.find(e => e.id == eppId);
            
            // Eliminar la ultima foto agregada
            if (epp && epp.imagenes && epp.imagenes.length > 0) {
                const ultimaIndex = epp.imagenes.length - 1;
                if (confirm('¿Eliminar la ultima imagen agregada?')) {
                    eliminarFotoEPP(eppId, ultimaIndex);
                }
            }
        }
    }
});

/**
 * Renderizar tabla de EPP agregados
 */
function renderizarTablaEPPAgregados() {
    const tbody = document.getElementById('cuerpoTablaEPP');
    if (!tbody) return;
    
    const esCotizacion = !!globalThis.__EPP_COTIZACION_MODE__;
    
    // Mostrar/ocultar columnas de cotizacion en el header
    document.querySelectorAll('.columna-cotizacion').forEach(col => {
        col.style.display = esCotizacion ? '' : 'none';
    });
    
    // Ajustar ancho del modal segun modo
    const modalContent = document.getElementById('modalAgregarEPPContent');
    if (modalContent) {
        if (esCotizacion) {
            modalContent.classList.remove('max-w-3xl');
            modalContent.classList.add('max-w-5xl');
        } else {
            modalContent.classList.remove('max-w-5xl');
            modalContent.classList.add('max-w-3xl');
        }
    }
    
    tbody.innerHTML = '';
    
    eppAgregadosList.forEach((epp, idx) => {
        const row = document.createElement('tr');
        row.className = idx % 2 === 0 ? 'bg-white' : 'bg-gray-50';
        
        // Inicializar valores si no existen
        if (epp.valor_unitario === undefined) epp.valor_unitario = 0;
        if (epp.total === undefined) epp.total = 0;
        
        // Miniaturas de fotos
        let fotosHtml = '';
        if (epp.imagenes && epp.imagenes.length > 0) {
            const fotosMostrar = epp.imagenes.slice(0, 3);
            fotosHtml = fotosMostrar.map((foto, i) =>
                `<div class="relative group foto-miniatura cursor-pointer"
                    data-epp-id="${epp.id}"
                    data-foto-index="${i}"
                    title="Click para eliminar, Ctrl+Click para expandir">
                    <img src="${foto.previewUrl || foto.base64}" alt="Foto ${i+1}" class="w-8 h-8 object-cover rounded border border-gray-200">
                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 rounded transition flex items-center justify-center">
                        <button type="button"
                            onclick="event.stopPropagation(); eliminarFotoEPP(${epp.id}, ${i})"
                            class="opacity-0 group-hover:opacity-100 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center transition transform hover:scale-110 hover:bg-red-600"
                            title="Eliminar imagen">
                            <i class="material-symbols-rounded" style="font-size: 14px;">delete</i>
                        </button>
                    </div>
                </div>`
            ).join('');
            if (epp.imagenes.length > 3) {
                fotosHtml += `<span class="text-xs text-gray-500 font-medium bg-gray-200 rounded px-2 py-1 cursor-pointer hover:bg-gray-300 transition" title="Haz clic para ver el resto">+${epp.imagenes.length - 3}</span>`;
            }
        }
        
        // Columnas de cotizacion (valor unitario y total)
        const columnasCotizacion = esCotizacion ? `
            <td class="px-4 py-2 text-center columna-cotizacion">
                <input
                    type="number"
                    value="${epp.valor_unitario || ''}"
                    min="0"
                    step="0.01"
                    onchange="actualizarValorUnitarioEPP(${epp.id}, this.value)"
                    class="w-24 px-2 py-1 border border-gray-300 rounded text-center text-sm"
                    placeholder="0"
                >
            </td>
            <td class="px-4 py-2 text-center columna-cotizacion">
                <span id="totalEPPItem_${epp.id}" class="font-semibold text-gray-900 text-sm">
                    ${((epp.cantidad || 1) * (epp.valor_unitario || 0)).toLocaleString('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 2})}
                </span>
            </td>
        ` : '';
        
        row.innerHTML = `
            <td class="px-4 py-2 text-gray-900 font-medium">${epp.nombre_completo}</td>
            <td class="px-4 py-2 text-center">
                <input
                    type="number"
                    value="${epp.cantidad}"
                    min="1"
                    onchange="actualizarCantidadEPP(${epp.id}, this.value)"
                    class="w-14 px-2 py-1 border border-gray-300 rounded text-center text-sm"
                >
            </td>
            <td class="px-4 py-2 text-gray-700 text-xs">
                <input
                    type="text"
                    value="${epp.observaciones}"
                    onchange="actualizarObservacionesEPP(${epp.id}, this.value)"
                    class="w-full px-2 py-1 border border-gray-300 rounded text-xs"
                    placeholder="Observaciones..."
                >
            </td>
            ${columnasCotizacion}
            <td class="px-4 py-2 text-center">
                <div class="flex gap-1 items-center justify-center flex-wrap border-2 border-dashed border-gray-300 rounded p-2 min-h-10 cursor-pointer bg-gray-50 hover:bg-gray-100 transition"
                    id="fotoZona_${epp.id}"
                    tabindex="0"
                    ondrop="manejarDropFotosEPP(event, ${epp.id})"
                    ondragover="manejarDragOverFotosEPP(event)"
                    ondragleave="manejarDragLeaveFotosEPP(event)">
                    ${fotosHtml ? `<div class="flex gap-1 items-center">${fotosHtml}</div>` : '<span class="text-gray-400 text-xs">Arrastra imágenes</span>'}
                </div>
                <input type="file" id="inputFotos_${epp.id}" multiple accept="image/*" style="display: none;" onchange="manejarSeleccionFotosEPP(event, ${epp.id})">
            </td>
            <td class="px-4 py-2 text-center">
                <button
                    type="button"
                    onclick="event.stopPropagation(); eliminarEPPDeLista(${epp.id})"
                    class="text-red-600 hover:text-red-800 font-medium transition"
                >
                    <i class="material-symbols-rounded" style="font-size: 18px;">delete</i>
                </button>
            </td>
        `;
        
        tbody.appendChild(row);
    });
    
    console.log('[renderizarTablaEPPAgregados] Tabla actualizada con', eppAgregadosList.length, 'EPPs', esCotizacion ? '(modo cotizacion)' : '');
}

/**
 * Agregar fotos a un EPP desde seleccion de archivos
 */
function manejarSeleccionFotosEPP(event, eppId) {
    const files = event.target.files;
    const epp = eppAgregadosList.find(e => e.id == eppId);
    if (!epp) return;
    
    if (!epp.imagenes) epp.imagenes = [];
    
    Array.from(files).forEach(file => {
        // Usar blob URL en lugar de base64
        const blobUrl = URL.createObjectURL(file);
        epp.imagenes.push({
            file: file,
            previewUrl: blobUrl,
            nombre: file.name
        });
        renderizarTablaEPPAgregados();
    });
    
    console.log('[manejarSeleccionFotosEPP] Fotos agregadas para EPP', eppId);
}

/**
 * Manejo de drag & drop para fotos
 */
function manejarDragOverFotosEPP(event) {
    event.preventDefault();
    event.stopPropagation();
    event.currentTarget.classList.add('bg-blue-100', 'border-blue-400');
}

function manejarDragLeaveFotosEPP(event) {
    event.preventDefault();
    event.stopPropagation();
    event.currentTarget.classList.remove('bg-blue-100', 'border-blue-400');
}

function manejarDropFotosEPP(event, eppId) {
    event.preventDefault();
    event.stopPropagation();
    event.currentTarget.classList.remove('bg-blue-100', 'border-blue-400');
    
    const files = event.dataTransfer.files;
    const epp = eppAgregadosList.find(e => e.id == eppId);
    if (!epp) return;
    
    if (!epp.imagenes) epp.imagenes = [];
    
    Array.from(files).forEach(file => {
        if (file.type.startsWith('image/')) {
            // Usar blob URL en lugar de base64
            const blobUrl = URL.createObjectURL(file);
            epp.imagenes.push({
                file: file,
                previewUrl: blobUrl,
                nombre: file.name
            });
            renderizarTablaEPPAgregados();
        }
    });
    
    console.log('[manejarDropFotosEPP] Fotos agregadas por drag & drop para EPP', eppId);
}

/**
 * Eliminar una foto de un EPP
 * Opciones de eliminación:
 * 1. Click en el botón X
 * 2. Click derecho en la miniatura
 * 3. Presionar Delete cuando esta seleccionada
 */
function eliminarFotoEPP(eppId, indexFoto, event) {
    // Prevenir propagación si viene de un evento
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    const epp = eppAgregadosList.find(e => e.id == eppId);
    if (epp && epp.imagenes) {
        const fotoEliminada = epp.imagenes[indexFoto];
        
        // Liberar la blob URL para ahorrar memoria
        if (fotoEliminada && fotoEliminada.previewUrl) {
            URL.revokeObjectURL(fotoEliminada.previewUrl);
        }
        
        epp.imagenes.splice(indexFoto, 1);
        renderizarTablaEPPAgregados();
        console.log('[eliminarFotoEPP] Foto eliminada de EPP', eppId, '- Nombre:', fotoEliminada?.nombre);
    }
}

/**
 * Manejar eventos de las miniaturas (click derecho, etc.)
 */
function manejarEventoMiniatura(event) {
    if (event.button === 2) { // Click derecho
        event.preventDefault();
        const miniatura = event.target.closest('.foto-miniatura');
        if (miniatura) {
            const eppId = Number(miniatura.dataset.eppId);
            const fotoIndex = Number(miniatura.dataset.fotoIndex);
            
            // Mostrar confirmacion simple
            if (confirm('¿Eliminar esta imagen?')) {
                eliminarFotoEPP(eppId, fotoIndex);
            }
        }
    }
}

/**
 * Actualizar cantidad de EPP agregado
 */
function actualizarCantidadEPP(eppId, cantidad) {
    const index = eppAgregadosList.findIndex(e => e.id == eppId);
    if (index !== -1) {
        eppAgregadosList[index].cantidad = parseInt(cantidad) || 1;
        // Recalcular total si estamos en modo cotizacion
        if (globalThis.__EPP_COTIZACION_MODE__) {
            const vu = parseFloat(eppAgregadosList[index].valor_unitario) || 0;
            eppAgregadosList[index].total = eppAgregadosList[index].cantidad * vu;
            const spanTotal = document.getElementById(`totalEPPItem_${eppId}`);
            if (spanTotal) {
                spanTotal.textContent = eppAgregadosList[index].total.toLocaleString('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 2});
            }
        }
        console.log('[actualizarCantidadEPP] Cantidad actualizada para EPP', eppId);
    }
}

/**
 * Actualizar valor unitario de EPP agregado (solo en modo cotizacion)
 */
function actualizarValorUnitarioEPP(eppId, valor) {
    const index = eppAgregadosList.findIndex(e => e.id == eppId);
    if (index !== -1) {
        eppAgregadosList[index].valor_unitario = parseFloat(valor) || 0;
        const cant = parseInt(eppAgregadosList[index].cantidad) || 1;
        eppAgregadosList[index].total = cant * eppAgregadosList[index].valor_unitario;
        
        const spanTotal = document.getElementById(`totalEPPItem_${eppId}`);
        if (spanTotal) {
            spanTotal.textContent = eppAgregadosList[index].total.toLocaleString('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 2});
        }
        console.log('[actualizarValorUnitarioEPP] V.Unitario actualizado para EPP', eppId, '=> Total:', eppAgregadosList[index].total);
    }
}

/**
 * Actualizar observaciones de EPP agregado
 */
function actualizarObservacionesEPP(eppId, observaciones) {
    const index = eppAgregadosList.findIndex(e => e.id == eppId);
    if (index !== -1) {
        eppAgregadosList[index].observaciones = observaciones || '-';
        console.log('[actualizarObservacionesEPP] Observaciones actualizadas para EPP', eppId);
    }
}

/**
 * Eliminar EPP de la lista agregada
 */
function eliminarEPPDeLista(eppId) {
    const index = eppAgregadosList.findIndex(e => e.id == eppId);
    if (index !== -1) {
        eppAgregadosList.splice(index, 1);
        
        // Desmarcar checkbox
        const checkbox = document.querySelector(`input[data-epp-id="${eppId}"]`);
        if (checkbox) checkbox.checked = false;
        
        // Actualizar UI
        actualizarSeleccionEPP();
        renderizarTablaEPPAgregados();
        
        console.log('[eliminarEPPDeLista] EPP eliminado:', eppId);
    }
}

/**
 * Abrir modal y cargar EPP disponibles
 */
function abrirModalAgregarEPP() {
    console.log('[abrirModalAgregarEPP] Abriendo modal con tabla de seleccion multiples');
    const modal = document.getElementById('modalAgregarEPP');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // IMPORTANTE: Obtener los EPPs ya agregados en el formulario
    console.log('[abrirModalAgregarEPP] Llamando a obtenerEPPsYaAgregadosEnFormulario...');
    const eppEnFormulario = obtenerEPPsYaAgregadosEnFormulario();
    console.log('[abrirModalAgregarEPP] Variable global actualizada:', eppYaAgregadosEnFormulario);
    
    // IMPORTANTE: Solo limpiar si la lista esta vacía (primera vez)
    // Si ya hay EPPs en la lista (regresos al modal), preservarlos
    if (eppAgregadosList.length === 0) {
        console.log('[abrirModalAgregarEPP] PRIMERA VEZ - limpiando estado previo');
        eppDisponiblesList = []; // Vaciar lista de disponibles solo si es la primera vez
        document.getElementById('inputBuscadorEPPTabla').value = '';
    } else {
        console.log('[abrirModalAgregarEPP] REGRESO AL MODAL - preservando', eppAgregadosList.length, 'EPPs agregados');
    }
    
    document.getElementById('contadorEPP').textContent = eppAgregadosList.length;
    document.getElementById('listaEPPAgregados').style.display = eppAgregadosList.length > 0 ? 'block' : 'none';
    document.getElementById('btnFinalizarAgregarEPP').disabled = eppAgregadosList.length === 0;
    
    // Limpiar dropdown - no cargar EPPs disponibles inicialmente
    const opcionesContainer = document.getElementById('opcionesDropdownEPP');
    if (opcionesContainer) {
        opcionesContainer.innerHTML = '';
    }
    
    // Ocultar el dropdown
    const dropdown = document.getElementById('dropdownEPP');
    if (dropdown) {
        dropdown.classList.add('hidden');
    }
    
    // Mostrar mensaje "sin resultados"
    const mensajeSinResultados = document.getElementById('mensajeSinResultados');
    if (mensajeSinResultados) {
        mensajeSinResultados.classList.remove('hidden');
    }
    
    // Re-renderizar tabla si hay EPPs
    if (eppAgregadosList.length > 0) {
        renderizarTablaEPPAgregados();
        console.log('[abrirModalAgregarEPP] Tabla Re-renderizada con', eppAgregadosList.length, 'EPPs');
    }
    
    // IMPORTANTISIMO: Ocultar seccion de edicion para evitar que Ctrl+V vaya alla
    const seccionFotos = document.getElementById('seccionFotosEPP');
    if (seccionFotos) {
        seccionFotos.style.display = 'none';
        console.log('[abrirModalAgregarEPP] seccion de edicion ocultada');
    }
    
    // ===== REGISTRAR MODAL EPP COMO SUB-MODAL EN DRAGDROPMANAGER =====
    // Esto da prioridad automatica al modal EPP cuando se pega
    // Patron consistente con modal-agregar-prenda-nueva y ColoresPorTalla.js
    if (globalThis.DragDropManager && typeof globalThis.DragDropManager.registrarSubModal === 'function') {
        globalThis.DragDropManager.registrarSubModal('modalAgregarEPP', function(file) {
            console.log('[Paste Handler EPP] Imagen pegada via DragDropManager:', file.name);
            
            // Caso A: Hay zona de fotos activa en tabla (prioritario)
            if (globalThis.zonaFotosActivaId && eppAgregadosList.length > 0) {
                const eppId = parseInt(globalThis.zonaFotosActivaId.replace('fotoZona_', ''));
                const epp = eppAgregadosList.find(e => e.id == eppId);
                if (epp) {
                    if (!epp.imagenes) epp.imagenes = [];
                    const blobUrl = URL.createObjectURL(file);
                    epp.imagenes.push({
                        file: file,
                        previewUrl: blobUrl,
                        nombre: file.name
                    });
                    renderizarTablaEPPAgregados();
                    console.log('[Paste Handler EPP]  Imagen pegada en zona activa:', eppId);
                    return;
                }
            }
            
            // Caso B: Hay EPPs en tabla
            if (eppAgregadosList.length > 0) {
                const eppId = eppAgregadosList[eppAgregadosList.length - 1].id;
                const epp = eppAgregadosList.find(e => e.id == eppId);
                if (epp) {
                    if (!epp.imagenes) epp.imagenes = [];
                    const blobUrl = URL.createObjectURL(file);
                    epp.imagenes.push({
                        file: file,
                        previewUrl: blobUrl,
                        nombre: file.name
                    });
                    renderizarTablaEPPAgregados();
                    console.log('[Paste Handler EPP]  Imagen pegada en ultimo EPP:', eppId);
                    return;
                }
            }
            
            // Caso C: No hay EPPs, guardar temporalmente
            console.log('[Paste Handler EPP]  No hay EPPs en tabla, guardando en buffer temporal');
            if (!globalThis.fotosEPP) globalThis.fotosEPP = [];
            globalThis.fotosEPP.push({
                file: file,
                previewUrl: URL.createObjectURL(file),
                nombre: file.name
            });
            
            // Mostrar seccion de edicion para que el usuario pueda crear un EPP
            const seccionFotos = document.getElementById('seccionFotosEPP');
            if (seccionFotos) {
                seccionFotos.style.display = 'block';
                console.log('[Paste Handler EPP] seccion de edicion mostrada');
            }
            
            // Actualizar contador
            const contadorFotos = document.getElementById('contadorFotosEPP');
            if (contadorFotos) {
                contadorFotos.textContent = globalThis.fotosEPP.length;
            }
        });
        console.log('[abrirModalAgregarEPP]  Modal EPP registrado en DragDropManager');
    } else if (globalThis.handlePasteEPP && typeof globalThis.handlePasteEPP === 'function') {
        // Fallback SOLO si DragDropManager no esta y handlePasteEPP esta disponible
        // Asegurarse que no este registrado ya
        document.removeEventListener('paste', globalThis.handlePasteEPP, true);
        document.addEventListener('paste', globalThis.handlePasteEPP, true);
        console.log('[abrirModalAgregarEPP]  Fallback: usando listener manual (DragDropManager no disponible)');
    }
    
    console.log('[abrirModalAgregarEPP] Modal abierto - EPPs agregados:', eppAgregadosList.length);
    console.log('[abrirModalAgregarEPP] EPPs a EXCLUIR:', eppYaAgregadosEnFormulario);
}

function actualizarTotalEPP() {
    const cantidadInput = document.getElementById('cantidadEPP');
    const valorUnitarioInput = document.getElementById('valorUnitarioEPP');
    const totalInput = document.getElementById('totalEPP');
    if (!cantidadInput || !valorUnitarioInput || !totalInput) return;

    const formatearNumero = (num) => {
        if (!Number.isFinite(num)) return '0';
        if (Number.isInteger(num)) return String(num);
        const s = num.toFixed(2);
        return s.replace(/\.00$/, '').replace(/(\.[0-9])0$/, '$1');
    };

    const cantidad = parseInt(cantidadInput.value) || 0;
    const vuRaw = String(valorUnitarioInput.value || '').trim();
    const vu = vuRaw !== '' && !isNaN(Number(vuRaw)) ? Number(vuRaw) : null;

    const total = (vu !== null && cantidad > 0) ? (vu * cantidad) : 0;
    totalInput.value = formatearNumero(total);
}

/**
 * Valida si hay datos sin guardar en el modal
 */
function hayDatosNoGuardados() {
    // Si hay EPPs en la lista, hay datos
    if (eppAgregadosList.length > 0) {
        return true;
    }
    
    // Validar si hay producto seleccionado
    if (productoSeleccionadoEPP) {
        return true;
    }
    
    // Validar si hay cantidad diferente de 1 (valor inicial)
    const cantidadInput = document.getElementById('cantidadEPP');
    if (cantidadInput && cantidadInput.value && parseInt(cantidadInput.value) !== 1) {
        return true;
    }
    
    // Validar si hay observaciones
    const obsInput = document.getElementById('observacionesEPP');
    if (obsInput && obsInput.value && obsInput.value.trim() !== '') {
        return true;
    }
    
    // Validar si hay fotos
    if (globalThis.fotosEPP && globalThis.fotosEPP.length > 0) {
        return true;
    }
    
    // Validar si hay valor unitario (modo cotizacion)
    const vuInput = document.getElementById('valorUnitarioEPP');
    if (vuInput && vuInput.value && vuInput.value.trim() !== '' && vuInput.value !== '0') {
        return true;
    }
    
    return false;
}

function cerrarModalAgregarEPP() {
    console.log(' [cerrarModalAgregarEPP] Cerrando modal');
    
    // Validar si hay datos sin guardar (excepto en modo edicion)
    const enModoEdicion = !!globalThis.eppEnEdicion;
    if (!enModoEdicion && hayDatosNoGuardados()) {
        console.log(' [cerrarModalAgregarEPP] Hay datos sin guardar - pidiendo confirmacion');
        Swal.fire({
            icon: 'warning',
            title: '¿Descartar cambios?',
            text: 'Tienes datos sin guardar que se perderán. ¿Estás seguro de que deseas cerrar?',
            showCancelButton: true,
            confirmButtonText: 'Sí, descartar',
            cancelButtonText: 'No, continuar',
            confirmButtonColor: '#dd3333',
            cancelButtonColor: '#3085d6'
        }).then((result) => {
            if (result.isConfirmed) {
                console.log(' [cerrarModalAgregarEPP] Usuario confirmo descartar cambios');
                cerrarModalAgregarEPPConfirmado();
            }
        });
        return; // No continuar si no esta confirmado
    }
    
    // Si no hay datos o esta en modo edicion, cerrar directo
    cerrarModalAgregarEPPConfirmado();
}

/**
 * Funcion auxiliar que realmente cierra el modal
 */
function cerrarModalAgregarEPPConfirmado() {
    console.log(' [cerrarModalAgregarEPPConfirmado] Cerrando modal confirmado');
    const modal = document.getElementById('modalAgregarEPP');
    
    // Resetear titulo del modal a estado por defecto
    const tituloModal = modal.querySelector('h2.text-white');
    if (tituloModal) {
        tituloModal.textContent = 'Agregar EPP al Pedido';
        console.log(' [cerrarModalAgregarEPPConfirmado] titulo reseteado a: Agregar EPP al Pedido');
    }
    
    modal.style.display = 'none';
    
    // ===== DESREGISTRAR DEL DRAGDROPMANAGER =====
    // Patron consistente con otros modales
    if (globalThis.DragDropManager && typeof globalThis.DragDropManager.desregistrarSubModal === 'function') {
        globalThis.DragDropManager.desregistrarSubModal('modalAgregarEPP');
        console.log('[cerrarModalAgregarEPP] Modal EPP desregistrado de DragDropManager');
    } else if (globalThis.handlePasteEPP && globalThis.DragDropManager === undefined) {
        // Fallback SOLO si DragDropManager no esta disponible
        document.removeEventListener('paste', globalThis.handlePasteEPP, true);
        console.log('[cerrarModalAgregarEPP] Listener manual removido (fallback)');
    }
    
    document.body.style.overflow = 'auto';
    eppAgregadosList = []; // Limpiar lista al cerrar
    globalThis.eppAgregadosList = eppAgregadosList;
    
    // Limpiar zona de fotos activa
    globalThis.zonaFotosActivaId = null;
    console.log('[cerrarModalAgregarEPP] Zona de fotos activa reseteada');

    // Limpiar flag de agregar EPP a pedido existente
    globalThis.__EPP_AGREGAR_PEDIDO_EXISTENTE__ = null;

    // Siempre limpiar estado de edicion y formulario al cerrar/cancelar
    eppEnEdicion = null;
    globalThis.eppEnEdicion = null;
    console.log(' [cerrarModalAgregarEPPConfirmado] globalThis.eppEnEdicion limpiado');
    
    // Limpiar imagenes temporales al cerrar
    limpiarImagenesTemporales();
    
    resetearModalAgregarEPP();
}

/**
 * Funcion para limpiar imagenes temporales del storage
 */
function limpiarImagenesTemporales() {
    // NO revocar URLs blob - se mantienen en cache global globalThis._eppFilesCache
    // Las URLs se revocaron cuando se elimine el item de la tabla
    // Simplemente limpiar el array temporal de fotosEPP
    globalThis.fotosEPP = [];
    
    console.log('[limpiarImagenesTemporales] Array de fotos temporales limpiado (URLs en cache global)');
}

function resetearModalAgregarEPP() {
    console.log(' [resetearModalAgregarEPP] INICIANDO reset COMPLETO del modal');
    
    // Limpiar imagenes temporales
    limpiarImagenesTemporales();
    
    // Limpiar producto seleccionado
    productoSeleccionadoEPP = null;
    console.log(' [resetearModalAgregarEPP] Producto seleccionado limpiado');
    
    // ELEMENTOS PRINCIPALES - Ocultarlos todos
    const elementosOcultar = [
        'productoCardEPP',
        'formularioAgregarEPP',
        'observacionesContainer',

        'resultadosBuscadorEPP',
        'valorUnitarioTotalContainer'
    ];
    
    elementosOcultar.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.style.setProperty('display', 'none', 'important');
            console.log(` [resetearModalAgregarEPP] ${id} ocultado`);
        }
    });
    
    // CAMPOS DE FORMULARIO - Resetear valores y deshabilitar
    const cantidadEPP = document.getElementById('cantidadEPP');
    const observacionesEPP = document.getElementById('observacionesEPP');
    const nombreProductoEPP = document.getElementById('nombreProductoEPP');
    const inputBuscadorEPP = document.getElementById('inputBuscadorEPP');
    
    if (cantidadEPP) {
        cantidadEPP.value = '1';
        cantidadEPP.disabled = true;
    }
    if (observacionesEPP) {
        observacionesEPP.value = '';
        observacionesEPP.disabled = true;
    }
    if (nombreProductoEPP) {
        nombreProductoEPP.value = '';
    }
    if (inputBuscadorEPP) {
        inputBuscadorEPP.value = '';
    }
    
    // VALOR UNITARIO Y TOTAL
    const valorUnitarioEPP = document.getElementById('valorUnitarioEPP');
    const totalEPP = document.getElementById('totalEPP');
    
    if (valorUnitarioEPP) {
        valorUnitarioEPP.value = '';
        valorUnitarioEPP.disabled = true;
    }
    if (totalEPP) {
        totalEPP.value = '0';
    }
    
    // LIMPIAR CONTAINER DE FOTOS
    const contenedorFotosEPP = document.getElementById('contenedorFotosEPP');
    const mensajeDragDrop = document.getElementById('mensajeDragDrop');
    
    if (contenedorFotosEPP) {
        // Eliminar elementos de fotos pero mantener mensaje
        const fotosItems = contenedorFotosEPP.querySelectorAll('.foto-epp-item');
        fotosItems.forEach(item => item.remove());
        console.log(' [resetearModalAgregarEPP] Fotos del contenedor limpiadas');
    }
    
    if (mensajeDragDrop) {
        mensajeDragDrop.style.display = 'flex';
        console.log(' [resetearModalAgregarEPP] Mensaje inicial de drag-drop restaurado');
    }
    
    // BOTONES DEL FOOTER (con null checks para evitar crash en contexto de edicion)
    const btnAgregar = document.getElementById('btnAgregarALista');
    const btnFinalizar = document.getElementById('btnFinalizarAgregarEPP');
    const btnGuardar = document.getElementById('btnGuardarCambiosEPP');
    if (btnAgregar) btnAgregar.disabled = true;
    if (btnFinalizar) { btnFinalizar.disabled = true; btnFinalizar.style.setProperty('display', 'flex', 'important'); }
    if (btnGuardar) { btnGuardar.disabled = true; btnGuardar.style.setProperty('display', 'none', 'important'); }
    
    console.log(' [resetearModalAgregarEPP] Botones reseteados');
    
    // Restaurar seccion de "EPP Agregados" al resetear (SOLO si no estamos editando)
    const enEdicion = !!eppEnEdicion || !!globalThis.eppEnEdicion;
    const listaEPPAgregados = document.getElementById('listaEPPAgregados');
    
    if (listaEPPAgregados) {
        if (enEdicion) {
            // Si estamos editando, mantener la tabla oculta
            listaEPPAgregados.removeAttribute('style');
            listaEPPAgregados.style.setProperty('display', 'none', 'important');
            listaEPPAgregados.style.setProperty('visibility', 'hidden', 'important');
            console.log(' [resetearModalAgregarEPP] Tabla ocultada (modo edicion)');
        } else {
            // Si estamos en modo agregar, mostrar la tabla
            listaEPPAgregados.removeAttribute('style');
            listaEPPAgregados.style.setProperty('display', 'block', 'important');
            listaEPPAgregados.style.setProperty('visibility', 'visible', 'important');
            console.log(' [resetearModalAgregarEPP] Tabla restaurada (modo agregar)');
        }
    }
    
    actualizarEstilosCampos();
    
    // Restaurar visibilidad del buscador (oculto en modo edicion)
    const buscadorSection = document.getElementById('buscadorEPPSection');
    if (buscadorSection) {
        buscadorSection.style.display = '';
    }
    
    // Ocultar seccion de fotos (solo para modo edicion)
    const seccionFotosResetEl = document.getElementById('seccionFotosEPP');
    if (seccionFotosResetEl) {
        seccionFotosResetEl.style.display = 'none';
    }
    
    // Resetear contador de fotos
    const contadorFotosReset = document.getElementById('contadorFotosEPP');
    if (contadorFotosReset) contadorFotosReset.textContent = '0';
    
    console.log(' [resetearModalAgregarEPP] COMPLETADO - Modal reseteado completamente');
}

function filtrarEPPBuscador(valor) {
    const busqueda = valor.toLowerCase().trim();
    
    if (!busqueda) {
        document.getElementById('resultadosBuscadorEPP').style.display = 'none';
        document.getElementById('productoCardEPP').style.display = 'none';
        document.getElementById('formularioAgregarEPP').style.display = 'none';
        document.getElementById('observacionesContainer').style.display = 'none';
        resetearFormularioEPP();
        return;
    }

    // Usar el filtrarEPP del servicio que llena el contenedor resultadosBuscadorEPP
    if (globalThis.eppService && typeof globalThis.eppService.filtrarEPP === 'function') {
        globalThis.eppService.filtrarEPP(busqueda);
        // El servicio llenará automáticamente resultadosBuscadorEPP
    } else {
        console.warn('eppService.filtrarEPP no disponible');
    }
}

function mostrarProductoEPP(producto) {
    console.log(' [mostrarProductoEPP] Llamado con producto:', producto);
    productoSeleccionadoEPP = producto;
    
    // Detectar si estamos en modo edicion
    const enModoEdicion = globalThis.eppEnEdicion && typeof globalThis.eppEnEdicion === 'object' && Object.keys(globalThis.eppEnEdicion).length > 0;
    console.log(' [mostrarProductoEPP] En modo edicion:', enModoEdicion);
    
    // LIMPIAR FOTOS DEL PRODUCTO ANTERIOR - pero solo en modo NORMAL
    // En modo edicion, mantenemos las fotos que el usuario haya cargado
    if (!enModoEdicion) {
        globalThis.fotosEPP = [];
        const contenedorFotosEPP = document.getElementById('contenedorFotosEPP');
        const mensajeDragDrop = document.getElementById('mensajeDragDrop');
        
        if (contenedorFotosEPP) {
            // Eliminar elementos de fotos pero mantener mensaje
            const fotosItems = contenedorFotosEPP.querySelectorAll('.foto-epp-item');
            fotosItems.forEach(item => item.remove());
            console.log(' [mostrarProductoEPP] Fotos del producto anterior limpiadas (modo normal)');
        }
        
        if (mensajeDragDrop) {
            mensajeDragDrop.style.display = 'flex';
            console.log(' [mostrarProductoEPP] Mensaje inicial de drag-drop mostrado');
        }
    } else {
        console.log(' [mostrarProductoEPP] Modo edicion - mantienen las fotos actuales');
    }
    
    // Mostrar tarjeta
    const tarjeta = document.getElementById('productoCardEPP');
    console.log(' [mostrarProductoEPP] Elemento tarjeta encontrado:', !!tarjeta);
    if (tarjeta) {
        tarjeta.style.display = 'block';
        console.log(' [mostrarProductoEPP] Tarjeta visible - display:', tarjeta.style.display);
    }
    
    const nombreElement = document.getElementById('nombreProductoEPP');
    console.log(' [mostrarProductoEPP] Elemento nombre encontrado:', !!nombreElement);
    if (nombreElement) {
        nombreElement.value = producto.nombre_completo || producto.nombre;
        console.log(' [mostrarProductoEPP] Nombre actualizado:', nombreElement.value);
    }

    // Mostrar formulario
    const formulario = document.getElementById('formularioAgregarEPP');
    console.log(' [mostrarProductoEPP] Elemento formulario encontrado:', !!formulario);
    if (formulario) {
        formulario.style.display = 'grid';
        console.log(' [mostrarProductoEPP] Formulario visible - display:', formulario.style.display);
    }
    
    const obsContainer = document.getElementById('observacionesContainer');
    console.log(' [mostrarProductoEPP] Elemento observaciones container encontrado:', !!obsContainer);
    if (obsContainer) {
        obsContainer.style.display = 'block';
        console.log(' [mostrarProductoEPP] Observaciones container visible - display:', obsContainer.style.display);
    }
    
    const btnAgregar = document.getElementById('btnAgregarALista');
    console.log(' [mostrarProductoEPP] Botón agregar encontrado:', !!btnAgregar);
    if (btnAgregar) {
        btnAgregar.style.display = 'flex';
        console.log(' [mostrarProductoEPP] Botón agregar visible - display:', btnAgregar.style.display);
    }

    // Habilitar campos
    const cantidadInput = document.getElementById('cantidadEPP');
    const obsInput = document.getElementById('observacionesEPP');
    console.log(' [mostrarProductoEPP] Cantidad input encontrado:', !!cantidadInput, 'Obs input encontrado:', !!obsInput);
    
    if (cantidadInput) {
        cantidadInput.disabled = false;
        console.log(' [mostrarProductoEPP] Campo cantidad habilitado');
    }

    const valorUnitarioInput = document.getElementById('valorUnitarioEPP');
    const vuCont = document.getElementById('valorUnitarioTotalContainer');
    const modoCotizacion = !!globalThis.__EPP_COTIZACION_MODE__;
    if (vuCont) vuCont.style.display = modoCotizacion ? 'block' : 'none';
    if (valorUnitarioInput) {
        valorUnitarioInput.disabled = !modoCotizacion;
    }

    // Recalcular total al habilitar
    actualizarTotalEPP();
    if (obsInput) {
        obsInput.disabled = false;
        console.log(' [mostrarProductoEPP] Campo observaciones habilitado');
    }
    if (btnAgregar) {
        btnAgregar.disabled = false;
        console.log(' [mostrarProductoEPP] Botón habilitado');
    }

    actualizarEstilosCampos();
    console.log(' [mostrarProductoEPP] Completado - todos los elementos configurados');
}


// Funcion cargarTallasEPP removida - talla incluida en nombre_completo

// Almacenar referencias globales a archivos para mantener blob URLs validadas
globalThis._eppFilesCache = new Map();

// Funcion para guardar archivo en cache global
function _guardarArchivoEnCache(blobUrl, file) {
    globalThis._eppFilesCache.set(blobUrl, file);
    console.log(`[_guardarArchivoEnCache] Archivo cacheado para blob URL: ${blobUrl}`);
}

// Funcion para convertir URLs blob a archivos para enviar como FormData (no base64 en JSON)
async function convertirBlobAArchivos(imagenes) {
    const imagenesConvertidas = [];
    
    for (const imagen of imagenes) {
        if (imagen.previewUrl && imagen.file) {
            // Obtener ID del pedido actual (temporal hasta que se cree el pedido)
            const pedidoId = globalThis.pedidoIdActual || 'temp';
            
            // Generar nombre generico para evitar conflictos
            const timestamp = Date.now();
            const randomSuffix = Math.random().toString(36).substring(2, 8);
            const nombreLimpio = imagen.nombre.replace(/[^a-zA-Z0-9.-]/g, '_');
            const nombreArchivo = `${timestamp}_${randomSuffix}_${nombreLimpio}`;
            
            // Preparar rutas para guardado en storage/pedidos/[pedido_id]/epp/
            const rutaStorage = `pedidos/${pedidoId}/epp`;
            const rutaCompleta = `${rutaStorage}/${nombreArchivo}`;
            const rutaWeb = `/storage/${rutaCompleta}`;
            
            imagenesConvertidas.push({
                id: imagen.id,
                nombre: imagen.nombre,
                extension: imagen.extension,
                file: imagen.file, // Para FormData
                previewUrl: imagen.previewUrl, // Para mostrar en UI
                
                // Metadatos para guardado en BD (sin base64)
                ruta_storage: rutaStorage, // pedidos/25/epp
                nombre_archivo: nombreArchivo, // 123456_abc123_images.jfif
                ruta_completa: rutaCompleta, // pedidos/25/epp/123456_abc123_images.jfif
                ruta_web: rutaWeb, // /storage/pedidos/25/epp/123456_abc123_images.jfif
                
                // Para tabla pedido_epp_imagenes
                pedido_epp_id: null, // Se asiganara cuando se guarde el EPP
                principal: 0, // 0 = no principal, 1 = principal
                orden: imagenesConvertidas.length + 1 // Orden automatio
            });
            
            console.log(`[convertirBlobAArchivos] Imagen preparada: ${imagen.nombre} -> ${rutaWeb}`);
        } else {
            // Si ya es base64 u otro formato, mantenerla (para compatibilidad)
            imagenesConvertidas.push(imagen);
        }
    }
    
    return imagenesConvertidas;
}

// Funcion para preparar datos para enviar: JSON limpio + FormData para imagenes
function prepararDatosParaEnvio(itemsPedido) {
    const datosLimpios = [];
    const formData = new FormData();
    
    itemsPedido.forEach((item, index) => {
        // Caso: item es el pedido completo (tiene propiedad epps[])
        if (item && item.epps && Array.isArray(item.epps)) {
            const pedidoProcesado = { ...item };
            pedidoProcesado.epps = item.epps.map((epp, eppIndex) => {
                const tieneImagenesConArchivo = epp.imagenes && epp.imagenes.length > 0 &&
                    epp.imagenes.some(img => img.file);

                const eppData = {
                    ...epp,
                    modo_imagenes: tieneImagenesConArchivo ? 'upload' : 'reuse'
                };

                if (tieneImagenesConArchivo) {
                    eppData.imagenes = [];
                    epp.imagenes.forEach((imagen, imgIndex) => {
                        if (imagen.file) {
                            const fieldName = `epp_imagen_${eppIndex}_${imgIndex}`;
                            formData.append(fieldName, imagen.file);
                            formData.append(`${fieldName}_metadata`, JSON.stringify({
                                id: imagen.id,
                                nombre: imagen.nombre,
                                extension: imagen.extension,
                                ruta_storage: imagen.ruta_storage,
                                nombre_archivo: imagen.nombre_archivo,
                                ruta_completa: imagen.ruta_completa,
                                ruta_web: imagen.ruta_web,
                                pedido_epp_id: imagen.pedido_epp_id,
                                principal: imagen.principal,
                                orden: imagen.orden
                            }));
                            eppData.imagenes.push({
                                id: imagen.id,
                                nombre: imagen.nombre,
                                ruta_web: imagen.ruta_web,
                                principal: imagen.principal,
                                orden: imagen.orden
                            });
                        }
                    });
                }

                return eppData;
            });
            datosLimpios.push(pedidoProcesado);

        // Caso legacy: item es un EPP individual con imagenes
        } else if (item.tipo === 'epp' && item.imagenes && item.imagenes.length > 0) {
            const eppData = {
                uid: item.uid || `uid-${Date.now()}-${Math.random().toString(36).substring(2, 8)}`,
                epp_id: item.epp_id,
                nombre_epp: item.nombre_epp,
                categoria: item.categoria || '',
                cantidad: item.cantidad,
                observaciones: item.observaciones,
                modo_imagenes: 'upload',
                imagenes: []
            };
            
            item.imagenes.forEach((imagen, imgIndex) => {
                if (imagen.file) {
                    const fieldName = `epp_imagen_${index}_${imgIndex}`;
                    formData.append(fieldName, imagen.file);
                    formData.append(`${fieldName}_metadata`, JSON.stringify({
                        id: imagen.id,
                        nombre: imagen.nombre,
                        extension: imagen.extension,
                        ruta_storage: imagen.ruta_storage,
                        nombre_archivo: imagen.nombre_archivo,
                        ruta_completa: imagen.ruta_completa,
                        ruta_web: imagen.ruta_web,
                        pedido_epp_id: imagen.pedido_epp_id,
                        principal: imagen.principal,
                        orden: imagen.orden
                    }));
                    eppData.imagenes.push({
                        id: imagen.id,
                        nombre: imagen.nombre,
                        ruta_web: imagen.ruta_web,
                        principal: imagen.principal,
                        orden: imagen.orden
                    });
                }
            });
            
            datosLimpios.push(eppData);
        } else {
            // Agregar outros items sin modificar, asegurando modo_imagenes solo en EPPs individuales
            if (item.tipo === 'epp') {
                datosLimpios.push({ ...item, modo_imagenes: item.modo_imagenes || 'reuse' });
            } else {
                datosLimpios.push(item);
            }
        }
    });
    
    return {
        jsonData: datosLimpios,
        formData: formData
    };
}

// Hacer la Funcion disponible globalmente (nombre especifico para no pisar la version principal)
globalThis.prepararDatosEppParaFormData = prepararDatosParaEnvio;

function agregarEPPALista() {
    if (!productoSeleccionadoEPP) {
        alert('Por favor selecciona un producto');
        return;
    }

    const cantidad = document.getElementById('cantidadEPP').value;
    const observaciones = document.getElementById('observacionesEPP').value || '-';

    if (!cantidad || cantidad <= 0) {
        alert('Por favor ingresa una cantidad valida');
        return;
    }

    const modoCotizacion = !!globalThis.__EPP_COTIZACION_MODE__;
    const valorUnitarioRaw = modoCotizacion ? document.getElementById('valorUnitarioEPP')?.value : null;
    const valorUnitario = (valorUnitarioRaw !== undefined && valorUnitarioRaw !== null && String(valorUnitarioRaw).trim() !== '')
        ? Number(valorUnitarioRaw)
        : null;
    const totalValue = modoCotizacion ? document.getElementById('totalEPP')?.value : null;
    const total = (totalValue !== undefined && totalValue !== null && String(totalValue).trim() !== '')
        ? Number(totalValue)
        : null;

    // Agregar a la lista (usar las fotos tal como estan, solo URLs blob temporales)
    const eppData = {
        id: productoSeleccionadoEPP.id,
        nombre_completo: productoSeleccionadoEPP.nombre_completo || productoSeleccionadoEPP.nombre,
        cantidad: parseInt(cantidad),
        observaciones: observaciones,
        valor_unitario: modoCotizacion ? valorUnitario : null,
        total: modoCotizacion ? total : null,
        imagenes: [...globalThis.fotosEPP], // Mantener fotos con URLs blob (temporal)
        imagen: productoSeleccionadoEPP.imagen
    };

    eppAgregadosList.push(eppData);

    console.log(' EPP agregado a lista:', eppAgregadosList[eppAgregadosList.length - 1]);

    // Actualizar tabla
    renderizarTablaEPP();

    // Mostrar lista si hay items
    if (eppAgregadosList.length > 0) {
        const listaContainer = document.getElementById('listaEPPAgregados');
        listaContainer.style.display = 'block';
        console.log(' [agregarEPPALista] Lista mostrada. Display:', listaContainer.style.display);
        document.getElementById('btnFinalizarAgregarEPP').disabled = false;
    }

    //IMPORTANTE: Limpiar completamente el formulario despues de agregar a lista
    console.log('[agregarEPPALista] Limpiando modal despues de agregar EPP...');
    
    // Resetear completamente el modal PERO manteniendo el estado del Botón Finalizar
    const botonFinalizarEstado = document.getElementById('btnFinalizarAgregarEPP').disabled;
    
    resetearModalAgregarEPP();
    
    // Restaurar estado del Botón Finalizar si hay EPPs
    if (eppAgregadosList.length > 0) {
        document.getElementById('btnFinalizarAgregarEPP').disabled = botonFinalizarEstado;
    }
    
    // Limpiar elementos especificos que no cubre resetearModalAgregarEPP
    document.getElementById('productoCardEPP').style.display = 'none';
    document.getElementById('formularioAgregarEPP').style.display = 'none';
    document.getElementById('observacionesContainer').style.display = 'none';
    
    // Limpiar buscador y desseleccionar producto
    document.getElementById('inputBuscadorEPP').value = '';
    document.getElementById('resultadosBuscadorEPP').style.display = 'none';
    productoSeleccionadoEPP = null;
    
    // Limpiar fotos del contenedor (ya estan asociadas al EPP)
    limpiarFotosEPP();
    
    console.log('[agregarEPPALista] Modal limpiado completamente');
}

function limpiarFotosEPP() {
    console.log(' [limpiarFotosEPP] Limpiando fotos del contenedor EPP');
    
    // NO liberar URLs blob si estan asociadas a EPPs agregados
    // Las URLs blob se mantienen para mostrar en las tarjetas
    // Solo limpiar el array temporal del contenedor
    globalThis.fotosEPP = [];
    
    // Limpiar contenedor HTML (solo eliminar elementos de fotos, no el mensaje)
    const contenedor = document.getElementById('contenedorFotosEPP');
    if (contenedor) {
        // Eliminar solo los elementos de fotos (clase foto-epp-item)
        const elementosFoto = contenedor.querySelectorAll('.foto-epp-item');
        elementosFoto.forEach(elemento => elemento.remove());
        
        // Mostrar mensaje inicial
        const mensajeDragDrop = document.getElementById('mensajeDragDrop');
        if (mensajeDragDrop) {
            mensajeDragDrop.style.display = 'flex';
        }
        
        console.log('[limpiarFotosEPP] Contenedor limpiado y mensaje inicial restaurado');
    }
}

function renderizarTablaEPP() {
    console.log(' [renderizarTablaEPP] Iniciado. Total items:', eppAgregadosList.length);
    const tbody = document.getElementById('cuerpoTablaEPP');
    if (!tbody) {
        console.error(' [renderizarTablaEPP] tbody no encontrado');
        return;
    }
    
    tbody.innerHTML = '';

    eppAgregadosList.forEach((epp, idx) => {
        console.log(` [renderizarTablaEPP] Renderizando EPP ${idx + 1}:`, epp.nombre_completo);
        const row = document.createElement('tr');
        row.className = idx % 2 === 0 ? 'bg-white' : 'bg-gray-50';
        
        // Generar miniaturas de fotos
        let fotosHtml = '';
        if (epp.imagenes && epp.imagenes.length > 0) {
            // Mostrar hasta 3 miniaturas
            const fotosMostrar = epp.imagenes.slice(0, 3);
            fotosHtml = fotosMostrar.map(foto =>
                `<img src="${foto.previewUrl || foto.base64}" alt="Foto EPP" class="w-8 h-8 object-cover rounded border border-gray-200" title="${foto.nombre}">`
            ).join(' ');
            
            // Si hay más de 3 fotos, mostrar indicador
            if (epp.imagenes.length > 3) {
                fotosHtml += `<span class="text-xs text-gray-500 ml-1">+${epp.imagenes.length - 3}</span>`;
            }
        } else {
            fotosHtml = '<span class="text-gray-400 text-xs">Sin fotos</span>';
        }
        
        row.innerHTML = `
            <td class="px-4 py-2">
                <div class="flex gap-1 items-center">
                    ${fotosHtml}
                </div>
            </td>
            <td class="px-4 py-2 text-gray-900 font-medium">${epp.nombre_completo}</td>
            <td class="px-4 py-2 text-gray-700">${epp.cantidad}</td>
            <td class="px-4 py-2 text-gray-700 text-xs">${epp.observaciones}</td>
            <td class="px-4 py-2 text-center">
                <button
                    type="button"
                    onclick="event.stopPropagation(); eliminarEPPDeLista(${epp.id})"
                    class="text-red-600 hover:text-red-800 font-medium transition"
                >
                    <i class="material-symbols-rounded" style="font-size: 18px;">delete</i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
    
    console.log(' [renderizarTablaEPP] Completado. Filas renderizadas:', eppAgregadosList.length);
}

function resetearFormularioEPP() {
    document.getElementById('nombreProductoEPP').value = '';
    document.getElementById('cantidadEPP').disabled = true;
    document.getElementById('cantidadEPP').value = '1';
    document.getElementById('observacionesEPP').disabled = true;
    document.getElementById('observacionesEPP').value = '';
    document.getElementById('btnAgregarALista').disabled = true;

    // Limpiar fotos del EPP
    limpiarFotosEPP();

    actualizarEstilosCampos();
}

function actualizarEstilosCampos() {
    const cantidadInput = document.getElementById('cantidadEPP');
    const observacionesInput = document.getElementById('observacionesEPP');

    // Actualizar cantidad
    if (cantidadInput) {
        if (cantidadInput.disabled) {
            cantidadInput.classList.add('disabled');
        } else {
            cantidadInput.classList.remove('disabled');
        }
    }

    // Actualizar observaciones
    if (observacionesInput) {
        if (observacionesInput.disabled) {
            observacionesInput.classList.add('disabled');
        } else {
            observacionesInput.classList.remove('disabled');
        }
    }
}
 

// ====================================
// FUNCIONES PARA CREAR NUEVO EPP
// ====================================

function abrirFormularioCrearEPP() {
    document.getElementById('formularioCrearEPP').style.display = 'block';
    document.getElementById('nombreCompletNuevoEPP').focus();
}

function cerrarFormularioCrearEPP() {
    document.getElementById('formularioCrearEPP').style.display = 'none';
    document.getElementById('nombreCompletNuevoEPP').value = '';
}

async function guardarNuevoEPP() {
    const nombreCompleto = document.getElementById('nombreCompletNuevoEPP').value.trim();
    
    if (!nombreCompleto) {
        Swal.fire({
            icon: 'warning',
            title: 'Campo requerido',
            text: 'Por favor ingresa un nombre completo para el EPP',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            customClass: {
                container: 'toast-epp-container'
            }
        });
        return;
    }

    try {
        console.log(' [guardarNuevoEPP] Creando EPP con nombre:', nombreCompleto);
        
        // Crear el EPP en la base de datos
        const response = await fetch('/api/epp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({
                nombre_completo: nombreCompleto,
                categoria_id: 19,  // categoria fija como especificaste
                tipo: 'PRODUCTO',
                activo: true  // Usar boolean true en lugar de 1
            })
        });

        const resultado = await response.json();

        if (!response.ok) {
            console.error('[guardarNuevoEPP] Response error:', {
                status: response.status,
                resultado: resultado
            });
            throw new Error(resultado.message || `Error ${response.status}: ${response.statusText}`);
        }

        if (!resultado.success) {
            throw new Error(resultado.message || 'Error desconocido');
        }

        const nuevoEPP = resultado.data || resultado.epp;
        console.log(' [guardarNuevoEPP] EPP creado exitosamente:', nuevoEPP);

        // Mostrar el producto inmediatamente en el formulario
        mostrarProductoEPP({
            id: nuevoEPP.id,
            nombre_completo: nuevoEPP.nombre_completo,
            nombre: nuevoEPP.nombre_completo,
            imagen: '',
            tallas: []
        });

        // Cerrar el formulario de creacion
        cerrarFormularioCrearEPP();
        
        // Mostrar notificación diferente si ya existía
        if (resultado.existia) {
            Swal.fire({
                icon: 'info',
                title: 'EPP existente',
                text: 'Este EPP ya existe. Se esta utilizando el existente.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                customClass: {
                    container: 'toast-epp-container'
                }
            });
        } else {
            Swal.fire({
                icon: 'success',
                title: 'EPP creado',
                text: 'EPP creado exitosamente. Ahora agrega la cantidad y observaciones.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                customClass: {
                    container: 'toast-epp-container'
                }
            });
        }

    } catch (error) {
        console.error(' [guardarNuevoEPP] Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al crear el EPP: ' + error.message,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000,
            customClass: {
                container: 'toast-epp-container'
            }
        });
    }
}

// ====================================
// FUNCIÓN PARA EDITAR EPP AGREGADO
// ====================================
// Inicializar en globalThis para que sea accesible globalmente
if (!globalThis.eppEnEdicion) {
    globalThis.eppEnEdicion = null;  // Para guardar el índice del EPP que se esta editando
}
let eppEnEdicion = null;  // Variable local para compatibilidad

function editarEPPAgregado(eppData) {
    console.log(' [editarEPPAgregado] INICIANDO - Abriendo modal de edicion para EPP:', eppData);
    
    // Usar el modal de edicion individual que ya existe
    if (typeof abrirModalEditarEPP === 'function') {
        // Preparar datos en formato compatible
        const eppParaEditar = {
            id: eppData.epp_id || eppData.id,
            nombre: eppData.nombre_epp || eppData.nombre,
            nombre_completo: eppData.nombre_epp || eppData.nombre,
            cantidad: eppData.cantidad || 1,
            observaciones: eppData.observaciones || '-',
            imagenes: eppData.imagenes || [],
            tarjetaId: eppData.tarjetaId  // Pasar ID de la tarjeta visual
        };
        
        console.log(' [editarEPPAgregado] Abriendo modal de edicion con datos:', eppParaEditar);
        abrirModalEditarEPP(eppParaEditar);
    } else {
        console.error(' [editarEPPAgregado] Funcion abrirModalEditarEPP no disponible');
    }
}

function guardarEdicionEPP() {
    console.log(' [guardarEdicionEPP] INICIANDO - globalThis.eppEnEdicion:', globalThis.eppEnEdicion);
    console.log(' [guardarEdicionEPP] Es objeto válido:', globalThis.eppEnEdicion && typeof globalThis.eppEnEdicion === 'object');
    
    if (!globalThis.eppEnEdicion || typeof globalThis.eppEnEdicion !== 'object' || Object.keys(globalThis.eppEnEdicion).length === 0) {
        console.error(' [guardarEdicionEPP]  No hay EPP válido en edicion:', globalThis.eppEnEdicion);
        return;
    }
    console.log(' [guardarEdicionEPP] EPP en edicion válido');

    const nombre = document.getElementById('nombreProductoEPP').value;
    const cantidad = document.getElementById('cantidadEPP').value;
    const observaciones = document.getElementById('observacionesEPP').value;

    const modoCotizacion = !!globalThis.__EPP_COTIZACION_MODE__;
    const vuRaw = modoCotizacion ? document.getElementById('valorUnitarioEPP')?.value : null;
    const vu = (vuRaw !== undefined && vuRaw !== null && String(vuRaw).trim() !== '' && !isNaN(Number(vuRaw)))
        ? Number(vuRaw)
        : null;
    const totalRaw = modoCotizacion ? document.getElementById('totalEPP')?.value : null;
    const total = (totalRaw !== undefined && totalRaw !== null && String(totalRaw).trim() !== '' && !isNaN(Number(totalRaw)))
        ? Number(totalRaw)
        : null;

    const imagenes = Array.isArray(globalThis.fotosEPP) ? globalThis.fotosEPP : [];

    if (!cantidad || cantidad <= 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Cantidad invalida',
            text: 'La cantidad debe ser mayor a 0',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            customClass: {
                container: 'toast-epp-container'
            }
        });
        return;
    }

    console.log(' [guardarEdicionEPP] Guardando cambios para EPP:', {
        epp_id: globalThis.eppEnEdicion.epp_id,
        nombre: nombre,
        cantidad: cantidad,
        observaciones: observaciones
    });

    // Actualizar en store o globalThis.itemsPedido
    const targetId = globalThis.eppEnEdicion.epp_id || globalThis.eppEnEdicion.id;
    const datosActualizados = {
        nombre_epp: nombre,
        nombre: nombre,
        cantidad: parseInt(cantidad),
        observaciones: observaciones || '-',
        imagenes: imagenes
    };
    if (modoCotizacion) {
        datosActualizados.valor_unitario = vu;
        datosActualizados.total = total;
    }

    if (globalThis.eppStore) {
        const ok = globalThis.eppStore.actualizarItem(targetId, datosActualizados);
        if (!ok) {
            console.warn(' [guardarEdicionEPP] No se encontro EPP en eppStore para actualizar');
        }
    } else {
        // Fallback sin store
        const index = globalThis.itemsPedido.findIndex(item =>
            String(item.epp_id || item.id) === String(targetId)
        );
        if (index !== -1) {
            Object.assign(globalThis.itemsPedido[index], datosActualizados);
            console.log(' [guardarEdicionEPP] EPP actualizado en globalThis.itemsPedido:', globalThis.itemsPedido[index]);
        } else {
            console.warn(' [guardarEdicionEPP] No se encontro EPP en globalThis.itemsPedido para actualizar');
        }
    }

    // Actualizar visualmente en la tarjeta (usar el item manager correspondiente)
    const esVistaNuevo = globalThis.location.pathname.includes('/crear-nuevo');
    
    if (esVistaNuevo) {
        // Vista de nuevo pedido - usar clases -nuevo
        if (globalThis.eppItemManagerTarjeta && typeof globalThis.eppItemManagerTarjeta.actualizarItem === 'function') {
            // Buscar la tarjeta usando el ID original para obtener el ID único
            const tarjeta = document.querySelector(`.item-epp-card-nuevo[data-epp-original-id="${targetId}"]`);
            const tarjetaId = tarjeta ? tarjeta.getAttribute('data-epp-id') : targetId;
            
            console.log('[guardarEdicionEPP] Actualizando tarjeta:', {
                targetId: targetId,
                tarjetaId: tarjetaId,
                tarjetaEncontrada: !!tarjeta
            });
            
            globalThis.eppItemManagerTarjeta.actualizarItem(tarjetaId, {
                nombre,
                cantidad: parseInt(cantidad),
                observaciones: observaciones || '-',
                imagenes,
                valor_unitario: undefined, // No hay valor unitario en nuevo pedido
                total: undefined, // No hay total en nuevo pedido
            });
        }
    } else {
        // Vista de cotizacion - usar clases genericas
        if (globalThis.eppItemManagerTabla && typeof globalThis.eppItemManagerTabla.actualizarItem === 'function') {
            globalThis.eppItemManagerTabla.actualizarItem(targetId, {
                nombre,
                cantidad: parseInt(cantidad),
                observaciones: observaciones || '-',
                imagenes,
                valor_unitario: modoCotizacion ? vu : undefined,
                total: modoCotizacion ? total : undefined,
            });
        }
    }

    // Limpiar referencia
    console.log(' [cerrarModalAgregarEPP] Limpiando globalThis.eppEnEdicion');
    globalThis.eppEnEdicion = null;
    
    // Restaurar botones a estado original
    const btnAgregar = document.getElementById('btnAgregarALista');
    const btnGuardarCambios = document.getElementById('btnGuardarCambiosEPP');
    const btnFinalizar = document.getElementById('btnFinalizarAgregarEPP');
    
    if (btnAgregar) {
        btnAgregar.style.display = 'none';
        console.log(' [guardarEdicionEPP] Botón agregar ocultado');
    }
    if (btnGuardarCambios) {
        btnGuardarCambios.style.display = 'none';
        btnGuardarCambios.disabled = true;
        console.log(' [guardarEdicionEPP] Botón guardar cambios ocultado');
    }
    if (btnFinalizar) {
        btnFinalizar.style.display = 'flex';
        btnFinalizar.disabled = true;
        console.log(' [guardarEdicionEPP] Botón finalizar restaurado');
    }
    
    // Cerrar modal directamente sin confirmacion (ya se guarda)
    cerrarModalAgregarEPPConfirmado();

    // Mostrar toast de éxito pequeño
    Swal.fire({
        icon: 'success',
        title: 'Guardado',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2500,
        timerProgressBar: false,
        customClass: {
            popup: 'swal2-toast-compact'
        },
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });
    
    // Agregar estilos CSS para toast compacto si no existen
    if (!document.getElementById('swal2-toast-compact-style')) {
        const style = document.createElement('style');
        style.id = 'swal2-toast-compact-style';
        style.textContent = `
            .swal2-toast-compact {
                min-width: auto !important;
                width: auto !important;
                padding: 8px 12px !important;
                border-radius: 6px !important;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15) !important;
            }
            .swal2-toast-compact .swal2-title {
                font-size: 14px !important;
                margin: 0 !important;
            }
            .swal2-toast-compact .swal2-icon {
                width: 24px !important;
                height: 24px !important;
                margin-right: 8px !important;
            }
        `;
        document.head.appendChild(style);
    }
    
    console.log(' [guardarEdicionEPP] edicion completada');
}

// ====================================

async function finalizarAgregarEPP() {
    if (eppAgregadosList.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Sin EPPs',
            text: 'Por favor agrega al menos un EPP',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            customClass: {
                container: 'toast-epp-container'
            }
        });
        return;
    }

    // ==========================================
    // MODO EDICIÓN: Guardar directamente vía API
    // ==========================================
    const pedidoIdExistente = globalThis.__EPP_AGREGAR_PEDIDO_EXISTENTE__;
    if (pedidoIdExistente) {
        console.log('[finalizarAgregarEPP] MODO EDICIÓN - Pidiendo novedad antes de guardar');
        
        // Pedir novedad/justificación del cambio
        // Inyectar CSS para z-index encima del modal EPP (z-index 9999999)
        let novedadStyle = document.getElementById('swal-epp-novedad-zindex');
        if (!novedadStyle) {
            novedadStyle = document.createElement('style');
            novedadStyle.id = 'swal-epp-novedad-zindex';
            novedadStyle.textContent = `
                .swal-epp-novedad-container {
                    z-index: 20000000 !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    position: fixed !important;
                }
                .swal-epp-novedad-container .swal2-popup {
                    margin: auto !important;
                }
            `;
            document.head.appendChild(novedadStyle);
        }

        const novedadResult = await Swal.fire({
            title: 'Novedad del cambio',
            input: 'textarea',
            inputLabel: '¿Por qué agregaste estos EPP?',
            inputPlaceholder: 'Describe brevemente el motivo...',
            inputAttributes: { 'aria-label': 'Novedad del cambio' },
            showCancelButton: true,
            confirmButtonText: ' Guardar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#10b981',
            customClass: { container: 'swal-epp-novedad-container' },
            inputValidator: (value) => {
                if (!value || !value.trim()) {
                    return 'Debes ingresar una novedad del cambio';
                }
            }
        });

        if (!novedadResult.isConfirmed) {
            console.log('[finalizarAgregarEPP] Usuario canceló la novedad');
            return;
        }

        const motivoUsuario = novedadResult.value.trim();
        // Construir novedad con nombres de todos los EPPs agregados
        const nombresEpp = eppAgregadosList.map(e => `"${e.nombre_completo || 'Sin nombre'}"`).join(', ');
        const novedad = `Agregó EPP: ${nombresEpp} - ${motivoUsuario}`;
        console.log('[finalizarAgregarEPP] MODO EDICIÓN - Guardando vía API para pedido:', pedidoIdExistente, 'novedad:', novedad);
        await _guardarEPPsViaAPI(pedidoIdExistente, novedad);
        return;
    }

    console.log(' [finalizarAgregarEPP] Finalizando con EPP:', eppAgregadosList);
    
    // Inicializar globalThis.itemsPedido si no existe
    if (!globalThis.itemsPedido) {
        globalThis.itemsPedido = [];
    }
    
    // Convertir imagenes de todos los EPPs de forma asíncrona
    const promesasEPP = eppAgregadosList.map(async (epp) => {
        console.log(` [finalizarAgregarEPP] Procesando EPP: ${epp.nombre_completo}`);
        
        // En modo edicion O modo cotizacion, mantener blob URLs (NO convertir a base64)
        // Las referencias a los archivos se guardan globalmente para mantener las blob URLs válidas
        let imagenesParaGuardar;
        const esModoCotizacion = !!globalThis.__EPP_COTIZACION_MODE__;
        const esModoEdicion = !!globalThis.__EPP_MODO_EDICION__;
        
        if (esModoEdicion || esModoCotizacion) {
            console.log(`[finalizarAgregarEPP] Modo ${esModoCotizacion ? 'cotizacion' : 'edicion'} - manteniendo blob URLs`);
            // Mantener las imagenes con blob URLs (los archivos estan referenciados globalmente)
            imagenesParaGuardar = epp.imagenes || [];
        } else {
            // En modo creación de pedido, convertir URLs blob a archivos para guardado
            imagenesParaGuardar = await convertirBlobAArchivos(epp.imagenes);
        }
        
        // Usar el item manager correspondiente segun la vista
        const esVistaNuevo = globalThis.location.pathname.includes('/crear-nuevo');
        const modoCotizacion = !!globalThis.__EPP_COTIZACION_MODE__;
        
        if (esVistaNuevo) {
            // Vista de nuevo pedido - usar clases -nuevo
            if (globalThis.eppItemManagerTarjeta && typeof globalThis.eppItemManagerTarjeta.crearItem === 'function') {
                globalThis.eppItemManagerTarjeta.crearItem(
                    epp.id,                    // id
                    epp.nombre_completo,        // nombre
                    'EPP',                     // categoria
                    epp.cantidad,              // cantidad
                    epp.observaciones,         // observaciones
                    imagenesParaGuardar,      // imagenes convertidas a archivos
                    null,
                    null, // No hay valor unitario en nuevo pedido
                    null  // No hay total en nuevo pedido
                );
                
                // Guardar referencias en cache global tambien para mantener blob URLs vivas
                if (imagenesParaGuardar && imagenesParaGuardar.length > 0) {
                    imagenesParaGuardar.forEach((imagen, idx) => {
                        if (imagen.previewUrl && imagen.previewUrl.startsWith('blob:')) {
                            _guardarArchivoEnCache(imagen.previewUrl, imagen.file || imagen);
                        }
                    });
                }
                
                console.log(` [finalizarAgregarEPP] EPP agregado a tarjeta (nuevo): ${epp.nombre_completo}`);
            } else {
                console.warn(' [finalizarAgregarEPP] eppItemManagerTarjeta no disponible');
            }
        } else {
            // Vista de cotizacion - usar clases genericas
            if (globalThis.eppItemManagerTabla && typeof globalThis.eppItemManagerTabla.crearItem === 'function') {
                globalThis.eppItemManagerTabla.crearItem(
                    epp.id,                    // id
                    epp.nombre_completo,        // nombre
                    'EPP',                     // categoria
                    epp.cantidad,              // cantidad
                    epp.observaciones,         // observaciones
                    imagenesParaGuardar,      // imagenes convertidas a archivos
                    null,
                    modoCotizacion ? epp.valor_unitario : null,
                    modoCotizacion ? epp.total : null,
                    'epp'                      // tipo
                );
                
                // Guardar referencias en cache global tambien para mantener blob URLs vivas
                if (imagenesParaGuardar && imagenesParaGuardar.length > 0) {
                    imagenesParaGuardar.forEach((imagen, idx) => {
                        if (imagen.previewUrl && imagen.previewUrl.startsWith('blob:')) {
                            _guardarArchivoEnCache(imagen.previewUrl, imagen.file || imagen);
                        }
                    });
                }
                
                console.log(` [finalizarAgregarEPP] EPP agregado a tarjeta (cotizacion): ${epp.nombre_completo}`);
            } else {
                console.warn(' [finalizarAgregarEPP] eppItemManagerTabla no disponible');
            }
        }
        
        // tambien guardar en globalThis.itemsPedido para que se enviar al servidor
        const eppData = {
            tipo: 'epp',
            epp_id: epp.id,
            nombre_epp: epp.nombre_completo,
            cantidad: epp.cantidad,
            observaciones: epp.observaciones,
            valor_unitario: modoCotizacion ? epp.valor_unitario : null,
            total: modoCotizacion ? epp.total : null,
            imagenes: imagenesParaGuardar, // Usar siempre las imagenes procesadas (base64 en cotizacion/edicion, rutas en creacion)
            modo_imagenes: (imagenesParaGuardar && imagenesParaGuardar.length > 0) ? 'upload' : 'reuse' // Indicar modo de imagenes
        };
        
        return eppData;
    });
    
    // Esperar a que todas las conversiones terminen
    try {
        const eppsProcesados = await Promise.all(promesasEPP);
        
        // Agregar todos los EPPs procesados via eppStore o globalThis.itemsPedido
        eppsProcesados.forEach((eppData) => {
            if (globalThis.eppStore) {
                globalThis.eppStore.agregarItem(eppData);
            } else {
                globalThis.itemsPedido.push(eppData);
            }
            console.log(` [finalizarAgregarEPP] EPP guardado:`, eppData);
            
            //  CRITICO: tambien registrar en gestionItemsUI para mantener sincronizado
            if (globalThis.gestionItemsUI && typeof globalThis.gestionItemsUI.agregarEPPAlOrden === 'function') {
                globalThis.gestionItemsUI.agregarEPPAlOrden(eppData);
                console.log(` [finalizarAgregarEPP] EPP registrado en gestionItemsUI:`, eppData.nombre_epp);
            } else {
                console.warn(' [finalizarAgregarEPP] gestionItemsUI no disponible');
            }
        });
        
        console.log(' [finalizarAgregarEPP] Todos los EPP han sido procesados y agregados');
        console.log(' [finalizarAgregarEPP] globalThis.itemsPedido actual:', globalThis.itemsPedido);
        
        // Limpiar lista de EPPs agregados ya que fueron guardados
        eppAgregadosList = [];
        globalThis.eppAgregadosList = eppAgregadosList;
        console.log(' [finalizarAgregarEPP] Lista de EPPs agregados limpiada');
        
        // Recalcular totales (eppStore.onChange ya lo hace, esto es para vistas sin store)
        if (!globalThis.eppStore && typeof globalThis.syncTotales === 'function') {
            globalThis.syncTotales();
            console.log('[finalizarAgregarEPP] Totales recalculados (fallback)');
        }
        
        // Cerrar modal
        cerrarModalAgregarEPP();
        
    } catch (error) {
        console.error('[finalizarAgregarEPP] Error procesando EPPs:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Ocurrió un error al procesar las imagenes. Por favor intenta nuevamente.',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000,
            customClass: {
                container: 'toast-epp-container'
            }
        });
    }
}

/**
 * Guardar EPPs directamente vía API cuando se edita un pedido existente
 * Se llama desde finalizarAgregarEPP() cuando __EPP_AGREGAR_PEDIDO_EXISTENTE__ esta activo
 */
async function _guardarEPPsViaAPI(pedidoId, novedad = '') {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let exitosos = 0;
    let errores = [];

    // Mostrar indicador de carga
    Swal.fire({
        title: 'Guardando EPPs...',
        text: `Guardando ${eppAgregadosList.length} EPP(s) en el pedido`,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => Swal.showLoading()
    });

    for (let idx = 0; idx < eppAgregadosList.length; idx++) {
        const epp = eppAgregadosList[idx];
        try {
            const formData = new FormData();
            formData.append('epp_id', epp.id);
            formData.append('cantidad', epp.cantidad || 1);
            formData.append('observaciones', epp.observaciones || '');
            // Solo enviar novedad en el PRIMER EPP para no duplicar
            if (novedad && idx === 0) {
                formData.append('novedad', novedad);
            }

            // Adjuntar imagenes como archivos si existen
            let imagenesAdjuntas = 0;
            if (epp.imagenes && epp.imagenes.length > 0) {
                for (let i = 0; i < epp.imagenes.length; i++) {
                    const img = epp.imagenes[i];
                    if (img.file instanceof File) {
                        formData.append(`imagenes[${i}]`, img.file, img.file.name);
                        imagenesAdjuntas++;
                    } else if (img.previewUrl && img.previewUrl.startsWith('blob:')) {
                        // Intentar obtener el archivo desde el cache global
                        const cachedFile = globalThis._eppFilesCache?.[img.previewUrl];
                        if (cachedFile instanceof File) {
                            formData.append(`imagenes[${i}]`, cachedFile, cachedFile.name);
                            imagenesAdjuntas++;
                        } else {
                            console.warn(`[_guardarEPPsViaAPI] Imagen ${i} no es File valido, omitiendo`);
                        }
                    } else {
                        console.warn(`[_guardarEPPsViaAPI] Imagen ${i} tipo no reconocido:`, typeof img.file, img);
                    }
                }
            }

            // Debug: log de datos que se envian
            console.log(`[_guardarEPPsViaAPI] Enviando EPP "${epp.nombre_completo}" al pedido ${pedidoId}`, {
                epp_id: epp.id,
                cantidad: epp.cantidad || 1,
                observaciones: epp.observaciones || '',
                novedad: (novedad && idx === 0) ? novedad : '(no)',
                imagenes: imagenesAdjuntas,
                csrfToken: csrfToken ? 'presente' : 'AUSENTE'
            });

            const response = await fetch(`/api/pedidos/${pedidoId}/epp/agregar`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                console.error(`[_guardarEPPsViaAPI] Respuesta ${response.status}:`, errorData);
                // Mostrar campos con error si existen
                if (errorData.errors) {
                    const camposError = Object.entries(errorData.errors)
                        .map(([campo, msgs]) => `${campo}: ${Array.isArray(msgs) ? msgs.join(', ') : msgs}`)
                        .join('; ');
                    throw new Error(`${errorData.message || 'Error'}: ${camposError}`);
                }
                throw new Error(errorData.message || `Error HTTP ${response.status}`);
            }

            const resultado = await response.json();
            console.log(`[_guardarEPPsViaAPI]  EPP "${epp.nombre_completo}" guardado:`, resultado);
            exitosos++;
        } catch (error) {
            console.error(`[_guardarEPPsViaAPI]  Error guardando EPP "${epp.nombre_completo}":`, error);
            errores.push({ nombre: epp.nombre_completo, error: error.message });
        }
    }

    // Cerrar loading
    Swal.close();

    // Limpiar flag y lista
    globalThis.__EPP_AGREGAR_PEDIDO_EXISTENTE__ = null;
    eppAgregadosList = [];
    globalThis.eppAgregadosList = eppAgregadosList;

    // Cerrar modal
    cerrarModalAgregarEPPConfirmado();

    if (errores.length > 0) {
        // Si hubo errores pero tambien exitos parciales, recargar datos
        if (exitosos > 0) {
            await _recargarYMostrarEPPs(pedidoId);
            Swal.fire({
                icon: 'warning',
                title: 'Guardado parcial',
                html: `Se guardaron ${exitosos} EPP(s) correctamente.<br>Errores: ${errores.map(e => e.nombre + ': ' + e.error).join('<br>')}`,
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error al guardar',
                html: `No se pudieron guardar los EPP(s).<br>Errores: ${errores.map(e => e.nombre + ': ' + e.error).join('<br>')}`,
            });
        }
    } else {
        // Todos exitosos - recargar datos y mostrar la lista actualizada en tiempo real
        await _recargarYMostrarEPPs(pedidoId);
        Swal.fire({
            icon: 'success',
            title: 'EPP(s) agregado(s)',
            text: `Se agregaron ${exitosos} EPP(s) al pedido correctamente`,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
    }
}

/**
 * Recargar datos del pedido y re-abrir la vista de EPPs (actualizacion en tiempo real)
 */
async function _recargarYMostrarEPPs(pedidoId) {
    try {
        if (globalThis.datosEdicionPedido && typeof globalThis.abrirEditarEPP === 'function') {
            // Recargar datos del pedido via fetch
            const pedidoResponse = await fetch(`/api/pedidos/${pedidoId}`, {
                headers: { 'Accept': 'application/json' }
            });
            if (pedidoResponse.ok) {
                const pedidoData = await pedidoResponse.json();
                if (pedidoData.epps || pedidoData.epps_transformados) {
                    globalThis.datosEdicionPedido.epps = pedidoData.epps_transformados || pedidoData.epps || [];
                    console.log('[_recargarYMostrarEPPs] Datos del pedido actualizados con', globalThis.datosEdicionPedido.epps.length, 'EPPs');
                }
            } else {
                console.warn('[_recargarYMostrarEPPs] Error al recargar pedido:', pedidoResponse.status);
            }
            // Re-abrir la vista de EPPs del pedido (actualizacion en tiempo real)
            abrirEditarEPP();
        }
    } catch (e) {
        console.warn('[_recargarYMostrarEPPs] No se pudieron recargar los datos del pedido:', e);
    }
}

// Cerrar modal al hacer clic fuera
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modalAgregarEPP');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                cerrarModalAgregarEPP();
            }
        });
    }
});

// Exportar Funcion globalmente para que sea accesible desde otros scripts
globalThis.abrirModalAgregarEPP = abrirModalAgregarEPP;
globalThis.cerrarModalAgregarEPP = cerrarModalAgregarEPP;
globalThis.editarEPPAgregado = editarEPPAgregado;
globalThis.guardarEdicionEPP = guardarEdicionEPP;

// Funciones para manejar fotos de EPP
globalThis.fotosEPP = [];
globalThis.fotosEPPEliminadas = [];

function agregarFotoEPP() {
    document.getElementById('inputFotosEPP').click();
}

function manejarSubidaFotosEPP(input) {
    const archivos = input.files;
    const pedidoId = globalThis.pedidoIdActual || 31; // ID del pedido actual
    
    console.log(` [manejarSubidaFotosEPP] Seleccionados ${archivos.length} archivos para el pedido ${pedidoId}`);
    
    Array.from(archivos).forEach((archivo, index) => {
        const nombreArchivo = archivo.name;
        const extension = nombreArchivo.split('.').pop().toLowerCase();
        
        // Validar que sea una imagen
        if (!['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'jfif'].includes(extension)) {
            console.warn(`[manejarSubidaFotosEPP] Archivo no valido: ${nombreArchivo}`);
            return;
        }
        
        // Crear URL blob para la imagen
        const previewUrl = URL.createObjectURL(archivo);
        
        // Guardar referencia al archivo para mantener la blob URL valida
        _guardarArchivoEnCache(previewUrl, archivo);
        
        // Crear objeto de imagen con URL blob
        const imagen = {
            id: Date.now() + '_' + index,
            file: archivo, // Mantener referencia al archivo original
            previewUrl: previewUrl, // URL blob para mostrar
            nombre: nombreArchivo,
            extension: extension,
            pedido_epp_id: null, // Se asiganara al guardar
            ruta_original: null,
            ruta_webp: null,
            principal: 0,
            orden: 0
        };
        
        // Agregar a la lista temporal
        globalThis.fotosEPP.push(imagen);
        
        // Mostrar vista previa
        mostrarVistaPreviaFoto(imagen);
        
        console.log(` [manejarSubidaFotosEPP] Foto agregada: ${nombreArchivo} (${(archivo.size / 1024).toFixed(2)} KB)`);
    });
    
    // Actualizar contador de fotos
    const contadorFotos = document.getElementById('contadorFotosEPP');
    if (contadorFotos) contadorFotos.textContent = globalThis.fotosEPP.length;
    
    // Limpiar input para permitir seleccionar el mismo archivo nuevamente
    input.value = '';
}

function mostrarVistaPreviaFoto(imagen) {
    console.log('[mostrarVistaPreviaFoto] Iniciando con imagen:', imagen);
    
    const contenedor = document.getElementById('contenedorFotosEPP');
    console.log('[mostrarVistaPreviaFoto] Contenedor encontrado:', !!contenedor);
    
    // Ocultar mensaje inicial si esta visible
    const mensajeDragDrop = document.getElementById('mensajeDragDrop');
    console.log('[mostrarVistaPreviaFoto] Mensaje drag-drop encontrado:', !!mensajeDragDrop);
    
    if (mensajeDragDrop) {
        mensajeDragDrop.style.display = 'none';
        console.log('[mostrarVistaPreviaFoto] Mensaje drag-drop ocultado');
    }
    
    // Crear elemento de imagen con atributo data-foto-id
    const divImagen = document.createElement('div');
    divImagen.className = 'relative group foto-epp-item';
    divImagen.setAttribute('data-foto-id', imagen.id);
    console.log('[mostrarVistaPreviaFoto] Div creado con ID:', imagen.id);
    
    divImagen.innerHTML = `
        <div class="relative overflow-hidden rounded-lg border-2 border-gray-200">
            <img src="${imagen.previewUrl}" alt="Foto EPP" class="w-full h-32 object-cover"
                 onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTI4IiBoZWlnaHQ9IjEyOCIgdmlld0JveD0iMCAwIDEyOCAxMjgiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMjgiIGhlaWdodD0iMTI4IiBmaWxsPSIjRjRGNEY2Ii8+CjxwYXRoIGQ9Ik00OCA0OEg4MFY4MEg0OFY0OFoiIHN0cm9rZT0iIzlDQTNBIiBzdHJva2Utd2lkdGg9IjIiIGZpbGw9Im5vbmUiLz4KPHN2ZyB4PSI0OCIgeT0iNDgiIHdpZHRoPSIzMiIgaGVpZ2h0PSIzMiIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPgo8cGF0aCBkPSJNMTIgMkM2LjQ4IDIgMiA2LjQ4IDIgMTJTNi40OCAyMiAxMiAyMkMxNy41MiAyMiAyMiAxNy41MiAyMiAxMlMyMiA2LjQ4IDEyIDEyUzYuNDggMiAxMiAyWk0xMiA4QzEwLjkgOCA5IDguMSA5IDEwUzguMSAxMCAxMCAxMFMxMy45IDEwIDEzIDEwUzE0LjE4IDEwIDE0LjE4IDhTMTMuMSA2IDEyIDZaIiBmaWxsPSIjOUNDQTNBIi8+Cjwvc3ZnPgo8L3N2Zz4K'; this.style.opacity='0.5';"
                 title="Imagen no disponible">
            <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                <button type="button" onclick="eliminarFotoEPPEdicion('${imagen.id}', event)" class="bg-red-500 text-white p-2 rounded-full hover:bg-red-600 transition" title="Eliminar imagen">
                    <i class="material-symbols-rounded text-base">delete</i>
                </button>
            </div>
            <div class="absolute bottom-0 right-0 bg-blue-600 text-white text-xs px-2 py-1 rounded-tl">
                ${imagen.nombre}
            </div>
        </div>
    `;
    
    console.log('[mostrarVistaPreviaFoto] HTML creado, agregando al contenedor...');
    contenedor.appendChild(divImagen);
    console.log('[mostrarVistaPreviaFoto] Imagen agregada al DOM. Total elementos en contenedor:', contenedor.children.length);
}


/**
 * Eliminar una foto del modo edicion (contenedorFotosEPP / globalThis.fotosEPP)
 */
function eliminarFotoEPPEdicion(fotoId, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    console.log('[eliminarFotoEPPEdicion] Eliminando foto con ID:', fotoId);
    
    // Eliminar del array globalThis.fotosEPP
    if (globalThis.fotosEPP && Array.isArray(globalThis.fotosEPP)) {
        const idx = globalThis.fotosEPP.findIndex(f => String(f.id) === String(fotoId));
        if (idx !== -1) {
            const foto = globalThis.fotosEPP[idx];
            // Liberar blob URL si existe
            if (foto.previewUrl && foto.previewUrl.startsWith('blob:')) {
                URL.revokeObjectURL(foto.previewUrl);
            }
            globalThis.fotosEPP.splice(idx, 1);
            console.log('[eliminarFotoEPPEdicion] Foto eliminada del array. Restantes:', globalThis.fotosEPP.length);
        }
    }
    
    // Eliminar del DOM
    const contenedor = document.getElementById('contenedorFotosEPP');
    if (contenedor) {
        const elemento = contenedor.querySelector(`[data-foto-id="${fotoId}"]`);
        if (elemento) {
            elemento.remove();
            console.log('[eliminarFotoEPPEdicion] Elemento DOM eliminado');
        }
    }
    
    // Mostrar mensaje drag-drop si no quedan fotos
    if (!globalThis.fotosEPP || globalThis.fotosEPP.length === 0) {
        const mensajeDragDrop = document.getElementById('mensajeDragDrop');
        if (mensajeDragDrop) {
            mensajeDragDrop.style.display = 'flex';
        }
    }
    
    // Actualizar contador
    const contadorFotos = document.getElementById('contadorFotosEPP');
    if (contadorFotos) {
        contadorFotos.textContent = globalThis.fotosEPP ? globalThis.fotosEPP.length : 0;
    }
}
globalThis.eliminarFotoEPPEdicion = eliminarFotoEPPEdicion;

// Funciones para Drag and Drop
function handleDragOverEPP(event) {
    event.preventDefault();
    event.stopPropagation();
    
    const contenedor = document.getElementById('contenedorFotosEPP');
    contenedor.classList.add('border-blue-400', 'bg-blue-50');
    contenedor.classList.remove('border-gray-300');
    
    // Ocultar mensaje inicial si hay imagenes
    if (globalThis.fotosEPP.length > 0) {
        const mensajeDragDrop = document.getElementById('mensajeDragDrop');
        if (mensajeDragDrop) {
            mensajeDragDrop.style.display = 'none';
        }
    }
}

function handleDragLeaveEPP(event) {
    event.preventDefault();
    event.stopPropagation();
    
    const contenedor = document.getElementById('contenedorFotosEPP');
    contenedor.classList.remove('border-blue-400', 'bg-blue-50');
    contenedor.classList.add('border-gray-300');
    
    // Mostrar mensaje inicial si no hay imagenes
    if (globalThis.fotosEPP.length === 0) {
        const mensajeDragDrop = document.getElementById('mensajeDragDrop');
        if (mensajeDragDrop) {
            mensajeDragDrop.style.display = 'flex';
        }
    }
}

function handleDropEPP(event) {
    event.preventDefault();
    event.stopPropagation();
    
    const contenedor = document.getElementById('contenedorFotosEPP');
    if (contenedor) {
        contenedor.classList.remove('border-blue-400', 'bg-blue-50');
        contenedor.classList.add('border-gray-300');
    }
    
    const archivos = event.dataTransfer.files;
    const pedidoId = globalThis.pedidoIdActual || 31;
    
    console.log(` [handleDropEPP] Se arrastraron ${archivos.length} archivos para el pedido ${pedidoId}`);
    
    // Ocultar mensaje inicial
    const mensajeDragDrop = document.getElementById('mensajeDragDrop');
    if (mensajeDragDrop) {
        mensajeDragDrop.style.display = 'none';
    }
    
    // Procesar cada archivo arrastrado
    Array.from(archivos).forEach((archivo, index) => {
        const nombreArchivo = archivo.name;
        const extension = nombreArchivo.split('.').pop().toLowerCase();
        
        // Validar que sea una imagen
        if (!['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'jfif'].includes(extension)) {
            console.warn(`[handleDropEPP] Archivo no valido: ${nombreArchivo}`);
            return;
        }
        
        // Crear URL blob para la imagen
        const previewUrl = URL.createObjectURL(archivo);
        
        // Crear objeto de imagen con URL blob
        const imagen = {
            id: Date.now() + '_' + index + '_drop',
            file: archivo, // Mantener referencia al archivo original
            previewUrl: previewUrl, // URL blob para mostrar
            nombre: nombreArchivo,
            extension: extension,
            pedido_epp_id: null,
            ruta_original: null,
            ruta_webp: null,
            principal: 0,
            orden: 0
        };
        
        // Agregar a la lista temporal
        globalThis.fotosEPP.push(imagen);
        
        // Mostrar vista previa
        mostrarVistaPreviaFoto(imagen);
        
        console.log(`[handleDropEPP] Foto arrastrada: ${nombreArchivo} (${(archivo.size / 1024).toFixed(2)} KB)`);
    });
    
    // Actualizar contador de fotos
    const contadorFotosD = document.getElementById('contadorFotosEPP');
    if (contadorFotosD) contadorFotosD.textContent = globalThis.fotosEPP.length;
}
