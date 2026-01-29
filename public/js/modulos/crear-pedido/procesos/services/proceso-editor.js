/**
 * ProcesosEditor - Gestor de Edición Dinámica de Procesos
 * 
 * Responsabilidad: Gestionar la edición de procesos individuales dentro de una prenda
 * 
 * REGLAS CRÍTICAS:
 * ✅ Solo edita el proceso específico seleccionado
 * ✅ No duplica procesos
 * ✅ No afecta otras prendas ni procesos
 * ✅ Permite eliminar/agregar ubicaciones e imágenes
 * ✅ Reemplaza (no merge) ubicaciones al guardar
 */

class ProcesosEditor {
    constructor() {
        // Buffer de edición: qué proceso está siendo editado
        this.procesoEnEdicion = null;
        
        // Estado original del proceso: para comparar cambios
        this.procesoOriginal = null;
        
        // Cambios realizados: ubicaciones, imágenes, observaciones
        this.cambios = {
            ubicaciones: null,      // Array reemplazado (no merge)
            imagenes: null,         // Array reemplazado (no merge)
            observaciones: null,    // String
            tallas: null           // Object
        };
        
        // Flag para saber si hay cambios pendientes
        this.hayChangiosPendientes = false;
    }

    /**
     * Iniciar edición de un proceso
     * Captura el estado original para comparación
     * 
     * @param {string} tipo - Tipo de proceso (ej: 'reflectivo', 'bordado')
     * @param {object} datosProceso - Datos del proceso desde window.procesosSeleccionados
     */
    iniciarEdicion(tipo, datosProceso) {
        console.log('🔧 [PROCESO-EDITOR] Iniciando edición del proceso:', {
            tipo,
            tieneDatos: !!datosProceso,
            datosKeys: datosProceso ? Object.keys(datosProceso) : 'N/A'
        });

        if (!datosProceso) {
            console.error('❌ [PROCESO-EDITOR] No hay datos del proceso para editar');
            return false;
        }

        // Guardar referencia del proceso en edición
        this.procesoEnEdicion = {
            tipo: tipo,
            datos: JSON.parse(JSON.stringify(datosProceso)) // Deep copy
        };

        // Guardar estado original para detectar cambios
        this.procesoOriginal = JSON.parse(JSON.stringify(datosProceso));

        // Limpiar cambios pendientes
        this.limpiarCambios();

        console.log('✅ [PROCESO-EDITOR] Edición iniciada:', {
            tipo: this.procesoEnEdicion.tipo,
            procesoId: this.procesoEnEdicion.datos.id,
            ubicacionesOriginales: this.procesoOriginal.ubicaciones,
            imagenesOriginales: this.procesoOriginal.imagenes?.length || 0
        });

        return true;
    }

    /**
     * Registrar cambio de ubicaciones
     * Reemplaza completamente el array (no merge)
     * 
     * @param {array} nuevasUbicaciones - Array de ubicaciones seleccionadas
     */
    registrarCambioUbicaciones(nuevasUbicaciones) {
        if (!this.procesoEnEdicion) {
            console.warn('⚠️ [PROCESO-EDITOR] No hay proceso en edición');
            return;
        }

        console.log('📍 [PROCESO-EDITOR] Registrando cambio de ubicaciones:', {
            ubicacionesAnteriores: this.cambios.ubicaciones || this.procesoOriginal.ubicaciones,
            ubicacionesNuevas: nuevasUbicaciones,
            cantidad: nuevasUbicaciones.length
        });

        // Guardar como cambio
        this.cambios.ubicaciones = [...nuevasUbicaciones];
        this.hayChangiosPendientes = true;

        // Actualizar datos en memoria
        this.procesoEnEdicion.datos.ubicaciones = [...nuevasUbicaciones];
    }

    /**
     * Registrar cambio de imágenes
     * Reemplaza completamente el array (no merge)
     * 
     * @param {array} nuevasImagenes - Array de URLs de imágenes
     */
    registrarCambioImagenes(nuevasImagenes) {
        if (!this.procesoEnEdicion) {
            console.warn('⚠️ [PROCESO-EDITOR] No hay proceso en edición');
            return;
        }

        console.log('🖼️ [PROCESO-EDITOR] Registrando cambio de imágenes:', {
            imagenesAnteriores: this.cambios.imagenes || (this.procesoOriginal.imagenes?.length || 0),
            imagenesNuevas: nuevasImagenes.length,
            imagenes: nuevasImagenes
        });

        // Guardar como cambio
        this.cambios.imagenes = [...nuevasImagenes];
        this.hayChangiosPendientes = true;

        // Actualizar datos en memoria
        this.procesoEnEdicion.datos.imagenes = [...nuevasImagenes];
    }

