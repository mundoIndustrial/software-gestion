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
            
            <!-- Nombre del EPP - READONLY (No editable) -->
            <div>
                <label for="modalEditarEPPBuscador" class="block text-sm font-medium text-gray-700 mb-2">Nombre del EPP</label>
                <div class="relative">
                    <input 
                        type="text" 
                        id="modalEditarEPPBuscador" 
                        placeholder="EPP" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-700 cursor-not-allowed"
                        readonly
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
                        class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:bg-gray-50 transition">
                        
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
    
    // PRIMERO: Buscar en window.itemsPedido (donde están realmente los EPPs agregados)
    let eppEncontrado = null;
    indiceEPPEnEdicion = -1;
    
    if (window.itemsPedido && Array.isArray(window.itemsPedido)) {
        console.log('[abrirModalEditarEPP] Buscando en window.itemsPedido...');
        
        indiceEPPEnEdicion = window.itemsPedido.findIndex(e => {
            // Criterio 1: Coincidir ID directo
            if (e.epp_id === eppId || e.epp_id === parseInt(eppId)) return true;
            if (e.id === eppId || String(e.id) === String(eppId)) return true;
            
            // Criterio 2: Coincidir por nombre_epp (como aparece en las tarjetas)
            if (eppData.nombre && (e.nombre_epp === eppData.nombre || e.nombre_epp === eppData.nombre_completo)) return true;
            if (eppData.nombre_epp && e.nombre_epp === eppData.nombre_epp) return true;
            
            // Criterio 3: Coincidir por tarjeta ID (Si está disponible)
            if (tarjetaEppIdEnEdicion && e.tarjetaId === tarjetaEppIdEnEdicion) return true;
            
            return false;
        });
        
        if (indiceEPPEnEdicion !== -1) {
            eppEncontrado = { ...window.itemsPedido[indiceEPPEnEdicion] };
            console.log('[abrirModalEditarEPP] ✅ EPP encontrado en window.itemsPedido:', eppEncontrado);
        }
    }
    
    // FALLBACK: Si no encontró en window.itemsPedido, buscar en eppAgregadosList
    if (indiceEPPEnEdicion === -1 && eppAgregadosList.length > 0) {
        console.log('[abrirModalEditarEPP] EPP no encontrado en window.itemsPedido, buscando en eppAgregadosList...');
        
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
        
        if (indiceEPPEnEdicion !== -1) {
            eppEncontrado = { ...eppAgregadosList[indiceEPPEnEdicion] };
            console.log('[abrirModalEditarEPP] ✅ EPP encontrado en eppAgregadosList:', eppEncontrado);
        }
    }
    
    console.log('[abrirModalEditarEPP] Búsqueda completada - Índice encontrado:', indiceEPPEnEdicion);
    
    if (eppEncontrado) {
        // Usar directamente el objeto encontrado
        eppEnEdicionIndividual = eppEncontrado;
        
        // IMPORTANTE: Asegurar que siempre tiene ambas propiedades para búsqueda
        // Si no tiene 'id', usar 'epp_id'
        if (!eppEnEdicionIndividual.id && eppEnEdicionIndividual.epp_id) {
            eppEnEdicionIndividual.id = eppEnEdicionIndividual.epp_id;
        }
        // Si tiene 'id' pero no 'epp_id', asegurar que epp_id esté disponible
        if (!eppEnEdicionIndividual.epp_id && eppEnEdicionIndividual.id) {
            eppEnEdicionIndividual.epp_id = eppEnEdicionIndividual.id;
        }
        
        // Sincronizar nombres
        if (!eppEnEdicionIndividual.nombre && eppEnEdicionIndividual.nombre_epp) {
            eppEnEdicionIndividual.nombre = eppEnEdicionIndividual.nombre_epp;
        }
        if (!eppEnEdicionIndividual.nombre_completo && eppEnEdicionIndividual.nombre_epp) {
            eppEnEdicionIndividual.nombre_completo = eppEnEdicionIndividual.nombre_epp;
        }
        
        // Cargar imágenes con validación
        fotosEnEdicionIndividual = [];
        if (eppEncontrado.imagenes && Array.isArray(eppEncontrado.imagenes)) {
            // Filtrar solo imágenes válidas (descartar blob URLs revocadas)
            fotosEnEdicionIndividual = eppEncontrado.imagenes.filter(img => {
                // Validar que la imagen tiene una URL válida
                if (!img) return false;
                const hasValidUrl = img.previewUrl || img.url || img.src || img.ruta_webp || img.ruta_web || img.ruta_original;
                return !!hasValidUrl;
            });
            console.log('[abrirModalEditarEPP] Imágenes cargadas:', fotosEnEdicionIndividual.length, '/', eppEncontrado.imagenes.length);
        }
        
        console.log('[abrirModalEditarEPP] EPP cargado para edición:', eppEnEdicionIndividual);
    } else {
        // Fallback: crear uno nuevo con los datos recibidos
        console.warn('[abrirModalEditarEPP] EPP no encontrado en ninguna lista, usando datos recibidos como fallback');
        eppEnEdicionIndividual = { 
            id: eppId,
            epp_id: eppId,
            nombre: eppData.nombre || eppData.nombre_epp || eppData.nombre_completo || 'EPP',
            nombre_completo: eppData.nombre_completo || eppData.nombre_epp || eppData.nombre || 'EPP',
            nombre_epp: eppData.nombre_epp || eppData.nombre_completo || eppData.nombre || 'EPP',
            cantidad: eppData.cantidad || 1,
            observaciones: eppData.observaciones || '-',
            imagenes: eppData.imagenes || []
        };
        
        // Cargar imágenes del fallback con validación
        fotosEnEdicionIndividual = [];
        if (eppData.imagenes && Array.isArray(eppData.imagenes)) {
            fotosEnEdicionIndividual = eppData.imagenes.filter(img => {
                if (!img) return false;
                const hasValidUrl = img.previewUrl || img.url || img.src || img.ruta_webp || img.ruta_web || img.ruta_original;
                return !!hasValidUrl;
            });
        }
    }
    
    // Llenar formulario
    document.getElementById('modalEditarEPPBuscador').value = eppEnEdicionIndividual.nombre_completo || eppEnEdicionIndividual.nombre_epp || eppEnEdicionIndividual.nombre || 'EPP';
    document.getElementById('modalEditarEPPNombre').textContent = eppEnEdicionIndividual.nombre_completo || eppEnEdicionIndividual.nombre_epp || eppEnEdicionIndividual.nombre || 'EPP';
    document.getElementById('modalEditarEPPCantidad').value = eppEnEdicionIndividual.cantidad || 1;
    document.getElementById('modalEditarEPPObservaciones').value = eppEnEdicionIndividual.observaciones || '-';
    
    // Mostrar fotos existentes
    mostrarFotosEnModalEditar();
    
    // Mostrar modal
    document.getElementById('modalEditarEPP').classList.remove('hidden');
    
    // Configurar zona de fotos para recibir paste events
    const fotoZona = document.getElementById('modalEditarEPPFotoZona');
    if (fotoZona) {
        // Hacer que la zona sea focuseable (para paste events)
        fotoZona.setAttribute('tabindex', '0');
        // NO hacer focus automático - deja que el usuario dicte dónde escribir
        
        // Agregar listener de paste DIRECTAMENTE en la zona
        if (!fotoZona.__hasPasteListener) {
            function handlePasteInFotoZona(e) {
                // Solo procesar si el modal está visible
                const modal = document.getElementById('modalEditarEPP');
                if (!modal || modal.classList.contains('hidden')) return;
                
                e.preventDefault();
                const items = e.clipboardData?.items;
                if (!items) return;
                
                let pegadasOk = 0;
                for (let item of items) {
                    if (item.type.startsWith('image/')) {
                        const file = item.getAsFile();
                        if (!file) continue;
                        
                        const previewUrl = URL.createObjectURL(file);
                        
                        const imagen = {
                            id: Date.now() + '_paste_' + Math.random().toString(36).substr(2, 5),
                            file: file,
                            previewUrl: previewUrl,
                            nombre: file.name || 'pegado_' + Date.now() + '.png',
                            extension: (file.name || '').split('.').pop().toLowerCase() || 'png',
                            tamaño: file.size,
                            pedido_epp_id: null,
                            ruta_original: null,
                            ruta_webp: null,
                            principal: 0,
                            orden: 0
                        };
                        
                        if (!fotosEnEdicionIndividual) fotosEnEdicionIndividual = [];
                        fotosEnEdicionIndividual.push(imagen);
                        pegadasOk++;
                    }
                }
                
                if (pegadasOk > 0) {
                    console.log('[modalEditarEPP] ✅ Imágenes pegadas via Ctrl+V:', pegadasOk);
                    mostrarFotosEnModalEditar();
                }
            }
            
            // Agregar listener en el DOCUMENT para capturar paste global cuando modal esté abierto
            document.addEventListener('paste', handlePasteInFotoZona);
            fotoZona.__hasPasteListener = true;
            fotoZona.__pasteHandler = handlePasteInFotoZona;
            console.log('[abrirModalEditarEPP] Paste listener agregado globalmente (activo cuando modal visible)');
        }
        
        // Agregar listeners de drag & drop
        if (!fotoZona.__hasDragDropListener) {
            fotoZona.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.stopPropagation();
                fotoZona.classList.add('bg-blue-50', 'border-blue-400');
            });
            
            fotoZona.addEventListener('dragleave', (e) => {
                e.preventDefault();
                e.stopPropagation();
                fotoZona.classList.remove('bg-blue-50', 'border-blue-400');
            });
            
            fotoZona.addEventListener('drop', (e) => {
                e.preventDefault();
                e.stopPropagation();
                fotoZona.classList.remove('bg-blue-50', 'border-blue-400');
                manejarDropEnModalEditar(e);
            });
            
            fotoZona.__hasDragDropListener = true;
            console.log('[abrirModalEditarEPP] Drag & drop listeners agregados');
        }
    }
    
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
        const response = await fetch(`/api/epp?q=${encodeURIComponent(busqueda)}&per_page=100`);
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
    
    // Buscar el EPP en window.itemsPedido - DEBE estar en el pedido
    let eppEncontrado = null;
    let indiceEncontrado = -1;
    
    if (window.itemsPedido && Array.isArray(window.itemsPedido)) {
        indiceEncontrado = window.itemsPedido.findIndex(item => 
            (item.epp_id === epp.id || item.epp_id === parseInt(epp.id) ||
             item.id === epp.id || String(item.id) === String(epp.id))
        );
        
        if (indiceEncontrado !== -1) {
            eppEncontrado = window.itemsPedido[indiceEncontrado];
            console.log('[seleccionarEPPEnEdicion] ✅ EPP encontrado en window.itemsPedido:', eppEncontrado);
        }
    }
    
    // Si el EPP NO está en el pedido, mostrar error
    if (!eppEncontrado) {
        console.warn('[seleccionarEPPEnEdicion] ❌ El EPP seleccionado no está agregado al pedido');
        
        // Mostrar alerta amigable
        if (window.Swal) {
            Swal.fire({
                icon: 'warning',
                title: 'EPP no disponible',
                text: 'El EPP seleccionado no está agregado a este pedido. Solo puedes editar EPPs que ya están incluidos en el pedido.',
                confirmButtonText: 'Ok',
                confirmButtonColor: '#3b82f6'
            });
        } else {
            alert('El EPP seleccionado no está agregado al pedido. Solo puedes editar EPPs que ya están incluidos en el pedido.');
        }
        
        // Resetear el selector al EPP anterior
        document.getElementById('modalEditarEPPBuscador').value = eppEnEdicionIndividual.nombre_completo || eppEnEdicionIndividual.nombre_epp || eppEnEdicionIndividual.nombre;
        document.getElementById('modalEditarEPPDropdown').classList.add('hidden');
        return;
    }
    
    // EPP encontrado en el pedido - cargar sus datos
    eppEnEdicionIndividual = eppEncontrado;
    indiceEPPEnEdicion = indiceEncontrado;
    
    // Cargar imágenes con validación
    fotosEnEdicionIndividual = [];
    if (eppEncontrado.imagenes && Array.isArray(eppEncontrado.imagenes)) {
        fotosEnEdicionIndividual = eppEncontrado.imagenes.filter(img => {
            if (!img) return false;
            const hasValidUrl = img.previewUrl || img.url || img.src || img.ruta_webp || img.ruta_web || img.ruta_original;
            return !!hasValidUrl;
        });
    }
    
    console.log('[seleccionarEPPEnEdicion] EPP cargado correctamente, imágenes:', fotosEnEdicionIndividual.length);
    
    // Resetear tarjeta porque es otro EPP
    tarjetaEppIdEnEdicion = null;
    
    // Actualizar UI
    document.getElementById('modalEditarEPPBuscador').value = eppEnEdicionIndividual.nombre_completo || eppEnEdicionIndividual.nombre_epp || eppEnEdicionIndividual.nombre;
    document.getElementById('modalEditarEPPNombre').textContent = eppEnEdicionIndividual.nombre_completo || eppEnEdicionIndividual.nombre_epp || eppEnEdicionIndividual.nombre;
    document.getElementById('modalEditarEPPCantidad').value = eppEnEdicionIndividual.cantidad || 1;
    document.getElementById('modalEditarEPPObservaciones').value = eppEnEdicionIndividual.observaciones || '-';
    
    // Actualizar galería
    mostrarFotosEnModalEditar();
    
    // Ocultar dropdown
    document.getElementById('modalEditarEPPDropdown').classList.add('hidden');
}

