// ========== FUNCIONES PARA MODAL DE SECCION ==========

function abrirModalSeleccion() {
    console.log('[abrirModalSeleccion] Abriendo modal de seleccion de tipo');
    const modal = document.getElementById('modalSeleccionTipo');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function cerrarModalSeleccion() {
    console.log('[cerrarModalSeleccion] Cerrando modal de seleccion de tipo');
    const modal = document.getElementById('modalSeleccionTipo');
    if (modal) {
        modal.style.display = 'none';
    }
}

function seleccionarTipoProducto(tipo) {
    console.log('[seleccionarTipoProducto] Tipo seleccionado:', tipo);
    cerrarModalSeleccion();
    
    if (tipo === 'prenda') {
        abrirModalAgregarPrenda();
    } else if (tipo === 'epp') {
        abrirModalAgregarEPP();
    }
}

// ========== FUNCIONES PARA MODAL DE PRENDA ==========

function abrirModalAgregarPrenda() {
    console.log('[abrirModalAgregarPrenda] Abriendo modal de prenda');
    const modal = document.getElementById('modalAgregarPrenda');
    if (modal) {
        // Limpiar formulario
        document.getElementById('descripcionPrenda').value = '';
        document.getElementById('cantidadPrenda').value = '1';
        document.getElementById('valorUnitarioPrenda').value = '';
        document.getElementById('totalPrenda').value = '0';
        document.getElementById('observacionesPrenda').value = '';
        limpiarFotosPrenda();
        
        modal.style.display = 'flex';
        
        // Registrar listener de paste DESPUES de un pequeño delay
        setTimeout(() => {
            document.addEventListener('paste', globalThis.handlePastePrenda);
            console.log('[abrirModalAgregarPrenda] Paste listener registrado');
        }, 100);
    }
}

function cerrarModalAgregarPrenda() {
    console.log('[cerrarModalAgregarPrenda] Cerrando modal de prenda');
    const modal = document.getElementById('modalAgregarPrenda');
    if (modal) {
        modal.style.display = 'none';
        
        // Remover listener de paste
        document.removeEventListener('paste', globalThis.handlePastePrenda);
        console.log('[cerrarModalAgregarPrenda] Paste listener removido');
    }
    
    // Limpiar referencias de EDICION
    globalThis.prendaEnEdicion = null;
    globalThis.eppEnEdicion = null;
    console.log('[cerrarModalAgregarPrenda] Referencias de EDICION limpiadas');
}

function actualizarTotalPrenda() {
    const cantidad = parseFloat(document.getElementById('cantidadPrenda').value) || 0;
    const valorUnitario = parseFloat(document.getElementById('valorUnitarioPrenda').value) || 0;
    const total = cantidad * valorUnitario;
    document.getElementById('totalPrenda').value = total.toFixed(2);
}

function agregarFotoPrenda() {
    console.log('[agregarFotoPrenda] Abriendo selector de archivos');
    const input = document.getElementById('inputFotosPrenda');
    if (input) {
        input.click();
    }
}

function manejarSubidaFotosPrenda(input) {
    console.log('[manejarSubidaFotosPrenda] Archivos seleccionados:', input.files.length);
    
    const archivos = Array.from(input.files);
    const contenedor = document.getElementById('contenedorFotosPrenda');
    const mensaje = document.getElementById('mensajeDragDropPrenda');
    
    archivos.forEach((archivo, index) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const div = document.createElement('div');
            div.className = 'relative group rounded-lg overflow-hidden';
            div.innerHTML = `
                <img src="${e.target.result}" class="w-full h-24 object-cover border border-gray-300 rounded-lg cursor-pointer"
                    onclick="mostrarVistaPreviaFotoPrenda(this.src)">
                <button type="button" class="absolute top-1 right-1 bg-red-600 text-white p-1 rounded opacity-0 group-hover:opacity-100 transition"
                    onclick="this.parentElement.remove(); validarBotonesPrenda()">
                    <i class="material-symbols-rounded" style="font-size: 16px;">close</i>
                </button>
            `;
            contenedor.appendChild(div);
            if (mensaje) mensaje.style.display = 'none';
            validarBotonesPrenda();
        };
        reader.readAsDataURL(archivo);
    });
    
    input.value = '';
}