    /**
     * Registrar cambio de observaciones
     * 
     * @param {string} nuevasObservaciones - Texto de observaciones
     */
    registrarCambioObservaciones(nuevasObservaciones) {
        if (!this.procesoEnEdicion) {
            console.warn('⚠️ [PROCESO-EDITOR] No hay proceso en edición');
            return;
        }

        console.log('[PROCESO-EDITOR] Registrando cambio de observaciones:', {
            anterior: this.cambios.observaciones || this.procesoOriginal.observaciones,
            nueva: nuevasObservaciones
        });

        this.cambios.observaciones = nuevasObservaciones;
        this.hayChangiosPendientes = true;
        this.procesoEnEdicion.datos.observaciones = nuevasObservaciones;
    }

    /**
     * Registrar cambio de tallas
     * 
     * @param {object} nuevasTallas - Objeto con tallas por género
     */
    registrarCambioTallas(nuevasTallas) {
        if (!this.procesoEnEdicion) {
            console.warn('⚠️ [PROCESO-EDITOR] No hay proceso en edición');
            return;
        }

        console.log('📊 [PROCESO-EDITOR] Registrando cambio de tallas:', {
            anterior: this.cambios.tallas || this.procesoOriginal.tallas,
            nueva: nuevasTallas
        });

        this.cambios.tallas = JSON.parse(JSON.stringify(nuevasTallas));
        this.hayChangiosPendientes = true;
        this.procesoEnEdicion.datos.tallas = JSON.parse(JSON.stringify(nuevasTallas));
    }

    /**
     * Obtener el proceso en edición
     */
    obtenerProcesoenEdicion() {
        return this.procesoEnEdicion;
    }

    /**
     * Obtener solo los cambios realizados
     * Retorna un objeto con solo los campos que fueron modificados
     */
    obtenerCambios() {
        const cambiosFinales = {};

        // Si hay cambio en ubicaciones - NORMALIZAR Y LIMPIAR
        if (this.cambios.ubicaciones !== null) {
            cambiosFinales.ubicaciones = this._normalizarUbicaciones(this.cambios.ubicaciones);
        }

        // Si hay cambio en imágenes - NORMALIZAR Y LIMPIAR
        if (this.cambios.imagenes !== null) {
            cambiosFinales.imagenes = this._normalizarImagenes(this.cambios.imagenes);
        }

        // Si hay cambio en observaciones
        if (this.cambios.observaciones !== null) {
            cambiosFinales.observaciones = this.cambios.observaciones;
        }

        // Si hay cambio en tallas
        if (this.cambios.tallas !== null) {
            cambiosFinales.tallas = this.cambios.tallas;
        }

        console.log('📤 [PROCESO-EDITOR] Cambios a enviar:', {
            tipo: this.procesoEnEdicion?.tipo,
            cambios: Object.keys(cambiosFinales),
            cambiosFinales
        });

        return cambiosFinales;
    }

    /**
     * PRIVADO: Normalizar ubicaciones para evitar doble JSON encoding
     * Convierte elementos JSON-encodados de vuelta a valores simples
     * @private
     */
    _normalizarUbicaciones(ubicaciones) {
        if (!Array.isArray(ubicaciones)) {
            return ubicaciones;
        }

        return ubicaciones.map(ub => {
            // Si es string que parece JSON (empieza con [ o {), intentar parsearlo
            if (typeof ub === 'string' && (ub.startsWith('[') || ub.startsWith('{'))) {
                try {
                    const parsed = JSON.parse(ub);
                    // Si parsea a array o objeto, extraer valor simple
                    if (Array.isArray(parsed) && parsed.length > 0) {
                        return parsed[0]; // Tomar primer elemento
                    } else if (typeof parsed === 'object' && parsed.ubicacion) {
                        return parsed.ubicacion; // Extraer propiedad ubicacion
                    }
                } catch (e) {
                    // Si no parsea, mantener como string original
                }
            }
            
            // Si es objeto con 'ubicacion', extraer valor
            if (typeof ub === 'object' && ub !== null && ub.ubicacion) {
                return ub.ubicacion;
            }
            
            // Mantener como está
            return ub;
        }).filter(u => u && u.length > 0); // Filtrar vacíos
    }

