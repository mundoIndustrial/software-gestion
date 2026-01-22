/**
 * INTEGRACIÓN: Prenda Sin Cotización - Validación y Envío
 * 
 * Este módulo se encarga de integrar el módulo de prenda sin cotización
 * con el flujo general de validación y envío del formulario.
 */

/**
 * Validar datos de prendas tipo PRENDA antes de envío
 * @returns {boolean} true si pasa validación
 */
window.validarPrendasTipoPrendaSinCotizacion = function() {
    if (!window.gestorPrendaSinCotizacion) {
        console.warn(' GestorPrendaSinCotizacion no inicializado');
        return false;
    }

    const prendas = window.gestorPrendaSinCotizacion.obtenerActivas();
    
    if (prendas.length === 0) {
        Swal.fire('Error', 'Debe agregar al menos una prenda', 'error');
        return false;
    }

    let todasValidas = true;
    const errores = [];

    //  CRÍTICO: Obtener el índice REAL de cada prenda en el gestor, no el del array filtrado
    prendas.forEach((prenda) => {
        // Buscar el índice real de esta prenda
        const allPrendas = window.gestorPrendaSinCotizacion.prendas;
        const prendaIndex = allPrendas.indexOf(prenda);
        
        if (prendaIndex >= 0) {
            const validacion = window.gestorPrendaSinCotizacion.validar(prendaIndex);
            if (!validacion.valido) {
                todasValidas = false;
                errores.push(...validacion.errores);
            }
        }
    });

    if (!todasValidas) {
        const mensajeErrores = errores.map(e => `• ${e}`).join('\n');
        Swal.fire({
            title: 'Errores de validación',
            text: 'Corrija los siguientes errores:\n\n' + mensajeErrores,
            icon: 'error',
            html: '<p style="text-align: left;">' + mensajeErrores.replace(/\n/g, '<br>') + '</p>'
        });
        return false;
    }

    return true;
};

/**
 * Sincronizar datos de cantidades del DOM con el gestor
 *  Intenta leer del DOM, pero los datos ya deberían estar en el gestor
 */
window.sincronizarCantidadesDelDOM = function() {
    try {
        if (!window.gestorPrendaSinCotizacion) {
            console.warn(` Gestor no disponible`);
            return;
        }
        
        console.log(`\n🔄 ========== SINCRONIZANDO CANTIDADES DEL DOM ==========`);
        
        // Buscar TODOS los inputs de display (variaciones de clase)
        const selectores = [
            'input.talla-cantidad-display-editable',
            'input.talla-cantidad-display',
            'input[class*="talla-cantidad-display"]'
        ];
        
        let displayInputs = [];
        for (let selector of selectores) {
            const encontrados = document.querySelectorAll(selector);
            if (encontrados.length > 0) {
                displayInputs = encontrados;
                console.log(` Selector encontrado: "${selector}" → ${encontrados.length} inputs`);
                break;
            }
        }
        
        console.log(` Total de inputs de display encontrados en DOM: ${displayInputs.length}`);
        
        // Si encontramos inputs, sincronizarlos
        if (displayInputs.length > 0) {
            console.log(` Leyendo valores de inputs...`);
            const cantidadesPorPrenda = {};
            
            displayInputs.forEach((displayInput, i) => {
                const prendaIndex = parseInt(displayInput.dataset.prenda);
                const genero = displayInput.dataset.genero;
                const talla = displayInput.dataset.talla;
                const cantidad = parseInt(displayInput.value) || 0;
                
                if (!cantidadesPorPrenda[prendaIndex]) {
                    cantidadesPorPrenda[prendaIndex] = {};
                }
                if (!cantidadesPorPrenda[prendaIndex][genero]) {
                    cantidadesPorPrenda[prendaIndex][genero] = {};
                }
                
                cantidadesPorPrenda[prendaIndex][genero][talla] = cantidad;
                
                console.log(`   [${i}] Prenda ${prendaIndex}, ${genero} ${talla}: ${cantidad}`);
            });
            
            // Sincronizar con el gestor
            console.log(`\n🔄 Actualizando gestor con cantidades del DOM...`);
            Object.entries(cantidadesPorPrenda).forEach(([prendaIndex, generos]) => {
                try {
                    const prenda = window.gestorPrendaSinCotizacion.obtenerPorIndice(parseInt(prendaIndex));
                    
                    if (!prenda) {
                        console.warn(` Prenda ${prendaIndex} no existe en gestor`);
                        return;
                    }
                    
                    // Actualizar generosConTallas
                    Object.entries(generos).forEach(([genero, tallas]) => {
                        if (!prenda.generosConTallas[genero]) {
                            prenda.generosConTallas[genero] = {};
                        }
                        
                        Object.entries(tallas).forEach(([talla, cantidad]) => {
                            prenda.generosConTallas[genero][talla] = cantidad;
                        });
                    });
                    
                    console.log(` Prenda ${prendaIndex} actualizada:`, prenda.generosConTallas);
                } catch (e) {
                    console.error(` Error sincronizando prenda ${prendaIndex}:`, e);
                }
            });
        } else {
            // Sin inputs de display
            console.log(` Sin inputs de display en DOM. Los datos ya deberían estar en el gestor.`);
            console.log(` Estado actual del gestor:`);
            const prendas = window.gestorPrendaSinCotizacion.obtenerActivas();
            prendas.forEach((prenda, idx) => {
                console.log(`   Prenda ${idx} (${prenda.nombre_producto}):`, prenda.generosConTallas);
            });
        }
        console.log(` SINCRONIZACIÓN COMPLETADA\n`);
    } catch (e) {
        console.error(` Error en sincronizarCantidadesDelDOM:`, e);
        throw new Error('Error al sincronizar datos: ' + e.message);
    }
};

