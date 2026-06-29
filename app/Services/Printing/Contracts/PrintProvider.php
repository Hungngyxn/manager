<?php

namespace App\Services\Printing\Contracts;

use App\Services\Printing\DTO\PrintOrderRequest;
use App\Services\Printing\DTO\PrintOrderResult;

/**
 * Hợp đồng chung cho mọi nhà in (FlashShip, ... sau này).
 * Thêm nhà in mới chỉ cần implement interface này + khai báo trong config/printing.php.
 */
interface PrintProvider
{
    /** Khoá định danh provider, vd 'flashship'. */
    public function key(): string;

    /** Tạo đơn in tại nhà in. */
    public function createOrder(PrintOrderRequest $request): PrintOrderResult;

    /** Lấy trạng thái đơn theo mã đơn của nhà in (null nếu không hỗ trợ/không thấy). */
    public function getOrderStatus(string $providerOrderId): ?string;
}
