@extends('layouts.asesores')

@section('title', 'Mis Pedidos')
@section('page-title', 'Mis Pedidos')

@section('extra_styles')
    <link rel="stylesheet" href="{{ asset('css/asesores/pedidos/index.css') }}">
    <!-- CSS necesarios para el modal de crear/editar prendas -->
    <link rel="stylesheet" href="{{ asset('css/crear-pedido.css') }}">
    <link rel="stylesheet" href="{{ asset('css/crear-pedido-editable.css') }}">
    <link rel="stylesheet" href="{{ asset('css/form-modal-consistency.css') }}">
    <link rel="stylesheet" href="{{ asset('css/componentes/prendas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/componentes/reflectivo.css') }}">
    <!-- CSS del modal EPP -->
    <link rel="stylesheet" href="{{ asset('css/modulos/epp-modal.css') }}">
    <!-- CSS de modales personalizados (EPP y Prendas) -->
    <link rel="stylesheet" href="{{ asset('css/modales-personalizados.css') }}">
@endsection

@section('content')

    @include('asesores.pedidos.components.header')

    @include('asesores.pedidos.components.quick-filters')

    @include('asesores.pedidos.components.table')

    @include('asesores.pedidos.components.modals')

@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/asesores/pedidos.css') }}">
@endpush

@push('scripts')
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

<!--  SERVICIOS CENTRALIZADOS - Cargar PRIMERO -->
<script src="{{ asset('js/utilidades/validation-service.js') }}"></script>
<script src="{{ asset('js/utilidades/ui-modal-service.js') }}"></script>
<script src="{{ asset('js/utilidades/deletion-service.js') }}"></script>
<script src="{{ asset('js/utilidades/galeria-service.js') }}"></script>

