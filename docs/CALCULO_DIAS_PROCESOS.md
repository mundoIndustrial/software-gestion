# 📊 Sistema de Cálculo de Días en Procesos

## Descripción General

Se ha implementado un sistema automático de cálculo de días hábiles (excluyendo fines de semana y festivos) que reemplaza la lógica que estaba en `tabla_original`.

### ¿Qué cambió?

**Antes (tabla_original):**
- Campos de texto: `dias_corte`, `dias_bordado`, `dias_costura`, etc.
- Cálculo manual y propenso a errores
- No había automatización

**Ahora (procesos_prenda):**
- Campo único: `dias_duracion` que se calcula automáticamente
- Cálculo automático al guardar procesos
- Exclusión de sábados, domingos y festivos
- Métodos de helper en modelos para acceso fácil

## Componentes

### 1. `CalculadorDiasService` (app/Services)
Servicio central que calcula días hábiles.

**Métodos principales:**

```php
// Calcular días hábiles entre dos fechas
CalculadorDiasService::calcularDiasHabiles($fechaInicio, $fechaFin);
// Retorna: int (número de días)

// Formatear a texto "X días"
CalculadorDiasService::formatearDias($dias);
// Retorna: "5 días" o "1 día"

// Calcular días hasta hoy
CalculadorDiasService::calcularDiasHastahoy($fechaInicio);

// Validar si es fin de semana
CalculadorDiasService::esFinDeSemana($fecha);

// Validar si es festivo
CalculadorDiasService::esFestivo($fecha);

// Obtener próximo día hábil
CalculadorDiasService::proximoDiaHabil($fecha);
```

### 2. `ProcesoPrenda` (Model)
Modelo actualizado con cálculo automático.

**Características:**
- Al guardar un proceso con `fecha_inicio` y `fecha_fin`, calcula automáticamente `dias_duracion`
- Métodos helper para acceder a los días

**Uso en Blade:**

```blade
@foreach($pedido->procesos as $proceso)
    <tr>
        <td>{{ $proceso->proceso }}</td>
        <td>{{ $proceso->fecha_inicio->format('d/m/Y') }}</td>
        <td>{{ $proceso->fecha_fin->format('d/m/Y') }}</td>
        <td>{{ $proceso->dias_duracion }}</td> <!-- Calculado automáticamente -->
    </tr>
@endforeach
```

**Métodos disponibles:**

```php
$proceso->getDiasNumero();          // Retorna: 5 (número)
$proceso->getDiasHastaHoy();        // Para procesos en curso: "2 días"
$proceso->estáCompleto();           // bool
$proceso->estáEnProgreso();         // bool
```

### 3. `PedidoProduccion` (Model)
Métodos agregados para trabajar con días a nivel de pedido.

**Métodos disponibles:**

```php
$pedido->getTotalDias();                    // "25 días"
$pedido->getTotalDiasNumero();              // 25
$pedido->getDesgloseDiasPorProceso();       // Array con desglose por área
$pedido->estaEnRetraso();                   // bool
$pedido->getDiasDeRetraso();                // número de días de retraso
```

**Ejemplo de uso:**

```php
// En un controller
$pedido = PedidoProduccion::find(1);

// Información de días
echo $pedido->getTotalDias();              // "25 días"

// Desglose detallado
$desglose = $pedido->getDesgloseDiasPorProceso();
// Retorna:
// [
//     'Corte' => '5 días',
//     'Bordado' => '3 días',
//     'Costura' => '8 días',
//     ...
// ]

// Estado de entrega
if ($pedido->estaEnRetraso()) {
    echo "Retraso: " . $pedido->getDiasDeRetraso() . " días";
}
```

### 4. `CalculaDiasHelper` (Trait)
Trait reutilizable para controllers.

**Uso:**

```php
class MiController extends Controller {
    use CalculaDiasHelper;
    
    public function show($id) {
        $pedido = PedidoProduccion::find($id);
        
        // Obtener información formateada
        $infoDias = $this->formatearRespuestaDias($pedido);
        
        return response()->json($infoDias);
    }
}
```

### 5. Comando Artisan: `procesos:calcular-dias`
Calcula retroactivamente los días para procesos existentes.

**Uso:**

```bash
# Calcular días para procesos sin calcular
php artisan procesos:calcular-dias

# Modo dry-run (sin guardar)
php artisan procesos:calcular-dias --dry-run

# Recalcular todos los procesos
php artisan procesos:calcular-dias --fix-all
```

## Flujo de Datos

### 1. Cuando se crea/actualiza un proceso:

```php
$proceso = ProcesoPrenda::create([
    'prenda_pedido_id' => 1,
    'proceso' => 'Corte',
    'fecha_inicio' => '2025-01-15',
    'fecha_fin' => '2025-01-20',
    'encargado' => 'Juan',
    'estado_proceso' => 'Completado',
]);

// El modelo calcula automáticamente:
// $proceso->dias_duracion = "4 días" (excluyendo fines de semana)
```

### 2. En views/templates:

