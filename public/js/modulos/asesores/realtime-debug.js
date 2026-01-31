/**
 * Utilidad para verificar el estado del sistema de pedidos en tiempo real
 * Abre en consola para depuración
 */

// Función global para verificar estado
window.verificarEstadoRealtime = function() {
    if (!window.pedidosRealtimeRefresh) {
        console.error('❌ Sistema de realtime no inicializado');
        return;
    }
    
    const estado = window.pedidosRealtimeRefresh.getStatus();
    
    console.group('🔍 Estado Sistema Pedidos Real-time');
    console.log('📊 Tipo de Conexión:', estado.connectionType);
    console.log('🔄 Estado:', estado.isRunning ? 'Activo' : 'Inactivo');
    console.log('🌐 WebSockets:', estado.usingWebSockets ? '✅ Activo' : '❌ Inactivo');
    console.log('👁️ Página Visible:', estado.isVisible ? '✅' : '❌');
    console.log(' Foco:', estado.hasFocus ? '✅' : '❌');
    console.log('📈 Pedidos Monitoreados:', estado.pedidosCount);
    console.log('⏱️ Intervalo:', estado.checkInterval + 'ms');
    console.log('🕐 Último Cambio:', estado.lastChangeTime);
    console.log('📡 Canal Echo:', estado.echoChannel);
    console.groupEnd();
    
    // Mostrar indicador visual
    const indicator = document.querySelector('.realtime-connection-indicator');
    if (indicator) {
        console.log('📍 Indicador visual:', indicator.textContent, indicator.className);
    }
    
    return estado;
};

// Atajo para consola
window.rt = window.verificarEstadoRealtime;

console.log('✅ Utilidad de depuración de realtime disponible. Usa rt() o verificarEstadoRealtime() en consola');
