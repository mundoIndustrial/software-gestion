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
 * - abrirModalConfirmacionEliminar() - Abrir confirmacion
 * - cerrarModalConfirmacionEliminar() - Cerrar confirmacion
 * - confirmarEliminarAnchoMetraje() - Confirmar eliminacion
 * - guardarAnchoMetraje() - Guardar datos de ancho y metraje
 * - actualizarReciboConAnchoMetraje() - Actualizar recibo con datos
 */

/**
 * Abre el modal de Ancho y Metraje para una prenda especifica
 * Detecta si es prenda combinada (multiples colores) o normal
 * El usuario puede elegir guardar normal o por color
 */
function abrirModalAnchoMetraje(pedido, prendaId, prendaBodegaId = null, numeroPedido = null, tipoRecibo = 'COSTURA', numeroReciboInicial = null) {
    const modal = document.getElementById('modalAnchoMetraje');
    if (!modal) {
        console.error('[abrirModalAnchoMetraje] Modal no encontrado: modalAnchoMetraje');
        return;
    }
    
    modal.style.display = 'flex';

    const pedidoNum = parseInt(pedido, 10) || 0;
    const pedidoSegment = pedidoNum > 0 ? String(pedidoNum) : String(numeroPedido || '').trim();
    const prendaIdResolved = prendaId || prendaBodegaId;
    const esReciboBodega = String(tipoRecibo || '').toUpperCase() === 'CORTE-PARA-BODEGA';
    const queryBase = new URLSearchParams();
    if (prendaBodegaId) queryBase.set('prenda_bodega_id', String(prendaBodegaId));
    if (esReciboBodega) queryBase.set('tipo_recibo', 'CORTE-PARA-BODEGA');
    const qsBase = queryBase.toString() ? `?${queryBase.toString()}` : '';

    if (!pedidoSegment) {
        console.error('[abrirModalAnchoMetraje] No se pudo resolver pedido para construir URL', { pedido, numeroPedido, prendaId, prendaBodegaId, tipoRecibo });
        showToast('No se pudo resolver el pedido para ancho/metraje', 'error');
        return;
    }
    
    const numeroReciboForzado = Number(numeroReciboInicial);
    const tieneNumeroReciboForzado = Number.isFinite(numeroReciboForzado) && numeroReciboForzado > 0;

    const obtenerNumeroRecibo = () => {
        if (tieneNumeroReciboForzado) {
            document.getElementById('anchoMetrajeRecibo').textContent = String(numeroReciboForzado);
            return Promise.resolve(numeroReciboForzado);
        }

        return fetch(`/insumos/materiales/${encodeURIComponent(pedidoSegment)}/obtener-recibo-prenda/${prendaIdResolved}${qsBase}`)
        .then(r => r.json())
        .then(data => {
            const recibido = data?.success && data?.recibo ? Number(data.recibo) : 0;
            const numeroRecibo = Number.isFinite(recibido) && recibido > 0 ? recibido : null;
            document.getElementById('anchoMetrajeRecibo').textContent = numeroRecibo ? String(numeroRecibo) : '-';
            return numeroRecibo;
        })
        .catch(error => {
            console.error('Error al obtener recibo:', error);
            document.getElementById('anchoMetrajeRecibo').textContent = '-';
            return null;
        });
    };
    
    // Guardar pedido y prenda en el modal para usarlos despues
    modal.dataset.pedido = pedidoSegment;
    modal.dataset.prendaId = prendaIdResolved || '';
    modal.dataset.prendaBodegaId = prendaBodegaId || '';
    modal.dataset.tipoRecibo = tipoRecibo || 'COSTURA';
    modal.dataset.numeroRecibo = tieneNumeroReciboForzado ? String(numeroReciboForzado) : '';

    // Limpiar inputs
    document.getElementById('anchoInput').value = '';
    document.getElementById('metrajeInput').value = '';
    document.getElementById('colorInputsContainer').innerHTML = '';
    document.getElementById('piezaInputsContainer').innerHTML = '';
    
    // Resetear selector de modo a normal (fallback)
    document.querySelector('input[name="modoAnchoMetraje"][value="normal"]').checked = true;
    actualizarEstilosModoCards();
    
    // Ocultar todo y mostrar cargando
    document.getElementById('modoSelector').classList.add('hidden');
    document.getElementById('normalView').classList.add('hidden');
    document.getElementById('colorView').classList.add('hidden');
    document.getElementById('piezaView').classList.add('hidden');
    document.getElementById('anchoMetrajeLoading').classList.remove('hidden');
    actualizarIndicadorModo('normal', null, false);

    console.log('[abrirModalAnchoMetraje] Abriendo modal para pedido:', pedidoSegment, 'prenda:', prendaIdResolved);

    if (prendaIdResolved) {
        // Cargar colores y datos para rellenar los inputs segun el modo seleccionado
        obtenerNumeroRecibo().then((numeroRecibo) => {
            const queryLectura = new URLSearchParams(queryBase);
            if (numeroRecibo) {
                queryLectura.set('numero_recibo', String(numeroRecibo));
            }
            const qsLectura = queryLectura.toString() ? `?${queryLectura.toString()}` : '';

            return Promise.all([
                fetch(`/insumos/materiales/${encodeURIComponent(pedidoSegment)}/obtener-colores-prenda/${prendaIdResolved}${qsLectura}`).then(r => r.json()),
                fetch(`/insumos/materiales/${encodeURIComponent(pedidoSegment)}/obtener-ancho-metraje-prenda/${prendaIdResolved}${qsLectura}`).then(r => r.json())
            ]);
        })
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
            actualizarIndicadorModo('normal', tipoModoGuardado, tieneDatosGuardados);

            // Si existe modo guardado, abrir directamente en ese modo
            const modosValidos = ['normal', 'color', 'pieza', 'mano'];
            const modoInicial = modosValidos.includes(tipoModoGuardado) ? tipoModoGuardado : 'normal';
            const radioModoInicial = document.querySelector(`input[name="modoAnchoMetraje"][value="${modoInicial}"]`);
            if (radioModoInicial) {
                radioModoInicial.checked = true;
            }
            actualizarEstilosModoCards();
            
            console.log('[abrirModalAnchoMetraje] tipo_modo guardado:', tipoModoGuardado, 'tiene datos:', tieneDatosGuardados);
            
            // Determinar si mostrar opcion "POR PIEZA"
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
            asegurarModoSelectorInteractivo();
            
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
            asegurarModoSelectorInteractivo();
        });
    }
}

