/**
 * Tracking Modal Handler - Seguimiento por Prenda
 * Maneja la integración del modal de seguimiento con la vista de órdenes
 * Funcionalidad completa de seguimiento por prenda con áreas y procesos
 */

// Agregar estilos CSS para tabla estilo TNS
const trackingTableStyles = `
.tracking-prenda-table {
  margin: 8px 0;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
  background: white;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  cursor: pointer;
  transition: all 0.2s ease;
}

.tracking-prenda-table:hover {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transform: translateY(-1px);
}

.tracking-table {
  width: 100%;
  border-collapse: collapse;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  font-size: 13px;
}

.tracking-table-header {
  background: linear-gradient(135deg, #1e40af 0%, #3730a3 100%);
  color: white;
  font-weight: 600;
  font-size: 14px;
  text-align: center;
  padding: 12px 8px;
  letter-spacing: 0.5px;
  text-transform: uppercase;
}

.tracking-table-row:nth-child(even) {
  background-color: #f9fafb;
}

.tracking-table-row:hover {
  background-color: #f3f4f6;
}

.tracking-table-label {
  padding: 8px 12px;
  font-weight: 600;
  color: #374151;
  border-right: 1px solid #e5e7eb;
  width: 35%;
  text-align: left;
  background-color: #f8fafc;
}

.tracking-table-value {
  padding: 8px 12px;
  color: #1f2937;
  text-align: left;
  font-weight: 500;
}

.tracking-procesos-lista {
  padding: 8px 12px;
  background: #fef3c7;
  border-left: 3px solid #f59e0b;
}

.tracking-proceso-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 4px 0;
  border-bottom: 1px solid #fde68a;
}

.tracking-proceso-item:last-child {
  border-bottom: none;
}

.proceso-nombre {
  font-weight: 500;
  color: #92400e;
}

.proceso-estado {
  font-size: 11px;
  padding: 2px 6px;
  border-radius: 12px;
  background: #fbbf24;
  color: #78350f;
  font-weight: 600;
}

.tracking-bodega-indicador {
  padding: 8px 12px;
  background: #dcfce7;
  border-left: 3px solid #22c55e;
  color: #166534;
  font-weight: 600;
  font-size: 12px;
  text-align: center;
}

.tracking-seguimiento-badge {
  display: inline-block;
  margin: 2px;
  padding: 3px 8px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.tracking-seguimiento-badge.completado {
  background: #dcfce7;
  color: #166534;
  border: 1px solid #bbf7d0;
}

.tracking-seguimiento-badge.pendiente {
  background: #fef3c7;
  color: #92400e;
  border: 1px solid #fde68a;
}

/* ===== MODAL Y OVERLAY DE PRENDAS ===== */
.tracking-prendas-selector-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
  opacity: 0;
  visibility: hidden;
  transition: all 0.3s ease;
  padding: 20px;
  box-sizing: border-box;
}

.tracking-prendas-selector-overlay.show {
  opacity: 1;
  visibility: visible;
}

.tracking-prendas-selector-content {
  background: white;
  border-radius: 12px;
  max-width: 95vw;
  max-height: 90vh;
  width: 1200px;
  height: auto;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  transform: scale(0.95);
  transition: all 0.3s ease;
  display: flex;
  flex-direction: column;
}

.tracking-prendas-selector-overlay.show .tracking-prendas-selector-content {
  transform: scale(1);
}

.tracking-prendas-selector-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  border-bottom: 1px solid #e5e7eb;
  background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
  border-radius: 12px 12px 0 0;
}

.tracking-prendas-selector-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  border-radius: 8px;
  color: white;
  margin-right: 16px;
}

.tracking-prendas-selector-icon svg {
  width: 20px;
  height: 20px;
}

.tracking-prendas-selector-title {
  font-size: 18px;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
  flex: 1;
}

.tracking-prendas-selector-close {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  background: #ef4444;
  border: none;
  border-radius: 6px;
  color: white;
  cursor: pointer;
  transition: all 0.2s ease;
}

.tracking-prendas-selector-close:hover {
  background: #dc2626;
  transform: scale(1.1);
}

.tracking-prendas-selector-close svg {
  width: 16px;
  height: 16px;
}

.tracking-prendas-selector-body {
  padding: 24px;
  overflow-y: auto;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 20px;
  width: 100%;
  min-height: 0; /* Permitir que el contenedor se encoja */
}

.tracking-prendas-info {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  padding: 16px;
  background: #f8fafc;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.tracking-prendas-info-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.tracking-prendas-info-label {
  font-size: 12px;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.tracking-prendas-info-value {
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
}

.tracking-prendas-list {
  flex: 1;
  display: flex;
  flex-direction: column;
  width: 100%;
  min-height: 0; /* Permitir que el contenedor se encoja */
}

.tracking-prendas-list-title {
  font-size: 16px;
  font-weight: 700;
  color: #1f2937;
  margin: 0 0 16px 0;
  padding-bottom: 8px;
  border-bottom: 2px solid #e5e7eb;
}

.tracking-prendas-selector-container {
  flex: 1;
  width: 100%;
  height: 100%;
  min-height: 0; /* Permitir que el contenedor se encoja */
  overflow: hidden;
  background: white;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

/* ===== TABLA DE PRENDAS (ESTILO REPORTE) ===== */
.prendas-table-container {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  margin: 0;
  width: 100%;
  height: 100%;
  min-height: 0; /* Permitir que el contenedor se encoja */
}

.prendas-report-table {
  width: 100%;
  border-collapse: collapse;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  font-size: 13px;
  background: white;
  table-layout: fixed; /* Distribuir columnas uniformemente */
}

.prendas-report-table thead {
  background: linear-gradient(135deg, #374151 0%, #1f2937 100%);
  color: white;
}

.prendas-report-table th {
  padding: 12px 8px;
  text-align: center;
  font-weight: 600;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-right: 1px solid rgba(255, 255, 255, 0.1);
  vertical-align: middle;
}

.prendas-report-table th:first-child {
  text-align: left;
  width: 25%; /* Prenda - más ancha */
}

.prendas-report-table th:nth-child(2) {
  width: 10%; /* Cantidad */
}

.prendas-report-table th:nth-child(3) {
  width: 25%; /* Procesos - más ancha */
}

.prendas-report-table th:nth-child(4) {
  width: 12%; /* Área */
}

.prendas-report-table th:nth-child(5) {
  width: 12%; /* Estado */
}

.prendas-report-table th:nth-child(6) {
  width: 16%; /* Acciones - más ancha para el botón */
  border-right: none;
}

.prendas-table-row:nth-child(even) {
  background-color: #f9fafb;
}

.prendas-table-row:hover {
  background-color: #f3f4f6;
}

.prendas-table-cell {
  padding: 12px 8px;
  border-bottom: 1px solid #e5e7eb;
  vertical-align: middle;
  text-align: center;
  word-wrap: break-word;
  overflow: hidden;
}

.prendas-table-cell:first-child {
  text-align: left;
}

.prendas-name-cell {
  font-weight: 600;
  color: #1f2937;
}

.prendas-name {
  font-size: 14px;
  margin-bottom: 4px;
}

.bodega-badge {
  display: inline-block;
  font-size: 10px;
  padding: 2px 6px;
  background: #f59e0b;  /* Amarillo */
  color: #92400e;      /* Texto oscuro para contraste */
  border-radius: 10px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.procesos-cell {
  text-align: left;
}

.procesos-info {
  font-size: 11px;
  line-height: 1.4;
  color: #6b7280;
}

.estado-badge {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.estado-badge.estado-completado {
  background: #dcfce7;
  color: #166534;
  border: 1px solid #bbf7d0;
}

.estado-badge.estado-en-ejecución {
  background: #dbeafe;
  color: #1e40af;
  border: 1px solid #93c5fd;
}

.estado-badge.estado-pendiente {
  background: #fef3c7;
  color: #92400e;
  border: 1px solid #fde68a;
}

.estado-badge.estado-sin-procesos {
  background: #f3f4f6;
  color: #6b7280;
  border: 1px solid #d1d5db;
}

.acciones-cell {
  text-align: center;
}

.btn-ver-seguimiento {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 16px;
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 2px 4px rgba(59, 130, 246, 0.1);
  min-width: 80px;
  position: relative;
  overflow: hidden;
}

.btn-ver-seguimiento::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  transition: left 0.5s ease;
}

.btn-ver-seguimiento:hover {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-ver-seguimiento:hover::before {
  left: 100%;
}

.btn-ver-seguimiento:active {
  transform: translateY(0);
  box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
}

.btn-ver-seguimiento svg {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

/* Responsive para pantallas pequeñas */
@media (max-width: 768px) {
  .prendas-report-table {
    font-size: 11px;
  }
  
  .prendas-report-table th {
    padding: 8px 4px;
    font-size: 10px;
  }
  
  .prendas-table-cell {
    padding: 8px 4px;
  }
  
  .prendas-name {
    font-size: 12px;
  }
  
  .btn-ver-seguimiento {
    padding: 6px 12px;
    font-size: 11px;
    min-width: 70px;
    gap: 4px;
  }
  
  .btn-ver-seguimiento svg {
    width: 14px;
    height: 14px;
  }
}

/* Responsive para modal y tabla */
@media (max-width: 1200px) {
  .tracking-prendas-selector-content {
    max-width: 98vw;
    width: 100%;
  }
}

@media (max-width: 768px) {
  .tracking-prendas-selector-overlay {
    padding: 10px;
  }
  
  .tracking-prendas-selector-content {
    max-width: 95vw;
    width: 100%;
    margin: 0;
  }
  
  .tracking-prendas-selector-header {
    padding: 16px 20px;
  }
  
  .tracking-prendas-selector-icon {
    width: 32px;
    height: 32px;
    margin-right: 12px;
  }
  
  .tracking-prendas-selector-icon svg {
    width: 16px;
    height: 16px;
  }
  
  .tracking-prendas-selector-title {
    font-size: 16px;
  }
  
  .tracking-prendas-selector-body {
    padding: 16px;
    gap: 16px;
  }
  
  .tracking-prendas-info {
    grid-template-columns: 1fr;
    gap: 12px;
    padding: 12px;
    width: 100%;
  }
  
  .tracking-prendas-list {
    width: 100%;
    min-height: 0;
  }
  
  .tracking-prendas-selector-container {
    width: 100%;
    height: 100%;
    min-height: 0;
  }
  
  .prendas-table-container {
    width: 100%;
    height: 100%;
    min-height: 0;
  }
  
  .prendas-report-table {
    font-size: 11px;
  }
  
  .prendas-report-table th {
    padding: 8px 4px;
    font-size: 10px;
  }
  
  .prendas-table-cell {
    padding: 8px 4px;
  }
  
  .prendas-name {
    font-size: 12px;
  }
  
  .btn-ver-seguimiento {
    padding: 6px 12px;
    font-size: 11px;
    min-width: 70px;
    gap: 4px;
  }
  
  .btn-ver-seguimiento svg {
    width: 14px;
    height: 14px;
  }
}

@media (max-width: 600px) {
  .tracking-prendas-selector-overlay {
    padding: 5px;
  }
  
  .tracking-prendas-selector-content {
    max-width: 98vw;
    width: 100%;
    border-radius: 8px;
  }
  
  .tracking-prendas-selector-header {
    padding: 12px 16px;
  }
  
  .tracking-prendas-selector-icon {
    width: 28px;
    height: 28px;
    margin-right: 8px;
  }
  
  .tracking-prendas-selector-icon svg {
    width: 14px;
    height: 14px;
  }
  
  .tracking-prendas-selector-title {
    font-size: 14px;
  }
  
  .tracking-prendas-selector-body {
    padding: 12px;
    gap: 12px;
  }
  
  .tracking-prendas-info {
    padding: 8px;
    gap: 8px;
    width: 100%;
  }
  
  .tracking-prendas-list {
    width: 100%;
    min-height: 0;
  }
  
  .tracking-prendas-selector-container {
    width: 100%;
    height: 100%;
    min-height: 0;
  }
  
  .prendas-table-container {
    width: 100%;
    height: 100%;
    min-height: 0;
  }
  
  .tracking-prendas-info-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
  }
  
  .tracking-prendas-list-title {
    font-size: 14px;
    margin: 0 0 12px 0;
    padding-bottom: 6px;
  }
  
  .prendas-report-table {
    font-size: 10px;
  }
  
  .prendas-report-table th {
    padding: 6px 2px;
    font-size: 9px;
  }
  
  .prendas-table-cell {
    padding: 6px 2px;
  }
  
  .prendas-name {
    font-size: 11px;
  }
  
  .btn-ver-seguimiento {
    padding: 4px 6px;
    font-size: 10px;
    min-width: 50px;
    gap: 2px;
  }
  
  .btn-ver-seguimiento svg {
    width: 12px;
    height: 12px;
  }
}

  .prendas-report-table th:first-child {
    width: 30%;
  }
  
  .prendas-report-table th:nth-child(2) {
    width: 12%;
  }
  
  .prendas-report-table th:nth-child(3) {
    width: 20%;
  }
  
  .prendas-report-table th:nth-child(4) {
    width: 14%;
  }
  
  .prendas-report-table th:nth-child(5) {
    width: 12%;
  }
  
  .btn-ver-seguimiento {
    padding: 4px 6px;
    font-size: 10px;
    min-width: 50px;
    gap: 2px;
  }
  
  .btn-ver-seguimiento svg {
    width: 12px;
    height: 12px;
  }
}
  
  .prendas-report-table th:nth-child(2) {
    width: 12%;
  }
  
  .prendas-report-table th:nth-child(3) {
    width: 12%;
  }
  
  .prendas-report-table th:nth-child(4) {
    width: 12%;
  }
  
  .prendas-report-table th:nth-child(5) {
    width: 17%;
  }
  
  .prendas-report-table th:nth-child(6) {
    width: 12%;
  }
  
  .prendas-report-table th:nth-child(7) {
    width: 10%;
  }
  
  .btn-ver-seguimiento {
    padding: 4px 8px;
    font-size: 10px;
    min-width: 60px;
    gap: 2px;
  }
  
  .btn-ver-seguimiento svg {
    width: 12px;
    height: 12px;
  }
}
`;

