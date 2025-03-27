<?php

namespace Database\Seeders;

use Spatie\Async\Pool;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Console\Kernel;

class CustomersSeeder extends Seeder
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
        $totalRecords = 500001; // Total number of records to generate
        $numberOfProcesses = 5; // Number of concurrent tasks

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

                $customers = [];
                for ($j = $i; $j < $totalRecords; $j += $numberOfProcesses) {
                    $customers[] = [
                        'name' => fake()->name,
                        'email' => fake()->safeEmail . "+{$i}_{$j}@example.com",
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // Insert in batches
                    if (count($customers) === $batchSize) {
                        try {
                            DB::table('customers')->insert($customers);
                        } catch (\Exception $e) {
                            Log::error("Error in process {$i}: " . $e->getMessage());
                        }
                        $customers = []; // Clear the batch
                        unset($customers);
                        gc_collect_cycles(); // Force garbage collection
                    }
                }

                // Insert any remaining records
                if (! empty($customers)) {
                    try {
                        DB::table('customers')->insert($customers);
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
