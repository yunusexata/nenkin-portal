<x-ui.modal x-data="{ open: false }" @open-regular-modal.window="open = true"     @close-regular-modal.window="open = false" :isOpen="false" class="max-w-[96vw] p-10" wire:ignore.self>
    <div class="relative w-full bg-white dark:bg-gray-900 overflow-y-scroll max-h-[95vw]">
        {{-- Blocking overlay --}}
        <div wire:loading.flex class="absolute inset-0 bg-white/70 dark:bg-gray-900/70 z-50 flex items-center justify-center backdrop-blur-[2px]">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 flex flex-col items-center gap-3">
                <svg class="animate-spin h-10 w-8 text-brand-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-xs text-gray-500 dark:text-gray-400">Sedang mencari data anda ...</span>
            </div>
        </div>

        <h4 class="font-semibold text-gray-800 mb-7 text-title-sm dark:text-white/90">
            Preview Data
        </h4>

        <div class="border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            
        </div>
    </div>
</x-ui.modal>

