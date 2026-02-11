---
description: Estructura DDD del módulo Visualizador Logo (Pedidos Logo)
---

# DDD - Módulo `visualizador-logo/pedidos-logo`

Este documento describe la estructuración **DDD (opción B / más estricta)** aplicada al módulo de **Visualización de Logo → Pedidos Logo**, manteniendo las rutas existentes.

## Objetivos

- Separar **Dominio / Casos de Uso / Infraestructura**.
- Evitar reglas duplicadas en Controller/JS.
- Mantener URLs actuales:
  - `GET /visualizador-logo/pedidos-logo/data`
  - `POST /visualizador-logo/pedidos-logo/area-novedad`
  - `GET|POST|DELETE /visualizador-logo/pedidos-logo/disenos`

## Estructura por carpetas

### 1) Domain

`app/Domain/PedidosLogo/`

- `Enums/`
  - `AreaProcesoLogo.php`
    - Enum “central” de áreas posibles.

- `Policies/`
  - `AreasPermitidasPolicy.php`
    - Regla del negocio: **qué áreas se permiten por sección** (filtro `bordado` vs `estampado`).

- `Repositories/`
  - `ProcesoPrendaDetalleReadRepositoryInterface.php`
    - Contrato para lectura/paginación de recibos y validaciones de pertenencia.
  - `SeguimientoAreaRepositoryInterface.php`
    - Contrato para leer/escribir seguimiento (`prenda_areas_logo_pedido`).
  - `DisenoLogoPedidoRepositoryInterface.php`
    - Contrato para CRUD de `disenos_logo_pedido`.
  - `LogoDesignStorageInterface.php`
    - Contrato para eliminar archivos de diseños a partir de URL/ruta.

### 2) Application

`app/Application/PedidosLogo/UseCases/`

- `ListPedidosLogoUseCase.php`
  - Caso de uso para listar recibos (paginación + filtros + normalización de respuesta).

- `GuardarAreaNovedadPedidoLogoUseCase.php`
  - Caso de uso transaccional:
    - valida payload
    - infiere sección (bordado/estampado) por `tipo_proceso_id`
    - aplica `AreasPermitidasPolicy`
    - hace upsert y gestiona `fechas_areas`

- Diseños adjuntos:
  - `ListDisenosLogoPedidoUseCase.php`
  - `UploadDisenosLogoPedidoUseCase.php`
  - `DeleteDisenoLogoPedidoUseCase.php`

### 3) Infrastructure

#### Controllers (adaptadores HTTP)

`app/Infrastructure/Http/Controllers/VisualizadorLogo/`

- `PedidosLogoController.php`
  - `data()` → usa `ListPedidosLogoUseCase`
  - `guardarAreaNovedad()` → usa `GuardarAreaNovedadPedidoLogoUseCase`

- `DisenosLogoPedidoController.php`
  - `index()` / `store()` / `destroy()` → delega a UseCases

#### Persistencia

`app/Infrastructure/Persistence/Eloquent/PedidosLogo/`

- `ProcesoPrendaDetalleReadRepository.php`
- `SeguimientoAreaRepository.php`
- `DisenoLogoPedidoRepository.php`

#### Storage

`app/Infrastructure/Storage/PedidosLogo/`

- `LogoDesignStorage.php`

#### Bindings DI

`app/Infrastructure/Providers/PedidosLogoServiceProvider.php`

- Registra bindings:
  - `ProcesoPrendaDetalleReadRepositoryInterface` → `ProcesoPrendaDetalleReadRepository`
  - `SeguimientoAreaRepositoryInterface` → `SeguimientoAreaRepository`
  - `DisenoLogoPedidoRepositoryInterface` → `DisenoLogoPedidoRepository`
  - `LogoDesignStorageInterface` → `LogoDesignStorage`

Registro en:
- `app/Providers/AppServiceProvider.php`

## Rutas

En `routes/web.php` se mantienen las rutas pero se re-apuntan los handlers a Infrastructure:

- `GET /visualizador-logo/pedidos-logo/data` → `PedidosLogoController@data`
- `POST /visualizador-logo/pedidos-logo/area-novedad` → `PedidosLogoController@guardarAreaNovedad`
- `GET /visualizador-logo/pedidos-logo/disenos` → `DisenosLogoPedidoController@index`
- `POST /visualizador-logo/pedidos-logo/disenos` → `DisenosLogoPedidoController@store`
- `DELETE /visualizador-logo/pedidos-logo/disenos/{diseno}` → `DisenosLogoPedidoController@destroy`

## Notas

- La vista `resources/views/visualizador-logo/pedidos-logo.blade.php` permanece como UI.
- La lógica de negocio crítica (áreas permitidas) se centraliza en `Domain`.
- La orquestación y transacciones se centralizan en `Application`.
- Los controllers quedan como adaptadores (Infrastructure).
