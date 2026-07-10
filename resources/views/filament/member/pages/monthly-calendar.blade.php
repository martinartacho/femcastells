<x-filament-panels::page>
    @php
        $weeks     = $this->getCalendarData();
        $dayNames  = ['Dll', 'Dm', 'Dc', 'Dj', 'Dv', 'Ds', 'Dg'];
        $typeColor = [1 => '#6366f1', 2 => '#f59e0b', 3 => '#10b981'];
        $attColor  = ['ok' => 'bg-green-500', 'nok' => 'bg-red-400', null => 'bg-gray-300 dark:bg-gray-600'];
    @endphp

    <div class="space-y-4">
        {{-- Navegació mes --}}
        <div class="flex items-center justify-between">
            <button wire:click="previousMonth"
                class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                ← Anterior
            </button>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                {{ $this->getMonthLabel() }}
            </h2>
            <button wire:click="nextMonth"
                class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                Següent →
            </button>
        </div>

        {{-- Llegenda --}}
        <div class="flex flex-wrap gap-3 text-xs text-gray-500 dark:text-gray-400">
            <span class="flex items-center gap-1"><span style="background:#6366f1" class="inline-block w-2.5 h-2.5 rounded-sm"></span>Assaig</span>
            <span class="flex items-center gap-1"><span style="background:#f59e0b" class="inline-block w-2.5 h-2.5 rounded-sm"></span>Actuació</span>
            <span class="flex items-center gap-1"><span style="background:#10b981" class="inline-block w-2.5 h-2.5 rounded-sm"></span>Altres</span>
            <span class="flex items-center gap-1"><span class="inline-block w-2.5 h-2.5 rounded-full bg-green-500"></span>Assisteixo</span>
            <span class="flex items-center gap-1"><span class="inline-block w-2.5 h-2.5 rounded-full bg-red-400"></span>No assisteixo</span>
            <span class="flex items-center gap-1"><span class="inline-block w-2.5 h-2.5 rounded-full bg-gray-300 dark:bg-gray-600"></span>Pendent</span>
        </div>

        {{-- Graella --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
            {{-- Capçalera dies --}}
            <div class="grid grid-cols-7 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                @foreach ($dayNames as $d)
                    <div class="py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400">{{ $d }}</div>
                @endforeach
            </div>

            {{-- Setmanes --}}
            @foreach ($weeks as $week)
                <div class="grid grid-cols-7 divide-x divide-gray-100 dark:divide-gray-800 border-b border-gray-100 dark:border-gray-800 last:border-b-0">
                    @foreach ($week as $cell)
                        <div class="min-h-[80px] p-1 {{ $cell && $cell['today'] ? 'bg-indigo-50 dark:bg-indigo-950/30' : 'bg-white dark:bg-gray-900' }}">
                            @if ($cell)
                                <div class="mb-1 text-right text-xs font-semibold {{ $cell['today'] ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-400' }}">
                                    {{ $cell['day'] }}
                                </div>
                                @foreach ($cell['events'] as $ev)
                                    <a href="{{ route('filament.member.resources.calendars.index', ['tenant' => filament()->getTenant()->shortname]) }}"
                                       class="mb-0.5 flex items-center gap-0.5 rounded px-1 py-0.5 text-xs text-white hover:opacity-80"
                                       style="background: {{ $typeColor[$ev['type']] ?? '#6b7280' }}"
                                       title="{{ $ev['name'] }}">
                                        <span class="inline-block w-1.5 h-1.5 rounded-full flex-shrink-0 {{ $attColor[$ev['attendance']] ?? 'bg-gray-300' }}"></span>
                                        <span class="truncate">{{ $ev['time'] }} {{ $ev['name'] }}</span>
                                    </a>
                                @endforeach
                            @endif
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
