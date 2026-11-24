<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Fried Chicken POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gradient-to-br from-orange-100 to-orange-300 min-h-screen flex items-center justify-center p-4">

<div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md">

    <div class="text-center mb-6">
        <h2 class="text-2xl font-bold text-orange-600">
            Fried Chicken POS
        </h2>

        <p class="text-sm text-gray-500 mt-3">
            Masukkan email akun Anda. Kami akan mengirimkan
            tautan untuk mengatur ulang password.
        </p>
    </div>

    @if (session('status'))
        <div class="bg-green-100 text-green-700 p-3 rounded-lg mb-4 text-sm">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="bg-red-50 text-red-600 p-3 rounded-lg mb-4 text-sm">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-4">
            <label class="text-sm font-medium text-gray-700">
                Email Akun
            </label>

            <input
                type="email"
                name="email"
                value="{{ old('email') }}"
                class="w-full mt-1 p-3 border rounded-lg"
                placeholder="nama@email.com"
                required
                autofocus
            >
        </div>

        <button
            type="submit"
            class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 rounded-lg">
            Kirim Link Reset Password
        </button>
    </form>

    <div class="text-center mt-5">
        <a href="{{ route('login') }}"
           class="text-sm text-gray-600 hover:text-orange-600">
            &larr; Kembali ke Login
        </a>
    </div>

</div>

</body>
</html>
