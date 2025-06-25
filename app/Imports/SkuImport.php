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

    public function __construct()
    {
        $this->skuService = new SkuService();
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            if ($row->filter()->isEmpty()) continue;

            try {
                $skuCode     = trim($row['sku'] ?? '');
                $rawCost     = $row['base_cost'] ?? null;
                $rawQuantity = $row['so_luong_ton_kho'] ?? null;
                $skuName     = $row['mat_hang'] ?? null;
                $tierRaw     = trim($row['tier'] ?? '') ?: null;

                if (empty($skuCode)) {
                    $this->skipped[] = '[SKU không hợp lệ]';
                    continue;
                }

                // ✅ Gán số lượng mặc định nếu là #N/A
                if ($rawQuantity === '#N/A' || !is_numeric($rawQuantity)) {
                    $quantity = 1000;
                } else {
                    $quantity = floatval($rawQuantity);
                }

                // ✅ Chuẩn hoá cost
                $costStr = str_replace(',', '.', $rawCost);
                if (!is_numeric($costStr)) {
                    $this->skipped[] = $skuCode . ' - Base cost không hợp lệ';
                    continue;
                }
                $cost = floatval($costStr);

                // ✅ Gán tier đặc biệt nếu là SP Research mới
                if (strtolower(trim($tierRaw)) === 'sp research mới') {
                    $tierName = 'Tier 3';
                } else {
                    $tierName = trim(preg_replace('/\s*\(.*/', '', $tierRaw));
                }

                // Kiểm tra tier tồn tại nếu không rỗng
                if ($tierName && !Tier::where('tier', $tierName)->exists()) {
                    $this->skipped[] = $skuCode . " - Tier \"$tierName\" không tồn tại";
                    continue;
                }

                if (empty($skuName)) {
                    $this->skipped[] = $skuCode . ' - Tên mặt hàng trống';
                    continue;
                }

                // ✅ Tìm và cập nhật hoặc tạo mới SKU
                $sku = Sku::where('sku', $skuCode)->first();

                if ($sku) {
                    $sku->update([
                        'cost'     => $cost,
                        'name'     => $skuName,
                        'quantity' => $quantity,
                        'tier'     => $tierName,
                    ]);
                    $this->skuService->updateOrdersBySku($sku);
                    $this->updated[] = $skuCode;
                } else {
                    $newSku = Sku::create([
                        'sku'      => strtolower($skuCode),
                        'cost'     => $cost,
                        'name'     => $skuName,
                        'quantity' => $quantity,
                        'tier'     => $tierName,
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
