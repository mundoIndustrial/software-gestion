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
        console.log('  [ProtectorDatosPrincipales] Inicializando...');
        
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
        console.log('💾 [ProtectorDatosPrincipales] Guardando datos principales...');
        
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
                console.log(`    ${fieldId}: "${element.value}"`);
            }
        });

        // Agregar listener para monitor cambios accidentales
        this.iniciarMoniteo();
        this.guardados = true;
        console.log(' [ProtectorDatosPrincipales] Datos guardados');
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
                    console.warn(`  [ProtectorDatosPrincipales] ALERTA: ${fieldId} fue limpiado accidentalmente!`);
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
        console.warn(' [ProtectorDatosPrincipales] Restaurando datos principales...');

        Object.entries(this.datosPrincipales).forEach(([fieldId, valor]) => {
            const element = document.getElementById(fieldId);
            if (element && valor !== '') {
                const valorAnterior = element.value;
                element.value = valor;

                console.log(`    ${fieldId} restaurado: "${valorAnterior}" → "${valor}"`);

                // Disparar evento change para actualizar componentes
                const event = new Event('input', { bubbles: true });
                element.dispatchEvent(event);
            }
        });

        console.log(' [ProtectorDatosPrincipales] Datos restaurados');
    }

    /**
     * Actualizar datos guardados (cuando el usuario cambia voluntariamente)
     */
    actualizarDatos() {
        console.log(' [ProtectorDatosPrincipales] Actualizando datos guardados...');
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

console.log(' Módulo ProtectorDatosPrincipales cargado');
