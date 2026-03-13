/**
 * Modal Handlers for Insumos/Materiales Module
 * Handles opening/closing of all modals in the insumos interface
 */

// ===== MODAL: ANCHO METRAJE =====

function abrirModalAnchoMetraje(pedido, prendaId) {
    const modal = document.getElementById('modalAnchoMetraje');
    modal.style.display = 'flex';
    
    // Obtener el número de recibo
    fetch(`/insumos/materiales/${pedido}/obtener-recibo-prenda/${prendaId}`)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.recibo) {
                document.getElementById('anchoMetrajeRecibo').textContent = data.recibo;
            } else {
                document.getElementById('anchoMetrajeRecibo').textContent = '-';
            }
        })
        .catch(error => {
            console.error('Error al obtener recibo:', error);
            document.getElementById('anchoMetrajeRecibo').textContent = '-';
        });
    
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
        // Cargar colores y datos para rellenar los inputs según el modo seleccionado
        Promise.all([
            fetch(`/insumos/materiales/${pedido}/obtener-colores-prenda/${prendaId}`).then(r => r.json()),
            fetch(`/insumos/materiales/${pedido}/obtener-ancho-metraje-prenda/${prendaId}`).then(r => r.json())
        ])
        .then(([coloresData, datosData]) => {
            console.log('[abrirModalAnchoMetraje] Datos cargados:', { coloresData, datosData });
            
            const modoSelector = document.getElementById('modoSelector');
            const radioPieza = document.querySelector('input[name="modoAnchoMetraje"][value="pieza"]');
            const labelPieza = radioPieza?.closest('label');
            
            // Guardar datos para usar cuando el usuario cambie de modo
            modal.coloresData = coloresData;
            modal.datosData = datosData;
            
            // Guardar tipo_modo ya guardado en BD (si existe)
            const tipoModoGuardado = datosData.tipo_modo || null;
            modal.tipoModoGuardado = tipoModoGuardado;
            
            // Verificar si hay datos reales guardados (ancho o metrajes)
            const tieneDatosGuardados = (datosData.ancho !== null && datosData.ancho !== undefined) 
                || (datosData.data && datosData.data.length > 0);
            modal.tieneDatosGuardados = tieneDatosGuardados;
            
            console.log('[abrirModalAnchoMetraje] tipo_modo guardado:', tipoModoGuardado, 'tiene datos:', tieneDatosGuardados);
            
            // DETERMINAR SI MOSTRAR OPCIÓN "POR PIEZA"
            // Por Pieza = múltiples colores/telas (modo 'piezas') SIN datos en prenda_pedido_talla_colores
            const tieneMultiplesColores = coloresData.success && 
                                         coloresData.modo === 'piezas' && 
                                         coloresData.colores && 
                                         coloresData.colores.length > 1;
            
            const esCombinada = datosData.success && 
                               datosData.modo === 'talla-color';
            
            // SIEMPRE mostrar las 3 opciones
            console.log('[abrirModalAnchoMetraje] Mostrando siempre todas las 3 opciones de modo');
            
            // Pre-seleccionar: si hay tipo_modo guardado, usarlo; si no, inferir del tipo de prenda
            if (tipoModoGuardado && tieneDatosGuardados) {
                console.log('[abrirModalAnchoMetraje] Usando tipo_modo guardado:', tipoModoGuardado);
                document.querySelector(`input[name="modoAnchoMetraje"][value="${tipoModoGuardado}"]`).checked = true;
            } else if (esCombinada) {
                // Prenda combinada (talla-color) → seleccionar "Por Pieza"
                console.log('[abrirModalAnchoMetraje] Prenda combinada, pre-seleccionando modo pieza');
                document.querySelector('input[name="modoAnchoMetraje"][value="pieza"]').checked = true;
            } else if (tieneMultiplesColores) {
                // Prenda con múltiples colores → seleccionar "Por Color"
                console.log('[abrirModalAnchoMetraje] Prenda por color, pre-seleccionando modo color');
                document.querySelector('input[name="modoAnchoMetraje"][value="color"]').checked = true;
            } else {
                // Prenda normal (un solo color) → seleccionar "Normal"
                console.log('[abrirModalAnchoMetraje] Prenda normal, pre-seleccionando modo normal');
                document.querySelector('input[name="modoAnchoMetraje"][value="normal"]').checked = true;
            }
            
            // Ocultar loading y mostrar selector
            document.getElementById('anchoMetrajeLoading').classList.add('hidden');
            modoSelector.classList.remove('hidden');
            
            // Ejecutar cambio de modo inicial
            cambiarModoAnchoMetraje();
            
            // Mostrar/ocultar botón eliminar
            mostrarBotonesAnchoMetraje();
            
            // Agregar event listeners a los radio buttons para cambiar de vista dinámicamente
            document.querySelectorAll('input[name="modoAnchoMetraje"]').forEach(radio => {
                radio.addEventListener('change', cambiarModoAnchoMetraje);
            });
        })
        .catch(error => {
            console.error('[abrirModalAnchoMetraje] Error al cargar datos:', error);
            // Fallback: ocultar loading, mostrar selector y modo normal
            document.getElementById('anchoMetrajeLoading').classList.add('hidden');
            document.getElementById('modoSelector').classList.remove('hidden');
            document.querySelector('input[name="modoAnchoMetraje"][value="normal"]').checked = true;
            cambiarModoAnchoMetraje();
            
            // Mostrar/ocultar botón eliminar
            mostrarBotonesAnchoMetraje();
            
            // Agregar event listeners mismo en fallback
            document.querySelectorAll('input[name="modoAnchoMetraje"]').forEach(radio => {
                radio.addEventListener('change', cambiarModoAnchoMetraje);
            });
        });
    }
}

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

