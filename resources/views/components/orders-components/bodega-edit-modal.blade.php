@props(['show' => false, 'areaOptions'])

<div id="bodegaEditModal" class="bodega-edit-modal" style="display: none;">
    <div class="modern-modal-overlay" onclick="closeBodegaEditModal()"></div>
    <div class="modern-modal-container">
        <div class="modal-header">
            <div class="header-content">
                <div class="icon-wrapper">
                    <svg class="header-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h2 class="modal-title">Editar Bodega <span id="editBodegaOrderNumber"></span></h2>
            </div>
            <button class="close-btn" onclick="closeBodegaEditModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <form id="bodegaEditForm">
            <!-- Mensaje emergente -->
            <div id="editBodegaNotification" class="notification" style="display: none;"></div>

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
                            <input type="number" id="bodega_edit_pedido" name="pedido" class="form-input" readonly />
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <circle cx="12" cy="12" r="10" stroke-width="2"/>
                                    <path d="M12 6v6l4 2" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Estado
                            </label>
                            <select id="bodega_edit_estado" name="estado" class="form-select" required>
                                <option value="No iniciado">No iniciado</option>
                                <option value="En Ejecución">En Ejecución</option>
                                <option value="Entregado">Entregado</option>
                                <option value="Anulada">Anulada</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke-width="2"/>
                                    <path d="M16 2v4M8 2v4M3 10h18" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Área
                            </label>
                            <select id="bodega_edit_area" name="area" class="form-select" required>
                                <option value="">Seleccionar área...</option>
                                <option value="Inventario">Inventario</option>
                                <option value="Insumos-y-Telas">Insumos y Telas</option>
                                <option value="Corte">Corte</option>
                                <option value="Bordado">Bordado</option>
                                <option value="Estampado">Estampado</option>
                                <option value="Costura">Costura</option>
                                <option value="Reflectivo">Reflectivo</option>
                                <option value="Lavanderia">Lavanderia</option>
                                <option value="Arreglos">Arreglos</option>
                                <option value="Marras">Marras</option>
                                <option value="Control-Calidad">Control de Calidad</option>
                                <option value="Entrega">Entrega</option>
                                <option value="Despachos">Despachos</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Cliente
                            </label>
                            <input type="text" id="bodega_edit_cliente" name="cliente" class="form-input" placeholder="Nombre del cliente" readonly />
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Cantidad
                            </label>
                            <input type="number" id="bodega_edit_cantidad" name="cantidad" class="form-input" placeholder="Cantidad" readonly />
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
                    </div>

                    <div id="edit_prendasContainer" class="prendas-container">
                        <!-- Las prendas se cargarán dinámicamente aquí -->
                    </div>
                </div>

                <!-- Novedades -->
                <div class="section-card">
                    <h3 class="section-title">
                        <svg class="section-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        Novedades
                    </h3>
                    <div class="form-group">
                        <label class="form-label">Novedades</label>
                        <textarea id="bodega_edit_novedades" name="novedades" class="form-textarea" rows="4"></textarea>
                    </div>
                </div>

                <!-- Botones -->
                <div class="form-actions">
                    <button type="button" onclick="closeBodegaEditModal()" class="btn btn-secondary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        Cancelar
                    </button>
                    <button type="submit" id="bodega_edit_guardarBtn" class="btn btn-primary">
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
    .bodega-edit-modal {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: none;
        align-items: flex-start;
        justify-content: center;
        padding-top: 2vh;
        visibility: hidden;
        opacity: 0;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }
    
    .bodega-edit-modal[style*="display: flex"] {
        visibility: visible;
        opacity: 1;
    }

    .modern-modal-overlay {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.75);
        backdrop-filter: blur(8px);
    }

    .bodega-edit-modal .modern-modal-container {
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

    .bodega-edit-modal .modal-header {
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

    .bodega-edit-modal .header-content {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .bodega-edit-modal .icon-wrapper {
        width: 48px;
        height: 48px;
        background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
    }

    .bodega-edit-modal .header-icon {
        width: 28px;
        height: 28px;
        color: white;
        stroke-width: 2;
    }

    .bodega-edit-modal .modal-title {
        font-size: 28px;
        font-weight: 700;
        color: white;
        margin: 0;
    }

    .bodega-edit-modal .close-btn {
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

    .bodega-edit-modal .close-btn:hover {
        background: #ef4444;
        border-color: #ef4444;
        transform: rotate(90deg);
    }

    .bodega-edit-modal .close-btn svg {
        width: 20px;
        height: 20px;
        color: #ef4444;
        stroke-width: 2.5;
    }

    .bodega-edit-modal .close-btn:hover svg {
        color: white;
    }

    .bodega-edit-modal .form-content {
        padding: 24px 32px 32px 32px;
    }

    /* Estilo de scrollbar para webkit (Chrome, Safari, Edge) */
    .bodega-edit-modal .modern-modal-container::-webkit-scrollbar {
        width: 12px;
        display: block;
    }

    .bodega-edit-modal .modern-modal-container::-webkit-scrollbar-track {
        background: #2d3748;
        border-radius: 10px;
        margin: 5px 0;
    }

    .bodega-edit-modal .modern-modal-container::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #6366f1 0%, #3b82f6 100%);
        border-radius: 10px;
        border: 2px solid #2d3748;
        min-height: 40px;
    }

    .bodega-edit-modal .modern-modal-container::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(135deg, #4f46e5 0%, #2563eb 100%);
        cursor: pointer;
    }

    .bodega-edit-modal .modern-modal-container::-webkit-scrollbar-button {
        display: none;
    }

    /* Scrollbar para Firefox */
    .bodega-edit-modal .modern-modal-container {
        scrollbar-width: thin;
        scrollbar-color: #6366f1 #2d3748;
    }

    .bodega-edit-modal .section-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 16px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    }

    .bodega-edit-modal .section-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 18px;
        font-weight: 600;
        color: #1a202c;
        margin: 0 0 16px 0;
    }

    .bodega-edit-modal .section-icon {
        width: 20px;
        height: 20px;
        color: #3b82f6;
        stroke-width: 2;
    }

    .bodega-edit-modal .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 16px;
    }

    .bodega-edit-modal .form-group {
        display: flex;
        flex-direction: column;
    }

    .bodega-edit-modal .form-label {
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

    .bodega-edit-modal .label-icon {
        width: 16px;
        height: 16px;
        color: #3b82f6;
        stroke-width: 2;
    }

    .bodega-edit-modal .form-input,
    .bodega-edit-modal .form-select,
    .bodega-edit-modal .form-textarea {
        width: 100%;
        padding: 10px 14px;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        font-size: 14px;
        color: #1f2937;
        background: #f9fafb;
        transition: all 0.3s ease;
    }

    .bodega-edit-modal .form-input:focus,
    .bodega-edit-modal .form-select:focus,
    .bodega-edit-modal .form-textarea:focus {
        outline: none;
        border-color: #3b82f6;
        background: white;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .bodega-edit-modal .form-input:read-only {
        background: #e5e7eb;
        cursor: not-allowed;
    }

    .bodega-edit-modal .form-textarea {
        resize: vertical;
        min-height: 60px;
        font-family: inherit;
    }

    .bodega-edit-modal .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        margin-top: 24px;
        padding-top: 20px;
        border-top: 2px solid #e5e7eb;
    }

    .bodega-edit-modal .btn {
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

    .bodega-edit-modal .btn svg {
        width: 18px;
        height: 18px;
        stroke-width: 2.5;
    }

    .bodega-edit-modal .btn-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
        color: white;
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
    }

    .bodega-edit-modal .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(59, 130, 246, 0.4);
    }

    .bodega-edit-modal .btn-primary:active {
        transform: translateY(0);
    }

    .bodega-edit-modal .btn-secondary {
        background: #e5e7eb;
        color: #4b5563;
    }

    .bodega-edit-modal .btn-secondary:hover {
        background: #d1d5db;
    }

    .bodega-edit-modal .notification {
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

    .bodega-edit-modal .notification.success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }

    .bodega-edit-modal .notification.error {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
    }

    .bodega-edit-modal .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }

    .bodega-edit-modal .prendas-container {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .bodega-edit-modal .prenda-card {
        background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 16px;
        transition: all 0.3s ease;
        opacity: 1;
        transform: scale(1);
    }

    .bodega-edit-modal .prenda-card:hover {
        border-color: #3b82f6;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
    }

    @keyframes slideInCard {
        from {
            opacity: 0;
            transform: translateY(-10px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .bodega-edit-modal .prenda-card {
        animation: slideInCard 0.3s ease-out;
    }

    .bodega-edit-modal .prenda-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }

    .bodega-edit-modal .prenda-number {
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

    .bodega-edit-modal .prenda-content {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .bodega-edit-modal .form-label-small {
        font-size: 13px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .bodega-edit-modal .form-input-compact {
        width: 100%;
        padding: 10px 14px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
        color: #1f2937;
        background: white;
        transition: all 0.3s ease;
    }

    .bodega-edit-modal .form-input-compact:focus {
        outline: none;
        border-color: #3b82f6;
        background: white;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
    }

    .bodega-edit-modal .tallas-section {
        margin-top: 8px;
    }

    .bodega-edit-modal .tallas-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-bottom: 12px;
    }

    .bodega-edit-modal .tallas-list .talla-item {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        gap: 8px;
        align-items: center;
        animation: slideInTalla 0.2s ease-out;
    }

    @keyframes slideInTalla {
        from {
            opacity: 0;
            transform: translateX(-10px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .bodega-edit-modal .tallas-list input {
        padding: 8px 12px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
        color: #1f2937;
        background: white;
        transition: all 0.2s ease;
    }

    .bodega-edit-modal .tallas-list input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
    }

    .bodega-edit-modal .btn-add-talla {
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

    .bodega-edit-modal .btn-add-talla:hover {
        border-color: #3b82f6;
        background: #eff6ff;
    }

    .bodega-edit-modal .btn-add-talla svg {
        width: 16px;
        height: 16px;
        stroke-width: 2;
    }

    .bodega-edit-modal .btn-delete {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fee2e2;
        border: 2px solid #fecaca;
        border-radius: 8px;
        color: #ef4444;
        cursor: pointer;
        transition: all 0.3s ease;
        flex-shrink: 0;
    }

    .bodega-edit-modal .btn-delete:hover {
        background: #ef4444;
        color: white;
        border-color: #dc2626;
        transform: scale(1.1);
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
    }

    .bodega-edit-modal .btn-delete:active {
        transform: scale(0.95);
    }

    .bodega-edit-modal .btn-delete svg {
        width: 16px;
        height: 16px;
        stroke-width: 2.5;
    }

    .bodega-edit-modal .eliminar-prenda-btn {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fee2e2;
        border: 2px solid #fecaca;
        border-radius: 6px;
        color: #ef4444;
        cursor: pointer;
        font-weight: bold;
        font-size: 18px;
        transition: all 0.2s ease;
        flex-shrink: 0;
    }

    .bodega-edit-modal .eliminar-prenda-btn:hover {
        background: #ef4444;
        color: white;
        border-color: #dc2626;
        transform: scale(1.1);
    }

    .bodega-edit-modal .eliminar-prenda-btn:active {
        transform: scale(0.9);
    }

    .bodega-edit-modal .eliminar-talla-btn {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fee2e2;
        border: 2px solid #fecaca;
        border-radius: 6px;
        color: #ef4444;
        cursor: pointer;
        font-weight: bold;
        font-size: 18px;
        transition: all 0.2s ease;
        flex-shrink: 0;
    }

    .bodega-edit-modal .eliminar-talla-btn:hover {
        background: #ef4444;
        color: white;
        border-color: #dc2626;
        transform: scale(1.1);
    }

    .bodega-edit-modal .eliminar-talla-btn:active {
        transform: scale(0.9);
    }

    @media (max-width: 768px) {
        .bodega-edit-modal .modern-modal-container {
            width: 100%;
            max-width: 100%;
            max-height: 100vh;
            min-height: 100vh;
            border-radius: 0;
            transform: translateY(0) scale(1);
        }

        .bodega-edit-modal .form-grid {
            grid-template-columns: 1fr;
        }

        .bodega-edit-modal .form-content {
            padding: 20px;
        }

        .bodega-edit-modal .modal-header {
            padding: 16px 20px;
        }

        .bodega-edit-modal .modal-title {
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
