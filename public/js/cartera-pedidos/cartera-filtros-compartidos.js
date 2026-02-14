/**
 * Sistema de Filtros Compartidos para Cartera de Pedidos
 * Reutilizable entre todas las vistas (rechazados, aprobados, anulados)
 */

// ===== VARIABLES GLOBALES DE FILTROS =====
let filtroFechaActual = '';
let filtroClienteActual = '';
let filtroNumeroActual = '';

// Variables para autocomplete
let sugerenciaSeleccionada = -1;
let sugerenciasCliente = [];
let sugerenciasNumero = [];
let sugerenciasFecha = [];
let busquedaTimeout = null;

// ===== FUNCIONES GLOBALES DE FILTROS =====

// Función global para mostrar notificaciones
window.mostrarNotificacion = function(mensaje, tipo = 'info') {
  console.log('🔔 INICIANDO mostrarNotificacion (global):', {mensaje, tipo});
  
  // Crear el elemento toast
  const toast = document.createElement('div');
  toast.className = `toast toast-${tipo}`;
  toast.textContent = mensaje;
  
  // Agregar al contenedor
  const toastContainer = document.getElementById('toastContainer');
  if (toastContainer) {
    toastContainer.appendChild(toast);
    
    // Auto-remover después de 5 segundos
    setTimeout(() => {
      toast.style.animation = 'slideOutRight 0.3s ease-out forwards';
      setTimeout(() => toast.remove(), 300);
    }, 5000);
  } else {
    console.warn(' Contenedor de notificaciones no encontrado');
  }
};

// Función global para abrir modales de filtro
window.abrirModalFiltro = function(tipo, event) {
  console.log('🔍 INICIANDO abrirModalFiltro (global):', { tipo, event });
  
  if (event) {
    event.stopPropagation();
  }
  
  const modal = document.getElementById(`modalFiltro${tipo.charAt(0).toUpperCase() + tipo.slice(1)}`);
  console.log('🔍 Modal encontrado:', !!modal, 'ID:', `modalFiltro${tipo.charAt(0).toUpperCase() + tipo.slice(1)}`);
  
  if (modal) {
    console.log('🔍 Abriendo modal...');
    modal.classList.add('active');
    modal.style.display = 'block';
    
    // Limpiar input al abrir
    const input = document.getElementById(`filtro${tipo.charAt(0).toUpperCase() + tipo.slice(1)}Input`);
    if (input) {
      input.value = '';
      input.focus();
      console.log('🔍 Input limpiado y enfocado');
    }
    
    // Cerrar al hacer clic fuera
    modal.addEventListener('click', function(event) {
      if (event.target === modal) {
        cerrarModalFiltro(tipo);
      }
    });
    
    console.log(' Modal abierto exitosamente');
  } else {
    console.error(' ERROR: Modal no encontrado');
  }
};

// Función global para limpiar todos los filtros
window.limpiarTodosLosFiltros = function() {
  console.log('🧹 INICIANDO limpiarTodosLosFiltros (global)');
  
  // Limpiar variables de filtro
  filtroFechaActual = '';
  filtroClienteActual = '';
  filtroNumeroActual = '';
  
  // Limpiar inputs
  const inputFecha = document.getElementById('filtroFechaInput');
  const inputCliente = document.getElementById('filtroClienteInput');
  const inputNumero = document.getElementById('filtroNumeroInput');
  const inputEstado = document.getElementById('filtroEstadoInput');
  
  if (inputFecha) inputFecha.value = '';
  if (inputCliente) inputCliente.value = '';
  if (inputNumero) inputNumero.value = '';
  if (inputEstado) inputEstado.value = '';
  
  // Ocultar botón de limpiar filtros
  const btnLimpiar = document.getElementById('btnLimpiarFiltros');
  if (btnLimpiar) {
    btnLimpiar.style.display = 'none';
    console.log('🔍 Botón de limpiar filtros ocultado');
  }
  
  // Recargar pedidos sin filtros
  window.mostrarNotificacion('Todos los filtros han sido eliminados', 'success');
  
  // Llamar a la función de recarga específica de la vista
  if (typeof window.cargarPedidos === 'function') {
    window.cargarPedidos();
  }
  
  console.log(' Filtros limpiados exitosamente');
};

