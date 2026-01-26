/**
 * EppFormManager - Gestiona los formularios del modal de EPP
 * Patrón: Service Layer
 * Responsabilidad: Mostrar/ocultar/limpiar formularios
 */

class EppFormManager {
    constructor() {
        this.formularioCrearId = 'formularioEPPNuevo';
        this.buscadorId = 'inputBuscadorEPP';
        this.resultadosId = 'resultadosBuscadorEPP';
    }

    /**
     * Mostrar formulario de crear EPP nuevo
     */
    mostrarFormularioCrear() {
        document.getElementById(this.formularioCrearId).style.display = 'block';
        document.getElementById(this.buscadorId).value = '';
        document.getElementById(this.resultadosId).style.display = 'none';

    }

    /**
     * Ocultar formulario de crear EPP
     */
    ocultarFormularioCrear() {
        document.getElementById(this.formularioCrearId).style.display = 'none';
        this.limpiarFormularioCrear();

    }

    /**
     * Limpiar formulario de crear EPP
     */
    limpiarFormularioCrear() {
        const campos = [
            'nuevoEPPNombre',
            'nuevoEPPDescripcion'
        ];

        campos.forEach(id => {
            const elem = document.getElementById(id);
            if (elem) elem.value = '';
        });


    }

    /**
     * Obtener datos del formulario de crear EPP
     * Solo retorna nombre y descripción
     */
    obtenerDatosFormularioCrear() {
        return {
            nombre: document.getElementById('nuevoEPPNombre').value.trim(),
            descripcion: document.getElementById('nuevoEPPDescripcion').value.trim()
        };
    }

    /**
     * Limpiar buscador
     */
    limpiarBuscador() {
        document.getElementById(this.buscadorId).value = '';
        document.getElementById(this.resultadosId).style.display = 'none';
        document.getElementById(this.resultadosId).innerHTML = '';

    }

    /**
     * Mostrar resultados del buscador
     */
    mostrarResultadosBuscador(html) {
        const container = document.getElementById(this.resultadosId);
        container.innerHTML = html;
        container.style.display = 'block';

    }

    /**
     * Mostrar error en buscador
     */
    mostrarErrorBuscador(mensaje) {
        const container = document.getElementById(this.resultadosId);
        container.innerHTML = `<div style="padding: 1rem; color: #dc2626; text-align: center;">${mensaje}</div>`;
        container.style.display = 'block';

    }

    /**
     * Ocultar resultados del buscador
     */
    ocultarResultadosBuscador() {
        document.getElementById(this.resultadosId).style.display = 'none';

    }
}

// Exportar instancia global
window.eppFormManager = new EppFormManager();
