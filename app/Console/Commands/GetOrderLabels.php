<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ShopUsController;

class GetOrderLabels extends Command
{
    protected $signature = 'tiktok:get-labels';
    protected $description = 'Fetch TikTok orders and labels (Cron Job)';

    public function handle(ShopUsController $tiktok)
    {
        $tiktok->syncOrdersWithLabel();   // Chỉ gọi 1 hàm duy nhất
        return Command::SUCCESS;
    }
}
