/**
 * index.js
 * Punto de entrada del módulo pedidos-recibos
 * Carga y exporta todos los componentes
 */

// Importar módulo principal
import { PedidosRecibosModule } from './PedidosRecibosModule.js';

// Exportar para uso en otros módulos
export { PedidosRecibosModule };
export { ModalManager } from './components/ModalManager.js';
export { CloseButtonManager } from './components/CloseButtonManager.js';
export { NavigationManager } from './components/NavigationManager.js';
export { GalleryManager } from './components/GalleryManager.js';
export { ReceiptRenderer } from './components/ReceiptRenderer.js';
export { ReceiptBuilder } from './utils/ReceiptBuilder.js';
export { Formatters } from './utils/Formatters.js';

console.log('%c[pedidos-recibos] Módulo cargado ✓', 'color: #10b981; font-weight: bold;');
