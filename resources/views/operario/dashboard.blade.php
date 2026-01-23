@extends('operario.layout')

@section('title', 'Mis Órdenes')
@section('page-title', '')

@php
    // Helper para obtener clase de estado
    function getEstadoClass($estado) {
        $estado = strtolower(trim($estado));
        if (strpos($estado, 'ejecución') !== false || strpos($estado, 'proceso') !== false) {
            return 'en-proceso';
        }
        if (strpos($estado, 'completada') !== false || strpos($estado, 'completado') !== false) {
            return 'completada';
        }
        return 'pendiente';
    }
@endphp

@section('content')
<div class="operario-dashboard">
    <!-- Búsqueda -->
    <div class="search-section">
        <span class="material-symbols-rounded">search</span>
        <input type="text" id="searchInput" class="search-box" placeholder="Buscar por # Orden o Cliente...">
    </div>

    <!-- Mis Órdenes Section -->
    <div class="ordenes-section">
        <div class="section-title">
            <span class="material-symbols-rounded">assignment</span>
            <h3>MIS ORDENES</h3>
            <span class="ordenes-count">{{ count($operario->pedidos) }}</span>
        </div>

        @if(count($operario->pedidos) > 0)
            <div class="ordenes-list" id="ordenesList">
                @foreach($operario->pedidos as $pedido)
                    @php
                        $estadoClass = getEstadoClass($pedido['estado']);
                    @endphp
                    <div class="orden-card-simple" data-numero="{{ $pedido['numero_pedido'] }}" data-cliente="{{ strtolower($pedido['cliente']) }}">
                        <!-- Borde izquierdo coloreado -->
                        <div class="orden-border {{ $estadoClass }}"></div>

                        <!-- Contenido Izquierdo -->
                        <div class="orden-body">
                            <div class="orden-left">
                                <div class="orden-top">
                                    <div class="orden-numero-section">
                                        <h4 class="orden-numero">#{{ $pedido['numero_pedido'] }}</h4>
                                        <span class="estado-badge {{ $estadoClass }}" data-estado="{{ $pedido['estado'] }}">
                                            {{ strtoupper($pedido['estado']) }}
                                        </span>
                                    </div>
                                </div>

                                <div class="orden-cliente">
                                    <p class="cliente-label">CLIENTE</p>
                                    <p class="cliente-name">{{ $pedido['cliente'] }}</p>
                                </div>

                                <div class="orden-prendas">
                                    <p class="prendas-label">{{ $pedido['cantidad'] }} prenda(s): {{ $pedido['descripcion'] }}</p>
                                </div>

                                <!-- Contenedor de Botones -->
                                <div class="orden-buttons">
                                    <!-- Botón Reportar Pendiente -->
                                    <button class="btn-reportar-pendiente" onclick="abrirModalReportar('{{ $pedido['numero_pedido'] }}', '{{ $pedido['cliente'] }}')">
                                        <span class="material-symbols-rounded">warning</span>
                                        REPORTAR PENDIENTE
                                    </button>
                                    
                                    @if(!(strtolower($pedido['estado']) === 'completada' || strtolower($pedido['estado']) === 'completado'))
                                    <!-- Botón Completar Proceso -->
                                    <button class="btn-completar-proceso" onclick="marcarProcesoCompletado('{{ $pedido['numero_pedido'] }}')">
                                        <span class="material-symbols-rounded">check_circle</span>
                                        COMPLETAR PROCESO
                                    </button>
                                    @endif
                                </div>
                            </div>

                            <!-- Contenido Derecho -->
                            <div class="orden-right">
                                <div class="orden-fecha">
                                    <span class="orden-fecha-label">FECHA INICIO</span>
                                    <span>{{ $pedido['fecha_creacion'] }}</span>
                                </div>
                                <div class="orden-fecha">
                                    <span class="orden-fecha-label">EN {{ strtoupper($operario->areaOperario) }}</span>
                                    <span>{{ $pedido['fecha_inicio_proceso'] }}</span>
                                </div>
                                <a href="{{ route('operario.ver-pedido', $pedido['numero_pedido']) }}" class="action-arrow">
                                    <span class="material-symbols-rounded">arrow_forward</span>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <span class="material-symbols-rounded">inbox</span>
                <p>No hay órdenes asignadas</p>
            </div>
        @endif
    </div>
