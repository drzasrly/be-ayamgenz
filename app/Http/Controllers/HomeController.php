<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaksi;
use App\Models\Menu;
use App\Models\MenuCabangStok;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $hariIni = Carbon::today();

        $trxQuery = Transaksi::whereDate('tanggal', $hariIni)
            ->where('status', 'success');
        if ($user && $user->role !== 'admin' && $user->cabang_id) {
            $trxQuery->where('cabang_id', $user->cabang_id);
        }

        $totalPenjualan = (clone $trxQuery)->sum('total');
        $totalTransaksi = (clone $trxQuery)->count();
        $menuTerjual    = (clone $trxQuery)->sum('qty');
        
        if ($user && $user->role !== 'admin' && $user->cabang_id) {
            $stokMenipis = MenuCabangStok::where('cabang_id', $user->cabang_id)->where('stok', '<', 10)->count();
        } else {
            $stokMenipis = MenuCabangStok::where('stok', '<', 10)->count();
        }

        return view('dashboard', compact(
            'totalPenjualan', 
            'totalTransaksi', 
            'menuTerjual', 
            'stokMenipis'
        ));
    }
}
