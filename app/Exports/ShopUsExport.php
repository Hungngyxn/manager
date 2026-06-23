<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ShopUsExport implements FromCollection, WithHeadings
{
    protected $orders;

    public function __construct($orders)
    {
        $this->orders = $orders;
    }

    public function collection()
    {
        $data = collect();

        foreach ($this->orders as $order) {
            $products = $order->products ?? [];

            // Nếu products là chuỗi JSON thì decode
            if (is_string($products)) {
                $products = json_decode($products, true) ?? [];
            }

            // Lặp qua từng sản phẩm
            foreach ($products as $index => $product) {
                $data->push([
                    'Fullname'        => $index === 0 ? $order->customer_name : '', // chỉ dòng đầu mới có
                    'Phone'           => $index === 0 ? $order->customer_phone : '',
                    'Email'           => $index === 0 ? '' : '',
                    'Street'          => $index === 0 ? $order->customer_address : '',
                    'Street2'         => '',
                    'City'            => $index === 0 ? $order->customer_city : '',
                    'State'           => $index === 0 ? $order->customer_state : '',
                    'Country'         => $index === 0 ? $order->customer_country : '',
                    'Zipcode'         => $index === 0 ? $order->customer_postcode : '',
                    'SKU'             => $product['sku'] ?? '',
                    'Quantity'        => $product['quantity'] ?? 1,
                    'Label Url'       =>  $index === 0 ? $order->label_link : '',
                    'Tracking Number' =>  $index === 0 ? $order->tracking_number : '',
                    'Extra Id'        =>  $index === 0 ? "'". $order->order_id : '',
                    // 'Extra Id'        =>   "'". $order->order_id,

                ]);
            }

            // Nếu đơn hàng không có sản phẩm
            if (empty($products)) {
                $data->push([
                    'Fullname'        => $order->customer_name,
                    'Phone'           => $order->customer_phone,
                    'Email'           => '',
                    'Street'          => $order->customer_address,
                    'Street2'         => '',
                    'City'            => $order->customer_city,
                    'State'           => $order->customer_state,
                    'Country'         => $order->customer_country,
                    'Zipcode'         => $order->customer_postcode,
                    'SKU'             => '',
                    'Quantity'        => '',
                    'Label Url'       => $order->label_link ?? '',
                    'Tracking Number' => $order->tracking_number ?? '',
                    'Extra Id'        => '',
                ]);
            }
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'Fullname',
            'Phone',
            'Email',
            'Street',
            'Street2',
            'City',
            'State',
            'Country',
            'Zipcode',
            'SKU',
            'Quantity',
            'Label Url',
            'Tracking Number',
            'Extra Id'
        ];
    }
}
