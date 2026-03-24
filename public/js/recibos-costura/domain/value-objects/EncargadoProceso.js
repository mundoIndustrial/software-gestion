/**
 * EncargadoProceso - Value Object inmutable que representa la persona responsable de un proceso
 *
 * Características:
 * - Nombre no vacío
 * - Genera iniciales para avatares
 * - Genera URL de avatar con color automático
 * - Hash automático de color basado en nombre
 * - Detección de contraste para legibilidad
 *
 * @class EncargadoProceso
 */
class EncargadoProceso {
    constructor(nombre) {
        if (!nombre || typeof nombre !== 'string' || nombre.trim() === '') {
            throw new Error('El nombre del encargado no puede estar vacío');
        }

        const nombreLimpio = nombre.trim();

        // Hacer la propiedad inmutable
        Object.defineProperty(this, '_value', {
            value: nombreLimpio,
            writable: false,
            configurable: false,
            enumerable: false
        });
    }

    /**
     * Retorna el nombre del encargado
     */
    getNombre() {
        return this._value;
    }

    /**
     * Retorna una representación legible
     */
    toString() {
        return this._value;
    }

    /**
     * Compara este encargado con otro
     */
    equals(otro) {
        if (!(otro instanceof EncargadoProceso)) {
            return false;
        }
        return this._value.toLowerCase() === otro._value.toLowerCase();
    }

    /**
     * Genera las iniciales del encargado (hasta 3 caracteres)
     * Ejemplo: "Juan García" -> "JG"
     */
    getIniciales() {
        const palabras = this._value.trim().split(/\s+/);
        
        if (palabras.length === 0) {
            return '?';
        }

        if (palabras.length === 1) {
            return palabras[0].substring(0, 2).toUpperCase();
        }

        // Tomar la primera letra de cada palabra (máximo 3)
        const iniciales = palabras
            .slice(0, 3)
            .map(p => p[0].toUpperCase())
            .join('');

        return iniciales;
    }

    /**
     * Genera un color hexadecimal basado en el nombre (hash determinístico)
     * Siempre retorna el mismo color para el mismo nombre
     */
    _hashStringToColor() {
        const nombre = this._value.toLowerCase();
        let hash = 0;

        for (let i = 0; i < nombre.length; i++) {
            hash = ((hash << 5) - hash) + nombre.charCodeAt(i);
            hash = hash & hash; // Convertir a entero 32-bit
        }

        // Convertir hash a color hexadecimal
        const hue = Math.abs(hash) % 360;
        const saturation = 70;
        const lightness = 60;

        return `hsl(${hue}, ${saturation}%, ${lightness}%)`;
    }

    /**
     * Determina si el texto debe ser blanco o negro para contraste
     */
    _getTextColor() {
        // Extractar RGB del color HSL
        const hslString = this._hashStringToColor();
        const hslMatch = hslString.match(/(\d+),\s*(\d+)%,\s*(\d+)%/);

        if (!hslMatch) {
            return '#000000';
        }

        const lightness = parseInt(hslMatch[3]);

        // Si el fondo es oscuro (lightness < 50), usar texto blanco
        return lightness > 50 ? '#000000' : '#ffffff';
    }

    /**
     * Retorna la URL de un avatar generado con ui-avatars.com
     * Usa el nombre como referencia y genera un color automático
     */
    getAvatarUrl() {
        const iniciales = this.getIniciales();
        const bgColor = this._hashStringToColor().match(/(\d+)/g);
        const hsl = `${bgColor[0]},${bgColor[1]},${bgColor[2]}`;

        // Construir URL con parámetros: nombre, tamaño, fondo, color de texto
        const params = new URLSearchParams({
            name: iniciales,
            size: 40,
            background: this._hashStringToColor(),
            color: this._getTextColor(),
            rounded: true,
            font_size: 0.4,
            bold: true
        });

        return `https://ui-avatars.com/api/?${params.toString()}`;
    }

    /**
     * Factory method: crear desde un nombre
     * Retorna null si el nombre es inválido y manejo es requerido
     */
    static from(nombre) {
        if (!nombre || typeof nombre !== 'string' || nombre.trim() === '') {
            throw new Error('El nombre del encargado no puede estar vacío');
        }
        return new EncargadoProceso(nombre);
    }

    /**
     * Factory method: crear de forma segura sin lanzar error
     * Retorna null si el nombre es inválido
     */
    static tryFrom(nombre) {
        if (!nombre || typeof nombre !== 'string' || nombre.trim() === '') {
            return null;
        }
        try {
            return new EncargadoProceso(nombre);
        } catch (e) {
            return null;
        }
    }

    /**
     * Valida si un valor puede ser un encargado válido
     */
    static isEncargadoValido(valor) {
        return valor && typeof valor === 'string' && valor.trim() !== '';
    }
}
