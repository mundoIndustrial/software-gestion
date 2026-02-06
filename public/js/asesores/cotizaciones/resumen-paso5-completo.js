/**
 * resumen-paso4-completo.js
 * Actualización completa del Paso 4 con tablas dinámicas
 * Renderiza solo las secciones que tienen datos
 */

/**
 * FUNCIÓN PRINCIPAL: Actualizar todo el resumen del Paso 4
 */
function actualizarResumenPaso4Completo() {
    console.log('🔄 [actualizarResumenPaso4Completo] Iniciando actualización del resumen');
    
    // 1. Actualizar información del cliente
    actualizarResumenClientePaso4();
    
    // 2. Generar cards de prenda con tablas dinámicas
    generarCardsPredasConTablas();
}

/**
 * 1. ACTUALIZAR INFORMACIÓN DEL CLIENTE
 */
function actualizarResumenClientePaso4() {
    // Cliente
    const clienteInput = document.getElementById('cliente');
    const resumenCliente = document.getElementById('resumen_cliente');
    if (resumenCliente && clienteInput) {
        resumenCliente.textContent = clienteInput.value || '-';
    }
    
    // Fecha
    const fechaInput = document.getElementById('fechaActual');
    const resumenFecha = document.getElementById('resumen_fecha');
    if (resumenFecha && fechaInput) {
        let fechaTexto = '-';
        if (fechaInput.value) {
            const partes = fechaInput.value.split('-');
            if (partes.length === 3) {
                fechaTexto = `${partes[2]}/${partes[1]}/${partes[0]}`;
            }
        }
        resumenFecha.textContent = fechaTexto;
    }
    
    // Tipo de cotización
    const resumenTipo = document.getElementById('resumen_tipo');
    if (resumenTipo) {
        const prendas = document.querySelectorAll('.producto-card');
        const tienePrendas = prendas.length > 0;
        const tieneLogo = document.getElementById('descripcion_logo')?.value?.trim() !== '';
        const tieneReflectivo = false; // Paso 4 eliminado - ya no hay reflectivo
        
        let tipoDetectado = '� Cotización';
        if (tienePrendas && tieneLogo) {
            tipoDetectado = '📦 Combinada (Prendas + Logo)';
        } else if (tienePrendas) {
            tipoDetectado = 'Solo Prendas';
        } else if (tieneLogo) {
            tipoDetectado = ' Logo/Bordado';
        }
        resumenTipo.textContent = tipoDetectado;
    }
}

/**
 * 2. GENERAR CARDS DE PRENDA CON TABLAS DINÁMICAS
 */
function generarCardsPredasConTablas() {
    const contenedorCards = document.getElementById('resumen_prendas_cards');
    if (!contenedorCards) return;
    
    const prendas = document.querySelectorAll('.producto-card');
    contenedorCards.innerHTML = '';
    
    if (prendas.length === 0) {
        contenedorCards.innerHTML = '<div class="empty-state">No hay prendas en esta cotización</div>';
        return;
    }
    
    prendas.forEach((prenda, index) => {
        const cardHTML = generarCardPrenda(prenda, index);
        const div = document.createElement('div');
        div.innerHTML = cardHTML;
        contenedorCards.appendChild(div.firstElementChild);
    });
}

/**
 * GENERAR HTML DE UNA CARD DE PRENDA CON TABLAS
 */
