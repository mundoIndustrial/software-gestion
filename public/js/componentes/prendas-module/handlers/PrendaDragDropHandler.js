/**
 * ================================================
 * PRENDA DRAG & DROP HANDLER
 * ================================================
 * 
 * Manejo específico de drag & drop para imágenes de prendas
 * Hereda de BaseDragDropHandler y añade funcionalidad especializada
 * 
 * @class PrendaDragDropHandler
 * @extends BaseDragDropHandler
 */
class PrendaDragDropHandler extends BaseDragDropHandler {
    constructor() {
        super();
        this.maxImagenes = 3;
        this.imagenesActuales = [];
        this.tipo = 'prenda';
        this.opcionesMenu = {
            textoPegar: 'Pegar imagen de prenda',
            iconoPegar: 'content_paste'
        };
    }

    /**
     * Configurar drag & drop para prendas sin imágenes
     * @param {HTMLElement} previewElement - Elemento preview
     * @returns {PrendaDragDropHandler} Instancia para encadenamiento
     */
    configurarSinImagenes(previewElement) {
        if (!previewElement) {
            UIHelperService.log('PrendaDragDropHandler', 'Elemento preview no proporcionado', 'error');
            return this;
        }

        this.imagenesActuales = [];

        // Configurar usando el método base
        this._configurarHandlerBase(previewElement, {
            estilosDragOver: {
                background: '#eff6ff',
                border: '2px dashed #3b82f6',
                opacity: '0.8'
            },
            soloImagenes: true,
            maxArchivos: this.maxImagenes,
            callbacks: {
                onDragOver: (e) => this._onDragOver(e),
                onDragLeave: (e) => this._onDragLeave(e),
                onDrop: (files, e) => this._onDropSinImagenes(files, e),
                onClick: (e) => this._onClickSinImagenes(e),
                onPaste: (files, e) => this._onPasteSinImagenes(files, e),
                onError: (mensaje) => this._onError(mensaje)
            }
        });
        
        // Autoenfocar el preview para que pueda recibir paste
        setTimeout(() => {
            previewElement.focus();
        }, 100);
        
        UIHelperService.log('PrendaDragDropHandler', 'Configurado para prendas sin imágenes');
        return this;
    }

    /**
     * Configurar drag & drop para prendas con imágenes existentes
     * @param {HTMLElement} previewElement - Elemento preview
     * @param {Array} imagenesExistentes - Array de imágenes existentes
     * @returns {PrendaDragDropHandler} Instancia para encadenamiento
     */
    configurarConImagenes(previewElement, imagenesExistentes) {
        if (!previewElement) {
            UIHelperService.log('PrendaDragDropHandler', 'Elemento preview no proporcionado', 'error');
            return this;
        }

        this.imagenesActuales = imagenesExistentes || [];

        // Configurar usando el método base
        this._configurarHandlerBase(previewElement, {
            estilosDragOver: {
                background: 'rgba(59, 130, 246, 0.1)',
                border: '2px dashed #3b82f6',
                opacity: '0.8'
            },
            soloImagenes: true,
            maxArchivos: this.maxImagenes - this.imagenesActuales.length,
            callbacks: {
                onDragOver: (e) => this._onDragOverConImagenes(e),
                onDragLeave: (e) => this._onDragLeave(e),
                onDrop: (files, e) => this._onDropConImagenes(files, e),
                onClick: (e) => this._onClickConImagenes(e),
                onPaste: (files, e) => this._onPasteConImagenes(files, e),
                onError: (mensaje) => this._onError(mensaje)
            }
        });
        
        // Autoenfocar el preview para que pueda recibir paste
        setTimeout(() => {
            previewElement.focus();
        }, 100);
        
        UIHelperService.log('PrendaDragDropHandler', `Configurado para prendas con ${this.imagenesActuales.length} imágenes`);
        return this;
    }

    /**
     * Actualizar la lista de imágenes actuales
     * @param {Array} nuevasImagenes - Nueva lista de imágenes
     */
    actualizarImagenesActuales(nuevasImagenes) {
        this.imagenesActuales = nuevasImagenes || [];
        
        // Actualizar límite de archivos en el handler si existe
        if (this.handler) {
            const maxDisponible = this.maxImagenes - this.imagenesActuales.length;
            this.handler.actualizarOpciones({ maxArchivos: maxDisponible });
        }
        
        UIHelperService.log('PrendaDragDropHandler', `Imágenes actualizadas: ${this.imagenesActuales.length}`);
    }

