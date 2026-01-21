// =========================================================
// PASO 4: REFLECTIVO - GESTIÓN DE PRENDAS
// =========================================================
// ARCHIVO: paso-cuatro-cotizacion-combinada.js
// DESCRIPCIÓN: Gestión completa del Paso 4 (Reflectivo) 
// en el flujo de cotización combinada
// =========================================================

let prendas_reflectivo_paso4 = [];

function agregarPrendaReflectivoPaso4() {
    console.log('🔸 agregarPrendaReflectivoPaso4() - Iniciando...');
    
    const container = document.getElementById('prendas_reflectivo_container');
    if (!container) {
        console.error(' Container prendas_reflectivo_container no encontrado');
        return;
    }
    
    const numeroPrenda = prendas_reflectivo_paso4.length + 1;
    const prendasIndex = numeroPrenda - 1;
    
    // Obtener prendas del PASO 2 - EXACTAMENTE COMO EN PASO 3
    console.log('📥 Llamando obtenerPrendasDelPaso2()...');
    const prendasPaso2 = obtenerPrendasDelPaso2();
    console.log(` Prendas obtenidas: ${prendasPaso2.length}`);
    if (prendasPaso2.length > 0) {
        console.log(' Prendas del Paso 2:', prendasPaso2);
    }
    
    const fila = document.createElement('div');
    fila.className = 'prenda-reflectivo-item';
    fila.setAttribute('data-prenda-index', prendasIndex);
    fila.style.cssText = 'margin-bottom: 12px; padding: 12px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;';
    fila.innerHTML = `
        <div style="display: grid; gap: 10px;">
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #eee; font-weight: 600; color: #333; font-size: 0.9rem;">
                <span>🔸 Prenda Reflectivo <span class="numero-prenda">${numeroPrenda}</span></span>
                <button type="button" onclick="eliminarPrendaReflectivoPaso4(${prendasIndex});" style="background: none; color: #999; border: 1px solid #ddd; padding: 4px 8px; border-radius: 3px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;" onmouseover="this.style.background='#f0f0f0'; this.style.color='#333'" onmouseout="this.style.background='none'; this.style.color='#999'" title="Eliminar prenda">
                    ✕
                </button>
            </div>
            
            <!-- TIPO DE PRENDA - DROPDOWN CON PRENDAS DEL PASO 2 -->
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #333; font-size: 0.85rem;">👕 TIPO DE PRENDA</label>
                <select class="tipo-prenda-reflectivo select-prenda-paso2-reflectivo" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem; cursor: pointer;">
                    <option value="">-- Selecciona una prenda --</option>
                    ${prendasPaso2.map(prenda => {
                        let textoOpcion = prenda.nombre;
                        if (prenda.genero) {
                            textoOpcion += ' - ' + prenda.genero;
                        }
                        if (prenda.colores && prenda.colores.length > 0) {
                            textoOpcion += ' - Color: ' + prenda.colores.join(' - ');
                        }
                        const dataTallas = JSON.stringify(prenda.tallas).replace(/"/g, '&quot;');
                        const dataGenero = prenda.genero || '';
                        const dataColores = JSON.stringify(prenda.colores).replace(/"/g, '&quot;');
                        const dataImagenes = JSON.stringify(prenda.imagenes || []).replace(/"/g, '&quot;');
                        const dataVariaciones = JSON.stringify(prenda.variaciones || {}).replace(/"/g, '&quot;');
                        return `<option value="${prenda.nombre}" data-tallas="${dataTallas}" data-genero="${dataGenero}" data-colores="${dataColores}" data-imagenes="${dataImagenes}" data-variaciones="${dataVariaciones}">${textoOpcion}</option>`;
                    }).join('')}
                </select>
                <small style="color: #999; display: block; margin-top: 4px; font-size: 0.8rem;">Las prendas se cargan del PASO 2</small>
            </div>
            
            <!-- DESCRIPCIÓN -->
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #333; font-size: 0.85rem;">📝 DESCRIPCIÓN</label>
                <textarea class="descripcion-reflectivo" rows="2" placeholder="Describe el reflectivo para esta prenda (tipo, tamaño, color, ubicación, etc.)..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; resize: vertical; font-size: 0.9rem;"></textarea>
            </div>
            
            <!-- IMÁGENES (MÁXIMO 3) -->
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #333; font-size: 0.85rem;">📸 IMÁGENES (MÁXIMO 3)</label>
                <div class="imagenes-dropzone-reflectivo-${prendasIndex}" style="border: 2px dashed #3498db; border-radius: 6px; padding: 20px; text-align: center; background: #f0f7ff; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#dbeafe'; this.style.borderColor='#3498db';" onmouseout="this.style.background='#f0f7ff'; this.style.borderColor='#3498db';">
                    <div style="margin-bottom: 8px; font-size: 1.5rem;"></div>
                    <p style="margin: 0 0 4px 0; font-weight: 500; color: #333; font-size: 0.9rem;">Arrastra imágenes aquí</p>
                    <p style="margin: 0; font-size: 0.8rem; color: #999;">O haz clic para seleccionar (máx. 3)</p>
                    <input type="file" class="imagen-reflectivo-input-${prendasIndex}" accept="image/*" multiple style="display: none;" />
                </div>
                <div class="imagenes-preview-reflectivo-${prendasIndex}" style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px;">
                    <!-- Previsualizaciones aquí -->
                </div>
            </div>
            
            <!-- TALLAS A COTIZAR - TABLA DINÁMICA -->
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #333; font-size: 0.85rem;">📏 TALLAS A COTIZAR</label>
                <table style="width: 100%; border-collapse: collapse; background: white; border: 1px solid #ddd; border-radius: 4px; overflow: hidden; font-size: 0.85rem; margin-bottom: 8px;">
                    <thead>
                        <tr style="background: #f3f4f6; border-bottom: 1px solid #ddd;">
                            <th style="padding: 8px; text-align: left; font-weight: 600; color: #333; border-right: 1px solid #ddd;">Talla</th>
                            <th style="padding: 8px; text-align: left; font-weight: 600; color: #333; border-right: 1px solid #ddd;">Cantidad</th>
                            <th style="padding: 8px; text-align: center; font-weight: 600; color: #333; width: 40px;">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="tallas-reflectivo-tabla-body">
                        <!-- Filas de talla-cantidad aquí -->
                    </tbody>
                </table>
            </div>
            
            <!-- TABLA DE VARIACIONES -->
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #333; font-size: 0.85rem;">Variaciones</label>
                <table style="width: 100%; border-collapse: collapse; background: white; border: 1px solid #ddd; border-radius: 4px; overflow: hidden; font-size: 0.85rem;">
                    <thead>
                        <tr style="background: #f3f4f6; border-bottom: 1px solid #ddd;">
                            <th style="padding: 8px; text-align: left; font-weight: 600; color: #333; border-right: 1px solid #ddd;">Tipo</th>
                            <th style="padding: 8px; text-align: left; font-weight: 600; color: #333; border-right: 1px solid #ddd;">Valor</th>
                            <th style="padding: 8px; text-align: left; font-weight: 600; color: #333;">Observación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 8px; color: #333; font-weight: 500; border-right: 1px solid #ddd;">Manga</td>
                            <td style="padding: 8px; border-right: 1px solid #ddd;"><input type="text" class="variacion-manga-valor-reflectivo" placeholder="Corta, Larga..." style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;"></td>
                            <td style="padding: 8px;"><input type="text" class="variacion-manga-obs-reflectivo" placeholder="Observación..." style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;"></td>
                        </tr>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 8px; color: #333; font-weight: 500; border-right: 1px solid #ddd;">Bolsillos</td>
                            <td style="padding: 8px; border-right: 1px solid #ddd;"><input type="text" class="variacion-bolsillos-valor-reflectivo" placeholder="Sí, No..." style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;"></td>
                            <td style="padding: 8px;"><input type="text" class="variacion-bolsillos-obs-reflectivo" placeholder="Observación..." style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;"></td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; color: #333; font-weight: 500; border-right: 1px solid #ddd;">Broche/Botón</td>
                            <td style="padding: 8px; border-right: 1px solid #ddd;"><input type="text" class="variacion-broche-valor-reflectivo" placeholder="Automático, Metal..." style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;"></td>
                            <td style="padding: 8px;"><input type="text" class="variacion-broche-obs-reflectivo" placeholder="Observación..." style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- UBICACIÓN CON MODAL -->
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 10px; color: #333; font-size: 0.95rem;">📍 UBICACIONES</label>
                <div style="margin-bottom: 12px; display: flex; gap: 6px;">
                    <input type="text" class="seccion-ubicacion-reflectivo-input" list="opciones_seccion_reflectivo" placeholder="PECHO, ESPALDA, MANGA..." style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; text-transform: uppercase;">
                    <button type="button" class="btn-agregar-ubicacion-modal-reflectivo" style="background: #3498db; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-weight: 500; font-size: 0.9rem; white-space: nowrap;">
                        + Agregar
                    </button>
                </div>
                <datalist id="opciones_seccion_reflectivo">
                    <option value="PECHO">
                    <option value="ESPALDA">
                    <option value="MANGA">
                    <option value="CUELLO">
                    <option value="COSTADO">
                    <option value="MÚLTIPLE">
                </datalist>
                <div class="ubicaciones-reflectivo-agregadas" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 10px; min-height: 24px;"></div>
            </div>
            
            <!-- OBSERVACIONES GENERALES -->
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 10px; color: #333; font-size: 0.95rem;">💬 OBSERVACIONES GENERALES</label>
                <button type="button" class="btn-agregar-obs-reflectivo" style="background: #3498db; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-weight: 500; font-size: 0.9rem; margin-bottom: 10px; white-space: nowrap;">
                    + Agregar Observación
                </button>
                <div class="observaciones-reflectivo-lista" style="display: flex; flex-direction: column; gap: 8px;"></div>
            </div>
        </div>
    `;
    
    container.appendChild(fila);
    
    // LOG DE VALIDACIÓN
    const selectCreado = fila.querySelector('.select-prenda-paso2-reflectivo');
    const opcionesCreadas = selectCreado ? selectCreado.querySelectorAll('option').length : 0;
    console.log(` Prenda Reflectivo ${numeroPrenda} agregada. Select con ${opcionesCreadas} opciones (${opcionesCreadas - 1} prendas + 1 opción vacía)`);
    
    prendas_reflectivo_paso4.push({
        index: prendasIndex,
        tipo_prenda: '',
        descripcion: '',
        imagenes: [],
        tallas: [],
        ubicaciones: [],
        variaciones: {
            manga: { valor: '', observacion: '' },
            bolsillos: { valor: '', observacion: '' },
            broche_boton: { valor: '', observacion: '' }
        }
    });
    
    // LISTENER: Cuando se selecciona una prenda, cargar sus tallas, imágenes y variaciones
    const selectPrenda = fila.querySelector('.select-prenda-paso2-reflectivo');
    selectPrenda.addEventListener('change', (e) => {
        console.log(`🎯 SELECT PRENDA CAMBIÓ - Prenda ${prendasIndex}:`, e.target.value);
        
        const selectedOption = e.target.options[e.target.selectedIndex];
        const tallasJson = selectedOption.getAttribute('data-tallas');
        const imagenesJson = selectedOption.getAttribute('data-imagenes');
        const variacionesJson = selectedOption.getAttribute('data-variaciones');
        const nombrePrenda = selectPrenda.value.trim();
        
        console.log(`  - Nombre: ${nombrePrenda}`);
        console.log(`  - Tallas JSON: ${tallasJson}`);
        console.log(`  - Imágenes JSON: ${imagenesJson}`);
        console.log(`  - Variaciones JSON: ${variacionesJson}`);
        
        // Actualizar nombre en el objeto prenda
        const prenda = prendas_reflectivo_paso4.find(p => p.index === prendasIndex);
        if (prenda) {
            prenda.tipo_prenda = nombrePrenda;
        }
        
        // Limpiar tabla de tallas
        const tablaTallasBody = fila.querySelector('.tallas-reflectivo-tabla-body');
        tablaTallasBody.innerHTML = '';
        
        // Cargar tallas de la prenda seleccionada
        if (tallasJson) {
            try {
                const tallas = JSON.parse(tallasJson);
                console.log(`  - Tallas parseadas: ${JSON.stringify(tallas)}`);
                if (Array.isArray(tallas) && tallas.length > 0) {
                    tallas.forEach(talla => {
                        console.log(`    - Agregando fila para talla: ${talla}`);
                        agregarFilaTallaCantidadReflectivo(tablaTallasBody, prendasIndex, talla);
                    });
                }
            } catch (err) {
                console.error(' Error al parsear tallas:', err);
            }
        } else {
            console.warn('⚠️ No hay tallasJson en el atributo data-tallas');
        }
        
        // CARGAR VARIACIONES DE PRENDA DESDE PASO 2
        let variacionesPaso2 = {};
        try {
            if (variacionesJson) {
                variacionesPaso2 = JSON.parse(variacionesJson);
            }
        } catch (err) {
            console.error(' Error al parsear variaciones:', err);
            variacionesPaso2 = {};
        }
        
        // Cargar variaciones desde data-variaciones
        if (variacionesPaso2 && Object.keys(variacionesPaso2).length > 0) {
            // Buscar los campos de entrada en la fila
            const mangaValor = fila.querySelector('.variacion-manga-valor-reflectivo');
            const mangaObs = fila.querySelector('.variacion-manga-obs-reflectivo');
            const bolsillosValor = fila.querySelector('.variacion-bolsillos-valor-reflectivo');
            const bolsillosObs = fila.querySelector('.variacion-bolsillos-obs-reflectivo');
            const brocheValor = fila.querySelector('.variacion-broche-valor-reflectivo');
            const brocheObs = fila.querySelector('.variacion-broche-obs-reflectivo');
            
            // Cargar valores en los campos
            if (variacionesPaso2.manga) {
                if (mangaValor) mangaValor.value = variacionesPaso2.manga.valor || '';
                if (mangaObs) mangaObs.value = variacionesPaso2.manga.observacion || '';
            } else {
                if (mangaValor) mangaValor.value = '';
                if (mangaObs) mangaObs.value = '';
            }
            
            if (variacionesPaso2.bolsillos) {
                if (bolsillosValor) bolsillosValor.value = variacionesPaso2.bolsillos.valor || '';
                if (bolsillosObs) bolsillosObs.value = variacionesPaso2.bolsillos.observacion || '';
            } else {
                if (bolsillosValor) bolsillosValor.value = '';
                if (bolsillosObs) bolsillosObs.value = '';
            }
            
            if (variacionesPaso2.broche_boton) {
                if (brocheValor) brocheValor.value = variacionesPaso2.broche_boton.valor || '';
                if (brocheObs) brocheObs.value = variacionesPaso2.broche_boton.observacion || '';
            } else {
                if (brocheValor) brocheValor.value = '';
                if (brocheObs) brocheObs.value = '';
            }
        } else {
            // Si no hay variaciones, limpiar campos
            const mangaValor = fila.querySelector('.variacion-manga-valor-reflectivo');
            const mangaObs = fila.querySelector('.variacion-manga-obs-reflectivo');
            const bolsillosValor = fila.querySelector('.variacion-bolsillos-valor-reflectivo');
            const bolsillosObs = fila.querySelector('.variacion-bolsillos-obs-reflectivo');
            const brocheValor = fila.querySelector('.variacion-broche-valor-reflectivo');
            const brocheObs = fila.querySelector('.variacion-broche-obs-reflectivo');
            
            if (mangaValor) mangaValor.value = '';
            if (mangaObs) mangaObs.value = '';
            if (bolsillosValor) bolsillosValor.value = '';
            if (bolsillosObs) bolsillosObs.value = '';
            if (brocheValor) brocheValor.value = '';
            if (brocheObs) brocheObs.value = '';
        }
        
        // CARGAR IMÁGENES DE PRENDA DESDE PASO 2
        if (imagenesJson) {
            try {
                const imagenes = JSON.parse(imagenesJson);
                if (Array.isArray(imagenes) && imagenes.length > 0) {
                    const previewContainer = fila.querySelector(`.imagenes-preview-reflectivo-${prendasIndex}`);
                    if (previewContainer) {
                        console.log(`  - Imágenes parseadas: ${imagenes.length} encontradas`);
                        
                        // Guardar imágenes del PASO 2
                        fila.imagenesPaso2 = imagenes.slice(0, 3);
                        
                        // Mostrar las imágenes en la previsualización
                        previewContainer.innerHTML = '';
                        
                        fila.imagenesPaso2.forEach((imgSrc, imgIdx) => {
                            const div = document.createElement('div');
                            div.style.cssText = 'position: relative; width: 80px; height: 80px; border-radius: 6px; overflow: hidden; border: 1px solid #ddd;';
                            div.innerHTML = `
                                <img src="${imgSrc}" style="width: 100%; height: 100%; object-fit: cover;">
                                <span style="position: absolute; top: 2px; right: 2px; background: rgba(76, 175, 80, 0.9); color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: default; font-size: 0.75rem; padding: 0; display: flex; align-items: center; justify-content: center; font-weight: bold;">✓</span>
                            `;
                            previewContainer.appendChild(div);
                        });
                    }
                }
            } catch (err) {
                console.error(' Error al parsear imágenes:', err);
            }
        } else {
            console.warn('⚠️ No hay imagenesJson en el atributo data-imagenes');
        }
    });
    
    // Setup para agregar ubicación CON MODAL (como en reflectivo.js)
    const btnAgregarUbicacionModal = fila.querySelector('.btn-agregar-ubicacion-modal-reflectivo');
    btnAgregarUbicacionModal.addEventListener('click', (e) => {
        e.preventDefault();
        abrirModalUbicacionReflectivoPaso4(prendasIndex, fila);
    });
    
    // Setup para agregar observación
    const btnAgregarObs = fila.querySelector('.btn-agregar-obs-reflectivo');
    btnAgregarObs.addEventListener('click', (e) => {
        e.preventDefault();
        agregarObservacionReflectivoPaso4(prendasIndex, fila);
    });
    
    // Setup drag and drop para imágenes
    const dropzone = fila.querySelector(`.imagenes-dropzone-reflectivo-${prendasIndex}`);
    const input = fila.querySelector(`.imagen-reflectivo-input-${prendasIndex}`);
    const previewContainer = fila.querySelector(`.imagenes-preview-reflectivo-${prendasIndex}`);
    
    dropzone.addEventListener('click', () => input.click());
    
    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.style.background = '#dbeafe';
        dropzone.style.borderColor = '#3498db';
    });
    
    dropzone.addEventListener('dragleave', () => {
        dropzone.style.background = '#f0f7ff';
        dropzone.style.borderColor = '#3498db';
    });
    
    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.style.background = '#f0f7ff';
        dropzone.style.borderColor = '#3498db';
        
        const archivos = Array.from(e.dataTransfer.files).filter(f => f.type.startsWith('image/'));
        procesarImagenesReflectivo(archivos, prendasIndex, fila, previewContainer);
    });
    
    input.addEventListener('change', (e) => {
        const archivos = Array.from(e.target.files || []).filter(f => f.type.startsWith('image/'));
        procesarImagenesReflectivo(archivos, prendasIndex, fila, previewContainer);
    });
    
    actualizarEstadoSinPrendas();
}

