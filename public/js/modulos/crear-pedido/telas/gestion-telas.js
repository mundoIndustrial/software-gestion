/**
 * GESTIÓN DE TELAS
 * Sistema centralizado para manejar telas, colores, referencias e imágenes
 * 
 * CARACTERÍSTICAS:
 * - Gestión de múltiples telas con colores, referencias e imágenes
 * - Hasta 3 imágenes por tela
 * - Modal de visualización de imágenes en galería
 * - Tabla dinámica de telas agregadas
 */

// ========== ESTADO GLOBAL DE TELAS ==========
window.telasAgregadas = [];
window.imagenesTelaModalNueva = [];

//  GUARD: Asegurar que imagenesTelaStorage existe
if (!window.imagenesTelaStorage) {

    window.imagenesTelaStorage = {
        obtenerImagenes: () => [],
        agregarImagen: (file) => {

            return Promise.resolve();
        },
        limpiar: () => {

            return Promise.resolve();
        },
        obtenerBlob: (index) => null
    };
} else {

}

// ========== AGREGAR NUEVA TELA ==========
window.agregarTelaNueva = async function() {

    
    const color = document.getElementById('nueva-prenda-color').value.trim().toUpperCase();
    const tela = document.getElementById('nueva-prenda-tela').value.trim().toUpperCase();
    const referencia = document.getElementById('nueva-prenda-referencia').value.trim().toUpperCase();
    

    
    // Validación
    if (!color) {
        alert('Por favor completa el campo Color');
        document.getElementById('nueva-prenda-color').focus();
        return;
    }
    if (!tela) {
        alert('Por favor completa el campo Tela');
        document.getElementById('nueva-prenda-tela').focus();
        return;
    }
    if (!referencia) {
        alert('Por favor completa el campo Referencia');
        document.getElementById('nueva-prenda-referencia').focus();
        return;
    }
    
    // ✅ Buscar o crear tela en BD
    let telaId = null;
    const datalistTelas = document.getElementById('opciones-telas');
    if (datalistTelas) {
        for (let option of datalistTelas.options) {
            if (option.value.toUpperCase() === tela) {
                telaId = parseInt(option.dataset.id);
                break;
            }
        }
    }
    
    // Si no existe, crearla
    if (!telaId) {
        try {
            const response = await fetch('/asesores/api/telas', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ nombre: tela, referencia: referencia })
            });
            const result = await response.json();
            if (result.success && result.data) {
                telaId = result.data.id;
                
                // Agregar al datalist
                if (datalistTelas) {
                    const newOption = document.createElement('option');
                    newOption.value = result.data.nombre;
                    newOption.dataset.id = result.data.id;
                    newOption.dataset.referencia = result.data.referencia || '';
                    datalistTelas.appendChild(newOption);
                }
                
                console.log('[Telas] ✅ Tela creada:', result.data);
            }
        } catch (error) {
            console.error('[Telas] Error creando tela:', error);
        }
    }
    
    // ✅ Buscar o crear color en BD
    let colorId = null;
    const datalistColores = document.getElementById('opciones-colores');
    if (datalistColores) {
        for (let option of datalistColores.options) {
            if (option.value.toUpperCase() === color) {
                colorId = parseInt(option.dataset.id);
                break;
            }
        }
    }
    
    // Si no existe, crearlo
    if (!colorId) {
        try {
            const response = await fetch('/asesores/api/colores', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ nombre: color })
            });
            const result = await response.json();
            if (result.success && result.data) {
                colorId = result.data.id;
                
                // Agregar al datalist
                if (datalistColores) {
                    const newOption = document.createElement('option');
                    newOption.value = result.data.nombre;
                    newOption.dataset.id = result.data.id;
                    newOption.dataset.codigo = result.data.codigo || '';
                    datalistColores.appendChild(newOption);
                }
                
                console.log('[Colores] ✅ Color creado:', result.data);
            }
        } catch (error) {
            console.error('[Colores] Error creando color:', error);
        }
    }
    
    // Obtener imágenes del storage temporal - SOLO GUARDAR FILE OBJECTS (no blob URLs)
    const imagenesTemporales = window.imagenesTelaStorage.obtenerImagenes();

    
    // Copiar SOLO los File objects y metadatos (NO el previewUrl volátil)
    const imagenesCopia = imagenesTemporales.map(img => ({
        file: img.file,  // El File object es permanente
        nombre: img.nombre,
        tamaño: img.tamaño
        // NO copiar previewUrl - crearemos una nueva blob URL cuando sea necesario
    }));
    
    // Agregar a la lista
    window.telasAgregadas.push({ 
        color, 
        tela, 
        referencia,
        color_id: colorId,
        tela_id: telaId,
        imagenes: imagenesCopia
    });
    


    
    // Limpiar inputs
    document.getElementById('nueva-prenda-color').value = '';
    document.getElementById('nueva-prenda-tela').value = '';
    document.getElementById('nueva-prenda-referencia').value = '';
    
    // NO LIMPIAR window.imagenesTelaStorage aquí - se necesita para enviar las imágenes
    // Se limpiará después de que se envíe el pedido
    // window.imagenesTelaStorage.limpiar();
    
    // Limpiar preview temporal (el que se mostró mientras se agregaban imágenes - ahora dentro de la celda)
    const previewTemporal = document.getElementById('nueva-prenda-tela-preview');
    if (previewTemporal) {
        previewTemporal.innerHTML = '';
        previewTemporal.style.display = 'none'; // Ocultar completamente
    }
    
    // Limpiar input file
    const inputFile = document.getElementById('nueva-prenda-tela-img-input');
    if (inputFile) {
        inputFile.value = '';
    }
    
    // Actualizar tabla

    actualizarTablaTelas();
};

