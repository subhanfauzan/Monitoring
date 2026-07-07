<aside id="layout-menu" class="layout-menu menu-vertical menu flex-column"
    style="background: radial-gradient(circle at 0% 0%, #00adef, #3871c1);">

    <!-- Header Sidebar -->
    <div class="sidebar-header d-flex align-items-center justify-content-center pt-4 pb-2 position-relative">
        <!-- Logo -->
        <div class="logo-wrapper overflow-hidden d-flex justify-content-center">
            <img id="logo-img" src="{{ asset('images/2.png') }}" alt="Logo" style="height: 40px; width: auto;">
        </div>
        
        <!-- Custom Toggle Button -->
        <button id="custom-sidebar-toggle-btn" class="btn btn-sm text-white position-absolute" style="right: 15px; top: 25px; background: rgba(255,255,255,0.2); border: none; z-index: 10;">
            <i class="ti ti-menu-2 ti-sm"></i>
        </button>
    </div>

    <ul class="menu-inner py-1 mt-2 flex-grow-1">
        <li class="menu-item">
            <a href="/percakapanbaru" class="menu-link position-relative" style="color: white;">
                <i class="menu-icon tf-icons ti ti-id"></i>
                <div data-i18n="Percakapan Baru">Percakapan Baru</div>
                <span class="gemini-tooltip">Percakapan Baru</span>
            </a>
        </li>

        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle position-relative" style="color: white;">
                <i class="menu-icon tf-icons ti ti-layout-sidebar"></i>
                <div data-i18n="Report">Report</div>
                <span class="gemini-tooltip">Report</span>
            </a>

            <ul class="menu-sub">
                <li class="menu-item">
                    <a href="/dashboard" class="menu-link" style="color: white;">
                        <div data-i18n="DASHBOARD">DASHBOARD</div>
                    </a>
                </li>

                @foreach ($nops as $nop)
                    <li class="menu-item">
                        <a href="{{ route('tiketissue.show', ['id' => $nop->id]) }}" class="menu-link"
                            style="color: white;">
                            <div data-i18n="{{ $nop->nama_nop }}">{{ $nop->nama_nop }}</div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </li>

        <li class="menu-item">
            <a href="/nop" class="menu-link position-relative" style="color: white;">
                <i class="menu-icon tf-icons ti ti-settings"></i>
                <div data-i18n="Daftar NOP">Daftar NOP</div>
                <span class="gemini-tooltip">Daftar NOP</span>
            </a>
        </li>

        <li class="menu-item">
            <a href="/dapot" class="menu-link position-relative" style="color: white;">
                <i class="menu-icon tf-icons ti ti-checkbox"></i>
                <div data-i18n="Daftar Dapot">Daftar Dapot</div>
                <span class="gemini-tooltip">Daftar Dapot</span>
            </a>
        </li>
    </ul>

    <!-- User Profile Bottom Left -->
    <div class="sidebar-user-profile p-3 mt-auto d-flex align-items-center justify-content-between" style="border-top: 1px solid rgba(255, 255, 255, 0.2);">
        <div class="d-flex align-items-center overflow-hidden profile-wrapper">
            <!-- Foto -->
            <div class="avatar flex-shrink-0">
                <img id="profile-img" src="{{ asset('') }}template/assets/img/avatars/1.png" alt="User Avatar" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover; border: 2px solid rgba(255,255,255,0.8);" />
            </div>
            <!-- Nama User -->
            <div class="ms-3 profile-text text-white" style="white-space: nowrap; overflow: hidden; max-width: 150px;">
                <span class="d-block fw-bold mb-0 text-truncate" style="max-width: 130px;">{{ Auth::user()->name ?? 'User' }}</span>
                <span class="d-block small" style="color: rgba(255,255,255,0.7);">Staff OMC</span>
            </div>
        </div>
        
        <!-- Tombol Logout -->
        <form action="{{ route('logout') }}" method="POST" class="mb-0 flex-shrink-0 profile-logout-form">
            @csrf
            <button type="submit" class="btn btn-sm btn-icon rounded-circle border-0 profile-logout-btn" title="Logout" style="color: white; background: transparent;" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='transparent'">
                <i class="ti ti-logout"></i>
            </button>
        </form>
    </div>
</aside>

<!-- Javascript Toggle Menggunakan Native Template -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('custom-sidebar-toggle-btn');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                // Menggunakan Helpers bawaan Sneat agar sinkron dengan body/layout-page
                if (typeof window.Helpers !== 'undefined') {
                    window.Helpers.toggleCollapsed();
                } else {
                    // Fallback jika Helpers tidak ada
                    document.documentElement.classList.toggle('layout-menu-collapsed');
                }
            });
        }
    });
</script>

