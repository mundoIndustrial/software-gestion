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

        const nuevoCount = doc.getElementById('ordenesCount');
        const actualCount = document.getElementById('ordenesCount');
        if (nuevoCount && actualCount) {
            actualCount.textContent = nuevoCount.textContent;
        }

        if (typeof window.__initDashboardSearch === 'function') {
            window.__initDashboardSearch();
        }

        if (typeof window.filtrarPrendasPorRecibo === 'function') {
            const badgeActivo = document.querySelector('.badge-filtro[data-filtro].badge-filtro-active');
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

window.__actualizarDashboardSinRecargar = actualizarListaSinRecargar;

function ocultarOriginalReflectivoDistribuido(evento) {
    const rol = String(window.USUARIO_ACTUAL?.rol || '').toLowerCase();
    const tipoRecibo = String(evento?.tipo_recibo || '').toUpperCase();
    const accion = String(evento?.accion || '').toLowerCase();
    const numeroRecibo = String(evento?.numero_recibo || evento?.consecutivo || '').trim();
    const prendaId = String(evento?.prenda_id || '').trim();

    const esLiderReflectivo = rol === 'lider-reflectivo';
    const esParcial = Boolean(evento?.es_parcial);
    const esReflectivo = tipoRecibo === 'REFLECTIVO' || accion === 'recibo_asignado_reflectivo';

    if (!esLiderReflectivo || !esParcial || !esReflectivo || !numeroRecibo.includes('.')) {
        return;
    }

    const consecutivoOriginal = numeroRecibo.split('.')[0];
    let removidas = 0;

    document.querySelectorAll('.orden-card-simple').forEach((card) => {
        const tipoCard = String(card.dataset.tipoRecibo || '')
            .split(',')
            .map((valor) => valor.trim().toLowerCase());
        const prendaCard = String(card.dataset.prendaId || '').trim();
        const numeroCard = card.querySelector('.orden-numero')?.textContent?.trim() || '';

        if (!tipoCard.includes('reflectivo')) {
            return;
        }

        if (prendaId && prendaCard && prendaId !== prendaCard) {
            return;
        }

        if (numeroCard === `#${consecutivoOriginal}`) {
            card.remove();
            removidas++;
        }
    });

    if (removidas > 0) {
        console.log('[Operario Dashboard] Original reflectivo ocultado tras recibir parcial realtime', {
            prendaId,
            numeroReciboParcial: numeroRecibo,
            consecutivoOriginal,
            removidas,
        });
        actualizarContadorTarjetas();
    }
}

export function initRealtimeListeners() {
    // Verificar si es la página del operario-dashboard
    // De lo contrario, retornar sin hacer nada (ej: /asesores/dashboard)
    if (!document.querySelector('.operario-dashboard')) {
        return;
    }

    if (window.__operarioDashboardRealtimeInitStarted) {
        return;
    }
    window.__operarioDashboardRealtimeInitStarted = true;

    console.log('[Operario Dashboard] Iniciando escuchadores de tiempo real (Phase 5)');

    // Usar window.waitForEcho para esperar Echo disponible
    if (typeof window.waitForEcho !== 'function') {
        console.warn('[Operario Dashboard] waitForEcho no disponible, reintentando...');
        setTimeout(initRealtimeListeners, 500);
        return;
    }

    window.waitForEcho(() => {
        const ws = window.shared?.websocket;

        try {
            // Verificar disponibilidad de datos globales
            if (!window.USUARIO_ACTUAL?.id) {
                console.warn('[Operario Dashboard] USUARIO_ACTUAL no disponible');
                return;
            }

            console.log('[Operario Dashboard] Configurando listeners realtime', {
                usuario: window.USUARIO_ACTUAL,
                privateChannel: `App.Models.User.${window.USUARIO_ACTUAL.id}`,
                publicChannels: ['operarios.corte', 'recibos-costura'],
                hasSharedWebsocket: !!ws,
            });

            if (ws) {
                setupPrivateUserChannel(ws);
                setupPublicChannels(ws);
            } else {
                console.warn('[Operario Dashboard] WebSocket abstraction no disponible, usando fallback Echo');
            }

            setupEchoFallbackListeners();
            
            console.log('[Operario Dashboard]  Listeners configurados');
        } catch (error) {
            console.error('[Operario Dashboard] Error al configurar listeners:', error);
        }
    });
}

function setupEchoFallbackListeners() {
    const rol = String(window.USUARIO_ACTUAL?.rol || '').toLowerCase();
    const userId = window.USUARIO_ACTUAL?.id;

    if (!userId || !window.EchoInstance) {
        return;
    }

    if (window.__operarioEchoFallbackReady) {
        return;
    }
    window.__operarioEchoFallbackReady = true;

    try {
        console.log('[Operario Dashboard] Registrando fallback Echo', {
            userId,
            rol,
        });

        const mostrarNotificacion = (e) => {
            try {
                const encargadoStr = String(e?.encargado || e?.nombre_operario || '').trim();
                const numeroRecibo = String(e?.numero_recibo || e?.consecutivo || '').trim();
                const accion = String(e?.accion || '').toLowerCase();
                const mensaje = e?.mensaje || `Pedido #${e?.pedido_id || e?.pedido_produccion_id || ''} · Recibo #${numeroRecibo} actualizado`;
                
                const notifKey = `${rol}|fallback|${accion}|${e?.recibo_id || ''}|${numeroRecibo}|${encargadoStr}`;

                if (!shouldShowDashboardNotif(notifKey)) {
                    return;
                }

                let titulo = 'Actualización de recibo';
                let tipo = 'info';
                let icono = 'notifications';

                if (accion === 'recibo_completado') {
                    titulo = e?.es_parcial ? 'Parcial completado' : 'Recibo completado';
                    tipo = 'success';
                    icono = 'check_circle';
                } else if (accion === 'recibo_asignado_reflectivo') {
                    titulo = e?.es_parcial ? 'Recibo parcial REFLECTIVO asignado' : 'Recibo REFLECTIVO asignado';
                    icono = 'auto_awesome';
                } else if (accion === 'recibo_asignado_costura' || String(e?.tipo_recibo || '').toUpperCase() === 'COSTURA') {
                    titulo = e?.es_parcial ? 'Recibo parcial COSTURA asignado' : 'Recibo COSTURA asignado';
                    icono = 'checkroom';
                } else if (String(e?.tipo_recibo || '').toUpperCase() === 'PARCIAL') {
                    titulo = 'Recibo parcial asignado';
                }

                if (window.NotificacionesPush && typeof window.NotificacionesPush.add === 'function') {
                    window.NotificacionesPush.add({
                        id: notifKey,
                        title: titulo,
                        message: mensaje,
                        type: tipo,
                        icon: icono,
                        duration: 8000,
                    });
                }
            } catch (error) {
                console.warn('[Operario Dashboard] Error creando notificación fallback', error);
            }
        };

        const actualizarEstadoVisual = (e) => {
            aplicarEstadoReciboEnDOM(e);
        };

        if (rol === 'administrador-costura' || rol === 'costura-reflectivo' || rol === 'lider-reflectivo' || rol === 'vista-costura' || rol === 'cortador' || rol === 'visualizador_plooter') {
            window.EchoInstance.private(`App.Models.User.${userId}`)
                .listen('.operario.recibos.actualizados', async (e) => {
                    console.log('[Operario Dashboard] Fallback privado operario.recibos.actualizados:', { rol, evento: e });
                    mostrarNotificacion(e);
                    actualizarEstadoVisual(e);
                    await actualizarListaSinRecargar();
                    ocultarOriginalReflectivoDistribuido(e);
                });
        }

        if (rol === 'administrador-costura') {
            window.EchoInstance.channel('recibos-costura')
                .listen('.encargado.costura.asignado', (e) => {
                    console.log('[Operario Dashboard] Admin-costura fallback público encargado.costura.asignado:', e);
                    mostrarNotificacion(e);
                    actualizarEstadoVisual(e);
                    actualizarListaSinRecargar();
                });

            window.EchoInstance.channel('operarios.corte')
                .listen('.encargado.costura.asignado', (e) => {
                    console.log('[Operario Dashboard] Admin-costura fallback operarios.corte encargado.costura.asignado:', e);
                    mostrarNotificacion(e);
                    actualizarEstadoVisual(e);
                    actualizarListaSinRecargar();
                });
        }
    } catch (error) {
        console.warn('[Operario Dashboard] No se pudo registrar fallback Echo', error);
    }
}
function setupPrivateUserChannel(ws) {
    const userId = window.USUARIO_ACTUAL.id;
    
    ws.subscribe(`private-App.Models.User.${userId}`, '.operario.recibos.actualizados', async (e) => {
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
                titulo = e?.es_parcial ? 'Parcial completado' : 'Recibo completado';
            } else if (e?.accion === 'recibo_deshecho') {
                titulo = e?.es_parcial ? 'Parcial deshecho' : 'Recibo deshecho';
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
            aplicarEstadoReciboEnDOM(e);
        }

        if (e?.accion === 'recibo_deshecho' && e?.prenda_id) {
            revertirEstadoReciboEnDOM(e);
        }

        await actualizarListaSinRecargar();
        ocultarOriginalReflectivoDistribuido(e);
    });
}

