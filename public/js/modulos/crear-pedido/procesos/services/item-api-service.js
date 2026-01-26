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
        const configuracion = {
            headers: {
                'Content-Type': 'application/json',
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
            
            // ✅ PASO 1: Normalizar (sin mutación, elimina Files)
            const pedidoNormalizado = window.PayloadNormalizer.normalizePedido(pedidoData);
            console.debug('[validarPedido] ✅ Normalización completa');
            
            // ✅ PASO 2: Serializar JSON
            const jsonString = JSON.stringify(pedidoNormalizado);
            console.debug(`[validarPedido] ✅ JSON serializado: ${jsonString.length} bytes`);
            
            // ✅ PASO 3: Enviar
            console.debug('[validarPedido] 📤 Enviando a /validar...');
            const respuesta = await this.realizarPeticion(`${this.baseUrl}/validar`, {
                method: 'POST',
                body: jsonString
            });
            
            console.debug('[validarPedido] ✅ Respuesta:', respuesta);
            return respuesta;
            
        } catch (error) {
            console.error('[validarPedido] ❌ Error:', error);
            throw error;
        }
    }

    /**
     * Crear un nuevo pedido - FLUJO COMPLETO CON NORMALIZACIÓN
     */
    async crearPedido(pedidoData) {
        try {
            console.debug('[crearPedido] 📦 INICIO');

            // ✅ PASO 1: Extraer TODOS los Files PRIMERO (antes de cualquier normalización)
            console.debug('[crearPedido] PASO 1: Extrayendo files...');
            const filesExtraidos = this.extraerFilesDelPedido(pedidoData);
            console.debug('[crearPedido] ✅ PASO 1 completo:', {
                prendas: filesExtraidos.prendas.length,
                archivos_totales: filesExtraidos.prendas.reduce((sum, p) => 
                    sum + p.imagenes.length + 
                    p.telas.reduce((t, ta) => t + ta.length, 0) + 
                    Object.values(p.procesos).reduce((s, proc) => s + proc.length, 0), 0)
            });

            // ✅ PASO 2: Normalizar el pedido (elimina Files del JSON, evita ciclos)
            console.debug('[crearPedido] PASO 2: Normalizando...');
            const pedidoNormalizado = window.PayloadNormalizer.normalizePedido(pedidoData);
            console.debug('[crearPedido] ✅ PASO 2 completo - Items:', pedidoNormalizado.items.length);

            // ✅ PASO 3: Construir FormData con JSON limpio + archivos
            console.debug('[crearPedido] PASO 3: Construyendo FormData...');
            const formData = window.PayloadNormalizer.buildFormData(pedidoNormalizado, filesExtraidos);
            console.debug('[crearPedido] ✅ PASO 3 completo');

            // ✅ PASO 4: Enviar
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
                console.error('[crearPedido] ❌ Error del servidor:', {
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
            console.debug('[crearPedido] ✅ ÉXITO:', {
                pedido_id: resultado.pedido_id,
                numero_pedido: resultado.numero_pedido
            });

            return resultado;

        } catch (error) {
            console.error('[crearPedido] ❌ Error final:', error);
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
                console.log(`[item-api-service] ✅ Archivo: ${key} (${file.name})`);
            });
            
            // Imágenes de telas: prendas[0][telas][0][imagenes][0]
            prenda.telas.forEach((telaFiles, telaIdx) => {
                if (Array.isArray(telaFiles)) {
                    telaFiles.forEach((file, fileIdx) => {
                        const key = `prendas[${itemIdx}][telas][${telaIdx}][imagenes][${fileIdx}]`;
                        formData.append(key, file);
                        console.log(`[item-api-service] ✅ Archivo: ${key} (${file.name})`);
                    });
                }
            });
            
            // Imágenes de procesos: prendas[0][procesos][0][imagenes][0]
            Object.entries(prenda.procesos).forEach(([procesoKey, procesoFiles]) => {
                if (Array.isArray(procesoFiles)) {
                    procesoFiles.forEach((file, fileIdx) => {
                        const key = `prendas[${itemIdx}][procesos][${procesoKey}][imagenes][${fileIdx}]`;
                        formData.append(key, file);
                        console.log(`[item-api-service] ✅ Archivo: ${key} (${file.name})`);
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
    extraerFilesDelPedido(pedidoData) {
        console.debug('[extraerFilesDelPedido] INICIO');
        const estructura = { prendas: [] };

        if (!Array.isArray(pedidoData.items)) {
            return estructura;
        }

        pedidoData.items.forEach((item, prendaIdx) => {
            const prendaData = {
                idx: prendaIdx,
                imagenes: [],
                telas: [],
                procesos: {}
            };

            // ==========================================
            // 1. IMÁGENES DE PRENDA
            // ==========================================
            console.debug(`[extraerFiles] Prenda ${prendaIdx}: Buscando imagenes...`);
            if (Array.isArray(item.imagenes)) {
                item.imagenes.forEach((img) => {
                    if (img instanceof File) {
                        prendaData.imagenes.push(img);
                        console.debug(`[extraerFiles] ✅ Prenda[${prendaIdx}].imagenes = ${img.name} (${(img.size / 1024).toFixed(2)} KB)`);
                    }
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

                    // Buscar imagenes en tela.imagenes
                    if (Array.isArray(tela.imagenes)) {
                        tela.imagenes.forEach((img) => {
                            if (img instanceof File) {
                                prendaData.telas[telaIdx].push(img);
                                console.debug(`[extraerFiles] ✅ Prenda[${prendaIdx}].telas[${telaIdx}].imagenes = ${img.name}`);
                            }
                        });
                    }
                });
            }

            // ==========================================
            // 3. IMÁGENES DE PROCESOS - MÚLTIPLES UBICACIONES
            // ==========================================
            if (item.procesos && typeof item.procesos === 'object' && !Array.isArray(item.procesos)) {
                Object.entries(item.procesos).forEach(([procesoKey, proceso]) => {
                    prendaData.procesos[procesoKey] = [];

                    if (!proceso || typeof proceso !== 'object') {
                        return;
                    }

                    // Búsqueda de imagenes en proceso (prioridad: datos.imagenes > imagenes)
                    let imagenes = [];

                    // UBICACIÓN 1: proceso.datos.imagenes (prioridad)
                    if (proceso.datos && Array.isArray(proceso.datos.imagenes)) {
                        console.debug(`[extraerFiles] 🔍 Encontrado: proceso.datos.imagenes`);
                        imagenes = proceso.datos.imagenes;
                    }
                    // UBICACIÓN 2: proceso.imagenes (fallback)
                    else if (Array.isArray(proceso.imagenes)) {
                        console.debug(`[extraerFiles] 🔍 Encontrado: proceso.imagenes`);
                        imagenes = proceso.imagenes;
                    }

                    // Agregar solo Files a la estructura
                    imagenes.forEach((img) => {
                        if (img instanceof File) {
                            prendaData.procesos[procesoKey].push(img);
                            console.debug(`[extraerFiles] ✅ Prenda[${prendaIdx}].procesos[${procesoKey}] = ${img.name}`);
                        }
                    });

                    if (prendaData.procesos[procesoKey].length === 0) {
                        console.debug(`[extraerFiles] ℹ️  Prenda[${prendaIdx}].procesos[${procesoKey}] - Sin imagenes`);
                    }
                });
            }

            estructura.prendas.push(prendaData);
        });

        console.debug('[extraerFiles] RESUMEN:', {
            prendas: estructura.prendas.length,
            imagenes_prenda: estructura.prendas.reduce((sum, p) => sum + p.imagenes.length, 0),
            imagenes_telas: estructura.prendas.reduce((sum, p) => sum + p.telas.reduce((ts, t) => ts + t.length, 0), 0),
            imagenes_procesos: estructura.prendas.reduce((sum, p) => sum + Object.values(p.procesos).reduce((ps, proc) => ps + proc.length, 0), 0)
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
                console.log(`[item-api-service] ✅ Agregado a FormData: ${key}`);
            });
            
            // Agregar imágenes de telas
            prenda.telas.forEach((telaFiles, telaIdx) => {
                if (Array.isArray(telaFiles)) {
                    telaFiles.forEach((file, fileIdx) => {
                        const key = `items[${itemIdx}][telas][${telaIdx}][imagenes][${fileIdx}]`;
                        formData.append(key, file);
                        console.log(`[item-api-service] ✅ Agregado a FormData: ${key}`);
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
                        console.log(`[item-api-service] ✅ Agregado a FormData: ${key}`);
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
}

window.ItemAPIService = ItemAPIService;
