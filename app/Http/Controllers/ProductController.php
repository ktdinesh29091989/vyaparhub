<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->user()->products()->latest();

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->string('filter')->toString() === 'low') {
            $query->whereColumn('stock', '<=', 'stock_threshold');
        }

        $products = $query->paginate(12)->withQueryString();

        return view('products.index', compact('products'));
    }

    public function create()
    {
        return view('products.create', [
            'categories' => Product::CATEGORIES,
            'productTypes' => Product::PRODUCT_TYPES,
            'categoryFields' => Product::CATEGORY_FIELDS,
        ]);
    }

    /** Column header aliases accepted in an uploaded CSV, mapped to product fields. */
    private const CSV_COLUMN_ALIASES = [
        'name' => ['product', 'product name', 'name'],
        'sku' => ['sku', 'code', 'sku / code'],
        'category' => ['category', 'business category', 'vertical'],
        'product_type' => ['product type', 'garment type', 'type'],
        'source_location' => ['where to source in salem', 'source location', 'source', 'where to source'],
        'cost_price' => ['mill/wholesale price', 'mill / wholesale price', 'cost price', 'cost price (rs)', 'cost'],
        'selling_price' => ['sell price (meesho/local)', 'sell price', 'selling price', 'selling price (rs)'],
        'gst_percent' => ['gst %', 'gst percent', 'gst'],
        'stock' => ['stock', 'opening stock'],
        'stock_threshold' => ['low stock threshold', 'low-stock alert at', 'reorder level', 'stock threshold'],
    ];

    /** Downloads a sample CSV (Salem sourcing sheet) that can be re-uploaded via the importer. */
    public function downloadSampleCsv()
    {
        $rows = [
            ['Product', 'SKU', 'Category', 'Product Type', 'Where to source in Salem', 'Mill/wholesale price', 'Sell price (Meesho/local)', 'GST %', 'Stock', 'Low stock threshold'],
            ['Elampillai cotton silk saree (plain/emboss - everyday wear)', 'ELM-COT-01', 'Textile', 'Saree', 'Elampillai weavers direct', 440, 875, 5, 20, 5],
            ['Elampillai real zari silk saree (festival / wedding)', 'ELM-ZARI-01', 'Textile', 'Saree', 'Kirupa Textile, Elampillai', 600, 1350, 5, 10, 3],
            ['Salem Venpattu silk saree (GI certified - premium)', 'SLM-VEN-01', 'Textile', 'Saree', 'Ammapet Weavers Co-op', 1000, 2300, 5, 8, 2],
            ['Cotton kurti (readymade) - daily wear', 'KUR-COT-01', 'Textile', 'Kurti', 'Sri Lakshminarayana Tex, Salem', 230, 549, 5, 30, 10],
            ["Men's cotton shirt - casual / formal", 'SHIRT-CAS-01', 'Textile', 'Other', 'Shevapet Market, Salem', 185, 450, 5, 25, 8],
            ["Men's cotton T-shirt - plain + printed", 'TSHIRT-01', 'Textile', 'Other', 'Tirupur agents (60km from Salem)', 105, 275, 5, 40, 10],
            ["Men's dhoti (cotton) - Salem specialty", 'DHOTI-01', 'Textile', 'Other', 'Paramparaa / local mill', 150, 375, 5, 15, 5],
            ['Kids school uniform - Jan-June demand spike', 'UNIFORM-01', 'Textile', 'Other', 'Shevapet / local garment unit', 275, 600, 5, 20, 5],
            ['Dress material (churidar) - unstitched', 'CHURI-01', 'Textile', 'Suit', 'Shevapet textile market', 275, 650, 5, 18, 5],
            ['Blouse material / fabric roll - sell by metre', 'BLOUSE-01', 'Textile', 'Other', 'Shevapet fabric market', 90, 215, 5, 50, 15],
            ['Ladies casual sandals - size 36-40 mix', 'SNDL-01', 'Footwear', '', 'Salem footwear wholesale market', 185, 425, 5, 25, 8],
            ['Herbal face cream - 50g jar', 'CREAM-01', 'Cosmetics', '', 'Local cosmetics distributor, Salem', 75, 175, 18, 40, 10],
        ];

        return $this->streamCsv('salem-products-sample.csv', $rows);
    }

    /** Downloads a blank template with just the headers the importer understands. */
    public function downloadTemplateCsv()
    {
        $rows = [
            ['Product', 'SKU', 'Category', 'Product Type', 'Where to source in Salem', 'Mill/wholesale price', 'Sell price (Meesho/local)', 'GST %', 'Stock', 'Low stock threshold'],
        ];

        return $this->streamCsv('product-import-template.csv', $rows);
    }

    private function streamCsv(string $filename, array $rows)
    {
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** Bulk-create products from an uploaded CSV. */
    public function importCsv(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle);

        if (! $header) {
            fclose($handle);
            return back()->withErrors(['file' => 'The CSV file appears to be empty.']);
        }

        // Strip UTF-8 BOM from the first header cell, if present.
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        $fieldForColumn = $this->mapHeaderToFields($header);

        $isPro = $request->user()->isPro();
        $remaining = $isPro ? null : max(0, User::FREE_PRODUCT_LIMIT - $request->user()->products()->count());

        $imported = 0;
        $skipped = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue; // blank line
            }

            if (! $isPro && $remaining <= 0) {
                $skipped[] = "Row {$rowNumber}: Free plan limit of ".User::FREE_PRODUCT_LIMIT." products reached. Upgrade to Pro to import more.";
                continue;
            }

            $data = [];
            foreach ($row as $i => $value) {
                if (isset($fieldForColumn[$i])) {
                    $data[$fieldForColumn[$i]] = trim((string) $value);
                }
            }

            if (empty($data['name'])) {
                $skipped[] = "Row {$rowNumber}: missing product name.";
                continue;
            }

            $costPrice = $this->parseMoney($data['cost_price'] ?? null);
            $sellingPrice = $this->parseMoney($data['selling_price'] ?? null);

            if ($costPrice === null || $sellingPrice === null) {
                $problems = [];
                if ($costPrice === null) {
                    $problems[] = $this->priceProblemMessage('cost price', $data['cost_price'] ?? null);
                }
                if ($sellingPrice === null) {
                    $problems[] = $this->priceProblemMessage('selling price', $data['selling_price'] ?? null);
                }
                $skipped[] = "Row {$rowNumber} ({$data['name']}): ".implode(' ', $problems);
                continue;
            }

            $categorySlug = $this->resolveCategorySlug($data['category'] ?? null);
            if ($categorySlug === null) {
                $skipped[] = "Row {$rowNumber} ({$data['name']}): unrecognized category \"{$data['category']}\". Use one of: ".implode(', ', Product::CATEGORIES).'.';
                continue;
            }

            // Product type (garment sub-type) only applies to the Textile category — matches
            // the same rule enforced on the manual add/edit form (validateProduct()).
            $productType = $categorySlug === 'textile' ? ($data['product_type'] ?? null ?: null) : null;

            $request->user()->products()->create([
                'name' => $data['name'],
                'sku' => $data['sku'] ?? null ?: null,
                'category' => $categorySlug,
                'product_type' => $productType,
                'source_location' => $data['source_location'] ?? null ?: null,
                'cost_price' => $costPrice,
                'selling_price' => $sellingPrice,
                'gst_percent' => is_numeric($data['gst_percent'] ?? null) ? (float) $data['gst_percent'] : 5,
                'stock' => is_numeric($data['stock'] ?? null) ? (int) $data['stock'] : 0,
                'stock_threshold' => is_numeric($data['stock_threshold'] ?? null) ? (int) $data['stock_threshold'] : 5,
            ]);

            $imported++;
            if (! $isPro) {
                $remaining--;
            }
        }

        fclose($handle);

        $status = "Imported {$imported} product" . ($imported === 1 ? '' : 's') . '.';
        if ($skipped) {
            $status .= ' ' . count($skipped) . ' row' . (count($skipped) === 1 ? '' : 's') . ' skipped.';
        }

        return redirect()->route('products.index')
            ->with('status', $status)
            ->with('import_skipped', $skipped);
    }

    /** @return array<int, string> map of CSV column index => product field name */
    private function mapHeaderToFields(array $header): array
    {
        $map = [];

        foreach ($header as $i => $columnName) {
            $normalized = strtolower(trim(preg_replace('/\s+/', ' ', $columnName)));

            foreach (self::CSV_COLUMN_ALIASES as $field => $aliases) {
                if (in_array($normalized, $aliases, true)) {
                    $map[$i] = $field;
                    break;
                }
            }
        }

        // Backward compatibility: older CSVs (and the old sample/template) only had a single
        // "Category" column meaning garment type (Saree/Kurti/...). If no "Product Type" column
        // is present, treat that lone "Category" column as product_type instead of the new
        // business-vertical category field, so pre-multi-category CSVs keep importing correctly.
        if (! in_array('product_type', $map, true) && in_array('category', $map, true)) {
            $map[array_search('category', $map, true)] = 'product_type';
        }

        return $map;
    }

    /**
     * Resolves a CSV "Category" cell (accepts either the slug or the display label,
     * case-insensitively) to a valid Product::CATEGORIES slug. Returns null if the cell has a
     * value but it doesn't match anything, so the caller can skip the row instead of silently
     * mis-categorizing it. A blank cell (column missing entirely, or old-style CSV) defaults to
     * 'textile' — matches the products.category column default and keeps old CSVs working.
     */
    private function resolveCategorySlug(?string $value): ?string
    {
        $value = strtolower(trim((string) $value));

        if ($value === '') {
            return 'textile';
        }

        if (array_key_exists($value, Product::CATEGORIES)) {
            return $value;
        }

        foreach (Product::CATEGORIES as $slug => $label) {
            if (strtolower($label) === $value) {
                return $slug;
            }
        }

        return null;
    }

    /**
     * Parses "400" or "₹400" (with optional thousands separators/spaces, or a trailing unit
     * like "/metre") into a single float. Deliberately rejects ranges like "400-480" — a single
     * exact number is required for cost/selling price so profit calculations aren't built on an
     * averaged guess. Use priceProblemMessage() to explain a null result to the user.
     */
    private function parseMoney(?string $value): ?float
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $clean = str_replace(['₹', ',', ' '], '', $value);
        $clean = preg_replace('#/.*$#', '', $clean); // drop trailing "/metre", "/piece" etc.

        return is_numeric($clean) ? (float) $clean : null;
    }

    /** Explains why a cost/selling price cell failed to parse, distinguishing a range from other bad input. */
    private function priceProblemMessage(string $field, ?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return ucfirst($field).' is missing.';
        }

        if (preg_match('/\d\s*[-–—]\s*\d/', $value)) {
            return "Price ranges aren't supported for {$field} (\"{$value}\") — enter a single exact number, e.g. \"440\" instead of \"400-480\".";
        }

        return "{$field} (\"{$value}\") is not a valid number.";
    }

    public function store(Request $request)
    {
        $data = $this->validateProduct($request);
        $openingStock = (int) ($data['stock'] ?? 0);
        unset($data['stock']);

        $isFirstProduct = ! $request->user()->products()->exists();

        DB::transaction(function () use ($request, $data, $openingStock) {
            $product = $request->user()->products()->create($data + ['stock' => 0]);

            if ($openingStock > 0) {
                $product->recordMovement('add', $openingStock, 'Opening stock');
            }
        });

        if ($isFirstProduct) {
            return redirect()->route('dashboard')->with('status', 'Product added! Welcome to VyaparHub 🎉');
        }

        return redirect()->route('products.index')->with('status', 'Product added successfully.');
    }

    public function edit(Request $request, Product $product)
    {
        $this->authorizeProduct($request, $product);

        return view('products.edit', [
            'product' => $product,
            'categories' => Product::CATEGORIES,
            'productTypes' => Product::PRODUCT_TYPES,
            'categoryFields' => Product::CATEGORY_FIELDS,
            'movements' => $product->movements()->limit(15)->get(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $this->authorizeProduct($request, $product);

        $data = $this->validateProduct($request, $product);
        unset($data['stock']); // stock is changed only via adjustments, not edits

        $product->update($data);

        return redirect()->route('products.index')->with('status', 'Product updated.');
    }

    public function destroy(Request $request, Product $product)
    {
        $this->authorizeProduct($request, $product);
        $product->delete();

        return redirect()->route('products.index')->with('status', 'Product deleted.');
    }

    /** Manual correction (+/-) to on-hand stock; logged in stock_history as type=adjustment. */
    public function adjustStock(Request $request, Product $product)
    {
        $this->authorizeProduct($request, $product);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'not_in:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        if ($product->stock + $validated['quantity'] < 0) {
            return back()->withErrors(['quantity' => 'Adjustment would make stock negative.']);
        }

        $product->recordMovement('adjustment', $validated['quantity'], $validated['note'] ?? null);

        return back()->with('status', 'Stock updated.');
    }

    /** Dedicated "+ Add Stock" action: increments stock and logs a stock_history entry (type=add). */
    public function addStock(Request $request, Product $product)
    {
        $this->authorizeProduct($request, $product);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $product->recordMovement('add', $validated['quantity'], $validated['note'] ?? null);

        return back()->with('status', "Added {$validated['quantity']} units to {$product->name}.");
    }

    private function validateProduct(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'product_type' => ['nullable', 'string', 'max:100'],
            'category' => ['required', 'string', Rule::in(array_keys(Product::CATEGORIES))],
            'custom_attributes' => ['nullable', 'array'],
            'custom_attributes.*' => ['nullable', 'string', 'max:255'],
            'source_location' => ['nullable', 'string', 'max:255'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'gst_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'stock_threshold' => ['required', 'integer', 'min:0'],
        ]);

        if (array_key_exists('custom_attributes', $data)) {
            $data['custom_attributes'] = array_filter(
                $data['custom_attributes'],
                fn ($v) => $v !== null && $v !== ''
            ) ?: null;
        }

        // The "Product type" field is only shown/submitted for Textile; explicitly clear it
        // for every other category so a stale value can't survive a category switch on update().
        if ($data['category'] !== 'textile') {
            $data['product_type'] = null;
        }

        return $data;
    }

    private function authorizeProduct(Request $request, Product $product): void
    {
        abort_unless($product->user_id === $request->user()->id, 403);
    }
}
