/**
 * Tracking Modal - Selector dinámico de Encargado por Área
 * Maneja el cambio del campo Encargado entre select (Costura/Corte) y datalist (otros)
 */

(function() {
  'use strict';

  // Rastrear si el formulario está siendo procesado
  let isProcessing = false;

  // Inicializar el selector de encargado cuando el DOM esté listo
  document.addEventListener('DOMContentLoaded', initializeAreaSelector);

  function initializeAreaSelector() {
    const procesoAreaSelect = document.getElementById('procesoArea');
    if (!procesoAreaSelect) {
      console.log('[initializeAreaSelector] Campo procesoArea no encontrado aún');
      // Reintentar en 500ms
      setTimeout(initializeAreaSelector, 500);
      return;
    }

    console.log('[initializeAreaSelector] Inicializando selector de encargado por área');

    // Agregar evento de cambio al selector de área
    procesoAreaSelect.addEventListener('change', handleAreaChange);

    // Inicializar en la carga si hay valor
    handleAreaChange();
  }

  async function handleAreaChange() {
    const procesoAreaSelect = document.getElementById('procesoArea');
    const area = procesoAreaSelect?.value || '';

    console.log('[handleAreaChange] Área seleccionada:', area);

    // Elementos del formulario
    const inputDatalist = document.querySelector('input[data-input-type="datalist"]');
    const selectDropdown = document.getElementById('procesoEncargadoSelect');
    const datalist = document.getElementById('encargadoList');

    if (!inputDatalist || !selectDropdown || !datalist) {
      console.error('[handleAreaChange] No se encontraron elementos del formulario');
      return;
    }

    // Definir qué áreas usan select y cuáles usan datalist
    const selectAreas = ['Costura', 'Corte'];

    if (selectAreas.includes(area)) {
      // Mostrar select, ocultar datalist
      inputDatalist.style.display = 'none';
      selectDropdown.style.display = 'block';

      // Cargar usuarios correspondientes
      await loadUsuariosParaArea(area, selectDropdown, datalist);
    } else {
      // Mostrar datalist, ocultar select
      inputDatalist.style.display = 'block';
      selectDropdown.style.display = 'none';

      // Limpiar select pero mantenerlo por si cambian de área
      selectDropdown.innerHTML = '<option value="">Seleccionar encargado...</option>';

      // Cargar datalist con usuarios genéricos si es necesario
      if (area && area.trim() !== '') {
        await loadUsuariosEnDatalist(area, datalist);
      } else {
        // Si no hay área, limpiar datalist
        datalist.innerHTML = '';
      }
    }
  }

  async function loadUsuariosParaArea(area, selectElement, datalistElement) {
    try {
      let endpoint = '';

      if (area === 'Costura') {
        endpoint = '/api/usuarios/costura';
      } else if (area === 'Corte') {
        endpoint = '/api/usuarios/corte';
      }

      if (!endpoint) {
        console.log('[loadUsuariosParaArea] Área no requiere carga de usuarios');
        return;
      }

      console.log('[loadUsuariosParaArea] Cargando usuarios de:', endpoint);

      const response = await fetch(endpoint);
      if (!response.ok) {
        throw new Error(`Error al cargar usuarios: ${response.status}`);
      }

      const data = await response.json();
      console.log('[loadUsuariosParaArea] Usuarios cargados:', data);

      if (!data.success || !Array.isArray(data.usuarios)) {
        console.warn('[loadUsuariosParaArea] No se encontraron usuarios para:', area);
        selectElement.innerHTML = '<option value="">No hay usuarios disponibles</option>';
        return;
      }

      // Llenar el select
      selectElement.innerHTML = '<option value="">Seleccionar encargado...</option>';
      data.usuarios.forEach(usuario => {
        const option = document.createElement('option');
        option.value = usuario.name;
        option.textContent = usuario.name.toUpperCase();
        selectElement.appendChild(option);
      });

      console.log(`[loadUsuariosParaArea] Select poblado con ${data.usuarios.length} usuarios`);
    } catch (error) {
      console.error('[loadUsuariosParaArea] Error:', error);
      selectElement.innerHTML = '<option value="">Error al cargar usuarios</option>';
    }
  }

  async function loadUsuariosEnDatalist(area, datalistElement) {
    try {
      // Para el datalist, podemos intentar cargar usuarios genéricos
      // o simplemente dejar que el usuario escriba libremente
      console.log('[loadUsuariosEnDatalist] Datalist preparado para área:', area);

      // Limpiar datalist actual
      datalistElement.innerHTML = '';

      // Para áreas genéricas, podríamos cargar sugerencias pero por ahora
      // dejaremos que sea un campo libre de escritura
      // Si en futuro quieres agregar sugerencias, aquí es el lugar
    } catch (error) {
      console.error('[loadUsuariosEnDatalist] Error:', error);
    }
  }

  // Función para obtener el valor del encargado independientemente del tipo de input
  window.getEncargadoValue = function() {
    const inputDatalist = document.querySelector('#procesoEncargado');
    const selectDropdown = document.getElementById('procesoEncargadoSelect');

    if (inputDatalist && inputDatalist.style.display !== 'none') {
      return inputDatalist.value;
    } else if (selectDropdown && selectDropdown.style.display !== 'none') {
      return selectDropdown.value;
    }

    return '';
  };

  // Función para bloquear/desbloquear el formulario durante el procesamiento
  window.setFormProcessing = function(isProcessing) {
    const btnConfirm = document.getElementById('btnConfirmAddProceso');
    const btnCancel = document.getElementById('btnCancelAddProceso');
    const procesoArea = document.getElementById('procesoArea');
    const inputEncargado = document.getElementById('procesoEncargado');
    const selectEncargado = document.getElementById('procesoEncargadoSelect');
    const btnContent = document.getElementById('addProcesoButtonContent');
    const btnLoading = document.getElementById('addProcesoButtonLoading');

    if (isProcessing) {
      // Bloquear
      if (btnConfirm) btnConfirm.disabled = true;
      if (btnCancel) btnCancel.disabled = true;
      if (procesoArea) procesoArea.disabled = true;
      if (inputEncargado) inputEncargado.disabled = true;
      if (selectEncargado) selectEncargado.disabled = true;

      // Mostrar spinner
      if (btnContent) btnContent.style.display = 'none';
      if (btnLoading) btnLoading.style.display = 'flex';

      // Cambiar cursor
      document.body.style.cursor = 'wait';
    } else {
      // Desbloquear
      if (btnConfirm) btnConfirm.disabled = false;
      if (btnCancel) btnCancel.disabled = false;
      if (procesoArea) procesoArea.disabled = false;
      if (inputEncargado) inputEncargado.disabled = false;
      if (selectEncargado) selectEncargado.disabled = false;

      // Ocultar spinner
      if (btnContent) btnContent.style.display = 'flex';
      if (btnLoading) btnLoading.style.display = 'none';

      // Restaurar cursor
      document.body.style.cursor = 'default';
    }
  };

  // Intercept del handleAgregarProceso original para sincronizar el valor del encargado
  // y mantener el bloqueo del formulario
  const originalHandleAgregarProceso = window.handleAgregarProceso;
  if (typeof originalHandleAgregarProceso === 'function') {
    window.handleAgregarProceso = async function() {
      // Sincronizar el valor del encargado del select/datalist al input original
      // Esto asegura que el input tenga el valor correcto aunque esté oculto
      const encargadoValue = window.getEncargadoValue();
      const inputEncargado = document.getElementById('procesoEncargado');
      if (inputEncargado && encargadoValue) {
        inputEncargado.value = encargadoValue;
      }

      // Bloquear el formulario
      window.setFormProcessing(true);

      try {
        // Llamar al original
        return await originalHandleAgregarProceso.apply(this, arguments);
      } finally {
        // Desbloquear el formulario cuando termine (éxito o error)
        window.setFormProcessing(false);
      }
    };
  }
})();
