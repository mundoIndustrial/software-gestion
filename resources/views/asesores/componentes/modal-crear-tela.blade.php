<!-- Modal Crear Tela -->
<div id="modalCrearTela" class="modal-overlay" style="display: none;">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">
                <span class="material-symbols-rounded">add_circle</span>
                Nueva Tela
            </h3>
            <button type="button" class="modal-close" onclick="cerrarModalCrearTela()">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        <form id="formCrearTela" onsubmit="crearTela(event)">
            @csrf
            
            <div class="modal-body">
                <!-- Categoría -->
                <div class="form-group">
                    <label for="categoria_nueva" class="form-label">
                        <span class="material-symbols-rounded">category</span>
                        Categoría
                    </label>
                    <div class="input-with-button">
                        <select id="categoria_nueva" name="categoria" class="form-select" required>
                            <option value="">Seleccionar categoría...</option>
                            @foreach($categorias ?? [] as $categoria)
                                <option value="{{ $categoria }}">{{ $categoria }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn-icon" onclick="mostrarInputNuevaCategoria()" title="Nueva categoría">
                            <span class="material-symbols-rounded">add</span>
                        </button>
                    </div>
                    
                    <!-- Input para nueva categoría (oculto por defecto) -->
                    <div id="nuevaCategoriaContainer" style="display: none; margin-top: 0.75rem;">
                        <input type="text" 
                               id="nueva_categoria_input" 
                               class="form-input" 
                               placeholder="Nombre de la nueva categoría">
                        <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                            <button type="button" class="btn btn-sm btn-primary" onclick="agregarNuevaCategoria()">
                                Agregar
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="cancelarNuevaCategoria()">
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Nombre de la Tela -->
                <div class="form-group">
                    <label for="nombre_tela_nueva" class="form-label">
                        <span class="material-symbols-rounded">texture</span>
                        Nombre de la Tela
                    </label>
                    <input type="text" 
                           id="nombre_tela_nueva" 
                           name="nombre_tela" 
                           class="form-input" 
                           placeholder="Ej: Algodón Premium, Poliéster Stretch..."
                           required>
                </div>

                <!-- Stock Inicial -->
                <div class="form-group">
                    <label for="stock_inicial" class="form-label">
                        <span class="material-symbols-rounded">inventory_2</span>
                        Stock Inicial (metros)
                    </label>
                    <input type="number" 
                           id="stock_inicial" 
                           name="stock" 
                           class="form-input" 
                           step="0.01" 
                           min="0" 
                           value="0"
                           required>
                </div>

                <!-- Metraje Sugerido -->
                <div class="form-group">
                    <label for="metraje_sugerido_nuevo" class="form-label">
                        <span class="material-symbols-rounded">straighten</span>
                        Metraje Sugerido (metros)
                        <span class="label-optional">(Opcional)</span>
                    </label>
                    <input type="number" 
                           id="metraje_sugerido_nuevo" 
                           name="metraje_sugerido" 
                           class="form-input" 
                           step="0.01" 
                           min="0"
                           placeholder="Cantidad recomendada para mantener en stock">
                    <small class="form-hint">Este valor ayuda a identificar cuándo reabastecer</small>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalCrearTela()">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
                    <span class="material-symbols-rounded">save</span>
                    Crear Tela
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.input-with-button {
    display: flex;
    gap: 0.5rem;
}

.form-select {
    flex: 1;
    padding: 0.875rem 1rem;
    border: 2px solid var(--border-color);
    border-radius: 10px;
    font-size: 0.95rem;
    background: var(--bg-card);
    color: var(--text-primary);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.form-select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 4px rgba(0, 102, 204, 0.1);
}

.btn-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-icon:hover {
    background: var(--primary-dark);
    transform: scale(1.05);
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

.label-optional {
    font-size: 0.875rem;
    color: var(--text-secondary);
    font-weight: 400;
}

.form-hint {
    display: block;
    margin-top: 0.5rem;
    font-size: 0.875rem;
    color: var(--text-secondary);
}
</style>
