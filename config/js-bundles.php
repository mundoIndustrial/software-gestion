<?php

/**
 * JS Bundles Configuration
 * 
 * Each bundle concatenates multiple JS files into a single HTTP response.
 * This reduces ~120 HTTP requests to ~8, eliminating the waterfall bottleneck.
 * 
 * File order within each bundle is preserved (dependency order matters).
 * 
 * Usage in Blade:
 *   <script defer src="/js/bundle/crear-pedido-shared.js?v={{ config('app.asset_version') }}"></script>
 * 
 * Cache is invalidated when ASSET_VERSION changes in .env
 */

return [

    // ─── Bundle 1: Shared Services (loaded first) ───
    'crear-pedido-shared' => [
        'js/servicios/shared/event-bus.js',
        'js/servicios/shared/format-detector.js',
        'js/servicios/shared/shared-prenda-validation-service.js',
        'js/servicios/shared/shared-prenda-data-service.js',
        'js/servicios/shared/shared-prenda-storage-service.js',
        'js/servicios/shared/shared-prenda-editor-service.js',
        'js/servicios/shared/prenda-service-container.js',
        'js/servicios/shared/initialization-helper.js',
    ],

    // ─── Bundle 2: Config, Security, Constants, Image Storage ───
    'crear-pedido-config' => [
        'js/modulos/crear-pedido/seguridad/protector-datos-principales.js',
        'js/configuraciones/constantes-tallas.js',
        'js/modulos/crear-pedido/modales/modales-dinamicos.js',
        'js/services/epp/EppHttpService.js',
        'js/modulos/crear-pedido/fotos/image-storage-service.js',
    ],

    // ─── Bundle 3: EPP Services ───
    'crear-pedido-epp' => [
        'js/modulos/crear-pedido/epp/services/epp-api-service.js',
        'js/modulos/crear-pedido/epp/services/epp-state-manager.js',
        'js/modulos/crear-pedido/epp/services/epp-modal-manager.js',
        'js/modulos/crear-pedido/epp/services/epp-item-manager.js',
        'js/modulos/crear-pedido/epp/services/epp-imagen-manager.js',
        'js/modulos/crear-pedido/epp/services/epp-service.js',
        'js/modulos/crear-pedido/epp/services/epp-notification-service.js',
        'js/modulos/crear-pedido/epp/services/epp-creation-service.js',
        'js/modulos/crear-pedido/epp/services/epp-form-manager.js',
        'js/modulos/crear-pedido/epp/services/epp-menu-handlers.js',
        'js/modulos/crear-pedido/epp/templates/epp-modal-template.js',
        'js/modulos/crear-pedido/epp/interfaces/epp-modal-interface.js',
        'js/modulos/crear-pedido/epp/epp-init.js',
    ],

    // ─── Bundle 4: Core (tallas, telas, utilidades, procesos) ───
    'crear-pedido-core' => [
        'js/modulos/crear-pedido/tallas/gestion-tallas.js',
        'js/modulos/crear-pedido/telas/gestion-telas.js',
        'js/modulos/crear-pedido/procesos/gestion-items-pedido-constantes.js',
        'js/utilidades/dom-utils.js',
        'js/utilidades/modal-cleanup.js',
        'js/utilidades/tela-processor.js',
        'js/utilidades/prenda-data-builder.js',
        'js/utilidades/logger-app.js',
        'js/utilidades/validador-prenda.js',
        'js/modulos/crear-pedido/procesos/manejadores-procesos-prenda.js',
        'js/modulos/crear-pedido/procesos/gestor-modal-proceso-generico.js',
        'js/modulos/crear-pedido/procesos/renderizador-tarjetas-procesos.js',
        'js/componentes/procesos-imagenes-storage.js',
        'js/componentes/manejo-imagenes-proceso.js',
    ],

    // ─── Bundle 5: Prendas Services ───
    'crear-pedido-prendas' => [
        'js/modulos/crear-pedido/prendas/proceso-editor.js',
        'js/modulos/crear-pedido/prendas/gestor-edicion-procesos.js',
        'js/modulos/crear-pedido/prendas/servicio-procesos.js',
        'js/modulos/crear-pedido/prendas/middleware-guardado-prenda.js',
        'js/modulos/crear-pedido/prendas/notification-service.js',
        'js/modulos/crear-pedido/prendas/payload-normalizer.js',
        'js/modulos/crear-pedido/prendas/item-api-service.js',
        'js/modulos/crear-pedido/prendas/item-validator.js',
        'js/modulos/crear-pedido/prendas/item-form-collector.js',
        'js/modulos/crear-pedido/prendas/item-renderer.js',
        'js/modulos/crear-pedido/prendas/prenda-editor.js',
        'js/modulos/crear-pedido/prendas/prenda-editor-init.js',
        'js/modulos/crear-pedido/prendas/item-orchestrator.js',
        'js/componentes/prenda-form-collector.js',
        'js/modulos/crear-pedido/procesos/gestion-items-pedido.js',
        'js/modulos/crear-pedido/modales/modal-seleccion-prendas.js',
        'js/componentes/prendas-wrappers.js',
    ],

    // ─── Bundle 6: Gestores, Builders, Card Services, Componentes ───
    'crear-pedido-gestores' => [
        'js/modulos/crear-pedido/configuracion/api-pedidos-editable.js',
        'js/modulos/crear-pedido/fotos/manejador-fotos-prenda-edicion.js',
        'js/modulos/crear-pedido/fotos/galeria-imagenes-prenda.js',
        'js/modulos/crear-pedido/gestores/gestor-prenda-sin-cotizacion.js',
        'js/modulos/crear-pedido/prendas/inicializar-gestor.js',
        'js/modulos/crear-pedido/prendas/manejadores-variaciones.js',
        'js/prendas/utils/prenda-data-transformer.js',
        'js/prendas/builders/variaciones-builder.js',
        'js/prendas/builders/tallas-builder.js',
        'js/prendas/builders/procesos-builder.js',
        'js/componentes/services/image-converter-service.js',
        'js/componentes/services/prenda-card-service.js',
        'js/componentes/services/prenda-card-handlers.js',
        'js/componentes/prenda-card-readonly.js',
        'js/componentes/modal-prenda-dinamico-constantes.js',
        'js/componentes/modal-prenda-dinamico.js',
        'js/componentes/prenda-card-editar-simple.js',
    ],

    // ─── Bundle 7: Final UI Scripts ───
    'crear-pedido-ui' => [
        'js/modulos/crear-pedido/components/item-card-interactions.js',
        'js/componentes/prenda-editor-modal.js',
        'js/componentes/drag-drop-procesos-estilo-prenda.js',
    ],

    // ─── Bundle 8: Modal Prenda (colores-por-talla + drag-drop + FSM + loaders) ───
    'modal-prenda' => [
        'js/componentes/colores-por-talla/StateManager.js',
        'js/componentes/colores-por-talla/DOMUtils.js',
        'js/componentes/colores-por-talla/AsignacionManager.js',
        'js/componentes/colores-por-talla/WizardManager.js',
        'js/componentes/colores-por-talla/UIRenderer.js',
        'js/componentes/colores-por-talla/ColoresPorTalla.js',
        'js/componentes/colores-por-talla/compatibilidad.js',
        'js/componentes/colores-por-talla/diagnostico.js',
        'js/componentes/prendas-module/services/UIHelperService.js',
        'js/componentes/prendas-module/services/ClipboardService.js',
        'js/componentes/prendas-module/services/ContextMenuService.js',
        'js/componentes/prendas-module/services/DragDropEventHandler.js',
        'js/componentes/prendas-module/handlers/BaseDragDropHandler.js',
        'js/componentes/prendas-module/handlers/PrendaDragDropHandler.js',
        'js/componentes/prendas-module/handlers/TelaDragDropHandler.js',
        'js/componentes/prendas-module/handlers/ProcesoDragDropHandler.js',
        'js/componentes/prendas-module/drag-drop-manager.js',
        'js/modulos/crear-pedido/prendas/core/modal-mini-fsm.js',
        'js/modulos/crear-pedido/prendas/loaders/prenda-editor-basicos.js',
        'js/modulos/crear-pedido/prendas/loaders/prenda-editor-imagenes.js',
        'js/modulos/crear-pedido/prendas/loaders/prenda-editor-telas.js',
        'js/modulos/crear-pedido/prendas/loaders/prenda-editor-variaciones.js',
        'js/modulos/crear-pedido/prendas/loaders/prenda-editor-tallas.js',
        'js/modulos/crear-pedido/prendas/loaders/prenda-editor-colores.js',
        'js/modulos/crear-pedido/prendas/loaders/prenda-editor-procesos.js',
        'js/modulos/crear-pedido/prendas/modalHandlers/prenda-modal-manager.js',
        'js/modulos/crear-pedido/prendas/services/prenda-editor-service.js',
    ],

];