function limpiarFotosPrenda() {
    const contenedor = document.getElementById('contenedorFotosPrenda');
    const mensaje = document.getElementById('mensajeDragDropPrenda');
    
    if (contenedor) {
        const fotos = contenedor.querySelectorAll('div.relative');
        fotos.forEach(foto => foto.remove());
    }
    
    if (mensaje) mensaje.style.display = 'flex';
}

function mostrarVistaPreviaFotoPrenda(src) {
    console.log('[mostrarVistaPreviaFotoPrenda] Mostrando vista previa');
    // Implementar modal de vista previa si es necesario
}

function handleDropPrenda(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const contenedor = e.currentTarget;
    contenedor.style.borderColor = '#ccc';
    
    if (e.dataTransfer.files.length > 0) {
        const input = document.getElementById('inputFotosPrenda');
        input.files = e.dataTransfer.files;
        manejarSubidaFotosPrenda(input);
    }
}

function handleDragOverPrenda(e) {
    e.preventDefault();
    e.currentTarget.style.borderColor = '#3b82f6';
    e.currentTarget.style.backgroundColor = '#eff6ff';
}

function handleDragLeavePrenda(e) {
    e.currentTarget.style.borderColor = '#d1d5db';
    e.currentTarget.style.backgroundColor = 'transparent';
}

function validarBotonesPrenda() {
    const descripcion = document.getElementById('descripcionPrenda').value.trim();
    const cantidad = parseInt(document.getElementById('cantidadPrenda').value) || 0;
    const valido = descripcion.length > 0 && cantidad > 0;
    
    const btnFinalizar = document.getElementById('btnFinalizarAgregarPrenda');
    if (btnFinalizar) {
        btnFinalizar.disabled = !valido;
    }
}

