/**
 * Dropdown Service
 * 
 * Sistema completo de dropdowns para recibos-costura:
 * - Crear dropdowns dinámicos
 * - Posicionar inteligentemente
 * - Gestionar eventos (click items, outside click, toggle)
 * - Cerrar dropdowns
 * 
 * USO:
 * ====
 * DropdownService.open(button);
 * DropdownService.close();
 * DropdownService.closeAll();
 * DropdownService.isOpen();
 */

class DropdownService {
    constructor() {
        this.openDropdowns = new Set();
        this.eventListenerAttached = false;
    }

    /**
     * Extrae datos del botón
     * @param {HTMLElement} button - Botón con atributos data-*
     * @returns {object} Datos extraídos
     */
    extractButtonData(button) {
        return {
            menuId: button.getAttribute('data-menu-id'),
            pedidoId: button.getAttribute('data-pedido-id'),
            prendaId: button.getAttribute('data-prenda-id'),
            tipoRecibo: button.getAttribute('data-tipo-recibo') || 'COSTURA',
            esParcial: String(button.getAttribute('data-es-parcial') || '').toLowerCase() === 'true',
            parcialId: button.getAttribute('data-pedido-parcial-id')
        };
    }

    /**
     * Construir HTML de botones del dropdown
     * @param {object} data - Datos extraídos del botón
     * @returns {string} HTML de botones
     */
    buildDropdownButtons(data) {
        const { pedidoId, prendaId, tipoRecibo, esParcial, parcialId } = data;

        const onClickDetalles = esParcial && parcialId
            ? `openOrderDetailModalWithParcial(${parcialId}, ${prendaId || 'null'}, '${tipoRecibo}', ${pedidoId}); DropdownService.getInstance().closeAll()`
            : `openOrderDetailModalWithProcess(${pedidoId}, ${prendaId || 'null'}, '${tipoRecibo}'); DropdownService.getInstance().closeAll()`;

        return `
            <button class="dropdown-item-btn" onclick="${onClickDetalles}">
                <i class="fas fa-eye"></i> Ver Detalles
            </button>
            <div class="dropdown-divider"></div>
            <button class="dropdown-item-btn" onclick="abrirModalSeguimiento(${pedidoId}, ${prendaId || 'null'}); DropdownService.getInstance().closeAll()">
                <i class="fas fa-tasks"></i> Seguimiento
            </button>
        `;
    }

    /**
     * Crear o reutilizar dropdown
     * @param {HTMLElement} button - Botón que abre el dropdown
     * @returns {HTMLElement} Elemento dropdown
     */
    createOrGetDropdown(button) {
        const data = this.extractButtonData(button);

        // Verificar si ya existe
        let existing = document.getElementById(data.menuId);
        if (existing) {
            return existing;
        }

        // Obtener contenedor
        const container = document.getElementById('dropdowns-container');
        if (!container) {
            console.error('[DropdownService] No se encontró #dropdowns-container');
            return null;
        }

        // Crear dropdown
        const dropdown = document.createElement('div');
        dropdown.id = data.menuId;
        dropdown.className = 'dropdown-menu-recibos';
        dropdown.innerHTML = this.buildDropdownButtons(data);

        container.appendChild(dropdown);
        return dropdown;
    }

    /**
     * Posicionar dropdown inteligentemente
     * @param {HTMLElement} dropdown - Elemento del dropdown
     * @param {HTMLElement} button - Botón referencia
     */
    positionDropdown(dropdown, button) {
        const rect = button.getBoundingClientRect();
        dropdown.style.top = (rect.bottom + 5) + 'px';
        dropdown.style.left = rect.left + 'px';
        dropdown.style.display = 'block';
        dropdown.style.pointerEvents = 'auto';

        // Ajustar si se sale de la pantalla
        setTimeout(() => {
            const dropRect = dropdown.getBoundingClientRect();
            if (dropRect.right > window.innerWidth) {
                dropdown.style.left = (window.innerWidth - dropRect.width - 10) + 'px';
            }
            if (dropRect.bottom > window.innerHeight) {
                dropdown.style.top = (rect.top - dropRect.height - 5) + 'px';
            }
        }, 10);
    }

