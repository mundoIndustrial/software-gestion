/**
 * VALIDACIÓN Y ENVÍO DE DATOS - FASE 3
 * 
 * Utiliza los gestores para validar y estructurar datos antes de envío
 * Se carga DESPUÉS de init-gestores-fase2.js pero ANTES de crear-pedido-editable.js
 * 
 * Proporciona funciones reutilizables para validar y obtener datos
 */

(function() {
    'use strict';

    /**
     * Validar formulario antes de enviar
     * Usa validación de gestores
     * 
     * @returns {Object} {valido: boolean, errores: Array}
     */
    window.validarFormularioConGestores = function() {
        const errores = [];

        // Validar cliente
        const cliente = document.getElementById('cliente_editable')?.value;
        if (!cliente || cliente.trim() === '') {
            errores.push('Cliente es requerido');
        }

        // Validar selección de cotización o modo sin cotización
        const cotizacionId = document.getElementById('cotizacion_id_editable')?.value;
        const seccionCotizacion = document.getElementById('cotizacion_search_editable')?.closest('.form-section');
        const esSinCotizacion = seccionCotizacion && seccionCotizacion.style.display === 'none';

        if (!esSinCotizacion && !cotizacionId) {
            errores.push('Selecciona una cotización o crea un pedido sin cotización');
        }

        // Validar prendas
        const prendasContainer = document.getElementById('prendas-container-editable');
        if (!prendasContainer || prendasContainer.querySelectorAll('.prenda-card-editable').length === 0) {
            errores.push('Debe haber al menos una prenda');
        }

        // Validar usando gestores si están disponibles
        if (window.gestorPrendas) {
            const validacionPrendas = window.gestorPrendas.validar();
            if (!validacionPrendas.valido) {
                errores.push(...validacionPrendas.errores);
            }
        }

        if (window.currentEsLogo && window.gestorLogo) {
            const validacionLogo = window.gestorLogo.validar();
            if (!validacionLogo.valido) {
                errores.push(...validacionLogo.errores);
            }
        }

        return {
            valido: errores.length === 0,
            errores: errores
        };
    };

    /**
     * Mostrar errores de validación al usuario
     * 
     * @param {Array} errores - Array de mensajes de error
     */
    window.mostrarErroresValidacion = function(errores) {
        if (!errores || errores.length === 0) return;

        const listaErrores = errores
            .map(err => `<li style="text-align: left; margin-bottom: 0.5rem;">• ${err}</li>`)
            .join('');

        mostrarError(
            ' Validación fallida',
            `<ul style="margin: 1rem 0; padding-left: 1.5rem;">${listaErrores}</ul>`,
            10000  // Duración más larga para leer errores
        );
    };

    /**
     * Preparar datos completos del pedido para envío
     * Estructura consistente con o sin cotización
     * 
     * @returns {Object} Datos formateados para servidor
     */
    window.prepararDatosParaEnvio = function(options) {
        const soloConCantidades = !options || options.soloConCantidades !== false;
        const cotizacionId = document.getElementById('cotizacion_id_editable')?.value;
        const seccionCotizacion = document.getElementById('cotizacion_search_editable')?.closest('.form-section');
        const esSinCotizacion = seccionCotizacion && seccionCotizacion.style.display === 'none';

        const datos = {
            // Datos básicos
            cliente: document.getElementById('cliente_editable')?.value || '',
            orden_compra: document.getElementById('orden_compra_editable')?.value || '',
            asesora: document.getElementById('asesora_editable')?.value || '',
            forma_de_pago: document.getElementById('forma_de_pago_editable')?.value || '',
            dia_de_entrega: parseInt(document.getElementById('dia_de_entrega_editable')?.value || '', 10) || null,
            numero_cotizacion: esSinCotizacion ? null : cotizacionId,
            es_sin_cotizacion: esSinCotizacion,
            
            // Datos de prendas Y epps - unificados en items
            prendas: [],
            items: [],  //  AGREGADO: array unificado con prendas + epps
            epps: [],
            fotos_nuevas: {},
            
            // Datos de logo (si aplica)
            logo: null,
            
            // Datos reflectivos (si aplica)
            reflectivo: null
        };

        const storePayload = (typeof window.getPedidoSessionStore === 'function')
            ? window.getPedidoSessionStore().toPayload()
            : null;
        const usarStoreBase = !!(storePayload && Array.isArray(storePayload.items) && storePayload.items.length > 0);

        if (usarStoreBase) {
            const prendasStore = Array.isArray(storePayload.prendas) ? storePayload.prendas : [];
            const eppsStore = Array.isArray(storePayload.epps) ? storePayload.epps : [];

            datos.prendas = prendasStore.map((prenda) => ({
                tipo: 'prenda',
                uid: prenda.uid || prenda._local_id || prenda.local_id || null,
                local_id: prenda.local_id || prenda._local_id || prenda.uid || null,
                _local_id: prenda._local_id || prenda.local_id || prenda.uid || null,
                nombre_producto: prenda.nombre_producto || prenda.nombre_prenda || prenda.nombre || '',
                descripcion: prenda.descripcion || '',
                de_bodega: prenda.de_bodega !== undefined ? prenda.de_bodega : 1,
                genero: prenda.genero || '',
                cantidades: prenda.cantidades || prenda.cantidad_talla || prenda.cantidadesPorTalla || {},
                imagenes: Array.isArray(prenda.imagenes) ? prenda.imagenes : [],
                telas: Array.isArray(prenda.telas) ? prenda.telas : (Array.isArray(prenda.telasAgregadas) ? prenda.telasAgregadas : []),
                procesos: prenda.procesos || {},
                variaciones: prenda.variaciones || prenda.variantes || {},
                origen: prenda.origen || 'bodega'
            }));

            datos.epps = eppsStore.map((epp) => ({
                tipo: 'epp',
                uid: epp.uid || epp._local_id || epp.local_id || null,
                local_id: epp.local_id || epp._local_id || epp.uid || null,
                _local_id: epp._local_id || epp.local_id || epp.uid || null,
                epp_id: epp.epp_id || null,
                pedido_epp_id: epp.pedido_epp_id || epp.pedidoEppId || null,
                nombre_epp: epp.nombre_epp || epp.nombre_completo || epp.nombre || '',
                categoria: epp.categoria || '',
                cantidad: epp.cantidad || 1,
                observaciones: epp.observaciones || null,
                imagenes: Array.isArray(epp.imagenes) ? epp.imagenes : []
            }));

            datos.items = [...datos.prendas, ...datos.epps];
        }

        // ========== RECOPILACIÓN DE PRENDAS DEL GESTOR ==========
        // FIX: Si gestionItemsUI está disponible (modo edición de borrador), usarlo como fuente PRIMARIA
        // PERO COMBINAR con prendas nuevas de gestorPrendaSinCotizacion (prendas agregadas sin guardar borrador)
	        if (!usarStoreBase && window.gestionItemsUI && window.gestionItemsUI.prendas && window.gestionItemsUI.prendas.length > 0) {
            // PRIORIDAD: Usar gestionItemsUI (prendas del borrador siendo editado)
            const prendasEdicion = window.gestionItemsUI.prendas;
            const fotosNuevasGestor = window.gestorPrendaSinCotizacion?.fotosNuevas || {};
            const telasNuevasGestor = window.gestorPrendaSinCotizacion?.telasFotosNuevas || {};

            prendasEdicion.forEach((prenda, index) => {
                const prendaParaEnviar = {
                    tipo: 'prenda',  //  AGREGADO: identificador de tipo
                    uid: prenda.uid || prenda._local_id || prenda.local_id || null,
                    local_id: prenda.local_id || prenda._local_id || prenda.uid || null,
                    _local_id: prenda._local_id || prenda.local_id || prenda.uid || null,
                    nombre_producto: prenda.nombre_producto || prenda.nombre_prenda || '',
                    descripcion: prenda.descripcion || '',
                    de_bodega: prenda.de_bodega !== undefined ? prenda.de_bodega : 1,  //  AGREGADO: origen (bodega=1, confección=0)
                    genero: prenda.genero || '',
                    cantidades: prenda.cantidadesPorTalla || prenda.cantidad_talla || {},
                    //  AGREGADO: recolectar imágenes desde gestor
                    imagenes: fotosNuevasGestor[index] || prenda.imagenes || [],
                    //  AGREGADO: recolectar telas desde prenda
                    telas: (prenda.telasAgregadas || []).map((tela, telaIdx) => ({
                        tela: tela.nombre_tela || tela.tela || '',
                        color: tela.color || tela.color_nombre || '',
                        referencia: tela.referencia || '',
                        //  Imágenes de tela
                        imagenes: telasNuevasGestor?.[index]?.[telaIdx] || tela.imagenes || []
                    }))
                };

                // Solo agregar si tiene cantidades (o si es modo borrador: incluir aunque estén vacías)
                if (!soloConCantidades || Object.keys(prendaParaEnviar.cantidades).length > 0) {
                    datos.prendas.push(prendaParaEnviar);
                    datos.items.push(prendaParaEnviar);  //  AGREGADO: también en items
                }
            });

            // FIX IMPORTANTES: Si estamos editando un borrador Y hemos agregado nuevas prendas sin guardar
            // gestor también tendrá prendas nuevas. MEZCLAR ambas fuentes.
            const prendasNuevasDelGestor = window.gestorPrendaSinCotizacion?.obtenerActivas() || [];
            if (prendasNuevasDelGestor.length > 0) {
                console.info('[prepararDatosParaEnvio] Detectadas prendas nuevas agregadas sin guardar borrador:', prendasNuevasDelGestor.length);
                const fotosNuev = window.gestorPrendaSinCotizacion?.fotosNuevas || {};
                const telasNuev = window.gestorPrendaSinCotizacion?.telasFotosNuevas || {};

                prendasNuevasDelGestor.forEach((prenda, index) => {
                    const prendaParaEnviar = {
                        tipo: 'prenda',
                        uid: prenda.uid || prenda._local_id || prenda.local_id || null,
                        local_id: prenda.local_id || prenda._local_id || prenda.uid || null,
                        _local_id: prenda._local_id || prenda.local_id || prenda.uid || null,
                        nombre_producto: prenda.nombre_producto || prenda.nombre_prenda || '',
                        descripcion: prenda.descripcion || '',
                        de_bodega: prenda.de_bodega !== undefined ? prenda.de_bodega : 1,
                        genero: prenda.genero || '',
                        cantidades: prenda.cantidadesPorTalla || {},
                        imagenes: fotosNuev[index] || prenda.imagenes || [],
                        telas: (prenda.telasAgregadas || []).map((tela, telaIdx) => ({
                            tela: tela.nombre_tela || tela.tela || '',
                            color: tela.color || tela.color_nombre || '',
                            referencia: tela.referencia || '',
                            imagenes: telasNuev?.[index]?.[telaIdx] || tela.imagenes || []
                        }))
                    };

                    if (!soloConCantidades || Object.keys(prendaParaEnviar.cantidades).length > 0) {
                        datos.prendas.push(prendaParaEnviar);
                        datos.items.push(prendaParaEnviar);
                    }
                });
            }
	        } else if (!usarStoreBase && window.gestorPrendaSinCotizacion && window.gestorPrendaSinCotizacion.prendas) {
            // Fallback: Usar gestor si gestionItemsUI no está disponible (modo nuevo sin ediciones)
            const prendas = window.gestorPrendaSinCotizacion.obtenerActivas() || [];
            const fotosNuevasGestor = window.gestorPrendaSinCotizacion.fotosNuevas || {};
            const telasNuevasGestor = window.gestorPrendaSinCotizacion.telasFotosNuevas || {};

            prendas.forEach((prenda, index) => {
                const prendaParaEnviar = {
                    tipo: 'prenda',  //  AGREGADO: identificador de tipo
                    uid: prenda.uid || prenda._local_id || prenda.local_id || null,
                    local_id: prenda.local_id || prenda._local_id || prenda.uid || null,
                    _local_id: prenda._local_id || prenda.local_id || prenda.uid || null,
                    nombre_producto: prenda.nombre_producto || '',
                    descripcion: prenda.descripcion || '',
                    de_bodega: prenda.de_bodega !== undefined ? prenda.de_bodega : 1,  //  AGREGADO: origen (bodega=1, confección=0)
                    genero: prenda.genero || '',
                    cantidades: prenda.cantidadesPorTalla || {},
                    //  AGREGADO: recolectar imágenes desde gestor
                    imagenes: fotosNuevasGestor[index] || prenda.imagenes || [],
                    //  AGREGADO: recolectar telas desde prenda
                    telas: (prenda.telasAgregadas || []).map((tela, telaIdx) => ({
                        tela: tela.nombre_tela || tela.tela || '',
                        color: tela.color || tela.color_nombre || '',
                        referencia: tela.referencia || '',
                        //  Imágenes de tela
                        imagenes: telasNuevasGestor?.[index]?.[telaIdx] || tela.imagenes || []
                    }))
                };

                // Solo agregar si tiene cantidades (o si es modo borrador: incluir aunque estén vacías)
                if (!soloConCantidades || Object.keys(prendaParaEnviar.cantidades).length > 0) {
                    datos.prendas.push(prendaParaEnviar);
                    datos.items.push(prendaParaEnviar);  //  AGREGADO: también en items
                }
            });
	        } else if (!usarStoreBase) {
            // Fallback: usar DOM si gestor no existe
            const prendasContainer = document.getElementById('prendas-container-editable');
            const prendaCards = prendasContainer?.querySelectorAll('.prenda-card-editable') || [];

            prendaCards.forEach((card, index) => {
                // Saltar si fue eliminada
                if (window.prendasEliminadas && window.prendasEliminadas.has(index)) {
                    return;
                }

                const prenda = {
                    index: index,
                    tipo: 'prenda',  //  AGREGADO: identificador de tipo
                    uid: null,
                    local_id: null,
                    _local_id: null,
                    nombre_producto: card.querySelector('.prenda-nombre')?.value || '',
                    descripcion: card.querySelector('.prenda-descripcion')?.value || '',
                    de_bodega: card.querySelector(`input[name="de_bodega[${index}]"]`)?.checked ? 1 : 0,  //  AGREGADO: origen
                    genero: card.querySelector(`select[name="genero[${index}]"]`)?.value || '',
                    cantidades: {},
                    //  AGREGADO: recolectar imágenes de prenda - extrayendo File objects del input
                    imagenes: [],
                    //  AGREGADO: recolectar telas
                    telas: []
                };

                //  Extraer imágenes de prenda desde input file (si existen archivos en el input)
                const inputImagenPrenda = card.querySelector(`input[type="file"][data-prenda-imagenes="${index}"]`);
                if (inputImagenPrenda && inputImagenPrenda.files && inputImagenPrenda.files.length > 0) {
                    prenda.imagenes = Array.from(inputImagenPrenda.files);
                }
                
                //  Extraer telas con sus imágenes
                const telasContainer = card.querySelector('.telas-container');
                if (telasContainer) {
                    const telaElements = telasContainer.querySelectorAll('.tela-item');
                    telaElements.forEach((telaEl) => {
                        const tela = {
                            tela: telaEl.querySelector('.tela-nombre')?.value || '',
                            color: telaEl.querySelector('.tela-color')?.value || '',
                            referencia: telaEl.querySelector('.tela-referencia')?.value || '',
                            imagenes: []
                        };
                        
                        //  Extraer imágenes de tela
                        const inputImagenTela = telaEl.querySelector(`input[type="file"][data-tela-imagenes]`);
                        if (inputImagenTela && inputImagenTela.files && inputImagenTela.files.length > 0) {
                            tela.imagenes = Array.from(inputImagenTela.files);
                        }
                        
                        if (tela.tela || tela.nombre_tela || tela.color || tela.imagenes.length > 0) {
                            prenda.telas.push(tela);
                        }
                    });
                }

                // Recopilar cantidades por talla
                card.querySelectorAll('.talla-cantidad').forEach(input => {
                    const talla = input.getAttribute('data-talla');
                    const cantidad = parseInt(input.value) || 0;
                    if (talla && cantidad > 0) {
                        prenda.cantidades[talla] = cantidad;
                    }
                });

                // Solo agregar si tiene cantidades (o si es modo borrador: incluir aunque estén vacías)
                if (!soloConCantidades || Object.keys(prenda.cantidades).length > 0) {
                    datos.prendas.push(prenda);
                    datos.items.push(prenda);  //  AGREGADO: también en items
                }
            });
        }

        // Agregar datos de logo si existen
        if (window.gestorLogo) {
            const datosLogo = window.gestorLogo.obtenerDatosFormato();
            if (datosLogo.tecnicas.length > 0 || datosLogo.ubicaciones.length > 0) {
                datos.logo = datosLogo;
            }
        }

        // Agregar datos de tipo de cotización
        if (!esSinCotizacion) {
            const tipoCotizacionElement = document.querySelector('[data-tipo-cotizacion]');
            datos.tipo_cotizacion = tipoCotizacionElement?.dataset.tipoCotizacion || 'P';
        }

        // ========== RECOPILACION DE EPPs ==========
        // Fuente unica de verdad: gestionItemsUI.
	        if (!usarStoreBase) {
	            let eppsDelPedido = [];
	            if (window.gestionItemsUI && typeof window.gestionItemsUI.obtenerItemsOrdenados === 'function') {
	                eppsDelPedido = (window.gestionItemsUI.obtenerItemsOrdenados() || []).filter(item => item.tipo === 'epp');
	            } else {
	                console.warn('[prepararDatosParaEnvio] gestionItemsUI no disponible; no se incluiran EPPs');
	            }

	            if (eppsDelPedido.length > 0) {
	                datos.epps = eppsDelPedido.map(epp => ({
	                    tipo: 'epp',
	                    uid: epp.uid || epp._local_id || epp.local_id || null,
	                    local_id: epp.local_id || epp._local_id || epp.uid || null,
	                    _local_id: epp._local_id || epp.local_id || epp.uid || null,
	                    epp_id: epp.epp_id,
	                    pedido_epp_id: epp.pedido_epp_id || epp.pedidoEppId || null,
	                    cantidad: epp.cantidad || 1,
	                    observaciones: epp.observaciones || null,
	                    imagenes: epp.imagenes || [],
	                    imagenes_editadas: epp.imagenes_editadas === true
	                }));
	                datos.items.push(...datos.epps);
	            }
	        }
        console.debug('[prepararDatosParaEnvio] EPPs a enviar:', datos.epps);

        return datos;
    };

    /**
     * Enviar datos al servidor
     * Maneja el fetch y muestra resultados
     * 
     * @param {Object} datos - Datos a enviar
     * @param {string} endpoint - URL del servidor
     * @returns {Promise} Promesa del fetch
     */
    window.enviarDatosAlServidor = function(datos, endpoint = '/api/pedidos') {
        return new Promise((resolve, reject) => {
            const csrfToken = document.querySelector('input[name="_token"]')?.value ||
                            document.querySelector('meta[name="csrf-token"]')?.content;

            if (!csrfToken) {

                mostrarError('Error', 'Token de seguridad no encontrado');
                reject(new Error('CSRF token missing'));
                return;
            }



            // Usar FormData para enviar archivos correctamente
            const formData = new FormData();
            
            // ========== AGREGAR JSON DEL PEDIDO SIN LAS IMÁGENES ==========
            const pedidoLimpio = {
                cliente: datos.cliente || '',
                orden_compra: datos.orden_compra || '',
                asesora: datos.asesora || '',
                forma_de_pago: datos.forma_de_pago || '',
                numero_cotizacion: datos.numero_cotizacion,
                // Si estamos editando un borrador, enviar su ID para que el backend lo convierta
                borrador_pedido_id: (window.modoEdicion && window.pedidoEditarId && window.pedidoEditarData?.pedido?.estado === 'Borrador')
                    ? window.pedidoEditarId
                    : null,
                es_sin_cotizacion: datos.es_sin_cotizacion,
                tipo_cotizacion: datos.tipo_cotizacion || null,
	                logo: datos.logo || null,
	                reflectivo: datos.reflectivo || null,
	                audit_payload: datos.audit_payload || null,
	                prendas: (datos.prendas || []).map(p => ({
                    uid: p.uid || p._local_id || p.local_id || null,
                    local_id: p.local_id || p._local_id || p.uid || null,
                    tipo: p.tipo,
                    _local_id: p._local_id || p.local_id || p.uid || null,
                    nombre_producto: p.nombre_producto,
                    descripcion: p.descripcion,
                    genero: p.genero,
                    cantidades: p.cantidades,
                    telas: (p.telas || []).map(t => ({tela: t.nombre_tela || t.tela, color: t.color, referencia: t.referencia}))
                })),
                epps: (datos.epps || []).map(e => ({
                    uid: e.uid || e._local_id || e.local_id || null,
                    local_id: e.local_id || e._local_id || e.uid || null,
                    _local_id: e._local_id || e.local_id || e.uid || null,
                    epp_id: e.epp_id,
                    cantidad: e.cantidad,
                    observaciones: e.observaciones,
                    // Importante: enviar URLs para que backend copie desde /storage
                    imagenes: Array.isArray(e.imagenes)
                        ? e.imagenes.map(img => {
                            if (!img) return null;
                            if (typeof img === 'string') return img;
                            if (img.url) return img.url;
                            if (img.preview) return img.preview;
                            if (img.ruta_webp) return img.ruta_webp;
                            if (img.ruta) return img.ruta;
                            return null;
                        }).filter(Boolean)
                        : []
                }))
            };
            formData.append('pedido', JSON.stringify(pedidoLimpio));
            console.debug('[enviarDatosAlServidor] JSON del pedido:', pedidoLimpio);
            
            // Agregar datos principales
            formData.append('cliente', datos.cliente || '');
            formData.append('orden_compra', datos.orden_compra || '');
            formData.append('asesora', datos.asesora || '');
            formData.append('forma_de_pago', datos.forma_de_pago || '');
            
            // Agregar items con sus imágenes
            if (datos.items && Array.isArray(datos.items)) {
                datos.items.forEach((item, itemIndex) => {
                    // Datos básicos del item
                    formData.append(`items[${itemIndex}][uid]`, item.uid || item._local_id || item.local_id || '');
                    formData.append(`items[${itemIndex}][local_id]`, item.local_id || item._local_id || item.uid || '');
                    formData.append(`items[${itemIndex}][_local_id]`, item._local_id || item.local_id || item.uid || '');
                    formData.append(`items[${itemIndex}][tipo]`, item.tipo || '');
                    formData.append(`items[${itemIndex}][nombre_producto]`, item.nombre_producto || '');
                    formData.append(`items[${itemIndex}][descripcion]`, item.descripcion || '');
                    formData.append(`items[${itemIndex}][origen]`, item.origen || 'bodega');
                    
                    // Tallas (array de objetos {genero, talla, cantidad})
                    if (item.tallas && Array.isArray(item.tallas)) {
                        formData.append(`items[${itemIndex}][tallas]`, JSON.stringify(item.tallas));
                    }
                    
                    // Variaciones como JSON
                    if (item.variaciones) {
                        const variacionesStr = typeof item.variaciones === 'string' 
                            ? item.variaciones 
                            : JSON.stringify(item.variaciones);
                        formData.append(`items[${itemIndex}][variaciones]`, variacionesStr);
                    }
                    
                    // Procesos como JSON
                    if (item.procesos) {
                        formData.append(`items[${itemIndex}][procesos]`, JSON.stringify(item.procesos));
                    }
                    
                    // Imágenes de prenda
                    if (item.imagenes && Array.isArray(item.imagenes)) {
                        item.imagenes.forEach((img) => {
                            if (img instanceof File) {
                                formData.append(`items[${itemIndex}][imagenes][]`, img);
                            } else if (img && img.file instanceof File) {
                                formData.append(`items[${itemIndex}][imagenes][]`, img.file);
                            }
                        });
                    }
                    
                    // Telas con imágenes
                    if (item.telas && Array.isArray(item.telas)) {
                        item.telas.forEach((tela, telaIdx) => {
                            formData.append(`items[${itemIndex}][telas][${telaIdx}][tela]`, tela.tela || '');
                            formData.append(`items[${itemIndex}][telas][${telaIdx}][color]`, tela.color || '');
                            formData.append(`items[${itemIndex}][telas][${telaIdx}][referencia]`, tela.referencia || '');
                            
                            // Imágenes de tela
                            if (tela.imagenes && Array.isArray(tela.imagenes)) {
                                tela.imagenes.forEach((img) => {
                                    if (img instanceof File) {
                                        formData.append(`items[${itemIndex}][telas][${telaIdx}][imagenes][]`, img);
                                    } else if (img && img.file instanceof File) {
                                        formData.append(`items[${itemIndex}][telas][${telaIdx}][imagenes][]`, img.file);
                                    }
                                });
                            }
                        });
                    }
                    
                    //  Procesos con imágenes (NUEVO)
                    if (item.procesos && Array.isArray(item.procesos)) {
                        item.procesos.forEach((proceso, procesoIdx) => {
                            formData.append(`items[${itemIndex}][procesos][${procesoIdx}][nombre]`, proceso.nombre || '');
                            
                            // Imágenes de proceso
                            if (proceso.imagenes && Array.isArray(proceso.imagenes)) {
                                proceso.imagenes.forEach((img) => {
                                    if (img instanceof File) {
                                        formData.append(`items[${itemIndex}][procesos][${procesoIdx}][imagenes][]`, img);
                                    } else if (img && img.file instanceof File) {
                                        formData.append(`items[${itemIndex}][procesos][${procesoIdx}][imagenes][]`, img.file);
                                    }
                                });
                            }
                        });
                    }
                    
                    // EPP específico
                    if (item.tipo === 'epp') {
                        formData.append(`items[${itemIndex}][epp_id]`, item.epp_id || '');
                        formData.append(`items[${itemIndex}][nombre]`, item.nombre || '');
                        formData.append(`items[${itemIndex}][codigo]`, item.codigo || '');
                        formData.append(`items[${itemIndex}][categoria]`, item.categoria || '');
                        formData.append(`items[${itemIndex}][cantidad]`, item.cantidad || 0);
                        formData.append(`items[${itemIndex}][observaciones]`, item.observaciones || '');
                        
                        // IGNORADO: tabla epp_imagenes no existe, usar pedido_epp_imagenes
                        // Las imágenes se guardan en pedido_epp_imagenes después de crear el pedido
                        console.debug(' [FORMULARIO] EPP sin enviar imágenes de epp_imagenes');
                        
                        // if (item.imagenes && Array.isArray(item.imagenes)) {
                        //     item.imagenes.forEach((img) => {
                        //         if (img instanceof File) {
                        //             formData.append(`items[${itemIndex}][epp_imagenes][]`, img);
                        //         } else if (img && img.file instanceof File) {
                        //             formData.append(`items[${itemIndex}][epp_imagenes][]`, img.file);
                        //         }
                        //     });
                        // }
                    }
                });
            }

            // Agregar EPPs al FormData
            if (datos.epps && Array.isArray(datos.epps)) {
                datos.epps.forEach((epp, eppIndex) => {
                    formData.append(`epps[${eppIndex}][uid]`, epp.uid || epp._local_id || epp.local_id || '');
                    formData.append(`epps[${eppIndex}][local_id]`, epp.local_id || epp._local_id || epp.uid || '');
                    formData.append(`epps[${eppIndex}][_local_id]`, epp._local_id || epp.local_id || epp.uid || '');
                    formData.append(`epps[${eppIndex}][epp_id]`, epp.epp_id || '');
                    formData.append(`epps[${eppIndex}][cantidad]`, epp.cantidad || 1);
                    if (epp.observaciones) {
                        formData.append(`epps[${eppIndex}][observaciones]`, epp.observaciones);
                    }
                    
                    //  Agregar imágenes de EPP al FormData
                    if (epp.imagenes && Array.isArray(epp.imagenes)) {
                        epp.imagenes.forEach((img, imgIdx) => {
                            if (img instanceof File) {
                                formData.append(`epps[${eppIndex}][imagenes][${imgIdx}]`, img);
                                console.debug(`[enviarDatosAlServidor] Imagen EPP agregada: epps[${eppIndex}][imagenes][${imgIdx}]`, img.name);
                            } else if (img && img.file instanceof File) {
                                formData.append(`epps[${eppIndex}][imagenes][${imgIdx}]`, img.file);
                                console.debug(`[enviarDatosAlServidor] Imagen EPP agregada: epps[${eppIndex}][imagenes][${imgIdx}]`, img.file.name);
                            }
                        });
                    }
                });
                console.debug('[enviarDatosAlServidor] EPPs agregados al FormData:', datos.epps);
            }

            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                    // NO incluir Content-Type - el navegador lo establece automáticamente con FormData
                },
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {


                if (data.success) {
                    mostrarExito(
                        '¡Éxito!',
                        `Pedido creado exitosamente${data.numero_pedido ? '\nNúmero: ' + data.numero_pedido : ''}`
                    );
                    resolve(data);
                } else {
                    throw new Error(data.message || 'Error desconocido al crear pedido');
                }
            })
            .catch(error => {

                mostrarError(
                    'Error al crear pedido',
                    error.message || 'Ocurrió un error inesperado'
                );
                reject(error);
            });
        });
    };

    /**
     * Procesar submit del formulario de forma segura
     * Válida → Prepara → Envía
     * 
     * @param {string} endpoint - URL para enviar datos
     * @returns {Promise}
     */
	    window.procesarSubmitFormulario = function(endpoint = '/api/asesores/pedidos/crear') {
	        // 1. VALIDAR
	        const validacion = window.validarFormularioConGestores();

        if (!validacion.valido) {

            window.mostrarErroresValidacion(validacion.errores);
            return Promise.reject('Validación fallida');
	        }

	        const itemsOrdenados = (typeof window.getPedidoSessionStore === 'function')
	            ? (window.getPedidoSessionStore().snapshot() || [])
	            : ((window.gestionItemsUI && typeof window.gestionItemsUI.obtenerItemsOrdenados === 'function')
	                ? (window.gestionItemsUI.obtenerItemsOrdenados() || [])
	                : []);
	        if (typeof window.sessionConsistencyCheck === 'function') {
	            const consistency = window.sessionConsistencyCheck(itemsOrdenados);
	            if (!consistency.ok) {
	                alert('No se puede enviar el pedido por inconsistencias de estado. Revisa la consola.');
	                return Promise.reject('sessionConsistencyCheck failed');
	            }
	        }

	        // 2. PREPARAR DATOS
	        const datos = window.prepararDatosParaEnvio();
	        if (!datos) {
	            return Promise.reject('prepararDatosParaEnvio returned null');
	        }
	        if (typeof window.serializarPedidoSeguro === 'function') {
	            try {
	                const serializadoSeguro = window.serializarPedidoSeguro(itemsOrdenados);
	                if (serializadoSeguro && Array.isArray(serializadoSeguro.items)) {
	                    datos.prendas = serializadoSeguro.prendas || [];
	                    datos.epps = serializadoSeguro.epps || [];
	                    datos.items = serializadoSeguro.items || [];
	                    datos.audit_payload = serializadoSeguro.audit_payload || null;
	                }
	            } catch (error) {
	                console.error('[procesarSubmitFormulario] Error serializando pedido de forma segura:', error);
	                alert('No se puede enviar el pedido por inconsistencias de serialización. Revisa consola.');
	                return Promise.reject(error);
	            }
	        }

	        console.groupCollapsed('[IDENTIDAD-QA][pre-submit] items');
	        (datos.items || []).forEach((item, idx) => {
	            console.log({
	                idx,
	                tipo: item.tipo || (item.epp_id ? 'epp' : 'prenda'),
	                uid: item.uid || null,
	                _local_id: item._local_id || null,
	                local_id: item.local_id || null,
	                epp_id: item.epp_id || null,
	                nombre: item.nombre || item.nombre_epp || item.nombre_producto || item.nombre_prenda || null
	            });
	        });
	        console.groupEnd();


	        // 3. ENVIAR
        return window.enviarDatosAlServidor(datos, endpoint)
            .then(response => {
                // Redirigir a lista de pedidos después de 2 segundos
                setTimeout(() => {
                    window.location.href = '/api/asesores/pedidos';
                }, 2000);
                return response;
            });
    };

    /**
     * Obtener resumen de pedido para vista previa
     * 
     * @returns {Object} Resumen con prendas, logo, totales
     */
    window.obtenerResumenPedido = function() {
        const datos = window.prepararDatosParaEnvio();
        
        return {
            cliente: datos.cliente,
            asesora: datos.asesora,
            numero_cotizacion: datos.numero_cotizacion,
            cantidad_prendas: datos.prendas.length,
            cantidad_total_prendas: datos.prendas.reduce((sum, p) => {
                return sum + Object.values(p.cantidades).reduce((a, b) => a + b, 0);
            }, 0),
            tiene_logo: datos.logo !== null,
            tiene_fotos: Object.keys(datos.fotos_nuevas).length > 0,
            datos_completos: datos
        };
    };

    // Log de disponibilidad

})();