/**
 * Actualizar tabla de telas
 */
window.actualizarTablaTelas = function() {
    const tbody = document.getElementById('tbody-telas');
    
    if (!tbody) {

        return;
    }
    

    
    // Limpiar tbody excepto la fila de inputs (la primera fila)
    const filas = Array.from(tbody.querySelectorAll('tr'));

    
    filas.forEach((fila, index) => {

        if (index > 0) {
            fila.remove();

        }
    });
    

    
    // Agregar filas con los datos
    window.telasAgregadas.forEach((telaData, index) => {

        
        const tr = document.createElement('tr');
        tr.style.cssText = 'border-bottom: 1px solid #e5e7eb;';
        
        // Crear celda de imágenes
        let imagenHTML = '';
        if (telaData.imagenes && telaData.imagenes.length > 0) {

            
            // Crear un array con blob URLs dinámicas para esta visualización
            const imagenConBlobUrl = telaData.imagenes.map((img, imgIndex) => {
                // Crear una nueva blob URL a partir del File object
                let blobUrl;
                if (img && img.file instanceof File) {
                    blobUrl = URL.createObjectURL(img.file);
                } else if (img instanceof File) {
                    blobUrl = URL.createObjectURL(img);
                } else if (img && img.blobUrl) {
                    blobUrl = img.blobUrl;
                } else if (typeof img === 'string') {
                    blobUrl = img;
                } else if (img && img.url) {
                    // Backend retorna objetos con propiedades url, ruta_webp, ruta_original
                    blobUrl = img.url;
                } else if (img && img.ruta_webp) {
                    blobUrl = img.ruta_webp;
                } else if (img && img.ruta_original) {
                    blobUrl = img.ruta_original;
                } else if (img instanceof Blob) {
                    blobUrl = URL.createObjectURL(img);
                } else {

                    blobUrl = '';
                }
                if (blobUrl) {

                }
                return {
                    ...img,
                    previewUrl: blobUrl  // Blob URL recién creada para esta sesión
                };
            });
            
            imagenHTML = `
                <div style="display: flex; gap: 0.5rem; align-items: center; justify-content: center;">
                    <img src="${imagenConBlobUrl[0].previewUrl}" style="width: 40px; height: 40px; border-radius: 4px; object-fit: cover; cursor: pointer;" onclick="mostrarGaleriaImagenesTela(null, ${index}, 0)">
                    ${imagenConBlobUrl.length > 1 ? `<span style="background: #0066cc; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold;">+${imagenConBlobUrl.length - 1}</span>` : ''}
                </div>
            `;
        } else {

        }
        
        const html = `
            <td style="padding: 0.75rem; vertical-align: middle;">${telaData.tela}</td>
            <td style="padding: 0.75rem; vertical-align: middle;">${telaData.color}</td>
            <td style="padding: 0.75rem; vertical-align: middle;">${telaData.referencia}</td>
            <td style="padding: 0.75rem; text-align: center; vertical-align: middle; min-height: 60px; display: table-cell;">
                ${imagenHTML}
            </td>
            <td style="padding: 0.75rem; text-align: center; vertical-align: middle;">
                <button type="button" onclick="eliminarTela(${index})" class="btn btn-sm" style="background: #ef4444; color: white; padding: 0.25rem 0.5rem; font-size: 0.75rem; border: none; cursor: pointer; border-radius: 4px; transition: background 0.2s;" onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                    <span class="material-symbols-rounded" style="font-size: 1rem;">delete</span>
                </button>
            </td>
        `;
        
        tr.innerHTML = html;
        tbody.appendChild(tr);

    });
    

};

