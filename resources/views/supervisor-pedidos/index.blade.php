@extends('supervisor-pedidos.layout')

@section('title', 'Supervisión de Pedidos')
@section('page-title', 'Supervisión de Pedidos')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/supervisor-pedidos/index.css') }}">
    <!-- CSS para modal-agregar-prenda-nueva y formularios de edición -->
    <link rel="stylesheet" href="{{ asset('css/crear-pedido.css') }}">
    <link rel="stylesheet" href="{{ asset('css/crear-pedido-editable.css') }}">
    <link rel="stylesheet" href="{{ asset('css/form-modal-consistency.css') }}">
    <link rel="stylesheet" href="{{ asset('css/swal-z-index-fix.css') }}">
    <link rel="stylesheet" href="{{ asset('css/componentes/prendas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/modales/modal-exito-pedido.css') }}">
    <link rel="stylesheet" href="{{ asset('css/modulos/epp-modal.css') }}">
    
@endpush

@section('content')
<div class="supervisor-pedidos-container">

    <div id="supervisorPedidosIndexContent">

    @include('supervisor-pedidos.partials.tabla-ordenes')
    </div>

</div>

@include('supervisor-pedidos.partials.modales')



<!-- Modal Overlay y Wrapper para Detalles de Orden -->
<div id="dropdowns-container" style="position: fixed; top: 0; left: 0; z-index: 999999; pointer-events: none;"></div>

<div id="modal-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 9997; display: none;" onclick="closeModalOverlay()"></div>

<div id="order-detail-modal-wrapper" style="width: 90%; max-width: 90vw; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto; display: none; border-radius: 8px;">
    <x-orders-components.order-detail-modal />
</div>

<!-- Modal Wrapper para Detalles de Orden - LOGO -->
<div id="order-detail-modal-wrapper-logo" style="width: 90%; max-width: 90vw; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto; display: none; border-radius: 8px;">
    <x-orders-components.order-detail-modal-logo />
</div>

<!-- Modal Comparar Pedido y Cotización -->
<x-supervisor-pedidos.modal-comparar-pedido />

<!-- Modal Seguimiento del Pedido -->
<x-orders-components.order-tracking-modal />

<!-- Modal para Selector de Recibos (desde asesores) -->
@include('components.modals.recibos-process-selector')

<!-- Modal Editar Pedido (desde asesores) - Componente completo para edición de pedidos -->
@include('asesores.pedidos.components.modal-editar-pedido')

<!-- Componentes de módulos de edición (desde asesores) -->
@include('asesores.pedidos.components.modal-prendas-lista')
@include('asesores.pedidos.components.modal-agregar-prenda')
@include('asesores.pedidos.modals.modal-agregar-prenda-nueva')
@include('asesores.pedidos.components.modal-editar-prenda')
<!-- Modal Agregar EPP (mismo modal que en creación) -->
@include('asesores.pedidos.modals.modal-agregar-editar-epp')

@include('asesores.pedidos.components.modal-editar-epp')

<!-- Modal para Seleccionar Tallas -->
@include('asesores.pedidos.modals.modal-seleccionar-tallas')

<!-- Modal Selector de Modo de Proceso -->
@include('asesores.pedidos.modals.modal-selector-modo-proceso')
@include('asesores.pedidos.modals.modal-proceso-por-tallas')

<!-- Modal para Editar Procesos Genéricos -->
@include('asesores.pedidos.modals.modal-proceso-generico')

<!-- Modal para Confirmar Eliminación de Imagen de Proceso -->
@include('asesores.pedidos.modals.modal-confirmar-eliminar-imagen-proceso')

