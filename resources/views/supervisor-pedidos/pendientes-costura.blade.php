@extends('supervisor-pedidos.layout')

@section('title', 'Pendiente Costura')
@section('page-title', 'Pendiente Costura')

@push('styles')
<style>
    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.45);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .modal-content {
        background: white;
        border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        padding: 2rem;
        max-width: 500px;
        width: 100%;
        max-height: 80vh;
        display: flex;
        flex-direction: column;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .btn-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #6b7280;
    }

    .modal-body {
        flex: 1;
        overflow-y: auto;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-control {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 0.9rem;
    }

    .modal-footer {
        display: flex;
        gap: 0.5rem;
        margin-top: 1.5rem;
    }

    .btn {
        padding: 0.5rem 1rem;
        border-radius: 6px;
        border: 1px solid transparent;
        cursor: pointer;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .btn-primary {
        background: #3b82f6;
        color: white;
    }

    .btn-secondary {
        background: #e5e7eb;
        color: #1f2937;
    }

    .btn-filter-column {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    .btn-filter-column.has-filter {
        color: #f59e0b;
    }

    .filter-badge {
        position: absolute;
        top: -6px;
        right: -6px;
        min-width: 16px;
        height: 16px;
        padding: 0 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        line-height: 1;
        background: #f59e0b;
        color: #111827;
        border-radius: 999px;
        font-weight: 800;
        box-shadow: 0 2px 6px rgba(0,0,0,0.25);
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="supervisor-pedidos-container">
                <!-- Tabla de Órdenes -->
                <div style="background: #e5e7eb; border-radius: 8px; overflow: visible; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); padding: 0.75rem; width: 100%; max-width: 100%;">
                    <!-- Contenedor con Scroll -->
                    <div class="table-scroll-container" style="overflow-x: auto; overflow-y: auto; width: 100%; max-width: 100%; max-height: 800px; border-radius: 6px; scrollbar-width: thin; scrollbar-color: #cbd5e1 #f1f5f9;">
                        <!-- Header Azul -->
                        <div style="
                            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
                            color: white;
                            padding: 0.75rem 1rem;
                            display: grid;
                            grid-template-columns: 170px 110px 200px 300px 130px 100px;
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
                                    <i class="fas fa-filter" style="font-size: 1rem;"></i>
                                </button>
                            </div>
                            <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                                <span>N° Recibo</span>
                                <button type="button" class="btn-filter-column" data-col="numero_recibo" title="Filtrar N° Recibo" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;">
                                    <i class="fas fa-filter" style="font-size: 1rem;"></i>
                                </button>
                            </div>
                            <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                                <span>Cliente</span>
                                <button type="button" class="btn-filter-column" data-col="cliente" title="Filtrar Cliente" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;">
                                    <i class="fas fa-filter" style="font-size: 1rem;"></i>
                                </button>
                            </div>
                            <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                                <span>Prendas</span>
                                <button type="button" class="btn-filter-column" data-col="prendas" title="Filtrar Prendas" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;">
                                    <i class="fas fa-filter" style="font-size: 1rem;"></i>
                                </button>
                            </div>
                            <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                                <span>Asesora</span>
                                <button type="button" class="btn-filter-column" data-col="asesor" title="Filtrar Asesora" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;">
                                    <i class="fas fa-filter" style="font-size: 1rem;"></i>
                                </button>
                            </div>
                            <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                                <span>Color</span>
                            </div>
                        </div>

                        <div id="costurasRows">
                            <!-- Filas -->
                            @if($procesosConCantidad->isEmpty())
                                <div style="padding: 3rem 2rem; text-align: center; color: #6b7280;">
                                    <i class="fas fa-inbox" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem; display: block;"></i>
                                    <p style="font-size: 1rem; margin: 0;">No hay pendientes</p>
                                </div>
                            @else
                                @foreach($procesosConCantidad as $proceso)
                                    <div data-row="processo" data-color-stored="{{ $proceso['color_costura'] ?? '' }}" style="
                                        display: grid;
                                        grid-template-columns: 170px 110px 200px 300px 130px 100px;
                                        gap: 0.6rem;
                                        padding: 1rem;
                                        border-bottom: 1px solid #e5e7eb;
                                        align-items: start;
                                        min-width: min-content;
                                        background: white;
                                        transition: background 0.2s ease;
                                    " onmouseover="mostrarHoverFila(this)" onmouseout="restaurarColorFila(this)">
                                        
                                        <!-- Fecha de Creación -->
                                        <div style="display: flex; align-items: center; font-size: 0.9rem; color: #374151;">
                                            {{ \Carbon\Carbon::parse($proceso['fecha_creacion'])->format('d/m/Y') }}
                                        </div>

                                        <!-- Número de Recibo -->
                                        <div style="display: flex; align-items: center; font-size: 0.9rem; color: #374151; font-weight: 500;">
                                            {{ $proceso['numero_recibo'] }}
                                        </div>

                                        <!-- Cliente -->
                                        <div style="display: flex; align-items: center; font-size: 0.9rem; color: #374151;">
                                            {{ $proceso['cliente'] }}
                                        </div>

                                        <!-- Prendas -->
                                        <div style="display: flex; align-items: start; font-size: 0.9rem; color: #374151;">
                                            <div class="prenda-list">
                                                @php
                                                    $prendasAgrupadas = [];
                                                    foreach($proceso['prendas'] as $prenda) {
                                                        if(!empty($prenda->color_nombre) && !empty($prenda->cantidad_color)) {
                                                            // Con color
                                                            $key = $prenda->nombre_prenda . '|' . $prenda->color_nombre;
                                                            if(!isset($prendasAgrupadas[$key])) {
                                                                $prendasAgrupadas[$key] = $prenda->cantidad_color;
                                                            }
                                                        } elseif(!empty($prenda->cantidad_talla) && empty($prenda->color_nombre)) {
                                                            // Sin color
                                                            $tela = !empty($prenda->tela) ? ' ' . $prenda->tela : '';
                                                            $key = $prenda->nombre_prenda . $tela . '|sin-color';
                                                            if(!isset($prendasAgrupadas[$key])) {
                                                                $prendasAgrupadas[$key] = $prenda->cantidad_talla;
                                                            }
                                                        }
                                                    }
                                                @endphp
                                                @foreach($prendasAgrupadas as $prenda => $cantidad)
                                                    <div style="margin-bottom: 0.25rem;">
                                                        @php
                                                            $partes = explode('|', $prenda);
                                                            $nombrePrenda = $partes[0];
                                                            $tipo = $partes[1] ?? 'sin-color';
                                                            
                                                            if($tipo === 'sin-color') {
                                                                echo $cantidad . ' ' . $nombrePrenda;
                                                            } else {
                                                                echo $cantidad . ' ' . $nombrePrenda . ' color ' . $tipo;
                                                            }
                                                        @endphp
                                                    </div>
                                                @endforeach
                                                @if(count($prendasAgrupadas) === 0)
                                                    <div>-</div>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Asesora -->
                                        <div style="display: flex; align-items: center; font-size: 0.9rem; color: #374151;">
                                            {{ $proceso['asesor'] }}
                                        </div>

                                        <!-- Color Selector -->
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <div class="color-selector-wrapper" data-recibo-id="{{ $proceso['numero_recibo'] }}" style="position: relative; display: flex; gap: 0.3rem; align-items: center;">
                                                <button type="button" class="color-btn" data-color="#e0f2fe" title="Azul claro" style="width: 24px; height: 24px; border-radius: 50%; border: 2px solid #cbd5e1; background: #e0f2fe; cursor: pointer; transition: all 0.2s;"></button>
                                                <button type="button" class="color-btn" data-color="#fef08a" title="Amarillo" style="width: 24px; height: 24px; border-radius: 50%; border: 2px solid #cbd5e1; background: #fef08a; cursor: pointer; transition: all 0.2s;"></button>
                                                <button type="button" class="color-btn" data-color="#fecaca" title="Rojo claro" style="width: 24px; height: 24px; border-radius: 50%; border: 2px solid #cbd5e1; background: #fecaca; cursor: pointer; transition: all 0.2s;"></button>
                                            </div>
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

<button id="btnLimpiarFiltrosFlotante" type="button" onclick="limpiarTodosLosFiltrosPendientes()" style="display: none; position: fixed; right: 18px; bottom: 18px; z-index: 9998; background: #111827; color: #ffffff; border: 1px solid rgba(255,255,255,0.15); border-radius: 999px; padding: 10px 14px; font-size: 0.85rem; font-weight: 600; box-shadow: 0 10px 25px rgba(0,0,0,0.25); cursor: pointer;">
    Limpiar filtros
</button>

<!-- Modal Filtro Dinámico -->
<div id="modalFiltro" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalFiltroTitulo">Filtrar</h2>
            <button class="btn-close" type="button" onclick="cerrarModalFiltro()">&times;</button>
        </div>
        <div class="modal-body" id="filtroContenido">
            <!-- Contenido dinámico -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" onclick="aplicarFiltroColumna(event)">Aplicar</button>
            <button type="button" class="btn btn-secondary" onclick="cerrarModalFiltro()">Cancelar</button>
            <button type="button" class="btn btn-secondary" onclick="limpiarFiltroActual()" style="margin-left: auto;">Limpiar Filtro</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
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
        prendas: 'Filtrar Prendas',
        asesor: 'Filtrar Asesora'
    };

    modalTitulo.textContent = tituloMap[columna] || 'Filtrar';

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

function leerValorColumnaFila(fila, col) {
    const map = {
        fecha_creacion: 0,
        numero_recibo: 1,
        cliente: 2,
        prendas: 3,
        asesor: 4,
    };

    const idx = map[col];
    const cell = fila.children[idx];
    if (!cell) return '';

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

document.querySelectorAll('.btn-filter-column').forEach((btn) => {
    btn.addEventListener('click', function() {
        abrirModalFiltroPendientes(btn.getAttribute('data-col'));
    });
});

const overlay = document.getElementById('modalFiltro');
if (overlay) {
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            cerrarModalFiltro();
        }
    });
}

actualizarIndicadoresFiltrosPendientes();

// Funcionalidad de selector de colores con persistencia en BD
function mostrarHoverFila(elemento) {
    const colorGuardado = elemento.getAttribute('data-color-stored');
    if (colorGuardado && colorGuardado.trim()) {
        // Si tiene color guardado, usar un hover más suave
        elemento.style.background = colorGuardado;
        elemento.style.opacity = '0.9';
    } else {
        // Si no tiene color, usar el hover gris
        elemento.style.background = '#f9fafb';
    }
}

function restaurarColorFila(elemento) {
    const colorGuardado = elemento.getAttribute('data-color-stored');
    if (colorGuardado && colorGuardado.trim()) {
        // Restaurar el color guardado
        elemento.style.background = colorGuardado;
        elemento.style.opacity = '1';
    } else {
        // Si no tiene color, volver a blanco
        elemento.style.background = 'white';
    }
}

function inicializarSelectorColores() {
    // Aplicar colores guardados al cargar la página
    document.querySelectorAll('[data-row="processo"]').forEach((fila) => {
        const color = fila.getAttribute('data-color-stored');
        
        if (color && color.trim()) {
            // Aplicar el color al fondo de la fila
            fila.style.backgroundColor = color;
            
            // Encontrar y marcar el botón correspondiente
            const wrapper = fila.querySelector('.color-selector-wrapper');
            if (wrapper) {
                wrapper.querySelectorAll('.color-btn').forEach(btn => {
                    if (btn.getAttribute('data-color') === color) {
                        btn.style.boxShadow = '0 0 0 2px #1e40af';
                    }
                });
            }
        }
    });

    // Configurar manejadores de clic para los botones de color
    document.querySelectorAll('.color-btn').forEach((btn) => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const wrapper = this.closest('.color-selector-wrapper');
            const reciboId = wrapper.getAttribute('data-recibo-id');
            const color = this.getAttribute('data-color');
            const filaBg = wrapper.closest('[data-row="processo"]');

            // Aplicar color a la fila
            filaBg.style.backgroundColor = color;
            filaBg.setAttribute('data-color-stored', color);
            
            // Retroalimentación visual
            wrapper.querySelectorAll('.color-btn').forEach(b => b.style.boxShadow = '');
            this.style.boxShadow = '0 0 0 2px #1e40af';
            
            // Guardar en BD
            guardarColorCostura(reciboId, color);
        });
    });
}

// Ejecutar al cargar
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarSelectorColores);
} else {
    inicializarSelectorColores();
}

// Función para guardar el color en la BD
function guardarColorCostura(reciboId, color) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    fetch('/supervisor-pedidos/guardar-color-costura', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            numero_recibo: reciboId,
            color: color
        })
    })
    .catch(error => console.error('Error al guardar color:', error));
}
</script>
@endpush

@endsection
