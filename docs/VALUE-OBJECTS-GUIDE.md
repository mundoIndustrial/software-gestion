# 📦 Value Objects - Encapsulación de Datos
**Fecha:** 6 de Diciembre, 2024  
**Propósito:** Implementar getters/setters explícitos con encapsulación real

---

## 🎯 ¿Por qué Value Objects?

### Problema Identificado
```php
// ❌ ANTES: Acceso directo a propiedades sin encapsulación
$orden->numero_pedido = 123;
$orden->cliente = 'Cliente X';
$prenda->cantidad_talla = ['M' => 5, 'L' => 3];

// ✅ DESPUÉS: Acceso controlado mediante getters/setters
$orden->setEstado('Entregado');
$estadisticas = $orden->getEstadisticas();
```

### Beneficios
1. **Encapsulación Real** - Control sobre cambios de estado
2. **Validación** - Garantizar datos consistentes
3. **Cálculos Derivados** - Propiedades que se calculan automáticamente
4. **Type Safety** - Retorno tipado desde métodos
5. **Mantenibilidad** - Cambios internos sin afectar API

---

## 📋 Value Objects Implementados

### 1. `OrdenData` - Datos de Orden

**Ubicación:** `app/ValueObjects/OrdenData.php` (155 líneas)

**Propiedades:**
```php
private int $numero_pedido;        // Identificador único
private string $cliente;            // Nombre del cliente
private string $estado;             // Estado actual
private ?string $fecha_creacion;    // Fecha de creación
private ?string $forma_pago;        // Forma de pago
private ?string $area;              // Área de proceso
private int $total_cantidad;        // Cantidad total
private int $total_entregado;       // Cantidad entregada
```

**Getters:**
```php
getNumeroPedido(): int
getCliente(): string
getEstado(): string
getFechaCreacion(): ?string
getFormaPago(): ?string
getArea(): ?string
getTotalCantidad(): int
getTotalEntregado(): int
getPendiente(): int  // Calculado: total - entregado
```

**Setters:**
```php
setEstado(string $estado): self
setArea(?string $area): self
setFormaPago(?string $forma_pago): self
setTotalCantidad(int $total): self
setTotalEntregado(int $total): self
```

**Factory Methods:**
```php
static fromArray(array $data): self
static fromModel($modelo): self
```

**Métodos Utilitarios:**
```php
toArray(): array              // Para BD
validate(): bool              // Validación
```

---

### 2. `PrendaData` - Datos de Prenda

**Ubicación:** `app/ValueObjects/PrendaData.php` (175 líneas)

**Propiedades:**
```php
private int $numero_pedido;
private string $nombre_prenda;
private array $cantidad_talla;    // ['M' => 5, 'L' => 3, ...]
private int $cantidad_total;      // Calculado automáticamente
```

**Getters:**
```php
getNumeroPedido(): int
getNombrePrenda(): string
getCantidadTalla(): array
getCantidadTallaPorTalla(string $talla): int
getCantidadTotal(): int
getTallas(): array
```

**Setters (Fluent Interface):**
```php
setCantidadTalla(array $cantidad_talla): self
addTalla(string $talla, int $cantidad): self
setTallaCantidad(string $talla, int $cantidad): self
removeTalla(string $talla): self
```

**Ejemplo de uso:**
```php
$prenda = PrendaData::fromArray([
    'numero_pedido' => 123,
    'nombre_prenda' => 'Camisa Polo',
    'cantidad_talla' => ['S' => 2, 'M' => 5, 'L' => 3]
]);

// Fluent interface
$prenda
    ->addTalla('XL', 2)
    ->setTallaCantidad('S', 1)
    ->removeTalla('XL');

echo $prenda->getCantidadTotal();  // 9
```

---

### 3. `EstadisticasOrden` - Estadísticas Derivadas

**Ubicación:** `app/ValueObjects/EstadisticasOrden.php` (155 líneas)

**Propiedades:**
```php
private int $total_cantidad;
private int $total_entregado;
private int $total_pendiente;           // Calculado
private float $porcentaje_completado;   // Calculado
private string $estado_entrega;         // Calculado
```

**Getters:**
```php
getTotalCantidad(): int
getTotalEntregado(): int
getTotalPendiente(): int
getPorcentajeCompletado(): float
getEstadoEntrega(): string
```

**Getters Booleanos (Convenience):**
```php
isCompleto(): bool
isVacio(): bool
estaEnProgreso(): bool
noHaIniciado(): bool
```

**Ejemplo de uso:**
```php
$stats = EstadisticasOrden::create(100, 75);

echo $stats->getPorcentajeCompletado();  // 75.0
echo $stats->getTotalPendiente();         // 25
echo $stats->getEstadoEntrega();          // "En progreso"

if ($stats->isCompleto()) {
    echo "Orden completada";
}
```

---

## 🔄 Integración con Servicios

### Antes (Sin Value Objects)
```php
class RegistroOrdenStatsService {
    public function getOrderStats(int $pedido): array
    {
        $totalCantidad = DB::table('prendas_pedido')
            ->where('numero_pedido', $pedido)
            ->sum('cantidad');

        $totalEntregado = DB::table('procesos_prenda')
            ->where('numero_pedido', $pedido)
            ->sum('cantidad_completada');

        return [
            'total_cantidad' => $totalCantidad,
            'total_entregado' => $totalEntregado
        ];
    }
}
```