function generarCardPrenda(prenda, index) {
    const nombre = prenda.querySelector('input[name*="nombre_producto"]')?.value || 'Sin nombre';
    const cantidad = prenda.querySelector('input[name*="cantidad"]')?.value || '1';
    
    let html = `<div class="prenda-card-contenedor">
        <div class="prenda-card-layout">
            <div class="prenda-card-contenido">
                <!-- HEADER DE PRENDA -->
                <div class="prenda-card-header">${nombre}</div>`;
    
    // Info rápida: Color, Tela, Referencia (DATOS DE PRENDA - PASO 2)
    // Obtener del primer row de telas que esté dentro de esta prenda
    let color = '-', tela = '-', referencia = '-';
    const telaRow = prenda.querySelector('tr[data-tela-index="0"]');
    if (telaRow) {
        color = telaRow.querySelector('.color-input')?.value || '-';
        tela = telaRow.querySelector('.tela-input')?.value || '-';
        referencia = telaRow.querySelector('.referencia-input')?.value || '-';
    }
    
    // Solo mostrar si hay al menos uno que no sea "-"
    if (color !== '-' || tela !== '-' || referencia !== '-') {
        html += `<div class="prenda-info-rapida">
            <strong>Color:</strong> ${color} | <strong>Tela:</strong> ${tela} | <strong>Referencia:</strong> ${referencia}
        </div>`;
    }
    
    // TABLA DE VARIACIONES ESPECÍFICAS (PASO 2)
    html += generarTablaVariacionesPaso2(prenda, index);
    
    // TABLA DE LOGO (PASO 3)
    html += generarTablaLogoPaso3(nombre);
    
    // TABLA DE REFLECTIVO - ELIMINADO (PASO 4)
    // html += generarTablaReflectivoPaso4(nombre);
    
    // TABLA DE ESPECIFICACIONES (PASO 1)
    html += generarTablaEspecificacionesPaso1();
    
    // TALLAS SELECCIONADAS (PASO 2)
    html += generarTallasSeleccionadas(index, prenda);
    
    html += `</div>
            <div class="prenda-card-imagenes">`;
    
    // IMÁGENES DE PRENDA
    html += generarImagenesPrenda(prenda);
    
    html += `</div>
        </div>
    </div>`;
    
    return html;
}

/**
 * GENERAR TABLA DE VARIACIONES (PASO 2)
 */
function generarTablaVariacionesPaso2(prenda, index) {
    let variaciones = [];
    
    // Obtener checkboxes de variaciones dentro de esta prenda
    // Los checkboxes están en: input[name*="aplica_manga"], input[name*="aplica_bolsillos"], input[name*="aplica_broche"]
    
    // MANGA
    const mangaCheckbox = prenda.querySelector('input[name*="aplica_manga"]');
    const mangaInput = prenda.querySelector('.manga-input');
    const mangaObs = prenda.querySelector('input[name*="obs_manga"]');
    
    if (mangaCheckbox && mangaCheckbox.checked) {
        const mangaTipo = mangaInput?.value || '-';
        const mangaObservacion = mangaObs?.value || '';
        variaciones.push({
            nombre: 'Manga',
            valor: mangaTipo,
            observacion: mangaObservacion
        });
    }
    
    // BOLSILLOS
    const bolsillosCheckbox = prenda.querySelector('input[name*="aplica_bolsillos"]');
    const bolsillosObs = prenda.querySelector('input[name*="obs_bolsillos"]');
    
    if (bolsillosCheckbox && bolsillosCheckbox.checked) {
        const bolsillosObservacion = bolsillosObs?.value || 'Incluido';
        variaciones.push({
            nombre: 'Bolsillos',
            valor: '',
            observacion: bolsillosObservacion
        });
    }
    
    // BROCHE/BOTÓN
    const brocheCheckbox = prenda.querySelector('input[name*="aplica_broche"]');
    const brocheSelect = prenda.querySelector('select[name*="tipo_broche_id"]');
    const brocheObs = prenda.querySelector('input[name*="obs_broche"]');
    
    if (brocheCheckbox && brocheCheckbox.checked) {
        const brocheTipo = brocheSelect?.value ? (brocheSelect.value === '1' ? 'Broche' : 'Botón') : '';
        const brocheObservacion = brocheObs?.value || '';
        variaciones.push({
            nombre: 'Broche/Botón',
            valor: brocheTipo,
            observacion: brocheObservacion
        });
    }
    
    // Si no hay variaciones, no renderizar
    if (variaciones.length === 0) return '';
    
    let html = `<div class="seccion-titulo">VARIACIONES ESPECÍFICAS</div>
        <table class="tabla-prenda">
            <thead>
                <tr>
                    <th>Variación</th>
                    <th>Observación</th>
                </tr>
            </thead>
            <tbody>`;
    
    variaciones.forEach(v => {
        const valorTexto = v.valor ? `<strong>${v.valor}</strong> ` : '';
        html += `<tr>
            <td>${v.nombre}</td>
            <td><span class="observacion-texto">${valorTexto}${v.observacion}</span></td>
        </tr>`;
    });
    
    html += `</tbody>
        </table>`;
    
    return html;
}

/**
 * GENERAR TABLA DE LOGO (PASO 3)
 */
