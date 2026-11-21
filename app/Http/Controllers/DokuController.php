<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaksi;
use App\Models\Menu;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\MenuCabangStok;

class DokuController extends Controller
{
    
public function callback(Request $request)
{
    $data = $request->all();

    Log::info('===== DOKU CALLBACK MASUK =====', $data);

    $invoice = $data['order']['invoice_number'] ?? null;
    $status = $data['transaction']['status'] ?? null;

    if (!$invoice) {
        return response()->json([
            'message' => 'invoice not found'
        ], 400);
    }

    if (strtoupper((string) $status) !== 'SUCCESS') {
        return response()->json([
            'message' => 'payment not success'
        ]);
    }

    try {
        DB::transaction(function () use ($invoice) {

            $transaksis = Transaksi::where('invoice', $invoice)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->get();

            if ($transaksis->isEmpty()) {
                Log::info('Callback sudah diproses: ' . $invoice);
                return;
            }

            foreach ($transaksis as $trx) {

                if (!$trx->cabang_id) {
                    throw new \Exception(
                        'Cabang transaksi tidak ditemukan. ID transaksi: ' . $trx->id
                    );
                }

                $menu = Menu::findOrFail($trx->menu_id);

                $stok = MenuCabangStok::where('menu_id', $menu->id)
                    ->where('cabang_id', $trx->cabang_id)
                    ->lockForUpdate()
                    ->first();

                if (!$stok || $stok->stok < $trx->qty) {
                    throw new \Exception(
                        'Stok ' . $menu->nama_menu .
                        ' tidak mencukupi saat callback. Stok tersedia: ' .
                        ($stok->stok ?? 0)
                    );
                }

                $stok->decrement('stok', $trx->qty);

                $trx->update([
                    'status' => 'success'
                ]);

                Log::info(
                    'Stok QRIS dikurangi | Invoice: ' . $invoice .
                    ' | Menu: ' . $menu->nama_menu .
                    ' | Cabang: ' . $trx->cabang_id .
                    ' | Qty: ' . $trx->qty
                );
            }
        });

        return response()->json([
            'message' => 'success'
        ]);

    } catch (\Exception $e) {

        Log::error('ERROR CALLBACK DOKU: ' . $e->getMessage());

        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}

}