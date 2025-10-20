<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ProductListImport;

class ProductListController extends Controller
{
    /**
     * Import file Excel vào bảng product_mapping
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            Excel::import(new ProductListImport, $request->file('file'));

            return redirect()->back()->with('success', 'Import product mapping success!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Lỗi import: ' . $e->getMessage());
        }
    }
}
