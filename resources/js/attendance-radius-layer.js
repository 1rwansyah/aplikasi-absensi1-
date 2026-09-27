/**
 * Leaflet attendance radius layer — office circle + marker (rules: modular).
 */

const OFFICE_MARKER_HTML = `<span class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white shadow-lg ring-2 ring-white">K</span>`;

export function createOfficeMarkerIcon() {
    return L.divIcon({
        className: 'bg-transparent border-0',
        html: OFFICE_MARKER_HTML,
        iconSize: [36, 36],
        iconAnchor: [18, 18],
        popupAnchor: [0, -20],
    });
}

/**
 * @param {L.Map} map
 * @param {{ officeLatitude: number, officeLongitude: number, radiusMeters: number, radiusLabel?: string }} geofence
 */
export function addAttendanceRadiusLayer(map, geofence) {
    const lat = Number(geofence.officeLatitude);
    const lng = Number(geofence.officeLongitude);
    const radius = Number(geofence.radiusMeters);

    const officeMarker = L.marker([lat, lng], {
        icon: createOfficeMarkerIcon(),
    }).addTo(map);

    const label = geofence.radiusLabel || `${radius} meter`;
    officeMarker.bindPopup(
        `<div class="text-sm">
            <p class="font-semibold text-gray-900">Lokasi Kantor</p>
            <p class="mt-1 text-gray-600">Radius absensi: ${label}</p>
        </div>`,
    );

    const radiusCircle = L.circle([lat, lng], {
        color: '#2563eb',
        fillColor: '#3b82f6',
        fillOpacity: 0.18,
        weight: 2,
        radius,
    }).addTo(map);

    return { officeMarker, radiusCircle };
}
