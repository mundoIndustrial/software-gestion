/**
 * Modal Ancho y Metraje - Insumos Module (FASE 3)
 * Funciones para gestionar el modal de ancho y metraje por prenda
 * 
 * Funciones incluidas:
 * - abrirModalAnchoMetraje() - Abrir modal y cargar datos
 * - generarInputsPorColor() - Generar form inputs por color
 * - generarInputsPorTallaColor() - Generar form inputs por talla-color
 * - generarInputsPorPieza() - Generar form inputs por pieza
 * - cambiarModoAnchoMetraje() - Cambiar entre vistas (normal/color/pieza/mano)
 * - cerrarModalAnchoMetraje() - Cerrar modal
 * - abrirModalConfirmacionEliminar() - Abrir confirmación de eliminación
 * - cerrarModalConfirmacionEliminar() - Cerrar confirmación
 * - confirmarEliminarAnchoMetraje() - Confirmar eliminación
 * - guardarAnchoMetraje() - Guardar datos de ancho y metraje
 * - actualizarReciboConAnchoMetraje() - Actualizar recibo con datos
 */

/**
 * Abre el modal de Ancho y Metraje para una prenda específica
 * Detecta si es prenda combinada (múltiples colores) o normal
 * El usuario puede elegir guardar normal o por color
 */
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
            
            // Determinar si mostrar opción "POR PIEZA"
            const tieneMultiplesColores = coloresData.success && 
                                         coloresData.modo === 'piezas' && 
                                         coloresData.colores && 
                                         coloresData.colores.length > 1;
            
            const esCombinada = datosData.success && 
                               datosData.modo === 'talla-color';
            
            // SIEMPRE mostrar las 3 opciones
            console.log('[abrirModalAnchoMetraje] Mostrando siempre todas las 3 opciones de modo');
            
            // Mostrar selector de modo y ejecutar cambio
            modoSelector.classList.remove('hidden');
            
            // Disparar evento de cambio para mostrar la vista correcta
            const modoActual = document.querySelector('input[name="modoAnchoMetraje"]:checked').value;
            cambiarModoAnchoMetraje({ target: { value: modoActual } });
            
            // Ocultar loading
            document.getElementById('anchoMetrajeLoading').classList.add('hidden');
        })
        .catch(error => {
            console.error('[abrirModalAnchoMetraje] Error al cargar datos:', error);
            showToast('Error al cargar los datos', 'error');
            document.getElementById('anchoMetrajeLoading').classList.add('hidden');
            document.getElementById('modoSelector').classList.remove('hidden');
        });
    }
}

/**
 * Genera inputs dinámicos para cada color (modo por color)
 * Estructura: Ancho General + Metraje por Color
 */
