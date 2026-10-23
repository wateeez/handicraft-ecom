@php
    $selectedVendorId = old('vendor_id', $product->vendor_id ?? '');
    $selectedVendor = $vendors->firstWhere('id', (int) $selectedVendorId);
@endphp
<div x-data="{
        open: false,
        search: '',
        selectedId: '{{ $selectedVendorId }}',
        selectedName: @js($selectedVendor?->name ?? ''),
        vendors: @js($vendors->map(fn ($v) => ['id' => $v->id, 'name' => $v->name])->values()),
        get filtered() {
            const q = this.search.toLowerCase();
            return this.vendors.filter(v => v.name.toLowerCase().includes(q));
        },
        choose(v) { this.selectedId = v ? v.id : ''; this.selectedName = v ? v.name : ''; this.open = false; this.search = ''; }
    }" @click.outside="open = false" class="relative">
    <label class="block text-sm font-medium text-truffle-extra-dark">Vendor (Optional, admin only)</label>
    <input type="hidden" name="vendor_id" :value="selectedId">
    <button type="button" @click="open = !open"
        class="mt-1 flex w-full items-center justify-between rounded-md border border-truffle-medium/30 bg-card p-2 text-left shadow-sm focus:border-green-500 focus:ring-green-500 @error('vendor_id') border-red-500 @enderror">
        <span x-text="selectedName || 'Select Vendor'" :class="selectedName ? '' : 'text-gray-400'"></span>
        <span class="text-gray-400">&#9662;</span>
    </button>
    <div x-show="open" x-cloak class="absolute z-20 mt-1 w-full rounded-md border border-truffle-medium/30 bg-white shadow-lg">
        <input type="text" x-model="search" placeholder="Search vendors..." @keydown.enter.prevent="filtered[0] && choose(filtered[0])"
            class="w-full border-b border-truffle-medium/30 bg-card p-2 text-sm focus:outline-none" x-effect="open && $nextTick(() => $el.focus())">
        <ul class="max-h-48 overflow-y-auto text-sm">
            <li @click="choose(null)" class="cursor-pointer p-2 text-gray-500 hover:bg-gray-100">No vendor</li>
            <template x-for="v in filtered" :key="v.id">
                <li @click="choose(v)" x-text="v.name" class="cursor-pointer p-2 hover:bg-gray-100" :class="v.id == selectedId ? 'bg-green-50 font-medium' : ''"></li>
            </template>
            <li x-show="filtered.length === 0" class="p-2 text-gray-400">No vendors found</li>
        </ul>
    </div>
    @error('vendor_id')
        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
    @enderror
</div>
