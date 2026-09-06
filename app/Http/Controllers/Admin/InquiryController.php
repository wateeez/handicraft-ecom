<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Inquiry;
use Illuminate\Support\Str;

class InquiryController extends Controller
{
    public function index()
    {
        $inquiries = Inquiry::with('product')->latest()->paginate(10);
        return view('admin.inquiries.index', compact('inquiries'));
    }

    public function show(Inquiry $inquiry)
    {
        return view('admin.inquiries.show', compact('inquiry'));
    }

    public function reply(Request $request, Inquiry $inquiry)
    {
        $request->validate(['admin_reply' => 'required']);

        $inquiry->update([
            'admin_reply' => $request->admin_reply,
            'status' => 'replied'
        ]);

        // Logic to send email would go here

        return back()->with('success', 'Reply saved successfully.');
    }

    public function sendCheckout(Request $request, Inquiry $inquiry)
    {
        // Generate a unique token for this checkout
        $token = Str::random(40);

        $inquiry->update([
            'checkout_token' => $token,
            'status' => 'checkout_sent'
        ]);

        $link = route('checkout', ['token' => $token]);

        return back()->with('success', 'Checkout link generated: ' . $link);
    }
}

