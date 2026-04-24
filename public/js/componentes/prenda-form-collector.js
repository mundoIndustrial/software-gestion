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

    _crearFirmaImagen(img) {
        const file = img instanceof File ? img : (img?.file instanceof File ? img.file : null);
        if (file) {
            return ['file', file.name || '', file.size || 0, file.type || '', file.lastModified || 0].join('|');
        }

        const idPersistente = img?.id || img?.prenda_foto_id || null;
        if (idPersistente) {
            return `id|${idPersistente}`;
        }

        const rutaPersistente = img?.ruta || img?.ruta_original || img?.ruta_webp || img?.url || img?.previewUrl || '';
        if (typeof rutaPersistente === 'string' && rutaPersistente.trim() !== '') {
            return `ruta|${rutaPersistente.trim()}`;
        }

        return null;
    }

    _deduplicarImagenes(imagenes = [], contexto = 'imagenes') {
        if (!Array.isArray(imagenes) || imagenes.length <= 1) {
            return Array.isArray(imagenes) ? imagenes : [];
        }

        const vistos = new Set();
        const resultado = [];

        imagenes.forEach((img, idx) => {
            const firma = this._crearFirmaImagen(img) || `sin-firma|${idx}`;
            if (vistos.has(firma)) {
                console.warn(`[prenda-form-collector] Imagen duplicada omitida en ${contexto}:`, firma);
                return;
            }
            vistos.add(firma);
            resultado.push(img);
        });

        return resultado;
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
            const origenSelect = document.getElementById('nueva-prenda-origen-select')?.value || 'confeccion';
            
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
            // ✅ CRÍTICO: Establecer contexto ANTES de obtener imágenes (por si no se estableció correctamente)
            // Esto asegura que obtenemos las imágenes del almacenamiento correcto
            if (globalThis.imagenesPrendaStorage && typeof globalThis.imagenesPrendaStorage.setPrendaActual === 'function') {
                // Determine prenda ID: si estamos editando una prenda existente, usar el índice; si no, usar ID único del modal
                const isEditMode = prendaEditIndex !== null && prendaEditIndex !== undefined;
                let prendaId;

                if (isEditMode) {
                    prendaId = prendaEditIndex;
                } else {
                    // Para nuevas prendas, obtener el ID único del modal
                    const modalElement = document.getElementById('modal-agregar-prenda-nueva');
                    prendaId = (modalElement?.dataset?.draftPrendaLocalId?.trim()) || 'default';
                }

                globalThis.imagenesPrendaStorage.setPrendaActual(prendaId);
                console.log('[prenda-form-collector] Contexto de prenda establecido antes de obtener imágenes:', prendaId, '(modo edit:', isEditMode, ')');
            }

            const imagenesTemporales = globalThis.imagenesPrendaStorage?.obtenerImagenes?.() || [];
            
            console.log('[prenda-form-collector]  PROCESANDO IMÁGENES DE PRENDA:', {
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
            const imagenesCopia = this._deduplicarImagenes(imagenesTemporales.map((img, imgIdx) => {
                console.log(`[prenda-form-collector]  PROCESANDO IMAGEN ${imgIdx}:`);
                console.log(`[prenda-form-collector]    CONTENIDO COMPLETO DEL OBJETO:`, JSON.stringify({
                    previewUrl: img?.previewUrl?.substring ? img.previewUrl.substring(0, 80) : img?.previewUrl,
                    url: img?.url?.substring ? img.url.substring(0, 80) : img?.url,
                    nombre: img?.nombre,
                    tamano: img?.tamano,
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
                    //  CRÍTICO: Guardar el File object CON el metadata, no solo el File
                    // Esto asegura que cuando se recupere la imagen, tenga toda la info
                    return {
                        file: img.file,                    // ← El File object real
                        previewUrl: img.previewUrl,        // ← El blob URL para preview
                        nombre: img.nombre,
                        tamano: img.tamano,
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
                    // Respaldo controlado: si solo tiene blob URL y datos de BD, preservar ID para merge
                    console.log(`[prenda-form-classifier]    DECISIÓN: Preservando con blob URL de respaldo`);
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
                
                // 4️⃣ RESGUARDO DEFENSIVO: Si es un objeto que llegó del storage pero no tiene previewUrl,
                // preservarlo de todas formas porque algo debe tener
                if (img && typeof img === 'object') {
                    console.log(`[prenda-form-collector]    Objeto sin previewUrl - preservando como está`);
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
                //  CRÍTICO: Descartar IMÁGENES VACÍAS (blob URLs revocados del storage)
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
                
                console.log(`[prenda-form-collector]  FILTER [${filterIdx}]: esValido=${esValido}`, {
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
            }), 'prenda');
            
            console.log('[prenda-form-collector]  IMÁGENES DE PRENDA DESPUÉS DE PROCESAR:', {
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
            // porque globalThis.tallasRelacionales es limpiado después
            // Si asignas la referencia, el objeto se vacía
            const copiarTallasRelacionales = (obj) => {
                const copia = {};
                Object.entries(obj).forEach(([genero, tallasObj]) => {
                    copia[genero] = { ...tallasObj };
                });
                return copia;
            };
            
            //  IMPORTANTE: Hacer DEEP COPY de procesosSeleccionados
            // porque globalThis.procesosSeleccionados puede ser limpiado después
            const copiarProcesos = (procesos) => {
                console.log('[copiarProcesos]  INICIANDO - Procesos a copiar:', {
                    'es_objeto': !Array.isArray(procesos) && typeof procesos === 'object',
                    'keys': Object.keys(procesos || {}),
                    'contenido_completo': procesos
                });
                
                if (!procesos || typeof procesos !== 'object') {
                    return {};
                }
                const procesosMarcadosParaEliminar = new Set(
                    (Array.isArray(globalThis.procesosParaEliminarIds)
                        ? globalThis.procesosParaEliminarIds
                        : Array.from(globalThis.procesosParaEliminarIds || [])
                    ).map((id) => String(id))
                );
                const copia = {};
                Object.entries(procesos).forEach(([tipoProceso, proceso]) => {
                    console.log(`[copiarProcesos] Copiando tipo: ${tipoProceso}`, {
                        'proceso.tipo': proceso?.tipo,
                        'proceso.datos EXISTS': !!proceso?.datos,
                        'proceso.datos type': typeof proceso?.datos,
                        'proceso.datos keys': Object.keys(proceso?.datos || {}),
                        'proceso COMPLETO': proceso
                    });
                    
                    if (proceso && typeof proceso === 'object') {
                        // Deep copy completo de datos (incluye tallas, ubicaciones, observaciones, imagenes, datosExtendidos)
                        let datosCopiados = null;
                        if (proceso.datos && typeof proceso.datos === 'object') {
                            const modoProcesoNormalizado = proceso.datos.modo_tallas || 'generico';

                            datosCopiados = {
                                ...proceso.datos,
                                modo_tallas: modoProcesoNormalizado,
                                ubicaciones: Array.isArray(proceso.datos.ubicaciones) ? [...proceso.datos.ubicaciones] : (proceso.datos.ubicaciones || []),
                                tallas: proceso.datos.tallas ? JSON.parse(JSON.stringify(proceso.datos.tallas)) : {},
                                imagenes: Array.isArray(proceso.datos.imagenes) ? [...proceso.datos.imagenes] : [],
                                //  CRÍTICO: Preservar Files de fotos generales (modo general)
                                fotosGeneralesFiles: Array.isArray(proceso.datos.fotosGeneralesFiles) ? [...proceso.datos.fotosGeneralesFiles] : [],
                                imagenesFiles: Array.isArray(proceso.datos.imagenesFiles) ? [...proceso.datos.imagenesFiles] : [],
                                //  CRÍTICO: Preservar datosExtendidos con imagenesFiles (File objects)
                                datosExtendidos: proceso.datos.datosExtendidos ? JSON.parse(JSON.stringify(proceso.datos.datosExtendidos)) : {}
                            };
                            
                            //  Restaurar imagenesFiles arrays que se pierden en JSON.stringify
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
                            modo_tallas: datosCopiados?.modo_tallas || 'generico'
                        };

                        // Evitar reinsertar procesos que ya fueron marcados para eliminar en esta edición
                        const procesoId = copia[tipoProceso]?.datos?.id
                            || copia[tipoProceso]?.datos?.proceso_prenda_detalle_id
                            || null;
                        if (procesoId && procesosMarcadosParaEliminar.has(String(procesoId))) {
                            delete copia[tipoProceso];
                        }
                    }
                });
                return copia;
            };
            
            // IMPORTANTE: Obtener tallas desde globalThis.tallasRelacionales O desde StateManager (si viene del wizard)
            let tallasParaGuardar = copiarTallasRelacionales(globalThis.tallasRelacionales || {});
            
            // Si globalThis.tallasRelacionales está vacío pero hay datos en StateManager, usarlos
            const hasglobalThisTallas = Object.keys(tallasParaGuardar).some(genero => Object.keys(tallasParaGuardar[genero] || {}).length > 0);
            
            if (!hasglobalThisTallas && globalThis.StateManager && globalThis.StateManager.getAsignaciones) {
                console.log('[prenda-form-collector]  globalThis.tallasRelacionales está vacío, recuperando de StateManager...');
                
                const asignaciones = globalThis.StateManager.getAsignaciones();
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
            
            const tieneTallasNoUnisex = Object.entries(tallasParaGuardar || {}).some(([genero, tallas]) => {
                const gen = String(genero || '').toUpperCase();
                if (gen === 'UNISEX' || gen === 'GENERICO') return false;
                return Object.keys(tallas || {}).length > 0;
            });
            const tieneUnisex = Object.keys(tallasParaGuardar?.UNISEX || {}).length > 0;

            // Si hay tallas por género, eliminar cualquier rastro de GENERICO arrastrado
            if (tieneTallasNoUnisex && tallasParaGuardar.GENERICO) {
                delete tallasParaGuardar.GENERICO;
            }
            // Si hay UNISEX explícito, evitar duplicado en GENERICO
            if (tieneUnisex && tallasParaGuardar.GENERICO) {
                delete tallasParaGuardar.GENERICO;
            }

            // 🟢 NUEVO: Si hay "SOLO CANTIDAD", agregarlo al objeto de tallas con género especial
            if (globalThis.cantidadSoloSeleccionada && globalThis.cantidadSoloSeleccionada > 0) {
                if (!tieneTallasNoUnisex && !tieneUnisex) {
                    console.log('[prenda-form-collector]  "SOLO CANTIDAD" detectado:', globalThis.cantidadSoloSeleccionada);
                    
                    // Inicializar el género especial si no existe
                    if (!tallasParaGuardar['GENERICO']) {
                        tallasParaGuardar['GENERICO'] = {};
                    }
                    
                    // Agregar la cantidad con talla especial "SIN_ESPECIFICAR"
                    tallasParaGuardar['GENERICO']['SIN_ESPECIFICAR'] = globalThis.cantidadSoloSeleccionada;
                    
                    console.log('[prenda-form-collector] Tallas actualizadas con SOLO CANTIDAD:', tallasParaGuardar);
                } else {
                    console.log('[prenda-form-collector]  "SOLO CANTIDAD" ignorado: ya hay tallas por género');
                }
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
                procesos: copiarProcesos(globalThis.procesosSeleccionados),
                procesos_a_eliminar: Array.from(globalThis.procesosParaEliminarIds || []),
                // Estructura relacional: { DAMA: {S: 5}, CABALLERO: {M: 3} }
                //  COPIA PROFUNDA para evitar que se vacíe cuando se limpie el modal
                cantidad_talla: copiarTallasRelacionales(tallasParaGuardar || { DAMA: {}, CABALLERO: {}, UNISEX: {} }),
                variantes: {}
            };
            
            //  LOG CRÍTICO INMEDIATO: Verificar que prendaData.imagenes se asignó correctamente
            console.log('[prenda-form-collector]  CRÍTICO - prendaData.imagenes asignado JUSTO DESPUÉS DE CREAR prendaData:', {
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
            console.log('[prenda-form-collector]   - globalThis.tallasRelacionales:', globalThis.tallasRelacionales);
            console.log('[prenda-form-collector]   - ¿Son el MISMO objeto (tallas)?', prendaData.cantidad_talla === globalThis.tallasRelacionales);
            console.log('[prenda-form-collector]   - ¿Son el MISMO objeto (procesos)?', prendaData.procesos === globalThis.procesosSeleccionados);

            // ============================================
            // 4. PROCESAR TELAS AGREGADAS (FUENTE UNICA)
            // ============================================
            // Preservar imagenes de tela (nuevas y existentes) para que lleguen al
            // FormData y no se pierdan al crear el pedido.
            const normalizarImagenesTela = (tela = {}) => {
                const imagenesFuente = Array.isArray(tela.imagenes)
                    ? tela.imagenes
                    : (Array.isArray(tela.fotos) ? tela.fotos : []);

                return this._deduplicarImagenes(imagenesFuente
                    .map((img) => {
                        if (img instanceof File) {
                            return img;
                        }

                        if (img && img.file instanceof File) {
                            return {
                                file: img.file,
                                uid: img.uid || null,
                                previewUrl: img.previewUrl || img.preview || null,
                                nombre: img.nombre || img.file.name || ''
                            };
                        }

                        if (typeof img === 'string' && img.trim() !== '') {
                            return {
                                ruta: img,
                                ruta_webp: img,
                                urlDesdeDB: true
                            };
                        }

                        if (img && typeof img === 'object') {
                            const ruta = img.ruta || img.ruta_webp || img.ruta_original || img.url || img.previewUrl || null;
                            if (ruta) {
                                return {
                                    uid: img.uid || null,
                                    ruta: ruta,
                                    ruta_webp: img.ruta_webp || ruta,
                                    ruta_original: img.ruta_original || null,
                                    url: img.url || ruta,
                                    previewUrl: img.previewUrl || null,
                                    nombre: img.nombre || img.nombre_archivo || ''
                                };
                            }
                        }

                        return null;
                    })
                    .filter((img) => {
                        if (!img) return false;
                        if (img instanceof File) return true;
                        if (img.file instanceof File) return true;
                        return !!(img.ruta || img.ruta_webp || img.ruta_original || img.url || img.previewUrl);
                    }), 'tela');
            };

            const mapearTelaCanonica = (tela = {}) => ({
                id: tela.id || tela._original_id || tela.prenda_pedido_colores_telas_id || null,
                _original_id: tela._original_id || tela.id || null,
                prenda_pedido_colores_telas_id: tela.prenda_pedido_colores_telas_id || tela.id || tela._original_id || null,
                tela: tela.nombre_tela || tela.tela || '',
                color: tela.color || tela.color_nombre || '',
                referencia: tela.referencia || '',
                observaciones: tela.observaciones || '',
                tela_id: tela.tela_id || 0,
                color_id: tela.color_id || 0,
                nombre_tela: tela.nombre_tela || tela.tela || '',
                color_nombre: tela.color_nombre || tela.color || '',
                imagenes: normalizarImagenesTela(tela)
            });

            console.log('[prenda-form-collector] INICIANDO PROCESAMIENTO DE TELAS (fuente unica):', {
                globalThis_telasCreacion_exists: !!globalThis.telasCreacion,
                globalThis_telasCreacion_isArray: Array.isArray(globalThis.telasCreacion),
                globalThis_telasCreacion_length: globalThis.telasCreacion?.length || 0
            });

            if (Array.isArray(globalThis.telasCreacion) && globalThis.telasCreacion.length > 0) {
                prendaData.telasAgregadas = globalThis.telasCreacion.map(mapearTelaCanonica);
            } else if (
                Array.isArray(globalThis.telasAgregadas) &&
                globalThis.telasAgregadas.length > 0 &&
                (!globalThis.telasCreacion || !Array.isArray(globalThis.telasCreacion))
            ) {
                prendaData.telasAgregadas = globalThis.telasAgregadas.map(mapearTelaCanonica);
            } else if (
                !globalThis.telasCreacion &&
                prendaEditIndex !== null &&
                prendaEditIndex !== undefined &&
                prendasArray[prendaEditIndex]
            ) {
                const prendaAnterior = prendasArray[prendaEditIndex];
                if (Array.isArray(prendaAnterior?.telasAgregadas) && prendaAnterior.telasAgregadas.length > 0) {
                    prendaData.telasAgregadas = prendaAnterior.telasAgregadas.map(mapearTelaCanonica);
                }

                // IMPORTANTE: Tambien copiar variantes anteriores si existen
                if (prendaAnterior && prendaAnterior.variantes && Object.keys(prendaAnterior.variantes).length > 0) {
                    prendaData.variantes = prendaAnterior.variantes;
                }
            }

            console.log('[prenda-form-collector] TELAS CANONICAS mapeadas (preservando imagenes):', {
                length: prendaData.telasAgregadas?.length || 0,
                primer_elemento: prendaData.telasAgregadas?.[0]
            });

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
                        variantes.tipo_manga_id = Number(mangaId);
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
            console.log('[prenda-form-collector]  DIAGNÓSTICO de asignaciones:');
            console.log('[prenda-form-collector]   - globalThis.ColoresPorTalla existe?', !!globalThis.ColoresPorTalla);
            console.log('[prenda-form-collector]   - globalThis.ColoresPorTalla.obtenerDatosAsignaciones existe?', 
                globalThis.ColoresPorTalla && typeof globalThis.ColoresPorTalla.obtenerDatosAsignaciones === 'function');
            console.log('[prenda-form-collector]   - globalThis.StateManager existe?', !!globalThis.StateManager);
            console.log('[prenda-form-collector]   - globalThis.StateManager.getAsignaciones existe?', 
                globalThis.StateManager && typeof globalThis.StateManager.getAsignaciones === 'function');
            
            if (globalThis.ColoresPorTalla && typeof globalThis.ColoresPorTalla.obtenerDatosAsignaciones === 'function') {
                asignacionesColores = globalThis.ColoresPorTalla.obtenerDatosAsignaciones();
                console.log('[prenda-form-collector]  Asignaciones obtenidas de ColoresPorTalla:', asignacionesColores);
                console.log('[prenda-form-collector]   - ¿Vacío?', Object.keys(asignacionesColores).length === 0);
                console.log('[prenda-form-collector]   - Claves:', Object.keys(asignacionesColores));
            } else if (typeof obtenerDatosAsignacionesColores === 'function') {
                // Compatibilidad con la API antigua
                asignacionesColores = obtenerDatosAsignacionesColores();
                console.log('[prenda-form-collector]  Asignaciones de colores por talla (API antigua):', asignacionesColores);
            } else {
                // Si no hay función disponible, intentar obtener del StateManager
                if (globalThis.StateManager && typeof globalThis.StateManager.getAsignaciones === 'function') {
                    asignacionesColores = globalThis.StateManager.getAsignaciones();
                    console.log('[prenda-form-collector]  Asignaciones de colores recuperadas de StateManager:');
                    console.log('[prenda-form-collector]   - Datos:', asignacionesColores);
                    console.log('[prenda-form-collector]   - Claves:', Object.keys(asignacionesColores));
                    console.log('[prenda-form-collector]   - ¿Vacío?', Object.keys(asignacionesColores).length === 0);
                } else {
                    asignacionesColores = {};
                    console.warn('[prenda-form-collector]  Función obtenerDatosAsignaciones no disponible y StateManager sin datos');
                }
            }
            
            const construirAsignacionesConImagenesPersistidas = (asignacionesBase) => {
                const resultado = {};
                const getImageWizard = (globalThis.ColoresPorTalla && typeof globalThis.ColoresPorTalla.getImage === 'function')
                    ? globalThis.ColoresPorTalla.getImage.bind(globalThis.ColoresPorTalla)
                    : null;

                Object.entries(asignacionesBase || {}).forEach(([clave, asignacion]) => {
                    const copiaAsignacion = {
                        ...(asignacion || {}),
                        colores: []
                    };

                    const colores = Array.isArray(asignacion?.colores) ? asignacion.colores : [];
                    copiaAsignacion.colores = colores.map((color) => {
                        const colorCopia = { ...(color || {}) };

                        if (getImageWizard && colorCopia.imagen_id) {
                            const imagenWizard = getImageWizard(colorCopia.imagen_id);
                            if (imagenWizard) {
                                const file = imagenWizard.file instanceof File ? imagenWizard.file : null;
                                if (file) {
                                    colorCopia.imagen = {
                                        file,
                                        nombre: imagenWizard.nombre || file.name || '',
                                        blobUrl: imagenWizard.blobUrl || null
                                    };
                                }
                            }
                        }

                        return colorCopia;
                    });

                    resultado[clave] = copiaAsignacion;
                });

                return resultado;
            };

            const asignacionesColoresCopia = construirAsignacionesConImagenesPersistidas(asignacionesColores || {});
            prendaData.asignacionesColoresPorTalla = asignacionesColoresCopia;
            prendaData.asignacionesColores = asignacionesColoresCopia;
            console.log('[prenda-form-collector]  prendaData.asignacionesColoresPorTalla asignado:', prendaData.asignacionesColoresPorTalla);
            console.log('[prenda-form-collector]  Imagenes preservadas en asignaciones:', Object.values(asignacionesColoresCopia).reduce((acc, asig) => {
                const colores = Array.isArray(asig?.colores) ? asig.colores : [];
                acc.totalColores += colores.length;
                acc.conImagenId += colores.filter(c => !!c?.imagen_id).length;
                acc.conImagenFile += colores.filter(c => !!(c?.imagen?.file instanceof File)).length;
                acc.conImagenRuta += colores.filter(c => typeof c?.imagen_ruta === 'string' && c.imagen_ruta.trim() !== '').length;
                return acc;
            }, { totalColores: 0, conImagenId: 0, conImagenFile: 0, conImagenRuta: 0 }));

            // ============================================
            // 7. SEPARACIÓN DE FLUJOS: SIMPLE vs WIZARD
            // ============================================
            // Si hay asignaciones del wizard (colores por talla), recalcular cantidad_talla
            // con las cantidades reales en vez de los "1" que ColoresPorTalla.js pone en tallasRelacionales
            // para display. También marcar que las telas ya están en las asignaciones (no duplicar).
            const tieneAsignacionesWizard = Object.keys(asignacionesColoresCopia || {}).length > 0;
            
            if (tieneAsignacionesWizard) {
                console.log('[prenda-form-collector]  FLUJO WIZARD DETECTADO - Recalculando cantidad_talla desde asignaciones...');
                
                const tallasRecalculadas = {};
                Object.values(asignacionesColoresCopia).forEach(asignacion => {
                    const genero = (asignacion.genero || 'UNISEX').toUpperCase();
                    if (!tallasRecalculadas[genero]) {
                        tallasRecalculadas[genero] = {};
                    }
                    const talla = asignacion.talla;
                    // Sumar cantidades reales de colores para esta talla
                    const totalCantidad = (asignacion.colores || []).reduce((sum, c) => sum + (Number(c.cantidad) || 0), 0);
                    if (totalCantidad > 0 && talla) {
                        tallasRecalculadas[genero][talla] = totalCantidad;
                    }
                });
                
                console.log('[prenda-form-collector]  cantidad_talla ANTES (tallasRelacionales):', prendaData.cantidad_talla);
                console.log('[prenda-form-collector]  cantidad_talla DESPUÉS (recalculado):', tallasRecalculadas);
                prendaData.cantidad_talla = tallasRecalculadas;
                
                // Marcar flujo wizard para que el backend NO cree prenda_pedido_colores_telas (duplicado)
                prendaData.flujo = 'wizard';
                prendaData.tipoFlujoTallas = 'talla_color';
                prendaData.tipo_flujo_tallas = 'talla_color';
            } else {
                prendaData.flujo = 'simple';
                const tieneTallas = Object.values(prendaData.cantidad_talla || {}).some(
                    (tallasGenero) => tallasGenero && Object.keys(tallasGenero).length > 0
                );
                prendaData.tipoFlujoTallas = tieneTallas ? 'normal' : 'sin_tallas';
                prendaData.tipo_flujo_tallas = prendaData.tipoFlujoTallas;
            }
            
            console.log('[prenda-form-collector]  Flujo detectado:', prendaData.flujo);

            console.log('[prenda-form-collector]  Retornando prendaData completa:');
            console.log('[prenda-form-collector]  VERIFICACIÓN FINAL DE TELAS EN prendaData:', {
                telasAgregadas_exist: !!prendaData.telasAgregadas,
                telasAgregadas_isArray: Array.isArray(prendaData.telasAgregadas),
                telasAgregadas_length: prendaData.telasAgregadas?.length || 0,
                telasAgregadas_content: prendaData.telasAgregadas
            });
            console.log('[prenda-form-collector]', prendaData);

            // Preservar el _local_id del modal para evitar que _asegurarIdentidadPrenda() genere uno nuevo
            const modalElement = document.getElementById('modal-agregar-prenda-nueva');
            const modalLocalId = modalElement?.dataset?.draftPrendaLocalId;

            console.log('[prenda-form-collector]  DIAGNÓSTICO FINAL _local_id:', {
                modalElement_existe: !!modalElement,
                dataset_existe: !!modalElement?.dataset,
                draftPrendaLocalId_valor: modalLocalId,
                modalLocalId_truthy: !!modalLocalId,
                prendaData_local_id_antes: prendaData._local_id
            });

            if (modalLocalId) {
                prendaData._local_id = modalLocalId;
                console.log('[prenda-form-collector]  ✓ _local_id preservado del modal:', modalLocalId);
            } else {
                console.warn('[prenda-form-collector]  ⚠️ NO HAY _local_id en el modal, prendaData no tendrá _local_id inicial');
            }

            console.log('[prenda-form-collector]  prendaData._local_id final:', prendaData._local_id);

            return prendaData;

        } catch (error) {
            console.error('[prenda-form-collector]  ERROR CRÍTICO en construirPrendaDesdeFormulario:', error);
            console.error('[prenda-form-collector] Stack:', error.stack);
            return null;
        }
    }
}

// Instancia global para usar en toda la aplicación
globalThis.prendaFormCollector = new PrendaFormCollector();
