<!-- Modal para Editar un EPP Individual -->
<div id="modalEditarEPP" class="fixed inset-0 bg-black/50 flex items-center justify-center hidden z-[9999999]">
    <div class="bg-white rounded-lg w-full max-w-2xl shadow-2xl overflow-hidden flex flex-col max-h-[95vh]">
        
        <!-- Header -->
        <div class="bg-blue-600 px-6 py-4 flex justify-between items-center flex-shrink-0">
            <h2 class="text-white text-lg font-bold" id="modalEditarEPPTitulo">Editar EPP</h2>
            <button onclick="cerrarModalEditarEPP()" class="text-white hover:bg-blue-700 p-1 rounded transition">
                <i class="material-symbols-rounded">close</i>
            </button>
        </div>

        <!-- Body con scroll -->
        <div class="p-6 space-y-6 overflow-y-auto flex-1">
            
            <!-- Nombre del EPP con Buscador -->
            <div>
                <label for="modalEditarEPPBuscador" class="block text-sm font-medium text-gray-700 mb-2">Nombre del EPP</label>
                <div class="relative">
                    <input 
                        type="text" 
                        id="modalEditarEPPBuscador" 
                        placeholder="Buscar EPP..." 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-200"
                        onkeyup="filtrarEPPsEnEdicion(this.value)"
                        autocomplete="off"
                    >
                    <div id="modalEditarEPPDropdown" class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-40 overflow-y-auto hidden z-50"></div>
                </div>
                <!-- Mostrar EPP seleccionado -->
                <p id="modalEditarEPPNombreSeleccionado" class="text-xs text-gray-600 mt-2">EPP: <span id="modalEditarEPPNombre" class="font-semibold text-gray-900">-</span></p>
            </div>

            <!-- Cantidad -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="modalEditarEPPCantidad" class="block text-sm font-medium text-gray-700 mb-2">Cantidad</label>
                    <input 
                        type="number" 
                        id="modalEditarEPPCantidad" 
                        min="1" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-200"
                    >
                </div>
                <div>
                    <label for="modalEditarEPPObservaciones" class="block text-sm font-medium text-gray-700 mb-2">Observaciones</label>
                    <input 
                        type="text" 
                        id="modalEditarEPPObservaciones" 
                        placeholder="Escribe observaciones..." 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-200"
                    >
                </div>
            </div>

            <!-- Sección de Fotos -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <label class="text-sm font-semibold text-gray-900">Fotos del EPP</label>
                    <span class="text-xs text-gray-500">(<span id="modalEditarEPPFotosCount">0</span> foto/s)</span>
                </div>

                <!-- Zona de carga -->
                <div class="space-y-3">
                    <!-- Zona Drag & Drop -->
                    <div id="modalEditarEPPFotoZona" 
                        class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:bg-gray-50 transition"
                        ondrop="manejarDropEnModalEditar(event)" 
                        ondragover="event.preventDefault(); event.currentTarget.classList.add('bg-blue-50', 'border-blue-400')"
                        ondragleave="event.currentTarget.classList.remove('bg-blue-50', 'border-blue-400')">
                        
                        <div class="flex flex-col items-center gap-2">
                            <i class="material-symbols-rounded text-3xl text-gray-400">cloud_upload</i>
                            <p class="text-sm text-gray-600">Arrastra imágenes aquí</p>
                            <p class="text-xs text-gray-500">También puedes usar Ctrl+V o hacer clic en "Agregar Foto"</p>
                        </div>
                    </div>

                    <!-- Botón para agregar fotos -->
                    <button 
                        type="button"
                        onclick="document.getElementById('modalEditarEPPInputFotos').click()"
                        class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition text-sm flex items-center justify-center gap-2">
                        <i class="material-symbols-rounded" style="font-size: 18px;">add_photo_alternate</i>
                        Agregar Fotos
                    </button>
                </div>

                <!-- Input file DEBE estar fuera del contenedor que maneja onclick -->
                <input 
                    type="file" 
                    id="modalEditarEPPInputFotos" 
                    multiple 
                    accept="image/*" 
                    style="display: none !important;"
                    onchange="manejarSeleccionFotosEnModalEditar(event)"
                >

                <!-- Galería de fotos -->
                <div id="modalEditarEPPFotosGaleria" class="grid grid-cols-4 gap-2 mt-4">
                    <!-- Las fotos se agregan dinámicamente aquí -->
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3 flex-shrink-0">
            <button 
                onclick="cerrarModalEditarEPP()" 
                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-100 transition text-sm"
            >
                Cancelar
            </button>
            <button 
                id="btnGuardarEditarEPP"
                onclick="guardarEdicionEnModalEditarEPP()" 
                class="px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition text-sm flex items-center gap-2"
            >
                <i class="material-symbols-rounded" style="font-size: 18px;">save</i>
                Guardar Cambios
            </button>
        </div>
    </div>
