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

        // En la vista de recibos-costura o insumos/materiales, solo permitir navegación entre recibos de COSTURA
        const esVistaRecibosCostura = window.location.pathname.includes('/recibos-costura');
        const esVistaInsumos = window.location.pathname.includes('/insumos/materiales');
        const state = modalManager.getState();
        let procesosActuales = state.procesosActuales;
        
        if (esVistaRecibosCostura || esVistaInsumos) {
            // Filtrar solo los recibos de tipo COSTURA
            procesosActuales = procesosActuales.filter(proceso => {
                const tipo = String(proceso.tipo || proceso.tipo_proceso || '').toUpperCase();
                return tipo === 'COSTURA';
            });
            
            console.log('NavigationManager: Filtrando solo COSTURA', {
                original: state.procesosActuales.length,
                filtrados: procesosActuales.length,
                vista: esVistaRecibosCostura ? 'recibos-costura' : 'insumos/materiales'
            });
        }

        // Mostrar/ocultar según cantidad de procesos filtrados
        if (procesosActuales.length <= 1) {
            arrowContainer.style.display = 'none';

            return;
        }

        arrowContainer.style.display = 'flex';
        this._aplicarEstilos(arrowContainer, prevArrow, nextArrow);

        // Encontrar el índice actual en los procesos filtrados
        const procesoActual = state.procesosActuales[state.procesoActualIndice];
        const tipoActual = String(procesoActual.tipo || procesoActual.tipo_proceso || '').toUpperCase();
        let indiceFiltradoActual = -1;
        
        if (esVistaRecibosCostura && tipoActual === 'COSTURA') {
            // Encontrar el índice del proceso actual en la lista filtrada
            indiceFiltradoActual = procesosActuales.findIndex(p => {
                const tipo = String(p.tipo || p.tipo_proceso || '').toUpperCase();
                return tipo === 'COSTURA' && 
                       (p.id === procesoActual.id || p.consecutivo === procesoActual.consecutivo);
            });
        }

        // Botón anterior
        prevArrow.style.display = indiceFiltradoActual > 0 ? 'flex' : 'none';
        prevArrow.onclick = () => this._irAnterior(
            modalManager, 
            prendaData, 
            onProcesoCambiado,
            esVistaRecibosCostura
        );

        // Botón siguiente
        nextArrow.style.display = indiceFiltradoActual >= 0 && indiceFiltradoActual < procesosActuales.length - 1 ? 'flex' : 'none';
        nextArrow.onclick = () => this._irSiguiente(
            modalManager, 
            prendaData, 
            onProcesoCambiado,
            esVistaRecibosCostura
        );
    }

    /**
     * Navega al proceso anterior
     */
    static _irAnterior(modalManager, prendaData, onProcesoCambiado, esVistaRecibosCostura = false) {
        const state = modalManager.getState();
        let procesosActuales = state.procesosActuales;
        
        // Si estamos en la vista de recibos-costura, usar solo los procesos COSTURA
        if (esVistaRecibosCostura) {
            procesosActuales = procesosActuales.filter(proceso => {
                const tipo = String(proceso.tipo || proceso.tipo_proceso || '').toUpperCase();
                return tipo === 'COSTURA';
            });
        }
        
        const procesoActual = state.procesosActuales[state.procesoActualIndice];
        let nuevoIndice = -1;
        
        if (esVistaRecibosCostura) {
            // Encontrar el índice actual en la lista filtrada
            const indiceFiltradoActual = procesosActuales.findIndex(p => {
                const tipo = String(p.tipo || p.tipo_proceso || '').toUpperCase();
                return tipo === 'COSTURA' && 
                       (p.id === procesoActual.id || p.consecutivo === procesoActual.consecutivo);
            });
            
            // Navegar al anterior en la lista filtrada
            if (indiceFiltradoActual > 0) {
                const procesoAnteriorFiltrado = procesosActuales[indiceFiltradoActual - 1];
                // Encontrar el índice de este proceso en la lista original
                nuevoIndice = state.procesosActuales.findIndex(p => 
                    (p.id === procesoAnteriorFiltrado.id || p.consecutivo === procesoAnteriorFiltrado.consecutivo)
                );
            }
        } else {
            // Navegación normal
            nuevoIndice = state.procesoActualIndice - 1;
        }

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
    static _irSiguiente(modalManager, prendaData, onProcesoCambiado, esVistaRecibosCostura = false) {
        const state = modalManager.getState();
        let procesosActuales = state.procesosActuales;
        
        // Si estamos en la vista de recibos-costura, usar solo los procesos COSTURA
        if (esVistaRecibosCostura) {
            procesosActuales = procesosActuales.filter(proceso => {
                const tipo = String(proceso.tipo || proceso.tipo_proceso || '').toUpperCase();
                return tipo === 'COSTURA';
            });
        }
        
        const procesoActual = state.procesosActuales[state.procesoActualIndice];
        let nuevoIndice = -1;
        
        if (esVistaRecibosCostura) {
            // Encontrar el índice actual en la lista filtrada
            const indiceFiltradoActual = procesosActuales.findIndex(p => {
                const tipo = String(p.tipo || p.tipo_proceso || '').toUpperCase();
                return tipo === 'COSTURA' && 
                       (p.id === procesoActual.id || p.consecutivo === procesoActual.consecutivo);
            });
            
            // Navegar al siguiente en la lista filtrada
            if (indiceFiltradoActual >= 0 && indiceFiltradoActual < procesosActuales.length - 1) {
                const procesoSiguienteFiltrado = procesosActuales[indiceFiltradoActual + 1];
                // Encontrar el índice de este proceso en la lista original
                nuevoIndice = state.procesosActuales.findIndex(p => 
                    (p.id === procesoSiguienteFiltrado.id || p.consecutivo === procesoSiguienteFiltrado.consecutivo)
                );
            }
        } else {
            // Navegación normal
            nuevoIndice = state.procesoActualIndice + 1;
        }

        if (nuevoIndice >= 0 && nuevoIndice < state.procesosActuales.length) {
            const nuevoRecibo = state.procesosActuales[nuevoIndice];
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
        
        // En la vista de recibos-costura, solo permitir navegación entre recibos de COSTURA
        const esVistaRecibosCostura = window.location.pathname.includes('/recibos-costura');
        let procesosFiltrados = procesosActuales;
        
        if (esVistaRecibosCostura) {
            // Filtrar solo los recibos de tipo COSTURA
            procesosFiltrados = procesosActuales.filter(proceso => {
                const tipo = String(proceso.tipo || proceso.tipo_proceso || '').toUpperCase();
                return tipo === 'COSTURA';
            });
        }

        // Si solo hay un proceso COSTURA o ninguno, ocultar flechas
        if (procesosFiltrados.length <= 1) {
            if (arrowContainer) arrowContainer.style.display = 'none';
            prevArrow.style.display = 'none';
            nextArrow.style.display = 'none';
            return;
        }

        // Mostrar flechas si hay múltiples procesos COSTURA
        if (arrowContainer) arrowContainer.style.display = 'flex';

        // Encontrar el índice actual en los procesos filtrados
        const procesoActual = procesosActuales[procesoActualIndice];
        const tipoActual = String(procesoActual.tipo || procesoActual.tipo_proceso || '').toUpperCase();
        let indiceFiltradoActual = -1;
        
        if (esVistaRecibosCostura && tipoActual === 'COSTURA') {
            indiceFiltradoActual = procesosFiltrados.findIndex(p => {
                const tipo = String(p.tipo || p.tipo_proceso || '').toUpperCase();
                return tipo === 'COSTURA' && 
                       (p.id === procesoActual.id || p.consecutivo === procesoActual.consecutivo);
            });
        }

        // Actualizar visibilidad de las flechas según el índice filtrado
        prevArrow.style.display = indiceFiltradoActual > 0 ? 'flex' : 'none';
        nextArrow.style.display = indiceFiltradoActual >= 0 && indiceFiltradoActual < procesosFiltrados.length - 1 ? 'flex' : 'none';
    }
}

