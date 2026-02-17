@extends('supervisor-pedidos.layout')

@section('title', 'Pendientes Logo')
@section('page-title', 'Pendientes Logo')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="supervisor-pedidos-container">
                <div class="d-flex justify-content-end mb-3">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="recargarDatos()" style="position: relative; top: 6px;">
                        <span class="material-symbols-rounded me-1">refresh</span>
                        Recargar
                    </button>
                </div>

                <!-- Tabla de Órdenes - Diseño asesores/pedidos -->
                <div style="transform: scale(0.80); transform-origin: top left; width: 133.333333%;">
                    <div style="background: #e5e7eb; border-radius: 8px; overflow: visible; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); padding: 0.75rem; width: 100%; max-width: 100%;">
                        <!-- Contenedor con Scroll -->
                        <div class="table-scroll-container" style="overflow-x: auto; overflow-y: auto; width: 100%; max-width: 100%; max-height: 800px; border-radius: 6px; scrollbar-width: thin; scrollbar-color: #cbd5e1 #f1f5f9;">
                            <!-- Header Azul -->
                            <div style="
                                background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
                                color: white;
                                padding: 0.75rem 1rem;
                                display: grid;
                                grid-template-columns: 170px 110px 200px 150px 140px 130px 160px 170px;
                                gap: 0.6rem;
                                font-weight: 600;
                                font-size: 0.8rem;
                                text-transform: uppercase;
                                letter-spacing: 0.5px;
                                min-width: min-content;
                                border-radius: 6px;
                            ">
                                <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span>Fecha de Creación</span>
                                    <button type="button" class="btn-filter-column" data-col="fecha_creacion" title="Filtrar Fecha de Creación" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;">
                                        <span class="material-symbols-rounded" style="font-size: 1rem;">filter_alt</span>
                                    </button>
                                </div>
                                <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span>N° Recibo</span>
                                    <button type="button" class="btn-filter-column" data-col="numero_recibo" title="Filtrar N° Recibo" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;">
                                        <span class="material-symbols-rounded" style="font-size: 1rem;">filter_alt</span>
                                    </button>
                                </div>
                                <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span>Cliente</span>
                                    <button type="button" class="btn-filter-column" data-col="cliente" title="Filtrar Cliente" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;">
                                        <span class="material-symbols-rounded" style="font-size: 1rem;">filter_alt</span>
                                    </button>
                                </div>
                                <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span>Cantidad de Prendas</span>
                                    <button type="button" class="btn-filter-column" data-col="cantidad" title="Filtrar Cantidad de Prendas" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;">
                                        <span class="material-symbols-rounded" style="font-size: 1rem;">filter_alt</span>
                                    </button>
                                </div>
                                <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span>Asesor</span>
                                    <button type="button" class="btn-filter-column" data-col="asesor" title="Filtrar Asesor" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;">
                                        <span class="material-symbols-rounded" style="font-size: 1rem;">filter_alt</span>
                                    </button>
                                </div>
                                <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span>Logo</span>
                                    <button type="button" class="btn-filter-column" data-col="logo" title="Filtrar Logo" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;">
                                        <span class="material-symbols-rounded" style="font-size: 1rem;">filter_alt</span>
                                    </button>
                                </div>
                                <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span>Fecha de aprobación</span>
                                    <button type="button" class="btn-filter-column" data-col="fecha_aprobacion" title="Filtrar Fecha de aprobación" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;">
                                        <span class="material-symbols-rounded" style="font-size: 1rem;">filter_alt</span>
                                    </button>
                                </div>
                                <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem; padding-left: 10px;">
                                    <span>Fecha de llegada</span>
                                    <button type="button" class="btn-filter-column" data-col="fecha_llegada" title="Filtrar Fecha de llegada" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;">
                                        <span class="material-symbols-rounded" style="font-size: 1rem;">filter_alt</span>
                                    </button>
                                </div>
                            </div>

                            <div id="pendientesRows">
                                <!-- Filas -->
                                @if($procesosConCantidad->isEmpty())
                                    <div style="padding: 3rem 2rem; text-align: center; color: #6b7280;">
                                        <i class="fas fa-inbox" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem; display: block;"></i>
                                        <p style="font-size: 1rem; margin: 0;">No hay pendientes</p>
                                    </div>
                                @else
                                    @foreach($procesosConCantidad as $proceso)
                                        <div data-row="proceso" style="
                                            display: grid;
                                            grid-template-columns: 170px 110px 200px 150px 140px 130px 160px 170px;
                                            gap: 0.6rem;
                                            padding: 1rem;
                                            border-bottom: 1px solid #e5e7eb;
                                            align-items: center;
                                            min-width: min-content;
                                            background: white;
                                            transition: background 0.2s ease;
                                        " onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='white'">
                                        <div>
                                            <span>{{ \Carbon\Carbon::parse($proceso->fecha_creacion)->format('d/m/Y H:i') }}</span>
                                        </div>

                                        <div>
                                            <span style="font-weight: 600; color: #1e5ba8;">{{ $proceso->numero_recibo ?? 'Sin asignar' }}</span>
                                        </div>

                                        <div>
                                            <span>{{ $proceso->cliente }}</span>
                                        </div>

                                        <div>
                                            <span style="background: #e8f3ff; color: #1e40af; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap; border: 1px solid #bfdbfe; display: inline-block;">
                                                {{ $proceso->cantidad_total_prendas }} {{ $proceso->nombre_prenda ?: '' }}
                                            </span>
                                        </div>

                                        <div>
                                            <span>{{ $proceso->asesor }}</span>
                                        </div>

                                        <div>
                                            @switch($proceso->tipo_recibo)
                                                @case('BORDADO')
                                                    <span style="background: #f3e8ff; color: #6b21a8; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap; display: inline-block;">
                                                        Bordado
                                                    </span>
                                                    @break
                                                @case('ESTAMPADO')
                                                    <span style="background: #ffedd5; color: #9a3412; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap; display: inline-block;">
                                                        Estampado
                                                    </span>
                                                    @break
                                                @case('SUBLIMADO')
                                                    <span style="background: #cffafe; color: #155e75; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap; display: inline-block;">
                                                        Sublimado
                                                    </span>
                                                    @break
                                                @case('DTF')
                                                    <span style="background: #fce7f3; color: #9d174d; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap; display: inline-block;">
                                                        DTF
                                                    </span>
                                                    @break
                                                @default
                                                    <span style="background: #f3f4f6; color: #6b7280; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap; display: inline-block;">
                                                        {{ $proceso->tipo_recibo }}
                                                    </span>
                                            @endswitch
                                        </div>

                                        <div>
                                            @if($proceso->fecha_aprobacion)
                                                <span>{{ \Carbon\Carbon::parse($proceso->fecha_aprobacion)->format('d/m/Y H:i') }}</span>
                                            @else
                                                <span style="background: #f3f4f6; color: #9ca3af; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap; display: inline-block;">--</span>
                                            @endif
                                        </div>

                                        <div style="padding-left: 10px;">
                                            <input
                                                type="datetime-local"
                                                class="input-fecha-llegada"
                                                data-recibo-id="{{ $proceso->recibo_id }}"
                                                value="{{ $proceso->fecha_llegada ? \Carbon\Carbon::parse($proceso->fecha_llegada)->format('Y-m-d\\TH:i') : '' }}"
                                                style="
                                                    width: 100%;
                                                    max-width: 160px;
                                                    padding: 6px 8px;
                                                    border-radius: 8px;
                                                    border: 1px solid #cbd5e1;
                                                    font-size: 0.8rem;
                                                    outline: none;
                                                "
                                            />
                                        </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<button id="btnLimpiarFiltrosFlotante" type="button" onclick="limpiarTodosLosFiltrosPendientes()" style="display: none; position: fixed; right: 18px; bottom: 18px; z-index: 9998; background: #111827; color: #ffffff; border: 1px solid rgba(255,255,255,0.15); border-radius: 999px; padding: 10px 14px; font-size: 0.85rem; font-weight: 600; box-shadow: 0 10px 25px rgba(0,0,0,0.25); cursor: pointer;">
    Limpiar filtros