function generarTablaLogoPaso3(nombrePrenda) {
    // DEBUG
    console.log('🔍 generarTablaLogoPaso3 - Buscando prenda:', nombrePrenda);
    console.log('📦 window.tecnicasAgregadasPaso3 =', window.tecnicasAgregadasPaso3);
    
    // BUSCAR EN window.tecnicasAgregadasPaso3 las técnicas y ubicaciones de esta prenda
    let tecnicasUbicaciones = {}; // {tecnica: [ubicaciones...]}
    
    if (window.tecnicasAgregadasPaso3 && Array.isArray(window.tecnicasAgregadasPaso3)) {
        window.tecnicasAgregadasPaso3.forEach(tecnicaData => {
            if (tecnicaData.prendas && Array.isArray(tecnicaData.prendas)) {
                tecnicaData.prendas.forEach(prenda => {
                    // Buscar por nombre de prenda: permite búsqueda parcial (.includes()) ya que Paso 3 guarda el nombre con más detalles
                    if (prenda.nombre_prenda && prenda.nombre_prenda.toUpperCase().includes(nombrePrenda.toUpperCase())) {
                        console.log(' Prenda encontrada:', prenda.nombre_prenda);
                        const nombreTecnica = tecnicaData.tipo || tecnicaData.nombre || 'Técnica';
                        
                        // Inicializar array de ubicaciones para esta técnica
                        if (!tecnicasUbicaciones[nombreTecnica]) {
                            tecnicasUbicaciones[nombreTecnica] = [];
                        }
                        
                        // Agregar las ubicaciones
                        if (prenda.ubicaciones && Array.isArray(prenda.ubicaciones)) {
                            console.log('📍 Ubicaciones encontradas:', prenda.ubicaciones);
                            prenda.ubicaciones.forEach(ubicacion => {
                                if (ubicacion && ubicacion.trim() !== '' && ubicacion.trim() !== 'SIN UBICACIÓN') {
                                    tecnicasUbicaciones[nombreTecnica].push(ubicacion.trim());
                                }
                            });
                        }
                    }
                });
            }
        });
    }
    
    console.log(' tecnicasUbicaciones finales:', tecnicasUbicaciones);
    
    // Si no hay técnicas ni ubicaciones, no renderizar
    if (Object.keys(tecnicasUbicaciones).length === 0) {
        console.log('❌ Sin técnicas para esta prenda, no renderizando tabla');
        return '';
    }
    
    let html = `<div class="seccion-titulo">LOGO:</div>
        <table class="tabla-prenda">
            <thead>
                <tr>
                    <th>Técnica(s)</th>
                    <th>Ubicaciones</th>
                </tr>
            </thead>
            <tbody>`;
    
    // Renderizar una fila por técnica con sus ubicaciones
    Object.entries(tecnicasUbicaciones).forEach(([tecnica, ubicaciones]) => {
        // Mostrar todas las ubicaciones para esta técnica en una sola fila, separadas por saltos de línea
        const ubicacionesHTML = ubicaciones.map(ub => `<div>• ${ub}</div>`).join('');
        html += `<tr>
                <td>${tecnica}</td>
                <td><span class="observacion-texto">${ubicacionesHTML}</span></td>
            </tr>`;
    });
    
    html += `</tbody>
        </table>`;
    
    return html;
}

/**
 * GENERAR TABLA DE REFLECTIVO (PASO 4)
 */
function generarTablaReflectivoPaso4(nombrePrenda) {
    // DEBUG
    console.log('🔍 generarTablaReflectivoPaso4 - Buscando prenda:', nombrePrenda);
    console.log('📦 window.prendas_reflectivo_paso4 =', window.prendas_reflectivo_paso4);
    
    let reflectivoDatos = [];
    
    // Buscar en window.prendas_reflectivo_paso4 la prenda que coincida
    if (window.prendas_reflectivo_paso4 && Array.isArray(window.prendas_reflectivo_paso4)) {
        window.prendas_reflectivo_paso4.forEach(prenda => {
            // Buscar por nombre de prenda (búsqueda parcial con .includes())
            if (prenda.tipo_prenda && prenda.tipo_prenda.toUpperCase().includes(nombrePrenda.toUpperCase())) {
                console.log(' Prenda reflectivo encontrada:', prenda.tipo_prenda);
                
                // Extraer ubicaciones de esta prenda
                if (prenda.ubicaciones && Array.isArray(prenda.ubicaciones) && prenda.ubicaciones.length > 0) {
                    prenda.ubicaciones.forEach(item => {
                        if (item && (item.ubicacion || item.descripcion)) {
                            reflectivoDatos.push({
                                ubicacion: item.ubicacion || '-',
                                descripcion: item.descripcion || '-'
                            });
                        }
                    });
                    console.log('📍 Ubicaciones reflectivo encontradas:', reflectivoDatos);
                }
            }
        });
    }
    
    console.log(' reflectivoDatos finales:', reflectivoDatos);
    
    // Si no hay datos, no renderizar
    if (reflectivoDatos.length === 0) {
        console.log('❌ Sin reflectivo para esta prenda, no renderizando tabla');
        return '';
    }
    
    let html = `<div class="seccion-titulo">REFLECTIVO:</div>
        <table class="tabla-prenda">
            <thead>
                <tr>
                    <th>Ubicación</th>
                    <th>Descripción</th>
                </tr>
            </thead>
            <tbody>`;
    
    reflectivoDatos.forEach(item => {
        html += `<tr>
            <td>${item.ubicacion}</td>
            <td><span class="observacion-texto">${item.descripcion}</span></td>
        </tr>`;
    });
    
    html += `</tbody>
        </table>`;
    
    return html;
}

