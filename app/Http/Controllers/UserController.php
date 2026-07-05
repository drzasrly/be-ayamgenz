<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Cabang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function create()
    {
        $cabangs = Cabang::all();
        $users = User::where('role', 'cabang')->with('cabang')->get();
        return view('user.create', compact('cabangs', 'users'));
    }

    public function store(Request $request)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            abort(403);
        }

        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'cabang_id' => 'required'
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => 'cabang',
            'cabang_id' => $request->cabang_id
        ]);

        return redirect()->route('user.create')->with('success', 'User cabang berhasil dibuat!');
    }

    public function destroy($id)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            abort(403);
        }

        $user = User::findOrFail($id);

        if ($user->id === Auth::user()->id) {
            return redirect()->back()->with('error', 'Tidak dapat menghapus diri sendiri!');
        }

        $user->delete();

        return redirect()->back()->with('success', 'User cabang berhasil dihapus!');
    }
}