/**
 * Geo distance helpers — mirrors App\Support\GeoDistance (rules: reusable).
 */

const EARTH_RADIUS_METERS = 6371000;

export function distanceMeters(lat1, lng1, lat2, lng2) {
    const latFrom = (lat1 * Math.PI) / 180;
    const latTo = (lat2 * Math.PI) / 180;
    const latDelta = ((lat2 - lat1) * Math.PI) / 180;
    const lngDelta = ((lng2 - lng1) * Math.PI) / 180;

    const a = Math.sin(latDelta / 2) ** 2
        + Math.cos(latFrom) * Math.cos(latTo) * Math.sin(lngDelta / 2) ** 2;

    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    return EARTH_RADIUS_METERS * c;
}

export function isWithinRadius(lat, lng, centerLat, centerLng, radiusMeters) {
    return distanceMeters(lat, lng, centerLat, centerLng) <= radiusMeters;
}

export function formatDistance(meters) {
    if (meters >= 1000 && meters % 1000 === 0) {
        return `${meters / 1000} KM`;
    }

    if (meters >= 1000) {
        return `${(meters / 1000).toFixed(2)} KM`;
    }

    return `${Math.round(meters)} meter`;
}
