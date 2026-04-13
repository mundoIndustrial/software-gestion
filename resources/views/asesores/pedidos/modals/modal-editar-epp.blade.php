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

            <!-- SecciÃ³n de Fotos -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <label for="modalEditarEPPInputFotos" class="text-sm font-semibold text-gray-900">Fotos del EPP</label>
                    <span class="text-xs text-gray-500">(<span id="modalEditarEPPFotosCount">0</span> foto/s)</span>
                </div>

                <!-- Zona de carga -->
                <div class="space-y-3">
                <!-- Zona Drag & Drop -->
                    <div id="modalEditarEPPFotoZona" 
                        class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:bg-gray-50 transition">
                        
                        <div class="flex flex-col items-center gap-2">
                            <i class="material-symbols-rounded text-3xl text-gray-400">cloud_upload</i>
                            <p class="text-sm text-gray-600">Arrastra imÃ¡genes aquÃ­</p>
                            <p class="text-xs text-gray-500">TambiÃ©n puedes usar Ctrl+V o hacer clic en "Agregar Foto"</p>
                        </div>
                    </div>

                    <!-- BotÃ³n para agregar fotos -->
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

                <!-- GalerÃ­a de fotos -->
                <div id="modalEditarEPPFotosGaleria" class="grid grid-cols-4 gap-2 mt-4">
                    <!-- Las fotos se agregan dinÃ¡micamente aquÃ­ -->
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
 * Estado global para ediciÃ³n de EPP individual
 */
let eppEnEdicionIndividual = null;
let fotosEnEdicionIndividual = [];
let eppsDisponiblesParaEdicion = [];
let indiceEPPEnEdicion = -1;  // Guardar el Ã­ndice para bÃºsqueda rÃ¡pida
let tarjetaEppIdEnEdicion = null;  // Guardar el ID de la tarjeta visual para actualizar despuÃ©s
let imagenesEditadasEnModalEPP = false; // Marca de cambio real de imÃ¡genes en modal

function obtenerGestionItemsUIEditarEPP() {
    if (window.gestionItemsUI && typeof window.gestionItemsUI.buscarEPPEnEstado === 'function') {
        return window.gestionItemsUI;
    }
    return null;
}

function normalizarReferenciaEPPEditar(valor) {
    if (valor === null || valor === undefined || valor === '') return null;
    if (typeof valor === 'number' && !Number.isNaN(valor)) return valor;
    const txt = String(valor).trim();
    if (!txt) return null;
    if (/^\d+$/.test(txt)) return Number(txt);
    const matchTarjeta = txt.match(/^epp-(\d+)(?:-|$)/i);
    if (matchTarjeta) return Number(matchTarjeta[1]);
    return txt;
}

function construirReferenciaEPPEditar(eppData = {}) {
    return {
        tarjetaId: eppData.tarjetaId || eppData.id || null,
        pedido_epp_id: eppData.pedido_epp_id || eppData.pedidoEppId || null,
        epp_id: eppData.epp_id || eppData.data_epp_original_id || eppData.id || null,
        nombre: eppData.nombre || eppData.nombre_epp || eppData.nombre_completo || null
    };
}

function copiarImagenesValidasEPPEditar(imagenes = []) {
    if (!Array.isArray(imagenes)) {
        return [];
    }

    return imagenes
        .filter((img) => img && (img.previewUrl || img.url || img.src || img.ruta_webp || img.ruta_web || img.ruta_original))
        .map((img) => ({ ...img }));
}

