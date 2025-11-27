<!-- Modal de Seguimiento del Pedido -->
<div id="orderTrackingModal" class="order-tracking-modal" style="display: none;">
    <div class="tracking-modal-overlay" id="trackingModalOverlay"></div>
    <div class="tracking-modal-content">
        <!-- Header -->
        <div class="tracking-modal-header">
            <div class="tracking-header-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 11l3 3L22 4M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h2 class="tracking-modal-title">Seguimiento del Pedido</h2>
            <button class="tracking-modal-close" id="closeTrackingModal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Body -->
        <div class="tracking-modal-body">
            <!-- Información del Pedido -->
            <div class="tracking-order-info">
                <!-- Sección Izquierda: Pedido y Cliente -->
                <div class="tracking-info-section">
                    <!-- Pedido -->
                    <div class="tracking-info-card">
                        <div class="tracking-info-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <div class="tracking-info-content">
                            <span class="tracking-info-label">Pedido</span>
                            <span class="tracking-info-value" id="trackingOrderNumber">-</span>
                        </div>
                    </div>

                    <!-- Cliente -->
                    <div class="tracking-info-card">
                        <div class="tracking-info-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                        <div class="tracking-info-content">
                            <span class="tracking-info-label">Cliente</span>
                            <span class="tracking-info-value" id="trackingOrderClient">-</span>
                        </div>
                    </div>
                </div>

                <!-- Sección Derecha: Fechas -->
                <div class="tracking-dates-card">
                    <div class="tracking-dates-group">
                        <!-- Fecha de Inicio -->
                        <div class="tracking-date-item">
                            <div class="tracking-info-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                            </div>
                            <div class="tracking-info-content">
                                <span class="tracking-info-label">Fecha de Inicio</span>
                                <span class="tracking-info-value" id="trackingOrderDate">-</span>
                            </div>
                        </div>

                        <!-- Fecha Estimada -->
                        <div class="tracking-date-item">
                            <div class="tracking-info-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                            </div>
                            <div class="tracking-info-content">
                                <span class="tracking-info-label">Fecha Estimada</span>
                                <span class="tracking-info-value" id="trackingEstimatedDate">-</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total de Días -->
            <div class="tracking-total-days-container">
                <div class="tracking-total-days-card">
                    <div class="tracking-total-days-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <div class="tracking-total-days-content">
                        <span class="tracking-total-days-label">Total de Días</span>
                        <span class="tracking-total-days-value" id="trackingTotalDays">0</span>
                    </div>
                </div>
            </div>

            <!-- Timeline de Áreas -->
            <div class="tracking-timeline">
                <div id="trackingTimelineContainer" class="tracking-timeline-container">
                    <!-- Se llenará dinámicamente con JavaScript -->
                </div>
            </div>

        </div>
    </div>
</div>

<style>
.order-tracking-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
}

.tracking-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
}

.tracking-modal-content {
    position: relative;
    background: var(--bg-card, white);
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    max-width: 600px;
    width: 90%;
    max-height: 85vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.tracking-modal-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 20px;
    background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
    color: white;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.tracking-header-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 8px;
}

.tracking-header-icon svg {
    width: 20px;
    height: 20px;
}

.tracking-modal-title {
    flex: 1;
    font-size: 18px;
    font-weight: 600;
    margin: 0;
}

.tracking-modal-close {
    background: rgba(255, 255, 255, 0.25);
    border: 1px solid rgba(255, 255, 255, 0.3);
    width: 36px;
    height: 36px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.tracking-modal-close:hover {
    background: rgba(255, 255, 255, 0.35);
    border-color: rgba(255, 255, 255, 0.5);
    transform: scale(1.05);
}

.tracking-modal-close:active {
    transform: scale(0.95);
}

.tracking-modal-close svg {
    width: 20px;
    height: 20px;
    color: #000;
    stroke-width: 2.5;
}

.tracking-modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
}

.tracking-order-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 24px;
}

.tracking-info-section {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.tracking-dates-card {
    display: flex;
    flex-direction: column;
    padding: 0 !important;
    background: transparent !important;
    border: none !important;
}

.tracking-dates-group {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.tracking-date-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    height: 70px;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%);
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.tracking-date-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(99, 102, 241, 0.15);
    border-color: #818cf8;
    background: linear-gradient(135deg, #f8faff 0%, #fafbff 100%);
}

.tracking-info-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    height: 70px;
    background: linear-gradient(135deg, #f0f9ff 0%, #f5f3ff 100%);
    border: 1px solid #e0e7ff;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.tracking-info-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.1);
    border-color: #c7d2fe;
}

.tracking-info-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
    border-radius: 8px;
    flex-shrink: 0;
}

.tracking-info-icon svg {
    width: 20px;
    height: 20px;
    color: white;
    stroke-width: 2;
}

