<?php

namespace App\Imports;

use App\Models\Order;
use App\Models\ProductList;
use App\Models\Statement;
use App\Models\ListProduct;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class StatementImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        
        $rows->skip(1)->each(function ($row) {
            
            $orderId = $row[7];
            $productName = trim($row[10]);
            $totalSettlement = $row[15];
            $status = $row[4];
            
            if (empty($orderId)) {
                return;
            }
            
            $tier = ProductList::whereRaw('LOWER(product_name) = ?', [strtolower($productName)])->value('tier');
            $fulfillFee = Order::where('order_id', $orderId)->value('fulfill_fee');

            Statement::create([
                'user_id' => auth()->id(),
                'order_id' => $orderId,
                'total_settlement' => (float) $totalSettlement,
                'product_name' => $productName,
                'tier' => $tier,
                'fulfill_fee' => (float) $fulfillFee,
                'status' => $status ?? 'pending',
            ]);
        });
    }
    }
