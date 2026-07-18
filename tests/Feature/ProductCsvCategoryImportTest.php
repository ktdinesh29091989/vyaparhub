<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProductCsvCategoryImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_style_csv_with_only_category_column_maps_it_to_product_type(): void
    {
        $user = User::factory()->create();

        $csv = "Product,SKU,Category,Cost Price,Selling Price\n"
            ."Cotton Kurti,OLD-1,Kurti,200,400\n";

        $file = UploadedFile::fake()->createWithContent('old.csv', $csv);
        $this->actingAs($user)->post(route('products.import'), ['file' => $file]);

        $product = Product::where('sku', 'OLD-1')->first();
        $this->assertNotNull($product);
        $this->assertSame('textile', $product->category);
        $this->assertSame('Kurti', $product->product_type);
    }

    public function test_new_style_csv_with_mixed_categories_maps_each_row_correctly(): void
    {
        $user = User::factory()->create();

        $csv = "Product,SKU,Category,Product Type,Cost Price,Selling Price\n"
            ."Banarasi Saree,NEW-1,Textile,Saree,400,800\n"
            ."Casual Sandals,NEW-2,Footwear,,150,350\n"
            ."Herbal Cream,NEW-3,Cosmetics,,60,150\n";

        $file = UploadedFile::fake()->createWithContent('new.csv', $csv);
        $this->actingAs($user)->post(route('products.import'), ['file' => $file]);

        $textile = Product::where('sku', 'NEW-1')->first();
        $this->assertSame('textile', $textile->category);
        $this->assertSame('Saree', $textile->product_type);

        $footwear = Product::where('sku', 'NEW-2')->first();
        $this->assertSame('footwear', $footwear->category);
        $this->assertNull($footwear->product_type);

        $cosmetics = Product::where('sku', 'NEW-3')->first();
        $this->assertSame('cosmetics', $cosmetics->category);
        $this->assertNull($cosmetics->product_type);
    }

    public function test_product_type_is_ignored_for_non_textile_rows_even_if_present(): void
    {
        $user = User::factory()->create();

        // A Footwear row with a stray "Saree" in Product Type should not leak into product_type.
        $csv = "Product,SKU,Category,Product Type,Cost Price,Selling Price\n"
            ."Mismatched Row,MIX-1,Footwear,Saree,150,350\n";

        $file = UploadedFile::fake()->createWithContent('mixed.csv', $csv);
        $this->actingAs($user)->post(route('products.import'), ['file' => $file]);

        $product = Product::where('sku', 'MIX-1')->first();
        $this->assertSame('footwear', $product->category);
        $this->assertNull($product->product_type);
    }

    public function test_unrecognized_category_value_skips_the_row_instead_of_guessing(): void
    {
        $user = User::factory()->create();

        // A "Product Type" column must be present too, otherwise the lone "Category" column is
        // (correctly) treated as an old-style CSV and redirected to product_type instead —
        // see test_old_style_csv_with_only_category_column_maps_it_to_product_type().
        $csv = "Product,SKU,Category,Product Type,Cost Price,Selling Price\n"
            ."Bad Category Row,BAD-1,Shoes,,150,350\n";

        $file = UploadedFile::fake()->createWithContent('bad.csv', $csv);
        $response = $this->actingAs($user)->post(route('products.import'), ['file' => $file]);

        $this->assertNull(Product::where('sku', 'BAD-1')->first());
        $skipped = collect($response->getSession()->get('import_skipped'));
        $this->assertTrue($skipped->contains(fn ($m) => str_contains($m, 'unrecognized category')));
    }

    public function test_missing_category_column_entirely_defaults_to_textile(): void
    {
        $user = User::factory()->create();

        $csv = "Product,SKU,Cost Price,Selling Price\n"
            ."No Category Column,NOCAT-1,150,350\n";

        $file = UploadedFile::fake()->createWithContent('nocat.csv', $csv);
        $this->actingAs($user)->post(route('products.import'), ['file' => $file]);

        $product = Product::where('sku', 'NOCAT-1')->first();
        $this->assertNotNull($product);
        $this->assertSame('textile', $product->category);
    }
}
