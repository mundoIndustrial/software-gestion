-- Vista para mostrar entregas completas por pedido
-- Combina datos de entrega de supervisor a despacho y de despacho a asesor

CREATE OR REPLACE VIEW vista_entregas_completas AS
SELECT 
    p.id as pedido_id,
    p.numero_pedido,
    p.numero_cotizacion,
    p.cliente,
    p.estado as estado_pedido,
    p.fecha_de_creacion_de_orden,
    p.fecha_estimada_de_entrega,
    
    -- Datos de entrega de supervisor a despacho (desde prenda_entregas)
    MAX(CASE WHEN pe.entregado = 1 THEN pe.fecha_entrega END) as fecha_entrega_supervisor,
    MAX(CASE WHEN pe.entregado = 1 THEN u_supervisor.name END) as nombre_supervisor_entrega,
    MAX(CASE WHEN pe.entregado = 1 THEN u_supervisor.email END) as email_supervisor_entrega,
    
    -- Datos de entrega de despacho a asesor (desde despacho_parciales)
    MAX(CASE WHEN dp.entregado = 1 THEN dp.fecha_entrega END) as fecha_entrega_despacho,
    MAX(CASE WHEN dp.entregado = 1 THEN u_despacho.name END) as nombre_despacho_entrega,
    MAX(CASE WHEN dp.entregado = 1 THEN u_despacho.email END) as email_despacho_entrega,
    
    -- Contadores
    COUNT(DISTINCT CASE WHEN pe.entregado = 1 THEN pe.id END) as total_prendas_entregadas_supervisor,
    COUNT(DISTINCT CASE WHEN dp.entregado = 1 THEN dp.id END) as total_parciales_entregados_despacho,
    
    -- Estado de entregas
    CASE 
        WHEN MAX(CASE WHEN pe.entregado = 1 THEN 1 ELSE 0 END) = 1 AND 
             MAX(CASE WHEN dp.entregado = 1 THEN 1 ELSE 0 END) = 1 
        THEN 'Completado'
        WHEN MAX(CASE WHEN pe.entregado = 1 THEN 1 ELSE 0 END) = 1 
        THEN 'Pendiente Despacho'
        WHEN MAX(CASE WHEN dp.entregado = 1 THEN 1 ELSE 0 END) = 1 
        THEN 'Pendiente Supervisor'
        ELSE 'Pendiente Ambos'
    END as estado_entrega_general,
    
    -- Tiempos
    CASE 
        WHEN MAX(CASE WHEN pe.entregado = 1 THEN pe.fecha_entrega END) IS NOT NULL AND 
             MAX(CASE WHEN dp.entregado = 1 THEN dp.fecha_entrega END) IS NOT NULL
        THEN TIMESTAMPDIFF(
            HOUR, 
            MAX(CASE WHEN pe.entregado = 1 THEN pe.fecha_entrega END),
            MAX(CASE WHEN dp.entregado = 1 THEN dp.fecha_entrega END)
        )
        ELSE NULL
    END as horas_entre_entregas,
    
    -- Fecha última actualización
    GREATEST(
        COALESCE(MAX(CASE WHEN pe.entregado = 1 THEN pe.updated_at END), p.updated_at),
        COALESCE(MAX(CASE WHEN dp.entregado = 1 THEN dp.updated_at END), p.updated_at)
    ) as ultima_actualizacion

FROM pedidos_produccion p
LEFT JOIN prenda_entregas pe ON p.id = pe.prenda_pedido_id
LEFT JOIN despacho_parciales dp ON p.id = dp.pedido_id
LEFT JOIN users u_supervisor ON pe.usuario_id = u_supervisor.id
LEFT JOIN users u_despacho ON dp.usuario_id = u_despacho.id
GROUP BY p.id, p.numero_pedido, p.numero_cotizacion, p.cliente, p.estado, 
         p.fecha_de_creacion_de_orden, p.fecha_estimada_de_entrega
ORDER BY p.fecha_de_creacion_de_orden DESC;

-- Consulta de ejemplo para probar la vista
-- SELECT * FROM vista_entregas_completas WHERE estado_entrega_general != 'Pendiente Ambos' LIMIT 10;
