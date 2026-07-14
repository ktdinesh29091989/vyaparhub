<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderManager;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderImportController extends Controller
{
    public function __construct(private OrderManager $orders)
    {
    }

    public function form(Request $request)
    {
        return view('orders.import', [
            'channels' => $request->user()->channels()->orderBy('name')->get(),
        ]);
    }

    /** Download a ready-to-fill sample CSV. */
    public function sample(): StreamedResponse
    {
        $rows = [
            ['order_number', 'order_date', 'sku', 'product_name', 'quantity', 'sale_price', 'customer_name', 'customer_phone', 'status'],
            ['MEESHO-1001', '2026-06-20', 'SAR-01', 'Banarasi Silk Saree', '1', '1499', 'Anita Verma', '9876543210', 'delivered'],
            ['MEESHO-1002', '2026-06-21', 'KUR-05', 'Cotton Kurti - Blue', '2', '699', 'Priya Singh', '9123456780', 'shipped'],
            ['MEESHO-1003', '2026-06-21', 'SAR-01', 'Banarasi Silk Saree', '1', '1499', 'Reena Das', '9988776655', 'rto'],
        ];

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, 'vyaparhub-orders-sample.csv', ['Content-Type' => 'text/csv']);
    }

    public function import(Request $request)
    {
        $request->validate([
            'channel_id' => ['required', Rule::in($request->user()->channels()->pluck('id')->all())],
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $user = $request->user();
        $rows = $this->readCsv($request->file('file')->getRealPath());

        if (empty($rows)) {
            return back()->withErrors(['file' => 'The CSV appears to be empty or has no header row.']);
        }

        // Group rows into orders keyed by order_number.
        $grouped = [];
        foreach ($rows as $row) {
            $number = trim($row['order_number'] ?? '');
            if ($number === '') {
                continue;
            }
            $grouped[$number][] = $row;
        }

        $report = ['orders' => 0, 'items' => 0, 'products_created' => 0, 'skipped' => 0, 'duplicates' => 0];

        // Skip order numbers already imported for this seller (idempotent re-imports).
        $existing = $user->orders()
            ->whereIn('order_number', array_keys($grouped))
            ->pluck('order_number')
            ->flip();

        foreach ($grouped as $number => $lines) {
            if ($existing->has($number)) {
                $report['duplicates']++;
                continue;
            }

            $first = $lines[0];
            $items = [];

            foreach ($lines as $line) {
                $sku = trim($line['sku'] ?? '');
                $name = trim($line['product_name'] ?? '');
                if ($sku === '' && $name === '') {
                    $report['skipped']++;
                    continue;
                }

                $product = $this->resolveProduct($user, $sku, $name, $report);
                if (! $product) {
                    $report['skipped']++;
                    continue;
                }

                $items[] = [
                    'product_id' => $product->id,
                    'quantity' => (int) ($line['quantity'] ?? 1) ?: 1,
                    'sale_price' => $line['sale_price'] ?? null,
                ];
            }

            if (empty($items)) {
                continue;
            }

            $this->orders->create($user, [
                'channel_id' => (int) $request->channel_id,
                'order_number' => $number,
                'order_date' => $this->parseDate($first['order_date'] ?? null),
                'customer_name' => $first['customer_name'] ?? null,
                'customer_phone' => $first['customer_phone'] ?? null,
                'status' => $this->mapStatus($first['status'] ?? null),
            ], $items, 'import');

            $report['orders']++;
            $report['items'] += count($items);
        }

        return back()->with('status', sprintf(
            'Imported %d orders (%d items). %d products auto-created, %d duplicates skipped, %d rows skipped.',
            $report['orders'], $report['items'], $report['products_created'], $report['duplicates'], $report['skipped']
        ));
    }

    /** Parse CSV into an array of associative rows keyed by lowercased header. */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            return [];
        }

        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);
            return [];
        }
        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $header);

        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            if (count(array_filter($data, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue; // blank line
            }
            $rows[] = array_combine($header, array_pad($data, count($header), null));
        }
        fclose($handle);

        return $rows;
    }

    private function resolveProduct($user, string $sku, string $name, array &$report)
    {
        $query = $user->products();

        if ($sku !== '') {
            if ($found = (clone $query)->where('sku', $sku)->first()) {
                return $found;
            }
        }
        if ($name !== '') {
            if ($found = (clone $query)->where('name', $name)->first()) {
                return $found;
            }
        }

        // Auto-create a stub product so the order can be recorded; seller fills cost later.
        $report['products_created']++;

        return $user->products()->create([
            'name' => $name !== '' ? $name : $sku,
            'sku' => $sku !== '' ? $sku : null,
            'category' => 'Imported',
            'cost_price' => 0,
            'selling_price' => 0,
            'gst_percent' => 5,
            'stock' => 0,
            'stock_threshold' => 5,
        ]);
    }

    private function parseDate(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return now()->toDateString();
        }
        try {
            return \Illuminate\Support\Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return now()->toDateString();
        }
    }

    private function mapStatus(?string $value): string
    {
        $v = strtolower(trim((string) $value));

        return match (true) {
            str_contains($v, 'deliver') => 'delivered',
            str_contains($v, 'ship') => 'shipped',
            str_contains($v, 'rto') => 'rto',
            str_contains($v, 'return') => 'returned',
            str_contains($v, 'cancel') => 'cancelled',
            in_array($v, Order::STATUSES, true) => $v,
            default => 'placed',
        };
    }
}
