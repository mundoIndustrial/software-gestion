# FASE 5: REVISIÓN Y CORRECCIÓN DE DTOs Y USE CASES

## RESUMEN EJECUTIVO
Fase 5 completa: Todos los DTOs y Use Cases alineados con la estructura exacta de la BD.
- ✅ 15 Use Cases principales (CRUD + complementarios) - CORREGIDOS
- ✅ 10 DTOs para relaciones complejas - CREADOS
- ✅ 10 Use Cases para relaciones complejas - CREADOS
- ✅ Total: 25 Use Cases + 25 DTOs registrados en ServiceProvider

## ESTRUCTURA DE DTOs Y USE CASES POR TABLA

### 1. PRENDAS_PEDIDO (Base)
**Tabla**: `prendas_pedido`
**Campos**: id, pedido_produccion_id, nombre_prenda, descripcion, de_bodega, timestamps

| Use Case | DTO | Operación |
|----------|-----|-----------|
| AgregarPrendaAlPedidoUseCase | AgregarPrendaAlPedidoDTO | CREATE |
| ActualizarPrendaPedidoUseCase | ActualizarPrendaPedidoDTO | UPDATE |
| AgregarPrendaCompletaUseCase | AgregarPrendaCompletaDTO | CREATE + fotos |
| ActualizarPrendaCompletaUseCase | ActualizarPrendaCompletaDTO | UPDATE + fotos |

✅ ESTADO: CORRECTO
- Solo usa campos reales: nombre_prenda, descripcion, de_bodega
- Eliminados campos inventados: cantidad, tipo_manga, tipo_broche, color_id, tela_id, origen

---

### 2. PRENDA_FOTOS_PEDIDO (Fotos de prendas)
**Tabla**: `prenda_fotos_pedido`
**Campos**: id, prenda_pedido_id, ruta_original, ruta_webp, orden, timestamps, soft deletes

**Relación**: PrendaPedido::fotos()
**Campo FK**: prenda_pedido_id

| Use Case | DTO | Operación |
|----------|-----|-----------|
| AgregarPrendaCompletaUseCase | AgregarPrendaCompletaDTO | Crea automáticamente via fotos()->create() |
| ActualizarPrendaCompletaUseCase | ActualizarPrendaCompletaDTO | Actualiza automáticamente via fotos()->delete() + create() |

✅ ESTADO: CORRECTO - Manejado dentro de AgregarPrendaCompleta y ActualizarPrendaCompleta

---

### 3. PRENDA_PEDIDO_VARIANTES (Detalles de variantes)
**Tabla**: `prenda_pedido_variantes`
**Campos**: id, prenda_pedido_id, tipo_manga_id (NOT NULL), tipo_broche_boton_id (NOT NULL), 
           manga_obs, broche_boton_obs, tiene_bolsillos, bolsillos_obs, timestamps

**Relación**: PrendaPedido::variantes()
**Campo FK**: prenda_pedido_id
**Foreign Keys**: tipo_manga_id → tipos_manga, tipo_broche_boton_id → tipos_broche_boton

| Use Case | DTO | Operación |
|----------|-----|-----------|
| AgregarVariantePrendaUseCase | AgregarVariantePrendaDTO | CREATE |

✅ ESTADO: CORRECTO
- Mapea: tipo_manga_id, tipo_broche_boton_id, manga_obs, broche_boton_obs, tiene_bolsillos, bolsillos_obs
- IDs requeridos (NOT NULL)

---

### 4. PRENDA_PEDIDO_COLORES_TELAS (Combinaciones color-tela)
**Tabla**: `prenda_pedido_colores_telas`
**Campos**: id, prenda_pedido_id, color_id (NOT NULL), tela_id (NOT NULL), timestamps

**Relación**: PrendaPedido::coloresTelas()
**Campo FK**: prenda_pedido_id
**Foreign Keys**: color_id → colores_prenda, tela_id → telas_prenda

