/**
 * Attendance GPS capture — browser Geolocation API with optional client reverse geocode.
 */

import {
    geolocationErrorNotice,
    VERIFICATION_NOTICES,
} from './attendance-verification-messages.js';

const GEO_OPTIONS = {
    enableHighAccuracy: true,
    timeout: 12000,
    // Reuse a recent fix so page preload can make modal open much faster.
    maximumAge: 30000,
};

const REVERSE_GEOCODE_TIMEOUT_MS = 1500;

function locationError(notice) {
    const error = new Error(notice.message);
    error.verificationNotice = notice;

    return error;
}

async function assertLocationPermissionAllowed() {
    if (!navigator.permissions?.query) {
        return;
    }

    try {
        const status = await navigator.permissions.query({ name: 'geolocation' });
        if (status.state === 'denied') {
            throw locationError(geolocationErrorNotice({ code: 1 }));
        }
    } catch (error) {
        if (error?.verificationNotice) {
            throw error;
        }
        // Some browsers reject geolocation permission queries; ignore and fall back.
    }
}

function captureBrowserCoordinates() {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation?.getCurrentPosition) {
            reject(locationError({
                ...VERIFICATION_NOTICES.gpsUnavailable,
                message: 'Browser ini tidak mendukung akses lokasi. Gunakan browser terbaru seperti Chrome atau Edge. Absensi belum tercatat.',
            }));
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                resolve({
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                    withinRadius: true,
                });
            },
            (error) => reject(locationError(geolocationErrorNotice(error))),
            GEO_OPTIONS,
        );
    });
}

/**
 * Optional client-side reverse geocode (CORS-friendly). Server also resolves if empty.
 */
export async function reverseGeocodeClient(latitude, longitude) {
    try {
        const url = new URL('https://api.bigdatacloud.net/data/reverse-geocode-client');
        url.searchParams.set('latitude', String(latitude));
        url.searchParams.set('longitude', String(longitude));
        url.searchParams.set('localityLanguage', 'id');

        const response = await fetch(url.toString());
        if (!response.ok) {
            return null;
        }

        const data = await response.json();
        const parts = [
            data.locality,
            data.city,
            data.principalSubdivision,
            data.countryName,
        ].filter(Boolean);

        return parts.length ? parts.join(', ') : data.locality || null;
    } catch {
        return null;
    }
}

async function reverseGeocodeWithTimeout(latitude, longitude, timeoutMs = REVERSE_GEOCODE_TIMEOUT_MS) {
    return Promise.race([
        reverseGeocodeClient(latitude, longitude),
        new Promise((resolve) => {
            window.setTimeout(() => resolve(null), timeoutMs);
        }),
    ]);
}

/**
 * Resolves coordinates (and optional address) for attendance submission.
 *
 * @param {{ mapId?: string, requiresGeofence?: boolean }} options
 * @returns {Promise<{ latitude: number, longitude: number, accuracy: number, withinRadius: boolean, location: string|null }>}
 */
export async function resolveAttendanceLocation({ mapId = '', requiresGeofence = false } = {}) {
    await assertLocationPermissionAllowed();

    let result;

    if (mapId && window.GeofenceMap?.getInstance(mapId)) {
        const instance = window.GeofenceMap.getInstance(mapId);
        const tracked = await instance.trackUserLocation();
        result = {
            latitude: tracked.latitude,
            longitude: tracked.longitude,
            accuracy: tracked.accuracy,
            withinRadius: tracked.withinRadius,
        };
    } else {
        result = await captureBrowserCoordinates();
    }

    if (requiresGeofence && !result.withinRadius) {
        return { ...result, location: null };
    }

    // Do not block absensi readiness on slow reverse geocode.
    const location = await reverseGeocodeWithTimeout(result.latitude, result.longitude);

    return { ...result, location };
}
