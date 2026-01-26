/**
 * EppMenuHandlers - Gestión de eventos para menú de EPP (3 puntos)
 * Maneja clicks en el menú contextual, edición y eliminación de EPP
 */

window.EppMenuHandlers = {
    _inicializado: false, // Flag para evitar múltiples inicializaciones
    
    /**
     * Inicializar event listeners para menús de EPP
     */
    inicializar() {
        // Evitar inicializar múltiples veces
        if (this._inicializado) {
            return;
        }
        
        this._inicializado = true;
        
        // Usar event delegation para que funcione con elementos agregados dinámicamente
        document.addEventListener('click', (e) => {
            const btnMenu = e.target.closest('.btn-menu-epp');
            const btnEditar = e.target.closest('.btn-editar-epp');
            const btnEliminar = e.target.closest('.btn-eliminar-epp');
            const esSubmenu = e.target.closest('.submenu-epp');

            // Clic en botón de 3 puntos
            if (btnMenu) {
                e.stopPropagation();
                this._toggleMenu(btnMenu);
                return; // Importante: evitar que se ejecute el cierre de menús
            }

            // Clic en botón EDITAR
            if (btnEditar) {
                e.stopPropagation();
                this._editarEpp(btnEditar);
                return; // Importante: evitar que se ejecute el cierre de menús
            }

            // Clic en botón ELIMINAR
            if (btnEliminar) {
                e.stopPropagation();
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

        // Obtener el submenu desde el item-epp-card (contenedor principal)
        const itemCard = btn.closest('.item-epp-card') || btn.closest('.item-epp');
        const submenu = itemCard ? itemCard.querySelector('.submenu-epp') : null;
        
        if (!submenu) {
            return;
        }

        // Posicionamiento
        const btnRect = btn.getBoundingClientRect();
        const submenuRect = submenu.getBoundingClientRect();
        const btnParent = btn.parentElement;
        const btnParentRect = btnParent.getBoundingClientRect();

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
        } else {
            submenu.style.display = 'none';
        }
    },

    /**
     * Editar EPP - Intenta obtener datos del DOM, gestionItemsUI o BD
     */
    _editarEpp(btn) {
        const itemId = btn.dataset.itemId;

        // Obtener el item EPP
        const item = btn.closest('.item-epp') || btn.closest('.item-epp-card');
        if (!item) {
            return;
        }

        // OPCIÓN 1: Intentar obtener del DOM (tarjeta)
        let eppData = this._extraerDatosDelDOM(item, itemId);
        
        // OPCIÓN 2: Si no está completo, buscar en window.gestionItemsUI
        if (!eppData || Object.keys(eppData).length < 3) {
            eppData = this._extraerDatosDelGestionItemsUI(itemId) || eppData;
        }

        // OPCIÓN 3: Si aún no tiene datos, traer de la DB
        if (!eppData || Object.keys(eppData).length < 3) {
            this._traerEPPDelaBD(itemId, item);
            return; // El resto de la lógica se ejecutará cuando lleguen los datos de la DB
        }

        // Si ya tenemos los datos, proceder a editar
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

            // IMPORTANTE: Priorizar imágenes del stateManager (si existen = cambios pendientes)
            // Si no hay en stateManager, extraer del DOM (imágenes originales)
            let imagenes = [];
            
            if (window.eppStateManager) {
                const imagenesState = window.eppStateManager.getImagenesSubidas();
                if (imagenesState && imagenesState.length > 0) {
                    // Usar imágenes del stateManager (reflejan eliminaciones)
                    imagenes = imagenesState;
                } else {
                    // Si stateManager está vacío, obtener del DOM
                    const todosLosImg = item.querySelectorAll('img');
                    todosLosImg.forEach((img, idx) => {
                        if (img.src && !img.src.includes('placeholder')) {
                            imagenes.push({
                                id: `${itemId}-img-${idx}`,
                                url: img.src,
                                ruta_web: img.src,
                                nombre: img.alt || `imagen-${idx}`
                            });
                        }
                    });
                }
            } else {
                // Fallback: si no hay stateManager, extraer del DOM
                const todosLosImg = item.querySelectorAll('img');
                todosLosImg.forEach((img, idx) => {
                    if (img.src && !img.src.includes('placeholder')) {
                        imagenes.push({
                            id: `${itemId}-img-${idx}`,
                            url: img.src,
                            ruta_web: img.src,
                            nombre: img.alt || `imagen-${idx}`
                        });
                    }
                });
            }

            const datos = {
                epp_id: parseInt(itemId),
                nombre,
                cantidad,
                observaciones,
                imagenes: imagenes,
                esEdicion: true  // Indicador de que es edición
            };
            return datos;
        } catch (error) {
            return null;
        }
    },

    /**
     * Extraer datos de window.gestionItemsUI
     */
    _extraerDatosDelGestionItemsUI(itemId) {
        try {
            if (!window.gestionItemsUI || !window.gestionItemsUI.ordenItems) {
                return null;
            }

            // Buscar el EPP en los items ordenados
            const item = window.gestionItemsUI.ordenItems.find(i => i.epp_id === parseInt(itemId));
            
            if (item) {
                return item;
            }
            
            return null;
        } catch (error) {
            return null;
        }
    },

    /**
     * Traer datos del EPP desde la BD
     */
    async _traerEPPDelaBD(itemId, item) {
        try {
            const response = await fetch(`/api/epp/${itemId}`);
            
            if (!response.ok) {
                return;
            }

            const data = await response.json();
            
            // Proceder con los datos de la BD
            const eppData = data.data || data;
            this._procederAEditarEPP(eppData, itemId, item);
            
        } catch (error) {
            alert('Error al cargar los datos del EPP');
        }
    },

    /**
     * Proceder a editar el EPP con los datos obtenidos
     */
    _procederAEditarEPP(eppData, itemId, item) {
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
            window.eppService.abrirModalEditarEPP(eppData);
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

        // Obtener el item
        const item = btn.closest('.item-epp') || btn.closest('.item-epp-card');
        if (!item) {
            return;
        }

        // Mostrar SweetAlert de confirmación
        Swal.fire({
            title: '¿Eliminar este EPP?',
            text: 'Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Eliminar',
            cancelButtonText: 'Cancelar',
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                this._confirmarEliminacion(item, itemId);
            }
        });
    },

    /**
     * Confirmar eliminación de EPP
     */
    _confirmarEliminacion(item, itemId) {
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

        // Actualizar contador si existe
        if (window.eppItemManager) {
            const total = window.eppItemManager.contarItems();
            
            // Actualizar UI del contador
            this._actualizarContadorItems(total);
        }
    },

    /**
     * Actualizar contador de items en la UI
     */
    _actualizarContadorItems(total) {
        // Opción 1: Actualizar el <span> en el h2 de "Ítems del Pedido"
        const seccion = document.getElementById('seccion-items-pedido');
        if (seccion) {
            const h2 = seccion.querySelector('h2');
            if (h2) {
                const span = h2.querySelector('span');
                if (span) {
                    span.textContent = total;
                }
            }
        }
        
        // Opción 2: Buscar y actualizar cualquier elemento que muestre "EPPs" seguido de un número
        const allText = document.body.innerText;
        const nodes = this._getAllTextNodes(document.body);
        nodes.forEach(node => {
            if (node.textContent.toLowerCase().includes('epps')) {
                const parent = node.parentElement;
                if (parent && parent.nextElementSibling) {
                    const nextEl = parent.nextElementSibling;
                    if (nextEl && !isNaN(nextEl.textContent)) {
                        nextEl.textContent = total;
                    }
                }
            }
        });
        
        // Opción 3: Buscar todos los spans con números y actualizar si están cerca de "EPP"
        document.querySelectorAll('span, div, p').forEach(el => {
            if (el.textContent.trim() === '1' || el.textContent.trim() === '0' || /^\d+$/.test(el.textContent.trim())) {
                const parent = el.parentElement;
                const sibling = el.previousElementSibling || el.nextElementSibling;
                if ((parent && parent.textContent.includes('EPP')) || (sibling && sibling.textContent.includes('EPP'))) {
                    if (/^\d+$/.test(el.textContent.trim())) {
                        el.textContent = total;
                    }
                }
            }
        });
    },

    /**
     * Helper: Obtener todos los text nodes
     */
    _getAllTextNodes(element) {
        const textNodes = [];
        const walk = document.createTreeWalker(
            element,
            NodeFilter.SHOW_TEXT,
            null,
            false
        );
        let node;
        while (node = walk.nextNode()) {
            textNodes.push(node);
        }
        return textNodes;
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
