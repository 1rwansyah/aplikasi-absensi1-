@props([
    'mapId',
    'officeLat',
    'officeLng',
    'radiusMeters',
    'editable' => false,
    'trackUser' => false,
    'statusTarget' => null,
    'heightClass' => 'h-80',
])

<div
    id="{{ $mapId }}"
    data-geofence-map
    data-office-lat="{{ $officeLat }}"
    data-office-lng="{{ $officeLng }}"
    data-radius="{{ $radiusMeters }}"
    data-editable="{{ $editable ? '1' : '0' }}"
    data-track-user="{{ $trackUser ? '1' : '0' }}"
    @if ($statusTarget) data-status-target="{{ $statusTarget }}" @endif
    class="z-0 {{ $heightClass }} w-full rounded-xl border border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-gray-900/50"
></div>
