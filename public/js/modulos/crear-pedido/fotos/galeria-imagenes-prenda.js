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
    console.log(' [galeria-imagenes-prenda] ABRIENDO GALERÍA...');
    console.log('   - imagenes recibidas:', imagenes?.length || 0);
    console.log('   - prendaIndex:', prendaIndex);
    console.log('   - indiceInicial:', indiceInicial);
    console.log(' [galeria-imagenes-prenda] Dimensiones de pantalla:', {
        vw: window.innerWidth,
        vh: window.innerHeight,
        '98vw': window.innerWidth * 0.98,
        '90vh': window.innerHeight * 0.90
    });
    
    //  Detectar si estamos creando o editando
    const estamosCriando = window.gestionItemsUI?.prendaEditIndex === null;
    const estamoEditando = window.gestionItemsUI?.prendaEditIndex !== null && window.gestionItemsUI?.prendaEditIndex !== undefined;

    console.log('   - estamosCriando:', estamosCriando);
    console.log('   - estamoEditando:', estamoEditando);

    //  DECLARAR VARIABLES PRIMERO
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
    
    //  AHORA QUE TENEMOS 'prenda', DETECTAR SI ES PRENDA DE COTIZACIÓN PARA PROTECCIÓN
    let esPrendaDeCotizacion = false;
    let cotizacionId = null;
    
    if (estamoEditando && prenda) {
        esPrendaDeCotizacion = !!(prenda.cotizacion_id || prenda.tipo === 'cotizacion');
        cotizacionId = prenda.cotizacion_id || null;
    }
    
    console.log('   - esPrendaDeCotizacion:', esPrendaDeCotizacion);
    console.log('   - cotizacionId:', cotizacionId);
    
    //  Si estamos EDITANDO, usar SOLAMENTE this.prendas[prendaIndex].imagenes
    if (estamoEditando && prenda) {
        imagenesActuales = prenda.imagenes || [];
        console.log('   ✓ Modo edición: usando solo this.prendas[prendaIndex].imagenes');
    }
    
    //  Si estamos CREANDO, sincronizar con imágenes temporales del storage
    if (estamosCriando && window.imagenesPrendaStorage && window.imagenesPrendaStorage.obtenerImagenes) {
        const imagenesTemporales = window.imagenesPrendaStorage.obtenerImagenes();
        
        if (imagenesTemporales && imagenesTemporales.length > 0) {
            imagenesActuales = imagenesTemporales;
            console.log('   ✓ Modo creación: usando imágenes temporales del storage');
        }
    }
    
    //  Si estamos CREANDO y no hay storage, usar las que pasaron como parámetro
    if (estamosCriando && imagenesActuales.length === 0 && imagenes && imagenes.length > 0) {
        imagenesActuales = imagenes;
        console.log('   ✓ Usando imágenes del parámetro (creando)');
    }
    
    console.log('   - imagenesActuales finales:', imagenesActuales?.length || 0);
    
    if (!imagenesActuales || imagenesActuales.length === 0) {
        console.warn(' [galeria-imagenes-prenda] No hay imágenes para mostrar');
        window.__galeriaPrendaAbierta = false;
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
        if (img instanceof File) {
            // CASO 0: El elemento es directamente un File
            blobUrl = URL.createObjectURL(img);
        } else if (img.file instanceof File || img.file instanceof Blob) {
            // CASO 1: Imagen nueva (tiene File/Blob)
            blobUrl = URL.createObjectURL(img.file);
        } else if (img.blobUrl && img.blobUrl.startsWith('blob:')) {
            // CASO 2: Ya tiene blob URL
            blobUrl = img.blobUrl;
        } else if (img.previewUrl) {
            // CASO 3: Imagen de BD con previewUrl
            blobUrl = img.previewUrl;
        } else if (img.url) {
            // CASO 4: Imagen de BD con url
            blobUrl = img.url;
        } else if (img.ruta) {
            // CASO 5: Imagen de BD con ruta
            blobUrl = img.ruta;
        } else {
            console.warn('[galeria-imagenes-prenda]  Imagen sin URL válida:', img);
            return null;
        }
        return {
            ...img,
            blobUrl: blobUrl
        };
    }).filter(img => img !== null);
    
    if (imagenesConBlobUrl.length === 0) {
        console.warn('[galeria-imagenes-prenda]  No se encontraron imágenes válidas para mostrar');
        window.__galeriaPrendaAbierta = false;
        return;
    }
    
    console.log(' [galeria-imagenes-prenda] Creando modal con', imagenesConBlobUrl.length, 'imágenes');
    
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
    imgModal.style.cssText = 'width: 98vw; height: 90vh; max-width: 98vw; max-height: 90vh; border-radius: 8px; object-fit: contain; box-shadow: 0 20px 50px rgba(0,0,0,0.7);';
    
    console.log(' [galeria-imagenes-prenda] CSS aplicado a imgModal:', imgModal.style.cssText);
    console.log(' [galeria-imagenes-prenda] tamano calculado:', {
        'width': '98vw = ' + (window.innerWidth * 0.98) + 'px',
        'height': '90vh = ' + (window.innerHeight * 0.90) + 'px',
        'max-width': '98vw = ' + (window.innerWidth * 0.98) + 'px',
        'max-height': '90vh = ' + (window.innerHeight * 0.90) + 'px'
    });
    console.log(' [galeria-imagenes-prenda] Image src:', imgModal.src);
    
    imgContainer.appendChild(imgModal);
    
    // Agregar evento load para verificar dimensiones reales
    imgModal.onload = function() {
        console.log(' [galeria-imagenes-prenda] Imagen cargada - Dimensiones reales:', {
            naturalWidth: this.naturalWidth,
            naturalHeight: this.naturalHeight,
            displayWidth: this.offsetWidth,
            displayHeight: this.offsetHeight,
            computedStyle: window.getComputedStyle(this).width,
            computedHeight: window.getComputedStyle(this).height
        });
    };
    
    imgModal.onerror = function() {
        // Solo reportar error si hay una URL real (no vacía o la URL de la página)
        if (this.src && !this.src.includes('/crear-nuevo') && !this.src.endsWith('/')) {
            console.error(' [galeria-imagenes-prenda] Error al cargar imagen:', this.src);
        }
    };
    
    //  Crear contador ANTES de la función actualizarImagen
    const contador = document.createElement('span');
    contador.textContent = (indiceActual + 1) + ' de ' + imagenesConBlobUrl.length;
    contador.style.cssText = 'color: white; font-size: 1rem; font-weight: 500; min-width: 80px; text-align: center;';
    
    //  Función para actualizar la imagen mostrada (ahora contador existe)
    const actualizarImagen = (nuevoIndice) => {
        indiceActual = nuevoIndice;
        const newBlobUrl = imagenesConBlobUrl[indiceActual].blobUrl;
        imgModal.style.display = 'block'; // Asegurar que la imagen sea visible
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
    
    //  CAMBIAR COLOR Y AGREGAR ADVERTENCIA SI ES PRENDA DE COTIZACIÓN
    if (esPrendaDeCotizacion) {
        btnEliminar.style.background = '#f59e0b'; // Ámbar en lugar de rojo
        btnEliminar.title = 'Esta prenda viene de una cotización. La eliminación solo afectará este pedido.';
    }
    
    let eliminarEnProceso = false;
    btnEliminar.onclick = () => {
        if (eliminarEnProceso) return;
        eliminarEnProceso = true;
        


        
        // Verificar si Swal está disponible
        if (!window.Swal) {
            eliminarEnProceso = false;
            
            //  MENSAJE PERSONALIZADO PARA COTIZACIÓN
            const mensajeConfirmacion = esPrendaDeCotizacion 
                ? '¿Eliminar esta imagen del pedido?\n\nEsta prenda viene de una cotización. La eliminación solo afectará este pedido, la cotización original permanecerá intacta.'
                : '¿Eliminar esta imagen? Esta acción no se puede deshacer.';
                
            if (confirm(mensajeConfirmacion)) {
                procederConEliminacion();
            }
            return;
        }
        
        //  CONFIGURAR MENSAJE SWAL SEGÚN TIPO DE PRENDA
        const swalConfig = {
            title: esPrendaDeCotizacion ? '¿Eliminar imagen del pedido?' : '¿Eliminar imagen?',
            html: esPrendaDeCotizacion 
                ? 'Esta prenda viene de una cotización.<br><br><strong>La eliminación solo afectará este pedido.</strong><br>La cotización original permanecerá intacta.'
                : 'Esta acción no se puede deshacer',
            icon: esPrendaDeCotizacion ? 'warning' : 'warning',
            showCancelButton: true,
            confirmButtonColor: esPrendaDeCotizacion ? '#f59e0b' : '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: esPrendaDeCotizacion ? 'Sí, eliminar del pedido' : 'Sí, eliminar',
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
        };

        Swal.fire(swalConfig).then((result) => {
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

        console.log(' [galeria] Procediendo con eliminación - esPrendaDeCotizacion:', esPrendaDeCotizacion);
        
        // Determinar dónde está la imagen para eliminarla correctamente
        let imagenEliminada = false;
        
        //  SI ESTAMOS EDITANDO: eliminar del modelo prenda Y del storage
        if (estamoEditando && prenda && prenda.imagenes) {
            if (indiceActual < prenda.imagenes.length) {
                //  MARCAR LA IMAGEN COMO ELIMINADA SOLO EN EL PEDIDO (no en cotización)
                const imagenEliminadaDatos = prenda.imagenes[indiceActual];
                
                //  SI ES PRENDA DE COTIZACIÓN: marcar para eliminación posterior al guardar
                if (esPrendaDeCotizacion) {
                    // Agregar a lista de imágenes a eliminar del pedido (no de la cotización)
                    if (!prenda.imagenesEliminadasDelPedido) {
                        prenda.imagenesEliminadasDelPedido = [];
                    }
                    prenda.imagenesEliminadasDelPedido.push({
                        ...imagenEliminadaDatos,
                        indiceOriginal: indiceActual,
                        timestamp: Date.now()
                    });
                    
                    console.log('🔒 [galeria] Imagen marcada para eliminación del pedido (cotización protegida):', imagenEliminadaDatos);
                } else {
                    // Prenda normal: eliminar directamente
                    //  IMPORTANTE: marcar para eliminación diferida al guardar
                    // (el backend elimina desde imagenes_a_eliminar)
                    const imagenId = imagenEliminadaDatos?.id || null;
                    if (imagenId) {
                        if (!window.imagenesAEliminar) {
                            window.imagenesAEliminar = [];
                        }

                        const payload = {
                            id: imagenId,
                            ruta_original: imagenEliminadaDatos?.ruta_original || imagenEliminadaDatos?.url || imagenEliminadaDatos?.ruta_webp || null,
                            ruta_webp: imagenEliminadaDatos?.ruta_webp || null,
                            url: imagenEliminadaDatos?.url || imagenEliminadaDatos?.ruta_webp || imagenEliminadaDatos?.ruta_original || null
                        };

                        window.imagenesAEliminar.push(payload);
                        console.log(' [galeria] Imagen marcada para eliminación al guardar (prenda):', payload);
                    } else {
                        console.warn(' [galeria] Imagen eliminada sin id (no se puede eliminar en BD al guardar):', imagenEliminadaDatos);
                    }

                    prenda.imagenes.splice(indiceActual, 1);
                    console.log(' [galeria] Imagen eliminada directamente (prenda normal):', imagenEliminadaDatos);
                }
                
                imagenEliminada = true;
                
                //  SINCRONIZAR CON STORAGE REAL también en edición
                if (window.imagenesPrendaStorage && window.imagenesPrendaStorage.obtenerImagenes) {
                    try {
                        const imagenesTemporales = window.imagenesPrendaStorage.obtenerImagenes();
                        if (imagenesTemporales && imagenesTemporales.length > 0 && indiceActual < imagenesTemporales.length) {
                            //  SI ES COTIZACIÓN: no eliminar del storage temporal, solo marcar
                            if (!esPrendaDeCotizacion) {
                                window.imagenesPrendaStorage.eliminarImagen(indiceActual);
                                console.log(' [galeria] Eliminada imagen del storage en modo edición');
                            } else {
                                console.log('🔒 [galeria] Protección: NO eliminada del storage (es cotización)');
                            }
                        }
                    } catch (error) {
                        console.warn(' [galeria] Error eliminando del storage en modo edición:', error);
                    }
                }
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
            //  ACTUALIZAR ARRAY LOCAL SEGÚN TIPO DE PRENDA
            if (esPrendaDeCotizacion) {
                // Para cotizaciones: ocultar visualmente pero mantener en array hasta guardar
                imagenesConBlobUrl[indiceActual].eliminadaVisualmente = true;
                imagenesConBlobUrl[indiceActual].motivoEliminacion = 'Eliminada del pedido (cotización protegida)';
                console.log('🔒 [galeria] Imagen oculta visualmente (cotización protegida)');
            } else {
                // Para prendas normales: eliminar del array local
                imagenesConBlobUrl.splice(indiceActual, 1);
                console.log(' [galeria] Imagen eliminada del array local');
            }

        } else {

        }
        
        // Verificar si quedan imágenes
        if (imagenesConBlobUrl.length === 0) {

            
            // Ocultar la imagen en lugar de establecer src vacío
            imgModal.style.display = 'none';
            imgModal.src = 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs='; // imagen transparente 1x1
            imgContainer.innerHTML = `
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; height: 100%; gap: 2rem;">
                    <div style="font-size: 4rem; color: rgba(255,255,255,0.3);"></div>
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
    
    //  AGREGAR INDICADOR DE PROTECCIÓN SI ES PRENDA DE COTIZACIÓN
    if (esPrendaDeCotizacion) {
        const indicadorProteccion = document.createElement('div');
        indicadorProteccion.style.cssText = `
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(245, 158, 11, 0.9);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 600;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        `;
        indicadorProteccion.innerHTML = `
            <span class="material-symbols-rounded" style="font-size: 1rem;">shield</span>
            Prenda de Cotización Protegida
        `;
        modal.appendChild(indicadorProteccion);
    }
    
    document.body.appendChild(modal);
    

};


