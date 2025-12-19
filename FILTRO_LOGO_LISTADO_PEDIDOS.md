# FILTRO LOGO EN LISTADO DE PEDIDOS

## 📋 Cambios Realizados

### 1. **Controlador** (`PedidoProduccionController.php`)
- ✅ Agregada relación `logoPedidos` en consulta
- ✅ Soporte para filtro `tipo=logo` via query parameter
- ✅ Filtra solo pedidos que tengan `logoPedidos` cuando se activa el filtro
- ✅ Logs mejorados con información de tipo y número mostrable

### 2. **Vista** (`index.blade.php`)
- ✅ Agregado botón "Logo" con icono de paleta 🎨
- ✅ Botón activo cuando `request('tipo') === 'logo'`
- ✅ Reemplazado display de número: `numero_pedido` → `numero_pedido_mostrable`
- ✅ Ahora muestra LOGO-00001 para pedidos LOGO y 45451 para pedidos normales

### 3. **Modelo** (`PedidoProduccion.php`)
- ✅ Método `logoPedidos()` - Relación HasMany
- ✅ Método `logoPedido()` - Obtiene el primer LogoPedido
- ✅ Método `esLogo()` - Boolean si es pedido LOGO
- ✅ Método `getNumeroPedidoMostrable()` - Retorna número correcto
- ✅ Accessor `numero_pedido_mostrable` - Disponible en JSON

### 4. **DTO** (`CrearPedidoProduccionDTO.php`)
- ✅ Método `esLogoPedido()` - Detecta si es pedido LOGO

### 5. **Job** (`CrearPedidoProduccionJob.php`)
- ✅ No asigna número en `pedidos_produccion` cuando `esLogoPedido() === true`
- ✅ Mantiene NULL/vacío para pedidos LOGO

---

## 🎯 Comportamiento Esperado

### Filtro "Todos" (sin parámetros)
```
URL: /asesores/pedidos
Muestra: Todos los pedidos (LOGO y normales)
Números: 
  - Normales: 45451, 45452, etc.
  - LOGO: LOGO-00001, LOGO-00002, etc.
```

### Filtro "Logo"
```
URL: /asesores/pedidos?tipo=logo
Muestra: SOLO pedidos LOGO
Números: LOGO-00001, LOGO-00002, LOGO-00003, etc.
```

---

## 📊 Flujo de Visualización

```
Usuario cliquea "Logo"
    ↓
URL cambia a /asesores/pedidos?tipo=logo
    ↓
Controller filtra: whereHas('logoPedidos')
    ↓
Solo pedidos con relación logoPedidos se muestran
    ↓
Para cada pedido:
  - Si es LOGO: muestra LOGO-00001 (del numero_pedido_mostrable)
  - Si es normal: muestra 45451 (del numero_pedido_mostrable)
```

---

## 💾 Almacenamiento de Datos

### Pedidos Normales (con prendas)
```
pedidos_produccion:
  - numero_pedido: 45451
  
logo_pedidos: (no existe)
```

### Pedidos LOGO (sin prendas)
```
pedidos_produccion:
  - numero_pedido: NULL o vacío
  
logo_pedidos:
  - numero_pedido: LOGO-00001
  - pedido_id: (FK)
```

---

## 🔍 Verificación

Para verificar que funciona:

```sql
-- Ver pedidos LOGO
SELECT 
    pp.id as pedido_id,
    pp.numero_pedido as numero_en_produccion,
    lp.numero_pedido as numero_en_logo,
    pp.cliente
FROM pedidos_produccion pp
INNER JOIN logo_pedidos lp ON pp.id = lp.pedido_id
ORDER BY pp.created_at DESC;
```

---

## ✅ Checklist de Validación

- [ ] El botón "Logo" aparece en los filtros rápidos
- [ ] Al cliquear "Logo", se filtra solo pedidos LOGO
- [ ] Los números mostrados son LOGO-00001, LOGO-00002, etc.
- [ ] Al cliquear "Todos", se muestran todos los pedidos
- [ ] Los números normales se muestran correctamente (45451, etc.)
- [ ] El campo `numero_pedido` en pedidos_produccion está NULL/vacío para LOGO
- [ ] No se incrementa la secuencia `numero_pedido` para pedidos LOGO

---

## 🚀 Próximos Pasos Opcionales

1. **Combinar filtros**: Logo + Estado (Logo + Pendientes)
2. **Búsqueda avanzada**: Por número LOGO (LOGO-00001)
3. **Reportes**: Listar solo pedidos LOGO con sus imágenes
4. **Detalle**: Mostrar tabla de imágenes en vista de pedido LOGO
