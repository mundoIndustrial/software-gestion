/**
 * VARIACIONES - Gestión de variaciones y metadatos en Prenda Sin Cotización
 * 
 * Funciones para:
 * - Eliminar variaciones
 * - Manejar cambios de variaciones
 * - Sincronizar datos de telas
 * - Marcar como prenda de bodega
 * - Actualizar origen
 */

/**
 * Eliminar variación de prenda
 * @param {HTMLElement} element - Elemento del botón
 * @param {number} prendaIndex - Índice de la prenda
 * @param {string} variacion - Nombre de la variación
 */
window.eliminarVariacionPrendaTipo = function(element, prendaIndex, variacion) {
    Swal.fire({
        title: '¿Eliminar Variación?',
        text: `¿Está seguro de eliminar la variación "${variacion}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Sí, Eliminar'
    }).then((result) => {
        if (result.isConfirmed) {
            const prenda = window.gestorPrendaSinCotizacion?.obtenerPorIndice(prendaIndex);
            if (!prenda) return;

            if (prenda.variaciones && prenda.variaciones[variacion]) {
                delete prenda.variaciones[variacion];
                console.log(`✅ [VAR] Variación "${variacion}" eliminada de prenda ${prendaIndex}`);
            }

            const form = document.getElementById(`prenda-${prendaIndex}-form`);
            if (form) {
                const select = form.querySelector(`.variacion-prenda-select[data-variacion="${variacion}"]`);
                select?.remove();
                console.log(`✅ [VAR] Select de variación "${variacion}" eliminado del DOM`);
            }

            window.renderizarPrendasTipoPrendaSinCotizacion?.();

            Swal.fire({
                icon: 'success',
                title: 'Eliminada',
                text: 'Variación eliminada correctamente',
                timer: 1500,
                showConfirmButton: false
            });
        }
    });
};

/**
 * Manejar cambio en select de variación
 * @param {HTMLSelectElement} select - Elemento del select
 * @param {number} prendaIndex - Índice de la prenda
 * @param {string} variacion - Nombre de la variación
 */
window.manejarCambioVariacionPrendaTipo = function(select, prendaIndex, variacion) {
    const prenda = window.gestorPrendaSinCotizacion?.obtenerPorIndice(prendaIndex);
    if (!prenda) return;

    const valor = select.value;
    console.log(`📝 [VAR] Cambio en variación "${variacion}" de prenda ${prendaIndex}: "${valor}"`);

    // Actualizar en el gestor
    if (!prenda.variaciones) {
        prenda.variaciones = {};
    }
    prenda.variaciones[variacion] = valor;

    // Actualizar en PedidoState
    if (window.PedidoState) {
        const variaciones = window.PedidoState.getVariacionesPrenda(prendaIndex) || {};
        variaciones[variacion] = valor;
        window.PedidoState.setVariacionesPrenda(prendaIndex, variaciones);
        console.log(`✅ [VAR] Variación sincronizada en PedidoState`);
    }

    // Sincronizar datos de telas
    window.sincronizarDatosTelas?.(prendaIndex);
};

/**
 * Sincronizar datos de telas desde formulario
 * Actualiza los datos de telas en base a los valores del formulario
 * @param {number} prendaIndex - Índice de la prenda
 */
window.sincronizarDatosTelas = function(prendaIndex) {
    const prenda = window.gestorPrendaSinCotizacion?.obtenerPorIndice(prendaIndex);
    if (!prenda || !prenda.telas) {
        console.warn(`⚠️ [SYNC] Prenda ${prendaIndex} no tiene telas para sincronizar`);
        return;
    }

    const form = document.getElementById(`prenda-${prendaIndex}-form`);
    if (!form) {
        console.warn(`⚠️ [SYNC] Formulario prenda-${prendaIndex}-form no encontrado`);
        return;
    }

    console.log(`🔄 [SYNC] Sincronizando datos de telas para prenda ${prendaIndex}`);

    // Sincronizar cada tela con sus inputs
    prenda.telas.forEach((tela, telaIdx) => {
        const telaDiv = form.querySelector(`[data-tela-index="${telaIdx}"]`);
        if (!telaDiv) {
            console.warn(`⚠️ [SYNC] No se encontró div de tela ${telaIdx}`);
            return;
        }

        // Sincronizar descripción
        const descInput = telaDiv.querySelector('input[name="tela-descripcion"]');
        if (descInput && descInput.value) {
            tela.descripcion = descInput.value;
            console.log(`   ✅ Descripción tela ${telaIdx}: "${tela.descripcion}"`);
        }

        // Sincronizar metros
        const metrosInput = telaDiv.querySelector('input[name="tela-metros"]');
        if (metrosInput && metrosInput.value) {
            tela.metros = parseFloat(metrosInput.value) || 0;
            console.log(`   ✅ Metros tela ${telaIdx}: ${tela.metros}`);
        }

        // Sincronizar color
        const colorInput = telaDiv.querySelector('input[name="tela-color"]');
        if (colorInput && colorInput.value) {
            tela.color = colorInput.value;
            console.log(`   ✅ Color tela ${telaIdx}: "${tela.color}"`);
        }

        // Sincronizar composición
        const composInput = telaDiv.querySelector('input[name="tela-composicion"]');
        if (composInput && composInput.value) {
            tela.composicion = composInput.value;
            console.log(`   ✅ Composición tela ${telaIdx}: "${tela.composicion}"`);
        }

        // Sincronizar ancho
        const anchoInput = telaDiv.querySelector('input[name="tela-ancho"]');
        if (anchoInput && anchoInput.value) {
            tela.ancho = parseFloat(anchoInput.value) || 0;
            console.log(`   ✅ Ancho tela ${telaIdx}: ${tela.ancho}`);
        }

        // Sincronizar peso
        const pesoInput = telaDiv.querySelector('input[name="tela-peso"]');
        if (pesoInput && pesoInput.value) {
            tela.peso = parseFloat(pesoInput.value) || 0;
            console.log(`   ✅ Peso tela ${telaIdx}: ${tela.peso}`);
        }

        // Sincronizar densidad
        const densidadInput = telaDiv.querySelector('input[name="tela-densidad"]');
        if (densidadInput && densidadInput.value) {
            tela.densidad = parseFloat(densidadInput.value) || 0;
            console.log(`   ✅ Densidad tela ${telaIdx}: ${tela.densidad}`);
        }

        // Sincronizar estiramiento
        const estiramientoInput = telaDiv.querySelector('input[name="tela-estiramiento"]');
        if (estiramientoInput && estiramientoInput.value) {
            tela.estiramiento = estiramientoInput.value;
            console.log(`   ✅ Estiramiento tela ${telaIdx}: "${tela.estiramiento}"`);
        }
    });

    // Sincronizar PedidoState
    if (window.PedidoState) {
        window.PedidoState.setTelasPrenda(prendaIndex, prenda.telas);
        console.log(`✅ [SYNC] Telas sincronizadas en PedidoState`);
    }
};

/**
 * Marcar prenda como de bodega
 * @param {HTMLInputElement} checkbox - Elemento del checkbox
 * @param {number} prendaIndex - Índice de la prenda
 */
window.marcarPrendaDeBodega = function(checkbox, prendaIndex) {
    const prenda = window.gestorPrendaSinCotizacion?.obtenerPorIndice(prendaIndex);
    if (!prenda) return;

    const esBodega = checkbox.checked;
    prenda.es_bodega = esBodega;
    prenda.origen = esBodega ? 'bodega' : 'confeccion';

    console.log(`📍 [BODEGA] Prenda ${prendaIndex} marcada como ${esBodega ? 'bodega' : 'confección'}`);

    // Actualizar en PedidoState
    if (window.PedidoState) {
        window.PedidoState.setOrigenPrenda(prendaIndex, prenda.origen);
        console.log(`✅ [BODEGA] Origen sincronizado en PedidoState`);
    }

    // Actualizar visual
    const origenSection = document.querySelector(`[data-origen-section="${prendaIndex}"]`);
    if (origenSection) {
        origenSection.style.display = esBodega ? 'none' : 'block';
        console.log(`✅ [BODEGA] Sección de origen actualizada`);
    }
};

/**
 * Actualizar origen de prenda
 * @param {HTMLSelectElement} select - Elemento del select
 * @param {number} prendaIndex - Índice de la prenda
 */
window.actualizarOrigenPrenda = function(select, prendaIndex) {
    const prenda = window.gestorPrendaSinCotizacion?.obtenerPorIndice(prendaIndex);
    if (!prenda) return;

    const origen = select.value;
    prenda.origen = origen;

    console.log(`📍 [ORIGEN] Prenda ${prendaIndex} origen actualizado a: "${origen}"`);

    // Actualizar en PedidoState
    if (window.PedidoState) {
        window.PedidoState.setOrigenPrenda(prendaIndex, origen);
        console.log(`✅ [ORIGEN] Origen sincronizado en PedidoState`);
    }

    // Marcar como modificada
    if (window.PedidoState) {
        window.PedidoState.marcarModificada(prendaIndex);
    }
};

console.log('✅ [VARIACIONES] Componente prenda-sin-cotizacion-variaciones.js cargado');
