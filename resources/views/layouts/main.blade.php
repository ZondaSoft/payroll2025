@php
    // $active = 63;
    $user = auth()->user();
    $rol = '';
@endphp

<!doctype html>

<html
  lang="en"
  class="light-style layout-navbar-fixed layout-menu-fixed layout-compact"
  dir="ltr"
  data-theme="theme-default"
  data-assets-path="/"
  data-template="vertical-menu-template"
  data-style="light">
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>Zonda ERP | Agrotecnica Fueguina</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../../assets/img/favicon/favicon.ico" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&ampdisplay=swap"
      rel="stylesheet" />

    <!-- Icons -->
    <link rel="stylesheet" href="{{ asset('fonts/remixicon/remixicon.css') }}" />

    <!-- Menu waves for no-customizer fix -->
    <link rel="stylesheet" href="{{ asset('libs/node-waves/node-waves.css') }}" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="{{ asset('css/rtl/core.css') }}" class="template-customizer-core-css" />
    <link rel="stylesheet" href="{{ asset('css/rtl/theme-default.css') }}" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="{{ asset('css/demo.css') }}" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="{{ asset('/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    <link rel="stylesheet" href="{{ asset('/libs/typeahead-js/typeahead.css') }}" />
    <!-- {{-- <link rel="stylesheet" href="{{ asset('/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('/libs/datatables-checkboxes-jquery/datatables.checkboxes.css') }}" /> --}} -->
    <link rel="stylesheet" href="{{ asset('/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}" />

    @yield('styles')
    <!-- Page CSS -->

    <!-- Helpers -->
    <script src="{{ asset('js/helpers.js') }}"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
    <!-- <script src="{{ asset('js/template-customizer.js') }}"></script> -->
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="{{ asset('js/config.js') }}"></script>
  </head>

  <body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
        <!-- Menu -->

        <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
          <div class="app-brand demo">
            <a href="/" class="app-brand-link">
              <span class="app-brand-logo demo">
                <span style="color: var(--bs-primary)">
                  <!-- <svg width="268" height="150" viewBox="0 0 38 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                      d="M30.0944 2.22569C29.0511 0.444187 26.7508 -0.172113 24.9566 0.849138C23.1623 1.87039 22.5536 4.14247 23.5969 5.92397L30.5368 17.7743C31.5801 19.5558 33.8804 20.1721 35.6746 19.1509C37.4689 18.1296 38.0776 15.8575 37.0343 14.076L30.0944 2.22569Z"
                      fill="currentColor" />
                    <path
                      d="M30.171 2.22569C29.1277 0.444187 26.8274 -0.172113 25.0332 0.849138C23.2389 1.87039 22.6302 4.14247 23.6735 5.92397L30.6134 17.7743C31.6567 19.5558 33.957 20.1721 35.7512 19.1509C37.5455 18.1296 38.1542 15.8575 37.1109 14.076L30.171 2.22569Z"
                      fill="url(#paint0_linear_2989_100980)"
                      fill-opacity="0.4" />
                    <path
                      d="M22.9676 2.22569C24.0109 0.444187 26.3112 -0.172113 28.1054 0.849138C29.8996 1.87039 30.5084 4.14247 29.4651 5.92397L22.5251 17.7743C21.4818 19.5558 19.1816 20.1721 17.3873 19.1509C15.5931 18.1296 14.9843 15.8575 16.0276 14.076L22.9676 2.22569Z"
                      fill="currentColor" />
                    <path
                      d="M14.9558 2.22569C13.9125 0.444187 11.6122 -0.172113 9.818 0.849138C8.02377 1.87039 7.41502 4.14247 8.45833 5.92397L15.3983 17.7743C16.4416 19.5558 18.7418 20.1721 20.5361 19.1509C22.3303 18.1296 22.9391 15.8575 21.8958 14.076L14.9558 2.22569Z"
                      fill="currentColor" />
                    <path
                      d="M14.9558 2.22569C13.9125 0.444187 11.6122 -0.172113 9.818 0.849138C8.02377 1.87039 7.41502 4.14247 8.45833 5.92397L15.3983 17.7743C16.4416 19.5558 18.7418 20.1721 20.5361 19.1509C22.3303 18.1296 22.9391 15.8575 21.8958 14.076L14.9558 2.22569Z"
                      fill="url(#paint1_linear_2989_100980)"
                      fill-opacity="0.4" />
                    <path
                      d="M7.82901 2.22569C8.87231 0.444187 11.1726 -0.172113 12.9668 0.849138C14.7611 1.87039 15.3698 4.14247 14.3265 5.92397L7.38656 17.7743C6.34325 19.5558 4.04298 20.1721 2.24875 19.1509C0.454514 18.1296 -0.154233 15.8575 0.88907 14.076L7.82901 2.22569Z"
                      fill="currentColor" />
                    <defs>
                      <linearGradient
                        id="paint0_linear_2989_100980"
                        x1="5.36642"
                        y1="0.849138"
                        x2="10.532"
                        y2="24.104"
                        gradientUnits="userSpaceOnUse">
                        <stop offset="0" stop-opacity="1" />
                        <stop offset="1" stop-opacity="0" />
                      </linearGradient>
                      <linearGradient
                        id="paint1_linear_2989_100980"
                        x1="5.19475"
                        y1="0.849139"
                        x2="10.3357"
                        y2="24.1155"
                        gradientUnits="userSpaceOnUse">
                        <stop offset="0" stop-opacity="1" />
                        <stop offset="1" stop-opacity="0" />
                      </linearGradient>
                    </defs>
                  </svg> -->
                  <!-- <svg width="268" height="150" viewBox="0 0 38 20" fill="none" xmlns="http://www.w3.org/2000/svg"> -->
                  <img width="50" height="45" viewBox="0 0 38 20" src="{{ asset('img/logo_af.png') }}" alt="Agrotecnica Fueguina">
                </span>
              </span>
              <span class="app-brand-text demo menu-text fw-semibold ms-2">Agrotecnica</span>
            </a>

            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path
                  d="M8.47365 11.7183C8.11707 12.0749 8.11707 12.6531 8.47365 13.0097L12.071 16.607C12.4615 16.9975 12.4615 17.6305 12.071 18.021C11.6805 18.4115 11.0475 18.4115 10.657 18.021L5.83009 13.1941C5.37164 12.7356 5.37164 11.9924 5.83009 11.5339L10.657 6.707C11.0475 6.31653 11.6805 6.31653 12.071 6.707C12.4615 7.09747 12.4615 7.73053 12.071 8.121L8.47365 11.7183Z"
                  fill-opacity="0.9" />
                <path
                  d="M14.3584 11.8336C14.0654 12.1266 14.0654 12.6014 14.3584 12.8944L18.071 16.607C18.4615 16.9975 18.4615 17.6305 18.071 18.021C17.6805 18.4115 17.0475 18.4115 16.657 18.021L11.6819 13.0459C11.3053 12.6693 11.3053 12.0587 11.6819 11.6821L16.657 6.707C17.0475 6.31653 17.6805 6.31653 18.071 6.707C18.4615 7.09747 18.4615 7.73053 18.071 8.121L14.3584 11.8336Z"
                  fill-opacity="0.4" />
              </svg>
            </a>
          </div>

          <div class="menu-inner-shadow"></div>

          <ul class="menu-inner py-1">
            <!-- Apps & Pages -->
            <li class="menu-header mt-5">
              <span class="menu-header-text" data-i18n="Principal">Principal</span>
            </li>
            <li class="menu-item active open">
              <ul class="menu-sub">
                <li class="menu-item {{ $active==1?'active':' ' }}">
                  <a href="/" class="menu-link">
                    <div data-i18n="Menú principal">Menú principal</div>
                  </a>
                </li>
                @can('mydata.index')
                    <li class="menu-item">
                    <a href="mydata" class="menu-link">
                        <div data-i18n="Mi empresa">Mi empresa</div>
                    </a>
                    </li>
                @endcan
              </ul>
            </li>
            <!-- Config -->
            <li class="menu-header mt-5">
              <span class="menu-header-text" data-i18n="Configuracion">Configuracion</span>
            </li>
            <li class="menu-item {{ $active>=60?'active open':' ' }}">
              <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons ri-git-commit-line"></i>
                <div data-i18n="Configuracion">Configuracion</div>
              </a>
              @can('workshop.task')
                <ul class="menu-sub">
                    <li class="menu-item {{ $active==60?'active':' ' }}">
                    <a href="/tasks" class="menu-link">
                        <div data-i18n="Tareas">Tareas</div>
                    </a>
                    </li>
                </ul>
              @endcan
            </li>
            <!-- Segurity -->
            @role('Manager')
                <li class="menu-header mt-5">
                    <span class="menu-header-text" data-i18n="Seguridad">Seguridad</span>
                </li>
                <li class="menu-item {{ $active>=70?'active open':' ' }}">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons ri-lock-2-line"></i>
                    <div data-i18n="Configuracion">Configuracion</div>
                    </a>

                    <ul class="menu-sub">
                    <li class="menu-item {{ $active==70?'active':' ' }}">
                        <a href="/users" class="menu-link">
                        <div data-i18n="Usuarios">Usuarios</div>
                        </a>
                    </li>
                    </ul>
                    <ul class="menu-sub">
                        <li class="menu-item {{ $active==71?'active':' ' }}">
                        <a href="/roles" class="menu-link">
                            <div data-i18n="Roles">Roles</div>
                        </a>
                        </li>
                    </ul>
                    <ul class="menu-sub">
                        <li class="menu-item {{ $active==72?'active':' ' }}">
                            <a href="/permissions" class="menu-link">
                            <div data-i18n="Permisos">Permisos</div>
                            </a>
                        </li>
                    </ul>
                </li>
            @endrole
          </ul>


        </aside>
        <!-- / Menu -->

        <!-- Layout container -->
        <div class="layout-page">
          <!-- Navbar -->

          <nav
            class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
            id="layout-navbar">
            <div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0 d-xl-none">
              <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
                <i class="ri-menu-fill ri-22px"></i>
              </a>
            </div>

            <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
              <!-- Search -->
              @can('search')
                <div class="navbar-nav align-items-center">
                    <div class="nav-item navbar-search-wrapper mb-0">
                    <a class="nav-item nav-link search-toggler fw-normal px-0" href="javascript:void(0);">
                        <i class="ri-search-line ri-22px scaleX-n1-rtl me-3"></i>
                        <span class="d-none d-md-inline-block text-muted">Buscar (Ctrl+/)</span>
                    </a>
                    </div>
                </div>
              @endcan
              <!-- /Search -->

              <ul class="navbar-nav flex-row align-items-center ms-auto">
                <!-- Style Switcher -->
                <li class="nav-item dropdown-style-switcher dropdown me-1 me-xl-0">
                  <a
                    class="nav-link btn btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow"
                    href="javascript:void(0);"
                    data-bs-toggle="dropdown">
                    <i class="ri-22px"></i>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end dropdown-styles">
                    <li>
                      <a class="dropdown-item" href="javascript:void(0);" data-theme="light">
                        <span class="align-middle"><i class="ri-sun-line ri-22px me-3"></i>Light</span>
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item" href="javascript:void(0);" data-theme="dark">
                        <span class="align-middle"><i class="ri-moon-clear-line ri-22px me-3"></i>Dark</span>
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item" href="javascript:void(0);" data-theme="system">
                        <span class="align-middle"><i class="ri-computer-line ri-22px me-3"></i>System</span>
                      </a>
                    </li>
                  </ul>
                </li>
                <!-- / Style Switcher-->

                <!-- Quick links  -->
                @include('layouts.userQuicklinks')
                <!-- Quick links -->

                <!-- Notification -->
                @include('layouts.userNotification')
                <!--/ Notification -->

                <!-- User -->
                @include('layouts.userMenu')
                <!--/ User -->
              </ul>
            </div>

            <!-- Search Small Screens -->
            @can('search')
                <div class="navbar-search-wrapper search-input-wrapper d-none">
                <input
                    type="text"
                    class="form-control search-input container-xxl border-0"
                    placeholder="Search..."
                    aria-label="Search..." />
                <i class="ri-close-fill search-toggler cursor-pointer"></i>
                </div>
            @endcan
          </nav>

          <!-- / Navbar -->

          <!-- Content wrapper -->
          <div class="content-wrapper">
            <!-- Content -->
            @yield('content')
            <!-- / Content -->

            <!-- Footer -->
            <footer class="content-footer footer bg-footer-theme">
              <div class="container-xxl">
                <div
                  class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
                  <div class="text-body mb-2 mb-md-0">
                    ©
                    <script>
                      document.write(new Date().getFullYear());
                    </script><span class="text-danger"></i></span> by
                    <a href="https://zondasoftware.com.ar" target="_blank" class="footer-link">Zonda Software</a>
                  </div>
                  <!-- {{-- <div class="d-none d-lg-inline-block">
                    <a href="https://pixinvent.ticksy.com/" target="_blank" class="footer-link d-none d-sm-inline-block"
                      >Soporte</a
                    >
                  </div> --}} -->
                </div>
              </div>
            </footer>
            <!-- / Footer -->

            <div class="content-backdrop fade"></div>
          </div>
          <!-- Content wrapper -->
        </div>
        <!-- / Layout page -->
      </div>

      <!-- Overlay -->
      <div class="layout-overlay layout-menu-toggle"></div>

      <!-- Drag Target Area To SlideIn Menu On Small Screens -->
      <div class="drag-target"></div>
    </div>
    <!-- / Layout wrapper -->

    <!-- Core JS -->
    <!-- build:js assets/vendor/js/core.js -->
    <script src="{{ asset('/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('/js/bootstrap.js') }}"></script>
    <script src="{{ asset('/libs/node-waves/node-waves.js') }}"></script>
    <script src="{{ asset('/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('/libs/hammer/hammer.js') }}"></script>
    <script src="{{ asset('/libs/i18n/i18n.js') }}"></script>
    <script src="{{ asset('/libs/typeahead-js/typeahead.js') }}"></script>
    <script src="{{ asset('/js/menu.js') }}"></script>

    <!-- endbuild -->

    <!-- Vendors JS -->
    <script src="{{ asset('libs/moment/moment.js') }}"></script>
    <!-- {{-- <script src="{{ asset('libs/datatables-bs5/datatables-bootstrap5.js') }}"></script> --}} -->

    <!-- Main JS -->
    <script src="{{ asset('js/main.js') }}"></script>

    <!-- Page JS -->
    <!-- {{-- <script src="{{ asset('js/app-invoice-list.js') }}"></script> --}} -->


    @routes
    @yield('scripts')

  </body>
</html>
