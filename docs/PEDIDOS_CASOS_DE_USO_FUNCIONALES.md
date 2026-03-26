# Casos De Uso Funcionales - Modulo Pedidos

Fecha: 2026-03-26
Proyecto: Mundo Industrial
Modulo: Backend `Pedidos`

## Objetivo

Este documento sirve para validar manualmente que el modulo `Pedidos` conserve la misma funcionalidad despues del refactor orientado a DDD.

La meta no es probar arquitectura interna, sino confirmar comportamiento funcional visible para usuario o para flujos dependientes.

## Alcance

Cubrir:

- creacion de pedidos
- manejo de borradores
- prendas
- tallas
- telas y colores
- procesos
- EPPs
- imagenes
- consulta y edicion
- cambios de estado

No cubre:

- rendimiento
- concurrencia real de base de datos
- pruebas de infraestructura de MySQL

## Convencion

Cada caso incluye:

- ID
- nombre
- objetivo
- precondiciones
- pasos
- resultado esperado

---

## 1. Creacion De Pedidos

### CU-PED-001 - Crear pedido simple

Objetivo:
Validar que se pueda crear un pedido con una sola prenda basica.

Precondiciones:

- usuario asesor autenticado
- cliente disponible o campo cliente habilitado

Pasos:

1. Entrar al formulario de crear pedido.
2. Ingresar cliente.
3. Ingresar forma de pago.
4. Agregar una prenda con nombre y descripcion.
5. Agregar una talla con cantidad.
6. Guardar el pedido.

Resultado esperado:

- el pedido se crea sin error
- se asigna `numero_pedido`
- queda asociado al asesor autenticado
- el estado inicial corresponde al flujo esperado
- la prenda queda persistida con su talla y cantidad

### CU-PED-002 - Crear pedido con multiples prendas

Objetivo:
Validar que el sistema soporte varias prendas en un mismo pedido.

Precondiciones:

- usuario asesor autenticado

Pasos:

1. Crear un pedido nuevo.
2. Agregar dos o mas prendas.
3. Asignar tallas y cantidades a cada una.
4. Guardar el pedido.

Resultado esperado:

- todas las prendas quedan asociadas al mismo pedido
- no se duplican ni se pierden datos
- el total del pedido refleja todas las prendas

### CU-PED-003 - Crear pedido con EPPs

Objetivo:
Validar que se puedan incluir EPPs en la creacion.

Precondiciones:

- catalogo de EPPs disponible

Pasos:

1. Crear un pedido nuevo.
2. Agregar uno o varios EPPs.
3. Definir cantidades y observaciones.
4. Guardar.

Resultado esperado:

- los EPPs quedan asociados al pedido
- las cantidades quedan correctas
- el pedido se crea sin romper el flujo de prendas

---

## 2. Borradores

### CU-PED-004 - Guardar borrador simple

Objetivo:
Validar que el sistema permita guardar un pedido incompleto como borrador.

Precondiciones:

- usuario asesor autenticado

Pasos:

1. Abrir formulario de pedido.
2. Ingresar datos parciales.
3. Agregar una prenda incompleta o parcial.
4. Guardar como borrador.

Resultado esperado:

- el borrador se guarda sin exigir el flujo final completo
- el pedido queda recuperable despues

### CU-PED-005 - Reabrir borrador

Objetivo:
Validar que un borrador se pueda cargar sin perdida de datos.

Precondiciones:

- existe un borrador previamente guardado

Pasos:

1. Abrir el borrador desde listado o edicion.
2. Revisar cliente, observaciones y forma de pago.
3. Revisar prendas, tallas, telas, procesos e imagenes.

Resultado esperado:

- todos los datos guardados en el borrador aparecen correctamente
- no faltan imagenes
- no hay duplicados

### CU-PED-006 - Actualizar borrador

Objetivo:
Validar que editar un borrador no corrompa datos existentes.

Precondiciones:

- existe un borrador

Pasos:

1. Abrir borrador.
2. Editar cliente u observaciones.
3. Agregar una prenda nueva.
4. Modificar una prenda existente.
5. Guardar cambios.

Resultado esperado:

- los cambios nuevos se persisten
- lo anterior se conserva si no fue modificado
- no se pierden imagenes ni procesos ya existentes

### CU-PED-007 - Convertir borrador en pedido final

Objetivo:
Validar que el borrador pueda pasar al flujo formal de pedido.

Precondiciones:

- existe un borrador valido

Pasos:

