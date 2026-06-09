@extends('layouts.app')

@section('content')
<div class="container mx-auto py-8 px-4" x-data="esignApp()">
    <div class="max-w-4xl mx-auto bg-white shadow-lg rounded-lg overflow-hidden">
        <div class="p-6 border-b border-gray-200 bg-gray-50">
            <h1 class="text-2xl font-bold text-gray-800">Tandatangani Perjanjian Speed Nenkin</h1>
            <p class="text-sm text-gray-600 mt-1">Silakan baca seluruh dokumen sebelum memberikan tanda tangan digital Anda.</p>
        </div>

        <form action="{{ route('client.esign.store') }}" method="POST" id="esignForm" @submit.prevent="submitForm">
            @csrf
            
            {{-- Scrollable Agreement Container --}}
            <div class="relative">
                <div 
                    id="agreementContainer"
                    class="h-[500px] overflow-y-auto border-b border-gray-200"
                    @scroll="checkScroll($event)"
                >
                    <div id="agreementHtml">
                        @include('client.partials.agreement-content', [
                            'client' => $client,
                            'nik' => '', 
                            'address' => '',
                            'dateFormatted' => $dateFormatted,
                            'letterNumber' => $letterNumber
                        ])
                    </div>
                </div>

                {{-- Scroll Indicator --}}
                <div x-show="!hasScrolledToBottom" class="absolute bottom-4 left-1/2 -translate-x-1/2 bg-blue-600 text-white px-4 py-2 rounded-full text-sm font-bold shadow-lg animate-bounce">
                    ⬇️ Silakan scroll sampai bawah
                </div>
            </div>

            <div class="p-8">
                {{-- Form Inputs - Show only after scroll --}}
                <div x-show="hasScrolledToBottom" x-transition class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="nik" class="block text-sm font-medium text-gray-700 mb-1">NIK (Nomor Induk Kependudukan) <span class="text-red-500">*</span></label>
                            <input type="text" id="nik" name="nik" x-model="nik" @input="updatePreview" required
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Masukkan 16 digit NIK Anda">
                        </div>
                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Alamat Lengkap (Sesuai KTP) <span class="text-red-500">*</span></label>
                            <textarea id="address" name="address" x-model="address" @input="updatePreview" required rows="2"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Masukkan alamat lengkap Anda"></textarea>
                        </div>
                    </div>

                    {{-- Checkbox & Signature - Show only after NIK & Address filled --}}
                    <div x-show="nik.trim().length > 0 && address.trim().length > 0" x-transition class="space-y-6 pt-6 border-t border-gray-100">
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input id="agreement_accepted" name="agreement_accepted" type="checkbox" x-model="agreement_accepted" required
                                    class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="agreement_accepted" class="font-medium text-gray-700">Saya telah membaca dan menyetujui seluruh ketentuan perjanjian di atas</label>
                            </div>
                        </div>

                        {{-- Signature Canvas --}}
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Tanda Tangan Digital <span class="text-red-500">*</span></label>
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-2 bg-gray-50 relative">
                                <canvas id="signatureCanvas" class="w-full h-48 bg-white cursor-crosshair touch-none rounded" 
                                    @mousedown="startDrawing" @mousemove="draw" @mouseup="stopDrawing" @mouseleave="stopDrawing"
                                    @touchstart="startDrawing" @touchmove="draw" @touchend="stopDrawing">
                                </canvas>
                                <button type="button" @click="clearSignature" class="absolute top-4 right-4 bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 py-1 rounded text-xs font-bold transition">
                                    Bersihkan
                                </button>
                            </div>
                            <p class="text-xs text-gray-500">Gunakan mouse atau jari Anda untuk menandatangani di atas area putih.</p>
                        </div>

                        <input type="hidden" name="signature_data" x-model="signature_data">

                        {{-- Submit Button --}}
                        <div class="pt-4">
                            <button type="submit" 
                                :disabled="!isReadyToSubmit"
                                :class="isReadyToSubmit ? 'bg-blue-600 hover:bg-blue-700 shadow-md' : 'bg-gray-400 cursor-not-allowed'"
                                class="w-full py-4 px-6 text-white font-bold rounded-lg text-lg transition-all duration-200 uppercase tracking-wider">
                                Tandatangani & Kirim
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function esignApp() {
    return {
        hasScrolledToBottom: false,
        nik: '',
        address: '',
        agreement_accepted: false,
        signature_data: '',
        isDrawing: false,
        ctx: null,
        canvas: null,
        hasSignature: false,

        init() {
            this.canvas = document.getElementById('signatureCanvas');
            this.ctx = this.canvas.getContext('2d');
            this.resizeCanvas();
            window.addEventListener('resize', () => this.resizeCanvas());
            
            // Set initial state for dotted lines
            this.updatePreview();
        },

        resizeCanvas() {
            const rect = this.canvas.getBoundingClientRect();
            // We use the CSS size for the internal drawing buffer
            // but we need to account for device pixel ratio for sharpness
            const dpr = window.devicePixelRatio || 1;
            this.canvas.width = rect.width * dpr;
            this.canvas.height = rect.height * dpr;
            this.ctx.scale(dpr, dpr);
            
            // Re-set line style after resize/clear
            this.ctx.strokeStyle = '#000000';
            this.ctx.lineWidth = 2;
            this.ctx.lineCap = 'round';
            this.ctx.lineJoin = 'round';
        },

        checkScroll(e) {
            const element = e.target;
            // Buffer of 10px to account for rounding errors
            if (element.scrollHeight - element.scrollTop <= element.clientHeight + 10) {
                this.hasScrolledToBottom = true;
            }
        },

        updatePreview() {
            // Update the HTML inside the agreement preview
            const nikDisplay = this.nik.trim() || '.........................................................................';
            const addressDisplay = this.address.trim() || '.........................................................................';
            
            // Find the table cells in the agreement partial
            // PIHAK KEDUA table is usually the second table
            const tables = document.querySelectorAll('#agreementHtml table');
            if (tables.length >= 2) {
                const secondTable = tables[1];
                const rows = secondTable.querySelectorAll('tr');
                if (rows.length >= 4) {
                    // Row index 1: NIK
                    rows[1].cells[1].innerText = ': ' + nikDisplay;
                    // Row index 3: Alamat
                    rows[3].cells[1].innerText = ': ' + addressDisplay;
                }
            }
        },

        // Drawing Logic
        startDrawing(e) {
            this.isDrawing = true;
            const pos = this.getPointerPos(e);
            this.ctx.beginPath();
            this.ctx.moveTo(pos.x, pos.y);
            e.preventDefault();
        },

        draw(e) {
            if (!this.isDrawing) return;
            const pos = this.getPointerPos(e);
            this.ctx.lineTo(pos.x, pos.y);
            this.ctx.stroke();
            this.hasSignature = true;
            e.preventDefault();
        },

        stopDrawing() {
            if (this.isDrawing) {
                this.isDrawing = false;
                this.signature_data = this.canvas.toDataURL('image/png');
            }
        },

        getPointerPos(e) {
            const rect = this.canvas.getBoundingClientRect();
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            return {
                x: clientX - rect.left,
                y: clientY - rect.top
            };
        },

        clearSignature() {
            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
            this.signature_data = '';
            this.hasSignature = false;
        },

        get isReadyToSubmit() {
            return this.hasScrolledToBottom && 
                   this.nik.trim().length > 0 && 
                   this.address.trim().length > 0 && 
                   this.agreement_accepted && 
                   this.hasSignature;
        },

        submitForm() {
            if (this.isReadyToSubmit) {
                document.getElementById('esignForm').submit();
            }
        }
    }
}
</script>

<style>
#agreementContainer::-webkit-scrollbar {
    width: 8px;
}
#agreementContainer::-webkit-scrollbar-track {
    background: #f1f1f1;
}
#agreementContainer::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}
#agreementContainer::-webkit-scrollbar-thumb:hover {
    background: #555;
}
canvas {
    touch-action: none;
}
</style>
@endsection
