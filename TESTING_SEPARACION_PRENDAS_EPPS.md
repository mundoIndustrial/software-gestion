# Testing & Verificación - Separación Prendas/EPPs

**Fecha**: 26 Enero 2026  
**Estado**: Plan de Testing  

---

## 🧪 Pruebas Manual del Sistema

### Paso 1: Verificar Estructura Frontend

**Objetivo**: Verificar que ItemFormCollector separa correctamente prendas y epps

**Steps**:
1. Abrir navegador en: `/asesores/pedidos-editable/crear`
2. Abrir DevTools (F12) → Console
3. Agregar una PRENDA
   ```javascript
   // En console
   const GestionItemsUI = window.GestionItemsUI;
   console.log('Prendas:', GestionItemsUI.prendas);
   ```
4. Agregar un EPP
   ```javascript
   // En console  
   console.log('EPPs:', window.itemsPedido);
   ```
5. Click "Guardar Pedido" → Verificar logs:
   ```
   [ItemFormCollector] Prendas recolectadas: 1
   [ItemFormCollector] EPPs filtrados: 1
   [ItemFormCollector] Return: {prendas, epps}
   ```

**Resultado Esperado**:
```
✅ Prendas array con estructura de prenda
✅ EPPs array separado con estructura simple
✅ Ambos arrays presentes en payload
```

---

### Paso 2: Verificar Normalización

**Objetivo**: Verificar que PayloadNormalizer maneja ambas estructuras

**Steps**:
1. En console, capturar payload:
   ```javascript
   // Modificar ItemFormCollector para que log el payload antes de normalizar
   const payload = ItemFormCollector.recolectarDatos();
   console.log('Payload PRE-normalizador:', payload);
   ```
2. Verificar que PayloadNormalizer detecta estructura:
   ```
   [PayloadNormalizer] Detectada estructura NUEVA (prendas/epps)
   [PayloadNormalizer] Prendas normalizadas: 1
   [PayloadNormalizer] EPPs normalizados: 1
   ```

**Resultado Esperado**:
```
✅ Detecta estructura nueva correctamente
✅ Normaliza prendas (cantidad_talla: string → numbers)
✅ Preserva EPPs sin cambios
✅ Log muestra conteos correctos
```

---

### Paso 3: Verificar Envío a Backend

**Objetivo**: Verificar que FormData se construye correctamente

**Steps**:
1. En ItemAPIService.js, agregar logging:
   ```javascript
   // Antes de .post()
   console.log('FormData keys:', Array.from(formData.keys()));
   ```
2. Enviar pedido y verificar en Network tab (DevTools):
   - Request: POST /asesores/pedidos-editable/crear
   - Headers: Accept: application/json
   - Content-Type: multipart/form-data
   - Body contiene:
     - `pedido` (JSON string)
     - `prendas[0][imagenes][0]` (si hay)
     - `epps[0][imagenes][0]` (si hay)

**Resultado Esperado**:
```
✅ FormData bien construido
✅ Headers correctos
✅ Archivos en rutas correctas
```

---

### Paso 4: Verificar Validación Backend (FormRequest)

**Objetivo**: Verificar que CrearPedidoCompletoRequest valida estructura correctamente

**Escenario A: Estructura Válida**
- Payload con prendas válidas + EPPs válidos
- **Resultado Esperado**: HTTP 200, pedido creado

**Escenario B: epp_id Inválido**
- Enviar EPP con epp_id que no existe en tabla `epps`
- **Resultado Esperado**: HTTP 422, error JSON:
  ```json
  {
    "success": false,
    "errors": {
      "epps.0.epp_id": ["The selected epps.0.epp_id is invalid."]
    }
  }
  ```

**Escenario C: Falta epp_id**
- Enviar EPP sin epp_id
- **Resultado Esperado**: HTTP 422, error JSON:
  ```json
  {
    "success": false,
    "errors": {
      "epps.0.epp_id": ["The epps.0.epp_id field is required."]
    }
  }
  ```

