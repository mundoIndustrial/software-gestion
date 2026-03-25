'use strict';

/**
 * DAYS SELECTOR HANDLER
 * 
 * Expone globalmente las funciones para manejar el selector de días
 * DEBE cargarse ANTES de ui-components.js
 * Script tradicional (NO Es6 Module)
 */

/**
 * Reinitialize the days selector when the modal opens
 * This ensures the selector is ready when data is loaded 
 */
function ensureDaysSelectorInitialized() {
  console.log('[ensureDaysSelectorInitialized] Verificando selector...');
  
  // Si ya existe globalSelector, no hacer nada
  if (window.globalDaysSelectorManager && window.globalDaysSelectorManager.initialized) {
    console.log('[ensureDaysSelectorInitialized] Selector ya inicializado');
    return;
  }
}

/**
 * Actualizar selector de días con reintentos
 * Espera a que el selector esté disponible antes de actualizar
 * 
 * @param {number} dias - Número de días a establecer (1-35)
 * @param {number} intento - Intento actual (máx 10)
 */
window.updateDaysSelectorWithRetry = function(dias, intento = 0) {
  if (!dias) {
    console.log('[updateDaysSelectorWithRetry] No hay dias_de_entrega, saltando');
    return;
  }

  if (!dias || dias === null || dias === undefined) {
    console.log('[updateDaysSelectorWithRetry] Valor inválido:', dias);
    return;
  }

  console.log('[updateDaysSelectorWithRetry] Intentando actualizar con:', dias, 'Intento:', intento + 1);

  // Buscar el elemento del selector
  const valueEl = document.getElementById('trackingDaysSelectorValue');
  
  if (valueEl) {
    // El elemento existe, actualizar directamente
    const label = dias === 1 ? `${dias} día` : `${dias} días`;
    valueEl.textContent = label;
    console.log('[updateDaysSelectorWithRetry] ✅ Selector actualizado exitosamente a:', label);
    
    // Guardar en estado global si está disponible
    if (window.trackingDaysSelector && typeof window.trackingDaysSelector.setValue === 'function') {
      try {
        window.trackingDaysSelector.setValue(dias);
        console.log('[updateDaysSelectorWithRetry] ✅ Estado global también actualizado');
      } catch (error) {
        console.log('[updateDaysSelectorWithRetry] Aviso (no crítico):', error.message);
      }
    }
  } else if (intento < 10) {
    // Reintentar después de 100ms
    console.log(`[updateDaysSelectorWithRetry] Elemento no encontrado, reintentando (${intento + 1}/10)...`);
    setTimeout(() => window.updateDaysSelectorWithRetry(dias, intento + 1), 100);
  } else {
    console.warn('[updateDaysSelectorWithRetry] Elemento no encontrado después de 10 intentos');
  }
};

// Exponer función globalmente para que datos-loader pueda usarla
window.ensureDaysSelectorInitialized = ensureDaysSelectorInitialized;

console.log('[days-selector-handler.js] ✅ Funciones expuestas a window');
