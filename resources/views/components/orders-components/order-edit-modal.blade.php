@props(['show' => false, 'areaOptions'])

<div id="orderEditModal" class="order-edit-modal" style="display: none;">
    <div class="modern-modal-overlay" onclick="closeEditModal()"></div>
    <div class="modern-modal-container">
        <div class="modal-header">
            <div class="header-content">
                <div class="icon-wrapper">
                    <svg class="header-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h2 class="modal-title">Editar Orden <span id="editOrderNumber"></span></h2>
            </div>
            <button class="close-btn" onclick="closeEditModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <form id="orderEditForm">
            <!-- Mensaje emergente -->
            <div id="editNotification" class="notification" style="display: none;"></div>

            <div class="form-content">
                <!-- Información Principal -->
                <div class="section-card">
                    <h3 class="section-title">
                        <svg class="section-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        Información General
                    </h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Pedido
                            </label>
                            <input type="number" id="edit_pedido" name="pedido" class="form-input" readonly />
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <circle cx="12" cy="12" r="10" stroke-width="2"/>
                                    <path d="M12 6v6l4 2" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Estado
                            </label>
                            <select id="edit_estado" name="estado" class="form-select" required>
                                <option value="No iniciado">No iniciado</option>
                                <option value="En Ejecución">En Ejecución</option>
                                <option value="Entregado">Entregado</option>
                                <option value="Anulada">Anulada</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Cliente
                            </label>
                            <input type="text" id="edit_cliente" name="cliente" class="form-input" placeholder="Nombre del cliente" required />
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke-width="2"/>
                                    <path d="M16 2v4M8 2v4M3 10h18" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Fecha
                            </label>
                            <input type="date" id="edit_fecha_creacion" name="fecha_creacion" class="form-input" required />
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Encargado
                            </label>
                            <input type="text" id="edit_encargado" name="encargado" class="form-input" placeholder="Nombre del encargado" />
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Asesora
                            </label>
                            <input type="text" id="edit_asesora" name="asesora" class="form-input" placeholder="Nombre de la asesora" />
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Forma de Pago
                            </label>
                            <input type="text" id="edit_forma_pago" name="forma_pago" class="form-input" placeholder="Método de pago" />
                        </div>
                    </div>
                </div>

                <!-- Prendas -->
                <div class="section-card">
                    <div class="section-header">
                        <h3 class="section-title">
                            <svg class="section-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            Prendas
                        </h3>
                        <button type="button" id="edit_añadirPrendaBtn" class="btn-icon" title="Añadir prenda">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M12 4v16m8-8H4" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </div>

                    <div id="edit_prendasContainer" class="prendas-container">
                        <!-- Las prendas se cargarán dinámicamente aquí -->
                    </div>
                </div>

                <!-- Botones -->
                <div class="form-actions">
                    <button type="button" onclick="closeEditModal()" class="btn btn-secondary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        Cancelar
                    </button>
                    <button type="submit" id="edit_guardarBtn" class="btn btn-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M5 13l4 4L19 7" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        Guardar Cambios
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
    .order-edit-modal {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        padding-top: 2vh;
    }

    .modern-modal-overlay {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.75);
        backdrop-filter: blur(8px);
    }

    .order-edit-modal .modern-modal-container {
        position: relative;
        background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
        width: 90%;
        max-width: 90vw;
        min-height: 750px;
        max-height: 96vh;
        border-radius: 24px;
        box-shadow: 0 25px 80px rgba(0, 0, 0, 0.6);
        z-index: 10000;
        overflow-y: auto;
        animation: modalSlideIn 0.3s ease-out;
        transform: translateY(15px) scale(0.75);
        transform-origin: top center;
    }

    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-15px) scale(0.72);
        }
        to {
            opacity: 1;
            transform: translateY(15px) scale(0.75);
        }
    }

    .order-edit-modal .modal-header {
        background: linear-gradient(135deg, #3b5998 0%, #4c5d7a 100%);
        padding: 18px 28px;
        border-bottom: 1px solid rgba(99, 102, 241, 0.3);
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .order-edit-modal .header-content {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .order-edit-modal .icon-wrapper {
        width: 48px;
        height: 48px;
        background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
    }

    .order-edit-modal .header-icon {
        width: 28px;
        height: 28px;
        color: white;
        stroke-width: 2;
    }

    .order-edit-modal .modal-title {
        font-size: 28px;
        font-weight: 700;
        color: white;
        margin: 0;
    }

    .order-edit-modal .close-btn {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(239, 68, 68, 0.15);
        border: 2px solid rgba(239, 68, 68, 0.3);
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .order-edit-modal .close-btn:hover {
        background: #ef4444;
        border-color: #ef4444;
        transform: rotate(90deg);
    }

    .order-edit-modal .close-btn svg {
        width: 20px;
        height: 20px;
        color: #ef4444;
        stroke-width: 2.5;
    }

    .order-edit-modal .close-btn:hover svg {
        color: white;
    }

    .order-edit-modal .form-content {
        padding: 24px 32px 32px 32px;
    }

    /* Estilo de scrollbar para webkit (Chrome, Safari, Edge) - Aplicado al contenedor principal */
    .order-edit-modal .modern-modal-container::-webkit-scrollbar {
        width: 12px;
        display: block;
    }

    .order-edit-modal .modern-modal-container::-webkit-scrollbar-track {
        background: #2d3748;
        border-radius: 10px;
        margin: 5px 0;
    }

    .order-edit-modal .modern-modal-container::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #6366f1 0%, #3b82f6 100%);
        border-radius: 10px;
        border: 2px solid #2d3748;
        min-height: 40px;
    }

    .order-edit-modal .modern-modal-container::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(135deg, #4f46e5 0%, #2563eb 100%);
        cursor: pointer;
    }

    .order-edit-modal .modern-modal-container::-webkit-scrollbar-button {
        display: none;
    }

    /* Scrollbar para Firefox */
    .order-edit-modal .modern-modal-container {
        scrollbar-width: thin;
        scrollbar-color: #6366f1 #2d3748;
    }

    .order-edit-modal .section-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 16px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    }

    .order-edit-modal .section-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 18px;
        font-weight: 600;
        color: #1a202c;
        margin: 0 0 16px 0;
    }

    .order-edit-modal .section-icon {
        width: 20px;
        height: 20px;
        color: #3b82f6;
        stroke-width: 2;
    }

    .order-edit-modal .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }

    .order-edit-modal .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 16px;
    }

    .order-edit-modal .form-group {
        display: flex;
        flex-direction: column;
    }

    .order-edit-modal .form-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .order-edit-modal .label-icon {
        width: 16px;
        height: 16px;
        color: #3b82f6;
        stroke-width: 2;
    }

    .order-edit-modal .form-input,
    .order-edit-modal .form-select,
    .order-edit-modal .form-textarea {
        width: 100%;
        padding: 10px 14px;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        font-size: 14px;
        color: #1f2937;
        background: #f9fafb;
        transition: all 0.3s ease;
    }

    .order-edit-modal .form-input:focus,
    .order-edit-modal .form-select:focus,
    .order-edit-modal .form-textarea:focus {
        outline: none;
        border-color: #3b82f6;
        background: white;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .order-edit-modal .form-input:read-only {
        background: #e5e7eb;
        cursor: not-allowed;
    }

    .order-edit-modal .form-textarea {
        resize: vertical;
        min-height: 60px;
        font-family: inherit;
    }

    .order-edit-modal .prendas-container {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .order-edit-modal .prenda-card {
        background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 16px;
        transition: all 0.3s ease;
    }

    .order-edit-modal .prenda-card:hover {
        border-color: #3b82f6;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
    }

    .order-edit-modal .prenda-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }

    .order-edit-modal .prenda-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 36px;
        height: 36px;
        padding: 0 12px;
        background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
        color: white;
        border-radius: 10px;
        font-weight: 700;
        font-size: 14px;
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
    }

    .order-edit-modal .prenda-content {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .order-edit-modal .form-label-small {
        font-size: 13px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .order-edit-modal .form-input-compact {
        width: 100%;
        padding: 10px 14px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
        color: #1f2937;
        background: white;
        transition: all 0.3s ease;
    }

    .order-edit-modal .form-input-compact:focus {
        outline: none;
        border-color: #3b82f6;
        background: white;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
    }

    .order-edit-modal .tallas-section {
        margin-top: 8px;
    }

    .order-edit-modal .tallas-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-bottom: 12px;
    }

    .order-edit-modal .tallas-list > div {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        gap: 8px;
        align-items: center;
    }

    .order-edit-modal .tallas-list input {
        padding: 8px 12px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
        color: #1f2937;
        background: white;
        transition: all 0.2s ease;
    }

    .order-edit-modal .tallas-list input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
    }

    .order-edit-modal .btn-add-talla {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 8px 14px;
        background: white;
        border: 2px dashed #cbd5e0;
        border-radius: 8px;
        color: #3b82f6;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .order-edit-modal .btn-add-talla:hover {
        border-color: #3b82f6;
        background: #eff6ff;
    }

    .order-edit-modal .btn-add-talla svg {
        width: 16px;
        height: 16px;
        stroke-width: 2;
    }

    .order-edit-modal .btn-icon {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
        border: none;
        border-radius: 10px;
        color: white;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
    }

    .order-edit-modal .btn-icon:hover {
        transform: scale(1.05) rotate(90deg);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.5);
    }

    .order-edit-modal .btn-icon svg {
        width: 20px;
        height: 20px;
        stroke-width: 2.5;
    }

    .order-edit-modal .btn-delete {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fee;
        border: 2px solid #fecaca;
        border-radius: 8px;
        color: #ef4444;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .order-edit-modal .btn-delete:hover {
        background: #ef4444;
        color: white;
        border-color: #ef4444;
        transform: scale(1.05);
    }

    .order-edit-modal .btn-delete svg {
        width: 16px;
        height: 16px;
        stroke-width: 2.5;
    }

    .order-edit-modal .eliminar-talla-btn {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: transparent;
        border: 2px solid #fecaca;
        border-radius: 6px;
        color: #ef4444;
        cursor: pointer;
        font-weight: bold;
        font-size: 18px;
        transition: all 0.2s ease;
    }

    .order-edit-modal .eliminar-talla-btn:hover {
        background: #ef4444;
        color: white;
        border-color: #ef4444;
    }

    .order-edit-modal .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        margin-top: 24px;
        padding-top: 20px;
        border-top: 2px solid #e5e7eb;
    }

    .order-edit-modal .btn {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .order-edit-modal .btn svg {
        width: 18px;
        height: 18px;
        stroke-width: 2.5;
    }

    .order-edit-modal .btn-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
        color: white;
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
    }

    .order-edit-modal .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(59, 130, 246, 0.4);
    }

    .order-edit-modal .btn-primary:active {
        transform: translateY(0);
    }

    .order-edit-modal .btn-secondary {
        background: #e5e7eb;
        color: #4b5563;
    }

    .order-edit-modal .btn-secondary:hover {
        background: #d1d5db;
    }

    .order-edit-modal .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        max-width: 400px;
        padding: 16px 20px;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 600;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        z-index: 10001;
        animation: slideInRight 0.3s ease-out;
    }

    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(100px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .order-edit-modal .notification.success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }

    .order-edit-modal .notification.error {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
    }

    @media (max-width: 768px) {
        .order-edit-modal .modern-modal-container {
            width: 100%;
            max-width: 100%;
            max-height: 100vh;
            min-height: 100vh;
            border-radius: 0;
            transform: translateY(0) scale(1);
        }

        .order-edit-modal .form-grid {
            grid-template-columns: 1fr;
        }

        .order-edit-modal .form-content {
            padding: 20px;
        }

        .order-edit-modal .modal-header {
            padding: 16px 20px;
        }

        .order-edit-modal .modal-title {
            font-size: 20px;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-10px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
    }
</style>

<script src="{{ asset('js/orders-scripts/order-edit-modal.js') }}"></script>
