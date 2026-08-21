<?php

declare(strict_types=1);

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The whole user manual as one downloadable A4 document.
 *
 * Built from exactly the same content as the web page, so the two can never
 * disagree. Rendering ~60 pages with 50 screenshots takes long enough that the
 * result is cached on disk; the cache key covers the content, the template and
 * every screenshot, so any edit produces a new file on the next request.
 */
final class DocumentationPdf
{
    private const TEMPLATE = 'pdf.documentation';

    /**
     * A download response, rendering the PDF only if the cache has no copy.
     *
     * Streamed rather than returned as a plain response because Livewire only
     * converts StreamedResponse and BinaryFileResponse into browser downloads.
     */
    public static function download(string $lang): StreamedResponse
    {
        $path = self::build($lang);
        $filename = $lang === 'tl'
            ? 'CitiPOS-Gabay-ng-Gumagamit.pdf'
            : 'CitiPOS-User-Manual.pdf';

        return response()->streamDownload(
            fn () => readfile($path),
            $filename,
            ['Content-Type' => 'application/pdf'],
        );
    }

    /**
     * Render the manual if needed and return the path to the cached file.
     */
    public static function build(string $lang): string
    {
        $path = self::cachePath($lang);

        if (is_file($path)) {
            return $path;
        }

        self::purgeStaleCache($lang);

        $pdf = Pdf::loadView(self::TEMPLATE, self::viewData($lang))
            ->setPaper('a4', 'portrait')
            ->setOption('isRemoteEnabled', false)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');

        file_put_contents($path, $pdf->output());

        return $path;
    }

    /**
     * The content, flattened into numbered chapters with print-ready images.
     *
     * @return array<string, mixed>
     */
    public static function viewData(string $lang): array
    {
        $chapters = [];

        foreach (DocumentationContent::categories() as $index => $category) {
            $chapterNumber = $index + 1;
            $sections = [];

            foreach (DocumentationContent::sections($category['id']) as $i => $section) {
                $sections[] = [
                    'number' => $chapterNumber.'.'.($i + 1),
                    'title' => self::pick($section, $lang),
                    'blocks' => self::prepareBlocks($section['blocks'], $lang),
                ];
            }

            $chapters[] = [
                'number' => $chapterNumber,
                'id' => $category['id'],
                'title' => self::pick($category, $lang),
                'blurb' => self::pick($category, $lang, 'blurb'),
                'sections' => $sections,
            ];
        }

        return [
            'lang' => $lang,
            'chapters' => $chapters,
            'generatedOn' => now()->format('j F Y'),
        ];
    }

    /**
     * Resolve every block down to plain strings and image paths, so the
     * template only has to lay things out.
     *
     * @param  list<array<string, mixed>>  $blocks
     * @return list<array<string, mixed>>
     */
    private static function prepareBlocks(array $blocks, string $lang): array
    {
        $prepared = [];

        foreach ($blocks as $block) {
            $type = $block['type'] ?? 'text';

            $resolved = ['type' => $type, 'text' => self::pick($block, $lang)];

            if ($type === 'image') {
                $box = DocumentationImages::printBox($block['src']);

                // A screenshot that has gone missing drops out of the PDF
                // rather than breaking the render.
                if ($box === null) {
                    continue;
                }

                $resolved += $box;
            }

            if ($type === 'steps' || $type === 'list') {
                $resolved['items'] = array_map(
                    fn (array $item): string => self::pick($item, $lang),
                    $block['items'],
                );
            }

            if ($type === 'table') {
                $resolved['head'] = array_map(
                    fn (array $cell): string => self::pick($cell, $lang),
                    $block['head'],
                );
                $resolved['rows'] = array_map(
                    fn (array $row): array => array_map(
                        fn (array $cell): string => self::pick($cell, $lang),
                        $row,
                    ),
                    $block['rows'],
                );
            }

            $prepared[] = $resolved;
        }

        return $prepared;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private static function pick(array $item, string $lang, ?string $key = null): string
    {
        if ($key !== null) {
            return (string) ($item[$key.'_'.$lang] ?? $item[$key.'_en'] ?? '');
        }

        return (string) ($item[$lang] ?? $item['en'] ?? '');
    }

    private static function cachePath(string $lang): string
    {
        $directory = storage_path('app/docs');

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        return $directory.'/citipos-manual-'.$lang.'-'.self::fingerprint().'.pdf';
    }

    /**
     * Delete copies built from older content so the cache cannot grow without
     * bound as the manual is edited.
     */
    private static function purgeStaleCache(string $lang): void
    {
        foreach (glob(storage_path('app/docs/citipos-manual-'.$lang.'-*.pdf')) ?: [] as $stale) {
            @unlink($stale);
        }
    }

    /**
     * Changes to the words, the layout or any screenshot must all invalidate
     * a previously built PDF.
     */
    private static function fingerprint(): string
    {
        $sources = glob(app_path('Support/Documentation/*.php')) ?: [];
        $sources[] = app_path('Support/DocumentationContent.php');
        $sources[] = resource_path('views/pdf/documentation.blade.php');

        $parts = [];

        foreach ($sources as $source) {
            $parts[] = basename($source).':'.(is_file($source) ? filemtime($source) : 0);
        }

        $parts[] = DocumentationImages::fingerprint();

        return mb_substr(md5(implode('|', $parts)), 0, 12);
    }
}