</button>

<!-- Modal Filtro Dinámico -->
<div id="modalFiltro" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 9999; align-items: center; justify-content: center; padding: 1rem;">
    <div class="modal-content" style="width: 90%; max-width: 420px; position: relative;">
        <div class="modal-header">
            <h2 id="modalFiltroTitulo">Filtrar</h2>
            <button class="btn-close" type="button" onclick="cerrarModalFiltro()">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>
        <div class="modal-body">
            <form id="formFiltroColumna" onsubmit="aplicarFiltroColumna(event)">
                <div class="form-group" id="filtroContenido"></div>
                <div class="form-actions" style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem;">
                    <button type="button" class="btn btn-secondary" onclick="limpiarFiltroActual()">Limpiar</button>
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalFiltro()">Cancelar</button>
                    <button type="submit" class="btn btn-primary" style="background: var(--primary-color); color: white;">Aplicar Filtro</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para ver detalles del proceso -->
<div class="modal fade" id="modalDetalles" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles del Proceso</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalles-contenido">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" id="btn-aprobar-modal">Aprobar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .bg-purple { background-color: #6f42c1 !important; }
    .bg-orange { background-color: #fd7e14 !important; }
    .bg-cyan { background-color: #17a2b8 !important; }
    .bg-pink { background-color: #e83e8c !important; }
    
    .table-responsive {
        max-height: 70vh;
        overflow-y: auto;
    }
    
    .badge {
        font-size: 0.85em;
    }
    
    .material-symbols-rounded {
        vertical-align: middle;
    }
</style>
@endpush

@push('scripts')
<script>
function verDetalles(procesoId, tipoRecibo) {
    // Mostrar modal con loading
    const modal = new bootstrap.Modal(document.getElementById('modalDetalles'));
    document.getElementById('detalles-contenido').innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Cargando detalles...</p>
        </div>
    `;
    
    // Configurar botón aprobar
    document.getElementById('btn-aprobar-modal').onclick = function() {
        aprobarProceso(procesoId);
        modal.hide();
    };
    
    modal.show();
    
    // Cargar detalles del proceso
    fetch(`/supervisor-pedidos/procesos/${procesoId}/detalles`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderizarDetalles(data.data);
            } else {
                document.getElementById('detalles-contenido').innerHTML = `
                    <div class="alert alert-danger">
                        Error: ${data.message}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('detalles-contenido').innerHTML = `
                <div class="alert alert-danger">
                    Error al cargar los detalles del proceso.
                </div>
            `;
        });
}

function renderizarDetalles(data) {
    let html = `
        <div class="row">
            <div class="col-md-6">
                <h6>Información General</h6>
                <table class="table table-sm">
                    <tr><td><strong>Prenda:</strong></td><td>${data.nombre_prenda}</td></tr>
                    <tr><td><strong>Tipo:</strong></td><td>${data.tipo_recibo}</td></tr>
                    <tr><td><strong>Estado:</strong></td><td><span class="badge bg-warning">${data.estado}</span></td></tr>
                    <tr><td><strong>Observaciones:</strong></td><td>${data.observaciones || 'N/A'}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Tallas y Cantidades</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Género</th>
                                <th>Talla</th>
                                <th>Cantidad</th>
                            </tr>
                        </thead>
                        <tbody>
    `;
    
    // Agregar tallas
    if (data.tallas && data.tallas.length > 0) {
        data.tallas.forEach(talla => {
            html += `
                <tr>
                    <td>${talla.genero}</td>
                    <td>${talla.talla}</td>
                    <td>${talla.cantidad}</td>
                </tr>
            `;
        });
    } else {
        html += `<tr><td colspan="3" class="text-center">No hay tallas registradas</td></tr>`;
    }
    
    html += `
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
    
    // Agregar imágenes si hay
    if (data.imagenes && data.imagenes.length > 0) {
        html += `
            <div class="row mt-3">
                <div class="col-12">
                    <h6>Imágenes del Proceso</h6>
                    <div class="row">
        `;
        
        data.imagenes.forEach(imagen => {
            html += `
                <div class="col-md-3 mb-3">
                    <img src="/storage/${imagen.ruta_webp || imagen.ruta_original}" 
                         class="img-fluid img-thumbnail" 
                         alt="Imagen del proceso"
                         onclick="window.open('/storage/${imagen.ruta_webp || imagen.ruta_original}', '_blank')"
                         style="cursor: pointer;">
                </div>
            `;
        });
        
        html += `
                    </div>
                </div>
            </div>
        `;
    }
    
    document.getElementById('detalles-contenido').innerHTML = html;
}

function aprobarProceso(procesoId) {
    if (!confirm('¿Está seguro de aprobar este proceso?')) {
        return;
    }
    
    fetch(`/supervisor-pedidos/procesos/${procesoId}/aprobar`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Proceso aprobado correctamente');
            recargarDatos();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al aprobar el proceso');
    });
}

function verPedido(pedidoId) {
    window.open(`/supervisor-pedidos/${pedidoId}`, '_blank');
}

function recargarDatos() {
    recargarTablaPendientes();
}

function mostrarCargandoTablaPendientes() {
    const cont = document.getElementById('pendientesRows');
    if (!cont) return;
    cont.innerHTML = `
        <div style="padding: 2.5rem 1.5rem; text-align: center; color: #6b7280; background: white; border-bottom: 1px solid #e5e7eb;">
            <div style="display: inline-flex; align-items: center; gap: 0.6rem; font-weight: 600;">
                <span class="material-symbols-rounded" style="font-size: 1.2rem; animation: spinPendientes 0.8s linear infinite;">progress_activity</span>
                <span>Cargando datos...</span>
            </div>
        </div>
    `;
}

async function recargarTablaPendientes() {
    const cont = document.getElementById('pendientesRows');
    if (!cont) return;

    mostrarCargandoTablaPendientes();

    try {
        const resp = await fetch(window.location.href, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const html = await resp.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const nuevo = doc.getElementById('pendientesRows');
        if (!nuevo) {
            throw new Error('No se encontró el contenedor de filas en la respuesta');
        }

        cont.innerHTML = nuevo.innerHTML;

        inicializarPendientesUI();
        aplicarFiltrosEnVista();
        actualizarIndicadoresFiltrosPendientes();
    } catch (e) {
        console.error(e);
        cont.innerHTML = `
            <div style="padding: 2.5rem 1.5rem; text-align: center; color: #6b7280; background: white; border-bottom: 1px solid #e5e7eb;">
                No se pudieron cargar los datos. Intenta nuevamente.
            </div>
        `;
    }
}

function inicializarPendientesUI() {
    const inputs = document.querySelectorAll('.input-fecha-llegada');
    const onInputDebounced = debounce(async function(e) {
        const el = e.target;
        try {
            el.style.borderColor = '#cbd5e1';
            await guardarFechaLlegada(el);
            el.style.borderColor = '#10b981';
        } catch (err) {
            console.error(err);
            el.style.borderColor = '#ef4444';
        }
    }, 700);

    inputs.forEach((el) => {
        el.addEventListener('input', onInputDebounced);
        el.addEventListener('blur', async function() {
            try {
                el.style.borderColor = '#cbd5e1';
                await guardarFechaLlegada(el);
                el.style.borderColor = '#10b981';
            } catch (err) {
                console.error(err);
                el.style.borderColor = '#ef4444';
            }
        });
    });

    document.querySelectorAll('.btn-filter-column').forEach((btn) => {
        if (btn.getAttribute('data-filter-init') === '1') return;
        btn.setAttribute('data-filter-init', '1');
        btn.addEventListener('click', function() {
            abrirModalFiltroPendientes(btn.getAttribute('data-col'));
        });
    });
}

let filtroActual = null;
const filtrosPendientes = {};

function hayFiltrosActivosPendientes() {
    return Object.values(filtrosPendientes).some((regla) => {
        const values = regla?.values || [];
        return values.length > 0;
    });
}

function actualizarIndicadoresFiltrosPendientes() {
    document.querySelectorAll('.btn-filter-column').forEach((btn) => {
        btn.classList.remove('has-filter');
        const badge = btn.querySelector('.filter-badge');
        if (badge) badge.remove();

        const col = btn.getAttribute('data-col');
        const values = filtrosPendientes[col]?.values || [];
        if (values.length > 0) {
            btn.classList.add('has-filter');
            const b = document.createElement('span');
            b.className = 'filter-badge';
            b.textContent = values.length;
            b.style.cssText = 'position:absolute; top:-6px; right:-6px; min-width:16px; height:16px; padding:0 4px; display:flex; align-items:center; justify-content:center; font-size:10px; line-height:1; background:#f59e0b; color:#111827; border-radius:999px; font-weight:800; box-shadow:0 2px 6px rgba(0,0,0,0.25);';
            btn.style.position = 'relative';
            btn.appendChild(b);
        }
    });

    const flotante = document.getElementById('btnLimpiarFiltrosFlotante');
    if (flotante) {
        flotante.style.display = hayFiltrosActivosPendientes() ? 'block' : 'none';
    }
}

function limpiarTodosLosFiltrosPendientes() {
    Object.keys(filtrosPendientes).forEach((k) => delete filtrosPendientes[k]);
    aplicarFiltrosEnVista();
    actualizarIndicadoresFiltrosPendientes();
    cerrarModalFiltro();
}

function abrirModalFiltroPendientes(columna) {
    filtroActual = columna;
    const modal = document.getElementById('modalFiltro');
    const modalTitulo = document.getElementById('modalFiltroTitulo');
    const filtroContenido = document.getElementById('filtroContenido');

    const tituloMap = {
        fecha_creacion: 'Filtrar Fecha de Creación',
        numero_recibo: 'Filtrar N° Recibo',
        cliente: 'Filtrar Cliente',
        cantidad: 'Filtrar Cantidad de Prendas',
        asesor: 'Filtrar Asesor',
        logo: 'Filtrar Logo',
        fecha_aprobacion: 'Filtrar Fecha de aprobación',
        fecha_llegada: 'Filtrar Fecha de llegada'
    };

    modalTitulo.textContent = tituloMap[columna] || 'Filtrar';

    if (columna === 'fecha_creacion' || columna === 'fecha_aprobacion' || columna === 'fecha_llegada') {
        const opciones = obtenerOpcionesDesdeFilas(columna);
        const seleccionadas = (filtrosPendientes[columna]?.values) || [];

        filtroContenido.innerHTML = `
            <div class="form-group">
                <input type="text" id="buscadorFiltro" class="form-control" placeholder="Buscar..." style="margin-bottom: 1rem;" />
                <div id="listaOpciones" style="max-height: 300px; overflow-y: auto;">
                    ${opciones.map(opcion => {
                        const checked = seleccionadas.includes(opcion) ? 'checked' : '';
                        const label = opcion ? formatDateLabel(opcion) : '(Sin fecha)';
                        return `
                            <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; cursor: pointer; border-radius: 4px;">
                                <input type="checkbox" class="filtro-checkbox" value="${escapeHtml(opcion)}" ${checked} />
                                <span>${escapeHtml(label)}</span>
                            </label>
                        `;
                    }).join('')}
                </div>
            </div>
        `;

        setTimeout(() => {
            document.getElementById('buscadorFiltro')?.addEventListener('input', function(e) {
                const valor = e.target.value.toLowerCase();
                document.querySelectorAll('#listaOpciones label').forEach(label => {
                    const texto = label.textContent.toLowerCase();
                    label.style.display = texto.includes(valor) ? 'flex' : 'none';
                });
            });
        }, 0);
    } else {
        const opciones = obtenerOpcionesDesdeFilas(columna);
        const seleccionadas = (filtrosPendientes[columna]?.values) || [];

        filtroContenido.innerHTML = `
            <div class="form-group">
                <input type="text" id="buscadorFiltro" class="form-control" placeholder="Buscar..." style="margin-bottom: 1rem;" />
                <div id="listaOpciones" style="max-height: 300px; overflow-y: auto;">
                    ${opciones.map(opcion => {
                        const checked = seleccionadas.includes(opcion) ? 'checked' : '';
                        const label = opcion || '(Sin especificar)';
                        return `
                            <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; cursor: pointer; border-radius: 4px;">
                                <input type="checkbox" class="filtro-checkbox" value="${escapeHtml(opcion)}" ${checked} />
                                <span>${escapeHtml(label)}</span>
                            </label>
                        `;
                    }).join('')}
                </div>
            </div>
        `;

        setTimeout(() => {
            document.getElementById('buscadorFiltro')?.addEventListener('input', function(e) {
                const valor = e.target.value.toLowerCase();
                document.querySelectorAll('#listaOpciones label').forEach(label => {
                    const texto = label.textContent.toLowerCase();
                    label.style.display = texto.includes(valor) ? 'flex' : 'none';
                });
            });
        }, 0);
    }

    modal.style.display = 'flex';
}

function cerrarModalFiltro() {
    document.getElementById('modalFiltro').style.display = 'none';
    filtroActual = null;
}

function limpiarFiltroActual() {
    if (!filtroActual) return;
    delete filtrosPendientes[filtroActual];
    aplicarFiltrosEnVista();
    actualizarIndicadoresFiltrosPendientes();
    cerrarModalFiltro();
}

function aplicarFiltroColumna(event) {
    event.preventDefault();
    if (!filtroActual) return;

    const checkboxes = document.querySelectorAll('.filtro-checkbox:checked');
    const values = Array.from(checkboxes).map(cb => cb.value);
    if (values.length > 0) {
        filtrosPendientes[filtroActual] = { values };
    } else {
        delete filtrosPendientes[filtroActual];
    }

    aplicarFiltrosEnVista();
    actualizarIndicadoresFiltrosPendientes();
    cerrarModalFiltro();
}

function aplicarFiltrosEnVista() {
    const filas = document.querySelectorAll('[data-row="proceso"]');

    filas.forEach((fila) => {
        let visible = true;

        for (const [col, regla] of Object.entries(filtrosPendientes)) {
            if (!regla) continue;

            const valor = leerValorColumnaFila(fila, col);

            const values = regla.values || [];
            if (values.length > 0) {
                if (!values.includes(valor)) {
                    visible = false;
                    break;
                }
            }
        }

        fila.style.display = visible ? 'grid' : 'none';
    });
}

function obtenerOpcionesDesdeFilas(col) {
    const filas = document.querySelectorAll('[data-row="proceso"]');
    const set = new Set();

    filas.forEach((fila) => {
        set.add(leerValorColumnaFila(fila, col));
    });

    return Array.from(set).sort((a, b) => String(a).localeCompare(String(b)));
}

function formatDateLabel(yyyyMmDd) {
    if (!yyyyMmDd) return '';
    const parts = String(yyyyMmDd).split('-');
    if (parts.length !== 3) return String(yyyyMmDd);
    const [y, m, d] = parts;
    if (!y || !m || !d) return String(yyyyMmDd);
    return `${d}/${m}/${y}`;
}

function leerValorColumnaFila(fila, col) {
    const map = {
        fecha_creacion: 0,
        numero_recibo: 1,
        cliente: 2,
        cantidad: 3,
        asesor: 4,
        logo: 5,
        fecha_aprobacion: 6,
        fecha_llegada: 7,
    };

    const idx = map[col];
    const cell = fila.children[idx];
    if (!cell) return '';

    if (col === 'fecha_llegada') {
        const input = cell.querySelector('input[type="datetime-local"]');
        return input?.value ? input.value.substring(0, 10) : '';
    }

    if (col === 'fecha_aprobacion') {
        const t = cell.textContent.trim();
        if (t === '--' || t === '') return '';
        const parts = t.split(' ');
        if (parts.length >= 2) {
            const [d, m, y] = parts[0].split('/');
            if (d && m && y) return `${y}-${m}-${d}`;
        }
        return '';
    }

    if (col === 'fecha_creacion') {
        const t = cell.textContent.trim();
        const parts = t.split(' ');
        if (parts.length >= 2) {
            const [d, m, y] = parts[0].split('/');
            if (d && m && y) return `${y}-${m}-${d}`;
        }
        return '';
    }

    const text = cell.textContent.trim();
    return text;
}

function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function debounce(fn, wait) {
    let t;
    return function(...args) {
        clearTimeout(t);
        t = setTimeout(() => fn.apply(this, args), wait);
    };
}

async function guardarFechaLlegada(input) {
    const reciboId = input.getAttribute('data-recibo-id');
    const fechaLlegada = input.value;

    const resp = await fetch(`/supervisor-pedidos/recibos/${reciboId}/fecha-llegada`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ fecha_llegada: fechaLlegada })
    });

    const data = await resp.json();
    if (!data.success) {
        throw new Error(data.message || 'Error al guardar fecha de llegada');
    }
}

// Inicializar DataTable si está disponible
$(document).ready(function() {
    if ($.fn.DataTable && false) {
        $('#tabla-pendientes').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es.json'
            },
            order: [[0, 'desc']],
            responsive: true,
            pageLength: 25
        });
    }

    inicializarPendientesUI();

    const overlay = document.getElementById('modalFiltro');
    overlay?.addEventListener('click', function(e) {
        if (e.target === overlay) {
            cerrarModalFiltro();
        }
    });

    actualizarIndicadoresFiltrosPendientes();
});

const styleSpin = document.createElement('style');
styleSpin.textContent = `@keyframes spinPendientes { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }`;
document.head.appendChild(styleSpin);
</script>
@endpush
