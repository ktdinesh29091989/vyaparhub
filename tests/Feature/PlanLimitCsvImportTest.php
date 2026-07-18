<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PlanLimitCsvImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_user_cannot_exceed_product_limit_via_csv_import(): void
    {
        $user = User::factory()->create(['plan' => 'free']);
        $user->ensureDefaultChannels();

        // Already at 3 of 5 allowed products.
        for ($i = 1; $i <= 3; $i++) {
            $user->products()->create([
                'name' => "Existing Product {$i}",
                'sku' => "EXIST-{$i}",
                'product_type' => 'Other',
                'cost_price' => 100,
                'selling_price' => 200,
                'gst_percent' => 5,
                'stock' => 10,
                'stock_threshold' => 2,
            ]);
        }

        // CSV has 5 new rows, but only 2 slots remain (limit 5).
        $csv = "Product,SKU,Category,Mill/wholesale price,Sell price (Meesho/local),GST %,Stock,Low stock threshold\n";
        for ($i = 1; $i <= 5; $i++) {
            $csv .= "New Product {$i},NEW-{$i},Other,100,200,5,10,2\n";
        }

        $file = UploadedFile::fake()->createWithContent('products.csv', $csv);

        $response = $this->actingAs($user)->post(route('products.import'), ['file' => $file]);

        $response->assertRedirect(route('products.index'));

        // Only 2 more should have been created (3 + 2 = 5, the Free plan cap).
        $this->assertSame(5, $user->products()->count());

        $skipped = collect(session('import_skipped'));
        $this->assertTrue($skipped->contains(fn ($m) => str_contains($m, 'Free plan limit')));
    }

    public function test_free_user_cannot_exceed_order_limit_via_csv_import(): void
    {
        $user = User::factory()->create(['plan' => 'free']);
        $user->ensureDefaultChannels();
        $channel = $user->channels()->first();

        // Already at 13 of 15 allowed orders this month.
        for ($i = 1; $i <= 13; $i++) {
            $user->orders()->create([
                'channel_id' => $channel->id,
                'order_number' => "EXIST-{$i}",
                'order_date' => now(),
                'status' => 'placed',
                'subtotal' => 100,
            ]);
        }

        // A well-stocked existing product, referenced by SKU so no new product needs to be created.
        $user->products()->create([
            'name' => 'Stocked Product',
            'sku' => 'STOCKED-1',
            'product_type' => 'Other',
            'cost_price' => 100,
            'selling_price' => 200,
            'gst_percent' => 5,
            'stock' => 100,
            'stock_threshold' => 2,
        ]);

        // CSV has 5 distinct new orders, but only 2 slots remain (limit 15).
        $csv = "order_number,order_date,sku,product_name,quantity,sale_price,customer_name,customer_phone,status\n";
        for ($i = 1; $i <= 5; $i++) {
            $csv .= "NEW-{$i},2026-07-01,STOCKED-1,Stocked Product,1,199,Test Customer,9999999999,delivered\n";
        }

        $file = UploadedFile::fake()->createWithContent('orders.csv', $csv);

        $response = $this->actingAs($user)->post(route('orders.import'), [
            'channel_id' => $channel->id,
            'file' => $file,
        ]);

        $response->assertRedirect();

        // Only 2 more should have been created (13 + 2 = 15, the Free plan cap).
        $this->assertSame(15, $user->orders()->count());
        $this->assertStringContainsString('Free plan limit', session('status'));
    }

    public function test_order_import_does_not_auto_create_products_past_product_limit(): void
    {
        $user = User::factory()->create(['plan' => 'free']);
        $user->ensureDefaultChannels();
        $channel = $user->channels()->first();

        // Already at 4 of 5 allowed products.
        for ($i = 1; $i <= 4; $i++) {
            $user->products()->create([
                'name' => "Existing Product {$i}",
                'sku' => "EXIST-{$i}",
                'product_type' => 'Other',
                'cost_price' => 100,
                'selling_price' => 200,
                'gst_percent' => 5,
                'stock' => 10,
                'stock_threshold' => 2,
            ]);
        }

        // 3 orders referencing brand-new (unresolvable) SKUs -> would auto-create 3 products,
        // but only 1 slot remains (limit 5). Status 'rto' skips the stock-sufficiency check
        // (restock status), isolating this test from the auto-created product's 0 stock.
        $csv = "order_number,order_date,sku,product_name,quantity,sale_price,customer_name,customer_phone,status\n";
        for ($i = 1; $i <= 3; $i++) {
            $csv .= "ORD-{$i},2026-07-01,UNKNOWN-{$i},Unknown Product {$i},1,199,Test Customer,9999999999,rto\n";
        }

        $file = UploadedFile::fake()->createWithContent('orders.csv', $csv);

        $this->actingAs($user)->post(route('orders.import'), [
            'channel_id' => $channel->id,
            'file' => $file,
        ]);

        // Only 1 more product should have been auto-created (4 + 1 = 5, the Free plan cap).
        $this->assertSame(5, $user->products()->count());
        // All 3 order groups should still process (order limit not hit), but the 2nd and 3rd
        // group's line item is skipped since no product could be resolved for them.
        $this->assertSame(1, $user->orders()->count());
    }

    public function test_one_bad_row_does_not_abort_the_rest_of_the_order_import_batch(): void
    {
        $user = User::factory()->create(['plan' => 'pro', 'plan_expires_at' => now()->addYear()]);
        $user->ensureDefaultChannels();
        $channel = $user->channels()->first();

        $user->products()->create([
            'name' => 'Stocked Product',
            'sku' => 'STOCKED-1',
            'product_type' => 'Other',
            'cost_price' => 100,
            'selling_price' => 200,
            'gst_percent' => 5,
            'stock' => 100,
            'stock_threshold' => 2,
        ]);

        // GOOD-1: existing, well-stocked product -> succeeds.
        // BAD-1: brand-new SKU, auto-created with 0 stock, non-restock status -> insufficient
        //        stock, should be skipped (not abort the batch).
        // GOOD-2: existing, well-stocked product, appears AFTER the bad row -> must still import.
        $csv = "order_number,order_date,sku,product_name,quantity,sale_price,customer_name,customer_phone,status\n"
            ."GOOD-1,2026-07-01,STOCKED-1,Stocked Product,1,199,Customer A,9999999999,delivered\n"
            ."BAD-1,2026-07-01,NEWSKU-1,Brand New Product,1,199,Customer B,9999999999,delivered\n"
            ."GOOD-2,2026-07-01,STOCKED-1,Stocked Product,1,199,Customer C,9999999999,delivered\n";

        $file = UploadedFile::fake()->createWithContent('orders.csv', $csv);

        $response = $this->actingAs($user)->post(route('orders.import'), [
            'channel_id' => $channel->id,
            'file' => $file,
        ]);

        $response->assertRedirect();

        // Both good rows imported despite the bad row sitting between them.
        $this->assertTrue($user->orders()->where('order_number', 'GOOD-1')->exists());
        $this->assertTrue($user->orders()->where('order_number', 'GOOD-2')->exists());
        $this->assertFalse($user->orders()->where('order_number', 'BAD-1')->exists());
        $this->assertSame(2, $user->orders()->count());

        $skipped = collect(session('import_skipped'));
        $this->assertTrue($skipped->contains(fn ($m) => str_contains($m, 'BAD-1') && str_contains($m, 'stock')));
    }
}
