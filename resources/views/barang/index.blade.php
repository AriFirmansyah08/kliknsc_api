@extends('home')

@section('content')
<div class="container">
    <h2 class="mb-4">Daftar Barang</h2>

    <!-- Tombol Tambah Barang -->
    <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#tambahBarangModal">
        Tambah Barang
    </button>
    <a href="{{ route('barang.cetak-pdf') }}" class="btn btn-danger mb-3" target="_blank">
    Cetak PDF
    </a>


    <!-- Modal Tambah Barang -->
    <div class="modal fade" id="tambahBarangModal" tabindex="-1" role="dialog" aria-labelledby="modalTitle" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah Barang</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('barang.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label for="nama_barang">Nama Barang</label>
                            <input type="text" class="form-control" name="nama_barang" required>
                        </div>
                        <div class="form-group">
                            <label for="kategori">Kategori</label>
                            <input type="text" class="form-control" name="kategori" required>
                        </div>
                        <div class="form-group">
                            <label for="stok">Stok</label>
                            <input type="number" class="form-control" name="stok" required>
                        </div>
                        <div class="form-group">
                            <label for="foto_barang">Foto Barang</label>
                            <input type="file" class="form-control" name="foto_barang">
                        </div>
                        <button type="submit" class="btn btn-success">Simpan</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Barang -->
    <table class="table table-bordered" id="mytable">
        <thead>
            <tr>
                <th style="width: 5%;">ID</th>
                <th style="width: 25%;">Nama Barang</th>
                <th style="width: 20%;">Kategori</th>
                <th style="width: 10%;">Stok</th>
                <th style="width: 25%;">Foto</th>
                <th style="width: 15%;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($barang as $item)
            <tr>
                <td>{{ $item->id_barang }}</td>
                <td>{{ $item->nama_barang }}</td>
                <td>{{ $item->kategori }}</td>
                <td>{{ $item->stok }}</td>
                <td>
                    @if ($item->foto_barang)
                        <img src="{{ asset('storage/' . $item->foto_barang) }}">
                    @else
                        Tidak Ada Foto
                    @endif
                </td>
                <td>
                    <button class="btn btn-warning btn-sm edit-barang" 
                        data-id="{{ $item->id_barang }}" 
                        data-nama="{{ $item->nama_barang }}" 
                        data-kategori="{{ $item->kategori }}" 
                        data-stok="{{ $item->stok }}"
                        data-keterangan="{{ $item->keterangan ?? '' }}" 
                        data-toggle="modal" 
                        data-target="#editBarangModal">
                        Edit
                    </button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Modal Edit Barang -->
<div class="modal fade" id="editBarangModal" tabindex="-1" role="dialog" aria-labelledby="editModalTitle" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalTitle">Edit Barang</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="" method="POST" id="editForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="edit_id" name="id_barang">
                    
                    <div class="form-group">
                        <label for="edit_nama_barang">Nama Barang</label>
                        <input type="text" class="form-control" id="edit_nama_barang" name="nama_barang">
                    </div>
                    <div class="form-group">
                        <label for="edit_kategori">Kategori</label>
                        <input type="text" class="form-control" id="edit_kategori" name="kategori">
                    </div>
                    <div class="form-group">
                        <label for="edit_stok">Stok</label>
                        <input type="number" class="form-control" name="stok" id="edit_stok" required>
                    </div>
                    <button type="submit" class="btn btn-success">Simpan Perubahan</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Tambahkan Bootstrap JS dan SweetAlert JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function () {
        $(".edit-barang").click(function () {
            var id = $(this).data('id');
            var nama_barang = $(this).data('nama');
            var kategori = $(this).data('kategori');
            var stok = $(this).data('stok');

            $("#edit_id").val(id);
            $("#edit_nama_barang").val(nama_barang);
            $("#edit_kategori").val(kategori);
            $("#edit_stok").val(stok);

            $("#editForm").attr("action", "/barang/update/" + id);
        });
    });
</script>

@if (session('success'))
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Sukses!',
            text: "{{ session('success') }}",
            timer: 3000,
            showConfirmButton: false
        });
    </script>
@endif

@if (session('success_edit'))
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Sukses!',
            text: "Data Berhasil Di Perbahrui",
            timer: 3000,
            showConfirmButton: false
        });
    </script>
@endif

@if ($errors->any())
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Gagal!',
            text: "Gagal Memperbarui Data",
            timer: 3000,
            showConfirmButton: false
        });
    </script>
@endif

@endsection
