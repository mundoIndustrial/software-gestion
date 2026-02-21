/**
 * ÍNDICE CENTRAL: modules/index.js
 * Exporta todos los módulos para fácil referencia
 * Principios SOLID: SRP (agregación), DIP (inyección de dependencias)
 */

// Módulos base sin dependencias
// @requires: ninguno
export { default as FormattingModule } from './formatting.js';
export { default as StorageModule } from './storageModule.js';
export { default as NotificationModule } from './notificationModule.js';

// Módulos con dependencias
// @requires: NotificationModule
export { default as UpdatesModule } from './updates.js';

// @requires: UpdatesModule
export { default as DropdownManager } from './dropdownManager.js';
export { default as DiaEntregaModule } from './diaEntregaModule.js';

// @requires: FormattingModule
export { default as RowManager } from './rowManager.js';

// Orquestador principal
// @requires: todos los anteriores
export { default as TableManager } from './tableManager.js';

/**
 * Dependencia de módulos (para referencia):
 * 
 * Nivel 0 (sin dependencias):
 *   - FormattingModule
 *   - StorageModule
 *   - NotificationModule
 * 
 * Nivel 1 (dependen de Nivel 0):
 *   - UpdatesModule (depende de NotificationModule)
 *   - RowManager (depende de FormattingModule)
 * 
 * Nivel 2 (dependen de Nivel 1):
 *   - DropdownManager (depende de UpdatesModule)
 *   - DiaEntregaModule (depende de UpdatesModule)
 * 
 * Nivel 3 (Orquestador):
 *   - TableManager (coordina todos)
 */

