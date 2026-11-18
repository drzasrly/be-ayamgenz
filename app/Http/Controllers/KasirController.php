<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaksi;
use App\Models\Cabang;
use App\Models\Menu;
use App\Models\Cart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class KasirController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $query = Transaksi::with('menu')->latest();
        
        if ($user->role != 'admin') {
            $query->where('cabang_id', $user->cabang_id);
        }

        $transaksi = $query->get();
        return view('kasir.index', compact('transaksi'));
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        $cabang = Cabang::all();

        if ($user->role == 'admin') {
            if ($request->has('cabang_id')) {
                session(['active_pos_cabang_id' => $request->cabang_id]);
            }
            $targetCabang = session('active_pos_cabang_id') ?: ($user->cabang_id ?: $cabang->first()?->id);
            $menu = Menu::where(function($q) use ($targetCabang) {
                $q->where('cabang_id', $targetCabang)
                  ->orWhereNull('cabang_id');
            })->with(['cabang', 'cabangStoks'])->get();
        } else {
            $targetCabang = $user->cabang_id;
            $menu = Menu::where(function($q) use ($user) {
                $q->where('cabang_id', $user->cabang_id)
                  ->orWhereNull('cabang_id');
            })->with(['cabang', 'cabangStoks'])->get();
        }

        // Tampilkan stok sesuai cabang yang aktif/sedang membuka kasir
        if ($targetCabang) {
            foreach ($menu as $m) {
                $m->stok = $m->getStokForCabang($targetCabang);
            }
        }

        $cart = Cart::with('menu')->where('user_id', Auth::id())->get();
        $totalCart = $cart->sum(fn($i) => ($i->menu->harga ?? 0) * $i->qty);

        return view('kasir.create', compact('menu', 'cabang', 'cart', 'totalCart', 'targetCabang'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'menu_id' => 'required|exists:menu,id',
            'qty' => 'required|integer|min:1',
        ]);

        $user = Auth::user();
        $menu = Menu::findOrFail($request->menu_id);

        $cabangId = ($user->role == 'admin') 
            ? (session('active_pos_cabang_id') ?: ($user->cabang_id ?: Cabang::first()?->id)) 
            : $user->cabang_id;

        $stokTersedia = $menu->getStokForCabang($cabangId);

        // Validasi jika stok cabang kosong
        if ($stokTersedia <= 0) {
            return back()->with('error', 'Stok untuk menu "' . $menu->nama_menu . '" di cabang ini sedang kosong (0).');
        }

        $cart = Cart::where('user_id', $user->id)
                    ->where('menu_id', $request->menu_id)
                    ->first();

        $currentQty = $cart ? $cart->qty : 0;
        if (($currentQty + $request->qty) > $stokTersedia) {
            return back()->with('error', 'Jumlah pesanan melebihi sisa stok yang tersedia (' . $stokTersedia . ').');
        }

        if ($cart) {
            $cart->increment('qty', $request->qty);
        } else {
            Cart::create([
                'user_id'   => $user->id,
                'menu_id'   => $request->menu_id,
                'qty'       => $request->qty,
                'cabang_id' => $cabangId 
            ]);
        }

        return back();
    }

    public function addToCart($id)
    {
        $user = Auth::user();
        $menu = Menu::findOrFail($id);

        $cabangId = ($user->role == 'admin') 
            ? (session('active_pos_cabang_id') ?: ($user->cabang_id ?: Cabang::first()?->id)) 
            : $user->cabang_id;

        $stokTersedia = $menu->getStokForCabang($cabangId);

        if ($stokTersedia <= 0) {
            return back()->with('error', 'Stok menu "' . $menu->nama_menu . '" di cabang ini sedang kosong (0).');
        }

        $cart = Cart::where('user_id', $user->id)->where('menu_id', $id)->first();
        $currentQty = $cart ? $cart->qty : 0;

        if ($currentQty >= $stokTersedia) {
            return back()->with('error', 'Jumlah pesanan sudah mencapai batas maksimal stok yang tersedia (' . $stokTersedia . ').');
        }

        if ($cart) {
            $cart->increment('qty');
        } else {
            Cart::create([
                'user_id'   => $user->id,
                'menu_id'   => $id,
                'qty'       => 1,
                'cabang_id' => $cabangId 
            ]);
        }

        return back();
    }

    public function removeCart($id)
    {
        Cart::where('id', $id)->where('user_id', Auth::id())->delete();
        return back(); // Notifikasi success dihapus
    }

    public function checkout(Request $request)
    {
        $request->validate(['metode_pembayaran' => 'required|in:cash,qris']);
        $user_id = Auth::id();
        $cartItems = Cart::with('menu')->where('user_id', $user_id)->get();

        if ($cartItems->isEmpty()) {
            return back()->with('error', 'Keranjang kosong');
        }

        $cartItems = $cartItems->filter(fn($item) => $item->menu !== null);
        $total = $cartItems->sum(fn($i) => ($i->menu->harga ?? 0) * $i->qty);

        if ($request->metode_pembayaran == 'cash') {
            DB::beginTransaction();
            try {
                $invoice = 'INV-' . time();
                foreach ($cartItems as $item) {
                    $menu = $item->menu;

                    $userCabangId = $user->cabang_id ?? $menu->cabang_id;
                    $branchStok = $menu->getStokForCabang($userCabangId);

                    // Kurangi stok menu cabang
                    if ($branchStok >= $item->qty) {
                        $menu->decrementStokCabang($userCabangId, $item->qty);
                    } else {
                        throw new \Exception('Stok menu ' . $menu->nama_menu . ' di cabang Anda tidak mencukupi (sisa: ' . $branchStok . ').');
                    }

                    Transaksi::create([
                        'invoice'           => $invoice,
                        'menu_id'           => $menu->id,
                        'cabang_id'         => $user->cabang_id ?? $menu->cabang_id,
                        'qty'               => $item->qty,
                        'total'             => $menu->harga * $item->qty,
                        'tanggal'           => now(),
                        'metode_pembayaran' => 'cash',
                        'status'            => 'success'
                    ]);
                }
                Cart::where('user_id', $user_id)->delete();
                DB::commit();
                return redirect()->route('kasir.create')->with('success', 'Transaksi Cash Berhasil!');
            } catch (\Exception $e) {
                DB::rollback();
                return back()->with('error', $e->getMessage());
            }
        }

        if ($request->metode_pembayaran == 'qris') {
            DB::beginTransaction();
            try {
                $invoice = 'INV-' . time();
                foreach ($cartItems as $item) {
                    $menu = $item->menu;

                    $userCabangId = $user->cabang_id ?? $item->menu->cabang_id;
                    $branchStok = $menu->getStokForCabang($userCabangId);

                    // Validasi stok sebelum checkout QRIS
                    if ($branchStok < $item->qty) {
                        throw new \Exception('Stok menu ' . $menu->nama_menu . ' di cabang Anda tidak mencukupi (sisa: ' . $branchStok . ').');
                    }

                    Transaksi::create([
                        'invoice'           => $invoice,
                        'menu_id'           => $item->menu_id,
                        'cabang_id'         => $user->cabang_id ?? $item->menu->cabang_id,
                        'qty'               => $item->qty,
                        'total'             => $item->menu->harga * $item->qty,
                        'tanggal'           => now(),
                        'metode_pembayaran' => 'qris',
                        'status'            => 'pending'
                    ]);
                }
                Cart::where('user_id', $user_id)->delete(); // 🔥 KOSONGKAN KERANJANG SETELAH CHECKOUT QRIS
                DB::commit();
                
                // Integrasi DOKU tetap dipertahankan
                $clientId = config('services.doku.client_id');
                $secretKey = config('services.doku.secret_key');
                $baseUrl = config('services.doku.base_url');
                $timestamp = gmdate('Y-m-d\TH:i:s\Z');
                $requestId = uniqid();
                $user = Auth::user();
\Log::info('APP_URL = ' . config('app.url'));
\Log::info('CALLBACK URL = ' . url('/doku/callback'));
\Log::info('RETURN URL = ' . url('/doku/return'));
                $body = [
                    "order" => [
                        "invoice_number" => $invoice,
                        "amount" => (int) $total,

                        // Return user setelah pembayaran selesai
                       "callback_url" => url('/doku/return'),

                        // Endpoint webhook Laravel
                       "notification_url" => url('/doku/callback')
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

$digest = base64_encode(
    hash('sha256', $jsonBody, true)
);

$requestTarget = '/checkout/v1/payment';

$signature = base64_encode(
    hash_hmac(
        'sha256',
        "Client-Id:$clientId\n".
        "Request-Id:$requestId\n".
        "Request-Timestamp:$timestamp\n".
        "Request-Target:$requestTarget\n".
        "Digest:$digest",
        $secretKey,
        true
    )
);

// DEBUG REQUEST
\Log::info('===== DOKU REQUEST =====');
\Log::info($jsonBody);

$response = Http::withHeaders([
    'Client-Id'         => $clientId,
    'Request-Id'        => $requestId,
    'Request-Timestamp' => $timestamp,
    'Signature'         => "HMACSHA256=$signature",
    'Digest'            => $digest,
    'Content-Type'      => 'application/json'
])->post(
    $baseUrl . $requestTarget,
    $body
);

// DEBUG RESPONSE
\Log::info('===== DOKU RESPONSE =====');
\Log::info($response->body());

if ($response->failed()) {
    \Log::error('===== DOKU ERROR =====');
    \Log::error($response->body());

    return back()->with(
        'error',
        'Doku Error: ' . $response->body()
    );
}

return redirect(
    $response['response']['payment']['url']
);
            } catch (\Exception $e) {
                DB::rollback();
                return back()->with('error', 'QRIS Error: ' . $e->getMessage());
            }
        }
    }

    public function dokuReturn(Request $request)
    {
        return redirect()->route('kasir.index')->with('success', 'Pembayaran DOKU selesai.');
    }
}