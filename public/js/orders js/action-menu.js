/**
 * Action Menu Handler
 * Maneja la apertura y cierre del menú de acciones en la tabla de órdenes
 */

(function() {
  'use strict';

  console.log('Action Menu Script Loaded');

  // Inicializar event listeners
  function init() {
    console.log('Initializing action menu...');
    
    // Usar delegación de eventos para botones
    document.addEventListener('click', function(e) {
      const button = e.target.closest('.action-view-btn');
      if (button) {
        handleButtonClick.call(button, e);
      }
    });

    // Usar delegación de eventos para items del menú
    document.addEventListener('click', function(e) {
      const item = e.target.closest('.action-menu-item');
      if (item) {
        handleMenuItemClick.call(item, e);
      }
    });

    // Cerrar menú al hacer click fuera
    document.addEventListener('click', handleDocumentClick);
    
    console.log('Action menu initialized');
  }

  /**
   * Maneja el click en el botón de acciones
   */
  function handleButtonClick(e) {
    e.preventDefault();
    e.stopPropagation();

    const button = this;
    const ordenId = button.getAttribute('data-orden-id');
    const menu = document.querySelector(`.action-menu[data-orden-id="${ordenId}"]`);

    console.log('Button clicked, ordenId:', ordenId);

    if (!menu) {
      console.warn('Menu not found for orden:', ordenId);
      return;
    }

    // Cerrar otros menús abiertos
    document.querySelectorAll('.action-menu').forEach(m => {
      if (m !== menu) {
        m.classList.remove('active');
      }
    });

    // Toggle del menú actual
    menu.classList.toggle('active');
  }

  /**
   * Maneja el click en items del menú
   */
  function handleMenuItemClick(e) {
    e.preventDefault();
    e.stopPropagation();

    const item = this; // Usar 'this' en lugar de e.currentTarget
    const action = item.getAttribute('data-action');
    const menu = item.closest('.action-menu');
    const ordenId = menu.getAttribute('data-orden-id');

    console.log('Menu item clicked, action:', action, 'ordenId:', ordenId);

    // Cerrar el menú
    menu.classList.remove('active');

    // Ejecutar la acción correspondiente
    switch(action) {
      case 'detalle':
        handleDetalle(ordenId);
        break;
      case 'seguimiento':
        handleSeguimiento(ordenId);
        break;
      default:
        console.warn('Acción desconocida:', action);
    }
  }

  /**
   * Maneja el click fuera del menú para cerrarlo
   */
  function handleDocumentClick(e) {
    // Si el click es en un botón o menú, no hacer nada
    if (e.target.closest('.action-view-btn') || e.target.closest('.action-menu')) {
      return;
    }

    // Cerrar todos los menús
    document.querySelectorAll('.action-menu.active').forEach(menu => {
      menu.classList.remove('active');
    });
  }

  /**
   * Acción: Ver detalle de la orden
   */
  function handleDetalle(ordenId) {
    console.log('Ver detalle de orden:', ordenId);
    
    // Obtener datos de la orden usando el mismo endpoint que asesores
    fetch(`/registros/${ordenId}`, {
      method: 'GET',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      }
    })
    .then(response => {
      console.log('Response status:', response.status);
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.json();
    })
    .then(data => {
      console.log('Datos recibidos:', data);
      // Disparar evento con los datos de la orden
      window.dispatchEvent(new CustomEvent('load-order-detail', { detail: data }));
    })
    .catch(error => {
      console.error('Error al obtener datos:', error);
      alert('Error al cargar los detalles de la orden: ' + error.message);
    });
  }

  /**
   * Acción: Ver seguimiento de la orden
   */
  function handleSeguimiento(ordenId) {
    console.log('Ver seguimiento de orden:', ordenId);
    
    // Verificar que la función openOrderTracking esté disponible
    if (typeof openOrderTracking === 'function') {
      openOrderTracking(ordenId);
    } else {
      console.error('openOrderTracking no está disponible');
      alert('Error: No se pudo cargar el módulo de seguimiento');
    }
  }

  // Inicializar cuando el DOM esté listo
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
