import { httpJson } from '../api/http';

export function cargarUsuariosCostura(tipoRecibo = '') {
    const datalist = document.getElementById('listaEncargados');
    if (!datalist) return;

    datalist.innerHTML = '';

    const qs = new URLSearchParams();
    const tr = String(tipoRecibo || '').trim().toUpperCase();
    if (tr) {
        qs.set('tipo_recibo', tr);
    }
    const url = qs.toString() ? `/api/usuarios/costura?${qs.toString()}` : '/api/usuarios/costura';

    fetch(url, {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success && data.usuarios) {
                data.usuarios.forEach((usuario) => {
                    const option = document.createElement('option');
                    option.value = usuario.name;
                    datalist.appendChild(option);
                });
            }
        })
        .catch((error) => {
            console.error('Error cargando usuarios de costura:', error);
        });
}

export function cargarTalleresDisponibles() {
    const datalist = document.getElementById('listaTalleresUnicos');
    if (!datalist) return;

    datalist.innerHTML = '';

    httpJson('/api/usuarios/taller')
        .then((response) => response.json())
        .then((data) => {
            if (data.success && data.usuarios) {
                data.usuarios.forEach((usuario) => {
                    const option = document.createElement('option');
                    option.value = usuario.name;
                    datalist.appendChild(option);
                });
            }
        })
        .catch((error) => {
            console.error('Error cargando talleres:', error);
        });
}

export function cargarTalleresParaDistribucion() {
    const datalist = document.getElementById('listaTalleresMultiplesDatalist');
    if (!datalist) return Promise.resolve([]);

    datalist.innerHTML = '';

    return httpJson('/api/usuarios/taller')
        .then((response) => {
            if (response?.ok === false) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            return response.json();
        })
        .then((data) => {
            if (data.success && data.usuarios) {
                window.talleresDisponibles = data.usuarios;
                window.talleresDisponiblesAsignacion = data.usuarios;

                const talleresSeleccionadosIds = (window.talleresSeleccionadosDistribucion || []).map((t) => t.id);

                data.usuarios.forEach((usuario) => {
                    if (!talleresSeleccionadosIds.includes(usuario.id)) {
                        const option = document.createElement('option');
                        option.value = usuario.name;
                        datalist.appendChild(option);
                    }
                });
            }

            return data?.usuarios || [];
        })
        .catch((error) => {
            console.error('Error cargando talleres para distribución:', error);
            return [];
        });
}
