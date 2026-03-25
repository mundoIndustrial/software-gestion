<?php

namespace App\Infrastructure\Services;

use App\Application\Shared\Contracts\TransactionManagerInterface;
use Illuminate\Support\Facades\DB;

class EloquentTransactionManager implements TransactionManagerInterface
{
    public function run(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
