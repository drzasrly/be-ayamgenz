@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    body {
        background-color: #f8f9fa;
        font-family: 'Montserrat', sans-serif;
    }

    .profile-card {
        border-radius: 16px;
        overflow: hidden;
        border: none;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    }

    .card-header-kfc {
        background: linear-gradient(135deg, #e4002b 0%, #b30022 100%);
        color: white;
        padding: 1.5rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .btn-kfc {
        background-color: #e4002b;
        color: white;
        font-weight: 700;
        text-transform: uppercase;
        border: none;
        padding: 10px 24px;
        border-radius: 8px;
        transition: 0.2s;
    }

    .btn-kfc:hover {
        background-color: #b30022;
        box-shadow: 0 4px 12px rgba(228, 0, 43, 0.2);
    }
</style>

<div class="container py-4">
    <!-- Header -->
    <div class="mb-4">
        <h3 class="fw-bold text-dark"><i class="fas fa-user-cog text-danger me-2"></i>Pengaturan Profil</h3>
        <p class="text-muted small">Kelola informasi profil Anda dan ubah kata sandi akun.</p>
    </div>

    @if(session('status') === 'profile-updated')
        <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
            <i class="fas fa-check-circle me-2"></i> Informasi profil berhasil diperbarui!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('status') === 'password-updated')
        <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
            <i class="fas fa-check-circle me-2"></i> Kata sandi berhasil diperbarui!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Kolom Kiri: Informasi Profil -->
        <div class="col-lg-6">
            <div class="card profile-card">
                <div class="card-header card-header-kfc">
                    <i class="fas fa-id-card me-2"></i> Informasi Akun
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('patch')

                        <!-- Nama -->
                        <div class="mb-3">
                            <label for="name" class="form-label fw-bold small text-muted text-uppercase">Nama Lengkap</label>
                            <input type="text" name="name" id="name" class="form-control form-control-lg @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required autocomplete="name">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label fw-bold small text-muted text-uppercase">Alamat Email</label>
                            <input type="email" name="email" id="email" class="form-control form-control-lg @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required autocomplete="email">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Role (Readonly) -->
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted text-uppercase">Hak Akses / Peran</label>
                            <input type="text" class="form-control form-control-lg bg-light text-capitalize" value="{{ $user->role }}" readonly>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-kfc btn-lg">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Ganti Password -->
        <div class="col-lg-6">
            <div class="card profile-card">
                <div class="card-header card-header-kfc">
                    <i class="fas fa-key me-2"></i> Ganti Kata Sandi
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf
                        @method('put')

                        <!-- Password Lama -->
                        <div class="mb-3">
                            <label for="update_password_current_password" class="form-label fw-bold small text-muted text-uppercase">Kata Sandi Saat Ini</label>
                            <input type="password" name="current_password" id="update_password_current_password" class="form-control form-control-lg @error('current_password', 'updatePassword') is-invalid @enderror" required autocomplete="current-password">
                            @error('current_password', 'updatePassword')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Password Baru -->
                        <div class="mb-3">
                            <label for="update_password_password" class="form-label fw-bold small text-muted text-uppercase">Kata Sandi Baru</label>
                            <input type="password" name="password" id="update_password_password" class="form-control form-control-lg @error('password', 'updatePassword') is-invalid @enderror" required autocomplete="new-password">
                            @error('password', 'updatePassword')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Ulangi Password Baru -->
                        <div class="mb-4">
                            <label for="update_password_password_confirmation" class="form-label fw-bold small text-muted text-uppercase">Konfirmasi Kata Sandi Baru</label>
                            <input type="password" name="password_confirmation" id="update_password_password_confirmation" class="form-control form-control-lg @error('password_confirmation', 'updatePassword') is-invalid @enderror" required autocomplete="new-password">
                            @error('password_confirmation', 'updatePassword')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-kfc btn-lg">Ubah Kata Sandi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
