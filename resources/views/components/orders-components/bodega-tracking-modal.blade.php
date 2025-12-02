<!-- Modal de Seguimiento del Pedido - Bodega -->
<div id="bodegaTrackingModal" class="bodega-tracking-modal" style="display: none;">
    <div class="bodega-tracking-modal-overlay" id="bodegaTrackingModalOverlay"></div>
    <div class="bodega-tracking-modal-content">
        <!-- Header -->
        <div class="bodega-tracking-modal-header">
            <div class="bodega-tracking-header-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 11l3 3L22 4M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h2 class="bodega-tracking-modal-title">Seguimiento - Bodega</h2>
            <button class="bodega-tracking-modal-close" id="closeBodegaTrackingModal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Body -->
        <div class="bodega-tracking-modal-body">
            <!-- Información del Pedido -->
            <div class="bodega-tracking-order-info">
                <!-- Sección Izquierda: Pedido y Cliente -->
                <div class="bodega-tracking-info-section">
                    <!-- Pedido -->
                    <div class="bodega-tracking-info-card">
                        <div class="bodega-tracking-info-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <div class="bodega-tracking-info-content">
                            <span class="bodega-tracking-info-label">Pedido</span>
                            <span class="bodega-tracking-info-value" id="bodegaTrackingOrderNumber">-</span>
                        </div>
                    </div>

                    <!-- Cliente -->
                    <div class="bodega-tracking-info-card">
                        <div class="bodega-tracking-info-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                        <div class="bodega-tracking-info-content">
                            <span class="bodega-tracking-info-label">Cliente</span>
                            <span class="bodega-tracking-info-value" id="bodegaTrackingOrderClient">-</span>
                        </div>
                    </div>
                </div>

                <!-- Sección Derecha: Estado y Descripción -->
                <div class="bodega-tracking-info-section">
                    <!-- Estado -->
                    <div class="bodega-tracking-info-card">
                        <div class="bodega-tracking-info-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </div>
                        <div class="bodega-tracking-info-content">
                            <span class="bodega-tracking-info-label">Estado</span>
                            <span class="bodega-tracking-info-value" id="bodegaTrackingOrderStatus">-</span>
                        </div>
                    </div>

                    <!-- Área -->
                    <div class="bodega-tracking-info-card">
                        <div class="bodega-tracking-info-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="9" y1="9" x2="15" y2="9"></line>
                                <line x1="9" y1="15" x2="15" y2="15"></line>
                            </svg>
                        </div>
                        <div class="bodega-tracking-info-content">
                            <span class="bodega-tracking-info-label">Área Actual</span>
                            <span class="bodega-tracking-info-value" id="bodegaTrackingOrderArea">-</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total de Días -->
            <div class="bodega-tracking-total-days-container">
                <div class="bodega-tracking-total-days-card">
                    <div class="bodega-tracking-total-days-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <div class="bodega-tracking-total-days-content">
                        <span class="bodega-tracking-total-days-label">Total de Días</span>
                        <span class="bodega-tracking-total-days-value" id="bodegaTrackingTotalDays">0</span>
                    </div>
                </div>
            </div>

            <!-- Procesos Timeline -->
            <div class="bodega-tracking-timeline">
                <h3 class="bodega-tracking-timeline-title">Procesos por Área</h3>
                <div id="bodegaTrackingTimelineContainer" class="bodega-tracking-timeline-container">
                    <!-- Se llenará dinámicamente con JavaScript -->
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    .bodega-tracking-modal {
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

    .bodega-tracking-modal-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
    }

    .bodega-tracking-modal-content {
        position: relative;
        background: var(--bg-card, white);
        border-radius: 12px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        max-width: 700px;
        width: 90%;
        max-height: 85vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        animation: bodegaSlideUp 0.3s ease-out;
    }

    @keyframes bodegaSlideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .bodega-tracking-modal-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 20px;
        background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
        color: white;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .bodega-tracking-header-icon {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 8px;
    }

    .bodega-tracking-header-icon svg {
        width: 20px;
        height: 20px;
    }

    .bodega-tracking-modal-title {
        flex: 1;
        font-size: 18px;
        font-weight: 600;
        margin: 0;
    }

    .bodega-tracking-modal-close {
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

    .bodega-tracking-modal-close:hover {
        background: rgba(255, 255, 255, 0.35);
        border-color: rgba(255, 255, 255, 0.5);
        transform: scale(1.05);
    }

    .bodega-tracking-modal-close:active {
        transform: scale(0.95);
    }

    .bodega-tracking-modal-close svg {
        width: 20px;
        height: 20px;
        color: #000;
        stroke-width: 2.5;
    }

    .bodega-tracking-modal-body {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
    }

    .bodega-tracking-order-info {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 24px;
    }

    .bodega-tracking-info-section {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .bodega-tracking-info-card {
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

    .bodega-tracking-info-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.1);
        border-color: #c7d2fe;
    }

    .bodega-tracking-info-icon {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
        border-radius: 8px;
        flex-shrink: 0;
    }

    .bodega-tracking-info-icon svg {
        width: 20px;
        height: 20px;
        color: white;
        stroke-width: 2;
    }

    .bodega-tracking-info-content {
        display: flex;
        flex-direction: column;
        gap: 2px;
        flex: 1;
    }

    .bodega-tracking-info-label {
        font-size: 12px;
        font-weight: 600;
        color: #f97316;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        margin-bottom: 4px;
    }

    .bodega-tracking-info-value {
        font-size: 15px;
        font-weight: 700;
        color: #1f2937;
        letter-spacing: -0.3px;
    }

    .bodega-tracking-timeline {
        margin-bottom: 24px;
    }

    .bodega-tracking-timeline-title {
        font-size: 16px;
        font-weight: 700;
        color: #1f2937;
        margin: 0 0 16px 0;
        padding-bottom: 10px;
        border-bottom: 2px solid #e5e7eb;
    }

    .bodega-tracking-timeline-container {
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .bodega-tracking-timeline-item {
        position: relative;
        display: flex;
        gap: 16px;
    }

    .bodega-tracking-timeline-item::before {
        content: '';
        position: absolute;
        left: 19px;
        top: 60px;
        width: 2px;
        height: calc(100% + 16px);
        background: linear-gradient(180deg, #f59e0b 0%, rgba(245, 158, 11, 0) 100%);
    }

    .bodega-tracking-timeline-item:last-child::before {
        display: none;
    }

    .bodega-tracking-timeline-dot {
        position: relative;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: white;
        border: 3px solid #f59e0b;
        border-radius: 50%;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        flex-shrink: 0;
        z-index: 2;
    }

    .bodega-tracking-timeline-dot.completed {
        background: #f59e0b;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
    }

    .bodega-tracking-timeline-dot.pending {
        border-color: #d1d5db;
        box-shadow: 0 0 0 3px rgba(209, 213, 219, 0.1);
    }

    .bodega-tracking-timeline-dot svg {
        width: 22px;
        height: 22px;
        color: white;
        stroke-width: 2.5;
    }

    .bodega-tracking-area-card {
        background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
        border: 2px solid #d1d5db;
        border-radius: 12px;
        padding: 16px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        flex: 1;
    }

    .bodega-tracking-area-card::before {
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

    .bodega-tracking-area-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
        border-color: #9ca3af;
        background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
    }

    .bodega-tracking-area-name {
        font-size: 15px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 12px;
    }

    .bodega-tracking-area-details {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
        font-size: 13px;
    }

    .bodega-tracking-detail-row {
        display: flex;
        flex-direction: column;
        gap: 4px;
        padding: 8px;
        background: rgba(255, 255, 255, 0.5);
        border-radius: 8px;
        border-left: 3px solid #f59e0b;
    }

    .bodega-tracking-detail-label {
        font-size: 11px;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .bodega-tracking-detail-value {
        font-size: 13px;
        font-weight: 600;
        color: #1f2937;
        word-break: break-word;
    }

    .bodega-tracking-total-days-container {
        display: flex;
        justify-content: center;
        margin: 16px 0;
        padding: 0 16px;
    }

    .bodega-tracking-total-days-card {
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

    .bodega-tracking-total-days-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(245, 158, 11, 0.35);
    }

    .bodega-tracking-total-days-icon {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.25);
        border-radius: 8px;
        flex-shrink: 0;
    }

    .bodega-tracking-total-days-icon svg {
        width: 24px;
        height: 24px;
        color: white;
        stroke-width: 2;
    }

    .bodega-tracking-total-days-content {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .bodega-tracking-total-days-label {
        font-size: 11px;
        font-weight: 700;
        color: rgba(255, 255, 255, 0.9);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .bodega-tracking-total-days-value {
        font-size: 22px;
        font-weight: 800;
        color: white;
        letter-spacing: -0.3px;
    }

    /* Light mode support */
    html:not([data-theme="dark"]) .bodega-tracking-modal-content,
    html[data-theme="light"] .bodega-tracking-modal-content {
        background: #f9fafb !important;
    }

    html:not([data-theme="dark"]) .bodega-tracking-info-card,
    html[data-theme="light"] .bodega-tracking-info-card {
        background: #ffffff !important;
        border: 1px solid #e5e7eb !important;
    }

    html:not([data-theme="dark"]) .bodega-tracking-area-card,
    html[data-theme="light"] .bodega-tracking-area-card {
        background: #ffffff !important;
        border: 1px solid #e5e7eb !important;
    }

    html:not([data-theme="dark"]) .bodega-tracking-area-name,
    html[data-theme="light"] .bodega-tracking-area-name {
        color: #111827 !important;
    }

    html:not([data-theme="dark"]) .bodega-tracking-detail-row,
    html[data-theme="light"] .bodega-tracking-detail-row {
        background: #f3f4f6 !important;
        border-left: 3px solid #f59e0b;
    }

    html:not([data-theme="dark"]) .bodega-tracking-detail-label,
    html[data-theme="light"] .bodega-tracking-detail-label {
        color: #6b7280 !important;
    }

    html:not([data-theme="dark"]) .bodega-tracking-detail-value,
    html[data-theme="light"] .bodega-tracking-detail-value {
        color: #111827 !important;
    }

    html:not([data-theme="dark"]) .bodega-tracking-info-label,
    html[data-theme="light"] .bodega-tracking-info-label {
        color: #6b7280 !important;
    }

    html:not([data-theme="dark"]) .bodega-tracking-info-value,
    html[data-theme="light"] .bodega-tracking-info-value {
        color: #111827 !important;
    }

    html:not([data-theme="dark"]) .bodega-tracking-timeline-title,
    html[data-theme="light"] .bodega-tracking-timeline-title {
        color: #111827 !important;
    }

    /* Dark mode support */
    @media (prefers-color-scheme: dark) {
        .bodega-tracking-modal-content {
            background: #1f2937;
        }

        .bodega-tracking-order-info {
            gap: 12px;
        }

        .bodega-tracking-info-card {
            background: linear-gradient(135deg, #78350f 0%, #92400e 100%);
            border-color: #f59e0b;
        }

        .bodega-tracking-info-card:hover {
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2);
            border-color: #fbbf24;
        }

        .bodega-tracking-info-label {
            color: #fcd34d;
        }

        .bodega-tracking-info-value {
            color: #f3f4f6;
        }

        .bodega-tracking-info-icon {
            background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
        }

        .bodega-tracking-modal-close {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.25);
        }

        .bodega-tracking-modal-close:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.4);
        }

        .bodega-tracking-modal-close svg {
            color: #000;
        }

        .bodega-tracking-area-card {
            background: linear-gradient(135deg, #374151 0%, #2d3748 100%);
            border-color: #4b5563;
        }

        .bodega-tracking-area-card:hover {
            background: linear-gradient(135deg, #3f4654 0%, #323d4a 100%);
            border-color: #6b7280;
        }

        .bodega-tracking-area-name {
            color: #f3f4f6;
        }

        .bodega-tracking-detail-row {
            background: rgba(0, 0, 0, 0.3);
            border-left-color: #f59e0b;
        }

        .bodega-tracking-detail-label {
            color: #d1d5db;
        }

        .bodega-tracking-detail-value {
            color: #f3f4f6;
        }

        .bodega-tracking-total-days-card {
            background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
        }

        .bodega-tracking-timeline-title {
            color: #f3f4f6;
        }
    }

    /* Responsive */
    @media (max-width: 768px) {
        .bodega-tracking-modal-content {
            max-width: 95%;
            max-height: 90vh;
            border-radius: 16px;
        }

        .bodega-tracking-modal-header {
            padding: 16px;
            gap: 10px;
        }

        .bodega-tracking-modal-title {
            font-size: 16px;
        }

        .bodega-tracking-modal-body {
            padding: 16px;
        }

        .bodega-tracking-order-info {
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .bodega-tracking-info-section {
            gap: 10px;
        }

        .bodega-tracking-info-card {
            padding: 12px;
            height: 70px;
        }

        .bodega-tracking-info-icon {
            width: 36px;
            height: 36px;
        }

        .bodega-tracking-info-icon svg {
            width: 18px;
            height: 18px;
        }

        .bodega-tracking-area-card {
            padding: 14px;
        }

        .bodega-tracking-area-name {
            font-size: 14px;
        }

        .bodega-tracking-area-details {
            grid-template-columns: 1fr;
            gap: 10px;
        }

        .bodega-tracking-detail-row {
            padding: 6px;
        }

        .bodega-tracking-total-days-container {
            margin: 20px 0;
            padding: 0 12px;
        }

        .bodega-tracking-total-days-card {
            min-width: 240px;
            padding: 16px 20px;
            gap: 12px;
        }

        .bodega-tracking-total-days-icon {
            width: 44px;
            height: 44px;
        }

        .bodega-tracking-total-days-icon svg {
            width: 24px;
            height: 24px;
        }

        .bodega-tracking-total-days-label {
            font-size: 11px;
        }

        .bodega-tracking-total-days-value {
            font-size: 24px;
        }

        .bodega-tracking-timeline-item {
            gap: 12px;
        }
    }
</style>
