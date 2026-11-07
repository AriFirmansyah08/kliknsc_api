<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pengajuan Barang</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            text-align: center;
        }
        .status-badge {
            padding: 5px;
            font-weight: bold;
            text-align: center;
        }
        .bg-success {
            background-color: #28a745;
            color: white;
        }
        .bg-danger {
            background-color: #dc3545;
            color: white;
        }
        .bg-warning {
            background-color: #ffc107;
            color: black;
        }
    </style>
</head>
<body>

    <div class="title">Laporan Pengajuan Barang</div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama User</th>
                <th>Nama Barang</th>
                <th>Jumlah</th>
                <th>Tanggal</th>
                <th>Status</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($pengajuan as $item)
            <tr>
                <td>{{ $item->id_pengajuan }}</td>
                <td>{{ $item->nama_user }}</td>
                <td>{{ $item->nama_barang }}</td>
                <td>{{ $item->jumlah }}</td>
                <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') }}</td>
                <td style="text-align: center;">
                    <span class="status-badge 
                        @if($item->status == 'Menunggu') bg-warning
                        @elseif($item->status == 'Disetujui') bg-success
                        @elseif($item->status == 'Ditolak') bg-danger
                        @endif">
                        {{ $item->status }}
                    </span>
                </td>
                <td>{{ $item->keterangan }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