function getModoLabel(modo) {
    const labels = {
        normal: 'Normal',
        color: 'Por Color',
        pieza: 'Por Pieza',
        mano: 'Manual',
    };
    return labels[modo] || 'Sin definir';
}

function getModoAyuda(modo) {
    const ayudas = {
        normal: 'Un ancho y un metraje para toda la prenda.',
        color: 'Un ancho general y metrajes separados por color.',
        pieza: 'Un ancho general y metrajes por pieza/color.',
        mano: 'Texto libre para casos especiales.',
    };
    return ayudas[modo] || '';
}

function actualizarIndicadorModo(modoActual, modoGuardado, tieneDatosGuardados) {
    const wrapper = document.getElementById('modoActivoInfo');
    const modoActualEl = document.getElementById('modoActivoLabel');
    const ayudaEl = document.getElementById('modoActivoAyuda');
    const modoGuardadoEl = document.getElementById('modoGuardadoLabel');

    if (!wrapper || !modoActualEl || !ayudaEl || !modoGuardadoEl) {
        return;
    }

    wrapper.classList.remove('hidden');

    modoActualEl.textContent = getModoLabel(modoActual);
    ayudaEl.textContent = getModoAyuda(modoActual);

    if (tieneDatosGuardados && modoGuardado) {
        modoGuardadoEl.textContent = `Guardado actualmente: ${getModoLabel(modoGuardado)}.`;
        modoGuardadoEl.classList.remove('hidden');
    } else {
        modoGuardadoEl.classList.add('hidden');
    }
}

