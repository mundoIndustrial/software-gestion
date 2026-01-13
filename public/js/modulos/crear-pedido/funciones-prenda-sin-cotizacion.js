/**
 * FUNCIONES GLOBALES - Prenda Sin Cotización Tipo PRENDA
 * 
 * Define todas las funciones globales necesarias para la interacción
 * del usuario con prendas de tipo PRENDA sin cotización:
 * - Agregar/Eliminar prendas
 * - Agregar/Eliminar tallas
 * - Agregar/Eliminar telas
 * - Agregar/Eliminar fotos
 * - Manejar variaciones
 */

/**
 * Inicializar el gestor de prenda sin cotización tipo PRENDA
 */
window.inicializarGestorPrendaSinCotizacion = function() {
    if (!window.gestorPrendaSinCotizacion) {
        window.gestorPrendaSinCotizacion = new GestorPrendaSinCotizacion();
        logWithEmoji('✅', 'GestorPrendaSinCotizacion inicializado');
    }
};

/**
 * Crear pedido tipo PRENDA sin cotización
 */
window.crearPedidoTipoPrendaSinCotizacion = function() {
    console.log('🎯 Iniciando creación de pedido PRENDA sin cotización');

    // Inicializar gestor si no existe
    if (!window.gestorPrendaSinCotizacion) {
        window.inicializarGestorPrendaSinCotizacion();
    }

    // Agregar primera prenda
    window.gestorPrendaSinCotizacion.agregarPrenda();

    // Renderizar UI
    window.renderizarPrendasTipoPrendaSinCotizacion();

    // Mostrar secciones pertinentes
    document.getElementById('seccion-info-prenda')?.style.setProperty('display', 'block', 'important');
    document.getElementById('seccion-prendas')?.style.setProperty('display', 'block', 'important');

    // Scroll
    document.getElementById('seccion-info-prenda')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

/**
 * Agregar una nueva prenda tipo PRENDA
 */
window.agregarPrendaTipoPrendaSinCotizacion = function() {
    // Solo permitir una prenda en el tipo de pedido PRENDA sin cotización
    if (window.gestorPrendaSinCotizacion) {
        const prendas = window.gestorPrendaSinCotizacion.obtenerActivas();
        if (prendas.length >= 1) {
            console.warn('⚠️ Solo se permite una prenda en el tipo de pedido PRENDA sin cotización');
            return;
        }
    }
    
    if (!window.gestorPrendaSinCotizacion) {
        window.inicializarGestorPrendaSinCotizacion();
    }

    window.gestorPrendaSinCotizacion.agregarPrenda();
    window.renderizarPrendasTipoPrendaSinCotizacion();
};

/**
 * Eliminar una prenda tipo PRENDA
 * @param {number} index - Índice de la prenda
 */
window.eliminarPrendaTipoPrenda = function(index) {
    Swal.fire({
        title: '¿Eliminar Prenda?',
        text: `¿Está seguro que desea eliminar la prenda ${index + 1}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, Eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.gestorPrendaSinCotizacion.eliminar(index);
            window.renderizarPrendasTipoPrendaSinCotizacion();
            Swal.fire('Eliminada', 'La prenda ha sido eliminada', 'success');
        }
    });
};

/**
 * Agregar una talla a una prenda
 * @param {number} prendaIndex - Índice de la prenda
 */
window.agregarTallaPrendaTipo = function(prendaIndex) {
    Swal.fire({
        title: 'Seleccionar Talla',
        html: `
            <select id="select-talla" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem;">
                <option value="">-- Seleccionar Talla --</option>
                <option value="XS">XS</option>
                <option value="S">S</option>
                <option value="M">M</option>
                <option value="L">L</option>
                <option value="XL">XL</option>
                <option value="XXL">XXL</option>
                <option value="XXXL">XXXL</option>
                <option value="2">2</option>
                <option value="4">4</option>
                <option value="6">6</option>
                <option value="8">8</option>
                <option value="10">10</option>
                <option value="12">12</option>
                <option value="14">14</option>
                <option value="16">16</option>
            </select>
        `,
        showCancelButton: true,
        confirmButtonText: 'Agregar',
        cancelButtonText: 'Cancelar',
        didOpen: (modal) => {
            document.getElementById('select-talla').focus();
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const talla = document.getElementById('select-talla').value;
            if (talla) {
                window.gestorPrendaSinCotizacion.agregarTalla(prendaIndex, talla);
                window.renderizarPrendasTipoPrendaSinCotizacion();
                Swal.fire('Éxito', `Talla ${talla} agregada`, 'success');
            } else {
                Swal.fire('Error', 'Seleccione una talla', 'error');
            }
        }
    });
};

/**
 * Eliminar una talla de una prenda
 * @param {number} prendaIndex - Índice de la prenda
 * @param {string} talla - Talla a eliminar
 */
window.eliminarTallaPrendaTipo = function(prendaIndex, talla) {
    Swal.fire({
        title: '¿Eliminar Talla?',
        text: `¿Está seguro que desea eliminar la talla ${talla}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, Eliminar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.gestorPrendaSinCotizacion.eliminarTalla(prendaIndex, talla);
            window.renderizarPrendasTipoPrendaSinCotizacion();
            Swal.fire('Eliminada', `La talla ${talla} ha sido eliminada`, 'success');
        }
    });
};

