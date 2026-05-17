<?php

namespace Tests\Unit\Support;

use App\Support\CustomPaginator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class CustomPaginatorTest extends TestCase
{
    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function makePaginator(
        array $items,
        int   $total,
        int   $perPage = 5,
        int   $currentPage = 1
    ): CustomPaginator
    {
        return new CustomPaginator(
            new Collection($items),
            $total,
            $perPage,
            $currentPage,
            ['path' => 'http://example.com/items']
        );
    }

    // ─── toArray() ────────────────────────────────────────────────────────────

    public function test_to_array_contains_required_keys(): void
    {
        $paginator = $this->makePaginator(['a', 'b', 'c'], 3);
        $result = $paginator->toArray();

        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('count', $result);
        $this->assertArrayHasKey('perPage', $result);
        $this->assertArrayHasKey('currentPage', $result);
        $this->assertArrayHasKey('totalPages', $result);
        $this->assertArrayHasKey('links', $result);
    }

    public function test_to_array_total_reflects_total_items(): void
    {
        $paginator = $this->makePaginator(['a', 'b'], 20, 5, 1);
        $result = $paginator->toArray();

        $this->assertSame(20, $result['total']);
    }

    public function test_to_array_count_reflects_items_on_current_page(): void
    {
        $paginator = $this->makePaginator(['a', 'b'], 20, 5, 1);
        $result = $paginator->toArray();

        $this->assertSame(2, $result['count']);
    }

    public function test_to_array_per_page_reflects_configured_value(): void
    {
        $paginator = $this->makePaginator(['a'], 10, 3, 1);
        $result = $paginator->toArray();

        $this->assertSame(3, $result['perPage']);
    }

    public function test_to_array_current_page_reflects_requested_page(): void
    {
        $paginator = $this->makePaginator(['a'], 10, 2, 3);
        $result = $paginator->toArray();

        $this->assertSame(3, $result['currentPage']);
    }

    public function test_to_array_total_pages_is_calculated_correctly(): void
    {
        // 10 items, 3 per page → 4 pages
        $paginator = $this->makePaginator(['a'], 10, 3, 1);
        $result = $paginator->toArray();

        $this->assertSame(4, $result['totalPages']);
    }

    // ─── linkCollection() ─────────────────────────────────────────────────────

    public function test_link_collection_returns_a_collection(): void
    {
        $paginator = $this->makePaginator(['a'], 10, 2, 1);
        $links = $paginator->linkCollection();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $links);
    }

    public function test_link_collection_marks_current_page_as_active(): void
    {
        $paginator = $this->makePaginator(['a', 'b'], 6, 2, 2);
        $links = $paginator->linkCollection();

        $activePage = $links->firstWhere('active', true);

        $this->assertNotNull($activePage);
        $this->assertSame('2', $activePage['label']);
    }

    public function test_link_collection_non_current_pages_are_not_active(): void
    {
        $paginator = $this->makePaginator(['a', 'b'], 6, 2, 1);
        $links = $paginator->linkCollection();

        $nonActive = $links->where('active', false)->where('url', '!=', null);

        foreach ($nonActive as $link) {
            $this->assertNotSame('1', $link['label']);
        }
    }
}
