@extends('admin.layout')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-truffle-extra-dark leading-tight">
            Inquiry Details #{{ $inquiry->id }}
        </h2>
        <a href="{{ route('admin.inquiries.index') }}" class="text-truffle-extra-dark hover:text-truffle-extra-dark">Back to List</a>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Customer Info -->
        <div class="bg-cream p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium border-b pb-2 mb-4">Customer Information</h3>
            <p><strong>Name:</strong> {{ $inquiry->name }}</p>
            <p><strong>Email:</strong> {{ $inquiry->email }}</p>
            <p><strong>Phone:</strong> {{ $inquiry->phone }}</p>
            <p class="mt-4"><strong>Address:</strong></p>
            <p>{{ $inquiry->address_line }}</p>
            <p>{{ $inquiry->city }}, {{ $inquiry->zip_code }}</p>
            <p>{{ $inquiry->country }}</p>
        </div>

        <!-- Product Info -->
        <div class="bg-cream p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium border-b pb-2 mb-4">Product Interest</h3>
            @if($inquiry->product)
                <p><strong>Product:</strong> {{ $inquiry->product->name }}</p>
                <p><strong>Price:</strong> ${{ $inquiry->product->price }}</p>
                <p><strong>Weight:</strong> {{ $inquiry->product->weight }} kg</p>
                <p><strong>Dimensions:</strong>
                    {{ $inquiry->product->length }}x{{ $inquiry->product->width }}x{{ $inquiry->product->height }} cm</p>
                <div class="mt-4">
                    <img src="{{ asset($inquiry->product->main_image) }}" alt="Product Image" class="h-32 object-cover rounded">
                </div>
            @else
                <p class="text-red-500">Product no longer exists.</p>
            @endif
        </div>

        <!-- Message & Reply -->
        <div class="bg-cream p-6 rounded-lg shadow md:col-span-2">
            <h3 class="text-lg font-medium border-b pb-2 mb-4">Inquiry</h3>
            <div class="bg-[#F5F2EA] p-4 rounded mb-6">
                <p class="text-truffle-extra-dark">{{ $inquiry->message }}</p>
                <span class="text-xs text-truffle-extra-dark mt-2 block">{{ $inquiry->created_at->diffForHumans() }}</span>
            </div>

            @if($inquiry->admin_reply)
                <div class="bg-blue-50 p-4 rounded mb-6 border-l-4 border-blue-500">
                    <h4 class="text-sm font-bold text-blue-700 mb-1">Admin Reply:</h4>
                    <p class="text-truffle-extra-dark">{{ $inquiry->admin_reply }}</p>
                    <span class="text-xs text-truffle-extra-dark mt-2 block">{{ $inquiry->updated_at->diffForHumans() }}</span>
                </div>
            @endif

            <form action="{{ route('admin.inquiries.reply', $inquiry) }}" method="POST" class="mb-8">
                @csrf
                <label class="block text-sm font-medium text-truffle-extra-dark mb-2">Reply to Customer</label>
                <textarea name="admin_reply" rows="3"
                    class="w-full border p-2 rounded focus:ring-green-500 focus:border-green-500"
                    placeholder="Type your reply here..."></textarea>
                <button type="submit" class="mt-2 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Send
                    Reply</button>
            </form>

            <div class="border-t pt-6">
                <h3 class="text-lg font-medium mb-4">Actions</h3>
                @if($inquiry->status != 'checkout_sent' && $inquiry->status != 'completed')
                    <div class="bg-yellow-50 p-4 rounded border border-yellow-200">
                        <p class="mb-2 text-sm text-yellow-800">If the customer is ready to purchase, generate a unique checkout
                            link for them.</p>
                        <form action="{{ route('admin.inquiries.send-checkout', $inquiry) }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="bg-green-premium text-white px-6 py-2 rounded font-bold hover:bg-green-800">Generate & Send
                                Checkout Link</button>
                        </form>
                    </div>
                @else
                    <div class="bg-purple-50 p-4 rounded border border-purple-200">
                        <p class="text-purple-800 font-bold">Checkout Link Generated</p>
                        <input type="text" readonly value="{{ route('checkout', ['token' => $inquiry->checkout_token]) }}"
                            class="w-full mt-2 p-2 bg-cream border rounded text-sm text-truffle-extra-dark">
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