function generarInputsPorColor(coloresData, datosData) {
    const container = document.getElementById('colorInputsContainer');
    container.innerHTML = '';
    
    // PRIMERO: Crear input de ANCHO GENERAL
    const anchoGeneralDiv = document.createElement('div');
    anchoGeneralDiv.className = 'bg-blue-50 border-l-4 border-blue-500 pl-4 py-3 rounded p-4';
    
    // Buscar ancho general: puede estar en datosData.ancho_general o dentro de data[]
    let anchoGeneralGuardado = '';
    if (datosData.success) {
        if (datosData.ancho_general) {
            anchoGeneralGuardado = datosData.ancho_general;
        } else if (datosData.ancho) {
            anchoGeneralGuardado = datosData.ancho;
        } else if (datosData.data && Array.isArray(datosData.data)) {
            const datosGeneral = datosData.data.find(d => d.ancho && !d.talla);
            if (datosGeneral) {
                anchoGeneralGuardado = datosGeneral.ancho || '';
            }
        }
    }
    
    anchoGeneralDiv.innerHTML = `
        <h3 class="font-bold text-gray-800 mb-3 flex items-center gap-2">
            <i class="fas fa-expand-alt text-blue-600"></i>
            Ancho General (se aplica a todos los colores)
        </h3>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Ancho (m):</label>
            <input 
                type="number" 
                id="anchoGeneralInput"
                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="0.00"
                step="0.01"
                min="0"
                value="${anchoGeneralGuardado}"
            >
        </div>
    `;
    container.appendChild(anchoGeneralDiv);
    
    // SEGUNDO: Crear inputs de METRAJE por color
    const metrajeDiv = document.createElement('div');
    metrajeDiv.className = 'border-t pt-4';
    
    const metrajeTitle = document.createElement('h3');
    metrajeTitle.className = 'font-bold text-gray-800 mb-3 flex items-center gap-2';
    metrajeTitle.innerHTML = '<i class="fas fa-ruler-vertical text-orange-600"></i> Metraje por Color';
    metrajeDiv.appendChild(metrajeTitle);
    
    // Crear UN input de metraje por color
    coloresData.forEach(colorData => {
        // El servidor ahora devuelve estructura simplificada
        // Prioridad: colorData.nombre > colorData.color?.nombre > 'Color'
        let colorNombre = 'Color';
        
        if (typeof colorData === 'string') {
            colorNombre = colorData;
        } else if (colorData.nombre) {
            colorNombre = colorData.nombre;
        } else if (colorData.color && typeof colorData.color === 'object' && colorData.color.nombre) {
            colorNombre = colorData.color.nombre;
        } else if (colorData.color && typeof colorData.color === 'string') {
            colorNombre = colorData.color;
        }
        
        console.log('[generarInputsPorColor] Color procesado:', { colorData, colorNombre });
        
        // Buscar metraje guardado para este color (sin talla)
        let metrajeGuardado = '';
        if (datosData.success && datosData.data && Array.isArray(datosData.data)) {
            const datosColor = datosData.data.find(d => d.color === colorNombre && !d.talla);
            if (datosColor) {
                metrajeGuardado = datosColor.metraje || '';
            }
        }
        
        const colorInputDiv = document.createElement('div');
        colorInputDiv.className = 'mb-4 p-3 bg-orange-50 rounded border border-orange-200';
        
        // Si hay tallas en el color, mostrarlas
        const tallasInfo = (colorData.tallas && colorData.tallas.length > 0) 
            ? ` (${colorData.tallas.join(', ')})` 
            : '';
        
        colorInputDiv.innerHTML = `
            <label class="block text-sm font-semibold text-gray-800 mb-2 flex items-center gap-2">
                <span class="inline-block w-3 h-3 rounded-full bg-orange-400"></span>
                ${colorNombre}${tallasInfo}
            </label>
            <input 
                type="number" 
                class="colorMetraje w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-orange-500"
                placeholder="0.00"
                step="0.01"
                min="0"
                data-color="${colorNombre}"
                data-talla=""
                value="${metrajeGuardado}"
            >
        `;
        metrajeDiv.appendChild(colorInputDiv);
    });
    
    container.appendChild(metrajeDiv);
}

/**
 * Genera inputs para talla-color (idéntico a por color, solo cambia el contenedor)
 * Estructura: Ancho General + Metraje por Color
 */
