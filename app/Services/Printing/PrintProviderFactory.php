<?php

namespace App\Services\Printing;

use App\Services\Printing\Contracts\PrintProvider;
use App\Services\Printing\Exceptions\PrintException;

/**
 * Factory: phân giải provider theo key cấu hình trong config/printing.php.
 * Dùng: PrintProviderFactory::make('flashship') hoặc make() lấy provider mặc định.
 */
class PrintProviderFactory
{
    public static function make(?string $key = null): PrintProvider
    {
        $key = $key ?: config('printing.default');
        $config = config("printing.providers.{$key}");

        if (empty($config) || empty($config['driver'])) {
            throw new PrintException("Không tìm thấy cấu hình nhà in: {$key}");
        }

        $driver = $config['driver'];
        $provider = new $driver($config);

        if (!$provider instanceof PrintProvider) {
            throw new PrintException("Driver nhà in '{$key}' phải implements PrintProvider.");
        }

        return $provider;
    }
}
