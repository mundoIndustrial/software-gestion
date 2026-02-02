/**
 * Action Menu Handler
 * Maneja la apertura y cierre del menú de acciones en la tabla de órdenes
 */

(function() {
  'use strict';



  // Inicializar event listeners
  function init() {

    
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



    if (!menu) {

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
    
    // Usar el mismo sistema que main: abrirSelectorRecibos
    if (typeof window.abrirSelectorRecibos === 'function') {
      window.abrirSelectorRecibos(ordenId);
    } else {
      console.error('❌ [handleDetalle] abrirSelectorRecibos no disponible');
      alert('Error: Sistema de detalles no disponible');
    }
  }

  /**
   * Acción: Ver seguimiento de la orden
   */
  function handleSeguimiento(ordenId) {

    
    // Verificar que la función openOrderTracking esté disponible
    if (typeof openOrderTracking === 'function') {
      openOrderTracking(ordenId);
    } else {

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
