<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaksi;
use App\Models\Menu;
use App\Models\MenuCabangStok;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DokuController extends Controller
{
    public function callback(Request $request)
    {
        $rawBody = $request->getContent();
        $data = json_decode($rawBody, true);

        Log::info('===== DOKU CALLBACK DITERIMA =====', [
            'method' => $request->method(),
            'path' => $request->getPathInfo(),
            'invoice' => data_get($data, 'order.invoice_number'),
            'status' => data_get($data, 'transaction.status'),
            'has_client_id' => $request->hasHeader('Client-Id'),
            'has_request_id' => $request->hasHeader('Request-Id'),
            'has_timestamp' => $request->hasHeader('Request-Timestamp'),
            'has_signature' => $request->hasHeader('Signature'),
        ]);

        // 1. Ambil konfigurasi DOKU
        $clientId = config('services.doku.client_id');
        $secretKey = config('services.doku.secret_key');

        // 2. Ambil header callback
        $receivedClientId = $request->header('Client-Id');
        $requestId = $request->header('Request-Id');
        $timestamp = $request->header('Request-Timestamp');
        $receivedSignature = $request->header('Signature');

        if (!$clientId || !$secretKey) {
            Log::error('Konfigurasi DOKU belum lengkap.');

            return response()->json([
                'message' => 'DOKU configuration error'
            ], 500);
        }

        if (
            !$receivedClientId ||
            !$requestId ||
            !$timestamp ||
            !$receivedSignature
        ) {
            Log::warning('Header callback DOKU tidak lengkap.');

            return response()->json([
                'message' => 'Missing DOKU headers'
            ], 400);
        }

        // 3. Validasi Client ID
        if (!hash_equals((string) $clientId, (string) $receivedClientId)) {
            Log::warning('DOKU Client ID tidak cocok.');

            return response()->json([
                'message' => 'Invalid Client ID'
            ], 401);
        }

        // 4. Hitung Digest dari raw JSON body
        $rawBody = $request->getContent();

        $digest = base64_encode(
            hash('sha256', $rawBody, true)
        );

        // 5. Bentuk komponen signature Non-SNAP
        $requestTarget = $request->getPathInfo();

        $signatureComponent =
            "Client-Id:$receivedClientId\n" .
            "Request-Id:$requestId\n" .
            "Request-Timestamp:$timestamp\n" .
            "Request-Target:$requestTarget\n" .
            "Digest:$digest";

        // 6. Hitung signature yang seharusnya diterima
        $expectedSignature = 'HMACSHA256=' . base64_encode(
            hash_hmac(
                'sha256',
                $signatureComponent,
                $secretKey,
                true
            )
        );

        // 7. Cocokkan signature
        if (!hash_equals($expectedSignature, $receivedSignature)) {
            Log::warning('Signature callback DOKU tidak valid.', [
                'request_id' => $requestId,
                'request_target' => $requestTarget,
            ]);

            return response()->json([
                'message' => 'Invalid signature'
            ], 401);
        }

        // 8. Baca data callback setelah signature valid
        $data = json_decode($rawBody, true);

        if (!is_array($data)) {
            return response()->json([
                'message' => 'Invalid JSON'
            ], 400);
        }

        $invoice = $data['order']['invoice_number'] ?? null;
        $amount = $data['order']['amount'] ?? null;
        $status = strtoupper(
            (string) ($data['transaction']['status'] ?? '')
        );

        if (!$invoice) {
            return response()->json([
                'message' => 'Invoice not found'
            ], 400);
        }

        Log::info('Callback DOKU terverifikasi.', [
            'invoice' => $invoice,
            'status' => $status,
            'request_id' => $requestId,
        ]);

        // 9. Jangan mengubah transaksi jika bukan SUCCESS
        if ($status !== 'SUCCESS') {
            return response()->json([
                'message' => 'Notification received'
            ], 200);
        }

        // 10. Validasi nominal
        if (!is_numeric($amount)) {
            return response()->json([
                'message' => 'Invalid payment amount'
            ], 400);
        }

        try {
            DB::transaction(function () use ($invoice, $amount) {

                // Ambil semua item dalam invoice
                $transaksis = Transaksi::where('invoice', $invoice)
                    ->lockForUpdate()
                    ->get();

                if ($transaksis->isEmpty()) {
                    throw new \RuntimeException(
                        'Invoice tidak ditemukan: ' . $invoice
                    );
                }

                // Pastikan seluruh item merupakan transaksi QRIS
                foreach ($transaksis as $trx) {
                    if ($trx->metode_pembayaran !== 'qris') {
                        throw new \RuntimeException(
                            'Metode pembayaran bukan QRIS.'
                        );
                    }
                }

                // Idempotensi: callback yang sudah diproses tidak mengurangi stok lagi
                if ($transaksis->every(
                    fn ($trx) => $trx->status === 'success'
                )) {
                    Log::info('Callback duplikat diabaikan.', [
                        'invoice' => $invoice,
                    ]);

                    return;
                }

                // Tolak jika status transaksi bercampur
                if ($transaksis->contains(
                    fn ($trx) => $trx->status !== 'pending'
                )) {
                    throw new \RuntimeException(
                        'Status item dalam invoice tidak konsisten.'
                    );
                }

                // Cocokkan nominal DOKU dengan total invoice
                $expectedAmount = (float) $transaksis->sum('total');

                if (
                    round((float) $amount, 2) !==
                    round($expectedAmount, 2)
                ) {
                    throw new \RuntimeException(
                        'Nominal pembayaran tidak cocok. ' .
                        'DOKU: ' . $amount .
                        ', Database: ' . $expectedAmount
                    );
                }

                // Ambil dan kunci seluruh stok terlebih dahulu
                $stokRows = [];

                foreach ($transaksis as $trx) {

                    if (!$trx->cabang_id) {
                        throw new \RuntimeException(
                            'Cabang transaksi tidak ditemukan: ' . $trx->id
                        );
                    }

                    $stok = MenuCabangStok::where(
                        'menu_id',
                        $trx->menu_id
                    )
                        ->where('cabang_id', $trx->cabang_id)
                        ->lockForUpdate()
                        ->first();

                    if (!$stok || $stok->stok < $trx->qty) {
                        throw new \RuntimeException(
                            'Stok tidak mencukupi untuk transaksi ID ' .
                            $trx->id
                        );
                    }

                    $stokRows[] = [
                        'stok' => $stok,
                        'trx' => $trx,
                    ];
                }

                // Semua stok tersedia, baru kurangi stok
                foreach ($stokRows as $row) {

                    $row['stok']->decrement(
                        'stok',
                        $row['trx']->qty
                    );

                    $row['trx']->update([
                        'status' => 'success'
                    ]);
                }

                Log::info('Pembayaran QRIS berhasil diproses.', [
                    'invoice' => $invoice,
                    'amount' => $amount,
                ]);
            });

            return response()->json([
                'message' => 'success'
            ], 200);

        } catch (Throwable $e) {

            Log::error('ERROR CALLBACK DOKU', [
                'invoice' => $invoice,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Callback processing failed'
            ], 500);
        }
    }
}
