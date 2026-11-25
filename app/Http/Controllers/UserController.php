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

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:users,name',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'cabang_id' => 'required|integer|exists:cabangs,id',
        ], [
            'name.required' => 'Nama pengguna wajib diisi.',
            'name.unique' => 'Nama pengguna sudah digunakan.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
            'cabang_id.required' => 'Cabang wajib dipilih.',
            'cabang_id.exists' => 'Cabang yang dipilih tidak ditemukan.',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role' => 'cabang',
            'cabang_id' => $validated['cabang_id'],
        ]);

        return redirect()->route('user.create')
            ->with('success', 'User cabang berhasil dibuat!');
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