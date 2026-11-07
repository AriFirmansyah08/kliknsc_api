@extends('home')

@section('content')
<div class="container mt-4">
    @if (Auth::check() && Auth::user()->akses == 'admin')
      <div class="row">
          <!-- Card 1: Jumlah User dengan Akses Karyawan -->
          <div class="col-md-3">
              <div class="card bg-primary text-white">
                  <div class="card-body">
                      <h5 class="card-title">Total Karyawan</h5>
                      <h3>{{ $jumlah_karyawan }}</h3>
                  </div>
              </div>
          </div>

          <!-- Card 2: Jumlah Barang -->
          <div class="col-md-3">
              <div class="card bg-success text-white">
                  <div class="card-body">
                      <h5 class="card-title">Total Barang</h5>
                      <h3>{{ $jumlah_barang }}</h3>
                  </div>
              </div>
          </div>

          <!-- Card 3: Jumlah Pengajuan -->
          <div class="col-md-3">
              <div class="card bg-warning text-white">
                  <div class="card-body">
                      <h5 class="card-title">Total Pengajuan</h5>
                      <h3>{{ $jumlah_pengajuan }}</h3>
                  </div>
              </div>
          </div>

          <!-- Card 4: Barang dengan Stok 0 -->
          <div class="col-md-3">
              <div class="card bg-danger text-white">
                  <div class="card-body">
                      <h5 class="card-title">Barang Stok Habis</h5>
                      <h3>{{ $barang_habis }}</h3>
                  </div>
              </div>
          </div>
      </div>
    @endif

    @if (Auth::check() && Auth::user()->akses == 'karyawan')
      <div class="row">
          <div class="col-md-12">
              <div class="card shadow-lg">
                  <div class="card-header bg-primary text-white">
                      <h5 class="mb-0">SOP PEMINJAMAN UNTUK KARYAWAN</h5>
                  </div>
                  <div class="card-body">
                      <p class="mb-2"><strong>1. Pengajuan Peminjaman:</strong> Karyawan mengisi formulir permintaan barang melalui sistem.</p>
                      <p class="mb-2"><strong>2. Persetujuan :</strong> Permintaan harus disetujui oleh Admin atau Staff terkait.</p>
                      <p class="mb-2"><strong>3. Pengambilan Barang:</strong> Jika disetujui, karyawan dapat mengambil barang di gudang.</p>
                      <p class="mb-0"><strong>5. Konfirmasi:</strong> Admin mencatat status pengembalian barang di sistem.</p>
                  </div>
              </div>
          </div>
      </div>
    @endif

</div>
@endsection