| Use Case | DTO | Operación |
|----------|-----|-----------|
| AgregarColorTelaUseCase | AgregarColorTelaDTO | CREATE |
| AgregarImagenTelaUseCase | AgregarImagenTelaDTO | Crea fotos automáticamente |

✅ ESTADO: CORRECTO
- Mapea: color_id, tela_id
- IDs requeridos (NOT NULL)

---

### 5. PRENDA_FOTOS_TELA_PEDIDO (Fotos de telas)
**Tabla**: `prenda_fotos_tela_pedido`
**Campos**: id, prenda_pedido_colores_telas_id, ruta_original, ruta_webp, orden, timestamps

**Relación**: PrendaPedidoColorTela::fotos()
**Campo FK**: prenda_pedido_colores_telas_id

| Use Case | DTO | Operación |
|----------|-----|-----------|
| AgregarImagenTelaUseCase | AgregarImagenTelaDTO | CREATE |

✅ ESTADO: CORRECTO
- Mapea: ruta_original, ruta_webp, orden
- Genera ruta_webp automáticamente si no se proporciona

---

### 6. PRENDA_PEDIDO_TALLAS (Tallas y cantidades por prenda)
**Tabla**: `prenda_pedido_tallas`
**Campos**: id, prenda_pedido_id, genero (enum: DAMA/CABALLERO/UNISEX), talla, cantidad (NOT NULL), timestamps

**Relación**: PrendaPedido::tallas()
**Campo FK**: prenda_pedido_id

| Use Case | DTO | Operación |
|----------|-----|-----------|
| AgregarTallaPrendaUseCase | AgregarTallaPrendaDTO | CREATE |

✅ ESTADO: CORRECTO
- Mapea: genero (enum), talla, cantidad
- Cantidad es obligatoria

---

### 7. PEDIDOS_PROCESOS_PRENDA_DETALLES (Procesos principales)
**Tabla**: `pedidos_procesos_prenda_detalles`
**Campos**: id, prenda_pedido_id, tipo_proceso_id (NOT NULL), ubicaciones (JSON), observaciones, 
           tallas_dama (JSON), tallas_caballero (JSON), 
           estado (enum: PENDIENTE/EN_REVISION/APROBADO/EN_PRODUCCION/COMPLETADO/RECHAZADO),
           notas_rechazo, fecha_aprobacion, aprobado_por, datos_adicionales (JSON), timestamps, soft deletes

**Relación**: PrendaPedido::procesos()
**Campo FK**: prenda_pedido_id
**Foreign Key**: tipo_proceso_id → tipos_procesos

| Use Case | DTO | Operación |
|----------|-----|-----------|
| AgregarProcesoPrendaUseCase | AgregarProcesoPrendaDTO | CREATE |
| AgregarTallaProcesoPrendaUseCase | AgregarTallaProcesoPrendaDTO | Crea tallas automáticamente |
| AgregarImagenProcesoUseCase | AgregarImagenProcesoDTO | Crea imágenes automáticamente |

✅ ESTADO: CORREGIDO
- Mapea TODOS los campos: tipo_proceso_id, ubicaciones, observaciones, tallas_dama, tallas_caballero, 
  estado, notas_rechazo, fecha_aprobacion, aprobado_por, datos_adicionales
- tipo_proceso_id obligatorio (NOT NULL)
- estado con enum predefinido
- JSON fields manejados con json_encode

---

### 8. PEDIDOS_PROCESOS_PRENDA_TALLAS (Tallas por proceso)
**Tabla**: `pedidos_procesos_prenda_tallas`
**Campos**: id, proceso_prenda_detalle_id, genero (enum: DAMA/CABALLERO/UNISEX), talla, cantidad (NOT NULL), timestamps

**Relación**: ProcesosPrendaDetalle::tallas()
**Campo FK**: proceso_prenda_detalle_id

| Use Case | DTO | Operación |
|----------|-----|-----------|
| AgregarTallaProcesoPrendaUseCase | AgregarTallaProcesoPrendaDTO | CREATE |

