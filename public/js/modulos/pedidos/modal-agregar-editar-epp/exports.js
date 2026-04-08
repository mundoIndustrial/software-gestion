// Exportar funciones necesarias al objeto globalThis para que estan disponibles globalmente
globalThis.abrirModalSeleccion = abrirModalSeleccion;
globalThis.cerrarModalSeleccion = cerrarModalSeleccion;
globalThis.seleccionarTipoProducto = seleccionarTipoProducto;
globalThis.abrirModalAgregarPrenda = abrirModalAgregarPrenda;
globalThis.cerrarModalAgregarPrenda = cerrarModalAgregarPrenda;
globalThis.actualizarTotalPrenda = actualizarTotalPrenda;
globalThis.agregarFotoPrenda = agregarFotoPrenda;
globalThis.manejarSubidaFotosPrenda = manejarSubidaFotosPrenda;
globalThis.limpiarFotosPrenda = limpiarFotosPrenda;
globalThis.mostrarVistaPreviaFotoPrenda = mostrarVistaPreviaFotoPrenda;
globalThis.handleDropPrenda = handleDropPrenda;
globalThis.handleDragOverPrenda = handleDragOverPrenda;
globalThis.handleDragLeavePrenda = handleDragLeavePrenda;
globalThis.validarBotonesPrenda = validarBotonesPrenda;
globalThis.finalizarAgregarPrenda = finalizarAgregarPrenda;

// Funciones nuevas de seleccion multiples con dropdown
globalThis.cargarEPPDisponibles = cargarEPPDisponibles;
globalThis.mostrarDropdownEPP = mostrarDropdownEPP;
globalThis.renderizarDropdownEPP = renderizarDropdownEPP;
globalThis.filtrarDropdownEPP = filtrarDropdownEPP;
globalThis.agregarEPPDesdeDropdown = agregarEPPDesdeDropdown;
globalThis.actualizarSeleccionEPP = actualizarSeleccionEPP;
globalThis.renderizarTablaEPPAgregados = renderizarTablaEPPAgregados;
globalThis.actualizarCantidadEPP = actualizarCantidadEPP;
globalThis.actualizarValorUnitarioEPP = actualizarValorUnitarioEPP;
globalThis.actualizarObservacionesEPP = actualizarObservacionesEPP;
globalThis.eliminarEPPDeLista = eliminarEPPDeLista;
globalThis.obtenerEPPsYaAgregadosEnFormulario = obtenerEPPsYaAgregadosEnFormulario;

// Funciones de manejo de fotos en tabla EPP
globalThis.manejarSeleccionFotosEPP = manejarSeleccionFotosEPP;
globalThis.manejarDragOverFotosEPP = manejarDragOverFotosEPP;
globalThis.manejarDragLeaveFotosEPP = manejarDragLeaveFotosEPP;
globalThis.manejarDropFotosEPP = manejarDropFotosEPP;
globalThis.eliminarFotoEPP = eliminarFotoEPP;

// Funciones principales del modal
globalThis.abrirModalAgregarEPP = abrirModalAgregarEPP;
globalThis.abrirModalEditarEPPNuevo = abrirModalEditarEPPNuevo;
globalThis.resetearModalAgregarEPP = resetearModalAgregarEPP;
globalThis.cerrarModalAgregarEPP = cerrarModalAgregarEPP;
globalThis.cerrarModalAgregarEPPConfirmado = cerrarModalAgregarEPPConfirmado;
globalThis.hayDatosNoGuardados = hayDatosNoGuardados;
globalThis.mostrarProductoEPP = mostrarProductoEPP;
globalThis.agregarEPPALista = agregarEPPALista;
globalThis.finalizarAgregarEPP = finalizarAgregarEPP;
globalThis.guardarEdicionEPP = guardarEdicionEPP;
globalThis.filtrarEPPBuscador = filtrarEPPBuscador;
globalThis.agregarFotoEPP = agregarFotoEPP;
globalThis.mostrarVistaPreviaFoto = mostrarVistaPreviaFoto;
globalThis.limpiarImagenesTemporales = limpiarImagenesTemporales;

console.log('[EPP Modal] Todas las funciones exportadas al objeto globalThis');

// ========== MANEJADOR DE CTRL+V PARA PRENDAS ==========
function handlePastePrenda(event) {
    console.log('[handlePastePrenda] Paste detectado');
    
    if (!event.clipboardData || !event.clipboardData.items) {
        console.warn('[handlePastePrenda] No hay datos en el clipboard');
        return;
    }
    
    const items = event.clipboardData.items;
    const archivos = [];
    
    for (let i = 0; i < items.length; i++) {
        if (items[i].kind === 'file' && items[i].type.startsWith('image/')) {
            archivos.push(items[i].getAsFile());
            console.log('[handlePastePrenda] Imagen pegada del clipboard:', items[i].type);
        }
    }
    
    if (archivos.length > 0) {
        event.preventDefault();
        event.stopPropagation();
        
        // Crear un pseudo-evento con los archivos
        const input = document.getElementById('inputFotosPrenda');
        const dataTransfer = new DataTransfer();
        
        archivos.forEach(archivo => {
            dataTransfer.items.add(archivo);
        });
        
        input.files = dataTransfer.files;
        manejarSubidaFotosPrenda(input);
        console.log('[handlePastePrenda] Archivos pegados procesados:', archivos.length);
    }
}

