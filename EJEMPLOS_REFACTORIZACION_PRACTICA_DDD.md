# EJEMPLOS PRÁCTICOS: Refactorización DDD

## Ejemplo 1: Crear Orden

### ANTES (Code Smell)
```php
public function store(Request $request)
{
    return $this->tryExec(function() use ($request) {
        // Validación
        $validatedData = $this->validationService->validateStoreRequest($request);

        // Verificar numero consecutivo
        $nextPedido = $this->numberService->getNextNumber();
        
        if (!$request->input('allow_any_pedido', false)) {
            if ($request->pedido != $nextPedido) {
                throw RegistroOrdenPedidoNumberException::unexpectedNumber(
                    $nextPedido,
                    $request->pedido
                );
            }
        }

        // Crear orden
        $pedido = $this->creationService->createOrder($validatedData);

        // Registrar evento
        $this->creationService->logOrderCreated(
            $pedido->numero_pedido,
            $validatedData['cliente'],
            $validatedData['estado'] ?? 'Pendiente'
        );

        // Broadcast
        $this->creationService->broadcastOrderCreated($pedido);

        return response()->json([
            'success' => true,
            'message' => 'Orden registrada correctamente',
            'pedido' => $pedido->numero_pedido
        ]);
    });
}
```

### DESPUÉS (Clean)
```php
public function store(Request $request)
{
    return $this->tryExec(fn() => 
        $this->createOrderUseCase->execute($request, $request->input('allow_any_pedido', false))
    );
}
```

**Ventajas:**
- ✅ 3 líneas vs 30 líneas
- ✅ Un responsabilidad clara
- ✅ Lógica extraída al UseCase
- ✅ Reutilizable desde consola/eventos

---

## Ejemplo 2: Calcular Fecha Estimada

### ANTES (Lógica dispersa)
```php
private function calcularFechaEstimadaConDiasHabiles($fechaInicio, $diasHabiles)
{
    try {
        $fecha = Carbon::parse($fechaInicio);
        $diasAgregados = 0;

        // Obtener festivos del año actual y siguiente
        $currentYear = $fecha->year;
        $nextYear = $currentYear + 1;
        $festivos = array_merge(
            FestivosColombiaService::obtenerFestivos($currentYear),
            FestivosColombiaService::obtenerFestivos($nextYear)
        );

        // Convertir festivos a formato YYYY-MM-DD
        $festivosFormatted = array_map(function ($fechaFestivo) {
            return Carbon::parse($fechaFestivo)->format('Y-m-d');
        }, $festivos);

        // Sumar días hábiles
        while ($diasAgregados < $diasHabiles) {
            $fecha->addDay();

            // Verificar si es fin de semana
            $diaSemana = $fecha->dayOfWeek;
            $esFinde = ($diaSemana === 0 || $diaSemana === 6);

            // Verificar si es festivo
            $esFestivo = in_array($fecha->format('Y-m-d'), $festivosFormatted);

            // Si no es fin de semana ni festivo, contar como día hábil
            if (!$esFinde && !$esFestivo) {
                $diasAgregados++;
            }
        }

        return $fecha;

    } catch (\Exception $e) {
        \Log::error('Error calculando fecha estimada: ' . $e->getMessage());
        // Fallback: sumar días simples sin considerar festivos
        return Carbon::parse($fechaInicio)->addDays($diasHabiles);
    }
}
```

### DESPUÉS (Encapsulado en Service)
```php
// En Domain Service
class OrderCalculationService
{
    public function calcularFechaEstimada(Carbon $fechaInicio, int $diasHabiles): Carbon
    {
        $fecha = $fechaInicio->copy();
        $festivos = $this->obtenerFestivosFormateados(
            $fechaInicio->year, 
            $fechaInicio->addYears(1)->year
        );
        
        $diasAgregados = 0;
        while ($diasAgregados < $diasHabiles) {
            $fecha->addDay();
            
            $esFinde = $fecha->dayOfWeek === 0 || $fecha->dayOfWeek === 6;
            $esFestivo = isset($festivos[$fecha->format('Y-m-d')]);
            
            if (!$esFinde && !$esFestivo) {
                $diasAgregados++;
            }
        }
        
        return $fecha;
    }
}

// En UseCase
class SaveDiaEntregaUseCase
{
    public function execute(int $id, ?int $diaDeEntrega): array
    {
        if ($diaDeEntrega > 0) {
            $fechaInicio = $orden->fecha_de_creacion_de_orden ?? $orden->created_at;
            $fechaEstimada = $this->calculationService->calcularFechaEstimada(
                Carbon::parse($fechaInicio),
                $diaDeEntrega
            );
            $updateData['fecha_estimada_de_entrega'] = $fechaEstimada;
        }
        
        $orden->update($updateData);
        return ['success' => true];
    }
}
```

**Ventajas:**
- ✅ Lógica reutilizable en múltiples places
- ✅ Testeable de forma aislada
- ✅ Cambios centralizados
- ✅ Mejorada legibilidad

---

## Ejemplo 3: Filtrar Órdenes