.tracking-info-content {
    display: flex;
    flex-direction: column;
    gap: 2px;
    flex: 1;
}

.tracking-info-label {
    font-size: 12px;
    font-weight: 600;
    color: #6366f1;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin-bottom: 4px;
}

.tracking-info-value {
    font-size: 15px;
    font-weight: 700;
    color: #1f2937;
    letter-spacing: -0.3px;
}

.tracking-timeline {
    margin-bottom: 24px;
}

.tracking-timeline-container {
    position: relative;
    padding-left: 20px;
}

.tracking-timeline-container::before {
    content: '';
    position: absolute;
    left: 7px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(180deg, #f59e0b 0%, #f97316 100%);
}

.tracking-timeline-item {
    position: relative;
    margin-bottom: 20px;
    padding-bottom: 20px;
}

.tracking-timeline-item:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
}

.tracking-timeline-item::before {
    content: '';
    position: absolute;
    left: -20px;
    top: 2px;
    width: 16px;
    height: 16px;
    background: white;
    border: 3px solid #f59e0b;
    border-radius: 50%;
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
}

.tracking-timeline-item.completed::before {
    background: #f59e0b;
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
}

.tracking-timeline-item.pending::before {
    border-color: #d1d5db;
    box-shadow: 0 0 0 3px rgba(209, 213, 219, 0.1);
}

.tracking-area-card {
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    border: 2px solid #d1d5db;
    border-radius: 12px;
    padding: 16px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.tracking-area-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #f59e0b 0%, #f97316 100%);
    opacity: 1;
    transition: opacity 0.3s ease;
}

.tracking-area-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
    border-color: #9ca3af;
    background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
}

.tracking-area-card:hover::before {
    opacity: 1;
}

.tracking-area-card.completed {
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    border-color: #d1d5db;
}

.tracking-area-card.completed::before {
    opacity: 1;
}

.tracking-area-card.pending {
    background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
    border-color: #9ca3af;
}

.tracking-area-name {
    font-size: 15px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.tracking-area-name svg {
    width: 22px;
    height: 22px;
    color: #f59e0b;
    filter: drop-shadow(0 2px 4px rgba(245, 158, 11, 0.2));
}

.tracking-area-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    font-size: 13px;
}

.tracking-detail-row {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 8px;
    background: rgba(255, 255, 255, 0.5);
    border-radius: 8px;
    border-left: 3px solid #f59e0b;
}

.tracking-detail-label {
    font-size: 11px;
    font-weight: 700;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.tracking-detail-value {
    font-size: 13px;
    font-weight: 600;
    color: #1f2937;
}

.tracking-days-badge {
    display: inline-block;
    background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
    color: white;
    padding: 4px 10px;
    border-radius: 16px;
    font-size: 12px;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

.tracking-days-badge-zero {
    display: inline-block;
    background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);
    color: white;
    padding: 4px 10px;
    border-radius: 16px;
    font-size: 12px;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(107, 114, 128, 0.2);
}

.tracking-total-days-container {
    display: flex;
    justify-content: center;
    margin: 16px 0;
    padding: 0 16px;
}

.tracking-total-days-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
    border-radius: 10px;
    box-shadow: 0 6px 16px rgba(245, 158, 11, 0.25);
    min-width: auto;
    max-width: 320px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.tracking-total-days-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(245, 158, 11, 0.35);
}

.tracking-total-days-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.25);
    border-radius: 8px;
    flex-shrink: 0;
}

.tracking-total-days-icon svg {
    width: 24px;
    height: 24px;
    color: white;
    stroke-width: 2;
}

