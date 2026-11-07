@extends('home')

@section('content')
<div class="container">
    <h2 class="mb-4">Daftar Barang</h2>


    <!-- Tabel Barang -->
    <table class="table table-bordered" id="mytable">
        <thead>
            <tr>
                <th style="width: 5%;">ID</th>
                <th style="width: 25%;">Nama Barang</th>
                <th style="width: 20%;">Kategori</th>
                <th style="width: 10%;">Stok</th>
                <th style="width: 40%;">Foto</th>
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
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endsection
