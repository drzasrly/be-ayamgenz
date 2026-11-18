<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Stok;
use App\Models\Cabang;
use App\Models\Menu;
use App\Models\Bahan;
use App\Models\Resep;
use Illuminate\Support\Facades\Auth;

class StokController extends Controller
{

    public function index(Request $request)
    {
        $user = Auth::user();
        $cabangs = Cabang::all();

        if ($user->role == 'admin') {
            $selectedCabangId = $request->cabang_id ?: ($cabangs->first()->id ?? null);
            if ($selectedCabangId) {
                $menus = Menu::where(function($q) use ($selectedCabangId) {
                    $q->where('cabang_id', $selectedCabangId)
                      ->orWhereNull('cabang_id');
                })->with(['cabang', 'cabangStoks'])->get();

                foreach ($menus as $m) {
                    $m->stok = $m->getStokForCabang($selectedCabangId);
                }
            } else {
                $menus = Menu::with(['cabang', 'cabangStoks'])->get();
            }
        } else {
            $selectedCabangId = $user->cabang_id;
            $menus = Menu::where(function($q) use ($user) {
                $q->where('cabang_id', $user->cabang_id)
                  ->orWhereNull('cabang_id');
            })->with(['cabang', 'cabangStoks'])->get();

            foreach ($menus as $m) {
                $m->stok = $m->getStokForCabang($user->cabang_id);
            }
        }

        // Bahan mentah
        $bahan = ($user->role == 'admin')
            ? Bahan::where('cabang_id', $selectedCabangId)->get()
            : Bahan::where('cabang_id', $user->cabang_id)->get();

        return view('stok.index', compact('menus', 'cabangs', 'bahan', 'selectedCabangId'));
    }

    public function create()
    {
        $cabangs = Cabang::all();
        return view('stok.create', compact('cabangs'));
    }

    public function createBahan()
    {
        $cabangs = \App\Models\Cabang::all();
        return view('bahan.create', compact('cabangs'));
    }

    public function store(Request $request)
    {
        Stok::create([
            'nama_bahan' => $request->nama_bahan,
            'jumlah' => $request->jumlah,
            'satuan' => $request->satuan,
            'cabang_id' => Auth::user()->cabang_id
        ]);

        return redirect()->route('stok.index')
        ->with('success','Stok berhasil ditambahkan');
    }

    public function storeBahan(Request $request)
    {
        $request->validate([
            'nama_bahan' => 'required|string|max:255',
            'jumlah' => 'required|numeric|min:0',
            'satuan' => 'required|string',
            'cabang_id' => 'required|exists:cabangs,id'
        ]);

        \App\Models\Bahan::create([
            'nama_bahan' => $request->nama_bahan,
            'jumlah' => $request->jumlah,
            'satuan' => $request->satuan,
            'cabang_id' => Auth::user()->cabang_id
        ]);

        return back()->with('success', 'Bahan mentah berhasil ditambahkan!');
    }
    public function edit(Stok $stok)
    {
        $user = Auth::user();

        if ($user->role != 'admin' && $stok->cabang_id != $user->cabang_id) {
            abort(403);
        }

        $cabangs = Cabang::all();
        return view('stok.edit', compact('stok','cabangs'));
    }


    // Edit Bahan Mentah
    public function editBahan($id) {
        $bahan = Bahan::findOrFail($id);
        return view('bahan.edit', compact('bahan'));
    }

    // Update Bahan Mentah
    public function updateBahan(Request $request, $id) {
        $bahan = Bahan::findOrFail($id);
        $bahan->update($request->all());
        return redirect()->route('stok.index')->with('success', 'Stok bahan berhasil diperbarui!');
    }

    public function update(Request $request, Stok $stok)
    {
        $user = Auth::user();

        if ($user->role != 'admin' && $stok->cabang_id != $user->cabang_id) {
            abort(403);
        }

        $stok->update($request->all());

        return redirect()->route('stok.index')
            ->with('success','Stok berhasil diupdate');
    }

    public function destroy(Stok $stok)
    {
        $user = Auth::user();

        if ($user->role != 'admin' && $stok->cabang_id != $user->cabang_id) {
            abort(403);
        }

        $stok->delete();

        return redirect()->route('stok.index')
            ->with('success','Stok berhasil dihapus');
    }

    /**
     * Tampilkan form tambah stok menu untuk cabang tertentu
     */
    public function editMenuStok($id, Request $request)
    {
        $user = Auth::user();
        $menu = Menu::findOrFail($id);
        $cabangs = Cabang::all();

        // Validasi akses cabang untuk staf
        if ($user->role != 'admin' && $menu->cabang_id !== null && $menu->cabang_id != $user->cabang_id) {
            abort(403, 'Anda tidak berhak mengatur stok menu cabang lain.');
        }

        if ($user->role == 'admin') {
            $selectedCabangId = $request->cabang_id ?: ($cabangs->first()->id ?? null);
        } else {
            $selectedCabangId = $user->cabang_id;
        }

        $currentStok = $menu->getStokForCabang($selectedCabangId);

        return view('menu.stok', compact('menu', 'cabangs', 'selectedCabangId', 'currentStok'));
    }

    /**
     * Update/tambah stok menu untuk cabang tertentu
     */
    public function updateMenuStok(Request $request, $id)
    {
        $user = Auth::user();
        $menu = Menu::findOrFail($id);

        if ($user->role == 'admin') {
            $request->validate([
                'stok' => 'required|integer|min:1',
                'cabang_id' => 'required|exists:cabangs,id',
            ]);
            $cabangId = $request->cabang_id;
        } else {
            // Staf cabang hanya bisa mengupdate stok cabang miliknya
            if ($menu->cabang_id !== null && $menu->cabang_id != $user->cabang_id) {
                abort(403, 'Anda tidak berhak mengatur stok menu cabang lain.');
            }

            $request->validate([
                'stok' => 'required|integer|min:1'
            ]);
            $cabangId = $user->cabang_id;
        }

        $menu->incrementStokCabang($cabangId, (int) $request->stok);
        $namaCabang = Cabang::find($cabangId)?->nama_cabang ?? 'Cabang';

        return redirect()->route('stok.index', ['cabang_id' => $cabangId])
            ->with('success', 'Stok menu "' . $menu->nama_menu . '" untuk ' . $namaCabang . ' berhasil ditambahkan (+ ' . $request->stok . ').');
    }

    /**
     * Hapus bahan mentah
     */
    public function destroyBahan($id)
    {
        $bahan = Bahan::findOrFail($id);
        $bahan->delete();

        return redirect()->route('stok.index')->with('success', 'Bahan mentah berhasil dihapus.');
    }
}