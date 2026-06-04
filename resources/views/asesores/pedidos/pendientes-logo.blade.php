@extends('layouts.asesores')

@section('title', 'Diseños Pendientes de Confirmar')
@section('page-title', 'Diseños Pendientes de Confirmar')

@section('extra_styles')
    <style>
        .pendientes-logo-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
            padding: 20px;
        }

        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .pendientes-table {
            width: 100%;
            border-collapse: collapse;
        }

        .pendientes-table thead {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-bottom: 2px solid #e2e8f0;
        }

        .pendientes-table th {
            padding: 16px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .pendientes-table tbody tr {
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.2s ease;
        }

        .pendientes-table tbody tr:hover {
            background: #f8fafc;
        }

        .pendientes-table tbody tr:last-child {
            border-bottom: none;
        }

        .pendientes-table td {
            padding: 16px;
            font-size: 14px;
            color: #334155;
        }

        .btn-ver {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-ver:hover {
            background: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
        }

        .btn-confirmar {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-confirmar:hover {
            background: #059669;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(16, 185, 129, 0.3);
        }

        .loading-container {
            text-align: center;
            padding: 60px 20px;
        }

        .loading-spinner {
            width: 48px;
            height: 48px;
            border: 4px solid #e5e7eb;
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 16px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            border: 2px dashed #e5e7eb;
        }

        .empty-state .empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }

        .empty-state h3 {
            font-size: 18px;
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .empty-state p {
            font-size: 14px;
            color: #9ca3af;
        }

        .estado-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .estado-pendiente {
            background: #fef3c7;
            color: #92400e;
        }

        .estado-confirmado {
            background: #d1fae5;
            color: #065f46;
        }

        .acciones-cell {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        /* Custom Confirmation Modal Styles */
        .confirm-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
        }

        .confirm-modal-overlay.active {
            display: flex;
        }

        .confirm-modal {
            background: white;
            border-radius: 16px;
            padding: 32px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .confirm-modal-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .confirm-modal-icon i {
            font-size: 32px;
            color: white;
        }

        .confirm-modal-title {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            text-align: center;
            margin-bottom: 12px;
        }

        .confirm-modal-message {
            font-size: 15px;
            color: #64748b;
            text-align: center;
            margin-bottom: 24px;
            line-height: 1.5;
        }

        .confirm-modal-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
        }

        .confirm-modal-btn {
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
            min-width: 120px;
        }

        .confirm-modal-btn-cancel {
            background: #f1f5f9;
            color: #64748b;
        }

        .confirm-modal-btn-cancel:hover {
            background: #e2e8f0;
            color: #475569;
        }

        .confirm-modal-btn-confirm {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }

        .confirm-modal-btn-confirm:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }
    </style>
@endsection

@section('content')
    <div class="pendientes-logo-container">
        <div id="disenosContainer">
            <div class="loading-container">
                <div class="loading-spinner"></div>
                <p style="color: #6b7280; font-size: 14px;">Cargando diseños pendientes...</p>
            </div>
        </div>
    </div>

{{-- MODAL EXACTO COMO EN visualizador-logo/pedidos-logo --}}
<div id="modal-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 9997; display: none; pointer-events: auto;" onclick="closeModalOverlay()"></div>

<div id="order-detail-modal-wrapper" style="width: 90%; max-width: 672px; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto; display: none;">
    <x-orders-components.order-detail-modal />
</div>

{{-- Custom Confirmation Modal --}}
<div id="confirm-modal-overlay" class="confirm-modal-overlay">
    <div class="confirm-modal">
        <div class="confirm-modal-icon">
            <i class="fas fa-check"></i>
        </div>
        <h3 class="confirm-modal-title">Confirmar Diseño</h3>
        <p class="confirm-modal-message">¿Estás seguro de que deseas confirmar este diseño? Esta acción no se puede deshacer.</p>
        <div class="confirm-modal-actions">
            <button class="confirm-modal-btn confirm-modal-btn-cancel" onclick="cerrarConfirmModal()">Cancelar</button>
            <button class="confirm-modal-btn confirm-modal-btn-confirm" onclick="ejecutarConfirmacion()">Confirmar</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            cargarDisenosPendientes();
        });

        function cargarDisenosPendientes() {
            const container = document.getElementById('disenosContainer');
            
            container.innerHTML = `
                <div class="loading-container">
                    <div class="loading-spinner"></div>
                    <p style="color: #6b7280; font-size: 14px;">Cargando diseños pendientes...</p>
                </div>
            `;

            fetch('/api/asesores/diseños-pendientes-logo', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Datos recibidos:', data);
                if (data.success && data.diseños) {
                    mostrarDiseños(data.diseños);
                } else {
                    mostrarError(data.message || 'Error al cargar diseños');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarError('Error al cargar los datos: ' + error.message);
            });
        }

        function mostrarDiseños(diseños) {
            const container = document.getElementById('disenosContainer');
            
            if (!diseños || diseños.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-icon">✓</div>
                        <h3>¡Todos los diseños confirmados!</h3>
                        <p>No hay diseños pendientes de confirmar en este momento</p>
                    </div>
                `;
                return;
            }

            const filasHtml = diseños.map((diseño, index) => `
                <tr>
                    <td>
                        <div class="acciones-cell">
                            <button class="btn-ver" data-pedido-id="${diseño.pedido_id}" data-prenda-id="${diseño.prenda_id}" data-tipo-proceso="${btoa(JSON.stringify(diseño.tipo_proceso))}">
                                <i class="fas fa-eye"></i> Ver
                            </button>
                        </div>
                    </td>
                    <td>${diseño.cliente || 'Sin cliente'}</td>
                    <td>${diseño.prenda || 'Sin prenda'}</td>
                    <td>${diseño.fecha || '-'}</td>
                    <td>
                        <span class="estado-badge ${diseño.estado === 'pendiente_por_confirmar' ? 'estado-pendiente' : 'estado-confirmado'}">
                            ${diseño.estado === 'pendiente_por_confirmar' ? 'Pendiente por confirmar' : 'Confirmado'}
                        </span>
                    </td>
                </tr>
            `).join('');

            const html = `
                <div class="table-container">
                    <table class="pendientes-table">
                        <thead>
                            <tr>
                                <th>Acciones</th>
                                <th>Cliente</th>
                                <th>Prenda</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${filasHtml}
                        </tbody>
                    </table>
                </div>
            `;
            
            container.innerHTML = html;

            // Add click event listeners to "Ver" buttons
            container.querySelectorAll('.btn-ver').forEach(btn => {
                btn.addEventListener('click', () => {
                    const pedidoId = parseInt(btn.getAttribute('data-pedido-id'));
                    const prendaId = parseInt(btn.getAttribute('data-prenda-id'));
                    const tipoProcesoBase64 = btn.getAttribute('data-tipo-proceso');
                    const tipoProceso = JSON.parse(atob(tipoProcesoBase64));
                    abrirRecibo(pedidoId, prendaId, tipoProceso);
                });
            });
        }

        function abrirRecibo(pedidoId, prendaId, tipoRecibo) {
            fetch(`/api/asesores/obtener-recibo-datos/${pedidoId}/${prendaId}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Acceso validado, abriendo recibo...');
                    verRecibo(pedidoId, prendaId, tipoRecibo);
                } else {
                    alert('Error: ' + (data.message || 'No tienes permiso para ver este recibo'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al validar acceso al recibo');
            });
        }

        function cargarModuloRecibos(pedidoId, prendaId, tipoRecibo) {
            // Si ya está cargado, abrir directamente
            if (window.openOrderDetailModalWithProcess) {
                console.log('Módulo ya cargado, abriendo recibo...');
                window.openOrderDetailModalWithProcess(pedidoId, prendaId, tipoRecibo);
                return;
            }

            // Si no, cargar el módulo
            console.log('Cargando módulo de recibos...');
            const script = document.createElement('script');
            script.src = "{{ asset('js/modulos/pedidos-recibos/loader.js') }}?v={{ time() }}";
            script.type = 'module';
            script.onload = () => {
                console.log('Módulo cargado, esperando openOrderDetailModalWithProcess...');
                let intentos = 0;
                const intervalo = setInterval(() => {
                    if (window.openOrderDetailModalWithProcess) {
                        clearInterval(intervalo);
                        console.log('Abriendo recibo...');
                        window.openOrderDetailModalWithProcess(pedidoId, prendaId, tipoRecibo);
                    }
                    intentos++;
                    if (intentos > 50) {
                        clearInterval(intervalo);
                        console.error('El módulo no se pudo cargar después de 50 intentos');
                        alert('El módulo de recibos no se pudo cargar');
                    }
                }, 100);
            };
            document.body.appendChild(script);
        }

        // Variables to store confirmation data
        let confirmDiseñoId = null;
        let confirmProcesoId = null;

        function confirmarDiseño(diseñoId, procesoId) {
            confirmDiseñoId = diseñoId;
            confirmProcesoId = procesoId;
            abrirConfirmModal();
        }

        function abrirConfirmModal() {
            const overlay = document.getElementById('confirm-modal-overlay');
            if (overlay) {
                overlay.classList.add('active');
            }
        }

        function cerrarConfirmModal() {
            const overlay = document.getElementById('confirm-modal-overlay');
            if (overlay) {
                overlay.classList.remove('active');
            }
            confirmDiseñoId = null;
            confirmProcesoId = null;
        }

        function ejecutarConfirmacion() {
            if (!confirmDiseñoId || !confirmProcesoId) {
                cerrarConfirmModal();
                return;
            }

            const diseñoId = confirmDiseñoId;
            const procesoId = confirmProcesoId;

            cerrarConfirmModal();

            fetch('/api/asesores/confirmar-diseño-logo', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({
                    diseño_id: diseñoId,
                    proceso_id: procesoId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Diseño confirmado correctamente');
                    cargarDisenosPendientes();
                } else {
                    alert('Error: ' + (data.message || 'No se pudo confirmar el diseño'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al confirmar el diseño');
            });
        }

        function mostrarError(mensaje) {
            const container = document.getElementById('disenosContainer');
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">⚠️</div>
                    <h3>Error al cargar diseños</h3>
                    <p>${mensaje}</p>
                </div>
            `;
        }

        // ========== FUNCIONES EXACTAS DE visualizador-logo/pedidos-logo ==========

        window.__pedidosRecibosLoaderUrl = @json(asset('js/modulos/pedidos-recibos/loader.js') . '?v=' . time());
        window.__pedidosRecibosLoaderPromise = null;

        window.__ensurePedidosRecibosModule = async function() {
            const tieneApiGlobal = typeof window.openOrderDetailModalWithProcess === 'function';
            const tieneInstancia = !!(window.pedidosRecibosModule && typeof window.pedidosRecibosModule.abrirRecibo === 'function');
            if (tieneApiGlobal || tieneInstancia) return true;

            const loaderUrl = String(window.__pedidosRecibosLoaderUrl || '').trim();
            if (!loaderUrl) return false;

            try {
                if (!window.__pedidosRecibosLoaderPromise) {
                    window.__pedidosRecibosLoaderPromise = import(loaderUrl);
                }
                await window.__pedidosRecibosLoaderPromise;
            } catch (error) {
                console.error('[pedidos-logo] Error cargando loader de recibos:', error);
                return false;
            }

            return (
                typeof window.openOrderDetailModalWithProcess === 'function' ||
                !!(window.pedidosRecibosModule && typeof window.pedidosRecibosModule.abrirRecibo === 'function')
            );
        };

        // Función global para ver recibo directamente (sin selector)
        window.verRecibo = async function(pedidoId, prendaId, tipoProceso, esParcial = false, pedidoParcialId = null, nombreProceso = null, numeroRecibo = null) {
            const moduloListo = await window.__ensurePedidosRecibosModule();
            if (!moduloListo) {
                console.error('El módulo de recibos no está disponible');
                alert('Error: El módulo de recibos no está disponible. Por favor recargue la página.');
                return;
            }

            const tipo = String(tipoProceso);

            // Si es anexo (recibo parcial), abrir usando el flujo de parcial para que cargue sus tallas/consecutivo.
            if (esParcial && pedidoParcialId) {
                if (window.pedidosRecibosModule && typeof window.pedidosRecibosModule.abrirReciboParcial === 'function') {
                    const nombre = nombreProceso ? String(nombreProceso) : tipo;
                    return window.pedidosRecibosModule.abrirReciboParcial(pedidoId, prendaId, tipo, Number(pedidoParcialId), nombre);
                }

                console.error('PedidosRecibosModule no está disponible para abrir anexos');
                alert('Error: No se pudo abrir el anexo. Por favor recargue la página.');
                return;
            }

            // Caso normal: abrir recibo base
            if (typeof window.openOrderDetailModalWithProcess === 'function') {
                return window.openOrderDetailModalWithProcess(pedidoId, prendaId, tipo, null, numeroRecibo);
            }

            if (window.pedidosRecibosModule && typeof window.pedidosRecibosModule.abrirRecibo === 'function') {
                return window.pedidosRecibosModule.abrirRecibo(pedidoId, prendaId, tipo, null, {
                    targetConsecutivo: numeroRecibo || null
                });
            }

            console.error('La función openOrderDetailModalWithProcess no está disponible');
            alert('Error: El módulo de recibos no está disponible. Por favor recargue la página.');
        };

        // Cerrar modal al hacer clic en el overlay
        window.closeModalOverlay = function() {
            const overlay = document.getElementById('modal-overlay');
            const wrapper = document.getElementById('order-detail-modal-wrapper');
            if (overlay) overlay.style.display = 'none';
            if (wrapper) wrapper.style.display = 'none';
            
            // Cerrar el módulo de recibos si está disponible
            if (typeof window.cerrarModalRecibos === 'function') {
                window.cerrarModalRecibos();
            }
        };

        // Cargar el módulo de recibos al inicio
        document.addEventListener('DOMContentLoaded', function() {
            // Pre-cargar el módulo en background
            window.__ensurePedidosRecibosModule().catch(err => {
                console.log('[pendientes-logo] Módulo no pre-cargado (no es crítico):', err);
            });
        });
    </script>
@endpush
