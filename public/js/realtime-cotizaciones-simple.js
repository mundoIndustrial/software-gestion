/**
 * Real-time updates for quotations - Phase 5: Versión simplificada con WebSocket abstraction
 * Debug/testing module using centralized WebSocket subscription
 */

console.log('[REALTIME-COT-SIMPLE] === ARCHIVO CARGADO (Phase 5) ===');

// Protección contra cargas múltiples
if (window.realtimeCotizacionesLoaded) {
    console.warn('[REALTIME-COT-SIMPLE]   El archivo ya fue cargado');
} else {
    window.realtimeCotizacionesLoaded = true;
    
    console.log('[REALTIME-COT-SIMPLE] Iniciando sistema simplificado...');
    
    function checkAndInitialize() {
        console.log('[REALTIME-COT-SIMPLE] Verificando WebSocket abstraction...');
        
        // Usar window.waitForEcho para esperar a que WebSocket esté disponible
        if (typeof window.waitForEcho === 'function') {
            console.log('[REALTIME-COT-SIMPLE]  waitForEcho disponible');
            window.waitForEcho(() => {
                const ws = window.shared?.websocket;
                if (ws) {
                    console.log('[REALTIME-COT-SIMPLE]  WebSocket abstraction encontrada');
                    try {
                        ws.subscribe('cotizaciones', '.cotizacion.creada', (event) => {
                            console.log('[REALTIME-COT-SIMPLE] Evento recibido:', event);
                        });
                        console.log('[REALTIME-COT-SIMPLE] 🎉 Suscripción exitosa');
                    } catch (e) {
                        console.error('[REALTIME-COT-SIMPLE] Error en suscripción:', e);
                    }
                } else {
                    console.error('[REALTIME-COT-SIMPLE]  WebSocket abstraction no disponible');
                }
            });
        } else {
            console.log('[REALTIME-COT-SIMPLE] waitForEcho no disponible, reintentando...');
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
