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

        $gambar = $request->file('gambar') ? $request->file('gambar')->store('menu', 'public') : null;

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
            'harga' => 'required|integer|min:0',
            'kategori' => 'required|string',
        ]);

        $menu->nama_menu = $request->nama_menu;
        $menu->harga = $request->harga;
        $menu->kategori = $request->kategori;

        if ($user->role == 'admin' && $request->has('cabang_id')) {
            $menu->cabang_id = ($request->cabang_id === 'all' || empty($request->cabang_id)) ? null : $request->cabang_id;
        }

        if ($request->file('gambar')) {
            $file = $request->file('gambar');
            $nama = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
            Storage::disk('public')->putFileAs('menu', $file, $nama);
            $menu->gambar = 'menu/' . $nama;
        }

        $menu->save();
        return redirect()->route('menu.index')->with('success', 'Data menu berhasil diperbarui!');
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

        $cabangId = ($user->role == 'admin') 
            ? (session('active_pos_cabang_id') ?: ($user->cabang_id ?: Cabang::first()?->id)) 
            : $user->cabang_id;

        $stokTersedia = $menu->getStokForCabang($cabangId);

        // Validasi ketersediaan stok
        if ($stokTersedia <= 0) {
            return redirect()->back()->with('error', 'Stok untuk menu "' . $menu->nama_menu . '" di cabang ini sedang kosong (0).');
        }

        $cartItem = Cart::where('user_id', $user->id)->where('menu_id', $id)->first();
        $currentCartQty = $cartItem ? $cartItem->qty : 0;

        if ($currentCartQty >= $stokTersedia) {
            return redirect()->back()->with('error', 'Jumlah pesanan "' . $menu->nama_menu . '" sudah mencapai batas maksimal stok yang tersedia (' . $stokTersedia . ').');
        }

        if ($cartItem) {
            $cartItem->increment('qty');
        } else {
            Cart::create([
                'user_id'   => $user->id,
                'menu_id'   => $id,
                'qty'       => 1,
                'cabang_id' => $cabangId
            ]);
        }

        return redirect()->back();
    }

    /**
     * 🔥 FUNGSI KURANG (-) SINKRON DENGAN ID MENU
     */
    public function decrease($id)
    {
        $user = Auth::user();
        $cartItem = Cart::where('user_id', $user->id)->where('menu_id', $id)->first();

        if ($cartItem) {
            if ($cartItem->qty > 1) {
                $cartItem->decrement('qty');
            } else {
                $cartItem->delete();
            }
            return redirect()->back();
        }

        return redirect()->back()->with('error', 'Item tidak ditemukan di keranjang');
    }
}
