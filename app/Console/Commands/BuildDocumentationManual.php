<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\DocumentationContent;
use App\Support\DocumentationPdf;
use Illuminate\Console\Command;

/**
 * Warms the manual PDF cache.
 *
 * Optional: the download route builds the file on demand anyway. Running this
 * as part of a deploy just moves the first render -- screenshot conversion
 * included, which is the slow part -- off the first person to click Download.
 */
final class BuildDocumentationManual extends Command
{
    protected $signature = 'docs:manual';

    protected $description = 'Pre-render the downloadable user manual PDF in every language';

    public function handle(): int
    {
        foreach (array_keys(DocumentationContent::languages()) as $lang) {
            $started = microtime(true);
            $path = DocumentationPdf::build($lang);

            $this->components->info(sprintf(
                '%s  %s  %.1f MB  %.1fs',
                $lang,
                basename($path),
                filesize($path) / 1048576,
                microtime(true) - $started,
            ));
        }

        return self::SUCCESS;
    }
}
