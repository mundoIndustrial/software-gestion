/**
 * Tracking Modal Handler
 * Maneja la integración del modal de seguimiento con la vista de órdenes
 */

(function() {
  'use strict';



  // Inicializar event listeners del modal
  function initTrackingModalListeners() {
    const closeBtn = document.getElementById('closeTrackingModal');
    const overlay = document.getElementById('trackingModalOverlay');
    const modal = document.getElementById('orderTrackingModal');

    if (closeBtn) {
      closeBtn.addEventListener('click', () => {

        if (typeof closeOrderTracking === 'function') {
          closeOrderTracking();
        } else if (modal) {
          modal.style.display = 'none';
        }
      });
    }

    if (overlay) {
      overlay.addEventListener('click', () => {

        if (typeof closeOrderTracking === 'function') {
          closeOrderTracking();
        } else if (modal) {
          modal.style.display = 'none';
        }
      });
    }

    // Cerrar con ESC
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && modal && modal.style.display !== 'none') {

        if (typeof closeOrderTracking === 'function') {
          closeOrderTracking();
        } else {
          modal.style.display = 'none';
        }
      }
    });


  }

  // Inicializar cuando el DOM esté listo
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTrackingModalListeners);
  } else {
    initTrackingModalListeners();
  }
})();