<script>
    //  Configurar variables globales
    window.fetchUrl = '/registros';
    window.modalContext = 'pedidos';

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


    //  REFACTORIZADO: abrirModalDescripcion() - Usar UIModalService
    async function abrirModalDescripcion(pedidoId, tipo) {
        try {
            UI.cargando('Cargando información...', 'Por favor espera');
            
            const response = await fetch(`/asesores/pedidos/${pedidoId}/recibos-datos`);
            const data = await response.json();
            
            Swal.close();
            
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
            
            UI.contenido({
                titulo: ' Prendas y Procesos',
                html: htmlContenido,
                ancho: '800px'
            });
        } catch (error) {
            Swal.close();
            UI.error('Error', 'No se pudo cargar la información');
            console.error('Error:', error);
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





    //  REFACTORIZADO: confirmarEliminarPedido - Usar DeletionService
    function confirmarEliminarPedido(pedidoId, numeroPedido) {
        Deletion.eliminarPedido(pedidoId, numeroPedido);
    }

    /**
     * Editar pedido - carga datos y abre modal de edición
     */
    function editarPedido(pedidoId) {
        UI.cargando('Cargando datos del pedido...', 'Por favor espera');
        
        fetch(`/asesores/pedidos-produccion/${pedidoId}/datos-edicion`)
            .then(res => res.json())
            .then(respuesta => {
                Swal.close();
                if (!respuesta.success) throw new Error(respuesta.message || 'Error al cargar datos');
                abrirModalEditarPedido(pedidoId, respuesta.datos, 'editar');  // Pasar 'editar' para mostrar botones
            })
            .catch(err => {
                Swal.close();
                UI.error('Error', 'No se pudo cargar el pedido: ' + err.message);
            });
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
                    <label style="display: block; font-weight: 600; color: #1f2937; margin-bottom: 0.5rem;">Cliente</label>
                    <input type="text" id="editCliente" value="${datos.cliente || ''}" style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 0.95rem;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 600; color: #1f2937; margin-bottom: 0.5rem;">Forma de Pago</label>
                    <input type="text" id="editFormaPago" value="${datos.forma_de_pago || ''}" style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 0.95rem;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 600; color: #1f2937; margin-bottom: 0.5rem;">Novedades</label>
                    <textarea id="editNovedades" style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 0.95rem; min-height: 100px;">${datos.novedades || ''}</textarea>
                </div>
            </div>
        `;
        
        UI.contenido({
            titulo: ' Editar Datos Generales',
            html: html,
            confirmButtonText: '💾 Guardar',
            confirmButtonColor: '#10b981',
            showCancelButton: true
        }).then((result) => {
            if (result.isConfirmed) {
                const datosActualizados = {
                    cliente: document.getElementById('editCliente').value,
                    forma_de_pago: document.getElementById('editFormaPago').value,
                    novedades: document.getElementById('editNovedades').value
                };
                
                guardarCambiosPedido(datos.id || datos.numero_pedido, datosActualizados);
            }
        });
        });
    }
    
    /**
     * Guardar cambios del pedido en el backend
     */
    function guardarCambiosPedido(pedidoId, datosActualizados) {
        UI.cargando('Guardando cambios...', 'Por favor espera');
        
        fetch(`/asesores/pedidos/${pedidoId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(datosActualizados)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            Swal.close();
            
            // Actualizar los datos globales
            if (window.datosEdicionPedido) {
                window.datosEdicionPedido.cliente = datosActualizados.cliente;
                window.datosEdicionPedido.forma_de_pago = datosActualizados.forma_de_pago;
                window.datosEdicionPedido.novedades = datosActualizados.novedades;
            }
            
            // Mostrar modal de confirmación para continuar editando
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
                } else {
                    // Recargar la tabla de pedidos
                    setTimeout(() => {
                        location.reload();
                    }, 500);
                }
            });
        })
        .catch(error => {
            console.error('Error al guardar cambios:', error);
            Swal.close();
            UI.error('Error al guardar', error.message || 'Ocurrió un error al guardar los cambios');
        });
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
<script src="{{ asset('js/asesores/pedidos-list.js') }}"></script>
<script src="{{ asset('js/asesores/pedidos.js') }}"></script>
<script src="{{ asset('js/asesores/pedidos-modal.js') }}"></script>
<script src="{{ asset('js/asesores/pedidos-dropdown-simple.js') }}"></script>
<script src="{{ asset('js/asesores/pedidos-anular.js') }}"></script>
<!-- Modal Manager para renderizar detalles del pedido (igual que órdenes) -->
<script src="{{ asset('js/orders js/order-detail-modal-manager.js') }}"></script>
<!-- NUEVO: Módulo de recibos dinámicos (refactorizado en componentes) -->
<script type="module" src="{{ asset('js/modulos/pedidos-recibos/loader.js') }}"></script>
<script src="{{ asset('js/asesores/pedidos-detail-modal.js') }}"></script>
<script src="{{ asset('js/asesores/pedidos-table-filters.js') }}"></script>
<!-- Image Gallery para mostrar fotos en el modal -->
<script src="{{ asset('js/orders-scripts/image-gallery-zoom.js') }}"></script>
<!-- Invoice Preview (necesario para generarHTMLFactura) -->
<script src="{{ asset('js/invoice-preview-live.js') }}"></script>
<!-- Invoice Preview desde Lista de Pedidos -->
<script src="{{ asset('js/asesores/invoice-from-list.js') }}"></script>

<!-- Módulos para gestionar prendas en el modal -->
<script src="{{ asset('js/configuraciones/constantes-tallas.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/fotos/image-storage-service.js') }}"></script>

<!-- Inicializar storages INMEDIATAMENTE (ANTES de que se cargue gestion-telas.js) -->
<script>
    //  CRÍTICO: Esto se ejecuta INMEDIATAMENTE
    if (!window.imagenesPrendaStorage) {
        window.imagenesPrendaStorage = new ImageStorageService(3);
        console.log(' [INDEX] imagenesPrendaStorage inicializado INMEDIATAMENTE');
    }
    if (!window.imagenesTelaStorage) {
        window.imagenesTelaStorage = new ImageStorageService(3);
        console.log(' [INDEX] imagenesTelaStorage inicializado INMEDIATAMENTE');
    }
    if (!window.imagenesReflectivoStorage) {
        window.imagenesReflectivoStorage = new ImageStorageService(3);
        console.log(' [INDEX] imagenesReflectivoStorage inicializado INMEDIATAMENTE');
    }
    if (!window.telasAgregadas) {
        window.telasAgregadas = [];
    }
    if (!window.procesosSeleccionados) {
        window.procesosSeleccionados = {};
    }
