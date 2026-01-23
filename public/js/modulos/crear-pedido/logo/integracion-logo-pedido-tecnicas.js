/**
 * INTEGRACIÓN: Logo Pedido - Conexión con Prendas Técnicas
 * 
 * Este script integra el módulo de prendas técnicas (logo-pedido-tecnicas.js)
 * con el módulo existente de logo-pedido.js
 * 
 * Reemplaza el renderizado anterior por el nuevo basado en prendas técnicas
 */

// =========================================================
// 1. INTEGRACIÓN CON RENDERIZAR PRENDAS EDITABLES
// =========================================================

// Interceptar la función original de renderizarPrendasEditables
const originalRenderizarPrendasEditables = window.renderizarPrendasEditables || function() {};

window.renderizarPrendasEditables = function(prendas, logoCotizacion = null, especificacionesCotizacion = null, esReflectivo = false, datosReflectivo = null, esLogo = false, tipoCotizacion = 'P') {
    
    // Si es LOGO, usar el nuevo sistema de prendas técnicas
    if (tipoCotizacion === 'L' && esLogo) {
        
        // Guardar datos globales para uso posterior
        window.currentTipoCotizacion = tipoCotizacion;
        window.currentEsLogo = esLogo;
        
        mostrarSeccionPrendasTecnicasLogoNuevo();
        return;
    }
    
    // Si NO es LOGO, usar el flujo original

    return originalRenderizarPrendasEditables(prendas, logoCotizacion, especificacionesCotizacion, esReflectivo, datosReflectivo, esLogo, tipoCotizacion);
};

// =========================================================
// 2. INTEGRACIÓN CON OBTENER DATOS DE COTIZACIÓN
// =========================================================

// Capturar la función original de obtenerDatosCotizacion
const originalObtenerDatosCotizacion = window.obtenerDatosCotizacion || function() {};

window.obtenerDatosCotizacion = async function(cotizacionId) {
    
    // Llamar a la versión original del servidor
    const resultado = await originalObtenerDatosCotizacion(cotizacionId);
    
    // Si tiene datos de logo, procesarlos con el nuevo sistema
    if (resultado && resultado.logo) {
        
        // Mostrar estructura completa de prendas técnicas
        if (resultado.prendas_tecnicas && resultado.prendas_tecnicas.length > 0) {

            resultado.prendas_tecnicas.forEach((prenda, index) => {
            });
        } else {


        }
        
        // Cargar las prendas técnicas desde la respuesta
        if (resultado.prendas_tecnicas && resultado.prendas_tecnicas.length > 0) {
            cargarLogoPrendasDesdeCotizacion(resultado.prendas_tecnicas);

        } else {

            window.logoPrendasTecnicas = [];
        }
    } else {


    }
    
    return resultado;
};

// =========================================================
// 2. MOSTRAR SECCIÓN DE PRENDAS TÉCNICAS (NUEVO DISEÑO)
// =========================================================

window.mostrarSeccionPrendasTecnicasLogoNuevo = function mostrarSeccionPrendasTecnicasLogoNuevo() {



    if (window.logoPrendasTecnicas && window.logoPrendasTecnicas.length > 0) {
        window.logoPrendasTecnicas.forEach((prenda, i) => {
        });
    }
    
    // Ya no es necesario cambiar el título, ahora es estático en el HTML

    
    // Encontrar el contenedor de prendas
    const prendasContainer = document.getElementById('prendas-container-editable');
    if (!prendasContainer) {

        return;
    }
    
    // Crear estructura HTML para prendas técnicas con NUEVO DISEÑO
    prendasContainer.innerHTML = `
        <!-- Sección de Prendas Técnicas -->
        <div style="margin-top: 2rem;">
            <!-- Contenedor de Tarjetas de Prendas Técnicas -->
            <div id="logo-prendas-tecnicas-container" style="min-height: 200px;">
                <!-- Se llenará dinámicamente con renderizarLogoPrendasTecnicas() -->
            </div>
        </div>
    `;
    
    // Renderizar las prendas técnicas que ya están cargadas


    
    try {
        renderizarLogoPrendasTecnicas();

    } catch (error) {

    }
}

// =========================================================
// 3. RECOPILAR DATOS DEL LOGO PARA ENVÍO
// =========================================================

window.recopilarDatosLogoPedido = function() {
    const datos = {
        prendas_tecnicas: obtenerDatosLogoPrendasParaEnvio(),
        observaciones_generales: document.getElementById('logo_observaciones_generales')?.value || '',
        fotos: (window.logoPrendasTecnicas || []).flatMap(prenda => prenda.fotos || [])
    };
    

    return datos;
};

// =========================================================
// 4. VALIDAR DATOS DEL LOGO
// =========================================================

window.validarLogoPedido = function() {
    // Validar que existan prendas técnicas
    if (!validarLogoPrendasTecnicas()) {

        return false;
    }
    

    return true;
};

// =========================================================
// 5. INICIALIZAR AL CARGAR EL MÓDULO
// =========================================================

document.addEventListener('DOMContentLoaded', function() {
    // Módulo de integración logo-pedido-tecnicas cargado
    
    // Cargar tipos de logo disponibles
    if (typeof cargarTiposLogosDisponibles === 'function') {
        cargarTiposLogosDisponibles();
    }
});

