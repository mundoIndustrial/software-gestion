/**
 * PrendaFormCollector
 * Componente responsable de recolectar y construir datos de prenda desde el formulario modal
 * Encapsula la lógica de extracción de: nombre, descripción, origen, imágenes, telas, tallas, variaciones, procesos
 * 
 * Separación de responsabilidades:
 * - gestion-items-pedido.js: Orquestación del flujo
 * - PrendaFormCollector: Extracción de datos del formulario (THIS FILE)
 * - ModalNovedadPrenda: Modales y feedback del usuario
 */

class PrendaFormCollector {
    constructor() {
        this.notificationService = null;
    }

    /**
     * Asignar el servicio de notificaciones
     */
    setNotificationService(service) {
        this.notificationService = service;
    }

    /**
     * Construir objeto de prenda desde el formulario modal
     * Recolecta: nombre, descripción, origen, imágenes, telas, tallas, variaciones, procesos
     * 
     * @param {number|null} prendaEditIndex - Índice si estamos en modo edición (para recuperar telas anteriores)
     * @param {Array} prendasArray - Array de prendas existentes (para modo edición)
     * @returns {Object|null} Objeto con datos de prenda o null si hay error
     */
    construirPrendaDesdeFormulario(prendaEditIndex = null, prendasArray = []) {
        try {
            // ============================================
            // 1. OBTENER DATOS BÁSICOS
            // ============================================
            const nombre = document.getElementById('nueva-prenda-nombre')?.value?.trim();
            const descripcion = document.getElementById('nueva-prenda-descripcion')?.value?.trim();
            const origenSelect = document.getElementById('nueva-prenda-origen-select')?.value || 'bodega';
            
            // Convertir origen a de_bodega (boolean)
            // 'bodega' -> true (de_bodega=1), 'confeccion' -> false (de_bodega=0)
            const de_bodega = origenSelect?.toLowerCase() === 'bodega' ? 1 : 0;

            // Validar campos requeridos
            if (!nombre) {
                this.notificationService?.error('El nombre de la prenda es requerido');
                return null;
            }

            // ============================================
            // 2. PROCESAR IMÁGENES DE PRENDA
            // ============================================
            const imagenesTemporales = window.imagenesPrendaStorage?.obtenerImagenes?.() || [];
            
            console.log('[prenda-form-collector] 🖼️ PROCESANDO IMÁGENES DE PRENDA:', {
                imagenesTemporales_length: imagenesTemporales.length,
                imagenesTemporales_type: Array.isArray(imagenesTemporales) ? 'array' : typeof imagenesTemporales,
                primeraprimeraDiagnostico: imagenesTemporales[0] ? {
                    tipo: typeof imagenesTemporales[0],
                    esFile: imagenesTemporales[0] instanceof File,
                    constructor: imagenesTemporales[0]?.constructor?.name,
                    propiedades: Object.keys(imagenesTemporales[0] || {})
                } : null
            });
            
            // Procesar imágenes: nuevas File objects + rutas de BD (NUNCA blob URLs que se revocaran)
            const imagenesCopia = imagenesTemporales.map((img, imgIdx) => {
                console.log(`[prenda-form-collector] 🔍 PROCESANDO IMAGEN ${imgIdx}:`);
                console.log(`[prenda-form-collector]   🔴 CONTENIDO COMPLETO DEL OBJETO:`, JSON.stringify({
                    previewUrl: img?.previewUrl?.substring ? img.previewUrl.substring(0, 80) : img?.previewUrl,
                    url: img?.url?.substring ? img.url.substring(0, 80) : img?.url,
                    nombre: img?.nombre,
                    tamaño: img?.tamaño,
                    id: img?.id,
                    prenda_foto_id: img?.prenda_foto_id,
                    ruta_original: img?.ruta_original?.substring ? img.ruta_original.substring(0, 80) : img?.ruta_original,
                    ruta_webp: img?.ruta_webp?.substring ? img.ruta_webp.substring(0, 80) : img?.ruta_webp,
                    hasFile: !!img?.file,
                    fileType: img?.file?.type,
                    fileSize: img?.file?.size
                }, null, 2));

                // 1️⃣ Si img es directamente un File object, usarlo (imagen nueva)
                if (img instanceof File) {
                    console.log(`[prenda-form-collector]    DECISIÓN: Es File object directo, RETORNANDO`);
                    return img;
                }
                
                // 2️⃣ Si img tiene propiedad file que es File object, usar eso (imagen cargada nuevamente)
                if (img && img.file instanceof File) {
                    console.log(`[prenda-form-collector]    DECISIÓN: Tiene .file que es File object, RETORNANDO`);
                    // 🔴 CRÍTICO: Guardar el File object CON el metadata, no solo el File
                    // Esto asegura que cuando se recupere la imagen, tenga toda la info
                    return {
                        file: img.file,                    // ← El File object real
                        previewUrl: img.previewUrl,        // ← El blob URL para preview
                        nombre: img.nombre,
                        tamaño: img.tamaño,
                        fileType: img.file.type,
                        fileSize: img.file.size
                    };
                }
                
                // 3️⃣ CRÍTICO: Si es un objeto con información de BD, extraer RUTA DE ALMACENAMIENTO
                // NUNCA guardar blob URLs porque se revocaran en limpieza asíncrona
                if (img && typeof img === 'object' && img.previewUrl) {
                    console.log(`[prenda-form-collector]   Es objeto con previewUrl:`, img.previewUrl.substring(0, 50));
                    
                    // Prioridad: ruta de almacenamiento permanente
                    if (img.ruta_original && img.ruta_original.startsWith('/')) {
                        console.log(`[prenda-form-collector]    DECISIÓN: Tiene ruta_original, RETORNANDO`);
                        return {
                            id: img.id,
                            prenda_foto_id: img.prenda_foto_id,
                            ruta: img.ruta_original,
                            ruta_original: img.ruta_original,
                            ruta_webp: img.ruta_webp,
                            nombre: img.nombre,
                            urlDesdeDB: true
                        };
                    }
                    if (img.ruta_webp && img.ruta_webp.startsWith('/')) {
                        console.log(`[prenda-form-collector]    DECISIÓN: Tiene ruta_webp, RETORNANDO`);
                        return {
                            id: img.id,
                            prenda_foto_id: img.prenda_foto_id,
                            ruta: img.ruta_webp,
                            ruta_original: img.ruta_original,
                            ruta_webp: img.ruta_webp,
                            nombre: img.nombre,
                            urlDesdeDB: true
                        };
                    }
                    if (img.url && img.url.startsWith('/')) {
                        console.log(`[prenda-form-collector]    DECISIÓN: Tiene url, RETORNANDO`);
                        return {
                            id: img.id,
                            prenda_foto_id: img.prenda_foto_id,
                            ruta: img.url,
                            ruta_original: img.ruta_original,
                            ruta_webp: img.ruta_webp,
                            nombre: img.nombre,
                            urlDesdeDB: true
                        };
                    }
                    // Fallback: Si solo tiene blob URL y datos de BD, preservar ID para merge
                    console.log(`[prenda-form-classifier]   ⚠️ DECISIÓN: Preservando con blob URL fallback`);
                    return {
                        id: img.id,
                        prenda_foto_id: img.prenda_foto_id,
                        enBD: true,
                        urlFallback: img.previewUrl,
                        ruta_original: img.ruta_original,
                        ruta_webp: img.ruta_webp,
                        nombre: img.nombre,
                        urlDesdeDB: true
                    };
                }
                
                // 4️⃣ FALLBACK DEFENSIVO: Si es un objeto que llegó del storage pero no tiene previewUrl,
                // preservarlo de todas formas porque algo debe tener
                if (img && typeof img === 'object') {
                    console.log(`[prenda-form-collector]   ⚠️ Objeto sin previewUrl - preservando como está`);
                    // Si tiene ID o alguna referencia a BD, marcalo como tal
                    if (img.id || img.prenda_foto_id) {
                        console.log(`[prenda-form-collector]    DECISIÓN: Preservando con urlDesdeDB=true`);
                        return {
                            ...img,
                            urlDesdeDB: true
                        };
                    }
                    // Si no, preservarlo tal cual
                    console.log(`[prenda-form-collector]    DECISIÓN: Preservando tal cual`);
                    return img;
                }
                
                // Retornar tal cual si es File o válido
                console.log(`[prenda-form-collector]    DECISIÓN: Retornando tal cual`);
                return img;
            }).filter((img, filterIdx) => {
                // 🔴 CRÍTICO: Descartar IMÁGENES VACÍAS (blob URLs revocados del storage)
                // Una imagen válida DEBE tener una de estas:
                // 1. Es un File object
                // 2. Tiene previewUrl NO VACÍO
                // 3. Tiene url NO VACÍO  
                // 4. Tiene id de BD (prenda_foto_id)
                // 5. Tiene ruta de almacenamiento (ruta, urlDesdeDB, enBD)
                // 6. Tiene file que es File object (objeto con metadatos)
                
                const esFile = img instanceof File;
                const tieneFileObj = img?.file instanceof File;
                const tienePreviewUrl = img?.previewUrl && img.previewUrl.trim() !== '';
                const tieneUrl = img?.url && img.url.trim() !== '';
                const tieneIdBD = img?.id || img?.prenda_foto_id;
                const tieneRutaBD = img?.ruta && typeof img.ruta === 'string' && img.ruta.trim() !== '';
                const esDesdeDB = img?.urlDesdeDB === true || img?.enBD === true;
                
                const esValido = img !== null && img !== undefined && (esFile || tieneFileObj || tienePreviewUrl || tieneUrl || tieneIdBD || tieneRutaBD || esDesdeDB);
                
                console.log(`[prenda-form-collector] 🔍 FILTER [${filterIdx}]: esValido=${esValido}`, {
                    esFile,
                    tieneFileObj: !!tieneFileObj,
                    tienePreviewUrl: !!tienePreviewUrl,
                    tieneUrl: !!tieneUrl,
                    tieneIdBD: !!tieneIdBD,
                    tieneRutaBD: !!tieneRutaBD,
                    esDesdeDB: !!esDesdeDB,
                    razon: !esValido ? (img === null || img === undefined ? 'null/undefined' : 'imagen vacía sin contenido') : 'válida'
                });
                
                if (!esValido) {
                    console.log(`[prenda-form-collector]  Imagen ${filterIdx} DESCARTADA - sin contenido válido`);
                }
                
                return esValido;
            });
            
            console.log('[prenda-form-collector] 🖼️ IMÁGENES DE PRENDA DESPUÉS DE PROCESAR:', {
                cantidad: imagenesCopia.length,
                detalles: imagenesCopia.map(img => ({
                    tipo: img instanceof File ? 'File' : typeof img,
                    esFile: img instanceof File,
                    tieneId: !!img.id,
                    tienePreviewUrl: !!img.previewUrl,
                    tieneRuta: !!img.ruta,
                    urlDesdeDB: img.urlDesdeDB,
                    keys: typeof img === 'object' ? Object.keys(img || {}).slice(0, 5) : 'N/A'
                }))
            });

            // ============================================
            // 3. CONSTRUIR OBJETO BASE DE PRENDA
            // ============================================
            //  IMPORTANTE: Hacer DEEP COPY de tallasRelacionales
            // porque window.tallasRelacionales es limpiado después
            // Si asignas la referencia, el objeto se vacía
            const copiarTallasRelacionales = (obj) => {
                const copia = {};
                Object.entries(obj).forEach(([genero, tallasObj]) => {
                    copia[genero] = { ...tallasObj };
                });
                return copia;
            };
            
            //  IMPORTANTE: Hacer DEEP COPY de procesosSeleccionados
            // porque window.procesosSeleccionados puede ser limpiado después
            const copiarProcesos = (procesos) => {
                if (!procesos || typeof procesos !== 'object') {
                    return {};
                }
                const copia = {};
                Object.entries(procesos).forEach(([tipoProceso, proceso]) => {
                    if (proceso && typeof proceso === 'object') {
                        // Deep copy completo de datos (incluye tallas, ubicaciones, observaciones, imagenes, datosExtendidos)
                        let datosCopiados = null;
                        if (proceso.datos && typeof proceso.datos === 'object') {
                            datosCopiados = {
                                ...proceso.datos,
                                ubicaciones: Array.isArray(proceso.datos.ubicaciones) ? [...proceso.datos.ubicaciones] : (proceso.datos.ubicaciones || []),
                                tallas: proceso.datos.tallas ? JSON.parse(JSON.stringify(proceso.datos.tallas)) : {},
                                imagenes: Array.isArray(proceso.datos.imagenes) ? [...proceso.datos.imagenes] : [],
                                // 🔴 CRÍTICO: Preservar Files de fotos generales (modo general)
                                fotosGeneralesFiles: Array.isArray(proceso.datos.fotosGeneralesFiles) ? [...proceso.datos.fotosGeneralesFiles] : [],
                                imagenesFiles: Array.isArray(proceso.datos.imagenesFiles) ? [...proceso.datos.imagenesFiles] : [],
                                // 🔴 CRÍTICO: Preservar datosExtendidos con imagenesFiles (File objects)
                                datosExtendidos: proceso.datos.datosExtendidos ? JSON.parse(JSON.stringify(proceso.datos.datosExtendidos)) : {}
                            };
                            
                            // 🔴 Restaurar imagenesFiles arrays que se pierden en JSON.stringify
                            // JSON.stringify pierde referencias a File objects, pero imagenesFiles debería estar
                            // Copiar arrays de Files directamente (NO mediante JSON)
                            if (proceso.datos.datosExtendidos && typeof proceso.datos.datosExtendidos === 'object') {
                                Object.entries(proceso.datos.datosExtendidos).forEach(([genero, tallasDatos]) => {
                                    if (tallasDatos && typeof tallasDatos === 'object') {
                                        Object.entries(tallasDatos).forEach(([talla, tallaData]) => {
                                            if (tallaData && Array.isArray(tallaData.imagenesFiles)) {
                                                // Copiar array de Files directamente (no pueden serializarse vía JSON)
                                                datosCopiados.datosExtendidos[genero][talla].imagenesFiles = [...tallaData.imagenesFiles];
                                                console.log(`[copiarProcesos]  imagenesFiles preservados para ${genero}__${talla}:`, tallaData.imagenesFiles.length, 'files');
                                            }
                                        });
                                    }
                                });
                            }
                            
                            // Log de fotos generales preservadas
                            if (datosCopiados.fotosGeneralesFiles && datosCopiados.fotosGeneralesFiles.length > 0) {
                                console.log(`[copiarProcesos]  fotosGeneralesFiles preservados para ${tipoProceso}:`, datosCopiados.fotosGeneralesFiles.length, 'files');
                            }
                        }
                        copia[tipoProceso] = {
                            tipo: proceso.tipo || tipoProceso,
                            datos: datosCopiados,
                            modoTallas: datosCopiados?.modoTallas || proceso.modoTallas || 'generico'
                        };
                    }
                });
                return copia;
            };
            
            // IMPORTANTE: Obtener tallas desde window.tallasRelacionales O desde StateManager (si viene del wizard)
            let tallasParaGuardar = window.tallasRelacionales || {};
            
            // Si window.tallasRelacionales está vacío pero hay datos en StateManager, usarlos
            const hasWindowTallas = Object.keys(tallasParaGuardar).some(genero => Object.keys(tallasParaGuardar[genero] || {}).length > 0);
            
            if (!hasWindowTallas && window.StateManager && window.StateManager.getAsignaciones) {
                console.log('[prenda-form-collector]  window.tallasRelacionales está vacío, recuperando de StateManager...');
                
                const asignaciones = window.StateManager.getAsignaciones();
                tallasParaGuardar = {};
                
                // Convertir asignaciones a formato cantidad_talla
                Object.values(asignaciones).forEach(asignacion => {
                    const genero = asignacion.genero || 'UNISEX';
                    if (!tallasParaGuardar[genero]) {
                        tallasParaGuardar[genero] = {};
                    }
                    
                    // Sumar cantidades de colores para esta talla
                    const totalCantidad = (asignacion.colores || []).reduce((sum, color) => sum + (color.cantidad || 0), 0);
                    
                    if (totalCantidad > 0) {
                        tallasParaGuardar[genero][asignacion.talla] = totalCantidad;
                    }
                });
                
                console.log('[prenda-form-collector]  Tallas recuperadas de StateManager:', tallasParaGuardar);
            }
            
            // 🟢 NUEVO: Si hay "SOLO CANTIDAD", agregarlo al objeto de tallas con género especial
            if (window.cantidadSoloSeleccionada && window.cantidadSoloSeleccionada > 0) {
                console.log('[prenda-form-collector]  "SOLO CANTIDAD" detectado:', window.cantidadSoloSeleccionada);
                
                // Inicializar el género especial si no existe
                if (!tallasParaGuardar['GENERICO']) {
                    tallasParaGuardar['GENERICO'] = {};
                }
                
                // Agregar la cantidad con talla especial "SIN_ESPECIFICAR"
                tallasParaGuardar['GENERICO']['SIN_ESPECIFICAR'] = window.cantidadSoloSeleccionada;
                
                console.log('[prenda-form-collector] Tallas actualizadas con SOLO CANTIDAD:', tallasParaGuardar);
            }

            const prendaData = {
                tipo: 'prenda_nueva',
                nombre_prenda: nombre,
                descripcion: descripcion || '',
                origen: origenSelect,  // Add the origen field
                de_bodega: de_bodega,  // 1 para bodega, 0 para confección
                // Imágenes de prenda copiadas del storage
                imagenes: imagenesCopia,
                telasAgregadas: [],
                //  COPIA PROFUNDA para evitar que se vacíe cuando se limpie el modal
                procesos: copiarProcesos(window.procesosSeleccionados),
                // Estructura relacional: { DAMA: {S: 5}, CABALLERO: {M: 3} }
                //  COPIA PROFUNDA para evitar que se vacíe cuando se limpie el modal
                cantidad_talla: copiarTallasRelacionales(tallasParaGuardar || { DAMA: {}, CABALLERO: {}, UNISEX: {} }),
                variantes: {}
            };
            
            // 🔴 LOG CRÍTICO INMEDIATO: Verificar que prendaData.imagenes se asignó correctamente
            console.log('[prenda-form-collector] 🔴 CRÍTICO - prendaData.imagenes asignado JUSTO DESPUÉS DE CREAR prendaData:', {
                imagenesCopia_length: imagenesCopia.length,
                prendaData_imagenes_length: prendaData.imagenes?.length || 0,
                sonLaMismaReferencia: prendaData.imagenes === imagenesCopia,
                contenido_imagenesCopia: imagenesCopia.map(img => ({
                    tipo: img instanceof File ? 'File' : typeof img,
                    id: img?.id,
                    previewUrl: img?.previewUrl?.substring(0, 50)
                })),
                contenido_prendaData_imagenes: prendaData.imagenes?.map(img => ({
                    tipo: img instanceof File ? 'File' : typeof img,
                    id: img?.id,
                    previewUrl: img?.previewUrl?.substring(0, 50)
                }))
            });

            // DEBUG: Log para ver qué se capturó
            console.log('[prenda-form-collector]  Datos capturados en prendaData:');
            console.log('[prenda-form-collector]   - nombre_prenda:', prendaData.nombre_prenda);
            console.log('[prenda-form-collector]   - origen:', prendaData.origen);
            console.log('[prenda-form-collector]   - de_bodega:', prendaData.de_bodega);
            console.log('[prenda-form-collector]   - cantidad_talla:', prendaData.cantidad_talla);
            console.log('[prenda-form-collector]   - procesos:', prendaData.procesos);
            console.log('[prenda-form-collector]   - DESGLOSE cantidad_talla:');
            console.log('[prenda-form-collector]     * DAMA:', prendaData.cantidad_talla.DAMA);
            console.log('[prenda-form-collector]     * CABALLERO:', prendaData.cantidad_talla.CABALLERO);
            console.log('[prenda-form-collector]     * UNISEX:', prendaData.cantidad_talla.UNISEX);
            console.log('[prenda-form-collector]   - window.tallasRelacionales:', window.tallasRelacionales);
            console.log('[prenda-form-collector]   - ¿Son el MISMO objeto (tallas)?', prendaData.cantidad_talla === window.tallasRelacionales);
            console.log('[prenda-form-collector]   - ¿Son el MISMO objeto (procesos)?', prendaData.procesos === window.procesosSeleccionados);

            // ============================================
            // 4. PROCESAR TELAS AGREGADAS (FLUJO CREACIÓN)
            // ============================================
            console.log('[prenda-form-collector]  INICIANDO PROCESAMIENTO DE TELAS:', {
                window_telasCreacion_exists: !!window.telasCreacion,
                window_telasCreacion_isArray: Array.isArray(window.telasCreacion),
                window_telasCreacion_length: window.telasCreacion?.length || 0
            });

            if (window.telasCreacion && Array.isArray(window.telasCreacion) && window.telasCreacion.length > 0) {
                console.log('[prenda-form-collector]  ANTES DE MAPEAR window.telasCreacion:', {
                    length: window.telasCreacion.length,
                    primer_elemento: window.telasCreacion[0]
                });

                prendaData.telasAgregadas = window.telasCreacion.map((tela, telaIdx) => {
                    // Copiar imágenes de tela: CRÍTICO - NUNCA usar blob URLs (se revoca en limpieza)
                    // Solo preservar File objects NUEVOS o rutas de almacenamiento PERMANENTES de BD
                    let imagenesDelaTela = tela.imagenes || [];
                    
                    // 🆕 CRÍTICO: Si tela.imagenes está vacío pero existe imagenesTelaStorage con imágenes
                    // usar las del storage como fallback (esto ocurre cuando se guardan cambios)
                    console.log(`[prenda-form-collector]  🔍 ANTES de fallback - Tela ${telaIdx}:`, {
                        imagenesDelaTela_length: imagenesDelaTela?.length || 0,
                        imagenesDelaTela_content: imagenesDelaTela,
                        imagenesTelaStorage_exists: !!window.imagenesTelaStorage,
                        imagenesTelaStorage_count: window.imagenesTelaStorage?.obtenerImagenes?.()?.length || 0
                    });
                    
                    if ((!imagenesDelaTela || imagenesDelaTela.length === 0 || 
                         (imagenesDelaTela.length > 0 && imagenesDelaTela[0] && Object.keys(imagenesDelaTela[0]).length === 0)) &&
                        window.imagenesTelaStorage && typeof window.imagenesTelaStorage.obtenerImagenes === 'function') {
                        
                        const imagenesDelStorage = window.imagenesTelaStorage.obtenerImagenes() || [];
                        if (imagenesDelStorage.length > 0) {
                            console.log(`[prenda-form-collector]  🆘 FALLBACK: Tela ${telaIdx} sin imágenes válidas, usando imagenesTelaStorage (${imagenesDelStorage.length} imágenes)`);
                            console.log(`[prenda-form-collector]  🆘 Imágenes del storage:`, imagenesDelStorage);
                            imagenesDelaTela = imagenesDelStorage;
                        } else {
                            console.log(`[prenda-form-collector]  ⚠️ imagenesTelaStorage VACÍO! No hay imágenes en storage`);
                        }
                    }
                    
                    const imagenesCopia = (imagenesDelaTela).map((img, imgIdx) => {
                        // 🔍 DEBUG PROFUNDO: Analizar exactamente qué es este objeto
                        let imagenDiagnostico = {
                            tipo: typeof img,
                            esFile: img instanceof File,
                            esObjeto: img && typeof img === 'object',
                            esNull: img === null,
                            esUndefined: img === undefined,
                            campos: img && typeof img === 'object' ? Object.keys(img) : 'N/A',
                            propiedadesEnumerables: img && typeof img === 'object' ? Object.getOwnPropertyNames(img).slice(0, 10) : 'N/A',
                            constructor: img?.constructor?.name || 'N/A',
                            toStringValor: Object.prototype.toString.call(img),
                            // Intentar acceder a propiedades directamente
                            _previewUrl: img?.previewUrl,
                            _ruta: img?.ruta,
                            _ruta_original: img?.ruta_original,
                            _ruta_webp: img?.ruta_webp,
                            _url: img?.url,
                            _id: img?.id,
                            _file: img?.file instanceof File ? 'File object' : typeof img?.file,
                            stringify_resultado: JSON.stringify(img)
                        };
                        console.log(`[prenda-form-collector] 🖼️ PROCESANDO imagen ${imgIdx} de tela ${telaIdx}:`, imagenDiagnostico);
                        
                        // 1️⃣ Si img es directamente un File object, usarlo (imagen nueva a subir)
                        if (img instanceof File) {
                            console.log(`[prenda-form-collector]    Imagen ${imgIdx}: FILE OBJECT`);
                            return img;
                        }
                        
                        // 2️⃣ Si img tiene propiedad file que es File object, usar eso (imagen cargada nuevamente)
                        if (img && img.file instanceof File) {
                            console.log(`[prenda-form-collector]    Imagen ${imgIdx}: FILE object dentro de propiedad`);
                            return img.file;
                        }
                        
                        // 3️⃣ CRÍTICO: Si es un objeto con información de BD, extraer RUTA DE ALMACENAMIENTO PERMANENTE
                        // NUNCA usar previewUrl/blob URLs aquí porque se revocan durante limpiarDespuésDeGuardar()
                        if (img && typeof img === 'object') {
                            // Buscar ruta de almacenamiento permanente en este orden de prioridad
                            if (img.ruta && img.ruta.startsWith('/')) {
                                console.log(`[prenda-form-collector]    Imagen ${imgIdx}: Usando img.ruta = ${img.ruta}`);
                                return img.ruta;  // 🎯 Prioridad 1: ruta absoluta de storage
                            }
                            if (img.ruta_original && img.ruta_original.startsWith('/')) {
                                console.log(`[prenda-form-collector]    Imagen ${imgIdx}: Usando img.ruta_original = ${img.ruta_original}`);
                                return img.ruta_original;  // 🎯 Prioridad 2: ruta original
                            }
                            if (img.ruta_webp && img.ruta_webp.startsWith('/')) {
                                console.log(`[prenda-form-collector]    Imagen ${imgIdx}: Usando img.ruta_webp = ${img.ruta_webp}`);
                                return img.ruta_webp;  // 🎯 Prioridad 3: ruta webp
                            }
                            // Si tiene URL de acceso, usarla si es path absoluto
                            if (img.url && img.url.startsWith('/')) {
                                console.log(`[prenda-form-collector]    Imagen ${imgIdx}: Usando img.url = ${img.url}`);
                                return img.url;
                            }
                            // Si tienen información de ID de BD, conservarla en un objeto (para merge posterior)
                            if (img.id || img.prenda_foto_id) {
                                console.log(`[prenda-form-collector]   ⚠️ Imagen ${imgIdx}: PRESERVANDO como objeto de BD (ID encontrado)`);
                                return {
                                    id: img.id || img.prenda_foto_id,
                                    // Conservar alguna ruta aunque sea blob, porque es respaldo
                                    urlFallback: img.previewUrl,
                                    enBD: true
                                };
                            }
                            
                            // 🆕 FALLBACK: Si es un objeto con 0 propiedades enumerables pero era en telasCreacion,
                            // intentar usar previewUrl como blob URL (último recurso)
                            if (Object.keys(img).length === 0 && img.previewUrl && img.previewUrl.startsWith('blob:')) {
                                console.log(`[prenda-form-collector]   ⚠️ Imagen ${imgIdx}: FALLBACK blob URL (objeto vacío pero con previewUrl)`);
                                // Retornar un objeto con la información que tenemos
                                return {
                                    previewUrl: img.previewUrl,
                                    esBlob: true,
                                    warning: 'Blob URL - puede revocar después'
                                };
                            }
                        }
                        
                        // 4️⃣ Si img es un string (ruta directa), usarlo
                        if (typeof img === 'string' && img.startsWith('/')) {
                            console.log(`[prenda-form-collector]    Imagen ${imgIdx}: STRING (ruta) = ${img}`);
                            return img;
                        }
                        
                        //  Ignorar blob URLs y otros valores inválidos
                        console.log(`[prenda-form-collector]    Imagen ${imgIdx}: DESCARTADA (blob URL o inválida)`);
                        return null;
                    }).filter(img => img !== null && img !== undefined);
                    
                    return {
                        tela: tela.nombre_tela || tela.tela || '',
                        color: tela.color || tela.color_nombre || '',
                        referencia: tela.referencia || '',
                        observaciones: tela.observaciones || '',
                        tela_id: tela.tela_id || 0,
                        color_id: tela.color_id || 0,
                        nombre_tela: tela.nombre_tela || tela.tela || '',
                        color_nombre: tela.color_nombre || tela.color || '',
                        imagenes: imagenesCopia
                    };
                });

                console.log('[prenda-form-collector] 🧵 DESPUÉS DE MAPEAR prendaData.telasAgregadas:', {
                    length: prendaData.telasAgregadas?.length || 0,
                    primer_elemento: prendaData.telasAgregadas?.[0]
                });
            } else {
                console.log('[prenda-form-collector]  NO HAY TELAS EN window.telasCreacion, mantiendo array vacío:', {
                    telasAgregadas_iniciales: prendaData.telasAgregadas
                });
            }
            // ============================================
            // 4.1. PROCESAR TELAS AGREGADAS (FLUJO EDICIÓN DESDE BD O COTIZACIÓN)
            // ============================================
            // IMPORTANTE: Solo usar window.telasAgregadas si window.telasCreacion NO fue definido
            // Si window.telasCreacion existe (incluso si está vacío), significa estamos en edición
            // y debemos respetar el estado actual [incluyendo la intención de eliminar todas las telas]
            if (window.telasAgregadas && Array.isArray(window.telasAgregadas) && window.telasAgregadas.length > 0 
                && (!window.telasCreacion || !Array.isArray(window.telasCreacion))) {
                console.log('[prenda-form-collector] 🧵 USANDO TELAS AGREGADAS (BD o Cotización)');
                prendaData.telasAgregadas = window.telasAgregadas.map((tela, telaIdx) => {
                    // Para cotización/BD, las imágenes ya vienen procesadas
                    const imagenesCopia = (tela.imagenes || []).map(img => {
                        // Si es una URL de BD, mantenerla como string
                        if (typeof img === 'string' && img.startsWith('/storage/')) {
                            return img;
                        }
                        // Si es un objeto con ruta, usar la ruta
                        if (img && img.ruta) {
                            return img.ruta;
                        }
                        // Si es un File object, usarlo
                        if (img instanceof File) {
                            return img;
                        }
                        return img;
                    }).filter(img => img !== null);
                    
                    return {
                        id: tela.id,  // Preservar ID de relación para MERGE
                        tela: tela.nombre_tela || tela.tela || '',
                        color: tela.color_nombre || tela.color || '',
                        referencia: tela.referencia || '',
                        observaciones: tela.observaciones || '',
                        color_id: tela.color_id,  // Preservar para MERGE
                        tela_id: tela.tela_id,    // Preservar para MERGE
                        imagenes: imagenesCopia
                    };
                });
            }
            // Si estamos en modo edición y no hay telas en window.telasAgregadas, 
            // obtener telas Y VARIANTES de la prenda anterior
            // PERO SOLO si window.telasCreacion no existe (no estamos en flujo de edición)
            else if (!window.telasCreacion && prendaEditIndex !== null && prendaEditIndex !== undefined && prendasArray[prendaEditIndex]) {
                const prendaAnterior = prendasArray[prendaEditIndex];
                
                // Copiar telas anteriores
                if (prendaAnterior && prendaAnterior.telasAgregadas && prendaAnterior.telasAgregadas.length > 0) {
                    prendaData.telasAgregadas = prendaAnterior.telasAgregadas.map(tela => ({
                        tela: tela.tela || '',
                        color: tela.color || '',
                        referencia: tela.referencia || '',
                        imagenes: tela.imagenes || []
                    }));
                }
                
                // IMPORTANTE: También copiar variantes anteriores si existen
                if (prendaAnterior && prendaAnterior.variantes && Object.keys(prendaAnterior.variantes).length > 0) {
                    prendaData.variantes = prendaAnterior.variantes;
                }
            }

            // ============================================
            // 5. RECOLECTAR VARIACIONES/VARIANTES
            // ============================================
            const variantes = {};
            
            // Manga
            const checkManga = document.getElementById('aplica-manga');
            if (checkManga && checkManga.checked) {
                const mangaInput = document.getElementById('manga-input');
                const mangaObs = document.getElementById('manga-obs');
                const valorManga = mangaInput?.value?.trim() || '';
                
                variantes.tipo_manga = valorManga;
                variantes.obs_manga = mangaObs?.value || '';
                
                // Buscar ID del tipo de manga en el datalist
                if (valorManga) {
                    const datalist = document.getElementById('opciones-manga');
                    let mangaId = null;
                    
                    if (datalist) {
                        // Buscar en las opciones del datalist
                        for (let option of datalist.options) {
                            if (option.value.toLowerCase() === valorManga.toLowerCase()) {
                                mangaId = option.dataset.id;
                                break;
                            }
                        }
                    }
                    
                    // Si encontramos el ID, guardarlo
                    if (mangaId) {
                        variantes.tipo_manga_id = parseInt(mangaId);
                        console.log('[prenda-form-collector] Manga encontrada en datalist:', {
                            nombre: valorManga,
                            id: mangaId
                        });
                    } else {
                        // Si no existe, marcar para creación asíncrona
                        variantes.tipo_manga_id = null;
                        variantes.tipo_manga_crear = true; // Flag para crear después
                        console.log('[prenda-form-collector]  Manga NO encontrada, se creará:', valorManga);
                    }
                }
            } else {
                variantes.tipo_manga = '';
                variantes.obs_manga = '';
                variantes.tipo_manga_id = null;
            }
            
            // Bolsillos
            const checkBolsillos = document.getElementById('aplica-bolsillos');
            if (checkBolsillos && checkBolsillos.checked) {
                const bolsillosObs = document.getElementById('bolsillos-obs');
                variantes.tiene_bolsillos = true;
                variantes.obs_bolsillos = bolsillosObs?.value || '';
            } else {
                variantes.tiene_bolsillos = false;
                variantes.obs_bolsillos = '';
            }
            
            // Broche
            const checkBroche = document.getElementById('aplica-broche');
            if (checkBroche && checkBroche.checked) {
                const broqueInput = document.getElementById('broche-input');
                const broqueObs = document.getElementById('broche-obs');
                variantes.tipo_broche = broqueInput?.value || '';
                variantes.obs_broche = broqueObs?.value || '';
                
                // Mapear valor del select a tipo_broche_boton_id
                // broche-input contiene: "broche" → ID 1, "boton" → ID 2
                const brocheValor = broqueInput?.value?.toLowerCase() || '';
                if (brocheValor === 'broche') {
                    variantes.tipo_broche_boton_id = 1;
                } else if (brocheValor === 'boton') {
                    variantes.tipo_broche_boton_id = 2;
                } else {
                    variantes.tipo_broche_boton_id = null;
                }
            } else {
                variantes.tipo_broche = '';
                variantes.obs_broche = '';
                variantes.tipo_broche_boton_id = null;
            }
            
            // Reflectivo
            const checkReflectivo = document.getElementById('aplica-reflectivo');
            if (checkReflectivo && checkReflectivo.checked) {
                const reflectivoObs = document.getElementById('reflectivo-obs');
                variantes.tiene_reflectivo = true;
                variantes.obs_reflectivo = reflectivoObs?.value || '';
            } else {
                variantes.tiene_reflectivo = false;
                variantes.obs_reflectivo = '';
            }
            
            prendaData.variantes = variantes;

            // ============================================
            // 6. ASIGNACIONES DE COLORES POR TALLA
            // ============================================
            // Recolectar asignaciones de colores-talla definidas en el módulo de colores-por-talla
            let asignacionesColores = {};
            
            // DIAGNÓSTICO: Verificar qué está disponible
            console.log('[prenda-form-collector] 🔍 DIAGNÓSTICO de asignaciones:');
            console.log('[prenda-form-collector]   - window.ColoresPorTalla existe?', !!window.ColoresPorTalla);
            console.log('[prenda-form-collector]   - window.ColoresPorTalla.obtenerDatosAsignaciones existe?', 
                window.ColoresPorTalla && typeof window.ColoresPorTalla.obtenerDatosAsignaciones === 'function');
            console.log('[prenda-form-collector]   - window.StateManager existe?', !!window.StateManager);
            console.log('[prenda-form-collector]   - window.StateManager.getAsignaciones existe?', 
                window.StateManager && typeof window.StateManager.getAsignaciones === 'function');
            
            if (window.ColoresPorTalla && typeof window.ColoresPorTalla.obtenerDatosAsignaciones === 'function') {
                asignacionesColores = window.ColoresPorTalla.obtenerDatosAsignaciones();
                console.log('[prenda-form-collector]  Asignaciones obtenidas de ColoresPorTalla:', asignacionesColores);
                console.log('[prenda-form-collector]   - ¿Vacío?', Object.keys(asignacionesColores).length === 0);
                console.log('[prenda-form-collector]   - Claves:', Object.keys(asignacionesColores));
            } else if (typeof obtenerDatosAsignacionesColores === 'function') {
                // Compatibilidad con la API antigua
                asignacionesColores = obtenerDatosAsignacionesColores();
                console.log('[prenda-form-collector]  Asignaciones de colores por talla (API antigua):', asignacionesColores);
            } else {
                // Si no hay función disponible, intentar obtener del StateManager
                if (window.StateManager && typeof window.StateManager.getAsignaciones === 'function') {
                    asignacionesColores = window.StateManager.getAsignaciones();
                    console.log('[prenda-form-collector]  Asignaciones de colores recuperadas de StateManager:');
                    console.log('[prenda-form-collector]   - Datos:', asignacionesColores);
                    console.log('[prenda-form-collector]   - Claves:', Object.keys(asignacionesColores));
                    console.log('[prenda-form-collector]   - ¿Vacío?', Object.keys(asignacionesColores).length === 0);
                } else {
                    asignacionesColores = {};
                    console.warn('[prenda-form-collector]  Función obtenerDatosAsignaciones no disponible y StateManager sin datos');
                }
            }
            
            prendaData.asignacionesColoresPorTalla = asignacionesColores;
            console.log('[prenda-form-collector]  prendaData.asignacionesColoresPorTalla asignado:', prendaData.asignacionesColoresPorTalla);

            // ============================================
            // 7. SEPARACIÓN DE FLUJOS: SIMPLE vs WIZARD
            // ============================================
            // Si hay asignaciones del wizard (colores por talla), recalcular cantidad_talla
            // con las cantidades reales en vez de los "1" que ColoresPorTalla.js pone en tallasRelacionales
            // para display. También marcar que las telas ya están en las asignaciones (no duplicar).
            const tieneAsignacionesWizard = Object.keys(asignacionesColores || {}).length > 0;
            
            if (tieneAsignacionesWizard) {
                console.log('[prenda-form-collector] 🔄 FLUJO WIZARD DETECTADO - Recalculando cantidad_talla desde asignaciones...');
                
                const tallasRecalculadas = {};
                Object.values(asignacionesColores).forEach(asignacion => {
                    const genero = (asignacion.genero || 'UNISEX').toUpperCase();
                    if (!tallasRecalculadas[genero]) {
                        tallasRecalculadas[genero] = {};
                    }
                    const talla = asignacion.talla;
                    // Sumar cantidades reales de colores para esta talla
                    const totalCantidad = (asignacion.colores || []).reduce((sum, c) => sum + (parseInt(c.cantidad) || 0), 0);
                    if (totalCantidad > 0 && talla) {
                        tallasRecalculadas[genero][talla] = totalCantidad;
                    }
                });
                
                console.log('[prenda-form-collector] 🔄 cantidad_talla ANTES (tallasRelacionales):', prendaData.cantidad_talla);
                console.log('[prenda-form-collector] 🔄 cantidad_talla DESPUÉS (recalculado):', tallasRecalculadas);
                prendaData.cantidad_talla = tallasRecalculadas;
                
                // Marcar flujo wizard para que el backend NO cree prenda_pedido_colores_telas (duplicado)
                prendaData.flujo = 'wizard';
            } else {
                prendaData.flujo = 'simple';
            }
            
            console.log('[prenda-form-collector] 📋 Flujo detectado:', prendaData.flujo);

            console.log('[prenda-form-collector]  Retornando prendaData completa:');
            console.log('[prenda-form-collector]  VERIFICACIÓN FINAL DE TELAS EN prendaData:', {
                telasAgregadas_exist: !!prendaData.telasAgregadas,
                telasAgregadas_isArray: Array.isArray(prendaData.telasAgregadas),
                telasAgregadas_length: prendaData.telasAgregadas?.length || 0,
                telasAgregadas_content: prendaData.telasAgregadas
            });
            console.log('[prenda-form-collector]', prendaData);

            return prendaData;

        } catch (error) {
            console.error('[prenda-form-collector]  ERROR CRÍTICO en construirPrendaDesdeFormulario:', error);
            console.error('[prenda-form-collector] Stack:', error.stack);
            return null;
        }
    }
}

// Instancia global para usar en toda la aplicación
window.prendaFormCollector = new PrendaFormCollector();
