<nav class="sidebar-nav scroll-sidebar" data-simplebar="">

  @if (Auth::check() && Auth::user()->akses == 'admin')
    <ul id="sidebarnav">
                <li class="nav-small-cap">
                  <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
                  <span class="hide-menu">Menu Utama</span>
                </li>
                <li class="sidebar-item">
                  <a class="sidebar-link" href="./index.html" aria-expanded="false">
                    <span>
                      <i class="ti ti-layout-dashboard"></i>
                    </span>
                    <span class="hide-menu">Dashboard</span>
                  </a>
                </li>
                <li class="nav-small-cap">
                  <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
                  <span class="hide-menu">ATK</span>
                </li>
                <li class="sidebar-item">
                  <a class="sidebar-link" href="{{ url('users') }}" aria-expanded="false">
                    <span>
                      <i class="ti ti-article"></i>
                    </span>
                    <span class="hide-menu">Karyawan</span>
                  </a>
                </li>
                <li class="sidebar-item">
                  <a class="sidebar-link" href="{{ url('barang') }}" aria-expanded="false">
                    <span>
                      <i class="ti ti-cards"></i>
                    </span>
                    <span class="hide-menu">Daftar ATK</span>
                  </a>
                </li>
                <li class="sidebar-item">
                  <a class="sidebar-link" href="{{ url('pengajuan') }}" aria-expanded="false">
                    <span>
                      <i class="ti ti-file-description"></i>
                    </span>
                    <span class="hide-menu">Daftar Pengajuan</span>
                  </a>
                </li>           
    </ul>
  @endif

  @if (Auth::check() && Auth::user()->akses == 'karyawan')
    <ul id="sidebarnav">
                <li class="nav-small-cap">
                  <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
                  <span class="hide-menu">Menu Utama</span>
                </li>
                <li class="sidebar-item">
                  <a class="sidebar-link" href="./index.html" aria-expanded="false">
                    <span>
                      <i class="ti ti-layout-dashboard"></i>
                    </span>
                    <span class="hide-menu">Dashboard</span>
                  </a>
                </li>
                <li class="nav-small-cap">
                  <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
                  <span class="hide-menu">ATK</span>
                </li>
                <li class="sidebar-item">
                  <a class="sidebar-link" href="{{ url('barang/karyawan') }}" aria-expanded="false">
                    <span>
                      <i class="ti ti-cards"></i>
                    </span>
                    <span class="hide-menu">Daftar ATK</span>
                  </a>
                </li>
                <li class="sidebar-item">
                  <a class="sidebar-link" href="{{ url('pengajuan/karyawan') }}" aria-expanded="false">
                    <span>
                      <i class="ti ti-file-description"></i>
                    </span>
                    <span class="hide-menu">Daftar Pengajuan</span>
                  </a>
                </li>           
    </ul>
  @endif
</nav>