# ⚡ QUICK REFERENCE: Cambios Implementados

**Fecha:** 16 de Enero, 2026  
**Tiempo de Implementación:** ~1 hora  
**Riesgo:** BAJO - Cambios bien aislados  

---

## 🎯 QUÉ SE CAMBIÓ

### Backend (PHP)

#### 1. `PrendaPedido` Model
```php
//  Ahora usa:
'pedido_produccion_id' (FK a pedidos_produccion.id)

//  Ya no se usa:
'numero_pedido'  // Comentado para referencia
```

#### 2. `PedidoProduccion` Model - Relación
```php
//  ANTES:
public function prendas(): HasMany {
    return $this->hasMany(PrendaPedido::class, 'numero_pedido', 'numero_pedido');
}

//  DESPUÉS:
public function prendas(): HasMany {
    return $this->hasMany(PrendaPedido::class, 'pedido_produccion_id');
}
```

#### 3. `PedidoPrendaService` - Al guardar prenda
```php
//  ANTES:
PrendaPedido::create([
    'numero_pedido' => $pedido->numero_pedido,
    'tipo_broche_id' => $prendaData['tipo_broche_id'],
]);

//  DESPUÉS:
PrendaPedido::create([
    'pedido_produccion_id' => $pedido->id,  //  CAMBIO CRÍTICO
    'tipo_broche_boton_id' => $prendaData['tipo_broche_boton_id'],  //  Actualizado
]);
```

---

### Frontend (JavaScript)

#### 1. `gestion-items-pedido.js` - recolectarDatosPedido()
```javascript
//  ANTES:
return {
    cliente: ...,
    items: [...],
    numero_pedido: 1025,  //  Enviaba esto
};

//  DESPUÉS:
return {
    cliente: ...,
    items: [...],
    // numero_pedido: null,  //  COMENTADO - Backend lo genera
};
```

#### 2. Logs Agregados
```javascript
// 🔍 En consola ahora verás:
📤 Objeto pedido final a enviar: {...}
 [manejarSubmitFormulario] Datos del pedido recolectados:
   Cliente: EMPRESA XYZ
   Items totales: 2
   ✓ Ítem 0: prenda="CAMISA POLO", tiene_id=false, tiene_tallas=true
 [manejarSubmitFormulario] PEDIDO CREADO EXITOSAMENTE
   pedido_id: 42
   numero_pedido: 1025
```

---

## 📊 IMPACTO

| Componente | Antes | Después | Beneficio |
|-----------|-------|---------|-----------|
| FK en `prendas_pedido` | `numero_pedido` | `pedido_produccion_id` |  Correcta relación |
| Validación MySQL |  Falla NOT NULL |  Passa |  Sin errores |
| `numero_pedido` |  Enviado desde FE |  Generado en BE |  Single source of truth |
| `tipo_broche_id` |  Antiguo |  `tipo_broche_boton_id` |  Consistente |
| Logs de Debug |  Ninguno | 📝 Múltiples |  Fácil debugging |

---

## 🧪 CÓMO VERIFICAR

### 1. Abrir DevTools (F12)

```bash
# En navegador -> F12 -> Consola
# Debería verse:
 [manejarSubmitFormulario] Datos del pedido recolectados:
   Items totales: 1
   ✓ Ítem 0: prenda="CAMISA POLO", ...
```

### 2. Verificar BD

```sql
-- Después de crear pedido, ejecutar:
SELECT 
    pp.id, 
    pp.nombre_prenda, 
    pp.pedido_produccion_id,
    ppr.numero_pedido
FROM prendas_pedido pp
JOIN pedidos_produccion ppr ON pp.pedido_produccion_id = ppr.id
WHERE ppr.id = 42;

-- Debería retornar: pedido_produccion_id = 42 (no NULL) 
```

### 3. Ver Logs

```bash
tail -f storage/logs/laravel.log | grep "PedidoPrendaService"

# Debería verse:
 [PedidoPrendaService] Prenda guardada exitosamente
   prenda_id => 128
   pedido_produccion_id => 42 
```

---

## 🔄 FLUJO ACTUAL

