# Guía de Implementación - Servicios de Prendas

## ✅ Completado

- [x] Estructura de carpetas
- [x] DTOs (CrearPrendaDTO, ImagenDTO, TelaDTO, VarianteDTO, TallaDTO)
- [x] Enums (TipoPrendaEnum)

## 📝 Próximos Pasos

### 1. Crear ImagenProcesadorService

**Ubicación:** `app/Application/Services/ImagenProcesadorService.php`

**Responsabilidades:**
- Validar formato de imagen (JPEG, PNG, WebP)
- Convertir a WebP usando Intervention Image
- Guardar en Storage (`storage/app/public/prendas/{id}/fotos/`)
- Generar miniaturas
- Retornar rutas finales

**Métodos:**
```php
public function procesarImagen(ImagenDTO $imagen, int $prendaId): string
public function procesarImagenTela(ImagenDTO $imagen, int $prendaId): string
public function validarFormato(UploadedFile $archivo): bool
public function convertirAWebP(UploadedFile $archivo): string
public function guardarEnStorage(string $contenidoWebP, string $ruta): string
public function generarMiniatura(string $rutaWebP): string
```

### 2. Crear TipoPrendaDetectorService

**Ubicación:** `app/Application/Services/TipoPrendaDetectorService.php`

**Responsabilidades:**
- Detectar tipo de prenda por nombre
- Validar tipo
- Crear tipos automáticamente si no existen

**Métodos:**
```php
public function detectar(string $nombrePrenda): TipoPrendaEnum
public function validar(string $tipo): bool
public function crearSiNoExiste(TipoPrendaEnum $tipo): TipoPrenda
```

### 3. Crear ColorGeneroMangaBrocheService

**Ubicación:** `app/Application/Services/ColorGeneroMangaBrocheService.php`

**Responsabilidades:**
- Crear/buscar colores
- Crear/buscar géneros
- Crear/buscar mangas
- Crear/buscar broches

**Métodos:**
```php
public function obtenerOCrearColor(string $nombre): ColorPrenda
public function obtenerOCrearGenero(string $nombre): GeneroPrenda
public function obtenerOCrearManga(int $id): TipoManga
public function obtenerOCrearBroche(int $id): TipoBroche
```

### 4. Crear PrendaTelasService

**Ubicación:** `app/Application/Services/PrendaTelasService.php`

**Responsabilidades:**
- Registrar telas múltiples
- Crear/buscar telas
- Crear/buscar colores
- Actualizar referencias

**Métodos:**
```php
public function registrarTelas(int $varianteId, array $telas): void
public function obtenerOCrearTela(TelaDTO $telaDTO): TelaPrenda
public function procesarFotoTela(TelaDTO $telaDTO, int $prendaId): ?string
```

### 5. Crear PrendaVariantesService

**Ubicación:** `app/Application/Services/PrendaVariantesService.php`

**Responsabilidades:**
- Crear variantes
- Actualizar variantes
- Registrar tallas
- Gestionar atributos

**Métodos:**
```php
public function crear(int $prendaId, VarianteDTO $varianteDTO): PrendaVariante
public function registrarTallas(int $prendaId, array $tallas): void
public function actualizar(int $varianteId, VarianteDTO $varianteDTO): PrendaVariante
```

### 6. Crear PrendaService

**Ubicación:** `app/Application/Services/PrendaService.php`

**Responsabilidades:**
- Crear prenda
- Actualizar prenda
- Eliminar prenda
- Obtener prenda por ID

**Métodos:**
```php
public function crear(CrearPrendaDTO $dto): Prenda
public function actualizar(int $id, CrearPrendaDTO $dto): Prenda
public function eliminar(int $id): bool
public function obtenerPorId(int $id): Prenda
```

### 7. Crear CatalogoPrendaService

**Ubicación:** `app/Application/Services/CatalogoPrendaService.php`

**Responsabilidades:**
- Generar catálogos
- Exportar datos
- Generar reportes

**Métodos:**
```php
public function generarCatalogo(int $prendaId): array
public function exportarPDF(int $prendaId): string
public function generarReporte(array $filtros): array
```

### 8. Crear ProcessPrendaImagenesJob

**Ubicación:** `app/Infrastructure/Jobs/ProcessPrendaImagenesJob.php`

**Responsabilidades:**
- Procesar imágenes de forma asincrónica
- Validar, convertir y guardar
- Actualizar modelo con rutas finales

