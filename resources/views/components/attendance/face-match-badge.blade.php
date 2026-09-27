@props([
    'percent' => null,
    'label' => null,
])

@php
    $faceService = app(\App\Services\FaceVerificationService::class);
    $badgeType = $faceService->matchPercentBadgeType($percent !== null ? (int) $percent : null);
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex flex-wrap items-center gap-1.5']) }}>
    @if ($label)
        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</span>
    @endif
    @if ($percent !== null)
        <x-ui.badge :type="$badgeType">{{ (int) $percent }}% cocok</x-ui.badge>
    @else
        <span class="text-xs text-gray-400">—</span>
    @endif
</div>
