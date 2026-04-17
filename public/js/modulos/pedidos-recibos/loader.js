/**
 * loader.js
 * Cargador compatible para que funcione con <script> tradicionales
 * Expone el modulo en window para acceso desde HTML/Blade
 *
 * Uso en Blade:
 * <script type="module" src="{{ asset('js/modulos/pedidos-recibos/loader.js') }}?v=..."></script>
 */

(async () => {
    const currentUrl = (() => {
        try {
            return new URL(import.meta.url, window.location.origin);
        } catch (_) {
            return null;
        }
    })();

    const version = currentUrl?.searchParams?.get('v');
    const suffix = version ? `?v=${encodeURIComponent(version)}` : '';

    // Importar con el mismo versionado del loader para evitar cache stale del modulo interno.
    const [{ PedidosRecibosModule }, { Formatters }] = await Promise.all([
        import(`./PedidosRecibosModule.js${suffix}`),
        import(`./utils/Formatters.js${suffix}`)
    ]);

    // Inicializar modulo
    const module = new PedidosRecibosModule();

    // Exponer en window para compatibilidad
    window.PedidosRecibosModule = PedidosRecibosModule;
    window.pedidosRecibosModule = module;
    window.Formatters = Formatters;

    // Exponer API publica compatibilidad con codigo antiguo
    window.openOrderDetailModalWithProcess = (
        pedidoId,
        prendaId,
        tipoRecibo,
        prendaIndex = null,
        targetConsecutivo = null,
        targetReciboId = null
    ) => {
        return module.abrirRecibo(
            pedidoId,
            prendaId,
            tipoRecibo,
            prendaIndex,
            {
                targetConsecutivo,
                targetReciboId
            }
        );
    };

    window.cerrarModalRecibos = () => {
        return module.cerrarRecibo();
    };

    // Exponer funcion de impresion para compatibilidad con botones HTML
    setTimeout(() => {
        if (typeof printReceiptModal === 'function') {
            window.printReceiptModal = printReceiptModal;
        }
    }, 100);
})();
