/**
 * ItemAPIService - Servicio de API para Ítems
 * 
 * Responsabilidad única: Comunicación con el backend
 * 
 * Principios SOLID aplicados:
 * - SRP: Solo gestiona llamadas a API
 * - DIP: Puede ser inyectado como dependencia
 * - OCP: Fácil de extender para nuevos endpoints
 */

class ItemAPIService {
    constructor(options = {}) {
        this.baseUrl = options.baseUrl || '/asesores/pedidos-editable';
        this.csrfToken = options.csrfToken || this.obtenerCSRFToken();
    }

    /**
     * Obtener token CSRF del DOM
     */
    obtenerCSRFToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    /**
     * Realizar petición HTTP genérica
     * @private
     */
    async realizarPeticion(url, opciones = {}) {
        // IMPORTANTE: Si el body es FormData, NO establecer Content-Type
        // FormData establece su propia cabecera con boundary
        const tieneFormData = opciones.body instanceof FormData;
        
        const configuracion = {
            headers: {
                'Accept': 'application/json',
                // Solo establecer Content-Type si NO es FormData
                ...(tieneFormData ? {} : { 'Content-Type': 'application/json' }),
                'X-CSRF-TOKEN': this.csrfToken,
                ...opciones.headers
            },
            ...opciones
        };

        const respuesta = await fetch(url, configuracion);
        
        if (!respuesta.ok) {
            // Intentar obtener el texto de error (puede ser HTML o JSON)
            const textoError = await respuesta.text();

            throw new Error(`HTTP error! status: ${respuesta.status}\n${textoError}`);
        }

        try {
            return await respuesta.json();
        } catch (error) {

            throw new Error(`Error al parsear respuesta JSON: ${error.message}`);
        }
    }

    /**
     * Obtener ítems desde el servidor
     */
    async obtenerItems() {
        try {
            return await this.realizarPeticion(`${this.baseUrl}/items`);
        } catch (error) {

            throw error;
        }
    }

    /**
     * Agregar un nuevo ítem
     */
    async agregarItem(itemData) {
        try {
            return await this.realizarPeticion(`${this.baseUrl}/items`, {
                method: 'POST',
                body: JSON.stringify(itemData)
            });
        } catch (error) {

            throw error;
        }
    }

    /**
     * Eliminar un ítem
     */
    async eliminarItem(index) {
        try {
            return await this.realizarPeticion(`${this.baseUrl}/items/${index}`, {
                method: 'DELETE'
            });
        } catch (error) {

            throw error;
        }
    }

    /**
     * Renderizar tarjeta de ítem (HTML)
     */
    async renderizarItemCard(item, index) {
        try {
            return await this.realizarPeticion(`${this.baseUrl}/render-item-card`, {
                method: 'POST',
                body: JSON.stringify({ item, index })
            });
        } catch (error) {

            throw error;
        }
    }

    /**
     * Validar un pedido completo
     */
    async validarPedido(pedidoData) {
        try {
            console.debug('[validarPedido] INICIO');
            console.log('[validarPedido] 📦 Datos recibidos:', pedidoData);
            console.log('[validarPedido] 📊 Prendas:', pedidoData.prendas?.length || 0);
            console.log('[validarPedido] 📊 EPPs:', pedidoData.epps?.length || 0);
            
            // PASO 1: Serializar JSON directamente (ya viene bien formado desde recolectarDatosPedido)
            const jsonString = JSON.stringify(pedidoData);
            console.debug(`[validarPedido] JSON serializado: ${jsonString.length} bytes`);
            console.log('[validarPedido] JSON String que se enviará:', jsonString);
            
            // PASO 2: Enviar en FormData con campo "pedido"
            const formData = new FormData();
            formData.append('pedido', jsonString);
            
            console.debug('[validarPedido] 📤 Enviando a /validar con FormData...');
            const respuesta = await this.realizarPeticion(`${this.baseUrl}/validar`, {
                method: 'POST',
                body: formData
            });
            
            console.debug('[validarPedido] Respuesta:', respuesta);
            return respuesta;
            
        } catch (error) {
            console.error('[validarPedido]  Error:', error);
            throw error;
        }
    }