// Función global para verificar si hay filtros activos
window.verificarFiltrosActivos = function() {
  const tieneFiltros = filtroFechaActual || filtroClienteActual || filtroNumeroActual;
  console.log('🔍 Verificando filtros activos (global):', {
    fecha: filtroFechaActual,
    cliente: filtroClienteActual,
    numero: filtroNumeroActual,
    tieneFiltros: tieneFiltros
  });
  
  if (tieneFiltros) {
    const btnLimpiar = document.getElementById('btnLimpiarFiltros');
    if (btnLimpiar) {
      btnLimpiar.style.display = 'flex';
      console.log('🔍 Mostrando botón de limpiar filtros');
    }
  } else {
    const btnLimpiar = document.getElementById('btnLimpiarFiltros');
    if (btnLimpiar) {
      btnLimpiar.style.display = 'none';
      console.log('🔍 Ocultando botón de limpiar filtros');
    }
  }
};

// ===== FUNCIONES DE AUTOCOMPLETE =====

// Buscar sugerencias de cliente
function buscarSugerenciasCliente() {
  console.log('🔍 INICIANDO buscarSugerenciasCliente');
  
  const input = document.getElementById('filtroClienteInput');
  const contenedor = document.getElementById('sugerenciasCliente');

  console.log('🔍 Input encontrado:', !!input);
  console.log('🔍 Contenedor encontrado:', !!contenedor);

  if (!input || !contenedor) {
    console.error(' ERROR: Input o contenedor no encontrado');
    return;
  }

  const textoBusqueda = input.value.toLowerCase().trim();
  console.log('🔍 Texto de búsqueda:', textoBusqueda);

  if (busquedaTimeout) clearTimeout(busquedaTimeout);

  if (textoBusqueda.length === 0) {
    console.log('🔍 Búsqueda vacía, ocultando sugerencias');
    contenedor.classList.remove('active');
    contenedor.style.display = 'none';
    sugerenciasCliente = [];
    sugerenciaSeleccionada = -1;
    return;
  }

  console.log('🔍 Configurando timeout para cargar sugerencias...');
  busquedaTimeout = setTimeout(() => {
    console.log('🔍 Ejecutando cargarSugerenciasDesdeBD...');
    cargarSugerenciasDesdeBD('cliente', textoBusqueda);
  }, 300);
}

// Buscar sugerencias de número
function buscarSugerenciasNumero() {
  const input = document.getElementById('filtroNumeroInput');
  const contenedor = document.getElementById('sugerenciasNumero');

  if (!input || !contenedor) return;
  
  const textoBusqueda = input.value.toLowerCase().trim();

  if (busquedaTimeout) clearTimeout(busquedaTimeout);

  if (textoBusqueda.length === 0) {
    contenedor.classList.remove('active');
    contenedor.style.display = 'none';
    sugerenciasNumero = [];
    sugerenciaSeleccionada = -1;
    return;
  }

  busquedaTimeout = setTimeout(() => {
    cargarSugerenciasDesdeBD('numero', textoBusqueda);
  }, 300);
}

// Buscar sugerencias de fecha
window.buscarSugerenciasFecha = function() {
  console.log(' INICIANDO buscarSugerenciasFecha');
  
  // Obtener el input correcto según la vista actual
  let inputId = 'filtroFechaInput';
  if (window.location.pathname.includes('/cartera/rechazados')) {
    inputId = 'filtroFechaInputRechazados';
  } else if (window.location.pathname.includes('/cartera/anulados')) {
    inputId = 'filtroFechaInputAnulados';
  }
  
  const input = document.getElementById(inputId);
  if (!input) {
    console.warn(' Input de fecha no encontrado:', inputId);
    return;
  }
  
  const textoBusqueda = input.value.trim();
  console.log(' Texto de búsqueda:', textoBusqueda);
  
  // Limpiar timeout anterior si existe
  if (busquedaTimeout) {
    clearTimeout(busquedaTimeout);
  }
  
  // Configurar timeout para evitar demasiadas llamadas
  busquedaTimeout = setTimeout(() => {
    cargarSugerenciasDesdeBD('fecha', textoBusqueda);
  }, 300);
};

