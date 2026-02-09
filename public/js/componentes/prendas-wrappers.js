/**
 * ================================================
 * WRAPPERS DELEGADORES: prendas.js
 * ================================================
 * 
 * Archivo separado con funciones proxy que delegan
 * a los módulos especializados (GestionItemsUI, etc.)
 * 
 * Mantiene compatibilidad hacia atrás sin duplicar lógica
 * 
 * CARGA: Después de prendas.js
 */

/**
 * WRAPPER: Abre el modal para agregar una prenda nueva
 * Delega a GestionItemsUI.abrirModalAgregarPrendaNueva()
 */
window.abrirModalPrendaNueva = function() {

    
    // Intentar usar GestionItemsUI si existe
    if (window.gestionItemsUI && typeof window.gestionItemsUI.abrirModalAgregarPrendaNueva === 'function') {

        return window.gestionItemsUI.abrirModalAgregarPrendaNueva();
    }
    
    // Fallback: abrir el modal directamente si existe
    const modal = document.getElementById('modal-agregar-prenda-nueva');
    if (modal) {
        // 🔥 Asegurar que estamos en modo CREATE (prendaEditIndex = null)
        if (window.gestionItemsUI) {
            window.gestionItemsUI.prendaEditIndex = null;
        }
        window.prendaEditIndex = null;
        
        // 🔥 Limpiar telas residuales ANTES de abrir el modal
        if (window.telasAgregadas) {
            window.telasAgregadas = [];
        }
        if (window.telasCreacion) {
            window.telasCreacion = [];
        }
        const tbodyTelas = document.getElementById('tbody-telas');
        if (tbodyTelas) {
            tbodyTelas.innerHTML = '';
        }

        modal.style.display = 'flex';
        // Limpiar formulario
        limpiarFormulario();
    } else {

    }
};

/**
 * WRAPPER: Cierra el modal de prenda nueva
 * Delega a GestionItemsUI.cerrarModalAgregarPrendaNueva()
 */
