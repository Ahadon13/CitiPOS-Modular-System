<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Print-ready copies of the manual screenshots.
 *
 * The originals are ~1850px wide PNGs totalling 7MB. Embedding those directly
 * would produce a PDF nobody wants to download and take a long time to build,
 * so each one is downscaled once to print resolution and re-encoded as JPEG --
 * the one format dompdf can embed without re-compressing it.
 *
 * Conversions are cached on disk and keyed by the source file's modification
 * time, so replacing a screenshot regenerates only that one.
 */
final class DocumentationImages
{
    /**
     * The box a screenshot is fitted into on the page. Narrower than the
     * 182mm text column so figures read as figures, and short enough that two
     * of them plus their captions still share a page.
     */
    public const MAX_WIDTH_MM = 160.0;

    public const MAX_HEIGHT_MM = 115.0;

    /**
     * Roughly 150dpi across the 180mm text column of an A4 page. Going wider
     * only inflates the file; the screenshots are UI, not photographs.
     */
    private const PRINT_WIDTH = 1100;

    private const JPEG_QUALITY = 82;

    /**
     * A filesystem path dompdf can embed, or null when the screenshot is gone.
     */
    public static function printPath(string $filename): ?string
    {
        $source = public_path('docs/img/'.$filename);

        if (! is_file($source)) {
            return null;
        }

        if (! function_exists('imagecreatefrompng')) {
            return $source;
        }

        $cached = self::cacheDirectory()
            .'/'.pathinfo($filename, PATHINFO_FILENAME)
            .'-'.mb_substr(md5($filename.'|'.filemtime($source)), 0, 8).'.jpg';

        if (is_file($cached)) {
            return $cached;
        }

        return self::convert($source, $cached) ? $cached : $source;
    }

    /**
     * The path plus the size to draw it at, in millimetres.
     *
     * Sizing has to happen here rather than in CSS. A plain `width: 100%`
     * draws the tall, cropped screenshots -- the cart panels are 418x886 --
     * nearly 400mm high on a 297mm page, so dompdf clips them and the bottom
     * of the picture simply never appears.
     *
     * @return array{path: string, width: float, height: float}|null
     */
    public static function printBox(string $filename): ?array
    {
        $path = self::printPath($filename);

        if ($path === null) {
            return null;
        }

        $size = @getimagesize($path);

        if ($size === false || $size[0] < 1 || $size[1] < 1) {
            return null;
        }

        $aspect = $size[0] / $size[1];

        $width = min(self::MAX_WIDTH_MM, self::MAX_HEIGHT_MM * $aspect);

        return [
            'path' => $path,
            'width' => round($width, 1),
            'height' => round($width / $aspect, 1),
        ];
    }

    /**
     * A fingerprint of every screenshot, so a replaced image invalidates any
     * PDF built from the old one.
     */
    public static function fingerprint(): string
    {
        $parts = [];

        foreach (glob(public_path('docs/img/*')) ?: [] as $path) {
            $parts[] = basename($path).':'.filemtime($path);
        }

        return md5(implode('|', $parts));
    }

    public static function cacheDirectory(): string
    {
        $directory = storage_path('app/docs/print-img');

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        return $directory;
    }

    private static function convert(string $source, string $destination): bool
    {
        $image = @imagecreatefrompng($source);

        if ($image === false) {
            return false;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        if ($width > self::PRINT_WIDTH) {
            $scaled = imagescale($image, self::PRINT_WIDTH);

            if ($scaled !== false) {
                imagedestroy($image);
                $image = $scaled;
                $width = imagesx($image);
                $height = imagesy($image);
            }
        }

        // JPEG has no alpha channel. Compose onto the same near-black the
        // screenshots use so transparent corners do not come out white.
        $canvas = imagecreatetruecolor($width, $height);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 5, 6, 9));
        imagecopy($canvas, $image, 0, 0, 0, 0, $width, $height);

        $ok = imagejpeg($canvas, $destination, self::JPEG_QUALITY);

        imagedestroy($image);
        imagedestroy($canvas);

        return $ok;
    }
}
