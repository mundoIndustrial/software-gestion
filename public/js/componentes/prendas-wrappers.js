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
    console.log(' [WRAPPER] abrirModalPrendaNueva() llamado');
    
    // Intentar usar GestionItemsUI si existe
    if (window.gestionItemsUI && typeof window.gestionItemsUI.abrirModalAgregarPrendaNueva === 'function') {
        console.log(' [WRAPPER] Delegando a GestionItemsUI.abrirModalAgregarPrendaNueva()');
        return window.gestionItemsUI.abrirModalAgregarPrendaNueva();
    }
    
    // Fallback: abrir el modal directamente si existe
    const modal = document.getElementById('modal-agregar-prenda-nueva');
    if (modal) {
        console.log(' [WRAPPER] GestionItemsUI no disponible, abriendo modal directamente');
        modal.style.display = 'flex';
        // Limpiar formulario
        limpiarFormulario();
    } else {
        console.error(' [WRAPPER] Modal no encontrado y GestionItemsUI no disponible');
    }
};

/**
 * WRAPPER: Cierra el modal de prenda nueva
 * Delega a GestionItemsUI.cerrarModalAgregarPrendaNueva()
 */
window.cerrarModalPrendaNueva = function() {
    console.log(' [WRAPPER] cerrarModalPrendaNueva() llamado');
    
    // Cerrar el modal directamente
    const modal = document.getElementById('modal-agregar-prenda-nueva');
    if (modal) {
        modal.style.setProperty('display', 'none', 'important');
        modal.classList.remove('active');
        console.log(' [WRAPPER] Modal cerrado');
        
        // ✅ NUEVO: Resetear texto del botón a "Agregar Prenda"
        const btnGuardar = document.getElementById('btn-guardar-prenda');
        if (btnGuardar) {
            btnGuardar.innerHTML = '<span class="material-symbols-rounded">check</span>Agregar Prenda';
            console.log(' [WRAPPER] Texto del botón reseteado a "Agregar Prenda"');
        }
        
        // ⚠️ SEGURIDAD: Limpiar SOLO el formulario del modal de prenda (form-prenda-nueva)
        // NUNCA limpiar el formulario principal (formCrearPedidoEditable)
        const form = document.getElementById('form-prenda-nueva');
        if (form) {
            form.reset();
            console.log(' [WRAPPER] Formulario del modal de prenda reseteado');
        }
        
        // ✅ SEGURIDAD: SOLO limpiar campos ESPECÍFICOS del modal de prenda
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
                console.log(` [WRAPPER] Campo del modal limpiado: ${fieldId}`);
            }
        });
        
        // Limpiar telas agregadas
        if (window.telasAgregadas) {
            window.telasAgregadas = [];
            console.log(' [WRAPPER] Telas limpiadas');
        }
        
        // Limpiar imágenes de prenda
        if (window.imagenesPrendaStorage) {
            window.imagenesPrendaStorage.limpiar();
            console.log(' [WRAPPER] Imágenes de prenda limpiadas');
        }
        
        // Limpiar cantidades de tallas
        if (window.cantidadesTallas) {
            window.cantidadesTallas = {};
            console.log(' [WRAPPER] Cantidades de tallas limpiadas');
        }
        
        // Limpiar tallas seleccionadas
        if (window.tallasSeleccionadas) {
            window.tallasSeleccionadas = {
                dama: { tallas: [], tipo: null },
                caballero: { tallas: [], tipo: null }
            };
            console.log(' [WRAPPER] Tallas seleccionadas limpias');
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
        console.log(' [WRAPPER] Checkboxes de variaciones limpiados');
        
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
        console.log(' [WRAPPER] Campos de variaciones limpios');
        
        // Limpiar procesos seleccionados
        if (window.limpiarProcesosSeleccionados) {
            window.limpiarProcesosSeleccionados();
            console.log(' [WRAPPER] Procesos limpiados');
        }
    }
};

/**
 * WRAPPER: Agrega una prenda nueva al pedido
 * Delega a GestionItemsUI.agregarPrendaNueva()
 */
window.agregarPrendaNueva = function() {
    console.log(' [WRAPPER] agregarPrendaNueva() llamado');
    
    // Intentar usar GestionItemsUI si existe
    if (window.gestionItemsUI && typeof window.gestionItemsUI.agregarPrendaNueva === 'function') {
        console.log(' [WRAPPER] Delegando a GestionItemsUI.agregarPrendaNueva()');
        return window.gestionItemsUI.agregarPrendaNueva();
    }
    
    console.error(' [WRAPPER] GestionItemsUI no disponible, no se puede agregar prenda');
};

/**
 * WRAPPER: Carga un item en el modal para editar
 * Delega a GestionItemsUI.cargarItemEnModal()
 */