</div>

<script>
/**
 * Estado global para edición de EPP individual
 */
let eppEnEdicionIndividual = null;
let fotosEnEdicionIndividual = [];
let eppsDisponiblesParaEdicion = [];
let indiceEPPEnEdicion = -1;  // Guardar el índice para búsqueda rápida
let tarjetaEppIdEnEdicion = null;  // Guardar el ID de la tarjeta visual para actualizar después

/**
 * Abrir modal para editar un EPP individual
 */
function abrirModalEditarEPP(eppData) {
    console.log('[abrirModalEditarEPP] Abriendo modal con EPP:', eppData);
    
    // Guardar ID de la tarjeta visual
    tarjetaEppIdEnEdicion = eppData.tarjetaId;
    
    // Normalizar el eppData para asegurar que tiene un ID válido
    const eppId = eppData.id || eppData.epp_id || eppData.data_epp_original_id;
    console.log('[abrirModalEditarEPP] ID normalizado:', eppId, 'Tarjeta ID:', tarjetaEppIdEnEdicion);
    
    // Buscar el índice del EPP en eppAgregadosList con múltiples criterios
    indiceEPPEnEdicion = eppAgregadosList.findIndex(e => {
        // Criterio 1: Coincidir ID directo
        if (e.id === eppId || String(e.id) === String(eppId)) return true;
        
        // Criterio 2: Coincidir por nombre si está disponible
        if (eppData.nombre && (e.nombre === eppData.nombre || e.nombre_completo === eppData.nombre)) return true;
        if (eppData.nombre_completo && e.nombre_completo === eppData.nombre_completo) return true;
        
        // Criterio 3: Coincidir por nombre_epp (como aparece en las tarjetas)
        if (eppData.nombre_epp && e.nombre_epp === eppData.nombre_epp) return true;
        
        return false;
    });
    
    console.log('[abrirModalEditarEPP] Índice encontrado:', indiceEPPEnEdicion, 'Total EPPs en lista:', eppAgregadosList.length);
    
    if (indiceEPPEnEdicion !== -1) {
        // Usar directamente el objeto de eppAgregadosList
        eppEnEdicionIndividual = { ...eppAgregadosList[indiceEPPEnEdicion] };
        fotosEnEdicionIndividual = eppAgregadosList[indiceEPPEnEdicion].imagenes ? [...eppAgregadosList[indiceEPPEnEdicion].imagenes] : [];
        
        console.log('[abrirModalEditarEPP] EPP encontrado en lista:', eppEnEdicionIndividual);
    } else {
        // Fallback: crear uno nuevo con los datos recibidos
        console.warn('[abrirModalEditarEPP] EPP no encontrado en lista, usando datos recibidos');
        eppEnEdicionIndividual = { 
            id: eppId,
            nombre: eppData.nombre || eppData.nombre_epp || eppData.nombre_completo || 'EPP',
            nombre_completo: eppData.nombre_completo || eppData.nombre_epp || eppData.nombre || 'EPP',
            cantidad: eppData.cantidad || 1,
            observaciones: eppData.observaciones || '-',
            imagenes: eppData.imagenes || []
        };
        fotosEnEdicionIndividual = eppData.imagenes ? [...eppData.imagenes] : [];
    }
    
    // Llenar formulario
    document.getElementById('modalEditarEPPBuscador').value = eppEnEdicionIndividual.nombre_completo || eppEnEdicionIndividual.nombre || 'EPP';
    document.getElementById('modalEditarEPPNombre').textContent = eppEnEdicionIndividual.nombre_completo || eppEnEdicionIndividual.nombre || 'EPP';
    document.getElementById('modalEditarEPPCantidad').value = eppEnEdicionIndividual.cantidad || 1;
    document.getElementById('modalEditarEPPObservaciones').value = eppEnEdicionIndividual.observaciones || '-';
    
    // Mostrar fotos existentes
    mostrarFotosEnModalEditar();
    
    // Mostrar modal
    document.getElementById('modalEditarEPP').classList.remove('hidden');
    
    console.log('[abrirModalEditarEPP] Modal abierto, EPP en edición:', eppEnEdicionIndividual, 'Índice:', indiceEPPEnEdicion, 'Tarjeta ID:', tarjetaEppIdEnEdicion);
}