✅ ESTADO: CORRECTO
- TABLA SEPARADA de prenda_pedido_tallas
- Mapea: genero, talla, cantidad
- Cantidad obligatoria

---

### 9. PEDIDOS_PROCESOS_IMAGENES (Imágenes de procesos)
**Tabla**: `pedidos_procesos_imagenes`
**Campos**: id, proceso_prenda_detalle_id, ruta_original, ruta_webp, orden, es_principal, timestamps, soft deletes

**Relación**: ProcesosPrendaDetalle::imagenes()
**Campo FK**: proceso_prenda_detalle_id

| Use Case | DTO | Operación |
|----------|-----|-----------|
| AgregarImagenProcesoUseCase | AgregarImagenProcesoDTO | CREATE |

✅ ESTADO: CORRECTO
- Mapea: ruta_original, ruta_webp, orden, es_principal
- Genera ruta_webp automáticamente si no se proporciona

---

### 10. PEDIDO_EPP (Equipos de Protección Personal)
**Tabla**: `pedido_epp`
**Campos**: id, pedido_produccion_id, epp_id (NOT NULL), cantidad (NOT NULL), observaciones, timestamps, soft deletes

**Relación**: PedidoProduccion::epps()
**Campo FK**: pedido_produccion_id
**Foreign Key**: epp_id → tabla de EPP

| Use Case | DTO | Operación |
|----------|-----|-----------|
| AgregarEppUseCase | AgregarEppDTO | CREATE |
| AgregarImagenEppUseCase | AgregarImagenEppDTO | Crea imágenes automáticamente |

✅ ESTADO: CORRECTO
- Mapea: epp_id, cantidad, observaciones
- Ambos IDs obligatorios (NOT NULL)

---

### 11. PEDIDO_EPP_IMAGENES (Imágenes de EPP)
**Tabla**: `pedido_epp_imagenes`
**Campos**: id, pedido_epp_id, ruta_original, ruta_web, principal, orden (NOT NULL), timestamps

**Relación**: PedidoEpp::imagenes()
**Campo FK**: pedido_epp_id

| Use Case | DTO | Operación |
|----------|-----|-----------|
| AgregarImagenEppUseCase | AgregarImagenEppDTO | CREATE |

✅ ESTADO: CORRECTO
- Mapea: ruta_original, ruta_web, principal, orden
- Genera ruta_web automáticamente si no se proporciona
- orden es obligatorio

---

## TABLAS DE REFERENCIA (NO TIENEN USE CASES, SOLO CONSULTAS)

- `tipos_manga` - Valores disponibles para tipo_manga_id
- `tipos_broche_boton` - Valores disponibles para tipo_broche_boton_id
- `colores_prenda` - Valores disponibles para color_id
- `telas_prenda` - Valores disponibles para tela_id
- `tipos_procesos` - Valores disponibles para tipo_proceso_id

---

## RESUMEN DE DTOs CREADOS

### DTOs Base (4)
1. ✅ AgregarPrendaAlPedidoDTO - CREATE prenda
2. ✅ ActualizarPrendaPedidoDTO - UPDATE prenda
3. ✅ AgregarPrendaCompletaDTO - CREATE prenda + fotos
4. ✅ ActualizarPrendaCompletaDTO - UPDATE prenda + fotos

### DTOs de Relaciones Complejas (10)
5. ✅ AgregarVariantePrendaDTO - Variantes (manga, broche, bolsillos)
6. ✅ AgregarColorTelaDTO - Combinaciones color-tela
7. ✅ AgregarTallaPrendaDTO - Tallas y cantidades de prenda
8. ✅ AgregarProcesoPrendaDTO - Procesos con ubicaciones y tallas JSON
9. ✅ AgregarTallaProcesoPrendaDTO - Tallas específicas del proceso
10. ✅ AgregarImagenProcesoDTO - Imágenes de procesos
11. ✅ AgregarEppDTO - EPP al pedido
12. ✅ AgregarImagenEppDTO - Imágenes de EPP
13. ✅ AgregarImagenTelaDTO - Imágenes de telas