window.cargarItemEnModal = function(item, itemIndex) {
    console.log(' [WRAPPER] cargarItemEnModal() llamado para item:', itemIndex);
    
    // Intentar usar GestionItemsUI si existe
    if (window.gestionItemsUI && typeof window.gestionItemsUI.cargarItemEnModal === 'function') {
        console.log(' [WRAPPER] Delegando a GestionItemsUI.cargarItemEnModal()');
        return window.gestionItemsUI.cargarItemEnModal(item, itemIndex);
    }
    
    console.error(' [WRAPPER] GestionItemsUI no disponible, no se puede cargar item en modal');
};

/**
 * WRAPPER: Maneja la carga de imágenes para prendas
 * Delega a window.imagenesPrendaStorage (ImageStorageService)
 */
window.manejarImagenesPrenda = function(input) {
    console.log('📷 [WRAPPER] manejarImagenesPrenda() llamado');
    
    if (!input.files || input.files.length === 0) {
        console.warn(' [WRAPPER] No se seleccionó archivo');
        return;
    }
    
    try {
        // Verificar que el servicio existe
        if (!window.imagenesPrendaStorage) {
            console.error(' [WRAPPER] window.imagenesPrendaStorage no disponible');
            alert('Error: Servicio de almacenamiento de imágenes no inicializado');
            return;
        }
        
        // Agregar imagen al storage - AHORA RETORNA PROMISE
        window.imagenesPrendaStorage.agregarImagen(input.files[0])
            .then(() => {
                console.log(' [WRAPPER] Imagen de prenda agregada al storage');
                actualizarPreviewPrenda();
            })
            .catch(err => {
                if (err.message === 'MAX_LIMIT') {
                    console.warn(' [WRAPPER] Límite de imágenes alcanzado');
                    mostrarModalLimiteImagenes();
                } else if (err.message === 'INVALID_FILE') {
                    console.warn(' [WRAPPER] Archivo inválido');
                    mostrarModalError('El archivo debe ser una imagen válida');
                } else {
                    console.error(' [WRAPPER] Error:', err.message);
                    mostrarModalError('Error al procesar la imagen: ' + err.message);
                }
            });
    } catch (err) {
        console.error(' [WRAPPER] Error inesperado:', err);
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
    console.log(' [WRAPPER] actualizarPreviewPrenda() llamado');
    
    try {
        // Obtener elementos del DOM
        const preview = document.getElementById('nueva-prenda-foto-preview');
        const contador = document.getElementById('nueva-prenda-foto-contador');
        const btn = document.getElementById('nueva-prenda-foto-btn');
        
        if (!preview) {
            console.error(' [WRAPPER] Preview element no encontrado');
            return;
        }
        
        // Verificar que el servicio existe
        if (!window.imagenesPrendaStorage) {
            console.error(' [WRAPPER] window.imagenesPrendaStorage no disponible');
            return;
        }
        
        // Obtener imágenes
        const imagenes = window.imagenesPrendaStorage.obtenerImagenes();
        console.log(' [WRAPPER] Imágenes en storage:', imagenes.length);
        
        // Si no hay imágenes, mostrar placeholder
        if (imagenes.length === 0) {
            preview.innerHTML = '<div style="text-align: center;"><div class="material-symbols-rounded" style="font-size: 2rem; color: #9ca3af; margin-bottom: 0.25rem;">add_photo_alternate</div><div style="font-size: 0.7rem; color: #9ca3af;">Click para agregar</div></div>';
            preview.style.cursor = 'pointer';
            if (contador) contador.textContent = '';
            if (btn) btn.style.display = 'block';
            return;
        }
        
        // Mostrar primera imagen
        preview.innerHTML = '';
        preview.style.cursor = 'pointer';
        
        const img = document.createElement('img');
        img.src = imagenes[0].previewUrl;
        img.style.cssText = 'width: 100%; height: 100%; object-fit: cover; cursor: pointer;';
        
        //  Solo agregar click handler al preview (no duplicar en la img)
        preview.onclick = (e) => {
            e.stopPropagation();
            mostrarGaleriaImagenesPrenda(imagenes, 0);
        };
        
        preview.appendChild(img);
        
        // Actualizar contador
        if (contador) {
            contador.textContent = imagenes.length === 1 ? '1 foto' : imagenes.length + ' fotos';
        }
        
        // Mostrar/ocultar botón "Agregar más"
        if (btn) {
            btn.style.display = imagenes.length < 3 ? 'block' : 'none';
        }
        
        console.log(' [WRAPPER] Preview actualizado');
    } catch (e) {
        console.error(' [WRAPPER] Error al actualizar preview:', e);
    }
};

/**
 * WRAPPER: Abre el selector de archivos para agregar foto a prenda
 */
window.abrirSelectorPrendas = function() {
    console.log(' [WRAPPER] abrirSelectorPrendas() llamado');
    const inputFotos = document.getElementById('nueva-prenda-foto-input');
    if (inputFotos) {
        inputFotos.click();
    } else {
        console.error(' [WRAPPER] Input file no encontrado');
    }
};

/**
 * WRAPPER: Maneja la carga de imágenes para telas
 */
window.manejarImagenTela = function(input) {
    console.log(' [WRAPPER] manejarImagenTela() llamado');
    
    if (!input.files || input.files.length === 0) {
        console.warn(' [WRAPPER] No se seleccionó archivo');
        return;
    }
    
    try {
        // Verificar que el servicio existe
        if (!window.imagenesTelaStorage) {
            console.error(' [WRAPPER] window.imagenesTelaStorage no disponible');
            alert('Error: Servicio de almacenamiento de imágenes de tela no inicializado');
            return;
        }
        
        console.log(' [WRAPPER] Llamando a agregarImagen()...');
        // Agregar imagen al storage - RETORNA UNA PROMISE
        const promesa = window.imagenesTelaStorage.agregarImagen(input.files[0]);
        
        console.log(' [WRAPPER] agregarImagen retornó:', promesa);
        console.log(' [WRAPPER] Es Promise:', promesa instanceof Promise);
        
        // Manejar como Promise
        if (promesa instanceof Promise) {
            promesa
                .then((resultado) => {
                    console.log(' [WRAPPER] ✅ Promise resuelta - resultado:', resultado);
                    if (typeof actualizarPreviewTela === 'function') {
                        actualizarPreviewTela();
                    } else {
                        console.warn(' [WRAPPER] actualizarPreviewTela no es una función');
                    }
                })
                .catch((error) => {
                    console.error(' [WRAPPER] Promise rechazada - error:', error.message);
                    if (error.message === 'MAX_LIMIT') {
                        console.warn(' [WRAPPER] Límite de imágenes alcanzado');
                        if (typeof mostrarModalLimiteImagenes === 'function') {
                            mostrarModalLimiteImagenes();
                        }
                    } else if (error.message === 'INVALID_FILE') {
                        console.warn(' [WRAPPER] Archivo inválido');
                        mostrarModalError('El archivo debe ser una imagen válida');
                    } else {
                        console.error(' [WRAPPER] Error desconocido:', error.message);
                        mostrarModalError('Error al procesar la imagen: ' + error.message);
                    }
                });
        } else {
            // Fallback: si no es Promise, tratar como objeto sincrónico
            console.warn(' [WRAPPER] agregarImagen() no retornó Promise, tratando como sincrónico');
            if (promesa && promesa.success === true) {
                console.log(' [WRAPPER] ✅ Imagen agregada (sincrónico)');
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
                console.error(' [WRAPPER] Resultado inválido:', promesa);
                mostrarModalError('Error al procesar la imagen');
            }
        }
    } catch (err) {
        console.error(' [WRAPPER] Error inesperado:', err);
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
    console.log(' [WRAPPER] actualizarPreviewTela() llamado');
    
    try {
        const preview = document.getElementById('nueva-prenda-tela-preview');
        
        if (!preview) {
            console.error(' [WRAPPER] Preview de tela element no encontrado');
            return;
        }
        
        // Verificar que el servicio existe
        if (!window.imagenesTelaStorage) {
            console.error(' [WRAPPER] window.imagenesTelaStorage no disponible');
            return;
        }
        
        // Obtener imágenes del storage temporal
        const imagenes = window.imagenesTelaStorage.obtenerImagenes();
        console.log(' [WRAPPER] Imágenes de tela en storage:', imagenes.length);
        
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
                    console.log('[WRAPPER] Eliminando imagen de tela índice:', index);
                    window.imagenesTelaStorage.eliminarImagen(index);
                    actualizarPreviewTela(); // Actualizar el preview después de eliminar
                };
                
                container.appendChild(imgElement);
                container.appendChild(btnEliminar);
                preview.appendChild(container);
            });
            
            console.log(' [WRAPPER] Preview temporal de telas actualizado con', imagenes.length, 'imagen(es) DENTRO de la celda - VISIBLE');
        } else {
            // Ocultar preview si no hay imágenes
            preview.style.display = 'none';
        }
    } catch (e) {
        console.error(' [WRAPPER] Error al actualizar preview de tela:', e);
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
        
        console.log(' [WRAPPER] Formulario limpiado');
    } catch (e) {
        console.error(' [WRAPPER] Error al limpiar formulario:', e);
    }
}

/**
 * WRAPPER: Mostrar galería de imágenes de prenda (modal)
 * Nota: La función real se define en funciones-prenda-sin-cotizacion.js
 * Este es un placeholder que será sobrescrito cuando se cargue ese módulo
 */
window.mostrarGaleriaImagenesPrenda = function(imagenes, indiceInicial = 0) {
    console.log(' [WRAPPER] mostrarGaleriaImagenesPrenda() - llamando función de galería');
    // La función real será definida por funciones-prenda-sin-cotizacion.js
};

console.log(' [WRAPPERS] Módulo prendas-wrappers.js cargado');

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
