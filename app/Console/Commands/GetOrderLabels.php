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
        $tiktok->runSyncOrdersWithLabel();   // CLI chạy trực tiếp phần xử lý nặng
        return Command::SUCCESS;
    }
}
