<?php

namespace Database\Seeders;

use App\Models\Channel;
use App\Models\User;
use App\Services\OrderManager;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds a fully populated demo account for prospects to explore.
 * Not run by DatabaseSeeder — run explicitly with:
 *   php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'demo@vyaparhub.in'],
            [
                'name' => 'Demo Seller',
                'business_name' => 'Salem Demo Store',
                'mobile' => '9876543210',
                'password' => Hash::make('demo123'),
                'email_verified_at' => now(),
                'plan' => 'pro',
                'plan_expires_at' => now()->addYear(),
            ]
        );

        $user->ensureDefaultChannels();
        $channels = $user->channels()->get()->keyBy('slug');

        $products = $this->seedProducts($user);

        $this->seedOrders($user, $channels, $products);
    }

    /** @return array<string, \App\Models\Product> */
    private function seedProducts(User $user): array
    {
        $catalog = [
            'P1' => ['name' => 'Kanjivaram Silk Saree', 'sku' => 'DEMO-SAR-KAN', 'category' => 'Saree', 'cost_price' => 2200, 'selling_price' => 3499, 'stock' => 12, 'stock_threshold' => 3],
            'P2' => ['name' => 'Cotton Handloom Saree', 'sku' => 'DEMO-SAR-COT', 'category' => 'Saree', 'cost_price' => 650, 'selling_price' => 999, 'stock' => 25, 'stock_threshold' => 5],
            'P3' => ['name' => 'Anarkali Kurti Set', 'sku' => 'DEMO-KUR-ANA', 'category' => 'Kurti', 'cost_price' => 480, 'selling_price' => 799, 'stock' => 20, 'stock_threshold' => 5],
            'P4' => ['name' => 'Salem Pure Cotton Dhoti', 'sku' => 'DEMO-DHO-PUR', 'category' => 'Dhoti', 'cost_price' => 220, 'selling_price' => 399, 'stock' => 40, 'stock_threshold' => 8],
            'P5' => ['name' => 'Silk Angavastram', 'sku' => 'DEMO-ANG-SLK', 'category' => 'Angavastram', 'cost_price' => 180, 'selling_price' => 299, 'stock' => 30, 'stock_threshold' => 6],
            'P6' => ['name' => 'Bridal Silk Lehenga', 'sku' => 'DEMO-LEH-BRD', 'category' => 'Lehenga', 'cost_price' => 4200, 'selling_price' => 6999, 'stock' => 4, 'stock_threshold' => 2],
            'P7' => ['name' => 'Chiffon Dupatta', 'sku' => 'DEMO-DUP-CHF', 'category' => 'Dupatta', 'cost_price' => 150, 'selling_price' => 299, 'stock' => 35, 'stock_threshold' => 8],
            'P8' => ['name' => "Men's Cotton Lungi", 'sku' => 'DEMO-LUN-COT', 'category' => 'Lungi', 'cost_price' => 140, 'selling_price' => 249, 'stock' => 3, 'stock_threshold' => 5],
        ];

        $products = [];

        foreach ($catalog as $key => $data) {
            $initialStock = $data['stock'];
            unset($data['stock']);

            $product = $user->products()->updateOrCreate(
                ['sku' => $data['sku']],
                $data + ['gst_percent' => 5, 'stock' => 0, 'is_active' => true]
            );

            if ($product->stock === 0) {
                $product->recordMovement('add', $initialStock, 'Initial stock');
            }

            $products[$key] = $product;
        }

        return $products;
    }

    /**
     * @param  \Illuminate\Support\Collection<string, Channel>  $channels
     * @param  array<string, \App\Models\Product>  $products
     */
    private function seedOrders($user, $channels, array $products): void
    {
        // Skip re-seeding orders if this demo account already has some (keeps the seeder idempotent-ish).
        if ($user->orders()->exists()) {
            return;
        }

        $orders = new OrderManager;

        $spec = [
            ['meesho', 28, 'delivered', 'Priya R', [['P2', 1], ['P7', 1]]],
            ['whatsapp', 27, 'delivered', 'Karthik S', [['P4', 2]]],
            ['local', 25, 'delivered', 'Walk-in', [['P8', 1]]],
            ['meesho', 24, 'delivered', 'Meena K', [['P1', 1]]],
            ['amazon', 22, 'delivered', 'Divya N', [['P3', 2], ['P7', 1]]],
            ['whatsapp', 20, 'delivered', 'Ramesh V', [['P5', 2]]],
            ['meesho', 19, 'shipped', 'Lakshmi P', [['P2', 1]]],
            ['local', 17, 'delivered', 'Walk-in', [['P4', 1], ['P8', 1]]],
            ['whatsapp', 15, 'delivered', 'Anitha S', [['P6', 1]]],   // → full return (wrong size)
            ['meesho', 13, 'delivered', 'Suresh M', [['P3', 1], ['P5', 1]]],
            ['amazon', 11, 'delivered', 'Deepa R', [['P1', 1]]],       // → RTO (damaged in transit)
            ['local', 9, 'delivered', 'Walk-in', [['P7', 2]]],
            ['whatsapp', 7, 'placed', 'Vijay K', [['P2', 1], ['P4', 1]]],
            ['meesho', 4, 'shipped', 'Kavya S', [['P3', 1]]],
            ['meesho', 2, 'delivered', 'Arun T', [['P5', 1], ['P7', 2]]], // → partial return (1 dupatta)
        ];

        $created = [];

        foreach ($spec as $i => [$channelSlug, $daysAgo, $status, $customer, $lines]) {
            $channel = $channels->get($channelSlug);

            $items = array_map(fn ($line) => [
                'product_id' => $products[$line[0]]->id,
                'quantity' => $line[1],
            ], $lines);

            $order = $orders->create($user, [
                'channel_id' => $channel?->id,
                'order_date' => now()->subDays($daysAgo)->toDateString(),
                'customer_name' => $customer,
                'status' => $status,
            ], $items, $channelSlug === 'whatsapp' ? 'whatsapp' : 'manual');

            $created[$i] = $order;
        }

        // 3 returns: full return, RTO, and a partial return — exercising the Returns & Profit flow.
        $anithaOrder = $created[8]->load('items');
        $orders->recordReturn($anithaOrder, [$anithaOrder->items->first()->id => 1], 50, 'returned');

        $deepaOrder = $created[10]->load('items');
        $orders->recordReturn($deepaOrder, [$deepaOrder->items->first()->id => 1], 60, 'rto');

        $arunOrder = $created[14]->load('items');
        $dupattaItem = $arunOrder->items->firstWhere('product_id', $products['P7']->id);
        $orders->recordReturn($arunOrder, [$dupattaItem->id => 1], 30, 'returned');
    }
}
