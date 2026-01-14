<!-- PASO 4: Prendas Editables - Componente Reutilizable -->
<div class="form-section" id="seccion-prendas" style="display: none;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2 style="margin: 0;">
            <span>4</span> <span id="titulo-prendas-dinamico">Prendas Técnicas del Logo</span>
        </h2>
        <button type="button" 
            id="btn-agregar-prenda-tecnica-logo"
            onclick="abrirModalAgregarPrendaTecnicaLogo()"
            style="display: none; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.3s; box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2); display: flex; align-items: center; gap: 0.5rem;" 
            onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(59, 130, 246, 0.3)'"
            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(59, 130, 246, 0.2)'">
            <span class="material-symbols-rounded" style="font-size: 1.1rem;">add_circle</span>
            Agregar Prenda Técnica
        </button>
    </div>

    <div id="prendas-container-editable">
        <div class="empty-state">
            <p>Selecciona una cotización para ver las prendas</p>
        </div>
    </div>
</div>