function obtenerCardReciboDesdeEvento(e) {
    const esParcial = Boolean(e?.es_parcial || e?.pedido_parcial_id || e?.id_parcial);

    if (esParcial) {
        const parcialId = e?.pedido_parcial_id || e?.id_parcial;
        if (parcialId) {
            const cardModalParcial = document.querySelector(`.parcial-card[data-parcial-id="${parcialId}"]`);
            if (cardModalParcial) {
                return cardModalParcial;
            }

            const cardParcial = document.querySelector(`.orden-card-simple[data-pedido-parcial-id="${parcialId}"]`);
            if (cardParcial) {
                return cardParcial;
            }
        }
    }

    if (e?.prenda_id) {
        const cardPorPrenda = document.querySelector(`.orden-card-simple[data-prenda-id="${e.prenda_id}"]`);
        if (cardPorPrenda) {
            return cardPorPrenda;
        }
    }

    if (e?.recibo_id) {
        return document.querySelector(`.orden-card-simple[data-numero-recibo="${e.recibo_id}"]`);
    }

    return null;
}

function aplicarEstadoReciboEnDOM(e) {
    const card = obtenerCardReciboDesdeEvento(e);
    if (!card) {
        return;
    }

    const esParcial = Boolean(e?.es_parcial || e?.pedido_parcial_id || e?.id_parcial);
    const originalCompletado = Boolean(e?.original_completado);
    const body = card.querySelector('.orden-body');
    const badgesEstado = card.querySelectorAll('.estado-badge, .badge-estado, .badge-completado-corte');
    const textoCompleto = esParcial ? 'COMPLETADO' : 'COMPLETADO COSTURA';

    if (esParcial) {
        card.classList.add('card-completado-costura');
        if (body) {
            body.classList.add('recibo-completado-area');
        }
        badgesEstado.forEach((badge) => {
            badge.classList.remove('badge-estado-en-progreso');
            badge.classList.add('badge-estado-completado');
            badge.textContent = textoCompleto;
        });
        asegurarBadgeCompletado(card, true);

        if (originalCompletado && e?.prenda_id) {
            const cardOriginal = document.querySelector(
                `.orden-card-simple[data-prenda-id="${e.prenda_id}"]:not([data-pedido-parcial-id])`
            );
            if (cardOriginal && cardOriginal !== card) {
                aplicarEstadoEnCardOriginal(cardOriginal, e);
            }
        }

        return;
    }

    if (originalCompletado) {
        aplicarEstadoEnCardOriginal(card, e);
    }
}

