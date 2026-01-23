/**
 * ================================================
 * PROTECTOR DE DATOS PRINCIPALES DEL PEDIDO
 * ================================================
 * 
 * Módulo de seguridad que previene que se limpien
 * accidentalmente datos principales del pedido:
 * - cliente_editable
 * - forma_de_pago_editable
 * - asesora_editable
 * - numero_pedido_editable
 * 
 * Este módulo actúa como una "barrera de seguridad"
 * que detecta intentos de limpiar estos campos
 * y los restaura si es necesario.
 */

class ProtectorDatosPrincipales {
    constructor() {
        this.datosPrincipales = {};
        this.guardados = false;
        this.inicializar();
    }

    inicializar() {

        
        // Esperar a que el DOM esté listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.guardarDatos());
        } else {
            this.guardarDatos();
        }
    }

    /**
     * Guardar datos principales del pedido
     */
    guardarDatos() {

        
        const camposAProteger = [
            'cliente_editable',
            'forma_de_pago_editable',
            'asesora_editable',
            'numero_pedido_editable'
        ];

        camposAProteger.forEach(fieldId => {
            const element = document.getElementById(fieldId);
            if (element) {
                this.datosPrincipales[fieldId] = element.value;

            }
        });

        // Agregar listener para monitor cambios accidentales
        this.iniciarMoniteo();
        this.guardados = true;

    }

    /**
     * Iniciar monitoreo de cambios
     */
    iniciarMoniteo() {
        // Monitorear cada 2 segundos para detectar limpiezas accidentales
        setInterval(() => {
            this.verificarIntegridad();
        }, 2000);
    }

    /**
     * Verificar si los datos fueron modificados sin autorización
     */
    verificarIntegridad() {
        if (!this.guardados) return;

        let datosCorruptos = false;

        Object.entries(this.datosPrincipales).forEach(([fieldId, valorOriginal]) => {
            const element = document.getElementById(fieldId);
            if (element) {
                const valorActual = element.value;

                // Si el valor está vacío pero NO debería estarlo (y no está readonly)
                if (valorActual === '' && valorOriginal !== '' && fieldId !== 'numero_pedido_editable') {

                    datosCorruptos = true;
                }
            }
        });

        if (datosCorruptos) {
            this.restaurarDatos();
        }
    }

    /**
     * Restaurar datos principales si fueron limpiados
     */
    restaurarDatos() {


        Object.entries(this.datosPrincipales).forEach(([fieldId, valor]) => {
            const element = document.getElementById(fieldId);
            if (element && valor !== '') {
                const valorAnterior = element.value;
                element.value = valor;



                // Disparar evento change para actualizar componentes
                const event = new Event('input', { bubbles: true });
                element.dispatchEvent(event);
            }
        });


    }

    /**
     * Actualizar datos guardados (cuando el usuario cambia voluntariamente)
     */
    actualizarDatos() {

        this.guardarDatos();
    }

    /**
     * Obtener datos guardados
     */
    obtenerDatos() {
        return { ...this.datosPrincipales };
    }
}

// Crear instancia global
window.protectorDatosPrincipales = new ProtectorDatosPrincipales();


