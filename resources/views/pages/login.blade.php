<!DOCTYPE html>

<html lang="en" class="light-style layout-wide  customizer-hide" dir="ltr" data-theme="theme-semi-dark" data-assets-path="{{ asset('') }}template/assets/" data-template="vertical-menu-template-semi-dark" data-style="light">


<!-- Mirrored from demos.pixinvent.com/vuexy-html-admin-template/html/vertical-menu-template-semi-dark/auth-login-cover.html by HTTrack Website Copier/3.x [XR&CO'2014], Tue, 28 Jan 2025 09:22:18 GMT -->
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>Login Cover - Pages | Vuexy - Bootstrap Admin Template</title>


    <meta name="description" content="Start your development with a Dashboard for Bootstrap 5" />
    <meta name="keywords" content="dashboard, bootstrap 5 dashboard, bootstrap 5 design, bootstrap 5">
    <!-- Canonical SEO -->
    <link rel="canonical" href="https://1.envato.market/vuexy_admin">


    <!-- ? PROD Only: Google Tag Manager (Default ThemeSelection: GTM-5DDHKGP, PixInvent: GTM-5J3LMKC) -->
    <script>
        (function(w, d, s, l, i) {
            w[l] = w[l] || [];
            w[l].push({
                'gtm.start': new Date().getTime(),
                event: 'gtm.js'
            });
            var f = d.getElementsByTagName(s)[0],
                j = d.createElement(s),
                dl = l != 'dataLayer' ? '&l=' + l : '';
            j.async = true;
            j.src =
                '{{ asset('') }}template/{{ asset('') }}template/www.googletagmanager.com/gtm5445.html?id=' +
                i + dl;
            f.parentNode.insertBefore(j, f);
        })(window, document, 'script', 'dataLayer', 'GTM-5J3LMKC');
    </script>
    <!-- End Google Tag Manager -->

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="https://demos.pixinvent.com/vuexy-html-admin-template/assets/img/favicon/favicon.ico" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com/">
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&amp;ampdisplay=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="{{ asset('') }}template/assets/vendor/fonts/fontawesome.css" />
    <link rel="stylesheet" href="{{ asset('') }}template/assets/vendor/fonts/tabler-icons.css"/>
    <link rel="stylesheet" href="{{ asset('') }}template/assets/vendor/fonts/flag-icons.css" />

    <!-- Core CSS -->

    <link rel="stylesheet" href="{{ asset('') }}template/assets/vendor/css/rtl/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="{{ asset('') }}template/assets/vendor/css/rtl/theme-semi-dark.css" class="template-customizer-theme-css" />

    <link rel="stylesheet" href="{{ asset('') }}template/assets/css/demo.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="{{ asset('') }}template/assets/vendor/libs/node-waves/node-waves.css" />

    <link rel="stylesheet" href="{{ asset('') }}template/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="{{ asset('') }}template/assets/vendor/libs/typeahead-js/typeahead.css" />
    <!-- Vendor -->
<link rel="stylesheet" href="{{ asset('') }}template/assets/vendor/libs/%40form-validation/form-validation.css" />

    <!-- Page CSS -->
    <!-- Page -->
<link rel="stylesheet" href="{{ asset('') }}template/assets/vendor/css/pages/page-auth.css">

    <!-- Helpers -->
    <script src="{{ asset('') }}template/assets/vendor/js/helpers.js"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->

    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
    <script src="{{ asset('') }}template/assets/vendor/js/template-customizer.js"></script>

    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="{{ asset('') }}template/assets/js/config.js"></script>

  </head>

  <body>


    <!-- ?PROD Only: Google Tag Manager (noscript) (Default ThemeSelection: GTM-5DDHKGP, PixInvent: GTM-5J3LMKC) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5J3LMKC" height="0" width="0" style="display: none; visibility: hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->

    <!-- Content -->

<div class="authentication-wrapper authentication-cover">
  <!-- Logo -->
  <a href="index.html" class="app-brand auth-cover-brand">
    <img src="{{ asset('') }}images/telkom.jpg" alt="Logo" style="width: 350px; height: 120px; margin-bottom: 10px;">
  </a>
  <!-- /Logo -->
  <div class="authentication-inner row m-0">
      <!-- /Left Text -->
      <div class="d-none d-lg-flex col-lg-8 p-0">
<div class="auth-cover-bg auth-cover-bg-color d-flex justify-content-center align-items-center" style="position: relative;">
  <!-- Gambar ilustrasi -->
  <img src="{{ asset('') }}template/assets/img/illustrations/auth-login-illustration-light.png" alt="auth-login-cover" class="my-5 auth-illustration" data-app-light-img="illustrations/auth-login-illustration-light.png" data-app-dark-img="illustrations/auth-login-illustration-dark.html">

  <!-- Gambar latar -->
  <img src="{{ asset('') }}template/assets/img/illustrations/bg-shape-image-light.png" alt="auth-login-cover" class="platform-bg" data-app-light-img="illustrations/bg-shape-image-light.png" data-app-dark-img="illustrations/bg-shape-image-dark.html">

  <!-- Teks di pojok kiri bawah -->
  <h5 style="position: absolute; bottom: 20px; left: 20px; margin: 0; color: black">
    Powered by Institut Teknologi Sepuluh Nopember
  </h5>
</div>

        </div>


    <!-- /Left Text -->

    <!-- Login -->
    <div class="d-flex col-12 col-lg-4 align-items-center authentication-bg p-sm-12 p-6 " style="background-color: #3674B5;">
  <div class="w-px-400 mx-auto" style="color: white;">

    <div class="w-px-400 mx-auto pt-5" style="color: white; text-align: center;">
    <img src="{{ asset('') }}images/1.png" alt="Logo" style="width: 150px; height: 150px; margin-bottom: 10px;">
    <h2 class="mb-1" style="color: white">Hello Welcome Back!</h2>
    <p class="mb-6">Let’s Login to Your Account</p>
    </div>


    <form id="formAuthentication" class="mb-6" action="{{ route('login') }}" method="POST">
        @csrf
      <div class="mb-6">
        <label for="email" class="form-label" style="color: white;">Email or Username</label>
        <input type="text" class="form-control" id="email" name="email" placeholder="Enter your email or username" style="background: transparent; border: 1px solid white; color: white;">
      </div>
      <div class="mb-6 form-password-toggle">
        <label class="form-label" for="password" style="color: white;">Password</label>
        <div class="input-group input-group-merge">
          <input type="password" id="password" class="form-control" name="password" placeholder="••••••••••••" aria-describedby="password" style="background: transparent; border: 1px solid white; color: white;">
          <span class="input-group-text cursor-pointer" style="background: transparent; color: white;"><i class="ti ti-eye-off"></i></span>
        </div>
      </div>

      <div class="my-8">
        <div class="d-flex justify-content-between">
          <div class="form-check mb-0 ms-2">

          </div>
          <a href="" style="color: white;">
            <p class="mb-0">Forgot Password?</p>
          </a>
        </div>
      </div>

      <button class="btn btn-primary d-grid w-100" type="submit" style="background: white; color: #3674B5; border: none;">
        Sign in
      </button>
    </form>

    <p class="text-center">
      <span>Dont Have Account?</span>
      <a href="" style="color: white;">
        <span>Create an account</span>
      </a>
    </p>

    <div class="divider my-6">
      <div class="divider-text" style="color: white;">or</div>
    </div>

    <div class="d-flex justify-content-center">
      <a href="javascript:;" class="btn btn-sm btn-icon rounded-pill btn-text-facebook me-1_5" style="color: white;">
        <i class="tf-icons ti ti-brand-facebook-filled"></i>
      </a>

      <a href="javascript:;" class="btn btn-sm btn-icon rounded-pill btn-text-twitter me-1_5" style="color: white;">
        <i class="tf-icons ti ti-brand-twitter-filled"></i>
      </a>

      <a href="javascript:;" class="btn btn-sm btn-icon rounded-pill btn-text-github me-1_5" style="color: white;">
        <i class="tf-icons ti ti-brand-github-filled"></i>
      </a>

      <a href="javascript:;" class="btn btn-sm btn-icon rounded-pill btn-text-google-plus" style="color: white;">
        <i class="tf-icons ti ti-brand-google-filled"></i>
      </a>
    </div>
  </div>
</div>

    <!-- /Login -->
  </div>
</div>

<!-- / Content -->




    <!-- Core JS -->
    <!-- build:js assets/vendor/js/core.js -->

    <script src="{{ asset('') }}template/assets/vendor/libs/jquery/jquery.js"></script>
    <script src="{{ asset('') }}template/assets/vendor/libs/popper/popper.js"></script>
    <script src="{{ asset('') }}template/assets/vendor/js/bootstrap.js"></script>
      <script src="{{ asset('') }}template/assets/vendor/libs/node-waves/node-waves.js"></script>
    <script src="{{ asset('') }}template/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="{{ asset('') }}template/assets/vendor/libs/hammer/hammer.js"></script>
    <script src="{{ asset('') }}template/assets/vendor/libs/i18n/i18n.js"></script>
    <script src="{{ asset('') }}template/assets/vendor/libs/typeahead-js/typeahead.js"></script>
    <script src="{{ asset('') }}template/assets/vendor/js/menu.js"></script>

    <!-- endbuild -->

    <!-- Vendors JS -->
    <script src="{{ asset('') }}template/assets/vendor/libs/%40form-validation/popular.js"></script>
<script src="{{ asset('') }}template/assets/vendor/libs/%40form-validation/bootstrap5.js"></script>
<script src="{{ asset('') }}template/assets/vendor/libs/%40form-validation/auto-focus.js"></script>

    <!-- Main JS -->
    <script src="{{ asset('') }}template/assets/js/main.js"></script>


    <!-- Page JS -->
    <script src="{{ asset('') }}template/assets/js/pages-auth.js"></script>

  </body>


<!-- Mirrored from demos.pixinvent.com/vuexy-html-admin-template/html/vertical-menu-template-semi-dark/auth-login-cover.html by HTTrack Website Copier/3.x [XR&CO'2014], Tue, 28 Jan 2025 09:22:18 GMT -->
</html>

<!-- beautify ignore:end -->
