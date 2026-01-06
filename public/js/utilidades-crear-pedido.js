/**
 * UTILIDADES PARA CREAR PEDIDO
 * Contiene helpers, managers y constantes reutilizables
 * 
 * ⭐⭐ CARGAR PRIMERO EN EL HTML (antes de todos los otros módulos)
 * Inicializa: window.fotosEliminadas (Set), FotoHelper, CantidadesManager, ESTILOS_FOTOS
 * 
 * Importar en HTML:
 * <script src="/js/utilidades-crear-pedido.js"></script>
 */

console.log('🔧 UTILIDADES: Iniciando carga de utilidades-crear-pedido.js');

// ============================================================
// INICIALIZACIÓN DE ESTADO GLOBAL
// ============================================================

/**
 * Inicializa estado global para gestión de fotos y prendas
 */
const initializeGlobalState = () => {
    if (!window.fotosEliminadas) window.fotosEliminadas = new Set();
    if (!window.prendasFotosNuevas) window.prendasFotosNuevas = [];
    if (!window.telasFotosNuevas) window.telasFotosNuevas = [];
    if (!window.reflectiveFotosNuevas) window.reflectiveFotosNuevas = [];
    if (!window.logoFotosNuevas) window.logoFotosNuevas = [];
};
initializeGlobalState();
console.log('✅ UTILIDADES: Estado global inicializado', { fotosEliminadas: window.fotosEliminadas, prendasFotosNuevas: window.prendasFotosNuevas });

// ============================================================
// HELPER PARA GESTIÓN DE URLS DE FOTOS
// ============================================================

/**
 * Helper para manipular y validar URLs de fotos
 * Maneja diferentes formatos de fotos (strings, objetos con diferentes propiedades)
 */
const FotoHelper = {
    /**
     * Extrae la URL de un objeto foto (string o con propiedades)
     * @param {string|object} foto - Foto como string o objeto
     * @returns {string|null} URL de la foto
     */
    toUrl(foto) {
        if (!foto) return null;
        if (typeof foto === 'string') return foto;
        return foto.preview || foto.url || foto.ruta_webp || foto.ruta_original || null;
    },

    /**
     * Verifica si una URL está marcada como eliminada
     * @param {string} url - URL a verificar
     * @returns {boolean}
     */
    isEliminated(url) {
        return window.fotosEliminadas?.has(url) || false;
    },

    /**
     * Normaliza un array de fotos a solo URLs válidas
     * @param {array} fotos - Array de fotos
     * @returns {array} Array de URLs
     */
    normalize(fotos) {
        return (fotos || [])
            .map(f => this.toUrl(f))
            .filter(url => url && !this.isEliminated(url));
    },

    /**
     * Filtra fotos que no estén eliminadas
     * @param {array} fotos - Array de fotos
     * @returns {array} Fotos no eliminadas
     */
    filterActive(fotos) {
        return (fotos || []).filter(foto => {
            const url = this.toUrl(foto);
            return url && !this.isEliminated(url);
        });
    },

    /**
     * Marca una URL como eliminada
     * @param {string} url - URL a eliminar
     */
    markAsEliminated(url) {
        if (url) window.fotosEliminadas.add(url);
    }
};

// Exponer FotoHelper globalmente
window.FotoHelper = FotoHelper;

// ============================================================
// CONSTANTES DE ESTILOS CSS
// ============================================================

/**
 * Constantes de estilos CSS inline para fotos y componentes de prendas
 * Centraliza estilos para fácil mantenimiento
 */
