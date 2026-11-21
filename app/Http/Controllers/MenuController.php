<?php

namespace App\Http\Controllers;

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
        
        // Admin melihat semua menu (baik global maupun spesifik cabang)
        // User cabang melihat menu spesifik cabangnya + menu global (Semua Cabang)
        $menu = ($user->role == 'admin') 
            ? Menu::with('cabang')->get() 
            : Menu::where(function($q) use ($user) {
                $q->where('cabang_id', $user->cabang_id)
                  ->orWhereNull('cabang_id');
            })->with('cabang')->get();

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


    public function destroy($id)
    {
        Menu::findOrFail($id)->delete();
        return redirect()->route('menu.index')->with('success', 'Menu berhasil dihapus');
    }

    /**
     * 🔥 FUNGSI TAMBAH (+) SINKRON DENGAN ID MENU DAN VALIDASI STOK CABANG
     */
    
    public function add($id)
    {
        $user = Auth::user();
        $menu = Menu::findOrFail($id);

        // Tentukan cabang POS yang aktif
        $cabangId = $user->role === 'admin'
            ? (session('active_pos_cabang_id')
                ?: ($user->cabang_id ?: Cabang::first()?->id))
            : $user->cabang_id;

        if (!$cabangId) {
            return back()->with('error', 'Cabang POS belum dipilih.');
        }

        // Pastikan menu merupakan menu global atau milik cabang aktif
        if ($menu->cabang_id !== null &&
            (int) $menu->cabang_id !== (int) $cabangId) {
            return back()->with('error', 'Menu tidak tersedia di cabang ini.');
        }

        $stokTersedia = $menu->getStokForCabang($cabangId);

        if ($stokTersedia <= 0) {
            return back()->with('error',
                'Stok menu "' . $menu->nama_menu . '" sedang kosong.');
        }

        // // Jangan mencampur keranjang dari cabang berbeda
        // $keranjangLama = Cart::where('user_id', $user->id)->get();

        // foreach ($keranjangLama as $item) {
        //     if (
        //         $item->cabang_id === null ||
        //         (int) $item->cabang_id !== (int) $cabangId
        //     ) {
        //         return back()->with(
        //             'error',
        //             'Keranjang masih berisi pesanan dari cabang lain. Kosongkan keranjang terlebih dahulu.'
        //         );
        //     }
        // }

        $cartItem = Cart::where('user_id', $user->id)
            ->where('menu_id', $id)
            ->where('cabang_id', $cabangId)
            ->first();

        $currentQty = $cartItem ? $cartItem->qty : 0;

        if ($currentQty >= $stokTersedia) {
            return back()->with('error',
                'Jumlah pesanan sudah mencapai batas stok (' . $stokTersedia . ').');
        }

        if ($cartItem) {
            $cartItem->increment('qty');
        } else {
            Cart::create([
                'user_id' => $user->id,
                'menu_id' => $id,
                'cabang_id' => $cabangId,
                'qty' => 1,
            ]);
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
