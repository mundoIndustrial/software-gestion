/**
 * Modales Dinámicos para Crear Pedido Editable
 * Archivo centralizado con todas las funciones de modales
 */

/**
 * Mostrar galería de imágenes de prenda
 */
function mostrarGaleriaPrenda(imagenes, indiceInicial = 0) {
    let indiceActual = indiceInicial;
    let modalClosed = false;
    
    const modal = document.createElement('div');
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.95); display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 10000; padding: 0;';
    modal.onclick = function(e) {
        if (e.target === modal && !modalClosed) {
            modalClosed = true;
            modal.remove();
        }
    };
    
    const container = document.createElement('div');
    container.style.cssText = 'position: relative; display: flex; flex-direction: column; align-items: center; width: 100%; height: 100%; max-width: 100%; max-height: 100%;';
    
    // Contenedor de imagen
    const imgContainer = document.createElement('div');
    imgContainer.style.cssText = 'flex: 1; display: flex; align-items: center; justify-content: center; position: relative; width: 100%; height: calc(100% - 120px); padding: 2rem;';
    
    const img = document.createElement('img');
    img.style.cssText = 'width: 90%; height: 90%; border-radius: 8px; object-fit: contain; box-shadow: 0 20px 50px rgba(0,0,0,0.7);';
    
    // Actualizar imagen
    const actualizarImagen = () => {
        const imagenesActuales = window.imagenesPrendaStorage.obtenerImagenes();
        if (imagenesActuales.length > 0 && indiceActual < imagenesActuales.length) {
            img.src = imagenesActuales[indiceActual].data;
        }
    };
    
    actualizarImagen();
    imgContainer.appendChild(img);
    container.appendChild(imgContainer);
    
    // Barra de herramientas
    const toolbar = document.createElement('div');
    toolbar.style.cssText = 'display: flex; justify-content: center; align-items: center; width: 100%; gap: 1rem; padding: 1.5rem; background: rgba(0,0,0,0.5);';
    
    // Botón anterior
    const btnAnterior = document.createElement('button');
    btnAnterior.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">arrow_back</span>';
    btnAnterior.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
    btnAnterior.onmouseover = () => btnAnterior.style.background = '#0052a3';
    btnAnterior.onmouseout = () => btnAnterior.style.background = '#0066cc';
    btnAnterior.onclick = () => {
        const imagenesActuales = window.imagenesPrendaStorage.obtenerImagenes();
        if (imagenesActuales.length > 0) {
            indiceActual = (indiceActual - 1 + imagenesActuales.length) % imagenesActuales.length;
            actualizarImagen();
            contador.textContent = (indiceActual + 1) + ' de ' + imagenesActuales.length;
        }
    };
    toolbar.appendChild(btnAnterior);
    
    // Botón eliminar
    const btnEliminar = document.createElement('button');
    btnEliminar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">delete</span>';
    btnEliminar.style.cssText = 'background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
    btnEliminar.onmouseover = () => btnEliminar.style.background = '#dc2626';
    btnEliminar.onmouseout = () => btnEliminar.style.background = '#ef4444';
    btnEliminar.onclick = () => mostrarConfirmacionEliminarImagen(
        indiceActual, 
        modal, 
        actualizarImagen, 
        contador
    );
    toolbar.appendChild(btnEliminar);
    
    // Contador
    const contador = document.createElement('div');
    contador.style.cssText = 'color: white; font-size: 0.95rem; font-weight: 500; min-width: 80px; text-align: center;';
    contador.textContent = (indiceActual + 1) + ' de ' + window.imagenesPrendaStorage.obtenerImagenes().length;
    toolbar.appendChild(contador);
    
    // Botón siguiente
    const btnSiguiente = document.createElement('button');
    btnSiguiente.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">arrow_forward</span>';
    btnSiguiente.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
    btnSiguiente.onmouseover = () => btnSiguiente.style.background = '#0052a3';
    btnSiguiente.onmouseout = () => btnSiguiente.style.background = '#0066cc';
    btnSiguiente.onclick = () => {
        const imagenesActuales = window.imagenesPrendaStorage.obtenerImagenes();
        if (imagenesActuales.length > 0) {
            indiceActual = (indiceActual + 1) % imagenesActuales.length;
            actualizarImagen();
            contador.textContent = (indiceActual + 1) + ' de ' + imagenesActuales.length;
        }
    };
    toolbar.appendChild(btnSiguiente);
    
    // Botón cerrar
    const btnCerrar = document.createElement('button');
    btnCerrar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">close</span>';
    btnCerrar.style.cssText = 'background: rgba(255,255,255,0.2); color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
    btnCerrar.onmouseover = () => btnCerrar.style.background = 'rgba(255,255,255,0.3)';
    btnCerrar.onmouseout = () => btnCerrar.style.background = 'rgba(255,255,255,0.2)';
    btnCerrar.onclick = () => {
        if (!modalClosed) {
            modalClosed = true;
            modal.remove();
        }
    };
    toolbar.appendChild(btnCerrar);
    
    container.appendChild(toolbar);
    modal.appendChild(container);
    document.body.appendChild(modal);
    
    // Navegación con teclas
    const manejarTeclas = function(e) {
        if (e.key === 'ArrowLeft') {
            btnAnterior.click();
        } else if (e.key === 'ArrowRight') {
            btnSiguiente.click();
        } else if (e.key === 'Escape') {
            if (!modalClosed) {
                modalClosed = true;
                modal.remove();
            }
            document.removeEventListener('keydown', manejarTeclas);
        }
    };
    document.addEventListener('keydown', manejarTeclas);
    
    modal.addEventListener('remove', function() {
        document.removeEventListener('keydown', manejarTeclas);
    });
}

