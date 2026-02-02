/**
 * ===============================================
 * CARTERA PEDIDOS - JAVASCRIPT [v2]
 * ===============================================
 * Gestiona los pedidos en estado "Pendiente cartera"
 * Funcionalidades: Cargar, aprobar, rechazar pedidos
 * Cambio: Columna NÚMERO removida
 */

// ===== VARIABLES GLOBALES =====
let pedidosData = [];
let pedidoSeleccionado = null;
const API_BASE = '/api/cartera/pedidos';
const ESTADO_FILTRO = 'pendiente_cartera';

// ===== HELPER: Validar elemento =====
function getElement(selector) {
  const el = document.querySelector(selector);
  if (!el) {
    console.warn(` Elemento no encontrado: ${selector}`);
  }
  return el;
}

// ===== HELPER: Validar elemento por ID =====
function getElementById(id) {
  const el = document.getElementById(id);
  if (!el) {
    console.warn(` Elemento con ID no encontrado: #${id}`);
  }
  return el;
}

// ===== INICIALIZACIÓN =====
document.addEventListener('DOMContentLoaded', function() {
  console.log('Cartera Pedidos - Inicializado');
  
  // Validar que los elementos críticos existan
  if (!getElement('#tablaPedidosBody')) {
    console.error(' Tabla no encontrada. La página aún no está lista.');
    return;
  }
  
  // Cargar pedidos al iniciar
  cargarPedidos();
  
  // Configurar listeners de contadores
  configurarContadores();
  
  // Configurar auto-refresh cada 5 minutos
  setInterval(cargarPedidos, 5 * 60 * 1000);
});

// ===== CARGA DE PEDIDOS =====
/**
 * Carga los pedidos desde la API
 * GET /api/pedidos?estado=pendiente_cartera
 */
async function cargarPedidos() {
  const btnRefresh = getElementById('btnRefreshPedidos');
  const tablaPedidosBody = getElementById('tablaPedidosBody');
  const emptyState = getElementById('emptyState');
  
  // Si algún elemento crítico no existe, abortar
  if (!tablaPedidosBody) {
    console.error(' No se puede cargar: tabla no existe');
    return;
  }
  
  try {
    // Mostrar estado de carga
    mostrarEstadoCarga(tablaPedidosBody);
    
    // Deshabilitar botón si existe
    if (btnRefresh) {
      btnRefresh.disabled = true;
    }
    
    // Obtener token CSRF
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const token = csrfMeta ? csrfMeta.content : '';
    
    // Llamar a la API
    const response = await fetch(`${API_BASE}?estado=${ESTADO_FILTRO}`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': token,
        'Content-Type': 'application/json',
      },
      credentials: 'same-origin'
    });
    
    if (!response.ok) {
      throw new Error(`Error HTTP: ${response.status}`);
    }
    
    const data = await response.json();
    
    console.log(' Pedidos cargados:', data);
    
    // Verificar estructura de datos
    if (data.data && Array.isArray(data.data)) {
      pedidosData = data.data;
    } else if (Array.isArray(data)) {
      pedidosData = data;
    } else {
      pedidosData = [];
    }
    
    // Renderizar tabla
    if (pedidosData.length > 0) {
      renderizarTabla(pedidosData);
      tablaPedidosBody.style.display = 'flex';
      if (emptyState) {
        emptyState.style.display = 'none';
      }
      actualizarInfoPaginacion(pedidosData.length);
      mostrarNotificacion('Pedidos cargados correctamente', 'success');
    } else {
      mostrarEstadoVacio(tablaPedidosBody);
      tablaPedidosBody.style.display = 'none';
      if (emptyState) {
        emptyState.style.display = 'flex';
      }
      mostrarNotificacion('No hay pedidos pendientes de cartera', 'info');
    }
    
  } catch (error) {
    console.error(' Error cargando pedidos:', error);
    mostrarNotificacion('Error al cargar los pedidos: ' + error.message, 'error');
    mostrarEstadoError(tablaPedidosBody);
  } finally {
    // Habilitar botón si existe
    if (btnRefresh) {
      btnRefresh.disabled = false;
    }
  }
}

// ===== RENDERIZACIÓN DE TABLA =====
/**
 * Renderiza los pedidos en la tabla
 */