window.cerrarModalPrendaNueva = function() {

    // 🔥 CRÍTICO: Resetear prendaEditIndex PRIMERO para evitar confundir CREATE con EDIT
    if (window.gestionItemsUI) {
        window.gestionItemsUI.prendaEditIndex = null;
    }
    window.prendaEditIndex = null;
    
    // 🔥 CRÍTICO: Limpiar COMPLETAMENTE todos los contenedores visuales al cerrar
    // Esto asegura que no haya residuos visuales entre modal open/close
    if (typeof ModalCleanup !== 'undefined') {
        ModalCleanup.limpiarContenedores();
    }
    
    // Cerrar el modal directamente
    const modal = document.getElementById('modal-agregar-prenda-nueva');
    if (modal) {
        modal.style.setProperty('display', 'none', 'important');
        modal.classList.remove('active');

        
        //  NUEVO: Resetear texto del botón a "Agregar Prenda"
        const btnGuardar = document.getElementById('btn-guardar-prenda');
        if (btnGuardar) {
            btnGuardar.innerHTML = '<span class="material-symbols-rounded">check</span>Agregar Prenda';

        }
        
        //  SEGURIDAD: Limpiar SOLO el formulario del modal de prenda (form-prenda-nueva)
        // NUNCA limpiar el formulario principal (formCrearPedidoEditable)
        const form = document.getElementById('form-prenda-nueva');
        if (form) {
            form.reset();

        }
        
        //  SEGURIDAD: SOLO limpiar campos ESPECÍFICOS del modal de prenda
        // Esto previene que se limpien accidentalmente campos del formulario principal
        const inputsLimpiarModal = [
            'nueva-prenda-nombre',
            'nueva-prenda-descripcion',
            'nueva-prenda-origen-select',
            'nueva-prenda-tela',
            'nueva-prenda-color',
            'nueva-prenda-referencia'
        ];
        
        inputsLimpiarModal.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field && fieldId.startsWith('nueva-prenda-')) {  // Extra validación: solo IDs que comienzan con 'nueva-prenda-'
                field.value = '';

            }
        });
        
        // 🔥 CRÍTICO: Limpiar TELAS - Array y DOM
        // Array en memoria
        if (window.telasAgregadas) {
            window.telasAgregadas = [];
        }
        // También limpiar telasCreacion si existe
        if (window.telasCreacion) {
            window.telasCreacion = [];
        }
        // 🔥 Limpiar tabla DOM de telas
        const tbodyTelas = document.getElementById('tbody-telas');
        if (tbodyTelas) {
            tbodyTelas.innerHTML = '';
        }
        
        // Limpiar imágenes de prenda
        if (window.imagenesPrendaStorage) {
            window.imagenesPrendaStorage.limpiar();

        }
        
        // Limpiar cantidades de tallas (relacional primaria)
        if (window.tallasRelacionales) {
            window.tallasRelacionales.DAMA = {};
            window.tallasRelacionales.CABALLERO = {};
            window.tallasRelacionales.UNISEX = {};

        }
        
        // Limpieza completada
        
        // Limpiar tallas seleccionadas
        if (window.tallasSeleccionadas) {
            window.tallasSeleccionadas = {
                dama: { tallas: [], tipo: null },
                caballero: { tallas: [], tipo: null }
            };

        }
        
        // Limpiar checkboxes de variaciones
        const checkboxes = [
            'aplica-manga', 'aplica-bolsillos', 'aplica-broche',
            'checkbox-reflectivo', 'checkbox-bordado', 'checkbox-estampado',
            'checkbox-dtf', 'checkbox-sublimado'
        ];
        
        checkboxes.forEach(checkboxId => {
            const checkbox = document.getElementById(checkboxId);
            if (checkbox) {
                checkbox.checked = false;
            }
        });

        
        // Limpiar campos de variaciones
        const campos = [
            'manga-input', 'manga-obs',
            'bolsillos-obs',
            'broche-input', 'broche-obs',
            'reflectivo-obs'
        ];
        
        campos.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.value = '';
                field.disabled = true;
                field.style.opacity = '0.5';
            }
        });

        
        // Limpiar procesos seleccionados
        if (window.limpiarProcesosSeleccionados) {
            window.limpiarProcesosSeleccionados();

        }
    }
};

/**
 * WRAPPER: Agrega una prenda nueva al pedido
 * Delega a GestionItemsUI.agregarPrendaNueva()
 */
window.agregarPrendaNueva = function() {

    
    // Intentar usar GestionItemsUI si existe
    if (window.gestionItemsUI && typeof window.gestionItemsUI.agregarPrendaNueva === 'function') {

        return window.gestionItemsUI.agregarPrendaNueva();
    }
    

};

/**
 * WRAPPER: Carga un item en el modal para editar
 * Delega a GestionItemsUI.cargarItemEnModal()
 */
window.cargarItemEnModal = function(item, itemIndex) {

    
    // Intentar usar GestionItemsUI si existe
    if (window.gestionItemsUI && typeof window.gestionItemsUI.cargarItemEnModal === 'function') {

        return window.gestionItemsUI.cargarItemEnModal(item, itemIndex);
    }
    

};

/**
 * WRAPPER: Maneja la carga de imágenes para prendas
 * Delega a window.imagenesPrendaStorage (ImageStorageService)
 */
window.manejarImagenesPrenda = function(input) {

    
    if (!input.files || input.files.length === 0) {

        return;
    }
    
    try {
        // Verificar que el servicio existe
        if (!window.imagenesPrendaStorage) {

            alert('Error: Servicio de almacenamiento de imágenes no inicializado');
            return;
        }
        
        // Agregar imagen al storage - AHORA RETORNA PROMISE
        window.imagenesPrendaStorage.agregarImagen(input.files[0])
            .then(() => {

                actualizarPreviewPrenda();
            })
            .catch(err => {
                if (err.message === 'MAX_LIMIT') {

                    mostrarModalLimiteImagenes();
                } else if (err.message === 'INVALID_FILE') {

                    mostrarModalError('El archivo debe ser una imagen válida');
                } else {

                    mostrarModalError('Error al procesar la imagen: ' + err.message);
                }
            });
    } catch (err) {

        mostrarModalError('Error al procesar imagen: ' + err.message);
    }
    
    // Limpiar input para permitir seleccionar el mismo archivo nuevamente
    input.value = '';
};