/**
 * Modal de confirmación para eliminar imagen
 */
function mostrarConfirmacionEliminarImagen(indiceActual, galeriaModal, actualizarImagen, contador) {
    const confirmModal = document.createElement('div');
    confirmModal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 10002;';
    
    const confirmBox = document.createElement('div');
    confirmBox.style.cssText = 'background: white; border-radius: 12px; padding: 2rem; max-width: 400px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);';
    
    const titulo = document.createElement('h3');
    titulo.textContent = '¿Eliminar esta imagen?';
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
        window.imagenesPrendaStorage.eliminarImagen(indiceActual);
        const imagenesRestantes = window.imagenesPrendaStorage.obtenerImagenes();
        
        if (imagenesRestantes.length === 0) {
            galeriaModal.remove();
            actualizarPreviewPrenda();
            return;
        }
        
        actualizarImagen();
        contador.textContent = (indiceActual + 1) + ' de ' + imagenesRestantes.length;
        actualizarPreviewPrenda();
    };
    botones.appendChild(btnConfirmar);
    
    confirmBox.appendChild(botones);
    confirmModal.appendChild(confirmBox);
    document.body.appendChild(confirmModal);
}

/**
 * Mostrar galería de imágenes de reflectivo
 */
function mostrarGaleriaReflectivo(imagenes, indiceInicial = 0) {
    let indiceActual = indiceInicial;
    let modalClosed = false;
    
    const modal = document.createElement('div');
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.95); display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 10000; padding: 0;';
    modal.onclick = function(e) {
        if (e.target === modal && !modalClosed) {
            modalClosed = true;
            modal.remove();
        }
    };
    
    const container = document.createElement('div');
    container.style.cssText = 'position: relative; display: flex; flex-direction: column; align-items: center; width: 100%; height: 100%; max-width: 100%; max-height: 100%;';
    
    const imgContainer = document.createElement('div');
    imgContainer.style.cssText = 'flex: 1; display: flex; align-items: center; justify-content: center; position: relative; width: 100%; height: calc(100% - 120px); padding: 2rem;';
    
    const img = document.createElement('img');
    img.style.cssText = 'width: 90%; height: 90%; border-radius: 8px; object-fit: contain; box-shadow: 0 20px 50px rgba(0,0,0,0.7);';
    img.src = imagenes[indiceActual].data;
    
    imgContainer.appendChild(img);
    container.appendChild(imgContainer);
    
    const toolbar = document.createElement('div');
    toolbar.style.cssText = 'display: flex; justify-content: center; align-items: center; width: 100%; gap: 1rem; padding: 1.5rem; background: rgba(0,0,0,0.5);';
    
    const btnAnterior = document.createElement('button');
    btnAnterior.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">arrow_back</span>';
    btnAnterior.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;';
    btnAnterior.onclick = () => {
        if (imagenes.length > 0) {
            indiceActual = (indiceActual - 1 + imagenes.length) % imagenes.length;
            img.src = imagenes[indiceActual].data;
            contador.textContent = (indiceActual + 1) + ' de ' + imagenes.length;
        }
    };
    toolbar.appendChild(btnAnterior);
    
    const contador = document.createElement('div');
    contador.style.cssText = 'color: white; font-size: 0.95rem; font-weight: 500; min-width: 80px; text-align: center;';
    contador.textContent = (indiceActual + 1) + ' de ' + imagenes.length;
    toolbar.appendChild(contador);
    
    const btnSiguiente = document.createElement('button');
    btnSiguiente.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">arrow_forward</span>';
    btnSiguiente.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;';
    btnSiguiente.onclick = () => {
        if (imagenes.length > 0) {
            indiceActual = (indiceActual + 1) % imagenes.length;
            img.src = imagenes[indiceActual].data;
            contador.textContent = (indiceActual + 1) + ' de ' + imagenes.length;
        }
    };
    toolbar.appendChild(btnSiguiente);
    
    const btnCerrar = document.createElement('button');
    btnCerrar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">close</span>';
    btnCerrar.style.cssText = 'background: rgba(255,255,255,0.2); color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;';
    btnCerrar.onclick = () => {
        if (!modalClosed) {
            modalClosed = true;
            modal.remove();
        }
    };
    toolbar.appendChild(btnCerrar);
    
    container.appendChild(toolbar);
    modal.appendChild(container);
    document.body.appendChild(modal);
    
    const manejarTeclas = function(e) {
        if (e.key === 'ArrowLeft') {
            btnAnterior.click();
        } else if (e.key === 'ArrowRight') {
            btnSiguiente.click();
        } else if (e.key === 'Escape') {
            if (!modalClosed) {
                modalClosed = true;
                modal.remove();
            }
            document.removeEventListener('keydown', manejarTeclas);
        }
    };
    document.addEventListener('keydown', manejarTeclas);
}
