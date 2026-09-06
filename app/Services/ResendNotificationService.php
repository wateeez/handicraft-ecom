<?php

namespace App\Services;

use App\Models\Order;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Log;
use Resend\Client;
use Resend\Transporters\HttpTransporter;
use Resend\ValueObjects\ApiKey;
use Resend\ValueObjects\Transporter\BaseUri;
use Resend\ValueObjects\Transporter\Headers;

class ResendNotificationService
{
    public function sendAdminOrderAlert(Order $order): void
    {
        $this->send(
            [(string) config('services.resend.admin_alert_email')],
            'New Order Alert #' . $order->order_number,
            view('emails.resend.admin-order-alert', ['order' => $order])->render()
        );
    }

    public function sendAdminInquiryAlert(Order $order): void
    {
        $adminEmail = (string) config('services.resend.admin_alert_email');
        \Illuminate\Support\Facades\Log::info('Attempting to send Admin Inquiry Alert', ['admin_email' => $adminEmail, 'order_id' => $order->id]);

        $this->send(
            [$adminEmail],
            'New Inquiry Alert #' . $order->order_number,
            view('emails.resend.admin-inquiry-alert', ['order' => $order])->render()
        );
    }

    public function sendCustomerPurchaseConfirmation(Order $order): void
    {
        $order->loadMissing('client');

        $email = $order->client?->email ?? ($order->client_snapshot['email'] ?? null);

        if (!$email) {
            return;
        }

        $this->send(
            [$email],
            'Order Confirmation #' . $order->order_number,
            view('emails.resend.customer-order-confirmation', ['order' => $order])->render()
        );
    }

    public function sendCustomerInquiryConfirmation(Order $order): void
    {
        $order->loadMissing('client');

        $email = $order->client?->email ?? ($order->client_snapshot['email'] ?? null);

        if (!$email) {
            return;
        }

        $this->send(
            [$email],
            'Inquiry Received #' . $order->order_number,
            view('emails.resend.customer-inquiry-confirmation', ['order' => $order])->render()
        );
    }

    public function sendCustomerOrderCreated(Order $order): void
    {
        $order->loadMissing('client');

        $email = $order->client?->email ?? ($order->client_snapshot['email'] ?? null);

        if (!$email) {
            return;
        }

        $this->send(
            [$email],
            'Order Created #' . $order->order_number,
            view('emails.resend.customer-order-created', ['order' => $order])->render()
        );
    }

    /**
     * Send a single email through Resend.
     */
    protected function send(array $to, string $subject, string $html): void
    {
        $apiKey = (string) config('services.resend.key');
        $from = (string) config('services.resend.from');

        if (empty($apiKey) || empty($from) || empty($to[0])) {
            \Illuminate\Support\Facades\Log::warning('Resend send skipped due to empty config', ['apiKey_set' => !empty($apiKey), 'from' => $from, 'to' => $to]);
            return;
        }

        try {
            $resend = $this->createClient($apiKey);

            $response = $resend->emails->send([
                'from' => $from,
                'to' => $to,
                'subject' => $subject,
                'html' => $html,
            ]);
            
            \Illuminate\Support\Facades\Log::info('Resend email sent successfully', ['to' => $to, 'subject' => $subject, 'response' => json_encode($response)]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Resend email send failed', [
                'subject' => $subject,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Build Resend client with an explicit CA bundle path when available.
     */
    protected function createClient(string $apiKey): Client
    {
        $clientOptions = [];
        $caBundle = $this->resolveCaBundlePath();

        if ($caBundle !== null) {
            $clientOptions['verify'] = $caBundle;
        }

        $apiKeyObj = ApiKey::from($apiKey);
        $baseUri = BaseUri::from(getenv('RESEND_BASE_URL') ?: 'api.resend.com');
        $headers = Headers::withAuthorization($apiKeyObj);

        $httpClient = new GuzzleClient($clientOptions);
        $transporter = new HttpTransporter($httpClient, $baseUri, $headers);

        return new Client($transporter);
    }

    /**
     * Resolve a CA bundle path that exists on disk.
     */
    protected function resolveCaBundlePath(): ?string
    {
        $candidates = [
            ini_get('curl.cainfo') ?: null,
            ini_get('openssl.cafile') ?: null,
            env('CURL_CA_BUNDLE'),
            env('SSL_CERT_FILE'),
            base_path('cacert.pem'),
            'C:/laragon/etc/ssl/cacert.pem',
            'C:\\laragon\\etc\\ssl\\cacert.pem',
        ];

        foreach ($candidates as $candidate) {
            if (!is_string($candidate) || !is_file($candidate)) {
                continue;
            }

            return $candidate;
        }

        return null;
    }
}
