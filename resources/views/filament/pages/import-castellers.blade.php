<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->content }}

        @if ($this->importedCount !== null)
            <div class="rounded-xl border p-4 {{ empty($this->importErrors) ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950/30' : 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/30' }}">
                <p class="text-sm font-medium {{ empty($this->importErrors) ? 'text-green-800 dark:text-green-300' : 'text-amber-800 dark:text-amber-300' }}">
                    {{ $this->importedCount }} castellers processats.
                    @if (!empty($this->importErrors))
                        {{ count($this->importErrors) }} files amb errors (vegeu a sota).
                    @endif
                </p>
            </div>
        @endif

        @if (!empty($this->importErrors))
            <div class="overflow-x-auto rounded-xl border border-red-200 dark:border-red-800">
                <table class="w-full text-sm">
                    <thead class="bg-red-50 dark:bg-red-950/40">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium text-red-700 dark:text-red-300">Fila</th>
                            <th class="px-4 py-2 text-left font-medium text-red-700 dark:text-red-300">Errors</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-red-100 dark:divide-red-900">
                        @foreach ($this->importErrors as $err)
                            <tr class="bg-white dark:bg-gray-900">
                                <td class="px-4 py-2 font-mono text-gray-700 dark:text-gray-300">{{ $err['row'] }}</td>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ implode(' / ', $err['errors']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-filament-panels::page>
