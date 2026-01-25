<div style="padding: 0 0.5rem 0 0; max-width: 100%; margin: 0 auto;">
    <!-- HEADER PROFESIONAL -->
    <div style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); border-radius: 12px; padding: 20px 30px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(30, 58, 138, 0.2);">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 30px;">
            <!-- TÍTULO CON ICONO -->
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="background: rgba(255,255,255,0.15); padding: 10px 12px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-list" style="color: white; font-size: 24px;"></i>
                </div>
                <div>
                    <h1 style="margin: 0; font-size: 1.5rem; color: white; font-weight: 700;">Lista de Pedidos</h1>
                    <p style="margin: 0; color: rgba(255,255,255,0.7); font-size: 0.85rem;">Gestiona tus pedidos de producción</p>
                </div>
            </div>

            <!-- BUSCADOR -->
            <div style="flex: 1; max-width: 500px; position: relative;">
                <input 
                    type="text" 
                    id="mainSearchInput" 
                    placeholder="Buscar por número de pedido o cliente..." 
                    style="width: 100%; padding: 10px 40px 10px 40px; border: 2px solid rgba(255,255,255,0.3); border-radius: 8px; background: rgba(255,255,255,0.95); font-size: 0.9rem; transition: all 0.3s; color: #1e40af; font-weight: 500;"
                    onfocus="this.style.background='white'; this.style.borderColor='white'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'"
                    onblur="this.style.background='rgba(255,255,255,0.95)'; this.style.borderColor='rgba(255,255,255,0.3)'; this.style.boxShadow='none'"
                >
                <span class="material-symbols-rounded" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #1e40af; pointer-events: none;">search</span>
                <button type="button" id="clearMainSearch" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #6b7280; cursor: pointer; padding: 4px; display: none; border-radius: 4px; transition: all 0.2s;" onmouseover="this.style.background='#f3f4f6'; this.style.color='#1e40af'" onmouseout="this.style.background='none'; this.style.color='#6b7280'">
                    <span class="material-symbols-rounded" style="font-size: 20px;">close</span>
                </button>
            </div>

            <!-- BOTÓN REGISTRAR -->
            <a href="{{ route('asesores.pedidos-editable.crear-desde-cotizacion') }}" style="background: white; color: #1e40af; padding: 10px 18px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; box-shadow: 0 2px 8px rgba(0,0,0,0.1); white-space: nowrap;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Registrar
            </a>
        </div>
    </div>
