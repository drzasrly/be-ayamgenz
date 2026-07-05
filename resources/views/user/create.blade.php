@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
    body {
        background-color: #f8f9fa;
        font-family: 'Montserrat', sans-serif;
    }

    .judul-halaman {
        color: #e4002b;
        font-weight: 800;
        text-transform: uppercase;
        border-left: 5px solid #ffc107;
        padding-left: 15px;
    }

    .card-kfc {
        border-radius: 15px;
        overflow: hidden;
        border: none;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }

    .card-header-merah {
        background: linear-gradient(135deg, #e4002b, #b30022);
        color: white;
        padding: 18px 20px;
    }

    .card-header-hitam {
        background: linear-gradient(135deg, #2b2b2b, #000);
        color: white;
        padding: 18px 20px;
    }

    .btn-back-custom {
        border-radius: 8px;
        font-weight: 600;
        transition: 0.3s;
    }

    .btn-save-kfc {
        background: #e4002b;
        color: white;
        padding: 12px;
        border: none;
        border-radius: 8px;
        width: 100%;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: 0.3s;
    }

    .btn-save-kfc:hover {
        background: #b30022;
        box-shadow: 0 4px 12px rgba(228, 0, 43, 0.2);
    }

    .thead-kfc {
        background-color: #ffc107;
        color: #432C1E;
    }

    .thead-kfc th {
        font-weight: 700;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
        padding: 15px 10px;
    }

    .form-group-kfc {
        margin-bottom: 18px;
    }

    .form-group-kfc label {
        font-weight: 700;
        font-size: 12px;
        color: #495057;
        margin-bottom: 6px;
        text-transform: uppercase;
        display: block;
    }

    .form-control-kfc {
        width: 100%;
        padding: 10px 14px;
        border-radius: 8px;
        border: 1px solid #ced4da;
        font-family: inherit;
        font-size: 14px;
    }

    .form-control-kfc:focus {
        border-color: #e4002b;
        outline: none;
        box-shadow: 0 0 0 3px rgba(228, 0, 43, 0.1);
    }
</style>

<div class="container py-4">

    <!-- HEADER / TITLE -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ url('/dashboard') }}" class="btn btn-outline-secondary btn-sm btn-back-custom fw-bold">
                <i class="fas fa-arrow-left me-1"></i>
            </a>
            <div>
                <h3 class="judul-halaman mb-0">Manajemen User Cabang</h3>
                <p class="text-muted small mb-0">Kelola otorisasi akun staf outlet cabang Anda</p>
            </div>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm mb-4">
            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
        </div>
    @endif

    <div class="row g-4">
        
        <!-- LEFT: CREATE USER FORM -->
        <div class="col-lg-5">
            <div class="card card-kfc shadow-sm">
                <div class="card-header-merah">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-user-plus me-2"></i> Tambah User Baru</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('user.store') }}">
                        @csrf

                        <div class="form-group-kfc">
                            <label>Nama Lengkap</label>
                            <input type="text" name="name" class="form-control-kfc" placeholder="Masukkan nama user..." required>
                        </div>

                        <div class="form-group-kfc">
                            <label>Alamat Email</label>
                            <input type="email" name="email" class="form-control-kfc" placeholder="Masukkan email..." required>
                        </div>

                        <div class="form-group-kfc">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control-kfc" placeholder="Minimal 6 karakter..." required>
                        </div>

                        <div class="form-group-kfc">
                            <label>Pilih Cabang Bertugas</label>
                            <select name="cabang_id" class="form-control-kfc" required>
                                <option value="">-- Pilih Cabang --</option>
                                @foreach($cabangs as $c)
                                    <option value="{{ $c->id }}">{{ $c->nama_cabang }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn-save-kfc mt-2">
                            <i class="fas fa-save me-1"></i> Simpan User
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- RIGHT: USER LIST TABLE -->
        <div class="col-lg-7">
            <div class="card card-kfc shadow-sm">
                <div class="card-header-hitam d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-users me-2 text-danger"></i> Daftar User Aktif</h5>
                    <span class="badge bg-danger text-uppercase fw-semibold" style="font-size: 10px; padding: 5px 10px;">{{ count($users) }} Pegawai</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="thead-kfc">
                                <tr>
                                    <th class="px-4 text-dark">Nama</th>
                                    <th class="text-dark">Email</th>
                                    <th class="text-dark">Cabang</th>
                                    <th class="text-center text-dark" style="width: 80px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $u)
                                <tr>
                                    <td class="px-4 fw-semibold text-capitalize">{{ $u->name }}</td>
                                    <td class="text-muted">{{ $u->email }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark border fw-semibold" style="font-size: 11px;">
                                            <i class="fas fa-store text-danger me-1"></i> {{ $u->cabang?->nama_cabang ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <form action="{{ route('user.destroy', $u->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus user ini?')" class="m-0 d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger border-0 rounded-circle" style="width: 32px; height: 32px; padding: 0;">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="fas fa-user-slash fs-2 mb-2 d-block text-secondary"></i>
                                        Belum ada user cabang terdaftar.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection