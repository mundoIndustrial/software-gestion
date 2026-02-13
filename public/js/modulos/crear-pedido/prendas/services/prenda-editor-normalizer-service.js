/**
 * 🔄 Servicio de Normalización de Datos
 * Responsabilidad: Asegurar que los datos estén en el formato correcto
 */

class PrendaEditorNormalizerService {
    /**
     * Normalizar estructura completa de prenda
     * @static
     * @param {Object} prenda - Prenda a normalizar
     * @returns {Object} Prenda normalizada
     */
    static normalizar(prenda) {
        if (!prenda) {
            return {};
        }

        // Normalizar telas
        prenda.telasAgregadas = this.normalizarTelas(prenda.telasAgregadas, prenda.telas);

        return prenda;
    }

    /**
     * Normalizar array de telas
     * @static
     * @param {Array|Object|number} telasAgregadas - Telas en cualquier formato
     * @param {Array} telasFallback - Fallback si telasAgregadas no es válido
     * @returns {Array} Array de telas
     */
    static normalizarTelas(telasAgregadas, telasFallback = []) {
        // Si ya es un array válido, devolverlo
        if (Array.isArray(telasAgregadas) && telasAgregadas.length > 0) {
            return telasAgregadas;
        }

        // Si es un objeto (pero no array), convertir
        if (telasAgregadas && typeof telasAgregadas === 'object' && !Array.isArray(telasAgregadas)) {
            const convertido = Object.values(telasAgregadas);
            if (convertido.length > 0) {
                console.log('[Normalizer] 🔄 Convertido telas de objeto a array');
                return convertido;
            }
        }

        // Si es un número u otro tipo inválido
        if (telasAgregadas && typeof telasAgregadas !== 'object') {
            console.warn('[Normalizer] ⚠️ telasAgregadas es tipo ' + typeof telasAgregadas);
            // Intentar fallback
            if (Array.isArray(telasFallback) && telasFallback.length > 0) {
                console.log('[Normalizer] 📍 Usando fallback a prenda.telas');
                return telasFallback;
            }
            return [];
        }

        // Fallback:  si nada funciona, usar telasFallback o []
        if (Array.isArray(telasFallback) && telasFallback.length > 0) {
            return telasFallback;
        }

        return [];
    }

    /**
     * Normalizar tallas
     * @static
     * @param {Object} tallasData - Datos de tallas
     * @returns {Object} Tallas normalizadas
     */
    static normalizarTallas(tallasData) {
        if (!tallasData) {
            return { DAMA: {}, CABALLERO: {}, UNISEX: {}, SOBREMEDIDA: {} };
        }

        return tallasData;
    }

    /**
     * Normalizar procesos
     * @static
     * @param {Array|Object} procesos - Procesos en cualquier formato
     * @returns {Array} Array de procesos
     */
    static normalizarProcesos(procesos) {
        if (Array.isArray(procesos)) {
            return procesos;
        }

        if (procesos && typeof procesos === 'object') {
            return Object.entries(procesos)
                .filter(([key, value]) => value !== false && value !== '' && value !== null)
                .map(([nombre, detalles]) => {
                    if (typeof detalles === 'object') {
                        return { nombre, ...detalles };
                    }
                    return { nombre, tipo: nombre };
                });
        }

        return [];
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PrendaEditorNormalizerService;
}