// Cargar sugerencias desde la base de datos
async function cargarSugerenciasDesdeBD(tipo, textoBusqueda) {
  console.log(' INICIANDO cargarSugerenciasDesdeBD:', {tipo, textoBusqueda});
  
  try {
    // Determinar el endpoint según el tipo y la vista actual
    let endpoint = '';
    if (tipo === 'cliente') {
      contenedor = document.getElementById('sugerenciasCliente');
      if (window.location.pathname.includes('/cartera/pedidos')) {
        endpoint = '/cartera/pedidos/clientes/sugerencias';
      } else if (window.location.pathname.includes('/cartera/rechazados')) {
        endpoint = '/cartera/rechazados/clientes/sugerencias';
      } else if (window.location.pathname.includes('/cartera/aprobados')) {
        endpoint = '/cartera/aprobados/clientes/sugerencias';
      } else if (window.location.pathname.includes('/cartera/anulados')) {
        endpoint = '/cartera/anulados/clientes/sugerencias';
      }
    } else if (tipo === 'numero') {
      contenedor = document.getElementById('sugerenciasNumero');
      if (window.location.pathname.includes('/cartera/pedidos')) {
        endpoint = '/cartera/pedidos/numeros/sugerencias';
      } else if (window.location.pathname.includes('/cartera/rechazados')) {
        endpoint = '/cartera/rechazados/numeros/sugerencias';
      } else if (window.location.pathname.includes('/cartera/aprobados')) {
        endpoint = '/cartera/aprobados/numeros/sugerencias';
      } else if (window.location.pathname.includes('/cartera/anulados')) {
        endpoint = '/cartera/anulados/numeros/sugerencias';
      }
    } else if (tipo === 'fecha') {
      contenedor = document.getElementById('sugerenciasFecha');
      if (window.location.pathname.includes('/cartera/pedidos')) {
        endpoint = '/cartera/pedidos/fechas/sugerencias';
      } else if (window.location.pathname.includes('/cartera/rechazados')) {
        endpoint = '/cartera/rechazados/fechas/sugerencias';
      } else if (window.location.pathname.includes('/cartera/aprobados')) {
        endpoint = '/cartera/aprobados/fechas/sugerencias';
      } else if (window.location.pathname.includes('/cartera/anulados')) {
        endpoint = '/cartera/anulados/fechas/sugerencias';
      }
    }
    
    console.log('🔍 Endpoint:', endpoint);
    console.log('🔍 Contenedor encontrado:', !!contenedor);
    
    if (!contenedor) {
      console.warn(' Contenedor no encontrado para tipo:', tipo);
      return [];
    }
    
    // Mostrar indicador de carga
    contenedor.innerHTML = '<div class="sugerencia-item">Cargando...</div>';
    
    // Obtener token CSRF
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    console.log('🔍 Token CSRF: encontrado:', !!token);
    
    // Hacer llamada a la API
    const response = await fetch(endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': token
      },
      body: JSON.stringify({
        busqueda: textoBusqueda
      })
    });
    
    console.log('🔍 Respuesta recibida: ' + response.status);
    
    if (!response.ok) {
      throw new Error('Error en la llamada a la API');
    }
    
    const data = await response.json();
    console.log('🔍 Datos recibidos de API:', data);
    
    // Procesar sugerencias
    if (data.success && data.sugerencias) {
      console.log('🔍 Procesando sugerencias:', data.sugerencias);
      
      if (tipo === 'cliente') {
        sugerenciasCliente = data.sugerencias;
        console.log('🔍 Llamando a renderizarSugerenciasCliente...');
        renderizarSugerenciasCliente(textoBusqueda);
      } else if (tipo === 'numero') {
        sugerenciasNumero = data.sugerencias;
        renderizarSugerenciasNumero(textoBusqueda);
      } else if (tipo === 'fecha') {
        // Eliminar duplicados de fechas
        sugerenciasFecha = [...new Set(data.sugerencias)];
        renderizarSugerenciasFecha(textoBusqueda);
      }
    } else {
      console.log('🔍 No hay resultados en la respuesta');
      // Si no hay resultados, ocultar
      contenedor.classList.remove('active');
      contenedor.style.display = 'none';
    }
    
  } catch (error) {
    console.error(' Error cargando sugerencias desde BD:', error);
    console.error(' Stack trace:', error.stack);
    
    // Mostrar mensaje de error
    const contenedor = document.getElementById(`sugerencias${tipo.charAt(0).toUpperCase() + tipo.slice(1)}`);
    if (contenedor) {
      contenedor.innerHTML = '<div class="sugerencia-item">Error al cargar sugerencias</div>';
      contenedor.classList.add('active');
      contenedor.style.display = 'block';
      
      // Ocultar después de 2 segundos
      setTimeout(() => {
        contenedor.classList.remove('active');
        contenedor.style.display = 'none';
      }, 2000);
    }
  }
}

