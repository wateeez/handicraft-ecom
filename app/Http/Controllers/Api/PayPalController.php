<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\InvoiceService;
use App\Services\OrderService;
use App\Services\ResendNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayPalController extends Controller
{
    public function __construct(
        protected OrderService $orderService,
        protected InvoiceService $invoiceService,
        protected ResendNotificationService $resendNotificationService,
    ) {
    }

    private function getAccessToken(): ?string
    {
        $clientId = config('services.paypal.client_id');
        $secret = config('services.paypal.secret');

        $url = config('services.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com/v1/oauth2/token'
            : 'https://api-m.sandbox.paypal.com/v1/oauth2/token';

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::withBasicAuth($clientId, $secret)
            ->asForm()
            ->post($url, ['grant_type' => 'client_credentials']);

        return $response->successful() ? $response->json('access_token') : null;
    }

    /**
     * Create a PayPal order using the authoritative amount from the pending DB order.
     * Expects JSON body: { "order_id": <int> }
     */
    public function createOrder(Request $request)
    {
        $request->validate(['order_id' => ['required', 'integer']]);

        $order = Order::find($request->integer('order_id'));

        if (!$order || $order->is_paid || $order->type !== Order::TYPE_ORDER) {
            return response()->json(['error' => 'Invalid or already paid order.'], 422);
        }

        $token = $this->getAccessToken();
        if (!$token) {
            return response()->json(['error' => 'Failed to get access token from PayPal.'], 500);
        }

        $url = config('services.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com/v2/checkout/orders'
            : 'https://api-m.sandbox.paypal.com/v2/checkout/orders';

        // Amount is read from the server – never from the browser
        $amount = number_format((float) $order->grand_total, 2, '.', '');

        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => (string) $order->id,
                    'custom_id'    => (string) $order->id,
                    'description'  => 'Order #' . $order->order_number,
                    'amount' => [
                        'currency_code' => 'USD',
                        'value'         => $amount,
                    ],
                ],
            ],
        ];

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::withToken($token)->post($url, $payload);

        if ($response->successful()) {
            return response()->json($response->json());
        }

        Log::error('PayPal createOrder failed', ['body' => $response->json()]);
        return response()->json($response->json(), $response->status());
    }

    /**
     * Capture a PayPal payment and mark the local order as paid.
     * Expects JSON body: { "order_id": <int> }
     */
    public function capturePayment(Request $request, string $paypalOrderId)
    {
        $request->validate(['order_id' => ['required', 'integer']]);

        $order = Order::find($request->integer('order_id'));

        if (!$order || $order->type !== Order::TYPE_ORDER) {
            return response()->json(['error' => 'Order not found.'], 404);
        }

        if ($order->is_paid) {
            return response()->json(['error' => 'Order is already paid.'], 422);
        }

        $token = $this->getAccessToken();
        if (!$token) {
            return response()->json(['error' => 'Failed to get access token from PayPal.'], 500);
        }

        $url = config('services.paypal.mode') === 'live'
            ? "https://api-m.paypal.com/v2/checkout/orders/{$paypalOrderId}/capture"
            : "https://api-m.sandbox.paypal.com/v2/checkout/orders/{$paypalOrderId}/capture";

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::withToken($token)
            ->withHeader('Content-Type', 'application/json')
            ->send('POST', $url);

        if (!$response->successful()) {
            Log::error('PayPal capturePayment failed', [
                'paypal_order_id' => $paypalOrderId,
                'local_order_id'  => $order->id,
                'body'            => $response->json(),
            ]);
            return response()->json($response->json(), $response->status());
        }

        $capture = $response->json();

        // Verify PayPal reports COMPLETED before marking paid
        $captureStatus = $capture['status'] ?? null;
        if ($captureStatus !== 'COMPLETED') {
            Log::warning('PayPal capture not COMPLETED', ['status' => $captureStatus, 'order_id' => $order->id]);
            return response()->json(['error' => 'Payment was not completed by PayPal.'], 422);
        }

        // Mark order as paid and lock financials — wrapped in a transaction with a
        // row-level lock so concurrent duplicate PayPal callbacks cannot double-capture.
        $alreadyPaid = DB::transaction(function () use ($order) {
            // Re-fetch with a write lock; if another request already committed, bail out.
            $fresh = Order::lockForUpdate()->find($order->id);
            if (!$fresh || $fresh->is_paid) {
                return true; // already handled by a concurrent request
            }
            $fresh->is_paid = true;
            $fresh->financial_locked_at = now();
            $fresh->save();
            $order->setRawAttributes($fresh->getAttributes()); // sync in-memory instance
            return false;
        });

        if ($alreadyPaid) {
            // Idempotent: payment was already processed; just return success
            $order->refresh();
            return response()->json([
                'success'      => true,
                'order_number' => $order->order_number,
                'message'      => 'Payment successful. Your order has been placed.',
                'redirect_url' => route('checkout.success', ['orderNumber' => $order->order_number]),
            ]);
        }

        // Transition status → processed (payment received online), and mark the
        // source inquiry (if any) as converted so the admin panel reflects it.
        try {
            $this->orderService->onPaymentConfirmed($order);
        } catch (\Exception $e) {
            Log::warning('PayPal: could not transition order status after payment', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
        }

        // Auto-generate and immediately issue an invoice
        try {
            $this->invoiceService->generateInvoice($order, null, 'issued');
        } catch (\Exception $e) {
            Log::warning('PayPal: could not auto-generate invoice after payment', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
        }

        // Clear the session cart
        session()->forget('cart');

        // Send customer confirmation email after successful payment capture.
        $this->resendNotificationService->sendCustomerPurchaseConfirmation($order->fresh(['client', 'items']));

        return response()->json([
            'success'      => true,
            'order_number' => $order->order_number,
            'message'      => 'Payment successful. Your order has been placed.',
            'redirect_url' => route('checkout.success', ['orderNumber' => $order->order_number]),
        ]);
    }

    /**
     * Cancel a pending (unpaid) order when the user closes the PayPal popup.
     * This is a public endpoint — no auth required — but only cancels orders
     * that are: not paid, of TYPE_ORDER, and not already cancelled.
     */
    public function cancelPending(Request $request, Order $order)
    {
        if ($order->is_paid) {
            return response()->json(['error' => 'Cannot cancel a paid order.'], 422);
        }

        if ($order->type !== Order::TYPE_ORDER) {
            return response()->json(['error' => 'Invalid order.'], 422);
        }

        if ($order->status === Order::STATUS_CANCELLED) {
            return response()->json(['success' => true, 'message' => 'Order already cancelled.']);
        }

        try {
            $this->orderService->changeStatus(
                $order,
                Order::STATUS_CANCELLED,
                null,
                ['notes' => 'Cancelled by buyer — PayPal popup closed before payment.'],
                true
            );
        } catch (\Exception $e) {
            Log::warning('cancelPending: could not cancel order', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Failed to cancel order.'], 500);
        }

        return response()->json(['success' => true]);
    }
}
