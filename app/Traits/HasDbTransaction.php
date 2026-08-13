<?php

declare(strict_types=1);

namespace App\Traits;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

trait HasDbTransaction
{
    /**
     * Run the callback inside a database transaction.
     *
     * Failures are logged and rethrown. Never swallow the exception here:
     * DB::transaction() has already rolled back (and, when nested, released
     * the savepoint) by the time it throws, so an extra rollBack() would tear
     * down the *parent* transaction and leave the connection in autocommit
     * while the outer callback keeps writing.
     *
     * @template TReturn of mixed
     *
     * @param  (Closure(Connection): TReturn)  $callback
     * @return TReturn
     *
     * @throws Throwable
     */
    private function dbTransaction(Closure $callback): mixed
    {
        try {
            return DB::transaction($callback);
        } catch (Throwable $e) {
            Log::error('Database transaction failed: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            throw $e;
        }
    }
}