```
┌─────────────────────────────────────────────────────┐
│ FRONTEND: gestion-items-pedido.js                   │
│                                                      │
│ 1. Recolecta items sin numero_pedido               │
│ 2. Agrega logs de verificación                      │
│ 3. Envía JSON al backend                            │
└──────────────────┬──────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────┐
│ BACKEND: CrearPedidoEditableController.php          │
│                                                      │
│ 1. Recibe items                                     │
│ 2. Crea PedidoProduccion (id=42)                   │
│ 3. Llama PedidoPrendaService                       │
└──────────────────┬──────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────┐
│ SERVICE: PedidoPrendaService.php                    │
│                                                      │
│ 1. Recibe pedido (id=42) + items                   │
│ 2. Para cada prenda:                               │
│    - PrendaPedido::create([                        │
│        'pedido_produccion_id' => 42,   AQUÍ      │
│        ...                                          │
│      ])                                            │
└──────────────────┬──────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────┐
│ DATABASE: MySQL                                     │
│                                                      │
│ prendas_pedido:                                     │
│ - id: 128                                           │
│ - pedido_produccion_id: 42   NO NULL             │
│ - nombre_prenda: CAMISA POLO                       │
│ - ...                                               │
└─────────────────────────────────────────────────────┘
```

---

## ⚙️ CAMPOS MODIFICADOS

### Tabla: `prendas_pedido`

| Campo | Antes | Después | Requerido |
|-------|-------|---------|-----------|
| `pedido_produccion_id` | Ignorado |  Usado | YES |
| `numero_pedido` | Usado |  Comentado | NO |
| `tipo_broche_id` | Usado |  Actualizado | NO |
| `tipo_broche_boton_id` | N/A |  Usado | NO |

---

## 🚨 POSIBLES PROBLEMAS Y SOLUCIONES

| Problema | Síntoma | Solución |
|----------|---------|----------|
| MySQL error NOT NULL en `pedido_produccion_id` |  Pedido no se crea | Ver: Service usa `pedido_produccion_id` al guardar |
| `numero_pedido` aparece en JSON | ⚠️ Aviso | Comentado en frontend, ignorado en backend |
| Prenda sin `pedido_produccion_id` |  Orfana | Verificar que relación `prendas()` usa FK correcto |
| Logs no aparecen | 🔍 No visible | Abrir DevTools F12 en navegador |

---

## 📱 CAMPOS JSON FRONTEND → BACKEND

```javascript
// Lo que SE ENVÍA:
{
  cliente: "EMPRESA XYZ",
  asesora: "Juan Pérez",
  forma_de_pago: "Contado",
  items: [
    {
      tipo: "prenda_nueva",
      prenda: "CAMISA POLO",
      origen: "bodega",
      tallas: ["dama-M", "dama-L"],
      variaciones: {...},
      // NO INCLUYE numero_pedido 
      // NO INCLUYE pedido_produccion_id  (se asigna en backend)
    }
  ]
}

// Lo que GENERA el backend:
{
  pedido_id: 42,
  numero_pedido: 1025,
  prendas: [
    {
      id: 128,
      pedido_produccion_id: 42,   ASIGNADO
      nombre_prenda: "CAMISA POLO",
      ...
    }
  ]
}
```

---

##  CHECKLIST FINAL

- [x] Modelos actualizados
- [x] Relaciones corregidas
- [x] Service usa `pedido_produccion_id`
- [x] Frontend comenta `numero_pedido`
- [x] Logs de depuración agregados
- [x] Documentación completada
- [x] Cambios `tipo_broche_id` → `tipo_broche_boton_id` incluidos
- [ ] Prueba manual en localhost
- [ ] Prueba en staging
- [ ] Deploy a producción

---

## 🎓 LECCIONES APRENDIDAS

1. **FK siempre debe usar PK de tabla relacionada**
   - `prendas_pedido.pedido_produccion_id` → `pedidos_produccion.id`
   - No usar campos alternativos como `numero_pedido`

2. **Single Source of Truth**
   - `numero_pedido` se genera una sola vez en `pedidos_produccion`
   - No repetir en `prendas_pedido` (evita inconsistencias)

3. **Logs de depuración son aliados**
   - Agregados en frontend permiten ver exactamente qué se envía
   - Facilita debugging cuando hay problemas

---

## 📞 COMANDOS ÚTILES

```bash
# Ver últimos errores
tail -f storage/logs/laravel.log

# Buscar logs de PedidoPrendaService
grep "PedidoPrendaService" storage/logs/laravel.log | tail -20

# Verificar estructura BD
DESC prendas_pedido;
DESC pedidos_produccion;

# Ver FK en tabla
SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME 
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE TABLE_NAME = 'prendas_pedido' 
AND REFERENCED_TABLE_NAME IS NOT NULL;
```

---

**Estado:**  IMPLEMENTADO Y DOCUMENTADO