    /**
     * Manejar evento drag over para prendas sin imágenes
     * @param {DragEvent} e - Evento drag over
     * @private
     */
    _onDragOver(e) {
        UIHelperService.log('PrendaDragDropHandler', 'Drag over en prenda sin imágenes');
    }

    /**
     * Manejar evento drag over para prendas con imágenes
     * @param {DragEvent} e - Evento drag over
     * @private
     */
    _onDragOverConImagenes(e) {
        UIHelperService.log('PrendaDragDropHandler', 'Drag over en prenda con imágenes');
    }

    /**
     * Manejar evento drag leave
     * @param {DragEvent} e - Evento drag leave
     * @private
     */
    _onDragLeave(e) {
        UIHelperService.log('PrendaDragDropHandler', 'Drag leave en prenda');
    }

    /**
     * Manejar evento drop para prendas sin imágenes
     * @param {FileList} files - Archivos arrastrados
     * @param {DragEvent} e - Evento drop
     * @private
     */
    _onDropSinImagenes(files, e) {
        UIHelperService.log('PrendaDragDropHandler', `Drop en prenda sin imágenes: ${files.length} archivos`);
        
        const tempInput = UIHelperService.crearInputTemporal(files);
        this._procesarImagen(tempInput);
    }

    /**
     * Manejar evento drop para prendas con imágenes
     * @param {FileList} files - Archivos arrastrados
     * @param {DragEvent} e - Evento drop
     * @private
     */
    _onDropConImagenes(files, e) {
        UIHelperService.log('PrendaDragDropHandler', `Drop en prenda con imágenes: ${files.length} archivos`);
        
        const tempInput = UIHelperService.crearInputTemporal(files);
        this._procesarImagen(tempInput);
    }

    /**
     * Manejar evento click para prendas sin imágenes
     * @param {MouseEvent} e - Evento click
     * @private
     */
    _onClickSinImagenes(e) {
        UIHelperService.log('PrendaDragDropHandler', 'Click en prenda sin imágenes');
        
        // Abrir el selector de archivos
        const inputFotos = document.getElementById('nueva-prenda-foto-input');
        if (inputFotos) {
            inputFotos.click();
        } else {
            UIHelperService.log('PrendaDragDropHandler', 'Input de fotos no encontrado', 'warn');
        }
    }

    /**
     * Manejar evento click para prendas con imágenes
     * @param {MouseEvent} e - Evento click
     * @private
     */
    _onClickConImagenes(e) {
        UIHelperService.log('PrendaDragDropHandler', 'Click en prenda con imágenes');
        
        // Primero intentar obtener imágenes del storage
        let imagenesParaGaleria = [];
        
        if (typeof window.imagenesPrendaStorage !== 'undefined' && window.imagenesPrendaStorage.obtenerImagenes) {
            try {
                const imagenesStorage = window.imagenesPrendaStorage.obtenerImagenes();
                imagenesParaGaleria = imagenesStorage
                    .map(img => img.previewUrl || img.src)
                    .filter(url => url && url.length > 0);
                
                UIHelperService.log('PrendaDragDropHandler', `Imágenes obtenidas del storage: ${imagenesParaGaleria.length}`);
            } catch (error) {
                UIHelperService.log('PrendaDragDropHandler', `Error al obtener imágenes del storage: ${error.message}`, 'warn');
            }
        }
        
        // Fallback: buscar imágenes en el DOM si no se encontraron en storage
        if (imagenesParaGaleria.length === 0) {
            const preview = document.getElementById('nueva-prenda-foto-preview');
            if (preview) {
                const imgs = preview.querySelectorAll('img');
                imagenesParaGaleria = Array.from(imgs)
                    .map(img => img.src)
                    .filter(src => src && src.length > 0);
                
                UIHelperService.log('PrendaDragDropHandler', `Imágenes obtenidas del DOM: ${imagenesParaGaleria.length}`);
            }
        }
        
        // Si hay imágenes, abrir galería modal
        if (imagenesParaGaleria.length > 0 && typeof Swal !== 'undefined') {
            UIHelperService.log('PrendaDragDropHandler', `Abriendo galería modal con ${imagenesParaGaleria.length} imágenes`);
            e.preventDefault();
            e.stopPropagation();
            this._abrirGaleriaDirecta(imagenesParaGaleria);
            return;
        }
        
        // Si no hay imágenes, abrir el selector de archivos
        UIHelperService.log('PrendaDragDropHandler', 'Abriendo selector de archivos');
        const inputFotos = document.getElementById('nueva-prenda-foto-input');
        if (inputFotos) {
            e.preventDefault();
            e.stopPropagation();
            inputFotos.click();
        } else {
            UIHelperService.log('PrendaDragDropHandler', 'Input de fotos no encontrado', 'warn');
        }
    }

