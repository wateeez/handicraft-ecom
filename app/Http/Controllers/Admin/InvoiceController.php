<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InvoiceController extends Controller
{
    public function __construct(protected InvoiceService $invoiceService)
    {
    }

    public function store(Request $request, Order $order)
    {
        if (!auth()->user()->hasPermission('manage_invoices')) {
            abort(403);
        }

        if ($order->hasActiveInvoice()) {
            return back()->with('error', 'An active invoice already exists for this order. Void it first.');
        }

        try {
            $invoice = $this->invoiceService->generateInvoice($order, auth()->user());
            return redirect()->route('admin.invoices.show', $invoice)
                ->with('success', "Invoice #{$invoice->invoice_number} generated.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate invoice: ' . $e->getMessage());
        }
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['order.client', 'order.items', 'generatedBy', 'voidedBy', 'auditLogs.user']);
        return view('admin.invoices.show', compact('invoice'));
    }

    public function issue(Invoice $invoice)
    {
        $this->authorize('issue', $invoice);

        try {
            $this->invoiceService->issueInvoice($invoice, auth()->user());
            return back()->with('success', "Invoice #{$invoice->invoice_number} has been issued.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function void(Request $request, Invoice $invoice)
    {
        $this->authorize('void', $invoice);

        $request->validate(['void_reason' => 'required|string|min:5']);

        try {
            $this->invoiceService->voidInvoice($invoice, auth()->user(), $request->void_reason);
            return back()->with('success', "Invoice #{$invoice->invoice_number} has been voided.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function download(Invoice $invoice)
    {
        if (!$invoice->pdf_path || !Storage::disk('local')->exists($invoice->pdf_path)) {
            // Regenerate if missing
            $path = $this->invoiceService->generatePdf($invoice);
            $invoice->pdf_path = $path;
            $invoice->save();
        }

        return Storage::disk('local')->download(
            $invoice->pdf_path,
            "{$invoice->invoice_number}.pdf"
        );
    }
}