/**
 * GENERAR TALLAS SELECCIONADAS
 */
function generarTallasSeleccionadas(index, prenda) {
    let tallas = [];
    
    // Buscar los badges de tallas seleccionadas dentro de esta prenda
    // Los badges están en un contenedor con clase .tallas-agregadas
    const tallasContainer = prenda.querySelector('.tallas-agregadas');
    if (tallasContainer) {
        const tallasBadges = tallasContainer.querySelectorAll('.talla-seleccionada, span[data-talla]');
        tallasBadges.forEach(badge => {
            const tallaText = badge.textContent?.trim()?.replace(/[×x]/, '').trim();
            if (tallaText) tallas.push(tallaText);
        });
    }
    
    if (tallas.length === 0) return '';
    
    let html = `<div class="tallas-container">
        <label class="tallas-label">Tallas seleccionadas:</label>
        <div class="tallas-badges">`;
    
    tallas.forEach(talla => {
        html += `<span class="talla-badge">${talla}</span>`;
    });
    
    html += `</div>
    </div>`;
    
    return html;
}

/**
 * GENERAR IMÁGENES DE PRENDA
 */
function generarImagenesPrenda(prenda) {
    const imagenes = prenda.querySelectorAll('.imagen-subida');
    
    if (imagenes.length === 0) return '';
    
    let html = `<div class="imagenes-grid">`;
    
    imagenes.forEach(img => {
        const src = img.src || img.getAttribute('data-src');
        if (src) {
            html += `<img src="${src}" alt="Prenda" class="imagen-preview" />`;
        }
    });
    
    html += `</div>`;
    
    return html;
}

/**
 * GENERAR TABLA DE ESPECIFICACIONES (PASO 1)
 */
function generarTablaEspecificacionesPaso1() {
    const especificaciones = window.especificacionesSeleccionadas || {};
    const tieneEspecificaciones = Object.keys(especificaciones).length > 0;
    
    if (!tieneEspecificaciones) return '';
    
    let html = `<div class="seccion-titulo">ESPECIFICACIONES</div>
        <table class="tabla-prenda">
            <thead>
                <tr>
                    <th>Especificación</th>
                    <th>Valor</th>
                    <th>Observación</th>
                </tr>
            </thead>
            <tbody>`;
    
    // Mapeo de nombres amigables
    const nombresAmigables = {
        'disponibilidad': 'Disponibilidad',
        'forma_pago': 'Forma de Pago',
        'regimen': 'Régimen',
        'se_ha_vendido': 'Se ha vendido',
        'ultima_venta': 'Última venta',
        'flete': 'Flete'
    };
    
    Object.entries(especificaciones).forEach(([categoria, datos]) => {
        if (Array.isArray(datos)) {
            datos.forEach(item => {
                const nombreCategoria = nombresAmigables[categoria] || categoria;
                const valor = item.valor || '-';
                const observacion = item.observacion || '-';
                
                html += `<tr>
                    <td>${nombreCategoria}</td>
                    <td><strong>${valor}</strong></td>
                    <td><span class="observacion-texto">${observacion}</span></td>
                </tr>`;
            });
        }
    });
    
    html += `</tbody>
        </table>`;
    
    return html;
}

// Exportar función principal
window.actualizarResumenPaso4Completo = actualizarResumenPaso4Completo;
// Mantener compatibilidad con el nombre antiguo
window.actualizarResumenPaso5Completo = actualizarResumenPaso4Completo;
