<?php

namespace Database\Seeders;

use Spatie\Async\Pool;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Console\Kernel;

class OrderItemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable query logging and event dispatching globally
        DB::disableQueryLog();
        DB::connection()->unsetEventDispatcher();

        $batchSize = 1000; // batch size
        $totalRecords = 1000001; // Total number of records to generate
        $numberOfProcesses = 3; // Number of concurrent tasks

        $pool = Pool::create();

        for ($i = 0; $i < $numberOfProcesses; $i++) {
            $pool->add(function () use ($i, $numberOfProcesses, $batchSize, $totalRecords) {
                // Manually bootstrap the Laravel application
                $app = require __DIR__ . '/../../bootstrap/app.php';
                $app->make(Kernel::class)->bootstrap();

                // Disable query logging and event dispatching for each process and ensure a fresh connection
                DB::disableQueryLog();
                DB::connection()->unsetEventDispatcher();
                DB::reconnect();

                $orderItems = [];
                for ($j = $i; $j < $totalRecords; $j += $numberOfProcesses) {
                    $orderItems[] = [
                        'order_id' => rand(1, 1000000),
                        'product_id' => rand(1, 100000),
                        'quantity' => rand(1, 10),
                        'price' => round(mt_rand(500, 50000) / 100, 2),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // Insert in batches
                    if (count($orderItems) === $batchSize) {
                        try {
                            DB::table('order_items')->insert($orderItems);
                        } catch (\Exception $e) {
                            Log::error("Error in process {$i}: " . $e->getMessage());
                        }
                        $orderItems = [];
                        unset($orderItems);
                        gc_collect_cycles();
                    }
                }

                // Insert any remaining records
                if (! empty($orderItems)) {
                    try {
                        DB::table('order_items')->insert($orderItems);
                    } catch (\Exception $e) {
                        Log::error("Error in process {$i}: " . $e->getMessage());
                    }
                }
            });
        }

        // Wait for all tasks to complete
        $pool->wait();
    }
}
