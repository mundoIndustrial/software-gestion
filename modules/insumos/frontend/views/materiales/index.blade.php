@extends('layouts.insumos.app')

@section('page-title', 'Materiales Insumos')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <h3>
                <i class="fas fa-box me-2"></i>Control de Insumos - Recibos de Costura
            </h3>
            <div style="position: relative; display: inline-block;">
                {{-- Campana de Notificaciones --}}
                <button id="notificationBellBtn" class="btn btn-outline-primary position-relative" title="Notificaciones de nuevos recibos aprobados" style="border: 2px solid #007bff;">
                    <i class="fas fa-bell fa-lg"></i>
                    <span id="notificationBadge" class="position-absolute top-0 start-100 translate-middle-y badge bg-danger" style="display: none;">0</span>
                </button>

                {{-- Dropdown de Notificaciones --}}
                <div id="notificationDropdown" class="dropdown-menu dropdown-menu-end" style="display: none; min-width: 400px; max-height: 500px; overflow-y: auto; position: absolute; right: 0; top: 100%; z-index: 1000; background: white; border: 1px solid #ddd; border-radius: 4px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <div class="dropdown-header" style="background: #f8f9fa; padding: 12px 16px; border-bottom: 1px solid #ddd;">
                        <h6 class="mb-0">Nuevos Recibos Aprobados</h6>
                        <small class="text-muted">Recibos COSTURA en Pendiente Insumos</small>
                    </div>
                    <div id="notificationsList" style="padding: 0;">
                        <div class="text-center text-muted py-3">
                            <p class="mb-0">Sin notificaciones</p>
                        </div>
                    </div>
                    <div style="padding: 8px 16px; border-top: 1px solid #ddd;">
                        <button id="clearNotificationsBtn" class="btn btn-sm btn-outline-secondary w-100">
                            <i class="fas fa-trash me-2"></i>Limpiar Notificaciones
                        </button>
                    </div>
                </div>
            </div>
            <a href="{{ route('insumos.materiales.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver a Materiales
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Recibos de Costura Pendientes de Insumos</h5>
            <small class="text-muted">Mostrando recibos COSTURA en estado PENDIENTE_INSUMOS</small>
        </div>
        <div class="card-body">
            <div id="recibosList" style="min-height: 300px;">
                <div class="text-center text-muted py-5">
                    <p>Cargando recibos...</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Sistema genérico de campana configurable por rol/vista --}}
<script>
console.log('========== SCRIPT CAMPANA INICIADO ==========');

// CONFIGURACIÓN DE CAMPANA ESPECÍFICA PARA INSUMOS - RECIBOS COSTURA
const CAMPANA_CONFIG = {
    // Identificador único de esta campana
    nombre: 'INSUMOS_COSTURA_PENDIENTE',
    
    // Endpoint para obtener contador
    endpoint: '/insumos/api/contar-costura-pendiente',
    
    // Filtró para eventos en tiempo real
    filtroEvento: function(data) {
        console.log('[FILTRO] Evaluando data:', data);
        // Solo mostrar COSTURA en PENDIENTE_INSUMOS
        return data.orden && data.orden.estado === 'PENDIENTE_INSUMOS';
    },
    
    // Cómo obtener datos de notificación del evento
    obtenerDatos: function(data) {
        console.log('[obtenerDatos] Extrayendo datos de:', data);
        return {
            numero: data.orden.numero_pedido || data.orden.pedido,
            cliente: data.orden.cliente_nombre || data.orden.cliente || 'Sin cliente',
            timestamp: new Date().toLocaleTimeString()
        };
    },
    
    // Canal de broadcast a escuchar
    canal: 'supervisor-pedidos',
    
    // Eventos a escuchar
    eventos: ['orden.updated', '.orden.updated', 'OrdenUpdated']
};

console.log('[CONFIG CAMPANA] Configuración definida:', CAMPANA_CONFIG);
console.log('[CONFIG CAMPANA] window.CAMPANA_CONFIG asignado:', window.CAMPANA_CONFIG);