function renderizarTabla(pedidos) {
  const tablaPedidosBody = document.getElementById('tablaPedidosBody');
  
  if (!pedidos || pedidos.length === 0) {
    mostrarEstadoVacio(tablaPedidosBody);
    return;
  }
  
  let html = '';
  
  pedidos.forEach((pedido) => {
    const numero = pedido.numero_pedido || pedido.numero || 'N/A';
    const cliente = pedido.cliente || pedido.nombre_cliente || 'N/A';
    const estado = pedido.estado || 'Pendiente cartera';
    const fecha = formatearFecha(pedido.fecha_de_creacion_de_orden || pedido.fecha_creacion || new Date());
    
    html += `
      <div class="table-row" data-pedido-id="${pedido.id}">
        <!-- Acciones -->
        <div class="table-cell acciones-column" style="flex: 0 0 180px;">
          <button class="btn-action btn-action-approve" 
                  onclick="abrirModalAprobacion(${pedido.id}, '${numero}')"
                  title="Aprobar pedido">
            <span class="material-symbols-rounded">check_circle</span>
            <span>Aprobar</span>
          </button>
          <button class="btn-action btn-action-reject" 
                  onclick="abrirModalRechazo(${pedido.id}, '${numero}')"
                  title="Rechazar pedido">
            <span class="material-symbols-rounded">block</span>
            <span>Rechazar</span>
          </button>
        </div>
        
        <!-- Cliente -->
        <div class="table-cell" style="flex: 1 1 auto;">
          <div class="cell-content" style="justify-content: flex-start;">
            <span>${cliente}</span>
          </div>
        </div>
        
        <!-- Estado -->
        <div class="table-cell" style="flex: 0 0 160px;">
          <div class="cell-content">
            <span class="estado-badge estado-pendiente">
              <span class="material-symbols-rounded" style="font-size: 0.9rem;">schedule</span>
              ${estado}
            </span>
          </div>
        </div>
        
        <!-- Fecha -->
        <div class="table-cell" style="flex: 0 0 140px;">
          <div class="cell-content">
            <span>${fecha}</span>
          </div>
        </div>
      </div>
    `;
  });
  
  tablaPedidosBody.innerHTML = `<div class="modern-table">${html}</div>`;
}

// ===== ESTADOS DE CARGA =====
function mostrarEstadoCarga(container) {
  container.innerHTML = `
    <div class="loading-state">
      <div class="spinner"></div>
      <p>Cargando pedidos...</p>
    </div>
  `;
}

function mostrarEstadoVacio(container) {
  container.innerHTML = '';
}

function mostrarEstadoError(container) {
  container.innerHTML = `
    <div class="loading-state">
      <span class="material-symbols-rounded" style="font-size: 3rem; color: var(--color-danger);">error</span>
      <p>Error al cargar los pedidos. Intenta nuevamente.</p>
    </div>
  `;
}

// ===== MODAL APROBACIÓN =====
/**
 * Abre el modal de aprobación
 */
function abrirModalAprobacion(pedidoId, numeroPedido) {
  console.log(' Abrir modal aprobación - Pedido:', numeroPedido);
  
  const modal = getElement('#modalAprobacion');
  const aprobacionNumero = getElement('#aprobacionPedidoNumero');
  const resumen = getElement('#pedidoResumen');
  
  if (!modal || !aprobacionNumero) {
    console.error(' Modal o elementos no encontrados');
    return;
  }
  
  pedidoSeleccionado = {
    id: pedidoId,
    numero: numeroPedido
  };
  
  // Buscar datos completos del pedido
  const pedido = pedidosData.find(p => p.id === pedidoId);
  if (pedido) {
    pedidoSeleccionado.datos = pedido;
  }
  
  // Actualizar información en el modal
  aprobacionNumero.textContent = numeroPedido;
  
  // Llenar resumen del pedido
  if (resumen && pedido) {
    resumen.innerHTML = `
      <div class="resumen-item">
        <span class="label">Número de Pedido:</span>
        <span class="value">#${pedido.numero_pedido}</span>
      </div>
      <div class="resumen-item">
        <span class="label">Cliente:</span>
        <span class="value">${pedido.cliente}</span>
      </div>
      <div class="resumen-item">
        <span class="label">Fecha:</span>
        <span class="value">${formatearFecha(pedido.fecha_de_creacion_de_orden)}</span>
      </div>
      <div class="resumen-item">
        <span class="label">Estado Actual:</span>
        <span class="value">${pedido.estado}</span>
      </div>
    `;
  }
  
  // Mostrar modal
  modal.classList.add('show');
  modal.style.display = 'flex';
  
  // Prevenir scroll del body
  document.body.style.overflow = 'hidden';
}