// ========== MANEJADOR DE CTRL+V MEJORADO PARA EPP ==========
function handlePasteEPP(event) {
    console.log('[handlePasteEPP] Paste detectado');
    
    // Prevenir que otros handlers lo procesen
    event.preventDefault();
    event.stopPropagation();
    
    if (!event.clipboardData || !event.clipboardData.items) {
        console.warn('[handlePasteEPP] No hay datos en el clipboard');
        return;
    }
    
    const items = event.clipboardData.items;
    const archivos = [];
    
    for (let i = 0; i < items.length; i++) {
        if (items[i].kind === 'file' && items[i].type.startsWith('image/')) {
            archivos.push(items[i].getAsFile());
            console.log('[handlePasteEPP] Imagen pegada del clipboard:', items[i].type);
        }
    }
    
    if (archivos.length > 0) {
        console.log('[handlePasteEPP] Archivos encontrados:', archivos.length);
        console.log('[handlePasteEPP] globalThis.zonaFotosActivaId:', globalThis.zonaFotosActivaId);
        
        // Determinar si estamos en la tabla de EPPs agregados o en seccion de EDICION
        if (globalThis.zonaFotosActivaId && globalThis.zonaFotosActivaId.startsWith('fotoZona_')) {
            // Estamos en la tabla - usar manejarSeleccionFotosEPP
            const eppId = globalThis.zonaFotosActivaId.replace('fotoZona_', '');
            const input = document.getElementById(`inputFotos_${eppId}`);
            
            console.log('[handlePasteEPP] Pegando en tabla para EPP:', eppId);
            console.log('[handlePasteEPP] Input encontrado:', !!input);
            
            if (input) {
                const dataTransfer = new DataTransfer();
                archivos.forEach(archivo => {
                    dataTransfer.items.add(archivo);
                });
                input.files = dataTransfer.files;
                
                console.log('[handlePasteEPP] Archivos asignados al input:', input.files.length);
                console.log('[handlePasteEPP] Llamando a manejarSeleccionFotosEPP');
                
                // Llamar directamente con el objeto evento correcto
                manejarSeleccionFotosEPP({target: input, preventDefault: () => {}, stopPropagation: () => {}}, eppId);
            }
        } else {
            // Estamos en modo EDICION - usar el input de EDICION
            const inputEdicion = document.getElementById('inputFotosEPP');
            
            console.log('[handlePasteEPP] Pegando en seccion de EDICION');
            console.log('[handlePasteEPP] Input EDICION encontrado:', !!inputEdicion);
            
            if (inputEdicion) {
                const dataTransfer = new DataTransfer();
                archivos.forEach(archivo => {
                    dataTransfer.items.add(archivo);
                });
                inputEdicion.files = dataTransfer.files;
                
                console.log('[handlePasteEPP] Archivos asignados al input EDICION:', inputEdicion.files.length);
                console.log('[handlePasteEPP] Llamando a manejarSubidaFotosEPP');
                
                manejarSubidaFotosEPP(inputEdicion);
            }
        }
        
        console.log('[handlePasteEPP] Archivos pegados procesados:', archivos.length);
    }
}

// Registrar listener de paste cuando se abre el modal de prenda
function abrirModalAgregarPrendaConPasteListener() {
    abrirModalAgregarPrenda();
    setTimeout(() => {
        document.addEventListener('paste', handlePastePrenda);
        console.log('[handlePastePrenda] Listener de paste registrado');
    }, 100);
}

function cerrarModalAgregarPrendaConPasteListener() {
    document.removeEventListener('paste', handlePastePrenda);
    cerrarModalAgregarPrenda();
    console.log('[handlePastePrenda] Listener de paste removido');
}

globalThis.handlePastePrenda = handlePastePrenda;
globalThis.abrirModalAgregarPrendaConPasteListener = abrirModalAgregarPrendaConPasteListener;
globalThis.cerrarModalAgregarPrendaConPasteListener = cerrarModalAgregarPrendaConPasteListener;
globalThis.handlePasteEPP = handlePasteEPP;

// ========== CERRAR MODALES AL HACER CLIC EN EL FONDO ==========
document.addEventListener('click', function(e) {
    // Cerrar modal de seleccion si se hace clic en el fondo
    const modalSeleccion = document.getElementById('modalSeleccionTipo');
    if (e.target === modalSeleccion) {
        cerrarModalSeleccion();
    }
    
    // Cerrar modal de prenda si se hace clic en el fondo
    const modalPrenda = document.getElementById('modalAgregarPrenda');
    if (e.target === modalPrenda) {
        cerrarModalAgregarPrenda();
    }
    
    // Cerrar modal de EPP si se hace clic en el fondo
    const modalEPP = document.getElementById('modalAgregarEPP');
    if (e.target === modalEPP) {
        cerrarModalAgregarEPP();
    }
});

/**
 * Variable global para rastrear cual zona de fotos esta activa (ultima clickeada)
 */
globalThis.zonaFotosActivaId = null;

/**
 * Hacer que las zonas de fotos reciban focus al hacer click
 * Esto permite que Ctrl+V detecte correctamente en cual EPP pegar la imagen
 * tambien abre el file picker al hacer click
 */
document.addEventListener('click', function(e) {
    const fotoZona = e.target.closest('[id^="fotoZona_"]');
    
    if (fotoZona) {
        // Dar focus a la zona para que Ctrl+V pueda detectarla
        fotoZona.focus();
        // IMPORTANTE: Guardar cual zona esta activa para usar en paste
        globalThis.zonaFotosActivaId = fotoZona.id;
        console.log('[click] Focus en zona de fotos:', globalThis.zonaFotosActivaId);
        
        // Abrir file picker
        const eppId = fotoZona.id.replace('fotoZona_', '');
        const inputFile = document.getElementById(`inputFotos_${eppId}`);
        if (inputFile) {
            console.log('[click] Abriendo file picker para EPP:', eppId);
            inputFile.click();
        }
    }
});
