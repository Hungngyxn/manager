<?php

namespace App\Services\Printing\DTO;

/**
 * Kết quả gửi đơn tới nhà in, đã chuẩn hoá.
 */
class PrintOrderResult
{
    public function __construct(
        public bool $success,
        public ?string $providerOrderId,
        public ?string $code,
        public string $message,
        public bool $pendingPayment = false,
        public array $raw = [],
    ) {
    }
}
