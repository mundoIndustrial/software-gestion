/**
 * ValidadorPrenda - Validación centralizada para prendas
 * 
 * Centraliza TODAS las validaciones relacionadas con prendas:
 * - Campos obligatorios
 * - Rangos y límites
 * - Consistencia de datos
 * - Reglas de negocio
 * 
 * Objetivo: Eliminar validaciones dispersas en 5+ lugares
 * Beneficio: Una única fuente de verdad para reglas de negocio
 * 
 * @author Phase 3 Refactorización
 * @version 1.0.0
 */

class ValidadorPrenda {
    /**
     * Validación exhaustiva de prenda nueva
     * 
     * @param {Object} prenda - Objeto prenda a validar
     * @returns {Object} { válido: boolean, errores: Array<string> }
     */
    static validarPrendaNueva(prenda) {
        const errores = [];

        // Validar campos básicos obligatorios
        if (!prenda.nombre_producto?.trim()) {
            errores.push('El nombre de la prenda es obligatorio');
        }
        if (prenda.nombre_producto?.length < 3) {
            errores.push('El nombre de la prenda debe tener al menos 3 caracteres');
        }
        if (prenda.nombre_producto?.length > 100) {
            errores.push('El nombre de la prenda no puede exceder 100 caracteres');
        }

        // Validar género
        if (!prenda.genero) {
            errores.push('El género de la prenda es obligatorio');
        }
        if (!['dama', 'caballero', 'unisex', 'infantil', 'mezclilla'].includes(prenda.genero)) {
            errores.push(`Género inválido: ${prenda.genero}`);
        }

        // Validar origen
        if (!prenda.origen) {
            errores.push('El origen de la prenda es obligatorio');
        }
        if (!['bodega', 'confecciona', 'importa', 'otros'].includes(prenda.origen)) {
            errores.push(`Origen inválido: ${prenda.origen}`);
        }

        // Validar tallas
        const tieneTallas = this.validarTallas(prenda.tallas || prenda.tallasPorGenero);
        if (!tieneTallas.válido) {
            errores.push(...tieneTallas.errores);
        }

        // Validar cantidades
        if (prenda.cantidadesPorTalla) {
            const cantidadesValidas = this.validarCantidadesPorTalla(prenda.cantidadesPorTalla);
            if (!cantidadesValidas.válido) {
                errores.push(...cantidadesValidas.errores);
            }
        }

        // Validar géneros con tallas (si existe)
        if (prenda.generosConTallas) {
            const generosValidos = this.validarGenerosConTallas(prenda.generosConTallas);
            if (!generosValidos.válido) {
                errores.push(...generosValidos.errores);
            }
        }

        // Validar procesos
        if (prenda.procesos) {
            const procesosValidos = this.validarProcesos(prenda.procesos);
            if (!procesosValidos.válido) {
                errores.push(...procesosValidos.errores);
            }
        }

        // Validar variaciones
        if (prenda.variantes || prenda.variaciones) {
            const variacionesValidas = this.validarVariaciones(prenda.variantes || prenda.variaciones);
            if (!variacionesValidas.válido) {
                errores.push(...variacionesValidas.errores);
            }
        }

        // Validar telas si existen
        if (prenda.telasAgregadas && prenda.telasAgregadas.length > 0) {
            const telasValidas = this.validarTelas(prenda.telasAgregadas);
            if (!telasValidas.válido) {
                errores.push(...telasValidas.errores);
            }
        }

        // Validar imágenes si existen
        if (prenda.imagenes && prenda.imagenes.length > 0) {
            const imagenesValidas = this.validarImagenes(prenda.imagenes);
            if (!imagenesValidas.válido) {
                errores.push(...imagenesValidas.errores);
            }
        }

        return {
            válido: errores.length === 0,
            errores: errores
        };
    }

    /**
     * Validar tallas estructura
     * 
     * @param {Array} tallas - Array de tallas
     * @returns {Object} { válido: boolean, errores: Array<string> }
     */
    static validarTallas(tallas) {
        const errores = [];

        if (!Array.isArray(tallas) || tallas.length === 0) {
            errores.push('La prenda debe tener al menos una talla');
            return { válido: false, errores };
        }

        // Validar estructura de cada talla
        tallas.forEach((tallaData, idx) => {
            if (!tallaData.genero) {
                errores.push(`Talla ${idx}: Falta género`);
            }
            if (!Array.isArray(tallaData.tallas) || tallaData.tallas.length === 0) {
                errores.push(`Talla ${idx} (${tallaData.genero}): Sin tallas específicas`);
            }
        });

        return {
            válido: errores.length === 0,
            errores
        };
    }

