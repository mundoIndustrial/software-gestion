import { httpJson, httpJsonBody } from '../api/http';
import { mostrarError, mostrarExito } from '../ui/messages';

export function manejarPasarACostura(btn) {
    const pedidoId = btn.dataset.pedidoId;
    const prendaId = btn.dataset.prendaId;
    const nombre = btn.dataset.nombre;
    const tipoRecibo = btn.dataset.tipoRecibo;
    const recibo = btn.dataset.recibo;
    const btnId = btn.id;

    console.log(' Manejar pasar a costura:', {
        pedidoId,
        prendaId,
        nombre,
        tipoRecibo,
        recibo,
        area: btn.dataset.area,
        procesoId: btn.dataset.procesoId,
        encargadoCostura: btn.dataset.encargadoCostura,
        btnId,
    });

    const esDeshacer = btn.classList.contains('btn-deshacer-costura');

    if (esDeshacer) {
        deshacerCosturaVista(pedidoId, prendaId, tipoRecibo, btnId);
    } else {
        abrirModalCostura(pedidoId, prendaId, nombre, tipoRecibo, recibo, btnId);
    }
}

export function abrirModalCostura(pedidoId, prendaId, nombre, tipoRecibo, recibo, btnId) {
    const modal = document.getElementById('modalCostura');
    if (!modal) return;

    const prendaNombreEl = document.getElementById('costuraPrendaNombre');
    const reciboNumeroEl = document.getElementById('costuraReciboNumero');
    const tipoReciboEl = document.getElementById('costuraTipoRecibo');

    if (prendaNombreEl) prendaNombreEl.textContent = nombre;
    if (reciboNumeroEl) reciboNumeroEl.textContent = recibo;
    if (tipoReciboEl) tipoReciboEl.textContent = tipoRecibo;

    cargarUsuariosCostura(tipoRecibo);

    window.costuraPendiente = { pedidoId, prendaId, tipoRecibo, btnId, recibo };
    modal.style.display = 'flex';
}

export function cargarUsuariosCostura(tipoRecibo = '') {
    const select = document.getElementById('costuraEncargado');
    if (!select) return;

    select.innerHTML = '<option value="">Cargando...</option>';

    const qs = new URLSearchParams();
    const tr = String(tipoRecibo || '').trim().toUpperCase();
    if (tr) {
        qs.set('tipo_recibo', tr);
    }
    const url = qs.toString() ? `/api/usuarios/costura?${qs.toString()}` : '/api/usuarios/costura';

    httpJson(url)
        .then((response) => response.json())
        .then((data) => {
            select.innerHTML = '<option value="">Seleccione un encargado...</option>';
            if (data.success && data.usuarios) {
                data.usuarios.forEach((usuario) => {
                    const option = document.createElement('option');
                    option.value = usuario.name;
                    option.textContent = usuario.name;
                    select.appendChild(option);
                });
            } else {
                select.innerHTML = '<option value="">No hay usuarios disponibles</option>';
            }
        })
        .catch((error) => {
            console.error('Error cargando usuarios de costura:', error);
            select.innerHTML = '<option value="">Error al cargar usuarios</option>';
        });
}

export function cerrarModalCostura() {
    const modal = document.getElementById('modalCostura');
    if (modal) modal.style.display = 'none';
    window.costuraPendiente = null;
}

export function confirmarPasarACostura() {
    const encargado = document.getElementById('costuraEncargado')?.value.trim();
    if (!encargado) {
        mostrarError('Error', 'Debes seleccionar un encargado de costura');
        return;
    }

    if (!window.costuraPendiente) {
        mostrarError('Error', 'No hay datos de la prenda pendiente');
        return;
    }

    const { pedidoId, prendaId, tipoRecibo, btnId, recibo } = window.costuraPendiente;

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
        encargado,
    });

    const btn = document.getElementById(btnId);
    if (!btn) {
        mostrarError('Error', 'No se encontró el botón de acción');
        return;
    }

    const originalHTML = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-rounded" style="animation: spin 1s linear infinite;">refresh</span> Procesando...';

    // La lógica original usa form+FormData hacia /recibos-novedades/{pedidoId}/{recibo}/pasar-a-costura
    const action = `/recibos-novedades/${pedidoId}/${recibo}/pasar-a-costura`;

    const formData = new FormData();
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    formData.append('prenda_id', prendaId);
    formData.append('encargado', encargado);
    formData.append('tipo_recibo', tipoRecibo);
    formData.append('_method', 'POST');

    fetch(action, {
        method: 'POST',
        body: formData,
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    })
        .then((response) => response.json())
        .then((data) => {
            console.log('Respuesta del servidor:', data);

            if (data.success) {
                btn.dataset.encargadoCostura = encargado;
                btn.dataset.procesoId = data.data?.proceso_id || '';
                btn.classList.add('btn-deshacer-costura');
                btn.innerHTML = '<span class="material-symbols-rounded">undo</span> DESHACER COSTURA';
                cerrarModalCostura();
                mostrarExito('Éxito', data.message || 'Prenda asignada a costura correctamente');
            } else {
                btn.innerHTML = originalHTML;
                mostrarError('Error', data.message || 'Error asignando a costura');
            }
        })
        .catch((error) => {
            console.error('Error:', error);
            btn.innerHTML = originalHTML;
            mostrarError('Error', 'Error de conexión: ' + error.message);
        })
        .finally(() => {
            btn.disabled = false;
        });
}

export function deshacerCosturaVista(pedidoId, prendaId, tipoRecibo, btnId) {
    console.log('[DESHACER-COSTURA] Iniciando función:', { pedidoId, prendaId, tipoRecibo, btnId });

    const btn = document.getElementById(btnId);
    if (!btn || btn.disabled) return;

    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-rounded" style="animation: spin 1s linear infinite;">refresh</span> Deshaciendo...';

    console.log('[DESHACER-COSTURA] Enviando DELETE a:', `/recibos-novedades/${pedidoId}/${prendaId}/deshacer-costura`);

    httpJsonBody(`/recibos-novedades/${pedidoId}/${prendaId}/deshacer-costura`, 'DELETE', {
        tipo_recibo: tipoRecibo,
    }, {
        headers: {
            Accept: 'application/json',
        },
    })
        .then((response) => {
            console.log('[DESHACER-COSTURA] Respuesta recibida:', response.status, response.statusText);

            if (!response.ok) {
                if (response.redirected) {
                    console.error('[DESHACER-COSTURA] La petición fue redirigida a:', response.url);
                }
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            return response.json();
        })
        .then((data) => {
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
        .catch((error) => {
            console.error('Error:', error);
            btn.innerHTML = originalHTML;
            mostrarError('Error', 'Error de conexión');
        })
        .finally(() => {
            btn.disabled = false;
        });
}
