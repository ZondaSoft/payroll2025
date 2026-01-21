<!-- User -->
<li class="nav-item navbar-dropdown dropdown-user dropdown">
  <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
    <div class="avatar avatar-online">
      <img src="{{ asset(Auth::user()->image ?? 'img/avatars/1.png') }}" alt class="rounded-circle" />
    </div>
  </a>
  <ul class="dropdown-menu dropdown-menu-end">
    <li>
      <a class="dropdown-item" href="#">
        <div class="d-flex">
          <div class="flex-shrink-0 me-2">
            <div class="avatar avatar-online">
              <img src="{{ asset(Auth::user()->image ?? 'img/avatars/1.png') }}" alt class="rounded-circle" />
            </div>
          </div>
          <div class="flex-grow-1">
            <span class="fw-medium d-block small">{{ $user->name }}</span>
            <small class="text-muted">{{ $rol }}</small>
          </div>
        </div>
      </a>
    </li>
    <li>
      <div class="dropdown-divider"></div>
    </li>
    <li>
      <a class="dropdown-item" href="/profile">
        <i class="ri-user-3-line ri-22px me-3"></i><span class="align-middle">Mi Perfil</span>
      </a>
    </li>
    <li>
      <div class="dropdown-divider"></div>
    </li>
    <li>
      <a class="dropdown-item" href="pages-faq.html">
        <i class="ri-question-line ri-22px me-3"></i><span class="align-middle">Preguntas Frecuentes</span>
      </a>
    </li>
    <li>
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <div class="d-grid px-4 pt-2 pb-1">
          <button class="btn btn-sm btn-danger d-flex" href="auth-login-cover.html" target="_blank">
            <small class="align-middle">Cerrar sesion</small>
            <i class="ri-logout-box-r-line ms-2 ri-16px"></i>
          </button>
        </div>
      </form>
    </li>
  </ul>
</li>
<!--/ User -->