(function() {
  'use strict';

  let currentOrderData = null;
  let currentPrendaData = null;

  // Inyectar estilos CSS para tabla estilo TNS
  function injectTrackingTableStyles() {
    if (!document.getElementById('tracking-table-styles')) {
      const styleElement = document.createElement('style');
      styleElement.id = 'tracking-table-styles';
      styleElement.textContent = trackingTableStyles;
      document.head.appendChild(styleElement);
    }
  }

  // Inicializar listeners del modal
  function initTrackingModalListeners() {
    // Inyectar estilos
    injectTrackingTableStyles();
    
    // Cerrar modal al hacer clic en el overlay
    const overlay = document.getElementById('trackingModalOverlay');
    if (overlay) {
      overlay.addEventListener('click', closeTrackingModal);
    }

    // Cerrar modal con botón X (si existe)
    const closeBtn = document.querySelector('.tracking-modal-close');
    if (closeBtn) {
      closeBtn.addEventListener('click', closeTrackingModal);
    }

    // Botón de volver a prendas (se configura en setupBackButton)
    const backBtn = document.getElementById('backToPrendasBtn');
    // No agregar event listener aquí, se maneja en setupBackButton()

    // Botón de abrir modal agregar proceso
    const btnOpenAddProcesoModal = document.getElementById('btnOpenAddProcesoModal');
    if (btnOpenAddProcesoModal) {
      btnOpenAddProcesoModal.addEventListener('click', openAddProcesoModal);
    }

    // Botones del modal agregar proceso
    const closeAddProcesoBtn = document.getElementById('closeAddProcesoModal');
    if (closeAddProcesoBtn) {
      closeAddProcesoBtn.addEventListener('click', closeAddProcesoModal);
    }

    const btnCancelAddProceso = document.getElementById('btnCancelAddProceso');
    if (btnCancelAddProceso) {
      btnCancelAddProceso.addEventListener('click', closeAddProcesoModal);
    }

    const btnConfirmAddProceso = document.getElementById('btnConfirmAddProceso');
    if (btnConfirmAddProceso) {
      btnConfirmAddProceso.addEventListener('click', handleAgregarProceso);
    }

    // Cerrar modal al hacer clic en el overlay
    const addProcesoOverlay = document.getElementById('addProcesoOverlay');
    if (addProcesoOverlay) {
      addProcesoOverlay.addEventListener('click', closeAddProcesoModal);
    }

    // Cerrar con ESC
    document.addEventListener('keydown', (e) => {
      const modal = document.getElementById('orderTrackingModal');
      if (e.key === 'Escape' && modal && modal.style.display !== 'none') {
        closeTrackingModal();
      }
    });
  }

  // Cerrar modal
  function closeTrackingModal() {
    const modal = document.getElementById('orderTrackingModal');
    if (modal) {
      modal.classList.remove('show');
      modal.style.display = 'none';
      // Resetear vistas (sin llamar a showPrendasView para evitar recursividad)
      console.log('[closeTrackingModal] Modal de seguimiento cerrado');
    }
  }

  // Función para abrir el modal de agregar proceso
  function openAddProcesoModal() {
    console.log('[openAddProcesoModal] Abriendo modal de agregar proceso');
    const modal = document.getElementById('addProcesoModal');
    console.log('[openAddProcesoModal] Modal encontrado:', !!modal);
    
    if (modal) {
      modal.classList.add('show');
      modal.style.setProperty('display', 'flex', 'important');
      modal.style.setProperty('visibility', 'visible', 'important');
      modal.style.setProperty('opacity', '1', 'important');
      modal.style.setProperty('z-index', '9999', 'important');
      
      console.log('[openAddProcesoModal] Modal configurado y mostrado');
      
      // Asegurar que los botones de cerrar funcionen
      setupAddProcesoModalListeners();
    } else {
      console.error('[openAddProcesoModal] No se encontró el modal addProcesoModal');
    }
  }

  // Configurar listeners del modal agregar proceso
  function setupAddProcesoModalListeners() {
    const closeBtn = document.getElementById('closeAddProcesoModal');
    if (closeBtn) {
      closeBtn.onclick = closeAddProcesoModal;
    }

    const cancelBtn = document.getElementById('btnCancelAddProceso');
    if (cancelBtn) {
      cancelBtn.onclick = closeAddProcesoModal;
    }

    const overlay = document.getElementById('addProcesoOverlay');
    if (overlay) {
      overlay.onclick = closeAddProcesoModal;
    }
  }

  // Función para cerrar el modal de agregar proceso
  function closeAddProcesoModal() {
    const modal = document.getElementById('addProcesoModal');
    if (modal) {
      modal.classList.remove('show');
      modal.style.display = 'none';
    }
  }

  // Configurar botón volver
  function setupBackButton() {
    const backBtn = document.getElementById('backToPrendasBtn');
    if (backBtn) {
      backBtn.onclick = showPrendasView;
      console.log('[setupBackButton] Botón volver configurado');
    } else {
      console.warn('[setupBackButton] Botón volver no encontrado');
    }
  }

  // Abrir selector de prendas (overlay)
  window.openOrderTracking = async function(orderId) {
    try {
      console.log('[openOrderTracking] Abriendo selector de prendas para orden:', orderId);
      
      // Cargar datos básicos del pedido
      await loadOrderBasicData(orderId);
      
      // Cargar prendas con seguimiento
      await loadPrendasWithTracking(orderId);
      
      // Mostrar overlay de prendas
      showPrendasSelector();
      
    } catch (error) {
      console.error('[openOrderTracking] Error:', error);
      showError('Error al cargar datos de seguimiento');
    }
  };

  // Cargar datos básicos del pedido
  async function loadOrderBasicData(orderId) {
    try {
      const response = await fetch(`/registros/${orderId}/recibos-datos`);
      if (!response.ok) throw new Error('Error al cargar datos del pedido');
      
      const data = await response.json();
      currentOrderData = data;
      
      // Actualizar información del pedido en el modal
      updateOrderInfo(data);
      
    } catch (error) {
      console.error('[loadOrderBasicData] Error:', error);
      throw error;
    }
  }

  // Actualizar información del pedido en el modal y selector
  function updateOrderInfo(orderData) {
    console.log('[updateOrderInfo] Datos recibidos:', orderData);
    console.log('[updateOrderInfo] numero_pedido:', orderData.numero_pedido);
    console.log('[updateOrderInfo] cliente:', orderData.cliente);
    console.log('[updateOrderInfo] estado:', orderData.estado);
    
    // Actualizar modal principal
    console.log('[updateOrderInfo] Campos de fecha disponibles:', {
      fecha_creacion: orderData.fecha_creacion,
      fecha_de_creacion_de_orden: orderData.fecha_de_creacion_de_orden,
      created_at: orderData.created_at,
      fecha_estimada_entrega: orderData.fecha_estimada_entrega
    });
    
    document.getElementById('trackingOrderNumber').textContent = orderData.numero_pedido || '-';
    document.getElementById('trackingOrderClient').textContent = orderData.cliente || '-';
    document.getElementById('trackingOrderStatus').textContent = orderData.estado || '-';
    document.getElementById('trackingOrderDate').textContent = formatDate(orderData.fecha_creacion || orderData.fecha_de_creacion_de_orden || orderData.created_at) || '-';
    document.getElementById('trackingEstimatedDate').textContent = formatDate(orderData.fecha_estimada_entrega) || '-';
    document.getElementById('trackingTotalDays').textContent = orderData.total_dias || '0';

    // Actualizar selector de prendas
    const selectorOrderNumber = document.getElementById('selectorOrderNumber');
    const selectorOrderClient = document.getElementById('selectorOrderClient');
    const selectorOrderStatus = document.getElementById('selectorOrderStatus');
    const selectorOrderStartDate = document.getElementById('selectorOrderStartDate');
    const selectorOrderEstimatedDate = document.getElementById('selectorOrderEstimatedDate');
    
    console.log('[updateOrderInfo] Elementos encontrados:', {
      selectorOrderNumber: !!selectorOrderNumber,
      selectorOrderClient: !!selectorOrderClient,
      selectorOrderStatus: !!selectorOrderStatus,
      selectorOrderStartDate: !!selectorOrderStartDate,
      selectorOrderEstimatedDate: !!selectorOrderEstimatedDate
    });
    
    // Actualizar información del modal principal
    const trackingOrderNumber = document.getElementById('trackingOrderNumber');
    const trackingOrderClient = document.getElementById('trackingOrderClient');
    const trackingOrderStatus = document.getElementById('trackingOrderStatus');
    const trackingOrderRecibo = document.getElementById('trackingOrderRecibo');
    
    console.log('[updateOrderInfo] Elementos encontrados:', {
      selectorOrderNumber: !!selectorOrderNumber,
      selectorOrderClient: !!selectorOrderClient,
      selectorOrderStatus: !!selectorOrderStatus,
      selectorOrderEstimatedDate: !!selectorOrderEstimatedDate,
      trackingOrderNumber: !!trackingOrderNumber,
      trackingOrderClient: !!trackingOrderClient,
      trackingOrderStatus: !!trackingOrderStatus,
      trackingOrderRecibo: !!trackingOrderRecibo
    });
    
    // Actualizar selector
    if (selectorOrderNumber) {
      selectorOrderNumber.textContent = orderData.numero_pedido || '-';
    }
    if (selectorOrderClient) {
      selectorOrderClient.textContent = orderData.cliente || '-';
    }
    if (selectorOrderStatus) {
      selectorOrderStatus.textContent = orderData.estado || '-';
    }
    if (selectorOrderStartDate) {
      // Actualizar fecha de inicio
      let fechaInicio = orderData.fecha_creacion || orderData.fecha_de_creacion_de_orden || orderData.created_at;
      
      if (fechaInicio) {
        // Formatear fecha
        let fechaFormateada = '';
        if (typeof fechaInicio === 'string') {
          try {
            const date = new Date(fechaInicio);
            if (!isNaN(date.getTime())) {
              fechaFormateada = date.toLocaleDateString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
              });
            } else {
              fechaFormateada = fechaInicio;
            }
          } catch (e) {
            fechaFormateada = fechaInicio;
          }
        } else if (fechaInicio instanceof Date) {
          fechaFormateada = fechaInicio.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
          });
        } else if (fechaInicio && fechaInicio.date) {
          fechaFormateada = new Date(fechaInicio.date).toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
          });
        }
        
        selectorOrderStartDate.textContent = fechaFormateada || '-';
      } else {
        selectorOrderStartDate.textContent = '-';
      }
    }
    if (selectorOrderEstimatedDate) {
      // Actualizar fecha estimada de entrega
      let fechaEstimada = orderData.fecha_estimada_de_entrega;
      
      if (fechaEstimada) {
        // Formatear fecha datetime
        let fechaFormateada = '';
        if (typeof fechaEstimada === 'string') {
          // Si es string, intentar parsear y formatear
          try {
            const date = new Date(fechaEstimada);
            if (!isNaN(date.getTime())) {
              fechaFormateada = date.toLocaleDateString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
              });
            } else {
              fechaFormateada = fechaEstimada;
            }
          } catch (e) {
            fechaFormateada = fechaEstimada;
          }
        } else if (fechaEstimada instanceof Date) {
          // Si es un objeto Date, formatearlo
          fechaFormateada = fechaEstimada.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
          });
        } else if (fechaEstimada && fechaEstimada.date) {
          // Si es un objeto Carbon/Laravel
          fechaFormateada = new Date(fechaEstimada.date).toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
          });
        }
        
        selectorOrderEstimatedDate.textContent = fechaFormateada || '-';
      } else {
        selectorOrderEstimatedDate.textContent = '-';
      }
    }
    
    // Actualizar modal principal
    if (trackingOrderNumber) {
      trackingOrderNumber.textContent = orderData.numero_pedido || '-';
    }
    if (trackingOrderClient) {
      trackingOrderClient.textContent = orderData.cliente || '-';
    }
    if (trackingOrderStatus) {
      trackingOrderStatus.textContent = orderData.estado || '-';
    }
    if (trackingOrderRecibo) {
      // Obtener el número de recibo más reciente de todo el pedido
      let ultimoReciboGeneral = '-';
      
      console.log('[updateOrderInfo] Buscando recibo en orderData:', {
        prendas_count: orderData.prendas ? orderData.prendas.length : 0,
        prendas: orderData.prendas
      });
      
      // Si tenemos datos de prendas con consecutivos, buscar el más reciente
      if (orderData.prendas && orderData.prendas.length > 0) {
        let reciboMasReciente = null;
        let fechaMasReciente = null;
        let totalRecibosEncontrados = 0;
        
        // Buscar entre todas las prendas el recibo más reciente por created_at
        for (const prenda of orderData.prendas) {
          console.log('[updateOrderInfo] Analizando prenda:', {
            prenda_id: prenda.id,
            prenda_nombre: prenda.nombre_prenda,
            consecutivos: prenda.consecutivos
          });
          
          // Los consecutivos vienen como objeto, no como array
          if (prenda.consecutivos && typeof prenda.consecutivos === 'object') {
            // Convertir el objeto de consecutivos a un array para procesar
            const recibosArray = [];
            for (const [tipo, numero] of Object.entries(prenda.consecutivos)) {
              if (numero !== null && numero !== undefined) {
                recibosArray.push({
                  tipo_recibo: tipo,
                  consecutivo_actual: numero,
                  activo: 1,
                  // Como no tenemos created_at, usamos el tipo como criterio
                  created_at: new Date().toISOString() // Todos tendrán la misma fecha, se ordenará por tipo
                });
              }
            }
            
            console.log('[updateOrderInfo] Recibos convertidos a array:', recibosArray);
            
            for (const recibo of recibosArray) {
              console.log('[updateOrderInfo] Analizando recibo:', recibo);
              totalRecibosEncontrados++;
              
              if (recibo.activo === 1) {
                // Como no tenemos created_at real, usamos el tipo como criterio de ordenamiento
                // COSTURA-BODEGA > COSTURA > otros (orden alfabético inverso)
                const prioridadTipo = {
                  'COSTURA-BODEGA': 3,
                  'COSTURA': 2,
                  'ESTAMPADO': 1,
                  'BORDADO': 1,
                  'DTF': 1,
                  'SUBLIMADO': 1,
                  'REFLECTIVO': 1
                };
                
                const prioridadActual = prioridadTipo[recibo.tipo_recibo] || 0;
                const prioridadMejor = reciboMasReciente ? (prioridadTipo[reciboMasReciente.tipo_recibo] || 0) : 0;
                
                if (!reciboMasReciente || prioridadActual > prioridadMejor) {
                  reciboMasReciente = recibo;
                  console.log('[updateOrderInfo] Recibo con mayor prioridad encontrado:', reciboMasReciente);
                }
              }
            }
          }
        }
        
        console.log('[updateOrderInfo] Resumen de búsqueda:', {
          total_recibos_encontrados: totalRecibosEncontrados,
          recibo_mas_reciente: reciboMasReciente
        });
        
        if (reciboMasReciente) {
          ultimoReciboGeneral = `${reciboMasReciente.tipo_recibo} #${reciboMasReciente.consecutivo_actual}`;
        }
      }
      
      console.log('[updateOrderInfo] Resultado final para trackingOrderRecibo:', ultimoReciboGeneral);
      trackingOrderRecibo.textContent = ultimoReciboGeneral;
    }
    if (selectorOrderEstimatedDate) {
      selectorOrderEstimatedDate.style.color = '#1f2937';
      selectorOrderEstimatedDate.style.fontWeight = '600';
    } else {
      selectorOrderEstimatedDate.textContent = 'No definida';
      selectorOrderEstimatedDate.style.color = '#9ca3af';
      selectorOrderEstimatedDate.style.fontWeight = '400';
    }
  }

  // Cargar prendas con seguimiento
  async function loadPrendasWithTracking(orderId) {
    try {
      console.log('[loadPrendasWithTracking] Cargando prendas para orden:', orderId);
      
      const response = await fetch(`/registros/${orderId}/seguimiento-prenda`);
      if (!response.ok) throw new Error('Error al cargar seguimiento de prendas');
      
      const data = await response.json();
      console.log('[loadPrendasWithTracking] Datos recibidos:', data);
      
      // Renderizar prendas
      renderPrendas(data.prendas || []);
      
    } catch (error) {
      console.error('[loadPrendasWithTracking] Error:', error);
      throw error;
    }
  }

  // Renderizar tabla única de prendas en el overlay
  function renderPrendas(prendas) {
    const container = document.getElementById('trackingPrendasSelectorContainer');
    if (!container) return;
    
    console.log('[renderPrendas] Renderizando tabla de prendas:', prendas.length);
    
    container.innerHTML = '';
    
    if (prendas.length === 0) {
      container.innerHTML = `
        <div class="tracking-no-prendas">
          <p>No hay prendas registradas para este pedido</p>
        </div>
      `;
      return;
    }
    
    // Crear tabla única con todas las prendas
    const tableHtml = createPrendasTable(prendas);
    container.innerHTML = tableHtml;
    
    // Actualizar fecha estimada de entrega del pedido
    updateEstimatedDeliveryDate();
  }

  // Actualizar fecha estimada de entrega del pedido
  function updateEstimatedDeliveryDate() {
    const fechaEstimadaElement = document.getElementById('selectorOrderEstimatedDate');
    if (!fechaEstimadaElement || !currentOrderData) return;
    
    // Obtener fecha estimada del pedido (campo correcto)
    let fechaEstimada = currentOrderData.fecha_estimada_de_entrega;
    
    if (fechaEstimada) {
      // Formatear fecha datetime
      let fechaFormateada = '';
      if (typeof fechaEstimada === 'string') {
        // Si es string, intentar parsear y formatear
        try {
          const date = new Date(fechaEstimada);
          if (!isNaN(date.getTime())) {
            fechaFormateada = date.toLocaleDateString('es-ES', {
              day: '2-digit',
              month: '2-digit',
              year: 'numeric'
            });
          } else {
            fechaFormateada = fechaEstimada;
          }
        } catch (e) {
          fechaFormateada = fechaEstimada;
        }
      } else if (fechaEstimada instanceof Date) {
        // Si es un objeto Date, formatearlo
        fechaFormateada = fechaEstimada.toLocaleDateString('es-ES', {
          day: '2-digit',
          month: '2-digit',
          year: 'numeric'
        });
      } else if (fechaEstimada && fechaEstimada.date) {
        // Si es un objeto Carbon/Laravel
        fechaFormateada = new Date(fechaEstimada.date).toLocaleDateString('es-ES', {
          day: '2-digit',
          month: '2-digit',
          year: 'numeric'
        });
      }
      
      fechaEstimadaElement.textContent = fechaFormateada;
      fechaEstimadaElement.style.color = '#1f2937';
      fechaEstimadaElement.style.fontWeight = '600';
    } else {
      fechaEstimadaElement.textContent = 'No definida';
      fechaEstimadaElement.style.color = '#9ca3af';
      fechaEstimadaElement.style.fontWeight = '400';
    }
  }

  // Crear tabla HTML con todas las prendas
  function createPrendasTable(prendas) {
    let tableHtml = `
      <div class="prendas-table-container">
        <table class="prendas-report-table">
          <thead>
            <tr>
              <th>Prenda</th>
              <th>Cantidad</th>
              <th>Procesos</th>
              <th>Área</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
    `;
    
    // Almacenar las prendas globalmente para acceso desde onclick
    window.prendasData = prendas;
    
    prendas.forEach((prenda, index) => {
      // Extraer información de la prenda
      const nombrePrenda = prenda.nombre_prenda || `Prenda ${index + 1}`;
      const cantidad = prenda.cantidad || 0;
      const totalProcesos = prenda.total_procesos || 0;
      
      // Extraer procesos
      let procesosInfo = '-';
      if (prenda.procesos && prenda.procesos.length > 0) {
        procesosInfo = prenda.procesos.map(p => {
          const tipoProceso = p.tipo_proceso;
          const nombre = tipoProceso?.nombre || 'Proceso';
          const estado = p.estado || 'PENDIENTE';
          return `${nombre} (${estado})`;
        }).join(', ');
      }
      
      // Extraer área basada en el proceso más reciente
      let area = '-';
      if (prenda.ultimo_proceso_area) {
        // Si ya viene el área del último proceso, usarla
        area = prenda.ultimo_proceso_area;
      } else if (prenda.area && prenda.area.trim() !== '') {
        // Si tiene área asignada directamente, usarla
        area = prenda.area;
      }
      
      // Determinar estado general
      let estadoGeneral = 'Sin procesos';
      if (prenda.procesos && prenda.procesos.length > 0) {
        const estados = prenda.procesos.map(p => p.estado || 'PENDIENTE');
        if (estados.every(e => e === 'COMPLETADO')) {
          estadoGeneral = 'Completado';
        } else if (estados.some(e => e === 'EN EJECUCIÓN')) {
          estadoGeneral = 'En ejecución';
        } else {
          estadoGeneral = 'Pendiente';
        }
      }
      
      // Badge de bodega
      const bodegaBadge = prenda.de_bodega ? '<span class="bodega-badge">Bodega</span>' : '';
      
      tableHtml += `
        <tr class="prendas-table-row" data-prenda-index="${index}">
          <td class="prendas-table-cell prendas-name-cell">
            <div class="prendas-name">${nombrePrenda}</div>
            ${bodegaBadge}
          </td>
          <td class="prendas-table-cell">${cantidad}</td>
          <td class="prendas-table-cell procesos-cell">
            <div class="procesos-info">${procesosInfo}</div>
          </td>
          <td class="prendas-table-cell">${area}</td>
          <td class="prendas-table-cell">
            <span class="estado-badge estado-${estadoGeneral.toLowerCase().replace(' ', '-')}">${estadoGeneral}</span>
          </td>
          <td class="prendas-table-cell acciones-cell">
            <button class="btn-ver-seguimiento" onclick="showPrendaTrackingFromTable(${index})" title="Ver seguimiento detallado">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 11l3 3L22 4"></path>
                <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"></path>
              </svg>
              Ver
            </button>
          </td>
        </tr>
      `;
    });
    
    tableHtml += `
          </tbody>
        </table>
      </div>
    `;
    
    return tableHtml;
  }

  // Mostrar selector de prendas (overlay)
  function showPrendasSelector() {
    const overlay = document.getElementById('trackingPrendasSelectorOverlay');
    console.log('[showPrendasSelector] Overlay encontrado:', !!overlay);
    if (overlay) {
      overlay.classList.add('show');
      console.log('[showPrendasSelector] Overlay mostrado');
    } else {
      console.error('[showPrendasSelector] No se encontró el overlay');
    }
  }

  // Cerrar selector de prendas
  window.cerrarSelectorPrendas = function() {
    const overlay = document.getElementById('trackingPrendasSelectorOverlay');
    if (overlay) {
      overlay.classList.remove('show');
    }
  };

  // Crear tabla simple de prenda (estilo TNS)
  function createPrendaCard(prenda, index) {
    const card = document.createElement('div');
    card.className = 'tracking-prenda-table';
    
    // Añadir event listener con debug
    card.addEventListener('click', function(e) {
      console.log('[createPrendaCard] Click en tabla de prenda:', prenda);
      e.preventDefault();
      e.stopPropagation();
      showPrendaTracking(prenda);
    });
    
    const seguimientosHtml = renderSeguimientosBadges(prenda.seguimientos || {});
    const areasHtml = renderAreasBadges(prenda.seguimientos_por_area || {});
    
    // Construir HTML de procesos en formato de fila
    let procesosHtml = '';
    if (prenda.procesos && prenda.procesos.length > 0) {
      procesosHtml = '<tr><td colspan="2"><div class="tracking-procesos-lista">';
      prenda.procesos.forEach(proceso => {
        // Acceder correctamente a los datos del tipo_proceso
        const tipoProceso = proceso.tipo_proceso;
        const procesoNombre = tipoProceso?.nombre || 'Proceso';
        const procesoEstado = proceso.estado || 'PENDIENTE';
        
        console.log('[createPrendaCard] Proceso:', proceso);
        console.log('[createPrendaCard] TipoProceso:', tipoProceso);
        
        procesosHtml += `
          <div class="tracking-proceso-item">
            <span class="proceso-nombre">${procesoNombre}</span>
            <span class="proceso-estado">${procesoEstado}</span>
          </div>
        `;
      });
      procesosHtml += '</div></td></tr>';
    }

    // Badge de bodega si aplica
    let bodegaBadge = '';
    if (prenda.de_bodega) {
      bodegaBadge = '<tr><td colspan="2"><div class="tracking-bodega-indicador">Se saca de bodega</div></td></tr>';
    }

    card.innerHTML = `
      <table class="tracking-table">
        <thead>
          <tr>
            <th colspan="2" class="tracking-table-header">
              ${prenda.nombre_prenda || `Prenda ${index + 1}`}
            </th>
          </tr>
        </thead>
        <tbody>
          <tr class="tracking-table-row">
            <td class="tracking-table-label">Cantidad:</td>
            <td class="tracking-table-value">${prenda.cantidad || 0}</td>
          </tr>
          <tr class="tracking-table-row">
            <td class="tracking-table-label">Procesos:</td>
            <td class="tracking-table-value">${prenda.total_procesos || 0}</td>
          </tr>
          ${procesosHtml}
          ${bodegaBadge}
          ${seguimientosHtml ? `<tr><td colspan="2">${seguimientosHtml}</td></tr>` : ''}
          ${areasHtml ? `<tr><td colspan="2">${areasHtml}</td></tr>` : ''}
        </tbody>
      </table>
    `;
    
    return card;
  }

  // Renderizar badges de seguimientos por tipo de recibo
  function renderSeguimientosBadges(seguimientos) {
    if (Object.keys(seguimientos).length === 0) {
      return '';
    }
    
    let badgesHtml = '<div class="tracking-prenda-seguimientos">';
    
    Object.entries(seguimientos).forEach(([tipo, data]) => {
      const statusClass = data.tiene_disponibles ? 'pendiente' : 'completado';
      
      badgesHtml += `
        <span class="tracking-seguimiento-badge ${statusClass}">
          ${tipo}: ${data.consecutivo_actual}/${data.consecutivo_inicial}
        </span>
      `;
    });
    
    badgesHtml += '</div>';
    return badgesHtml;
  }

  // Renderizar badges de áreas/procesos
  function renderAreasBadges(areas) {
    if (Object.keys(areas).length === 0) {
      return '';
    }
    
    let badgesHtml = '<div class="tracking-prenda-areas">';
    
    Object.entries(areas).forEach(([area, data]) => {
      const statusClass = data.esta_activo ? 'pendiente' : 'completado';
      
      badgesHtml += `
        <span class="tracking-seguimiento-badge ${statusClass}">
          ${area}: ${data.estado}
        </span>
      `;
    });
    
    badgesHtml += '</div>';
    return badgesHtml;
  }

  // Mostrar seguimiento de una prenda específica desde la tabla
  window.showPrendaTrackingFromTable = async function(index) {
    try {
      console.log('[showPrendaTrackingFromTable] INICIO - Índice:', index);
      
      // Obtener la prenda desde el array global
      const prenda = window.prendasData[index];
      if (!prenda) {
        console.error('[showPrendaTrackingFromTable] Prenda no encontrada en índice:', index);
        return;
      }
      
      console.log('[showPrendaTrackingFromTable] Prenda encontrada:', prenda);
      
      // Llamar a la función original con el objeto prenda
      await showPrendaTracking(prenda);
      
    } catch (error) {
      console.error('[showPrendaTrackingFromTable] Error:', error);
      showError('Error al cargar seguimiento de la prenda');
    }
  };

  // Mostrar seguimiento de una prenda específica
  window.showPrendaTracking = async function(prenda) {
    try {
      console.log('[showPrendaTracking] INICIO - Mostrando seguimiento para prenda:', prenda);
      
      currentPrendaData = prenda;
      
      // Cerrar overlay de prendas
      console.log('[showPrendaTracking] Cerrando overlay selector...');
      cerrarSelectorPrendas();
      
      // Mostrar modal de seguimiento
      console.log('[showPrendaTracking] Buscando modal...');
      const modal = document.getElementById('orderTrackingModal');
      if (modal) {
        console.log('[showPrendaTracking] Modal encontrado, agregando clase show...');
        modal.classList.add('show');
        
        // FORZAR ESTILO DIRECTAMENTE CON JAVASCRIPT
        modal.style.setProperty('display', 'flex', 'important');
        modal.style.setProperty('visibility', 'visible', 'important');
        modal.style.setProperty('opacity', '1', 'important');
        modal.style.setProperty('z-index', '9998', 'important');
        modal.style.setProperty('position', 'fixed', 'important');
        modal.style.setProperty('top', '0', 'important');
        modal.style.setProperty('left', '0', 'important');
        modal.style.setProperty('width', '100vw', 'important');
        modal.style.setProperty('height', '100vh', 'important');
        modal.style.setProperty('background', 'rgba(0, 0, 0, 0.5)', 'important');
        modal.style.setProperty('align-items', 'center', 'important');
        modal.style.setProperty('justify-content', 'center', 'important');
        
        // Asegurar que el botón volver funcione
        setupBackButton();
        
        console.log('[showPrendaTracking] Modal mostrado con estilos forzados');
        
        // Debug visual - verificar estado del modal
        setTimeout(() => {
          const modalElement = document.getElementById('orderTrackingModal');
          const computedStyle = window.getComputedStyle(modalElement);
          console.log('[showPrendaTracking] DEBUG - Estado del modal:', {
            display: computedStyle.display,
            visibility: computedStyle.visibility,
            opacity: computedStyle.opacity,
            zIndex: computedStyle.zIndex,
            hasClass: modalElement.classList.contains('show'),
            inlineDisplay: modalElement.style.display,
            inlineVisibility: modalElement.style.visibility
          });
        }, 100);
      } else {
        console.error('[showPrendaTracking] Modal no encontrado');
        return;
      }
      
      // Ocultar vista de prendas y mostrar timeline
      console.log('[showPrendaTracking] Actualizando vistas...');
      document.getElementById('trackingPrendasContainer').parentElement.style.display = 'none';
      document.getElementById('trackingTimelineSection').style.display = 'block';
      
      // Actualizar nombre de la prenda y número de recibo
      console.log('[showPrendaTracking] Actualizar nombre de la prenda y número de recibo');
      
      const nombreElement = document.getElementById('trackingPrendaName');
      if (nombreElement) {
        nombreElement.textContent = prenda.nombre_prenda || `Prenda ${prenda.id}`;
      }
      
      // Actualizar el header del recibo con el número más reciente
      const reciboHeaderElement = document.getElementById('trackingPrendaReciboHeader');
      if (reciboHeaderElement) {
        // Mostrar el número de recibo más reciente
        const numeroRecibo = prenda.ultimo_recibo_numero;
        if (numeroRecibo && numeroRecibo !== '-') {
          reciboHeaderElement.textContent = `Recibo #${numeroRecibo}`;
        } else {
          reciboHeaderElement.textContent = 'Sin recibo';
        }
      }
      
      // Determinar número de recibo desde la tabla consecutivos_recibos_pedidos
      let numeroRecibo = 'Sin recibo';
      if (prenda.consecutivos && prenda.consecutivos.length > 0) {
        // Buscar el primer recibo activo
        const reciboActivo = prenda.consecutivos.find(r => r.activo === 1);
        if (reciboActivo) {
          numeroRecibo = `${reciboActivo.tipo_recibo} #${reciboActivo.consecutivo_actual}`;
        } else if (prenda.consecutivos[0]) {
          // Si no hay activo, tomar el primero
          const primerRecibo = prenda.consecutivos[0];
          numeroRecibo = `${primerRecibo.tipo_recibo} #${primerRecibo.consecutivo_actual}`;
        }
      }
      
      // Actualizar tanto el subtítulo del header como el del timeline
      if (reciboHeaderElement) {
        reciboHeaderElement.textContent = numeroRecibo;
      }
      
      const reciboElement = document.getElementById('trackingPrendaRecibo');
      if (reciboElement) {
        reciboElement.textContent = numeroRecibo;
      }
      
      // Renderizar timeline de seguimiento
      console.log('[showPrendaTracking] Renderizando timeline...');
      renderPrendaTrackingTimeline(prenda);
      
      console.log('[showPrendaTracking] FINALIZADO - Seguimiento mostrado exitosamente');
      
    } catch (error) {
      console.error('[showPrendaTracking] Error:', error);
      showError('Error al cargar seguimiento de la prenda');
    }
  };

  // Renderizar timeline de seguimiento de prenda
  function renderPrendaTrackingTimeline(prenda) {
    const container = document.getElementById('trackingTimelineContainer');
    if (!container) return;

    console.log('[renderPrendaTrackingTimeline] Renderizando timeline para prenda:', prenda);
    console.log('[renderPrendaTrackingTimeline] Seguimientos por área en prenda:', prenda.seguimientos_por_area);

    // Botón de volver (eliminado - ya está en el header)
    container.innerHTML = '';

    // Renderizar seguimientos por área (procesos de producción)
    renderSeguimientosPorArea(prenda, container);

    // Renderizar seguimientos por tipo de recibo (ELIMINADO - no mostrar recibos en modal de seguimiento)
    // renderSeguimientosPorTipo(prenda, container);

    // Si no hay seguimientos por área, mostrar mensaje
    if (!prenda.seguimientos_por_area || Object.keys(prenda.seguimientos_por_area).length === 0) {
      renderNoSeguimiento(container);
    }
  }

  // Renderizar seguimientos por área (procesos)
  function renderSeguimientosPorArea(prenda, container) {
    const seguimientosPorArea = prenda.seguimientos_por_area || {};
    if (Object.keys(seguimientosPorArea).length > 0) {
      const seguimientosTitle = document.createElement('h4');
      seguimientosTitle.textContent = 'Seguimiento por Áreas/Procesos';
      seguimientosTitle.style.marginTop = '24px';
      container.appendChild(seguimientosTitle);
      
      Object.entries(seguimientosPorArea).forEach(([area, data]) => {
        const areaCard = createAreaCard(area, data);
        container.appendChild(areaCard);
      });
    }
  }

  // Renderizar seguimientos por tipo de recibo
  function renderSeguimientosPorTipo(prenda, container) {
    const seguimientosPorTipo = prenda.seguimientos || {};
    if (Object.keys(seguimientosPorTipo).length > 0) {
      const recibosTitle = document.createElement('h4');
      recibosTitle.textContent = 'Seguimiento por Tipo de Recibo';
      recibosTitle.style.marginTop = '24px';
      container.appendChild(recibosTitle);
      
      Object.entries(seguimientosPorTipo).forEach(([tipo, data]) => {
        const seguimientoCard = createSeguimientoCard(tipo, data);
        container.appendChild(seguimientoCard);
      });
    }
  }

  // Mostrar mensaje si no hay seguimientos
  function renderNoSeguimiento(container) {
    const noSeguimiento = document.createElement('div');
    noSeguimiento.className = 'tracking-no-seguimiento';
    noSeguimiento.innerHTML = '<p>No hay seguimientos registrados para esta prenda</p>';
    container.appendChild(noSeguimiento);
  }

  // Manejar eliminación de proceso
  window.handleEliminarProceso = async function(procesoId, areaName, event) {
    // Detener propagación para evitar que se cierre el modal
    if (event) {
      event.stopPropagation();
    }
    
    // Mostrar modal de confirmación
    showConfirmDeleteModal(procesoId, areaName);
  };

  // Mostrar modal de confirmación para eliminar
  function showConfirmDeleteModal(procesoId, areaName) {
    console.log('[showConfirmDeleteModal] Mostrando confirmación para eliminar:', { procesoId, areaName });
    
    const modal = document.getElementById('confirmDeleteModal');
    const processNameSpan = document.getElementById('deleteProcessName');
    
    if (modal && processNameSpan) {
      // Establecer el nombre del proceso
      processNameSpan.textContent = areaName;
      
      // Mostrar el modal
      modal.classList.add('show');
      modal.style.setProperty('display', 'flex', 'important');
      modal.style.setProperty('visibility', 'visible', 'important');
      modal.style.setProperty('opacity', '1', 'important');
      
      // Guardar el ID del proceso a eliminar
      window.processToDelete = { id: procesoId, name: areaName };
      
      // Configurar listeners
      setupConfirmDeleteModalListeners();
      
      console.log('[showConfirmDeleteModal] Modal de confirmación mostrado');
    } else {
      console.error('[showConfirmDeleteModal] No se encontró el modal o el span del nombre');
    }
  }

  // Configurar listeners del modal de confirmación
  function setupConfirmDeleteModalListeners() {
    // Botón cancelar
    const btnCancel = document.getElementById('btnCancelDelete');
    if (btnCancel) {
      btnCancel.onclick = closeConfirmDeleteModal;
    }
    
    // Botón cerrar (X)
    const btnClose = document.getElementById('closeConfirmDeleteModal');
    if (btnClose) {
      btnClose.onclick = closeConfirmDeleteModal;
    }
    
    // Botón confirmar eliminar
    const btnConfirm = document.getElementById('btnConfirmDelete');
    if (btnConfirm) {
      btnConfirm.onclick = executeDeleteProcess;
    }
    
    // Cerrar al hacer clic en el overlay
    const overlay = document.querySelector('.confirm-delete-overlay');
    if (overlay) {
      overlay.onclick = closeConfirmDeleteModal;
    }
  }

  // Cerrar modal de confirmación
  function closeConfirmDeleteModal() {
    const modal = document.getElementById('confirmDeleteModal');
    if (modal) {
      modal.classList.remove('show');
      modal.style.display = 'none';
      window.processToDelete = null;
    }
  }

  // Ejecutar la eliminación del proceso
  async function executeDeleteProcess() {
    if (!window.processToDelete) return;
    
    const { id: procesoId, name: areaName } = window.processToDelete;
    
    try {
      console.log('[executeDeleteProcess] Eliminando proceso:', { procesoId, areaName });

      const response = await fetch('/seguimiento-proceso/' + procesoId, {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        }
      });

      if (!response.ok) {
        throw new Error('Error al eliminar proceso');
      }

      const result = await response.json();
      console.log('[executeDeleteProcess] Proceso eliminado:', result);

      // Cerrar modal de confirmación
      closeConfirmDeleteModal();

      // Recargar seguimientos de la prenda
      console.log('[executeDeleteProcess] Recargando seguimientos para orden:', currentOrderData.id);
      await loadPrendasWithTracking(currentOrderData.id);
      
      console.log('[executeDeleteProcess] Seguimientos recargados, currentPrendaData:', currentPrendaData);
      
      // Actualizar vista actual
      if (currentPrendaData) {
        console.log('[executeDeleteProcess] Actualizando timeline con currentPrendaData:', currentPrendaData);
        renderPrendaTrackingTimeline(currentPrendaData);
      } else {
        console.log('[executeDeleteProcess] No hay currentPrendaData, intentando obtener del DOM');
        // Si no hay currentPrendaData, intentar obtener la primera prenda del DOM
        const prendaCards = document.querySelectorAll('.prenda-card');
        if (prendaCards.length > 0) {
          const firstCard = prendaCards[0];
          const prendaData = {
            id: parseInt(firstCard.dataset.prendaId),
            nombre_prenda: firstCard.querySelector('.prenda-name')?.textContent,
          };
          console.log('[executeDeleteProcess] Usando prendaData del DOM:', prendaData);
          renderPrendaTrackingTimeline(prendaData);
        }
      }

      // Mostrar mensaje de éxito
      showSuccess('Proceso eliminado correctamente');

    } catch (error) {
      console.error('[executeDeleteProcess] Error:', error);
      showError('Error al eliminar proceso: ' + error.message);
      closeConfirmDeleteModal();
    }
  }

  // Manejar edición de proceso
  window.handleEditarProceso = function(procesoId, areaName, processData, event) {
    // Detener propagación para evitar que se cierre el modal
    if (event) {
      event.stopPropagation();
    }
    
    console.log('[handleEditarProceso] Editando proceso:', { procesoId, areaName, processData });
    
    // Verificar si los elementos del formulario existen
    const procesoArea = document.getElementById('procesoArea');
    const procesoEstado = document.getElementById('procesoEstado');
    const procesoFechaInicio = document.getElementById('procesoFechaInicio');
    const procesoEncargado = document.getElementById('procesoEncargado');
    const procesoObservaciones = document.getElementById('procesoObservaciones');
    
    console.log('[handleEditarProceso] Elementos del formulario:', {
      procesoArea: !!procesoArea,
      procesoEstado: !!procesoEstado,
      procesoFechaInicio: !!procesoFechaInicio,
      procesoEncargado: !!procesoEncargado,
      procesoObservaciones: !!procesoObservaciones
    });
    
    // Llenar el formulario con los datos actuales
    if (procesoArea) procesoArea.value = processData.area || areaName;
    if (procesoEstado) procesoEstado.value = processData.estado || 'Pendiente';
    if (procesoFechaInicio) {
      // Formatear la fecha para el input date (YYYY-MM-DD)
      const fechaInicio = processData.fecha_inicio;
      if (fechaInicio) {
        const date = new Date(fechaInicio);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        procesoFechaInicio.value = `${year}-${month}-${day}`;
      }
    }
    if (procesoEncargado) procesoEncargado.value = processData.encargado || '';
    if (procesoObservaciones) procesoObservaciones.value = processData.observaciones || '';
    
    // Guardar el ID del proceso que se está editando
    window.editingProcessId = procesoId;
    
    // Cambiar el texto del botón a "Actualizar"
    const btnConfirmar = document.getElementById('btnConfirmAddProceso');
    if (btnConfirmar) {
      btnConfirmar.textContent = 'Actualizar Proceso';
      btnConfirmar.onclick = function() { handleActualizarProceso(procesoId); };
    }
    
    // Abrir el modal de agregar/editar proceso
    openAddProcesoModal();
    
    console.log('[handleEditarProceso] Modal de agregar/editar proceso abierto');
  };

  // Manejar actualización de proceso
  window.handleActualizarProceso = async function(procesoId) {
    try {
      const area = document.getElementById('procesoArea').value;
      const estado = document.getElementById('procesoEstado').value;
      const fechaInicio = document.getElementById('procesoFechaInicio').value;
      const encargado = document.getElementById('procesoEncargado').value;
      const observaciones = document.getElementById('procesoObservaciones').value;

      console.log('[handleActualizarProceso] Actualizando proceso:', {
        procesoId, area, estado, fechaInicio, encargado, observaciones
      });

      const response = await fetch('/seguimiento-proceso/' + procesoId, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        },
        body: JSON.stringify({
          area: area,
          estado: estado,
          fecha_inicio: fechaInicio || null,
          encargado: encargado,
          observaciones: observaciones
        })
      });

      if (!response.ok) {
        throw new Error('Error al actualizar proceso');
      }

      const result = await response.json();
      console.log('[handleActualizarProceso] Proceso actualizado:', result);

      // Limpiar formulario y resetear botón
      limpiarFormularioProceso();
      resetFormButton();

      // Recargar seguimientos de la prenda
      await loadPrendasWithTracking(currentOrderData.id);
      
      // Actualizar vista actual
      if (currentPrendaData) {
        renderPrendaTrackingTimeline(currentPrendaData);
      }

      // Mostrar mensaje de éxito
      showSuccess('Proceso actualizado correctamente');

    } catch (error) {
      console.error('[handleActualizarProceso] Error:', error);
      showError('Error al actualizar proceso: ' + error.message);
    }
  };

  // Limpiar formulario de proceso
  function limpiarFormularioProceso() {
    document.getElementById('procesoArea').value = '';
    document.getElementById('procesoEstado').value = 'Pendiente';
    document.getElementById('procesoFechaInicio').value = '';
    document.getElementById('procesoEncargado').value = '';
    document.getElementById('procesoObservaciones').value = '';
  }

  // Resetear botón del formulario a su estado original
  function resetFormButton() {
    const btnConfirmar = document.getElementById('btnConfirmAddProceso');
    if (btnConfirmar) {
      btnConfirmar.textContent = 'Agregar Proceso';
      btnConfirmar.onclick = handleAgregarProceso;
    }
    window.editingProcessId = null;
  }

  // Crear tarjeta de área/proceso
  function createAreaCard(area, data) {
    const card = document.createElement('div');
    card.className = `tracking-area-card ${data.esta_activo ? 'pending' : 'completed'}`;
    
    const iconSvg = getIconSvg(data.icono || 'description');
    
    card.innerHTML = `
      <div class="tracking-area-name">
        ${iconSvg}
        ${area}
        <div class="tracking-action-buttons">
          ${data.id ? `
          <button class="tracking-edit-btn" onclick="handleEditarProceso(${data.id}, '${area}', ${JSON.stringify(data).replace(/"/g, '&quot;')}, event)" title="Editar proceso">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
          </button>
          <button class="tracking-delete-btn" onclick="handleEliminarProceso(${data.id}, '${area}', event)" title="Eliminar proceso">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14zM10 11v6M14 11v6"/>
            </svg>
          </button>
          ` : ''}
        </div>
      </div>
      <div class="tracking-area-details">
        <div class="tracking-detail-row">
          <span class="tracking-detail-label">Estado</span>
          <span class="tracking-detail-value">
            <span class="tracking-days-badge ${data.esta_activo ? '' : 'tracking-days-badge-zero'}">
              ${data.estado}
            </span>
          </span>
        </div>
        <div class="tracking-detail-row">
          <span class="tracking-detail-label">Encargado</span>
          <span class="tracking-detail-value">${data.encargado || 'No asignado'}</span>
        </div>
        <div class="tracking-detail-row">
          <span class="tracking-detail-label">Fecha Inicio</span>
          <span class="tracking-detail-value">${formatDate(data.fecha_inicio) || 'No iniciado'}</span>
        </div>
        <div class="tracking-detail-row">
          <span class="tracking-detail-label">Fecha Fin</span>
          <span class="tracking-detail-value">${formatDate(data.fecha_fin) || 'En progreso'}</span>
        </div>
        ${data.duracion_dias ? `
        <div class="tracking-detail-row">
          <span class="tracking-detail-label">Duración</span>
          <span class="tracking-detail-value">${data.duracion_dias} días</span>
        </div>
        ` : ''}
        ${data.observaciones ? `
        <div class="tracking-detail-row">
          <span class="tracking-detail-label">Observaciones</span>
          <span class="tracking-detail-value">${data.observaciones}</span>
        </div>
        ` : ''}
      </div>
    `;
    
    return card;
  }

  // Crear tarjeta de seguimiento
  function createSeguimientoCard(tipo, data) {
    const card = document.createElement('div');
    card.className = 'tracking-area-card';
    
    const statusClass = data.tiene_disponibles ? 'pending' : 'completed';
    const statusText = data.tiene_disponibles ? 'En Progreso' : 'Completado';
    
    card.innerHTML = `
      <div class="tracking-area-name">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
        </svg>
        ${tipo}
      </div>
      <div class="tracking-area-details">
        <div class="tracking-detail-row">
          <span class="tracking-detail-label">Consecutivo Actual</span>
          <span class="tracking-detail-value">${data.consecutivo_actual}</span>
        </div>
        <div class="tracking-detail-row">
          <span class="tracking-detail-label">Consecutivo Inicial</span>
          <span class="tracking-detail-value">${data.consecutivo_inicial}</span>
        </div>
        <div class="tracking-detail-row">
          <span class="tracking-detail-label">Siguiente Consecutivo</span>
          <span class="tracking-detail-value">${data.siguiente_consecutivo}</span>
        </div>
        <div class="tracking-detail-row">
          <span class="tracking-detail-label">Estado</span>
          <span class="tracking-detail-value">
            <span class="tracking-days-badge ${data.tiene_disponibles ? '' : 'tracking-days-badge-zero'}">
              ${statusText}
            </span>
          </span>
        </div>
        ${data.notas ? `
        <div class="tracking-detail-row">
          <span class="tracking-detail-label">Notas</span>
          <span class="tracking-detail-value">${data.notas}</span>
        </div>
        ` : ''}
      </div>
    `;
    
    return card;
  }

  // Obtener SVG del icono
  function getIconSvg(iconName) {
    const icons = {
      'description': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>',
      'inventory_2': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"></path></svg>',
      'content_cut': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="6" cy="6" r="3"></circle><circle cx="18" cy="18" r="3"></circle><path d="M20.41 3.59l-7.06 7.06a2 2 0 01-2.83 0l-2.12-2.12a2 2 0 010-2.83l7.06-7.06a2 2 0 012.83 0l2.12 2.12a2 2 0 010 2.83z"></path></svg>',
      'brush': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.71 4.63l-1.34-1.34a1 1 0 00-1.41 0L9 12.59 10.41 14l8.3-8.3a1 1 0 000-1.41z"></path><path d="M18 13l3 3"></path><path d="M3 21l9-9"></path></svg>',
      'print': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7"></path><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>',
      'dry_cleaning': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M12 8v8"></path><path d="M8 12h8"></path></svg>',
      'checkroom': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7v10c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V7l-10-5z"></path><path d="M12 22V12"></path></svg>',
      'construction': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 21l6-6m0 0V9m0 6h6m-6-6l6-6m6 0l6 6m0 0v6m0-6h-6m6 6l-6 6"></path></svg>',
      'local_laundry_service': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7v10c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V7l-10-5z"></path><circle cx="12" cy="13" r="4"></circle></svg>',
      'handyman': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v7m0 0l3-3m-3 3l-3-3"></path><path d="M12 22v-7m0 0l3 3m-3-3l-3 3"></path><path d="M2 12h7m0 0l-3-3m3 3l-3 3"></path><path d="M22 12h-7m0 0l3-3m-3 3l3 3"></path></svg>',
      'verified': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
      'local_shipping': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"></path><polyline points="14,2 14,8 20,8"></polyline><line x1="16" y1="13" x2="16" y2="21"></line><line x1="8" y1="13" x2="8" y2="21"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>',
      'directions_car': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 17l2-2h8l2 2M5 7l2 2h8l2-2"></path><path d="M7 12h10"></path></svg>',
      'highlight': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11H3m6 0v6m0-6l-6 6m12 0h6m-6 0v6m0-6l6 6"></path></svg>',
      'search': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg>'
    };
    
    return icons[iconName] || icons.description;
  }

  // Mostrar vista de prendas (cerrar modal de seguimiento y volver a prendas)
  function showPrendasView() {
    console.log('[showPrendasView] Cerrando modal de seguimiento y volviendo a prendas...');
    
    // Cerrar el modal de seguimiento
    closeTrackingModal();
    
    // Mostrar el overlay de selección de prendas
    showPrendasSelector();
    
    console.log('[showPrendasView] Modal de seguimiento cerrado y selector de prendas mostrado');
  }

  // Formatear fecha
  function formatDate(dateString) {
    if (!dateString) return null;
    
    try {
      // Si el formato es d/m/Y, convertirlo a Y-m-d para el constructor Date
      if (typeof dateString === 'string' && dateString.includes('/')) {
        const parts = dateString.split('/');
        if (parts.length === 3) {
          const [day, month, year] = parts;
          // Crear fecha en formato Y-m-d
          const isoDate = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
          const date = new Date(isoDate);
          return date.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
          });
        }
      }
      
      // Para formatos estándar (ISO, etc.)
      const date = new Date(dateString);
      return date.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      });
    } catch (error) {
      console.warn('[formatDate] Error formateando fecha:', dateString, error);
      return dateString;
    }
  }

  // Mostrar error
  function showError(message) {
    console.error('[showError] ' + message);
    // Aquí podrías mostrar una notificación de error al usuario
  }

  // Manejar agregar proceso
  async function handleAgregarProceso() {
    try {
      const area = document.getElementById('procesoArea').value;
      const estado = document.getElementById('procesoEstado').value;
      const encargado = document.getElementById('procesoEncargado').value;
      const observaciones = document.getElementById('procesoObservaciones').value;

      if (!area) {
        showError('Por favor selecciona un área/proceso');
        return;
      }

      if (!currentPrendaData || !currentOrderData) {
        showError('No hay datos de la prenda o pedido');
        return;
      }

      console.log('[handleAgregarProceso] Agregando proceso:', {
        area,
        estado,
        encargado,
        observaciones,
        prenda_id: currentPrendaData.id,
        currentOrderData: currentOrderData
      });

      // Verificar que los datos necesarios existan
      console.log('[handleAgregarProceso] Verificando estructura de datos:', {
        currentOrderData: currentOrderData,
        'currentOrderData.numero_pedido': currentOrderData?.numero_pedido,
        'currentOrderData.pedido': currentOrderData?.pedido
      });
      
      if (!currentOrderData) {
        throw new Error('No hay datos del pedido');
      }
      
      if (!currentOrderData.numero_pedido) {
        throw new Error('No hay número de pedido');
      }

      // Enviar datos al backend
      const response = await fetch('/seguimiento-proceso/guardar', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        },
        body: JSON.stringify({
          pedido_produccion_id: currentOrderData.numero_pedido,
          prenda_id: currentPrendaData.id,
          area: area,
          estado: estado,
          encargado: encargado,
          observaciones: observaciones
        })
      });

      if (!response.ok) {
        throw new Error('Error al agregar proceso');
      }

      const result = await response.json();
      console.log('[handleAgregarProceso] Proceso agregado:', result);

      // Limpiar formulario
      limpiarFormularioProceso();

      // Cerrar modal de agregar proceso
      const modal = document.getElementById('addProcesoModal');
      if (modal) {
        modal.classList.remove('show');
        modal.style.display = 'none';
      }

      // Actualizar datos de la prenda con la respuesta del backend
      if (result.data && result.data.prenda) {
        currentPrendaData = result.data.prenda;
        console.log('[handleAgregarProceso] Prenda actualizada desde backend:', currentPrendaData);
        
        // Renderizar timeline con los datos actualizados
        renderPrendaTrackingTimeline(currentPrendaData);
      } else {
        // Si no vienen datos de la prenda, recargar desde el endpoint
        console.log('[handleAgregarProceso] Recargando datos desde endpoint...');
        await loadPrendasWithTracking(currentOrderData.id);
        
        // Buscar la prenda actualizada en los datos cargados
        if (window.prendasData && window.prendasData.length > 0) {
          const prendaActualizada = window.prendasData.find(p => p.id == currentPrendaData.id);
          if (prendaActualizada) {
            currentPrendaData = prendaActualizada;
            renderPrendaTrackingTimeline(currentPrendaData);
          }
        }
      }

      // Mostrar mensaje de éxito
      showSuccess('Proceso agregado correctamente');

    } catch (error) {
      console.error('[handleAgregarProceso] Error:', error);
      showError('Error al agregar proceso: ' + error.message);
    }
  }

  // Limpiar formulario de proceso
  function limpiarFormularioProceso() {
    document.getElementById('procesoArea').value = '';
    document.getElementById('procesoEstado').value = 'Pendiente';
    document.getElementById('procesoFechaInicio').value = '';
    document.getElementById('procesoEncargado').value = '';
    document.getElementById('procesoObservaciones').value = '';
  }

  // Mostrar mensaje de éxito
  function showSuccess(message) {
    // Crear elemento temporal para mostrar éxito
    const successDiv = document.createElement('div');
    successDiv.className = 'tracking-success-message';
    successDiv.textContent = message;
    successDiv.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      color: white;
      padding: 12px 20px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
      z-index: 100000;
      font-weight: 600;
      animation: slideInRight 0.3s ease-out;
    `;

    document.body.appendChild(successDiv);

    // Remover después de 3 segundos
    setTimeout(() => {
      if (successDiv.parentNode) {
        successDiv.parentNode.removeChild(successDiv);
      }
    }, 3000);
  }

  // Inicializar cuando el DOM esté listo
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTrackingModalListeners);
  } else {
    initTrackingModalListeners();
  }

})();