### Después (Con Value Objects)
```php
class RegistroOrdenStatsService {
    public function getOrderStats(int $pedido): EstadisticasOrden
    {
        $totalCantidad = $this->getTotalQuantity($pedido);
        $totalEntregado = $this->getTotalDelivered($pedido);

        return EstadisticasOrden::create($totalCantidad, $totalEntregado);
    }
}
```

---

## 🏗️ Arquitectura con Value Objects

```
┌──────────────────────────────────────────────────┐
│           HTTP REQUEST                           │
└────────────────┬─────────────────────────────────┘
                 │
                 ▼
        ┌────────────────────────┐
        │ RegistroOrdenController│
        └────────────┬───────────┘
                     │
    ┌────────────────┼────────────────┐
    ▼                ▼                ▼
┌──────────────┐ ┌──────────────┐ ┌─────────────┐
│Service Layer │ │Service Layer │ │Service Layer│
└──────┬───────┘ └──────┬───────┘ └──────┬──────┘
       │                │                │
       ▼                ▼                ▼
┌──────────────┐ ┌──────────────┐ ┌─────────────┐
│ OrdenData    │ │ PrendaData   │ │ Estadísticas│
│              │ │              │ │ Orden       │
│ + getters    │ │ + getters    │ │             │
│ + setters    │ │ + setters    │ │ + getters   │
└──────┬───────┘ └──────┬───────┘ └──────┬──────┘
       │                │                │
       └────────────────┼────────────────┘
                        │
                        ▼
                ┌────────────────┐
                │ JSON Response  │
                └────────────────┘
```

---

## 📝 Patrón Value Object

**Características:**
1. ✅ Inmutabilidad (parcial con setters fluent)
2. ✅ Encapsulación total (propiedades privadas)
3. ✅ Factory methods para creación
4. ✅ Getters tipados
5. ✅ Validación incorporada
6. ✅ Cálculos derivados

**Factory Methods Pattern:**
```php
// Construcción flexible
$orden = OrdenData::fromArray($data);
$orden = OrdenData::fromModel($modelo);
$prenda = PrendaData::fromArray($data);
```

**Fluent Interface:**
```php
$prenda
    ->addTalla('XL', 5)
    ->setTallaCantidad('M', 3)
    ->removeTalla('S');
```

---

## ✅ Cumplimiento SOLID

| Principio | Cumplimiento |
|-----------|--------------|
| **SRP** | ✅ Cada VO tiene responsabilidad única (datos + comportamiento) |
| **OCP** | ✅ Extensible sin modificar existentes (nuevos VOs) |
| **LSP** | ✅ Sustitución segura (getters siempre retornan tipos esperados) |
| **ISP** | ✅ Métodos específicos (no métodos inútiles) |
| **DIP** | ✅ Dependencia de abstracciones (Value Objects, no arrays) |

---

## 🔍 Ejemplo Completo

```php
// En un servicio
$orden = OrdenData::fromArray([
    'numero_pedido' => 123,
    'cliente' => 'Acme Corp',
    'estado' => 'No iniciado',
    'fecha_creacion' => '2024-12-06'
]);

$prendas = [
    PrendaData::fromArray([
        'numero_pedido' => 123,
        'nombre_prenda' => 'Camisa',
        'cantidad_talla' => ['M' => 5, 'L' => 3]
    ]),
    PrendaData::fromArray([
        'numero_pedido' => 123,
        'nombre_prenda' => 'Pantalón',
        'cantidad_talla' => ['M' => 2, 'L' => 2, 'XL' => 1]
    ])
];

// Manipulación segura
$totalPrendas = array_sum(array_map(
    fn($p) => $p->getCantidadTotal(),
    $prendas
));

$orden->setTotalCantidad($totalPrendas);
$orden->setEstado('En Ejecución');

// Conversión a JSON
return response()->json([
    'orden' => $orden->toArray(),
    'prendas' => array_map(fn($p) => $p->toApiArray(), $prendas)
]);
```

---

## 🚀 Próximos Pasos

1. **Integrar Value Objects en Servicios**
   - `RegistroOrdenStatsService` → retornar `EstadisticasOrden`
   - `RegistroOrdenPrendaService` → retornar array de `PrendaData`
   - `RegistroOrdenCreationService` → aceptar `OrdenData`

2. **Crear más Value Objects**
   - `ProcesoPrendaData` - Datos de procesos
   - `EntregaData` - Datos de entregas
   - `ValidacionData` - Errores de validación

3. **Actualizar Controlador**
   - Usar Value Objects en responses
   - Convertir a JSON al final

4. **Testing**
   - Unit tests para Value Objects
   - Tests de validación y cálculos

---

## 📊 Beneficios Resumidos

| Beneficio | Antes | Después |
|-----------|-------|---------|
| Encapsulación | ❌ Acceso directo | ✅ Getters/Setters |
| Validación | ❌ En servicios | ✅ En Value Object |
| Type Safety | ⚠️ Mixed types | ✅ Tipado completo |
| Mantenibilidad | ⚠️ Frágil | ✅ Robusta |
| Testabilidad | ⚠️ Difícil | ✅ Fácil |
| IDE Autocompletar | ⚠️ Limited | ✅ Full |

---

**Implementado:** 6 de Diciembre, 2024  
**Estado:** ✅ Ready para integración
