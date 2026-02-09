@extends('layouts.visualizador-logo')

@section('title', 'Cotizaciones Bordado/Estampado')

@section('page-title', 'Cotizaciones')

@section('content')
<div style="padding: 1rem 1rem 2rem 1rem; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); min-height: calc(100vh - 60px); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
    <!-- Tabla de Cotizaciones -->
    <div style="display: flex; justify-content: center;">
        <div style="width: 100%; max-width: 900px;">
            <!-- Container -->
            <div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07), 0 1px 3px rgba(0, 0, 0, 0.06); border: 1px solid #e2e8f0;">
                <!-- Header -->
                <div style="
                    background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
                    color: white;
                    padding: 1rem 1.5rem;
                    display: grid;
                    grid-template-columns: 100px 240px 160px 120px 130px;
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
                    <div style="text-align: center; color: #cbd5e1;">Acciones</div>
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

/* Estilos para el modal fullscreen */
.modal.fullscreen {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.9);
    z-index: 9999;
    display: none;
    justify-content: center;
    align-items: center;
    padding: 0;
    margin: 0;
}

.modal.fullscreen .modal-content {
    width: 100vw;
    height: 100vh;
    max-width: none;
    max-height: none;
    border-radius: 0;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    margin: 0;
}

.modal.fullscreen .modal-header {
    background: linear-gradient(135deg, #1d78e1 0%, #0f4c81 100%);
    color: white;
    padding: 1rem 2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}

.modal.fullscreen .modal-header-logo {
    height: 40px;
    width: auto;
    filter: brightness(0) invert(1); /* Cambia el logo a blanco */
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let paginaActual = 1;
    let searchTimeout;
    let cotizacionesOriginales = [];
    
    // Cargar cotizaciones
    cargarCotizaciones();
    
    // Event listeners para la barra de búsqueda
    const searchInput = document.getElementById('search-input');
    const clearSearchBtn = document.getElementById('clear-search');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.trim();
            
            // Mostrar/ocultar botón de limpiar
            if (searchTerm) {
                clearSearchBtn.style.display = 'block';
            } else {
                clearSearchBtn.style.display = 'none';
            }
            
            // Búsqueda en tiempo real con debounce
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                paginaActual = 1;
                cargarCotizaciones(searchTerm);
            }, 300);
        });
    }
    
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function() {
            searchInput.value = '';
            this.style.display = 'none';
            paginaActual = 1;
            cargarCotizaciones('');
            searchInput.focus();
        });
    }
    
    function cargarCotizaciones(searchTerm = '') {
        const params = new URLSearchParams({
            page: paginaActual
        });
        
        if (searchTerm) {
            params.append('search', searchTerm);
        }
        
        fetch(`{{ route("visualizador-logo.cotizaciones") }}?${params}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Guardar datos originales para filtrado local si es necesario
                    if (cotizacionesOriginales.length === 0 && !searchTerm) {
                        cotizacionesOriginales = data.cotizaciones.data;
                    }
                    renderizarCotizaciones(data.cotizaciones, searchTerm);
                }
            })
            .catch(error => {
                mostrarError();
            });
    }
    
    function renderizarCotizaciones(cotizaciones, searchTerm = '') {
        const tbody = document.getElementById('cotizaciones-body');
        
        if (cotizaciones.data.length === 0) {
            tbody.innerHTML = `
                <div style="padding: 3rem 2rem; text-align: center; color: #64748b; background: #f8fafc;">
                    <i class="fas fa-inbox" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 1rem; display: block;"></i>
                    <p style="margin: 0; font-size: 1rem; font-weight: 500;">
                        ${searchTerm ? 'No se encontraron cotizaciones para tu búsqueda' : 'No se encontraron cotizaciones'}
                    </p>
                </div>
            `;
            return;
        }
        
        tbody.innerHTML = cotizaciones.data.map((cot, index) => {
            // Extraer nombre del cliente usando el accessor
            let nombreCliente = cot.cliente_nombre || '-';
            let nombreAsesor = cot.asesor?.name || cot.asesor_nombre || '-';
            
            // Resaltar término de búsqueda si existe
            let numeroCotizacion = cot.numero_cotizacion || 'Borrador';
            if (searchTerm) {
                const regex = new RegExp(`(${searchTerm})`, 'gi');
                numeroCotizacion = numeroCotizacion.replace(regex, '<mark style="background: #fef3c7; color: #92400e; padding: 2px 4px; border-radius: 3px;">$1</mark>');
                nombreCliente = nombreCliente.replace(regex, '<mark style="background: #fef3c7; color: #92400e; padding: 2px 4px; border-radius: 3px;">$1</mark>');
            }
            
            return `
                <div style="
                    display: grid;
                    grid-template-columns: 100px 240px 160px 120px 130px;
                    gap: 1rem;
                    padding: 1rem 1.5rem;
                    align-items: center;
                    transition: all 0.3s ease;
                    background: white;
                    border-bottom: 1px solid #e2e8f0;
                " onmouseover="this.style.background='#f8fafc'; this.style.boxShadow='inset 0 0 0 1px #e2e8f0'" onmouseout="this.style.background='white'; this.style.boxShadow='none'">
                    
                    <div style="font-weight: 700; color: #0ea5e9; font-size: 0.95rem;">${numeroCotizacion}</div>
                    <div style="color: #334155; font-size: 0.95rem; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${nombreCliente}</div>
                    <div style="color: #64748b; font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${nombreAsesor}</div>
                    <div style="color: #64748b; font-size: 0.95rem;">${formatearFecha(cot.fecha_envio)}</div>
                    <div style="display: flex; justify-content: center; gap: 0.5rem;">
                        <button 
                           onclick="openCotizacionModal(${cot.id})"
                           title="Ver detalle"
                           style="
                               background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
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
                               box-shadow: 0 2px 8px rgba(14, 165, 233, 0.2);
                           " onmouseover="this.style.transform='translateY(-3px) scale(1.1)'; this.style.boxShadow='0 6px 16px rgba(14, 165, 233, 0.35)'" onmouseout="this.style.transform='translateY(0) scale(1)'; this.style.boxShadow='0 2px 8px rgba(14, 165, 233, 0.2)'">
                            <i class="fas fa-eye"></i>
                        </button>
                        <a href="/cotizacion/${cot.id}/pdf/logo" 
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
                    const searchTerm = document.getElementById('search-input').value.trim();
                    cargarCotizaciones(searchTerm);
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

// Funciones para el modal de cotización
function openCotizacionModal(cotizacionId) {
    // En Visualizador Logo:
    // - Si es combinada, forzar vista LOGO y ocultar selector.
    //   El openCotizacionModal del contador aplica esto cuando __cotizacionModalHideSelector=true.
    window.__cotizacionModalHideSelector = true;
    window.__cotizacionModalViewMode = 'logo';

    if (window.openCotizacionModalContador) {
        window.openCotizacionModalContador(cotizacionId);
        return;
    }
    alert('No se pudo abrir el modal de cotización');
}

function closeCotizacionModal() {
    if (window.closeCotizacionModalContador) {
        window.closeCotizacionModalContador();
        return;
    }
    document.getElementById('cotizacionModal').style.display = 'none';
}
</script>

<!-- Modal de Cotización del Contador -->
<div id="cotizacionModal" class="modal fullscreen" style="display: none;">
    <div class="modal-content" style="background: white;">
        <div class="modal-header">
            <img src="{{ asset('images/logo2.png') }}" alt="Logo Mundo Industrial" class="modal-header-logo" width="150" height="60">
            <div style="display: flex; gap: 3rem; align-items: center; flex: 1; margin-left: 2rem; color: white; font-size: 0.85rem;">
                <div>
                    <p style="margin: 0; opacity: 0.8;">Cotización #</p>
                    <p id="modalHeaderNumber" style="margin: 0; font-size: 1.1rem; font-weight: 600;">-</p>
                </div>
                <div>
                    <p style="margin: 0; opacity: 0.8;">Fecha</p>
                    <p id="modalHeaderDate" style="margin: 0; font-size: 1.1rem; font-weight: 600;">-</p>
                </div>
                <div>
                    <p style="margin: 0; opacity: 0.8;">Cliente</p>
                    <p id="modalHeaderClient" style="margin: 0; font-size: 1.1rem; font-weight: 600;">-</p>
                </div>
                <div>
                    <p style="margin: 0; opacity: 0.8;">Asesora</p>
                    <p id="modalHeaderAdvisor" style="margin: 0; font-size: 1.1rem; font-weight: 600;">-</p>
                </div>
            </div>
            <button onclick="closeCotizacionModal()" style="background: rgba(255,255,255,0.2); border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0.5rem 1rem; border-radius: 4px; transition: all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                ✕
            </button>
        </div>
        <div id="modalBody" style="padding: 2rem; overflow-y: auto; background: white;"></div>
    </div>
</div>

@endsection

