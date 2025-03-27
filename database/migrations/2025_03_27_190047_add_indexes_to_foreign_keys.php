<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index('customer_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->index(['order_id', 'product_id']);
        });

        Schema::table('consolidated_orders', function (Blueprint $table) {
            $table->index(['order_id', 'customer_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['customer_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['order_id', 'product_id']);
        });

        Schema::table('consolidated_orders', function (Blueprint $table) {
            $table->dropIndex(['order_id', 'customer_id', 'product_id']);
        });
    }
};
