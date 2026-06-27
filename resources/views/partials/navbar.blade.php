<nav class="layout-navbar container-xxxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme py-10 fs-5"
    id="layout-navbar">
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
            <i class="ti ti-menu-2 ti-md fs-2"></i> <!-- Memperbesar ikon menu -->
        </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
        <!-- Search -->

        <!-- /Search -->

        <div class="">
            <span class="d-block fw-bold">Welcome to Operation Monitoring Center Reg Jawa Timur</span> <!-- Nama di atas -->
            <span class="d-block small text-muted">Let’s take a detailed look at Incident today</span> <!-- Teks di bawah nama -->
        </div>
        <ul class="navbar-nav flex-row align-items-center ms-auto">
            <!-- Logo Telkom & Telkomsel -->
            <li class="nav-item me-3 d-flex align-items-center gap-2 gap-md-4">
                <img src="{{ asset('images/telkom.jpg') }}" alt="Telkom Infra" class="img-fluid" style="max-height: 80px; height: auto; object-fit: contain;">
                <img src="{{ asset('images/telkomsel.png') }}" alt="Telkomsel" class="img-fluid pl-3" style="max-height: 80px; height: auto; object-fit: contain;">
            </li>
        </ul>
    </div>

    <!-- Search Small Screens -->
    <div class="navbar-search-wrapper search-input-wrapper d-none">
        <input type="text" class="form-control search-input container-xxl border-0 fs-4" placeholder="Search..."
            aria-label="Search..." />
        <i class="ti ti-x search-toggler cursor-pointer fs-2"></i> <!-- Memperbesar ikon search -->
    </div>
</nav>
