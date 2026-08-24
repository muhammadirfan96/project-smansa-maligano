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

/* Data unik di dalam QR Code */
$data_qr = "SISWA-" . $data["id"] . "-" . $data["nisn"];

/* Buat QR Code */
$qrCode = new QrCode($data_qr);

$writer = new PngWriter();

$result = $writer->write($qrCode);

/* Ubah QR menjadi Base64 agar bisa tampil di halaman */
$qr_base64 = base64_encode($result->getString());
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <title>QR Code Absensi</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            padding: 30px;
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

        .qr {
            margin-top: 20px;
        }

        .qr img {
            width: 300px;
            max-width: 100%;
        }

        .keterangan {
            margin-top: 15px;
            font-size: 14px;
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

</div>

</body>
</html>