<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrdersExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $orders;

    public function __construct($orders)
    {
        $this->orders = $orders;
    }

    public function collection()
    {
        return $this->orders;
    }

    public function map($item): array
    {
        return [
            $item->extra_id . "\t",
            $item->order_id. "\t",
            $item->sku,
            $item->skuInfo->name ?? null,
            $item->shop_name,
            $item->seller->name ?? null,
            $item->quantity,
            $item->cost,
            $item->fulfill_fee,
            $item->total,
            $item->profit,
            $item->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    public function headings(): array
    {
        return [
            'Extra ID',
            'Order ID',
            'SKU',
            'Product Name',
            'Shop',
            'Seller',
            'Quantity',
            'Cost',
            'Fulfill Fee',
            'Total',
            'Profit',
            'Created At',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        foreach (range(2, count($this->orders) + 1) as $row) {
            $sheet->getStyle("A$row")->getNumberFormat()->setFormatCode('@');
        }

        return [];
    }
}
