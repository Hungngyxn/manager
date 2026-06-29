<?php

namespace App\Services\Printing\DTO;

use App\Models\ShopUs;

/**
 * Dữ liệu đơn cần in đã chuẩn hoá (độc lập nhà in).
 * `fromShopUs()` gom dữ liệu từ bảng shop_us (cột products + print + địa chỉ khách).
 */
class PrintOrderRequest
{
    /**
     * @param PrintOrderItem[] $items
     */
    public function __construct(
        public string $externalOrderId,
        public string $buyerFirstName,
        public string $buyerLastName,
        public ?string $buyerEmail,
        public ?string $buyerPhone,
        public ?string $buyerAddress1,
        public ?string $buyerAddress2,
        public ?string $buyerCity,
        public ?string $buyerProvince,
        public ?string $buyerZip,
        public ?string $buyerCountry,
        public ?string $shipment,
        public ?string $labelUrl,
        public array $items,
    ) {
    }

    public static function fromShopUs(ShopUs $order): self
    {
        $print = $order->print ?? [];
        [$first, $last] = self::splitName((string) $order->customer_name);

        $items = [];
        foreach (($order->products ?? []) as $p) {
            $variantId = isset($p['variant_id']) && $p['variant_id'] !== '' ? (int) $p['variant_id'] : null;

            $items[] = new PrintOrderItem(
                variantId: $variantId,
                quantity: (int) ($p['quantity'] ?? 1),
                positions: array_values(array_filter((array) ($p['print_position'] ?? []))),
                designUrl: $p['design_url'] ?? null,
                mockupUrl: $p['mockup_url'] ?? null,
                specialPrint: !empty($p['special_print']),
                note: (string) ($p['note'] ?? ''),
                printType: isset($p['print_type']) ? (int) $p['print_type'] : null,
            );
        }

        return new self(
            externalOrderId: (string) $order->order_id,
            buyerFirstName: $first !== '' ? $first : 'Customer',
            buyerLastName: $last,
            buyerEmail: null,
            buyerPhone: $order->customer_phone,
            buyerAddress1: $order->customer_address,
            buyerAddress2: null,
            buyerCity: $order->customer_city,
            buyerProvince: $order->customer_state,
            buyerZip: $order->customer_postcode,
            buyerCountry: $order->customer_country,
            shipment: $print['shipment'] ?? null,
            labelUrl: $print['shipping_label_url'] ?? null,
            items: $items,
        );
    }

    /**
     * Tách "John Doe Smith" -> ['John', 'Doe Smith']. Một từ -> last rỗng.
     *
     * @return array{0:string,1:string}
     */
    private static function splitName(string $fullName): array
    {
        $fullName = trim(preg_replace('/\s+/', ' ', $fullName));
        if ($fullName === '') {
            return ['', ''];
        }

        $parts = explode(' ', $fullName, 2);
        return [$parts[0], $parts[1] ?? ''];
    }
}
