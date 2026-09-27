<?php

namespace Tests\Unit;

use App\Services\AttendanceLocationService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AttendanceLocationServiceTest extends TestCase
{
    public function test_resolve_address_normalizes_client_address(): void
    {
        $service = app(AttendanceLocationService::class);

        $address = $service->resolveAddress(
            -6.301234,
            106.765432,
            'Ciputat Timur, Tangerang Selatan, Jawa, Indonesia',
        );

        $this->assertSame('Ciputat Timur, Tangerang Selatan', $address);
    }

    public function test_reverse_geocode_builds_compact_address_from_nominatim_address(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                'display_name' => 'Ciputat Timur, Tangerang Selatan, Banten, Indonesia',
                'address' => [
                    'suburb' => 'Ciputat Timur',
                    'city' => 'Tangerang Selatan',
                    'state' => 'Banten',
                    'country' => 'Indonesia',
                ],
            ]),
        ]);

        $service = app(AttendanceLocationService::class);

        $address = $service->resolveAddress(-6.301234, 106.765432);

        $this->assertSame('Ciputat Timur, Tangerang Selatan', $address);
    }

    public function test_reverse_geocode_falls_back_to_normalized_display_name(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                'display_name' => 'Jalan Sultan Agung, Setiabudi, Jakarta Selatan, Daerah Khusus Ibukota Jakarta, Indonesia',
                'address' => [],
            ]),
        ]);

        $service = app(AttendanceLocationService::class);

        $address = $service->resolveAddress(-6.225234, 106.825432);

        $this->assertSame('Jalan Sultan Agung, Setiabudi, Jakarta Selatan', $address);
    }

    public function test_reverse_geocode_falls_back_to_coordinates_when_request_fails(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([], 500),
        ]);

        $service = app(AttendanceLocationService::class);

        $address = $service->resolveAddress(-6.301234, 106.765432);

        $this->assertSame('Koordinat: -6.301234, 106.765432', $address);
    }
}
