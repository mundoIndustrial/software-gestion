/**
 * DiasTranscurridos - Value Object inmutable que representa días transcurridos con lógica de rangos
 *
 * Rangos de color:
 * - Verde: 0-4 días (reciente)
 * - Amarillo: 5-13 días (normal)
 * - Rojo: 14+ días (retrasado)
 *
 * @class DiasTranscurridos
 */
class DiasTranscurridos {
    // Constantes de rangos
    static RANGO_VERDE = { min: 0, max: 4, nombre: 'verde' };
    static RANGO_AMARILLO = { min: 5, max: 13, nombre: 'amarillo' };
    static RANGO_ROJO = { min: 14, max: Infinity, nombre: 'rojo' };

    constructor(numero) {
        // Validar que sea un número no negativo entero
        if (!Number.isInteger(numero) || numero < 0) {
            throw new Error(`Los días deben ser un número entero no negativo. Recibido: ${numero}`);
        }

        // Hacer la propiedad inmutable
        Object.defineProperty(this, '_value', {
            value: numero,
            writable: false,
            configurable: false,
            enumerable: false
        });
    }

    /**
     * Retorna el valor numérico de los días
     */
    toNumber() {
        return this._value;
    }

    /**
     * Retorna una representación legible
     */
    toString() {
        return `${this._value} día${this._value === 1 ? '' : 's'}`;
    }

    /**
     * Compara estos días con otro
     */
    equals(otros) {
        if (!(otros instanceof DiasTranscurridos)) {
            return false;
        }
        return this._value === otros._value;
    }

    /**
     * Retorna el rango al que pertenecen estos días
     * Retorna: 'verde', 'amarillo' o 'rojo'
     */
    getRango() {
        if (this._value >= DiasTranscurridos.RANGO_ROJO.min) {
            return DiasTranscurridos.RANGO_ROJO.nombre;
        }
        if (this._value >= DiasTranscurridos.RANGO_AMARILLO.min) {
            return DiasTranscurridos.RANGO_AMARILLO.nombre;
        }
        return DiasTranscurridos.RANGO_VERDE.nombre;
    }

    /**
     * Retorna la clase Bootstrap para el badge
     */
    getColorBadge() {
        const rango = this.getRango();
        const colores = {
            'verde': 'success',
            'amarillo': 'warning',
            'rojo': 'danger'
        };
        return colores[rango] || 'light';
    }

    /**
     * Retorna el color hexadecimal para gráficos
     */
    getColorHex() {
        const rango = this.getRango();
        const colores = {
            'verde': '#198754',   // green
            'amarillo': '#ffc107', // yellow
            'rojo': '#dc3545'      // red
        };
        return colores[rango] || '#ffffff';
    }

    /**
     * Retorna el icono Font Awesome según el rango
     */
    getIcon() {
        const rango = this.getRango();
        const iconos = {
            'verde': 'fa-check-circle',
            'amarillo': 'fa-clock',
            'rojo': 'fa-exclamation-circle'
        };
        return iconos[rango] || 'fa-circle';
    }

    /**
     * Predicados para verificar el rango
     */
    esReciente() {
        return this.getRango() === 'verde';
    }

    esNormal() {
        return this.getRango() === 'amarillo';
    }

    esRetrasado() {
        return this.getRango() === 'rojo';
    }

    /**
     * Factory method: crear desde un número
     */
    static from(numero) {
        if (numero === null || numero === undefined) {
            throw new Error('El número de días no puede ser nulo');
        }
        return new DiasTranscurridos(numero);
    }

    /**
     * Factory method: crear desde dos fechas
     * Calcula la diferencia en días entre dos fechas
     */
    static fromFechas(fechaInicio, fechaFin) {
        if (!fechaInicio || !fechaFin) {
            throw new Error('Las fechas no pueden ser nulas');
        }

        const inicio = new Date(fechaInicio);
        const fin = new Date(fechaFin);

        if (isNaN(inicio.getTime()) || isNaN(fin.getTime())) {
            throw new Error('Las fechas no son válidas');
        }

        const tiempoTranscurrido = fin.getTime() - inicio.getTime();
        const diasTranscurridos = Math.ceil(tiempoTranscurrido / (1000 * 60 * 60 * 24));

        // Asegurar que el número es no negativo
        const resultado = Math.max(0, diasTranscurridos);

        return new DiasTranscurridos(resultado);
    }

    /**
     * Factory method: crear valor cero
     */
    static cero() {
        return new DiasTranscurridos(0);
    }

    /**
     * Retorna los límites de rango para referencia
     */
    static getRangos() {
        return {
            verde: DiasTranscurridos.RANGO_VERDE,
            amarillo: DiasTranscurridos.RANGO_AMARILLO,
            rojo: DiasTranscurridos.RANGO_ROJO
        };
    }
}
