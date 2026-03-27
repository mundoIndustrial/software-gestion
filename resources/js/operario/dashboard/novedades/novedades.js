import { httpJson, httpJsonBody } from '../api/http';
import { mostrarError, mostrarExito } from '../ui/messages';

export function abrirModalNovedad(numeroPedido, prendaId, nombrePrenda, numeroRecibo) {
    console.log(' Abriendo modal novedad', { numeroPedido, prendaId, nombrePrenda, numeroRecibo });

    const modal = document.getElementById('modalNovedad');
    if (!modal) {
        console.error('Modal no encontrado');
        return;
    }

    const tituloModal = document.getElementById('modalNovedadHeaderTitulo');
    if (tituloModal) {
        tituloModal.textContent = `NOVEDADES - PEDIDO #${numeroPedido} - RECIBO ${numeroRecibo}`;
    }

    const numeroPedidoEl = document.getElementById('novedadNumeroPedido');
    const prendaIdEl = document.getElementById('novedadPrendaId');

    if (numeroPedidoEl) numeroPedidoEl.value = numeroPedido;
    if (prendaIdEl) prendaIdEl.value = prendaId;

    const prendaNombreEl = document.getElementById('novedadPrendaNombre');
    const reciboNumeroEl = document.getElementById('novedadReciboNumero');

    if (prendaNombreEl) prendaNombreEl.textContent = nombrePrenda;
    if (reciboNumeroEl) reciboNumeroEl.textContent = numeroRecibo;

    let hiddenRecibo = document.getElementById('novedadNumeroRecibo');
    if (!hiddenRecibo) {
        hiddenRecibo = document.createElement('input');
        hiddenRecibo.type = 'hidden';
        hiddenRecibo.id = 'novedadNumeroRecibo';
        hiddenRecibo.name = 'numero_recibo';
        document.getElementById('modalNovedad')?.appendChild(hiddenRecibo);
    }
    hiddenRecibo.value = numeroRecibo;

    cargarNovedadesDelUsuario(numeroPedido, prendaId);

    modal.style.display = 'flex';
}

export function cerrarModalNovedad() {
    const modal = document.getElementById('modalNovedad');
    if (modal) {
        modal.style.display = 'none';
        const textarea = document.getElementById('novedadDescripcionText');
        if (textarea) textarea.value = '';
    }
}

export function cargarNovedadesDelUsuario(numeroPedido, prendaId) {
    console.log(' Cargando novedades', { numeroPedido, prendaId });

    httpJson(`/operario/api/novedades/${numeroPedido}/${prendaId}`, {
        headers: {},
    })
        .then((response) => response.json())
        .then((data) => {
            console.log(' Novedades cargadas:', data);
            mostrarNovedades(data.novedades || []);
        })
        .catch((error) => {
            console.error(' Error cargando novedades:', error);
            const historial = document.getElementById('novedadesHistorial');
            if (historial) {
                historial.innerHTML = '<p style="color: #999;">Error cargando novedades</p>';
            }
        });
}

function mostrarNovedades(novedades) {
    const historial = document.getElementById('novedadesHistorial');
    if (!historial) {
        console.error('Historial no encontrado');
        return;
    }

    if (novedades.length === 0) {
        historial.innerHTML =
            '<div style="padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.75rem; background: #f9fafb; color: #6b7280; font-size: 0.9rem;">No hay novedades registradas</div>';
        return;
    }

    let html = '';
    novedades.forEach((novedad) => {
        const fecha = (novedad.creado_en || novedad.created_at || '').toString();
        const esMia = !!(novedad.es_mia ?? novedad.created_by_me ?? novedad.esPropia ?? false);
        const tipoRaw = (novedad.tipo_novedad || novedad.tipo || 'observacion').toString();
        const tipo = tipoRaw.toUpperCase();
        const usuarioNombre = (novedad.usuario_nombre || '').toString();
        const usuarioRol = (novedad.usuario_rol || '').toString();
        const descripcion = (novedad.descripcion || novedad.novedad_texto || '').toString();
        const descripcionEscaped = descripcion.replace(/'/g, "\\'");

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

export function guardarNovedad() {
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

    const numeroPedido = document.getElementById('novedadNumeroPedido')?.value;
    const prendaId = document.getElementById('novedadPrendaId')?.value;
    const numeroRecibo = document.getElementById('novedadNumeroRecibo')?.value;

    const btnGuardar = document.getElementById('btnGuardarNovedad');
    const textoOriginal = btnGuardar ? btnGuardar.innerHTML : '';

    if (btnGuardar) {
        btnGuardar.disabled = true;
        btnGuardar.innerHTML =
            '<span class="material-symbols-rounded" style="animation: spin 1s linear infinite;">refresh</span> Guardando...';
    }

    httpJsonBody(
        '/operario/api/novedades/crear',
        'POST',
        {
            numero_pedido: numeroPedido,
            prenda_id: prendaId,
            numero_recibo: numeroRecibo,
            novedad_texto: descripcion,
            tipo_novedad: 'observacion',
        },
        {}
    )
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                textareaDescripcion.value = '';
                cargarNovedadesDelUsuario(numeroPedido, prendaId);
                mostrarExito('Éxito', 'Novedad registrada correctamente');
            } else {
                mostrarError('Error', data.message || 'Error registrando novedad');
            }
        })
        .catch((error) => {
            console.error('Error guardando novedad:', error);
            mostrarError('Error', 'Error guardando novedad');
        })
        .finally(() => {
            if (btnGuardar) {
                btnGuardar.disabled = false;
                btnGuardar.innerHTML = textoOriginal;
            }
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
        btnGuardar.onclick = window.guardarNovedad;
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

export function editarNovedad(novedadId, textoActual, tipoActual) {
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
    btnGuardar.onclick = function () {
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
        btnGuardar.innerHTML =
            '<span class="material-symbols-rounded" style="animation: spin 1s linear infinite;">refresh</span> Actualizando...';

        httpJsonBody(`/operario/api/novedades/${novedadId}`, 'PUT', {
            novedad_texto: descripcion,
            tipo_novedad: tipo,
        })
            .then((r) => r.json())
            .then((data) => {
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
            .catch((err) => {
                console.error('Error actualizando novedad:', err);
                mostrarError('Error', 'Error actualizando novedad');
            })
            .finally(() => {
                btnGuardar.disabled = false;
                btnGuardar.innerHTML = 'Actualizar Novedad';
            });
    };
}

export function eliminarNovedad(novedadId) {
    if (!confirm('¿Eliminar esta novedad?')) {
        return;
    }

    const numeroPedido = document.getElementById('novedadNumeroPedido')?.value;
    const prendaId = document.getElementById('novedadPrendaId')?.value;

    httpJson(`/operario/api/novedades/${novedadId}`, {
        method: 'DELETE',
        headers: {},
    })
        .then((r) => r.json())
        .then((data) => {
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
        .catch((err) => {
            console.error('Error eliminando novedad:', err);
            mostrarError('Error', 'Error eliminando novedad');
        });
}

