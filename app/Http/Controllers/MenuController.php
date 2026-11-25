<?php

namespace App\Http\Controllers;
use App\Models\MenuCabangStok;
use Illuminate\Http\Request;
use App\Models\Menu;
use App\Models\Cabang;
use App\Models\Resep;
use App\Models\Bahan;
use App\Models\Cart; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MenuController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            // Admin tetap dapat melihat menu aktif dan nonaktif.
            $menu = Menu::with('cabang')->get();
        } else {
            // Pengguna cabang hanya melihat menu aktif.
            $menu = Menu::where('is_active', true)
                ->where(function ($q) use ($user) {
                    $q->where('cabang_id', $user->cabang_id)
                    ->orWhereNull('cabang_id');
                })
                ->with('cabang')
                ->get();
        }

        return view('menu.index', compact('menu'));
    }

    public function create()
    {
        $cabangs = Cabang::all();
        return view('menu.create', compact('cabangs'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'nama_menu' => 'required|string|max:255',
            'harga' => 'required|integer|min:0',
            'kategori' => 'required|string',
            'cabang_id' => 'nullable',
            'gambar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:15360',
        ]);

        // Jika admin memilih 'all' atau kosong, cabang_id = null (artinya berlaku untuk Semua Cabang)
        if ($user->role == 'admin') {
            $cabangId = ($request->cabang_id === 'all' || empty($request->cabang_id)) ? null : $request->cabang_id;
        } else {
            $cabangId = $user->cabang_id;
        }

        $gambar = null;

        if ($request->hasFile('gambar')) {
            $gambar = $request->file('gambar')->store('menu', 'public');
        }

        Menu::create([
            'nama_menu' => $request->nama_menu,
            'harga'     => $request->harga,
            'kategori'  => $request->kategori,
            'stok'      => 0,
            'cabang_id' => $cabangId,
            'gambar'    => $gambar
        ]);

        return redirect()->route('menu.index')->with('success', 'Menu baru berhasil ditambahkan!');
    }

    public function edit($id)
    {
        $menu = Menu::findOrFail($id);
        $cabangs = Cabang::all();
        return view('menu.edit', compact('menu', 'cabangs'));
    }

    
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $menu = Menu::findOrFail($id);

        $request->validate([
            'nama_menu' => 'required|string|max:255',
            'harga'     => 'required|integer|min:0',
            'kategori'  => 'required|string',
            'gambar'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:15360',
        ]);

        $menu->nama_menu = $request->nama_menu;
        $menu->harga = $request->harga;
        $menu->kategori = $request->kategori;

        if ($user->role == 'admin' && $request->has('cabang_id')) {
            $menu->cabang_id = ($request->cabang_id === 'all' || empty($request->cabang_id))
                ? null
                : $request->cabang_id;
        }

        // Upload gambar baru jika dipilih
        if ($request->hasFile('gambar')) {
            $gambarLama = $menu->gambar;

            $gambarBaru = $request->file('gambar')->store('menu', 'public');

            $menu->gambar = $gambarBaru;

            $menu->save();

            // Hapus gambar lama setelah gambar baru berhasil disimpan
            if ($gambarLama && Storage::disk('public')->exists($gambarLama)) {
                Storage::disk('public')->delete($gambarLama);
            }
        } else {
            // Tidak upload gambar baru, gunakan gambar lama
            $menu->save();
        }

        return redirect()->route('menu.index')
            ->with('success', 'Data menu berhasil diperbarui!');
    }

    public function toggleStatus($id)
    {
        $menu = Menu::findOrFail($id);

        $menu->is_active = !$menu->is_active;
        $menu->save();

        $status = $menu->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('menu.index')
            ->with('success', 'Menu "' . $menu->nama_menu . '" berhasil ' . $status . '.');
    }

    public function destroy($id)
    {
        $menu = Menu::findOrFail($id);

        // Cegah penghapusan menu yang pernah digunakan dalam transaksi.
        $pernahTransaksi = DB::table('transaksis')
            ->where('menu_id', $menu->id)
            ->exists();

        if ($pernahTransaksi) {
            return redirect()->route('menu.index')
                ->with(
                    'error',
                    'Menu "' . $menu->nama_menu .
                    '" tidak dapat dihapus karena memiliki riwayat transaksi. Nonaktifkan menu jika sudah tidak dijual.'
                );
        }

        // Cegah penghapusan menu yang masih ada di keranjang.
        $adaDiKeranjang = Cart::where('menu_id', $menu->id)->exists();

        if ($adaDiKeranjang) {
            return redirect()->route('menu.index')
                ->with(
                    'error',
                    'Menu "' . $menu->nama_menu .
                    '" tidak dapat dihapus karena masih berada di keranjang pengguna.'
                );
        }

        // Hapus menu dan data terkait dalam satu transaksi database.
        DB::transaction(function () use ($menu) {
            $gambar = $menu->gambar;

            $menu->delete();

            // Hapus gambar hanya setelah penghapusan database berhasil.
            if ($gambar && Storage::disk('public')->exists($gambar)) {
                Storage::disk('public')->delete($gambar);
            }
        });

        return redirect()->route('menu.index')
            ->with('success', 'Menu berhasil dihapus.');
    }

    /**
     * 🔥 FUNGSI TAMBAH (+) SINKRON DENGAN ID MENU DAN VALIDASI STOK CABANG
     */
    
    public function add($id)
    {
        $user = Auth::user();
        $menu = Menu::findOrFail($id);

        if (!$menu->is_active) {
            return back()->with('error', 'Menu sudah tidak aktif dan tidak dapat dibeli.');
        }

        $cabangId = $user->role === 'admin'
            ? (session('active_pos_cabang_id')
                ?: ($user->cabang_id ?: Cabang::first()?->id))
            : $user->cabang_id;

        if (!$cabangId || !Cabang::whereKey($cabangId)->exists()) {
            return back()->with('error', 'Cabang POS belum dipilih atau tidak valid.');
        }

        if (
            $menu->cabang_id !== null &&
            (int) $menu->cabang_id !== (int) $cabangId
        ) {
            return back()->with('error', 'Menu tidak tersedia di cabang ini.');
        }

        try {
            DB::transaction(function () use ($user, $menu, $cabangId) {
                $stok = MenuCabangStok::where('menu_id', $menu->id)
                    ->where('cabang_id', $cabangId)
                    ->lockForUpdate()
                    ->first();

                if (!$stok || $stok->stok <= 0) {
                    throw new \RuntimeException(
                        'Stok menu "' . $menu->nama_menu . '" sedang kosong.'
                    );
                }

                $cartItem = Cart::where('user_id', $user->id)
                    ->where('menu_id', $menu->id)
                    ->where('cabang_id', $cabangId)
                    ->lockForUpdate()
                    ->first();

                $currentQty = $cartItem?->qty ?? 0;

                if ($currentQty >= $stok->stok) {
                    throw new \RuntimeException(
                        'Jumlah pesanan sudah mencapai batas stok (' . $stok->stok . ').'
                    );
                }

                if ($cartItem) {
                    $cartItem->increment('qty');
                } else {
                    Cart::create([
                        'user_id' => $user->id,
                        'menu_id' => $menu->id,
                        'cabang_id' => $cabangId,
                        'qty' => 1,
                    ]);
                }
            });

        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back();
    }


    /**
     * 🔥 FUNGSI KURANG (-) SINKRON DENGAN ID MENU
     */
    public function decrease($id)
    {
        $user = Auth::user();

        $cabangId = $user->role === 'admin'
            ? (session('active_pos_cabang_id')
                ?: ($user->cabang_id ?: Cabang::first()?->id))
            : $user->cabang_id;

        $cartItem = Cart::where('user_id', $user->id)
            ->where('menu_id', $id)
            ->where('cabang_id', $cabangId)
            ->first();

        if (!$cartItem) {
            return back()->with(
                'error',
                'Item tidak ditemukan di keranjang cabang ini.'
            );
        }

        if ($cartItem->qty > 1) {
            $cartItem->decrement('qty');
        } else {
            $cartItem->delete();
        }

        return back();
    }
}
