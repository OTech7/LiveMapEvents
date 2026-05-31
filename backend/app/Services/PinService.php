<?php

namespace App\Services;

use App\Models\Pin;
use Clickbar\Magellan\Data\Geometries\Point;
use Clickbar\Magellan\Database\PostgisFunctions\ST;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class PinService
{
    public function getNearby(array $data): LengthAwarePaginator
    {
        $cacheKey = $this->makeCacheKey($data);

        return Cache::remember($cacheKey, (int)config('services.pins.cache_ttl', 30), function () use ($data) {
            $point = Point::makeGeodetic($data['lat'], $data['lng']);

            // Exclude pins belonging to frozen venues. We use whereIn rather
            // than whereHas because whereHas conflicts with Magellan's spatial
            // query builder. Pins with no venue (event-only pins) are kept.
            $unfrozenVenueIds = \App\Models\Venue::whereNull('frozen_at')->pluck('id');

            // ST::distanceSphere returns a MagellanNumericExpression (implements
            // Expression) — pass it as the column argument in where/orderBy.
            $distanceExpr = ST::distanceSphere('location', $point);

            $query = Pin::query()
                ->where($distanceExpr, '<=', $data['radius'])
                ->orderBy($distanceExpr)
                ->where(function ($q) use ($unfrozenVenueIds) {
                    $q->whereNull('venue_id')
                        ->orWhereIn('venue_id', $unfrozenVenueIds);
                });

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
