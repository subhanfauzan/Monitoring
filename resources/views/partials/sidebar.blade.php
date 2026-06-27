<aside id="layout-menu" class="layout-menu menu-vertical menu d-flex flex-column"
    style="background: radial-gradient(circle at 0% 0%, #00adef, #3871c1);">

    <div class="d-flex flex-column align-items-center pt-3">
        <img id="logo-img" src="{{ asset('images/2.png') }}" alt="Logo"
            style="width: 100px; height: auto; margin-bottom: 10px;">
    </div>

    <ul class="menu-inner py-1 flex-grow-1">
        <li class="menu-item">
            <a href="/dashboard" class="menu-link" style="color: white;">
                <i class="menu-icon tf-icons ti ti-id"></i>
                <div data-i18n="Dashboard">Dashboard</div>
            </a>
        </li>

        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle" style="color: white;">
                <i class="menu-icon tf-icons ti ti-layout-sidebar"></i>
                <div data-i18n="Report">Report</div>
            </a>

            <ul class="menu-sub">
                <li class="menu-item">
                    <a href="/tiket" class="menu-link" style="color: white;">
                        <div data-i18n="JATIM">JATIM</div>
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
            <a href="/nop" class="menu-link" style="color: white;">
                <i class="menu-icon tf-icons ti ti-settings"></i>
                <div data-i18n="Daftar NOP">Daftar NOP</div>
            </a>
        </li>

        <li class="menu-item">
            <a href="/dapot" class="menu-link" style="color: white;">
                <i class="menu-icon tf-icons ti ti-checkbox"></i>
                <div data-i18n="Daftar Dapot">Daftar Dapot</div>
            </a>
        </li>
    </ul>

    <!-- User Profile Bottom Left -->
    <div class="sidebar-user-profile p-3 mt-auto d-flex align-items-center justify-content-between" style="border-top: 1px solid rgba(255, 255, 255, 0.2);">
        <div class="d-flex align-items-center overflow-hidden">
            <!-- Foto di sebelah kiri -->
            <div class="avatar flex-shrink-0">
                <img src="{{ asset('') }}template/assets/img/avatars/1.png" alt="User Avatar" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover; border: 2px solid rgba(255,255,255,0.8);" />
            </div>
            <!-- Nama User -->
            <div class="ms-3 user-name-replacement text-truncate text-white">
                <span class="d-block fw-bold mb-0">{{ Auth::user()->name ?? 'User' }}</span>
                <span class="d-block small" style="color: rgba(255,255,255,0.7);">Staff OMC</span>
            </div>
        </div>
        
        <!-- Tombol Logout -->
        <form action="{{ route('logout') }}" method="POST" class="mb-0 user-name-replacement">
            @csrf
            <button type="submit" class="btn btn-sm btn-icon btn-outline-light rounded-circle border-0" title="Logout" style="color: white;" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='transparent'">
                <i class="ti ti-logout"></i>
            </button>
        </form>
    </div>
</aside>

<!-- JavaScript untuk Toggle Logo -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.querySelector('.layout-menu-toggle');
        const logoImg = document.getElementById('logo-img');
        const layoutMenu = document.getElementById('layout-menu');

        toggleBtn.addEventListener('click', function() {
            layoutMenu.classList.toggle('collapsed');

            // Logo ditampilkan saat menu TIDAK collapsed
            if (!layoutMenu.classList.contains('collapsed')) {
                logoImg.classList.remove('d-none');
            } else {
                logoImg.classList.add('d-none');
            }
        });
    });
</script>

<!-- CSS Tambahan supaya lebih smooth -->
<style>
    /* Transisi lebih halus untuk logo */
    #logo-img {
        transition: opacity 0.3s ease, transform 0.3s ease;
    }

    #layout-menu.collapsed #logo-img {
        opacity: 0;
        transform: scale(0.8);
    }

    #layout-menu:not(.collapsed) #logo-img {
        opacity: 1;
        transform: scale(1);
    }

    /* Animasi untuk info user di bawah */
    .user-name-replacement {
        transition: opacity 0.3s ease, transform 0.3s ease;
    }

    #layout-menu.collapsed .user-name-replacement {
        opacity: 0;
        display: none;
    }

    #layout-menu:not(.collapsed) .user-name-replacement {
        opacity: 1;
        display: block;
    }
</style>
