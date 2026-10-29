<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: Arial, sans-serif;">
    @php
        // Build links from APP_URL, not the host of whichever request triggered the email
        $siteUrl = rtrim((string) config('app.url'), '/');
        $orderPageUrl = $siteUrl . route('checkout.success', ['orderNumber' => $order->order_number, 'token' => $order->paymentToken()], false);
        $logoUrl = $siteSettings['footer_logo_url'] ?? $siteSettings['navbar_logo_url'] ?? null;
        if ($logoUrl && !\Illuminate\Support\Str::startsWith($logoUrl, ['http://', 'https://'])) {
            $logoUrl = $siteUrl . '/' . ltrim($logoUrl, '/');
        }
    @endphp

    <table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td align="center" style="padding: 20px 0;">
                
                <!-- Main Container -->
                <table border="0" cellpadding="0" cellspacing="0" width="600" style="background-color: #ffffff; border-radius: 4px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                    
                    <!-- Header with Logo -->
                    <tr>
                        <td align="center" style="background-color: #2d4a34; padding: 40px 20px;">
                            @if($logoUrl)
                                <img src="{{ $logoUrl }}" alt="{{ $siteSettings['site_name'] ?? 'Handicraft Nepal NP' }}" width="200" style="display: block; margin-bottom: 20px; border: 0;">
                            @endif
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: normal; text-transform: uppercase; letter-spacing: 2px;">Order Confirmation</h1>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 40px 40px 20px 40px; color: #333333; line-height: 1.6;">
                            <p style="font-size: 18px; font-weight: bold; margin-bottom: 20px;">Dear {{ $order->client_snapshot['name'] ?? $order->client?->name ?? 'Customer' }},</p>
                            <p style="margin-bottom: 20px;">We have received your payment for order <strong>#{{ $order->order_number }}</strong>. The payment details are now updated on your <strong>Order tab</strong>.</p>
                            
                            <!-- Payment Details Box -->
                            <div style="background-color: #f9f9f9; padding: 20px; border-left: 4px solid #2d4a34; margin-bottom: 20px;">
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="padding: 5px 0;"><strong>Payment Method:</strong></td>
                                        <td>{{ $order->payment_method?->name ?? 'PayPal' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 5px 0;"><strong>Amount:</strong></td>
                                        <td>{{ $order->currency ?? 'US$' }} {{ number_format($order->grand_total ?? 0, 2) }}</td>
                                    </tr>
                                </table>
                            </div>

                            <p style="margin-bottom: 30px;">You can view your updated receipt and order status on your <strong>Order page</strong>.</p>
                        </td>
                    </tr>

                    <!-- Button -->
                    <tr>
                        <td align="center" style="padding-bottom: 40px;">
                            <a href="{{ $orderPageUrl }}" target="_blank" rel="noopener" style="background-color: #2d4a34; color: #ffffff; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">Visit your Order Page</a>
                            <p style="margin: 20px 40px 0; font-size: 12px; color: #777777; line-height: 1.5;">
                                If the button doesn't work, copy this link into your browser:<br>
                                <a href="{{ $orderPageUrl }}" target="_blank" rel="noopener" style="color: #2d4a34; word-break: break-all;">{{ $orderPageUrl }}</a>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer / Signature -->
                    <tr>
                        <td style="padding: 0 40px 40px 40px; border-top: 1px solid #eeeeee; color: #777777; font-size: 14px;">
                            <p style="margin-top: 30px; line-height: 1.4;">
                                Thank you,<br>
                                <span style="color: #2d4a34; font-weight: bold;">Handicraft Nepal NP</span><br>
                                {{ config('app.name', 'Handicraft Nepal NP') }}
                            </p>
                        </td>
                    </tr>

                </table>
                <!-- End Main Container -->

            </td>
        </tr>
    </table>

</body>
</html>