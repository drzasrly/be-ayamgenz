<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FnB Fried Chicken</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- 🔥 SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Custom Style Global -->
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Montserrat', sans-serif;
        }

        .main-wrapper {
            max-width: 1400px;
            margin: auto;
        }

        /* 🔥 Custom Popup Style */
        .swal2-popup {
            border-radius: 20px !important;
            padding: 20px !important;
        }

        .swal2-title {
            font-weight: 800 !important;
        }

        .swal2-confirm, .swal2-cancel {
            border-radius: 8px !important;
            padding: 8px 16px;
            font-weight: 600;
        }
    </style>

    @stack('styles')

</head>

<body>

    @auth
    <div class="container-fluid pt-2 pt-sm-3 px-2 px-sm-3 px-lg-4">
        <div class="main-wrapper">
            <div class="d-flex flex-wrap justify-content-between align-items-center bg-white px-3 py-2 rounded-4 shadow-sm border border-light gap-2" style="font-size: 13px;">
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <a href="{{ route('dashboard') }}" class="text-danger fw-bold text-uppercase text-decoration-none" style="letter-spacing: 0.5px; font-weight: 800; font-size: 14px;">
                        <i class="fas fa-drumstick-bite me-1"></i> FnB Chicken
                    </a>
                    <span class="text-muted d-none d-sm-inline">|</span>
                    <span class="text-secondary small">
                        <strong class="text-dark text-capitalize">{{ auth()->user()->name }}</strong> 
                        <span class="badge bg-danger text-white text-uppercase ms-1" style="font-size: 9px; font-weight: 700; border-radius: 4px; padding: 2px 5px;">{{ auth()->user()->role }}</span>
                        @if(auth()->user()->cabang)
                            <span class="text-muted d-none d-md-inline">|</span> 
                            <span class="text-muted d-none d-sm-inline">Cabang: <strong class="text-dark">{{ auth()->user()->cabang->nama_cabang }}</strong></span>
                        @endif
                    </span>
                </div>
                <div class="d-flex align-items-center gap-1 gap-sm-2 ms-auto">
                    <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-light text-secondary rounded-pill px-2 px-sm-3 py-1 fw-bold border" style="font-size: 11px; background-color: #f8f9fa;">
                        <i class="fas fa-user-cog text-danger"></i> <span class="d-none d-sm-inline ms-1">Edit Profil</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2 px-sm-3 py-1 fw-bold" style="font-size: 11px;">
                            <i class="fas fa-sign-out-alt"></i> <span class="d-none d-sm-inline ms-1">Keluar</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endauth

    <div class="container-fluid mt-2 mt-sm-3 px-2 px-sm-3 px-lg-4 mb-5">
        <div class="main-wrapper">
            @yield('content')
        </div>
    </div>

    <!-- ========================= -->
    <!-- 🔥 POPUP NOTIFICATION -->
    <!-- ========================= -->

    @if(session('error'))
    <script>
    Swal.fire({
        title: 'Gagal!',
        text: `{!! session('error') !!}`,
        icon: 'error',
        showCancelButton: true,
        confirmButtonText: 'Coba Lagi',
        cancelButtonText: 'Tutup',
        confirmButtonColor: '#f97316',
        cancelButtonColor: '#ef4444'
    }).then((result) => {

        // 🔁 tetap di form
        if (result.isConfirmed) {
            return;
        }

        // 🔥 balik ke dashboard
        window.location.href = "{{ url('/dashboard') }}";

    });
    </script>
    @endif

    @if(session('success'))
    <script>
        Swal.fire({
            title: 'Berhasil!',
            text: "{{ session('success') }}",
            icon: 'success',
            confirmButtonColor: '#22c55e',
            timer: 2000,
            showConfirmButton: false
        });
    </script>
    @endif

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    @stack('scripts')

</body>
</html>