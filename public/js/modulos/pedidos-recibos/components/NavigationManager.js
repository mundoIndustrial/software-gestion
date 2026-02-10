/**
 * NavigationManager.js
 * Maneja la navegación entre procesos con flechas
 */

export class NavigationManager {
    /**
     * Configura las flechas de navegación
     */
    static configurarFlechas(modalManager, prendaData, onProcesoCambiado) {
        const prevArrow = document.getElementById('prev-arrow');
        const nextArrow = document.getElementById('next-arrow');
        const arrowContainer = document.querySelector('.arrow-container');

        if (!prevArrow || !nextArrow || !arrowContainer) {

            return;
        }

        // En el visualizador-logo se listan recibos individualmente; no permitir navegación por flechas
        const esVistaVisualizadorLogo = window.location.pathname.includes('/visualizador-logo/pedidos-logo');
        if (esVistaVisualizadorLogo) {
            arrowContainer.style.display = 'none';
            prevArrow.style.display = 'none';
            nextArrow.style.display = 'none';
            prevArrow.onclick = null;
            nextArrow.onclick = null;
            return;
        }

        const state = modalManager.getState();
        const procesosActuales = state.procesosActuales;
        const procesoActualIndice = state.procesoActualIndice;

        // Mostrar/ocultar según cantidad de procesos
        if (procesosActuales.length <= 1) {
            arrowContainer.style.display = 'none';

            return;
        }

        arrowContainer.style.display = 'flex';
        this._aplicarEstilos(arrowContainer, prevArrow, nextArrow);

        // Botón anterior
        prevArrow.style.display = procesoActualIndice > 0 ? 'flex' : 'none';
        prevArrow.onclick = () => this._irAnterior(
            modalManager, 
            prendaData, 
            onProcesoCambiado
        );

        // Botón siguiente
        nextArrow.style.display = procesoActualIndice < procesosActuales.length - 1 ? 'flex' : 'none';
        nextArrow.onclick = () => this._irSiguiente(
            modalManager, 
            prendaData, 
            onProcesoCambiado
        );
    }

    /**
     * Navega al proceso anterior
     */
    static _irAnterior(modalManager, prendaData, onProcesoCambiado) {
        const state = modalManager.getState();
        const nuevoIndice = state.procesoActualIndice - 1;

        if (nuevoIndice >= 0) {
            const nuevoRecibo = state.procesosActuales[nuevoIndice];
            const tipoRecibo = String(nuevoRecibo.tipo || nuevoRecibo.tipo_proceso || '');
            
            modalManager.setState({ procesoActualIndice: nuevoIndice });
            if (onProcesoCambiado) {
                onProcesoCambiado(prendaData, nuevoIndice, tipoRecibo);
            }
        }
    }

    /**
     * Navega al proceso siguiente
     */
    static _irSiguiente(modalManager, prendaData, onProcesoCambiado) {
        const state = modalManager.getState();
        const nuevoIndice = state.procesoActualIndice + 1;
        const procesosActuales = state.procesosActuales;

        if (nuevoIndice < procesosActuales.length) {
            const nuevoRecibo = procesosActuales[nuevoIndice];
            const tipoRecibo = String(nuevoRecibo.tipo || nuevoRecibo.tipo_proceso || '');
            
            modalManager.setState({ procesoActualIndice: nuevoIndice });
            if (onProcesoCambiado) {
                onProcesoCambiado(prendaData, nuevoIndice, tipoRecibo);
            }
        }
    }

    /**
     * Aplica estilos a los botones de navegación
     */
    static _aplicarEstilos(arrowContainer, prevArrow, nextArrow) {
        arrowContainer.style.justifyContent = 'center';
        arrowContainer.style.alignItems = 'center';
        arrowContainer.style.gap = '16px';

        [prevArrow, nextArrow].forEach(btn => {
            btn.style.display = 'flex';
            btn.style.alignItems = 'center';
            btn.style.justifyContent = 'center';
            btn.style.background = '#3b82f6';
            btn.style.color = 'white';
            btn.style.border = 'none';
            btn.style.borderRadius = '50%';
            btn.style.width = '40px';
            btn.style.height = '40px';
            btn.style.cursor = 'pointer';
            btn.style.transition = 'all 0.2s';

            btn.onmouseover = () => btn.style.background = '#2563eb';
            btn.onmouseout = () => btn.style.background = '#3b82f6';
        });
    }

    /**
     * Actualiza la visibilidad de las flechas (después de cambiar de proceso)
     */
    static actualizarVisibilidad(modalManager) {
        const prevArrow = document.getElementById('prev-arrow');
        const nextArrow = document.getElementById('next-arrow');
        const arrowContainer = document.querySelector('.arrow-container');
        
        if (!prevArrow || !nextArrow) return;

        // En el visualizador-logo no se usan flechas de navegación
        const esVistaVisualizadorLogo = window.location.pathname.includes('/visualizador-logo/pedidos-logo');
        if (esVistaVisualizadorLogo) {
            if (arrowContainer) arrowContainer.style.display = 'none';
            prevArrow.style.display = 'none';
            nextArrow.style.display = 'none';
            prevArrow.onclick = null;
            nextArrow.onclick = null;
            return;
        }

        const state = modalManager.getState();
        const { procesoActualIndice, procesosActuales } = state;

        prevArrow.style.display = procesoActualIndice > 0 ? 'flex' : 'none';
        nextArrow.style.display = procesoActualIndice < procesosActuales.length - 1 ? 'flex' : 'none';
    }
}

