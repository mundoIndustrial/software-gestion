# Plan de Implementación: Módulo Gestión de Lavandería

Este plan describe el diseño y la implementación del nuevo módulo **Gestión de Lavandería** en la aplicación, siguiendo la arquitectura DDD (Domain-Driven Design), con un diseño adaptativo premium y un sistema de control de salidas/llegadas con firma digital.

---

## User Review Required

> [!IMPORTANT]
> Se creará un nuevo rol en la base de datos llamado `gestor-lavanderia`. El acceso al módulo estará protegido por un middleware de Laravel que valida si el usuario autenticado tiene este rol (o roles administradores como `admin`).

> [!WARNING]
> La firma digital se capturará en el frontend usando un canvas interactivo (Signature Pad) y se almacenará en formato Base64 en la base de datos para simplificar la persistencia y asegurar la visualización inmediata tanto en PC como en móviles.

---

## Open Questions

> [!NOTE]
> No hay preguntas abiertas críticas en este momento. La estructura se alinea perfectamente con los modelos existentes `ConsecutivoReciboPedido`, `PrendaPedido`, `PrendaBodega` y sus respectivas tablas de tallas.

---

## Proposed Changes

### 1. Base de Datos (Migraciones)

#### [NEW] [create_lavanderia_tables.php](file:///c:/Users/usuario/Desktop/mundoindustrial/database/migrations/2026_05_26_130000_create_lavanderia_tables.php)
Creará las tablas `lavanderia_movimientos` y `lavanderia_movimiento_tallas` (evitando la palabra "despacho"), además de insertar el rol `gestor-lavanderia` en la tabla `roles`.

```sql
CREATE TABLE `lavanderia_movimientos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `consecutivo_recibo_pedido_id` bigint unsigned NOT NULL,
  `numero_recibo` int NOT NULL,
  `tipo_recibo` enum('COSTURA', 'CORTE-PARA-BODEGA') NOT NULL,
  `fecha_salida` datetime NOT NULL,
  `firma_salida` longtext NOT NULL COMMENT 'Firma digital en formato Base64',
  `fecha_llegada` datetime DEFAULT NULL,
  `firma_llegada` longtext DEFAULT NULL COMMENT 'Firma digital en formato Base64',
  `novedad` text DEFAULT NULL,
  `estado` enum('PENDIENTE', 'PARCIAL', 'COMPLETADO') NOT NULL DEFAULT 'PENDIENTE',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_lavanderia_recibo_id` FOREIGN KEY (`consecutivo_recibo_pedido_id`) REFERENCES `consecutivos_recibos_pedidos` ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `lavanderia_movimiento_tallas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lavanderia_movimiento_id` bigint unsigned NOT NULL,
  `talla` varchar(50) NOT NULL,
  `genero` varchar(50) DEFAULT NULL,
  `color` varchar(100) DEFAULT NULL,
  `cantidad_enviada` int NOT NULL,
  `cantidad_recibida` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_lavanderia_movimiento_id` FOREIGN KEY (`lavanderia_movimiento_id`) REFERENCES `lavanderia_movimientos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 2. Estructura DDD (Backend)

Implementaremos las clases del dominio, aplicación e infraestructura en directorios limpios bajo `app/Domain/Lavanderia`, `app/Application/Lavanderia` y `app/Infrastructure/Lavanderia`.

#### [NEW] [LavanderiaMovimiento.php (Modelo Eloquent)](file:///c:/Users/usuario/Desktop/mundoindustrial/app/Models/LavanderiaMovimiento.php)
#### [NEW] [LavanderiaMovimientoTalla.php (Modelo Eloquent)](file:///c:/Users/usuario/Desktop/mundoindustrial/app/Models/LavanderiaMovimientoTalla.php)
Modelos Eloquent mapeados a las nuevas tablas.

#### [NEW] [LavanderiaAccess.php (Middleware)](file:///c:/Users/usuario/Desktop/mundoindustrial/app/Http/Middleware/LavanderiaAccess.php)
Middleware para restringir acceso al rol `gestor-lavanderia` o `admin`.

#### [NEW] [LavanderiaController.php](file:///c:/Users/usuario/Desktop/mundoindustrial/app/Infrastructure/Http/Controllers/Lavanderia/LavanderiaController.php)
Controlador principal para gestionar las vistas y las peticiones AJAX de lavandería (buscar recibo, registrar salida, registrar llegada).

#### [NEW] [lavanderia.php (Rutas)](file:///c:/Users/usuario/Desktop/mundoindustrial/routes/lavanderia.php)
Archivo de rutas independiente que será requerido en `routes/web.php`.

---

### 3. Interfaz de Usuario (Frontend)

#### [NEW] [index.blade.php](file:///c:/Users/usuario/Desktop/mundoindustrial/modules/lavanderia/frontend/views/index.blade.php)
Vista Blade totalmente responsive (Mobile y PC). Incluye:
- Tabla premium de control de salidas y llegadas con diseño idéntico al mockup (tarjetas colapsables para móviles).
- Formulario modal o dinámico para **Registrar Salida** buscando por recibo de tipo `COSTURA` o `CORTE-PARA-BODEGA`.
- Autocompletado del cliente, prenda, descripción, tallas y cantidades disponibles.
- Firma digital integrada mediante canvas HTML5 (Signature Pad).
- Modal interactivo para **Registrar Llegada** actualizando cantidades parciales/totales, firmando la recepción e ingresando novedades en caso de faltantes.

---

## Verification Plan

### Automated/Manual Verification
1. **Migraciones**: Ejecutar `php artisan migrate` para crear las tablas y registrar el rol.
2. **Acceso de Roles**: Probar el middleware ingresando con un usuario sin el rol (debe dar 403) y con un usuario con rol `gestor-lavanderia` (debe permitir acceso).
3. **Formulario de Salida**: Buscar un recibo existente (de tipo COSTURA o CORTE-PARA-BODEGA). Verificar que extrae los datos correctamente según el tipo de recibo.
4. **Firma**: Dibujar la firma digital y guardar. Validar que se guarda en la base de datos y se visualiza en la tabla de control.
5. **Llegadas**: Realizar una llegada parcial, verificar que el estado cambia a `PARCIAL` y la novedad se muestra en rojo. Realizar la llegada completa, verificar estado `COMPLETADO`.
