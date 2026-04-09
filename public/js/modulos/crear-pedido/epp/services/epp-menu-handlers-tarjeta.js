/**
 * EppMenuHandlerTarjeta - Gestor de menus EPP para vistas con estructura de TARJETAS
 * Hereda de EppMenuHandlerBase
 * Usado en: create-nuevo, edit mode
 */

console.log('[EppMenuHandlerTarjeta] Script cargado - version con herencia');

class EppMenuHandlerTarjeta extends EppMenuHandlerBase {
    constructor() {
        const config = {
            contenedorId: 'lista-items-pedido',
            itemSelector: '.item-epp-card-nuevo',
            btnMenuSelector: '.btn-menu-epp-nuevo',
            btnEditarSelector: '.btn-editar-epp-nuevo',
            btnEliminarSelector: '.btn-eliminar-epp-nuevo',
            submenuSelector: '.submenu-epp-nuevo'
        };
        super(config);
        console.log('[EppMenuHandlerTarjeta] Constructor completado con herencia de EppMenuHandlerBase');
    }

    attachEventListeners() {
        console.log('[EppMenuHandlerTarjeta] Configurando event listeners para TARJETA...');

        // Event delegation para menus dinamicos
        document.addEventListener('click', (e) => {
            const btnMenu = e.target.closest('.btn-menu-epp-nuevo');
            if (btnMenu) {
                e.preventDefault();
                e.stopPropagation();
                console.log('[EppMenuHandlerTarjeta] Click en boton menu EPP detectado');
                this.toggleMenu(btnMenu);
            }

            const btnEditar = e.target.closest('.btn-editar-epp-nuevo');
            if (btnEditar) {
                e.preventDefault();
                e.stopPropagation();
                console.log('[EppMenuHandlerTarjeta] Click en boton editar EPP detectado');
                this.editarEPP(btnEditar);
            }

            const btnEliminar = e.target.closest('.btn-eliminar-epp-nuevo');
            if (btnEliminar) {
                e.preventDefault();
                e.stopPropagation();
                console.log('[EppMenuHandlerTarjeta] Click en boton eliminar EPP detectado');
                this.eliminarEPP(btnEliminar);
            }
        });

        // Cerrar menus al hacer clic fuera
        document.addEventListener('click', (e) => {
            const clickEnMenu = e.target.closest('.submenu-epp-nuevo') || e.target.closest('.btn-menu-epp-nuevo');
            if (!clickEnMenu) {
                this.cerrarTodosLosMenus();
            }
        });

        console.log('[EppMenuHandlerTarjeta] Event listeners configurados correctamente');
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

        if (typeof window.abrirModalEditarEPP === 'function') {
            window.abrirModalEditarEPP({
                id: eppId,
                tarjetaId: eppId
            });
        } else {
            console.warn(`[${this.constructor.name}] abrirModalEditarEPP no disponible`);
        }
    }
}

console.log('[EppMenuHandlerTarjeta] Creando instancia global...');
window.eppMenuHandlerTarjeta = new EppMenuHandlerTarjeta();
console.log('[EppMenuHandlerTarjeta] Instancia global creada');
