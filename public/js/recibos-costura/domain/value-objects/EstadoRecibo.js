/**
 * EstadoRecibo - Value Object inmutable que representa los estados válidos de un recibo de costura
 *
 * Estados válidos:
 * - "PENDIENTE_INSUMOS": Esperando insumos para iniciar ejecución
 * - "En Ejecución": En proceso de costura
 * - "No iniciado": No ha comenzado el proceso
 *
 * @class EstadoRecibo
 */
class EstadoRecibo {
    // Constantes de estados
    static PENDIENTE_INSUMOS = 'PENDIENTE_INSUMOS';
    static EN_EJECUCION = 'En Ejecución';
    static NO_INICIADO = 'No iniciado';

    constructor(valor) {
        // Validar que sea un estado válido
        const estadosValidos = [
            EstadoRecibo.PENDIENTE_INSUMOS,
            EstadoRecibo.EN_EJECUCION,
            EstadoRecibo.NO_INICIADO
        ];

        if (!estadosValidos.includes(valor)) {
            throw new Error(
                `Estado inválido: "${valor}". Estados válidos: ${estadosValidos.join(', ')}`
            );
        }

        // Hacer la propiedad inmutable
        Object.defineProperty(this, '_value', {
            value: valor,
            writable: false,
            configurable: false,
            enumerable: false
        });
    }

    /**
     * Retorna el valor del estado
     */
    toString() {
        return this._value;
    }

    /**
     * Retorna el valor del estado (alias)
     */
    getValue() {
        return this._value;
    }

    /**
     * Compara este estado con otro
     */
    equals(otro) {
        if (!(otro instanceof EstadoRecibo)) {
            return false;
        }
        return this._value === otro._value;
    }

    /**
     * Retorna la clase Bootstrap para el badge del estado
     */
    getColorBadge() {
        const colores = {
            [EstadoRecibo.PENDIENTE_INSUMOS]: 'secondary',
            [EstadoRecibo.EN_EJECUCION]: 'info',
            [EstadoRecibo.NO_INICIADO]: 'warning'
        };
        return colores[this._value] || 'light';
    }

    /**
     * Retorna el color hexadecimal para gráficos
     */
    getColorHex() {
        const colores = {
            [EstadoRecibo.PENDIENTE_INSUMOS]: '#6c757d', // gray
            [EstadoRecibo.EN_EJECUCION]: '#0dcaf0',      // cyan
            [EstadoRecibo.NO_INICIADO]: '#ffc107'        // yellow
        };
        return colores[this._value] || '#ffffff';
    }

    /**
     * Retorna el icono Font Awesome para el estado
     */
    getIcon() {
        const iconos = {
            [EstadoRecibo.PENDIENTE_INSUMOS]: 'fa-hourglass-half',
            [EstadoRecibo.EN_EJECUCION]: 'fa-spinner',
            [EstadoRecibo.NO_INICIADO]: 'fa-exclamation-circle'
        };
        return iconos[this._value] || 'fa-circle';
    }

    /**
     * Predicados para verificar el tipo de estado
     */
    pendienteInsumos() {
        return this._value === EstadoRecibo.PENDIENTE_INSUMOS;
    }

    enEjecucion() {
        return this._value === EstadoRecibo.EN_EJECUCION;
    }

    noIniciado() {
        return this._value === EstadoRecibo.NO_INICIADO;
    }

    /**
     * Factory method: crear desde un valor cualquiera
     * Lanza error si el valor es inválido
     */
    static from(valor) {
        if (!valor) {
            throw new Error('El estado no puede ser vacío');
        }
        return new EstadoRecibo(valor);
    }

    /**
     * Factory method: retorna todos los estados válidos
     */
    static todos() {
        return [
            new EstadoRecibo(EstadoRecibo.PENDIENTE_INSUMOS),
            new EstadoRecibo(EstadoRecibo.EN_EJECUCION),
            new EstadoRecibo(EstadoRecibo.NO_INICIADO)
        ];
    }

    /**
     * Valida si un valor es un estado válido
     */
    static isValido(valor) {
        const estadosValidos = [
            EstadoRecibo.PENDIENTE_INSUMOS,
            EstadoRecibo.EN_EJECUCION,
            EstadoRecibo.NO_INICIADO
        ];
        return estadosValidos.includes(valor);
    }
}
