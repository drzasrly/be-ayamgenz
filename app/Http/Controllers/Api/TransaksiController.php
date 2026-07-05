<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaksi;
use App\Models\Menu;
use App\Models\Cart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TransaksiController extends Controller
{
    /**
     * Display transactions list for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Transaksi::with('menu')->latest();

        if ($user->role !== 'admin') {
            $query->where('cabang_id', $user->cabang_id);
        }

        $transaksis = $query->get();

        return response()->json([
            'success' => true,
            'data' => $transaksis
        ]);
    }

    /**
     * Process checkout transaction.
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'metode_pembayaran' => 'required|in:cash,qris',
            'items' => 'nullable|array',
            'items.*.menu_id' => 'required_with:items|exists:menu,id',
            'items.*.qty' => 'required_with:items|integer|min:1',
        ]);

        $user = $request->user();
        $items = [];

        // 1. Dapatkan item belanjaan, baik dari payload JSON maupun dari tabel database Cart
        if ($request->has('items') && !empty($request->items)) {
            foreach ($request->items as $item) {
                $menu = Menu::findOrFail($item['menu_id']);
                
                // Pastikan kasir tidak memesan menu dari cabang lain
                if ($user->role !== 'admin' && $menu->cabang_id !== $user->cabang_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Menu ' . $menu->nama_menu . ' bukan milik cabang Anda.'
                    ], 403);
                }

                $items[] = [
                    'menu' => $menu,
                    'qty' => $item['qty']
                ];
            }
        } else {
            // Jika payload items kosong, cari dari tabel cart berdasarkan user_id
            $cartItems = Cart::with('menu')->where('user_id', $user->id)->get();

            if ($cartItems->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Keranjang belanja Anda kosong.'
                ], 400);
            }

            foreach ($cartItems as $cartItem) {
                if (!$cartItem->menu) continue;
                
                $items[] = [
                    'menu' => $cartItem->menu,
                    'qty' => $cartItem->qty
                ];
            }
        }

        // Hitung total harga
        $total = collect($items)->sum(fn($i) => $i['menu']->harga * $i['qty']);
        $invoice = 'INV-' . time();

        // 2. Proses transaksi Cash
        if ($request->metode_pembayaran === 'cash') {
            DB::beginTransaction();
            try {
                foreach ($items as $item) {
                    $menu = $item['menu'];

                    // Kurangi stok menu (nasi selalu tersedia tetapi tetap dikurangi)
                    $isNasi = (stripos($menu->nama_menu, 'nasi') !== false);
                    if ($isNasi || $menu->stok >= $item['qty']) {
                        $menu->decrement('stok', $item['qty']);
                    } else {
                        throw new \Exception('Stok menu ' . $menu->nama_menu . ' tidak mencukupi.');
                    }

                    Transaksi::create([
                        'invoice' => $invoice,
                        'menu_id' => $menu->id,
                        'cabang_id' => $menu->cabang_id,
                        'qty' => $item['qty'],
                        'total' => $menu->harga * $item['qty'],
                        'tanggal' => now(),
                        'metode_pembayaran' => 'cash',
                        'status' => 'success'
                    ]);
                }

                // Kosongkan keranjang di database jika checkout berhasil
                Cart::where('user_id', $user->id)->delete();

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Transaksi Cash berhasil disimpan.',
                    'data' => [
                        'invoice' => $invoice,
                        'total' => $total,
                        'status' => 'success',
                        'metode_pembayaran' => 'cash'
                    ]
                ], 201);

            } catch (\Exception $e) {
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memproses transaksi cash: ' . $e->getMessage()
                ], 500);
            }
        }

        // 3. Proses transaksi QRIS (Integrasi DOKU)
        if ($request->metode_pembayaran === 'qris') {
            DB::beginTransaction();
            try {
                // Simpan transaksi status pending terlebih dahulu
                foreach ($items as $item) {
                    $menu = $item['menu'];

                    // Validasi stok sebelum checkout QRIS (nasi selalu tersedia)
                    $isNasi = (stripos($menu->nama_menu, 'nasi') !== false);
                    if (!$isNasi && $menu->stok < $item['qty']) {
                        throw new \Exception('Stok menu ' . $menu->nama_menu . ' tidak mencukupi.');
                    }

                    Transaksi::create([
                        'invoice' => $invoice,
                        'menu_id' => $menu->id,
                        'cabang_id' => $menu->cabang_id,
                        'qty' => $item['qty'],
                        'total' => $menu->harga * $item['qty'],
                        'tanggal' => now(),
                        'metode_pembayaran' => 'qris',
                        'status' => 'pending'
                    ]);
                }

                // Kosongkan keranjang di database
                Cart::where('user_id', $user->id)->delete();

                DB::commit();

                // DOKU API Request
                $clientId = config('services.doku.client_id');
                $secretKey = config('services.doku.secret_key');
                $baseUrl = config('services.doku.base_url');
                $timestamp = gmdate('Y-m-d\TH:i:s\Z');
                $requestId = uniqid();

                $body = [
                    "order" => [
                        "invoice_number" => $invoice,
                        "amount" => (int) $total,
                        "callback_url" => url('/kasir') // Tautan callback default
                    ],
                    "payment" => [
                        "payment_due_date" => 60
                    ],
                    "customer" => [
                        "name" => $user->name,
                        "email" => $user->email
                    ]
                ];

                $jsonBody = json_encode($body);
                $digest = base64_encode(hash('sha256', $jsonBody, true));
                $requestTarget = '/checkout/v1/payment';
                
                $signaturePayload = "Client-Id:$clientId\nRequest-Id:$requestId\nRequest-Timestamp:$timestamp\nRequest-Target:$requestTarget\nDigest:$digest";
                $signature = base64_encode(hash_hmac('sha256', $signaturePayload, $secretKey, true));

                $response = Http::withHeaders([
                    'Client-Id' => $clientId,
                    'Request-Id' => $requestId,
                    'Request-Timestamp' => $timestamp,
                    'Signature' => "HMACSHA256=$signature",
                    'Digest' => $digest,
                    'Content-Type' => 'application/json'
                ])->post($baseUrl . $requestTarget, $body);

                if ($response->failed()) {
                    Log::error('DOKU API Error (API): ' . $response->body());
                    return response()->json([
                        'success' => false,
                        'message' => 'Gagal terhubung dengan Payment Gateway DOKU.',
                        'error_detail' => $response->json()
                    ], 502);
                }

                $paymentUrl = $response['response']['payment']['url'] ?? null;

                return response()->json([
                    'success' => true,
                    'message' => 'Transaksi QRIS berhasil dibuat. Silakan selesaikan pembayaran.',
                    'data' => [
                        'invoice' => $invoice,
                        'total' => $total,
                        'status' => 'pending',
                        'metode_pembayaran' => 'qris',
                        'payment_url' => $paymentUrl
                    ]
                ], 201);

            } catch (\Exception $e) {
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memproses transaksi QRIS: ' . $e->getMessage()
                ], 500);
            }
        }
    }
}
