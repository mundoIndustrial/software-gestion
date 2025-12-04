@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/cotizaciones/cotizaciones.css') }}">

<div class="cotizaciones-container">
    <div class="cotizaciones-wrapper">
        <div class="cotizaciones-scroll">
            <table class="cotizaciones-table">
                <thead>
                    <tr>
                        @if(auth()->user() && auth()->user()->role && auth()->user()->role->name === 'asesor')
                            <th>Número</th>
                        @endif
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Asesora</th>
                        <th>Estado</th>
                        <th style="text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cotizaciones as $cotizacion)
                        <tr>
                            @if(auth()->user() && auth()->user()->role && auth()->user()->role->name === 'asesor')
                                <td><strong>COT-{{ str_pad($cotizacion->id, 5, '0', STR_PAD_LEFT) }}</strong></td>
                            @endif
                            <td>{{ $cotizacion->created_at ? $cotizacion->created_at->format('d/m/Y h:i A') : 'N/A' }}</td>
                            <td>{{ $cotizacion->cliente ?? 'N/A' }}</td>
                            <td>{{ $cotizacion->asesora ?? ($cotizacion->usuario->name ?? 'N/A') }}</td>
                            <td>
                                <span class="badge-entregar">📦 Entregar</span>
                            </td>
                            <td>
                                <div class="cotizaciones-actions">
                                    <button class="btn-view" title="Ver Detalles" onclick="openCotizacionModal({{ $cotizacion->id }})">
                                        <span class="material-symbols-rounded">visibility</span>
                                    </button>
                                    <button class="btn-pdf" title="Descargar PDF">
                                        <span class="material-symbols-rounded">picture_as_pdf</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            @if(auth()->user() && auth()->user()->role && auth()->user()->role->name === 'asesor')
                                <td colspan="6" class="no-cotizaciones">
                            @else
                                <td colspan="5" class="no-cotizaciones">
                            @endif
                                <span class="material-symbols-rounded">inbox</span>
                                <p>No hay cotizaciones para entregar</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal de Cotización -->
@include('cotizaciones.partials.cotizacion-modal')

