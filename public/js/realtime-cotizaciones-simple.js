/**
 * Real-time updates for quotations - Versión simplificada para debugging
 */

console.log('[REALTIME-COT-SIMPLE] === ARCHIVO CARGADO ===');

// Protección contra cargas múltiples
if (window.realtimeCotizacionesLoaded) {
    console.warn('[REALTIME-COT-SIMPLE] ⚠️  El archivo ya fue cargado');
} else {
    window.realtimeCotizacionesLoaded = true;
    
    console.log('[REALTIME-COT-SIMPLE] Iniciando sistema simplificado...');
    
    function checkAndInitialize() {
        console.log('[REALTIME-COT-SIMPLE] Verificando Echo...');
        console.log('[REALTIME-COT-SIMPLE] typeof window.Echo:', typeof window.Echo);
        console.log('[REALTIME-COT-SIMPLE] window.Echo:', window.Echo);
        
        if (typeof window.Echo !== 'undefined' && window.Echo) {
            console.log('[REALTIME-COT-SIMPLE] ✅ Echo encontrado');
            console.log('[REALTIME-COT-SIMPLE] typeof window.Echo.channel:', typeof window.Echo?.channel);
            
            if (typeof window.Echo.channel === 'function') {
                console.log('[REALTIME-COT-SIMPLE] ✅ Echo.channel es función, intentando suscribir...');
                try {
                    window.Echo.channel('cotizaciones')
                        .listen('.cotizacion.creada', (event) => {
                            console.log('[REALTIME-COT-SIMPLE] Evento recibido:', event);
                        });
                    console.log('[REALTIME-COT-SIMPLE] 🎉 Suscripción exitosa');
                } catch (e) {
                    console.error('[REALTIME-COT-SIMPLE] Error en suscripción:', e);
                }
            } else {
                console.error('[REALTIME-COT-SIMPLE] ❌ Echo.channel no es función');
                
                // Buscar alternativas
                console.log('[REALTIME-COT-SIMPLE] Buscando alternativas...');
                console.log('[REALTIME-COT-SIMPLE] window.EchoInstance:', typeof window.EchoInstance);
                console.log('[REALTIME-COT-SIMPLE] window.EchoInstance:', window.EchoInstance);
                
                if (window.EchoInstance && typeof window.EchoInstance.channel === 'function') {
                    console.log('[REALTIME-COT-SIMPLE] ✅ Usando EchoInstance');
                    window.Echo = window.EchoInstance;
                }
            }
        } else {
            console.log('[REALTIME-COT-SIMPLE] Echo no disponible, reintentando...');
            setTimeout(checkAndInitialize, 1000);
        }
    }
    
    // Iniciar
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', checkAndInitialize);
    } else {
        checkAndInitialize();
    }
}
