/**
 * ================================================
 * UI HELPERS & UTILITIES
 * ================================================
 * 
 * Funciones auxiliares, limpieza de formularios y modales
 * Utilidades generales para la interfaz de usuario
 * 
 * @module UIHelpers
 */

/**
 * FUNCIÓN AUXILIAR: Limpiar formulario manualmente
 * Se usa como fallback si GestionItemsUI no está disponible
 */
function limpiarFormulario() {
    try {
        const inputs = [
            'nueva-prenda-nombre',
            'nueva-prenda-descripcion',
            'nueva-prenda-origen-select',
            'nueva-prenda-tela',
            'nueva-prenda-color',
            'nueva-prenda-referencia'
        ];
        
        inputs.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                if (element.type === 'select-one') {
                    element.value = element.querySelector('option')?.value || '';
                } else {
                    element.value = '';
                }
            }
        });
        
        // Limpiar checkboxes
        const checkboxes = [
            'aplica-manga',
            'aplica-bolsillos',
            'aplica-broche',
            'checkbox-reflectivo'
        ];
        
        checkboxes.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.checked = false;
            }
        });
        
        // Limpiar inputs de variaciones
        const variaciones = [
            'manga-input',
            'manga-obs',
            'bolsillos-obs',
            'broche-input',
            'broche-obs'
        ];
        
        variaciones.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.value = '';
            }
        });
        
    } catch (e) {
        console.error('[limpiarFormulario] Error al limpiar formulario:', e);
    }
}

/**
 * MODALES: Mostrar límite de imágenes
 */
window.mostrarModalLimiteImagenes = function() {
    const modal = document.createElement('div');
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 100000;';
    
    const contenido = document.createElement('div');
    contenido.style.cssText = 'background: white; border-radius: 12px; padding: 2rem; max-width: 400px; box-shadow: 0 20px 50px rgba(0,0,0,0.3); text-align: center;';
    
    contenido.innerHTML = `
        <div style="font-size: 3rem; color: #f59e0b; margin-bottom: 1rem;">⚠️</div>
        <h2 style="margin: 0 0 1rem 0; color: #1f2937;">Límite de imágenes alcanzado</h2>
        <p style="margin: 0 0 1.5rem 0; color: #6b7280;">Solo puedes agregar un máximo de 3 imágenes por prenda.</p>
        <button onclick="this.parentElement.parentElement.remove()" style="background: #3b82f6; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-weight: 600;">Entendido</button>
    `;
    
    modal.appendChild(contenido);
    document.body.appendChild(modal);
    
    // Cerrar al hacer click fuera
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    });
    
    // Cerrar con Escape
    const cerrarConEsc = (e) => {
        if (e.key === 'Escape') {
            modal.remove();
            document.removeEventListener('keydown', cerrarConEsc);
        }
    };
    document.addEventListener('keydown', cerrarConEsc);
};

/**
 * MODALES: Mostrar error genérico
 */
window.mostrarModalError = function(mensaje) {
    const modal = document.createElement('div');
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 100000;';
    
    const contenido = document.createElement('div');
    contenido.style.cssText = 'background: white; border-radius: 12px; padding: 2rem; max-width: 400px; box-shadow: 0 20px 50px rgba(0,0,0,0.3); text-align: center;';
    
    contenido.innerHTML = `
        <div style="font-size: 3rem; color: #ef4444; margin-bottom: 1rem;">❌</div>
        <h2 style="margin: 0 0 1rem 0; color: #1f2937;">Error</h2>
        <p style="margin: 0 0 1.5rem 0; color: #6b7280;">${mensaje}</p>
        <button onclick="this.parentElement.parentElement.remove()" style="background: #ef4444; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-weight: 600;">Cerrar</button>
    `;
    
    modal.appendChild(contenido);
    document.body.appendChild(modal);
    
    // Cerrar al hacer click fuera
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    });
    
    // Cerrar con Escape
    const cerrarConEsc = (e) => {
        if (e.key === 'Escape') {
            modal.remove();
            document.removeEventListener('keydown', cerrarConEsc);
        }
    };
    document.addEventListener('keydown', cerrarConEsc);
};

/**
 * Galería de imágenes de prenda (versión simplificada para compatibilidad)
 * Solo se crea si no existe ya una implementación más completa
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
            
            if (indiceActual >= imagenesValidas.length) {
                indiceActual = imagenesValidas.length - 1;
            }
            
            imgElement.src = imagenesValidas[indiceActual].src;
            contador.textContent = (indiceActual + 1) + ' de ' + imagenesValidas.length;
        };
        
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
        
        const btnEliminar = document.createElement('button');
        btnEliminar.textContent = '🗑️ Eliminar';
        btnEliminar.style.cssText = 'background: #ef4444; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-size: 1rem; font-weight: 500; transition: background 0.2s;';
        btnEliminar.title = 'Eliminar esta imagen';
        btnEliminar.onmouseover = () => btnEliminar.style.background = '#dc2626';
        btnEliminar.onmouseout = () => btnEliminar.style.background = '#ef4444';
        btnEliminar.onclick = () => {
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
                
                // Eliminar del array original
                const imagenAEliminar = imagenes[indiceActual];
                const indiceEnOriginal = imagenes.indexOf(imagenAEliminar);
                if (indiceEnOriginal !== -1) {
                    imagenes.splice(indiceEnOriginal, 1);
                    console.log(' Imagen eliminada del array original');
                }
                
                // Actualizar storage si está disponible
                if (window.imagenesPrendaStorage && typeof window.imagenesPrendaStorage.establecerImagenes === 'function') {
                    window.imagenesPrendaStorage.establecerImagenes(imagenes);
                    console.log(' [SYNC] window.imagenesPrendaStorage actualizado con', imagenes.length, 'imágenes');
                }
                
                actualizarUI();
                
                // Actualizar preview principal
                if (typeof window.actualizarPreviewPrenda === 'function') {
                    window.actualizarPreviewPrenda();
                    console.log(' [SYNC] Preview principal actualizado - contador debería cambiar a:', imagenes.length, 'fotos');
                }
            };
            botonesDiv.appendChild(btnConfirmarEliminar);
            
            confirmBox.appendChild(botonesDiv);
            confirmModalDiv.appendChild(confirmBox);
            
            confirmModalDiv.onclick = (e) => {
                if (e.target === confirmModalDiv) {
                    confirmModalDiv.remove();
                }
            };
            
            document.body.appendChild(confirmModalDiv);
        };
        
        toolbar.appendChild(btnEliminar);
        
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
        
        const cerrarConEsc = (e) => {
            if (e.key === 'Escape') {
                modal.remove();
                document.removeEventListener('keydown', cerrarConEsc);
            }
        };
        document.addEventListener('keydown', cerrarConEsc);
        
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
