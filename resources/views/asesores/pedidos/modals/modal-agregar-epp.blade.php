<!-- Modal Agregar EPP al Pedido -->
<div id="modalAgregarEPP" class="fixed inset-0 bg-black/50 flex items-center justify-center" style="display: none; z-index: 999999;">
    <div class="bg-white rounded-lg w-full max-w-2xl shadow-2xl overflow-hidden" style="z-index: 1000000; max-height: 90vh; display: flex; flex-direction: column;">
        
        <!-- Header Azul -->
        <div class="bg-blue-600 px-6 py-4 flex justify-between items-center flex-shrink-0">
            <h2 class="text-white text-lg font-bold">Agregar EPP al Pedido</h2>
            <button onclick="cerrarModalAgregarEPP()" class="text-white hover:bg-blue-700 p-1 rounded transition">
                <i class="material-symbols-rounded">close</i>
            </button>
        </div>

        <!-- Body con scroll -->
        <div class="p-6 space-y-4 overflow-y-auto flex-1" style="max-height: calc(90vh - 140px);">
            
            <!-- Buscador -->
            <div>
                <label for="inputBuscadorEPP" class="text-sm font-medium text-gray-700 block mb-2">Buscar por Referencia o Nombre</label>
                <div class="relative">
                    <i class="material-symbols-rounded absolute left-3 top-2.5 text-gray-400 text-xl">search</i>
                    <input 
                        type="text" 
                        id="inputBuscadorEPP"
                        onkeyup="filtrarEPPBuscador(this.value)"
                        placeholder="Ej. Casco, Nitrilo, Botas..." 
                        class="w-full pl-10 pr-4 py-2 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-200 text-sm"
                    >
                </div>
                <!-- Contenedor de resultados de búsqueda - DENTRO DEL FORMULARIO -->
                <div id="resultadosBuscadorEPP" class="bg-white border border-gray-300 border-t-0 rounded-b-lg shadow max-h-64 overflow-y-auto mt-0" style="display: none;"></div>
            </div>

            <!-- Botón Crear Nuevo EPP -->
            <div class="flex gap-2">
                <button 
                    type="button"
                    onclick="abrirFormularioCrearEPP()"
                    class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg font-medium flex items-center justify-center gap-2 hover:bg-blue-700 transition text-sm"
                >
                    <i class="material-symbols-rounded" style="font-size: 20px;">add_circle</i>
                    Crear Nuevo EPP
                </button>
            </div>

            <!-- Formulario para Crear Nuevo EPP (inicialmente oculto) -->
            <div id="formularioCrearEPP" class="bg-blue-50 border-2 border-blue-200 rounded-lg p-4" style="display: none;">
                <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="material-symbols-rounded">add</i>
                    Crear Nuevo EPP
                </h3>
                
                <div class="space-y-3">
                    <div>
                        <label for="nombreCompletNuevoEPP" class="text-sm font-medium text-gray-700 block mb-1">Nombre Completo</label>
                        <input 
                            type="text"
                            id="nombreCompletNuevoEPP"
                            placeholder="Ej. CASCO DE SEGURIDAD ROJO"
                            class="w-full px-3 py-2 border-2 border-blue-300 rounded-lg focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-200 text-sm"
                        >
                    </div>
                    
                    <div class="flex gap-2">
                        <button 
                            type="button"
                            onclick="guardarNuevoEPP()"
                            class="flex-1 px-3 py-2 bg-green-600 text-white rounded-lg font-medium flex items-center justify-center gap-2 hover:bg-green-700 transition text-sm"
                        >
                            <i class="material-symbols-rounded" style="font-size: 18px;">check_circle</i>
                            Guardar
                        </button>
                        <button 
                            type="button"
                            onclick="cerrarFormularioCrearEPP()"
                            class="flex-1 px-3 py-2 bg-gray-400 text-white rounded-lg font-medium flex items-center justify-center gap-2 hover:bg-gray-500 transition text-sm"
                        >
                            <i class="material-symbols-rounded" style="font-size: 18px;">close</i>
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>


            <!-- Tarjeta Producto (inicialmente oculta) -->
            <div id="productoCardEPP" class="bg-blue-50 border border-blue-200 rounded-lg p-4 animate-in fade-in" style="display: none;">
                <div>
                    <label for="nombreProductoEPP" class="text-sm font-medium text-gray-700 block mb-2">Nombre del EPP</label>
                    <input 
                        type="text"
                        id="nombreProductoEPP"
                        placeholder="Nombre del EPP"
                        readonly
                        class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg bg-gray-100 text-gray-700 text-sm focus:outline-none cursor-default"
                    >
                </div>
            </div>

            <!-- Sección de Fotos (opcional) -->
            <div id="seccionFotosEPP" style="display: none;">
                <div class="flex items-center justify-between mb-3">
                    <label class="text-sm font-medium text-gray-700">Fotos del EPP (Opcional)</label>
                    <button type="button" onclick="agregarFotoEPP()" class="px-3 py-1 bg-blue-600 text-white text-sm rounded-lg font-medium flex items-center gap-1 hover:bg-blue-700 transition">
                        <i class="material-symbols-rounded" style="font-size: 16px;">add_photo_alternate</i>
                        Agregar Foto
                    </button>
                </div>
                
                <!-- Contenedor de imágenes -->
                <div id="contenedorFotosEPP" class="grid grid-cols-3 gap-3 mb-4 border-2 border-dashed border-gray-300 rounded-lg p-4 min-h-[120px] transition-all"
                     tabindex="0" 
                     style="outline: none;"
                     data-zona="epp"
                     onmouseover="this.focus()"
                     onmouseleave="this.blur()"
                     ondrop="handleDropEPP(event)"
                     ondragover="handleDragOverEPP(event)"
                     ondragleave="handleDragLeaveEPP(event)">
                    
                    <!-- Mensaje inicial -->
                    <div id="mensajeDragDrop" class="col-span-3 flex flex-col items-center justify-center text-gray-400">
                        <i class="material-symbols-rounded text-4xl mb-2">cloud_upload</i>
                        <p class="text-sm">Arrastra imágenes aquí o haz clic en "Agregar Foto"</p>
                        <p class="text-xs">También puedes pegar con Ctrl+V</p>
                        <p class="text-xs">Formatos: JPG, PNG, GIF, WebP, JFIF</p>
                    </div>
                    
                    <!-- Las imágenes se agregarán aquí dinámicamente -->
                </div>
                
                <!-- Input oculto para subir archivos -->
                <input 
                    type="file" 
                    id="inputFotosEPP" 
                    multiple 
                    accept="image/*" 
                    style="display: none;"
                    onchange="manejarSubidaFotosEPP(this)"
                >
            </div>

            <!-- Cantidad y Talla -->
            <!-- Solo Cantidad -->
            <div id="formularioAgregarEPP" style="display: none;">
                <div>
                    <label for="cantidadEPP" class="text-sm font-medium text-gray-700 block mb-2">Cantidad</label>
                    <input 
                        type="number"
                        id="cantidadEPP"
                        value="1"
                        placeholder="1"
                        min="1"
                        disabled
                        class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg bg-gray-100 text-gray-500 text-sm disabled:bg-gray-100 disabled:text-gray-400 focus:outline-none"
                    >
                </div>
            </div>

            <!-- Observaciones -->
            <div id="observacionesContainer" style="display: none;">
                <label for="observacionesEPP" class="text-sm font-medium text-gray-700 block mb-2">Observaciones (Opcional)</label>
                <textarea 
                    id="observacionesEPP"
                    placeholder="Detalles adicionales..."
                    disabled
                    rows="2"
                    class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg bg-gray-100 text-gray-500 text-sm disabled:bg-gray-100 disabled:text-gray-400 focus:outline-none resize-none"
                ></textarea>
            </div>

            <!-- Botón Agregar a Lista -->
            <button 
                id="btnAgregarALista"
                onclick="agregarEPPALista()"
                disabled
                class="w-full px-4 py-2 bg-green-600 text-white rounded-lg font-medium flex items-center justify-center gap-2 hover:bg-green-700 disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed transition text-sm"
                style="display: none;"
            >
                <i class="material-symbols-rounded" style="font-size: 20px;">add</i>
                Agregar a la Lista
            </button>

            <!-- Lista de EPP agregados -->
            <div id="listaEPPAgregados" style="display: none;">
                <h3 class="text-sm font-semibold text-gray-900 mb-2 flex items-center gap-2">
                    <i class="material-symbols-rounded">list</i>
                    EPP Agregados
                </h3>
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-2 text-left text-gray-700 font-medium">Foto</th>
                                <th class="px-4 py-2 text-left text-gray-700 font-medium">EPP</th>
                                <th class="px-4 py-2 text-left text-gray-700 font-medium">Cantidad</th>
                                <th class="px-4 py-2 text-left text-gray-700 font-medium">Observaciones</th>
                                <th class="px-4 py-2 text-center text-gray-700 font-medium">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaEPP">
                            <!-- Se llena dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Footer fijo -->
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3 flex-shrink-0">
            <button onclick="cerrarModalAgregarEPP()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-100 transition text-sm">
                Cancelar
            </button>
            <!-- Botón Finalizar (visible en modo normal) -->
            <button 
                id="btnFinalizarAgregarEPP"
                onclick="finalizarAgregarEPP()"
                disabled
                class="px-4 py-2 bg-blue-600 text-white rounded-lg font-medium flex items-center gap-2 hover:bg-blue-700 disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed transition text-sm"
            >
                <i class="material-symbols-rounded" style="font-size: 20px;">check_circle</i>
                Finalizar
            </button>
            <!-- Botón Guardar Cambios (visible en modo edición) -->
            <button 
                id="btnGuardarCambiosEPP"
                onclick="guardarEdicionEPP()"
                disabled
                class="px-4 py-2 bg-green-600 text-white rounded-lg font-medium flex items-center gap-2 hover:bg-green-700 disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed transition text-sm"
                style="display: none;"
            >
                <i class="material-symbols-rounded" style="font-size: 20px;">save</i>
                Guardar Cambios
            </button>
        </div>
    </div>
