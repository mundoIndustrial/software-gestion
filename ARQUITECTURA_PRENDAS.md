# Arquitectura Limpia - Módulo de Gestión de Prendas

## 📋 Índice
1. Estructura de Carpetas
2. Flujo de Datos
3. Servicios Especializados
4. DTOs y Value Objects
5. Jobs y Acciones
6. Migraciones de Base de Datos
7. Rutas API
8. Ejemplos Frontend
9. Respuestas JSON

---

## 1. Estructura de Carpetas

```
app/
├── Domain/
│   ├── Prendas/
│   │   ├── Models/
│   │   │   ├── Prenda.php
│   │   │   ├── PrendaVariante.php
│   │   │   ├── PrendaTalla.php
│   │   │   ├── PrendaFoto.php
│   │   │   └── PrendaTela.php
│   │   ├── ValueObjects/
│   │   │   ├── PrendaId.php
│   │   │   ├── RutaImagen.php
│   │   │   ├── DimensionesImagen.php
│   │   │   └── TipoPrendaEnum.php
│   │   ├── Repositories/
│   │   │   ├── PrendaRepository.php
│   │   │   ├── PrendaVarianteRepository.php
│   │   │   └── PrendaTalaRepository.php
│   │   └── Events/
│   │       ├── PrendaCreada.php
│   │       └── ImagenesProc esadas.php
│
├── Application/
│   ├── DTOs/
│   │   ├── CrearPrendaDTO.php
│   │   ├── ImagenDTO.php
│   │   ├── TelaDTO.php
│   │   ├── VarianteDTO.php
│   │   └── TallaDTO.php
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
│
├── Infrastructure/
│   ├── Jobs/
│   │   └── ProcessPrendaImagenesJob.php
│   ├── Repositories/
│   │   ├── EloquentPrendaRepository.php
│   │   └── EloquentPrendaVarianteRepository.php
│   └── Providers/
│       └── PrendaServiceProvider.php
│
├── Http/
│   ├── Controllers/
│   │   └── PrendaController.php
│   ├── Requests/
│   │   ├── CrearPrendaRequest.php
│   │   └── ActualizarPrendaRequest.php
│   └── Resources/
│       ├── PrendaResource.php
│       └── PrendaColeccionResource.php
│
└── Enums/
    ├── TipoPrendaEnum.php
    ├── EstadoPrendaEnum.php
    └── TipoImagenEnum.php

storage/
└── app/
    └── public/
        └── prendas/
            ├── {prenda_id}/
            │   ├── fotos/
            │   │   ├── foto_1.webp
            │   │   └── foto_2.webp
            │   └── telas/
            │       ├── tela_1.webp
            │       └── tela_2.webp
```

---

## 2. Flujo de Datos

```
Frontend (FormData)
    ↓
PrendaController::store()
    ↓
CrearPrendaRequest (validación)
    ↓
CrearPrendaDTO (transformación)
    ↓
CrearPrendaAction (orquestación)
    ├→ PrendaService (crear prenda)
    ├→ PrendaVariantesService (variantes)
    ├→ PrendaTelasService (telas)
    ├→ TipoPrendaDetectorService (detectar tipo)
    ├→ ColorGeneroMangaBrocheService (atributos)
    └→ ProcessPrendaImagenesJob (imágenes async)
        └→ ImagenProcesadorService (validar, convertir, guardar)
    ↓
PrendaResource (respuesta JSON)
    ↓
Frontend (JSON)
```

---

## 3. Servicios Especializados

### PrendaService
- Crear prenda
- Actualizar prenda
- Eliminar prenda
- Obtener prenda por ID

### PrendaVariantesService
- Crear variantes
- Actualizar variantes
- Registrar tallas
- Gestionar atributos (manga, broche, bolsillos, reflectivo)

### PrendaTelasService
- Registrar telas múltiples
- Crear/buscar telas
- Crear/buscar colores
- Actualizar referencias

### TipoPrendaDetectorService
- Detectar tipo por nombre
- Validar tipo
- Crear tipos automáticamente