// Renderizar sugerencias de cliente
function renderizarSugerenciasCliente(textoBusqueda) {
  console.log('🔍 INICIANDO renderizarSugerenciasCliente:', {textoBusqueda, sugerenciasCliente});
  
  const contenedor = document.getElementById('sugerenciasCliente');
  console.log('🔍 Contenedor encontrado:', !!contenedor);
  console.log('🔍 SugerenciasCliente array:', sugerenciasCliente);
  console.log('🔍 Longitud de sugerencias:', sugerenciasCliente.length);
  
  if (!contenedor) {
    console.log('🔍 Contenedor no encontrado');
    return;
  }
  
  if (sugerenciasCliente.length === 0) {
    console.log('🔍 No hay sugerencias de cliente, mostrando mensaje...');
    contenedor.innerHTML = '<div class="sugerencia-item sin-resultados">No hay resultados</div>';
    contenedor.classList.add('active');
    contenedor.style.display = 'block';
    return;
  }
  
  console.log('🔍 Construyendo HTML para sugerencias...');
  let html = '';
  
  sugerenciasCliente.forEach((cliente, index) => {
    console.log(`🔍 Procesando sugerencia ${index}: ${cliente}`);
    
    const clienteLower = cliente.toLowerCase();
    const busquedaLower = textoBusqueda.toLowerCase();
    let inicio = clienteLower.indexOf(busquedaLower);
    let fin = inicio + busquedaLower.length;
    
    let textoResaltado = cliente;
    if (inicio !== -1) {
      textoResaltado = cliente.substring(0, inicio) + 
                      '<span class="sugerencia-coincidencia">' + 
                      cliente.substring(inicio, fin) + 
                      '</span>' + 
                      cliente.substring(fin);
    }
    
    html += `
      <div class="sugerencia-item ${index === sugerenciaSeleccionada ? 'seleccionada' : ''}" 
           onclick="seleccionarSugerenciaCliente(${index}, '${cliente.replace(/'/g, "\\'")}')" 
           onmouseover="hoverSugerencia(${index})" 
           onmouseout="unhoverSugerencia()">
        <span class="sugerencia-text">${textoResaltado}</span>
      </div>
    `;
  });
  
  console.log('🔍 HTML construido:', html);
  console.log('🔍 Insertando HTML en contenedor...');
  
  contenedor.innerHTML = html;
  contenedor.classList.add('active');
  contenedor.style.display = 'block';
  
  console.log(' renderizarSugerenciasCliente completado');
}