    /**
     * Validar cantidades por talla
     * 
     * @param {Object} cantidades - Objeto de cantidades { talla: cantidad }
     * @returns {Object} { válido: boolean, errores: Array<string> }
     */
    static validarCantidadesPorTalla(cantidades) {
        const errores = [];

        if (!cantidades || typeof cantidades !== 'object') {
            errores.push('Cantidades por talla debe ser un objeto');
            return { válido: false, errores };
        }

        let hayAlguna = false;
        Object.entries(cantidades).forEach(([talla, cantidad]) => {
            if (typeof cantidad !== 'number' || cantidad < 0) {
                errores.push(`Cantidad inválida para talla ${talla}: ${cantidad}`);
            }
            if (cantidad > 0) hayAlguna = true;
        });

        if (!hayAlguna) {
            errores.push('Debes especificar cantidad para al menos una talla');
        }

        return {
            válido: errores.length === 0,
            errores
        };
    }

    /**
     * Validar géneros con tallas
     * 
     * @param {Object} generosConTallas - Objeto { genero: { talla: cantidad } }
     * @returns {Object} { válido: boolean, errores: Array<string> }
     */
    static validarGenerosConTallas(generosConTallas) {
        const errores = [];

        if (!generosConTallas || typeof generosConTallas !== 'object') {
            errores.push('Géneros con tallas debe ser un objeto');
            return { válido: false, errores };
        }

        Object.entries(generosConTallas).forEach(([genero, tallas]) => {
            if (!['dama', 'caballero', 'unisex', 'infantil'].includes(genero)) {
                errores.push(`Género inválido: ${genero}`);
            }
            if (typeof tallas !== 'object' || Object.keys(tallas).length === 0) {
                errores.push(`Género ${genero}: Sin tallas asignadas`);
            }
            Object.entries(tallas).forEach(([talla, cantidad]) => {
                if (typeof cantidad !== 'number' || cantidad <= 0) {
                    errores.push(`${genero} - ${talla}: Cantidad inválida`);
                }
            });
        });

        return {
            válido: errores.length === 0,
            errores
        };
    }

    /**
     * Validar procesos
     * 
     * @param {Object} procesos - Objeto de procesos configurables
     * @returns {Object} { válido: boolean, errores: Array<string> }
     */
    static validarProcesos(procesos) {
        const errores = [];
        const procesosValidos = ['reflectivo', 'bordado', 'estampado', 'teñido', 'planchado'];

        if (typeof procesos !== 'object') {
            errores.push('Procesos debe ser un objeto');
            return { válido: false, errores };
        }

        Object.keys(procesos).forEach(tipoProceso => {
            if (!procesosValidos.includes(tipoProceso)) {
                errores.push(`Proceso desconocido: ${tipoProceso}`);
            }

            const proceso = procesos[tipoProceso];
            if (proceso.datos === null) {
                errores.push(`Proceso ${tipoProceso}: Datos vacíos`);
            }
        });

        return {
            válido: errores.length === 0,
            errores
        };
    }

    /**
     * Validar variaciones
     * 
     * @param {Object} variaciones - Objeto de variaciones
     * @returns {Object} { válido: boolean, errores: Array<string> }
     */
    static validarVariaciones(variaciones) {
        const errores = [];

        if (!variaciones || typeof variaciones !== 'object') {
            errores.push('Variaciones debe ser un objeto');
            return { válido: false, errores };
        }

        // Validar estructura esperada
        const camposEsperados = [
            'tipo_manga',
            'obs_manga',
            'tipo_broche',
            'obs_broche',
            'tiene_bolsillos',
            'obs_bolsillos',
            'tiene_reflectivo',
            'obs_reflectivo'
        ];

        camposEsperados.forEach(campo => {
            if (!(campo in variaciones)) {
                errores.push(`Variación faltante: ${campo}`);
            }
        });

        // Validar valores específicos
        if (variaciones.tipo_manga && ![
            'No aplica',
            'Larga',
            'Media',
            'Corta',
            'Sin manga'
        ].includes(variaciones.tipo_manga)) {
            errores.push(`Tipo de manga inválido: ${variaciones.tipo_manga}`);
        }

        if (variaciones.tipo_broche && ![
            'No aplica',
            'Botones',
            'Zipper',
            'Corchetes',
            'Velcro'
        ].includes(variaciones.tipo_broche)) {
            errores.push(`Tipo de broche inválido: ${variaciones.tipo_broche}`);
        }

        if (typeof variaciones.tiene_bolsillos !== 'boolean') {
            errores.push(`Bolsillos debe ser boolean: ${variaciones.tiene_bolsillos}`);
        }

        if (typeof variaciones.tiene_reflectivo !== 'boolean') {
            errores.push(`Reflectivo debe ser boolean: ${variaciones.tiene_reflectivo}`);
        }

        return {
            válido: errores.length === 0,
            errores
        };
    }

