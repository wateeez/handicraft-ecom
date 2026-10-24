@php
    $seoTitle = old('meta_title', $model->meta_title ?? '');
    $seoDescription = old('meta_description', $model->meta_description ?? '');
@endphp
<div class="{{ $wrapperClass ?? 'col-span-2 mt-2' }} border-t border-truffle-medium/30 pt-4"
    x-data="{ t: {{ mb_strlen($seoTitle) }}, d: {{ mb_strlen($seoDescription) }} }">
    <h3 class="text-sm font-semibold text-truffle-extra-dark">Search engine listing (SEO)</h3>
    <p class="text-xs text-truffle-extra-dark/70 mb-3">Optional. Leave blank to build these from the {{ $hint ?? 'page content' }} automatically.</p>

    <div class="mb-3">
        <label class="flex justify-between text-sm font-medium text-truffle-extra-dark">
            Meta title
            <span class="text-xs font-normal" :class="t > 60 ? 'text-red-600' : 'text-truffle-extra-dark/60'"><span x-text="t"></span>/60 recommended</span>
        </label>
        <input type="text" name="meta_title" maxlength="70" value="{{ $seoTitle }}"
            class="bg-card mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2"
            @input="t = $event.target.value.length">
    </div>
    <div>
        <label class="flex justify-between text-sm font-medium text-truffle-extra-dark">
            Meta description
            <span class="text-xs font-normal" :class="d > 160 ? 'text-red-600' : 'text-truffle-extra-dark/60'"><span x-text="d"></span>/160 recommended</span>
        </label>
        <textarea name="meta_description" rows="2" maxlength="200"
            class="bg-card mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2"
            @input="d = $event.target.value.length">{{ $seoDescription }}</textarea>
    </div>
</div>