/**
 * Eliminar tela con confirmación
 */
window.eliminarTela = function(index) {
    const confirmModal = document.createElement('div');
    confirmModal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 10002;';
    
    const confirmBox = document.createElement('div');
    confirmBox.style.cssText = 'background: white; border-radius: 12px; padding: 2rem; max-width: 400px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);';
    
    const titulo = document.createElement('h3');
    titulo.textContent = '¿Eliminar esta tela?';
    titulo.style.cssText = 'margin: 0 0 1rem 0; color: #1f2937; font-size: 1.25rem;';
    confirmBox.appendChild(titulo);
    
    const mensaje = document.createElement('p');
    mensaje.textContent = 'Esta acción no se puede deshacer.';
    mensaje.style.cssText = 'margin: 0 0 1.5rem 0; color: #6b7280; font-size: 0.95rem;';
    confirmBox.appendChild(mensaje);
    
    const botones = document.createElement('div');
    botones.style.cssText = 'display: flex; gap: 1rem; justify-content: flex-end;';
    
    const btnCancelar = document.createElement('button');
    btnCancelar.textContent = 'Cancelar';
    btnCancelar.style.cssText = 'background: #e5e7eb; color: #1f2937; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 500; transition: background 0.2s;';
    btnCancelar.onmouseover = () => btnCancelar.style.background = '#d1d5db';
    btnCancelar.onmouseout = () => btnCancelar.style.background = '#e5e7eb';
    btnCancelar.onclick = () => confirmModal.remove();
    botones.appendChild(btnCancelar);
    
    const btnConfirmar = document.createElement('button');
    btnConfirmar.textContent = 'Eliminar';
    btnConfirmar.style.cssText = 'background: #ef4444; color: white; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 500; transition: background 0.2s;';
    btnConfirmar.onmouseover = () => btnConfirmar.style.background = '#dc2626';
    btnConfirmar.onmouseout = () => btnConfirmar.style.background = '#ef4444';
    btnConfirmar.onclick = () => {
        confirmModal.remove();

        window.telasAgregadas.splice(index, 1);
        actualizarTablaTelas();
    };
    botones.appendChild(btnConfirmar);
    
    confirmBox.appendChild(botones);
    confirmModal.appendChild(confirmBox);
    document.body.appendChild(confirmModal);
};

/**
 * Manejar imagen de tela
 */
window.manejarImagenTela = function(input) {
    if (!input.files || input.files.length === 0) {
        return;
    }
    
    const file = input.files[0];
    
    // Validar que sea imagen
    if (!file.type.startsWith('image/')) {
        alert('Por favor selecciona una imagen válida');
        return;
    }
    
    // Verificar límite de 3 imágenes
    if (window.imagenesTelaStorage.obtenerImagenes().length >= 3) {
        alert('Máximo 3 imágenes por tela');
        return;
    }
    
    // Agregar imagen al storage
    window.imagenesTelaStorage.agregarImagen(file)
        .then(() => {

            
            //  Actualizar preview temporal en la primera fila
            const preview = document.getElementById('nueva-prenda-tela-preview');
            if (preview) {
                preview.style.display = 'flex';
                preview.innerHTML = '';
                
                const imagenes = window.imagenesTelaStorage.obtenerImagenes();
                imagenes.forEach((img, idx) => {
                    const imgEl = document.createElement('img');
                    imgEl.src = img.previewUrl;
                    imgEl.style.cssText = 'width: 40px; height: 40px; border-radius: 4px; object-fit: cover; cursor: pointer;';
                    imgEl.onclick = () => {

                    };
                    preview.appendChild(imgEl);
                });
                
                // Mostrar badge de cantidad si hay más de 1
                if (imagenes.length > 1) {
                    const badge = document.createElement('span');
                    badge.style.cssText = 'position: absolute; background: #0066cc; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold; margin-left: -12px; margin-top: 30px;';
                    badge.textContent = `+${imagenes.length - 1}`;
                    preview.appendChild(badge);
                }
                

            }
            
            input.value = '';
        })
        .catch(err => {
            alert(err.message);
        });
};

