<?php

namespace App\Services;

use App\Support\GeocodedAddressFormatter;
use Illuminate\Support\Facades\Http;

class AttendanceLocationService
{
    public function resolveAddress(?float $latitude, ?float $longitude, ?string $clientAddress = null): ?string
    {
        if (filled($clientAddress)) {
            return GeocodedAddressFormatter::normalizeClientAddress($clientAddress);
        }

        if ($latitude === null || $longitude === null) {
            return null;
        }

        return $this->reverseGeocode($latitude, $longitude);
    }

    private function reverseGeocode(float $latitude, float $longitude): ?string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => config('app.name', 'AbsensiApp').'/1.0',
                'Accept-Language' => 'id',
            ])
                ->timeout(6)
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'format' => 'jsonv2',
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'zoom' => 18,
                    'addressdetails' => 1,
                ]);

            if (! $response->successful()) {
                return $this->coordinatesFallback($latitude, $longitude);
            }

            $address = $response->json('address');

            if (is_array($address) && $address !== []) {
                $formatted = GeocodedAddressFormatter::formatNominatimAddress($address);

                if (filled($formatted)) {
                    return $formatted;
                }
            }

            $displayName = $response->json('display_name');

            if (filled($displayName)) {
                return GeocodedAddressFormatter::normalizeClientAddress($displayName);
            }
        } catch (\Throwable) {
            // Fall through to coordinate label.
        }

        return $this->coordinatesFallback($latitude, $longitude);
    }

    private function coordinatesFallback(float $latitude, float $longitude): string
    {
        return sprintf('Koordinat: %.6f, %.6f', $latitude, $longitude);
    }
}
