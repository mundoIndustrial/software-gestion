/**
 * EppMenuHandlers - Gestión de eventos para menú de EPP (3 puntos)
 * Maneja clicks en el menú contextual, edición y eliminación de EPP
 */

window.EppMenuHandlers = {
    /**
     * Inicializar event listeners para menús de EPP
     */
    inicializar() {
        console.log('[EppMenuHandlers]  Inicializando handlers de menú EPP');
        
        // Usar event delegation para que funcione con elementos agregados dinámicamente
        document.addEventListener('click', (e) => {
            const btnMenu = e.target.closest('.btn-menu-epp');
            const btnEditar = e.target.closest('.btn-editar-epp');
            const btnEliminar = e.target.closest('.btn-eliminar-epp');
            const esSubmenu = e.target.closest('.submenu-epp');

            // Clic en botón de 3 puntos
            if (btnMenu) {
                e.stopPropagation();
                console.log('[EppMenuHandlers]  Clic detectado en btn-menu-epp');
                this._toggleMenu(btnMenu);
                return; // Importante: evitar que se ejecute el cierre de menús
            }

            // Clic en botón EDITAR
            if (btnEditar) {
                e.stopPropagation();
                console.log('[EppMenuHandlers]  Clic detectado en btn-editar-epp');
                this._editarEpp(btnEditar);
                return; // Importante: evitar que se ejecute el cierre de menús
            }

            // Clic en botón ELIMINAR
            if (btnEliminar) {
                e.stopPropagation();
                console.log('[EppMenuHandlers]  Clic detectado en btn-eliminar-epp');
                this._eliminarEpp(btnEliminar);
                return; // Importante: evitar que se ejecute el cierre de menús
            }

            // Si se hace clic en el submenu, no cerrar
            if (esSubmenu) {
                return;
            }

            // Cerrar menú si se hace clic en cualquier otro lugar
            this._cerrarTodosLosMenus();
        });

    },

    /**
     * Toggle menú de EPP
     */
    _toggleMenu(btn) {
        const itemId = btn.dataset.itemId;
        console.log('[EppMenuHandlers] 📋 Toggle menú para item:', itemId);

        // Obtener el submenu desde el item-epp-card (contenedor principal)
        const itemCard = btn.closest('.item-epp-card') || btn.closest('.item-epp');
        const submenu = itemCard ? itemCard.querySelector('.submenu-epp') : null;
        
        if (!submenu) {
            console.warn('[EppMenuHandlers]  No se encontró submenu');
            console.log('[EppMenuHandlers] Item card:', itemCard);
            console.log('[EppMenuHandlers] HTML:', itemCard?.innerHTML);
            return;
        }

        console.log('[EppMenuHandlers] 🔍 Submenu encontrado');

        // Log de posicionamiento
        const btnRect = btn.getBoundingClientRect();
        const submenuRect = submenu.getBoundingClientRect();
        const btnParent = btn.parentElement;
        const btnParentRect = btnParent.getBoundingClientRect();
        
        console.log('[EppMenuHandlers] 📐 Botón:', {
            top: btnRect.top,
            left: btnRect.left,
            right: btnRect.right,
            bottom: btnRect.bottom
        });
        
        console.log('[EppMenuHandlers] 📐 Parent del botón:', {
            position: window.getComputedStyle(btnParent).position,
            top: btnParentRect.top,
            left: btnParentRect.left,
            right: btnParentRect.right,
            bottom: btnParentRect.bottom
        });
        
        console.log('[EppMenuHandlers] 📐 Submenu antes de mostrar:', {
            position: window.getComputedStyle(submenu).position,
            top: submenuRect.top,
            left: submenuRect.left,
            right: submenuRect.right,
            bottom: submenuRect.bottom,
            display: window.getComputedStyle(submenu).display
        });

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
            
            // Log después de mostrar
            setTimeout(() => {
                const submenuRectAfter = submenu.getBoundingClientRect();
                console.log('[EppMenuHandlers] Menú mostrado');
                console.log('[EppMenuHandlers] 📐 Submenu después de mostrar:', {
                    position: window.getComputedStyle(submenu).position,
                    top: submenuRectAfter.top,
                    left: submenuRectAfter.left,
                    right: submenuRectAfter.right,
                    bottom: submenuRectAfter.bottom,
                    display: window.getComputedStyle(submenu).display,
                    offsetTop: submenu.offsetTop,
                    offsetLeft: submenu.offsetLeft,
                    offsetParent: submenu.offsetParent?.className
                });
            }, 0);
        } else {
            submenu.style.display = 'none';
            console.log('[EppMenuHandlers] Menú ocultado');
        }
    },

    /**
     * Editar EPP - Intenta obtener datos del DOM, gestionItemsUI o BD
     */
    _editarEpp(btn) {
        const itemId = btn.dataset.itemId;
        console.log('[EppMenuHandlers] ✏️ Editar EPP:', itemId);

        // Obtener el item EPP
        const item = btn.closest('.item-epp') || btn.closest('.item-epp-card');
        if (!item) {
            console.warn('[EppMenuHandlers]  No se encontró item para editar');
            return;
        }

        // OPCIÓN 1: Intentar obtener del DOM (tarjeta)
        console.log('[EppMenuHandlers] 🔍 OPCIÓN 1: Extrayendo datos del DOM');
        let eppData = this._extraerDatosDelDOM(item, itemId);
        
        // OPCIÓN 2: Si no está completo, buscar en window.gestionItemsUI
        if (!eppData || Object.keys(eppData).length < 3) {
            console.log('[EppMenuHandlers] 🔍 OPCIÓN 2: Buscando en window.gestionItemsUI');
            eppData = this._extraerDatosDelGestionItemsUI(itemId) || eppData;
        }

        // OPCIÓN 3: Si aún no tiene datos, traer de la DB
        if (!eppData || Object.keys(eppData).length < 3) {
            console.log('[EppMenuHandlers] 🔍 OPCIÓN 3: Traer de la DB');
            this._traerEPPDelaBD(itemId, item);
            return; // El resto de la lógica se ejecutará cuando lleguen los datos de la DB
        }

        // Si ya tenemos los datos, proceder a editar
        console.log('[EppMenuHandlers] eppData final:', eppData);
        this._procederAEditarEPP(eppData, itemId, item);
    },

    /**
     * Extraer datos del DOM (tarjeta renderizada)
     */
    _extraerDatosDelDOM(item, itemId) {
        try {
            const nombre = item.querySelector('h4')?.textContent?.trim() || '';
            
            // Buscar cantidad - puede estar en diferentes lugares
            let cantidad = 0;
            const cantidadSpans = item.querySelectorAll('p');
            for (let span of cantidadSpans) {
                const text = span.textContent.toLowerCase();
                if (text.includes('cantidad')) {
                    cantidad = parseInt(span.nextElementSibling?.textContent) || 0;
                    break;
                }
            }
            
            // Buscar observaciones
            let observaciones = '';
            for (let span of cantidadSpans) {
                const text = span.textContent.toLowerCase();
                if (text.includes('observaciones')) {
                    observaciones = span.nextElementSibling?.textContent?.trim() || '';
                    break;
                }
            }

            // Extraer imágenes del DOM
            const imagenes = [];
            // Buscar en galerías, contenedores de imágenes, o cualquier img dentro del item
            const todosLosImg = item.querySelectorAll('img');
            todosLosImg.forEach((img, idx) => {
                // Ignorar imágenes de placeholder
                if (img.src && !img.src.includes('placeholder')) {
                    imagenes.push({
                        id: `${itemId}-img-${idx}`,
                        url: img.src,
                        ruta_web: img.src,
                        nombre: img.alt || `imagen-${idx}`
                    });
                    console.log('[EppMenuHandlers] 📷 Imagen extraída:', img.src);
                }
            });

            console.log('[EppMenuHandlers] 🖼️ Total imágenes encontradas:', imagenes.length);

            const datos = {
                epp_id: parseInt(itemId),
                nombre,
                cantidad,
                observaciones,
                imagenes: imagenes,
                esEdicion: true  // Indicador de que es edición
            };
            
            console.log('[EppMenuHandlers] Datos del DOM:', datos);
            return datos;
        } catch (error) {
            console.warn('[EppMenuHandlers]  Error extrayendo del DOM:', error);
            return null;
        }
    },

    /**
     * Extraer datos de window.gestionItemsUI
     */
    _extraerDatosDelGestionItemsUI(itemId) {
        try {
            if (!window.gestionItemsUI || !window.gestionItemsUI.ordenItems) {
                console.warn('[EppMenuHandlers]  window.gestionItemsUI no disponible');
                return null;
            }

            // Buscar el EPP en los items ordenados
            const item = window.gestionItemsUI.ordenItems.find(i => i.epp_id === parseInt(itemId));
            
            if (item) {
                console.log('[EppMenuHandlers] Datos de gestionItemsUI:', item);
                return item;
            }
            
            return null;
        } catch (error) {
            console.warn('[EppMenuHandlers]  Error en gestionItemsUI:', error);
            return null;
        }
    },

    /**
     * Traer datos del EPP desde la BD
     */
    async _traerEPPDelaBD(itemId, item) {
        try {
            console.log('[EppMenuHandlers] 🌐 Llamando a API para obtener EPP:', itemId);
            
            const response = await fetch(`/api/epp/${itemId}`);
            
            if (!response.ok) {
                console.warn('[EppMenuHandlers]  Error en la API:', response.status);
                return;
            }

            const data = await response.json();
            console.log('[EppMenuHandlers] Datos de la BD:', data);
            
            // Proceder con los datos de la BD
            const eppData = data.data || data;
            this._procederAEditarEPP(eppData, itemId, item);
            
        } catch (error) {
            console.error('[EppMenuHandlers]  Error obteniendo EPP de la BD:', error);
            alert('Error al cargar los datos del EPP');
        }
    },

    /**
     * Proceder a editar el EPP con los datos obtenidos
     */
    _procederAEditarEPP(eppData, itemId, item) {
        console.log('[EppMenuHandlers] 📋 Procesando edición con datos:', eppData);

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
            console.log('[EppMenuHandlers] 🔓 Abriendo modal de edición');
            window.eppService.abrirModalEditarEPP(eppData);
        } else {
            console.warn('[EppMenuHandlers]  eppService no disponible');
        }

        // Cerrar menú
        const submenu = item.querySelector('.submenu-epp');
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
            console.warn('[EppMenuHandlers]  No se encontró item para eliminar');
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
                <div style="font-size: 2.5rem; margin-bottom: 1rem; color: #dc2626;"></div>
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
        console.log('[EppMenuHandlers] Confirmando eliminación de EPP:', itemId);

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
        console.log('[EppMenuHandlers] EPP eliminado del DOM');

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
