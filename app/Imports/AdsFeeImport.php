<?php

namespace App\Imports;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;
use App\Models\User;
use App\Models\AdsFee;
use PhpOffice\PhpSpreadsheet\RichText\RichText;

class AdsFeeImport
{
    public function importAdsFeeFromExcel($filePath)
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $headerRow = 1;
        // Lấy danh sách ngày (hàng đầu tiên)
        $dates = [];
        foreach ($sheet->rangeToArray("A{$headerRow}:{$highestColumn}{$headerRow}", null, true, true, true)[1] as $col => $val) {
            if ($col === 'A')
                continue; // Cột đầu là 'Seller'

            // Nếu là dạng text như "May-1", "5/1", xử lý thủ công:
            try {
                $carbonDate = Carbon::parse($val);
                $dates[$col] = $carbonDate->format('Y-m-d');
            } catch (\Exception $e) {
                $dates[$col] = null;
            }

        }

        // Duyệt từng dòng data
        for ($row = 1; $row <= $highestRow; $row++) {
            $sellerName = trim($sheet->getCell("A{$row}")->getValue());
            if (empty($sellerName))
                continue;

            $user = User::where('name', 'LIKE', $sellerName)->first();
            if (!$user)
                continue;

            foreach ($dates as $col => $date) {
                if (!$date)
                    continue;

                $ads = $sheet->getCell($col . $row)->getCalculatedValue(); // Lấy giá trị tính toán (thay vì công thức)
                if (!is_numeric($ads))
                    continue;

                AdsFee::updateOrCreate(
                    ['user_id' => $user->id, 'date' => $date],
                    ['ads' => $ads]
                );
            }
        }

        return "Import completed.";
    }

}
