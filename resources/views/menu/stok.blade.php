@extends('layouts.app')

@section('content')

<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    body {
        background-color: #f8f9fa;
        font-family: 'Montserrat', sans-serif;
    }

    .form-edit-wrapper {
        max-width: 500px;
        margin: 0 auto;
    }

    /* HEADER */
    .card-header-stok {
        background: linear-gradient(135deg, #e4002b 0%, #b30022 100%);
        color: white;
        border-radius: 15px 15px 0 0 !important;
        padding: 1.25rem;
    }

    .card-header-stok h5 {
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-size: 1rem;
    }

    /* INPUT */
    .label-custom {
        font-weight: 700;
        font-size: 11px;
        text-transform: uppercase;
        color: #555;
        margin-bottom: 6px;
        display: block;
    }

    .form-control-custom {
        font-size: 14px;
        padding: 10px 12px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
    }

    .form-control-custom:focus {
        border-color: #e4002b;
        box-shadow: 0 0 0 0.2rem rgba(228, 0, 43, 0.1);
    }

    /* BUTTON */
    .btn-submit {
        background-color: #e4002b;
        color: white;
        font-weight: 800;
        text-transform: uppercase;
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        font-size: 13px;
    }

    .btn-submit:hover {
        background-color: #b30022;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(228, 0, 43, 0.3);
    }

    @media (max-width: 576px) {
        .d-flex-mobile {
            flex-direction: column-reverse;
            gap: 10px;
        }

        .btn-submit, .btn-light {
            width: 100%;
        }
    }
</style>

<div class="container py-4 py-md-5">
    <div class="form-edit-wrapper">

        <!-- BACK -->
        <div class="mb-3">
            <a href="{{ route('stok.index', ['cabang_id' => $selectedCabangId]) }}" class="text-decoration-none text-muted small fw-bold">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Inventaris
            </a>
        </div>

        <!-- CARD -->
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden">

            <div class="card-header card-header-stok border-0">
                <div class="d-flex align-items-center">
                    <div class="bg-white bg-opacity-25 p-2 rounded-3 me-3">
                        <i class="fas fa-boxes text-white"></i>
                    </div>
                    <div>
                        <h5 class="mb-0">Atur Stok Menu Cabang</h5>
                        <small class="text-white-50" style="font-size: 11px;">Stok hanya berlaku untuk cabang terkait.</small>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">

                <form action="{{ route('menu.stok.update', $menu->id) }}" method="POST">
                    @csrf

                    <!-- CABANG TARGET -->
                    @if(auth()->user()->role == 'admin')
                        <div class="mb-3">
                            <label class="label-custom text-primary">Cabang Tujuan</label>
                            <select name="cabang_id" class="form-select form-control-custom fw-bold shadow-sm" required onchange="window.location.href = '{{ route('menu.stok.edit', $menu->id) }}?cabang_id=' + this.value">
                                @foreach($cabangs as $c)
                                    <option value="{{ $c->id }}" {{ $selectedCabangId == $c->id ? 'selected' : '' }}>
                                        📍 {{ $c->nama_cabang }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted" style="font-size: 10px;">
                                Mengubah pilihan cabang akan memuat sisa stok cabang tersebut.
                            </small>
                        </div>
                    @else
                        <input type="hidden" name="cabang_id" value="{{ auth()->user()->cabang_id }}">
                        <div class="mb-3">
                            <label class="label-custom">Cabang Anda</label>
                            <div class="p-2.5 bg-light rounded-3 border fw-bold text-dark" style="font-size: 13px;">
                                <i class="fas fa-store me-2 text-danger"></i> {{ auth()->user()->cabang->nama_cabang ?? 'Cabang Anda' }}
                            </div>
                        </div>
                    @endif

                    <!-- NAMA MENU -->
                    <div class="mb-3">
                        <label class="label-custom">Nama Menu</label>
                        <input type="text" 
                               class="form-control form-control-custom fw-bold shadow-sm bg-light" 
                               value="{{ $menu->nama_menu }}" readonly>
                    </div>

                    <!-- STOK SAAT INI DI CABANG INI -->
                    <div class="mb-3">
                        <label class="label-custom">Sisa Stok Saat Ini (Di Cabang Ini)</label>
                        <div class="input-group">
                            <input type="text" 
                                   class="form-control form-control-custom shadow-sm bg-light fw-bold {{ $currentStok <= 5 ? 'text-danger' : 'text-dark' }}" 
                                   value="{{ $currentStok }}" readonly>
                            <span class="input-group-text bg-light border-start-0 text-muted small">Porsi / Pcs</span>
                        </div>
                    </div>

                    <!-- TAMBAH STOK -->
                    <div class="mb-3">
                        <label class="label-custom text-danger">Tambah Stok (+)</label>
                        <input type="number" name="stok"
                               class="form-control form-control-custom shadow-sm @error('stok') is-invalid @enderror"
                               min="1" 
                               placeholder="Contoh: 20"
                               required autofocus>

                        @error('stok')
                            <div class="text-danger mt-1" style="font-size: 11px;">
                                {{ $message }}
                            </div>
                        @enderror
                        <small class="text-muted mt-1 d-block" style="font-size: 11px;">
                            Masukkan jumlah porsi yang ditambahkan ke stok cabang ini.
                        </small>
                    </div>

                    <hr class="my-4 opacity-25">

                    <!-- BUTTON -->
                    <div class="d-flex justify-content-end gap-2 d-flex-mobile">

                        <a href="{{ route('stok.index', ['cabang_id' => $selectedCabangId]) }}" class="btn btn-light px-4 fw-bold text-muted border">
                            Batal
                        </a>

                        <button type="submit" class="btn btn-submit shadow-sm">
                            <i class="fas fa-plus-circle me-1"></i> Simpan Stok Cabang
                        </button>

                    </div>

                </form>

            </div>
        </div>

    </div>
</div>

@endsection