/**
 *  DEPRECATED: Ya no se usa - las imágenes de tela se renderizan en actualizarTablaTelas()
 * Las imágenes se renderizaban en este punto, pero causaba errores porque la fila
 * de la tela aún no existía en la tabla. Ahora solo se renderizan cuando la tela
 * se agrega y se crea su fila correspondiente.
 */
window.actualizarPreviewTela = function() {

};

/**
 * Mostrar galería de imágenes temporales (antes de guardar tela)
 */
window.mostrarGaleriaImagenesTemporales = function(imagenes, indiceInicial = 0) {
    if (!imagenes || imagenes.length === 0) return;
    
    window.imagenesTelaModalNueva = imagenes;
    let indiceActual = indiceInicial;
    
    const modal = document.createElement('div');
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.95); display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 100001; padding: 0;';
    
    const container = document.createElement('div');
    container.style.cssText = 'position: relative; display: flex; flex-direction: column; align-items: center; width: 100%; height: 100%; max-width: 100%; max-height: 100%;';
    
    const imgContainer = document.createElement('div');
    imgContainer.style.cssText = 'flex: 1; display: flex; align-items: center; justify-content: center; position: relative; width: 100%; height: calc(100% - 120px); padding: 2rem;';
    
    const imgModal = document.createElement('img');
    imgModal.src = imagenes[indiceActual].previewUrl;  // Usar blob URL en lugar de base64
    imgModal.style.cssText = 'width: 90vw; height: 85vh; border-radius: 8px; object-fit: contain; box-shadow: 0 20px 50px rgba(0,0,0,0.7);';
    
    imgContainer.appendChild(imgModal);
    
    // Toolbar
    const toolbar = document.createElement('div');
    toolbar.style.cssText = 'display: flex; justify-content: center; align-items: center; width: 100%; gap: 1rem; padding: 1.5rem; background: rgba(0,0,0,0.5);';
    
    const btnAnterior = document.createElement('button');
    btnAnterior.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">arrow_back</span>';
    btnAnterior.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
    btnAnterior.onmouseover = () => btnAnterior.style.background = '#0052a3';
    btnAnterior.onmouseout = () => btnAnterior.style.background = '#0066cc';
    btnAnterior.onclick = () => {
        indiceActual = (indiceActual - 1 + imagenes.length) % imagenes.length;
        imgModal.src = imagenes[indiceActual].previewUrl;  // Usar blob URL
        contador.textContent = (indiceActual + 1) + ' de ' + imagenes.length;
    };
    toolbar.appendChild(btnAnterior);
    
    //  BOTÓN ELIMINAR REMOVIDO - Solo usar la X para cerrar la galería
    
    const contador = document.createElement('div');
    contador.style.cssText = 'color: white; font-size: 0.95rem; font-weight: 500; min-width: 80px; text-align: center;';
    contador.textContent = (indiceActual + 1) + ' de ' + imagenes.length;
    toolbar.appendChild(contador);
    
    const btnSiguiente = document.createElement('button');
    btnSiguiente.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">arrow_forward</span>';
    btnSiguiente.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
    btnSiguiente.onmouseover = () => btnSiguiente.style.background = '#0052a3';
    btnSiguiente.onmouseout = () => btnSiguiente.style.background = '#0066cc';
    btnSiguiente.onclick = () => {
        indiceActual = (indiceActual + 1) % imagenes.length;
        imgModal.src = imagenes[indiceActual].previewUrl;  // Usar blob URL
        contador.textContent = (indiceActual + 1) + ' de ' + imagenes.length;
    };
    toolbar.appendChild(btnSiguiente);
    
    const btnCerrar = document.createElement('button');
    btnCerrar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">close</span>';
    btnCerrar.style.cssText = 'background: rgba(255,255,255,0.2); color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
    btnCerrar.onmouseover = () => btnCerrar.style.background = 'rgba(255,255,255,0.3)';
    btnCerrar.onmouseout = () => btnCerrar.style.background = 'rgba(255,255,255,0.2)';
    btnCerrar.onclick = () => modal.remove();
    toolbar.appendChild(btnCerrar);
    
    container.appendChild(imgContainer);
    container.appendChild(toolbar);
    modal.appendChild(container);
    document.body.appendChild(modal);
};

/**
 * Obtener telas para envío
 */
window.obtenerTelasParaEnvio = function() {

    return window.telasAgregadas;
};

/**
 * Limpiar todas las telas
 */
window.limpiarTelas = function() {

    window.telasAgregadas = [];
    if (window.imagenesTelaStorage) {
        window.imagenesTelaStorage.limpiar();
    }
    actualizarTablaTelas();
};

/**
 * NUEVA GALERÍA: Mostrar galería de imágenes de tela (mismo comportamiento que prendas)
 * @param {Array} imagenes - Array de imágenes de la tela
 * @param {number} telaIndex - Índice de la tela en la tabla
 * @param {number} indiceInicial - Índice inicial a mostrar
 */
window.mostrarGaleriaImagenesTela = function(imagenes, telaIndex = 0, indiceInicial = 0) {
    //  Obtener la tela específica y sus imágenes (fuente de verdad por tela)
    const telaActual = window.telasAgregadas && window.telasAgregadas[telaIndex] ? window.telasAgregadas[telaIndex] : null;
    if (!telaActual) {

        return;
    }
    const imagenesActuales = telaActual.imagenes || [];
    
    if (!imagenesActuales || imagenesActuales.length === 0) {

        return;
    }
    
    //  Evitar que se reabra la galería mientras está en uso
    if (window.__galeriaTelaAbierta) {

        return;
    }
    window.__galeriaTelaAbierta = true;
    

    
    // Crear nuevos blob URLs para evitar que se revoquen
    const imagenesConBlobUrl = imagenesActuales.map((img, idx) => {
        let blobUrl;
        if (img.file instanceof File || img.file instanceof Blob) {
            blobUrl = URL.createObjectURL(img.file);
        } else if (img.previewUrl && img.previewUrl.startsWith('blob:')) {
            blobUrl = img.previewUrl;
        } else {

            return null;
        }
        return {
            ...img,
            previewUrl: blobUrl,
            blobUrl: blobUrl
        };
    }).filter(img => img !== null);
    
    if (imagenesConBlobUrl.length === 0) {

        window.__galeriaTelaAbierta = false;
        return;
    }
    
    let indiceActual = indiceInicial;
    
    const modal = document.createElement('div');
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); display: flex; flex-direction: column; align-items: center; justify-content: flex-start; z-index: 10000; padding: 0; margin: 0; overflow: hidden;';
    
    const container = document.createElement('div');
    container.style.cssText = 'position: relative; display: flex; flex-direction: column; align-items: center; width: 100%; height: 100%; max-width: 100%; max-height: 100%;';
    
    const imgContainer = document.createElement('div');
    imgContainer.style.cssText = 'flex: 1; display: flex; align-items: center; justify-content: center; position: relative; width: 100%; padding: 2rem 1rem; overflow: hidden;';
    
    const imgModal = document.createElement('img');
    imgModal.src = imagenesConBlobUrl[indiceActual].previewUrl;
    imgModal.style.cssText = 'width: 90vw; height: 85vh; border-radius: 8px; object-fit: contain; box-shadow: 0 20px 50px rgba(0,0,0,0.7);';
    
    imgContainer.appendChild(imgModal);
    
    //  Función auxiliar para actualizar la imagen
    const actualizarImagen = (nuevoIndice) => {
        indiceActual = nuevoIndice;
        const newBlobUrl = imagenesConBlobUrl[indiceActual].previewUrl;
        imgModal.src = '';
        imgModal.src = newBlobUrl;
        contador.textContent = (indiceActual + 1) + ' de ' + imagenesConBlobUrl.length;

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

        const nuevoIndice = (indiceActual - 1 + imagenesConBlobUrl.length) % imagenesConBlobUrl.length;
        actualizarImagen(nuevoIndice);
    };
    toolbar.appendChild(btnAnterior);
    
    //  BOTÓN ELIMINAR REMOVIDO - Solo usar la X del formulario para eliminar
    // Las imágenes de telas se eliminan desde el formulario, no desde la galería
    
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

        cerrarGaleria();
    };
    toolbar.appendChild(btnCerrar);
    
    // Cerrar con ESC
    const handleEsc = (e) => {
        if (e.key === 'Escape') {

            cerrarGaleria();
        }
    };
    document.addEventListener('keydown', handleEsc);
    
    // Cerrar al clickear afuera
    modal.onclick = (e) => {
        if (e.target === modal) {

            cerrarGaleria();
        }
    };
    
    //  Función para cerrar la galería y limpiar flags
    const cerrarGaleria = () => {
        document.removeEventListener('keydown', handleEsc);
        modal.remove();
        window.__galeriaTelaAbierta = false;
    };
    
    container.appendChild(imgContainer);
    container.appendChild(toolbar);
    modal.appendChild(container);
    document.body.appendChild(modal);
    

};
