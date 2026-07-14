<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * APP_TIMEZONE switched from UTC to Asia/Kolkata. Rows written before this
 * change hold true-UTC clock digits; new rows will hold true-IST clock digits.
 * Rebase every existing timestamp (+5:30) so old and new rows agree once
 * everything is interpreted in IST going forward. Date-only columns
 * (order_date, spent_on) are untouched — they have no time-of-day to shift.
 */
return new class extends Migration
{
    /** @var array<string, list<string>> */
    private array $columnsByTable = [
        'users' => ['created_at', 'updated_at', 'email_verified_at', 'plan_expires_at'],
        'channels' => ['created_at', 'updated_at'],
        'products' => ['created_at', 'updated_at', 'deleted_at'],
        'stock_history' => ['created_at'],
        'orders' => ['created_at', 'updated_at', 'deleted_at'],
        'order_items' => ['created_at', 'updated_at'],
        'expenses' => ['created_at', 'updated_at'],
    ];

    public function up(): void
    {
        $this->shift(330);
    }

    public function down(): void
    {
        $this->shift(-330);
    }

    private function shift(int $minutes): void
    {
        foreach ($this->columnsByTable as $table => $columns) {
            foreach ($columns as $column) {
                DB::table($table)->whereNotNull($column)->orderBy('id')
                    ->select(['id', $column])
                    ->chunkById(200, function ($rows) use ($table, $column, $minutes) {
                        foreach ($rows as $row) {
                            DB::table($table)->where('id', $row->id)->update([
                                $column => Carbon::parse($row->$column)->addMinutes($minutes)->format('Y-m-d H:i:s'),
                            ]);
                        }
                    });
            }
        }
    }
};
