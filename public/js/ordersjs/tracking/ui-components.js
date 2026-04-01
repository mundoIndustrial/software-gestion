'use strict';

// Componentes UI para el sistema de tracking
class TrackingUIComponents {
  constructor() {
    this.contadorTimer = null;
    this.init();
  }

  init() {
    // Inicializar contadores dinámicos si es necesario
  }

  // Iniciar timer para actualización automática de contadores
  iniciarTimerContadores() {
    // Detener timer existente
    if (this.contadorTimer) {
      clearInterval(this.contadorTimer);
    }
    
    // Actualizar inmediatamente
    this.actualizarContadoresDinamicos();
    
    // Configurar timer para actualizar cada día a medianoche
    const ahora = new Date();
    const manana = new Date(ahora);
    manana.setDate(manana.getDate() + 1);
    manana.setHours(0, 0, 0, 0);
    
    const msHastaManana = manana.getTime() - ahora.getTime();
    
    // Primer actualización a medianoche
    setTimeout(() => {
      this.actualizarContadoresDinamicos();
      
      // Luego actualizar cada 24 horas
      this.contadorTimer = setInterval(() => this.actualizarContadoresDinamicos(), 24 * 60 * 60 * 1000);
    }, msHastaManana);
    
    console.log('[iniciarTimerContadores] Timer configurado para actualizar diariamente');
  }

  // Detener timer de contadores
  detenerTimerContadores() {
    if (this.contadorTimer) {
      clearInterval(this.contadorTimer);
      this.contadorTimer = null;
      console.log('[detenerTimerContadores] Timer detenido');
    }
  }

