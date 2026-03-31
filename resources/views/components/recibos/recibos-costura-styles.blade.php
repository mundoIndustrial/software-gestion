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
    width: auto !important;
    min-width: 120px !important;
    max-width: none !important;
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
    width: auto !important;
    min-width: 120px !important;
    max-width: none !important;
    text-align: center !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
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
    position: relative;
    flex-shrink: 0;
    margin: 0 auto;
}

.btn-ver-dropdown:hover {
    background: #1e5ba8;
    color: white;
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

.distribution-modal {
    position: fixed;
    inset: 0;
    display: none;
    z-index: 10000001;
}

.distribution-modal.is-open {
    display: block;
}

.distribution-modal__backdrop {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
}

.distribution-modal__dialog {
    position: relative;
    width: min(760px, calc(100vw - 32px));
    max-height: calc(100vh - 48px);
    margin: 24px auto;
    border-radius: 28px;
    overflow: hidden;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    box-shadow: 0 30px 80px rgba(15, 23, 42, 0.28);
    animation: distributionModalIn 0.22s ease-out;
}

.distribution-modal__header {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 22px 24px;
    color: white;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

.distribution-modal__header-icon {
    width: 52px;
    height: 52px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.18);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2);
}

.distribution-modal__header-icon i {
    font-size: 22px !important;
    color: white;
}

.distribution-modal__header-copy {
    flex: 1;
    min-width: 0;
}

.distribution-modal__eyebrow {
    margin: 0 0 4px 0;
    font-size: 11px !important;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.72);
}

.distribution-modal__header-copy h2 {
    margin: 0;
    font-size: 28px;
    line-height: 1.1;
    font-weight: 800;
    color: white;
}

.distribution-modal__close {
    width: 40px;
    height: 40px;
    border: none;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.14);
    color: white;
    cursor: pointer;
    transition: transform 0.18s ease, background 0.18s ease;
}

.distribution-modal__close:hover {
    transform: translateY(-1px);
    background: rgba(255, 255, 255, 0.24);
}

.distribution-modal__body {
    padding: 22px;
    max-height: calc(100vh - 180px);
    overflow-y: auto;
    background: #f8fafc;
}

.distribution-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 18px;
}

.distribution-summary__card {
    padding: 14px 16px;
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.88);
    border: 1px solid rgba(148, 163, 184, 0.18);
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
}

.distribution-summary__label {
    display: block;
    margin-bottom: 6px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    font-size: 10px !important;
    font-weight: 700;
}

.distribution-summary__value {
    color: #0f172a;
    font-size: 18px !important;
    font-weight: 800;
}

.distribution-list {
    display: grid;
    gap: 16px;
}

.distribution-card {
    position: relative;
    overflow: hidden;
    border-radius: 24px;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
}

.distribution-card::before {
    content: "";
    position: absolute;
    inset: 0 auto 0 0;
    width: 6px;
    background: #1e40af;
}

.distribution-card__inner {
    padding: 18px 20px 18px 24px;
}

.distribution-card__top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
}

.distribution-card__title {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.distribution-card__title h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 800;
    color: #0f172a;
}

.distribution-card__meta {
    display: grid;
    gap: 12px;
}

.distribution-card__actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 16px;
}

.distribution-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    border-radius: 14px;
    border: 1px solid rgba(37, 99, 235, 0.18);
    background: #eff6ff;
    color: #1d4ed8;
    font-size: 12px !important;
    font-weight: 800;
    letter-spacing: 0.02em;
    cursor: pointer;
    transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
}

.distribution-action-btn:hover {
    transform: translateY(-1px);
    background: #dbeafe;
    box-shadow: 0 10px 24px rgba(37, 99, 235, 0.12);
}

.distribution-card__row {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    flex-wrap: wrap;
}

.distribution-card__row-label {
    min-width: 88px;
    padding-top: 4px;
    color: #475569;
    font-size: 11px !important;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.distribution-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 12px;
    border-radius: 999px;
    font-size: 11px !important;
    font-weight: 700;
    letter-spacing: 0.02em;
}

.distribution-pill--blue {
    background: rgba(37, 99, 235, 0.12);
    color: #1d4ed8;
}

.distribution-pill--green {
    background: rgba(16, 185, 129, 0.12);
    color: #047857;
}

.distribution-pill--slate {
    background: rgba(100, 116, 139, 0.12);
    color: #334155;
}

