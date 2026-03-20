import { actualizarContadorTarjetas } from '../ui/counters';
import { asegurarBadgeCompletado } from '../ui/badges';

function shouldShowDashboardNotif(key) {
    try {
        if (!key) return true;

        const now = Date.now();
        const ttlMs = 12000;

        window.__operarioDashboardNotifsSeen = window.__operarioDashboardNotifsSeen || {};
        const seen = window.__operarioDashboardNotifsSeen;

        for (const k of Object.keys(seen)) {
            if (now - (seen[k] || 0) > ttlMs) {
                delete seen[k];
            }
        }

        if (seen[key]) {
            return false;
        }

        seen[key] = now;
        return true;
    } catch (e) {
        return true;
    }
}

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

export function initRealtimeListeners() {
    if (window.__operarioDashboardRealtimeInitStarted) {
        return;
    }
    window.__operarioDashboardRealtimeInitStarted = true;

    console.log('[Operario Dashboard] Iniciando escuchadores de tiempo real (Phase 5)');

    // CRÍTICO: Esperar a que window.shared esté disponible (race condition fix)
    if (!window.shared?.isReady) {
        console.log('[Operario Dashboard] Esperando window.shared.isReady...');
        setTimeout(initRealtimeListeners, 50);
        return;
    }

    // Usar window.waitForEcho para esperar WebSocket disponible
    if (typeof window.waitForEcho !== 'function') {
        console.warn('[Operario Dashboard] waitForEcho no disponible, reintentando...');
        setTimeout(initRealtimeListeners, 500);
        return;
    }

    window.waitForEcho(() => {
        const ws = window.shared?.websocket;
        if (!ws) {
            console.error('[Operario Dashboard] WebSocket abstraction no disponible');
            return;
        }

        try {
            // Verificar disponibilidad de datos globales
            if (!window.USUARIO_ACTUAL?.id) {
                console.warn('[Operario Dashboard] USUARIO_ACTUAL no disponible');
                return;
            }

            console.log('[Operario Dashboard] Configurando listeners con WebSocket', {
                usuario: window.USUARIO_ACTUAL,
                privateChannel: `App.Models.User.${window.USUARIO_ACTUAL.id}`,
                publicChannels: ['operarios.corte', 'recibos-costura'],
            });

            // Canal privado del usuario
            setupPrivateUserChannel(ws);
            
            // Canales públicos
            setupPublicChannels(ws);
            
            console.log('[Operario Dashboard] ✅ Listeners configurados');
        } catch (error) {
            console.error('[Operario Dashboard] Error al configurar listeners:', error);
        }
    });
}