/**
 * WRAPPER: Actualiza el preview de las imágenes de prenda
 * Usa window.imagenesPrendaStorage para obtener las imágenes
 */
window.actualizarPreviewPrenda = function() {
    console.log('[actualizarPreviewPrenda] 🎬 Iniciando actualización del preview');
    
    try {
        // Obtener elementos del DOM
        const preview = document.getElementById('nueva-prenda-foto-preview');
        const contador = document.getElementById('nueva-prenda-foto-contador');
        const btn = document.getElementById('nueva-prenda-foto-btn');
        
        console.log('[actualizarPreviewPrenda] 🔍 Elementos DOM:', {
            preview: preview ? 'ENCONTRADO' : 'NO ENCONTRADO',
            contador: contador ? 'ENCONTRADO' : 'NO ENCONTRADO',
            btn: btn ? 'ENCONTRADO' : 'NO ENCONTRADO'
        });
        
        if (!preview) {
            console.warn('[actualizarPreviewPrenda]  Preview element no encontrado');
            return;
        }
        
        // Verificar que el servicio existe
        if (!window.imagenesPrendaStorage) {
            console.warn('[actualizarPreviewPrenda]  imagenesPrendaStorage no disponible');
            return;
        }
        
        // Obtener imágenes
        const imagenes = window.imagenesPrendaStorage.obtenerImagenes();
        console.log('[actualizarPreviewPrenda] 📦 Imágenes cargadas:', imagenes.length);

        
        // Si no hay imágenes, mostrar placeholder
        if (imagenes.length === 0) {
            console.log('[actualizarPreviewPrenda] 📭 Sin imágenes, mostrando placeholder');
            preview.innerHTML = '<div style="text-align: center;"><div class="material-symbols-rounded" style="font-size: 2rem; color: #9ca3af; margin-bottom: 0.25rem;">add_photo_alternate</div><div style="font-size: 0.7rem; color: #9ca3af;">Click para agregar</div></div>';
            preview.style.cursor = 'pointer';
            if (contador) contador.textContent = '';
            if (btn) btn.style.display = 'block';
            return;
        }
        
        // Mostrar primera imagen
        console.log('[actualizarPreviewPrenda] 🖼️ Mostrando primera imagen');
        preview.innerHTML = '';
        preview.style.cursor = 'pointer';
        
        const img = document.createElement('img');
        img.src = imagenes[0].previewUrl;
        img.style.cssText = 'width: 100%; height: 100%; object-fit: cover; cursor: pointer;';
        
        console.log('[actualizarPreviewPrenda] 🎬 Src de imagen:', img.src);
        
        //  Solo agregar click handler al preview (no duplicar en la img)
        preview.onclick = (e) => {
            e.stopPropagation();
            mostrarGaleriaImagenesPrenda(imagenes, 0);
        };
        
        preview.appendChild(img);
        console.log('[actualizarPreviewPrenda]  Imagen agregada al preview');
        
        // Actualizar contador
        if (contador) {
            contador.textContent = imagenes.length === 1 ? '1 foto' : imagenes.length + ' fotos';
        }
        
        // Mostrar/ocultar botón "Agregar más"
        if (btn) {
            btn.style.display = imagenes.length < 3 ? 'block' : 'none';
        }
        

    } catch (e) {

    }
};

/**
 * WRAPPER: Abre el selector de archivos para agregar foto a prenda
 */
window.abrirSelectorPrendas = function() {

    const inputFotos = document.getElementById('nueva-prenda-foto-input');
    if (inputFotos) {
        inputFotos.click();
    } else {

    }
};

/**
 * WRAPPER: Maneja la carga de imágenes para telas
 */
