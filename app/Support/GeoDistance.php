<?php

namespace App\Support;

final class GeoDistance
{
    private const EARTH_RADIUS_METERS = 6371000;

    public static function distanceMeters(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2,
    ): float {
        $latFrom = deg2rad($lat1);
        $latTo = deg2rad($lat2);
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lngDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_METERS * $c;
    }

    public static function isWithinRadius(
        float $lat,
        float $lng,
        float $centerLat,
        float $centerLng,
        int $radiusMeters,
    ): bool {
        return self::distanceMeters($lat, $lng, $centerLat, $centerLng) <= $radiusMeters;
    }
}