function configurarZonaFotosModalEditarEPP() {
    const fotoZona = document.getElementById('modalEditarEPPFotoZona');
    if (!fotoZona) {
        return;
    }

    fotoZona.setAttribute('tabindex', '0');

    if (!fotoZona.__hasPasteListener) {
        function handlePasteInFotoZona(e) {
            const modal = document.getElementById('modalEditarEPP');
            if (!modal || modal.classList.contains('hidden')) return;

            const items = e.clipboardData?.items;
            if (!items) return;

            let pegadasOk = 0;
            for (const item of items) {
                if (!item.type.startsWith('image/')) continue;

                e.preventDefault();
                const file = item.getAsFile();
                if (!file) continue;

                fotosEnEdicionIndividual.push({
                    id: Date.now() + '_paste_' + Math.random().toString(36).slice(2, 7),
                    file,
                    previewUrl: URL.createObjectURL(file),
                    nombre: file.name || `pegado_${Date.now()}.png`,
                    extension: (file.name || '').split('.').pop().toLowerCase() || 'png',
                    pedido_epp_id: null,
                    ruta_original: null,
                    ruta_webp: null,
                    principal: 0,
                    orden: 0
                });
                pegadasOk++;
            }

            if (pegadasOk > 0) {
                imagenesEditadasEnModalEPP = true;
                mostrarFotosEnModalEditar();
            }
        }

        document.addEventListener('paste', handlePasteInFotoZona);
        fotoZona.__hasPasteListener = true;
        fotoZona.__pasteHandler = handlePasteInFotoZona;
    }

    if (!fotoZona.__hasDragDropListener) {
        fotoZona.addEventListener('click', (e) => {
            if (e.target.closest('button')) return;
            document.getElementById('modalEditarEPPInputFotos')?.click();
        });

        fotoZona.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                document.getElementById('modalEditarEPPInputFotos')?.click();
            }
        });

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
    }
}

function cargarEPPUnificadoEnModalEditar(eppData, opciones = {}) {
    const gestion = obtenerGestionItemsUIEditarEPP();
    if (!gestion) {
        return null;
    }

    const referencia = construirReferenciaEPPEditar(eppData);
    const eppEncontrado = gestion.buscarEPPEnEstado(referencia);
    if (!eppEncontrado) {
        return null;
    }

    const tarjetaId = opciones.tarjetaId === null
        ? null
        : (opciones.tarjetaId || eppEncontrado.tarjetaId || `epp-${eppEncontrado.pedido_epp_id || eppEncontrado.pedidoEppId || eppEncontrado.epp_id || eppEncontrado.id}`);

    tarjetaEppIdEnEdicion = tarjetaId;
    indiceEPPEnEdicion = -1;
    imagenesEditadasEnModalEPP = false;

    eppEnEdicionIndividual = {
        ...eppEncontrado,
        id: normalizarReferenciaEPPEditar(eppEncontrado.id || eppEncontrado.epp_id || eppEncontrado.tarjetaId),
        epp_id: normalizarReferenciaEPPEditar(eppEncontrado.epp_id || eppEncontrado.id),
        pedido_epp_id: eppEncontrado.pedido_epp_id || eppEncontrado.pedidoEppId || null,
        tarjetaId,
        nombre: eppEncontrado.nombre || eppEncontrado.nombre_epp || eppEncontrado.nombre_completo || '',
        nombre_epp: eppEncontrado.nombre_epp || eppEncontrado.nombre || eppEncontrado.nombre_completo || '',
        nombre_completo: eppEncontrado.nombre_completo || eppEncontrado.nombre_epp || eppEncontrado.nombre || ''
    };

    fotosEnEdicionIndividual = copiarImagenesValidasEPPEditar(eppEncontrado.imagenes || []);

    document.getElementById('modalEditarEPPBuscador').value = eppEnEdicionIndividual.nombre_completo || eppEnEdicionIndividual.nombre_epp || eppEnEdicionIndividual.nombre || 'EPP';
    document.getElementById('modalEditarEPPNombre').textContent = eppEnEdicionIndividual.nombre_completo || eppEnEdicionIndividual.nombre_epp || eppEnEdicionIndividual.nombre || 'EPP';
    document.getElementById('modalEditarEPPCantidad').value = eppEnEdicionIndividual.cantidad || 1;
    document.getElementById('modalEditarEPPObservaciones').value = eppEnEdicionIndividual.observaciones || '-';
    document.getElementById('modalEditarEPPDropdown').classList.add('hidden');

    mostrarFotosEnModalEditar();
    document.getElementById('modalEditarEPP').classList.remove('hidden');
    configurarZonaFotosModalEditarEPP();

    return eppEnEdicionIndividual;
}

/**
 * Abrir modal para editar un EPP individual
 */