window.manejarImagenTela = function(input) {

    
    if (!input.files || input.files.length === 0) {

        return;
    }
    
    try {
        // Verificar que el servicio existe
        if (!window.imagenesTelaStorage) {

            alert('Error: Servicio de almacenamiento de imágenes de tela no inicializado');
            return;
        }
        

        // Agregar imagen al storage - RETORNA UNA PROMISE
        const promesa = window.imagenesTelaStorage.agregarImagen(input.files[0]);
        


        
        // Manejar como Promise
        if (promesa instanceof Promise) {
            promesa
                .then((resultado) => {

                    if (typeof actualizarPreviewTela === 'function') {
                        actualizarPreviewTela();
                    } else {

                    }
                })
                .catch((error) => {

                    if (error.message === 'MAX_LIMIT') {

                        if (typeof mostrarModalLimiteImagenes === 'function') {
                            mostrarModalLimiteImagenes();
                        }
                    } else if (error.message === 'INVALID_FILE') {

                        mostrarModalError('El archivo debe ser una imagen válida');
                    } else {

                        mostrarModalError('Error al procesar la imagen: ' + error.message);
                    }
                });
        } else {
            // Fallback: si no es Promise, tratar como objeto sincrónico

            if (promesa && promesa.success === true) {

                if (typeof actualizarPreviewTela === 'function') {
                    actualizarPreviewTela();
                }
            } else if (promesa && promesa.reason === 'MAX_LIMIT') {
                if (typeof mostrarModalLimiteImagenes === 'function') {
                    mostrarModalLimiteImagenes();
                }
            } else if (promesa && promesa.reason === 'INVALID_FILE') {
                mostrarModalError('El archivo debe ser una imagen válida');
            } else {

                mostrarModalError('Error al procesar la imagen');
            }
        }
    } catch (err) {

        mostrarModalError('Error al procesar imagen: ' + err.message);
    }
    
    // Limpiar input
    input.value = '';
};

/**
 * WRAPPER: Actualiza el preview temporal de imágenes de tela
 * Renderiza DENTRO de la celda de imagen de la fila de inputs
 */
window.actualizarPreviewTela = function() {

    
    try {
        const preview = document.getElementById('nueva-prenda-tela-preview');
        
        if (!preview) {

            return;
        }
        
        // Verificar que el servicio existe
        if (!window.imagenesTelaStorage) {

            return;
        }
        
        // Obtener imágenes del storage temporal
        const imagenes = window.imagenesTelaStorage.obtenerImagenes();

        
        // Limpiar preview anterior
        preview.innerHTML = '';
        
        // Si hay imágenes, mostrarlas como miniaturas dentro de la celda
        if (imagenes.length > 0) {
            // Hacer visible el preview cuando hay imágenes (dentro de la celda)
            preview.style.display = 'flex';
            preview.style.visibility = 'visible';
            preview.style.opacity = '1';
            preview.style.height = 'auto';
            preview.style.overflow = 'visible';
            
            imagenes.forEach((img, index) => {
                const container = document.createElement('div');
                container.style.cssText = 'position: relative; width: 60px; height: 60px; flex-shrink: 0;';
                
                const imgElement = document.createElement('img');
                imgElement.src = img.previewUrl;
                imgElement.style.cssText = 'width: 100%; height: 100%; object-fit: cover; border-radius: 4px; border: 2px solid #0066cc; cursor: pointer; transition: opacity 0.2s;';
                imgElement.onclick = () => window.mostrarGaleriaImagenesTemporales(imagenes, index);
                imgElement.onmouseover = () => imgElement.style.opacity = '0.7';
                imgElement.onmouseout = () => imgElement.style.opacity = '1';
                
                const btnEliminar = document.createElement('button');
                btnEliminar.type = 'button';
                btnEliminar.innerHTML = '×';
                btnEliminar.style.cssText = 'position: absolute; top: -8px; right: -8px; width: 24px; height: 24px; background: #ef4444; color: white; border: none; border-radius: 50%; cursor: pointer; font-size: 16px; padding: 0; display: flex; align-items: center; justify-content: center; font-weight: bold; transition: background 0.2s;';
                btnEliminar.onmouseover = () => btnEliminar.style.background = '#dc2626';
                btnEliminar.onmouseout = () => btnEliminar.style.background = '#ef4444';
                btnEliminar.onclick = (e) => {
                    e.preventDefault();
                    e.stopPropagation();

                    window.imagenesTelaStorage.eliminarImagen(index);
                    actualizarPreviewTela(); // Actualizar el preview después de eliminar
                };
                
                container.appendChild(imgElement);
                container.appendChild(btnEliminar);
                preview.appendChild(container);
            });
            

        } else {
            // Ocultar preview si no hay imágenes
            preview.style.display = 'none';
        }
    } catch (e) {

    }
};

