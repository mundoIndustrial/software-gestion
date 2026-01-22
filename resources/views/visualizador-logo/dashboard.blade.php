@extends('layouts.visualizador-logo')

@section('title', 'Cotizaciones Bordado/Estampado')

@section('content')
<div style="padding: 2rem 1rem; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); min-height: calc(100vh - 60px); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
    <!-- Header Title -->
    <div style="display: flex; justify-content: center; margin-bottom: 2rem;">
        <div style="text-align: center; width: 100%; max-width: 900px;">
            <h1 style="margin: 0; font-size: 2rem; font-weight: 700; color: #0f172a; letter-spacing: -0.02em;">
                <i class="fas fa-file-pdf" style="color: #e11d48; margin-right: 0.5rem;"></i>Cotizaciones
            </h1>
            <p style="margin: 0.5rem 0 0 0; color: #64748b; font-size: 0.95rem;">Gestiona y descarga tus cotizaciones de bordado y estampado</p>
        </div>
    </div>

    <!-- Filtros Card -->
    <div style="display: flex; justify-content: center; margin-bottom: 2rem;">
        <div style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07), 0 1px 3px rgba(0, 0, 0, 0.06); width: 100%; max-width: 900px; border: 1px solid #e2e8f0;">
            <div style="display: grid; grid-template-columns: 1fr 150px 130px 130px 110px; gap: 1rem; align-items: end;">
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-size: 0.85rem; font-weight: 600; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-search" style="margin-right: 0.5rem; color: #0ea5e9;"></i>Buscar
                    </label>
                    <input type="text" id="filtro-search" placeholder="Cotización, cliente..." style="width: 100%; padding: 0.7rem 1rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; transition: all 0.3s; background: #f8fafc;" onmouseover="this.style.borderColor='#cbd5e1'" onmouseout="this.style.borderColor='#e2e8f0'" onfocus="this.style.borderColor='#0ea5e9'" onblur="this.style.borderColor='#e2e8f0'">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-size: 0.85rem; font-weight: 600; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-filter" style="margin-right: 0.5rem; color: #0ea5e9;"></i>Estado
                    </label>
                    <select id="filtro-estado" style="width: 100%; padding: 0.7rem 1rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; transition: all 0.3s; background: white; color: #334155;" onmouseover="this.style.borderColor='#cbd5e1'" onmouseout="this.style.borderColor='#e2e8f0'" onfocus="this.style.borderColor='#0ea5e9'" onblur="this.style.borderColor='#e2e8f0'">
                        <option value="">Todos</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="aprobado">Aprobado</option>
                        <option value="rechazado">Rechazado</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-size: 0.85rem; font-weight: 600; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-calendar" style="margin-right: 0.5rem; color: #0ea5e9;"></i>Desde
                    </label>
                    <input type="date" id="filtro-fecha-desde" style="width: 100%; padding: 0.7rem 1rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; transition: all 0.3s; background: #f8fafc;" onmouseover="this.style.borderColor='#cbd5e1'" onmouseout="this.style.borderColor='#e2e8f0'" onfocus="this.style.borderColor='#0ea5e9'" onblur="this.style.borderColor='#e2e8f0'">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-size: 0.85rem; font-weight: 600; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-calendar" style="margin-right: 0.5rem; color: #0ea5e9;"></i>Hasta
                    </label>
                    <input type="date" id="filtro-fecha-hasta" style="width: 100%; padding: 0.7rem 1rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; transition: all 0.3s; background: #f8fafc;" onmouseover="this.style.borderColor='#cbd5e1'" onmouseout="this.style.borderColor='#e2e8f0'" onfocus="this.style.borderColor='#0ea5e9'" onblur="this.style.borderColor='#e2e8f0'">
                </div>
                <button id="btn-filtrar" style="background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); color: white; border: none; padding: 0.7rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.3s; box-shadow: 0 4px 6px rgba(14, 165, 233, 0.3); height: auto; text-transform: uppercase; letter-spacing: 0.5px;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 12px rgba(14, 165, 233, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px rgba(14, 165, 233, 0.3)'">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Tabla de Cotizaciones -->
    <div style="display: flex; justify-content: center;">
        <div style="width: 100%; max-width: 900px;">
            <!-- Container -->
            <div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07), 0 1px 3px rgba(0, 0, 0, 0.06); border: 1px solid #e2e8f0;">
                <!-- Header -->
                <div style="
                    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
                    color: white;
                    padding: 1rem 1.5rem;
                    display: grid;
                    grid-template-columns: 100px 240px 160px 120px 80px;
                    gap: 1rem;
                    font-weight: 700;
                    font-size: 0.9rem;
                    text-transform: uppercase;
                    letter-spacing: 0.05em;
                ">
                    <div style="color: #cbd5e1;">Número</div>
                    <div style="color: #cbd5e1;">Cliente</div>
                    <div style="color: #cbd5e1;">Asesor</div>
                    <div style="color: #cbd5e1;">Fecha</div>
                    <div style="text-align: center; color: #cbd5e1;">Acción</div>
                </div>

                <!-- Filas -->
                <div id="cotizaciones-body">
                    <div style="padding: 3rem 2rem; text-align: center; color: #64748b; background: #f8fafc;">
                        <div style="font-size: 2.5rem; color: #cbd5e1; margin-bottom: 1rem;">
                            <i class="fas fa-spinner fa-spin"></i>
                        </div>
                        <p style="margin: 0; font-size: 1rem; font-weight: 500;">Cargando cotizaciones...</p>
                    </div>
                </div>
            </div>
            
            <!-- Paginación -->
            <div id="paginacion-container" style="margin-top: 1.5rem; text-align: center;"></div>
        </div>
    </div>
