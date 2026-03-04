<!-- Modal Selector: Elegir modo de configuración del proceso -->
<div id="modal-selector-modo-proceso" class="modal-overlay" style="z-index: 99999999999 !important; display: none;">
    <div style="background: white; border-radius: 16px; max-width: 560px; width: 92%; padding: 0; box-shadow: 0 25px 50px rgba(0,0,0,0.35); overflow: hidden;">
        
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%); padding: 1.25rem 1.5rem; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <span class="material-symbols-rounded" id="selector-modo-icon" style="font-size: 1.8rem; color: #60a5fa;">settings</span>
                <div>
                    <h3 id="selector-modo-titulo" style="margin: 0; font-size: 1.15rem; font-weight: 800; color: white;">Configurar Proceso</h3>
                    <p style="margin: 0; color: #94a3b8; font-size: 0.8rem;">¿Cómo deseas aplicar este proceso?</p>
                </div>
            </div>
            <button onclick="cerrarSelectorModoProceso(true)" style="background: none; border: none; cursor: pointer; color: #94a3b8; padding: 0.25rem;">
                <span class="material-symbols-rounded" style="font-size: 1.5rem;">close</span>
            </button>
        </div>

        <!-- Opciones -->
        <div style="padding: 1.5rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            
            <!-- Opción 1: Para TODAS las tallas -->
            <div id="opcion-modo-todas" 
                onclick="seleccionarModoProcesoTodas()"
                style="cursor: pointer; border: 2px solid #d1fae5; border-radius: 12px; padding: 1.25rem 1rem; text-align: center; transition: all 0.2s; background: #f0fdf4;"
                onmouseover="this.style.borderColor='#16a34a'; this.style.boxShadow='0 4px 12px rgba(22,163,74,0.15)'; this.style.transform='translateY(-2px)';"
                onmouseout="this.style.borderColor='#d1fae5'; this.style.boxShadow='none'; this.style.transform='translateY(0)';">
                <span class="material-symbols-rounded" style="font-size: 2.5rem; color: #16a34a; display: block; margin-bottom: 0.5rem;">done_all</span>
                <h4 style="margin: 0 0 0.35rem; font-size: 1rem; font-weight: 800; color: #111827;">Para Todas</h4>
                <p style="margin: 0; font-size: 0.75rem; color: #6b7280; line-height: 1.3;">
                    Una misma ubicación, observación y fotos para <strong>todas las tallas</strong>
                </p>
            </div>

            <!-- Opción 2: Editar POR TALLAS -->
            <div id="opcion-modo-tallas" 
                onclick="seleccionarModoProcesoTallas()"
                style="cursor: pointer; border: 2px solid #dbeafe; border-radius: 12px; padding: 1.25rem 1rem; text-align: center; transition: all 0.2s; background: #eff6ff;"
                onmouseover="this.style.borderColor='#2563eb'; this.style.boxShadow='0 4px 12px rgba(37,99,235,0.15)'; this.style.transform='translateY(-2px)';"
                onmouseout="this.style.borderColor='#dbeafe'; this.style.boxShadow='none'; this.style.transform='translateY(0)';">
                <span class="material-symbols-rounded" style="font-size: 2.5rem; color: #2563eb; display: block; margin-bottom: 0.5rem;">edit_note</span>
                <h4 style="margin: 0 0 0.35rem; font-size: 1rem; font-weight: 800; color: #111827;">Por Tallas</h4>
                <p style="margin: 0; font-size: 0.75rem; color: #6b7280; line-height: 1.3;">
                    Configurar ubicación, observación y foto <strong>para cada talla</strong>
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div style="padding: 0 1.5rem 1.25rem; text-align: center;">
            <button onclick="cerrarSelectorModoProceso(true)" style="background: #f3f4f6; color: #6b7280; border: none; padding: 0.6rem 1.5rem; border-radius: 8px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: all 0.2s;"
                onmouseover="this.style.background='#e5e7eb';"
                onmouseout="this.style.background='#f3f4f6';">
                Cancelar
            </button>
        </div>
    </div>
</div>
