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
            if ($row->filter()->isEmpty())
                continue;

            try {
                $skuCode = trim($row['sku'] ?? '');
                $product_name = $skuCode;
                $rawCost = $row['base_cost'] ?? null;
                $rawQuantity = $row['so_luong_ton_kho'] ?? null;
                $skuName = $row['mat_hang'] ?? null;
                $price = $row['gia_ban'] ?? null;
                $freeshipping = $row['freeship'] ?? null;
                $tierRaw = trim($row['tier'] ?? '') ?: null;

                if (empty($skuCode)) {
                    $this->skipped[] = '[Invalid SKU]';
                    continue;
                }

                // ✅ Set default quantity if it's #N/A or invalid
                if ($rawQuantity === '#N/A' || !is_numeric($rawQuantity)) {
                    $quantity = 1000;
                } else {
                    $quantity = floatval($rawQuantity);
                }

                // ✅ Normalize cost
                $costStr = str_replace(',', '.', $rawCost);
                if (!is_numeric($costStr)) {
                    $this->skipped[] = $skuCode . ' - Invalid base cost';
                    continue;
                }
                $cost = floatval($costStr);

                // ✅ Handle special tier case for "SP Research mới"
                if (strtolower(trim($tierRaw)) === 'sp research mới' || strtolower(trim($tierRaw)) === 'tpcn') {
                    $tierName = 'Tier 1';
                } else {
                    $tierName = trim(preg_replace('/\s*\(.*/', '', $tierRaw));
                }

                // Check if tier exists if not empty
                if ($tierName && !Tier::where('tier', $tierName)->exists()) {
                    $this->skipped[] = $skuCode . " - Tier \"$tierName\" does not exist";
                    continue;
                }

                if (empty($skuName)) {
                    $this->skipped[] = $skuCode . ' - Empty product name';
                    continue;
                }

                // ✅ Find and update or create SKU
                $sku = Sku::where('sku', $skuCode)->first();

                if ($sku) {
                    $sku->update([
                        'cost' => $cost,
                        'name' => $skuName,
                        'quantity' => $quantity,
                        'product_name' => $product_name,
                        'price' => $price,
                        'freeshipping' => !empty($freeshipping) ? 1 : 0,
                        'tier' => $tierName,
                    ]);
                    $this->skuService->updateOrdersBySku($sku);
                    $this->updated[] = $skuCode;
                } else {
                    $newSku = Sku::create([
                        'sku' => strtolower($skuCode),
                        'cost' => $cost,
                        'name' => $skuName,
                        'product_name' => $product_name,
                        'quantity' => $quantity,
                        'price' => $price,
                        'freeshipping' => !empty($freeshipping) ? 1 : 0,
                        'tier' => $tierName,
                    ]);
                    $this->skuService->updateOrdersBySku($newSku);
                    $this->created[] = $skuCode;
                }

            } catch (\Throwable $e) {
                $this->skipped[] = $skuCode . ' - Unknown error';
                continue;
            }
        }
    }
}
