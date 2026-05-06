/**
 * EppMenuHandlerBase - Clase base abstracta para gestión de menús EPP
 * Patrón: Template Method + Factory
 * Proporciona lógica común para todas las variantes de menú (tabla, tarjeta, etc)
 */

class EppMenuHandlerBase {
    constructor(config = {}) {
        console.log(`[${this.constructor.name}]  Constructor iniciado`);
        
        this.inicializado = false;
        this.observer = null;
        this.eventListenersConfigurados = new Set();
        
        // Configuración sobrescribible por subclases
        this.config = {
            contenedorId: 'lista-items-pedido',
            itemSelector: '.item-epp-card',
            btnMenuSelector: '.btn-menu-epp',
            btnEditarSelector: '.btn-editar-epp',
            btnEliminarSelector: '.btn-eliminar-epp',
            submenuSelector: '.submenu-epp',
            ...config
        };
        
        this.inicializar();
        this.setupMutationObserver();
        console.log(`[${this.constructor.name}]  Constructor completado`);
    }

    inicializar() {
        console.log(`[${this.constructor.name}]  Inicializando...`);
        if (!this.inicializado) {
            console.log(`[${this.constructor.name}]  Primer inicialización - configurando listeners`);
            this.attachEventListeners();
            this.inicializado = true;
            console.log(`[${this.constructor.name}]  Inicializado correctamente`);
        } else {
            console.warn(`[${this.constructor.name}]  Ya fue inicializado, evitando duplicación`);
        }
    }

    /**
     * Método virtual - debe ser implementado por subclases
     */
    attachEventListeners() {
        throw new Error(`[${this.constructor.name}] attachEventListeners() debe ser implementado por la subclase`);
    }

