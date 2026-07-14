<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('shop_name', 'business_name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('mobile', 10)->nullable()->after('business_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('mobile');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('business_name', 'shop_name');
        });
    }
};
