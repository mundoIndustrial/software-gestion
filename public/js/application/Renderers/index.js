/**
 * application/Renderers/index.js
 * 
 * Exporta todos los renderers (OCP - Open/Closed Principle)
 * Fácil agregar nuevos renderers sin tocar el handler principal
 */

export { PrendaTrackingRenderer } from './PrendaTrackingRenderer.js';
export { AreaCardRenderer } from './AreaCardRenderer.js';
export { BadgeRenderer } from './BadgeRenderer.js';
export { UpdateRenderer } from './UpdateRenderer.js';
