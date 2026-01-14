/**
 * IMAGENES - Galerías de imágenes en Prenda Sin Cotización
 * 
 * Funciones para:
 * - Mostrar galerías de imágenes
 * - Abrir/Cerrar galerías
 * - Eliminar imágenes
 */

// ✅ Forzar z-index alto para SweetAlert2
const styleSheet = document.createElement('style');
styleSheet.textContent = `
    .swal2-container {
        z-index: 11000 !important;
    }
    .swal2-backdrop {
        z-index: 10999 !important;
    }
`;
if (!document.querySelector('style[data-swal-z-index]')) {
    styleSheet.setAttribute('data-swal-z-index', 'true');
    document.head.appendChild(styleSheet);
}

/**
 * Mostrar galería de imágenes de prenda (modal)
 * @param {Array} imagenes - Array de imágenes con propiedades file y previewUrl
 * @param {number} indiceInicial - Índice inicial a mostrar
 */
window.mostrarGaleriaImagenesPrenda = function(imagenes, indiceInicial = 0) {
    // ✅ SIEMPRE obtener imágenes del storage (fuente de verdad)
    // ignorar el parámetro que puede estar desincronizado
    const imagenesDelStorage = window.imagenesPrendaStorage?.obtenerImagenes() || [];
    const imagenesActuales = imagenesDelStorage.length > 0 ? imagenesDelStorage : imagenes;
    
    if (!imagenesActuales || imagenesActuales.length === 0) {
        console.error('❌ [GALERÍA PRENDA] No hay imágenes para mostrar');
        return;
    }
    
    // ✅ Evitar que se reabra la galería mientras está en uso
    if (window.__galeriaImagenesPrendaAbierta) {
        console.warn('⚠️ [GALERÍA PRENDA] Galería ya está abierta, ignorando');
        return;
    }
    window.__galeriaImagenesPrendaAbierta = true;
    
    console.log('🖼️ [GALERÍA PRENDA] Abriendo galería:', imagenesActuales.length, 'imágenes');
    
    // Crear nuevos blob URLs para evitar que se revoquen
    const imagenesConBlobUrl = imagenesActuales.map((img, idx) => {
        let blobUrl;
        if (img.file instanceof File || img.file instanceof Blob) {
            blobUrl = URL.createObjectURL(img.file);
            console.log(`📸 [GALERÍA PRENDA] Creada blob URL para imagen ${idx}`);
        } else if (img.previewUrl && img.previewUrl.startsWith('blob:')) {
            blobUrl = img.previewUrl;
            console.log(`📸 [GALERÍA PRENDA] Usando blob URL existente para imagen ${idx}`);
        } else {
            console.error(`❌ [GALERÍA PRENDA] Imagen ${idx} sin File o blob URL válido`);
            return null;
        }
        return {
            ...img,
            previewUrl: blobUrl,
            blobUrl: blobUrl
        };
    }).filter(img => img !== null);
    
    if (imagenesConBlobUrl.length === 0) {
        console.error('❌ [GALERÍA PRENDA] No se pudieron crear blob URLs válidos');
        window.__galeriaImagenesPrendaAbierta = false;
        return;
    }
    
    let indiceActual = indiceInicial;
    
    const modal = document.createElement('div');
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.95); display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 10000; padding: 0;';
    
    const container = document.createElement('div');
    container.style.cssText = 'position: relative; display: flex; flex-direction: column; align-items: center; width: 100%; height: 100%; max-width: 100%; max-height: 100%;';
    
    const imgContainer = document.createElement('div');
    imgContainer.style.cssText = 'flex: 1; display: flex; align-items: center; justify-content: center; position: relative; width: 100%; height: calc(100% - 120px); padding: 1rem;';
    
    const imgModal = document.createElement('img');
    imgModal.src = imagenesConBlobUrl[indiceActual].previewUrl;
    imgModal.style.cssText = 'width: 90vw; height: 85vh; border-radius: 8px; object-fit: contain; box-shadow: 0 20px 50px rgba(0,0,0,0.7);';
    
    imgContainer.appendChild(imgModal);
    
    // ✅ Función auxiliar para actualizar la imagen
    const actualizarImagen = (nuevoIndice) => {
        indiceActual = nuevoIndice;
        const newBlobUrl = imagenesConBlobUrl[indiceActual].previewUrl;
        imgModal.src = '';
        imgModal.src = newBlobUrl;
        contador.textContent = (indiceActual + 1) + ' de ' + imagenesConBlobUrl.length;
        console.log(`🔄 [GALERÍA PRENDA] Imagen actualizada a índice ${indiceActual}`);
    };
    
    // Toolbar
    const toolbar = document.createElement('div');
    toolbar.style.cssText = 'display: flex; justify-content: center; align-items: center; width: 100%; gap: 1rem; padding: 1.5rem; background: rgba(0,0,0,0.5);';
    
    const btnAnterior = document.createElement('button');
    btnAnterior.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">arrow_back</span>';
    btnAnterior.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
    btnAnterior.onmouseover = () => btnAnterior.style.background = '#0052a3';
    btnAnterior.onmouseout = () => btnAnterior.style.background = '#0066cc';
    btnAnterior.onclick = () => {
        console.log('⬅️ [GALERÍA PRENDA] Imagen anterior');
        const nuevoIndice = (indiceActual - 1 + imagenesConBlobUrl.length) % imagenesConBlobUrl.length;
        actualizarImagen(nuevoIndice);
    };
    toolbar.appendChild(btnAnterior);
    
    const btnEliminar = document.createElement('button');
    btnEliminar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">delete</span>';
    btnEliminar.style.cssText = 'background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
    btnEliminar.onmouseover = () => btnEliminar.style.background = '#dc2626';
    btnEliminar.onmouseout = () => btnEliminar.style.background = '#ef4444';
    
    let eliminarEnProceso = false;
    btnEliminar.onclick = () => {
        // ✅ Prevenir múltiples clics mientras se muestra el diálogo
        if (eliminarEnProceso) return;
        eliminarEnProceso = true;
        
        console.log('🗑️ [GALERÍA PRENDA] Eliminando imagen:', indiceActual);
        
        // ✅ NO ocultamos el modal - el SweetAlert se muestra encima con z-index propio
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
            allowEscapeKey: false
        }).then((result) => {
            eliminarEnProceso = false;
            
            if (result.isConfirmed) {
                // ✅ SOLO eliminar del storage (fuente de verdad)
                // No hacer splice en imagenes porque podría ser una referencia al storage
                // Usar solo el storage como fuente de verdad
                const storageImagenes = window.imagenesPrendaStorage?.obtenerImagenes();
                if (storageImagenes && indiceActual < storageImagenes.length) {
                    storageImagenes.splice(indiceActual, 1);
                    console.log('✅ [GALERÍA PRENDA] Imagen eliminada');
                    
                    // ✅ También actualizar el array local de blob URLs para la galería
                    imagenesConBlobUrl.splice(indiceActual, 1);
                } else {
                    console.error('❌ [GALERÍA PRENDA] No se pudo eliminar la imagen');
                }
                
                // ✅ SIEMPRE actualizar el preview para mantener sincronización
                window.actualizarPreviewPrenda?.();
                
                // ✅ Verificar el array del storage (fuente de verdad)
                if (!storageImagenes || storageImagenes.length === 0) {
                    console.log('📭 [GALERÍA PRENDA] Sin más imágenes, mostrando estado vacío');
                    
                    // Mostrar estado vacío en lugar de cerrar
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
                    
                    // Deshabilitar botones excepto cerrar
                    btnAnterior.disabled = true;
                    btnAnterior.style.opacity = '0.5';
                    btnAnterior.style.cursor = 'not-allowed';
                    
                    btnSiguiente.disabled = true;
                    btnSiguiente.style.opacity = '0.5';
                    btnSiguiente.style.cursor = 'not-allowed';
                    
                    btnEliminar.disabled = true;
                    btnEliminar.style.opacity = '0.5';
                    btnEliminar.style.cursor = 'not-allowed';
                    
                    contador.textContent = '0 de 0';
                    
                    Swal.close();
                    return;
                }
                
                if (indiceActual >= imagenesConBlobUrl.length) {
                    indiceActual = imagenesConBlobUrl.length - 1;
                }
                
                console.log(`✅ [GALERÍA PRENDA] Imagen eliminada, mostrando índice ${indiceActual}`);
                actualizarImagen(indiceActual);
                Swal.close();
            } else {
                // ✅ Si cancela, no hace nada (el modal ya está visible)
                Swal.close();
            }
        });
    };
    toolbar.appendChild(btnEliminar);
    
    const contador = document.createElement('div');
    contador.style.cssText = 'color: white; font-size: 0.95rem; font-weight: 500; min-width: 80px; text-align: center;';
    contador.textContent = (indiceActual + 1) + ' de ' + imagenesConBlobUrl.length;
    toolbar.appendChild(contador);
    
    const btnSiguiente = document.createElement('button');
    btnSiguiente.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">arrow_forward</span>';
    btnSiguiente.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
    btnSiguiente.onmouseover = () => btnSiguiente.style.background = '#0052a3';
    btnSiguiente.onmouseout = () => btnSiguiente.style.background = '#0066cc';
    btnSiguiente.onclick = () => {
        console.log('➡️ [GALERÍA PRENDA] Imagen siguiente');
        const nuevoIndice = (indiceActual + 1) % imagenesConBlobUrl.length;
        actualizarImagen(nuevoIndice);
    };
    toolbar.appendChild(btnSiguiente);
    
    const btnCerrar = document.createElement('button');
    btnCerrar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">close</span>';
    btnCerrar.style.cssText = 'background: rgba(255,255,255,0.2); color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
    btnCerrar.onmouseover = () => btnCerrar.style.background = 'rgba(255,255,255,0.3)';
    btnCerrar.onmouseout = () => btnCerrar.style.background = 'rgba(255,255,255,0.2)';
    
    let cerrando = false;
    btnCerrar.onclick = () => {
        if (cerrando) return;
        cerrando = true;
        console.log('❌ [GALERÍA PRENDA] Cerrando');
        cerrarGaleria();
    };
    toolbar.appendChild(btnCerrar);
    
    // Cerrar con ESC
    const handleEsc = (e) => {
        if (e.key === 'Escape') {
            console.log('⌨️ [GALERÍA PRENDA] ESC presionado');
            cerrarGaleria();
        }
    };
    document.addEventListener('keydown', handleEsc);
    
    // Cerrar al clickear afuera
    modal.onclick = (e) => {
        if (e.target === modal) {
            console.log('🖱️ [GALERÍA PRENDA] Click fuera');
            cerrarGaleria();
        }
    };
    
    container.appendChild(imgContainer);
    container.appendChild(toolbar);
    modal.appendChild(container);
    document.body.appendChild(modal);
    
    // ✅ Función para cerrar la galería y limpiar flags
    const cerrarGaleria = () => {
        document.removeEventListener('keydown', handleEsc);
        modal.remove();
        window.__galeriaImagenesPrendaAbierta = false;
    };
    
    console.log('✅ [GALERÍA PRENDA] Galería abierta');
};

