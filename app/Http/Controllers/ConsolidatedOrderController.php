<?php

namespace App\Http\Controllers;

use PDO;
use Illuminate\Http\Request;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ConsolidatedOrdersImport;

class ConsolidatedOrderController extends Controller
{
    public function __construct(
        public OrderService $orderService,
    ) {}

    /**
     * Export consolidated orders.
     */
    public function export(): JsonResponse
    {
        $this->orderService->exportOrders();

        return response()->json(['message' => 'Export started. Stakeholders will be notified when it is ready.']);
    }

    /**
     * Import consolidated orders from an Excel file.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv|max:2048',
        ]);

        // Convert Excel to CSV (if xlsx)
        $file = $request->file('file');
        $csvFilePath = storage_path('app/imports/consolidated_orders.csv');

        if ($file->getClientOriginalExtension() === 'xlsx') {
            Excel::import(new ConsolidatedOrdersImport($csvFilePath), $file);
        } else {
            $file->storeAs('imports', 'consolidated_orders.csv', 'local');
        }

        // Import using LOAD DATA INFILE
        $this->orderService->importLoadDataInfile($csvFilePath);

        return response()->json(['message' => 'Import successful']);
    }
}
