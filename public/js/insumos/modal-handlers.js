/**
 * MODAL HANDLERS - Insumos Materiales
 * Gestión centralizada de todos los modales
 */

/**
 * Abre el modal de Ancho y Metraje para una prenda específica
 * Detecta si es prenda combinada (múltiples colores) o normal
 */
function abrirModalAnchoMetraje(pedido, prendaId) {
    const modal = document.getElementById('modalAnchoMetraje');
    modal.style.display = 'flex';
    
    // Obtener el número de recibo
    fetch(`/insumos/materiales/${pedido}/obtener-recibo-prenda/${prendaId}`)
        .then(response => response.json())
        .then(data => {
            const reciboNum = data.success ? data.recibo_number : '-';
            document.getElementById('anchoMetrajeRecibo').textContent = reciboNum;
        })
        .catch(error => console.error('[abrirModalAnchoMetraje] Error:', error));
    
    // Guardar pedido y prenda en el modal para usarlos después
    modal.dataset.pedido = pedido;
    modal.dataset.prendaId = prendaId;

    // Limpiar inputs
    document.getElementById('anchoInput').value = '';
    document.getElementById('metrajeInput').value = '';
    document.getElementById('colorInputsContainer').innerHTML = '';
    document.getElementById('piezaInputsContainer').innerHTML = '';
    
    // Resetear selector de modo a normal
    document.querySelector('input[name="modoAnchoMetraje"][value="normal"]').checked = true;
    
    // Ocultar todo y mostrar cargando
    document.getElementById('modoSelector').classList.add('hidden');
    document.getElementById('normalView').classList.add('hidden');
    document.getElementById('colorView').classList.add('hidden');
    document.getElementById('piezaView').classList.add('hidden');
    document.getElementById('anchoMetrajeLoading').classList.remove('hidden');

    console.log('[abrirModalAnchoMetraje] Abriendo modal para pedido:', pedido, 'prenda:', prendaId);

    if (prendaId) {
        // Cargar datos de ancho y metraje
        fetch(`/insumos/materiales/${pedido}/obtener-ancho-metraje-prenda/${prendaId}`)
            .then(response => response.json())
            .then(datosData => {
                console.log('[abrirModalAnchoMetraje] Datos obtenidos:', datosData);

                // Detectar si existe modo ya guardado
                if (datosData.success && datosData.tipo_modo) {
                    modal.tipoModoGuardado = datosData.tipo_modo;
                    modal.tieneDatosGuardados = true;
                }

                // Cargar colores o piezas según el tipo de prenda
                return fetch(`/insumos/materiales/${pedido}/obtener-colores-prenda/${prendaId}`)
                    .then(response => response.json())
                    .then(coloresData => {
                        console.log('[abrirModalAnchoMetraje] Colores:', coloresData);

                        // Mostrar el selector de modo
                        document.getElementById('modoSelector').classList.remove('hidden');
                        document.getElementById('anchoMetrajeLoading').classList.add('hidden');

                        mostrarBotonesAnchoMetraje();

                        // Cargar la vista según el modo
                        if (datosData.success && datosData.tipo_modo === 'color') {
                            generarInputsPorColor(coloresData.data || [], datosData);
                            document.querySelector('input[name="modoAnchoMetraje"][value="color"]').checked = true;
                            cambiarModoAnchoMetraje();
                        } else if (datosData.success && datosData.tipo_modo === 'pieza') {
                            generarInputsPorTallaColor(coloresData.data || [], datosData);
                            document.querySelector('input[name="modoAnchoMetraje"][value="pieza"]').checked = true;
                            cambiarModoAnchoMetraje();
                        } else if (datosData.success && datosData.tipo_modo === 'mano') {
                            document.querySelector('input[name="modoAnchoMetraje"][value="mano"]').checked = true;
                            cambiarModoAnchoMetraje();
                            if (datosData.data) {
                                document.getElementById('manoAnchoMetrajeTextarea').value = datosData.data;
                            }
                        } else {
                            // Normal por defecto
                            generarInputsPorColor(coloresData.data || [], datosData);
                            document.querySelector('input[name="modoAnchoMetraje"][value="normal"]').checked = true;
                            cambiarModoAnchoMetraje();
                        }
                    });
            })
            .catch(error => {
                console.error('[abrirModalAnchoMetraje] Error cargando datos:', error);
                document.getElementById('anchoMetrajeLoading').classList.add('hidden');
                showToast('Error cargando datos del ancho/metraje', 'error');
            });
    }
}

