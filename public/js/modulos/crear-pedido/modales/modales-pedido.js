/**
 * MODALES Y DIÁLOGOS - CREAR PEDIDO EDITABLE
 * 
 * Este archivo contiene todas las funciones de modales y diálogos
 * utilizadas en el formulario de creación de pedidos.
 * 
 * Modales incluidos:
 * - Galerías de fotos (prenda, tela, logo)
 * - Modales de agregar fotos
 * - Modal de ubicación/sección para logo
 * - Modal de edición de sección
 * - Confirmaciones y alertas
 */

// ============================================================
// GALERÍAS DE FOTOS
// ============================================================

/**
 * Abre galería de fotos de tela con navegación por flechas
 * @param {number} prendaIndex - Índice de la prenda
 * @param {number} telaIndex - Índice de la tela
 * @param {number} fotoIdx - Índice de foto inicial (default 0)
 */
window.abrirGaleriaTela = function(prendaIndex, telaIndex, fotoIdx = 0) {
    const galeriaTela = (window.telasGaleria && window.telasGaleria[prendaIndex] && window.telasGaleria[prendaIndex][telaIndex])
        ? window.telasGaleria[prendaIndex][telaIndex]
        : [];
    if (!galeriaTela || galeriaTela.length === 0) {
        Swal.fire({ icon: 'info', title: 'Sin fotos', text: 'Esta tela no tiene imágenes para mostrar.' });
        return;
    }

    let idx = Math.max(0, Math.min(fotoIdx, galeriaTela.length - 1));

    const keyHandler = (e) => {
        if (!window.__galeriaTelaActiva) return;
        if (e.key === 'ArrowLeft') {
            e.preventDefault();
            document.getElementById('gal-tela-prev')?.click();
        } else if (e.key === 'ArrowRight') {
            e.preventDefault();
            document.getElementById('gal-tela-next')?.click();
        }
    };

    const renderModal = () => {
        const url = galeriaTela[idx];
        const contenido = `
            <div style="display:flex; flex-direction:column; align-items:center; gap:1rem;">
                <div style="position:relative; width:100%; max-width:620px;">
                    <img src="${url}" alt="Foto tela" style="width:100%; border-radius:8px; border:1px solid #e5e7eb; object-fit:contain; max-height:70vh;">
                    <button id="gal-tela-prev" style="position:absolute; top:50%; left:-16px; transform:translateY(-50%); background:#111827cc; color:white; border:none; border-radius:50%; width:38px; height:38px; cursor:pointer; font-size:1.1rem; display:flex; align-items:center; justify-content:center;">‹</button>
                    <button id="gal-tela-next" style="position:absolute; top:50%; right:-16px; transform:translateY(-50%); background:#111827cc; color:white; border:none; border-radius:50%; width:38px; height:38px; cursor:pointer; font-size:1.1rem; display:flex; align-items:center; justify-content:center;">›</button>
                </div>
                <div style="font-size:0.9rem; color:#4b5563;">${idx + 1} / ${galeriaTela.length}</div>
            </div>
        `;

        Swal.fire({
            html: contenido,
            showConfirmButton: false,
            showCloseButton: true,
            width: '75%',
            didOpen: () => {
                window.__galeriaTelaActiva = true;
                const prev = document.getElementById('gal-tela-prev');
                const next = document.getElementById('gal-tela-next');
                prev.onclick = () => { idx = (idx - 1 + galeriaTela.length) % galeriaTela.length; renderModal(); };
                next.onclick = () => { idx = (idx + 1) % galeriaTela.length; renderModal(); };
                window.addEventListener('keydown', keyHandler);
            },
            willClose: () => {
                window.__galeriaTelaActiva = false;
                window.removeEventListener('keydown', keyHandler);
            }
        });
    };

    renderModal();
};

/**
 * Abre galería de fotos de prenda con navegación
 * @param {number} prendaIndex - Índice de la prenda
 * @param {number} fotoIdx - Índice de foto inicial (default 0)
 */
