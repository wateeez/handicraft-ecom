<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            line-height: 1.5;
            font-size: 13px;
            margin: 0;
            padding: 0;
        }

        .header {
            width: 100%;
            margin-bottom: 30px;
            border-bottom: 2px solid #22c55e;
            padding-bottom: 15px;
        }

        .logo {
            font-size: 26px;
            font-weight: bold;
            color: #16a34a;
            line-height: 1;
        }

        .invoice-title {
            font-size: 24px;
            color: #374151;
            margin: 0;
            text-transform: uppercase;
        }

        .meta-data {
            text-align: right;
        }

        .status-badge {
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-issued {
            background-color: #dcfce7;
            color: #16a34a;
        }

        .status-draft {
            background-color: #fef9c3;
            color: #ca8a04;
        }

        .status-voided {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .details-grid {
            width: 100%;
            margin-bottom: 30px;
        }

        .details-grid td {
            vertical-align: top;
            width: 50%;
        }

        .section-title {
            font-size: 11px;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .info-text {
            font-size: 13px;
            margin: 0;
            line-height: 1.4;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table.items th {
            background-color: #f9fafb;
            color: #374151;
            padding: 10px;
            text-align: left;
            font-size: 12px;
            font-weight: bold;
            border-bottom: 1px solid #e5e7eb;
        }

        table.items td {
            padding: 10px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: top;
        }

        .text-right {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        .totals-table {
            width: 300px;
            border-collapse: collapse;
            float: right;
        }

        .totals-table td {
            padding: 5px 10px;
        }

        .grand-total {
            font-weight: bold;
            font-size: 16px;
            border-top: 1px solid #374151;
            padding-top: 10px !important;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            color: #9ca3af;
            font-size: 11px;
            border-top: 1px solid #f3f4f6;
            padding-top: 15px;
            clear: both;
        }

        .void-watermark {
            position: absolute;
            top: 25%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            color: rgba(239, 68, 68, 0.08);
            font-weight: bold;
            z-index: -1;
        }
    </style>
</head>

<body>
    <div style="padding: 30px;">
        @if($invoice->status === 'voided')
            <div class="void-watermark">VOID</div>
        @endif

        <table border="0" cellpadding="0" cellspacing="0" width="100%"
            style="border-collapse: collapse; margin-bottom: 20px;">
            <tr>
                <td style="vertical-align: top; border-bottom: 2px solid #22c55e; padding-bottom: 15px;">
                    <div class="logo">{{ config('app.name', 'OMS Application') }}</div>
                    <div style="color: #6b7280; font-size: 11px; margin-top: 5px;">
                        123 Commerce Blvd.<br>
                        Suite 400<br>
                        Business City, ST 12345
                    </div>
                </td>
                <td
                    style="vertical-align: top; text-align: right; border-bottom: 2px solid #22c55e; padding-bottom: 15px;">
                    <h1 class="invoice-title">Invoice</h1>
                    <p style="margin: 5px 0;"><strong>#{{ $invoice->invoice_number }}</strong></p>
                    <span class="status-badge status-{{ $invoice->status }}">{{ $invoice->status }}</span>
                    <p style="margin: 10px 0 0 0; font-size: 11px; color: #6b7280;">Date:
                        {{ ($invoice->issued_at ?? $invoice->created_at)->format('M d, Y') }}
                    </p>
                    <p style="margin: 2px 0 0 0; font-size: 11px; color: #6b7280;">
                        {{ ($invoice->financial_snapshot['order_type'] ?? $invoice->order->type) === 'inquiry' ? 'Inquiry' : 'Order' }}
                        Reference: #{{ $invoice->financial_snapshot['order_number'] ?? $invoice->order->order_number }}
                    </p>
                </td>
            </tr>
        </table>

        @php $c = $invoice->client_snapshot; @endphp
        <table border="0" cellpadding="0" cellspacing="0" width="100%"
            style="border-collapse: collapse; margin-bottom: 30px;">
            <tr>
                <td width="55%" style="vertical-align: top;">
                    <div class="section-title">Billed To</div>
                    <p class="info-text">
                        <strong>{{ $c['name'] ?? 'Walk-in Customer' }}</strong><br>
                        @if(!empty($c['buyer_id'])) <span style="color: #6b7280; font-size: 11px;">Buyer ID:
                            {{ $c['buyer_id'] }}</span><br> @endif
                        @if(!empty($c['company'])) {{ $c['company'] }}<br> @endif
                        @if(!empty($c['address']))
                            {{ $c['address'] }}<br>
                            {{ $c['city'] ?? '' }} {{ $c['state'] ? ', ' . $c['state'] : '' }}
                            {{ $c['zip_code'] ?? '' }}<br>
                            {{ country_name($c['country'] ?? null) }}
                        @endif
                    </p>
                </td>
                <td width="45%" style="vertical-align: top;">
                    @if(!empty($c['email']) || !empty($c['phone']))
                        <div class="section-title">Contact Info</div>
                        <p class="info-text">
                            @if(!empty($c['email'])) {{ $c['email'] }}<br> @endif
                            @if(!empty($c['phone'])) {{ $c['phone'] }} @endif
                        </p>
                    @endif
                </td>
            </tr>
        </table>

        @php $fin = $invoice->financial_snapshot; @endphp

        <table border="0" cellpadding="0" cellspacing="0" width="100%"
            style="border-collapse: collapse; margin-bottom: 30px;">
            <tr>
                <td width="55%" style="vertical-align: top;">
                    <div class="section-title">Ship To</div>
                    <p class="info-text">
                        @if(!empty($fin['shipping_address'])) {{ $fin['shipping_address'] }}<br> @endif
                        @if(!empty($fin['shipping_country'])) {{ $fin['shipping_country'] }} @endif
                    </p>
                </td>
                <td width="45%" style="vertical-align: top;">
                    @if(!empty($fin['shipping_provider_name']))
                        <div class="section-title">Shipping Provider</div>
                        <p class="info-text">{{ $fin['shipping_provider_name'] }}</p>
                    @endif
                </td>
            </tr>
        </table>

        <table border="0" cellpadding="0" cellspacing="0" width="100%"
            style="border-collapse: collapse; margin-bottom: 20px;">
            <thead>
                <tr>
                    <th
                        style="background-color: #f9fafb; border-bottom: 1px solid #e5e7eb; padding: 10px; text-align: left; font-size: 11px; font-weight: bold;">
                        Description</th>
                    <th style="background-color: #f9fafb; border-bottom: 1px solid #e5e7eb; padding: 10px; text-align: center; font-size: 11px; font-weight: bold;"
                        width="50">Qty</th>
                    <th style="background-color: #f9fafb; border-bottom: 1px solid #e5e7eb; padding: 10px; text-align: right; font-size: 11px; font-weight: bold;"
                        width="80">Unit Price</th>
                    <th style="background-color: #f9fafb; border-bottom: 1px solid #e5e7eb; padding: 10px; text-align: right; font-size: 11px; font-weight: bold;"
                        width="80">Subtotal</th>
                    <th style="background-color: #f9fafb; border-bottom: 1px solid #e5e7eb; padding: 10px; text-align: right; font-size: 11px; font-weight: bold;"
                        width="80">Discount</th>
                    <th style="background-color: #f9fafb; border-bottom: 1px solid #e5e7eb; padding: 10px; text-align: right; font-size: 11px; font-weight: bold;"
                        width="90">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($fin['items'] as $item)
                    <tr>
                        <td style="padding: 10px; border-bottom: 1px solid #f3f4f6;">
                            <strong>{{ $item['product_name'] }}</strong>
                            @if(!empty($item['variant'])) <span
                                style="color: #6b7280; font-size: 10px; display: block;">Variant:
                            {{ $item['variant'] }}</span> @endif
                            @if($item['product_sku']) <span style="color: #6b7280; font-size: 10px; display: block;">SKU:
                            {{ $item['product_sku'] }}</span> @endif

                            @if(!empty($item['dimensions'])) <span
                                style="color: #6b7280; font-size: 10px; display: block;">Dim:
                            {{ $item['dimensions'] }}</span> @endif
                        </td>
                        <td style="padding: 10px; border-bottom: 1px solid #f3f4f6; text-align: center;">
                            {{ $item['quantity'] }}
                        </td>
                        <td style="padding: 10px; border-bottom: 1px solid #f3f4f6; text-align: right;">
                            ${{ number_format($item['unit_price'], 2) }}
                        </td>
                        <td style="padding: 10px; border-bottom: 1px solid #f3f4f6; text-align: right;">
                            ${{ number_format($item['item_subtotal'] ?? ($item['unit_price'] * $item['quantity']), 2) }}
                        </td>
                        <td
                            style="padding: 10px; border-bottom: 1px solid #f3f4f6; text-align: right; color: #dc2626; font-size: 11px;">
                            @if($item['item_discount_amount'] > 0)
                                -${{ number_format($item['item_discount_amount'], 2) }}
                            @else
                                -
                            @endif
                        </td>
                        <td style="padding: 10px; border-bottom: 1px solid #f3f4f6; text-align: right; font-weight: bold;">
                            ${{ number_format($item['line_total'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;">
            <tr>
                <td width="60%"></td>
                <td width="40%">
                    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;">
                        <tr>
                            <td style="padding: 4px 0; color: #6b7280;">Subtotal</td>
                            <td style="padding: 4px 0; text-align: right;">
                                ${{ number_format(collect($fin['items'])->sum(fn($i) => $i['item_subtotal'] ?? ($i['unit_price'] * $i['quantity'])), 2) }}</td>
                        </tr>
                        @if($fin['item_discount_total'] > 0)
                            <tr>
                                <td style="padding: 4px 0; color: #dc2626;">Item Discounts</td>
                                <td style="padding: 4px 0; text-align: right; color: #dc2626;">
                                    -${{ number_format($fin['item_discount_total'], 2) }}</td>
                            </tr>
                        @endif
                        @if($fin['order_discount_amount'] > 0)
                            <tr>
                                <td style="padding: 4px 0; color: #dc2626;">Order Discount</td>
                                <td style="padding: 4px 0; text-align: right; color: #dc2626;">
                                    -${{ number_format($fin['order_discount_amount'], 2) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td style="padding: 4px 0; color: #6b7280;">Shipping</td>
                            <td style="padding: 4px 0; text-align: right;">
                                ${{ number_format($fin['shipping_cost'], 2) }}</td>
                        </tr>
                        <tr>
                            <td
                                style="padding: 10px 0 0 0; font-weight: bold; font-size: 16px; border-top: 1px solid #374151;">
                                Grand Total</td>
                            <td
                                style="padding: 10px 0 0 0; text-align: right; font-weight: bold; font-size: 16px; border-top: 1px solid #374151; color: #16a34a;">
                                ${{ number_format($fin['grand_total'], 2) }}
                                <span
                                    style="font-size: 11px; font-weight: normal; color: #9ca3af;">{{ $fin['currency'] }}</span>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>


        <div class="footer">
            <p style="margin: 0; margin-bottom: 5px;">Thank you for your business!</p>
            <p style="margin: 0;">Payment is due upon receipt. If you have any questions regarding this invoice, please
                contact support at {{ config('mail.from.address', 'support@example.com') }}.</p>
        </div>
    </div>
</body>

</html>
