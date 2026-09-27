@props(['text' => ''])

<span {{ $attributes->merge(['class' => 'tooltip-wrap relative inline-flex items-center']) }}>
    {{ $slot }}
    @if($text)
        <span class="tooltip">{{ $text }}</span>
    @endif
</span>

<style>
.tooltip-wrap { position: relative; display: inline-flex; align-items: center; }
.tooltip-wrap .tooltip {
    position: absolute;
    bottom: calc(100% + 6px);
    left: 50%;
    transform: translateX(-50%);
    white-space: nowrap;
    background-color: #1f2937;
    color: #fff;
    font-size: 0.75rem;
    font-weight: 500;
    padding: 4px 10px;
    border-radius: 6px;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.15s ease;
    z-index: 10;
}
.tooltip-wrap .tooltip::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    border: 5px solid transparent;
    border-top-color: #1f2937;
}
.tooltip-wrap:hover .tooltip { opacity: 1; }
</style>
