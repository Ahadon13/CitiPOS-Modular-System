<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Support\DocumentationContent;
use App\Support\DocumentationPdf;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The public user manual at /documentation.
 *
 * Deliberately unauthenticated: staff need to be able to reach it from the
 * landing page before they have signed in, and from a phone at the counter.
 * It reads nothing from the database -- every word and screenshot is static
 * content, so it exposes no customer or sales data.
 */
final class Documentation extends Component
{
    /** Kept in the URL so a section can be linked to or bookmarked. */
    #[Url(as: 'c', keep: false)]
    public string $category = 'getting-started';

    #[Url(as: 'lang', keep: false)]
    public string $lang = 'en';

    public function mount(): void
    {
        if (! DocumentationContent::isValidCategory($this->category)) {
            $this->category = 'getting-started';
        }

        if (! array_key_exists($this->lang, DocumentationContent::languages())) {
            $this->lang = 'en';
        }
    }

    public function setCategory(string $category): void
    {
        if (DocumentationContent::isValidCategory($category)) {
            $this->category = $category;
        }
    }

    public function setLang(string $lang): void
    {
        if (array_key_exists($lang, DocumentationContent::languages())) {
            $this->lang = $lang;
        }
    }

    /**
     * The whole manual as one A4 PDF, in the language currently on screen.
     *
     * Rendering is cached, so this is only slow the first time after the
     * content or a screenshot changes.
     */
    public function downloadPdf(): StreamedResponse
    {
        return DocumentationPdf::download($this->lang);
    }

    /**
     * @return list<array<string, mixed>>
     */
    #[Computed]
    public function categories(): array
    {
        return DocumentationContent::categories();
    }

    /**
     * @return list<array<string, mixed>>
     */
    #[Computed]
    public function sections(): array
    {
        return DocumentationContent::sections($this->category);
    }

    /**
     * @return array<string, mixed>|null
     */
    #[Computed]
    public function activeCategory(): ?array
    {
        foreach ($this->categories as $category) {
            if ($category['id'] === $this->category) {
                return $category;
            }
        }

        return null;
    }

    public function render()
    {
        return view('documentation')->layout('components.layouts.guest');
    }
}
