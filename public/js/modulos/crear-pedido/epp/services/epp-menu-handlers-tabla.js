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

    /**
     * Sobrescribir editarEPP para usar selectores de tabla
     * En tabla, buscamos .item-epp[data-item-id] en lugar de .item-epp-card[data-epp-id]
     */
    editarEPP(btn) {
        const tarjetaId = btn.getAttribute('data-item-id');
        console.log(`[${this.constructor.name}] Editando EPP: ${tarjetaId}`);

        // Para tabla usamos .item-epp con data-item-id
        const tarjeta = document.querySelector(`.item-epp[data-item-id="${tarjetaId}"]`);
        if (!tarjeta) {
            console.error(`[${this.constructor.name}] Tarjeta no encontrada: ${tarjetaId}`);
            return;
        }

        this.cerrarTodosLosMenus();

        // Lógica de edición
        const eppOriginalIdRaw = tarjeta.getAttribute('data-pedido-epp-id');
        const tipoRaw = tarjeta.getAttribute('data-tipo');
        const nombre = tarjeta.querySelector('span')?.textContent?.trim() || null;
        const eppOriginalId = eppOriginalIdRaw && /^\d+$/.test(String(eppOriginalIdRaw)) ? Number(eppOriginalIdRaw) : null;
        const tipo = tipoRaw || 'epp';

        if (typeof window.abrirModalEditarEPP === 'function') {
            window.abrirModalEditarEPP({
                id: tarjetaId,
                tarjetaId: tarjetaId,
                epp_id: eppOriginalId,
                data_epp_original_id: eppOriginalId,
                pedido_epp_id: eppOriginalId,
                nombre: nombre,
                nombre_epp: nombre,
                nombre_completo: nombre,
                tipo: tipo
            });
        } else {
            console.warn(`[${this.constructor.name}] abrirModalEditarEPP no disponible`);
        }
    }

    attachEventListeners() {
        console.log('[EppMenuHandlerTabla]  Configurando event listeners para tabla...');
        
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
