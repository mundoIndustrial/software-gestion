/**
 * Módulo: CotizacionDataLoader
 * Responsabilidad: Cargar datos de cotización desde servidor
 * Principio SRP: solo responsable de cargar datos
 */
export class CotizacionDataLoader {
    /**
     * Carga datos completos de una cotización
     */
    static async cargar(cotizacionId, options = {}) {
        try {
            const url = `/asesores/cotizaciones/${cotizacionId}`;
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Error al cargar cotización:', error);
            throw error;
        }
    }

    /**
     * Carga próximo número de pedido
     */
    static async cargarProximoNumero() {
        try {
            const response = await fetch('/asesores/next-pedido', {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Error al cargar próximo número:', error);
            throw error;
        }
    }
}
