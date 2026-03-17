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

    let intentos = 0;
    const maxIntentos = 100;

    function init() {
        try {
            intentos += 1;

            if (!window.USUARIO_ACTUAL?.id) {
                if (intentos < maxIntentos) {
                    return setTimeout(init, 200);
                }
                console.warn('[Operario Dashboard] No se pudo iniciar realtime: USUARIO_ACTUAL no disponible');
                return;
            }

            if (!window.EchoInstance) {
                if (intentos < maxIntentos) {
                    return setTimeout(init, 200);
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
                .listen('.encargado.costura.asignado', (e) => {
                    console.log(
                        '[Operario Dashboard] Encargado de costura asignado, eliminando recibo de vista del cortador:',
                        e
                    );

                    if (String(window.USUARIO_ACTUAL?.rol || '').toLowerCase() === 'cortador') {
                        const prendaId = e?.prenda_id;

                        if (prendaId) {
                            const card = document.querySelector(`.orden-card-simple[data-prenda-id="${prendaId}"]`);
                            if (card) {
                                console.log(`[Operario Dashboard] Eliminando tarjeta prenda_id: ${prendaId}`);
                                card.remove();

                                actualizarContadorTarjetas();
                                console.log('[Operario Dashboard] ✅ Tarjeta eliminada y contador actualizado');
                            } else {
                                console.warn(`[Operario Dashboard] No se encontró tarjeta con prenda_id: ${prendaId}`);
                            }
                        }
                    } else {
                        console.log('[Operario Dashboard] No es cortador, no elimina tarjeta');
                    }

                    if (String(window.USUARIO_ACTUAL?.rol || '').toLowerCase() === 'administrador-costura') {
                        console.log(
                            '[Operario Dashboard] Admin-costura recibió encargado.costura.asignado (operarios.corte), refrescando lista'
                        );

                        const encargadoRol = String(e?.encargado_rol || '').trim().toLowerCase();
                        if (encargadoRol === 'costura-reflectivo') {
                            console.log(
                                '[Operario Dashboard] Admin-costura: encargado es costura-reflectivo, no notifica (solo refresca lista)'
                            );
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
                                            duration: 8000,
                                        });
                                    } else if ('Notification' in window) {
                                        if (Notification.permission === 'granted') {
                                            new Notification('Recibo asignado a costura', {
                                                body: mensaje,
                                                icon: '/icon.png',
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
                    })
                    .listen('.recibo.completado', (e) => {
                        console.log('[Operario Dashboard] Evento recibo.completado recibido:', e);
                        try {
                            if (window.NotificacionesPush && typeof window.NotificacionesPush.add === 'function') {
                                const dedupeKey = [
                                    'public',
                                    'recibo.completado',
                                    String(e?.prenda_id || ''),
                                    String(e?.recibo_id || ''),
                                    String(e?.consecutivo || e?.numero_recibo || ''),
                                    String(e?.area || ''),
                                    String(e?.updated_at || e?.created_at || ''),
                                    String(e?.nombre_operario || ''),
                                ].join('|');

                                if (!shouldShowDashboardNotif(dedupeKey)) {
                                    actualizarListaSinRecargar();
                                    return;
                                }

                                const area = String(e?.area || '').trim();
                                const consecutivo = e?.consecutivo ? `#${e.consecutivo}` : '';
                                const operario = String(e?.nombre_operario || '').trim();
                                let titulo = 'Recibo completado';
                                let detalle = `Recibo ${consecutivo}`.trim();

                                if (area && operario) {
                                    titulo += ` en ${area}`;
                                    detalle += ` por ${operario}`;
                                }

                                const notifStableId = [
                                    'recibo_completado',
                                    String(e?.recibo_id || e?.numero_recibo || e?.consecutivo || ''),
                                    String(e?.prenda_id || ''),
                                ].join('|');

                                window.NotificacionesPush.add({
                                    id: notifStableId,
                                    title: titulo,
                                    message: detalle,
                                    type: 'success',
                                    icon: 'check_circle',
                                    duration: 5000,
                                });
                            }
                        } catch (err) {
                            console.warn('[Operario Dashboard] Error creando notificación push', err);
                        }
                        actualizarListaSinRecargar();
                    });
            }

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

            if (String(window.USUARIO_ACTUAL?.rol || '').toLowerCase() === 'costurero') {
                const nombreCosturero = String(window.USUARIO_ACTUAL?.nombre || '').trim();
                if (nombreCosturero) {
                    const nombreNormalizado = nombreCosturero.toLowerCase().replace(/[^a-zA-Z0-9]/g, '_');

                    console.log(
                        `[Operario Dashboard] Costurero ${nombreCosturero} (normalizado: ${nombreNormalizado}) suscribiendo a canales`
                    );

                    window.EchoInstance.channel('recibos-costura')
                        .subscribed(() => {
                            console.log(
                                `[Operario Dashboard] Costurero ${nombreCosturero} suscrito OK a canal público recibos-costura`
                            );
                        })
                        .error((err) => {
                            console.error(
                                `[Operario Dashboard] Error suscribiendo costurero ${nombreCosturero} a canal público:`,
                                err
                            );
                        })
                        .listen('.recibo.pasado.control.calidad', (e) => {
                            console.log('[Operario Dashboard] 🚫 Recibo pasado a Control Calidad - eliminar de vista:', e);

                            const prendaId = e?.prenda_id;
                            if (prendaId) {
                                const tarjeta = document.querySelector(
                                    `.orden-card-simple[data-prenda-id="${prendaId}"]`
                                );
                                if (tarjeta) {
                                    console.log(`[Operario Dashboard] Eliminando tarjeta con prenda_id: ${prendaId}`);

                                    tarjeta.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
                                    tarjeta.style.opacity = '0';
                                    tarjeta.style.transform = 'translateX(-100%)';

                                    setTimeout(() => {
                                        tarjeta.remove();
                                        console.log('[Operario Dashboard] ✅ Tarjeta eliminada de la vista');

                                        if (window.NotificacionesPush && typeof window.NotificacionesPush.add === 'function') {
                                            window.NotificacionesPush.add({
                                                title: 'Recibo movido a Control de Calidad',
                                                message:
                                                    e?.mensaje ||
                                                    `El recibo #${e?.numero_recibo} ya no está disponible en costura`,
                                                type: 'info',
                                                duration: 5000,
                                            });
                                        } else {
                                            if ('Notification' in window && Notification.permission === 'granted') {
                                                new Notification('Recibo movido a Control de Calidad', {
                                                    body:
                                                        e?.mensaje ||
                                                        `El recibo #${e?.numero_recibo} (${e?.nombre_prenda}) ha pasado a Control de Calidad`,
                                                    icon: '/icon.png',
                                                });
                                            }
                                        }
                                    }, 500);
                                } else {
                                    console.warn(`[Operario Dashboard] No se encontró tarjeta con prenda_id: ${prendaId}`);
                                }
                            }
                        });

                    window.EchoInstance.private(`costurero.${nombreNormalizado}`)
                        .subscribed(() => {
                            console.log(`[Operario Dashboard] Costurero ${nombreCosturero} suscrito OK a canal privado`);
                        })
                        .error((err) => {
                            console.error(`[Operario Dashboard] Error suscribiendo costurero ${nombreCosturero}:`, err);
                        })
                        .listen('.recibo.asignado', (e) => {
                            console.log('[Operario Dashboard] ✅ Recibo asignado a costurero RECIBIDO:', e);

                            if (window.NotificacionesPush && typeof window.NotificacionesPush.add === 'function') {
                                console.log('[Operario Dashboard] Mostrando notificación push...');
                                window.NotificacionesPush.add({
                                    title: 'Nuevo recibo de costura asignado',
                                    message:
                                        e?.mensaje || `Tienes un nuevo recibo: ${e?.nombre_prenda} #${e?.numero_recibo}`,
                                    type: 'info',
                                    icon: 'checkroom',
                                    duration: 8000,
                                });
                                console.log('[Operario Dashboard] ✅ Notificación push mostrada');
                            } else {
                                console.log('[Operario Dashboard] Usando notificación nativa del navegador...');
                                if ('Notification' in window && Notification.permission === 'granted') {
                                    new Notification('Nuevo recibo de costura asignado', {
                                        body:
                                            e?.mensaje ||
                                            `Tienes un nuevo recibo: ${e?.nombre_prenda} #${e?.numero_recibo}`,
                                        icon: '/icon.png',
                                        badge: '/icon.png',
                                    });
                                    console.log('[Operario Dashboard] ✅ Notificación nativa mostrada');
                                } else if ('Notification' in window && Notification.permission !== 'denied') {
                                    Notification.requestPermission().then((permission) => {
                                        if (permission === 'granted') {
                                            new Notification('Nuevo recibo de costura asignado', {
                                                body:
                                                    e?.mensaje ||
                                                    `Tienes un nuevo recibo: ${e?.nombre_prenda} #${e?.numero_recibo}`,
                                                icon: '/icon.png',
                                            });
                                            console.log('[Operario Dashboard] ✅ Notificación nativa mostrada (permiso concedido)');
                                        }
                                    });
                                } else {
                                    console.log('[Operario Dashboard] Mostrando alert simple como fallback');
                                    alert(
                                        `Nuevo recibo asignado: ${e?.mensaje || `Tienes un nuevo recibo: ${e?.nombre_prenda} #${e?.numero_recibo}`}`
                                    );
                                }
                            }

                            console.log('[Operario Dashboard] Actualizando lista sin recargar...');
                            actualizarListaSinRecargar();
                            console.log('[Operario Dashboard] ✅ Lista actualizada');
                        });
                }
            }

            const rolUsuario = String(window.USUARIO_ACTUAL?.rol || '').toLowerCase();
            if (rolUsuario === 'costura-reflectivo' || rolUsuario === 'lider-reflectivo') {
                console.log(
                    '[Operario Dashboard] Costura-reflectivo/Lider-reflectivo suscribiendo a canal recibos-costura para escuchar cambios de área'
                );

                window.EchoInstance.channel('recibos-costura')
                    .subscribed(() => {
                        console.log(
                            '[Operario Dashboard] Costura-reflectivo/Lider-reflectivo suscrito OK a canal público recibos-costura'
                        );
                    })
                    .error((err) => {
                        console.error(
                            '[Operario Dashboard] Error suscribiendo costura-reflectivo/lider-reflectivo a canal público:',
                            err
                        );
                    })
                    .listen('.recibo.pasado.control.calidad', (e) => {
                        console.log(
                            '[Operario Dashboard] Costura-reflectivo/Lider-reflectivo: Recibo pasado a Control Calidad:',
                            e
                        );

                        const tipoRecibo = String(e?.tipo_recibo || '').toUpperCase();
                        if (tipoRecibo !== 'REFLECTIVO') {
                            console.log('[Operario Dashboard] Costura-reflectivo/Lider-reflectivo: No es REFLECTIVO, ignorando');
                            return;
                        }

                        const prendaId = e?.prenda_id;
                        if (prendaId) {
                            const tarjeta = document.querySelector(`.orden-card-simple[data-prenda-id="${prendaId}"]`);
                            if (tarjeta) {
                                console.log(
                                    `[Operario Dashboard] Costura-reflectivo/Lider-reflectivo: Eliminando tarjeta REFLECTIVO con prenda_id: ${prendaId}`
                                );

                                tarjeta.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
                                tarjeta.style.opacity = '0';
                                tarjeta.style.transform = 'translateX(-100%)';

                                setTimeout(() => {
                                    tarjeta.remove();
                                    console.log(
                                        '[Operario Dashboard] ✅ Tarjeta REFLECTIVO eliminada de vista costura-reflectivo/lider-reflectivo'
                                    );

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
        } catch (e) {
            console.error('[Operario Dashboard] Error initRealtimeListeners', e);
        }
    }

    init();
}

export { actualizarListaSinRecargar };
