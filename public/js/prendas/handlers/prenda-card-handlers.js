/**
 * PrendaCardHandlers - Manejadores de eventos para tarjeta de prenda
 * 
 * Responsabilidad: Gestionar todos los eventos de interacción de la tarjeta
 * Patrón: Observer + Event Delegation
 */

class PrendaCardHandlers {
    /**
     * Inicializar todos los event listeners de una tarjeta
     * @param {HTMLElement} tarjeta - Elemento tarjeta
     * @param {Object} prenda - Datos de la prenda
     * @param {number} prendaIndex - Índice de la prenda
     * @param {Object} callbacks - Callbacks de acciones (editar, eliminar, etc)
     */
    static inicializar(tarjeta, prenda, prendaIndex, callbacks = {}) {


        this._inicializarExpandibles(tarjeta, prendaIndex);
        this._inicializarMenuContextual(tarjeta, prendaIndex, prenda, callbacks);
        this._inicializarGalerias(tarjeta, prenda, prendaIndex);
        this._inicializarAcciones(tarjeta, prendaIndex, prenda, callbacks);
    }

    /**
     * Inicializar expandibles
     * @private
     */
    static _inicializarExpandibles(tarjeta, prendaIndex) {
        const botones = tarjeta.querySelectorAll('.btn-expandible-prenda');
        
        botones.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const seccionId = btn.getAttribute('data-seccion');
                const contenedor = tarjeta.querySelector(`[data-seccion-contenido="${seccionId}"]`);
                
                if (contenedor) {
                    const estaAbierto = contenedor.style.display !== 'none';
                    contenedor.style.display = estaAbierto ? 'none' : 'block';
                    btn.classList.toggle('expanded', !estaAbierto);
                    

                }
            });
        });
    }

    /**
     * Inicializar menú contextual (3 puntos)
     * @private
     */
    static _inicializarMenuContextual(tarjeta, prendaIndex, prenda, callbacks) {
        const btnMenu = tarjeta.querySelector('.btn-menu-prenda');
        const menu = tarjeta.querySelector('.menu-contextual-prenda');

        if (!btnMenu || !menu) return;

        btnMenu.addEventListener('click', (e) => {
            e.stopPropagation();
            menu.style.display = menu.style.display === 'none' ? 'flex' : 'none';
        });

        // Cerrar menu al hacer click afuera
        document.addEventListener('click', (e) => {
            if (!tarjeta.contains(e.target)) {
                menu.style.display = 'none';
            }
        });

        // Opciones del menú
        const btnEditar = menu.querySelector('[data-accion="editar"]');
        const btnEliminar = menu.querySelector('[data-accion="eliminar"]');

        if (btnEditar) {
            btnEditar.addEventListener('click', () => {
                menu.style.display = 'none';
                if (callbacks.onEditar) {
                    callbacks.onEditar(prendaIndex, prenda);
                }

            });
        }

        if (btnEliminar) {
            btnEliminar.addEventListener('click', () => {
                menu.style.display = 'none';
                this._confirmarEliminar(prendaIndex, callbacks);
            });
        }
    }

    /**
     * Inicializar galerías
     * @private
     */
    static _inicializarGalerias(tarjeta, prenda, prendaIndex) {
        // Galería de fotos
        const fotoPrincipal = tarjeta.querySelector('.foto-principal-readonly');
        if (fotoPrincipal) {
            fotoPrincipal.style.cursor = 'pointer';
            fotoPrincipal.addEventListener('click', () => {
                GaleriaService.abrirGaleriaFotos(prenda, prendaIndex);
            });
        }

        // Galería de telas
        const fotoTela = tarjeta.querySelector('.foto-tela-readonly');
        if (fotoTela) {
            fotoTela.style.cursor = 'pointer';
            fotoTela.addEventListener('click', () => {
                GaleriaService.abrirGaleriaTelas(prenda, prendaIndex);
            });
        }
    }

    /**
     * Inicializar acciones adicionales
     * @private
     */
    static _inicializarAcciones(tarjeta, prendaIndex, prenda, callbacks) {
        // Puede extenderse para más acciones en el futuro

    }

    /**
     * Confirmar eliminación
     * @private
     */
    static _confirmarEliminar(prendaIndex, callbacks) {
        if (!window.DeletionService) {

            return;
        }

        window.DeletionService.confirmarEliminacion(
            'Eliminar Prenda',
            '¿Estás seguro de que deseas eliminar esta prenda? Esta acción no se puede deshacer.',
            () => {
                if (callbacks.onEliminar) {
                    callbacks.onEliminar(prendaIndex);
                }

            }
        );
    }

    /**
     * Limpiar event listeners de una tarjeta
     * @param {HTMLElement} tarjeta - Elemento tarjeta
     */
    static limpiar(tarjeta) {
        if (!tarjeta) return;
        
        const clone = tarjeta.cloneNode(true);
        tarjeta.parentNode.replaceChild(clone, tarjeta);
        

    }
}

window.PrendaCardHandlers = PrendaCardHandlers;

