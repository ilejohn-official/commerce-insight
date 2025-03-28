<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use App\Models\ConsolidatedOrder;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class AppendMoreOrders implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public $chunkIndex,
        public int $chunkSize,
        public string $filePath
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $orders = ConsolidatedOrder::select([
            'order_id',
            'customer_id',
            'customer_name',
            'customer_email',
            'product_id',
            'product_name',
            'sku',
            'quantity',
            'item_price',
            'line_total',
            'order_date',
            'order_status',
            'order_total',
            'created_at'
        ])->skip($this->chunkIndex * $this->chunkSize)
            ->take($this->chunkSize)
            ->get();

        $open = fopen($this->filePath, 'a+');
        foreach ($orders as $moreOrder) {
            fputcsv($open, $moreOrder->toArray());
        }
        fclose($open);
    }
}
