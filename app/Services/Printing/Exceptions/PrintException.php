<?php

namespace App\Services\Printing\Exceptions;

use RuntimeException;

/**
 * Lỗi nghiệp vụ khi gửi đơn tới nhà in (thiếu dữ liệu, provider trả lỗi, auth fail...).
 */
class PrintException extends RuntimeException
{
}
