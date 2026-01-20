/**
 * FUNCIONES DE INTEGRACIÓN - Reflectivo Sin Cotización
 * 
 * Este módulo contiene todas las funciones de control que se llaman desde
 * los eventos HTML (onclick, onchange, etc.) para manejar la lógica
 * del flujo de prendas tipo REFLECTIVO sin cotización previa.
 * 
 * Funciones principales:
 * - crearPedidoTipoReflectivoSinCotizacion() - Crear pedido tipo reflectivo
 * - agregarPrendaReflectivoSinCotizacion() - Agregar una prenda nueva
 * - eliminarPrendaReflectivoSinCotizacion() - Eliminar una prenda
 * - abrirGaleriaImagenesReflectivo() - Abrir selector de imágenes
 * - abrirSelectorTallasReflectivo() - Abrir selector de tallas
 */

/**
 * Crear un pedido tipo REFLECTIVO sin cotización previa
 * Se llama cuando se selecciona "Nuevo Pedido" > "REFLECTIVO"
 */
function crearPedidoTipoReflectivoSinCotizacion() {
    console.log(' INICIANDO: Crear pedido tipo REFLECTIVO sin cotización');

    // Mostrar los pasos 2 y 3
    const seccionInfoPrenda = document.getElementById('seccion-info-prenda');
    const seccionPrendas = document.getElementById('seccion-prendas');
    if (seccionInfoPrenda) seccionInfoPrenda.style.display = 'block';
    if (seccionPrendas) seccionPrendas.style.display = 'block';

    //  ACTUALIZAR TÍTULO DINÁMICO
    const tituloPrendasDinamico = document.getElementById('titulo-prendas-dinamico');
    if (tituloPrendasDinamico) {
        tituloPrendasDinamico.innerHTML = 'Nuevo Pedido Reflectivo';
        console.log(' Título dinámico actualizado a REFLECTIVO');
    }

    // Limpiar container
    const container = document.getElementById('prendas-container-editable');
    if (!container) {
        console.error(' Container no encontrado: prendas-container-editable');
        return;
    }

    // Limpiar gestor anterior
    window.gestorReflectivoSinCotizacion = new GestorReflectivoSinCotizacion('prendas-container-editable');

    // Mostrar botón submit
    const btnSubmit = document.getElementById('btn-submit');
    if (btnSubmit) {
        btnSubmit.style.display = 'block';
        btnSubmit.textContent = '✓ Crear Pedido Reflectivo';
    }

    // Limpiar container
    container.innerHTML = '';

    // Agregar primera prenda
    const index = agregarPrendaReflectivoSinCotizacion();
    
    // Mostrar mensaje de bienvenida
    const mensajeUI = document.createElement('div');
    mensajeUI.style.cssText = `
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        border-left: 4px solid #1e40af;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        color: #1e3a5f;
        font-size: 0.9rem;
    `;
    mensajeUI.innerHTML = `
        <i class="fas fa-info-circle"></i> 
        <strong>Nuevo Pedido Reflectivo:</strong> Completa los campos de la prenda. 
        Este pedido contiene una sola prenda reflectivo.
    `;
    container.insertBefore(mensajeUI, container.firstChild);

    logWithEmoji('', 'Formulario de reflectivo creado');
}

/**
 * Agregar una nueva prenda tipo REFLECTIVO
 * @returns {number} Índice de la prenda agregada
 */
function agregarPrendaReflectivoSinCotizacion() {
    console.log('➕ Agregando nueva prenda reflectivo');

    const gestor = window.gestorReflectivoSinCotizacion;
    const container = document.getElementById('prendas-container-editable');

    if (!container || !gestor) {
        console.error(' Gestor o container no disponible');
        return -1;
    }

    // Agregar al gestor
    const index = gestor.agregarPrenda();

    // Renderizar
    const html = renderizarPrendaReflectivoSinCotizacion(gestor.obtenerPorIndice(index), index);
    container.insertAdjacentHTML('beforeend', html);

    // Inicializar secciones
    const prenda = gestor.obtenerPorIndice(index);
    renderizarImagenesReflectivo(index, prenda.fotos || []);
    renderizarTallasReflectivo(index, prenda.tallas || []);
    renderizarUbicacionesReflectivo(index, prenda.ubicaciones || []);
    
    //  NUEVO: Renderizar tallas existentes en generosConTallas
    if (prenda.generosConTallas && Object.keys(prenda.generosConTallas).length > 0) {
        Object.keys(prenda.generosConTallas).forEach(genero => {
            renderizarTallasDelGeneroReflectivo(index, genero);
        });
    }

    // Mostrar botón de agregar más prendas
    mostrarBotonAgregarMasPrendas();

    logWithEmoji('', `Prenda reflectivo ${index + 1} renderizada`);
    return index;
}

/**
 * Eliminar una prenda tipo REFLECTIVO
 * @param {number} index - Índice de la prenda
 */
function eliminarPrendaReflectivoSinCotizacion(index) {
    console.log(`🗑️ Eliminando prenda reflectivo ${index + 1}`);

    if (typeof modalConfirmarEliminarPrenda !== 'function') {
        if (confirm(`¿Eliminar prenda reflectivo ${index + 1}?`)) {
            const card = document.querySelector(`.prenda-card-reflectivo[data-prenda-index="${index}"]`);
            if (card) {
                card.remove();
                window.gestorReflectivoSinCotizacion.eliminar(index);
                logWithEmoji('', `Prenda reflectivo ${index + 1} eliminada`);
            }
        }
    } else {
        modalConfirmarEliminarPrenda(`reflectivo ${index + 1}`).then((result) => {
            if (result.isConfirmed) {
                const card = document.querySelector(`.prenda-card-reflectivo[data-prenda-index="${index}"]`);
                if (card) {
                    card.remove();
                    window.gestorReflectivoSinCotizacion.eliminar(index);
                    logWithEmoji('', `Prenda reflectivo ${index + 1} eliminada`);
                }
            }
        });
    }
}

/**
 * Abrir galería de imágenes para una prenda
 * @param {number} prendaIndex - Índice de la prenda
 */
function abrirGaleriaImagenesReflectivo(prendaIndex) {
    console.log(`📸 Abriendo galería para prenda ${prendaIndex + 1}`);
    const input = document.querySelector(`input.input-file-imagenes-reflectivo[name="imagenes_reflectivo[${prendaIndex}][]"]`);
    if (input) {
        input.click();
    }
}

/**
 * Eliminar una imagen de una prenda
 * @param {number} prendaIndex - Índice de la prenda
 * @param {number} imagenIndex - Índice de la imagen
 */
function eliminarImagenReflectivo(prendaIndex, imagenIndex) {
    console.log(`🗑️ Eliminando imagen ${imagenIndex + 1} de prenda ${prendaIndex + 1}`);

    const gestor = window.gestorReflectivoSinCotizacion;
    gestor.eliminarFoto(prendaIndex, imagenIndex);
    renderizarImagenesReflectivo(prendaIndex, gestor.obtenerFotosNuevas(prendaIndex));

    logWithEmoji('', `Imagen eliminada`);
}

