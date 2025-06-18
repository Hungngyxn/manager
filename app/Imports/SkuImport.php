<?php

namespace App\Imports;

use App\Models\Sku;
use App\Models\Tier;
use App\Services\SkuService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SkuImport implements ToCollection, WithHeadingRow
{
    public $created = [];
    public $updated = [];
    public $skipped = [];
    protected $skuService;
    protected $skuMap = [];

    public function __construct()
    {
        $this->skuService = new SkuService();
    }

    public function collection(Collection $rows)
    {
        // Bước 1: Lưu map SKU gốc để hỗ trợ tính Pack
        foreach ($rows as $row) {
            if ($row->filter()->isEmpty()) continue;

            $sku = trim($row['sku'] ?? '');
            $cost = isset($row['base_cost']) ? str_replace(',', '.', $row['base_cost']) : null;
            $quantity = $row['so_luong_ton_kho'] ?? null;

            if (!$sku) continue;

            if (is_numeric($cost) && is_numeric($quantity) && $quantity !== '#N/A') {
                $this->skuMap[$sku] = [
                    'cost'     => floatval($cost),
                    'quantity' => floatval($quantity),
                ];
            }
        }

        // Bước 2: Xử lý từng dòng dữ liệu
        foreach ($rows as $row) {
            if ($row->filter()->isEmpty()) continue;

            try {
                $skuCode      = trim($row['sku'] ?? '');
                $rawCost      = $row['base_cost'] ?? null;
                $rawQuantity  = $row['so_luong_ton_kho'] ?? null;
                $skuName      = $row['mat_hang'] ?? null;
                $tierRaw     = trim($row['tier'] ?? '') ?: null;
                $tierName = trim(preg_replace('/\s*\(.*/', '', $tierRaw));

                if (empty($skuCode)) {
                    $this->skipped[] = '[SKU không hợp lệ]';
                    continue;
                }

                // Xử lý Pack SKU nếu có
                if (preg_match('/(.+)_Pack(\d+)$/', $skuCode, $packMatches)) {
                    $baseSku   = $packMatches[1];
                    $multiplier = intval($packMatches[2]);

                    if (isset($this->skuMap[$baseSku])) {
                        $cost     = $this->skuMap[$baseSku]['cost'] * $multiplier;
                        $quantity = $this->skuMap[$baseSku]['quantity'];
                    } else {
                        $this->skipped[] = $skuCode . ' - Không tìm thấy SKU gốc cho Pack';
                        continue;
                    }
                } else {
                    $costStr = str_replace(',', '.', $rawCost);

                    if (!is_numeric($costStr)) {
                        $this->skipped[] = $skuCode . ' - Base cost không hợp lệ';
                        continue;
                    }

                    if (!is_numeric($rawQuantity) || $rawQuantity === '#N/A') {
                        $this->skipped[] = $skuCode . ' - Quantity không hợp lệ';
                        continue;
                    }

                    if (empty($skuName)) {
                        $this->skipped[] = $skuCode . ' - Tên mặt hàng trống';
                        continue;
                    }
                    
                    $cost     = floatval($costStr);
                    $quantity = floatval($rawQuantity);
                }

                if ($tierName && !Tier::where('tier', $tierName)->exists()) {

                    $this->skipped[] = $skuCode . " - Tier \"$tierName\" không tồn tại";
                    continue;
                }
                // Tìm SKU
                $sku = Sku::where('sku', $skuCode)->first();

                if ($sku) {
                    $sku->update([
                        'cost'      => $cost,
                        'name'      => $skuName,
                        'quantity'  => $quantity,
                        'tier' => $tierName,
                    ]);
                    $this->skuService->updateOrdersBySku($sku);
                    $this->updated[] = $skuCode;
                } else {
                    $newSku = Sku::create([
                        'sku'       => strtolower($skuCode),
                        'cost'      => $cost,
                        'name'      => $skuName,
                        'quantity'  => $quantity,
                        'tier' => $tierName,
                    ]);
                    $this->skuService->updateOrdersBySku($newSku);
                    $this->created[] = $skuCode;
                }

            } catch (\Throwable $e) {
                $this->skipped[] = $skuCode . ' - Lỗi không xác định';
                continue;
            }
        }
    }
}
