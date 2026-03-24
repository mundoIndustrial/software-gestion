/**
 * ContainerFactory - Factory para construir DIContainer pre-configurado (Phase 11)
 * 
 * Responsabilidad: Registrar todos los servicios en el container
 * 
 * Ventajas:
 * - Centraliza toda la configuración de dependencias
 * - Fácil de mockear/extender para testing
 * - Separación entre instanciación y uso
 * - Un único punto de verdad para las dependencias
 * 
 * @module ContainerFactory
 */

import { DIContainer } from './DIContainer.js';

/**
 * Construir y configurar el DIContainer con todos los servicios
 * 
 * @param {Object} dependencies - Dependencias externas (Domain, Infrastructure, APIs)
 * @param {Object} dependencies.orderState - OrderState del Domain Layer
 * @param {Object} dependencies.dateFormatter - DateFormatter del Domain Layer
 * @param {Object} dependencies.svgIcons - SvgIcons del Infrastructure Layer
 * @param {Object} dependencies.modalUtils - ModalUtils del Infrastructure Layer
 * @param {Object} dependencies.dateUtils - DateUtils del Infrastructure Layer
 * @param {Object} dependencies.queryUtils - QueryUtils del Infrastructure Layer
 * @returns {DIContainer} Container configurado y listo
 * 
 * @example
 * const container = createContainer({
 *   orderState,
 *   dateFormatter,
 *   svgIcons,
 *   modalUtils,
 *   dateUtils,
 *   queryUtils,
 *   OrderApiService
 * });
 * 
 * const processService = container.get('processService');
 */
export function createContainer(dependencies) {
  const container = new DIContainer();

  // ============================================================
  // REGISTER: Infrastructure Layer (stateless helpers)
  // ============================================================

  container.register('svgIcons', () => dependencies.svgIcons);
  container.register('modalUtils', () => dependencies.modalUtils);
  container.register('dateUtils', () => dependencies.dateUtils);
  container.register('queryUtils', () => dependencies.queryUtils);

  // ============================================================
  // REGISTER: Domain Layer (state & value objects)
  // ============================================================

  container.register('orderState', () => dependencies.orderState);
  container.register('dateFormatter', () => dependencies.dateFormatter);

  // ============================================================
  // REGISTER: Application Layer - Services
  // ============================================================

  // 1. API Service (base dependency)
  container.register('orderApiService', () => dependencies.OrderApiService);

  // 2. UI Feedback Service (abstracción para mensajes)
  container.register('uiFeedbackService', () => ({
    showSuccess: (message) => dependencies.showSuccess(message),
    showError: (message) => dependencies.showError(message)
  }));

  // 3. Modal Service (abstracción para modal actions)
  container.register('modalService', () => ({
    closeConfirmDeleteModal: dependencies.closeConfirmDeleteModal
  }));

  // 4. Validation Service (stateless)
  container.register('formValidationService', () => {
    const { ProcessFormValidationService } = dependencies;
    return new ProcessFormValidationService();
  });

  // 5. Form State Manager
  container.register('formStateManager', () => {
    const { FormStateManager } = dependencies;
    return new FormStateManager();
  });

  // ============================================================
  // REGISTER: UI Managers (Phase 12 - DIP)
  // ============================================================

  // 5a. Process Form Manager (singleton para reutilización)
  container.register('processFormManager', () => {
    const { ProcessFormManager } = dependencies;
    return new ProcessFormManager();
  });

  // 5b. Modal Event Binder Factory (crear nuevas instancias por modal)
  container.register('modalEventBinderFactory', () => {
    const { ModalEventBinder } = dependencies;
    return (modalId) => new ModalEventBinder(modalId);
  });

  // 5c. Button Loading Manager Factory (crear nuevas instancias por botón)
  container.register('buttonLoadingManagerFactory', () => {
    const { ButtonLoadingManager } = dependencies;
    return (buttonId, config) => new ButtonLoadingManager(buttonId, config);
  });

  // 5d. Days Selector Manager Factory (crear nuevas instancias por selector)
  container.register('daysSelectorManagerFactory', () => {
    const { DaysSelectorManager } = dependencies;
    return (selectorId, options) => new DaysSelectorManager(selectorId, options);
  });

  // ============================================================
  // REGISTER: Domain Services (Phase 12 - DIP)
  // ============================================================

  // 5d. Areas Config Service (centralizar configuración de áreas)
  container.register('areasConfigService', (get) => {
    const { AreasConfigService } = dependencies;
    return new AreasConfigService(
      get('orderState'),
      {} // Heredará configuración de orderState
    );
  });

  // 5e. Process Workflow Service (orquestar agregar/editar procesos)
  container.register('processWorkflowService', (get) => {
    const { ProcessWorkflowService } = dependencies;
    return new ProcessWorkflowService({
      orderApiService: get('orderApiService'),
      orderState: get('orderState'),
      formManager: get('processFormManager'),
      areasConfigService: get('areasConfigService'),
      uiFeedbackService: get('uiFeedbackService')
    });
  });

  // 6. Data Reload Service (con dependencias)
  container.register('dataReloadService', (get) => {
    const { DataReloadService } = dependencies;
    // Inicialmente vacío, se actualizará post-rendererdefinition
    return new DataReloadService(
      get('orderApiService'),
      get('orderState'),
      {}
    );
  });

  // 7. Process Delete Service
  container.register('processDeleteService', (get) => {
    const { ProcessDeleteService } = dependencies;
    return new ProcessDeleteService(
      get('orderApiService'),
      get('dataReloadService'),
      get('uiFeedbackService'),
      get('modalService')
    );
  });

  // 8. Process Service (orquestrador principal)
  container.register('processService', (get) => {
    const { ProcessService } = dependencies;
    return new ProcessService(
      get('processDeleteService'),
      get('formValidationService'),
      get('formStateManager'),
      get('dataReloadService'),
      get('orderApiService'),
      get('orderState'),
      get('uiFeedbackService')
    );
  });

  // ============================================================
  // REGISTER: Presentation Layer - Renderers
  // ============================================================

  container.register('prendaTrackingRenderer', () => {
    const { PrendaTrackingRenderer } = dependencies;
    return new PrendaTrackingRenderer();
  });

  container.register('areaCardRenderer', () => {
    const { AreaCardRenderer } = dependencies;
    return new AreaCardRenderer();
  });

  container.register('badgeRenderer', () => {
    const { BadgeRenderer } = dependencies;
    return new BadgeRenderer();
  });

  container.register('updateRenderer', () => {
    const { UpdateRenderer } = dependencies;
    return new UpdateRenderer();
  });

  // ============================================================
  // DEFERRED SETUP: Inyección de renderers en DataReloadService
  // ============================================================
  // Los renderers necesitan inyectarse DESPUÉS de que sean definidas las funciones
  // de rendering en el handler (ej: renderPrendaTrackingTimeline, actualizarAreaEnTablaRecibos)

  container.registerDeferredSetup((get) => {
    // Esta función se ejecutará cuando se llame a container.executeDeferredSetup()
    // Permitirá inyectar callbacks de renderizado en dataReloadService
    if (dependencies.renderCallbacks) {
      const dataReloadService = get('dataReloadService');
      dataReloadService._renderers = dependencies.renderCallbacks;
    }
  });

  return container;
}

/**
 * Crear un container con valores por defecto (para testing)
 * 
 * @returns {DIContainer} Container vacío
 */
export function createTestContainer() {
  return new DIContainer();
}