const ESTILOS_FOTOS = {
    // Contenedores de fotos
    fotosTelaContenedor: "position: relative; width: 100%; max-width: 110px; margin: 0 auto; border: 2px solid #1e40af; border-radius: 10px; background: #f0f7ff; padding: 0.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.06); display: flex; flex-direction: column; align-items: center; gap: 0.4rem;",
    fotosTelaContenedorVacio: "width: 100%; max-width: 110px; margin: 0 auto; border: 2px dashed #1e40af; border-radius: 10px; background: #f0f7ff; padding: 0.5rem; display: flex; flex-direction: column; align-items: center; gap: 0.35rem;",
    
    // Imágenes
    imgTelaContenedor: "position: relative; width: 90px; height: 90px; overflow: hidden; border-radius: 8px; border: 1px solid #d0d0d0; box-shadow: 0 1px 4px rgba(0,0,0,0.08); background: white;",
    imgTelaBase: "width: 100%; height: 100%; object-fit: cover; cursor: pointer; transition: transform 0.2s;",
    imgTelaPlaceholder: "width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#9ca3af;font-size:0.8rem;",
    
    // Botones
    btnAgregarFoto: "background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%); color: white; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; font-weight: 900; font-size: 0.95rem; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 3px 8px rgba(14,165,233,0.2);",
    btnEliminarImg: "position: absolute; top: 6px; right: 6px; background: #dc3545; color: white; border: none; width: 20px; height: 20px; border-radius: 50%; cursor: pointer; font-weight: bold; font-size: 0.8rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.2); z-index: 8;",
    btnEditar: "background: #3b82f6; color: white; border: none; padding: 0.5rem 0.6rem; border-radius: 4px; cursor: pointer; font-size: 0.9rem; transition: all 0.2s; min-width: 35px;",
    btnEliminar: "background: #dc3545; color: white; border: none; padding: 0.5rem 0.6rem; border-radius: 4px; cursor: pointer; font-size: 0.9rem; transition: all 0.2s; min-width: 35px;",
    
    // Badge
    badgeRestantes: "position:absolute; bottom:6px; right:6px; background:#1e40af; color:white; padding:2px 6px; border-radius:12px; font-size:0.75rem; font-weight:700;",
    
    // Tablas
    tableRowHover: "border-bottom: 1px solid #e5e7eb; transition: all 0.2s;",
    tableCellBorder: "padding: 1rem; border-right: 1px solid #e5e7eb;",
    
    // Texto
    textoSinFoto: "font-size: 0.8rem; color: #1e3a8a; font-weight: 600; text-align: center;",
    textoSinUbicaciones: "color: #999; font-size: 0.75rem;"
};

// Exponer ESTILOS_FOTOS globalmente
window.ESTILOS_FOTOS = ESTILOS_FOTOS;

// ============================================================
// FUNCIONES HELPER PARA LÓGICA DE NEGOCIO
// ============================================================

/**
 * Verifica si debe renderizarse el tab de logo basado en tipo de cotización
 * @param {string} tipoCotizacion - Tipo de cotización (P, PL, L, RF, etc)
 * @param {object} logoCotizacion - Datos del logo
 * @returns {boolean}
 */
const debeRenderizarLogoTab = (tipoCotizacion, logoCotizacion) => {
    if (tipoCotizacion === 'P' || !logoCotizacion) return false;
    return !!(
        logoCotizacion.descripcion || 
        (Array.isArray(logoCotizacion.tecnicas) && logoCotizacion.tecnicas.length > 0) || 
        (Array.isArray(logoCotizacion.ubicaciones) && logoCotizacion.ubicaciones.length > 0) || 
        (Array.isArray(logoCotizacion.fotos) && logoCotizacion.fotos.length > 0)
    );
};

/**
 * Filtra cotizaciones basado en criterios de búsqueda
 * @param {array} cotizaciones - Lista de cotizaciones
 * @param {string} filtro - Término de búsqueda
 * @returns {array} Cotizaciones filtradas
 */
const filtrarCotizaciones = (cotizaciones, filtro) => {
    if (!Array.isArray(cotizaciones) || !filtro) return cotizaciones;
    const filtroLower = filtro.toLowerCase();
    return cotizaciones.filter(cot => 
        cot.numero.toLowerCase().includes(filtroLower) ||
        (cot.numero_cotizacion && cot.numero_cotizacion.toLowerCase().includes(filtroLower)) ||
        cot.cliente.toLowerCase().includes(filtroLower)
    );
};