</div>

<style>
    .operario-dashboard {
        padding: 1.5rem;
        max-width: 1000px;
        margin: 0 auto;
    }

    /* Búsqueda */
    .search-section {
        position: relative;
        margin-bottom: 1.5rem;
    }

    .search-box {
        width: 100%;
        padding: 0.6rem 1rem 0.6rem 2.5rem;
        border: 1px solid #ddd;
        border-radius: 24px;
        font-size: 0.85rem;
        background: #f9f9f9;
        transition: all 0.3s ease;
    }

    .search-box:focus {
        outline: none;
        background: white;
        border-color: #1976d2;
        box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.1);
    }

    .search-section .material-symbols-rounded {
        position: absolute;
        left: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: #999;
        font-size: 18px;
        pointer-events: none;
    }

    /* Órdenes Section */
    .ordenes-section {
        background: white;
    }

    .section-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #eee;
    }

    .section-title .material-symbols-rounded {
        color: #333;
        font-size: 20px;
    }

    .section-title h3 {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 700;
        color: #333;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .ordenes-count {
        margin-left: auto;
        background: transparent;
        color: #999;
        padding: 0;
        border-radius: 0;
        font-size: 0.85rem;
        font-weight: 600;
    }

    /* Órdenes List */
    .ordenes-list {
        display: grid;
        gap: 0.75rem;
    }

    .orden-card-simple {
        display: flex;
        background: white;
        border: 1px solid #eee;
        border-radius: 6px;
        overflow: hidden;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .orden-card-simple:hover {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        border-color: #ddd;
    }

    .orden-border {
        width: 4px;
        flex-shrink: 0;
    }

    .orden-border.en-proceso {
        background: #2196F3;
    }

    .orden-border.pendiente {
        background: #FFC107;
    }

    .orden-border.completada {
        background: #4CAF50;
    }

    .orden-body {
        flex: 1;
        padding: 0.9rem 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .orden-left {
        flex: 1;
    }

    .orden-top {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.4rem;
    }

    .orden-numero-section {
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }

    .orden-numero {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: #333;
    }

    .estado-badge {
        display: inline-block;
        padding: 0.3rem 0.6rem;
        border-radius: 3px;
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .estado-badge.en-proceso {
        background: #E3F2FD;
        color: #1976D2;
    }

    .estado-badge.pendiente {
        background: #FFF3E0;
        color: #F57C00;
    }

    .estado-badge.completada {
        background: #E8F5E9;
        color: #388E3C;
    }

    .orden-cliente {
        margin-bottom: 0;
    }

    .cliente-label {
        margin: 0;
        font-size: 0.65rem;
        color: #999;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .cliente-name {
        margin: 0.15rem 0 0;
        font-size: 0.85rem;
        font-weight: 600;
        color: #333;
    }

    .orden-prendas {
        margin-bottom: 0;
    }

    .prendas-label {
        margin: 0;
        font-size: 0.75rem;
        color: #666;
        line-height: 1.3;
    }

    .orden-right {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-left: 1rem;
    }

    .orden-fecha {
        text-align: right;
        font-size: 0.75rem;
        color: #999;
        font-weight: 500;
        white-space: nowrap;
    }

    .orden-fecha-label {
        display: block;
        font-size: 0.65rem;
        color: #ccc;
        margin-bottom: 0.2rem;
    }

    .action-arrow {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: #f0f0f0;
        color: #999;
        text-decoration: none;
        transition: all 0.3s ease;
        flex-shrink: 0;
    }

    .action-arrow:hover {
        background: #1976d2;
        color: white;
        transform: translateX(2px);
    }

    .action-arrow .material-symbols-rounded {
        font-size: 16px;
    }

    /* Botón Reportar Pendiente */
    .orden-actions {
        padding: 0.75rem 1rem;
        border-top: 1px solid #f0f0f0;
        background: #fafafa;
    }

    .orden-buttons {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.6rem;
        flex-wrap: wrap;
    }

    .btn-reportar-pendiente {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        margin-top: 0.6rem;
        padding: 0.5rem 0.8rem;
        background: #FFEBEE;
        color: #EF5350;
        border: 1px solid #EF5350;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        cursor: pointer;
        transition: all 0.3s ease;
        width: fit-content;
        box-shadow: 0 2px 4px rgba(239, 83, 80, 0.15);
    }

    .btn-reportar-pendiente:hover {
        background: #FFCDD2;
        box-shadow: 0 4px 8px rgba(239, 83, 80, 0.25);
        transform: translateY(-1px);
    }

    .btn-reportar-pendiente .material-symbols-rounded {
        font-size: 14px;
    }

    .btn-completar-proceso {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.5rem 0.8rem;
        background: #E8F5E9;
        color: #388E3C;
        border: 1px solid #388E3C;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        cursor: pointer;
        transition: all 0.3s ease;
        width: fit-content;
        box-shadow: 0 2px 4px rgba(56, 142, 60, 0.15);
    }

    .btn-completar-proceso:hover {
        background: #C8E6C9;
        box-shadow: 0 4px 8px rgba(56, 142, 60, 0.25);
        transform: translateY(-1px);
    }

    .btn-completar-proceso .material-symbols-rounded {
        font-size: 14px;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 2rem;
        color: #999;
    }

    .empty-state .material-symbols-rounded {
        font-size: 48px;
        margin-bottom: 0.75rem;
        opacity: 0.5;
    }

    .empty-state p {
        font-size: 0.9rem;
        margin: 0;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .operario-dashboard {
            padding: 1rem;
        }

        .operario-header {
            margin-bottom: 1rem;
        }

        .operario-name {
            font-size: 1rem;
        }

        .orden-body {
            flex-direction: column;
            align-items: flex-start;
        }

        .orden-right {
            width: 100%;
            margin-left: 0;
            margin-top: 0.5rem;
            justify-content: space-between;
        }

        .orden-fecha {
            text-align: left;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const ordenesList = document.getElementById('ordenesList');
        const ordenCards = ordenesList ? ordenesList.querySelectorAll('.orden-card-simple') : [];

        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                const busqueda = e.target.value.toLowerCase().trim();

                ordenCards.forEach(card => {
                    const numero = card.dataset.numero.toLowerCase();
                    const cliente = card.dataset.cliente.toLowerCase();

                    // Mostrar si coincide con número o cliente
                    if (numero.includes(busqueda) || cliente.includes(busqueda) || busqueda === '') {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        }
    });

    // Modal Reportar Pendiente
    function abrirModalReportar(numeroPedido, cliente) {
        const modal = document.getElementById('modalReportar');
        document.getElementById('numeroPedidoReportar').value = numeroPedido;
        document.getElementById('numeroPedidoDisplay').textContent = '#' + numeroPedido;
        document.getElementById('clienteReportar').textContent = cliente;
        document.getElementById('novedadText').value = '';
        document.getElementById('novedadesAnteriores').innerHTML = '<p style="color: #999;">Cargando novedades...</p>';
        
        // Cargar novedades anteriores
        fetch('/operario/api/novedades/' + numeroPedido)
            .then(response => response.json())
            .then(data => {
                const contenedor = document.getElementById('novedadesAnteriores');
                if (data.success && data.novedades) {
                    const novedades = data.novedades.split('\n\n').filter(n => n.trim());
                    if (novedades.length > 0) {
                        contenedor.innerHTML = '<div class="novedades-list">' + 
                            novedades.map(novedad => `<div class="novedad-item">${novedad}</div>`).join('') + 
                            '</div>';
                    } else {
                        contenedor.innerHTML = '<p style="color: #999;">No hay novedades anteriores</p>';
                    }
                } else {
                    contenedor.innerHTML = '<p style="color: #999;">No hay novedades anteriores</p>';
                }
            })
            .catch(error => {
                document.getElementById('novedadesAnteriores').innerHTML = '<p style="color: #999;">No hay novedades anteriores</p>';
            });
        
        modal.style.display = 'flex';
    }

    function cerrarModalReportar() {
        const modal = document.getElementById('modalReportar');
        modal.style.display = 'none';
        document.getElementById('novedadText').value = '';
    }

    function enviarReporte() {
        const numeroPedido = document.getElementById('numeroPedidoReportar').value;
        const novedad = document.getElementById('novedadText').value.trim();

        if (!novedad) {
            alert('Por favor describe el problema o novedad');
            return;
        }

        // Enviar al servidor
        fetch('{{ route("operario.reportar-pendiente") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                numero_pedido: numeroPedido,
                novedad: novedad
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                cerrarModalReportar();
                abrirModalExito('Novedad reportada correctamente', 'El estado ha sido cambiado a Pendiente.');
                
                // Recargar después de 2 segundos para que se actualice con los nuevos datos
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                alert('Error: ' + (data.message || 'No se pudo reportar la novedad'));
            }
        })
        .catch(error => {
            alert('Error al reportar la novedad');
        });
    }

    // Cerrar modal al hacer click fuera
    window.onclick = function(event) {
        const modal = document.getElementById('modalReportar');
        const modalExito = document.getElementById('modalExito');
        if (event.target === modal) {
            cerrarModalReportar();
        }
        if (event.target === modalExito) {
            cerrarModalExito();
        }
    }

    // Modal de éxito
    function abrirModalExito(titulo, mensaje) {
        document.getElementById('exitoTitulo').textContent = titulo;
        document.getElementById('exitoMensaje').textContent = mensaje;
        document.getElementById('modalExito').style.display = 'flex';
    }

    function cerrarModalExito() {
        document.getElementById('modalExito').style.display = 'none';
    }

    // Cerrar modal exitoal hacer click fuera

    // Función para marcar proceso como completado
    window.marcarProcesoCompletado = async function(numeroPedido) {
        try {
            const response = await fetch(`/operario/api/completar-proceso/${numeroPedido}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const data = await response.json();

            if (response.ok) {
                // Mostrar modal de éxito
                document.getElementById('exitoTitulo').textContent = '¡Proceso Completado!';
                document.getElementById('exitoMensaje').textContent = 'El proceso ha sido marcado como completado correctamente.';
                document.getElementById('modalExito').style.display = 'flex';
            } else {
                // Mostrar modal de error
                document.getElementById('exitoTitulo').textContent = ' Error';
                document.getElementById('exitoMensaje').textContent = data.message || 'No se pudo completar el proceso';
                document.getElementById('modalExito').style.display = 'flex';
            }
        } catch (error) {
            // Mostrar modal de error
            document.getElementById('exitoTitulo').textContent = ' Error';
            document.getElementById('exitoMensaje').textContent = 'Error al completar el proceso';
            document.getElementById('modalExito').style.display = 'flex';
        }
    };

    window.cerrarModalExito = function() {
        const modal = document.getElementById('modalExito');
        modal.style.display = 'none';
        
        // Recargar la página si fue exitoso (cuando el título es "¡Proceso Completado!")
        const titulo = document.getElementById('exitoTitulo').textContent;
        if (titulo === '¡Proceso Completado!') {
            setTimeout(() => {
                location.reload();
            }, 500);
        }
    };
</script>

<!-- Modal Reportar Pendiente -->
<div id="modalReportar" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <span class="material-symbols-rounded modal-icon">warning</span>
            <h2>REPORTAR PENDIENTE</h2>
        </div>

        <div class="modal-body">
            <p class="modal-description">Describe el problema o novedad. La orden pasará a estado <strong>Pendiente</strong>.</p>
            
            <div class="modal-info">
                <p class="info-label">ORDEN:</p>
                <p class="info-value" id="numeroPedidoDisplay"></p>
                <p class="info-label">CLIENTE:</p>
                <p class="info-value" id="clienteReportar"></p>
            </div>

            <!-- Novedades anteriores -->
            <div style="margin: 15px 0; padding: 10px; background: #f9f9f9; border-radius: 4px; border-left: 3px solid #3498db;">
                <p class="info-label" style="margin-top: 0;">NOVEDADES ANTERIORES:</p>
                <div id="novedadesAnteriores" style="max-height: 150px; overflow-y: auto; font-size: 13px;"></div>
            </div>

            <textarea 
                id="novedadText" 
                class="modal-textarea" 
                placeholder="Ej: Falta talla M, insumo hilo rojo, error en medidas..."
                rows="5"></textarea>
            
            <input type="hidden" id="numeroPedidoReportar">
        </div>

        <div class="modal-footer">
            <button class="btn-cancelar" onclick="cerrarModalReportar()">CANCELAR</button>
            <button class="btn-enviar" onclick="enviarReporte()">ENVIAR</button>
        </div>
    </div>
</div>

<style>
    .novedades-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .novedad-item {
        padding: 8px;
        background: white;
        border-radius: 3px;
        border-left: 2px solid #27ae60;
        font-size: 12px;
        color: #2c3e50;
        line-height: 1.4;
    }
    
    /* Modal Overlay */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }

    .modal-content {
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        max-width: 450px;
        width: 90%;
        overflow: hidden;
        animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
        from {
            transform: translateY(20px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .modal-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1.5rem;
        background: #fafafa;
        border-bottom: 1px solid #eee;
    }

    .modal-icon {
        color: #EF5350;
        font-size: 28px;
    }

    .modal-header h2 {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: #EF5350;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .modal-body {
        padding: 1.5rem;
    }

    .modal-description {
        margin: 0 0 1rem;
        font-size: 0.85rem;
        color: #666;
        line-height: 1.5;
    }

    .modal-info {
        background: #f9f9f9;
        padding: 1rem;
        border-radius: 4px;
        margin-bottom: 1rem;
    }

    .info-label {
        margin: 0 0 0.25rem;
        font-size: 0.7rem;
        color: #999;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .info-value {
        margin: 0 0 0.75rem;
        font-size: 0.9rem;
        font-weight: 600;
        color: #333;
    }

    .info-value:last-child {
        margin-bottom: 0;
    }

    .modal-textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-family: 'Poppins', sans-serif;
        font-size: 0.85rem;
        color: #333;
        resize: vertical;
        transition: all 0.3s ease;
    }

    .modal-textarea:focus {
        outline: none;
        border-color: #1976d2;
        box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.1);
    }

    .modal-textarea::placeholder {
        color: #999;
    }

    .modal-footer {
        display: flex;
        gap: 0.75rem;
        padding: 1rem 1.5rem;
        background: #fafafa;
        border-top: 1px solid #eee;
    }

    .btn-cancelar {
        flex: 1;
        padding: 0.75rem;
        background: #f0f0f0;
        color: #666;
        border: none;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-cancelar:hover {
        background: #e0e0e0;
    }

    .btn-enviar {
        flex: 1;
        padding: 0.75rem;
        background: #EF5350;
        color: white;
        border: none;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-enviar:hover {
        background: #E53935;
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(239, 83, 80, 0.3);
    }

    /* Modal de Éxito */
    .modal-exito {
        max-width: 400px;
    }

    .modal-header-exito {
        background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
        border: none;
    }

    .modal-header-exito h2 {
        color: white;
    }

    .modal-icon-exito {
        color: white;
        font-size: 32px;
    }
</style>

<!-- Modal Éxito -->
<div id="modalExito" class="modal-overlay">
    <div class="modal-content modal-exito">
        <div class="modal-header modal-header-exito">
            <span class="material-symbols-rounded modal-icon-exito">check_circle</span>
            <h2 id="exitoTitulo">¡Éxito!</h2>
        </div>

        <div class="modal-body">
            <p id="exitoMensaje" style="text-align: center; color: #2c3e50; font-size: 16px; margin: 30px 0;">
                La novedad ha sido guardada correctamente.
            </p>
        </div>

        <div class="modal-footer" style="justify-content: center;">
            <button class="btn-enviar" style="width: 150px;" onclick="cerrarModalExito()">ACEPTAR</button>
        </div>
    </div>
</div>

@endsection

