# 🔍 AUDITORÍA BACKEND: Migración de Tallas a Modelo Relacional

**Fecha:** 22 de Enero 2026  
**Estado:** CRÍTICO - Se requieren cambios en 2 servicios

---

## 📊 ANÁLISIS GENERAL

### ✅ CORRECTAMENTE IMPLEMENTADO

#### 1. **Tabla de Base de Datos** 
- ✅ Tabla `prenda_pedido_tallas` creada (2026_01_22_000000)
- ✅ Estructura: `prenda_pedido_id` + `genero` + `talla` + `cantidad`
- ✅ Genero es ENUM: ['DAMA', 'CABALLERO', 'UNISEX']
- ✅ Índice único por: (prenda_pedido_id, genero, talla)

```php
// Correcta estructura relacional
Schema::create('prenda_pedido_tallas', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('prenda_pedido_id');
    $table->enum('genero', ['DAMA', 'CABALLERO', 'UNISEX']);  // ✅ RELACIONAL
    $table->string('talla', 50);
    $table->unsignedInteger('cantidad')->default(0);
    // Foreign keys e índices...
});
```

#### 2. **Controlador - PedidosProduccionViewController**
- ✅ Recibe FormData correctamente
- ✅ Procesa archivos de prendas
- ✅ Llama a `PedidoPrendaService::guardarPrendasEnPedido()`
- ✅ Calcula cantidad_total desde tabla relacional

```php
// CORRECTO: Cálculo desde tabla relacional
$cantidadTotal = \DB::table('prenda_pedido_tallas')
    ->whereIn('prenda_pedido_id', $pedido->prendas()->pluck('id'))
    ->sum('cantidad');
```

#### 3. **Flujo PedidoPrendaService**
- ✅ Recibe `cantidad_talla` como array
- ✅ Detecta estructura relacional: `{GENERO: {TALLA: CANTIDAD}}`
- ✅ Delega a `guardarTallasPrenda()` ✅ CORRECTO

---

## 🔴 PROBLEMAS ENCONTRADOS

### **CRÍTICO #1: PrendaTallaService (Domain) - Formato Legacy**

**Archivo:** `app/Domain/PedidoProduccion/Services/PrendaTallaService.php`

**Problema:**  
El servicio espera formato LEGACY: `{talla: cantidad}` (string plano)
Pero ahora recibe formato RELACIONAL: `{DAMA: {S: 5}, CABALLERO: {M: 3}}`

```php
// ❌ ACTUAL - LEGACY
public function guardarTallasPrenda(int $prendaId, mixed $cantidades): void
{
    // Trata cada entrada como: "S" => 5, "M" => 10
    foreach ($tallasCantidades as $talla => $cantidad) {
        $registros[] = [
            'prenda_ped_id' => $prendaId,
            'talla' => (string)$talla,           // ❌ Falta 'genero'
            'cantidad' => (int)$cantidad,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
    
    \App\Models\PrendaTalaPed::insert($registros);  // ❌ Tabla incorrecta
}
```

**Impacto:**
- ❌ No guarda el `genero` (DAMA/CABALLERO/UNISEX)
- ❌ Usa tabla `PrendaTalaPed` (tabla legacy)
- ❌ No utiliza tabla `prenda_pedido_tallas` (tabla relacional)

**Solución Requerida:**  
Actualizar método para procesar estructura relacional:

```php
// ✅ NUEVO - RELACIONAL
public function guardarTallasPrenda(int $prendaId, mixed $cantidades): void
{
    $tallasCantidades = is_string($cantidades) 
        ? json_decode($cantidades, true) ?? [] 
        : (array)$cantidades;

    if (empty($tallasCantidades)) {
        return;
    }

    $registros = [];
    
    // Procesar estructura: {DAMA: {S: 5}, CABALLERO: {M: 3}}
    foreach ($tallasCantidades as $genero => $tallas) {
        // Validar que es género válido
        if (!in_array($genero, ['DAMA', 'CABALLERO', 'UNISEX'])) {
            continue;
        }
        
        // Procesar tallas de este género
        if (is_array($tallas)) {
            foreach ($tallas as $talla => $cantidad) {
                if ($cantidad > 0) {
                    $registros[] = [
                        'prenda_pedido_id' => $prendaId,
                        'genero' => $genero,                    // ✅ NUEVO
                        'talla' => (string)$talla,
                        'cantidad' => (int)$cantidad,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }
    }

    if (!empty($registros)) {
        // ✅ Tabla correcta
        \DB::table('prenda_pedido_tallas')->insert($registros);
        
        Log::info(' [PrendaTallaService] Tallas relacionales guardadas', [
            'prenda_pedido_id' => $prendaId,
            'total_registros' => count($registros),
        ]);
    }
}
```

