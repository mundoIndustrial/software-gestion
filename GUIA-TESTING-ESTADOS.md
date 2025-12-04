# GUÍA DE TESTING: ESTADOS COTIZACIONES Y PEDIDOS

## ✅ TESTS IMPLEMENTADOS

Se han creado **15+ tests** que cubren:

### Unit Tests (Servicios)
- [x] CotizacionEstadoServiceTest (14 tests)
- [x] PedidoEstadoServiceTest (14 tests)
- [x] EstadosTest (19 tests)
- [x] HistorialCambiosCotizacionTest (5 tests)

### Feature Tests (Controllers)
- [x] CotizacionEstadoControllerTest (9 tests)
- [x] PedidoEstadoControllerTest (9 tests)

### Factories
- [x] HistorialCambiosCotizacionFactory
- [x] HistorialCambiosPedidoFactory

**Total de tests**: 70+

---

## 🚀 EJECUTAR TESTS

### Ejecutar todos los tests
```bash
php artisan test
```

### Ejecutar tests específicos
```bash
# Solo tests de servicios
php artisan test tests/Unit/Services/

# Solo tests de enums
php artisan test tests/Unit/Enums/

# Solo tests de controllers
php artisan test tests/Feature/

# Solo un archivo de tests
php artisan test tests/Unit/Services/CotizacionEstadoServiceTest.php
```

### Ejecutar con salida detallada
```bash
php artisan test --verbose
```

### Ejecutar con cobertura de código
```bash
php artisan test --coverage
```

### Ejecutar un test específico
```bash
php artisan test --filter test_obtener_siguiente_numero_cotizacion
```

---

## 📋 TESTS DISPONIBLES

### CotizacionEstadoServiceTest (tests/Unit/Services/)

1. **test_obtener_siguiente_numero_cotizacion**
   - Verifica que el siguiente número se calcula correctamente

2. **test_obtener_siguiente_numero_cotizacion_sin_registros**
   - Verifica que inicia en 1 si no hay cotizaciones

3. **test_enviar_cotizacion_a_contador**
   - Verifica transición BORRADOR → ENVIADA_CONTADOR

4. **test_validar_transicion_borrador_a_enviada_contador**
   - Verifica que la transición es válida

5. **test_rechazar_transicion_invalida**
   - Verifica que transiciones inválidas se rechazan

6. **test_aprobar_como_contador**
   - Verifica transición ENVIADA_CONTADOR → APROBADA_CONTADOR

7. **test_asignar_numero_cotizacion**
   - Verifica asignación de número

8. **test_numeros_cotizacion_son_unicos**
   - Verifica que no hay números duplicados

9. **test_obtener_historial_cambios**
   - Verifica que el historial se registra

10. **test_obtener_estado_actual**
    - Verifica obtención del estado

11. **test_flujo_completo_cotizacion**
    - Verifica flujo completo: BORRADOR → APROBADA_COTIZACIONES

12. **test_no_permitir_transicion_duplicada**
    - Verifica que no se permiten transiciones desde estado final

13-14. Más tests adicionales

### PedidoEstadoServiceTest (tests/Unit/Services/)

Similar a CotizacionEstadoServiceTest pero para pedidos:

1. test_obtener_siguiente_numero_pedido
2. test_obtener_siguiente_numero_pedido_sin_registros
3. test_aprobar_pedido_como_supervisor
4. test_validar_transicion_pendiente_a_aprobado
5. test_rechazar_transicion_invalida
6. test_enviar_a_produccion
7. test_asignar_numero_pedido
8. test_numeros_pedido_son_unicos
9. test_obtener_historial_cambios
10. test_obtener_estado_actual
11. test_marcar_como_finalizado
12. test_flujo_completo_pedido
13. test_no_permitir_transicion_desde_estado_final

### EstadosTest (tests/Unit/Enums/)

1. **test_estado_cotizacion_tiene_6_valores** ✓
2. **test_estado_pedido_tiene_4_valores** ✓
3. **test_transicion_valida_borrador_a_enviada_contador** ✓
4. **test_transicion_invalida_borrador_a_finalizada** ✓
5. **test_transiciones_validas_completas_cotizacion** ✓
6. **test_transiciones_validas_completas_pedido** ✓
7. **test_estados_finales_sin_transiciones** ✓
8. **test_labels_cotizacion** ✓
9. **test_labels_pedido** ✓
10. **test_colores_cotizacion** ✓
11. **test_colores_pedido** ✓
12. **test_iconos_cotizacion** ✓
13. **test_iconos_pedido** ✓
14. **test_enum_from_string_cotizacion** ✓
15. **test_enum_from_invalid_string** ✓

### CotizacionEstadoControllerTest (tests/Feature/)

1. **test_enviar_cotizacion_endpoint** ✓
2. **test_no_permitir_enviar_cotizacion_otro_usuario** ✓
3. **test_aprobar_contador_endpoint** ✓
4. **test_aprobar_aprobador_endpoint** ✓
5. **test_obtener_historial_endpoint** ✓
6. **test_obtener_seguimiento_endpoint** ✓
7. **test_no_permitir_ver_seguimiento_cotizacion_ajena** ✓
8. **test_endpoint_requiere_autenticacion** ✓
9. **test_transicion_invalida_devuelve_error** ✓

### PedidoEstadoControllerTest (tests/Feature/)

