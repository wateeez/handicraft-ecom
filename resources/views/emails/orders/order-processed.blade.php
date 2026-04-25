<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: #2563eb;
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
        }

        .content {
            background: #f9f9f9;
            padding: 20px;
            border: 1px solid #e5e7eb;
        }

        .footer {
            background: #f3f4f6;
            padding: 15px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
            border-radius: 0 0 8px 8px;
        }

        .order-items {
            background: white;
            border: 1px solid #e5e7eb;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .item-row:last-child {
            border-bottom: none;
        }

        .item-details {
            flex: 1;
            font-size: 13px;
            color: #666;
        }

        .item-price {
            text-align: right;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2>Your Order is Being Processed</h2>
    </div>
    <div class="content">
        <p>Dear {{ $order->client_snapshot['name'] ?? $order->client?->name ?? 'Customer' }},</p>
        <p>Great news! Your order <strong>#{{ $order->order_number }}</strong> is now being processed by our team.</p>
        <p><strong>Order Total:</strong> ${{ number_format($order->grand_total, 2) }}</p>

        @if($order->items->count() > 0)
            <div class="order-items">
                <h3 style="margin-top: 0; margin-bottom: 10px; font-size: 14px;">Order Items:</h3>
                @foreach($order->items as $item)
                    <div class="item-row">
                        <div class="item-details">
                            <div style="font-weight: bold;">{{ $item->product_snapshot['name'] ?? 'Product' }}</div>
                            <div>Quantity: {{ $item->quantity }}</div>
                            <div>Purchase Type: {{ $item->purchase_type }}</div>
                            @if($item->spiritual_option)
                                <div style="color: #7c3aed;">Spiritual Option: {{ $item->spiritual_option }}</div>
                            @endif
                        </div>
                        <div class="item-price">${{ number_format($item->line_total, 2) }}</div>
                    </div>
                @endforeach
            </div>
        @endif

        <p>We will notify you once your order is dispatched.</p>
        <p>Best regards,<br>The Operations Team</p>
    </div>
    <div class="footer">This is an automated message.</div>
</body>

</html>
