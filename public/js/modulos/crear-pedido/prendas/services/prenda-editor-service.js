/**
 *  Módulo de Servicio - Comunicación con Servidor
 * Responsabilidad: Obtener datos del servidor de forma segura
 */

class PrendaEditorService {
    /**
     * Obtener prenda completa del servidor
     */
    static async obtenerDelServidor(prendaId, pedidoId) {
        // Validar parámetros
        if (!pedidoId || !prendaId) {
            console.warn('[ Service] Parámetros inválidos:', { prendaId, pedidoId });
            return null;
        }
        
        try {
            const endpoint = `/pedidos-public/${pedidoId}/factura-datos`;
            
            console.log('[ Service]  Obteniendo desde:', endpoint);
            
            const response = await fetch(endpoint, {
                headers: { 'Accept': 'application/json' }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const result = await response.json();
            
            if (!result.success || !result.data || !result.data.prendas) {
                throw new Error('Respuesta inválida');
            }
            
            // Buscar prenda específica
            const prenda = result.data.prendas.find(p => 
                p.id === prendaId || p.prenda_pedido_id === prendaId
            );
            
            if (!prenda) {
                console.warn('[ Service] Prenda no encontrada en servidor');
                return null;
            }
            
            console.log(' [Service] Prenda obtenida del servidor');
            return prenda;
            
        } catch (error) {
            console.error('[ Service] Error:', error.message);
            return null;
        }
    }

    /**
     * Determinar si debe obtener del servidor o usar datos locales
     */
    static debeObtenerDelServidor(prenda) {
        // Si ya se trajo de BD (ej: adapter de pedidos), no re-fetch
        if (prenda._fromDB) {
            console.log('[ Service] Datos ya vienen de BD (_fromDB=true), skip fetch');
            return false;
        }

        const pedidoId = prenda.pedido_id || prenda.pedidoId;
        const prendaId = prenda.id || prenda.prenda_pedido_id;
        
        // Solo si AMBOS IDs existen
        return !!(pedidoId && prendaId);
    }

    /**
     * Obtener prenda con fallback a datos locales
     */
    static async obtenerConFallback(prenda) {
        if (this.debeObtenerDelServidor(prenda)) {
            const pedidoId = prenda.pedido_id || prenda.pedidoId;
            const prendaId = prenda.id || prenda.prenda_pedido_id;
            
            console.log('[ Service] Intentando obtener del servidor...');
            const prendaCompleta = await this.obtenerDelServidor(prendaId, pedidoId);
            
            if (prendaCompleta) {
                return prendaCompleta;
            }
            
            console.log('[ Service] Fallback a datos locales');
        } else {
            console.log('[ Service] Usando datos locales (pedido nuevo/sin guardar)');
        }
        
        return prenda;
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PrendaEditorService;
}