function agregarFilaTallaCantidadReflectivo(tablaTallasBody, prendasIndex, talla = '') {
    const fila = document.createElement('tr');
    fila.style.cssText = 'border-bottom: 1px solid #ddd;';
    
    const inputTalla = document.createElement('input');
    inputTalla.type = 'text';
    inputTalla.className = 'talla-input-reflectivo';
    inputTalla.value = talla;
    inputTalla.placeholder = 'Talla';
    inputTalla.style.cssText = 'width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem; text-transform: uppercase;';
    inputTalla.readOnly = talla !== '';
    
    const inputCantidad = document.createElement('input');
    inputCantidad.type = 'number';
    inputCantidad.className = 'cantidad-input-reflectivo';
    inputCantidad.placeholder = 'Cantidad';
    inputCantidad.min = '1';
    inputCantidad.style.cssText = 'width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem;';
    
    const btnEliminar = document.createElement('button');
    btnEliminar.type = 'button';
    btnEliminar.textContent = '✕';
    btnEliminar.style.cssText = 'background: none; color: #999; border: 1px solid #ddd; padding: 4px; border-radius: 3px; cursor: pointer; font-size: 0.85rem; width: 100%; transition: all 0.2s;';
    btnEliminar.onmouseover = function() { this.style.background = '#f0f0f0'; this.style.color = '#333'; };
    btnEliminar.onmouseout = function() { this.style.background = 'none'; this.style.color = '#999'; };
    btnEliminar.onclick = function() {
        fila.remove();
    };
    
    const td1 = document.createElement('td');
    td1.style.cssText = 'padding: 8px; border-right: 1px solid #ddd;';
    td1.appendChild(inputTalla);
    
    const td2 = document.createElement('td');
    td2.style.cssText = 'padding: 8px; border-right: 1px solid #ddd;';
    td2.appendChild(inputCantidad);
    
    const td3 = document.createElement('td');
    td3.style.cssText = 'padding: 8px; text-align: center;';
    td3.appendChild(btnEliminar);
    
    fila.appendChild(td1);
    fila.appendChild(td2);
    fila.appendChild(td3);
    
    tablaTallasBody.appendChild(fila);
}