// Renderizar sugerencias de número
function renderizarSugerenciasNumero(textoBusqueda) {
  const contenedor = document.getElementById('sugerenciasNumero');
  
  if (!contenedor) {
    return;
  }
  
  if (sugerenciasNumero.length === 0) {
    console.log('🔍 No hay sugerencias de número, mostrando mensaje...');
    contenedor.innerHTML = '<div class="sugerencia-item sin-resultados">No hay resultados</div>';
    contenedor.classList.add('active');
    contenedor.style.display = 'block';
    return;
  }
  
  let html = '';
  
  sugerenciasNumero.forEach((numero, index) => {
    const numeroLower = numero.toLowerCase();
    const busquedaLower = textoBusqueda.toLowerCase();
    let inicio = numeroLower.indexOf(busquedaLower);
    let fin = inicio + busquedaLower.length;
    
    let textoResaltado = numero;
    if (inicio !== -1) {
      textoResaltado = numero.substring(0, inicio) + 
                      '<span class="sugerencia-coincidencia">' + 
                      numero.substring(inicio, fin) + 
                      '</span>' + 
                      numero.substring(fin);
    }
    
    html += `
      <div class="sugerencia-item ${index === sugerenciaSeleccionada ? 'seleccionada' : ''}" 
           onclick="seleccionarSugerenciaNumero(${index}, '${numero.replace(/'/g, "\\'")}')" 
           onmouseover="hoverSugerencia(${index})" 
           onmouseout="unhoverSugerencia()">
        <span class="sugerencia-text">${textoResaltado}</span>
      </div>
    `;
  });
  
  contenedor.innerHTML = html;
  contenedor.classList.add('active');
  contenedor.style.display = 'block';
}

// Renderizar sugerencias de fecha
function renderizarSugerenciasFecha(textoBusqueda) {
  const contenedor = document.getElementById('sugerenciasFecha');
  
  if (!contenedor) {
    return;
  }
  
  if (sugerenciasFecha.length === 0) {
    console.log('🔍 No hay sugerencias de fecha, mostrando mensaje...');
    contenedor.innerHTML = '<div class="sugerencia-item sin-resultados">No hay resultados</div>';
    contenedor.classList.add('active');
    contenedor.style.display = 'block';
    return;
  }
  
  let html = '';
  
  sugerenciasFecha.forEach((fecha, index) => {
    const fechaLower = fecha.toLowerCase();
    const busquedaLower = textoBusqueda.toLowerCase();
    let inicio = fechaLower.indexOf(busquedaLower);
    let fin = inicio + busquedaLower.length;
    
    let textoResaltado = fecha;
    if (inicio !== -1) {
      textoResaltado = fecha.substring(0, inicio) + 
                      '<span class="sugerencia-coincidencia">' + 
                      fecha.substring(inicio, fin) + 
                      '</span>' + 
                      fecha.substring(fin);
    }
    
    html += `
      <div class="sugerencia-item ${index === sugerenciaSeleccionada ? 'seleccionada' : ''}" 
           onclick="seleccionarSugerenciaFecha(${index}, '${fecha.replace(/'/g, "\\'")}')" 
           onmouseover="hoverSugerencia(${index})" 
           onmouseout="unhoverSugerencia()">
        <span class="sugerencia-text">${textoResaltado}</span>
      </div>
    `;
  });
  
  contenedor.innerHTML = html;
  contenedor.classList.add('active');
  contenedor.style.display = 'block';
}

// ===== FUNCIONES DE SELECCIÓN =====

// Seleccionar sugerencia de cliente
function seleccionarSugerenciaCliente(index, valor) {
  console.log('🔍 INICIANDO seleccionarSugerenciaCliente:', { index, valor });
  
  const input = document.getElementById('filtroClienteInput');
  console.log('🔍 Input encontrado:', !!input);
  
  if (input) {
    console.log('🔍 Valor actual del input:', input.value);
    console.log('🔍 Asignando nuevo valor:', valor);
    
    input.value = valor;
    sugerenciaSeleccionada = index;
    
    console.log('🔍 Valor asignado exitosamente:', input.value);
    
    // Ocultar sugerencias después de seleccionar
    const contenedor = document.getElementById('sugerenciasCliente');
    console.log('🔍 Contenedor encontrado:', !!contenedor);
    
    if (contenedor) {
      console.log('🔍 Ocultando contenedor de sugerencias');
      contenedor.classList.remove('active');
      contenedor.style.display = 'none';
    } else {
      console.error(' ERROR: Contenedor sugerenciasCliente no encontrado');
    }

    // Limpiar sugerencias
    sugerenciasCliente = [];
    console.log('🔍 Sugerencias limpiadas');
    
    // Aplicar filtro
    console.log('🔍 Aplicando filtro...');
    aplicarFiltroCliente();
    
    console.log(' seleccionarSugerenciaCliente completado exitosamente');
    
  } else {
    console.error(' ERROR: Input filtroClienteInput no encontrado');
  }
}

