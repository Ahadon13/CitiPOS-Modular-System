<?php

namespace App\Traits;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

trait HasDbTransaction
{
    /**
     * @template TReturn of mixed
     *
     * @param  (Closure(Connection): TReturn)  $callback
     * @return TReturn|false
     */
    private function dbTransaction(Closure $callback): mixed
    {
        try {
            return DB::transaction($callback);
        } catch (Throwable $e) {
            $this->safeRollback();

            Log::error('Database transaction failed: '.$e->getMessage(), [
                'exception' => $e,
            ]);
        }

        return false;
    }

    private function safeRollback(): void
    {
        try {
            DB::rollBack();
        } catch (Throwable $e) {

            Log::error('Rollback failed: '.$e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }
}