    /**
     * Crear un nuevo pedido - FLUJO COMPLETO CON NORMALIZACIÓN
     */
    async crearPedido(pedidoData) {
        try {
            console.debug('[crearPedido] 📦 INICIO');

            // PASO 1: Extraer TODOS los Files PRIMERO (antes de cualquier normalización)
            console.debug('[crearPedido] PASO 1: Extrayendo files...');
            const filesExtraidos = await this.extraerFilesDelPedido(pedidoData);
            console.debug('[crearPedido] PASO 1 completo:', {
                prendas: filesExtraidos.prendas.length,
                archivos_totales: filesExtraidos.prendas.reduce((sum, p) => 
                    sum + p.imagenes.length + 
                    p.telas.reduce((t, ta) => t + ta.length, 0) + 
                    Object.values(p.procesos).reduce((s, proc) => s + proc.length, 0), 0)
            });

            // PASO 1.5: ACTUALIZAR pedidoData CON METADATOS de archivos extraídos
            // Para que la normalización tenga uid y formdata_key
            console.debug('[crearPedido] PASO 1.5: Inyectando metadatos en pedidoData...');
            filesExtraidos.prendas.forEach((prendaExtraida, prendaIdx) => {
                if (pedidoData.prendas && pedidoData.prendas[prendaIdx]) {
                    const prenda = pedidoData.prendas[prendaIdx];
                    
                    // Reemplazar imagenes de prenda con estructura enriquecida
                    prendaExtraida.imagenes.forEach((imgEnriquecida, imgIdx) => {
                        if (prenda.imagenes && prenda.imagenes[imgIdx]) {
                            prenda.imagenes[imgIdx] = {
                                file: imgEnriquecida.file,
                                formdata_key: imgEnriquecida.formdata_key,
                                uid: imgEnriquecida.uid
                            };
                        }
                    });
                    
                    // Reemplazar imagenes de telas
                    prendaExtraida.telas.forEach((telaEnriquecida, telaIdx) => {
                        if (prenda.telas && prenda.telas[telaIdx] && Array.isArray(telaEnriquecida)) {
                            telaEnriquecida.forEach((imgEnriquecida, imgIdx) => {
                                if (prenda.telas[telaIdx].imagenes && prenda.telas[telaIdx].imagenes[imgIdx]) {
                                    prenda.telas[telaIdx].imagenes[imgIdx] = {
                                        file: imgEnriquecida.file,
                                        formdata_key: imgEnriquecida.formdata_key,
                                        uid: imgEnriquecida.uid
                                    };
                                }
                            });
                        }
                    });
                    
                    // Reemplazar imagenes de procesos
                    Object.entries(prendaExtraida.procesos).forEach(([procesoKey, procesosEnriquecidos]) => {
                        if (prenda.procesos && prenda.procesos[procesoKey] && Array.isArray(procesosEnriquecidos)) {
                            console.debug(`[crearPedido] INYECTANDO procesos[${procesoKey}]:`, {
                                procesosEnriquecidos_length: procesosEnriquecidos.length,
                                procesos_exists: !!prenda.procesos[procesoKey],
                                proceso_estructura: prenda.procesos[procesoKey]
                            });
                            procesosEnriquecidos.forEach((imgEnriquecida, imgIdx) => {
                                // Intentar inyectar en datos.imagenes PRIMERO (estructura más común)
                                if (prenda.procesos[procesoKey].datos?.imagenes && Array.isArray(prenda.procesos[procesoKey].datos.imagenes)) {
                                    if (prenda.procesos[procesoKey].datos.imagenes[imgIdx]) {
                                        prenda.procesos[procesoKey].datos.imagenes[imgIdx] = {
                                            file: imgEnriquecida.file,
                                            formdata_key: imgEnriquecida.formdata_key,
                                            uid: imgEnriquecida.uid
                                        };
                                        console.debug(`[crearPedido] Inyectado en datos.imagenes[${imgIdx}]`);
                                    }
                                }
                                // Fallback: intentar en imagenes directo
                                else if (prenda.procesos[procesoKey].imagenes && Array.isArray(prenda.procesos[procesoKey].imagenes)) {
                                    if (prenda.procesos[procesoKey].imagenes[imgIdx]) {
                                        prenda.procesos[procesoKey].imagenes[imgIdx] = {
                                            file: imgEnriquecida.file,
                                            formdata_key: imgEnriquecida.formdata_key,
                                            uid: imgEnriquecida.uid
                                        };
                                        console.debug(`[crearPedido] Inyectado en imagenes[${imgIdx}]`);
                                    }
                                }
                            });
                        }
                    });
                }
            });
            
            // EPPs también
            filesExtraidos.epps.forEach((eppExtraida, eppIdx) => {
                if (pedidoData.epps && pedidoData.epps[eppIdx]) {
                    const epp = pedidoData.epps[eppIdx];
                    eppExtraida.imagenes.forEach((imgEnriquecida, imgIdx) => {
                        if (epp.imagenes && epp.imagenes[imgIdx]) {
                            epp.imagenes[imgIdx] = {
                                file: imgEnriquecida.file,
                                formdata_key: imgEnriquecida.formdata_key,
                                uid: imgEnriquecida.uid
                            };
                        }
                    });
                }
            });

            // PASO 2: Normalizar el pedido (elimina Files del JSON, evita ciclos)
            console.debug('[crearPedido] PASO 2: Normalizando...');
            
            // Validar que PayloadNormalizer esté disponible
            if (!window.PayloadNormalizer) {
                console.error('[crearPedido]  window.PayloadNormalizer no existe');
                throw new Error(' CRITICAL: window.PayloadNormalizer no está disponible. Verifica que payload-normalizer.js se cargó correctamente.');
            }
            
            if (typeof window.PayloadNormalizer.normalizar !== 'function') {
                console.error('[crearPedido]  window.PayloadNormalizer.normalizar no es una función', {
                    PayloadNormalizer: window.PayloadNormalizer,
                    tipo: typeof window.PayloadNormalizer,
                    metodos: Object.keys(window.PayloadNormalizer || {})
                });
                throw new Error(' CRITICAL: window.PayloadNormalizer.normalizar no es una función. PayloadNormalizer cargó incorrectamente.');
            }
            
            const pedidoNormalizado = window.PayloadNormalizer.normalizar(pedidoData);
            
            // Log según estructura
            if (pedidoNormalizado.prendas && pedidoNormalizado.epps) {
                console.debug('[crearPedido] PASO 2 completo - Prendas:', pedidoNormalizado.prendas.length, '- EPPs:', pedidoNormalizado.epps.length);
            } else {
                console.debug('[crearPedido] PASO 2 completo - Items:', pedidoNormalizado.items?.length);
            }

            // PASO 3: Construir FormData con JSON limpio + archivos
            console.debug('[crearPedido] PASO 3: Construyendo FormData...');
            
            if (typeof window.PayloadNormalizer.buildFormData !== 'function') {
                throw new Error(' CRITICAL: window.PayloadNormalizer.buildFormData no es una función.');
            }
            
            const formData = window.PayloadNormalizer.buildFormData(pedidoNormalizado, filesExtraidos);
            console.debug('[crearPedido] PASO 3 completo');

            // DEBUG: Verificar qué contiene FormData antes de enviar
            console.log('[crearPedido] 📋 Inspeccionando FormData antes de fetch:');
            let formDataDebug = [];
            for (let [key, value] of formData.entries()) {
                formDataDebug.push({
                    key: key,
                    tipo: value instanceof File ? 'File' : typeof value,
                    nombre: value instanceof File ? value.name : value.substring(0, 50),
                    size: value instanceof File ? value.size : 'N/A'
                });
            }
            console.log('[crearPedido] FormData entries:', formDataDebug);

            // PASO 4: Enviar
            console.debug('[crearPedido] PASO 4: Enviando POST a /crear');
            const respuesta = await fetch(`${this.baseUrl}/crear`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: formData
            });

            // Verificar respuesta
            if (!respuesta.ok) {
                const errorData = await respuesta.json().catch(() => ({ message: 'Error desconocido' }));
                console.error('[crearPedido]  Error del servidor:', {
                    status: respuesta.status,
                    errors: errorData.errors
                });

                if (respuesta.status === 422 && errorData.errors) {
                    const mensajesError = Object.entries(errorData.errors)
                        .map(([campo, mensajes]) => `${campo}: ${mensajes.join(', ')}`)
                        .join('\n');
                    throw new Error(`Validación fallida:\n${mensajesError}`);
                }

                throw new Error(errorData.message || `HTTP error! status: ${respuesta.status}`);
            }

            const resultado = await respuesta.json();
            console.debug('[crearPedido] ÉXITO:', {
                pedido_id: resultado.pedido_id,
                numero_pedido: resultado.numero_pedido
            });

            return resultado;

        } catch (error) {
            console.error('[crearPedido]  Error final:', error);
            throw error;
        }
    }

    /**
     * CRÍTICO: Limpiar completamente el payload removiendo TODOS los Files
     * Recorre recursivamente y reemplaza Files con null (pero MANTIENE estructura)
     * NO elimina arrays vacíos ni elementos null de objetos - mantiene estructura
     * @private
     */
    limpiarPayloadDeFiles(obj) {
        if (obj === null || obj === undefined) {
            return obj;
        }

        if (obj instanceof File) {
            return null; // Remover Files y reemplazar con null
        }

        if (Array.isArray(obj)) {
            // Mapear pero NO filtrar - mantener estructura
            return obj.map(item => this.limpiarPayloadDeFiles(item));
        }

        if (typeof obj === 'object') {
            const limpio = {};
            for (const [key, value] of Object.entries(obj)) {
                limpio[key] = this.limpiarPayloadDeFiles(value);
                // NO condicionar - copiar todos los keys
            }
            return limpio;
        }

        return obj;
    }



    /**
     * Normalizar tallas: convertir strings a números
     * { S: "20", M: "30" } → { S: 20, M: 30 }
     * @private
     */
    normalizarTallas(tallas) {
        if (!tallas || typeof tallas !== 'object') {
            return {};
        }

        const normalizadas = {};

        Object.entries(tallas).forEach(([genero, tallasCant]) => {
            if (!tallasCant || typeof tallasCant !== 'object') {
                return;
            }

            normalizadas[genero] = {};

            Object.entries(tallasCant).forEach(([talla, cantidad]) => {
                // Convertir a número, descartar si es 0 o NaN
                const num = parseInt(cantidad, 10);
                if (!isNaN(num) && num > 0) {
                    normalizadas[genero][talla] = num;
                }
            });

            // Si quedan tallas, mantener; si no, vaciar
            if (Object.keys(normalizadas[genero]).length === 0) {
                normalizadas[genero] = {};
            }
        });

        return normalizadas;
    }

    /**
     * Construir metadata para JSON string (sin Files)
     * Asegura que TODOS los datos necesarios estén presentes
     * INCLUYE telas completas Y procesos completos
     * @private
     */
    construirMetadata(sanitizado, filesExtraidos) {
        const metadata = {
            cliente: sanitizado.cliente,
            asesora: sanitizado.asesora,
            forma_de_pago: sanitizado.forma_de_pago,
            descripcion: sanitizado.descripcion || '',
            items: []
        };

        if (!Array.isArray(sanitizado.items)) {
            return metadata;
        }

        sanitizado.items.forEach((item, itemIdx) => {
            const itemMeta = {
                tipo: item.tipo,
                nombre_prenda: item.nombre_prenda,
                descripcion: item.descripcion,
                origen: item.origen,
                cantidad_talla: item.cantidad_talla || {},
                variaciones: item.variaciones || {},
                // IMPORTANTE: Incluir procesos completos CON todos los datos
                procesos: item.procesos ? this.normalizarProcesos(item.procesos) : {},
                // IMPORTANTE: Incluir telas COMPLETAS, no solo count
                telas: this.extraerTelasCompletas(item.telas || [])
            };

            metadata.items.push(itemMeta);
        });

        return metadata;
    }

    /**
     * Extraer telas COMPLETAS sin Files
     * Mantiene todos los datos de tela necesarios
     * @private
     */
    extraerTelasCompletas(telas) {
        if (!Array.isArray(telas)) {
            return [];
        }

        return telas.map(tela => ({
            tela_id: tela.tela_id,
            color_id: tela.color_id,
            tela_nombre: tela.tela,
            color_nombre: tela.color,
            referencia: tela.referencia,
            // Nota: imagenes van en FormData, no en JSON
            imagenes: [] // Array vacío para mantener estructura, serán inyectadas desde controller
        }));
    }

    /**
     * Normalizar procesos: asegurar que contenga TODOS los datos (sin Files)
     * Incluye ubicaciones, observaciones, tallas, etc.
     * @private
     */
    normalizarProcesos(procesos) {
        const normalizados = {};
        
        Object.entries(procesos).forEach(([procesoKey, proceso]) => {
            if (!proceso || typeof proceso !== 'object') {
                return;
            }

            // Extraer datos de ubicaciones correctamente
            let ubicaciones = [];
            if (Array.isArray(proceso.datos?.ubicaciones)) {
                ubicaciones = proceso.datos.ubicaciones;
            } else if (Array.isArray(proceso.ubicaciones)) {
                ubicaciones = proceso.ubicaciones;
            }

            // Extraer datos de tallas correctamente
            let tallas = {};
            if (typeof proceso.datos?.tallas === 'object' && proceso.datos.tallas !== null) {
                tallas = proceso.datos.tallas;
            } else if (typeof proceso.tallas === 'object' && proceso.tallas !== null) {
                tallas = proceso.tallas;
            }

            // Extraer observaciones
            const observaciones = proceso.datos?.observaciones || proceso.observaciones || '';
            const tipo = proceso.datos?.tipo || proceso.tipo || procesoKey;

            normalizados[procesoKey] = {
                tipo: tipo,
                ubicaciones: ubicaciones,
                observaciones: observaciones,
                tallas: tallas,
                // Nota: imagenes van en FormData, no en JSON
                imagenes: [] // Array vacío para mantener estructura, serán inyectadas desde controller
            };
        });

        return normalizados;
    }

    /**
     * Agregar archivos a FormData en rutas estructuradas separadas
     * @private
     */
    agregarFilesAFormDataSeparado(formData, filesExtraidos) {
        filesExtraidos.prendas.forEach(prenda => {
            const itemIdx = prenda.idx;
            
            // Imágenes de prenda: prendas[0][imagenes][0]
            prenda.imagenes.forEach((file, fileIdx) => {
                const key = `prendas[${itemIdx}][imagenes][${fileIdx}]`;
                formData.append(key, file);
                console.log(`[item-api-service] Archivo: ${key} (${file.name})`);
            });
            
            // Imágenes de telas: prendas[0][telas][0][imagenes][0]
            prenda.telas.forEach((telaFiles, telaIdx) => {
                if (Array.isArray(telaFiles)) {
                    telaFiles.forEach((file, fileIdx) => {
                        const key = `prendas[${itemIdx}][telas][${telaIdx}][imagenes][${fileIdx}]`;
                        formData.append(key, file);
                        console.log(`[item-api-service] Archivo: ${key} (${file.name})`);
                    });
                }
            });
            
            // Imágenes de procesos: prendas[0][procesos][0][imagenes][0]
            Object.entries(prenda.procesos).forEach(([procesoKey, procesoFiles]) => {
                if (Array.isArray(procesoFiles)) {
                    procesoFiles.forEach((file, fileIdx) => {
                        const key = `prendas[${itemIdx}][procesos][${procesoKey}][imagenes][${fileIdx}]`;
                        formData.append(key, file);
                        console.log(`[item-api-service] Archivo: ${key} (${file.name})`);
                    });
                }
            });
        });
    }

    /**
     * Convertir objeto a FormData, detectando archivos File automáticamente
     * @private
     */
    convertirAFormData(data, formData = new FormData(), parentKey = '') {
        for (let key in data) {
            if (!data.hasOwnProperty(key)) continue;
            
            const value = data[key];
            const formKey = parentKey ? `${parentKey}[${key}]` : key;
            
            // Si es un File, agregarlo directamente
            if (value instanceof File) {
                formData.append(formKey, value);
            }
            // Si es un array
            else if (Array.isArray(value)) {
                value.forEach((item, index) => {
                    const arrayKey = `${formKey}[${index}]`;
                    
                    if (item instanceof File) {
                        formData.append(arrayKey, item);
                    } else if (typeof item === 'object' && item !== null) {
                        this.convertirAFormData({ [index]: item }, formData, formKey);
                    } else {
                        formData.append(arrayKey, item !== null && item !== undefined ? item : '');
                    }
                });
            }
            // Si es un objeto (pero no File, Date, null)
            else if (typeof value === 'object' && value !== null && !(value instanceof Date)) {
                this.convertirAFormData(value, formData, formKey);
            }
            // Valor primitivo (string, number, boolean, null, undefined)
            else {
                formData.append(formKey, value !== null && value !== undefined ? value : '');
            }
        }
        
        return formData;
    }

    /**
     * Extraer TODOS los Files de la estructura de pedido
     * Retorna { prendas: [{ idx, imagenes, telas: [...], procesos: {...} }] }
     * 
     * CRÍTICO: Busca en MÚLTIPLES UBICACIONES
     * @private
     */
    async extraerFilesDelPedido(pedidoData) {
        console.debug('[extraerFilesDelPedido] INICIO');
        console.debug('[extraerFilesDelPedido] ESTRUCTURA PEDIDO DATA:', {
            prendas_count: pedidoData.prendas?.length,
            prenda_0: pedidoData.prendas?.[0] ? {
                uid: pedidoData.prendas[0].uid,
                procesos_keys: Object.keys(pedidoData.prendas[0].procesos || {}),
                procesos_reflectivo: pedidoData.prendas[0].procesos?.reflectivo ? {
                    tipo: pedidoData.prendas[0].procesos.reflectivo.tipo,
                    uid: pedidoData.prendas[0].procesos.reflectivo.uid,
                    tiene_datos_key: !!pedidoData.prendas[0].procesos.reflectivo.datos,
                    datos_imagenes: pedidoData.prendas[0].procesos.reflectivo.datos?.imagenes,
                    imagenes_directo: pedidoData.prendas[0].procesos.reflectivo.imagenes
                } : 'NO EXISTE'
            } : 'NO EXISTE'
        });
        
        const estructura = { 
            prendas: [], 
            epps: [],
            // Mapeo de formdata_key a File object (para que buildFormData pueda acceder luego)
            archivosMap: {}
        };

        // Array para recolectar todas las promesas de conversión
        const conversionPromises = [];

        // NUEVA ESTRUCTURA: prendas y epps separados
        if (Array.isArray(pedidoData.prendas)) {
            pedidoData.prendas.forEach((item, prendaIdx) => {
                const prendaData = {
                    idx: prendaIdx,
                    imagenes: [],
                    telas: [],
                    procesos: {}
                };

                // ==========================================
                // 1. IMÁGENES DE PRENDA
                // ==========================================
                if (Array.isArray(item.imagenes)) {
                    console.log(`[extraerFiles] 📸 Procesando ${item.imagenes.length} imágenes de prenda:`, item.imagenes.map(img => ({
                        tiene_ruta: !!img.ruta,
                        ruta: img.ruta,
                        uid: img.uid,
                        nombre_archivo: img.nombre_archivo,
                        formdata_key: img.formdata_key,
                        previewUrl: img.previewUrl,
                        urlDesdeDB: img.urlDesdeDB
                    })));
                    
                    item.imagenes.forEach((img, imgIdx) => {
                        if (img instanceof File) {
                            // Generar formdata_key y guardarlo para referencia
                            const formdataKey = `prendas[${prendaIdx}][imagenes][${imgIdx}]`;
                            prendaData.imagenes.push({
                                file: img,
                                formdata_key: formdataKey,
                                uid: item.uid || null  // ← AGREGADO: Capturar UID de la prenda
                            });
                            estructura.archivosMap[formdataKey] = img;
                            console.debug(`[extraerFiles] Prenda[${prendaIdx}].imagenes[${imgIdx}] = ${img.name} (key: ${formdataKey}, uid: ${item.uid || 'N/A'})`);
                        } else if (img.ruta && typeof img.ruta === 'string') {
                            // 🔄 CONVERTIR IMAGEN DE COTIZACIÓN A FILE
                            console.log(`[extraerFiles] 🔄 Convirtiendo imagen de prenda con ruta:`, img.ruta);
                            const promise = this.convertirImagenDeCotizacionAFile(img, `prendas[${prendaIdx}][imagenes][${imgIdx}]`, prendaData.imagenes, estructura.archivosMap, item.uid);
                            conversionPromises.push(promise);
                        } else if (img.previewUrl && typeof img.previewUrl === 'string') {
                            // 🔄 CONVERTIR IMAGEN DE COTIZACIÓN A FILE (usando previewUrl)
                            console.log(`[extraerFiles] 🔄 Convirtiendo imagen de prenda con previewUrl:`, img.previewUrl);
                            const imgConRuta = { ...img, ruta: img.previewUrl };
                            const promise = this.convertirImagenDeCotizacionAFile(imgConRuta, `prendas[${prendaIdx}][imagenes][${imgIdx}]`, prendaData.imagenes, estructura.archivosMap, item.uid);
                            conversionPromises.push(promise);
                        } else {
                            console.warn(`[extraerFiles] ⚠️ Imagen de prenda[${imgIdx}] no se procesará:`, {
                                tipo: typeof img,
                                es_file: img instanceof File,
                                tiene_ruta: !!img.ruta,
                                ruta_tipo: typeof img.ruta,
                                datos_completos: img
                            });
                        }
                    });
                } else {
                    console.warn(`[extraerFiles] ⚠️ No hay imágenes de prenda o no es array:`, {
                        imagenes: item.imagenes,
                        tipo: typeof item.imagenes,
                        es_array: Array.isArray(item.imagenes)
                    });
                }

                // ==========================================
                // 2. IMÁGENES DE TELAS
                // ==========================================
                if (Array.isArray(item.telas)) {
                    item.telas.forEach((tela, telaIdx) => {
                        if (!prendaData.telas[telaIdx]) {
                            prendaData.telas[telaIdx] = [];
                        }

                        if (Array.isArray(tela.imagenes)) {
                            tela.imagenes.forEach((img, imgIdx) => {
                                if (img instanceof File) {
                                    const formdataKey = `prendas[${prendaIdx}][telas][${telaIdx}][imagenes][${imgIdx}]`;
                                    prendaData.telas[telaIdx].push({
                                        file: img,
                                        formdata_key: formdataKey,
                                        uid: tela.uid || null  // ← AGREGADO: Capturar UID de la tela
                                    });
                                    estructura.archivosMap[formdataKey] = img;
                                    console.debug(`[extraerFiles] Prenda[${prendaIdx}].telas[${telaIdx}].imagenes[${imgIdx}] = ${img.name} (key: ${formdataKey}, uid: ${tela.uid || 'N/A'})`);
                                } else if (img.ruta && typeof img.ruta === 'string') {
                                    // 🔄 CONVERTIR IMAGEN DE COTIZACIÓN A FILE
                                    const promise = this.convertirImagenDeCotizacionAFile(img, `prendas[${prendaIdx}][telas][${telaIdx}][imagenes][${imgIdx}]`, prendaData.telas[telaIdx], estructura.archivosMap, tela.uid);
                                    conversionPromises.push(promise);
                                }
                            });
                        }
                    });
                }

                // ==========================================
                // 3. IMÁGENES DE PROCESOS
                // ==========================================
                if (item.procesos && typeof item.procesos === 'object' && !Array.isArray(item.procesos)) {
                    Object.entries(item.procesos).forEach(([procesoKey, proceso]) => {
                        prendaData.procesos[procesoKey] = [];

                        if (!proceso || typeof proceso !== 'object') {
                            return;
                        }

                        let imagenes = [];
                        if (proceso.datos && Array.isArray(proceso.datos.imagenes)) {
                            imagenes = proceso.datos.imagenes;
                        } else if (Array.isArray(proceso.imagenes)) {
                            imagenes = proceso.imagenes;
                        }

                        imagenes.forEach((img, imgIdx) => {
                            if (img instanceof File) {
                                const formdataKey = `prendas[${prendaIdx}][procesos][${procesoKey}][${imgIdx}]`;
                                prendaData.procesos[procesoKey].push({
                                    file: img,
                                    formdata_key: formdataKey,
                                    uid: img.uid || null  // ← AGREGADO: Capturar UID de la imagen del proceso si existe
                                });
                                estructura.archivosMap[formdataKey] = img;
                                console.debug(`[extraerFiles] Prenda[${prendaIdx}].procesos[${procesoKey}][${imgIdx}] = ${img.name} (key: ${formdataKey}, uid: ${img.uid || 'N/A'})`);
                            }
                        });
                    });
                }

                estructura.prendas.push(prendaData);
            });
        }

        // EXTRAER ARCHIVOS DE EPPs
        if (Array.isArray(pedidoData.epps)) {
            pedidoData.epps.forEach((epp, eppIdx) => {
                const eppData = {
                    idx: eppIdx,
                    imagenes: []
                };

                // Extraer imágenes de EPP
                if (Array.isArray(epp.imagenes)) {
                    epp.imagenes.forEach((img, imgIdx) => {
                        if (img instanceof File) {
                            const formdataKey = `epps[${eppIdx}][imagenes][${imgIdx}]`;
                            eppData.imagenes.push({
                                file: img,
                                formdata_key: formdataKey,
                                uid: epp.uid || null
                            });
                            estructura.archivosMap[formdataKey] = img;
                            console.debug(`[extraerFiles] EPP[${eppIdx}].imagenes[${imgIdx}] = ${img.name} (key: ${formdataKey}, uid: ${epp.uid || 'N/A'})`);
                        }
                    });
                }

                estructura.epps.push(eppData);
            });
        }

        // BACKWARDS COMPATIBILITY: estructura antigua con items[]
        if (Array.isArray(pedidoData.items) && estructura.prendas.length === 0) {
            pedidoData.items.forEach((item, prendaIdx) => {
                const prendaData = {
                    idx: prendaIdx,
                    imagenes: [],
                    telas: [],
                    procesos: {}
                };

                if (Array.isArray(item.imagenes)) {
                    item.imagenes.forEach((img, imgIdx) => {
                        if (img instanceof File) {
                            const formdataKey = `prendas[${prendaIdx}][imagenes][${imgIdx}]`;
                            prendaData.imagenes.push({ file: img, formdata_key: formdataKey });
                            estructura.archivosMap[formdataKey] = img;
                        }
                    });
                }

                if (Array.isArray(item.telas)) {
                    item.telas.forEach((tela, telaIdx) => {
                        if (!prendaData.telas[telaIdx]) {
                            prendaData.telas[telaIdx] = [];
                        }

                        if (Array.isArray(tela.imagenes)) {
                            tela.imagenes.forEach((img, imgIdx) => {
                                if (img instanceof File) {
                                    const formdataKey = `prendas[${prendaIdx}][telas][${telaIdx}][imagenes][${imgIdx}]`;
                                    prendaData.telas[telaIdx].push({ file: img, formdata_key: formdataKey });
                                    estructura.archivosMap[formdataKey] = img;
                                }
                            });
                        }
                    });
                }

                if (item.procesos && typeof item.procesos === 'object' && !Array.isArray(item.procesos)) {
                    Object.entries(item.procesos).forEach(([procesoKey, proceso]) => {
                        prendaData.procesos[procesoKey] = [];

                        if (!proceso || typeof proceso !== 'object') {
                            return;
                        }

                        let imagenes = [];
                        if (proceso.datos && Array.isArray(proceso.datos.imagenes)) {
                            imagenes = proceso.datos.imagenes;
                        } else if (Array.isArray(proceso.imagenes)) {
                            imagenes = proceso.imagenes;
                        }

                        imagenes.forEach((img, imgIdx) => {
                            if (img instanceof File) {
                                const formdataKey = `prendas[${prendaIdx}][procesos][${procesoKey}][${imgIdx}]`;
                                prendaData.procesos[procesoKey].push({ file: img, formdata_key: formdataKey });
                                estructura.archivosMap[formdataKey] = img;
                            }
                        });
                    });
                }

                estructura.prendas.push(prendaData);
            });
        }

        // 🔄 ESPERAR A QUE TODAS LAS CONVERSIONES DE IMÁGENES TERMINEN
        if (conversionPromises.length > 0) {
            console.log(`[extraerFilesDelPedido] 🔄 Esperando ${conversionPromises.length} conversiones de imágenes...`);
            await Promise.all(conversionPromises);
            console.log(`[extraerFilesDelPedido] ✅ Todas las conversiones completadas`);
        }

        // Contar archivos extraídos
        let totalArchivos = 0;
        estructura.prendas.forEach(prenda => {
            totalArchivos += prenda.imagenes.length;
            prenda.telas.forEach(telaImgs => {
                if (Array.isArray(telaImgs)) {
                    totalArchivos += telaImgs.length;
                }
            });
            Object.values(prenda.procesos).forEach(procesoImgs => {
                if (Array.isArray(procesoImgs)) {
                    totalArchivos += procesoImgs.length;
                }
            });
        });
        
        estructura.epps.forEach(epp => {
            totalArchivos += epp.imagenes.length;
        });

        console.log('[extraerFilesDelPedido] ✅ EXTRACCIÓN COMPLETADA:', {
            prendas: estructura.prendas.length,
            epps: estructura.epps.length,
            archivos_totales: totalArchivos,
            archivos_en_map: Object.keys(estructura.archivosMap).length,
            estructura: estructura.prendas.map(p => ({
                imagenes_prenda: p.imagenes.length,
                imagenes_telas: p.telas.reduce((sum, t) => sum + (Array.isArray(t) ? t.length : 0), 0),
                procesos: Object.keys(p.procesos).map(k => ({
                    tipo: k,
                    imagenes: p.procesos[k].length
                }))
            }))
        });

        return estructura;
    }

    /**
     * Agregar Files extraídos a FormData con estructura anidada
     * items[0][imagenes][0], items[0][telas][0][imagenes][0], etc.
     * 
     * @param {FormData} formData
     * @param {Object} filesExtraidos - Estructura retornada por extraerFilesDelPedido
     * @private
     */
    agregarFilesAFormData(formData, filesExtraidos) {
        filesExtraidos.prendas.forEach(prenda => {
            const itemIdx = prenda.idx;
            
            // Agregar imágenes de prenda
            prenda.imagenes.forEach((file, fileIdx) => {
                const key = `items[${itemIdx}][imagenes][${fileIdx}]`;
                formData.append(key, file);
                console.log(`[item-api-service] Agregado a FormData: ${key}`);
            });
            
            // Agregar imágenes de telas
            prenda.telas.forEach((telaFiles, telaIdx) => {
                if (Array.isArray(telaFiles)) {
                    telaFiles.forEach((file, fileIdx) => {
                        const key = `items[${itemIdx}][telas][${telaIdx}][imagenes][${fileIdx}]`;
                        formData.append(key, file);
                        console.log(`[item-api-service] Agregado a FormData: ${key}`);
                    });
                }
            });
            
            // Agregar imágenes de procesos en ruta separada (NO dentro de procesos JSON)
            // Las imágenes NO van en items[i][procesos][...] porque procesos es JSON string
            Object.entries(prenda.procesos).forEach(([procesoKey, procesoFiles]) => {
                if (Array.isArray(procesoFiles)) {
                    procesoFiles.forEach((file, fileIdx) => {
                        // Usar ruta separada para evitar conflicto con procesos JSON string
                        const key = `items[${itemIdx}][procesos_files][${procesoKey}][${fileIdx}]`;
                        formData.append(key, file);
                        console.log(`[item-api-service] Agregado a FormData: ${key}`);
                    });
                }
            });
        });
    }

    /**
     * Contar total de Files en estructura extraída
     * @private
     */
    contarFiles(estructura) {
        let total = 0;
        estructura.prendas.forEach(prenda => {
            total += prenda.imagenes.length;
            prenda.telas.forEach(telaFiles => {
                if (Array.isArray(telaFiles)) {
                    total += telaFiles.length;
                }
            });
            Object.values(prenda.procesos).forEach(procesoFiles => {
                if (Array.isArray(procesoFiles)) {
                    total += procesoFiles.length;
                }
            });
        });
        return total;
    }

    /**
     * Mostrar estructura de Files para debugging
     * @private
     */
    mostrarEstructuraFiles(estructura) {
        const resumen = estructura.prendas.map((prenda, idx) => ({
            prenda_idx: idx,
            imagenes: prenda.imagenes.length,
            telas: prenda.telas.filter(t => Array.isArray(t)).length,
            procesos: Object.keys(prenda.procesos).length
        }));
        return resumen;
    }

    /**
     * Contar archivos en FormData (para debugging)
     * @private
     */
    contarArchivosEnFormData(formData) {
        let count = 0;
        for (let [key, value] of formData.entries()) {
            if (value instanceof File) {
                count++;
            }
        }
        return count;
    }

    /**
     * Actualizar un pedido existente
     */
    async actualizarPedido(pedidoId, pedidoData) {
        try {
            return await this.realizarPeticion(`${this.baseUrl}/${pedidoId}`, {
                method: 'PUT',
                body: JSON.stringify(pedidoData)
            });
        } catch (error) {

            throw error;
        }
    }

    /**
     * Convertir imagen de cotización (con ruta) a objeto File
     * @param {Object} img - Objeto de imagen con ruta y uid
     * @param {string} formdataKey - Key para FormData
     * @param {Array} targetArray - Array donde agregar el archivo
     * @param {Object} archivosMap - Map de archivos
     * @param {string} parentUid - UID del padre (prenda/tela)
     * @returns {Promise<void>}
     */
    async convertirImagenDeCotizacionAFile(img, formdataKey, targetArray, archivosMap, parentUid) {
        try {
            console.log(`[convertirImagenDeCotizacionAFile] 🔄 Convirtiendo imagen:`, {
                ruta: img.ruta,
                uid: img.uid,
                formdataKey,
                parentUid
            });

            // Fetch de la imagen desde cotizaciones
            const response = await fetch(img.ruta);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const blob = await response.blob();
            const fileName = img.ruta.split('/').pop() || 'imagen_cotizacion.png';
            const file = new File([blob], fileName, { type: blob.type });

            // Agregar a las estructuras
            targetArray.push({
                file: file,
                formdata_key: formdataKey,
                uid: parentUid || null
            });
            archivosMap[formdataKey] = file;

            console.log(`[convertirImagenDeCotizacionAFile] ✅ Imagen convertida:`, {
                fileName,
                fileSize: file.size,
                fileType: file.type,
                formdataKey
            });

        } catch (error) {
            console.error(`[convertirImagenDeCotizacionAFile] ❌ Error convirtiendo imagen:`, {
                ruta: img.ruta,
                error: error.message
            });
            // No lanzar el error, solo loguearlo para que continúe el proceso
        }
    }
}

window.ItemAPIService = ItemAPIService;