/**
 * Abrir selector de tallas para una prenda
 * @param {number} prendaIndex - Índice de la prenda
 */
function abrirSelectorTallasReflectivo(prendaIndex) {
    console.log(`📏 Abriendo selector de tallas para prenda ${prendaIndex + 1}`);

    // Opciones de tallas disponibles
    const tallasDisponibles = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];
    
    // Obtener tallas ya agregadas
    const prenda = window.gestorReflectivoSinCotizacion.obtenerPorIndice(prendaIndex);
    const tallasAgregadas = prenda ? prenda.tallas : [];

    // Crear opciones HTML
    const opcionesHTML = tallasDisponibles
        .filter(talla => !tallasAgregadas.includes(talla))
        .map(talla => `<option value="${talla}">${talla}</option>`)
        .join('');

    if (opcionesHTML === '') {
        modalAlertaReflectivo('Tallas', 'Ya has agregado todas las tallas disponibles');
        return;
    }

    // Crear modal
    const html = `
        <div style="
            padding: 1.5rem;
            background: white;
            border-radius: 8px;
        ">
            <h3 style="margin-top: 0; margin-bottom: 1rem; font-size: 1.1rem; font-weight: 600;">
                Agregar Talla
            </h3>
            <select id="selector-talla-reflectivo" style="
                width: 100%;
                padding: 0.75rem;
                border: 2px solid #cbd5e1;
                border-radius: 6px;
                font-size: 0.95rem;
                margin-bottom: 1rem;
            ">
                <option value="">-- Selecciona una talla --</option>
                ${opcionesHTML}
            </select>
            <div style="display: flex; gap: 0.75rem;">
                <button type="button" 
                        onclick="confirmarAgregarTallaReflectivo(${prendaIndex})"
                        style="
                            flex: 1;
                            padding: 0.75rem;
                            background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%);
                            color: white;
                            border: none;
                            border-radius: 6px;
                            font-weight: 600;
                            cursor: pointer;
                        ">
                    Agregar Talla
                </button>
                <button type="button" 
                        onclick="cerrarModalTallaReflectivo()"
                        style="
                            flex: 1;
                            padding: 0.75rem;
                            background: #e5e7eb;
                            color: #374151;
                            border: none;
                            border-radius: 6px;
                            font-weight: 600;
                            cursor: pointer;
                        ">
                    Cancelar
                </button>
            </div>
        </div>
    `;

    // Mostrar modal
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            html: html,
            didOpen: () => {
                const selector = document.getElementById('selector-talla-reflectivo');
                if (selector) selector.focus();
            },
            showConfirmButton: false,
            allowOutsideClick: true
        });
    } else {
        alert('Función de selector no disponible');
    }
}

/**
 * Confirmar agregar talla
 * @param {number} prendaIndex - Índice de la prenda
 */
window.confirmarAgregarTallaReflectivo = function(prendaIndex) {
    const select = document.getElementById('selector-talla-reflectivo');
    if (!select || !select.value) {
        alert('Selecciona una talla');
        return;
    }

    const talla = select.value;
    const gestor = window.gestorReflectivoSinCotizacion;
    gestor.agregarTalla(prendaIndex, talla);

    renderizarTallasReflectivo(prendaIndex, gestor.obtenerPorIndice(prendaIndex).tallas);

    if (typeof Swal !== 'undefined') {
        Swal.close();
    }

    logWithEmoji('', `Talla ${talla} agregada a prenda ${prendaIndex + 1}`);
};

/**
 * Cerrar modal de talla
 */
window.cerrarModalTallaReflectivo = function() {
    if (typeof Swal !== 'undefined') {
        Swal.close();
    }
};

/**
 * Eliminar una talla de una prenda
 * @param {number} prendaIndex - Índice de la prenda
 * @param {string} talla - Talla a eliminar
 */
function eliminarTallaReflectivo(prendaIndex, talla) {
    console.log(`🗑️ Eliminando talla ${talla} de prenda ${prendaIndex + 1}`);

    const gestor = window.gestorReflectivoSinCotizacion;
    gestor.eliminarTalla(prendaIndex, talla);
    renderizarTallasReflectivo(prendaIndex, gestor.obtenerPorIndice(prendaIndex).tallas);

    logWithEmoji('', `Talla ${talla} eliminada`);
}

/**
 * Actualizar nombre/tipo de prenda
 * @param {number} prendaIndex - Índice de la prenda
 * @param {string} valor - Nuevo valor
 */
function actualizarNombrePrendaReflectivo(prendaIndex, valor) {
    const prenda = window.gestorReflectivoSinCotizacion.obtenerPorIndice(prendaIndex);
    if (prenda) {
        prenda.nombre_producto = valor;
    }
}

/**
 * Actualizar descripción de prenda
 * @param {number} prendaIndex - Índice de la prenda
 * @param {string} valor - Nuevo valor
 */
function actualizarDescripcionPrendaReflectivo(prendaIndex, valor) {
    const prenda = window.gestorReflectivoSinCotizacion.obtenerPorIndice(prendaIndex);
    if (prenda) {
        prenda.descripcion = valor;
    }
}

/**
 * Actualizar género de prenda
 * @param {number} prendaIndex - Índice de la prenda
 * @param {string} genero - Género (Dama/Caballero)
 */
function actualizarGeneroReflectivo(prendaIndex, genero) {
    const gestor = window.gestorReflectivoSinCotizacion;
    const prenda = gestor.obtenerPorIndice(prendaIndex);
    if (!prenda) return;
    
    // Recopilar géneros seleccionados (checkboxes)
    const prendaCard = document.querySelector(`.prenda-card-reflectivo[data-prenda-index="${prendaIndex}"]`);
    if (!prendaCard) return;
    
    const generosSeleccionados = [];
    const checkDama = prendaCard.querySelector('input[name*="genero_reflectivo_dama"]');
    const checkCaballero = prendaCard.querySelector('input[name*="genero_reflectivo_caballero"]');
    
    if (checkDama && checkDama.checked) {
        generosSeleccionados.push('dama');
    }
    if (checkCaballero && checkCaballero.checked) {
        generosSeleccionados.push('caballero');
    }
    
    // Actualizar prenda con géneros seleccionados
    prenda.generosSeleccionados = generosSeleccionados;
    
    // Mostrar/ocultar secciones de tallas
    const seccionDama = prendaCard.querySelector('.genero-dama-section');
    const seccionCaballero = prendaCard.querySelector('.genero-caballero-section');
    
    if (seccionDama) {
        seccionDama.style.display = checkDama && checkDama.checked ? 'block' : 'none';
    }
    if (seccionCaballero) {
        seccionCaballero.style.display = checkCaballero && checkCaballero.checked ? 'block' : 'none';
    }
    
    logWithEmoji('✏️', `Géneros seleccionados: ${generosSeleccionados.join(', ') || 'ninguno'}`);
}

