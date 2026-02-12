/**
 * Script Específico para la Vista de Pedidos Rechazados
 * Utiliza el sistema de filtros compartido
 */

// ===== VARIABLES GLOBALES ESPECÍFICAS DE LA VISTA =====
let pedidosDataRechazados = [];
let pedidoSeleccionadoRechazados = null;
const API_BASE_RECHAZADOS = '/api/cartera/rechazados';

// Variables de paginación
let currentPage = 1;
let totalPages = 1;
let pedidosPorPagina = 10;

// Exponer datos globalmente para que el sistema compartido pueda acceder
window.pedidosDataRechazados = pedidosDataRechazados;

// ===== FUNCIONES ESPECÍFICAS DE LA VISTA =====

// Función principal para cargar pedidos (será llamada por el sistema compartido)
window.cargarPedidos = async function() {
  const tablaPedidosBody = document.getElementById('tablaPedidosBody');
  
  if (!tablaPedidosBody) {
    console.error('❌ ERROR: No se puede cargar - tabla no existe');
    return;
  }
  
  try {
    mostrarEstadoCarga(tablaPedidosBody);
    
    // Construir URL con filtros
    let url = API_BASE_RECHAZADOS;
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
      pedidosDataRechazados = data.data || [];
      
      // Sincronizar con la variable global
      window.pedidosDataRechazados = pedidosDataRechazados;
      
      // Extraer datos de paginación
      const pagination = data.pagination || {};
      totalPages = pagination.total_pages || 1;
      
      renderizarPedidos(pedidosDataRechazados);
      actualizarPaginacion(pagination.total || 0);
      
      if (pedidosDataRechazados.length === 0) {
        mostrarEstadoVacio();
      } else {
        ocultarEstados();
      }
    } else {
      throw new Error(data.message || 'Error al cargar pedidos');
    }
    
  } catch (error) {
    console.error('🔥 Error cargando pedidos:', error);
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
      <div style="flex: 0 0 150px; padding: 8px 10px;">
        <span>${formatearFechaHora(obtenerFechaRechazo(pedido))}</span>
      </div>
      <div style="flex: 0 0 200px; padding: 8px 10px;">
        <span class="estado-rechazado" style="cursor: pointer; text-decoration: underline;" onclick="verMotivoRechazo('${pedido.motivo_rechazo_cartera || 'N/A'}')" title="Click para ver motivo completo">${pedido.motivo_rechazo_cartera ? (pedido.motivo_rechazo_cartera.length > 20 ? pedido.motivo_rechazo_cartera.substring(0, 20) + '...' : pedido.motivo_rechazo_cartera) : 'N/A'}</span>
      </div>
    </div>
  `).join('');
}

// Función para obtener la fecha correcta según el estado del pedido
function obtenerFechaRechazo(pedido) {
  // Para pedidos rechazados por cartera, usar rechazado_por_cartera_en
  if (pedido.estado === 'RECHAZADO_CARTERA') {
    return pedido.rechazado_por_cartera_en;
  }
  
  // Para otros estados, usar created_at como fallback
  return pedido.created_at || pedido.updated_at;
}

// Ver motivo de rechazo completo
function verMotivoRechazo(motivo) {
  console.log('🔍 INICIANDO verMotivoRechazo:', {motivo});
  
  // Crear modal para mostrar el motivo completo
  const modalWrapper = document.createElement('div');
  modalWrapper.id = 'motivo-rechazo-modal-wrapper';
  modalWrapper.style.cssText = `
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    box-sizing: border-box;
  `;
  
  const modal = document.createElement('div');
  modal.style.cssText = `
    background: white;
    border-radius: 8px;
    max-width: 600px;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15);
    position: relative;
    padding: 0;
  `;
  
  modal.innerHTML = `
    <div style="padding: 20px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #e5e7eb;">
        <div style="display: flex; align-items: center; gap: 10px;">
          <span class="material-symbols-rounded" style="font-size: 1.5rem; color: #dc2626;">info</span>
          <h3 style="margin: 0; color: #1f2937; font-size: 1.1rem; font-weight: 600;">Motivo de Rechazo</h3>
        </div>
        <button type="button" onclick="cerrarModalMotivo()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6b7280; padding: 0; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; border-radius: 50%;">&times;</button>
      </div>
      <div style="padding: 15px; background: #fef2f2; border-radius: 6px; border-left: 4px solid #dc2626;">
        <p style="margin: 0; color: #374151; line-height: 1.6; white-space: pre-wrap; word-wrap: break-word;">${motivo || 'No hay motivo especificado'}</p>
      </div>
    </div>
  `;
  
  modalWrapper.appendChild(modal);
  document.body.appendChild(modalWrapper);
  
  // Cerrar modal al hacer click fuera del contenido
  modalWrapper.addEventListener('click', function(e) {
    if (e.target === modalWrapper) {
      cerrarModalMotivo();
    }
  });
  
  console.log('🔍 Modal de motivo abierto');
}

// Cerrar modal de motivo
function cerrarModalMotivo() {
  const modal = document.getElementById('motivo-rechazo-modal-wrapper');
  if (modal) {
    document.body.removeChild(modal);
    console.log('🔍 Modal de motivo cerrado');
  }
}

// Ver detalles de un pedido
function verPedido(pedidoId) {
  const pedido = pedidosDataRechazados.find(p => p.id === pedidoId);
  
  if (!pedido) {
    console.error('❌ Pedido no encontrado:', pedidoId);
    return;
  }
  
  pedidoSeleccionadoRechazados = pedido;
  
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
        <strong>Fecha Rechazo:</strong> ${formatearFechaHora(obtenerFechaRechazo(pedido))}
      </div>
      <div class="detalle-row">
        <strong>Motivo Rechazo:</strong> <span class="estado-rechazado">${pedido.motivo_rechazo_cartera || 'N/A'}</span>
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
  pedidoSeleccionadoRechazados = null;
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
  console.log('🚀 Cartera Rechazados - Inicializado');
  
  // Validar que los elementos críticos existan
  const tablaPedidosBody = document.getElementById('tablaPedidosBody');
  const emptyState = document.getElementById('emptyState');
  const loadingState = document.getElementById('loadingState');
  const paginationContainer = document.querySelector('.pagination-container');
  
  if (!tablaPedidosBody) {
    console.error('❌ ERROR: No se encontró el contenedor de la tabla');
    return;
  }
  
  console.log('✅ Todos los elementos críticos encontrados. Iniciando carga...');
  
  // Cargar pedidos iniciales
  window.cargarPedidos();
});

console.log('📄 Script de Cartera Rechazados cargado correctamente');
