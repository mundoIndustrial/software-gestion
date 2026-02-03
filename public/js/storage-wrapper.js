/**
 * Storage Wrapper - Protege acceso a localStorage/sessionStorage
 * Evita errores de "Access to storage is not allowed from this context"
 */

// Crear wrappers seguros para localStorage
const SafeStorage = {
    setItem: function(key, value) {
        try {
            localStorage.setItem(key, value);
        } catch (e) {
            if (e.name === 'SecurityError' || e.name === 'QuotaExceededError') {
                console.warn('[SafeStorage] No se puede acceder a localStorage:', e.message);
            } else {
                throw e;
            }
        }
    },
    
    getItem: function(key) {
        try {
            return localStorage.getItem(key);
        } catch (e) {
            if (e.name === 'SecurityError') {
                console.warn('[SafeStorage] No se puede acceder a localStorage:', e.message);
                return null;
            } else {
                throw e;
            }
        }
    },
    
    removeItem: function(key) {
        try {
            localStorage.removeItem(key);
        } catch (e) {
            if (e.name === 'SecurityError') {
                console.warn('[SafeStorage] No se puede acceder a localStorage:', e.message);
            } else {
                throw e;
            }
        }
    },
    
    clear: function() {
        try {
            localStorage.clear();
        } catch (e) {
            if (e.name === 'SecurityError') {
                console.warn('[SafeStorage] No se puede acceder a localStorage:', e.message);
            } else {
                throw e;
            }
        }
    }
};

// Crear wrappers seguros para sessionStorage
const SafeSessionStorage = {
    setItem: function(key, value) {
        try {
            sessionStorage.setItem(key, value);
        } catch (e) {
            if (e.name === 'SecurityError' || e.name === 'QuotaExceededError') {
                console.warn('[SafeSessionStorage] No se puede acceder a sessionStorage:', e.message);
            } else {
                throw e;
            }
        }
    },
    
    getItem: function(key) {
        try {
            return sessionStorage.getItem(key);
        } catch (e) {
            if (e.name === 'SecurityError') {
                console.warn('[SafeSessionStorage] No se puede acceder a sessionStorage:', e.message);
                return null;
            } else {
                throw e;
            }
        }
    },
    
    removeItem: function(key) {
        try {
            sessionStorage.removeItem(key);
        } catch (e) {
            if (e.name === 'SecurityError') {
                console.warn('[SafeSessionStorage] No se puede acceder a sessionStorage:', e.message);
            } else {
                throw e;
            }
        }
    },
    
    clear: function() {
        try {
            sessionStorage.clear();
        } catch (e) {
            if (e.name === 'SecurityError') {
                console.warn('[SafeSessionStorage] No se puede acceder a sessionStorage:', e.message);
            } else {
                throw e;
            }
        }
    }
};

// Exportar globalmente
window.SafeStorage = SafeStorage;
window.SafeSessionStorage = SafeSessionStorage;