function finalizarAgregarPrenda() {
    console.log('[finalizarAgregarPrenda] Finalizando agregaron de prenda');
    
    // Verificar si estamos en modo EDICION
    const enModoEdicion = !!(globalThis.eppEnEdicion && typeof globalThis.eppEnEdicion === 'object' && Object.keys(globalThis.eppEnEdicion).length > 0);
    const prendaEnEdicion = enModoEdicion ? globalThis.eppEnEdicion : null;
    
    if (enModoEdicion) {
        console.log('[finalizarAgregarPrenda]  MODO Edicion detectado:', prendaEnEdicion);
    } else {
        console.log('[finalizarAgregarPrenda]  MODO CREACION');
    }
    
    const descripcion = document.getElementById('descripcionPrenda').value.trim();
    const cantidad = parseInt(document.getElementById('cantidadPrenda').value) || 1;
    const valorUnitario = parseFloat(document.getElementById('valorUnitarioPrenda').value) || 0;
    const total = parseFloat(document.getElementById('totalPrenda').value) || 0;
    const observaciones = document.getElementById('observacionesPrenda').value.trim();
    
    if (!descripcion) {
        alert('Por favor ingresa la descripcion de la prenda');
        return;
    }
    
    // Obtener imagenes
    const fotos = [];
    const imagenes = document.querySelectorAll('#contenedorFotosPrenda img');
    imagenes.forEach(img => {
        fotos.push(img.src);
    });
    
    // Crear objeto de prenda
    const prenda = {
        tipo: 'prenda',
        id: Date.now(),
        descripcion: descripcion,
        cantidad: cantidad,
        valorUnitario: valorUnitario,
        total: total,
        observaciones: observaciones,
        imagenes: fotos
    };
    
    console.log('[finalizarAgregarPrenda] Prenda creada:', prenda);
    
    // Agregar a ventana global para uso posterior
    if (!globalThis.prendasAgregadas) {
        globalThis.prendasAgregadas = [];
    }
    
    // Si estamos en modo EDICION, actualizar la prenda existente en prendasAgregadas
    if (enModoEdicion) {
        const targetId = prendaEnEdicion.id || prendaEnEdicion.prenda_id || prendaEnEdicion.epp_id;
        const indexPrendas = globalThis.prendasAgregadas.findIndex(p =>
            String(p.id) === String(targetId) || String(p.prenda_id) === String(targetId)
        );
        if (indexPrendas !== -1) {
            globalThis.prendasAgregadas[indexPrendas] = prenda;
            console.log('[finalizarAgregarPrenda] Prenda actualizada en prendasAgregadas');
        }
    } else {
        globalThis.prendasAgregadas.push(prenda);
    }
    
    // Agregar a globalThis.itemsPedido para enviar
    if (!globalThis.itemsPedido) {
        globalThis.itemsPedido = [];
    }
    
    const prendaData = {
        tipo: 'prenda',
        id: prenda.id,
        nombre_epp: descripcion,  // Usar mismo field que EPP para compatibilidad
        cantidad: cantidad,
        observaciones: observaciones || '-',
        valor_unitario: valorUnitario,
        total: total,
        imagenes: fotos
    };
    
    // Si estamos en modo EDICION, NO agregar a globalThis.itemsPedido de nuevo
    // Solo actualizar el existente
    if (!enModoEdicion) {
        globalThis.itemsPedido.push(prendaData);
        console.log('[finalizarAgregarPrenda] Prenda agregada a globalThis.itemsPedido');
    } else {
        // Actualizar en globalThis.itemsPedido si existe
        const targetId = prendaEnEdicion.id || prendaEnEdicion.prenda_id || prendaEnEdicion.epp_id;
        const index = globalThis.itemsPedido.findIndex(item =>
            String(item.id) === String(targetId) || String(item.epp_id) === String(targetId)
        );
        if (index !== -1) {
            globalThis.itemsPedido[index].nombre_epp = descripcion;
            globalThis.itemsPedido[index].nombre = descripcion;
            globalThis.itemsPedido[index].cantidad = cantidad;
            globalThis.itemsPedido[index].observaciones = observaciones || '-';
            globalThis.itemsPedido[index].valor_unitario = valorUnitario;
            globalThis.itemsPedido[index].total = total;
            globalThis.itemsPedido[index].imagenes = fotos;
            console.log('[finalizarAgregarPrenda] Prenda actualizada en globalThis.itemsPedido:', globalThis.itemsPedido[index]);
        }
    }
    
    // Renderizar en la tabla principal usando eppItemManagerTabla
    if (globalThis.eppItemManagerTabla && typeof globalThis.eppItemManagerTabla.crearItem === 'function') {
        if (enModoEdicion) {
            // MODO EDICIÓN: Actualizar item existente
            console.log('[finalizarAgregarPrenda]  ACTUALIZANDO prenda en tabla principal');
            const targetId = prendaEnEdicion.id || prendaEnEdicion.prenda_id || prendaEnEdicion.epp_id;
            
            if (typeof globalThis.eppItemManagerTabla.actualizarItem === 'function') {
                globalThis.eppItemManagerTabla.actualizarItem(targetId, {
                    nombre: descripcion,
                    cantidad: cantidad,
                    observaciones: observaciones || '-',
                    valor_unitario: valorUnitario,
                    total: total,
                    imagenes: fotos.map((src, idx) => ({
                        previewUrl: src,
                        base64: src,
                        nombre: `prenda_${prenda.id}_${idx}`
                    }))
                });
                console.log('[finalizarAgregarPrenda] Prenda actualizada correctamente');
            } else {
                console.warn('[finalizarAgregarPrenda] actualizarItem no disponible');
            }
        } else {
            // MODO CREACIÓN: Crear nuevo item
            console.log('[finalizarAgregarPrenda]  Creando nueva prenda en tabla principal');
            globalThis.eppItemManagerTabla.crearItem(
                prenda.id,                // id
                descripcion,               // nombre
                'prenda',                  // categoria
                cantidad,                  // cantidad
                observaciones || '-',      // observaciones
                fotos.map((src, idx) => ({  // imagenes
                    previewUrl: src,
                    base64: src,
                    nombre: `prenda_${prenda.id}_${idx}`
                })),
                prenda.id,                 // pedidoEppId
                valorUnitario,             // valorUnitario
                total,                     // total
                'prenda'                   // tipo
            );
        }
    } else {
        console.warn('[finalizarAgregarPrenda] eppItemManagerTabla no disponible');
    }
    
    // Registrar en gestionItemsUI si esta disponible
    if (globalThis.gestionItemsUI && typeof globalThis.gestionItemsUI.agregarEPPAlOrden === 'function') {
        globalThis.gestionItemsUI.agregarEPPAlOrden(prendaData);
        console.log('[finalizarAgregarPrenda] Prenda registrada en gestionItemsUI');
    }
    
    // Recalcular totales despues de agregar la prenda
    if (typeof globalThis.syncTotales === 'function') {
        globalThis.syncTotales();
        console.log('[finalizarAgregarPrenda] Totales recalculados');
    }
    
    // NOTA: El guardado en BD se realiza cuando se envia la cotizacion completa (junto con EPPs)
    // No se intenta guardar la prenda individualmente aqui
    
    cerrarModalAgregarPrenda();
}

