/**
 * ================================================
 * TELAS MODULE - GESTIÓN DE TELAS
 * ================================================
 * 
 * Funciones CRUD para manejo de telas
 * Agregar, eliminar, actualizar y validar telas
 * 
 * @module TelasModule
 * @version 2.0.0
 */

/**
 * Agregar nueva tela con validación
 * @returns {Promise<boolean>} Resultado de la operación
 */
window.agregarTelaNueva = async function() {
    console.log('═════════════════════════════════════════════════════════════════');
    console.log('[agregarTelaNueva] 🟦 CLICK DETECTADO EN BOTÓN AGREGAR TELA');
    console.log('═════════════════════════════════════════════════════════════════');
    
    try {
        console.log('[agregarTelaNueva] ✅ ENTRADA: Iniciando agregación de nueva tela');
        
        // DIAGNÓSTICO 1: Estado INICIAL de telasCreacion
        console.log('[agregarTelaNueva] 📊 DIAGNÓSTICO 1 - Estado INICIAL:');
        console.log('  window.telasCreacion:', window.telasCreacion);
        console.log('  Cantidad de telas:', window.telasCreacion?.length || 0);
        if (window.telasCreacion && window.telasCreacion.length > 0) {
            console.log('  Telas en array:');
            window.telasCreacion.forEach((t, idx) => {
                console.log(`    [${idx}] Color: ${t.color}, Tela: ${t.tela}, Ref: ${t.referencia}`);
            });
        }
        
        // Obtener elementos del DOM
        console.log('[agregarTelaNueva] 🔍 Buscando elementos en el DOM...');
        const colorElement = document.getElementById('nueva-prenda-color');
        const telaElement = document.getElementById('nueva-prenda-tela');
        const referenciaElement = document.getElementById('nueva-prenda-referencia');
        
        // Verificar que los elementos existan
        if (!colorElement || !telaElement || !referenciaElement) {
            console.error('[agregarTelaNueva] ❌ Elementos del modal no encontrados. Verifica que el modal esté abierto.');
            window.mostrarErrorTela('nueva-prenda-tela', 'Error: Modal no está activo');
            return false;
        }
        console.log('[agregarTelaNueva] ✓ Elementos del modal encontrados');
        
        // Obtener valores de los campos
        const color = colorElement.value.trim().toUpperCase();
        const tela = telaElement.value.trim();
        const referencia = referenciaElement.value.trim();
        
        console.log('[agregarTelaNueva] 📝 VALORES CAPTURADOS:', { color, tela, referencia });
        
        // Validar campos
        console.log('[agregarTelaNueva] 🔐 Validando campos...');
        const validacion = window.validarCamposTela(color, tela, referencia);
        
        if (!validacion.valido) {
            console.warn('[agregarTelaNueva] ❌ Validación fallida:', validacion.errores);
            
            // Mostrar errores
            validacion.errores.forEach(error => {
                window.mostrarErrorTela(error.campo, error.mensaje);
            });
            
            return false;
        }
        console.log('[agregarTelaNueva] ✓ Validación exitosa');
        
        // Verificar si la tela ya existe
        console.log('[agregarTelaNueva] 🔎 Verificando duplicados...');
        const telaExistente = window.telasCreacion.find(t => 
            t.color.toUpperCase() === color.toUpperCase() && 
            t.tela.toUpperCase() === tela.toUpperCase()
        );
        
        if (telaExistente) {
            console.warn('[agregarTelaNueva] ⚠️  Tela ya existe:', { color, tela });
            window.mostrarErrorTela('nueva-prenda-tela', 'Esta tela ya está agregada');
            return false;
        }
        console.log('[agregarTelaNueva] ✓ No hay duplicados');
        
        // Crear objeto de tela
        console.log('[agregarTelaNueva] 🖼️  Obteniendo imágenes temporales...');
        const imagenesActuales = window.imagenesTelaModalNueva || [];
        
        // Debug: Verificar estado del array antes de guardar
        console.log('[agregarTelaNueva] 📸 DIAGNÓSTICO 2 - Imágenes temporales:');
        console.log('  window.imagenesTelaModalNueva definido:', !!window.imagenesTelaModalNueva);
        console.log('  Cantidad de imágenes:', window.imagenesTelaModalNueva?.length || 0);
        console.log('  Imágenes:', window.imagenesTelaModalNueva?.map(img => ({ name: img.name, size: img.size })) || []);
        
        const nuevaTela = {
            color: color,
            tela: tela,
            referencia: referencia,
            imagenes: [...imagenesActuales], // Copiar las imágenes actuales
            fechaCreacion: new Date().toISOString()
        };
        
        console.log('[agregarTelaNueva] 🆕 OBJETO TELA CREADO:', nuevaTela);
        console.log('[agregarTelaNueva] 📸 Imágenes a incluir:', imagenesActuales.length);
        
        // DIAGNÓSTICO 3: ANTES de hacer push
        console.log('[agregarTelaNueva] 📊 DIAGNÓSTICO 3 - ANTES de push:');
        console.log('  window.telasCreacion.length:', window.telasCreacion.length);
        console.log('  Contenido actual:', window.telasCreacion.map(t => `${t.color}/${t.tela}`));
        
        // Agregar al array
        console.log('[agregarTelaNueva] ➕ Haciendo PUSH a window.telasCreacion...');
        window.telasCreacion.push(nuevaTela);
        
        // DIAGNÓSTICO 4: DESPUÉS de hacer push
        console.log('[agregarTelaNueva] 📊 DIAGNÓSTICO 4 - DESPUÉS de push:');
        console.log('  window.telasCreacion.length:', window.telasCreacion.length);
        console.log('  Contenido actualizado:', window.telasCreacion.map(t => `${t.color}/${t.tela}`));
        window.telasCreacion.forEach((t, idx) => {
            console.log(`    [${idx}] ${t.color} - ${t.tela} - ${t.referencia}`);
        });
        
        // Limpiar campos
        console.log('[agregarTelaNueva] 🧹 Limpiando campos del modal...');
        document.getElementById('nueva-prenda-color').value = '';
        document.getElementById('nueva-prenda-tela').value = '';
        document.getElementById('nueva-prenda-referencia').value = '';
        console.log('[agregarTelaNueva] ✓ Campos limpiados');
        
        // Limpiar preview de imágenes (de forma segura)
        try {
            const previewDiv = document.getElementById('nueva-prenda-tela-preview');
            if (previewDiv) {
                previewDiv.innerHTML = '';
                previewDiv.style.display = 'none';
                console.log('[agregarTelaNueva] ✓ Preview de imágenes limpiado');
            }
        } catch (e) {
            console.warn('[agregarTelaNueva] ⚠️  Error al limpiar preview:', e);
        }
        
        // Limpiar errores
        console.log('[agregarTelaNueva] 🧹 Limpiando errores...');
        window.limpiarTodosLosErroresTela();
        console.log('[agregarTelaNueva] ✓ Errores limpiados');
        
        // Limpiar imágenes temporales DESPUÉS de guardarlas en la tela
        console.log('[agregarTelaNueva] 🧹 Limpiando imágenes temporales...');
        window.imagenesTelaModalNueva = [];
        console.log('[agregarTelaNueva] ✓ Imágenes temporales limpiadas después de guardar en tela');
        
        // DIAGNÓSTICO 5: ANTES de actualizar tabla
        console.log('[agregarTelaNueva] 📊 DIAGNÓSTICO 5 - ANTES de actualizarTablaTelas():');
        console.log('  window.telasCreacion:', window.telasCreacion);
        console.log('  Cantidad total:', window.telasCreacion.length);
        
        // Actualizar tabla
        console.log('[agregarTelaNueva] 🔄 Llamando a window.actualizarTablaTelas()...');
        window.actualizarTablaTelas();
        console.log('[agregarTelaNueva] ✓ Tabla actualizada');
        
        // DIAGNÓSTICO 6: DESPUÉS de actualizar tabla
        console.log('[agregarTelaNueva] 📊 DIAGNÓSTICO 6 - DESPUÉS de actualizarTablaTelas():');
        console.log('  window.telasCreacion:', window.telasCreacion);
        console.log('  Cantidad total:', window.telasCreacion.length);
        
        console.log('[agregarTelaNueva] ✅ Tela agregada exitosamente con ' + imagenesActuales.length + ' imágenes');
        console.log('═════════════════════════════════════════════════════════════════');
        return true;
        
    } catch (error) {
        console.error('[agregarTelaNueva]  Error al agregar tela:', error);
        window.mostrarErrorTela('nueva-prenda-tela', 'Error al agregar la tela');
        return false;
    }
};