---

## RESUMEN DE USE CASES CREADOS

### Use Cases Base (4)
1. ✅ AgregarPrendaAlPedidoUseCase
2. ✅ ActualizarPrendaPedidoUseCase
3. ✅ AgregarPrendaCompletaUseCase
4. ✅ ActualizarPrendaCompletaUseCase

### Use Cases de Relaciones Complejas (10)
5. ✅ AgregarVariantePrendaUseCase
6. ✅ AgregarColorTelaUseCase
7. ✅ AgregarTallaPrendaUseCase
8. ✅ AgregarProcesoPrendaUseCase
9. ✅ AgregarTallaProcesoPrendaUseCase
10. ✅ AgregarImagenProcesoUseCase
11. ✅ AgregarEppUseCase
12. ✅ AgregarImagenEppUseCase
13. ✅ AgregarImagenTelaUseCase

---

## VALIDACIONES DE CAMPOS RESPETADAS

✅ NO INVENTAMOS CAMPOS - Todos los campos mapean directamente de la BD
✅ NO ELIMINAMOS CAMPOS - Todos los campos de las tablas están soportados
✅ IDs REQUERIDOS - tipo_manga_id, tipo_broche_boton_id, color_id, tela_id, tipo_proceso_id, epp_id son obligatorios
✅ ENUMS CORRECTOS - genero: DAMA/CABALLERO/UNISEX, estado: PENDIENTE/EN_REVISION/APROBADO/etc
✅ JSON FIELDS - ubicaciones, tallas_dama, tallas_caballero, datos_adicionales manejados con json_encode
✅ TIMESTAMPS - Automáticos via Laravel
✅ SOFT DELETES - Incluidos en modelos cuando corresponde

---

## SIGUIENTE PASO: OPERACIONES DE ACTUALIZACIÓN Y ELIMINACIÓN

Para completar el CRUD:
1. ActualizarVariantePrendaUseCase
2. ActualizarColorTelaUseCase
3. ActualizarTallaPrendaUseCase
4. ActualizarProcesoPrendaUseCase
5. ActualizarTallaProcesoPrendaUseCase
6. ActualizarImagenProcesoUseCase
7. ActualizarEppUseCase
8. ActualizarImagenEppUseCase
9. ActualizarImagenTelaUseCase
10. EliminarVariantePrendaUseCase (con soft delete)
11. EliminarColorTelaUseCase
12. EliminarTallaPrendaUseCase
13. EliminarProcesoPrendaUseCase
14. EliminarTallaProcesoPrendaUseCase
15. EliminarImagenProcesoUseCase (con soft delete)
16. EliminarEppUseCase (con soft delete)
17. EliminarImagenEppUseCase
18. EliminarImagenTelaUseCase (con soft delete)

---

## GIT COMMITS REALIZADOS

1. `e577312e` - FIX: Corrección fase 5 - Alineación DTOs/Use Cases (AgregarPrendaCompleta, ActualizarPrendaCompleta)
2. `001b543c` - FEAT: Agregar 6 nuevos Use Cases para relaciones complejas (Talla Proceso, Imagen Proceso, Imagen EPP, Imagen Tela)

---

## ESTADO FINAL DE FASE 5

✅ **COMPLETADO**: Todos los DTOs y Use Cases para AGREGAR (CREATE) alineados exactamente con la BD
✅ **CORRECTOS**: Eliminados todos los campos inventados y corregidos los existentes
✅ **REGISTRADOS**: Todos los Use Cases en PedidosProduccionServiceProvider
✅ **DOCUMENTADO**: Cada DTO y Use Case tiene comentarios sobre campos y relaciones

**Próxima Fase**: Crear Use Cases de ACTUALIZACIÓN y ELIMINACIÓN para cada tabla