function actualizarEstilosModoCards() {
    const cards = document.querySelectorAll('#modoSelector label[data-modo-card]');
    cards.forEach((card) => {
        const radio = card.querySelector('input[type="radio"][name="modoAnchoMetraje"]');
        if (!radio) return;

        if (radio.checked) {
            card.classList.remove('border-slate-200', 'bg-white');
            card.classList.add('border-indigo-400', 'bg-indigo-50', 'shadow-sm');
        } else {
            card.classList.remove('border-indigo-400', 'bg-indigo-50', 'shadow-sm');
            card.classList.add('border-slate-200', 'bg-white');
        }
    });
}

function asegurarModoSelectorInteractivo() {
    const modoSelector = document.getElementById('modoSelector');
    if (!modoSelector) return;

    modoSelector.style.pointerEvents = 'auto';
    modoSelector.classList.remove('pointer-events-none', 'opacity-50');

    const radios = modoSelector.querySelectorAll('input[type="radio"][name="modoAnchoMetraje"]');
    radios.forEach((radio) => {
        radio.disabled = false;
        radio.removeAttribute('disabled');
    });

    const cards = modoSelector.querySelectorAll('label[data-modo-card]');
    cards.forEach((card) => {
        card.style.pointerEvents = 'auto';
        card.setAttribute('aria-disabled', 'false');
    });
}

function leerAnchoCompartidoTemporal(modal) {
    if (!modal) return '';
    return (modal.dataset.anchoCompartido || '').trim();
}

