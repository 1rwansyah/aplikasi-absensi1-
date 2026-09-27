@props(['log'])

@php
    $actionClass = 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300';
    $actionLower = strtolower($log->action);
    if (str_contains($actionLower, 'create') || str_contains($actionLower, 'add')) {
        $actionClass = 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
    } elseif (str_contains($actionLower, 'update') || str_contains($actionLower, 'edit')) {
        $actionClass = 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400';
    } elseif (str_contains($actionLower, 'delete') || str_contains($actionLower, 'remove')) {
        $actionClass = 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
    } elseif (str_contains($actionLower, 'approve')) {
        $actionClass = 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
    } elseif (str_contains($actionLower, 'reject')) {
        $actionClass = 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400';
    }
@endphp

<tr class="transition hover:bg-slate-50/50 dark:hover:bg-slate-700/50">
    <td class="whitespace-nowrap px-6 py-4 text-slate-500 dark:text-slate-400">
        <div class="font-medium text-slate-700 dark:text-slate-200" title="{{ $log->created_at->format('d M Y H:i:s') }}">
            {{ $log->created_at->diffForHumans() }}
        </div>
        <div class="text-xs">{{ $log->created_at->format('d M Y') }}</div>
    </td>
    <td class="px-6 py-4 font-medium text-slate-900 dark:text-slate-100">
        {{ $log->user->name ?? 'System' }}
    </td>
    <td class="px-6 py-4">
        @if($log->role === 'admin')
            <span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10 dark:bg-red-400/10 dark:text-red-400 dark:ring-red-400/20">Admin</span>
        @elseif($log->role === 'hr')
            <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-400/10 dark:text-blue-400 dark:ring-blue-400/30">HR</span>
        @else
            <span class="inline-flex items-center rounded-full bg-slate-50 px-2.5 py-0.5 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-500/10 dark:bg-slate-400/10 dark:text-slate-400 dark:ring-slate-400/20">{{ ucfirst($log->role) }}</span>
        @endif
    </td>
    <td class="px-6 py-4">
        <div class="mb-1">
            <span class="inline-flex items-center rounded bg-slate-100 px-2 py-0.5 text-xs font-medium {{ $actionClass }}">
                {{ strtoupper($log->action) }}
            </span>
        </div>
        <p class="text-sm dark:text-slate-200">{{ $log->summary() }}</p>
        @if ($detail = $log->inlineChangeDetail())
            <p class="mt-1 text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ $detail }}</p>
        @endif
    </td>

</tr>
