<?php

namespace App\Jobs;

use App\Http\Controllers\ShopUsController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncShopUsPendingStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Cập nhật trạng thái các đơn ShopUS đang chờ.
     * Job không mang dữ liệu nên an toàn khi dispatchAfterResponse().
     */
    public function handle(ShopUsController $controller): void
    {
        $controller->runSyncPendingOrdersStatus();
    }
}
