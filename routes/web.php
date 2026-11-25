<?php

use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\CabangController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\StokController;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ResepController;
use App\Http\Controllers\LaporanController;

use App\Http\Controllers\DokuController;

// Doku Payment Gateway Webhook (Harus di luar Auth Middleware)
// Route::post('/doku/callback', [DokuController::class, 'callback']);

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/
Route::get('/storage/{path}', function ($path) {
    $fullPath = storage_path('app/public/' . $path);
    if (!file_exists($fullPath)) { 
        abort(404); 
    }
    return response()->file($fullPath);
})->where('path', '.*');

Route::get('/', function () {
    return redirect('/login');
});

/*
|--------------------------------------------------------------------------
| AUTHENTICATION (GUEST)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    // Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    // Route::post('/register', [RegisteredUserController::class, 'store']);

    // Lupa Password
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    // Reset Password menggunakan token
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});



/*
|--------------------------------------------------------------------------
| SEMUA USER LOGIN (ADMIN & PEGAWAI/CABANG)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    // Dashboard & Profile
    Route::get('/dashboard', [HomeController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Update password
    Route::put('/password', [PasswordController::class, 'update'])
        ->name('password.update');

    // ==========================================
    // EMAIL VERIFICATION
    // ==========================================

    // Menampilkan halaman pemberitahuan verifikasi email
    Route::get('/verify-email', [EmailVerificationPromptController::class, '__invoke'])
        ->name('verification.notice');

    // Memproses link verifikasi email
    Route::get('/verify-email/{id}/{hash}', [VerifyEmailController::class, '__invoke'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    // Mengirim ulang email verifikasi
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // ==========================================
    // PASSWORD CONFIRMATION
    // ==========================================

    // Menampilkan halaman konfirmasi password
    Route::get('/confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    // Memproses konfirmasi password
    Route::post('/confirm-password', [ConfirmablePasswordController::class, 'store']);

    // Kasir dan transaksi
    Route::get('/kasir', [KasirController::class, 'index'])
        ->name('kasir.index');

    Route::get('/kasir/create', [KasirController::class, 'create'])
        ->name('kasir.create');

    Route::post('/kasir', [KasirController::class, 'store'])
        ->name('kasir.store');

    Route::post('/checkout', [KasirController::class, 'checkout'])
        ->name('checkout');

    // Route::get('/kasir/nota/{invoice}', [KasirController::class, 'cetakNota'])
    //     ->name('kasir.nota');
    
    // Menu (Akses Lihat)
    Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');

    // Manajemen Stok
    Route::get('/stok', [StokController::class, 'index'])->name('stok.index');
    Route::get('/menu/{id}/stok', [StokController::class, 'editMenuStok'])->name('menu.stok.edit');
    Route::post('/menu/{id}/stok', [StokController::class, 'updateMenuStok'])->name('menu.stok.update');

    // Manajemen Bahan Mentah
    Route::get('/stok/bahan/create', [StokController::class, 'createBahan'])->name('stok.bahan.create');
    Route::post('/stok/bahan/store', [StokController::class, 'storeBahan'])->name('stok.bahan.store');
    Route::get('/stok/bahan/{id}/edit', [StokController::class, 'editBahan'])->name('stok.bahan.edit');
    Route::post('/stok/bahan/{id}/update', [StokController::class, 'updateBahan'])->name('stok.bahan.update');
    Route::delete('/stok/bahan/{id}', [StokController::class, 'destroyBahan'])->name('stok.bahan.destroy');

    /* 
    |--------------------------------------------------------------------------
    | FITUR KERANJANG (CART)
    |--------------------------------------------------------------------------
    | Menggunakan MenuController untuk fitur Tambah/Kurang agar lebih praktis.
    */
    Route::get('/cart', [KasirController::class, 'cart'])->name('cart');
    
    Route::delete('/cart/remove/{id}', [KasirController::class, 'removeCart'])
        ->name('cart.remove');


    // Fitur Tambah (+) dan Kurang (-) qty di Card Menu
   Route::post('/cart/add/{id}', [MenuController::class, 'add'])
        ->name('cart.add');

    Route::post('/cart/decrease/{id}', [MenuController::class, 'decrease'])
        ->name('cart.decrease');

    // // Doku Payment Gateway Return
    // Route::get('/doku/return', [KasirController::class, 'dokuReturn'])->name('doku.return');

    Route::get('/kasir/nota/{invoice}', [KasirController::class, 'cetakNota'])
    ->name('kasir.nota')
    ->middleware('auth');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->name('logout');
});

/*
|--------------------------------------------------------------------------
| KHUSUS ROLE: ADMIN
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->group(function () {

    // Manajemen Cabang & User
    Route::resource('cabang', CabangController::class);
    Route::get('/user/create', [UserController::class, 'create'])->name('user.create');
    Route::post('/user/store', [UserController::class, 'store'])->name('user.store');
    Route::delete('/user/{id}', [UserController::class, 'destroy'])->name('user.destroy');

    // Manajemen Menu Lengkap (Create, Edit, Delete)
    Route::post('/menu/{id}/toggle-status', [MenuController::class, 'toggleStatus'])
        ->name('menu.toggleStatus');

    Route::resource('menu', MenuController::class)->except(['index']);

    // Laporan Operasional
    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/pdf', [LaporanController::class, 'exportPdf'])->name('laporan.pdf');
    Route::get('/laporan/excel', [LaporanController::class, 'exportExcel'])->name('laporan.excel');
});