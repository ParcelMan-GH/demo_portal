<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Upload Package Photo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-slate-50 min-h-screen flex flex-col antialiased">
    
    <div class="flex-1 flex flex-col items-center justify-center p-6" x-data="mobileUploader()">
        
        <div class="w-16 h-16 bg-orange-100 text-orange-600 rounded-2xl flex items-center justify-center mb-6 shadow-inner">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </div>

        <h1 class="text-2xl font-bold text-slate-900 mb-2 text-center">Capture Package</h1>
        <p class="text-slate-500 text-center mb-10 text-sm px-4">Take a clear photo of the package receipt or label. It will instantly appear on your desktop.</p>

        <!-- Step 1: Choose source -->
        <div x-show="status === 'idle'" class="w-full max-w-sm space-y-4">
            <label class="w-full bg-orange-600 active:bg-orange-700 text-white rounded-2xl py-4 px-6 flex items-center justify-center gap-3 text-lg font-bold shadow-xl shadow-orange-600/30 transition-transform active:scale-95 cursor-pointer">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Take Photo
                <!-- capture="environment" forces the rear camera -->
                <input type="file" accept="image/*" capture="environment" class="hidden" @change="uploadPhoto">
            </label>

            <label class="w-full bg-slate-900 active:bg-slate-800 text-white rounded-2xl py-4 px-6 flex items-center justify-center gap-3 text-lg font-bold shadow-xl shadow-slate-900/30 transition-transform active:scale-95 cursor-pointer">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Choose from Gallery
                <input type="file" accept="image/*" class="hidden" @change="uploadPhoto">
            </label>
        </div>

        <!-- Loading State -->
        <div x-show="status === 'uploading'" x-cloak class="w-full max-w-sm bg-slate-900 text-white rounded-2xl py-4 px-6 flex items-center justify-center gap-3 text-lg font-bold shadow-xl">
            <svg class="w-6 h-6 animate-spin text-orange-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            Uploading...
        </div>

        <!-- Success State -->
        <div x-show="status === 'success'" x-cloak class="w-full max-w-sm bg-emerald-500 text-white rounded-2xl py-4 px-6 flex flex-col items-center justify-center gap-2 shadow-xl">
            <div class="flex items-center gap-2 text-lg font-bold">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                Sent to Desktop!
            </div>
            <p class="text-emerald-100 text-xs font-medium">You can close this tab, or take another photo.</p>
        </div>

        <button x-show="status === 'success'" x-cloak @click="status = 'idle'" class="mt-6 text-slate-500 font-bold text-sm underline underline-offset-4">
            Take Another Photo
        </button>
    </div>

    <script>
        function mobileUploader() {
            return {
                status: 'idle', // idle, uploading, success
                sessionId: '{{ $sessionId }}',
                
                async uploadPhoto(event) {
                    const file = event.target.files[0];
                    if (!file) return;

                    this.status = 'uploading';
                    
                    const formData = new FormData();
                    formData.append('photo', file);

                    try {
                        const response = await fetch(`/mobile-camera/${this.sessionId}/upload`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: formData
                        });

                        if (response.ok) {
                            this.status = 'success';
                        } else {
                            Swal.fire({ title: 'Upload failed', text: 'Please try again.', icon: 'error', confirmButtonColor: '#E2762B' });
                            this.status = 'idle';
                        }
                    } catch (error) {
                        Swal.fire({ title: 'Network error', text: 'Please try again.', icon: 'error', confirmButtonColor: '#E2762B' });
                        this.status = 'idle';
                    }
                }
            }
        }
    </script>
</body>
</html>