<?php

namespace App\Console\Commands;

use App\Models\ShopUs;
use Illuminate\Console\Command;

class RelocateShopUsLabels extends Command
{
    protected $signature = 'shopus:relocate-labels';
    protected $description = 'Chuyển label PDF từ storage/app/public/label_links sang label_links/ ở gốc project và cập nhật label_link trong DB';

    public function handle()
    {
        $oldDir = storage_path('app/public/label_links');
        $newDir = base_path('label_links');

        if (!is_dir($newDir)) {
            mkdir($newDir, 0755, true);
        }

        // 1) Di chuyển file PDF còn nằm trong storage sang label_links/ ở gốc project
        $moved = 0;
        if (is_dir($oldDir)) {
            foreach (glob($oldDir . '/*') as $src) {
                $dest = $newDir . '/' . basename($src);
                if (!file_exists($dest) && @rename($src, $dest)) {
                    $moved++;
                }
            }
        }

        // 2) Cập nhật label_link trong DB: mọi link trỏ tới label_links/{file} -> đường dẫn tương đối
        $updated = 0;
        ShopUs::where('label_link', 'like', '%label_links/%')->each(function (ShopUs $order) use (&$updated) {
            $name = basename(parse_url($order->label_link, PHP_URL_PATH) ?: $order->label_link);
            $relative = "label_links/{$name}";

            if ($order->label_link !== $relative) {
                $order->update(['label_link' => $relative]);
                $updated++;
            }
        });

        $this->info("Đã chuyển {$moved} file, cập nhật {$updated} bản ghi label_link.");

        return Command::SUCCESS;
    }
}