function mostrarBotonesAnchoMetraje() {
    const modal = document.getElementById('modalAnchoMetraje');
    const btnEliminar = document.getElementById('btnEliminarAnchoMetraje');
    
    if (modal.tieneDatosGuardados) {
        btnEliminar.classList.remove('hidden');
    } else {
        btnEliminar.classList.add('hidden');
    }
}

function abrirModalConfirmacionEliminar() {
    const modalConfirmacion = document.getElementById('modalConfirmacionEliminar');
    modalConfirmacion.classList.remove('hidden');
}

function cerrarModalConfirmacionEliminar() {
    const modalConfirmacion = document.getElementById('modalConfirmacionEliminar');
    modalConfirmacion.classList.add('hidden');
}

// ===== MODAL: INSUMOS =====

function abrirModalInsumos(pedido, prendaId) {
    // Mostrar el modal
    const modal = document.getElementById('insumosModal');
    modal.style.display = 'flex';
    
    // Remover aria-hidden del contenido principal para evitar conflictos
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
            // Actualizar nombre de prenda si viene en la respuesta
            if (data.nombre_prenda) {
                document.getElementById('modalPrendaNombre').textContent = data.nombre_prenda;
            } else if (prendaId) {
                document.getElementById('modalPrendaNombre').textContent = `Prenda #${prendaId}`;
            }
            llenarTablaInsumos(data.materiales || []);
        })
        .catch(error => {
            showToast('Error al cargar los insumos', 'error');
        });
}

function cerrarModalInsumos() {
    const modal = document.getElementById('insumosModal');
    modal.style.display = 'none';
    
    // Restaurar aria-hidden al contenido principal
    const mainContent = document.getElementById('mainContent');
    if (mainContent) {
        mainContent.setAttribute('aria-hidden', 'false');
    }
}

// ===== MODAL: OBSERVACIONES =====

