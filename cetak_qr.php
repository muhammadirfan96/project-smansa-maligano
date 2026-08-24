<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/koneksi.php";
require_once "vendor/autoload.php";

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

if (!isset($_GET["id"])) {
    header("Location: siswa.php");
    exit;
}

$id = (int) $_GET["id"];

/* Ambil data siswa dan kelas */
$query = mysqli_query(
    $conn,
    "SELECT siswa.*, kelas.nama_kelas
     FROM siswa
     JOIN kelas ON siswa.id_kelas = kelas.id
     WHERE siswa.id = $id"
);

$data = mysqli_fetch_assoc($query);

if (!$data) {
    header("Location: siswa.php");
    exit;
}

/* Data QR */
$data_qr = "SISWA-" . $data["id"] . "-" . $data["nisn"];

/* Buat QR Code */
$qrCode = new QrCode($data_qr);

$writer = new PngWriter();

$result = $writer->write($qrCode);

$qr_base64 = base64_encode($result->getString());
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Cetak QR Code Siswa</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            padding: 30px;
            text-align: center;
        }

        .kartu {
            width: 400px;
            margin: auto;
            background: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px;
            box-sizing: border-box;
        }

        h2 {
            margin-top: 0;
        }

        .nama {
            font-size: 22px;
            font-weight: bold;
            margin-top: 10px;
        }

        .kelas {
            font-size: 18px;
            margin-top: 5px;
            margin-bottom: 20px;
        }

        .qr img {
            width: 300px;
            max-width: 100%;
        }

        .keterangan {
            margin-top: 15px;
            font-size: 14px;
        }

        .btn {
            display: inline-block;
            margin-top: 25px;
            padding: 10px 18px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        @media print {

            body {
                background: white;
                padding: 0;
            }

            .kartu {
                box-shadow: none;
                width: 100%;
            }

            .btn,
            .kembali {
                display: none;
            }

        }

    </style>

</head>

<body>

<div class="kartu">

    <h2>SMAN 1 MALIGANO</h2>

    <div class="nama">
        <?php echo htmlspecialchars($data["nama"]); ?>
    </div>

    <div class="kelas">
        Kelas: <?php echo htmlspecialchars($data["nama_kelas"]); ?>
    </div>

    <div class="qr">

        <img
            src="data:image/png;base64,<?php echo $qr_base64; ?>"
            alt="QR Code Absensi"
        >

    </div>

    <div class="keterangan">
        QR Code Absensi Siswa
    </div>

    <button
        class="btn"
        onclick="window.print()"
    >
        🖨 Cetak QR Code
    </button>

    <br>

    <a
        href="siswa.php"
        class="kembali"
    >
        ← Kembali ke Data Siswa
    </a>

</div>

</body>
</html>