function generarInputsPorTallaColor(coloresData, datosData) {
    const container = document.getElementById('piezaInputsContainer');
    container.innerHTML = '';
    
    // PRIMERO: Crear input de ANCHO GENERAL
    const anchoGeneralDiv = document.createElement('div');
    anchoGeneralDiv.className = 'bg-blue-50 border-l-4 border-blue-500 pl-4 py-3 rounded p-4';
    
    // Buscar ancho general: puede estar en datosData.ancho_general o dentro de data[]
    let anchoGeneralGuardado = '';
    if (datosData.success) {
        if (datosData.ancho_general) {
            anchoGeneralGuardado = datosData.ancho_general;
        } else if (datosData.ancho) {
            anchoGeneralGuardado = datosData.ancho;
        } else if (datosData.data && Array.isArray(datosData.data)) {
            const datosGeneral = datosData.data.find(d => d.ancho && !d.talla);
            if (datosGeneral) {
                anchoGeneralGuardado = datosGeneral.ancho || '';
            }
        }
    }
    
    anchoGeneralDiv.innerHTML = `
        <h3 class="font-bold text-gray-800 mb-3 flex items-center gap-2">
            <i class="fas fa-expand-alt text-blue-600"></i>
            Ancho General (se aplica a todos los colores)
        </h3>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Ancho (m):</label>
            <input 
                type="number" 
                id="anchoGeneralPiezaInput"
                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="0.00"
                step="0.01"
                min="0"
                value="${anchoGeneralGuardado}"
            >
        </div>
    `;
    container.appendChild(anchoGeneralDiv);
    
    // SEGUNDO: Crear inputs de METRAJE por color
    const metrajeDiv = document.createElement('div');
    metrajeDiv.className = 'border-t pt-4';
    
    const metrajeTitle = document.createElement('h3');
    metrajeTitle.className = 'font-bold text-gray-800 mb-3 flex items-center gap-2';
    metrajeTitle.innerHTML = '<i class="fas fa-ruler-vertical text-orange-600"></i> Metraje por Color';
    metrajeDiv.appendChild(metrajeTitle);
    
    // Crear UN input de metraje por color
    coloresData.forEach(colorData => {
        // El servidor ahora devuelve estructura simplificada
        // Prioridad: colorData.nombre > colorData.color?.nombre > 'Color'
        let colorNombre = 'Color';
        
        if (typeof colorData === 'string') {
            colorNombre = colorData;
        } else if (colorData.nombre) {
            colorNombre = colorData.nombre;
        } else if (colorData.color && typeof colorData.color === 'object' && colorData.color.nombre) {
            colorNombre = colorData.color.nombre;
        } else if (colorData.color && typeof colorData.color === 'string') {
            colorNombre = colorData.color;
        }
        
        console.log('[generarInputsPorTallaColor] Color procesado:', { colorData, colorNombre });
        
        // Buscar metraje guardado para este color (sin talla)
        let metrajeGuardado = '';
        if (datosData.success && datosData.data && Array.isArray(datosData.data)) {
            const datosColor = datosData.data.find(d => d.color === colorNombre && !d.talla);
            if (datosColor) {
                metrajeGuardado = datosColor.metraje || '';
            }
        }
        
        const colorInputDiv = document.createElement('div');
        colorInputDiv.className = 'mb-4 p-3 bg-orange-50 rounded border border-orange-200';
        
        // Si hay tallas en el color, mostrarlas
        const tallasInfo = (colorData.tallas && colorData.tallas.length > 0) 
            ? ` (${colorData.tallas.join(', ')})` 
            : '';
        
        colorInputDiv.innerHTML = `
            <label class="block text-sm font-semibold text-gray-800 mb-2 flex items-center gap-2">
                <span class="inline-block w-3 h-3 rounded-full bg-orange-400"></span>
                ${colorNombre}${tallasInfo}
            </label>
            <input 
                type="number" 
                class="colorMetraje w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-orange-500"
                placeholder="0.00"
                step="0.01"
                min="0"
                data-color="${colorNombre}"
                data-talla=""
                value="${metrajeGuardado}"
            >
        `;
        metrajeDiv.appendChild(colorInputDiv);
    });
    
    container.appendChild(metrajeDiv);
}

/**
 * Genera inputs dinámicos para entrada por pieza/item
 */
