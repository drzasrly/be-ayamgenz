@extends('layouts.app')

@section('content')

<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    body {
        background: #f4f6f9;
        font-family: 'Montserrat', sans-serif;
    }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }

    .title {
        font-weight: 800;
        font-size: 20px;
    }

    .subtitle {
        font-size: 12px;
        color: #888;
    }

    .summary-card {
        background: white;
        border-radius: 12px;
        padding: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }

    .summary-title {
        font-size: 11px;
        color: #888;
        font-weight: 700;
    }

    .summary-value {
        font-size: 20px;
        font-weight: 800;
    }

    .table-container {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 10px 25px rgba(0,0,0,0.05);
    }

    .table thead {
        background: #fafafa;
    }

    .table th {
        font-size: 11px;
        text-transform: uppercase;
        color: #999;
    }

    .table tbody tr:hover {
        background: #fff5f6;
    }

    .menu-name {
        font-weight: 700;
        font-size: 14px;
    }

    .menu-id {
        font-size: 11px;
        color: #aaa;
    }

    .badge-qty {
        background: #fff3cd;
        color: #856404;
        font-weight: 700;
        border-radius: 20px;
        padding: 5px 10px;
    }

    .total {
        font-weight: 800;
        color: #e4002b;
    }

    .btn-kfc {
        background: #e4002b;
        color: white;
        border-radius: 8px;
        font-weight: 600;
    }

    .btn-back {
        border-radius: 8px;
    }
</style>

<div class="container-fluid px-0 px-sm-2 px-md-3 py-2 py-md-4">

    <!-- HEADER -->
    <div class="header flex-wrap gap-2">

        <div class="d-flex align-items-center gap-2 gap-sm-3">
            <a href="{{ url('/dashboard') }}" class="btn btn-outline-secondary btn-back btn-sm py-1.5 px-2.5">
                <i class="fas fa-arrow-left"></i>
            </a>

            <div>
                <div class="title fs-5 fs-sm-4">Riwayat Transaksi</div>
                <div class="subtitle">Semua transaksi hari ini</div>
            </div>
        </div>

        <a href="{{ route('kasir.create') }}" class="btn btn-kfc fw-bold shadow-sm px-3 py-2">
            <i class="fas fa-plus me-1"></i> Transaksi
        </a>
    </div>

    <!-- SUMMARY -->
    <div class="row g-2 g-md-3 mb-3 mb-md-4">
        <div class="col-6 col-md-4">
            <div class="summary-card p-3">
                <div class="summary-title">TOTAL OMSET</div>
                <div class="summary-value text-danger fs-6 fs-sm-5 fs-md-4">
                    Rp {{ number_format($transaksi->sum('total'), 0, ',', '.') }}
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4">
            <div class="summary-card p-3">
                <div class="summary-title">ITEM TERJUAL</div>
                <div class="summary-value fs-6 fs-sm-5 fs-md-4">
                    {{ $transaksi->sum('qty') }} Produk
                </div>
            </div>
        </div>
    </div>

    <!-- TABLE -->
    <div class="table-container table-responsive">
        <table class="table table-hover mb-0 align-middle">

            <thead>
                <tr>
                    <th>no</th>
                    <th>Waktu</th>
                    <th>Produk</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">Total</th>
                    <th class="text-center">Pembayaran</th> <!-- 🔥 TAMBAHAN -->
                    <th class="text-center">Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse($transaksi as $t)
                <tr>

                    <td class="fw-bold text-muted">
                        {{ $loop->iteration }}
                    </td>

                    <td>
                        <div>{{ $t->created_at->format('d M Y') }}</div>
                        <small class="text-muted">
                            {{ $t->created_at->format('H:i') }}
                        </small>
                    </td>

                    <td>
                        <div class="menu-name">
                            {{ $t->menu->nama_menu ?? 'Menu Terhapus' }}
                        </div>
                        <div class="menu-id">
                            #TX-{{ $t->id }}
                        </div>
                    </td>

                    <td class="text-center">
                        <span class="badge-qty">{{ $t->qty }}</span>
                    </td>

                    <td class="text-end total">
                        Rp {{ number_format($t->total, 0, ',', '.') }}
                    </td>

                    <!-- 🔥 METODE PEMBAYARAN -->
                    <td class="text-center">
                        @if($t->metode_pembayaran == 'cash')
                            <span class="badge bg-success">💵 Cash</span>
                        @elseif($t->metode_pembayaran == 'qris')
                            <span class="badge bg-primary">📱 QRIS</span>
                        @else
                            <span class="badge bg-secondary">-</span>
                        @endif
                    </td>

                    <!-- 🔥 STATUS PEMBAYARAN -->
                    <td class="text-center">
                        @if($t->status == 'success')
                            <span class="badge bg-success rounded-pill px-3"><i class="fas fa-check-circle me-1"></i> Sukses</span>
                        @elseif($t->status == 'pending')
                            <span class="badge bg-warning text-dark rounded-pill px-3"><i class="fas fa-clock me-1"></i> Pending</span>
                        @elseif($t->status == 'failed')
                            <span class="badge bg-danger rounded-pill px-3"><i class="fas fa-times-circle me-1"></i> Gagal</span>
                        @else
                            <span class="badge bg-secondary rounded-pill px-3">{{ $t->status }}</span>
                        @endif
                    </td>

                    
                    <td class="text-center">
                        <div class="d-flex justify-content-center gap-1">

                            <a href="/kasir/edit/{{ $t->id }}"
                            class="btn btn-sm btn-outline-warning"
                            title="Edit transaksi">
                                <i class="fas fa-edit"></i>
                            </a>

                            @if($t->status === 'success' && $t->invoice)
                                <a href="{{ route('kasir.nota', ['invoice' => $t->invoice]) }}"
                                target="_blank"
                                class="btn btn-sm btn-outline-primary"
                                title="Cetak nota">
                                    <i class="fas fa-print"></i>
                                </a>
                            @endif

                        </div>
                    </td>


                </tr>

                @empty
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="fas fa-receipt fa-3x mb-3 opacity-25"></i>
                        <p class="mb-0">Belum ada transaksi hari ini</p>
                    </td>
                </tr>
                @endforelse
            </tbody>

        </table>
    </div>

</div>

@endsection