/**
 * Función global única para confirmar eliminación de tela
 */
window.confirmarEliminacionTela = function(index) {
    console.log('[confirmarEliminacionTela] 🗑️ Confirmando eliminación de tela en índice:', index);
    
    try {
        const telas = window.telasCreacion;
        if (!telas || index < 0 || index >= telas.length) {
            console.warn('[confirmarEliminacionTela]  Índice inválido en confirmación:', index);
            return;
        }
        
        const telaAEliminar = telas[index];
        console.log('[confirmarEliminacionTela]  Tela a eliminar definitivamente:', telaAEliminar);
        
        // Eliminar del array principal
        window.telasCreacion.splice(index, 1);
        
        // SINCRONIZAR: Si telasAgregadas existe, eliminarlo de ahí también
        // (prenda-form-collector.js lo prioriza sobre telasCreacion si existe con datos)
        if (window.telasAgregadas && Array.isArray(window.telasAgregadas) && window.telasAgregadas.length > index) {
            console.log('[confirmarEliminacionTela]  Sincronizando eliminación en telasAgregadas');
            window.telasAgregadas.splice(index, 1);
        }
        
        // Actualizar tabla
        window.actualizarTablaTelas();
        
        // Actualizar contador
        window.actualizarContadorTelas();
        
        console.log('[confirmarEliminacionTela]  Tela eliminada exitosamente. Quedan:', window.telasCreacion.length);
        
    } catch (error) {
        console.error('[confirmarEliminacionTela]  Error al eliminar tela:', error);
    }
};