window.abrirGaleriaPrenda = function(prendaIndex, fotoIdx = 0) {
    const galeria = (window.prendasGaleria && window.prendasGaleria[prendaIndex]) ? window.prendasGaleria[prendaIndex] : [];
    if (!galeria || galeria.length === 0) {
        Swal.fire({ icon: 'info', title: 'Sin fotos', text: 'Esta prenda no tiene imágenes para mostrar.' });
        return;
    }

    let idx = Math.max(0, Math.min(fotoIdx, galeria.length - 1));

    const keyHandler = (e) => {
        if (!window.__galeriaPrendaActiva) return;
        if (e.key === 'ArrowLeft') {
            e.preventDefault();
            document.getElementById('gal-prenda-prev')?.click();
        } else if (e.key === 'ArrowRight') {
            e.preventDefault();
            document.getElementById('gal-prenda-next')?.click();
        }
    };

    const renderModal = () => {
        const url = galeria[idx];
        const contenido = `
            <div style="display:flex; flex-direction:column; align-items:center; gap:1rem;">
                <div style="position:relative; width:100%; max-width:620px;">
                    <img src="${url}" alt="Foto prenda" style="width:100%; border-radius:8px; border:1px solid #e5e7eb; object-fit:contain; max-height:70vh;">
                    <button id="gal-prenda-prev" style="position:absolute; top:50%; left:-16px; transform:translateY(-50%); background:#111827cc; color:white; border:none; border-radius:50%; width:38px; height:38px; cursor:pointer; font-size:1.1rem; display:flex; align-items:center; justify-content:center;">‹</button>
                    <button id="gal-prenda-next" style="position:absolute; top:50%; right:-16px; transform:translateY(-50%); background:#111827cc; color:white; border:none; border-radius:50%; width:38px; height:38px; cursor:pointer; font-size:1.1rem; display:flex; align-items:center; justify-content:center;">›</button>
                </div>
                <div style="font-size:0.9rem; color:#4b5563;">${idx + 1} / ${galeria.length}</div>
            </div>
        `;

        Swal.fire({
            html: contenido,
            showConfirmButton: false,
            showCloseButton: true,
            width: '75%',
            didOpen: () => {
                window.__galeriaPrendaActiva = true;
                const prev = document.getElementById('gal-prenda-prev');
                const next = document.getElementById('gal-prenda-next');
                prev.onclick = () => { idx = (idx - 1 + galeria.length) % galeria.length; renderModal(); };
                next.onclick = () => { idx = (idx + 1) % galeria.length; renderModal(); };
                window.addEventListener('keydown', keyHandler);
            },
            willClose: () => {
                window.__galeriaPrendaActiva = false;
                window.removeEventListener('keydown', keyHandler);
            }
        });
    };

    renderModal();
};

/**
 * Abre modal genérico para mostrar una imagen
 * @param {string} url - URL de la imagen
 * @param {string} titulo - Título del modal
 */
window.abrirModalImagen = function(url, titulo) {
    Swal.fire({
        title: titulo || 'Imagen',
        html: `<img src="${url}" style="max-width: 100%; max-height: 80vh; border-radius: 8px;">`,
        showConfirmButton: true,
        confirmButtonText: 'Cerrar',
        width: '90%'
    });
};

// ============================================================
// MODALES DE FOTOS
// ============================================================

/**
 * Abre diálogo para agregar fotos al logo
 */
window.abrirModalAgregarFotosLogo = function() {
    if (logoFotosSeleccionadas && logoFotosSeleccionadas.length >= 5) {
        Swal.fire({
            icon: 'warning',
            title: 'Límite alcanzado',
            text: 'El logo puede tener máximo 5 imágenes',
            confirmButtonText: 'OK'
        });
        return;
    }
    
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.multiple = true;
    
    input.addEventListener('change', (e) => {
        window.manejarArchivosFotosLogo(e.target.files);
    });
    
    input.click();
};

/**
 * Abre diálogo para agregar fotos a una prenda
 * @param {number} prendaIndex - Índice de la prenda
 */
window.abrirModalAgregarFotosPrenda = function(prendaIndex) {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.multiple = true;
    
    input.addEventListener('change', (e) => {
        window.manejarArchivosFotosPrenda(e.target.files, prendaIndex);
    });
    
    input.click();
};

