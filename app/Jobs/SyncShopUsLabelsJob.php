<?php

namespace App\Jobs;

use App\Http\Controllers\ShopUsController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncShopUsLabelsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Chạy phần đồng bộ đơn + lấy label của ShopUS.
     *
     * Job không mang dữ liệu nên an toàn khi dispatchAfterResponse()
     * (chạy ngay sau khi response đã trả về trình duyệt, không cần queue worker).
     */
    public function handle(ShopUsController $controller): void
    {
        $controller->runSyncOrdersWithLabel();
    }
}
