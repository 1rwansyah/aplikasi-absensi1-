<?php

namespace App\Support;

class GeocodedAddressFormatter
{
    private const MAX_LENGTH = 500;

    /**
     * @param  array<string, mixed>  $address
     */
    public static function formatNominatimAddress(array $address): ?string
    {
        $parts = [];

        $street = self::pickStreet($address);
        if ($street !== null) {
            $parts[] = $street;
        }

        if ($street === null) {
            $specific = self::pickFirst($address, ['neighbourhood', 'quarter', 'hamlet', 'village', 'residential']);
            if ($specific !== null) {
                $parts[] = $specific;
            }
        }

        $mid = self::pickFirst($address, ['suburb', 'city_district', 'district']);
        if ($mid !== null && ! self::containsPart($parts, $mid)) {
            $parts[] = $mid;
        }

        $city = self::pickFirst($address, ['city', 'town', 'municipality', 'county']);
        if ($city !== null && ! self::containsPart($parts, $city)) {
            $parts[] = $city;
        }

        if ($parts === []) {
            return null;
        }

        return self::truncate(implode(', ', $parts));
    }

    public static function normalizeClientAddress(string $address): string
    {
        $address = trim($address);

        if ($address === '') {
            return '';
        }

        $parts = array_values(array_filter(
            array_map(trim(...), explode(',', $address)),
            static fn (string $part): bool => $part !== '',
        ));

        if ($parts === []) {
            return '';
        }

        $original = $parts;

        while (count($parts) > 1 && self::isGenericSuffix(end($parts))) {
            array_pop($parts);
        }

        if (count($parts) > 1 && self::isBareJakartaProvince(end($parts))) {
            array_pop($parts);
        }

        $parts = self::deduplicateParts($parts);

        if ($parts === []) {
            $parts = self::deduplicateParts($original);
        }

        return self::truncate(implode(', ', $parts));
    }

    /**
     * @param  array<string, mixed>  $address
     */
    private static function pickStreet(array $address): ?string
    {
        $road = self::pickFirst($address, ['road', 'pedestrian', 'footway', 'residential', 'path', 'cycleway']);

        if ($road === null) {
            return null;
        }

        $houseNumber = isset($address['house_number']) ? trim((string) $address['house_number']) : '';

        if ($houseNumber !== '') {
            return $houseNumber.' '.$road;
        }

        return $road;
    }

    /**
     * @param  array<string, mixed>  $address
     * @param  list<string>  $keys
     */
    private static function pickFirst(array $address, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (! isset($address[$key])) {
                continue;
            }

            $value = trim((string) $address[$key]);

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $parts
     */
    private static function containsPart(array $parts, string $candidate): bool
    {
        foreach ($parts as $part) {
            if (self::samePart($part, $candidate)) {
                return true;
            }
        }

        return false;
    }

    private static function samePart(string $left, string $right): bool
    {
        return self::normalizePart($left) === self::normalizePart($right);
    }

    private static function normalizePart(string $part): string
    {
        return mb_strtolower(trim($part));
    }

    private static function isGenericSuffix(string $part): bool
    {
        $normalized = self::normalizePart($part);

        if (in_array($normalized, ['indonesia', 'java', 'jawa', 'republic of indonesia'], true)) {
            return true;
        }

        return in_array($normalized, self::provinceNames(), true);
    }

    private static function isBareJakartaProvince(string $part): bool
    {
        $normalized = self::normalizePart($part);

        return in_array($normalized, ['jakarta', 'dki jakarta', 'jakarta raya'], true);
    }

    /**
     * @return list<string>
     */
    private static function provinceNames(): array
    {
        return [
            'aceh',
            'bali',
            'banten',
            'bengkulu',
            'daerah istimewa yogyakarta',
            'daerah khusus ibukota jakarta',
            'di yogyakarta',
            'dki jakarta',
            'gorontalo',
            'jambi',
            'jawa barat',
            'jawa tengah',
            'jawa timur',
            'kalimantan barat',
            'kalimantan selatan',
            'kalimantan tengah',
            'kalimantan timur',
            'kalimantan utara',
            'kepulauan bangka belitung',
            'kepulauan riau',
            'lampung',
            'maluku',
            'maluku utara',
            'nusa tenggara barat',
            'nusa tenggara timur',
            'papua',
            'papua barat',
            'papua barat daya',
            'papua pegunungan',
            'papua selatan',
            'papua tengah',
            'riau',
            'sulawesi barat',
            'sulawesi selatan',
            'sulawesi tengah',
            'sulawesi tenggara',
            'sulawesi utara',
            'sumatera barat',
            'sumatera selatan',
            'sumatera utara',
        ];
    }

    /**
     * @param  list<string>  $parts
     * @return list<string>
     */
    private static function deduplicateParts(array $parts): array
    {
        $unique = [];

        foreach ($parts as $part) {
            if (self::containsPart($unique, $part)) {
                continue;
            }

            $unique[] = $part;
        }

        return $unique;
    }

    private static function truncate(string $address): string
    {
        return mb_substr($address, 0, self::MAX_LENGTH);
    }
}
