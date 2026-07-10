<x-filament-widgets::widget>
    <div class="flex gap-4">
        <div class="flex-1 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-5 py-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Últim accés</p>
            <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $this->getLastLogin() }}</p>
        </div>
        <div class="flex-1 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-5 py-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total accessos</p>
            <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $this->getLoginCount() }}</p>
        </div>
    </div>
</x-filament-widgets::widget>
