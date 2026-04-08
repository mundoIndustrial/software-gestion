<?php

namespace App\Infrastructure\Containers;

use App\Services\RegistroOrdenExtendedQueryService;
use App\Services\RegistroOrdenSearchExtendedService;
use App\Services\RegistroOrdenFilterExtendedService;
use App\Services\RegistroOrdenTransformService;
use App\Services\RegistroOrdenProcessService;
use App\Services\RegistroOrdenStatsService;
use App\Services\RegistroOrdenProcessesService;
use App\Services\RegistroOrdenEnumService;

/**
 * RegistroOrdenQueryServicesContainer
 * Agrupa todos los servicios de query, search, filter y transform
 * para reducir parámetros del constructor
 */
final class RegistroOrdenQueryServicesContainer
{
    public function __construct(
        public readonly RegistroOrdenExtendedQueryService $extendedQueryService,
        public readonly RegistroOrdenSearchExtendedService $extendedSearchService,
        public readonly RegistroOrdenFilterExtendedService $extendedFilterService,
        public readonly RegistroOrdenTransformService $transformService,
        public readonly RegistroOrdenProcessService $processService,
        public readonly RegistroOrdenStatsService $statsService,
        public readonly RegistroOrdenProcessesService $processesService,
        public readonly RegistroOrdenEnumService $enumService,
    ) {}
}
