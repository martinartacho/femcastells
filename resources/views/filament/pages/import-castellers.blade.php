<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Advertiment de perill --}}
        <div class="rounded-xl border-2 border-red-400 bg-red-50 dark:border-red-600 dark:bg-red-950/40 p-4">
            <div class="flex gap-3">
                <div class="flex-shrink-0 mt-0.5">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="text-red-600 dark:text-red-400" style="width:1.5rem;height:1.5rem">
                        <path fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div>
                    <p class="font-bold text-red-700 dark:text-red-300 text-sm uppercase tracking-wide">Atenció — Operació de risc elevat</p>
                    <p class="mt-1 text-sm text-red-700 dark:text-red-400">
                        Aquesta operació pot <strong>sobreescriure dades de castellers de forma irreversible</strong>
                        (assistències, assignacions, historial). Assegura't primer de fer una còpia de seguretat de la base de dades.
                    </p>
                </div>
            </div>
        </div>

        {{-- Criteri d'identificació --}}
        <div class="rounded-xl border border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-950/30 p-4 text-sm text-blue-800 dark:text-blue-300">
            <p class="font-semibold mb-1">Criteri d'identificació de castellers existents:</p>
            <ol class="list-decimal list-inside space-y-0.5">
                <li>Si la fila té <strong>núm. soci</strong> → es busca per núm. soci dins la colla</li>
                <li>Si no coincideix (o no té núm. soci) → es busca per <strong>àlies</strong></li>
                <li>Si tampoc coincideix → es crea com a <strong>nou casteller</strong></li>
            </ol>
            <p class="mt-2 text-blue-600 dark:text-blue-400">Les dades dels castellers trobats s'actualitzen amb els valors del fitxer.</p>
        </div>

        {{ $this->content }}

        @if ($this->importStats !== null)
            @php $stats = $this->importStats; $errors = $this->importErrors; @endphp

            <div class="rounded-xl border p-4 {{ empty($errors) ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950/30' : 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/30' }}">
                <p class="text-sm font-semibold mb-2 {{ empty($errors) ? 'text-green-800 dark:text-green-300' : 'text-amber-800 dark:text-amber-300' }}">
                    {{ $stats['total'] }} castellers processats
                    @if (!empty($errors))
                        · {{ count($errors) }} files amb errors
                    @endif
                </p>
                <div class="flex flex-wrap gap-4 text-sm {{ empty($errors) ? 'text-green-700 dark:text-green-400' : 'text-amber-700 dark:text-amber-400' }}">
                    @if ($stats['num_soci'] > 0)
                        <span>✓ {{ $stats['num_soci'] }} actualitzats per núm. soci</span>
                    @endif
                    @if ($stats['alias'] > 0)
                        <span>✓ {{ $stats['alias'] }} actualitzats per àlies</span>
                    @endif
                    @if ($stats['new'] > 0)
                        <span>+ {{ $stats['new'] }} nous castellers</span>
                    @endif
                </div>
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