</div>


<script>
// Variables globales
let productoSeleccionadoEPP = null;
let eppAgregadosList = []; // Lista de EPP agregados

function abrirModalAgregarEPP() {
    console.log('📖 [abrirModalAgregarEPP] Abriendo modal');
    const modal = document.getElementById('modalAgregarEPP');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Solo resetear si no estamos en modo edición
    if (!eppEnEdicion) {
        console.log('📖 [abrirModalAgregarEPP] Modo normal - resetear modal');
        resetearModalAgregarEPP();
    } else {
        console.log('📖 [abrirModalAgregarEPP] Modo edición - NO resetear modal');
    }
}

function cerrarModalAgregarEPP() {
    console.log('🔒 [cerrarModalAgregarEPP] Cerrando modal');
    const modal = document.getElementById('modalAgregarEPP');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    eppAgregadosList = []; // Limpiar lista al cerrar
}

function resetearModalAgregarEPP() {
    productoSeleccionadoEPP = null;
    eppAgregadosList = [];
    eppEnEdicion = null;
    document.getElementById('inputBuscadorEPP').value = '';
    document.getElementById('productoCardEPP').style.display = 'none';
    document.getElementById('formularioAgregarEPP').style.display = 'none';
    document.getElementById('observacionesContainer').style.display = 'none';
    document.getElementById('listaEPPAgregados').style.display = 'none';
    document.getElementById('cuerpoTablaEPP').innerHTML = '';
    document.getElementById('cantidadEPP').disabled = true;
    document.getElementById('cantidadEPP').value = '1';
    document.getElementById('observacionesEPP').disabled = true;
    document.getElementById('observacionesEPP').value = '';
    document.getElementById('btnAgregarALista').disabled = true;
    document.getElementById('btnAgregarALista').style.display = 'none';
    document.getElementById('btnFinalizarAgregarEPP').disabled = true;
    document.getElementById('btnFinalizarAgregarEPP').style.display = 'flex';
    document.getElementById('btnGuardarCambiosEPP').disabled = true;
    document.getElementById('btnGuardarCambiosEPP').style.display = 'none';
    
    actualizarEstilosCampos();
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
    if (window.eppService && typeof window.eppService.filtrarEPP === 'function') {
        window.eppService.filtrarEPP(busqueda);
        // El servicio llenará automáticamente resultadosBuscadorEPP
    } else {
        console.warn('eppService.filtrarEPP no disponible');
    }
}

