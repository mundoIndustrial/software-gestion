/**
 * loader.js
 * Cargador compatible para que funcione con <script> tradicionales
 * Expone el módulo en window para acceso desde HTML/Blade
 * 
 * Uso en Blade:
 * <script type="module" src="{{ asset('js/modulos/pedidos-recibos/loader.js') }}"></script>
 */

import { PedidosRecibosModule } from './PedidosRecibosModule.js';
import { Formatters } from './utils/Formatters.js';
import { ReceiptRenderer } from './components/ReceiptRenderer.js';

// Inicializar módulo
const module = new PedidosRecibosModule();

// Exponer en window para compatibilidad
window.PedidosRecibosModule = PedidosRecibosModule;
window.pedidosRecibosModule = module;
window.Formatters = Formatters;
window.ReceiptRenderer = ReceiptRenderer;

console.log('[loader.js] ✓ Formatters expuesto a window');
console.log('[loader.js] ✓ ReceiptRenderer expuesto a window');

// Exponer API pública compatibilidad con código antiguo
window.openOrderDetailModalWithProcess = (pedidoId, prendaId, tipoRecibo, prendaIndex = null) => {
    return module.abrirRecibo(pedidoId, prendaId, tipoRecibo, prendaIndex);
};

window.cerrarModalRecibos = () => {
    return module.cerrarRecibo();
};