// ============================================================
// MANAGER PARA CANTIDADES DE TALLAS
// ============================================================

/**
 * Manager para guardar y restaurar cantidades de tallas
 * Preserva valores de entrada cuando se re-renderiza el DOM
 */
const CantidadesManager = {
    /**
     * Guarda cantidades actuales de tallas de una prenda
     * @param {number} prendaIndex - Índice de la prenda
     */
    guardar(prendaIndex) {
        if (!window.cantidadesGuardadas) window.cantidadesGuardadas = {};
        
        const prendaContainer = document.querySelector(`.prenda-card-editable[data-prenda-index="${prendaIndex}"]`);
        if (!prendaContainer) return;
        
        const inputsCantidad = prendaContainer.querySelectorAll('.talla-cantidad');
        window.cantidadesGuardadas[prendaIndex] = {};
        
        inputsCantidad.forEach(input => {
            const talla = input.getAttribute('data-talla');
            const cantidad = input.value || '0';
            window.cantidadesGuardadas[prendaIndex][talla] = cantidad;
        });
    },

    /**
     * Restaura cantidades guardadas después de re-renderizar
     * @param {number} prendaIndex - Índice de la prenda
     */
    restaurar(prendaIndex) {
        if (!window.cantidadesGuardadas?.[prendaIndex]) return;
        
        const prendaContainer = document.querySelector(`.prenda-card-editable[data-prenda-index="${prendaIndex}"]`);
        if (!prendaContainer) return;
        
        const cantidadesARestaurar = window.cantidadesGuardadas[prendaIndex];
        for (const talla in cantidadesARestaurar) {
            const input = prendaContainer.querySelector(`.talla-cantidad[data-talla="${talla}"]`);
            if (input) input.value = cantidadesARestaurar[talla];
        }
    },

    /**
     * Limpia todas las cantidades guardadas
     */
    limpiar() {
        window.cantidadesGuardadas = {};
    }
};

// Exponer CantidadesManager globalmente
window.CantidadesManager = CantidadesManager;

// ============================================================
// FUNCIÓN CONSOLIDADA: Eliminar Imágenes
// ============================================================

/**
 * Función genérica para eliminar imágenes de cualquier tipo
 * Soporta: prenda, tela, logo, reflectivo
 * @param {HTMLElement} button - Botón que disparó la acción
 * @param {string} tipo - Tipo de imagen a eliminar
 */