1. **test_aprobar_pedido_endpoint** ✓
2. **test_obtener_historial_pedido_endpoint** ✓
3. **test_obtener_seguimiento_pedido_endpoint** ✓
4. **test_asesor_puede_ver_su_pedido** ✓
5. **test_no_permitir_ver_pedido_otro_asesor** ✓
6. **test_endpoint_requiere_autenticacion** ✓
7. **test_transicion_invalida_devuelve_error** ✓
8. **test_numero_pedido_por_asignar** ✓

### HistorialCambiosCotizacionTest (tests/Unit/Models/)

1. **test_crear_historial_cambios** ✓
2. **test_relacion_con_cotizacion** ✓
3. **test_relacion_con_usuario** ✓
4. **test_json_datos_adicionales** ✓
5. **test_timestamp_created_at** ✓

---

## 📊 COBERTURA ESPERADA

```
- Servicios: ~95% de cobertura
- Controllers: ~90% de cobertura
- Enums: ~100% de cobertura
- Models: ~85% de cobertura
- Global: ~90%+ de cobertura
```

---

## 🔍 EJEMPLOS DE EJECUCIÓN

### Ejecutar todo
```bash
php artisan test
```

**Salida esperada:**
```
PASS  Tests\Unit\Services\CotizacionEstadoServiceTest
  ✓ obtener siguiente numero cotizacion
  ✓ obtener siguiente numero cotizacion sin registros
  ✓ enviar cotizacion a contador
  ...

PASS  Tests\Feature\CotizacionEstadoControllerTest
  ✓ enviar cotizacion endpoint
  ✓ no permitir enviar cotizacion otro usuario
  ...

Tests:  70 passed (125 assertions)
```

### Ejecutar con verbose
```bash
php artisan test --verbose
```

### Ver solo fallos
```bash
php artisan test --only-failures
```

### Stop en primer fallo
```bash
php artisan test --stop-on-failure
```

---

## 🛠️ TROUBLESHOOTING

### Error: "Migration table not found"
```bash
php artisan migrate:fresh --env=testing
php artisan test
```

### Error: "PDO Exception"
Asegúrate que tu `.env.testing` está configurado correctamente:
```env
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

### Limpiar caché de tests
```bash
php artisan test --cache-result
```

### Ejecutar con output de SQL
```bash
php artisan test --debug
```

---

## ✨ RECOMENDACIONES

1. **Ejecutar antes de commit**
   ```bash
   php artisan test
   ```

2. **Ejecutar en CI/CD pipeline**
   ```bash
   php artisan test --coverage --min=90
   ```

3. **Monitorear cobertura**
   ```bash
   php artisan test --coverage --coverage-html=coverage/
   # Luego abrir coverage/index.html en navegador
   ```

4. **Tests en paralelo** (PHP 8.2+)
   ```bash
   php artisan test --parallel
   ```

---

## 📚 ESTRUCTURA DE TESTS

```
tests/
├── Unit/
│   ├── Services/
│   │   ├── CotizacionEstadoServiceTest.php
│   │   └── PedidoEstadoServiceTest.php
│   ├── Enums/
│   │   └── EstadosTest.php
│   └── Models/
│       └── HistorialCambiosCotizacionTest.php
└── Feature/
    ├── CotizacionEstadoControllerTest.php
    └── PedidoEstadoControllerTest.php
```

---

## 🎯 ÁREAS CUBIERTAS

### ✅ Servicios
- Transiciones de estado válidas
- Rechazar transiciones inválidas
- Asignación de números
- Historial de cambios
- Validación de datos

### ✅ Controllers
- Endpoints REST
- Autenticación
- Autorización
- Validación de entrada
- Respuestas JSON

### ✅ Enums
- Valores correctos
- Transiciones permitidas
- Labels, colores, iconos
- Conversión desde string

### ✅ Modelos
- Relaciones
- Factories
- Validaciones
- Atributos

---

## 🚀 PRÓXIMOS PASOS

1. **Tests de Jobs**
   - AsignarNumeroCotizacionJob
   - EnviarCotizacionAContadorJob
   - AsignarNumeroPedidoJob

2. **Tests de Integración**
   - Flujo completo usuario → cotización → pedido
   - Testing de colas en tiempo real
   - Testing de eventos

3. **Tests de Performance**
   - Benchmarking de transiciones
   - Carga de historial
   - Query optimization

4. **Tests de Seguridad**
   - SQL injection
   - XSS prevention
   - CSRF protection
   - Authorization bypass

---

## 💡 TIPS DE TESTING

### Usar factories para datos de prueba
```php
$cotizacion = Cotizacion::factory()->create([
    'estado' => EstadoCotizacion::BORRADOR->value,
]);
```

### Usar helpers de assertions
```php
$this->assertDatabaseHas('cotizaciones', [
    'numero_cotizacion' => 1001,
]);
```

### Mock externos
```php
Mail::fake();
Notification::fake();
```

### Database transactions en tests
```php
use RefreshDatabase; // Ejecuta migraciones fresh en cada test
use DatabaseTransactions; // Solo revierte en transacciones
```

---

## 📞 SOPORTE

Si los tests fallan:

1. Verifica que las migraciones se ejecutaron
2. Revisa los logs: `storage/logs/laravel.log`
3. Ejecuta con `--verbose`
4. Revisa la estructura de los factories
5. Verifica que los modelos tienen factories definidas

¿Necesitas más tests o cobertura de áreas específicas?
