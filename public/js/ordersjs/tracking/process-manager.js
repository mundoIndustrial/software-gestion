'use strict';

// Protección contra redeclaraciones si el script se carga múltiples veces
if (typeof ProcessManager !== 'undefined') {
  console.warn('[process-manager.js] ProcessManager ya fue declarada, omitiendo redeclaración');
} else {
  // Gestión de procesos del sistema de tracking
  class ProcessManager {
  constructor() {
    this.init();
  }

  init() {
    this.setupEncargadoDynamicSelector();
  }

  // Configurar el selector dinámico de encargado
  setupEncargadoDynamicSelector() {
    const procesoArea = document.getElementById('procesoArea');
    if (!procesoArea) return;

    procesoArea.addEventListener('change', async (e) => {
      const area = e.target.value.toLowerCase().trim();
      
      // Buscar el contenedor de encargado de forma más robusta
      const formGroups = document.querySelectorAll('.add-proceso-form-group');
      let procesoEncargadoContainer = null;
      
      formGroups.forEach(group => {
        const label = group.querySelector('label');
        if (label && label.textContent.includes('Encargado')) {
          procesoEncargadoContainer = group;
        }
      });
      
      if (!procesoEncargadoContainer) {
        console.warn('[setupEncargadoDynamicSelector] No se encontró el contenedor de encargado');
        return;
      }
      
      console.log('[setupEncargadoDynamicSelector] Área seleccionada:', area);

      // Áreas que requieren selector dinámico
      if (area === 'corte' || area === 'costura') {
        console.log('[setupEncargadoDynamicSelector] Convertir a selector para:', area);
        await this.convertEncargadoToSelect(area, procesoEncargadoContainer);
      } else {
        console.log('[setupEncargadoDynamicSelector] Convertir a input para:', area);
        this.convertEncargadoToInput(procesoEncargadoContainer);
      }
    });
  }

  // Convertir campo de encargado a SELECT
  async convertEncargadoToSelect(area, container) {
    // Primero, remover cualquier input o select anterior
    const existingInput = document.getElementById('procesoEncargado');
    const existingSelect = document.getElementById('procesoEncargadoSelect');
    
    if (existingInput) {
      existingInput.remove();
    }
    if (existingSelect) {
      existingSelect.remove();
    }

    // Crear nuevo select
    const select = document.createElement('select');
    select.id = 'procesoEncargadoSelect';
    select.className = 'add-proceso-select';
    select.innerHTML = '<option value="">Seleccionar encargado...</option>';

    container.appendChild(select);

    try {
      console.log('[convertEncargadoToSelect] Cargando usuarios para:', area);
      
      // Cargar usuarios desde API
      const response = await fetch(`/api/usuarios/por-area?area=${encodeURIComponent(area)}`);
      const data = await response.json();

      if (data.success && data.usuarios && data.usuarios.length > 0) {
        data.usuarios.forEach(usuario => {
          const option = document.createElement('option');
          option.value = usuario.id;
          option.textContent = usuario.name;
          select.appendChild(option);
        });
        console.log('[convertEncargadoToSelect] ✓ Usuarios cargados:', data.usuarios.length);
      } else {
        console.warn('[convertEncargadoToSelect] No hay usuarios disponibles para:', area);
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'No hay usuarios disponibles';
        option.disabled = true;
        select.appendChild(option);
      }
    } catch (error) {
      console.error('[convertEncargadoToSelect] Error al cargar usuarios:', error);
      const option = document.createElement('option');
      option.value = '';
      option.textContent = 'Error al cargar usuarios';
      option.disabled = true;
      select.appendChild(option);
    }
  }

  // Convertir campo de encargado a INPUT
  convertEncargadoToInput(container) {
    // Primero, remover cualquier input o select anterior
    const existingInput = document.getElementById('procesoEncargado');
    const existingSelect = document.getElementById('procesoEncargadoSelect');
    
    if (existingInput) {
      existingInput.remove();
    }
    if (existingSelect) {
      existingSelect.remove();
    }

    // Crear nuevo input
    const input = document.createElement('input');
    input.type = 'text';
    input.id = 'procesoEncargado';
    input.className = 'add-proceso-input';
    input.placeholder = 'Nombre del encargado';
    input.style.textTransform = 'uppercase';
    container.appendChild(input);
    
    console.log('[convertEncargadoToInput] Input de texto creado');
  }

  // Permite editar el área actual incluso si no existe proceso (creación rápida con prefill)
  handleCrearProcesoDesdeArea(areaName, event, encargadoPrefill = '') {
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
  }

  // Manejar eliminación de proceso
  handleEliminarProceso(procesoId, areaName, event) {
    // Detener propagación para evitar que se cierre el modal
    if (event) {
      event.stopPropagation();
    }
    
    // Mostrar modal de confirmación
    if (typeof showConfirmDeleteModal === 'function') {
      showConfirmDeleteModal(procesoId, areaName);
    }
  }

  // Ejecutar la eliminación del proceso
  async executeDeleteProcess() {
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
      if (typeof closeConfirmDeleteModal === 'function') {
        closeConfirmDeleteModal();
      }

      // Recargar seguimientos de la prenda
      console.log('[executeDeleteProcess] Recargando seguimientos para orden:', window.currentOrderData.id);
      if (typeof loadPrendasWithTracking === 'function') {
        await loadPrendasWithTracking(window.currentOrderData.id);
      }

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
        if (typeof renderPrendaTrackingTimeline === 'function') {
          renderPrendaTrackingTimeline(window.currentPrendaData);
        }
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
            if (typeof renderPrendaTrackingTimeline === 'function') {
              renderPrendaTrackingTimeline(prendaParaRender);
            }
          } else {
            // Fallback: crear objeto con el ID
            const prendaData = {
              id: prendaId,
              nombre_prenda: firstCard.querySelector('.prenda-name')?.textContent,
            };
            console.log('[executeDeleteProcess] Usando prendaData del DOM:', prendaData);
            if (typeof renderPrendaTrackingTimeline === 'function') {
              renderPrendaTrackingTimeline(prendaData);
            }
          }
        }
      }

      // Mostrar mensaje de éxito
      if (typeof showSuccess === 'function') {
        showSuccess('Proceso eliminado correctamente');
      }
      
      // Actualizar el área en la tabla de recibos-costura si estamos en esa página
      if (typeof actualizarAreaEnTablaRecibos === 'function') {
        await actualizarAreaEnTablaRecibos();
      }

    } catch (error) {
      console.error('[executeDeleteProcess] Error:', error);
      if (typeof showError === 'function') {
        showError('Error al eliminar proceso: ' + error.message);
      }
      if (typeof closeConfirmDeleteModal === 'function') {
        closeConfirmDeleteModal();
      }
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

  // Manejar edición de proceso
  handleEditarProceso(procesoId, areaName, processData, event) {
    // Detener propagación para evitar que se cierre el modal
    if (event) {
      event.stopPropagation();
    }
    
    console.log('[handleEditarProceso] Editando proceso:', { procesoId, areaName, processData });
    
    // Abrir el modal primero
    if (typeof openAddProcesoModal === 'function') {
      openAddProcesoModal();
    }
    
    // Verificar si los elementos del formulario existen
    const procesoArea = document.getElementById('procesoArea');
    const procesoEstado = document.getElementById('procesoEstado');
    const procesoFechaInicio = document.getElementById('procesoFechaInicio');
    const procesoObservaciones = document.getElementById('procesoObservaciones');
    
    console.log('[handleEditarProceso] Elementos del formulario:', {
      procesoArea: !!procesoArea,
      procesoEstado: !!procesoEstado,
      procesoFechaInicio: !!procesoFechaInicio,
      procesoObservaciones: !!procesoObservaciones
    });
    
    // Llenar el área
    if (procesoArea) {
      procesoArea.value = processData.area || areaName;
      
      // Disparar evento change para que se cree el selector dinámico si es necesario
      const changeEvent = new Event('change', { bubbles: true });
      procesoArea.dispatchEvent(changeEvent);
    }
    
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
    
    if (procesoObservaciones) procesoObservaciones.value = processData.observaciones || '';
    
    // Esperar a que el selector se haya creado (si es necesario) antes de establecer el encargado
    setTimeout(() => {
      const inputEncargado = document.getElementById('procesoEncargado');
      const selectEncargado = document.getElementById('procesoEncargadoSelect');
      
      if (selectEncargado && selectEncargado.offsetParent !== null) {
        // Es un select - establecer el valor si existe una opción con ese nombre
        const options = selectEncargado.options;
        const encargadoValue = processData.encargado || '';
        
        for (let i = 0; i < options.length; i++) {
          if (options[i].text.toLowerCase() === encargadoValue.toLowerCase()) {
            selectEncargado.value = options[i].value;
            break;
          }
        }
        console.log('[handleEditarProceso] Encargado seleccionado en select:', encargadoValue);
      } else if (inputEncargado) {
        // Es un input - establecer el valor directamente
        inputEncargado.value = processData.encargado || '';
        console.log('[handleEditarProceso] Encargado establecido en input:', processData.encargado);
      }
    }, 150);
    
    // Guardar el ID del proceso que se está editando
    window.editingProcessId = procesoId;
    
    // Cambiar el texto del botón a "Actualizar"
    const btnConfirmar = document.getElementById('btnConfirmAddProceso');
    if (btnConfirmar) {
      btnConfirmar.textContent = 'Actualizar Proceso';
      btnConfirmar.onclick = () => this.handleActualizarProceso(procesoId);
    }
    
    console.log('[handleEditarProceso] Modal de agregar/editar proceso abierto');
  }

  // Manejar actualización de proceso
  async handleActualizarProceso(procesoId) {
    try {
      const procesoAreaEl = document.getElementById('procesoArea');
      const procesoEstadoEl = document.getElementById('procesoEstado');
      const procesoFechaInicioEl = document.getElementById('procesoFechaInicio');
      const procesoObservacionesEl = document.getElementById('procesoObservaciones');

      // Buscar encargado - puede ser un input o un select
      const inputEncargado = document.getElementById('procesoEncargado');
      const selectEncargado = document.getElementById('procesoEncargadoSelect');
      
      let encargado = '';
      if (selectEncargado && selectEncargado.offsetParent !== null) {
        // Es un select - obtener el texto del option seleccionado
        const selectedOption = selectEncargado.options[selectEncargado.selectedIndex];
        encargado = selectedOption ? selectedOption.text : '';
      } else if (inputEncargado) {
        // Es un input - obtener el valor
        encargado = inputEncargado.value;
      }

      if (!procesoAreaEl) {
        throw new Error('No se encontró el campo de área. Por favor recarga la página.');
      }

      const area = procesoAreaEl.value;
      const estado = procesoEstadoEl ? procesoEstadoEl.value : 'Pendiente';
      const fechaInicio = procesoFechaInicioEl ? procesoFechaInicioEl.value : '';
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
      if (typeof limpiarFormularioProceso === 'function') {
        limpiarFormularioProceso();
      }
      if (typeof resetFormButton === 'function') {
        resetFormButton();
      }

      // Cerrar modal de agregar/editar proceso
      try {
        if (typeof closeAddProcesoModal === 'function') {
          closeAddProcesoModal();
        }
      } catch (e) {
        console.warn('[handleActualizarProceso] No se pudo cerrar addProcesoModal:', e);
      }

      // Recargar seguimientos de la prenda
      const orderId = window.currentOrderData?.id;
      if (orderId && typeof loadPrendasWithTracking === 'function') {
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

      if (window.currentPrendaData && typeof renderPrendaTrackingTimeline === 'function') {
        renderPrendaTrackingTimeline(window.currentPrendaData);
      }

      // Mostrar mensaje de éxito
      if (typeof showSuccess === 'function') {
        showSuccess('Proceso actualizado correctamente');
      }

      // Actualizar la fila en la tabla de recibos-costura si estamos en esa página
      if (typeof actualizarAreaEnTablaRecibos === 'function') {
        await actualizarAreaEnTablaRecibos();
      }

    } catch (error) {
      console.error('[handleActualizarProceso] Error:', error);
      if (typeof showError === 'function') {
        showError('Error al actualizar proceso: ' + error.message);
      }
    }
  }

  // Limpiar formulario de proceso
  limpiarFormularioProceso() {
    const procesoArea = document.getElementById('procesoArea');
    if (procesoArea) procesoArea.value = '';

    const procesoEncargado = document.getElementById('procesoEncargado');
    if (procesoEncargado) procesoEncargado.value = '';

    const procesoEncargadoSelect = document.getElementById('procesoEncargadoSelect');
    if (procesoEncargadoSelect) procesoEncargadoSelect.value = '';

    const procesoEstado = document.getElementById('procesoEstado');
    if (procesoEstado) procesoEstado.value = 'Pendiente';

    const procesoFechaInicio = document.getElementById('procesoFechaInicio');
    if (procesoFechaInicio) procesoFechaInicio.value = '';

    const procesoObservaciones = document.getElementById('procesoObservaciones');
    if (procesoObservaciones) procesoObservaciones.value = '';
  }

  // Resetear botón del formulario a su estado original
  resetFormButton() {
    const btnConfirmar = document.getElementById('btnConfirmAddProceso');
    if (btnConfirmar) {
      btnConfirmar.textContent = 'Agregar Proceso';
      btnConfirmar.onclick = () => this.handleAgregarProceso();
    }
    window.editingProcessId = null;
  }

  // Manejar agregar proceso
  async handleAgregarProceso() {
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
        if (typeof showError === 'function') {
          showError('Por favor selecciona un área/proceso');
        }
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
        if (typeof showError === 'function') {
          showError('Por favor ingresa el nombre del encargado');
        }
        // Ocultar indicador de carga
        if (btnContent && btnLoading && btnConfirm) {
          btnContent.style.display = 'flex';
          btnLoading.style.display = 'none';
          btnConfirm.disabled = false;
        }
        return;
      }

      if (!window.currentPrendaData || !window.currentOrderData) {
        if (typeof showError === 'function') {
          showError('No hay datos de la prenda o pedido');
        }
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
      this.limpiarFormularioProceso();

      // Cerrar modal de agregar proceso
      const modal = document.getElementById('addProcesoModal');
      if (modal) {
        modal.classList.remove('show');
        modal.style.display = 'none';
      }

      // Actualizar datos de la prenda con la respuesta del backend
      if (result.data && result.data.prenda) {
        window.currentPrendaData = result.data.prenda;
        console.log('[handleAgregarProceso] Prenda actualizada desde backend:', window.currentPrendaData);
        
        // Renderizar timeline con los datos actualizados
        if (typeof renderPrendaTrackingTimeline === 'function') {
          renderPrendaTrackingTimeline(window.currentPrendaData);
        }
      } else {
        // Si no vienen datos de la prenda, recargar desde el endpoint
        console.log('[handleAgregarProceso] Recargando datos desde endpoint...');
        if (typeof loadPrendasWithTracking === 'function') {
          await loadPrendasWithTracking(window.currentOrderData.id);
        }
        
        // Buscar la prenda actualizada en los datos cargados
        if (window.prendasData && window.prendasData.length > 0) {
          const prendaActualizada = window.prendasData.find(p => p.id == window.currentPrendaData.id);
          if (prendaActualizada) {
            window.currentPrendaData = prendaActualizada;
            if (typeof renderPrendaTrackingTimeline === 'function') {
              renderPrendaTrackingTimeline(window.currentPrendaData);
            }
          }
        }
      }

      //  Mostrar mensaje diferente según si fue creado o actualizado
      const mensaje = result.action === 'actualizado' 
        ? 'Proceso actualizado correctamente' 
        : 'Proceso agregado correctamente';
      if (typeof showSuccess === 'function') {
        showSuccess(mensaje);
      }

      // Actualizar la fila en la tabla de recibos-costura si estamos en esa página
      if (typeof actualizarAreaEnTablaRecibos === 'function') {
        await actualizarAreaEnTablaRecibos();
      }

    } catch (error) {
      console.error('[handleAgregarProceso] Error:', error);
      
      // Manejar específicamente errores de JSON
      if (error instanceof SyntaxError && error.message.includes('JSON')) {
        console.error('[handleAgregarProceso] Error de JSON - el servidor devolvió HTML en lugar de JSON');
        if (typeof showError === 'function') {
          showError('Error del servidor: La respuesta no es válida. Posiblemente un error de permisos o validación.');
        }
      } else {
        if (typeof showError === 'function') {
          showError('Error al agregar proceso: ' + error.message);
        }
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
}

// Exportar para uso global
window.ProcessManager = ProcessManager;
window.processManager = new ProcessManager();

// Funciones globales para compatibilidad
window.handleCrearProcesoDesdeArea = (areaName, event, encargadoPrefill) => window.processManager.handleCrearProcesoDesdeArea(areaName, event, encargadoPrefill);
window.handleEliminarProceso = (procesoId, areaName, event) => window.processManager.handleEliminarProceso(procesoId, areaName, event);
window.executeDeleteProcess = () => window.processManager.executeDeleteProcess();
window.handleEditarProceso = (procesoId, areaName, processData, event) => window.processManager.handleEditarProceso(procesoId, areaName, processData, event);
window.handleActualizarProceso = (procesoId) => window.processManager.handleActualizarProceso(procesoId);
window.limpiarFormularioProceso = () => window.processManager.limpiarFormularioProceso();
window.resetFormButton = () => window.processManager.resetFormButton();
window.handleAgregarProceso = () => window.processManager.handleAgregarProceso();

} // Cierre del else - protección contra redeclaraciones