<!-- CSS Hanya Untuk Custom Elemen (Logo & User Profile) -->
<style>
    /* CEGAH SIDEBAR MEMBESAR SAAT HOVER (Sneat bawaan menggunakan .layout-menu-hover) */
    html.layout-menu-collapsed .layout-menu:hover,
    html.layout-menu-collapsed .layout-menu.layout-menu-hover {
        width: 80px !important; /* Kunci lebar agar tidak melebar */
    }

    /* MATIKAN SCROLLBAR SAAT COLLAPSED AGAR TOOLTIP TIDAK TERPOTONG */
    html.layout-menu-collapsed .layout-menu,
    html.layout-menu-collapsed .menu-inner {
        overflow: visible !important;
    }

    /* TOOLTIP ALA GEMINI (Hanya muncul saat collapsed & di hover) */
    .gemini-tooltip {
        position: absolute;
        left: 90px; /* Jarak ke kanan dari icon diperbesar agar tidak menempel */
        top: 50%;
        margin-top: -16px; /* Centering secara vertikal */
        background-color: #2b5b94; /* Disamakan dengan warna submenu */
        color: #ffffff;
        padding: 6px 14px;
        border-radius: 20px; /* Membulat seperti kapsul */
        font-size: 13px;
        font-weight: 500;
        white-space: nowrap;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        z-index: 99999;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transform: translateX(-5px);
        transition: opacity 0.2s ease, transform 0.2s ease;
    }

    /* Segitiga panah penunjuk (tail) di kiri tooltip */
    .gemini-tooltip::before {
        content: "";
        position: absolute;
        top: 50%;
        right: 100%; /* Menempel di sisi kiri tooltip */
        margin-top: -5px; 
        border-width: 5px;
        border-style: solid;
        border-color: transparent #2b5b94 transparent transparent;
    }

    /* Munculkan tooltip saat menu di hover DAN sidebar sedang collapsed */
    html.layout-menu-collapsed .layout-menu .menu-item:hover .gemini-tooltip {
        opacity: 1;
        visibility: visible;
        transform: translateX(0);
    }

    /* Saat Collapsed, posisikan tombol toggle tepat di tengah atas */
    html.layout-menu-collapsed #custom-sidebar-toggle-btn {
        right: 50% !important;
        transform: translateX(50%) !important;
    }

    /* Hilangkan logo agar tidak menabrak / meluber saat sidebar kecil */
    html.layout-menu-collapsed .logo-wrapper {
        opacity: 0 !important;
        max-width: 0 !important;
        visibility: hidden;
    }
    
    /* Warna icon menu agar putih dan membesar sedikit saat ditutup */
    html.layout-menu-collapsed .layout-menu .menu-link i {
        color: white !important;
        transform: scale(1.1);
    }
    
    /* Sembunyikan teks menu utama & panah dropdown */
    html.layout-menu-collapsed .layout-menu .menu-inner > .menu-item > .menu-link div,
    html.layout-menu-collapsed .layout-menu .menu-toggle::after {
        opacity: 0 !important;
        display: none !important;
    }

    /* ========================================================
       FLOATING SUBMENU SAAT COLLAPSED
       ======================================================== */
    
    /* Sembunyikan tooltip Gemini jika item tersebut memiliki submenu */
    html.layout-menu-collapsed .layout-menu .menu-item:has(.menu-sub):hover .gemini-tooltip {
        display: none !important;
    }

    /* Pastikan menu item relatif agar submenu posisinya pas di sebelahnya */
    html.layout-menu-collapsed .layout-menu .menu-item {
        position: relative;
    }

    /* Tampilkan submenu melayang di sebelah kanan */
    html.layout-menu-collapsed .layout-menu .menu-item:hover > .menu-sub {
        display: block !important;
        position: absolute;
        left: 90px; /* Digeser lebih ke kanan sejajar dengan tooltip */
        top: 0;
        width: 220px;
        background-color: #2b5b94; /* Warna biru solid yang elegan */
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        padding: 10px 0;
        z-index: 999999;
    }

    /* Tambahkan segitiga panah penunjuk (tail) di kiri submenu agar konsisten */
    html.layout-menu-collapsed .layout-menu .menu-item:hover > .menu-sub::before {
        content: "";
        position: absolute;
        top: 18px; /* Disesuaikan agar sejajar dengan tinggi icon */
        right: 100%;
        border-width: 6px;
        border-style: solid;
        border-color: transparent #2b5b94 transparent transparent;
    }

    /* Pastikan teks di dalam submenu terlihat jelas */
    html.layout-menu-collapsed .layout-menu .menu-sub .menu-link div {
        opacity: 1 !important;
        display: block !important;
        margin-left: 0 !important;
        color: white !important;
    }

    /* Efek hover pada item submenu */
    html.layout-menu-collapsed .layout-menu .menu-sub .menu-link:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }

    /* ==== BOTTOM USER PROFILE ==== */
    html.layout-menu-collapsed .sidebar-user-profile {
        flex-direction: column !important;
        justify-content: center !important;
        align-items: center !important;
        padding: 1rem 0 !important;
        gap: 10px;
    }
    
    html.layout-menu-collapsed .profile-wrapper {
        justify-content: center !important;
        width: 100% !important;
        margin: 0 !important;
    }
    
    html.layout-menu-collapsed .profile-text {
        opacity: 0 !important;
        max-width: 0 !important;
        margin-left: 0 !important;
    }
    
    html.layout-menu-collapsed #profile-img {
        width: 32px !important;
        height: 32px !important;
    }
</style>
