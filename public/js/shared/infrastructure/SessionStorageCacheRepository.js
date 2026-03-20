/**
 * =====================================================
 * SHARED INFRASTRUCTURE - SESSION STORAGE CACHE
 * =====================================================
 * Implementación del CacheRepository usando sessionStorage.
 * Auto-limpia valores expirados en garbage collection.
 * Fallback a localStorage si sessionStorage unavailable.
 *
 * Dependencias:
 *   - window.CacheRepository (public/js/shared/CacheRepository.js)
 */

class SessionStorageCacheRepository extends CacheRepository {
    constructor(options = {}) {
        super();
        
        this.storage = options.storage || 'session'; // 'session' o 'local'
        this.keyPrefix = options.keyPrefix || 'app_cache_';
        this.maxSize = options.maxSize || 5242880; // 5MB
        this.garbageCollectionInterval = options.garbageCollectionInterval || 300000; // 5 min
        
        this._backend = this._getBackend();
        this._startGarbageCollection();
    }

    _getBackend() {
        try {
            if (this.storage === 'session') {
                window.sessionStorage.setItem('__test__', '1');
                window.sessionStorage.removeItem('__test__');
                return window.sessionStorage;
            } else if (this.storage === 'local') {
                window.localStorage.setItem('__test__', '1');
                window.localStorage.removeItem('__test__');
                return window.localStorage;
            }
        } catch {
            console.warn(`[CacheRepository] ${this.storage}Storage no disponible, usando fallback`);
        }
        
        // Fallback a in-memory si ambos indisponibles
        return new Map();
    }

    _makeKey(key) {
        return `${this.keyPrefix}${key}`;
    }

    _encodeValue(value, ttl) {
        return JSON.stringify({
            value,
            expires: ttl ? Date.now() + ttl : null,
            timestamp: Date.now(),
        });
    }

    _decodeValue(encoded) {
        try {
            const data = JSON.parse(encoded);
            
            // Validar expiración
            if (data.expires && data.expires < Date.now()) {
                return null;
            }
            
            return data.value;
        } catch {
            return null;
        }
    }

    /**
     * Obtiene un valor del cache
     */
    get(key) {
        const storageKey = this._makeKey(key);
        
        try {
            let encoded;
            
            if (this._backend instanceof Map) {
                encoded = this._backend.get(storageKey);
            } else {
                encoded = this._backend.getItem(storageKey);
            }
            
            return encoded ? this._decodeValue(encoded) : null;
        } catch (error) {
            console.error('[CacheRepository] Error en get():', error);
            return null;
        }
    }

    /**
     * Guarda un valor en el cache
     */
    set(key, value, ttl = null) {
        const storageKey = this._makeKey(key);
        const encoded = this._encodeValue(value, ttl);
        
        try {
            // Verificar tamaño aproximado
            if (this._getStorageSize() + encoded.length > this.maxSize) {
                console.warn('[CacheRepository] Cuota superada, limpiando...');
                this._runGarbageCollection();
                
                // Si sigue siendo demasiado grande, no guardar
                if (this._getStorageSize() + encoded.length > this.maxSize) {
                    throw new CacheError('Storage quota exceeded');
                }
            }
            
            if (this._backend instanceof Map) {
                this._backend.set(storageKey, encoded);
            } else {
                this._backend.setItem(storageKey, encoded);
            }
        } catch (error) {
            throw new CacheError(`Error en set(): ${error.message}`, error);
        }
    }

    /**
     * Verifica si una clave existe y no ha expirado
     */
    has(key) {
        return this.get(key) !== null;
    }

    /**
     * Elimina una clave
     */
    remove(key) {
        const storageKey = this._makeKey(key);
        
        try {
            if (this._backend instanceof Map) {
                this._backend.delete(storageKey);
            } else {
                this._backend.removeItem(storageKey);
            }
        } catch (error) {
            throw new CacheError(`Error en remove(): ${error.message}`, error);
        }
    }

    /**
     * Limpia TODO el cache
     */
    clear() {
        try {
            if (this._backend instanceof Map) {
                this._backend.clear();
            } else {
                const keysToRemove = [];
                for (let i = 0; i < this._backend.length; i++) {
                    const key = this._backend.key(i);
                    if (key?.startsWith(this.keyPrefix)) {
                        keysToRemove.push(key);
                    }
                }
                keysToRemove.forEach(key => this._backend.removeItem(key));
            }
        } catch (error) {
            throw new CacheError(`Error en clear(): ${error.message}`, error);
        }
    }

    /**
     * Limpia valores expirados (garbage collection)
     */
    _runGarbageCollection() {
        try {
            const keysToRemove = [];
            
            if (this._backend instanceof Map) {
                for (const [key, encoded] of this._backend.entries()) {
                    if (key.startsWith(this.keyPrefix)) {
                        try {
                            const data = JSON.parse(encoded);
                            if (data.expires && data.expires < Date.now()) {
                                keysToRemove.push(key);
                            }
                        } catch { /* malformed, remove */ keysToRemove.push(key); }
                    }
                }
            } else {
                for (let i = 0; i < this._backend.length; i++) {
                    const key = this._backend.key(i);
                    if (key?.startsWith(this.keyPrefix)) {
                        try {
                            const encoded = this._backend.getItem(key);
                            const data = JSON.parse(encoded);
                            if (data.expires && data.expires < Date.now()) {
                                keysToRemove.push(key);
                            }
                        } catch { keysToRemove.push(key); }
                    }
                }
            }
            
            keysToRemove.forEach(key => {
                if (this._backend instanceof Map) {
                    this._backend.delete(key);
                } else {
                    this._backend.removeItem(key);
                }
            });
        } catch (error) {
            console.error('[CacheRepository] Error en GC:', error);
        }
    }

    /**
     * Calcula el tamaño aproximado del storage
     */
    _getStorageSize() {
        let size = 0;
        
        if (this._backend instanceof Map) {
            for (const [k, v] of this._backend.entries()) {
                size += k.length + v.length;
            }
        } else {
            for (let i = 0; i < this._backend.length; i++) {
                const key = this._backend.key(i);
                const value = this._backend.getItem(key);
                size += (key?.length || 0) + (value?.length || 0);
            }
        }
        
        return size;
    }

    /**
     * Inicia limpieza automática periódica
     */
    _startGarbageCollection() {
        this._gcInterval = setInterval(() => {
            this._runGarbageCollection();
        }, this.garbageCollectionInterval);
    }

    /**
     * Detiene la limpieza automática
     */
    stopGarbageCollection() {
        if (this._gcInterval) {
            clearInterval(this._gcInterval);
        }
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { SessionStorageCacheRepository };
} else {
    window.SessionStorageCacheRepository = SessionStorageCacheRepository;
}
