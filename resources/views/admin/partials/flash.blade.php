@php($inline = $inline ?? false)
@foreach(['success' => session('success'), 'error' => session('error')] as $toastType => $toastMessage)
    @if($toastMessage)
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, {{ $toastType === 'error' ? 8000 : 4500 }})"
            x-show="show" x-transition.opacity.duration.300ms role="alert"
            class="{{ $inline ? 'w-full sm:w-auto sm:max-w-md' : 'fixed top-4 left-4 md:left-[17rem] z-50 w-[calc(100%-2rem)] sm:w-auto sm:max-w-sm' }} flex items-center gap-2.5 pl-3 pr-2 py-2.5 rounded-xl border shadow-lg text-sm {{ $toastType === 'error' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-card border-primary/30 text-foreground' }}"
            @if(!$inline && $toastType === 'error') style="top: 4rem" @endif>
            <span class="flex-shrink-0 w-5 h-5 rounded-full flex items-center justify-center {{ $toastType === 'error' ? 'bg-red-100 text-red-600' : 'bg-primary/10 text-primary' }}">
                @if($toastType === 'error')
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                @else
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                @endif
            </span>
            <p class="font-medium leading-snug">{{ $toastMessage }}</p>
            <button type="button" @click="show = false" aria-label="Dismiss" class="ml-1 p-1 rounded-md opacity-60 hover:opacity-100 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif
@endforeach