</div>

<style>
.badge-estado {
    padding: 0.4rem 0.8rem;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 6px;
    display: inline-block;
}

.pagination {
    list-style: none;
    display: flex;
    gap: 0.5rem;
    padding: 0;
    margin: 0;
    justify-content: center;
    flex-wrap: wrap;
}

.page-item.active .page-link {
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
    border-color: #0ea5e9;
    color: white;
}

.page-link {
    color: #0ea5e9;
    text-decoration: none;
    padding: 0.5rem 0.8rem;
    border: 2px solid #e2e8f0;
    border-radius: 6px;
    transition: all 0.3s;
    font-weight: 500;
}

.page-link:hover:not(.disabled) {
    background: #f0f9ff;
    border-color: #0ea5e9;
    transform: translateY(-2px);
}

.page-item.disabled .page-link {
    color: #cbd5e1;
    cursor: not-allowed;
    opacity: 0.5;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let paginaActual = 1;
    
    // Cargar cotizaciones
    cargarCotizaciones();
    
    // Event listeners para filtros
    document.getElementById('btn-filtrar').addEventListener('click', function() {
        paginaActual = 1;
        cargarCotizaciones();
    });
    
    // Enter en búsqueda
    document.getElementById('filtro-search').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            paginaActual = 1;
            cargarCotizaciones();
        }
    });
    
    // Filtrar al cambiar estado
    document.getElementById('filtro-estado').addEventListener('change', function() {
        paginaActual = 1;
        cargarCotizaciones();
    });
    
    // Filtrar al cambiar fecha desde
    document.getElementById('filtro-fecha-desde').addEventListener('change', function() {
        paginaActual = 1;
        cargarCotizaciones();
    });
    
    // Filtrar al cambiar fecha hasta
    document.getElementById('filtro-fecha-hasta').addEventListener('change', function() {
        paginaActual = 1;
        cargarCotizaciones();
    });
    
    function cargarCotizaciones() {
        const params = new URLSearchParams({
            page: paginaActual,
            search: document.getElementById('filtro-search').value,
            estado: document.getElementById('filtro-estado').value,
            fecha_desde: document.getElementById('filtro-fecha-desde').value,
            fecha_hasta: document.getElementById('filtro-fecha-hasta').value,
        });
        
        fetch(`{{ route("visualizador-logo.cotizaciones") }}?${params}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderizarCotizaciones(data.cotizaciones);
                }
            })
            .catch(error => {
                console.error('Error al cargar cotizaciones:', error);
                mostrarError();
            });
    }
    
    function renderizarCotizaciones(cotizaciones) {
        const tbody = document.getElementById('cotizaciones-body');
        
        // Debug: Mostrar estructura de datos
        console.log(' ===== INICIO renderizarCotizaciones =====');
        console.log(' Objeto cotizaciones completo:', cotizaciones);
        console.log(' Array de datos:', cotizaciones.data);
        console.log(' Total de registros:', cotizaciones.data.length);
        
        if (cotizaciones.data.length > 0) {
            console.log(' Primer registro completo:', cotizaciones.data[0]);
            console.log('👤 Campo cliente:', cotizaciones.data[0].cliente);
            console.log('🆔 Campo cliente_id:', cotizaciones.data[0].cliente_id);
            console.log('👨 Objeto asesor:', cotizaciones.data[0].asesor);
            console.log(' Todas las propiedades del primer registro:', Object.keys(cotizaciones.data[0]));
        }
        
        if (cotizaciones.data.length === 0) {
            tbody.innerHTML = `
                <div style="padding: 3rem 2rem; text-align: center; color: #64748b; background: #f8fafc;">
                    <i class="fas fa-inbox" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 1rem; display: block;"></i>
                    <p style="margin: 0; font-size: 1rem; font-weight: 500;">No se encontraron cotizaciones</p>
                </div>
            `;
            return;
        }
        
        tbody.innerHTML = cotizaciones.data.map((cot, index) => {
            // Extraer nombre del cliente - el campo 'cliente' es texto plano en la tabla
            let nombreCliente = cot.cliente || '-';
            
            console.log(` Procesando cotización #${index}:`, {
                id: cot.id,
                numero: cot.numero_cotizacion,
                cliente_campo: cot.cliente,
                cliente_id: cot.cliente_id,
                nombreCliente_asignado: nombreCliente
            });
            
            let nombreAsesor = cot.asesor?.name || cot.asesor_nombre || '-';
            
            return `
                <div style="
                    display: grid;
                    grid-template-columns: 100px 240px 160px 120px 80px;
                    gap: 1rem;
                    padding: 1rem 1.5rem;
                    align-items: center;
                    transition: all 0.3s ease;
                    background: white;
                    border-bottom: 1px solid #e2e8f0;
                " onmouseover="this.style.background='#f8fafc'; this.style.boxShadow='inset 0 0 0 1px #e2e8f0'" onmouseout="this.style.background='white'; this.style.boxShadow='none'">
                    
                    <div style="font-weight: 700; color: #0ea5e9; font-size: 0.95rem;">${cot.numero_cotizacion || 'Borrador'}</div>
                    <div style="color: #334155; font-size: 0.95rem; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${nombreCliente}</div>
                    <div style="color: #64748b; font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${nombreAsesor}</div>
                    <div style="color: #64748b; font-size: 0.95rem;">${formatearFecha(cot.fecha_envio)}</div>
                    <div style="display: flex; justify-content: center;">
                        <a href="/cotizacion/${cot.id}/pdf?tipo=logo" 
                           target="_blank"
                           title="Descargar PDF Logo"
                           style="
                               background: linear-gradient(135deg, #e11d48 0%, #be185d 100%);
                               color: white;
                               border: none;
                               padding: 0.6rem;
                               border-radius: 8px;
                               cursor: pointer;
                               font-size: 1rem;
                               transition: all 0.3s ease;
                               display: flex;
                               align-items: center;
                               justify-content: center;
                               width: 40px;
                               height: 40px;
                               box-shadow: 0 2px 8px rgba(225, 29, 72, 0.2);
                               text-decoration: none;
                           " onmouseover="this.style.transform='translateY(-3px) scale(1.1)'; this.style.boxShadow='0 6px 16px rgba(225, 29, 72, 0.35)'" onmouseout="this.style.transform='translateY(0) scale(1)'; this.style.boxShadow='0 2px 8px rgba(225, 29, 72, 0.2)'">
                            <i class="fas fa-file-pdf"></i>
                        </a>
                    </div>
                </div>
            `;
        }).join('');
        
        renderizarPaginacion(cotizaciones);
    }
    
    function formatearFecha(fecha) {
        if (!fecha) return '-';
        const date = new Date(fecha);
        return date.toLocaleDateString('es-ES', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        });
    }
    
    function renderizarPaginacion(cotizaciones) {
        const container = document.getElementById('paginacion-container');
        
        if (cotizaciones.last_page <= 1) {
            container.innerHTML = '';
            return;
        }
        
        let html = '<nav><ul class="pagination">';
        
        // Anterior
        html += `<li class="page-item ${cotizaciones.current_page === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${cotizaciones.current_page - 1}"><i class="fas fa-chevron-left" style="margin-right: 0.3rem;"></i>Anterior</a>
        </li>`;
        
        // Páginas
        for (let i = 1; i <= cotizaciones.last_page; i++) {
            if (i === 1 || i === cotizaciones.last_page || (i >= cotizaciones.current_page - 2 && i <= cotizaciones.current_page + 2)) {
                html += `<li class="page-item ${i === cotizaciones.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>`;
            } else if (i === cotizaciones.current_page - 3 || i === cotizaciones.current_page + 3) {
                html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }
        
        // Siguiente
        html += `<li class="page-item ${cotizaciones.current_page === cotizaciones.last_page ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${cotizaciones.current_page + 1}">Siguiente <i class="fas fa-chevron-right" style="margin-left: 0.3rem;"></i></a>
        </li>`;
        
        html += '</ul></nav>';
        container.innerHTML = html;
        
        // Event listeners para paginación
        container.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = parseInt(this.dataset.page);
                if (page && page !== paginaActual) {
                    paginaActual = page;
                    cargarCotizaciones();
                }
            });
        });
    }
    
    function mostrarError() {
        const tbody = document.getElementById('cotizaciones-body');
        tbody.innerHTML = `
            <div style="padding: 3rem 2rem; text-align: center; color: #64748b; background: #f8fafc;">
                <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #ef4444; margin-bottom: 1rem; display: block;"></i>
                <p style="margin: 0; font-size: 1rem; font-weight: 500;">Error al cargar las cotizaciones</p>
            </div>
        `;
    }
});
</script>
@endsection
