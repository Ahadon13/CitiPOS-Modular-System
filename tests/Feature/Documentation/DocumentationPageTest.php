<?php

declare(strict_types=1);

use App\Livewire\Documentation;
use App\Support\DocumentationContent;
use Livewire\Livewire;

it('is reachable without signing in', function () {
    $this->get(route('documentation'))
        ->assertOk()
        ->assertSee('How to use CitiPOS');
});

it('links to the manual from the landing page', function () {
    $this->get(route('welcome'))
        ->assertOk()
        ->assertSee('Documentation')
        ->assertSee(route('documentation'));
});

it('renders every category in both languages', function () {
    foreach (DocumentationContent::categories() as $category) {
        foreach (array_keys(DocumentationContent::languages()) as $lang) {
            $component = Livewire::test(Documentation::class)
                ->set('lang', $lang)
                ->call('setCategory', $category['id'])
                ->assertOk()
                ->assertSee($category[$lang]);

            // Every section heading for the category must reach the page.
            foreach (DocumentationContent::sections($category['id']) as $section) {
                $component->assertSee($section[$lang], escape: false);
            }
        }
    }
});

it('falls back to safe defaults for unknown category or language', function () {
    Livewire::withQueryParams(['c' => 'nope', 'lang' => 'fr'])
        ->test(Documentation::class)
        ->assertSet('category', 'getting-started')
        ->assertSet('lang', 'en');

    Livewire::test(Documentation::class)
        ->call('setCategory', 'nope')
        ->assertSet('category', 'getting-started')
        ->call('setLang', 'fr')
        ->assertSet('lang', 'en');
});

it('ships every screenshot it references', function () {
    $missing = [];

    foreach (DocumentationContent::categories() as $category) {
        foreach (DocumentationContent::sections($category['id']) as $section) {
            foreach ($section['blocks'] as $block) {
                if (($block['type'] ?? null) !== 'image') {
                    continue;
                }

                if (! is_file(public_path('docs/img/'.$block['src']))) {
                    $missing[] = $block['src'];
                }
            }
        }
    }

    expect($missing)->toBe([]);
});

it('translates every string it renders', function () {
    $untranslated = [];

    $walk = function (array $node, string $trail) use (&$walk, &$untranslated): void {
        if (array_key_exists('en', $node) && ! array_key_exists('tl', $node)) {
            $untranslated[] = $trail;
        }

        foreach (['blocks', 'items', 'head'] as $key) {
            foreach ($node[$key] ?? [] as $i => $child) {
                if (is_array($child)) {
                    $walk($child, "{$trail}.{$key}[{$i}]");
                }
            }
        }

        foreach ($node['rows'] ?? [] as $r => $row) {
            foreach ($row as $c => $cell) {
                if (is_array($cell)) {
                    $walk($cell, "{$trail}.rows[{$r}][{$c}]");
                }
            }
        }
    };

    foreach (DocumentationContent::categories() as $category) {
        $walk($category, $category['id']);

        foreach (DocumentationContent::sections($category['id']) as $section) {
            $walk($section, $category['id'].'/'.$section['id']);
        }
    }

    expect($untranslated)->toBe([]);
});
