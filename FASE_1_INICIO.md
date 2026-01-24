# FASE 1 – DOMINIO: GUÍA DE IMPLEMENTACIÓN

**Status:** Próxima a comenzar  
**Objetivo:** Completar implementación del dominio con persistencia en tests  
**Tiempo estimado:** 3-4 horas

---

##  TAREAS DE FASE 1

### 1️⃣ Crear Tests de Persistencia

**Archivo:** `tests/Feature/Domain/Pedidos/PedidoRepositoryTest.php`

```php
<?php

namespace Tests\Feature\Domain\Pedidos;

use Tests\TestCase;
use App\Domain\Pedidos\Agregado\PedidoAggregate;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Domain\Pedidos\ValueObjects\NumeroPedido;

class PedidoRepositoryTest extends TestCase
{
    private PedidoRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(PedidoRepository::class);
    }

    /**
     * Test: Guardar y recuperar pedido por ID
     */
    public function test_guardar_y_recuperar_por_id()
    {
        // Crear agregado
        $pedido = PedidoAggregate::crear(
            clienteId: 1,
            descripcion: 'Pedido de persistencia',
            prendasData: [
                [
                    'prenda_id' => 1,
                    'descripcion' => 'Camiseta',
                    'cantidad' => 10,
                    'tallas' => ['DAMA' => ['S' => 10]],
                ]
            ]
        );

        // Guardar
        $this->repository->guardar($pedido);

        // Recuperar
        $recuperado = $this->repository->porId($pedido->id());

        $this->assertNotNull($recuperado);
        $this->assertEquals($pedido->id(), $recuperado->id());
        $this->assertEquals('Pedido de persistencia', $recuperado->descripcion());
    }

    /**
     * Test: Guardar y recuperar por número
     */
    public function test_guardar_y_recuperar_por_numero()
    {
        $pedido = PedidoAggregate::crear(
            clienteId: 1,
            descripcion: 'Test',
            prendasData: [[
                'prenda_id' => 1,
                'descripcion' => 'Camiseta',
                'cantidad' => 5,
                'tallas' => ['DAMA' => ['S' => 5]],
            ]]
        );

        $this->repository->guardar($pedido);

        $recuperado = $this->repository->porNumero($pedido->numero());

        $this->assertNotNull($recuperado);
        $this->assertTrue($pedido->numero()->esIgualA($recuperado->numero()));
    }

    /**
     * Test: Obtener por estado
     */
    public function test_obtener_por_estado()
    {
        $pedido = PedidoAggregate::crear(
            clienteId: 1,
            descripcion: 'Test estado',
            prendasData: [[
                'prenda_id' => 1,
                'descripcion' => 'Camiseta',
                'cantidad' => 5,
                'tallas' => ['DAMA' => ['S' => 5]],
            ]]
        );

        $this->repository->guardar($pedido);

        $pendientes = $this->repository->porEstado('PENDIENTE');

        $this->assertNotEmpty($pendientes);
    }

    /**
     * Test: Actualizar estado
     */
    public function test_actualizar_estado()
    {
        $pedido = PedidoAggregate::crear(
            clienteId: 1,
            descripcion: 'Test update',
            prendasData: [[
                'prenda_id' => 1,
                'descripcion' => 'Camiseta',
                'cantidad' => 5,
                'tallas' => ['DAMA' => ['S' => 5]],
            ]]
        );

        $this->repository->guardar($pedido);
        
        // Recuperar y confirmar
        $recuperado = $this->repository->porId($pedido->id());
        $recuperado->confirmar();
        $this->repository->guardar($recuperado);

        // Verificar cambio
        $confirmado = $this->repository->porId($pedido->id());
        $this->assertEquals('CONFIRMADO', $confirmado->estado()->valor());
    }
}
```

### 2️⃣ Ejecutar los Tests

```bash
php artisan test tests/Feature/Domain/Pedidos/PedidoRepositoryTest.php
```

Si fallan, es normal. La implementación del Repository aún no está lista.

### 3️⃣ Ajustar PedidoRepositoryImpl

Verificar que:
- Las transacciones funcionen
- El Mapper Eloquent ↔ Agregado sea correcto
- Las prendas se guarden correctamente

### 4️⃣ Ejecutar Tests Nuevamente

Objetivo: 4/4 tests pasando

---

## 🔧 COMANDOS ÚTILES

```bash
# Ver estructura creada
find app/Domain/Pedidos -type f -name "*.php" | wc -l

# Verificar errores de sintaxis
php -l app/Domain/Pedidos/Agregado/PedidoAggregate.php

# Ejecutar con tinker
php artisan tinker
> $p = \App\Domain\Pedidos\Agregado\PedidoAggregate::crear(1, 'Test', [[...]]);
> dd($p->toArray());

# Ejecutar tests específicos
php artisan test tests/Feature/Domain/Pedidos/PedidoRepositoryTest.php
```

---

## ⏭️ DESPUÉS DE FASE 1

Una vez que los tests de persistencia pasen, estarás listo para:

**Fase 2:** Crear el Controller que use los Use Cases (sin cambiar producción aún)

---

## 📝 CHECKLIST FASE 1

- [ ] Tests de persistencia creados
- [ ] Repository Implementation actualizado
- [ ] Tests pasando 4/4
- [ ] Transacciones funcionando
- [ ] Prendas guardadas correctamente
- [ ] Estados actualizados correctamente

---

**Próximo:** Fase 2 - Persistencia DDD en uso
