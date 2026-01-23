/**
 * CARGADOR DE DATOS EN MODO EDICIÓN
 * 
 * Carga los datos del pedido existente en el formulario de creación
 * para permitir edición con la interfaz completa
 * 
 *  NOTA: Este script se carga DESPUÉS de que se incluya crear-pedido-desde-cotizacion.blade.php
 * Por lo que todos los módulos necesarios ya estarán disponibles
 */

let datosEdicionCargados = false;

// Helper: Convertir array de tallas {genero, talla, cantidad} a JSON {GENERO: {talla: cantidad}}
function convertirTallasArrayAJson(tallas) {
    if (!Array.isArray(tallas)) return {};
    
    const resultado = {};
    tallas.forEach(tallaObj => {
        if (tallaObj.genero && tallaObj.talla && tallaObj.cantidad) {
            const genero = tallaObj.genero.toUpperCase();
            if (!resultado[genero]) {
                resultado[genero] = {};
            }
            resultado[genero][tallaObj.talla] = tallaObj.cantidad;
        }
    });
    return resultado;
}

// Esperar a que el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciarCargaEdicion);
} else {
    // El DOM ya está listo
    iniciarCargaEdicion();
}

function iniciarCargaEdicion() {



    
    if (!window.modoEdicion || !window.pedidoEdicionData) {

        ocultarLoadingOverlay();
        return;
    }


    // Esperar a que se carguen todos los módulos necesarios
    esperarModulosYCargar();
}

function esperarModulosYCargar(intentos = 0) {
    // Verificar los módulos esenciales
    const tieneGestor = window.gestorPrendaSinCotizacion && 
                        typeof window.gestorPrendaSinCotizacion.agregarPrenda === 'function';
    
    const tieneRenderizador = typeof window.renderizarPrendasSinCotizacion === 'function' ||
                              typeof renderizarPrendasSinCotizacion === 'function';
    
    const tieneModoCargado = window.modoEdicion && window.pedidoEdicionData;

    if (tieneModoCargado && datosEdicionCargados === false) {
        cargarDatosEdicion();
        datosEdicionCargados = true;
    } else if (intentos < 50) {
        setTimeout(() => esperarModulosYCargar(intentos + 1), 200);
    } else {

        cargarDatosEdicion();
        datosEdicionCargados = true;
    }
}

function cargarDatosEdicion() {
    try {

        
        const datos = window.pedidoEdicionData;
        
        if (!datos) {

            ocultarLoadingOverlay();
            return;
        }

        console.log('[EDICIÓN]  Estructura de datos:', {
            pedido: datos.pedido ? '✓' : '✗',
            estados: datos.estados ? `✓ (${datos.estados.length})` : '✗',
            areas: datos.areas ? `✓ (${datos.areas.length})` : '✗',
        });

        // 1. Cargar información general
        cargarInformacionGeneral(datos);

        // 2. Cargar prendas si existen
        if (datos.pedido && datos.pedido.prendas && datos.pedido.prendas.length > 0) {

            cargarPrendas(datos.pedido.prendas);
        }

        // 3. Actualizar título
        actualizarTituloPagina(datos);


        
        // Ocultar overlay después de un pequeño delay para que se vea la transición
        setTimeout(() => ocultarLoadingOverlay(), 300);

    } catch (error) {

        ocultarLoadingOverlay();
    }
}

function cargarInformacionGeneral(datos) {

    
    try {
        const pedido = datos.pedido;
        if (!pedido) return;

        // Buscar y llenar campos del formulario
        const campos = {
            'cliente': pedido.cliente,
            'forma_de_pago': pedido.forma_de_pago,
            'observaciones': pedido.observaciones,
            'descripcion': pedido.descripcion,
            'novedades': pedido.novedades,
            'estado': pedido.estado,
            'area': pedido.area,
        };

        Object.entries(campos).forEach(([nombre, valor]) => {
            if (!valor) return;

            const selectores = [
                `input[name="${nombre}"]`,
                `select[name="${nombre}"]`,
                `textarea[name="${nombre}"]`,
                `#${nombre}`,
            ];

            const elemento = buscarElemento(selectores);
            if (elemento) {
                elemento.value = valor;
                elemento.dispatchEvent(new Event('change', { bubbles: true }));

            }
        });

    } catch (error) {

    }
}

function cargarPrendas(prendas) {

    
    // Asegurar que el gestor está inicializado
    if (!window.gestorPrendaSinCotizacion) {

        if (typeof window.inicializarGestorSinCotizacion === 'function') {
            window.inicializarGestorSinCotizacion();
        } else {
            window.gestorPrendaSinCotizacion = new (window.GestorPrendaSinCotizacion || class {});
        }
    }

    prendas.forEach((prenda, index) => {
        try {

            
            // Agregar la prenda al gestor
            const prendasIndex = window.gestorPrendaSinCotizacion.agregarPrenda({
                nombre_producto: prenda.nombre_prenda || '',
                descripcion: prenda.descripcion || '',
                genero: prenda.genero || [],
                generosConTallas: prenda.generosConTallas || convertirTallasArrayAJson(prenda.tallas) || {},
                tipo_manga: prenda.tipo_manga || 'No aplica',
                obs_manga: prenda.obs_manga || '',
                tipo_broche: prenda.tipo_broche || 'No aplica',
                obs_broche: prenda.obs_broche || '',
                tiene_bolsillos: prenda.tiene_bolsillos || false,
                obs_bolsillos: prenda.obs_bolsillos || '',
                tiene_reflectivo: prenda.tiene_reflectivo || false,
                obs_reflectivo: prenda.obs_reflectivo || '',
                telas: prenda.telas || [],
                telasAgregadas: prenda.telasAgregadas || [],
                fotos: prenda.fotos || [],
                telaFotos: prenda.telaFotos || [],
                origen: prenda.origen || 'bodega',
                de_bodega: prenda.de_bodega || 1,
                procesos: prenda.procesos || {},
                variaciones: prenda.variaciones || {},
            });



        } catch (error) {

        }
    });

    // Renderizar todas las prendas
    if (typeof window.renderizarPrendasSinCotizacion === 'function') {

        window.renderizarPrendasSinCotizacion();

    } else if (typeof renderizarPrendasSinCotizacion === 'function') {

        renderizarPrendasSinCotizacion();

    } else {

    }
}

function actualizarTituloPagina(datos) {
    const pedido = datos.pedido;
    const titulo = `Editar Pedido #${pedido.numero_pedido || pedido.id}`;
    
    document.title = titulo;
    
    const pageTitle = document.querySelector('[class*="page-title"]') || 
                      document.querySelector('h1.page-title') ||
                      document.querySelector('.section-title');
    
    if (pageTitle) {
        pageTitle.textContent = titulo;

    }
}

function buscarElemento(selectores) {
    if (!Array.isArray(selectores)) {
        selectores = [selectores];
    }
    
    for (const selector of selectores) {
        try {
            const elemento = document.querySelector(selector);
            if (elemento) {
                return elemento;
            }
        } catch (e) {
            // Selector inválido, continuar
        }
    }
    return null;
}

function ocultarLoadingOverlay() {
    const overlay = document.getElementById('page-loading-overlay');
    if (overlay) {
        overlay.style.opacity = '0';
        overlay.style.transition = 'opacity 0.3s ease-out';
        setTimeout(() => {
            overlay.style.display = 'none';
        }, 300);
    }
}




