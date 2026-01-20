# Métodos a Eliminar del Controlador

##  Ya Migrados a Servicios - ELIMINAR DEL CONTROLADOR

### NumeracionService
- `generarNumeroPedido()` - Línea ~1277
- `generarNumeroLogoPedido()` - Línea ~1276

### DescripcionService  
- `construirDescripcionPrenda()` - ELIMINADO 
- `construirDescripcionPrendaCompleta()` - ELIMINADO 
- `construirDescripcionPrendaSinCotizacion()` - Línea ~2355 (renombrado a _ELIMINADO)
- `armarDescripcionVariacionesPrendaSinCotizacion()` - Línea ~2517
- `construirDescripcionReflectivoSinCotizacion()` - Línea ~2809

### ImagenService
- `guardarImagenComoWebp()` - Línea ~2571

##  Resumen

**Total de métodos duplicados a eliminar:** ~8 métodos
**Líneas estimadas a eliminar:** ~600-800 líneas
**Controlador actual:** ~2900 líneas
**Controlador después:** ~2100-2300 líneas

##  Próximos Pasos

1. Eliminar completamente los métodos duplicados (no solo renombrar)
2. Verificar que todas las llamadas usen los servicios
3. Eliminar métodos OBSOLETOS marcados como tal
4. Refactorizar métodos grandes restantes a servicios adicionales
5. Meta final: Controlador < 1000 líneas
