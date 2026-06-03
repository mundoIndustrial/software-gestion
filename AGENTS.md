# AGENTS.md

## Propósito
Este repositorio es una aplicación Laravel. Estas instrucciones aplican a todo el árbol del proyecto, salvo que exista un `AGENTS.md` más específico en una subcarpeta.

## Objetivo al trabajar aquí
- Hacer cambios mínimos y enfocados.
- Resolver la causa raíz, no solo el síntoma.
- Mantener consistencia con el estilo existente del proyecto.
- No tocar código ajeno al problema actual.

## Flujo de trabajo recomendado
1. Entender el cambio solicitado y ubicar los archivos relevantes.
2. Revisar dependencias directas antes de editar.
3. Hacer el cambio mínimo necesario.
4. Validar con pruebas o lint solo en la parte afectada.
5. Reportar claramente qué cambió y qué falta, si aplica.

## Reglas de edición
- Preferir cambios pequeños y fáciles de revisar.
- No introducir refactors amplios si no son necesarios.
- No agregar comentarios inline salvo que el usuario lo pida.
- No agregar copyright o licencias nuevas.
- No usar variables de un solo carácter salvo casos muy puntuales.
- Si hay patrones existentes en el repo, seguirlos.

## Convenciones de Laravel
- Respetar la estructura estándar de Laravel cuando aplique.
- Mantener coherencia entre controllers, requests, services, repositories, jobs y listeners según el patrón existente.
- Si el proyecto ya usa una convención de nombres, no inventar una nueva.
- Evitar mover archivos de lugar sin una razón clara.

## Validación
- Si el cambio afecta PHP, validar con `php -l` en los archivos tocados cuando sea posible.
- Si afecta comportamiento, correr las pruebas más cercanas al cambio antes de sugerir una validación más amplia.
- No intentar arreglar fallos no relacionados que aparezcan durante la validación.
- Si una prueba falla por un problema previo ajeno al cambio, reportarlo sin expandir el alcance.

## Seguridad y cuidado
- No borrar, renombrar ni mover archivos de forma destructiva sin necesidad.
- No asumir cambios de configuración global sin revisar el contexto.
- Si hay dudas sobre impacto, detenerse y explicar la alternativa antes de seguir.
- No hacer commits, crear ramas ni publicar cambios a GitHub a menos que el usuario lo pida explícitamente.

## Hallazgos y bugs
- Si durante el trabajo aparece un bug relacionado con el cambio actual, informarlo claramente al usuario.
- No intentar corregir bugs no relacionados sin pedir permiso.
- Si el bug afecta directamente lo que se está tocando, señalarlo aunque no forme parte del alcance principal.

## Entrega
- Explicar qué se cambió, por qué y cómo se validó.
- Si quedó algo pendiente, decirlo explícitamente.
- Referenciar rutas de archivos concretas cuando sea útil.
