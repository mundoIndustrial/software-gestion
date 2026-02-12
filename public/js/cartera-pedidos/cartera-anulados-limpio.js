/**
 * Script Específico para la Vista de Pedidos Anulados
 * Utiliza el sistema de filtros compartido
 */

// ===== VARIABLES GLOBALES ESPECÍFICAS DE LA VISTA =====
let pedidosDataAnulados = [];
let pedidoSeleccionadoAnulados = null;
const API_BASE_ANULADOS = '/api/cartera/anulados';

// Variables de paginación
let currentPage = 1;
let totalPages = 1;
let pedidosPorPagina = 10;

// Exponer datos globalmente para que el sistema compartido pueda acceder
window.pedidosDataAnulados = pedidosDataAnulados;

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
    let url = API_BASE_ANULADOS;
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
      pedidosDataAnulados = data.data || [];
      
      // Sincronizar con la variable global
      window.pedidosDataAnulados = pedidosDataAnulados;
      
      // Extraer datos de paginación
      const pagination = data.pagination || {};
      totalPages = pagination.total_pages || 1;
      
      renderizarPedidos(pedidosDataAnulados);
      actualizarPaginacion(pagination.total || 0);
      
      if (pedidosDataAnulados.length === 0) {
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
      <div style="flex: 0 0 120px; padding: 8px 10px;">
        <span>#${pedido.numero || 'N/A'}</span>
      </div>
      <div style="flex: 0 0 150px; padding: 8px 10px;">
        <span>${formatearFecha(obtenerFechaAnulacion(pedido))}</span>
      </div>
      <div style="flex: 1 1 auto; padding: 8px 10px; cursor: pointer;" onclick="abrirModalNovedades(${pedido.id}, '${(pedido.novedades || 'Sin novedades').replace(/'/g, "\\'").replace(/\n/g, '\\n')}', '${pedido.numero || 'N/A'}')" title="Haz click para ver todas las novedades">
        <span class="novedades-celda">${pedido.novedades ? (pedido.novedades.substring(0, 50) + (pedido.novedades.length > 50 ? '...' : '')) : 'Sin novedades'}</span>
      </div>
    </div>
  `).join('');
}

// Ver detalles de un pedido
function verPedido(pedidoId) {
  const pedido = pedidosDataAnulados.find(p => p.id === pedidoId);
  
  if (!pedido) {
    console.error('❌ Pedido no encontrado:', pedidoId);
    return;
  }
  
  pedidoSeleccionadoAnulados = pedido;
  
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
        <strong>Fecha Anulación:</strong> ${formatearFecha(obtenerFechaAnulacion(pedido))}
      </div>
      <div class="detalle-row">
        <strong>Anulado por:</strong> ${obtenerQuienAnulo(pedido)}
      </div>
      <div class="detalle-row">
        <strong>Estado:</strong> <span class="estado-anulado">${pedido.estado || 'N/A'}</span>
      </div>
      <div class="detalle-row">
        <strong>Motivo Anulación:</strong> ${pedido.motivo_anulacion || 'N/A'}
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
  pedidoSeleccionadoAnulados = null;
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

function formatearMoneda(monto) {
  if (!monto) return '0.00';
  
  return Number(monto).toLocaleString('es-CO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
}

// ===== INICIALIZACIÓN =====

document.addEventListener('DOMContentLoaded', function() {
  console.log('🚀 Cartera Anulados - Inicializado');
  
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

// Función para obtener la fecha de anulación correcta
function obtenerFechaAnulacion(pedido) {
  // Debug: Ver qué datos tiene el pedido
  console.log('🔍 DEBUG ANULADOS - Pedido:', {
    id: pedido.id,
    estado: pedido.estado,
    fecha_revision: pedido.fecha_revision,
    usuario_revision: pedido.usuario_revision,
    anulado_por_asesora_en: pedido.anulado_por_asesora_en,
    anulado_por_asesora_id: pedido.anulado_por_asesora_id,
    updated_at: pedido.updated_at,
    created_at: pedido.created_at
  });
  
  // Prioridad para fecha de anulación por asesora:
  // 1. Si hay anulado_por_asesora_en, usar esa (anulación específica por asesora)
  if (pedido.anulado_por_asesora_en) {
    return pedido.anulado_por_asesora_en;
  }
  
  // 2. Si hay fecha_revision, usar esa (revisión general)
  if (pedido.fecha_revision) {
    return pedido.fecha_revision;
  }
  
  // 3. Si no, usar updated_at (última actualización)
  return pedido.updated_at;
}

// Función para obtener quién anuló el pedido
function obtenerQuienAnulo(pedido) {
  // Debug: Ver quién anuló
  console.log('🔍 DEBUG QUIEN ANULO - Pedido:', {
    id: pedido.id,
    anulado_por_asesora_id: pedido.anulado_por_asesora_id,
    usuario_revision: pedido.usuario_revision,
    asesor_id: pedido.asesor_id
  });
  
  // Prioridad para quién anuló:
  // 1. Si hay anulado_por_asesora_id, usar ese ID para obtener nombre
  if (pedido.anulado_por_asesora_id) {
    return `Asesora ID: ${pedido.anulado_por_asesora_id}`;
  }
  
  // 2. Si hay usuario_revision, usar ese
  if (pedido.usuario_revision) {
    return pedido.usuario_revision;
  }
  
  // 3. Si hay asesor_id, usar ese
  if (pedido.asesor_id) {
    return `Asesora ID: ${pedido.asesor_id}`;
  }
  
  // 4. Si no hay información, devolver 'N/A'
  return 'N/A';
}

// Función para abrir modal de novedades
function abrirModalNovedades(pedidoId, novedades, numeroPedido) {
  const modal = document.getElementById('modalVerNovedades');
  const contenido = document.getElementById('novedadesContenido');
  const titulo = document.getElementById('novedadesTitulo');
  
  if (modal && contenido) {
    titulo.textContent = `Novedades del Pedido #${numeroPedido}`;
    // Preservar saltos de línea y espacios
    contenido.innerHTML = '<pre style="white-space: pre-wrap; word-wrap: break-word; font-family: inherit; font-size: inherit;">' + 
      (novedades || 'Sin novedades registradas').replace(/</g, '&lt;').replace(/>/g, '&gt;') + 
      '</pre>';
    modal.classList.add('active');
    // Scroll al modal
    modal.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
}

// Función para cerrar modal de novedades
function cerrarModalNovedades() {
  const modal = document.getElementById('modalVerNovedades');
  if (modal) {
    modal.classList.remove('active');
  }
}

// Configurar event listeners cuando el documento carga
document.addEventListener('DOMContentLoaded', function() {
  const modalVerNovedades = document.getElementById('modalVerNovedades');
  
  if (modalVerNovedades) {
    // Cerrar modal al hacer click en el overlay (área gris)
    modalVerNovedades.addEventListener('click', function(event) {
      if (event.target === this) {
        cerrarModalNovedades();
      }
    });
    
    // Cerrar modal con tecla ESC
    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape' && modalVerNovedades.classList.contains('active')) {
        cerrarModalNovedades();
      }
    });
  }
});

console.log('📄 Script de Cartera Anulados cargado correctamente');