// Sistema genérico de notificaciones (funciona para cualquier rol)
(function() {
    console.log('[ IIFE INICIO] Entrando en función autoejecutable');
    
    const CONFIG = window.CAMPANA_CONFIG || {};
    const nombre = CONFIG.nombre || 'CAMPANA_GENERICA';
    
    console.log('[🔔 SISTEMA ' + nombre + '] ========== INICIADO ==========');
    console.log('[🔔 SISTEMA ' + nombre + '] CONFIG disponible:', !!CONFIG);
    console.log('[🔔 SISTEMA ' + nombre + '] CONFIG.nombre:', nombre);
    console.log('[🔔 SISTEMA ' + nombre + '] CONFIG.endpoint:', CONFIG.endpoint);
    
    // Desactivar sistemas globales de notificaciones
    console.log('[🔔 SISTEMA ' + nombre + '] Desactivando funciones globales...');
    window.updateNotificationBadge = function() {
        console.log('[GLOBAL] updateNotificationBadge desactivado');
    };
    window.updateNotificationsList = function() {
        console.log('[GLOBAL] updateNotificationsList desactivado');
    };
    console.log('[🔔 SISTEMA ' + nombre + '] Funciones globales desactivadas ✓');

    const waitForDOM = () => {
        console.log('[DOM WAIT] document.readyState:', document.readyState);
        if (document.readyState === 'loading') {
            console.log('[DOM WAIT] DOM cargando, agregando listener...');
            document.addEventListener('DOMContentLoaded', function() {
                console.log('[DOM WAIT] DOMContentLoaded dispuesto');
                inicializar();
            });
        } else {
            console.log('[DOM WAIT] DOM ya listo, ejecutando con setTimeout(50)...');
            setTimeout(inicializar, 50);
        }
    };

    async function inicializar() {
        console.log('[🔔 ' + nombre + '] ========== INICIALIZANDO ==========');
        console.log('[🔔 ' + nombre + '] Paso 1: Validar configuración');
        
        // Validar configuración
        if (!CONFIG.endpoint) {
            console.error('[ ' + nombre + '] FALTA ENDPOINT');
            return;
        }
        console.log('[ ' + nombre + '] Endpoint válido:', CONFIG.endpoint);

        console.log('[🔔 ' + nombre + '] Paso 2: Obtener elementos del DOM');
        // Obtener elementos
        const bellBtn = document.getElementById('notificationBellBtn');
        const badge = document.getElementById('notificationBadge');
        
        console.log('[DOM] notificationBellBtn encontrado:', !!bellBtn);
        console.log('[DOM] notificationBadge encontrado:', !!badge);
        
        if (!bellBtn || !badge) {
            console.error('[ ' + nombre + '] ELEMENTOS NO ENCONTRADOS');
            console.error('[DOM] bellBtn:', bellBtn);
            console.error('[DOM] badge:', badge);
            return;
        }
        console.log('[ ' + nombre + '] Elementos encontrados');

        console.log('[🔔 ' + nombre + '] Paso 3: Limpiar estado previo');
        // Limpiar estado previo
        badge.textContent = '0';
        badge.style.display = 'none';
        badge.setAttribute('data-initialized', 'true');
        console.log('[ ' + nombre + '] Badge limpio:', badge.textContent, 'visible:', badge.style.display);

        console.log('[🔔 ' + nombre + '] Paso 4: Obtener contador desde API');
        // Obtener contador desde API
        try {
            console.log('[🌐 ' + nombre + '] Llamando endpoint:', CONFIG.endpoint);
            
            const response = await fetch(CONFIG.endpoint, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            console.log('[HTTP] Response status:', response.status);
            console.log('[HTTP] Response ok:', response.ok);

            if (response.ok) {
                const data = await response.json();
                const total = data.total || 0;
                
                console.log('[ ' + nombre + '] Respuesta API:', data);
                console.log('[ ' + nombre + '] Total:', total);
                console.log('[ ' + nombre + '] Debug:', data.debug);
                
                // Actualizar badge
                badge.textContent = total;
                badge.style.display = total > 0 ? 'inline-block' : 'none';
                badge.setAttribute('data-count', total);
                
                console.log('[ ' + nombre + '] Badge actualizado');
                console.log('[ ' + nombre + '] textContent:', badge.textContent);
                console.log('[ ' + nombre + '] display:', badge.style.display);
            } else {
                console.error('[ ' + nombre + '] Error HTTP:', response.status);
            }
        } catch (error) {
            console.error('[ ' + nombre + '] Error en fetch API:', error);
            console.error('[ERROR] Stack:', error.stack);
        }

        console.log('[🔔 ' + nombre + '] Paso 5: Setup controles de botón');
        // Setup interacción
        setupBellControls();
        
        console.log('[🔔 ' + nombre + '] Paso 6: Setup listeners en tiempo real');
        // Listeners en tiempo real
        if (CONFIG.canal && CONFIG.eventos) {
            console.log('[⏩ ' + nombre + '] Configuración de tiempo real detectada');
            if (window.EchoInstance) {
                console.log('[ ' + nombre + '] Echo disponible, configurando listeners...');
                setupRealtimeListeners();
            } else if (window.waitForEcho) {
                console.log('[⏳ ' + nombre + '] Usando waitForEcho callback...');
                window.waitForEcho(() => {
                    console.log('[ ' + nombre + '] waitForEcho callback ejecutado');
                    setupRealtimeListeners();
                });
            } else {
                console.warn('[ ' + nombre + '] Echo no disponible ni waitForEcho');
            }
        }
        
        console.log('[🔔 ' + nombre + '] ========== INICIALIZACIÓN COMPLETA ==========');
    }

    function setupBellControls() {
        console.log('[BELL CONTROLS] Iniciando setup...');
        const bellBtn = document.getElementById('notificationBellBtn');
        const dropdown = document.getElementById('notificationDropdown');
        
        console.log('[BELL CONTROLS] bellBtn encontrado:', !!bellBtn);
        console.log('[BELL CONTROLS] dropdown encontrado:', !!dropdown);
        
        if (bellBtn && dropdown) {
            bellBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                console.log('[BELL CLICK] Evento click detectado');
                dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
                console.log('[BELL CLICK] Dropdown display:', dropdown.style.display);
            });

            document.addEventListener('click', (e) => {
                if (!dropdown.contains(e.target) && !bellBtn.contains(e.target)) {
                    console.log('[BELL CLICK] Click fuera, cerrando dropdown');
                    dropdown.style.display = 'none';
                }
            });

            console.log('[ BELL CONTROLS] Configurado');
        }

        const clearBtn = document.getElementById('clearNotificationsBtn');
        console.log('[CLEAR BTN] Encontrado:', !!clearBtn);
        
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                console.log('[CLEAR BTN] Click en limpiar');
                const list = document.getElementById('notificationsList');
                if (list) {
                    list.innerHTML = '<div class="text-center text-muted py-3"><p class="mb-0">Sin notificaciones</p></div>';
                }
                const badge = document.getElementById('notificationBadge');
                if (badge) badge.style.display = 'none';
                console.log('[CLEAR BTN] Notificaciones limpiadas');
            });
        }
    }

    function setupRealtimeListeners() {
        console.log('[REALTIME] ========== CONFIGURANDO TIEMPO REAL ==========');
        
        const echo = window.EchoInstance;
        console.log('[REALTIME] Echo disponible:', !!echo);
        
        if (!echo) {
            console.error('[ REALTIME] Echo no disponible');
            return;
        }

        const channel = echo.channel(CONFIG.canal);
        console.log('[REALTIME] Canal:', CONFIG.canal);
        console.log('[REALTIME] Eventos a escuchar:', CONFIG.eventos);
        
        (CONFIG.eventos || []).forEach(eventName => {
            console.log('[REALTIME] Agregando listener para evento:', eventName);
            channel.listen(eventName, (data) => {
                console.log('[📢 REALTIME] Evento recibido:', eventName);
                console.log('[📢 REALTIME] Data:', data);
                
                // Aplicar filtro del rol
                const pasaFiltro = !CONFIG.filtroEvento || CONFIG.filtroEvento(data);
                console.log('[📢 REALTIME] ¿Pasa filtro?:', pasaFiltro);
                
                if (pasaFiltro) {
                    console.log('[ REALTIME] PROCESANDO EVENTO');
                    
                    // Actualizar contador
                    const badge = document.getElementById('notificationBadge');
                    if (badge) {
                        const num = parseInt(badge.textContent || '0') + 1;
                        badge.textContent = num;
                        badge.style.display = 'inline-block';
                        console.log('[REALTIME] Badge incrementado a:', num);
                    }

                    // Agregar notificación al dropdown
                    const notifData = CONFIG.obtenerDatos ? CONFIG.obtenerDatos(data) : {};
                    console.log('[REALTIME] Datos notificación:', notifData);
                    agregarNotificacion(notifData);
                }
            });
        });

        console.log('[ REALTIME] ========== CONFIGURACIÓN COMPLETA ==========');
    }

    function agregarNotificacion(notif) {
        console.log('[NOTIF] Agregando notificación:', notif);
        const list = document.getElementById('notificationsList');
        if (!list) {
            console.error('[ NOTIF] Elemento notificationsList no encontrado');
            return;
        }

        // Limpiar "sin notificaciones" si existe
        if (list.children.length === 1 && list.children[0]?.textContent.includes('Sin notificaciones')) {
            console.log('[NOTIF] Limpiando "sin notificaciones"');
            list.innerHTML = '';
        }

        // Crear elemento de notificación
        const item = document.createElement('div');
        item.style.cssText = 'padding: 12px 16px; border-bottom: 1px solid #eee; cursor: pointer;';
        item.innerHTML = 
            '<strong style="color: #007bff;">Recibo #' + (notif.numero || 'N/A') + '</strong><br>' +
            '<small style="color: #666;">' + (notif.cliente || 'Sin cliente') + '</small><br>' +
            '<small style="color: #999; font-size: 0.85em;">' + (notif.timestamp || '') + '</small>';
        list.insertBefore(item, list.firstChild);
        
        console.log('[ NOTIF] Notificación agregada');
    }

    console.log('[ IIFE] Llamando waitForDOM()');
    waitForDOM();
    console.log('[ IIFE] waitForDOM() llamado');
})();

console.log('========== SCRIPT CAMPANA TERMINADO ==========');
</script>
@endsection
