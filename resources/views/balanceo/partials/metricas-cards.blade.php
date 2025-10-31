<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);">
        <p style="margin: 0; opacity: 0.9; font-size: 13px; font-weight: 500;">SAM Total</p>
        <h3 style="margin: 8px 0 0 0; font-size: 32px; font-weight: 700;" x-text="parseFloat(metricas.sam_total).toFixed(1) + 's'"></h3>
    </div>
    <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(240, 147, 251, 0.3);">
        <p style="margin: 0; opacity: 0.9; font-size: 13px; font-weight: 500;">Meta Teórica</p>
        <h3 style="margin: 8px 0 0 0; font-size: 32px; font-weight: 700;" x-text="metricas.meta_teorica || 'N/A'"></h3>
    </div>
    <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(79, 172, 254, 0.3);">
        <p style="margin: 0; opacity: 0.9; font-size: 13px; font-weight: 500;">Meta Real</p>
        <h3 style="margin: 8px 0 0 0; font-size: 32px; font-weight: 700;" x-text="metricas.meta_real || 'N/A'"></h3>
    </div>
    <div style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(67, 233, 123, 0.3);">
        <p style="margin: 0; opacity: 0.9; font-size: 13px; font-weight: 500;">Meta Sugerida (85%)</p>
        <h3 style="margin: 8px 0 0 0; font-size: 32px; font-weight: 700;" x-text="metricas.meta_sugerida_85 || 'N/A'"></h3>
    </div>
    <div style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(250, 112, 154, 0.3);">
        <p style="margin: 0; opacity: 0.9; font-size: 13px; font-weight: 500;">Tiempo Disponible</p>
        <h3 style="margin: 8px 0 0 0; font-size: 32px; font-weight: 700;" x-text="parseFloat(metricas.tiempo_disponible_horas || 0).toFixed(1) + 'h'"></h3>
    </div>
    <div style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #333; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(168, 237, 234, 0.3);">
        <p style="margin: 0; opacity: 0.8; font-size: 13px; font-weight: 500;">Cuello de Botella</p>
        <h3 style="margin: 8px 0 0 0; font-size: 24px; font-weight: 700;" x-text="metricas.operario_cuello_botella || 'N/A'"></h3>
        <p style="margin: 4px 0 0 0; font-size: 16px; font-weight: 600;" x-text="metricas.tiempo_cuello_botella ? parseFloat(metricas.tiempo_cuello_botella).toFixed(1) + 's' : ''"></p>
    </div>
</div>