</script>

<!-- Ahora cargar gestion-telas.js (con imagenesTelaStorage YA disponible) -->
<script src="{{ asset('js/modulos/crear-pedido/telas/gestion-telas.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/tallas/gestion-tallas.js') }}"></script>

<!-- Manejadores de variaciones (manga, bolsillos, broche) -->
<script src="{{ asset('js/modulos/crear-pedido/prendas/manejadores-variaciones.js') }}"></script>

<!-- Wrappers de prendas -->
<script src="{{ asset('js/componentes/prendas-wrappers.js') }}"></script>

<!-- Utilidades DOM - Necesario para modal-cleanup.js -->
<script src="{{ asset('js/utilidades/dom-utils.js') }}"></script>

<!-- Constantes de items pedido - Necesario para modal-cleanup.js -->
<script src="{{ asset('js/modulos/crear-pedido/procesos/gestion-items-pedido-constantes.js') }}"></script>

<!-- Modal Cleanup - CRÍTICO para limpiar y preparar el modal correctamente -->
<script src="{{ asset('js/utilidades/modal-cleanup.js') }}"></script>

<!--  SERVICIOS SOLID - Deben cargarse ANTES de GestionItemsUI -->
<script src="{{ asset('js/modulos/crear-pedido/procesos/services/notification-service.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/procesos/services/item-api-service.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/procesos/services/item-validator.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/procesos/services/item-form-collector.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/procesos/services/item-renderer.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/procesos/services/prenda-editor.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/procesos/services/item-orchestrator.js') }}?v={{ time() }}"></script>

<!-- Componentes de Modales - Deben cargarse ANTES de GestionItemsUI -->
<script src="{{ asset('js/componentes/modal-novedad-prenda.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/componentes/modal-novedad-edicion.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/componentes/prenda-form-collector.js') }}?v={{ time() }}"></script>

<script src="{{ asset('js/modulos/crear-pedido/procesos/gestion-items-pedido.js') }}?v={{ time() }}"></script>

<!-- Dependencias para Modal Dinámico -->
<script src="{{ asset('js/modulos/crear-pedido/configuracion/api-pedidos-editable.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/procesos/manejadores-procesos-prenda.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/procesos/gestor-modal-proceso-generico.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/procesos/renderizador-tarjetas-procesos.js') }}"></script>

<!-- Modal Dinámico: Constantes HTML (DEBE cargarse ANTES del modal principal) -->
<script src="{{ asset('js/componentes/modal-prenda-dinamico-constantes.js') }}"></script>

<!-- Modal Dinámico: Prenda Nueva -->
<script src="{{ asset('js/componentes/modal-prenda-dinamico.js') }}"></script>

<!-- Componente: Editor de Prendas Modal -->
<script src="{{ asset('js/componentes/prenda-editor-modal.js') }}"></script>
<!-- EPP Services - Deben cargarse ANTES del modal -->
<script src="{{ asset('js/modulos/crear-pedido/epp/services/epp-api-service.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/epp/services/epp-state-manager.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/epp/services/epp-modal-manager.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/epp/services/epp-item-manager.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/epp/services/epp-imagen-manager.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/epp/services/epp-service.js') }}"></script>

<!-- EPP Services SOLID - Mejoras de refactorización -->
<script src="{{ asset('js/modulos/crear-pedido/epp/services/epp-notification-service.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/epp/services/epp-creation-service.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/epp/services/epp-form-manager.js') }}"></script>

<!-- EPP Templates e Interfaces -->
<script src="{{ asset('js/modulos/crear-pedido/epp/templates/epp-modal-template.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/epp/interfaces/epp-modal-interface.js') }}"></script>

<!-- EPP Initialization -->
<script src="{{ asset('js/modulos/crear-pedido/epp/epp-init.js') }}"></script>

<!-- Modal EPP (refactorizado) - Carga DESPUÉS de los servicios -->
<script src="{{ asset('js/modulos/crear-pedido/modales/modal-agregar-epp.js') }}"></script>

<!-- MODULAR ORDER TRACKING (SOLID Architecture) -->
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

@endpush
