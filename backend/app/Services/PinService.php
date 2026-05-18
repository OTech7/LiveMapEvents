<?php

namespace App\Services;

use App\Models\Pin;
use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class PinService
{
    public function getNearby(array $data): LengthAwarePaginator
    {
        $cacheKey = $this->makeCacheKey($data);

        return Cache::remember($cacheKey, 30, function () use ($data) {
            $point = Point::makeGeodetic($data['lng'], $data['lat']);

            $query = Pin::query()
                ->distanceSphere('location', $point, $data['radius'])
                ->orderByDistanceSphere('location', $point);

            if (!empty($data['types'])) {
                $query->whereIn('type', $data['types']);
            }

            if (!empty($data['categories'])) {
                $query->whereIn('category_id', $data['categories']);
            }

            return $query->paginate(20);
        });
    }

    /**
     * Build a stable cache key that includes all query parameters including
     * the current page, so each page has its own cached result set.
     */
    private function makeCacheKey(array $data): string
    {
        // Normalise to a sorted array so key order never causes cache misses.
        ksort($data);

        return 'pins:' . md5(json_encode($data));
    }
}