    /**
     * Manejar evento paste para prendas sin imágenes
     * @param {FileList} files - Archivos del portapapeles
     * @param {ClipboardEvent} e - Evento paste
     * @private
     */
    _onPasteSinImagenes(files, e) {
        UIHelperService.log('PrendaDragDropHandler', `Paste en prenda sin imágenes: ${files.length} archivos`);
        
        const tempInput = UIHelperService.crearInputTemporal(files);
        this._procesarImagen(tempInput);
    }

    /**
     * Manejar evento paste para prendas con imágenes
     * @param {FileList} files - Archivos del portapapeles
     * @param {ClipboardEvent} e - Evento paste
     * @private
     */
    _onPasteConImagenes(files, e) {
        UIHelperService.log('PrendaDragDropHandler', `Paste en prenda con imágenes: ${files.length} archivos`);
        
        const tempInput = UIHelperService.crearInputTemporal(files);
        this._procesarImagen(tempInput);
    }

    /**
     * Manejar errores
     * @param {string} mensaje - Mensaje de error
     * @private
     */
    _onError(mensaje) {
        UIHelperService.log('PrendaDragDropHandler', `Error: ${mensaje}`, 'error');
        UIHelperService.mostrarModalError(mensaje);
    }

    /**
     * Procesar imagen específica para prendas
     * @param {HTMLInputElement} input - Input con archivos
     * @private
     */
    _procesarImagen(input) {
        // Usar la función global existente si está disponible
        if (typeof window.manejarImagenesPrenda === 'function') {
            window.manejarImagenesPrenda(input);
            UIHelperService.log('PrendaDragDropHandler', 'Imágenes procesadas con función global');
        } else {
            UIHelperService.log('PrendaDragDropHandler', 'Función manejarImagenesPrenda no disponible', 'error');
            UIHelperService.mostrarModalError('No se pudo procesar las imágenes. Función de manejo no disponible.');
        }
    }