/**
 * FUNCIÓN AUXILIAR: Limpiar formulario manualmente
 * Se usa como fallback si GestionItemsUI no está disponible
 */
function limpiarFormulario() {
    try {
        const inputs = [
            'nueva-prenda-nombre',
            'nueva-prenda-descripcion',
            'nueva-prenda-color',
            'nueva-prenda-tela',
            'nueva-prenda-referencia'
        ];
        
        inputs.forEach(id => {
            const input = document.getElementById(id);
            if (input) input.value = '';
        });
        
        // 🔥 CRÍTICO: Limpiar TELAS - Array y DOM
        if (window.telasAgregadas) {
            window.telasAgregadas = [];
        }
        if (window.telasCreacion) {
            window.telasCreacion = [];
        }
        const tbodyTelas = document.getElementById('tbody-telas');
        if (tbodyTelas) {
            tbodyTelas.innerHTML = '';
        }

    } catch (e) {

    }
}

/**
 * WRAPPER: Mostrar galería de imágenes de prenda (modal)
 * Versión simplificada para edición de prendas
 */
if (!window.mostrarGaleriaImagenesPrenda) {
    window.mostrarGaleriaImagenesPrenda = function(imagenes, prendaIndex = 0, indiceInicial = 0) {
        console.log('🖼️ [mostrarGaleriaImagenesPrenda] Abriendo galería con', imagenes?.length || 0, 'imágenes');
        console.log('🖼️ [mostrarGaleriaImagenesPrenda] Dimensiones de pantalla:', {
            vw: window.innerWidth,
            vh: window.innerHeight,
            '90vw': window.innerWidth * 0.9,
            '90vh': window.innerHeight * 0.9
        });
        
        if (!imagenes || imagenes.length === 0) {
            console.warn(' No hay imágenes para mostrar');
            return;
        }
        
        let indiceActual = indiceInicial;
        const imagenesValidas = imagenes.map(img => ({
            src: img.previewUrl || img.url || img.ruta || img.blobUrl || '',
            ...img
        })).filter(img => img.src);
        
        console.log('🖼️ [mostrarGaleriaImagenesPrenda] Imágenes válidas:', imagenesValidas.length);
        console.log('🖼️ [mostrarGaleriaImagenesPrenda] Primera imagen src:', imagenesValidas[0]?.src);
        
        if (imagenesValidas.length === 0) {
            console.warn(' No hay imágenes con URLs válidas');
            return;
        }
        
        // Crear modal
        const modal = document.createElement('div');
        modal.style.cssText = `
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background: rgba(0,0,0,0.95); display: flex; flex-direction: column;
            align-items: center; justify-content: center; z-index: 100001; 
            padding: 0; margin: 0;
        `;
        
        const imgElement = document.createElement('img');
        imgElement.src = imagenesValidas[indiceActual].src;
        imgElement.style.cssText = `
            min-width: 80vw; min-height: 60vh; max-width: 95vw; max-height: 90vh; 
            width: 90vw; height: 70vh; object-fit: cover; 
            border-radius: 8px; box-shadow: 0 20px 50px rgba(0,0,0,0.7);
        `;
        
        console.log('🖼️ [mostrarGaleriaImagenesPrenda] CSS aplicado a imgElement:', imgElement.style.cssText);
        console.log('🖼️ [mostrarGaleriaImagenesPrenda] Tamaño calculado:', {
            'min-width': '80vw = ' + (window.innerWidth * 0.80) + 'px',
            'min-height': '60vh = ' + (window.innerHeight * 0.60) + 'px',
            'width': '90vw = ' + (window.innerWidth * 0.90) + 'px',
            'height': '70vh = ' + (window.innerHeight * 0.70) + 'px',
            'max-width': '95vw = ' + (window.innerWidth * 0.95) + 'px',
            'max-height': '90vh = ' + (window.innerHeight * 0.90) + 'px'
        });
        
        // Agregar evento load para verificar dimensiones reales
        imgElement.onload = function() {
            console.log('🖼️ [mostrarGaleriaImagenesPrenda] Imagen cargada - Dimensiones reales:', {
                naturalWidth: this.naturalWidth,
                naturalHeight: this.naturalHeight,
                displayWidth: this.offsetWidth,
                displayHeight: this.offsetHeight,
                computedStyle: window.getComputedStyle(this).width,
                computedHeight: window.getComputedStyle(this).height
            });
        };
        
        imgElement.onerror = function() {
            console.error('🖼️ [mostrarGaleriaImagenesPrenda] Error al cargar imagen:', this.src);
        };
        
        // Toolbar
        const toolbar = document.createElement('div');
        toolbar.style.cssText = `
            display: flex; justify-content: center; gap: 1rem; margin-top: 1.5rem;
            padding: 1rem; background: rgba(0,0,0,0.8); border-radius: 8px;
        `;
        
        const contador = document.createElement('span');
        contador.style.cssText = 'color: white; font-size: 1rem; min-width: 80px; text-align: center;';
        
        const actualizarUI = () => {
            if (imagenesValidas.length === 0) {
                modal.remove();
                console.log(' Todas las imágenes fueron eliminadas, galería cerrada');
                return;
            }
            
            // Ajustar índice si es necesario
            if (indiceActual >= imagenesValidas.length) {
                indiceActual = imagenesValidas.length - 1;
            }
            
            imgElement.src = imagenesValidas[indiceActual].src;
            contador.textContent = (indiceActual + 1) + ' de ' + imagenesValidas.length;
        };
        
        // Botón anterior
        const btnAnterior = document.createElement('button');
        btnAnterior.textContent = '◀';
        btnAnterior.style.cssText = 'background: #0066cc; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-size: 1.2rem; transition: background 0.2s;';
        btnAnterior.onmouseover = () => btnAnterior.style.background = '#0052a3';
        btnAnterior.onmouseout = () => btnAnterior.style.background = '#0066cc';
        btnAnterior.onclick = () => {
            indiceActual = (indiceActual - 1 + imagenesValidas.length) % imagenesValidas.length;
            actualizarUI();
        };
        toolbar.appendChild(btnAnterior);
        
        toolbar.appendChild(contador);
        
        // Botón siguiente
        const btnSiguiente = document.createElement('button');
        btnSiguiente.textContent = '▶';
        btnSiguiente.style.cssText = 'background: #0066cc; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-size: 1.2rem; transition: background 0.2s;';
        btnSiguiente.onmouseover = () => btnSiguiente.style.background = '#0052a3';
        btnSiguiente.onmouseout = () => btnSiguiente.style.background = '#0066cc';
        btnSiguiente.onclick = () => {
            indiceActual = (indiceActual + 1) % imagenesValidas.length;
            actualizarUI();
        };
        toolbar.appendChild(btnSiguiente);
        
        // 🗑️ Botón eliminar
        const btnEliminar = document.createElement('button');
        btnEliminar.textContent = '🗑️ Eliminar';
        btnEliminar.style.cssText = 'background: #ef4444; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-size: 1rem; font-weight: 500; transition: background 0.2s;';
        btnEliminar.title = 'Eliminar esta imagen';
        btnEliminar.onmouseover = () => btnEliminar.style.background = '#dc2626';
        btnEliminar.onmouseout = () => btnEliminar.style.background = '#ef4444';
        btnEliminar.onclick = () => {
            // Crear modal personalizado para confirmación
            const confirmModalDiv = document.createElement('div');
            confirmModalDiv.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; z-index: 100002;';
            
            const confirmBox = document.createElement('div');
            confirmBox.style.cssText = 'background: white; border-radius: 12px; padding: 2rem; max-width: 400px; box-shadow: 0 20px 60px rgba(0,0,0,0.4);';
            
            const titulo = document.createElement('h3');
            titulo.textContent = '¿Eliminar imagen?';
            titulo.style.cssText = 'margin: 0 0 1rem 0; color: #1f2937; font-size: 1.25rem; font-weight: 600;';
            confirmBox.appendChild(titulo);
            
            const mensaje = document.createElement('p');
            mensaje.textContent = '¿Estás seguro de que deseas eliminar esta imagen? Esta acción no se puede deshacer.';
            mensaje.style.cssText = 'margin: 0 0 1.5rem 0; color: #6b7280; font-size: 0.95rem; line-height: 1.5;';
            confirmBox.appendChild(mensaje);
            
            const botonesDiv = document.createElement('div');
            botonesDiv.style.cssText = 'display: flex; gap: 1rem; justify-content: flex-end;';
            
            const btnCancelar = document.createElement('button');
            btnCancelar.textContent = 'Cancelar';
            btnCancelar.type = 'button';
            btnCancelar.style.cssText = 'background: #e5e7eb; color: #1f2937; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 500; transition: background 0.2s;';
            btnCancelar.onmouseover = () => btnCancelar.style.background = '#d1d5db';
            btnCancelar.onmouseout = () => btnCancelar.style.background = '#e5e7eb';
            btnCancelar.onclick = () => confirmModalDiv.remove();
            botonesDiv.appendChild(btnCancelar);
            
            const btnConfirmarEliminar = document.createElement('button');
            btnConfirmarEliminar.textContent = 'Eliminar';
            btnConfirmarEliminar.type = 'button';
            btnConfirmarEliminar.style.cssText = 'background: #ef4444; color: white; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 500; transition: background 0.2s;';
            btnConfirmarEliminar.onmouseover = () => btnConfirmarEliminar.style.background = '#dc2626';
            btnConfirmarEliminar.onmouseout = () => btnConfirmarEliminar.style.background = '#ef4444';
            btnConfirmarEliminar.onclick = () => {
                confirmModalDiv.remove();
                
                console.log('🗑️ [mostrarGaleriaImagenesPrenda] Eliminando imagen en índice', indiceActual);
                
                // Eliminar de imagenesValidas
                imagenesValidas.splice(indiceActual, 1);
                
                // Eliminar del array original (imagenes)
                const imagenAEliminar = imagenes[indiceActual];
                const indiceEnOriginal = imagenes.indexOf(imagenAEliminar);
                if (indiceEnOriginal !== -1) {
                    imagenes.splice(indiceEnOriginal, 1);
                    console.log(' Imagen eliminada del array original');
                }
                
                //  IMPORTANTE: Actualizar window.imagenesPrendaStorage con el nuevo array
                if (window.imagenesPrendaStorage && typeof window.imagenesPrendaStorage.establecerImagenes === 'function') {
                    window.imagenesPrendaStorage.establecerImagenes(imagenes);
                    console.log(' [SYNC] window.imagenesPrendaStorage actualizado con', imagenes.length, 'imágenes');
                }
                
                // Actualizar UI
                actualizarUI();
                
                // 🔴 FIX: También actualizar el contador del preview principal
                if (typeof window.actualizarPreviewPrenda === 'function') {
                    window.actualizarPreviewPrenda();
                    console.log(' [SYNC] Preview principal actualizado - contador debería cambiar a:', imagenes.length, 'fotos');
                }
            };
            botonesDiv.appendChild(btnConfirmarEliminar);
            
            confirmBox.appendChild(botonesDiv);
            confirmModalDiv.appendChild(confirmBox);
            
            // Cerrar si se hace click fuera del modal
            confirmModalDiv.onclick = (e) => {
                if (e.target === confirmModalDiv) {
                    confirmModalDiv.remove();
                }
            };
            
            document.body.appendChild(confirmModalDiv);
        };
        toolbar.appendChild(btnEliminar);
        
        // Botón cerrar
        const btnCerrar = document.createElement('button');
        btnCerrar.textContent = '✕';
        btnCerrar.style.cssText = 'background: #6c757d; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-size: 1.2rem; transition: background 0.2s;';
        btnCerrar.title = 'Cerrar galería';
        btnCerrar.onmouseover = () => btnCerrar.style.background = '#5a6268';
        btnCerrar.onmouseout = () => btnCerrar.style.background = '#6c757d';
        btnCerrar.onclick = () => modal.remove();
        toolbar.appendChild(btnCerrar);
        
        modal.appendChild(imgElement);
        modal.appendChild(toolbar);
        
        // Cerrar con ESC
        const cerrarConEsc = (e) => {
            if (e.key === 'Escape') {
                modal.remove();
                document.removeEventListener('keydown', cerrarConEsc);
            }
        };
        document.addEventListener('keydown', cerrarConEsc);
        
        // Cerrar con click en el fondo
        modal.onclick = (e) => {
            if (e.target === modal) {
                modal.remove();
                document.removeEventListener('keydown', cerrarConEsc);
            }
        };
        
        document.body.appendChild(modal);
        actualizarUI();
        
        console.log(' Galería abierta con', imagenesValidas.length, 'imágenes');
    };
}