function procesarImagenesReflectivo(archivos, prendasIndex, fila, previewContainer) {
    const prenda = prendas_reflectivo_paso4.find(p => p.index === prendasIndex);
    if (!prenda) return;
    
    const maxImagenes = 3;
    if (prenda.imagenes.length + archivos.length > maxImagenes) {
        Swal.fire('⚠️', `Máximo ${maxImagenes} imágenes permitidas`, 'warning');
        return;
    }
    
    archivos.forEach(archivo => {
        const reader = new FileReader();
        reader.onload = (e) => {
            prenda.imagenes.push({
                file: archivo,
                preview: e.target.result
            });
            
            renderizarImagenesReflectivo(prendasIndex, fila);
        };
        reader.readAsDataURL(archivo);
    });
}

function renderizarImagenesReflectivo(prendasIndex, fila) {
    const prenda = prendas_reflectivo_paso4.find(p => p.index === prendasIndex);
    if (!prenda) return;
    
    const previewContainer = fila.querySelector(`.imagenes-preview-reflectivo-${prendasIndex}`);
    previewContainer.innerHTML = '';
    
    // PRIMERO: Mostrar imágenes del PASO 2 (si existen)
    if (fila.imagenesPaso2 && Array.isArray(fila.imagenesPaso2) && fila.imagenesPaso2.length > 0) {
        fila.imagenesPaso2.forEach((imgSrc, imgIdx) => {
            const div = document.createElement('div');
            div.style.cssText = 'position: relative; width: 80px; height: 80px; border-radius: 6px; overflow: hidden; border: 1px solid #ddd;';
            div.innerHTML = `
                <img src="${imgSrc}" style="width: 100%; height: 100%; object-fit: cover;">
                <span style="position: absolute; top: 2px; right: 2px; background: rgba(76, 175, 80, 0.9); color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: default; font-size: 0.75rem; padding: 0; display: flex; align-items: center; justify-content: center; font-weight: bold;">✓</span>
            `;
            previewContainer.appendChild(div);
        });
    }
    
    // SEGUNDO: Mostrar imágenes agregadas por el usuario
    prenda.imagenes.forEach((img, idx) => {
        const div = document.createElement('div');
        div.style.cssText = 'position: relative; width: 80px; height: 80px; border-radius: 6px; overflow: hidden; border: 1px solid #ddd;';
        div.innerHTML = `
            <img src="${img.preview}" style="width: 100%; height: 100%; object-fit: cover;">
            <button type="button" onclick="eliminarImagenReflectivo(${prendasIndex}, ${idx})" style="position: absolute; top: 2px; right: 2px; background: rgba(244, 67, 54, 0.9); color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer; font-size: 0.85rem; padding: 0; display: flex; align-items: center; justify-content: center;">✕</button>
        `;
        previewContainer.appendChild(div);
    });
}

