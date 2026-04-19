<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('partnerships_branch_id_lookup', 'branch_id');
        $this->addIndexIfMissing('partnerships_customer_type_id_lookup', 'customer_type_id');
        $this->addIndexIfMissing('partnerships_product_packaging_id_lookup', 'product_packaging_id');

        if ($this->hasIndex('unique_partnership_pricing')) {
            Schema::table('partnerships', function (Blueprint $table) {
                $table->dropUnique('unique_partnership_pricing');
            });
        }

        if (! $this->hasIndex('unique_branch_partnership_pricing')) {
            Schema::table('partnerships', function (Blueprint $table) {
                $table->unique(['branch_id', 'customer_type_id', 'product_packaging_id'], 'unique_branch_partnership_pricing');
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('unique_branch_partnership_pricing')) {
            Schema::table('partnerships', function (Blueprint $table) {
                $table->dropUnique('unique_branch_partnership_pricing');
            });
        }

        if (! $this->hasIndex('unique_partnership_pricing')) {
            Schema::table('partnerships', function (Blueprint $table) {
                $table->unique(['customer_type_id', 'product_packaging_id'], 'unique_partnership_pricing');
            });
        }
    }

    private function addIndexIfMissing(string $indexName, string $column): void
    {
        if ($this->hasIndex($indexName)) {
            return;
        }

        Schema::table('partnerships', function (Blueprint $table) use ($indexName, $column) {
            $table->index($column, $indexName);
        });
    }

    private function hasIndex(string $indexName): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'partnerships')
            ->where('index_name', $indexName)
            ->exists();
    }
};
