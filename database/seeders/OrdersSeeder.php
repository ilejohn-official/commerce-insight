<?php

namespace Database\Seeders;

use Spatie\Async\Pool;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Console\Kernel;

class OrdersSeeder extends Seeder
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

                $orders = [];
                for ($j = $i; $j < $totalRecords; $j += $numberOfProcesses) {
                    $orders[] = [
                        'customer_id' => rand(1, 500000),
                        'order_date' => now()->subDays(rand(0, 730)),
                        'status' => ['pending', 'completed', 'shipped', 'cancelled'][array_rand(['pending', 'completed', 'shipped', 'cancelled'])],
                        'total_amount' => rand(1000, 200000) / 100,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // Insert in batches
                    if (count($orders) === $batchSize) {
                        try {
                            DB::table('orders')->insert($orders);
                        } catch (\Exception $e) {
                            Log::error("Error in process {$i}: " . $e->getMessage());
                        }
                        $orders = []; // Clear the batch
                        unset($orders);
                        gc_collect_cycles(); // Force garbage collection
                    }
                }

                // Insert any remaining records
                if (! empty($orders)) {
                    try {
                        DB::table('orders')->insert($orders);
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