function eliminarImagenReflectivo(prendasIndex, imgIndex) {
    const prenda = prendas_reflectivo_paso4.find(p => p.index === prendasIndex);
    if (prenda) {
        prenda.imagenes.splice(imgIndex, 1);
        
        const fila = document.querySelector(`[data-prenda-index="${prendasIndex}"]`);
        if (fila) {
            renderizarImagenesReflectivo(prendasIndex, fila);
        }
    }
}

function eliminarPrendaReflectivoPaso4(prendasIndex) {
    prendas_reflectivo_paso4 = prendas_reflectivo_paso4.filter(p => p.index !== prendasIndex);
    
    const fila = document.querySelector(`[data-prenda-index="${prendasIndex}"]`);
    if (fila) fila.remove();
    
    // Renumerar
    document.querySelectorAll('.prenda-reflectivo-item .numero-prenda').forEach((el, idx) => {
        el.textContent = idx + 1;
    });
    
    actualizarEstadoSinPrendas();
}

function actualizarEstadoSinPrendas() {
    const container = document.getElementById('prendas_reflectivo_container');
    const msgSinPrendas = document.getElementById('sin_prendas_reflectivo');
    
    if (container && container.children.length > 0) {
        msgSinPrendas.style.display = 'none';
    } else {
        msgSinPrendas.style.display = 'block';
    }
}

