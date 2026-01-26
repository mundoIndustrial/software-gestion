# 🧪 Guía Rápida de Prueba: Procesos Automáticos

## ⚡ Prueba Rápida (2 minutos)

### Opción 1: Ejecutar Tests Unitarios

```bash
# En la terminal de tu proyecto
php artisan test tests/Feature/ProcesosAutomaticosTest.php

# O solo una prueba específica
php artisan test tests/Feature/ProcesosAutomaticosTest.php --filter test_proceso_creacion_orden_se_crea_automaticamente

# Output esperado:
# ✓ test_proceso_creacion_orden_se_crea_automaticamente
# ✓ test_proceso_inicial_tiene_datos_correctos
# ✓ test_multiples_pedidos_tienen_procesos_independientes
# ✓ test_pedido_se_crea_con_estado_y_area_correctos
# ✓ test_crear_proceso_adicional
# ✓ test_error_en_proceso_inicial_causa_rollback
# ✓ test_codigo_referencia_se_asigna_correctamente
```

---

## 🔍 Prueba Manual en Base de Datos

### Paso 1: Crear un Pedido

**Opción A: Vía API (si tienes endpoint)**
```bash
curl -X POST http://localhost:8000/api/pedidos \
  -H "Content-Type: application/json" \
  -d '{
    "pedido": 9999,
    "cliente": "Test Manual",
    "fecha_creacion": "2024-01-15",
    "forma_pago": "Contado",
    "prendas": [
      {
        "prenda": "Camiseta",
        "tallas": [
          {"talla": "M", "cantidad": 10}
        ]
      }
    ]
  }'
```

**Opción B: Vía Formulario Web**
1. Ir a la URL de crear pedido en tu aplicación
2. Completar formulario con datos de prueba
3. Guardar

### Paso 2: Verificar en Base de Datos

```sql
-- Ejecutar en tu BD (MySQL/PostgreSQL)
SELECT * FROM procesos_prenda 
WHERE numero_pedido = 9999 
AND proceso = 'Creación de Orden';

-- Resultado esperado:
-- | id | numero_pedido | prenda_pedido_id | proceso          | estado_proceso | fecha_inicio | dias_duracion | encargado | observaciones                        | codigo_referencia |
-- |----|---------------|------------------|------------------|----------------|--------------|---------------|-----------|--------------------------------------|-------------------|
-- | 1  | 9999          | NULL             | Creación de Orden| Pendiente      | 2024-01-15...| 1             | NULL      | Proceso inicial de creación del ... | 9999              |
```

---

## 🌐 Prueba en Frontend (Recibos)

### Paso 1: Crear Pedido y Abrir Recibos

1. En tu navegador, ir a la sección de recibos
2. Crear nuevo pedido (mismo que arriba)
3. Abrir el recibo del pedido creado

### Paso 2: Verificar Red en DevTools

```javascript
// En Console de DevTools (F12)
// El endpoint que se llamará será: /recibos/datos/[ID]

// Respuesta esperada (en Network tab → Response):
{
  "procesos": [
    {
      "id": 1,
      "numero_pedido": 9999,
      "prenda_pedido_id": null,
      "proceso": "Creación de Orden",
      "nombre": "Creación de Orden",           Campo importante
      "tipo": "Creación de Orden",             Campo importante
      "estado_proceso": "Pendiente",
      "fecha_inicio": "2024-01-15T10:30:45",
      "fecha_fin": null,
      "dias_duracion": 1,
      "encargado": null,
      "observaciones": "Proceso inicial de creación del pedido",
      "codigo_referencia": "9999",
      "tallas": [],
      "imagenes": [],
      "ubicaciones": []
    }
  ],
  "prendas": [...],
  "pedido": {...}
}
```

### Paso 3: Verificar Visualización

1. En el modal de recibos, debe aparecer "Creación de Orden" en la sección de procesos
2. Debe mostrar estado "Pendiente"
3. Debe tener imagen (si aplica)

---

## 📊 Prueba de Logs

```bash
# En terminal (en la carpeta del proyecto)
tail -f storage/logs/laravel.log | grep "REGISTRO-ORDEN"

# Luego crear un pedido y observar:
[2024-01-15 10:30:45] local.INFO: [REGISTRO-ORDEN] Creando pedido...
[2024-01-15 10:30:45] local.INFO: [REGISTRO-ORDEN] Pedido creado exitosamente
[2024-01-15 10:30:45] local.INFO: [REGISTRO-ORDEN-PROCESO] Iniciando creación de proceso inicial
[2024-01-15 10:30:45] local.INFO: [REGISTRO-ORDEN-PROCESO] Proceso inicial creado exitosamente
```

---

## 🔄 Prueba de Transacciones (Rollback)

### Verificar que si algo falla, todo se deshace