/**
 * Abre diálogo para agregar fotos a una tela
 * @param {number} prendaIndex - Índice de la prenda
 * @param {number} telaIndex - Índice de la tela
 */
window.abrirModalAgregarFotosTela = function(prendaIndex, telaIndex) {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.multiple = true;
    
    input.addEventListener('change', (e) => {
        window.manejarArchivosFotosTela(e.target.files, prendaIndex, telaIndex);
    });
    
    input.click();
};

// ============================================================
// MODALES DE UBICACIÓN PARA LOGO
// ============================================================

/**
 * Abre modal para configurar ubicación/sección del logo
 * @param {string} ubicacion - Nombre de la ubicación
 * @param {Array} opciones - Opciones disponibles
 * @param {Object} seccionActual - Datos actuales (null si es crear)
 */
window.abrirModalUbicacionLogo = function(ubicacion, opciones, seccionActual) {
    let html = `
        <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 1rem;" id="modalUbicacionLogo">
            <div style="background: white; border-radius: 16px; padding: 2rem; max-width: 600px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,0.3); max-height: 90vh; overflow-y: auto;">
                
                <!-- Header del modal -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 2px solid #f0f0f0;">
                    <h2 style="margin: 0; color: #1e40af; font-size: 1.3rem; font-weight: 700;">Editar Ubicación</h2>
                    <button type="button" onclick="cerrarModalUbicacionLogo()" style="background: none; border: none; color: #999; font-size: 1.8rem; cursor: pointer; padding: 0; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">×</button>
                </div>
                
                <!-- Sección 1: Nombre de la sección -->
                <div style="margin-bottom: 2rem;">
                    <label style="display: block; font-weight: 700; margin-bottom: 0.75rem; color: #1e40af; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px;">1. Nombre de la Sección</label>
                    <div style="position: relative;">
                        <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #999; font-size: 1.2rem;">👕</span>
                        <input type="text" id="nombreSeccionLogo" value="${ubicacion}" placeholder="Ej: CAMISA, JEAN, GORRA" style="width: 100%; padding: 0.75rem 0.75rem 0.75rem 2.5rem; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1rem; transition: all 0.3s; box-sizing: border-box;">
                    </div>
                </div>
                
                <!-- Sección 2: Ubicaciones -->
                <div style="margin-bottom: 2rem;">
                    <label style="display: block; font-weight: 700; margin-bottom: 1rem; color: #1e40af; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px;">2. Ubicaciones Disponibles</label>
                    <div id="opcionesUbicacionLogo" style="display: flex; flex-direction: column; gap: 0.5rem; padding: 1rem; background: #f9f9f9; border-radius: 8px; max-height: 250px; overflow-y: auto;"></div>
                </div>
                
                <!-- Sección 3: Agregar personalizado -->
                <div style="margin-bottom: 2rem;">
                    <label style="display: block; font-weight: 700; margin-bottom: 0.75rem; color: #1e40af; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px;">3. Agregar Personalizado</label>
                    <div style="display: flex; gap: 0.75rem;">
                        <input type="text" id="nuevaOpcionLogo" placeholder="Ej: BOLSILLO, MANGA" style="flex: 1; padding: 0.75rem; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 0.95rem; transition: all 0.3s; box-sizing: border-box;">
                        <button type="button" onclick="agregarOpcionPersonalizadaLogo()" style="background: linear-gradient(135deg, #27ae60 0%, #229954 100%); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.3s; white-space: nowrap;">+ Agregar</button>
                    </div>
                </div>
                
                <!-- Sección 4: Observaciones -->
                <div style="margin-bottom: 2rem;">
                    <label style="display: block; font-weight: 700; margin-bottom: 0.75rem; color: #1e40af; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px;">4. Observaciones</label>
                    <textarea id="obsUbicacionLogo" placeholder="Añade cualquier observación o nota importante..." style="width: 100%; padding: 0.75rem; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 0.95rem; resize: vertical; min-height: 80px; box-sizing: border-box; font-family: inherit; transition: all 0.3s;">${seccionActual && seccionActual.observaciones ? seccionActual.observaciones : ''}</textarea>
                </div>
                
                <!-- Botones de acción -->
                <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                    <button type="button" onclick="cerrarModalUbicacionLogo()" style="background: #f0f0f0; color: #333; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.3s;">Cancelar</button>
                    <button type="button" onclick="guardarUbicacionLogo()" style="background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.3s;">✓ Guardar</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', html);
    
    // Agregar opciones como checkboxes
    setTimeout(() => {
        const container = document.getElementById('opcionesUbicacionLogo');
        if (container && opciones && opciones.length > 0) {
            container.innerHTML = '';
            opciones.forEach(opcion => {
                const label = document.createElement('label');
                label.style.cssText = 'display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.75rem; border-radius: 6px; transition: background 0.2s; background: #f0f9ff; border: 1px solid #bfdbfe;';
                label.innerHTML = `
                    <input type="checkbox" value="${opcion}" ${seccionActual && seccionActual.ubicaciones && seccionActual.ubicaciones.includes(opcion) ? 'checked' : ''} style="width: 18px; height: 18px; cursor: pointer;" class="opcion-ubicacion-logo">
                    <span style="flex: 1;">${opcion}</span>
                `;
                label.addEventListener('mouseover', () => label.style.background = '#dbeafe');
                label.addEventListener('mouseout', () => label.style.background = '#f0f9ff');
                container.appendChild(label);
            });
        }
    }, 10);
    
    // Mejorar inputs con estilos al enfocar
    setTimeout(() => {
        const inputs = document.querySelectorAll('#modalUbicacionLogo input[type="text"], #modalUbicacionLogo textarea');
        inputs.forEach(input => {
            input.addEventListener('focus', () => {
                input.style.borderColor = '#0066cc';
                input.style.boxShadow = '0 0 0 3px rgba(0, 102, 204, 0.1)';
            });
            input.addEventListener('blur', () => {
                input.style.borderColor = '#e0e0e0';
                input.style.boxShadow = 'none';
            });
        });
    }, 20);
};

/**
 * Cierra el modal de ubicación
 */
window.cerrarModalUbicacionLogo = function() {
    const modal = document.getElementById('modalUbicacionLogo');
    if (modal) modal.remove();
};

/**
 * Abre modal para editar sección en tab de logo (cotización combinada)
 * @param {string} ubicacion - Nombre de la ubicación
 * @param {Array} opcionesDisponibles - Opciones para esta ubicación
 * @param {Object} seccionData - Datos de la sección (null si es crear)
 */
window.abrirModalSeccionEditarTab = function(ubicacion, opcionesDisponibles, seccionData) {
    // Función para renderizar modal - incluida en este archivo
    console.log('📍 Abriendo modal de edición para:', ubicacion);
    Swal.fire({
        title: seccionData ? 'Editar Sección' : 'Configurar Sección',
        html: `<div style="text-align: left; max-height: 60vh; overflow-y: auto;">
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1f2937;">Ubicaciones:</label>
                <div id="opcionesModal" style="display: flex; flex-wrap: wrap; gap: 0.5rem; padding: 1rem; background: #f9f9f9; border-radius: 8px; max-height: 200px; overflow-y: auto;"></div>
            </div>
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1f2937;">Tallas:</label>
                <div id="tallasModal" style="display: flex; flex-wrap: wrap; gap: 0.5rem; padding: 1rem; background: #f9f9f9; border-radius: 8px; max-height: 200px; overflow-y: auto;"></div>
            </div>
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1f2937;">Observaciones:</label>
                <textarea id="obsModal" placeholder="Notas..." style="width: 100%; padding: 0.75rem; border: 1px solid #d0d0d0; border-radius: 4px; min-height: 80px; font-family: inherit;"></textarea>
            </div>
        </div>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: seccionData ? 'Actualizar' : 'Guardar',
        cancelButtonText: 'Cancelar',
        didOpen: () => {
            const opcionesContainer = document.getElementById('opcionesModal');
            if (opcionesContainer && opcionesDisponibles) {
                opcionesContainer.innerHTML = '';
                opcionesDisponibles.forEach(opcion => {
                    const label = document.createElement('label');
                    label.style.cssText = 'display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem 1rem; background: #e8f4f8; border-radius: 6px; font-size: 0.9rem;';
                    label.innerHTML = `<input type="checkbox" value="${opcion}" style="width: 16px; height: 16px;"> ${opcion}`;
                    opcionesContainer.appendChild(label);
                });
            }
        }
    });
};

