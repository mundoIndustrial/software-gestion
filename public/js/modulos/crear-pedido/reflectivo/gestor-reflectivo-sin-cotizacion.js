/**
 * GESTOR DE REFLECTIVO SIN COTIZACIÓN - Nueva Prenda Tipo REFLECTIVO
 * 
 * Este módulo maneja toda la lógica de renderización y gestión de prendas
 * cuando el usuario selecciona "Nuevo Pedido" > "REFLECTIVO" sin cotización previa.
 * 
 * Renderiza los campos necesarios para crear un pedido tipo REFLECTIVO:
 * - Información básica (tipo de prenda, descripción)
 * - Imágenes (máximo 3)
 * - Género (Dama/Caballero)
 * - Tallas y cantidades
 * 
 * RESPONSABILIDADES:
 * - Agregar prendas de tipo REFLECTIVO
 * - Renderizar formularios completos para reflectivo
 * - Gestionar fotos
 * - Gestionar tallas y géneros
 */

class GestorReflectivoSinCotizacion {
    /**
     * Constructor
     * @param {string} containerId - ID del contenedor donde renderizar
     */
    constructor(containerId = 'prendas-container-editable') {
        this.prendas = [];
        this.containerId = containerId;
        this.prendasEliminadas = new Set();
        this.fotosNuevas = {};
        this.tipoPedidoActual = 'R'; // Tipo REFLECTIVO
        
    }

    /**
     * Crear una prenda base de tipo REFLECTIVO
     * @returns {Object} Prenda inicializada
     */
    crearPrendaBase() {
        return {
            nombre_producto: '', // Tipo de prenda (Camiseta, Pantalón, etc.)
            descripcion: '', // Descripción del reflectivo
            genero: '', // Dama o Caballero (vacío por defecto)
            generosSeleccionados: [], //  NUEVO: Array de géneros seleccionados
            tallas: [],
            cantidadesPorTalla: {},
            generosConTallas: {}, //  NUEVO: Estructura género => talla => cantidad
            fotos: [],
            ubicaciones: [] // Ubicaciones del reflectivo
        };
    }

    /**
     * Agregar una nueva prenda de tipo REFLECTIVO
     * @returns {number} Índice de la prenda agregada
     */
    agregarPrenda() {
        const nuevaPrenda = this.crearPrendaBase();
        this.prendas.push(nuevaPrenda);
        const index = this.prendas.length - 1;
        return index;
    }

    /**
     * Obtener todas las prendas activas
     * @returns {Array} Prendas no eliminadas
     */
    obtenerActivas() {
        return this.prendas.filter((_, index) => !this.prendasEliminadas.has(index));
    }

    /**
     * Obtener prenda por índice
     * @param {number} index - Índice de la prenda
     * @returns {Object|null} Prenda encontrada
     */
    obtenerPorIndice(index) {
        return this.prendas[index] || null;
    }

    /**
     * Eliminar prenda (marcar para eliminación)
     * @param {number} index - Índice de la prenda
     */
    eliminar(index) {
        this.prendasEliminadas.add(index);
    }

    /**
     * Agregar talla a una prenda
     * @param {number} prendaIndex - Índice de la prenda
     * @param {string} talla - Talla a agregar
     */
    agregarTalla(prendaIndex, talla) {
        const prenda = this.obtenerPorIndice(prendaIndex);
        if (!prenda) return;

        if (!Array.isArray(prenda.tallas)) {
            prenda.tallas = [];
        }

        if (!prenda.tallas.includes(talla)) {
            prenda.tallas.push(talla);
            if (!prenda.cantidadesPorTalla) {
                prenda.cantidadesPorTalla = {};
            }
            prenda.cantidadesPorTalla[talla] = 0;
        }
    }

    /**
     * Eliminar talla de una prenda
     * @param {number} prendaIndex - Índice de la prenda
     * @param {string} talla - Talla a eliminar
     */
    eliminarTalla(prendaIndex, talla) {
        const prenda = this.obtenerPorIndice(prendaIndex);
        if (!prenda) return;

        if (Array.isArray(prenda.tallas)) {
            const idx = prenda.tallas.indexOf(talla);
            if (idx >= 0) {
                prenda.tallas.splice(idx, 1);
                if (prenda.cantidadesPorTalla) {
                    delete prenda.cantidadesPorTalla[talla];
                }
            }
        }
    }