function resolverAnchoGeneralInicial(modal, datosData) {
    const anchoTemporal = leerAnchoCompartidoTemporal(modal);
    if (anchoTemporal) {
        return anchoTemporal;
    }

    let anchoGeneralGuardado = '';
    if (datosData && datosData.success) {
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

    if (anchoGeneralGuardado) {
        modal.dataset.anchoCompartido = String(anchoGeneralGuardado);
    }
    return anchoGeneralGuardado;
}

function registrarSyncAnchoCompartido(input, modal) {
    if (!input || !modal) return;
    input.addEventListener('input', () => {
        modal.dataset.anchoCompartido = input.value.trim();
    });
}

function normalizarClaveColor(valor) {
    return String(valor || '')
        .trim()
        .replace(/\s+/g, ' ')
        .toUpperCase();
}

function requiereConfirmacionCambioModo(modal, modoSeleccionado) {
    const modoGuardado = modal.tipoModoGuardado;
    const tieneDatosGuardados = modal.tieneDatosGuardados;

    if (!modoGuardado || !tieneDatosGuardados) {
        return false;
    }

    // Solo pedir confirmacion de reemplazo al pasar a modo Manual.
    if (modoSeleccionado !== 'mano') {
        return false;
    }

    return modoGuardado !== modoSeleccionado;
}

async function confirmarCambioModoAntesDeGuardar(modal, modoSeleccionado) {
    if (!requiereConfirmacionCambioModo(modal, modoSeleccionado)) {
        return true;
    }

    const modoAnterior = getModoLabel(modal.tipoModoGuardado);
    const modoNuevo = getModoLabel(modoSeleccionado);
    const tituloAdvertencia = 'Cambio de modo';
    const mensaje = `Se eliminaran los datos previos de ancho y metraje guardados en "${modoAnterior}" y se reemplazaran por "${modoNuevo}".`;
    const mensajeHtml = `
        <span style="color:#dc2626;font-weight:800;">SE ELIMINARA</span>
        <span> el ancho y metraje previo guardado en "<strong>${modoAnterior}</strong>" y se reemplazara por "<strong>${modoNuevo}</strong>".</span>
    `;

    if (window.Swal && typeof window.Swal.fire === 'function') {
        const result = await window.Swal.fire({
            title: tituloAdvertencia,
            html: mensajeHtml,
            icon: 'warning',
            target: document.body,
            customClass: {
                container: 'swal-ancho-metraje-superpuesto',
            },
            showCancelButton: true,
            confirmButtonText: 'Guardar y reemplazar',
            cancelButtonText: 'Revisar',
            reverseButtons: true,
        });
        return !!result.isConfirmed;
    }

    return window.confirm(`${tituloAdvertencia}\n\n${mensaje}\n\nÂ¿Deseas continuar?`);
}

function actualizarEstadoGuardadoModal(modal, modo) {
    modal.tipoModoGuardado = modo;
    modal.tieneDatosGuardados = true;
    actualizarIndicadorModo(modo, modal.tipoModoGuardado, modal.tieneDatosGuardados);
}

/**
 * Genera inputs dinamicos para cada color (modo por color)
 * Estructura: Ancho General + Metraje por Color
 */
function generarInputsPorColor(coloresData, datosData) {
    const container = document.getElementById('colorInputsContainer');
    const modal = document.getElementById('modalAnchoMetraje');
    container.innerHTML = '';
    
    // PRIMERO: Crear input de ANCHO GENERAL
    const anchoGeneralDiv = document.createElement('div');
    anchoGeneralDiv.className = 'bg-blue-50 border border-blue-200 rounded-xl p-4 shadow-sm';
    
    // Buscar ancho general: puede estar en datosData.ancho_general o dentro de data[]
    const anchoGeneralGuardado = resolverAnchoGeneralInicial(modal, datosData);
    
    anchoGeneralDiv.innerHTML = `
        <h3 class="font-bold text-gray-800 mb-3 flex items-center gap-2">
            <i class="fas fa-expand-alt text-blue-600"></i>
            Ancho General (se aplica a todos los colores)
        </h3>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Ancho:</label>
            <input 
                type="text" 
                id="anchoGeneralInput"
                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Ingresa ancho..."
                value="${anchoGeneralGuardado}"
            >
        </div>
    `;
    container.appendChild(anchoGeneralDiv);
    registrarSyncAnchoCompartido(anchoGeneralDiv.querySelector('#anchoGeneralInput'), modal);
    
    // SEGUNDO: Crear inputs de METRAJE por color
    const metrajeDiv = document.createElement('div');
    metrajeDiv.className = 'border-t border-slate-200 pt-4';
    
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
            const colorNombreKey = normalizarClaveColor(colorNombre);
            const datosColor = datosData.data.find(d =>
                normalizarClaveColor(d?.color) === colorNombreKey && !d.talla
            );
            if (datosColor) {
                metrajeGuardado = datosColor.metraje || '';
            }
        }
        
        const colorInputDiv = document.createElement('div');
        colorInputDiv.className = 'mb-3 p-3.5 bg-white rounded-xl border border-orange-200 shadow-sm';
        
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
                type="text" 
                class="colorMetraje w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-orange-500"
                placeholder="Ingresa metraje..."
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
 * Genera inputs para talla-color (identico a por color, solo cambia el contenedor)
 * Estructura: Ancho General + Metraje por Color
 */
function generarInputsPorTallaColor(coloresData, datosData) {
    const container = document.getElementById('piezaInputsContainer');
    const modal = document.getElementById('modalAnchoMetraje');
    container.innerHTML = '';
    
    // PRIMERO: Crear input de ANCHO GENERAL
    const anchoGeneralDiv = document.createElement('div');
    anchoGeneralDiv.className = 'bg-blue-50 border border-blue-200 rounded-xl p-4 shadow-sm';
    
    // Buscar ancho general: puede estar en datosData.ancho_general o dentro de data[]
    const anchoGeneralGuardado = resolverAnchoGeneralInicial(modal, datosData);
    
    anchoGeneralDiv.innerHTML = `
        <h3 class="font-bold text-gray-800 mb-3 flex items-center gap-2">
            <i class="fas fa-expand-alt text-blue-600"></i>
            Ancho General (se aplica a todos los colores)
        </h3>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Ancho:</label>
            <input 
                type="text" 
                id="anchoGeneralPiezaInput"
                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Ingresa ancho..."
                value="${anchoGeneralGuardado}"
            >
        </div>
    `;
    container.appendChild(anchoGeneralDiv);
    registrarSyncAnchoCompartido(anchoGeneralDiv.querySelector('#anchoGeneralPiezaInput'), modal);
    
    // SEGUNDO: Crear inputs de METRAJE por color
    const metrajeDiv = document.createElement('div');
    metrajeDiv.className = 'border-t border-slate-200 pt-4';
    
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
            const colorNombreKey = normalizarClaveColor(colorNombre);
            const datosColor = datosData.data.find(d =>
                normalizarClaveColor(d?.color) === colorNombreKey && !d.talla
            );
            if (datosColor) {
                metrajeGuardado = datosColor.metraje || '';
            }
        }
        
        const colorInputDiv = document.createElement('div');
        colorInputDiv.className = 'mb-3 p-3.5 bg-white rounded-xl border border-orange-200 shadow-sm';
        
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
 * Genera inputs dinamicos para entrada por pieza/item
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
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Numero/Item:</label>
                    <input 
                        type="text" 
                        class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="Numero de pieza"
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
    actualizarIndicadorModo(modo, tipoModoGuardado, tieneDatosGuardados);
    actualizarEstilosModoCards();
    
    const normalView = document.getElementById('normalView');
    const colorView = document.getElementById('colorView');
    const piezaView = document.getElementById('piezaView');
    const manoView = document.getElementById('manoView');
    let vistaActiva = null;
    
    // Ocultar todas las vistas
    normalView.classList.add('hidden');
    colorView.classList.add('hidden');
    piezaView.classList.add('hidden');
    manoView.classList.add('hidden');
    
    // Ocultar todos los mensajes de "no hay datos"
    document.getElementById('normalDataWarning')?.classList.add('hidden');
    document.getElementById('colorDataWarning')?.classList.add('hidden');
    document.getElementById('piezaDataWarning')?.classList.add('hidden');
    
    // Mostrar/ocultar boton de eliminar basado en si hay datos guardados y el modo es el mismo
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
        vistaActiva = normalView;
        
        // Cargar datos si estan disponibles en el modal
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
        // MODO COLOR - multiples colores (mismo metraje para todas las tallas)
        colorView.classList.remove('hidden');
        vistaActiva = colorView;
        
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
        // MODO PIEZA - Misma estructura que "Por Color" pero se guardar con tipo_modo='pieza'
        piezaView.classList.remove('hidden');
        vistaActiva = piezaView;
        
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
        vistaActiva = manoView;
        
        // Cargar datos si estan disponibles
        if (modal.datosData && modal.datosData.success) {
            const contenidoMano = modal.datosData.contenido_mano || '';
            document.getElementById('manoTexto').value = contenidoMano;
        } else {
            document.getElementById('manoTexto').value = '';
        }
    }

    llevarVistaModoAlInicio(vistaActiva);
}