/**
 * Cierra el modal de aprobación
 */
function cerrarModalAprobacion() {
  const modal = getElement('#modalAprobacion');
  if (!modal) return;
  
  modal.classList.remove('show');
  modal.style.display = 'none';
  document.body.style.overflow = 'auto';
  pedidoSeleccionado = null;
}

/**
 * Confirma la aprobación del pedido
 */
async function confirmarAprobacion(event) {
  event.preventDefault();
  
  if (!pedidoSeleccionado || !pedidoSeleccionado.id) {
    mostrarNotificacion('Error: Pedido no seleccionado', 'error');
    return;
  }
  
  const btnConfirmar = getElementById('btnConfirmarAprobacion');
  const pedidoId = pedidoSeleccionado.id;
  const numeroPedido = pedidoSeleccionado.numero;
  
  try {
    if (btnConfirmar) {
      btnConfirmar.disabled = true;
      btnConfirmar.classList.add('loading');
    }
    
    const token = document.querySelector('meta[name="csrf-token"]').content;
    
    console.log(' Enviando aprobación para pedido:', pedidoId);
    
    const response = await fetch(`${API_BASE}/${pedidoId}/aprobar`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': token,
        'Content-Type': 'application/json',
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        pedido_id: pedidoId,
        accion: 'aprobar'
      })
    });
    
    const data = await response.json();
    
    if (!response.ok) {
      throw new Error(data.message || `Error HTTP: ${response.status}`);
    }
    
    console.log(' Pedido aprobado correctamente:', data);
    
    // Cerrar modal
    cerrarModalAprobacion();
    
    // Mostrar notificación
    mostrarNotificacion(
      'Aprobado correctamente',
      'success'
    );
    
    // Recargar tabla
    setTimeout(() => cargarPedidos(), 1000);
    
  } catch (error) {
    console.error(' Error aprobando pedido:', error);
    mostrarNotificacion('Error al aprobar: ' + error.message, 'error');
  } finally {
    if (btnConfirmar) {
      btnConfirmar.disabled = false;
      btnConfirmar.classList.remove('loading');
    }
  }
}

// ===== MODAL RECHAZO =====
/**
 * Abre el modal de rechazo
 */
function abrirModalRechazo(pedidoId, numeroPedido) {
  console.log(' Abrir modal rechazo - Pedido:', numeroPedido);
  
  const modal = getElement('#modalRechazo');
  const rechazoPedidoNumero = getElement('#rechazoPedidoNumero');
  const formRechazo = getElement('#formRechazo');
  const contadorRechazo = getElement('#contadorRechazo');
  
  if (!modal || !rechazoPedidoNumero || !formRechazo) {
    console.error(' Modal o elementos no encontrados');
    return;
  }
  
  pedidoSeleccionado = {
    id: pedidoId,
    numero: numeroPedido
  };
  
  // Actualizar información en el modal
  rechazoPedidoNumero.textContent = numeroPedido;
  
  // Limpiar formulario
  formRechazo.reset();
  if (contadorRechazo) {
    contadorRechazo.textContent = '0';
  }
  
  // Mostrar modal
  modal.classList.add('show');
  modal.style.display = 'flex';
  
  // Prevenir scroll del body
  document.body.style.overflow = 'hidden';
  
  // Enfoca el textarea
  setTimeout(() => {
    const textarea = getElement('#motivoRechazo');
    if (textarea) {
      textarea.focus();
    }
  }, 100);
}

/**
 * Cierra el modal de rechazo
 */
function cerrarModalRechazo() {
  const modal = getElement('#modalRechazo');
  if (!modal) return;
  
  modal.classList.remove('show');
  modal.style.display = 'none';
  document.body.style.overflow = 'auto';
  pedidoSeleccionado = null;
}

/**
 * Confirma el rechazo del pedido
 */
