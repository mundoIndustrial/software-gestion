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
            

            
            // Procesar imágenes: nuevas File objects Y imágenes de base de datos
            const imagenesCopia = imagenesTemporales.map(img => {

                
                // Si img es directamente un File object, usarlo
                if (img instanceof File) {

                    return img;
                }
                // Si img tiene propiedad file que es File object, usar eso
                if (img && img.file instanceof File) {

                    return img.file;
                }
                // Si es un objeto con previewUrl (desde BD), PRESERVARLO para edición
                if (img && img.previewUrl && !img.file) {
                    // Retornar objeto con la URL para que el backend sepa que es imagen existente
                    return {
                        previewUrl: img.previewUrl,
                        urlDesdeDB: true,
                        nombre: img.nombre
                    };
                }
                // Fallback: retornar img tal cual si es File

                return img;
            }).filter(img => img !== null && (img instanceof File || (img && img.urlDesdeDB)));

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
                        copia[tipoProceso] = {
                            tipo: proceso.tipo || tipoProceso,
                            datos: proceso.datos ? { ...proceso.datos } : null
                        };
                    }
                });
                return copia;
            };
            
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
                cantidad_talla: copiarTallasRelacionales(window.tallasRelacionales || { DAMA: {}, CABALLERO: {}, UNISEX: {} }),
                variantes: {}
            };

            // DEBUG: Log para ver qué se capturó
            console.log('[prenda-form-collector] 📦 Datos capturados en prendaData:');
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
            if (window.telasCreacion && Array.isArray(window.telasCreacion) && window.telasCreacion.length > 0) {
                prendaData.telasAgregadas = window.telasCreacion.map((tela, telaIdx) => {
                    // Copiar imágenes de tela: SOLO File objects (NO blobs ni previewUrl)
                    const imagenesCopia = (tela.imagenes || []).map(img => {
                        // Si img es directamente un File object, usarlo
                        if (img instanceof File) {

                            return img;
                        }
                        // Si img tiene propiedad file que es File object, usar eso
                        if (img && img.file instanceof File) {

                            return img.file;
                        }
                        // Si es un objeto con previewUrl (desde BD), ignorar
                        if (img && img.previewUrl && !img.file) {

                            return null;
                        }

                        return img;
                    }).filter(img => img !== null && img instanceof File);
                    
                    return {
                        tela: tela.tela || '',
                        color: tela.color || '',
                        referencia: tela.referencia || '',
                        imagenes: imagenesCopia
                    };
                });
            }
            // ============================================
            // 4.1. PROCESAR TELAS AGREGADAS (FLUJO EDICIÓN DESDE BD O COTIZACIÓN)
            // ============================================
            else if (window.telasAgregadas && Array.isArray(window.telasAgregadas) && window.telasAgregadas.length > 0) {
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
                        color_id: tela.color_id,  // Preservar para MERGE
                        tela_id: tela.tela_id,    // Preservar para MERGE
                        imagenes: imagenesCopia
                    };
                });
            }
            // Si estamos en modo edición y no hay telas en window.telasAgregadas, 
            // obtener telas Y VARIANTES de la prenda anterior
            else if (prendaEditIndex !== null && prendaEditIndex !== undefined && prendasArray[prendaEditIndex]) {
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

            console.log('[prenda-form-collector]  Retornando prendaData completa:');
            console.log('[prenda-form-collector]', prendaData);

            return prendaData;

        } catch (error) {

            return null;
        }
    }
}

// Instancia global para usar en toda la aplicación
window.prendaFormCollector = new PrendaFormCollector();
