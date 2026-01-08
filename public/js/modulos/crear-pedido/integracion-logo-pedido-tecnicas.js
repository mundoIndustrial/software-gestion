/**
 * INTEGRACIÓN: Logo Pedido - Conexión con Prendas Técnicas
 * 
 * Este script integra el módulo de prendas técnicas (logo-pedido-tecnicas.js)
 * con el módulo existente de logo-pedido.js
 * 
 * Reemplaza el renderizado anterior por el nuevo basado en prendas técnicas
 */

console.log('🔗 INTEGRACION-LOGO-PEDIDO-TECNICAS: Iniciando...');

// =========================================================
// 1. INTEGRACIÓN CON RENDERIZAR PRENDAS EDITABLES
// =========================================================

// Interceptar la función original de renderizarPrendasEditables
const originalRenderizarPrendasEditables = window.renderizarPrendasEditables || function() {};

window.renderizarPrendasEditables = function(prendas, logoCotizacion = null, especificacionesCotizacion = null, esReflectivo = false, datosReflectivo = null, esLogo = false, tipoCotizacion = 'P') {
    console.log('🔗 INTEGRACION: renderizarPrendasEditables interceptado');
    console.log('   Tipo de cotización:', tipoCotizacion);
    console.log('   Es Logo:', esLogo);
    console.log('   logoCotizacion:', logoCotizacion);
    
    // Si es LOGO, usar el nuevo sistema de prendas técnicas
    if (tipoCotizacion === 'L' && esLogo) {
        console.log('✅ INTEGRACION: Detectado tipo LOGO, mostrando prendas técnicas en tarjetas');
        
        // Guardar datos globales para uso posterior
        window.currentTipoCotizacion = tipoCotizacion;
        window.currentEsLogo = esLogo;
        
        mostrarSeccionPrendasTecnicasLogoNuevo();
        return;
    }
    
    // Si NO es LOGO, usar el flujo original
    console.log('ℹ️ INTEGRACION: No es LOGO, usando renderizado original');
    return originalRenderizarPrendasEditables(prendas, logoCotizacion, especificacionesCotizacion, esReflectivo, datosReflectivo, esLogo, tipoCotizacion);
};

// =========================================================
// 2. INTEGRACIÓN CON OBTENER DATOS DE COTIZACIÓN
// =========================================================

// Capturar la función original de obtenerDatosCotizacion
const originalObtenerDatosCotizacion = window.obtenerDatosCotizacion || function() {};

window.obtenerDatosCotizacion = async function(cotizacionId) {
    console.log('🔗 INTEGRACION: obtenerDatosCotizacion interceptado para cotización:', cotizacionId);
    
    // Llamar a la versión original del servidor
    const resultado = await originalObtenerDatosCotizacion(cotizacionId);
    
    console.log('🔗 INTEGRACION: Respuesta recibida del servidor:', resultado);
    console.log('   - Tipo:', typeof resultado);
    console.log('   - Keys disponibles:', Object.keys(resultado || {}));
    console.log('   - resultado.logo existe:', !!resultado?.logo);
    console.log('   - resultado.prendas_tecnicas existe:', !!resultado?.prendas_tecnicas);
    
    // Si tiene datos de logo, procesarlos con el nuevo sistema
    if (resultado && resultado.logo) {
        console.log('✅ INTEGRACION: Datos de logo recibidos');
        console.log('   - Logo tipo:', resultado.logo?.tipo);
        console.log('   - Logo ID:', resultado.logo?.id);
        console.log('   - prendas_tecnicas disponibles:', resultado.prendas_tecnicas ? resultado.prendas_tecnicas.length : 0);
        
        // Mostrar estructura completa de prendas técnicas
        if (resultado.prendas_tecnicas && resultado.prendas_tecnicas.length > 0) {
            console.log('📦 DETALLES DE PRENDAS TÉCNICAS:');
            resultado.prendas_tecnicas.forEach((prenda, index) => {
                console.log(`   Prenda ${index}:`, {
                    id: prenda.id,
                    tecnica: prenda.tecnica,
                    talla: prenda.talla,
                    cantidadTallas: prenda.cantidadTallas,
                    ubicaciones: prenda.ubicaciones,
                    fotos: prenda.fotos ? prenda.fotos.length : 0,
                    estructura_completa: prenda
                });
            });
        } else {
            console.log('⚠️ INTEGRACION: prendas_tecnicas está vacío o no existe');
            console.log('   - resultado.prendas_tecnicas:', resultado.prendas_tecnicas);
        }
        
        // Cargar las prendas técnicas desde la respuesta
        if (resultado.prendas_tecnicas && resultado.prendas_tecnicas.length > 0) {
            console.log('✅ INTEGRACION: Cargando prendas técnicas...');
            cargarLogoPrendasDesdeCotizacion(resultado.prendas_tecnicas);
            console.log('✅ INTEGRACION: logoPrendasTecnicas después de cargar:', window.logoPrendasTecnicas);
        } else {
            console.log('⚠️ INTEGRACION: No hay prendas técnicas en la respuesta');
            window.logoPrendasTecnicas = [];
        }
    } else {
        console.log('⚠️ INTEGRACION: No hay datos de logo en la respuesta');
        console.log('   - resultado.logo:', resultado?.logo);
    }
    
    return resultado;
};