function abrirModalEditarEPP(eppData) {
    console.log('[abrirModalEditarEPP] Abriendo modal con EPP:', eppData);
    const eppUnificado = cargarEPPUnificadoEnModalEditar(eppData, {
        tarjetaId: eppData?.tarjetaId || eppData?.id || null
    });
    if (eppUnificado) {
        console.log('[abrirModalEditarEPP] Modal cargado desde gestionItemsUI');
        return;
    }
    console.warn('[abrirModalEditarEPP] No se encontro el EPP en gestionItemsUI');
}

/**
 * Filtrar EPPs en el buscador de ediciÃ³n
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
 * Seleccionar un EPP diferente en la ediciÃ³n
 */
function seleccionarEPPEnEdicion(epp) {
    console.log('[seleccionarEPPEnEdicion] EPP seleccionado:', epp);
    const eppUnificado = cargarEPPUnificadoEnModalEditar(epp, { tarjetaId: null });
    if (eppUnificado) {
        tarjetaEppIdEnEdicion = null;
        document.getElementById('modalEditarEPPDropdown').classList.add('hidden');
        console.log('[seleccionarEPPEnEdicion] EPP resuelto desde gestionItemsUI');
        return;
    }
    if (window.Swal) {
        Swal.fire({
            icon: 'warning',
            title: 'EPP no disponible',
            text: 'El EPP seleccionado no esta agregado a este pedido.',
            confirmButtonText: 'Ok',
            confirmButtonColor: '#3b82f6'
        });
    }
    document.getElementById('modalEditarEPPDropdown').classList.add('hidden');
}

/**
 * Cerrar modal de ediciÃ³n
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
 * Mostrar fotos en la galerÃ­a del modal
 */