function capturePrendasReflectivoPaso4() {
    const prendas = [];
    
    document.querySelectorAll('.prenda-reflectivo-item').forEach((fila, idx) => {
        const tipoPrenda = fila.querySelector('.tipo-prenda-reflectivo')?.value.trim() || '';
        const descripcion = fila.querySelector('.descripcion-reflectivo')?.value.trim() || '';
        
        // OBTENER DEL OBJETO PRENDA (no del DOM) - porque están en prendas_reflectivo_paso4
        const prendasData = prendas_reflectivo_paso4[idx];
        const ubicaciones = prendasData ? (prendasData.ubicaciones || []) : [];
        const observacionesGenerales = [];
        
        // Capturar observaciones generales
        fila.querySelectorAll('.observaciones-reflectivo-lista > div').forEach(filaObs => {
            const textoInput = filaObs.querySelector('.obs-reflectivo-texto');
            const checkboxInput = filaObs.querySelector('.obs-reflectivo-check');
            const valorInput = filaObs.querySelector('.obs-reflectivo-valor');
            
            if (textoInput && textoInput.value.trim()) {
                // Si checkbox está visible y marcado
                if (checkboxInput && checkboxInput.checked) {
                    observacionesGenerales.push({
                        tipo: 'checkbox',
                        texto: textoInput.value.trim(),
                        valor: true
                    });
                } 
                // Si text input está visible
                else if (valorInput && valorInput.style.display !== 'none') {
                    observacionesGenerales.push({
                        tipo: 'valor',
                        texto: textoInput.value.trim(),
                        valor: valorInput.value.trim()
                    });
                }
                // Si solo está el texto
                else {
                    observacionesGenerales.push({
                        texto: textoInput.value.trim(),
                        valor: true
                    });
                }
            }
        });
        
        // Capturar tallas con cantidades
        const tallas = [];
        fila.querySelectorAll('.tallas-reflectivo-tabla-body tr').forEach(tr => {
            const tallaInput = tr.querySelector('.talla-input-reflectivo');
            const cantidadInput = tr.querySelector('.cantidad-input-reflectivo');
            
            if (tallaInput && cantidadInput) {
                const talla = tallaInput.value.trim();
                const cantidad = cantidadInput.value.trim();
                
                if (talla) {
                    tallas.push({
                        talla: talla,
                        cantidad: cantidad ? parseInt(cantidad) : 0
                    });
                }
            }
        });
        
        // CAPTURAR VARIACIONES
        const variaciones = {
            manga: {
                valor: fila.querySelector('.variacion-manga-valor-reflectivo')?.value.trim() || '',
                observacion: fila.querySelector('.variacion-manga-obs-reflectivo')?.value.trim() || ''
            },
            bolsillos: {
                valor: fila.querySelector('.variacion-bolsillos-valor-reflectivo')?.value.trim() || '',
                observacion: fila.querySelector('.variacion-bolsillos-obs-reflectivo')?.value.trim() || ''
            },
            broche_boton: {
                valor: fila.querySelector('.variacion-broche-valor-reflectivo')?.value.trim() || '',
                observacion: fila.querySelector('.variacion-broche-obs-reflectivo')?.value.trim() || ''
            }
        };
        
        const prenda = prendas_reflectivo_paso4.find(p => p.index === idx);
        
        prendas.push({
            tipo_prenda: tipoPrenda,
            descripcion: descripcion,
            ubicaciones: ubicaciones.map(u => ({
                ubicacion: u.ubicacion,
                descripcion: u.descripcion
            })),
            tallas: tallas,
            variaciones: variaciones,
            observaciones_generales: observacionesGenerales,
            imagenes: prenda ? prenda.imagenes : []
        });
    });
    
    return prendas;
}

