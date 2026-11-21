@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    body {
        background-color: #f4f7f6;
        font-family: 'Montserrat', sans-serif;
    }

    /* --- HEADER POS --- */
    .card-header-kfc {
        background: linear-gradient(135deg, #e4002b, #b30022);
        color: white;
        padding: 14px 18px;
        border-bottom: 4px solid #ffc107;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-radius: 16px 16px 0 0;
        gap: 8px;
    }

    .btn-back-kfc {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.4);
        border-radius: 10px;
        padding: 6px 12px;
        font-size: 13px;
        font-weight: 600;
        transition: 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
    }

    .btn-back-kfc:hover {
        background: white;
        color: #e4002b;
    }

    /* --- SEARCH & CATEGORY PILLS --- */
    .category-scroll {
        display: flex;
        gap: 6px;
        overflow-x: auto;
        padding-bottom: 4px;
        scrollbar-width: none; /* Firefox */
    }
    .category-scroll::-webkit-scrollbar {
        display: none; /* Chrome, Safari, Edge */
    }

    .cat-pill {
        white-space: nowrap;
        font-size: 11px;
        font-weight: 700;
        padding: 5px 12px;
        border-radius: 20px;
        border: 1px solid #dee2e6;
        background: white;
        color: #6c757d;
        cursor: pointer;
        transition: 0.2s;
    }

    .cat-pill.active, .cat-pill:hover {
        background: #e4002b;
        color: white;
        border-color: #e4002b;
    }

    /* --- GRID MENU RESPONSIVE --- */
    .menu-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr); 
        gap: 10px;
    }
    @media (min-width: 576px) { .menu-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; } }
    @media (min-width: 768px) { .menu-grid { grid-template-columns: repeat(3, 1fr); gap: 12px; } }
    @media (min-width: 1200px) { .menu-grid { grid-template-columns: repeat(4, 1fr); gap: 14px; } }

    .menu-card {
        background: #fff;
        border-radius: 14px;
        padding: 8px;
        text-align: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        transition: transform 0.2s, box-shadow 0.2s;
        border: 1px solid #f0f0f0;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    @media (min-width: 768px) {
        .menu-card {
            padding: 12px;
        }
    }

    .menu-card:hover:not(.out-of-stock) { 
        transform: translateY(-3px); 
        box-shadow: 0 6px 15px rgba(0,0,0,0.08);
    }

    .menu-card.out-of-stock {
        opacity: 0.65;
        background: #fafafa;
    }
    
    .menu-card.out-of-stock img {
        filter: grayscale(1);
    }

    .menu-img-wrap {
        position: relative;
        width: 100%;
        height: 95px;
        margin-bottom: 6px;
        border-radius: 10px;
        overflow: hidden;
        background: #f8f9fa;
    }

    @media (min-width: 768px) {
        .menu-img-wrap {
            height: 115px;
        }
    }

    .menu-img-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .badge-stock {
        position: absolute;
        top: 5px;
        right: 5px;
        font-size: 9px;
        font-weight: 700;
        padding: 2px 6px;
        border-radius: 6px;
    }

    .menu-title {
        font-weight: 700;
        font-size: 12px;
        line-height: 1.25;
        color: #212529;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 30px;
        margin-bottom: 4px;
    }

    @media (min-width: 768px) {
        .menu-title {
            font-size: 13px;
            min-height: 34px;
        }
    }

    .menu-price {
        color: #e4002b;
        font-weight: 800;
        font-size: 12px;
        margin-bottom: 6px;
    }

    @media (min-width: 768px) {
        .menu-price {
            font-size: 13px;
        }
    }

    /* --- QTY CONTROL --- */
    .qty-control {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        margin-top: auto;
    }

    .btn-qty {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        border: 1px solid #e4002b;
        background: #fff;
        color: #e4002b;
        transition: 0.15s;
        text-decoration: none;
        font-weight: bold;
        font-size: 12px;
    }

    .btn-qty:hover, .btn-qty:active {
        background: #e4002b;
        color: white;
    }

    .qty-count {
        min-width: 24px;
        text-align: center;
        font-weight: 800;
        font-size: 13px;
    }

    /* --- CART SECTION --- */
    .cart-box {
        background: #fff;
        border-radius: 16px;
        padding: 16px;
        box-shadow: 0 6px 20px rgba(0,0,0,0.05);
        border: 1px solid #eee;
    }

    @media (min-width: 768px) {
        .cart-box {
            padding: 22px;
        }
    }

    .checkout-btn {
        background-color: #28a745;
        border: none;
        font-weight: 800;
        padding: 12px;
        border-radius: 12px;
        transition: 0.2s;
        color: white;
        font-size: 14px;
        letter-spacing: 0.5px;
    }

    .checkout-btn:hover, .checkout-btn:active { 
        background-color: #218838; 
        color: white;
    }

    /* --- STICKY FLOATING CART BAR ON MOBILE --- */
    .mobile-cart-bar {
        position: fixed;
        bottom: 12px;
        left: 12px;
        right: 12px;
        z-index: 1040;
        background: #1f2937;
        color: white;
        border-radius: 14px;
        padding: 10px 16px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.25);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Spacing for mobile bottom bar */
    @media (max-width: 991px) {
        .pos-container {
            padding-bottom: 70px;
        }
    }
