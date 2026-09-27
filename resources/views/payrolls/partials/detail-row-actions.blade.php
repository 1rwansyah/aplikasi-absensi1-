@if ($payroll->status !== 'paid' && $detail->isEditable())
    <div class="flex items-center justify-end gap-0.5">
        <button type="button"
            title="Edit"
            onclick="openItemEditModal({{ $detail->id }}, '{{ addslashes($detail->name) }}', {{ (int) $detail->amount }}, '{{ addslashes($detail->notes ?? '') }}', '{{ route('payroll-details.update', $detail) }}')"
            class="inline-flex items-center justify-center rounded-lg p-1.5 text-gray-500 transition hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700">
            <svg class="h-4 w-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
            </svg>
            <span class="sr-only">Edit</span>
        </button>
        <form id="item-delete-form-{{ $detail->id }}" method="POST" action="{{ route('payroll-details.destroy', $detail) }}">
            @csrf @method('DELETE')
        </form>
        <button type="button"
            title="Hapus"
            onclick="openItemDeleteModal({{ $detail->id }}, '{{ addslashes($detail->name) }}')"
            class="inline-flex items-center justify-center rounded-lg p-1.5 text-red-500 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            <span class="sr-only">Hapus</span>
        </button>
    </div>
@endif
