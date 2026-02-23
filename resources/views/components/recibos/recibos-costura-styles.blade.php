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

/* Forzar mismo ancho para Día de entrega y Total de días */
.table-header th:nth-child(1) {
    width: 60px !important;
    min-width: 60px !important;
    max-width: 60px !important;
}

.table-header th:nth-child(2) {
    width: 120px !important;
    min-width: 120px !important;
    max-width: 120px !important;
}

.table-header th:nth-child(3) {
    width: 120px !important;
    min-width: 120px !important;
    max-width: 120px !important;
}

.table-header th:nth-child(4) {
    width: 120px !important;
    min-width: 120px !important;
    max-width: 120px !important;
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

.table tbody tr.dias-0-4 {
    background: #dcfce7;
    border-left: 4px solid #16a34a;
}

.table tbody tr.dias-0-4:hover {
    background: #bbf7d0;
}

.table tbody tr.dias-10-15 {
    background: #fecaca;
    border-left: 4px solid #dc2626;
}

.table tbody tr.dias-10-15:hover {
    background: #fca5a5;
}

.table tbody tr.dias-mayor-15 {
    background: #d4d4d4;
    border-left: 4px solid #6b7280;
}

.table tbody tr.dias-mayor-15:hover {
    background: #c4c7ce;
}

.table td {
    padding: 14px 12px !important;
    vertical-align: middle !important;
    border-right: 1px solid #f1f5f9;
    font-size: 11px !important;
    color: #475569;
}

/* Forzar mismo ancho para Día de entrega y Total de días en el cuerpo */
.table tbody tr td:nth-child(1) {
    width: 60px !important;
    min-width: 60px !important;
    max-width: 60px !important;
    text-align: center !important;
    padding: 8px 4px !important;
}

.table tbody tr td:nth-child(2) {
    width: 120px !important;
    min-width: 120px !important;
    max-width: 120px !important;
    text-align: center !important;
}

.table tbody tr td:nth-child(3) {
    width: 120px !important;
    min-width: 120px !important;
    max-width: 120px !important;
    text-align: center !important;
}

.table tbody tr td:nth-child(4) {
    width: 120px !important;
    min-width: 120px !important;
    max-width: 120px !important;
    text-align: center !important;
}

/* Aplicar font-size 11px a todo el texto dentro de la tabla */
.table td * {
    font-size: 11px !important;
}

.table td span {
    font-size: 11px !important;
}

.table td .cell-content {
    font-size: 11px !important;
}

.table td .table-cell {
    font-size: 11px !important;
}

.table td:last-child {
    border-right: none;
}

/* Estilos para badges */
.badge {
    padding: 6px 12px !important;
    border-radius: 20px !important;
    font-size: 11px !important;
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
    padding: 6px 12px !important;
    border: none !important;
    border-radius: 20px !important;
    background: linear-gradient(135deg, #64748b 0%, #475569 100%) !important;
    color: white !important;
    font-size: 11px !important;
    font-weight: 600 !important;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
    cursor: pointer;
    text-align: center;
    font-family: inherit;
    line-height: 1.2;
    height: auto;
    min-height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.dia-entrega-dropdown:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.dia-entrega-dropdown:hover {
    background: linear-gradient(135deg, #475569 0%, #334155 100%) !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

/* Estilo especial para dropdown con valor seleccionado (orange-highlight) */
.dia-entrega-dropdown.orange-highlight {
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%) !important;
    color: white !important;
    font-weight: 600 !important;
    border: none !important;
}

.dia-entrega-dropdown.orange-highlight:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.2);
}

.dia-entrega-dropdown.orange-highlight:hover {
    background: linear-gradient(135deg, #ea580c 0%, #dc2626 100%) !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.dia-entrega-dropdown option {
    background: white !important;
    color: #475569 !important;
    font-weight: 500 !important;
    text-transform: none !important;
    letter-spacing: normal !important;
    padding: 8px 12px !important;
}

/* Estilos para botones de acción */
/* ===== DROPDOWN BUTTON STYLES (Estilo Insumos) ===== */
.btn-ver-dropdown {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px !important;
    height: 32px !important;
    border: 1px solid #e2e8f0;
    background: white;
    color: #64748b;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px !important;
    transition: all 0.2s ease;
    position: relative;
    flex-shrink: 0;
    margin: 0 auto;
}

.btn-ver-dropdown:hover {
    background: #1e5ba8;
    color: white;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.btn-ver-dropdown:active {
    transform: translateY(0);
}

.btn-ver-dropdown i {
    display: flex;
    align-items: center;
    justify-content: center;
}

.acciones-column {
    position: relative !important;
    z-index: 1 !important;
}

/* ===== DROPDOWN MENU (creado dinámicamente en #dropdowns-container) ===== */
.dropdown-menu-recibos button:hover {
    background: #f0f9ff !important;
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
    z-index: 9999999 !important;
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

/* Estilos para el modal de filtros - Mejorados para evitar conflictos */
.filter-modal {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    background: rgba(0, 0, 0, 0.5) !important;
    backdrop-filter: blur(4px) !important;
    z-index: 999999 !important;
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 20px !important;
    box-sizing: border-box !important;
    width: 100vw !important;
    height: 100vh !important;
}

.filter-modal[style*="flex"] {
    display: flex !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.filter-modal-content {
    background: white !important;
    border-radius: 12px !important;
    max-width: 400px !important;
    width: 100% !important;
    max-height: 80vh !important;
    overflow: hidden !important;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
    display: flex !important;
    flex-direction: column !important;
    margin: 0 !important;
    box-sizing: border-box !important;
    position: relative !important;
    transform: none !important;
    min-height: 200px !important;
}

.filter-modal-header {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    padding: 20px 24px !important;
    border-bottom: 1px solid #e5e7eb !important;
    background: #f9fafb !important;
    border-radius: 12px 12px 0 0 !important;
    margin: 0 !important;
    box-sizing: border-box !important;
    flex-shrink: 0 !important;
}

.filter-modal-header h3 {
    margin: 0 !important;
    font-size: 18px !important;
    font-weight: 600 !important;
    color: #111827 !important;
    padding: 0 !important;
    line-height: 1.2 !important;
}

.filter-modal-close {
    background: none !important;
    border: none !important;
    font-size: 24px !important;
    cursor: pointer !important;
    color: #6b7280 !important;
    padding: 0 !important;
    width: 32px !important;
    height: 32px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    border-radius: 6px !important;
    transition: all 0.2s ease !important;
    box-sizing: border-box !important;
    flex-shrink: 0 !important;
}

.filter-modal-close:hover {
    background: #f3f4f6 !important;
    color: #374151 !important;
}

.filter-modal-body {
    padding: 16px 24px !important;
    overflow-y: auto !important;
    flex: 1 !important;
    margin: 0 !important;
    box-sizing: border-box !important;
    min-height: 100px !important;
}

.filter-search {
    width: 100% !important;
    padding: 10px 12px !important;
    border: 1px solid #d1d5db !important;
    border-radius: 8px !important;
    font-size: 14px !important;
    margin-bottom: 16px !important;
    transition: border-color 0.2s ease !important;
    box-sizing: border-box !important;
    display: block !important;
}

.filter-search:focus {
    outline: none !important;
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
}

.filter-options {
    max-height: 300px !important;
    overflow-y: auto !important;
    margin: 0 !important;
    padding: 0 !important;
    box-sizing: border-box !important;
}

.filter-option {
    display: flex !important;
    align-items: center !important;
    padding: 8px 0 !important;
    border-bottom: 1px solid #f3f4f6 !important;
    transition: background-color 0.2s ease !important;
    margin: 0 !important;
    box-sizing: border-box !important;
}

.filter-option:hover {
    background: #f9fafb !important;
    margin: 0 -12px !important;
    padding-left: 12px !important;
    padding-right: 12px !important;
}

.filter-option:last-child {
    border-bottom: none !important;
}

.filter-option input[type="checkbox"] {
    margin-right: 12px !important;
    width: 16px !important;
    height: 16px !important;
    accent-color: #3b82f6 !important;
    flex-shrink: 0 !important;
}

.filter-option label {
    flex: 1 !important;
    font-size: 14px !important;
    color: #374151 !important;
    cursor: pointer !important;
    font-weight: 500 !important;
    margin: 0 !important;
    padding: 0 !important;
}

.filter-modal-footer {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    padding: 16px 24px !important;
    border-top: 1px solid #e5e7eb !important;
    background: #f9fafb !important;
    border-radius: 0 0 12px 12px !important;
    gap: 12px !important;
    margin: 0 !important;
    box-sizing: border-box !important;
    flex-shrink: 0 !important;
}

.btn-filter-reset,
.btn-filter-apply {
    padding: 10px 16px !important;
    border: none !important;
    border-radius: 8px !important;
    font-size: 14px !important;
    font-weight: 500 !important;
    cursor: pointer !important;
    transition: all 0.2s ease !important;
    flex: 1 !important;
    box-sizing: border-box !important;
    display: block !important;
}

.btn-filter-reset {
    background: #f3f4f6 !important;
    color: #6b7280 !important;
    border: 1px solid #d1d5db !important;
}

.btn-filter-reset:hover {
    background: #e5e7eb !important;
    color: #374151 !important;
}

.btn-filter-apply {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
    color: white !important;
    border: 1px solid #2563eb !important;
}

.btn-filter-apply:hover {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
}

.btn-select-all:hover {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
}

/* Estilos específicos para inputs de texto en filtros */
.filter-text-input {
    width: 100% !important;
    padding: 12px !important;
    border: 1px solid #d1d5db !important;
    border-radius: 8px !important;
    font-size: 14px !important;
    box-sizing: border-box !important;
    transition: border-color 0.2s ease !important;
    display: block !important;
}

.filter-text-input:focus {
    outline: none !important;
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
}

/* Estilos para inputs de rango */
input[type="range"] {
    width: 100% !important;
    height: 6px !important;
    border-radius: 3px !important;
    background: #d1d5db !important;
    outline: none !important;
    transition: background 0.3s !important;
    display: block !important;
}

input[type="range"]::-webkit-slider-thumb {
    appearance: none !important;
    width: 20px !important;
    height: 20px !important;
    border-radius: 50% !important;
    background: #3b82f6 !important;
    cursor: pointer !important;
    transition: background 0.3s !important;
}

input[type="range"]::-moz-range-thumb {
    width: 20px !important;
    height: 20px !important;
    border-radius: 50% !important;
    background: #3b82f6 !important;
    cursor: pointer !important;
    border: none !important;
    transition: background 0.3s !important;
}

/* Estilos para inputs de fecha */
.filter-date-input {
    width: 100% !important;
    padding: 10px !important;
    border: 1px solid #d1d5db !important;
    border-radius: 8px !important;
    font-size: 14px !important;
    box-sizing: border-box !important;
    transition: border-color 0.2s ease !important;
    display: block !important;
}

.filter-date-input:focus {
    outline: none !important;
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
}

/* Forzar visibilidad del contenido del modal */
.filter-modal * {
    visibility: visible !important;
    opacity: 1 !important;
}


</style>
@endpush
