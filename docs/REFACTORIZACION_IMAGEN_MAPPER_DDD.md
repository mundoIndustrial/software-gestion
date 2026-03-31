#  REFACTORIZACIÓN ImagenMapperService - DDD & Clean Architecture

##RESUMEN EJECUTIVO

**Antes:** 1 monolítíco servicio con múltiples responsabilidades  
**Después:** 5 componentes especializados con responsabilidad única

---

##  PROBLEMAS ENCONTRADOS

### ❌ 1. Single Responsibility Principle VIOLADO

El `ImagenMapperService` original hacía:

```php
class ImagenMapperService {
    public function mapearImagenesPrenda(array $item): array      // ← Tarea 1
    public function mapearImagenesTelas(array $telas): array       // ← Tarea 2
    
    // Internamente también:
    // - Valida format de imágenes
    // - Decide qué hacer con previewUrl vs file vs string
    // - Procesa colores y telas (ColorTelaService)
    // - Construye arrays para la BD
}
```

**Impacto:** 
- Difícil de mantener
- Difícil de testear (necesita toda la estructura)
- Cambios en un formato afectan a todo

### ❌ 2. Duplicación de Lógica (Violación DRY)

Ambos métodos contenían lógica similar pero no reutilizable:

```php
// En mapearImagenesPrenda()
if (isset($imagen['previewUrl'])) { ... }
elseif (isset($imagen['file'])) { ... }
elseif (is_string($imagen)) { ... }

// En mapearImagenesTelas() - EXACTO IGUAL
if (isset($imagen['file'])) { ... }
elseif (is_array($imagen) && isset($imagen['nombre'])) { ... }
elseif (is_string($imagen)) { ... }
```

**Impacto:**
- Cambiar la lógica de imágenes requiere cambios en 2 lugares
- Bug fixes en un lugar no se aplican al otro

### ❌ 3. Lógica de Negocio Mezclada (Violación DDD)

```php
// En mapearImagenesTelas()
$colorTelaIds = $this->colorTelaService->procesarTela($tela);  // ← Domain
```

La decisión "cómo procesar telas" es lógica de negocio del Domain, pero está mezclada:

```
Infrastructure Mapper + Domain Logic = Confusión de responsabilidades
```

### ❌ 4. Falta de Tipos y Estructura

```php
public function mapearImagenesPrenda(array $item): array  // ← Qué estructura espera $item?
```

Imposible saber:
- Qué keys debe tener el array?
- Qué valores son válidos?
- Cuáles fields son opcionales?

**Impacto:** Errores en runtime, documentación fuera de sync.

### ❌ 5. Sin Validación (Factory Method Falta)

```php
foreach ($imagenes as $idx => $imagen) {
    // SI la imagen no encaja en ningún formato, se ignora silenciosamente
    // No hay error
    // No hay logging claro
    // No hay estructura definida
}
```

**Impacto:** Imágenes perdidas sin avisar al usuario.

### ❌ 6. Testing Difícil

```php
// Para testear este servicio necesitarías:
$service = new ImagenMapperService(
    new ColorTelaService(),  // ← Necesita BD
    // ...
);

// El test no es unitario, es de integración
$result = $service->mapearImagenesTelas([
    // ← Necesita estructura exacta
]);
```

---

##  SOLUCIÓN: REFACTORIZACIÓN COMPLETA

###  NUEVA ARQUITECTURA

```
app/
├── Domain/Pedidos/ValueObjects/
│   ├── ImagenPrenda.php           ← VO: validación + estructura
│   └── ImagenTela.php             ← VO: validación + estructura
│
└── Infrastructure/
    ├── Mappers/Imagenes/
    │   ├── ImagenDTOToPrendaArrayMapper.php        ← Convierte VO → array
    │   ├── ImagenDTOToTelaArrayMapper.php          ← Convierte VO → array
    │   ├── PrendaImagenesMapper.php                ← Orquesta prendas
    │   └── TelaImagenesMapper.php                  ← Orquesta telas
    └── Services/Pedidos/
        ├── ImagenesService.php                     ← Fachada pública
        └── ImagenMapperService.php                 ← DEPRECATED (puente)
```

---

## 💡 CÓMO FUNCIONA LA NUEVA SOLUCIÓN

### 1️⃣ Value Objects (Domain Layer)

```php
// Domain/Pedidos/ValueObjects/ImagenPrenda.php

class ImagenPrenda {
    //  VENTAJAS:
    // 1. Valida que la imagen sea válida
    // 2. Estructura clara: qué fields tiene
    // 3. Factory methods para cada tipo
    // 4. Self-documenting
    
    public static function from($imagen, int $orden): self {
        // Detecta automáticamente qué tipo es
        // Crea VO con valores validados
    }
    
    public function toArray(): array {
        // VO sabe cómo convertirse a array
    }
}
```