/**
 * Obtener datos de prendas PRENDA para envío
 * @returns {Object} Datos formateados para envío
 */
window.obtenerDatosPrendasTipoPrendaSinCotizacion = function() {
    if (!window.gestorPrendaSinCotizacion) {
        return {
            prendas: [],
            fotosNuevas: {},
            telasFotosNuevas: {},
            prendasEliminadas: []
        };
    }

    console.log(` ANTES DE SINCRONIZAR - Prendas en gestor:`, window.gestorPrendaSinCotizacion.obtenerActivas());

    //  CRÍTICO: Sincronizar datos del DOM antes de obtenerlos
    window.sincronizarCantidadesDelDOM();

    console.log(` DESPUÉS DE SINCRONIZAR - Prendas en gestor:`, window.gestorPrendaSinCotizacion.obtenerActivas());

    return window.gestorPrendaSinCotizacion.obtenerDatosFormato();
};

/**
 * Limpiar el módulo de prenda sin cotización
 */
window.limpiarPrendasTipoPrendaSinCotizacion = function() {
    if (window.gestorPrendaSinCotizacion) {
        window.gestorPrendaSinCotizacion.limpiar();
    }
};

/**
 * Obtener resumen de prendas para mostrar en confirmación de envío
 * @returns {string} HTML del resumen
 */
window.obtenerResumenPrendasTipoPrendaSinCotizacion = function() {
    const prendas = window.gestorPrendaSinCotizacion.obtenerActivas();
    
    if (prendas.length === 0) {
        return '<p style="color: #999;">No hay prendas agregadas</p>';
    }

    let html = `
        <div style="text-align: left; max-height: 300px; overflow-y: auto;">
            <h4 style="margin-top: 0; color: #0066cc;">Resumen de Prendas:</h4>
    `;

    prendas.forEach((prenda, index) => {
        const tallas = prenda.tallas?.join(', ') || 'Sin tallas';
        const telas = prenda.variantes?.telas_multiples?.length || 0;
        const fotos = prenda.fotos?.length || 0;

        html += `
            <div style="margin-bottom: 1rem; padding: 0.75rem; background: #f0f9ff; border-left: 3px solid #0066cc; border-radius: 4px;">
                <strong style="color: #0052a3;">Prenda ${index + 1}: ${prenda.nombre_producto || 'Sin nombre'}</strong>
                <div style="font-size: 0.9rem; color: #666; margin-top: 0.5rem;">
                    <div>📏 Tallas: ${tallas}</div>
                    <div> Telas: ${telas}</div>
                    <div>📸 Fotos: ${fotos}</div>
                </div>
            </div>
        `;
    });

    html += `</div>`;
    return html;
};

/**
 * Hook para ejecutarse ANTES de validar el formulario completo
 * Verifica si estamos en modo PRENDA sin cotización
 */
window.hookPreValidacionPrendaSinCotizacion = function() {
    const tipoPedido = document.querySelector('input[name="tipo_pedido_editable"]:checked')?.value;
    const tipoNuevo = tipoPedido === 'nuevo';
    const tipoPrendaSelect = document.getElementById('tipo_pedido_nuevo')?.value;
    
    if (tipoNuevo && tipoPrendaSelect === 'P') {
        console.log(' Pre-validación: Modo PRENDA sin cotización detectado');
        return window.validarPrendasTipoPrendaSinCotizacion();
    }
    
    return true;
};

/**
 * Hook para ejecutarse ANTES de serializar datos para envío
 * Agrega datos de prendas PRENDA al objeto de envío
 */
