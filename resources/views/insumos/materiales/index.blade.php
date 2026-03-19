{{-- resources/views/insumos/materiales/index.blade.php --}}
@extends('layouts.insumos')

@section('title', 'Gestión de Insumos - Control de Insumos del Pedido')
@section('page-title', 'Control de Insumos del Pedido')

@section('content')
<link rel="stylesheet" href="{{ asset('css/insumos/materiales.css') }}?v={{ time() }}">
{{-- Todos los estilos CSS extraídos a public/css/insumos/materiales.css --}}

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
     * Delegado al backend mediante API asincrónica
     */
    async function calcularDemora(materialId) {
        const idParts = materialId.split('_');
        const ordenId = idParts[1];
        const index = idParts[2];
        
        const fechaPedidoInput = document.getElementById('fecha_pedido_' + ordenId + '_' + index + '_' + idParts.slice(3).join('_'));
        const fechaLlegadaInput = document.getElementById('fecha_llegada_' + ordenId + '_' + index + '_' + idParts.slice(3).join('_'));
        const diasSpan = document.getElementById('dias_' + materialId);
        
        if (!fechaPedidoInput || !fechaLlegadaInput || !diasSpan) {
            return;
        }
        
        if (!fechaPedidoInput.value || !fechaLlegadaInput.value) {
            diasSpan.textContent = '-';
            diasSpan.className = 'inline-block px-3 py-1 rounded-full text-sm font-semibold bg-gray-100 text-gray-600';
            return;
        }
        
        const demora = await window.calcularDemoraAsync(fechaPedidoInput.value, fechaLlegadaInput.value);
        diasSpan.textContent = demora.texto;
        diasSpan.className = `inline-block px-3 py-1 rounded-full text-sm font-semibold ${demora.clase_bg} ${demora.clase_text}`;
    }

    // Variable global para rastrear el botón del dropdown abierto
    let dropdownAbiertoButton = null;
    let dropdownVerAbiertoButton = null;

    /**
     * Crear dropdown para Ver Recibo / Seguimiento
     */
    function crearDropdownVerRecibo(event, button) {
        event.preventDefault();
        event.stopPropagation();
        
        // Si el dropdown está abierto del mismo botón, cerrarlo
        if (dropdownVerAbiertoButton === button) {
            cerrarDropdownVerRecibo();
            dropdownVerAbiertoButton = null;
            return;
        }
        
        // Cerrar dropdown anterior si existe
        cerrarDropdownVerRecibo();
        
        // Guardar referencia al botón actual
        dropdownVerAbiertoButton = button;
        
        const container = document.getElementById('dropdowns-container');
        if (!container) return;
        
        // Obtener datos del botón
        const pedidoId = button.getAttribute('data-pedido-id') || button.getAttribute('data-pedido-produccion-id');
        const prendaId = button.getAttribute('data-prenda-id');
        
        // Obtener posición del botón relativa al viewport
        const rect = button.getBoundingClientRect();
        
        // Crear elemento del dropdown
        const dropdown = document.createElement('div');
        dropdown.className = 'dropdown-ver-fixed';
        dropdown.style.cssText = `
            position: absolute;
            top: ${rect.bottom}px;
            left: ${rect.left}px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            min-width: 180px;
            z-index: 999999;
            overflow: visible;
            pointer-events: auto;
        `;
        
        const html = `
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
            " onclick="abrirDetalleRecibo('${pedidoId}', '${prendaId ?? 'null'}', 'COSTURA'); cerrarDropdownVerRecibo();" 
            onmouseover="this.style.background='#dbeafe'" 
            onmouseout="this.style.background='transparent'">
                <i class="fas fa-file-lines" style="color: #3b82f6; font-size: 1rem;"></i>
                <span>Ver recibo</span>
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
            " onclick="abrirSeguimientoRecibo('${pedidoId}', '${prendaId ?? ''}'); cerrarDropdownVerRecibo();" 
            onmouseover="this.style.background='#e0f2fe'" 
            onmouseout="this.style.background='transparent'">
                <i class="fas fa-map-location-dot" style="color: #0284c7; font-size: 1rem;"></i>
                <span>Seguimiento</span>
            </button>
        `;
        
        dropdown.innerHTML = html;
        container.appendChild(dropdown);
    }

    /**
     * Cerrar dropdown Ver Recibo
     */
    function cerrarDropdownVerRecibo() {
        document.querySelectorAll('.dropdown-ver-fixed').forEach(menu => {
            menu.remove();
        });
        dropdownVerAbiertoButton = null;
    }

    /**
     * Abre el tracking directamente desde el botón VER, obteniendo datos de la fila
     */
    function abrirTrackingDesdeBotonVer(event, button) {
        event.preventDefault();
        event.stopPropagation();
        
        console.log('[abrirTrackingDesdeBotonVer] Iniciando...');
        
        // Obtener datos del botón VER
        const pedidoProduccionId = button.getAttribute('data-pedido-produccion-id');
        const prendaId = button.getAttribute('data-prenda-id');
        
        // Buscar la fila (tr) más cercana
        const fila = button.closest('tr');
        if (!fila) {
            console.error('[abrirTrackingDesdeBotonVer] No se encontró la fila');
            return;
        }
        
        // Buscar el botón de acciones ("...") en la misma fila para obtener los datos completos
        const btnAcciones = fila.querySelector('.btn-acciones');
        let consecutivo = null;
        let estado = null;
        let tipoRecibo = null;
        
        if (btnAcciones) {
            consecutivo = btnAcciones.getAttribute('data-consecutivo');
            estado = btnAcciones.getAttribute('data-estado');
            tipoRecibo = btnAcciones.getAttribute('data-tipo-recibo');
            console.log('[abrirTrackingDesdeBotonVer] Datos obtenidos del botón acciones:', {
                consecutivo, estado, tipoRecibo
            });
        } else {
            console.warn('[abrirTrackingDesdeBotonVer] No se encontró btn-acciones, continuando sin esos datos');
        }
        
        // Abrir modal con los datos completos
        abrirModalInsumos(pedidoProduccionId, prendaId, consecutivo, estado, tipoRecibo);
    }
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
        
        // Obtener posición del botón relativa al viewport
        const rect = button.getBoundingClientRect();
        
        // Crear elemento del dropdown con position absolute dentro del contenedor fixed
        const dropdown = document.createElement('div');
        dropdown.className = 'acciones-dropdown-fixed';
        dropdown.style.cssText = `
            position: absolute;
            top: ${rect.bottom}px;
            left: ${rect.right}px;
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
            " onclick="crearDropdownVerRecibo(event, this, '${pedidoProduccionId}', '${prendaId}'); cerrarDropdownAcciones();" 
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
     * Cerrar dropdown cuando se hace scroll (similar a contador)
     */
    window.addEventListener('scroll', function() {
        if (dropdownAbiertoButton) {
            cerrarDropdownAcciones();
        }
        if (dropdownVerAbiertoButton) {
            cerrarDropdownVerRecibo();
        }
    }, { passive: true });

    /**
     * Cerrar dropdowns cuando se hace click fuera
     */
    document.addEventListener('click', function(e) {
        const isClickInsideDropdownVer = e.target.closest('.dropdown-ver-fixed');
        const isClickInsideDropdownAcciones = e.target.closest('.acciones-dropdown-fixed');
        
        // Cerrar dropdown de ver si el click es fuera
        if (dropdownVerAbiertoButton && !isClickInsideDropdownVer && e.target !== dropdownVerAbiertoButton && !dropdownVerAbiertoButton.contains(e.target)) {
            cerrarDropdownVerRecibo();
        }
        
        // Cerrar dropdown de acciones si el click es fuera
        if (dropdownAbiertoButton && !isClickInsideDropdownAcciones && e.target !== dropdownAbiertoButton && !dropdownAbiertoButton.contains(e.target)) {
            cerrarDropdownAcciones();
        }
    }, false);

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
     * Abrir modal de seguimiento del recibo con datos completos
     */
    async function abrirSeguimientoRecibo(pedidoId, prendaId, consecutivo = null, estado = null, tipoRecibo = null) {
        console.log('[abrirSeguimientoRecibo] 1. INICIANDO FUNCIÓN');
        
        pedidoId = parseInt(pedidoId) || null;
        prendaId = parseInt(prendaId) || null;
        
        console.log('[abrirSeguimientoRecibo] 2. Parámetros procesados:', {
            pedidoId,
            prendaId,
            consecutivo,
            estado,
            tipoRecibo
        });
        
        if (!pedidoId) {
            console.error('pedidoId es requerido para abrir el seguimiento');
            return;
        }
        
        try {
            console.log('[abrirSeguimientoRecibo] 3. Buscando procesos...');
            
            // Cargar procesos de la prenda específica
            let procesos = [];
            if (prendaId) {
                const url = `/api/ordenes/${pedidoId}/procesos?prenda_id=${prendaId}`;
                console.log('[abrirSeguimientoRecibo] 4. URL de procesos:', url);
                
                const response = await fetch(url);
                console.log('[abrirSeguimientoRecibo] 5. Response status:', response.status, response.ok);
                
                if (response.ok) {
                    procesos = await response.json();
                    console.log('[abrirSeguimientoRecibo] 6. Procesos cargados:', procesos);
                } else {
                    const errorText = await response.text();
                    console.warn('[abrirSeguimientoRecibo] Error en respuesta:', errorText);
                }
            }

            console.log('[abrirSeguimientoRecibo] 7. Construyendo objeto prenda...');
            
            // Crear objeto prenda con procesos cargados e información del recibo
            const prenda = {
                id: prendaId,
                pedido_produccion_id: pedidoId,
                numero_prenda: prendaId,
                procesos: procesos,
                // Información del recibo para el header del modal
                ultimo_recibo_numero: consecutivo || null,
                consecutivos: consecutivo ? [consecutivo] : [],
                estado: estado || 'PENDIENTE_INSUMOS',
                tipo_recibo: tipoRecibo || 'COSTURA',
                area: estado === 'En Ejecución' ? 'Costura' : estado || 'Insumos',
                // Estructurar procesos por área para compatibilidad con seguimientos_por_area
                seguimientos_por_area: procesos.reduce((acc, proceso) => {
                    const area = proceso.proceso || 'Desconocida';
                    if (!acc[area]) {
                        acc[area] = [];
                    }
                    acc[area].push(proceso);
                    return acc;
                }, {})
            };
            
            console.log('[abrirSeguimientoRecibo] 8. Objeto prenda completo:', prenda);
            console.log('[abrirSeguimientoRecibo] 9. typeof showPrendaTracking:', typeof showPrendaTracking);
            console.log('[abrirSeguimientoRecibo] 10. showPrendaTracking disponible?', window.showPrendaTracking ? 'SÍ' : 'NO');
            
            // Llamar a showPrendaTracking si existe
            if (typeof showPrendaTracking === 'function') {
                console.log('[abrirSeguimientoRecibo] 11. Llamando a showPrendaTracking...');
                const resultado = showPrendaTracking(prenda);
                console.log('[abrirSeguimientoRecibo] 12. showPrendaTracking retornó:', resultado);
            } else {
                console.error('[abrirSeguimientoRecibo] 11. showPrendaTracking NO es una función');
                console.error('window.showPrendaTracking:', window.showPrendaTracking);
                
                // Intentar buscar en objetos globales
                console.log('[DEBUG] Buscando showPrendaTracking en window...');
                for (let key in window) {
                    if (key.toLowerCase().includes('tracking') || key.toLowerCase().includes('prenda')) {
                        console.log('[DEBUG] Encontrado:', key, typeof window[key]);
                    }
                }
            }
        } catch (error) {
            console.error('[abrirSeguimientoRecibo] ERROR CAPTURADO:', error);
            console.error('[abrirSeguimientoRecibo] Stack:', error.stack);
            alert('Error al cargar el seguimiento: ' + error.message);
        }
    }

    /**
     * Aplica modo readonly al modal de tracking para desactivar edición
     * Oculta botones de editar, eliminar y agregar proceso
     */
    function aplicarModoReadonly() {
        console.log('[aplicarModoReadonly] ========== INICIANDO ==========');
        
        // Función para aplicar los estilos
        function aplicarReadonly() {
            console.log('[aplicarModoReadonly] Buscando modal...');
            const modal = document.getElementById('orderTrackingModal');
            
            if (!modal) {
                console.warn('[aplicarModoReadonly] ⚠ Modal NO encontrado');
                return false;
            }
            
            console.log('[aplicarModoReadonly] ✓ Modal encontrado, aplicando estilos...');
            
            let modificados = 0;
            
            // 1. OCULTAR BOTONES DE EDICIÓN (tracking-edit-btn)
            const editButtons = modal.querySelectorAll('button.tracking-edit-btn');
            console.log('[aplicarModoReadonly] Botones EDITAR encontrados:', editButtons.length);
            editButtons.forEach((btn, index) => {
                console.log('[aplicarModoReadonly]   • Ocultando EDITAR #' + (index + 1));
                btn.style.display = 'none !important';
                btn.style.visibility = 'hidden';
                btn.style.position = 'absolute';
                btn.style.pointerEvents = 'none';
                btn.disabled = true;
                modificados++;
            });
            
            // 2. OCULTAR BOTONES DE ELIMINACIÓN (tracking-delete-btn)
            const deleteButtons = modal.querySelectorAll('button.tracking-delete-btn');
            console.log('[aplicarModoReadonly] Botones ELIMINAR encontrados:', deleteButtons.length);
            deleteButtons.forEach((btn, index) => {
                console.log('[aplicarModoReadonly]   • Ocultando ELIMINAR #' + (index + 1));
                btn.style.display = 'none !important';
                btn.style.visibility = 'hidden';
                btn.style.position = 'absolute';
                btn.style.pointerEvents = 'none';
                btn.disabled = true;
                modificados++;
            });
            
            // 3. OCULTAR BOTÓN "AGREGAR ÁREA / PROCESO" (btnOpenAddProcesoModal)
            const btnAgregar = document.getElementById('btnOpenAddProcesoModal');
            console.log('[aplicarModoReadonly] Botón AGREGAR ÁREA:', btnAgregar ? 'ENCONTRADO' : 'NO ENCONTRADO');
            if (btnAgregar) {
                console.log('[aplicarModoReadonly]   • Ocultando AGREGAR ÁREA');
                btnAgregar.style.display = 'none !important';
                btnAgregar.style.visibility = 'hidden';
                btnAgregar.style.position = 'absolute';
                btnAgregar.style.pointerEvents = 'none';
                btnAgregar.disabled = true;
                modificados++;
            }
            
            console.log('[aplicarModoReadonly] ✓ TOTAL DE CAMBIOS APLICADOS:', modificados);
            return modificados > 0;
        }
        
        // Intentar aplicar inmediatamente
        console.log('[aplicarModoReadonly] Intento 1: aplicando inmediatamente...');
        let aplicado = aplicarReadonly();
        
        if (!aplicado) {
            // Si no se aplicó, esperar y reintentar
            console.log('[aplicarModoReadonly] Intento 1 fallido, esperando 300ms...');
            setTimeout(() => {
                console.log('[aplicarModoReadonly] Intento 2: reintentando después de espera...');
                aplicarReadonly();
            }, 300);
        }
        
        // Intento 3: Después de 800ms
        setTimeout(() => {
            console.log('[aplicarModoReadonly] Intento 3: después de 800ms...');
            aplicarReadonly();
        }, 800);
        
        // Crear observador para cambios dinámicos
        console.log('[aplicarModoReadonly] Configurando observador de mutaciones...');
        const observer = new MutationObserver((mutations) => {
            setTimeout(() => {
                const resultado = aplicarReadonly();
                if (resultado) {
                    console.log('[aplicarModoReadonly] Readonly re-aplicado después de mutación');
                }
            }, 100);
        });
        
        // Observar cambios en el modal
        const modal = document.getElementById('orderTrackingModal');
        if (modal) {
            console.log('[aplicarModoReadonly] ✓ Observador iniciado en modal');
            observer.observe(modal, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeOldValue: true
            });
        } else {
            console.warn('[aplicarModoReadonly] ⚠ No se pudo iniciar observador (modal no encontrado)');
        }
        
        console.log('[aplicarModoReadonly] ========== FINALIZADO ==========');
    }

    // Funciones de modal 'Pasar a Revisar' movidas a pasar-a-revisar-insumos.js
    // - abrirModalPasarRevisar()
    // - cerrarModalPasarRevisar()
    // - contador de caracteres
    // - cerrar al hacer clic fuera
</script>

{{-- Toast Container --}}
<div id="toastContainer" style="position: fixed; top: 24px; right: 24px; z-index: 99999; display: flex; flex-direction: column; gap: 12px; pointer-events: none;"></div>

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
                                <span>N° Recibo</span>
                            </th>
                            <th class="text-left py-4 px-6 font-bold">
                                <div class="flex items-center justify-between gap-2">
                                    <span>N° Pedido</span>
                                </div>
                            </th>
                            <th class="text-left py-4 px-6 font-bold">
                                <span>Cliente</span>
                            </th>
                            <th class="text-center py-4 px-6 font-bold">
                                <span>Estado</span>
                            </th>
                            <th class="text-center py-4 px-6 font-bold">
                                <span>Área</span>
                            </th>
                            <th class="text-center py-4 px-6 font-bold">
                                <span>Fecha de Inicio</span>
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
                                    {{-- Indicador de materiales (punto rojo en esquina izquierda) --}}
                                    @if(isset($orden->tiene_materiales) && $orden->tiene_materiales)
                                        <div 
                                            class="btn-tooltip"
                                            data-tooltip="Contiene {{ $orden->cantidad_materiales }} material(es)"
                                            title="Contiene {{ $orden->cantidad_materiales }} material(es)"
                                            style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); display: inline-flex; align-items: center; justify-content: center;"
                                        >
                                            <div class="w-3 h-3 bg-red-500 rounded-full animate-pulse" style="box-shadow: 0 0 8px rgba(239, 68, 68, 0.6);"></div>
                                        </div>
                                    @endif

                                    <div class="flex items-center justify-center gap-3" style="display: flex !important; flex-wrap: wrap; overflow: visible;">
                                        {{-- Definir variables primero --}}
                                        @php
                                            $userRole = auth()->user()->role;
                                            $roleName = is_object($userRole) ? $userRole->name : $userRole;
                                            $isPatronista = $roleName === 'patronista';
                                            $isInsumos = $roleName === 'insumos';
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

                                        {{-- Dropdown Ver Recibo / Seguimiento --}}
                                        <button 
                                            class="btn-ver-dropdown btn-tooltip p-2 text-blue-600 hover:bg-blue-50 rounded transition relative"
                                            onclick="abrirTrackingDesdeBotonVer(event, this)"
                                            data-pedido-id="{{ $pedidoProduccionId }}"
                                            data-pedido-produccion-id="{{ $pedidoProduccionId }}"
                                            data-prenda-id="{{ $orden->prenda_id ?? '' }}"
                                            data-tooltip="Ver recibo o seguimiento"
                                            title="Ver recibo o seguimiento"
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
</script>

{{-- Modal para ver orden --}}
<!-- Modal de Detalle de Orden -->
<div id="modal-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 9997; display: none; pointer-events: auto;" onclick="closeModalOverlay()"></div>

<div id="order-detail-modal-wrapper" style="width: 90%; max-width: 672px; position: fixed; top: 60%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto; display: none;">
    <x-orders-components.order-detail-modal />
</div>

<!-- Modal de Seguimiento del Pedido -->
<x-orders-components.order-tracking-modal />

<!-- Contenedor para dropdowns dinámicos con position fixed -->
<div id="dropdowns-container" style="position: fixed; top: 0; left: 0; z-index: 999999; pointer-events: none;"></div>

<script>
    /**
     * Alias para cerrar el modal - compatible con asesores
     */

    // Remaining inline script starts here - All remaining functions moved to JS modules


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

    // Función movida a modal-ancho-metraje-insumos.js: generarInputsPorColor()
    // Función movida a modal-ancho-metraje-insumos.js: generarInputsPorTallaColor()
    // Función movida a modal-ancho-metraje-insumos.js: generarInputsPorPieza()
    // Función movida a modal-ancho-metraje-insumos.js: cambiarModoAnchoMetraje()
    // Función movida a modal-ancho-metraje-insumos.js: cerrarModalAnchoMetraje()

    // Funciones stub movidas a index-blade-handlers.js en FASE 6

    /**
     * Abre el modal de seguimiento usando el tracking de recibos-costura con datos completos
     */
    function abrirModalInsumos(pedido, prendaId, consecutivo = null, estado = null, tipoRecibo = null) {
        console.log('[abrirModalInsumos] INICIANDO - Parámetros recibidos:', {
            pedido,
            prendaId,
            consecutivo,
            estado,
            tipoRecibo
        });
        // Delegar al modal de tracking de recibos-costura
        abrirSeguimientoRecibo(pedido, prendaId, consecutivo, estado, tipoRecibo);
    }

    /**
     * Cierra el modal (ya no necesaria, pero se mantiene por compatibilidad)
     */
    function cerrarModalInsumos() {
        // El modal de tracking se cierra por su propio handler
    }

    // Filtro functions han sido movidas a filter-modal-insumos.js
    
    // Función movida a status-actions-insumos.js: cambiarEstadoRecibo()

    // Función movida a status-actions-insumos.js: cambiarEstadoPedido()
    
    // Función movida a status-actions-insumos.js: cerrarModalConfirmarProduccion()
    
    // Función movida a status-actions-insumos.js: restaurarBotonAprobar()
    
    // Función movida a status-actions-insumos.js: confirmarEnvioProduccion()
    
    // ========== EXPONER FUNCIONES A WINDOW GLOBAL ==========
    // Esto permite que otros scripts accedan a estas funciones
    window.abrirModalInsumos = abrirModalInsumos;
    window.cerrarModalInsumos = cerrarModalInsumos;
    window.abrirSeguimientoRecibo = abrirSeguimientoRecibo;
    
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

<!-- Script de Diagnóstico del Tracking Modal -->
<script>
    console.log('\n========================================');
    console.log('DIAGNÓSTICO: Tracking Modal en Materiales');
    console.log('========================================\n');
    
    // Esperar a que el DOM esté completamente listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', runDiagnostico);
    } else {
        // DOM ya está listo
        setTimeout(runDiagnostico, 500);
    }
    
    function runDiagnostico() {
        console.log('[CHECK 1] ¿showPrendaTracking existe?');
        console.log('  Type:', typeof window.showPrendaTracking);
        console.log('  Value:', window.showPrendaTracking);
        
        console.log('\n[CHECK 2] ¿Modal existe?');
        const modal = document.getElementById('orderTrackingModal');
        console.log('  Encontrado:', !!modal);
        
        console.log('\n[CHECK 3] ¿abrirModalInsumos funciona?');
        console.log('  Type:', typeof window.abrirModalInsumos);
        
        console.log('\n[CHECK 4] Scripts en la página:');
        const scripts = document.querySelectorAll('script[src*="tracking"]');
        console.log('  Tracking scripts encontrados:', scripts.length);
        scripts.forEach(s => {
            console.log(`    • ${s.src}`);
            console.log(`      Defer: ${s.defer}, Async: ${s.async}`);
        });
        
        console.log('\n[CHECK 5] Esperando 3 segundos más para defer scripts...');
        setTimeout(() => {
            console.log('  showPrendaTracking ahora?', typeof window.showPrendaTracking);
            if (typeof window.showPrendaTracking !== 'function') {
                console.error('  ❌ PROBLEMA: showPrendaTracking no está disponible');
                console.log('\n[DEBUG] Buscando funciones que contengan "tracking":');
                Object.keys(window).forEach(key => {
                    if (key.includes('tracking') || key.includes('Tracking') || 
                        key.includes('prenda') || key.includes('Prenda')) {
                        console.log(`    • ${key}: ${typeof window[key]}`);
                    }
                });
            } else {
                console.log('  ✅ showPrendaTracking está disponible');
            }
        }, 3000);
    }
    
    // Test manual: En la consola ejecuta:
    console.log('\n[INSTRUCCIÓN] Para testear manualmente, ejecuta en consola:');
    console.log('  abrirModalInsumos(8, 5, "1", "PENDIENTE_INSUMOS", "COSTURA")');
    console.log('  O verifica la consola para más detalles...\n');
</script>

<!-- Scripts para el modal de órdenes (defer para no-críticos) -->
<script defer src="{{ asset('js/ordersjs/order-detail-modal-manager.js') }}"></script>
<script defer src="{{ asset('js/asesores/pedidos-detail-modal.js') }}"></script>
<!-- Image Gallery para mostrar fotos en el modal -->
<script defer src="{{ asset('js/orders-scripts/image-gallery-zoom.js') }}"></script>
<script defer src="{{ asset('js/insumos/pagination.js') }}"></script>

<!-- Módulos de Insumos - Critical Path (carga inmediata) -->
<script type="module" src="{{ asset('js/insumos/index.js') }}"></script>

<!-- FASE 1-2: Módulos base (critical) -->
<script src="{{ asset('js/insumos/modal-handlers-insumos.js') }}"></script>
<script src="{{ asset('js/insumos/table-handlers-insumos.js') }}"></script>
<script src="{{ asset('js/insumos/filter-manager-insumos.js') }}"></script>

<!-- Inicializar sistema de festivos (API externa + fallback local) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof inicializarFestivos === 'function') {
        inicializarFestivos().catch(error => {
            console.error('[Init] Error inicializando festivos:', error);
        });
    }
});
</script>
<script src="{{ asset('js/insumos/material-operations-insumos.js') }}"></script>
<script src="{{ asset('js/insumos/form-handlers-insumos.js') }}"></script>

<!-- FASE 3-6: Módulos adicionales (defer) -->
<script defer src="{{ asset('js/insumos/status-actions-insumos.js') }}"></script>
<script defer src="{{ asset('js/insumos/modal-ancho-metraje-insumos.js') }}"></script>
<script defer src="{{ asset('js/insumos/filter-modal-insumos.js') }}"></script>
<script defer src="{{ asset('js/insumos/insumos-modal-management.js') }}"></script>
<script defer src="{{ asset('js/insumos/notifications-realtime-insumos.js') }}"></script>
<script defer src="{{ asset('js/insumos/recibos-selector-insumos.js') }}"></script>
<script defer src="{{ asset('js/insumos/pasar-a-revisar-insumos.js') }}"></script>
<script defer src="{{ asset('js/insumos/index-blade-handlers.js') }}"></script>

<!-- Performance - Utilidades (defer) -->
<script defer src="{{ asset('js/insumos/search-debounce.js') }}"></script>

<!-- Scripts no-críticos (defer) -->
<script defer src="{{ asset('js/ordersjs/tracking-modal-utils.js') }}?v={{ time() }}"></script>
<script defer src="{{ asset('js/ordersjs/tracking-modal-handler.js') }}?v={{ time() }}"></script>
<script defer src="{{ asset('js/asesores/pedidos-dropdown-simple.js') }}"></script>
<script defer src="{{ asset('js/modulos/invoice/InvoiceLazyLoader.js') }}?v={{ time() }}"></script>
<script defer src="{{ asset('js/asesores/invoice-from-list.js') }}"></script>
<script defer src="{{ asset('js/asesores/receipt-manager.js') }}"></script>
<script defer src="{{ asset('js/insumos/insumos-galeria.js') }}"></script>

<!-- Scripts para Recibos/Procesos (SIN defer para carga rápida) -->
<script type="module" src="{{ asset('js/modulos/pedidos-recibos/loader.js') }}"></script>

<!-- Detectar rol insumos y aplicar readonly automáticamente -->
<script>
    // Variable global para el rol del usuario
    window.userRole = '{{ $roleName ?? "guest" }}';
    window.isInsumos = window.userRole === 'insumos';
    
    console.log('[Sistema] Rol del usuario:', window.userRole);
    console.log('[Sistema] ¿Es rol INSUMOS?:', window.isInsumos);
    
    if (window.isInsumos) {
        console.log('[Sistema] ✓ MODO READONLY ACTIVADO - Usuario con rol INSUMOS puede solo VER, no editar');
    }
</script>

@endsection

