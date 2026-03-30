<?php

namespace App\Services;

use App\Models\Pin;
use Illuminate\Support\Facades\Cache;
use Clickbar\Magellan\Data\Geometries\Point;

class PinService
{
    public function getNearby(array $data)
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

    private function makeCacheKey(array $data): string
    {
        return 'pins:' . md5(json_encode($data));
    }
}