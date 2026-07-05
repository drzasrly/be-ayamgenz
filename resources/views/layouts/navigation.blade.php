<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm py-3" style="border-bottom: 4px solid #e4002b;">
    <div class="container-fluid px-4">
        <!-- Logo -->
        <a class="navbar-brand fw-extrabold text-uppercase d-flex align-items-center" href="{{ route('dashboard') }}" style="letter-spacing: 1.5px; font-weight: 800;">
            <i class="fas fa-drumstick-bite text-danger me-2 fs-4"></i>
            <span class="text-white">FnB</span><span class="text-danger">Chicken</span>
        </a>
        
        <!-- Toggle Mobile -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Menu items -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 fw-bold">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active text-danger' : 'text-white-50' }}" href="{{ route('dashboard') }}">
                        <i class="fas fa-chart-line me-1"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('kasir.create') ? 'active text-danger' : 'text-white-50' }}" href="{{ route('kasir.create') }}">
                        <i class="fas fa-cash-register me-1"></i> Transaksi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('menu.index') ? 'active text-danger' : 'text-white-50' }}" href="{{ route('menu.index') }}">
                        <i class="fas fa-utensils me-1"></i> Kelola Menu
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('stok.index') ? 'active text-danger' : 'text-white-50' }}" href="{{ route('stok.index') }}">
                        <i class="fas fa-boxes me-1"></i> Stok
                    </a>
                </li>
            </ul>

            <!-- Right side info & actions -->
            @auth
            <div class="d-flex align-items-center gap-3">
                <!-- User Info -->
                <div class="text-white text-end d-none d-md-block">
                    <div class="fw-bold text-capitalize" style="font-size: 14px;">
                        <i class="fas fa-user-circle text-danger me-1"></i> {{ auth()->user()->name }}
                    </div>
                    <small class="text-white-50 text-uppercase fw-semibold" style="font-size: 10px; letter-spacing: 0.5px;">
                        Role: {{ auth()->user()->role }}
                        @if(auth()->user()->cabang)
                            | {{ auth()->user()->cabang->nama_cabang }}
                        @endif
                    </small>
                </div>

                <!-- Action Dropdown / Links -->
                <div class="dropdown">
                    <button class="btn btn-outline-light dropdown-toggle btn-sm px-3 fw-bold rounded-pill text-capitalize" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                         Menu
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" aria-labelledby="userMenu" style="border-radius: 12px; font-size: 14px;">
                        <li>
                            <a class="dropdown-item fw-semibold" href="{{ route('profile.edit') }}">
                                <i class="fas fa-user-cog me-2 text-danger"></i> Edit Profil
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button type="submit" class="dropdown-item fw-semibold text-danger">
                                    <i class="fas fa-sign-out-alt me-2"></i> Keluar (Logout)
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
            @endauth
        </div>
    </div>
</nav>