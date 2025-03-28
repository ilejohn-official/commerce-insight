<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RefreshConsolidatedOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'consolidated-orders:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh consolidated orders table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = microtime(true);

        DB::disableQueryLog();
        DB::statement('SET FOREIGN_KEY_CHECKS=0;'); // Disable foreign key checks
        DB::table('consolidated_orders')->truncate();
        DB::statement('ALTER TABLE consolidated_orders DISABLE KEYS'); // Disable indexes

        DB::statement("
            INSERT INTO consolidated_orders (
                order_id, customer_id, customer_name, customer_email, 
                product_id, product_name, sku, quantity, item_price, 
                line_total, order_date, order_status, order_total, created_at, updated_at
            )
            SELECT 
                o.id, c.id, c.name, c.email, p.id, p.name, p.sku, 
                oi.quantity, oi.price, oi.quantity * oi.price, o.order_date, 
                o.status, o.total_amount, NOW(), NOW()
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN customers c ON o.customer_id = c.id
            JOIN products p ON oi.product_id = p.id
        ");

        DB::statement('ALTER TABLE consolidated_orders ENABLE KEYS');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->info('Consolidated orders table refreshed successfully.');
        $this->info('Execution time: ' . round($executionTime, 2) . ' seconds.');
    }
}
