'use strict';

// Selector de días para entrega
class TrackingDaysSelector {
  constructor() {
    this.init();
  }

  init() {
    this.setupDaysSelector();
  }

  setupDaysSelector() {
    const selector = document.getElementById('trackingDaysSelector');
    const trigger = document.getElementById('trackingDaysSelectorTrigger');
    const menu = document.getElementById('trackingDaysSelectorMenu');
    const valueEl = document.getElementById('trackingDaysSelectorValue');
    if (!selector || !trigger || !menu || !valueEl) return;

    if (!menu.dataset.bound) {
      // Agregar opción "Sin seleccionar" al inicio
      let menuItems = '<button type="button" class="tracking-days-selector-item" data-value="0">Sin seleccionar</button>';
      menuItems += Array.from({ length: 35 }, (_, i) => {
        const n = i + 1;
        const label = `${n} ${n === 1 ? 'día' : 'días'}`;
        return `<button type="button" class="tracking-days-selector-item" data-value="${n}">${label}</button>`;
      }).join('');
      menu.innerHTML = menuItems;
      menu.dataset.bound = '1';
    }

    const closeMenu = () => {
      menu.style.display = 'none';
      selector.classList.remove('open');
    };

    const openMenu = () => {
      menu.style.display = 'block';
      selector.classList.add('open');
    };

    const toggleMenu = () => {
      const isOpen = menu.style.display !== 'none' && menu.style.display !== '';
      if (isOpen) closeMenu();
      else openMenu();
    };

    if (!trigger.dataset.bound) {
      trigger.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        toggleMenu();
      });
      trigger.dataset.bound = '1';
    }

    if (!menu.dataset.clickBound) {
      menu.addEventListener('click', (e) => {
        const btn = e.target.closest('.tracking-days-selector-item');
        if (!btn) return;
        const n = parseInt(btn.dataset.value, 10);
        if (!Number.isFinite(n)) return;

        // Manejar "Sin seleccionar" (valor 0)
        if (n === 0) {
          valueEl.textContent = 'Sin seleccionar';
          window.__trackingDiasSeleccionados = null;
        } else {
          valueEl.textContent = `${n} ${n === 1 ? 'día' : 'días'}`;
          window.__trackingDiasSeleccionados = n;
        }
        
        // Guardar los datos al cambiar la selección
        if (typeof saveDiaEntregaSelection === 'function') {
          saveDiaEntregaSelection();
        }
        closeMenu();
      });
      menu.dataset.clickBound = '1';
    }

    if (!document.body.dataset.trackingDaysSelectorGlobalBound) {
      document.addEventListener('click', (e) => {
        if (!selector.contains(e.target)) {
          closeMenu();
        }
      });

      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          closeMenu();
        }
      });
      document.body.dataset.trackingDaysSelectorGlobalBound = '1';
    }
  }
}

// Exportar para uso global
window.TrackingDaysSelector = TrackingDaysSelector;
window.trackingDaysSelector = new TrackingDaysSelector();
