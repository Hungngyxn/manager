<?php

use App\Services\Printing\Providers\FlashShipProvider;

return [
    /*
    |--------------------------------------------------------------------------
    | Nhà in mặc định khi bấm "Send to printer"
    |--------------------------------------------------------------------------
    */
    'default' => env('PRINT_PROVIDER', 'flashship'),

    /*
    |--------------------------------------------------------------------------
    | Danh sách nhà in (POD provider)
    |--------------------------------------------------------------------------
    | Thêm nhà in mới = thêm 1 block ở đây + 1 class implements PrintProvider.
    | Factory đọc 'driver' để khởi tạo, phần config còn lại truyền vào provider.
    */
    'providers' => [
        'flashship' => [
            'driver' => FlashShipProvider::class,

            // Test: https://uat-api.flashship.net/seller-api-v2
            // Prod: https://api.flashship.net/seller-api-v2
            'base_url' => env('FLASHSHIP_BASE_URL', 'https://uat-api.flashship.net/seller-api-v2'),

            // Ưu tiên API token (hạn 1 năm, không cần gọi /token).
            // Nếu trống thì fallback đăng nhập bằng username/password.
            'api_token' => env('FLASHSHIP_API_TOKEN'),
            'username'  => env('FLASHSHIP_USERNAME'),
            'password'  => env('FLASHSHIP_PASSWORD'),

            // printType mặc định: 1 = DTF, 2 = DTG, 3 = Basic DTF
            'default_print_type' => (int) env('FLASHSHIP_PRINT_TYPE', 1),

            // Map "shipment" (chuỗi ở Print setup) -> mã số FlashShip
            // 1: FirstClass | 2: Priority | 3: RushProduction | 4: OverNight | 6: Expedite
            'shipment_map' => [
                'Standard'        => 1,
                'Rush Product'    => 3,
                'Expedite Tiktok' => 6,
            ],
        ],
    ],
];
