<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Transaksi;
use App\Models\Cabang;
use App\Models\Menu;
use App\Models\Cart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\MenuCabangStok;

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
            $menu = Menu::where('is_active', true)
            ->where(function ($q) use ($targetCabang) {
                $q->where('cabang_id', $targetCabang)
                ->orWhereNull('cabang_id');
            })
            ->with(['cabang', 'cabangStoks'])
            ->get();
        } else {
            $targetCabang = $user->cabang_id;
            $menu = Menu::where('is_active', true)
            ->where(function ($q) use ($user) {
                $q->where('cabang_id', $user->cabang_id)
                ->orWhereNull('cabang_id');
            })
            ->with(['cabang', 'cabangStoks'])
            ->get();
        }

        // Tampilkan stok sesuai cabang yang aktif/sedang membuka kasir
        if ($targetCabang) {
            foreach ($menu as $m) {
                $m->stok = $m->getStokForCabang($targetCabang);
            }
        }

        $cart = Cart::with('menu')
            ->where('user_id', Auth::id())
            ->where('cabang_id', $targetCabang)
            ->get();
        $totalCart = $cart->sum(fn($i) => ($i->menu->harga ?? 0) * $i->qty);

        return view('kasir.create', compact('menu', 'cabang', 'cart', 'totalCart', 'targetCabang'));
    }


    public function cart()
    {
        $user = Auth::user();

        // Tentukan cabang aktif
        if ($user->role === 'admin') {
            $targetCabang = session('active_pos_cabang_id')
                ?: ($user->cabang_id ?: Cabang::first()?->id);
        } else {
            $targetCabang = $user->cabang_id;
        }

        // Ambil keranjang milik pengguna dan cabang aktif
        $cart = Cart::with('menu')
            ->where('user_id', $user->id)
            ->where('cabang_id', $targetCabang)
            ->get();

        return view('kasir.cart', compact('cart'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'menu_id' => 'required|exists:menu,id',
            'qty' => 'required|integer|min:1',
        ]);

        $user = Auth::user();
        $menu = Menu::findOrFail($request->menu_id);

        if (!$menu->is_active) {
            return back()->with('error', 'Menu sudah tidak aktif dan tidak dapat dibeli.');
        }

        $cabangId = ($user->role === 'admin')
            ? (session('active_pos_cabang_id') ?: ($user->cabang_id ?: Cabang::first()?->id))
            : $user->cabang_id;

        if (
            !$cabangId ||
            !Cabang::whereKey($cabangId)->exists()
        ) {
            return back()->with('error', 'Cabang POS tidak valid.');
        }

        if (
            $menu->cabang_id !== null &&
            (int) $menu->cabang_id !== (int) $cabangId
        ) {
            return back()->with('error', 'Menu tidak tersedia di cabang ini.');
        }

        $stokTersedia = $menu->getStokForCabang($cabangId);

        // Validasi jika stok cabang kosong
        if ($stokTersedia <= 0) {
            return back()->with('error', 'Stok untuk menu "' . $menu->nama_menu . '" di cabang ini sedang kosong (0).');
        }

        $cart = Cart::where('user_id', $user->id)
            ->where('menu_id', $request->menu_id)
            ->where('cabang_id', $cabangId)
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

        $cart = Cart::where('user_id', $user->id)
            ->where('menu_id', $id)
            ->where('cabang_id', $cabangId)
            ->first();
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
        $validated = $request->validate([
            'metode_pembayaran' => 'required|in:cash,qris',
        ]);

        $user = Auth::user();

        try {
            $invoice = DB::transaction(function () use ($user, $validated) {

                // Ambil dan kunci keranjang di dalam transaksi
                $cartItems = Cart::where('user_id', $user->id)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                if ($cartItems->isEmpty()) {
                    throw new \RuntimeException('Keranjang kosong.');
                }

                // Pastikan semua item memiliki menu dan cabang
                $cartItems->load('menu');

                foreach ($cartItems as $item) {
                    if (!$item->menu || !$item->cabang_id || $item->qty <= 0) {
                        throw new \RuntimeException(
                            'Data keranjang tidak valid. Silakan periksa kembali pesanan.'
                        );
                    }
                }

                // Pastikan hanya satu cabang dalam satu transaksi
                $cabangIds = $cartItems->pluck('cabang_id')
                    ->unique()
                    ->values();

                if ($cabangIds->count() !== 1) {
                    throw new \RuntimeException(
                        'Keranjang tidak boleh mencampur pesanan dari cabang berbeda.'
                    );
                }

                $cabangId = (int) $cabangIds->first();

                // Pegawai hanya boleh checkout untuk cabangnya sendiri
                if (
                    $user->role !== 'admin' &&
                    (int) $user->cabang_id !== $cabangId
                ) {
                    throw new \RuntimeException(
                        'Anda tidak memiliki akses ke transaksi cabang ini.'
                    );
                }

                // Admin harus checkout dari cabang POS yang sedang aktif
                if ($user->role === 'admin') {
                    $activeCabangId = session('active_pos_cabang_id')
                        ?: ($user->cabang_id ?: Cabang::first()?->id);

                    if (!$activeCabangId || (int) $activeCabangId !== $cabangId) {
                        throw new \RuntimeException(
                            'Cabang keranjang tidak sesuai dengan cabang POS aktif.'
                        );
                    }
                }

                $invoice = 'INV-' . strtoupper(
                    (string) \Illuminate\Support\Str::uuid()
                );

                foreach ($cartItems as $item) {
                    $menu = Menu::whereKey($item->menu_id)
                        ->lockForUpdate()
                        ->first();

                    if (!$menu) {
                        throw new \RuntimeException(
                            'Menu dalam keranjang tidak ditemukan.'
                        );
                    }

                    if (!$menu->is_active) {
                        throw new \RuntimeException(
                            'Menu "' . $menu->nama_menu .
                            '" sudah tidak aktif. Silakan hapus dari keranjang.'
                        );
                    }

                    // Menu harus global atau milik cabang transaksi
                    if (
                        $menu->cabang_id !== null &&
                        (int) $menu->cabang_id !== $cabangId
                    ) {
                        throw new \RuntimeException(
                            'Menu ' . $menu->nama_menu .
                            ' tidak tersedia di cabang ini.'
                        );
                    }

                    // Kunci stok cabang
                    $stok = MenuCabangStok::where('menu_id', $menu->id)
                        ->where('cabang_id', $cabangId)
                        ->lockForUpdate()
                        ->first();

                    if (!$stok || $stok->stok < $item->qty) {
                        throw new \RuntimeException(
                            'Stok ' . $menu->nama_menu .
                            ' tidak mencukupi. Stok tersedia: ' .
                            ($stok->stok ?? 0)
                        );
                    }

                    // Kurangi stok hanya pada cabang transaksi
                    $stok->decrement('stok', $item->qty);

                    Transaksi::create([
                        'invoice' => $invoice,
                        'menu_id' => $menu->id,
                        'cabang_id' => $cabangId,
                        'qty' => $item->qty,
                        'total' => $menu->harga * $item->qty,
                        'tanggal' => now()->toDateString(),
                        'metode_pembayaran' => $validated['metode_pembayaran'],
                        'status' => 'success',
                    ]);
                }

                // Hapus hanya item keranjang yang diproses
                $cartIds = $cartItems->pluck('id');

                Cart::where('user_id', $user->id)
                    ->whereIn('id', $cartIds)
                    ->delete();

                return $invoice;
            }, 3);

            return redirect()->route('kasir.nota', [
                'invoice' => $invoice,
            ]);

        } catch (\Throwable $e) {
            report($e);

            $message = $e instanceof \RuntimeException
                ? $e->getMessage()
                : 'Checkout gagal. Silakan coba kembali.';

            return back()->with('error', $message);
        }
    }


    // public function checkout(Request $request)
    // {
    //     $request->validate([
    //         'metode_pembayaran' => 'required|in:cash,qris'
    //     ]);

    //     $user = Auth::user();
    //     $user_id = $user->id;

    //     $cartItems = Cart::with('menu')
    //         ->where('user_id', $user_id)
    //         ->get();

    //     if ($cartItems->isEmpty()) {
    //         return back()->with('error', 'Keranjang kosong.');
    //     }

    //     $cartItems = $cartItems->filter(fn($item) => $item->menu !== null);

    //     if ($cartItems->isEmpty()) {
    //         return back()->with('error', 'Menu dalam keranjang tidak ditemukan.');
    //     }

    //     foreach ($cartItems as $item) {
    //         if (!$item->cabang_id) {
    //             return back()->with(
    //                 'error',
    //                 'Ada pesanan lama yang belum memiliki cabang. Kosongkan keranjang dan masukkan ulang menu.'
    //             );
    //         }
    //     }

    //     $cabangBerbeda = $cartItems->pluck('cabang_id')->unique()->count() > 1;

    //     if ($cabangBerbeda) {
    //         return back()->with('error', 'Keranjang tidak boleh mencampur cabang.');
    //     }

    //     $total = $cartItems->sum(
    //         fn($item) => $item->menu->harga * $item->qty
    //     );

    //     $invoice = 'INV-' . strtoupper(uniqid());

        //     if ($request->metode_pembayaran === 'cash') {
        //     try {
        //         DB::transaction(function () use ($cartItems, $user_id, $invoice) {

        //             foreach ($cartItems as $item) {
        //                 $menu = $item->menu;
        //                 $cabangId = $item->cabang_id;

        //                 // Kunci baris stok agar tidak terjadi pengurangan bersamaan
        //                 $stok = MenuCabangStok::where('menu_id', $menu->id)
        //                     ->where('cabang_id', $cabangId)
        //                     ->lockForUpdate()
        //                     ->first();

        //                 if (!$stok || $stok->stok < $item->qty) {
        //                     throw new \Exception(
        //                         'Stok ' . $menu->nama_menu .
        //                         ' tidak mencukupi. Stok tersedia: ' .
        //                         ($stok->stok ?? 0)
        //                     );
        //                 }

        //                 $stok->decrement('stok', $item->qty);

        //                 Transaksi::create([
        //                     'invoice' => $invoice,
        //                     'menu_id' => $menu->id,
        //                     'cabang_id' => $cabangId,
        //                     'qty' => $item->qty,
        //                     'total' => $menu->harga * $item->qty,
        //                     'tanggal' => now()->toDateString(),
        //                     'metode_pembayaran' => 'cash',
        //                     'status' => 'success',
        //                 ]);
        //             }

        //             Cart::where('user_id', $user_id)->delete();
        //         });

        //         return redirect()->route('kasir.nota', [
        //             'invoice' => $invoice
        //         ]);

        //     } catch (\Exception $e) {
        //         return back()->with('error', $e->getMessage());
        //     }
        // }

        

        // if ($request->metode_pembayaran === 'qris') {

        //     DB::beginTransaction();

        //     try {
        //         foreach ($cartItems as $item) {
        //             Transaksi::create([
        //                 'invoice' => $invoice,
        //                 'menu_id' => $item->menu_id,
        //                 'cabang_id' => $item->cabang_id,
        //                 'qty' => $item->qty,
        //                 'total' => $item->menu->harga * $item->qty,
        //                 'tanggal' => now()->toDateString(),
        //                 'metode_pembayaran' => 'qris',
        //                 'status' => 'pending',
        //             ]);
        //         }

        //         Cart::where('user_id', $user_id)->delete();

        //         DB::commit();

        //     } catch (\Exception $e) {
        //         DB::rollBack();

        //         return back()->with('error', $e->getMessage());
        //     }

        //     try {
        //         // Integrasi DOKU tetap dipertahankan
        //         $clientId = config('services.doku.client_id');
        //         $secretKey = config('services.doku.secret_key');
        //         $baseUrl = config('services.doku.base_url');
        //         $timestamp = gmdate('Y-m-d\TH:i:s\Z');
        //         $requestId = uniqid();
        //         $user = Auth::user();
        //         \Log::info('APP_URL = ' . config('app.url'));
        //         \Log::info('CALLBACK URL = ' . url('/doku/callback'));
        //         \Log::info('RETURN URL = ' . url('/doku/return'));
                
        //         $publicBaseUrl = rtrim(config('app.url'), '/');

        //         $body = [
        //             "order" => [
        //                 "invoice_number" => $invoice,
        //                 "amount" => (int) $total,
        //                 "callback_url" => $publicBaseUrl . '/doku/return',
        //                 "notification_url" => $publicBaseUrl . '/doku/callback'
        //             ],

        //             "payment" => [
        //                 "payment_due_date" => 60
        //             ],

        //             "customer" => [
        //                 "name" => $user->name,
        //                 "email" => $user->email
        //             ]
        //         ];

        //         \Log::info('APP_URL = ' . config('app.url'));
        //         \Log::info('CALLBACK URL = ' . $body['order']['notification_url']);
        //         \Log::info('RETURN URL = ' . $body['order']['callback_url']);

        //         $jsonBody = json_encode($body);

        //         $digest = base64_encode(
        //             hash('sha256', $jsonBody, true)
        //         );

        //         $requestTarget = '/checkout/v1/payment';

        //         $signature = base64_encode(
        //             hash_hmac(
        //                 'sha256',
        //                 "Client-Id:$clientId\n".
        //                 "Request-Id:$requestId\n".
        //                 "Request-Timestamp:$timestamp\n".
        //                 "Request-Target:$requestTarget\n".
        //                 "Digest:$digest",
        //                 $secretKey,
        //                 true
        //             )
        //         );

        //         // DEBUG REQUEST
        //         \Log::info('===== DOKU REQUEST =====');
        //         \Log::info($jsonBody);

        //         $response = Http::withHeaders([
        //             'Client-Id'         => $clientId,
        //             'Request-Id'        => $requestId,
        //             'Request-Timestamp' => $timestamp,
        //             'Signature'         => "HMACSHA256=$signature",
        //             'Digest'            => $digest,
        //         ])->withBody(
        //             $jsonBody,
        //             'application/json'
        //         )->post($baseUrl . $requestTarget);

        //         // DEBUG RESPONSE
        //         \Log::info('===== DOKU RESPONSE =====');
        //         \Log::info($response->body());

        //         if ($response->failed()) {
        //             \Log::error('===== DOKU ERROR =====');
        //             \Log::error($response->body());

        //             return back()->with(
        //                 'error',
        //                 'Doku Error: ' . $response->body()
        //             );
        //         }

        //         return redirect(
        //             $response['response']['payment']['url']
        //         );
        //         } catch (\Exception $e) {
        //             \Log::error('QRIS ERROR: ' . $e->getMessage());

        //             return back()->with(
        //                 'error',
        //                 'QRIS Error: ' . $e->getMessage()
        //             );
        //         }

        //     } 

        // } 

    

    // public function dokuReturn(Request $request)
    // {
    //     return redirect()->route('kasir.index')
    //         ->with('success', 'Pembayaran DOKU selesai.');
    // }


    public function cetakNota($invoice)
    {
        $user = Auth::user();

        // Ambil seluruh item berdasarkan invoice
        $transaksi = Transaksi::with('menu')
            ->where('invoice', $invoice)
            ->orderBy('id')
            ->get();

        if ($transaksi->isEmpty()) {
            abort(404, 'Transaksi tidak ditemukan.');
        }

        // Pastikan semua transaksi sudah berhasil
        if ($transaksi->contains(fn($item) => $item->status !== 'success')) {
            abort(403, 'Transaksi belum berhasil dan nota belum dapat dicetak.');
        }

        // Kasir hanya boleh mencetak nota cabangnya sendiri
        if ($user->role !== 'admin') {
            if ($transaksi->contains(
                fn ($item) => (int) $item->cabang_id !== (int) $user->cabang_id
            )) {
                abort(403, 'Anda tidak memiliki akses ke nota ini.');
            }
        }

        $first = $transaksi->first();

        $cabang = Cabang::find($first->cabang_id);

        $total = $transaksi->sum('total');

        return view('kasir.nota', compact(
            'transaksi',
            'invoice',
            'cabang',
            'total'
        ));
    }


}