/**
 * Guardar prenda en la base de datos
 */
function guardarPrendaEnBD(prendaData) {
    const cotizacionId = document.querySelector('[data-cotizacion-id]')?.getAttribute('data-cotizacion-id')
        || new URLSearchParams(globalThis.location.search).get('id')
        || globalThis.__COTIZACION_ID__;
    
    if (!cotizacionId) {
        console.warn('[guardarPrendaEnBD] No se puede obtener el ID de cotizacion');
        return;
    }
    
    const datos = {
        cotizacion_id: cotizacionId,
        descripcion: prendaData.nombre_epp,
        cantidad: prendaData.cantidad,
        observaciones: prendaData.observaciones,
        valor_unitario: prendaData.valor_unitario,
        imagenes: prendaData.imagenes
    };
    
    console.log('[guardarPrendaEnBD] Intentando guardar prenda:', datos);
    
    fetch('/api/cotizacion/prendas', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify(datos)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('[guardarPrendaEnBD] Prenda guardada exitosamente:', data);
            if (globalThis.mostrarNotificacion) {
                globalThis.mostrarNotificacion('Prenda guardada correctamente', 'success');
            }
        } else {
            console.error('[guardarPrendaEnBD] Error:', data.error);
            if (globalThis.mostrarNotificacion) {
                globalThis.mostrarNotificacion('Error al guardar prenda: ' + data.error, 'error');
            }
        }
    })
    .catch(error => {
        console.error('[guardarPrendaEnBD] Error de conexion:', error);
        if (globalThis.mostrarNotificacion) {
            globalThis.mostrarNotificacion('Error de conexion: ' + error.message, 'error');
        }
    });
}

// Observadores de cambios en el formulario de prenda
document.addEventListener('DOMContentLoaded', function() {
    const descripcionPrenda = document.getElementById('descripcionPrenda');
    const cantidadPrenda = document.getElementById('cantidadPrenda');
    
    if (descripcionPrenda) {
        descripcionPrenda.addEventListener('input', validarBotonesPrenda);
    }
    
    if (cantidadPrenda) {
        cantidadPrenda.addEventListener('input', validarBotonesPrenda);
    }
});
