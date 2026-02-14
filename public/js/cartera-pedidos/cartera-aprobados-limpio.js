/**
 * Script Específico para la Vista de Pedidos Aprobados
 * Utiliza el sistema de filtros compartido
 */

// ===== VARIABLES GLOBALES ESPECÍFICAS DE LA VISTA =====
let pedidosDataAprobados = [];
let pedidoSeleccionadoAprobados = null;
const API_BASE_APROBADOS = '/api/cartera/aprobados';

// Variables de paginación
let currentPage = 1;
let totalPages = 1;
let pedidosPorPagina = 10;

// Exponer datos globalmente para que el sistema compartido pueda acceder
window.pedidosDataAprobados = pedidosDataAprobados;

// ===== FUNCIONES ESPECÍFICAS DE LA VISTA =====

// Función principal para cargar pedidos (será llamada por el sistema compartido)
window.cargarPedidos = async function() {
  const tablaPedidosBody = document.getElementById('tablaPedidosBody');
  
  if (!tablaPedidosBody) {
    console.error(' ERROR: No se puede cargar - tabla no existe');
    return;
  }
  
  try {
    mostrarEstadoCarga(tablaPedidosBody);
    
    // Construir URL con filtros
    let url = API_BASE_APROBADOS;
    const params = new URLSearchParams();
    
    // Agregar filtros si existen
    if (filtroClienteActual) {
      params.append('cliente', filtroClienteActual);
    }
    if (filtroNumeroActual) {
      params.append('numero', filtroNumeroActual);
    }
    if (filtroFechaActual) {
      params.append('fecha', filtroFechaActual);
    }
    
    // Agregar paginación
    params.append('page', currentPage);
    params.append('limit', pedidosPorPagina);
    
    if (params.toString()) {
      url += '?' + params.toString();
    }
    
    console.log('🔍 Cargando pedidos desde:', url);
    
    const response = await fetch(url);
    
    if (!response.ok) {
      throw new Error(`Error HTTP: ${response.status}`);
    }
    
    const data = await response.json();
    console.log('📊 Datos recibidos:', data);
    
    if (data.success) {
      // Corregir: los datos están en "data", no en "pedidos"
      pedidosDataAprobados = data.data || [];
      
      // Sincronizar con la variable global
      window.pedidosDataAprobados = pedidosDataAprobados;
      
      // Extraer datos de paginación
      const pagination = data.pagination || {};
      totalPages = pagination.total_pages || 1;
      
      renderizarPedidos(pedidosDataAprobados);
      actualizarPaginacion(pagination.total || 0);
      
      if (pedidosDataAprobados.length === 0) {
        mostrarEstadoVacio();
      } else {
        ocultarEstados();
      }
    } else {
      throw new Error(data.message || 'Error al cargar pedidos');
    }
    
  } catch (error) {
    console.error(' Error cargando pedidos:', error);
    mostrarError(error.message);
  } finally {
    ocultarCargando();
  }
};

// Renderizar pedidos en la tabla
function renderizarPedidos(pedidos) {
  const tablaPedidosBody = document.getElementById('tablaPedidosBody');
  
  if (!pedidos || pedidos.length === 0) {
    // Limpiar el cuerpo de la tabla y mostrar el estado vacío
    tablaPedidosBody.innerHTML = '';
    mostrarEstadoVacio();
    return;
  }
  
  // Ocultar estado vacío y mostrar pedidos
  ocultarEstados();
  
  tablaPedidosBody.innerHTML = pedidos.map((pedido, index) => `
    <div class="table-row-cartera">
      <div style="flex: 0 0 180px; display: flex; align-items: center; justify-content: center; padding: 8px;">
        <button class="btn-action-cartera btn-info-cartera" title="Ver factura" onclick="verFactura(${pedido.id}, '${pedido.numero || 'N/A'}')" style="padding: 8px 10px; display: flex; align-items: center; justify-content: center;">
          <span class="material-symbols-rounded" style="font-size: 1.3rem;">receipt</span>
        </button>
      </div>
      <div style="flex: 0 0 250px; padding: 8px 14px 8px 32px;">
        <strong>${pedido.cliente || 'N/A'}</strong>
      </div>
      <div style="flex: 0 0 120px; padding: 8px 10px;">
        <span>#${pedido.numero || 'N/A'}</span>
      </div>
      <div style="flex: 0 0 150px; padding: 8px 10px;">
        <span>${formatearFechaHora(obtenerFechaAprobacion(pedido))}</span>
      </div>
    </div>
  `).join('');
}

// Función para obtener la fecha correcta según el estado del pedido
function obtenerFechaAprobacion(pedido) {
  // Debug: Ver qué datos tiene el pedido
  console.log('🔍 DEBUG APROBADOS - Pedido:', {
    id: pedido.id,
    estado: pedido.estado,
    aprobado_por_cartera_en: pedido.aprobado_por_cartera_en,
    aprobado_por_supervisor_en: pedido.aprobado_por_supervisor_en,
    created_at: pedido.created_at,
    updated_at: pedido.updated_at
  });
  
  // SIEMPRE usar aprobado_por_cartera_en
  return pedido.aprobado_por_cartera_en;
}

