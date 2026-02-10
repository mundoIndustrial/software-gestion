/**
 * ================================================
 * DRAG & DROP FUNCTIONALITY
 * ================================================
 * 
 * Funciones para manejar drag & drop de imágenes
 * Soporta tanto imágenes de prendas como de telas
 * 
 * @module DragDropHandlers
 */

/**
 * Mostrar modal de error
 * @param {string} mensaje - Mensaje a mostrar
 */
function mostrarModalError(mensaje) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: mensaje,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Cerrar'
        });
    } else {
        // Fallback a alert si Swal no está disponible
        alert('❌ Error: ' + mensaje);
    }
}

/**
 * Obtener o crear contenedor para overlays sin restricciones de overflow
 * Esto previene que elementos fixed sean clipeados por overflow: hidden en padre
 */
function obtenerContenedorOverlay() {
    let container = document.getElementById('drag-drop-overlay-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'drag-drop-overlay-container';
        container.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 999999999;
            pointer-events: none;
            overflow: visible;
        `;
        document.body.appendChild(container);
        console.log('[drag-drop-handlers] ✅ Contenedor overlay creado');
    }
    return container;
}

/**
 * Configura los event listeners para drag & drop en el preview de imágenes
 */
window.setupDragAndDrop = function(previewElement) {
    // Limpiar event listeners anteriores clonando el elemento
    const newPreview = previewElement.cloneNode(true);
    previewElement.parentNode.replaceChild(newPreview, previewElement);
    
    // Prevenir comportamiento por defecto para todos los eventos
    const preventDefaults = (e) => {
        e.preventDefault();
        e.stopPropagation();
    };
    
    // Eventos de drag
    newPreview.addEventListener('dragover', preventDefaults);
    newPreview.addEventListener('dragenter', preventDefaults);
    newPreview.addEventListener('dragleave', preventDefaults);
    
    // Evento dragover con feedback visual
    newPreview.addEventListener('dragover', (e) => {
        e.preventDefault();
        newPreview.style.background = '#eff6ff';
        newPreview.style.border = '2px dashed #3b82f6';
        newPreview.style.opacity = '0.8';
    });
    
    // Evento dragleave para restaurar estilos
    newPreview.addEventListener('dragleave', (e) => {
        e.preventDefault();
        newPreview.style.background = '';
        newPreview.style.border = '';
        newPreview.style.opacity = '1';
    });
    
    // Evento drop - manejar archivos arrastrados
    newPreview.addEventListener('drop', (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        // Restaurar estilos
        newPreview.style.background = '';
        newPreview.style.border = '';
        newPreview.style.opacity = '1';
        
        // Verificar si hay archivos
        const files = e.dataTransfer.files;
        if (files.length === 0) {
            return;
        }
        
        // Procesar el primer archivo (solo imágenes)
        const file = files[0];
        
        // Verificar que sea una imagen
        if (!file.type.startsWith('image/')) {
            mostrarModalError('Por favor arrastra solo archivos de imagen');
            return;
        }
        
        // Crear un input file temporal para usar la función existente
        const tempInput = document.createElement('input');
        tempInput.type = 'file';
        tempInput.files = files;
        
        // Usar la función existente para manejar la imagen
        if (typeof window.manejarImagenesPrenda === 'function') {
            window.manejarImagenesPrenda(tempInput);
        } else {
            console.error('[setupDragAndDrop] ❌ La función manejarImagenesPrenda no está disponible');
        }
    });
    
    // Evento click como alternativa
    newPreview.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        // Enfocar el elemento para poder recibir eventos paste
        newPreview.focus();
        
        // Abrir el selector de archivos
        const inputFotos = document.getElementById('nueva-prenda-foto-input');
        if (inputFotos) {
            inputFotos.click();
        } else {
            // Input de fotos no encontrado, no hacer nada
        }
    });
    
    // Evento focus para mostrar indicador visual
    newPreview.addEventListener('focus', (e) => {
        newPreview.style.boxShadow = '0 0 0 3px rgba(59, 130, 246, 0.3)';
        newPreview.style.border = '2px solid #3b82f6';
    });
    
    // Evento blur para quitar indicador visual
    newPreview.addEventListener('blur', (e) => {
        newPreview.style.boxShadow = '';
        newPreview.style.border = '';
    });
    
    // Prevenir menú contextual del navegador
    newPreview.addEventListener('contextmenu', (e) => {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        return false;
    }, true);
    
    newPreview.addEventListener('mouseup', (e) => {
        if (e.button === 2) {
            e.preventDefault();
            e.stopPropagation();
        }
    });
    
    // Evento para mostrar menú personalizado
    // NOTA: Usamos mousedown + botón derecho como alternativa ya que contextmenu está bloqueado
    newPreview.addEventListener('mousedown', (e) => {
        console.log(`[setupDragAndDrop] 🖱️ Mousedown detectado, botón: ${e.button}`);
        
        // Botón derecho = 2
        if (e.button === 2) {
            console.log(`[setupDragAndDrop] 🎉 ¡Botón derecho detectado!`);
            e.preventDefault();
            e.stopPropagation();
            
            // Enfocar el elemento
            newPreview.focus();
            
            console.log(`[setupDragAndDrop] 🎯 Elemento enfocado, creando menú...`);
            
            // Calcular posición para evitar que se corte por los bordes
            const menuWidth = 180;
            const menuHeight = 50; // Altura aproximada del menú
            const padding = 10;
            
            let left = e.clientX;
            let top = e.clientY;
            
            // Ajustar posición horizontal si se sale por la derecha
            if (left + menuWidth > window.innerWidth - padding) {
                left = window.innerWidth - menuWidth - padding;
            }
            
            // Ajustar posición vertical si se sale por abajo
            if (top + menuHeight > window.innerHeight - padding) {
                top = window.innerHeight - menuHeight - padding;
            }
            
            // Asegurar que no sea negativo
            left = Math.max(padding, left);
            top = Math.max(padding, top);
            
            // Crear menú contextual
            const menu = document.createElement('div');
            menu.style.cssText = `
                position: fixed;
                left: ${left}px;
                top: ${top}px;
                background: white;
                border: 1px solid #d1d5db;
                border-radius: 6px;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
                z-index: 999999;
                padding: 4px 0;
                min-width: 180px;
                font-size: 14px;
                backdrop-filter: blur(10px);
                background: rgba(255, 255, 255, 0.95);
            `;
            
            // Opción de pegar
            const pasteOption = document.createElement('div');
            pasteOption.style.cssText = `
                padding: 8px 16px;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 8px;
                color: #374151;
                transition: background-color 0.2s;
            `;
            pasteOption.innerHTML = `
                <span class="material-symbols-rounded" style="font-size: 18px;">content_paste</span>
                Pegar imagen de prenda
            `;
            
            // Hover effect
            pasteOption.addEventListener('mouseenter', () => {
                pasteOption.style.backgroundColor = '#f3f4f6';
            });
            pasteOption.addEventListener('mouseleave', () => {
                pasteOption.style.backgroundColor = '';
            });
            
            // Click para pegar
            pasteOption.addEventListener('click', () => {
                console.log('[setupDragAndDrop] 📋 Iniciando pegado desde menú contextual...');
                
                // Cerrar menú inmediatamente para evitar múltiples clics
                if (menu && document.body.contains(menu)) {
                    document.body.removeChild(menu);
                }
                
                // Intentar obtener imagen del portapapeles
                navigator.clipboard.read().then(items => {
                    console.log('[setupDragAndDrop] 📋 Items en portapapeles:', items.length);
                    
                    for (let item of items) {
                        console.log('[setupDragAndDrop] 📋 Tipos disponibles:', item.types);
                        
                        // Verificar si hay algún tipo de imagen
                        const imageTypes = item.types.filter(type => 
                            type.includes('image/png') || 
                            type.includes('image/jpeg') || 
                            type.includes('image/gif') || 
                            type.includes('image/webp') ||
                            type.includes('image/bmp')
                        );
                        
                        console.log('[setupDragAndDrop] 📋 Tipos de imagen encontrados:', imageTypes);
                        
                        if (imageTypes.length > 0) {
                            console.log('[setupDragAndDrop] 📋 Procesando tipo:', imageTypes[0]);
                            
                            item.getType(imageTypes[0]).then(blob => {
                                console.log('[setupDragAndDrop] 📋 Blob obtenido:', blob.type, blob.size);
                                
                                const file = new File([blob], 'pasted-image.png', { type: blob.type });
                                console.log('[setupDragAndDrop] 📋 File creado:', file.name, file.type, file.size);
                                
                                // Crear input temporal
                                const tempInput = document.createElement('input');
                                tempInput.type = 'file';
                                const dataTransfer = new DataTransfer();
                                dataTransfer.items.add(file);
                                tempInput.files = dataTransfer.files;
                                
                                console.log('[setupDragAndDrop] 📋 Input temporal creado, archivos:', tempInput.files.length);
                                
                                // Usar función existente
                                if (typeof window.manejarImagenesPrenda === 'function') {
                                    console.log('[setupDragAndDrop] 📋 Llamando a manejarImagenesPrenda...');
                                    window.manejarImagenesPrenda(tempInput);
                                } else {
                                    console.error('[setupDragAndDrop] ❌ La función manejarImagenesPrenda no está disponible');
                                }
                            }).catch(err => {
                                console.error('[setupDragAndDrop] ❌ Error al obtener blob:', err);
                                mostrarModalError('No se pudo procesar la imagen del portapapeles');
                            });
                            break;
                        }
                    }
                    
                    // Si no se encontraron imágenes
                    if (items.length > 0 && !items.some(item => 
                        item.types.some(type => type.includes('image/'))
                    )) {
                        console.warn('[setupDragAndDrop] ⚠️ No hay imágenes en el portapapeles');
                        mostrarModalError('El portapapeles no contiene imágenes. Por favor copia una imagen primero.');
                    }
                    
                }).catch(err => {
                    console.warn('[setupDragAndDrop] 📋 No se pudo acceder al portapapeles:', err);
                    
                    // Fallback: intentar con el evento paste tradicional
                    console.log('[setupDragAndDrop] 📋 Intentando fallback con evento paste...');
                    const pasteEvent = new ClipboardEvent('paste', {
                        clipboardData: new DataTransfer()
                    });
                    newPreview.dispatchEvent(pasteEvent);
                });
            });
            
            menu.appendChild(pasteOption);
            
            // Agregar al DOM
            document.body.appendChild(menu);
            
            // Cerrar menú al hacer clic fuera
            const closeMenu = (e) => {
                if (menu && document.body.contains(menu)) {
                    document.body.removeChild(menu);
                    document.removeEventListener('click', closeMenu);
                }
            };
            
            // Cerrar menú al presionar Escape
            const closeMenuEscape = (e) => {
                if (e.key === 'Escape') {
                    if (menu && document.body.contains(menu)) {
                        document.body.removeChild(menu);
                    }
                    document.removeEventListener('keydown', closeMenuEscape);
                }
            };
            
            setTimeout(() => {
                document.addEventListener('click', closeMenu);
                document.addEventListener('keydown', closeMenuEscape);
            }, 100);
        }
    });
    
    // Evento paste para permitir pegar imágenes desde el portapapeles
    newPreview.addEventListener('paste', (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('[setupDragAndDrop] 📋 Evento paste detectado');
        console.log('[setupDragAndDrop] 📋 ClipboardData items:', e.clipboardData?.items?.length || 0);
        
        // Obtener items del portapapeles
        const items = e.clipboardData.items;
        if (items.length === 0) {
            console.warn('[setupDragAndDrop] 📋 No hay items en el portapapeles');
            return;
        }
        
        console.log('[setupDragAndDrop] 📋 Items disponibles:');
        for (let i = 0; i < items.length; i++) {
            console.log(`[setupDragAndDrop] 📋 Item ${i}:`, items[i].type, items[i].kind);
        }
        
        // Buscar imágenes en el portapapeles
        for (let i = 0; i < items.length; i++) {
            const item = items[i];
            
            // Verificar si es una imagen
            if (item.type.startsWith('image/')) {
                console.log('[setupDragAndDrop] 📋 Imagen encontrada:', item.type);
                
                // Obtener el archivo
                const file = item.getAsFile();
                if (file) {
                    console.log('[setupDragAndDrop] 📋 Archivo obtenido:', file.name, file.type, file.size);
                    
                    // Crear un input file temporal para usar la función existente
                    const tempInput = document.createElement('input');
                    tempInput.type = 'file';
                    tempInput.files = new DataTransfer().files;
                    
                    // Agregar el archivo al DataTransfer
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    tempInput.files = dataTransfer.files;
                    
                    console.log('[setupDragAndDrop] 📋 Input temporal preparado');
                    
                    // Usar la función existente para manejar la imagen
                    if (typeof window.manejarImagenesPrenda === 'function') {
                        console.log('[setupDragAndDrop] 📋 Llamando a manejarImagenesPrenda...');
                        window.manejarImagenesPrenda(tempInput);
                    } else {
                        console.error('[setupDragAndDrop] ❌ La función manejarImagenesPrenda no está disponible');
                    }
                    
                    // Salir después de procesar la primera imagen
                    break;
                } else {
                    console.warn('[setupDragAndDrop] ⚠️ No se pudo obtener el archivo del item');
                }
            }
        }
        
        // Si no se encontraron imágenes
        const hasImages = Array.from(items).some(item => item.type.startsWith('image/'));
        if (!hasImages) {
            console.warn('[setupDragAndDrop] ⚠️ No se encontraron imágenes en el portapapeles');
            mostrarModalError('El portapapeles no contiene imágenes válidas. Por favor copia una imagen primero.');
        }
    });
    
    // Hacer que el elemento sea focusable para recibir eventos paste
    newPreview.setAttribute('tabindex', '0');
    newPreview.style.outline = 'none';
};

/**
 * Configura los event listeners para drag & drop cuando ya hay imágenes
 * Permite reemplazar o agregar más imágenes
 */
window.setupDragAndDropConImagen = function(previewElement, imagenesActuales) {
    // Limpiar event listeners anteriores clonando el elemento
    const newPreview = previewElement.cloneNode(true);
    previewElement.parentNode.replaceChild(newPreview, previewElement);
    
    // Prevenir comportamiento por defecto para todos los eventos
    const preventDefaults = (e) => {
        e.preventDefault();
        e.stopPropagation();
    };
    
    // Eventos de drag
    newPreview.addEventListener('dragover', preventDefaults);
    newPreview.addEventListener('dragenter', preventDefaults);
    newPreview.addEventListener('dragleave', preventDefaults);
    
    // Evento dragover con feedback visual
    newPreview.addEventListener('dragover', (e) => {
        e.preventDefault();
        newPreview.style.background = 'rgba(59, 130, 246, 0.1)';
        newPreview.style.border = '2px dashed #3b82f6';
        newPreview.style.opacity = '0.8';
    });
    
    // Evento dragleave para restaurar estilos
    newPreview.addEventListener('dragleave', (e) => {
        e.preventDefault();
        newPreview.style.background = '';
        newPreview.style.border = '';
        newPreview.style.opacity = '1';
    });
    
    // Evento drop - manejar archivos arrastrados
    newPreview.addEventListener('drop', (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        // Restaurar estilos
        newPreview.style.background = '';
        newPreview.style.border = '';
        newPreview.style.opacity = '1';
        
        console.log('[setupDragAndDropConImagen] 📦 Archivos arrastrados:', e.dataTransfer.files.length);
        console.log('[setupDragAndDropConImagen] 📸 Imágenes actuales:', imagenesActuales.length);
        
        // Verificar si hay archivos
        const files = e.dataTransfer.files;
        if (files.length === 0) {
            console.log('[setupDragAndDropConImagen] 📭 No se arrastraron archivos');
            return;
        }
        
        // Verificar límite de imágenes
        if (imagenesActuales.length >= 3) {
            console.warn('[setupDragAndDropConImagen] ⚠️ Límite de imágenes alcanzado');
            mostrarModalError('Solo se permiten máximo 3 imágenes por prenda');
            return;
        }
        
        // Procesar el primer archivo (solo imágenes)
        const file = files[0];
        console.log('[setupDragAndDropConImagen] 📄 Procesando archivo:', file.name, file.type);
        
        // Verificar que sea una imagen
        if (!file.type.startsWith('image/')) {
            console.warn('[setupDragAndDropConImagen] ⚠️ El archivo no es una imagen:', file.type);
            mostrarModalError('Por favor arrastra solo archivos de imagen');
            return;
        }
        
        // Crear un input file temporal para usar la función existente
        const tempInput = document.createElement('input');
        tempInput.type = 'file';
        tempInput.files = files;
        
        // Usar la función existente para manejar la imagen
        if (typeof window.manejarImagenesPrenda === 'function') {
            window.manejarImagenesPrenda(tempInput);
        } else {
            // La función manejarImagenesPrenda no está disponible, no hacer nada
        }
    });
    
    // Evento click como alternativa
    newPreview.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        // Enfocar el elemento para poder recibir eventos paste
        newPreview.focus();
        
        // Abrir el selector de archivos
        const inputFotos = document.getElementById('nueva-prenda-foto-input');
        if (inputFotos) {
            inputFotos.click();
        } else {
            // Input de fotos no encontrado, no hacer nada
        }
    });
    
    // Evento focus para mostrar indicador visual
    newPreview.addEventListener('focus', (e) => {
        newPreview.style.boxShadow = '0 0 0 3px rgba(59, 130, 246, 0.3)';
        newPreview.style.border = '2px solid #3b82f6';
    });
    
    // Evento blur para quitar indicador visual
    newPreview.addEventListener('blur', (e) => {
        newPreview.style.boxShadow = '';
        newPreview.style.border = '';
    });
    
    // Prevenir menú contextual del navegador
    newPreview.addEventListener('contextmenu', (e) => {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        return false;
    }, true);
    
    newPreview.addEventListener('mouseup', (e) => {
        if (e.button === 2) {
            e.preventDefault();
            e.stopPropagation();
        }
    });
    
    // Evento para mostrar menú personalizado
    // NOTA: Usamos mousedown + botón derecho como alternativa ya que contextmenu está bloqueado
    newPreview.addEventListener('mousedown', (e) => {
        console.log(`[setupDragDropProceso] 🖱️ Mousedown detectado en proceso ${procesoNumero}, botón: ${e.button}`);
        
        // Botón derecho = 2
        if (e.button === 2) {
            console.log(`[setupDragDropProceso] 🎉 ¡Botón derecho detectado en proceso ${procesoNumero}!`);
            e.preventDefault();
            e.stopPropagation();
            
            // Enfocar el elemento
            newPreview.focus();
            
            console.log(`[setupDragDropProceso] 🎯 Elemento enfocado, creando menú...`);
            
            // Calcular posición para evitar que se corte por los bordes
        const menuWidth = 180;
        const menuHeight = 50; // Altura aproximada del menú
        const padding = 10;
        
        let left = e.clientX;
        let top = e.clientY;
        
        // Ajustar posición horizontal si se sale por la derecha
        if (left + menuWidth > window.innerWidth - padding) {
            left = window.innerWidth - menuWidth - padding;
        }
        
        // Ajustar posición vertical si se sale por abajo
        if (top + menuHeight > window.innerHeight - padding) {
            top = window.innerHeight - menuHeight - padding;
        }
        
        // Asegurar que no sea negativo
        left = Math.max(padding, left);
        top = Math.max(padding, top);
        
        // Crear menú contextual
        const menu = document.createElement('div');
        menu.style.cssText = `
            position: fixed;
            left: ${left}px;
            top: ${top}px;
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
            z-index: 999999;
            padding: 4px 0;
            min-width: 180px;
            font-size: 14px;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        `;
        
        // Opción de pegar
        const pasteOption = document.createElement('div');
        pasteOption.style.cssText = `
            padding: 8px 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #374151;
            transition: background-color 0.2s;
        `;
        pasteOption.innerHTML = `
            <span class="material-symbols-rounded" style="font-size: 18px;">content_paste</span>
            Pegar imagen
        `;
        
        // Hover effect
        pasteOption.addEventListener('mouseenter', () => {
            pasteOption.style.backgroundColor = '#f3f4f6';
        });
        pasteOption.addEventListener('mouseleave', () => {
            pasteOption.style.backgroundColor = '';
        });
        
        // Click para pegar
        pasteOption.addEventListener('click', () => {
            // Verificar límite de imágenes
            if (imagenesActuales.length >= 3) {
                console.warn('[setupDragAndDropConImagen] ⚠️ Límite de imágenes alcanzado');
                mostrarModalError('Solo se permiten máximo 3 imágenes por prenda');
                document.body.removeChild(menu);
                return;
            }
            
            // Intentar obtener imagen del portapapeles
            navigator.clipboard.read().then(items => {
                for (let item of items) {
                    if (item.types.includes('image/png') || item.types.includes('image/jpeg') || item.types.includes('image/gif')) {
                        item.getType(item.types[0]).then(blob => {
                            const file = new File([blob], 'pasted-image.png', { type: blob.type });
                            
                            // Crear input temporal
                            const tempInput = document.createElement('input');
                            tempInput.type = 'file';
                            const dataTransfer = new DataTransfer();
                            dataTransfer.items.add(file);
                            tempInput.files = dataTransfer.files;
                            
                            // Usar función existente
                            if (typeof window.manejarImagenesPrenda === 'function') {
                                window.manejarImagenesPrenda(tempInput);
                            }
                        });
                        break;
                    }
                }
            }).catch(err => {
                console.warn('[setupDragAndDropConImagen] 📋 No se pudo acceder al portapapeles:', err);
                // Fallback: intentar con el evento paste tradicional
                const pasteEvent = new ClipboardEvent('paste', {
                    clipboardData: new DataTransfer()
                });
                newPreview.dispatchEvent(pasteEvent);
            });
            
            // Cerrar menú
            document.body.removeChild(menu);
        });
        
        menu.appendChild(pasteOption);
        
        // Agregar al DOM
        document.body.appendChild(menu);
        
        // Cerrar menú al hacer clic fuera
        const closeMenu = (e) => {
            if (menu && document.body.contains(menu)) {
                document.body.removeChild(menu);
                document.removeEventListener('click', closeMenu);
            }
        };
        
        // Cerrar menú al presionar Escape
        const closeMenuEscape = (e) => {
            if (e.key === 'Escape') {
                if (menu && document.body.contains(menu)) {
                    document.body.removeChild(menu);
                }
                document.removeEventListener('keydown', closeMenuEscape);
            }
        };
        
        setTimeout(() => {
            document.addEventListener('click', closeMenu);
            document.addEventListener('keydown', closeMenuEscape);
        }, 100);
        }
    });
    
    // Evento paste para permitir pegar imágenes desde el portapapeles
    newPreview.addEventListener('paste', (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        // Verificar límite de imágenes
        if (imagenesActuales.length >= 3) {
            console.warn('[setupDragAndDropConImagen] ⚠️ Límite de imágenes alcanzado');
            mostrarModalError('Solo se permiten máximo 3 imágenes por prenda');
            return;
        }
        
        // Obtener items del portapapeles
        const items = e.clipboardData.items;
        if (items.length === 0) {
            return;
        }
        
        // Buscar imágenes en el portapapeles
        for (let i = 0; i < items.length; i++) {
            const item = items[i];
            
            // Verificar si es una imagen
            if (item.type.startsWith('image/')) {
                // Obtener el archivo
                const file = item.getAsFile();
                if (file) {
                    console.log('[setupDragAndDropConImagen] 📋 Imagen pegada del portapapeles:', file.name, file.type);
                    
                    // Crear un input file temporal para usar la función existente
                    const tempInput = document.createElement('input');
                    tempInput.type = 'file';
                    tempInput.files = new DataTransfer().files;
                    
                    // Agregar el archivo al DataTransfer
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    tempInput.files = dataTransfer.files;
                    
                    // Usar la función existente para manejar la imagen
                    if (typeof window.manejarImagenesPrenda === 'function') {
                        window.manejarImagenesPrenda(tempInput);
                    } else {
                        // La función manejarImagenesPrenda no está disponible, no hacer nada
                    }
                    
                    // Salir después de procesar la primera imagen
                    break;
                }
            }
        }
    });
    
    // Hacer que el elemento sea focusable para recibir eventos paste
    newPreview.setAttribute('tabindex', '0');
    newPreview.style.outline = 'none';
};

/**
 * Configura los event listeners para drag & drop en imágenes de tela
 */
window.setupDragDropTela = function(dropZone) {
    if (!dropZone) {
        return;
    }
    
    // Prevenir comportamiento por defecto para todos los eventos
    const preventDefaults = (e) => {
        e.preventDefault();
        e.stopPropagation();
    };
    
    // Eventos de drag
    dropZone.addEventListener('dragover', preventDefaults);
    dropZone.addEventListener('dragenter', preventDefaults);
    dropZone.addEventListener('dragleave', preventDefaults);
    
    // Evento dragover con feedback visual
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.style.background = 'rgba(59, 130, 246, 0.1)';
        dropZone.style.border = '2px dashed #3b82f6';
        dropZone.style.borderRadius = '6px';
        dropZone.style.transform = 'scale(1.02)';
        dropZone.style.padding = '8px';
        
        // Cambiar el botón para indicar que está activo
        const button = dropZone.querySelector('button');
        if (button) {
            button.style.background = '#2563eb';
            button.style.transform = 'scale(1.05)';
            button.style.boxShadow = '0 4px 12px rgba(59, 130, 246, 0.3)';
        }
        
        // Resaltar el texto de ayuda
        const helpText = dropZone.querySelector('div[style*="color: #6b7280"]');
        if (helpText) {
            helpText.style.color = '#3b82f6';
            helpText.style.fontWeight = '500';
            const icon = helpText.querySelector('.material-symbols-rounded');
            if (icon) {
                icon.style.opacity = '1';
            }
        }
        
        // Feedback visual durante drag over
    });
    
    // Evento dragleave para restaurar estilos
    dropZone.addEventListener('dragleave', (e) => {
        e.preventDefault();
        dropZone.style.background = '';
        dropZone.style.border = '';
        dropZone.style.borderRadius = '';
        dropZone.style.padding = '';
        dropZone.style.transform = '';
        
        // Restaurar botón
        const button = dropZone.querySelector('button');
        if (button) {
            button.style.background = '';
            button.style.transform = '';
            button.style.boxShadow = '';
        }
        
        // Restaurar texto de ayuda
        const helpText = dropZone.querySelector('div[style*="color: #6b7280"]');
        if (helpText) {
            helpText.style.color = '#6b7280';
            helpText.style.fontWeight = 'normal';
            const icon = helpText.querySelector('.material-symbols-rounded');
            if (icon) {
                icon.style.opacity = '0.5';
            }
        }
        
    });
    
    // Evento drop - manejar archivos arrastrados
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        // Restaurar estilos
        dropZone.style.background = '';
        dropZone.style.border = '';
        dropZone.style.borderRadius = '';
        dropZone.style.padding = '';
        dropZone.style.transform = '';
        
        // Restaurar botón
        const button = dropZone.querySelector('button');
        if (button) {
            button.style.background = '';
            button.style.transform = '';
            button.style.boxShadow = '';
        }
        
        // Restaurar texto de ayuda
        const helpText = dropZone.querySelector('div[style*="color: #6b7280"]');
        if (helpText) {
            helpText.style.color = '#6b7280';
            helpText.style.fontWeight = 'normal';
            const icon = helpText.querySelector('.material-symbols-rounded');
            if (icon) {
                icon.style.opacity = '0.5';
            }
        }
        
        // Verificar si hay archivos
        const files = e.dataTransfer.files;
        if (files.length === 0) {
            return;
        }
        
        // Procesar el primer archivo (solo imágenes)
        const file = files[0];
        
        // Verificar que sea una imagen
        if (!file.type.startsWith('image/')) {
            mostrarModalError('Por favor arrastra solo archivos de imagen para la tela');
            return;
        }
        
        // Crear un input file temporal para usar la función existente
        const tempInput = document.createElement('input');
        tempInput.type = 'file';
        tempInput.files = files;
        
        // Usar la función existente para manejar la imagen
        if (typeof window.manejarImagenTela === 'function') {
            window.manejarImagenTela(tempInput);
        } else {
            // La función manejarImagenTela no está disponible, no hacer nada
        }
    });
    
    // Evento click para enfocar y permitir pegar
    dropZone.addEventListener('click', (e) => {
        // Enfocar el elemento para poder recibir eventos paste
        dropZone.focus();
    });
    
    // Evento focus para mostrar indicador visual
    dropZone.addEventListener('focus', (e) => {
        dropZone.style.boxShadow = '0 0 0 3px rgba(59, 130, 246, 0.3)';
        dropZone.style.border = '2px solid #3b82f6';
    });
    
    // Evento blur para quitar indicador visual
    dropZone.addEventListener('blur', (e) => {
        dropZone.style.boxShadow = '';
        dropZone.style.border = '';
    });
    
    // Evento contextmenu para mostrar menú personalizado
    dropZone.addEventListener('contextmenu', (e) => {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        
        // Enfocar el elemento
        dropZone.focus();
        
        // Calcular posición para evitar que se corte por los bordes
        const menuWidth = 180;
        const menuHeight = 50; // Altura aproximada del menú
        const padding = 10;
        
        let left = e.clientX;
        let top = e.clientY;
        
        // Ajustar posición horizontal si se sale por la derecha
        if (left + menuWidth > window.innerWidth - padding) {
            left = window.innerWidth - menuWidth - padding;
        }
        
        // Ajustar posición vertical si se sale por abajo
        if (top + menuHeight > window.innerHeight - padding) {
            top = window.innerHeight - menuHeight - padding;
        }
        
        // Asegurar que no sea negativo
        left = Math.max(padding, left);
        top = Math.max(padding, top);
        
        // Crear menú contextual
        const menu = document.createElement('div');
        menu.style.cssText = `
            position: fixed;
            left: ${left}px;
            top: ${top}px;
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
            z-index: 999999;
            padding: 4px 0;
            min-width: 180px;
            font-size: 14px;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        `;
        
        // Opción de pegar
        const pasteOption = document.createElement('div');
        pasteOption.style.cssText = `
            padding: 8px 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #374151;
            transition: background-color 0.2s;
        `;
        pasteOption.innerHTML = `
            <span class="material-symbols-rounded" style="font-size: 18px;">content_paste</span>
            Pegar imagen de tela
        `;
        
        // Hover effect
        pasteOption.addEventListener('mouseenter', () => {
            pasteOption.style.backgroundColor = '#f3f4f6';
        });
        pasteOption.addEventListener('mouseleave', () => {
            pasteOption.style.backgroundColor = '';
        });
        
        // Click para pegar
        pasteOption.addEventListener('click', () => {
            console.log('[setupDragDropTela] 📋 Iniciando pegado de imagen de tela...');
            
            // Intentar obtener imagen del portapapeles
            navigator.clipboard.read().then(items => {
                console.log('[setupDragDropTela] 📋 Items en portapapeles:', items.length);
                
                for (let item of items) {
                    console.log('[setupDragDropTela] 📋 Tipos disponibles:', item.types);
                    
                    // Verificar si hay algún tipo de imagen
                    const imageTypes = item.types.filter(type => 
                        type.includes('image/png') || 
                        type.includes('image/jpeg') || 
                        type.includes('image/gif') || 
                        type.includes('image/webp') ||
                        type.includes('image/bmp')
                    );
                    
                    console.log('[setupDragDropTela] 📋 Tipos de imagen encontrados:', imageTypes);
                    
                    if (imageTypes.length > 0) {
                        console.log('[setupDragDropTela] 📋 Procesando tipo:', imageTypes[0]);
                        
                        item.getType(imageTypes[0]).then(blob => {
                            console.log('[setupDragDropTela] 📋 Blob obtenido:', blob.type, blob.size);
                            
                            const file = new File([blob], 'pasted-tela-image.png', { type: blob.type });
                            console.log('[setupDragDropTela] 📋 File creado:', file.name, file.type, file.size);
                            
                            // Crear input temporal
                            const tempInput = document.createElement('input');
                            tempInput.type = 'file';
                            const dataTransfer = new DataTransfer();
                            dataTransfer.items.add(file);
                            tempInput.files = dataTransfer.files;
                            
                            console.log('[setupDragDropTela] 📋 Input temporal creado, archivos:', tempInput.files.length);
                            
                            // Usar función existente
                            if (typeof window.manejarImagenTela === 'function') {
                                console.log('[setupDragDropTela] 📋 Llamando a manejarImagenTela...');
                                window.manejarImagenTela(tempInput);
                            } else {
                                console.error('[setupDragDropTela] ❌ La función manejarImagenTela no está disponible');
                            }
                        }).catch(err => {
                            console.error('[setupDragDropTela] ❌ Error al obtener blob:', err);
                            mostrarModalError('No se pudo procesar la imagen del portapapeles');
                        });
                        break;
                    }
                }
                
                // Si no se encontraron imágenes
                if (items.length > 0 && !items.some(item => 
                    item.types.some(type => type.includes('image/'))
                )) {
                    console.warn('[setupDragDropTela] ⚠️ No hay imágenes en el portapapeles');
                    mostrarModalError('El portapapeles no contiene imágenes. Por favor copia una imagen primero.');
                }
                
            }).catch(err => {
                console.warn('[setupDragDropTela] 📋 No se pudo acceder al portapapeles:', err);
                mostrarModalError('No se pudo acceder al portapapeles. Intenta copiar una imagen y usar Ctrl+V.');
            });
            
            // Cerrar menú
            document.body.removeChild(menu);
        });
        
        menu.appendChild(pasteOption);
        
        // Agregar al DOM
        document.body.appendChild(menu);
        
        // Cerrar menú al hacer clic fuera
        const closeMenu = (e) => {
            if (menu && document.body.contains(menu)) {
                document.body.removeChild(menu);
                document.removeEventListener('click', closeMenu);
            }
        };
        
        // Cerrar menú al presionar Escape
        const closeMenuEscape = (e) => {
            if (e.key === 'Escape') {
                if (menu && document.body.contains(menu)) {
                    document.body.removeChild(menu);
                }
                document.removeEventListener('keydown', closeMenuEscape);
            }
        };
        
        setTimeout(() => {
            document.addEventListener('click', closeMenu);
            document.addEventListener('keydown', closeMenuEscape);
        }, 100);
    });
    
    // Evento paste para permitir pegar imágenes desde el portapapeles
    dropZone.addEventListener('paste', (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('[setupDragDropTela] 📋 Evento paste detectado en zona de tela');
        console.log('[setupDragDropTela] 📋 ClipboardData items:', e.clipboardData?.items?.length || 0);
        
        // Obtener items del portapapeles
        const items = e.clipboardData.items;
        if (items.length === 0) {
            console.warn('[setupDragDropTela] 📋 No hay items en el portapapeles');
            return;
        }
        
        console.log('[setupDragDropTela] 📋 Items disponibles:');
        for (let i = 0; i < items.length; i++) {
            console.log(`[setupDragDropTela] 📋 Item ${i}:`, items[i].type, items[i].kind);
        }
        
        // Buscar imágenes en el portapapeles
        for (let i = 0; i < items.length; i++) {
            const item = items[i];
            
            // Verificar si es una imagen
            if (item.type.startsWith('image/')) {
                console.log('[setupDragDropTela] 📋 Imagen encontrada:', item.type);
                
                // Obtener el archivo
                const file = item.getAsFile();
                if (file) {
                    console.log('[setupDragDropTela] 📋 Archivo obtenido:', file.name, file.type, file.size);
                    
                    // Crear un input file temporal para usar la función existente
                    const tempInput = document.createElement('input');
                    tempInput.type = 'file';
                    tempInput.files = new DataTransfer().files;
                    
                    // Agregar el archivo al DataTransfer
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    tempInput.files = dataTransfer.files;
                    
                    console.log('[setupDragDropTela] 📋 Input temporal preparado');
                    
                    // Usar la función existente para manejar la imagen
                    if (typeof window.manejarImagenTela === 'function') {
                        console.log('[setupDragDropTela] 📋 Llamando a manejarImagenTela...');
                        window.manejarImagenTela(tempInput);
                    } else {
                        console.error('[setupDragDropTela] ❌ La función manejarImagenTela no está disponible');
                    }
                    
                    // Salir después de procesar la primera imagen
                    break;
                } else {
                    console.warn('[setupDragDropTela] ⚠️ No se pudo obtener el archivo del item');
                }
            }
        }
        
        // Si no se encontraron imágenes
        const hasImages = Array.from(items).some(item => item.type.startsWith('image/'));
        if (!hasImages) {
            console.warn('[setupDragDropTela] ⚠️ No se encontraron imágenes en el portapapeles');
            mostrarModalError('El portapapeles no contiene imágenes válidas. Por favor copia una imagen primero.');
        }
    });
    
    // Hacer que el elemento sea focusable para recibir eventos paste
    dropZone.setAttribute('tabindex', '0');
    dropZone.style.outline = 'none';
};

/**
 * Configura los event listeners para drag & drop en el preview de imágenes de tela
 * Permite arrastrar más imágenes directamente sobre el área donde ya se muestran
 */
window.setupDragDropTelaPreview = function(previewElement) {
    if (!previewElement) {
        return;
    }
    
    // Prevenir comportamiento por defecto para todos los eventos
    const preventDefaults = (e) => {
        e.preventDefault();
        e.stopPropagation();
    };
    
    // Eventos de drag
    previewElement.addEventListener('dragover', preventDefaults);
    previewElement.addEventListener('dragenter', preventDefaults);
    previewElement.addEventListener('dragleave', preventDefaults);
    
    // Evento dragover con feedback visual
    previewElement.addEventListener('dragover', (e) => {
        e.preventDefault();
        previewElement.style.background = 'rgba(59, 130, 246, 0.15)';
        previewElement.style.border = '2px dashed #3b82f6';
        previewElement.style.opacity = '0.8';
        previewElement.style.transform = 'scale(1.02)';
    });
    
    // Evento dragleave para restaurar estilos
    previewElement.addEventListener('dragleave', (e) => {
        e.preventDefault();
        previewElement.style.background = '';
        previewElement.style.border = '';
        previewElement.style.opacity = '1';
        previewElement.style.transform = '';
    });
    
    // Evento drop - manejar archivos arrastrados
    previewElement.addEventListener('drop', (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        // Restaurar estilos
        previewElement.style.background = '';
        previewElement.style.border = '';
        previewElement.style.opacity = '1';
        previewElement.style.transform = '';
        
        // Verificar si hay archivos
        const files = e.dataTransfer.files;
        if (files.length === 0) {
            return;
        }
        
        // Procesar el primer archivo (solo imágenes)
        const file = files[0];
        
        // Verificar que sea una imagen
        if (!file.type.startsWith('image/')) {
            mostrarModalError('Por favor arrastra solo archivos de imagen para la tela');
            return;
        }
        
        // Crear un input file temporal para usar la función existente
        const tempInput = document.createElement('input');
        tempInput.type = 'file';
        tempInput.files = files;
        
        // Usar la función existente para manejar la imagen
        if (typeof window.manejarImagenTela === 'function') {
            window.manejarImagenTela(tempInput);
        } else {
            // La función manejarImagenTela no está disponible, no hacer nada
        }
    });
};

/**
 * Inicialización del drag & drop cuando el DOM está listo
 */
window.inicializarDragDropPrenda = function() {
    const preview = document.getElementById('nueva-prenda-foto-preview');
    if (preview) {
        // Verificar si ya hay imágenes
        if (window.imagenesPrendaStorage && window.imagenesPrendaStorage.obtenerImagenes().length > 0) {
            const imagenes = window.imagenesPrendaStorage.obtenerImagenes();
            window.setupDragAndDropConImagen(preview, imagenes);
        } else {
            window.setupDragAndDrop(preview);
        }
    } else {
        // Preview no encontrado, no hacer nada
    }
};

/**
 * Inicialización automática del drag & drop para imágenes de tela
 */
window.inicializarDragDropTela = function() {
    // Configurar drag & drop en el botón
    const dropZone = document.getElementById('nueva-prenda-tela-drop-zone');
    if (dropZone) {
        window.setupDragDropTela(dropZone);
    }
    
    // Configurar drag & drop en el preview si ya hay imágenes
    const preview = document.getElementById('nueva-prenda-tela-preview');
    if (preview && preview.style.display !== 'none') {
        if (typeof window.setupDragDropTelaPreview === 'function') {
            window.setupDragDropTelaPreview(preview);
        }
    }
};

/**
 * Configura los event listeners para drag & drop en previews de procesos
 * Soporta múltiples previews (1, 2, 3) con funcionalidad de pegar
 */
window.setupDragDropProceso = function(previewElement, procesoNumero) {
    console.log(`[setupDragDropProceso] 🎯 Configurando proceso ${procesoNumero}...`);
    
    if (!previewElement) {
        console.error(`[setupDragDropProceso] ❌ Preview element es null para proceso ${procesoNumero}`);
        return;
    }
    
    console.log(`[setupDragDropProceso] ✅ Preview element encontrado para proceso ${procesoNumero}`);
    
    // Limpiar event listeners anteriores clonando el elemento
    const newPreview = previewElement.cloneNode(true);
    previewElement.parentNode.replaceChild(newPreview, previewElement);
    
    console.log(`[setupDragDropProceso] 🔄 Element clonado y reemplazado para proceso ${procesoNumero}`);
    
    // Prevenir comportamiento por defecto para todos los eventos
    const preventDefaults = (e) => {
        e.preventDefault();
        e.stopPropagation();
    };
    
    // Eventos de drag
    newPreview.addEventListener('dragover', preventDefaults);
    newPreview.addEventListener('dragenter', preventDefaults);
    newPreview.addEventListener('dragleave', preventDefaults);
    
    // Evento dragover con feedback visual
    newPreview.addEventListener('dragover', (e) => {
        e.preventDefault();
        newPreview.style.background = 'rgba(59, 130, 246, 0.1)';
        newPreview.style.border = '2px dashed #3b82f6';
        newPreview.style.opacity = '0.8';
        newPreview.style.transform = 'scale(1.05)';
    });
    
    // Evento dragleave para restaurar estilos
    newPreview.addEventListener('dragleave', (e) => {
        e.preventDefault();
        newPreview.style.background = '';
        newPreview.style.border = '';
        newPreview.style.opacity = '1';
        newPreview.style.transform = '';
    });
    
    // Evento drop - manejar archivos arrastrados
    newPreview.addEventListener('drop', (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        // Restaurar estilos
        newPreview.style.background = '';
        newPreview.style.border = '';
        newPreview.style.opacity = '1';
        newPreview.style.transform = '';
        
        // Verificar si hay archivos
        const files = e.dataTransfer.files;
        if (files.length === 0) {
            return;
        }
        
        // Procesar el primer archivo (solo imágenes)
        const file = files[0];
        
        // Verificar que sea una imagen
        if (!file.type.startsWith('image/')) {
            mostrarModalError('Por favor arrastra solo archivos de imagen para el proceso');
            return;
        }
        
        // Crear un input file temporal para usar la función existente
        const tempInput = document.createElement('input');
        tempInput.type = 'file';
        tempInput.files = files;
        
        // Usar la función existente para manejar la imagen
        if (typeof window.manejarImagenProceso === 'function') {
            window.manejarImagenProceso(tempInput, procesoNumero);
        } else {
            console.error(`[setupDragDropProceso] ❌ La función manejarImagenProceso no está disponible para proceso ${procesoNumero}`);
        }
    });
    
    // Evento click para enfocar y permitir pegar
    newPreview.addEventListener('click', (e) => {
        console.log(`[setupDragDropProceso] 🖱️ Click detectado en proceso ${procesoNumero}`);
        
        // Cerrar cualquier menú contextual abierto
        const menuAbierto = document.querySelector('.proceso-context-menu-debug');
        if (menuAbierto && menuAbierto.parentElement) {
            console.log(`[setupDragDropProceso] 🗑️ Cerrando menú contextual previo...`);
            menuAbierto.parentElement.removeChild(menuAbierto);
        }
        
        // Enfocar el elemento para poder recibir eventos paste
        newPreview.focus();
        
        // Abrir el selector de archivos original
        const inputId = `proceso-foto-input-${procesoNumero}`;
        const inputElement = document.getElementById(inputId);
        if (inputElement) {
            console.log(`[setupDragDropProceso] 📁 Abriendo input ${inputId}`);
            inputElement.click();
        } else {
            console.error(`[setupDragDropProceso] ❌ Input ${inputId} no encontrado`);
        }
    });
    
    // IMPORTANTE: Remover el onclick del HTML para que no interfiera
    newPreview.removeAttribute('onclick');
    console.log(`[setupDragDropProceso] 🗑️ Onclick removido del HTML para proceso ${procesoNumero}`);
    
    // Evento focus para mostrar indicador visual
    newPreview.addEventListener('focus', (e) => {
        newPreview.style.boxShadow = '0 0 0 3px rgba(59, 130, 246, 0.3)';
        newPreview.style.border = '2px solid #3b82f6';
    });
    
    // Evento blur para quitar indicador visual
    newPreview.addEventListener('blur', (e) => {
        newPreview.style.boxShadow = '';
        newPreview.style.border = '';
    });
    
    // Prevenir menú contextual del navegador - múltiples formas
    newPreview.addEventListener('contextmenu', (e) => {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        console.log(`[setupDragDropProceso] 🚫 Evento contextmenu bloqueado`);
        return false;
    }, true); // Usar captura para que se ejecute primero
    
    // Bloquear también en mouseup para mayor efectividad
    newPreview.addEventListener('mouseup', (e) => {
        if (e.button === 2) {
            e.preventDefault();
            e.stopPropagation();
            console.log(`[setupDragDropProceso] 🚫 Mouseup botón derecho bloqueado`);
        }
    });
    
    // Evento para mostrar menú personalizado
    // NOTA: Usamos mousedown + botón derecho como alternativa ya que contextmenu está bloqueado
    newPreview.addEventListener('mousedown', (e) => {
        console.log(`[setupDragDropProceso] 🖱️ Mousedown detectado en proceso ${procesoNumero}, botón: ${e.button}`);
        
        // Botón derecho = 2
        if (e.button === 2) {
            console.log(`[setupDragDropProceso] 🎉 ¡Botón derecho detectado en proceso ${procesoNumero}!`);
            e.preventDefault();
            e.stopPropagation();
            
            // Enfocar el elemento
            newPreview.focus();
            
            console.log(`[setupDragDropProceso] 🎯 Elemento enfocado, creando menú...`);
            
            // Calcular posición para evitar que se corte por los bordes
            const menuWidth = 180;
            const menuHeight = 50; // Altura aproximada del menú
            const padding = 10;
            
            let left = e.clientX;
            let top = e.clientY;
            
            // Ajustar posición horizontal si se sale por la derecha
            if (left + menuWidth > window.innerWidth - padding) {
                left = window.innerWidth - menuWidth - padding;
            }
            
            // Ajustar posición vertical si se sale por abajo
            if (top + menuHeight > window.innerHeight - padding) {
                top = window.innerHeight - menuHeight - padding;
            }
            
            // Asegurar que no sea negativo
            left = Math.max(padding, left);
            top = Math.max(padding, top);
            
            // Crear menú contextual
            const menu = document.createElement('div');
            menu.style.cssText = `
                position: fixed !important;
                left: ${left}px !important;
                top: ${top}px !important;
                background: white !important;
                border: 1px solid #d1d5db !important;
                border-radius: 6px !important;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25) !important;
                z-index: 1 !important;
                padding: 4px 0 !important;
                min-width: 180px !important;
                font-size: 14px !important;
                backdrop-filter: blur(10px) !important;
                visibility: visible !important;
                opacity: 1 !important;
                display: block !important;
                pointer-events: auto !important;
            `;
            
            // Agregar clase para debugging
            menu.className = 'proceso-context-menu-debug';
            
            // Prevenir propagación de eventos en el menú
            menu.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
            });
            menu.addEventListener('mousedown', (e) => {
                e.preventDefault();
                e.stopPropagation();
            });
            
            // Opción de pegar
            const pasteOption = document.createElement('div');
            pasteOption.style.cssText = `
                padding: 8px 16px !important;
                cursor: pointer !important;
                display: flex !important;
                align-items: center !important;
                gap: 8px !important;
                color: #374151 !important;
                transition: background-color 0.2s !important;
                user-select: none !important;
                white-space: nowrap !important;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
                line-height: 1.5 !important;
            `;
            pasteOption.innerHTML = `
                <span class="material-symbols-rounded" style="font-size: 18px; flex-shrink: 0;">content_paste</span>
                <span>Pegar imagen ${procesoNumero}</span>
            `;
            
            // Hover effect
            pasteOption.addEventListener('mouseenter', () => {
                pasteOption.style.backgroundColor = '#f3f4f6';
            });
            pasteOption.addEventListener('mouseleave', () => {
                pasteOption.style.backgroundColor = '';
            });
            
            // Click para pegar
            pasteOption.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log(`[setupDragDropProceso] 📋 Iniciando pegado de imagen de proceso ${procesoNumero}...`);
                
                // Cerrar menú inmediatamente para evitar múltiples clics
                if (menu && menu.parentElement) {
                    menu.parentElement.removeChild(menu);
                    // Restaurar pointer-events si el contenedor está vacío
                    const overlay = document.getElementById('drag-drop-overlay-container');
                    if (overlay && overlay.children.length === 0) {
                        overlay.style.pointerEvents = 'none';
                    }
                }
                
                // Intentar obtener imagen del portapapeles
                navigator.clipboard.read().then(items => {
                    console.log(`[setupDragDropProceso] 📋 Items en portapapeles:`, items.length);
                    
                    for (let item of items) {
                        console.log(`[setupDragDropProceso] 📋 Tipos disponibles:`, item.types);
                        
                        // Verificar si hay algún tipo de imagen
                        const imageTypes = item.types.filter(type => 
                            type.includes('image/png') || 
                            type.includes('image/jpeg') || 
                            type.includes('image/gif') || 
                            type.includes('image/webp') ||
                            type.includes('image/bmp')
                        );
                        
                        console.log(`[setupDragDropProceso] 📋 Tipos de imagen encontrados:`, imageTypes);
                        
                        if (imageTypes.length > 0) {
                            console.log(`[setupDragDropProceso] 📋 Procesando tipo:`, imageTypes[0]);
                            
                            item.getType(imageTypes[0]).then(blob => {
                                console.log(`[setupDragDropProceso] 📋 Blob obtenido:`, blob.type, blob.size);
                                
                                const file = new File([blob], `pasted-proceso-${procesoNumero}-image.png`, { type: blob.type });
                                console.log(`[setupDragDropProceso] 📋 File creado:`, file.name, file.type, file.size);
                                
                                // Crear input temporal
                                const tempInput = document.createElement('input');
                                tempInput.type = 'file';
                                const dataTransfer = new DataTransfer();
                                dataTransfer.items.add(file);
                                tempInput.files = dataTransfer.files;
                                
                                console.log(`[setupDragDropProceso] 📋 Input temporal creado, archivos:`, tempInput.files.length);
                                
                                // Usar función existente
                                if (typeof window.manejarImagenProceso === 'function') {
                                    console.log(`[setupDragDropProceso] 📋 Llamando a manejarImagenProceso...`);
                                    window.manejarImagenProceso(tempInput, procesoNumero);
                                } else {
                                    console.error(`[setupDragDropProceso] ❌ La función manejarImagenProceso no está disponible para proceso ${procesoNumero}`);
                                }
                            }).catch(err => {
                                console.error(`[setupDragDropProceso] ❌ Error al obtener blob:`, err);
                                mostrarModalError('No se pudo procesar la imagen del portapapeles');
                            });
                            break;
                        }
                    }
                    
                    // Si no se encontraron imágenes
                    if (items.length > 0 && !items.some(item => 
                        item.types.some(type => type.includes('image/'))
                    )) {
                        console.warn(`[setupDragDropProceso] ⚠️ No hay imágenes en el portapapeles`);
                        mostrarModalError('El portapapeles no contiene imágenes. Por favor copia una imagen primero.');
                    }
                    
                }).catch(err => {
                    console.warn(`[setupDragDropProceso] 📋 No se pudo acceder al portapapeles:`, err);
                    mostrarModalError('No se pudo acceder al portapapeles. Intenta copiar una imagen y usar Ctrl+V.');
                });
            });
            
            menu.appendChild(pasteOption);
            
            // Agregar al contenedor overlay (sin restricciones de overflow)
            const overlayContainer = obtenerContenedorOverlay();
            // Cambiar pointer-events para que el menú sea clickeable
            overlayContainer.style.pointerEvents = 'auto';
            overlayContainer.appendChild(menu);
            
            console.log(`[setupDragDropProceso] ✅ Menú agregado al OVERLAY en posición (${left}, ${top})`);
            
            
            // Cerrar menú al hacer clic fuera (pero NO dentro del menú)
            const closeMenu = (e) => {
                // Verificar si el clic fue dentro del menú
                if (menu && menu.contains(e.target)) {
                    return; // No cerrar si es dentro del menú
                }
                if (menu && menu.parentElement) {
                    console.log(`[setupDragDropProceso] 🔌 Cerrando menú por clic fuera`);
                    menu.parentElement.removeChild(menu);
                    // Restaurar pointer-events si el contenedor está vacío
                    const overlay = document.getElementById('drag-drop-overlay-container');
                    if (overlay && overlay.children.length === 0) {
                        overlay.style.pointerEvents = 'none';
                    }
                    document.removeEventListener('click', closeMenu);
                    document.removeEventListener('mousedown', closeMenu);
                }
            };
            
            // Cerrar menú al presionar Escape
            const closeMenuEscape = (e) => {
                if (e.key === 'Escape') {
                    if (menu && menu.parentElement) {
                        console.log(`[setupDragDropProceso] 🔌 Cerrando menú por Escape`);
                        menu.parentElement.removeChild(menu);
                        // Restaurar pointer-events si el contenedor está vacío
                        const overlay = document.getElementById('drag-drop-overlay-container');
                        if (overlay && overlay.children.length === 0) {
                            overlay.style.pointerEvents = 'none';
                        }
                    }
                    document.removeEventListener('keydown', closeMenuEscape);
                }
            };
            
            setTimeout(() => {
                console.log(`[setupDragDropProceso] 📌 Activando listeners para cerrar menú`);
                document.addEventListener('click', closeMenu);
                document.addEventListener('mousedown', closeMenu);
                document.addEventListener('keydown', closeMenuEscape);
            }, 200);
        }
    });
    
    // Evento paste para permitir pegar imágenes desde el portapapeles
    newPreview.addEventListener('paste', (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        console.log(`[setupDragDropProceso] 📋 Evento paste detectado en proceso ${procesoNumero}`);
        console.log(`[setupDragDropProceso] 📋 ClipboardData items:`, e.clipboardData?.items?.length || 0);
        
        // Obtener items del portapapeles
        const items = e.clipboardData.items;
        if (items.length === 0) {
            console.warn(`[setupDragDropProceso] 📋 No hay items en el portapapeles`);
            return;
        }
        
        console.log(`[setupDragDropProceso] 📋 Items disponibles:`);
        for (let i = 0; i < items.length; i++) {
            console.log(`[setupDragDropProceso] 📋 Item ${i}:`, items[i].type, items[i].kind);
        }
        
        // Buscar imágenes en el portapapeles
        for (let i = 0; i < items.length; i++) {
            const item = items[i];
            
            // Verificar si es una imagen
            if (item.type.startsWith('image/')) {
                console.log(`[setupDragDropProceso] 📋 Imagen encontrada:`, item.type);
                
                // Obtener el archivo
                const file = item.getAsFile();
                if (file) {
                    console.log(`[setupDragDropProceso] 📋 Archivo obtenido:`, file.name, file.type, file.size);
                    
                    // Crear un input file temporal para usar la función existente
                    const tempInput = document.createElement('input');
                    tempInput.type = 'file';
                    tempInput.files = new DataTransfer().files;
                    
                    // Agregar el archivo al DataTransfer
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    tempInput.files = dataTransfer.files;
                    
                    console.log(`[setupDragDropProceso] 📋 Input temporal preparado`);
                    
                    // Usar la función existente para manejar la imagen
                    if (typeof window.manejarImagenProceso === 'function') {
                        console.log(`[setupDragDropProceso] 📋 Llamando a manejarImagenProceso...`);
                        window.manejarImagenProceso(tempInput, procesoNumero);
                    } else {
                        console.error(`[setupDragDropProceso] ❌ La función manejarImagenProceso no está disponible para proceso ${procesoNumero}`);
                    }
                    
                    // Salir después de procesar la primera imagen
                    break;
                } else {
                    console.warn(`[setupDragDropProceso] ⚠️ No se pudo obtener el archivo del item`);
                }
            }
        }
        
        // Si no se encontraron imágenes
        const hasImages = Array.from(items).some(item => item.type.startsWith('image/'));
        if (!hasImages) {
            console.warn(`[setupDragDropProceso] ⚠️ No se encontraron imágenes en el portapapeles`);
            mostrarModalError('El portapapeles no contiene imágenes válidas. Por favor copia una imagen primero.');
        }
    });
    
    // Hacer que el elemento sea focusable para recibir eventos paste
    newPreview.setAttribute('tabindex', '0');
    newPreview.style.outline = 'none';
};

/**
 * Inicialización automática del drag & drop para imágenes de procesos
 */
window.inicializarDragDropProcesos = function() {
    console.log('[inicializarDragDropProcesos] 🚀 Iniciando configuración de drag & drop para procesos...');
    
    // Configurar drag & drop para los 3 previews de procesos
    for (let i = 1; i <= 3; i++) {
        const preview = document.getElementById(`proceso-foto-preview-${i}`);
        console.log(`[inicializarDragDropProcesos] 🔍 Buscando preview ${i}:`, preview ? '✅ encontrado' : '❌ no encontrado');
        
        if (preview) {
            window.setupDragDropProceso(preview, i);
            console.log(`[inicializarDragDropProcesos] ✅ Drag & drop configurado para proceso ${i}`);
        } else {
            console.log(`[inicializarDragDropProcesos] ⚠️ Preview ${i} no encontrado`);
        }
    }
    
    console.log('[inicializarDragDropProcesos] 🏁 Configuración completada');
};

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', window.inicializarDragDropPrenda);
    document.addEventListener('DOMContentLoaded', window.inicializarDragDropTela);
    document.addEventListener('DOMContentLoaded', window.inicializarDragDropProcesos);
} else {
    // El DOM ya está cargado
    window.inicializarDragDropPrenda();
    window.inicializarDragDropTela();
    window.inicializarDragDropProcesos();
}

/**
 * Comando de debugging para investigar menús contextuales
 * Uso en consola: debugContextMenu()
 */
window.debugContextMenu = function() {
    console.log('=== DEBUG: Buscando menús de contexto ===');
    
    // Buscar todos los menús contextuales en el DOM
    const menus = document.querySelectorAll('[class*="context-menu"]');
    console.log(`Menús encontrados en el DOM: ${menus.length}`);
    menus.forEach((menu, idx) => {
        const rect = menu.getBoundingClientRect();
        console.log(`Menú ${idx}:`, {
            clase: menu.className,
            visible: rect.width > 0 && rect.height > 0,
            posición: `(${Math.round(rect.x)}, ${Math.round(rect.y)})`,
            tamaño: `${Math.round(rect.width)}x${Math.round(rect.height)}`,
            zIndex: window.getComputedStyle(menu).zIndex,
            display: window.getComputedStyle(menu).display,
            opacity: window.getComputedStyle(menu).opacity,
        });
    });
    
    // Buscar modales que podrían estar ocultando
    const modals = document.querySelectorAll('[role="dialog"], .modal, [class*="modal"]');
    console.log(`\nModales encontrados: ${modals.length}`);
    modals.forEach((modal, idx) => {
        const style = window.getComputedStyle(modal);
        if (style.display !== 'none' && style.zIndex > 1) {
            console.log(`Modal ${idx}:`, {
                clase: modal.className,
                zIndex: style.zIndex,
                overflow: style.overflow,
                pointerEvents: style.pointerEvents
            });
        }
    });
};

/**
 * Comando para simular un clic derecho y ver si aparece el menú
 * Uso en consola: testRightClick()
 */
window.testRightClick = function() {
    console.log('Simulando clic derecho en proceso 1...');
    const preview = document.getElementById('proceso-foto-preview-1');
    if (!preview) {
        console.error('Preview 1 no encontrado');
        return;
    }
    
    const event = new MouseEvent('mousedown', {
        bubbles: true,
        cancelable: true,
        button: 2, // Botón derecho
        clientX: 200,
        clientY: 300,
    });
    
    preview.dispatchEvent(event);
    console.log('Evento enviado. Revisa la consola para los logs de setupDragDropProceso');
};