### ImagenProcesadorService
- Validar formato (JPEG, PNG, WebP)
- Convertir a WebP
- Guardar en Storage
- Generar miniaturas
- Retornar rutas

### CatalogoPrendaService
- Generar catálogos
- Exportar datos
- Generar reportes

### ColorGeneroMangaBrocheService
- Crear/buscar colores
- Crear/buscar géneros
- Crear/buscar mangas
- Crear/buscar broches

---

## 4. DTOs

### CrearPrendaDTO
```php
class CrearPrendaDTO {
    public string $nombre_producto;
    public string $descripcion;
    public string $tipo_prenda; // CAMISA, PANTALON, etc
    public array $tallas; // ['XS', 'S', 'M', ...]
    public array $variantes; // VarianteDTO[]
    public array $telas; // TelaDTO[]
    public array $fotos; // ImagenDTO[]
    public ?string $genero;
}
```

### ImagenDTO
```php
class ImagenDTO {
    public UploadedFile $archivo;
    public string $tipo; // 'foto_prenda', 'foto_tela'
    public int $orden;
}
```

### TelaDTO
```php
class TelaDTO {
    public string $nombre;
    public string $referencia;
    public string $color;
    public ?UploadedFile $foto;
}
```

---

## 5. Jobs

### ProcessPrendaImagenesJob
- Procesa imágenes de forma asincrónica
- Valida, convierte y guarda
- Actualiza modelo con rutas finales

---

## 6. Migraciones

### Tabla: prendas
```sql
CREATE TABLE prendas (
    id BIGINT PRIMARY KEY,
    nombre_producto VARCHAR(255),
    descripcion TEXT,
    tipo_prenda_id BIGINT,
    genero_id BIGINT,
    estado ENUM('activo', 'inactivo'),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Tabla: prenda_variantes
```sql
CREATE TABLE prenda_variantes (
    id BIGINT PRIMARY KEY,
    prenda_id BIGINT,
    tipo_manga_id BIGINT,
    tipo_broche_id BIGINT,
    tiene_bolsillos BOOLEAN,
    tiene_reflectivo BOOLEAN,
    descripcion_adicional TEXT,
    created_at TIMESTAMP
);
```

### Tabla: prenda_tallas
```sql
CREATE TABLE prenda_tallas (
    id BIGINT PRIMARY KEY,
    prenda_id BIGINT,
    talla VARCHAR(50),
    cantidad INT,
    created_at TIMESTAMP
);
```

### Tabla: prenda_fotos
```sql
CREATE TABLE prenda_fotos (
    id BIGINT PRIMARY KEY,
    prenda_id BIGINT,
    ruta_original VARCHAR(500),
    ruta_webp VARCHAR(500),
    tipo ENUM('prenda', 'tela'),
    orden INT,
    created_at TIMESTAMP
);
```

### Tabla: prenda_telas_cotizacion
```sql
CREATE TABLE prenda_telas_cotizacion (
    id BIGINT PRIMARY KEY,
    variante_prenda_id BIGINT,
    color_id BIGINT,
    tela_id BIGINT,
    created_at TIMESTAMP
);
```

---

## 7. Rutas API

```php
Route::apiResource('prendas', PrendaController::class);

