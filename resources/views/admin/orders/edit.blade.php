@extends('admin.layout')

@section('header')
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.orders.index') }}" class="text-truffle-extra-dark/70 hover:text-green-premium transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </a>
        <div>
            <h2 class="text-xl font-bold flex items-center gap-2">
                Edit Order <span class="font-mono text-green-premium">{{ $order->order_number }}</span>
            </h2>
            <p class="text-sm text-truffle-extra-dark">Draft Request or Order Modification</p>
        </div>
    </div>
@endsection

@section('content')
    <div x-data="orderEditForm()" class="pb-10">
        <form action="{{ route('admin.orders.update', $order) }}" method="POST" id="orderEditForm">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Main Form Content (Left) --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- Basic Settings --}}
                    <div class="bg-cream rounded-xl shadow-sm border border-truffle-medium/30 p-6">
                        <h3 class="text-lg font-semibold text-truffle-extra-dark mb-4 border-b pb-2">General Details</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-truffle-extra-dark mb-1">Type <span
                                        class="text-red-500">*</span></label>
                                <select name="type"
                                    class="w-full border-truffle-medium/30 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500"
                                    {{ $order->isFinanciallyLocked() ? 'disabled' : '' }}>
                                    <option value="inquiry" {{ old('type', $order->type) == 'inquiry' ? 'selected' : '' }}>
                                        Inquiry (Draft Request)</option>
                                    <option value="order" {{ old('type', $order->type) == 'order' ? 'selected' : '' }}>Order
                                        (Confirmed)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-truffle-extra-dark mb-1">Client Profile</label>
                                <select name="client_id" x-model="client_id"
                                    class="w-full border-truffle-medium/30 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500">
                                    <option value="">-- Select or Walk-in --</option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}" {{ old('client_id', $order->client_id) == $client->id ? 'selected' : '' }}>
                                            {{ $client->name }} ({{ $client->buyer_id }})
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-truffle-extra-dark mt-1">Updates the client profile attached to this order.</p>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-truffle-extra-dark mb-1">Internal Notes</label>
                                <textarea name="notes" rows="3"
                                    class="w-full border-truffle-medium/30 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500">{{ old('notes', $order->notes) }}</textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Line Items --}}
                    <div class="bg-cream rounded-xl shadow-sm border border-truffle-medium/30 p-6">
                        <div class="flex items-center justify-between mb-4 border-b pb-2">
                            <h3 class="text-lg font-semibold text-truffle-extra-dark">Line Items</h3>
                            @if(!$order->isFinanciallyLocked())
                                <button type="button" @click="addItem()"
                                    class="text-sm font-medium text-green-premium hover:text-green-premium bg-green-premium/10 px-3 py-1.5 rounded-lg transition-colors">
                                    + Add Item
                                </button>
                            @endif
                        </div>

                        <div class="space-y-4">
                            <template x-for="(item, index) in items" :key="item.id">
                                <div class="p-4 border border-gray-100 bg-[#F5F2EA] rounded-lg relative group">
                                    @if(!$order->isFinanciallyLocked())
                                        <button type="button" @click="removeItem(index)"
                                            class="absolute -top-3 -right-3 w-7 h-7 bg-red-100 text-red-600 rounded-full flex items-center justify-center hover:bg-red-200 transition-colors opacity-0 group-hover:opacity-100">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    @endif

                                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                                        {{-- Product Selector --}}
                                        <div class="md:col-span-4">
                                            <label class="block text-xs font-semibold text-truffle-extra-dark mb-1">Product</label>
                                            <select :name="`items[${index}][product_id]`" x-model="item.product_id"
                                                @change="populateItemData(index)"
                                                class="w-full text-sm border-truffle-medium/30 rounded-md bg-cream shadow-sm disabled:bg-[#F5F2EA]"
                                                {{ $order->isFinanciallyLocked() ? 'disabled' : '' }}>
                                                <option value="">-- Custom Product --</option>
                                                @foreach($products as $product)
                                                    <option value="{{ $product->id }}"
                                                        data-price="{{ $product->effective_price }}"
                                                        data-weight="{{ $product->weight }}"
                                                        data-length="{{ $product->length }}" data-width="{{ $product->width }}"
                                                        data-height="{{ $product->height }}">
                                                        {{ $product->name }} ({{ $product->sku }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        {{-- Custom Name (show if no product selected) --}}
                                        <div class="md:col-span-3" x-show="!item.product_id">
                                            <label class="block text-xs font-semibold text-truffle-extra-dark mb-1">Item Name</label>
                                            <input type="text" :name="`items[${index}][product_name]`"
                                                x-model="item.product_name"
                                                class="w-full text-sm border-truffle-medium/30 rounded-md disabled:bg-[#F5F2EA]"
                                                placeholder="Custom item" {{ $order->isFinanciallyLocked() ? 'disabled' : '' }}>
                                        </div>

                                        {{-- Qty --}}
                                        <div class="md:col-span-2" :class="item.product_id ? 'md:col-start-5' : ''">
                                            <label class="block text-xs font-semibold text-truffle-extra-dark mb-1">Qty *</label>
                                            <input type="number" :name="`items[${index}][quantity]`"
                                                x-model.number="item.quantity" min="1" @input="calculateTotals()"
                                                class="w-full text-sm border-truffle-medium/30 rounded-md disabled:bg-[#F5F2EA]"
                                                required {{ $order->isFinanciallyLocked() ? 'disabled' : '' }}>
                                        </div>

                                        {{-- Unit Price --}}
                                        <div class="md:col-span-3">
                                            <label class="block text-xs font-semibold text-truffle-extra-dark mb-1">Unit Price ($)
                                                *</label>
                                            <input type="number" step="0.01" :name="`items[${index}][unit_price]`"
                                                x-model.number="item.unit_price" @input="calculateTotals()"
                                                class="w-full text-sm border-truffle-medium/30 rounded-md disabled:bg-[#F5F2EA]"
                                                required {{ $order->isFinanciallyLocked() ? 'disabled' : '' }}>
                                        </div>

                                        <div class="md:col-span-1">
                                            <label class="block text-xs font-semibold text-truffle-extra-dark mb-1">Weight</label>
                                            <input type="number" step="0.001" :name="`items[${index}][weight_kg]`"
                                                x-model.number="item.weight_kg" @input="calculateTotals()"
                                                class="w-full text-sm border-truffle-medium/30 rounded-md disabled:bg-[#F5F2EA]" {{ $order->isFinanciallyLocked() ? 'disabled' : '' }}>
                                        </div>
                                        <div class="md:col-span-3 flex gap-1">
                                            <div class="w-1/3">
                                                <label class="block text-xs font-semibold text-truffle-extra-dark mb-1">L(cm)</label>
                                                <input type="number" step="0.01" :name="`items[${index}][length]`"
                                                    x-model.number="item.length"
                                                    class="w-full text-sm border-truffle-medium/30 rounded-md disabled:bg-[#F5F2EA]"
                                                    {{ $order->isFinanciallyLocked() ? 'disabled' : '' }}>
                                            </div>
                                            <div class="w-1/3">
                                                <label class="block text-xs font-semibold text-truffle-extra-dark mb-1">W(cm)</label>
                                                <input type="number" step="0.01" :name="`items[${index}][width]`"
                                                    x-model.number="item.width"
                                                    class="w-full text-sm border-truffle-medium/30 rounded-md disabled:bg-[#F5F2EA]"
                                                    {{ $order->isFinanciallyLocked() ? 'disabled' : '' }}>
                                            </div>
                                            <div class="w-1/3">
                                                <label class="block text-xs font-semibold text-truffle-extra-dark mb-1">H(cm)</label>
                                                <input type="number" step="0.01" :name="`items[${index}][height]`"
                                                    x-model.number="item.height"
                                                    class="w-full text-sm border-truffle-medium/30 rounded-md disabled:bg-[#F5F2EA]"
                                                    {{ $order->isFinanciallyLocked() ? 'disabled' : '' }}>
                                            </div>
                                        </div>

                                        {{-- Item Discount --}}
                                        <div
                                            class="md:col-span-5 md:col-start-1 pt-2 border-t border-truffle-medium/30 mt-2 flex gap-3">
                                            <div class="w-1/2">
                                                <label class="block text-xs font-medium text-truffle-extra-dark mb-1">Item
                                                    Discount</label>
                                                <select :name="`items[${index}][item_discount_type]`"
                                                    x-model="item.item_discount_type" @change="calculateTotals()"
                                                    class="w-full text-xs border-truffle-medium/30 rounded-md disabled:bg-[#F5F2EA]"
                                                    {{ $order->isFinanciallyLocked() ? 'disabled' : '' }}>
                                                    <option value="none">None</option>
                                                    <option value="percent">Percent (%)</option>
                                                    <option value="fixed">Fixed ($)</option>
                                                </select>
                                            </div>
                                            <div class="w-1/2" x-show="item.item_discount_type !== 'none'">
                                                <label class="block text-xs font-medium text-truffle-extra-dark mb-1">Value</label>
                                                <input type="number" step="0.01"
                                                    :name="`items[${index}][item_discount_value]`"
                                                    x-model.number="item.item_discount_value" @input="calculateTotals()"
                                                    class="w-full text-xs border-truffle-medium/30 rounded-md disabled:bg-[#F5F2EA]"
                                                    {{ $order->isFinanciallyLocked() ? 'disabled' : '' }}>
                                            </div>
                                        </div>

                                        {{-- Line Total --}}
                                        <div class="md:col-span-3 md:col-start-10 flex flex-col justify-end text-right">
                                            <div class="text-xs text-truffle-extra-dark line-through"
                                                x-show="item.item_discount_amount > 0">
                                                $<span x-text="(item.unit_price * item.quantity).toFixed(2)"></span>
                                            </div>
                                            <div class="text-lg font-bold text-truffle-extra-dark">
                                                $<span x-text="item.line_total.toFixed(2)"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div x-show="items.length === 0"
                                class="text-center py-6 border-2 border-dashed border-truffle-medium/30 rounded-lg text-truffle-extra-dark/70">
                                No items. Add items to recalculate totals.
                            </div>
                        </div>
                    </div>

                    {{-- Shipping Options --}}
                    <div class="bg-cream rounded-xl shadow-sm border border-truffle-medium/30 p-6">
                        <h3 class="text-lg font-semibold text-truffle-extra-dark mb-4 border-b pb-2">Shipping Information <span
                                class="text-sm font-normal text-truffle-extra-dark">(Always editable)</span></h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-truffle-extra-dark mb-1">Provider</label>
                                <select name="shipping_provider_id" x-model="shipping_provider_id"
                                    class="w-full border-truffle-medium/30 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500">
                                    <option value="">Select Provider...</option>
                                    @foreach($shippingProviders as $p)
                                        <option value="{{ $p->id }}" {{ (old('shipping_provider_id', $order->shipping_provider_id) == $p->id) || (empty(old('shipping_provider_id', $order->shipping_provider_id)) && strtolower($p->name) === 'standard shipping') ? 'selected' : '' }}>{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-truffle-extra-dark mb-1">Tracking Number</label>
                                <input type="text" name="tracking_number"
                                    value="{{ old('tracking_number', $order->tracking_number) }}"
                                    class="w-full border-truffle-medium/30 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500">
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Summary Sidebar (Right) --}}
                <div class="lg:col-span-1 space-y-6">

                    @if($order->isFinanciallyLocked())
                        <div class="bg-amber-50 border-l-4 border-amber-600 rounded-r-xl shadow-sm p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                <h4 class="font-bold text-amber-900">Financials Locked</h4>
                            </div>
                            <p class="text-xs text-amber-800 leading-tight">Prices and items cannot be edited because the order
                                has been marked as PAID ({{ $order->financial_locked_at->format('M d, Y') }}).</p>
                        </div>
                    @endif

                    <div class="bg-cream rounded-xl shadow-sm border border-truffle-medium/30 p-6 sticky top-6">
                        <h3 class="text-lg font-semibold text-truffle-extra-dark mb-4 border-b pb-2">Financial Summary</h3>

                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between text-truffle-extra-dark font-medium">
                                <span>Subtotal</span>
                                <span>$<span x-text="afterItemDisc.toFixed(2)"></span></span>
                            </div>

                            <div class="border-t border-gray-100 pt-3 mt-3 shadow-inner bg-[#F5F2EA] -mx-6 px-6 pb-2">
                                <label class="block text-xs font-medium text-truffle-extra-dark mb-1">Order-level Discount</label>
                                <div class="flex gap-2">
                                    <select name="order_discount_type" x-model="order_discount_type"
                                        @change="calculateTotals()"
                                        class="w-1/2 text-sm border-truffle-medium/30 rounded-md disabled:bg-[#E8E2D2]" {{ $order->isFinanciallyLocked() ? 'disabled' : '' }}>
                                        <option value="none">None</option>
                                        <option value="percent">Percent (%)</option>
                                        <option value="fixed">Fixed ($)</option>
                                    </select>
                                    <input type="number" step="0.01" name="order_discount_value"
                                        x-model.number="order_discount_value" x-show="order_discount_type !== 'none'"
                                        @input="calculateTotals()"
                                        class="w-1/2 text-sm border-truffle-medium/30 rounded-md disabled:bg-[#E8E2D2]" {{ $order->isFinanciallyLocked() ? 'readonly' : '' }}>
                                </div>
                                <div class="text-right text-xs text-red-500 mt-1 font-medium"
                                    x-show="order_discount_amount > 0">
                                    -$<span x-text="order_discount_amount.toFixed(2)"></span>
                                </div>
                            </div>

                            <div class="border-t border-gray-100 pt-3 mt-3">
                                <label class="block text-xs font-medium text-truffle-extra-dark mb-1">Shipping Cost ($)</label>
                                <div class="flex gap-2">
                                    <input type="number" step="0.01" name="shipping_cost" x-model.number="shipping_cost"
                                        @input="calculateTotals()"
                                        class="w-2/3 text-sm border-truffle-medium/30 rounded-md disabled:bg-[#E8E2D2]" {{ $order->isFinanciallyLocked() ? 'readonly' : '' }}>

                                    @if(!$order->isFinanciallyLocked())
                                        <button type="button" @click="autoCalculateShipping()" :disabled="isCalculatingShipping"
                                            class="w-1/3 bg-[#F5F2EA] border border-truffle-medium/30 hover:bg-[#E8E2D2] rounded-md text-xs font-medium px-2 py-1 flex items-center justify-center transition-colors">
                                            <span x-show="!isCalculatingShipping">Auto Calc</span>
                                            <span x-show="isCalculatingShipping" class="flex gap-1 items-center">
                                                <svg class="animate-spin h-3 w-3 text-truffle-extra-dark"
                                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                        stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor"
                                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                    </path>
                                                </svg>
                                                Calc...
                                            </span>
                                        </button>
                                    @endif
                                </div>
                                <div x-show="shippingError === 'Over Weight'" x-cloak
                                    class="flex items-center gap-2 mt-2 px-3 py-2 rounded-md border-2 border-[#8f3222] bg-[#8f3222]/[0.08]">
                                    <svg class="w-4 h-4 text-[#8f3222] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                    </svg>
                                    <span class="text-xs font-bold uppercase tracking-wide text-[#8f3222]">Over Weight</span>
                                </div>
                                <div x-show="shippingError && shippingError !== 'Over Weight'" class="text-red-500 text-xs mt-1" x-text="shippingError"></div>
                                <div x-show="shippingSuccess" class="text-green-premium text-xs mt-1">Shipping updated via
                                    calculator!</div>
                            </div>

                            <div class="pt-3">
                                <label class="block text-xs font-medium text-truffle-extra-dark mb-1">Delivery Target (Days)</label>
                                <input type="number" name="delivery_period_days" value="{{ $order->delivery_period_days }}"
                                    class="w-full text-sm border-truffle-medium/30 rounded-md">
                            </div>

                            <div class="border-t-2 border-truffle-medium/30 pt-4 mt-4 flex justify-between items-end">
                                <div>
                                    <div class="text-truffle-extra-dark font-bold text-lg">Grand Total</div>
                                    <div class="text-xs text-truffle-extra-dark">Total Wt: <span
                                            x-text="total_weight.toFixed(2)"></span> kg</div>
                                </div>
                                <div class="text-2xl font-black text-green-premium">
                                    $<span x-text="grand_total.toFixed(2)"></span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6">
                            <button type="submit"
                                class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-xl hover:bg-blue-700 transition-colors shadow-sm cursor-pointer"
                                :disabled="items.length === 0 && !{{ $order->isFinanciallyLocked() ? 'true' : 'false' }}">
                                Save Changes
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>

    @php
        function buildAlpineArray($items)
        {
            $arr = [];
            $id = 1;
            foreach ($items as $i) {
                $arr[] = [
                    'id' => $id++,
                    'product_id' => $i->product_id,
                    'product_name' => str_replace("'", "\'", $i->product_name),
                    'quantity' => $i->quantity,
                    'unit_price' => floatval($i->unit_price),
                    'weight_kg' => floatval($i->weight_kg),
                    'length' => isset($i->product_snapshot['length']) && $i->product_snapshot['length'] ? floatval($i->product_snapshot['length']) : null,
                    'width' => isset($i->product_snapshot['width']) && $i->product_snapshot['width'] ? floatval($i->product_snapshot['width']) : null,
                    'height' => isset($i->product_snapshot['height']) && $i->product_snapshot['height'] ? floatval($i->product_snapshot['height']) : null,
                    'item_discount_type' => $i->item_discount_type,
                    'item_discount_value' => floatval($i->item_discount_value),
                    'item_discount_amount' => floatval($i->item_discount_amount),
                    'line_total' => floatval($i->line_total)
                ];
            }
            return json_encode($arr);
        }
    @endphp

    <script>
        function orderEditForm() {
            return {
                items: {!! buildAlpineArray($order->items) !!},
                nextId: 999, // safe buffer

                // Initial setup
                order_discount_type: '{{ $order->order_discount_type }}',
                order_discount_value: {{ floatval($order->order_discount_value) }},
                shipping_cost: {{ floatval($order->shipping_cost) }},
                shipping_provider_id: '{{ old('shipping_provider_id', $order->shipping_provider_id) }}',
                client_id: '{{ old('client_id', $order->client_id) }}',

                // Calculated
                subtotal_gross: 0,
                afterItemDisc: 0,
                item_discount_total: 0,
                order_discount_amount: 0,
                total_weight: 0,
                grand_total: 0,

                // Calculation
                isCalculatingShipping: false,
                shippingError: '',
                shippingSuccess: false,

                init() {
                    if (this.items.length === 0) this.addItem();
                    this.calculateTotals();
                },

                addItem() {
                    this.items.push({
                        id: this.nextId++,
                        product_id: '',
                        product_name: '',
                        quantity: 1,
                        unit_price: 0,
                        weight_kg: 0,
                        length: null,
                        width: null,
                        height: null,
                        item_discount_type: 'none',
                        item_discount_value: 0,
                        item_discount_amount: 0,
                        line_total: 0
                    });
                    this.$nextTick(() => { this.calculateTotals(); });
                },

                removeItem(index) {
                    this.items.splice(index, 1);
                    this.calculateTotals();
                },

                populateItemData(index) {
                    const selectEl = document.querySelector(`select[name="items[${index}][product_id]"]`);
                    if (selectEl && selectEl.selectedIndex > 0) {
                        const option = selectEl.options[selectEl.selectedIndex];
                        this.items[index].unit_price = parseFloat(option.dataset.price) || 0;
                        this.items[index].weight_kg = parseFloat(option.dataset.weight) || 0;
                        this.items[index].length = parseFloat(option.dataset.length) || null;
                        this.items[index].width = parseFloat(option.dataset.width) || null;
                        this.items[index].height = parseFloat(option.dataset.height) || null;
                    } else {
                        this.items[index].unit_price = 0;
                        this.items[index].weight_kg = 0;
                        this.items[index].length = null;
                        this.items[index].width = null;
                        this.items[index].height = null;
                    }
                    this.calculateTotals();
                },

                calculateTotals() {
                    let gross = 0;
                    let itemDisc = 0;
                    let wt = 0;
                    let afterItemDisc = 0;

                    this.items.forEach(item => {
                        // Ensure numbers
                        item.quantity = parseInt(item.quantity) || 0;
                        item.unit_price = parseFloat(item.unit_price) || 0;
                        item.weight_kg = parseFloat(item.weight_kg) || 0;
                        item.item_discount_value = parseFloat(item.item_discount_value) || 0;

                        let rowGross = item.unit_price * item.quantity;
                        item.item_discount_amount = 0;

                        if (item.item_discount_type === 'percent') {
                            item.item_discount_amount = (rowGross * (item.item_discount_value / 100));
                        } else if (item.item_discount_type === 'fixed') {
                            item.item_discount_amount = Math.min(item.item_discount_value, rowGross);
                        }

                        item.line_total = Math.max(0, rowGross - item.item_discount_amount);

                        gross += rowGross;
                        itemDisc += item.item_discount_amount;
                        wt += (item.weight_kg * item.quantity);
                        afterItemDisc += item.line_total;
                    });

                    this.subtotal_gross = gross;
                    this.afterItemDisc = afterItemDisc;
                    this.item_discount_total = itemDisc;
                    this.total_weight = wt;

                    // Order level discount
                    this.order_discount_amount = 0;
                    let orderVal = parseFloat(this.order_discount_value) || 0;

                    if (this.order_discount_type === 'percent') {
                        this.order_discount_amount = (afterItemDisc * (orderVal / 100));
                    } else if (this.order_discount_type === 'fixed') {
                        this.order_discount_amount = Math.min(orderVal, afterItemDisc);
                    }

                    let sc = parseFloat(this.shipping_cost) || 0;

                    this.grand_total = Math.max(0, afterItemDisc - this.order_discount_amount + sc);
                },

                async autoCalculateShipping() {
                    this.shippingError = '';
                    this.shippingSuccess = false;

                    const clientId = this.client_id;
                    const providerId = this.shipping_provider_id;

                    if (!clientId) {
                        this.shippingError = 'Please select a Client Profile first.';
                        return;
                    }

                    if (!providerId) {
                        this.shippingError = 'Please select a Shipping Provider.';
                        return;
                    }

                    if (this.items.length === 0) {
                        this.shippingError = 'Please add line items first.';
                        return;
                    }

                    this.isCalculatingShipping = true;

                    try {
                        const response = await fetch('{{ route("admin.orders.calculate-shipping") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                client_id: clientId,
                                shipping_provider_id: providerId,
                                items: this.items
                            })
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            throw new Error(data.error || 'Failed to calculate shipping');
                        }

                        this.shipping_cost = parseFloat(data.cost);
                        this.calculateTotals();
                        this.shippingSuccess = true;

                        setTimeout(() => { this.shippingSuccess = false; }, 3000);

                    } catch (err) {
                        this.shippingError = err.message;
                    } finally {
                        this.isCalculatingShipping = false;
                    }
                }
            }
        }
    </script>
@endsection