const eliminarImagenGenerico = (button, tipo = 'prenda') => {
    if (typeof modalConfirmarEliminarImagen !== 'function') return;
    
    const tiposConfig = {
        prenda: { label: 'imagen de prenda', selectPrefix: 'data-prenda-index' },
        tela: { label: 'imagen de tela', selectPrefix: 'data-prenda-index' },
        logo: { label: 'imagen del logo', selectPrefix: null },
        reflectivo: { label: 'imagen del reflectivo', selectPrefix: null }
    };
    
    const config = tiposConfig[tipo] || tiposConfig.prenda;
    
    modalConfirmarEliminarImagen(config.label).then((result) => {
        if (!result.isConfirmed) return;
        
        let contenedor, img, fotoUrl, prendaIndex, telaIndex, fotoId;
        
        // Obtener elementos según tipo
        if (tipo === 'reflectivo') {
            const match = button.closest('.reflectivo-foto-item');
            if (!match) return;
            contenedor = match;
            img = contenedor.querySelector('img');
            fotoUrl = img?.getAttribute('src');
            fotoId = contenedor.getAttribute('data-foto-id');
        } else {
            contenedor = button.closest('div[style*="position: relative"]');
            if (!contenedor) return;
            img = contenedor.querySelector('img');
            fotoUrl = img?.getAttribute('src');
            prendaIndex = parseInt(img?.getAttribute('data-prenda-index') || '0');
            if (tipo === 'tela') telaIndex = parseInt(img?.getAttribute('data-tela-index') || '0');
        }
        
        if (!fotoUrl) return;
        window.fotosEliminadas.add(fotoUrl);
        contenedor.remove();
        
        // Procesar y re-renderizar
        const procesarYRender = () => {
            procesarImagenesRestantes(prendaIndex || null, tipo);
            if (window.eliminarImagenTimeout) clearTimeout(window.eliminarImagenTimeout);
            window.eliminarImagenTimeout = setTimeout(() => {
                if (typeof renderizarPrendas === 'function') renderizarPrendas();
                window.eliminarImagenTimeout = null;
            }, 200);
        };
        
        // Eliminar de arrays específicos según tipo
        if (tipo === 'prenda' && window.prendasCargadas?.[prendaIndex]) {
            const prendasFotos = window.prendasCargadas[prendaIndex].fotos;
            if (prendasFotos?.length) {
                const idx = prendasFotos.findIndex(f => {
                    const url = typeof f === 'string' ? f : (f.url || f.ruta_webp || f.ruta_original);
                    return url === fotoUrl;
                });
                if (idx >= 0) prendasFotos.splice(idx, 1);
            }
        } else if (tipo === 'prenda' && window.prendasFotosNuevas?.[prendaIndex]) {
            const idx = window.prendasFotosNuevas[prendaIndex].findIndex(f => {
                const url = f.url || f.preview || f.ruta_webp || f.ruta_original;
                return url === fotoUrl;
            });
            if (idx >= 0) window.prendasFotosNuevas[prendaIndex].splice(idx, 1);
        } else if (tipo === 'tela' && window.telasFotosNuevas?.[prendaIndex]?.[telaIndex]) {
            const idx = window.telasFotosNuevas[prendaIndex][telaIndex].findIndex(f => {
                const url = f.url || f.preview || f.ruta_webp || f.ruta_original;
                return url === fotoUrl;
            });
            if (idx >= 0) window.telasFotosNuevas[prendaIndex][telaIndex].splice(idx, 1);
        } else if (tipo === 'logo' && window.logoFotosNuevas) {
            const idx = window.logoFotosNuevas.findIndex(f => {
                const url = f.url || f.preview || f.ruta_webp || f.ruta_original;
                return url === fotoUrl;
            });
            if (idx >= 0) window.logoFotosNuevas.splice(idx, 1);
        }
        
        procesarYRender();
    });
};

/**
 * Aliases para compatibilidad con onclick inline en HTML
 */
window.eliminarImagenPrenda = (button) => eliminarImagenGenerico(button, 'prenda');
window.eliminarImagenTela = (button) => eliminarImagenGenerico(button, 'tela');
window.eliminarImagenLogo = (button) => eliminarImagenGenerico(button, 'logo');
window.eliminarFotoReflectivoPedido = (fotoId) => {
    const btn = document.querySelector(`.reflectivo-foto-item[data-foto-id="${fotoId}"] button`);
    if (btn) eliminarImagenGenerico(btn, 'reflectivo');
};

// ============================================================
// ALIASES PARA COMPATIBILIDAD
// ============================================================

/**
 * Aliases que usan CantidadesManager directamente
 */
window.guardarCantidadesActuales = (prendaIndex) => CantidadesManager.guardar(prendaIndex);
window.restaurarCantidadesGuardadas = (prendaIndex) => CantidadesManager.restaurar(prendaIndex);
console.log('✅ UTILIDADES: utilidades-crear-pedido.js COMPLETAMENTE CARGADO');
console.log('   - FotoHelper disponible:', typeof window.FotoHelper !== 'undefined');
console.log('   - CantidadesManager disponible:', typeof window.CantidadesManager !== 'undefined');
console.log('   - ESTILOS_FOTOS disponible:', typeof ESTILOS_FOTOS !== 'undefined');
console.log('   - fotosEliminadas inicializado:', window.fotosEliminadas instanceof Set);