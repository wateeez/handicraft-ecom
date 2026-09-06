<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Your Order Has Been Created</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f1eb;font-family:Arial,sans-serif;">

<table border="0" cellpadding="0" cellspacing="0" width="100%" style="padding:30px 10px;">
  <tr>
    <td align="center">
      <table border="0" cellpadding="0" cellspacing="0" width="620" style="background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,0.08);">

        <!-- Header -->
        <tr>
          <td align="center" style="background:linear-gradient(135deg,#4a3728 0%,#7c5c44 100%);padding:36px 32px;">
            <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:700;letter-spacing:0.5px;">
              🛍️ Order Created
            </h1>
            <p style="margin:8px 0 0;color:rgba(255,255,255,0.75);font-size:13px;">
              Your order has been successfully created in our system.
            </p>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="padding:32px 36px;">

            <p style="margin:0 0 20px;font-size:16px;color:#2d2d2d;">
              Dear <strong>{{ $order->client_snapshot['name'] ?? $order->client?->name ?? 'Customer' }}</strong>,
            </p>
            <p style="margin:0 0 24px;font-size:14px;color:#555;line-height:1.6;">
              Thank you for choosing us! A new order has been created for you. Please review the details below.
            </p>

            <!-- Order reference -->
            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background:#faf8f5;border:1px solid #e8e2d2;border-radius:8px;margin-bottom:24px;">
              <tr>
                <td style="padding:16px 20px;">
                  <table border="0" cellpadding="4" cellspacing="0" width="100%">
                    <tr>
                      <td style="font-size:12px;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;font-weight:700;">Order Number</td>
                      <td style="font-size:14px;font-weight:700;color:#4a3728;text-align:right;">#{{ $order->order_number }}</td>
                    </tr>
                    <tr>
                      <td style="font-size:12px;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;font-weight:700;">Date</td>
                      <td style="font-size:14px;color:#2d2d2d;text-align:right;">{{ $order->created_at->format('d M Y, H:i') }}</td>
                    </tr>
                    @if($order->shipping_cost > 0)
                    <tr>
                      <td style="font-size:12px;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;font-weight:700;">Shipping Cost</td>
                      <td style="font-size:14px;color:#2d2d2d;text-align:right;">${{ number_format($order->shipping_cost, 2) }}</td>
                    </tr>
                    @endif
                  </table>
                </td>
              </tr>
            </table>

            <!-- Items -->
            @if($order->items->isNotEmpty())
            <p style="margin:0 0 12px;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#7c5c44;">Order Items</p>
            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;margin-bottom:24px;">
              <thead>
                <tr style="background:#4a3728;">
                  <th style="padding:10px 12px;font-size:12px;color:#fff;text-align:left;font-weight:700;">Product</th>
                  <th style="padding:10px 12px;font-size:12px;color:#fff;text-align:center;font-weight:700;">Qty</th>
                  <th style="padding:10px 12px;font-size:12px;color:#fff;text-align:right;font-weight:700;">Unit Price</th>
                  <th style="padding:10px 12px;font-size:12px;color:#fff;text-align:right;font-weight:700;">Total</th>
                </tr>
              </thead>
              <tbody>
                @foreach($order->items as $item)
                <tr style="border-bottom:1px solid #f0ece4;">
                  <td style="padding:12px;font-size:13px;color:#2d2d2d;">
                    <strong>{{ $item->product_snapshot['name'] ?? $item->product?->name ?? 'Product' }}</strong>
                    @if(!empty($item->product_snapshot['sku']))
                    <br><span style="font-size:11px;color:#9ca3af;">SKU: {{ $item->product_snapshot['sku'] }}</span>
                    @endif
                  </td>
                  <td style="padding:12px;font-size:13px;color:#2d2d2d;text-align:center;">{{ $item->quantity }}</td>
                  <td style="padding:12px;font-size:13px;color:#2d2d2d;text-align:right;">${{ number_format($item->unit_price, 2) }}</td>
                  <td style="padding:12px;font-size:13px;font-weight:700;color:#2d2d2d;text-align:right;">${{ number_format($item->line_total, 2) }}</td>
                </tr>
                @endforeach
                <tr style="background:#faf8f5;">
                  <td colspan="3" style="padding:12px;font-size:13px;text-align:right;color:#555;">Subtotal</td>
                  <td style="padding:12px;font-size:13px;font-weight:700;color:#2d2d2d;text-align:right;">${{ number_format($order->subtotal, 2) }}</td>
                </tr>
                @if($order->shipping_cost > 0)
                <tr style="background:#faf8f5;">
                  <td colspan="3" style="padding:12px;font-size:13px;text-align:right;color:#555;">Shipping</td>
                  <td style="padding:12px;font-size:13px;font-weight:700;color:#2d2d2d;text-align:right;">${{ number_format($order->shipping_cost, 2) }}</td>
                </tr>
                @endif
                <tr style="background:#4a3728;">
                  <td colspan="3" style="padding:12px;font-size:13px;text-align:right;color:#fff;font-weight:700;">Total</td>
                  <td style="padding:12px;font-size:15px;font-weight:700;color:#fff;text-align:right;">${{ number_format($order->grand_total, 2) }}</td>
                </tr>
              </tbody>
            </table>
            @endif

            <p style="margin:0;font-size:14px;color:#555;line-height:1.6;">
              If you have any questions, simply reply to this email.<br><br>
              Best regards,<br>
              <strong style="color:#4a3728;">{{ config('app.name', 'Handicraft Nepal') }}</strong>
            </p>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="background:#4a3728;padding:16px 32px;text-align:center;">
            <p style="margin:0;font-size:12px;color:rgba(255,255,255,0.6);">
              This email was sent regarding order #{{ $order->order_number }}. Please do not reply directly to this automated message.
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>
