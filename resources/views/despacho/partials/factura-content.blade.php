<div>
    <!-- Header factura -->
    <div style="background: #1e3a8a; color: white; padding: 16px; border-radius: 6px; margin-bottom: 12px; text-align: center;">
        <div style="font-size: 18px; font-weight: 700; margin-bottom: 8px;">FACTURA DE PEDIDO</div>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; font-size: 12px; margin-top: 12px;">
            <div>
                <div style="font-size: 10px; opacity: 0.8;">Número</div>
                <div style="font-weight: 600;">{{ $datos['pedido']['numero_pedido'] ?? 'N/A' }}</div>
            </div>
            <div>
                <div style="font-size: 10px; opacity: 0.8;">Cliente</div>
                <div style="font-weight: 600;">{{ $datos['pedido']['cliente'] ?? 'N/A' }}</div>
            </div>
            <div>
                <div style="font-size: 10px; opacity: 0.8;">Asesora</div>
                <div style="font-weight: 600;">{{ $datos['pedido']['asesor'] ?? 'N/A' }}</div>
            </div>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 12px; margin-top: 8px;">
            <div>
                <div style="font-size: 10px; opacity: 0.8;">Forma de Pago</div>
                <div style="font-weight: 600;">{{ $datos['pedido']['forma_pago'] ?? 'N/A' }}</div>
            </div>
            <div>
                <div style="font-size: 10px; opacity: 0.8;">Fecha</div>
                <div style="font-weight: 600;">{{ $datos['pedido']['fecha'] ?? date('d/m/Y') }}</div>
            </div>
        </div>
    </div>

    <!-- Prendas -->
    @if(isset($datos['prendas']) && !empty($datos['prendas']))
        @foreach($datos['prendas'] as $index => $prenda)
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 6px; margin-bottom: 16px; padding: 16px;">
                <!-- Header simple -->
                <div style="border-bottom: 1px solid #e5e7eb; padding-bottom: 8px; margin-bottom: 12px;">
                    <div style="font-size: 14px; font-weight: 600; color: #374151;">
                        PRENDA {{ $index + 1 }}: {{ $prenda['nombre_prenda'] ?? 'Sin nombre' }}
                        @if(isset($prenda['estado']) && $prenda['estado'] === 'SE SACA DE BODEGA')
                            <span style="color: #ea580c; font-weight: bold;">- SE SACA DE BODEGA</span>
                        @endif
                    </div>
                    <div style="font-size: 12px; color: #6b7280; margin-top: 2px;">
                        {{ $prenda['descripcion'] ?? 'Sin descripción' }}
                    </div>
                </div>
                
                <!-- Telas -->
                @if(isset($prenda['colores_telas']) && !empty($prenda['colores_telas']))
                    <div style="margin-bottom: 12px;">
                        @foreach($prenda['colores_telas'] as $colorTela)
                            <div style="padding: 6px 0; border-bottom: 1px solid #f3f4f6;">
                                <span style="font-size: 11px; color: #374151;">
                                    <strong>Tela:</strong> {{ $colorTela['tela_nombre'] ?? 'N/A' }}
                                    @if($colorTela['color_nombre'])
                                        <strong style="margin-left: 12px;">Color:</strong> {{ $colorTela['color_nombre'] }}
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
                
                <!-- Imagen pequeña -->
                @if(isset($prenda['imagenes']) && !empty($prenda['imagenes']))
                    <div style="float: right; margin-left: 12px; margin-bottom: 8px;">
                        @foreach($prenda['imagenes'] as $imagen)
                            <img src="{{ $imagen['ruta_webp'] }}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px; border: 1px solid #e5e7eb;">
                        @endforeach
                    </div>
                @endif
                
                <!-- Contenido compacto -->
                <div style="margin-right: 100px;">
                    <!-- Variantes -->
                    @if(isset($prenda['variantes']) && !empty($prenda['variantes']))
                        <table style="width: 100%; font-size: 11px; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                                    <th style="padding: 6px 8px; text-align: left; font-weight: 600; color: #374151;">Talla</th>
                                    <th style="padding: 6px 8px; text-align: center; font-weight: 600; color: #374151;">Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($prenda['variantes'] as $variante)
                                    <tr style="background: #ffffff; border-bottom: 1px solid #f3f4f6;">
                                        <td style="padding: 6px 8px; font-weight: 600; color: #374151;">{{ $variante['talla'] ?? 'N/A' }}</td>
                                        <td style="padding: 6px 8px; text-align: center; color: #6b7280;">{{ $variante['cantidad'] ?? 0 }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                    
                    <!-- Procesos -->
                    @if(isset($prenda['procesos']) && !empty($prenda['procesos']))
                        <div style="margin-bottom: 0;">
                            @foreach($prenda['procesos'] as $proceso)
                                <div style="padding: 8px 0; border-bottom: 1px solid #f3f4f6;">
                                    <div style="font-weight: 600; color: #374151; margin-bottom: 4px; font-size: 11px;">{{ $proceso['tipo_proceso'] ?? 'N/A' }}</div>
                                    
                                    @if(!empty($proceso['observaciones']))
                                        <div style="font-size: 10px; color: #6b7280; margin-bottom: 2px;">
                                             {{ $proceso['observaciones'] }}
                                        </div>
                                    @endif
                                    
                                    @if(isset($proceso['tallas']['caballero']) && !empty($proceso['tallas']['caballero']))
                                        @foreach($proceso['tallas']['caballero'] as $talla => $cantidad)
                                            <div style="font-size: 10px; color: #6b7280; margin-bottom: 2px;">
                                                {{ $talla }}({{ $cantidad }})
                                            </div>
                                        @endforeach
                                    @endif
                                    
                                    @if(!empty($proceso['ubicaciones']))
                                        <div style="font-size: 10px; color: #6b7280; margin-bottom: 2px;">
                                            Ubicaciones: {{ is_array($proceso['ubicaciones']) ? implode(', ', $proceso['ubicaciones']) : $proceso['ubicaciones'] }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                
                <div style="clear: both;"></div>
            </div>
        @endforeach
    @endif

    <!-- EPPs -->
    @if(isset($datos['epps']) && !empty($datos['epps']))
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 6px; margin-bottom: 16px; padding: 16px;">
            <div style="border-bottom: 1px solid #e5e7eb; padding-bottom: 8px; margin-bottom: 12px;">
                <div style="font-size: 14px; font-weight: 600; color: #374151;">EPPS</div>
            </div>
            @foreach($datos['epps'] as $epp)
                <div style="margin-bottom: 12px;">
                    <div style="font-weight: 600; color: #374151; margin-bottom: 4px;">{{ $epp['nombre'] ?? 'N/A' }}</div>
                    <div style="font-size: 12px; color: #6b7280;">
                        Cantidad: {{ $epp['cantidad'] ?? 0 }}
                        @if(!empty($epp['observaciones']))
                            <div style="font-size: 10px; color: #6b7280; margin-top: 4px;">
                                Observaciones: {{ $epp['observaciones'] }}
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Totales -->
    <div style="margin: 12px 0; padding: 12px; background: #f3f4f6; border-radius: 6px; border: 2px solid #d1d5db; text-align: right;">
        <div style="font-size: 12px; margin-bottom: 8px;">
            <strong>Total Ítems:</strong> {{ $datos['total_items'] ?? 0 }}
        </div>
    </div>
</div>
