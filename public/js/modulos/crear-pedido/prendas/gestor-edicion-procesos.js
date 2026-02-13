/**
 * GestorEditacionProcesos - Maneja el flujo completo de edición de procesos
 * 
 * Responsabilidad: Orquestar la edición de un proceso dentro de una prenda
 * - Detectar si es edición o creación
 * - Guardar cambios en el buffer
 * - Aplicar cambios al guardar la prenda
 */

class GestorEditacionProcesos {
    constructor() {
        // Tracking de qué procesos fueron editados
        this.procesosEditados = new Map(); // Mapa de tipo => {id, cambios}
        this.procesoEnEdicionActual = null;
    }

    /**
     * Iniciar edición de un proceso existente
     * Diferencia entre crear uno nuevo y editar uno existente
     * 
     * @param {string} tipo - Tipo de proceso (reflectivo, bordado, etc)
     * @param {boolean} esNuevo - true si es nuevo, false si es edición
     */
    iniciarEdicion(tipo, esNuevo = false) {
        console.log(' [GESTOR-EDICION] Iniciando edición de proceso:', {
            tipo,
            esNuevo,
            hayProcesoEnEdicion: !!this.procesoEnEdicionActual
        });

        // Si hay un proceso en edición, guardarlo antes
        if (this.procesoEnEdicionActual && this.procesoEnEdicionActual.tipo !== tipo) {
            console.log('📌 [GESTOR-EDICION] Hay otro proceso en edición, guardando cambios...');
            this.guardarCambiosActuales();
        }

        // Establecer proceso actual
        this.procesoEnEdicionActual = {
            tipo: tipo,
            esNuevo: esNuevo
        };

        // Si es edición (no nuevo), iniciar el editor de procesos
        if (!esNuevo && window.procesosEditor) {
            const datosActuales = window.procesosSeleccionados[tipo]?.datos;
            if (datosActuales) {
                window.procesosEditor.iniciarEdicion(tipo, datosActuales);
                console.log(' [GESTOR-EDICION] Editor de procesos iniciado en modo edición');
            }
        }
    }

    /**
     * Guardar cambios del proceso actual
     * Se ejecuta automáticamente cuando se cierra el modal o se cambia de proceso
     */
    guardarCambiosActuales() {
        if (!this.procesoEnEdicionActual) {
            return;
        }

        const tipo = this.procesoEnEdicionActual.tipo;
        const esNuevo = this.procesoEnEdicionActual.esNuevo;

        console.log('💾 [GESTOR-EDICION] Guardando cambios del proceso:', {
            tipo,
            esNuevo,
            hayEditor: !!window.procesosEditor
        });

        //  Verificar si hay imágenes nuevas (Files) o existentes
        const tieneImagenesNuevas = window.imagenesProcesoActual?.some(img => img instanceof File);
        const tieneImagenesExistentes = window.imagenesProcesoExistentes?.length > 0;
        const tieneImagenes = tieneImagenesNuevas || tieneImagenesExistentes;

        console.log('📸 [GESTOR-EDICION] Verificación de imágenes:', {
            tieneImagenesNuevas,
            tieneImagenesExistentes,
            tieneImagenes
        });

        // Si es edición (no nuevo) y hay cambios en el editor O hay imágenes
        if (!esNuevo && window.procesosEditor && (window.procesosEditor.tieneChangiosPendientes() || tieneImagenes)) {
            const cambios = window.procesosEditor.obtenerCambios();
            const datosCompletos = window.procesosEditor.obtenerPayloadActualizacion();

            // Registrar que este proceso fue editado
            this.procesosEditados.set(tipo, {
                id: datosCompletos.id,
                tipo_proceso_id: datosCompletos.tipo_proceso_id,
                cambios: cambios,
                datosCompletos: datosCompletos
            });

            console.log(' [GESTOR-EDICION] Cambios registrados como editados:', {
                tipo,
                cambios: Object.keys(cambios),
                idProceso: datosCompletos.id,
                tieneImagenes
            });

            // Guardar en window.procesosSeleccionados para que se refleje inmediatamente
            window.procesosEditor.guardarEnWindowProcesos();
        }

        // Limpiar
        this.procesoEnEdicionActual = null;
    }

    /**
     * Obtener lista de procesos que fueron editados
     * Retorna solo los que tienen cambios registrados
     */
    obtenerProcesosEditados() {
        const editados = [];
        this.procesosEditados.forEach((valor, tipo) => {
            editados.push({
                tipo,
                ...valor
            });
        });

        console.log('📋 [GESTOR-EDICION] Procesos editados:', {
            cantidad: editados.length,
            tipos: editados.map(p => p.tipo),
            detalles: editados
        });

        return editados;
    }

    /**
     * Verificar si un proceso fue editado
     */
    fueeditado(tipo) {
        return this.procesosEditados.has(tipo);
    }

    /**
     * Limpiar registro de procesos editados
     * Se llama después de guardar la prenda exitosamente
     */
    limpiar() {
        console.log('🧹 [GESTOR-EDICION] Limpiando registro de procesos editados');
        this.procesosEditados.clear();
        this.procesoEnEdicionActual = null;
    }

    /**
     * Cancelar edición actual
     */
    cancelar() {
        if (this.procesoEnEdicionActual) {
            console.log(' [GESTOR-EDICION] Cancelando edición de:', this.procesoEnEdicionActual.tipo);
            
            // Cancelar en el editor si no es nuevo
            if (!this.procesoEnEdicionActual.esNuevo && window.procesosEditor) {
                window.procesosEditor.cancelarEdicion();
            }
        }

        this.procesoEnEdicionActual = null;
    }
}

// Crear instancia global
window.gestorEditacionProcesos = new GestorEditacionProcesos();
window.GestorEditacionProcesos = GestorEditacionProcesos;
