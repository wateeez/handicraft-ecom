<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::withCount('orders');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('buyer_id', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $clients = $query->latest()->paginate(20)->withQueryString();

        return view('admin.clients.index', compact('clients'));
    }

    public function create()
    {
        return view('admin.clients.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'company_name' => 'nullable|string|max:255',
            'address_line' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $validated['buyer_id'] = Client::generateBuyerId();
        $validated['created_by'] = auth()->id();

        $client = Client::create($validated);

        AuditLogService::logSimple('client_created', null, "Client '{$client->name}' (#{$client->buyer_id}) created.", auth()->user());

        return redirect()->route('admin.clients.show', $client)
            ->with('success', "Client {$client->name} created successfully.");
    }

    public function show(Client $client)
    {
        $client->load(['orders' => fn($q) => $q->latest()->with(['shippingProvider', 'items', 'convertedOrder', 'sourceInquiry'])]);
        return view('admin.clients.show', compact('client'));
    }

    public function edit(Client $client)
    {
        return view('admin.clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'company_name' => 'nullable|string|max:255',
            'address_line' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $client->update($validated);

        AuditLogService::logSimple('client_updated', null, "Client '{$client->name}' (#{$client->buyer_id}) updated.", auth()->user());

        return redirect()->route('admin.clients.show', $client)
            ->with('success', 'Client updated successfully.');
    }
}
