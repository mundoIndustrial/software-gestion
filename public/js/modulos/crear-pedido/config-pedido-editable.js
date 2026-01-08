/**
 * CONFIGURACIÓN Y CONSTANTES - Crear Pedido Editable
 * 
 * Este archivo contiene todas las constantes, opciones y configuración
 * utilizada en crear-pedido-editable.js
 */

// ============================================================
// OPCIONES POR UBICACIÓN (para Logo)
// ============================================================
const LOGO_OPCIONES_POR_UBICACION = {
    'CAMISA': ['PECHO', 'ESPALDA', 'MANGA IZQUIERDA', 'MANGA DERECHA', 'CUELLO'],
    'JEAN_SUDADERA': ['PIERNA IZQUIERDA', 'PIERNA DERECHA', 'BOLSILLO TRASERO', 'BOLSILLO RELOJERO'],
    'GORRAS': ['FRENTE', 'LATERAL', 'TRASERA']
};

// ============================================================
// TALLAS DISPONIBLES (estándar)
// ============================================================
const TALLAS_ESTANDAR = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];

// ============================================================
// GÉNEROS DISPONIBLES
// ============================================================
const GENEROS_DISPONIBLES = ['Dama', 'Caballero'];

// ============================================================
// TÉCNICAS DE LOGO
// ============================================================
const TECNICAS_DISPONIBLES = [
    'BORDADO',
    'DTF',
    'ESTAMPADO',
    'SUBLIMADO'
];

// ============================================================
// LÍMITES Y CONFIGURACIONES
// ============================================================
const CONFIG = {
    MAX_FOTOS_LOGO: 5,
    MAX_FOTOS_PRENDA: 10,
    MAX_FOTOS_TELA: 8,
    ANIMATION_DURATION: 300, // ms
    DEBOUNCE_DELAY: 500 // ms
};

// ============================================================
// MENSAJES Y TEXTOS
// ============================================================
const MENSAJES = {
    PRENDA_ELIMINAR_CONFIRMAR: '¿Estás seguro de que quieres eliminar esta prenda del pedido?',
    PRENDA_ELIMINADA: 'La prenda ha sido eliminada del pedido',
    VARIACION_ELIMINAR_CONFIRMAR: '¿Estás seguro de que quieres eliminar esta variación?',
    VARIACION_ELIMINADA: 'La variación ha sido eliminada',
    TALLA_ELIMINAR_CONFIRMAR: (talla, numPrenda) => `¿Quitar la talla ${talla} de la prenda ${numPrenda}?`,
    TALLA_ELIMINADA: 'Talla removida correctamente',
    FOTO_LIMITE_ALCANZADO: (max) => `Ya has alcanzado el máximo de ${max} imágenes permitidas`,
    FOTO_LIMITE_EXCEDIDO: (disponible) => `Solo puedes agregar ${disponible} imagen${disponible !== 1 ? 's' : ''} más. Máximo permitido.`,
    UBICACION_REQUERIDA: 'Debes seleccionar una ubicación',
    UBICACION_DUPLICADA: 'Esta ubicación ya existe',
    TECNICA_DUPLICADA: 'Esta técnica ya está agregada',
    CAMPO_VACIO: 'Por favor completa este campo',
    ERROR_GENERICO: 'Ocurrió un error. Por favor intenta de nuevo.'
};

// ============================================================
// ESTILOS COMUNES (reutilizables)
// ============================================================
const ESTILOS = {
    BTN_PRIMARIO: 'background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; padding: 0.75rem 1.5rem;',
    BTN_SECUNDARIO: 'background: #f0f0f0; color: #333; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; padding: 0.75rem 1.5rem;',
    BTN_ELIMINAR: 'background: #dc3545; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; padding: 0.5rem 1rem;',
    BTN_AGREGAR: 'background: #27ae60; color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center; font-weight: bold;',
    INPUT_STANDARD: 'width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.9rem;',
    TEXTAREA_STANDARD: 'width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.9rem; font-family: inherit; min-height: 100px;',
    CARD_STANDARD: 'background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); padding: 1.5rem; margin-bottom: 2rem;'
};

// ============================================================
// TIPOS DE COTIZACIÓN
// ============================================================
const TIPOS_COTIZACION = {
    PRENDA_SIMPLE: 'P',
    PRENDA_LOGO: 'PL',
    LOGO_SOLO: 'L',
    REFLECTIVO: 'RF'
};

// ============================================================
// SELECTORES DE DOM (constantes de IDs)
// ============================================================
const DOM_SELECTORS = {
    // Búsqueda y selección
    searchInput: '#cotizacion_search_editable',
    hiddenInput: '#cotizacion_id_editable',
    dropdown: '#cotizacion_dropdown_editable',
    selectedDiv: '#cotizacion_selected_editable',
    selectedText: '#cotizacion_selected_text_editable',
    
    // Formulario general
    prendasContainer: '#prendas-container-editable',
    clienteInput: '#cliente_editable',
    asesoraInput: '#asesora_editable',
    formaPagoInput: '#forma_de_pago_editable',
    numeroCotizacionInput: '#numero_cotizacion_editable',
    numeroPedidoInput: '#numero_pedido_editable',
    formCrearPedido: '#formCrearPedidoEditable',
    btnSubmit: '#btn-submit',
    
    // Logo
    logoDescripcion: '#logo_descripcion',
    logoObservacionesTecnicas: '#logo_observaciones_tecnicas',
    galeraFotosLogo: '#galeria-fotos-logo',
    tecnicasSeleccionadas: '#tecnicas_seleccionadas_logo',
    selectorTecnicas: '#selector_tecnicas_logo'
};

// Exportar para uso en otros módulos (si usas ES6 modules)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        LOGO_OPCIONES_POR_UBICACION,
        TALLAS_ESTANDAR,
        GENEROS_DISPONIBLES,
        TECNICAS_DISPONIBLES,
        CONFIG,
        MENSAJES,
        ESTILOS,
        TIPOS_COTIZACION,
        DOM_SELECTORS
    };
}