// Específicas
Route::post('prendas/{id}/procesar-imagenes', [PrendaController::class, 'procesarImagenes']);
Route::get('prendas/{id}/catalogo', [PrendaController::class, 'generarCatalogo']);
Route::post('prendas/detectar-tipo', [PrendaController::class, 'detectarTipo']);
```

---

## 8. Ejemplo Frontend (React + Vite)

```javascript
const crearPrenda = async (datos) => {
    const formData = new FormData();
    
    // Datos básicos
    formData.append('nombre_producto', datos.nombre);
    formData.append('descripcion', datos.descripcion);
    formData.append('tipo_prenda', datos.tipo);
    formData.append('genero', datos.genero);
    
    // Tallas
    datos.tallas.forEach((talla, idx) => {
        formData.append(`tallas[${idx}]`, talla);
    });
    
    // Variantes
    datos.variantes.forEach((variante, idx) => {
        formData.append(`variantes[${idx}][tipo_manga_id]`, variante.manga);
        formData.append(`variantes[${idx}][tipo_broche_id]`, variante.broche);
        formData.append(`variantes[${idx}][tiene_bolsillos]`, variante.bolsillos);
        formData.append(`variantes[${idx}][tiene_reflectivo]`, variante.reflectivo);
    });
    
    // Telas
    datos.telas.forEach((tela, idx) => {
        formData.append(`telas[${idx}][nombre]`, tela.nombre);
        formData.append(`telas[${idx}][referencia]`, tela.referencia);
        formData.append(`telas[${idx}][color]`, tela.color);
        if (tela.foto) {
            formData.append(`telas[${idx}][foto]`, tela.foto);
        }
    });
    
    // Fotos
    datos.fotos.forEach((foto, idx) => {
        formData.append(`fotos[${idx}][archivo]`, foto.archivo);
        formData.append(`fotos[${idx}][tipo]`, foto.tipo);
        formData.append(`fotos[${idx}][orden]`, idx);
    });
    
    const response = await fetch('/api/prendas', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    });
    
    return response.json();
};
```

---

## 9. Respuesta JSON

```json
{
    "success": true,
    "data": {
        "id": 1,
        "nombre_producto": "Camisa Drill",
        "descripcion": "Camisa de trabajo en drill",
        "tipo_prenda": "CAMISA",
        "genero": "dama",
        "tallas": ["XS", "S", "M", "L"],
        "variantes": [
            {
                "id": 1,
                "tipo_manga": "Corta",
                "tipo_broche": "Botón",
                "tiene_bolsillos": true,
                "tiene_reflectivo": false
            }
        ],
        "telas": [
            {
                "id": 1,
                "nombre": "Drill",
                "referencia": "DR-001",
                "color": "Azul",
                "foto": "/storage/prendas/1/telas/tela_1.webp"
            }
        ],
        "fotos": [
            {
                "id": 1,
                "ruta_original": "/storage/prendas/1/fotos/foto_1_original.jpg",
                "ruta_webp": "/storage/prendas/1/fotos/foto_1.webp",
                "tipo": "prenda",
                "orden": 1
            }
        ],
        "created_at": "2025-12-10T09:00:00Z"
    },
    "message": "Prenda creada exitosamente"
}
```

---

## 10. Principios Aplicados

### SOLID
- **S**ingle Responsibility: Cada servicio tiene una única responsabilidad
- **O**pen/Closed: Abierto para extensión, cerrado para modificación
- **L**iskov Substitution: Interfaces para servicios intercambiables
- **I**nterface Segregation: Interfaces específicas, no genéricas
- **D**ependency Inversion: Inyección de dependencias

### DDD
- **Entidades**: Prenda, PrendaVariante, PrendaTalla
- **Value Objects**: PrendaId, RutaImagen, DimensionesImagen
- **Agregados**: Prenda (raíz agregada)
- **Repositorios**: Abstracción de persistencia
- **Servicios de Dominio**: TipoPrendaDetectorService
- **Eventos de Dominio**: PrendaCreada, ImagenesProcessadas

### Arquitectura Hexagonal
- **Puertos**: Interfaces de repositorios
- **Adaptadores**: Implementaciones Eloquent
- **Casos de Uso**: Actions y Services
- **Entidades**: Domain Models

---

## 11. Ventajas de esta Arquitectura

✅ **Mantenibilidad**: Código organizado y fácil de entender
✅ **Escalabilidad**: Fácil agregar nuevas funcionalidades
✅ **Testabilidad**: Servicios desacoplados, fáciles de testear
✅ **Reutilización**: Servicios reutilizables en diferentes contextos
✅ **Independencia**: Lógica de negocio independiente del framework
✅ **Performance**: Procesamiento de imágenes asincrónico
✅ **Seguridad**: Validación en múltiples capas

---

## 12. Próximos Pasos

1. Crear la estructura de carpetas
2. Implementar DTOs
3. Implementar Value Objects
4. Implementar Servicios
5. Implementar Jobs
6. Crear Migraciones
7. Actualizar Controladores
8. Crear Tests
9. Documentar API
10. Desplegar a producción