/**
 * Eliminar tela con confirmación
 * @param {number} index - Índice de la tela a eliminar
 * @param {Event} event - Evento del click (opcional)
 */
window.eliminarTela = function(index, event) {
    console.log('[eliminarTela] 🗑️ Iniciando eliminación de tela:', index);
    
    // Prevenir propagación de eventos para evitar clicks accidentales
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    try {
        const telas = window.telasCreacion;
        if (!telas || index < 0 || index >= telas.length) {
            console.warn('[eliminarTela]  Índice inválido:', index);
            return;
        }
        
        const telaAEliminar = telas[index];
        console.log('[eliminarTela]  Tela a eliminar:', telaAEliminar);
        
        // Cerrar cualquier modal existente primero
        const modalExistente = document.querySelector('.modal-confirmacion');
        if (modalExistente) {
            modalExistente.remove();
        }
        
        // Crear modal de confirmación
        const modal = document.createElement('div');
        modal.className = 'modal-confirmacion';
        modal.style.cssText = `
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center;
            z-index: 1060000;
        `;
        
        const contenido = document.createElement('div');
        contenido.style.cssText = `
            background: white; border-radius: 12px; padding: 2rem; max-width: 400px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.3); text-align: center;
        `;
        
        contenido.innerHTML = `
            <h3 style="margin: 0 0 1rem 0; color: #1f2937;">¿Eliminar Tela?</h3>
            <p style="margin: 0 0 1.5rem 0; color: #6b7280; line-height: 1.5;">
                ¿Estás seguro de que deseas eliminar esta tela?
                <br><strong>Color:</strong> ${telaAEliminar.color}
                <br><strong>Tela:</strong> ${telaAEliminar.tela}
                <br><strong>Referencia:</strong> ${telaAEliminar.referencia || 'N/A'}
            </p>
            <div style="display: flex; gap: 1rem; justify-content: center;">
                <button type="button" id="btn-cancelar-eliminar" style="background: #e5e7eb; color: #1f2937; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer;">Cancelar</button>
                <button type="button" id="btn-confirmar-eliminar" style="background: #ef4444; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer;">Eliminar</button>
            </div>
        `;
        
        modal.appendChild(contenido);
        document.body.appendChild(modal);
        
        // Configurar eventos de los botones
        const btnCancelar = document.getElementById('btn-cancelar-eliminar');
        const btnConfirmar = document.getElementById('btn-confirmar-eliminar');
        
        btnCancelar.addEventListener('click', () => {
            console.log('[eliminarTela]  Cancelado por usuario');
            modal.remove();
        });
        
        btnConfirmar.addEventListener('click', () => {
            console.log('[eliminarTela]  Confirmado por usuario');
            window.confirmarEliminacionTela(index);
            modal.remove();
        });
        
        // Cerrar al hacer click fuera
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                console.log('[eliminarTela]  Cerrado al hacer click fuera');
                modal.remove();
            }
        });
        
        // Cerrar con Escape
        const cerrarConEsc = (e) => {
            if (e.key === 'Escape') {
                console.log('[eliminarTela]  Cerrado con Escape');
                modal.remove();
                document.removeEventListener('keydown', cerrarConEsc);
            }
        };
        document.addEventListener('keydown', cerrarConEsc);
        
        console.log('[eliminarTela]  Modal de confirmación creado');
        
    } catch (error) {
        console.error('[eliminarTela]  Error al eliminar tela:', error);
    }
};