/**
 * Mostrar botón para agregar más prendas
 */
function mostrarBotonAgregarMasPrendas() {
    const container = document.getElementById('prendas-container-editable');
    if (!container) return;

    //  REFLECTIVO: NO mostrar botón agregar más prendas (solo se permite 1 prenda)
    const btnAnterior = container.querySelector('.btn-agregar-mas-prendas-reflectivo');
    if (btnAnterior) {
        btnAnterior.remove();
    }
    
    console.log(' No se muestra botón "Agregar Prenda" en tipo Reflectivo (máximo 1 prenda permitida)');
}

/**
 * Modal de alerta simple
 * @param {string} titulo - Título del modal
 * @param {string} mensaje - Mensaje
 */
function modalAlertaReflectivo(titulo, mensaje) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: titulo,
            text: mensaje,
            icon: 'info',
            confirmButtonColor: '#1e40af',
            confirmButtonText: 'OK'
        });
    } else {
        alert(`${titulo}\n\n${mensaje}`);
    }
}

/**
 * Modal para confirmar eliminar prenda
 * @param {string} nombre - Nombre de la prenda
 * @returns {Promise}
 */
function modalConfirmarEliminarPrenda(nombre) {
    if (typeof Swal !== 'undefined') {
        return Swal.fire({
            title: 'Eliminar prenda',
            text: `¿Estás seguro de que quieres eliminar la prenda ${nombre}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#1e40af',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        });
    } else {
        return Promise.resolve({
            isConfirmed: confirm(`¿Eliminar prenda ${nombre}?`)
        });
    }
}

/**
 * Actualizar selector de tallas reflectivo (cuando selecciona tipo de talla)
 */
window.actualizarSelectTallasReflectivo = function(selectElement) {
    const tipoTalla = selectElement.value;
    const fila = selectElement.closest('.tipo-prenda-row');
    
    const modoSelect = fila.querySelector('.talla-modo-select-reflectivo');
    const rangoDiv = fila.querySelector('.talla-rango-selectors-reflectivo');
    const botonesDiv = fila.querySelector('.talla-botones-reflectivo');
    
    if (tipoTalla === '') {
        modoSelect.style.display = 'none';
        rangoDiv.style.display = 'none';
        botonesDiv.style.display = 'none';
        return;
    }
    
    // Mostrar selector de modo
    modoSelect.style.display = 'block';
    modoSelect.value = '';
    
    // Ocultar botones y rango
    rangoDiv.style.display = 'none';
    botonesDiv.style.display = 'none';
    
    // Variables globales de tallas (COMO EN PRENDA)
    const tallasLetras = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'];
    const tallasDama = ['6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26'];
    const tallasCaballero = ['28', '30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50'];
    
    // Obtener selectores de rango
    const selectDesde = fila.querySelector('.talla-desde-reflectivo');
    const selectHasta = fila.querySelector('.talla-hasta-reflectivo');
    
    if (tipoTalla === 'letra') {
        // LETRAS: mostrar todas las letras
        selectDesde.innerHTML = '<option value="">Desde</option>' + tallasLetras.map(t => `<option value="${t}">${t}</option>`).join('');
        selectHasta.innerHTML = '<option value="">Hasta</option>' + tallasLetras.map(t => `<option value="${t}">${t}</option>`).join('');
    } else if (tipoTalla === 'numero') {
        // NÚMEROS: obtener el género de la prenda
        const prendaCard = fila.closest('.prenda-card-reflectivo');
        const selectGenero = prendaCard ? prendaCard.querySelector('.genero-radio-reflectivo:checked') : null;
        const genero = selectGenero ? selectGenero.value : null;
        
        if (!genero) {
            modalAlertaReflectivo('Género requerido', 'Por favor selecciona un género (Dama/Caballero) antes de elegir NÚMEROS');
            selectElement.value = '';
            modoSelect.style.display = 'none';
            return;
        }
        
        const tallas = genero === 'Dama' ? tallasDama : tallasCaballero;
        selectDesde.innerHTML = '<option value="">Desde</option>' + tallas.map(t => `<option value="${t}">${t}</option>`).join('');
        selectHasta.innerHTML = '<option value="">Hasta</option>' + tallas.map(t => `<option value="${t}">${t}</option>`).join('');
    }
};

/**
 * Actualizar modo de selección de tallas (manual o rango)
 */
window.actualizarModoTallasReflectivo = function(selectElement) {
    const modo = selectElement.value;
    const fila = selectElement.closest('.tipo-prenda-row');
    
    const rangoDiv = fila.querySelector('.talla-rango-selectors-reflectivo');
    const botonesDiv = fila.querySelector('.talla-botones-reflectivo');
    const tallasBotones = fila.querySelector('.talla-botones-container-reflectivo');
    
    // Variables globales de tallas
    const tallasLetras = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'];
    const tallasDama = ['6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26'];
    const tallasCaballero = ['28', '30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50'];
    
    if (modo === 'manual') {
        rangoDiv.style.display = 'none';
        botonesDiv.style.display = 'block';
        
        // Obtener tipo de talla
        const tipoSelect = fila.querySelector('.talla-tipo-select-reflectivo');
        const tipoTalla = tipoSelect.value;
        
        // Obtener género de la prenda si es NÚMEROS
        let tallasParaMostrar = [];
        if (tipoTalla === 'letra') {
            tallasParaMostrar = tallasLetras;
        } else if (tipoTalla === 'numero') {
            const prendaCard = fila.closest('.prenda-card-reflectivo');
            const selectGenero = prendaCard ? prendaCard.querySelector('.genero-radio-reflectivo:checked') : null;
            const genero = selectGenero ? selectGenero.value : null;
            
            if (genero === 'Dama') {
                tallasParaMostrar = tallasDama;
            } else if (genero === 'Caballero') {
                tallasParaMostrar = tallasCaballero;
            } else {
                modalAlertaReflectivo('Género requerido', 'Por favor selecciona un género');
                selectElement.value = '';
                botonesDiv.style.display = 'none';
                return;
            }
        }
        
        // Generar botones de tallas
        tallasBotones.innerHTML = tallasParaMostrar.map(talla => `
            <button type="button" class="btn-talla-selector-reflectivo" data-talla="${talla}" style="
                padding: 0.5rem 1rem;
                background: white;
                border: 2px solid #1e40af;
                color: #1e40af;
                border-radius: 20px;
                cursor: pointer;
                font-weight: 600;
                font-size: 0.85rem;
                transition: all 0.2s ease;
            " onclick="seleccionarTallaManualReflectivo(this)">
                ${talla}
            </button>
        `).join('');
    } else if (modo === 'rango') {
        rangoDiv.style.display = 'flex';
        botonesDiv.style.display = 'none';
    } else {
        rangoDiv.style.display = 'none';
        botonesDiv.style.display = 'none';
    }
};

/**
 * Seleccionar talla en modo manual
 */
window.seleccionarTallaManualReflectivo = function(boton) {
    boton.style.background = boton.style.background === 'rgb(30, 64, 175)' ? 'white' : '#1e40af';
    boton.style.color = boton.style.color === 'rgb(30, 64, 175)' ? 'white' : '#1e40af';
};

/**
 * Agregar tallas seleccionadas en modo manual
 */
window.agregarTallasSeleccionadasReflectivo = function(boton) {
    const fila = boton.closest('.tipo-prenda-row');
    const prendaIndex = parseInt(fila.dataset.prendaIndex);
    
    const botonesSeleccionados = fila.querySelectorAll('.btn-talla-selector-reflectivo');
    const tallasParaAgregar = [];
    
    botonesSeleccionados.forEach(btn => {
        if (btn.style.background === 'rgb(30, 64, 175)') {
            tallasParaAgregar.push(btn.dataset.talla);
        }
    });
    
    if (tallasParaAgregar.length === 0) {
        modalAlertaReflectivo('Selección vacía', 'Selecciona al menos una talla');
        return;
    }
    
    const gestor = window.gestorReflectivoSinCotizacion;
    tallasParaAgregar.forEach(talla => {
        gestor.agregarTalla(prendaIndex, talla);
    });
    
    renderizarTallasReflectivo(prendaIndex, gestor.obtenerPorIndice(prendaIndex).tallas);
    
    // Deseleccionar botones para permitir agregar más
    botonesSeleccionados.forEach(btn => {
        btn.style.background = 'white';
        btn.style.color = '#1e40af';
    });
    
    logWithEmoji('', `${tallasParaAgregar.length} talla(s) agregada(s)`);
};

/**
 * Agregar tallas por rango
 */
window.agregarTallasRangoReflectivo = function(boton) {
    const fila = boton.closest('.tipo-prenda-row');
    const prendaIndex = parseInt(fila.dataset.prendaIndex);
    
    const selectDesde = fila.querySelector('.talla-desde-reflectivo').value;
    const selectHasta = fila.querySelector('.talla-hasta-reflectivo').value;
    
    if (!selectDesde || !selectHasta) {
        modalAlertaReflectivo('Rango incompleto', 'Selecciona tanto "Desde" como "Hasta"');
        return;
    }
    
    // Variables globales de tallas
    const tallasLetras = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'];
    const tallasDama = ['6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26'];
    const tallasCaballero = ['28', '30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50'];
    
    const tipoSelect = fila.querySelector('.talla-tipo-select-reflectivo');
    const tipoTalla = tipoSelect.value;
    
    let tallasDisponibles = [];
    
    if (tipoTalla === 'letra') {
        tallasDisponibles = tallasLetras;
    } else if (tipoTalla === 'numero') {
        const prendaCard = fila.closest('.prenda-card-reflectivo');
        const selectGenero = prendaCard ? prendaCard.querySelector('.genero-radio-reflectivo:checked') : null;
        const genero = selectGenero ? selectGenero.value : null;
        
        if (genero === 'Dama') {
            tallasDisponibles = tallasDama;
        } else if (genero === 'Caballero') {
            tallasDisponibles = tallasCaballero;
        }
    }
    
    const idxDesde = tallasDisponibles.indexOf(selectDesde);
    const idxHasta = tallasDisponibles.indexOf(selectHasta);
    
    if (idxDesde === -1 || idxHasta === -1 || idxDesde > idxHasta) {
        modalAlertaReflectivo('Rango inválido', 'El rango debe ser válido');
        return;
    }
    
    const tallasAgregar = tallasDisponibles.slice(idxDesde, idxHasta + 1);
    
    const gestor = window.gestorReflectivoSinCotizacion;
    tallasAgregar.forEach(talla => {
        gestor.agregarTalla(prendaIndex, talla);
    });
    
    renderizarTallasReflectivo(prendaIndex, gestor.obtenerPorIndice(prendaIndex).tallas);
    
    // Limpiar selects de rango para agregar más
    fila.querySelector('.talla-desde-reflectivo').value = '';
    fila.querySelector('.talla-hasta-reflectivo').value = '';
    
    logWithEmoji('', `${tallasAgregar.length} talla(s) agregada(s) por rango`);
};

/**
 * Actualizar cantidad de talla
 */
window.actualizarCantidadTallaReflectivo = function(prendaIndex, talla, cantidad) {
    const gestor = window.gestorReflectivoSinCotizacion;
    const prenda = gestor.obtenerPorIndice(prendaIndex);
    
    if (!prenda.cantidadesPorTalla) {
        prenda.cantidadesPorTalla = {};
    }
    
    prenda.cantidadesPorTalla[talla] = parseInt(cantidad) || 0;
    logWithEmoji('', `Cantidad de ${talla} actualizada a ${cantidad}`);
};

// Agregar listener para sincronizar cantidades de tallas
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('talla-cantidad-reflectivo')) {
        const prendaIndex = parseInt(e.target.dataset.prenda);
        const talla = e.target.dataset.talla;
        const cantidad = e.target.value;
        window.actualizarCantidadTallaReflectivo(prendaIndex, talla, cantidad);
    }
}, true);

/**
 * Abrir modal para agregar ubicación
 */
window.abrirModalAgregarUbicacionReflectivo = function(prendaIndex) {
    const modalHTML = renderizarModalAgregarUbicacionReflectivo(prendaIndex);
    const modalContainer = document.createElement('div');
    modalContainer.id = `modal-ubicacion-reflectivo-${prendaIndex}`;
    modalContainer.innerHTML = modalHTML;
    document.body.appendChild(modalContainer);

    // Focus en el input
    setTimeout(() => {
        const input = document.getElementById('modal-ubicacion-input-reflectivo');
        if (input) input.focus();
    }, 100);
};

/**
 * Cerrar modal de ubicación
 */
window.cerrarModalAgregarUbicacionReflectivo = function() {
    const modales = document.querySelectorAll('[id^="modal-ubicacion-reflectivo-"]');
    modales.forEach(modal => modal.remove());
};

/**
 * Guardar ubicación
 */
window.guardarUbicacionReflectivo = function(prendaIndex) {
    const ubicacionInput = document.getElementById('modal-ubicacion-input-reflectivo');
    const observacionesInput = document.getElementById('modal-observaciones-input-reflectivo');

    if (!ubicacionInput || !observacionesInput) return;

    const ubicacion = ubicacionInput.value.trim();
    const observaciones = observacionesInput.value.trim();

    if (!ubicacion) {
        modalAlertaReflectivo('Ubicación requerida', 'Por favor ingresa el nombre de la ubicación');
        return;
    }

    const gestor = window.gestorReflectivoSinCotizacion;
    gestor.agregarUbicacion(prendaIndex, ubicacion, observaciones);

    // Limpiar input en la tarjeta
    const card = document.querySelector(`.prenda-card-reflectivo[data-prenda-index="${prendaIndex}"] .ubicacion-input-reflectivo`);
    if (card) {
        card.value = '';
    }

    // Renderizar ubicaciones
    const ubicaciones = gestor.obtenerUbicaciones(prendaIndex);
    renderizarUbicacionesReflectivo(prendaIndex, ubicaciones);

    // Cerrar modal
    window.cerrarModalAgregarUbicacionReflectivo();

    logWithEmoji('', `Ubicación "${ubicacion}" guardada para prenda ${prendaIndex + 1}`);
};

/**
 * Eliminar ubicación
 */
window.eliminarUbicacionReflectivo = function(prendaIndex, ubicacionId) {
    const gestor = window.gestorReflectivoSinCotizacion;
    gestor.eliminarUbicacion(prendaIndex, ubicacionId);

    // Renderizar ubicaciones actualizadas
    const ubicaciones = gestor.obtenerUbicaciones(prendaIndex);
    renderizarUbicacionesReflectivo(prendaIndex, ubicaciones);

    logWithEmoji('🗑️', `Ubicación eliminada de prenda ${prendaIndex + 1}`);
};

/**
 * ENVIAR PEDIDO REFLECTIVO AL SERVIDOR
 */
window.enviarReflectivoSinCotizacion = function() {
    return new Promise(async (resolve, reject) => {
        try {
            const gestor = window.gestorReflectivoSinCotizacion;

            //  CORREGIDO: Usar datos del gestor directamente en lugar del DOM
            // El gestor ya tiene los datos actualizados
            const prendas = gestor.obtenerActivas();
            
            // Validar
            const validacion = gestor.validar();
            if (!validacion.valido) {
                console.error(' Validación fallida:', validacion.errores);
                let mensajeError = 'Errores de validación:\n';
                validacion.errores.forEach(error => {
                    mensajeError += `• ${error}\n`;
                });
                Swal.fire('Error', mensajeError, 'error');
                reject(new Error('Validación fallida'));
                return;
            }

            // Obtener datos generales
            const cliente = document.getElementById('cliente_editable')?.value;
            const formaPago = document.getElementById('forma_de_pago_editable')?.value || '';
            const asesora = document.getElementById('asesora_editable')?.value || '';

            if (!cliente) {
                Swal.fire('Error', 'El cliente es requerido', 'error');
                reject(new Error('Cliente requerido'));
                return;
            }

            logWithEmoji('📤', 'Enviando pedido REFLECTIVO sin cotización', {
                cliente,
                formaPago,
                asesora,
                prendas: prendas
            });

            // Usar FormData para enviar archivos
            const formData = new FormData();
            formData.append('cliente', cliente);
            formData.append('forma_de_pago', formaPago);
            formData.append('asesora', asesora);
            formData.append('_token', document.querySelector('input[name="_token"]')?.value || '');

            // Agregar prendas desde el gestor
            prendas.forEach((prenda, index) => {
                formData.append(`prendas[${index}][nombre_producto]`, prenda.nombre_producto || '');
                formData.append(`prendas[${index}][descripcion]`, prenda.descripcion || '');
                //  CORREGIDO: Enviar generosSeleccionados en lugar de genero
                const generosStr = (prenda.generosSeleccionados && prenda.generosSeleccionados.length > 0) 
                    ? JSON.stringify(prenda.generosSeleccionados) 
                    : '';
                formData.append(`prendas[${index}][genero]`, generosStr);

                //  NUEVO: Usar generosConTallas en lugar de cantidadesPorTalla
                if (prenda.generosConTallas && Object.keys(prenda.generosConTallas).length > 0) {
                    Object.entries(prenda.generosConTallas).forEach(([genero, tallas]) => {
                        Object.entries(tallas).forEach(([talla, cantidad]) => {
                            if (cantidad > 0) {
                                formData.append(`prendas[${index}][cantidad_talla][${genero}][${talla}]`, cantidad);
                            }
                        });
                    });
                } else if (prenda.cantidadesPorTalla) {
                    // Fallback: si no hay generosConTallas, usar el antiguo cantidadesPorTalla
                    Object.entries(prenda.cantidadesPorTalla).forEach(([talla, cantidad]) => {
                        if (cantidad > 0) {
                            formData.append(`prendas[${index}][cantidades][${talla}]`, cantidad);
                        }
                    });
                }

                // Ubicaciones
                const ubicaciones = gestor.obtenerUbicaciones(index);
                if (ubicaciones && ubicaciones.length > 0) {
                    ubicaciones.forEach((ubicacion, ubIndex) => {
                        formData.append(`prendas[${index}][ubicaciones][${ubIndex}][nombre]`, ubicacion.nombre || '');
                        formData.append(`prendas[${index}][ubicaciones][${ubIndex}][observaciones]`, ubicacion.observaciones || '');
                    });
                }
            });

            // Agregar imágenes
            logWithEmoji('📸', 'Procesando imágenes de reflectivos...');
            
            // Obtener fotos del gestor
            prendas.forEach((prenda, prendaIndex) => {
                const fotosNuevas = gestor.obtenerFotosNuevas(prendaIndex);
                if (Array.isArray(fotosNuevas) && fotosNuevas.length > 0) {
                    fotosNuevas.forEach((foto, fotoIndex) => {
                        const archivo = foto instanceof File ? foto : (foto && foto.file instanceof File ? foto.file : null);
                        
                        if (archivo) {
                            formData.append(`prendas[${prendaIndex}][fotos][]`, archivo);
                            logWithEmoji('', `Imagen de reflectivo ${prendaIndex + 1} agregada: ${archivo.name}`);
                        } else if (typeof foto === 'string') {
                            formData.append(`prendas[${prendaIndex}][fotos_existentes][]`, foto);
                        }
                    });
                }
            });
            
            // Fallback: también revisar window.fotosReflectivoSinCotizacion por compatibilidad
            if (window.fotosReflectivoSinCotizacion) {
                Object.entries(window.fotosReflectivoSinCotizacion).forEach(([prendaIndex, fotos]) => {
                    if (Array.isArray(fotos)) {
                        fotos.forEach((foto, fotoIndex) => {
                            const archivo = foto instanceof File ? foto : (foto && foto.file instanceof File ? foto.file : null);
                            
                            if (archivo) {
                                formData.append(`prendas[${prendaIndex}][fotos][]`, archivo);
                                logWithEmoji('', `Imagen de reflectivo ${prendaIndex + 1} agregada: ${archivo.name}`);
                            } else if (typeof foto === 'string') {
                                formData.append(`prendas[${prendaIndex}][fotos_existentes][]`, foto);
                            }
                        });
                    }
                });
            }

            // Enviar al servidor
            const response = await fetch('/asesores/pedidos-produccion/crear-reflectivo-sin-cotizacion', {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`Error del servidor: ${response.statusText}`);
            }

            const data = await response.json();

            if (data.success) {
                logWithEmoji('', 'Pedido REFLECTIVO creado exitosamente', data);
                
                Swal.fire({
                    icon: 'success',
                    title: '¡Pedido creado!',
                    text: `Pedido ${data.pedido_id} creado exitosamente`,
                    confirmButtonText: 'OK'
                }).then(() => {
                    // Redirigir después de 2 segundos
                    setTimeout(() => {
                        window.location.href = '/asesores/pedidos';
                    }, 2000);
                });

                resolve(data);
            } else {
                throw new Error(data.message || 'Error desconocido en el servidor');
            }
        } catch (error) {
            console.error(' Error al enviar pedido REFLECTIVO:', error);
            Swal.fire('Error', error.message || 'Error al crear el pedido', 'error');
            reject(error);
        }
    });
};

/**
 * SISTEMA DE TALLAS CON GÉNEROS - Reflectivo (IGUAL A PRENDA)
 * Replica exactamente la lógica de PRENDA para consistencia
 */

/**
 * Abrir flujo de selección de géneros y tallas
 */
window.agregarTallasAlGeneroReflectivo = function(prendaIndex, genero) {
    console.log(` Abriendo flujo de tallas para género: ${genero}`);
    
    const prendaCard = document.querySelector(`.prenda-card-reflectivo[data-prenda-index="${prendaIndex}"]`);
    if (!prendaCard) {
        console.error(` Prenda card no encontrada`);
        return;
    }

    const prenda = window.gestorReflectivoSinCotizacion.obtenerPorIndice(prendaIndex);
    if (!prenda) {
        console.error(` Prenda no encontrada en gestor`);
        return;
    }

    // Inicializar estructura
    if (!prenda.generosConTallas) {
        prenda.generosConTallas = {};
    }
    if (!prenda.generosConTallas[genero]) {
        prenda.generosConTallas[genero] = {};
    }

    // Mostrar modal para elegir tipo de talla (Letra vs Número)
    Swal.fire({
        title: `Agregar Tallas - ${genero.toUpperCase()}`,
        html: `
            <div style="display: flex; gap: 1rem; justify-content: center; padding: 1rem;">
                <button type="button" id="btn-letra" style="flex: 1; padding: 1rem; border: 2px solid #e5e7eb; border-radius: 8px; background: white; cursor: pointer; font-weight: 600; font-size: 0.95rem; transition: all 0.3s;">
                    <div style="font-size: 1.5rem; margin-bottom: 0.5rem;"><i class="fas fa-font" style="color: #1e40af;"></i></div>
                    <div>LETRAS</div>
                    <div style="font-size: 0.75rem; color: #666; margin-top: 0.5rem;">XS, S, M, L, XL...</div>
                </button>
                <button type="button" id="btn-numero" style="flex: 1; padding: 1rem; border: 2px solid #e5e7eb; border-radius: 8px; background: white; cursor: pointer; font-weight: 600; font-size: 0.95rem; transition: all 0.3s;">
                    <div style="font-size: 1.5rem; margin-bottom: 0.5rem;"><i class="fas fa-numbers" style="color: #1e40af;"></i></div>
                    <div>NÚMEROS</div>
                    <div style="font-size: 0.75rem; color: #666; margin-top: 0.5rem;">Dama/Caballero</div>
                </button>
            </div>
        `,
        showConfirmButton: false,
        didOpen: () => {
            document.getElementById('btn-letra').addEventListener('click', () => {
                Swal.close();
                agregarTallasPorMetodoReflectivo(prendaIndex, genero, 'letra');
            });
            document.getElementById('btn-numero').addEventListener('click', () => {
                Swal.close();
                agregarTallasPorMetodoReflectivo(prendaIndex, genero, 'numero');
            });
        }
    });
};

/**
 * Paso 2: Elegir método (Manual o Rango)
 */
window.agregarTallasPorMetodoReflectivo = function(prendaIndex, genero, tipoTalla) {
    const tallasLetra = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'];
    const tallasDama = ['6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26'];
    const tallasCaballero = ['28', '30', '32', '34', '36', '38', '40', '42', '44', '46'];
    
    let tallasPorTipo;
    if (tipoTalla === 'letra') {
        tallasPorTipo = tallasLetra;
    } else {
        tallasPorTipo = (genero === 'dama') ? tallasDama : tallasCaballero;
    }
    
    const prendaCard = document.querySelector(`.prenda-card-reflectivo[data-prenda-index="${prendaIndex}"]`);
    if (!prendaCard) return;
    
    const tallasActuales = Array.from(prendaCard.querySelectorAll(`.talla-cantidad-genero-editable[data-prenda="${prendaIndex}"][data-genero="${genero}"]`))
        .map(input => input.dataset.talla);
    
    const tallasDisponibles = tallasPorTipo.filter(talla => !tallasActuales.includes(talla));
    
    if (tallasDisponibles.length === 0) {
        Swal.fire({
            icon: 'info',
            title: 'Sin tallas disponibles',
            text: `Ya tienes todas las tallas de ${tipoTalla === 'letra' ? 'LETRA' : 'NÚMERO'} agregadas`
        });
        return;
    }
    
    Swal.fire({
        title: 'Método de Selección',
        html: `
            <div style="display: flex; gap: 1rem; justify-content: center; padding: 1rem;">
                <button type="button" id="btn-manual" style="flex: 1; padding: 1rem; border: 2px solid #e5e7eb; border-radius: 8px; background: white; cursor: pointer; font-weight: 600; font-size: 0.95rem; transition: all 0.3s;">
                    <div style="font-size: 1.5rem; margin-bottom: 0.5rem;"><i class="fas fa-hand-pointer" style="color: #1e40af;"></i></div>
                    <div>MANUAL</div>
                    <div style="font-size: 0.75rem; color: #666; margin-top: 0.5rem;">Una por una</div>
                </button>
                <button type="button" id="btn-rango" style="flex: 1; padding: 1rem; border: 2px solid #e5e7eb; border-radius: 8px; background: white; cursor: pointer; font-weight: 600; font-size: 0.95rem; transition: all 0.3s;">
                    <div style="font-size: 1.5rem; margin-bottom: 0.5rem;"><i class="fas fa-sliders-h" style="color: #1e40af;"></i></div>
                    <div>RANGO</div>
                    <div style="font-size: 0.75rem; color: #666; margin-top: 0.5rem;">Desde... hasta</div>
                </button>
            </div>
        `,
        showConfirmButton: false,
        didOpen: () => {
            document.getElementById('btn-manual').addEventListener('click', () => {
                Swal.close();
                seleccionarTallasManualReflectivo(prendaIndex, genero, tallasDisponibles, tipoTalla);
            });
            document.getElementById('btn-rango').addEventListener('click', () => {
                Swal.close();
                seleccionarTallasRangoReflectivo(prendaIndex, genero, tallasPorTipo, tallasActuales, tipoTalla);
            });
        }
    });
};

/**
 * Paso 3A: Selección MANUAL
 */
window.seleccionarTallasManualReflectivo = function(prendaIndex, genero, tallasDisponibles, tipoTalla) {
    const generoLabel = genero.charAt(0).toUpperCase() + genero.slice(1);
    
    Swal.fire({
        title: `Agregar Tallas - ${generoLabel} (MANUAL)`,
        html: `
            <div style="max-height: 400px; overflow-y: auto; padding: 1rem;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(80px, 1fr)); gap: 0.5rem;">
                    ${tallasDisponibles.map(talla => `
                        <button type="button" class="btn-talla-manual-reflectivo" data-talla="${talla}" 
                                style="padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 6px; background: white; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.3s;">
                            ${talla}
                        </button>
                    `).join('')}
                </div>
            </div>
            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                <div style="font-size: 0.85rem; color: #666; font-weight: 500;">Tallas seleccionadas: <span id="contador-tallas">0</span></div>
                <div id="lista-tallas-seleccionadas" style="margin-top: 0.5rem; display: flex; flex-wrap: wrap; gap: 0.5rem;"></div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Agregar',
        cancelButtonText: 'Cancelar',
        didOpen: () => {
            const tallasSeleccionadas = new Set();
            
            document.querySelectorAll('.btn-talla-manual-reflectivo').forEach(btn => {
                btn.addEventListener('click', function() {
                    const talla = this.dataset.talla;
                    
                    if (tallasSeleccionadas.has(talla)) {
                        tallasSeleccionadas.delete(talla);
                        this.style.background = 'white';
                        this.style.borderColor = '#e5e7eb';
                        this.classList.remove('btn-talla-seleccionada');
                    } else {
                        tallasSeleccionadas.add(talla);
                        this.style.background = '#1e40af';
                        this.style.color = 'white';
                        this.style.borderColor = '#1e40af';
                        this.classList.add('btn-talla-seleccionada');
                    }
                    
                    document.getElementById('contador-tallas').textContent = tallasSeleccionadas.size;
                    document.getElementById('lista-tallas-seleccionadas').innerHTML = 
                        Array.from(tallasSeleccionadas).map(t => `<span style="background: #e3f2fd; color: #1e40af; padding: 0.3rem 0.6rem; border-radius: 4px; font-size: 0.8rem; font-weight: 600;">${t}</span>`).join('');
                });
            });
        },
        preConfirm: () => {
            const contador = parseInt(document.getElementById('contador-tallas').textContent);
            if (contador === 0) {
                Swal.showValidationMessage('Selecciona al menos una talla');
                return false;
            }
            return Array.from(document.querySelectorAll('.btn-talla-manual-reflectivo.btn-talla-seleccionada')).map(btn => btn.dataset.talla);
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            agregarTallasAlGeneroReflectivo_Interno(prendaIndex, genero, result.value, tipoTalla);
        }
    });
};

/**
 * Paso 3B: Selección por RANGO
 */
window.seleccionarTallasRangoReflectivo = function(prendaIndex, genero, todasLasTallas, tallasActuales, tipoTalla) {
    const generoLabel = genero.charAt(0).toUpperCase() + genero.slice(1);
    const tallasDisponibles = todasLasTallas.filter(t => !tallasActuales.includes(t));
    
    Swal.fire({
        title: `Agregar Tallas por Rango - ${generoLabel}`,
        html: `
            <div style="display: flex; flex-direction: column; gap: 1rem; padding: 1rem;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; text-align: left;">Desde:</label>
                    <select id="talla-inicio" style="width: 100%; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.9rem;">
                        <option value="">-- Selecciona --</option>
                        ${todasLasTallas.map(talla => `<option value="${talla}">${talla}</option>`).join('')}
                    </select>
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; text-align: left;">Hasta:</label>
                    <select id="talla-fin" style="width: 100%; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.9rem;">
                        <option value="">-- Selecciona --</option>
                        ${todasLasTallas.map(talla => `<option value="${talla}">${talla}</option>`).join('')}
                    </select>
                </div>
                <div style="background: #f0f7ff; padding: 0.75rem; border-radius: 4px; font-size: 0.85rem; color: #1e3a8a; font-weight: 500;">
                    Tallas a agregar: <span id="preview-rango">0</span>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Agregar',
        cancelButtonText: 'Cancelar',
        didOpen: () => {
            const selectInicio = document.getElementById('talla-inicio');
            const selectFin = document.getElementById('talla-fin');
            const preview = document.getElementById('preview-rango');
            
            const actualizarPreview = () => {
                const inicio = selectInicio.value;
                const fin = selectFin.value;
                
                if (inicio && fin) {
                    const idxInicio = todasLasTallas.indexOf(inicio);
                    const idxFin = todasLasTallas.indexOf(fin);
                    
                    if (idxInicio >= 0 && idxFin >= 0) {
                        const [min, max] = idxInicio <= idxFin ? [idxInicio, idxFin] : [idxFin, idxInicio];
                        const rango = todasLasTallas.slice(min, max + 1);
                        preview.textContent = rango.filter(t => !tallasActuales.includes(t)).length;
                    }
                } else {
                    preview.textContent = '0';
                }
            };
            
            selectInicio.addEventListener('change', actualizarPreview);
            selectFin.addEventListener('change', actualizarPreview);
        },
        preConfirm: () => {
            const inicio = document.getElementById('talla-inicio').value;
            const fin = document.getElementById('talla-fin').value;
            
            if (!inicio || !fin) {
                Swal.showValidationMessage('Debes seleccionar rango inicial y final');
                return false;
            }
            
            const idxInicio = todasLasTallas.indexOf(inicio);
            const idxFin = todasLasTallas.indexOf(fin);
            
            if (idxInicio < 0 || idxFin < 0) {
                Swal.showValidationMessage('Rango inválido');
                return false;
            }
            
            const [min, max] = idxInicio <= idxFin ? [idxInicio, idxFin] : [idxFin, idxInicio];
            return todasLasTallas.slice(min, max + 1).filter(t => !tallasActuales.includes(t));
        }
    }).then((result) => {
        if (result.isConfirmed && result.value && result.value.length > 0) {
            agregarTallasAlGeneroReflectivo_Interno(prendaIndex, genero, result.value, tipoTalla);
        }
    });
};

/**
 * Agregar tallas internas (después del modal)
 */
function agregarTallasAlGeneroReflectivo_Interno(prendaIndex, genero, tallas, tipoTalla) {
    const prendaCard = document.querySelector(`.prenda-card-reflectivo[data-prenda-index="${prendaIndex}"]`);
    if (!prendaCard) return;

    // Crear inputs para cada talla (solo si no existen)
    tallas.forEach(talla => {
        // Verificar si ya existe
        const existente = prendaCard.querySelector(
            `.talla-cantidad-genero-editable[data-prenda="${prendaIndex}"][data-genero="${genero}"][data-talla="${talla}"]`
        );
        if (existente) {
            console.warn(` Talla ${talla} ya existe para ${genero}`);
            return; // Saltar si ya existe
        }

        const inputTalla = document.createElement('input');
        inputTalla.type = 'hidden';
        inputTalla.name = `cantidades_genero[${prendaIndex}][${genero}][${talla}]`;
        inputTalla.className = 'talla-cantidad-genero-editable';
        inputTalla.value = '0';
        inputTalla.dataset.talla = talla;
        inputTalla.dataset.genero = genero;
        inputTalla.dataset.prenda = prendaIndex;
        inputTalla.dataset.tipoTalla = tipoTalla;

        prendaCard.appendChild(inputTalla);
        console.log(` Input creado para ${genero} ${talla}`);
    });

    // Re-renderizar la sección del género
    renderizarTallasDelGeneroReflectivo(prendaIndex, genero);

    Swal.fire({
        icon: 'success',
        title: 'Tallas agregadas',
        text: `Se agregaron ${tallas.length} talla(s) a ${genero}`,
        timer: 1500,
        showConfirmButton: false
    });
}

/**
 * Renderizar tallas del género en tabla (Reflectivo)
 */
function renderizarTallasDelGeneroReflectivo(prendaIndex, genero) {
    const prendaCard = document.querySelector(`.prenda-card-reflectivo[data-prenda-index="${prendaIndex}"]`);
    if (!prendaCard) return;

    const containerGenero = prendaCard.querySelector(
        `.tallas-genero-container-reflectivo[data-prenda="${prendaIndex}"][data-genero="${genero}"]`
    );
    if (!containerGenero) return;

    // Buscar inputs de tallas para este género
    let tallasInputs = prendaCard.querySelectorAll(
        `.talla-cantidad-genero-editable[data-prenda="${prendaIndex}"][data-genero="${genero}"]`
    );

    if (tallasInputs.length === 0) {
        containerGenero.innerHTML = '<p style="padding: 0.75rem 1rem; background: white; color: #9ca3af; font-size: 0.85rem; margin: 0; border-radius: 0 0 6px 6px;">Sin tallas agregadas</p>';
        return;
    }

    let html = '';
    let isFirst = true;

    tallasInputs.forEach((input) => {
        const talla = input.dataset.talla;
        const cantidad = input.value || '0';

        html += `
            <div style="padding: 1rem; background: white; border: 1px solid #e0e0e0; ${isFirst ? '' : 'border-top: none;'} display: grid; grid-template-columns: 1.5fr 1fr 100px; gap: 1rem; align-items: center; transition: background 0.2s; width: 100%;">
                <div style="display: flex; flex-direction: column;">
                    <label style="font-size: 0.75rem; color: #666; font-weight: 600; text-transform: uppercase; margin-bottom: 0.4rem;">Talla</label>
                    <div style="font-weight: 500; color: #1f2937;">${talla}</div>
                </div>
                <div style="display: flex; flex-direction: column;">
                    <label style="font-size: 0.75rem; color: #666; font-weight: 600; text-transform: uppercase; margin-bottom: 0.4rem;">Cantidad</label>
                    <input type="number" 
                           min="0" 
                           value="${cantidad}" 
                           placeholder="0"
                           class="talla-cantidad-display"
                           data-talla="${talla}"
                           data-genero="${genero}"
                           data-prenda="${prendaIndex}"
                           style="width: 100%; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.9rem; transition: border-color 0.2s;">
                </div>
                <div style="text-align: center;">
                    <button type="button" class="btn-eliminar-talla-genero" onclick="eliminarTallaDelGeneroReflectivo(${prendaIndex}, '${genero}', '${talla}')" style="background: #dc3545; color: white; border: none; padding: 0.5rem 0.75rem; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: 600; transition: all 0.2s; display: inline-flex; align-items: center; gap: 0.3rem; white-space: nowrap;" title="Eliminar talla">
                        <i class="fas fa-trash-alt" style="font-size: 0.7rem;"></i> Quitar
                    </button>
                </div>
            </div>
        `;
        isFirst = false;
    });

    containerGenero.innerHTML = html;

    // Agregar listeners a los inputs de display
    containerGenero.querySelectorAll('.talla-cantidad-display').forEach(input => {
        input.addEventListener('change', (e) => {
            const prendaIdx = parseInt(e.target.dataset.prenda);
            const gen = e.target.dataset.genero;
            const talla = e.target.dataset.talla;
            const cantidad = parseInt(e.target.value) || 0;

            // Actualizar el input oculto correspondiente
            const prendaCard = document.querySelector(`.prenda-card-reflectivo[data-prenda-index="${prendaIdx}"]`);
            if (prendaCard) {
                let hiddenInput = prendaCard.querySelector(
                    `.talla-cantidad-genero-editable[data-prenda="${prendaIdx}"][data-genero="${gen}"][data-talla="${talla}"]`
                );

                if (hiddenInput) {
                    hiddenInput.value = cantidad;
                }
            }

            // Actualizar gestor
            const prenda = window.gestorReflectivoSinCotizacion.obtenerPorIndice(prendaIdx);
            if (prenda) {
                if (!prenda.generosConTallas) {
                    prenda.generosConTallas = {};
                }
                if (!prenda.generosConTallas[gen]) {
                    prenda.generosConTallas[gen] = {};
                }
                prenda.generosConTallas[gen][talla] = cantidad;
                console.log(` Cantidad actualizada - Prenda: ${prendaIdx}, Género: ${gen}, Talla: ${talla}, Cantidad: ${cantidad}`);
            }
        });
    });
}

/**
 * Eliminar una talla de un género (Reflectivo)
 */
window.eliminarTallaDelGeneroReflectivo = function(prendaIndex, genero, talla) {
    Swal.fire({
        title: '¿Confirmar?',
        text: `¿Eliminar talla ${talla} de ${genero}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const prendaCard = document.querySelector(`.prenda-card-reflectivo[data-prenda-index="${prendaIndex}"]`);
            if (!prendaCard) return;

            // Eliminar input oculto
            const hiddenInput = prendaCard.querySelector(
                `.talla-cantidad-genero-editable[data-prenda="${prendaIndex}"][data-genero="${genero}"][data-talla="${talla}"]`
            );
            if (hiddenInput) {
                hiddenInput.remove();
            }

            // Actualizar gestor
            const prenda = window.gestorReflectivoSinCotizacion.obtenerPorIndice(prendaIndex);
            if (prenda && prenda.generosConTallas && prenda.generosConTallas[genero]) {
                delete prenda.generosConTallas[genero][talla];
            }

            // Re-renderizar
            renderizarTallasDelGeneroReflectivo(prendaIndex, genero);

            Swal.fire('Eliminado', 'Talla eliminada correctamente', 'success');
        }
    });
};

logWithEmoji('', 'Funciones de reflectivo sin cotización cargadas');