function mostrarFotosEnModalEditar() {
    const galeria = document.getElementById('modalEditarEPPFotosGaleria');
    galeria.innerHTML = '';
    
    console.log('[mostrarFotosEnModalEditar] Mostrando fotos. Total en array:', fotosEnEdicionIndividual.length);
    
    fotosEnEdicionIndividual.forEach((foto, index) => {
        // Obtener URL vÃ¡lida de la foto
        const fotoUrl = foto.previewUrl || foto.url || foto.src || foto.ruta_webp || foto.ruta_web || foto.ruta_original || (foto.file ? URL.createObjectURL(foto.file) : '');
        
        // Validar que la URL sea vÃ¡lida
        if (!fotoUrl) {
            console.warn(`[mostrarFotosEnModalEditar] Foto ${index} no tiene URL vÃ¡lida, omitiendo`);
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
        errorDiv.innerHTML = '<div style="text-center;"><div style="font-size: 2rem; margin-bottom: 0.5rem;"></div><div style="font-size: 0.75rem; color: #666;">Error cargando</div></div>';
        
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
        
        // Crear el overlay (botÃ³n delete)
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
    
    // Mostrar galerÃ­a si hay fotos, ocultarla si estÃ¡ vacÃ­a
    if (galeria.children.length > 0) {
        galeria.style.display = 'grid';
    } else {
        galeria.style.display = 'none';
    }
    
    console.log('[mostrarFotosEnModalEditar] GalerÃ­a actualizada con', galeria.children.length, 'imÃ¡genes');
}

/**
 * Manejar selecciÃ³n de fotos en el modal
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
    if (files.length > 0) {
        imagenesEditadasEnModalEPP = true;
    }
    
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
    if (files.length > 0) {
        imagenesEditadasEnModalEPP = true;
    }
    
    mostrarFotosEnModalEditar();
    console.log('[manejarDropEnModalEditar] Total de fotos:', fotosEnEdicionIndividual.length);
}

/**
 * Eliminar una foto en el modal
 */
function eliminarFotoEnModalEditar(index) {
    const foto = fotosEnEdicionIndividual[index];
    console.log('[eliminarFotoEnModalEditar] Eliminando foto en Ã­ndice:', index, '- Nombre:', foto.nombre || 'sin nombre');
    
    // Solo revocar blob URLs que creamos en esta sesiÃ³n (que tienen un File object)
    if (foto && foto.file && foto.previewUrl && foto.previewUrl.startsWith('blob:')) {
        console.log('[eliminarFotoEnModalEditar] Revocando blob URL de archivo temporal');
        URL.revokeObjectURL(foto.previewUrl);
    }
    
    fotosEnEdicionIndividual.splice(index, 1);
    imagenesEditadasEnModalEPP = true;
    mostrarFotosEnModalEditar();
    
    // Actualizar la tarjeta visual inmediatamente despuÃ©s de eliminar
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
 * Guardar cambios de ediciÃ³n individual
 */
function guardarEdicionEnModalEditarEPP() {
    const cantidad = parseInt(document.getElementById('modalEditarEPPCantidad').value) || 1;
    const observaciones = document.getElementById('modalEditarEPPObservaciones').value || '-';
    if (!eppEnEdicionIndividual) {
        console.error('[guardarEdicionEnModalEditarEPP] No hay EPP en edicion');
        return;
    }
    const gestion = obtenerGestionItemsUIEditarEPP();
    if (!gestion || typeof gestion.actualizarEPPEnEstado !== 'function' || typeof gestion.obtenerItemsOrdenados !== 'function') {
        console.error('[guardarEdicionEnModalEditarEPP] gestionItemsUI no disponible');
        return;
    }
    const eppActualizado = gestion.actualizarEPPEnEstado(
        construirReferenciaEPPEditar({
            ...eppEnEdicionIndividual,
            tarjetaId: tarjetaEppIdEnEdicion || eppEnEdicionIndividual.tarjetaId || eppEnEdicionIndividual.id
        }),
        {
            cantidad,
            observaciones,
            imagenes: fotosEnEdicionIndividual,
            imagenes_editadas: imagenesEditadasEnModalEPP,
            pedido_epp_id: eppEnEdicionIndividual.pedido_epp_id || eppEnEdicionIndividual.pedidoEppId || null,
            nombre: eppEnEdicionIndividual.nombre_completo || eppEnEdicionIndividual.nombre_epp || eppEnEdicionIndividual.nombre || '',
            nombre_epp: eppEnEdicionIndividual.nombre_completo || eppEnEdicionIndividual.nombre_epp || eppEnEdicionIndividual.nombre || '',
            nombre_completo: eppEnEdicionIndividual.nombre_completo || eppEnEdicionIndividual.nombre_epp || eppEnEdicionIndividual.nombre || ''
        }
    );
    if (!eppActualizado) {
        console.error('[guardarEdicionEnModalEditarEPP] No se encontro el EPP en gestionItemsUI');
        return;
    }
    if (gestion.renderer && typeof gestion.renderer.actualizar === 'function') {
        Promise.resolve(gestion.renderer.actualizar(gestion.obtenerItemsOrdenados()))
            .catch((error) => {
                console.error('[guardarEdicionEnModalEditarEPP] Error en re-render unificado:', error);
            })
            .finally(() => {
                cerrarModalEditarEPP();
            });
        return;
    }
    cerrarModalEditarEPP();
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
        console.log('[actualizarTarjetaEPPEnDOM] Iniciando actualizaciÃ³n de tarjeta:', {
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
            console.warn('[actualizarTarjetaEPPEnDOM]  No se encontrÃ³ elemento h4 para el nombre');
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
        
        // Actualizar imÃ¡genes
        if (datos.imagenes && datos.imagenes.length > 0) {
            // Buscar o crear el contenedor de imÃ¡genes
            let containerImagenes = tarjeta.querySelector('.epp-imagenes-container');
            
            // Crear HTML para las imÃ¡genes
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
            console.log('[actualizarTarjetaEPPEnDOM] ImÃ¡genes actualizadas:', datos.imagenes.length);
        } else {
            // Si no hay imÃ¡genes, limpiar el contenedor
            const containerImagenes = tarjeta.querySelector('.epp-imagenes-container');
            if (containerImagenes) {
                containerImagenes.innerHTML = '';
                console.log('[actualizarTarjetaEPPEnDOM] Contenedor de imÃ¡genes limpiado (0 imÃ¡genes)');
            }
        }
        
        console.log('[actualizarTarjetaEPPEnDOM]  Tarjeta actualizada correctamente');
    } catch (error) {
        console.error('[actualizarTarjetaEPPEnDOM] Error actualizando tarjeta:', error);
    }
}
</script>




