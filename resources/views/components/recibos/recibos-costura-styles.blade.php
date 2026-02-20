@push('styles')
<!-- Estilos específicos para botones de novedades -->
<link rel="stylesheet" href="{{ asset('css/novedades-button.css') }}?v={{ time() }}">

<style>
/* Estilos para tabla HTML tradicional - Mejorado */
.table-scroll-container {
    background: white;
    border-radius: 16px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
    overflow-x: auto;
    overflow-y: visible; /* Permitir que el menú se muestre */
    position: relative; /* Añadir para contexto de posicionamiento */
    border: 1px solid #e2e8f0;
}

/* Asegurar que la tabla no recorte el menú */
.modern-table {
    margin-bottom: 0;
    min-width: 1400px; /* Ancho mínimo para forzar scroll horizontal */
    position: relative;
    border-collapse: separate;
    border-spacing: 0;
}

.table-header {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
    position: sticky;
    top: 0;
    z-index: 10;
}

.table-header th {
    color: white !important;
    font-weight: 700 !important;
    font-size: 0.875rem !important;
    padding: 16px 12px !important;
    text-align: center !important;
    border-right: 1px solid rgba(255, 255, 255, 0.15) !important;
    vertical-align: middle !important;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: inset 0 -1px 0 rgba(255, 255, 255, 0.1);
}

.table-header th:first-child {
    border-top-left-radius: 16px;
}

.table-header th:last-child {
    border-right: none !important;
    border-top-right-radius: 16px;
}

.table tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: all 0.3s ease;
    position: relative;
}

.table tbody tr:hover {
    background: linear-gradient(90deg, #f8fafc 0%, #f1f5f9 100%) !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.table tbody tr.dias-mayor-15 {
    background: #d4d4d4;
    border-left: 4px solid #6b7280;
}

.table tbody tr.dias-mayor-15:hover {
    background: #c4c7ce;
}

.table tbody tr.dias-5-9 {
    background: #fef08a;
    border-left: 4px solid #ca8a04;
}

.table tbody tr.dias-5-9:hover {
    background: #fde047;
}

.table tbody tr.dias-10-15 {
    background: #fecaca;
    border-left: 4px solid #dc2626;
}

.table tbody tr.dias-10-15:hover {
    background: #fca5a5;
}

.table td {
    padding: 14px 12px !important;
    vertical-align: middle !important;
    border-right: 1px solid #f1f5f9;
    font-size: 0.875rem;
    color: #475569;
}

.table td:last-child {
    border-right: none;
}

/* Estilos para badges */
.badge {
    padding: 6px 12px !important;
    border-radius: 20px !important;
    font-size: 0.75rem !important;
    font-weight: 600 !important;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.bg-info {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
    color: white !important;
}

.bg-secondary {
    background: linear-gradient(135deg, #64748b 0%, #475569 100%) !important;
    color: white !important;
}

/* Estilos para dropdown de día de entrega */
.dia-entrega-dropdown {
    width: 100%;
    padding: 8px 12px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    background: white;
    color: #475569;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.3s ease;
    cursor: pointer;
}

.dia-entrega-dropdown:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.dia-entrega-dropdown:hover {
    border-color: #94a3b8;
}

/* Estilos para botones de acción */
/* ===== ACTION MENU STYLES (Estilo Contador) ===== */
.action-view-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border: 1px solid #e2e8f0;
    background: white;
    color: #64748b;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s ease;
    position: relative;
    flex-shrink: 0;
}

.action-view-btn:hover {
    background: #1e5ba8;
    color: white;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.action-view-btn:active {
    transform: translateY(0);
}

.action-view-btn i {
    display: flex;
    align-items: center;
    justify-content: center;
}

/* ===== ACTION MENU (Estilo Contador) ===== */
.action-menu {
    position: absolute;
    top: 50%;
    left: 85px;
    transform: translateY(-50%);
    background: white;
    border: 1px solid #e0e6ed;
    border-radius: 8px;
    box-shadow: 0 4px 12px 0 rgba(0, 0, 0, 0.08);
    min-width: 180px;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: all 0.2s ease;
    z-index: 9999;
}

.action-menu.show,
.action-menu.active {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
    display: block !important;
    z-index: 9999 !important;
}

.acciones-column {
    position: relative !important;
    z-index: 1 !important;
}

.action-menu-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    color: #2c3e50;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    border-bottom: 1px solid #e5e7eb;
    transition: all 0.2s ease;
    cursor: pointer;
}

.action-menu-item:last-child {
    border-bottom: none;
}

.action-menu-item:hover {
    background: #2b7ec9;
    color: #1e5ba8;
    padding-left: 20px;
}

.action-menu-item i {
    font-size: 16px;
    width: 20px;
    text-align: center;
}

/* Ocultar botón Volver en recibos-costura - selector más específico */
body #orderTrackingModal .tracking-back-btn {
    display: none !important;
}

/* Sobrescribir la regla específica del componente */
#orderTrackingModal.show .tracking-back-btn {
    display: none !important;
}

/* Alternativa adicional */
.tracking-back-btn#backToPrendasBtn {
    display: none !important;
}

/* Regla específica para sobrescribir el estilo del componente */
#orderTrackingModal.show .tracking-back-btn#backToPrendasBtn {
    display: none !important;
}

@keyframes slideUp {
    from {
        transform: translateY(50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Estilos del modal de seguimiento - igual que en registros */
#orderTrackingModal.show {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    opacity: 1 !important;
    visibility: visible !important;
    z-index: 9998 !important;
}

#orderTrackingModal.show .tracking-modal-content {
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
    animation: slideUp 0.3s ease-out !important;
    margin: auto !important;
    position: relative !important;
    background: white !important;
    border-radius: 16px !important;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3) !important;
    max-width: 1200px !important;
    width: 95% !important;
    max-height: 95vh !important;
}

/* Acciones - Basado en el ejemplo de registros */
.acciones-column {
    position: relative;
    text-align: center; /* Centrar el botón sin flex */
}

.action-view-btn {
    width: 32px;
    height: 32px;
    border: 1px solid #e2e8f0;
    background: white;
    color: #64748b;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s ease;
    position: relative;
    flex-shrink: 0;
}

.action-view-btn:hover {
    background: #1e5ba8;
    color: white;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.action-view-btn:active {
    transform: translateY(0);
}

.action-view-btn i {
    display: flex;
    align-items: center;
    justify-content: center;
}

.action-menu {
    position: absolute;
    top: 50%;
    left: 85px;
    transform: translateY(-50%);
    background: white;
    border: 1px solid #e0e6ed;
    border-radius: 8px;
    box-shadow: 0 4px 12px 0 rgba(0, 0, 0, 0.08);
    min-width: 180px;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: all 0.2s ease;
    z-index: 9999;
}

.action-menu.show,
.action-menu.active {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
    display: block !important;
    z-index: 9999 !important;
}

.acciones-column {
    position: relative !important;
    z-index: 1 !important;
}

.action-menu-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    color: #2c3e50;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    border-bottom: 1px solid #e5e7eb;
    transition: all 0.2s ease;
    cursor: pointer;
}

.action-menu-item:last-child {
    border-bottom: none;
}

.action-menu-item:hover {
    background: #2b7ec9;
    color: #1e5ba8;
    padding-left: 20px;
}

.action-menu-item i {
    font-size: 16px;
    width: 20px;
    text-align: center;
}
</style>
@endpush