// Seleccionar sugerencia de número
function seleccionarSugerenciaNumero(index, valor) {
  const input = document.getElementById('filtroNumeroInput');
  if (input) {
    input.value = valor;
    sugerenciaSeleccionada = index;
    
    const contenedor = document.getElementById('sugerenciasNumero');
    if (contenedor) {
      contenedor.classList.remove('active');
      contenedor.style.display = 'none';
    }

    sugerenciasNumero = [];
    aplicarFiltroNumero();
  }
}

// Seleccionar sugerencia de fecha
function seleccionarSugerenciaFecha(index, valor) {
  const input = document.getElementById('filtroFechaInput');
  if (input) {
    input.value = valor;
    sugerenciaSeleccionada = index;
    
    const contenedor = document.getElementById('sugerenciasFecha');
    if (contenedor) {
      contenedor.classList.remove('active');
      contenedor.style.display = 'none';
    }

    sugerenciasFecha = [];
    aplicarFiltroFecha();
  }
}

// ===== FUNCIONES DE APLICAR FILTROS =====

// Aplicar filtro de cliente
function aplicarFiltroCliente() {
  const input = document.getElementById('filtroClienteInput');
  
  if (input) {
    const valor = input.value.trim();
    
    if (valor) {
      filtroClienteActual = valor;
      window.mostrarNotificacion(`Filtro de cliente aplicado: ${valor}`, 'success');
      cerrarModalFiltro('cliente');
      
      // Llamar a la función de recarga específica de la vista
      if (typeof window.cargarPedidos === 'function') {
        window.cargarPedidos();
      }
      
      setTimeout(() => verificarFiltrosActivos(), 100);
    } else {
      filtroClienteActual = '';
      window.mostrarNotificacion('Filtro de cliente eliminado', 'info');
      
      if (typeof window.cargarPedidos === 'function') {
        window.cargarPedidos();
      }
      
      setTimeout(() => verificarFiltrosActivos(), 100);
    }
  }
}

// Aplicar filtro de número
function aplicarFiltroNumero() {
  const input = document.getElementById('filtroNumeroInput');
  
  if (input) {
    const valor = input.value.trim();
    
    if (valor) {
      filtroNumeroActual = valor;
      window.mostrarNotificacion(`Filtro de número aplicado: ${valor}`, 'success');
      cerrarModalFiltro('numero');
      
      if (typeof window.cargarPedidos === 'function') {
        window.cargarPedidos();
      }
      
      setTimeout(() => verificarFiltrosActivos(), 100);
    } else {
      filtroNumeroActual = '';
      window.mostrarNotificacion('Filtro de número eliminado', 'info');
      
      if (typeof window.cargarPedidos === 'function') {
        window.cargarPedidos();
      }
      
      setTimeout(() => verificarFiltrosActivos(), 100);
    }
  }
}

// Aplicar filtro de fecha
function aplicarFiltroFecha() {
  const input = document.getElementById('filtroFechaInput');
  
  if (input) {
    const valor = input.value.trim();
    
    if (valor) {
      filtroFechaActual = valor;
      window.mostrarNotificacion(`Filtro de fecha aplicado: ${valor}`, 'success');
      cerrarModalFiltro('fecha');
      
      if (typeof window.cargarPedidos === 'function') {
        window.cargarPedidos();
      }
      
      setTimeout(() => verificarFiltrosActivos(), 100);
    } else {
      filtroFechaActual = '';
      window.mostrarNotificacion('Filtro de fecha eliminado', 'info');
      
      if (typeof window.cargarPedidos === 'function') {
        window.cargarPedidos();
      }
      
      setTimeout(() => verificarFiltrosActivos(), 100);
    }
  }
}

