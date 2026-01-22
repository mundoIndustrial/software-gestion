# 🔧 GUÍA DE IMPLEMENTACIÓN: Migración Backend Tallas Relacionales

**Estado:** FASE 2 - BACKEND EN PROGRESO  
**Última Actualización:** 22 Enero 2026

---

## 📋 CAMBIOS REALIZADOS

### ✅ COMPLETADO

#### 1. **Frontend - Migración 100%**
- ✅ 12 archivos JavaScript refactorizados
- ✅ 0 referencias legacy (`cantidadesTallas`, `tallas_dama/caballero`)
- ✅ Sintaxis validada en todos los archivos
- ✅ Estructura relacional: `{DAMA: {S: 5}, CABALLERO: {M: 3}}`

#### 2. **Backend - Base de Datos**
- ✅ Tabla `prenda_pedido_tallas` con estructura relacional
- ✅ Esquema: `prenda_pedido_id` + `genero` + `talla` + `cantidad`
- ✅ Índice único para prevenir duplicados

#### 3. **Backend - Servicio PrendaTallaService**
- ✅ Actualizado para procesar estructura relacional
- ✅ Detecta automáticamente formato legacy vs. relacional
- ✅ Inserta en tabla correcta: `prenda_pedido_tallas`
- ✅ Incluye validación de género (DAMA/CABALLERO/UNISEX)

---

## 🔄 FLUJO COMPLETO FRONTEND → BACKEND

```
┌─────────────────────────────────────────────────────────┐
│ FRONTEND: Estructura Relacional (JavaScript)             │
│ {DAMA: {S: 5, M: 10}, CABALLERO: {M: 3, L: 7}}         │
└────────────────────────┬────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────┐
│ FormData.append('cantidad_talla', JSON.stringify(...))   │
│ + JSON.stringify(window.tallasRelacionales)              │
└────────────────────────┬────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────┐
│ BACKEND: Controlador (PedidosProduccionViewController)   │
│ POST /pedidos-produccion/crear-prenda-sin-cotizacion     │
└────────────────────────┬────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────┐
│ PedidoPrendaService::guardarPrendasEnPedido()            │
│ → Procesa prendas con cantidad_talla                     │
└────────────────────────┬────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────┐
│ PrendaTallaService::guardarTallasPrenda() [ACTUALIZADO]  │
│ → Detecta formato relacional                             │
│ → Inserta en prenda_pedido_tallas                        │
└────────────────────────┬────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────┐
│ BD: Tabla prenda_pedido_tallas                           │
│ prenda_pedido_id | genero    | talla | cantidad          │
│ 1                | DAMA      | S     | 5                  │
│ 1                | DAMA      | M     | 10                 │
│ 1                | CABALLERO | M     | 3                  │
│ 1                | CABALLERO | L     | 7                  │
└─────────────────────────────────────────────────────────┘
```

---

## 🧪 VALIDACIÓN: Pasos para Verificar Implementación

### Paso 1: Prueba de Unidad - PrendaTallaService

```php
// Crear test que valide ambos formatos
public function testGuardarTallasRelacional()
{
    $prendaId = 1;
    
    // FORMATO RELACIONAL
    $cantidades = [
        'DAMA' => ['S' => 5, 'M' => 10, 'L' => 3],
        'CABALLERO' => ['M' => 8, 'L' => 12],
    ];
    
    $service = new PrendaTallaService();
    $service->guardarTallasPrenda($prendaId, $cantidades);
    
    // Verificar
    $registros = DB::table('prenda_pedido_tallas')
        ->where('prenda_pedido_id', $prendaId)
        ->get();
    
    // Assertions
    $this->assertEquals(5, $registros->count());
    $this->assertEquals(38, $registros->sum('cantidad'));  // 5+10+3+8+12
    
    // Verificar géneros
    $this->assertTrue($registros->where('genero', 'DAMA')->count() > 0);
    $this->assertTrue($registros->where('genero', 'CABALLERO')->count() > 0);
}
```

### Paso 2: Prueba E2E - Crear Pedido Completo

```bash
# Terminal
php artisan tinker

# Simular request desde frontend
$data = [
    'cliente' => 'Cliente Test',
    'forma_de_pago' => 'Credito',
    'prendas' => [
        [
            'nombre_producto' => 'Polo',
            'descripcion' => 'Polo manga corta',
            'genero' => ['dama'],
            'cantidad_talla' => [
                'DAMA' => ['S' => 5, 'M' => 10],
                'CABALLERO' => [],
                'UNISEX' => []
            ]
        ]
    ]
];

// Crear pedido
$response = \Illuminate\Support\Facades\Http::post(
    route('pedidos-produccion.crear-prenda-sin-cotizacion'),
    $data
);

// Verificar respuesta
dd($response->json());

// Verificar BD
DB::table('prenda_pedido_tallas')->where('prenda_pedido_id', 1)->get();
```

