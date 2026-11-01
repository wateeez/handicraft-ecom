{{--
    Sub-category (required) and child sub-category (optional).
    Submits a single sub_category_id: the child if one is chosen, otherwise the sub-category.
    Expects $selected (current sub_category_id or null). Reads the sub-category tree from
    the data-subcategories attribute on the #category_id options.
--}}
<div>
    <label class="block text-sm font-medium text-truffle-extra-dark">Sub-Category <span class="text-red-500">*</span></label>
    <select id="sub_category_parent" required
        class="bg-card mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2 @error('sub_category_id') border-red-500 @enderror">
        <option value="">Select Sub-Category</option>
    </select>
    <p id="sub_category_empty_hint" class="text-xs text-red-500 mt-1" hidden>This category has no sub-categories yet. Add one on the Categories page first.</p>
    @error('sub_category_id')
        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
    @enderror
</div>

<div id="sub_category_child_wrapper" hidden>
    <label class="block text-sm font-medium text-truffle-extra-dark">Child Sub-Category (Optional)</label>
    <select id="sub_category_child"
        class="bg-card mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2">
        <option value="">None</option>
    </select>
</div>

<input type="hidden" name="sub_category_id" id="sub_category_id" value="{{ $selected }}">

<script>
(function () {
    const categorySelect = document.getElementById('category_id');
    const parentSelect = document.getElementById('sub_category_parent');
    const childSelect = document.getElementById('sub_category_child');
    const childWrapper = document.getElementById('sub_category_child_wrapper');
    const emptyHint = document.getElementById('sub_category_empty_hint');
    const hiddenInput = document.getElementById('sub_category_id');

    function tree() {
        const option = categorySelect.options[categorySelect.selectedIndex];
        return option && option.value ? JSON.parse(option.getAttribute('data-subcategories') || '[]') : [];
    }

    function fill(select, items, placeholder, selectedId) {
        select.innerHTML = '';
        select.appendChild(new Option(placeholder, ''));
        items.forEach(function (item) {
            select.appendChild(new Option(item.name, item.id, false, item.id == selectedId));
        });
    }

    function sync() {
        hiddenInput.value = childSelect.value || parentSelect.value;
    }

    function renderChildren(selectedChildId) {
        const parent = tree().find(function (sub) { return sub.id == parentSelect.value; });
        const children = parent ? parent.children : [];
        fill(childSelect, children, 'None', selectedChildId);
        childWrapper.hidden = children.length === 0;
        sync();
    }

    function renderParents(selectedId) {
        const subs = tree();
        // A saved sub_category_id may point at a child; select its parent and then the child.
        let parentId = selectedId, childId = null;
        subs.forEach(function (sub) {
            if (sub.children.some(function (child) { return child.id == selectedId; })) {
                parentId = sub.id;
                childId = selectedId;
            }
        });
        fill(parentSelect, subs, 'Select Sub-Category', parentId);
        emptyHint.hidden = !categorySelect.value || subs.length > 0;
        renderChildren(childId);
    }

    categorySelect.addEventListener('change', function () { renderParents(null); });
    parentSelect.addEventListener('change', function () { renderChildren(null); });
    childSelect.addEventListener('change', sync);
    renderParents(hiddenInput.value);
})();
</script>
