@extends('layouts.asesores')

@section('title', 'Mis Pedidos')
@section('page-title', 'Mis Pedidos')

@section('extra_styles')
    <!--  OPTIMIZADO: Solo CSS necesario para lista de pedidos -->
    <link rel="stylesheet" href="{{ asset('css/asesores/pedidos/index.css') }}">
    <link rel="stylesheet" href="{{ asset('css/asesores/pedidos/page-loading.css') }}">

    <!-- CSS base para el modal de agregar/editar prenda (mismos que usar crear-pedido-nuevo) -->
    <link rel="stylesheet" href="{{ asset('css/crear-pedido.css') }}">
    <link rel="stylesheet" href="{{ asset('css/crear-pedido-editable.css') }}">
    <link rel="stylesheet" href="{{ asset('css/form-modal-consistency.css') }}">
    <link rel="stylesheet" href="{{ asset('css/swal-z-index-fix.css') }}">
    
    <!--  FIX GLOBAL: Posicionamiento de SweetAlert para modales grandes -->
    <style>
        /* Permitir que SweetAlert sea scrolleable y se posicione correctamente */
        .swal2-container {
            align-items: flex-start !important;
            padding-top: 20px !important;
            max-height: 100vh !important;
            overflow-y: auto !important;
        }
        
        .swal2-popup {
            max-height: 95vh !important;
            overflow-y: auto !important;
            width: auto !important;
            max-width: 1200px !important;
            margin-left: auto !important;
            margin-right: auto !important;
        }
        
        .swal2-html-container {
            overflow: visible !important;
            max-height: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        
        /* Fondo oscuro debe ocupar toda la pantalla */
        .swal2-backdrop {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
        }
        
        /*  FIX CRÍTICO: Todos los .modal-overlay deben ser fixed overlays SIN scroll en el fondo */
        .modal-overlay {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            background: rgba(0, 0, 0, 0.5) !important;
            display: none !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 20px !important;
            overflow: hidden !important;
            /*  CRÍTICO: NO usar !important en z-index para permitir inline styles */
            z-index: 99999;
        }
        
        /* Cuando está visible, mostrar con flexbox */
        .modal-overlay[style*="display: flex"] {
            display: flex !important;
        }
        
        /* Contenedor del modal debe tener scroll */
        .modal-overlay .modal-container,
        .modal-overlay .modal-content {
            position: relative !important;
            background: white !important;
            max-height: 95vh !important;
            overflow-y: auto !important;
            max-width: 900px !important;
            width: 100% !important;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f1f5f9;
        }
        
        /* Scroll personalizado para navegadores WebKit */
        .modal-overlay .modal-container::-webkit-scrollbar,
        .modal-overlay .modal-content::-webkit-scrollbar {
            width: 8px;
        }
        
        .modal-overlay .modal-container::-webkit-scrollbar-track,
        .modal-overlay .modal-content::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        
        .modal-overlay .modal-container::-webkit-scrollbar-thumb,
        .modal-overlay .modal-content::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        
        .modal-overlay .modal-container::-webkit-scrollbar-thumb:hover,
        .modal-overlay .modal-content::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
@endsection

@section('content')

    <!--  LOADING OVERLAY - Se muestra mientras carga la página -->
    <div id="page-loading-overlay">
        <div class="loading-container">
            <div class="spinner"></div>
            <div class="loading-text">
                Cargando los pedidos<span class="loading-dots"></span>
            </div>
            <div class="loading-subtext">
                Por favor espera mientras se cargan los datos
            </div>
        </div>
    </div>

    @include('asesores.pedidos.components.header')

    @include('asesores.pedidos.components.quick-filters')

    @include('asesores.pedidos.components.table')

    @include('asesores.pedidos.components.modals')

@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/asesores/pedidos.css') }}">
@endpush

@push('scripts')

<!--  STORAGE WRAPPER: Protege acceso a localStorage/sessionStorage evitando errores de contexto -->
<script src="{{ asset('js/storage-wrapper.js') }}"></script>

<!--  PRELOADER: Precarga en background para evitar delays en primera apertura -->
<script src="{{ asset('js/lazy-loaders/prenda-editor-preloader.js') }}"></script>

<!--  LAZY LOADERS: Cargan módulos bajo demanda -->
<script src="{{ asset('js/lazy-loaders/prenda-editor-loader-modular.js') }}"></script>
<script src="{{ asset('js/lazy-loaders/epp-manager-loader.js') }}"></script>

<!-- Componente: Modal Editar Pedido -->
@include('asesores.pedidos.components.modal-editar-pedido')

<!-- Componente: Modal Lista Prendas -->
@include('asesores.pedidos.components.modal-prendas-lista')

<!-- Componente: Modal Agregar Prenda -->
@include('asesores.pedidos.components.modal-agregar-prenda')

<!-- Componente: Modal Editar Prenda Específica -->
@include('asesores.pedidos.components.modal-editar-prenda')

<!-- Componente: Modal Editar EPP -->
@include('asesores.pedidos.components.modal-editar-epp')

{{-- modal-agregar-prenda-nueva YA se incluye desde components/modals.blade.php --}}
{{-- NO duplicar aquí para evitar "Identifier already declared" en todos los scripts --}}

<!-- Scripts adicionales para edición de prendas desde lista de pedidos -->
@php $v = config('app.asset_version', time()); @endphp
<script defer src="{{ js_asset('js/modulos/crear-pedido/prendas/prenda-editor.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/modulos/crear-pedido/tallas/gestion-tallas.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/modulos/crear-pedido/telas/telas-module/manejo-imagenes.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/componentes/prenda-form-collector.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/componentes/prenda-editor-pedidos-adapter.js') }}?v={{ $v }}"></script>

<!-- Sistema de Actualización en Tiempo Real con WebSockets -->
<!-- Nota: Los scripts de WebSockets se cargan automáticamente en el layout base para usuarios autenticados -->

<!--  SERVICIOS CENTRALIZADOS - Cargar PRIMERO -->
<script src="{{ asset('js/utilidades/validation-service.js') }}"></script>
<script src="{{ asset('js/utilidades/ui-modal-service.js') }}"></script>
<script src="{{ asset('js/utilidades/deletion-service.js') }}"></script>
<script src="{{ asset('js/utilidades/galeria-service.js') }}"></script>

<script>
    //  Configurar variables globales
    window.fetchUrl = '/registros';
    window.modalContext = 'pedidos';
    window.__despachoObsUsuarioActualId = {{ auth()->id() ?? 'null' }};

    //  REFACTORIZADO: verMotivoanulacion() - Usar UIModalService
    function verMotivoanulacion(numeroPedido, motivo, usuario, fecha) {
        const html = `
            <div style="text-align: left;">
                <div style="margin-bottom: 1.25rem;">
                    <label style="font-size: 0.75rem; font-weight: 700; color: #6b7280; text-transform: uppercase; margin-bottom: 0.375rem; display: block;">Motivo</label>
                    <div style="font-size: 0.95rem; color: #374151; background: #fef2f2; padding: 0.875rem; border-radius: 6px; border-left: 3px solid #ef4444;">
                        ${motivo || 'No especificado'}
                    </div>
                </div>
                <div style="margin-bottom: 1.25rem;">
                    <label style="font-size: 0.75rem; font-weight: 700; color: #6b7280; text-transform: uppercase; margin-bottom: 0.375rem; display: block;">Anulado por</label>
                    <div style="font-size: 0.95rem; color: #374151; background: #f3f4f6; padding: 0.75rem; border-radius: 6px; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-user" style="color: #6b7280;"></i>
                        ${usuario || 'Sistema'}
                    </div>
                </div>
                <div>
                    <label style="font-size: 0.75rem; font-weight: 700; color: #6b7280; text-transform: uppercase; margin-bottom: 0.375rem; display: block;">Fecha y Hora</label>
                    <div style="font-size: 0.95rem; color: #374151; background: #f3f4f6; padding: 0.75rem; border-radius: 6px; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-calendar" style="color: #6b7280;"></i>
                        ${fecha || 'No disponible'}
                    </div>
                </div>
            </div>
        `;
        
        UI.contenido({
            titulo: ` Motivo de anulación - Pedido #${numeroPedido}`,
            html: html,
            ancho: '500px'
        });
    }


    //  REFACTORIZADO: abrirModalDescripcion() - Usar UIModalService con _ensureSwal
    async function abrirModalDescripcion(pedidoId, tipo) {
        try {
            // Esperar a que Swal esté disponible antes de mostrar modal
            await _ensureSwal(() => {
                UI.cargando('Cargando información...', 'Por favor espera');
            });
            
            const response = await fetch(`/pedidos/${pedidoId}/factura-datos`);
            const result = await response.json();
            const data = result.data || result;
            
            // Cerrar modal de carga usando _ensureSwal
            await _ensureSwal(() => {
                Swal.close();
            });
            
            let htmlContenido = '';
            if (data.prendas && Array.isArray(data.prendas)) {
                htmlContenido += '<div style="margin-bottom: 2rem;">';
                data.prendas.forEach((prenda, idx) => {
                    const descripcionPrenda = construirDescripcionComoPrenda(prenda, idx);
                    htmlContenido += `<div style="margin-bottom: 1.5rem; padding: 1.5rem; background: #f9fafb; border-radius: 8px; border-left: 4px solid #3b82f6;">${descripcionPrenda}`;
                    
                    if (prenda.procesos && Array.isArray(prenda.procesos) && prenda.procesos.length > 0) {
                        htmlContenido += `<div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 2px solid #e5e7eb;"><div style="font-weight: 600; color: #374151; margin-bottom: 1rem; font-size: 1.1rem;">Procesos de Producción</div>`;
                        prenda.procesos.forEach((proceso) => {
                            const descripcionProceso = construirDescripcionComoProceso(prenda, proceso);
                            htmlContenido += `<div style="background: white; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">${descripcionProceso}</div>`;
                        });
                        htmlContenido += '</div>';
                    }
                    htmlContenido += '</div>';
                });
                htmlContenido += '</div>';
            }
            
            // Mostrar contenido usando UI que internamente usa _ensureSwal
            UI.contenido({
                titulo: ' Prendas y Procesos',
                html: htmlContenido,
                ancho: '800px'
            });
        } catch (error) {
            // Cerrar cualquier modal abierto
            await _ensureSwal(() => {
                Swal.close();
            });
            UI.error('Error', 'No se pudo cargar la información');
        }
    }


    // Helper: Construir descripción de prenda
    function construirDescripcionComoPrenda(prenda, numero) {
        const lineas = [];
        if (prenda.nombre_prenda || prenda.nombre) {
            lineas.push(`<div style="font-weight: 700; font-size: 1.1rem; margin-bottom: 0.75rem; color: #1f2937;">PRENDA ${numero + 1}: ${(prenda.nombre_prenda || prenda.nombre).toUpperCase()}</div>`);
        }
        const partes = [];
        if (prenda.tela) partes.push(`<strong>TELA:</strong> ${prenda.tela.toUpperCase()}`);
        if (prenda.color) partes.push(`<strong>COLOR:</strong> ${prenda.color.toUpperCase()}`);
        if (prenda.ref) partes.push(`<strong>REF:</strong> ${prenda.ref.toUpperCase()}`);
        if (prenda.variantes?.length > 0) {
            const manga = prenda.variantes[0].manga;
            if (manga) {
                let mangaTexto = manga.toUpperCase();
                if (prenda.variantes[0].manga_obs?.trim()) {
                    mangaTexto += ` (${prenda.variantes[0].manga_obs.toUpperCase()})`;
                }
                partes.push(`<strong>MANGA:</strong> ${mangaTexto}`);
            }
        }
        if (partes.length > 0) lineas.push(`<div style="margin-bottom: 0.75rem; color: #374151;">${partes.join(' | ')}</div>`);
        if (prenda.descripcion?.trim()) lineas.push(`<div style="margin-bottom: 0.75rem; color: #374151;">${prenda.descripcion.toUpperCase()}</div>`);
        
        const detalles = [];
        if (prenda.variantes?.length > 0) {
            const v = prenda.variantes[0];
            if (v.bolsillos_obs?.trim()) detalles.push(`<div style="margin-bottom: 0.5rem; color: #374151;">• <strong>BOLSILLOS:</strong> ${v.bolsillos_obs.toUpperCase()}</div>`);
            if (v.broche_obs?.trim()) {
                const etiqueta = v.broche?.toUpperCase() || 'BROCHE/BOTÓN';
                detalles.push(`<div style="margin-bottom: 0.5rem; color: #374151;">• <strong>${etiqueta}:</strong> ${v.broche_obs.toUpperCase()}</div>`);
            }
        }
        if (detalles.length > 0) lineas.push(...detalles);
        if (prenda.tallas && Object.keys(prenda.tallas).length > 0) {
            lineas.push(`<div style="margin-top: 0.75rem; margin-bottom: 0.5rem; font-weight: 600; color: #1f2937;">TALLAS</div>`);
            lineas.push(construirTallasFormato(prenda.tallas, prenda.genero));
        }
        return lineas.join('');
    }

    // Helper: Construir descripción de proceso
    function construirDescripcionComoProceso(prenda, proceso) {
        const lineas = [];
        if (proceso.tipo_proceso || proceso.nombre_proceso) {
            lineas.push(`<div style="font-weight: 700; font-size: 1rem; margin-bottom: 0.75rem; color: #1f2937;">${(proceso.tipo_proceso || proceso.nombre_proceso).toUpperCase()}</div>`);
        }
        const partes = [];
        if (prenda.tela) partes.push(`<strong>TELA:</strong> ${prenda.tela.toUpperCase()}`);
        if (prenda.color) partes.push(`<strong>COLOR:</strong> ${prenda.color.toUpperCase()}`);
        if (prenda.ref) partes.push(`<strong>REF:</strong> ${prenda.ref.toUpperCase()}`);
        if (partes.length > 0) lineas.push(`<div style="margin-bottom: 0.75rem; color: #374151;">${partes.join(' | ')}</div>`);
        if (proceso.ubicaciones?.length > 0) {
            lineas.push(`<div style="margin-bottom: 0.5rem; font-weight: 600; color: #1f2937;">UBICACIONES:</div>`);
            proceso.ubicaciones.forEach(u => lineas.push(`<div style="margin-bottom: 0.25rem; color: #374151;">• ${u.toUpperCase()}</div>`));
            lineas.push(`<div style="margin-bottom: 0.75rem;"></div>`);
        }
        if (proceso.observaciones?.trim()) {
            lineas.push(`<div style="margin-bottom: 0.5rem; font-weight: 600; color: #1f2937;">OBSERVACIONES:</div>`);
            lineas.push(`<div style="margin-bottom: 0.75rem; color: #374151;">${proceso.observaciones.toUpperCase()}</div>`);
        }
        if (prenda.tallas && Object.keys(prenda.tallas).length > 0) {
            lineas.push(`<div style="margin-top: 0.75rem; margin-bottom: 0.5rem; font-weight: 600; color: #1f2937;">TALLAS</div>`);
            lineas.push(construirTallasFormato(prenda.tallas, prenda.genero));
        }
        return lineas.join('');
    }

    // Helper: Construir formato de tallas
    function construirTallasFormato(tallas, generoDefault = 'dama') {
        const tallasDama = {}, tallasCalballero = {};
        Object.entries(tallas).forEach(([key, value]) => {
            if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
                const genero = key.toLowerCase();
                Object.entries(value).forEach(([talla, cantidad]) => {
                    if (genero === 'dama') tallasDama[talla] = cantidad;
                    else if (genero === 'caballero') tallasCalballero[talla] = cantidad;
                });
            } else if (typeof value === 'number' || typeof value === 'string') {
                if (key.includes('-')) {
                    const [genero, talla] = key.split('-');
                    if (genero.toLowerCase() === 'dama') tallasDama[talla] = value;
                    else if (genero.toLowerCase() === 'caballero') tallasCalballero[talla] = value;
                } else {
                    const genero = generoDefault || 'dama';
                    if (genero.toLowerCase() === 'dama') tallasDama[key] = value;
                    else if (genero.toLowerCase() === 'caballero') tallasCalballero[key] = value;
                }
            }
        });
        
        let resultado = '';
        if (Object.keys(tallasDama).length > 0) {
            const tallasStr = Object.entries(tallasDama).map(([t, c]) => `<span style="color: #dc2626;"><strong>${t}: ${c}</strong></span>`).join(', ');
            resultado += `<div style="margin-bottom: 0.5rem; color: #374151;">DAMA: ${tallasStr}</div>`;
        }
        if (Object.keys(tallasCalballero).length > 0) {
            const tallasStr = Object.entries(tallasCalballero).map(([t, c]) => `<span style="color: #dc2626;"><strong>${t}: ${c}</strong></span>`).join(', ');
            resultado += `<div style="margin-bottom: 0.5rem; color: #374151;">CABALLERO: ${tallasStr}</div>`;
        }
        return resultado;
    }





    //  FLAG GLOBAL - Prevenir múltiples ediciones simultáneas (Race Condition Fix)
    let edicionEnProgreso = false;

    //  REFACTORIZADO: confirmarEliminarPedido - Usar DeletionService
    function confirmarEliminarPedido(pedidoId, numeroPedido) {
        Deletion.eliminarPedido(pedidoId, numeroPedido);
    }

    /**
     * Editar pedido - OPTIMIZADO CON LAZY LOADING
     * 
     *  CAMBIOS:
     * - Carga módulos de edición bajo demanda (NO en la carga inicial)
     * - SIEMPRE hace fetch para obtener datos completos (modal necesita estructura completa)
     * - Tiempo: <100ms para lazy loader (cacheado), ~500ms para fetch datos
     */
    async function editarPedido(pedidoId) {
        // 🔒 Prevenir múltiples clics simultáneos
        if (window.edicionEnProgreso) {
            return;
        }
        
        window.edicionEnProgreso = true;
        const tiempoInicio = performance.now();
        const etapas = {};
        
        try {
            etapas.inicio = performance.now();
            console.log(`[editarPedido]  Iniciando apertura modal - Pedido: ${pedidoId}`);
            //  PASO 1: Abrir modal pequeño de carga centrado
            console.log('[editarPedido]  Abriendo modal de carga...');
            await _ensureSwal();
            etapas.swalReady = performance.now();
            console.log(`[editarPedido]  Swal listo: ${(etapas.swalReady - etapas.inicio).toFixed(2)}ms`);
            
            // Mostrar modal pequeño con spinner centrado
            const modalPromise = Swal.fire({
                html: `
                    <div style="text-align: center; padding: 2rem;">
                        <div style="width: 60px; height: 60px; border: 4px solid #e5e7eb; border-top-color: #1e40af; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 1.5rem;"></div>
                        <p style="color: #6b7280; font-size: 14px; font-weight: 500; margin: 0;">Cargando datos del pedido...</p>
                    </div>
                    <style>
                        @keyframes spin {
                            to { transform: rotate(360deg); }
                        }
                    </style>
                `,
                width: '300px',
                padding: '0',
                background: 'white',
                showConfirmButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: (modal) => {
                    const swalContainer = document.querySelector('.swal2-container');
                    if (swalContainer) {
                        swalContainer.style.display = 'flex';
                        swalContainer.style.alignItems = 'center';
                        swalContainer.style.justifyContent = 'center';
                    }
                    document.body.style.overflow = 'hidden';
                }
            });

            //  PASO 2: Cargar módulos en segundo plano (con preloader inteligente)
            // Si ya está precargado, esto es casi instantáneo (<1ms)
            // Si no, muestra el loader mientras termina
            if (!window.PrendaEditorPreloader?.isReady?.()) {
                console.log('[editarPedido]  Cargando módulos de edición (con preloader)...');
                try {
                    await window.PrendaEditorPreloader.loadWithLoader({
                        title: 'Cargando datos',
                        message: 'Por favor espera...',
                        onComplete: () => {
                            console.log('[editarPedido]  Módulos cargados completamente');
                        }
                    });
                    etapas.modulosCargados = performance.now();
                    console.log(`[editarPedido]  Módulos cargados: ${(etapas.modulosCargados - etapas.swalReady).toFixed(2)}ms`);
                } catch (error) {
                    console.error('[editarPedido]  Error cargando módulos:', error);
                    Swal.close();
                    UI.error('Error', 'No se pudieron cargar los módulos de edición');
                    window.edicionEnProgreso = false;
                    return;
                }
            } else {
                etapas.modulosCargados = performance.now();
                console.log('[editarPedido] ⚡ Módulos ya precargados en background (cache)');
            }

            //  PASO 3: Fetch de datos mientras el modal ya está visible
            console.log('[editarPedido] 📥 Cargando datos completos del servidor...');

            const response = await fetch(`/asesores/pedidos/${pedidoId}/factura-datos`, {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const respuesta = await response.json();

            if (!respuesta.success) {
                throw new Error(respuesta.message || 'Error desconocido');
            }

            const datos = respuesta.data || respuesta.datos;
            
            //  DEBUG: Verificar qué datos están llegando
            console.log(' [editarPedido] Datos recibidos del servidor:', {
                datos_keys: Object.keys(datos),
                tiene_id: !!datos.id,
                id_valor: datos.id,
                tiene_numero_pedido: !!datos.numero_pedido,
                numero_pedido_valor: datos.numero_pedido,
                tiene_numero: !!datos.numero,
                numero_valor: datos.numero,
                datos_completos: datos
            });
            
            etapas.fetchCompleto = performance.now();
            console.log(`[editarPedido]  Fetch completado: ${(etapas.fetchCompleto - etapas.modulosCargados).toFixed(2)}ms`);
            
            // Transformar datos al formato que espera abrirModalEditarPedido
            const datosTransformados = {
                id: datos.id, // Siempre usar el ID real de la BD
                numero_pedido: datos.numero_pedido || datos.numero,
                numero: datos.numero || datos.numero_pedido,
                cliente: datos.cliente || 'Cliente sin especificar',
                asesora: datos.asesor || datos.asesora?.name || 'Asesor sin especificar',
                estado: datos.estado || 'Pendiente',
                forma_de_pago: datos.forma_pago || datos.forma_de_pago || 'No especificada',
                prendas: datos.prendas || [],
                epps: datos.epps_transformados || datos.epps || [],
                procesos: datos.procesos || [],
                // Copiar todas las otras propiedades
                ...datos
            };

            console.log('[editarPedido]  Datos cargados:', {
                id: datosTransformados.id,
                numero: datosTransformados.numero_pedido,
                cliente: datosTransformados.cliente,
                prendas: datosTransformados.prendas?.length || 0,
                procesos: datosTransformados.procesos?.length || 0
            });

            //  PASO 4: Reemplazar modal de carga con contenido real
            etapas.antes_modal = performance.now();
            console.log(`[editarPedido] 🎬 Abriendo modal de edición...`);
            
            await abrirModalEditarPedido(pedidoId, datosTransformados, 'editar');
            
            etapas.fin = performance.now();
            console.log(`
[editarPedido]  RESUMEN DE TIEMPOS:
  └─ Swal Ready: ${(etapas.swalReady - etapas.inicio).toFixed(2)}ms
  └─ Módulos: ${(etapas.modulosCargados - etapas.swalReady).toFixed(2)}ms
  └─ Fetch: ${(etapas.fetchCompleto - etapas.modulosCargados).toFixed(2)}ms
  └─ Modal: ${(etapas.fin - etapas.antes_modal).toFixed(2)}ms
  └─ TOTAL: ${(etapas.fin - etapas.inicio).toFixed(2)}ms
            `);

        } catch (err) {
            Swal.close();
            console.error('[editarPedido]  Error:', err);
            UI.error('Error', 'No se pudo cargar el pedido: ' + err.message);
            
        } finally {
            window.edicionEnProgreso = false;
        }
    }
    
    
    /**
     * Abrir formulario para editar datos generales del pedido
     */
    function abrirEditarDatos() {
        Validator.requireEdicionPedido(() => {
            const datos = window.datosEdicionPedido;
        
        const html = `
            <div style="text-align: left;">
                <div style="margin-bottom: 1rem;">
                    <label for="editCliente" style="display: block; font-weight: 600; color: #1f2937; margin-bottom: 0.5rem;">Cliente</label>
                    <input type="text" id="editCliente" value="${datos.cliente || ''}" style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 0.95rem;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label for="editFormaPago" style="display: block; font-weight: 600; color: #1f2937; margin-bottom: 0.5rem;">Forma de Pago</label>
                    <input type="text" id="editFormaPago" value="${datos.forma_de_pago || ''}" style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 0.95rem;">
                </div>
            </div>
        `;
        
        UI.contenido({
            titulo: ' Editar Datos Generales',
            html: html,
            confirmButtonText: ' Guardar',
            confirmButtonColor: '#10b981',
            showCancelButton: true
        }).then((result) => {
            if (result.isConfirmed) {
                const datosActualizados = {
                    cliente: document.getElementById('editCliente').value,
                    forma_de_pago: document.getElementById('editFormaPago').value
                };
                
                // Abrir modal de justificación ANTES de guardar
                abrirModalJustificacionCambio(datos.id || datos.numero_pedido, datosActualizados);
            }
        });
        });
    }
    
    /**
     * Abre un modal para justificar los cambios del pedido
     */
    function abrirModalJustificacionCambio(pedidoId, datosActualizados) {
        const html = `
            <div style="text-align: left;">
                <div style="margin-bottom: 1rem;">
                    <label for="justificacion-cambio" style="display: block; font-weight: 600; color: #1f2937; margin-bottom: 0.5rem;">¿Por qué hiciste este cambio?</label>
                    <textarea id="justificacion-cambio" 
                        placeholder="Explica brevemente el motivo de los cambios..." 
                        style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 0.95rem; min-height: 100px; resize: vertical;">
                    </textarea>
                </div>
            </div>
        `;
        
        UI.contenido({
            titulo: 'Registrar Novedad del Cambio',
            html: html,
            confirmButtonText: ' Confirmar y Guardar',
            confirmButtonColor: '#10b981',
            showCancelButton: true
        }).then((result) => {
            if (result.isConfirmed) {
                const justificacion = document.getElementById('justificacion-cambio').value.trim();
                
                if (!justificacion) {
                    showNotification('Debes ingresar una novedad del cambio', 'warning');
                    // Reabrir modal si no hay justificación
                    setTimeout(() => abrirModalJustificacionCambio(pedidoId, datosActualizados), 300);
                    return;
                }
                
                // Agregar justificación a los datos
                datosActualizados.justificacion = justificacion;
                
                // Ahora sí guardar
                guardarCambiosPedido(pedidoId, datosActualizados);
            }
        });
    }
    
    /**
     * Guardar cambios del pedido en el backend
     * FIX: Usar async/await para mejor manejo de race conditions
     */
    async function guardarCambiosPedido(pedidoId, datosActualizados) {
        try {
            //  Esperar a que Swal esté disponible
            await _ensureSwal();
            
            UI.cargando('Guardando cambios...', 'Por favor espera');
            
            //  Hacer fetch
            const response = await fetch(`/api/pedidos/${pedidoId}/actualizar-descripcion`, {
                method: 'PATCH',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    cliente: datosActualizados.cliente || '',
                    forma_de_pago: datosActualizados.forma_de_pago || '',
                    justificacion: datosActualizados.justificacion || ''
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            //  Actualizar la fila en la tabla en tiempo real
            if (data.data) {
                actualizarFilaTabla(pedidoId, data.data);
            }
            
            //  Cerrar modal de carga ANTES de abrir el siguiente
            Swal.close();
            
            //  Actualizar los datos globales
            if (window.datosEdicionPedido) {
                window.datosEdicionPedido.cliente = datosActualizados.cliente;
                window.datosEdicionPedido.forma_de_pago = datosActualizados.forma_de_pago;
                if (data.data && data.data.novedades) {
                    window.datosEdicionPedido.novedades = data.data.novedades;
                }
            }
            
            //  Esperar a que Swal esté disponible para mostrar éxito
            await _ensureSwal();
            
            //  Mostrar modal de confirmación para continuar editando
            Swal.fire({
                title: ' Guardado Exitosamente',
                text: '¿Deseas continuar editando este pedido?',
                icon: 'success',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, continuar editando',
                cancelButtonText: 'No, cerrar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Volver a abrir el modal de edición del pedido
                    abrirModalEditarPedido(window.datosEdicionPedido.id || window.datosEdicionPedido.numero_pedido, window.datosEdicionPedido, 'editar');
                }
                // Ya no necesitamos recargar la página
            });
            
        } catch (error) {
            // Cerrar modal de carga
            Swal.close();
            
            UI.error('Error al guardar', error.message || 'Ocurrió un error al guardar los cambios');
        }
    }

    /**
     * actualizarFilaTabla()
     * Actualiza la fila de la tabla en tiempo real sin recargar la página
     */
    function actualizarFilaTabla(pedidoId, pedidoActualizado) {
        try {
            // Buscar la fila correspondiente en la tabla
            const filas = document.querySelectorAll('[data-pedido-row]');
            
            filas.forEach((fila) => {
                // Verificar si esta fila corresponde al pedido actualizado
                const btnEditarEnFila = fila.querySelector(`button[onclick*="editarPedido(${pedidoId})"]`);
                
                if (btnEditarEnFila) {
                    // Actualizar cliente
                    const cellasCliente = fila.querySelectorAll('div');
                    let indiceCliente = 4; // Índice aproximado de la celda de cliente
                    if (cellasCliente[indiceCliente]) {
                        cellasCliente[indiceCliente].textContent = pedidoActualizado.cliente || '-';
                    }
                    
                    // Actualizar novedades
                    let indiceNovedades = 6; // Índice aproximado de la celda de novedades
                    if (cellasCliente[indiceNovedades]) {
                        if (pedidoActualizado.novedades && pedidoActualizado.novedades.trim()) {
                            cellasCliente[indiceNovedades].textContent = pedidoActualizado.novedades;
                            cellasCliente[indiceNovedades].style.cursor = 'pointer';
                            cellasCliente[indiceNovedades].onclick = function() {
                                abrirModalNovedades(pedidoActualizado.numero_pedido, pedidoActualizado.novedades);
                            };
                        } else {
                            cellasCliente[indiceNovedades].innerHTML = '<span style="color: #d1d5db;">-</span>';
                        }
                    }
                    
                    // Actualizar forma de pago
                    let indiceFormaPago = 7; // Índice aproximado de la celda de forma de pago
                    if (cellasCliente[indiceFormaPago]) {
                        cellasCliente[indiceFormaPago].textContent = pedidoActualizado.forma_de_pago || '-';
                        cellasCliente[indiceFormaPago].style.cursor = 'pointer';
                        cellasCliente[indiceFormaPago].onclick = function() {
                            abrirModalCelda('Forma de Pago', pedidoActualizado.forma_de_pago || '-');
                        };
                    }
                    
                    // Animar la actualización
                    fila.style.backgroundColor = '#fef3c7';
                    setTimeout(() => {
                        fila.style.transition = 'background-color 0.5s ease';
                        fila.style.backgroundColor = 'white';
                    }, 100);
                }
            });
            
        } catch (error) {
        }
    }
    
    // Funciones refactorizadas - Cargar desde componentes:
    // - abrirEditarPrendas() → modal-prendas-lista.blade.php
    // - abrirAgregarPrenda() y guardarNuevaPrenda() → modal-agregar-prenda.blade.php
    // - abrirEditarPrendaEspecifica() → modal-editar-prenda.blade.php
    // - abrirEditarEPP() y abrirEditarEPPEspecifico() → modal-editar-epp.blade.php

    //  REFACTORIZADO: eliminarPedido - DeletionService maneja todo (confirmación, fetch, notificaciones)

    //  REFACTORIZADO: mostrarNotificacion - Usar UIModalService.toastExito()/toastError() en su lugar

    /**
     * Buscador principal: buscar por número de pedido o cliente
     */
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('mainSearchInput');
        const clearButton = document.getElementById('clearMainSearch');
        
        if (!searchInput) return;

        // Función para buscar en las filas
        function searchOrders() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            const rows = document.querySelectorAll('[data-pedido-row]');
            let visibleCount = 0;

            rows.forEach(row => {
                const numeroPedido = (row.getAttribute('data-numero-pedido') || '').toLowerCase();
                const cliente = (row.getAttribute('data-cliente') || '').toLowerCase();
                
                const matches = !searchTerm || 
                               numeroPedido.includes(searchTerm) || 
                               cliente.includes(searchTerm);

                if (matches) {
                    row.style.display = 'grid';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Mostrar/ocultar el botón de limpiar
            if (searchTerm) {
                clearButton.style.display = 'block';
            } else {
                clearButton.style.display = 'none';
            }

            // Mensaje si no hay resultados
            const tableContainer = document.querySelector('.table-scroll-container');
            let noResultsMsg = document.getElementById('noSearchResults');
            
            if (visibleCount === 0 && searchTerm) {
                if (!noResultsMsg) {
                    noResultsMsg = document.createElement('div');
                    noResultsMsg.id = 'noSearchResults';
                    noResultsMsg.style.cssText = 'padding: 2rem; text-align: center; color: #6b7280; font-size: 0.95rem;';
                    noResultsMsg.innerHTML = `
                        <i class="fas fa-search" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.5;"></i>
                        <p style="margin: 0; font-weight: 600;">No se encontraron resultados</p>
                        <p style="margin: 0.25rem 0 0 0; font-size: 0.85rem;">Intenta con otro término de búsqueda</p>
                    `;
                    tableContainer.appendChild(noResultsMsg);
                }
            } else if (noResultsMsg) {
                noResultsMsg.remove();
            }
        }

        // Buscar mientras se escribe (con delay)
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(searchOrders, 300);
        });

        // Limpiar búsqueda
        clearButton.addEventListener('click', function() {
            searchInput.value = '';
            searchOrders();
            searchInput.focus();
        });
    });
</script>
<!--  CORE PEDIDOS (necesario para lista) -->
<script src="{{ asset('js/asesores/pedidos-list.js') }}"></script>
<script src="{{ asset('js/asesores/pedidos.js') }}"></script>
<script src="{{ asset('js/asesores/pedidos-modal.js') }}"></script>
<script src="{{ asset('js/asesores/pedidos-dropdown-simple.js') }}"></script>
<script src="{{ asset('js/asesores/pedidos-anular.js') }}"></script>
<script src="{{ asset('js/asesores/observaciones-despacho.js') }}?v={{ $v }}"></script>

<!--  TRACKING Y RECIBOS (necesario para funcionalidad completa) -->
<script type="module" src="{{ asset('js/modulos/pedidos-recibos/loader.js') }}"></script>
<script src="{{ asset('js/asesores/pedidos-detail-modal.js') }}"></script>
<script src="{{ asset('js/asesores/pedidos-table-filters.js') }}"></script>
<script src="{{ asset('js/orders-scripts/image-gallery-zoom.js') }}"></script>
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

<!--  ORDER TRACKING (MODULAR - necesario) -->
<script src="{{ asset('js/order-tracking/modules/dateUtils.js') }}"></script>
<script src="{{ asset('js/order-tracking/modules/holidayManager.js') }}"></script>
<script src="{{ asset('js/order-tracking/modules/areaMapper.js') }}"></script>
<script src="{{ asset('js/order-tracking/modules/trackingService.js') }}"></script>
<script src="{{ asset('js/order-tracking/modules/trackingUI.js') }}"></script>
<script src="{{ asset('js/order-tracking/modules/apiClient.js') }}"></script>
<script src="{{ asset('js/order-tracking/modules/processManager.js') }}"></script>
<script src="{{ asset('js/order-tracking/modules/tableManager.js') }}"></script>
<script src="{{ asset('js/order-tracking/modules/dropdownManager.js') }}"></script>
<script src="{{ asset('js/order-tracking/orderTracking-v2.js') }}"></script>

<!-- CSS para controlar z-index de modales SweetAlert2 -->
<style>
    /* Estrategia agresiva: z-index muy alto para modal de novedad y sus variantes */
    .swal-modal-novedad,
    .swal-modal-novedad.swal2-container {
        z-index: 999999 !important;
    }
    
    .swal-modal-novedad .swal2-popup,
    .swal-modal-novedad .swal2-modal {
        z-index: 999999 !important;
    }
    
    .swal-modal-novedad .swal2-backdrop {
        z-index: 999998 !important;
    }
    
    /* Modales secundarios (warning, cargando, éxito, error) también con z-index alto */
    .swal-modal-warning,
    .swal-modal-warning.swal2-container,
    .swal-modal-cargando,
    .swal-modal-cargando.swal2-container,
    .swal-modal-exito,
    .swal-modal-exito.swal2-container,
    .swal-modal-error,
    .swal-modal-error.swal2-container {
        z-index: 999999 !important;
    }
    
    /* Popup de SweetAlert */
    .swal-modal-warning .swal2-popup,
    .swal-modal-cargando .swal2-popup,
    .swal-modal-exito .swal2-popup,
    .swal-modal-error .swal2-popup {
        z-index: 999999 !important;
    }
</style>

<!--  SCRIPT: Ocultar loading cuando la página está lista -->
<script>
    (function() {
        
        //  Cuando el DOM esté completamente cargado
        document.addEventListener('DOMContentLoaded', function() {
            
            // Dar un pequeño delay para que todos los scripts se inicialicen
            setTimeout(function() {
                const overlay = document.getElementById('page-loading-overlay');
                
                if (overlay) {
                    // Agregar clase 'hidden' para animar la desaparición
                    overlay.classList.add('hidden');
                    
                    // Remover del DOM después de la animación
                    setTimeout(function() {
                        overlay.remove();
                    }, 400);  // Coincide con duración de transición CSS
                }
            }, 500);  // Pequeño delay para sincronización
        });
        
        // Alternativa: Si por algún motivo pasa mucho tiempo, ocultar después de X segundos
        const maxLoadTime = setTimeout(function() {
            const overlay = document.getElementById('page-loading-overlay');
            if (overlay && !overlay.classList.contains('hidden')) {
                overlay.classList.add('hidden');
                setTimeout(function() {
                    overlay.remove();
                }, 400);
            }
        }, 10000);  // 10 segundos máximo
        
        // Cuando la ventana cargue completamente (incluyendo imágenes)
        window.addEventListener('load', function() {
            clearTimeout(maxLoadTime);  // Cancelar timeout si aún está activo
        });
    })();

    /**
     * abrirModalCelda()
     * Abre modal para mostrar contenido completo de celda truncada
     */
    function abrirModalCelda(titulo, contenido) {
        let contenidoLimpio = contenido || '-';
        contenidoLimpio = contenidoLimpio.replace(/\*\*\*/g, '');
        contenidoLimpio = contenidoLimpio.replace(/\*\*\*\s*[A-Z\s]+:\s*\*\*\*/g, '');
        
        let prendas = contenidoLimpio.split('\n\n').filter(p => p.trim());
        let htmlContenido = '';
        
        prendas.forEach((prenda) => {
            let lineas = prenda.split('\n').map(l => l.trim()).filter(l => l);
            htmlContenido += '<div style="margin-bottom: 1.5rem; padding: 1rem; background: #f9fafb; border-radius: 8px; border-left: 4px solid #3b82f6;">';
            lineas.forEach((linea) => {
                if (linea.match(/^(\d+)\.\s+Prenda:/i) || linea.match(/^Prenda \d+:/i)) {
                    htmlContenido += `<div style="font-weight: 700; font-size: 1rem; margin-bottom: 0.5rem; color: #1f2937;">${linea}</div>`;
                } else if (linea.match(/^Color:|^Tela:|^Manga:/i)) {
                    htmlContenido += `<div style="margin-bottom: 0.5rem; color: #374151;">${linea}</div>`;
                } else if (linea.match(/^DESCRIPCIÓN:/i)) {
                    htmlContenido += `<div style="margin-bottom: 0.5rem; color: #374151;"><strong>${linea}</strong></div>`;
                } else if (linea.match(/^(Reflectivo|Bolsillos|Broche|Ojal):/i)) {
                    htmlContenido += `<div style="margin-bottom: 0.5rem; color: #374151;"><strong>${linea}</strong></div>`;
                } else if (linea.startsWith('•') || linea.startsWith('-')) {
                    htmlContenido += `<div style="margin-left: 1.5rem; margin-bottom: 0.25rem; color: #374151;">• ${linea.substring(1).trim()}</div>`;
                } else if (linea.match(/^Tallas:/i)) {
                    htmlContenido += `<div style="margin-bottom: 0.5rem; color: #374151;"><strong>${linea}</strong></div>`;
                } else if (linea) {
                    htmlContenido += `<div style="margin-bottom: 0.25rem; color: #374151;">${linea}</div>`;
                }
            });
            htmlContenido += '</div>';
        });
        
        const modalHTML = `
            <div id="celdaModal" style="
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                animation: fadeIn 0.3s ease;
            " onclick="if(event.target.id === 'celdaModal') cerrarModalCelda()">
                <div style="
                    background: white;
                    border-radius: 12px;
                    padding: 2rem;
                    max-width: 600px;
                    max-height: 80vh;
                    overflow-y: auto;
                    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
                    animation: slideUp 0.3s ease;
                ">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h2 style="margin: 0; color: #1f2937; font-size: 1.25rem; font-weight: 700;">${titulo}</h2>
                        <button onclick="cerrarModalCelda()" style="
                            background: #f3f4f6;
                            border: none;
                            border-radius: 6px;
                            padding: 0.5rem 0.75rem;
                            cursor: pointer;
                            font-size: 1.25rem;
                            color: #6b7280;
                            transition: all 0.2s;
                        " onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
                            ✕
                        </button>
                    </div>
                    <div style="color: #374151; line-height: 1.6;">
                        ${htmlContenido || contenidoLimpio}
                    </div>
                </div>
            </div>
            <style>
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                @keyframes slideUp {
                    from { transform: translateY(20px); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
            </style>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }

    /**
     * cerrarModalCelda()
     * Cierra el modal de celda
     */
    function cerrarModalCelda() {
        const modal = document.getElementById('celdaModal');
        if (modal) {
            modal.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => {
                modal.remove();
            }, 300);
        }
    }

    /**
     * abrirModalNovedades()
     * Abre modal para mostrar las novedades completas de un pedido
     */
    function abrirModalNovedades(numeroPedido, novedades) {
        if (!novedades || novedades.trim() === '') {
            novedades = 'Sin novedades registradas';
        }

        // Procesar novedades para mostrar con mejor formato
        let bloques = novedades.split('\n\n').filter(b => b.trim());
        let htmlContenido = '';

        bloques.forEach((bloque) => {
            if (bloque.startsWith('📝')) {
                // Extraer información del registro [Usuario - Rol - Fecha]
                let lineas = bloque.split('\n').filter(l => l.trim());
                let primerLinea = lineas[0];
                let resto = lineas.slice(1).join('<br>');
                
                // Parsear la primera línea para extraer datos
                let match = primerLinea.match(/\[(.*?)\]/);
                let info = match ? match[1] : '';
                let novedad = primerLinea.replace(/\[.*?\]\n?/, '') || resto;
                
                htmlContenido += `
                    <div style="margin-bottom: 1.5rem; padding: 1.25rem; background: linear-gradient(135deg, #dbeafe 0%, #e0f2fe 100%); border-left: 5px solid #0284c7; border-radius: 8px; box-shadow: 0 2px 8px rgba(2, 132, 199, 0.1);">
                        <div style="font-weight: 600; color: #0c4a6e; font-size: 0.9rem; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 1.1rem;">👤</span> ${info}
                        </div>
                        <div style="color: #1e40af; line-height: 1.6; font-size: 0.95rem;">
                            ${resto || novedad}
                        </div>
                    </div>
                `;
            } else if (bloque.trim()) {
                htmlContenido += `<div style="margin-bottom: 0.75rem; padding: 0.75rem; color: #374151; background: #f9fafb; border-radius: 6px; border-left: 3px solid #9ca3af;">${bloque}</div>`;
            }
        });

        const modalHTML = `
            <div id="novedadesModal" style="
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                animation: fadeIn 0.3s ease;
            " onclick="if(event.target.id === 'novedadesModal') cerrarModalNovedades()">
                <div style="
                    background: white;
                    border-radius: 12px;
                    padding: 2rem;
                    max-width: 700px;
                    max-height: 80vh;
                    overflow-y: auto;
                    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
                    animation: slideUp 0.3s ease;
                ">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h2 style="margin: 0; color: #1f2937; font-size: 1.25rem; font-weight: 700;">Novedades - Pedido #${numeroPedido}</h2>
                        <button onclick="cerrarModalNovedades()" style="
                            background: #f3f4f6;
                            border: none;
                            border-radius: 6px;
                            padding: 0.5rem 0.75rem;
                            cursor: pointer;
                            font-size: 1.25rem;
                            color: #6b7280;
                            transition: all 0.2s;
                        " onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
                            ✕
                        </button>
                    </div>
                    <div style="color: #374151; line-height: 1.8;">
                        ${htmlContenido}
                    </div>
                </div>
            </div>
            <style>
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                @keyframes slideUp {
                    from { transform: translateY(20px); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
            </style>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }

    /**
     * cerrarModalNovedades()
     * Cierra el modal de novedades
     */
    function cerrarModalNovedades() {
        const modal = document.getElementById('novedadesModal');
        if (modal) {
            modal.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => {
                modal.remove();
            }, 300);
        }
    }

    /**
     * NUEVO: Escuchar evento de actualización de prenda y refrescar tabla en tiempo real
     */
    window.addEventListener('prendaActualizada', async function(event) {
        console.log('[asesores/pedidos] 📢 Evento recibido: prendaActualizada', event.detail);
        
        const { pedidoId } = event.detail;
        
        // Refrescar la tabla automáticamente
        await refrescarTablaPedidos();
    });

    /**
     * refrescarTablaPedidos()
     * Realiza AJAX para recargar la tabla sin refrescar la página
     */
    async function refrescarTablaPedidos() {
        try {
            console.log('[asesores/pedidos]  Refrescando tabla de pedidos...');
            
            // Mantener los parámetros de filtro/búsqueda actuales
            const params = new URLSearchParams(window.location.search);
            const queryString = params.toString() ? '?' + params.toString() : '';
            
            const response = await fetch(`/asesores/pedidos${queryString}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const html = await response.text();
            
            // Extraer solo la tabla del HTML respuesta
            const parser = new DOMParser();
            const newDoc = parser.parseFromString(html, 'text/html');
            const nuevoContenidoTabla = newDoc.querySelector('.table-scroll-container');
            
            if (nuevoContenidoTabla) {
                const tablaActual = document.querySelector('.table-scroll-container');
                if (tablaActual) {
                    tablaActual.innerHTML = nuevoContenidoTabla.innerHTML;
                    console.log('[asesores/pedidos]  Tabla refrescada exitosamente');
                    
                    // Mostrar notificación visual
                    mostrarNotificacionActualizacion();
                }
            }
        } catch (error) {
            console.error('[asesores/pedidos]  Error refrescando tabla:', error);
        }
    }

    /**
     * mostrarNotificacionActualizacion()
     * Muestra notificación visual de actualización
     */
    function mostrarNotificacionActualizacion() {
        const notificacion = document.createElement('div');
        notificacion.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #10b981;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 999999;
            animation: slideInRight 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.95rem;
            font-weight: 500;
        `;
        notificacion.innerHTML = `
            <span class="material-symbols-rounded" style="font-size: 20px;">check_circle</span>
            <span>Tabla actualizada en tiempo real</span>
        `;
        
        document.body.appendChild(notificacion);
        
        // Remover después de 3 segundos
        setTimeout(() => {
            notificacion.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => notificacion.remove(), 300);
        }, 3000);
    }

    // Agregar estilos de animación
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);

    /**
     *  INICIALIZACIÓN DE LAZY LOADERS
     * 
     * Envuelve funciones de interfaz para cargar módulos bajo demanda
     */
    document.addEventListener('DOMContentLoaded', function() {

        //  Inicializar PrendaEditorPreloader si está disponible
        if (window.PrendaEditorPreloader) {
            window.PrendaEditorPreloader.start();
        } else {
            console.warn('[PedidosInit]  PrendaEditorPreloader no encontrado');
        }

        //  Inicializar PrendaEditorLoader si está disponible
        if (window.PrendaEditorLoader) {
        } else {
            console.warn('[PedidosInit]  PrendaEditorLoader no encontrado - revisar script de carga');
        }

        //  Inicializar EPPManagerLoader si está disponible
        if (window.EPPManagerLoader) {
        } else {
            console.warn('[PedidosInit]  EPPManagerLoader no encontrado - revisar script de carga');
        }

        //  Envolver funciones de edición/creación para garantizar lazy loading
        if (typeof abrirModalEditarEPP === 'function') {
            const originalAbrirEPP = window.abrirModalEditarEPP;
            window.abrirModalEditarEPP = async function(...args) {
                if (window.EPPManagerLoader && !window.EPPManagerLoader.isLoaded()) {
                    console.log('[PedidosInit]  Cargando EPP manager antes de abrir...');
                    try {
                        await window.EPPManagerLoader.load();
                    } catch (e) {
                        console.error('[PedidosInit] Error cargando EPP:', e);
                    }
                }
                return originalAbrirEPP.apply(this, args);
            };
            console.log('[PedidosInit]  abrirModalEditarEPP envuelto para lazy loading');
        }

        //  Envolver función para agregar prenda
        if (typeof abrirAgregarPrenda === 'function') {
            const originalAgregar = window.abrirAgregarPrenda;
            window.abrirAgregarPrenda = async function(...args) {
                if (window.PrendaEditorLoader && !window.PrendaEditorLoader.isLoaded()) {
                    console.log('[PedidosInit]  Cargando prenda editor antes de agregar...');
                    try {
                        await window.PrendaEditorLoader.load();
                    } catch (e) {
                        console.error('[PedidosInit] Error cargando prenda editor:', e);
                    }
                }
                return originalAgregar.apply(this, args);
            };
        }

    });
</script>

<!-- Servicio de imágenes - Para edición de prendas -->
<script src="{{ asset('js/modulos/crear-pedido/fotos/image-storage-service.js') }}"></script>

<!-- Inicializador de servicios de imágenes - Para edición de prendas -->
<script src="{{ asset('js/modulos/crear-pedido/inicializadores/init-storage-servicios.js') }}"></script>

<!-- Manejadores de procesos - Para edición de procesos desde pedidos/index -->
<script src="{{ asset('js/modulos/crear-pedido/procesos/manejadores-procesos-prenda.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/procesos/gestor-modal-proceso-generico.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/procesos/renderizador-tarjetas-procesos.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/componentes/procesos-imagenes-storage.js') }}"></script>
<script src="{{ asset('js/componentes/manejo-imagenes-proceso.js') }}"></script>
<script src="{{ asset('js/componentes/manejador-imagen-proceso-con-indice.js') }}"></script>


@endpush