<script>
function openCotizacionModal(cotizacionId) {
    fetch(`/cotizaciones/${cotizacionId}/detalle`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const cotizacion = data.data;
                
                // Llenar información principal
                document.getElementById('modalClienteName').textContent = cotizacion.cliente || 'N/A';
                document.getElementById('modalCotizacionNumber').textContent = `COT-${String(cotizacion.id).padStart(5, '0')}`;
                document.getElementById('modalAsesora').textContent = cotizacion.asesora || (cotizacion.usuario?.name || 'N/A');
                document.getElementById('modalFecha').textContent = new Date(cotizacion.created_at).toLocaleDateString('es-ES', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                document.getElementById('modalEstado').textContent = '📦 Entregar';

                // Llenar productos
                const productosContainer = document.getElementById('modalProductos');
                productosContainer.innerHTML = '';
                
                if (cotizacion.prendas_cotizaciones && cotizacion.prendas_cotizaciones.length > 0) {
                    cotizacion.prendas_cotizaciones.forEach(prenda => {
                        const imagen = prenda.fotos && prenda.fotos.length > 0 ? prenda.fotos[0] : null;
                        const tallas = prenda.tallas ? (Array.isArray(prenda.tallas) ? prenda.tallas.join(', ') : prenda.tallas) : 'N/A';
                        
                        const productCard = document.createElement('div');
                        productCard.className = 'product-card';
                        productCard.innerHTML = `
                            <div class="product-image">
                                ${imagen ? `<img src="${imagen}" alt="${prenda.nombre_producto}">` : '<span>Sin imagen</span>'}
                            </div>
                            <div class="product-info">
                                <div class="product-name">${prenda.nombre_producto || 'N/A'}</div>
                                <div class="product-desc">${prenda.descripcion || 'Sin descripción'}</div>
                                <div class="product-tallas">TALLAS: (${tallas})</div>
                            </div>
                        `;
                        productosContainer.appendChild(productCard);
                    });
                } else {
                    productosContainer.innerHTML = '<p style="color: #9ca3af;">No hay productos</p>';
                }

                // Llenar especificaciones
                const especificacionesBody = document.getElementById('modalEspecificaciones');
                especificacionesBody.innerHTML = '';
                
                if (cotizacion.especificaciones) {
                    const specs = cotizacion.especificaciones;
                    const specLabels = {
                        'disponibilidad': 'DISPONIBILIDAD',
                        'forma_pago': 'FORMA DE PAGO',
                        'regimen': 'RÉGIMEN',
                        'se_ha_vendido': 'SE HA VENDIDO',
                        'ultima_venta': 'ÚLTIMA VENTA',
                        'flete': 'FLETE DE ENVÍO'
                    };

                    Object.entries(specLabels).forEach(([key, label]) => {
                        const valores = specs[key] || [];
                        const valoresText = Array.isArray(valores) ? valores.join(', ') : (valores || '-');
                        
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td><strong>${label}</strong></td>
                            <td>${valoresText}</td>
                        `;
                        especificacionesBody.appendChild(row);
                    });
                }

                // Llenar técnicas
                const tecnicasSection = document.getElementById('tecnicasSection');
                const tecnicasContainer = document.getElementById('modalTecnicas');
                
                if (cotizacion.logo_cotizacion && cotizacion.logo_cotizacion.tecnicas && cotizacion.logo_cotizacion.tecnicas.length > 0) {
                    tecnicasSection.style.display = 'block';
                    tecnicasContainer.innerHTML = '';
                    cotizacion.logo_cotizacion.tecnicas.forEach(tecnica => {
                        const badge = document.createElement('span');
                        badge.className = 'tecnica-badge';
                        badge.textContent = tecnica;
                        tecnicasContainer.appendChild(badge);
                    });
                } else {
                    tecnicasSection.style.display = 'none';
                }

                // Llenar observaciones técnicas
                const obsTecnicasSection = document.getElementById('obsTecnicasSection');
                const obsTecnicasBox = document.getElementById('modalObsTecnicas');
                
                if (cotizacion.logo_cotizacion && cotizacion.logo_cotizacion.observaciones_tecnicas) {
                    obsTecnicasSection.style.display = 'block';
                    obsTecnicasBox.textContent = cotizacion.logo_cotizacion.observaciones_tecnicas;
                } else {
                    obsTecnicasSection.style.display = 'none';
                }

                // Llenar observaciones generales
                const obsGeneralesSection = document.getElementById('obsGeneralesSection');
                const obsGeneralesList = document.getElementById('modalObsGenerales');
                
                if (cotizacion.logo_cotizacion && cotizacion.logo_cotizacion.observaciones_generales && cotizacion.logo_cotizacion.observaciones_generales.length > 0) {
                    obsGeneralesSection.style.display = 'block';
                    obsGeneralesList.innerHTML = '';
                    cotizacion.logo_cotizacion.observaciones_generales.forEach(obs => {
                        const li = document.createElement('li');
                        li.textContent = obs;
                        obsGeneralesList.appendChild(li);
                    });
                } else {
                    obsGeneralesSection.style.display = 'none';
                }

                // Mostrar modal
                document.getElementById('cotizacionModal').style.display = 'flex';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar la cotización');
        });
}

function closeCotizacionModal() {
    document.getElementById('cotizacionModal').style.display = 'none';
}

// Cerrar modal al hacer clic fuera
document.addEventListener('click', function(event) {
    const modal = document.getElementById('cotizacionModal');
    if (event.target === modal) {
        closeCotizacionModal();
    }
});

// Cerrar modal con ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeCotizacionModal();
    }
});
</script>
@endsection