window.hookSerializacionPrendaSinCotizacion = function(datosEnvio) {
    const tipoPedido = document.querySelector('input[name="tipo_pedido_editable"]:checked')?.value;
    const tipoNuevo = tipoPedido === 'nuevo';
    const tipoPrendaSelect = document.getElementById('tipo_pedido_nuevo')?.value;
    
    if (tipoNuevo && tipoPrendaSelect === 'P') {
        console.log('📤 Serializando datos PRENDA sin cotización');
        const datosPrenda = window.obtenerDatosPrendasTipoPrendaSinCotizacion();
        
        // Agregar datos de prendas al objeto de envío
        datosEnvio.prendas = datosPrenda.prendas;
        datosEnvio.fotosNuevas = datosPrenda.fotosNuevas;
        datosEnvio.telasFotosNuevas = datosPrenda.telasFotosNuevas;
        datosEnvio.prendasEliminadas = datosPrenda.prendasEliminadas;
        datosEnvio.tipoPedidoNuevo = 'P'; // PRENDA
        
        console.log(' Datos PRENDA agregados:', datosEnvio);
    }
    
    return datosEnvio;
};

/**
 * Enviar pedido PRENDA sin cotización al servidor
 * NUEVA ESTRATEGIA: Usar gestorDatosPedidoJSON que acumula TODO en un JSON
 * @returns {Promise} Promise que resuelve cuando el pedido se guarda
 */
window.enviarPrendaSinCotizacion = function() {
    return new Promise(async (resolve, reject) => {
        try {
            console.log(`🚀 ============ INICIANDO ENVÍO PRENDA SIN COTIZACIÓN ============`);
            
            // Validar datos
            console.log(` [1] Validando prendas...`);
            if (!window.validarPrendasTipoPrendaSinCotizacion()) {
                console.error(` Validación fallida`);
                reject(new Error('Validación fallida'));
                return;
            }
            console.log(` [1] Validación OK`);

            // Obtener cliente
            const cliente = document.getElementById('cliente_editable')?.value;
            const formaPago = document.getElementById('forma_de_pago_editable')?.value || '';

            console.log(` Cliente: ${cliente}, Forma Pago: ${formaPago}`);

            if (!cliente) {
                console.error(` Cliente no especificado`);
                reject(new Error('Cliente no especificado'));
                return;
            }

            //  USAR GESTOR CENTRALIZADO JSON
            if (!window.gestorDatosPedidoJSON) {
                console.error(` GestorDatosPedidoJSON no disponible`);
                reject(new Error('GestorDatosPedidoJSON no inicializado'));
                return;
            }

            // Crear FormData con todos los datos del JSON
            console.log(` [2] Preparando FormData desde gestorDatosPedidoJSON...`);
            const formData = window.gestorDatosPedidoJSON.crearFormData();
            
            // Agregar cliente y forma de pago
            formData.append('cliente', cliente);
            formData.append('forma_de_pago', formaPago);
            formData.append('tipo_pedido', 'P'); // PRENDA sin cotización

            console.log(` [2] FormData preparado`);

            // Enviar al servidor
            console.log(` [3] Enviando FormData al servidor...`);
            
            const response = await fetch('/asesores/pedidos-produccion/crear-prenda-sin-cotizacion', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]')?.value || ''
                }
            });

            console.log(` [3] Respuesta recibida del servidor: ${response.status} ${response.statusText}`);

            const result = await response.json();
            
            console.log(` [4] Resultado JSON:`, result);

            if (!response.ok) {
                console.error(` Error en respuesta: ${result.message}`);
                throw new Error(result.message || 'Error al crear el pedido');
            }

            console.log(` ============ ENVÍO COMPLETADO CON ÉXITO ============\n`);

            // Mostrar éxito
            Swal.fire({
                title: ' Pedido creado',
                html: `
                    <div style="text-align: left;">
                        <p><strong>Número de Pedido:</strong> ${result.numero_pedido}</p>
                        <p><strong>Total de prendas:</strong> ${window.gestorDatosPedidoJSON.datosCompletos.prendas.length}</p>
                    </div>
                `,
                icon: 'success',
                confirmButtonText: 'Ir a Pedidos',
                confirmButtonColor: '#0ea5e9'
            }).then(() => {
                // Limpiar datos y redirigir
                window.gestorDatosPedidoJSON.limpiar();
                window.location.href = '/asesores/pedidos-produccion';
            });

            resolve(result);
            
        } catch (error) {
            console.error(` Error al enviar pedido PRENDA:`, error);
            Swal.fire('Error', error.message || 'Error al crear el pedido', 'error');
            reject(error);
        }
    });
};