### ANTES (Lógica mixta)
```php
public function filterOrders(Request $request)
{
    try {
        $filters = $request->input('filters', []);
        $page = $request->input('page', 1);
        $perPage = 25;

        $query = PedidoProduccion::query();

        // Aplicar filtros
        if (!empty($filters)) {
            foreach ($filters as $column => $values) {
                if (empty($values)) continue;

                switch ($column) {
                    case 'estado':
                        $query->whereIn('estado', $values);
                        break;
                    case 'area':
                        $query->whereIn('area', $values);
                        break;
                    case 'dia_entrega':
                        $dias = array_map(function($v) {
                            return (int) str_replace(' Dias', '', $v);
                        }, $values);
                        $query->whereIn('dia_de_entrega', $dias);
                        break;
                    // ... 20 cases más
                }
            }
        }

        // Obtener resultados
        $ordenes = $query->get();

        // Transformar datos
        $ordenesData = $ordenesPaginadas->map(function($orden) {
            return [...];
        });

        return response()->json([...]);
    } catch (\Exception $e) {
        \Log::error('Error al filtrar ordenes: ' . $e->getMessage());
    }
}
```

### DESPUÉS (Separación de concerns)
```php
// En Controller
public function filterOrders(Request $request)
{
    $result = $this->orderQueryService->filterOrders(
        $request->input('filters', []),
        $request->input('page', 1),
        25
    );

    return response()->json(['success' => true, ...$result]);
}

// En QueryService
class OrderQueryService
{
    public function filterOrders(array $filters, int $page = 1, int $perPage = 25): array
    {
        $query = $this->buildQuery();
        $this->applyFilterCriteria($query, $filters);
        
        $total = $query->count();
        $ordenes = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $this->formatOrdersForDisplay($ordenes->items()),
            'pagination' => $this->formatPagination($ordenes)
        ];
    }

    private function applyFilterCriteria(&$query, array $filters): void
    {
        if (isset($filters['estado'])) {
            $query->whereIn('estado', $filters['estado']);
        }
        
        if (isset($filters['area'])) {
            $query->whereIn('area', $filters['area']);
        }
        
        // ... filtros adicionales
    }
}
```

**Ventajas:**
- ✅ Controller < 3 líneas
- ✅ Lógica de query centralizada
- ✅ Fácil modificar criterios
- ✅ Reutilizable desde múltiples controllers

---

## Ejemplo 4: Value Objects para Estados

### ANTES (Tipo primitivo)
```php
// Sin validación
$estado = $request->input('estado'); // Podría ser cualquier string

$orden->update(['estado' => $estado]); // ❌ Sin validación

if ($orden->estado == 'En Ejecucion') { // ❌ String magic
    // ...
}
```

### DESPUÉS (Value Object)
```php
// Con validación
$estado = EntregaEstado::create($request->input('estado'));

// ✅ Lanza excepción si es inválido
$orden->update(['estado' => $estado->toString()]);

if ($estado->equals(EntregaEstado::EN_EJECUCION)) { // ✅ Type-safe
    // ...
}

// ✅ Métodos de lógica
if ($estado->isFinal()) {
    // Puede archivarse
}

// ✅ Obtener todos los estados válidos
$todosEstados = EntregaEstado::todos();
```

**Ventajas:**
- ✅ Validación en construcción
- ✅ Métodos de lógica en el value object
- ✅ Type-safe (no strings mágicos)
- ✅ Evita bugs por typos

---

## Ejemplo 5: Domain Service para Validaciones

### ANTES (Dispersa)
```php
// En múltiples lugares del controller
if ($dia < 1 || $dia > 35) {
    throw new Exception('Día inválido');
}

// En otro método
if (!($dia >= 1 && $dia <= 35)) {
    return false;
}

// En otro lado
if ($dia < 1 || $dia > 35) {
    $error = 'No es válido';
}
```

### DESPUÉS (Centralizada)
```php
class OrderFilteringService
{
    public function validarDiaEntrega(int $dia): bool
    {
        return $dia >= 1 && $dia <= 35;
    }

    public function validarFiltros(array $filtros): bool
    {
        $camposValidos = ['estado', 'area', 'dia_entrega', ...];
        
        foreach (array_keys($filtros) as $campo) {
            if (!in_array($campo, $camposValidos)) {
                return false;
            }
        }

        return true;
    }
}

// Uso
if (!$filteringService->validarDiaEntrega($dia)) {
    throw new InvalidArgumentException('Día inválido');
}
```

**Ventajas:**
- ✅ DRY (Don't Repeat Yourself)
- ✅ Cambios centralizados
- ✅ Testeable
- ✅ Documentación clara

---

## Resumen de Patrones

| Patrón | Caso de Uso | Ventaja |
|--------|-----------|---------|
| **UseCase** | Cada operación importante | Reutilizable, testeable |
| **Value Object** | Estados, números especiales | Type-safe, validación |
| **Domain Service** | Lógica pura reutilizable | Sin acoplamiento a BD |
| **Query Service** | Queries complejas | Centralizadas, reutilizables |
| **Repository** | Acceso a datos | Abstracta la BD |

