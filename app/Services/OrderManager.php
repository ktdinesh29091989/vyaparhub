<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderManager
{
    /**
     * Create an order with its line items, decrement stock, and compute totals.
     *
     * @param  array  $data   order-level fields (channel_id, order_date, customer_name, customer_phone, status, shipping_charge, commission_amount, order_number, notes)
     * @param  array  $lines  [['product_id'=>int, 'quantity'=>int, 'sale_price'=>float|null], ...]
     */
    public function create(User $user, array $data, array $lines, string $source = 'manual'): Order
    {
        return DB::transaction(function () use ($user, $data, $lines, $source) {
            $channel = $data['channel_id']
                ? $user->channels()->find($data['channel_id'])
                : null;

            $status = $data['status'] ?? 'placed';
            if (! in_array($status, Order::RESTOCK_STATUSES, true)) {
                $this->assertSufficientStock($user, $lines);
            }

            $order = $user->orders()->create([
                'channel_id' => $channel?->id,
                'order_number' => $data['order_number'] ?? $this->nextOrderNumber($user, $channel),
                'order_date' => $data['order_date'] ?? now()->toDateString(),
                'customer_name' => $data['customer_name'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'status' => $data['status'] ?? 'placed',
                'source' => $source,
                'notes' => $data['notes'] ?? null,
                'subtotal' => 0,
                'shipping_charge' => 0,
                'commission_amount' => 0,
            ]);

            $restocked = in_array($order->status, Order::RESTOCK_STATUSES, true);

            foreach ($lines as $line) {
                $product = $user->products()->find($line['product_id']);
                if (! $product) {
                    continue;
                }

                $qty = max(1, (int) $line['quantity']);
                $salePrice = isset($line['sale_price']) && $line['sale_price'] !== null && $line['sale_price'] !== ''
                    ? (float) $line['sale_price']
                    : (float) $product->selling_price;

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'quantity' => $qty,
                    'sale_price' => $salePrice,
                    'cost_price' => $product->cost_price,
                    'gst_percent' => $product->gst_percent,
                ]);

                // Only consume stock for orders that are actually live sales.
                if (! $restocked) {
                    $product->recordMovement('deduct', -$qty, "Order {$order->order_number}", $order->id);
                }
            }

            $order->load('items');
            $order->recalcSubtotal();

            $order->shipping_charge = $data['shipping_charge'] ?? ($channel->shipping_charge ?? 0);
            $order->commission_amount = isset($data['commission_amount']) && $data['commission_amount'] !== null && $data['commission_amount'] !== ''
                ? (float) $data['commission_amount']
                : round($order->subtotal * ($channel->commission_percent ?? 0) / 100, 2);

            $order->save();

            return $order;
        });
    }

    /**
     * Manual status change (placed/shipped/delivered/cancelled).
     * Cancelling puts the still-on-order units back on the shelf; un-cancelling takes them off again.
     */
    public function changeStatus(Order $order, string $newStatus): void
    {
        $wasCancelled = $order->status === 'cancelled';
        $willCancel = $newStatus === 'cancelled';

        DB::transaction(function () use ($order, $newStatus, $wasCancelled, $willCancel) {
            if (! $wasCancelled && $willCancel) {
                $this->moveOutstandingStock($order, +1, 'adjustment', "Cancelled {$order->order_number}");
            } elseif ($wasCancelled && ! $willCancel) {
                $this->moveOutstandingStock($order, -1, 'deduct', "Re-opened {$order->order_number}");
            }

            $order->update(['status' => $newStatus]);
        });
    }

    /**
     * Record a (possibly partial) return / RTO: restock the returned units, add reverse-shipping cost,
     * and set the order status to returned or partially_returned.
     *
     * @param  array  $returns  [orderItemId => qtyReturned, ...]
     */
    public function recordReturn(Order $order, array $returns, float $returnShipping = 0, string $type = 'returned'): void
    {
        DB::transaction(function () use ($order, $returns, $returnShipping, $type) {
            foreach ($order->items as $item) {
                $qty = (int) ($returns[$item->id] ?? 0);
                $qty = min(max(0, $qty), $item->returnable_quantity);
                if ($qty === 0) {
                    continue;
                }

                $item->increment('returned_quantity', $qty);

                if ($item->product_id && ($product = Product::where('user_id', $order->user_id)->find($item->product_id))) {
                    $product->recordMovement('return', +$qty, "Return {$order->order_number}", $order->id);
                }
            }

            $order->load('items');
            $order->return_shipping = (float) $order->return_shipping + $returnShipping;

            $allReturned = $order->items->every(fn ($i) => $i->returned_quantity >= $i->quantity);
            $anyReturned = $order->items->contains(fn ($i) => $i->returned_quantity > 0);

            $order->status = $allReturned ? ($type === 'rto' ? 'rto' : 'returned')
                : ($anyReturned ? 'partially_returned' : $order->status);

            $order->save();
        });
    }

    /** Restock a still-active order's outstanding units before deleting it. */
    public function deleteOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            if (! in_array($order->status, Order::RESTOCK_STATUSES, true)) {
                $this->moveOutstandingStock($order, +1, 'adjustment', "Order {$order->order_number} deleted");
            }
            $order->delete();
        });
    }

    /** Move the units still counted as sold (quantity − already returned) for each item. */
    private function moveOutstandingStock(Order $order, int $sign, string $type, string $note): void
    {
        foreach ($order->items as $item) {
            $outstanding = max(0, $item->quantity - (int) $item->returned_quantity);
            if ($outstanding > 0 && $item->product_id && ($product = Product::where('user_id', $order->user_id)->find($item->product_id))) {
                $product->recordMovement($type, $sign * $outstanding, $note, $order->id);
            }
        }
    }

    /** Guard against overselling: throws a validation error if any line exceeds available stock. */
    private function assertSufficientStock(User $user, array $lines): void
    {
        $needed = [];
        foreach ($lines as $line) {
            if (empty($line['product_id'])) {
                continue;
            }
            $needed[$line['product_id']] = ($needed[$line['product_id']] ?? 0) + max(1, (int) $line['quantity']);
        }

        if (! $needed) {
            return;
        }

        $products = $user->products()->whereIn('id', array_keys($needed))->get()->keyBy('id');

        foreach ($needed as $productId => $qty) {
            $product = $products->get($productId);
            if ($product && $qty > $product->stock) {
                throw ValidationException::withMessages([
                    'items' => "Not enough stock for \"{$product->name}\" (have {$product->stock}, need {$qty}).",
                ]);
            }
        }
    }

    private function nextOrderNumber(User $user, ?Channel $channel): string
    {
        $prefix = strtoupper(substr($channel->slug ?? 'ord', 0, 2));
        $seq = $user->orders()->count() + 1001;

        return "{$prefix}-{$seq}";
    }
}
