/**
 *  Módulo de Asignación de Colores por Talla
 * Responsabilidad: Cargar y mostrar asignación de colores
 */

class PrendaEditorColores {
    /**
     * Cargar asignación de colores por talla
     */
    static cargar(prenda) {
        console.log(' [Colores] Cargando asignaciones:', {
            cantidad: prenda.asignaciones?.length || 0
        });
        
        const tabla = document.getElementById('tabla-resumen-asignaciones-cuerpo');
        if (!tabla) {
            console.warn(' [Colores] No encontrado #tabla-resumen-asignaciones-cuerpo');
            return;
        }
        
        // Si no hay asignaciones, salir
        if (!prenda.asignaciones || !Array.isArray(prenda.asignaciones) || prenda.asignaciones.length === 0) {
            console.log(' [Colores] Sin asignaciones para cargar');
            this._ocultarSeccion();
            return;
        }
        
        // Limpiar tabla
        tabla.innerHTML = '';
        
        // Cargar asignaciones
        prenda.asignaciones.forEach((asignacion, idx) => {
            const fila = this._crearFilaAsignacion(asignacion, idx);
            tabla.appendChild(fila);
            console.log(` [Colores] Asignación ${idx + 1}: ${asignacion.tela} - ${asignacion.talla}`);
        });
        
        // Mostrar sección y actualizar contador
        this._mostrarSeccion();
        this._actualizarContadores(prenda);
        
        //  Replicar a global para que sea editable
        if (prenda.asignacionesColoresPorTalla) {
            window.ColoresPorTalla = window.ColoresPorTalla || {};
            window.ColoresPorTalla.datos = JSON.parse(JSON.stringify(prenda.asignacionesColoresPorTalla));
            console.log('[Carga]  Asignaciones de colores replicadas');
        }
        
        console.log(' [Colores] Completado');
    }

    /**
     * Crear fila de asignación para la tabla
     * @private
     */
    static _crearFilaAsignacion(asignacion, idx) {
        const fila = document.createElement('tr');
        fila.innerHTML = `
            <td>${asignacion.tela || asignacion.tela_nombre || '-'}</td>
            <td>${asignacion.genero || asignacion.genero_nombre || '-'}</td>
            <td>${asignacion.talla || '-'}</td>
            <td>${asignacion.color || asignacion.color_nombre || '-'}</td>
            <td style="text-align: center;">
                <input type="number" class="form-control" value="${asignacion.cantidad || 0}" min="0" style="width: 80px;">
            </td>
            <td style="text-align: center;">
                <button type="button" class="btn btn-sm btn-danger" 
                    onclick="eliminarAsignacion(${idx})">
                    ✕
                </button>
            </td>
        `;
        return fila;
    }

    /**
     * Actualizar contadores
     * @private
     */
    static _actualizarContadores(prenda) {
        // Contador de asignaciones
        const contador = document.getElementById('contador-asignaciones');
        if (contador) {
            contador.value = prenda.asignaciones.length;
        }
        
        // Total de cantidades
        const totalAsignaciones = document.getElementById('total-asignaciones-resumen');
        if (totalAsignaciones && prenda.asignaciones.length > 0) {
            let total = 0;
            prenda.asignaciones.forEach(a => {
                total += parseInt(a.cantidad) || 0;
            });
            totalAsignaciones.textContent = total;
        }
    }

    /**
     * Mostrar sección de asignaciones
     * @private
     */
    static _mostrarSeccion() {
        const msgVacio = document.getElementById('msg-resumen-vacio');
        if (msgVacio) msgVacio.style.display = 'none';
        
        const seccion = document.getElementById('seccion-resumen-asignaciones');
        if (seccion) seccion.style.display = 'block';
    }

    /**
     * Ocultar sección de asignaciones
     * @private
     */
    static _ocultarSeccion() {
        const msgVacio = document.getElementById('msg-resumen-vacio');
        if (msgVacio) msgVacio.style.display = 'block';
        
        const seccion = document.getElementById('seccion-resumen-asignaciones');
        if (seccion) seccion.style.display = 'none';
    }

    /**
     * Limpiar asignaciones
     */
    static limpiar() {
        const tabla = document.getElementById('tabla-resumen-asignaciones-cuerpo');
        if (tabla) tabla.innerHTML = '';
        
        const contador = document.getElementById('contador-asignaciones');
        if (contador) contador.value = '';
        
        const totalAsignaciones = document.getElementById('total-asignaciones-resumen');
        if (totalAsignaciones) totalAsignaciones.textContent = '0';
        
        this._ocultarSeccion();
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PrendaEditorColores;
}
