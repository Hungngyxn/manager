<?php

namespace App\Http\Controllers;

use App\Imports\SkuImport;
use App\Models\Order;
use App\Models\Sku;
use App\Models\Log;
use App\Models\Tier;
use Illuminate\Http\Request;
use App\Services\SkuService;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class SkuController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('perPage', 10);
        $query = Sku::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('sku', 'like', "%$search%")
                    ->orWhere('name', 'like', "%$search%");
            });
        }

        if ($tier = $request->input('tier')) {
            $query->where('tier', $tier);
        }

        $tiers = Tier::orderBy('tier')->get();
        $skus = $query->paginate($perPage)->appends($request->only(['search', 'tier']));

        return view('pages.sku.index', compact('skus', 'tiers'));
    }

    public function create()
    {
        $tiers = Tier::orderBy('tier')->get();
        return view('pages.sku.create', compact('tiers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'sku' => 'required|max:255',
            'cost' => 'required|numeric|min:0',
            'name' => 'required|max:255',
            'quantity' => 'required|numeric|min:0',
            'tier' => 'required',
        ]);

        DB::beginTransaction();

        try {
            $skuData = $request->only('sku', 'name', 'cost', 'quantity', 'tier');
            $skuData['sku'] = trim(strtolower($skuData['sku']));

            $sku = Sku::updateOrCreate(
                ['sku' => $skuData['sku']],
                $skuData
            );

            app(SkuService::class)->updateOrdersBySku($sku);

            DB::commit();

            return redirect()->route('sku.index')->with('success', 'SKU saved and related orders updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('sku.index')->with('error', 'Failed to save SKU: ' . $e->getMessage());
        }
    }

    public function edit(Sku $sku)
    {
        $tiers = Tier::orderBy('tier')->get();

        return view('pages.sku.edit', compact('sku', 'tiers'));
    }

    public function update(Request $request, Sku $sku)
    {
        $request->validate([
            'sku' => 'required|max:255|unique:skus,sku,' . $sku->id,
            'cost' => 'required|numeric|min:0',
            'name' => 'required|max:255',
            'quantity' => 'required|numeric|min:0',
            'tier' => 'required',
        ]);

        $updateScope = $request->input('update_scope', 'all');

        DB::beginTransaction();

        try {
            $sku->update($request->only('sku', 'name', 'cost', 'quantity', 'tier'));

            app(SkuService::class)->updateOrdersBySku($sku, $updateScope);

            DB::commit();

            return redirect()->route('sku.index')->with('success', 'SKU and related orders updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('sku.index')->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    public function destroy(Sku $sku)
    {
        $sku->delete();
        
        return redirect()->route('sku.index')->with('success', 'SKU deleted successfully.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
        ]);

        $import = new SkuImport();

        try {
            Excel::import($import, $request->file('file'));

            $messages = [];

            if (count($import->created)) {
                $messages[] = '✅ Created: ' . implode(', ', $import->created);
            }

            if (count($import->updated)) {
                $messages[] = '🔁 Updated: ' . implode(', ', $import->updated);
            }

            if (count($import->skipped)) {
                $skippedLines = implode('<br>• ', $import->skipped);
                $messages[] = '⚠️ Skipped:<br>• ' . $skippedLines;
            }

            return redirect()->route('sku.index')->with('status', implode('<br>', $messages));
        } catch (\Exception $e) {
            $errorInfo = [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ];

            return redirect()->route('sku.index')->with('error', 'Import failed: ' . json_encode($errorInfo));
        }
    }
}
