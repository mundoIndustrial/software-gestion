/**
 * ⚠️  ARCHIVO DEPRECADO - VER MÓDULO NUEVO
 * 
 * Este archivo es SOLO REFERENCIA. El código ha sido migrado a estructura modular.
 * 
 * 📦 NUEVA ESTRUCTURA (prenda-tarjeta/):
 * - prenda-tarjeta/loader.js (⭐ USA ESTO - carga automática)
 * - prenda-tarjeta/index.js (función principal)
 * - prenda-tarjeta/secciones.js (secciones expandibles)
 * - prenda-tarjeta/galerias.js (modales de galerías)
 * - prenda-tarjeta/interacciones.js (event listeners)
 * 
 * CARGA EN HTML (NUEVA):
 * 
 *   <link rel="stylesheet" href="/css/componentes/prenda-card-readonly.css">
 *   <script src="/js/componentes/prenda-tarjeta/loader.js"></script>
 * 
 * (Después del script de SweetAlert2)
 * 
 * Para más detalles, ver: public/js/componentes/prenda-tarjeta/README.md
 */

// ============================================
// ⚠️  NOTA: Este es código de referencia
// ============================================
// El código real está en los módulos de prenda-tarjeta/

// ESTRUCTURA DE DATOS DE PRENDA
const ejemploPrenda = {
    id: 1,
    nombre_producto: "Camisa Casual",
    descripcion: "Camisa casual de algodón, perfecta para uso diario",
    origen: "bodega",
    
    // FOTOS (array)
    fotos: [
        "/storage/prendas/foto1.jpg",
        "/storage/prendas/foto2.jpg",
        "/storage/prendas/foto3.jpg"
    ],
    
    // TALLAS POR GÉNERO
    generosConTallas: {
        "dama": {
            "XS": 20,
            "S": 30,
            "M": 25
        },
        "caballero": {
            "S": 15,
            "M": 35,
            "L": 20
        }
    },
    
    // VARIACIONES
    variantes: {
        tela: "Algodón 100%",
        color: "Azul",
        referencia: "CAM-001-BLUE",
        manga: "Larga",
        manga_obs: "Manga con puño elástico",
        broche: "Botones",
        broche_obs: "Botones naturales",
        bolsillos: "Sí",
        bolsillos_obs: "Dos bolsillos frontales",
        botones: "Sí",
        botones_obs: "Botones de concha nacar",
        reflectivo: "No"
    },
    
    // PROCESOS
    procesos: {
        bordado: {
            tipo: "Logo",
            datos: {
                ubicacion: "Pecho izquierdo",
                tamaño: "5cm x 5cm"
            }
        },
        estampado: {
            tipo: "Full Print",
            datos: {
                area: "Espalda completa",
                color: "Rojo"
            }
        }
    }
};

// ============================================
// USO EN CÓDIGO
// ============================================

/**
 * 1. GENERAR Y MOSTRAR UNA TARJETA
 */
function mostrarTarjetaPrenda(prenda, indice) {
    const container = document.getElementById('prendas-container-editable');
    if (!container) {
        console.error('Container no encontrado');
        return;
    }
    
    // Generar HTML
    const html = generarTarjetaPrendaReadOnly(prenda, indice);
    
    // Agregar al container
    container.insertAdjacentHTML('beforeend', html);
}

/**
 * 2. USAR CON EL GESTOR EXISTENTE (recomendado)
 */
function renderizarPrendasEnTarjetas() {
    if (!window.gestorPrendaSinCotizacion) {
        console.error('Gestor no inicializado');
        return;
    }
    
    const container = document.getElementById('prendas-container-editable');
    if (!container) return;
    
    // Limpiar container
    container.innerHTML = '';
    
    // Obtener prendas activas
    const prendas = window.gestorPrendaSinCotizacion.obtenerActivas();
    
    if (prendas.length === 0) {
        container.innerHTML = '<div class="empty-state">No hay prendas agregadas</div>';
        return;
    }
    
    // Renderizar cada prenda como tarjeta
    let html = '';
    prendas.forEach((prenda, indice) => {
        html += generarTarjetaPrendaReadOnly(prenda, indice);
    });
    
    container.innerHTML = html;
}

