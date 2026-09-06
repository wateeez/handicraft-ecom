<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Quote Request</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f4f1eb;
            color: #2d2d2d;
            padding: 30px 10px;
        }
        .wrapper {
            max-width: 620px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #4a3728 0%, #7c5c44 100%);
            padding: 30px 32px;
            color: #fff;
        }
        .header h1 {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        .header p {
            margin-top: 6px;
            font-size: 13px;
            opacity: 0.8;
        }
        .badge {
            display: inline-block;
            background: #f59e0b;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 10px;
        }
        .body {
            padding: 28px 32px;
        }
        .section-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #7c5c44;
            margin-bottom: 12px;
            padding-bottom: 6px;
            border-bottom: 2px solid #f4f1eb;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 24px;
        }
        .info-item {
            background: #faf8f5;
            border: 1px solid #e8e2d2;
            border-radius: 8px;
            padding: 10px 14px;
        }
        .info-item .label {
            font-size: 11px;
            color: #9ca3af;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-item .value {
            font-size: 14px;
            font-weight: 600;
            color: #2d2d2d;
            margin-top: 2px;
        }
        .info-full {
            background: #faf8f5;
            border: 1px solid #e8e2d2;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 10px;
        }
        .info-full .label {
            font-size: 11px;
            color: #9ca3af;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-full .value {
            font-size: 14px;
            font-weight: 600;
            color: #2d2d2d;
            margin-top: 2px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
            font-size: 13px;
        }
        .items-table thead tr {
            background: #4a3728;
            color: #fff;
        }
        .items-table thead th {
            padding: 10px 12px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .items-table thead th:last-child { text-align: right; }
        .items-table tbody tr {
            border-bottom: 1px solid #f0ece4;
        }
        .items-table tbody tr:last-child { border-bottom: none; }
        .items-table tbody td {
            padding: 12px;
            vertical-align: top;
        }
        .items-table tbody td:last-child { text-align: right; font-weight: 700; }
        .product-name { font-weight: 600; color: #2d2d2d; }
        .product-meta { font-size: 11px; color: #9ca3af; margin-top: 2px; }
        .totals-box {
            background: #faf8f5;
            border: 1px solid #e8e2d2;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 24px;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            padding: 4px 0;
            color: #555;
        }
        .totals-row.total {
            font-size: 16px;
            font-weight: 700;
            color: #2d2d2d;
            border-top: 2px solid #e8e2d2;
            margin-top: 8px;
            padding-top: 10px;
        }
        .footer {
            background: #4a3728;
            padding: 18px 32px;
            text-align: center;
            font-size: 12px;
            color: rgba(255,255,255,0.6);
        }
        .footer a { color: rgba(255,255,255,0.8); }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Header -->
        <div class="header">
            <h1>📦 New Quote Request</h1>
            <p>A customer has submitted a cart quote request from your store.</p>
            <span class="badge">Action Required</span>
        </div>

        <!-- Body -->
        <div class="body">

            <!-- Customer Info -->
            <div class="section-title">Customer Details</div>
            <div class="info-grid">
                <div class="info-item">
                    <div class="label">Full Name</div>
                    <div class="value">{{ $customerData['name'] }}</div>
                </div>
                <div class="info-item">
                    <div class="label">Email Address</div>
                    <div class="value">{{ $customerData['email'] }}</div>
                </div>
                <div class="info-item">
                    <div class="label">Phone Number</div>
                    <div class="value">{{ $customerData['phone'] ?: '—' }}</div>
                </div>
                <div class="info-item">
                    <div class="label">Country</div>
                    <div class="value">{{ $customerData['country'] }}</div>
                </div>
            </div>
            <div class="info-full">
                <div class="label">Shipping Address</div>
                <div class="value">
                    {{ $customerData['address'] }},
                    {{ $customerData['city'] }}
                    @if($customerData['zip_code']) {{ $customerData['zip_code'] }} @endif
                    — {{ $customerData['country'] }}
                </div>
            </div>

            <!-- Requested Items -->
            <div class="section-title" style="margin-top: 20px;">Items Requested</div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th style="text-align:center">Qty</th>
                        <th style="text-align:right">Unit Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cartItems as $item)
                    <tr>
                        <td>
                            <div class="product-name">{{ $item['product']->name }}</div>
                            <div class="product-meta">
                                SKU: {{ $item['product']->sku ?? '—' }}
                                &nbsp;|&nbsp;
                                {{ $item['product']->formatted_length ?? '?' }}×{{ $item['product']->formatted_width ?? '?' }}×{{ $item['product']->formatted_height ?? '?' }} cm
                                &nbsp;|&nbsp;
                                {{ $item['product']->formatted_weight ?? '?' }} kg
                            </div>
                        </td>
                        <td style="text-align:center">{{ $item['quantity'] }}</td>
                        <td style="text-align:right">${{ number_format($item['unit_price'], 2) }}</td>
                        <td>${{ number_format($item['subtotal'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Totals -->
            <div class="section-title">Order Totals</div>
            <div class="totals-box">
                <div class="totals-row">
                    <span>Subtotal</span>
                    <span>${{ number_format($subtotal, 2) }}</span>
                </div>
                <div class="totals-row">
                    <span>Shipping ({{ $shippingProvider ?: 'Not selected' }})</span>
                    <span>{{ $shippingCost > 0 ? '$' . number_format($shippingCost, 2) : 'TBD' }}</span>
                </div>
                <div class="totals-row total">
                    <span>Estimated Total</span>
                    <span>${{ number_format($subtotal + $shippingCost, 2) }}</span>
                </div>
            </div>

        </div>

        <div class="footer">
            This quote request was submitted via your store. Reply to the customer at
            <a href="mailto:{{ $customerData['email'] }}">{{ $customerData['email'] }}</a>
        </div>
    </div>
</body>
</html>
