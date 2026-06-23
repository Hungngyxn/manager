<?php

namespace App\Http\Controllers;

use App\Imports\SkuOrderImport;
use App\Models\SkuOrder;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class SkuOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = SkuOrder::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('asin', 'like', "%$search%")
                    ->orWhere('warehouse_name', 'like', "%$search%")
                    ->orWhere('name', 'like', "%$search%");
            });
        }

        $skuOrders = $query->orderBy('id', 'desc')->paginate(20);

        return view('pages.sku_order.index', compact('skuOrders'));
    }


    public function create()
    {
        return view('pages.sku_order.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'sku' => 'required|unique:sku_orders,sku',
            'asin' => 'nullable|string',
            'name' => 'nullable|string',
            'warehouse_name' => 'nullable|string',
            'quantity_per_pack' => 'required|integer|min:1',
        ]);

        SkuOrder::create($request->all());

        return redirect()->route('sku-orders.index')
            ->with('status', 'SKU Order created successfully.');
    }

    public function edit(SkuOrder $skuOrder)
    {
        return view('pages.sku_order.edit', compact('skuOrder'));
    }

    public function update(Request $request, SkuOrder $skuOrder)
    {
        $request->validate([
            'sku' => 'required|unique:sku_orders,sku,' . $skuOrder->id,
            'asin' => 'nullable|string',
            'name' => 'nullable|string',
            'warehouse_name' => 'nullable|string',
            'quantity_per_pack' => 'required|integer|min:1',
        ]);

        $skuOrder->update($request->all());

        return redirect()->route('sku-orders.index')
            ->with('status', 'SKU Order updated successfully.');
    }

    public function destroy(SkuOrder $skuOrder)
    {
        $skuOrder->delete();

        return redirect()->route('sku-orders.index')
            ->with('status', 'SKU Order deleted successfully.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        try {
            Excel::import(new SkuOrderImport, $request->file('file'));
            return redirect()->route('sku-orders.index')->with('status', 'Import successful.');
        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
