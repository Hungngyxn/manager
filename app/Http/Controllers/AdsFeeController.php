<?php

namespace App\Http\Controllers;

use App\Imports\AdsFeeImport;
use App\Models\AdsFee;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AdsFeeController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date_format:Y-m-d',
            'ads' => 'required|numeric',
        ]);
        try {
            AdsFee::updateOrCreate(
                ['user_id' => $request->user_id, 'date' => $request->date],
                ['ads' => $request->ads]
            );

            return redirect()->route('report.index')->with('status', 'Cập nhật chi phí ads thành công.');
        } catch (\Exception $e) {
            return redirect()->route('report.index')->with('error', $e->getMessage());
        }
    }


    public function fetch(Request $request)
    {
        $ads = AdsFee::where('user_id', $request->user_id)
            ->where('date', $request->date)
            ->value('ads');

        return response()->json(['ads' => $ads]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'ads_excel' => 'required|file|mimes:xlsx,xls'
        ]);

        try {
            $importer = new AdsFeeImport();
            $filePath = $request->file('ads_excel')->getPathname();

            $message = $importer->importAdsFeeFromExcel($filePath);

            $minDate = AdsFee::min('date');
            $maxDate = AdsFee::max('date');
            $allUserIds = AdsFee::select('user_id')->distinct()->pluck('user_id');

            foreach ($allUserIds as $userId) {
                ReportController::calculateReportForUser($userId, null, $minDate, $maxDate);
            }

            return redirect()->route('report.index', [
                'date_range' => $minDate . ' to ' . $maxDate
            ])->with('status', $message);

        } catch (\Throwable $e) {
            return back()->with('error', 'Import thất bại: ' . $e->getMessage());
        }
    }

}
