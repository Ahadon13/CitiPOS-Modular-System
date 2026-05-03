<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\Product\CategoryType;
use App\Imports\ProductsImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Throwable;

final class ImportProductsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 3600;

    public bool $failOnTimeout = true;

    public function __construct(
        private readonly string $filePath,
        private readonly int $branchId,
        private readonly int $userId,
        private readonly string $productCategoryType,
        private readonly ?string $originalFileName = null,
    ) {}

    public function handle(): void
    {
        $disk = Storage::disk('local');

        if (! $disk->exists($this->filePath)) {
            throw new RuntimeException("Import file was not found: {$this->filePath}");
        }

        try {
            Excel::import(
                new ProductsImport(
                    $this->branchId,
                    $this->userId,
                    CategoryType::from($this->productCategoryType),
                ),
                $disk->path($this->filePath),
            );

            $disk->delete($this->filePath);
        } catch (Throwable $e) {
            Log::error('Queued product import failed', [
                'message' => $e->getMessage(),
                'file' => $this->originalFileName,
                'stored_file' => $this->filePath,
                'user_id' => $this->userId,
                'branch_id' => $this->branchId,
                'product_category_type' => $this->productCategoryType,
            ]);

            throw $e;
        }
    }
}