1. Abrir borrador.
2. Completar datos faltantes.
3. Confirmar guardado final.

Resultado esperado:

- el borrador se transforma en pedido activo
- conserva prendas, tallas, telas, procesos e imagenes
- mantiene coherencia de numero, estado y relaciones

---

## 3. Prendas

### CU-PED-008 - Agregar prenda con tallas normales

Objetivo:
Validar el alta de una prenda con tallas comunes.

Pasos:

1. Crear o editar un pedido.
2. Agregar una prenda.
3. Registrar tallas normales con cantidades.
4. Guardar.

Resultado esperado:

- las tallas quedan guardadas correctamente
- las cantidades por talla son exactas

### CU-PED-009 - Agregar prenda con sobremedidas

Objetivo:
Validar el flujo de tallas especiales o sobremedidas.

Pasos:

1. Agregar una prenda.
2. Registrar una o varias sobremedidas.
3. Guardar.

Resultado esperado:

- las sobremedidas quedan persistidas
- se distinguen de las tallas normales

### CU-PED-010 - Editar prenda existente

Objetivo:
Validar que una prenda ya guardada pueda actualizarse.

Pasos:

1. Abrir pedido existente.
2. Editar nombre, descripcion o tallas.
3. Guardar cambios.

Resultado esperado:

- la prenda refleja los nuevos datos
- no se duplican relaciones

### CU-PED-011 - Eliminar prenda

Objetivo:
Validar la eliminacion de una prenda sin dañar el resto del pedido.

Pasos:

1. Abrir pedido con varias prendas.
2. Eliminar una prenda.
3. Guardar.

Resultado esperado:

- la prenda eliminada deja de aparecer
- las otras prendas permanecen correctas

---

## 4. Variantes

### CU-PED-012 - Guardar manga, broche y bolsillos

Objetivo:
Validar que las variantes de la prenda se conserven.

Pasos:

1. Agregar una prenda.
2. Definir manga.
3. Definir broche o boton.
4. Definir bolsillos y observaciones.
5. Guardar.

Resultado esperado:

- las variantes quedan registradas
- al reabrir el pedido se mantienen

---

## 5. Telas Y Colores

### CU-PED-013 - Guardar tela y color general

Objetivo:
Validar el caso en el que una prenda tiene color/tela general.

Pasos:

1. Agregar prenda.
2. Registrar una tela.
3. Registrar un color general.
4. Guardar.

Resultado esperado:

- la tela y color quedan asociados a la prenda
- al reabrir se visualizan correctamente

### CU-PED-014 - Guardar color por talla

Objetivo:
Validar el desglose por talla-color.

Pasos:

1. Agregar prenda con tallas.
2. Asignar colores por talla.
3. Guardar.

Resultado esperado:

- se guarda la asociacion por talla-color
- no se mezcla con el color general

### CU-PED-015 - Guardar multiples telas

Objetivo:
Validar prendas con varias telas.

Pasos:

1. Agregar una prenda.
2. Registrar dos o mas telas.
3. Asignar colores si aplica.
4. Guardar.

Resultado esperado:

- todas las telas quedan guardadas
- no se pierden referencias ni observaciones

---

## 6. Procesos

### CU-PED-016 - Agregar proceso simple

Objetivo:
Validar un proceso basico asociado a una prenda.

Pasos:

1. Abrir una prenda en pedido o borrador.
2. Agregar proceso.
3. Definir tipo y observaciones.
4. Guardar.

Resultado esperado:

- el proceso queda asociado a la prenda
- aparece al reabrir el pedido

### CU-PED-017 - Agregar proceso con tallas

Objetivo:
Validar tallas propias del proceso.

Pasos:

1. Agregar proceso a una prenda.
2. Registrar tallas del proceso.
3. Guardar.

Resultado esperado:

- las tallas del proceso quedan persistidas
- se distinguen de las tallas de la prenda

### CU-PED-018 - Agregar proceso con color por talla

Objetivo:
Validar el desglose por talla-color dentro del proceso.

Pasos:

1. Crear o editar proceso.
2. Asignar color y/o tela por talla.
3. Guardar.

Resultado esperado:

- los registros por talla-color del proceso se guardan correctamente
- al consultar, la estructura sigue intacta

---

## 7. Imagenes

### CU-PED-019 - Subir imagen de prenda

Objetivo:
Validar carga de imagen principal de prenda.

Pasos:

1. Agregar una prenda.
2. Subir una imagen.
3. Guardar pedido o borrador.

