<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ATK BPJS</title>
  <link rel="shortcut icon" type="image/png" href="../assets/images/logos/favicon.png" />
  <link rel="stylesheet" href="{{asset('assets/css/styles.min.css')}}" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- DataTables css -->
  <link href="{{ asset('assets/DataTables/datatables.css') }}" rel="stylesheet" type="text/css" />
  <link href="{{ asset('assets/DataTables/datatables.min.css') }}" rel="stylesheet" type="text/css" />
  <link href="{{ asset('assets/libs/sweetalert2/sweetalert2.min.css')}}" rel="stylesheet" type="text/css" />
  <style>
    #mytable td:nth-child(2) { 
    width: 25%;
    text-align: center;
    }

    #mytable td:nth-child(2) img {
        max-width: 100%; /* Gambar tidak melebihi lebar kolom */
        height: auto; /* Biarkan tinggi menyesuaikan */
        object-fit: cover; /* Memastikan gambar tetap proporsional */
        border-radius: 5px;
    }
    #mytable td:nth-child(5) { 
        text-align: center;
    }

    #mytable td:nth-child(5) img {
        width: 100%; /* Mengisi seluruh kolom "Foto" (25%) */
        max-width: 180px; /* Batasi ukuran maksimal */
        height: auto; /* Menyesuaikan tinggi proporsional */
        object-fit: cover; /* Hindari gambar gepeng */
        border-radius: 5px; /* Opsional: sudut sedikit melengkung */
    }

</style>
</head>