/**
 * MODALES: Mostrar límite de imágenes
 */
window.mostrarModalLimiteImagenes = function() {
    const modal = document.createElement('div');
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 100000;';
    
    const box = document.createElement('div');
    box.style.cssText = 'background: white; border-radius: 12px; padding: 2rem; max-width: 400px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);';
    
    const titulo = document.createElement('h3');
    titulo.textContent = 'Límite de imágenes';
    titulo.style.cssText = 'margin: 0 0 0.75rem 0; color: #1f2937; font-size: 1.25rem;';
    box.appendChild(titulo);
    
    const mensaje = document.createElement('p');
    mensaje.textContent = 'Solo se permiten máximo 3 imágenes por tela.';
    mensaje.style.cssText = 'margin: 0 0 1.5rem 0; color: #6b7280; font-size: 0.95rem; line-height: 1.5;';
    box.appendChild(mensaje);
    
    const btnAceptar = document.createElement('button');
    btnAceptar.textContent = 'Aceptar';
    btnAceptar.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 500; transition: background 0.2s;';
    btnAceptar.onmouseover = () => btnAceptar.style.background = '#0052a3';
    btnAceptar.onmouseout = () => btnAceptar.style.background = '#0066cc';
    btnAceptar.onclick = () => modal.remove();
    box.appendChild(btnAceptar);
    
    modal.appendChild(box);
    document.body.appendChild(modal);
};