Resultado esperado:

- la imagen se guarda
- queda asociada a la prenda correcta

### CU-PED-020 - Subir imagen de tela

Objetivo:
Validar imagen asociada a color/tela.

Pasos:

1. Agregar tela a la prenda.
2. Subir imagen relacionada.
3. Guardar.

Resultado esperado:

- la imagen queda asociada a la tela correcta

### CU-PED-021 - Subir imagen de proceso

Objetivo:
Validar imagen asociada a proceso.

Pasos:

1. Agregar proceso a una prenda.
2. Subir una o mas imagenes.
3. Guardar.

Resultado esperado:

- las imagenes quedan asociadas al proceso
- se conservan al reabrir

### CU-PED-022 - Actualizar pedido con imagenes ya existentes

Objetivo:
Validar que la edicion no duplique ni pierda imagenes.

Pasos:

1. Abrir pedido con imagenes.
2. Agregar nuevas imagenes.
3. Mantener algunas existentes.
4. Guardar.

Resultado esperado:

- las imagenes existentes permanecen
- las nuevas se agregan
- no hay duplicados

---

## 8. Consulta Y Edicion

### CU-PED-023 - Obtener pedido completo

Objetivo:
Validar que la consulta devuelva toda la informacion necesaria.

Pasos:

1. Abrir un pedido existente.
2. Revisar cabecera, prendas, tallas, variantes, telas, procesos, EPPs e imagenes.

Resultado esperado:

- toda la informacion aparece consistente
- no hay campos vacios inesperados

### CU-PED-024 - Listar pedidos

Objetivo:
Validar el listado general.

Pasos:

1. Ir al listado de pedidos.
2. Buscar por numero.
3. Filtrar por estado.

Resultado esperado:

- los pedidos aparecen correctamente
- los filtros funcionan

---

## 9. Cambios De Estado

### CU-PED-025 - Confirmar pedido

Objetivo:
Validar la transicion a confirmado.

Pasos:

1. Abrir pedido valido.
2. Confirmarlo.

Resultado esperado:

- cambia al estado esperado
- no altera datos internos del pedido

### CU-PED-026 - Iniciar produccion

Objetivo:
Validar la transicion de confirmado a produccion.

Pasos:

1. Tomar pedido confirmado.
2. Ejecutar cambio a produccion.

Resultado esperado:

- el estado cambia correctamente

### CU-PED-027 - Completar pedido

Objetivo:
Validar la finalizacion.

Pasos:

1. Tomar pedido en produccion.
2. Marcar como completado.

Resultado esperado:

- el pedido queda en estado final esperado

### CU-PED-028 - Intentar transicion invalida

Objetivo:
Validar que el sistema rechace cambios de estado no permitidos.

Pasos:

1. Intentar pasar un pedido desde un estado no valido a otro no permitido.

Resultado esperado:

- el sistema rechaza la operacion
- no deja el pedido en estado inconsistente

---

## 10. Casos Limite

### CU-PED-029 - Pedido sin prendas

Objetivo:
Validar el contrato actual si el sistema lo permite.

Pasos:

1. Crear pedido sin prendas.

Resultado esperado:

- si el contrato actual lo acepta, se guarda sin error
- si no, devuelve validacion clara

### CU-PED-030 - Proceso sin imagenes

Objetivo:
Validar que imagenes sean opcionales donde corresponda.

Resultado esperado:

- el proceso se guarda sin error

### CU-PED-031 - Reapertura sin cambios

Objetivo:
Validar idempotencia funcional basica.

Pasos:

1. Abrir pedido ya creado.
2. Guardar sin cambiar nada.

Resultado esperado:

- no se duplican datos
- no se rompen relaciones

---

## 11. Orden Recomendado De Ejecucion

Si se quiere una validacion rapida y de alto valor, ejecutar en este orden:

1. `CU-PED-001`
2. `CU-PED-004`
3. `CU-PED-005`
4. `CU-PED-006`
5. `CU-PED-008`
6. `CU-PED-013`
7. `CU-PED-016`
8. `CU-PED-019`
9. `CU-PED-023`
10. `CU-PED-025`

## 12. Criterio De Cierre

Se considera que el refactor conserva funcionalidad si:

- los casos criticos de crear/guardar/editar pedido funcionan
- no hay perdida de tallas, telas, procesos o imagenes
- los cambios de estado siguen respetando el flujo esperado
- el pedido se puede consultar y reabrir correctamente