    /**
     * Abrir galería modal directamente con array de URLs
     * @param {Array<string>} imagenes - Array de URLs de imágenes
     * @private
     */
    _abrirGaleriaDirecta(imagenes) {
        if (!imagenes || imagenes.length === 0 || typeof Swal === 'undefined') return;

        // Inyectar/actualizar CSS para z-index encima del modal de prenda (z-index: 1050000)
        let galeriaStyle = document.getElementById('swal-galeria-zindex-style');
        if (!galeriaStyle) {
            galeriaStyle = document.createElement('style');
            galeriaStyle.id = 'swal-galeria-zindex-style';
            document.head.appendChild(galeriaStyle);
        }
        galeriaStyle.textContent = `
            .swal-galeria-container {
                z-index: 2000000 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                height: 100% !important;
            }
            .swal-galeria-container .swal2-popup {
                margin: auto !important;
            }
        `;

        let idx = 0;

        const keyHandler = (e) => {
            if (!window.__galeriaPrendaActiva) return;
            if (e.key === 'ArrowLeft') { e.preventDefault(); document.getElementById('gal-prenda-prev')?.click(); }
            else if (e.key === 'ArrowRight') { e.preventDefault(); document.getElementById('gal-prenda-next')?.click(); }
        };

        const eliminarImagenActual = () => {
            // 🔴 CORRECCIÓN: Detectar correctamente si estamos en modo edición o creación
            const modal = document.getElementById('modal-agregar-prenda-nueva');
            const modalVisible = modal && modal.style.display !== 'none';
            
            // Detectar si es modo edición (hay datos de edición cargados)
            const esModoEdicion = window.modoEdicion === true || 
                                 (window.pedidoEditarId && window.pedidoEditarId > 0) ||
                                 (window.pedidoEditarData && window.pedidoEditarData.pedido);
            
            console.log('[PrendaDragDropHandler] 🗑️ Modo detectado:', {
                modalVisible,
                esModoEdicion,
                pedidoEditarId: window.pedidoEditarId,
                modoEdicion: window.modoEdicion
            });
            
            if (modalVisible && esModoEdicion) {
                // Modo edición: marcar para eliminación diferida
                console.log('[PrendaDragDropHandler] 🗑️ Modo edición detectado, marcando imagen para eliminación diferida');
                
                // Inicializar array si no existe
                if (!window.imagenesAEliminar) {
                    window.imagenesAEliminar = [];
                }
                
                // Obtener imágenes del storage para encontrar el ID
                if (window.imagenesPrendaStorage && window.imagenesPrendaStorage.obtenerImagenes) {
                    const imgs = window.imagenesPrendaStorage.obtenerImagenes();
                    const imagenAEliminar = imgs[idx];
                    
                    // Agregar ID al array si tiene ID y no está ya marcada
                    if (imagenAEliminar && imagenAEliminar.id && !window.imagenesAEliminar.includes(imagenAEliminar.id)) {
                        window.imagenesAEliminar.push(imagenAEliminar.id);
                        console.log('[PrendaDragDropHandler] ✅ Imagen marcada para eliminación diferida:', {
                            id: imagenAEliminar.id,
                            totalMarcadas: window.imagenesAEliminar.length
                        });
                        
                        // Ocultar visualmente la imagen en la galería
                        imagenes.splice(idx, 1);
                        if (imagenes.length > 0) {
                            idx = Math.min(idx, imagenes.length - 1);
                            renderModal();
                        } else {
                            Swal.close();
                        }
                        
                        // Actualizar preview DOM para mostrar imagen como eliminada
                        if (typeof window.actualizarPreviewPrenda === 'function') {
                            window.actualizarPreviewPrenda();
                        }
                        
                        return;
                    } else if (imagenAEliminar && !imagenAEliminar.id) {
                        // Imagen nueva sin ID: eliminar inmediatamente del storage
                        console.log('[PrendaDragDropHandler] 🗑️ Imagen nueva sin ID, eliminando inmediatamente');
                        
                        // Eliminar del storage
                        if (window.imagenesPrendaStorage && window.imagenesPrendaStorage.eliminarImagen) {
                            window.imagenesPrendaStorage.eliminarImagen(idx);
                        }
                        
                        // Ocultar visualmente la imagen en la galería
                        imagenes.splice(idx, 1);
                        if (imagenes.length > 0) {
                            idx = Math.min(idx, imagenes.length - 1);
                            renderModal();
                        } else {
                            Swal.close();
                        }
                        
                        // Actualizar preview DOM
                        if (typeof window.actualizarPreviewPrenda === 'function') {
                            window.actualizarPreviewPrenda();
                        }
                        
                        return;
                    }
                }
                
                // Si no se puede marcar para eliminación, mostrar mensaje
                Swal.fire({
                    title: 'No se puede eliminar',
                    text: 'Esta imagen no se puede marcar para eliminación',
                    icon: 'warning',
                    customClass: { container: 'swal-galeria-container' }
                });
                return;
            }
            
            // Modo creación: eliminación inmediata (comportamiento original)
            Swal.fire({
                title: '¿Eliminar imagen?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                customClass: { container: 'swal-galeria-container' }
            }).then((result) => {
                if (!result.isConfirmed) { renderModal(); return; }
                
                // Eliminar del storage
                if (window.imagenesPrendaStorage && window.imagenesPrendaStorage.obtenerImagenes) {
                    const imgs = window.imagenesPrendaStorage.obtenerImagenes();
                    imgs.splice(idx, 1);
                    window.imagenesPrendaStorage.establecerImagenes(imgs);
                }
                
                // Eliminar de la lista local
                imagenes.splice(idx, 1);
                
                // Actualizar preview DOM
                if (typeof window.actualizarPreviewPrenda === 'function') {
                    window.actualizarPreviewPrenda();
                } else if (typeof PrendaEditorImagenes !== 'undefined' && typeof PrendaEditorImagenes.actualizarPreviewDespuesDeAgregar === 'function') {
                    // 🔴 ELIMINADO: _actualizarPreviewDOM() causaba apilamiento
                    // Usar actualizarPreviewDespuesDeAgregar() en su lugar
                    PrendaEditorImagenes.actualizarPreviewDespuesDeAgregar();
                }
                
                if (imagenes.length > 0) {
                    idx = Math.min(idx, imagenes.length - 1);
                    renderModal();
                } else {
                    const preview = document.getElementById('nueva-prenda-foto-preview');
                    if (preview) {
                        preview.innerHTML = '<div class="foto-preview-content"><div class="material-symbols-rounded">add_photo_alternate</div><div class="foto-preview-text">Click para seleccionar o<br>Ctrl+V para pegar imagen</div></div>';
                    }
                    Swal.close();
                }
            });
        };

        const renderModal = () => {
            if (imagenes.length === 0) { Swal.close(); return; }
            const url = imagenes[idx];
            Swal.fire({
                html: `
                    <div style="display:flex; flex-direction:column; align-items:center; gap:1rem;">
                        <div style="position:relative; width:100%; max-width:620px;">
                            <img src="${url}" alt="Foto prenda" style="width:100%; border-radius:8px; border:1px solid #e5e7eb; object-fit:contain; max-height:65vh;">
                            ${imagenes.length > 1 ? `
                                <button id="gal-prenda-prev" style="position:absolute; top:50%; left:-16px; transform:translateY(-50%); background:#111827cc; color:white; border:none; border-radius:50%; width:38px; height:38px; cursor:pointer; font-size:1.1rem; display:flex; align-items:center; justify-content:center;">‹</button>
                                <button id="gal-prenda-next" style="position:absolute; top:50%; right:-16px; transform:translateY(-50%); background:#111827cc; color:white; border:none; border-radius:50%; width:38px; height:38px; cursor:pointer; font-size:1.1rem; display:flex; align-items:center; justify-content:center;">›</button>
                            ` : ''}
                        </div>
                        <div style="display:flex; align-items:center; gap:1rem;">
                            <span style="font-size:0.9rem; color:#4b5563;">${idx + 1} / ${imagenes.length}</span>
                            <button id="gal-prenda-delete" style="background:#dc2626; color:white; border:none; border-radius:6px; padding:0.4rem 1rem; cursor:pointer; font-size:0.85rem; display:flex; align-items:center; gap:0.3rem;">
                                <span class="material-symbols-rounded" style="font-size:1rem;">delete</span> Eliminar
                            </button>
                        </div>
                    </div>
                `,
                showConfirmButton: false,
                showCloseButton: true,
                width: '75%',
                customClass: { container: 'swal-galeria-container' },
                didOpen: () => {
                    window.__galeriaPrendaActiva = true;
                    const prev = document.getElementById('gal-prenda-prev');
                    const next = document.getElementById('gal-prenda-next');
                    const del = document.getElementById('gal-prenda-delete');
                    if (prev) prev.onclick = () => { idx = (idx - 1 + imagenes.length) % imagenes.length; renderModal(); };
                    if (next) next.onclick = () => { idx = (idx + 1) % imagenes.length; renderModal(); };
                    if (del) del.onclick = () => eliminarImagenActual();
                    window.addEventListener('keydown', keyHandler);
                },
                willClose: () => {
                    window.__galeriaPrendaActiva = false;
                    window.removeEventListener('keydown', keyHandler);
                }
            });
        };

        renderModal();
    }

