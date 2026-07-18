<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProductCsvPriceValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_range_is_rejected_with_a_clear_message_not_averaged(): void
    {
        $user = User::factory()->create();

        $csv = "Product,SKU,Cost Price,Selling Price\n"
            ."Range Priced Item,RANGE-1,400-480,799-950\n";

        $file = UploadedFile::fake()->createWithContent('range.csv', $csv);
        $response = $this->actingAs($user)->post(route('products.import'), ['file' => $file]);

        // Must NOT create a product with an averaged price (e.g. cost=440).
        $this->assertNull(Product::where('sku', 'RANGE-1')->first());

        $skipped = collect($response->getSession()->get('import_skipped'));
        $this->assertTrue($skipped->contains(
            fn ($m) => str_contains($m, "Price ranges aren't supported") && str_contains($m, '400-480')
        ));
    }

    public function test_a_plain_single_number_price_still_imports_correctly(): void
    {
        $user = User::factory()->create();

        $csv = "Product,SKU,Cost Price,Selling Price\n"
            ."Plain Priced Item,PLAIN-1,440,875\n";

        $file = UploadedFile::fake()->createWithContent('plain.csv', $csv);
        $this->actingAs($user)->post(route('products.import'), ['file' => $file]);

        $product = Product::where('sku', 'PLAIN-1')->first();
        $this->assertNotNull($product);
        $this->assertEquals(440, $product->cost_price);
        $this->assertEquals(875, $product->selling_price);
    }

    public function test_missing_price_gets_a_distinct_message_from_a_range(): void
    {
        $user = User::factory()->create();

        $csv = "Product,SKU,Cost Price,Selling Price\n"
            ."No Cost Price,NOCOST-1,,875\n";

        $file = UploadedFile::fake()->createWithContent('nocost.csv', $csv);
        $response = $this->actingAs($user)->post(route('products.import'), ['file' => $file]);

        $this->assertNull(Product::where('sku', 'NOCOST-1')->first());

        $skipped = collect($response->getSession()->get('import_skipped'));
        $this->assertTrue($skipped->contains(
            fn ($m) => stripos($m, 'cost price') !== false && stripos($m, 'missing') !== false && ! str_contains($m, 'ranges')
        ));
    }
}