// Aplicar filtro de estado
function aplicarFiltroEstado() {
  const input = document.getElementById('filtroEstadoInput');
  
  if (input) {
    const valor = input.value.trim();
    
    if (valor) {
      // Para el filtro de estado, podríamos implementar lógica específica si es necesario
      window.mostrarNotificacion(`Filtro de estado aplicado: ${valor}`, 'success');
      cerrarModalFiltro('estado');
      // Aquí podrías agregar lógica para filtrar por estado si es necesario
    } else {
      window.mostrarNotificacion('Filtro de estado eliminado', 'info');
    }
  }
}

// ===== FUNCIONES AUXILIARES =====

// Cerrar modal de filtro
function cerrarModalFiltro(tipo) {
  const modal = document.getElementById(`modalFiltro${tipo.charAt(0).toUpperCase() + tipo.slice(1)}`);
  if (modal) {
    modal.classList.remove('active');
    modal.style.display = 'none';
  }
}

// Hover sobre sugerencia
function hoverSugerencia(index) {
  sugerenciaSeleccionada = index;
  
  // Actualizar visualización
  const items = document.querySelectorAll('.sugerencia-item');
  items.forEach((item, i) => {
    if (i === index) {
      item.classList.add('seleccionada');
    } else {
      item.classList.remove('seleccionada');
    }
  });
}

// Unhover de sugerencia
function unhoverSugerencia() {
  sugerenciaSeleccionada = -1;
  
  const items = document.querySelectorAll('.sugerencia-item');
  items.forEach(item => {
    item.classList.remove('seleccionada');
  });
}

// Función global para ver factura
window.verFactura = function(pedidoId, numeroPedido) {
  console.log('🧾 INICIANDO verFactura (global):', {pedidoId, numeroPedido});
  
  // Verificar si el ModalManager está disponible
  if (typeof window.crearModalPreviewFactura === 'function') {
    console.log('🧾 ModalManager encontrado, obteniendo datos del pedido...');
    
    try {
      // Usar InvoiceDataFetcher para obtener los datos correctos del pedido
      if (typeof window.invoiceDataFetcher !== 'undefined') {
        console.log('🧾 InvoiceDataFetcher encontrado, obteniendo datos del servidor...');
        
        // Obtener datos del pedido desde el servidor
        window.invoiceDataFetcher.obtenerDatosFactura(pedidoId)
          .then(datos => {
            console.log('🧾 Datos del servidor obtenidos:', datos);
            
            // Usar el ModalManager para crear el modal
            window.crearModalPreviewFactura(datos);
            console.log('🧾 Modal de factura abierto con ModalManager');
          })
          .catch(error => {
            console.error(' Error al obtener datos del servidor:', error);
            window.mostrarNotificacion('Error al obtener datos de la factura', 'error');
          });
      } else {
        // Fallback si InvoiceDataFetcher no está disponible
        console.warn(' InvoiceDataFetcher no encontrado, intentando con datos locales...');
        
        // Intentar obtener los datos del pedido desde la vista actual
        let pedidoDatos = null;
        
        if (typeof pedidosDataAprobados !== 'undefined') {
          pedidoDatos = pedidosDataAprobados.find(p => p.id == pedidoId);
        } else if (typeof pedidosDataRechazados !== 'undefined') {
          pedidoDatos = pedidosDataRechazados.find(p => p.id == pedidoId);
        } else if (typeof pedidosDataAnulados !== 'undefined') {
          pedidoDatos = pedidosDataAnulados.find(p => p.id == pedidoId);
        }
        
        console.log('🧾 Datos locales del pedido encontrados:', pedidoDatos);
        
        if (pedidoDatos) {
          // Usar el ModalManager con datos locales
          window.crearModalPreviewFactura(pedidoDatos);
          console.log('🧾 Modal de factura abierto con datos locales y ModalManager');
        } else {
          console.warn(' No se encontraron datos locales del pedido');
          window.mostrarNotificacion('No se encontraron datos del pedido', 'warning');
        }
      }
    } catch (error) {
      console.error(' Error al generar factura:', error);
      window.mostrarNotificacion('Error al generar la factura', 'error');
    }
  } else {
    console.warn(' ModalManager no encontrado');
    window.mostrarNotificacion(`Factura del pedido #${numeroPedido} - Sistema no disponible`, 'warning');
  }
};