**Escenario D: Cantidad EPP Inválida**
- Enviar EPP con cantidad < 1
- **Resultado Esperado**: HTTP 422, error JSON

**Escenario E: Sin Prendas ni EPPs**
- Payload sin ambos arrays vacíos
- **Resultado Esperado**: Debería aceptarse (al menos uno debe existir)
- **Nota**: Posible agregar validación `at_least_one_of:[prendas, epps]`

---

### Paso 5: Verificar Creación en Base de Datos

**Objetivo**: Verificar que se crean registros correctos en BD

**Test con Prendas + EPPs Completos**:

```sql
-- Después de crear pedido, verificar:

-- 1. Pedido existe
SELECT * FROM pedidos_produccion WHERE id = 123;
-- Resultado esperado: 1 fila con numero_pedido, cliente_id, etc.

-- 2. Prendas creadas
SELECT * FROM prendas_pedido WHERE pedido_id = 123;
-- Resultado esperado: 1 fila por prenda agregada

-- 3. EPPs creados
SELECT * FROM pedido_epp WHERE pedido_id = 123;
-- Resultado esperado: 1 fila por EPP
-- Campos: pedido_id=123, epp_id=42, nombre_epp='...',cantidad=50, observaciones='...'

-- 4. Imágenes de EPP
SELECT pe.*, pei.* 
FROM pedido_epp pe
LEFT JOIN pedido_epp_imagenes pei ON pei.pedido_epp_id = pe.id
WHERE pe.pedido_id = 123;
-- Resultado esperado: 1+ filas por imagen, ruta_webp poblado

-- 5. Verificar archivo WebP existe
-- En servidor:
ls -la storage/app/public/pedidos/123/epps/
-- Resultado esperado: epp_42_img_0.webp, epp_42_img_1.webp, etc.
```

**Verificación Manual**:
```bash
# Conectar a BD
mysql -u user -p database

# Query
USE mundoindustrial;
SELECT * FROM pedido_epp WHERE pedido_id = 123 \G

# Output esperado:
# *************************** 1. row ***************************
#                 id: 1
#          pedido_id: 123
#            epp_id: 42
#        nombre_epp: Casco de Seguridad
#          cantidad: 50
#   observaciones: Color azul marino
#       created_at: 2026-01-26 14:30:00
#       updated_at: 2026-01-26 14:30:00
```

---

### Paso 6: Verificar Logs del Controlador

**Objetivo**: Verificar que creador pedido sigue flujo correcto

**En storage/logs/laravel.log**, buscar logs del pedido:

```
[2026-01-26 14:30:00] local.INFO: [CrearPedidoEditableController] Iniciando creación transaccional
  has_pedido_json: true
  archivos_count: 3

[2026-01-26 14:30:01] local.INFO: [CrearPedidoEditableController] Estructura detectada
  nueva: SÍ (prendas/epps)
  antigua: NO

[2026-01-26 14:30:01] local.INFO: [CrearPedidoEditableController] Pedido creado en transacción
  pedido_id: 123
  numero_pedido: PED-2026-00123
  prendas_count: 1

[2026-01-26 14:30:02] local.INFO: [CrearPedidoEditableController] Imágenes de prendas procesadas
  pedido_id: 123

[2026-01-26 14:30:02] local.INFO: [CrearPedidoEditableController] 📦 Procesando EPPs
  pedido_id: 123
  epps_count: 1

[2026-01-26 14:30:02] local.INFO: [CrearPedidoEditableController] EPP creado
  pedido_epp_id: 45
  epp_id: 42
  cantidad: 50

[2026-01-26 14:30:03] local.DEBUG: [CrearPedidoEditableController] 📸 Imagen EPP guardada (WebP)
  pedido_epp_id: 45
  webp: pedidos/123/epps/epp_42_img_0.webp
  orden: 1

[2026-01-26 14:30:03] local.INFO: [CrearPedidoEditableController] TRANSACCIÓN EXITOSA
  pedido_id: 123
  numero_pedido: PED-2026-00123
```

