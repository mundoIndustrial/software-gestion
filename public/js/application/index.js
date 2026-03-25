/**
 * Application Layer Exports
 * 
 * Central hub re-exporting all application services and renderers
 * Enables single import point for the entire application layer
 */

// ============================================================
// Re-export from ordersjs/application (specialized services)
// ============================================================

// API Services
export { default as OrderApiService } from '../ordersjs/application/OrderApiService.js';
export { ProcessService } from '../ordersjs/application/ProcessService.js';
export { ProcessDeleteService } from '../ordersjs/application/ProcessDeleteService.js';
export { ProcessFormValidationService } from '../ordersjs/application/ProcessFormValidationService.js';
export { FormStateManager } from '../ordersjs/application/FormStateManager.js';
export { DataReloadService } from '../ordersjs/application/DataReloadService.js';

// UI Managers
export { ProcessFormManager } from '../ordersjs/application/ProcessFormManager.js';
export { ModalEventBinder } from '../ordersjs/application/ModalEventBinder.js';
export { ButtonLoadingManager } from '../ordersjs/application/ButtonLoadingManager.js';

// Domain Services
export { AreasConfigService } from '../ordersjs/application/AreasConfigService.js';
export { ProcessWorkflowService } from '../ordersjs/application/ProcessWorkflowService.js';

// Renderers (from ordersjs layer - comprehensive implementations)
export { PrendaTrackingRenderer } from '../ordersjs/application/Renderers/PrendaTrackingRenderer.js';
export { AreaCardRenderer } from '../ordersjs/application/Renderers/AreaCardRenderer.js';
export { BadgeRenderer } from '../ordersjs/application/Renderers/BadgeRenderer.js';
export { UpdateRenderer } from '../ordersjs/application/Renderers/UpdateRenderer.js';

// Dependency Injection
export { DIContainer } from '../ordersjs/application/DIContainer.js';
export { createContainer, createTestContainer } from '../ordersjs/application/ContainerFactory.js';

// ============================================================
// Local services (shared across modules)
// ============================================================
export { DaysSelectorManager } from './DaysSelectorManager.js';
export { OrderLoaderService } from './OrderLoaderService.js';
export { DateFormatterFacade } from './DateFormatterFacade.js';
