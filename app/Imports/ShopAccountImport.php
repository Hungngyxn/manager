<?php

namespace App\Imports;

use App\Models\ShopAccount;
use App\Models\SellerHasShop;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ShopAccountImport implements ToCollection, WithHeadingRow
{
    public $created = [];
    public $skipped = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $excelRow = $index + 2;

            $email = trim($row['email'] ?? '');
            $userName = trim($row['user_name'] ?? '');
            $thangRegRaw = $row['thang_reg'] ?? '';
            $tuoiAcc = trim($row['tuoi_acc'] ?? '');
            $status = trim($row['status'] ?? 'active');

            if (empty($email) || empty($thangRegRaw) || empty($tuoiAcc)) {
                $this->skipped[] = "Row $excelRow: missing required fields";
                continue;
            }

            // Xử lý định dạng ngày từ Excel (có thể là số hoặc chuỗi)
            if (is_numeric($thangRegRaw)) {
                try {
                    $thangReg = Carbon::instance(ExcelDate::excelToDateTimeObject($thangRegRaw))
                        ->format('Y-m-d');
                } catch (\Exception $e) {
                    $this->skipped[] = "Row $excelRow: invalid Excel numeric date '$thangRegRaw'";
                    continue;
                }
            } else {
                try {
                    $thangReg = Carbon::createFromFormat('M-y', trim($thangRegRaw))
                        ->format('Y-m-d');
                } catch (\Exception $e) {
                    $this->skipped[] = "Row $excelRow: invalid date string '$thangRegRaw'";
                    continue;
                }
            }

            // Tìm user nếu có
            $user = User::where('name', $userName)->first();

            // Tạo hoặc cập nhật tài khoản
            $shopAccount = ShopAccount::updateOrCreate(
                ['email' => $email],
                [
                    'thang_reg' => $thangReg,
                    'tuoi_acc' => $tuoiAcc,
                    'status' => $status ?: 'active',
                    'user_id' => $user?->id,
                ]
            );

            // Nếu có bản ghi trong seller_has_shop, cập nhật user_id
            $sellerShop = SellerHasShop::where('email', $email)->first();
            if ($sellerShop) {
                $sellerShop->update([
                    'user_id' => $user?->id,
                ]);
            }

            $this->created[] = "Row $excelRow: $email";
        }
    }
}
