<?php

namespace App\Support;

use Illuminate\Pagination\LengthAwarePaginator;

class CustomPaginator extends LengthAwarePaginator
{

    /**
     * Get the paginator links as a collection (for JSON responses).
     *
     * @return \Illuminate\Support\Collection
     */
    public function linkCollection()
    {
        return collect($this->elements())->flatMap(function ($item) {
            if (!is_array($item)) {
                return [['url' => null, 'label' => '...', 'active' => false]];
            }

            return collect($item)->map(function ($url, $page) {
                return [
                    'url' => $url,
                    'label' => (string) $page,
                    'active' => $this->currentPage() === $page,
                ];
            });
        });
    }

    public function toArray()
    {
        return [
            'items'       => $this->items->toArray(),
            'total'       => $this->total(),
            'count'       => $this->count(),
            'perPage'     => $this->perPage(),
            'currentPage' => $this->currentPage(),
            'totalPages'  => $this->lastPage(),
            'links'       => $this->linkCollection()
        ];
    }
}