function mostrarProductoEPP(producto) {
    console.log(' [mostrarProductoEPP] Llamado con producto:', producto);
    productoSeleccionadoEPP = producto;
    
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

    // Mostrar sección de fotos
    const seccionFotos = document.getElementById('seccionFotosEPP');
    console.log(' [mostrarProductoEPP] Sección fotos encontrada:', !!seccionFotos);
    if (seccionFotos) {
        seccionFotos.style.display = 'block';
        console.log(' [mostrarProductoEPP] Sección fotos visible - display:', seccionFotos.style.display);
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


// Función cargarTallasEPP removida - talla incluida en nombre_completo

// Función para convertir URLs blob a archivos para envío como FormData (no base64 en JSON)
async function convertirBlobAArchivos(imagenes) {
    const imagenesConvertidas = [];
    
    for (const imagen of imagenes) {
        if (imagen.previewUrl && imagen.file) {
            // Obtener ID del pedido actual (temporal hasta que se cree el pedido)
            const pedidoId = window.pedidoIdActual || 'temp';
            
            // Generar nombre único para evitar conflictos
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
                tamaño: imagen.tamaño,
                file: imagen.file, // Para FormData
                previewUrl: imagen.previewUrl, // Para mostrar en UI
                
                // Metadatos para guardado en BD (sin base64)
                ruta_storage: rutaStorage, // pedidos/25/epp
                nombre_archivo: nombreArchivo, // 123456_abc123_images.jfif
                ruta_completa: rutaCompleta, // pedidos/25/epp/123456_abc123_images.jfif
                ruta_web: rutaWeb, // /storage/pedidos/25/epp/123456_abc123_images.jfif
                
                // Para tabla pedido_epp_imagenes
                pedido_epp_id: null, // Se asignará cuando se guarde el EPP
                principal: 0, // 0 = no principal, 1 = principal
                orden: imagenesConvertidas.length + 1 // Orden automático
            });
            
            console.log(`[convertirBlobAArchivos] Imagen preparada: ${imagen.nombre} -> ${rutaWeb}`);
        } else {
            // Si ya es base64 u otro formato, mantenerla (para compatibilidad)
            imagenesConvertidas.push(imagen);
        }
    }
    
    return imagenesConvertidas;
}

