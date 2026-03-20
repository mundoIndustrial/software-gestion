'use strict';

  // Precargar festivos del año actual y siguiente
  async function precargarFestivos() {
    const anioActual = new Date().getFullYear();
    const anioSiguiente = anioActual + 1;
    
    try {
      // Precargar en paralelo
      await Promise.all([
        obtenerFestivos(anioActual),
        obtenerFestivos(anioSiguiente)
      ]);
      console.log('[precargarFestivos] Festivos precargados correctamente');
    } catch (error) {
      console.warn('[precargarFestivos] Error precargando festivos:', error);
    }
  }

// Inicializar listeners del modal
function initTrackingModalListeners() {
    // Cerrar modal al hacer clic en el overlay
    const overlay = document.getElementById('trackingModalOverlay');
    if (overlay) {
      overlay.addEventListener('click', closeTrackingModal);
    }

    // Cerrar modal con botón X (si existe)
    const closeBtn = document.querySelector('.tracking-modal-close');
    if (closeBtn) {
      closeBtn.addEventListener('click', closeTrackingModal);
    }

    // Configurar listeners del modal agregar proceso
    setupAddProcesoModalListeners();

    // Configurar listeners del modal de confirmación
    setupConfirmDeleteModalListeners();

    // Configurar botón volver
    setupBackButton();

    // Precargar festivos para mejorar rendimiento
    precargarFestivos();
  }

  // Timer para actualizar contadores dinámicos
  let contadorTimer = null;

  // Actualizar contadores de días dinámicos (procesos sin fecha fin)
  function actualizarContadoresDinamicos() {
    try {
      // Buscar todas las tarjetas de áreas que tengan contadores dinámicos
      const areaCards = document.querySelectorAll('.tracking-area-card');
      
      areaCards.forEach(card => {
        const areaElement = card.querySelector('.tracking-area-name');
        if (!areaElement) return;
        
        const area = areaElement.textContent.trim();
        const totalDiasElement = card.querySelector('.tracking-total-dias');
        const duracionAreaElement = card.querySelector('.tracking-duracion-area');
        
        if (!totalDiasElement || !duracionAreaElement) return;
        
        // Obtener datos del proceso (desde data attributes o recalcular)
        const processData = window.currentPrendaData?.seguimientos_por_area?.[area];
        if (!processData) return;
        
        // Recalcular días dinámicamente
        const ini = toDateObject(processData.fecha_inicio);
        if (!ini) return;
        
        // Si no hay fecha fin/completado, contar hasta hoy
        if (!processData.fecha_fin && !processData.fecha_completado) {
          const diasHabiles = calcularDiasHabilesSync(ini, new Date());
          const diasText = diasHabiles === 0 ? '0 días' : `${diasHabiles} día${diasHabiles !== 1 ? 's' : ''}`;
          
          // Actualizar visualización
          if (totalDiasElement.textContent.includes('día')) {
            totalDiasElement.textContent = diasText;
          }
          if (duracionAreaElement.textContent.includes('día')) {
            duracionAreaElement.textContent = diasText;
          }
        }
      });
      
      console.log('[actualizarContadoresDinamicos] Contadores actualizados');
    } catch (error) {
      console.error('[actualizarContadoresDinamicos] Error:', error);
    }
  }

  // Iniciar timer para actualización automática de contadores
  function iniciarTimerContadores() {
    // Detener timer existente
    if (contadorTimer) {
      clearInterval(contadorTimer);
    }
    
    // Actualizar inmediatamente
    actualizarContadoresDinamicos();
    
    // Configurar timer para actualizar cada día a medianoche
    const ahora = new Date();
    const manana = new Date(ahora);
    manana.setDate(manana.getDate() + 1);
    manana.setHours(0, 0, 0, 0);
    
    const msHastaManana = manana.getTime() - ahora.getTime();
    
    // Primer actualización a medianoche
    setTimeout(() => {
      actualizarContadoresDinamicos();
      
      // Luego actualizar cada 24 horas
      contadorTimer = setInterval(actualizarContadoresDinamicos, 24 * 60 * 60 * 1000);
    }, msHastaManana);
    
    console.log('[iniciarTimerContadores] Timer configurado para actualizar diariamente');
  }

  // Detener timer de contadores
  function detenerTimerContadores() {
    if (contadorTimer) {
      clearInterval(contadorTimer);
      contadorTimer = null;
      console.log('[detenerTimerContadores] Timer detenido');
    }
  }

  // Función para abrir el modal de agregar proceso
  const closeAddProcesoBtn = document.getElementById('closeAddProcesoModal');
  if (closeAddProcesoBtn) {
    closeAddProcesoBtn.addEventListener('click', closeAddProcesoModal);
  }

  const btnCancelAddProceso = document.getElementById('btnCancelAddProceso');
  if (btnCancelAddProceso) {
    btnCancelAddProceso.addEventListener('click', closeAddProcesoModal);
  }

  // Cerrar modal
  function closeTrackingModal() {
    const modal = document.getElementById('orderTrackingModal');
    if (modal) {
      modal.classList.remove('show');
      modal.style.display = 'none';
      // Resetear vistas (sin llamar a showPrendasView para evitar recursividad)
      console.log('[closeTrackingModal] Modal de seguimiento cerrado');
    }
  }

  // Función para abrir el modal de agregar proceso
  function openAddProcesoModal() {
    console.log('[openAddProcesoModal] Abriendo modal de agregar proceso');
    const modal = document.getElementById('addProcesoModal');
    console.log('[openAddProcesoModal] Modal encontrado:', !!modal);
    
    if (modal) {
      // Si no estamos editando, abrir limpio para agregar una nueva área
      if (!window.editingProcessId) {
        if (typeof resetFormButton === 'function') {
          resetFormButton();
        }
        if (typeof limpiarFormularioProceso === 'function') {
          limpiarFormularioProceso();
        }
      }

      modal.classList.add('show');
      
      // FORZAR ESTILO DIRECTAMENTE CON JAVASCRIPT
      modal.style.setProperty('display', 'flex', 'important');
      modal.style.setProperty('visibility', 'visible', 'important');
      modal.style.setProperty('opacity', '1', 'important');
      modal.style.setProperty('z-index', '10000000', 'important');
      
      console.log('[openAddProcesoModal] Modal configurado y mostrado');
      
      // Asegurar que los botones de cerrar funcionen
      setupAddProcesoModalListeners();
    } else {
      console.error('[openAddProcesoModal] No se encontró el modal addProcesoModal');
    }
  }

  // Configurar listeners del modal agregar proceso
  function setupAddProcesoModalListeners() {
    const closeBtn = document.getElementById('closeAddProcesoModal');
    if (closeBtn) {
      closeBtn.onclick = closeAddProcesoModal;
    }

    const cancelBtn = document.getElementById('btnCancelAddProceso');
    if (cancelBtn) {
      cancelBtn.onclick = closeAddProcesoModal;
    }

    const overlay = document.getElementById('addProcesoOverlay');
    if (overlay) {
      overlay.onclick = closeAddProcesoModal;
    }
  }

  // Función para cerrar el modal de agregar proceso
  function closeAddProcesoModal() {
    const modal = document.getElementById('addProcesoModal');
    if (modal) {
      modal.classList.remove('show');
      modal.style.display = 'none';
    }
  }

  // Configurar botón volver
  function setupBackButton() {
    const backBtn = document.getElementById('backToPrendasBtn');
    if (backBtn) {
      backBtn.onclick = showPrendasView;
      console.log('[setupBackButton] Botón volver configurado');
    } else {
      console.warn('[setupBackButton] Botón volver no encontrado');
    }
  }

  // Abrir selector de prendas (overlay)
  window.openOrderTracking = async function(orderId, mostrarSelector = true) {
    try {
      console.log('[openOrderTracking] Abriendo selector de prendas para orden:', orderId, 'mostrarSelector:', mostrarSelector);
      
      // Cargar datos básicos del pedido
      await loadOrderBasicData(orderId);
      
      // Cargar prendas con seguimiento
      await loadPrendasWithTracking(orderId);
      
      // Mostrar overlay de prendas solo si se solicita
      if (mostrarSelector) {
        showPrendasSelector();
      }
      
      console.log('[openOrderTracking] Datos cargados correctamente. currentOrderData:', window.currentOrderData);
      
    } catch (error) {
      console.error('[openOrderTracking] Error:', error);
      showError('Error al cargar datos de seguimiento');
    }
  };

  // Compatibilidad con implementación vieja (tracking-modal-script.blade.php)
  // Algunas vistas (ej. supervisor-pedidos) llaman mostrarTrackingModal(pedidoData).
  window.mostrarTrackingModal = function(pedidoData) {
    try {
      const orderId = pedidoData?.id || pedidoData?.pedido_id || pedidoData?.pedido?.id || null;
      if (!orderId) {
        console.error('[mostrarTrackingModal] No se encontró orderId en pedidoData:', pedidoData);
        return;
      }

      // El flujo nuevo carga datos desde /registros/{id}/... y abre el selector de prendas.
      window.openOrderTracking(orderId, true);
    } catch (e) {
      console.error('[mostrarTrackingModal] Error:', e);
    }
  };

  // Cargar datos básicos del pedido
  async function loadOrderBasicData(orderId) {
    try {
      const response = await fetch(`/registros/${orderId}/recibos-datos`);
      if (!response.ok) throw new Error('Error al cargar datos del pedido');
      
      const result = await response.json();
      console.log('[loadOrderBasicData] Respuesta del endpoint:', result);
      
      // Extraer datos desde la estructura del endpoint
      const data = result.data || result;
      console.log('[loadOrderBasicData] Datos extraídos:', data);
      
      window.currentOrderData = data;
      
      // Actualizar información del pedido en el modal
      updateOrderInfo(data);
      
    } catch (error) {
      console.error('[loadOrderBasicData] Error:', error);
      throw error;
    }
  }

  // Actualizar información del pedido en el modal y selector
  function updateOrderInfo(orderData) {
    console.log('[updateOrderInfo] Datos recibidos:', orderData);
    console.log('[updateOrderInfo] numero_pedido:', orderData.numero_pedido);
    console.log('[updateOrderInfo] cliente:', orderData.cliente);
    console.log('[updateOrderInfo] estado:', orderData.estado);
    
    // Actualizar modal principal
    console.log('[updateOrderInfo] Campos de fecha disponibles:', {
      fecha_creacion: orderData.fecha_creacion,
      fecha_de_creacion_de_orden: orderData.fecha_de_creacion_de_orden,
      created_at: orderData.created_at,
      fecha_estimada_entrega: orderData.fecha_estimada_entrega
    });
    
    document.getElementById('trackingOrderNumber').textContent = orderData.numero_pedido || '-';
    document.getElementById('trackingOrderClient').textContent = orderData.cliente || '-';
    document.getElementById('trackingOrderStatus').textContent = (orderData.estado || '-').replace(/_/g, ' ').toUpperCase();
    document.getElementById('trackingEstimatedDate').textContent = formatDate(orderData.fecha_estimada_entrega) || '-';
    document.getElementById('trackingTotalDays').textContent = orderData.total_dias || '0';

    // Actualizar selector de prendas
    const selectorOrderNumber = document.getElementById('selectorOrderNumber');
    const selectorOrderClient = document.getElementById('selectorOrderClient');
    const selectorOrderStatus = document.getElementById('selectorOrderStatus');
    const selectorOrderStartDate = document.getElementById('selectorOrderStartDate');
    const selectorOrderEstimatedDate = document.getElementById('selectorOrderEstimatedDate');
    
    console.log('[updateOrderInfo] Elementos encontrados:', {
      selectorOrderNumber: !!selectorOrderNumber,
      selectorOrderClient: !!selectorOrderClient,
      selectorOrderStatus: !!selectorOrderStatus,
      selectorOrderStartDate: !!selectorOrderStartDate,
      selectorOrderEstimatedDate: !!selectorOrderEstimatedDate
    });
    
    // Actualizar información del modal principal
    const trackingOrderNumber = document.getElementById('trackingOrderNumber');
    const trackingOrderClient = document.getElementById('trackingOrderClient');
    const trackingOrderStatus = document.getElementById('trackingOrderStatus');
    const trackingOrderRecibo = document.getElementById('trackingOrderRecibo');
    
    console.log('[updateOrderInfo] Elementos encontrados:', {
      selectorOrderNumber: !!selectorOrderNumber,
      selectorOrderClient: !!selectorOrderClient,
      selectorOrderStatus: !!selectorOrderStatus,
      selectorOrderEstimatedDate: !!selectorOrderEstimatedDate,
      trackingOrderNumber: !!trackingOrderNumber,
      trackingOrderClient: !!trackingOrderClient,
      trackingOrderStatus: !!trackingOrderStatus,
      trackingOrderRecibo: !!trackingOrderRecibo
    });
    
    // Actualizar selector
    if (selectorOrderNumber) {
      selectorOrderNumber.textContent = orderData.numero_pedido || '-';
    }
    if (selectorOrderClient) {
      selectorOrderClient.textContent = orderData.cliente || '-';
    }
    if (selectorOrderStatus) {
      selectorOrderStatus.textContent = (orderData.estado || '-').replace(/_/g, ' ').toUpperCase();
    }
    if (selectorOrderStartDate) {
      // Actualizar fecha de inicio
      let fechaInicio = orderData.fecha_creacion || orderData.fecha_de_creacion_de_orden || orderData.created_at;
      
      if (fechaInicio) {
        // Formatear fecha
        let fechaFormateada = '';
        if (typeof fechaInicio === 'string') {
          try {
            const date = new Date(fechaInicio);
            if (!isNaN(date.getTime())) {
              fechaFormateada = date.toLocaleDateString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
              });
            } else {
              fechaFormateada = fechaInicio;
            }
          } catch (e) {
            fechaFormateada = fechaInicio;
          }
        } else if (fechaInicio instanceof Date) {
          fechaFormateada = fechaInicio.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
          });
        } else if (fechaInicio && fechaInicio.date) {
          fechaFormateada = new Date(fechaInicio.date).toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
          });
        }
        
        selectorOrderStartDate.textContent = fechaFormateada || '-';
      } else {
        selectorOrderStartDate.textContent = '-';
      }
    }
    if (selectorOrderEstimatedDate) {
      // Actualizar fecha estimada de entrega
      let fechaEstimada = orderData.fecha_estimada_de_entrega;
      
      if (fechaEstimada) {
        // Formatear fecha datetime
        let fechaFormateada = '';
        if (typeof fechaEstimada === 'string') {
          // Si es string, intentar parsear y formatear
          try {
            const date = new Date(fechaEstimada);
            if (!isNaN(date.getTime())) {
              fechaFormateada = date.toLocaleDateString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
              });
            } else {
              fechaFormateada = fechaEstimada;
            }
          } catch (e) {
            fechaFormateada = fechaEstimada;
          }
        } else if (fechaEstimada instanceof Date) {
          // Si es un objeto Date, formatearlo
          fechaFormateada = fechaEstimada.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
          });
        } else if (fechaEstimada && fechaEstimada.date) {
          // Si es un objeto Carbon/Laravel
          fechaFormateada = new Date(fechaEstimada.date).toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
          });
        }
        
        selectorOrderEstimatedDate.textContent = fechaFormateada || '-';
      } else {
        selectorOrderEstimatedDate.textContent = '-';
      }
    }
    
    // Actualizar modal principal
    if (trackingOrderNumber) {
      trackingOrderNumber.textContent = orderData.numero_pedido || '-';
    }
    if (trackingOrderClient) {
      trackingOrderClient.textContent = orderData.cliente || '-';
    }
    if (trackingOrderStatus) {
      trackingOrderStatus.textContent = (orderData.estado || '-').replace(/_/g, ' ').toUpperCase();
    }
    if (trackingOrderRecibo) {
      // Buscar específicamente recibos de COSTURA (excluir COSTURA-BODEGA)
      let ultimoReciboCostura = '-';
      
      console.log('[updateOrderInfo] Buscando recibo COSTURA en orderData:', {
        prendas_count: orderData.prendas ? orderData.prendas.length : 0,
        prendas: orderData.prendas
      });
      
      // Si tenemos datos de prendas con consecutivos, buscar COSTURA
      if (orderData.prendas && orderData.prendas.length > 0) {
        let reciboCosturaEncontrado = null;
        let totalRecibosEncontrados = 0;
        
        // Buscar entre todas las prendas el recibo de COSTURA
        for (const prenda of orderData.prendas) {
          console.log('[updateOrderInfo] Analizando prenda:', {
            prenda_id: prenda.id,
            prenda_nombre: prenda.nombre_prenda,
            consecutivos: prenda.consecutivos
          });
          
          // Los consecutivos vienen como objeto, no como array
          if (prenda.consecutivos && typeof prenda.consecutivos === 'object') {
            // Convertir el objeto de consecutivos a un array para procesar
            const recibosArray = [];
            for (const [tipo, datos] of Object.entries(prenda.consecutivos)) {
              if (datos !== null && datos !== undefined) {
                recibosArray.push({
                  tipo_recibo: tipo,
                  consecutivo_actual: datos.consecutivo_actual || datos,
                  activo: datos.activo !== undefined ? datos.activo : 1,
                  created_at: datos.created_at || new Date().toISOString()
                });
              }
            }
            
            console.log('[updateOrderInfo] Recibos convertidos a array:', recibosArray);
            
            for (const recibo of recibosArray) {
              console.log('[updateOrderInfo] Analizando recibo:', recibo);
              totalRecibosEncontrados++;
              
              // Solo buscar recibos de tipo COSTURA (excluir COSTURA-BODEGA)
              if (recibo.activo === 1 && recibo.tipo_recibo === 'COSTURA') {
                reciboCosturaEncontrado = recibo;
                console.log('[updateOrderInfo] Recibo COSTURA encontrado:', reciboCosturaEncontrado);
                break; // Encontramos el primero, no necesitamos seguir buscando
              }
            }
          }
          
          // Si ya encontramos un recibo COSTURA, salir del bucle de prendas
          if (reciboCosturaEncontrado) {
            break;
          }
        }
        
        console.log('[updateOrderInfo] Resumen de búsqueda:', {
          total_recibos_encontrados: totalRecibosEncontrados,
          recibo_costura_encontrado: reciboCosturaEncontrado
        });
        
        if (reciboCosturaEncontrado) {
          ultimoReciboCostura = `COSTURA #${reciboCosturaEncontrado.consecutivo_actual}`;
        }
      }
      
      console.log('[updateOrderInfo] Resultado final para trackingOrderRecibo:', ultimoReciboCostura);
      trackingOrderRecibo.textContent = ultimoReciboCostura;
    }
    if (selectorOrderEstimatedDate) {
      selectorOrderEstimatedDate.style.color = '#1f2937';
      selectorOrderEstimatedDate.style.fontWeight = '600';
    } else {
      selectorOrderEstimatedDate.textContent = 'No definida';
      selectorOrderEstimatedDate.style.color = '#9ca3af';
      selectorOrderEstimatedDate.style.fontWeight = '400';
    }
  }

  // Cargar prendas con seguimiento
  async function loadPrendasWithTracking(orderId) {
    try {
      console.log('[loadPrendasWithTracking] Cargando prendas para orden:', orderId);
      
      const response = await fetch(`/registros/${orderId}/seguimiento-prenda`);
      if (!response.ok) throw new Error('Error al cargar seguimiento de prendas');
      
      const data = await response.json();
      console.log('[loadPrendasWithTracking] Datos recibidos:', data);
      
      // Renderizar prendas
      renderPrendas(data.prendas || []);
      
    } catch (error) {
      console.error('[loadPrendasWithTracking] Error:', error);
      throw error;
    }
  }

  // Renderizar tabla única de prendas en el overlay
  function renderPrendas(prendas) {
    const container = document.getElementById('trackingPrendasSelectorContainer');
    if (!container) return;
    
    console.log('[renderPrendas] Renderizando tabla de prendas:', prendas.length);
    
    container.innerHTML = '';
    
    if (prendas.length === 0) {
      container.innerHTML = `
        <div class="tracking-no-prendas">
          <p>No hay prendas registradas para este pedido</p>
        </div>
      `;
      return;
    }
    
    // Crear tabla única con todas las prendas
    const tableHtml = createPrendasTable(prendas);
    container.innerHTML = tableHtml;
    
    // Actualizar fecha estimada de entrega del pedido
    updateEstimatedDeliveryDate();
  }

  // Actualizar fecha estimada de entrega del pedido
  function updateEstimatedDeliveryDate() {
    const fechaEstimadaElement = document.getElementById('selectorOrderEstimatedDate');
    if (!fechaEstimadaElement || !window.currentOrderData) return;
    
    // Obtener fecha estimada del pedido (campo correcto)
    let fechaEstimada = window.currentOrderData.fecha_estimada_de_entrega;
    
    if (fechaEstimada) {
      // Formatear fecha datetime
      let fechaFormateada = '';
      if (typeof fechaEstimada === 'string') {
        // Si es string, intentar parsear y formatear
        try {
          const date = new Date(fechaEstimada);
          if (!isNaN(date.getTime())) {
            fechaFormateada = date.toLocaleDateString('es-ES', {
              day: '2-digit',
              month: '2-digit',
              year: 'numeric'
            });
          } else {
            fechaFormateada = fechaEstimada;
          }
        } catch (e) {
          fechaFormateada = fechaEstimada;
        }
      } else if (fechaEstimada instanceof Date) {
        // Si es un objeto Date, formatearlo
        fechaFormateada = fechaEstimada.toLocaleDateString('es-ES', {
          day: '2-digit',
          month: '2-digit',
          year: 'numeric'
        });
      } else if (fechaEstimada && fechaEstimada.date) {
        // Si es un objeto Carbon/Laravel
        fechaFormateada = new Date(fechaEstimada.date).toLocaleDateString('es-ES', {
          day: '2-digit',
          month: '2-digit',
          year: 'numeric'
        });
      }
      
      fechaEstimadaElement.textContent = fechaFormateada;
      fechaEstimadaElement.style.color = '#1f2937';
      fechaEstimadaElement.style.fontWeight = '600';
    } else {
      fechaEstimadaElement.textContent = 'No definida';
      fechaEstimadaElement.style.color = '#9ca3af';
      fechaEstimadaElement.style.fontWeight = '400';
    }
  }

  // Crear tabla HTML con todas las prendas
  function createPrendasTable(prendas) {
    let tableHtml = `
      <div class="prendas-table-container">
        <table class="prendas-report-table">
          <thead>
            <tr>
              <th>Prenda</th>
              <th>Cantidad</th>
              <th>Procesos</th>
              <th>Área</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
    `;
    
    // Almacenar las prendas globalmente para acceso desde onclick
    window.prendasData = prendas;
    
    prendas.forEach((prenda, index) => {
      // Logging para depuración
      console.log(`[createPrendasTable] Prenda ${index}:`, {
        nombre: prenda.nombre_prenda,
        tipos_recibo_procesos: prenda.tipos_recibo_procesos,
        procesos_generales: prenda.procesos
      });
      
      // Extraer información de la prenda
      const nombrePrenda = prenda.nombre_prenda || `Prenda ${index + 1}`;
      const cantidad = prenda.cantidad || 0;
      const totalProcesos = prenda.total_procesos || 0;
      
      // Extraer tipos de recibo que son procesos (ESTAMPADO, BORDADO, REFLECTIVO, DTF, SUBLIMADO)
      let procesosInfo = '-';
      if (prenda.tipos_recibo_procesos && prenda.tipos_recibo_procesos.length > 0) {
        procesosInfo = prenda.tipos_recibo_procesos.map(p => {
          const nombre = p.nombre || 'Proceso';
          const estado = (p.estado || 'PENDIENTE').replace(/_/g, ' '); // Reemplazar guiones bajos por espacios
          return `${nombre} (${estado})`;
        }).join(', ');
      } else if (prenda.procesos && prenda.procesos.length > 0) {
        // Fallback a procesos generales si no hay tipos de recibo
        procesosInfo = prenda.procesos.map(p => {
          const tipoProceso = p.tipo_proceso;
          const nombre = tipoProceso?.nombre || 'Proceso';
          const estado = (p.estado || 'PENDIENTE').replace(/_/g, ' '); // Reemplazar guiones bajos por espacios
          return `${nombre} (${estado})`;
        }).join(', ');
      }
      
      // Extraer área basada en el proceso más reciente
      let area = '-';
      if (prenda.ultimo_proceso_area) {
        // Si ya viene el área del último proceso, usarla
        area = prenda.ultimo_proceso_area;
      } else if (prenda.area && prenda.area.trim() !== '') {
        // Si tiene área asignada directamente, usarla
        area = prenda.area;
      }
      
      // Usar el estado del pedido en lugar del estado calculado de procesos
      const estadoPedido = window.currentOrderData?.estado || 'Sin estado';
      const estadoFormateado = estadoPedido.replace(/_/g, ' ').toUpperCase();
      
      // Determinar si el botón debe estar desactivado (para prendas de bodega)
      const esDeBodega = prenda.de_bodega || false;
      const botonDisabled = esDeBodega ? 'disabled' : '';
      const botonTitle = esDeBodega ? 'Prenda de bodega - no disponible para seguimiento' : 'Ver seguimiento detallado';
      const botonClass = esDeBodega ? 'btn-ver-seguimiento disabled' : 'btn-ver-seguimiento';
      
      // Badge de origen de prenda
      let badgeHtml = '';
      if (prenda.de_bodega) {
        badgeHtml = '<span class="bodega-badge">SE SACA DE BODEGA</span>';
      } else {
        badgeHtml = '<span class="confeciona-badge">SE CONFECCIONA</span>';
      }
      
      tableHtml += `
        <tr class="prendas-table-row" data-prenda-index="${index}">
          <td class="prendas-table-cell prendas-name-cell">
            <div class="prendas-name">${nombrePrenda}</div>
            ${badgeHtml}
          </td>
          <td class="prendas-table-cell">${cantidad}</td>
          <td class="prendas-table-cell procesos-cell">
            <div class="procesos-info">${procesosInfo}</div>
          </td>
          <td class="prendas-table-cell">${area}</td>
          <td class="prendas-table-cell">
            <span class="estado-badge estado-${estadoPedido.toLowerCase().replace(/_/g, '-')}">${estadoFormateado}</span>
          </td>
          <td class="prendas-table-cell acciones-cell">
            <button class="${botonClass}" ${botonDisabled} onclick="showPrendaTrackingFromTable(${index})" title="${botonTitle}">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 11l3 3L22 4"></path>
                <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"></path>
              </svg>
              Ver
            </button>
          </td>
        </tr>
      `;
    });
    
    tableHtml += `
          </tbody>
        </table>
      </div>
    `;
    
    return tableHtml;
  }

  // Mostrar selector de prendas (overlay)
  function showPrendasSelector() {
    const overlay = document.getElementById('trackingPrendasSelectorOverlay');
    console.log('[showPrendasSelector] Overlay encontrado:', !!overlay);
    if (overlay) {
      overlay.classList.add('show');
      console.log('[showPrendasSelector] Overlay mostrado');
    } else {
      console.error('[showPrendasSelector] No se encontró el overlay');
    }
  }

  // Cerrar selector de prendas
  window.cerrarSelectorPrendas = function() {
    const overlay = document.getElementById('trackingPrendasSelectorOverlay');
    if (overlay) {
      overlay.classList.remove('show');
      overlay.style.display = 'none';
    }
  };

  // Crear tabla simple de prenda (estilo TNS)
  function createPrendaCard(prenda, index) {
    const card = document.createElement('div');
    card.className = 'tracking-prenda-table';
    
    // Añadir event listener con debug
    card.addEventListener('click', function(e) {
      console.log('[createPrendaCard] Click en tabla de prenda:', prenda);
      e.preventDefault();
      e.stopPropagation();
      showPrendaTracking(prenda);
    });
    
    const seguimientosHtml = renderSeguimientosBadges(prenda.seguimientos || {});
    const areasHtml = renderAreasBadges(prenda.seguimientos_por_area || {});
    
    // Construir HTML de procesos en formato de fila
    let procesosHtml = '';
    if (prenda.procesos && prenda.procesos.length > 0) {
      procesosHtml = '<tr><td colspan="2"><div class="tracking-procesos-lista">';
      prenda.procesos.forEach(proceso => {
        // Acceder correctamente a los datos del tipo_proceso
        const tipoProceso = proceso.tipo_proceso;
        const procesoNombre = tipoProceso?.nombre || 'Proceso';
        const procesoEstado = proceso.estado || 'PENDIENTE';
        
        console.log('[createPrendaCard] Proceso:', proceso);
        console.log('[createPrendaCard] TipoProceso:', tipoProceso);
        
        procesosHtml += `
          <div class="tracking-proceso-item">
            <span class="proceso-nombre">${procesoNombre}</span>
            <span class="proceso-estado">${procesoEstado}</span>
          </div>
        `;
      });
      procesosHtml += '</div></td></tr>';
    }

    // Badge de bodega si aplica
    let bodegaBadge = '';
    if (prenda.de_bodega) {
      bodegaBadge = '<tr><td colspan="2"><div class="tracking-bodega-indicador">Se saca de bodega</div></td></tr>';
    }

    card.innerHTML = `
      <table class="tracking-table">
        <thead>
          <tr>
            <th colspan="2" class="tracking-table-header">
              ${prenda.nombre_prenda || `Prenda ${index + 1}`}
            </th>
          </tr>
        </thead>
        <tbody>
          <tr class="tracking-table-row">
            <td class="tracking-table-label">Cantidad:</td>
            <td class="tracking-table-value">${prenda.cantidad || 0}</td>
          </tr>
          <tr class="tracking-table-row">
            <td class="tracking-table-label">Procesos:</td>
            <td class="tracking-table-value">${prenda.total_procesos || 0}</td>
          </tr>
          ${procesosHtml}
          ${bodegaBadge}
          ${seguimientosHtml ? `<tr><td colspan="2">${seguimientosHtml}</td></tr>` : ''}
          ${areasHtml ? `<tr><td colspan="2">${areasHtml}</td></tr>` : ''}
        </tbody>
      </table>
    `;
    
    return card;
  }

  // Permite editar el área actual incluso si no existe proceso (creación rápida con prefill)
  window.handleCrearProcesoDesdeArea = function(areaName, event, encargadoPrefill = '') {
    try {
      if (event) {
        event.preventDefault();
        event.stopPropagation();
      }

      if (typeof openAddProcesoModal !== 'function') {
        console.warn('[handleCrearProcesoDesdeArea] openAddProcesoModal no disponible');
        return;
      }

      // Asegurar que sea modo "agregar" (no edición)
      if (typeof resetFormButton === 'function') {
        resetFormButton();
      } else {
        window.editingProcessId = null;
      }

      openAddProcesoModal();

      const procesoArea = document.getElementById('procesoArea');
      if (procesoArea) procesoArea.value = areaName || '';

      // En modo "editar área actual" (sin id) prellenar encargado si lo tenemos
      const procesoEncargado = document.getElementById('procesoEncargado');
      const encargadoFallback = window.currentConsecutivoCosturaData?.encargado || '';
      const encargadoFinal = String(encargadoPrefill || encargadoFallback || '').trim();
      if (procesoEncargado) {
        procesoEncargado.value = encargadoFinal ? encargadoFinal.toUpperCase() : '';
      }
    } catch (e) {
      console.error('[handleCrearProcesoDesdeArea] Error:', e);
    }
  };

  // Renderizar badges de seguimientos por tipo de recibo
  function renderSeguimientosBadges(seguimientos) {
    if (Object.keys(seguimientos).length === 0) {
      return '';
    }
    
    let badgesHtml = '<div class="tracking-prenda-seguimientos">';
    
    Object.entries(seguimientos).forEach(([tipo, data]) => {
      const statusClass = data.tiene_disponibles ? 'pendiente' : 'completado';
      
      badgesHtml += `
        <span class="tracking-seguimiento-badge ${statusClass}">
          ${tipo}: ${data.consecutivo_actual}/${data.consecutivo_inicial}
        </span>
      `;
    });
    
    badgesHtml += '</div>';
    return badgesHtml;
  }

  // Renderizar badges de áreas/procesos
  function renderAreasBadges(areas) {
    if (Object.keys(areas).length === 0) {
      return '';
    }
    
    let badgesHtml = '<div class="tracking-prenda-areas">';
    
    Object.entries(areas).forEach(([area, data]) => {
      const statusClass = data.esta_activo ? 'pendiente' : 'completado';
      
      badgesHtml += `
        <span class="tracking-seguimiento-badge ${statusClass}">
          ${area}: ${data.estado}
        </span>
      `;
    });
    
    badgesHtml += '</div>';
    return badgesHtml;
  }

  // Mostrar seguimiento de una prenda específica desde la tabla
  window.showPrendaTrackingFromTable = async function(index) {
    try {
      console.log('[showPrendaTrackingFromTable] INICIO - Índice:', index);
      
      // Obtener la prenda desde el array global
      const prenda = window.prendasData[index];
      if (!prenda) {
        console.error('[showPrendaTrackingFromTable] Prenda no encontrada en índice:', index);
        return;
      }
      
      console.log('[showPrendaTrackingFromTable] Prenda encontrada:', prenda);
      
      // Llamar a la función original con el objeto prenda
      await showPrendaTracking(prenda);
      
    } catch (error) {
      console.error('[showPrendaTrackingFromTable] Error:', error);
    }
  };

  // Mostrar seguimiento de una prenda específica
  window.showPrendaTracking = async function(prenda) {
    try {
      console.log('[showPrendaTracking] INICIO - Mostrando seguimiento para prenda:', prenda);

      try {
        const tieneSeguimiento = prenda && (
          (prenda.seguimientos_por_area && Object.keys(prenda.seguimientos_por_area).length > 0) ||
          (prenda.seguimientos && Object.keys(prenda.seguimientos).length > 0) ||
          (prenda.ultimo_recibo_numero && prenda.ultimo_recibo_numero !== '-')
        );

        if (!tieneSeguimiento && Array.isArray(window.prendasData) && window.prendasData.length > 0) {
          const prendaId = prenda?.id || prenda?.prenda_pedido_id;
          const prendaEnriquecida = window.prendasData.find(p =>
            String(p?.id) === String(prendaId) || String(p?.prenda_pedido_id) === String(prendaId)
          );

          if (prendaEnriquecida) {
            prenda = Object.assign({}, prendaEnriquecida, prenda);
            console.log('[showPrendaTracking] Usando prenda enriquecida desde prendasData:', prendaEnriquecida);
          }
        }
      } catch (e) {
        console.warn('[showPrendaTracking] Error hidratando prenda desde prendasData:', e);
      }
      
      window.currentPrendaData = prenda;
      
      // Cerrar overlay de prendas
      const overlaySelector = document.getElementById('trackingPrendasSelectorOverlay');
      if (overlaySelector) {
        console.log('[showPrendaTracking] Cerrando overlay selector...');
        cerrarSelectorPrendas();
      }
      
      // Mostrar modal de seguimiento
      console.log('[showPrendaTracking] Buscando modal...');
      const modal = document.getElementById('orderTrackingModal');
      if (modal) {
        console.log('[showPrendaTracking] Modal encontrado, agregando clase show...');
        modal.classList.add('show');
        
        // FORZAR ESTILO DIRECTAMENTE CON JAVASCRIPT
        modal.style.setProperty('display', 'flex', 'important');
        modal.style.setProperty('visibility', 'visible', 'important');
        modal.style.setProperty('opacity', '1', 'important');
        
        // Iniciar timer para contadores dinámicos
        iniciarTimerContadores();
        modal.style.setProperty('z-index', '9999999', 'important');
        modal.style.setProperty('position', 'fixed', 'important');
        modal.style.setProperty('top', '0', 'important');
        modal.style.setProperty('left', '0', 'important');
        modal.style.setProperty('width', '100vw', 'important');
        modal.style.setProperty('height', '100vh', 'important');
        modal.style.setProperty('background', 'rgba(0, 0, 0, 0.5)', 'important');
        modal.style.setProperty('align-items', 'center', 'important');
        modal.style.setProperty('justify-content', 'center', 'important');
        
        // Asegurar que el botón volver funcione
        setupBackButton();
        
        console.log('[showPrendaTracking] Modal mostrado con estilos forzados');
        
        // Debug visual - verificar estado del modal
        setTimeout(() => {
          const modalElement = document.getElementById('orderTrackingModal');
          const computedStyle = window.getComputedStyle(modalElement);
          console.log('[showPrendaTracking] DEBUG - Estado del modal:', {
            display: computedStyle.display,
            visibility: computedStyle.visibility,
            opacity: computedStyle.opacity,
            zIndex: computedStyle.zIndex,
            hasClass: modalElement.classList.contains('show'),
            inlineDisplay: modalElement.style.display,
            inlineVisibility: modalElement.style.visibility
          });
        }, 100);
      } else {
        console.error('[showPrendaTracking] Modal no encontrado');
        return;
      }
      
      // Ocultar vista de prendas y mostrar timeline
      console.log('[showPrendaTracking] Actualizando vistas...');
      document.getElementById('trackingPrendasContainer').parentElement.style.display = 'none';
      document.getElementById('trackingTimelineSection').style.display = 'block';
      
      // CONTROLAR VISIBILIDAD DE BOTÓN AGREGAR BASADO EN READONLY
      const btnAgregar = document.getElementById('btnOpenAddProcesoModal');
      if (btnAgregar) {
        if (prenda?.readonly) {
          console.log('[showPrendaTracking] Modo READONLY: Ocultando botón AGREGAR ÁREA');
          btnAgregar.style.display = 'none';
          btnAgregar.disabled = true;
        } else {
          console.log('[showPrendaTracking] Modo NORMAL: Mostrando botón AGREGAR ÁREA');
          btnAgregar.style.display = 'block';
          btnAgregar.disabled = false;
        }
      }
      
      // Actualizar nombre de la prenda y número de recibo
      console.log('[showPrendaTracking] Actualizar nombre de la prenda y número de recibo');
      
      const nombreElement = document.getElementById('trackingPrendaName');
      if (nombreElement) {
        nombreElement.textContent = prenda.nombre_prenda || `Prenda ${prenda.id}`;
      }
      
      // Actualizar el header del recibo con el número más reciente
      const reciboHeaderElement = document.getElementById('trackingPrendaReciboHeader');
      if (reciboHeaderElement) {
        // Resolver área actual (prioridad: último proceso > área en prenda > área del pedido)
        let areaActual = '-';
        if (prenda.ultimo_proceso_area) {
          areaActual = prenda.ultimo_proceso_area;
        } else if (prenda.area && String(prenda.area).trim() !== '') {
          areaActual = prenda.area;
        } else if (!window.location.pathname.includes('/recibos-costura') && window.currentOrderData?.area && String(window.currentOrderData.area).trim() !== '') {
          areaActual = window.currentOrderData.area;
        }

        // Resolver encargado real (solo desde proceso)
        const encargadoActual = prenda.ultimo_proceso_encargado || null;

        // Debug: Ver qué datos tenemos
        console.log('[DEBUG] Datos de prenda para recibo:', {
          'ultimo_recibo_numero': prenda.ultimo_recibo_numero,
          'consecutivos': prenda.consecutivos,
          'consecutivos_length': prenda.consecutivos ? prenda.consecutivos.length : 'undefined',
          'area_actual_resuelta': areaActual
        });
        
        // Mostrar el número de recibo más reciente
        const numeroRecibo = prenda.ultimo_recibo_numero;
        if (numeroRecibo && numeroRecibo !== '-') {
          reciboHeaderElement.textContent = areaActual && areaActual !== '-'
            ? `Recibo #${numeroRecibo} - ${areaActual}`
            : `Recibo #${numeroRecibo}`;
          console.log('[DEBUG] Usando ultimo_recibo_numero:', numeroRecibo);
        } else {
          reciboHeaderElement.textContent = areaActual && areaActual !== '-'
            ? `Sin recibo - ${areaActual}`
            : 'Sin recibo';
          console.log('[DEBUG] ultimo_recibo_numero vacío o inválido');
        }
      }
      
      // Determinar número de recibo desde la tabla consecutivos_recibos_pedidos
      let numeroRecibo = 'Sin recibo';
      const consecutivosList = normalizeConsecutivos(prenda?.consecutivos);
      if (consecutivosList.length > 0) {
        console.log('[DEBUG] Procesando consecutivos:', consecutivosList);

        // Preferir COSTURA activo
        const reciboCosturaActivo = consecutivosList.find(r => String(r.tipo_recibo || '').toUpperCase() === 'COSTURA' && (r.activo === 1 || r.activo === true));
        const reciboActivo = reciboCosturaActivo || consecutivosList.find(r => (r.activo === 1 || r.activo === true));
        if (reciboActivo) {
          numeroRecibo = `${reciboActivo.tipo_recibo} #${reciboActivo.consecutivo_actual}`;
          console.log('[DEBUG] Recibo activo encontrado:', reciboActivo);
        } else if (consecutivosList[0]) {
          const primerRecibo = consecutivosList[0];
          numeroRecibo = `${primerRecibo.tipo_recibo} #${primerRecibo.consecutivo_actual}`;
          console.log('[DEBUG] Usando primer recibo:', primerRecibo);
        }
      } else {
        console.log('[DEBUG] No hay consecutivos en la prenda');
      }
      
      // Actualizar tanto el subtítulo del header como el del timeline
      if (reciboHeaderElement) {
        // Mantener el área en el header (si existe)
        const match = String(numeroRecibo || '');
        const areaActual = prenda?.ultimo_proceso_area
          || prenda?.area
          || (!window.location.pathname.includes('/recibos-costura') ? (window.currentOrderData?.area || '') : '');
        reciboHeaderElement.textContent = areaActual && String(areaActual).trim() !== ''
          ? `${match} - ${areaActual}`
          : match;
        console.log('[DEBUG] Header actualizado con:', numeroRecibo);
      }
      
      const reciboElement = document.getElementById('trackingPrendaRecibo');
      if (reciboElement) {
        reciboElement.textContent = numeroRecibo;
      }
      
      // Renderizar timeline de seguimiento
      console.log('[showPrendaTracking] Renderizando timeline...');
      renderPrendaTrackingTimeline(prenda);
      
      console.log('[showPrendaTracking] FINALIZADO - Seguimiento mostrado exitosamente');
      
    } catch (error) {
      console.error('[showPrendaTracking] Error:', error);
      showError('Error al cargar seguimiento de la prenda');
    }
  };

  // Renderizar timeline de seguimiento de prenda
  function renderPrendaTrackingTimeline(prenda) {
    const container = document.getElementById('trackingTimelineContainer');
    if (!container) return;

    console.log('[renderPrendaTrackingTimeline] Renderizando timeline para prenda:', prenda);
    console.log('[renderPrendaTrackingTimeline] Seguimientos por área en prenda:', prenda.seguimientos_por_area);

    // Botón de volver (eliminado - ya está en el header)
    container.innerHTML = '';

    // Renderizar seguimientos por área (procesos de producción)
    renderSeguimientosPorArea(prenda, container);

    // Renderizar seguimientos por tipo de recibo (ELIMINADO - no mostrar recibos en modal de seguimiento)
    // renderSeguimientosPorTipo(prenda, container);

    // Si no hay seguimientos por área, mostrar mensaje
    if (!prenda.seguimientos_por_area || Object.keys(prenda.seguimientos_por_area).length === 0) {
      renderNoSeguimiento(container);
    }
  }

  // Renderizar seguimientos por área (procesos)
  function renderSeguimientosPorArea(prenda, container) {
    const seguimientosPorArea = prenda.seguimientos_por_area || {};
    if (Object.keys(seguimientosPorArea).length === 0) {
      return;
    }

    let reciboCreatedAt = null;

    let activationSection = null;
    let areasSection = null;

    // Sección: fechas relevantes (creación de orden / activación del recibo)
    try {
      activationSection = document.createElement('div');
      activationSection.className = 'tracking-section tracking-section-activation';

      const activationTitle = document.createElement('div');
      activationTitle.className = 'tracking-section-title';
      activationTitle.textContent = 'Activación del recibo:';
      activationSection.appendChild(activationTitle);

      const fechasWrapper = document.createElement('div');
      fechasWrapper.className = 'tracking-info-row';

      const fechaCreacionOrden = window.currentOrderData?.fecha_de_creacion_de_orden || null;

      const consecutivosList = normalizeConsecutivos(prenda?.consecutivos);
      if (consecutivosList.length > 0) {
        const reciboCosturaActivo = consecutivosList.find(r => String(r.tipo_recibo || '').toUpperCase() === 'COSTURA' && (r.activo === 1 || r.activo === true));
        const reciboActivo = reciboCosturaActivo || consecutivosList.find(r => (r.activo === 1 || r.activo === true)) || consecutivosList[0];
        reciboCreatedAt = reciboActivo?.created_at || null;
      }

      const cardCreacionOrden = document.createElement('div');
      cardCreacionOrden.className = 'tracking-info-card';
      cardCreacionOrden.innerHTML = `
        <div class="tracking-info-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
          </svg>
        </div>
        <div class="tracking-info-content">
          <span class="tracking-info-label">Fecha creación orden</span>
          <span class="tracking-info-value">${formatDateTime(fechaCreacionOrden) || '-'}</span>
        </div>
      `;

      const cardActivacionRecibo = document.createElement('div');
      cardActivacionRecibo.className = 'tracking-info-card';
      cardActivacionRecibo.innerHTML = `
        <div class="tracking-info-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 14l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            <path d="M12 6v4m0 2h2"></path>
          </svg>
        </div>
        <div class="tracking-info-content">
          <span class="tracking-info-label">Fecha activación recibo</span>
          <span class="tracking-info-value">${formatDateTime(reciboCreatedAt) || '-'}</span>
        </div>
      `;

      // Tiempo transcurrido: mostrar duración real (días/h/m/s). Además, mostrar días hábiles como referencia.
      let tiempoTranscurridoText = '-';
      const fechaCreacionDate = toDateObject(fechaCreacionOrden);
      const reciboActDate = toDateObject(reciboCreatedAt);
      if (fechaCreacionDate && reciboActDate) {
        const diffMs = Math.max(0, reciboActDate.getTime() - fechaCreacionDate.getTime());
        const human = formatDurationHuman(diffMs);
        const diasHabiles = calcularDiasHabilesSync(fechaCreacionDate, reciboActDate);
        tiempoTranscurridoText = diasHabiles > 0
          ? `${human} (${diasHabiles} días hábiles)`
          : human;
      }

      const cardTiempoTrans = document.createElement('div');
      cardTiempoTrans.className = 'tracking-info-card';
      cardTiempoTrans.innerHTML = `
        <div class="tracking-info-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <path d="M12 6v6l4 2"></path>
          </svg>
        </div>
        <div class="tracking-info-content">
          <span class="tracking-info-label">Tiempo transcurrido</span>
          <span class="tracking-info-value">${tiempoTranscurridoText}</span>
        </div>
      `;

      fechasWrapper.appendChild(cardCreacionOrden);
      fechasWrapper.appendChild(cardActivacionRecibo);
      fechasWrapper.appendChild(cardTiempoTrans);
      activationSection.appendChild(fechasWrapper);
      container.appendChild(activationSection);
    } catch (e) {
      console.warn('[renderSeguimientosPorArea] No se pudo renderizar sección de fechas:', e);
    }

    // Sección: Seguimiento por áreas + botón Agregar Área
    areasSection = document.createElement('div');
    areasSection.className = 'tracking-section tracking-section-areas';

    const areasHeader = document.createElement('div');
    areasHeader.className = 'tracking-section-header';

    const headerTitle = document.createElement('div');
    headerTitle.className = 'tracking-section-title';
    headerTitle.textContent = 'Seguimiento por áreas:';
    areasHeader.appendChild(headerTitle);

    const btnAgregarArea = document.getElementById('btnAgregarArea');
    if (btnAgregarArea) {
      areasHeader.appendChild(btnAgregarArea);
    }

    areasSection.appendChild(areasHeader);
    container.appendChild(areasSection);

    // Insertar área virtual "Insumos" (fecha llegada = activación recibo)
    const mergedAreas = { ...seguimientosPorArea };
    const hasInsumos = Object.keys(mergedAreas).some(k => String(k || '').toLowerCase() === 'insumos');
    if (!hasInsumos && reciboCreatedAt) {
      const areaCorteKey = Object.keys(mergedAreas).find(k => String(k || '').toLowerCase().includes('corte')) || null;
      let areaEnvioProduccionKey = areaCorteKey;
      let fechaEnvioProduccion = areaCorteKey ? (mergedAreas[areaCorteKey]?.fecha_inicio || null) : null;

      // Fallback: si no hay Corte, usar la primera área con fecha_inicio más temprana (lo más cercano a "envío")
      if (!fechaEnvioProduccion) {
        let bestKey = null;
        let bestDate = null;
        Object.entries(mergedAreas).forEach(([k, v]) => {
          if (String(k || '').toLowerCase() === 'insumos') return;
          const d = toDateObject(v?.fecha_inicio);
          if (!d) return;
          if (!bestDate || d.getTime() < bestDate.getTime()) {
            bestDate = d;
            bestKey = k;
          }
        });
        if (bestKey && bestDate) {
          areaEnvioProduccionKey = bestKey;
          fechaEnvioProduccion = mergedAreas[bestKey]?.fecha_inicio || null;
        }
      }

      const yaEnviadoAProduccion = Boolean(fechaEnvioProduccion);

      mergedAreas['Insumos'] = {
        estado: yaEnviadoAProduccion ? 'Enviado a producción' : 'Llegó a insumos',
        encargado: '-',
        fecha_inicio: reciboCreatedAt,
        fecha_fin: fechaEnvioProduccion,
        duracion_dias: null,
        icono: 'inventory_2',
        esta_activo: !yaEnviadoAProduccion,
        can_edit: false,
        hide_encargado: true,
        tiempo_transcurrido: (function() {
          const ini = toDateObject(reciboCreatedAt);
          const fin = toDateObject(fechaEnvioProduccion);
          if (!ini || !fin) return null;
          return formatDurationHuman(Math.max(0, fin.getTime() - ini.getTime()));
        })()
      };
    }

    const orderedEntries = [];
    if (mergedAreas['Insumos']) {
      orderedEntries.push(['Insumos', mergedAreas['Insumos']]);
    }
    Object.entries(mergedAreas).forEach(([area, data]) => {
      if (String(area || '').toLowerCase() === 'insumos') return;
      orderedEntries.push([area, data]);
    });

    orderedEntries.forEach(([area, data]) => {
      const areaCard = createAreaCard(area, data, prenda?.readonly || false);
      areasSection.appendChild(areaCard);
    });
  }

  // Mostrar mensaje si no hay seguimientos
  function renderNoSeguimiento(container) {
    const noSeguimiento = document.createElement('div');
    noSeguimiento.className = 'tracking-no-seguimiento';

    // Mantener el mensaje original
    noSeguimiento.innerHTML = '<p>No hay seguimientos registrados para esta prenda</p>';
    container.appendChild(noSeguimiento);

    // Usar la UI original del tracking para mostrar el área actual y encargado si se puede
    // (sin inventar una vista nueva). La edición/creación se hace con el botón "Agregar Área".
    const prenda = window.currentPrendaData || {};
    const esRecibosCostura = window.location.pathname.includes('/recibos-costura');

    const procesoIdFallback = window.currentConsecutivoCosturaData?.proceso_id || null;
    const tieneProcesoReal = Boolean(prenda?.ultimo_proceso_id || procesoIdFallback);

    const areaActual = prenda?.ultimo_proceso_area
      || (prenda?.area && String(prenda.area).trim() !== '' ? prenda.area : null)
      || (!esRecibosCostura && window.currentOrderData?.area && String(window.currentOrderData.area).trim() !== '' ? window.currentOrderData.area : null)
      || null;

    // Encargado real solo desde procesos_prenda; fallback a /consecutivo-costura si está disponible
    const encargadoActual = prenda?.ultimo_proceso_encargado
      || window.currentConsecutivoCosturaData?.encargado
      || null;

    // Si hay algo que mostrar, renderizar una tarjeta estándar de área.
    if (tieneProcesoReal && areaActual && typeof createAreaCard === 'function') {
      const estadoUltimo = prenda?.ultimo_proceso_estado || 'Pendiente';
      const estaActivo = estadoUltimo !== 'Completado';

      const fechaInicioFallback = window.currentConsecutivoCosturaData?.fecha_inicio || null;
      const fechaFinFallback = window.currentConsecutivoCosturaData?.fecha_fin || null;

      const card = createAreaCard(areaActual, {
        id: prenda?.ultimo_proceso_id || procesoIdFallback,
        can_edit: true,
        area: areaActual,
        estado: estadoUltimo,
        fecha_inicio: prenda?.ultimo_proceso_fecha_inicio || fechaInicioFallback,
        fecha_fin: prenda?.ultimo_proceso_fecha_fin || fechaFinFallback,
        encargado: encargadoActual || 'No asignado',
        observaciones: prenda?.ultimo_proceso_observaciones || '',
        codigo_referencia: prenda?.ultimo_proceso_codigo_referencia || null,
        dias_duracion: prenda?.ultimo_proceso_dias_duracion || null,
        esta_activo: estaActivo,
      }, prenda?.readonly || false);
      container.appendChild(card);
    }
  }

  // Manejar eliminación de proceso
  window.handleEliminarProceso = async function(procesoId, areaName, event) {
    // Detener propagación para evitar que se cierre el modal
    if (event) {
      event.stopPropagation();
    }
    
    // Mostrar modal de confirmación
    showConfirmDeleteModal(procesoId, areaName);
  };

  // Mostrar modal de confirmación para eliminar
  function showConfirmDeleteModal(procesoId, areaName) {
    console.log('[showConfirmDeleteModal] Mostrando confirmación para eliminar:', { procesoId, areaName });
    
    const modal = document.getElementById('confirmDeleteModal');
    const processNameSpan = document.getElementById('deleteProcessName');
    
    if (modal && processNameSpan) {
      // Establecer el nombre del proceso
      processNameSpan.textContent = areaName;
      
      // Mostrar el modal
      modal.classList.add('show');
      modal.style.setProperty('display', 'flex', 'important');
      modal.style.setProperty('visibility', 'visible', 'important');
      modal.style.setProperty('opacity', '1', 'important');
      modal.style.setProperty('z-index', '10000001', 'important');
      
      // Guardar el ID del proceso a eliminar
      window.processToDelete = { id: procesoId, name: areaName };
      
      // Configurar listeners
      setupConfirmDeleteModalListeners();
      
      console.log('[showConfirmDeleteModal] Modal de confirmación mostrado');
    } else {
      console.error('[showConfirmDeleteModal] No se encontró el modal o el span del nombre');
    }
  }

  // Configurar listeners del modal de confirmación
  function setupConfirmDeleteModalListeners() {
    // Botón cancelar
    const btnCancel = document.getElementById('btnCancelDelete');
    if (btnCancel) {
      btnCancel.onclick = closeConfirmDeleteModal;
    }
    
    // Botón cerrar (X)
    const btnClose = document.getElementById('closeConfirmDeleteModal');
    if (btnClose) {
      btnClose.onclick = closeConfirmDeleteModal;
    }
    
    // Botón confirmar eliminar
    const btnConfirm = document.getElementById('btnConfirmDelete');
    if (btnConfirm) {
      btnConfirm.onclick = executeDeleteProcess;
    }
    
    // Cerrar al hacer clic en el overlay
    const overlay = document.querySelector('.confirm-delete-overlay');
    if (overlay) {
      overlay.onclick = closeConfirmDeleteModal;
    }
  }

  // Cerrar modal de confirmación
  function closeConfirmDeleteModal() {
    const modal = document.getElementById('confirmDeleteModal');
    if (modal) {
      modal.classList.remove('show');
      modal.style.display = 'none';
      window.processToDelete = null;
    }
  }

  // Ejecutar la eliminación del proceso
  async function executeDeleteProcess() {
    if (!window.processToDelete) return;
    
    // Mostrar indicador de carga
    const btnContent = document.getElementById('deleteButtonContent');
    const btnLoading = document.getElementById('deleteButtonLoading');
    const btnConfirm = document.getElementById('btnConfirmDelete');
    
    if (btnContent && btnLoading && btnConfirm) {
      btnContent.style.display = 'none';
      btnLoading.style.display = 'flex';
      btnConfirm.disabled = true;
    }
    
    const { id: procesoId, name: areaName } = window.processToDelete;
    
    try {
      console.log('[executeDeleteProcess] Eliminando proceso:', { procesoId, areaName });

      const response = await fetch('/seguimiento-proceso/' + procesoId, {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        }
      });

      if (!response.ok) {
        throw new Error('Error al eliminar proceso');
      }

      const result = await response.json();
      console.log('[executeDeleteProcess] Proceso eliminado:', result);

      // Cerrar modal de confirmación
      closeConfirmDeleteModal();

      // Recargar seguimientos de la prenda
      console.log('[executeDeleteProcess] Recargando seguimientos para orden:', window.currentOrderData.id);
      await loadPrendasWithTracking(window.currentOrderData.id);

      // Refrescar consecutivo/area/encargado/fechas (fallback del modal)
      try {
        if (window.location.pathname.includes('/recibos-costura') && window.currentOrderData?.id && window.currentPrendaData?.id) {
          const url = `/registros/${window.currentOrderData.id}/consecutivo-costura?prenda_id=${window.currentPrendaData.id}`;
          const resp = await fetch(url);
          if (resp.ok) {
            window.currentConsecutivoCosturaData = await resp.json();
          }
        }
      } catch (e) {
        console.warn('[executeDeleteProcess] No se pudo refrescar consecutivo-costura:', e);
      }
      
      console.log('[executeDeleteProcess] Seguimientos recargados');
      
      // Buscar la prenda actualizada en los datos recargados
      if (window.prendasData && window.prendasData.length > 0 && window.currentPrendaData) {
        const prendaActualizada = window.prendasData.find(p => p.id == window.currentPrendaData.id);
        if (prendaActualizada) {
          window.currentPrendaData = prendaActualizada;
          console.log('[executeDeleteProcess] Prenda actualizada encontrada:', window.currentPrendaData);
        }
      }
      
      // Actualizar vista actual
      if (window.currentPrendaData && window.currentPrendaData.id) {
        console.log('[executeDeleteProcess] Actualizando timeline con prenda actualizada:', window.currentPrendaData);
        renderPrendaTrackingTimeline(window.currentPrendaData);
      } else {
        console.log('[executeDeleteProcess] No hay currentPrendaData válida, intentando obtener del DOM');
        // Si no hay currentPrendaData, intentar obtener la primera prenda del DOM
        const prendaCards = document.querySelectorAll('.prenda-card');
        if (prendaCards.length > 0) {
          const firstCard = prendaCards[0];
          const prendaId = parseInt(firstCard.dataset.prendaId);
          
          // Buscar en prendasData
          let prendaParaRender = null;
          if (window.prendasData) {
            prendaParaRender = window.prendasData.find(p => p.id == prendaId);
          }
          
          if (prendaParaRender) {
            console.log('[executeDeleteProcess] Usando prendaData de prendasData:', prendaParaRender);
            window.currentPrendaData = prendaParaRender;
            renderPrendaTrackingTimeline(prendaParaRender);
          } else {
            // Fallback: crear objeto con el ID
            const prendaData = {
              id: prendaId,
              nombre_prenda: firstCard.querySelector('.prenda-name')?.textContent,
            };
            console.log('[executeDeleteProcess] Usando prendaData del DOM:', prendaData);
            renderPrendaTrackingTimeline(prendaData);
          }
        }
      }

      // Mostrar mensaje de éxito
      showSuccess('Proceso eliminado correctamente');
      
      // Actualizar el área en la tabla de recibos-costura si estamos en esa página
      actualizarAreaEnTablaRecibos();

    } catch (error) {
      console.error('[executeDeleteProcess] Error:', error);
      showError('Error al eliminar proceso: ' + error.message);
      closeConfirmDeleteModal();
    } finally {
      // Ocultar indicador de carga
      const btnContent = document.getElementById('deleteButtonContent');
      const btnLoading = document.getElementById('deleteButtonLoading');
      const btnConfirm = document.getElementById('btnConfirmDelete');
      
      if (btnContent && btnLoading && btnConfirm) {
        btnContent.style.display = 'flex';
        btnLoading.style.display = 'none';
        btnConfirm.disabled = false;
      }
    }
  }
  
  // Actualizar el área en la tabla de recibos-costura
  async function actualizarAreaEnTablaRecibos() {
    try {
      console.log('[actualizarAreaEnTablaRecibos] Verificando si estamos en recibos-costura');
      
      // Verificar si estamos en la página de recibos-costura
      if (!window.location.pathname.includes('/recibos-costura')) {
        console.log('[actualizarAreaEnTablaRecibos] No estamos en recibos-costura, omitiendo actualización');
        return;
      }

      const pedidoId = window.currentOrderData?.id || null;
      const prendaId = window.currentPrendaData?.id || null;
      const numeroRecibo = window.currentConsecutivoCosturaData?.consecutivo || null;

      if (!pedidoId || !prendaId || !numeroRecibo) {
        console.warn('[actualizarAreaEnTablaRecibos] Datos insuficientes para refrescar fila', {
          pedidoId,
          prendaId,
          numeroRecibo
        });
        return;
      }

      const row = findReciboCosturaRow(pedidoId, prendaId, numeroRecibo);
      if (!row) {
        console.warn('[actualizarAreaEnTablaRecibos] No se encontró fila a actualizar', {
          pedidoId,
          prendaId,
          numeroRecibo
        });
        return;
      }

      const url = `/registros/${pedidoId}/consecutivo-costura?prenda_id=${encodeURIComponent(prendaId)}`;
      const resp = await fetch(url);
      if (!resp.ok) {
        throw new Error(`Error HTTP ${resp.status} al refrescar consecutivo-costura`);
      }
      const data = await resp.json();
      console.log('[actualizarAreaEnTablaRecibos] Respuesta consecutivo-costura:', data);

      if (!data || !data.success) {
        return;
      }

      // Área (columna 3)
      const areaBadge = row.querySelector('td:nth-child(3) .badge');
      if (areaBadge && data.area) {
        areaBadge.textContent = data.area;
      }

      // Encargado orden (última columna)
      const encargadoSpan = row.querySelector('td:last-child span');
      if (encargadoSpan) {
        encargadoSpan.textContent = (data.encargado && String(data.encargado).trim() !== '')
          ? String(data.encargado).trim()
          : '-';
      }
      
    } catch (error) {
      console.error('[actualizarAreaEnTablaRecibos] Error general:', error);
    }
  }

  function findReciboCosturaRow(pedidoId, prendaId, numeroRecibo) {
    const filas = document.querySelectorAll('#tablaRecibosBody tr[data-pedido-id][data-numero-recibo]');
    for (const fila of filas) {
      const filaPedidoId = fila.getAttribute('data-pedido-id');
      const filaNumeroRecibo = fila.getAttribute('data-numero-recibo');
      if (String(filaPedidoId) !== String(pedidoId) || String(filaNumeroRecibo) !== String(numeroRecibo)) {
        continue;
      }
      const btn = fila.querySelector('.btn-ver-dropdown');
      const filaPrendaId = btn ? btn.getAttribute('data-prenda-id') : null;
      if (String(filaPrendaId) === String(prendaId)) {
        return fila;
      }
    }
    return null;
  }

  // Manejar edición de proceso
  window.handleEditarProceso = function(procesoId, areaName, processData, event) {
    // Detener propagación para evitar que se cierre el modal
    if (event) {
      event.stopPropagation();
    }
    
    console.log('[handleEditarProceso] Editando proceso:', { procesoId, areaName, processData });
    
    // Verificar si los elementos del formulario existen
    const procesoArea = document.getElementById('procesoArea');
    const procesoEstado = document.getElementById('procesoEstado');
    const procesoFechaInicio = document.getElementById('procesoFechaInicio');
    const procesoEncargado = document.getElementById('procesoEncargado');
    const procesoObservaciones = document.getElementById('procesoObservaciones');
    
    console.log('[handleEditarProceso] Elementos del formulario:', {
      procesoArea: !!procesoArea,
      procesoEstado: !!procesoEstado,
      procesoFechaInicio: !!procesoFechaInicio,
      procesoEncargado: !!procesoEncargado,
      procesoObservaciones: !!procesoObservaciones
    });
    
    // Llenar el formulario con los datos actuales
    if (procesoArea) procesoArea.value = processData.area || areaName;
    if (procesoEstado) procesoEstado.value = processData.estado || 'Pendiente';
    if (procesoFechaInicio) {
      // Formatear la fecha para el input date (YYYY-MM-DD)
      const fechaInicio = processData.fecha_inicio;
      if (fechaInicio) {
        const date = new Date(fechaInicio);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        procesoFechaInicio.value = `${year}-${month}-${day}`;
      }
    }
    if (procesoEncargado) procesoEncargado.value = processData.encargado || '';
    if (procesoObservaciones) procesoObservaciones.value = processData.observaciones || '';
    
    // Guardar el ID del proceso que se está editando
    window.editingProcessId = procesoId;
    
    // Cambiar el texto del botón a "Actualizar"
    const btnConfirmar = document.getElementById('btnConfirmAddProceso');
    if (btnConfirmar) {
      btnConfirmar.textContent = 'Actualizar Proceso';
      btnConfirmar.onclick = function() { handleActualizarProceso(procesoId); };
    }
    
    // Abrir el modal de agregar/editar proceso
    openAddProcesoModal();
    
    console.log('[handleEditarProceso] Modal de agregar/editar proceso abierto');
  };

  // Manejar actualización de proceso
  window.handleActualizarProceso = async function(procesoId) {
    try {
      const procesoAreaEl = document.getElementById('procesoArea');
      const procesoEstadoEl = document.getElementById('procesoEstado');
      const procesoFechaInicioEl = document.getElementById('procesoFechaInicio');
      const procesoEncargadoEl = document.getElementById('procesoEncargado');
      const procesoObservacionesEl = document.getElementById('procesoObservaciones');

      if (!procesoAreaEl || !procesoEncargadoEl) {
        throw new Error('No se encontraron los campos del formulario para actualizar el proceso. Por favor recarga la página.');
      }

      const area = procesoAreaEl.value;
      const estado = procesoEstadoEl ? procesoEstadoEl.value : 'Pendiente';
      const fechaInicio = procesoFechaInicioEl ? procesoFechaInicioEl.value : '';
      const encargado = procesoEncargadoEl.value;
      const observaciones = procesoObservacionesEl ? procesoObservacionesEl.value : '';

      console.log('[handleActualizarProceso] Actualizando proceso:', {
        procesoId, area, estado, fechaInicio, encargado, observaciones
      });

      const response = await fetch('/seguimiento-proceso/' + procesoId, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        },
        body: JSON.stringify({
          area: area,
          estado: estado,
          fecha_inicio: fechaInicio || null,
          encargado: encargado,
          observaciones: observaciones
        })
      });

      if (!response.ok) {
        throw new Error('Error al actualizar proceso');
      }

      const result = await response.json();
      console.log('[handleActualizarProceso] Proceso actualizado:', result);

      // Limpiar formulario y resetear botón
      limpiarFormularioProceso();
      resetFormButton();

      // Cerrar modal de agregar/editar proceso
      try {
        closeAddProcesoModal();
      } catch (e) {
        console.warn('[handleActualizarProceso] No se pudo cerrar addProcesoModal:', e);
      }

      // Recargar seguimientos de la prenda
      const orderId = window.currentOrderData?.id;
      if (orderId) {
        await loadPrendasWithTracking(orderId);
      } else {
        console.warn('[handleActualizarProceso] currentOrderData.id no disponible, no se recargan prendas');
      }
      
      // Actualizar vista actual
      if (window.currentPrendaData && window.currentPrendaData.id && Array.isArray(window.prendasData)) {
        const prendaActualizada = window.prendasData.find(p => String(p.id) === String(window.currentPrendaData.id));
        if (prendaActualizada) {
          window.currentPrendaData = prendaActualizada;
        }
      }

      if (window.currentPrendaData) {
        renderPrendaTrackingTimeline(window.currentPrendaData);
      }

      // Mostrar mensaje de éxito
      showSuccess('Proceso actualizado correctamente');

      // Actualizar la fila en la tabla de recibos-costura si estamos en esa página
      await actualizarAreaEnTablaRecibos();

    } catch (error) {
      console.error('[handleActualizarProceso] Error:', error);
      showError('Error al actualizar proceso: ' + error.message);
    }
  };

  // Limpiar formulario de proceso
  function limpiarFormularioProceso() {
    const procesoArea = document.getElementById('procesoArea');
    if (procesoArea) procesoArea.value = '';

    const procesoEncargado = document.getElementById('procesoEncargado');
    if (procesoEncargado) procesoEncargado.value = '';

    const procesoEstado = document.getElementById('procesoEstado');
    if (procesoEstado) procesoEstado.value = 'Pendiente';

    const procesoFechaInicio = document.getElementById('procesoFechaInicio');
    if (procesoFechaInicio) procesoFechaInicio.value = '';

    const procesoObservaciones = document.getElementById('procesoObservaciones');
    if (procesoObservaciones) procesoObservaciones.value = '';
  }

  // Resetear botón del formulario a su estado original
  function resetFormButton() {
    const btnConfirmar = document.getElementById('btnConfirmAddProceso');
    if (btnConfirmar) {
      btnConfirmar.textContent = 'Agregar Proceso';
      btnConfirmar.onclick = handleAgregarProceso;
    }
    window.editingProcessId = null;
  }

  // Crear tarjeta de área/proceso
  function createAreaCard(area, data, readonly = false) {
    const card = document.createElement('div');
    
    // Si es readonly, agregar clase visual
    if (readonly) {
      card.classList.add('tracking-readonly-mode');
    }
    
    const iconSvg = getIconSvg(data.icono || 'description');
    
    const fechaCompletadoDisplay = data.fecha_completado || null;
    const fechaFinParaDuracion = fechaCompletadoDisplay || data.fecha_fin || null;

    const totalDiasArea = (function() {
      const ini = toDateObject(data.fecha_inicio);
      
      // Si no hay fecha de fin o completado, contar hasta hoy (dinámico)
      if (!fechaFinParaDuracion) {
        if (!ini) return null;
        return calcularDiasHabilesSync(ini, new Date());
      }
      
      // Si hay fecha fin/completado, contar hasta esa fecha (estático)
      const fin = toDateObject(fechaFinParaDuracion);
      if (!ini || !fin) return null;
      
      // Usar cálculo de días hábiles con festivos (igual que recibos-costura)
      return calcularDiasHabilesSync(ini, fin);
    })();

    const isInsumos = String(area || '').toLowerCase() === 'insumos';
    const isCorte = String(area || '').toLowerCase().includes('corte');
    const isCostura = String(area || '').toLowerCase().includes('costura');
    const isControlCalidad = String(area || '').toLowerCase().includes('control') && String(area || '').toLowerCase().includes('calidad');
    
    // Procesos que requieren encargado y usan fecha_completado como fecha_fin
    const needsEncargado = isCorte || isCostura || isControlCalidad;
    const shouldShowAssignmentDuration = needsEncargado;
    
    // Determinar si se debe ocultar el campo de encargado
    const shouldHideEncargado = isInsumos || !needsEncargado;

    const hasFechaCompletado = !isInsumos && Boolean(toDateObject(data.fecha_completado));
    const estadoDisplay = isInsumos ? (data.estado || 'Pendiente') : (hasFechaCompletado ? 'Completado' : 'Pendiente');
    const estaActivoDisplay = isInsumos ? Boolean(data.esta_activo) : !hasFechaCompletado;

    card.className = `tracking-area-card tracking-area-card-v2 ${estaActivoDisplay ? 'pending' : 'completed'}`;

    const formatBadgeDuration = function(diffMs) {
      const ms = Math.max(0, Number(diffMs) || 0);
      const minutes = Math.floor(ms / 60000);
      const hours = Math.floor(ms / 3600000);
      const days = Math.floor(ms / 86400000);
      
      if (days >= 1) {
        return `${days} ${days === 1 ? 'Día' : 'Días'}`;
      } else if (hours >= 1) {
        return `${hours}h`;
      } else if (minutes >= 1) {
        return `${minutes}min`;
      } else {
        return '< 1min';
      }
    };

    const fechaLlegada = formatDate(data.fecha_inicio) || '---';
    
    // Lógica dinámica para fecha_fin según el tipo de proceso
    let fechaFinRaw = null;
    if (isInsumos) {
      fechaFinRaw = data.fecha_fin || null;
    } else if (needsEncargado) {
      // Para Corte, Costura, Control Calidad: usar fecha_completado
      fechaFinRaw = data.fecha_completado || null;
    } else {
      // Para otros procesos (Entrega, Despacho, etc.): usar fecha_fin o determinar dinámicamente
      fechaFinRaw = data.fecha_fin || null;
      
      // Si no hay fecha_fin, podríamos intentar determinarla por el siguiente proceso
      // Esto requeriría datos adicionales de los otros procesos
    }
    
    const fechaFin = formatDate(fechaFinRaw) || (data.esta_activo ? '---' : '---');

    const fechaAsignacion = formatDate(data.fecha_de_asignacion_encargado) || '---';
    const duracionAsignacion = (function() {
      if (!shouldShowAssignmentDuration) return '---';
      const ini = toDateObject(data.fecha_inicio);
      const asg = toDateObject(data.fecha_de_asignacion_encargado);
      if (!ini || !asg) return '---';
      const diffMs = asg.getTime() - ini.getTime();
      return formatBadgeDuration(diffMs);
    })();

    const duracionEnArea = (function() {
      if (needsEncargado) {
        // Para procesos con encargado: calcular desde asignación hasta completado
        const asg = toDateObject(data.fecha_de_asignacion_encargado);
        const fin = toDateObject(fechaFinRaw);
        if (!asg || !fin) return '---';
        const diffMs = fin.getTime() - asg.getTime();
        return formatBadgeDuration(diffMs);
      } else {
        // Para procesos sin encargado: calcular desde inicio hasta fin
        const ini = toDateObject(data.fecha_inicio);
        const fin = toDateObject(fechaFinRaw);
        if (!ini || !fin) return '---';
        const diffMs = fin.getTime() - ini.getTime();
        return formatBadgeDuration(diffMs);
      }
    })();

    const totalDiasAreaDisplay = (function() {
      if (!needsEncargado) {
        // Para procesos sin encargado: calcular duración total
        const ini = toDateObject(data.fecha_inicio);
        
        // Si no hay fecha fin, contar hasta hoy (dinámico)
        if (!fechaFinRaw) {
          if (!ini) return '---';
          const diasHabiles = calcularDiasHabilesSync(ini, new Date());
          return diasHabiles === 0 ? '0 días' : `${diasHabiles} día${diasHabiles !== 1 ? 's' : ''}`;
        }
        
        // Si hay fecha fin, contar hasta esa fecha (estático)
        const fin = toDateObject(fechaFinRaw);
        if (!ini || !fin) return '---';
        const diasHabiles = calcularDiasHabilesSync(ini, fin);
        return diasHabiles === 0 ? '0 días' : `${diasHabiles} día${diasHabiles !== 1 ? 's' : ''}`;
      }

      // Para procesos con encargado: calcular suma de asignación + duración en área
      const ini = toDateObject(data.fecha_inicio);
      const asg = toDateObject(data.fecha_de_asignacion_encargado);
      
      let fin;
      // Si no hay fecha fin, usar hoy (dinámico)
      if (!fechaFinRaw) {
        fin = new Date();
      } else {
        fin = toDateObject(fechaFinRaw);
      }
      
      if (!ini || !fin) {
        return totalDiasArea === null ? '---' : (totalDiasArea === 0 ? '0 días' : `${totalDiasArea} día${totalDiasArea !== 1 ? 's' : ''}`);
      }

      // Si hay encargado asignado, contar desde asignación; sino desde inicio
      const inicioCalculo = asg || ini;
      const diasTotales = calcularDiasHabilesSync(inicioCalculo, fin);
      return diasTotales === 0 ? '0 días' : `${diasTotales} día${diasTotales !== 1 ? 's' : ''}`;
    })();

    const tiempoCompletadoDisplay = (function() {
      if (data.tiempo_transcurrido) return data.tiempo_transcurrido;
      
      const ini = toDateObject(data.fecha_inicio);
      if (!ini) return null;
      
      let fin;
      // Si no hay fecha completado, usar hoy (dinámico)
      if (!fechaCompletadoDisplay) {
        fin = new Date();
      } else {
        fin = toDateObject(fechaCompletadoDisplay);
        if (!fin) return null;
      }
      
      const diasHabiles = calcularDiasHabilesSync(ini, fin);
      return diasHabiles === 0 ? '0 días' : `${diasHabiles} día${diasHabiles !== 1 ? 's' : ''}`;
    })();

    // SOLO generar botones si NO es readonly
    const accionesHtml = readonly ? '' : `${(data.id || data.can_edit) ? `
            <button class="tracking-edit-btn" onclick="${data.id ? `handleEditarProceso(${data.id}, '${area}', ${JSON.stringify(data).replace(/"/g, '&quot;')}, event)` : `handleCrearProcesoDesdeArea('${area}', event, '${String(data.encargado || '').replace(/'/g, "\\'")}')`}" title="Editar proceso">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
              </svg>
            </button>
            ${data.id ? `
            <button class="tracking-delete-btn" onclick="handleEliminarProceso(${data.id}, '${area}', event)" title="Eliminar proceso">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14zM10 11v6M14 11v6"/>
              </svg>
            </button>
            ` : ''}
            ` : ''}`;

    if (isInsumos) {
      card.innerHTML = `
        <div class="tracking-area-v2-left">
          <div class="tracking-area-v2-icon">${iconSvg}</div>
          <div class="tracking-area-v2-name">${area}</div>
        </div>

        <div class="tracking-area-v2-body">
          <div class="tracking-area-v2-row">
            <div class="tracking-area-v2-field">
              <div class="tracking-area-v2-label">Fecha de llegada:</div>
              <div class="tracking-area-v2-pill">${fechaLlegada}</div>
            </div>
            <div class="tracking-area-v2-field">
              <div class="tracking-area-v2-label">Fecha de envío a producción</div>
              <div class="tracking-area-v2-pill">${fechaFin}</div>
            </div>
            <div class="tracking-area-v2-field tracking-area-v2-field-right"></div>
          </div>

          <div class="tracking-area-v2-footer">
            <div class="tracking-area-v2-status">
              <span class="tracking-days-badge ${estaActivoDisplay ? '' : 'tracking-days-badge-zero'}">${estadoDisplay}</span>
            </div>
            <div class="tracking-area-v2-actions">${accionesHtml}</div>
            <div class="tracking-area-v2-total-days">
              <span class="tracking-area-v2-total-label">Total Días:</span>
              <span class="tracking-area-v2-total-value">${totalDiasAreaDisplay}</span>
            </div>
          </div>
        </div>
      `;
    } else {
      card.innerHTML = `
        <div class="tracking-area-v2-left">
          <div class="tracking-area-v2-icon">${iconSvg}</div>
          <div class="tracking-area-v2-name">${area}</div>
        </div>

        <div class="tracking-area-v2-body">
          <div class="tracking-area-v2-row">
            <div class="tracking-area-v2-field">
              <div class="tracking-area-v2-label">Fecha de llegada:</div>
              <div class="tracking-area-v2-pill">${fechaLlegada}</div>
            </div>
            ${!data.encargado || data.encargado.trim() === '' ? `
            <div class="tracking-area-v2-field tracking-area-v2-field-right">
              <div class="tracking-area-v2-label">Fecha fin</div>
              <div class="tracking-area-v2-pill">${fechaFin}</div>
            </div>
            ` : `
            <div class="tracking-area-v2-field">
              <div class="tracking-area-v2-label">Fecha de asignación de ${String(area).toLowerCase()}:</div>
              <div class="tracking-area-v2-pill">${fechaAsignacion}</div>
            </div>
            <div class="tracking-area-v2-field tracking-area-v2-field-right">
              <div class="tracking-area-v2-label">Duración asignación de ${String(area).toLowerCase()}:</div>
              <div class="tracking-area-v2-badge">${duracionAsignacion}</div>
            </div>
            `}
          </div>

          <div class="tracking-area-v2-row">
            ${!data.encargado || data.encargado.trim() === '' ? '' : `
            ${shouldHideEncargado || data.hide_encargado ? '' : `
            <div class="tracking-area-v2-field">
              <div class="tracking-area-v2-label">Encargado:</div>
              <div class="tracking-area-v2-pill">${data.encargado || '---'}</div>
            </div>
            `}
            <div class="tracking-area-v2-field">
              <div class="tracking-area-v2-label">Fecha fin</div>
              <div class="tracking-area-v2-pill">${fechaFin}</div>
            </div>
            <div class="tracking-area-v2-field tracking-area-v2-field-right">
              <div class="tracking-area-v2-label">Duración en ${area}</div>
              <div class="tracking-area-v2-badge">${duracionEnArea}</div>
            </div>
            `}
          </div>

          <div class="tracking-area-v2-footer">
            <div class="tracking-area-v2-status">
              <span class="tracking-days-badge ${estaActivoDisplay ? '' : 'tracking-days-badge-zero'}">${estadoDisplay}</span>
            </div>
            <div class="tracking-area-v2-actions">${accionesHtml}</div>
            <div class="tracking-area-v2-total-days">
              <span class="tracking-area-v2-total-label">Total Días:</span>
              <span class="tracking-area-v2-total-value">${totalDiasAreaDisplay}</span>
            </div>
          </div>
        </div>
      `;
    }
    
    return card;
  }

  // Obtener SVG del icono
  function getIconSvg(iconName) {
    const icons = {
      // Iconos genéricos existentes
      'description': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>',
      'inventory_2': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"></path></svg>',
      'content_cut': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="6" cy="6" r="3"></circle><circle cx="18" cy="18" r="3"></circle><path d="M20.41 3.59l-7.06 7.06a2 2 0 01-2.83 0l-2.12-2.12a2 2 0 010-2.83l7.06-7.06a2 2 0 012.83 0l2.12 2.12a2 2 0 010 2.83z"></path></svg>',
      'brush': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.71 4.63l-1.34-1.34a1 1 0 00-1.41 0L9 12.59 10.41 14l8.3-8.3a1 1 0 000-1.41z"></path><path d="M18 13l3 3"></path><path d="M3 21l9-9"></path></svg>',
      'print': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7"></path><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>',
      'dry_cleaning': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M12 8v8"></path><path d="M8 12h8"></path></svg>',
      'checkroom': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7v10c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V7l-10-5z"></path><path d="M12 22V12"></path></svg>',
      'construction': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 21l6-6m0 0V9m0 6h6m-6-6l6-6m6 0l6 6m0 0v6m0-6h-6m6 6l-6 6"></path></svg>',
      'local_laundry_service': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7v10c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V7l-10-5z"></path><circle cx="12" cy="13" r="4"></circle></svg>',
      
      // Iconos específicos para áreas
      'Corte': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /><path d="M3 17a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /><path d="M8.6 8.6l10.4 10.4" /><path d="M8.6 15.4l10.4 -10.4" /></svg>',
      'Bordado': '<svg viewBox="0 0 24 24" fill="currentColor"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 2c5.498 0 10 4.002 10 9c0 1.351 -.6 2.64 -1.654 3.576c-1.03 .914 -2.412 1.424 -3.846 1.424h-2.516a1 1 0 0 0 -.5 1.875a1 1 0 0 1 .194 .14a2.3 2.3 0 0 1 -1.597 3.99l-.156 -.009l.068 .004l-.273 -.004c-5.3 -.146 -9.57 -4.416 -9.716 -9.716l-.004 -.28c0 -5.523 4.477 -10 10 -10m-3.5 6.5a2 2 0 0 0 -1.995 1.85l-.005 .15a2 2 0 1 0 2 -2m8 0a2 2 0 0 0 -1.995 1.85l-.005 .15a2 2 0 1 0 2 -2m-4 -3a2 2 0 0 0 -1.995 1.85l-.005 .15a2 2 0 1 0 2 -2" /></svg>',
      'Estampado': '<svg viewBox="0 0 24 24" fill="currentColor"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 2c5.498 0 10 4.002 10 9c0 1.351 -.6 2.64 -1.654 3.576c-1.03 .914 -2.412 1.424 -3.846 1.424h-2.516a1 1 0 0 0 -.5 1.875a1 1 0 0 1 .194 .14a2.3 2.3 0 0 1 -1.597 3.99l-.156 -.009l.068 .004l-.273 -.004c-5.3 -.146 -9.57 -4.416 -9.716 -9.716l-.004 -.28c0 -5.523 4.477 -10 10 -10m-3.5 6.5a2 2 0 0 0 -1.995 1.85l-.005 .15a2 2 0 1 0 2 -2m8 0a2 2 0 0 0 -1.995 1.85l-.005 .15a2 2 0 1 0 2 -2m-4 -3a2 2 0 0 0 -1.995 1.85l-.005 .15a2 2 0 1 0 2 -2" /></svg>',
      'Costura': '<svg viewBox="0 0 24 24" fill="currentColor"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14.883 3.007l.095 -.007l.112 .004l.113 .017l.113 .03l6 2a1 1 0 0 1 .677 .833l.007 .116v5a1 1 0 0 1 -.883 .993l-.117 .007h-2v7a2 2 0 0 1 -1.85 1.995l-.15 .005h-10a2 2 0 0 1 -1.995 -1.85l-.005 -.15v-7h-2a1 1 0 0 1 -.993 -.883l-.007 -.117v-5a1 1 0 0 1 .576 -.906l.108 -.043l6 -2a1 1 0 0 1 1.316 .949a2 2 0 0 0 3.995 .15l.009 -.24l.017 -.113l.037 -.134l.044 -.103l.05 -.092l.068 -.093l.069 -.08c.056 -.054 .113 -.1 .175 -.14l.096 -.053l.103 -.044l.108 -.032l.112 -.02z" /></svg>',
      'Taller': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21h18"/><path d="M5.5 21l-1.5 -6l6 -1"/><path d="M18.5 21l1.5 -6l-6 -1"/><path d="M8 12l4 -4l4 4"/><path d="M12 8v13"/></svg>',
      'Lavandería': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"/><path d="M12 12m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"/><path d="M12 3v6"/></svg>',
      'Control de Calidad': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 12l5 5l10 -10"/></svg>',
      'Despacho': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 2l8 4.5v9l-8 4.5l-8 -4.5v-9z"/><path d="M12 12l8 -4.5"/><path d="M12 12v9"/><path d="M12 12l-8 -4.5"/></svg>',
      'Entrega': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 21l-8 -4.5v-9l8 -4.5l8 4.5v4.5" /><path d="M12 12l8 -4.5" /><path d="M12 12v9" /><path d="M12 12l-8 -4.5" /><path d="M15 18h7" /><path d="M19 15l3 3l-3 3" /></svg>',
      'Insumos': '<svg viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"><line x1="20" y1="140" x2="180" y2="140" /><line x1="20" y1="40" x2="20" y2="140" /><line x1="20" y1="40" x2="60" y2="40" /><circle cx="60" cy="170" r="15" /><circle cx="140" cy="170" r="15" /><rect x="40" y="90" width="50" height="40" /><rect x="55" y="90" width="20" height="10" /><rect x="100" y="90" width="50" height="40" /><rect x="115" y="90" width="20" height="10" /><rect x="75" y="50" width="50" height="40" /><rect x="90" y="50" width="20" height="10" /></svg>'
    };
    
    return icons[iconName] || icons['description'];
  }

  // Mostrar vista de prendas (cerrar modal de seguimiento y volver a prendas)
  function showPrendasView() {
    console.log('[showPrendasView] Cerrando modal de seguimiento y volviendo a prendas...');
    
    // Cerrar el modal de seguimiento
    closeTrackingModal();
    
    // Mostrar el overlay de selección de prendas
    showPrendasSelector();
    
    console.log('[showPrendasView] Modal de seguimiento cerrado y selector de prendas mostrado');
  }

  // Formatear fecha
  function formatDate(dateString) {
    if (window.formatDate && window.formatDate !== formatDate) {
      return window.formatDate(dateString);
    }
    if (!dateString) return null;
    
    try {
      // Si el formato es d/m/Y, convertirlo a Y-m-d para el constructor Date
      if (typeof dateString === 'string' && dateString.includes('/')) {
        const parts = dateString.split('/');
        if (parts.length === 3) {
          const [day, month, year] = parts;
          // Crear fecha en formato Y-m-d
          const isoDate = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
          const date = new Date(isoDate);
          return date.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
          });
        }
      }
      
      // Para formatos estándar (ISO, etc.)
      const date = new Date(dateString);
      return date.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      });
    } catch (error) {
      console.warn('[formatDate] Error formateando fecha:', dateString, error);
      return dateString;
    }
  }

  // Formatear fecha con hora (mostrar fecha + hora completa)
  function formatDateTime(dateString) {
    if (window.formatDateTime && window.formatDateTime !== formatDateTime) {
      return window.formatDateTime(dateString);
    }
    if (!dateString) return null;

    try {
      const raw = (dateString && typeof dateString === 'object' && dateString.date)
        ? dateString.date
        : dateString;

      const date = raw instanceof Date ? raw : new Date(raw);
      if (isNaN(date.getTime())) return String(dateString);

      return date.toLocaleString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
      });
    } catch (error) {
      console.warn('[formatDateTime] Error formateando fecha:', dateString, error);
      return String(dateString);
    }
  }

  // Normalizar consecutivos (puede venir como array o como objeto indexado)
  function normalizeConsecutivos(consecutivos) {
    if (window.normalizeConsecutivos && window.normalizeConsecutivos !== normalizeConsecutivos) {
      return window.normalizeConsecutivos(consecutivos);
    }
    if (!consecutivos) return [];
    if (Array.isArray(consecutivos)) return consecutivos;

    if (typeof consecutivos === 'object') {
      try {
        return Object.values(consecutivos).filter(Boolean);
      } catch (e) {
        return [];
      }
    }

    return [];
  }

  function toDateObject(value) {
    if (window.toDateObject && window.toDateObject !== toDateObject) {
      return window.toDateObject(value);
    }
    if (!value) return null;
    try {
      const raw = (value && typeof value === 'object' && value.date)
        ? value.date
        : value;
      const date = raw instanceof Date ? raw : new Date(raw);
      if (isNaN(date.getTime())) return null;
      return date;
    } catch (e) {
      return null;
    }
  }

  // Cache para festivos por año
  const festivosCache = new Map();

  // Obtener festivos desde la API (con cache)
  async function obtenerFestivos(anio) {
    if (festivosCache.has(anio)) {
      return festivosCache.get(anio);
    }

    try {
      const response = await fetch(`/api/festivos?year=${anio}`);
      if (!response.ok) throw new Error('Error al obtener festivos');
      
      const data = await response.json();
      if (data.success && data.data) {
        festivosCache.set(anio, data.data);
        return data.data;
      }
      
      // Fallback: festivos fijos colombianos si la API falla
      const festivosFijos = [
        `${anio}-01-01`, // Año Nuevo
        `${anio}-05-01`, // Día del Trabajo
        `${anio}-07-01`, // Día de la Independencia
        `${anio}-07-20`, // Grito de Independencia
        `${anio}-08-07`, // Batalla de Boyacá
        `${anio}-12-08`, // Inmaculada Concepción
        `${anio}-12-25`, // Navidad
      ];
      
      festivosCache.set(anio, festivosFijos);
      return festivosFijos;
    } catch (error) {
      console.warn('[obtenerFestivos] Error obteniendo festivos, usando fallback:', error);
      
      // Fallback: festivos fijos colombianos
      const festivosFijos = [
        `${anio}-01-01`, // Año Nuevo
        `${anio}-05-01`, // Día del Trabajo
        `${anio}-07-01`, // Día de la Independencia
        `${anio}-07-20`, // Grito de Independencia
        `${anio}-08-07`, // Batalla de Boyacá
        `${anio}-12-08`, // Inmaculada Concepción
        `${anio}-12-25`, // Navidad
      ];
      
      festivosCache.set(anio, festivosFijos);
      return festivosFijos;
    }
  }

  // Calcular días hábiles entre dos fechas (replicando lógica exacta del backend)
  async function calcularDiasHabiles(fechaInicio, fechaFin) {
    if (!fechaInicio || !fechaFin) return 0;

    const inicio = fechaInicio instanceof Date ? fechaInicio : new Date(fechaInicio);
    const fin = fechaFin instanceof Date ? fechaFin : new Date(fechaFin);

    // Validar fechas
    if (isNaN(inicio.getTime()) || isNaN(fin.getTime())) return 0;
    if (fin < inicio) return 0;
    
    // Si las fechas son iguales, retornar 0 (no cuenta el mismo día)
    if (inicio.toDateString() === fin.toDateString()) return 0;

    try {
      // Obtener festivos del año de inicio
      let festivos = await obtenerFestivos(inicio.getFullYear());
      
      // Agregar festivos del siguiente año si es necesario
      if (fin.getFullYear() > inicio.getFullYear()) {
        const festivosSiguiente = await obtenerFestivos(fin.getFullYear());
        festivos = [...festivos, ...festivosSiguiente];
      }

      let diasHabiles = 0;
      const actual = new Date(inicio);
      
      // Iterar desde la fecha de inicio hasta la fecha fin (inclusive)
      while (actual <= fin) {
        // Verificar si no es sábado (6) ni domingo (0)
        if (actual.getDay() !== 0 && actual.getDay() !== 6) {
          // Verificar si no es festivo
          const fechaStr = actual.toISOString().slice(0, 10);
          if (!festivos.includes(fechaStr)) {
            diasHabiles++;
          }
        }
        
        actual.setDate(actual.getDate() + 1);
      }

      // Restar 1 porque no se cuenta el día de inicio (igual que backend)
      return Math.max(0, diasHabiles - 1);
    } catch (error) {
      console.error('[calcularDiasHabiles] Error:', error);
      // Fallback a cálculo simple sin festivos
      return calcularDiasHabilesSimple(fechaInicio, fechaFin);
    }
  }

  // Versión síncrona para compatibilidad (usa cache o fallback)
  function calcularDiasHabilesSync(fechaInicio, fechaFin) {
    if (!fechaInicio || !fechaFin) return 0;

    const inicio = fechaInicio instanceof Date ? fechaInicio : new Date(fechaInicio);
    const fin = fechaFin instanceof Date ? fechaFin : new Date(fechaFin);

    // Validar fechas
    if (isNaN(inicio.getTime()) || isNaN(fin.getTime())) return 0;
    if (fin < inicio) return 0;
    
    // Si las fechas son iguales, retornar 0 (no cuenta el mismo día)
    if (inicio.toDateString() === fin.toDateString()) return 0;

    // Usar festivos del cache si están disponibles, sino usar fallback
    const anio = inicio.getFullYear();
    let festivos = festivosCache.get(anio);
    
    if (!festivos) {
      // Fallback: festivos fijos colombianos
      festivos = [
        `${anio}-01-01`, // Año Nuevo
        `${anio}-05-01`, // Día del Trabajo
        `${anio}-07-01`, // Día de la Independencia
        `${anio}-07-20`, // Grito de Independencia
        `${anio}-08-07`, // Batalla de Boyacá
        `${anio}-12-08`, // Inmaculada Concepción
        `${anio}-12-25`, // Navidad
      ];
    }

    // Agregar festivos del siguiente año si es necesario
    if (fin.getFullYear() > inicio.getFullYear()) {
      const festivosSiguiente = festivosCache.get(fin.getFullYear()) || [
        `${fin.getFullYear()}-01-01`,
        `${fin.getFullYear()}-05-01`,
        `${fin.getFullYear()}-07-01`,
        `${fin.getFullYear()}-07-20`,
        `${fin.getFullYear()}-08-07`,
        `${fin.getFullYear()}-12-08`,
        `${fin.getFullYear()}-12-25`,
      ];
      festivos = [...festivos, ...festivosSiguiente];
    }

    let diasHabiles = 0;
    const actual = new Date(inicio);
    
    // Iterar desde la fecha de inicio hasta la fecha fin (inclusive)
    while (actual <= fin) {
      // Verificar si no es sábado (6) ni domingo (0)
      if (actual.getDay() !== 0 && actual.getDay() !== 6) {
        // Verificar si no es festivo
        const fechaStr = actual.toISOString().slice(0, 10);
        if (!festivos.includes(fechaStr)) {
          diasHabiles++;
        }
      }
      
      actual.setDate(actual.getDate() + 1);
    }

    // Restar 1 porque no se cuenta el día de inicio (igual que backend)
    return Math.max(0, diasHabiles - 1);
  }

  function formatDurationHuman(diffMs) {
    if (window.formatDurationHuman && window.formatDurationHuman !== formatDurationHuman) {
      return window.formatDurationHuman(diffMs);
    }
    const totalSeconds = Math.floor((diffMs || 0) / 1000);
    const days = Math.floor(totalSeconds / 86400);
    const hours = Math.floor((totalSeconds % 86400) / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    const parts = [];
    if (days > 0) parts.push(`${days} ${days === 1 ? 'día' : 'días'}`);
    if (hours > 0) parts.push(`${hours}h`);
    if (minutes > 0) parts.push(`${minutes}m`);
    if (seconds > 0 || parts.length === 0) parts.push(`${seconds}s`);
    return parts.join(' ');
  }

  // Mostrar error
  function showError(message) {
    console.error('[showError] ' + message);
    // Usar el sistema global de toasts
    if (window.showToast) {
      window.showToast(message, 'error');
    }
  }

  // Manejar agregar proceso
  async function handleAgregarProceso() {
    try {
      // Mostrar indicador de carga
      const btnContent = document.getElementById('addProcesoButtonContent');
      const btnLoading = document.getElementById('addProcesoButtonLoading');
      const btnConfirm = document.getElementById('btnConfirmAddProceso');
      
      if (btnContent && btnLoading && btnConfirm) {
        btnContent.style.display = 'none';
        btnLoading.style.display = 'flex';
        btnConfirm.disabled = true;
      }

      const area = document.getElementById('procesoArea').value;
      const encargado = document.getElementById('procesoEncargado').value.toUpperCase();

      if (!area) {
        showError('Por favor selecciona un área/proceso');
        // Ocultar indicador de carga
        if (btnContent && btnLoading && btnConfirm) {
          btnContent.style.display = 'flex';
          btnLoading.style.display = 'none';
          btnConfirm.disabled = false;
        }
        return;
      }

      // Validar encargado solo para áreas que lo requieren
      const areaLower = area.toLowerCase();
      const needsEncargado = ['corte', 'costura', 'control de calidad'];
      const areaRequiresEncargado = needsEncargado.some(reqArea => areaLower.includes(reqArea));
      
      if (areaRequiresEncargado && !encargado.trim()) {
        showError('Por favor ingresa el nombre del encargado');
        // Ocultar indicador de carga
        if (btnContent && btnLoading && btnConfirm) {
          btnContent.style.display = 'flex';
          btnLoading.style.display = 'none';
          btnConfirm.disabled = false;
        }
        return;
      }

      if (!window.currentPrendaData || !window.currentOrderData) {
        showError('No hay datos de la prenda o pedido');
        // Ocultar indicador de carga
        if (btnContent && btnLoading && btnConfirm) {
          btnContent.style.display = 'flex';
          btnLoading.style.display = 'none';
          btnConfirm.disabled = false;
        }
        return;
      }

      console.log('[handleAgregarProceso] Agregando proceso:', {
        area,
        encargado,
        prenda_id: window.currentPrendaData.id,
        currentOrderData: window.currentOrderData
      });

      // Verificar que los datos necesarios existan
      console.log('[handleAgregarProceso] Verificando estructura de datos:', {
        currentOrderData: window.currentOrderData,
        'currentOrderData.numero_pedido': window.currentOrderData?.numero_pedido,
        'currentOrderData.pedido': window.currentOrderData?.pedido
      });
      
      if (!window.currentOrderData) {
        throw new Error('No hay datos del pedido');
      }
      
      if (!window.currentOrderData.numero_pedido) {
        throw new Error('No hay número de pedido');
      }

      // Enviar datos al backend
      // Estado y fecha_inicio se establecen automáticamente en el backend
      const response = await fetch('/seguimiento-proceso/guardar', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        },
        body: JSON.stringify({
          pedido_produccion_id: window.currentOrderData.numero_pedido,
          prenda_id: window.currentPrendaData.id,
          area: area,
          encargado: encargado,
          estado: 'Pendiente'
        })
      });

      if (!response.ok) {
        // Intentar obtener más información del error
        const errorText = await response.text();
        console.error('[handleAgregarProceso] Error response:', {
          status: response.status,
          statusText: response.statusText,
          body: errorText.substring(0, 500) // Primeros 500 caracteres
        });
        
        // Si es HTML, probablemente es un error de Laravel
        if (errorText.includes('<!DOCTYPE html>') || errorText.includes('<html')) {
          throw new Error('Error del servidor. Posiblemente un error de validación o permisos.');
        }
        
        throw new Error(`Error ${response.status}: ${response.statusText}`);
      }

      // Verificar que la respuesta sea JSON antes de parsear
      const contentType = response.headers.get('content-type');
      if (!contentType || !contentType.includes('application/json')) {
        const responseText = await response.text();
        console.error('[handleAgregarProceso] Respuesta no es JSON:', {
          contentType: contentType,
          body: responseText.substring(0, 500)
        });
        throw new Error('El servidor devolvió una respuesta inesperada. Contacte al administrador.');
      }

      const result = await response.json();
      console.log('[handleAgregarProceso] Proceso guardado:', result);

      // Limpiar formulario
      limpiarFormularioProceso();

      // Cerrar modal de agregar proceso
      const modal = document.getElementById('addProcesoModal');
      if (modal) {
        modal.classList.remove('show');
        modal.style.display = 'none';
      }

      // Actualizar datos de la prenda con la respuesta del backend
      if (result.data && result.data.prenda) {
        currentPrendaData = result.data.prenda;
        console.log('[handleAgregarProceso] Prenda actualizada desde backend:', currentPrendaData);
        
        // Renderizar timeline con los datos actualizados
        renderPrendaTrackingTimeline(currentPrendaData);
      } else {
        // Si no vienen datos de la prenda, recargar desde el endpoint
        console.log('[handleAgregarProceso] Recargando datos desde endpoint...');
        await loadPrendasWithTracking(currentOrderData.id);
        
        // Buscar la prenda actualizada en los datos cargados
        if (window.prendasData && window.prendasData.length > 0) {
          const prendaActualizada = window.prendasData.find(p => p.id == currentPrendaData.id);
          if (prendaActualizada) {
            currentPrendaData = prendaActualizada;
            renderPrendaTrackingTimeline(currentPrendaData);
          }
        }
      }

      // ✅ Mostrar mensaje diferente según si fue creado o actualizado
      const mensaje = result.action === 'actualizado' 
        ? 'Proceso actualizado correctamente' 
        : 'Proceso agregado correctamente';
      showSuccess(mensaje);

      // Actualizar la fila en la tabla de recibos-costura si estamos en esa página
      await actualizarAreaEnTablaRecibos();

    } catch (error) {
      console.error('[handleAgregarProceso] Error:', error);
      
      // Manejar específicamente errores de JSON
      if (error instanceof SyntaxError && error.message.includes('JSON')) {
        console.error('[handleAgregarProceso] Error de JSON - el servidor devolvió HTML en lugar de JSON');
        showError('Error del servidor: La respuesta no es válida. Posiblemente un error de permisos o validación.');
      } else {
        showError('Error al agregar proceso: ' + error.message);
      }
    } finally {
      // Ocultar indicador de carga
      const btnContent = document.getElementById('addProcesoButtonContent');
      const btnLoading = document.getElementById('addProcesoButtonLoading');
      const btnConfirm = document.getElementById('btnConfirmAddProceso');
      
      if (btnContent && btnLoading && btnConfirm) {
        btnContent.style.display = 'flex';
        btnLoading.style.display = 'none';
        btnConfirm.disabled = false;
      }
    }
  }

  // Limpiar formulario de proceso
  function limpiarFormularioProceso() {
    document.getElementById('procesoArea').value = '';
    document.getElementById('procesoEncargado').value = '';
  }

  // Mostrar mensaje de éxito
  function showSuccess(message) {
    // Usar el sistema global de toasts
    if (window.showToast) {
      window.showToast(message, 'success');
    }
  }

  // Inicializar cuando el DOM esté listo
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTrackingModalListeners);
  } else {
    initTrackingModalListeners();
  }