    /**
     * Actualizar cantidad para una talla
     * @param {number} prendaIndex - Índice de la prenda
     * @param {string} talla - Talla
     * @param {number} cantidad - Nueva cantidad
     */
    actualizarCantidadTalla(prendaIndex, talla, cantidad) {
        const prenda = this.obtenerPorIndice(prendaIndex);
        if (!prenda) return;

        if (!prenda.cantidadesPorTalla) {
            prenda.cantidadesPorTalla = {};
        }
        prenda.cantidadesPorTalla[talla] = parseInt(cantidad) || 0;
    }

    /**
     * Agregar fotos a una prenda (máximo 3)
     * @param {number} prendaIndex - Índice de la prenda
     * @param {Array} fotos - Array de fotos
     */
    agregarFotos(prendaIndex, fotos) {
        if (!this.fotosNuevas[prendaIndex]) {
            this.fotosNuevas[prendaIndex] = [];
        }

        const fotosActuales = this.fotosNuevas[prendaIndex].length;
        const espacioDisponible = Math.max(0, 3 - fotosActuales);

        if (espacioDisponible <= 0) {
            return;
        }

        const fotosAgregar = fotos.slice(0, espacioDisponible);
        this.fotosNuevas[prendaIndex] = [...this.fotosNuevas[prendaIndex], ...fotosAgregar];
    }

    /**
     * Eliminar foto de una prenda
     * @param {number} prendaIndex - Índice de la prenda
     * @param {number} fotoIndex - Índice de la foto
     */
    eliminarFoto(prendaIndex, fotoIndex) {
        if (this.fotosNuevas[prendaIndex]) {
            this.fotosNuevas[prendaIndex].splice(fotoIndex, 1);
        }
    }

    /**
     * Obtener fotos nuevas de una prenda
     * @param {number} prendaIndex - Índice de la prenda
     * @returns {Array} Fotos nuevas
     */
    obtenerFotosNuevas(prendaIndex) {
        return this.fotosNuevas[prendaIndex] || [];
    }

    /**
     * Actualizar género de una prenda
     * @param {number} prendaIndex - Índice de la prenda
     * @param {string} genero - Género (Dama/Caballero)
     */
    actualizarGenero(prendaIndex, genero) {
        const prenda = this.obtenerPorIndice(prendaIndex);
        if (prenda) {
            prenda.genero = genero;
        }
    }

    /**
     * Agregar ubicación a una prenda
     * @param {number} prendaIndex - Índice de la prenda
     * @param {string} ubicacion - Nombre de la ubicación (PECHO, ESPALDA, MANGA, etc.)
     * @param {string} observaciones - Observaciones/detalles de la ubicación
     */
    agregarUbicacion(prendaIndex, ubicacion, observaciones = '') {
        const prenda = this.obtenerPorIndice(prendaIndex);
        if (!prenda) return;

        if (!Array.isArray(prenda.ubicaciones)) {
            prenda.ubicaciones = [];
        }

        // Evitar duplicados
        if (!prenda.ubicaciones.some(u => u.nombre.toLowerCase() === ubicacion.toLowerCase())) {
            prenda.ubicaciones.push({
                nombre: ubicacion,
                observaciones: observaciones,
                id: Date.now() // ID único para poder eliminar después
            });
        }
    }

    /**
     * Eliminar ubicación de una prenda
     * @param {number} prendaIndex - Índice de la prenda
     * @param {number} ubicacionId - ID de la ubicación a eliminar
     */
    eliminarUbicacion(prendaIndex, ubicacionId) {
        const prenda = this.obtenerPorIndice(prendaIndex);
        if (!prenda || !Array.isArray(prenda.ubicaciones)) return;

        const idx = prenda.ubicaciones.findIndex(u => u.id === ubicacionId);
        if (idx >= 0) {
            const ubicacionEliminada = prenda.ubicaciones[idx].nombre;
            prenda.ubicaciones.splice(idx, 1);
        }
    }

    /**
     * Obtener ubicaciones de una prenda
     * @param {number} prendaIndex - Índice de la prenda
     * @returns {Array} Array de ubicaciones
     */
    obtenerUbicaciones(prendaIndex) {
        const prenda = this.obtenerPorIndice(prendaIndex);
        return prenda && Array.isArray(prenda.ubicaciones) ? prenda.ubicaciones : [];
    }