/**
 * Cierra el modal de sección
 */
window.cerrarModalSeccionTab = function() {
    // Swal ya cierra el modal automáticamente
};

// ============================================================
// CONFIRMACIONES Y ALERTAS
// ============================================================

/**
 * Modal de confirmación para eliminar talla
 * @param {number} prendaIndex - Índice de la prenda
 * @param {string} talla - Nombre de la talla
 */
window.modalConfirmarEliminarTalla = function(prendaIndex, talla) {
    return Swal.fire({
        title: 'Eliminar talla',
        text: `¿Estás seguro de que quieres eliminar la talla ${talla}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });
};

/**
 * Modal de confirmación para agregar talla
 * @param {number} prendaIndex - Índice de la prenda
 * @param {Array} tallasDisponibles - Tallas que se pueden agregar
 */
window.modalAgregarTalla = function(prendaIndex, tallasDisponibles) {
    return Swal.fire({
        title: 'Agregar Talla',
        html: `
            <div style="text-align: left;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.75rem; color: #1f2937;">Selecciona una talla para agregar:</label>
                <select id="selector_talla_agregar" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.95rem; font-family: inherit;">
                    <option value="">-- SELECCIONA UNA TALLA --</option>
                    ${tallasDisponibles.map(talla => `<option value="${talla}">${talla}</option>`).join('')}
                </select>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#4ade80',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Agregar',
        cancelButtonText: 'Cancelar',
        didOpen: () => {
            const selector = document.getElementById('selector_talla_agregar');
            if (selector) selector.focus();
        }
    });
};