// Detectar clics en botones de filtro (fallback)
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('btn-filter-column')) {
    console.log('🔍 CLIC EN BOTÓN btn-filter-column:', e.target);
    console.log('🔍 onclick attribute:', e.target.onclick ? 'tiene onclick' : 'sin onclick');
    
    if (!e.target.onclick) {
      // Extraer el tipo del onclick si existe
      const onclickStr = e.target.getAttribute('onclick');
      if (onclickStr) {
        const tipoMatch = onclickStr.match(/abrirModalFiltro\('([^']+)'/);
        if (tipoMatch) {
          const tipo = tipoMatch[1];
          window.abrirModalFiltro(tipo, e);
        }
      }
    }
  }
}, true);

// ========================================
// FUNCIONES PARA APLICAR FILTROS
// ========================================

// Aplicar filtro de cliente
function aplicarFiltroCliente() {
  console.log('🔍 APLICAR FILTRO CLIENTE');
  const input = document.getElementById('filtroClienteInput');
  const valor = input ? input.value.trim() : '';
  
  console.log(' Valor del filtro:', valor);
  
  if (valor) {
    // Actualizar variable global de filtro
    if (typeof window !== 'undefined') {
      window.filtroCliente = valor;
      console.log(' Filtro cliente aplicado:', valor);
    }
    
    // Recargar pedidos con el nuevo filtro
    if (typeof cargarPedidos === 'function') {
      currentPage = 1; // Volver a primera página
      cargarPedidos();
    }
  } else {
    console.warn(' No hay valor de filtro');
  }
  
  // Cerrar el modal
  cerrarModalFiltro('cliente');
}

// Aplicar filtro de número
function aplicarFiltroNumero() {
  console.log('🔍 APLICAR FILTRO NÚMERO');
  const input = document.getElementById('filtroNumeroInput');
  const valor = input ? input.value.trim() : '';
  
  console.log(' Valor del filtro:', valor);
  
  if (valor) {
    // Guardar como búsqueda o filtro
    if (typeof window !== 'undefined') {
      window.currentSearch = valor;
      console.log(' Filtro número aplicado:', valor);
    }
    
    // Recargar pedidos con el nuevo filtro
    if (typeof cargarPedidos === 'function') {
      currentPage = 1; // Volver a primera página
      cargarPedidos();
    }
  } else {
    console.warn(' No hay valor de filtro');
  }
  
  // Cerrar el modal
  cerrarModalFiltro('numero');
}

// Aplicar filtro de fecha
function aplicarFiltroFecha() {
  console.log('🔍 APLICAR FILTRO FECHA');
  const input = document.getElementById('filtroFechaInput');
  const valor = input ? input.value.trim() : '';
  
  console.log(' Valor del filtro:', valor);
  
  if (valor) {
    // Actualizar variable global de filtro
    if (typeof window !== 'undefined') {
      window.filtroFechaDesde = valor;
      console.log(' Filtro fecha aplicado:', valor);
    }
    
    // Recargar pedidos con el nuevo filtro
    if (typeof cargarPedidos === 'function') {
      currentPage = 1; // Volver a primera página
      cargarPedidos();
    }
  } else {
    console.warn(' No hay valor de filtro');
  }
  
  // Cerrar el modal
  cerrarModalFiltro('fecha');
}

console.log('🎯 Sistema de Filtros Compartidos cargado correctamente');
