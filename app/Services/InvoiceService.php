<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    /**
     * Generate a new invoice for an order.
     */
    public function generateInvoice(Order $order, ?User $user, string $status = 'draft'): Invoice
    {
        // Wrap in a transaction so that generateInvoiceNumber()'s lockForUpdate()
        // actually serialises concurrent requests and prevents duplicate numbers.
        $invoice = DB::transaction(function () use ($order, $user, $status) {
            return Invoice::create([
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'order_id' => $order->id,
                'generated_by' => $user?->id,
                'status' => $status,
                'client_snapshot' => $order->client_snapshot ?? ($order->client ? app(OrderService::class)->buildClientSnapshot($order->client) : null),
                'financial_snapshot' => $this->buildFinancialSnapshot($order),
                'issued_at' => $status === 'issued' ? now() : null,
            ]);
        });

        // Generate PDF immediately
        $pdfPath = $this->generatePdf($invoice);
        $invoice->pdf_path = $pdfPath;
        $invoice->save();

        AuditLogService::log(
            'invoice_generated',
            $order,
            $invoice,
            [],
            ['invoice_number' => $invoice->invoice_number, 'status' => $status],
            $user,
            "Invoice #{$invoice->invoice_number} generated ({$status})."
        );

        return $invoice;
    }

    /**
     * Issue a draft invoice.
     */
    public function issueInvoice(Invoice $invoice, User $user): Invoice
    {
        if ($invoice->status !== 'draft') {
            throw new \Exception('Only draft invoices can be issued.');
        }

        $invoice->status = 'issued';
        $invoice->issued_at = now();
        $invoice->save();

        // Regenerate PDF with issued status
        $pdfPath = $this->generatePdf($invoice);
        $invoice->pdf_path = $pdfPath;
        $invoice->save();

        AuditLogService::log(
            'invoice_issued',
            $invoice->order,
            $invoice,
            ['status' => 'draft'],
            ['status' => 'issued'],
            $user,
            "Invoice #{$invoice->invoice_number} issued."
        );

        return $invoice;
    }

    /**
     * Void an invoice.
     */
    public function voidInvoice(Invoice $invoice, User $user, string $reason): Invoice
    {
        if (!$invoice->isVoidable()) {
            throw new \Exception('This invoice cannot be voided.');
        }

        $invoice->status = 'voided';
        $invoice->voided_at = now();
        $invoice->voided_by = $user->id;
        $invoice->void_reason = $reason;
        $invoice->save();

        AuditLogService::log(
            'invoice_voided',
            $invoice->order,
            $invoice,
            ['status' => 'issued'],
            ['status' => 'voided', 'reason' => $reason],
            $user,
            "Invoice #{$invoice->invoice_number} voided. Reason: {$reason}"
        );

        return $invoice;
    }

    /**
     * Generate PDF for an invoice and return the storage path.
     */
    public function generatePdf(Invoice $invoice): string
    {
        $invoice->load(['order.client', 'order.items', 'generatedBy']);

        $pdf = Pdf::loadView('admin.invoices.pdf', ['invoice' => $invoice])
            ->setPaper('a4');

        $filename = "invoices/{$invoice->invoice_number}.pdf";
        Storage::disk('local')->put($filename, $pdf->output());

        return $filename;
    }

    /**
     * Build financial snapshot from order.
     */
    public function buildFinancialSnapshot(Order $order): array
    {
        $order->load('items');

        $items = $order->items->map(function ($item) {
            $dims = null;
            if (isset($item->product_snapshot['length']) && $item->product_snapshot['length']) {
                $dims = "{$item->product_snapshot['length']}x{$item->product_snapshot['width']}x{$item->product_snapshot['height']}cm";
            }
            return [
                'product_name' => $item->product_name,
                'product_sku' => $item->product_sku,

                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'weight_kg' => $item->weight_kg,
                'dimensions' => $dims,
                'item_discount_type' => $item->item_discount_type,
                'item_discount_value' => $item->item_discount_value,
                'item_discount_amount' => $item->item_discount_amount,
                'line_total' => $item->line_total,
            ];
        })->toArray();

        return [
            'items' => $items,
            'subtotal' => $order->subtotal,
            'item_discount_total' => $order->item_discount_total,
            'order_discount_type' => $order->order_discount_type,
            'order_discount_value' => $order->order_discount_value,
            'order_discount_amount' => $order->order_discount_amount,
            'shipping_cost' => $order->shipping_cost,
            'total_weight_kg' => $order->total_weight_kg,
            'grand_total' => $order->grand_total,
            'currency' => 'USD',
        ];
    }
}
