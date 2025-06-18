<?php

namespace App\Http\Controllers;

use App\Models\AdsFee;
use Illuminate\Http\Request;

class AdsFeeController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date_format:Y-m-d',
            'ads' => 'required|numeric|min:0',
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

}