/**
 * MODALES: Mostrar error genérico
 */
window.mostrarModalError = function(mensaje) {
    const modal = document.createElement('div');
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 100000;';
    
    const box = document.createElement('div');
    box.style.cssText = 'background: white; border-radius: 12px; padding: 2rem; max-width: 400px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);';
    
    const titulo = document.createElement('h3');
    titulo.textContent = 'Error';
    titulo.style.cssText = 'margin: 0 0 0.75rem 0; color: #dc2626; font-size: 1.25rem;';
    box.appendChild(titulo);
    
    const msg = document.createElement('p');
    msg.textContent = mensaje;
    msg.style.cssText = 'margin: 0 0 1.5rem 0; color: #6b7280; font-size: 0.95rem; line-height: 1.5;';
    box.appendChild(msg);
    
    const btnAceptar = document.createElement('button');
    btnAceptar.textContent = 'Aceptar';
    btnAceptar.style.cssText = 'background: #dc2626; color: white; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 500; transition: background 0.2s;';
    btnAceptar.onmouseover = () => btnAceptar.style.background = '#b91c1c';
    btnAceptar.onmouseout = () => btnAceptar.style.background = '#dc2626';
    btnAceptar.onclick = () => modal.remove();
    box.appendChild(btnAceptar);
    
    modal.appendChild(box);
    document.body.appendChild(modal);
};
