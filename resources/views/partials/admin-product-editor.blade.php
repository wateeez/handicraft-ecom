{{-- Storefront quick-edit panel for logged-in admins. Opened by any [data-edit-product] button. --}}
<div id="admin-product-editor" class="fixed inset-0 z-[100] hidden" role="dialog" aria-modal="true" aria-labelledby="admin-product-editor-title">
    <div class="absolute inset-0 bg-black/40" data-editor-close></div>
    <form class="absolute inset-y-0 right-0 flex w-full max-w-md flex-col bg-card text-foreground shadow-2xl" novalidate>
        <div class="flex items-center justify-between border-b border-border px-5 py-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Admin quick edit</p>
                <h2 id="admin-product-editor-title" class="font-serif text-lg font-bold">Edit product</h2>
            </div>
            <button type="button" class="rounded-full p-2 text-muted-foreground hover:bg-muted" data-editor-close aria-label="Close">&times;</button>
        </div>

        <div class="flex-1 space-y-4 overflow-y-auto px-5 py-5 text-sm">
            <div>
                <label for="editor-name" class="mb-1.5 block font-semibold">Name</label>
                <input id="editor-name" name="name" type="text" required maxlength="255"
                    class="w-full rounded-xl border border-border bg-background px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="editor-price" class="mb-1.5 block font-semibold">Price ($)</label>
                    <input id="editor-price" name="price" type="number" step="0.01" min="0" required
                        class="w-full rounded-xl border border-border bg-background px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30">
                </div>
                <div>
                    <label for="editor-discount-price" class="mb-1.5 block font-semibold">Sale price ($)</label>
                    <input id="editor-discount-price" name="discount_price" type="number" step="0.01" min="0" placeholder="None"
                        class="w-full rounded-xl border border-border bg-background px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30">
                </div>
            </div>
            <div>
                <label for="editor-description" class="mb-1.5 block font-semibold">Short description</label>
                <textarea id="editor-description" name="description" rows="3"
                    class="w-full rounded-xl border border-border bg-background px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"></textarea>
            </div>
            <div>
                <label for="editor-long-description" class="mb-1.5 block font-semibold">Full description</label>
                <textarea id="editor-long-description" name="long_description" rows="8"
                    class="w-full rounded-xl border border-border bg-background px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"></textarea>
            </div>
            <label class="flex items-center gap-2 font-semibold">
                <input name="is_order_now_enabled" type="checkbox" class="h-4 w-4 rounded border-border">
                Allow ordering (unchecked = Inquiry only)
            </label>

            <p class="hidden rounded-xl border border-destructive/30 bg-destructive/10 px-3 py-2 text-destructive" data-editor-error></p>
        </div>

        <div class="flex items-center justify-between gap-3 border-t border-border px-5 py-4">
            <a href="#" class="text-sm font-semibold text-primary hover:underline" data-editor-dashboard-link>Images &amp; more in dashboard</a>
            <button type="submit" class="rounded-full bg-primary px-6 py-2.5 text-sm font-semibold text-primary-foreground hover:opacity-90 disabled:opacity-60" data-editor-save>
                Save
            </button>
        </div>
    </form>
</div>

<script>
    (function () {
        const editor = document.getElementById('admin-product-editor');
        const form = editor.querySelector('form');
        const errorBox = editor.querySelector('[data-editor-error]');
        const saveButton = editor.querySelector('[data-editor-save]');
        const dashboardLink = editor.querySelector('[data-editor-dashboard-link]');
        const csrfToken = @json(csrf_token());
        let current = null;

        function open(product) {
            current = product;
            form.elements.name.value = product.name ?? '';
            form.elements.price.value = product.price ?? '';
            form.elements.discount_price.value = product.discount_price ?? '';
            form.elements.description.value = product.description ?? '';
            form.elements.long_description.value = product.long_description ?? '';
            form.elements.is_order_now_enabled.checked = !!product.is_order_now_enabled;
            dashboardLink.href = product.dashboard_url;
            errorBox.classList.add('hidden');
            editor.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            form.elements.name.focus();
        }

        function close() {
            editor.classList.add('hidden');
            document.body.style.overflow = '';
            current = null;
        }

        document.addEventListener('click', function (e) {
            const trigger = e.target.closest('[data-edit-product]');
            if (trigger) {
                // Triggers sit inside product links; don't follow the link
                e.preventDefault();
                e.stopPropagation();
                open(JSON.parse(trigger.dataset.editProduct));
                return;
            }
            if (e.target.closest('[data-editor-close]')) close();
        }, true);

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !editor.classList.contains('hidden')) close();
        });

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            if (!current) return;

            saveButton.disabled = true;
            saveButton.textContent = 'Saving...';
            errorBox.classList.add('hidden');

            try {
                const response = await fetch(current.update_url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        name: form.elements.name.value.trim(),
                        price: form.elements.price.value,
                        discount_price: form.elements.discount_price.value === '' ? null : form.elements.discount_price.value,
                        description: form.elements.description.value,
                        long_description: form.elements.long_description.value,
                        is_order_now_enabled: form.elements.is_order_now_enabled.checked,
                    }),
                });
                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const messages = data.errors ? Object.values(data.errors).flat() : [data.message || 'Could not save. Please try again.'];
                    if (response.status === 419) messages.splice(0, messages.length, 'Your session expired. Refresh the page and try again.');
                    if (response.status === 403) messages.splice(0, messages.length, 'Your account is not allowed to edit products.');
                    errorBox.textContent = messages.join(' ');
                    errorBox.classList.remove('hidden');
                    return;
                }

                // On the product's own page a renamed product gets a new URL
                const onProductPage = window.location.pathname === new URL(current.page_url, window.location.origin).pathname;
                if (onProductPage && data.url) {
                    window.location.replace(data.url);
                } else {
                    window.location.reload();
                }
            } catch (err) {
                errorBox.textContent = 'Network error. Please try again.';
                errorBox.classList.remove('hidden');
            } finally {
                saveButton.disabled = false;
                saveButton.textContent = 'Save';
            }
        });
    })();
</script>
