<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View|RedirectResponse
    {
        if (!config('auth.enable_forgot_password', env('ENABLE_FORGOT_PASSWORD', true))) {
            return redirect()->route('login')->with('error', 'Fitur reset password sedang dinonaktifkan oleh administrator.');
        }

        $requirePin = !empty(env('FORGOT_PASSWORD_PIN'));

        return view('auth.forgot-password', compact('requirePin'));
    }

    /**
     * Handle an incoming password reset request.
     */
    public function store(Request $request): RedirectResponse
    {
        if (!config('auth.enable_forgot_password', env('ENABLE_FORGOT_PASSWORD', true))) {
            return redirect()->route('login')->with('error', 'Fitur reset password sedang dinonaktifkan oleh administrator.');
        }

        $rules = [
            'email' => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ];

        $configuredPin = env('FORGOT_PASSWORD_PIN');
        if (!empty($configuredPin)) {
            $rules['security_pin'] = [
                'required',
                function ($attribute, $value, $fail) use ($configuredPin) {
                    if ($value !== (string) $configuredPin) {
                        $fail('PIN Keamanan salah. Silakan hubungi admin.');
                    }
                }
            ];
        }

        $request->validate($rules, [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.exists' => 'Email tidak terdaftar di sistem.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal harus 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sesuai.',
            'security_pin.required' => 'PIN Keamanan wajib diisi.',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'Pengguna tidak ditemukan.']);
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
        ])->save();

        return redirect()->route('login')->with('status', 'Password untuk akun (' . $user->email . ') berhasil direset! Silakan login dengan password baru.');
    }
}