    /**
     * Recopilar datos del DOM
     * Obtiene información de campos del formulario
     */
    recopilarDatosDelDOM() {
        const container = document.getElementById(this.containerId);
        if (!container) return [];

        const prendaCards = container.querySelectorAll('.prenda-card-reflectivo');
        const prendasDelDOM = [];

        prendaCards.forEach((card, index) => {
            if (this.prendasEliminadas.has(index)) return;

            //  CORREGIDO: Obtener los géneros seleccionados
            const generosSeleccionados = [];
            const checkDama = card.querySelector('input[name*="genero_reflectivo_dama"]');
            const checkCaballero = card.querySelector('input[name*="genero_reflectivo_caballero"]');
            
            if (checkDama && checkDama.checked) {
                generosSeleccionados.push('dama');
            }
            if (checkCaballero && checkCaballero.checked) {
                generosSeleccionados.push('caballero');
            }

            const prenda = {
                index: index,
                nombre_producto: card.querySelector('[name*="tipo_prenda"]')?.value || '',
                descripcion: card.querySelector('[name*="descripcion"]')?.value || '',
                genero: card.querySelector('.genero-radio-reflectivo:checked')?.value || '',
                generosSeleccionados: generosSeleccionados, //  NUEVO: Incluir géneros seleccionados
                cantidadesPorTalla: {},
                generosConTallas: {} //  NUEVO: Estructura con géneros
            };

            //  NUEVO: Recopilar tallas con géneros desde los inputs ocultos
            card.querySelectorAll('.talla-cantidad-genero-editable').forEach(input => {
                const talla = input.getAttribute('data-talla');
                const genero = input.getAttribute('data-genero');
                const cantidad = parseInt(input.value) || 0;
                
                if (talla && genero && cantidad > 0) {
                    if (!prenda.generosConTallas[genero]) {
                        prenda.generosConTallas[genero] = {};
                    }
                    prenda.generosConTallas[genero][talla] = cantidad;
                }
            });

            // Fallback: Recopilar del sistema antiguo si no hay generosConTallas
            if (Object.keys(prenda.generosConTallas).length === 0) {
                card.querySelectorAll('.talla-cantidad-reflectivo').forEach(input => {
                    const talla = input.getAttribute('data-talla');
                    const cantidad = parseInt(input.value) || 0;
                    if (talla && cantidad > 0) {
                        prenda.cantidadesPorTalla[talla] = cantidad;
                    }
                });
            }

            prendasDelDOM.push(prenda);
        });

        return prendasDelDOM;
    }

    /**
     * Validar antes de envío
     * @returns {Object} {valido: boolean, errores: Array}
     */
    validar() {
        const errores = [];
        const cliente = document.getElementById('cliente_editable')?.value;

        if (!cliente || cliente.trim() === '') {
            errores.push('Cliente es requerido');
        }

        if (this.prendas.length === 0) {
            errores.push('Debe haber al menos una prenda');
        }

        let tieneCantidades = false;
        this.prendas.forEach((prenda, index) => {
            if (!prenda.nombre_producto || prenda.nombre_producto.trim() === '') {
                errores.push(`Prenda ${index + 1}: Tipo de prenda es requerido`);
            }

            //  CORREGIDO: Validar generosConTallas en lugar de cantidadesPorTalla
            let tieneCantidadesEnPrenda = false;
            if (prenda.generosConTallas && Object.keys(prenda.generosConTallas).length > 0) {
                Object.values(prenda.generosConTallas).forEach(tallas => {
                    Object.values(tallas).forEach(cantidad => {
                        if (cantidad > 0) {
                            tieneCantidadesEnPrenda = true;
                        }
                    });
                });
            }

            if (!tieneCantidadesEnPrenda) {
                errores.push(`Prenda ${index + 1}: Debe tener al menos una cantidad de talla`);
            } else {
                tieneCantidades = true;
            }

            //  CORREGIDO: Validar generosSeleccionados en lugar de genero
            if (!prenda.generosSeleccionados || prenda.generosSeleccionados.length === 0) {
                errores.push(`Prenda ${index + 1}: Género es requerido`);
            }
        });

        if (!tieneCantidades) {
            errores.push('Debe haber cantidades en al menos una prenda');
        }

        return {
            valido: errores.length === 0,
            errores: errores
        };
    }

    /**
     * Obtener cantidad de prendas
     * @returns {number} Cantidad
     */
    cantidad() {
        return this.prendas.length;
    }

    /**
     * Limpiar todas las prendas
     */
    limpiar() {
        this.prendas = [];
        this.prendasEliminadas.clear();
        this.fotosNuevas = {};
    }
}

// Instancia global del gestor
window.gestorReflectivoSinCotizacion = new GestorReflectivoSinCotizacion();
