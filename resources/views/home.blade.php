<!doctype html>
<html lang="en">

@include('Template.head')

<body>
  <!--  Body Wrapper -->
  <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
    data-sidebar-position="fixed" data-header-position="fixed">
    <!-- Sidebar Start -->
    <aside class="left-sidebar">
      <!-- Sidebar scroll-->
      <div>
        <div class="brand-logo d-flex align-items-center justify-content-between">
          <a href="./index.html" class="text-nowrap logo-img">
          <img src="{{ asset('assets/images/logos/logo_bpjs.png') }}" width="180" alt="Logo BPJS" />
          </a>
          <div class="close-btn d-xl-none d-block sidebartoggler cursor-pointer" id="sidebarCollapse">
            <i class="ti ti-x fs-8"></i>
          </div>
        </div>

        <!-- Sidebar navigation-->
        @include('Template.sidebar')
        <!-- End Sidebar navigation -->

      </div>
      <!-- End Sidebar scroll-->
    </aside>

    <!--  Sidebar End -->


    <!--  Main wrapper -->
    <div class="body-wrapper">

      <!--  Header Start -->
      @include('Template.navbar')
      <!--  Header End -->
      <div class="container-fluid">
        <!--  Row 1 -->
        @yield('content')
        <div class="py-4 px-4 text-center">
          <p class="mb-0 fs-5">PERANCANGAN SISTEM INFORMASI PERMINTAAN BARANG ALAT TULIS KANTOR 
             <a href="#" target="_blank" class="pe-1 text-primary text-decoration-underline"><br>BERBASIS WEBSITE 
             DI BPJS SUKABUMI </a> by <a href="https://themewagon.com">Daffa Muhammad Jawad</a></p>
        </div>
      </div>
    </div>
  </div>
  
  <script src="../assets/libs/jquery/dist/jquery.min.js"></script>

  <script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/sidebarmenu.js"></script>
  <script src="../assets/js/app.min.js"></script>
  <script src="../assets/libs/apexcharts/dist/apexcharts.min.js"></script>
  <script src="../assets/libs/simplebar/dist/simplebar.js"></script>
  <script src="../assets/js/dashboard.js"></script>
  <script src="{{ asset('assets/DataTables/datatables.js') }}"></script>
  <script> 
    $(document).ready(function() {
        $('#mytable').DataTable();
    });
  </script>
  <!-- Sweet Alerts js -->
<script src="{{ asset('assets/libs/sweetalert2/sweetalert2.min.js')}}"></script>
<!-- Sweet alert init js-->
<script src="{{ asset('assets/js/sweetalerts.init.js')}}"></script>
<script src="{{ asset('assets/libs/sweetalert2/sweetalert2.all.min.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>