<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderDeliverySchedule;
use App\Models\Client;
use App\Models\ShippingProvider;
use App\Models\User;
use App\Jobs\MarkOrderDeliveredJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(
        protected NotificationService $notificationService,
        protected ResendNotificationService $resendNotificationService
    ) {
    }

    /**
     * Create a new order or inquiry.
     */
    public function createOrder(array $data, ?User $creator = null): Order
    {
        return DB::transaction(function () use ($data, $creator) {
            $type = $data['type'] ?? Order::TYPE_INQUIRY;

            $shippingProviderId = $data['shipping_provider_id'] ?? ShippingProvider::getDefaultProviderId();

            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'type' => $type,
                'checkout_token' => $type === Order::TYPE_INQUIRY ? Str::random(32) : null,
                'client_id' => $data['client_id'] ?? null,
                'created_by' => $creator?->id,
                'status' => Order::STATUS_UNPROCESSED,
                'order_discount_type' => $data['order_discount_type'] ?? 'none',
                'order_discount_value' => $data['order_discount_value'] ?? 0,
                'shipping_cost' => $data['shipping_cost'] ?? 0,
                'shipping_provider_id' => $shippingProviderId,
                'delivery_period_days' => $data['delivery_period_days'] ?? 14,
                'source_inquiry_id' => $data['source_inquiry_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Save items
            foreach (($data['items'] ?? []) as $itemData) {
                $this->addItem($order, $itemData);
            }

            $this->recalculateTotals($order);

            // Capture client snapshot
            if ($order->client) {
                $order->client_snapshot = $this->buildClientSnapshot($order->client);
                $order->save();
            }

            AuditLogService::logSimple(
                'order_created',
                $order,
                "Order #{$order->order_number} created as " . ucfirst($order->type) . '.',
                $creator
            );

            DB::afterCommit(function () use ($order) {
                $freshOrder = $order->fresh(['client', 'items']);

                if (!$freshOrder) {
                    return;
                }

                if ($freshOrder->type === Order::TYPE_INQUIRY) {
                    $this->resendNotificationService->sendAdminInquiryAlert($freshOrder);
                    $this->resendNotificationService->sendCustomerInquiryConfirmation($freshOrder);
                    return;
                }

                if ($freshOrder->type === Order::TYPE_ORDER) {
                    $this->resendNotificationService->sendAdminOrderAlert($freshOrder);
                    $this->resendNotificationService->sendCustomerOrderCreated($freshOrder);
                }
            });

            return $order;
        });
    }

    /**
     * Update an existing order.
     */
    public function updateOrder(Order $order, array $data, User $editor): Order
    {
        return DB::transaction(function () use ($order, $data, $editor) {
            $isLocked = $order->isFinanciallyLocked();
            $oldValues = $order->toArray();

            // Update non-financial fields always allowed
            $updateFields = [
                'notes',
                'client_id',
                'type',
                'shipping_provider_id',
                'tracking_number',
                'delivery_period_days'
            ];

            // Financial fields only allowed if not locked
            if (!$isLocked) {
                $updateFields = array_merge($updateFields, [
                    'order_discount_type',
                    'order_discount_value',
                    'shipping_cost'
                ]);
            }

            $fillable = array_intersect_key($data, array_flip($updateFields));
            $order->fill($fillable);
            $order->save();

            // Sync items if provided and not locked
            if (!$isLocked && isset($data['items'])) {
                $order->items()->delete();
                foreach ($data['items'] as $itemData) {
                    $this->addItem($order, $itemData);
                }
                $this->recalculateTotals($order);
            }

            // Update client snapshot if client changed
            if (isset($data['client_id']) && $order->client) {
                $order->client_snapshot = $this->buildClientSnapshot($order->client);
                $order->save();
            }

            AuditLogService::log(
                'order_updated',
                $order,
                null,
                $oldValues,
                $order->fresh()->toArray(),
                $editor,
                "Order #{$order->order_number} updated."
            );

            return $order->fresh(['items', 'client']);
        });
    }

    /**
     * Add a line item to an order.
     */
    public function addItem(Order $order, array $itemData): OrderItem
    {
        $item = new OrderItem([
            'order_id' => $order->id,
            'product_id' => $itemData['product_id'] ?? null,
            'quantity' => $itemData['quantity'] ?? 1,
            'unit_price' => $itemData['unit_price'],
            'weight_kg' => $itemData['weight_kg'] ?? 0,
            'item_discount_type' => $itemData['item_discount_type'] ?? 'none',
            'item_discount_value' => $itemData['item_discount_value'] ?? 0,
        ]);

        // Build product snapshot
        if (!empty($itemData['product_id'])) {
            $product = \App\Models\Product::find($itemData['product_id']);
            if ($product) {
                $item->product_snapshot = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku ?? '-',
                    'weight' => $product->weight,
                    'length' => $product->length,
                    'width' => $product->width,
                    'height' => $product->height,
                    'price' => $product->price,
                    'discount_price' => $product->discount_price,
                ];
            }
        } elseif (!empty($itemData['product_name'])) {
            $item->product_snapshot = [
                'name' => $itemData['product_name'],
                'sku' => $itemData['product_sku'] ?? '-',
                'weight' => $itemData['weight_kg'] ?? 0,
                'length' => $itemData['length'] ?? null,
                'width' => $itemData['width'] ?? null,
                'height' => $itemData['height'] ?? null,
                'price' => $itemData['unit_price'],
            ];
        }

        $item->calculateTotals();
        $item->save();

        return $item;
    }

    /**
     * Recalculate all order totals based on items.
     */
    public function recalculateTotals(Order $order): void
    {
        $order->load('items');

        // The subtotal now DIRECTLY reflects item-level discounts
        $subtotal = $order->items->sum('line_total');
        $itemDiscountTotal = $order->items->sum('item_discount_amount');
        $totalWeight = $order->items->sum(fn($i) => $i->weight_kg * $i->quantity);

        // Order-level discount is applied on the already-item-discounted subtotal
        $orderDiscountAmount = 0;
        if ($order->order_discount_type === 'percent') {
            $orderDiscountAmount = round($subtotal * ($order->order_discount_value / 100), 2);
        } elseif ($order->order_discount_type === 'fixed') {
            $orderDiscountAmount = min($order->order_discount_value, $subtotal);
        }

        $grandTotal = max(0, $subtotal - $orderDiscountAmount + $order->shipping_cost);

        $order->subtotal = $subtotal;
        $order->item_discount_total = $itemDiscountTotal;
        $order->order_discount_amount = $orderDiscountAmount;
        $order->total_weight_kg = $totalWeight;
        $order->grand_total = $grandTotal;
        $order->save();
    }

    /**
     * Change order status with validation.
     */
    public function changeStatus(Order $order, string $newStatus, ?User $user, array $context = [], bool $override = false): Order
    {
        $oldStatus = $order->status;

        if (!$order->canTransitionTo($newStatus) && !$override) {
            throw new \Exception("Invalid status transition from '{$oldStatus}' to '{$newStatus}'.");
        }

        $order->status = $newStatus;

        // Handle dispatch specifics
        if ($newStatus === Order::STATUS_DISPATCHED) {
            if (empty($context['tracking_number']) || empty($context['shipping_provider_id'])) {
                throw new \Exception('Shipping provider and tracking number are required for dispatch.');
            }
            $order->tracking_number = $context['tracking_number'];
            $order->shipping_provider_id = $context['shipping_provider_id'];
            $order->dispatched_at = $context['dispatched_at'] ?? now();
            $deliveryDays = $order->delivery_period_days ?: 14;
            $order->expected_delivery_at = Carbon::parse($order->dispatched_at)->addDays($deliveryDays);
            $order->delivered_at = null;
        }

        if ($newStatus === Order::STATUS_CANCELLED) {
            $order->cancelled_at = now();
            $order->cancelled_by = $user?->id;
            $order->cancellation_reason = $context['cancellation_reason'] ?? null;
        }

        $order->save();

        if ($newStatus === Order::STATUS_DISPATCHED) {
            OrderDeliverySchedule::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'expected_delivery_at' => $order->expected_delivery_at,
                    'status' => 'pending',
                    'scheduled_at' => now(),
                    'completed_at' => null,
                    'cancelled_at' => null,
                    'cancellation_reason' => null,
                ]
            );
        }

        if (in_array($newStatus, [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED], true)) {
            $schedule = $order->deliverySchedule;
            if ($schedule && $schedule->status === 'pending') {
                $schedule->update([
                    'status' => $newStatus === Order::STATUS_DELIVERED ? 'completed' : 'cancelled',
                    'completed_at' => $newStatus === Order::STATUS_DELIVERED ? now() : null,
                    'cancelled_at' => $newStatus === Order::STATUS_CANCELLED ? now() : null,
                    'cancellation_reason' => $newStatus === Order::STATUS_CANCELLED
                        ? $order->cancellation_reason
                        : null,
                ]);
            }
        }

        AuditLogService::logStatusChange(
            $order,
            $oldStatus,
            $newStatus,
            $user,
            $override ? '[Override]' : ''
        );

        // Fire notification
        $this->notificationService->notify($order, $newStatus);

        return $order;
    }

    /**
     * Mark order as paid and lock financials.
     */
    public function markAsPaid(Order $order, User $user): Order
    {
        if ($order->is_paid) {
            throw new \Exception('Order is already marked as paid.');
        }

        $order->is_paid = true;
        $order->financial_locked_at = now();
        $order->save();

        AuditLogService::logSimple(
            'marked_paid',
            $order,
            "Order #{$order->order_number} marked as paid. Financials locked.",
            $user
        );

        $this->onPaymentConfirmed($order, $user);

        return $order;
    }

    /**
     * Post-payment bookkeeping shared by every path that confirms a customer paid
     * (online PayPal capture, or an admin manually marking an order as paid):
     * advance the order workflow past "unprocessed", and — if this order was
     * checked out from a quote inquiry — mark that source inquiry as converted so
     * it no longer looks unattended in the admin Inquiries list.
     */
    public function onPaymentConfirmed(Order $order, ?User $user = null): void
    {
        if ($order->canTransitionTo(Order::STATUS_PROCESSED)) {
            $this->changeStatus($order, Order::STATUS_PROCESSED, $user, [], true);
        }

        if ($order->source_inquiry_id) {
            $inquiry = $order->sourceInquiry ?? Order::find($order->source_inquiry_id);
            if ($inquiry && !in_array($inquiry->status, [Order::STATUS_CANCELLED, Order::STATUS_CONVERTED], true)) {
                $this->changeStatus($inquiry, Order::STATUS_CONVERTED, $user, [], true);
            }
        }
    }

    /**
     * Cancel an order.
     */
    public function cancelOrder(Order $order, User $user, string $reason): Order
    {
        if (!$order->isCancellable()) {
            throw new \Exception('This order cannot be cancelled.');
        }

        if ($order->hasIssuedInvoice()) {
            throw new \Exception('Cannot cancel order with an issued invoice. Void the invoice first.');
        }

        return $this->changeStatus($order, Order::STATUS_CANCELLED, $user, [
            'cancellation_reason' => $reason,
        ], true);
    }

    /**
     * Merge multiple orders into a target order.
     */
    public function mergeOrders(array $sourceOrderIds, Order $targetOrder, User $user): Order
    {
        return DB::transaction(function () use ($sourceOrderIds, $targetOrder, $user) {
            // Prevent merging into a financially locked (paid) order — that would mutate
            // locked totals and bypass the financial guard.
            if ($targetOrder->isFinanciallyLocked()) {
                throw new \Exception("Order #{$targetOrder->order_number} is financially locked and cannot be used as a merge target.");
            }

            $sourceOrders = Order::whereIn('id', $sourceOrderIds)
                ->where('id', '!=', $targetOrder->id)
                ->get();

            foreach ($sourceOrders as $source) {
                /** @var Order $source */
                if (!$source->canBeMerged()) {
                    throw new \Exception("Order #{$source->order_number} cannot be merged.");
                }

                // Move items to target
                $source->items()->update(['order_id' => $targetOrder->id]);

                // Mark source as merged
                $source->is_merged = true;
                $source->merged_into_order_id = $targetOrder->id;
                $source->save();
            }

            // Update target's merged_order_ids
            $existingMerged = $targetOrder->merged_order_ids ?? [];
            $targetOrder->merged_order_ids = array_merge($existingMerged, $sourceOrderIds);
            $targetOrder->save();

            $this->recalculateTotals($targetOrder);

            $mergedNumbers = $sourceOrders->pluck('order_number')->implode(', ');
            AuditLogService::logSimple(
                'order_merged',
                $targetOrder,
                "Orders [{$mergedNumbers}] merged into #{$targetOrder->order_number}.",
                $user,
                ['merged_order_ids' => $sourceOrderIds]
            );

            return $targetOrder->fresh(['items', 'client']);
        });
    }

    /**
     * Soft delete an order.
     */
    public function deleteOrder(Order $order, User $user): void
    {
        AuditLogService::logSimple(
            'order_deleted',
            $order,
            "Order #{$order->order_number} soft-deleted.",
            $user
        );
        $order->delete();
    }

    /**
     * Mark a quantity of a line item as returned, tracked independently of the
     * order's normal fulfilment status.
     */
    public function markItemReturned(OrderItem $item, int $quantity, ?string $reason, User $user): OrderItem
    {
        $order = $item->order;

        if (!$order || !$order->isReturnEligible()) {
            throw new \Exception('Items can only be returned once the order has been dispatched or delivered.');
        }

        if ($quantity < 1 || $quantity > $item->returnable_quantity) {
            throw new \Exception("Return quantity must be between 1 and {$item->returnable_quantity}.");
        }

        $item->returned_quantity += $quantity;
        $item->return_status = $item->returned_quantity >= $item->quantity
            ? OrderItem::RETURN_STATUS_RETURNED
            : OrderItem::RETURN_STATUS_PARTIAL;
        $item->return_reason = $reason;
        $item->returned_at = now();
        $item->returned_by = $user->id;
        $item->save();

        AuditLogService::logSimple(
            'item_returned',
            $order,
            "{$quantity} x \"{$item->product_name}\" marked as returned on order #{$order->order_number}."
                . ($reason ? " Reason: {$reason}" : ''),
            $user,
            ['order_item_id' => $item->id, 'returned_quantity' => $item->returned_quantity]
        );

        return $item;
    }

    /**
     * Mark every item on an order as fully returned in one bulk action.
     */
    public function markAllItemsReturned(Order $order, User $user): void
    {
        if (!$order->isReturnEligible()) {
            throw new \Exception('Returns can only be recorded once the order has been dispatched or delivered.');
        }

        foreach ($order->items as $item) {
            $item->returned_quantity = $item->quantity;
            $item->return_status = OrderItem::RETURN_STATUS_RETURNED;
            $item->return_reason = $item->return_reason ?? 'Bulk return update';
            $item->returned_at = $item->returned_at ?? now();
            $item->returned_by = $user->id;
            $item->save();
        }

        AuditLogService::logSimple(
            'order_all_items_returned',
            $order,
            "All items on order #{$order->order_number} were marked as returned.",
            $user
        );
    }

    /**
     * Clear all return records for the order in one bulk action.
     */
    public function clearAllItemReturns(Order $order, User $user): void
    {
        foreach ($order->items as $item) {
            if ($item->return_status === OrderItem::RETURN_STATUS_NONE) {
                continue;
            }

            $item->returned_quantity = 0;
            $item->return_status = OrderItem::RETURN_STATUS_NONE;
            $item->return_reason = null;
            $item->returned_at = null;
            $item->returned_by = null;
            $item->save();
        }

        AuditLogService::logSimple(
            'order_all_item_returns_cleared',
            $order,
            "All return records were cleared for order #{$order->order_number}.",
            $user
        );
    }

    /**
     * Undo a previously recorded return for a line item, resetting it back to
     * "not returned".
     */
    public function undoItemReturn(OrderItem $item, User $user): OrderItem
    {
        if ($item->return_status === OrderItem::RETURN_STATUS_NONE) {
            throw new \Exception('This item has no return recorded.');
        }

        $order = $item->order;
        $previouslyReturned = $item->returned_quantity;

        $item->returned_quantity = 0;
        $item->return_status = OrderItem::RETURN_STATUS_NONE;
        $item->return_reason = null;
        $item->returned_at = null;
        $item->returned_by = null;
        $item->save();

        AuditLogService::logSimple(
            'item_return_undone',
            $order,
            "Return of {$previouslyReturned} x \"{$item->product_name}\" was reversed on order #{$order->order_number}.",
            $user,
            ['order_item_id' => $item->id]
        );

        return $item;
    }

    /**
     * Build client snapshot array from Client model.
     */
    public function buildClientSnapshot(Client $client): array
    {
        return [
            'buyer_id' => $client->buyer_id,
            'name' => $client->name,
            'email' => $client->email,
            'phone' => $client->phone,
            'company' => $client->company_name,
            'address' => $client->address_line,
            'city' => $client->city,
            'state' => $client->state,
            'zip_code' => $client->zip_code,
            'country' => $client->country,
        ];
    }
}
