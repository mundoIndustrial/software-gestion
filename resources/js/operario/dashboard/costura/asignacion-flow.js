import { mostrarError, mostrarExito } from '../ui/messages';
import { confirmarDistribucionTaller } from './taller-flow';

export function confirmarAsignacion() {
    if (!window.opcionAsignacionSeleccionada) {
        mostrarError('Error', 'Debe seleccionar un tipo de asignacion');
        return;
    }

    if (!window.datosModalCostura) {
        mostrarError('Error', 'No hay datos de la prenda');
        return;
    }

    if (window.opcionAsignacionSeleccionada === 'completo') {
        window.confirmarPasarACostura?.();
        return;
    }

    if (window.opcionAsignacionSeleccionada === 'distribuir') {
        const btnConfirmar = document.getElementById('btnConfirmarAsignacion');
        const originalText = btnConfirmar ? btnConfirmar.innerHTML : null;

        const { pedidoId, prendaId, tipoRecibo, recibo } = window.datosModalCostura;
        if (!pedidoId || !prendaId || !tipoRecibo || !recibo) {
            mostrarError('Error', 'Faltan datos necesarios para procesar la solicitud');
            return;
        }

        if (!window.asignacionesPorModulo || Object.keys(window.asignacionesPorModulo).length === 0) {
            mostrarError('Error', 'No hay asignaciones realizadas');
            return;
        }

        const modulos = window?.datosDistribucion?.modulos || [];

        const parseTallaBase = (tallaRaw) => {
            const parts = String(tallaRaw || '').split('_');
            const base = parts[0] || String(tallaRaw || '');
            const normalized = String(base || '').trim();
            const match = normalized.match(/^(.+?)\s*\((.+)\)$/);
            return match ? match[1].trim() : normalized;
        };

        const asignacionesTemp = Object.entries(window.asignacionesPorModulo)
            .map(([moduloIdStr, asignacionesTallas]) => {
                const moduloId = parseInt(moduloIdStr);
                const modulo = modulos.find((m) => m.id === moduloId);
                const encargado = (modulo?.encargado || '').trim();

                const tallasNuevas = [];
                const tallasExistentes = [];

                Object.entries(asignacionesTallas || {}).forEach(([tallaRaw, datos]) => {
                    let cantidad;
                    let color;
                    let esNueva;

                    if (typeof datos === 'object' && datos !== null) {
                        cantidad = parseInt(datos.cantidad) || 0;
                        color = datos.color || null;
                        esNueva = datos.es_nueva || false;
                    } else {
                        cantidad = parseInt(datos) || 0;
                        color = null;
                        esNueva = false;
                    }

                    const tallaBase = parseTallaBase(tallaRaw);
                    const itemTalla = window.datosDistribucion.tallas.find((t) => {
                        const baseT = (t.tallaOriginal || (String(t.talla || '').split(' ')[0])) || '';
                        return String(baseT) === String(tallaBase);
                    });

                    const tallaObj = {
                        talla: tallaBase,
                        cantidad,
                        color_nombre: color,
                        genero: itemTalla ? itemTalla.genero : null,
                    };

                    if (cantidad > 0) {
                        if (esNueva) {
                            tallasNuevas.push(tallaObj);
                        } else {
                            tallasExistentes.push(tallaObj);
                        }
                    }
                });

                const asignacionesResult = [];

                if (window.datosModalCostura.esEdicion) {
                    if (tallasNuevas.length > 0) {
                        asignacionesResult.push({
                            encargado,
                            tallas: tallasNuevas,
                            is_nueva_parte: true,
                        });
                    }
                } else {
                    const tallasParaGuardar = [...tallasNuevas, ...tallasExistentes];
                    if (tallasParaGuardar.length > 0) {
                        asignacionesResult.push({
                            encargado,
                            tallas: tallasParaGuardar,
                        });
                    }
                }

                return asignacionesResult;
            })
            .flat();

        const asignaciones = asignacionesTemp.filter((a) => a.encargado && Array.isArray(a.tallas) && a.tallas.length > 0);

        if (asignaciones.length === 0) {
            mostrarError('Error', 'No hay asignaciones validas para guardar');
            return;
        }

        const action = `/recibos-novedades/${pedidoId}/${recibo}/distribuir-por-modulos`;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

        if (btnConfirmar) {
            btnConfirmar.disabled = true;
            btnConfirmar.innerHTML = '<span class="material-symbols-rounded" style="animation: spin 1s linear infinite;">refresh</span> Procesando...';
        }

        const nuevasPartes = window.datosModalCostura.esEdicion
            ? (window.__datosParcialesEdicion || []).filter((p) => p.id && typeof p.id === 'number')
            : [];

        fetch(action, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
            },
            body: JSON.stringify({
                prenda_id: prendaId,
                ...(window.datosModalCostura.prendaBodegaId ? { prenda_bodega_id: window.datosModalCostura.prendaBodegaId } : {}),
                tipo_recibo: tipoRecibo,
                asignaciones,
                es_edicion: window.datosModalCostura.esEdicion || false,
                nuevas_partes: nuevasPartes.length > 0 ? nuevasPartes : undefined,
            }),
        })
            .then((r) => r.json())
            .then(async (data) => {
                if (data?.success) {
                    window.cerrarModalCostura?.();
                    mostrarExito('Exito', data?.message || 'La Distribucion del recibo fue exitosa');

                    if (
                        document.getElementById('ordenesList') &&
                        typeof window.__actualizarDashboardSinRecargar === 'function'
                    ) {
                        try {
                            await window.__actualizarDashboardSinRecargar();
                        } catch (refreshError) {
                            console.warn('No se pudo actualizar el dashboard tras distribuir el recibo:', refreshError);
                        }
                    }
                } else {
                    mostrarError('Error', data?.message || 'No se pudo guardar la Distribucion');
                }
            })
            .catch((err) => {
                console.error('Error guardando Distribucion:', err);
                mostrarError('Error', 'Error de conexion: ' + (err?.message || err));
            })
            .finally(() => {
                if (btnConfirmar) {
                    btnConfirmar.disabled = false;
                    if (originalText !== null) btnConfirmar.innerHTML = originalText;
                }
            });
        return;
    }

    if (window.opcionAsignacionSeleccionada === 'taller') {
        confirmarDistribucionTaller();
    }
}