/**
 * Agregar una tela a una prenda
 * @param {number} prendaIndex - Índice de la prenda
 */
window.agregarTelaPrendaTipo = function(prendaIndex) {
    let selectedFiles = [];
    
    Swal.fire({
        title: 'Agregar Tela',
        html: `
            <form>
                <div style="margin-bottom: 1rem; text-align: left;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Nombre de la Tela:</label>
                    <input id="nombre-tela" type="text" placeholder="Ej: Algodón, Poliéster" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                <div style="margin-bottom: 1rem; text-align: left;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Color:</label>
                    <input id="color-tela" type="text" placeholder="Ej: Rojo, Azul" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                <div style="margin-bottom: 1rem; text-align: left;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Referencia:</label>
                    <input id="referencia-tela" type="text" placeholder="Ej: REF-001" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                <div style="margin-bottom: 1rem; text-align: left;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                        Imágenes (opcional):
                        <span style="font-weight: normal; color: #6b7280; font-size: 0.85rem;">Agrega una o varias imágenes</span>
                    </label>
                    <button type="button" id="btn-agregar-imagenes" style="width: 100%; padding: 0.75rem; background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                        <span>📷</span>
                        <span>Agregar Imágenes</span>
                    </button>
                    <input id="imagenes-tela" type="file" accept="image/*" multiple style="display: none;">
                    <div id="contador-imagenes" style="margin-top: 0.5rem; font-size: 0.85rem; color: #0066cc; font-weight: 600;"></div>
                    <div id="preview-imagenes" style="margin-top: 0.5rem; display: flex; gap: 0.5rem; flex-wrap: wrap;"></div>
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Agregar',
        cancelButtonText: 'Cancelar',
        didOpen: (modal) => {
            document.getElementById('nombre-tela').focus();
            
            // Manejar selección de imágenes
            const inputImagenes = document.getElementById('imagenes-tela');
            const btnAgregarImagenes = document.getElementById('btn-agregar-imagenes');
            const previewContainer = document.getElementById('preview-imagenes');
            const contadorImagenes = document.getElementById('contador-imagenes');
            
            // Botón para abrir selector de archivos
            btnAgregarImagenes.addEventListener('click', () => {
                inputImagenes.click();
            });
            
            // Acumular imágenes seleccionadas
            inputImagenes.addEventListener('change', (e) => {
                const newFiles = Array.from(e.target.files);
                
                // Agregar nuevos archivos a los existentes (acumular)
                newFiles.forEach(file => {
                    // Verificar que no esté duplicado
                    const existe = selectedFiles.some(f => f.name === file.name && f.size === file.size);
                    if (!existe) {
                        selectedFiles.push(file);
                    }
                });
                
                // Actualizar contador
                if (selectedFiles.length > 0) {
                    contadorImagenes.textContent = `✓ ${selectedFiles.length} imagen${selectedFiles.length !== 1 ? 'es' : ''} seleccionada${selectedFiles.length !== 1 ? 's' : ''}`;
                } else {
                    contadorImagenes.textContent = '';
                }
                
                // Re-renderizar todas las previews
                previewContainer.innerHTML = '';
                selectedFiles.forEach((file, index) => {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const preview = document.createElement('div');
                        preview.style.cssText = 'position: relative; width: 60px; height: 60px;';
                        preview.innerHTML = `
                            <img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px; border: 2px solid #0066cc;">
                            <div style="position: absolute; top: -5px; right: -5px; background: #ef4444; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 12px; cursor: pointer;" data-index="${index}">×</div>
                        `;
                        
                        // Agregar evento para eliminar imagen individual
                        const btnEliminar = preview.querySelector('[data-index]');
                        btnEliminar.addEventListener('click', () => {
                            selectedFiles.splice(index, 1);
                            preview.remove();
                            // Actualizar contador
                            if (selectedFiles.length > 0) {
                                contadorImagenes.textContent = `✓ ${selectedFiles.length} imagen${selectedFiles.length !== 1 ? 'es' : ''} seleccionada${selectedFiles.length !== 1 ? 's' : ''}`;
                            } else {
                                contadorImagenes.textContent = '';
                            }
                        });
                        
                        previewContainer.appendChild(preview);
                    };
                    reader.readAsDataURL(file);
                });
                
                // Resetear input para permitir seleccionar el mismo archivo de nuevo
                inputImagenes.value = '';
            });
        }
    }).then(async (result) => {
        if (result.isConfirmed) {
            const nombreTela = document.getElementById('nombre-tela').value;
            const color = document.getElementById('color-tela').value;
            const referencia = document.getElementById('referencia-tela').value;

            if (nombreTela) {
                // Agregar la tela al gestor
                window.gestorPrendaSinCotizacion.agregarTela(prendaIndex, {
                    nombre_tela: nombreTela,
                    color: color,
                    referencia: referencia
                });
                
                // Obtener el índice de la tela recién agregada
                const prenda = window.gestorPrendaSinCotizacion.obtenerPorIndice(prendaIndex);
                const telaIndex = (prenda?.variantes?.telas_multiples?.length || 1) - 1;
                
                // Si hay imágenes seleccionadas, subirlas
                if (selectedFiles.length > 0) {
                    console.log(`📸 Subiendo ${selectedFiles.length} imagen(es) para la tela...`);
                    
                    try {
                        Swal.fire({
                            title: 'Subiendo imágenes...',
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading()
                        });
                        
                        // Subir cada imagen
                        for (const file of selectedFiles) {
                            if (file.type.startsWith('image/')) {
                                const imageData = await window.ImageService.uploadTelaImage(file, prendaIndex, telaIndex, null);
                                
                                // Guardar en el gestor
                                if (!window.gestorPrendaSinCotizacion.telasFotosNuevas) {
                                    window.gestorPrendaSinCotizacion.telasFotosNuevas = {};
                                }
                                if (!window.gestorPrendaSinCotizacion.telasFotosNuevas[prendaIndex]) {
                                    window.gestorPrendaSinCotizacion.telasFotosNuevas[prendaIndex] = {};
                                }
                                if (!window.gestorPrendaSinCotizacion.telasFotosNuevas[prendaIndex][telaIndex]) {
                                    window.gestorPrendaSinCotizacion.telasFotosNuevas[prendaIndex][telaIndex] = [];
                                }
                                
                                window.gestorPrendaSinCotizacion.telasFotosNuevas[prendaIndex][telaIndex].push({
                                    url: imageData.url,
                                    ruta_webp: imageData.ruta_webp,
                                    ruta_original: imageData.ruta_original,
                                    thumbnail: imageData.thumbnail,
                                    tela_id: imageData.tela_id,
                                    isNew: true
                                });
                                
                                console.log(`✅ Imagen subida: ${imageData.filename}`);
                            }
                        }
                        
                        Swal.close();
                    } catch (error) {
                        console.error('❌ Error al subir imágenes:', error);
                        Swal.fire('Advertencia', 'Tela agregada pero hubo un error al subir algunas imágenes', 'warning');
                    }
                }
                
                // Re-renderizar la sección de telas
                if (prenda && typeof window.renderizarTelasPrendaTipo === 'function') {
                    const container = document.querySelector(`[data-prenda-index="${prendaIndex}"]`);
                    if (container) {
                        const telasSection = container.querySelector('[data-section="telas"]');
                        if (telasSection) {
                            telasSection.innerHTML = window.renderizarTelasPrendaTipo(prenda, prendaIndex);
                            console.log('✅ Sección de telas actualizada con nueva tela y fotos');
                        }
                    }
                }
                
                const mensaje = selectedFiles.length > 0 
                    ? `Tela agregada con ${selectedFiles.length} imagen(es)` 
                    : 'Tela agregada correctamente';
                Swal.fire('Éxito', mensaje, 'success');
            } else {
                Swal.fire('Error', 'Ingrese el nombre de la tela', 'error');
            }
        }
    });
};

/**
 * Eliminar una tela de una prenda
 * @param {number} prendaIndex - Índice de la prenda
 * @param {number} telaIndex - Índice de la tela
 */
window.eliminarTelaPrendaTipo = function(prendaIndex, telaIndex) {
    Swal.fire({
        title: '¿Eliminar Tela?',
        text: '¿Está seguro que desea eliminar esta tela?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, Eliminar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.gestorPrendaSinCotizacion.eliminarTela(prendaIndex, telaIndex);
            window.renderizarPrendasTipoPrendaSinCotizacion();
            Swal.fire('Eliminada', 'La tela ha sido eliminada', 'success');
        }
    });
};

/**
 * Eliminar una variación
 * @param {number} prendaIndex - Índice de la prenda
 * @param {number} varIdx - Índice de la variación
 */
window.eliminarVariacionPrendaTipo = function(prendaIndex, varIdx) {
    Swal.fire({
        title: '¿Eliminar Variación?',
        text: '¿Está seguro?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Sí, Eliminar'
    }).then((result) => {
        if (result.isConfirmed) {
            // La variación se elimina en el renderizado posterior
            window.renderizarPrendasTipoPrendaSinCotizacion();
            Swal.fire('Eliminada', 'La variación ha sido eliminada', 'success');
        }
    });
};

/**
 * Abrir modal para agregar fotos a una prenda
 * @param {number} prendaIndex - Índice de la prenda
 */
window.abrirModalAgregarFotosPrendaTipo = function(prendaIndex) {
    // Usar el modal existente o crear uno
    if (typeof abrirModalAgregarFotosPrenda === 'function') {
        abrirModalAgregarFotosPrenda(prendaIndex);
    } else {
        Swal.fire('Info', 'Función de fotos aún no disponible', 'info');
    }
};

/**
 * Abrir modal para agregar fotos a una tela
 * @param {number} prendaIndex - Índice de la prenda
 * @param {number} telaIndex - Índice de la tela
 */
window.abrirModalAgregarFotosTelaType = function(prendaIndex, telaIndex) {
    if (typeof abrirModalAgregarFotosTela === 'function') {
        abrirModalAgregarFotosTela(prendaIndex, telaIndex);
    } else {
        Swal.fire('Info', 'Función de fotos de tela aún no disponible', 'info');
    }
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

/**
 * Eliminar imagen de tela
 * @param {HTMLElement} element - Elemento del botón
 * @param {number} prendaIndex - Índice de la prenda
 * @param {number} telaIndex - Índice de la tela
 */
window.eliminarImagenTelaTipo = function(element, prendaIndex, telaIndex) {
    Swal.fire({
        title: '¿Eliminar Imagen?',
        text: '¿Está seguro que desea eliminar esta imagen de tela?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Sí, Eliminar'
    }).then((result) => {
        if (result.isConfirmed) {
            console.log(`🗑️ Eliminando foto de tela - Prenda: ${prendaIndex}, Tela: ${telaIndex}`);
            
            const prenda = window.gestorPrendaSinCotizacion.obtenerPorIndice(prendaIndex);
            if (prenda) {
                const tela = prenda.telas && prenda.telas[telaIndex];
                
                // ✅ OPCIÓN 1: Limpiar telaFotos (fotos guardadas del servidor)
                if (tela && prenda.telaFotos && Array.isArray(prenda.telaFotos)) {
                    console.log(`   Antes: telaFotos.length = ${prenda.telaFotos.length}`);
                    prenda.telaFotos = [];  // Vaciar completamente
                    console.log(`   Después: telaFotos.length = ${prenda.telaFotos.length}`);
                }
                
                // ✅ OPCIÓN 2: Limpiar fotos nuevas de tela
                const keyFotosNuevas = `${prendaIndex}_${telaIndex}`;
                if (window.gestorPrendaSinCotizacion.telasFotosNuevas && 
                    window.gestorPrendaSinCotizacion.telasFotosNuevas[prendaIndex]) {
                    window.gestorPrendaSinCotizacion.telasFotosNuevas[prendaIndex][telaIndex] = [];
                    console.log(`   Fotos nuevas de tela limpiadas`);
                }
            }
            
            // ✅ Solo re-renderizar la sección de TELAS (no toda la prenda)
            const container = document.querySelector(`[data-prenda-index="${prendaIndex}"]`);
            if (container) {
                const telasSection = container.querySelector('[data-section="telas"]');
                if (telasSection) {
                    const telasHtml = window.renderizarTelasPrendaTipo(prenda, prendaIndex);
                    telasSection.innerHTML = telasHtml;
                    logWithEmoji('🗑️', `Foto de tela eliminada, sección actualizada`);
                }
            }
            
            Swal.fire('Eliminada', 'La imagen de tela ha sido eliminada', 'success');
        }
    });
};

/**
 * Manejar cambios en selects de variaciones
 * @param {HTMLElement} selectElement - Elemento select
 */
window.manejarCambioVariacionPrendaTipo = function(selectElement) {
    const campo = selectElement.dataset.field;
    const prendaIndex = parseInt(selectElement.dataset.prenda);
    const valor = selectElement.value;

    if (campo.includes('_obs')) {
        // Observaciones
        return;
    }

    const prenda = window.gestorPrendaSinCotizacion.obtenerPorIndice(prendaIndex);
    if (prenda) {
        if (campo.includes('tiene_')) {
            // Convertir Sí/No a booleano
            prenda.variantes[campo] = valor === 'Sí';
            prenda[campo] = valor === 'Sí';
        } else {
            prenda.variantes[campo] = valor;
            prenda[campo] = valor;
        }
    }
};

/**
 * Sincronizar datos de telas desde inputs
 * @param {number} prendaIndex - Índice de la prenda
 */
window.sincronizarDatosTelas = function(prendaIndex) {
    const prenda = window.gestorPrendaSinCotizacion.obtenerPorIndice(prendaIndex);
    if (!prenda) return;

    const container = document.querySelector(`[data-prenda-index="${prendaIndex}"]`);
    if (!container) return;

    // Sincronizar nombre, descripción, género
    const inputNombre = container.querySelector('.prenda-nombre');
    const inputDesc = container.querySelector('.prenda-descripcion');
    const selectGenero = container.querySelector('.prenda-genero');
    
    if (inputNombre?.value) prenda.nombre_producto = inputNombre.value;
    if (inputDesc?.value) prenda.descripcion = inputDesc.value;
    if (selectGenero?.value) prenda.genero = selectGenero.value;
    
    // Sincronizar cantidades de tallas
    container.querySelectorAll('.talla-cantidad').forEach(input => {
        const talla = input.dataset.talla;
        const cantidad = parseInt(input.value) || 0;
        if (prenda.cantidadesPorTalla) {
            prenda.cantidadesPorTalla[talla] = cantidad;
        }
    });

    // Sincronizar datos de telas
    const telaRows = container.querySelectorAll('[data-tela-index]');
    telaRows.forEach(row => {
        const telaIdx = parseInt(row.dataset.telaIndex);
        const nombreInput = row.querySelector('.tela-nombre');
        const colorInput = row.querySelector('.tela-color');
        const refInput = row.querySelector('.tela-referencia');

        if (prenda.variantes?.telas_multiples?.[telaIdx]) {
            prenda.variantes.telas_multiples[telaIdx].nombre_tela = nombreInput?.value || '';
            prenda.variantes.telas_multiples[telaIdx].color = colorInput?.value || '';
            prenda.variantes.telas_multiples[telaIdx].referencia = refInput?.value || '';
            // También actualizar en el array telas
            if (prenda.telas?.[telaIdx]) {
                prenda.telas[telaIdx].nombre_tela = nombreInput?.value || '';
                prenda.telas[telaIdx].color = colorInput?.value || '';
                prenda.telas[telaIdx].referencia = refInput?.value || '';
            }
        }
    });

    // Sincronizar variaciones (incluyendo observaciones)
    container.querySelectorAll('[data-field]').forEach(field => {
        const nombreCampo = field.dataset.field;
        if (nombreCampo) {
            const valor = field.value || field.textContent;
            if (prenda.variantes && nombreCampo in prenda.variantes) {
                if (nombreCampo.includes('tiene_')) {
                    prenda.variantes[nombreCampo] = valor === 'Sí';
                    prenda[nombreCampo] = valor === 'Sí';
                } else {
                    prenda.variantes[nombreCampo] = valor;
                    prenda[nombreCampo] = valor;
                }
            }
        }
    });

    // ✅ SINCRONIZAR OBSERVACIONES EXPLÍCITAMENTE
    container.querySelectorAll('.variacion-obs').forEach(textarea => {
        const campoObs = textarea.dataset.field;
        if (campoObs && prenda.variantes) {
            prenda.variantes[campoObs] = textarea.value;
            logWithEmoji('📝', `Observación ${campoObs} guardada: "${textarea.value}"`);
        }
    });

    logWithEmoji('🔄', `Datos de prenda ${prendaIndex} sincronizados completamente`);
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
                                // Es una foto existente - eliminarla de prenda.fotos
                                prenda.fotos.splice(idx, 1);
                            } else {
                                // Es una foto nueva - eliminar del array de fotos nuevas
                                const idxEnNuevas = idx - fotosExistentes;
                                fotosNuevas.splice(idxEnNuevas, 1);
                                window.gestorPrendaSinCotizacion.fotosNuevas[index] = fotosNuevas;
                            }

                            if (galeriaUrls.length === 0) {
                                Swal.fire('Eliminado', 'Última foto eliminada. Cerrando galería.', 'success');
                                window.__galeriaPrendaTipoActiva = false;
                                window.removeEventListener('keydown', keyHandler);
                                // Re-renderizar para actualizar vista
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

                            // Actualizar en el gestor si es una foto nueva
                            if (idx < fotosTelaJSON.length) {
                                // Es una foto existente - manejar por ID si es necesario
                                logWithEmoji('🗑️', 'Foto de tela existente (requiere manejo especial)');
                            } else {
                                // Es una foto nueva - eliminar del array de fotos nuevas
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
 * Marcar prenda como de bodega o no
 */
window.marcarPrendaDeBodega = function(prendaIndex, valor) {
    const gestor = window.gestorPrendaSinCotizacion;
    const prenda = gestor.obtenerPorIndice(prendaIndex);
    
    if (!prenda) {
        console.error(`❌ Prenda ${prendaIndex} no encontrada`);
        return;
    }
    
    prenda.de_bodega = valor;
    
    const estado = valor ? '✅ Marcada como de bodega' : '❌ Desmarcada de bodega';
    logWithEmoji('🏭', `Prenda ${prendaIndex + 1}: ${estado}`);
};

logWithEmoji('✅', 'Funciones globales de Prenda Sin Cotización cargadas');
