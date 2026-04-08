# Insumos - Cierre de Limpieza (2026-03-26)

Estado final aplicado:

- Eliminado `app/Services/Insumos/MaterialesService.php`.
- Eliminado legado paralelo `modules/insumos/*`.
- Flujo vigente de materiales (DDD por capas):
  - `app/Application/Insumos/UseCases/*`
  - `app/Domain/Insumos/Repositories/*`
  - `app/Infrastructure/Insumos/Persistence/Eloquent/*`
  - `app/Infrastructure/Http/Controllers/Insumos/InsumosController.php`

Verificaciones:

- No hay referencias activas en runtime a `Modules\\Insumos`.
- No hay referencias activas a `App\\Services\\Insumos\\MaterialesService`.
- Pruebas feature de Insumos pasan.