  // Actualizar contadores de días dinámicos (procesos sin fecha fin)
  actualizarContadoresDinamicos() {
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
        const processData = globalThis.currentPrendaData?.seguimientos_por_area?.[area];
        if (!processData) return;
        
        // Recalcular días dinámicamente
        const ini = typeof toDateObject === 'function' ? toDateObject(processData.fecha_inicio) : null;
        if (!ini) return;
        
        // Si no hay fecha fin/completado, contar hasta hoy
        if (!processData.fecha_fin && !processData.fecha_completado) {
          const diasHabiles = typeof calcularDiasHabilesSync === 'function' 
            ? calcularDiasHabilesSync(ini, new Date())
            : 0;
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

  // Actualizar información del pedido en el modal y selector
  updateOrderInfo(orderData) {
    console.log('[updateOrderInfo] Datos recibidos:', orderData);
    console.log('[updateOrderInfo] numero_pedido:', orderData.numero_pedido);
    console.log('[updateOrderInfo] cliente:', orderData.cliente);
    console.log('[updateOrderInfo] estado:', orderData.estado);
    
    // Actualizar modal principal
    console.log('[updateOrderInfo] Campos de fecha disponibles:', {
      fecha_creacion: orderData.fecha_creacion,
      created_at: orderData.created_at,
      created_at: orderData.created_at,
      fecha_estimada_de_entrega: orderData.fecha_estimada_de_entrega
    });
    
    const trackingOrderNumber = document.getElementById('trackingOrderNumber');
    const trackingOrderClient = document.getElementById('trackingOrderClient');
    const trackingOrderStatus = document.getElementById('trackingOrderStatus');
    const trackingEstimatedDate = document.getElementById('trackingEstimatedDate');
    const trackingTotalDaysEl = document.getElementById('trackingTotalDays');
    const trackingOrderRecibo = document.getElementById('trackingOrderRecibo');
    
    if (trackingOrderNumber) {
      trackingOrderNumber.textContent = orderData.numero_pedido || '-';
    }
    if (trackingOrderClient) {
      trackingOrderClient.textContent = orderData.cliente || '-';
    }
    if (trackingOrderStatus) {
      trackingOrderStatus.textContent = (orderData.estado || '-').replace(/_/g, ' ').toUpperCase();
    }
    if (trackingEstimatedDate) {
      trackingEstimatedDate.textContent = typeof formatDate === 'function' 
        ? formatDate(orderData.fecha_estimada_de_entrega) || '-'
        : '-';
    }
    if (trackingTotalDaysEl) {
      trackingTotalDaysEl.textContent = orderData.total_dias || '0';
    }

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
      let fechaInicio = orderData.fecha_creacion || orderData.created_at || orderData.created_at;
      
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
      
      selectorOrderEstimatedDate.style.color = '#1f2937';
      selectorOrderEstimatedDate.style.fontWeight = '600';
    } else {
      if (selectorOrderEstimatedDate) {
        selectorOrderEstimatedDate.textContent = 'No definida';
        selectorOrderEstimatedDate.style.color = '#9ca3af';
        selectorOrderEstimatedDate.style.fontWeight = '400';
      }
    }

    // Actualizar selector de días si dia_de_entrega existe
    if (orderData.dia_de_entrega) {
      console.log('[updateOrderInfo] Dias de entrega encontrados:', orderData.dia_de_entrega);
      
      // Usar la función de reintentos si existe
      if (typeof globalThis.updateDaysSelectorWithRetry === 'function') {
        globalThis.updateDaysSelectorWithRetry(orderData.dia_de_entrega);
      } else {
        console.warn('[updateOrderInfo] updateDaysSelectorWithRetry no disponible');
      }
    } else {
      console.log('[updateOrderInfo] Sin dia_de_entrega en orderData');
    }
    
    // Actualizar recibo principal
    if (trackingOrderRecibo) {
      // Buscar el recibo principal según el tipo de recibo que se está visualizando
      let ultimoRecibo = '-';
      
      console.log('[updateOrderInfo] Buscando recibo principal en orderData:', {
        prendas_count: orderData.prendas ? orderData.prendas.length : 0,
        prendas: orderData.prendas
      });
      
      // Si tenemos datos de prendas con consecutivos, buscar el recibo principal
      if (orderData.prendas && orderData.prendas.length > 0) {
        let reciboPrincipalEncontrado = null;
        let totalRecibosEncontrados = 0;
        
        // Buscar entre todas las prendas el recibo principal
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
            
            // Orden de prioridad: COSTURA > REFLECTIVO > ESTAMPADO > BORDADO > otros
            const prioridadRecibos = ['COSTURA', 'REFLECTIVO', 'ESTAMPADO', 'BORDADO', 'DTF', 'SUBLIMADO'];
            
            for (const prioridad of prioridadRecibos) {
              for (const recibo of recibosArray) {
                console.log('[updateOrderInfo] Analizando recibo:', recibo);
                totalRecibosEncontrados++;
                
                // Buscar recibo activo del tipo actual según prioridad
                if (recibo.activo === 1 && recibo.tipo_recibo === prioridad) {
                  reciboPrincipalEncontrado = recibo;
                  console.log('[updateOrderInfo] Recibo principal encontrado:', reciboPrincipalEncontrado);
                  break; // Encontramos el de esta prioridad
                }
              }
              
              // Si ya encontramos un recibo de esta prioridad, salir del bucle de prioridad
              if (reciboPrincipalEncontrado) {
                break;
              }
            }
          }
          
          // Si ya encontramos un recibo principal, salir del bucle de prendas
          if (reciboPrincipalEncontrado) {
            break;
          }
        }
        
        console.log('[updateOrderInfo] Resumen de búsqueda:', {
          total_recibos_encontrados: totalRecibosEncontrados,
          recibo_principal_encontrado: reciboPrincipalEncontrado
        });
        
        if (reciboPrincipalEncontrado) {
          ultimoRecibo = `${reciboPrincipalEncontrado.tipo_recibo} #${reciboPrincipalEncontrado.consecutivo_actual}`;
        }
      }
      
      console.log('[updateOrderInfo] Resultado final para trackingOrderRecibo:', ultimoRecibo);
      trackingOrderRecibo.textContent = ultimoRecibo;
    }
  }

  // Actualizar fecha estimada de entrega del pedido
  updateEstimatedDeliveryDate() {
    const fechaEstimadaElement = document.getElementById('selectorOrderEstimatedDate');
    if (!fechaEstimadaElement || !globalThis.currentOrderData) return;
    
    // Obtener fecha estimada del pedido (campo correcto)
    let fechaEstimada = globalThis.currentOrderData.fecha_estimada_de_entrega;
    
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

  // Mostrar selector de prendas (overlay)
  showPrendasSelector() {
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
  cerrarSelectorPrendas() {
    const overlay = document.getElementById('trackingPrendasSelectorOverlay');
    if (overlay) {
      overlay.classList.remove('show');
      overlay.style.display = 'none';
    }
  }

  // Mostrar vista de prendas (cerrar modal de seguimiento y volver a prendas)
  showPrendasView() {
    console.log('[showPrendasView] Cerrando modal de seguimiento y volviendo a prendas...');
    
    // Cerrar el modal de seguimiento
    if (typeof closeTrackingModal === 'function') {
      closeTrackingModal();
    }
    
    // Mostrar el overlay de selección de prendas
    this.showPrendasSelector();
    
    console.log('[showPrendasView] Modal de seguimiento cerrado y selector de prendas mostrado');
  }

  // Mostrar error
  showError(message) {
    console.error('[showError] ' + message);
    // Usar el sistema global de toasts
    if (globalThis.showToast) {
      globalThis.showToast(message, 'error');
    }
  }

  // Mostrar mensaje de éxito
  showSuccess(message) {
    // Usar el sistema global de toasts
    if (globalThis.showToast) {
      globalThis.showToast(message, 'success');
    }
  }
}

// Exportar para uso global
globalThis.TrackingUIComponents = TrackingUIComponents;
globalThis.trackingUIComponents = new TrackingUIComponents();

// Funciones globales para compatibilidad
globalThis.iniciarTimerContadores = () => globalThis.trackingUIComponents.iniciarTimerContadores();
globalThis.detenerTimerContadores = () => globalThis.trackingUIComponents.detenerTimerContadores();
globalThis.actualizarContadoresDinamicos = () => globalThis.trackingUIComponents.actualizarContadoresDinamicos();
globalThis.updateOrderInfo = (orderData) => globalThis.trackingUIComponents.updateOrderInfo(orderData);
globalThis.updateEstimatedDeliveryDate = () => globalThis.trackingUIComponents.updateEstimatedDeliveryDate();
globalThis.showPrendasSelector = () => globalThis.trackingUIComponents.showPrendasSelector();
globalThis.cerrarSelectorPrendas = () => globalThis.trackingUIComponents.cerrarSelectorPrendas();
globalThis.showPrendasView = () => globalThis.trackingUIComponents.showPrendasView();
globalThis.showError = (message) => globalThis.trackingUIComponents.showError(message);
globalThis.showSuccess = (message) => globalThis.trackingUIComponents.showSuccess(message);
