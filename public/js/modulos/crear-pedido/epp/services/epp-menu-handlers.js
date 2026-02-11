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
        console.log(' [_editarEpp] ===== CLICK EN EDITAR =====');
        const itemId = btn.dataset.itemId;
        console.log(' [_editarEpp] itemId:', itemId);
        
        // Obtener el item EPP
        const item = btn.closest('.item-epp') || btn.closest('.item-epp-card');
        if (!item) {
            console.error(' [_editarEpp] No se encontró elemento .item-epp o .item-epp-card');
            return;
        }
        console.log(' [_editarEpp] Elemento item encontrado:', item.className);

        // Obtener pedido_epp_id del DOM si está disponible
        const pedidoEppId = item.dataset.pedidoEppId || itemId;
        console.log(' [_editarEpp] pedidoEppId:', pedidoEppId);

        // OPCIÓN 1: Intentar obtener del DOM (tarjeta)
        console.log(' [_editarEpp] OPCIÓN 1: Extrayendo datos del DOM...');
        let eppData = this._extraerDatosDelDOM(item, itemId);
        console.log(' [_editarEpp] Datos del DOM:', eppData);
        console.log(' [_editarEpp] Cantidad de campos:', Object.keys(eppData).length);
        
        // OPCIÓN 2: Si no está completo, buscar en window.gestionItemsUI
        if (!eppData || Object.keys(eppData).length < 3) {
            console.log(' [_editarEpp] OPCIÓN 1 INCOMPLETA - Intentando OPCIÓN 2: gestionItemsUI...');
            eppData = this._extraerDatosDelGestionItemsUI(itemId) || eppData;
            console.log(' [_editarEpp] Datos de gestionItemsUI:', eppData);
        }

        // OPCIÓN 3: Si aún no tiene datos, traer de la DB
        if (!eppData || Object.keys(eppData).length < 3) {
            console.log(' [_editarEpp] OPCIÓN 2 INCOMPLETA - Intentando OPCIÓN 3: BD...');
            this._traerEPPDelaBD(itemId, item);
            return; // El resto de la lógica se ejecutará cuando lleguen los datos de la DB
        }

        // Agregar pedido_epp_id a los datos
        if (eppData && !eppData.pedido_epp_id) {
            eppData.pedido_epp_id = pedidoEppId;
        }
        console.log(' [_editarEpp] Datos finales con pedido_epp_id:', eppData);

        // Si ya tenemos los datos, proceder a editar
        console.log(' [_editarEpp] Datos completos encontrados - Procediendo a editar');
        this._procederAEditarEPP(eppData, itemId, item);
    },

    /**
     * Extraer datos del DOM (tarjeta renderizada)
     */
    _extraerDatosDelDOM(item, itemId) {
        console.log('🟪 [_extraerDatosDelDOM] Extrayendo datos del DOM para itemId:', itemId);
        try {
            const nombre = item.querySelector('h4')?.textContent?.trim() || '';
            console.log('🟪 [_extraerDatosDelDOM] Nombre encontrado:', nombre);
            
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
            console.log('🟪 [_extraerDatosDelDOM] Cantidad encontrada:', cantidad);
            
            // Buscar observaciones
            let observaciones = '';
            for (let span of cantidadSpans) {
                const text = span.textContent.toLowerCase();
                if (text.includes('observaciones')) {
                    observaciones = span.nextElementSibling?.textContent?.trim() || '';
                    break;
                }
            }
            console.log('🟪 [_extraerDatosDelDOM] Observaciones encontradas:', observaciones);

            // IMPORTANTE: Priorizar imágenes del stateManager (si existen = cambios pendientes)
            // Si no hay en stateManager, extraer del DOM (imágenes originales)
            let imagenes = [];
            
            if (window.eppStateManager) {
                const imagenesState = window.eppStateManager.getImagenesSubidas();
                if (imagenesState && imagenesState.length > 0) {
                    // Usar imágenes del stateManager (reflejan eliminaciones)
                    imagenes = imagenesState;
                    console.log('🟪 [_extraerDatosDelDOM] Imágenes de stateManager:', imagenes.length);
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
                    console.log('🟪 [_extraerDatosDelDOM] Imágenes del DOM (vacío stateManager):', imagenes.length);
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
                console.log('🟪 [_extraerDatosDelDOM] Imágenes del DOM (sin stateManager):', imagenes.length);
            }

            const datos = {
                epp_id: parseInt(itemId),
                nombre,
                cantidad,
                observaciones,
                imagenes: imagenes,
                esEdicion: true  // Indicador de que es edición
            };
            console.log('🟪 [_extraerDatosDelDOM] Datos finales:', datos);
            return datos;
        } catch (error) {
            console.error('🟪 [_extraerDatosDelDOM] Error:', error);
            return null;
        }
    },

    /**
     * Extraer datos de window.gestionItemsUI
     */
    _extraerDatosDelGestionItemsUI(itemId) {
        console.log('🟩 [_extraerDatosDelGestionItemsUI] Buscando itemId:', itemId);
        try {
            if (!window.gestionItemsUI || !window.gestionItemsUI.ordenItems) {
                console.log('🟩 [_extraerDatosDelGestionItemsUI] window.gestionItemsUI no disponible o sin ordenItems');
                return null;
            }

            console.log('🟩 [_extraerDatosDelGestionItemsUI] Buscando en', window.gestionItemsUI.ordenItems.length, 'items');
            // Buscar el EPP en los items ordenados
            const item = window.gestionItemsUI.ordenItems.find(i => i.epp_id === parseInt(itemId));
            
            if (item) {
                console.log('🟩 [_extraerDatosDelGestionItemsUI] Item encontrado:', item);
                return item;
            }
            
            console.log('🟩 [_extraerDatosDelGestionItemsUI] Item NO encontrado en gestionItemsUI');
            return null;
        } catch (error) {
            console.error('🟩 [_extraerDatosDelGestionItemsUI] Error:', error);
            return null;
        }
    },

    /**
     * Traer datos del EPP desde la BD
     */
    async _traerEPPDelaBD(itemId, item) {
        console.log(' [_traerEPPDelaBD] Obteniendo datos de BD para itemId:', itemId);
        try {
            console.log(' [_traerEPPDelaBD] Llamando a /api/epp/' + itemId);
            const response = await fetch(`/api/epp/${itemId}`);
            
            if (!response.ok) {
                console.error(' [_traerEPPDelaBD] Error en response:', response.status, response.statusText);
                return;
            }

            const data = await response.json();
            console.log(' [_traerEPPDelaBD] Datos recibidos de BD:', data);
            
            // Proceder con los datos de la BD
            const eppData = data.data || data;
            console.log(' [_traerEPPDelaBD] EPP Data extraída:', eppData);
            this._procederAEditarEPP(eppData, itemId, item);
            
        } catch (error) {
            console.error(' [_traerEPPDelaBD] Error:', error);
            alert('Error al cargar los datos del EPP');
        }
    },

    /**
     * Proceder a editar el EPP con los datos obtenidos
     */
    _procederAEditarEPP(eppData, itemId, item) {
        console.log(' [_procederAEditarEPP] ===== INICIANDO TRANSFORMACIÓN =====');
        console.log(' [_procederAEditarEPP] Datos recibidos:', eppData);
        
        // Transformar datos para que sean compatibles con editarEPPAgregado
        // Los datos pueden venir de diferentes fuentes y tienen estructuras diferentes
        const eppDataTransformado = {
            epp_id: eppData.epp_id || eppData.id,
            id: eppData.epp_id || eppData.id,
            nombre_epp: eppData.nombre_epp || eppData.nombre_completo || eppData.nombre || '',
            nombre: eppData.nombre_epp || eppData.nombre_completo || eppData.nombre || '',
            cantidad: eppData.cantidad || 1,
            observaciones: eppData.observaciones || '',
            imagenes: eppData.imagenes || [],
            imagen: eppData.imagen || null,
        };
        console.log(' [_procederAEditarEPP] Datos transformados:', eppDataTransformado);
        
        // Crear evento personalizado con los datos
        const evento = new CustomEvent('epp:editar', {
            detail: {
                itemId,
                eppData: eppDataTransformado,
                elemento: item
            }
        });
        console.log(' [_procederAEditarEPP] Despachando evento personalizado "epp:editar"');
        document.dispatchEvent(evento);

        // Cerrar menú primero
        const submenu = item.querySelector('.submenu-epp');
        if (submenu) submenu.style.display = 'none';
        console.log(' [_procederAEditarEPP] Menú cerrado');

        // Usar setTimeout para asegurar que la función esté disponible
        // Esto permite que el Blade template se cargue
        console.log(' [_procederAEditarEPP] Esperando 100ms para llamar a editarEPPAgregado...');
        setTimeout(() => {
            if (typeof window.editarEPPAgregado === 'function') {
                console.log('🟥 [_procederAEditarEPP]  LLAMANDO A window.editarEPPAgregado() ');
                console.log('🟥 [_procederAEditarEPP] Con datos:', eppDataTransformado);
                window.editarEPPAgregado(eppDataTransformado);
            } else {
                console.warn('🟥 [_procederAEditarEPP]  window.editarEPPAgregado NO disponible');
                console.warn('🟥 [_procederAEditarEPP] Funciones disponibles en window:', Object.keys(window).filter(k => k.includes('edit') || k.includes('epp')));
                if (window.eppService && typeof window.eppService.abrirModalEditarEPP === 'function') {
                    console.log('🟥 [_procederAEditarEPP] Usando servicio antiguo para editar EPP');

                    window.eppService.abrirModalEditarEPP(eppDataTransformado);
                } else {
                    console.error('[EppMenuHandlers]  No hay función disponible para editar EPP');
                }
            }
        }, 100);
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
