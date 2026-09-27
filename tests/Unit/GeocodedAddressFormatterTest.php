<?php

namespace Tests\Unit;

use App\Support\GeocodedAddressFormatter;
use PHPUnit\Framework\TestCase;

class GeocodedAddressFormatterTest extends TestCase
{
    public function test_formats_nominatim_address_with_road_suburb_and_city(): void
    {
        $formatted = GeocodedAddressFormatter::formatNominatimAddress([
            'road' => 'Jalan Sultan Agung',
            'suburb' => 'Setiabudi',
            'city' => 'Jakarta Selatan',
            'state' => 'Daerah Khusus Ibukota Jakarta',
            'country' => 'Indonesia',
        ]);

        $this->assertSame('Jalan Sultan Agung, Setiabudi, Jakarta Selatan', $formatted);
    }

    public function test_formats_nominatim_address_with_suburb_and_city_only(): void
    {
        $formatted = GeocodedAddressFormatter::formatNominatimAddress([
            'suburb' => 'Ciputat Timur',
            'city' => 'Tangerang Selatan',
            'state' => 'Banten',
            'country' => 'Indonesia',
        ]);

        $this->assertSame('Ciputat Timur, Tangerang Selatan', $formatted);
    }

    public function test_formats_nominatim_address_with_neighbourhood_suburb_and_city(): void
    {
        $formatted = GeocodedAddressFormatter::formatNominatimAddress([
            'neighbourhood' => 'Guntur',
            'suburb' => 'Setiabudi',
            'city' => 'Jakarta Selatan',
            'state' => 'Daerah Khusus Ibukota Jakarta',
            'country' => 'Indonesia',
        ]);

        $this->assertSame('Guntur, Setiabudi, Jakarta Selatan', $formatted);
    }

    public function test_formats_nominatim_address_with_house_number_and_road(): void
    {
        $formatted = GeocodedAddressFormatter::formatNominatimAddress([
            'house_number' => '12',
            'road' => 'Jalan Sudirman',
            'suburb' => 'Karet Tengsin',
            'city' => 'Jakarta Pusat',
            'country' => 'Indonesia',
        ]);

        $this->assertSame('12 Jalan Sudirman, Karet Tengsin, Jakarta Pusat', $formatted);
    }

    public function test_skips_duplicate_nominatim_address_parts(): void
    {
        $formatted = GeocodedAddressFormatter::formatNominatimAddress([
            'suburb' => 'Setiabudi',
            'city_district' => 'Setiabudi',
            'city' => 'Jakarta Selatan',
            'country' => 'Indonesia',
        ]);

        $this->assertSame('Setiabudi, Jakarta Selatan', $formatted);
    }

    public function test_normalizes_client_address_from_browser_geocoder(): void
    {
        $normalized = GeocodedAddressFormatter::normalizeClientAddress(
            'Ciputat Timur, Tangerang Selatan, Banten, Indonesia',
        );

        $this->assertSame('Ciputat Timur, Tangerang Selatan', $normalized);
    }

    public function test_normalizes_client_address_with_island_and_country(): void
    {
        $normalized = GeocodedAddressFormatter::normalizeClientAddress(
            'Ciputat Timur, Tangerang Selatan, Jawa, Indonesia',
        );

        $this->assertSame('Ciputat Timur, Tangerang Selatan', $normalized);
    }

    public function test_normalizes_nominatim_display_name(): void
    {
        $normalized = GeocodedAddressFormatter::normalizeClientAddress(
            'Guntur, Setiabudi, Jakarta Selatan, Daerah Khusus Ibukota Jakarta, Indonesia',
        );

        $this->assertSame('Guntur, Setiabudi, Jakarta Selatan', $normalized);
    }

    public function test_normalizes_client_address_with_trailing_jakarta_province(): void
    {
        $normalized = GeocodedAddressFormatter::normalizeClientAddress(
            'Setiabudi, Jakarta Selatan, Jakarta, Indonesia',
        );

        $this->assertSame('Setiabudi, Jakarta Selatan', $normalized);
    }

    public function test_normalizes_client_address_preserves_single_meaningful_part(): void
    {
        $normalized = GeocodedAddressFormatter::normalizeClientAddress('Indonesia');

        $this->assertSame('Indonesia', $normalized);
    }
}