// =========================================================
// 2. MOSTRAR SECCIÓN DE PRENDAS TÉCNICAS (NUEVO DISEÑO)
// =========================================================

window.mostrarSeccionPrendasTecnicasLogoNuevo = function mostrarSeccionPrendasTecnicasLogoNuevo() {
    console.log('🎨 INTEGRACION: Mostrando nueva sección de prendas técnicas');
    console.log('📦 Estado actual de logoPrendasTecnicas:', window.logoPrendasTecnicas);
    console.log('   - Cantidad de prendas:', window.logoPrendasTecnicas?.length || 0);
    if (window.logoPrendasTecnicas && window.logoPrendasTecnicas.length > 0) {
        window.logoPrendasTecnicas.forEach((prenda, i) => {
            console.log(`   Prenda ${i}:`, {
                tecnica: prenda.tecnica,
                ubicaciones: prenda.ubicaciones?.length || 0,
                tallas: prenda.tallas?.length || 0,
                fotos: prenda.fotos?.length || 0
            });
        });
    }
    
    // Ya no es necesario cambiar el título, ahora es estático en el HTML
    console.log('✅ Sección de prendas técnicas lista');
    
    // Encontrar el contenedor de prendas
    const prendasContainer = document.getElementById('prendas-container-editable');
    if (!prendasContainer) {
        console.warn('⚠️ INTEGRACION: Contenedor de prendas no encontrado');
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
    console.log('📦 Prendas técnicas para renderizar:', window.logoPrendasTecnicas.length);
    console.log('   - Llamando a renderizarLogoPrendasTecnicas()...');
    
    try {
        renderizarLogoPrendasTecnicas();
        console.log('✅ renderizarLogoPrendasTecnicas() ejecutada correctamente');
    } catch (error) {
        console.error('❌ Error al ejecutar renderizarLogoPrendasTecnicas():', error);
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
    
    console.log('📤 Datos compilados para envío:', datos);
    return datos;
};

// =========================================================
// 4. VALIDAR DATOS DEL LOGO
// =========================================================

window.validarLogoPedido = function() {
    // Validar que existan prendas técnicas
    if (!validarLogoPrendasTecnicas()) {
        console.error('❌ Validación de prendas técnicas fallida');
        return false;
    }
    
    console.log('✅ Logo pedido validado correctamente');
    return true;
};

// =========================================================
// 5. INICIALIZAR AL CARGAR EL MÓDULO
// =========================================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Módulo de integración logo-pedido-tecnicas cargado');
    console.log('   - mostrarSeccionPrendasTecnicasLogoNuevo está disponible:', typeof window.mostrarSeccionPrendasTecnicasLogoNuevo === 'function');
    
    // Cargar tipos de logo disponibles
    if (typeof cargarTiposLogosDisponibles === 'function') {
        cargarTiposLogosDisponibles();
    }
});
