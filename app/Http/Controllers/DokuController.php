<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaksi;
use App\Models\Menu;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DokuController extends Controller
{
    public function callback(Request $request)
    {
        $data = $request->all();

        // =====================================
        // SIMPAN RAW CALLBACK
        // =====================================
        file_put_contents(
            storage_path('logs/doku.log'),
            json_encode($data) . PHP_EOL,
            FILE_APPEND
        );

        Log::info('===== DOKU CALLBACK MASUK =====');
        Log::info($data);

        // =====================================
        // AMBIL DATA
        // =====================================
        $invoice = $data['order']['invoice_number'] ?? null;
        $status  = $data['transaction']['status'] ?? null;

        Log::info('DOKU INVOICE: ' . $invoice);
        Log::info('DOKU STATUS: ' . $status);

        // =====================================
        // VALIDASI
        // =====================================
        if (!$invoice) {
            Log::warning('INVOICE KOSONG');

            return response()->json([
                'message' => 'invoice not found'
            ], 400);
        }

        if (strtoupper($status) !== 'SUCCESS') {
            Log::warning('STATUS BUKAN SUCCESS: ' . $status);

            return response()->json([
                'message' => 'payment not success'
            ]);
        }

        DB::beginTransaction();

        try {

            // =====================================
            // CARI TRANSAKSI
            // =====================================
            $transaksis = Transaksi::where('invoice', $invoice)
                ->where('status', 'pending')
                ->get();

            Log::info('TRANSAKSI DITEMUKAN: ' . $transaksis->count());

            if ($transaksis->isEmpty()) {

                Log::warning(
                    'TRANSAKSI TIDAK DITEMUKAN ATAU SUDAH DIPROSES. INVOICE: '
                    . $invoice
                );

                DB::rollBack();

                return response()->json([
                    'message' => 'already processed'
                ]);
            }

            foreach ($transaksis as $trx) {

                Log::info('PROSES TRANSAKSI ID: ' . $trx->id);

                $menu = Menu::find($trx->menu_id);

                if (!$menu) {
                    Log::warning(
                        'MENU TIDAK DITEMUKAN. MENU ID: '
                        . $trx->menu_id
                    );

                    continue;
                }

                // =====================================
                // KURANGI STOK CABANG
                // =====================================
                $isNasi = stripos($menu->nama_menu, 'nasi') !== false;
                $branchId = $trx->cabang_id ?? $menu->cabang_id;
                $branchStok = $menu->getStokForCabang($branchId);

                if ($isNasi || $branchStok >= $trx->qty) {

                    $stokAwal = $branchStok;

                    $stokAkhir = $menu->decrementStokCabang($branchId, $trx->qty);

                    Log::info(
                        'STOK CABANG BERHASIL DIKURANGI | CABANG: ' . $branchId . ' | MENU: '
                        . $menu->nama_menu
                        . ' | AWAL: '
                        . $stokAwal
                        . ' | AKHIR: '
                        . $stokAkhir
                    );
                } else {

                    Log::warning(
                        'STOK CABANG TIDAK MENCUKUPI | CABANG: ' . $branchId . ' | MENU: '
                        . $menu->nama_menu
                    );
                }

                // =====================================
                // UPDATE STATUS
                // =====================================
                $trx->update([
                    'status' => 'success'
                ]);

                Log::info(
                    'STATUS TRANSAKSI BERHASIL DIUBAH KE SUCCESS. ID: '
                    . $trx->id
                );
            }

            DB::commit();

            Log::info(
                'CALLBACK BERHASIL DIPROSES. INVOICE: '
                . $invoice
            );

            return response()->json([
                'message' => 'success'
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('ERROR CALLBACK DOKU');
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function handleNotification(Request $request)
    {
        Log::info('===== DOKU NOTIFICATION =====');
        Log::info($request->all());

        return response()->json([
            'status' => 'SUCCESS'
        ]);
    }
}