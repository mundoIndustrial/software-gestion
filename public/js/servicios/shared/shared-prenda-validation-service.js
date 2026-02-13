/**
 * 🔒 SharedPrendaValidationService
 * 
 * IMPORTANTE: AISLADO DE COTIZACIONES
 * - Solo reglas de validación genéricas para prendas
 * - NO tiene reglas específicas de cotización
 * - Validación puramente funcional
 */

class SharedPrendaValidationService {
    constructor(config = {}) {
        this.rules = config.rules || this.getReglasDefecto();
        console.log('[SharedPrendaValidationService] ✓ Inicializado');
    }

    /**
     * Validar datos de prenda completos
     * @returns {Array} Array de errores (vacío si no hay errores)
     */
    validar(prenda) {
        console.log('[SharedPrendaValidation] 🔍 Validando prenda:', prenda.nombre);

        const errores = [];

        // 1️⃣ Validar nombre (obligatorio)
        if (!prenda.nombre || prenda.nombre.trim().length === 0) {
            errores.push({
                campo: 'nombre',
                mensaje: 'El nombre de la prenda es obligatorio',
                severidad: 'error'
            });
        } else if (prenda.nombre.trim().length < 3) {
            errores.push({
                campo: 'nombre',
                mensaje: 'El nombre debe tener al menos 3 caracteres',
                severidad: 'warning'
            });
        }

        // 2️⃣ Validar origen (debe ser válido)
        const origenesValidos = ['bodega', 'confeccion'];
        if (!prenda.origen || !origenesValidos.includes(prenda.origen)) {
            errores.push({
                campo: 'origen',
                mensaje: 'Origen de prenda inválido (seleccionar: bodega o confección)',
                severidad: 'error'
            });
        }

        // 3️⃣ Validar que tenga al menos una talla con cantidad > 0
        const tienetallaValida = this.validarTallas(prenda.tallas || []);
        if (!tienetallaValida) {
            errores.push({
                campo: 'tallas',
                mensaje: 'Debe agregar al menos una talla con cantidad mayor a 0',
                severidad: 'error'
            });
        }

        // 4️⃣ Validar telas (opcional pero si hay, deben ser válidas)
        if (prenda.telas && prenda.telas.length > 0) {
            const erroresTelas = this.validarTelas(prenda.telas);
            errores.push(...erroresTelas);
        }

        // 5️⃣ Validar procesos (si hay)
        if (prenda.procesos && prenda.procesos.length > 0) {
            const erroresProcesos = this.validarProcesos(prenda.procesos);
            errores.push(...erroresProcesos);
        }

        // Separar por severidad
        const erroresGrave = errores.filter(e => e.severidad === 'error');
        const advertencias = errores.filter(e => e.severidad === 'warning');

        console.log('[SharedPrendaValidation] Resultado:', {
            valida: erroresGrave.length === 0,
            errores: erroresGrave.length,
            advertencias: advertencias.length
        });

        return erroresGrave; // Retorna solo errores graves (no advertencias)
    }

    /**
     * Validar tallas
     */
    validarTallas(tallas) {
        if (!Array.isArray(tallas) || tallas.length === 0) {
            return false;
        }

        // Verificar que al menos UNA talla tenga cantidad > 0
        return tallas.some(t => {
            const cantidad = typeof t.cantidad === 'string' 
                ? parseInt(t.cantidad) 
                : t.cantidad;
            return cantidad > 0;
        });
    }

    /**
     * Validar telas
     */
    validarTelas(telas) {
        const errores = [];

        telas.forEach((tela, index) => {
            // Si tiene tela_id, debe ser válido
            if (!tela.tela_id || tela.tela_id <= 0) {
                errores.push({
                    campo: `telas[${index}]`,
                    mensaje: `Tela ${index + 1}: Debe seleccionar una tela válida`,
                    severidad: 'error'
                });
            }
        });

        return errores;
    }

    /**
     * Validar procesos
     */
    validarProcesos(procesos) {
        const errores = [];

        procesos.forEach((proceso, index) => {
            // Proceso debe tener ID
            if (!proceso.id || proceso.id <= 0) {
                errores.push({
                    campo: `procesos[${index}]`,
                    mensaje: `Proceso ${index + 1}: ID inválido`,
                    severidad: 'warning'
                });
            }
        });

        return errores;
    }

    /**
     * Validar campo individual
     */
    validarCampo(nombreCampo, valor) {
        console.log(`[SharedPrendaValidation] Validando campo: ${nombreCampo}`);

        switch (nombreCampo) {
            case 'nombre':
                if (!valor || valor.trim().length < 3) {
                    return {
                        valido: false,
                        mensaje: 'Nombre debe tener al menos 3 caracteres'
                    };
                }
                return { valido: true };

            case 'origen':
                if (!['bodega', 'confeccion'].includes(valor)) {
                    return {
                        valido: false,
                        mensaje: 'Origen inválido'
                    };
                }
                return { valido: true };

            case 'talla':
                if (!valor || !valor.talla) {
                    return {
                        valido: false,
                        mensaje: 'Talla requerida'
                    };
                }
                if (typeof valor.cantidad !== 'number' || valor.cantidad <= 0) {
                    return {
                        valido: false,
                        mensaje: 'Cantidad debe ser mayor a 0'
                    };
                }
                return { valido: true };

            default:
                return { valido: true };
        }
    }

    /**
     * Obtener reglas de validación
     */
    getReglasDefecto() {
        return {
            nombre: {
                required: true,
                minLength: 3,
                maxLength: 500
            },
            descripcion: {
                required: false,
                maxLength: 1000
            },
            origen: {
                required: true,
                enum: ['bodega', 'confeccion']
            },
            tallas: {
                required: true,
                minItems: 1,
                validate: (tallas) => {
                    return tallas.some(t => t.cantidad > 0);
                }
            },
            telas: {
                required: false,
                validate: (telas) => {
                    if (!Array.isArray(telas)) return true;
                    return telas.every(t => t.tela_id);
                }
            }
        };
    }

    /**
     * Cambiar reglas de validación
     */
    setReglas(nuevasReglas) {
        this.rules = { ...this.rules, ...nuevasReglas };
        console.log('[SharedPrendaValidation] Reglas actualizadas');
    }

    /**
     * Resetear a reglas por defecto
     */
    resetearReglas() {
        this.rules = this.getReglasDefecto();
        console.log('[SharedPrendaValidation] Reglas reseteadas a defecto');
    }
}

// Exportar
window.SharedPrendaValidationService = SharedPrendaValidationService;
console.log('[SharedPrendaValidationService] 🔐 Cargado (AISLADO DE COTIZACIONES)');
