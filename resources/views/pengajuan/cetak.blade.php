<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title class="text-center">Formulir Pengajuan ATK</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
        }
        .container {
            width: 100%;
            padding: 20px;
        }
        .header {
            margin-bottom: 20px;
        }
        /* table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 8px;
            text-align: left;
        } */
        .text-center {
            text-align: center;
        }
        .signature .row {
            display: flex;
            justify-content: space-between;
        }

        .signature .col-6 {
            width: 48%; /* Memberikan jarak antar kolom */
        }
        /* img { width: 60px; height: 60px; object-fit: cover; } */
        img {
            max-width: 100%; /* Gambar tidak melebihi lebar kolom */
            height: auto; /* Biarkan tinggi menyesuaikan */
            object-fit: cover; /* Memastikan gambar tetap proporsional */
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2 class="text-center">FORMULIR PERMINTAAN ATK</h2>
            <p><strong>Tanggal:</strong> {{ $pengajuan->tanggal }}</p>
            <p><strong>Nama Pemohon:</strong> {{ $pengajuan->nama_user }}</p>
            <p><strong>Email:</strong> {{ $pengajuan->email }}</p>
        </div>

        <table class="text-center" style="width: 100%; border-collapse: collapse; margin-top: 30px; text-align: center;">
            <thead style="border: 1px solid black; padding: 10px; background-color: #f2f2f2;">
                <tr>
                    <th style="border: 1px solid black; padding: 8px;">No</th>
                    <th style="border: 1px solid black; padding: 8px;">Nama Barang</th>
                    <th style="border: 1px solid black; padding: 8px;">Jumlah</th>
                    <th style="border: 1px solid black; padding: 8px;">Status</th>
                    <th style="border: 1px solid black; padding: 8px;">Kategori</th>
                </tr>
            </thead>
            <tbody style="border: 1px solid black;">
                <tr>
                    <td style="border: 1px solid black; padding: 8px; text-align: center;">{{ $pengajuan->id_pengajuan }}</td>
                    <td style="border: 1px solid black; padding: 8px;">{{ $pengajuan->nama_barang }}</td>
                    <td style="border: 1px solid black; padding: 8px; text-align: center;">{{ $pengajuan->jumlah }}</td>
                    <td style="border: 1px solid black; padding: 8px; text-align: center;">{{ $pengajuan->status }}</td>
                    <td style="border: 1px solid black; padding: 8px; text-align: center;">{{ $pengajuan->kategori }}</td>
                </tr>
                <tr>
                    <!-- Menambahkan colspan pada dua kolom terakhir agar lebar -->
                    <td style="border: 1px solid black; padding: 8px;" colspan="2">{{ $pengajuan->keterangan }}</td>
                    <!-- <td style="border: 1px solid black; padding: 8px;" colspan="2; text-align: center;">
                        <img src="{{ $pengajuan->foto_barang }}" alt="Foto Barang" width="50">
                    </td> -->
                    <td style="border: 1px solid black; padding: 8px;" colspan="3; text-align: center;">
                        @if ($pengajuan->foto_barang)
                            <img src="{{ public_path('storage/' . $pengajuan->foto_barang) }}" alt="Foto Barang">
                        @else
                            Tidak Ada Foto
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>



        <div class="signaturex">
            <!-- Baris pertama: Diajukan Oleh -->
            <div class="text-center" style="margin-bottom: 20px;">
                <p><strong>Diajukan Oleh,</strong></p>
                <br><br><br>
                <p>( {{ $pengajuan->nama_user }} )</p>
            </div>

            <!-- Baris kedua: Mengetahui & Menyetujui dalam satu baris -->
            <table class="text-center" style="width: 100%; border-collapse: collapse; margin-top: 30px; text-align: center;">
                <thead class="text-center">
                    <tr>
                        <th style="width: 50%; padding-bottom: 10px; vertical-align: middle;"><strong>Mengetahui,</strong></th>
                        <th style="width: 50%; padding-bottom: 10px; vertical-align: middle;"><strong>Menyetujui,</strong></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding-top: 40px; vertical-align: middle;">( Staff Admin )</td>
                        <td style="padding-top: 40px; vertical-align: middle;">( BPJS KETENAGAKERJAAN Sukabumi )</td>
                    </tr>
                </tbody>
            </table>

        </div>

    </div>
</body>
</html>
