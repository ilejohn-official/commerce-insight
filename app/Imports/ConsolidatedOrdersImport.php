<?php

namespace App\Imports;

use App\Models\ConsolidatedOrder;
use Maatwebsite\Excel\Concerns\ToModel;

class ConsolidatedOrdersImport implements ToModel
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return new ConsolidatedOrder([
            'order_id' => $row[0],
            'customer_id' => $row[1],
            'customer_name' => $row[2],
            'customer_email' => $row[3],
            'product_id' => $row[4],
            'product_name' => $row[5],
            'sku' => $row[6],
            'quantity' => $row[7],
            'item_price' => $row[8],
            'line_total' => $row[9],
            'order_date' => $row[10],
            'order_status' => $row[11],
            'order_total' => $row[12],
        ]);
    }
}
