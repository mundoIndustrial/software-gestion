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
                console.log('🔄 Refrescando token CSRF...');
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
                        console.log('✅ Token CSRF actualizado:', {
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
                    console.error('❌ Meta tag csrf-token no encontrado');
                    return false;
                }
            } else {
                throw new Error('Token no recibido en la respuesta');
            }
            
        } catch (error) {
            console.error('❌ Error al refrescar token CSRF:', error);
            
            // Si falla, NO mostrar advertencia para no interrumpir al usuario
            // Solo loguear el error en consola
            
            return false;
        }
    }
    
    /**
     * Muestra advertencia cuando la sesión está por expirar
     */
    function showExpiryWarning() {
        if (warningShown) return;
        
        warningShown = true;
        
        const timeRemaining = Math.ceil(CONFIG.WARNING_BEFORE_EXPIRY / 60000); // minutos
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '⚠️ Sesión por expirar',
                html: `Tu sesión expirará en <strong>${timeRemaining} minutos</strong>.<br>
                       <small style="color: #666;">Guarda tu trabajo o haz clic en cualquier parte para extender la sesión.</small>`,
                icon: 'warning',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#1e40af',
                allowOutsideClick: false
            }).then(() => {
                // Al cerrar el modal, refrescar inmediatamente
                refreshCsrfToken();
                resetTimers();
            });
        } else {
            console.warn('⚠️ ADVERTENCIA: Tu sesión expirará pronto. Guarda tu trabajo.');
        }
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
            console.log('⏰ Timers reiniciados:', {
                refresh_cada: `${CONFIG.REFRESH_INTERVAL / 60000} minutos`,
                advertencia_en: `${warningTime / 60000} minutos`
            });
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
                        console.log('👆 Actividad detectada - Refrescando token...');
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
            console.error('❌ Sistema de refresh CSRF no iniciado: meta tag no encontrado');
            return;
        }
        
        console.log('🔐 Sistema de refresh CSRF iniciado');
        console.log('   ⏰ Refresh automático cada:', CONFIG.REFRESH_INTERVAL / 60000, 'minutos');
        console.log('   ⚠️  Advertencia de expiración:', CONFIG.WARNING_BEFORE_EXPIRY / 60000, 'minutos antes');
        
        // Iniciar timers
        resetTimers();
        
        // Rastrear actividad del usuario
        trackUserActivity();
        
        // Hacer un refresh inmediato después de 1 minuto (para verificar que funciona)
        setTimeout(() => {
            if (CONFIG.DEBUG) {
                console.log('🔄 Ejecutando primer refresh de verificación...');
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
