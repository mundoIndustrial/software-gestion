/**
 * DaysSelectorManager - Manage delivery days selector dropdown
 * 
 * Responsibility: Encapsulate all DOM manipulation and event binding 
 * for the days selector. Replaces setupDaysSelector() function.
 * 
 * Usage:
 *   const selector = new DaysSelectorManager('trackingDaysSelector', {
 *     orderState,
 *     onSave: saveDiaEntregaSelection
 *   });
 *   selector.initialize();
 */

class DaysSelectorManager {
  constructor(selectorId, options = {}) {
    this.selectorId = selectorId;
    this.orderState = options.orderState;
    this.onSave = options.onSave || (() => {});
    
    this.selector = null;
    this.trigger = null;
    this.menu = null;
    this.valueEl = null;
    
    this.initialized = false;
    
    this.cacheElements();
  }

  cacheElements() {
    this.selector = document.getElementById(this.selectorId);
    this.trigger = document.getElementById(`${this.selectorId}Trigger`);
    this.menu = document.getElementById(`${this.selectorId}Menu`);
    this.valueEl = document.getElementById(`${this.selectorId}Value`);
  }

  isValid() {
    return !!(this.selector && this.trigger && this.menu && this.valueEl);
  }

  initialize() {
    if (!this.isValid()) {
      console.warn(`[DaysSelectorManager] Elementos no encontrados para ${this.selectorId}`);
      return;
    }

    if (this.initialized) return;

    this.renderMenuItems();
    
    this.bindTriggerClick();
    this.bindMenuClick();
    this.bindDocumentClick();
    this.bindDocumentKeydown();
    
    this.initialized = true;
    console.log(`[DaysSelectorManager] Inicializado para ${this.selectorId}`);
  }

  renderMenuItems() {
    if (this.menu.dataset.bound) return;

    let menuItems = '<button type="button" class="tracking-days-selector-item" data-value="0">Sin seleccionar</button>';
    
    menuItems += Array.from({ length: 35 }, (_, i) => {
      const n = i + 1;
      const label = `${n} ${n === 1 ? 'día' : 'días'}`;
      return `<button type="button" class="tracking-days-selector-item" data-value="${n}">${label}</button>`;
    }).join('');

    this.menu.innerHTML = menuItems;
    this.menu.dataset.bound = '1';
  }

  closeMenu() {
    this.menu.style.display = 'none';
    this.selector.classList.remove('open');
  }

  openMenu() {
    this.menu.style.display = 'block';
    this.selector.classList.add('open');
  }

  toggleMenu() {
    const isOpen = this.menu.style.display !== 'none' && this.menu.style.display !== '';
    if (isOpen) {
      this.closeMenu();
    } else {
      this.openMenu();
    }
  }

  bindTriggerClick() {
    if (this.trigger.dataset.bound) return;

    this.trigger.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      this.toggleMenu();
    });

    this.trigger.dataset.bound = '1';
  }

  bindMenuClick() {
    if (this.menu.dataset.clickBound) return;

    this.menu.addEventListener('click', (e) => {
      const btn = e.target.closest('.tracking-days-selector-item');
      if (!btn) return;

      const n = parseInt(btn.dataset.value, 10);
      if (!Number.isFinite(n)) return;

      if (n === 0) {
        this.valueEl.textContent = 'Sin seleccionar';
        this.orderState.setSelectedDays(null);
      } else {
        this.valueEl.textContent = `${n} ${n === 1 ? 'día' : 'días'}`;
        this.orderState.setSelectedDays(n);
      }

      this.onSave();
      
      this.closeMenu();
    });

    this.menu.dataset.clickBound = '1';
  }

  bindDocumentClick() {
    if (document.body.dataset.trackingDaysSelectorGlobalBound) return;

    document.addEventListener('click', (e) => {
      if (!this.selector.contains(e.target)) {
        this.closeMenu();
      }
    });

    document.body.dataset.trackingDaysSelectorGlobalBound = '1';
  }

  bindDocumentKeydown() {
    if (window.trackingDaysSelectorKeydownBound) return;

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        this.closeMenu();
      }
    });

    window.trackingDaysSelectorKeydownBound = true;
  }

  getValue() {
    return this.orderState.getSelectedDays();
  }

  setValue(days) {
    if (days === null || days === 0) {
      this.valueEl.textContent = 'Sin seleccionar';
      this.orderState.setSelectedDays(null);
    } else if (Number.isFinite(days)) {
      const label = `${days} ${days === 1 ? 'día' : 'días'}`;
      this.valueEl.textContent = label;
      this.orderState.setSelectedDays(days);
    }
  }

  reset() {
    this.valueEl.textContent = 'Sin seleccionar';
    this.orderState.setSelectedDays(null);
    this.closeMenu();
  }
}

export { DaysSelectorManager };