    /**
     * Validar telas
     * 
     * @param {Array} telas - Array de telas
     * @returns {Object} { válido: boolean, errores: Array<string> }
     */
    static validarTelas(telas) {
        const errores = [];

        if (!Array.isArray(telas)) {
            errores.push('Telas debe ser un array');
            return { válido: false, errores };
        }

        if (telas.length === 0) {
            return { válido: true, errores: [] };
        }

        telas.forEach((tela, idx) => {
            if (!tela.color) {
                errores.push(`Tela ${idx}: Falta color`);
            }
            if (!tela.tela) {
                errores.push(`Tela ${idx}: Falta nombre de tela`);
            }
            if (tela.imagenes && !Array.isArray(tela.imagenes)) {
                errores.push(`Tela ${idx}: Imágenes debe ser un array`);
            }
        });

        return {
            válido: errores.length === 0,
            errores
        };
    }

    /**
     * Validar imágenes
     * 
     * @param {Array} imagenes - Array de imágenes
     * @returns {Object} { válido: boolean, errores: Array<string> }
     */
    static validarImagenes(imagenes) {
        const errores = [];
        const extensionesValidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        const tamañoMaxMB = 5;
        const tamañoMaxBytes = tamañoMaxMB * 1024 * 1024;

        if (!Array.isArray(imagenes)) {
            errores.push('Imágenes debe ser un array');
            return { válido: false, errores };
        }

        imagenes.forEach((img, idx) => {
            if (!img.nombre) {
                errores.push(`Imagen ${idx}: Falta nombre`);
            }

            if (img.tamaño && img.tamaño > tamañoMaxBytes) {
                errores.push(`Imagen ${idx}: Excede tamaño máximo (${tamañoMaxMB}MB)`);
            }

            if (img.nombre) {
                const ext = img.nombre.split('.').pop().toLowerCase();
                if (!extensionesValidas.includes(ext)) {
                    errores.push(`Imagen ${idx}: Extensión no permitida (.${ext})`);
                }
            }
        });

        if (imagenes.length === 0) {
            errores.push('Debes agregar al menos una imagen');
        }

        return {
            válido: errores.length === 0,
            errores
        };
    }

    /**
     * Validación rápida del formulario
     * Valida solo campos visibles/obligatorios ANTES de procesar
     * 
     * @returns {Object} { válido: boolean, errores: Array<string> }
     */
    static validarFormularioRápido() {
        const errores = [];

        // Validar nombre
        const nombre = document.getElementById('nueva-prenda-nombre')?.value?.trim();
        if (!nombre) {
            errores.push('Nombre de prenda es obligatorio');
        }

        // Validar género (tallas)
        const tallasSeleccionadas = window.tallasSeleccionadas || {};
        const tienetallasDama = tallasSeleccionadas.dama?.tallas?.length > 0;
        const tieneTallasCaballero = tallasSeleccionadas.caballero?.tallas?.length > 0;

        if (!tienetallasDama && !tieneTallasCaballero) {
            errores.push('Debes seleccionar al menos una talla');
        }

        return {
            válido: errores.length === 0,
            errores
        };
    }

    /**
     * Obtener lista de validaciones pendientes (para debugging)
     * 
     * @param {Object} prenda - Prenda a validar
     * @returns {Array} Array de validaciones pendientes
     */
    static obtenerValidacionesPendientes(prenda) {
        const validaciones = [];

        if (!prenda.nombre_producto) validaciones.push('Nombre de prenda');
        if (!prenda.genero) validaciones.push('Género');
        if (!prenda.origen) validaciones.push('Origen');
        if (!prenda.tallas || prenda.tallas.length === 0) validaciones.push('Tallas');
        if (!prenda.cantidadesPorTalla || Object.keys(prenda.cantidadesPorTalla).length === 0) {
            validaciones.push('Cantidades por talla');
        }

        return validaciones;
    }
}

// Exportar globalmente
window.ValidadorPrenda = ValidadorPrenda;