/**
 * Cerrar modal de edición
 */
function cerrarModalEditarEPP() {
    console.log('[cerrarModalEditarEPP] Cerrando modal');
    document.getElementById('modalEditarEPP').classList.add('hidden');
    document.getElementById('modalEditarEPPDropdown').classList.add('hidden');
    
    // Remover listeners de la fotoZona
    const fotoZona = document.getElementById('modalEditarEPPFotoZona');
    if (fotoZona) {
        if (fotoZona.__pasteHandler) {
            document.removeEventListener('paste', fotoZona.__pasteHandler);
            fotoZona.__hasPasteListener = false;
            fotoZona.__pasteHandler = null;
            console.log('[cerrarModalEditarEPP] Paste listener removido del document');
        }
        // Los drag & drop listeners se quitan limpiando las referencias
        fotoZona.__hasDragDropListener = false;
        fotoZona.classList.remove('bg-blue-50', 'border-blue-400');
    }
    
    // Limpiar referencias
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
    
    console.log('[mostrarFotosEnModalEditar] Mostrando fotos. Total en array:', fotosEnEdicionIndividual.length);
    
    fotosEnEdicionIndividual.forEach((foto, index) => {
        // Obtener URL válida de la foto
        const fotoUrl = foto.previewUrl || foto.url || foto.src || foto.ruta_webp || foto.ruta_web || foto.ruta_original || (foto.file ? URL.createObjectURL(foto.file) : '');
        
        // Validar que la URL sea válida
        if (!fotoUrl) {
            console.warn(`[mostrarFotosEnModalEditar] Foto ${index} no tiene URL válida, omitiendo`);
            return;
        }
        
        // Normalizar URL: agregar /storage/ si es una ruta relativa sin protocolo (blob: o http://)
        let finalUrl = fotoUrl;
        if (!fotoUrl.startsWith('blob:') && !fotoUrl.startsWith('http://') && !fotoUrl.startsWith('https://') && !fotoUrl.startsWith('/')) {
            // Es una ruta relativa sin /storage/, agregar el prefijo
            finalUrl = '/storage/' + fotoUrl;
        } else if (!fotoUrl.startsWith('blob:') && !fotoUrl.startsWith('http://') && !fotoUrl.startsWith('https://') && fotoUrl.startsWith('/')) {
            // Es una ruta absoluta que no comienza con /storage/, revisar si necesita agregarse
            if (!fotoUrl.startsWith('/storage/')) {
                finalUrl = '/storage' + fotoUrl;
            }
        }
        
        const div = document.createElement('div');
        div.className = 'relative group rounded-lg overflow-hidden bg-gray-100 aspect-square';
        
        // Crear la imagen
        const img = document.createElement('img');
        img.src = finalUrl;
        img.alt = `Foto ${index + 1}`;
        img.className = 'w-full h-full object-cover transition-opacity duration-200';
        img.style.width = '100%';
        img.style.height = '100%';
        img.style.objectFit = 'cover';
        
        // Crear el placeholder de error (inicialmente oculto)
        const errorDiv = document.createElement('div');
        errorDiv.className = 'absolute inset-0 flex items-center justify-center bg-red-50';
        errorDiv.style.display = 'none';
        errorDiv.innerHTML = '<div style="text-center;"><div style="font-size: 2rem; margin-bottom: 0.5rem;">❌</div><div style="font-size: 0.75rem; color: #666;">Error cargando</div></div>';
        
        // Evento cuando la imagen carga exitosamente
        img.onload = function() {
            console.log(`[mostrarFotosEnModalEditar] Imagen ${index} cargada exitosamente`);
            errorDiv.style.display = 'none';
            img.style.opacity = '1';
        };
        
        // Evento cuando falla la carga
        img.onerror = function() {
            console.warn(`[mostrarFotosEnModalEditar] Error cargando imagen ${index}:`, fotoUrl);
            errorDiv.style.display = 'flex';
            img.style.opacity = '0.3';
        };
        
        // Crear el overlay (botón delete)
        const overlay = document.createElement('div');
        overlay.className = 'absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition flex items-center justify-center';
        
        const deleteBtn = document.createElement('button');
        deleteBtn.type = 'button';
        deleteBtn.className = 'opacity-0 group-hover:opacity-100 bg-red-500 text-white p-2 rounded-full hover:bg-red-600 transition';
        deleteBtn.title = 'Eliminar';
        deleteBtn.onclick = function() {
            eliminarFotoEnModalEditar(index);
        };
        deleteBtn.innerHTML = '<i class="material-symbols-rounded" style="font-size: 18px;">delete</i>';
        
        overlay.appendChild(deleteBtn);
        
        // Armar el contenedor
        div.appendChild(img);
        div.appendChild(errorDiv);
        div.appendChild(overlay);
        
        galeria.appendChild(div);
    });
    
    document.getElementById('modalEditarEPPFotosCount').textContent = fotosEnEdicionIndividual.length;
    
    // Mostrar galería si hay fotos, ocultarla si está vacía
    if (galeria.children.length > 0) {
        galeria.style.display = 'grid';
    } else {
        galeria.style.display = 'none';
    }
    
    console.log('[mostrarFotosEnModalEditar] Galería actualizada con', galeria.children.length, 'imágenes');
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
    console.log('[eliminarFotoEnModalEditar] Eliminando foto en índice:', index, '- Nombre:', foto.nombre || 'sin nombre');
    
    // Solo revocar blob URLs que creamos en esta sesión (que tienen un File object)
    if (foto && foto.file && foto.previewUrl && foto.previewUrl.startsWith('blob:')) {
        console.log('[eliminarFotoEnModalEditar] Revocando blob URL de archivo temporal');
        URL.revokeObjectURL(foto.previewUrl);
    }
    
    fotosEnEdicionIndividual.splice(index, 1);
    mostrarFotosEnModalEditar();
    
    // Actualizar la tarjeta visual inmediatamente después de eliminar
    if (tarjetaEppIdEnEdicion) {
        actualizarTarjetaEPPEnDOM(tarjetaEppIdEnEdicion, {
            nombre: eppEnEdicionIndividual.nombre_epp || eppEnEdicionIndividual.nombre_completo || eppEnEdicionIndividual.nombre,
            cantidad: document.getElementById('modalEditarEPPCantidad')?.value || (eppEnEdicionIndividual.cantidad || 1),
            observaciones: document.getElementById('modalEditarEPPObservaciones')?.value || (eppEnEdicionIndividual.observaciones || '-'),
            imagenes: fotosEnEdicionIndividual
        });
        console.log('[eliminarFotoEnModalEditar] Tarjeta visual actualizada inmediatamente');
    }
    
    console.log('[eliminarFotoEnModalEditar] Foto eliminada, total restantes:', fotosEnEdicionIndividual.length);
}

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
        totalEPPsEnLista: eppAgregadosList.length,
        itemsPedidoDisponible: !!window.itemsPedido,
        totalItemsPedido: window.itemsPedido ? window.itemsPedido.length : 0
    });
    
    // PRIMERO: Intentar buscar en window.itemsPedido (donde realmente están los EPPs agregados)
    let index = -1;
    let targetList = null;
    
    // PRIMERO: Usar el índice guardado SI ES VÁLIDO
    if (window.itemsPedido && Array.isArray(window.itemsPedido) && indiceEPPEnEdicion >= 0 && indiceEPPEnEdicion < window.itemsPedido.length) {
        console.log('[guardarEdicionEnModalEditarEPP] Verificando índice guardado:', indiceEPPEnEdicion);
        
        const eppEnIndice = window.itemsPedido[indiceEPPEnEdicion];
        
        // Validar que el EPP en este índice coincide con el que estamos editando
        if ((eppEnIndice.epp_id === eppEnEdicionIndividual.epp_id) || 
            (eppEnIndice.epp_id === eppEnEdicionIndividual.id) ||
            (eppEnIndice.nombre_epp === eppEnEdicionIndividual.nombre_epp)) {
            
            index = indiceEPPEnEdicion;
            targetList = window.itemsPedido;
            console.log('[guardarEdicionEnModalEditarEPP] ✅ Usando índice guardado:', index, '- EPP verificado');
        }
    }
    
    // SEGUNDO: Si no encontró con índice guardado, buscar en eppAgregadosList PRIMERO (donde realmente se agregan)
    if (index === -1 && eppAgregadosList.length > 0) {
        console.log('[guardarEdicionEnModalEditarEPP] Buscando en eppAgregadosList...', {
            eppEnEdicion_epp_id: eppEnEdicionIndividual.epp_id,
            eppEnEdicion_id: eppEnEdicionIndividual.id,
            eppEnEdicion_nombre_epp: eppEnEdicionIndividual.nombre_epp,
            eppAgregadosListLength: eppAgregadosList.length
        });
        
        index = eppAgregadosList.findIndex(e => {
            // Criterio 1: Coincidir por epp_id (propiedades reales)
            if (e.epp_id && eppEnEdicionIndividual.epp_id && e.epp_id === eppEnEdicionIndividual.epp_id) {
                console.log('[guardarEdicionEnModalEditarEPP] Criterio 1 en eppAgregadosList (epp_id)','coincide');
                return true;
            }
            if (e.epp_id && eppEnEdicionIndividual.id && e.epp_id === eppEnEdicionIndividual.id) {
                return true;
            }
            
            // Criterio 2: Coincidir por ID directo
            if (e.id === eppEnEdicionIndividual.epp_id || e.id === eppEnEdicionIndividual.id) {
                return true;
            }
            if (String(e.id) === String(eppEnEdicionIndividual.epp_id) || String(e.id) === String(eppEnEdicionIndividual.id)) {
                return true;
            }
            
            // Criterio 3: Coincidir por nombre
            if (e.nombre_epp && eppEnEdicionIndividual.nombre_epp && e.nombre_epp === eppEnEdicionIndividual.nombre_epp) {
                return true;
            }
            if (e.nombre === eppEnEdicionIndividual.nombre_epp || e.nombre === eppEnEdicionIndividual.nombre_completo) {
                return true;
            }
            
            return false;
        });
        
        if (index !== -1) {
            targetList = eppAgregadosList;
            console.log('[guardarEdicionEnModalEditarEPP] ✅ EPP encontrado en eppAgregadosList, índice:', index);
        } else {
            console.log('[guardarEdicionEnModalEditarEPP] EPP no encontrado en eppAgregadosList, buscando en window.itemsPedido...');
        }
    }
    
    // TERCERO: Si aún no encontró, buscar en window.itemsPedido
    if (index === -1 && window.itemsPedido && Array.isArray(window.itemsPedido)) {
        console.log('[guardarEdicionEnModalEditarEPP] Buscando en window.itemsPedido...');
        
        index = window.itemsPedido.findIndex(e => {
            // Criterio 1: Coincidir por epp_id (propiedades reales de window.itemsPedido)
            if (e.epp_id && eppEnEdicionIndividual.epp_id && e.epp_id === eppEnEdicionIndividual.epp_id) {
                return true;
            }
            if (e.epp_id && eppEnEdicionIndividual.id && e.epp_id === eppEnEdicionIndividual.id) {
                return true;
            }
            
            // Criterio 2: Coincidir por ID directo (compatibilidad)
            if (e.id === eppEnEdicionIndividual.id || e.id === eppEnEdicionIndividual.epp_id) {
                return true;
            }
            if (String(e.id) === String(eppEnEdicionIndividual.id) || String(e.id) === String(eppEnEdicionIndividual.epp_id)) {
                return true;
            }
            
            // Criterio 3: Coincidir por nombre_epp 
            if (e.nombre_epp && eppEnEdicionIndividual.nombre_epp && e.nombre_epp === eppEnEdicionIndividual.nombre_epp) {
                return true;
            }
            if (e.nombre_epp && (e.nombre_epp === eppEnEdicionIndividual.nombre_completo || e.nombre_epp === eppEnEdicionIndividual.nombre)) {
                return true;
            }
            
            // Criterio 4: Coincidir por tarjeta ID (Si está disponible)
            if (tarjetaEppIdEnEdicion && e.tarjetaId === tarjetaEppIdEnEdicion) {
                return true;
            }
            
            return false;
        });
        
        if (index !== -1) {
            targetList = window.itemsPedido;
            console.log('[guardarEdicionEnModalEditarEPP] ✅ EPP encontrado en window.itemsPedido, índice:', index);
        }
    }
    
    // FALLBACK: Si no encontró en window.itemsPedido, intentar en eppAgregadosList
    if (index === -1 && eppAgregadosList.length > 0) {
        console.log('[guardarEdicionEnModalEditarEPP] Buscando en eppAgregadosList...');
        
        index = eppAgregadosList.findIndex(e => {
            // Criterio 1: Coincidir por epp_id (si existe)
            if (e.epp_id && eppEnEdicionIndividual.epp_id && e.epp_id === eppEnEdicionIndividual.epp_id) return true;
            if (e.epp_id && eppEnEdicionIndividual.id && e.epp_id === eppEnEdicionIndividual.id) return true;
            
            // Criterio 2: Coincidir por ID
            if (e.id === eppEnEdicionIndividual.id || e.id === eppEnEdicionIndividual.epp_id) return true;
            if (String(e.id) === String(eppEnEdicionIndividual.id) || String(e.id) === String(eppEnEdicionIndividual.epp_id)) return true;
            
            // Criterio 3: Coincidir por nombre
            if (e.nombre === eppEnEdicionIndividual.nombre) return true;
            if (e.nombre === eppEnEdicionIndividual.nombre_epp) return true;
            if (e.nombre_completo === eppEnEdicionIndividual.nombre_completo) return true;
            if (e.nombre_epp === eppEnEdicionIndividual.nombre_epp) return true;
            
            return false;
        });
        
        if (index !== -1) {
            targetList = eppAgregadosList;
            console.log('[guardarEdicionEnModalEditarEPP] ✅ EPP encontrado en eppAgregadosList, índice:', index);
        }
    }
    
    // Actualizar datos en la lista encontrada
    if (index !== -1 && targetList && index < targetList.length) {
        const eppAntes = { ...targetList[index] };
        
        console.log('[guardarEdicionEnModalEditarEPP] Actualizando EPP en lista:', {
            indiceEncontrado: index,
            lista: targetList === window.itemsPedido ? 'window.itemsPedido' : 'eppAgregadosList',
            eppEncontrado: eppAntes,
            fotosAGuardar: fotosEnEdicionIndividual.length
        });
        
        // Actualizar propiedades
        targetList[index].cantidad = cantidad;
        targetList[index].observaciones = observaciones;
        targetList[index].imagenes = fotosEnEdicionIndividual;
        
        // Mantener nombres sincronizados
        if (eppEnEdicionIndividual.nombre_completo) {
            targetList[index].nombre = eppEnEdicionIndividual.nombre_completo;
            targetList[index].nombre_epp = eppEnEdicionIndividual.nombre_completo;
            targetList[index].nombre_completo = eppEnEdicionIndividual.nombre_completo;
        }
        
        console.log('[guardarEdicionEnModalEditarEPP] ✅ EPP actualizado correctamente:', {
            lista: targetList === window.itemsPedido ? 'window.itemsPedido' : 'eppAgregadosList',
            indice: index,
            antes: eppAntes,
            ahora: targetList[index]
        });
        
        // SINCRONIZACIÓN CRÍTICA: Si se actualizó en window.itemsPedido, también actualizar en eppAgregadosList
        if (targetList === window.itemsPedido && eppAgregadosList.length > 0) {
            const eppActualizado = window.itemsPedido[index];
            console.log('[guardarEdicionEnModalEditarEPP] Buscando en eppAgregadosList para sincronizar:', {
                eppActualizado_epp_id: eppActualizado.epp_id,
                eppActualizado_id: eppActualizado.id,
                eppActualizado_nombre_epp: eppActualizado.nombre_epp,
                eppAgregadosListLength: eppAgregadosList.length
            });
            
            let eppEnLista = null;
            
            // CRITERIO 1: Por epp_id (más confiable)
            eppEnLista = eppAgregadosList.find(e => {
                if (e.epp_id && eppActualizado.epp_id && e.epp_id === eppActualizado.epp_id) return true;
                if (e.epp_id && eppActualizado.id && e.epp_id === eppActualizado.id) return true;
                return false;
            });
            
            // CRITERIO 2: Por id (alternativa)
            if (!eppEnLista) {
                eppEnLista = eppAgregadosList.find(e => {
                    if (e.id === eppActualizado.epp_id) return true;
                    if (e.id === eppActualizado.id) return true;
                    return false;
                });
            }
            
            // CRITERIO 3: Por nombre_epp (si todo lo demás falla)
            if (!eppEnLista && eppActualizado.nombre_epp) {
                eppEnLista = eppAgregadosList.find(e => {
                    if (e.nombre_epp === eppActualizado.nombre_epp) return true;
                    if (e.nombre === eppActualizado.nombre_epp) return true;
                    if (e.nombre_completo === eppActualizado.nombre_epp) return true;
                    return false;
                });
            }
            
            if (eppEnLista) {
                console.log('[guardarEdicionEnModalEditarEPP] ✅ EPP encontrado en eppAgregadosList, sincronizando...');
                eppEnLista.cantidad = cantidad;
                eppEnLista.observaciones = observaciones;
                eppEnLista.imagenes = fotosEnEdicionIndividual;
                if (eppEnEdicionIndividual.nombre_completo) {
                    eppEnLista.nombre = eppEnEdicionIndividual.nombre_completo;
                    eppEnLista.nombre_epp = eppEnEdicionIndividual.nombre_completo;
                    eppEnLista.nombre_completo = eppEnEdicionIndividual.nombre_completo;
                }
                console.log('[guardarEdicionEnModalEditarEPP] ✅ eppAgregadosList sincronizado');
                
                // SINCRONIZACIÓN CRÍTICA: También actualizar window.gestionItemsUI.epps (usado por el renderer)
                if (window.gestionItemsUI && window.gestionItemsUI.epps && Array.isArray(window.gestionItemsUI.epps)) {
                    const eppEnGestión = window.gestionItemsUI.epps.find(e => {
                        if (e.epp_id === eppActualizado.epp_id) return true;
                        if (e.id === eppActualizado.id) return true;
                        if (e.nombre_epp === eppActualizado.nombre_epp) return true;
                        return false;
                    });
                    
                    if (eppEnGestión) {
                        eppEnGestión.cantidad = cantidad;
                        eppEnGestión.observaciones = observaciones;
                        eppEnGestión.imagenes = fotosEnEdicionIndividual;
                        if (eppEnEdicionIndividual.nombre_completo) {
                            eppEnGestión.nombre = eppEnEdicionIndividual.nombre_completo;
                            eppEnGestión.nombre_epp = eppEnEdicionIndividual.nombre_completo;
                            eppEnGestión.nombre_completo = eppEnEdicionIndividual.nombre_completo;
                        }
                        console.log('[guardarEdicionEnModalEditarEPP] ✅ window.gestionItemsUI.epps sincronizado');
                    }
                }
                
                // Actualizar tabla visual
                if (typeof renderizarTablaEPPAgregados === 'function') {
                    renderizarTablaEPPAgregados();
                }
            } else {
                console.warn('[guardarEdicionEnModalEditarEPP] ⚠️ No se encontró EPP en eppAgregadosList para sincronizar');
            }
        }
        
        // SINCRONIZACIÓN INVERSA: Si se actualizó en eppAgregadosList, también actualizar en window.itemsPedido
        if (targetList === eppAgregadosList && window.itemsPedido && Array.isArray(window.itemsPedido) && window.itemsPedido.length > 0) {
            const eppActualizado = eppAgregadosList[index];
            const eppEnItemsPedido = window.itemsPedido.find(e => {
                if (e.epp_id === eppActualizado.epp_id || e.epp_id === eppActualizado.id) return true;
                if (e.id === eppActualizado.id) return true;
                if (e.nombre_epp === eppActualizado.nombre || e.nombre_epp === eppActualizado.nombre_completo) return true;
                return false;
            });
            
            if (eppEnItemsPedido) {
                console.log('[guardarEdicionEnModalEditarEPP] Sincronizando con window.itemsPedido...');
                eppEnItemsPedido.cantidad = cantidad;
                eppEnItemsPedido.observaciones = observaciones;
                eppEnItemsPedido.imagenes = fotosEnEdicionIndividual;
                if (eppEnEdicionIndividual.nombre_completo) {
                    eppEnItemsPedido.nombre = eppEnEdicionIndividual.nombre_completo;
                    eppEnItemsPedido.nombre_epp = eppEnEdicionIndividual.nombre_completo;
                    eppEnItemsPedido.nombre_completo = eppEnEdicionIndividual.nombre_completo;
                }
                console.log('[guardarEdicionEnModalEditarEPP] ✅ window.itemsPedido sincronizado');
            }
        }
        
        // Actualizar tarjeta visual directamente en el DOM
        if (tarjetaEppIdEnEdicion) {
            actualizarTarjetaEPPEnDOM(tarjetaEppIdEnEdicion, {
                nombre: eppEnEdicionIndividual.nombre_epp || eppEnEdicionIndividual.nombre_completo || eppEnEdicionIndividual.nombre,
                cantidad: cantidad,
                observaciones: observaciones,
                imagenes: fotosEnEdicionIndividual
            });
            console.log('[guardarEdicionEnModalEditarEPP] ✅ Tarjeta visual actualizada en DOM:', tarjetaEppIdEnEdicion);
        } else {
            console.warn('[guardarEdicionEnModalEditarEPP] ⚠️ No hay tarjetaId para actualizar la tarjeta visual');
        }
        
        // RE-RENDERIZAR TARJETAS: Actualizar la UI con el ItemRenderer
        if (window.gestionItemsUI && window.gestionItemsUI.renderer) {
            try {
                console.log('[guardarEdicionEnModalEditarEPP] Llamando a itemRenderer para re-renderizar tarjetas...');
                const itemsOrdenados = window.gestionItemsUI.obtenerItemsOrdenados();
                window.gestionItemsUI.renderer.actualizar(itemsOrdenados).then(() => {
                    console.log('[guardarEdicionEnModalEditarEPP] ✅ Tarjetas re-renderizadas exitosamente');
                    cerrarModalEditarEPP();
                }).catch(error => {
                    console.error('[guardarEdicionEnModalEditarEPP] Error al re-renderizar:', error);
                    cerrarModalEditarEPP();
                });
            } catch (error) {
                console.error('[guardarEdicionEnModalEditarEPP] Error al obtener items para re-renderizar:', error);
                cerrarModalEditarEPP();
            }
        } else {
            console.log('[guardarEdicionEnModalEditarEPP] ItemRenderer no disponible, cerrando modal sin re-renderizar');
            cerrarModalEditarEPP();
        }
    } else {
        console.error('[guardarEdicionEnModalEditarEPP] ❌ No se encontró el EPP en ninguna lista', {
            indiceCalculado: index,
            eppEnEdicion: eppEnEdicionIndividual,
            eppAgregadosListLength: eppAgregadosList.length,
            itemsPedidoLength: window.itemsPedido ? window.itemsPedido.length : 0,
            itemsPedidoContent: window.itemsPedido ? JSON.stringify(window.itemsPedido.map(e => ({id: e.id, epp_id: e.epp_id, nombre_epp: e.nombre_epp}))) : 'N/A',
            buscandoPor: {
                id: eppEnEdicionIndividual.id,
                epp_id: eppEnEdicionIndividual.epp_id,
                nombre: eppEnEdicionIndividual.nombre,
                nombre_completo: eppEnEdicionIndividual.nombre_completo
            }
        });
        
        // Intentar una vez más con criterios más flexibles
        if (window.itemsPedido && Array.isArray(window.itemsPedido) && window.itemsPedido.length > 0) {
            console.log('[guardarEdicionEnModalEditarEPP] Reintentando búsqueda con criterios flexibles...');
            
            index = window.itemsPedido.findIndex(e => {
                // Búsqueda muy flexible por nombre
                if (eppEnEdicionIndividual.nombre_completo && 
                    e.nombre_epp && 
                    e.nombre_epp.toLowerCase().includes(eppEnEdicionIndividual.nombre_completo.toLowerCase())) {
                    return true;
                }
                return false;
            });
            
            if (index !== -1) {
                console.log('[guardarEdicionEnModalEditarEPP] ✅ EPP encontrado en reintento por nombre flexible');
                targetList = window.itemsPedido;
                
                // Actualizar datos en la lista encontrada
                if (targetList && index < targetList.length) {
                    const eppAntes = { ...targetList[index] };
                    
                    targetList[index].cantidad = cantidad;
                    targetList[index].observaciones = observaciones;
                    targetList[index].imagenes = fotosEnEdicionIndividual;
                    
                    console.log('[guardarEdicionEnModalEditarEPP] ✅ EPP actualizado correctamente en reintento:', {
                        lista: 'window.itemsPedido',
                        indice: index,
                        antes: eppAntes,
                        ahora: targetList[index]
                    });
                }
                
                // RE-RENDERIZAR TARJETAS: Actualizar la UI con el ItemRenderer
                if (window.gestionItemsUI && window.gestionItemsUI.renderer) {
                    try {
                        console.log('[guardarEdicionEnModalEditarEPP] Llamando a itemRenderer para re-renderizar tarjetas (reintento)...');
                        const itemsOrdenados = window.gestionItemsUI.obtenerItemsOrdenados();
                        window.gestionItemsUI.renderer.actualizar(itemsOrdenados).then(() => {
                            console.log('[guardarEdicionEnModalEditarEPP] ✅ Tarjetas re-renderizadas exitosamente (reintento)');
                            cerrarModalEditarEPP();
                        }).catch(error => {
                            console.error('[guardarEdicionEnModalEditarEPP] Error al re-renderizar (reintento):', error);
                            cerrarModalEditarEPP();
                        });
                    } catch (error) {
                        console.error('[guardarEdicionEnModalEditarEPP] Error al obtener items para re-renderizar (reintento):', error);
                        cerrarModalEditarEPP();
                    }
                } else {
                    console.log('[guardarEdicionEnModalEditarEPP] ItemRenderer no disponible en reintento, cerrando modal');
                    cerrarModalEditarEPP();
                }
            }
        }
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

/**
 * Actualizar tarjeta EPP en el DOM
 * Busca la tarjeta visual y actualiza su contenido sin depender del manager
 */
function actualizarTarjetaEPPEnDOM(tarjetaId, datos) {
    try {
        console.log('[actualizarTarjetaEPPEnDOM] Iniciando actualización de tarjeta:', {
            tarjetaId: tarjetaId,
            datos: datos
        });
        
        // Buscar la tarjeta por su data-epp-id
        const tarjeta = document.querySelector(`[data-epp-id="${tarjetaId}"]`);
        
        if (!tarjeta) {
            console.warn('[actualizarTarjetaEPPEnDOM] Tarjeta no encontrada con ID:', tarjetaId);
            return;
        }
        
        console.log('[actualizarTarjetaEPPEnDOM] Tarjeta encontrada, actualizando...');
        
        // Actualizar nombre - buscar el h4 dentro del header
        const nombreElemento = tarjeta.querySelector('h4');
        if (nombreElemento) {
            nombreElemento.textContent = datos.nombre || '-';
            console.log('[actualizarTarjetaEPPEnDOM] Nombre actualizado a:', datos.nombre);
        } else {
            console.warn('[actualizarTarjetaEPPEnDOM] ⚠️ No se encontró elemento h4 para el nombre');
        }
        
        // Actualizar cantidad y observaciones
        // La estructura es: Detalles principales con grid de 2 columnas
        // Columna 1: Cantidad label + cantidad valor
        // Columna 2: Observaciones label + observaciones valor
        
        const parrafos = Array.from(tarjeta.querySelectorAll('p'));
        
        // Buscar y actualizar cantidad
        for (let i = 0; i < parrafos.length; i++) {
            const parrafo = parrafos[i];
            if (parrafo.textContent.includes('Cantidad')) {
                // El siguiente p debe tener la cantidad
                if (i + 1 < parrafos.length) {
                    parrafos[i + 1].textContent = datos.cantidad || 1;
                    console.log('[actualizarTarjetaEPPEnDOM] Cantidad actualizada a:', datos.cantidad);
                }
                break;
            }
        }
        
        // Buscar y actualizar observaciones
        for (let i = 0; i < parrafos.length; i++) {
            const parrafo = parrafos[i];
            if (parrafo.textContent.includes('Observaciones')) {
                // El siguiente p debe tener las observaciones
                if (i + 1 < parrafos.length) {
                    parrafos[i + 1].textContent = datos.observaciones || '-';
                    console.log('[actualizarTarjetaEPPEnDOM] Observaciones actualizadas a:', datos.observaciones);
                }
                break;
            }
        }
        
        // Actualizar imágenes
        if (datos.imagenes && datos.imagenes.length > 0) {
            // Buscar o crear el contenedor de imágenes
            let containerImagenes = tarjeta.querySelector('.epp-imagenes-container');
            
            // Crear HTML para las imágenes
            let htmlImagenes = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 0.5rem; margin-top: 1rem;">';
            
            for (let i = 0; i < datos.imagenes.length; i++) {
                const img = datos.imagenes[i];
                const src = img.previewUrl || img.url || img.src || (typeof img === 'string' ? img : '');
                
                if (src) {
                    htmlImagenes += `
                        <div style="width: 80px; height: 80px; background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 4px; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                            <img src="${src}" alt="EPP" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.style.display='none';">
                        </div>
                    `;
                }
            }
            
            htmlImagenes += '</div>';
            
            if (!containerImagenes) {
                containerImagenes = document.createElement('div');
                containerImagenes.className = 'epp-imagenes-container';
                tarjeta.appendChild(containerImagenes);
            }
            
            containerImagenes.innerHTML = htmlImagenes;
            console.log('[actualizarTarjetaEPPEnDOM] Imágenes actualizadas:', datos.imagenes.length);
        } else {
            // Si no hay imágenes, limpiar el contenedor
            const containerImagenes = tarjeta.querySelector('.epp-imagenes-container');
            if (containerImagenes) {
                containerImagenes.innerHTML = '';
                console.log('[actualizarTarjetaEPPEnDOM] Contenedor de imágenes limpiado (0 imágenes)');
            }
        }
        
        console.log('[actualizarTarjetaEPPEnDOM] ✅ Tarjeta actualizada correctamente');
    } catch (error) {
        console.error('[actualizarTarjetaEPPEnDOM] Error actualizando tarjeta:', error);
    }
}
</script>
