# ⚡ QUICK START: Validar ObtenerPedidoUseCase

## Ejecutar en 3 pasos (5 minutos máximo)

### Paso 1: Abrir Terminal

```powershell
# PowerShell en Windows
cd C:\Users\Usuario\Documents\trabahiiiii\v10\v10\mundoindustrial
```

### Paso 2: Ejecutar Script de Validación

```powershell
php validate-bd-relations.php 2700
```

**Verás algo como:**
```
================================================================================
VALIDACIÓN DE ESTRUCTURA BD Y RELACIONES ELOQUENT
================================================================================

 Validando pedido ID: 2700

1️⃣  Verificando existencia del pedido...
    Pedido encontrado: #2700

2️⃣  Verificando relación prendas...
    Prendas cargadas: 5 prendas

   Verificando prenda ID: 101 (CAMISA DRILL)
   3️⃣  Verificando relación tallas...
       Tallas cargadas: 6 registros
...
 VALIDACIÓN COMPLETADA EXITOSAMENTE
================================================================================
```

### Paso 3: Si Hay Error

1. **Leer el mensaje de error**
2. **Consultar:** [GUIA_DEBUGGING_OBTENER_PEDIDO.md](GUIA_DEBUGGING_OBTENER_PEDIDO.md)
3. **Buscar sección:** "Errores Comunes y Soluciones"

---

## Si TODO está 

Siguiente: Probar API en navegador

```
GET http://localhost:8000/api/pedidos/2700
```

Debe retornar JSON con:
- `data.prendas[]` (no vacío)
- `data.epps[]` (puede estar vacío)
- Cada prenda con: nombre, tela, color, tallas, variantes, imagenes, imagenes_tela

---

## Archivos Documentación

📖 **[VALIDACION_ESTRUCTURA_BD_RELACIONES.md](VALIDACION_ESTRUCTURA_BD_RELACIONES.md)**
- Detalle completo de todas las relaciones

📖 **[GUIA_DEBUGGING_OBTENER_PEDIDO.md](GUIA_DEBUGGING_OBTENER_PEDIDO.md)**
- Cómo debuggear si algo falla

📖 **[ACTUALIZACION_OBTENER_PEDIDO_USE_CASE.md](ACTUALIZACION_OBTENER_PEDIDO_USE_CASE.md)**
- Resumen de cambios realizados

📖 **[RESUMEN_OBTENER_PEDIDO_V2.md](RESUMEN_OBTENER_PEDIDO_V2.md)**
- Resumen ejecutivo del refactor

---

## Cambios Realizados

 Archivo: `app/Application/Pedidos/UseCases/ObtenerPedidoUseCase.php`
- 316 líneas de código (antes 161)
- 6 métodos privados para acceso a BD
- Logging integrado
- Manejo de errores con try-catch

 Mapeado a BD real:
- `prendas_pedido` → obtenerPrendasCompletas()
- `prenda_pedido_tallas` → construirEstructuraTallas()
- `prenda_pedido_variantes` → obtenerVariantes()
- `prenda_pedido_colores_telas` → obtenerColorYTela()
- `prenda_fotos_tela_pedido` → obtenerImagenesTela()
- `pedido_epp` + `pedido_epp_imagenes` → obtenerEpps()

---

## 🚨 Si Necesitas Cambiar Pedido ID

```powershell
# En lugar de 2700, usar otro ID:
php validate-bd-relations.php 2701
php validate-bd-relations.php 2702
```

---

**¡Listo! Ya está todo refactorizado y documentado. 🎉**
