<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
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

        if ($user->role === 'admin') {
            $selectedCabangId = $request->cabang_id
                ?: ($cabangs->first()->id ?? null);

            if ($selectedCabangId) {
                // Pastikan cabang yang dipilih benar-benar tersedia
                if (!Cabang::whereKey($selectedCabangId)->exists()) {
                    abort(404, 'Cabang tidak ditemukan.');
                }

                $menus = Menu::where('is_active', true)
                    ->where(function ($q) use ($selectedCabangId) {
                        $q->where('cabang_id', $selectedCabangId)
                        ->orWhereNull('cabang_id');
                    })
                    ->with(['cabang', 'cabangStoks'])
                    ->get();

                foreach ($menus as $m) {
                    $m->stok = $m->getStokForCabang($selectedCabangId);
                }
            } else {
                $menus = Menu::where('is_active', true)
                    ->with(['cabang', 'cabangStoks'])
                    ->get();
            }
        } else {
            if (!$user->cabang_id) {
                abort(403, 'Akun belum memiliki cabang.');
            }

            $selectedCabangId = $user->cabang_id;

            $menus = Menu::where('is_active', true)
                ->where(function ($q) use ($user) {
                    $q->where('cabang_id', $user->cabang_id)
                    ->orWhereNull('cabang_id');
                })
                ->with(['cabang', 'cabangStoks'])
                ->get();

            foreach ($menus as $m) {
                $m->stok = $m->getStokForCabang($user->cabang_id);
            }
        }

        // Bahan mentah
        $bahan = ($user->role === 'admin')
            ? Bahan::where('cabang_id', $selectedCabangId)->get()
            : Bahan::where('cabang_id', $user->cabang_id)->get();

        return view('stok.index', compact(
            'menus',
            'cabangs',
            'bahan',
            'selectedCabangId'
        ));
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
        $user = Auth::user();

        if ($user->role === 'admin') {
            $validated = $request->validate([
                'nama_bahan' => 'required|string|max:255',
                'jumlah' => 'required|numeric|min:0',
                'satuan' => 'required|string|max:50',
                'cabang_id' => 'required|exists:cabangs,id',
            ]);

            $cabangId = $validated['cabang_id'];
        } else {
            if (!$user->cabang_id) {
                abort(403, 'Akun belum memiliki cabang.');
            }

            $validated = $request->validate([
                'nama_bahan' => 'required|string|max:255',
                'jumlah' => 'required|numeric|min:0',
                'satuan' => 'required|string|max:50',
            ]);

            $cabangId = $user->cabang_id;
        }

        Bahan::create([
            'nama_bahan' => $validated['nama_bahan'],
            'jumlah' => $validated['jumlah'],
            'satuan' => $validated['satuan'],
            'cabang_id' => $cabangId,
        ]);

        return redirect()->route('stok.index')
            ->with('success', 'Bahan mentah berhasil ditambahkan!');
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
    public function editBahan($id)
    {
        $user = Auth::user();

        $query = Bahan::query();

        if ($user->role !== 'admin') {
            $query->where('cabang_id', $user->cabang_id);
        }

        $bahan = $query->findOrFail($id);

        return view('bahan.edit', compact('bahan'));
    }

    // Update Bahan Mentah
    public function updateBahan(Request $request, $id)
    {
        $user = Auth::user();

        $query = Bahan::query();

        if ($user->role !== 'admin') {
            $query->where('cabang_id', $user->cabang_id);
        }

        $bahan = $query->findOrFail($id);

        $validated = $request->validate([
            'nama_bahan' => 'required|string|max:255',
            'jumlah' => 'required|numeric|min:0',
            'satuan' => 'required|string|max:50',
        ]);

        $bahan->update($validated);

        return redirect()->route('stok.index')
            ->with('success', 'Stok bahan berhasil diperbarui!');
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

        // Menu nonaktif tidak boleh diatur stoknya
        if (!$menu->is_active) {
            return redirect()->route('stok.index')
                ->with('error', 'Menu tidak aktif. Stok tidak dapat diatur.');
        }

        $cabangs = Cabang::all();

        if ($user->role === 'admin') {

            // Admin dapat memilih cabang
            $selectedCabangId = $request->cabang_id
                ?: ($cabangs->first()->id ?? null);

            if (
                !$selectedCabangId ||
                !Cabang::whereKey($selectedCabangId)->exists()
            ) {
                abort(404, 'Cabang tidak ditemukan.');
            }

        } else {

            // Staf wajib memiliki cabang
            if (!$user->cabang_id) {
                abort(403, 'Akun belum memiliki cabang.');
            }

            $selectedCabangId = $user->cabang_id;

            // Staf tidak boleh mengatur menu khusus cabang lain
            if (
                $menu->cabang_id !== null &&
                (int) $menu->cabang_id !== (int) $user->cabang_id
            ) {
                abort(403, 'Anda tidak berhak mengatur stok menu cabang lain.');
            }
        }

        // Ambil stok sesuai cabang yang dipilih
        $currentStok = $menu->getStokForCabang($selectedCabangId);

        return view('menu.stok', compact(
            'menu',
            'cabangs',
            'selectedCabangId',
            'currentStok'
        ));
    }


    /**
     * Update/tambah stok menu untuk cabang tertentu
     */
    public function updateMenuStok(Request $request, $id)
    {
        $user = Auth::user();
        $menu = Menu::findOrFail($id);

        if (!$menu->is_active) {
            return back()->with('error', 'Menu tidak aktif.');
        }

        if ($user->role === 'admin') {
            $validated = $request->validate([
                'stok' => 'required|integer|min:1',
                'cabang_id' => 'required|exists:cabangs,id',
            ]);

            $cabangId = $validated['cabang_id'];
            if (
                $menu->cabang_id !== null &&
                (int) $menu->cabang_id !== (int) $cabangId
            ) {
                abort(403, 'Menu ini tidak tersedia untuk cabang yang dipilih.');
            }
        } else {
            if (!$user->cabang_id) {
                abort(403, 'Akun belum memiliki cabang.');
            }

            if (
                $menu->cabang_id !== null &&
                (int) $menu->cabang_id !== (int) $user->cabang_id
            ) {
                abort(403, 'Anda tidak berhak mengatur stok menu cabang lain.');
            }

            $validated = $request->validate([
                'stok' => 'required|integer|min:1',
            ]);

            $cabangId = $user->cabang_id;
        }

        DB::transaction(function () use ($menu, $cabangId, $validated) {

            // Kunci data menu selama proses penambahan stok
            $lockedMenu = Menu::whereKey($menu->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Tambahkan stok cabang
            $lockedMenu->incrementStokCabang(
                $cabangId,
                (int) $validated['stok']
            );

        }, 3);

        $namaCabang = Cabang::find($cabangId)?->nama_cabang ?? 'Cabang';

        return redirect()->route('stok.index', [
            'cabang_id' => $cabangId
        ])->with(
            'success',
            'Stok menu "' . $menu->nama_menu .
            '" untuk ' . $namaCabang .
            ' berhasil ditambahkan (+ ' . $validated['stok'] . ').'
        );
    }

    /**
     * Hapus bahan mentah
     */
    public function destroyBahan($id)
    {
        $user = Auth::user();

        $query = Bahan::query();

        if ($user->role !== 'admin') {
            $query->where('cabang_id', $user->cabang_id);
        }

        $bahan = $query->findOrFail($id);
        $bahan->delete();

        return redirect()->route('stok.index')
            ->with('success', 'Bahan mentah berhasil dihapus.');
    }
}