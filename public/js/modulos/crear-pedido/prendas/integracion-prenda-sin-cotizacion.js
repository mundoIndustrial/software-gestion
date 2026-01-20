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

    logWithEmoji('', 'Validación de prendas PRENDA completada correctamente');
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
        logWithEmoji('🗑️', 'Módulo de prendas PRENZA sin cotización limpiado');
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

            logWithEmoji('', 'Pedido PRENDA creado exitosamente', result);
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
                Swal.fire('Error', 'El cliente es requerido', 'error');
                reject(new Error('Cliente requerido'));
                return;
            }

            logWithEmoji('📤', 'Enviando pedido PRENDA sin cotización', datosPrenda);

            //  SINCRONIZAR de_bodega DESDE DOM ANTES DE ENVIAR
            datosPrenda.prendas.forEach((prenda, index) => {
                const container = document.querySelector(`[data-prenda-index="${index}"]`);
                if (container) {
                    const checkboxBodega = container.querySelector('.checkbox-de-bodega');
                    if (checkboxBodega) {
                        prenda.de_bodega = checkboxBodega.checked;
                        logWithEmoji('', `Prenda ${index}: de_bodega sincronizado = ${prenda.de_bodega}`);
                    }
                }
            });

            // 🔴 USAR FormData PARA ENVIAR ARCHIVOS
            console.log(` [3] Construyendo FormData...`);
            const formData = new FormData();
            formData.append('cliente', cliente);
            formData.append('forma_de_pago', formaPago);
            formData.append('_token', document.querySelector('input[name="_token"]')?.value || '');

            // Agregar prendas como JSON
            datosPrenda.prendas.forEach((prenda, index) => {
                console.log(`\n [3.${index}] Procesando prenda #${index}: ${prenda.nombre_producto}`);
                
                formData.append(`prendas[${index}][nombre_producto]`, prenda.nombre_producto || '');
                formData.append(`prendas[${index}][descripcion]`, prenda.descripcion || '');
                
                //  GENERO: Puede ser single string o array de múltiples géneros
                const generos = Array.isArray(prenda.genero) 
                    ? prenda.genero 
                    : (prenda.genero ? [prenda.genero] : []);
                console.log(`   📌 Géneros: ${JSON.stringify(generos)}`);
                formData.append(`prendas[${index}][genero]`, JSON.stringify(generos));

                //  ESTRUCTURA DE TALLAS POR GÉNERO: {genero: {talla: cantidad}}
                let cantidadPorGeneroTalla = {};
                
                console.log(`    generosConTallas en prenda:`, prenda.generosConTallas);
                console.log(`    cantidadesPorTalla en prenda:`, prenda.cantidadesPorTalla);
                
                if (prenda.generosConTallas && Object.keys(prenda.generosConTallas).length > 0) {
                    // Usar la estructura optimizada
                    cantidadPorGeneroTalla = prenda.generosConTallas;
                    console.log(`    Usando generosConTallas optimizado:`, cantidadPorGeneroTalla);
                } else if (prenda.cantidadesPorTalla && Object.keys(prenda.cantidadesPorTalla).length > 0) {
                    // Convertir estructura antigua a nueva
                    generos.forEach(genero => {
                        cantidadPorGeneroTalla[genero] = { ...prenda.cantidadesPorTalla };
                    });
                    console.log(`    Convertida estructura antigua a:`, cantidadPorGeneroTalla);
                } else {
                    console.warn(`    NO HAY CANTIDADES DE TALLAS PARA ESTA PRENDA`);
                }
                
                //  ENVIAR cantidad_talla COMO JSON ÚNICO
                console.log(`   📤 Enviando cantidad_talla como JSON:`, JSON.stringify(cantidadPorGeneroTalla));
                if (Object.keys(cantidadPorGeneroTalla).length > 0) {
                    formData.append(`prendas[${index}][cantidad_talla]`, JSON.stringify(cantidadPorGeneroTalla));
                } else {
                    console.warn(`    Enviando cantidad_talla vacío`);
                    formData.append(`prendas[${index}][cantidad_talla]`, JSON.stringify({}));
                }
                
                // COMPATIBILIDAD
                if (prenda.cantidadesPorTalla) {
                    Object.entries(prenda.cantidadesPorTalla).forEach(([talla, cantidad]) => {
                        if (cantidad > 0) {
                            formData.append(`prendas[${index}][cantidades][${talla}]`, cantidad);
                        }
                    });
                }

                //  AGREGAR OBSERVACIONES - Capturar desde el DOM del modal o desde prenda
                // Primero intentar desde el DOM del modal (si está abierto)
                let obs_manga = document.getElementById('manga-obs')?.value?.trim() || prenda.variantes?.tipo_manga_obs || prenda.obs_manga || '';
                let obs_broche = document.getElementById('broche-obs')?.value?.trim() || prenda.variantes?.tipo_broche_obs || prenda.obs_broche || '';
                let obs_bolsillos = document.getElementById('bolsillos-obs')?.value?.trim() || prenda.variantes?.tiene_bolsillos_obs || prenda.obs_bolsillos || '';
                let obs_reflectivo = document.getElementById('reflectivo-obs')?.value?.trim() || prenda.variantes?.tiene_reflectivo_obs || prenda.obs_reflectivo || '';
                
                console.log(`    Obs - Manga: "${obs_manga}", Broche: "${obs_broche}", Bolsillos: "${obs_bolsillos}", Reflectivo: "${obs_reflectivo}"`);
                
                formData.append(`prendas[${index}][obs_manga]`, obs_manga);
                formData.append(`prendas[${index}][obs_broche]`, obs_broche);
                formData.append(`prendas[${index}][obs_bolsillos]`, obs_bolsillos);
                formData.append(`prendas[${index}][obs_reflectivo]`, obs_reflectivo);

                //  CAMPOS BOOLEANOS Y VARIACIONES
                formData.append(`prendas[${index}][tipo_manga]`, prenda.variantes?.tipo_manga || prenda.tipo_manga || 'No aplica');
                formData.append(`prendas[${index}][tipo_broche]`, prenda.variantes?.tipo_broche || prenda.tipo_broche || 'No aplica');
                formData.append(`prendas[${index}][tiene_bolsillos]`, (prenda.variantes?.tiene_bolsillos || prenda.tiene_bolsillos) ? '1' : '0');
                formData.append(`prendas[${index}][tiene_reflectivo]`, (prenda.variantes?.tiene_reflectivo || prenda.tiene_reflectivo) ? '1' : '0');
                
                //  CAMPO DE BODEGA
                formData.append(`prendas[${index}][de_bodega]`, prenda.de_bodega ? '1' : '0');

                // Tallas seleccionadas
                if (prenda.tallas && Array.isArray(prenda.tallas)) {
                    prenda.tallas.forEach(talla => {
                        formData.append(`prendas[${index}][tallas][]`, talla);
                    });
                }

                // Variantes (telas, etc)
                if (prenda.variantes) {
                    formData.append(`prendas[${index}][variantes]`, JSON.stringify(prenda.variantes));
                }

                // Agregar telas como estructura para que coincida con FormData de fotos
                if (prenda.variantes?.telas_multiples && Array.isArray(prenda.variantes.telas_multiples)) {
                    prenda.variantes.telas_multiples.forEach((tela, telaIdx) => {
                        formData.append(`prendas[${index}][telas][${telaIdx}][nombre_tela]`, tela.nombre_tela || '');
                        formData.append(`prendas[${index}][telas][${telaIdx}][color]`, tela.color || '');
                        formData.append(`prendas[${index}][telas][${telaIdx}][referencia]`, tela.referencia || '');
                    });
                }
            });

            // 📸 AGREGAR IMÁGENES DE PRENDAS
            logWithEmoji('📸', 'Procesando imágenes de prendas...');
            Object.entries(datosPrenda.fotosNuevas || {}).forEach(([prendaIndex, fotos]) => {
                if (Array.isArray(fotos)) {
                    fotos.forEach((foto, fotoIndex) => {
                        // Foto puede ser un objeto {file, url, fileName, ...} o un File directo
                        const archivo = foto instanceof File ? foto : (foto && foto.file instanceof File ? foto.file : null);
                        
                        if (archivo) {
                            formData.append(`prendas[${prendaIndex}][fotos][]`, archivo);
                            logWithEmoji('', `Imagen de prenda ${prendaIndex + 1} agregada: ${archivo.name}`);
                        } else if (typeof foto === 'string') {
                            // Si es una URL existente, guardarla igual
                            formData.append(`prendas[${prendaIndex}][fotos_existentes][]`, foto);
                        }
                    });
                }
            });

            // 📸 AGREGAR IMÁGENES DE TELAS
            logWithEmoji('📸', 'Procesando imágenes de telas...');
            Object.entries(datosPrenda.telasFotosNuevas || {}).forEach(([prendaIndex, telas]) => {
                Object.entries(telas).forEach(([telaIndex, fotos]) => {
                    if (Array.isArray(fotos)) {
                        fotos.forEach((foto, fotoIndex) => {
                            // Foto puede ser un objeto {file, url, fileName, ...} o un File directo
                            const archivo = foto instanceof File ? foto : (foto && foto.file instanceof File ? foto.file : null);
                            
                            if (archivo) {
                                formData.append(`prendas[${prendaIndex}][telas][${telaIndex}][fotos][]`, archivo);
                                logWithEmoji('', `Imagen de tela de prenda ${prendaIndex + 1} agregada: ${archivo.name}`);
                            } else if (typeof foto === 'string') {
                                // Si es una URL existente
                                formData.append(`prendas[${prendaIndex}][telas][${telaIndex}][fotos_existentes][]`, foto);
                            }
                        });
                    }
                });
            });
            // 🔄 AGREGAR PROCESOS CON IMÁGENES
            logWithEmoji('⚙️', 'Procesando procesos con imágenes...');
            
            datosPrenda.prendas.forEach((prenda, prendaIndex) => {
                if (!prenda.procesos || typeof prenda.procesos !== 'object') {
                    return; // No hay procesos para esta prenda
                }
                
                Object.entries(prenda.procesos).forEach(([tipoProceso, procesoData]) => {
                    if (!procesoData || !procesoData.datos) {
                        return; // No hay datos válidos para este proceso
                    }
                    
                    const datos = procesoData.datos;
                    const imagenes = datos.imagenes || [];
                    
                    // Agregar datos del proceso (sin las imágenes por ahora)
                    const datosProcesoSinImagenes = { ...datos };
                    delete datosProcesoSinImagenes.imagenes;
                    
                    formData.append(
                        `prendas[${prendaIndex}][procesos][${tipoProceso}]`,
                        JSON.stringify(datosProcesoSinImagenes)
                    );
                    
                    logWithEmoji('', `Proceso "${tipoProceso}" agregado para prenda ${prendaIndex + 1}`);
                    
                    // Agregar imágenes del proceso como archivos reales
                    imagenes.forEach((imagen, imagenIndex) => {
                        if (imagen instanceof File) {
                            // Es un File object - agregar directamente
                            formData.append(
                                `prendas[${prendaIndex}][procesos][${tipoProceso}][imagenes][]`,
                                imagen
                            );
                            logWithEmoji('📸', `Imagen ${imagenIndex + 1} del proceso "${tipoProceso}" agregada: ${imagen.name}`);
                        } else if (typeof imagen === 'string') {
                            // Es una URL existente o ruta
                            formData.append(
                                `prendas[${prendaIndex}][procesos][${tipoProceso}][imagenes_existentes][]`,
                                imagen
                            );
                            logWithEmoji('🔗', `Imagen existente ${imagenIndex + 1} del proceso "${tipoProceso}" referenciada`);
                        }
                    });
                });
            });
            // Enviar al servidor
            console.log(` [4] Enviando FormData al servidor...`);
            console.log(` FormData contiene:`, {
                cliente: cliente,
                forma_de_pago: formaPago,
                prendas_count: datosPrenda.prendas.length
            });
            
            const response = await fetch('/asesores/pedidos-produccion/crear-prenda-sin-cotizacion', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]')?.value || ''
                }
            });

            console.log(` [4] Respuesta recibida del servidor: ${response.status} ${response.statusText}`);

            const result = await response.json();
            
            console.log(` [5] Resultado JSON:`, result);

            if (!response.ok) {
                console.error(` Error en respuesta: ${result.message}`);
                throw new Error(result.message || 'Error al crear el pedido');
            }

            logWithEmoji('', 'Pedido PRENDA creado exitosamente', result);
            console.log(` ============ ENVÍO COMPLETADO CON ÉXITO ============\n`);

            // Mostrar éxito
            Swal.fire({
                title: ' Pedido creado',
                html: `
                    <div style="text-align: left;">
                        <p><strong>Número de Pedido:</strong> ${result.numero_pedido}</p>
                        <p><strong>Total de prendas:</strong> ${datosPrenda.prendas.length}</p>
                        <p><strong>Cantidad total:</strong> ${result.cantidad_total}</p>
                    </div>
                `,
                icon: 'success',
                confirmButtonText: 'Ir a Pedidos',
                cancelButtonText: 'Ver Pedido'
            }).then((res) => {
                if (res.isConfirmed) {
                    // Redirigir al listado de pedidos
                    window.location.href = result.redirect_url || '/asesores/pedidos';
                } else if (res.isDismissed && res.dismiss === Swal.DismissReason.cancel) {
                    // Ver el pedido creado
                    window.location.href = `/asesores/pedidos-produccion/${result.pedido_id}`;
                }
            });

            resolve(result);

        } catch (error) {
            logWithEmoji('', 'Error al enviar pedido PRENDA', error.message);
            console.error('Error completo:', error);
            
            //  NO RECARGAR LA PÁGINA - Mantener el formulario intacto
            // Hacer scroll al error para que el usuario lo vea
            Swal.fire({
                title: ' Error al enviar',
                text: error.message,
                icon: 'error',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#dc3545',
                didClose: () => {
                    // Hacer scroll al contenedor de prendas para que vea qué datos están faltantes
                    const prendasContainer = document.getElementById('prendas-container-editable');
                    if (prendasContainer) {
                        prendasContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }
            }).catch(() => {
                // En caso de que Swal tenga error, seguir mostrando el error
                console.warn('No se pudo mostrar el modal de error');
            });
            
            reject(error);
        }
    });
};

/**
 * Hook para ejecutarse DESPUÉS de envío exitoso
 * Limpia el módulo
 */
window.hookPostEnvioPrendaSinCotizacion = function(response) {
    const tipoPedido = document.querySelector('input[name="tipo_pedido_editable"]:checked')?.value;
    const tipoNuevo = tipoPedido === 'nuevo';
    const tipoPrendaSelect = document.getElementById('tipo_pedido_nuevo')?.value;
    
    if (tipoNuevo && tipoPrendaSelect === 'P') {
        console.log(' Post-envío: Limpiando módulo PRENDA sin cotización');
        window.limpiarPrendasTipoPrendaSinCotizacion();
    }
};

logWithEmoji('', 'Integración de validación y envío para PRENDA sin cotización cargada');
