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

// Esperar a que el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciarCargaEdicion);
} else {
    // El DOM ya está listo
    iniciarCargaEdicion();
}

function iniciarCargaEdicion() {
    console.log('[EDICIÓN] Inicializando modo de edición...');
    console.log('[EDICIÓN] window.modoEdicion:', window.modoEdicion);
    console.log('[EDICIÓN] window.pedidoEdicionData:', window.pedidoEdicionData ? 'disponible' : 'NO disponible');
    
    if (!window.modoEdicion || !window.pedidoEdicionData) {
        console.log('[EDICIÓN] No en modo edición, saltando carga de datos');
        ocultarLoadingOverlay();
        return;
    }

    console.log('[EDICIÓN] ✓ Modo edición detectado, esperando módulos...');
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
        console.log('[EDICIÓN]  Datos disponibles, cargando...', {
            tieneGestor,
            tieneRenderizador,
            intentos
        });
        cargarDatosEdicion();
        datosEdicionCargados = true;
    } else if (intentos < 50) {
        setTimeout(() => esperarModulosYCargar(intentos + 1), 200);
    } else {
        console.warn('[EDICIÓN]  Timeout esperando módulos. Cargando con módulos disponibles...');
        cargarDatosEdicion();
        datosEdicionCargados = true;
    }
}

function cargarDatosEdicion() {
    try {
        console.log('[EDICIÓN] 🔄 Cargando datos del pedido para edición');
        
        const datos = window.pedidoEdicionData;
        
        if (!datos) {
            console.warn('[EDICIÓN] No hay datos disponibles');
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
            console.log('[EDICIÓN]  Encontradas', datos.pedido.prendas.length, 'prendas');
            cargarPrendas(datos.pedido.prendas);
        }

        // 3. Actualizar título
        actualizarTituloPagina(datos);

        console.log('[EDICIÓN]  Datos cargados correctamente');
        
        // Ocultar overlay después de un pequeño delay para que se vea la transición
        setTimeout(() => ocultarLoadingOverlay(), 300);

    } catch (error) {
        console.error('[EDICIÓN]  Error cargando datos:', error);
        ocultarLoadingOverlay();
    }
}

function cargarInformacionGeneral(datos) {
    console.log('[EDICIÓN]  Cargando información general...');
    
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
                console.log(`[EDICIÓN] ✓ ${nombre}: ${valor}`);
            }
        });

    } catch (error) {
        console.error('[EDICIÓN]  Error cargando información general:', error);
    }
}

function cargarPrendas(prendas) {
    console.log('[EDICIÓN]  Cargando', prendas.length, 'prendas...');
    
    // Asegurar que el gestor está inicializado
    if (!window.gestorPrendaSinCotizacion) {
        console.log('[EDICIÓN]  Inicializando gestor de prendas...');
        if (typeof window.inicializarGestorSinCotizacion === 'function') {
            window.inicializarGestorSinCotizacion();
        } else {
            window.gestorPrendaSinCotizacion = new (window.GestorPrendaSinCotizacion || class {});
        }
    }

    prendas.forEach((prenda, index) => {
        try {
            console.log(`[EDICIÓN] 📌 Agregando prenda ${index + 1}:`, prenda.nombre_prenda || 'Sin nombre');
            
            // Agregar la prenda al gestor
            const prendasIndex = window.gestorPrendaSinCotizacion.agregarPrenda({
                nombre_producto: prenda.nombre_prenda || '',
                descripcion: prenda.descripcion || '',
                genero: prenda.genero || [],
                generosConTallas: prenda.generosConTallas || prenda.cantidad_talla || {},
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

            console.log(`[EDICIÓN] ✓ Prenda ${index + 1} agregada al gestor (índice: ${prendasIndex})`);

        } catch (error) {
            console.error(`[EDICIÓN]  Error procesando prenda ${index + 1}:`, error);
        }
    });

    // Renderizar todas las prendas
    if (typeof window.renderizarPrendasSinCotizacion === 'function') {
        console.log('[EDICIÓN]  Renderizando prendas en la interfaz...');
        window.renderizarPrendasSinCotizacion();
        console.log('[EDICIÓN]  Prendas renderizadas');
    } else if (typeof renderizarPrendasSinCotizacion === 'function') {
        console.log('[EDICIÓN]  Renderizando prendas (función global)...');
        renderizarPrendasSinCotizacion();
        console.log('[EDICIÓN]  Prendas renderizadas');
    } else {
        console.warn('[EDICIÓN]  Función renderizarPrendasSinCotizacion no disponible');
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
        console.log('[EDICIÓN] ✓ Título actualizado:', titulo);
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

console.log(' [EDICIÓN] Módulo de edición cargado y listo');