---

### **CRÍTICO #2: PrendaVarianteService - Compatibilidad**

**Archivo:** `app/Application/Services/PrendaVarianteService.php`

**Problema:**  
Crea variantes usando `crearVariantesDesdeCantidadTalla()` con el formato antiguo.

**Impacto:**
- ❌ Variantes no alineadas con tallas relacionales
- ⚠️ Posible inconsistencia de datos

**Verificación Necesaria:**
Revisar cómo este servicio procesa el nuevo formato y ajustar si es necesario.

---

## 📋 RESUMEN DE CAMBIOS BACKEND NECESARIOS

| Componente | Estado | Acción |
|-----------|--------|--------|
| BD - Tabla prenda_pedido_tallas | ✅ OK | Ninguna |
| BD - Estructura GENERO+TALLA+CANTIDAD | ✅ OK | Ninguna |
| Controlador PedidosProduccionViewController | ✅ OK | Ninguna |
| PedidoPrendaService - guardarPrenda | ✅ OK | Verificar |
| **PrendaTallaService - guardarTallasPrenda** | 🔴 FALLO | **ACTUALIZAR** |
| PrendaVarianteService | ⚠️ REVISAR | Validar salida |
| CrearProcesoPrendaDTO | ⚠️ REVISAR | Verifica tallas_dama/caballero |
| EloquentProcesoPrendaDetalleRepository | ⚠️ REVISAR | Mapeo de tallas legacy |

---

## 🔧 RECOMENDACIONES DE IMPLEMENTACIÓN

### **Paso 1: Actualizar PrendaTallaService.php**
Implementar el método mejorado que procesa estructura relacional.

### **Paso 2: Validar PrendaVarianteService**
- Asegurar que crea variantes correctamente
- Validar que no crea duplicados

### **Paso 3: Revisar DTOs**
- `CrearProcesoPrendaDTO`: Cambiar `tallas_dama`/`tallas_caballero` → `tallas`
- Validar transformación de datos

### **Paso 4: Auditoría de Repositorios**
- Verificar lecturas desde `prenda_pedido_tallas`
- Asegurar que no usa tabla legacy `prenda_tala_ped`

### **Paso 5: Testing E2E**
```bash
# Crear pedido con 3 prendas, cada una con múltiples tallas
# Verificar:
1. ✅ Datos guardados en prenda_pedido_tallas
2. ✅ Genero + Talla + Cantidad correctos
3. ✅ Cantidad total calculado correctamente
4. ✅ Sin duplicados
5. ✅ Sin referencias a tabla legacy
```

---

## 📁 ARCHIVOS A MODIFICAR

### **CRÍTICO (Bloqueante):**
1. `app/Domain/PedidoProduccion/Services/PrendaTallaService.php` → Actualizar método

### **IMPORTANTE (Validar):**
2. `app/Application/Services/PrendaVarianteService.php` → Revisar compatibilidad
3. `app/DTOs/CrearProcesoPrendaDTO.php` → Migrar a estructura relacional
4. `app/Repositories/EloquentProcesoPrendaDetalleRepository.php` → Adaptar queries

### **INFORMACIÓN (Monitorear):**
5. Todos los lugares que leen de `prenda_pedido_tallas`

---

## 🎯 CRITERIOS DE ACEPTACIÓN

- [ ] PrendaTallaService usa tabla `prenda_pedido_tallas`
- [ ] Cada registro tiene: prenda_pedido_id + genero + talla + cantidad
- [ ] Genero es uno de: DAMA, CABALLERO, UNISEX
- [ ] No hay referencias a tabla `prenda_tala_ped` o `prendas_tala`
- [ ] Tests E2E pasan correctamente
- [ ] Datos existentes migrables sin pérdida

---

## 🚀 PRÓXIMAS ACCIONES

1. Actualizar `PrendaTallaService.php` según especificación
2. Ejecutar tests para validar cambios
3. Revisar compatibilidad con procesos (tallas_dama/caballero)
4. Validación E2E completa

