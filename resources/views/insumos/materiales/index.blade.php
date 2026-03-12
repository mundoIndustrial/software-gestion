{{-- resources/views/insumos/materiales/index.blade.php --}}
@extends('layouts.insumos')

@section('title', 'Gestión de Insumos - Control de Insumos del Pedido')
@section('page-title', 'Control de Insumos del Pedido')

@section('content')
<link rel="stylesheet" href="{{ asset('css/insumos/materiales.css') }}?v={{ time() }}">
<style>
    /* Ocultar el top-nav del layout para esta vista */
    .top-nav {
        display: none !important;
    }
    
    /* Ajustar page-content para que no tenga padding superior */
    .page-content {
        padding: 0 !important;
        margin: 0 !important;
    }
    
    /* FIX: Remover max-width del container para insumos */
    .container {
        max-width: none !important;
        width: 100% !important;
        margin-left: 0 !important;
        padding: 1.5rem !important;
    }
    
    /* Hacer el thead sticky */
    table thead {
        position: sticky;
        top: 0;
        z-index: 10;
        background-color: inherit;
    }
    
    table thead tr {
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    table thead th {
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    /* Tooltips mejorados */
    .btn-tooltip {
        position: relative;
    }
    
    .btn-tooltip:hover::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        background-color: #1f2937;
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-size: 0.65rem;
        white-space: nowrap;
        z-index: 50000;
        margin-bottom: 0.25rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .btn-tooltip:hover::before {
        content: '';
        position: absolute;
        bottom: calc(100% - 0.15rem);
        left: 50%;
        transform: translateX(-50%);
        border: 0.25rem solid transparent;
        border-top-color: #1f2937;
        z-index: 50000;
    }
    
    /* Permitir que los tooltips se muestren sin ser cortados */
    td {
        overflow: visible !important;
    }
    
    /* Asegurar que la columna de acciones sea visible */
    td:last-child {
        overflow: visible !important;
        display: table-cell !important;
        min-width: 200px !important;
    }
    
    /* Asegurar que los botones sean visibles en la celda de acciones */
    td:last-child > div {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0.75rem !important;
        flex-wrap: wrap !important;
        overflow: visible !important;
    }
    
    td:last-child button {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    /* Indicador de carga */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.3);
        display: none;
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }
    
    .loading-overlay.active {
        display: flex;
    }
    
    .loading-spinner {
        width: 50px;
        height: 50px;
        border: 5px solid #f3f3f3;
        border-top: 5px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Mejorar contraste en modo oscuro para filas hover */
    @media (prefers-color-scheme: dark) {
        tbody tr:hover {
            background-color: rgba(59, 130, 246, 0.1) !important;
        }
    }
    
    /* Hover normal en modo claro */
    @media (prefers-color-scheme: light) {
        tbody tr:hover {
            background-color: #f9fafb !important;
        }
    }

    /* Responsive Design para Tabla */
    @media (max-width: 1024px) {
        table {
            font-size: 0.7em !important;
        }
        
        th, td {
            padding: 0.5rem !important;
        }
    }

    @media (max-width: 768px) {
        table {
            font-size: 0.65em !important;
        }
        
        th, td {
            padding: 0.35rem !important;
        }
        
        .overflow-x-auto {
            -webkit-overflow-scrolling: touch;
        }
    }

    @media (max-width: 480px) {
        table {
            font-size: 0.6em !important;
        }
        
        th, td {
            padding: 0.25rem !important;
        }
    }

    /* Estilos para el botón de check (marca) */
    .btn-check-row {
        position: relative;
        transition: all 0.3s ease;
        min-width: 40px;
        flex-shrink: 0;
    }

    /* Estilos para el botón de acciones (menú) */
    .btn-acciones {
        position: relative;
        min-width: 40px;
        flex-shrink: 0;
    }

    .btn-check-row.checked {
        background-color: #a78bfa !important;
        color: white !important;
    }

    /* Estilos para filas marcadas - aplicar color a toda la fila */
    tr.row-checked {
        background-color: #ede9fe !important;
    }

    tr.row-checked:hover {
        background-color: #ddd6fe !important;
    }

    /* Asegurar que la celda de acciones también tenga el color en filas marcadas */
    tr.row-checked td:first-child {
        background-color: #ede9fe !important;
    }

    tr.row-checked:hover td:first-child {
        background-color: #ddd6fe !important;
    }

    /* Optimizar espaciado de botones en acciones */
    td:first-child .flex {
        gap: 0.25rem !important;
        flex-wrap: nowrap !important;
        min-width: 0;
        overflow-x: auto;
        overflow-y: hidden;
    }

    td:first-child .flex button {
        flex-shrink: 0;
        min-width: 40px;
        min-height: 40px;
        padding: 0.5rem !important;
    }
</style>

@if(app()->isLocal())
<script>
    console.time('RENDER_TOTAL');
</script>
@endif
<script>
    // Lazy load images cuando estén visibles
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    }
                    observer.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => imageObserver.observe(img));
    }

    /**
     * Calcula los días de demora entre Fecha Pedido y Fecha Llegada EN TIEMPO REAL
     */
    function calcularDemora(materialId) {
        // El materialId tiene formato: material_PEDIDO_INDEX_NOMBRE
        // Necesitamos extraer PEDIDO e INDEX
        const idParts = materialId.split('_');
        
        // Si tiene más de 3 partes, es porque el nombre tiene guiones
        // Formato: ['material', 'PEDIDO', 'INDEX', 'NOMBRE', ...]
        const ordenId = idParts[1];
        const index = idParts[2];
        
        // Reconstruir los IDs de fecha con el mismo formato
        const fechaPedidoInput = document.getElementById('fecha_pedido_' + ordenId + '_' + index + '_' + idParts.slice(3).join('_'));
        const fechaLlegadaInput = document.getElementById('fecha_llegada_' + ordenId + '_' + index + '_' + idParts.slice(3).join('_'));
        const diasSpan = document.getElementById('dias_' + materialId);
        
        if (!fechaPedidoInput || !fechaLlegadaInput || !diasSpan) {
            return;
        }
        
        // Solo calcular si ambas fechas están completas
        if (fechaPedidoInput.value && fechaLlegadaInput.value) {
            const fechaPedido = new Date(fechaPedidoInput.value + 'T00:00:00');
            const fechaLlegada = new Date(fechaLlegadaInput.value + 'T00:00:00');
            
            // Calcular diferencia en días
            const diferencia = Math.floor((fechaLlegada - fechaPedido) / (1000 * 60 * 60 * 24));
            
            // Color según demora
            let bgColor = 'bg-gray-100';
            let textColor = 'text-gray-600';
            let icon = '';
            
            if (diferencia <= 0) {
                bgColor = 'bg-green-100';
                textColor = 'text-green-700';
                icon = '✓ ';
            } else if (diferencia <= 5) {
                bgColor = 'bg-yellow-100';
                textColor = 'text-yellow-700';
                icon = '⚠ ';
            } else {
                bgColor = 'bg-red-100';
                textColor = 'text-red-700';
                icon = '✕ ';
            }
            
            diasSpan.textContent = icon + diferencia + ' días';
            diasSpan.className = `inline-block px-3 py-1 rounded-full text-sm font-semibold ${bgColor} ${textColor}`;
        } else {
            diasSpan.textContent = '-';
            diasSpan.className = 'inline-block px-3 py-1 rounded-full text-sm font-semibold bg-gray-100 text-gray-600';
        }
    }

    /**
     * Alterna el estado de una fila (marcada/sin marcar) con color púrpura claro
     */
    function toggleRowCheck(button, event) {
        event.preventDefault();
        event.stopPropagation();
        
        // Encontrar la fila (tr) del botón
        const row = button.closest('tr');
        if (!row) return;
        
        // Alternar clase de marcado en el botón
        button.classList.toggle('checked');
        
        // Alternar clase de marcado en la fila
        row.classList.toggle('row-checked');
        
        // Obtener el estado marcado actual
        const isMarcado = button.classList.contains('checked');
        
        // Aquí podemos obtener el ID del material desde algún atributo del row
        // Por ahora usamos un data-id que debemos agregar en la tabla
        const materialId = row.dataset.materialId || row.dataset.reciboId;
        
        if (materialId) {
            // Enviar petición AJAX para guardar el estado
            guardarEstadoMarcado(materialId, isMarcado, button);
        }
    }

    /**
     * Envía el estado de marcado al servidor
     */
    function guardarEstadoMarcado(materialId, marcado, button) {
        if (!materialId) {
            console.error('Material ID no disponible');
            return;
        }
        
        const url = `/insumos/materiales/${materialId}/toggle-marcado`;
        
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                marcado: marcado
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Estado de marcado guardado correctamente', data);
            } else {
                console.error('Error al guardar el estado:', data.message);
                // Revertir si hay error
                button.classList.toggle('checked');
                button.closest('tr').classList.toggle('row-checked');
                alert('Error al guardar: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error en la petición:', error);
            // Revertir si hay error
            button.classList.toggle('checked');
            button.closest('tr').classList.toggle('row-checked');
            alert('Error al guardar el estado');
        });
    }

    // Variable global para rastrear el botón del dropdown abierto
    let dropdownAbiertoButton = null;

    /**
     * Crear dropdown de acciones posicionado de forma fija
     */
    function crearDropdownAcciones(event, button) {
        event.preventDefault();
        event.stopPropagation();
        
        // Si el dropdown está abierto del mismo botón, cerrarlo
        if (dropdownAbiertoButton === button) {
            cerrarDropdownAcciones();
            dropdownAbiertoButton = null;
            return;
        }
        
        // Cerrar dropdown anterior si existe
        cerrarDropdownAcciones();
        
        // Guardar referencia al botón actual
        dropdownAbiertoButton = button;
        
        const container = document.getElementById('dropdowns-container');
        if (!container) return;
        
        // Obtener datos del botón
        const pedidoProduccionId = button.getAttribute('data-pedido-produccion-id');
        const prendaId = button.getAttribute('data-prenda-id');
        const reciboId = button.getAttribute('data-recibo-id');
        const consecutivo = button.getAttribute('data-consecutivo');
        const estado = button.getAttribute('data-estado');
        const tipoRecibo = button.getAttribute('data-tipo-recibo');
        
        // Obtener posición del botón
        const rect = button.getBoundingClientRect();
        
        // Crear elemento del dropdown
        const dropdown = document.createElement('div');
        dropdown.className = 'acciones-dropdown-fixed';
        dropdown.style.cssText = `
            position: fixed;
            top: ${rect.bottom + 8}px;
            left: ${rect.right + 8}px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            min-width: 220px;
            z-index: 999999;
            overflow: visible;
            pointer-events: auto;
        `;
        
        let html = `
            <button style="
                width: 100%;
                text-align: left;
                padding: 0.875rem 1rem;
                border: none;
                background: transparent;
                cursor: pointer;
                color: #374151;
                font-size: 0.875rem;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                gap: 0.75rem;
                font-weight: 500;
                border-bottom: 1px solid #f3f4f6;
            " onclick="abrirModalInsumos('${pedidoProduccionId}', '${prendaId}'); cerrarDropdownAcciones();" 
            onmouseover="this.style.background='#f0fdf4'" 
            onmouseout="this.style.background='transparent'">
                <i class="fas fa-box" style="color: #10b981; font-size: 1rem;"></i>
                <span>Gestionar materiales</span>
            </button>
            
            <button style="
                width: 100%;
                text-align: left;
                padding: 0.875rem 1rem;
                border: none;
                background: transparent;
                cursor: pointer;
                color: #374151;
                font-size: 0.875rem;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                gap: 0.75rem;
                font-weight: 500;
                border-bottom: 1px solid #f3f4f6;
            " onclick="abrirModalAnchoMetraje('${pedidoProduccionId}', '${prendaId}'); cerrarDropdownAcciones();" 
            onmouseover="this.style.background='#fef3c7'" 
            onmouseout="this.style.background='transparent'">
                <i class="fas fa-ruler" style="color: #f59e0b; font-size: 1rem;"></i>
                <span>Ancho y metraje</span>
            </button>
        `;
        
        // Agregar botón "Pasar a Revisar" solo si NO está en estado DEVUELTO_ASESOR
        if (estado !== 'DEVUELTO_ASESOR') {
            html += `
            <button style="
                width: 100%;
                text-align: left;
                padding: 0.875rem 1rem;
                border: none;
                background: transparent;
                cursor: pointer;
                color: #374151;
                font-size: 0.875rem;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                gap: 0.75rem;
                font-weight: 500;
                border-bottom: 1px solid #f3f4f6;
            " onclick="abrirModalPasarRevisar('${reciboId}', '${pedidoProduccionId}'); cerrarDropdownAcciones();" 
            onmouseover="this.style.background='#fde4e4'" 
            onmouseout="this.style.background='transparent'">
                <i class="fas fa-arrow-rotate-left" style="color: #dc2626; font-size: 1rem;"></i>
                <span>Pasar a Revisar</span>
            </button>
            `;
        }
        
        // Agregar botón de envío solo si está en estado Pendiente
        if (estado === 'Pendiente' || estado === 'PENDIENTE_INSUMOS' || estado === 'Pendiente_Insumos') {
            html += `
            <button style="
                width: 100%;
                text-align: left;
                padding: 0.875rem 1rem;
                border: none;
                background: transparent;
                cursor: pointer;
                color: #374151;
                font-size: 0.875rem;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                gap: 0.75rem;
                font-weight: 500;
            " onclick="cambiarEstadoRecibo('${reciboId}', '${consecutivo}'); cerrarDropdownAcciones();" 
            onmouseover="this.style.background='#dbeafe'" 
            onmouseout="this.style.background='transparent'">
                <i class="fas fa-paper-plane" style="color: #3b82f6; font-size: 1rem;"></i>
                <span>Enviar a producción</span>
            </button>
            `;
        }
        
        dropdown.innerHTML = html;
        container.appendChild(dropdown);
    }

    /**
     * Cerrar todos los dropdowns de acciones
     */
    function cerrarDropdownAcciones() {
        document.querySelectorAll('.acciones-dropdown-fixed').forEach(menu => {
            menu.remove();
        });
        dropdownAbiertoButton = null;
    }

    /**
     * Abre el detalle del recibo
     */
    function abrirDetalleRecibo(pedidoId, prendaId, tipoRecibo) {
        // Convertir parámetros correctamente
        pedidoId = parseInt(pedidoId) || null;
        
        // Convertir la string 'null' a null real, o convertir a número si tiene valor
        if (prendaId === 'null' || prendaId === '' || !prendaId) {
            prendaId = null;
        } else {
            prendaId = parseInt(prendaId) || null;
        }
        
        // Verificar si existe la función openOrderDetailModalWithProcess
        if (typeof openOrderDetailModalWithProcess === 'function') {
            openOrderDetailModalWithProcess(pedidoId, prendaId, tipoRecibo);
        } else {
            console.error('Función openOrderDetailModalWithProcess no disponible');
        }
    }

    /**
     * Abre el modal para pasar a revisar
     */
    function abrirModalPasarRevisar(reciboId, pedidoId) {
        const modal = document.getElementById('modalPasarRevisar');
        if (!modal) {
            console.error('Modal no encontrado');
            return;
        }
        
        // Actualizar datos en el modal
        document.getElementById('reciboIdPasarRevisar').value = reciboId;
        document.getElementById('pedidoIdPasarRevisar').value = pedidoId;
        document.getElementById('formPasarRevisar').reset();
        document.getElementById('contadorPasarRevisar').textContent = '0';
        
        // Mostrar modal
        modal.style.display = 'flex';
    }

    /**
     * Cierra el modal de pasar a revisar
     */
    function cerrarModalPasarRevisar() {
        const modal = document.getElementById('modalPasarRevisar');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    /**
     * Confirma pasar a revisar
     */
    function confirmarPasarRevisar(event) {
        event.preventDefault();
        
        const reciboId = document.getElementById('reciboIdPasarRevisar').value;
        const motivo = document.getElementById('motivoPasarRevisar').value;
        
        if (!motivo.trim()) {
            alert('Por favor ingresa el motivo');
            return;
        }
        
        // Mostrar cargando
        const btnConfirmar = document.getElementById('btnConfirmarPasarRevisar');
        btnConfirmar.disabled = true;
        btnConfirmar.textContent = 'Procesando...';
        
        // Enviar petición
        fetch(`/insumos/materiales/${reciboId}/pasar-revisar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                motivo: motivo
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Recibo pasado a revisión correctamente', 'success');
                cerrarModalPasarRevisar();
                // Recargar la tabla
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showToast(data.message || 'Error al pasar a revisión', 'error');
                btnConfirmar.disabled = false;
                btnConfirmar.innerHTML = '<i class="fas fa-arrow-rotate-left"></i> Pasar a Revisar';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error al procesar la solicitud', 'error');
            btnConfirmar.disabled = false;
            btnConfirmar.innerHTML = '<i class="fas fa-arrow-rotate-left"></i> Pasar a Revisar';
        });
    }

    /**
     * Actualizar contador de caracteres
     */
    document.addEventListener('input', function(e) {
        if (e.target.id === 'motivoPasarRevisar') {
            const contador = document.getElementById('contadorPasarRevisar');
            if (contador) {
                contador.textContent = e.target.value.length;
            }
        }
    });

    /**
     * Cerrar modal al hacer clic fuera
     */
    document.addEventListener('click', function(e) {
        const modal = document.getElementById('modalPasarRevisar');
        if (modal && e.target === modal) {
            cerrarModalPasarRevisar();
        }
    });
</script>

{{-- Toast Container --}}
<div id="toastContainer" style="position: fixed; top: 24px; right: 24px; z-index: 99999; display: flex; flex-direction: column; gap: 12px; pointer-events: none;"></div>

{{-- Dropdowns Container (visible encima de todo) --}}
<div id="dropdowns-container" style="position: fixed; top: 0; left: 0; z-index: 999999; pointer-events: none;"></div>

{{-- Loading Overlay --}}
<div id="loadingOverlay" class="loading-overlay">
    <div class="loading-spinner"></div>
</div>

<div style="min-height: 100vh; background: #f9fafb; margin: 0; padding: 1.5rem; box-sizing: border-box;">
    {{-- Header Principal Blanco --}}
    <div style="background: white; border-bottom: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); width: 100%; margin: 0; box-sizing: border-box;">
        <div style="padding: 1rem 0; width: 100%;">
            {{-- Título, Descripción y Campana --}}
            <div style="margin-bottom: 1rem; padding: 0 0.5rem; display: flex; justify-content: space-between; align-items: flex-start;">
                <div style="flex: 1;">
                    <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
                        <span class="material-symbols-rounded text-4xl text-blue-600">inventory_2</span>
                        Control de Insumos del Pedido
                    </h1>
                    <p class="text-gray-600 text-sm mt-2">Gestiona y controla los insumos de tus pedidos en tiempo real</p>
                </div>
                {{-- Campana de Notificaciones INSUMOS (IDs únicos para evitar colisión con notifications-realtime.js global) --}}
                <div style="position: relative;">
                    <button id="insumosBellBtn" class="relative p-3 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Notificaciones de nuevos recibos">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        <span id="insumosBadge" class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1 -translate-y-1 bg-red-600 rounded-full" style="display: none; min-width: 20px;">0</span>
                    </button>

                    {{-- Dropdown de Notificaciones --}}
                    <div id="insumosDropdown" class="absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-lg border border-gray-200 z-50" style="display: none; max-height: 500px; overflow-y: auto;">
                        <div class="p-4 border-b border-gray-200 bg-grad gradient-to-r from-blue-50 to-blue-100">
                            <div class="flex justify-between items-center">
                                <h3 class="font-bold text-gray-900">Nuevos Recibos Aprobados</h3>
                                <button id="insumosClearBtn" class="text-sm text-gray-600 hover:text-gray-900 font-medium">Limpiar Todo</button>
                            </div>
                        </div>
                        <div id="insumosNotifList" class="divide-y divide-gray-200">
                            <div class="p-4 text-center text-gray-500">
                                <p>Sin notificaciones</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Buscador Mejorado --}}
            <form action="{{ route('insumos.materiales.index') }}" method="GET" class="flex gap-3 items-end" style="padding: 0 0.5rem;">
                <div class="flex-1 relative">
                    <div class="relative">
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ request('search') }}"
                            placeholder="Buscar por N° Recibo (1234) o Cliente (Empresa ABC)..."
                            class="w-full px-4 py-3 bg-gray-50 text-gray-800 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition shadow-sm"
                        >
                    </div>
                </div>
                <button type="submit" class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition shadow-sm flex items-center gap-2 whitespace-nowrap">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    Buscar
                </button>
                @if((request('filter_column') && request('filter_values')) || (request('filter_columns') && request('filter_values')))
                    <a href="{{ route('insumos.materiales.index') }}" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition shadow-sm flex items-center gap-2 whitespace-nowrap border border-gray-300">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                        </svg>
                        Limpiar Filtros
                    </a>
                @endif
                @if(request('search'))
                    <a href="{{ route('insumos.materiales.index') }}" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition shadow-sm flex items-center gap-2 whitespace-nowrap border border-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Limpiar Búsqueda
                    </a>
                @endif
            </form>

            {{-- Mensaje de búsqueda activa --}}
            @if(request('search'))
                <div class="mt-4 p-4 bg-blue-50 border-l-4 border-blue-500 rounded-lg">
                    <p class="text-blue-800 text-sm flex items-center gap-2">
                        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        <strong>Búsqueda activa:</strong> Mostrando <strong>{{ $ordenes->total() }}</strong> resultado(s) para "<strong>{{ request('search') }}</strong>"
                    </p>
                </div>
            @endif
        </div>
    </div>

    <div style="margin: 0; width: 100%; overflow: visible;">
        {{-- Tabla Principal de Órdenes --}}
        <div class="bg-white" style="margin: 0; border-radius: 0; box-shadow: none; width: 100%; overflow-x: auto; overflow-y: visible; padding: 0 0.5rem;">
            <div style="width: 100%; margin: 0; padding: 0;">
                <table class="w-full" style="font-size: 0.75em; width: 100%; margin: 0; padding: 0;">
                    <thead>
                        <tr class="bg-gradient-to-r from-blue-600 to-blue-700 text-white">
                            <th class="text-center py-4 px-6 font-bold whitespace-nowrap" style="min-width: 200px;">Acciones</th>
                            <th class="text-left py-4 px-6 font-bold">
                                <div class="flex items-center justify-between gap-2">
                                    <span>N° Recibo</span>
                                    <button class="filter-btn-insumos hover:bg-blue-500 p-1 rounded transition" data-column="numero_pedido" title="Filtrar por N° Recibo">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </th>
                            <th class="text-left py-4 px-6 font-bold">
                                <div class="flex items-center justify-between gap-2">
                                    <span>N° Pedido</span>
                                </div>
                            </th>
                            <th class="text-left py-4 px-6 font-bold">
                                <div class="flex items-center justify-between gap-2">
                                    <span>Cliente</span>
                                    <button class="filter-btn-insumos hover:bg-blue-500 p-1 rounded transition" data-column="cliente" title="Filtrar por Cliente">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </th>
                            <th class="text-center py-4 px-6 font-bold">
                                <div class="flex items-center justify-center gap-2">
                                    <span>Estado</span>
                                    <button class="filter-btn-insumos hover:bg-blue-500 p-1 rounded transition" data-column="estado" title="Filtrar por Estado">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </th>
                            <th class="text-center py-4 px-6 font-bold">
                                <div class="flex items-center justify-center gap-2">
                                    <span>Área</span>
                                    <button class="filter-btn-insumos hover:bg-blue-500 p-1 rounded transition" data-column="area" title="Filtrar por Área">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </th>
                            <th class="text-center py-4 px-6 font-bold">
                                <div class="flex items-center justify-center gap-2">
                                    <span>Fecha de Inicio</span>
                                    <button class="filter-btn-insumos hover:bg-blue-500 p-1 rounded transition" data-column="fecha_de_creacion_de_orden" title="Filtrar por Fecha de Inicio">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ordenes ?? [] as $orden)
                            <tr class="border-b border-gray-200 hover:bg-gray-50 transition @if(isset($orden->dias_calculados) && $orden->dias_calculados > 0)
                                @if($orden->dias_calculados >= 14) dias-mayor-15
                                @elseif($orden->dias_calculados >= 10) dias-10-15
                                @elseif($orden->dias_calculados >= 5) dias-5-9
                                @else dias-0-4 @endif
                            @endif @if(isset($orden->marcar_plooter) && $orden->marcar_plooter) row-checked @endif" 
                            data-pedido="{{ strtoupper($orden->numero_pedido ?? '') }}" 
                            data-cliente="{{ strtoupper($orden->cliente ?? '') }}" 
                            data-orden-pedido="{{ $orden->numero_pedido }}"
                            data-recibo="{{ $orden->id ?? '' }}"
                            data-material-id="{{ $orden->id ?? '' }}"
                            data-pedido-produccion-id="{{ $orden->pedido_produccion_id ?? '' }}">
                                <td class="py-4 px-6 text-center" style="min-width: 250px; overflow: visible; background: white; position: relative; z-index: 5;">
                                    <div class="flex items-center justify-center gap-3" style="display: flex !important; flex-wrap: wrap; overflow: visible;">
                                        {{-- Definir variables primero --}}
                                        @php
                                            $userRole = auth()->user()->role;
                                            $roleName = is_object($userRole) ? $userRole->name : $userRole;
                                            $isPatronista = $roleName === 'patronista';
                                            $reciboId = $orden->id;
                                            $pedidoProduccionId = $orden->pedido_produccion_id;
                                        @endphp
                                        
                                        {{-- Botón Check (marca) en purple --}}
                                        <button 
                                            class="btn-check-row btn-tooltip p-2 text-purple-600 hover:bg-purple-50 rounded transition @if(isset($orden->marcar_plooter) && $orden->marcar_plooter) checked @endif"
                                            onclick="toggleRowCheck(this, event)"
                                            data-tooltip="Marcar fila"
                                            title="Marcar fila"
                                        >
                                            <i class="fas fa-check text-lg"></i>
                                        </button>

                                        {{-- Botón Ver Recibo (visible siempre) --}}
                                        <button 
                                            class="btn-tooltip p-2 text-blue-600 hover:bg-blue-50 rounded transition"
                                            onclick="abrirDetalleRecibo('{{ $pedidoProduccionId }}', '{{ $orden->prenda_id ?? 'null' }}', '{{ $orden->tipo_recibo ?? 'COSTURA' }}')"
                                            data-tooltip="Ver recibo"
                                            title="Ver recibo"
                                        >
                                            <i class="fas fa-eye text-lg"></i>
                                        </button>

                                        {{-- Dropdown de Acciones (solo para no-patronistas) --}}
                                        @if(!$isPatronista)
                                            <button 
                                                class="btn-acciones p-2 text-gray-600 hover:bg-gray-100 rounded transition"
                                                onclick="crearDropdownAcciones(event, this)"
                                                data-pedido-produccion-id="{{ $pedidoProduccionId }}"
                                                data-prenda-id="{{ $orden->prenda_id ?? '' }}"
                                                data-recibo-id="{{ $reciboId }}"
                                                data-consecutivo="{{ $orden->consecutivo_actual }}"
                                                data-estado="{{ $orden->estado ?? '' }}"
                                                data-tipo-recibo="{{ $orden->tipo_recibo ?? 'COSTURA' }}"
                                                title="Más opciones"
                                            >
                                                <i class="fas fa-ellipsis-v text-lg"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="font-bold text-blue-600 text-lg">{{ $orden->numero_pedido ?? 'N/A' }}</span>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="font-medium text-gray-800">{{ $orden->numero_pedido_original ?? 'N/A' }}</span>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="font-medium text-gray-800">{{ $orden->cliente ?? 'N/A' }}</span>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    @php
                                        $estadoClass = '';
                                        $estadoColor = '';
                                        $estadoDisplay = '';
                                        
                                        if ($orden->estado === 'No iniciado') {
                                            $estadoClass = 'bg-gray-400 text-white';
                                            $estadoDisplay = 'No iniciado';
                                        } elseif ($orden->estado === 'En Ejecución') {
                                            $estadoClass = 'bg-blue-100 text-blue-800';
                                            $estadoDisplay = 'En Ejecución';
                                        } elseif ($orden->estado === 'Anulada') {
                                            $estadoClass = 'bg-amber-100 text-amber-800';
                                            $estadoDisplay = 'Anulada';
                                        } elseif ($orden->estado === 'PENDIENTE_INSUMOS' || $orden->estado === 'Pendiente_Insumos') {
                                            $estadoClass = 'bg-green-500 text-white';
                                            $estadoDisplay = 'Pendiente Insumos';
                                        } elseif ($orden->estado === 'DEVUELTO_ASESOR') {
                                            $estadoClass = 'bg-red-500 text-white';
                                            $estadoDisplay = 'Devuelto Asesor';
                                        } else {
                                            $estadoDisplay = str_replace('_', ' ', $orden->estado ?? 'N/A');
                                        }
                                    @endphp
                                    <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold {{ $estadoClass }}">
                                        {{ $estadoDisplay }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    @php
                                        $areaClass = '';
                                        $areaText = $orden->area ?? 'N/A';
                                        if ($orden->area === 'Corte') {
                                            $areaClass = 'bg-purple-100 text-purple-800';
                                        } elseif ($orden->area === 'Creación de Orden' || $orden->area === 'Creación de orden') {
                                            $areaClass = 'bg-green-100 text-green-800';
                                            $areaText = 'Creación de Orden';
                                        }
                                    @endphp
                                    <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold {{ $areaClass }}">
                                        {{ $areaText }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <span class="text-gray-600 text-sm">
                                        {{ $orden->fecha_de_creacion_de_orden ? \Carbon\Carbon::parse($orden->fecha_de_creacion_de_orden)->subHours(5)->format('d/m/Y') : 'N/A' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 px-6 text-center">
                                    <p class="text-xl text-gray-500">No hay órdenes disponibles</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    {{-- Paginación --}}
    @if($ordenes instanceof \Illuminate\Pagination\Paginator || $ordenes instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="table-pagination" id="tablePagination">
            <div class="pagination-info">
                <span id="paginationInfo">Mostrando {{ $ordenes->firstItem() }}-{{ $ordenes->lastItem() }} de {{ $ordenes->total() }} registros</span>
            </div>
            <div class="pagination-controls" id="paginationControls">
                @if($ordenes->hasPages())
                    <button class="pagination-btn" data-page="1" {{ $ordenes->currentPage() == 1 ? 'disabled' : '' }}>
                        <i class="fas fa-angle-double-left"></i>
                    </button>
                    <button class="pagination-btn" data-page="{{ $ordenes->currentPage() - 1 }}" {{ $ordenes->currentPage() == 1 ? 'disabled' : '' }}>
                        <i class="fas fa-angle-left"></i>
                    </button>
                    
                    @php
                        $start = max(1, $ordenes->currentPage() - 2);
                        $end = min($ordenes->lastPage(), $ordenes->currentPage() + 2);
                    @endphp
                    
                    @for($i = $start; $i <= $end; $i++)
                        <button class="pagination-btn page-number {{ $i == $ordenes->currentPage() ? 'active' : '' }}" data-page="{{ $i }}">
                            {{ $i }}
                        </button>
                    @endfor
                    
                    <button class="pagination-btn" data-page="{{ $ordenes->currentPage() + 1 }}" {{ $ordenes->currentPage() == $ordenes->lastPage() ? 'disabled' : '' }}>
                        <i class="fas fa-angle-right"></i>
                    </button>
                    <button class="pagination-btn" data-page="{{ $ordenes->lastPage() }}" {{ $ordenes->currentPage() == $ordenes->lastPage() ? 'disabled' : '' }}>
                        <i class="fas fa-angle-double-right"></i>
                    </button>
                @endif
            </div>
        </div>
    @endif
    </div>
</div>

<script>
    /**
     * Mostrar Toast Notification mejorado
     */
    function showToast(message, type = 'success', duration = 6000) {
        const toastContainer = document.getElementById('toastContainer');
        
        // Configuración de estilos por tipo
        const config = {
            success: {
                bg: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
                icon: '<svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>',
                title: 'Éxito',
                progressColor: '#6ee7b7'
            },
            error: {
                bg: 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)',
                icon: '<svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>',
                title: 'Error',
                progressColor: '#fca5a5'
            },
            warning: {
                bg: 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
                icon: '<svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86l-8.6 14.86A1 1 0 002.54 20h18.92a1 1 0 00.85-1.28l-8.6-14.86a1 1 0 00-1.72 0z"/></svg>',
                title: 'Advertencia',
                progressColor: '#fcd34d'
            },
            info: {
                bg: 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)',
                icon: '<svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/></svg>',
                title: 'Información',
                progressColor: '#93c5fd'
            }
        };
        
        const cfg = config[type] || config.success;
        
        // Crear elemento de toast
        const toast = document.createElement('div');
        toast.style.cssText = `
            background: ${cfg.bg};
            color: white;
            padding: 16px 20px 14px 16px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.18), 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            align-items: flex-start;
            gap: 12px;
            min-width: 320px;
            max-width: 420px;
            pointer-events: auto;
            position: relative;
            overflow: hidden;
            animation: toastSlideIn 0.4s cubic-bezier(0.21, 1.02, 0.73, 1) forwards;
            opacity: 0;
            transform: translateX(100%);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        `;
        
        // Convertir saltos de línea
        const formattedMessage = message.replace(/\n/g, '<br>');
        
        toast.innerHTML = `
            <div style="flex-shrink: 0; width: 36px; height: 36px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-top: 1px;">
                ${cfg.icon}
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="font-weight: 700; font-size: 14px; margin-bottom: 3px; letter-spacing: 0.2px;">${cfg.title}</div>
                <div style="font-size: 13px; line-height: 1.5; opacity: 0.95; white-space: pre-line; word-break: break-word;">${formattedMessage}</div>
            </div>
            <button onclick="this.closest('div[data-toast]').dispatchEvent(new Event('close'))" style="flex-shrink: 0; background: rgba(255,255,255,0.15); border: none; color: white; cursor: pointer; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: background 0.2s; margin-top: 1px;" onmouseenter="this.style.background='rgba(255,255,255,0.3)'" onmouseleave="this.style.background='rgba(255,255,255,0.15)'">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <div style="position: absolute; bottom: 0; left: 0; height: 3px; background: ${cfg.progressColor}; border-radius: 0 0 12px 12px; animation: toastProgress ${duration}ms linear forwards; width: 100%;"></div>
        `;
        
        toast.setAttribute('data-toast', 'true');
        
        // Función para cerrar el toast
        function closeToast() {
            toast.style.animation = 'toastSlideOut 0.35s cubic-bezier(0.33, 0, 0.67, 0) forwards';
            setTimeout(() => toast.remove(), 350);
        }
        
        toast.addEventListener('close', closeToast);
        
        toastContainer.appendChild(toast);
        
        // Auto-remover después del tiempo
        const autoClose = setTimeout(closeToast, duration);
        
        // Pausar al hover
        toast.addEventListener('mouseenter', () => {
            clearTimeout(autoClose);
            const progressBar = toast.querySelector('div[style*="toastProgress"]');
            if (progressBar) progressBar.style.animationPlayState = 'paused';
        });
        
        toast.addEventListener('mouseleave', () => {
            const remaining = 2000;
            const progressBar = toast.querySelector('div[style*="toastProgress"]');
            if (progressBar) progressBar.style.animationPlayState = 'running';
            setTimeout(closeToast, remaining);
        });
    }
    
    /**
     * Confirma la eliminación de un material y lo elimina inmediatamente
     */
    function confirmarEliminacion(checkbox, materialId) {
        // Si se deselecciona, mostrar modal de confirmación
        if (!checkbox.checked) {
            // Obtener datos del material
            const fila = checkbox.closest('tr');
            const celdas = fila.querySelectorAll('td');
            const nombreMaterial = celdas[0].textContent.trim().replace(/^[•●○◐◑\s]+/, '').trim();
            
            const inputsFecha = fila.querySelectorAll('input[type="date"]');
            const fechaPedido = inputsFecha[0]?.value || 'No especificada';
            const fechaLlegada = inputsFecha[1]?.value || 'No especificada';
            
            // Obtener el pedido del modal (es más confiable)
            const ordenPedido = document.getElementById('modalPedido').textContent;
            
            // Mostrar modal de confirmación
            Swal.fire({
                title: '¿Eliminar Material?',
                html: `<div style="text-align: left; margin: 20px 0;">
                    <p><strong>Material:</strong> ${nombreMaterial}</p>
                    <p><strong>Fecha Pedido:</strong> ${fechaPedido}</p>
                    <p><strong>Fecha Llegada:</strong> ${fechaLlegada}</p>
                    <p style="color: #ef4444; margin-top: 15px;"><strong><i class="fas fa-exclamation-triangle"></i> Se eliminará este registro y todos sus datos.</strong></p>
                </div>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    const swalContainer = document.querySelector('.swal2-container');
                    if (swalContainer) {
                        swalContainer.style.zIndex = '10020';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Eliminar inmediatamente sin guardar
                    eliminarMaterialInmediatamente(nombreMaterial, ordenPedido, fila);
                } else {
                    // Volver a seleccionar si cancela
                    checkbox.checked = true;
                }
            });
        }
    }

    /**
     * Elimina un material inmediatamente del servidor (elimina completamente)
     */
    function eliminarMaterialInmediatamente(nombreMaterial, ordenPedido, fila) {
        Swal.showLoading();
        
        fetch(`/insumos/materiales/${ordenPedido}/eliminar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ 
                nombre_material: nombreMaterial
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Eliminar la fila con animación
                fila.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => {
                    fila.remove();
                    showToast('Material eliminado correctamente', 'success');
                    Swal.hideLoading();
                    Swal.close();
                }, 300);
            } else {
                showToast('Error al eliminar: ' + data.message, 'error');
                Swal.hideLoading();
                Swal.close();
                // Volver a marcar el checkbox si falla
                const checkbox = fila.querySelector('input[type="checkbox"]');
                if (checkbox) checkbox.checked = true;
            }
        })
        .catch(error => {
            showToast('Error al eliminar el material', 'error');
            Swal.hideLoading();
            Swal.close();
            // Volver a marcar el checkbox si falla
            const checkbox = fila.querySelector('input[type="checkbox"]');
            if (checkbox) checkbox.checked = true;
        });
    }

    /**
     * Guarda los cambios enviando los datos al servidor
     */
    function guardarCambios(ordenPedido) {
        const materiales = [];
        
        // Obtener todos los checkboxes de materiales
        const checkboxes = document.querySelectorAll(`input[type="checkbox"][id^="checkbox_"]`);


        // Debug: mostrar todos los checkboxes de la página
        const todosCheckboxes = document.querySelectorAll('input[type="checkbox"]');
        todosCheckboxes.forEach((cb, i) => {
        });
        
        checkboxes.forEach((inputCheckbox, index) => {
            const fila = inputCheckbox.closest('tr');
            if (!fila) return;
            
            const celdas = fila.querySelectorAll('td');
            
            // Obtener el nombre del material del primer celda (removiendo el punto de color)
            const nombreMaterialEl = celdas[0];
            let nombreMaterial = nombreMaterialEl.textContent.trim();
            // Remover caracteres especiales del punto de color
            nombreMaterial = nombreMaterial.replace(/^[•●○◐◑\s]+/, '').trim();
            
            // Obtener los inputs de fecha de esta fila
            const inputsFecha = fila.querySelectorAll('input[type="date"]');
            const checkboxElement = fila.querySelector('input[type="checkbox"]');
            
            const fechaPedidoInput = inputsFecha[0];
            const fechaLlegadaInput = inputsFecha[1];
            
            const fechaPedido = fechaPedidoInput?.value || '';
            const fechaLlegada = fechaLlegadaInput?.value || '';
            const recibido = checkboxElement?.checked || false;
            
            // Obtener valores originales (comparar strings)
            const originalCheckbox = checkboxElement?.dataset.original === 'true';
            const originalFechaPedido = fechaPedidoInput?.dataset.original || '';
            const originalFechaLlegada = fechaLlegadaInput?.dataset.original || '';
            
            // Detectar si hay cambios (comparar valores como strings)
            const checkboxCambio = recibido !== originalCheckbox;
            const fechaPedidoCambio = (fechaPedido || null) !== (originalFechaPedido || null);
            const fechaLlegadaCambio = (fechaLlegada || null) !== (originalFechaLlegada || null);
            const hayChangios = checkboxCambio || fechaPedidoCambio || fechaLlegadaCambio;
            // Guardar si el checkbox está marcado O si hay cambios
            if (recibido || hayChangios) {
                materiales.push({
                    nombre: nombreMaterial,
                    fecha_pedido: fechaPedido || null,
                    fecha_llegada: fechaLlegada || null,
                    recibido: recibido,
                });
            }
        });
        fetch(`/insumos/materiales/${ordenPedido}/guardar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ materiales }),
        })
        .then(response => {
            // Si no es JSON válido, mostrar error
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error(`HTTP ${response.status}: ${text.substring(0, 100)}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showToast('Guardado exitoso', 'success');
            } else {
                showToast('Guardado exitoso', 'success');
            }
        })
        .catch(error => {
            let mensajeError = 'Error al guardar los cambios';
            
            // Si es un error JSON, extraer el mensaje
            if (error.message.includes('HTTP')) {
                mensajeError = error.message;
            } else if (error instanceof SyntaxError) {
                mensajeError = 'Error en el servidor (respuesta inválida)';
            }
            
            showToast(mensajeError, 'error');
        });
    }

    /**
     * Limpia todos los campos del formulario de una orden
     */
    function limpiarFormulario(ordenId) {
        const orden = document.querySelector(`[data-pedido]`).closest('.orden-item');
        const inputs = orden.querySelectorAll('input[type="date"], input[type="checkbox"]');
        
        inputs.forEach(input => {
            if (input.type === 'date') {
                input.value = '';
            } else if (input.type === 'checkbox') {
                input.checked = false;
            }
        });
        
        // Limpiar también los spans de días
        const diasSpans = orden.querySelectorAll('[id^="dias_"]');
        diasSpans.forEach(span => {
            span.textContent = '-';
            span.className = 'inline-block px-3 py-1 rounded-full text-sm font-semibold bg-gray-100 text-gray-600';
        });
    }
</script>

<style>
    input[type="date"] {
        appearance: none;
        -webkit-appearance: none;
        cursor: pointer;
    }

    input[type="date"]:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .accent-green-500:checked {
        accent-color: #22c55e;
    }

    /* Estilos del Modal de Orden */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }

    .modal-overlay.active {
        display: flex;
    }

    .order-detail-modal-container {
        background: white;
        width: 90%;
        max-width: 900px;
        max-height: 90vh;
        overflow-y: auto;
        border-radius: 24px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        padding: 30px;
    }

    .order-detail-card {
        border: 2px solid #000;
        border-radius: 10px;
        padding: 30px;
        background: white;
        position: relative;
    }

    .order-logo {
        display: block;
        margin: 0 auto 20px auto;
        width: 120px;
        height: auto;
    }

    .order-date {
        display: inline-block;
        background: black;
        border-radius: 8px;
        padding: 8px 12px;
        color: white;
        text-align: center;
        margin-bottom: 15px;
    }

    .fec-label {
        font-weight: bold;
        font-size: 12px;
        text-transform: uppercase;
    }

    .date-boxes {
        display: flex;
        gap: 4px;
        margin-top: 4px;
    }

    .date-box {
        background: white;
        color: black;
        border-radius: 4px;
        width: 45px;
        height: 28px;
        line-height: 26px;
        font-weight: bold;
        text-align: center;
        font-size: 12px;
    }

    .order-header-info {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin: 15px 0;
    }

    .order-info-field {
        font-weight: 600;
        font-size: 13px;
    }

    .order-info-field span {
        font-weight: 400;
        display: block;
        margin-top: 2px;
    }

    .receipt-title {
        text-align: center;
        font-weight: 800;
        font-size: 18px;
        text-transform: uppercase;
        margin: 20px 0;
        color: #000;
    }

    .pedido-number {
        text-align: center;
        font-weight: 800;
        font-size: 16px;
        color: #ff0000;
        margin: 10px 0;
    }

    .separator-line {
        height: 2px;
        background-color: #000;
        margin: 20px 0;
    }

    .signature-section {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-top: 30px;
    }

    .signature-field {
        font-weight: 600;
        font-size: 13px;
        flex: 1;
    }

    .vertical-separator {
        width: 2px;
        background-color: #000;
        margin: 0 20px;
        height: 60px;
    }

    .close-modal-btn {
        display: inline-block;
        margin-top: 20px;
        padding: 8px 16px;
        background: #3b82f6;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
    }

    .close-modal-btn:hover {
        background: #2563eb;
    }

    /* Toast Animations */
    @keyframes toastSlideIn {
        from {
            transform: translateX(120%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes toastSlideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(120%);
            opacity: 0;
        }
    }

    @keyframes toastProgress {
        from {
            width: 100%;
        }
        to {
            width: 0%;
        }
    }

    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
</style>

{{-- Modal para ver orden --}}
<!-- Modal de Detalle de Orden -->
<div id="modal-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 9997; display: none; pointer-events: auto;" onclick="closeModalOverlay()"></div>

<div id="order-detail-modal-wrapper" style="width: 90%; max-width: 672px; position: fixed; top: 60%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto; display: none;">
    <x-orders-components.order-detail-modal />
</div>

<!-- Modal de Seguimiento del Pedido -->
<x-orders-components.order-tracking-modal />

{{-- Modal para ver insumos --}}
<div id="insumosModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center" style="display: none; z-index: 10001; top: 0; left: 0; right: 0; bottom: 0;">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto" style="z-index: 10002;">
        <div class="sticky top-0 bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6 flex justify-between items-center" style="z-index: 10003;">
            <div>
                <h2 class="text-2xl font-bold flex items-center gap-2">
                    <i class="fas fa-box"></i>
                    Insumos de la Orden
                </h2>
                <p class="text-blue-100 text-sm">Pedido: <span id="modalPedido" class="font-bold"></span></p>
                <p class="text-blue-100 text-sm">Prenda: <span id="modalPrendaNombre" class="font-bold"></span></p>
                <input type="hidden" id="modalPrendaId" value="">
            </div>
            <button onclick="cerrarModalInsumos()" class="text-white hover:bg-blue-600 rounded-full p-2 transition">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-100 border-b-2 border-gray-300">
                            <th class="text-left py-3 px-4 font-bold text-gray-800 min-w-max">Insumo</th>
                            <th class="text-center py-3 px-3 font-bold text-gray-800">Estado</th>
                            <th class="text-center py-3 px-3 font-bold text-gray-800">Fecha Orden</th>
                            <th class="text-center py-3 px-3 font-bold text-gray-800">Fecha Pedido</th>
                            <th class="text-center py-3 px-3 font-bold text-gray-800">Fecha Pago</th>
                            <th class="text-center py-3 px-3 font-bold text-gray-800">Fecha Despacho</th>
                            <th class="text-center py-3 px-3 font-bold text-gray-800">Fecha Llegada</th>
                            <th class="text-center py-3 px-3 font-bold text-gray-800">Días Demora</th>
                            <th class="text-center py-3 px-3 font-bold text-gray-800">Observaciones</th>
                            <th class="text-center py-3 px-3 font-bold text-gray-800">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="insumosTableBody">
                        <!-- Se llena dinámicamente -->
                    </tbody>
                </table>
            </div>

            <div class="mt-6 flex gap-3 justify-between">
                <div class="flex gap-3">
                    <button 
                        onclick="agregarMaterialModal()"
                        class="px-6 py-2 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition flex items-center gap-2"
                    >
                        <i class="fas fa-plus"></i> Agregar Insumo
                    </button>
                </div>
                <div class="flex gap-3">
                    <button 
                        onclick="guardarInsumosModal()"
                        class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition flex items-center gap-2"
                    >
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                    <button 
                        onclick="cerrarModalInsumos()"
                        class="px-6 py-2 bg-gray-400 text-white font-semibold rounded-lg hover:bg-gray-500 transition flex items-center gap-2"
                    >
                        <i class="fas fa-times"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal para ver/editar observaciones --}}
<div id="observacionesModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center" style="display: none; z-index: 10001;">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4" style="z-index: 10002;">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6 flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold flex items-center gap-2">
                    <i class="fas fa-sticky-note"></i>
                    Observaciones del Insumo
                </h2>
                <p class="text-blue-100 text-sm">Material: <span id="observacionesMaterial" class="font-bold"></span></p>
            </div>
            <button onclick="cerrarModalObservaciones()" class="text-white hover:bg-blue-600 rounded-full p-2 transition">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Observaciones:</label>
                <textarea 
                    id="observacionesTexto" 
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none" 
                    rows="6"
                    placeholder="Escribe las observaciones del insumo aquí..."
                    onkeydown="if(event.ctrlKey && event.key === 'Enter') guardarObservaciones()"
                ></textarea>
                <p class="text-gray-500 text-xs mt-2">💡 Presiona <strong>Ctrl + Enter</strong> para guardar rápidamente</p>
            </div>
            <div class="flex gap-3 justify-end">
                <button 
                    onclick="guardarObservaciones()" 
                    class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition flex items-center gap-2"
                >
                    <i class="fas fa-save"></i> Guardar
                </button>
                <button 
                    onclick="cerrarModalObservaciones()" 
                    class="px-6 py-2 bg-gray-400 text-white font-semibold rounded-lg hover:bg-gray-500 transition flex items-center gap-2"
                >
                    <i class="fas fa-times"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal de Ancho y Metraje --}}
<div id="modalAnchoMetraje" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center" style="z-index: 10001;">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] flex flex-col" style="z-index: 10002;">
        <div class="bg-gradient-to-r from-blue-900 to-blue-800 text-white p-3 flex justify-between items-center shadow-lg flex-shrink-0" style="background: linear-gradient(to right, #111827, #1e3a8a) !important;">
            <div>
                <h2 class="text-lg font-bold flex items-center gap-2 drop-shadow text-white">
                    <i class="fas fa-ruler"></i>
                    Ancho y Metraje - Recibo: <span id="anchoMetrajeRecibo" class="font-bold text-white">-</span>
                </h2>
            </div>
            <button onclick="cerrarModalAnchoMetraje()" class="text-white bg-blue-700 rounded-full p-2 transition hover:bg-blue-600 flex-shrink-0">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <div class="overflow-y-auto flex-1 p-6 space-y-6">
            <!-- Indicador de carga mientras se obtienen los datos -->
            <div id="anchoMetrajeLoading" class="text-center py-6">
                <i class="fas fa-spinner fa-spin text-2xl text-blue-600"></i>
                <p class="text-sm text-gray-500 mt-2">Cargando datos...</p>
            </div>

            <!-- SELECTOR DE MODO: Normal, Por Color, Por Pieza o A Mano -->
            <div id="modoSelector" class="bg-gray-100 p-4 rounded-lg border border-gray-300 hidden">
                <p class="text-sm font-semibold text-gray-700 mb-3">¿Cómo deseas ingresar el ancho y metraje?</p>
                <div class="flex gap-4 flex-wrap">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input 
                            type="radio" 
                            name="modoAnchoMetraje" 
                            value="normal" 
                            class="modoRadio"
                            checked
                        >
                        <span class="text-gray-800 font-medium">Normal</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input 
                            type="radio" 
                            name="modoAnchoMetraje" 
                            value="color" 
                            class="modoRadio"
                        >
                        <span class="text-gray-800 font-medium">Por Color</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input 
                            type="radio" 
                            name="modoAnchoMetraje" 
                            value="pieza" 
                            class="modoRadio"
                        >
                        <span class="text-gray-800 font-medium">Por Pieza</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input 
                            type="radio" 
                            name="modoAnchoMetraje" 
                            value="mano" 
                            class="modoRadio"
                        >
                        <span class="text-gray-800 font-medium">A Mano</span>
                    </label>
                </div>
            </div>

            <!-- VISTA NORMAL: Un ancho/metraje por prenda -->
            <div id="normalView" class="space-y-4 hidden">
                <div class="bg-green-50 border-l-4 border-green-600 p-3 mb-4 hidden" id="normalDataWarning">
                    <p class="text-sm text-green-900">
                        <i class="fas fa-check-circle mr-2"></i>
                        No hay datos guardados. Ingresa los valores a continuación.
                    </p>
                </div>
                <div>
                    <label class="block text-base font-bold text-gray-800 mb-2">Ancho (m):</label>
                    <input 
                        type="number" 
                        id="anchoInput" 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent"
                        placeholder="Ingresa el ancho en metros..."
                        step="0.01"
                        min="0"
                    >
                </div>
                <div>
                    <label class="block text-base font-bold text-gray-800 mb-2">Metraje (m):</label>
                    <input 
                        type="number" 
                        id="metrajeInput" 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent"
                        placeholder="Ingresa el metraje en metros..."
                        step="0.01"
                        min="0"
                    >
                </div>
            </div>

            <!-- VISTA POR COLOR: Para prendas con múltiples colores -->
            <div id="colorView" class="space-y-4 hidden">
                <div class="bg-blue-50 border-l-4 border-blue-600 p-3 mb-4 hidden" id="colorDataWarning">
                    <p class="text-sm text-blue-900">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <strong>No hay datos disponibles</strong> para esta prenda. No se encontraron colores registrados.
                    </p>
                </div>
                <div id="colorInputsContainer" class="space-y-4">
                    <!-- Los inputs por color se generarán dinámicamente aquí -->
                </div>
            </div>

            <!-- VISTA POR PIEZA: Para prendas combinadas (talla-color) -->
            <div id="piezaView" class="space-y-4 hidden">
                <div class="bg-orange-50 border-l-4 border-orange-600 p-3 mb-4 hidden" id="piezaDataWarning">
                    <p class="text-sm text-orange-900">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <strong>No hay datos disponibles</strong> para esta prenda. No se encontraron combinaciones talla-color registradas.
                    </p>
                </div>
                <div id="piezaInputsContainer" class="space-y-4">
                    <!-- Los inputs por pieza/talla-color se generarán dinámicamente aquí -->
                </div>
            </div>

            <!-- VISTA A MANO: Ingresar texto libre para ancho y metraje -->
            <div id="manoView" class="space-y-4 hidden">
                <div class="bg-purple-50 border-l-4 border-purple-600 p-3 mb-4">
                    <p class="text-sm text-purple-900">
                        <i class="fas fa-edit mr-2"></i>
                        Ingresa el ancho y metraje en formato libre. El texto se mostrará directamente en el recibo.
                    </p>
                </div>
                <div>
                    <label class="block text-base font-bold text-gray-800 mb-2">Ancho y Metraje:</label>
                    <textarea 
                        id="manoAnchoMetrajeTextarea" 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent"
                        placeholder="Ej: Ancho: 2.10 m, Metraje: 1.50 m"
                        rows="4"
                    ></textarea>
                </div>
            </div>

            <div class="flex gap-3 justify-end border-t border-gray-200 p-6 flex-shrink-0">
                <button 
                    id="btnEliminarAnchoMetraje"
                    onclick="abrirModalConfirmacionEliminar()" 
                    class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg flex items-center gap-2 hidden"
                >
                    <i class="fas fa-trash-alt"></i> Eliminar
                </button>
                <button 
                    onclick="guardarAnchoMetraje()" 
                    class="px-6 py-2 text-white font-semibold rounded-lg flex items-center gap-2"
                    style="background: linear-gradient(to right, #111827, #1e3a8a) !important; color: white !important;"
                >
                    <i class="fas fa-save"></i> Guardar
                </button>
                <button 
                    onclick="cerrarModalAnchoMetraje()" 
                    class="px-6 py-2 bg-gray-400 text-white font-semibold rounded-lg flex items-center gap-2"
                >
                    <i class="fas fa-times"></i> Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DE CONFIRMACIÓN PARA ELIMINAR ANCHO/METRAJE -->
<div id="modalConfirmacionEliminar" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center" style="z-index: 10002;">
    <div class="bg-white rounded-lg shadow-2xl w-96">
        <div class="bg-gradient-to-r from-red-600 to-red-700 text-white p-4 rounded-t-lg flex items-center gap-3">
            <i class="fas fa-exclamation-triangle text-2xl"></i>
            <h2 class="text-lg font-bold">Eliminar Datos</h2>
        </div>
        <div class="p-6">
            <p class="text-gray-700 text-base font-semibold mb-2">¿Estás seguro?</p>
            <p class="text-gray-600 text-sm mb-6">Se eliminará todo el registro de ancho/metraje para esta prenda. Esta acción no se puede deshacer.</p>
            <div class="flex gap-3 justify-end">
                <button 
                    onclick="cerrarModalConfirmacionEliminar()" 
                    class="px-4 py-2 bg-gray-400 text-white font-semibold rounded-lg hover:bg-gray-500 flex items-center gap-2"
                >
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button 
                    onclick="confirmarEliminarAnchoMetraje()" 
                    class="px-4 py-2 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 flex items-center gap-2"
                >
                    <i class="fas fa-trash-alt"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Contenedor para dropdowns dinámicos -->
<div id="dropdowns-container" style="position: fixed; top: 0; left: 0; z-index: 999999; pointer-events: none;"></div>

{{-- Modal de Confirmación para Enviar a Producción --}}
<div id="modalConfirmarProduccion" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center" style="display: none; z-index: 10001; top: 0; left: 0; right: 0; bottom: 0;">
    <div class="bg-white rounded-lg shadow-2xl" style="width: 380px; z-index: 10002;">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4 rounded-t-lg flex items-center gap-3">
            <i class="fas fa-industry text-2xl"></i>
            <h2 class="text-base font-bold">Aprobar Recibo</h2>
        </div>

        <div class="p-5">
            <p class="text-gray-700 mb-2 text-sm font-semibold">Recibo N°:</p>
            <p class="text-2xl font-bold text-blue-600 mb-4" id="numeroPedidoConfirm"></p>
            
            <p class="text-gray-600 text-sm leading-relaxed mb-6">
                ¿Aprobar este recibo para enviar a producción? Solo se aprobará este recibo individual.
            </p>
            
            <div class="flex gap-3 justify-end">
                <button 
                    onclick="cerrarModalConfirmarProduccion()"
                    class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 font-semibold rounded hover:bg-gray-300 transition text-sm"
                >
                    Cancelar
                </button>
                <button 
                    id="btnAprobarProduccion"
                    onclick="confirmarEnvioProduccion()"
                    class="flex-1 px-4 py-2 bg-blue-600 text-white font-semibold rounded hover:bg-blue-700 transition text-sm"
                >
                    Aprobar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    /**
     * Alias para cerrar el modal - compatible con asesores
     */

    /**
     * Abre el modal de Ancho y Metraje para una prenda específica
     * Detecta si es prenda combinada (múltiples colores) o normal
     * El usuario puede elegir guardar normal o por color
     */
    function abrirModalAnchoMetraje(pedido, prendaId) {
        const modal = document.getElementById('modalAnchoMetraje');
        modal.style.display = 'flex';
        
        // Obtener el número de recibo
        fetch(`/insumos/materiales/${pedido}/obtener-recibo-prenda/${prendaId}`)
            .then(r => r.json())
            .then(data => {
                if (data.success && data.recibo) {
                    document.getElementById('anchoMetrajeRecibo').textContent = data.recibo;
                } else {
                    document.getElementById('anchoMetrajeRecibo').textContent = '-';
                }
            })
            .catch(error => {
                console.error('Error al obtener recibo:', error);
                document.getElementById('anchoMetrajeRecibo').textContent = '-';
            });
        
        // Guardar pedido y prenda en el modal para usarlos después
        modal.dataset.pedido = pedido;
        modal.dataset.prendaId = prendaId;

        // Limpiar inputs
        document.getElementById('anchoInput').value = '';
        document.getElementById('metrajeInput').value = '';
        document.getElementById('colorInputsContainer').innerHTML = '';
        document.getElementById('piezaInputsContainer').innerHTML = '';
        
        // Resetear selector de modo a normal
        document.querySelector('input[name="modoAnchoMetraje"][value="normal"]').checked = true;
        
        // Ocultar todo y mostrar cargando
        document.getElementById('modoSelector').classList.add('hidden');
        document.getElementById('normalView').classList.add('hidden');
        document.getElementById('colorView').classList.add('hidden');
        document.getElementById('piezaView').classList.add('hidden');
        document.getElementById('anchoMetrajeLoading').classList.remove('hidden');

        console.log('[abrirModalAnchoMetraje] Abriendo modal para pedido:', pedido, 'prenda:', prendaId);

        if (prendaId) {
            // Cargar colores y datos para rellenar los inputs según el modo seleccionado
            Promise.all([
                fetch(`/insumos/materiales/${pedido}/obtener-colores-prenda/${prendaId}`).then(r => r.json()),
                fetch(`/insumos/materiales/${pedido}/obtener-ancho-metraje-prenda/${prendaId}`).then(r => r.json())
            ])
            .then(([coloresData, datosData]) => {
                console.log('[abrirModalAnchoMetraje] Datos cargados:', { coloresData, datosData });
                
                const modoSelector = document.getElementById('modoSelector');
                const radioPieza = document.querySelector('input[name="modoAnchoMetraje"][value="pieza"]');
                const labelPieza = radioPieza?.closest('label');
                
                // Guardar datos para usar cuando el usuario cambie de modo
                modal.coloresData = coloresData;
                modal.datosData = datosData;
                
                // Guardar tipo_modo ya guardado en BD (si existe)
                const tipoModoGuardado = datosData.tipo_modo || null;
                modal.tipoModoGuardado = tipoModoGuardado;
                
                // Verificar si hay datos reales guardados (ancho o metrajes)
                const tieneDatosGuardados = (datosData.ancho !== null && datosData.ancho !== undefined) 
                    || (datosData.data && datosData.data.length > 0);
                modal.tieneDatosGuardados = tieneDatosGuardados;
                
                console.log('[abrirModalAnchoMetraje] tipo_modo guardado:', tipoModoGuardado, 'tiene datos:', tieneDatosGuardados);
                
                // DETERMINAR SI MOSTRAR OPCIÓN "POR PIEZA"
                // Por Pieza = múltiples colores/telas (modo 'piezas') SIN datos en prenda_pedido_talla_colores
                const tieneMultiplesColores = coloresData.success && 
                                             coloresData.modo === 'piezas' && 
                                             coloresData.colores && 
                                             coloresData.colores.length > 1;
                
                const esCombinada = datosData.success && 
                                   datosData.modo === 'talla-color';
                
                // SIEMPRE mostrar las 3 opciones
                console.log('[abrirModalAnchoMetraje] Mostrando siempre todas las 3 opciones de modo');
                
                // Pre-seleccionar: si hay tipo_modo guardado, usarlo; si no, inferir del tipo de prenda
                if (tipoModoGuardado && tieneDatosGuardados) {
                    console.log('[abrirModalAnchoMetraje] Usando tipo_modo guardado:', tipoModoGuardado);
                    document.querySelector(`input[name="modoAnchoMetraje"][value="${tipoModoGuardado}"]`).checked = true;
                } else if (esCombinada) {
                    // Prenda combinada (talla-color) → seleccionar "Por Pieza"
                    console.log('[abrirModalAnchoMetraje] Prenda combinada, pre-seleccionando modo pieza');
                    document.querySelector('input[name="modoAnchoMetraje"][value="pieza"]').checked = true;
                } else if (tieneMultiplesColores) {
                    // Prenda con múltiples colores → seleccionar "Por Color"
                    console.log('[abrirModalAnchoMetraje] Prenda por color, pre-seleccionando modo color');
                    document.querySelector('input[name="modoAnchoMetraje"][value="color"]').checked = true;
                } else {
                    // Prenda normal (un solo color) → seleccionar "Normal"
                    console.log('[abrirModalAnchoMetraje] Prenda normal, pre-seleccionando modo normal');
                    document.querySelector('input[name="modoAnchoMetraje"][value="normal"]').checked = true;
                }
                
                // Ocultar loading y mostrar selector
                document.getElementById('anchoMetrajeLoading').classList.add('hidden');
                modoSelector.classList.remove('hidden');
                
                // Ejecutar cambio de modo inicial
                cambiarModoAnchoMetraje();
                
                // Mostrar/ocultar botón eliminar
                mostrarBotonesAnchoMetraje();
                
                // Agregar event listeners a los radio buttons para cambiar de vista dinámicamente
                document.querySelectorAll('input[name="modoAnchoMetraje"]').forEach(radio => {
                    radio.addEventListener('change', cambiarModoAnchoMetraje);
                });
            })
            .catch(error => {
                console.error('[abrirModalAnchoMetraje] Error al cargar datos:', error);
                // Fallback: ocultar loading, mostrar selector y modo normal
                document.getElementById('anchoMetrajeLoading').classList.add('hidden');
                document.getElementById('modoSelector').classList.remove('hidden');
                document.querySelector('input[name="modoAnchoMetraje"][value="normal"]').checked = true;
                cambiarModoAnchoMetraje();
                
                // Mostrar/ocultar botón eliminar
                mostrarBotonesAnchoMetraje();
                
                // Agregar event listeners mismo en fallback
                document.querySelectorAll('input[name="modoAnchoMetraje"]').forEach(radio => {
                    radio.addEventListener('change', cambiarModoAnchoMetraje);
                });
            });
        }
    }

    /**
     * Genera inputs dinámicos para cada color (modo por color)
     * Estructura: Ancho General + Metraje por Color
     */
    function generarInputsPorColor(coloresData, datosData) {
        const container = document.getElementById('colorInputsContainer');
        container.innerHTML = '';
        
        // PRIMERO: Crear input de ANCHO GENERAL
        const anchoGeneralDiv = document.createElement('div');
        anchoGeneralDiv.className = 'bg-blue-50 border-l-4 border-blue-500 pl-4 py-3 rounded p-4';
        
        // Buscar ancho general: puede estar en datosData.ancho (top-level) o dentro de data[]
        let anchoGeneralGuardado = '';
        if (datosData.success) {
            if (datosData.ancho) {
                anchoGeneralGuardado = datosData.ancho;
            } else if (datosData.data && Array.isArray(datosData.data)) {
                const datosGeneral = datosData.data.find(d => d.ancho && !d.talla);
                if (datosGeneral) {
                    anchoGeneralGuardado = datosGeneral.ancho || '';
                }
            }
        }
        
        anchoGeneralDiv.innerHTML = `
            <h3 class="font-bold text-gray-800 mb-3 flex items-center gap-2">
                <i class="fas fa-expand-alt text-blue-600"></i>
                Ancho General (se aplica a todos los colores)
            </h3>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Ancho (m):</label>
                <input 
                    type="number" 
                    id="anchoGeneralInput"
                    class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="0.00"
                    step="0.01"
                    min="0"
                    value="${anchoGeneralGuardado}"
                >
            </div>
        `;
        container.appendChild(anchoGeneralDiv);
        
        // SEGUNDO: Crear inputs de METRAJE por color
        const metrajeDiv = document.createElement('div');
        metrajeDiv.className = 'border-t pt-4';
        
        const metrajeTitle = document.createElement('h3');
        metrajeTitle.className = 'font-bold text-gray-800 mb-3 flex items-center gap-2';
        metrajeTitle.innerHTML = '<i class="fas fa-ruler-vertical text-orange-600"></i> Metraje por Color';
        metrajeDiv.appendChild(metrajeTitle);
        
        // Crear UN input de metraje por color
        coloresData.forEach(colorData => {
            const colorNombre = colorData.nombre || colorData.color || colorData.color_nombre;
            
            // Buscar metraje guardado para este color (sin talla)
            let metrajeGuardado = '';
            if (datosData.success && datosData.data && Array.isArray(datosData.data)) {
                const datosColor = datosData.data.find(d => d.color === colorNombre && !d.talla);
                if (datosColor) {
                    metrajeGuardado = datosColor.metraje || '';
                }
            }
            
            const colorInputDiv = document.createElement('div');
            colorInputDiv.className = 'mb-4 p-3 bg-orange-50 rounded border border-orange-200';
            
            // Si hay tallas en el color, mostrarlas
            const tallasInfo = (colorData.tallas && colorData.tallas.length > 0) 
                ? ` (${colorData.tallas.join(', ')})` 
                : '';
            
            colorInputDiv.innerHTML = `
                <label class="block text-sm font-semibold text-gray-800 mb-2 flex items-center gap-2">
                    <span class="inline-block w-3 h-3 rounded-full bg-orange-400"></span>
                    ${colorNombre}${tallasInfo}
                </label>
                <input 
                    type="number" 
                    class="colorMetraje w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-orange-500"
                    placeholder="0.00"
                    step="0.01"
                    min="0"
                    data-color="${colorNombre}"
                    data-talla=""
                    value="${metrajeGuardado}"
                >
            `;
            metrajeDiv.appendChild(colorInputDiv);
        });
        
        container.appendChild(metrajeDiv);
    }

    /**
     * Genera inputs para talla-color (idéntico a por color, solo cambia el contenedor)
     * Estructura: Ancho General + Metraje por Color
     */
    function generarInputsPorTallaColor(coloresData, datosData) {
        const container = document.getElementById('piezaInputsContainer');
        container.innerHTML = '';
        
        // PRIMERO: Crear input de ANCHO GENERAL
        const anchoGeneralDiv = document.createElement('div');
        anchoGeneralDiv.className = 'bg-blue-50 border-l-4 border-blue-500 pl-4 py-3 rounded p-4';
        
        // Buscar ancho general: puede estar en datosData.ancho (top-level) o dentro de data[]
        let anchoGeneralGuardado = '';
        if (datosData.success) {
            if (datosData.ancho) {
                anchoGeneralGuardado = datosData.ancho;
            } else if (datosData.data && Array.isArray(datosData.data)) {
                const datosGeneral = datosData.data.find(d => d.ancho && !d.talla);
                if (datosGeneral) {
                    anchoGeneralGuardado = datosGeneral.ancho || '';
                }
            }
        }
        
        anchoGeneralDiv.innerHTML = `
            <h3 class="font-bold text-gray-800 mb-3 flex items-center gap-2">
                <i class="fas fa-expand-alt text-blue-600"></i>
                Ancho General (se aplica a todos los colores)
            </h3>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Ancho (m):</label>
                <input 
                    type="number" 
                    id="anchoGeneralPiezaInput"
                    class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="0.00"
                    step="0.01"
                    min="0"
                    value="${anchoGeneralGuardado}"
                >
            </div>
        `;
        container.appendChild(anchoGeneralDiv);
        
        // SEGUNDO: Crear inputs de METRAJE por color
        const metrajeDiv = document.createElement('div');
        metrajeDiv.className = 'border-t pt-4';
        
        const metrajeTitle = document.createElement('h3');
        metrajeTitle.className = 'font-bold text-gray-800 mb-3 flex items-center gap-2';
        metrajeTitle.innerHTML = '<i class="fas fa-ruler-vertical text-orange-600"></i> Metraje por Color';
        metrajeDiv.appendChild(metrajeTitle);
        
        // Crear UN input de metraje por color
        coloresData.forEach(colorData => {
            const colorNombre = colorData.nombre || colorData.color || colorData.color_nombre;
            
            // Buscar metraje guardado para este color (sin talla)
            let metrajeGuardado = '';
            if (datosData.success && datosData.data && Array.isArray(datosData.data)) {
                const datosColor = datosData.data.find(d => d.color === colorNombre && !d.talla);
                if (datosColor) {
                    metrajeGuardado = datosColor.metraje || '';
                }
            }
            
            const colorInputDiv = document.createElement('div');
            colorInputDiv.className = 'mb-4 p-3 bg-orange-50 rounded border border-orange-200';
            
            // Si hay tallas en el color, mostrarlas
            const tallasInfo = (colorData.tallas && colorData.tallas.length > 0) 
                ? ` (${colorData.tallas.join(', ')})` 
                : '';
            
            colorInputDiv.innerHTML = `
                <label class="block text-sm font-semibold text-gray-800 mb-2 flex items-center gap-2">
                    <span class="inline-block w-3 h-3 rounded-full bg-orange-400"></span>
                    ${colorNombre}${tallasInfo}
                </label>
                <input 
                    type="number" 
                    class="colorMetraje w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-orange-500"
                    placeholder="0.00"
                    step="0.01"
                    min="0"
                    data-color="${colorNombre}"
                    data-talla=""
                    value="${metrajeGuardado}"
                >
            `;
            metrajeDiv.appendChild(colorInputDiv);
        });
        
        container.appendChild(metrajeDiv);
    }

    /**
     * Cambia entre vista normal, vista por color y vista por pieza
     */
    /**
     * Genera inputs dinámicos para entrada por pieza/item
     */
    function generarInputsPorPieza(piezasData, datosData) {
        const container = document.getElementById('piezaInputsContainer');
        container.innerHTML = '';
        
        if (!piezasData || piezasData.length === 0) {
            container.innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-info-circle text-xl mb-2"></i>
                    <p class="text-sm">No hay datos de piezas disponibles para esta prenda.</p>
                </div>
            `;
            return;
        }
        
        piezasData.forEach((piezaData, index) => {
            const piezaNumero = piezaData.numero || piezaData.nombre || `Pieza ${index + 1}`;
            
            // Buscar datos guardados para esta pieza
            let metrajeGuardado = '';
            if (datosData && datosData.success && datosData.piezas) {
                const datoPieza = datosData.piezas.find(p => 
                    (p.numero && p.numero === piezaNumero) || 
                    (p.nombre && p.nombre === piezaNumero)
                );
                if (datoPieza) {
                    metrajeGuardado = datoPieza.metraje || '';
                }
            }
            
            const piezaDiv = document.createElement('div');
            piezaDiv.className = 'pieza-row border-l-4 border-purple-500 pl-4 py-3 bg-gray-50 rounded mb-3';
            piezaDiv.innerHTML = `
                <h4 class="font-bold text-gray-800 mb-3 flex items-center gap-2">
                    <span class="inline-block w-4 h-4 rounded bg-purple-500"></span>
                    ${piezaNumero}
                </h4>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Número/Item:</label>
                        <input 
                            type="text" 
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500"
                            placeholder="Número de pieza"
                            data-pieza-numero
                            value="${piezaNumero}"
                            disabled
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Metraje (m):</label>
                        <input 
                            type="number" 
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500"
                            placeholder="0.00"
                            step="0.01"
                            min="0"
                            data-pieza-metraje
                            value="${metrajeGuardado}"
                        >
                    </div>
                </div>
            `;
            container.appendChild(piezaDiv);
        });
        
        // Botón para agregar más filas (opcional)
        const btnAddPieza = document.createElement('button');
        btnAddPieza.type = 'button';
        btnAddPieza.className = 'mt-3 px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 text-sm';
        btnAddPieza.innerHTML = '<i class="fas fa-plus mr-2"></i>Agregar pieza';
        btnAddPieza.onclick = agregarFilaPieza;
        container.appendChild(btnAddPieza);
    }
    
    /**
     * Agrega una fila nueva para pieza
     */
    function agregarFilaPieza() {
        const container = document.getElementById('piezaInputsContainer');
        const filasBotones = container.querySelectorAll('button');
        const numPiezas = container.querySelectorAll('.pieza-row').length;
        
        const piezaDiv = document.createElement('div');
        piezaDiv.className = 'pieza-row border-l-4 border-purple-500 pl-4 py-3 bg-gray-50 rounded mb-3';
        piezaDiv.innerHTML = `
            <h4 class="font-bold text-gray-800 mb-3 flex items-center gap-2">
                <span class="inline-block w-4 h-4 rounded bg-purple-500"></span>
                Pieza ${numPiezas + 1}
            </h4>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Número/Item:</label>
                    <input 
                        type="text" 
                        class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="Número de pieza"
                        data-pieza-numero
                    >
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Metraje (m):</label>
                    <input 
                        type="number" 
                        class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="0.00"
                        step="0.01"
                        min="0"
                        data-pieza-metraje
                    >
                </div>
            </div>
            <button type="button" class="mt-2 px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600 text-xs" onclick="this.closest('.pieza-row').remove()">
                <i class="fas fa-trash mr-1"></i>Eliminar
            </button>
        `;
        
        // Insertar antes del botón "Agregar pieza"
        container.insertBefore(piezaDiv, filasBotones[0] || null);
    }

    function cambiarModoAnchoMetraje(e) {
        // Obtener modo: desde el evento (si existe) o del radio button seleccionado
        let modo;
        if (e && e.target) {
            modo = e.target.value;
        } else {
            // Llamada directa sin evento - obtener del radio button seleccionado
            modo = document.querySelector('input[name="modoAnchoMetraje"]:checked').value;
        }
        
        const modal = document.getElementById('modalAnchoMetraje');
        
        // VALIDAR: Si ya hay datos guardados con otro tipo_modo, advertir al usuario
        const tipoModoGuardado = modal.tipoModoGuardado;
        const tieneDatosGuardados = modal.tieneDatosGuardados;
        
        if (e && e.target && tipoModoGuardado && tieneDatosGuardados && modo !== tipoModoGuardado) {
            const nombresMode = { 'normal': 'Normal', 'color': 'Por Color', 'pieza': 'Por Pieza', 'mano': 'A Mano' };
            const modoGuardadoNombre = nombresMode[tipoModoGuardado] || tipoModoGuardado;
            const modoNuevoNombre = nombresMode[modo] || modo;
            
            // Mostrar advertencia pero permitir el cambio de modo
            showToast(
                `Cambiando de modo "${modoGuardadoNombre}" a "${modoNuevoNombre}". Al guardar, los datos anteriores se reemplazarán.`,
                'warning',
                4000
            );
        }
        
        const normalView = document.getElementById('normalView');
        const colorView = document.getElementById('colorView');
        const piezaView = document.getElementById('piezaView');
        const manoView = document.getElementById('manoView');
        
        // Ocultar todas las vistas
        normalView.classList.add('hidden');
        colorView.classList.add('hidden');
        piezaView.classList.add('hidden');
        manoView.classList.add('hidden');
        
        // Ocultar todos los mensajes de "no hay datos"
        document.getElementById('normalDataWarning')?.classList.add('hidden');
        document.getElementById('colorDataWarning')?.classList.add('hidden');
        document.getElementById('piezaDataWarning')?.classList.add('hidden');
        
        if (modo === 'normal') {
            // MODO NORMAL - Un valor para toda la prenda
            normalView.classList.remove('hidden');
            
            // Cargar datos si están disponibles en el modal
            // ancho y metraje están en el nivel superior de datosData, no dentro de data[]
            if (modal.datosData && modal.datosData.success) {
                if (modal.datosData.ancho !== null && modal.datosData.ancho !== undefined) {
                    document.getElementById('anchoInput').value = modal.datosData.ancho;
                } else {
                    document.getElementById('anchoInput').value = '';
                }
                
                if (modal.datosData.metraje !== null && modal.datosData.metraje !== undefined) {
                    document.getElementById('metrajeInput').value = modal.datosData.metraje;
                } else {
                    document.getElementById('metrajeInput').value = '';
                }
            } else {
                // Mostrar aviso si no hay datos
                document.getElementById('normalDataWarning')?.classList.remove('hidden');
                document.getElementById('anchoInput').value = '';
                document.getElementById('metrajeInput').value = '';
            }
            
        } else if (modo === 'color') {
            // MODO COLOR - Múltiples colores (mismo metraje para todas las tallas)
            colorView.classList.remove('hidden');
            
            const coloresData = modal.coloresData;
            const datosData = modal.datosData;
            
            if (coloresData && coloresData.success && coloresData.colores && coloresData.colores.length > 0) {
                // Mostrar inputs por color
                console.log('[cambiarModoAnchoMetraje] Modo color: Generando inputs por color');
                generarInputsPorColor(coloresData.colores, datosData);
            } else {
                // No hay datos de colores disponibles
                console.log('[cambiarModoAnchoMetraje] Sin datos de colores disponibles');
                document.getElementById('colorDataWarning')?.classList.remove('hidden');
                document.getElementById('colorInputsContainer').innerHTML = '';
            }
            
        } else if (modo === 'pieza') {
            // MODO PIEZA - Misma estructura que "Por Color" pero se guardará con tipo_modo='pieza'
            piezaView.classList.remove('hidden');
            
            const coloresData = modal.coloresData;
            const datosData = modal.datosData;
            
            if (coloresData && coloresData.success && (coloresData.modo === 'piezas' || coloresData.modo === 'talla-color') && coloresData.colores) {
                // Mostrar matriz talla-color (mismo HTML que por color)
                console.log('[cambiarModoAnchoMetraje] Modo pieza: Usando estructura de color/talla');
                generarInputsPorTallaColor(coloresData.colores, datosData);
            } else {
                // No hay datos disponibles
                console.log('[cambiarModoAnchoMetraje] Sin datos de talla-color disponibles');
                document.getElementById('piezaDataWarning')?.classList.remove('hidden');
                document.getElementById('piezaInputsContainer').innerHTML = '';
            }
        } else if (modo === 'mano') {
            // MODO A MANO - Texto libre
            manoView.classList.remove('hidden');
            
            // Cargar datos si están disponibles
            // contenido_mano está en el nivel superior de datosData, no dentro de data[]
            if (modal.datosData && modal.datosData.success) {
                const contenidoMano = modal.datosData.contenido_mano || '';
                document.getElementById('manoAnchoMetrajeTextarea').value = contenidoMano;
            } else {
                document.getElementById('manoAnchoMetrajeTextarea').value = '';
            }
        }
    }

    /**
     * Cierra el modal de Ancho y Metraje
     */
    function cerrarModalAnchoMetraje() {
        const modal = document.getElementById('modalAnchoMetraje');
        modal.style.display = 'none';
        
        // Limpiar los inputs
        document.getElementById('anchoInput').value = '';
        document.getElementById('metrajeInput').value = '';
        document.getElementById('colorInputsContainer').innerHTML = '';
        document.getElementById('piezaInputsContainer').innerHTML = '';
        document.getElementById('manoAnchoMetrajeTextarea').value = '';
    }

    /**
     * Mostrar/ocultar botón eliminar basado en si hay datos guardados
     */
    function mostrarBotonesAnchoMetraje() {
        const modal = document.getElementById('modalAnchoMetraje');
        const btnEliminar = document.getElementById('btnEliminarAnchoMetraje');
        
        if (modal.tieneDatosGuardados) {
            btnEliminar.classList.remove('hidden');
        } else {
            btnEliminar.classList.add('hidden');
        }
    }

    /**
     * Abre el modal de confirmación para eliminar ancho/metraje
     */
    function abrirModalConfirmacionEliminar() {
        const modalConfirmacion = document.getElementById('modalConfirmacionEliminar');
        modalConfirmacion.classList.remove('hidden');
    }

    /**
     * Cierra el modal de confirmación para eliminar ancho/metraje
     */
    function cerrarModalConfirmacionEliminar() {
        const modalConfirmacion = document.getElementById('modalConfirmacionEliminar');
        modalConfirmacion.classList.add('hidden');
    }

    /**
     * Confirma y ejecuta la eliminación de ancho/metraje
     */
    function confirmarEliminarAnchoMetraje() {
        const modal = document.getElementById('modalAnchoMetraje');
        const prendaId = modal.dataset.prendaId;
        const pedido = modal.dataset.pedido;
        
        if (!prendaId) {
            showToast('Error: No se encontró la información de la prenda', 'error');
            return;
        }

        // Llamar al backend para eliminar
        fetch(`/insumos/materiales/${pedido}/eliminar-ancho-metraje-prenda`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({
                prenda_id: prendaId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Datos eliminados correctamente', 'success');
                cerrarModalConfirmacionEliminar();
                
                // Recargar el modal (vacío)
                setTimeout(() => {
                    cerrarModalAnchoMetraje();
                    abrirModalAnchoMetraje(pedido, prendaId);
                }, 800);
            } else {
                showToast('Error al eliminar los datos: ' + (data.message || ''), 'error');
            }
        })
        .catch(error => {
            console.error('Error al eliminar ancho y metraje:', error);
            showToast('Error al eliminar los datos', 'error');
        });
    }

    /**
     * Guarda los valores de Ancho y Metraje (normal o por color)
     * Respeta la selección del usuario en el radio button
     */
    function guardarAnchoMetraje() {
        const modal = document.getElementById('modalAnchoMetraje');
        const prendaId = modal.dataset.prendaId;
        const pedido = modal.dataset.pedido;
        
        if (!prendaId) {
            showToast('Error: No se encontró la información de la prenda', 'error');
            return;
        }
        
        // Obtener modo seleccionado del radio button
        const modoSeleccionado = document.querySelector('input[name="modoAnchoMetraje"]:checked').value;
        
        if (modoSeleccionado === 'normal') {
            // GUARDAR MODO NORMAL
            const anchoVal = document.getElementById('anchoInput').value.trim();
            const metrajeVal = document.getElementById('metrajeInput').value.trim();
            const ancho = anchoVal ? parseFloat(anchoVal) : null;
            const metraje = metrajeVal ? parseFloat(metrajeVal) : null;
            
            // Validar
            if (anchoVal && (isNaN(ancho) || ancho <= 0)) {
                showToast('El ancho debe ser un número mayor a 0', 'warning');
                return;
            }
            
            if (metrajeVal && (isNaN(metraje) || metraje <= 0)) {
                showToast('El metraje debe ser un número mayor a 0', 'warning');
                return;
            }
            
            // Guardar datos globales para compatibilidad
            window.actualizarAnchoMetrajeUniversal(ancho || 0, metraje || 0, pedido);
            
            // Enviar al servidor (sin color)
            fetch(`/insumos/materiales/${pedido}/guardar-ancho-metraje-prenda`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify({
                    prenda_id: prendaId,
                    color: null,
                    tipo_modo: 'normal',
                    ancho: ancho,
                    metraje: metraje
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Ancho y metraje guardados correctamente', 'success');
                    
                    if (window.receiptManager && window.receiptManager.datosFactura) {
                        console.log('[guardarAnchoMetraje] Actualizando recibo abierto...');
                        actualizarReciboConAnchoMetraje();
                    }
                    
                    setTimeout(() => {
                        cerrarModalAnchoMetraje();
                    }, 1000);
                } else {
                    showToast('Error al guardar los datos', 'error');
                }
            })
            .catch(error => {
                console.error('Error al guardar ancho y metraje:', error);
                showToast('Error al guardar los datos', 'error');
            });
        } else if (modoSeleccionado === 'color') {
            // GUARDAR MODO POR COLOR (siempre genérico por color, aplicable a todas sus tallas)
            const promises = [];
            let erroresValidacion = false;
            
            // Si existe ancho general (para talla-color), guardarlo primero
            const anchoGeneralInput = document.getElementById('anchoGeneralInput');
            if (anchoGeneralInput) {
                const anchoGeneralVal = anchoGeneralInput.value.trim();
                const anchoGeneral = anchoGeneralVal ? parseFloat(anchoGeneralVal) : null;
                
                if (anchoGeneralVal && (isNaN(anchoGeneral) || anchoGeneral <= 0)) {
                    showToast('El ancho general debe ser un número mayor a 0', 'warning');
                    return;
                }
                
                // Guardar ancho general (sin color ni talla) si tiene valor
                if (anchoGeneral !== null) {
                    const promise = fetch(`/insumos/materiales/${pedido}/guardar-ancho-metraje-prenda`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                        },
                        body: JSON.stringify({
                            prenda_id: prendaId,
                            color: null,
                            tela: null,
                            talla: null,
                            tipo_modo: 'color',
                            ancho: anchoGeneral,
                            metraje: null
                        })
                    }).then(response => response.json());
                    
                    promises.push(promise);
                }
            }
            
            // Agrupar inputs únicos por color para metraje
            const coloresUnicos = new Map();
            
            document.querySelectorAll('#colorInputsContainer .colorMetraje').forEach(metrajeInput => {
                const colorNombre = metrajeInput.dataset.color;
                const tela = metrajeInput.dataset.tela || null;
                const talla = metrajeInput.dataset.talla || null;
                
                // Solo procesar inputs genéricos (sin talla) para metraje
                if (!coloresUnicos.has(colorNombre)) {
                    let selectorMetraje = `.colorMetraje[data-color="${colorNombre}"]`;
                    if (tela) {
                        selectorMetraje += `[data-tela="${tela}"]`;
                    }
                    selectorMetraje += '[data-talla=""]';
                    
                    const metrajeGenerico = document.querySelector(selectorMetraje);
                    
                    if (metrajeGenerico) {
                        const metrajeVal = metrajeGenerico.value.trim();
                        
                        coloresUnicos.set(colorNombre, {
                            tela: tela,
                            metraje: metrajeVal
                        });
                    }
                }
            });
            
            // Guardar metraje por color
            coloresUnicos.forEach((datos, colorNombre) => {
                if (erroresValidacion) return;
                
                const metrajeVal = datos.metraje;
                const metraje = metrajeVal ? parseFloat(metrajeVal) : null;
                
                // Validar
                if (metrajeVal && (isNaN(metraje) || metraje <= 0)) {
                    showToast(`Metraje de ${colorNombre} debe ser un número mayor a 0`, 'warning');
                    erroresValidacion = true;
                    return;
                }
                
                // Guardar como genérico (sin talla) - se aplica a todas las tallas de este color
                const promise = fetch(`/insumos/materiales/${pedido}/guardar-ancho-metraje-prenda`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    },
                    body: JSON.stringify({
                        prenda_id: prendaId,
                        color: colorNombre,
                        tela: datos.tela,
                        talla: null,
                        tipo_modo: 'color',
                        ancho: null,
                        metraje: metraje
                    })
                }).then(response => response.json());
                
                promises.push(promise);
            });
            
            if (erroresValidacion) return;
            
            Promise.all(promises)
                .then(results => {
                    if (results.every(r => r.success)) {
                        showToast('Ancho y metraje guardados correctamente', 'success');
                        
                        setTimeout(() => {
                            cerrarModalAnchoMetraje();
                        }, 1000);
                    } else {
                        showToast('Error al guardar algunos datos', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error al guardar ancho y metraje por color:', error);
                    showToast('Error al guardar los datos', 'error');
                });
        } else if (modoSeleccionado === 'pieza') {
            // GUARDAR MODO PIEZA (misma estructura que color, pero con tipo_modo='pieza')
            const promises = [];
            let erroresValidacion = false;
            
            // Si existe ancho general, guardarlo primero (usar ID específico de pieza)
            const anchoGeneralInput = document.getElementById('anchoGeneralPiezaInput');
            if (anchoGeneralInput) {
                const anchoGeneralVal = anchoGeneralInput.value.trim();
                const anchoGeneral = anchoGeneralVal ? parseFloat(anchoGeneralVal) : null;
                
                if (anchoGeneralVal && (isNaN(anchoGeneral) || anchoGeneral <= 0)) {
                    showToast('El ancho general debe ser un número mayor a 0', 'warning');
                    return;
                }
                
                // Guardar ancho general (sin color ni talla) si tiene valor
                if (anchoGeneral !== null) {
                    const promise = fetch(`/insumos/materiales/${pedido}/guardar-ancho-metraje-prenda`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                        },
                        body: JSON.stringify({
                            prenda_id: prendaId,
                            color: null,
                            tela: null,
                            talla: null,
                            tipo_modo: 'pieza',
                            ancho: anchoGeneral,
                            metraje: null
                        })
                    }).then(response => response.json());
                    
                    promises.push(promise);
                }
            }
            
            // Agrupar inputs únicos por color para metraje
            const coloresUnicos = new Map();
            
            document.querySelectorAll('#piezaInputsContainer .colorMetraje').forEach(metrajeInput => {
                const colorNombre = metrajeInput.dataset.color;
                const tela = metrajeInput.dataset.tela || null;
                const talla = metrajeInput.dataset.talla || null;
                
                // Solo procesar inputs genéricos (sin talla) para metraje
                if (!coloresUnicos.has(colorNombre)) {
                    let selectorMetraje = `.colorMetraje[data-color="${colorNombre}"]`;
                    if (tela) {
                        selectorMetraje += `[data-tela="${tela}"]`;
                    }
                    selectorMetraje += '[data-talla=""]';
                    
                    const metrajeGenerico = document.querySelector(`#piezaInputsContainer ${selectorMetraje}`);
                    
                    if (metrajeGenerico) {
                        const metrajeVal = metrajeGenerico.value.trim();
                        
                        coloresUnicos.set(colorNombre, {
                            tela: tela,
                            metraje: metrajeVal
                        });
                    }
                }
            });
            
            // Guardar metraje por color
            coloresUnicos.forEach((datos, colorNombre) => {
                if (erroresValidacion) return;
                
                const metrajeVal = datos.metraje;
                const metraje = metrajeVal ? parseFloat(metrajeVal) : null;
                
                // Validar
                if (metrajeVal && (isNaN(metraje) || metraje <= 0)) {
                    showToast(`Metraje de ${colorNombre} debe ser un número mayor a 0`, 'warning');
                    erroresValidacion = true;
                    return;
                }
                
                // Guardar como genérico (sin talla) - se aplica a todas las tallas de este color
                const promise = fetch(`/insumos/materiales/${pedido}/guardar-ancho-metraje-prenda`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    },
                    body: JSON.stringify({
                        prenda_id: prendaId,
                        color: colorNombre,
                        tela: datos.tela,
                        talla: null,
                        tipo_modo: 'pieza',
                        ancho: null,
                        metraje: metraje
                    })
                }).then(response => response.json());
                
                promises.push(promise);
            });
            
            if (erroresValidacion) return;
            
            Promise.all(promises)
                .then(results => {
                    if (results.every(r => r.success)) {
                        showToast('Datos de piezas guardados correctamente', 'success');
                        
                        setTimeout(() => {
                            cerrarModalAnchoMetraje();
                        }, 1000);
                    } else {
                        showToast('Error al guardar algunos datos', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error al guardar datos de pieza:', error);
                    showToast('Error al guardar los datos', 'error');
                });
        } else if (modoSeleccionado === 'mano') {
            // GUARDAR MODO A MANO
            const contenidoMano = document.getElementById('manoAnchoMetrajeTextarea').value.trim();
            
            if (!contenidoMano) {
                showToast('Por favor, ingresa el contenido de ancho y metraje', 'warning');
                return;
            }
            
            // Enviar al servidor
            fetch(`/insumos/materiales/${pedido}/guardar-ancho-metraje-prenda`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify({
                    prenda_id: prendaId,
                    tipo_modo: 'mano',
                    contenido_mano: contenidoMano
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Ancho y metraje guardados correctamente', 'success');
                    
                    if (window.receiptManager && window.receiptManager.datosFactura) {
                        console.log('[guardarAnchoMetraje] Actualizando recibo abierto...');
                        actualizarReciboConAnchoMetraje();
                    }
                    
                    setTimeout(() => {
                        cerrarModalAnchoMetraje();
                    }, 1000);
                } else {
                    showToast('Error al guardar los datos', 'error');
                }
            })
            .catch(error => {
                console.error('Error al guardar ancho y metraje a mano:', error);
                showToast('Error al guardar los datos', 'error');
            });
        }
    }
    
    /**
     * Actualiza el recibo abierto con los datos de ancho y metraje
     */
    function actualizarReciboConAnchoMetraje() {
        if (!window.datosAnchoMetraje || !window.receiptManager) {
            console.log('[actualizarReciboConAnchoMetraje] No hay datos de ancho/metraje o ReceiptManager');
            return;
        }
        
        const { ancho, metraje } = window.datosAnchoMetraje;
        
        // Buscar o crear el elemento para mostrar ancho y metraje
        let anchoMetrajeElement = document.getElementById('ancho-metraje-disponible');
        
        if (!anchoMetrajeElement) {
            // Crear el elemento si no existe
            anchoMetrajeElement = document.createElement('div');
            anchoMetrajeElement.id = 'ancho-metraje-disponible';
            anchoMetrajeElement.style.cssText = `
                position: absolute;
                top: 15px;
                right: 15px;
                background: rgba(0, 0, 0, 0.8);
                color: white;
                padding: 8px 12px;
                border-radius: 8px;
                font-size: 0.75rem;
                font-weight: bold;
                text-align: right;
                z-index: 10;
            `;
            
            // Insertar después del título del recibo
            const receiptTitle = document.getElementById('receipt-title');
            if (receiptTitle) {
                receiptTitle.parentNode.insertBefore(anchoMetrajeElement, receiptTitle.nextSibling);
            }
        }
        
        // Actualizar el contenido
        anchoMetrajeElement.innerHTML = `
            ANCHO DISPONIBLE: ${ancho.toFixed(2)} m<br>
            METRAJE DISPONIBLE: ${metraje.toFixed(2)} m
        `;
        
        console.log('[actualizarReciboConAnchoMetraje] Recibo actualizado con ancho y metraje');
    }

    /**
     * Abre el modal de insumos para una orden y prenda específica
     */
    function abrirModalInsumos(pedido, prendaId) {
        // Mostrar el modal
        const modal = document.getElementById('insumosModal');
        modal.style.display = 'flex';
        
        // Remover aria-hidden del contenido principal para evitar conflictos
        const mainContent = document.getElementById('mainContent');
        if (mainContent) {
            mainContent.removeAttribute('aria-hidden');
        }

        // Establecer el pedido y prenda
        document.getElementById('modalPedido').textContent = pedido;
        document.getElementById('modalPrendaId').value = prendaId || '';
        document.getElementById('modalPrendaNombre').textContent = prendaId ? `Cargando...` : 'General';

        // Construir URL con prenda_id si existe
        let url = `/insumos/api/materiales/${pedido}`;
        if (prendaId) {
            url += `?prenda_id=${prendaId}`;
        }

        // Cargar los insumos de la orden filtrados por prenda
        fetch(url)
            .then(response => response.json())
            .then(data => {
                // Actualizar nombre de prenda si viene en la respuesta
                if (data.nombre_prenda) {
                    document.getElementById('modalPrendaNombre').textContent = data.nombre_prenda;
                } else if (prendaId) {
                    document.getElementById('modalPrendaNombre').textContent = `Prenda #${prendaId}`;
                }
                llenarTablaInsumos(data.materiales || []);
            })
            .catch(error => {
                showToast('Error al cargar los insumos', 'error');
            });
    }

    /**
     * Cierra el modal de insumos
     */
    function cerrarModalInsumos() {
        const modal = document.getElementById('insumosModal');
        modal.style.display = 'none';
        
        // Restaurar aria-hidden al contenido principal
        const mainContent = document.getElementById('mainContent');
        if (mainContent) {
            mainContent.setAttribute('aria-hidden', 'false');
        }
    }

    /**
     * Llena la tabla de insumos del modal
     */
    function llenarTablaInsumos(materiales) {
        const tbody = document.getElementById('insumosTableBody');
        tbody.innerHTML = '';

        const pedido = document.getElementById('modalPedido').textContent;
        
        // Mostrar SOLO los materiales que ya están guardados (sin mostrar estándar por defecto)
        materiales.forEach((materialData, index) => {
            crearFilaMaterial(materialData.nombre_material, materialData, index, pedido, tbody);
        });
    }

    /**
     * Crea una fila de material en la tabla
     */
    function crearFilaMaterial(nombreMaterial, materialData, index, pedido, tbody) {
        const sanitizedMaterial = nombreMaterial.replace(/\s+/g, '_').toLowerCase();
        const materialId = `material_modal_${pedido}_${index}_${sanitizedMaterial}`;

        const row = document.createElement('tr');
        row.className = 'border-b border-gray-200 hover:bg-gray-50 transition';
        row.id = `row_${materialId}`;
        row.setAttribute('data-guardado', 'true');
        
        const colores = ['bg-green-500', 'bg-yellow-500', 'bg-gray-400'];
        const colorPunto = colores[index % 3];

        row.innerHTML = `
            <td class="py-3 px-4 font-medium text-gray-900 min-w-max">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full ${colorPunto}"></div>
                    <span>${nombreMaterial}</span>
                </div>
            </td>
            <td class="py-3 px-3 text-center">
                <input 
                    type="checkbox" 
                    id="checkbox_${materialId}"
                    class="w-5 h-5 cursor-pointer material-checkbox accent-green-500"
                    ${materialData.recibido ? 'checked' : ''}
                    data-original="${materialData.recibido ? 'true' : 'false'}"
                >
            </td>
            <td class="py-3 px-3 text-center">
                <input 
                    type="date" 
                    id="fecha_orden_${materialId}"
                    class="px-2 py-1 border border-gray-300 rounded text-xs font-medium text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 w-full"
                    value="${materialData.fecha_orden ? materialData.fecha_orden : ''}"
                    data-original="${materialData.fecha_orden ? materialData.fecha_orden : ''}"
                >
            </td>
            <td class="py-3 px-3 text-center">
                <input 
                    type="date" 
                    id="fecha_pedido_${materialId}"
                    class="px-2 py-1 border border-gray-300 rounded text-xs font-medium text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 w-full"
                    value="${materialData.fecha_pedido ? materialData.fecha_pedido : ''}"
                    data-original="${materialData.fecha_pedido ? materialData.fecha_pedido : ''}"
                >
            </td>
            <td class="py-3 px-3 text-center">
                <input 
                    type="date" 
                    id="fecha_pago_${materialId}"
                    class="px-2 py-1 border border-gray-300 rounded text-xs font-medium text-purple-600 focus:outline-none focus:ring-2 focus:ring-purple-500 w-full"
                    value="${materialData.fecha_pago ? materialData.fecha_pago : ''}"
                    data-original="${materialData.fecha_pago ? materialData.fecha_pago : ''}"
                >
            </td>
            <td class="py-3 px-3 text-center">
                <input 
                    type="date" 
                    id="fecha_despacho_${materialId}"
                    class="px-2 py-1 border border-gray-300 rounded text-xs font-medium text-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 w-full"
                    value="${materialData.fecha_despacho ? materialData.fecha_despacho : ''}"
                    data-original="${materialData.fecha_despacho ? materialData.fecha_despacho : ''}"
                >
            </td>
            <td class="py-3 px-3 text-center">
                <input 
                    type="date" 
                    id="fecha_llegada_${materialId}"
                    class="px-2 py-1 border border-gray-300 rounded text-xs font-medium text-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 w-full"
                    value="${materialData.fecha_llegada ? materialData.fecha_llegada : ''}"
                    data-original="${materialData.fecha_llegada ? materialData.fecha_llegada : ''}"
                >
            </td>
            <td class="py-3 px-3 text-center">
                <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-gray-100 text-gray-600 flex items-center justify-center gap-1">
                    ${materialData.dias_demora !== null && materialData.dias_demora !== undefined ? 
                        (materialData.dias_demora <= 0 ? '<i class="fas fa-check text-green-600"></i>' : 
                         materialData.dias_demora <= 5 ? '<i class="fas fa-exclamation-triangle text-yellow-600"></i>' : 
                         '<i class="fas fa-times text-red-600"></i>') + 
                        materialData.dias_demora + 'd' 
                        : '-'}
                </span>
            </td>
            <td class="py-3 px-3 text-center">
                <button 
                    onclick="abrirModalObservaciones('${materialId}', '${nombreMaterial}')"
                    class="px-2 py-1 bg-blue-100 text-blue-600 font-medium rounded hover:bg-blue-200 transition text-sm flex items-center gap-1 justify-center"
                    title="Ver/Editar observaciones"
                >
                    <i class="fas fa-eye"></i>
                </button>
                <input type="hidden" id="observaciones_${materialId}" value="${materialData.observaciones ? materialData.observaciones.replace(/"/g, '&quot;') : ''}">
            </td>
            <td class="py-3 px-3 text-center">
                <button 
                    onclick="eliminarFilaMaterial('${materialId}')"
                    class="px-2 py-1 bg-red-100 text-red-600 font-medium rounded hover:bg-red-200 transition text-sm flex items-center gap-1 justify-center"
                    title="Eliminar"
                >
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        `;

        tbody.appendChild(row);
    }

    /**
     * Mostrar modal para agregar nuevo material
     */
    function agregarMaterialModal() {
        const materialesEstandar = [
            'Tela', 
            'Reflectivo', 
            'Cierre', 
            'Cuello y puños',
            'Sesgo Relleno',
            'Sesgo Tela',
            'Sesgo en la misma Tela',
            'Hiladillo',
            'Citafalla',
            'Cordón'
        ];
        const tbody = document.getElementById('insumosTableBody');
        
        // Obtener materiales ya agregados
        const materialesAgregados = new Set();
        tbody.querySelectorAll('tr').forEach(fila => {
            const nombre = fila.querySelector('td:first-child span').textContent.trim();
            materialesAgregados.add(nombre);
        });
        
        // Filtrar materiales estándar que no estén agregados
        const materialesDisponibles = materialesEstandar.filter(m => !materialesAgregados.has(m));
        
        // Crear opciones HTML con datalist
        const opcionesHTML = `
            <div style="text-align: left;">
                <label style="display: block; margin-bottom: 10px; font-weight: bold;">Seleccionar o Escribir Insumo:</label>
                <input 
                    type="text" 
                    id="materialInput" 
                    list="materialesList"
                    placeholder="Selecciona o escribe un insumo..."
                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;"
                    autocomplete="off"
                >
                <datalist id="materialesList">
                    ${materialesDisponibles.map(m => `<option value="${m}">`).join('')}
                </datalist>
            </div>
        `;
        
        Swal.fire({
            title: 'Agregar Insumo',
            html: opcionesHTML,
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Agregar',
            cancelButtonText: 'Cancelar',
            allowOutsideClick: false,
            allowEscapeKey: false,
            customClass: {
                container: 'swal-container-top',
                popup: 'swal-popup-top'
            },
            didOpen: () => {
                const inputElement = document.getElementById('materialInput');
                if (inputElement) {
                    inputElement.focus();
                }
                
                // Asegurar z-index superior
                const swalContainer = document.querySelector('.swal2-container');
                if (swalContainer) {
                    swalContainer.style.zIndex = '10010';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const inputElement = document.getElementById('materialInput');
                const nombreMaterial = inputElement?.value.trim() || '';
                
                if (!nombreMaterial) {
                    showToast('Debes seleccionar o ingresar un material', 'warning');
                    return;
                }
                
                agregarMaterialATabla(nombreMaterial);
            }
        });
    }

    /**
     * Agregar material a la tabla
     */
    function agregarMaterialATabla(nombreMaterial) {
        const tbody = document.getElementById('insumosTableBody');
        const pedido = document.getElementById('modalPedido').textContent;
        const index = tbody.children.length;
        const sanitizedMaterial = nombreMaterial.replace(/\s+/g, '_').toLowerCase();
        const materialId = `material_modal_${pedido}_${index}_${sanitizedMaterial}`;

        const colores = ['bg-green-500', 'bg-yellow-500', 'bg-gray-400'];
        const colorPunto = colores[index % 3];

        const row = document.createElement('tr');
        row.className = 'border-b border-gray-200 hover:bg-gray-50 transition';
        row.id = `row_${materialId}`;
        
        // Marcar como fila nueva (no guardada en BD)
        row.setAttribute('data-nuevo', 'true');
        // Inicializar atributo data-observaciones vacío
        row.setAttribute('data-observaciones', '');

        row.innerHTML = `
            <td class="py-3 px-4 font-medium text-gray-900 min-w-max">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full ${colorPunto}"></div>
                    <span>${nombreMaterial}</span>
                </div>
            </td>
            <td class="py-3 px-3 text-center">
                <input 
                    type="checkbox" 
                    id="checkbox_${materialId}"
                    class="w-5 h-5 cursor-pointer material-checkbox accent-green-500"
                    data-original="false"
                >
            </td>
            <td class="py-3 px-3 text-center">
                <input 
                    type="date" 
                    id="fecha_orden_${materialId}"
                    class="px-2 py-1 border border-gray-300 rounded text-xs font-medium text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 w-full"
                    data-original=""
                >
            </td>
            <td class="py-3 px-3 text-center">
                <input 
                    type="date" 
                    id="fecha_pedido_${materialId}"
                    class="px-2 py-1 border border-gray-300 rounded text-xs font-medium text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 w-full"
                    data-original=""
                >
            </td>
            <td class="py-3 px-3 text-center">
                <input 
                    type="date" 
                    id="fecha_pago_${materialId}"
                    class="px-2 py-1 border border-gray-300 rounded text-xs font-medium text-purple-600 focus:outline-none focus:ring-2 focus:ring-purple-500 w-full"
                    data-original=""
                >
            </td>
            <td class="py-3 px-3 text-center">
                <input 
                    type="date" 
                    id="fecha_llegada_${materialId}"
                    class="px-2 py-1 border border-gray-300 rounded text-xs font-medium text-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 w-full"
                    data-original=""
                >
            </td>
            <td class="py-3 px-3 text-center">
                <input 
                    type="date" 
                    id="fecha_despacho_${materialId}"
                    class="px-2 py-1 border border-gray-300 rounded text-xs font-medium text-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 w-full"
                    data-original=""
                >
            </td>
            <td class="py-3 px-3 text-center">
                <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-gray-100 text-gray-600">-</span>
            </td>
            <td class="py-3 px-3 text-center">
                <button 
                    onclick="abrirModalObservaciones('${materialId}', '${nombreMaterial}')"
                    class="px-2 py-1 bg-blue-100 text-blue-600 font-medium rounded hover:bg-blue-200 transition text-sm flex items-center gap-1 justify-center"
                    title="Ver/Editar observaciones"
                >
                    <i class="fas fa-eye"></i>
                </button>
            </td>
            <td class="py-3 px-3 text-center">
                <button 
                    onclick="eliminarFilaMaterial('${materialId}')"
                    class="px-2 py-1 bg-red-100 text-red-600 font-medium rounded hover:bg-red-200 transition text-sm flex items-center gap-1 justify-center"
                    title="Eliminar"
                >
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        `;

        tbody.appendChild(row);
        showToast(`Material "${nombreMaterial}" agregado`, 'success');
    }

    /**
     * Elimina una fila de material del modal (elimina completamente)
     */
    function eliminarFilaMaterial(materialId) {
        const row = document.getElementById(`row_${materialId}`);
        const checkbox = document.getElementById(`checkbox_${materialId}`);
        
        if (row && checkbox) {
            // Obtener nombre del material
            const nombreMaterial = row.querySelector('td:first-child span').textContent.trim();
            const pedido = document.getElementById('modalPedido').textContent;
            
            // Verificar si la fila es nueva (aún no guardada en BD)
            const esFilaNueva = row.hasAttribute('data-nuevo') || !row.dataset.guardado;
            
            // Mostrar confirmación
            Swal.fire({
                title: '¿Eliminar Material?',
                html: `<div style="text-align: left; margin: 20px 0;">
                    <p><strong>Material:</strong> ${nombreMaterial}</p>
                    <p style="color: #ef4444; margin-top: 15px;"><strong><i class="fas fa-exclamation-triangle"></i> Se eliminará este registro${esFilaNueva ? '' : ' permanentemente'}.</strong></p>
                </div>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    const swalContainer = document.querySelector('.swal2-container');
                    if (swalContainer) {
                        swalContainer.style.zIndex = '10020';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    if (esFilaNueva) {
                        // Si es nueva, solo remover del DOM sin llamar al servidor
                        row.style.animation = 'slideOut 0.3s ease-out';
                        setTimeout(() => {
                            row.remove();
                            showToast('Material eliminado', 'success');
                        }, 300);
                    } else {
                        // Eliminar del servidor
                        fetch(`/insumos/materiales/${pedido}/eliminar`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({ 
                                nombre_material: nombreMaterial
                            }),
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Eliminar fila con animación
                                row.style.animation = 'slideOut 0.3s ease-out';
                                setTimeout(() => {
                                    row.remove();
                                    showToast('Material eliminado', 'success');
                                }, 300);
                            } else {
                                showToast('Error al eliminar: ' + data.message, 'error');
                            }
                        })
                        .catch(error => {
                            showToast('Error al eliminar el material', 'error');
                        });
                    }
                }
            });
        }
    }

    /**
     * Elimina un material (marca como eliminado)
     */
    function eliminarMaterial(materialId) {
        const checkbox = document.getElementById(`checkbox_${materialId}`);
        if (checkbox) {
            checkbox.checked = false;
            checkbox.style.opacity = '0.5';
        }
    }

    /**
     * Abre el modal de observaciones para un insumo
     */
    function abrirModalObservaciones(materialId, nombreMaterial) {
        // Mostrar el modal
        const modal = document.getElementById('observacionesModal');
        modal.style.display = 'flex';
        
        // Establecer el nombre del material
        document.getElementById('observacionesMaterial').textContent = nombreMaterial;
        
        // Guardar el materialId en un atributo data para usarlo al guardar
        modal.setAttribute('data-material-id', materialId);
        
        // Extraer el pedido del materialId
        // Formato: material_modal_${pedido}_${index}_${sanitizedMaterial}
        // O: material_${PEDIDO}_INDEX_NOMBRE
        let pedido = '';
        
        if (materialId.includes('material_modal_')) {
            // Nuevo formato: material_modal_45454_0_Tela
            const partes = materialId.split('_');
            if (partes.length >= 3) {
                pedido = partes[2]; // Índice 2 es el número de pedido
            }
        } else if (materialId.includes('material_')) {
            // Antiguo formato
            const partes = materialId.split('_');
            if (partes.length >= 2) {
                pedido = partes[1];
            }
        }
        
        // Guardar el pedido en un atributo data
        modal.setAttribute('data-pedido', pedido);
        
        // Obtener observaciones del input hidden
        const inputObservaciones = document.getElementById(`observaciones_${materialId}`);
        if (inputObservaciones) {
            document.getElementById('observacionesTexto').value = inputObservaciones.value;
        } else {
            document.getElementById('observacionesTexto').value = '';
        }
        
        // Enfocar el textarea
        document.getElementById('observacionesTexto').focus();
    }

    /**
     * Cierra el modal de observaciones
     */
    function cerrarModalObservaciones() {
        const modal = document.getElementById('observacionesModal');
        modal.style.display = 'none';
        document.getElementById('observacionesTexto').value = '';
        modal.removeAttribute('data-material-id');
    }

    /**
     * Guarda las observaciones del insumo directamente en la BD
     */
    function guardarObservaciones() {
        const modal = document.getElementById('observacionesModal');
        const materialId = modal.getAttribute('data-material-id');
        const pedido = modal.getAttribute('data-pedido');
        const observaciones = document.getElementById('observacionesTexto').value;
        
        if (!materialId) {
            showToast('Error: No se pudo identificar el material', 'error');
            return;
        }
        
        if (!pedido) {
            showToast('Error: No se pudo identificar el pedido', 'error');
            return;
        }
        
        // Guardar en el input hidden
        const inputObservaciones = document.getElementById(`observaciones_${materialId}`);
        if (inputObservaciones) {
            inputObservaciones.value = observaciones;
        }
        
        // Obtener el nombre del material
        const fila = document.getElementById(`row_${materialId}`);
        let nombreMaterial = '';
        if (fila) {
            const primeraColumna = fila.querySelector('td:first-child span');
            if (primeraColumna) {
                nombreMaterial = primeraColumna.textContent.trim();
            }
        }
        
        // Obtener el estado actual del checkbox
        const checkbox = fila ? fila.querySelector('input[type="checkbox"]') : null;
        const recibido = checkbox ? checkbox.checked : false;
        
        // Obtener todas las fechas
        const todosInputsFecha = fila ? fila.querySelectorAll('input[type="date"]') : [];
        const fechaOrden = todosInputsFecha[0]?.value || null;
        const fechaPedido = todosInputsFecha[1]?.value || null;
        const fechaPago = todosInputsFecha[2]?.value || null;
        const fechaLlegada = todosInputsFecha[3]?.value || null;
        const fechaDespacho = todosInputsFecha[4]?.value || null;
        
        // Enviar directamente al servidor
        fetch(`/insumos/materiales/${pedido}/guardar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ 
                materiales: [{
                    nombre: nombreMaterial || `Material ${materialId}`,
                    fecha_orden: fechaOrden,
                    fecha_pedido: fechaPedido,
                    fecha_pago: fechaPago,
                    fecha_llegada: fechaLlegada,
                    fecha_despacho: fechaDespacho,
                    observaciones: observaciones || null,
                    recibido: recibido,
                }]
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Observaciones guardadas correctamente', 'success');
                // Actualizar el input hidden para que se refleje en futuras aperturas
                const inputObservaciones = document.getElementById(`observaciones_${materialId}`);
                if (inputObservaciones) {
                    inputObservaciones.value = observaciones;
                }
                // Recargar los datos del modal para asegurar sincronización
                fetch(`/insumos/api/materiales/${pedido}`)
                    .then(response => response.json())
                    .then(fetchData => {
                        if (fetchData.materiales) {
                            llenarTablaInsumos(fetchData.materiales || []);
                        }
                    })
                    .catch(err => console.error('Error recargando datos:', err));
            } else {
                showToast('Error al guardar observaciones: ' + (data.message || ''), 'error');
            }
            cerrarModalObservaciones();
        })
        .catch(error => {
            showToast('Error al guardar observaciones: ' + error.message, 'error');
        });
    }

    /**
     * Guarda los cambios de insumos desde el modal
     */
    function guardarInsumosModal() {
        const pedido = document.getElementById('modalPedido').textContent;
        const prendaId = document.getElementById('modalPrendaId').value;
        const materiales = [];
        
        // Recopilar todos los materiales del modal
        const tbody = document.getElementById('insumosTableBody');
        const filas = tbody.querySelectorAll('tr');
        
        filas.forEach((fila) => {
            const celdas = fila.querySelectorAll('td');
            
            // Obtener nombre del material
            const nombreMaterialEl = celdas[0];
            let nombreMaterial = nombreMaterialEl.textContent.trim();
            nombreMaterial = nombreMaterial.replace(/^[•●○◐◑\s]+/, '').trim();
            
            // Obtener checkbox y fechas
            const checkbox = fila.querySelector('input[type="checkbox"]');
            const todosInputsFecha = fila.querySelectorAll('input[type="date"]');
            const fechaOrdenInput = todosInputsFecha[0];
            const fechaPedidoInput = todosInputsFecha[1];
            const fechaPagoInput = todosInputsFecha[2];
            const fechaLlegadaInput = todosInputsFecha[3];
            const fechaDespachoInput = todosInputsFecha[4];
            
            const recibido = checkbox?.checked || false;
            const fechaOrden = fechaOrdenInput?.value || '';
            const fechaPedido = fechaPedidoInput?.value || '';
            const fechaPago = fechaPagoInput?.value || '';
            const fechaLlegada = fechaLlegadaInput?.value || '';
            const fechaDespacho = fechaDespachoInput?.value || '';
            
            // Obtener observaciones del input hidden
            const inputObservaciones = fila.querySelector(`input[type="hidden"][id^="observaciones_"]`);
            const observaciones = inputObservaciones ? inputObservaciones.value : '';
            // Agregar si está marcado o tiene fechas
            if (recibido || fechaOrden || fechaPedido || fechaPago || fechaLlegada || fechaDespacho || observaciones) {
                materiales.push({
                    nombre: nombreMaterial,
                    fecha_orden: fechaOrden || null,
                    fecha_pedido: fechaPedido || null,
                    fecha_pago: fechaPago || null,
                    fecha_llegada: fechaLlegada || null,
                    fecha_despacho: fechaDespacho || null,
                    recibido: recibido,
                    observaciones: observaciones || null,
                });
            }
        });
        // Enviar al servidor
        fetch(`/insumos/materiales/${pedido}/guardar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ materiales, prenda_id: prendaId || null }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Materiales guardados correctamente', 'success');
            } else {
                showToast('Error al guardar', 'error');
            }
            cerrarModalInsumos();
        })
        .catch(error => {
            showToast('Error al guardar los materiales', 'error');
        });
    }
    /**
     * Cierra el modal al hacer clic fuera de él
     */
    document.getElementById('insumosModal').addEventListener('click', function(e) {
        if (e.target === this) {
            cerrarModalInsumos();
        }
    });

    /**
     * Event listener para checkboxes de materiales en el modal
     */
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('material-checkbox')) {
            const checkbox = e.target;
            const materialId = checkbox.id.replace('checkbox_', '');
            confirmarEliminacion(checkbox, materialId);
        }
        
        // Recalcular días de demora cuando cambian las fechas
        if (e.target.type === 'date') {
            const fila = e.target.closest('tr');
            if (fila) {
                actualizarDiasDemora(fila);
            }
        }
    });
    
    /**
     * Actualiza los días de demora en tiempo real
     */
    function actualizarDiasDemora(fila) {
        const todosInputsFecha = fila.querySelectorAll('input[type="date"]');
        const fechaPedido = todosInputsFecha[0]?.value;
        const fechaLlegada = todosInputsFecha[1]?.value;
        
        if (!fechaPedido || !fechaLlegada) {
            // Si falta alguna fecha, mostrar "-"
            const diasSpan = fila.querySelector('span[class*="bg-"]');
            if (diasSpan) {
                diasSpan.textContent = '-';
                diasSpan.className = 'inline-block px-3 py-1 rounded-full text-sm font-semibold bg-gray-100 text-gray-600';
            }
            return;
        }
        
        // Calcular días laborales (sin contar sábados, domingos)
        const fecha1 = new Date(fechaPedido);
        const fecha2 = new Date(fechaLlegada);
        
        let diasLaborales = 0;
        const fecha = new Date(fecha1);
        
        while (fecha <= fecha2) {
            const dia = fecha.getDay();
            // Si no es sábado (6) ni domingo (0)
            if (dia !== 0 && dia !== 6) {
                diasLaborales++;
            }
            fecha.setDate(fecha.getDate() + 1);
        }
        
        // Restar 1 porque no contamos el día de inicio
        diasLaborales = Math.max(0, diasLaborales - 1);
        
        // Actualizar el span de días de demora
        const diasSpan = fila.querySelector('span[class*="bg-"]');
        if (diasSpan) {
            let className = 'inline-block px-3 py-1 rounded-full text-sm font-semibold ';
            
            if (diasLaborales <= 0) {
                className += 'bg-green-100 text-green-800';
            } else if (diasLaborales <= 5) {
                className += 'bg-yellow-100 text-yellow-800';
            } else {
                className += 'bg-red-100 text-red-800';
            }
            
            diasSpan.textContent = diasLaborales + ' días';
            diasSpan.className = className;
        }
    }

    /**
     * Manejo de filtros en la tabla de órdenes con modal
     */
    let currentFilterColumn = null;
    let currentFilterValues = [];
    let selectedFilters = {};

    document.querySelectorAll('.filter-btn-insumos').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const column = this.getAttribute('data-column');
            currentFilterColumn = column;
            // Mostrar modal vacío (sin cargar valores aún)
            currentFilterValues = [];
            showFilterModal(column, []);
        });
    });

    function showFilterModal(column, values) {
        // Crear modal si no existe
        let modal = document.getElementById('filterModalInsumos');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'filterModalInsumos';
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
            `;
            document.body.appendChild(modal);
        }
        
        const columnNames = {
            'pedido': 'Pedido',
            'cliente': 'Cliente',
            'estado': 'Estado',
            'area': 'Área',
            'fecha': 'Fecha',
            'fecha_de_creacion_de_orden': 'Fecha de Inicio'
        };

        // Valores predefinidos para ciertos filtros
        const predefinedValues = {
            'area': ['Corte', 'Creación de Orden'],
            'estado': {
                db: ['Pendiente', 'No iniciado', 'En Ejecución', 'Entregado', 'Anulada', 'PENDIENTE_SUPERVISOR', 'PENDIENTE_INSUMOS', 'pendiente_cartera', 'RECHAZADO_CARTERA', 'DEVUELTO_A_ASESORA'],
                display: ['Pendiente', 'No iniciado', 'En Ejecución', 'Entregado', 'Anulada', 'Pendiente Supervisor', 'Pendiente Insumos', 'Pendiente Cartera', 'Rechazado Cartera', 'Devuelto a Asesora']
            }
        };

        // Usar valores predefinidos si existen, sino usar los de la tabla
        let displayValues = values;
        if (column === 'estado' && predefinedValues[column]) {
            displayValues = predefinedValues[column].display;
            values = predefinedValues[column].db;
        } else if (predefinedValues[column]) {
            displayValues = predefinedValues[column];
        }
        
        modal.innerHTML = `
            <div style="background: white; border-radius: 12px; padding: 24px; width: 90%; max-width: 500px; max-height: 80vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0; font-size: 18px; font-weight: bold;">Filtrar Insumos por: ${columnNames[column] || column}</h3>
                    <button onclick="document.getElementById('filterModalInsumos').style.display='none'" style="background: none; border: none; font-size: 24px; cursor: pointer;">×</button>
                </div>
                
                <div style="display: flex; gap: 10px; margin-bottom: 20px; align-items: center;">
                    <input type="text" id="filterSearchInsumos" placeholder="Buscar valores..." style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                    <button onclick="applyFilters()" style="padding: 10px 20px; background: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; white-space: nowrap;">✓ Aplicar</button>
                    <button onclick="selectAllFilters()" class="filter-btn-tooltip" data-tooltip="Marcar todos" style="padding: 10px 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);">
                        <i class="fas fa-check-double"></i>
                    </button>
                    <button onclick="deselectAllFilters()" class="filter-btn-tooltip" data-tooltip="Desmarcar todos" style="padding: 10px 12px; background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>
                
                <div id="filterListInsumos" style="max-height: 400px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px;">
                    <p style="text-align: center; color: #999; padding: 20px;">Escribe para buscar valores...</p>
                </div>
            </div>
        `;
        
        modal.style.display = 'flex';
        
        // Agregar tooltips a los botones
        setTimeout(() => {
            document.querySelectorAll('.filter-btn-tooltip').forEach(btn => {
                btn.addEventListener('mouseenter', function() {
                    const tooltip = this.getAttribute('data-tooltip');
                    const rect = this.getBoundingClientRect();
                    
                    // Crear tooltip
                    const tooltipEl = document.createElement('div');
                    tooltipEl.textContent = tooltip;
                    tooltipEl.style.cssText = `
                        position: fixed;
                        top: ${rect.top - 40}px;
                        left: ${rect.left + rect.width / 2}px;
                        transform: translateX(-50%);
                        background: #333;
                        color: white;
                        padding: 8px 12px;
                        border-radius: 6px;
                        font-size: 12px;
                        white-space: nowrap;
                        z-index: 10000;
                        pointer-events: none;
                    `;
                    document.body.appendChild(tooltipEl);
                    
                    // Remover tooltip al salir
                    const removeTooltip = () => {
                        tooltipEl.remove();
                        this.removeEventListener('mouseleave', removeTooltip);
                    };
                    this.addEventListener('mouseleave', removeTooltip);
                });
            });
        }, 100);
        
        // Cargar valores al abrir el modal
        let allValuesLoaded = false;
        let allValues = [];
        // Mostrar mensaje de carga
        const filterList = document.getElementById('filterListInsumos');
        filterList.innerHTML = '<p style="text-align: center; color: #999; padding: 20px;">Cargando...</p>';
        
        // Obtener valores del backend
        fetch(`/insumos/api/filtros/${column}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    allValues = data.valores;
                    allValuesLoaded = true;
                    // Renderizar primeros 15 valores
                    renderFilterValues(allValues, '', column);
                } else {
                    filterList.innerHTML = '<p style="text-align: center; color: #f00; padding: 20px;">Error al cargar valores</p>';
                }
            })
            .catch(error => {
                filterList.innerHTML = '<p style="text-align: center; color: #f00; padding: 20px;">Error al cargar valores</p>';
            });
        
        // Agregar búsqueda
        document.getElementById('filterSearchInsumos').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            
            // Si ya tenemos los valores, filtrar
            if (allValuesLoaded) {
                renderFilterValues(allValues, searchTerm, column);
            }
        });
    }
    
    function renderFilterValues(values, searchTerm, column) {
        const filterList = document.getElementById('filterListInsumos');
        const urlParams = new URLSearchParams(window.location.search);
        const filterColumns = urlParams.getAll('filter_columns[]') || [];
        const filterValuesArray = urlParams.getAll('filter_values[]') || [];
        
        // Mapeo de estados para display
        const estadoMap = {
            'PENDIENTE_SUPERVISOR': 'Pendiente Supervisor',
            'PENDIENTE_INSUMOS': 'Pendiente Insumos',
            'pendiente_cartera': 'Pendiente Cartera',
            'RECHAZADO_CARTERA': 'Rechazado Cartera',
            'DEVUELTO_A_ASESORA': 'Devuelto a Asesora'
        };
        
        // Convertir valores a display si es estado
        const displayMappedValues = values.map(val => {
            if (column === 'estado' && estadoMap[val]) {
                return { db: val, display: estadoMap[val] };
            }
            return { db: val, display: val };
        });
        
        // Filtrar valores según búsqueda
        let filteredValues = displayMappedValues.filter(valObj => {
            // Convertir a string si no lo es
            const valStr = String(valObj.display || '').trim();
            return valStr.length > 0 && valStr.toLowerCase().includes(searchTerm.toLowerCase());
        });
        
        if (filteredValues.length === 0) {
            filterList.innerHTML = '<p style="text-align: center; color: #999; padding: 20px;">No se encontraron resultados</p>';
            return;
        }
        
        // Si no hay búsqueda, mostrar solo los primeros 15
        const displayValues = searchTerm === '' ? filteredValues.slice(0, 15) : filteredValues;
        
        // Mostrar información de cuántos valores hay
        let totalText = '';
        if (searchTerm === '' && filteredValues.length > 15) {
            totalText = `<p style="text-align: center; color: #666; padding: 10px; font-size: 12px;">Mostrando ${Math.min(15, filteredValues.length)} de ${filteredValues.length} valores. Busca para ver más.</p>`;
        }
        
        // Renderizar checkboxes
        filterList.innerHTML = totalText + displayValues.map(valObj => {
            // Usar el valor de la BD para el comparador
            const dbVal = String(valObj.db || '').trim();
            const displayVal = String(valObj.display || '').trim();
            
            // Buscar si este valor está en los filtros del MISMO TIPO DE COLUMNA
            let isChecked = false;
            filterColumns.forEach((col, idx) => {
                if (col === column && filterValuesArray[idx] === dbVal) {
                    isChecked = true;
                }
            });
            
            return `
                <label style="display: flex; align-items: center; padding: 10px; cursor: pointer; border-radius: 4px; transition: background 0.2s; hover: background-color: #f3f4f6;">
                    <input type="checkbox" value="${dbVal}" class="filter-checkbox" ${isChecked ? 'checked' : ''} style="margin-right: 10px; cursor: pointer;">
                    <span style="flex: 1;">${displayVal}</span>
                </label>
            `;
        }).join('');
    }

    function selectAllFilters() {
        document.querySelectorAll('.filter-checkbox').forEach(cb => cb.checked = true);
    }

    function deselectAllFilters() {
        document.querySelectorAll('.filter-checkbox').forEach(cb => cb.checked = false);
    }

    function clearAllFilters() {
        // Mostrar todas las filas
        document.querySelectorAll('table tbody tr').forEach(row => row.style.display = '');
        document.getElementById('filterModalInsumos').style.display = 'none';
    }

    function clearAllTableFilters() {
        // Redirigir a la página sin filtros
        window.location.href = '{{ route("insumos.materiales.index") }}';
    }

    function applyFilters() {
        const selected = Array.from(document.querySelectorAll('.filter-checkbox:checked')).map(cb => cb.value);
        if (selected.length === 0) {
            // Si no hay selección, ir a la página sin filtros
            window.location.href = '{{ route("insumos.materiales.index") }}';
        } else {
            // Obtener filtros existentes de la URL
            const urlParams = new URLSearchParams(window.location.search);
            const existingFilters = {};
            
            // Recopilar filtros existentes
            const filterColumns = urlParams.getAll('filter_columns[]') || [];
            const filterValuesArray = urlParams.getAll('filter_values[]') || [];
            // Reconstruir objeto de filtros existentes
            filterColumns.forEach((col, idx) => {
                if (!existingFilters[col]) {
                    existingFilters[col] = [];
                }
                if (filterValuesArray[idx]) {
                    existingFilters[col].push(filterValuesArray[idx]);
                }
            });
            
            // Agregar o actualizar el filtro actual
            existingFilters[currentFilterColumn] = selected;
            // Construir URL con todos los filtros
            const filterParams = new URLSearchParams();
            Object.keys(existingFilters).forEach(column => {
                filterParams.append('filter_columns[]', column);
                existingFilters[column].forEach(value => {
                    filterParams.append('filter_values[]', value);
                });
            });
            
            const finalUrl = `{{ route("insumos.materiales.index") }}?${filterParams.toString()}`;
            window.location.href = finalUrl;
        }
        
        document.getElementById('filterModalInsumos').style.display = 'none';
    }

    // Cerrar modal al hacer clic fuera
    document.addEventListener('click', function(e) {
        const modal = document.getElementById('filterModalInsumos');
        if (modal && e.target === modal) {
            modal.style.display = 'none';
        }
    });
    
    /**
     * Envía un recibo individual a producción
     */
    function cambiarEstadoRecibo(reciboId, consecutivo) {
        // Guardar el ID del recibo y su consecutivo en variables globales
        window.reciboParaProduccion = reciboId;
        window.consecutivoRecibo = consecutivo;
        
        // Mostrar el modal
        document.getElementById('numeroPedidoConfirm').textContent = consecutivo;
        document.getElementById('modalConfirmarProduccion').style.display = 'flex';
    }

    /**
     * Mantener compatibilidad con llamadas anteriores
     */
    function cambiarEstadoPedido(numeroPedido, estadoActual) {
        if (estadoActual.toLowerCase() === 'pendiente' || estadoActual === 'PENDIENTE_INSUMOS') {
            window.pedidoParaProduccion = numeroPedido;
            document.getElementById('numeroPedidoConfirm').textContent = numeroPedido;
            document.getElementById('modalConfirmarProduccion').style.display = 'flex';
        } else {
            showToast('Este pedido ya ha sido enviado a producción', 'info');
        }
    }
    
    /**
     * Cierra el modal de confirmación
     */
    function cerrarModalConfirmarProduccion() {
        document.getElementById('modalConfirmarProduccion').style.display = 'none';
        window.reciboParaProduccion = null;
        window.consecutivoRecibo = null;
        window.pedidoParaProduccion = null;
        
        // Restaurar botón al cerrar modal
        restaurarBotonAprobar();
    }
    
    /**
     * Restaura el estado original del botón Aprobar
     */
    function restaurarBotonAprobar() {
        const btnAprobar = document.getElementById('btnAprobarProduccion');
        if (btnAprobar) {
            // Limpiar interval de animación
            if (btnAprobar.loadingInterval) {
                clearInterval(btnAprobar.loadingInterval);
                btnAprobar.loadingInterval = null;
            }
            
            btnAprobar.disabled = false;
            btnAprobar.innerHTML = 'Aprobar';
            btnAprobar.style.fontSize = '';
            btnAprobar.classList.add('hover:bg-blue-700');
            btnAprobar.classList.remove('opacity-75', 'cursor-not-allowed');
        }
    }
    
    /**
     * Confirma el envío a producción (recibo individual o pedido completo)
     */
    function confirmarEnvioProduccion() {
        const reciboId = window.reciboParaProduccion;
        const pedidoId = window.pedidoParaProduccion;
        
        if (!reciboId && !pedidoId) return;
        
        // Bloquear botón y mostrar "Cargando..."
        const btnAprobar = document.getElementById('btnAprobarProduccion');
        const textoOriginal = btnAprobar.innerHTML;
        btnAprobar.disabled = true;
        btnAprobar.innerHTML = 'Cargando';
        btnAprobar.style.fontSize = '14px';
        
        // Animación de puntos
        let dots = 0;
        const loadingInterval = setInterval(() => {
            dots = (dots + 1) % 4;
            btnAprobar.innerHTML = 'Cargando' + '.'.repeat(dots);
        }, 500);
        
        // Guardar interval para limpiar después
        btnAprobar.loadingInterval = loadingInterval;
        
        btnAprobar.classList.remove('hover:bg-blue-700');
        btnAprobar.classList.add('opacity-75', 'cursor-not-allowed');
        
        const proximoEstado = 'En Ejecución';
        
        // Mostrar loading overlay
        document.getElementById('loadingOverlay').classList.add('active');
        
        // Determinar URL según si es recibo individual o pedido completo
        let url;
        if (reciboId) {
            url = `/insumos/materiales/recibo/${reciboId}/cambiar-estado`;
        } else {
            url = `/insumos/materiales/${pedidoId}/cambiar-estado`;
        }
        
        // Enviar petición al servidor
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ 
                estado: proximoEstado
            }),
        })
        .then(response => response.json())
        .then(data => {
            // Ocultar loading overlay
            document.getElementById('loadingOverlay').classList.remove('active');
            
            if (data.success) {
                cerrarModalConfirmarProduccion();
                
                showToast('Recibo aprobado', 'success');
                
                // Recargar la página después de 2 segundos
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                // Restaurar botón
                restaurarBotonAprobar();
                showToast('Error al cambiar el estado: ' + (data.message || ''), 'error');
            }
        })
        .catch(error => {
            // Ocultar loading overlay
            document.getElementById('loadingOverlay').classList.remove('active');
            
            // Restaurar botón
            restaurarBotonAprobar();
            
            showToast('Error al cambiar el estado', 'error');
        });
    }
    
    console.timeEnd('RENDER_TOTAL');
    console.log(` Total de órdenes: {{ $ordenes->total() }}`);
    
    // Mostrar indicador de carga cuando se hace clic en paginación
    document.querySelectorAll('.pagination-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!this.disabled) {
                document.getElementById('loadingOverlay').classList.add('active');
            }
        });
    });
</script>

<!-- Scripts para el modal de órdenes -->
<script src="{{ asset('js/ordersjs/order-detail-modal-manager.js') }}"></script>
<script src="{{ asset('js/asesores/pedidos-detail-modal.js') }}"></script>
<!-- Image Gallery para mostrar fotos en el modal -->
<script src="{{ asset('js/orders-scripts/image-gallery-zoom.js') }}"></script>
<script src="{{ asset('js/insumos/pagination.js') }}"></script>

<!-- Script para el modal de seguimiento -->
<script src="{{ asset('js/ordersjs/tracking-modal-handler.js') }}?v={{ time() }}"></script>

<!-- Override: Abrir seguimiento directamente por prenda (como recibos-costura) -->
<script>
/**
 * Override de verSeguimiento para insumos/materiales
 * Abre el modal de seguimiento directamente con la primera prenda,
 * sin mostrar el selector de prendas (igual que recibos-costura)
 */
window.verSeguimiento = function(pedidoId, prendaIdTarget) {
    console.log('[Insumos verSeguimiento] Abriendo seguimiento directo para pedido:', pedidoId, 'prenda:', prendaIdTarget);

    if (typeof openOrderTracking !== 'function') {
        console.error('[Insumos verSeguimiento] openOrderTracking no disponible');
        alert('Sistema de seguimiento no disponible');
        return;
    }

    // Cargar datos del pedido SIN mostrar el selector de prendas (false)
    openOrderTracking(pedidoId, false).then(() => {
        console.log('[Insumos verSeguimiento] Datos inicializados, buscando prenda:', prendaIdTarget);

        // Buscar prendas en las diferentes estructuras posibles
        let prendas = null;
        if (window.currentOrderData && window.currentOrderData.prendas) {
            prendas = window.currentOrderData.prendas;
        } else if (window.currentOrderData && window.currentOrderData.data && window.currentOrderData.data.prendas) {
            prendas = window.currentOrderData.data.prendas;
        } else if (window.prendasData && window.prendasData.length > 0) {
            prendas = window.prendasData;
        }

        if (prendas && prendas.length > 0) {
            // Buscar la prenda específica por ID
            let prendaSeleccionada = null;
            if (prendaIdTarget) {
                prendaSeleccionada = prendas.find(p => 
                    String(p.id) === String(prendaIdTarget) || 
                    String(p.prenda_pedido_id) === String(prendaIdTarget)
                );
                console.log('[Insumos verSeguimiento] Prenda encontrada por ID:', prendaSeleccionada?.nombre_prenda || prendaSeleccionada?.nombre);
            }
            
            // Fallback: primera prenda
            if (!prendaSeleccionada) {
                prendaSeleccionada = prendas[0];
                console.log('[Insumos verSeguimiento] Usando primera prenda como fallback');
            }
            
            window.currentPrendaData = prendaSeleccionada;
            abrirModalSeguimientoDirectoInsumos(pedidoId, prendaIdTarget);
        } else {
            console.warn('[Insumos verSeguimiento] No hay prendas, abriendo selector como fallback');
            if (typeof showPrendasSelector === 'function') {
                showPrendasSelector();
            } else {
                alert('No hay prendas disponibles para este pedido');
            }
        }
    }).catch(error => {
        console.error('[Insumos verSeguimiento] Error:', error);
        alert('Error al cargar los datos del pedido: ' + error.message);
    });
};

/**
 * Abre el modal de seguimiento directamente sin selector (versión insumos)
 */
function abrirModalSeguimientoDirectoInsumos(pedidoId, prendaIdTarget) {
    // Abrir overlay
    const trackingOverlay = document.getElementById('trackingModalOverlay');
    if (trackingOverlay) {
        trackingOverlay.style.display = 'block';
    } else {
        console.warn('[Insumos] Modal de seguimiento no encontrado');
        alert('Modal de seguimiento no disponible');
        return;
    }

    // Abrir contenido del modal
    const trackingModal = document.getElementById('orderTrackingModal');
    if (trackingModal) {
        trackingModal.style.display = 'flex';
        trackingModal.classList.add('show');

        // Construir URL con prenda_id si está disponible
        let urlConsecutivo = `/registros/${pedidoId}/consecutivo-costura`;
        if (prendaIdTarget) {
            urlConsecutivo += `?prenda_id=${prendaIdTarget}`;
        }

        // Obtener consecutivo de costura para esta prenda específica
        fetch(urlConsecutivo)
            .then(response => {
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            })
            .then(data => {
                if (data.success && data.consecutivo) {
                    const reciboEl = document.getElementById('trackingOrderRecibo');
                    if (reciboEl) reciboEl.textContent = data.consecutivo;

                    const headerEl = document.getElementById('trackingPrendaReciboHeader');
                    if (headerEl) headerEl.textContent = `COSTURA #${data.consecutivo}`;
                } else {
                    const reciboEl = document.getElementById('trackingOrderRecibo');
                    if (reciboEl) reciboEl.textContent = '-';
                    const headerEl = document.getElementById('trackingPrendaReciboHeader');
                    if (headerEl) headerEl.textContent = 'COSTURA #?';
                }

                if (data.fecha_creacion) {
                    const fechaEl = document.getElementById('trackingOrderDate');
                    if (fechaEl) {
                        const fecha = new Date(data.fecha_creacion);
                        fechaEl.textContent = fecha.toLocaleDateString('es-ES', {
                            day: '2-digit', month: '2-digit', year: 'numeric'
                        });
                    }
                }

                // Mostrar seguimiento de la prenda seleccionada
                if (typeof showPrendaTracking === 'function' && window.currentPrendaData) {
                    showPrendaTracking(window.currentPrendaData);
                }
            })
            .catch(error => {
                console.error('[Insumos] Error al obtener consecutivo:', error);
                if (typeof showPrendaTracking === 'function' && window.currentPrendaData) {
                    showPrendaTracking(window.currentPrendaData);
                }
            });
    }
}
</script>

