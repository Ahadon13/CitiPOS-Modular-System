<?php

declare(strict_types=1);

use App\Livewire\Documentation;
use App\Support\DocumentationContent;
use App\Support\DocumentationImages;
use App\Support\DocumentationPdf;
use Livewire\Livewire;

it('offers the manual as a download in English', function () {
    Livewire::test(Documentation::class)
        ->set('lang', 'en')
        ->call('downloadPdf')
        ->assertFileDownloaded('CitiPOS-User-Manual.pdf');
});

it('offers the manual as a download in Tagalog', function () {
    Livewire::test(Documentation::class)
        ->set('lang', 'tl')
        ->call('downloadPdf')
        ->assertFileDownloaded('CitiPOS-Gabay-ng-Gumagamit.pdf');
});

it('builds a real, multi-page PDF', function () {
    $path = DocumentationPdf::build('en');

    expect($path)->toBeFile();

    $pdf = file_get_contents($path);

    // Byte-wise on purpose: a PDF is binary, and the mb_* equivalents would be
    // reading compressed streams as if they were UTF-8 text.
    expect(str_starts_with($pdf, '%PDF-'))->toBeTrue()
        ->and(preg_match_all('#/Type\s*/Page[^s]#', $pdf))->toBeGreaterThan(30);
});

it('reuses the cached PDF instead of rendering again', function () {
    $first = DocumentationPdf::build('en');
    $stamp = filemtime($first);

    $second = DocumentationPdf::build('en');

    expect($second)->toBe($first)
        ->and(filemtime($second))->toBe($stamp);
});

it('lays out every chapter and section it is given', function () {
    $data = DocumentationPdf::viewData('en');

    expect($data['chapters'])->toHaveCount(count(DocumentationContent::categories()));

    foreach ($data['chapters'] as $index => $chapter) {
        $expected = DocumentationContent::sections($chapter['id']);

        expect($chapter['number'])->toBe($index + 1)
            ->and($chapter['title'])->not->toBe('')
            ->and($chapter['sections'])->toHaveCount(count($expected));

        foreach ($chapter['sections'] as $i => $section) {
            expect($section['number'])->toBe($chapter['number'].'.'.($i + 1))
                ->and($section['title'])->not->toBe('');
        }
    }
});

it('resolves a print-ready file for every screenshot', function () {
    $checked = 0;

    foreach (DocumentationContent::categories() as $category) {
        foreach (DocumentationContent::sections($category['id']) as $section) {
            foreach ($section['blocks'] as $block) {
                if (($block['type'] ?? null) !== 'image') {
                    continue;
                }

                $path = DocumentationImages::printPath($block['src']);

                expect($path)->not->toBeNull()
                    ->and($path)->toBeFile();

                $checked++;
            }
        }
    }

    expect($checked)->toBeGreaterThan(40);
});

it('shrinks screenshots well below their original weight', function () {
    $source = public_path('docs/img/dashboard.png');
    $print = DocumentationImages::printPath('dashboard.png');

    expect(getimagesize($print)[0])->toBeLessThanOrEqual(1100)
        ->and(filesize($print))->toBeLessThan(filesize($source));
});

it('ignores a screenshot that has gone missing rather than failing the render', function () {
    expect(DocumentationImages::printPath('this-file-does-not-exist.png'))->toBeNull();
});

it('carries the reference chapter that only the manual has', function () {
    $sections = DocumentationContent::sections('reference');

    expect(array_column($sections, 'id'))
        ->toBe(['shortcuts', 'roles', 'glossary', 'help']);
});

it('fits every screenshot inside the printable area', function () {
    // Regression: sizing images with `width: 100%` drew the tall cropped POS
    // panels ~380mm high on a 297mm page, so dompdf clipped them and the
    // bottom of those pictures never appeared in the manual.
    $oversized = [];

    foreach (DocumentationContent::categories() as $category) {
        foreach (DocumentationContent::sections($category['id']) as $section) {
            foreach ($section['blocks'] as $block) {
                if (($block['type'] ?? null) !== 'image') {
                    continue;
                }

                $box = DocumentationImages::printBox($block['src']);

                if ($box['width'] > DocumentationImages::MAX_WIDTH_MM
                    || $box['height'] > DocumentationImages::MAX_HEIGHT_MM) {
                    $oversized[] = sprintf('%s (%smm x %smm)', $block['src'], $box['width'], $box['height']);
                }
            }
        }
    }

    expect($oversized)->toBe([]);
});

it('keeps a screenshot at its original proportions', function () {
    // 418x886 -- the tall cart panel that used to overflow the page.
    $box = DocumentationImages::printBox('pos-cart-walkin.png');

    [$width, $height] = getimagesize(public_path('docs/img/pos-cart-walkin.png'));

    expect($box['height'])->toBe(DocumentationImages::MAX_HEIGHT_MM)
        ->and($box['width'] / $box['height'])->toBeGreaterThan($width / $height * 0.99)
        ->and($box['width'] / $box['height'])->toBeLessThan($width / $height * 1.01);
});
