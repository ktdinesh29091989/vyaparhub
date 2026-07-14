<?php

namespace App\Http\Controllers;

use App\Services\ProfitCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChannelController extends Controller
{
    public function index(Request $request, ProfitCalculator $profit)
    {
        $user = $request->user();
        $user->ensureDefaultChannels();

        $channels = $user->channels()->orderBy('name')->get();

        $from = $request->date('from') ?: now()->startOfMonth();
        $to = $request->date('to') ?: now()->endOfMonth();

        $orders = $user->orders()->with(['items', 'channel'])
            ->whereBetween('order_date', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->get();

        $breakdown = [];
        foreach ($channels as $channel) {
            $channelOrders = $orders->where('channel_id', $channel->id);
            $orderCount = $channelOrders->count();
            $agg = $profit->aggregate($channelOrders);

            $returnedCount = $channelOrders->whereIn('status', ['returned', 'partially_returned', 'rto'])->count();

            $qtyByProduct = [];
            foreach ($channelOrders as $order) {
                foreach ($order->items as $item) {
                    $qtyByProduct[$item->product_name] = ($qtyByProduct[$item->product_name] ?? 0) + $item->quantity;
                }
            }
            arsort($qtyByProduct);
            $bestSeller = array_key_first($qtyByProduct);

            $breakdown[] = [
                'name' => $channel->name,
                'badge_color' => $channel->badge_color,
                'chart_color' => match ($channel->slug) {
                    'meesho' => '#ec4899', 'amazon' => '#f97316', 'whatsapp' => '#10b981', 'local' => '#3b82f6',
                    default => '#94a3b8',
                },
                'orders' => $orderCount,
                'revenue' => $agg['revenue'],
                'profit' => $agg['net_profit'],
                'avg_profit' => $orderCount > 0 ? $agg['net_profit'] / $orderCount : 0,
                'return_rate' => $orderCount > 0 ? $returnedCount / $orderCount * 100 : 0,
                'best_seller' => $bestSeller ?? '—',
            ];
        }

        return view('channels.index', [
            'channels' => $channels,
            'breakdown' => $breakdown,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }

    /** Add a custom sales channel beyond the 4 built-in defaults (e.g. Flipkart, JioMart, own website). */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'shipping_charge' => ['nullable', 'numeric', 'min:0'],
        ]);

        $user = $request->user();
        $baseSlug = Str::slug($validated['name']);
        $slug = $baseSlug;
        $suffix = 2;
        while ($user->channels()->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        $user->channels()->create([
            'name' => $validated['name'],
            'slug' => $slug,
            'commission_percent' => $validated['commission_percent'] ?? 0,
            'shipping_charge' => $validated['shipping_charge'] ?? 0,
            'is_active' => true,
        ]);

        return back()->with('status', "\"{$validated['name']}\" channel added.");
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'channels' => ['required', 'array'],
            'channels.*.commission_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'channels.*.shipping_charge' => ['required', 'numeric', 'min:0'],
            'channels.*.is_active' => ['nullable', 'boolean'],
        ]);

        foreach ($validated['channels'] as $id => $attrs) {
            $request->user()->channels()->where('id', $id)->update([
                'commission_percent' => $attrs['commission_percent'],
                'shipping_charge' => $attrs['shipping_charge'],
                'is_active' => (bool) ($attrs['is_active'] ?? false),
            ]);
        }

        return back()->with('status', 'Channel settings saved.');
    }
}
