/**
 * MÓDULO: Gestión de Cotizaciones Tipo Logo (L)
 * 
 * REEMPLAZADO: Ahora usa el nuevo sistema de tarjetas desde logo-pedido-tecnicas.js
 */

/**
 * FUNCIÓN PRINCIPAL: Renderizar cotización tipo Logo
 * Delegada al nuevo sistema de tarjetas
 */
window.renderizarLogoPedido = function(logoCotizacion) {
    renderizarCamposLogo(logoCotizacion);
};

/**
 * FUNCIÓN: Renderizar campos del Logo
 * REEMPLAZADO: Ahora usa el nuevo sistema de tarjetas (logo-pedido-tecnicas.js)
 */
function renderizarCamposLogo(logoCotizacion) {
    console.log('🎨 renderizarCamposLogo(): Delegando al nuevo sistema de tarjetas');
    console.log('📦 Datos logo recibidos:', logoCotizacion);
    
    // Usar el nuevo sistema de renderizado de tarjetas
    window.currentTipoCotizacion = 'L';
    window.currentEsLogo = true;
    window.currentLogoCotizacion = logoCotizacion;
    
    if (typeof mostrarSeccionPrendasTecnicasLogoNuevo === 'function') {
        console.log('✅ Llamando a mostrarSeccionPrendasTecnicasLogoNuevo()...');
        mostrarSeccionPrendasTecnicasLogoNuevo();
    } else {
        console.error('❌ Error: mostrarSeccionPrendasTecnicasLogoNuevo no está disponible');
    }
}

console.log('✅ logo-pedido.js cargado (versión refactorizada - usa logo-pedido-tecnicas.js');