function llevarVistaModoAlInicio(vistaActiva) {
    if (!vistaActiva) {
        return;
    }

    const modal = document.getElementById('modalAnchoMetraje');
    const scrollContainer = modal?.querySelector('.overflow-y-auto');

    if (scrollContainer) {
        scrollContainer.scrollTo({ top: 0, behavior: 'smooth' });
    }

    vistaActiva.scrollIntoView({ behavior: 'smooth', block: 'start' });

    const primerCampo = vistaActiva.querySelector('input:not([type="radio"]), textarea, select');
    if (primerCampo && typeof primerCampo.focus === 'function') {
        setTimeout(() => {
            primerCampo.focus({ preventScroll: true });
        }, 220);
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
 * Abre el modal de confirmacion para eliminar ancho/metraje
 */
function abrirModalConfirmacionEliminar() {
    const modalConfirmacion = document.getElementById('modalConfirmacionEliminar');
    modalConfirmacion.classList.remove('hidden');
}

/**
 * Cierra el modal de confirmacion para eliminar ancho/metraje
 */
function cerrarModalConfirmacionEliminar() {
    const modalConfirmacion = document.getElementById('modalConfirmacionEliminar');
    modalConfirmacion.classList.add('hidden');
}

/**
 * Confirma y ejecuta la eliminacion de ancho/metraje
 */
function confirmarEliminarAnchoMetraje() {
    const modal = document.getElementById('modalAnchoMetraje');
    const prendaId = modal.dataset.prendaId;
    const pedido = modal.dataset.pedido;
    const prendaBodegaId = modal.dataset.prendaBodegaId ? parseInt(modal.dataset.prendaBodegaId, 10) : null;
    const tipoRecibo = modal.dataset.tipoRecibo || 'COSTURA';
    const numeroReciboTexto = (document.getElementById('anchoMetrajeRecibo')?.textContent || '').replace('#', '').trim();
    const numeroRecibo = numeroReciboTexto && !Number.isNaN(Number(numeroReciboTexto)) ? Number(numeroReciboTexto) : null;
    
    if (!prendaId) {
        showToast('Error: No se encontro la informacion de la prenda', 'error');
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
            prenda_id: prendaId,
            prenda_bodega_id: prendaBodegaId,
            numero_recibo: numeroRecibo,
            tipo_recibo: tipoRecibo
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Datos eliminados correctamente', 'success');
            cerrarModalConfirmacionEliminar();
            
            // Recargar el modal (vacio)
            setTimeout(() => {
                cerrarModalAnchoMetraje();
                abrirModalAnchoMetraje(pedido, prendaId, prendaBodegaId, pedido, tipoRecibo, modal.dataset.numeroRecibo || null);
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
 * Respeta la seleccion del usuario en el radio button
 */
function guardarAnchoMetraje() {
    const modal = document.getElementById('modalAnchoMetraje');
    const prendaId = modal.dataset.prendaId;
    const pedido = modal.dataset.pedido;
    
    if (!prendaId) {
        showToast('Error: No se encontro la informacion de la prenda', 'error');
        return;
    }
    
    // Obtener modo seleccionado del radio button
    const modoSeleccionado = document.querySelector('input[name="modoAnchoMetraje"]:checked').value;

    confirmarCambioModoAntesDeGuardar(modal, modoSeleccionado).then((puedeContinuar) => {
        if (!puedeContinuar) {
            return;
        }

        guardarAnchoMetrajePorModo(modal, prendaId, pedido, modoSeleccionado);
    });
}

function guardarAnchoMetrajePorModo(modal, prendaId, pedido, modoSeleccionado) {
    const prendaBodegaId = modal.dataset.prendaBodegaId ? parseInt(modal.dataset.prendaBodegaId, 10) : null;
    const tipoRecibo = modal.dataset.tipoRecibo || 'COSTURA';
    const prendaPedidoIdPayload = prendaBodegaId ? null : prendaId;
    const numeroReciboTexto = (document.getElementById('anchoMetrajeRecibo')?.textContent || '').replace('#', '').trim();
    const numeroRecibo = numeroReciboTexto && !Number.isNaN(Number(numeroReciboTexto)) ? Number(numeroReciboTexto) : null;
    const extraPayload = {
        prenda_bodega_id: prendaBodegaId,
        numero_recibo: numeroRecibo,
        tipo_recibo: tipoRecibo,
    };
    
    if (modoSeleccionado === 'normal') {
        // GUARDAR MODO NORMAL
        const anchoVal = document.getElementById('anchoInput').value.trim();
        const metrajeVal = document.getElementById('metrajeInput').value.trim();
        const ancho = anchoVal || null;
        const metraje = metrajeVal || null;
        
        // Guardar datos globales para compatibilidad
        const anchoNum = Number(anchoVal);
        const metrajeNum = Number(metrajeVal);
        const puedeActualizarUniversal = anchoVal !== '' && metrajeVal !== '' &&
            Number.isFinite(anchoNum) && Number.isFinite(metrajeNum);

        if (puedeActualizarUniversal && typeof window.actualizarAnchoMetrajeUniversal === 'function') {
            window.actualizarAnchoMetrajeUniversal(anchoNum, metrajeNum, pedido);
        } else {
            console.warn('[guardarAnchoMetraje] No se actualiza vista universal: ancho/metraje no numericos o helper no disponible.');
            window.datosAnchoMetraje = {
                ancho: anchoVal || null,
                metraje: metrajeVal || null,
                pedido: pedido || 'SIN PEDIDO',
                fecha: new Date().toISOString(),
                modulo: window.location.pathname
            };
            
            // Disparar evento para otros componentes
            window.dispatchEvent(new CustomEvent('anchoMetrajeActualizado', {
                detail: window.datosAnchoMetraje
            }));
        }
        
        // Enviar al servidor (sin color)
        fetch(`/insumos/materiales/${pedido}/guardar-ancho-metraje-prenda`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({
                prenda_pedido_id: prendaPedidoIdPayload,
                color: null,
                tipo_modo: 'normal',
                ancho: ancho,
                metraje: metraje,
                ...extraPayload
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('[guardarAnchoMetraje] Respuesta del servidor:', data);
            
            if (data.success) {
                actualizarEstadoGuardadoModal(modal, modoSeleccionado);
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
        // GUARDAR MODO POR COLOR - Simplificado para evitar codigo duplicado
        const promises = [];
        
        // Guardar ancho general si existe
        const anchoGeneralInput = document.getElementById('anchoGeneralInput');
        if (anchoGeneralInput && anchoGeneralInput.value.trim()) {
            const anchoGeneral = anchoGeneralInput.value.trim();
                promises.push(
                    fetch(`/insumos/materiales/${pedido}/guardar-ancho-metraje-prenda`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                        },
                        body: JSON.stringify({
                            prenda_pedido_id: prendaPedidoIdPayload,
                            color: null,
                            tipo_modo: 'color',
                            ancho: anchoGeneral,
                            metraje: null,
                            ...extraPayload
                        })
                    }).then(r => r.json())
                );
        }
        
        // Guardar metrajes por color
        document.querySelectorAll('#colorInputsContainer .colorMetraje').forEach(input => {
            const colorNombre = (input.dataset.color || '').trim();
            const metrajeVal = input.value.trim();
            
            if (metrajeVal) {
                    promises.push(
                        fetch(`/insumos/materiales/${pedido}/guardar-ancho-metraje-prenda`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                            },
                            body: JSON.stringify({
                                prenda_pedido_id: prendaPedidoIdPayload,
                                color: colorNombre,
                                tipo_modo: 'color',
                                ancho: null,
                                metraje: metrajeVal,
                                ...extraPayload
                            })
                        }).then(r => r.json())
                    );
            }
        });
        
        if (promises.length === 0) {
            showToast('Por favor llena al menos un campo', 'warning');
            return;
        }
        
        Promise.all(promises)
            .then(results => {
                if (results.every(r => r.success)) {
                    actualizarEstadoGuardadoModal(modal, modoSeleccionado);
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
        // GUARDAR MODO POR PIEZA - Identico a color pero con tipo_modo='pieza'
        const promises = [];
        
        // Guardar ancho general si existe
        const anchoGeneralPiezaInput = document.getElementById('anchoGeneralPiezaInput');
        if (anchoGeneralPiezaInput && anchoGeneralPiezaInput.value.trim()) {
            const anchoGeneral = anchoGeneralPiezaInput.value.trim();
                promises.push(
                    fetch(`/insumos/materiales/${pedido}/guardar-ancho-metraje-prenda`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                        },
                        body: JSON.stringify({
                            prenda_pedido_id: prendaPedidoIdPayload,
                            color: null,
                            tipo_modo: 'pieza',
                            ancho: anchoGeneral,
                            metraje: null,
                            ...extraPayload
                        })
                    }).then(r => r.json())
                );
        }
        
        // Guardar metrajes por color (mismos inputs que color)
        document.querySelectorAll('#piezaInputsContainer .colorMetraje').forEach(input => {
            const colorNombre = (input.dataset.color || '').trim();
            const metrajeVal = input.value.trim();
            
            if (metrajeVal) {
                    promises.push(
                        fetch(`/insumos/materiales/${pedido}/guardar-ancho-metraje-prenda`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                            },
                            body: JSON.stringify({
                                prenda_pedido_id: prendaPedidoIdPayload,
                                color: colorNombre,
                                tipo_modo: 'pieza',
                                ancho: null,
                                metraje: metrajeVal,
                                ...extraPayload
                            })
                        }).then(r => r.json())
                    );
            }
        });
        
        if (promises.length === 0) {
            showToast('Por favor llena al menos un campo', 'warning');
            return;
        }
        
        Promise.all(promises)
            .then(results => {
                if (results.every(r => r.success)) {
                    actualizarEstadoGuardadoModal(modal, modoSeleccionado);
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
                prenda_pedido_id: prendaPedidoIdPayload,
                color: null,
                tipo_modo: 'mano',
                ancho: null,
                metraje: null,
                contenido_mano: contenidoMano,
                ...extraPayload
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('[guardarAnchoMetraje] Respuesta del servidor (modo mano):', data);
            
            if (data.success) {
                actualizarEstadoGuardadoModal(modal, modoSeleccionado);
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
        
        // Insertar despues del titulo del recibo
        const receiptTitle = document.getElementById('receipt-title');
        if (receiptTitle) {
            receiptTitle.parentNode.insertBefore(anchoMetrajeElement, receiptTitle.nextSibling);
        }
    }
    
    // Actualizar el contenido
    const anchoTexto = (ancho ?? '').toString().trim();
    const metrajeTexto = (metraje ?? '').toString().trim();
    anchoMetrajeElement.innerHTML = `
        ANCHO DISPONIBLE: ${anchoTexto || '-'}<br>
        METRAJE DISPONIBLE: ${metrajeTexto || '-'}
    `;
    
    console.log('[actualizarReciboConAnchoMetraje] Recibo actualizado con ancho y metraje');
}

function exportModalAnchoMetraje() {
    if (!document.getElementById('swalAnchoMetrajeLayerStyle')) {
        const style = document.createElement('style');
        style.id = 'swalAnchoMetrajeLayerStyle';
        style.textContent = '.swal-ancho-metraje-superpuesto{z-index:1100000 !important;}';
        document.head.appendChild(style);
    }

    document.addEventListener('change', function (event) {
        const target = event.target;
        if (!(target instanceof HTMLInputElement)) {
            return;
        }
        if (target.name !== 'modoAnchoMetraje') {
            return;
        }
        actualizarEstilosModoCards();
        cambiarModoAnchoMetraje({ target });
    });

    document.addEventListener('click', function (event) {
        const target = event.target;
        if (!(target instanceof Element)) {
            return;
        }

        const card = target.closest('#modoSelector label[data-modo-card]');
        if (!card) {
            return;
        }

        const radio = card.querySelector('input[type="radio"][name="modoAnchoMetraje"]');
        if (!radio) {
            return;
        }

        asegurarModoSelectorInteractivo();

        if (!radio.checked) {
            radio.checked = true;
            radio.dispatchEvent(new Event('change', { bubbles: true }));
        } else {
            actualizarEstilosModoCards();
            cambiarModoAnchoMetraje({ target: radio });
        }
    }, true);

    window.insumosHandlers = window.insumosHandlers || {};
    window.insumosHandlers.modalAnchoMetraje = {
        abrirModalAnchoMetraje,
        generarInputsPorColor,
        generarInputsPorTallaColor,
        generarInputsPorPieza,
        cambiarModoAnchoMetraje,
        cerrarModalAnchoMetraje,
        abrirModalConfirmacionEliminar,
        cerrarModalConfirmacionEliminar,
        confirmarEliminarAnchoMetraje,
        guardarAnchoMetraje,
        actualizarReciboConAnchoMetraje,
        asegurarModoSelectorInteractivo,
    };
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', exportModalAnchoMetraje);
} else {
    exportModalAnchoMetraje();
}
