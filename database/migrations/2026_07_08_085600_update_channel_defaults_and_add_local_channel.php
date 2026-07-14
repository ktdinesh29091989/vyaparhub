<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Only touches channel rows still sitting at the OLD default values (0/0, 15/0, 0/60) —
     * any seller who already edited their commission/shipping keeps their own numbers.
     */
    public function up(): void
    {
        DB::table('channels')->where('slug', 'meesho')
            ->where('commission_percent', 0)->where('shipping_charge', 0)
            ->update(['commission_percent' => 2, 'shipping_charge' => 50]);

        DB::table('channels')->where('slug', 'amazon')
            ->where('commission_percent', 15)->where('shipping_charge', 0)
            ->update(['commission_percent' => 3, 'shipping_charge' => 60]);

        DB::table('channels')->where('slug', 'whatsapp')
            ->where('commission_percent', 0)->where('shipping_charge', 60)
            ->update(['commission_percent' => 0, 'shipping_charge' => 0]);

        $usersWithoutLocal = DB::table('users')
            ->whereNotIn('id', DB::table('channels')->where('slug', 'local')->pluck('user_id'))
            ->pluck('id');

        $now = now();
        foreach ($usersWithoutLocal as $userId) {
            DB::table('channels')->insert([
                'user_id' => $userId,
                'name' => 'Local',
                'slug' => 'local',
                'commission_percent' => 0,
                'shipping_charge' => 0,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('channels')->where('slug', 'meesho')
            ->where('commission_percent', 2)->where('shipping_charge', 50)
            ->update(['commission_percent' => 0, 'shipping_charge' => 0]);

        DB::table('channels')->where('slug', 'amazon')
            ->where('commission_percent', 3)->where('shipping_charge', 60)
            ->update(['commission_percent' => 15, 'shipping_charge' => 0]);

        DB::table('channels')->where('slug', 'whatsapp')
            ->where('commission_percent', 0)->where('shipping_charge', 0)
            ->update(['commission_percent' => 0, 'shipping_charge' => 60]);

        DB::table('channels')->where('slug', 'local')->delete();
    }
};