    /**
     * Abrir dropdown
     * @param {HTMLElement} button - Botón que abre el dropdown
     */
    open(button) {
        if (!button) return;

        console.log('[DropdownService] Abriendo dropdown...');

        // Toggle: si ya está abierto, cerrar
        if (button.classList.contains('dropdown-opening')) {
            console.log('[DropdownService]  TOGGLE: Botón ya estaba abierto, cerrando...');
            this.closeAll();
            return;
        }

        // Cerrar todos primero
        this.closeAll();

        // Marcar como abierto
        button.classList.add('dropdown-opening');

        // Crear o reutilizar dropdown
        const dropdown = this.createOrGetDropdown(button);
        if (!dropdown) {
            button.classList.remove('dropdown-opening');
            return;
        }

        // Posicionar
        this.positionDropdown(dropdown, button);
        this.openDropdowns.add(dropdown.id);

        // Quitar focus
        button.blur();

        console.log('[DropdownService] Dropdown abierto:', dropdown.id);

        // Adjuntar event listener si no está adjunto
        if (!this.eventListenerAttached) {
            this.attachEventListeners();
        }
    }

    /**
     * Cerrar un dropdown específico por ID
     * @param {string} dropdownId - ID del dropdown
     */
    closeById(dropdownId) {
        const dropdown = document.getElementById(dropdownId);
        if (dropdown) {
            dropdown.style.display = 'none';
            dropdown.style.pointerEvents = 'none';
            this.openDropdowns.delete(dropdownId);
        }

        // Remover clase del botón
        const btn = document.querySelector(`[data-menu-id="${dropdownId}"]`);
        if (btn) {
            btn.classList.remove('dropdown-opening');
        }
    }

    /**
     * Cerrar todos los dropdowns
     */
    closeAll() {
        console.log('[DropdownService] Cerrando todos los dropdowns');

        // Remover clase de todos los botones
        const buttons = document.querySelectorAll('.btn-ver-dropdown.dropdown-opening');
        buttons.forEach(btn => btn.classList.remove('dropdown-opening'));

        // Cerrar todos los dropdowns
        const dropdowns = document.querySelectorAll('.dropdown-menu-recibos');
        let count = 0;
        dropdowns.forEach(dropdown => {
            if (dropdown.style.display !== 'none') {
                dropdown.style.display = 'none';
                dropdown.style.pointerEvents = 'none';
                count++;
            }
        });

        this.openDropdowns.clear();
        console.log('[DropdownService]  Dropdowns cerrados:', count);
    }

    /**
     * Verificar si algún dropdown está abierto
     * @returns {boolean}
     */
    isOpen() {
        return this.openDropdowns.size > 0;
    }

    /**
     * Adjuntar event listeners para delegación
     */
    attachEventListeners() {
        if (this.eventListenerAttached) return;

        document.addEventListener('click', (e) => this.handleClickEvent(e), true);
        this.eventListenerAttached = true;

        console.log('[DropdownService] Event listeners adjuntados');
    }

    /**
     * Manejar eventos de click
     * @param {Event} e - Evento de click
     */
    handleClickEvent(e) {
        const btnVer = e.target.closest('.btn-ver-dropdown');
        const dropdownMenu = e.target.closest('.dropdown-menu-recibos');
        const itemBtn = e.target.closest('.dropdown-item-btn');

        // Click en botón: abrir/toggle
        if (btnVer) {
            e.preventDefault();
            e.stopPropagation();
            this.open(btnVer);
            return;
        }

        // Click en item del dropdown
        if (itemBtn && dropdownMenu) {
            console.log('[DropdownService] Click en item - cerrando después de ejecutar acción');
            setTimeout(() => {
                this.closeAll();
            }, 50);
            return;
        }

        // Click dentro del dropdown pero no en item
        if (dropdownMenu && !itemBtn) {
            console.log('[DropdownService] Click dentro del dropdown - permitiendo propagación');
            return;
        }

        // Click fuera - cerrar si hay abierto
        if (!btnVer && !dropdownMenu && this.isOpen()) {
            console.log('[DropdownService] Click fuera - cerrando dropdown');
            this.closeAll();
        }
    }

    /**
     * Obtener instancia singleton
     */
    static getInstance() {
        if (!window.dropdownServiceInstance) {
            window.dropdownServiceInstance = new DropdownService();
        }
        return window.dropdownServiceInstance;
    }
}

// Crear instancia global
const DropdownService_Instance = DropdownService.getInstance();

// Hacer disponible globalmente
window.DropdownService = DropdownService;
