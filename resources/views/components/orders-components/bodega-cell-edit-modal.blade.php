<!-- Modal para editar una celda individual -->
<div id="bodegaCellEditModal" class="bodega-cell-edit-modal" style="display: none;">
    <div class="cell-modal-overlay" onclick="closeCellEditModal()"></div>
    <div class="cell-modal-container">
        <div class="cell-modal-header">
            <h3 id="cellModalTitle">Editar Campo</h3>
            <button class="cell-modal-close" onclick="closeCellEditModal()">✕</button>
        </div>

        <div class="cell-modal-content">
            <div class="form-group">
                <label id="cellModalLabel">Valor:</label>
                <textarea id="cellModalInput" class="cell-modal-input" placeholder="Ingrese el valor" rows="6"></textarea>
            </div>
        </div>

        <div class="cell-modal-footer">
            <button class="cell-modal-btn cancel" onclick="closeCellEditModal()">Cancelar</button>
            <button class="cell-modal-btn save" onclick="saveCellEdit()">Guardar</button>
        </div>
    </div>
</div>

<style>
    .bodega-cell-edit-modal {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        visibility: hidden;
        opacity: 0;
        transition: opacity 0.2s ease, visibility 0.2s ease;
    }

    .bodega-cell-edit-modal[style*="display: flex"] {
        visibility: visible;
        opacity: 1;
    }

    .cell-modal-overlay {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(6px);
    }

    .cell-modal-container {
        position: relative;
        background: #ffffff;
        border-radius: 14px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
        width: 400px;
        height: 350px;
        max-width: 90vw;
        max-height: 90vh;
        z-index: 10000;
        animation: cellModalSlideIn 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    /* Responsive para tablets y móviles */
    @media (max-width: 768px) {
        .cell-modal-container {
            width: 85vw;
            height: 80vh;
        }
    }

    @media (max-width: 480px) {
        .cell-modal-container {
            width: 90vw;
            height: 85vh;
        }
    }

    @keyframes cellModalSlideIn {
        from {
            opacity: 0;
            transform: scale(0.85) translateY(-20px);
        }
        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    .cell-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 20px;
        border-bottom: 1px solid #f0f0f0;
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        flex-shrink: 0;
    }

    .cell-modal-header h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 700;
        color: #1a1a1a;
        letter-spacing: -0.3px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .cell-modal-close {
        background: none;
        border: none;
        font-size: 28px;
        color: #999;
        cursor: pointer;
        padding: 0;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        transition: all 0.2s ease;
        line-height: 1;
    }

    .cell-modal-close:hover {
        background: #f0f0f0;
        color: #333;
    }

    .cell-modal-content {
        padding: 8px;
        background: #ffffff;
        overflow-y: auto;
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .cell-modal-content .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
        width: 100%;
        min-width: 0;
    }

    /* Scroll bar personalizado */
    .cell-modal-content::-webkit-scrollbar {
        width: 6px;
    }

    .cell-modal-content::-webkit-scrollbar-track {
        background: #f5f5f5;
    }

    .cell-modal-content::-webkit-scrollbar-thumb {
        background: #d0d0d0;
        border-radius: 3px;
    }

    .cell-modal-content::-webkit-scrollbar-thumb:hover {
        background: #b0b0b0;
    }

    .cell-modal-content label {
        font-size: 12px;
        font-weight: 700;
        color: #333;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .cell-modal-input {
        padding: 6px 8px;
        border: 2px solid #e0e0e0;
        border-radius: 6px;
        font-size: 12px;
        color: #1a1a1a;
        background: #ffffff;
        transition: all 0.2s ease;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        width: 370px;
        height: 116px;
        box-sizing: border-box;
        resize: none;
        overflow-y: auto;
        overflow-x: hidden;
        word-wrap: break-word;
        white-space: pre-wrap;
        line-height: 1.4;
    }

    .cell-modal-input::placeholder {
        color: #999;
    }

    .cell-modal-input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.08);
        background: #f8fbff;
    }

    /* Scroll bar del textarea */
    .cell-modal-input::-webkit-scrollbar {
        width: 6px;
    }

    .cell-modal-input::-webkit-scrollbar-track {
        background: #f5f5f5;
        border-radius: 3px;
    }

    .cell-modal-input::-webkit-scrollbar-thumb {
        background: #d0d0d0;
        border-radius: 3px;
    }

    .cell-modal-input::-webkit-scrollbar-thumb:hover {
        background: #b0b0b0;
    }

    .cell-modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        padding: 12px 16px;
        border-top: 1px solid #f0f0f0;
        background: #f8f9fa;
        flex-shrink: 0;
    }

    .cell-modal-btn {
        padding: 9px 18px;
        border: none;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        letter-spacing: 0.3px;
    }

    .cell-modal-btn.cancel {
        background: #e8e8e8;
        color: #333;
    }

    .cell-modal-btn.cancel:hover {
        background: #d8d8d8;
        transform: translateY(-1px);
    }

    .cell-modal-btn.cancel:active {
        transform: translateY(0);
    }

    .cell-modal-btn.save {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    .cell-modal-btn.save:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
    }

    .cell-modal-btn.save:active {
        transform: translateY(0);
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
    }
</style>
