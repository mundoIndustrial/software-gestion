<!-- Modal Ajustar Stock -->
<div id="modalAjustarStock" class="modal-overlay" style="display: none;">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">
                <span class="material-symbols-rounded">inventory</span>
                Ajustar Stock
            </h3>
            <button type="button" class="modal-close" onclick="cerrarModalAjustarStock()">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        <form id="formAjustarStock" onsubmit="ajustarStock(event)">
            @csrf
            <input type="hidden" id="tela_id_ajuste" name="tela_id">
            
            <div class="modal-body">
                <!-- Info de la tela -->
                <div class="info-tela-card">
                    <div class="info-item">
                        <span class="info-label">Tela:</span>
                        <span class="info-value" id="tela_nombre_ajuste">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Stock Actual:</span>
                        <span class="info-value stock-actual" id="stock_actual_ajuste">0 m</span>
                    </div>
                </div>

                <!-- Tipo de Acción -->
                <div class="form-group">
                    <label class="form-label">
                        <span class="material-symbols-rounded">swap_horiz</span>
                        Tipo de Acción
                    </label>
                    <div class="radio-group">
                        <label class="radio-option entrada">
                            <input type="radio" name="tipo_accion" value="entrada" checked onchange="actualizarVistaPrevia()">
                            <span class="radio-custom"></span>
                            <span class="radio-label">
                                <span class="material-symbols-rounded">add_circle</span>
                                Entrada (Aumentar)
                            </span>
                        </label>
                        <label class="radio-option salida">
                            <input type="radio" name="tipo_accion" value="salida" onchange="actualizarVistaPrevia()">
                            <span class="radio-custom"></span>
                            <span class="radio-label">
                                <span class="material-symbols-rounded">remove_circle</span>
                                Salida (Descontar)
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Cantidad -->
                <div class="form-group">
                    <label for="cantidad_ajuste" class="form-label">
                        <span class="material-symbols-rounded">straighten</span>
                        Cantidad (metros)
                    </label>
                    <input type="number" 
                           id="cantidad_ajuste" 
                           name="cantidad" 
                           class="form-input" 
                           step="0.01" 
                           min="0.01" 
                           required
                           oninput="actualizarVistaPrevia()">
                </div>

                <!-- Vista Previa -->
                <div class="preview-card">
                    <div class="preview-title">Vista Previa</div>
                    <div class="preview-content">
                        <div class="preview-item">
                            <span>Stock Actual:</span>
                            <span id="preview_stock_actual">0 m</span>
                        </div>
                        <div class="preview-operator" id="preview_operator">+</div>
                        <div class="preview-item">
                            <span>Cantidad:</span>
                            <span id="preview_cantidad">0 m</span>
                        </div>
                        <div class="preview-divider"></div>
                        <div class="preview-item resultado">
                            <span>Nuevo Stock:</span>
                            <span id="preview_stock_nuevo" class="stock-nuevo">0 m</span>
                        </div>
                    </div>
                </div>

                <!-- Observaciones -->
                <div class="form-group">
                    <label for="observaciones_ajuste" class="form-label">
                        <span class="material-symbols-rounded">description</span>
                        Observaciones (Opcional)
                    </label>
                    <textarea id="observaciones_ajuste" 
                              name="observaciones" 
                              class="form-textarea" 
                              rows="3" 
                              placeholder="Motivo del ajuste, proveedor, orden de compra, etc."></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalAjustarStock()">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
                    <span class="material-symbols-rounded">save</span>
                    Guardar Ajuste
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-container {
    background: var(--bg-card);
    border-radius: 16px;
    box-shadow: var(--shadow-xl);
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem 2rem;
    border-bottom: 2px solid var(--border-color);
}

.modal-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
}

.modal-title .material-symbols-rounded {
    font-size: 1.75rem;
    color: var(--primary-color);
}

.modal-close {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 8px;
    transition: all 0.2s ease;
    color: var(--text-secondary);
}

.modal-close:hover {
    background: var(--bg-hover);
    color: var(--danger-color);
}

.modal-body {
    padding: 2rem;
}

.info-tela-card {
    background: var(--bg-secondary);
    border-radius: 12px;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
    display: flex;
    gap: 2rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-label {
    font-size: 0.875rem;
    color: var(--text-secondary);
    font-weight: 600;
}

.info-value {
    font-size: 1.125rem;
    color: var(--text-primary);
    font-weight: 700;
}

.stock-actual {
    color: var(--primary-color);
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.75rem;
    font-size: 0.95rem;
}

.form-label .material-symbols-rounded {
    font-size: 1.25rem;
    color: var(--primary-color);
}

.radio-group {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.radio-option {
    position: relative;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    border: 2px solid var(--border-color);
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.radio-option:hover {
    border-color: var(--primary-color);
    background: var(--bg-hover);
}

.radio-option input[type="radio"] {
    position: absolute;
    opacity: 0;
}

.radio-option input[type="radio"]:checked ~ .radio-custom {
    border-color: var(--primary-color);
    background: var(--primary-color);
}

.radio-option input[type="radio"]:checked ~ .radio-custom::after {
    opacity: 1;
}

.radio-option.entrada input[type="radio"]:checked ~ .radio-label {
    color: var(--success-color);
}

.radio-option.salida input[type="radio"]:checked ~ .radio-label {
    color: var(--danger-color);
}

.radio-custom {
    width: 20px;
    height: 20px;
    border: 2px solid var(--border-color);
    border-radius: 50%;
    position: relative;
    transition: all 0.3s ease;
}

.radio-custom::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 8px;
    height: 8px;
    background: white;
    border-radius: 50%;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.radio-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: var(--text-primary);
}

.form-input,
.form-textarea {
    width: 100%;
    padding: 0.875rem 1rem;
    border: 2px solid var(--border-color);
    border-radius: 10px;
    font-size: 0.95rem;
    background: var(--bg-card);
    color: var(--text-primary);
    transition: all 0.3s ease;
}

.form-input:focus,
.form-textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 4px rgba(0, 102, 204, 0.1);
}

.preview-card {
    background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-hover) 100%);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border: 2px solid var(--border-color);
}

.preview-title {
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 1rem;
    font-size: 1rem;
}

.preview-content {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.preview-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.95rem;
}

.preview-item span:first-child {
    color: var(--text-secondary);
    font-weight: 600;
}

.preview-item span:last-child {
    color: var(--text-primary);
    font-weight: 700;
}

.preview-operator {
    text-align: center;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary-color);
}

.preview-divider {
    height: 2px;
    background: var(--border-color);
    margin: 0.5rem 0;
}

.preview-item.resultado {
    font-size: 1.125rem;
}

.stock-nuevo {
    color: var(--success-color);
    font-size: 1.25rem;
}

.modal-footer {
    display: flex;
    gap: 1rem;
    padding: 1.5rem 2rem;
    border-top: 2px solid var(--border-color);
    justify-content: flex-end;
}

.btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1.5rem;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-secondary {
    background: var(--bg-secondary);
    color: var(--text-primary);
}

.btn-secondary:hover {
    background: var(--bg-hover);
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
    color: white;
    box-shadow: var(--shadow-sm);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

@media (max-width: 768px) {
    .modal-container {
        width: 95%;
        max-height: 95vh;
    }

    .radio-group {
        grid-template-columns: 1fr;
    }

    .info-tela-card {
        flex-direction: column;
        gap: 1rem;
    }
}
</style>
