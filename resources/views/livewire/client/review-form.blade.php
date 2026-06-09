<div>
    @if ($showForm)
        <div class="w-full max-w-lg mx-auto bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xl overflow-hidden p-6 md:p-8 mt-6 transition-all duration-300">
            @if ($hasSubmitted)
                <!-- Thank You Screen -->
                <div class="text-center py-6 flex flex-col items-center">
                    <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center text-green-500 mb-4 animate-bounce">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 dark:text-white mb-2">Terima Kasih!</h3>
                    <p class="text-gray-650 dark:text-gray-300 text-medium px-4">
                        {{ $submittedMessage }}
                    </p>
                    
                    @if ($redirectUrl)
                        <div class="mt-6">
                            <a href="{{ $redirectUrl }}" target="_blank" class="inline-flex items-center px-5 py-2.5 bg-orange-500 hover:bg-orange-600 text-white font-semibold rounded-xl shadow transition duration-200 text-sm">
                                Lanjutkan Ke Tautan Review
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                            </a>
                        </div>
                    @endif
                </div>
            @else
                <!-- Review Form -->
                <div>
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-extrabold text-gray-800 dark:text-white">Bagaimana pengalaman Anda?</h2>
                        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Ulasan Anda sangat berharga bagi peningkatan layanan kami.</p>
                    </div>

                    <form wire:submit.prevent="submit" class="space-y-6">
                        <!-- Star Rating Picker -->
                        <div class="flex flex-col items-center">
                            <div class="flex items-center space-x-2">
                                @for ($i = 1; $i <= 5; $i++)
                                    <button type="button" 
                                            wire:click="selectRating({{ $i }})" 
                                            class="focus:outline-none transition-transform duration-150 hover:scale-125"
                                            title="Bintang {{ $i }}">
                                        <svg class="w-12 h-12 {{ $i <= $rating ? 'text-amber-400 fill-amber-400' : 'text-gray-350 dark:text-gray-600' }} hover:text-amber-400 transition-colors duration-150" 
                                             fill="none" 
                                             stroke="currentColor" 
                                             viewBox="0 0 24 24" 
                                             xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" 
                                                  stroke-linejoin="round" 
                                                  stroke-width="2" 
                                                  d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.907c.961 0 1.36 1.24.588 1.81l-3.97 2.883a1 1 0 00-.364 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.971-2.883a1 1 0 00-1.18 0l-3.97 2.883c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.364-1.118l-3.97-2.883c-.77-.57-.37-1.81.588-1.81h4.906a1 1 0 00.951-.69l1.519-4.674z">
                                            </path>
                                        </svg>
                                    </button>
                                @endfor
                            </div>
                            @error('rating')
                                <span class="text-red-500 text-xs mt-2">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Feedback Message -->
                        <div class="space-y-2">
                            <label for="message" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Pesan Ulasan <span class="text-xs text-gray-400 dark:text-gray-505">(Opsional)</span>
                            </label>
                            <textarea id="message" 
                                      wire:model.defer="message" 
                                      rows="4" 
                                      placeholder="Ceritakan pengalaman Anda (opsional)..."
                                      class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 focus:outline-none transition duration-150"></textarea>
                        </div>

                        @error('general')
                            <div class="bg-red-50 dark:bg-red-900/20 text-red-650 dark:text-red-400 text-sm p-4 rounded-xl">
                                {{ $message }}
                            </div>
                        @enderror

                        <!-- Submit Button -->
                        <div>
                            <button type="submit" 
                                    {{ $rating === 0 ? 'disabled' : '' }}
                                    class="w-full flex items-center justify-center px-6 py-3.5 bg-orange-500 hover:bg-orange-600 disabled:bg-gray-300 disabled:dark:bg-gray-750 disabled:cursor-not-allowed text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 text-medium">
                                Kirim Ulasan
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('open-new-tab', (event) => {
            const data = Array.isArray(event) ? event[0] : event;
            if (data && data.url) {
                window.open(data.url, '_blank');
            }
        });
    });
</script>
@endpush