/**
 * Abre galería de fotos de prenda con navegación y controles
 * @param {number} index - Índice de la prenda
 */
window.abrirGaleriaPrendaTipo = function(index) {
    const prenda = window.gestorPrendaSinCotizacion.obtenerPorIndice(index);
    if (!prenda) return;

    const fotosNuevas = window.gestorPrendaSinCotizacion.obtenerFotosNuevas(index) || [];
    const fotos = [...(prenda.fotos || []), ...fotosNuevas];
    
    if (fotos.length === 0) {
        Swal.fire({ icon: 'info', title: 'Sin fotos', text: 'Esta prenda no tiene imágenes para mostrar.' });
        return;
    }

    // Convertir fotos a URLs
    const galeriaUrls = fotos.map(foto => {
        return typeof foto === 'string' ? foto : (foto.url || foto.ruta_webp || foto.ruta_original || '');
    }).filter(url => url);

    if (galeriaUrls.length === 0) {
        Swal.fire({ icon: 'info', title: 'Sin fotos', text: 'Esta prenda no tiene imágenes para mostrar.' });
        return;
    }

    let idx = 0;
    const fotosExistentes = prenda.fotos?.length || 0;

    const keyHandler = (e) => {
        if (!window.__galeriaPrendaTipoActiva) return;
        if (e.key === 'ArrowLeft') {
            e.preventDefault();
            document.getElementById('gal-prenda-tipo-prev')?.click();
        } else if (e.key === 'ArrowRight') {
            e.preventDefault();
            document.getElementById('gal-prenda-tipo-next')?.click();
        }
    };

    const renderModal = () => {
        const url = galeriaUrls[idx];
        const contenido = `
            <div style="display:flex; flex-direction:column; align-items:center; gap:1rem;">
                <div style="position:relative; width:100%; max-width:620px;">
                    <img src="${url}" alt="Foto prenda" style="width:100%; border-radius:8px; border:1px solid #e5e7eb; object-fit:contain; max-height:70vh;">
                    <button id="gal-prenda-tipo-prev" style="position:absolute; top:50%; left:-16px; transform:translateY(-50%); background:#111827cc; color:white; border:none; border-radius:50%; width:38px; height:38px; cursor:pointer; font-size:1.1rem; display:flex; align-items:center; justify-content:center;">‹</button>
                    <button id="gal-prenda-tipo-next" style="position:absolute; top:50%; right:-16px; transform:translateY(-50%); background:#111827cc; color:white; border:none; border-radius:50%; width:38px; height:38px; cursor:pointer; font-size:1.1rem; display:flex; align-items:center; justify-content:center;">›</button>
                    <button id="gal-prenda-tipo-del" style="position:absolute; top:6px; right:6px; background:#dc3545; color:white; border:none; border-radius:50%; width:38px; height:38px; cursor:pointer; font-size:1.1rem; display:flex; align-items:center; justify-content:center;">×</button>
                </div>
                <div style="font-size:0.9rem; color:#4b5563;">${idx + 1} / ${galeriaUrls.length}</div>
            </div>
        `;

        Swal.fire({
            html: contenido,
            showConfirmButton: false,
            showCloseButton: true,
            width: '75%',
            didOpen: () => {
                window.__galeriaPrendaTipoActiva = true;
                const prev = document.getElementById('gal-prenda-tipo-prev');
                const next = document.getElementById('gal-prenda-tipo-next');
                const delBtn = document.getElementById('gal-prenda-tipo-del');

                prev.onclick = () => { idx = (idx - 1 + galeriaUrls.length) % galeriaUrls.length; renderModal(); };
                next.onclick = () => { idx = (idx + 1) % galeriaUrls.length; renderModal(); };
                delBtn.onclick = () => {
                    Swal.fire({
                        title: '¿Eliminar imagen?',
                        text: 'Esta acción no se puede deshacer',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            galeriaUrls.splice(idx, 1);
                            fotos.splice(idx, 1);

                            // Actualizar en el gestor
                            if (idx < fotosExistentes) {
                                prenda.fotos.splice(idx, 1);
                            } else {
                                const idxEnNuevas = idx - fotosExistentes;
                                fotosNuevas.splice(idxEnNuevas, 1);
                                window.gestorPrendaSinCotizacion.fotosNuevas[index] = fotosNuevas;
                            }

                            if (galeriaUrls.length === 0) {
                                Swal.fire('Eliminado', 'Última foto eliminada. Cerrando galería.', 'success');
                                window.__galeriaPrendaTipoActiva = false;
                                window.removeEventListener('keydown', keyHandler);
                                window.renderizarPrendasTipoPrendaSinCotizacion();
                            } else {
                                idx = Math.min(idx, galeriaUrls.length - 1);
                                renderModal();
                            }
                        }
                    });
                };

                window.addEventListener('keydown', keyHandler);
            },
            willClose: () => {
                window.__galeriaPrendaTipoActiva = false;
                window.removeEventListener('keydown', keyHandler);
            }
        });
    };

    renderModal();
};

