<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Fried Chicken POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-orange-100 to-orange-300 min-h-screen flex items-center justify-center p-4">

<div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md">
    
    <div class="text-center mb-6">
        <h2 class="text-2xl font-bold text-orange-600 mb-1">
            🍗 Fried Chicken POS
        </h2>
        <span class="inline-block bg-orange-100 text-orange-700 text-xs px-2.5 py-1 rounded-full font-semibold">
            Mode Pemulihan Password Sementara
        </span>
        <p class="text-xs text-gray-500 mt-2">
            Masukkan email akun Anda dan tentukan password baru untuk mengganti password lama.
        </p>
    </div>

    {{-- Pesan Status / Sukses --}}
    @if (session('status'))
        <div class="bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4 text-sm font-medium">
            {{ session('status') }}
        </div>
    @endif

    {{-- Pesan Error Validasi --}}
    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-600 p-3 rounded-lg mb-4 text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        {{-- Email Terdaftar --}}
        <div class="mb-4">
            <label class="text-sm font-medium text-gray-700">Email Akun Terdaftar</label>
            <input type="email" name="email" value="{{ old('email') }}"
                class="w-full mt-1 p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-400 focus:outline-none text-sm"
                placeholder="nama@email.com"
                required autofocus>
        </div>

        {{-- Password Baru --}}
        <div class="mb-4">
            <label class="text-sm font-medium text-gray-700">Password Baru</label>
            <input type="password" name="password"
                class="w-full mt-1 p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-400 focus:outline-none text-sm"
                placeholder="Minimal 6 karakter"
                required>
        </div>

        {{-- Konfirmasi Password Baru --}}
        <div class="mb-4">
            <label class="text-sm font-medium text-gray-700">Konfirmasi Password Baru</label>
            <input type="password" name="password_confirmation"
                class="w-full mt-1 p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-400 focus:outline-none text-sm"
                placeholder="Ulangi password baru"
                required>
        </div>

        {{-- Security PIN (Jika diaktifkan di .env) --}}
        @if ($requirePin ?? false)
            <div class="mb-4 bg-amber-50 p-3 rounded-lg border border-amber-200">
                <label class="text-sm font-semibold text-amber-800">PIN Keamanan Administrator</label>
                <input type="password" name="security_pin"
                    class="w-full mt-1 p-2 border border-amber-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:outline-none text-sm"
                    placeholder="Masukkan PIN keamanan"
                    required>
                <p class="text-xs text-amber-600 mt-1">Sistem memerlukan PIN otoritas dari administrator.</p>
            </div>
        @endif

        <button type="submit"
            class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2.5 rounded-lg shadow transition mt-2">
            Simpan Password Baru
        </button>
    </form>

    <div class="text-center mt-5">
        <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-orange-600 font-medium inline-flex items-center gap-1 transition">
            &larr; Kembali ke Halaman Login
        </a>
    </div>

</div>

</body>
</html>