.distribution-sizes {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.distribution-size-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 12px;
    border-radius: 16px;
    background: #eff6ff;
    color: #1e3a8a;
    border: 1px solid rgba(59, 130, 246, 0.14);
    font-weight: 700;
}

.distribution-empty {
    padding: 36px 20px;
    text-align: center;
    border-radius: 24px;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    border: 1px dashed rgba(148, 163, 184, 0.4);
    color: #475569;
}

.partial-tracking-modal {
    position: fixed;
    inset: 0;
    display: none;
    z-index: 10000030;
}

.partial-tracking-modal.is-open {
    display: block;
}

.partial-tracking-modal__backdrop {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.54);
    backdrop-filter: blur(3px);
}

.partial-tracking-modal__dialog {
    position: relative;
    width: min(720px, calc(100vw - 36px));
    max-height: calc(100vh - 72px);
    margin: 36px auto;
    border-radius: 26px;
    overflow: hidden;
    background: #f8fafc;
    border: 1px solid #dbeafe;
    box-shadow: 0 28px 70px rgba(15, 23, 42, 0.34);
    animation: distributionModalIn 0.22s ease-out;
}

.partial-tracking-modal__header {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px 22px;
    color: white;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

.partial-tracking-modal__header-icon {
    width: 50px;
    height: 50px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.18);
}

.partial-tracking-modal__header-icon i {
    font-size: 20px !important;
    color: white;
}

.partial-tracking-modal__header-copy {
    flex: 1;
    min-width: 0;
}

.partial-tracking-modal__eyebrow {
    margin: 0 0 4px 0;
    font-size: 11px !important;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.74);
}

.partial-tracking-modal__header-copy h2 {
    margin: 0;
    font-size: 26px;
    line-height: 1.1;
    font-weight: 800;
    color: white;
}

.partial-tracking-modal__close {
    width: 40px;
    height: 40px;
    border: none;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.14);
    color: white;
    cursor: pointer;
}

.partial-tracking-modal__body {
    padding: 22px;
    max-height: calc(100vh - 200px);
    overflow-y: auto;
    background: #f8fafc;
}

.partial-tracking-summary {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 18px;
}

.partial-tracking-summary__card {
    padding: 14px 16px;
    border-radius: 18px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06);
}

.partial-tracking-summary__label {
    display: block;
    margin-bottom: 6px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    font-size: 10px !important;
    font-weight: 700;
}

.partial-tracking-summary__value {
    color: #0f172a;
    font-size: 17px !important;
    font-weight: 800;
}

.partial-tracking-muted {
    color: #64748b;
    font-size: 12px !important;
    font-weight: 700;
}

.partial-tracking-sizes {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    max-height: 74px;
    overflow: auto;
    padding-right: 6px;
}

.partial-tracking-size-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    border-radius: 16px;
    background: #eff6ff;
    color: #1e3a8a;
    border: 1px solid rgba(59, 130, 246, 0.14);
    font-weight: 800;
    font-size: 12px !important;
    line-height: 1;
}

.partial-tracking-sizes::-webkit-scrollbar {
    height: 8px;
    width: 8px;
}

.partial-tracking-sizes::-webkit-scrollbar-thumb {
    background: rgba(148, 163, 184, 0.6);
    border-radius: 999px;
}

.partial-tracking-sizes::-webkit-scrollbar-track {
    background: rgba(226, 232, 240, 0.6);
    border-radius: 999px;
}

.partial-tracking-sizes {
    scrollbar-color: rgba(148, 163, 184, 0.7) rgba(226, 232, 240, 0.7);
    scrollbar-width: thin;
}

.partial-tracking-summary__card .partial-tracking-summary__value {
    word-break: break-word;
}

.partial-tracking-summary__card:nth-child(3) .partial-tracking-summary__value {
    font-size: 13px !important;
    font-weight: 700;
}

.partial-tracking-summary__card:nth-child(4) .partial-tracking-summary__value {
    display: inline-flex;
    align-items: baseline;
    gap: 8px;
    font-size: 20px !important;
    font-weight: 900;
}

.partial-tracking-summary__card:nth-child(4) .partial-tracking-summary__value::after {
    content: "habiles";
    font-size: 11px !important;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: rgba(100, 116, 139, 0.95);
}