@push('scripts')
    <!--  MODALES DE ACCIONES (CARGADO TEMPRANO - Aprobación, Anulación, Ocultación) -->
    <script src="{{ asset('js/supervisor-pedidos/modales-acciones.js') }}?v={{ time() }}"></script>
    
    <!--  SERVICIOS CENTRALIZADOS (Requeridos para modal-editar-pedido) -->
    <script src="{{ asset('js/utilidades/validation-service.js') }}"></script>
    <script src="{{ asset('js/utilidades/ui-modal-service.js') }}"></script>
    <script src="{{ asset('js/utilidades/deletion-service.js') }}"></script>
    <script src="{{ asset('js/utilidades/galeria-service.js') }}"></script>
    
    <!--  SERVICIO DE ALMACENAMIENTO DE IMÁGENES (Requerido para agregar/eliminar imágenes) -->
    <script src="{{ asset('js/modulos/crear-pedido/fotos/image-storage-service.js') }}"></script>
    
    <!--  LAZY LOADERS: Cargan módulos bajo demanda (Requeridos para modal-editar-pedido) -->
    <script src="{{ asset('js/lazy-loaders/prenda-editor-preloader.js') }}"></script>
    <script src="{{ asset('js/lazy-loaders/prenda-editor-loader-modular.js') }}"></script>
    <script src="{{ asset('js/lazy-loaders/epp-manager-loader.js') }}"></script>
    <script defer src="{{ asset('js/componentes/epp-agregar-pedido.js') }}"></script>

    <!-- Scripts para edición de prendas desde lista de pedidos (requeridos por editarPrendaDePedido) -->
    <script defer src="{{ asset('js/modulos/crear-pedido/prendas/prenda-editor.js') }}"></script>
    <script defer src="{{ asset('js/modulos/crear-pedido/tallas/gestion-tallas.js') }}"></script>
    <script defer src="{{ asset('js/modulos/crear-pedido/telas/telas-module/manejo-imagenes.js') }}"></script>
    <script defer src="{{ asset('js/componentes/prenda-form-collector.js') }}"></script>
    <script defer src="{{ asset('js/componentes/prenda-editor-pedidos-adapter.js') }}"></script>
    <script defer src="{{ asset('js/componentes/prenda-agregar-pedido.js') }}"></script>
    
    <!-- Inicializador de servicios de imágenes -->
    <script src="{{ asset('js/modulos/crear-pedido/inicializadores/init-storage-servicios.js') }}"></script>
    
    <!-- Manejadores de procesos - Para edición de procesos desde supervisor -->
    <script src="{{ asset('js/modulos/crear-pedido/procesos/manejadores-procesos-prenda.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/gestor-modal-proceso-generico.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/selector-modo-proceso.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/gestor-modal-proceso-por-tallas.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/extension-editor-tallas-multiproducto.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/extension-guardar-datos-tallas-extendida.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/renderizador-tarjetas-procesos.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/componentes/procesos-imagenes-storage.js') }}"></script>
    <script src="{{ asset('js/componentes/manejo-imagenes-proceso.js') }}"></script>
    <script src="{{ asset('js/componentes/manejador-imagen-proceso-con-indice.js') }}"></script>
    
    <!-- Scripts para funcionalidad de asesores - Módulos Desacoplados -->
    <script src="{{ asset('js/asesores/pedidos-dropdown-simple.js') }}"></script>
    <script src="{{ asset('js/asesores/observaciones-despacho.js') }}"></script>
    <script src="{{ asset('js/asesores/pedidos-modal-edit.js') }}"></script>
    <!-- Scripts para Vista Previa en Vivo de Factura - Módulos Desacoplados -->
    <script src="{{ asset('js/modulos/invoice/ImageGalleryManager.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/invoice/FormDataCaptureService.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/invoice/InvoiceRenderer.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/invoice/ModalManager.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/invoice/InvoiceExportService.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/invoice-preview-live.js') }}"></script>
    <!-- Scripts para Vista de Factura desde Lista - Lazy Loading -->
    <script src="{{ asset('js/modulos/invoice/InvoiceLazyLoader.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/asesores/invoice-from-list.js') }}"></script>
    <script src="{{ asset('js/asesores/receipt-manager.js') }}"></script>
    <script src="{{ asset('js/asesores/pedidos-detail-modal.js') }}"></script>
    <script src="{{ asset('js/asesores/pedidos-anular.js') }}"></script>
    
    <!-- Scripts específicos de supervisor -->
    <script src="{{ asset('js/supervisor-pedidos/supervisor-pedidos-detail-modal.js') }}"></script>
    <script src="{{ asset('js/ordersjs/tracking-modal-utils.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/ordersjs/tracking-modal-handler.js') }}"></script>
    
    <!-- Script para abrir el modal de seguimiento (inline para asegurar disponibilidad) -->
    <script>
        /**
         * Abre el modal de seguimiento del pedido
         * @param {number} ordenId - ID de la orden/pedido
         */
        window.openOrderTrackingModal = function(ordenId) {
            console.log('[openOrderTrackingModal] Abriendo modal para orden:', ordenId);
            
            // Primero verificar que mostrarTrackingModal está disponible
            if (typeof mostrarTrackingModal !== 'function') {
                console.error('[openOrderTrackingModal] ERROR: mostrarTrackingModal no está disponible');
                alert('Error: El modal de seguimiento no está cargado correctamente. Por favor, recarga la página.');
                return;
            }
            
            console.log('[openOrderTrackingModal] mostrarTrackingModal está disponible');
            
            // Obtener datos del pedido desde la ruta de supervisor
            console.log('[openOrderTrackingModal] Obteniendo datos de /supervisor-pedidos/' + ordenId + '/datos');
            
            fetch(`/supervisor-pedidos/${ordenId}/datos`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin'
            })
                .then(response => {
                    console.log('[openOrderTrackingModal] Response status:', response.status);
                    
                    if (!response.ok) {
                        console.error('[openOrderTrackingModal] HTTP error! status:', response.status);
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(pedidoData => {
                    console.log('[openOrderTrackingModal] Datos del pedido recibidos:', pedidoData);
                    
                    // Si tenemos los datos, intentar obtener los procesos
                    console.log('[openOrderTrackingModal] Obteniendo procesos de /api/ordenes/' + ordenId + '/procesos');
                    
                    return fetch(`/api/ordenes/${ordenId}/procesos`, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        credentials: 'same-origin'
                    })
                        .then(procResponse => {
                            console.log('[openOrderTrackingModal] Procesos response status:', procResponse.status);
                            
                            // Si la respuesta es exitosa, agregar los procesos
                            if (procResponse.ok) {
                                return procResponse.json().then(procesos => {
                                    console.log('[openOrderTrackingModal] Procesos obtenidos:', procesos);
                                    pedidoData.procesos = procesos;
                                    return pedidoData;
                                });
                            }
                            // Si falla, devolver los datos sin procesos
                            console.warn('[openOrderTrackingModal] No se pudieron cargar los procesos (status ' + procResponse.status + ')');
                            pedidoData.procesos = [];
                            return pedidoData;
                        })
                        .catch(error => {
                            console.warn('[openOrderTrackingModal] Error al obtener procesos:', error);
                            pedidoData.procesos = [];
                            return pedidoData;
                        });
                })
                .then(data => {
                    console.log('[openOrderTrackingModal] Datos finales listos. Llamando a mostrarTrackingModal...');
                    
                    // Verificar nuevamente que la función existe
                    if (typeof mostrarTrackingModal !== 'function') {
                        console.error('[openOrderTrackingModal] ERROR: mostrarTrackingModal no está disponible en el then final');
                        alert('Error: El modal de seguimiento no está cargado correctamente.');
                        return;
                    }
                    
                    // Llamar a la función que rellena y muestra el modal
                    try {
                        mostrarTrackingModal(data);
                        console.log('[openOrderTrackingModal] Modal mostrado exitosamente');
                    } catch (e) {
                        console.error('[openOrderTrackingModal] Error al llamar mostrarTrackingModal:', e);
                        alert('Error: ' + e.message);
                    }
                })
                .catch(error => {
                    console.error('[openOrderTrackingModal] Error general:', error);
                    alert('Error: No se puede abrir el seguimiento. Intenta nuevamente.');
                });
        };

        /**
         * Cierra el modal de seguimiento
         */
        window.closeOrderTracking = function() {
            console.log('[closeOrderTracking] Cerrando modal de seguimiento');
            const modal = document.getElementById('orderTrackingModal');
            if (modal) {
                modal.style.display = 'none';
            }
        };
    </script>
    
    <!-- Scripts para Recibos/Procesos -->
    <script type="module" src="{{ asset('js/modulos/pedidos-recibos/loader.js') }}"></script>
    
    <!-- Función para toggle de factura (compatible con order-detail-modal) -->
    <script>
    window.toggleFactura = function() {
        // Usar Galeria si está disponible
        if (typeof Galeria !== 'undefined' && Galeria.toggleFactura) {
            Galeria.toggleFactura('order-detail-modal-wrapper', 'btn-factura', 'btn-galeria');
        }
    };

    // Función para abrir imagen en grande desde la galería
    window.abrirModalImagenProcesoGrande = (function() {
        let galleryManagerLoaded = false;
        let GalleryManager = null;
        
        return async function(indice, fotosJSON) {
            console.log('[GalleryManager] Intentando abrir imagen:', indice);
                
                // Si ya está cargado, usar directamente
                if (galleryManagerLoaded && GalleryManager) {
                    return GalleryManager.abrirModalImagenProcesoGrande(indice, fotosJSON);
                }
                
                try {
                    // Intentar cargar el módulo GalleryManager
                    console.log('[GalleryManager] Cargando módulo...');
                    
                    // Primero intentar con la ruta relativa
                    try {
                        const module = await import('./js/modulos/pedidos-recibos/components/GalleryManager.js');
                        GalleryManager = module.GalleryManager;
                        galleryManagerLoaded = true;
                        console.log('[GalleryManager] Módulo cargado correctamente');
                    } catch (importError) {
                        console.warn('[GalleryManager] Error con ruta relativa, intentando ruta absoluta:', importError);
                        // Si falla, intentar cargar como script global
                        if (typeof window.GalleryManager !== 'undefined') {
                            GalleryManager = window.GalleryManager;
                            galleryManagerLoaded = true;
                            console.log('[GalleryManager] Usando GalleryManager global');
                        } else {
                            throw new Error('No se pudo cargar GalleryManager');
                        }
                    }
                    
                    if (GalleryManager) {
                        return GalleryManager.abrirModalImagenProcesoGrande(indice, fotosJSON);
                    }
                } catch (err) {
                    console.error('[GalleryManager] Error cargando GalleryManager:', err);
                    galleryManagerLoaded = false;
                    
                    // Implementación fallback básica
                    console.log('[GalleryManager] Usando implementación fallback');
                    try {
                        let fotos = typeof fotosJSON === 'string' ? JSON.parse(fotosJSON) : fotosJSON;
                        if (!fotos || !fotos[indice]) {
                            console.error('Imagen no encontrada:', indice);
                            return;
                        }
                        
                        // Crear modal simple
                        const modal = document.createElement('div');
                        modal.style.cssText = `
                            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                            background: rgba(0,0,0,0.9); z-index: 9999; display: flex;
                            align-items: center; justify-content: center;
                        `;
                        modal.innerHTML = `
                            <div style="position: relative; max-width: 90%; max-height: 90%;">
                                <img src="${fotos[indice]}" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                <button onclick="this.parentElement.parentElement.remove()" style="
                                    position: absolute; top: 10px; right: 10px;
                                    background: white; border: none; border-radius: 50%;
                                    width: 40px; height: 40px; cursor: pointer; font-size: 20px;
                                ">×</button>
                            </div>
                        `;
                        document.body.appendChild(modal);
                        modal.addEventListener('click', (e) => {
                            if (e.target === modal) modal.remove();
                        });
                    } catch (fallbackErr) {
                        console.error('[GalleryManager] Error en fallback:', fallbackErr);
                    }
                }
            };
        })();

        // ===== FUNCIONES PARA MODAL DE NOVEDADES =====
        window.abrirNovedades = function(ordenId, novedades) {
            console.log('[Novedades] Abriendo modal con ID:', ordenId);
            const modal = document.getElementById('modalNovedades');
            const contenido = document.getElementById('modalNovedadesContent');
            
            if (modal && contenido) {
                // Procesar saltos de línea: reemplazar \n literal con saltos reales
                const procesado = novedades.replace(/\\n/g, '\n');
                
                // Separar por doble salto de línea (separador de novedades)
                const novedadesArray = procesado.split('\n\n').filter(n => n.trim());
                
                // Formatear cada novedad
                let html = '';
                novedadesArray.forEach((novedad, index) => {
                    // Extraer usuario, rol y fecha usando regex
                    const match = novedad.match(/\[(.*?)\]\s(.*)/);
                    
                    if (match) {
                        const header = match[1];
                        const mensaje = match[2];
                        
                        html += `
                            <div style="
                                background: white;
                                border-left: 4px solid #1e40af;
                                padding: 1.2rem;
                                margin-bottom: 1.5rem;
                                border-radius: 4px;
                                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                            ">
                                <div style="
                                    display: flex;
                                    align-items: center;
                                    gap: 0.5rem;
                                    margin-bottom: 0.8rem;
                                    font-weight: 600;
                                    color: #1e40af;
                                    font-size: 0.85rem;
                                ">
                                    <span style="color: #3b82f6;">✓</span>
                                    <span>${escapeHtml(header)}</span>
                                </div>
                                <div style="
                                    color: #374151;
                                    font-size: 0.95rem;
                                    line-height: 1.6;
                                    white-space: pre-wrap;
                                    word-wrap: break-word;
                                ">
                                    ${escapeHtml(mensaje)}
                                </div>
                            </div>
                        `;
                    } else {
                        // Si no coincide el formato, mostrar como está
                        html += `
                            <div style="
                                background: white;
                                border-left: 4px solid #6b7280;
                                padding: 1.2rem;
                                margin-bottom: 1.5rem;
                                border-radius: 4px;
                                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                            ">
                                <div style="
                                    color: #374151;
                                    font-size: 0.95rem;
                                    line-height: 1.6;
                                    white-space: pre-wrap;
                                    word-wrap: break-word;
                                ">
                                    ${escapeHtml(novedad)}
                                </div>
                            </div>
                        `;
                    }
                });
                
                contenido.innerHTML = html;
                modal.style.display = 'flex';
                modal.style.alignItems = 'center';
                modal.style.justifyContent = 'center';
                console.log('[Novedades] Modal abierto');
            }
        };

        // Función auxiliar para escapar HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        window.cerrarModalNovedades = function() {
            console.log('[Novedades] Cerrando modal');
            const modal = document.getElementById('modalNovedades');
            if (modal) {
                modal.style.display = 'none';
            }
        };

        // Cerrar modal al hacer clic fuera del contenido
        document.getElementById('modalNovedades')?.addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalNovedades();
            }
        });
        // ===== FUNCIÓN PARA ABRIR MODAL DE APROBACIÓN =====
        // Se ha movido a: js/supervisor-pedidos/modales-acciones.js
        // Por lo tanto, window.abrirModalAprobacion ya está definida al cargar la página
    </script>

    <!--  REALTIME: Listener para actualizaciones de órdenes en tiempo real -->
    <script>
        /**
         Sistema de Tiempo Real - Supervisor-Pedidos
         Se suscribe al canal 'supervisor-pedidos' via WebSockets
         Actualiza la tabla automáticamente cuando llegan nuevas órdenes o cambios
         Usa un polling para esperar a que window.waitForEcho esté disponible
         */
        
        // Definir funciones reutilizables ANTES de intentar conectarse
        /**
         * Actualiza una fila existente en la tabla
         */
        function actualizarFilaEnTabla(fila, orden, action) {
            console.log(`[Realtime] Actualizando fila para pedido #${orden.numero_pedido}`);

            // Actualizar campos que pueden haber cambiado
            const celdas = fila.querySelectorAll('[data-field]');
            celdas.forEach(celda => {
                const field = celda.getAttribute('data-field');
                if (orden[field]) {
                    const newValue = orden[field];
                    if (celda.textContent !== newValue) {
                        celda.textContent = newValue;
                        // Agregar animación de cambio
                        celda.style.backgroundColor = '#fff9e6';
                        setTimeout(() => {
                            celda.style.backgroundColor = '';
                        }, 1500);
                    }
                }
            });

            // Cambiar background de la fila para indicar cambio
            fila.style.backgroundColor = '#f0f9ff';
            setTimeout(() => {
                fila.style.backgroundColor = 'white';
            }, 2000);
        }

        /**
         * Agrega una nueva fila a la tabla cuando llega una nueva orden
         */
        function agregarNuevaFilaATabla(orden, action) {
            console.log(`[Realtime] Agregando nueva fila para pedido #${orden.numero_pedido}`);
            
            const tableContainer = document.querySelector('.table-scroll-container');
            if (!tableContainer) {
                console.warn('[Realtime] No se encontró el contenedor de tabla');
                return;
            }

            // Crear HTML de la nueva fila basado en la estructura existente
            const numeroPedido = orden.numero_pedido || orden.numero;
            const filaHTML = `
                <div data-pedido-id="${orden.id}" style="
                    display: grid;
                    grid-template-columns: 200px 140px 200px 140px 150px 150px;
                    gap: 1.2rem;
                    padding: 1rem;
                    border-bottom: 1px solid #e5e7eb;
                    align-items: center;
                    min-width: min-content;
                    background: #f0f9ff;
                    animation: slideInDown 0.5s ease;
                    transition: background 0.2s ease;
                " onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='#f0f9ff'">
                    <!-- Acciones -->
                    <div style="display: flex; gap: 0.5rem; align-items: center; justify-content: center;">
                        <button class="btn-ver-dropdown" onclick="editarPedido(${orden.id})" title="Editar" style="
                            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
                            color: white;
                            border: none;
                            padding: 0.5rem;
                            border-radius: 6px;
                            cursor: pointer;
                            font-size: 1rem;
                            transition: all 0.3s ease;
                        ">
                            <span class="material-symbols-rounded">edit</span>
                        </button>
                    </div>
                    <!-- Número -->
                    <div data-field="numero_pedido" style="font-weight: 600; color: #1e3a8a;">#${numeroPedido}</div>
                    <!-- Cliente -->
                    <div data-field="cliente" style="color: #2c3e50;">${orden.cliente || 'Sin especificar'}</div>
                    <!-- Novedades -->
                    <div data-field="novedades" style="color: #666; font-size: 0.9rem;">${orden.novedades || '-'}</div>
                    <!-- Asesora -->
                    <div data-field="asesor" style="color: #666;">${orden.asesor?.name || orden.asesor || 'Sin asignar'}</div>
                    <!-- Forma Pago -->
                    <div data-field="forma_pago" style="color: #666;">${orden.forma_pago || 'No especificada'}</div>
                </div>
            `;

            // Agregar la nueva fila al final de la tabla
            tableContainer.innerHTML += filaHTML;
            
            // Scroll suave a la nueva fila
            tableContainer.scrollLeft = tableContainer.scrollWidth;
        }

        function supervisorPedidosMostrarNotificacionNuevoPedido(orden) {
            try {
                const numero = orden?.numero_pedido || orden?.numero || '';
                const cliente = orden?.cliente ? ` - ${orden.cliente}` : '';
                const mensaje = `Nuevo pedido${numero ? ' #' + numero : ''}${cliente}`;

                // Actualizar badge/lista de notificaciones en tiempo real (sin recargar)
                try {
                    const badge = document.getElementById('notificationBadge');
                    if (badge) {
                        const count = (parseInt(badge.textContent) || 0) + 1;
                        badge.textContent = String(count);
                        badge.style.display = count > 0 ? 'block' : 'none';
                    }

                    // Sincronización (opcional) con backend SIN bloquear la notificación inmediata.
                    if (window.__supervisorPedidosNotifSyncT) clearTimeout(window.__supervisorPedidosNotifSyncT);
                    window.__supervisorPedidosNotifSyncT = setTimeout(() => {
                        try {
                            if (typeof window.supervisorPedidosRefreshNotificaciones === 'function') {
                                window.supervisorPedidosRefreshNotificaciones();
                            } else if (typeof cargarNotificacionesPendientes === 'function') {
                                cargarNotificacionesPendientes();
                            } else {
                                window.dispatchEvent(new CustomEvent('supervisorPedidos:notificacionesRefresh'));
                            }
                        } catch (e) {
                            // noop
                        }
                    }, 1200);
                } catch (e) {
                    // noop
                }

                if (window.PedidosRealtimeRefresh && window.PedidosRealtimeRefresh.instance && typeof window.PedidosRealtimeRefresh.instance.showRealtimeToast === 'function') {
                    window.PedidosRealtimeRefresh.instance.showRealtimeToast(mensaje, 'success');
                    return;
                }

                const bg = '#16a34a';
                const container = document.getElementById('toastContainer') || (() => {
                    let div = document.getElementById('toastContainer');
                    if (div) return div;
                    div = document.createElement('div');
                    div.id = 'toastContainer';
                    div.className = 'toast-container';
                    div.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 99999; display: flex; flex-direction: column; gap: 10px;';
                    document.body.appendChild(div);
                    return div;
                })();

                const toast = document.createElement('div');
                toast.style.cssText = `
                    background: ${bg};
                    color: white;
                    padding: 12px 14px;
                    border-radius: 10px;
                    box-shadow: 0 10px 25px rgba(0,0,0,0.18);
                    font-size: 13px;
                    font-weight: 600;
                    max-width: 360px;
                    transform: translateX(120%);
                    transition: transform 0.25s ease;
                `;
                toast.textContent = mensaje;
                container.appendChild(toast);

                requestAnimationFrame(() => {
                    toast.style.transform = 'translateX(0)';
                });

                setTimeout(() => {
                    toast.style.transform = 'translateX(120%)';
                    setTimeout(() => toast.remove(), 250);
                }, 3500);
            } catch (e) {
                // silencioso
            }
        }

        function supervisorPedidosMaybeNotifyFromActualizado(payload) {
            try {
                const pedido = payload?.pedido || payload?.orden || payload || {};
                const nuevo = payload?.nuevo_estado?.new || payload?.nuevo_estado || pedido?.estado || '';
                const anterior = payload?.anterior_estado || payload?.nuevo_estado?.old || '';

                if (String(nuevo).toUpperCase() !== 'PENDIENTE_SUPERVISOR') return;
                if (String(anterior).toUpperCase() === 'PENDIENTE_SUPERVISOR') return;

                if (!window.__supervisorPedidosNotifiedIds) {
                    window.__supervisorPedidosNotifiedIds = new Set();
                }

                const key = String(pedido?.id || payload?.pedido_id || payload?.id || '');
                if (!key) return;
                if (window.__supervisorPedidosNotifiedIds.has(key)) return;
                window.__supervisorPedidosNotifiedIds.add(key);

                supervisorPedidosMostrarNotificacionNuevoPedido(pedido);
            } catch (e) {
                // noop
            }
        }

        function supervisorPedidosInsertarFilaNuevaAlInicio(orden) {
            const tableContainer = document.querySelector('.table-scroll-container');
            if (!tableContainer) return;

            const header = tableContainer.querySelector('[style*="grid-template-columns: 200px 140px 200px 150px 140px 150px 150px"]');
            const numeroPedido = orden?.numero_pedido || orden?.numero || 'N/A';
            const estado = orden?.estado || 'PENDIENTE_SUPERVISOR';

            const estadoColors = {
                'PENDIENTE_SUPERVISOR': { bg: '#fff3cd', text: '#856404', label: 'Pendiente Supervisor' },
                'PENDIENTE_INSUMOS': { bg: '#d1ecf1', text: '#0c5460', label: 'Pendiente Insumos' },
                'En Ejecución': { bg: '#d4edda', text: '#155724', label: 'En Ejecución' },
                'No iniciado': { bg: '#e2e3e5', text: '#383d41', label: 'No Iniciado' },
                'Entregado': { bg: '#d4edda', text: '#155724', label: 'Entregado' },
                'Finalizada': { bg: '#d4edda', text: '#155724', label: 'Finalizada' },
                'Anulada': { bg: '#f8d7da', text: '#721c24', label: 'Anulada' },
                'DEVUELTO_A_ASESORA': { bg: '#f8d7da', text: '#721c24', label: 'Devuelto' },
            };
            const estadoInfo = estadoColors[estado] || { bg: '#e2e3e5', text: '#383d41', label: estado };

            const fila = document.createElement('div');
            fila.setAttribute('data-pedido-id', String(orden?.id || ''));
            fila.style.cssText = `
                display: grid;
                grid-template-columns: 60px 220px 120px 200px 150px 140px 150px 150px 150px;
                gap: 1.2rem;
                padding: 1rem;
                border-bottom: 1px solid #e5e7eb;
                align-items: center;
                min-width: min-content;
                background: #f0f9ff;
                animation: slideInDown 0.5s ease;
                transition: background 0.2s ease;
            `;
            fila.onmouseover = function() { this.style.background = '#f9fafb'; };
            fila.onmouseout = function() { this.style.background = 'white'; };

            const safeNumero = String(numeroPedido).replace('#', '');
            fila.innerHTML = `
                <div style="display: flex; gap: 0.5rem; align-items: center; justify-content: center;">
                    <button class="btn-ver-dropdown" data-menu-id="menu-ver-${safeNumero}" data-pedido="${safeNumero}" data-pedido-id="${orden?.id || ''}" title="Ver Opciones" style="
                        background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
                        color: white;
                        border: none;
                        padding: 0.5rem;
                        border-radius: 6px;
                        cursor: pointer;
                        font-size: 1rem;
                        transition: all 0.3s ease;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        width: 36px;
                        height: 36px;
                        box-shadow: 0 2px 4px rgba(37, 99, 235, 0.3);
                    ">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div>
                    <span style="font-weight: 600; color: #1e5ba8;">#${numeroPedido}</span>
                </div>
                <div>
                    <span>${orden?.cliente || ''}</span>
                </div>
                <div>
                    <span style="background: ${estadoInfo.bg}; color: ${estadoInfo.text}; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap; display: inline-block;">
                        ${estadoInfo.label}
                    </span>
                </div>
                <div>
                    <span style="background: #f3f4f6; color: #9ca3af; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap;">
                        Sin novedades
                    </span>
                </div>
                <div>
                    <span>${orden?.asesora || orden?.asesor || 'N/A'}</span>
                </div>
                <div>
                    <span>${orden?.forma_pago || orden?.forma_de_pago || 'N/A'}</span>
                </div>
            `;

            if (header && header.parentNode === tableContainer) {
                header.insertAdjacentElement('afterend', fila);
            } else {
                tableContainer.prepend(fila);
            }

            setTimeout(() => {
                fila.style.backgroundColor = 'white';
            }, 2000);
        }

      
        // Agregar estilos de animación si no existen
        if (!document.querySelector('style[data-realtime]')) {
            const style = document.createElement('style');
            style.setAttribute('data-realtime', 'true');
            style.textContent = `
                @keyframes slideInDown {
                    from {
                        opacity: 0;
                        transform: translateY(-20px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
                @keyframes slideInRight {
                    from {
                        opacity: 0;
                        transform: translateX(100px);
                    }
                    to {
                        opacity: 1;
                        transform: translateX(0);
                    }
                }
                @keyframes slideOutRight {
                    from {
                        opacity: 1;
                        transform: translateX(0);
                    }
                    to {
                        opacity: 0;
                        transform: translateX(100px);
                    }
                }
            `;
            document.head.appendChild(style);
        }

        //  ESPERAR A QUE window.waitForEcho ESTÉ DISPONIBLE ANTES DE CONECTARSE
        console.log('[Realtime Supervisor] ⏳ Esperando a que window.waitForEcho esté disponible...');
        
        // Función para inicializar el listener
        function initializeRealtimeListener() {
            // Verificar que window.waitForEcho está disponible
            if (typeof window.waitForEcho !== 'function') {
                console.log('[Realtime Supervisor] ⏳ window.waitForEcho aún no disponible, reintentando en 100ms...');
                setTimeout(initializeRealtimeListener, 100);
                return;
            }
            
            console.log('[Realtime Supervisor]  window.waitForEcho está disponible, inicializando listener...');
            
            window.waitForEcho(() => {
                console.log('[Realtime Supervisor] ✅ Echo está listo, inicializando suscripción...');
                
                try {
                    const echo = window.EchoInstance || window.Echo;
                    if (!echo || typeof echo.channel !== 'function') {
                        console.error('[Realtime Supervisor] ❌ EchoInstance no disponible o inválido');
                        return;
                    }

                    const refreshTabla = async (payload, eventName) => {
                        console.log(`[Realtime Supervisor] 🔄 Refresh solicitado por ${eventName}:`, payload);

                        if (eventName && String(eventName).includes('pedidos.creados:.pedido.creado')) {
                            const pedido = payload?.pedido || payload?.orden || payload || {};
                            supervisorPedidosInsertarFilaNuevaAlInicio(pedido);
                            supervisorPedidosMostrarNotificacionNuevoPedido(pedido);
                            return;
                        }

                        // Si el backend no emite "pedido.creado" para supervisor, pero sí "pedido.actualizado"
                        // cuando pasa a PENDIENTE_SUPERVISOR, notificar de inmediato.
                        if (eventName && String(eventName).includes('despacho.pedidos:.pedido.actualizado')) {
                            supervisorPedidosMaybeNotifyFromActualizado(payload);
                        }

                        if (window.__realtimeSupervisorRefreshTimeout) {
                            clearTimeout(window.__realtimeSupervisorRefreshTimeout);
                        }

                        window.__realtimeSupervisorRefreshTimeout = setTimeout(async () => {
                            try {
                                const response = await fetch(window.location.href, {
                                    method: 'GET',
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest'
                                    }
                                });

                                if (!response.ok) {
                                    console.error('[Realtime Supervisor] ❌ No se pudo refrescar la tabla:', response.status);
                                    return;
                                }

                                const html = await response.text();
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(html, 'text/html');

                                const nuevaTabla = doc.querySelector('.table-scroll-container');
                                const tablaActual = document.querySelector('.table-scroll-container');

                                if (!nuevaTabla || !tablaActual) {
                                    console.warn('[Realtime Supervisor] ⚠️ No se encontró .table-scroll-container para refrescar');
                                    return;
                                }

                                tablaActual.innerHTML = nuevaTabla.innerHTML;

                                const pedido = payload?.pedido || payload?.orden || payload || {};
                                const ordenNoti = {
                                    id: pedido.id,
                                    numero_pedido: pedido.numero_pedido || payload?.numero_pedido || 'N/A',
                                    cliente: pedido.cliente || '',
                                    estado: pedido.estado || ''
                                };
                                // Notificación desactivada para evitar mostrar "Pedido #X actualizado"
                                // if (typeof mostrarNotificacionEnTiempoReal === 'function') {
                                //     mostrarNotificacionEnTiempoReal(ordenNoti, 'created');
                                // }
                            } catch (error) {
                                console.error('[Realtime Supervisor] ❌ Error refrescando tabla:', error);
                            }
                        }, 450);
                    };

                    // Listeners de eventos custom (emitidos por public/js/modulos/asesores/pedidos-realtime.js)
                    // para evitar recargar toda la página.
                    if (!window.__supervisorPedidosRealtimeCustomBound) {
                        window.__supervisorPedidosRealtimeCustomBound = true;

                        window.addEventListener('supervisorPedidos:realtimePedidoCreado', (e) => {
                            const pedido = e?.detail?.pedido || e?.detail?.raw?.pedido || {};
                            supervisorPedidosInsertarFilaNuevaAlInicio(pedido);
                            supervisorPedidosMostrarNotificacionNuevoPedido(pedido);
                        });

                        window.addEventListener('supervisorPedidos:realtimePedidoActualizado', (e) => {
                            // Para cambios de recibos/estado/etc: refrescar el contenedor sin recargar navegador
                            refreshTabla(e?.detail?.raw || e?.detail?.pedido || {}, e?.detail?.source || 'custom:.pedido.actualizado');
                        });
                    }

                    // Canal principal (cuando el pedido cambia de estado desde despacho/cartera)
                    echo.channel('despacho.pedidos')
                        .listen('.pedido.actualizado', (data) => refreshTabla(data, 'despacho.pedidos:.pedido.actualizado'))
                        .on('pusher:subscription_succeeded', () => {
                            console.log('[Realtime Supervisor] ✅ Subscripción exitosa al canal despacho.pedidos');
                        })
                        .on('pusher:subscription_error', (error) => {
                            console.error('[Realtime Supervisor] ❌ Error en subscripción despacho.pedidos:', error);
                        });

                    // Canal legado/alterno (si existe en el backend)
                    const channelSupervisor = echo.channel('supervisor-pedidos');
                    channelSupervisor.subscribed(() => {
                        console.log('[Realtime Supervisor] ✅ Suscripción exitosa al canal supervisor-pedidos');
                    });
                    channelSupervisor.error((error) => {
                        console.error('[Realtime Supervisor] ❌ Error en suscripción al canal supervisor-pedidos:', error);
                    });
                    channelSupervisor.listen('OrdenUpdated', (data) => refreshTabla(data, 'supervisor-pedidos:OrdenUpdated'));

                    echo.channel('pedidos.creados')
                        .listen('.pedido.creado', (data) => refreshTabla(data, 'pedidos.creados:.pedido.creado'));
                    
                    console.log('[Realtime Supervisor] ✅ Sistema de tiempo real inicializado correctamente');
                } catch (error) {
                    console.error('[Realtime Supervisor] ❌ Error inicializando listener:', error);
                }
            });
        }

        // Esperar a que el documento esté completamente cargado antes de inicializar
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeRealtimeListener);
        } else {
            initializeRealtimeListener();
        }
    </script>

    <!-- 🚨 SCRIPT ESPECÍFICO PARA SUPERVISOR-PEDIDOS: Botón limpiar asignaciones -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Esperar a que jQuery esté disponible
            const verificarJQuery = setInterval(function() {
                if (typeof jQuery !== 'undefined' && jQuery.fn.modal) {
                    clearInterval(verificarJQuery);
                    
                    // Configurar botón de limpiar asignaciones
                    const btnLimpiarAsignaciones = document.getElementById('btn-limpiar-asignaciones');
                    
                    if (btnLimpiarAsignaciones) {
                        btnLimpiarAsignaciones.addEventListener('click', function(e) {
                            e.preventDefault();
                            
                            console.log('[Supervisor-Pedidos] Botón limpiar asignaciones clickeado');
                            
                            // 🔧 Adaptación para supervisor-pedidos: Buscar contenedor principal
                            const supervisorWrapper = document.querySelector('.supervisor-pedidos-container') || 
                                                      document.querySelector('#mainContent') || 
                                                      document.querySelector('main');
                            if (supervisorWrapper) {
                                supervisorWrapper.removeAttribute('aria-hidden');
                            }
                            
                            // Remover cualquier overlay existente antes de abrir el modal
                            const overlayExistente = document.getElementById('overlay-confirmar-limpiar');
                            if (overlayExistente) {
                                overlayExistente.remove();
                            }
                            
                            // Abrir modal de confirmación
                            try {
                                jQuery('#modal-confirmar-limpiar').modal('show');
                                console.log('[Supervisor-Pedidos] Modal de confirmación abierto (Bootstrap 4)');
                            } catch (error) {
                                console.error('[Supervisor-Pedidos] Error al abrir modal:', error);
                                // Fallback: mostrar alert simple
                                if (confirm('¿Eliminar todas las asignaciones de colores? Esta acción no se puede deshacer.')) {
                                    // Ejecutar la función de limpiar
                                    if (window.ColoresPorTalla && typeof window.ColoresPorTalla.limpiarTodo === 'function') {
                                        window.ColoresPorTalla.limpiarTodo();
                                    }
                                }
                            }
                        });
                        
                        // Listener para el botón de confirmar dentro del modal
                        const btnConfirmarLimpiar = document.getElementById('btn-confirmar-limpiar-todo');
                        if (btnConfirmarLimpiar) {
                            btnConfirmarLimpiar.addEventListener('click', function() {
                                console.log('[Supervisor-Pedidos] Confirmado: Limpiar todo');
                                
                                // Ejecutar la función de limpiar
                                if (window.ColoresPorTalla && typeof window.ColoresPorTalla.limpiarTodo === 'function') {
                                    window.ColoresPorTalla.limpiarTodo();
                                }
                                
                                // Actualizar total si existe la función
                                if (typeof actualizarTotalPrendas === 'function') {
                                    actualizarTotalPrendas();
                                }
                                
                                // Cerrar modal de confirmación
                                const modalLimpiar = document.getElementById('modal-confirmar-limpiar');
                                if (modalLimpiar) {
                                    jQuery(modalLimpiar).modal('hide');
                                }
                                
                                // Remover overlay si existe
                                const ov = document.getElementById('overlay-confirmar-limpiar');
                                if (ov) ov.remove();
                                
                                console.log('[Supervisor-Pedidos] Limpieza completada');
                            });
                        }
                        
                        // Listener para remover aria-hidden cuando se cierre el modal
                        jQuery('#modal-confirmar-limpiar').on('hidden.bs.modal', function() {
                            const supervisorWrapper = document.querySelector('.supervisor-pedidos-container') || 
                                                          document.querySelector('#mainContent') || 
                                                          document.querySelector('main');
                            if (supervisorWrapper) {
                                supervisorWrapper.setAttribute('aria-hidden', 'true');
                            }
                        });
                        
                        console.log('[Supervisor-Pedidos] ✅ Botón limpiar asignaciones configurado');
                    } else {
                        console.log('[Supervisor-Pedidos] ⚠️ Botón limpiar asignaciones no encontrado');
                    }
                }
            }, 100);
        });

    <!-- Script para manejo de checkboxes de selección de pedidos -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Cargar selecciones guardadas al iniciar
            setTimeout(() => {
                cargarSeleccionesGuardadas();
            }, 500); // Esperar 500ms para que los checkboxes estén disponibles

            // Checkbox "Seleccionar todos"
            const selectAllCheckbox = document.getElementById('selectAll');
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function(e) {
                    const checkboxes = document.querySelectorAll('.pedido-checkbox');
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = e.target.checked;
                        const pedidoId = checkbox.getAttribute('data-pedido-id');
                        const fila = checkbox.closest('div[style*="grid-template-columns"]');
                        
                        if (e.target.checked) {
                            seleccionarPedido(pedidoId);
                            if (fila) {
                                fila.style.background = '#d1d5db'; // Gris medio claro
                                fila.style.transition = 'background 0.2s';
                                fila.dataset.seleccionado = 'true';
                            }
                        } else {
                            deseleccionarPedido(pedidoId);
                            if (fila) {
                                fila.style.background = 'white';
                                fila.style.transition = 'background 0.2s';
                                fila.dataset.seleccionado = 'false';
                            }
                        }
                    });
                });
            }

            // Agregar event listeners a todos los checkboxes
            document.addEventListener('change', function(e) {
                if (e.target && e.target.classList.contains('pedido-checkbox')) {
                    const pedidoId = e.target.getAttribute('data-pedido-id');
                    // Buscar la fila padre (el div que contiene el grid)
                    const fila = e.target.closest('div[style*="grid-template-columns"]');
                    
                    if (e.target.checked) {
                        seleccionarPedido(pedidoId);
                        // Marcar la fila como seleccionada
                        if (fila) {
                            fila.style.background = '#d1d5db'; // Gris medio claro
                            fila.style.transition = 'background 0.2s';
                            fila.dataset.seleccionado = 'true';
                            console.log(`✅ Fila marcada como seleccionada para pedido ${pedidoId}`);
                        } else {
                            console.log(`⚠️ No se encontró la fila para pedido ${pedidoId}`);
                        }
                    } else {
                        deseleccionarPedido(pedidoId);
                        // Restaurar color original de la fila
                        if (fila) {
                            fila.style.background = 'white';
                            fila.style.transition = 'background 0.2s';
                            fila.dataset.seleccionado = 'false';
                            console.log(`✅ Fila restaurada a color normal para pedido ${pedidoId}`);
                        } else {
                            console.log(`⚠️ No se encontró la fila para pedido ${pedidoId}`);
                        }
                    }
                }
            });

            // Función para seleccionar un pedido
            function seleccionarPedido(pedidoId) {
                // Usar URL relativa (funciona en desarrollo y producción)
                fetch(`/supervisor-pedidos/seleccionar/${pedidoId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    console.log(`✅ Respuesta del servidor para pedido ${pedidoId}:`, data);
                    if (data.success) {
                        console.log(`✅ Pedido ${pedidoId} seleccionado correctamente`);
                        // El checkbox ya está marcado por el evento change, no necesitamos hacer nada más
                    } else {
                        console.error(`❌ Error al seleccionar pedido ${pedidoId}:`, data.message);
                        // Revertir checkbox si hubo error
                        const checkbox = document.querySelector(`.pedido-checkbox[data-pedido-id="${pedidoId}"]`);
                        if (checkbox) checkbox.checked = false;
                    }
                })
                .catch(error => {
                    console.error(`❌ Error de red al seleccionar pedido ${pedidoId}:`, error);
                    // Revertir checkbox si hubo error
                    const checkbox = document.querySelector(`.pedido-checkbox[data-pedido-id="${pedidoId}"]`);
                    if (checkbox) checkbox.checked = false;
                });
            }

            // Función para deseleccionar un pedido
            function deseleccionarPedido(pedidoId) {
                // Usar URL relativa (funciona en desarrollo y producción)
                fetch(`/supervisor-pedidos/seleccionar/${pedidoId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log(`✅ Pedido ${pedidoId} deseleccionado correctamente`);
                    } else {
                        console.error(`❌ Error al deseleccionar pedido ${pedidoId}:`, data.message);
                        // Revertir checkbox si hubo error
                        const checkbox = document.querySelector(`.pedido-checkbox[data-pedido-id="${pedidoId}"]`);
                        if (checkbox) checkbox.checked = true;
                    }
                })
                .catch(error => {
                    console.error(`❌ Error de red al deseleccionar pedido ${pedidoId}:`, error);
                    // Revertir checkbox si hubo error
                    const checkbox = document.querySelector(`.pedido-checkbox[data-pedido-id="${pedidoId}"]`);
                    if (checkbox) checkbox.checked = true;
                });
            }

            // Función para cargar las selecciones guardadas del usuario
            function cargarSeleccionesGuardadas() {
                console.log('🔄 Cargando selecciones guardadas...');
                console.log('🌐 URL actual:', window.location.href);
                console.log('🌐 Origin:', window.location.origin);
                console.log('🌐 Hostname:', window.location.hostname);
                console.log('🌐 Port:', window.location.port);
                
                // Usar URL relativa (funciona en desarrollo y producción)
                fetch('/supervisor-pedidos/selecciones', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    }
                })
                .then(response => {
                    // Si es 404, salir silenciosamente (ruta no existe)
                    if (response.status === 404) {
                        return null;
                    }
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (!data) return; // Si fue 404, data es null
                    console.log('📥 Respuesta de selecciones:', data);
                    if (data.success && data.selecciones) {
                        console.log(`📋 Se encontraron ${data.selecciones.length} selecciones guardadas:`, data.selecciones);
                        // Marcar los checkboxes correspondientes
                        data.selecciones.forEach(pedidoId => {
                            const checkbox = document.querySelector(`.pedido-checkbox[data-pedido-id="${pedidoId}"]`);
                            if (checkbox) {
                                checkbox.checked = true;
                                const fila = checkbox.closest('div[style*="grid-template-columns"]');
                                if (fila) {
                                    fila.style.background = '#d1d5db'; // Gris medio claro
                                    fila.style.transition = 'background 0.2s';
                                    fila.dataset.seleccionado = 'true';
                                } else {
                                    console.log(`⚠️ No se encontró la fila para pedido ${pedidoId}`);
                                }
                            } else {
                                console.log(`⚠️ No se encontró checkbox para pedido ${pedidoId}`);
                            }
                        });
                        console.log(`📋 Se cargaron ${data.selecciones.length} selecciones guardadas`);
                    } else {
                        console.log('📭 No hay selecciones guardadas o error en respuesta');
                    }
                })
                .catch(error => {
                    console.error('❌ Error al cargar selecciones guardadas:', error);
                });
            }

            // Hacer las funciones disponibles globalmente para uso externo
            window.seleccionarPedido = seleccionarPedido;
            window.deseleccionarPedido = deseleccionarPedido;
            window.cargarSeleccionesGuardadas = cargarSeleccionesGuardadas;
        });
    </script>
@endpush

@endsection

