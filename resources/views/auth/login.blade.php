<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Kasir</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-orange-100 to-orange-300 min-h-screen flex items-center justify-center p-4">

<div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md">
    
    <h2 class="text-2xl font-bold text-center text-orange-600 mb-2">
        🍗 Fried Chicken POS
    </h2>
    <p class="text-center text-gray-500 mb-6">
        Login Admin / Cabang
    </p>

    {{-- Pesan Sukses / Status --}}
    @if (session('status'))
        <div class="bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4 text-sm font-medium">
            {{ session('status') }}
        </div>
    @endif

    {{-- Pesan Error Session --}}
    @if (session('error'))
        <div class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm font-medium">
            {{ session('error') }}
        </div>
    @endif

    {{-- Pesan Error Validasi --}}
    @if ($errors->any())
        <div class="bg-red-100 border border-red-200 text-red-600 p-3 rounded-lg mb-4 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-4">
            <label class="text-sm font-medium text-gray-600">Nama Pengguna</label>
            <input type="text" name="name" value="{{ old('name') }}"
                class="w-full mt-1 p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-400 focus:outline-none"
                placeholder="Masukkan nama pengguna"
                required autofocus>
        </div>

        <div class="mb-5">
            <div class="flex justify-between items-center mb-1">
                <label class="text-sm font-medium text-gray-600">Password</label>
                @if (config('auth.enable_forgot_password', env('ENABLE_FORGOT_PASSWORD', true)))
                    <a href="{{ route('password.request') }}" 
                       class="text-xs text-orange-600 hover:text-orange-700 font-medium hover:underline">
                        Lupa Password?
                    </a>
                @endif
            </div>
            <input type="password" name="password"
                class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-400 focus:outline-none"
                placeholder="••••••••"
                required>
        </div>

        <button type="submit"
            class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2.5 rounded-lg shadow transition">
            Login
        </button>
    </form>

    <p class="text-center text-sm text-gray-500 mt-5">
        Belum punya akun?
        <a href="{{ route('register') }}" class="text-orange-600 font-semibold hover:underline">Register</a>
    </p>

</div>

</body>
</html>