function setupPrivateUserChannel(ws) {
    const userId = window.USUARIO_ACTUAL.id;
    
    ws.subscribe(`private-App.Models.User.${userId}`, '.operario.recibos.actualizados', (e) => {
        console.log('[Operario Dashboard] Evento operario.recibos.actualizados recibido (privado):', e);

        const accionesNotificar = [
            'asignado',
            'recibo_asignado_reflectivo',
            'recibo_asignado_costura',
            'recibo_completado',
            'recibo_deshecho',
        ];
        if (accionesNotificar.includes(e?.accion) && e?.mensaje) {
            const dedupeKey = [
                'private',
                String(e?.accion || ''),
                String(e?.prenda_id || ''),
                String(e?.id_recibo || e?.recibo_id || ''),
                String(e?.numero_recibo || e?.consecutivo || ''),
                String(e?.updated_at || e?.proceso_updated_at || ''),
                String(e?.mensaje || ''),
            ].join('|');

            if (!shouldShowDashboardNotif(dedupeKey)) {
                actualizarListaSinRecargar();
                return;
            }

            const tipoRecibo = String(e?.tipo_recibo || '').toUpperCase();
            let icono = 'checkroom';
            let tipoNotif = 'info';

            if (e?.accion === 'recibo_completado') {
                icono = 'check_circle';
                tipoNotif = 'success';
            } else if (e?.accion === 'recibo_deshecho') {
                icono = 'undo';
                tipoNotif = 'warning';
            } else if (tipoRecibo === 'REFLECTIVO') {
                icono = 'auto_awesome';
            }

            let titulo = 'Recibo de ' + (tipoRecibo || 'Costura') + ' asignado';
            if (e?.accion === 'recibo_completado') {
                titulo = 'Recibo completado';
            } else if (e?.accion === 'recibo_deshecho') {
                titulo = 'Recibo deshecho';
            }

            const notifStableId = [
                String(e?.accion || 'evento'),
                String(e?.id_recibo || e?.recibo_id || e?.numero_recibo || e?.consecutivo || ''),
                String(e?.prenda_id || ''),
            ].join('|');

            if (window.NotificacionesPush && typeof window.NotificacionesPush.add === 'function') {
                window.NotificacionesPush.add({
                    id: notifStableId,
                    title: titulo,
                    message: e.mensaje,
                    type: tipoNotif,
                    icon: icono,
                    duration: 8000,
                });
            } else if ('Notification' in window && Notification.permission === 'granted') {
                new Notification(titulo, {
                    body: e.mensaje,
                    icon: '/icon.png',
                });
            }
        }

        if (e?.accion === 'recibo_completado' && e?.prenda_id) {
            const card = document.querySelector(`.orden-card-simple[data-prenda-id="${e.prenda_id}"]`);
            if (card) {
                card.classList.add('card-completado-costura');
                const body = card.querySelector('.orden-body');
                if (body) {
                    body.classList.add('recibo-completado-area');
                }
                asegurarBadgeCompletado(card, true);
            }
        }

        if (e?.accion === 'recibo_deshecho' && e?.prenda_id) {
            const card = document.querySelector(`.orden-card-simple[data-prenda-id="${e.prenda_id}"]`);
            if (card) {
                card.classList.remove('card-completado-costura');
                const body = card.querySelector('.orden-body');
                if (body) {
                    body.classList.remove('recibo-completado-area');
                }
                asegurarBadgeCompletado(card, false);
            }
        }

        actualizarListaSinRecargar();
    });
}

