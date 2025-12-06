/**
 * Módulo: CotizacionRepository
 * Responsabilidad: Gestionar acceso a datos de cotizaciones
 * Principio DIP: proporciona interfaz clara de acceso a datos
 */
export class CotizacionRepository {
    constructor(cotizacionesData = []) {
        this.cotizaciones = cotizacionesData;
    }

    /**
     * Obtiene todas las cotizaciones
     */
    obtenerTodas() {
        return this.cotizaciones;
    }

    /**
     * Filtra cotizaciones por asesor
     */
    filtrarPorAsesor(nombreAsesor) {
        return this.cotizaciones.filter(cot => cot.asesora === nombreAsesor);
    }

    /**
     * Busca por término (número o cliente)
     */
    buscar(termino) {
        if (!termino) return this.cotizaciones;

        const terminoLower = termino.toLowerCase();
        return this.cotizaciones.filter(cot =>
            cot.numero.toLowerCase().includes(terminoLower) ||
            cot.cliente.toLowerCase().includes(terminoLower)
        );
    }

    /**
     * Obtiene cotización por ID
     */
    obtenerPorId(id) {
        return this.cotizaciones.find(cot => cot.id === id);
    }
}
