/**
 * SISTEMA DE REFRESH AUTOMÁTICO DEL TOKEN CSRF
 * 
 * Previene el error 419 (CSRF token mismatch) cuando el usuario deja
 * el formulario abierto por mucho tiempo.
 * 
 * Funcionamiento:
 * - Refresca el token cada 30 minutos (sin recargar la página)
 * - Muestra notificación cuando la sesión está por expirar
 * - Mantiene la sesión activa mientras el usuario trabaja
 */

(function() {
    'use strict';
    
    // ============ CONFIGURACIÓN ============
    const CONFIG = {
        REFRESH_INTERVAL: 30 * 60 * 1000,      // 30 minutos
        SESSION_LIFETIME: 120 * 60 * 1000,     // 120 minutos (2 horas)
        WARNING_BEFORE_EXPIRY: 10 * 60 * 1000, // Advertir 10 minutos antes
        REFRESH_URL: '/refresh-csrf',
        DEBUG: false  // Cambiar a true para ver logs en consola
    };
    
    let lastActivityTime = Date.now();
    let refreshTimer = null;
    let warningTimer = null;
    let warningShown = false;
    
    // ============ FUNCIONES PRINCIPALES ============
    
    /**
     * Actualiza el token CSRF sin recargar la página
     */
    async function refreshCsrfToken() {
        try {
            if (CONFIG.DEBUG) {

            }
            
            const response = await fetch(CONFIG.REFRESH_URL, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.token) {
                // Actualizar el meta tag
                const metaTag = document.querySelector('meta[name="csrf-token"]');
                if (metaTag) {
                    metaTag.setAttribute('content', data.token);
                    
                    if (CONFIG.DEBUG) {
                        console.log(' Token CSRF actualizado:', {
                            timestamp: data.timestamp,
                            token_preview: data.token.substring(0, 10) + '...'
                        });
                    }
                    
                    // Actualizar también los inputs hidden @csrf si existen
                    const csrfInputs = document.querySelectorAll('input[name="_token"]');
                    csrfInputs.forEach(input => {
                        input.value = data.token;
                    });
                    
                    // Resetear el tiempo de última actividad
                    lastActivityTime = Date.now();
                    warningShown = false;
                    
                    // NO mostrar notificación para no molestar al usuario
                    // El refresh es silencioso
                    
                    return true;
                } else {

                    return false;
                }
            } else {
                throw new Error('Token no recibido en la respuesta');
            }
            
        } catch (error) {

            
            // Si falla, NO mostrar advertencia para no interrumpir al usuario
            // Solo loguear el error en consola
            
            return false;
        }
    }
    
    /**
     * Muestra advertencia cuando la sesión está por expirar
     * DESACTIVADA: El token se refresca automáticamente cada 30 minutos,
     * por lo que la sesión nunca debería expirar
     */
    function showExpiryWarning() {
        // Advertencia desactivada - no es necesaria
        return;
    }
    
    /**
     * Reinicia los timers de refresh y advertencia
     */
    function resetTimers() {
        // Limpiar timers existentes
        if (refreshTimer) clearInterval(refreshTimer);
        if (warningTimer) clearTimeout(warningTimer);
        
        // Timer de refresh periódico (cada 30 minutos)
        refreshTimer = setInterval(() => {
            refreshCsrfToken();
        }, CONFIG.REFRESH_INTERVAL);
        
        // Timer de advertencia (10 minutos antes de expirar)
        const warningTime = CONFIG.SESSION_LIFETIME - CONFIG.WARNING_BEFORE_EXPIRY;
        warningTimer = setTimeout(() => {
            showExpiryWarning();
        }, warningTime);
        
        if (CONFIG.DEBUG) {
        }
    }
    
    /**
     * Detecta actividad del usuario para resetear sesión
     */
    function trackUserActivity() {
        const events = ['mousedown', 'keypress', 'scroll', 'touchstart'];
        
        events.forEach(event => {
            document.addEventListener(event, () => {
                const now = Date.now();
                const timeSinceLastActivity = now - lastActivityTime;
                
                // Si pasaron más de 5 minutos desde la última actividad, refrescar
                if (timeSinceLastActivity > 5 * 60 * 1000) {
                    if (CONFIG.DEBUG) {

                    }
                    refreshCsrfToken();
                }
                
                lastActivityTime = now;
            }, { passive: true });
        });
    }
    
    // ============ INICIALIZACIÓN ============
    
    /**
     * Inicia el sistema de refresh automático
     */
    function init() {
        // Verificar que exista el meta tag
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (!metaTag) {

            return;
        }
        
        // Iniciar timers
        resetTimers();
        
        // Rastrear actividad del usuario
        trackUserActivity();
        
        // Hacer un refresh inmediato después de 1 minuto (para verificar que funciona)
        setTimeout(() => {
            if (CONFIG.DEBUG) {

            }
            refreshCsrfToken();
        }, 60000); // 1 minuto
    }
    
    // Iniciar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Exponer función global para refresh manual
    window.refreshCsrfToken = refreshCsrfToken;
    
})();

