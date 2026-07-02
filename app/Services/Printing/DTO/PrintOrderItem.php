<?php

namespace App\Services\Printing\DTO;

/**
 * Một dòng sản phẩm cần in, đã chuẩn hoá (độc lập nhà in).
 * Provider sẽ dịch sang định dạng riêng (vd FlashShip: printer_design_{pos}_url).
 */
class PrintOrderItem
{
    /**
     * @param string[] $positions Vị trí in dạng thô từ Print setup, vd ['Front','Back']
     */
    public function __construct(
        public ?int $variantId,
        public int $quantity,
        public array $positions,
        public ?string $designUrl,
        public ?string $mockupUrl,
        public bool $specialPrint = false,
        public string $note = '',
        public ?int $printType = null,
    ) {
    }
}
