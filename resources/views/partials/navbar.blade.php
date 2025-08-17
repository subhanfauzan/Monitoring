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
            <!-- User -->
            <li class="nav-item navbar-dropdown dropdown-user dropdown me-5">

                <div class="d-flex align-items-center">
                    <!-- Avatar -->
                    <div class="text-end">
                        <span class="d-block fw-bold">{{ Auth::user()->name }}</span> <!-- Nama di atas -->
                        <span class="d-block small text-muted">Staff OMC</span> <!-- Teks di bawah nama -->
                    </div>
                    <div class="avatar avatar-online avatar-xl ms-2">
                        <img src="{{ asset('') }}template/assets/img/avatars/1.png" alt class="rounded-circle" />
                    </div>

                    <!-- Teks Nama dan Deskripsi (dipindah ke sebelah kiri) -->
                </div>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item mt-0" href="pages-account-settings-account.html">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 me-2">
                                    <div class="avatar avatar-online">
                                        <img src="{{ asset('') }}template/assets/img/avatars/1.png" alt
                                            class="rounded-circle" />
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">{{ Auth::user()->name }}</h6>
                                    <small class="text-muted">Admin</small>
                                </div>
                            </div>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider my-1 mx-n2"></div>
                    </li>
                    <li>
                        <a class="dropdown-item" href="pages-profile-user.html">
                            <i class="ti ti-user me-3 ti-md"></i><span class="align-middle">My Profile</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="pages-account-settings-account.html">
                            <i class="ti ti-settings me-3 ti-md"></i><span class="align-middle">Settings</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="pages-account-settings-billing.html">
                            <span class="d-flex align-items-center align-middle">
                                <i class="flex-shrink-0 ti ti-file-dollar me-3 ti-md"></i><span
                                    class="flex-grow-1 align-middle">Billing</span>
                                <span
                                    class="flex-shrink-0 badge bg-danger d-flex align-items-center justify-content-center">4</span>
                            </span>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider my-1 mx-n2"></div>
                    </li>
                    <li>
                        <a class="dropdown-item" href="pages-pricing.html">
                            <i class="ti ti-currency-dollar me-3 ti-md"></i><span class="align-middle">Pricing</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="pages-faq.html">
                            <i class="ti ti-question-mark me-3 ti-md"></i><span class="align-middle">FAQ</span>
                        </a>
                    </li>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-danger d-flex container-fluid">
                            <small class="align-middle">Logout</small>
                            <i class="ti ti-logout ms-2 ti-14px"></i>
                        </button>
                    </form>

                </ul>
            </li>
            <!--/ User -->
        </ul>
    </div>

    <!-- Search Small Screens -->
    <div class="navbar-search-wrapper search-input-wrapper d-none">
        <input type="text" class="form-control search-input container-xxl border-0 fs-4" placeholder="Search..."
            aria-label="Search..." />
        <i class="ti ti-x search-toggler cursor-pointer fs-2"></i> <!-- Memperbesar ikon search -->
    </div>
</nav>