/**
 * 3. INTEGRACIÓN CON MODAL DE AGREGAR PRENDA
 * 
 * El flujo es:
 * - Click en "Agregar prenda" → Abre modal
 * - Usuario completa datos en el modal
 * - Click en "Guardar" → Agrega prenda al gestor
 * - Renderiza tarjeta READONLY con los datos
 * 
 * NO se debe hacer en el modal:
 * - No crear inputs editables en la tarjeta
 * - No permitir editar datos inline
 * - Solo botón "Editar" abre el modal nuevamente
 */

// ============================================
// EVENTOS MANEJADOS AUTOMÁTICAMENTE
// ============================================

/**
 * CLICK EN EXPANDIBLES
 * - Expande/contrae secciones de Variaciones, Tallas, Procesos
 * - Gira el icono de chevron
 * - Manejo automático: No requiere código adicional
 */

/**
 * CLICK EN FOTO
 * - Abre modal con galería de fotos
 * - Permite navegar con flechas si hay múltiples fotos
 * - Muestra contador "Foto X de Y"
 * - Manejo automático: No requiere código adicional
 */

/**
 * CLICK EN MENÚ (3 PUNTOS)
 * Opciones:
 * 
 * a) EDITAR
 *    - Abre el modal de prenda
 *    - Carga los datos de esa prenda
 *    - Permite modificarlas
 *    - Usa window.gestionItemsUI.cargarItemEnModal()
 * 
 * b) ELIMINAR
 *    - Abre modal de confirmación (SweetAlert2)
 *    - Si confirma: llama a gestorPrendaSinCotizacion.eliminarPrenda()
 *    - Re-renderiza la lista
 */

// ============================================
// FUNCIONES (Ahora en módulos prenda-tarjeta/)
// ============================================

/**
 * generarTarjetaPrendaReadOnly(prenda, indice)
 * → Ubicación: prenda-tarjeta/index.js
 * → Retorna HTML string de la tarjeta
 * 
 * construirSeccionVariaciones(prenda, indice)
 * → Retorna HTML string sección expandible de variaciones
 * 
 * construirSeccionTallas(prenda, indice)
 * → Retorna HTML string sección expandible de tallas
 * 
 * construirSeccionProcesos(prenda, indice)
 * → Retorna HTML string sección expandible de procesos
 * 
 * abrirGaleriaFotosModal(prenda, prendaIndex)
 * → Abre SweetAlert2 modal con galería
 */

// ============================================
// DEPENDENCIAS REQUERIDAS
// ============================================

/**
 * LIBRERÍAS EXTERNAS:
 * - SweetAlert2 (ya en el proyecto)
 * - FontAwesome icons (ya en el proyecto)
 * 
 * MÓDULOS LOCALES ESPERADOS:
 * - window.gestorPrendaSinCotizacion (gestor de prendas)
 * - window.gestionItemsUI (interfaz de gestión de items)
 * - window.renderizarPrendasTipoPrendaSinCotizacion() (función de renderizado)
 */

// ============================================
// PERSONALIZACIÓN
// ============================================

/**
 * Para cambiar estilos:
 * Editar: public/css/componentes/prenda-card-readonly.css
 * 
 * Clases principales:
 * - .prenda-card-readonly (tarjeta completa)
 * - .prenda-card-header (encabezado)
 * - .prenda-card-body (contenido + foto)
 * - .prenda-info-section (información izquierda)
 * - .prenda-foto-section (foto derecha)
 * - .seccion-expandible (secciones expandibles)
 */

// ============================================
// TESTEO
// ============================================

/**
 * Para probar con datos de ejemplo:
 * 
 * 1. En consola del navegador, ejecutar:
 *    mostrarTarjetaPrenda(ejemploPrenda, 0);
 * 
 * 2. Debería aparecer una tarjeta con todos los elementos
 * 
 * 3. Probar interacciones:
 *    - Click en expandibles → expandir/contraer
 *    - Click en foto → abrir galería
 *    - Click en 3 puntos → abrir menú
 *    - Click en Editar → intenta cargar modal
 *    - Click en Eliminar → abre confirmación
 */

console.log('✅ Guía de uso prenda-card-readonly cargada');
