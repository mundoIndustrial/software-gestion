<div style="background: rgba(255, 255, 255, 0.03); padding: 24px; border-radius: 12px; margin-bottom: 24px; border: 1px solid rgba(59, 130, 246, 0.15);">
    <h2 style="margin: 0 0 20px 0; font-size: 18px; color: white; display: flex; align-items: center; gap: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
        <span class="material-symbols-rounded" style="color: #3B82F6; font-size: 24px;">settings</span>
        Parámetros de Producción
    </h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
        <div>
            <label style="display: block; margin-bottom: 8px; color: #94a3b8; font-size: 13px; font-weight: 500;">Total Operarios</label>
            <input type="number" x-model="parametros.total_operarios" @change="updateParametros()"
                   style="width: 100%; padding: 12px; border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 6px; font-size: 14px; transition: all 0.3s; background: rgba(59, 130, 246, 0.1); color: white; font-weight: 600;"
                   onfocus="this.style.borderColor='rgba(59, 130, 246, 0.5)'; this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.1)'" 
                   onblur="this.style.borderColor='rgba(59, 130, 246, 0.3)'; this.style.boxShadow='none'">
        </div>
        <div>
            <label style="display: block; margin-bottom: 8px; color: #94a3b8; font-size: 13px; font-weight: 500;">Turnos</label>
            <input type="number" x-model="parametros.turnos" @change="updateParametros()"
                   style="width: 100%; padding: 12px; border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 6px; font-size: 14px; transition: all 0.3s; background: rgba(59, 130, 246, 0.1); color: white; font-weight: 600;"
                   onfocus="this.style.borderColor='rgba(59, 130, 246, 0.5)'; this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.1)'" 
                   onblur="this.style.borderColor='rgba(59, 130, 246, 0.3)'; this.style.boxShadow='none'">
        </div>
        <div>
            <label style="display: block; margin-bottom: 8px; color: #94a3b8; font-size: 13px; font-weight: 500;">Horas por Turno</label>
            <input type="number" step="0.1" x-model="parametros.horas_por_turno" @change="updateParametros()"
                   style="width: 100%; padding: 12px; border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 6px; font-size: 14px; transition: all 0.3s; background: rgba(59, 130, 246, 0.1); color: white; font-weight: 600;"
                   onfocus="this.style.borderColor='rgba(59, 130, 246, 0.5)'; this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.1)'" 
                   onblur="this.style.borderColor='rgba(59, 130, 246, 0.3)'; this.style.boxShadow='none'">
        </div>
        <div>
            <label style="display: block; margin-bottom: 8px; color: #94a3b8; font-size: 13px; font-weight: 500;">% Eficiencia</label>
            <input type="number" step="0.01" min="0" max="100" x-model="parametros.porcentaje_eficiencia" @change="updateParametros()"
                   style="width: 100%; padding: 12px; border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 6px; font-size: 14px; transition: all 0.3s; background: rgba(59, 130, 246, 0.1); color: white; font-weight: 600;"
                   onfocus="this.style.borderColor='rgba(59, 130, 246, 0.5)'; this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.1)'" 
                   onblur="this.style.borderColor='rgba(59, 130, 246, 0.3)'; this.style.boxShadow='none'">
        </div>
    </div>
</div>