@media (max-width: 980px) {
    .partial-tracking-summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

.partial-tracking-timeline {
    position: relative;
    display: grid;
    gap: 16px;
}

.partial-tracking-timeline::before {
    content: "";
    position: absolute;
    top: 10px;
    bottom: 10px;
    left: 18px;
    width: 2px;
    background: linear-gradient(180deg, #60a5fa 0%, #bfdbfe 100%);
}

.partial-tracking-step {
    position: relative;
    margin-left: 44px;
    padding: 16px 18px;
    border-radius: 20px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    box-shadow: 0 14px 28px rgba(15, 23, 42, 0.06);
}

.partial-tracking-step:has(.partial-tracking-step__days) {
    padding-bottom: 54px;
}

.partial-tracking-step__days {
    position: absolute;
    right: 16px;
    bottom: 14px;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    border-radius: 999px;
    border: 1px solid rgba(220, 38, 38, 0.18);
    background: rgba(254, 242, 242, 0.9);
    box-shadow: 0 10px 22px rgba(220, 38, 38, 0.08);
}

.partial-tracking-step__days-label {
    font-size: 10px !important;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    font-weight: 800;
    color: rgba(100, 116, 139, 0.9);
}

.partial-tracking-step__days-value {
    font-size: 16px !important;
    font-weight: 900;
    color: #dc2626;
}

.partial-tracking-step::before {
    content: "";
    position: absolute;
    top: 22px;
    left: -34px;
    width: 14px;
    height: 14px;
    border-radius: 999px;
    background: #2563eb;
    border: 4px solid #dbeafe;
    box-sizing: content-box;
}

.partial-tracking-step__top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 10px;
}

.partial-tracking-step__title h3 {
    margin: 0 0 6px 0;
    color: #0f172a;
    font-size: 18px;
    font-weight: 800;
}

.partial-tracking-step__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.partial-tracking-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 11px;
    border-radius: 999px;
    font-size: 11px !important;
    font-weight: 700;
}

.partial-tracking-badge--blue {
    background: #dbeafe;
    color: #1d4ed8;
}

.partial-tracking-badge--green {
    background: #dcfce7;
    color: #15803d;
}

.partial-tracking-badge--slate {
    background: #e2e8f0;
    color: #334155;
}

.partial-tracking-step__dates {
    display: grid;
    gap: 6px;
    color: #475569;
    font-size: 12px !important;
    font-weight: 600;
}

.partial-tracking-empty {
    padding: 40px 22px;
    text-align: center;
    border-radius: 24px;
    background: #ffffff;
    border: 1px dashed #cbd5e1;
    color: #475569;
}

.distribution-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    min-height: 180px;
    color: #1d4ed8;
    font-weight: 700;
}

.distribution-spinner {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: 3px solid rgba(37, 99, 235, 0.16);
    border-top-color: #2563eb;
    animation: distributionSpin 0.7s linear infinite;
}

@keyframes distributionSpin {
    to {
        transform: rotate(360deg);
    }
}

@keyframes distributionModalIn {
    from {
        opacity: 0;
        transform: translateY(18px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@media (max-width: 640px) {
    .distribution-modal__dialog {
        width: calc(100vw - 20px);
        margin: 10px auto;
        max-height: calc(100vh - 20px);
        border-radius: 22px;
    }

    .distribution-modal__header {
        padding: 18px;
    }

    .distribution-modal__header-copy h2 {
        font-size: 22px;
    }

    .distribution-modal__body {
        padding: 16px;
        max-height: calc(100vh - 150px);
    }

    .distribution-summary {
        grid-template-columns: 1fr;
    }

    .distribution-card__row-label {
        min-width: 100%;
        padding-top: 0;
    }

    .distribution-card__actions {
        justify-content: stretch;
    }

    .distribution-action-btn {
        width: 100%;
        justify-content: center;
    }

    .partial-tracking-modal__dialog {
        width: calc(100vw - 20px);
        margin: 10px auto;
        max-height: calc(100vh - 20px);
        border-radius: 22px;
    }

    .partial-tracking-modal__header {
        padding: 18px;
    }

    .partial-tracking-modal__header-copy h2 {
        font-size: 22px;
    }

    .partial-tracking-modal__body {
        padding: 16px;
        max-height: calc(100vh - 150px);
    }

    .partial-tracking-summary {
        grid-template-columns: 1fr;
    }

    .partial-tracking-step {
        margin-left: 34px;
        padding: 14px;
    }

    .partial-tracking-step:has(.partial-tracking-step__days) {
        padding-bottom: 60px;
    }

    .partial-tracking-step__days {
        right: 12px;
        bottom: 12px;
    }

    .partial-tracking-timeline::before {
        left: 14px;
    }

    .partial-tracking-step::before {
        left: -28px;
    }
}


</style>
@endpush