    setupMutationObserver() {
        const contenedor = document.getElementById(this.config.contenedorId);
        if (!contenedor) {
            console.warn(`[${this.constructor.name}] No se encontró contenedor #${this.config.contenedorId}`);
            return;
        }

        this.observer = new MutationObserver((mutations) => {
            let nuevosItems = false;
            
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            if (node.classList.contains('item-epp-card')) {
                                nuevosItems = true;
                                console.log(`[${this.constructor.name}] Nuevo EPP detectado`);
                                this.setupItemMenu(node);
                            }
                            
                            const nuevosEnContenedor = node.querySelectorAll('.item-epp-card');
                            nuevosEnContenedor.forEach((item) => {
                                nuevosItems = true;
                                this.setupItemMenu(item);
                            });
                        }
                    });
                }
            });
            
            if (nuevosItems) {
                setTimeout(() => this.verificarTodosLosBotones(), 100);
            }
        });

        this.observer.observe(contenedor, {
            childList: true,
            subtree: true
        });
        
        console.log(`[${this.constructor.name}] MutationObserver configurado`);
    }

    setupItemMenu(itemElement) {
        const botonMenu = itemElement.querySelector(this.config.btnMenuSelector);
        if (botonMenu) {
            const itemId = botonMenu.getAttribute('data-item-id');
            
            if (this.eventListenersConfigurados.has(itemId)) {
                return;
            }
            
            const computedStyle = window.getComputedStyle(botonMenu);
            if (computedStyle.pointerEvents === 'none') {
                botonMenu.style.pointerEvents = 'auto';
            }
            
            const submenu = itemElement.querySelector(`${this.config.submenuSelector}[data-item-id="${itemId}"]`);
            if (submenu) {
                submenu.style.display = 'none';
            }
            
            this.eventListenersConfigurados.add(itemId);
        }
    }

    verificarTodosLosBotones() {
        const botonesMenu = document.querySelectorAll(this.config.btnMenuSelector);
        console.log(`[${this.constructor.name}] Verificando ${botonesMenu.length} botones de menú...`);
        
        this.cerrarTodosLosMenus();
        
        botonesMenu.forEach((btn) => {
            const itemId = btn.getAttribute('data-item-id');
            const computedStyle = window.getComputedStyle(btn);
            
            if (computedStyle.pointerEvents === 'none') {
                btn.style.pointerEvents = 'auto';
            }
            
            const submenu = document.querySelector(`${this.config.submenuSelector}[data-item-id="${itemId}"]`);
            if (submenu) {
                submenu.style.display = 'none';
                if (!this.eventListenersConfigurados.has(itemId)) {
                    this.eventListenersConfigurados.add(itemId);
                }
            }
        });
    }

    toggleMenu(btn) {
        const itemId = btn.getAttribute('data-item-id');
        console.log(`[${this.constructor.name}] toggleMenu: ${itemId}`);
        
        const submenu = document.querySelector(`${this.config.submenuSelector}[data-item-id="${itemId}"]`);
        if (!submenu) {
            console.error(`[${this.constructor.name}] Submenú no encontrado: ${itemId}`);
            return;
        }

        const estabaVisible = window.getComputedStyle(submenu).display !== 'none';
        this.cerrarTodosLosMenus();

        if (!estabaVisible) {
            submenu.style.display = 'flex';
        }
        console.log(`[${this.constructor.name}] Menú ${itemId}: ${submenu.style.display}`);
    }

    cerrarTodosLosMenus() {
        const todosLosMenus = document.querySelectorAll(this.config.submenuSelector);
        todosLosMenus.forEach((menu) => {
            menu.style.display = 'none';
        });
    }

    editarEPP(btn) {
        const tarjetaId = btn.getAttribute('data-item-id');
        console.log(`[${this.constructor.name}] Editando EPP: ${tarjetaId}`);

        const selectorItem = this.config?.itemSelector || '.item-epp-card';
        const tarjeta = document.querySelector(`${selectorItem}[data-epp-id="${tarjetaId}"]`);
        if (!tarjeta) {
            console.error(`[${this.constructor.name}] Tarjeta no encontrada: ${tarjetaId}`);
            return;
        }

        this.cerrarTodosLosMenus();

        // Lógica de edición
        const eppOriginalIdRaw = tarjeta.getAttribute('data-epp-original-id');
        const pedidoEppIdRaw = tarjeta.getAttribute('data-pedido-epp-id');
        const nombre = tarjeta.querySelector('h4, h5')?.textContent?.trim() || null;
        const eppOriginalId = eppOriginalIdRaw && /^\d+$/.test(String(eppOriginalIdRaw)) ? Number(eppOriginalIdRaw) : null;
        const pedidoEppId = pedidoEppIdRaw && /^\d+$/.test(String(pedidoEppIdRaw)) ? Number(pedidoEppIdRaw) : null;

        if (typeof window.abrirModalEditarEPP === 'function') {
            window.abrirModalEditarEPP({
                id: tarjetaId,
                tarjetaId: tarjetaId,
                epp_id: eppOriginalId,
                data_epp_original_id: eppOriginalId,
                pedido_epp_id: pedidoEppId,
                nombre: nombre,
                nombre_epp: nombre,
                nombre_completo: nombre
            });
        } else {
            console.warn(`[${this.constructor.name}] abrirModalEditarEPP no disponible`);
        }
    }

    eliminarEPP(btn) {
        const itemId = btn.getAttribute('data-item-id');
        console.log(`[${this.constructor.name}] Eliminando EPP: ${itemId}`);

        this.cerrarTodosLosMenus();

        if (window.Swal) {
            Swal.fire({
                title: '¿Eliminar este EPP?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.realizarEliminacion(itemId);
                }
            });
        } else {
            console.error(`[${this.constructor.name}] Swal no disponible para confirmar eliminacion de EPP`);
        }
    }

    realizarEliminacion(tarjetaId) {
        try {
            if (typeof window.eliminarItemPedidoSeguro === 'function') {
                const eliminadoSeguro = window.eliminarItemPedidoSeguro(tarjetaId);
                if (eliminadoSeguro) {
                    if (window.Swal) {
                        Swal.fire('Eliminado', 'EPP eliminado correctamente', 'success');
                    }
                    console.log(`[${this.constructor.name}] EPP ${tarjetaId} eliminado (ruta segura)`);
                    return;
                }
            }

            // Fuente unica de verdad: GestionItemsUI (estado) + ItemRenderer (DOM)
            const gestion = window.gestionItemsUI;
            if (!gestion || typeof gestion.eliminarEPPPorTarjetaId !== 'function') {
                console.error(`[${this.constructor.name}] gestionItemsUI.eliminarEPPPorTarjetaId no disponible`);
                return;
            }

            const eliminado = gestion.eliminarEPPPorTarjetaId(tarjetaId);
            if (!eliminado) {
                console.warn(`[${this.constructor.name}] No se pudo eliminar EPP ${tarjetaId} desde GestionItemsUI`);
                return;
            }

            if (typeof gestion._actualizarRenderItemsOrdenadosSinBloquear === 'function') {
                gestion._actualizarRenderItemsOrdenadosSinBloquear();
            } else if (typeof gestion._actualizarRenderItemsOrdenados === 'function') {
                gestion._actualizarRenderItemsOrdenados();
            }

            if (window.Swal) {
                Swal.fire('Eliminado', 'EPP eliminado correctamente', 'success');
            }

            console.log(`[${this.constructor.name}] EPP ${tarjetaId} eliminado`);
        } catch (error) {
            console.error(`[${this.constructor.name}] Error al eliminar:`, error);
        }
    }

    refrescar() {
        console.log(`[${this.constructor.name}] Refrescando`);
    }
}