/**
 * Actualizar datos de una tela existente
 * @param {number} index - Índice de la tela a actualizar
 * @param {Object} nuevosDatos - Nuevos datos de la tela
 * @returns {boolean} Resultado de la operación
 */
window.actualizarTela = function(index, nuevosDatos) {
    console.log('[actualizarTela]  Actualizando tela:', index);
    
    try {
        const telas = window.telasCreacion;
        if (!telas || index < 0 || index >= telas.length) {
            console.warn('[actualizarTela]  Índice inválido:', index);
            return false;
        }
        
        const telaActual = telas[index];
        console.log('[actualizarTela]  Tela actual:', telaActual);
        console.log('[actualizarTela]  Nuevos datos:', nuevosDatos);
        
        // Actualizar datos
        Object.assign(telaActual, nuevosDatos);
        telaActual.fechaModificacion = new Date().toISOString();
        
        console.log('[actualizarTela]  Tela actualizada exitosamente');
        return true;
        
    } catch (error) {
        console.error('[actualizarTela]  Error al actualizar tela:', error);
        return false;
    }
};

/**
 * Obtener tela por índice
 * @param {number} index - Índice de la tela
 * @returns {Object|null} Tela encontrada o null
 */
window.obtenerTelaPorIndice = function(index) {
    const telas = window.telasCreacion;
    if (!telas || index < 0 || index >= telas.length) {
        return null;
    }
    return telas[index];
};

/**
 * Buscar telas por criterios
 * @param {Object} criterios - Criterios de búsqueda
 * @returns {Array} Telas que coinciden con los criterios
 */
window.buscarTelas = function(criterios) {
    const telas = window.telasCreacion || [];
    
    return telas.filter(tela => {
        // Buscar por color
        if (criterios.color && !tela.color.toLowerCase().includes(criterios.color.toLowerCase())) {
            return false;
        }
        
        // Buscar por nombre de tela
        if (criterios.tela && !tela.tela.toLowerCase().includes(criterios.tela.toLowerCase())) {
            return false;
        }
        
        // Buscar por referencia
        if (criterios.referencia && !tela.referencia.toLowerCase().includes(criterios.referencia.toLowerCase())) {
            return false;
        }
        
        return true;
    });
};

/**
 * Verificar si existe una tela
 * @param {string} color - Color de la tela
 * @param {string} tela - Nombre de la tela
 * @returns {boolean} True si existe
 */
window.existeTela = function(color, tela) {
    return window.buscarTelas({ color, tela }).length > 0;
};
