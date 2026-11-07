@extends('home')

@section('content')

<div class="container">
    <h2>Manajemen karyawan</h2>
    
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addUserModal">Tambah User</button>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered" id="mytable">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 25%;">Foto</th>
                <th style="width: 15%;">Nama</th>
                <th style="width: 30%;">Email</th>
                <th style="width: 25%;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
            <tr>
                <td>{{ $loop->iteration }}</td> 
                <td>
                    @if($user->foto_user)
                        <img src="{{ asset('storage/'.$user->foto_user) }}">
                    @else
                        Tidak ada foto
                    @endif
                </td>

                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>
                    <button class="btn btn-sm btn-warning edit-user"
                        data-id="{{ $user->id }}"
                        data-name="{{ $user->name }}"
                        data-email="{{ $user->email }}"
                        data-akses="{{ $user->akses }}"
                        data-foto="{{ $user->foto_user }}"
                        data-bs-toggle="modal"
                        data-bs-target="#editUserModal">
                        Edit
                    </button>
                    
                    <button class="btn btn-sm btn-danger delete-user"
                        data-id="{{ $user->id }}"
                        data-name="{{ $user->name }}"
                        data-bs-toggle="modal"
                        data-bs-target="#deleteUserModal">
                        Hapus
                    </button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Modal Tambah User -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('users.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Nama</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Foto</label>
                        <input type="file" name="foto_user" class="form-control">
                    </div>
                    <input type="hidden" name="akses" value="karyawan">
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit User -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="editUserForm" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editId" name="id">
                    
                    <div class="mb-3">
                        <label>Nama</label>
                        <input type="text" id="editName" name="name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" id="editEmail" name="email" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Password (Kosongkan jika tidak ingin mengubah)</label>
                        <input type="password" id="editPassword" name="password" class="form-control">
                    </div>
                    <input type="hidden" id="editAkses" name="akses" value="karyawan">

                    <div class="mb-3">
                        <label>Foto Profil</label>
                        <input type="file" id="editFotoUser" name="foto_user" class="form-control">
                        <img id="editFotoPreview" src="" alt="Foto Profil" class="img-thumbnail mt-2" style="max-width: 150px;">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Hapus User -->
<div class="modal fade" id="deleteUserModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="deleteUserForm" method="POST">
            @csrf
            @method('DELETE')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus user <strong id="deleteUserName"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tambahkan Bootstrap JS (jika belum ada di layout utama) -->
 
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<script>
document.querySelectorAll('.edit-user').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('editId').value = this.dataset.id;
        document.getElementById('editName').value = this.dataset.name;
        document.getElementById('editEmail').value = this.dataset.email;
        document.getElementById('editAkses').value = this.dataset.akses;
        
        // Set URL action untuk update
        document.getElementById('editUserForm').action = "/users/update/" + this.dataset.id;

        // Jika ada foto, tampilkan di preview
        if (this.dataset.foto) {
            document.getElementById('editFotoPreview').src = "/storage/" + this.dataset.foto;
            document.getElementById('editFotoPreview').style.display = "block";
        } else {
            document.getElementById('editFotoPreview').style.display = "none";
        }
    });
});

// Preview gambar sebelum diunggah
document.getElementById('editFotoUser').addEventListener('change', function(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('editFotoPreview').src = e.target.result;
            document.getElementById('editFotoPreview').style.display = "block";
        };
        reader.readAsDataURL(file);
    }
});
</script>

<script>
document.querySelectorAll('.edit-user').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('editId').value = this.dataset.id;
        document.getElementById('editName').value = this.dataset.name;
        document.getElementById('editEmail').value = this.dataset.email;
        document.getElementById('editUserForm').action = "/users/update/" + this.dataset.id;
    });
});

document.querySelectorAll('.delete-user').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('deleteUserName').textContent = this.dataset.name;
        document.getElementById('deleteUserForm').action = "/users/delete/" + this.dataset.id;
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
            text: "Terjadi kesalahan saat menyimpan data",
            timer: 3000,
            showConfirmButton: false
        });
    </script>
@endif




@endsection