**Métodos:**
```php
public function handle(ImagenProcesadorService $procesador): void
```

### 9. Crear CrearPrendaAction

**Ubicación:** `app/Application/Actions/CrearPrendaAction.php`

**Responsabilidades:**
- Orquestar la creación de prenda
- Coordinar servicios
- Disparar eventos

**Métodos:**
```php
public function ejecutar(CrearPrendaDTO $dto): Prenda
```

### 10. Crear PrendaController

**Ubicación:** `app/Http/Controllers/PrendaController.php`

**Responsabilidades:**
- Recibir peticiones HTTP
- Validar requests
- Llamar a actions
- Retornar responses

**Métodos:**
```php
public function store(CrearPrendaRequest $request): JsonResponse
public function show(int $id): JsonResponse
public function update(int $id, ActualizarPrendaRequest $request): JsonResponse
public function destroy(int $id): JsonResponse
public function index(): JsonResponse
```

---

## 🗂️ Estructura de Carpetas a Crear

```
app/
├── Application/
│   ├── DTOs/
│   │   ├── CrearPrendaDTO.php ✅
│   │   ├── ImagenDTO.php ✅
│   │   ├── TelaDTO.php ✅
│   │   ├── VarianteDTO.php ✅
│   │   └── TallaDTO.php ✅
│   ├── Services/
│   │   ├── PrendaService.php
│   │   ├── PrendaVariantesService.php
│   │   ├── PrendaTelasService.php
│   │   ├── TipoPrendaDetectorService.php
│   │   ├── ImagenProcesadorService.php
│   │   ├── CatalogoPrendaService.php
│   │   └── ColorGeneroMangaBrocheService.php
│   ├── Actions/
│   │   ├── CrearPrendaAction.php
│   │   ├── ProcesarImagenesAction.php
│   │   └── GenerarCatalogoAction.php
│   └── Queries/
│       ├── ObtenerPrendaQuery.php
│       └── ListarPrendasQuery.php
├── Enums/
│   ├── TipoPrendaEnum.php ✅
│   ├── EstadoPrendaEnum.php
│   └── TipoImagenEnum.php
├── Infrastructure/
│   ├── Jobs/
│   │   └── ProcessPrendaImagenesJob.php
│   ├── Repositories/
│   │   ├── EloquentPrendaRepository.php
│   │   └── EloquentPrendaVarianteRepository.php
│   └── Providers/
│       └── PrendaServiceProvider.php
└── Http/
    ├── Controllers/
    │   └── PrendaController.php
    ├── Requests/
    │   ├── CrearPrendaRequest.php
    │   └── ActualizarPrendaRequest.php
    └── Resources/
        ├── PrendaResource.php
        └── PrendaColeccionResource.php
```

---

## 🔧 Instalación de Dependencias

```bash
# Intervention Image para procesar imágenes
composer require intervention/image

# Spatie para manejo de archivos
composer require spatie/laravel-medialibrary

# Para eventos de dominio
composer require spatie/laravel-event-sourcing
```

---

## 📋 Migraciones a Crear

```bash
php artisan make:migration create_prendas_table
php artisan make:migration create_prenda_variantes_table
php artisan make:migration create_prenda_tallas_table
php artisan make:migration create_prenda_fotos_table
php artisan make:migration create_prenda_telas_cotizacion_table
```

---

## 🚀 Próximos Pasos

1. **Crear ImagenProcesadorService** (crítico para procesamiento de imágenes)
2. **Crear TipoPrendaDetectorService** (necesario para detectar tipos)
3. **Crear ColorGeneroMangaBrocheService** (para atributos)
4. **Crear PrendaTelasService** (para telas múltiples)
5. **Crear PrendaVariantesService** (para variantes)
6. **Crear PrendaService** (servicio principal)
7. **Crear ProcessPrendaImagenesJob** (procesamiento async)
8. **Crear CrearPrendaAction** (orquestación)
9. **Crear PrendaController** (HTTP)
10. **Crear Migraciones** (BD)
11. **Crear Tests** (validación)

---

## 📝 Notas Importantes

- Todos los servicios deben ser inyectados por dependencia
- Las imágenes se procesan de forma asincrónica con Jobs
- Los DTOs transforman datos de entrada
- Los Value Objects encapsulan lógica de dominio
- Los Enums reemplazan strings mágicos
- Los Repositories abstraen la persistencia
- Las Actions orquestan múltiples servicios

