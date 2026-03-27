'use strict';

// Gestión de modales del sistema de tracking
class TrackingModalManager {
  constructor() {
    this.init();
  }

  init() {
    this.initTrackingModalListeners();
  }

  // Inicializar listeners del modal
  initTrackingModalListeners() {
    // Cerrar modal al hacer clic en el overlay
    const overlay = document.getElementById('trackingModalOverlay');
    if (overlay) {
      overlay.onclick = () => this.closeTrackingModal();
    }

    // Cerrar modal con botón X (si existe)
    const closeBtn = document.querySelector('.tracking-modal-close');
    if (closeBtn) {
      closeBtn.onclick = () => this.closeTrackingModal();
    }

    // Configurar listeners del modal agregar proceso
    this.setupAddProcesoModalListeners();

    // Configurar listeners del modal de confirmación
    this.setupConfirmDeleteModalListeners();

    // Configurar botón volver
    this.setupBackButton();
  }

  // Cerrar modal principal de tracking
  closeTrackingModal() {
    const modal = document.getElementById('orderTrackingModal');
    if (modal) {
      modal.classList.remove('show');
      modal.style.display = 'none';
      console.log('[closeTrackingModal] Modal de seguimiento cerrado');
    }
  }

  // Abrir modal de agregar proceso
  openAddProcesoModal() {
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
      this.setupAddProcesoModalListeners();
    } else {
      console.error('[openAddProcesoModal] No se encontró el modal addProcesoModal');
    }
  }

  // Cerrar modal de agregar proceso
  closeAddProcesoModal() {
    const modal = document.getElementById('addProcesoModal');
    if (modal) {
      modal.classList.remove('show');
      modal.style.display = 'none';
    }
  }

  // Configurar listeners del modal agregar proceso
  setupAddProcesoModalListeners() {
    // Abrir modal
    const openBtn = document.getElementById('btnOpenAddProcesoModal');
    if (openBtn) {
      openBtn.onclick = () => this.openAddProcesoModal();
      console.log('[setupAddProcesoModalListeners] Botón ABRIR modal configurado');
    } else {
      console.warn('[setupAddProcesoModalListeners] Botón ABRIR modal no encontrado');
    }

    // Cerrar modal
    const closeBtn = document.getElementById('closeAddProcesoModal');
    if (closeBtn) {
      closeBtn.onclick = () => this.closeAddProcesoModal();
    }

    const cancelBtn = document.getElementById('btnCancelAddProceso');
    if (cancelBtn) {
      cancelBtn.onclick = () => this.closeAddProcesoModal();
    }

    const overlay = document.getElementById('addProcesoOverlay');
    if (overlay) {
      overlay.onclick = () => this.closeAddProcesoModal();
    }
  }

  // Configurar botón volver
  setupBackButton() {
    const backBtn = document.getElementById('backToPrendasBtn');
    if (backBtn) {
      backBtn.onclick = () => {
        if (typeof showPrendasView === 'function') {
          showPrendasView();
        }
      };
      console.log('[setupBackButton] Botón volver configurado');
    }
  }

  // Mostrar modal de confirmación para eliminar
  showConfirmDeleteModal(procesoId, areaName) {
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
      this.setupConfirmDeleteModalListeners();
      
      console.log('[showConfirmDeleteModal] Modal de confirmación mostrado');
    } else {
      console.error('[showConfirmDeleteModal] No se encontró el modal o el span del nombre');
    }
  }

  // Configurar listeners del modal de confirmación
  setupConfirmDeleteModalListeners() {
    // Botón cancelar
    const btnCancel = document.getElementById('btnCancelDelete');
    if (btnCancel) {
      btnCancel.onclick = () => this.closeConfirmDeleteModal();
    }
    
    // Botón cerrar (X)
    const btnClose = document.getElementById('closeConfirmDeleteModal');
    if (btnClose) {
      btnClose.onclick = () => this.closeConfirmDeleteModal();
    }
    
    // Botón confirmar eliminar
    const btnConfirm = document.getElementById('btnConfirmDelete');
    if (btnConfirm) {
      btnConfirm.onclick = () => {
        if (typeof executeDeleteProcess === 'function') {
          executeDeleteProcess();
        }
      };
    }
    
    // Cerrar al hacer clic en el overlay
    const overlay = document.querySelector('.confirm-delete-overlay');
    if (overlay) {
      overlay.onclick = () => this.closeConfirmDeleteModal();
    }
  }

  // Cerrar modal de confirmación
  closeConfirmDeleteModal() {
    const modal = document.getElementById('confirmDeleteModal');
    if (modal) {
      modal.classList.remove('show');
      modal.style.display = 'none';
      window.processToDelete = null;
    }
  }
}

// Exportar para uso global
window.TrackingModalManager = TrackingModalManager;
if (!window.trackingModalManager) {
  window.trackingModalManager = new TrackingModalManager();
}

// Funciones globales para compatibilidad
window.openAddProcesoModal = () => window.trackingModalManager.openAddProcesoModal();
window.closeAddProcesoModal = () => window.trackingModalManager.closeAddProcesoModal();
window.closeTrackingModal = () => window.trackingModalManager.closeTrackingModal();
window.showConfirmDeleteModal = (procesoId, areaName) => window.trackingModalManager.showConfirmDeleteModal(procesoId, areaName);
window.closeConfirmDeleteModal = () => window.trackingModalManager.closeConfirmDeleteModal();