```blade
<!-- Información del proceso -->
<div class="process-card">
    <h4>{{ $proceso->proceso }}</h4>
    <p>Inicio: {{ $proceso->fecha_inicio->format('d/m/Y') }}</p>
    <p>Fin: {{ $proceso->fecha_fin->format('d/m/Y') }}</p>
    <p class="highlight">Duración: {{ $proceso->dias_duracion }}</p>
</div>

<!-- Resumen del pedido -->
<div class="pedido-summary">
    <p>Días totales: {{ $pedido->getTotalDias() }}</p>
    @if($pedido->estaEnRetraso())
        <p class="warning">
            ⚠️ En retraso: {{ $pedido->getDiasDeRetraso() }} días
        </p>
    @endif
</div>
```

### 3. En endpoints JSON:

```php
Route::get('/pedidos/{id}', function($id) {
    $pedido = PedidoProduccion::with(['prendas', 'procesos'])->find($id);
    
    return response()->json([
        'pedido' => $pedido,
        'dias' => [
            'total' => $pedido->getTotalDias(),
            'desglose' => $pedido->getDesgloseDiasPorProceso(),
            'en_retraso' => $pedido->estaEnRetraso(),
            'dias_retraso' => $pedido->getDiasDeRetraso(),
        ]
    ]);
});
```

## Festivos Configurables

Los festivos están definidos en `CalculadorDiasService::obtenerFestivos()`.

**Festivos fijos incluidos:**
- 1 de enero (Año Nuevo)
- 1 de mayo (Día del Trabajo)
- 1 de julio (Día de la Independencia)
- 20 de julio (Grito de Independencia)
- 7 de agosto (Batalla de Boyacá)
- 8 de diciembre (Inmaculada Concepción)
- 25 de diciembre (Navidad)

**Para agregar festivos movibles:**
1. Edita `CalculadorDiasService::obtenerFestivos()`
2. Agrega cálculos para Viernes Santo, Ascensión, etc.
3. Ejemplo con librería Carbon:

```php
// Viernes Santo 2025 (20 de abril)
$viernesSanto = $this->calcularViernesSanto($anio);
$festivos[] = $viernesSanto->toDateString();
```

## Migración de Datos Existentes

Se han actualizado los comandos de migración para que:
1. Creen procesos con fechas desde `tabla_original`
2. El modelo ProcesoPrenda calcule automáticamente `dias_duracion`

**Para re-migrar con cálculo correcto:**

```bash
php artisan procesos:calcular-dias --fix-all
```

## Casos de Uso

### 1. Dashboard con estadísticas de tiempos

```php
$pedidos = PedidoProduccion::with('procesos')->get();

$estadisticas = [
    'promedio_dias_pedido' => round(
        $pedidos->avg(fn($p) => $p->getTotalDiasNumero())
    ),
    'pedidos_en_retraso' => $pedidos->filter(fn($p) => $p->estaEnRetraso())->count(),
    'area_mas_lenta' => $this->calcularAreaMasLenta($pedidos),
];
```

### 2. Alertas de retraso

```php
$pedidosRetrasados = PedidoProduccion::all()
    ->filter(fn($p) => $p->estaEnRetraso())
    ->map(fn($p) => [
        'pedido' => $p->numero_pedido,
        'dias_retraso' => $p->getDiasDeRetraso(),
        'fecha_entrega_estimada' => $p->fecha_estimada_de_entrega,
    ]);
```

### 3. Reporte de productividad por área

```php
$desglose = $pedido->getDesgloseDiasPorProceso();

foreach ($desglose as $area => $dias) {
    $this->registrarProductividadArea($area, $dias);
}
```

## Notas Importantes

⚠️ **Diferencia con tabla_original:**
- `tabla_original` tenía múltiples campos (`dias_corte`, `dias_bordado`, etc.)
- El nuevo sistema tiene **un solo campo** (`dias_duracion`) que se reutiliza para cada proceso
- Los datos se organizan por **prenda** y **proceso**, no por pedido global

📌 **Caching de festivos:**
- Los festivos se cachean por año
- Si necesitas actualizar festivos sin reiniciar: `Cache::forget("festivos_{year}")`

📌 **Performance:**
- Use eager loading: `with(['procesos'])` para evitar N+1 queries
- Los cálculos se hacen en memoria cuando es posible
- El cálculo automático en el modelo es eficiente (se hace al guardar, no en cada lectura)

## Ejemplo Completo

```php
// 1. Crear un pedido
$pedido = PedidoProduccion::create([
    'numero_pedido' => 1001,
    'cliente_id' => 1,
    'user_id' => 1,
    'fecha_de_creacion_de_orden' => '2025-01-15',
    'fecha_estimada_de_entrega' => '2025-02-15',
]);

// 2. Crear prenda
$prenda = $pedido->prendas()->create([
    'nombre_prenda' => 'Camisa',
    'cantidad' => 100,
]);

// 3. Crear procesos
$prenda->procesos()->create([
    'proceso' => 'Corte',
    'fecha_inicio' => '2025-01-15',
    'fecha_fin' => '2025-01-20',
    'encargado' => 'Juan',
    'estado_proceso' => 'Completado',
    // dias_duracion se calcula automáticamente: "4 días"
]);

// 4. Consultar información
echo $pedido->getTotalDias();              // "25 días"
echo $prenda->procesos[0]->dias_duracion;  // "4 días"
echo $pedido->estaEnRetraso() ? 'Sí' : 'No'; // Verifica vs fecha_estimada
```
