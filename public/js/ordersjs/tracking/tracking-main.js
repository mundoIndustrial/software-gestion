'use strict';

// Archivo principal que inicializa todos los módulos de tracking
class TrackingMain {
  constructor() {
    this.init();
  }

  init() {
    console.log('[TrackingMain] Inicializando sistema de tracking modular...');
    
    // Esperar a que todos los módulos estén cargados
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => this.initializeModules());
    } else {
      this.initializeModules();
    }
  }

  initializeModules() {
    console.log('[TrackingMain] DOM listo, inicializando módulos...');
    
    // Los módulos se auto-inicializan al ser cargados, pero aquí podemos
    // asegurarnos de que todo esté configurado correctamente
    
    // Inicializar listeners principales si es necesario
    this.setupMainListeners();
    
    // Precargar festivos
    if (typeof precargarFestivos === 'function') {
      precargarFestivos();
    }
    
    console.log('[TrackingMain] Sistema de tracking modular inicializado completamente');
  }

  setupMainListeners() {
    // Configurar listeners globales si son necesarios
    // Por ejemplo, atajos de teclado, eventos globales, etc.
    
    // Ejemplo: cerrar modales con ESC
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        // Cerrar cualquier modal abierto
        const modals = document.querySelectorAll('.modal.show, [class*="modal"].show');
        modals.forEach(modal => {
          if (modal.id === 'orderTrackingModal' && typeof closeTrackingModal === 'function') {
            closeTrackingModal();
          } else if (modal.id === 'addProcesoModal' && typeof closeAddProcesoModal === 'function') {
            closeAddProcesoModal();
          } else if (modal.id === 'confirmDeleteModal' && typeof closeConfirmDeleteModal === 'function') {
            closeConfirmDeleteModal();
          } else if (modal.id === 'trackingPrendasSelectorOverlay' && typeof cerrarSelectorPrendas === 'function') {
            cerrarSelectorPrendas();
          }
        });
      }
    });

    // Ejemplo: atajos de teclado para acciones comunes
    document.addEventListener('keydown', (e) => {
      // Ctrl+N para agregar nuevo proceso
      if (e.ctrlKey && e.key === 'n') {
        e.preventDefault();
        const btnAgregar = document.getElementById('btnOpenAddProcesoModal');
        if (btnAgregar && btnAgregar.style.display !== 'none' && !btnAgregar.disabled) {
          if (typeof openAddProcesoModal === 'function') {
            openAddProcesoModal();
          }
        }
      }
    });
  }

  // Método para verificar que todos los módulos estén cargados
  checkModulesStatus() {
    const modules = {
      'TrackingModalManager': typeof window.TrackingModalManager !== 'undefined',
      'TrackingDaysSelector': typeof window.TrackingDaysSelector !== 'undefined',
      'DateUtils': typeof window.DateUtils !== 'undefined',
      'TrackingDataLoader': typeof window.TrackingDataLoader !== 'undefined',
      'TrackingUIComponents': typeof window.TrackingUIComponents !== 'undefined',
      'ProcessManager': typeof window.ProcessManager !== 'undefined',
      'PrendasRenderer': typeof window.PrendasRenderer !== 'undefined',
      'AreaCards': typeof window.AreaCards !== 'undefined'
    };

    console.log('[TrackingMain] Estado de los módulos:', modules);
    
    const allLoaded = Object.values(modules).every(loaded => loaded);
    if (allLoaded) {
      console.log('[TrackingMain] ✅ Todos los módulos cargados correctamente');
    } else {
      console.warn('[TrackingMain] ⚠️ Algunos módulos no están cargados:', modules);
    }
    
    return allLoaded;
  }

  // Método para recargar/reinicializar el sistema
  reinitialize() {
    console.log('[TrackingMain] Reinicializando sistema de tracking...');
    this.checkModulesStatus();
    this.initializeModules();
  }
}

// Crear instancia principal
window.TrackingMain = TrackingMain;
window.trackingMain = new TrackingMain();

// Verificar estado después de un corto tiempo para asegurar que todo cargó
setTimeout(() => {
  window.trackingMain.checkModulesStatus();
}, 1000);

// Exportar función para uso externo si es necesario
window.reinitializeTracking = () => window.trackingMain.reinitialize();
