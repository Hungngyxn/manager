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
    | Danh sách máy in / nhà in (dropdown "Printer" ở modal Print setup)
    |--------------------------------------------------------------------------
    | Định dạng: 'value' => [
    |     'label'    => 'Nhãn hiển thị',
    |     'enabled'  => bool,        // false -> hiển thị nhưng không chọn được (disabled)
    |     'provider' => 'key|null',  // key trong 'providers' bên dưới sẽ nhận đơn khi "Send to printer"
    | ]
    | Chọn provider theo trường 'provider' này (KHÔNG dựa vào việc trùng tên slug).
    | Hiện chỉ FlashShip đã tích hợp; các nhà in khác để provider = null chờ tích hợp.
    */
    'printers' => [
        'monkey_king_print' => ['label' => 'Monkey King Print', 'enabled' => false, 'provider' => null],
        'flashship'         => ['label' => 'FlashShip',         'enabled' => true,  'provider' => 'flashship'],
        'tee_all_over'      => ['label' => 'Tee All Over',      'enabled' => false, 'provider' => null],
        'embroidered_mpk'   => ['label' => 'Embroidered-MPK',   'enabled' => false, 'provider' => null],
        'mango'             => ['label' => 'Mango',             'enabled' => false, 'provider' => null],
        'thao_nguyen_poster' => ['label' => 'Thảo Nguyễn - Poster', 'enabled' => false, 'provider' => null],
        'mu_tommy'          => ['label' => 'Mũ Tommy',          'enabled' => false, 'provider' => null],
        'vu_us'             => ['label' => 'Vũ US',             'enabled' => false, 'provider' => null],
        'ff_amazon'         => ['label' => 'FF Amazon',         'enabled' => false, 'provider' => null],
        'lenful'            => ['label' => 'Lenful',            'enabled' => false, 'provider' => null],
        'shopee'            => ['label' => 'Shopee',            'enabled' => false, 'provider' => null],
        'catkissfish'       => ['label' => 'CatKissFish',       'enabled' => false, 'provider' => null],
        'merchize'          => ['label' => 'Merchize',          'enabled' => false, 'provider' => null],
        'printeeshub'       => ['label' => 'printeeshub.com',   'enabled' => false, 'provider' => null],
        'sellerwix'         => ['label' => 'SellerWix',         'enabled' => false, 'provider' => null],
        'print_arrows'      => ['label' => 'Print Arrows',      'enabled' => false, 'provider' => null],
        'a2k_ecom'          => ['label' => 'A2K Ecom',          'enabled' => false, 'provider' => null],
        'pressify'          => ['label' => 'Pressify',          'enabled' => false, 'provider' => null],
    ],

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
