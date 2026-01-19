/**
 * loader.js
 * Cargador compatible para que funcione con <script> tradicionales
 * Expone el módulo en window para acceso desde HTML/Blade
 * 
 * Uso en Blade:
 * <script type="module" src="{{ asset('js/modulos/pedidos-recibos/loader.js') }}"></script>
 */

import { PedidosRecibosModule } from './PedidosRecibosModule.js';

// Inicializar módulo
const module = new PedidosRecibosModule();

// Exponer en window para compatibilidad
window.PedidosRecibosModule = PedidosRecibosModule;
window.pedidosRecibosModule = module;

// Exponer API pública compatibilidad con código antiguo
window.openOrderDetailModalWithProcess = (pedidoId, prendaId, tipoRecibo, prendaIndex = null) => {
    return module.abrirRecibo(pedidoId, prendaId, tipoRecibo, prendaIndex);
};

window.cerrarModalRecibos = () => {
    return module.cerrarRecibo();
};

console.log('%c✓ Módulo Pedidos-Recibos cargado correctamente', 'color: #10b981; font-weight: bold; font-size: 14px;');