function aplicarEstadoEnCardOriginal(card, e) {
    if (!card) return;

    const body = card.querySelector('.orden-body');
    const badgesEstado = card.querySelectorAll('.estado-badge, .badge-estado, .badge-completado-corte');

    card.classList.add('card-completado-costura');
    if (body) {
        body.classList.add('recibo-completado-area');
    }
    badgesEstado.forEach((badge) => {
        badge.classList.remove('badge-estado-en-progreso');
        badge.classList.add('badge-estado-completado');
        badge.textContent = e?.es_parcial ? 'COMPLETADO COSTURA' : 'COMPLETADO COSTURA';
    });
    asegurarBadgeCompletado(card, true);
}

function revertirEstadoReciboEnDOM(e) {
    const parcialCard = obtenerCardReciboDesdeEvento(e);
    const originalCard = e?.prenda_id
        ? document.querySelector(`.orden-card-simple[data-prenda-id="${e.prenda_id}"]`)
        : null;

    const revertirCard = (card, esOriginal = false) => {
        if (!card) return;

        const body = card.querySelector('.orden-body');
        const badgesEstado = card.querySelectorAll('.estado-badge, .badge-estado, .badge-completado-corte');

        card.classList.remove('card-completado-costura');
        if (body) {
            body.classList.remove('recibo-completado-area');
        }

        badgesEstado.forEach((badge) => {
            badge.classList.remove('badge-estado-completado');
            badge.textContent = esOriginal ? 'PENDIENTE COSTURA' : 'PENDIENTE';
        });

        asegurarBadgeCompletado(card, false);
    };

    if (parcialCard) {
        revertirCard(parcialCard, false);
    }

    if (e?.original_completado === false && originalCard) {
        revertirCard(originalCard, true);
    }

    if (e?.original_completado === true && originalCard) {
        aplicarEstadoReciboEnDOM({ ...e, es_parcial: false, pedido_parcial_id: null, id_parcial: null });
    }
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
            actualizarListaSinRecargar();
        });

        ws.subscribe('recibos-costura', '.encargado.costura.asignado', (e) => {
            console.log('[Operario Dashboard] Evento encargado.costura.asignado recibido (vista-costura):', e);
            actualizarListaSinRecargar();
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
                    let titulo = e?.es_parcial ? 'Parcial completado' : 'Recibo completado';
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

    if (rol === 'costurero' || rol === 'confeccion-sobremedida') {
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

                const tituloNotif = e?.es_parcial ? 'Nuevo recibo parcial asignado' : 'Nuevo recibo de costura asignado';

                if (window.NotificacionesPush && typeof window.NotificacionesPush.add === 'function') {
                    const stableId = `nuevo_recibo_${e?.numero_recibo || ''}`;
                    window.NotificacionesPush.add({
                        id: stableId,
                        title: tituloNotif,
                        message: mensaje,
                        type: 'info',
                        icon: 'checkroom',
                        duration: 10000,
                    });
                } else if ('Notification' in window && Notification.permission === 'granted') {
                    new Notification(tituloNotif, {
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
                        console.log('[Operario Dashboard]  Tarjeta REFLECTIVO eliminada');

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

