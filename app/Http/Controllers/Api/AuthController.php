<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle user login.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'nullable|string',
        ]);

        $user = User::with('cabang')->where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah.'
            ], 401);
        }

        // Batasi agar admin hanya bisa login lewat web panel, tidak lewat mobile app
        if ($user->role === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Admin hanya dapat masuk melalui panel Website.'
            ], 403);
        }

        $deviceName = $request->device_name ?? 'mobile_app';
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'cabang_id' => $user->cabang_id,
                'cabang' => $user->cabang ? [
                    'id' => $user->cabang->id,
                    'nama_cabang' => $user->cabang->nama_cabang,
                    'alamat' => $user->cabang->alamat,
                ] : null
            ]
        ]);
    }

    /**
     * Handle user logout.
     */
    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil. Token telah dihapus.'
        ]);
    }

    /**
     * Get the authenticated user.
     */
    public function me(Request $request)
    {
        $user = $request->user()->load('cabang');

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'cabang_id' => $user->cabang_id,
                'cabang' => $user->cabang ? [
                    'id' => $user->cabang->id,
                    'nama_cabang' => $user->cabang->nama_cabang,
                    'alamat' => $user->cabang->alamat,
                ] : null
            ]
        ]);
    }
}