/**
 * Abre galería de fotos de tela con navegación y controles
 * @param {number} index - Índice de la prenda
 * @param {number} telaIdx - Índice de la tela
 */
window.abrirGaleriaTexturaTipo = function(index, telaIdx) {
    const prenda = window.gestorPrendaSinCotizacion.obtenerPorIndice(index);
    if (!prenda || !prenda.telas || !prenda.telas[telaIdx]) return;

    const tela = prenda.telas[telaIdx];
    const fotosNuevas = window.gestorPrendaSinCotizacion.obtenerFotosNuevasTela(index, telaIdx) || [];
    const fotosTelaJSON = tela.telaFotos?.filter(f => f.tela_id === tela.id) || [];
    const fotos = [...fotosTelaJSON, ...fotosNuevas];

    if (fotos.length === 0) {
        Swal.fire({ icon: 'info', title: 'Sin fotos', text: 'Esta tela no tiene imágenes para mostrar.' });
        return;
    }

    // Convertir fotos a URLs
    const galeriaUrls = fotos.map(foto => {
        return typeof foto === 'string' ? foto : (foto.url || foto.ruta_webp || foto.ruta_original || '');
    }).filter(url => url);

    if (galeriaUrls.length === 0) {
        Swal.fire({ icon: 'info', title: 'Sin fotos', text: 'Esta tela no tiene imágenes para mostrar.' });
        return;
    }

    let idx = 0;

    const keyHandler = (e) => {
        if (!window.__galeriaTexturaTipoActiva) return;
        if (e.key === 'ArrowLeft') {
            e.preventDefault();
            document.getElementById('gal-textura-tipo-prev')?.click();
        } else if (e.key === 'ArrowRight') {
            e.preventDefault();
            document.getElementById('gal-textura-tipo-next')?.click();
        }
    };

    const renderModal = () => {
        const url = galeriaUrls[idx];
        const contenido = `
            <div style="display:flex; flex-direction:column; align-items:center; gap:1rem;">
                <div style="position:relative; width:100%; max-width:620px;">
                    <img src="${url}" alt="Foto tela" style="width:100%; border-radius:8px; border:1px solid #e5e7eb; object-fit:contain; max-height:70vh;">
                    <button id="gal-textura-tipo-prev" style="position:absolute; top:50%; left:-16px; transform:translateY(-50%); background:#111827cc; color:white; border:none; border-radius:50%; width:38px; height:38px; cursor:pointer; font-size:1.1rem; display:flex; align-items:center; justify-content:center;">‹</button>
                    <button id="gal-textura-tipo-next" style="position:absolute; top:50%; right:-16px; transform:translateY(-50%); background:#111827cc; color:white; border:none; border-radius:50%; width:38px; height:38px; cursor:pointer; font-size:1.1rem; display:flex; align-items:center; justify-content:center;">›</button>
                    <button id="gal-textura-tipo-del" style="position:absolute; top:6px; right:6px; background:#dc3545; color:white; border:none; border-radius:50%; width:38px; height:38px; cursor:pointer; font-size:1.1rem; display:flex; align-items:center; justify-content:center;">×</button>
                </div>
                <div style="font-size:0.9rem; color:#4b5563;">${idx + 1} / ${galeriaUrls.length}</div>
            </div>
        `;

        Swal.fire({
            html: contenido,
            showConfirmButton: false,
            showCloseButton: true,
            width: '75%',
            didOpen: () => {
                window.__galeriaTexturaTipoActiva = true;
                const prev = document.getElementById('gal-textura-tipo-prev');
                const next = document.getElementById('gal-textura-tipo-next');
                const delBtn = document.getElementById('gal-textura-tipo-del');

                prev.onclick = () => { idx = (idx - 1 + galeriaUrls.length) % galeriaUrls.length; renderModal(); };
                next.onclick = () => { idx = (idx + 1) % galeriaUrls.length; renderModal(); };
                delBtn.onclick = () => {
                    Swal.fire({
                        title: '¿Eliminar imagen?',
                        text: 'Esta acción no se puede deshacer',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            galeriaUrls.splice(idx, 1);
                            fotos.splice(idx, 1);

                            if (idx < fotosTelaJSON.length) {
                                logWithEmoji('🗑️', 'Foto de tela existente (requiere manejo especial)');
                            } else {
                                const idxEnNuevas = idx - fotosTelaJSON.length;
                                fotosNuevas.splice(idxEnNuevas, 1);
                                window.gestorPrendaSinCotizacion.telasFotosNuevas[index][telaIdx] = fotosNuevas;
                            }

                            if (galeriaUrls.length === 0) {
                                Swal.fire('Eliminado', 'Última foto eliminada. Cerrando galería.', 'success');
                                window.__galeriaTexturaTipoActiva = false;
                                window.removeEventListener('keydown', keyHandler);
                            } else {
                                idx = Math.min(idx, galeriaUrls.length - 1);
                                renderModal();
                            }
                        }
                    });
                };

                window.addEventListener('keydown', keyHandler);
            },
            willClose: () => {
                window.__galeriaTexturaTipoActiva = false;
                window.removeEventListener('keydown', keyHandler);
            }
        });
    };

    renderModal();
};