.tracking-total-days-content {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.tracking-total-days-label {
    font-size: 11px;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.9);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.tracking-total-days-value {
    font-size: 22px;
    font-weight: 800;
    color: white;
    letter-spacing: -0.3px;
}

/* Light mode support - cuando el sidebar está en modo claro */
html:not([data-theme="dark"]) .tracking-modal-content,
html[data-theme="light"] .tracking-modal-content {
    background: #f9fafb !important;
}

html:not([data-theme="dark"]) .tracking-info-card,
html[data-theme="light"] .tracking-info-card {
    background: #ffffff !important;
    border: 1px solid #e5e7eb !important;
}

html:not([data-theme="dark"]) .tracking-date-item,
html[data-theme="light"] .tracking-date-item {
    background: #ffffff !important;
    border: 1px solid #e5e7eb !important;
}

html:not([data-theme="dark"]) .tracking-area-card,
html[data-theme="light"] .tracking-area-card {
    background: #ffffff !important;
    border: 1px solid #e5e7eb !important;
}

html:not([data-theme="dark"]) .tracking-area-name,
html[data-theme="light"] .tracking-area-name {
    color: #111827 !important;
}

html:not([data-theme="dark"]) .tracking-detail-row,
html[data-theme="light"] .tracking-detail-row {
    background: #f3f4f6 !important;
    border-left: 3px solid #f59e0b;
}

html:not([data-theme="dark"]) .tracking-detail-label,
html[data-theme="light"] .tracking-detail-label {
    color: #6b7280 !important;
}

html:not([data-theme="dark"]) .tracking-detail-value,
html[data-theme="light"] .tracking-detail-value {
    color: #111827 !important;
}

html:not([data-theme="dark"]) .tracking-days-badge-zero,
html[data-theme="light"] .tracking-days-badge-zero {
    background: linear-gradient(135deg, #d1d5db 0%, #9ca3af 100%) !important;
}

html:not([data-theme="dark"]) .tracking-info-label,
html[data-theme="light"] .tracking-info-label {
    color: #6b7280 !important;
}

html:not([data-theme="dark"]) .tracking-info-value,
html[data-theme="light"] .tracking-info-value {
    color: #111827 !important;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .tracking-modal-content {
        background: #1f2937;
    }

    .tracking-order-info {
        gap: 12px;
    }

    .tracking-info-card {
        background: linear-gradient(135deg, #78350f 0%, #92400e 100%);
        border-color: #f59e0b;
    }

    .tracking-info-card:hover {
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2);
        border-color: #fbbf24;
    }

    .tracking-date-item {
        background: linear-gradient(135deg, #374151 0%, #2d3748 100%);
        border-color: #4b5563;
    }

    .tracking-date-item:hover {
        box-shadow: 0 8px 20px rgba(99, 102, 241, 0.25);
        border-color: #818cf8;
        background: linear-gradient(135deg, #3f4654 0%, #323d4a 100%);
    }

    .tracking-info-label {
        color: #a5b4fc;
    }

    .tracking-info-value {
        color: #f3f4f6;
    }

    .tracking-info-icon {
        background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
    }

    .tracking-modal-close {
        background: rgba(255, 255, 255, 0.15);
        border-color: rgba(255, 255, 255, 0.25);
    }

    .tracking-modal-close:hover {
        background: rgba(255, 255, 255, 0.25);
        border-color: rgba(255, 255, 255, 0.4);
    }

    .tracking-modal-close svg {
        color: #000;
    }

    .tracking-area-card {
        background: linear-gradient(135deg, #374151 0%, #2d3748 100%);
        border-color: #4b5563;
    }

    .tracking-area-card.completed {
        background: linear-gradient(135deg, #374151 0%, #2d3748 100%);
        border-color: #4b5563;
    }

    .tracking-area-card.pending {
        background: linear-gradient(135deg, #2d3748 0%, #1f2937 100%);
        border-color: #374151;
    }

    .tracking-area-name {
        color: #f3f4f6;
    }

    .tracking-detail-row {
        background: rgba(0, 0, 0, 0.3);
        border-left-color: #f59e0b;
    }

    .tracking-detail-label {
        color: #d1d5db;
    }

    .tracking-detail-value {
        color: #f3f4f6;
    }

    .tracking-days-badge-zero {
        background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%) !important;
    }

    .tracking-total-days-card {
        background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
    }

}

/* Responsive */
@media (max-width: 768px) {
    .tracking-modal-content {
        max-width: 95%;
        max-height: 90vh;
        border-radius: 16px;
    }

    .tracking-modal-header {
        padding: 16px;
        gap: 10px;
    }

    .tracking-modal-title {
        font-size: 16px;
    }

    .tracking-modal-body {
        padding: 16px;
    }

    .tracking-order-info {
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .tracking-info-section {
        gap: 10px;
    }

    .tracking-dates-card {
        padding: 0 !important;
    }

    .tracking-dates-group {
        gap: 10px;
    }

    .tracking-date-item {
        padding: 12px;
        height: 70px;
    }

    .tracking-info-card {
        padding: 12px;
        height: 70px;
    }

    .tracking-info-icon {
        width: 36px;
        height: 36px;
    }

    .tracking-info-icon svg {
        width: 18px;
        height: 18px;
    }

    .tracking-area-card {
        padding: 14px;
    }

    .tracking-area-name {
        font-size: 14px;
        gap: 8px;
    }

    .tracking-area-name svg {
        width: 20px;
        height: 20px;
    }

    .tracking-area-details {
        grid-template-columns: 1fr;
        gap: 10px;
    }

    .tracking-detail-row {
        padding: 6px;
    }

    .tracking-total-days-container {
        margin: 20px 0;
        padding: 0 12px;
    }

    .tracking-total-days-card {
        min-width: 240px;
        padding: 16px 20px;
        gap: 12px;
    }

    .tracking-total-days-icon {
        width: 44px;
        height: 44px;
    }

    .tracking-total-days-icon svg {
        width: 24px;
        height: 24px;
    }

    .tracking-total-days-label {
        font-size: 11px;
    }

    .tracking-total-days-value {
        font-size: 24px;
    }
}
</style>
