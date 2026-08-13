<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-branch barcode scanner configuration.
 *
 * A USB scanner is a keyboard as far as the browser is concerned, so there is
 * no hardware identity to store. What is configurable is how aggressively the
 * page should interpret fast keystrokes as a scan -- and that genuinely varies
 * per counter, because scanner models differ in speed and suffix key.
 *
 * A branch belongs to exactly one module, so per-branch settings also give
 * per-module settings for free.
 *
 * The feature ships disabled everywhere: the existing POS flow is untouched
 * until someone opts a branch in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (! Schema::hasColumn('branches', 'barcode_scanner_enabled')) {
                $table->boolean('barcode_scanner_enabled')->default(false)->after('is_active');
            }

            if (! Schema::hasColumn('branches', 'barcode_min_length')) {
                $table->unsignedSmallInteger('barcode_min_length')->default(6)->after('barcode_scanner_enabled');
            }

            if (! Schema::hasColumn('branches', 'barcode_keystroke_threshold_ms')) {
                // Max gap between keystrokes still counted as machine input.
                $table->unsignedSmallInteger('barcode_keystroke_threshold_ms')->default(50)->after('barcode_min_length');
            }
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            foreach (['barcode_keystroke_threshold_ms', 'barcode_min_length', 'barcode_scanner_enabled'] as $column) {
                if (Schema::hasColumn('branches', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