---

### Paso 7: Verificar Backward Compatibility

**Objetivo**: Verificar que estructura antigua (`items[]`) aún funciona

**Steps**:
1. Modificar PayloadNormalizer para enviar estructura antigua
2. Payload con `items[]` en lugar de `prendas[]`
3. Enviar al backend

**Resultado Esperado**:
```
✅ Backend detecta estructura antigua
✅ No procesa epps
✅ Procesa solo prendas
✅ Pedido creado correctamente
```

**Verificación en logs**:
```
[CrearPedidoEditableController] Estructura detectada
  nueva: NO
  antigua: SÍ (items)
```

---

##  Troubleshooting

### Problema: "Unexpected token '<'"

**Causa**: Backend retorna HTML en lugar de JSON

**Verificación**:
- [ ] Check `Accept: application/json` header en request
- [ ] Check `failedValidation()` en FormRequest retorna JSON
- [ ] Check logs de error en backend

**Solución**:
1. Verificar headers en ItemAPIService.js
2. Verificar que FormRequest tiene método `failedValidation()`
3. Revisar logs: `tail -f storage/logs/laravel.log`

---

### Problema: "The selected epps.0.epp_id is invalid"

**Causa**: epp_id no existe en tabla `epps`

**Verificación**:
```sql
SELECT COUNT(*) FROM epps WHERE id = 42;
-- Resultado esperado: 1
```

**Solución**:
- Verificar ID correcto en payload
- Verificar que tabla `epps` tiene registros
- Agregr nuevo EPP si es necesario

---

### Problema: "Imagen no se convierte a WebP"

**Causa**: ImageUploadService no convierte

**Verificación**:
- [ ] Check that ImageMagick is installed: `which convert`
- [ ] Check storage/logs for ImageUploadService errors
- [ ] Verify image upload path permissions

**Solución**:
1. Instalar ImageMagick: `apt-get install imagemagick`
2. Check PHP config: `php -m | grep -i imagick`
3. Revisar logs del servicio

---

### Problema: "File not found" en pedidos/epps/

**Causa**: Ruta incorrecta en request

**Verificación**:
- [ ] FormData key es `epps[i][imagenes][j]`
- [ ] Not `epp[i][imagenes][j]`
- [ ] Not `epps[i][images][j]`

**Solución**:
```javascript
// CORRECTO
epps[0][imagenes][0] = file

//  INCORRECTO
epp[0][imagenes][0] = file
epps[0][images][0] = file
```

---

## 📊 Matriz de Testing

| Escenario | Frontend | Backend | BD | Archivos | Estado |
|-----------|----------|---------|----|---------| -------|
| Prendas solas | | | | | Existente |
| EPPs solos | | | | | Nuevo |
| Prendas + EPPs | | | | | Nuevo |
| Items (antigua) | N/A | | | | Compatible |
| Sin imagenes EPP | | | | | Permitido |
| epp_id inválido | | | | N/A | Error esperado |
| Imagen corrupta | | | Rollback | Limpiado | Error esperado |

---

## Checklist Final

- [ ] Frontend ItemFormCollector separa correctamente
- [ ] Frontend PayloadNormalizer maneja ambas estructuras
- [ ] Backend FormRequest valida correctamente
- [ ] Backend detecta estructura automáticamente
- [ ] Backend procesa prendas (existente)
- [ ] Backend procesa EPPs (nuevo)
- [ ] EPPs se guardan en BD correctamente
- [ ] Imágenes se convierten a WebP
- [ ] Archivos se guardan en estructura correcta
- [ ] Transacción hace rollback en error
- [ ] Logs son informativos
- [ ] Backward compatibility funciona
- [ ] Tests de casos de error pasan

---

**Próximo**: Ejecutar tests manuales y reportar resultados
