/**
 * GALERÍA DE IMÁGENES PARA PRENDAS
 * Mostrar, navegar y eliminar imágenes de prendas con modal
 */

/**
 * Mostrar galería de imágenes de prenda
 * @param {Array} imagenes - Array de imágenes de la prenda
 * @param {number} prendaIndex - Índice de la prenda
 * @param {number} indiceInicial - Índice inicial a mostrar
 */
window.mostrarGaleriaImagenesPrenda = function(imagenes, prendaIndex = 0, indiceInicial = 0) {

    
    //  Detectar si estamos creando o editando
    const estamosCriando = window.gestionItemsUI?.prendaEditIndex === null;
    const estamoEditando = window.gestionItemsUI?.prendaEditIndex !== null && window.gestionItemsUI?.prendaEditIndex !== undefined;

    
    let imagenesActuales = [];
    let prenda = null;
    
    //  Si estamos EDITANDO, obtener prenda desde GestionItemsUI primero
    if (estamoEditando && window.gestionItemsUI) {
        const itemsOrdenados = window.gestionItemsUI.obtenerItemsOrdenados();
        if (itemsOrdenados && itemsOrdenados[window.gestionItemsUI.prendaEditIndex]) {
            prenda = itemsOrdenados[window.gestionItemsUI.prendaEditIndex];

        }
    }
    
    //  Si no se encontró en GestionItemsUI, intentar desde gestorPrendaSinCotizacion
    if (!prenda && estamoEditando) {
        prenda = window.gestorPrendaSinCotizacion?.obtenerPorIndice(window.gestionItemsUI.prendaEditIndex);

    }
    
    //  Si estamos EDITANDO, obtener imágenes guardadas
    if (estamoEditando && prenda) {
        imagenesActuales = prenda.imagenes || [];

    }
    
    //  Siempre sincronizar con imágenes temporales del storage
    if (window.imagenesPrendaStorage && window.imagenesPrendaStorage.obtenerTodas) {
        const imagenesTemporales = window.imagenesPrendaStorage.obtenerTodas();

        
        if (imagenesTemporales && imagenesTemporales.length > 0) {
            // Crear lista combinada usando Map de objetos únicos (NO por nombre, por referencia del archivo)
            const imagenesMap = new Map();
            
            // Agregar imágenes guardadas (usar índice único)
            imagenesActuales.forEach((img, idx) => {
                const key = `saved-${idx}`;
                imagenesMap.set(key, img);
            });
            
            // Agregar imágenes temporales (usar su índice único)
            imagenesTemporales.forEach((img, idx) => {
                const key = `temporal-${idx}`;
                imagenesMap.set(key, img);
            });
            
            imagenesActuales = Array.from(imagenesMap.values());

        }
    }
    
    //  Si estamos CREANDO y no hay storage, usar las que pasaron como parámetro
    if (estamosCriando && imagenesActuales.length === 0 && imagenes && imagenes.length > 0) {
        imagenesActuales = imagenes;

    }
    
    if (!imagenesActuales || imagenesActuales.length === 0) {

        return;
    }
    
    //  Evitar que se reabra la galería mientras está en uso
    if (window.__galeriaPrendaAbierta) {

        return;
    }
    window.__galeriaPrendaAbierta = true;
    

    
    // Crear blob URLs válidos para las imágenes
    const imagenesConBlobUrl = imagenesActuales.map((img, idx) => {
        let blobUrl;
        if (img.file instanceof File || img.file instanceof Blob) {
            blobUrl = URL.createObjectURL(img.file);
        } else if (img.blobUrl && img.blobUrl.startsWith('blob:')) {
            blobUrl = img.blobUrl;
        } else {

            return null;
        }
        return {
            ...img,
            blobUrl: blobUrl
        };
    }).filter(img => img !== null);
    
    if (imagenesConBlobUrl.length === 0) {

        window.__galeriaPrendaAbierta = false;
        return;
    }
    
    let indiceActual = indiceInicial;
    
    // Crear modal
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed; 
        top: 0; 
        left: 0; 
        width: 100%; 
        height: 100%; 
        background: rgba(0,0,0,0.95); 
        display: flex; 
        flex-direction: column;
        align-items: center; 
        justify-content: flex-start;
        z-index: 100001; 
        padding: 0;
        margin: 0;
        overflow: hidden;
    `;
    
    // Contenedor de imagen - ocupa todo el espacio disponible
    const imgContainer = document.createElement('div');
    imgContainer.style.cssText = `
        flex: 1;
        display: flex; 
        align-items: center; 
        justify-content: center; 
        position: relative; 
        width: 100%; 
        padding: 2rem 1rem;
        overflow: hidden;
    `;
    
    const imgModal = document.createElement('img');
    imgModal.src = imagenesConBlobUrl[indiceActual].blobUrl;
    imgModal.style.cssText = 'width: 95vw; height: 80vh; border-radius: 8px; object-fit: contain; box-shadow: 0 20px 50px rgba(0,0,0,0.7);';
    
    imgContainer.appendChild(imgModal);
    
    //  Crear contador ANTES de la función actualizarImagen
    const contador = document.createElement('span');
    contador.textContent = (indiceActual + 1) + ' de ' + imagenesConBlobUrl.length;
    contador.style.cssText = 'color: white; font-size: 1rem; font-weight: 500; min-width: 80px; text-align: center;';
    
    //  Función para actualizar la imagen mostrada (ahora contador existe)
    const actualizarImagen = (nuevoIndice) => {
        indiceActual = nuevoIndice;
        const newBlobUrl = imagenesConBlobUrl[indiceActual].blobUrl;
        imgModal.src = '';
        imgModal.src = newBlobUrl;
        contador.textContent = (indiceActual + 1) + ' de ' + imagenesConBlobUrl.length;

    };
    
    // Toolbar con botones - en la parte inferior
    const toolbar = document.createElement('div');
    toolbar.style.cssText = `
        display: flex; 
        justify-content: center; 
        align-items: center; 
        width: 100%; 
        gap: 1rem; 
        padding: 1.5rem; 
        background: rgba(0,0,0,0.8);
        flex-shrink: 0;
        position: relative;
        z-index: 10001;
    `;
    
    // Botón Anterior
    const btnAnterior = document.createElement('button');
    btnAnterior.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">arrow_back</span>';
    btnAnterior.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
    btnAnterior.onmouseover = () => btnAnterior.style.background = '#0052a3';
    btnAnterior.onmouseout = () => btnAnterior.style.background = '#0066cc';
    btnAnterior.onclick = () => {

        const nuevoIndice = (indiceActual - 1 + imagenesConBlobUrl.length) % imagenesConBlobUrl.length;
        actualizarImagen(nuevoIndice);
    };
    toolbar.appendChild(btnAnterior);
    
    // Botón Eliminar
    const btnEliminar = document.createElement('button');
    btnEliminar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">delete</span>';
    btnEliminar.style.cssText = 'background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
    btnEliminar.onmouseover = () => btnEliminar.style.background = '#dc2626';
    btnEliminar.onmouseout = () => btnEliminar.style.background = '#ef4444';
    
    let eliminarEnProceso = false;
    btnEliminar.onclick = () => {
        if (eliminarEnProceso) return;
        eliminarEnProceso = true;
        


        
        // Verificar si Swal está disponible
        if (!window.Swal) {

            eliminarEnProceso = false;
            
            if (confirm('¿Eliminar esta imagen? Esta acción no se puede deshacer.')) {
                procederConEliminacion();
            }
            return;
        }
        
        Swal.fire({
            title: '¿Eliminar imagen?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: (modal) => {
                // Establecer z-index más alto que la galería
                const swalContainer = document.querySelector('.swal2-container');
                if (swalContainer) {
                    swalContainer.style.zIndex = '100002';
                }
            }
        }).then((result) => {

            eliminarEnProceso = false;
            
            if (result.isConfirmed) {
                procederConEliminacion();
            }
        }).catch((error) => {

            eliminarEnProceso = false;
        });
    };
    
    //  Función extraída para manejar la eliminación
    const procederConEliminacion = () => {

        
        // Determinar dónde está la imagen para eliminarla correctamente
        let imagenEliminada = false;
        
        //  SI ESTAMOS EDITANDO: eliminar del modelo prenda
        if (estamoEditando && prenda && prenda.imagenes) {
            if (indiceActual < prenda.imagenes.length) {
                prenda.imagenes.splice(indiceActual, 1);

                imagenEliminada = true;
            }
        }
        
        //  SI ESTAMOS CREANDO: eliminar del storage temporal
        if (estamosCriando && window.imagenesPrendaStorage) {
            try {
                // El storage usa obtenerImagenes() para obtener las imágenes
                const imagenesTemporales = window.imagenesPrendaStorage.obtenerImagenes();

                
                if (imagenesTemporales && imagenesTemporales.length > 0) {
                    // Si el índice está dentro de las imágenes temporales, eliminarlo del storage
                    if (indiceActual < imagenesTemporales.length) {
                        window.imagenesPrendaStorage.eliminarImagen(indiceActual);

                        imagenEliminada = true;
                    }
                }
            } catch (error) {

            }
        }
        
        if (imagenEliminada) {
            // Actualizar array local
            imagenesConBlobUrl.splice(indiceActual, 1);

        } else {

        }
        
        // Verificar si quedan imágenes
        if (imagenesConBlobUrl.length === 0) {

            
            imgModal.src = '';
            imgContainer.innerHTML = `
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; height: 100%; gap: 2rem;">
                    <div style="font-size: 4rem; color: rgba(255,255,255,0.3);">📸</div>
                    <div style="text-align: center;">
                        <div style="color: white; font-size: 1.2rem; font-weight: 500;">Sin imágenes</div>
                        <div style="color: rgba(255,255,255,0.7); font-size: 0.9rem; margin-top: 0.5rem;">Todas las imágenes han sido eliminadas</div>
                    </div>
                </div>
            `;
            
            // Deshabilitar botones
            btnAnterior.disabled = true;
            btnAnterior.style.opacity = '0.5';
            btnAnterior.style.cursor = 'not-allowed';
            
            btnSiguiente.disabled = true;
            btnSiguiente.style.opacity = '0.5';
            btnSiguiente.style.cursor = 'not-allowed';
            
            btnEliminar.disabled = true;
            btnEliminar.style.opacity = '0.5';
            btnEliminar.style.cursor = 'not-allowed';
        } else {
            // Ajustar el índice si es necesario
            if (indiceActual >= imagenesConBlobUrl.length) {
                indiceActual = imagenesConBlobUrl.length - 1;
            }
            actualizarImagen(indiceActual);
        }
    };
    toolbar.appendChild(btnEliminar);
    
    // Botón Siguiente
    const btnSiguiente = document.createElement('button');
    btnSiguiente.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">arrow_forward</span>';
    btnSiguiente.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
    btnSiguiente.onmouseover = () => btnSiguiente.style.background = '#0052a3';
    btnSiguiente.onmouseout = () => btnSiguiente.style.background = '#0066cc';
    btnSiguiente.onclick = () => {

        const nuevoIndice = (indiceActual + 1) % imagenesConBlobUrl.length;
        actualizarImagen(nuevoIndice);
    };
    toolbar.appendChild(btnSiguiente);
    
    // Agregar contador a la toolbar (ya creado antes)
    toolbar.appendChild(contador);
    
    // Botón Cerrar
    const btnCerrar = document.createElement('button');
    btnCerrar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">close</span>';
    btnCerrar.style.cssText = 'background: #6c757d; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
    btnCerrar.onmouseover = () => btnCerrar.style.background = '#5a6268';
    btnCerrar.onmouseout = () => btnCerrar.style.background = '#6c757d';
    btnCerrar.onclick = () => {

        
        //  Sincronizar el preview principal después de cerrar galería
        if (window.actualizarPreviewPrenda) {

            window.actualizarPreviewPrenda();
        }
        
        modal.remove();
        window.__galeriaPrendaAbierta = false;
    };
    toolbar.appendChild(btnCerrar);
    
    // Ensamblar modal
    modal.appendChild(imgContainer);
    modal.appendChild(toolbar);
    document.body.appendChild(modal);
    

};