/**
 * Eliminar imagen de prenda
 * @param {HTMLElement} element - Elemento del botón
 * @param {number} prendaIndex - Índice de la prenda
 */
window.eliminarImagenPrendaTipo = function(element, prendaIndex) {
    Swal.fire({
        title: '¿Eliminar Imagen?',
        text: '¿Está seguro que desea eliminar esta imagen?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Sí, Eliminar'
    }).then((result) => {
        if (result.isConfirmed) {
            console.log(`🗑️ Eliminando imagen de prenda ${prendaIndex}`);
            
            // Obtener la URL de la imagen
            const imgContainer = element.closest('div[style*="position: relative"]');
            const img = imgContainer?.querySelector('img');
            const fotoUrl = img?.getAttribute('src');
            
            if (!fotoUrl) {
                console.error('No se pudo obtener la URL de la foto');
                return;
            }
            
            console.log(`🔍 URL de foto a eliminar: ${fotoUrl}`);
            
            // ✅ ELIMINAR DEL GESTOR (fotosNuevas)
            if (window.gestorPrendaSinCotizacion?.fotosNuevas?.[prendaIndex]) {
                const fotosNuevas = window.gestorPrendaSinCotizacion.fotosNuevas[prendaIndex];
                console.log(`📊 Fotos nuevas en gestor antes de eliminar:`, fotosNuevas);
                
                const idx = fotosNuevas.findIndex(f => {
                    const url = f.url || f.ruta_webp || f.ruta_original;
                    console.log(`   Comparando: "${url}" === "${fotoUrl}" ? ${url === fotoUrl}`);
                    return url === fotoUrl;
                });
                
                console.log(`📍 Índice encontrado: ${idx}`);
                if (idx >= 0) {
                    fotosNuevas.splice(idx, 1);
                    console.log(`✅ Foto eliminada de gestorPrendaSinCotizacion.fotosNuevas[${prendaIndex}]`);
                } else {
                    console.warn(`⚠️ No se encontró la foto en gestorPrendaSinCotizacion.fotosNuevas`);
                }
            } else {
                console.warn(`⚠️ No hay fotosNuevas en gestorPrendaSinCotizacion para prenda ${prendaIndex}`);
            }
            
            // ✅ TAMBIÉN ELIMINAR DE prenda.fotos si existe
            const prenda = window.gestorPrendaSinCotizacion?.obtenerPorIndice(prendaIndex);
            if (prenda && prenda.fotos && prenda.fotos.length > 0) {
                const idx = prenda.fotos.findIndex(f => {
                    const url = typeof f === 'string' ? f : (f.url || f.ruta_webp || f.ruta_original);
                    return url === fotoUrl;
                });
                if (idx >= 0) {
                    prenda.fotos.splice(idx, 1);
                    console.log(`✅ Foto eliminada de prenda.fotos`);
                }
            }
            
            // ✅ ELIMINAR DE PedidoState
            if (window.PedidoState) {
                const fotos = window.PedidoState.getFotosPrenda(prendaIndex) || [];
                const idxState = fotos.findIndex(f => {
                    const url = f.url || f.preview || f.ruta_webp || f.ruta_original;
                    return url === fotoUrl;
                });
                if (idxState >= 0) {
                    fotos.splice(idxState, 1);
                    window.PedidoState.setFotosPrenda(prendaIndex, fotos);
                    console.log(`✅ Foto eliminada de PedidoState prenda ${prendaIndex}`);
                }
            }
            
            // ✅ ELIMINAR DE prendasFotosNuevas
            if (window.prendasFotosNuevas?.[prendaIndex]) {
                const idx = window.prendasFotosNuevas[prendaIndex].findIndex(f => {
                    const url = f.url || f.preview || f.ruta_webp || f.ruta_original;
                    return url === fotoUrl;
                });
                if (idx >= 0) {
                    window.prendasFotosNuevas[prendaIndex].splice(idx, 1);
                    console.log(`✅ Foto eliminada de prendasFotosNuevas[${prendaIndex}]`);
                }
            }
            
            // Marcar como eliminada
            if (!window.fotosEliminadas) window.fotosEliminadas = new Set();
            window.fotosEliminadas.add(fotoUrl);
            
            // Re-renderizar
            window.renderizarPrendasTipoPrendaSinCotizacion();
            
            Swal.fire({
                icon: 'success',
                title: 'Eliminada',
                text: 'La imagen ha sido eliminada',
                timer: 1500,
                showConfirmButton: false
            });
        }
    });
};

console.log('✅ [IMAGENES] Componente prenda-sin-cotizacion-imagenes.js cargado');
