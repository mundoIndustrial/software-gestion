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
            const origen = document.getElementById('nueva-prenda-origen-select')?.value || 'bodega';

            // Validar campos requeridos
            if (!nombre) {
                this.notificationService?.error('El nombre de la prenda es requerido');
                return null;
            }

            // ============================================
            // 2. PROCESAR IMÁGENES DE PRENDA
            // ============================================
            const imagenesTemporales = window.imagenesPrendaStorage?.obtenerImagenes?.() || [];
            

            
            // Copiar SOLO los File objects (NO blobs ni previewUrl)
            const imagenesCopia = imagenesTemporales.map(img => {

                
                // Si img es directamente un File object, usarlo
                if (img instanceof File) {

                    return img;
                }
                // Si img tiene propiedad file que es File object, usar eso
                if (img && img.file instanceof File) {

                    return img.file;
                }
                // Si es un objeto con previewUrl (desde BD), ignorar - no es un File nuevo
                if (img && img.previewUrl && !img.file) {

                    return null;
                }
                // Fallback: retornar img tal cual si es File

                return img;
            }).filter(img => img !== null && img instanceof File);

            // ============================================
            // 3. CONSTRUIR OBJETO BASE DE PRENDA
            // ============================================
            const prendaData = {
                tipo: 'prenda_nueva',
                nombre_prenda: nombre,
                descripcion: descripcion || '',
                origen: origen,
                // Imágenes de prenda copiadas del storage
                imagenes: imagenesCopia,
                telasAgregadas: [],
                procesos: window.procesosSeleccionados || {},
                // Estructura relacional: { DAMA: {S: 5}, CABALLERO: {M: 3} }
                cantidad_talla: window.tallasRelacionales || { DAMA: {}, CABALLERO: {}, UNISEX: {} },
                variantes: {}
            };

            // ============================================
            // 4. PROCESAR TELAS AGREGADAS
            // ============================================
            if (window.telasAgregadas && Array.isArray(window.telasAgregadas) && window.telasAgregadas.length > 0) {
                prendaData.telasAgregadas = window.telasAgregadas.map((tela, telaIdx) => {
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
                        // Imágenes de tela copiadas
                        imagenes: imagenesCopia
                    };
                });
            }
            // Si estamos en modo edición y no hay telas en window.telasAgregadas, 
            // obtener telas de la prenda anterior
            else if (prendaEditIndex !== null && prendaEditIndex !== undefined && prendasArray[prendaEditIndex]) {
                const prendaAnterior = prendasArray[prendaEditIndex];
                if (prendaAnterior && prendaAnterior.telasAgregadas && prendaAnterior.telasAgregadas.length > 0) {
                    prendaData.telasAgregadas = prendaAnterior.telasAgregadas.map(tela => ({
                        tela: tela.tela || '',
                        color: tela.color || '',
                        referencia: tela.referencia || '',
                        imagenes: tela.imagenes || []
                    }));

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
                variantes.manga = mangaInput?.value || '';
                variantes.obs_manga = mangaObs?.value || '';
            } else {
                variantes.manga = '';
                variantes.obs_manga = '';
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
                variantes.broche = broqueInput?.value || '';
                variantes.obs_broche = broqueObs?.value || '';
            } else {
                variantes.broche = '';
                variantes.obs_broche = '';
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


            return prendaData;

        } catch (error) {

            return null;
        }
    }
}

// Instancia global para usar en toda la aplicación
window.prendaFormCollector = new PrendaFormCollector();
