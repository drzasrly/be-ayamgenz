<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Menu;

class MenuController extends Controller
{
    /**
     * Display a listing of the menus.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Menu::where('is_active', true)->with('cabang');

        // Jika bukan admin, melihat menu dari cabangnya sendiri + menu global (semua cabang)
        if ($user->role !== 'admin') {
            $query->where(function($q) use ($user) {
                $q->where('cabang_id', $user->cabang_id)
                  ->orWhereNull('cabang_id');
            });
        } else {
            // Jika admin, opsional memfilter berdasarkan cabang_id lewat query parameter
            if ($request->has('cabang_id')) {
                $cabangId = $request->cabang_id;
                $query->where(function($q) use ($cabangId) {
                    $q->where('cabang_id', $cabangId)
                      ->orWhereNull('cabang_id');
                });
            }
        }

        // Opsional memfilter berdasarkan kategori
        if ($request->has('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        $targetCabang = ($user->role !== 'admin') ? $user->cabang_id : ($request->cabang_id ?: $user->cabang_id);

        $menus = $query->with('cabangStoks')->get()->map(function($menu) use ($targetCabang) {
            return [
                'id' => $menu->id,
                'nama_menu' => $menu->nama_menu,
                'harga' => (int) $menu->harga,
                'kategori' => $menu->kategori,
                'stok' => $targetCabang ? $menu->getStokForCabang($targetCabang) : (int) $menu->stok,
                'cabang_id' => $menu->cabang_id,
                'cabang_name' => $menu->cabang ? $menu->cabang->nama_cabang : 'Semua Cabang',
                'gambar' => $menu->gambar ? url('storage/' . $menu->gambar) : null,
                'created_at' => $menu->created_at,
                'updated_at' => $menu->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $menus
        ]);
    }

    /**
     * Display the specified menu.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $menu = Menu::with('cabang')->findOrFail($id);

        if (!$menu->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Menu tidak tersedia.'
            ], 404);
        }

        // Validasi akses cabang (menu global cabang_id null bisa diakses semua cabang)
        if ($user->role !== 'admin' && $menu->cabang_id !== null && $menu->cabang_id !== $user->cabang_id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke menu di cabang lain.'
            ], 403);
        }

        $targetCabang = ($user->role !== 'admin') ? $user->cabang_id : ($request->cabang_id ?: $user->cabang_id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $menu->id,
                'nama_menu' => $menu->nama_menu,
                'harga' => (int) $menu->harga,
                'kategori' => $menu->kategori,
                'stok' => $targetCabang ? $menu->getStokForCabang($targetCabang) : (int) $menu->stok,
                'cabang_id' => $menu->cabang_id,
                'cabang_name' => $menu->cabang ? $menu->cabang->nama_cabang : 'Semua Cabang',
                'gambar' => $menu->gambar ? url('storage/' . $menu->gambar) : null,
                'created_at' => $menu->created_at,
                'updated_at' => $menu->updated_at,
            ]
        ]);
    }
}