/**
 * Filtrar EPPs en el buscador de edición
 */
async function filtrarEPPsEnEdicion(valor) {
    const dropdown = document.getElementById('modalEditarEPPDropdown');
    const busqueda = valor.toLowerCase().trim();
    
    if (!busqueda) {
        dropdown.classList.add('hidden');
        return;
    }
    
    try {
        // Usar el mismo endpoint que modal-agregar-epp
        const response = await fetch(`/api/epp/gestion?q=${encodeURIComponent(busqueda)}&per_page=100`);
        const result = await response.json();
        const epps = (result.success && result.data) ? result.data : [];
        
        if (epps.length === 0) {
            dropdown.innerHTML = '<div class="p-3 text-gray-500 text-sm">No hay EPPs disponibles</div>';
            dropdown.classList.remove('hidden');
            return;
        }
        
        dropdown.innerHTML = epps.map((epp) => {
            const nombreEscapado = (epp.nombre_completo || epp.nombre || '').replace(/'/g, "\\'");
            const marcaEscapada = (epp.marca || '').replace(/'/g, "\\'");
            
            return `
                <div 
                    class="px-3 py-3 hover:bg-blue-100 cursor-pointer border-b border-gray-200 last:border-b-0 text-sm transition"
                    onclick="seleccionarEPPEnEdicion({ 
                        id: ${epp.id}, 
                        nombre: '${nombreEscapado}',
                        nombre_completo: '${nombreEscapado}'
                    })"
                >
                    <div class="font-medium text-gray-900">${epp.nombre_completo || epp.nombre}</div>
                    ${epp.marca ? `<div class="text-xs text-gray-500">${epp.marca}</div>` : ''}
                </div>
            `;
        }).join('');
        
        dropdown.classList.remove('hidden');
        console.log('[filtrarEPPsEnEdicion] Resultados encontrados:', epps.length);
        
    } catch (error) {
        console.error('[filtrarEPPsEnEdicion] Error:', error);
        dropdown.innerHTML = '<div class="p-3 text-red-500 text-sm">Error al buscar</div>';
        dropdown.classList.remove('hidden');
    }
}

/**
 * Seleccionar un EPP diferente en la edición
 */
function seleccionarEPPEnEdicion(epp) {
    console.log('[seleccionarEPPEnEdicion] EPP seleccionado:', epp);
    
    // Actualizar EPP en edición
    eppEnEdicionIndividual.id = epp.id;
    eppEnEdicionIndividual.epp_id = epp.id;
    eppEnEdicionIndividual.nombre = epp.nombre;
    eppEnEdicionIndividual.nombre_completo = epp.nombre_completo || epp.nombre;
    
    // Resetear índice porque cambió el EPP
    indiceEPPEnEdicion = -1;
    tarjetaEppIdEnEdicion = null;  // Resetear tarjeta porque es otro EPP
    
    // Actualizar UI
    document.getElementById('modalEditarEPPBuscador').value = epp.nombre_completo || epp.nombre;
    document.getElementById('modalEditarEPPNombre').textContent = epp.nombre_completo || epp.nombre;
    
    // Ocultar dropdown
    document.getElementById('modalEditarEPPDropdown').classList.add('hidden');
    
    // Limpiar fotos al cambiar EPP
    fotosEnEdicionIndividual = [];
    mostrarFotosEnModalEditar();
}

/**
 * Cerrar modal de edición
 */
function cerrarModalEditarEPP() {
    console.log('[cerrarModalEditarEPP] Cerrando modal');
    document.getElementById('modalEditarEPP').classList.add('hidden');
    document.getElementById('modalEditarEPPDropdown').classList.add('hidden');
    eppEnEdicionIndividual = null;
    fotosEnEdicionIndividual = [];
    indiceEPPEnEdicion = -1;
    tarjetaEppIdEnEdicion = null;
}

/**
 * Mostrar fotos en la galería del modal
 */
function mostrarFotosEnModalEditar() {
    const galeria = document.getElementById('modalEditarEPPFotosGaleria');
    galeria.innerHTML = '';
    
    fotosEnEdicionIndividual.forEach((foto, index) => {
        const div = document.createElement('div');
        div.className = 'relative group rounded-lg overflow-hidden bg-gray-100 aspect-square';
        
        const fotoUrl = foto.previewUrl || foto.url || (foto.file ? URL.createObjectURL(foto.file) : '');
        
        div.innerHTML = `
            <img src="${fotoUrl}" alt="Foto ${index + 1}" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition flex items-center justify-center">
                <button 
                    type="button"
                    onclick="eliminarFotoEnModalEditar(${index})"
                    class="opacity-0 group-hover:opacity-100 bg-red-500 text-white p-2 rounded-full hover:bg-red-600 transition"
                    title="Eliminar"
                >
                    <i class="material-symbols-rounded" style="font-size: 18px;">delete</i>
                </button>
            </div>
        `;
        galeria.appendChild(div);
    });
    
    document.getElementById('modalEditarEPPFotosCount').textContent = fotosEnEdicionIndividual.length;
}

/**
 * Manejar selección de fotos en el modal
 */
function manejarSeleccionFotosEnModalEditar(event) {
    const files = event.target.files;
    
    console.log('[manejarSeleccionFotosEnModalEditar] Archivos seleccionados:', files.length);
    
    Array.from(files).forEach((file, i) => {
        if (file.type.startsWith('image/')) {
            const blobUrl = URL.createObjectURL(file);
            fotosEnEdicionIndividual.push({
                file: file,
                previewUrl: blobUrl,
                nombre: file.name
            });
            console.log(`[manejarSeleccionFotosEnModalEditar] Foto ${i + 1} agregada: ${file.name}`);
        }
    });
    
    mostrarFotosEnModalEditar();
    console.log('[manejarSeleccionFotosEnModalEditar] Total de fotos:', fotosEnEdicionIndividual.length);
    
    // Limpiar input para permitir seleccionar el mismo archivo nuevamente
    event.target.value = '';
}

/**
 * Manejar drag & drop en el modal
 */
function manejarDropEnModalEditar(event) {
    event.preventDefault();
    event.stopPropagation();
    
    const zona = event.currentTarget;
    zona.classList.remove('bg-blue-50', 'border-blue-400');
    
    const files = event.dataTransfer.files;
    console.log('[manejarDropEnModalEditar] Archivos soltados:', files.length);
    
    Array.from(files).forEach((file, i) => {
        if (file.type.startsWith('image/')) {
            const blobUrl = URL.createObjectURL(file);
            fotosEnEdicionIndividual.push({
                file: file,
                previewUrl: blobUrl,
                nombre: file.name
            });
            console.log(`[manejarDropEnModalEditar] Foto ${i + 1} agregada: ${file.name}`);
        }
    });
    
    mostrarFotosEnModalEditar();
    console.log('[manejarDropEnModalEditar] Total de fotos:', fotosEnEdicionIndividual.length);
}

/**
 * Eliminar una foto en el modal
 */
function eliminarFotoEnModalEditar(index) {
    const foto = fotosEnEdicionIndividual[index];
    console.log('[eliminarFotoEnModalEditar] Eliminando foto en índice:', index, '- Nombre:', foto.nombre);
    
    if (foto && foto.previewUrl) {
        URL.revokeObjectURL(foto.previewUrl);
    }
    
    fotosEnEdicionIndividual.splice(index, 1);
    mostrarFotosEnModalEditar();
    console.log('[eliminarFotoEnModalEditar] Foto eliminada, total restantes:', fotosEnEdicionIndividual.length);
}

/**
 * Soporte para Ctrl+V en el modal
 */
document.addEventListener('paste', function(e) {
    const modal = document.getElementById('modalEditarEPP');
    if (!modal || modal.classList.contains('hidden')) return;
    
    const items = e.clipboardData.items;
    if (!items) return;
    
    console.log('[paste] Imagen pegada en modal editar EPP');
    
    for (let item of items) {
        if (item.type.startsWith('image/')) {
            const file = item.getAsFile();
            const blobUrl = URL.createObjectURL(file);
            fotosEnEdicionIndividual.push({
                file: file,
                previewUrl: blobUrl,
                nombre: file.name
            });
        }
    }
    
    mostrarFotosEnModalEditar();
});

/**
 * Cerrar dropdown al hacer click fuera
 */
document.addEventListener('click', function(e) {
    const buscador = document.getElementById('modalEditarEPPBuscador');
    const dropdown = document.getElementById('modalEditarEPPDropdown');
    
    if (buscador && !buscador.contains(e.target) && dropdown && !dropdown.contains(e.target)) {
        dropdown.classList.add('hidden');
    }
});

/**
 * Guardar cambios de edición individual
 */
function guardarEdicionEnModalEditarEPP() {
    const cantidad = parseInt(document.getElementById('modalEditarEPPCantidad').value) || 1;
    const observaciones = document.getElementById('modalEditarEPPObservaciones').value || '-';
    
    if (!eppEnEdicionIndividual) {
        console.error('[guardarEdicionEnModalEditarEPP] No hay EPP en edición');
        return;
    }
    
    console.log('[guardarEdicionEnModalEditarEPP] Iniciando guardado para EPP:', {
        id: eppEnEdicionIndividual.id,
        nombre: eppEnEdicionIndividual.nombre,
        indiceGuardado: indiceEPPEnEdicion,
        tarjetaId: tarjetaEppIdEnEdicion,
        totalEPPsEnLista: eppAgregadosList.length
    });
    
    // Usar el índice guardado si es válido
    let index = indiceEPPEnEdicion;
    
    // Si el índice no es válido o está fuera de rango, buscar nuevamente
    if (index === -1 || index === undefined || index >= eppAgregadosList.length) {
        console.log('[guardarEdicionEnModalEditarEPP] Índice inválido o fuera de rango, buscando nuevamente...');
        index = eppAgregadosList.findIndex(e => {
            // Criterio 1: Coincidir ID
            if (e.id === eppEnEdicionIndividual.id || String(e.id) === String(eppEnEdicionIndividual.id)) return true;
            
            // Criterio 2: Coincidir por nombre
            if (e.nombre === eppEnEdicionIndividual.nombre) return true;
            if (e.nombre_completo === eppEnEdicionIndividual.nombre_completo) return true;
            
            return false;
        });
        console.log('[guardarEdicionEnModalEditarEPP] Nuevo índice encontrado:', index);
    }
    
    // Actualizar datos en eppAgregadosList
    if (index !== -1 && index < eppAgregadosList.length) {
        const eppAntes = { ...eppAgregadosList[index] };
        
        eppAgregadosList[index].cantidad = cantidad;
        eppAgregadosList[index].observaciones = observaciones;
        eppAgregadosList[index].imagenes = fotosEnEdicionIndividual;
        eppAgregadosList[index].nombre = eppEnEdicionIndividual.nombre;
        eppAgregadosList[index].nombre_epp = eppEnEdicionIndividual.nombre_completo || eppEnEdicionIndividual.nombre;
        eppAgregadosList[index].nombre_completo = eppEnEdicionIndividual.nombre_completo || eppEnEdicionIndividual.nombre;
        
        console.log('[guardarEdicionEnModalEditarEPP] ✅ EPP actualizado en eppAgregadosList, índice:', index, {
            antes: eppAntes,
            ahora: eppAgregadosList[index]
        });
        
        // Actualizar tarjeta visual si existe el manager y el tarjetaId
        if (tarjetaEppIdEnEdicion && window.eppManager && typeof window.eppManager.actualizarItem === 'function') {
            window.eppManager.actualizarItem(tarjetaEppIdEnEdicion, {
                nombre: eppEnEdicionIndividual.nombre_completo || eppEnEdicionIndividual.nombre,
                cantidad: cantidad,
                observaciones: observaciones,
                imagenes: fotosEnEdicionIndividual
            });
            console.log('[guardarEdicionEnModalEditarEPP] ✅ Tarjeta visual actualizada:', tarjetaEppIdEnEdicion);
        } else {
            console.warn('[guardarEdicionEnModalEditarEPP] ⚠️ No se pudo actualizar la tarjeta visual', {
                tarjetaId: tarjetaEppIdEnEdicion,
                managerDisponible: !!window.eppManager,
                actualizarItemDisponible: window.eppManager ? typeof window.eppManager.actualizarItem : 'N/A'
            });
        }
        
        // Cerrar modal
        cerrarModalEditarEPP();
        
        // Mostrar confirmación
        alert('✅ Cambios guardados correctamente');
    } else {
        console.error('[guardarEdicionEnModalEditarEPP] ❌ No se encontró el EPP en la lista', {
            indiceCalculado: index,
            eppEnEdicion: eppEnEdicionIndividual,
            eppAgregadosList: eppAgregadosList
        });
        alert('❌ Error: No se pudo encontrar el EPP para guardar los cambios. Intenta nuevamente.');
    }
}

// Exportar funciones globales
window.abrirModalEditarEPP = abrirModalEditarEPP;
window.cerrarModalEditarEPP = cerrarModalEditarEPP;
window.guardarEdicionEnModalEditarEPP = guardarEdicionEnModalEditarEPP;
window.manejarSeleccionFotosEnModalEditar = manejarSeleccionFotosEnModalEditar;
window.manejarDropEnModalEditar = manejarDropEnModalEditar;
window.eliminarFotoEnModalEditar = eliminarFotoEnModalEditar;
window.filtrarEPPsEnEdicion = filtrarEPPsEnEdicion;
window.seleccionarEPPEnEdicion = seleccionarEPPEnEdicion;
</script>