// Actualizar resumen de reflectivo en paso 5 (REVISAR)
function actualizarResumenReflectivoPaso4() {
    const prendasReflectivo = capturePrendasReflectivoPaso4();
    const container = document.getElementById('resumen_reflectivo_prendas');
    
    if (!container) return;
    
    container.innerHTML = '';
    
    if (prendasReflectivo.length === 0) {
        container.innerHTML = '<p style="color: #999; text-align: center; padding: 15px;">-</p>';
        return;
    }
    
    prendasReflectivo.forEach((prenda, idx) => {
        const div = document.createElement('div');
        div.style.cssText = 'padding: 12px; background: #fff; border: 1px solid #dbeafe; border-radius: 6px;';
        
        let html = `<p style="margin: 0 0 10px 0; font-weight: 600; color: #0066cc; border-bottom: 2px solid #3498db; padding-bottom: 8px;">Prenda ${idx + 1}: ${prenda.tipo_prenda || '-'}</p>`;
        
        html += `<p style="margin: 5px 0; font-size: 0.9rem;"><strong>Descripción:</strong> ${prenda.descripcion || '-'}</p>`;
        
        // Mostrar ubicaciones con descripción
        if (prenda.ubicaciones.length > 0) {
            html += `<div style="margin: 8px 0; font-size: 0.9rem;"><strong>Ubicaciones:</strong>`;
            prenda.ubicaciones.forEach(ub => {
                html += `<div style="margin-left: 12px; padding: 6px; background: #f0f7ff; border-left: 3px solid #3498db; border-radius: 3px; margin-top: 4px;">
                    <strong>${ub.ubicacion}:</strong> ${ub.descripcion}
                </div>`;
            });
            html += `</div>`;
        }
        
        // Mostrar tallas con cantidades
        if (prenda.tallas.length > 0) {
            html += `<p style="margin: 5px 0; font-size: 0.9rem;"><strong>Tallas:</strong> ${prenda.tallas.map(t => `${t.talla} (${t.cantidad})`).join(', ')}</p>`;
        }
        
        // Mostrar observaciones generales
        if (prenda.observaciones_generales && prenda.observaciones_generales.length > 0) {
            html += `<div style="margin: 8px 0; font-size: 0.9rem;"><strong>Observaciones:</strong>`;
            prenda.observaciones_generales.forEach(obs => {
                const texto = obs.texto || obs;
                const valor = obs.valor || '';
                html += `<div style="margin-left: 12px; padding: 4px; font-size: 0.85rem;">• ${texto}${valor && valor !== true ? ': ' + valor : ''}</div>`;
            });
            html += `</div>`;
        }
        
        if (prenda.imagenes.length > 0) {
            html += `<p style="margin: 5px 0 8px 0; font-size: 0.9rem;"><strong>Imágenes:</strong> ${prenda.imagenes.length}</p>`;
        }
        
        div.innerHTML = html;
        
        if (prenda.imagenes.length > 0) {
            const galeriaDiv = document.createElement('div');
            galeriaDiv.style.cssText = 'display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px;';
            prenda.imagenes.forEach(img => {
                const imgDiv = document.createElement('div');
                imgDiv.style.cssText = 'width: 60px; height: 60px; border-radius: 4px; overflow: hidden; border: 1px solid #ddd;';
                imgDiv.innerHTML = `<img src="${img.preview}" style="width: 100%; height: 100%; object-fit: cover;">`;
                galeriaDiv.appendChild(imgDiv);
            });
            div.appendChild(galeriaDiv);
        }
        
        container.appendChild(div);
    });
}

