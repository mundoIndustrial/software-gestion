# 🔍 Scripts de Debugging - Análisis de Datos de Prendas

Este directorio contiene scripts para analizar y debuggear por qué no se está guardando toda la información en la tabla `prenda_pedido_variantes`.

## 📋 Scripts Disponibles

### 1. `analizar_datos_prendas.php`
**Propósito:** Análisis detallado de qué datos se han guardado en la BD

```bash
php analizar_datos_prendas.php [numero_pedido]
```

**Ejemplo:**
```bash
php analizar_datos_prendas.php 50001
```

**Qué hace:**
- ✅ Muestra todas las prendas del pedido
- ✅ Muestra todas las variantes de cada prenda
- ✅ Valida cada campo (talla, cantidad, IDs, observaciones)
- ✅ Genera estadísticas de campos vacíos
- ✅ Muestra query SQL para inspección directa

**Output esperado:**
```
┌─ PRENDA #1
│ Nombre: Chaleco
│ Variantes: 2
│ ├─ Variante #1
│ │ • Talla: M ✅
│ │ • Cantidad: 50 ✅
│ │ • Color ID: 1 ✅
│ │ • Tela ID: 2 ✅
│ │ • Tiene Bolsillos: true ✅
└─
```

---

### 2. `debug_flujo_prendas.php`
**Propósito:** Debug completo del flujo desde entrada hasta salida

```bash
php debug_flujo_prendas.php [numero_pedido]
```

**Ejemplo:**
```bash
php debug_flujo_prendas.php 50001
```

**Qué hace:**
- ✅ Muestra estructura completa de prendas y variantes
- ✅ Detecta campos faltantes o vacíos
- ✅ Lista todos los problemas encontrados
- ✅ Genera JSON con toda la información para analizar
- ✅ Proporciona recomendaciones siguientes

**Output esperado:**
```
1️⃣  PRENDAS
   Total: 3
   ├─ PRENDA #1 (ID: 1)
   │  └─ 2️⃣  VARIANTES
   │     • Total: 2
   │     Variante #1
   │     ├─ Datos Básicos
   │     │  • Talla: M ✅
   │     │  • Cantidad: 50 ✅
   │     ├─ IDs de Relaciones
   │     │  • color_id: 1 ✅
   │     │  • tela_id: 2 ✅
   │     │  • tipo_manga_id: 3 ✅
   │     │  • tipo_broche_boton_id: 1 ✅
```

---

### 3. `monitorear_requests_frontend.php`
**Propósito:** Validar estructura de datos esperados del frontend

```bash
php monitorear_requests_frontend.php [minutos]
```

**Ejemplo:**
```bash
php monitorear_requests_frontend.php 10
```

**Qué hace:**
- ✅ Muestra estructura esperada de datos
- ✅ Genera checklist de validación
- ✅ Proporciona comandos útiles para debugging
- ✅ Documenta puntos clave del código

---

## 🚀 Flujo de Debugging Recomendado

### Paso 1: Verificar que los datos se guardaron
```bash
php analizar_datos_prendas.php 50001
```

Si ves campos vacíos → **Vé al Paso 2**

Si todos los campos están llenos → **El problema está en otra parte**

---

### Paso 2: Debug completo del flujo
```bash
php debug_flujo_prendas.php 50001
```

Esto te dará:
- Lista exacta de qué campos faltan
- JSON con toda la información
- Recomendaciones de dónde revisar

---

### Paso 3: Validar estructura esperada
```bash
php monitorear_requests_frontend.php 10
```

Asegúrate de que el frontend está enviando:
- `nombre_prenda`
- `descripcion`
- `genero`
- Array de `variantes` con todos los campos

---

## 🔧 Comandos Útiles Adicionales

### Ver últimos logs
```bash
tail -100 storage/logs/laravel.log | grep -i 'prenda'
```

### Buscar errores específicos
```bash
grep -i 'error\|exception' storage/logs/laravel.log | tail -20
```

### Monitorear logs en tiempo real
```bash
tail -f storage/logs/laravel.log
```

### Consultar directamente la BD
```sql
SELECT * FROM prenda_pedido_variantes 
WHERE prenda_pedido_id IN (
  SELECT id FROM prendas_pedido 
  WHERE pedido_produccion_id = (
    SELECT id FROM pedidos_produccion WHERE numero_pedido = 50001
  )
) 
ORDER BY id DESC LIMIT 10;
```

---

## 📊 Campos Críticos Esperados

Cada variante **DEBE** tener:

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `talla` | string | ✅ SÍ | M, L, XL, etc |
| `cantidad` | int | ✅ SÍ | Cantidad de unidades |
| `color_id` | bigint | ✅ SÍ | ID del color (> 0) |
| `tela_id` | bigint | ✅ SÍ | ID de la tela (> 0) |
| `tipo_manga_id` | bigint | ✅ SÍ | ID del tipo de manga (> 0) |
| `tipo_broche_boton_id` | bigint | ✅ SÍ | ID del tipo de broche (> 0) |
| `manga_obs` | longtext | ❌ NO | Observaciones de manga |
| `broche_boton_obs` | longtext | ❌ NO | Observaciones de broche |
| `tiene_bolsillos` | tinyint(1) | ❌ NO | 0 o 1 |
| `bolsillos_obs` | longtext | ❌ NO | Observaciones de bolsillos |

---

## 🐛 Problemas Comunes y Soluciones

### Problema: `talla` está vacía
- **Causa:** El frontend no está enviando la talla
- **Solución:** Revisar `gestion-items-pedido.js` → método `recolectarDatosPedido()`

### Problema: `color_id` es 0 o NULL
- **Causa:** El usuario no seleccionó un color en el frontend
- **Solución:** Validar que el campo esté obligatorio en el formulario

### Problema: `tipo_broche_boton_id` es 0
- **Causa:** Campo renombrado pero el frontend aún envía otro nombre
- **Solución:** Revisar sincronización entre frontend y backend

### Problema: Los datos llegan incompletos al backend
- **Causa:** Problema en `recolectarDatosPedido()` o en la estructura JSON
- **Solución:** Validar con `debug_flujo_prendas.php` primero

---

## 🔗 Archivos Relacionados

### Backend
- `app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionViewController.php`
- `app/Application/Services/PedidoPrendaService.php`
- `app/Models/PrendaPedido.php`
- `app/Models/PrendaVariante.php`

### Frontend
- `public/js/modulos/crear-pedido/procesos/gestion-items-pedido.js`
- `resources/views/asesores/pedidos/crear-pedido-completo.blade.php`

---

## 📞 Contacto y Soporte

Si después de ejecutar los scripts no logras identificar el problema:

1. Ejecuta los tres scripts
2. Guarda los outputs en un archivo
3. Revisa los logs: `tail -200 storage/logs/laravel.log`
4. Compara la estructura JSON generada con lo esperado

---

**Última actualización:** 16 de Enero de 2026
