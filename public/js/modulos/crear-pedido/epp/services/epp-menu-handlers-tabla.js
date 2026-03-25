/**
 * EppMenuHandlerTabla - Gestor de menús EPP para vistas con estructura de TABLA
 * Hereda de EppMenuHandlerBase
 * Usado en: cotizaciones, vistas legacy con tablas
 */

class EppMenuHandlerTabla extends EppMenuHandlerBase {
    constructor() {
        super({
            contenedorId: 'tabla-items-pedido',
            itemSelector: '.item-epp',
            btnMenuSelector: '.btn-menu-epp',
            btnEditarSelector: '.btn-editar-epp',
            btnEliminarSelector: '.btn-eliminar-epp',
            submenuSelector: '.submenu-epp'
        });
        console.log('[EppMenuHandlerTabla]  Inicializado para estructura TABLA');
    }

    attachEventListeners() {
        console.log('[EppMenuHandlerTabla] 🎧 Configurando event listeners para tabla...');
        
        document.addEventListener('click', (e) => {
            // Click en botón de menú
            if (e.target.classList.contains(this.config.btnMenuSelector.substring(1))) {
                e.preventDefault();
                e.stopPropagation();
                console.log('[EppMenuHandlerTabla] Click en botón menú');
                this.toggleMenu(e.target);
            }

            // Click en botón editar
            if (e.target.classList.contains(this.config.btnEditarSelector.substring(1))) {
                e.preventDefault();
                e.stopPropagation();
                console.log('[EppMenuHandlerTabla] Click en botón editar');
                this.editarEPP(e.target);
            }

            // Click en botón eliminar
            if (e.target.classList.contains(this.config.btnEliminarSelector.substring(1))) {
                e.preventDefault();
                e.stopPropagation();
                console.log('[EppMenuHandlerTabla] Click en botón eliminar');
                this.eliminarEPP(e.target);
            }
        });

        // Cerrar menús al hacer clic fuera
        document.addEventListener('click', (e) => {
            const clickEnMenu = e.target.closest(this.config.submenuSelector) || 
                               e.target.closest(this.config.btnMenuSelector);
            if (!clickEnMenu) {
                this.cerrarTodosLosMenus();
            }
        });
        
        console.log('[EppMenuHandlerTabla]  Event listeners configurados');
    }
}

// Exportar instancia global
window.eppMenuHandlerTabla = new EppMenuHandlerTabla();
console.log('[EppMenuHandlerTabla]  Instancia global creada');