/**
 * Cierra el modal de Ancho y Metraje
 */
function cerrarModalAnchoMetraje() {
    const modal = document.getElementById('modalAnchoMetraje');
    modal.style.display = 'none';
    
    // Limpiar los inputs
    document.getElementById('anchoInput').value = '';
    document.getElementById('metrajeInput').value = '';
    document.getElementById('colorInputsContainer').innerHTML = '';
    document.getElementById('piezaInputsContainer').innerHTML = '';
    document.getElementById('manoAnchoMetrajeTextarea').value = '';
}

/**
 * Guarda los valores de Ancho y Metraje (normal o por color)
 */
function guardarAnchoMetraje() {
    const modal = document.getElementById('modalAnchoMetraje');
    const prendaId = modal.dataset.prendaId;
    const pedido = modal.dataset.pedido;
    
    if (!prendaId) {
        showToast('Error: No se encontró el ID de la prenda', 'error');
        return;
    }
    
    // Obtener modo seleccionado del radio button
    const modoSeleccionado = document.querySelector('input[name="modoAnchoMetraje"]:checked').value;
    
    if (modoSeleccionado === 'normal') {
        const ancho = parseFloat(document.getElementById('anchoInput').value);
        const metraje = parseFloat(document.getElementById('metrajeInput').value);
        
        if (!ancho || !metraje || ancho <= 0 || metraje <= 0) {
            showToast('Por favor ingresa ancho y metraje válidos', 'warning');
            return;
        }

        const data = {
            modo: 'normal',
            tipo_modo: 'normal',
            ancho: ancho,
            metraje: metraje
        };

        fetch(`/insumos/materiales/${pedido}/guardar-ancho-metraje-prenda/${prendaId}`, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify(data),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Ancho y metraje guardados exitosamente', 'success');
                window.datosAnchoMetraje = { ancho, metraje };
                actualizarReciboConAnchoMetraje();
                cerrarModalAnchoMetraje();
            } else {
                showToast(data.message || 'Error al guardar', 'error');
            }
        })
        .catch(error => {
            console.error('[guardarAnchoMetraje] Error:', error);
            showToast('Error al guardar ancho y metraje: ' + error.message, 'error');
        });
    } 
    // Aquí irían los otros modos (color, pieza, mano)
    // Por ahora solo implementamos normal
}

/**
 * Abre el modal de insumos para una orden y prenda específica
 */
function abrirModalInsumos(pedido, prendaId) {
    const modal = document.getElementById('insumosModal');
    modal.style.display = 'flex';
    
    // Remover aria-hidden del contenido principal
    const mainContent = document.getElementById('mainContent');
    if (mainContent) {
        mainContent.removeAttribute('aria-hidden');
    }

    // Establecer el pedido y prenda
    document.getElementById('modalPedido').textContent = pedido;
    document.getElementById('modalPrendaId').value = prendaId || '';
    document.getElementById('modalPrendaNombre').textContent = prendaId ? `Cargando...` : 'General';

    // Construir URL con prenda_id si existe
    let url = `/insumos/api/materiales/${pedido}`;
    if (prendaId) {
        url += `?prenda_id=${prendaId}`;
    }

    // Cargar los insumos de la orden filtrados por prenda
    fetch(url)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalPrendaNombre').textContent = prendaId ? `#${prendaId}` : 'General';
            llenarTablaInsumos(data);
        })
        .catch(error => {
            console.error('[abrirModalInsumos] Error:', error);
            showToast('Error al cargar los insumos', 'error');
        });
}