    /**
     * PRIVADO: Normalizar imágenes para evitar valores null/vacíos
     * Filtra las imágenes válidas
     * @private
     */
    _normalizarImagenes(imagenes) {
        if (!Array.isArray(imagenes)) {
            return [];
        }

        return imagenes
            .map(img => {
                // Si es string, limpiar y retornar
                if (typeof img === 'string') {
                    return img.trim();
                }
                return null;
            })
            .filter(img => img && img !== 'null' && img.length > 0); // Filtrar vacíos y "null"
    }

    /**
     * Guardar cambios del proceso en window.procesosSeleccionados
     * Actualiza la referencia para que se refleje en el modal
     */
    guardarEnWindowProcesos() {
        if (!this.procesoEnEdicion) {
            console.warn('⚠️ [PROCESO-EDITOR] No hay proceso en edición');
            return false;
        }

        const tipo = this.procesoEnEdicion.tipo;

        // Actualizar el proceso en window.procesosSeleccionados
        if (window.procesosSeleccionados && window.procesosSeleccionados[tipo]) {
            console.log('💾 [PROCESO-EDITOR] Guardando cambios en window.procesosSeleccionados:', tipo);
            
            // Actualizar datos del proceso
            window.procesosSeleccionados[tipo].datos = {
                ...window.procesosSeleccionados[tipo].datos,
                ...this.procesoEnEdicion.datos
            };

            console.log('✅ [PROCESO-EDITOR] Cambios guardados en memoria:', {
                tipo,
                datosActualizados: window.procesosSeleccionados[tipo].datos
            });

            return true;
        }

        console.error('❌ [PROCESO-EDITOR] No se encontró proceso en window.procesosSeleccionados:', tipo);
        return false;
    }

    /**
     * Obtener datos del proceso en edición para enviar al servidor
     * Incluye el ID del proceso para identificarlo en BD
     */
    obtenerPayloadActualizacion() {
        if (!this.procesoEnEdicion) {
            return null;
        }

        const cambios = this.obtenerCambios();

        const payload = {
            id: this.procesoEnEdicion.datos.id,           // ID del proceso en BD
            tipo_proceso_id: this.procesoEnEdicion.datos.tipo_proceso_id,  // ID del tipo
            tipo: this.procesoEnEdicion.datos.tipo,        // Tipo formateado (reflectivo, bordado, etc)
            ...cambios
        };

        console.log('📦 [PROCESO-EDITOR] Payload para actualización:', payload);
        return payload;
    }

    /**
     * Cancelar edición y limpiar buffer
     */
    cancelarEdicion() {
        console.log('❌ [PROCESO-EDITOR] Cancelando edición del proceso:', {
            tipo: this.procesoEnEdicion?.tipo
        });

        this.procesoEnEdicion = null;
        this.procesoOriginal = null;
        this.limpiarCambios();
        this.hayChangiosPendientes = false;
    }

    /**
     * Limpiar cambios registrados
     * @private
     */
    limpiarCambios() {
        this.cambios = {
            ubicaciones: null,
            imagenes: null,
            observaciones: null,
            tallas: null
        };
    }

    /**
     * Verificar si hay cambios pendientes
     */
    tieneChangiosPendientes() {
        return this.hayChangiosPendientes;
    }

    /**
     * Obtener resumen de cambios para mostrar al usuario
     */
    obtenerResumenCambios() {
        const cambios = [];

        if (this.cambios.ubicaciones !== null) {
            cambios.push(`Ubicaciones: ${this.cambios.ubicaciones.length} nuevas`);
        }

        if (this.cambios.imagenes !== null) {
            cambios.push(`Imágenes: ${this.cambios.imagenes.length} imágenes`);
        }

        if (this.cambios.observaciones !== null) {
            cambios.push('Observaciones actualizadas');
        }

        if (this.cambios.tallas !== null) {
            cambios.push('Tallas actualizadas');
        }

        return cambios.length > 0 ? cambios : ['Sin cambios detectados'];
    }
}

// Crear instancia global
window.procesosEditor = new ProcesosEditor();
window.ProcesosEditor = ProcesosEditor;
