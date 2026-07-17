<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\OrderManager;
use App\Services\ProfitCalculator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(private OrderManager $orders)
    {
    }

    /** Status pill groups shown on the orders list filter bar (mapped onto the richer STATUSES set). */
    public const STATUS_PILLS = [
        'placed' => ['placed'],
        'shipped' => ['shipped'],
        'delivered' => ['delivered'],
        'returns' => ['rto', 'returned', 'partially_returned'],
        'cancelled' => ['cancelled'],
    ];

    public function index(Request $request)
    {
        $user = $request->user();

        $query = $this->filteredOrdersQuery($request, $user)
            ->with(['channel', 'items'])->withCount('items')
            ->latest('order_date')->latest('id');

        $orders = $query->paginate(15)->withQueryString();

        $summary = [
            'total' => $user->orders()->count(),
            'open' => $user->orders()->whereIn('status', ['placed', 'shipped'])->count(),
            'delivered' => $user->orders()->where('status', 'delivered')->count(),
            'returns' => $user->orders()->whereIn('status', ['rto', 'returned', 'partially_returned'])->count(),
        ];

        // Totals across the FULL filtered set (not just the current page).
        $profit = app(ProfitCalculator::class);
        $filteredOrders = $this->filteredOrdersQuery($request, $user)->with('items')->get();

        $category = $request->string('category')->toString();
        $category = array_key_exists($category, Product::CATEGORIES) ? $category : null;

        if ($category) {
            // Restrict the totals to just this category's line items, so a mixed-cart order
            // (e.g. one Textile + one Cosmetics item) doesn't overstate the filtered total.
            $categoryOf = $user->products()->pluck('category', 'id')->all();
            $sums = $profit->byCategory($filteredOrders, $categoryOf)[$category]
                ?? ['revenue' => 0.0, 'net_profit' => 0.0, 'units_sold' => 0];
            $filtered = ['count' => $filteredOrders->count(), ...$sums];
        } else {
            $filtered = [
                'count' => $filteredOrders->count(),
                ...$profit->aggregate($filteredOrders),
            ];
        }

        return view('orders.index', [
            'orders' => $orders,
            'summary' => $summary,
            'filtered' => $filtered,
            'channels' => $user->channels()->orderBy('name')->get(),
            'categories' => Product::CATEGORIES,
            'statuses' => Order::STATUSES,
            'statusPills' => self::STATUS_PILLS,
            'manualStatuses' => Order::MANUAL_STATUSES,
        ]);
    }

    /** Shared filter logic for both the paginated list and the filtered-totals summary row. */
    private function filteredOrdersQuery(Request $request, $user)
    {
        $query = $user->orders();

        if ($pill = $request->string('status')->toString()) {
            if (isset(self::STATUS_PILLS[$pill])) {
                $query->whereIn('status', self::STATUS_PILLS[$pill]);
            }
        }
        if ($channelId = $request->integer('channel')) {
            $query->where('channel_id', $channelId);
        }
        if ($category = $request->string('category')->toString()) {
            if (array_key_exists($category, Product::CATEGORIES)) {
                $query->whereHas('items.product', fn ($q) => $q->where('category', $category));
            }
        }
        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhereHas('items', fn ($i) => $i->where('product_name', 'like', "%{$search}%"));
            });
        }
        if ($from = $request->date('from')) {
            $query->whereDate('order_date', '>=', $from);
        }
        if ($to = $request->date('to')) {
            $query->whereDate('order_date', '<=', $to);
        }

        return $query;
    }

    public function create(Request $request)
    {
        return view('orders.create', $this->formData($request));
    }

    /** Mobile-first quick entry for orders taken over WhatsApp. */
    public function quick(Request $request)
    {
        return view('orders.quick', $this->formData($request));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'channel_id' => ['nullable', Rule::in($request->user()->channels()->pluck('id')->all())],
            'order_number' => ['nullable', 'string', 'max:100'],
            'order_date' => ['required', 'date'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'status' => ['required', Rule::in(Order::STATUSES)],
            'shipping_charge' => ['nullable', 'numeric', 'min:0'],
            'commission_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', Rule::in($request->user()->products()->pluck('id')->all())],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.sale_price' => ['nullable', 'numeric', 'min:0'],
        ], [
            'items.required' => 'Add at least one product to the order.',
            'items.*.product_id.required' => 'Choose a product for each line.',
        ]);

        $order = $this->orders->create(
            $request->user(),
            $data,
            $data['items'],
            $request->input('source', 'manual'),
        );

        return redirect()->route('orders.show', $order)->with('status', "Order {$order->order_number} created.");
    }

    public function show(Request $request, Order $order, ProfitCalculator $profit)
    {
        $this->authorizeOrder($request, $order);
        $order->load(['items', 'channel']);

        return view('orders.show', [
            'order' => $order,
            'breakdown' => $profit->forOrder($order),
            'manualStatuses' => Order::MANUAL_STATUSES,
            'canReturn' => in_array($order->status, Order::RETURNABLE_STATUSES, true)
                && $order->items->contains(fn ($i) => $i->returnable_quantity > 0),
            'stockHistory' => $order->stockHistory()->with('product')->get(),
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);

        if ($order->is_locked) {
            return back()->withErrors(['status' => 'This order is locked and its status can no longer be changed.']);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(Order::MANUAL_STATUSES)],
        ]);

        $order->load('items');
        $this->orders->changeStatus($order, $validated['status']);

        return back()->with('status', "Status updated to {$validated['status']}.");
    }

    /** Record a (possibly partial) return / RTO. */
    public function recordReturn(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);
        $order->load('items');

        $validated = $request->validate([
            'type' => ['required', 'in:returned,rto'],
            'return_shipping' => ['nullable', 'numeric', 'min:0'],
            'returns' => ['required', 'array'],
            'returns.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $returns = array_filter(array_map('intval', $validated['returns']));
        if (empty($returns)) {
            return back()->withErrors(['returns' => 'Enter at least one returned quantity.']);
        }

        // Guard each quantity against what's actually returnable.
        foreach ($order->items as $item) {
            if (isset($returns[$item->id]) && $returns[$item->id] > $item->returnable_quantity) {
                return back()->withErrors(['returns' => "Return qty for {$item->product_name} exceeds what's left."]);
            }
        }

        $this->orders->recordReturn($order, $returns, (float) ($validated['return_shipping'] ?? 0), $validated['type']);

        return back()->with('status', 'Return recorded and stock restored.');
    }

    public function destroy(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);
        $order->load('items');
        $this->orders->deleteOrder($order);

        return redirect()->route('orders.index')->with('status', 'Order deleted and stock restored.');
    }

    private function formData(Request $request): array
    {
        $user = $request->user();

        return [
            'channels' => $user->channels()->where('is_active', true)->orderBy('name')->get(),
            'products' => $user->products()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'selling_price', 'cost_price', 'stock']),
            'statuses' => Order::STATUSES,
        ];
    }

    private function authorizeOrder(Request $request, Order $order): void
    {
        abort_unless($order->user_id === $request->user()->id, 403);
    }
}
