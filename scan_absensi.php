<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Scan Absensi QR - SMAN 1 Maligano</title>

    <script src="https://unpkg.com/html5-qrcode"></script>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            padding: 30px;
            text-align: center;
        }

        .container {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
        }

        h1 {
            margin-top: 0;
        }

        #reader {
            width: 100%;
            margin-top: 20px;
        }

        #hasil {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
            display: none;
        }

        .berhasil {
            background: #dcfce7;
            color: #166534;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 15px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

    </style>

</head>

<body>

<div class="container">

    <h1>Scan QR Absensi</h1>

    <p>
        Arahkan kamera ke QR Code siswa.
    </p>

    <div id="reader"></div>

    <div id="hasil"></div>

    <a href="dashboard.php" class="btn">
        ← Dashboard
    </a>

</div>


<script>

let sudahScan = false;

function tampilkanHasil(pesan, tipe) {

    const hasil = document.getElementById("hasil");

    hasil.style.display = "block";
    hasil.className = tipe;
    hasil.innerHTML = pesan;

}


function aktifkanScanLagi() {

    setTimeout(function () {

        sudahScan = false;

        document.getElementById("hasil").style.display = "none";

    }, 3000);

}


function onScanSuccess(decodedText, decodedResult) {

    if (sudahScan) {
        return;
    }

    // Mengunci pembacaan sementara
    sudahScan = true;

    tampilkanHasil(
        "Memproses absensi...",
        "berhasil"
    );


    fetch("proses_absensi.php", {

        method: "POST",

        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },

        body: "qr=" + encodeURIComponent(decodedText)

    })

    .then(function(response) {
        return response.json();
    })

    .then(function(data) {

        if (data.status === "berhasil") {

            tampilkanHasil(

                "<h3>✓ ABSENSI BERHASIL</h3>" +

                "<b>Nama:</b> " +
                data.nama +

                "<br><br>" +

                "<b>Kelas:</b> " +
                data.kelas +

                "<br><br>" +

                "<b>Jam:</b> " +
                data.jam +

                "<br><br>" +

                "<small>Siap scan siswa berikutnya...</small>",

                "berhasil"

            );

        } else if (data.status === "sudah_absen") {

            tampilkanHasil(

                "<h3>SISWA SUDAH ABSEN</h3>" +

                "<b>Nama:</b> " +
                data.nama +

                "<br><br>" +

                "<b>Kelas:</b> " +
                data.kelas +

                "<br><br>" +

                "<small>Siap scan siswa berikutnya...</small>",

                "error"

            );

        } else {

            tampilkanHasil(

                data.pesan +
                "<br><br>" +
                "<small>Siap scan kembali...</small>",

                "error"

            );

        }

        // Setelah 3 detik siap membaca QR berikutnya
        aktifkanScanLagi();

    })

    .catch(function(error) {

        console.error(error);

        tampilkanHasil(
            "Terjadi kesalahan saat memproses absensi.",
            "error"
        );

        aktifkanScanLagi();

    });

}


let html5QrcodeScanner = new Html5QrcodeScanner(

    "reader",

    {
        fps: 10,

        qrbox: {
            width: 250,
            height: 250
        }

    },

    false

);


html5QrcodeScanner.render(
    onScanSuccess
);

</script>

</body>
</html>