# 🏗️ REFACTORIZACIÓN - CONTROLLERS A ARQUITECTURA DDD

**Fecha:** 10 de Diciembre de 2025
**Estado:** 📋 PLAN DE REFACTORIZACIÓN

---

## 🎯 OBJETIVO

Mover la lógica de los controllers HTTP a la arquitectura DDD del módulo de cotizaciones, siguiendo el patrón CQRS (Commands/Queries) ya establecido.

---

## 📊 ARQUITECTURA DDD ACTUAL

```
app/Application/Cotizacion/
├── Commands/                    (CQRS - Casos de uso)
│   ├── CrearCotizacionCommand
│   ├── CambiarEstadoCotizacionCommand
│   ├── AceptarCotizacionCommand
│   ├── EliminarCotizacionCommand
│   └── SubirImagenCotizacionCommand
│
├── Handlers/                    (Manejadores de comandos)
│   └── Commands/
│       ├── CrearCotizacionHandler
│       ├── CambiarEstadoCotizacionHandler
│       └── ...
│
├── Queries/                     (Consultas)
│   └── ...
│
├── DTOs/                        (Data Transfer Objects)
│   ├── CrearCotizacionDTO
│   └── CotizacionDTO
│
└── Services/                    (Servicios de aplicación)
    └── CrearCotizacionApplicationService
```

---

## 🔄 FLUJO DDD ACTUAL

```
HTTP Request
    ↓
Controller (HTTP)
    ↓
DTO (Validación)
    ↓
Command (CQRS)
    ↓
Handler (Orquestación)
    ↓
Domain Logic
    ↓
Repository (Persistencia)
    ↓
HTTP Response
```

---

## 📋 CONTROLLERS A REFACTORIZAR

### 1. CotizacionPrendaController
**Ubicación:** `app/Http/Controllers/CotizacionPrendaController.php`

**Métodos:**
- `create()` → Mostrar formulario
- `store()` → Crear cotización (usar CrearCotizacionCommand)
- `lista()` → Listar cotizaciones (usar Query)
- `edit()` → Mostrar edición
- `update()` → Actualizar (usar CambiarEstadoCotizacionCommand)
- `enviar()` → Enviar cotización (usar CambiarEstadoCotizacionCommand)
- `destroy()` → Eliminar (usar EliminarCotizacionCommand)

### 2. CotizacionBordadoController
**Ubicación:** `app/Http/Controllers/CotizacionBordadoController.php`

**Métodos:**
- Similar a CotizacionPrendaController pero para tipo L (Logo)

### 3. Otros Controllers
- `CotizacionController`
- `CotizacionEstadoController`
- `CotizacionesViewController`
- etc.

---

## 🔧 PATRÓN DE REFACTORIZACIÓN

### ANTES (Controller tradicional)
```php
class CotizacionPrendaController extends Controller
{
    public function store(Request $request)
    {
        $datos = $request->validated();
        $cotizacion = Cotizacion::create($datos);
        return response()->json(['success' => true]);
    }
}
```

### DESPUÉS (DDD con CQRS)
```php
class CotizacionPrendaController extends Controller
{
    public function __construct(
        private CrearCotizacionHandler $crearCotizacionHandler
    ) {}

    public function store(Request $request)
    {
        // 1. Crear DTO desde request
        $dto = CrearCotizacionDTO::desdeArray($request->all());

        // 2. Crear Command
        $command = CrearCotizacionCommand::crear($dto);

        // 3. Ejecutar Handler
        $cotizacion = $this->crearCotizacionHandler->handle($command);

        // 4. Retornar respuesta
        return response()->json([
            'success' => true,
            'cotizacion_id' => $cotizacion->id
        ]);
    }
}
```

---

## 📝 PASOS DE REFACTORIZACIÓN

### Paso 1: Crear DTOs necesarios
```
app/Application/Cotizacion/DTOs/
├── CrearCotizacionDTO ✅
├── ActualizarCotizacionDTO (CREAR)
├── EliminarCotizacionDTO (CREAR)
└── ListarCotizacionesDTO (CREAR)
```

### Paso 2: Crear Commands necesarios
```
app/Application/Cotizacion/Commands/
├── CrearCotizacionCommand ✅
├── CambiarEstadoCotizacionCommand ✅
├── AceptarCotizacionCommand ✅
├── EliminarCotizacionCommand ✅
└── SubirImagenCotizacionCommand ✅
```

### Paso 3: Crear Handlers necesarios
```
app/Application/Cotizacion/Handlers/Commands/
├── CrearCotizacionHandler (VERIFICAR)
├── CambiarEstadoCotizacionHandler (VERIFICAR)
├── EliminarCotizacionHandler (VERIFICAR)
└── ...
```

### Paso 4: Crear Queries (si es necesario)
```
app/Application/Cotizacion/Queries/
├── ObtenerCotizacionQuery (CREAR)
├── ListarCotizacionesQuery (CREAR)
└── ...
```

### Paso 5: Refactorizar Controllers
```
app/Http/Controllers/
├── CotizacionPrendaController (REFACTORIZAR)
├── CotizacionBordadoController (REFACTORIZAR)
├── CotizacionController (REFACTORIZAR)
└── ...
```

### Paso 6: Registrar en Service Provider
```
app/Infrastructure/Providers/CotizacionServiceProvider.php
- Registrar todos los Handlers
- Registrar todos los Queries
```

---

## 🎯 RESPONSABILIDADES

### Controller (HTTP)
- ✅ Recibir request
- ✅ Validar entrada
- ✅ Crear DTO
- ✅ Ejecutar comando/query
- ✅ Retornar respuesta HTTP

### DTO
- ✅ Transferir datos entre capas
- ✅ Validación de tipos
- ✅ Conversión desde/hacia array

### Command
- ✅ Encapsular intención del usuario
- ✅ Datos necesarios para ejecutar

### Handler
- ✅ Orquestar lógica de negocio
- ✅ Coordinar repositorios
- ✅ Manejar excepciones

### Domain
- ✅ Lógica de negocio pura
- ✅ Validaciones de dominio
- ✅ Reglas de negocio

---

## 📊 BENEFICIOS

| Aspecto | Beneficio |
|---------|-----------|
| **Testabilidad** | Fácil de testear sin HTTP |
| **Reutilización** | Handlers usables desde CLI, API, etc. |
| **Mantenibilidad** | Lógica separada y clara |
| **Escalabilidad** | Fácil agregar nuevos casos de uso |
| **Documentación** | Commands documentan intenciones |

---

## 🔗 REFERENCIAS

**Patrón CQRS:**
- Commands: Modifican estado
- Queries: Leen estado

**Patrón DDD:**
- Domain: Lógica de negocio
- Application: Casos de uso
- Infrastructure: Implementación técnica

---

## 📌 PRÓXIMOS PASOS

1. **Crear DTOs faltantes**
2. **Crear Queries faltantes**
3. **Refactorizar CotizacionPrendaController**
4. **Refactorizar CotizacionBordadoController**
5. **Refactorizar otros controllers**
6. **Actualizar Service Provider**
7. **Tests**

---

**Plan creado:** 10 de Diciembre de 2025
**Estado:** 📋 LISTO PARA IMPLEMENTACIÓN