// Ver detalles de un pedido
function verPedido(pedidoId) {
  const pedido = pedidosDataAprobados.find(p => p.id === pedidoId);
  
  if (!pedido) {
    console.error(' Pedido no encontrado:', pedidoId);
    return;
  }
  
  pedidoSeleccionadoAprobados = pedido;
  
  const detallesDiv = document.getElementById('pedidoDetalles');
  detallesDiv.innerHTML = `
    <div class="pedido-detalles">
      <div class="detalle-row">
        <strong>Número:</strong> #${pedido.numero || 'N/A'}
      </div>
      <div class="detalle-row">
        <strong>Cliente:</strong> ${pedido.cliente || 'N/A'}
      </div>
      <div class="detalle-row">
        <strong>Fecha Aprobación:</strong> ${formatearFechaHora(obtenerFechaAprobacion(pedido))}
      </div>
      <div class="detalle-row">
        <strong>Estado:</strong> <span class="estado-aprobado">${pedido.estado || 'N/A'}</span>
      </div>
      <div class="detalle-row">
        <strong>Total:</strong> $${formatearMoneda(pedido.total || 0)}
      </div>
    </div>
  `;
  
  // Mostrar modal
  const modal = document.getElementById('modalVerPedido');
  if (modal) {
    modal.style.display = 'block';
  }
}

// Cerrar modal de ver pedido
function cerrarModalVerPedido() {
  const modal = document.getElementById('modalVerPedido');
  if (modal) {
    modal.style.display = 'none';
  }
  pedidoSeleccionadoAprobados = null;
}

// ===== FUNCIONES DE PAGINACIÓN =====

function goToPage(page) {
  if (page < 1 || page > totalPages) return;
  
  currentPage = page;
  window.cargarPedidos();
}

function actualizarPaginacion(total) {
  const paginationInfo = document.getElementById('paginationInfo');
  const btnPrev = document.getElementById('btnPrevPage');
  const btnNext = document.getElementById('btnNextPage');
  
  if (paginationInfo) {
    paginationInfo.textContent = `Mostrando ${Math.min(total, currentPage * pedidosPorPagina)} de ${total} pedidos`;
  }
  
  if (btnPrev) {
    btnPrev.disabled = currentPage <= 1;
  }
  
  if (btnNext) {
    btnNext.disabled = currentPage >= totalPages;
  }
}

// ===== FUNCIONES AUXILIARES =====

function mostrarEstadoCarga(container) {
  if (!container) return;
  
  container.innerHTML = `
    <div class="loading-state-cartera" style="display: flex;">
      <div class="loading-spinner"></div>
      <p>Cargando pedidos...</p>
    </div>
  `;
}

function ocultarCargando() {
  const loadingState = document.getElementById('loadingState');
  if (loadingState) {
    loadingState.style.display = 'none';
  }
}

function mostrarEstadoVacio() {
  const emptyState = document.getElementById('emptyState');
  if (emptyState) {
    emptyState.style.display = 'flex';
  }
}

function ocultarEstados() {
  const emptyState = document.getElementById('emptyState');
  const loadingState = document.getElementById('loadingState');
  
  if (emptyState) emptyState.style.display = 'none';
  if (loadingState) loadingState.style.display = 'none';
}

function mostrarError(mensaje) {
  const tablaPedidosBody = document.getElementById('tablaPedidosBody');
  if (tablaPedidosBody) {
    tablaPedidosBody.innerHTML = `
      <div class="error-state-cartera">
        <span class="material-symbols-rounded">error</span>
        <p>Error: ${mensaje}</p>
      </div>
    `;
  }
}

function formatearFecha(fecha) {
  if (!fecha) return 'N/A';
  
  try {
    const date = new Date(fecha);
    if (isNaN(date.getTime())) return 'N/A';
    
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    
    return `${day}/${month}/${year}`;
  } catch (error) {
    return 'N/A';
  }
}

function formatearFechaHora(fecha) {
  if (!fecha) return 'N/A';
  
  try {
    const date = new Date(fecha);
    if (isNaN(date.getTime())) return 'N/A';
    
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    
    // Determinar AM o PM
    const ampm = hours >= 12 ? 'PM' : 'AM';
    const displayHours = hours > 12 ? hours - 12 : (hours === 0 ? 12 : hours);
    
    return `${day}/${month}/${year} ${String(displayHours).padStart(2, '0')}:${minutes} ${ampm}`;
  } catch (error) {
    return 'N/A';
  }
}

function formatearMoneda(monto) {
  if (!monto) return '0.00';
  
  return Number(monto).toLocaleString('es-CO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
}

// ===== INICIALIZACIÓN =====

document.addEventListener('DOMContentLoaded', function() {
  console.log('🚀 Cartera Aprobados - Inicializado');
  
  // Validar que los elementos críticos existan
  const tablaPedidosBody = document.getElementById('tablaPedidosBody');
  const emptyState = document.getElementById('emptyState');
  const loadingState = document.getElementById('loadingState');
  const paginationContainer = document.querySelector('.pagination-container');
  
  if (!tablaPedidosBody) {
    console.error(' ERROR: No se encontró el contenedor de la tabla');
    return;
  }
  
  console.log(' Todos los elementos críticos encontrados. Iniciando carga...');
  
  // Cargar pedidos iniciales
  window.cargarPedidos();
});

console.log('📄 Script de Cartera Aprobados cargado correctamente');
