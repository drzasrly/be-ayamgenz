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
        $menu = ($user->role == 'admin') ? Menu::all() : Menu::where('cabang_id', $user->cabang_id)->get();
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
        $request->validate(['nama_menu' => 'required', 'harga' => 'required|integer', 'kategori' => 'required']);
        $cabangId = $user->role == 'admin' ? $request->cabang_id : $user->cabang_id;

        $gambar = $request->file('gambar') ? $request->file('gambar')->store('menu', 'public') : null;

        Menu::create([
            'nama_menu' => $request->nama_menu, 'harga' => $request->harga,
            'kategori' => $request->kategori, 'stok' => 0,
            'cabang_id' => $cabangId, 'gambar' => $gambar
        ]);

        return redirect()->route('menu.index')->with('success', 'Menu ditambahkan');
    }

    public function edit($id)
    {
        $menu = Menu::findOrFail($id);
        return view('menu.edit', compact('menu'));
    }

    public function update(Request $request, $id)
    {
        $menu = Menu::findOrFail($id);
        $menu->nama_menu = $request->nama_menu;
        $menu->harga = $request->harga;
        $menu->kategori = $request->kategori;

        if ($request->file('gambar')) {
            $file = $request->file('gambar');
            $nama = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
            Storage::disk('public')->putFileAs('menu', $file, $nama);
            $menu->gambar = 'menu/' . $nama;
        }

        $menu->save();
        return redirect()->route('menu.index')->with('success', 'Berhasil update');
    }

    public function destroy($id)
    {
        Menu::findOrFail($id)->delete();
        return redirect()->route('menu.index')->with('success', 'Menu dihapus');
    }

    /**
     * 🔥 FUNGSI TAMBAH (+) SINKRON DENGAN ID MENU
     */
    public function add($id)
    {
        $user = Auth::user();
        $menu = Menu::findOrFail($id);

        $cartItem = Cart::where('user_id', $user->id)->where('menu_id', $id)->first();

        if ($cartItem) {
            $cartItem->increment('qty');
        } else {
            Cart::create([
                'user_id' => $user->id, 'menu_id' => $id, 'qty' => 1, 'cabang_id' => $user->cabang_id
            ]);
        }

        return redirect()->back(); // Notifikasi success dihapus
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
            return redirect()->back(); // Notifikasi success dihapus
        }

        return redirect()->back()->with('error', 'Item tidak ditemukan');
    }

}