// =========================================================
// FUNCIONES DE UBICACIONES REFLECTIVO CON MODAL (Paso 4)
// =========================================================

/**
 * Abre modal para agregar ubicación con descripción
 */
function abrirModalUbicacionReflectivoPaso4(prendasIndex, fila) {
    const inputSeccion = fila.querySelector('.seccion-ubicacion-reflectivo-input');
    const seccion = inputSeccion.value.trim().toUpperCase();

    if (!seccion) {
        Swal.fire('⚠️', 'Por favor escribe una sección (ej: PECHO, ESPALDA, MANGA...)', 'warning');
        return;
    }

    // Crear modal
    let html = `
        <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999;" id="modalUbicacionReflectivoPaso4-${prendasIndex}">
            <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 500px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 style="margin: 0; color: #1e40af; font-size: 1.1rem;">${seccion}</h3>
                    <div style="display: flex; gap: 0.5rem;">
                        <button type="button" onclick="document.getElementById('modalUbicacionReflectivoPaso4-${prendasIndex}').remove()" style="background: #ef4444; color: white; border: none; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; font-size: 1.5rem; display: flex; align-items: center; justify-content: center;">×</button>
                        <button type="button" onclick="guardarUbicacionReflectivoPaso4(${prendasIndex})" style="background: #3498db; color: white; border: none; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; font-size: 1.5rem; display: flex; align-items: center; justify-content: center;">+</button>
                    </div>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 700; margin-bottom: 0.75rem; color: #333; font-size: 0.95rem;">OBSERVACIÓN/DETALLES:</label>
                    <textarea id="descUbicacionReflectivoPaso4-${prendasIndex}" placeholder="Ej: Franja vertical de 10cm, color plateado, lado izquierdo..." style="width: 100%; padding: 0.75rem; border: 2px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; resize: vertical; min-height: 120px; font-family: inherit; transition: all 0.2s ease;" onkeydown="if(event.key==='Escape') document.getElementById('modalUbicacionReflectivoPaso4-${prendasIndex}').remove()"></textarea>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', html);
    
    // Enfocar en el textarea
    setTimeout(() => {
        document.getElementById(`descUbicacionReflectivoPaso4-${prendasIndex}`).focus();
    }, 10);
}

/**
 * Guarda la ubicación y la descripción
 */
function guardarUbicacionReflectivoPaso4(prendasIndex) {
    const prenda = prendas_reflectivo_paso4.find(p => p.index === prendasIndex);
    if (!prenda) return;
    
    // Encontrar el fila (elemento DOM)
    const filaElement = document.querySelector(`[data-prenda-index="${prendasIndex}"]`);
    if (!filaElement) return;
    
    const inputSeccion = filaElement.querySelector('.seccion-ubicacion-reflectivo-input');
    const seccion = inputSeccion.value.trim().toUpperCase();
    const desc = document.getElementById(`descUbicacionReflectivoPaso4-${prendasIndex}`).value.trim();
    
    if (!desc) {
        Swal.fire('⚠️', 'Por favor escribe una descripción', 'warning');
        return;
    }
    
    // Agregar a array de ubicaciones
    if (!prenda.ubicaciones) {
        prenda.ubicaciones = [];
    }
    
    // Verificar que no exista duplicada
    const existe = prenda.ubicaciones.some(u => u.ubicacion === seccion && u.descripcion === desc);
    if (existe) {
        Swal.fire('⚠️', 'Esta ubicación ya fue agregada', 'warning');
        return;
    }
    
    prenda.ubicaciones.push({
        ubicacion: seccion,
        descripcion: desc
    });
    
    // Cerrar modal
    document.getElementById(`modalUbicacionReflectivoPaso4-${prendasIndex}`).remove();
    
    // Limpiar input
    inputSeccion.value = '';
    
    // Renderizar ubicaciones
    renderizarUbicacionesReflectivoPaso4(prendasIndex);
}

/**
 * Renderiza las ubicaciones agregadas
 */
function renderizarUbicacionesReflectivoPaso4(prendasIndex) {
    const prenda = prendas_reflectivo_paso4.find(p => p.index === prendasIndex);
    if (!prenda || !prenda.ubicaciones) return;
    
    const filaElement = document.querySelector(`[data-prenda-index="${prendasIndex}"]`);
    if (!filaElement) return;
    
    const contenedor = filaElement.querySelector('.ubicaciones-reflectivo-agregadas');
    if (!contenedor) return;
    
    contenedor.innerHTML = '';
    
    prenda.ubicaciones.forEach((item, index) => {
        const div = document.createElement('div');
        div.style.cssText = 'background: white; border: 2px solid #3498db; border-radius: 8px; padding: 1rem; margin-bottom: 0.5rem;';
        
        const ubicacionText = item.ubicacion || '';
        const descText = item.descripcion || '';
        
        div.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: start;">
                <div style="flex: 1;">
                    <h4 style="margin: 0 0 0.5rem 0; color: #1e40af; font-size: 0.95rem; font-weight: 700;">🎯 ${ubicacionText}</h4>
                    <p style="margin: 0; color: #666; font-size: 0.85rem; line-height: 1.4;">${descText}</p>
                </div>
                <button type="button" onclick="eliminarUbicacionReflectivoPaso4(${prendasIndex}, ${index})" style="background: #ef4444; color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-left: 10px;">×</button>
            </div>
        `;
        contenedor.appendChild(div);
    });
    
    console.log(`📍 Ubicaciones para prenda ${prendasIndex}: ${prenda.ubicaciones.length}`);
}

