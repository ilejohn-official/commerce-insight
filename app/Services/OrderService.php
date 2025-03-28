<?php

namespace App\Services;

use PDO;
use Illuminate\Bus\Batch;
use App\Jobs\AppendMoreOrders;
use App\Models\ConsolidatedOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Bus;
use App\Jobs\CreateOrdersExportFile;
use App\Notifications\ExportCompleted;
use Illuminate\Support\Facades\Notification;

class OrderService
{
  /**
   * Export consolidated orders to a CSV file.
   */
  public function exportOrders(): void
  {
    $chunkSize = 10000;
    $ordersCount = ConsolidatedOrder::count();
    $numberOfChunks = ceil($ordersCount / $chunkSize);
    $fileName = 'consolidated_orders_' . now()->format('Y-m-d_H-i-s') . '.csv';
    $filePath = storage_path('app/public/' . $fileName);

    $batches = [
      new CreateOrdersExportFile($chunkSize, $filePath)
    ];

    if ($ordersCount > $chunkSize) {
      $numberOfChunks = $numberOfChunks - 1;
      for ($numberOfChunks; $numberOfChunks > 0; $numberOfChunks--) {
        $batches[] = new AppendMoreOrders($numberOfChunks, $chunkSize, $filePath);
      }
    }

    Bus::batch($batches)
      ->name('Export Consolidated Orders')
      ->then(function (Batch $batch) use ($fileName) {
        // Notify stakeholders
        $emails = config('notifications.export_recipients');

        // For Devs
        logger()->info('Export completed. path: ' . asset("storage/{$fileName}"));

        foreach ($emails as $email) {
          Notification::route('mail', $email)
            ->notify(new ExportCompleted(asset("storage/{$fileName}")));
        }
      })
      ->catch(function (Batch $batch, \Throwable $e) {
        logger()->error('Export failed: ' . $e->getMessage());
      })
      ->dispatch();
  }

  /**
   * Import consolidated orders from a CSV file using LOAD DATA INFILE.
   */
  public function importLoadDataInfile(string $filePath): void
  {
    $pdo = DB::connection()->getPdo();
    $pdo->setAttribute(PDO::MYSQL_ATTR_LOCAL_INFILE, true);

    $filepath = str_replace('\\', '/', $filePath);

    // Step 1: Load data into a temporary table
    $pdo->exec("DROP TABLE IF EXISTS temp_consolidated_orders");
    $pdo->exec("
        CREATE TEMPORARY TABLE temp_consolidated_orders LIKE consolidated_orders
    ");

    $query = <<<SQL
    LOAD DATA LOCAL INFILE '$filepath'
    INTO TABLE temp_consolidated_orders
    FIELDS TERMINATED BY ',' 
    ENCLOSED BY '"'
    LINES TERMINATED BY '\n'
    IGNORE 1 LINES
    (@order_id, @customer_id, @customer_name, @customer_email, @product_id, @product_name, @sku, @quantity, @item_price, @line_total, @order_date, @order_status, @order_total)
    SET
        order_id = @order_id,
        customer_id = @customer_id,
        customer_name = @customer_name,
        customer_email = @customer_email,
        product_id = @product_id,
        product_name = @product_name,
        sku = @sku,
        quantity = @quantity,
        item_price = @item_price,
        line_total = @line_total,
        order_date = NULLIF(NULLIF(@order_date, '0000-00-00 00:00:00'), ''),
        order_status = @order_status,
        order_total = @order_total,
        created_at = NOW(),
        updated_at = NOW()
    SQL;

    $pdo->exec($query);

    // Step 2: Merge temp table with consolidated_orders
    $mergeQuery = <<<SQL
    INSERT INTO consolidated_orders (
        order_id, customer_id, customer_name, customer_email, 
        product_id, product_name, sku, quantity, item_price, 
        line_total, order_date, order_status, order_total, created_at, updated_at
    )
    SELECT 
        order_id, customer_id, customer_name, customer_email, 
        product_id, product_name, sku, quantity, item_price, 
        line_total, order_date, order_status, order_total, created_at, updated_at
    FROM temp_consolidated_orders
    ON DUPLICATE KEY UPDATE 
        customer_name = VALUES(customer_name),
        customer_email = VALUES(customer_email),
        product_name = VALUES(product_name),
        sku = VALUES(sku),
        quantity = VALUES(quantity),
        item_price = VALUES(item_price),
        line_total = VALUES(line_total),
        order_date = VALUES(order_date),
        order_status = VALUES(order_status),
        order_total = VALUES(order_total),
        updated_at = NOW()
    SQL;

    $pdo->exec($mergeQuery);
  }
}