function abrirModalObservaciones(materialId, nombreMaterial) {
    // Mostrar el modal
    const modal = document.getElementById('observacionesModal');
    modal.style.display = 'flex';
    
    // Establecer el nombre del material
    document.getElementById('observacionesMaterial').textContent = nombreMaterial;
    
    // Guardar el materialId en un atributo data para usarlo al guardar
    modal.setAttribute('data-material-id', materialId);
    
    // Extraer el pedido del materialId
    // Formato: material_modal_${pedido}_${index}_${sanitizedMaterial}
    // O: material_${PEDIDO}_INDEX_NOMBRE
    let pedido = '';
    
    if (materialId.includes('material_modal_')) {
        // Nuevo formato: material_modal_45454_0_Tela
        const partes = materialId.split('_');
        if (partes.length >= 3) {
            pedido = partes[2]; // Índice 2 es el número de pedido
        }
    } else if (materialId.includes('material_')) {
        // Antiguo formato
        const partes = materialId.split('_');
        if (partes.length >= 2) {
            pedido = partes[1];
        }
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

function cerrarModalObservaciones() {
    const modal = document.getElementById('observacionesModal');
    modal.style.display = 'none';
    document.getElementById('observacionesTexto').value = '';
    modal.removeAttribute('data-material-id');
}

// ===== MODAL: RECIBO =====

function abrirDetalleRecibo(pedidoId, prendaId, tipoRecibo) {
    // Convertir parámetros correctamente
    pedidoId = parseInt(pedidoId) || null;
    
    // Convertir la string 'null' a null real, o convertir a número si tiene valor
    if (prendaId === 'null' || prendaId === '' || !prendaId) {
        prendaId = null;
    } else {
        prendaId = parseInt(prendaId) || null;
    }
    
    // Si la función no está disponible, cargar el módulo dinámicamente
    if (typeof openOrderDetailModalWithProcess === 'function') {
        openOrderDetailModalWithProcess(pedidoId, prendaId, tipoRecibo);
    } else {
        console.warn('[modal-handlers-insumos] PedidosRecibosModule no disponible, cargando...');
        
        // Cargar el módulo dinámicamente
        const script = document.createElement('script');
        script.type = 'module';
        script.textContent = `
            import { PedidosRecibosModule } from '/js/modulos/pedidos-recibos/PedidosRecibosModule.js';
            
            const module = new PedidosRecibosModule();
            window.openOrderDetailModalWithProcess = (pedidoId, prendaId, tipoRecibo, prendaIndex = null) => {
                return module.abrirRecibo(pedidoId, prendaId, tipoRecibo, prendaIndex);
            };
            
            // Llamar la función después de cargar
            window.openOrderDetailModalWithProcess(${pedidoId}, ${prendaId}, '${tipoRecibo}');
        `;
        document.head.appendChild(script);
    }
}

// ===== MODAL: PASAR A REVISAR =====

function abrirModalPasarRevisar(reciboId, pedidoId) {
    const modal = document.getElementById('modalPasarRevisar');
    if (!modal) {
        console.error('Modal no encontrado');
        return;
    }
    
    // Actualizar datos en el modal
    document.getElementById('reciboIdPasarRevisar').value = reciboId;
    document.getElementById('pedidoIdPasarRevisar').value = pedidoId;
    document.getElementById('formPasarRevisar').reset();
    document.getElementById('contadorPasarRevisar').textContent = '0';
    
    // Mostrar modal
    modal.style.display = 'flex';
}

function cerrarModalPasarRevisar() {
    const modal = document.getElementById('modalPasarRevisar');
    if (modal) {
        modal.style.display = 'none';
    }
}

// ===== INIT EVENT LISTENERS =====

// Cerrar modales al hacer click fuera (delegación)
document.addEventListener('DOMContentLoaded', function() {
    const insumosModal = document.getElementById('insumosModal');
    if (insumosModal) {
        insumosModal.addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalInsumos();
            }
        });
    }
    
    // Export all functions to window for global access
    window.abrirModalAnchoMetraje = abrirModalAnchoMetraje;
    window.cerrarModalAnchoMetraje = cerrarModalAnchoMetraje;
    window.mostrarBotonesAnchoMetraje = mostrarBotonesAnchoMetraje;
    window.abrirModalConfirmacionEliminar = abrirModalConfirmacionEliminar;
    window.cerrarModalConfirmacionEliminar = cerrarModalConfirmacionEliminar;
    window.abrirModalInsumos = abrirModalInsumos;
    window.cerrarModalInsumos = cerrarModalInsumos;
    window.abrirModalObservaciones = abrirModalObservaciones;
    window.cerrarModalObservaciones = cerrarModalObservaciones;
    window.abrirDetalleRecibo = abrirDetalleRecibo;
    window.abrirModalPasarRevisar = abrirModalPasarRevisar;
    window.cerrarModalPasarRevisar = cerrarModalPasarRevisar;
});
