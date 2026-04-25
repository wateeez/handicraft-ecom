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
            background: #16a34a;
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
    </style>
</head>

<body>
    <div class="header">
        <h2>Your Order Has Been Delivered!</h2>
    </div>
    <div class="content">
        <p>Dear {{ $order->client_snapshot['name'] ?? $order->client?->name ?? 'Customer' }},</p>
        <p>Your order <strong>#{{ $order->order_number }}</strong> has been successfully delivered.</p>
        <p>We hope you love your purchase! If you have any questions or concerns, please don't hesitate to contact us.</p>
        
        @if($order->items->count() > 0)
            <div style="background: white; border: 1px solid #e5e7eb; padding: 15px; margin: 15px 0; border-radius: 4px;">
                <h3 style="margin-top: 0; margin-bottom: 10px; font-size: 14px;">Order Items:</h3>
                @foreach($order->items as $item)
                    <div style="padding: 10px 0; border-bottom: 1px solid #f0f0f0;">
                        <div style="font-weight: bold;">{{ $item->product_snapshot['name'] ?? 'Product' }}</div>
                        <div style="font-size: 13px; color: #666;">
                            Quantity: {{ $item->quantity }} | Purchase Type: {{ $item->purchase_type }}
                            @if($item->spiritual_option)
                                | Spiritual Option: {{ $item->spiritual_option }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <p>Thank you for your business!</p>
        <p>Best regards,<br>The Operations Team</p>
    </div>
    <div class="footer">This is an automated message.</div>
</body>

</html>
