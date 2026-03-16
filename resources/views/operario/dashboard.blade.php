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
<div class="operario-dashboard {{ auth()->user()->hasRole('vista-costura') ? 'is-vista-costura' : '' }}">
    <!-- Usuario Logueado en Variable Global -->
    <script>
        window.USUARIO_ACTUAL = {
            id: {{ Auth::id() }},
            rol: '{{ Auth::user()->roles->first()->name ?? '' }}',
            nombre: '{{ Auth::user()->name ?? '' }}'
        };

    // Tabs para administrador-costura (sin recargar)
    (function() {
        function setActiveAdminTab(tab) {
            document.querySelectorAll('[data-admin-tab]').forEach(btn => {
                btn.classList.toggle('badge-filtro-active', btn.dataset.adminTab === tab);
            });
        }

        async function cargarAdminTab(tab) {
            try {
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tab);

                const resp = await fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (!resp.ok) {
                    throw new Error('HTTP ' + resp.status);
                }

                const html = await resp.text();
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const nuevoOrdenesList = doc.getElementById('ordenesList');
                const actualOrdenesList = document.getElementById('ordenesList');

                if (!nuevoOrdenesList || !actualOrdenesList) {
                    throw new Error('No se encontró #ordenesList');
                }

                actualOrdenesList.innerHTML = nuevoOrdenesList.innerHTML;

                // Re-inicializar búsqueda (si existe)
                if (typeof window.__initDashboardSearch === 'function') {
                    window.__initDashboardSearch();
                }

                // Actualizar URL sin recargar
                window.history.pushState({ tab }, '', url.toString());

                setActiveAdminTab(tab);
            } catch (e) {
                console.warn('[Operario Dashboard] Falló cargar tab admin, recargando página', e);
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tab);
                window.location.href = url.toString();
            }
        }

        function initAdminTabs() {
            const botones = document.querySelectorAll('[data-admin-tab]');
            if (!botones.length) {
                return;
            }

            botones.forEach(btn => {
                btn.addEventListener('click', function() {
                    const tab = btn.dataset.adminTab;
                    if (!tab) return;
                    cargarAdminTab(tab);
                });
            });

            window.addEventListener('popstate', function() {
                const tab = new URL(window.location.href).searchParams.get('tab') || 'costura';
                setActiveAdminTab(tab);
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initAdminTabs);
        } else {
            initAdminTabs();
        }
    })();

        // Tiempo real: escuchar cuando se asignen recibos/procesos al operario
        (function () {
            let intentos = 0;
            const maxIntentos = 100;

            async function actualizarListaSinRecargar() {
                try {
                    const url = window.location.href;
                    const resp = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    if (!resp.ok) {
                        throw new Error('HTTP ' + resp.status);
                    }

                    const html = await resp.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');

                    const nuevoOrdenesList = doc.getElementById('ordenesList');
                    const actualOrdenesList = document.getElementById('ordenesList');
                    if (!nuevoOrdenesList || !actualOrdenesList) {
                        throw new Error('No se encontró #ordenesList en HTML');
                    }

                    actualOrdenesList.innerHTML = nuevoOrdenesList.innerHTML;

                    const nuevoCount = doc.querySelector('.ordenes-count');
                    const actualCount = document.querySelector('.ordenes-count');
                    if (nuevoCount && actualCount) {
                        actualCount.textContent = nuevoCount.textContent;
                    }

                    if (typeof window.__initDashboardSearch === 'function') {
                        window.__initDashboardSearch();
                    }

                    if (typeof window.filtrarPrendasPorRecibo === 'function') {
                        const badgeActivo = document.querySelector('.badge-filtro-active');
                        const filtroActivo = badgeActivo ? badgeActivo.dataset.filtro : null;
                        if (filtroActivo) {
                            window.filtrarPrendasPorRecibo(filtroActivo);
                        }
                    }

                    console.log('[Operario Dashboard] Lista actualizada sin recargar');
                } catch (e) {
                    console.warn('[Operario Dashboard] Falló actualizar lista sin recargar, recargando página', e);
                    window.location.reload();
                }
            }

            function initRealtimeListeners() {
                try {
                    intentos += 1;

                    if (!window.USUARIO_ACTUAL?.id) {
                        if (intentos < maxIntentos) {
                            return setTimeout(initRealtimeListeners, 200);
                        }
                        console.warn('[Operario Dashboard] No se pudo iniciar realtime: USUARIO_ACTUAL no disponible');
                        return;
                    }

                    if (!window.EchoInstance) {
                        if (intentos < maxIntentos) {
                            return setTimeout(initRealtimeListeners, 200);
                        }
                        console.warn('[Operario Dashboard] No se pudo iniciar realtime: EchoInstance no disponible');
                        return;
                    }

                    console.log('[Operario Dashboard] Inicializando listeners Echo', {
                        usuario: window.USUARIO_ACTUAL,
                        privateChannel: `private-App.Models.User.${window.USUARIO_ACTUAL.id}`,
                        publicChannel: 'operarios.corte',
                    });

                    window.EchoInstance.private(`App.Models.User.${window.USUARIO_ACTUAL.id}`)
                        .subscribed(() => {
                            console.log('[Operario Dashboard] Suscrito OK a canal privado', `App.Models.User.${window.USUARIO_ACTUAL.id}`);
                        })
                        .error((err) => {
                            console.error('[Operario Dashboard] Error suscribiendo canal privado', err);
                        })
                        .listen('.operario.recibos.actualizados', (e) => {
                            console.log('[Operario Dashboard] Evento operario.recibos.actualizados recibido (privado):', e);
                            actualizarListaSinRecargar();
                        });

                    // Fallback público: evento de asignación de corte (compara por nombre)
                    window.EchoInstance.channel('operarios.corte')
                        .subscribed(() => {
                            console.log('[Operario Dashboard] Suscrito OK a canal público', 'operarios.corte');
                        })
                        .error((err) => {
                            console.error('[Operario Dashboard] Error suscribiendo canal público operarios.corte', err);
                        })
                        .listen('.corte.asignado', (e) => {
                            const encargadoEvento = String(e?.encargado || '').trim().toLowerCase();
                            const nombreActual = String(window.USUARIO_ACTUAL?.nombre || '').trim().toLowerCase();

                            console.log('[Operario Dashboard] Evento corte.asignado recibido:', e);
                            console.log('[Operario Dashboard] Comparando encargado vs usuario:', {
                                encargadoEvento,
                                nombreActual,
                            });

                            if (encargadoEvento && nombreActual && encargadoEvento === nombreActual) {
                                console.log('[Operario Dashboard] Coincide encargado con usuario, actualizando lista sin recargar');
                                actualizarListaSinRecargar();
                            }
                        })
                        // Nuevo: Escuchar cuando se asigna encargado a costura para eliminar recibo de vista del cortador
                        .listen('.encargado.costura.asignado', (e) => {
                            console.log('[Operario Dashboard] Encargado de costura asignado, eliminando recibo de vista del cortador:', e);
                            
                            // Para cortadores: eliminar el recibo que ya tiene encargado de costura
                            if (String(window.USUARIO_ACTUAL?.rol || '').toLowerCase() === 'cortador') {
                                const prendaId = e?.prenda_id;
                                const pedidoId = e?.pedido_id;
                                
                                if (prendaId) {
                                    // Mostrar notificación al cortador
                                    if (window.NotificacionesPush && typeof window.NotificacionesPush.add === 'function') {
                                        const mensaje = e?.mensaje || `El recibo #${e?.numero_recibo} ha sido asignado a ${e?.encargado} para costura`;
                                        window.NotificacionesPush.add({
                                            title: 'Recibo asignado a costura',
                                            message: mensaje,
                                            type: 'info',
                                            icon: 'checkroom',
                                            duration: 6000
                                        });
                                        console.log('[Operario Dashboard] ✅ Notificación push mostrada al cortador');
                                    } else {
                                        // Fallback: Notificación nativa del navegador
                                        console.log('[Operario Dashboard] Cortador usando notificación nativa...');
                                        if ('Notification' in window && Notification.permission === 'granted') {
                                            new Notification('Recibo asignado a costura', {
                                                body: e?.mensaje || `El recibo #${e?.numero_recibo} ha sido asignado a ${e?.encargado} para costura`,
                                                icon: '/icon.png'
                                            });
                                            console.log('[Operario Dashboard] ✅ Notificación nativa mostrada al cortador');
                                        } else if ('Notification' in window && Notification.permission !== 'denied') {
                                            Notification.requestPermission().then(permission => {
                                                if (permission === 'granted') {
                                                    new Notification('Recibo asignado a costura', {
                                                        body: e?.mensaje || `El recibo #${e?.numero_recibo} ha sido asignado a ${e?.encargado} para costura`,
                                                        icon: '/icon.png'
                                                    });
                                                }
                                            });
                                        } else {
                                            alert(`Recibo asignado: ${e?.mensaje || `El recibo #${e?.numero_recibo} ha sido asignado a ${e?.encargado} para costura`}`);
                                        }
                                    }
                                    
                                    // Buscar y eliminar la tarjeta del recibo
                                    const card = document.querySelector(`.orden-card-simple[data-prenda-id="${prendaId}"]`);
                                    if (card) {
                                        console.log(`[Operario Dashboard] Eliminando tarjeta prenda_id: ${prendaId}`);
                                        card.remove();
                                        
                                        // Actualizar contador de tarjetas
                                        actualizarContadorTarjetas();
                                        console.log('[Operario Dashboard] ✅ Tarjeta eliminada y contador actualizado');
                                    } else {
                                        console.warn(`[Operario Dashboard] No se encontró tarjeta con prenda_id: ${prendaId}`);
                                    }
                                }
                            } else {
                                console.log('[Operario Dashboard] No es cortador, no elimina tarjeta');
                            }

                            // Para administrador-costura: refrescar lista sin recargar
                            if (String(window.USUARIO_ACTUAL?.rol || '').toLowerCase() === 'administrador-costura') {
                                console.log('[Operario Dashboard] Admin-costura recibió encargado.costura.asignado (operarios.corte), refrescando lista');

                                // Mostrar notificación (solo recibos que llegan a costureros / módulos)
                                try {
                                    window.__adminCosturaNotifsSeen = window.__adminCosturaNotifsSeen || {};
                                    const encargadoStr = String(e?.encargado || '').trim();
                                    const encargadoLower = encargadoStr.toLowerCase();
                                    const pareceModuloOCosturero = encargadoLower.includes('modulo') || encargadoLower.includes('módulo') || encargadoLower.includes('costur') || encargadoLower.includes('sobremedida');
                                    if (pareceModuloOCosturero) {
                                        const notifKey = `${e?.proceso_id || ''}|${e?.proceso_updated_at || ''}|${e?.pedido_id || ''}|${e?.numero_recibo || ''}`;
                                        if (window.__adminCosturaNotifsSeen[notifKey]) {
                                            console.log('[Operario Dashboard] Admin-costura: notificación duplicada ignorada', notifKey);
                                        } else {
                                            window.__adminCosturaNotifsSeen[notifKey] = Date.now();
                                        const cliente = String(e?.cliente || '').trim();
                                        const dtRaw = e?.proceso_updated_at;
                                        let fecha = '';
                                        try {
                                            if (dtRaw) {
                                                const d = new Date(dtRaw);
                                                if (!isNaN(d.getTime())) {
                                                    const dd = String(d.getDate()).padStart(2, '0');
                                                    const mm = String(d.getMonth() + 1).padStart(2, '0');
                                                    const yyyy = d.getFullYear();
                                                    const hh = String(d.getHours()).padStart(2, '0');
                                                    const mi = String(d.getMinutes()).padStart(2, '0');
                                                    fecha = `${dd}/${mm}/${yyyy} ${hh}:${mi}`;
                                                }
                                            }
                                        } catch (e) {
                                            fecha = '';
                                        }

                                        const mensaje = `Pedido #${e?.pedido_id || ''}${cliente ? ` · ${cliente}` : ''} · Recibo #${e?.numero_recibo || ''} asignado a ${encargadoStr}`;
                                        if (window.NotificacionesPush && typeof window.NotificacionesPush.add === 'function') {
                                            window.NotificacionesPush.add({
                                                id: e?.consecutivo_recibo_id || undefined,
                                                title: 'Recibo asignado a costura',
                                                message: mensaje,
                                                fecha: fecha,
                                                type: 'info',
                                                icon: 'checkroom',
                                                duration: 8000
                                            });
                                        } else if ('Notification' in window) {
                                            if (Notification.permission === 'granted') {
                                                new Notification('Recibo asignado a costura', {
                                                    body: mensaje,
                                                    icon: '/icon.png'
                                                });
                                            } else if (Notification.permission !== 'denied') {
                                                Notification.requestPermission();
                                            }
                                        }
                                        }
                                    } else {
                                        console.log('[Operario Dashboard] Admin-costura: asignación no parece a módulo/costurero, no notifica');
                                    }
                                } catch (err) {
                                    console.warn('[Operario Dashboard] Admin-costura: error mostrando notificación', err);
                                }

                                actualizarListaSinRecargar();
                            }
                        });

                    // Vista-costura: escuchar cuando insumos aprueba/envía el recibo a producción (área Corte)
                    // Evento broadcast: App\Events\ReciboAprobado -> channel('recibos-costura') -> 'recibo.aprobado'
                    if (String(window.USUARIO_ACTUAL?.rol || '').toLowerCase() === 'vista-costura') {
                        window.EchoInstance.channel('recibos-costura')
                            .subscribed(() => {
                                console.log('[Operario Dashboard] Suscrito OK a canal público', 'recibos-costura');
                            })
                            .error((err) => {
                                console.error('[Operario Dashboard] Error suscribiendo canal público recibos-costura', err);
                            })
                            .listen('.recibo.aprobado', (e) => {
                                console.log('[Operario Dashboard] Evento recibo.aprobado recibido:', e);
                                // Vista-costura ya no muestra recibos en área Corte; no refrescar aquí.
                            })
                            .listen('.recibo.completado', (e) => {
                                console.log('[Operario Dashboard] Evento recibo.completado recibido:', e);
                                try {
                                    if (window.NotificacionesPush && typeof window.NotificacionesPush.add === 'function') {
                                        const area = String(e?.area || '').trim();
                                        const consecutivo = e?.consecutivo ? `#${e.consecutivo}` : '';
                                        const operario = String(e?.nombre_operario || '').trim();
                                        let titulo = 'Recibo completado';
                                        let detalle = `Recibo ${consecutivo}`.trim();
                                        
                                        if (area && operario) {
                                            titulo += ` en ${area}`;
                                            detalle += ` por ${operario}`;
                                        }
                                        
                                        window.NotificacionesPush.add({
                                            title: titulo,
                                            message: detalle,
                                            type: 'success',
                                            icon: 'check_circle',
                                            duration: 5000
                                        });
                                    }
                                } catch (err) {
                                    console.warn('[Operario Dashboard] Error creando notificación push', err);
                                }
                                actualizarListaSinRecargar();
                            });
                    }

                    // Administrador-costura: refrescar lista en tiempo real cuando se asigna/deshace costura
                    if (String(window.USUARIO_ACTUAL?.rol || '').toLowerCase() === 'administrador-costura') {
                        window.EchoInstance.channel('recibos-costura')
                            .subscribed(() => {
                                console.log('[Operario Dashboard] Admin-costura suscrito OK a canal público', 'recibos-costura');
                            })
                            .error((err) => {
                                console.error('[Operario Dashboard] Error suscribiendo admin-costura a canal público recibos-costura', err);
                            })
                            .listen('.encargado.costura.asignado', (e) => {
                                console.log('[Operario Dashboard] Admin-costura evento encargado.costura.asignado, refrescando lista:', e);
                                actualizarListaSinRecargar();
                            })
                            .listen('.encargado.costura.deshacer', (e) => {
                                console.log('[Operario Dashboard] Admin-costura evento encargado.costura.deshacer, refrescando lista:', e);
                                actualizarListaSinRecargar();
                            })
                            .listen('.operario.recibos.actualizados', (e) => {
                                console.log('[Operario Dashboard] Admin-costura evento operario.recibos.actualizados (canal público), refrescando lista:', e);
                                actualizarListaSinRecargar();
                            });
                    }

                    // Costurero: escuchar cuando se le asigna un recibo
                    if (String(window.USUARIO_ACTUAL?.rol || '').toLowerCase() === 'costurero') {
                        const nombreCosturero = String(window.USUARIO_ACTUAL?.nombre || '').trim();
                        if (nombreCosturero) {
                            // Normalizar el nombre igual que en PHP
                            const nombreNormalizado = nombreCosturero.toLowerCase().replace(/[^a-zA-Z0-9]/g, '_');
                            
                            console.log(`[Operario Dashboard] Costurero ${nombreCosturero} (normalizado: ${nombreNormalizado}) suscribiendo a canales`);
                            
                            // Suscribir al canal público para recibir eventos de Control de Calidad
                            window.EchoInstance.channel('recibos-costura')
                                .subscribed(() => {
                                    console.log(`[Operario Dashboard] Costurero ${nombreCosturero} suscrito OK a canal público recibos-costura`);
                                })
                                .error((err) => {
                                    console.error(`[Operario Dashboard] Error suscribiendo costurero ${nombreCosturero} a canal público:`, err);
                                })
                                .listen('.recibo.pasado.control.calidad', (e) => {
                                    console.log('[Operario Dashboard] 🚫 Recibo pasado a Control Calidad - eliminar de vista:', e);
                                    
                                    // Eliminar la tarjeta del recibo de la vista del costurero
                                    const prendaId = e?.prenda_id;
                                    if (prendaId) {
                                        const tarjeta = document.querySelector(`.orden-card-simple[data-prenda-id="${prendaId}"]`);
                                        if (tarjeta) {
                                            console.log(`[Operario Dashboard] Eliminando tarjeta con prenda_id: ${prendaId}`);
                                            
                                            // Animación de desvanecimiento antes de eliminar
                                            tarjeta.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
                                            tarjeta.style.opacity = '0';
                                            tarjeta.style.transform = 'translateX(-100%)';
                                            
                                            setTimeout(() => {
                                                tarjeta.remove();
                                                console.log('[Operario Dashboard] ✅ Tarjeta eliminada de la vista');
                                                
                                                // Mostrar notificación al costurero
                                                if (window.NotificacionesPush && typeof window.NotificacionesPush.add === 'function') {
                                                    window.NotificacionesPush.add({
                                                        title: 'Recibo movido a Control de Calidad',
                                                        message: e?.mensaje || `El recibo #${e?.numero_recibo} ya no está disponible en costura`,
                                                        type: 'info',
                                                        duration: 5000
                                                    });
                                                } else {
                                                    // Fallback: Notificación nativa
                                                    if ('Notification' in window && Notification.permission === 'granted') {
                                                        new Notification('Recibo movido a Control de Calidad', {
                                                            body: e?.mensaje || `El recibo #${e?.numero_recibo} (${e?.nombre_prenda}) ha pasado a Control de Calidad`,
                                                            icon: '/icon.png'
                                                        });
                                                    }
                                                }
                                            }, 500);
                                        } else {
                                            console.warn(`[Operario Dashboard] No se encontró tarjeta con prenda_id: ${prendaId}`);
                                        }
                                    }
                                });
                            
                            // Suscribir al canal privado para recibir asignaciones específicas
                            window.EchoInstance.private(`costurero.${nombreNormalizado}`)
                                .subscribed(() => {
                                    console.log(`[Operario Dashboard] Costurero ${nombreCosturero} suscrito OK a canal privado`);
                                })
                                .error((err) => {
                                    console.error(`[Operario Dashboard] Error suscribiendo costurero ${nombreCosturero}:`, err);
                                })
                                .listen('.recibo.asignado', (e) => {
                                    console.log('[Operario Dashboard] ✅ Recibo asignado a costurero RECIBIDO:', e);
                                    console.log('[Operario Dashboard] Datos del recibo:', {
                                        pedido_id: e?.pedido_id,
                                        prenda_id: e?.prenda_id,
                                        numero_recibo: e?.numero_recibo,
                                        nombre_prenda: e?.nombre_prenda,
                                        encargado: e?.encargado,
                                        mensaje: e?.mensaje
                                    });
                                    
                                    // Mostrar notificación
                                    if (window.NotificacionesPush && typeof window.NotificacionesPush.add === 'function') {
                                        console.log('[Operario Dashboard] Mostrando notificación push...');
                                        window.NotificacionesPush.add({
                                            title: 'Nuevo recibo de costura asignado',
                                            message: e?.mensaje || `Tienes un nuevo recibo: ${e?.nombre_prenda} #${e?.numero_recibo}`,
                                            type: 'info',
                                            icon: 'checkroom',
                                            duration: 8000
                                        });
                                        console.log('[Operario Dashboard] ✅ Notificación push mostrada');
                                    } else {
                                        // Fallback: Notificación nativa del navegador
                                        console.log('[Operario Dashboard] Usando notificación nativa del navegador...');
                                        if ('Notification' in window && Notification.permission === 'granted') {
                                            new Notification('Nuevo recibo de costura asignado', {
                                                body: e?.mensaje || `Tienes un nuevo recibo: ${e?.nombre_prenda} #${e?.numero_recibo}`,
                                                icon: '/icon.png', // Puedes ajustar el ícono
                                                badge: '/icon.png'
                                            });
                                            console.log('[Operario Dashboard] ✅ Notificación nativa mostrada');
                                        } else if ('Notification' in window && Notification.permission !== 'denied') {
                                            // Pedir permiso para notificaciones
                                            Notification.requestPermission().then(permission => {
                                                if (permission === 'granted') {
                                                    new Notification('Nuevo recibo de costura asignado', {
                                                        body: e?.mensaje || `Tienes un nuevo recibo: ${e?.nombre_prenda} #${e?.numero_recibo}`,
                                                        icon: '/icon.png'
                                                    });
                                                    console.log('[Operario Dashboard] ✅ Notificación nativa mostrada (permiso concedido)');
                                                }
                                            });
                                        } else {
                                            // Fallback final: Alert simple
                                            console.log('[Operario Dashboard] Mostrando alert simple como fallback');
                                            alert(`Nuevo recibo asignado: ${e?.mensaje || `Tienes un nuevo recibo: ${e?.nombre_prenda} #${e?.numero_recibo}`}`);
                                        }
                                    }
                                    
                                    // Actualizar la lista sin recargar para mostrar el nuevo recibo
                                    console.log('[Operario Dashboard] Actualizando lista sin recargar...');
                                    actualizarListaSinRecargar();
                                    console.log('[Operario Dashboard] ✅ Lista actualizada');
                                });
                        }
                    }

                    // Costura-reflectivo: escuchar cuando un recibo REFLECTIVO pase a Control de Calidad
                    if (String(window.USUARIO_ACTUAL?.rol || '').toLowerCase() === 'costura-reflectivo') {
                        console.log('[Operario Dashboard] Costura-reflectivo suscribiendo a canal recibos-costura para escuchar cambios de área');
                        
                        window.EchoInstance.channel('recibos-costura')
                            .subscribed(() => {
                                console.log('[Operario Dashboard] Costura-reflectivo suscrito OK a canal público recibos-costura');
                            })
                            .error((err) => {
                                console.error('[Operario Dashboard] Error suscribiendo costura-reflectivo a canal público:', err);
                            })
                            .listen('.recibo.pasado.control.calidad', (e) => {
                                console.log('[Operario Dashboard] Costura-reflectivo: Recibo pasado a Control Calidad:', e);
                                
                                // Solo procesar si es un recibo REFLECTIVO
                                const tipoRecibo = String(e?.tipo_recibo || '').toUpperCase();
                                if (tipoRecibo !== 'REFLECTIVO') {
                                    console.log('[Operario Dashboard] Costura-reflectivo: No es REFLECTIVO, ignorando');
                                    return;
                                }
                                
                                const prendaId = e?.prenda_id;
                                if (prendaId) {
                                    const tarjeta = document.querySelector(`.orden-card-simple[data-prenda-id="${prendaId}"]`);
                                    if (tarjeta) {
                                        console.log(`[Operario Dashboard] Costura-reflectivo: Eliminando tarjeta REFLECTIVO con prenda_id: ${prendaId}`);
                                        
                                        // Animación de desvanecimiento
                                        tarjeta.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
                                        tarjeta.style.opacity = '0';
                                        tarjeta.style.transform = 'translateX(-100%)';
                                        
                                        setTimeout(() => {
                                            tarjeta.remove();
                                            console.log('[Operario Dashboard] ✅ Tarjeta REFLECTIVO eliminada de vista costura-reflectivo');
                                            
                                            // Notificación
                                            if (window.NotificacionesPush && typeof window.NotificacionesPush.add === 'function') {
                                                window.NotificacionesPush.add({
                                                    title: 'Recibo REFLECTIVO movido',
                                                    message: e?.mensaje || `El recibo #${e?.numero_recibo} pasó a Control de Calidad`,
                                                    type: 'info',
                                                    icon: 'auto_awesome',
                                                    duration: 5000
                                                });
                                            }
                                        }, 500);
                                    }
                                }
                            });
                    }
                } catch (e) {
                    console.error('[Operario Dashboard] Error initRealtimeListeners', e);
                }
            }

            initRealtimeListeners();
        })();
    </script>
    <!-- Búsqueda -->
    <div class="search-section">
        <span class="material-symbols-rounded">search</span>
        <input type="text" id="searchInput" class="search-box" placeholder="Buscar por # Recibo, Prenda o Cliente...">
        <button id="clearFilterBtn" class="clear-filter-btn" title="Limpiar filtro" style="display: none;">
            <span class="material-symbols-rounded">close</span>
        </button>
    </div>

    <!-- Mis Prendas Section -->
    <div class="ordenes-section">
        <div class="section-title">
            <span class="material-symbols-rounded">checkroom</span>
            <h3>RECIBOS DE COSTURA</h3>
            <span class="ordenes-count">{{ count($prendasConRecibos ?? []) }}</span>
        </div>

        <!-- Filtros por tipo de recibo para costura-reflectivo y vista-costura -->
        @if(auth()->user()->hasRole('costura-reflectivo') || auth()->user()->hasRole('vista-costura'))
        <div class="filtros-badges">
            @if(auth()->user()->hasRole('vista-costura'))
                <button class="badge-filtro badge-filtro-active" data-filtro="costura" onclick="filtrarPrendasPorRecibo('costura')">
                    <span class="material-symbols-rounded">checkroom</span>
                    Costura
                </button>
                <button class="badge-filtro" data-filtro="reflectivo" onclick="filtrarPrendasPorRecibo('reflectivo')">
                    <span class="material-symbols-rounded">auto_awesome</span>
                    Reflectivo
                </button>
            @else
                <!-- Para costura-reflectivo: mostrar ambos tags -->
                <button class="badge-filtro badge-filtro-active" data-filtro="costura" onclick="filtrarPrendasPorRecibo('costura')">
                    <span class="material-symbols-rounded">checkroom</span>
                    Costura
                </button>
                <button class="badge-filtro" data-filtro="reflectivo" onclick="filtrarPrendasPorRecibo('reflectivo')">
                    <span class="material-symbols-rounded">auto_awesome</span>
                    Reflectivo
                </button>
            @endif
        </div>
        @endif

        @if(auth()->user()->hasRole('administrador-costura'))
        <div class="filtros-badges">
            <button type="button" class="badge-filtro {{ ($tab ?? 'costura') === 'costura' ? 'badge-filtro-active' : '' }}" data-admin-tab="costura">
                <span class="material-symbols-rounded">checkroom</span>
                Costura
            </button>
            <button type="button" class="badge-filtro {{ ($tab ?? 'costura') === 'sobremedida' ? 'badge-filtro-active' : '' }}" data-admin-tab="sobremedida">
                <span class="material-symbols-rounded">straighten</span>
                Sobremedida
            </button>
        </div>
        @endif

        <div class="ordenes-list" id="ordenesList">
            @if(count($prendasConRecibos ?? []) > 0)
                @foreach($prendasConRecibos as $prenda)
                    @php
                        $estadoClass = 'pendiente'; // Siempre pendiente, eliminar en-proceso
                        // Determinar tipo de recibo para filtro
                        // Para vista-costura y costura-reflectivo: una prenda puede tener ambos tipos de recibos
                        // Para otros roles: solo muestra reflectivos
                        $tiposRecibos = array_map(function($r) { return strtoupper($r['tipo_recibo']); }, $prenda['recibos']);
                        $tieneReflectivo = in_array('REFLECTIVO', $tiposRecibos);
                        $tieneCostura = in_array('COSTURA', $tiposRecibos) || in_array('COSTURA-BODEGA', $tiposRecibos);
                        
                        // Obtener el área del recibo principal para filtros
                        $reciboPrincipalFiltro = $prenda['recibos'][0] ?? null;
                        $areaReciboFiltro = strtolower(trim((string) ($reciboPrincipalFiltro['area'] ?? '')));
                        
                        // Para vista-costura y costura-reflectivo, guardar ambos tipos en el atributo data
                        if (auth()->user()->hasRole('vista-costura') || auth()->user()->hasRole('costura-reflectivo')) {
                            // Guardar tipos separados por comas para poder filtrar correctamente
                            $tiposParaFiltro = [];
                            if ($tieneCostura) $tiposParaFiltro[] = 'costura';
                            if ($tieneReflectivo) $tiposParaFiltro[] = 'reflectivo';
                            $esReflectivo = implode(',', $tiposParaFiltro); // "costura,reflectivo" o "costura" o "reflectivo"
                        } else {
                            // Para otros roles, solo mostrar reflectivos
                            $esReflectivo = $tieneReflectivo ? 'reflectivo' : 'costura';
                        }
                        
                        // Por defecto:
                        // - costura-reflectivo: mostrar COSTURA por defecto (pero incluir las que tienen ambos)
                        // - vista-costura: mostrar COSTURA por defecto (pero incluir las que tienen ambos)
                        // - costurero: mostrar COSTURA por defecto
                        // - cortador: mostrar prendas con área "Corte" (independientemente del tipo de recibo)
                        $displayInicial = '';
                        if (auth()->user()->hasRole('costura-reflectivo') || auth()->user()->hasRole('vista-costura') || auth()->user()->hasRole('costurero') || auth()->user()->hasRole('administrador-costura')) {
                            // Mostrar por defecto las que tienen costura (incluyendo las que tienen ambos)
                            $displayInicial = $tieneCostura ? '' : 'none';
                        } elseif (auth()->user()->hasRole('cortador')) {
                            // Para cortadores: mostrar las que tienen área "Corte"
                            $displayInicial = $areaReciboFiltro === 'corte' ? '' : 'none';
                        } else {
                            $displayInicial = $tieneReflectivo ? '' : 'none';
                        }
                    @endphp

                    @if(auth()->user()->hasRole('vista-costura') && $areaReciboFiltro === 'corte')
                        @continue
                    @endif

                    @php
                        // Definir variables necesarias para el card
                        $reciboPrincipalCard = $prenda['recibos'][0] ?? null;
                        $reciboCompletadoCostura = (bool) ($reciboPrincipalCard['completado_costura'] ?? false);
                    @endphp

                    <div class="orden-card-simple {{ ((auth()->user()->hasRole('costurero') || auth()->user()->hasRole('costura-reflectivo') || auth()->user()->hasRole('administrador-costura')) && $reciboCompletadoCostura) ? 'card-completado-costura' : '' }} {{ $tieneReflectivo ? 'borde-reflectivo' : '' }}" 
                         data-numero="{{ $prenda['numero_pedido'] }}" 
                         data-prenda="{{ strtolower($prenda['nombre_prenda']) }}"
                         data-prenda-id="{{ $prenda['prenda_id'] }}"
                         data-cliente="{{ strtolower($prenda['cliente']) }}"
                         data-tipo-recibo="{{ $esReflectivo }}"
                         style="display: {{ $displayInicial }}">
                        
                        <!-- Borde izquierdo eliminado -->
                        <!-- <div class="orden-border {{ $estadoClass }}"></div> -->

                        <!-- Contenido Izquierdo -->
                        @php
                            $reciboPrincipal = $prenda['recibos'][0] ?? null;
                            $reciboCompletadoArea = (bool) ($reciboPrincipal['completado_area'] ?? false);
                            $reciboCompletadoCorte = (bool) ($reciboPrincipal['completado_corte'] ?? false);
                            $areaReciboActual = (string) ($reciboPrincipal['area'] ?? '');
                            $reciboCompletadoCostura = (bool) ($reciboPrincipal['completado_costura'] ?? false);
                            $reciboCompletadoControlCalidad = (bool) ($reciboPrincipal['completado_control_calidad'] ?? false);
                            $areaReciboNormalizada = strtolower(trim($areaReciboActual));
                            $completadoVistaSegunArea = $areaReciboNormalizada === 'costura'
                                ? $reciboCompletadoCostura
                                : ($areaReciboNormalizada === 'corte'
                                    ? $reciboCompletadoCorte
                                    : (in_array($areaReciboNormalizada, ['control calidad', 'control de calidad'], true)
                                        ? $reciboCompletadoControlCalidad
                                        : false));
                            $labelAreaVista = $areaReciboActual ?: '-';
                            $labelEstadoVista = $completadoVistaSegunArea
                                ? ('COMPLETADO ' . strtoupper($labelAreaVista))
                                : ('PENDIENTE ' . strtoupper($labelAreaVista));
                        @endphp
                        <div class="orden-body {{ ($reciboCompletadoArea || (auth()->user()->hasRole('vista-costura') && $completadoVistaSegunArea)) ? 'recibo-completado-area' : '' }}">
                            @php
                                $encargadoVista = null;
                                // Para vista-costura, buscar el encargado según el área actual del recibo
                                if (auth()->user()->hasRole('vista-costura')) {
                                    // Buscar el encargado del proceso correspondiente al área actual
                                    $procesoActual = null;
                                    if ($areaReciboNormalizada === 'corte') {
                                        $procesoActual = \App\Models\ProcesoPrenda::where('numero_pedido', $prenda['numero_pedido'])
                                            ->where('prenda_pedido_id', $prenda['prenda_id'])
                                            ->whereRaw('LOWER(TRIM(proceso)) = ?', ['corte'])
                                            ->whereNull('deleted_at')
                                            ->latest('fecha_inicio')
                                            ->first();
                                    } elseif ($areaReciboNormalizada === 'costura') {
                                        $procesoActual = \App\Models\ProcesoPrenda::where('numero_pedido', $prenda['numero_pedido'])
                                            ->where('prenda_pedido_id', $prenda['prenda_id'])
                                            ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
                                            ->whereNull('deleted_at')
                                            ->latest('fecha_inicio')
                                            ->first();
                                    } elseif (in_array($areaReciboNormalizada, ['control calidad', 'control de calidad'], true)) {
                                        $procesoActual = \App\Models\ProcesoPrenda::where('numero_pedido', $prenda['numero_pedido'])
                                            ->where('prenda_pedido_id', $prenda['prenda_id'])
                                            ->whereRaw('LOWER(TRIM(proceso)) IN (?, ?)', ['control calidad', 'control de calidad'])
                                            ->whereNull('deleted_at')
                                            ->latest('fecha_inicio')
                                            ->first();
                                    }
                                    $encargadoVista = $procesoActual ? $procesoActual->encargado : null;
                                } else {
                                    // Lógica original para otros roles
                                    if ($areaReciboNormalizada === 'corte') {
                                        $encargadoVista = $reciboPrincipal['encargado_corte'] ?? null;
                                    } elseif ($areaReciboNormalizada === 'costura') {
                                        $encargadoVista = $reciboPrincipal['encargado_costura'] ?? null;
                                    } elseif (in_array($areaReciboNormalizada, ['control calidad', 'control de calidad'], true)) {
                                        $encargadoVista = $reciboPrincipal['encargado_control_calidad'] ?? null;
                                    }
                                }
                                $encargadoVista = is_string($encargadoVista) ? trim($encargadoVista) : $encargadoVista;
                                
                                // Obtener encargado de corte para mostrar en el card (excepto cortadores)
                                $encargadoCorte = $reciboPrincipal['encargado_corte'] ?? null;
                                $encargadoCorte = is_string($encargadoCorte) ? trim($encargadoCorte) : $encargadoCorte;
                            @endphp
                            @if(!auth()->user()->hasRole('vista-costura') && !auth()->user()->hasRole('cortador') && !auth()->user()->hasRole('costurero'))
                                <div class="orden-encargado-corner" onclick="event.stopPropagation();">
                                    <strong>Encargado:</strong>
                                    <span>{{ $encargadoVista ? strtoupper($encargadoVista) : 'SIN ENCARGADO' }}</span>
                                </div>
                            @endif
                            {{-- Mostrar encargado de corte para todos excepto cortadores --}}
                            @if(!auth()->user()->hasRole('cortador'))
                                <div class="orden-encargado-corte" onclick="event.stopPropagation();" style="background: #fef3c7; padding: 6px 10px; border-radius: 6px; margin-bottom: 8px; display: inline-flex; align-items: center; gap: 8px; width: fit-content;">
                                    <strong style="color: #92400e; font-size: 12px;">Encargado Corte:</strong>
                                    <span style="color: #78350f; font-size: 12px; font-weight: 600;">{{ $encargadoCorte ? strtoupper($encargadoCorte) : 'SIN ASIGNAR' }}</span>
                                </div>
                            @endif
                            @if(auth()->user()->hasRole('vista-costura'))
                                <div class="orden-top-badges" onclick="event.stopPropagation();">
                                    <span class="badge-area">{{ strtoupper($labelAreaVista) }}</span>
                                    <span class="badge-completado-corte {{ $completadoVistaSegunArea ? 'is-on' : '' }}">
                                        {{ $labelEstadoVista }}
                                    </span>
                                    <strong class="label-encargado">Encargado:</strong>
                                    <span class="badge-encargado">
                                        {{ $encargadoVista ? strtoupper($encargadoVista) : 'SIN ENCARGADO' }}
                                    </span>
                                </div>
                            @endif
                            <div class="orden-left">
                                <div class="orden-top">
                                    <div class="orden-numero-section">
                                        @if(isset($prenda['recibos'][0]['consecutivo_actual']))
                                            <h4 class="orden-numero">#{{ $prenda['recibos'][0]['consecutivo_actual'] }}</h4>
                                        @else
                                            <h4 class="orden-numero">#{{ $prenda['numero_pedido'] }}</h4>
                                        @endif
                                        <span class="estado-badge {{ $estadoClass }}" data-estado="recibo-costura">
                                            {{ count(array_unique(array_map(fn($r) => strtoupper($r['tipo_recibo']), $prenda['recibos']))) }} RECIBOS
                                        </span>
                                        @if(auth()->user()->hasRole('costurero') && $reciboCompletadoCostura)
                                            <span class="badge-completado-costura is-on">COMPLETADO</span>
                                        @endif
                                        @if(auth()->user()->hasRole('costura-reflectivo') && $reciboCompletadoCostura)
                                            <span class="badge-completado-costura is-on">COMPLETADO</span>
                                        @endif
                                        @if(auth()->user()->hasRole('administrador-costura') && $reciboCompletadoCostura)
                                            <span class="badge-completado-costura is-on">COMPLETADO</span>
                                        @endif
                                    </div>
                                    <!-- Badge completado para costurero - posicionado en esquina superior derecha solo en mobile -->
                                    @if(auth()->user()->hasRole('costurero') && $reciboCompletadoCostura)
                                        <span class="badge-completado-costura is-on mobile-top-right">COMPLETADO</span>
                                    @endif
                                    <!-- Badge completado para costura-reflectivo - posicionado en esquina superior derecha solo en mobile -->
                                    @if(auth()->user()->hasRole('costura-reflectivo') && $reciboCompletadoCostura)
                                        <span class="badge-completado-costura is-on mobile-top-right">COMPLETADO</span>
                                    @endif
                                    <!-- Badge completado para administrador-costura - posicionado en esquina superior derecha solo en mobile -->
                                    @if(auth()->user()->hasRole('administrador-costura') && $reciboCompletadoCostura)
                                        <span class="badge-completado-costura is-on mobile-top-right">COMPLETADO</span>
                                    @endif
                                    <!-- Botón de más opciones para mobile -->
                                    <button class="mobile-actions-toggle" onclick="toggleMobileActions({{ $prenda['prenda_id'] }})">
                                        <span class="material-symbols-rounded">more_horiz</span>
                                    </button>
                                </div>

                                <div class="orden-cliente">
                                    <p class="cliente-label">CLIENTE</p>
                                    <p class="cliente-name">{{ $prenda['cliente'] }}</p>
                                </div>

                                <!-- Botón Ver Recibo (debajo del estado para mobile) -->
                                <div class="mobile-ver-recibo-section">
                                    <button class="btn-ver-recibos mobile-under-state" onclick="abrirDetallesRecibos('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] }}, '{{ $prenda['nombre_prenda'] }}', '{{ $prenda['recibos'][0]['tipo_recibo'] ?? '' }}', {{ !empty($prenda['recibos'][0]['pedido_parcial_id']) ? (int)$prenda['recibos'][0]['pedido_parcial_id'] : 'null' }})">
                                        <span class="material-symbols-rounded">visibility</span>
                                        VER RECIBO
                                    </button>
                                </div>

                                <div class="orden-prendas">
                                    <p class="prendas-label">
                                        <strong>{{ $prenda['nombre_prenda'] }}</strong>
                                        @if($prenda['descripcion'])
                                            {{ $prenda['descripcion'] }}
                                        @endif
                                    </p>        
                                </div>

                                <!-- Contenedor de Botones -->
                                <div class="orden-buttons">
                                    @if(auth()->user()->hasRole('cortador'))
                                        @php
                                            $reciboPrincipal = $prenda['recibos'][0] ?? null;
                                            $areaRecibo = strtolower(trim((string)($reciboPrincipal['area'] ?? '')));
                                            $esCorteRecibo = $areaRecibo === 'corte';
                                            $esCosturaRecibo = $areaRecibo === 'costura';
                                            $reciboId = $reciboPrincipal['id'] ?? null;
                                        @endphp
                                        
                                        {{-- Botón para cortadores: Marcar como completado (pasa a Costura) --}}
                                        @if($esCorteRecibo && $reciboId)
                                            <button class="btn-completar-corte" 
                                                    id="btn-completar-{{ $prenda['prenda_id'] }}"
                                                    data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                    data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                    data-recibo-id="{{ $reciboId }}"
                                                    data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                    onclick="completarCorte(this)">
                                                <span class="material-symbols-rounded">check_circle</span>
                                                MARCAR COMPLETADO
                                            </button>
                                        @endif
                                        
                                        {{-- Botón para cortadores: Deshacer (regresa a Corte) --}}
                                        @if($esCosturaRecibo && $reciboId)
                                            <button class="btn-deshacer-corte" 
                                                    id="btn-deshacer-{{ $prenda['prenda_id'] }}"
                                                    data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                    data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                    data-recibo-id="{{ $reciboId }}"
                                                    data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                    onclick="deshacerCorte(this)">
                                                <span class="material-symbols-rounded">undo</span>
                                                DESHACER
                                            </button>
                                        @endif
                                    @endif
                                    
                                    @if(auth()->user()->hasRole('costurero'))
                                        @php
                                            $reciboPrincipal = $prenda['recibos'][0] ?? null;
                                            $areaRecibo = strtolower(trim((string)($reciboPrincipal['area'] ?? '')));
                                            $esCosturaRecibo = $areaRecibo === 'costura';
                                            $reciboId = $reciboPrincipal['id'] ?? null;
                                            $reciboCompletadoCostura = (bool) ($reciboPrincipal['completado_costura'] ?? false);
                                        @endphp
                                        
                                        {{-- Botón para costureros: Marcar como completado (sin cambiar de área) --}}
                                        @if($esCosturaRecibo && $reciboId && !$reciboCompletadoCostura)
                                            <button class="btn-completar-costura" 
                                                    id="btn-completar-costura-{{ $prenda['prenda_id'] }}"
                                                    data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                    data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                    data-recibo-id="{{ $reciboId }}"
                                                    data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                    onclick="completarCostura(this)">
                                                <span class="material-symbols-rounded">check_circle</span>
                                                COMPLETAR
                                            </button>
                                        @endif
                                        
                                        {{-- Botón para costureros: Deshacer (regresa a pendiente) --}}
                                        @if($esCosturaRecibo && $reciboId && $reciboCompletadoCostura)
                                            <button class="btn-deshacer-costura" 
                                                    id="btn-deshacer-costura-{{ $prenda['prenda_id'] }}"
                                                    data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                    data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                    data-recibo-id="{{ $reciboId }}"
                                                    data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                    onclick="deshacerCostura(this)">
                                                <span class="material-symbols-rounded">undo</span>
                                                DESHACER
                                            </button>
                                        @endif
                                    @endif
                                    
                                    @if(auth()->user()->hasRole('vista-costura'))
                                        @php
                                            $tiposUnicos = collect($prenda['recibos'])->pluck('tipo_recibo')->map(fn($t) => strtoupper($t))->unique()->values();
                                            $areaActual = $prenda['recibos'][0]['area'] ?? null;
                                            $procesoId = $prenda['recibos'][0]['proceso_id_costura'] ?? null;
                                            // Para vista-costura, buscar el encargado real del proceso de costura
                                            $encargadoCostura = null;
                                            if (strtolower(trim($areaActual ?? '')) === 'costura') {
                                                $procesoCosturaReal = \App\Models\ProcesoPrenda::where('numero_pedido', $prenda['numero_pedido'])
                                                    ->where('prenda_pedido_id', $prenda['prenda_id'])
                                                    ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
                                                    ->whereNull('deleted_at')
                                                    ->latest('fecha_inicio')
                                                    ->first();
                                                $encargadoCostura = $procesoCosturaReal ? $procesoCosturaReal->encargado : null;
                                            }
                                            $tipoRecibo = strtoupper($tiposUnicos->first() ?? 'COSTURA');
                                            $esCC = in_array(strtolower(trim($areaActual ?? '')), ['control calidad', 'control de calidad']);
                                            $esCosturaProceso = strtolower(trim($areaActual ?? '')) === 'costura';
                                            $esTipoReciboCostura = in_array('COSTURA', $tiposUnicos->toArray());
                                            $encargadoCostura = is_string($encargadoCostura) ? trim($encargadoCostura) : $encargadoCostura;
                                            $tieneEncargadoCostura = !empty($encargadoCostura);
                                            $mostrarComoDeshacerCostura = $esCosturaProceso && $tieneEncargadoCostura;
                                        @endphp

                                        {{-- Botón "Pasar a Costura" o "DESHACER COSTURA" solo para recibos tipo COSTURA --}}
                                        @if($esTipoReciboCostura)
                                            <button class="btn-pasar-costura {{ $mostrarComoDeshacerCostura ? 'btn-deshacer-costura' : '' }}" 
                                                    id="btn-costura-{{ $prenda['prenda_id'] }}"
                                                    data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                    data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                    data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                    data-tipo-recibo="COSTURA"
                                                    data-recibo="{{ isset($prenda['recibos'][0]['consecutivo_actual']) ? $prenda['recibos'][0]['consecutivo_actual'] : $prenda['numero_pedido'] }}"
                                                    data-area="{{ $areaActual ?? '' }}"
                                                    data-proceso-id="{{ $procesoId }}"
                                                    data-encargado-costura="{{ is_string($encargadoCostura ?? null) ? trim($encargadoCostura) : ($encargadoCostura ?? '') }}"
                                                    onclick="manejarPasarACostura(this)">
                                                <span class="material-symbols-rounded">{{ $mostrarComoDeshacerCostura ? 'undo' : 'checkroom' }}</span>
                                                {{ $mostrarComoDeshacerCostura ? 'DESHACER COSTURA' : 'PASAR A COSTURA' }}
                                            </button>
                                        @endif

                                        {{-- Botón "Pasar a C.C" o "DESHACER" --}}
                                        <button class="btn-pasar-cc" 
                                                id="btn-cc-{{ $prenda['prenda_id'] }}"
                                                data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                data-tipo-recibo="{{ $tipoRecibo }}"
                                                data-recibo="{{ isset($prenda['recibos'][0]['consecutivo_actual']) ? $prenda['recibos'][0]['consecutivo_actual'] : $prenda['numero_pedido'] }}"
                                                data-area="{{ $areaActual ?? 'COSTURA' }}"
                                                data-proceso-id="{{ $procesoId }}"
                                                onclick="pasarAControlCalidad(this)">
                                            <span class="material-symbols-rounded">{{ $esCC ? 'undo' : 'check_circle' }}</span>
                                            {{ $esCC ? 'DESHACER' : 'PASAR A C.C' }}
                                        </button>
                                    @endif
                                    
                                    <button class="btn-agregar-novedad" onclick="abrirModalNovedad('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] }}, '{{ $prenda['nombre_prenda'] }}', {{ isset($prenda['recibos'][0]['consecutivo_actual']) ? $prenda['recibos'][0]['consecutivo_actual'] : $prenda['numero_pedido'] }})">
                                        <span class="material-symbols-rounded">comment</span>
                                        AGREGAR NOVEDAD
                                    </button>
                                    @if(auth()->user()->hasRole('costura-reflectivo') || auth()->user()->hasRole('vista-costura'))
                                        @php
                                            $reciboReflectivo = collect($prenda['recibos'] ?? [])->first(function($r) {
                                                return strtoupper((string)($r['tipo_recibo'] ?? '')) === 'REFLECTIVO';
                                            });
                                            $tieneReciboReflectivo = !empty($reciboReflectivo);
                                            $encargadoCosturaRef = $reciboReflectivo['encargado_costura'] ?? null;
                                            $encargadoCosturaRef = is_string($encargadoCosturaRef) ? trim($encargadoCosturaRef) : $encargadoCosturaRef;
                                            $tieneEncargadoCosturaRef = !empty($encargadoCosturaRef);
                                            $areaReciboRef = $reciboReflectivo['area'] ?? '';
                                            $esCosturaAreaRef = strtolower(trim((string)$areaReciboRef)) === 'costura';
                                        @endphp
                                        @if($tieneReciboReflectivo && (auth()->user()->hasRole('vista-costura') || auth()->user()->hasRole('administrador-costura')))
                                            <button class="btn-pasar-costura {{ $tieneEncargadoCosturaRef ? 'btn-deshacer-costura' : '' }}" 
                                                    id="btn-costura-reflectivo-{{ $prenda['prenda_id'] }}"
                                                    data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                    data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                    data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                    data-tipo-recibo="REFLECTIVO"
                                                    data-recibo="{{ $reciboReflectivo['consecutivo_actual'] ?? $prenda['numero_pedido'] }}"
                                                    data-area="{{ $areaReciboRef }}"
                                                    data-proceso-id="{{ $reciboReflectivo['proceso_id_costura'] ?? '' }}"
                                                    data-encargado-costura="{{ $encargadoCosturaRef ?? '' }}"
                                                    onclick="manejarPasarACostura(this)">
                                                <span class="material-symbols-rounded">{{ $tieneEncargadoCosturaRef ? 'undo' : 'checkroom' }}</span>
                                                {{ $tieneEncargadoCosturaRef ? 'DESHACER COSTURA' : 'PASAR A COSTURA' }}
                                            </button>
                                        @endif
                                    @endif
                                    @if(auth()->user()->hasRole('costura-reflectivo') || auth()->user()->hasRole('vista-costura') || auth()->user()->hasRole('administrador-costura'))
                                        {{-- Para costura-reflectivo/vista-costura/administrador-costura, crear un botón por cada TIPO de recibo (sin duplicados) --}}
                                        @php
                                            $tiposUnicos = collect($prenda['recibos'])->pluck('tipo_recibo')->map(fn($t) => strtoupper($t))->unique()->values();
                                        @endphp
                                        @foreach($tiposUnicos as $tipoReciboUnico)
                                            @php
                                                $reciboTipo = collect($prenda['recibos'] ?? [])->first(function($r) use ($tipoReciboUnico) {
                                                    return strtoupper((string)($r['tipo_recibo'] ?? '')) === strtoupper((string)$tipoReciboUnico);
                                                });
                                                $pedidoParcialId = $reciboTipo['pedido_parcial_id'] ?? null;
                                            @endphp
                                            <button class="btn-ver-recibos" onclick="abrirDetallesRecibos('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] }}, '{{ $prenda['nombre_prenda'] }}', 'VER RECIBO', {{ $pedidoParcialId ? (int)$pedidoParcialId : 'null' }})">
                                                <span class="material-symbols-rounded">visibility</span>
                                                VER RECIBO
                                            </button>
                                        @endforeach
                                        
                                        {{-- Botones de completar/deshacer para costura-reflectivo y administrador-costura --}}
                                        @if(auth()->user()->hasRole('costura-reflectivo') || auth()->user()->hasRole('administrador-costura'))
                                            @php
                                                $tiposUnicos = collect($prenda['recibos'])->pluck('tipo_recibo')->map(fn($t) => strtoupper($t))->unique()->values();
                                            @endphp
                                            @foreach($tiposUnicos as $tipoReciboUnico)
                                                @php
                                                    $reciboTipo = collect($prenda['recibos'] ?? [])->first(function($r) use ($tipoReciboUnico) {
                                                        return strtoupper((string)($r['tipo_recibo'] ?? '')) === strtoupper((string)$tipoReciboUnico);
                                                    });
                                                    $reciboId = $reciboTipo['id'] ?? null;
                                                    $areaRecibo = strtolower(trim((string)($reciboTipo['area'] ?? '')));
                                                    $esCosturaArea = $areaRecibo === 'costura';
                                                    $reciboCompletadoArea = false;
                                                    
                                                    // Verificar si está completado según el área
                                                    if ($esCosturaArea) {
                                                        $reciboCompletadoArea = (bool) ($reciboTipo['completado_costura'] ?? false);
                                                    } else {
                                                        $reciboCompletadoArea = (bool) ($reciboTipo['completado_area'] ?? false);
                                                    }
                                                    
                                                    // Para costura-reflectivo y administrador-costura: verificar si tiene encargado asignado SOLO en recibos REFLECTIVO
                                                    // Para administrador-costura: permitir siempre (no requiere encargado)
                                                    $tieneEncargadoAsignado = false;
                                                    if (strtoupper($tipoReciboUnico) === 'REFLECTIVO') {
                                                        $encargadoReflectivo = $reciboTipo['encargado_costura'] ?? null;
                                                        $encargadoReflectivo = is_string($encargadoReflectivo) ? trim($encargadoReflectivo) : $encargadoReflectivo;
                                                        $tieneEncargadoAsignado = !empty($encargadoReflectivo);
                                                    } else {
                                                        // Para otros tipos (COSTURA), se permite sin encargado
                                                        $tieneEncargadoAsignado = true;
                                                    }
                                                    
                                                    // Para administrador-costura: siempre permitir
                                                    if (auth()->user()->hasRole('administrador-costura')) {
                                                        $tieneEncargadoAsignado = true;
                                                    }
                                                    
                                                    $tipoReciboNormalizado = strtolower($tipoReciboUnico);
                                                @endphp
                                                
                                                @if($reciboId && $esCosturaArea && $tieneEncargadoAsignado)
                                                    @if(!$reciboCompletadoArea)
                                                        <button class="btn-completar-costura" 
                                                                id="btn-completar-{{ $tipoReciboNormalizado }}-{{ $prenda['prenda_id'] }}"
                                                                data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                                data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                                data-recibo-id="{{ $reciboId }}"
                                                                data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                                data-tipo-recibo="{{ $tipoReciboNormalizado }}"
                                                                onclick="completarCostura(this)">
                                                            <span class="material-symbols-rounded">check_circle</span>
                                                            COMPLETAR {{ strtoupper($tipoReciboUnico) }}
                                                        </button>
                                                    @else
                                                        <button class="btn-deshacer-costura" 
                                                                id="btn-deshacer-{{ $tipoReciboNormalizado }}-{{ $prenda['prenda_id'] }}"
                                                                data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                                data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                                data-recibo-id="{{ $reciboId }}"
                                                                data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                                data-tipo-recibo="{{ $tipoReciboNormalizado }}"
                                                                onclick="deshacerCostura(this)">
                                                            <span class="material-symbols-rounded">undo</span>
                                                            DESHACER {{ strtoupper($tipoReciboUnico) }}
                                                        </button>
                                                    @endif
                                                @endif
                                            @endforeach
                                        @endif
                                    @else
                                        {{-- Para otros operarios, un solo botón con tipo de recibo --}}
                                        <button class="btn-ver-recibos" onclick="abrirDetallesRecibos('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] }}, '{{ $prenda['nombre_prenda'] }}', '{{ $prenda['recibos'][0]['tipo_recibo'] ?? '' }}', {{ !empty($prenda['recibos'][0]['pedido_parcial_id']) ? (int)$prenda['recibos'][0]['pedido_parcial_id'] : 'null' }})">
                                            <span class="material-symbols-rounded">visibility</span>
                                            VER RECIBOS
                                        </button>
                                    @endif
                                </div>

                                <!-- Mobile Actions Drawer -->
                                <div class="mobile-actions-drawer" id="mobile-drawer-{{ $prenda['prenda_id'] }}">
                                    @if(auth()->user()->hasRole('cortador'))
                                        @php
                                            $reciboPrincipal = $prenda['recibos'][0] ?? null;
                                            $areaRecibo = strtolower(trim((string)($reciboPrincipal['area'] ?? '')));
                                            $esCorteRecibo = $areaRecibo === 'corte';
                                            $esCosturaRecibo = $areaRecibo === 'costura';
                                            $reciboId = $reciboPrincipal['id'] ?? null;
                                        @endphp
                                        
                                        {{-- Botón para cortadores: Marcar como completado (pasa a Costura) --}}
                                        @if($esCorteRecibo && $reciboId)
                                            <button class="btn-completar-corte" 
                                                    data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                    data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                    data-recibo-id="{{ $reciboId }}"
                                                    data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                    onclick="completarCorte(this)">
                                                <span class="material-symbols-rounded">check_circle</span>
                                                MARCAR COMPLETADO
                                            </button>
                                        @endif
                                        
                                        {{-- Botón para cortadores: Deshacer (regresa a Corte) --}}
                                        @if($esCosturaRecibo && $reciboId)
                                            <button class="btn-deshacer-corte" 
                                                    data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                    data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                    data-recibo-id="{{ $reciboId }}"
                                                    data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                    onclick="deshacerCorte(this)">
                                                <span class="material-symbols-rounded">undo</span>
                                                DESHACER
                                            </button>
                                        @endif
                                    @endif
                                    
                                    @if(auth()->user()->hasRole('costurero'))
                                        @php
                                            $reciboPrincipal = $prenda['recibos'][0] ?? null;
                                            $areaRecibo = strtolower(trim((string)($reciboPrincipal['area'] ?? '')));
                                            $esCosturaRecibo = $areaRecibo === 'costura';
                                            $reciboId = $reciboPrincipal['id'] ?? null;
                                            $reciboCompletadoCostura = (bool) ($reciboPrincipal['completado_costura'] ?? false);
                                        @endphp
                                        
                                        {{-- Botón para costureros: Marcar como completado (sin cambiar de área) --}}
                                        @if($esCosturaRecibo && $reciboId && !$reciboCompletadoCostura)
                                            <button class="btn-completar-costura" 
                                                    data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                    data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                    data-recibo-id="{{ $reciboId }}"
                                                    data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                    onclick="completarCostura(this)">
                                                <span class="material-symbols-rounded">check_circle</span>
                                                COMPLETAR
                                            </button>
                                        @endif
                                        
                                        {{-- Botón para costureros: Deshacer (regresa a pendiente) --}}
                                        @if($esCosturaRecibo && $reciboId && $reciboCompletadoCostura)
                                            <button class="btn-deshacer-costura" 
                                                    data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                    data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                    data-recibo-id="{{ $reciboId }}"
                                                    data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                    onclick="deshacerCostura(this)">
                                                <span class="material-symbols-rounded">undo</span>
                                                DESHACER
                                            </button>
                                        @endif
                                    @endif
                                    
                                    {{-- Botones mobile para costura-reflectivo --}}
                                    @if(auth()->user()->hasRole('costura-reflectivo'))
                                        @php
                                            $tiposUnicos = collect($prenda['recibos'])->pluck('tipo_recibo')->map(fn($t) => strtoupper($t))->unique()->values();
                                        @endphp
                                        @foreach($tiposUnicos as $tipoReciboUnico)
                                            @php
                                                $reciboTipo = collect($prenda['recibos'] ?? [])->first(function($r) use ($tipoReciboUnico) {
                                                    return strtoupper((string)($r['tipo_recibo'] ?? '')) === strtoupper((string)$tipoReciboUnico);
                                                });
                                                $reciboId = $reciboTipo['id'] ?? null;
                                                $areaRecibo = strtolower(trim((string)($reciboTipo['area'] ?? '')));
                                                $esCosturaArea = $areaRecibo === 'costura';
                                                $reciboCompletadoArea = false;
                                                
                                                // Verificar si está completado según el área
                                                if ($esCosturaArea) {
                                                    $reciboCompletadoArea = (bool) ($reciboTipo['completado_costura'] ?? false);
                                                } else {
                                                    $reciboCompletadoArea = (bool) ($reciboTipo['completado_area'] ?? false);
                                                }
                                                
                                                $tipoReciboNormalizado = strtolower($tipoReciboUnico);
                                            @endphp
                                            
                                            @if($reciboId && $esCosturaArea)
                                                @if(!$reciboCompletadoArea)
                                                    <button class="btn-completar-costura" 
                                                            data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                            data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                            data-recibo-id="{{ $reciboId }}"
                                                            data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                            data-tipo-recibo="{{ $tipoReciboNormalizado }}"
                                                            onclick="completarCostura(this)">
                                                        <span class="material-symbols-rounded">check_circle</span>
                                                        COMPLETAR {{ strtoupper($tipoReciboUnico) }}
                                                    </button>
                                                @else
                                                    <button class="btn-deshacer-costura" 
                                                            data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                            data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                            data-recibo-id="{{ $reciboId }}"
                                                            data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                            data-tipo-recibo="{{ $tipoReciboNormalizado }}"
                                                            onclick="deshacerCostura(this)">
                                                        <span class="material-symbols-rounded">undo</span>
                                                        DESHACER {{ strtoupper($tipoReciboUnico) }}
                                                    </button>
                                                @endif
                                            @endif
                                        @endforeach
                                    @endif
                                    
                                    @if(auth()->user()->hasRole('vista-costura'))
                                        @php
                                            $tiposUnicos = collect($prenda['recibos'])->pluck('tipo_recibo')->map(fn($t) => strtoupper($t))->unique()->values();
                                            $areaActual = $prenda['recibos'][0]['area'] ?? null;
                                            $procesoId = $prenda['recibos'][0]['proceso_id_costura'] ?? null;
                                            // Para vista-costura, buscar el encargado real del proceso de costura
                                            $encargadoCostura = null;
                                            if (strtolower(trim($areaActual ?? '')) === 'costura') {
                                                $procesoCosturaReal = \App\Models\ProcesoPrenda::where('numero_pedido', $prenda['numero_pedido'])
                                                    ->where('prenda_pedido_id', $prenda['prenda_id'])
                                                    ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
                                                    ->whereNull('deleted_at')
                                                    ->latest('fecha_inicio')
                                                    ->first();
                                                $encargadoCostura = $procesoCosturaReal ? $procesoCosturaReal->encargado : null;
                                            }
                                            $tipoRecibo = strtoupper($tiposUnicos->first() ?? 'COSTURA');
                                            $esCC = in_array(strtolower(trim($areaActual ?? '')), ['control calidad', 'control de calidad']);
                                            $esCosturaProceso = strtolower(trim($areaActual ?? '')) === 'costura';
                                            $esTipoReciboCostura = in_array('COSTURA', $tiposUnicos->toArray());
                                            $encargadoCostura = is_string($encargadoCostura) ? trim($encargadoCostura) : $encargadoCostura;
                                            $tieneEncargadoCostura = !empty($encargadoCostura);
                                            $mostrarComoDeshacerCostura = $esCosturaProceso && $tieneEncargadoCostura;
                                        @endphp

                                        {{-- Botón "Pasar a Costura" o "DESHACER COSTURA" solo para recibos tipo COSTURA --}}
                                        @if($esTipoReciboCostura)
                                            <button class="btn-pasar-costura {{ $mostrarComoDeshacerCostura ? 'btn-deshacer-costura' : '' }}" 
                                                    id="btn-costura-{{ $prenda['prenda_id'] }}"
                                                    data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                    data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                    data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                    data-tipo-recibo="COSTURA"
                                                    data-recibo="{{ isset($prenda['recibos'][0]['consecutivo_actual']) ? $prenda['recibos'][0]['consecutivo_actual'] : $prenda['numero_pedido'] }}"
                                                    data-area="{{ $areaActual ?? '' }}"
                                                    data-proceso-id="{{ $procesoId }}"
                                                    data-encargado-costura="{{ is_string($encargadoCostura ?? null) ? trim($encargadoCostura) : ($encargadoCostura ?? '') }}"
                                                    onclick="manejarPasarACostura(this)">
                                                <span class="material-symbols-rounded">{{ $mostrarComoDeshacerCostura ? 'undo' : 'checkroom' }}</span>
                                                {{ $mostrarComoDeshacerCostura ? 'DESHACER COSTURA' : 'PASAR A COSTURA' }}
                                            </button>
                                        @endif

                                        {{-- Botón "Pasar a C.C" o "DESHACER" --}}
                                        <button class="btn-pasar-cc" 
                                                id="btn-cc-{{ $prenda['prenda_id'] }}"
                                                data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                data-tipo-recibo="{{ $tipoRecibo }}"
                                                data-recibo="{{ isset($prenda['recibos'][0]['consecutivo_actual']) ? $prenda['recibos'][0]['consecutivo_actual'] : $prenda['numero_pedido'] }}"
                                                data-area="{{ $areaActual ?? 'COSTURA' }}"
                                                data-proceso-id="{{ $procesoId }}"
                                                onclick="pasarAControlCalidad(this)">
                                            <span class="material-symbols-rounded">{{ $esCC ? 'undo' : 'check_circle' }}</span>
                                            {{ $esCC ? 'DESHACER' : 'PASAR A C.C' }}
                                        </button>
                                    @endif
                                    
                                    @if(auth()->user()->hasRole('costura-reflectivo') || auth()->user()->hasRole('vista-costura'))
                                        @php
                                            $tiposUnicos = collect($prenda['recibos'])->pluck('tipo_recibo')->map(fn($t) => strtoupper($t))->unique()->values();
                                        @endphp
                                        @foreach($tiposUnicos as $tipoReciboUnico)
                                            @php
                                                $reciboTipo = collect($prenda['recibos'] ?? [])->first(function($r) use ($tipoReciboUnico) {
                                                    return strtoupper((string)($r['tipo_recibo'] ?? '')) === strtoupper((string)$tipoReciboUnico);
                                                });
                                                $pedidoParcialId = $reciboTipo['pedido_parcial_id'] ?? null;
                                            @endphp
                                            <button class="btn-ver-recibos" onclick="abrirDetallesRecibos('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] }}, '{{ $prenda['nombre_prenda'] }}', 'VER RECIBO', {{ $pedidoParcialId ? (int)$pedidoParcialId : 'null' }})">
                                                <span class="material-symbols-rounded">visibility</span>
                                                VER RECIBO
                                            </button>
                                        @endforeach
                                    @else
                                        <button class="btn-ver-recibos" onclick="abrirDetallesRecibos('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] }}, '{{ $prenda['nombre_prenda'] }}', '{{ $prenda['recibos'][0]['tipo_recibo'] ?? '' }}', {{ !empty($prenda['recibos'][0]['pedido_parcial_id']) ? (int)$prenda['recibos'][0]['pedido_parcial_id'] : 'null' }})">
                                            <span class="material-symbols-rounded">visibility</span>
                                            VER RECIBOS
                                        </button>
                                    @endif
                                    
                                    @if(auth()->user()->hasRole('cortador'))
                                    @php
                                        $reciboPrincipal = $prenda['recibos'][0] ?? null;
                                        $areaRecibo = strtolower(trim((string)($reciboPrincipal['area'] ?? '')));
                                        $esCorteRecibo = $areaRecibo === 'corte';
                                        $esCosturaRecibo = $areaRecibo === 'costura';
                                        $reciboId = $reciboPrincipal['id'] ?? null;
                                    @endphp
                                    
                                    @if($esCorteRecibo && $reciboId)
                                        <button class="btn-completar-corte" 
                                                data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                data-recibo-id="{{ $reciboId }}"
                                                data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                onclick="completarCorte(this)">
                                            <span class="material-symbols-rounded">check_circle</span>
                                            MARCAR COMPLETADO
                                        </button>
                                    @elseif($esCosturaRecibo && $reciboId)
                                        <button class="btn-deshacer-corte" 
                                                data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                data-recibo-id="{{ $reciboId }}"
                                                data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                onclick="deshacerCorte(this)">
                                            <span class="material-symbols-rounded">undo</span>
                                            DESHACER
                                        </button>
                                    @else
                                        <button class="btn-agregar-novedad" onclick="abrirModalNovedad('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] }}, '{{ $prenda['nombre_prenda'] }}', {{ isset($prenda['recibos'][0]['consecutivo_actual']) ? $prenda['recibos'][0]['consecutivo_actual'] : $prenda['numero_pedido'] }})">
                                            <span class="material-symbols-rounded">comment</span>
                                            AGREGAR NOVEDAD
                                        </button>
                                    @endif
                                @else
                                    <button class="btn-agregar-novedad" onclick="abrirModalNovedad('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] }}, '{{ $prenda['nombre_prenda'] }}', {{ isset($prenda['recibos'][0]['consecutivo_actual']) ? $prenda['recibos'][0]['consecutivo_actual'] : $prenda['numero_pedido'] }})">
                                        <span class="material-symbols-rounded">comment</span>
                                        AGREGAR NOVEDAD
                                    </button>
                                @endif
                                    <button class="mobile-actions-toggle" onclick="toggleMobileActions({{ $prenda['prenda_id'] }})">
                                        <span class="material-symbols-rounded">more_horiz</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Contenido Derecho -->
                            <div class="orden-right">
                                <div class="orden-right-center">
                                    <a href="#" class="action-arrow" onclick="abrirDetallesRecibos('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] }}, '{{ $prenda['nombre_prenda'] }}', '{{ $prenda['recibos'][0]['tipo_recibo'] ?? '' }}'); return false;">
                                        <span class="material-symbols-rounded">arrow_forward</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="empty-state">
                    <span class="material-symbols-rounded">inbox</span>
                    <p>No hay prendas con recibos de costura asignadas</p>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        window.__initDashboardSearch = function() {
            const searchInput = document.getElementById('searchInput');
            const clearBtn = document.getElementById('clearFilterBtn');

            const ordenesList = document.getElementById('ordenesList');
            const ordenCards = ordenesList ? ordenesList.querySelectorAll('.orden-card-simple') : [];

            console.log('=== TARJETAS CARGADAS EN DASHBOARD ===');
            console.log('Total de tarjetas:', ordenCards.length);
            ordenCards.forEach((card, index) => {
                console.log(`Tarjeta ${index + 1}:`, {
                    numero: card.dataset.numero,
                    prenda: card.dataset.prenda,
                    cliente: card.dataset.cliente,
                    'data-tipo-recibo': card.dataset.tipoRecibo
                });
            });
            console.log('=====================================\n');

            if (!searchInput) {
                return;
            }

            if (window.__dashboardClearHandler && clearBtn) {
                clearBtn.removeEventListener('click', window.__dashboardClearHandler);
            }
            if (window.__dashboardSearchHandler) {
                searchInput.removeEventListener('input', window.__dashboardSearchHandler);
            }

            window.__dashboardClearHandler = function() {
                searchInput.value = '';
                if (clearBtn) {
                    clearBtn.style.display = 'none';
                }

                const event = new Event('input', { bubbles: true });
                searchInput.dispatchEvent(event);
            };

            if (clearBtn) {
                clearBtn.addEventListener('click', window.__dashboardClearHandler);
            }

            window.__dashboardSearchHandler = function(e) {
                const busqueda = e.target.value.toLowerCase().trim();

                if (clearBtn) {
                    clearBtn.style.display = busqueda ? 'flex' : 'none';
                }

                const ordenesListActual = document.getElementById('ordenesList');
                const cardsActuales = ordenesListActual ? ordenesListActual.querySelectorAll('.orden-card-simple') : [];

                cardsActuales.forEach(card => {
                    const reciboDom = card.querySelector('.orden-right .orden-fecha span:not(.orden-fecha-label)');
                    const numeroRecibo = reciboDom ? reciboDom.textContent.toLowerCase().trim() : '';
                    const cliente = String(card.dataset.cliente || '').toLowerCase();

                    const prendaDom = card.querySelector('.orden-prendas .prendas-label strong');
                    const nombrePrenda = prendaDom ? prendaDom.textContent.toLowerCase().trim() : '';

                    if (numeroRecibo.includes(busqueda) || cliente.includes(busqueda) || nombrePrenda.includes(busqueda) || busqueda === '') {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            };

            searchInput.addEventListener('input', window.__dashboardSearchHandler);
        };

        if (typeof window.__initDashboardSearch === 'function') {
            window.__initDashboardSearch();
        }
    });

    // Función para filtrar prendas por tipo de recibo
    window.filtrarPrendasPorRecibo = function(filtro) {
        console.log(' [FILTRO] Iniciando filtro:', filtro);
        
        // Actualizar estado de botones
        document.querySelectorAll('.badge-filtro').forEach(btn => {
            btn.classList.remove('badge-filtro-active');
        });
        const btnFiltro = document.querySelector(`[data-filtro="${filtro}"]`);
        if (btnFiltro) {
            btnFiltro.classList.add('badge-filtro-active');
        }

        // Filtrar tarjetas
        const ordenesList = document.getElementById('ordenesList');
        if (!ordenesList) {
            console.error(' ordenesList no encontrado');
            return;
        }

        const ordenCards = ordenesList.querySelectorAll('.orden-card-simple');
        console.log(` [FILTRO] Tarjetas encontradas: ${ordenCards.length}`);
        
        let mostradas = 0;
        let ocultadas = 0;
        
        ordenCards.forEach((card, index) => {
            const tipoRecibo = card.dataset.tipoRecibo;
            const numeroPedido = card.dataset.numero;
            const nombrePrenda = card.dataset.prenda;
            
            console.log(`Tarjeta ${index + 1}: Pedido=${numeroPedido}, Prenda=${nombrePrenda}, data-tipo-recibo="${tipoRecibo}"`);
            
            if (filtro === 'todos') {
                card.style.display = '';
                mostradas++;
            } else {
                // Para vista-costura, tipoRecibo puede tener múltiples valores separados por comas
                // Ej: "costura,reflectivo" o "costura" o "reflectivo"
                const tipos = tipoRecibo ? tipoRecibo.split(',').map(t => t.trim()) : [];
                
                if (tipos.includes(filtro)) {
                    console.log(`  Mostrando (contiene "${filtro}" en [${tipos.join(', ')}])`);
                    card.style.display = '';
                    mostradas++;
                } else {
                    console.log(`  Ocultando (no contiene "${filtro}" en [${tipos.join(', ')}])`);
                    card.style.display = 'none';
                    ocultadas++;
                }
            }
        });
        
        console.log(` [FILTRO] Filtro completado: ${mostradas} mostradas, ${ocultadas} ocultadas`);
    };

    // Función para abrir detalles de recibos
    function abrirDetallesRecibos(numeroPedido, prendaId, nombrePrenda, tipoRecibo, pedidoParcialId = null) {
        console.log(' [ABRIR DETALLES RECIBOS] ===== INICIANDO =====');
        console.log(' Parámetros recibidos:', {
            numeroPedido: numeroPedido,
            prendaId: prendaId,
            nombrePrenda: nombrePrenda,
            tipoRecibo: tipoRecibo,
            pedidoParcialId: pedidoParcialId
        });
        
        // Validar que tengamos el número de pedido
        if (!numeroPedido || numeroPedido === '' || numeroPedido === null || numeroPedido === undefined) {
            console.error(' ERROR: numeroPedido está vacío o undefined', numeroPedido);
            alert('Error: No se pudo determinar el número de pedido');
            return false;
        }
        
        // Convertir a string si es número
        const numeroPedidoStr = String(numeroPedido).trim();
        console.log(' numeroPedido normalizado:', numeroPedidoStr);
        
        // Construir la URL con prenda_id y tipo de recibo si se proporcionan
        let url = '/operario/pedido/' + numeroPedidoStr;
        const params = new URLSearchParams();
        
        if (prendaId) {
            params.append('prenda_id', prendaId);
            console.log(' Prenda ID:', prendaId);
        }
        
        if (tipoRecibo) {
            params.append('tipo_recibo', tipoRecibo);
            console.log(' Tipo de recibo:', tipoRecibo);
        }

        if (pedidoParcialId) {
            params.append('pedido_parcial_id', pedidoParcialId);
            console.log(' Pedido Parcial ID:', pedidoParcialId);
        }
        
        if (params.toString()) {
            url += '?' + params.toString();
        }
        
        console.log(' URL a navegar:', url);
        
        // Navegar
        try {
            console.log(' Iniciando navegación...');
            window.location.href = url;
            console.log(' Navegación iniciada exitosamente');
            return false;
        } catch (error) {
            console.error(' Error al navegar:', error);
            return false;
        }
    }

    // Función para abrir modal de novedades
    function abrirModalNovedad(numeroPedido, prendaId, nombrePrenda, numeroRecibo) {
        console.log(' Abriendo modal novedad', {numeroPedido, prendaId, nombrePrenda, numeroRecibo});
        
        const modal = document.getElementById('modalNovedad');
        if (!modal) {
            console.error('Modal no encontrado');
            return;
        }

        const tituloModal = document.getElementById('modalNovedadHeaderTitulo');
        if (tituloModal) {
            tituloModal.textContent = `NOVEDADES - PEDIDO #${numeroPedido} - RECIBO ${numeroRecibo}`;
        }
        
        // Configurar datos del modal
        document.getElementById('novedadNumeroPedido').value = numeroPedido;
        document.getElementById('novedadPrendaId').value = prendaId;
        document.getElementById('novedadPrendaNombre').textContent = nombrePrenda;
        document.getElementById('novedadReciboNumero').textContent = numeroRecibo;
        
        // Guardar el numero de recibo en un campo oculto para usarlo al guardar
        let hiddenRecibo = document.getElementById('novedadNumeroRecibo');
        if (!hiddenRecibo) {
            hiddenRecibo = document.createElement('input');
            hiddenRecibo.type = 'hidden';
            hiddenRecibo.id = 'novedadNumeroRecibo';
            hiddenRecibo.name = 'numero_recibo';
            document.getElementById('modalNovedad').appendChild(hiddenRecibo);
        }
        hiddenRecibo.value = numeroRecibo;
        
        // Cargar novedades existentes
        cargarNovedadesDelUsuario(numeroPedido, prendaId);
        
        // Mostrar modal
        modal.style.display = 'flex';
    }

    // Función para cargar novedades del usuario
    function cargarNovedadesDelUsuario(numeroPedido, prendaId) {
        console.log(' Cargando novedades', {numeroPedido, prendaId});
        
        fetch(`/operario/api/novedades/${numeroPedido}/${prendaId}`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            console.log(' Novedades cargadas:', data);
            mostrarNovedades(data.novedades || []);
        })
        .catch(error => {
            console.error(' Error cargando novedades:', error);
            const historial = document.getElementById('novedadesHistorial');
            if (historial) {
                historial.innerHTML = '<p style="color: #999;">Error cargando novedades</p>';
            }
        });
    }

    // Función para mostrar novedades
    function mostrarNovedades(novedades) {
        const historial = document.getElementById('novedadesHistorial');
        if (!historial) {
            console.error('Historial no encontrado');
            return;
        }
        
        if (novedades.length === 0) {
            historial.innerHTML = '<div style="padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.75rem; background: #f9fafb; color: #6b7280; font-size: 0.9rem;">No hay novedades registradas</div>';
            return;
        }
        
        let html = '';
        novedades.forEach(novedad => {
            const fecha = (novedad.creado_en || novedad.created_at || '').toString();
            const esMia = !!(novedad.es_mia ?? novedad.created_by_me ?? novedad.esPropia ?? false);
            const tipoRaw = (novedad.tipo_novedad || novedad.tipo || 'observacion').toString();
            const tipo = tipoRaw.toUpperCase();
            const usuarioNombre = (novedad.usuario_nombre || '').toString();
            const usuarioRol = (novedad.usuario_rol || '').toString();
            const descripcion = (novedad.descripcion || novedad.novedad_texto || '').toString();
            const descripcionEscaped = descripcion.replace(/'/g, "\\'");
            
            // Verificar si la novedad fue editada
            const editado = parseInt(novedad.editado || 0);
            let fechaEdicion = '';
            if (editado === 1 && novedad.editado_en) {
                fechaEdicion = novedad.editado_en.toString();
            }
            
            html += `
                <div style="padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.75rem; margin-bottom: 0.75rem; background: #f3f4f6;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                            <span style="background: #dbeafe; color: #1d4ed8; font-weight: 700; font-size: 0.7rem; padding: 0.25rem 0.6rem; border-radius: 0.5rem;">${tipo}</span>
                            ${editado === 1 ? '<span style="background: #fbbf24; color: #92400e; font-weight: 700; font-size: 0.7rem; padding: 0.25rem 0.6rem; border-radius: 0.5rem;">EDITADO</span>' : ''}
                            <span style="color: #6b7280; font-size: 0.85rem;">${usuarioNombre}</span>
                            ${usuarioRol ? `<span style="background: #e5e7eb; color: #374151; font-weight: 700; font-size: 0.7rem; padding: 0.25rem 0.6rem; border-radius: 0.5rem;">${usuarioRol}</span>` : ''}
                        </div>
                        <div style="color: #9ca3af; font-size: 0.8rem; white-space: nowrap;">${fecha}</div>
                    </div>
                    <div style="margin-top: 0.75rem; color: #374151; font-size: 0.95rem; line-height: 1.4;">${descripcion}</div>
                    ${editado === 1 && fechaEdicion ? `
                        <div style="margin-top: 0.5rem; color: #92400e; font-size: 0.75rem; font-style: italic;">Editado: ${fechaEdicion}</div>
                    ` : ''}
                    ${esMia ? `
                        <div style="margin-top: 0.75rem; display: flex; gap: 0.5rem;">
                            <button onclick="editarNovedad(${novedad.id}, '${descripcionEscaped}', '${tipoRaw}')" style="background: #3b82f6; color: white; border: none; border-radius: 0.375rem; padding: 0.35rem 0.8rem; cursor: pointer; font-weight: 600; font-size: 0.85rem;">Editar</button>
                            <button onclick="eliminarNovedad(${novedad.id})" style="background: #ef4444; color: white; border: none; border-radius: 0.375rem; padding: 0.35rem 0.8rem; cursor: pointer; font-weight: 600; font-size: 0.85rem;">Eliminar</button>
                        </div>
                    ` : ''}
                </div>
            `;
        });
        
        historial.innerHTML = html;
    }

    // Función para cerrar modal de novedades
    function cerrarModalNovedad() {
        const modal = document.getElementById('modalNovedad');
        if (modal) {
            modal.style.display = 'none';
            const textarea = document.getElementById('novedadDescripcionText');
            if (textarea) textarea.value = '';
        }
    }

    // Función para guardar novedad
    function guardarNovedad() {
        const textareaDescripcion = document.getElementById('novedadDescripcionText');
        
        if (!textareaDescripcion) {
            mostrarError('Error', 'Elementos del formulario no encontrados');
            return;
        }
        
        const descripcion = textareaDescripcion.value.trim();
        if (!descripcion) {
            mostrarError('Error', 'Debes describir la novedad');
            return;
        }
        
        const numeroPedido = document.getElementById('novedadNumeroPedido').value;
        const prendaId = document.getElementById('novedadPrendaId').value;
        const numeroRecibo = document.getElementById('novedadNumeroRecibo').value;
        
        const btnGuardar = document.getElementById('btnGuardarNovedad');
        const textoOriginal = btnGuardar.innerHTML;
        
        // Deshabilitar botón y mostrar loading
        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<span class="material-symbols-rounded" style="animation: spin 1s linear infinite;">refresh</span> Guardando...';
        
        fetch('/operario/api/novedades/crear', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                numero_pedido: numeroPedido,
                prenda_id: prendaId,
                numero_recibo: numeroRecibo,
                novedad_texto: descripcion,
                tipo_novedad: 'observacion'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                textareaDescripcion.value = '';
                cargarNovedadesDelUsuario(numeroPedido, prendaId);
                mostrarExito('Éxito', 'Novedad registrada correctamente');
            } else {
                mostrarError('Error', data.message || 'Error registrando novedad');
            }
        })
        .catch(error => {
            console.error('Error guardando novedad:', error);
            mostrarError('Error', 'Error guardando novedad');
        })
        .finally(() => {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = textoOriginal;
        });
    }

    function normalizarTipoNovedad(tipo) {
        const t = (tipo || 'observacion').toString().trim().toLowerCase();
        const permitidos = ['observacion', 'problema', 'cambio', 'correccion', 'aprobacion', 'rechazo'];
        return permitidos.includes(t) ? t : 'observacion';
    }

    function restaurarModoCrearNovedad() {
        const btnGuardar = document.getElementById('btnGuardarNovedad');
        if (btnGuardar) {
            btnGuardar.onclick = guardarNovedad;
            btnGuardar.textContent = 'Guardar Novedad';
        }

        const textarea = document.getElementById('novedadDescripcionText');
        if (textarea) {
            textarea.value = '';
        }

        const idEdit = document.getElementById('novedadEditId');
        if (idEdit) {
            idEdit.value = '';
        }
    }

    window.editarNovedad = function(novedadId, textoActual, tipoActual) {
        const textarea = document.getElementById('novedadDescripcionText');
        const btnGuardar = document.getElementById('btnGuardarNovedad');
        if (!textarea || !btnGuardar) {
            mostrarError('Error', 'No se pudo iniciar la edición');
            return;
        }

        let idEdit = document.getElementById('novedadEditId');
        if (!idEdit) {
            idEdit = document.createElement('input');
            idEdit.type = 'hidden';
            idEdit.id = 'novedadEditId';
            document.getElementById('modalNovedad')?.appendChild(idEdit);
        }
        idEdit.value = novedadId;

        textarea.value = (textoActual || '').toString();
        textarea.focus();

        btnGuardar.textContent = 'Actualizar Novedad';
        btnGuardar.onclick = function() {
            const descripcion = textarea.value.trim();
            if (!descripcion) {
                mostrarError('Error', 'Debes describir la novedad');
                return;
            }

            const numeroPedido = document.getElementById('novedadNumeroPedido')?.value;
            const prendaId = document.getElementById('novedadPrendaId')?.value;
            const tipo = normalizarTipoNovedad(tipoActual);

            const textoOriginal = btnGuardar.innerHTML;
            btnGuardar.disabled = true;
            btnGuardar.innerHTML = '<span class="material-symbols-rounded" style="animation: spin 1s linear infinite;">refresh</span> Actualizando...';

            fetch(`/operario/api/novedades/${novedadId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    novedad_texto: descripcion,
                    tipo_novedad: tipo
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    restaurarModoCrearNovedad();
                    if (numeroPedido && prendaId) {
                        cargarNovedadesDelUsuario(numeroPedido, prendaId);
                    }
                    mostrarExito('Éxito', 'Novedad actualizada correctamente');
                } else {
                    mostrarError('Error', data.message || 'Error actualizando novedad');
                }
            })
            .catch(err => {
                console.error('Error actualizando novedad:', err);
                mostrarError('Error', 'Error actualizando novedad');
            })
            .finally(() => {
                btnGuardar.disabled = false;
                btnGuardar.innerHTML = 'Actualizar Novedad';
            });
        };
    };

    window.eliminarNovedad = function(novedadId) {
        if (!confirm('¿Eliminar esta novedad?')) {
            return;
        }

        const numeroPedido = document.getElementById('novedadNumeroPedido')?.value;
        const prendaId = document.getElementById('novedadPrendaId')?.value;

        fetch(`/operario/api/novedades/${novedadId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                if (numeroPedido && prendaId) {
                    cargarNovedadesDelUsuario(numeroPedido, prendaId);
                }
                restaurarModoCrearNovedad();
                mostrarExito('Éxito', 'Novedad eliminada correctamente');
            } else {
                mostrarError('Error', data.message || 'Error eliminando novedad');
            }
        })
        .catch(err => {
            console.error('Error eliminando novedad:', err);
            mostrarError('Error', 'Error eliminando novedad');
        });
    };

    // Funciones para manejar costura
    function manejarPasarACostura(btn) {
        const pedidoId = btn.dataset.pedidoId;
        const prendaId = btn.dataset.prendaId;
        const nombre = btn.dataset.nombre;
        const tipoRecibo = btn.dataset.tipoRecibo;
        const recibo = btn.dataset.recibo;
        const area = btn.dataset.area;
        const procesoId = btn.dataset.procesoId;
        const encargadoCostura = btn.dataset.encargadoCostura;
        const btnId = btn.id;

        console.log(' Manejar pasar a costura:', {
            pedidoId, prendaId, nombre, tipoRecibo, recibo, area, procesoId, encargadoCostura, btnId
        });

        const esDeshacer = btn.classList.contains('btn-deshacer-costura');
        
        if (esDeshacer) {
            deshacerCosturaVista(pedidoId, prendaId, tipoRecibo, btnId);
        } else {
            abrirModalCostura(pedidoId, prendaId, nombre, tipoRecibo, recibo, btnId);
        }
    }

    function abrirModalCostura(pedidoId, prendaId, nombre, tipoRecibo, recibo, btnId) {
        const modal = document.getElementById('modalCostura');
        if (!modal) return;

        document.getElementById('costuraPrendaNombre').textContent = nombre;
        document.getElementById('costuraReciboNumero').textContent = recibo;
        document.getElementById('costuraTipoRecibo').textContent = tipoRecibo;
        
        // Cargar usuarios con rol 'costura'
        cargarUsuariosCostura(tipoRecibo);
        
        window.costuraPendiente = { pedidoId, prendaId, tipoRecibo, btnId, recibo };
        modal.style.display = 'flex';
    }

    function cargarUsuariosCostura(tipoRecibo = '') {
        const select = document.getElementById('costuraEncargado');
        select.innerHTML = '<option value="">Cargando...</option>';

        const qs = new URLSearchParams();
        const tr = String(tipoRecibo || '').trim().toUpperCase();
        if (tr) {
            qs.set('tipo_recibo', tr);
        }
        const url = qs.toString() ? `/api/usuarios/costura?${qs.toString()}` : '/api/usuarios/costura';
        
        fetch(url, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            select.innerHTML = '<option value="">Seleccione un encargado...</option>';
            if (data.success && data.usuarios) {
                data.usuarios.forEach(usuario => {
                    const option = document.createElement('option');
                    option.value = usuario.name;
                    option.textContent = usuario.name;
                    select.appendChild(option);
                });
            } else {
                select.innerHTML = '<option value="">No hay usuarios disponibles</option>';
            }
        })
        .catch(error => {
            console.error('Error cargando usuarios de costura:', error);
            select.innerHTML = '<option value="">Error al cargar usuarios</option>';
        });
    }

    function cerrarModalCostura() {
        const modal = document.getElementById('modalCostura');
        if (modal) modal.style.display = 'none';
        window.costuraPendiente = null;
    }

    function confirmarPasarACostura() {
        const encargado = document.getElementById('costuraEncargado').value.trim();
        if (!encargado) {
            mostrarError('Error', 'Debes seleccionar un encargado de costura');
            return;
        }

        if (!window.costuraPendiente) {
            mostrarError('Error', 'No hay datos de la prenda pendiente');
            return;
        }

        const { pedidoId, prendaId, tipoRecibo, btnId, recibo } = window.costuraPendiente;
        
        // Validar que todos los datos necesarios estén presentes
        if (!pedidoId || !prendaId || !tipoRecibo || !recibo) {
            mostrarError('Error', 'Faltan datos necesarios para procesar la solicitud');
            console.error('Datos incompletos:', { pedidoId, prendaId, tipoRecibo, recibo });
            return;
        }
        
        console.log('Datos del formulario:', {
            pedidoId,
            prendaId,
            tipoRecibo,
            recibo,
            encargado
        });

        const btn = document.getElementById(btnId);
        const originalHTML = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-rounded" style="animation: spin 1s linear infinite;">refresh</span> Procesando...';

        // Crear un formulario dinámico para evitar problemas con fetch
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/recibos-novedades/${pedidoId}/${recibo}/pasar-a-costura`;
        form.style.display = 'none';
        
        // Agregar CSRF token
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = document.querySelector('meta[name="csrf-token"]').content;
        form.appendChild(csrfToken);
        
        // Agregar datos como campos hidden
        console.log('Datos del formulario:', {
            pedidoId,
            prendaId,
            tipoRecibo,
            recibo,
            encargado
        });
        
        const prendaIdInput = document.createElement('input');
        prendaIdInput.type = 'hidden';
        prendaIdInput.name = 'prenda_id';
        prendaIdInput.value = prendaId;
        form.appendChild(prendaIdInput);
        
        const encargadoInput = document.createElement('input');
        encargadoInput.type = 'hidden';
        encargadoInput.name = 'encargado';
        encargadoInput.value = encargado;
        form.appendChild(encargadoInput);
        
        const tipoReciboInput = document.createElement('input');
        tipoReciboInput.type = 'hidden';
        tipoReciboInput.name = 'tipo_recibo';
        tipoReciboInput.value = tipoRecibo;
        form.appendChild(tipoReciboInput);
        
        console.log('Campos del formulario:');
        console.log('prenda_id:', prendaIdInput.value);
        console.log('encargado:', encargadoInput.value);
        console.log('tipo_recibo:', tipoReciboInput.value);
        
        // Agregar método spoofing si es necesario
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'POST';
        form.appendChild(methodInput);
        
        document.body.appendChild(form);
        
        // Enviar el formulario via AJAX para evitar redirección
        const formData = new FormData(form);
        
        fetch(form.action, {
            method: form.method,
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            console.log('Respuesta del servidor:', data);
            
            if (data.success) {
                btn.dataset.encargadoCostura = encargado;
                btn.dataset.procesoId = data.data.proceso_id || '';
                btn.classList.add('btn-deshacer-costura');
                btn.innerHTML = '<span class="material-symbols-rounded">undo</span> DESHACER COSTURA';
                cerrarModalCostura();
                mostrarExito('Éxito', data.message || 'Prenda asignada a costura correctamente');
            } else {
                btn.innerHTML = originalHTML;
                mostrarError('Error', data.message || 'Error asignando a costura');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            btn.innerHTML = originalHTML;
            mostrarError('Error', 'Error de conexión: ' + error.message);
        })
        .finally(() => {
            btn.disabled = false;
            document.body.removeChild(form);
        });
    }

    function deshacerCosturaVista(pedidoId, prendaId, tipoRecibo, btnId) {
        console.log('[DESHACER-COSTURA] Iniciando función:', {pedidoId, prendaId, tipoRecibo, btnId});
        
        const btn = document.getElementById(btnId);
        if (!btn || btn.disabled) return;

    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-rounded" style="animation: spin 1s linear infinite;">refresh</span> Deshaciendo...';

    console.log('[DESHACER-COSTURA] Enviando DELETE a:', `/recibos-novedades/${pedidoId}/${prendaId}/deshacer-costura`);

    // Usar la ruta correcta con parámetros en la URL
    fetch(`/recibos-novedades/${pedidoId}/${prendaId}/deshacer-costura`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            tipo_recibo: tipoRecibo
        })
    })
    .then(response => {
        console.log('[DESHACER-COSTURA] Respuesta recibida:', response.status, response.statusText);
        
        if (!response.ok) {
            // Si la respuesta no es OK, podría ser una redirección
            if (response.redirected) {
                console.error('[DESHACER-COSTURA] La petición fue redirigida a:', response.url);
            }
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return response.json();
    })
    .then(data => {
        console.log('[DESHACER-COSTURA] Datos recibidos:', data);
        if (data.success) {
            btn.classList.remove('btn-deshacer-costura');
            btn.dataset.encargadoCostura = '';
            btn.dataset.procesoId = '';
            btn.innerHTML = '<span class="material-symbols-rounded">checkroom</span> PASAR A COSTURA';
            mostrarExito('Éxito', 'Asignación a costura deshecha correctamente');
        } else {
            btn.innerHTML = originalHTML;
            mostrarError('Error', data.message || 'Error deshaciendo costura');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.innerHTML = originalHTML;
        mostrarError('Error', 'Error de conexión');
    })
    .finally(() => {
        btn.disabled = false;
    });
}

// Función para pasar a control de calidad
function pasarAControlCalidad(btn) {
    const pedidoId = btn.dataset.pedidoId;
    const prendaId = btn.dataset.prendaId;
    const nombre = btn.dataset.nombre;
    const tipoRecibo = btn.dataset.tipoRecibo;
    const recibo = btn.dataset.recibo;
    const area = btn.dataset.area;
    const procesoId = btn.dataset.procesoId;
    const btnId = btn.id;

    const esDeshacer = btn.textContent.includes('DESHACER');
        
    if (esDeshacer) {
        // DESHACER C.C
        const originalCCHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-rounded" style="animation: spin 1s linear infinite;">refresh</span> Deshaciendo...';
        btn.style.opacity = '0.6';
        btn.style.pointerEvents = 'none';

        fetch('/recibos-novedades/' + pedidoId + '/' + prendaId + '/deshacer-control-calidad', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                tipo_recibo: tipoRecibo
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const nuevoArea = data.data.area_nueva;
                btn.dataset.area = nuevoArea;
                btn.dataset.procesoId = '';
                btn.innerHTML = '<span class="material-symbols-rounded">check_circle</span> PASAR A C.C';
                console.log('✅ Control Calidad deshecho. Área restaurada a: ' + nuevoArea);
            } else {
                btn.innerHTML = originalHTML;
                mostrarError('Error', data.message || 'Error deshaciendo control de calidad');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            btn.innerHTML = originalHTML;
            mostrarError('Error', 'Error de conexión');
        })
        .finally(() => {
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.pointerEvents = '';
        });
    } else {
        // PASAR A C.C - crear proceso en Control de Calidad
        console.log('Pasando a Control de Calidad:', {pedidoId, prendaId, nombre, tipoRecibo, recibo});
        
        // Crear formulario dinámico para enviar a Control de Calidad
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/recibos-novedades/${pedidoId}/${recibo}/cambiar-area-control-calidad`;
        form.style.display = 'none';
        
        // Agregar CSRF token
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = document.querySelector('meta[name="csrf-token"]').content;
        form.appendChild(csrfToken);
        
        // Agregar datos como campos hidden
        const prendaIdInput = document.createElement('input');
        prendaIdInput.type = 'hidden';
        prendaIdInput.name = 'prenda_id';
        prendaIdInput.value = prendaId;
        form.appendChild(prendaIdInput);
        
        const tipoReciboInput = document.createElement('input');
        tipoReciboInput.type = 'hidden';
        tipoReciboInput.name = 'tipo_recibo';
        tipoReciboInput.value = tipoRecibo;
        form.appendChild(tipoReciboInput);
        
        document.body.appendChild(form);
        
        // Enviar el formulario via AJAX
        const formData = new FormData(form);
        
        fetch(form.action, {
            method: form.method,
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            console.log('Respuesta del servidor (Control Calidad):', data);
            
            if (data.success) {
                // Actualizar botón
                btn.dataset.area = 'Control Calidad';
                btn.dataset.procesoId = data.data.proceso_id || '';
                btn.innerHTML = '<span class="material-symbols-rounded">undo</span> DESHACER';
                mostrarExito('Éxito', data.message || 'Recibo enviado a Control de Calidad correctamente');
            } else {
                btn.innerHTML = originalHTML;
                mostrarError('Error', data.message || 'Error enviando a Control de Calidad');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            btn.innerHTML = originalHTML;
            mostrarError('Error', 'Error de conexión: ' + error.message);
        })
        .finally(() => {
            // Restaurar estado del botón
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.pointerEvents = '';
            document.body.removeChild(form);
        });
    }
}

    // Funciones de utilidad para modales
    function mostrarExito(titulo, texto = '') {
        mostrarMensaje(titulo, texto, 'exito', '✅');
    }

    function mostrarError(titulo, texto = '') {
        mostrarMensaje(titulo, texto, 'error', '❌');
    }

    function mostrarMensaje(titulo, texto, tipo = 'exito', icono = '✅') {
        const modal = document.getElementById('modalMensaje');
        const contenido = document.getElementById('modalMensajeContenido');
        const iconoEl = document.getElementById('modalMensajeIcono');
        const tituloEl = document.getElementById('modalMensajeTitulo');
        const textoEl = document.getElementById('modalMensajeTexto');

        if (!modal || !contenido) {
            console.error('Modal de mensaje no encontrado');
            return;
        }

        // Configurar contenido
        if (iconoEl) iconoEl.textContent = icono;
        if (tituloEl) tituloEl.textContent = titulo;
        if (textoEl) textoEl.textContent = texto;

        // Configurar estilos según tipo
        const colores = {
            exito: { bg: '#10b981', border: '#059669' },
            error: { bg: '#ef4444', border: '#dc2626' },
            info: { bg: '#3b82f6', border: '#2563eb' }
        };

        const color = colores[tipo] || colores.info;
        contenido.style.borderColor = color.border;

        // Crear botón de cerrar
        const boton = document.createElement('button');
        boton.textContent = 'CERRAR';
        boton.style.cssText = `
            background: ${color.bg};
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 1rem;
            transition: background 0.2s;
        `;
        boton.onmouseover = function() { this.style.background = color.border; };
        boton.onmouseout = function() { this.style.background = color.bg; };
        boton.onclick = cerrarModalMensaje;
        
        // Eliminar botón anterior si existe
        const botonAnterior = contenido.querySelector('button');
        if (botonAnterior) botonAnterior.remove();
        
        contenido.appendChild(boton);
        
        // Mostrar modal
        modal.style.display = 'flex';
    }

    function cerrarModalMensaje() {
        const modal = document.getElementById('modalMensaje');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    // Función para actualizar el contador de tarjetas
    function actualizarContadorTarjetas() {
        const contador = document.querySelector('.ordenes-count');
        if (contador) {
            const tarjetas = document.querySelectorAll('.orden-card-simple:not([style*="display: none"])');
            contador.textContent = tarjetas.length;
        }
    }

    // Cerrar modales al hacer click fuera
    window.addEventListener('click', function(event) {
        const modalNovedad = document.getElementById('modalNovedad');
        const modalCostura = document.getElementById('modalCostura');
        const modalMensaje = document.getElementById('modalMensaje');
        
        if (modalNovedad && event.target === modalNovedad) {
            cerrarModalNovedad();
        }
        if (modalCostura && event.target === modalCostura) {
            cerrarModalCostura();
        }
        if (modalMensaje && event.target === modalMensaje) {
            cerrarModalMensaje();
        }
    });

    // Función para toggle de acciones mobile
    window.toggleMobileActions = function(prendaId) {
        const drawer = document.getElementById(`mobile-drawer-${prendaId}`);
        const toggleBtns = document.querySelectorAll(`.mobile-actions-toggle[onclick*="${prendaId}"]`);
        
        if (!drawer) {
            console.warn(`No se encontró el drawer mobile-drawer-${prendaId}`);
            return;
        }
        
        const isActive = drawer.classList.contains('active');
        
        // Cerrar todos los demás drawers
        document.querySelectorAll('.mobile-actions-drawer.active').forEach(d => {
            if (d.id !== `mobile-drawer-${prendaId}`) {
                d.classList.remove('active');
            }
        });
        
        // Quitar clase active de todos los botones excepto los del prenda actual
        document.querySelectorAll('.mobile-actions-toggle.active').forEach(btn => {
            if (!btn.onclick || !btn.onclick.toString().includes(prendaId)) {
                btn.classList.remove('active');
            }
        });
        
        // Abrir/cerrar el drawer actual
        if (!isActive) {
            drawer.classList.add('active');
            toggleBtns.forEach(btn => btn.classList.add('active'));
        } else {
            drawer.classList.remove('active');
            toggleBtns.forEach(btn => btn.classList.remove('active'));
        }
    };

    // Función para cortadores: Marcar como completado (pasa a Costura)
    window.completarCorte = function(btn) {
        const reciboId = btn.dataset.reciboId;
        const pedidoId = btn.dataset.pedidoId;
        const prendaId = btn.dataset.prendaId;
        const nombre = btn.dataset.nombre;
        const card = btn.closest('.orden-card-simple');
        
        if (!reciboId) {
            return;
        }
        
        // Deshabilitar botón y mostrar loading
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-rounded spin">refresh</span> PROCESANDO...';
        
        // Enviar solicitud AJAX a la ruta correcta
        fetch(`/operario/api/recibos/${reciboId}/completar`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar la interfaz dinámicamente
                actualizarInterfazCorte(card, 'completado', btn);
                
                // Actualizar también el drawer mobile si existe
                const drawerBtn = document.querySelector(`#mobile-drawer-${prendaId} .btn-completar-corte[data-recibo-id="${reciboId}"]`);
                if (drawerBtn) {
                    actualizarInterfazCorte(drawerBtn.closest('.mobile-actions-drawer'), 'completado', drawerBtn);
                }
            } else {
                // En caso de error, restaurar botón
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error completando corte:', error);
            // Restaurar botón en caso de error
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    };
    
    // Función para cortadores: Deshacer (regresa a Corte)
    window.deshacerCorte = function(btn) {
        const reciboId = btn.dataset.reciboId;
        const pedidoId = btn.dataset.pedidoId;
        const prendaId = btn.dataset.prendaId;
        const nombre = btn.dataset.nombre;
        const card = btn.closest('.orden-card-simple');
        
        if (!reciboId) {
            return;
        }
        
        // Deshabilitar botón y mostrar loading
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-rounded spin">refresh</span> PROCESANDO...';
        
        // Enviar solicitud AJAX a la ruta correcta
        fetch(`/operario/api/recibos/${reciboId}/deshacer`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar la interfaz dinámicamente
                actualizarInterfazCorte(card, 'deshacer', btn);
                
                // Actualizar también el drawer mobile si existe
                const drawerBtn = document.querySelector(`#mobile-drawer-${prendaId} .btn-deshacer-corte[data-recibo-id="${reciboId}"]`);
                if (drawerBtn) {
                    actualizarInterfazCorte(drawerBtn.closest('.mobile-actions-drawer'), 'deshacer', drawerBtn);
                }
            } else {
                // En caso de error, restaurar botón
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error deshaciendo corte:', error);
            // Restaurar botón en caso de error
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    };
    
    // Función para actualizar la interfaz dinámicamente
    function actualizarInterfazCorte(container, accion, btnActual) {
        if (!container) return;
        
        const prendaId = btnActual.dataset.prendaId;
        const reciboId = btnActual.dataset.reciboId;
        const nombre = btnActual.dataset.nombre;
        
        if (accion === 'completado') {
            // Cambiar botón de completar a deshacer
            const nuevoBtn = document.createElement('button');
            nuevoBtn.className = 'btn-deshacer-corte';
            nuevoBtn.setAttribute('data-pedido-id', btnActual.dataset.pedidoId);
            nuevoBtn.setAttribute('data-prenda-id', prendaId);
            nuevoBtn.setAttribute('data-recibo-id', reciboId);
            nuevoBtn.setAttribute('data-nombre', nombre);
            nuevoBtn.setAttribute('onclick', 'deshacerCorte(this)');
            nuevoBtn.innerHTML = '<span class="material-symbols-rounded">undo</span> DESHACER';
            
            // Reemplazar botón en el contenedor
            if (btnActual.parentNode) {
                btnActual.parentNode.replaceChild(nuevoBtn, btnActual);
            }
            
            // Actualizar badges de estado si existen
            const badges = container.querySelectorAll('.badge-completado-corte, .badge-estado');
            badges.forEach(badge => {
                badge.classList.add('is-on');
                badge.textContent = 'COMPLETADO';
            });
            
        } else if (accion === 'deshacer') {
            // Cambiar botón de deshacer a completar
            const nuevoBtn = document.createElement('button');
            nuevoBtn.className = 'btn-completar-corte';
            nuevoBtn.setAttribute('data-pedido-id', btnActual.dataset.pedidoId);
            nuevoBtn.setAttribute('data-prenda-id', prendaId);
            nuevoBtn.setAttribute('data-recibo-id', reciboId);
            nuevoBtn.setAttribute('data-nombre', nombre);
            nuevoBtn.setAttribute('onclick', 'completarCorte(this)');
            nuevoBtn.innerHTML = '<span class="material-symbols-rounded">check_circle</span> MARCAR COMPLETADO';
            
            // Reemplazar botón en el contenedor
            if (btnActual.parentNode) {
                btnActual.parentNode.replaceChild(nuevoBtn, btnActual);
            }
            
            // Actualizar badges de estado si existen
            const badges = container.querySelectorAll('.badge-completado-corte, .badge-estado');
            badges.forEach(badge => {
                badge.classList.remove('is-on');
                badge.textContent = 'PENDIENTE';
            });
        }
    }
    
    // Función para costureros: Marcar como completado (sin cambiar de área)
    window.completarCostura = function(btn) {
        const reciboId = btn.dataset.reciboId;
        const pedidoId = btn.dataset.pedidoId;
        const prendaId = btn.dataset.prendaId;
        const nombre = btn.dataset.nombre;
        const card = btn.closest('.orden-card-simple');
        
        if (!reciboId) {
            return;
        }
        
        // Deshabilitar botón y mostrar loading
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-rounded spin">refresh</span> PROCESANDO...';
        
        // Enviar solicitud AJAX a la misma ruta que usa el cortador
        fetch(`/operario/api/recibos/${reciboId}/completar`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar la interfaz dinámicamente
                actualizarInterfazCostura(card, 'completado', btn);
                
                // Actualizar también el drawer mobile si existe
                const drawerBtn = document.querySelector(`#mobile-drawer-${prendaId} .btn-completar-costura[data-recibo-id="${reciboId}"]`);
                if (drawerBtn) {
                    actualizarInterfazCostura(drawerBtn.closest('.mobile-actions-drawer'), 'completado', drawerBtn);
                }
                
                // Actualizar/crear badge de completado en el card principal
                asegurarBadgeCompletado(card, true);
            } else {
                // En caso de error, restaurar botón
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error completando costura:', error);
            // Restaurar botón en caso de error
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    };
    
    // Función para costureros: Deshacer (regresa a pendiente)
    window.deshacerCostura = function(btn) {
        // Validar que btn sea un elemento válido
        if (!btn || !btn.dataset) {
            console.error('[DESHACER-COSTURA] Botón inválido o sin dataset');
            return;
        }
        
        const reciboId = btn.dataset.reciboId;
        const pedidoId = btn.dataset.pedidoId;
        const prendaId = btn.dataset.prendaId;
        const nombre = btn.dataset.nombre;
        const card = btn.closest('.orden-card-simple');
        
        if (!reciboId) {
            console.error('[DESHACER-COSTURA] No se encontró reciboId en el botón');
            return;
        }
        
        // Deshabilitar botón y mostrar loading
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-rounded spin">refresh</span> PROCESANDO...';
        
        // Enviar solicitud AJAX a la misma ruta que usa el cortador
        fetch(`/operario/api/recibos/${reciboId}/deshacer`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar la interfaz dinámicamente
                actualizarInterfazCostura(card, 'deshacer', btn);
                
                // Actualizar también el drawer mobile si existe
                const drawerBtn = document.querySelector(`#mobile-drawer-${prendaId} .btn-deshacer-costura[data-recibo-id="${reciboId}"]`);
                if (drawerBtn) {
                    actualizarInterfazCostura(drawerBtn.closest('.mobile-actions-drawer'), 'deshacer', drawerBtn);
                }
                
                // Actualizar/quitar badge de completado en el card principal
                asegurarBadgeCompletado(card, false);
            } else {
                // En caso de error, restaurar botón
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error deshaciendo costura:', error);
            // Restaurar botón en caso de error
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    };
    
    // Función para actualizar la interfaz de costura dinámicamente
    function actualizarInterfazCostura(container, accion, btnActual) {
        if (!container) return;
        
        const prendaId = btnActual.dataset.prendaId;
        const reciboId = btnActual.dataset.reciboId;
        const nombre = btnActual.dataset.nombre;
        
        if (accion === 'completado') {
            // Cambiar fondo del card a azul claro
            const card = container.closest('.orden-card-simple') || container;
            if (card) {
                card.classList.add('card-completado-costura');
            }
            
            // Cambiar botón de completar a deshacer
            const nuevoBtn = document.createElement('button');
            nuevoBtn.className = 'btn-deshacer-costura';
            nuevoBtn.setAttribute('data-pedido-id', btnActual.dataset.pedidoId);
            nuevoBtn.setAttribute('data-prenda-id', prendaId);
            nuevoBtn.setAttribute('data-recibo-id', reciboId);
            nuevoBtn.setAttribute('data-nombre', nombre);
            nuevoBtn.setAttribute('onclick', 'deshacerCostura(this)');
            nuevoBtn.innerHTML = '<span class="material-symbols-rounded">undo</span> DESHACER';
            
            // Reemplazar botón en el contenedor
            if (btnActual.parentNode) {
                btnActual.parentNode.replaceChild(nuevoBtn, btnActual);
            }
            
            // Actualizar badge de completado si existe
            const badges = container.querySelectorAll('.badge-completado-costura');
            badges.forEach(badge => {
                badge.classList.add('is-on');
                badge.textContent = 'COMPLETADO';
            });
            
        } else if (accion === 'deshacer') {
            // Quitar fondo azul claro del card
            const card = container.closest('.orden-card-simple') || container;
            if (card) {
                card.classList.remove('card-completado-costura');
            }
            
            // Cambiar botón de deshacer a completar
            const nuevoBtn = document.createElement('button');
            nuevoBtn.className = 'btn-completar-costura';
            nuevoBtn.setAttribute('data-pedido-id', btnActual.dataset.pedidoId);
            nuevoBtn.setAttribute('data-prenda-id', prendaId);
            nuevoBtn.setAttribute('data-recibo-id', reciboId);
            nuevoBtn.setAttribute('data-nombre', nombre);
            nuevoBtn.setAttribute('onclick', 'completarCostura(this)');
            nuevoBtn.innerHTML = '<span class="material-symbols-rounded">check_circle</span> COMPLETAR';
            
            // Reemplazar botón en el contenedor
            if (btnActual.parentNode) {
                btnActual.parentNode.replaceChild(nuevoBtn, btnActual);
            }
            
            // Actualizar badge de completado si existe
            const badges = container.querySelectorAll('.badge-completado-costura');
            badges.forEach(badge => {
                badge.classList.remove('is-on');
                badge.textContent = 'PENDIENTE';
            });
        }
    }

    function asegurarBadgeCompletado(card, estaCompletado) {
        if (!card) return;

        const badgesExistentes = card.querySelectorAll('.badge-completado-costura');

        if (estaCompletado) {
            if (badgesExistentes.length > 0) {
                badgesExistentes.forEach(badge => {
                    badge.classList.add('is-on');
                    badge.textContent = 'COMPLETADO';
                });
                return;
            }

            const contenedorNumero = card.querySelector('.orden-numero-section');
            const estadoBadge = contenedorNumero ? contenedorNumero.querySelector('.estado-badge') : null;
            if (!contenedorNumero || !estadoBadge) {
                return;
            }

            const badgeNuevo = document.createElement('span');
            badgeNuevo.className = 'badge-completado-costura is-on';
            badgeNuevo.textContent = 'COMPLETADO';

            estadoBadge.insertAdjacentElement('afterend', badgeNuevo);
        } else {
            badgesExistentes.forEach(badge => {
                badge.remove();
            });
        }
    }

    // Cerrar drawers al hacer click fuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.mobile-actions-toggle') && !e.target.closest('.mobile-actions-drawer')) {
            document.querySelectorAll('.mobile-actions-drawer.active').forEach(d => {
                d.classList.remove('active');
            });
            document.querySelectorAll('.mobile-actions-toggle.active').forEach(btn => {
                btn.classList.remove('active');
            });
        }
    });

    // Agregar estilos para animación de spin
    const style = document.createElement('style');
    style.textContent = `
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* Estilos para cards completados por costurero */
        .card-completado-costura {
            background-color: #e3f2fd !important;
            border-left: 4px solid #2196f3 !important;
            box-shadow: 0 2px 8px rgba(33, 150, 243, 0.1) !important;
        }
        
        /* Borde verde para recibos reflectivos */
        .borde-reflectivo {
            border-left: 4px solid #4caf50 !important;
            box-shadow: 0 2px 8px rgba(76, 175, 80, 0.2) !important;
        }
        
        /* Botones para costureros */
        .btn-completar-costura {
            background: linear-gradient(135deg, #2196f3, #1976d2);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }
        
        .btn-completar-costura:hover {
            background: linear-gradient(135deg, #1976d2, #1565c0);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
        }
        
        .btn-deshacer-costura {
            background: linear-gradient(135deg, #ff9800, #f57c00);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }
        
        .btn-deshacer-costura:hover {
            background: linear-gradient(135deg, #f57c00, #ef6c00);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
        }
        
        /* Badge para estado completado de costura */
        .badge-completado-costura {
            background: #2196f3;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-completado-costura.is-on {
            background: #1976d2;
            box-shadow: 0 2px 4px rgba(33, 150, 243, 0.3);
        }
        
        /* Posicionamiento especial para mobile */
        .badge-completado-costura.mobile-top-right {
            position: absolute;
            top: 8px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
            font-size: 0.65rem;
            padding: 0.2rem 0.6rem;
            display: none; /* Oculto por defecto, solo visible en mobile */
        }
        
        /* Sección del botón ver recibo para mobile */
        .mobile-ver-recibo-section {
            display: none;
            margin: 8px 0;
            text-align: center;
        }
        
        .mobile-ver-recibo-section .btn-ver-recibos {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        /* Responsive para mobile */
        @media (max-width: 768px) {
            .orden-top {
                position: relative;
            }
            
            .mobile-ver-recibo-section {
                display: block;
            }
            
            /* Mostrar badge completado en esquina superior derecha solo en mobile */
            .badge-completado-costura.mobile-top-right {
                display: inline-block;
            }
            
            /* Ocultar badge completado del desktop en mobile */
            .orden-numero-section .badge-completado-costura:not(.mobile-top-right) {
                display: none;
            }
            
            /* Estilos específicos para botón ver recibo en mobile - EXCEPTO para vista-costura */
            .mobile-ver-recibo-section .btn-ver-recibos:not(.vista-costura-mobile) {
                border-radius: 12px !important;
                border: 1px solid #e0e0e0 !important;
                box-shadow: none !important;
            }
            
            /* Ocultar botones ver recibo originales en mobile - EXCEPTO para vista-costura */
            .orden-buttons .btn-ver-recibos:not(.mobile-under-state):not(.vista-costura-mobile) {
                display: none;
            }
            
            /* Para vista-costura, ocultar el botón mobile y mostrar el original */
            body[data-user-role="vista-costura"] .mobile-ver-recibo-section {
                display: none !important;
            }
            
            body[data-user-role="vista-costura"] .orden-buttons .btn-ver-recibos {
                display: inline-flex !important;
            }
            
            .btn-completar-costura,
            .btn-deshacer-costura {
                padding: 0.75rem;
                font-size: 0.8rem;
                min-height: 44px;
                justify-content: center;
            }
            
            .btn-completar-costura span,
            .btn-deshacer-costura span {
                font-size: 1.2rem;
            }
        }
    `;
    document.head.appendChild(style);
</script>

<!-- Modales -->
<!-- Modal de Mensaje Genérico -->
<div id="modalMensaje" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div id="modalMensajeContenido" style="background: white; padding: 2rem; border-radius: 12px; max-width: 400px; width: 90%; text-align: center; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
        <div id="modalMensajeIcono" style="font-size: 3rem; margin-bottom: 1rem;"></div>
        <h3 id="modalMensajeTitulo" style="margin: 0 0 1rem 0; font-size: 1.25rem; font-weight: 600;"></h3>
        <p id="modalMensajeTexto" style="margin: 0 0 1.5rem 0; color: #666;"></p>
    </div>
</div>

<!-- Modal de Novedades -->
<div id="modalNovedad" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; max-width: 760px; width: 92%; max-height: 85vh; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.25);">
        <div style="background: #111827; color: white; padding: 1rem 1.25rem; display: flex; align-items: center; justify-content: space-between;">
            <div id="modalNovedadHeaderTitulo" style="font-weight: 800; letter-spacing: 0.5px; font-size: 0.95rem; text-transform: uppercase;">NOVEDADES</div>
            <button type="button" onclick="cerrarModalNovedad()" style="background: transparent; border: none; color: white; cursor: pointer; font-size: 1.25rem; line-height: 1; padding: 0.25rem;">×</button>
        </div>

        <div style="padding: 1.25rem; overflow-y: auto; max-height: calc(85vh - 56px);">
            <input type="hidden" id="novedadNumeroPedido">
            <input type="hidden" id="novedadPrendaId">

            <div style="margin-bottom: 1rem;">
                <div style="color: #111827; font-weight: 700; font-size: 0.95rem; margin-bottom: 0.5rem;">Historial:</div>
                <div id="novedadesHistorial" style="max-height: 220px; overflow-y: auto; padding-right: 0.25rem;"></div>
            </div>

            <div style="height: 1px; background: #e5e7eb; margin: 1rem 0;"></div>

            <div style="color: #111827; font-weight: 800; font-size: 1rem; margin-bottom: 0.75rem;">Agregar Nueva Novedad:</div>

            <div style="margin-bottom: 1rem;">
                <textarea id="novedadDescripcionText" rows="5" style="width: 100%; padding: 0.9rem; border: 1px solid #d1d5db; border-radius: 10px; resize: vertical; font-size: 0.95rem;" placeholder="Escribe tu novedad aquí..."></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <button type="button" id="btnGuardarNovedad" onclick="guardarNovedad()" style="padding: 0.85rem 1rem; background: #22c55e; color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 800;">Guardar Novedad</button>
                <button type="button" onclick="cerrarModalNovedad()" style="padding: 0.85rem 1rem; background: #94a3b8; color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 800;">Cancelar</button>
            </div>

            <div style="display: none;">
                <div id="novedadPrendaNombre"></div>
                <div id="novedadReciboNumero"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Costura -->
<div id="modalCostura" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; padding: 2rem; border-radius: 12px; max-width: 500px; width: 90%;">
        <h3 style="margin: 0 0 1.5rem 0; font-size: 1.25rem; font-weight: 600;">Asignar a Costura</h3>
        
        <div style="margin-bottom: 1rem;">
            <p style="margin: 0 0 0.5rem 0; color: #666;">Prenda: <strong id="costuraPrendaNombre"></strong></p>
            <p style="margin: 0 0 0.5rem 0; color: #666;">Recibo: <strong id="costuraReciboNumero"></strong></p>
            <p style="margin: 0 0 1rem 0; color: #666;">Tipo: <strong id="costuraTipoRecibo"></strong></p>
        </div>
        
        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Encargado de Costura:</label>
            <select id="costuraEncargado" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; background: white;">
                <option value="">Seleccione un encargado...</option>
            </select>
        </div>
        
        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
            <button type="button" onclick="cerrarModalCostura()" style="padding: 0.75rem 1.5rem; border: 1px solid #ddd; background: white; border-radius: 8px; cursor: pointer;">Cancelar</button>
            <button type="button" onclick="confirmarPasarACostura()" style="padding: 0.75rem 1.5rem; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer;">Asignar</button>
        </div>
    </div>
</div>

@endsection