/**
 * Elimina una ubicación
 */
function eliminarUbicacionReflectivoPaso4(prendasIndex, index) {
    const prenda = prendas_reflectivo_paso4.find(p => p.index === prendasIndex);
    if (!prenda || !prenda.ubicaciones) return;
    
    prenda.ubicaciones.splice(index, 1);
    renderizarUbicacionesReflectivoPaso4(prendasIndex);
}

// =========================================================
// FUNCIONES DE OBSERVACIONES GENERALES (Paso 4)
// =========================================================

/**
 * Agrega fila de observación
 */
function agregarObservacionReflectivoPaso4(prendasIndex, fila) {
    const contenedor = fila.querySelector('.observaciones-reflectivo-lista');
    if (!contenedor) return;
    
    const filaObs = document.createElement('div');
    filaObs.style.cssText = 'display: flex; gap: 10px; align-items: center; padding: 10px; background: white; border-radius: 6px; border: 1px solid #ddd;';
    filaObs.innerHTML = `
        <input type="text" class="obs-reflectivo-texto" placeholder="Escribe una observación..." style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
        <div style="display: flex; gap: 5px; align-items: center; flex-shrink: 0;">
            <div class="obs-checkbox-mode" style="display: flex; align-items: center; gap: 5px;">
                <input type="checkbox" class="obs-reflectivo-check" style="width: 20px; height: 20px; cursor: pointer;">
            </div>
            <div class="obs-text-mode" style="display: none; flex: 1;">
                <input type="text" class="obs-reflectivo-valor" placeholder="Valor..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
            </div>
            <button type="button" class="obs-toggle-btn" style="background: #3498db; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: bold; flex-shrink: 0;">✓/✎</button>
        </div>
        <button type="button" style="background: #f44336; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 1rem; flex-shrink: 0;">✕</button>
    `;
    contenedor.appendChild(filaObs);
    
    const toggleBtn = filaObs.querySelector('.obs-toggle-btn');
    const checkboxMode = filaObs.querySelector('.obs-checkbox-mode');
    const textMode = filaObs.querySelector('.obs-text-mode');
    const deleteBtn = filaObs.querySelector('button:last-child');
    
    // Toggle entre checkbox y text
    toggleBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (checkboxMode.style.display === 'none') {
            checkboxMode.style.display = 'flex';
            textMode.style.display = 'none';
            toggleBtn.style.background = '#3498db';
        } else {
            checkboxMode.style.display = 'none';
            textMode.style.display = 'flex';
            toggleBtn.style.background = '#ff9800';
        }
    });
    
    // Eliminar fila
    deleteBtn.addEventListener('click', () => filaObs.remove());
}

// =========================================================
// INICIALIZACIÓN
// =========================================================

console.log('✅ PASO 4 (Reflectivo) - Módulo cargado correctamente');
