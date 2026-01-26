/**
 * EppMenuHandlers - Gestión de eventos para menú de EPP (3 puntos)
 * Maneja clicks en el menú contextual, edición y eliminación de EPP
 */

window.EppMenuHandlers = {
    /**
     * Inicializar event listeners para menús de EPP
     */
    inicializar() {
        console.log('[EppMenuHandlers] 🔧 Inicializando handlers de menú EPP');
        
        // Usar event delegation para que funcione con elementos agregados dinámicamente
        document.addEventListener('click', (e) => {
            // Clic en botón de 3 puntos
            if (e.target.closest('.btn-menu-epp')) {
                e.stopPropagation();
                this._toggleMenu(e.target.closest('.btn-menu-epp'));
            }

            // Clic en botón EDITAR
            if (e.target.closest('.btn-editar-epp')) {
                e.stopPropagation();
                this._editarEpp(e.target.closest('.btn-editar-epp'));
            }

            // Clic en botón ELIMINAR
            if (e.target.closest('.btn-eliminar-epp')) {
                e.stopPropagation();
                this._eliminarEpp(e.target.closest('.btn-eliminar-epp'));
            }

            // Cerrar menú si se hace clic en otro lugar
            if (!e.target.closest('.btn-menu-epp') && !e.target.closest('.submenu-epp')) {
                this._cerrarTodosLosMenus();
            }
        });

        console.log('[EppMenuHandlers] ✅ Event listeners inicializados');
    },

    /**
     * Toggle menú de EPP
     */
    _toggleMenu(btn) {
        const itemId = btn.dataset.itemId;
        console.log('[EppMenuHandlers] 📋 Toggle menú para item:', itemId);

        // Obtener el submenu hermano
        const submenu = btn.nextElementSibling;
        if (!submenu || !submenu.classList.contains('submenu-epp')) {
            console.warn('[EppMenuHandlers] ⚠️ No se encontró submenu hermano');
            return;
        }

        console.log('[EppMenuHandlers] 🔍 Submenu encontrado');

        // Cerrar otros menús primero
        document.querySelectorAll('.submenu-epp').forEach(menu => {
            if (menu !== submenu) {
                menu.style.display = 'none';
            }
        });

        // Mostrar/ocultar este menú
        const isHidden = submenu.style.display === 'none' || submenu.style.display === '';
        if (isHidden) {
            submenu.style.display = 'flex';
            submenu.style.flexDirection = 'column';
            console.log('[EppMenuHandlers] ✅ Menú mostrado');
        } else {
            submenu.style.display = 'none';
            console.log('[EppMenuHandlers] ✅ Menú ocultado');
        }
    },

    /**
     * Editar EPP
     */
    _editarEpp(btn) {
        const itemId = btn.dataset.itemId;
        console.log('[EppMenuHandlers] ✏️ Editar EPP:', itemId);

        // Obtener el item EPP
        const item = btn.closest('.item-epp') || btn.closest('.item-epp-card');
        if (!item) {
            console.warn('[EppMenuHandlers] ⚠️ No se encontró item para editar');
            return;
        }

        // Buscar los datos del EPP en window.itemsPedido
        let eppData = null;
        if (window.itemsPedido && Array.isArray(window.itemsPedido)) {
            eppData = window.itemsPedido.find(item => item.tipo === 'epp' && item.epp_id === parseInt(itemId));
        }

        if (!eppData) {
            console.warn('[EppMenuHandlers] ⚠️ No se encontraron datos del EPP');
            alert('No se pudieron cargar los datos del EPP');
            return;
        }

        console.log('[EppMenuHandlers] 📦 Datos del EPP encontrados:', eppData);

        // Crear evento personalizado con los datos
        const evento = new CustomEvent('epp:editar', {
            detail: {
                itemId,
                eppData,
                elemento: item
            }
        });
        document.dispatchEvent(evento);

        // Abrir modal de edición con los datos
        if (window.eppService && typeof window.eppService.abrirModalEditarEPP === 'function') {
            console.log('[EppMenuHandlers] 🔓 Abriendo modal de edición con datos');
            window.eppService.abrirModalEditarEPP(eppData);
        } else {
            console.warn('[EppMenuHandlers] ⚠️ Función abrirModalEditarEPP no disponible');
        }

        // Cerrar menú
        const submenu = btn.closest('.submenu-epp');
        if (submenu) submenu.style.display = 'none';
    },

    /**
     * Eliminar EPP
     */
    _eliminarEpp(btn) {
        const itemId = btn.dataset.itemId;
        console.log('[EppMenuHandlers] 🗑️ Eliminar EPP:', itemId);

        // Obtener el item
        const item = btn.closest('.item-epp') || btn.closest('.item-epp-card');
        if (!item) {
            console.warn('[EppMenuHandlers] ⚠️ No se encontró item para eliminar');
            return;
        }

        // Mostrar modal de confirmación
        this._mostrarModalConfirmacion(item, itemId);
    },

    /**
     * Mostrar modal de confirmación para eliminar
     */
    _mostrarModalConfirmacion(item, itemId) {
        // Crear overlay
        const overlay = document.createElement('div');
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            animation: fadeIn 0.3s ease;
        `;

        // Crear modal
        const modal = document.createElement('div');
        modal.style.cssText = `
            background: white;
            border-radius: 12px;
            padding: 2rem;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15);
            animation: slideUp 0.3s ease;
        `;

        modal.innerHTML = `
            <div style="text-align: center;">
                <div style="font-size: 2.5rem; margin-bottom: 1rem; color: #dc2626;">⚠️</div>
                <h3 style="margin: 0 0 0.5rem 0; font-size: 1.25rem; font-weight: 600; color: #1f2937;">Eliminar EPP</h3>
                <p style="margin: 0 0 1.5rem 0; font-size: 0.95rem; color: #6b7280; line-height: 1.5;">¿Deseas eliminar este EPP del pedido? Esta acción no se puede deshacer.</p>
                
                <div style="display: flex; gap: 0.75rem; justify-content: center;">
                    <button class="btn-modal-cancelar" style="padding: 0.75rem 1.5rem; background: #e5e7eb; border: 1px solid #d1d5db; border-radius: 8px; cursor: pointer; font-size: 0.9rem; font-weight: 500; color: #1f2937; transition: all 0.2s ease;"
                        onmouseover="this.style.background = '#d1d5db';"
                        onmouseout="this.style.background = '#e5e7eb';">
                        Cancelar
                    </button>
                    <button class="btn-modal-confirmar" data-item-id="${itemId}" style="padding: 0.75rem 1.5rem; background: #dc2626; border: 1px solid #991b1b; border-radius: 8px; cursor: pointer; font-size: 0.9rem; font-weight: 500; color: white; transition: all 0.2s ease;"
                        onmouseover="this.style.background = '#b91c1c';"
                        onmouseout="this.style.background = '#dc2626';">
                        Eliminar
                    </button>
                </div>
            </div>
        `;

        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        // Agregar estilos de animación si no existen
        if (!document.querySelector('#epp-modal-animations')) {
            const style = document.createElement('style');
            style.id = 'epp-modal-animations';
            style.textContent = `
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                @keyframes slideUp {
                    from { transform: translateY(20px); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
            `;
            document.head.appendChild(style);
        }

        // Evento para cancelar
        modal.querySelector('.btn-modal-cancelar').addEventListener('click', () => {
            overlay.remove();
        });

        // Evento para confirmar
        modal.querySelector('.btn-modal-confirmar').addEventListener('click', () => {
            overlay.remove();
            this._confirmarEliminacion(item, itemId);
        });

        // Cerrar al hacer clic en el overlay
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.remove();
            }
        });
    },

    /**
     * Confirmar eliminación de EPP
     */
    _confirmarEliminacion(item, itemId) {
        console.log('[EppMenuHandlers] ✅ Confirmando eliminación de EPP:', itemId);

        // Disparar evento personalizado
        const evento = new CustomEvent('epp:eliminar', {
            detail: {
                itemId,
                elemento: item
            }
        });
        document.dispatchEvent(evento);

        // Eliminar del DOM
        item.remove();
        console.log('[EppMenuHandlers] ✅ EPP eliminado del DOM');

        // Actualizar contador si existe
        if (window.eppItemManager) {
            const total = window.eppItemManager.contarItems();
            console.log('[EppMenuHandlers] 📊 EPPs restantes:', total);
        }
    },

    /**
     * Cerrar todos los menús
     */
    _cerrarTodosLosMenus() {
        document.querySelectorAll('.submenu-epp').forEach(menu => {
            menu.style.display = 'none';
        });
        console.log('[EppMenuHandlers] 🔒 Todos los menús cerrados');
    }
};

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.EppMenuHandlers.inicializar();
    });
} else {
    // DOM ya está listo
    window.EppMenuHandlers.inicializar();
}