    /**
     * Configurar menú contextual para prendas
     * @param {MouseEvent} e - Evento que activa el menú
     * Pegar imagen desde menú contextual
     * @param {boolean} conImagenes - Si hay imágenes existentes
     * @private
     */
    async _pegarDesdeMenuContextual(conImagenes) {
        UIHelperService.log('PrendaDragDropHandler', '🖱️ Iniciando pegado desde menú contextual...');
        
        try {
            // Verificar límite de imágenes
            if (conImagenes && this.imagenesActuales.length >= this.maxImagenes) {
                UIHelperService.mostrarModalError(`Solo se permiten máximo ${this.maxImagenes} imágenes por prenda`);
                return;
            }

            // Verificar si ClipboardService está disponible
            if (!window.ClipboardService) {
                UIHelperService.log('PrendaDragDropHandler', ' ClipboardService no disponible', 'error');
                throw new Error('ClipboardService no disponible');
            }

            UIHelperService.log('PrendaDragDropHandler', ' ClipboardService disponible, intentando leer...');

            // Leer imágenes del portapapeles
            const archivos = await ClipboardService.leerImagenes({
                maxArchivos: this.maxImagenes - this.imagenesActuales.length
            });

            UIHelperService.log('PrendaDragDropHandler', `📁 Archivos obtenidos: ${archivos.length}`);

            if (archivos.length > 0) {
                const tempInput = UIHelperService.crearInputTemporal(archivos);
                this._procesarImagen(tempInput);
                UIHelperService.log('PrendaDragDropHandler', ` Imagen pegada desde menú: ${archivos.length} archivos`);
            } else {
                UIHelperService.log('PrendaDragDropHandler', ' No se encontraron imágenes en el portapapeles', 'warn');
            }

        } catch (error) {
            UIHelperService.log('PrendaDragDropHandler', ` Error principal pegando desde menú: ${error.message}`, 'error');
            
            // Fallback mejorado: usar el portapapeles del navegador directamente
            try {
                UIHelperService.log('PrendaDragDropHandler', '🔄 Intentando fallback con navigator.clipboard...');
                
                if (navigator.clipboard && navigator.clipboard.read) {
                    const items = await navigator.clipboard.read();
                    UIHelperService.log('PrendaDragDropHandler', ` Items encontrados en fallback: ${items.length}`);
                    
                    const archivos = [];
                    
                    for (const item of items) {
                        UIHelperService.log('PrendaDragDropHandler', `🔍 Tipos en item: ${item.types.join(', ')}`);
                        
                        for (const type of item.types) {
                            if (type.startsWith('image/')) {
                                UIHelperService.log('PrendaDragDropHandler', `🖼️ Procesando tipo de imagen: ${type}`);
                                
                                const blob = await item.getType(type);
                                UIHelperService.log('PrendaDragDropHandler', `📦 Blob obtenido: ${blob.size} bytes`);
                                
                                const file = new File([blob], `imagen-${Date.now()}.${type.split('/')[1]}`, {
                                    type: type
                                });
                                archivos.push(file);
                                
                                // Limitar al máximo necesario
                                if (archivos.length >= this.maxImagenes - this.imagenesActuales.length) {
                                    break;
                                }
                            }
                        }
                        if (archivos.length >= this.maxImagenes - this.imagenesActuales.length) {
                            break;
                        }
                    }
                    
                    if (archivos.length > 0) {
                        const tempInput = UIHelperService.crearInputTemporal(archivos);
                        this._procesarImagen(tempInput);
                        UIHelperService.log('PrendaDragDropHandler', ` Imagen pegada con fallback: ${archivos.length} archivos`);
                    } else {
                        UIHelperService.mostrarModalError('No se encontraron imágenes en el portapapeles. Por favor copia una imagen primero.');
                    }
                } else {
                    UIHelperService.log('PrendaDragDropHandler', ' navigator.clipboard.read no disponible', 'error');
                    // Fallback final: solicitar al usuario que use Ctrl+V
                    UIHelperService.mostrarModalError('Usa Ctrl+V para pegar la imagen directamente en el área de fotos.');
                }
            } catch (fallbackError) {
                UIHelperService.log('PrendaDragDropHandler', ` Error en fallback: ${fallbackError.message}`, 'error');
                UIHelperService.mostrarModalError('No se pudo acceder al portapapeles. Por favor usa Ctrl+V para pegar la imagen.');
            }
        }
    }

    /**
     * Obtener estado actual del handler
     * @returns {Object} Estado actual
     */
    getEstado() {
        return {
            configurado: !!this.handler,
            imagenesActuales: this.imagenesActuales.length,
            maxImagenes: this.maxImagenes,
            handlerEstado: this.handler ? this.handler.getEstado() : null
        };
    }

    /**
     * Desactivar el handler
     */
    desactivar() {
        if (this.handler) {
            this.handler.desactivar();
        }
        UIHelperService.log('PrendaDragDropHandler', 'Handler desactivado');
    }
}

// Exportar para uso en módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PrendaDragDropHandler;
}

// Asignar al window para uso global
window.PrendaDragDropHandler = PrendaDragDropHandler;
