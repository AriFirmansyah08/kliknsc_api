@extends('home')

@section('content')
<div class="container">
    <h2 class="mb-4">Daftar Pengajuan Barang</h2>

    <!-- Tombol Tambah -->
    <button class="btn btn-primary mb-3 text-white fw-bold" data-toggle="modal" data-target="#tambahPengajuanModal">
        Tambah Pengajuan
    </button>

    <!-- Modal Tambah Pengajuan -->
<div class="modal fade" id="tambahPengajuanModal" tabindex="-1" role="dialog" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Tambah Pengajuan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="{{ route('pengajuan.store') }}" method="POST">
                    @csrf
                    <!-- Nama User tidak perlu dipilih, otomatis dari user yang login -->
                    <div class="form-group">
                        <label>Nama User</label>
                        <input type="text" class="form-control" value="{{ Auth::user()->name }}" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label for="id_barang">Nama Barang</label>
                        <select class="form-control" name="id_barang" required>
                            <option value="" disabled selected>Pilih Barang</option>
                            @foreach ($barang as $item)
                                <option value="{{ $item->id_barang }}">{{ $item->nama_barang }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="jumlah">Jumlah</label>
                        <input type="number" class="form-control" name="jumlah" min="1" required>
                    </div>
                    <div class="form-group">
                        <label for="keterangan">Keterangan</label>
                        <textarea class="form-control" name="keterangan" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">Simpan</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- Tabel Pengajuan -->
<table class="table table-bordered" id="mytable">
    <thead class="thead-dark">
        <tr>
            <th>ID</th>
            <th>Nama User</th>
            <th>Nama Barang</th>
            <th>Jumlah</th>
            <th>Tanggal</th>
            <th>Status</th>
            <th>Keterangan</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($pengajuan as $item)
        <tr id="row-{{ $item->id_pengajuan }}">
            <td>{{ $item->id_pengajuan }}</td>
            <td>{{ $item->nama_user }}</td>
            <td>{{ $item->nama_barang }}</td>
            <td>{{ $item->jumlah }}</td>
            <td>{{ $item->tanggal }}</td>
            <td>
                <span class="status-badge badge text-white px-3 py-2 fs-7 fw-bold 
                    @if($item->status == 'Menunggu') bg-success 
                    @elseif($item->status == 'Disetujui') bg-primary 
                    @elseif($item->status == 'Ditolak') bg-danger 
                    @endif">
                    {{ $item->status }}
                </span>
            </td>
            <td>{{ $item->keterangan }}</td>
            <td>
                <button class="btn btn-primary text-white fw-bold btn-sm print-btn" data-id="{{ $item->id_pengajuan }}">
                    Cetak
                </button>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).on('click', '.print-btn', function() {
        var id = $(this).data('id'); // Ambil ID Pengajuan
        if (!id) {
            alert('ID Pengajuan tidak ditemukan!');
            return;
        }

        // Redirect ke URL cetak PDF
        window.open('/pengajuan/cetak/' + id, '_blank');
    });
</script>

<script>
    $(document).ready(function () {
    $('.status-clickable').on('click', function () {
        var id_pengajuan = $(this).data('id');
        var $badge = $(this);

        Swal.fire({
            title: "Ubah Status Pengajuan",
            text: "Pilih status baru untuk pengajuan ini",
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Disetujui",
            cancelButtonText: "Ditolak",
            reverseButtons: true
        }).then((result) => {
            var newStatus = null;
            if (result.isConfirmed) {
                newStatus = "Disetujui";
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                newStatus = "Ditolak";
            }

            if (newStatus) {
                $.ajax({
                    url: "{{ route('pengajuan.updateStatus') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        id_pengajuan: id_pengajuan,
                        status: newStatus
                    },
                    success: function (response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Sukses!',
                                text: response.message,
                                timer: 3000,
                                showConfirmButton: false
                            });

                            $badge.text(newStatus);
                            $badge.removeClass('bg-success bg-primary bg-danger');

                            if (newStatus === 'Disetujui') {
                                $badge.addClass('bg-primary');
                            } else if (newStatus === 'Ditolak') {
                                $badge.addClass('bg-danger');
                            }

                            $badge.removeClass('status-clickable');
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: response.message,
                                timer: 3000,
                                showConfirmButton: false
                            });
                        }
                    },
                    error: function () {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: 'Terjadi kesalahan, coba lagi',
                            timer: 3000,
                            showConfirmButton: false
                        });
                    }
                });
            }
        });
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
