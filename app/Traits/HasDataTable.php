<?php

declare(strict_types=1);

namespace App\Traits;

use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

trait HasDataTable
{
    use WithoutUrlPagination, WithPagination;

    /** @var int[] */
    public array $perPageOptions = [7, 15, 25, 50, 100, 200, 300, 500];

    /** @var string[] */
    public array $sort = [
        'column' => 'id',
        'direction' => 'desc',
    ];

    public string $search = '';

    public int $perPage = 7;

    /**
     * Additional properties that should trigger a page reset when updated.
     *
     * @return array<int, string>
     */
    abstract protected function getAdditionalPageResetProperties(): array;

    public function updating(string $property): void
    {
        $shouldResetPage = in_array(
            needle: $property,
            haystack: [
                'sort',
                'search',
                'perPage',
                ...$this->getAdditionalPageResetProperties(),
            ],
            strict: true,
        );

        if ($shouldResetPage) {
            $this->resetPage(); // Reset pagination to the first page
        }
    }

    protected function setSortProperty(string $column, string $direction): void
    {
        $this->sort = [
            'column' => $column,
            'direction' => $direction,
        ];
    }
}