async function confirmarRechazo(event) {
  event.preventDefault();
  
  if (!pedidoSeleccionado || !pedidoSeleccionado.id) {
    mostrarNotificacion('Error: Pedido no seleccionado', 'error');
    return;
  }
  
  const motivoElement = getElement('#motivoRechazo');
  if (!motivoElement) {
    console.error(' Textarea de motivo no encontrado');
    return;
  }
  
  const motivo = motivoElement.value.trim();
  
  if (!motivo || motivo.length < 10) {
    mostrarNotificacion('El motivo debe tener al menos 10 caracteres', 'warning');
    return;
  }
  
  const btnConfirmar = getElement('#btnConfirmarRechazo');
  if (btnConfirmar) {
    btnConfirmar.disabled = true;
    btnConfirmar.classList.add('loading');
  }
  
  try {
    const pedidoId = pedidoSeleccionado.id;
    const numeroPedido = pedidoSeleccionado.numero;
    
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const token = csrfMeta ? csrfMeta.content : '';
    
    console.log(' Enviando rechazo para pedido:', pedidoId);
    
    const response = await fetch(`${API_BASE}/${pedidoId}/rechazar`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': token,
        'Content-Type': 'application/json',
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        pedido_id: pedidoId,
        motivo: motivo,
        accion: 'rechazar'
      })
    });
    
    const data = await response.json();
    
    if (!response.ok) {
      throw new Error(data.message || `Error HTTP: ${response.status}`);
    }
    
    console.log(' Pedido rechazado correctamente:', data);
    
    // Cerrar modal
    cerrarModalRechazo();
    
    // Mostrar notificación
    mostrarNotificacion(
      'Rechazado correctamente',
      'success'
    );
    
    // Recargar tabla
    setTimeout(() => cargarPedidos(), 1000);
    
  } catch (error) {
    console.error(' Error rechazando pedido:', error);
    mostrarNotificacion('Error al rechazar: ' + error.message, 'error');
  } finally {
    if (btnConfirmar) {
      btnConfirmar.disabled = false;
      btnConfirmar.classList.remove('loading');
    }
  }
}

// ===== UTILIDADES =====

/**
 * Configura los contadores de caracteres en los textareas
 */
function configurarContadores() {
  const textareas = document.querySelectorAll('textarea[maxlength]');
  
  textareas.forEach(textarea => {
    textarea.addEventListener('input', function() {
      const contadorId = this.id.includes('Rechazo') ? 'contadorRechazo' : 'contadorActual';
      const contador = document.getElementById(contadorId);
      if (contador) {
        contador.textContent = this.value.length;
      }
    });
  });
}

/**
 * Actualiza la información de paginación
 */
function actualizarInfoPaginacion(total) {
  const paginationInfo = document.getElementById('paginationInfo');
  if (paginationInfo) {
    paginationInfo.textContent = `Mostrando ${total} pedido${total !== 1 ? 's' : ''}`;
  }
}

/**
 * Formatea una fecha al formato dd/mm/yyyy
 */
function formatearFecha(fecha) {
  if (!fecha) return 'N/A';
  
  if (typeof fecha === 'string') {
    fecha = new Date(fecha);
  }
  
  if (!(fecha instanceof Date) || isNaN(fecha)) {
    return 'N/A';
  }
  
  const day = String(fecha.getDate()).padStart(2, '0');
  const month = String(fecha.getMonth() + 1).padStart(2, '0');
  const year = fecha.getFullYear();
  
  return `${day}/${month}/${year}`;
}

/**
 * Muestra una notificación toast
 */
function mostrarNotificacion(mensaje, tipo = 'info') {
  const container = document.getElementById('toastContainer');
  
  if (!container) return;
  
  const toast = document.createElement('div');
  toast.className = `toast ${tipo}`;
  
  let icon = 'info';
  switch(tipo) {
    case 'success': icon = 'check_circle'; break;
    case 'error': icon = 'error'; break;
    case 'warning': icon = 'warning'; break;
    case 'info': icon = 'info'; break;
  }
  
  toast.innerHTML = `
    <span class="material-symbols-rounded">${icon}</span>
    <span>${mensaje}</span>
  `;
  
  container.appendChild(toast);
  
  // Auto-remover después de 5 segundos
  setTimeout(() => {
    toast.style.animation = 'slideOutRight 0.3s ease-out forwards';
    setTimeout(() => toast.remove(), 300);
  }, 5000);
}

// ===== CIERRE DE MODALES CON ESC =====
document.addEventListener('keydown', function(event) {
  if (event.key === 'Escape') {
    cerrarModalRechazo();
    cerrarModalAprobacion();
  }
});

// ===== CIERRE DE MODALES AL HACER CLIC EN EL OVERLAY =====
['modalRechazo', 'modalAprobacion'].forEach(modalId => {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.addEventListener('click', function(event) {
      if (event.target === this) {
        if (modalId === 'modalRechazo') {
          cerrarModalRechazo();
        } else {
          cerrarModalAprobacion();
        }
      }
    });
  }
});

console.log(' Script de Cartera Pedidos cargado correctamente');
