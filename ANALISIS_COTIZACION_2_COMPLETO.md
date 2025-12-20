# 🔍 ANÁLISIS COMPLETO: Cotización 2 - Telas no guardan

## ✅ Lo que sí se guardó correctamente

1. **Cotización creada**: La cotización 2 se creó exitosamente
2. **Prendas guardadas**: Se guardaron 2 prendas
   - Camisa drill (con variantes)
   - Pantalón drill (con variantes)
3. **Datos de telas (texto)**: Se guardaron los datos principales de telas:
   - Color, tipo de tela, referencia
4. **Fotos de prenda**: Se guardaron las fotos principales de cada prenda
5. **Fotos de logo**: Se guardaron las 5 imágenes de logo

## ❌ Lo que NO se guardó

Las **TELAS ADICIONALES (2 y 3 para la camisa)** no aparecen al editar la cotización.

Según el log que compartiste:
- Se enviaron 3 telas para camisa: Naranja/drill, VERDE/OXFORD, AZUL/DRILL BORNEO
- Se enviaron sus fotos correspondientes
- Pero solo la primera tela (Naranja/drill) aparece cuando editas

## 🔍 Investigación del Problema

### Estado Actual del Código

**guardado.js línea 805** (ya corregido):
```javascript
formData.append(`productos_friendly[${index}][telas][${telaIdx}][fotos][]`, foto);
```

✅ **Correcto**: Usa el prefijo `productos_friendly` que el servidor espera.

### Flujo esperado vs real

```
ENVIADO POR CLIENTE:
├─ Metadata de telas: prendas[0][variantes][telas_multiples] = [...]
└─ Fotos de telas: productos_friendly[0][telas][0|1|2][fotos][] = [File, File]

ESPERADO POR SERVIDOR:
├─ Busca telas en: request()->input('prendas.X.variantes.telas_multiples')
└─ Busca fotos en: request()->allFiles('productos_friendly.X.telas.Y.fotos')
```

## 🎯 Posibles Causas del Problema

### Causa 1: Las telas se guardan pero SIN los datos de color/tela/referencia
```
├─ Tela 1 se guarda: color="Naranja", tela="drill", referencia="ref-2020"
├─ Tela 2 NO se guarda:  color, tela, referencia NULOS
└─ Tela 3 NO se guarda:  color, tela, referencia NULOS
```

**Por qué pasaría esto**: El servidor recibe `telas_multiples` como JSON/array y trata de guardar cada una, pero algo falla con las telas 2 y 3.

### Causa 2: Las fotos de telas NO se guardan
```
Las fotos se envían bajo: productos_friendly[0][telas][0|1|2][fotos][]
Pero el servidor trata de procesarlas y algo falla
```

## 🔧 Para Diagnosticar el Problema Real

Necesitas ejecutar estas queries en la BD:

```sql
-- 1. Ver cuántas telas se guardaron
SELECT COUNT(*) FROM prenda_telas 
WHERE prenda_pedido_id IN (
    SELECT id FROM prenda_pedido WHERE cotizacion_id = 2
);

-- 2. Ver qué telas se guardaron
SELECT id, prenda_pedido_id, color_id, tela_id, referencia
FROM prenda_telas pt
JOIN prenda_pedido pp ON pt.prenda_pedido_id = pp.id
WHERE pp.cotizacion_id = 2;

-- 3. Ver si hay fotos de telas
SELECT COUNT(*) FROM prenda_fotos_tela_pedido
WHERE prenda_pedido_id IN (
    SELECT id FROM prenda_pedido WHERE cotizacion_id = 2
);
```

## 📋 Próximos Pasos

1. **Ejecutar las queries** para saber exactamente qué se guardó en BD
2. **Revisar logs del servidor** (`storage/logs/laravel.log`) para ver errores de guardado
3. **Verificar la función `guardarTelas`** en `PedidoPrendaService`
4. **Probar nuevamente** y compartir resultados

## 🚀 Hipótesis Más Probable

Las telas DE TEXTO se guardaron, pero:
- **Telas 2 y 3 faltantes**: No llegaron al servidor o hubo error al guardarlas
- **Fotos de telas**: Podrían estar bien pero el formulario de edición no las muestra

El fix de guardado.js (cambiar a `productos_friendly`) ya se aplicó, así que en la próxima cotización debería funcionar mejor.