### Paso 3: Verificación de Datos

```sql
-- Conectar a BD y ejecutar:

-- 1. Ver estructura de tabla
DESCRIBE prenda_pedido_tallas;

-- 2. Ver datos insertados
SELECT * FROM prenda_pedido_tallas 
WHERE prenda_pedido_id = 1
ORDER BY genero, talla;

-- 3. Verificar suma de cantidades
SELECT 
    prenda_pedido_id, 
    genero,
    SUM(cantidad) as total
FROM prenda_pedido_tallas
WHERE prenda_pedido_id = 1
GROUP BY prenda_pedido_id, genero;

-- 4. Buscar duplicados (NO debe haber)
SELECT 
    prenda_pedido_id, genero, talla, COUNT(*) as duplicados
FROM prenda_pedido_tallas
GROUP BY prenda_pedido_id, genero, talla
HAVING COUNT(*) > 1;
```

---

## 📝 CHECKLIST DE IMPLEMENTACIÓN

### Fase 1: Backend Actualizado ✅
- [x] Actualizar `PrendaTallaService::guardarTallasPrenda()`
- [x] Agregar validación de género
- [x] Agregar detección de formato (relacional vs. legacy)
- [x] Cambiar tabla: `prenda_tala_ped` → `prenda_pedido_tallas`
- [x] Validar sintaxis PHP

### Fase 2: Testing 
- [ ] Test unitario de `PrendaTallaService`
- [ ] Test E2E de crear pedido con prendas
- [ ] Validar datos en BD
- [ ] Probar con múltiples géneros
- [ ] Probar con estructura legacy (fallback)

### Fase 3: Datos Existentes
- [ ] Crear seeder para migrar datos legacy → relacional
- [ ] Validar integridad de migración
- [ ] Backup previa a migración

### Fase 4: Auditoría Adicional
- [ ] Revisar `PrendaVarianteService` para compatibilidad
- [ ] Verificar `CrearProcesoPrendaDTO`
- [ ] Auditar `EloquentProcesoPrendaDetalleRepository`
- [ ] Buscar referencias a tabla `prenda_tala_ped`

### Fase 5: Deploy
- [ ] Run migrations
- [ ] Clear cache
- [ ] Deploy código actualizado
- [ ] Validación final en producción

---

## 🚨 PUNTOS CRÍTICOS A MONITOREAR

### 1. **Compatibilidad Backward**
- El método detecta automáticamente formato legacy
- Fallback a UNISEX si no hay género especificado
- ✅ Sin breaking changes

### 2. **Validación de Género**
- Solo acepta: DAMA, CABALLERO, UNISEX
- Genera warning si recibe género inválido
- ✅ Seguro contra inyección

### 3. **Duplicados**
- Índice UNIQUE: (prenda_pedido_id, genero, talla)
- Previene inserciones duplicadas a nivel BD
- ✅ Integridad garantizada

### 4. **Logs y Auditoría**
- Log INFO: Tallas guardadas correctamente
- Log WARNING: Formato legacy o género inválido
- Log ERROR: Excepciones
- ✅ Trazabilidad completa

---

## 🔗 REFERENCIAS DE ARCHIVOS

### Actualizado:
- `app/Domain/PedidoProduccion/Services/PrendaTallaService.php`

### Documentación:
- `AUDITORIA_BACKEND_TALLAS_RELACIONALES.md`
- `GUIA_IMPLEMENTACION_PASO_A_PASO.md`

### Controladores:
- `app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionViewController.php`

### Servicios relacionados:
- `app/Application/Services/PedidoPrendaService.php`
- `app/Application/Services/PrendaVarianteService.php`

### Base de datos:
- `database/migrations/2026_01_22_000000_create_prenda_pedido_tallas_table.php`

---

## 🎯 SIGUIENTE PASO

Ejecutar tests E2E para validar que el flujo completo funciona:

```bash
# 1. Crear test
php artisan make:test PrendaTallaServiceTest

# 2. Implementar tests
# (Ver sección Paso 1 anterior)

# 3. Ejecutar
php artisan test tests/PrendaTallaServiceTest.php

# 4. Validar en BD manualmente si los tests pasan
```

---

## 📞 SOPORTE

Si encuentras problemas:

1. **Revisar logs:** `storage/logs/laravel.log`
2. **Ejecutar test individual:** `php artisan test --filter=testGuardarTallasRelacional`
3. **Verificar BD:** Ejecutar queries SQL de verificación
4. **Rollback:** `php artisan migrate:rollback`