</style>

<div class="container-fluid px-0 px-sm-2 px-md-3 py-1 py-md-3 pos-container">

    {{-- Error Alert --}}
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-3 mx-2 mx-sm-0" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Success Alert --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-3 mx-2 mx-sm-0" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm" style="border-radius: 16px; overflow: hidden;">
        
        <!-- HEADER KASIR -->
        <div class="card-header card-header-kfc">
            <a href="{{ url('/dashboard') }}" class="btn-back-kfc">
                <i class="fas fa-arrow-left me-1"></i> <span class="d-none d-sm-inline">Kembali</span>
            </a>
            
            <div class="text-center">
                <h5 class="mb-0 fw-bold fs-6 fs-sm-5"><i class="fas fa-cash-register me-1"></i> POS KASIR</h5>
            </div>

            <!-- Tombol Cepat Loncat ke Keranjang di Mobile -->
            <a href="#cart-section" class="btn btn-warning btn-sm fw-bold rounded-pill d-lg-none position-relative px-3 py-1 shadow-sm text-dark" style="font-size: 12px;">
                <i class="fas fa-shopping-cart me-1"></i>
                <span class="d-none d-xs-inline">Cart</span>
                @if($cart->count() > 0)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger text-white">
                        {{ $cart->sum('qty') }}
                    </span>
                @endif
            </a>
            <div class="d-none d-lg-block" style="width: 80px;"></div>
        </div>

        <!-- BAR INFORMASI CABANG KASIR -->
        <div class="px-3 pt-3">
            @if(auth()->user()->role == 'admin')
            <div class="bg-light p-2.5 px-3 rounded-3 border d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-store text-danger"></i>
                    <span class="fw-bold small text-muted">Kasir Cabang Aktif:</span>
                    <form method="GET" action="{{ route('kasir.create') }}" class="m-0">
                        <select name="cabang_id" class="form-select form-select-sm fw-bold border-danger" onchange="this.form.submit()">
                            @foreach($cabang as $c)
                                <option value="{{ $c->id }}" {{ ($targetCabang ?? null) == $c->id ? 'selected' : '' }}>
                                    📍 {{ $c->nama_cabang }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
                <span class="badge bg-danger text-white" style="font-size: 10px;">Super Admin Mode</span>
            </div>
            @else
            <div class="bg-light p-2.5 px-3 rounded-3 border d-flex align-items-center gap-2">
                <i class="fas fa-store text-danger"></i>
                <span class="small text-muted">Kasir Cabang:</span>
                <span class="badge bg-danger text-white fw-bold">{{ auth()->user()->cabang->nama_cabang ?? 'Cabang Anda' }}</span>
            </div>
            @endif
        </div>

        <div class="card-body p-2 p-sm-3 p-md-4">
            <div class="row g-3 g-lg-4">
                
                <!-- ============================================== -->
                <!-- LEFT COLUMN: DAFTAR MENU -->
                <!-- ============================================== -->
                <div class="col-lg-7">
                    
                    <!-- PENCARIAN & FILTER KATEGORI -->
                    <div class="bg-white p-2 p-sm-3 rounded-3 shadow-sm border mb-3">
                        <div class="input-group mb-2">
                            <span class="input-group-text bg-light border-0 text-muted">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" id="searchMenuInput" 
                                   class="form-control bg-light border-0" 
                                   placeholder="Cari nama menu..." 
                                   style="font-size: 13px;"
                                   onkeyup="filterMenuItems()">
                            <button class="btn btn-light border-0 text-muted" type="button" onclick="clearSearch()" id="clearSearchBtn" style="display: none;">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        @php
                            $categories = $menu->pluck('kategori')->filter()->unique();
                        @endphp

                        @if($categories->count() > 0)
                            <div class="category-scroll pt-1">
                                <button type="button" class="cat-pill active" onclick="filterCategory('all', this)">
                                    Semua ({{ $menu->count() }})
                                </button>
                                @foreach($categories as $cat)
                                    @php
                                        $countCat = $menu->where('kategori', $cat)->count();
                                    @endphp
                                    <button type="button" class="cat-pill" onclick="filterCategory('{{ strtolower($cat) }}', this)">
                                        {{ ucfirst($cat) }} ({{ $countCat }})
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- GRID MENU -->
                    <div class="menu-grid" id="menuGridContainer">
                        @forelse($menu as $m)
                            @php
                                $itemInCart = $cart
                                    ->where('menu_id', $m->id)
                                    ->where('cabang_id', $targetCabang)
                                    ->first();

                                // Stok harus berlaku untuk SEMUA menu
                                $stokTersedia = max(0, (int) ($m->stok ?? 0));

                                // Stok kosong berarti habis, tanpa pengecualian nama menu
                                $isOutOfStock = $stokTersedia <= 0;

                                // Batas jumlah pesanan
                                $qtyDiKeranjang = $itemInCart ? (int) $itemInCart->qty : 0;
                                $batasStok = $qtyDiKeranjang >= $stokTersedia;

                                $kategoriSlug = strtolower($m->kategori ?? 'lainnya');
                            @endphp

                            <div class="menu-card {{ $isOutOfStock ? 'out-of-stock' : '' }}" 
                                 data-name="{{ strtolower($m->nama_menu) }}" 
                                 data-category="{{ $kategoriSlug }}">
                                
                                <div class="menu-img-wrap">
                                    <img src="{{ $m->gambar ? asset('storage/'.$m->gambar) : 'https://placehold.co/200x150/fffae6/e4002b?text='.urlencode($m->nama_menu) }}" 
                                         alt="{{ $m->nama_menu }}"
                                         loading="lazy">
                                    
                                    @if($isOutOfStock)
                                        <span class="badge bg-danger badge-stock">HABIS</span>
                                    @elseif($stokTersedia <= 5)
                                        <span class="badge bg-warning text-dark badge-stock">
                                            Sisa {{ $stokTersedia }}
                                        </span>
                                    @else
                                        <span class="badge bg-success badge-stock">
                                            Stok {{ $stokTersedia }}
                                        </span>
                                    @endif
                                </div>

                                <div class="menu-title" title="{{ $m->nama_menu }}">
                                    {{ $m->nama_menu }}
                                </div>
                                
                                <div class="menu-price">
                                    Rp {{ number_format($m->harga, 0, ',', '.') }}
                                </div>
                                
                                @if($isOutOfStock)

                                    {{-- STOK HABIS --}}
                                    <button type="button"
                                            class="btn btn-secondary btn-sm w-100 rounded-pill fw-bold py-1"
                                            style="font-size: 11px;"
                                            disabled>
                                        <i class="fas fa-ban me-1"></i> Stok Habis
                                    </button>

                                @elseif($itemInCart)

                                    {{-- MENU SUDAH ADA DI KERANJANG --}}
                                    <div class="qty-control">

                                        <form action="{{ route('cart.decrease', $m->id) }}"
                                            method="POST"
                                            class="m-0">
                                            @csrf
                                            <button type="submit" class="btn-qty" title="Kurangi">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                        </form>

                                        <span class="qty-count text-dark">
                                            {{ $itemInCart->qty }}
                                        </span>

                                        @if($batasStok)

                                            {{-- JUMLAH SUDAH MENCAPAI STOK --}}
                                            <button type="button"
                                                    class="btn-qty"
                                                    disabled
                                                    title="Stok tidak mencukupi"
                                                    style="opacity: 0.4; cursor: not-allowed;">
                                                <i class="fas fa-plus"></i>
                                            </button>

                                        @else

                                            <form action="{{ route('cart.add', $m->id) }}"
                                                method="POST"
                                                class="m-0">
                                                @csrf
                                                <button type="submit" class="btn-qty" title="Tambah">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </form>

                                        @endif

                                    </div>

                                @else

                                    {{-- MENU BELUM MASUK KERANJANG --}}
                                    <form action="{{ route('cart.add', $m->id) }}" method="POST">
                                        @csrf
                                        <button type="submit"
                                                class="btn btn-outline-danger btn-sm w-100 rounded-pill fw-bold py-1"
                                                style="font-size: 12px;">
                                            <i class="fas fa-plus me-1"></i> Tambah
                                        </button>
                                    </form>

                                @endif
                            </div>
                        @empty
                            <div class="col-12 text-center py-5">
                                <i class="fas fa-utensils fa-3x text-muted mb-2 opacity-50"></i>
                                <p class="text-muted small">Belum ada menu di cabang ini.</p>
                            </div>
                        @endforelse
                    </div>

                    <!-- Notif jika hasil filter pencarian kosong -->
                    <div id="noMenuResult" class="text-center py-5 d-none bg-white rounded-3 shadow-sm mt-3">
                        <i class="fas fa-search fa-2x text-muted mb-2 opacity-50"></i>
                        <p class="text-muted small mb-0">Menu yang dicari tidak ditemukan.</p>
                    </div>

                </div>

                <!-- ============================================== -->
                <!-- RIGHT COLUMN: SHOPPING CART & PEMBAYARAN -->
                <!-- ============================================== -->
                <div class="col-lg-5" id="cart-section">
                    
                    <div class="cart-box">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0 fs-6 fs-sm-5">
                                <i class="fas fa-shopping-cart me-2 text-success"></i> Keranjang Pesanan
                            </h5>
                            @if($cart->count() > 0)
                                <span class="badge bg-danger rounded-pill px-2.5 py-1" style="font-size: 11px;">
                                    {{ $cart->sum('qty') }} Item
                                </span>
                            @endif
                        </div>

                        <!-- LIST ITEM DI KERANJANG -->
                        <div class="cart-items" style="max-height: 320px; overflow-y: auto;">
                            @php $total = 0; @endphp
                            @forelse($cart as $item)
                                @php 
                                    $subtotal = ($item->menu->harga ?? 0) * $item->qty;
                                    $total += $subtotal;
                                @endphp
                                <div class="d-flex justify-content-between align-items-center border-bottom py-2.5 px-1">
                                    <div class="pe-2" style="flex: 1; min-width: 0;">
                                        <div class="fw-bold text-dark text-truncate" style="font-size: 13px;">
                                            {{ $item->menu->nama_menu ?? 'Menu Terhapus' }}
                                        </div>
                                        <div class="text-muted" style="font-size: 11px;">
                                            {{ $item->qty }} x Rp {{ number_format($item->menu->harga ?? 0, 0, ',', '.') }}
                                        </div>
                                    </div>
                                    <div class="text-end d-flex align-items-center gap-2">
                                        <div class="fw-bold text-danger" style="font-size: 13px;">
                                            Rp {{ number_format($subtotal, 0, ',', '.') }}
                                        </div>
                                        <form action="{{ route('cart.remove', $item->id) }}"
                                            method="POST"
                                            class="m-0">
                                            @csrf
                                            <button type="submit"
                                                    class="btn btn-sm btn-light text-danger rounded-circle p-1"
                                                    style="width: 28px; height: 28px;">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-shopping-basket fa-3x text-secondary mb-2 opacity-25"></i>
                                    <p class="mb-0 small fw-semibold">Keranjang masih kosong</p>
                                    <small class="text-muted">Pilih menu di sebelah untuk menambahkan</small>
                                </div>
                            @endforelse
                        </div>

                        <!-- TOTAL & CHECKOUT -->
                        @if($cart->count() > 0)
                        <div class="mt-3 pt-3 border-top">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <span class="text-muted small">Total Pembayaran</span>
                                    <h4 class="fw-bold text-danger mb-0 fs-5 fs-sm-4">
                                        Rp {{ number_format($total, 0, ',', '.') }}
                                    </h4>
                                </div>
                                <span class="badge bg-light text-dark border px-2 py-1 small">
                                    {{ $cart->count() }} Variasi Menu
                                </span>
                            </div>

                            <form action="{{ route('checkout') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="fw-bold small mb-1 text-muted" style="font-size: 12px;">Metode Pembayaran</label>
                                    <select name="metode_pembayaran" class="form-select bg-light border-0 py-2.5 fw-semibold" style="font-size: 13px;" required>
                                        <option value="">-- Pilih Cara Bayar --</option>
                                        <option value="cash">💵 Cash (Tunai)</option>
                                        <option value="qris">📱 QRIS / Digital Payment</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-success w-100 checkout-btn shadow-sm py-3">
                                    <i class="fas fa-check-circle me-2"></i> SELESAIKAN PESANAN
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>

                    <!-- FORM INPUT CEPAT / MANUAL -->
                    <div class="mt-3 p-3 bg-white shadow-sm border" style="border-radius: 14px;">
                        <h6 class="text-muted small fw-bold mb-2">
                            <i class="fas fa-keyboard me-1 text-secondary"></i> INPUT CEPAT
                        </h6>
                        <form action="{{ route('kasir.store') }}" method="POST">
                            @csrf
                            <div class="row g-2 align-items-center">
                                <div class="col-12 col-sm-6">
                                    <select name="menu_id" class="form-select form-select-sm bg-light border-0 py-2" style="font-size: 12px;" required>
                                        <option value="">-- Pilih Menu --</option>
                                        @foreach($menu as $m)

                                            @php
                                                $stokMenu = max(0, (int) ($m->stok ?? 0));
                                            @endphp

                                            <option value="{{ $m->id }}"
                                                    {{ $stokMenu <= 0 ? 'disabled' : '' }}>

                                                {{ $m->nama_menu }}
                                                - Rp {{ number_format($m->harga, 0, ',', '.') }}

                                                @if($stokMenu <= 0)
                                                    (HABIS)
                                                @else
                                                    (Stok: {{ $stokMenu }})
                                                @endif

                                            </option>

                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6 col-sm-3">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-0 text-muted" style="font-size: 11px;">Qty</span>
                                        <input type="number" name="qty" class="form-control bg-light border-0 py-2 text-center" value="1" min="1" required style="font-size: 12px;">
                                    </div>
                                </div>
                                <div class="col-6 col-sm-3">
                                    <button type="submit" class="btn btn-dark btn-sm w-100 py-2 fw-semibold" style="font-size: 12px;">
                                        <i class="fas fa-plus me-1"></i> Tambah
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>

            </div>
        </div>
    </div>
</div>

{{-- STICKY FLOATING CART BAR UNTUK SMARTPHONE --}}
@if($cart->count() > 0)
<div class="mobile-cart-bar d-lg-none">
    <div>
        <div class="small text-white-50" style="font-size: 11px;">
            <i class="fas fa-shopping-cart me-1"></i> {{ $cart->sum('qty') }} item terpilih
        </div>
        <div class="fw-bold text-white fs-6">
            Rp {{ number_format($totalCart, 0, ',', '.') }}
        </div>
    </div>
    <a href="#cart-section" class="btn btn-warning btn-sm fw-bold px-3 py-2 rounded-pill shadow-sm text-dark" style="font-size: 12px;">
        Bayar Sekarang <i class="fas fa-arrow-right ms-1"></i>
    </a>
</div>
@endif

{{-- JAVASCRIPT FILTER PENCARIAN & KATEGORI --}}
<script>
    let currentCategory = 'all';

    function filterMenuItems() {
        const query = document.getElementById('searchMenuInput').value.toLowerCase().trim();
        const clearBtn = document.getElementById('clearSearchBtn');
        const cards = document.querySelectorAll('.menu-card');
        const noResult = document.getElementById('noMenuResult');
        let visibleCount = 0;

        if (query.length > 0) {
            clearBtn.style.display = 'block';
        } else {
            clearBtn.style.display = 'none';
        }

        cards.forEach(card => {
            const name = card.getAttribute('data-name') || '';
            const cat = card.getAttribute('data-category') || '';

            const matchQuery = name.includes(query);
            const matchCategory = (currentCategory === 'all') || (cat === currentCategory);

            if (matchQuery && matchCategory) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        if (visibleCount === 0) {
            noResult.classList.remove('d-none');
        } else {
            noResult.classList.add('d-none');
        }
    }

    function filterCategory(category, element) {
        currentCategory = category;

        // Toggle active class pada pill
        document.querySelectorAll('.cat-pill').forEach(pill => {
            pill.classList.remove('active');
        });
        if (element) {
            element.classList.add('active');
        }

        filterMenuItems();
    }

    function clearSearch() {
        document.getElementById('searchMenuInput').value = '';
        filterMenuItems();
    }
</script>
@endsection
