# Frontend Supervisor Pedidos - Fase 1 (1 semana)

Fecha: 2026-03-27
Alcance: solo modulo `supervisor-pedidos`
Stack: Blade + Vite + JavaScript vainilla

## Objetivo

Reducir el acoplamiento y la carga desordenada de scripts en `supervisor-pedidos` sin romper funcionalidades actuales.

## Resultado esperado al final de la semana

- 1 entrypoint principal de frontend para `supervisor-pedidos`.
- Menos scripts directos en Blade.
- Menos dependencia de `js/asesores/*` dentro de supervisor.
- Baseline y metricas comparables en cada PR.

## Baseline inicial (antes de Fase 1)

Indicadores observados en revision:

- `resources/views/supervisor-pedidos/index.blade.php` carga muchos scripts directos.
- Hay includes de componentes y scripts del modulo `asesores`.
- `layout` de supervisor hereda estilos de asesores y mezcla scripts inline con bundles.

## Plan dia a dia

1. Dia 1 - Baseline y guardrails
- Crear auditoria automatica para contar:
  - cantidad de `<script>` por vista,
  - scripts de `asesores` consumidos por supervisor,
  - cantidad de scripts inline.
- Publicar baseline en documento tecnico.
- Definir checklist de PR para no introducir mas acoplamiento.

2. Dia 2 - Entry point unico (sin cambiar comportamiento)
- Crear `resources/js/supervisor-pedidos/entry.js`.
- Mover inicializacion de scripts de supervisor a ese entrypoint.
- Mantener compatibilidad con globals actuales.

3. Dia 3 - Extraer inline JS critico de `layout`
- Sacar bloques inline de notificaciones/filtros a archivos `public/js/supervisor-pedidos/layout/*`.
- Dejar en Blade solo data/configuracion.

4. Dia 4 - Reducir dependencia directa de `asesores`
- Identificar piezas reusables y moverlas a `public/js/shared/*`.
- Reapuntar supervisor a `shared` en vez de `asesores` cuando sea posible.

5. Dia 5 - Limpieza y verificacion
- Quitar cargas duplicadas.
- Ejecutar auditoria final y comparar contra baseline.
- Publicar reporte de cierre Fase 1.

## Criterios de aceptacion (Fase 1)

- No regresiones visibles en:
  - listado supervisor,
  - modal detalle,
  - modal tracking,
  - acciones aprobar/anular/seleccionar.
- Reduccion medible (objetivo minimo):
  - al menos 20% menos scripts directos en `supervisor-pedidos/index.blade.php`,
  - al menos 30% menos referencias directas a `js/asesores/*` desde vistas supervisor.
- Auditoria automatica disponible y ejecutable por comando.

## Estado de cierre

Fase 1 completada (2026-03-27).

Checklist de cumplimiento:

- [x] Entrypoint unico del modulo conectado por Vite.
- [x] Reduccion >= 20% de scripts directos en `supervisor-pedidos/index.blade.php`.
- [x] Reduccion >= 30% de referencias directas a `js/asesores/*` desde vistas supervisor.
- [x] Auditoria automatica disponible y usada para baseline/seguimiento.

Documento de cierre:

- `docs/FRONT_SUPERVISOR_PEDIDOS_FASE1_CIERRE_2026-03-27.md`

## Riesgos y mitigacion

- Riesgo: romper orden de carga por dependencias globales.
- Mitigacion: migracion por capas, no big-bang, fallback temporal en Blade durante transicion.

- Riesgo: acoplamiento oculto con modales compartidos.
- Mitigacion: mover primero a `shared`, luego desacoplar implementaciones.

## Comando de auditoria

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\auditar-front-supervisor.ps1
```