function generarInputsPorPieza(piezasData, datosData) {
    const container = document.getElementById('piezaInputsContainer');
    container.innerHTML = '';
    
    if (!piezasData || piezasData.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-info-circle text-xl mb-2"></i>
                <p class="text-sm">No hay datos de piezas disponibles para esta prenda.</p>
            </div>
        `;
        return;
    }
    
    piezasData.forEach((piezaData, index) => {
        const piezaNumero = piezaData.numero || piezaData.nombre || `Pieza ${index + 1}`;
        
        // Buscar datos guardados para esta pieza
        let metrajeGuardado = '';
        if (datosData && datosData.success && datosData.piezas) {
            const datoPieza = datosData.piezas.find(p => 
                (p.numero && p.numero === piezaNumero) || 
                (p.nombre && p.nombre === piezaNumero)
            );
            if (datoPieza) {
                metrajeGuardado = datoPieza.metraje || '';
            }
        }
        
        const piezaDiv = document.createElement('div');
        piezaDiv.className = 'pieza-row border-l-4 border-purple-500 pl-4 py-3 bg-gray-50 rounded mb-3';
        piezaDiv.innerHTML = `
            <h4 class="font-bold text-gray-800 mb-3 flex items-center gap-2">
                <span class="inline-block w-4 h-4 rounded bg-purple-500"></span>
                ${piezaNumero}
            </h4>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Número/Item:</label>
                    <input 
                        type="text" 
                        class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="Número de pieza"
                        data-pieza-numero
                        value="${piezaNumero}"
                        disabled
                    >
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Metraje (m):</label>
                    <input 
                        type="number" 
                        class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="0.00"
                        step="0.01"
                        min="0"
                        data-pieza-metraje
                        value="${metrajeGuardado}"
                    >
                </div>
            </div>
        `;
        container.appendChild(piezaDiv);
    });
}

/**
 * Cambia entre vista normal, vista por color y vista por pieza
 */
function cambiarModoAnchoMetraje(e) {
    // Obtener modo: desde el evento (si existe) o del radio button seleccionado
    let modo;
    if (e && e.target) {
        modo = e.target.value;
    } else {
        // Llamada directa sin evento - obtener del radio button seleccionado
        modo = document.querySelector('input[name="modoAnchoMetraje"]:checked').value;
    }
    
    const modal = document.getElementById('modalAnchoMetraje');
    
    // VALIDAR: Si ya hay datos guardados con otro tipo_modo, advertir al usuario
    const tipoModoGuardado = modal.tipoModoGuardado;
    const tieneDatosGuardados = modal.tieneDatosGuardados;
    
    if (e && e.target && tipoModoGuardado && tieneDatosGuardados && modo !== tipoModoGuardado) {
        const nombresMode = { 'normal': 'Normal', 'color': 'Por Color', 'pieza': 'Por Pieza', 'mano': 'A Mano' };
        const modoGuardadoNombre = nombresMode[tipoModoGuardado] || tipoModoGuardado;
        const modoNuevoNombre = nombresMode[modo] || modo;
        
        // Mostrar advertencia pero permitir el cambio de modo
        showToast(
            `Cambiando de modo "${modoGuardadoNombre}" a "${modoNuevoNombre}". Al guardar, los datos anteriores se reemplazarán.`,
            'warning',
            4000
        );
    }
    
    const normalView = document.getElementById('normalView');
    const colorView = document.getElementById('colorView');
    const piezaView = document.getElementById('piezaView');
    const manoView = document.getElementById('manoView');
    
    // Ocultar todas las vistas
    normalView.classList.add('hidden');
    colorView.classList.add('hidden');
    piezaView.classList.add('hidden');
    manoView.classList.add('hidden');
    
    // Ocultar todos los mensajes de "no hay datos"
    document.getElementById('normalDataWarning')?.classList.add('hidden');
    document.getElementById('colorDataWarning')?.classList.add('hidden');
    document.getElementById('piezaDataWarning')?.classList.add('hidden');
    
    // Mostrar/ocultar botón de eliminar basado en si hay datos guardados y el modo es el mismo
    const btnEliminar = document.getElementById('btnEliminarAnchoMetraje');
    if (btnEliminar) {
        if (tieneDatosGuardados && tipoModoGuardado === modo) {
            btnEliminar.classList.remove('hidden');
        } else {
            btnEliminar.classList.add('hidden');
        }
    }
    
    if (modo === 'normal') {
        // MODO NORMAL - Un valor para toda la prenda
        normalView.classList.remove('hidden');
        
        // Cargar datos si están disponibles en el modal
        if (modal.datosData && modal.datosData.success) {
            if (modal.datosData.ancho !== null && modal.datosData.ancho !== undefined) {
                document.getElementById('anchoInput').value = modal.datosData.ancho;
            } else {
                document.getElementById('anchoInput').value = '';
            }
            
            if (modal.datosData.metraje !== null && modal.datosData.metraje !== undefined) {
                document.getElementById('metrajeInput').value = modal.datosData.metraje;
            } else {
                document.getElementById('metrajeInput').value = '';
            }
        } else {
            // Mostrar aviso si no hay datos
            document.getElementById('normalDataWarning')?.classList.remove('hidden');
            document.getElementById('anchoInput').value = '';
            document.getElementById('metrajeInput').value = '';
        }
        
    } else if (modo === 'color') {
        // MODO COLOR - Múltiples colores (mismo metraje para todas las tallas)
        colorView.classList.remove('hidden');
        
        const coloresData = modal.coloresData;
        const datosData = modal.datosData;
        
        // El servidor devuelve: coloresData.data en lugar de coloresData.colores
        if (coloresData && coloresData.success && coloresData.data && coloresData.data.length > 0) {
            // Mostrar inputs por color
            console.log('[cambiarModoAnchoMetraje] Modo color: Generando inputs por color');
            generarInputsPorColor(coloresData.data, datosData);
        } else {
            // No hay datos de colores disponibles
            console.log('[cambiarModoAnchoMetraje] Sin datos de colores disponibles');
            document.getElementById('colorDataWarning')?.classList.remove('hidden');
            document.getElementById('colorInputsContainer').innerHTML = '';
        }
        
    } else if (modo === 'pieza') {
        // MODO PIEZA - Misma estructura que "Por Color" pero se guardará con tipo_modo='pieza'
        piezaView.classList.remove('hidden');
        
        const coloresData = modal.coloresData;
        const datosData = modal.datosData;
        
        // El servidor devuelve: coloresData.data en lugar de coloresData.colores
        if (coloresData && coloresData.success && coloresData.data && coloresData.data.length > 0) {
            // Mostrar matriz talla-color (mismo HTML que por color)
            console.log('[cambiarModoAnchoMetraje] Modo pieza: Usando estructura de color/talla');
            generarInputsPorTallaColor(coloresData.data, datosData);
        } else {
            // No hay datos disponibles
            console.log('[cambiarModoAnchoMetraje] Sin datos de talla-color disponibles');
            document.getElementById('piezaDataWarning')?.classList.remove('hidden');
            document.getElementById('piezaInputsContainer').innerHTML = '';
        }
    } else if (modo === 'mano') {
        // MODO A MANO - Texto libre
        manoView.classList.remove('hidden');
        
        // Cargar datos si están disponibles
        if (modal.datosData && modal.datosData.success) {
            const contenidoMano = modal.datosData.contenido_mano || '';
            document.getElementById('manoTexto').value = contenidoMano;
        } else {
            document.getElementById('manoTexto').value = '';
        }
    }
}

/**
 * Cierra el modal de Ancho y Metraje
 */
function cerrarModalAnchoMetraje() {
    const modal = document.getElementById('modalAnchoMetraje');
    modal.style.display = 'none';
}

/**
 * Abre el modal de confirmación para eliminar ancho/metraje
 */
function abrirModalConfirmacionEliminar() {
    const modalConfirmacion = document.getElementById('modalConfirmacionEliminar');
    modalConfirmacion.classList.remove('hidden');
}

/**
 * Cierra el modal de confirmación para eliminar ancho/metraje
 */
function cerrarModalConfirmacionEliminar() {
    const modalConfirmacion = document.getElementById('modalConfirmacionEliminar');
    modalConfirmacion.classList.add('hidden');
}

/**
 * Confirma y ejecuta la eliminación de ancho/metraje
 */
function confirmarEliminarAnchoMetraje() {
    const modal = document.getElementById('modalAnchoMetraje');
    const prendaId = modal.dataset.prendaId;
    const pedido = modal.dataset.pedido;
    
    if (!prendaId) {
        showToast('Error: No se encontró la información de la prenda', 'error');
        return;
    }

    // Llamar al backend para eliminar
    fetch(`/insumos/materiales/${pedido}/eliminar-ancho-metraje-prenda`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        },
        body: JSON.stringify({
            prenda_id: prendaId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Datos eliminados correctamente', 'success');
            cerrarModalConfirmacionEliminar();
            
            // Recargar el modal (vacío)
            setTimeout(() => {
                cerrarModalAnchoMetraje();
                abrirModalAnchoMetraje(pedido, prendaId);
            }, 800);
        } else {
            showToast('Error al eliminar los datos: ' + (data.message || ''), 'error');
        }
    })
    .catch(error => {
        console.error('Error al eliminar ancho y metraje:', error);
        showToast('Error al eliminar los datos', 'error');
    });
}

/**
 * Guarda los valores de Ancho y Metraje (normal o por color)
 * Respeta la selección del usuario en el radio button
 */
function guardarAnchoMetraje() {
    const modal = document.getElementById('modalAnchoMetraje');
    const prendaId = modal.dataset.prendaId;
    const pedido = modal.dataset.pedido;
    
    if (!prendaId) {
        showToast('Error: No se encontró la información de la prenda', 'error');
        return;
    }
    
    // Obtener modo seleccionado del radio button
    const modoSeleccionado = document.querySelector('input[name="modoAnchoMetraje"]:checked').value;
    
    if (modoSeleccionado === 'normal') {
        // GUARDAR MODO NORMAL
        const anchoVal = document.getElementById('anchoInput').value.trim();
        const metrajeVal = document.getElementById('metrajeInput').value.trim();
        const ancho = anchoVal ? parseFloat(anchoVal) : null;
        const metraje = metrajeVal ? parseFloat(metrajeVal) : null;
        
        // Validar
        if (anchoVal && (isNaN(ancho) || ancho <= 0)) {
            showToast('El ancho debe ser un número mayor a 0', 'warning');
            return;
        }
        
        if (metrajeVal && (isNaN(metraje) || metraje <= 0)) {
            showToast('El metraje debe ser un número mayor a 0', 'warning');
            return;
        }
        
        // Guardar datos globales para compatibilidad
        window.actualizarAnchoMetrajeUniversal(ancho || 0, metraje || 0, pedido);
        
        // Enviar al servidor (sin color)
        fetch(`/insumos/materiales/${pedido}/guardar-ancho-metraje-prenda`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({
                prenda_pedido_id: prendaId,
                color: null,
                tipo_modo: 'normal',
                ancho: ancho,
                metraje: metraje
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('[guardarAnchoMetraje] Respuesta del servidor:', data);
            
            if (data.success) {
                showToast('Ancho y metraje guardados correctamente', 'success');
                
                if (window.receiptManager && window.receiptManager.datosFactura) {
                    console.log('[guardarAnchoMetraje] Actualizando recibo abierto...');
                    actualizarReciboConAnchoMetraje();
                }
                
                setTimeout(() => {
                    cerrarModalAnchoMetraje();
                }, 1000);
            } else {
                console.error('[guardarAnchoMetraje] Error del servidor:', data.error || data.message);
                showToast('Error: ' + (data.error || data.message || 'Error al guardar los datos'), 'error');
            }
        })
        .catch(error => {
            console.error('Error al guardar ancho y metraje:', error);
            showToast('Error al guardar los datos', 'error');
        });
    } else if (modoSeleccionado === 'color') {
        // GUARDAR MODO POR COLOR - Simplificado para evitar código duplicado
        const promises = [];
        
        // Guardar ancho general si existe
        const anchoGeneralInput = document.getElementById('anchoGeneralInput');
        if (anchoGeneralInput && anchoGeneralInput.value.trim()) {
            const anchoGeneral = parseFloat(anchoGeneralInput.value.trim());
            if (!isNaN(anchoGeneral) && anchoGeneral > 0) {
                promises.push(
                    fetch(`/insumos/materiales/${pedido}/guardar-ancho-metraje-prenda`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                        },
                        body: JSON.stringify({
                            prenda_pedido_id: prendaId,
                            color: null,
                            tipo_modo: 'color',
                            ancho: anchoGeneral,
                            metraje: null
                        })
                    }).then(r => r.json())
                );
            }
        }
        
        // Guardar metrajes por color
        document.querySelectorAll('#colorInputsContainer .colorMetraje').forEach(input => {
            const colorNombre = input.dataset.color;
            const metrajeVal = input.value.trim();
            
            if (metrajeVal) {
                const metraje = parseFloat(metrajeVal);
                if (!isNaN(metraje) && metraje > 0) {
                    promises.push(
                        fetch(`/insumos/materiales/${pedido}/guardar-ancho-metraje-prenda`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                            },
                            body: JSON.stringify({
                                prenda_pedido_id: prendaId,
                                color: colorNombre,
                                tipo_modo: 'color',
                                ancho: null,
                                metraje: metraje
                            })
                        }).then(r => r.json())
                    );
                }
            }
        });
        
        if (promises.length === 0) {
            showToast('Por favor llena al menos un campo', 'warning');
            return;
        }
        
        Promise.all(promises)
            .then(results => {
                if (results.every(r => r.success)) {
                    showToast('Ancho y metraje guardados correctamente', 'success');
                    setTimeout(() => {
                        cerrarModalAnchoMetraje();
                    }, 1000);
                } else {
                    showToast('Error al guardar algunos datos', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error al guardar los datos', 'error');
            });
    } else if (modoSeleccionado === 'pieza') {
        // GUARDAR MODO POR PIEZA - Idéntico a color pero con tipo_modo='pieza'
        const promises = [];
        
        // Guardar ancho general si existe
        const anchoGeneralPiezaInput = document.getElementById('anchoGeneralPiezaInput');
        if (anchoGeneralPiezaInput && anchoGeneralPiezaInput.value.trim()) {
            const anchoGeneral = parseFloat(anchoGeneralPiezaInput.value.trim());
            if (!isNaN(anchoGeneral) && anchoGeneral > 0) {
                promises.push(
                    fetch(`/insumos/materiales/${pedido}/guardar-ancho-metraje-prenda`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                        },
                        body: JSON.stringify({
                            prenda_pedido_id: prendaId,
                            color: null,
                            tipo_modo: 'pieza',
                            ancho: anchoGeneral,
                            metraje: null
                        })
                    }).then(r => r.json())
                );
            }
        }
        
        // Guardar metrajes por color (mismos inputs que color)
        document.querySelectorAll('#piezaInputsContainer .colorMetraje').forEach(input => {
            const colorNombre = input.dataset.color;
            const metrajeVal = input.value.trim();
            
            if (metrajeVal) {
                const metraje = parseFloat(metrajeVal);
                if (!isNaN(metraje) && metraje > 0) {
                    promises.push(
                        fetch(`/insumos/materiales/${pedido}/guardar-ancho-metraje-prenda`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                            },
                            body: JSON.stringify({
                                prenda_pedido_id: prendaId,
                                color: colorNombre,
                                tipo_modo: 'pieza',
                                ancho: null,
                                metraje: metraje
                            })
                        }).then(r => r.json())
                    );
                }
            }
        });
        
        if (promises.length === 0) {
            showToast('Por favor llena al menos un campo', 'warning');
            return;
        }
        
        Promise.all(promises)
            .then(results => {
                if (results.every(r => r.success)) {
                    showToast('Ancho y metraje guardados correctamente', 'success');
                    setTimeout(() => {
                        cerrarModalAnchoMetraje();
                    }, 1000);
                } else {
                    showToast('Error al guardar algunos datos', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error al guardar los datos', 'error');
            });
    } else if (modoSeleccionado === 'mano') {
        // GUARDAR MODO A MANO - Guardar texto libre
        const contenidoMano = document.getElementById('manoTexto').value.trim();
        
        if (!contenidoMano) {
            showToast('Por favor ingresa el contenido', 'warning');
            return;
        }
        
        fetch(`/insumos/materiales/${pedido}/guardar-ancho-metraje-prenda`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({
                prenda_pedido_id: prendaId,
                color: null,
                tipo_modo: 'mano',
                ancho: null,
                metraje: null,
                contenido_mano: contenidoMano
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('[guardarAnchoMetraje] Respuesta del servidor (modo mano):', data);
            
            if (data.success) {
                showToast('Ancho y metraje guardados correctamente', 'success');
                
                setTimeout(() => {
                    cerrarModalAnchoMetraje();
                }, 1000);
            } else {
                console.error('[guardarAnchoMetraje] Error del servidor:', data.error || data.message);
                showToast('Error: ' + (data.error || data.message || 'Error al guardar los datos'), 'error');
            }
        })
        .catch(error => {
            console.error('Error al guardar ancho y metraje:', error);
            showToast('Error al guardar los datos', 'error');
        });
    }
}

/**
 * Actualiza el recibo abierto con los datos de ancho y metraje
 */
function actualizarReciboConAnchoMetraje() {
    if (!window.datosAnchoMetraje || !window.receiptManager) {
        console.log('[actualizarReciboConAnchoMetraje] No hay datos de ancho/metraje o ReceiptManager');
        return;
    }
    
    const { ancho, metraje } = window.datosAnchoMetraje;
    
    // Buscar o crear el elemento para mostrar ancho y metraje
    let anchoMetrajeElement = document.getElementById('ancho-metraje-disponible');
    
    if (!anchoMetrajeElement) {
        // Crear el elemento si no existe
        anchoMetrajeElement = document.createElement('div');
        anchoMetrajeElement.id = 'ancho-metraje-disponible';
        anchoMetrajeElement.style.cssText = `
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: bold;
            text-align: right;
            z-index: 10;
        `;
        
        // Insertar después del título del recibo
        const receiptTitle = document.getElementById('receipt-title');
        if (receiptTitle) {
            receiptTitle.parentNode.insertBefore(anchoMetrajeElement, receiptTitle.nextSibling);
        }
    }
    
    // Actualizar el contenido
    anchoMetrajeElement.innerHTML = `
        ANCHO DISPONIBLE: ${ancho.toFixed(2)} m<br>
        METRAJE DISPONIBLE: ${metraje.toFixed(2)} m
    `;
    
    console.log('[actualizarReciboConAnchoMetraje] Recibo actualizado con ancho y metraje');
}

// Auto-initialize: Export all functions to window on DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    window.abrirModalAnchoMetraje = abrirModalAnchoMetraje;
    window.generarInputsPorColor = generarInputsPorColor;
    window.generarInputsPorTallaColor = generarInputsPorTallaColor;
    window.generarInputsPorPieza = generarInputsPorPieza;
    window.cambiarModoAnchoMetraje = cambiarModoAnchoMetraje;
    window.cerrarModalAnchoMetraje = cerrarModalAnchoMetraje;
    window.abrirModalConfirmacionEliminar = abrirModalConfirmacionEliminar;
    window.cerrarModalConfirmacionEliminar = cerrarModalConfirmacionEliminar;
    window.confirmarEliminarAnchoMetraje = confirmarEliminarAnchoMetraje;
    window.guardarAnchoMetraje = guardarAnchoMetraje;
    window.actualizarReciboConAnchoMetraje = actualizarReciboConAnchoMetraje;
});
