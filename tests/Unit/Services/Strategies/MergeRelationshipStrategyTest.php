<?php

namespace Tests\Unit\Services\Strategies;

use Tests\TestCase;
use App\Infrastructure\Services\Strategies\MergeRelationshipStrategy;

class MergeRelationshipStrategyTest extends TestCase
{
    /**
     * Test: Estrategia MERGE clase existe
     */
    public function test_merge_strategy_class_exists(): void
    {
        $this->assertTrue(class_exists(MergeRelationshipStrategy::class));
    }

    /**
     * Test: MergeRelationshipStrategy tiene método merge
     */
    public function test_strategy_has_merge_method(): void
    {
        $reflection = new \ReflectionClass(MergeRelationshipStrategy::class);
        $this->assertTrue($reflection->hasMethod('merge'));
    }

    /**
     * Test: MergeRelationshipStrategy tiene método mergeColores
     */
    public function test_strategy_has_merge_colores_method(): void
    {
        $reflection = new \ReflectionClass(MergeRelationshipStrategy::class);
        $this->assertTrue($reflection->hasMethod('mergeColores'));
    }

    /**
     * Test: MergeRelationshipStrategy tiene método mergeTelas
     */
    public function test_strategy_has_merge_telas_method(): void
    {
        $reflection = new \ReflectionClass(MergeRelationshipStrategy::class);
        $this->assertTrue($reflection->hasMethod('mergeTelas'));
    }

    /**
     * Test: MergeRelationshipStrategy tiene método mergeTallas
     */
    public function test_strategy_has_merge_tallas_method(): void
    {
        $reflection = new \ReflectionClass(MergeRelationshipStrategy::class);
        $this->assertTrue($reflection->hasMethod('mergeTallas'));
    }

    /**
     * Test: MergeRelationshipStrategy tiene método mergeVariantes
     */
    public function test_strategy_has_merge_variantes_method(): void
    {
        $reflection = new \ReflectionClass(MergeRelationshipStrategy::class);
        $this->assertTrue($reflection->hasMethod('mergeVariantes'));
    }

    /**
     * Test: MergeRelationshipStrategy tiene método getOnlyInPayload
     */
    public function test_strategy_has_get_only_in_payload_method(): void
    {
        $reflection = new \ReflectionClass(MergeRelationshipStrategy::class);
        $this->assertTrue($reflection->hasMethod('getOnlyInPayload'));
    }

    /**
     * Test: MergeRelationshipStrategy tiene método getOnlyInExisting
     */
    public function test_strategy_has_get_only_in_existing_method(): void
    {
        $reflection = new \ReflectionClass(MergeRelationshipStrategy::class);
        $this->assertTrue($reflection->hasMethod('getOnlyInExisting'));
    }

    /**
     * Test: Todos los métodos son estáticos
     */
    public function test_all_methods_are_static(): void
    {
        $reflection = new \ReflectionClass(MergeRelationshipStrategy::class);
        $methods = ['merge', 'mergeColores', 'mergeTelas', 'mergeTallas', 'mergeVariantes'];

        foreach ($methods as $method) {
            $reflectionMethod = $reflection->getMethod($method);
            $this->assertTrue($reflectionMethod->isStatic(), "Method $method is not static");
        }
    }

    /**
     * Test: Clase documenta lógica MERGE
     */
    public function test_merge_logic_documented(): void
    {
        $reflection = new \ReflectionClass(MergeRelationshipStrategy::class);
        $docComment = $reflection->getDocComment();

        // La clase debe tener documentación sobre MERGE
        $this->assertNotEmpty($docComment);
    }
}