// Función para preparar datos para envío: JSON limpio + FormData para imágenes
function prepararDatosParaEnvio(itemsPedido) {
    const datosLimpios = [];
    const formData = new FormData();
    
    itemsPedido.forEach((item, index) => {
        if (item.tipo === 'epp' && item.imagenes && item.imagenes.length > 0) {
            // Procesar EPP con imágenes
            const eppData = {
                uid: item.uid || `uid-${Date.now()}-${Math.random().toString(36).substring(2, 8)}`,
                epp_id: item.epp_id,
                nombre_epp: item.nombre_epp,
                categoria: item.categoria || '',
                cantidad: item.cantidad,
                observaciones: item.observaciones,
                imagenes: [] // Array vacío, las imágenes van en FormData
            };
            
            // Agregar cada imagen al FormData
            item.imagenes.forEach((imagen, imgIndex) => {
                if (imagen.file) {
                    // Agregar archivo al FormData con nombre único
                    const fieldName = `epp_imagen_${index}_${imgIndex}`;
                    formData.append(fieldName, imagen.file);
                    
                    // Agregar metadatos de la imagen al FormData
                    formData.append(`${fieldName}_metadata`, JSON.stringify({
                        id: imagen.id,
                        nombre: imagen.nombre,
                        extension: imagen.extension,
                        tamaño: imagen.tamaño,
                        ruta_storage: imagen.ruta_storage,
                        nombre_archivo: imagen.nombre_archivo,
                        ruta_completa: imagen.ruta_completa,
                        ruta_web: imagen.ruta_web,
                        pedido_epp_id: imagen.pedido_epp_id,
                        principal: imagen.principal,
                        orden: imagen.orden
                    }));
                    
                    // Agregar referencia en el EPP (sin base64)
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
            // Agregar otros items sin modificar
            datosLimpios.push(item);
        }
    });
    
    return {
        jsonData: datosLimpios,
        formData: formData
    };
}

// Hacer la función disponible globalmente
window.prepararDatosParaEnvio = prepararDatosParaEnvio;

function agregarEPPALista() {
    if (!productoSeleccionadoEPP) {
        alert('Por favor selecciona un producto');
        return;
    }

    const cantidad = document.getElementById('cantidadEPP').value;
    const observaciones = document.getElementById('observacionesEPP').value || '-';

    if (!cantidad || cantidad <= 0) {
        alert('Por favor ingresa una cantidad válida');
        return;
    }

    // Agregar a la lista (usar las fotos tal como están, solo URLs blob temporales)
    const eppData = {
        id: productoSeleccionadoEPP.id,
        nombre_completo: productoSeleccionadoEPP.nombre_completo || productoSeleccionadoEPP.nombre,
        cantidad: parseInt(cantidad),
        observaciones: observaciones,
        imagenes: [...window.fotosEPP], // Mantener fotos con URLs blob (temporal)
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

    // 🔑 IMPORTANTE: Limpiar completamente el formulario
    // Ocultar tarjeta y formulario
    document.getElementById('productoCardEPP').style.display = 'none';
    document.getElementById('formularioAgregarEPP').style.display = 'none';
    document.getElementById('observacionesContainer').style.display = 'none';
    document.getElementById('btnAgregarALista').style.display = 'none';

    // Resetear valores
    document.getElementById('cantidadEPP').value = '1';
    document.getElementById('observacionesEPP').value = '';
    
    // 🔑 IMPORTANTE: Limpiar fotos del contenedor (ya están asociadas al EPP)
    limpiarFotosEPP();
    
    // 🔑 IMPORTANTE: Limpiar buscador y desseleccionar producto
    document.getElementById('inputBuscadorEPP').value = '';
    productoSeleccionadoEPP = null;
}

function limpiarFotosEPP() {
    console.log('🧹 [limpiarFotosEPP] Limpiando fotos del contenedor EPP');
    
    // NO liberar URLs blob si están asociadas a EPPs agregados
    // Las URLs blob se mantendrán para mostrar en las tarjetas
    // Solo limpiar el array temporal del contenedor
    window.fotosEPP = [];
    
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
        
        // Asegurar que la sección de fotos esté visible
        document.getElementById('seccionFotosEPP').style.display = 'block';
        
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
        console.log(`📌 [renderizarTablaEPP] Renderizando EPP ${idx + 1}:`, epp.nombre_completo);
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
                    onclick="eliminarEPPDeLista(${idx})"
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

function eliminarEPPDeLista(idx) {
    eppAgregadosList.splice(idx, 1);
    renderizarTablaEPP();

    if (eppAgregadosList.length === 0) {
        document.getElementById('listaEPPAgregados').style.display = 'none';
        document.getElementById('btnFinalizarAgregarEPP').disabled = true;
    }
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
        console.log('📤 [guardarNuevoEPP] Creando EPP con nombre:', nombreCompleto);
        
        // Crear el EPP en la base de datos
        const response = await fetch('/api/epp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({
                nombre_completo: nombreCompleto,
                categoria_id: 19,  // Categoría fija como especificaste
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

        // Cerrar el formulario de creación
        cerrarFormularioCrearEPP();
        
        // Mostrar notificación diferente si ya existía
        if (resultado.existia) {
            Swal.fire({
                icon: 'info',
                title: 'EPP existente',
                text: 'Este EPP ya existe. Se está utilizando el existente.',
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
let eppEnEdicion = null;  // Para guardar el índice del EPP que se está editando

function editarEPPAgregado(eppData) {
    console.log('✏️ [editarEPPAgregado] INICIANDO - Editando EPP:', eppData);
    
    // Guardar referencia del EPP en edición
    eppEnEdicion = eppData;
    console.log('✏️ [editarEPPAgregado] eppEnEdicion configurado:', !!eppEnEdicion);
    
    // Limpiar la lista de agregados para modo edición
    eppAgregadosList = [];
    productoSeleccionadoEPP = null;
    console.log('✏️ [editarEPPAgregado] Lista de agregados y producto limpiados');
    
    // Limpiar buscador
    const buscador = document.getElementById('inputBuscadorEPP');
    if (buscador) buscador.value = '';
    const resultados = document.getElementById('resultadosBuscadorEPP');
    if (resultados) resultados.style.display = 'none';
    console.log('✏️ [editarEPPAgregado] Buscador limpiado');
    
    // Limpiar elementos visuales previos
    const tarjetaCard = document.getElementById('productoCardEPP');
    if (tarjetaCard) tarjetaCard.style.display = 'none';
    const lista = document.getElementById('listaEPPAgregados');
    if (lista) lista.style.display = 'none';
    const cuerpo = document.getElementById('cuerpoTablaEPP');
    if (cuerpo) cuerpo.innerHTML = '';
    console.log('✏️ [editarEPPAgregado] Elementos visuales previos limpiados');
    
    // Mostrar el producto seleccionado
    console.log('✏️ [editarEPPAgregado] Llamando a mostrarProductoEPP...');
    mostrarProductoEPP({
        id: eppData.epp_id || eppData.id,
        nombre_completo: eppData.nombre_epp || eppData.nombre,
        nombre: eppData.nombre_epp || eppData.nombre,
        imagen: ''
    });
    console.log('✏️ [editarEPPAgregado] mostrarProductoEPP completado');
    
    // Cargar valores en el formulario
    const cantidadInput = document.getElementById('cantidadEPP');
    const obsInput = document.getElementById('observacionesEPP');
    if (cantidadInput) cantidadInput.value = eppData.cantidad || 1;
    if (obsInput) obsInput.value = eppData.observaciones || '';
    console.log('✏️ [editarEPPAgregado] Valores cargados - cantidad:', eppData.cantidad, 'observaciones:', eppData.observaciones);
    
    // Mostrar los campos del formulario
    const formulario = document.getElementById('formularioAgregarEPP');
    const obsContainer = document.getElementById('observacionesContainer');
    const btnAgregar = document.getElementById('btnAgregarALista');
    const btnGuardarCambios = document.getElementById('btnGuardarCambiosEPP');
    const btnFinalizar = document.getElementById('btnFinalizarAgregarEPP');
    
    if (formulario) {
        formulario.style.display = 'grid';
        console.log('✏️ [editarEPPAgregado] Formulario mostrado');
    }
    if (obsContainer) {
        obsContainer.style.display = 'block';
        console.log('✏️ [editarEPPAgregado] Contenedor observaciones mostrado');
    }
    
    // Ocultar botón de agregar a lista (no se usa en modo edición)
    if (btnAgregar) {
        btnAgregar.style.display = 'none';
        console.log('✏️ [editarEPPAgregado] Botón agregar a lista ocultado');
    }
    
    // Habilitar campos para edición
    if (cantidadInput) cantidadInput.disabled = false;
    if (obsInput) obsInput.disabled = false;
    console.log('✏️ [editarEPPAgregado] Campos habilitados');
    
    // Configurar botones del footer
    if (btnFinalizar) {
        btnFinalizar.style.display = 'none';
        btnFinalizar.disabled = true;
        console.log('✏️ [editarEPPAgregado] Botón finalizar ocultado');
    }
    if (btnGuardarCambios) {
        btnGuardarCambios.style.display = 'flex';
        btnGuardarCambios.disabled = false;
        console.log('✏️ [editarEPPAgregado] Botón guardar cambios mostrado y habilitado');
    }
    
    // Actualizar estilos de campos
    actualizarEstilosCampos();
    
    console.log('✏️ [editarEPPAgregado] Preparado para edición, abriendo modal...');
    
    // Mostrar modal
    abrirModalAgregarEPP();
    
    console.log('✏️ [editarEPPAgregado] FINALIZADO - Modal abierto en modo edición');
}

function guardarEdicionEPP() {
    if (!eppEnEdicion) {
        console.error(' No hay EPP en edición');
        return;
    }

    const nombre = document.getElementById('nombreProductoEPP').value;
    const cantidad = document.getElementById('cantidadEPP').value;
    const observaciones = document.getElementById('observacionesEPP').value;

    if (!cantidad || cantidad <= 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Cantidad inválida',
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

    console.log('💾 [guardarEdicionEPP] Guardando cambios para EPP:', {
        epp_id: eppEnEdicion.epp_id,
        nombre: nombre,
        cantidad: cantidad,
        observaciones: observaciones
    });

    // Actualizar en window.itemsPedido
    const index = window.itemsPedido.findIndex(item => item.epp_id === eppEnEdicion.epp_id || item.epp_id === eppEnEdicion.id);
    if (index !== -1) {
        window.itemsPedido[index].nombre_epp = nombre;
        window.itemsPedido[index].cantidad = parseInt(cantidad);
        window.itemsPedido[index].observaciones = observaciones || '-';
        console.log(' [guardarEdicionEPP] EPP actualizado en window.itemsPedido:', window.itemsPedido[index]);
    } else {
        console.warn(' [guardarEdicionEPP] No se encontró EPP en window.itemsPedido para actualizar');
    }

    // Actualizar visualmente en la tarjeta
    const tarjeta = document.querySelector(`.item-epp[data-item-id="${eppEnEdicion.epp_id || eppEnEdicion.id}"]`);
    if (tarjeta) {
        // Buscar el h4 que contiene el nombre y actualizarlo
        const titulo = tarjeta.querySelector('h4');
        if (titulo) {
            titulo.textContent = nombre;
            console.log(' [guardarEdicionEPP] Nombre actualizado en tarjeta a:', nombre);
        }
        // Buscar los párrafos que contienen cantidad y observaciones
        const paragrafos = tarjeta.querySelectorAll('p');
        // El segundo párrafo de los detalles contiene la cantidad
        if (paragrafos.length > 1) {
            paragrafos[1].textContent = cantidad;  // Actualizar cantidad
            console.log(' [guardarEdicionEPP] Cantidad actualizada en tarjeta de', cantidad);
        }
        if (paragrafos.length > 3) {
            paragrafos[3].textContent = observaciones || '-';  // Actualizar observaciones
            console.log(' [guardarEdicionEPP] Observaciones actualizadas en tarjeta');
        }
    } else {
        console.warn(' [guardarEdicionEPP] Tarjeta no encontrada en DOM');
    }

    // Limpiar referencia
    eppEnEdicion = null;
    
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
    
    // Cerrar modal
    cerrarModalAgregarEPP();

    // Mostrar toast de éxito
    Swal.fire({
        icon: 'success',
        title: 'EPP actualizado',
        text: 'Los cambios se han guardado correctamente',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        customClass: {
            container: 'toast-epp-container'
        }
    });
    
    console.log(' [guardarEdicionEPP] Edición completada');
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

    console.log(' [finalizarAgregarEPP] Finalizando con EPP:', eppAgregadosList);
    
    // Inicializar window.itemsPedido si no existe
    if (!window.itemsPedido) {
        window.itemsPedido = [];
    }
    
    // Convertir imágenes de todos los EPPs de forma asíncrona
    const promesasEPP = eppAgregadosList.map(async (epp) => {
        console.log(`📌 [finalizarAgregarEPP] Procesando EPP: ${epp.nombre_completo}`);
        
        // Convertir URLs blob a archivos para guardado
        const imagenesParaGuardar = await convertirBlobAArchivos(epp.imagenes);
        
        // Usar eppItemManager para crear la tarjeta visual
        if (window.eppItemManager && typeof window.eppItemManager.crearItem === 'function') {
            window.eppItemManager.crearItem(
                epp.id,                    // id
                epp.nombre_completo,        // nombre
                'EPP',                     // categoria
                epp.cantidad,              // cantidad
                epp.observaciones,         // observaciones
                imagenesParaGuardar       // imagenes convertidas a archivos
            );
            console.log(` [finalizarAgregarEPP] EPP agregado a tarjeta: ${epp.nombre_completo}`);
        } else {
            console.warn(' [finalizarAgregarEPP] eppItemManager no disponible');
        }
        
        // También guardar en window.itemsPedido para que se envíe al servidor
        const eppData = {
            tipo: 'epp',
            epp_id: epp.id,
            nombre_epp: epp.nombre_completo,
            cantidad: epp.cantidad,
            observaciones: epp.observaciones,
            imagenes: imagenesParaGuardar // Usar imágenes convertidas a archivos
        };
        
        return eppData;
    });
    
    // Esperar a que todas las conversiones terminen
    try {
        const eppsProcesados = await Promise.all(promesasEPP);
        
        // Agregar todos los EPPs procesados a window.itemsPedido
        eppsProcesados.forEach((eppData) => {
            window.itemsPedido.push(eppData);
            console.log(` [finalizarAgregarEPP] EPP guardado en window.itemsPedido:`, eppData);
            
            //  CRÍTICO: También registrar en gestionItemsUI para mantener sincronizado
            if (window.gestionItemsUI && typeof window.gestionItemsUI.agregarEPPAlOrden === 'function') {
                window.gestionItemsUI.agregarEPPAlOrden(eppData);
                console.log(` [finalizarAgregarEPP] EPP registrado en gestionItemsUI:`, eppData.nombre_epp);
            } else {
                console.warn(' [finalizarAgregarEPP] gestionItemsUI no disponible');
            }
        });
        
        console.log(' [finalizarAgregarEPP] Todos los EPP han sido procesados y agregados');
        console.log(' [finalizarAgregarEPP] window.itemsPedido actual:', window.itemsPedido);
        
        cerrarModalAgregarEPP();
        
    } catch (error) {
        console.error('[finalizarAgregarEPP] Error procesando EPPs:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Ocurrió un error al procesar las imágenes. Por favor intenta nuevamente.',
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

// Exportar función globalmente para que sea accesible desde otros scripts
window.abrirModalAgregarEPP = abrirModalAgregarEPP;
window.cerrarModalAgregarEPP = cerrarModalAgregarEPP;
window.editarEPPAgregado = editarEPPAgregado;
window.guardarEdicionEPP = guardarEdicionEPP;

// Funciones para manejar fotos de EPP
window.fotosEPP = [];
window.fotosEPPEliminadas = [];

function agregarFotoEPP() {
    document.getElementById('inputFotosEPP').click();
}

function manejarSubidaFotosEPP(input) {
    const archivos = input.files;
    const pedidoId = window.pedidoIdActual || 31; // ID del pedido actual
    
    console.log(`📸 [manejarSubidaFotosEPP] Seleccionados ${archivos.length} archivos para el pedido ${pedidoId}`);
    
    Array.from(archivos).forEach((archivo, index) => {
        const nombreArchivo = archivo.name;
        const extension = nombreArchivo.split('.').pop().toLowerCase();
        
        // Validar que sea una imagen
        if (!['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'jfif'].includes(extension)) {
            console.warn(`[manejarSubidaFotosEPP] Archivo no válido: ${nombreArchivo}`);
            return;
        }
        
        // Crear URL blob para la imagen
        const previewUrl = URL.createObjectURL(archivo);
        
        // Crear objeto de imagen con URL blob
        const imagen = {
            id: Date.now() + '_' + index,
            file: archivo, // Mantener referencia al archivo original
            previewUrl: previewUrl, // URL blob para mostrar
            nombre: nombreArchivo,
            extension: extension,
            tamaño: archivo.size,
            pedido_epp_id: null, // Se asignará al guardar
            ruta_original: null,
            ruta_webp: null,
            principal: 0,
            orden: 0
        };
        
        // Agregar a la lista temporal
        window.fotosEPP.push(imagen);
        
        // Mostrar vista previa
        mostrarVistaPreviaFoto(imagen);
        
        console.log(`📸 [manejarSubidaFotosEPP] Foto agregada: ${nombreArchivo} (${(archivo.size / 1024).toFixed(2)} KB)`);
    });
    
    // Limpiar input para permitir seleccionar el mismo archivo nuevamente
    input.value = '';
}

function mostrarVistaPreviaFoto(imagen) {
    const contenedor = document.getElementById('contenedorFotosEPP');
    
    // Ocultar mensaje inicial si está visible
    const mensajeDragDrop = document.getElementById('mensajeDragDrop');
    if (mensajeDragDrop) {
        mensajeDragDrop.style.display = 'none';
    }
    
    // Crear elemento de imagen con atributo data-foto-id
    const divImagen = document.createElement('div');
    divImagen.className = 'relative group foto-epp-item';
    divImagen.setAttribute('data-foto-id', imagen.id);
    divImagen.innerHTML = `
        <div class="relative overflow-hidden rounded-lg border-2 border-gray-200">
            <img src="${imagen.previewUrl}" alt="Foto EPP" class="w-full h-32 object-cover">
            <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                <button type="button" onclick="eliminarFotoEPP('${imagen.id}')" class="bg-red-500 text-white p-1 rounded-full opacity-0 group-hover:opacity-100 transition-opacity">
                    <i class="mostrar-symbols-rounded text-sm">delete</i>
                </button>
            </div>
            <div class="absolute bottom-0 right-0 bg-blue-600 text-white text-xs px-2 py-1 rounded-tl">
                ${imagen.nombre}
            </div>
        </div>
    `;
    
    contenedor.appendChild(divImagen);
    
    // Mostrar sección de fotos si no está visible
    document.getElementById('seccionFotosEPP').style.display = 'block';
}

function eliminarFotoEPP(fotoId) {
    console.log(`[eliminarFotoEPP] Intentando eliminar foto con ID: ${fotoId}`);
    console.log(`[eliminarFotoEPP] Fotos actuales:`, window.fotosEPP.map(f => ({ id: f.id, nombre: f.nombre })));
    
    // Encontrar la foto para liberar la URL blob
    const fotoAEliminar = window.fotosEPP.find(foto => foto.id === fotoId);
    
    // Eliminar de la lista temporal
    window.fotosEPP = window.fotosEPP.filter(foto => foto.id !== fotoId);
    
    // Eliminar del DOM usando el selector más específico
    const elemento = document.querySelector(`#contenedorFotosEPP div.foto-epp-item[data-foto-id="${fotoId}"]`);
    console.log(`[eliminarFotoEPP] Buscando elemento con selector: #contenedorFotosEPP div.foto-epp-item[data-foto-id="${fotoId}"]`);
    console.log(`[eliminarFotoEPP] Elemento encontrado:`, elemento);
    console.log(`[eliminarFotoEPP] Elemento ID:`, elemento?.id);
    console.log(`[eliminarFotoEPP] Elemento classes:`, elemento?.className);
    console.log(`[eliminarFotoEPP] Elemento es contenedor principal:`, elemento?.id === 'contenedorFotosEPP');
    
    if (elemento) {
        // Verificación extra de seguridad
        if (elemento.id === 'contenedorFotosEPP') {
            console.error(`[eliminarFotoEPP] ¡ERROR! Se encontró el contenedor principal en lugar de la imagen. Abortando eliminación.`);
            return;
        }
        elemento.remove();
        console.log(`[eliminarFotoEPP] Elemento DOM eliminado para ID: ${fotoId}`);
    } else {
        console.warn(`[eliminarFotoEPP] No se encontró elemento con ID: ${fotoId}`);
        
        // Intentar buscar por el nombre del archivo como fallback
        const elementos = document.querySelectorAll('#contenedorFotosEPP div.foto-epp-item');
        console.log(`[eliminarFotoEPP] Elementos con clase foto-epp-item encontrados:`, elementos.length);
        elementos.forEach((elemento, index) => {
            console.log(`[eliminarFotoEPP] Elemento ${index}:`, elemento.id, elemento.className);
            const img = elemento.querySelector('img');
            if (img && img.alt && img.alt.includes(fotoId)) {
                console.log(`[eliminarFotoEPP] Eliminando elemento ${index} por nombre: ${img.alt}`);
                elemento.remove();
                return;
            }
        });
    }
    
    // Liberar la URL blob para liberar memoria
    if (fotoAEliminar && fotoAEliminar.previewUrl) {
        URL.revokeObjectURL(fotoAEliminar.previewUrl);
        console.log(`[eliminarFotoEPP] URL blob liberada para: ${fotoAEliminar.nombre}`);
    }
    
    console.log(`[eliminarFotoEPP] Fotos restantes: ${window.fotosEPP.length}`);
    
    // Si no hay más fotos, mostrar mensaje inicial
    if (window.fotosEPP.length === 0) {
        const mensajeDragDrop = document.getElementById('mensajeDragDrop');
        if (mensajeDragDrop) {
            mensajeDragDrop.style.display = 'flex';
        }
        // No ocultar la sección completa, solo mostrar el mensaje inicial
        // document.getElementById('seccionFotosEPP').style.display = 'none'; // Comentar esta línea
    }
}

// Funciones para Drag and Drop
function handleDragOverEPP(event) {
    event.preventDefault();
    event.stopPropagation();
    
    const contenedor = document.getElementById('contenedorFotosEPP');
    contenedor.classList.add('border-blue-400', 'bg-blue-50');
    contenedor.classList.remove('border-gray-300');
    
    // Ocultar mensaje inicial si hay imágenes
    if (window.fotosEPP.length > 0) {
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
    
    // Mostrar mensaje inicial si no hay imágenes
    if (window.fotosEPP.length === 0) {
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
    const pedidoId = window.pedidoIdActual || 31;
    
    console.log(`📸 [handleDropEPP] Se arrastraron ${archivos.length} archivos para el pedido ${pedidoId}`);
    
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
            console.warn(`[handleDropEPP] Archivo no válido: ${nombreArchivo}`);
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
            tamaño: archivo.size,
            pedido_epp_id: null,
            ruta_original: null,
            ruta_webp: null,
            principal: 0,
            orden: 0
        };
        
        // Agregar a la lista temporal
        window.fotosEPP.push(imagen);
        
        // Mostrar vista previa
        mostrarVistaPreviaFoto(imagen);
        
        console.log(`[handleDropEPP] Foto arrastrada: ${nombreArchivo} (${(archivo.size / 1024).toFixed(2)} KB)`);
    });
}
</script>

<style>
    /* Asegurar que los toasts EPP aparezcan encima de todo */
    .toast-epp-container {
        z-index: 9999999 !important;
    }
</style>