function setupPublicChannels(ws) {
    // Canal operarios.corte - disponible para cortadores y administradores
    ws.subscribe('operarios.corte', '.corte.asignado', (e) => {
        const encargadoEvento = String(e?.encargado || '').trim().toLowerCase();
        const nombreActual = String(window.USUARIO_ACTUAL?.nombre || '').trim().toLowerCase();

        console.log('[Operario Dashboard] Evento corte.asignado recibido:', e);

        if (encargadoEvento && nombreActual && encargadoEvento === nombreActual) {
            console.log('[Operario Dashboard] Coincide encargado con usuario, actualizando lista');
            actualizarListaSinRecargar();
        }
    });
    
    ws.subscribe('operarios.corte', '.encargado.costura.asignado', (e) => {
        console.log('[Operario Dashboard] Evento encargado.costura.asignado recibido:', e);

        if (String(window.USUARIO_ACTUAL?.rol || '').toLowerCase() === 'cortador') {
            const prendaId = e?.prenda_id;
            if (prendaId) {
                const card = document.querySelector(`.orden-card-simple[data-prenda-id="${prendaId}"]`);
                if (card) {
                    console.log(`[Operario Dashboard] Eliminando tarjeta prenda_id: ${prendaId}`);
                    card.remove();
                    actualizarContadorTarjetas();
                }
            }
        }

        if (String(window.USUARIO_ACTUAL?.rol || '').toLowerCase() === 'administrador-costura') {
            const encargadoRol = String(e?.encargado_rol || '').trim().toLowerCase();
            if (encargadoRol === 'costura-reflectivo') {
                actualizarListaSinRecargar();
                return;
            }

            try {
                window.__adminCosturaNotifsSeen = window.__adminCosturaNotifsSeen || {};
                const encargadoStr = String(e?.encargado || '').trim();
                const encargadoLower = encargadoStr.toLowerCase();
                const pareceModuloOCosturero =
                    encargadoLower.includes('modulo') ||
                    encargadoLower.includes('módulo') ||
                    encargadoLower.includes('costur') ||
                    encargadoLower.includes('sobremedida');
                
                if (pareceModuloOCosturero) {
                    const notifKey = `${e?.proceso_id || ''}|${e?.proceso_updated_at || ''}|${e?.pedido_id || ''}|${e?.numero_recibo || ''}`;
                    if (!window.__adminCosturaNotifsSeen[notifKey]) {
                        window.__adminCosturaNotifsSeen[notifKey] = Date.now();
                        const mensaje = `Pedido #${e?.pedido_id || ''} · Recibo #${e?.numero_recibo || ''} asignado a ${encargadoStr}`;
                        
                        if (window.NotificacionesPush && typeof window.NotificacionesPush.add === 'function') {
                            window.NotificacionesPush.add({
                                id: e?.consecutivo_recibo_id || undefined,
                                title: 'Recibo asignado a costura',
                                message: mensaje,
                                type: 'info',
                                icon: 'checkroom',
                                duration: 8000,
                            });
                        }
                    }
                }
            } catch (err) {
                console.warn('[Operario Dashboard] Error en notificación admin-costura:', err);
            }

            actualizarListaSinRecargar();
        }
    });

    // Canal recibos-costura - para vista-costura y administrador-costura
    const rol = String(window.USUARIO_ACTUAL?.rol || '').toLowerCase();
    
    if (rol === 'vista-costura') {
        ws.subscribe('recibos-costura', '.recibo.aprobado', (e) => {
            console.log('[Operario Dashboard] Evento recibo.aprobado recibido:', e);
        });
        
        ws.subscribe('recibos-costura', '.recibo.completado', (e) => {
            console.log('[Operario Dashboard] Evento recibo.completado recibido:', e);
            try {
                if (window.NotificacionesPush && typeof window.NotificacionesPush.add === 'function') {
                    const dedupeKey = [
                        'public',
                        'recibo.completado',
                        String(e?.prenda_id || ''),
                        String(e?.recibo_id || ''),
                        String(e?.updated_at || e?.created_at || ''),
                    ].join('|');

                    if (!shouldShowDashboardNotif(dedupeKey)) {
                        actualizarListaSinRecargar();
                        return;
                    }

                    const area = String(e?.area || '').trim();
                    const operario = String(e?.nombre_operario || '').trim();
                    let titulo = 'Recibo completado';
                    if (area) titulo += ` en ${area}`;

                    window.NotificacionesPush.add({
                        id: `recibo_completado|${e?.recibo_id || ''}|${e?.prenda_id || ''}`,
                        title: titulo,
                        message: operario ? `por ${operario}` : 'Completado',
                        type: 'success',
                        icon: 'check_circle',
                        duration: 5000,
                    });
                }
            } catch (err) {
                console.warn('[Operario Dashboard] Error creando notificación push:', err);
            }
            actualizarListaSinRecargar();
        });
    }

    if (rol === 'administrador-costura') {
        ws.subscribe('recibos-costura', '.encargado.costura.asignado', (e) => {
            console.log('[Operario Dashboard] Admin-costura evento encargado.costura.asignado');
            actualizarListaSinRecargar();
        });
        
        ws.subscribe('recibos-costura', '.encargado.costura.deshacer', (e) => {
            console.log('[Operario Dashboard] Admin-costura evento encargado.costura.deshacer');
            actualizarListaSinRecargar();
        });
        
        ws.subscribe('recibos-costura', '.operario.recibos.actualizados', (e) => {
            console.log('[Operario Dashboard] Admin-costura evento operario.recibos.actualizados (canal público)');
            actualizarListaSinRecargar();
        });
    }

    if (rol === 'costurero') {
        const nombreCosturero = String(window.USUARIO_ACTUAL?.nombre || '').trim();
        if (nombreCosturero) {
            const nombreNormalizado = nombreCosturero.toLowerCase().replace(/[^a-zA-Z0-9]/g, '_');
            console.log(`[Operario Dashboard] Costurero suscribiendo a canales`);

            ws.subscribe('recibos-costura', '.nuevo.recibo.costurero', (e) => {
                console.log('[Operario Dashboard] Costurero: Nuevo recibo asignado:', e);

                if (!shouldShowDashboardNotif(`costurero.nuevo|${e?.numero_recibo}`)) {
                    actualizarListaSinRecargar();
                    return;
                }

                const tipoRecibo = String(e?.tipo_recibo || '').toUpperCase();
                let mensaje = e?.mensaje || `Tienes un nuevo recibo: ${e?.nombre_prenda} #${e?.numero_recibo}`;

                if (window.NotificacionesPush && typeof window.NotificacionesPush.add === 'function') {
                    const stableId = `nuevo_recibo_${e?.numero_recibo || ''}`;
                    window.NotificacionesPush.add({
                        id: stableId,
                        title: 'Nuevo recibo de costura asignado',
                        message: mensaje,
                        type: 'info',
                        icon: 'checkroom',
                        duration: 10000,
                    });
                } else if ('Notification' in window && Notification.permission === 'granted') {
                    new Notification('Nuevo recibo de costura asignado', {
                        body: mensaje,
                        icon: '/icon.png',
                    });
                } else if ('Notification' in window && Notification.permission !== 'denied') {
                    Notification.requestPermission();
                }

                actualizarListaSinRecargar();
            });

            ws.subscribe(`costurero.${nombreNormalizado}`, '.completado.recibo', (e) => {
                console.log('[Operario Dashboard] Costurero: Evento de recibo completado:', e);
                actualizarListaSinRecargar();
            });
        }
    }

    // Costura-reflectivo/Leader-reflectivo listeners
    if (rol === 'costura-reflectivo' || rol === 'lider-reflectivo') {
        console.log('[Operario Dashboard] Costura-reflectivo/Lider-reflectivo suscribiendo a canal recibos-costura');

        ws.subscribe('recibos-costura', '.recibo.pasado.control.calidad', (e) => {
            console.log('[Operario Dashboard] Costura-reflectivo/Lider-reflectivo: Recibo pasado a Control Calidad:', e);

            const tipoRecibo = String(e?.tipo_recibo || '').toUpperCase();
            if (tipoRecibo !== 'REFLECTIVO') {
                console.log('[Operario Dashboard] No es REFLECTIVO, ignorando');
                return;
            }

            const prendaId = e?.prenda_id;
            if (prendaId) {
                const tarjeta = document.querySelector(`.orden-card-simple[data-prenda-id="${prendaId}"]`);
                if (tarjeta) {
                    console.log(`[Operario Dashboard] Eliminando tarjeta REFLECTIVO con prenda_id: ${prendaId}`);

                    tarjeta.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
                    tarjeta.style.opacity = '0';
                    tarjeta.style.transform = 'translateX(-100%)';

                    setTimeout(() => {
                        tarjeta.remove();
                        console.log('[Operario Dashboard] ✅ Tarjeta REFLECTIVO eliminada');

                        if (window.NotificacionesPush && typeof window.NotificacionesPush.add === 'function') {
                            window.NotificacionesPush.add({
                                title: 'Recibo REFLECTIVO movido',
                                message: e?.mensaje || `El recibo #${e?.numero_recibo} pasó a Control de Calidad`,
                                type: 'info',
                                icon: 'auto_awesome',
                                duration: 5000,
                            });
                        }
                    }, 500);
                }
            }
        });
    }
}

// Iniciar listeners cuando sea apropiado
document.addEventListener('DOMContentLoaded', () => {
    console.log('[Operario Dashboard] DOMContentLoaded, iniciando listeners');
    initRealtimeListeners();
});

export { actualizarListaSinRecargar };