**Flujo:**
```
Array crudo → ImagenPrenda::from() → Validación + Estructura → VO
```

### 2️⃣ DTO-To-Array Mappers (Infrastructure)

```php
// Infrastructure/Mappers/Imagenes/ImagenDTOToPrendaArrayMapper.php

class ImagenDTOToPrendaArrayMapper {
    //  RESPONSABILIDAD ÚNICA:
    // Convertir ImagenPrenda (VO) → array para BD
    
    public function mapear(ImagenPrenda $imagen): array {
        // Solo transforma, no valida
        return $imagen->toArray();
    }
}
```

**Flujo:**
```
ImagenPrenda (VO) → mapper.mapear() → array para BD
```

### 3️⃣ Mapper Orquestadores (Infrastructure)

```php
// Infrastructure/Mappers/Imagenes/PrendaImagenesMapper.php

class PrendaImagenesMapper {
    //  RESPONSABILIDAD ÚNICA:
    // Orquestar mapeo de MÚLTIPLES imágenes
    
    public function mapear(array $imagenes): array {
        foreach ($imagenes as $idx => $imagen) {
            // 1. Crear VO (valida)
            $vo = ImagenPrenda::from($imagen, $idx + 1);
            
            // 2. Transformar a array
            $array = $this->mapper->mapear($vo);
            
            // 3. Agregar a resultado
        }
    }
}
```

**Flujo:**
```
Array de imágenes crudo
    → foreach imagen
    → ImagenPrenda::from() [valida]
    → mapper.mapear() [transforma]
    → array para BD
```

### 4️⃣ Fachada Pública (Infrastructure)

```php
// Infrastructure/Services/Pedidos/ImagenesService.php

class ImagenesService {
    //  Interfaz pública simple
    // El resto de la app solo ve esto
    
    public function mapearImagenesPrenda(array $item): array
    public function mapearImagenesTelas(array $telas): array
}
```

---

##  COMPARATIVA: ANTES vs DESPUÉS

### ❌ ANTES (Monolítíco)
```
Frontend Data
    ↓
ImagenMapperService::mapearImagenesPrenda($item)
    ↓
  [Validación]
  [Decisión formato]
  [Transformación]  ← Todo aquí
  [Logging]
    ↓
Array para BD
```

**Problemas:**
- Difícil de testear (todas las dependencias)
- Difícil de cambiar (afecta ambos métodos)
- Difícil de reutilizar (lógica no aislada)

###  DESPUÉS (Separado por responsabilidad)
```
Frontend Data
    ↓
ImagenPrenda::from() {valida + estructura}
    ↓
ImagenDTOToPrendaArrayMapper::mapear() {solo transforma}
    ↓
Array para BD
```

**Beneficios:**
-  Fácil de testear (pequeñas unidades)
-  Fácil de cambiar (responsabilidad aislada)
-  Fácil de reutilizar (componentes aislados)
-  Fácil de extender (agregar nuevos mappers)

---

##  EJEMPLOS DE USO

### Opción 1: Usar ImagenesService (RECOMENDADO)
```php
// En Application UseCase o Controller

$imagenesService = app(ImagenesService::class);

// Mapear imágenes de prenda
$fotosFormateadas = $imagenesService->mapearImagenesPrenda([
    'imagenes' => [
        ['previewUrl' => 'blob:...', 'nombre' => 'roja.png', 'tamano' => 2048],
        ['/storage/existing.webp']
    ]
]);

// Mapear imágenes de telas
$telasFormateadas = $imagenesService->mapearImagenesTelas([
    [
        'tela' => 'Algodón',
        'color' => 'Rojo',
        'referencia' => 'ALG-001',
        'imagenes' => [...]
    ]
]);
```

### Opción 2: Usar Mappers directamente (AVANZADO)
```php
// Si necesitas más control

$prendaMapper = app(PrendaImagenesMapper::class);
$imagenVO = ImagenPrenda::fromPreviewUrl([...], 1);
$array = $prendaMapper->mapear([$imagenVO]);
```

### Opción 3: Value Object directamente (TESTING)
```php
// En tests unitarios

$imagenVO = ImagenPrenda::from([
    'previewUrl' => 'blob:...',
    'nombre' => 'test.png',
    'tamano' => 1024
], 1);

$this->assertTrue($imagenVO->esPreview());
$this->assertEquals('blob:...', $imagenVO->previewUrl);
```

