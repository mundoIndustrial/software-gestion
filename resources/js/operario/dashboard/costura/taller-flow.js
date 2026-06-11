import { mostrarError, mostrarExito } from '../ui/messages';

export function confirmarDistribucionTaller() {
    const tipoDistribucion = window.tipoDistribucionTaller || 'unico';
    const btnConfirmar = document.getElementById('btnConfirmarAsignacion');
    const originalText = btnConfirmar ? btnConfirmar.innerHTML : null;

    const { pedidoId, prendaId, prendaBodegaId, tipoRecibo, recibo, esEdicion } = window.datosModalCostura;
    const subtipoTaller = tipoDistribucion === 'unico' ? 'unico' : 'multiple';
    const encargadosSeleccionados = tipoDistribucion === 'unico'
        ? (document.getElementById('tallerUnicoSelector')?.value.trim() || '')
        : '';

    if (tipoDistribucion === 'unico' && !encargadosSeleccionados) {
        mostrarError('Error', 'Debe seleccionar un taller');
        return;
    }

    if (tipoDistribucion === 'multiple' && (!window.asignacionesPorTaller || Object.keys(window.asignacionesPorTaller).length === 0)) {
        mostrarError('Error', 'No hay asignaciones realizadas');
        return;
    }

    const asignaciones = tipoDistribucion === 'multiple'
        ? Object.entries(window.asignacionesPorTaller || {})
            .map(([tallerId, tallasAsignadas]) => {
                const taller = (window.talleresSeleccionadosDistribucion || []).find((item) => String(item?.id ?? '') === String(tallerId))
                    || (window.datosDistribucion?.talleres || []).find((item) => String(item?.id ?? '') === String(tallerId))
                    || {};

                const encargado = String(taller?.nombre || taller?.name || '').trim();
                const tallas = Object.entries(tallasAsignadas || {})
                    .map(([tallaIdUnico, datos]) => {
                        const cantidad = typeof datos === 'object' && datos !== null ? (parseInt(datos.cantidad) || 0) : (parseInt(datos) || 0);
                        if (cantidad <= 0) return null;

                        const colorNombre = typeof datos === 'object' && datos !== null
                            ? (datos.color || datos.color_nombre || null)
                            : null;

                        const genero = typeof datos === 'object' && datos !== null
                            ? (datos.genero || null)
                            : null;

                        const tallaBase = String(tallaIdUnico || '').split('_')[0] || tallaIdUnico;

                        return {
                            talla: tallaBase,
                            cantidad,
                            color_nombre: colorNombre,
                            genero,
                        };
                    })
                    .filter(Boolean);

                if (!encargado || tallas.length === 0) {
                    return null;
                }

                return { encargado, tallas };
            })
            .filter(Boolean)
        : undefined;

    const action = `/recibos-novedades/${pedidoId}/${recibo}/pasar-a-taller`;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    if (btnConfirmar) {
        btnConfirmar.disabled = true;
        btnConfirmar.innerHTML = '<span class="material-symbols-rounded" style="animation: spin 1s linear infinite;">refresh</span> Procesando...';
    }

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
            prenda_bodega_id: prendaBodegaId ?? '',
            tipo_recibo: tipoRecibo,
            tipo_distribucion: 'taller',
            subtipo_taller: subtipoTaller,
            encargado: encargadosSeleccionados || undefined,
            asignaciones,
            asignaciones_por_taller: window.asignacionesPorTaller || undefined,
            talleres_seleccionados: window.talleresSeleccionadosDistribucion || undefined,
            tallas_distribucion: window.datosDistribucion?.tallas || undefined,
            es_edicion: esEdicion || false,
        }),
    })
        .then((r) => r.json())
        .then(async (data) => {
            if (data?.success) {
                window.cerrarModalCostura?.();
                mostrarExito('Exito', data?.message || 'El recibo fue asignado a taller correctamente');

                if (
                    document.getElementById('ordenesList') &&
                    typeof window.__actualizarDashboardSinRecargar === 'function'
                ) {
                    try {
                        await window.__actualizarDashboardSinRecargar();
                    } catch (refreshError) {
                        console.warn('No se pudo actualizar el dashboard tras asignar a taller:', refreshError);
                    }
                }
            } else {
                mostrarError('Error', data?.message || 'No se pudo asignar a taller');
            }
        })
        .catch((err) => {
            console.error('Error asignando a taller:', err);
            mostrarError('Error', 'Error de conexion: ' + (err?.message || err));
        })
        .finally(() => {
            if (btnConfirmar) {
                btnConfirmar.disabled = false;
                if (originalText !== null) btnConfirmar.innerHTML = originalText;
            }
        });
}
