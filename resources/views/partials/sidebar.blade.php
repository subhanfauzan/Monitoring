<aside id="layout-menu" class="layout-menu menu-vertical menu"
    style="background: radial-gradient(circle at 0% 0%, #00adef, #3871c1);">

    <div class="d-flex flex-column align-items-center pt-3">
        <img id="logo-img" src="{{ asset('images/2.png') }}" alt="Logo"
            style="width: 100px; height: auto; margin-bottom: 10px;">
    </div>

    <ul class="menu-inner py-1">
        <li class="menu-item">
            <a href="/dashboard" class="menu-link" style="color: white;">
                <i class="menu-icon tf-icons ti ti-id"></i>
                <div data-i18n="Dashboard">Dashboard</div>
            </a>
        </li>

        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle" style="color: white;">
                <i class="menu-icon tf-icons ti ti-layout-sidebar"></i>
                <div data-i18n="Daftar Tiket">Daftar Tiket</div>
            </a>

            <ul class="menu-sub">
                <li class="menu-item">
                    <a href="/tiket" class="menu-link" style="color: white;">
                        <div data-i18n="Import & Export">Import & Export</div>
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
</style>