/**
 * Cierra el modal de insumos
 */
function cerrarModalInsumos() {
    const modal = document.getElementById('insumosModal');
    modal.style.display = 'none';
    
    // Restaurar aria-hidden al contenido principal
    const mainContent = document.getElementById('mainContent');
    if (mainContent) {
        mainContent.setAttribute('aria-hidden', 'true');
    }
}

/**
 * Abre el modal de observaciones para un insumo
 */
function abrirModalObservaciones(materialId, nombreMaterial) {
    const modal = document.getElementById('observacionesModal');
    modal.style.display = 'flex';
    
    // Establecer el nombre del material
    document.getElementById('observacionesMaterial').textContent = nombreMaterial;
    
    // Guardar el materialId en un atributo data
    modal.setAttribute('data-material-id', materialId);
    
    // Extraer el pedido del materialId
    let pedido = '';
    
    if (materialId.includes('material_modal_')) {
        const partes = materialId.split('_');
        pedido = partes[2];
    } else if (materialId.includes('material_')) {
        const partes = materialId.split('_');
        pedido = partes[1];
    }
    
    // Guardar el pedido en un atributo data
    modal.setAttribute('data-pedido', pedido);
    
    // Obtener observaciones del input hidden
    const inputObservaciones = document.getElementById(`observaciones_${materialId}`);
    if (inputObservaciones) {
        document.getElementById('observacionesTexto').value = inputObservaciones.value;
    } else {
        document.getElementById('observacionesTexto').value = '';
    }
    
    // Enfocar el textarea
    document.getElementById('observacionesTexto').focus();
}

/**
 * Cierra el modal de observaciones
 */
function cerrarModalObservaciones() {
    const modal = document.getElementById('observacionesModal');
    modal.style.display = 'none';
    document.getElementById('observacionesTexto').value = '';
    modal.removeAttribute('data-material-id');
}

/**
 * Actualiza el recibo abierto con los datos de ancho y metraje
 */
function actualizarReciboConAnchoMetraje() {
    if (!window.datosAnchoMetraje || !window.receiptManager) {
        return;
    }
    
    const { ancho, metraje } = window.datosAnchoMetraje;
    
    // Buscar o crear el elemento para mostrar ancho y metraje
    let anchoMetrajeElement = document.getElementById('ancho-metraje-disponible');
    
    if (!anchoMetrajeElement) {
        // Crear el elemento si no existe
        const container = document.querySelector('.modal-content') || document.querySelector('.order-detail-modal-container');
        if (container) {
            anchoMetrajeElement = document.createElement('div');
            anchoMetrajeElement.id = 'ancho-metraje-disponible';
            anchoMetrajeElement.className = 'bg-blue-100 border-l-4 border-blue-600 p-4 my-4 rounded';
            container.insertBefore(anchoMetrajeElement, container.firstChild);
        } else {
            return;
        }
    }
    
    // Actualizar el contenido
    anchoMetrajeElement.innerHTML = `
        <p class="font-bold text-blue-900 mb-2">📏 ANCHO Y METRAJE DISPONIBLE</p>
        ANCHO DISPONIBLE: ${ancho.toFixed(2)} m<br/>
        METRAJE DISPONIBLE: ${metraje.toFixed(2)} m
    `;
    
    console.log('[actualizarReciboConAnchoMetraje] Recibo actualizado con ancho y metraje');
}

export {
    abrirModalAnchoMetraje,
    cerrarModalAnchoMetraje,
    guardarAnchoMetraje,
    abrirModalInsumos,
    cerrarModalInsumos,
    abrirModalObservaciones,
    cerrarModalObservaciones,
    actualizarReciboConAnchoMetraje
};