/**
 * Modal de confirmación para eliminar imagen
 * @param {string} tipo - Tipo de imagen (prenda, tela, logo, reflectivo)
 */
window.modalConfirmarEliminarImagen = function(tipo = 'imagen') {
    return Swal.fire({
        title: 'Eliminar imagen',
        text: `¿Estás seguro de que quieres eliminar esta ${tipo}? No se guardará en el pedido.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });
};

/**
 * Modal de confirmación para eliminar talla del reflectivo
 * @param {string} talla - Nombre de la talla
 */
window.modalConfirmarEliminarTallaReflectivo = function(talla) {
    return Swal.fire({
        title: 'Eliminar talla',
        text: `¿Estás seguro de que quieres eliminar la talla ${talla}? No se incluirá en el pedido.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });
};

/**
 * Muestra alerta de información
 * @param {string} titulo - Título del modal
 * @param {string} mensaje - Mensaje a mostrar
 */
window.modalInfo = function(titulo, mensaje) {
    return Swal.fire({
        icon: 'info',
        title: titulo,
        text: mensaje,
        confirmButtonText: 'OK'
    });
};

/**
 * Muestra alerta de éxito
 * @param {string} titulo - Título del modal
 * @param {string} mensaje - Mensaje a mostrar
 * @param {number} timer - Tiempo en ms antes de cerrar (opcional)
 */
window.modalExito = function(titulo, mensaje, timer = 2000) {
    return Swal.fire({
        icon: 'success',
        title: titulo,
        text: mensaje,
        timer: timer,
        showConfirmButton: timer ? false : true,
        confirmButtonText: 'OK'
    });
};

/**
 * Muestra alerta de error
 * @param {string} titulo - Título del modal
 * @param {string} mensaje - Mensaje a mostrar
 */
window.modalError = function(titulo, mensaje) {
    return Swal.fire({
        icon: 'error',
        title: titulo,
        text: mensaje,
        confirmButtonText: 'OK'
    });
};
