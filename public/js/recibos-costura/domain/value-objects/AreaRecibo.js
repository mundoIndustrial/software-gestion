/**
 * AreaRecibo - Value Object inmutable que representa las áreas de proceso de costura
 *
 * Áreas válidas:
 * - "Costura": Área de costura manual/máquinas
 * - "Corte": Área de corte de telas
 * - "Insumos": Gestión de insumos y material
 * - "Estampado": Área de estampado de prendas
 * - "Bordado": Área de bordado manual
 * - "Control Calidad": Control de calidad final
 *
 * @class AreaRecibo
 */
class AreaRecibo {
    // Constantes de áreas
    static COSTURA = 'Costura';
    static CORTE = 'Corte';
    static INSUMOS = 'Insumos';
    static ESTAMPADO = 'Estampado';
    static BORDADO = 'Bordado';
    static CONTROL_CALIDAD = 'Control Calidad';

    constructor(valor) {
        // Validar que sea un área válida
        const areasValidas = [
            AreaRecibo.COSTURA,
            AreaRecibo.CORTE,
            AreaRecibo.INSUMOS,
            AreaRecibo.ESTAMPADO,
            AreaRecibo.BORDADO,
            AreaRecibo.CONTROL_CALIDAD
        ];

        if (!areasValidas.includes(valor)) {
            throw new Error(
                `Área inválida: "${valor}". Áreas válidas: ${areasValidas.join(', ')}`
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
     * Retorna el valor del área
     */
    toString() {
        return this._value;
    }

    /**
     * Retorna el valor del área (alias)
     */
    getValue() {
        return this._value;
    }

    /**
     * Compara esta área con otra
     */
    equals(otra) {
        if (!(otra instanceof AreaRecibo)) {
            return false;
        }
        return this._value === otra._value;
    }

    /**
     * Retorna la clase Bootstrap para el badge del área
     */
    getColorBadge() {
        const colores = {
            [AreaRecibo.COSTURA]: 'primary',
            [AreaRecibo.CORTE]: 'info',
            [AreaRecibo.INSUMOS]: 'warning',
            [AreaRecibo.ESTAMPADO]: 'danger',
            [AreaRecibo.BORDADO]: 'success',
            [AreaRecibo.CONTROL_CALIDAD]: 'dark'
        };
        return colores[this._value] || 'light';
    }

    /**
     * Retorna el color hexadecimal para gráficos
     */
    getColorHex() {
        const colores = {
            [AreaRecibo.COSTURA]: '#0d6efd',           // blue
            [AreaRecibo.CORTE]: '#0dcaf0',             // cyan
            [AreaRecibo.INSUMOS]: '#ffc107',           // yellow
            [AreaRecibo.ESTAMPADO]: '#dc3545',         // red
            [AreaRecibo.BORDADO]: '#198754',           // green
            [AreaRecibo.CONTROL_CALIDAD]: '#212529'    // dark
        };
        return colores[this._value] || '#ffffff';
    }

    /**
     * Retorna el icono Font Awesome para el área
     */
    getIcon() {
        const iconos = {
            [AreaRecibo.COSTURA]: 'fa-needle',
            [AreaRecibo.CORTE]: 'fa-cut',
            [AreaRecibo.INSUMOS]: 'fa-boxes',
            [AreaRecibo.ESTAMPADO]: 'fa-stamp',
            [AreaRecibo.BORDADO]: 'fa-palette',
            [AreaRecibo.CONTROL_CALIDAD]: 'fa-check-circle'
        };
        return iconos[this._value] || 'fa-circle';
    }

    /**
     * Predicados para verificar el tipo de área
     */
    esCostura() {
        return this._value === AreaRecibo.COSTURA;
    }

    esCorte() {
        return this._value === AreaRecibo.CORTE;
    }

    esInsumos() {
        return this._value === AreaRecibo.INSUMOS;
    }

    esEstampado() {
        return this._value === AreaRecibo.ESTAMPADO;
    }

    esBordado() {
        return this._value === AreaRecibo.BORDADO;
    }

    esControlCalidad() {
        return this._value === AreaRecibo.CONTROL_CALIDAD;
    }

    /**
     * Retorna true si esta área es parte de producción
     */
    esAreaProduccion() {
        return ![AreaRecibo.INSUMOS, AreaRecibo.CONTROL_CALIDAD].includes(this._value);
    }

    /**
     * Factory method: crear desde un valor cualquiera
     */
    static from(valor) {
        if (!valor) {
            throw new Error('El área no puede ser vacía');
        }
        return new AreaRecibo(valor);
    }

    /**
     * Factory method: retorna todas las áreas
     */
    static todas() {
        return [
            new AreaRecibo(AreaRecibo.COSTURA),
            new AreaRecibo(AreaRecibo.CORTE),
            new AreaRecibo(AreaRecibo.INSUMOS),
            new AreaRecibo(AreaRecibo.ESTAMPADO),
            new AreaRecibo(AreaRecibo.BORDADO),
            new AreaRecibo(AreaRecibo.CONTROL_CALIDAD)
        ];
    }

    /**
     * Factory method: retorna solo áreas de producción
     */
    static areasProduccion() {
        return [
            new AreaRecibo(AreaRecibo.COSTURA),
            new AreaRecibo(AreaRecibo.CORTE),
            new AreaRecibo(AreaRecibo.ESTAMPADO),
            new AreaRecibo(AreaRecibo.BORDADO)
        ];
    }

    /**
     * Valida si un valor es un área válida
     */
    static isValida(valor) {
        const areasValidas = [
            AreaRecibo.COSTURA,
            AreaRecibo.CORTE,
            AreaRecibo.INSUMOS,
            AreaRecibo.ESTAMPADO,
            AreaRecibo.BORDADO,
            AreaRecibo.CONTROL_CALIDAD
        ];
        return areasValidas.includes(valor);
    }
}
