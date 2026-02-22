@push('scripts')
<!-- Scripts para Recibos/Procesos -->
<script type="module" src="{{ asset('js/modulos/pedidos-recibos/loader.js') }}"></script>

<!-- Script para el modal de seguimiento -->
<script src="{{ asset('js/ordersjs/tracking-modal-handler.js') }}?v={{ time() }}"></script>

<!-- Scripts para la funcionalidad de Día de Entrega -->
<script src="{{ asset('js/ordersjs/modules/diaEntregaModule.js') }}?v={{ time() }}"></script>

<!-- Scripts para Formatters -->
<script type="module" src="{{ asset('js/modulos/pedidos-recibos/utils/Formatters.js') }}?v={{ time() }}"></script>

<!-- Script para novedades de recibos (sistema completo) -->
<script src="{{ asset('js/recibos-novedades.js') }}?v={{ time() }}"></script>

<!-- Script con funciones globales (cargado antes del HTML) -->
<script>
// Funciones globales para el modal de celda formateada
function abrirModalCeldaConFormato(titulo, prendas) {
    console.log('[abrirModalCeldaConFormato]  INICIO - Datos recibidos:');
    console.log('[abrirModalCeldaConFormato] Título:', titulo);
    console.log('[abrirModalCeldaConFormato] Prendas tipo:', typeof prendas);
    console.log('[abrirModalCeldaConFormato] Prendas es array:', Array.isArray(prendas));
    console.log('[abrirModalCeldaConFormato] Prendas cantidad:', prendas ? prendas.length : 0);
    console.log('[abrirModalCeldaConFormato] Prendas RAW:', prendas);
    
    let htmlContenido = '';
    
    if (!prendas || prendas.length === 0) {
        htmlContenido = '<div style="text-align: center; color: #9ca3af;">No hay prendas disponibles</div>';
    } else {
        prendas.forEach((prenda, idx) => {
            console.log(`[abrirModalCeldaConFormato] ⚡ Procesando prenda ${idx}:`, prenda);
            
            // Convertir objeto Eloquent a objeto simple si es necesario
            let prendaData = prenda.toJSON ? prenda.toJSON() : prenda;
            console.log(`[abrirModalCeldaConFormato]  Después toJSON:`, prendaData);
            
            // NORMALIZAR datos: convertir objetos a strings
            prendaData = normalizarPrendaData(prendaData);
            console.log(`[abrirModalCeldaConFormato]  Después normalizar:`, prendaData);
            console.log(`[abrirModalCeldaConFormato] Campos principales: nombre="${prendaData.nombre_prenda}", tela="${prendaData.tela}", color="${prendaData.color}", manga="${prendaData.manga}"`);
            
            // Generar HTML formateado como en el recibo
            let prendaHtml = '';
            try {
                // Intentar usar Formatters si está disponible
                if (window.Formatters && typeof window.Formatters.construirDescripcionCostura === 'function') {
                    console.log(`[abrirModalCeldaConFormato] 🎯 Usando window.Formatters.construirDescripcionCostura`);
                    prendaHtml = window.Formatters.construirDescripcionCostura(prendaData);
                } else if (typeof Formatters !== 'undefined' && typeof Formatters.construirDescripcionCostura === 'function') {
                    console.log(`[abrirModalCeldaConFormato] 🎯 Usando Formatters.construirDescripcionCostura (module)`);
                    prendaHtml = Formatters.construirDescripcionCostura(prendaData);
                } else {
                    // Fallback si Formatters no disponible - generar HTML simple
                    console.log(`[abrirModalCeldaConFormato]  Formatters no disponible, usando fallback`);
                    prendaHtml = generarDescripcionSimple(prendaData);
                }
            } catch (e) {
                console.error('[abrirModalCeldaConFormato]  Error al formatear prenda:', e);
                console.error('[abrirModalCeldaConFormato] Stack:', e.stack);
                prendaHtml = generarDescripcionSimple(prendaData);
            }
            
            console.log(`[abrirModalCeldaConFormato] 📄 HTML generado:`, prendaHtml);
            
            htmlContenido += `<div style="margin-bottom: 1.5rem; padding: 1rem; background: #f9fafb; border-radius: 8px; border-left: 4px solid #3b82f6;">
                ${prendaHtml}
            </div>`;
        });
    }
    
    console.log('[abrirModalCeldaConFormato]  HTML FINAL A MOSTRAR:', htmlContenido);
    
    // Crear y mostrar el modal
    const modal = document.createElement('div');
    modal.id = 'modal-celda-formateada';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    `;
    
    const content = document.createElement('div');
    content.style.cssText = `
        background: white;
        border-radius: 12px;
        max-width: 800px;
        width: 100%;
        max-height: 80vh;
        overflow-y: auto;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    `;
    
    content.innerHTML = `
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 20px 24px; border-bottom: 1px solid #e5e7eb; background: #f9fafb; border-radius: 12px 12px 0 0;">
            <h2 style="margin: 0; font-size: 18px; font-weight: 600; color: #111827;">${titulo}</h2>
            <button onclick="this.closest('#modal-celda-formateada').remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 6px;">×</button>
        </div>
        <div style="padding: 24px;">
            ${htmlContenido}
        </div>
    `;
    
    modal.appendChild(content);
    document.body.appendChild(modal);
    
    // Cerrar al hacer clic fuera
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    });
    
    // Cerrar con ESC
    const handleEscape = (e) => {
        if (e.key === 'Escape') {
            modal.remove();
            document.removeEventListener('keydown', handleEscape);
        }
    };
    document.addEventListener('keydown', handleEscape);
}

function cerrarModalCeldaFormateada() {
    const modal = document.getElementById('modal-celda-formateada');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Funciones auxiliares
function generarDescripcionSimple(prenda) {
    console.log('[generarDescripcionSimple]  INPUT:', prenda);
    let html = '';
    
    // Título
    if (prenda.nombre_prenda) {
        html += `<strong style="font-size: 13.4px;">PRENDA: ${prenda.nombre_prenda.toUpperCase()}</strong><br>`;
        console.log('[generarDescripcionSimple]  Nombre agregado');
    }
    
    // Atributos básicos
    if (prenda.tela || prenda.color || prenda.manga) {
        let attrs = [];
        if (prenda.tela) {
            attrs.push(`<strong>TELA:</strong> ${prenda.tela.toUpperCase()}`);
            console.log('[generarDescripcionSimple]  Tela:', prenda.tela);
        }
        if (prenda.color) {
            attrs.push(`<strong>COLOR:</strong> ${prenda.color.toUpperCase()}`);
            console.log('[generarDescripcionSimple]  Color:', prenda.color);
        }
        if (prenda.manga) {
            attrs.push(`<strong>MANGA:</strong> ${prenda.manga.toUpperCase()}`);
            console.log('[generarDescripcionSimple]  Manga:', prenda.manga);
        }
        html += attrs.join(' | ') + '<br>';
    }
    
    // Descripción - Limpiar basura del inicio
    if (prenda.descripcion) {
        console.log('[generarDescripcionSimple] 📝 Descripción RAW:', prenda.descripcion);
        let desc = String(prenda.descripcion);
        // Limpiar líneas de basura del inicio (DSFSDFS, etc)
        desc = desc.split('\n').filter(linea => {
            const trimmed = linea.trim();
            // Saltar líneas basura
            if (!trimmed) return false;
            if (trimmed.match(/^[A-Z]{5,}[A-Z\s]{0,10}$/i) && 
                !trimmed.match(/^(PRENDA|TALLA|TELA|COLOR|MANGA|BOLSILLO|BOTÓN|CREMALLERA|DESCRIPCIÓN|DAMA|HOMBRE)/i)) {
                console.log('[generarDescripcionSimple] 🚫 Línea basura filtrada:', trimmed);
                return false;
            }
            return true;
        }).join('\n');
        
        if (desc.trim()) {
            html += desc + '<br>';
            console.log('[generarDescripcionSimple]  Descripción agregada (después de limpiar)');
        }
    }
    
    console.log('[generarDescripcionSimple] 📄 OUTPUT HTML:', html);
}

// Función para normalizar datos de prenda
function normalizarPrendaData(prendaData) {
    const normalized = { ...prendaData };
    
    // Normalizar campos que puedan ser objetos
    Object.keys(normalized).forEach(key => {
        if (normalized[key] && typeof normalized[key] === 'object' && !Array.isArray(normalized[key])) {
            // Si es un objeto con propiedades comunes, convertir a string
            if (normalized[key].nombre) {
                normalized[key] = normalized[key].nombre;
            } else if (normalized[key].id) {
                normalized[key] = `ID: ${normalized[key].id}`;
            } else {
                normalized[key] = JSON.stringify(normalized[key]);
            }
        }
    });
    
    return normalized;
}

// Función fallback para generar HTML
function generarFallbackHTML(prendaData) {
    return `
        <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 16px;">
            <h3 style="margin: 0 0 12px 0; font-size: 16px; font-weight: 600; color: #111827;">
                ${prendaData.nombre_prenda || 'Prenda sin nombre'}
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; font-size: 14px;">
                ${prendaData.talla ? `<div><strong>Talla:</strong> ${prendaData.talla}</div>` : ''}
                ${prendaData.tela ? `<div><strong>Tela:</strong> ${prendaData.tela}</div>` : ''}
                ${prendaData.color ? `<div><strong>Color:</strong> ${prendaData.color}</div>` : ''}
                ${prendaData.manga ? `<div><strong>Manga:</strong> ${prendaData.manga}</div>` : ''}
                ${prendaData.descripcion ? `<div><strong>Descripción:</strong> ${prendaData.descripcion}</div>` : ''}
            </div>
        </div>
    `;
}

// Función para obtener datos de la prenda asociada al recibo (igual que en registros)
function obtenerDatosPrendaRecibo(reciboId, titulo) {
    console.log(`[obtenerDatosPrendaRecibo] 📌 Obteniendo datos para recibo ID: ${reciboId}`);
    
    // Obtener pedido_produccion_id desde la base de datos usando el recibo_id
    fetch(`/api/recibos/${reciboId}/pedido`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(datos => {
            console.log(`[obtenerDatosPrendaRecibo] 📄 Datos del recibo recibidos:`, datos);
            
            if (!datos.pedido_produccion_id) {
                console.error('No se encontró pedido_produccion_id para el recibo:', reciboId);
                alert('No se pudo identificar el pedido asociado a este recibo');
                return;
            }
            
            const pedidoProduccionId = datos.pedido_produccion_id;
            console.log(`[obtenerDatosPrendaRecibo] 📋 Pedido ID encontrado: ${pedidoProduccionId}`);
            
            // Obtener datos de la prenda del pedido (igual que en registros)
            fetch(`/api/pedidos/${pedidoProduccionId}/prendas`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(datosPrendas => {
                    console.log(`[obtenerDatosPrendaRecibo] 📄 Datos de prendas recibidos:`, datosPrendas);
                    
                    if (datosPrendas.data && typeof datosPrendas.data === 'object') {
                        datosPrendas = datosPrendas.data;
                    }
                    
                    if (!datosPrendas.prendas || !Array.isArray(datosPrendas.prendas) || datosPrendas.prendas.length === 0) {
                        console.warn('No se encontraron prendas para el pedido:', pedidoProduccionId);
                        alert('No se encontraron prendas para este pedido');
                        return;
                    }
                    
                    console.log(`[obtenerDatosPrendaRecibo] ✅ Prendas encontradas: ${datosPrendas.prendas.length}`);
                    
                    // Usar las prendas del pedido (igual que en registros)
                    abrirModalCeldaConFormato(titulo, datosPrendas.prendas);
                })
                .catch(error => {
                    console.error('[obtenerDatosPrendaRecibo] Error al obtener datos de prendas:', error);
                    alert('Error al cargar los datos de la prenda: ' + error.message);
                });
        })
        .catch(error => {
            console.error('[obtenerDatosPrendaRecibo] Error al obtener datos del recibo:', error);
            alert('Error al identificar el pedido asociado: ' + error.message);
        });
}

// Función para abrir modal de novedades específicas de recibo (NUEVO SISTEMA)
function openNovedadesModalRecibo(button) {
    // Obtener datos desde los data attributes del botón
    const pedidoId = button.getAttribute('data-pedido-id');
    const numeroRecibo = button.getAttribute('data-numero-recibo');
    const novedadesActuales = button.getAttribute('data-novedades') || '';
    
    console.log(`[openNovedadesModalRecibo] 📝 Abriendo modal para pedido: ${pedidoId}, recibo: ${numeroRecibo}`);
    console.log(`[openNovedadesModalRecibo] Novedades actuales:`, novedadesActuales);
    
    // Esperar a que el script de novedades esté disponible
    if (typeof abrirModalNovedadesRecibo === 'function') {
        abrirModalNovedadesRecibo(pedidoId, numeroRecibo);
        return;
    }
    
    // Si no está disponible, esperar un poco y reintentar
    setTimeout(() => {
        if (typeof abrirModalNovedadesRecibo === 'function') {
            abrirModalNovedadesRecibo(pedidoId, numeroRecibo);
        } else {
            console.warn('[openNovedadesModalRecibo] Sistema nuevo no disponible, usando fallback');
            // Fallback simple: mostrar alerta con las novedades actuales
            alert(`Novedades del recibo ${numeroRecibo}:\n\n${novedadesActuales || 'Sin novedades'}`);
        }
    }, 100);
}

// Funciones para el menú de acciones
document.addEventListener('DOMContentLoaded', function() {
    console.log('[Menu] Inicializando menú de acciones');
});

// Los event listeners del menú de acciones ahora están manejados por el sistema
// de dropdowns dinámicos (estilo insumos) en recibos-costura.blade.php

// ============================================================
// TIEMPO REAL: Escuchar canal recibos-costura via WebSocket/Reverb
// ============================================================
(function() {
    console.log('[RecibosCostura-RT] Inicializando listener en tiempo real...');

    function suscribirCanal() {
        const echoInstance = window.EchoInstance;
        if (!echoInstance) {
            console.warn('[RecibosCostura-RT] EchoInstance no disponible aún');
            return;
        }

        console.log('[RecibosCostura-RT] Suscribiendo al canal recibos-costura...');

        echoInstance.channel('recibos-costura')
            .listen('.recibo.aprobado', function(data) {
                console.log('[RecibosCostura-RT] Recibo aprobado recibido:', data);
                agregarReciboEnTiempoReal(data);
            });

        console.log('[RecibosCostura-RT] Suscripción activa al canal recibos-costura');
    }

    /**
     * Agregar una nueva fila a la tabla cuando llega un recibo aprobado
     */
    function agregarReciboEnTiempoReal(data) {
        const reciboId = data.recibo_id;

        // Verificar que no exista ya en la tabla
        if (document.querySelector(`tr[data-orden-id="${reciboId}"]`)) {
            console.log('[RecibosCostura-RT] Recibo ya existe en tabla, ignorando:', reciboId);
            return;
        }

        // Obtener datos completos del recibo vía API
        fetch(`/recibos-costura/recibo/${reciboId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(result => {
            if (!result.success || !result.recibo) {
                console.warn('[RecibosCostura-RT] No se pudo obtener datos del recibo:', reciboId);
                return;
            }

            const recibo = result.recibo;
            console.log('[RecibosCostura-RT] Datos completos del recibo:', recibo);

            insertarFilaRecibo(recibo);
            mostrarNotificacionNuevoRecibo(recibo);
        })
        .catch(error => {
            console.error('[RecibosCostura-RT] Error al obtener datos del recibo:', error);
        });
    }

    /**
     * Insertar una nueva fila en la tabla de recibos
     */
    function insertarFilaRecibo(recibo) {
        const tbody = document.getElementById('tablaRecibosBody');
        if (!tbody) {
            console.warn('[RecibosCostura-RT] No se encontró tablaRecibosBody');
            return;
        }

        // Remover fila de "No se encontraron recibos" si existe
        const filaVacia = tbody.querySelector('td[colspan]');
        if (filaVacia) {
            filaVacia.closest('tr').remove();
        }

        // Determinar clases de días
        const dias = recibo.dias_calculados || 0;
        let diasClase = '';
        if (dias >= 14) diasClase = 'dias-mayor-15';
        else if (dias >= 10) diasClase = 'dias-10-15';
        else if (dias >= 5) diasClase = 'dias-5-9';
        else if (dias > 0) diasClase = 'dias-0-4';

        // Badge de estado
        let estadoBadge = 'bg-secondary';
        let estadoTexto = recibo.estado;
        if (recibo.estado === 'En Ejecución') estadoBadge = 'bg-primary';
        else if (recibo.estado === 'No iniciado') estadoBadge = 'bg-warning';
        else if (recibo.estado === 'PENDIENTE_INSUMOS') { estadoBadge = 'bg-info'; estadoTexto = 'Pendiente Insumos'; }

        // Badge de area
        let areaBadge = 'bg-secondary';
        if (recibo.area === 'Corte') areaBadge = 'bg-success';
        else if (recibo.area === 'Insumos') areaBadge = 'bg-info';

        // Badge de días
        let diasBadge = 'bg-secondary';
        if (dias >= 14) diasBadge = 'bg-danger';
        else if (dias >= 5) diasBadge = 'bg-warning';
        else if (dias > 0) diasBadge = 'bg-success';

        const tr = document.createElement('tr');
        tr.className = diasClase;
        tr.setAttribute('data-orden-id', recibo.id);
        tr.setAttribute('data-pedido-id', recibo.pedido_produccion_id || '');
        tr.setAttribute('data-numero-recibo', recibo.consecutivo_actual || '');

        tr.innerHTML = `
            <td class="acciones-column" style="text-align: center; position: relative;">
                <button class="btn-ver-dropdown" title="Ver Opciones"
                    data-menu-id="menu-recibo-${recibo.id}"
                    data-pedido-id="${recibo.pedido_produccion_id}"
                    data-prenda-id="${recibo.prenda_id || ''}">
                    <i class="fas fa-eye"></i>
                </button>
            </td>
            <td><span class="badge ${estadoBadge}">${estadoTexto}</span></td>
            <td><span class="badge ${areaBadge}">${recibo.area || 'Insumos'}</span></td>
            <td style="text-align: center;">
                <span class="badge ${diasBadge}" style="font-weight: 600;">${dias} días</span>
            </td>
            <td style="text-align: center;">
                <span style="font-weight: 600;">${recibo.consecutivo_actual}</span>
            </td>
            <td style="text-align: center;">
                <span>${recibo.cliente || 'N/A'}</span>
            </td>
            <td>
                <div class="table-cell" style="flex: 10;">
                    <div class="cell-content" style="justify-content: flex-start; cursor: pointer;">
                        <span class="descripcion-prenda-texto" style="color: #6b7280; font-size: 0.875rem; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            ${recibo.nombre_prenda || 'Sin prendas'} <span style="color: #3b82f6; font-weight: 600;">...</span>
                        </span>
                    </div>
                </div>
            </td>
            <td><span class="text-muted">-</span></td>
            <td>
                <div class="table-cell" style="flex: 0 0 120px;">
                    <div class="cell-content" style="justify-content: flex-start;">
                        <button class="btn-edit-novedades"
                            data-pedido-id="${recibo.pedido_produccion_id}"
                            data-numero-recibo="${recibo.consecutivo_actual}"
                            data-novedades=""
                            onclick="event.stopPropagation(); openNovedadesModalRecibo(this)"
                            title="Ver novedades del recibo" type="button">
                            <span class="novedades-text empty">Sin novedades</span>
                            <span class="material-symbols-rounded">edit</span>
                        </button>
                    </div>
                </div>
            </td>
            <td><span>${recibo.fecha_creacion || '-'}</span></td>
            <td><span class="fecha-estimada-span text-muted">-</span></td>
            <td><span class="text-muted">-</span></td>
        `;

        // Insertar al inicio de la tabla (los más recientes primero)
        tbody.insertBefore(tr, tbody.firstChild);

        // Animación de entrada
        tr.style.transition = 'background-color 1.5s ease';
        tr.style.backgroundColor = '#d4edda';
        setTimeout(() => { tr.style.backgroundColor = ''; }, 2000);

        console.log('[RecibosCostura-RT] Fila insertada para recibo:', recibo.consecutivo_actual);
    }

    /**
     * Mostrar notificación visual de nuevo recibo
     */
    function mostrarNotificacionNuevoRecibo(recibo) {
        // Crear notificación toast
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed; top: 20px; right: 20px; z-index: 99999;
            background: #28a745; color: white; padding: 12px 20px;
            border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            font-size: 14px; font-weight: 500; max-width: 400px;
            animation: slideInRight 0.3s ease-out;
        `;
        toast.innerHTML = `
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-check-circle" style="font-size: 18px;"></i>
                <div>
                    <div style="font-weight: 600;">Nuevo recibo aprobado</div>
                    <div style="font-size: 12px; opacity: 0.9;">Recibo #${recibo.consecutivo_actual} - ${recibo.cliente || ''}</div>
                </div>
            </div>
        `;
        document.body.appendChild(toast);

        // Remover después de 4 segundos
        setTimeout(() => {
            toast.style.transition = 'opacity 0.5s ease';
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 500);
        }, 4000);
    }

    // Suscribir usando waitForEcho o directamente si ya está listo
    if (typeof window.waitForEcho === 'function') {
        window.waitForEcho(suscribirCanal);
    } else {
        // Fallback: esperar a que EchoInstance esté disponible
        const checkInterval = setInterval(() => {
            if (window.EchoInstance) {
                clearInterval(checkInterval);
                suscribirCanal();
            }
        }, 500);
        // Dejar de intentar después de 30 segundos
        setTimeout(() => clearInterval(checkInterval), 30000);
    }
})();
</script>
@endpush