---

##  VENTAJAS PARA TESTING

### ❌ Antes (Difícil)
```php
public function testMapearPrenda() {
    $service = new ImagenMapperService(
        new ColorTelaServiceFake(),  // ← Necesita mock
    );
    
    $result = $service->mapearImagenesPrenda([...]);
    
    // No es realmente unitario
}
```

###  Después (Fácil)
```php
public function testImagenPrendaFromPreviewUrl() {
    $imagen = ImagenPrenda::fromPreviewUrl([
        'previewUrl' => 'blob:...',
        'nombre' => 'test.png',
        'tamano' => 1024
    ], 1);
    
    $this->assertTrue($imagen->esPreview());
    $this->assertEquals('test.png', $imagen->nombre);
    $this->assertEquals(1024, $imagen->tamano);
}

public function testImagenDTOToPrendaArrayMapper() {
    $mapper = new ImagenDTOToPrendaArrayMapper();
    
    $imagen = ImagenPrenda::from('blob:...', 1);
    $array = $mapper->mapear($imagen);
    
    $this->assertIsArray($array);
    $this->assertArrayHasKey('ruta_original', $array);
}

public function testPrendaImagenesMapper() {
    $mapper = new PrendaImagenesMapper(
        new ImagenDTOToPrendaArrayMapper()
    );
    
    $result = $mapper->mapear([
        ['previewUrl' => 'blob:...', 'nombre' => 'test.png']
    ]);
    
    $this->assertCount(1, $result);
}
```

**Ventajas:**
-  Cada test es pequeño y aislado
-  No necesita dependencias complejas
-  Fácil de entender qué se está testando
-  Rápido de ejecutar

---

##COMPARATIVA DE PRINCIPIOS SOLID

| Principio | Antes | Después |
|-----------|-------|---------|
| S (Single Responsibility) | ❌ Múltiples |  Una cada clase |
| O (Open/Closed) | ❌ Cerrado a cambios |  Abierto a extensión |
| L (Liskov Substitution) | ❌ N/A |  Interfaces claras |
| I (Interface Segregation) | ❌ Métodos gordos |  Métodos pequeños |
| D (Dependency Inversion) | ❌ Acoplado a servicios |  Inyección de deps |

---

##  PRÓXIMOS PASOS

### 1. Registrar en Service Provider
```php
// app/Providers/AppServiceProvider.php

public function register() {
    $this->app->singleton(ImagenesService::class, function ($app) {
        return new ImagenesService(
            new PrendaImagenesMapper(
                new ImagenDTOToPrendaArrayMapper()
            ),
            new TelaImagenesMapper(
                new ImagenDTOToTelaArrayMapper(),
                $app->make(ColorTelaService::class)
            ),
        );
    });
}
```

### 2. Migrar código existente
```php
// OLD (ImagenMapperService)
$mapper = app(ImagenMapperService::class);
$result = $mapper->mapearImagenesPrenda($item);

// NEW (ImagenesService)
$service = app(ImagenesService::class);
$result = $service->mapearImagenesPrenda($item);
```

### 3. Eliminar ImagenMapperService (cuando todos migren)
Por ahora está como puente de compatibilidad.

### 4. Crear más Value Objects si es necesario
```php
// Ejemplos:
- ImagenProcesoProduccion
- ImagenRecibo
- ImagenLogo
```

---

## 📚 REFERENCIA ARQUITECTÓNICA

Esta refactorización sigue:

- **DDD:** Value Objects (`ImagenPrenda`, `ImagenTela`)
- **Clean Architecture:** Separación de capas (Domain/Infrastructure)
- **SOLID:** Single Responsibility (cada clase hace una cosa)
- **Hexagonal:** Mappers aislados, fachada pública
- **Factory Pattern:** `ImagenPrenda::from()`
- **Facade Pattern:** `ImagenesService`

---

## ✨ RESUMEN DE BENEFICIOS

| Aspecto | Beneficio |
|---------|-----------|
| **Mantenibilidad** |  Cada componente es independiente |
| **Testabilidad** |  Tests unitarios simples y rápidos |
| **Extensibilidad** |  Agregar nuevos mappers = una nueva clase |
| **Readabilidad** |  Código autodocumentado con VOs y names claros |
| **Reusabilidad** |  ImagenPrenda se puede usar en cualquier lado |
| **Type Safety** |  Value Objects garantizan estructura |
| **Error Handling** |  Errores detectados temprano en VOs |
| **Performance** |  Sin cambios (mismo número de operaciones) |