```php
// En Laravel Tinker o un Test
>>> $service = app('App\Services\RegistroOrdenCreationService');

// Verificar estado antes
>>> DB::table('pedidos_produccion')->count();
// 100

>>> DB::table('procesos_prenda')->count();
// 150

// Simular error (datos inválidos)
>>> try {
    $service->createOrder(['invalid' => 'data']);
  } catch (\Exception $e) {
    echo $e->getMessage();
  }

// Verificar estado después (debe ser igual)
>>> DB::table('pedidos_produccion')->count();
// 100 (sin cambios)

>>> DB::table('procesos_prenda')->count();
// 150 (sin cambios)
```

---

## Checklist de Verificación Rápida

- [ ] Test 1: `test_proceso_creacion_orden_se_crea_automaticamente` - PASS
- [ ] Test 2: `test_proceso_inicial_tiene_datos_correctos` - PASS
- [ ] Test 3: `test_multiples_pedidos_tienen_procesos_independientes` - PASS
- [ ] Test 4: `test_pedido_se_crea_con_estado_y_area_correctos` - PASS
- [ ] Test 5: `test_crear_proceso_adicional` - PASS
- [ ] Test 6: `test_error_en_proceso_inicial_causa_rollback` - PASS
- [ ] Test 7: `test_codigo_referencia_se_asigna_correctamente` - PASS
- [ ] BD Manual: Proceso "Creación de Orden" aparece ✓
- [ ] Frontend: Proceso aparece en recibos ✓
- [ ] Logs: Se registran eventos correctamente ✓
- [ ] Transacciones: Rollback funciona ✓

---

## 🚨 Si Algo Falla

### Problema: "Process not created"

```bash
# 1. Verificar logs
tail -50 storage/logs/laravel.log

# 2. Verificar tabla existe
SHOW TABLES LIKE 'procesos_prenda';

# 3. Verificar estructura de tabla
DESCRIBE procesos_prenda;

# 4. Verificar modelo ProcesoPrenda
# Debe tener estos campos en $fillable:
protected $fillable = [
    'numero_pedido', 'prenda_pedido_id', 'proceso',
    'estado_proceso', 'fecha_inicio', 'fecha_fin',
    'dias_duracion', 'encargado', 'observaciones',
    'codigo_referencia', // ... otros campos
];
```

### Problema: "Foreign Key Error"

```bash
# 1. Verificar constraints
SHOW CREATE TABLE procesos_prenda;

# 2. Si tiene FK a pedidos_produccion, verificar que exista el pedido
SELECT * FROM pedidos_produccion WHERE numero_pedido = 9999;

# 3. Desactivar checks temporalmente si es necesario (cuidado)
SET FOREIGN_KEY_CHECKS=0;
# Crear datos
SET FOREIGN_KEY_CHECKS=1;
```

### Problema: "Test Failed - Assertion"

1. Leer mensaje de error completo
2. Verificar que los datos coinciden exactamente
3. Ejecutar con `-vv` para más detalle:
   ```bash
   php artisan test tests/Feature/ProcesosAutomaticosTest.php -vv
   ```

---

## 💡 Tips Útiles

```php
// En Tinker (php artisan tinker)

// Ver último pedido creado
>>> $pedido = \App\Models\PedidoProduccion::latest()->first();
>>> $pedido

// Ver procesos del último pedido
>>> $pedido->procesos()->get();
// Relación debe estar definida en Model

// Ver si método funciona directamente
>>> $service = app('App\Services\RegistroOrdenCreationService');
>>> $service->createAdditionalProcesso($pedido, 'Costura', ['dias_duracion' => 2]);

// Verificar campos de ProcesoPrenda
>>> $proceso = \App\Models\ProcesoPrenda::find(1);
>>> $proceso->toArray();
```

---

## 📝 Script de Prueba Completo

```bash
#!/bin/bash

echo "🧪 Iniciando pruebas de procesos automáticos..."

# 1. Ejecutar tests
echo "1️⃣  Ejecutando tests..."
php artisan test tests/Feature/ProcesosAutomaticosTest.php --no-ansi

if [ $? -eq 0 ]; then
    echo "✅ Todos los tests pasaron!"
else
    echo " Algunos tests fallaron"
    exit 1
fi

# 2. Verificar BD
echo "2️⃣  Verificando base de datos..."
php artisan tinker << 'EOF'
$procesos = \App\Models\ProcesoPrenda::where('proceso', 'Creación de Orden')->count();
echo "Procesos 'Creación de Orden': $procesos\n";

$pedidos = \App\Models\PedidoProduccion::count();
echo "Total de pedidos: $pedidos\n";

return true;
EOF

echo "3️⃣  Prueba completada con éxito!"
```

---

## 🎯 Resultado Esperado

**Cuando todo funciona correctamente:**

✅ Todos los 7 tests pasan  
✅ Procesos aparecen en BD  
✅ Procesos aparecen en recibos del frontend  
✅ Logs muestran creación exitosa  
✅ Transacciones funcionan correctamente  
✅ No hay errores en console/logs  

---

**Duración total de prueba:** 5-10 minutos  
**Dificultad:** Baja (principalmente clic y verificación)  
**Requiere:** Terminal + Navegador + BD tool (opcional)
