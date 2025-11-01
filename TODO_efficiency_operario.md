# TODO: Eficiencia y Selector de Operarios

## 1. Eficiencia - Multiplicar por 100 y redondear
- [ ] Actualizar cálculos en TablerosController.php para multiplicar eficiencia por 100
- [ ] Redondear valores decimales (ej. 54.5 -> 55)
- [ ] Verificar display en tableros.blade.php

## 2. Selector de Operarios - Autocomplete
- [ ] Agregar métodos searchOperarios y storeOperario en TablerosController.php
- [ ] Agregar rutas para search-operarios y store-operario en routes/web.php
- [ ] Cambiar select de operario por input autocomplete en form_modal_piso_corte.blade.php
- [ ] Agregar JavaScript para autocomplete de operarios
- [ ] Probar funcionalidad de búsqueda y creación

## Archivos a modificar:
- app/Http/Controllers/TablerosController.php
- resources/views/components/form_modal_piso_corte.blade.php
- routes/web.php
- resources/views/tableros.blade.php (verificación de display)
