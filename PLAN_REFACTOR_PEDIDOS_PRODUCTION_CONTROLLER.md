# 📊 PLAN REFACTOR - PedidosProduccionController

## Análisis Actual
- **Archivo**: app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php
- **Líneas**: 1069
- **Métodos**: 14 públicos
- **Patrón**: CQRS (QueryBus, CommandBus)
- **Inyecciones**: QueryBus, CommandBus, Repository

## Métodos a Refactorizar

### CRUD Operations (6)
1. `index()` - Listar pedidos
   - Query: ListarPedidosQuery
   - DTO: ListarPedidosProductionDTO (NEW)
   - UseCase: ListarPedidosProductionUseCase (NEW)

2. `show(id)` - Obtener pedido específico
   - Query: ObtenerPedidoQuery
   - DTO: ObtenerPedidoProductionDTO (NEW)
   - UseCase: ObtenerPedidoProductionUseCase (NEW)

3. `store()` - Crear pedido
   - Command: CrearPedidoCommand
   - DTO: CrearPedidoProductionDTO (NEW)
   - UseCase: CrearPedidoProductionUseCase (NEW)

4. `update(id)` - Actualizar pedido
   - Command: ActualizarPedidoCommand
   - DTO: ActualizarPedidoProductionDTO (NEW)
   - UseCase: ActualizarPedidoProductionUseCase (NEW)

5. `destroy(id)` - Eliminar pedido
   - Command: EliminarPedidoCommand
   - DTO: EliminarPedidoProductionDTO (NEW)
   - UseCase: EliminarPedidoProductionUseCase (NEW)

### Operaciones Complementarias (9)
6. `cambiarEstado(id)` - Cambiar estado
7. `agregarPrenda(id)` - Agregar prenda simple
8. `filtrarPorEstado()` - Filtrar por estado
9. `buscarPorNumero()` - Búsqueda
10. `obtenerPrendas(id)` - Listar prendas
11. `renderItemCard()` - Renderizar UI
12. `actualizarPrenda()` - Actualizar prenda
13. `agregarPrendaCompleta(id)` - Agregar prenda completa
14. `actualizarPrendaCompleta(id)` - Actualizar prenda completa

## Estrategia
- Mantener CQRS (es un buen patrón)
- Envolver CQRS en Use Cases
- Los Use Cases ejecutan Queries/Commands
- Centralizar DTOs

## Beneficios
- Mejor separación de responsabilidades
- Más testeable
- Reutilizable en otros contextos
- Mantiene CQRS + agrega DDD