<!-- Scripts para Dropdown de Ver Pedido -->
<script src="{{ asset('js/asesores/pedidos-dropdown-simple.js') }}"></script>
<!-- Scripts para Vista de Factura desde Lista - Lazy Loading -->
<script src="{{ asset('js/modulos/invoice/InvoiceLazyLoader.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/asesores/invoice-from-list.js') }}"></script>
<script src="{{ asset('js/asesores/receipt-manager.js') }}"></script>
<script src="{{ asset('js/insumos/insumos-galeria.js') }}"></script>

<!-- Scripts para Recibos/Procesos -->
<script type="module" src="{{ asset('js/modulos/pedidos-recibos/loader.js') }}"></script>

<!-- Script para activar dropdowns en insumos -->
<script>
    let dropdownAbierto = {};
    
    document.addEventListener('DOMContentLoaded', function() {
        console.log('[Insumos Dropdowns] DOMContentLoaded iniciado');
        console.log('[Insumos Dropdowns] Buscando botones btn-ver-dropdown...');
        
        const botones = document.querySelectorAll('.btn-ver-dropdown');
        console.log(`[Insumos Dropdowns] Encontrados ${botones.length} botones`);
        
        // Esperar un momento para asegurar que todo esté cargado
        setTimeout(() => {
            // Cuando se haga clic en cualquier botón btn-ver-dropdown, abrir el dropdown
            document.addEventListener('click', function(e) {
                const btnVerDropdown = e.target.closest('.btn-ver-dropdown');
                if (btnVerDropdown) {
                    console.log('[Insumos Dropdowns] Clic en botón Ver');
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const menuId = btnVerDropdown.getAttribute('data-menu-id');
                    console.log(`[Insumos Dropdowns] menuId: ${menuId}`);
                    
                    // Crear el dropdown si no existe
                    let dropdown = document.getElementById(menuId);
                    console.log(`[Insumos Dropdowns] Dropdown existe: ${dropdown !== null}`);
                    
                    if (!dropdown) {
                        console.log(`[Insumos Dropdowns] Creando dropdown ${menuId}...`);
                        // Usar la función crearDropdownVer del script pedidos-dropdown-simple.js
                        if (typeof crearDropdownVer === 'function') {
                            console.log('[Insumos Dropdowns] Función crearDropdownVer disponible');
                            // Llamar a la función interna
                            dropdown = crearDropdownVer(btnVerDropdown);
                            console.log(`[Insumos Dropdowns] Dropdown creado: ${dropdown !== null}`);
                            dropdownAbierto[menuId] = false; // Inicializar estado
                        } else {
                            console.error('[Insumos Dropdowns] Función crearDropdownVer NO disponible');
                        }
                    }
                    
                    if (dropdown) {
                        console.log(`[Insumos Dropdowns] Estado actual: ${dropdownAbierto[menuId] ? 'ABIERTO' : 'CERRADO'}`);
                        
                        // Toggle del dropdown actual
                        if (!dropdownAbierto[menuId]) {
                            // Posicionar el dropdown cerca del botón
                            const rect = btnVerDropdown.getBoundingClientRect();
                            dropdown.style.top = (rect.bottom + 5) + 'px';
                            dropdown.style.left = (rect.left) + 'px';
                            dropdown.style.display = 'block';
                            dropdown.style.pointerEvents = 'auto';
                            dropdownAbierto[menuId] = true;
                            console.log('[Insumos Dropdowns] Dropdown abierto');
                        } else {
                            dropdown.style.display = 'none';
                            dropdown.style.pointerEvents = 'none';
                            dropdownAbierto[menuId] = false;
                            console.log('[Insumos Dropdowns] Dropdown cerrado');
                        }
                    }
                }
            });
            
            // Cerrar dropdown al hacer clic afuera
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.btn-ver-dropdown') && !e.target.closest('.dropdown-menu')) {
                    document.querySelectorAll('.dropdown-menu').forEach(menu => {
                        const id = menu.id;
                        if (dropdownAbierto[id]) {
                            menu.style.display = 'none';
                            menu.style.pointerEvents = 'none';
                            dropdownAbierto[id] = false;
                        }
                    });
                }
            });
        }, 100); // Pequeño retraso para asegurar que el DOM esté completamente cargado
    });
    
    /**
     * Función para abrir selector de recibos
     * Primero muestra la lista de prendas
     */
    function abrirSelectorRecibos(pedidoId) {
        console.log('[abrirSelectorRecibos] Cargando lista de prendas con pedidoId:', pedidoId);
        
        // Cargar datos del pedido
        fetch(`/pedidos-public/${pedidoId}/recibos-datos`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(datos => {
            console.log('[abrirSelectorRecibos] Datos recibidos:', datos);
            
            // Determinar dónde están los datos reales
            const datosReales = datos.data || datos;
            
            if (datosReales.prendas && datosReales.prendas.length > 0) {
                // Mostrar selector de prendas
                mostrarSelectorDePrendas(datosReales, pedidoId);
            } else {
                console.error('[abrirSelectorRecibos] No se encontraron prendas');
            }
        })
        .catch(error => {
            console.error('[abrirRecibos] Error al cargar datos:', error);
        });
    }
    
    /**
     * Muestra el modal con la lista de prendas para seleccionar
     */
    function mostrarSelectorDePrendas(datos, pedidoId) {
        console.log('[mostrarSelectorDePrendas] Mostrando lista de prendas');
        
        // Crear un modal completamente separado
        const modal = document.createElement('div');
        modal.id = 'selector-prendas-modal';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            padding: 1rem;
        `;
        
        const modalContent = document.createElement('div');
        modalContent.style.cssText = `
            background: white;
            border-radius: 12px;
            padding: 2rem;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        `;
        
        modalContent.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="margin: 0; font-size: 1.5rem; font-weight: bold; color: #1f2937;">
                    Seleccionar Prenda - Pedido ${datos.numero_pedido}
                </h2>
                <button onclick="cerrarSelectorPrendas()" style="
                    background: none;
                    border: none;
                    font-size: 1.5rem;
                    cursor: pointer;
                    color: #6b7280;
                    padding: 0.5rem;
                    border-radius: 0.375rem;
                    transition: all 0.2s ease;
                " onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='none'">
                    ×
                </button>
            </div>
            
            <div style="margin-bottom: 1.5rem; color: #6b7280;">
                Cliente: ${datos.cliente || 'N/A'} | Asesor: ${datos.asesor || datos.asesora || 'N/A'}
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                ${datos.prendas.map((prenda, index) => `
                    <button onclick="seleccionarPrendaRecibo('${pedidoId}', ${index})" style="
                        background: white;
                        border: 2px solid #e5e7eb;
                        border-radius: 8px;
                        padding: 1.5rem;
                        text-align: left;
                        cursor: pointer;
                        transition: all 0.2s ease;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    " onmouseover="this.style.background='#f9fafb'; this.style.borderColor='#3b82f6'" onmouseout="this.style.background='white'; this.style.borderColor='#e5e7eb'">
                        <div>
                            <div style="font-weight: 700; color: #1f2937; margin-bottom: 0.5rem; font-size: 1.125rem;">
                                ${prenda.nombre || 'Prenda sin nombre'}
                            </div>
                            <div style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.25rem;">
                                ${prenda.descripcion || 'Sin descripción'}
                            </div>
                            <div style="font-size: 0.75rem; color: #9ca3af;">
                                Cantidad: ${prenda.cantidad || 'N/A'}
                            </div>
                        </div>
                        <div style="background: #3b82f6; color: white; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 700;">
                            Ver Recibo
                        </div>
                    </button>
                `).join('')}
            </div>
        `;
        
        modal.appendChild(modalContent);
        document.body.appendChild(modal);
        
        // Cerrar al hacer clic fuera
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                cerrarSelectorPrendas();
            }
        });
        
        // Guardar datos para usarlos después
        window.datosSelectorPrendas = datos;
        window.pedidoIdSelector = pedidoId;
    }
    
    /**
     * Actualiza los datos básicos del modal
     */
    function actualizarDatosBasicosModal(modalContainer, datos) {
        // Asesor
        const asesoraElement = modalContainer.querySelector('#asesora-value');
        if (asesoraElement) {
            asesoraElement.textContent = datos.asesor || datos.asesora || 'N/A';
        }
        
        // Forma de pago
        const formaPagoElement = modalContainer.querySelector('#forma-pago-value');
        if (formaPagoElement) {
            formaPagoElement.textContent = datos.forma_de_pago || 'N/A';
        }
        
        // Cliente
        const clienteElement = modalContainer.querySelector('#cliente-value');
        if (clienteElement) {
            clienteElement.textContent = datos.cliente || 'N/A';
        }
        
        // Pedido
        const pedidoElement = modalContainer.querySelector('.pedido-number');
        if (pedidoElement) {
            pedidoElement.textContent = datos.numero_pedido;
        }
        
        // Fecha actual
        const now = new Date();
        const dayElement = modalContainer.querySelector('.day-box');
        const monthElement = modalContainer.querySelector('.month-box');
        const yearElement = modalContainer.querySelector('.year-box');
        
        if (dayElement) dayElement.textContent = now.getDate().toString().padStart(2, '0');
        if (monthElement) monthElement.textContent = (now.getMonth() + 1).toString().padStart(2, '0');
        if (yearElement) yearElement.textContent = now.getFullYear().toString();
    }
    
    /**
     * Selecciona una prenda y abre su recibo
     * Usa el sistema de recibos que ya funciona
     */
    function seleccionarPrendaRecibo(pedidoId, prendaIndex) {
        console.log('[seleccionarPrendaRecibo] Seleccionada prenda:', prendaIndex);
        
        // Cerrar selector
        cerrarSelectorPrendas();
        
        // Usar el sistema de recibos que ya funciona
        if (typeof verRecibosDelPedido === 'function') {
            verRecibosDelPedido(null, pedidoId, prendaIndex);
        } else {
            console.error('[seleccionarPrendaRecibo] verRecibosDelPedido no está disponible');
        }
    }
    
    /**
     * Carga el módulo PedidosRecibosModule usando el loader existente
     */
    function cargarPedidosRecibosModule(callback) {
        const script = document.createElement('script');
        script.src = '/js/modulos/pedidos-recibos/loader.js';
        script.onload = callback;
        script.onerror = () => {
            console.error('[cargarPedidosRecibosModule] Error al cargar el loader');
        };
        document.head.appendChild(script);
    }
    
    /**
     * Cierra el selector de prendas
     */
    function cerrarSelectorPrendas() {
        const modal = document.getElementById('selector-prendas-modal');
        if (modal) {
            modal.remove();
        }
        
        // Limpiar datos
        window.datosSelectorPrendas = null;
        window.pedidoIdSelector = null;
    }

    /**
     * REALTIME LISTENERS - Escuchar cambios en tiempo real desde supervisor-pedidos
     * Cuando se aprueba un pedido en supervisor-pedidos, actualiza la tabla en insumos/materiales
     */
    function initializeRealtimeListener() {
        try {
            window.waitForEcho(() => {
                const echo = window.EchoInstance;
                
                if (!echo) {
                    console.warn('[Realtime Insumos] Echo no disponible');
                    return;
                }

                console.log('[Realtime Insumos] Inicializando listeners...');

                // Almacenar notificaciones
                window.notificacionesInsumos = window.notificacionesInsumos || [];

                // Función para agregar notificación a la campana
                const addNotification = (orden) => {
                    console.log('[🔔 Campana] Nuevo recibo aprobado:', orden.numero_pedido || orden.pedido);
                    const notificacion = {
                        id: Math.random().toString(36).substr(2, 9),
                        pedido_numero: orden.numero_pedido || orden.pedido,
                        cliente: orden.cliente_nombre || 'Sin cliente',
                        timestamp: new Date().toLocaleTimeString(),
                        orden_id: orden.id
                    };

                    window.notificacionesInsumos.push(notificacion);

                    // Incrementar badge
                    const badge = document.getElementById('insumosBadge');
                    if (badge) {
                        const current = parseInt(badge.textContent || '0') + 1;
                        badge.textContent = current;
                        badge.style.display = 'inline-flex';
                    }

                    // Agregar a la lista visual
                    const notificationsList = document.getElementById('insumosNotifList');
                    if (notificationsList) {
                        // Limpiar placeholder si existe
                        if (notificationsList.children.length === 1 && 
                            (notificationsList.children[0].textContent.includes('Sin notificaciones') || 
                             notificationsList.children[0].textContent.includes('Sin recibos'))) {
                            notificationsList.innerHTML = '';
                        }

                        const notifEl = document.createElement('div');
                        notifEl.className = 'p-4 hover:bg-gray-50 transition cursor-pointer border-b border-gray-100';
                        notifEl.innerHTML = `
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <p class="font-bold text-blue-600">Recibo #${notificacion.pedido_numero}</p>
                                    <p class="text-sm text-gray-600">${notificacion.cliente}</p>
                                    <p class="text-xs text-gray-400 mt-1">${notificacion.timestamp}</p>
                                </div>
                                <button class="text-blue-600 hover:text-blue-800 font-medium text-sm px-3 py-1 rounded hover:bg-blue-50" onclick="verReciboDesdeCampana(${notificacion.orden_id})">
                                    Ver
                                </button>
                            </div>
                        `;
                        notificationsList.insertBefore(notifEl, notificationsList.firstChild);
                    }

                    // Reproducir sonido de notificación
                    playNotificationSound();

                    // Toast visual
                    showNotificationToast(notificacion);
                };

                // Función para refrescar la tabla
                const refreshMateriales = debounce(() => {
                    console.log('[Realtime Insumos] Refrescando tabla de materiales...');
                    // Recargar la página o hacer una llamada AJAX para actualizar
                    location.reload();
                }, 2000);

                // ==========================================
                // CANAL: supervisor-pedidos
                // ==========================================
                const channelSupervisor = echo.channel('supervisor-pedidos');
                
                channelSupervisor.subscribed(() => {
                    console.log('[Realtime Insumos] ✅ Suscripción exitosa al canal supervisor-pedidos');
                });
                
                channelSupervisor.error((error) => {
                    console.error('[Realtime Insumos] ❌ Error en suscripción al canal supervisor-pedidos:', error);
                });
                
                // Escuchar evento 'orden.updated' (el nombre devuelto por broadcastAs())
                channelSupervisor.listen('.orden.updated', (data) => {
                    console.log('[Realtime Insumos] 📢 Evento .orden.updated recibido:', data);
                    // SOLO mostrar notificación si el estado es PENDIENTE_INSUMOS (es decir, fue aprobado en supervisor)
                    if (data.orden && data.orden.estado === 'PENDIENTE_INSUMOS') {
                        console.log('[Realtime Insumos] ✅ Recibo aprobado a PENDIENTE_INSUMOS, mostrando notificación');
                        addNotification(data.orden);
                        refreshMateriales();
                    } else {
                        console.log('[Realtime Insumos] ⏭ Recibo actualizado pero NO está en PENDIENTE_INSUMOS, estado:', data.orden?.estado);
                    }
                });

                // Alternativa: Escuchar sin el prefijo de punto
                channelSupervisor.listen('orden.updated', (data) => {
                    console.log('[Realtime Insumos] 📢 Evento orden.updated (sin punto) recibido:', data);
                    if (data.orden && data.orden.estado === 'PENDIENTE_INSUMOS') {
                        console.log('[Realtime Insumos] ✅ Recibo aprobado a PENDIENTE_INSUMOS, mostrando notificación');
                        addNotification(data.orden);
                        refreshMateriales();
                    }
                });
                
                // Por si el evento tiene el nombre de la clase
                channelSupervisor.listen('OrdenUpdated', (data) => {
                    console.log('[Realtime Insumos] 📢 Evento OrdenUpdated recibido:', data);
                    if (data.orden && data.orden.estado === 'PENDIENTE_INSUMOS') {
                        console.log('[Realtime Insumos] ✅ Recibo aprobado a PENDIENTE_INSUMOS, mostrando notificación');
                        addNotification(data.orden);
                        refreshMateriales();
                    }
                });

                // ==========================================
                // CANAL: ordenes
                // ==========================================
                const channelOrdenes = echo.channel('ordenes');
                
                channelOrdenes.subscribed(() => {
                    console.log('[Realtime Insumos] ✅ Suscripción exitosa al canal ordenes');
                });
                
                channelOrdenes.error((error) => {
                    console.error('[Realtime Insumos] ❌ Error en suscripción al canal ordenes:', error);
                });

                // Múltiples variantes de nombres de eventos
                ['orden.updated', '.orden.updated', 'OrdenUpdated'].forEach(eventName => {
                    channelOrdenes.listen(eventName, (data) => {
                        console.log(`[Realtime Insumos] 📢 Evento '${eventName}' recibido en canal ordenes:`, data);
                        // SOLO notificar si el estado es PENDIENTE_INSUMOS
                        if (data.orden && data.orden.estado === 'PENDIENTE_INSUMOS') {
                            addNotification(data.orden);
                        }
                        refreshMateriales();
                    });
                });

                console.log('[Realtime Insumos] ✅ Sistema de tiempo real inicializado correctamente');
            });
        } catch (error) {
            console.error('[Realtime Insumos] ❌ Error inicializando listener:', error);
        }
    }

    /**
     * Setup de controles de la campana de notificaciones
     */
    function setupNotificationBellControls() {
        const bellBtn = document.getElementById('insumosBellBtn');
        const dropdown = document.getElementById('insumosDropdown');
        const clearBtn = document.getElementById('insumosClearBtn');

        // Abrir/cerrar dropdown
        if (bellBtn) {
            bellBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (dropdown) {
                    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
                }
            });
        }

        // Limpiar todas las notificaciones
        if (clearBtn) {
            clearBtn.addEventListener('click', (e) => {
                e.preventDefault();
                window.notificacionesInsumos = [];
                const badge = document.getElementById('insumosBadge');
                if (badge) {
                    badge.textContent = '0';
                    badge.style.display = 'none';
                }
                const notificationsList = document.getElementById('insumosNotifList');
                if (notificationsList) {
                    notificationsList.innerHTML = '<div class="p-4 text-center text-gray-500"><p>Sin notificaciones</p></div>';
                }
            });
        }

        // Cerrar dropdown cuando se hace click fuera
        document.addEventListener('click', (e) => {
            if (dropdown && bellBtn && !dropdown.contains(e.target) && !bellBtn.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });
    }

    /**
     * Reproduce un sonido de notificación
     */
    function playNotificationSound() {
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.value = 800;
            oscillator.type = 'sine';
            
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.5);
        } catch (e) {
            console.log('[Notificación] No se pudo reproducir sonido:', e.message);
        }
    }

    /**
     * Muestra un toast visual cuando llega una notificación
     */
    function showNotificationToast(notificacion) {
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-4 right-4 bg-green-500 text-white p-4 rounded-lg shadow-lg z-50 flex items-center gap-3 animate-pulse';
        toast.style.animation = 'slideInUp 0.5s ease-out';
        toast.innerHTML = `
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
                <p class="font-bold">Nuevo Recibo Aprobado</p>
                <p class="text-sm">Número: #${notificacion.pedido_numero} - ${notificacion.cliente}</p>
            </div>
        `;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOutDown 0.5s ease-out';
            setTimeout(() => toast.remove(), 500);
        }, 5000);
    }

    /**
     * Ve un recibo desde la notificación de campana
     */
    window.verReciboDesdeCampana = function(ordenId) {
        console.log('[Notificación] Visualizando recibo:', ordenId);
        // Buscar la fila en la tabla y hacer scroll hacia ella
        const row = document.querySelector(`tr[data-pedido-produccion-id="${ordenId}"]`);
        if (row) {
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            row.style.backgroundColor = '#fef3c7';
            setTimeout(() => {
                row.style.backgroundColor = '';
            }, 2000);
        }
        // Cerrar dropdown
        const dropdown = document.getElementById('insumosDropdown');
        if (dropdown) dropdown.style.display = 'none';
    };

    // Función debounce para evitar múltiples refrescos rápidos
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    console.log('[🔔 CAMPANA INSUMOS] Sistema iniciado');
    
    // ======= MARCAR RECIBO COMO VISTO =======
    async function marcarReciboVisto(reciboId, itemElement) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            const response = await fetch('/insumos/api/recibo/' + reciboId + '/marcar-visto', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken ? csrfToken.content : ''
                }
            });

            if (response.ok) {
                console.log('[🔔 CAMPANA INSUMOS] Recibo', reciboId, 'marcado como visto');

                // Animar y remover el item
                itemElement.style.transition = 'all 0.3s ease';
                itemElement.style.opacity = '0';
                itemElement.style.maxHeight = '0';
                itemElement.style.overflow = 'hidden';
                itemElement.style.padding = '0';
                itemElement.style.margin = '0';

                setTimeout(function() {
                    itemElement.remove();

                    // Decrementar badge
                    const badge = document.getElementById('insumosBadge');
                    if (badge) {
                        let count = parseInt(badge.textContent) || 0;
                        count = Math.max(0, count - 1);
                        badge.textContent = count;
                        badge.style.display = count > 0 ? 'inline-flex' : 'none';
                    }

                    // Si no quedan items, mostrar mensaje vacío
                    const list = document.getElementById('insumosNotifList');
                    if (list && list.querySelectorAll('[data-recibo-id]').length === 0) {
                        list.innerHTML = '<div class="p-4 text-center text-gray-500"><p>Sin recibos pendientes</p></div>';
                    }
                }, 300);
            } else {
                console.error('[🔔 CAMPANA INSUMOS] Error marcando visto:', response.status);
                alert('Error al marcar como visto');
            }
        } catch (error) {
            console.error('[🔔 CAMPANA INSUMOS] Error en marcarReciboVisto:', error);
            alert('Error al marcar como visto');
        }
    }

    // ======= CARGAR CONTEO Y LISTA INICIAL DESDE API =======
    async function cargarConteoInicial() {
        try {
            const response = await fetch('/insumos/api/contar-costura-pendiente', {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (response.ok) {
                const data = await response.json();
                const total = data.total || 0;
                const recibos = data.recibos || [];
                
                console.log('[🔔 CAMPANA INSUMOS] Total:', total, '| Recibos cargados:', recibos.length);
                
                // Actualizar badge
                const badge = document.getElementById('insumosBadge');
                if (badge) {
                    badge.textContent = total;
                    badge.style.display = total > 0 ? 'inline-flex' : 'none';
                }
                
                // Poblar lista del dropdown
                const list = document.getElementById('insumosNotifList');
                if (list && recibos.length > 0) {
                    list.innerHTML = '';
                    recibos.forEach(function(recibo) {
                        const item = document.createElement('div');
                        item.className = 'p-3 hover:bg-gray-50 transition border-b border-gray-100';
                        item.setAttribute('data-recibo-id', recibo.id);
                        item.innerHTML = 
                            '<div class="flex justify-between items-center">' +
                                '<div class="flex-1 cursor-pointer" data-action="ver">' +
                                    '<p class="font-bold text-blue-600">Recibo #' + recibo.numero_recibo + '</p>' +
                                    '<p class="text-sm text-gray-600">' + recibo.cliente + '</p>' +
                                    '<p class="text-xs text-gray-400 mt-1">' + recibo.fecha + '</p>' +
                                '</div>' +
                                '<button class="btn-marcar-visto ml-2 p-1.5 rounded-full hover:bg-green-100 transition" data-id="' + recibo.id + '" title="Marcar como visto">' +
                                    '<svg class="w-5 h-5 text-gray-400 hover:text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                                        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>' +
                                    '</svg>' +
                                '</button>' +
                            '</div>';
                        
                        // Click en datos -> ver recibo
                        item.querySelector('[data-action="ver"]').addEventListener('click', function() {
                            if (typeof verReciboDesdeCampana === 'function') {
                                verReciboDesdeCampana(recibo.pedido_id);
                            }
                        });
                        
                        // Click en check -> marcar como visto
                        item.querySelector('.btn-marcar-visto').addEventListener('click', function(e) {
                            e.stopPropagation();
                            marcarReciboVisto(recibo.id, item);
                        });
                        
                        list.appendChild(item);
                    });
                    
                    // Si hay más recibos que los que mostramos
                    if (total > recibos.length) {
                        const moreItem = document.createElement('div');
                        moreItem.className = 'p-3 text-center text-gray-500 text-sm';
                        moreItem.textContent = '... y ' + (total - recibos.length) + ' recibo(s) más';
                        list.appendChild(moreItem);
                    }
                } else if (list && recibos.length === 0) {
                    list.innerHTML = '<div class="p-4 text-center text-gray-500"><p>Sin recibos pendientes</p></div>';
                }
            } else {
                console.error('[🔔 CAMPANA INSUMOS] Error HTTP:', response.status);
            }
        } catch (error) {
            console.error('[🔔 CAMPANA INSUMOS] Error cargando datos:', error);
        }
    }

    // Esperar a que el documento esté completamente cargado antes de inicializar
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            cargarConteoInicial();
            initializeRealtimeListener();
            setupNotificationBellControls();
        });
    } else {
        cargarConteoInicial();
        initializeRealtimeListener();
        setupNotificationBellControls();
    }

    /**
     * CSS para animaciones de notificación
     */
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(100px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideOutDown {
            from {
                opacity: 1;
                transform: translateY(0);
            }
            to {
                opacity: 0;
                transform: translateY(100px);
            }
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }

        .animate-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        #insumosBellBtn {
            position: relative;
            transition: all 0.2s ease-in-out;
        }

        #insumosBellBtn:hover {
            background-color: #dbeafe;
        }

        #insumosDropdown {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .notification-item {
            border-left: 4px solid #2563eb;
            transition: all 0.2s ease-in-out;
        }

        .notification-item:hover {
            background-color: #f3f4f6;
        }
    `;
    document.head.appendChild(style);
</script>

{{-- Modal: Pasar a Revisar --}}
<div id="modalPasarRevisar" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); z-index: 1000000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); max-width: 500px; width: 90%; padding: 0; overflow: hidden;">
        <div style="background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); color: white; padding: 1.5rem; display: flex; align-items: center; gap: 1rem;">
            <i class="fas fa-arrow-rotate-left" style="font-size: 1.5rem;"></i>
            <div>
                <h2 style="margin: 0; font-size: 1.25rem; font-weight: bold;">¿Pasar a Revisión?</h2>
                <p style="margin: 0.25rem 0 0 0; font-size: 0.9rem; opacity: 0.9;">Recibo a revisión por asesor</p>
            </div>
        </div>

        <div style="padding: 1.5rem;">
            <p style="margin: 0 0 1rem 0; color: #6b7280; font-size: 0.95rem;">
                Esta acción devolverá el recibo para que sea corregido.
            </p>

            <form id="formPasarRevisar" onsubmit="confirmarPasarRevisar(event)">
                <input type="hidden" id="reciboIdPasarRevisar" value=""><input type="hidden" id="pedidoIdPasarRevisar" value="">
                <div style="margin-bottom: 1rem;">
                    <label for="motivoPasarRevisar" style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #374151;">Motivo de la revisión *</label>
                    <textarea id="motivoPasarRevisar" name="motivo_pasar_revisar" rows="4" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-family: inherit; font-size: 0.95rem; resize: vertical;" placeholder="Ej: Revisar especificaciones, cambios en cantidad..." required minlength="10" maxlength="500"></textarea>
                    <small style="display: block; margin-top: 0.5rem; color: #6b7280; text-align: right;"><span id="contadorPasarRevisar">0</span>/500 caracteres</small>
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" onclick="cerrarModalPasarRevisar()" style="padding: 0.75rem 1.5rem; border: 1px solid #d1d5db; background: white; color: #374151; border-radius: 6px; cursor: pointer; font-weight: 500; transition: all 0.2s;">Cancelar</button>
                    <button type="submit" id="btnConfirmarPasarRevisar" style="padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; transition: all 0.2s;"><i class="fas fa-arrow-rotate-left"></i> Pasar a Revisión</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

