<div style="background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%); padding: 32px; border-radius: 20px; border: 1px solid rgba(255, 157, 88, 0.2); box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);">
    <!-- Header -->
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 28px;">
        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #ff9d58 0%, #ff7b3d 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(255, 157, 88, 0.3);">
            <svg style="width: 28px; height: 28px; color: white;" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <h2 style="margin: 0; font-size: 24px; color: white; font-weight: 700;">
            Métricas Globales de Producción
        </h2>
    </div>

    <!-- Grid de Tarjetas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 24px;">
        <!-- Tarjeta: Parámetros de Configuración -->
        <div style="background: white; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                <svg style="width: 20px; height: 20px; color: #ff9d58;" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #1a202c;">Configuración</h3>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div>
                    <label style="display: block; font-size: 13px; color: #64748b; margin-bottom: 6px; font-weight: 500;">Total de operarios</label>
                    <input type="number" x-model="parametros.total_operarios" @change="updateParametros()"
                           style="width: 100%; padding: 10px 14px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 16px; font-weight: 600; color: #2d3748; background: #f7fafc; transition: all 0.3s;"
                           onfocus="this.style.borderColor='#ff9d58'; this.style.background='white'" onblur="this.style.borderColor='#e2e8f0'; this.style.background='#f7fafc'">
                </div>
                
                <div>
                    <label style="display: block; font-size: 13px; color: #64748b; margin-bottom: 6px; font-weight: 500;">Turnos de trabajo</label>
                    <input type="number" x-model="parametros.turnos" @change="updateParametros()"
                           style="width: 100%; padding: 10px 14px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 16px; font-weight: 600; color: #2d3748; background: #f7fafc; transition: all 0.3s;"
                           onfocus="this.style.borderColor='#ff9d58'; this.style.background='white'" onblur="this.style.borderColor='#e2e8f0'; this.style.background='#f7fafc'">
                </div>
                
                <div>
                    <label style="display: block; font-size: 13px; color: #64748b; margin-bottom: 6px; font-weight: 500;">Horas por turno</label>
                    <input type="number" step="0.1" x-model="parametros.horas_por_turno" @change="updateParametros()"
                           style="width: 100%; padding: 10px 14px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 16px; font-weight: 600; color: #2d3748; background: #f7fafc; transition: all 0.3s;"
                           onfocus="this.style.borderColor='#ff9d58'; this.style.background='white'" onblur="this.style.borderColor='#e2e8f0'; this.style.background='#f7fafc'">
                </div>
            </div>
        </div>

        <!-- Tarjeta: Tiempos Disponibles -->
        <div style="background: white; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                <svg style="width: 20px; height: 20px; color: #ff9d58;" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <circle cx="12" cy="12" r="10" stroke-width="2"/>
                    <path d="M12 6v6l4 2" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #1a202c;">Tiempos Disponibles</h3>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div style="padding: 14px; background: #f7fafc; border-radius: 10px; border-left: 3px solid #ff9d58;">
                    <p style="margin: 0 0 4px 0; font-size: 12px; color: #64748b; font-weight: 500;">En Horas</p>
                    <p style="margin: 0; font-size: 24px; font-weight: 700; color: #2d3748;" x-text="parseFloat(metricas.tiempo_disponible_horas || 0).toFixed(2)"></p>
                </div>
                
                <div style="padding: 14px; background: #f7fafc; border-radius: 10px; border-left: 3px solid #ff9d58;">
                    <p style="margin: 0 0 4px 0; font-size: 12px; color: #64748b; font-weight: 500;">En Segundos</p>
                    <p style="margin: 0; font-size: 24px; font-weight: 700; color: #2d3748;" x-text="parseFloat(metricas.tiempo_disponible_segundos || 0).toFixed(0)"></p>
                </div>
            </div>
        </div>

        <!-- Tarjeta: SAM y Métricas Clave -->
        <div style="background: linear-gradient(135deg, #ff9d58 0%, #ff7b3d 100%); border-radius: 16px; padding: 24px; box-shadow: 0 8px 16px rgba(255, 157, 88, 0.3); color: white;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                <svg style="width: 20px; height: 20px; color: white;" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M13 10V3L4 14h7v7l9-11h-7z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: white;">Métricas Clave</h3>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div style="padding: 16px; background: rgba(255, 255, 255, 0.15); border-radius: 10px; backdrop-filter: blur(10px);">
                    <p style="margin: 0 0 6px 0; font-size: 13px; color: rgba(255, 255, 255, 0.9); font-weight: 500;">SAM Total</p>
                    <p style="margin: 0; font-size: 32px; font-weight: 700; color: white;" x-text="parseFloat(metricas.sam_total).toFixed(1)"></p>
                </div>
                
                <div style="padding: 14px; background: rgba(255, 255, 255, 0.15); border-radius: 10px; backdrop-filter: blur(10px);">
                    <p style="margin: 0 0 4px 0; font-size: 12px; color: rgba(255, 255, 255, 0.9); font-weight: 500;">SAM Real</p>
                    <p style="margin: 0; font-size: 20px; font-weight: 700; color: white;" x-text="metricas.sam_real ? parseFloat(metricas.sam_real).toFixed(1) : 'N/A'"></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Grid de Métricas Secundarias -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <!-- Meta Real -->
        <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);">
            <p style="margin: 0 0 8px 0; font-size: 13px; color: #64748b; font-weight: 500;">Meta Real</p>
            <p style="margin: 0; font-size: 28px; font-weight: 700; color: #ff9d58;" x-text="metricas.meta_real || 'N/A'"></p>
        </div>

        <!-- Meta Teórica -->
        <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);">
            <p style="margin: 0 0 8px 0; font-size: 13px; color: #64748b; font-weight: 500;">Meta Teórica</p>
            <p style="margin: 0; font-size: 28px; font-weight: 700; color: #2d3748;" x-text="metricas.meta_teorica || 'N/A'"></p>
        </div>

        <!-- Meta Sugerida -->
        <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); border: 2px solid #ff9d58;">
            <p style="margin: 0 0 8px 0; font-size: 13px; color: #64748b; font-weight: 500;">Meta Sugerida (85%)</p>
            <p style="margin: 0; font-size: 28px; font-weight: 700; color: #ff9d58;" x-text="metricas.meta_sugerida_85 || 'N/A'"></p>
        </div>

        <!-- Operario Cuello de Botella -->
        <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);">
            <p style="margin: 0 0 8px 0; font-size: 13px; color: #64748b; font-weight: 500;">Operario Cuello de Botella</p>
            <p style="margin: 0; font-size: 20px; font-weight: 700; color: #2d3748;" x-text="metricas.operario_cuello_botella || 'N/A'"></p>
        </div>

        <!-- Tiempo Cuello de Botella -->
        <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);">
            <p style="margin: 0 0 8px 0; font-size: 13px; color: #64748b; font-weight: 500;">Tiempo Cuello de Botella (s)</p>
            <p style="margin: 0; font-size: 28px; font-weight: 700; color: #2d3748;" x-text="metricas.tiempo_cuello_botella ? parseFloat(metricas.tiempo_cuello_botella).toFixed(1) : 'N/A'"></p>
        </div>
    </div>

    <!-- Nota informativa -->
    <div style="display: flex; gap: 12px; padding: 16px 20px; background: rgba(255, 157, 88, 0.1); border-radius: 12px; border-left: 4px solid #ff9d58;">
        <svg style="width: 20px; height: 20px; color: #ff9d58; flex-shrink: 0; margin-top: 2px;" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <circle cx="12" cy="12" r="10" stroke-width="2"/>
            <path d="M12 16v-4M12 8h.01" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <p style="margin: 0; color: #94a3b8; font-size: 13px; line-height: 1.6;">
            <strong style="color: #ff9d58;">Nota:</strong> Los campos editables actualizan automáticamente todas las métricas calculadas en tiempo real.
        </p>
    </div>
</div>
