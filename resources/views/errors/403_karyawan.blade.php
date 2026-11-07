<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Ditolak</title>

    <!-- Redirect ke dashboard setelah 5 detik -->
    <meta http-equiv="refresh" content="5;url={{ route('dashboard') }}">

    <style>
        body {
            text-align: center;
            padding: 50px;
            font-family: Arial, sans-serif;
        }
        .error-container {
            max-width: 600px;
            margin: auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>

    <div class="error-container">
        <h1>⚠️ Akses Ditolak!</h1>
        <p>Halaman ini hanya bisa diakses oleh karyawan.</p>
        <p>Anda akan dialihkan ke dashboard dalam <span id="countdown">5</span> detik...</p>
    </div>

    <script>
        // Countdown sebelum redirect
        let countdown = 5;
        const countdownElement = document.getElementById("countdown");

        setInterval(() => {
            if (countdown > 1) {
                countdown--;
                countdownElement.textContent = countdown;
            }
        }, 1000);
    </script>

</body>
</html>
