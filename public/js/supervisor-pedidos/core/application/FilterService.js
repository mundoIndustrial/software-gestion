/**
 * Application Layer - FilterService
 * =====================================================
 * Lógica de negocio para filtros de la tabla de pedidos.
 * Gestiona estado de filtros en URL y carga opciones del servidor.
 *
 * Responsabilidades:
 *   - Leer/escribir parámetros de filtro en la URL
 *   - Cargar opciones de filtro desde el repositorio
 *   - Navegar con AJAX (SPA-like) dentro de supervisor-pedidos
 */

class FilterService {
    constructor(repository) {
        this.repository = repository;
    }

    /**
     * Obtiene los valores de filtro activos desde la URL actual
     * @param {string} columna - Nombre de la columna
     * @returns {string[]}
     */
    getActiveFilterValues(columna) {
        const url = new URL(window.location.href);

        if (columna === 'numero' || columna === 'id-orden') {
            return this._splitParam(url, 'numero');
        }
        if (columna === 'fecha') {
            const fechas = this._splitParam(url, 'fecha');
            if (fechas.length > 0) return fechas;

            const desde = url.searchParams.get('fecha_desde') || '';
            const hasta = url.searchParams.get('fecha_hasta') || '';
            return [desde, hasta].filter(Boolean);
        }
        if (columna === 'forma_pago' || columna === 'forma-pago') {
            return this._splitParam(url, 'forma_pago');
        }

        return this._splitParam(url, columna);
    }

    /**
     * Carga opciones de filtro del servidor
     * @param {string} campo - Campo a filtrar
     * @returns {Promise<{opciones: string[]}>}
     */
    async loadFilterOptions(campo) {
        if (!campo) {
            throw new PedidoValidationError('Campo de filtro requerido');
        }
        return await this.repository.getFilterOptions(campo);
    }

    /**
     * Construye la URL con los filtros aplicados
     * @param {string} columna - Columna del filtro
     * @param {string[]} valores - Valores seleccionados
     * @param {{desde?: string, hasta?: string}} fechas - Rango de fechas (solo para filtro fecha)
     * @returns {string} URL con filtros aplicados
     */
    buildFilteredUrl(columna, valores, fechas = {}) {
        const url = new URL(window.location.href);

        if (columna === 'fecha') {
            url.searchParams.delete('fecha');
            url.searchParams.delete('fecha_desde');
            url.searchParams.delete('fecha_hasta');

            if (valores.length > 0) {
                url.searchParams.set('fecha', valores.join(','));
                return url.toString();
            }

            // Compatibilidad: conservar soporte para rango si se usa desde otra vista.
            if (fechas.desde) url.searchParams.set('fecha_desde', fechas.desde);
            if (fechas.hasta) url.searchParams.set('fecha_hasta', fechas.hasta);
            return url.toString();
        }

        const paramName = this._resolveParamName(columna);
        url.searchParams.delete(paramName);

        if (valores.length > 0) {
            url.searchParams.set(paramName, valores.join(','));
        }

        return url.toString();
    }

    /**
     * Navega a una URL usando AJAX (reemplaza contenido del contenedor)
     * @param {string} urlString - URL destino
     * @param {{pushState?: boolean}} options
     * @returns {Promise<boolean>} true si la navegación fue exitosa
     */
    async navigateAjax(urlString, options = {}) {
        const { pushState = true } = options;
        const container = document.getElementById('supervisorPedidosIndexContent');

        if (!container) {
            window.location.href = urlString;
            return false;
        }

        try {
            container.style.opacity = '0.6';
            container.style.pointerEvents = 'none';

            // Mostrar overlay de carga
            const loadingOverlay = document.getElementById('sp-loading-overlay');
            if (loadingOverlay) {
                loadingOverlay.style.display = 'flex';
                loadingOverlay.style.opacity = '1';
            }

            const data = await this.repository.fetchOrdersData(urlString);

            if (typeof window.renderSupervisorOrdersTable !== 'function') {
                window.location.href = urlString;
                return false;
            }

            window.renderSupervisorOrdersTable(data);

            if (pushState) {
                window.history.pushState({ url: urlString }, '', urlString);
            }

            return true;
        } catch (e) {
            console.error('[FilterService] Error en navegación AJAX:', e);
            let shouldHardReload = true;
            try {
                const currentUrl = new URL(window.location.href, window.location.origin).toString();
                const targetUrl = new URL(urlString, window.location.origin).toString();
                shouldHardReload = currentUrl !== targetUrl;
            } catch (_) {
                shouldHardReload = true;
            }

            if (shouldHardReload) {
                window.location.href = urlString;
            }
            return false;
        } finally {
            container.style.opacity = '';
            container.style.pointerEvents = '';

            // Ocultar overlay de carga
            const overlay = document.getElementById('sp-loading-overlay');
            if (overlay) {
                overlay.style.opacity = '0';
                setTimeout(() => { overlay.style.display = 'none'; }, 300);
            }
        }
    }

    // ===== PRIVADOS =====

    _splitParam(url, name) {
        const raw = url.searchParams.get(name) || '';
        return raw.split(',').map(v => v.trim()).filter(Boolean);
    }

    _resolveParamName(columna) {
        if (columna === 'id-orden' || columna === 'numero') return 'numero';
        if (columna === 'forma-pago' || columna === 'forma_pago') return 'forma_pago';
        return columna;
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { FilterService };
} else {
    window.FilterService = FilterService;
}
