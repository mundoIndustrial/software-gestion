<?php

namespace App\Application\Shared\Contracts;

interface TransactionManagerInterface
{
    public function run(callable $callback): mixed;
}
