<!-- PASO 3: Prendas del Pedido - Componente Reutilizable -->
<div class="form-section" id="seccion-prendas" style="display: none;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 style="margin: 0;">
            <span>3</span> <span id="titulo-prendas-dinamico">Prendas del Pedido</span>
        </h2>
        <button type="button" onclick="abrirEditarPrendas()" style="background: #1e40af; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-size: 0.95rem; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.backgroundColor='#1e3a8a'" onmouseout="this.style.backgroundColor='#1e40af'">
            👕 Editar Prendas
        </button>
    </div>

    <div id="prendas-container-editable">
        <div class="empty-state">
            <p>Agrega ítems al pedido</p>
        </div>
    </div>
</div>
