@extends('layouts.asesores')

@include('components.modal-imagen')

@section('extra_styles')
    <link rel="stylesheet" href="{{ asset('css/crear-pedido.css') }}">
    <link rel="stylesheet" href="{{ asset('css/crear-pedido-editable.css') }}">
    <link rel="stylesheet" href="{{ asset('css/form-modal-consistency.css') }}">
    <link rel="stylesheet" href="{{ asset('css/swal-z-index-fix.css') }}">
@endsection

@section('content')
    @php
        // Modo edición: usar el flujo de cotización como base
        $tipo = 'cotizacion';
        $esModoEdicion = true;
        $pedidoEdicion = $pedido ?? null;
    @endphp
    
    <!-- CRÍTICO: Definir datos del pedido ANTES de que se cargue crear-pedido-desde-cotizacion -->
    <script>
        console.log('🔥 [editar-pedido] Definiendo datos del pedido ANTES de todo...');
        
        window.modoEdicion = true;
        window.pedidoEdicionId = {{ $pedido->id }};
        window.pedidoEdicionData = @json($pedidoData);
        
        console.log('🔥 [editar-pedido] pedidoData recibido:', window.pedidoEdicionData);
        console.log('🔥 [editar-pedido] ¿Tiene procesos?', 'procesos' in window.pedidoEdicionData);
        
        // IMPORTANTE: Para que abrirEditarPrendaModal() funcione correctamente
        // Establecer datosEdicionPedido con la estructura que espera
        window.datosEdicionPedido = {
            numero_pedido: {{ $pedido->id }},
            id: {{ $pedido->id }},
            cliente: window.pedidoEdicionData.cliente || '{{ $pedido->cliente }}',
            forma_de_pago: window.pedidoEdicionData.forma_de_pago || '{{ $pedido->forma_de_pago }}',
            novedades: window.pedidoEdicionData.descripcion || '{{ $pedido->novedades }}',
            ...(window.pedidoEdicionData && window.pedidoEdicionData.pedido ? window.pedidoEdicionData.pedido : {})
        };
        
        // Establecer en body para que obtenerPedidoId() lo encuentre
        document.body.dataset.pedidoIdEdicion = {{ $pedido->id }};
        
        console.log('🔥 [editar-pedido] window.datosEdicionPedido =', window.datosEdicionPedido);
    </script>

    <!-- Loading Overlay de Página Completa -->
    <div id="page-loading-overlay">
        <div class="loading-spinner"></div>
        <div class="loading-text">Cargando editor de pedidos...</div>
        <div class="loading-subtext">Por favor espera mientras preparamos todo</div>
    </div>

    <!-- Contenedor para factura editable con botones -->
    <div id="factura-container-editable" style="margin: 1rem 0; padding: 1rem; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb;">
        <!-- La factura se renderizará aquí -->
    </div>

    {{-- Flujo para editar pedidos existentes --}}
    @include('asesores.pedidos.crear-pedido-desde-cotizacion')

    <!-- Script para renderizar factura editable - Se ejecuta después de que TODA la página carga -->
    <script src="{{ asset('js/invoice-preview-live.js') }}?v={{ time() }}"></script>
    <script>
        window.addEventListener('load', function() {
            console.log('🔥 [FACTURA-EDITABLE] Página completamente cargada, ejecutando...');
            
            // Esperar un poco más para que invoice-preview-live se cargue completamente
            setTimeout(function() {
                console.log(' [FACTURA-EDITABLE] Buscando datos...');
                console.log('   - generarHTMLFactura:', typeof window.generarHTMLFactura);
                console.log('   - pedidoEdicionData:', typeof window.pedidoEdicionData);
                console.log('   - pedidoEdicionData.pedido:', window.pedidoEdicionData?.pedido ? 'SÍ' : 'NO');
                console.log('   - prendas:', window.pedidoEdicionData?.pedido?.prendas?.length || 0);
                
                // Función para renderizar la factura
                function renderizarFacturaEditableEnPagina() {
                    if (typeof generarHTMLFactura === 'function' && window.pedidoEdicionData?.pedido?.prendas) {
                        console.log(' [FACTURA-EDITABLE] Condiciones cumplidas, renderizando...');
                        
                        const datos = {
                            numero_pedido: window.pedidoEdicionData.pedido.numero_pedido || window.pedidoEdicionId,
                            numero_pedido_temporal: window.pedidoEdicionData.pedido.numero_pedido_temporal,
                            cliente: window.pedidoEdicionData.pedido.cliente || '',
                            asesora: window.pedidoEdicionData.pedido.asesora || '',
                            forma_de_pago: window.pedidoEdicionData.pedido.forma_de_pago || '',
                            prendas: window.pedidoEdicionData.pedido.prendas || [],
                            procesos: window.pedidoEdicionData.pedido.procesos || [],
                            epps: window.pedidoEdicionData['epps_transformados'] || window.pedidoEdicionData.pedido.epps || []
                        };
                        
                        console.log(' [FACTURA-EDITABLE] Datos preparados:', {
                            prendas: datos.prendas.length,
                            epps: datos.epps.length
                        });
                        
                        try {
                            const htmlFactura = generarHTMLFactura(datos);
                            const contenedor = document.getElementById('factura-container-editable');
                            if (contenedor) {
                                contenedor.innerHTML = htmlFactura;
                                console.log(' [FACTURA-EDITABLE] FACTURA RENDERIZADA EXITOSAMENTE');
                                return true;
                            } else {
                                console.log(' [FACTURA-EDITABLE] Contenedor no encontrado');
                                return false;
                            }
                        } catch (e) {
                            console.error(' [FACTURA-EDITABLE] Error al renderizar:', e);
                            return false;
                        }
                    } else {
                        console.log('⏳ [FACTURA-EDITABLE] Esperando datos...', {
                            tieneGenerarHTMLFactura: typeof window.generarHTMLFactura === 'function',
                            tienePedidoEdicionData: !!window.pedidoEdicionData,
                            tienePrendas: !!window.pedidoEdicionData?.pedido?.prendas
                        });
                        return false;
                    }
                }
                
                // Ejecutar cada 300ms
                console.log(' [FACTURA-EDITABLE] Iniciando intervalo de renderización');
                let intentos = 0;
                const intervalo = setInterval(function() {
                    intentos++;
                    if (renderizarFacturaEditableEnPagina()) {
                        clearInterval(intervalo);
                        console.log('🎉 [FACTURA-EDITABLE] Listo en intento ' + intentos);
                    } else if (intentos >= 50) {
                        clearInterval(intervalo);
                        console.log(' [FACTURA-EDITABLE] Timeout después de ' + intentos + ' intentos');
                    }
                }, 300);
            }, 1000); // Esperar 1 segundo después de que la página carga
        });
    </script>

@endsection

@push('scripts')
    <!-- Script de edición - carga DESPUÉS de todos los demás módulos -->
    <script src="{{ asset('js/modulos/crear-pedido/edicion/cargar-datos-edicion.js') }}"></script>
    
    <!-- Componente para editar prendas con procesos desde API -->
    <script src="{{ asset('js/componentes/prenda-card-editar-simple.js') }}"></script>
@endpush


