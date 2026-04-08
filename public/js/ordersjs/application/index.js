/**
 * Application Layer - Index
 * 
 * Exporta todos los servicios de aplicación y renderers (OCP)
 */

// Services
export { default as OrderApiService } from './OrderApiService.js';
export { ProcessDeleteService } from './ProcessDeleteService.js';
export { ProcessFormValidationService } from './ProcessFormValidationService.js';
export { FormStateManager } from './FormStateManager.js';
export { DataReloadService } from './DataReloadService.js';
export { ProcessService } from './ProcessService.js';

// UI Managers (Phase 12 - DIP)
export { ProcessFormManager } from './ProcessFormManager.js';
export { ModalEventBinder } from './ModalEventBinder.js';
export { ButtonLoadingManager } from './ButtonLoadingManager.js';

// Domain Services (Phase 12 - DIP)
export { AreasConfigService } from './AreasConfigService.js';
export { ProcessWorkflowService } from './ProcessWorkflowService.js';

// Renderers (Phase 10 - OCP)
export { PrendaTrackingRenderer } from './Renderers/PrendaTrackingRenderer.js';
export { AreaCardRenderer } from './Renderers/AreaCardRenderer.js';
export { BadgeRenderer } from './Renderers/BadgeRenderer.js';
export { UpdateRenderer } from './Renderers/UpdateRenderer.js';

// Dependency Injection Container (Phase 11 - DIP)
export { DIContainer } from './DIContainer.js';
export { createContainer, createTestContainer } from './ContainerFactory.js';
