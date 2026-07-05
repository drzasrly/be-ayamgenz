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
        
        $query = Menu::with('cabang');

        // Jika bukan admin, hanya bisa melihat menu dari cabangnya sendiri
        if ($user->role !== 'admin') {
            $query->where('cabang_id', $user->cabang_id);
        } else {
            // Jika admin, opsional memfilter berdasarkan cabang_id lewat query parameter
            if ($request->has('cabang_id')) {
                $query->where('cabang_id', $request->cabang_id);
            }
        }

        // Opsional memfilter berdasarkan kategori
        if ($request->has('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        $menus = $query->get()->map(function($menu) {
            return [
                'id' => $menu->id,
                'nama_menu' => $menu->nama_menu,
                'harga' => (int) $menu->harga,
                'kategori' => $menu->kategori,
                'stok' => (int) $menu->stok,
                'cabang_id' => $menu->cabang_id,
                'cabang_name' => $menu->cabang ? $menu->cabang->nama_cabang : null,
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

        // Validasi akses cabang
        if ($user->role !== 'admin' && $menu->cabang_id !== $user->cabang_id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke menu di cabang lain.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $menu->id,
                'nama_menu' => $menu->nama_menu,
                'harga' => (int) $menu->harga,
                'kategori' => $menu->kategori,
                'stok' => (int) $menu->stok,
                'cabang_id' => $menu->cabang_id,
                'cabang_name' => $menu->cabang ? $menu->cabang->nama_cabang : null,
                'gambar' => $menu->gambar ? url('storage/' . $menu->gambar) : null,
                'created_at' => $menu->created_at,
                'updated_at' => $menu->updated_at,
            ]
        ]);
    }
}
