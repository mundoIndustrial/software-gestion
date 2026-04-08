/**
 * EppMenuHandlerTarjeta - Gestor de menús EPP para vistas con estructura de TARJETAS
 * Hereda de EppMenuHandlerBase
 * Usado en: create-nuevo, edit mode
 */

console.log('[EppMenuHandlerTarjeta]  Script cargado - Versión con herencia');

class EppMenuHandlerTarjeta extends EppMenuHandlerBase {
    constructor() {
        const config = {
            contenedorId: 'lista-items-pedido',
            itemSelector: '.item-epp-card-nuevo',
            botonMenuSelector: '.btn-menu-epp-nuevo',
            menuSelector: '.submenu-epp-nuevo',
            botonesAccion: {
                editar: '.btn-editar-epp-nuevo',
                eliminar: '.btn-eliminar-epp-nuevo'
            }
        };
        super(config);
        console.log('[EppMenuHandlerTarjeta]  Constructor completado con herencia de EppMenuHandlerBase');
    }

    attachEventListeners() {
        console.log('[EppMenuHandlerTarjeta]  Configurando event listeners para TARJETA...');
        
        // Event delegation para menús dinámicos
        document.addEventListener('click', (e) => {
            // Detectar botón de menú del EPP
            if (e.target.classList.contains('btn-menu-epp-nuevo')) {
                e.preventDefault();
                e.stopPropagation();
                console.log('[EppMenuHandlerTarjeta]  Click en botón menú EPP detectado');
                this.toggleMenu(e.target);
            }

            // Botón de editar
            if (e.target.classList.contains('btn-editar-epp-nuevo')) {
                e.preventDefault();
                e.stopPropagation();
                console.log('[EppMenuHandlerTarjeta]  Click en botón editar EPP detectado');
                this.editarEPP(e.target);
            }

            // Botón de eliminar
            if (e.target.classList.contains('btn-eliminar-epp-nuevo')) {
                e.preventDefault();
                e.stopPropagation();
                console.log('[EppMenuHandlerTarjeta]  Click en botón eliminar EPP detectado');
                this.eliminarEPP(e.target);
            }
        });

        // Cerrar menús al hacer clic fuera
        document.addEventListener('click', (e) => {
            const clickEnMenu = e.target.closest('.submenu-epp-nuevo') || e.target.closest('.btn-menu-epp-nuevo');
            if (!clickEnMenu) {
                this.cerrarTodosLosMenus();
            }
        });
        
        console.log('[EppMenuHandlerTarjeta]  Event listeners configurados correctamente');
    }

    editarEPP(btn) {
        const eppId = btn.getAttribute('data-item-id');
        console.log(`[${this.constructor.name}] Editando EPP: ${eppId}`);

        const tarjeta = document.querySelector(`.item-epp-card-nuevo[data-epp-id="${eppId}"]`);
        if (!tarjeta) {
            console.error(`[${this.constructor.name}] Tarjeta no encontrada: ${eppId}`);
            return;
        }

        this.cerrarTodosLosMenus();

        // Lógica de edición
        if (typeof window.abrirModalEditarEPP === 'function') {
            window.abrirModalEditarEPP({ 
                id: eppId,
                tarjetaId: eppId  // Usar eppId como tarjetaId (se busca por data-epp-id)
            });
        } else {
            console.warn(`[${this.constructor.name}] abrirModalEditarEPP no disponible`);
        }
    }
}

console.log('[EppMenuHandlerTarjeta]  Creando instancia global...');
window.eppMenuHandlerTarjeta = new EppMenuHandlerTarjeta();
console.log('[EppMenuHandlerTarjeta]  Instancia global creada');
