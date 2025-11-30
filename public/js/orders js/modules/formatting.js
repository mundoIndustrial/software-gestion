/**
 * MÓDULO: formatting.js
 * Responsabilidad: Formateo de datos (fechas, valores, etc.)
 * Principios SOLID: SRP (Single Responsibility)
 */

const COLUMNAS_FECHA = [
    'fecha_de_creacion_de_orden', 'fecha_estimada_de_entrega', 'inventario', 
    'insumos_y_telas', 'corte', 'bordado', 'estampado', 'costura', 'reflectivo', 
    'lavanderia', 'arreglos', 'marras', 'control_de_calidad', 'entrega', 'despacho'
];

const FormattingModule = {
    /**
     * Formatear fecha a DD/MM/YYYY
     */
    formatearFecha(fecha, columna = 'desconocida') {
        console.log(`[formatting] Entrada: "${fecha}" (tipo: ${typeof fecha}, columna: ${columna})`);
        
        if (!fecha) return fecha;
        
        // Si es Date object, convertir a string YYYY-MM-DD
        if (fecha instanceof Date) {
            const year = fecha.getFullYear();
            const month = String(fecha.getMonth() + 1).padStart(2, '0');
            const day = String(fecha.getDate()).padStart(2, '0');
            fecha = `${year}-${month}-${day}`;
        }
        
        if (typeof fecha !== 'string') return fecha;
        
        // Si ya está en DD/MM/YYYY, devolver
        if (fecha.match(/^\d{2}\/\d{2}\/\d{4}$/)) {
            return fecha;
        }
        
        // Si está en YYYY-MM-DD, convertir
        if (fecha.match(/^\d{4}-\d{2}-\d{2}$/)) {
            const partes = fecha.split('-');
            return partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0]}` : fecha;
        }
        
        return fecha;
    },

    /**
     * Verificar si una columna es de fecha
     */
    esColumnaFecha(column) {
        return COLUMNAS_FECHA.includes(column);
    },

    /**
     * Asegurar formato correcto de fecha
     */
    asegurarFormatoFecha(fecha) {
        if (!fecha || typeof fecha !== 'string') return fecha;
        
        if (fecha.match(/^\d{2}\/\d{2}\/\d{4}$/)) {
            return fecha;
        }
        
        return this.formatearFecha(fecha);
    }
};

// Exponer módulo globalmente
window.FormattingModule = FormattingModule;
globalThis.FormattingModule = FormattingModule;

