<x-filament-panels::page>
    @php
        $colla = $this->getColla();
        $contact = $colla?->gdpr_email ?? $colla?->email ?? '';
    @endphp

    <div class="max-w-2xl mx-auto space-y-6">
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-6 shadow-sm">
            <div class="prose dark:prose-invert max-w-none text-sm leading-relaxed">
                {!! nl2br(e($colla?->gdpr_text ?? '')) !!}
            </div>
        </div>

        <form wire:submit="accept">
            {{ $this->form }}

            <div class="mt-4 flex justify-end gap-3">
                @foreach ($this->getFormActions() as $action)
                    {{ $action }}
                @endforeach
            </div>
        </form>
    </div>
</x-filament-panels::page>
