```php
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/koneksi.php";
require_once "vendor/autoload.php";

use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

/* Ambil ID kelas */

if (!isset($_GET["id_kelas"])) {
    die("Kelas belum dipilih.");
}

$id_kelas = (int) $_GET["id_kelas"];


/* Ambil data kelas */

$query_kelas = mysqli_query(
    $conn,
    "SELECT * FROM kelas WHERE id = $id_kelas"
);

$kelas = mysqli_fetch_assoc($query_kelas);

if (!$kelas) {
    die("Data kelas tidak ditemukan.");
}


/* Ambil semua siswa dalam kelas */

$query_siswa = mysqli_query(
    $conn,
    "SELECT * FROM siswa
     WHERE id_kelas = $id_kelas
     ORDER BY nama ASC"
);


/* Buat isi HTML PDF */

$html = '
<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">

<style>

@page {
    margin: 10mm;
}

body {
    font-family: Arial, sans-serif;
}

/* Kartu ukuran KTP vertikal */
.kartu {
    width: 54mm;
    height: 85.6mm;
    border: 1px solid #000;
    text-align: center;
    padding: 4mm;
    box-sizing: border-box;
    page-break-after: always;
}

.sekolah {
    font-size: 11pt;
    font-weight: bold;
    margin-bottom: 8mm;
}

.nama {
    font-size: 13pt;
    font-weight: bold;
    margin-bottom: 3mm;
}

.kelas {
    font-size: 10pt;
    margin-bottom: 6mm;
}

.qr img {
    width: 42mm;
    height: 42mm;
}

.keterangan {
    font-size: 8pt;
    margin-top: 4mm;
}

</style>

</head>

<body>
';


$writer = new PngWriter();


while ($siswa = mysqli_fetch_assoc($query_siswa)) {

    /* Data unik QR */

    $data_qr =
        "SISWA-" .
        $siswa["id"] .
        "-" .
        $siswa["nisn"];


    /* Buat QR */

    $qrCode = new QrCode($data_qr);

    $result = $writer->write($qrCode);

    $qr_base64 =
        base64_encode(
            $result->getString()
        );


    /* Masukkan kartu */

    $html .= '

    <div class="kartu">

        <div class="sekolah">
            SMAN 1 MALIGANO
        </div>

        <div class="nama">
            ' .
            htmlspecialchars($siswa["nama"]) .
        '
        </div>

        <div class="kelas">
            Kelas: ' .
            htmlspecialchars($kelas["nama_kelas"]) .
        '
        </div>

        <div class="qr">

            <img
                src="data:image/png;base64,' .
                $qr_base64 .
                '"
            >

        </div>

        <div class="keterangan">
            QR CODE ABSENSI SISWA
        </div>

    </div>

    ';
}


$html .= '
</body>
</html>
';


/* Pengaturan Dompdf */

$options = new Options();

$options->set("isHtml5ParserEnabled", true);

$options->set("isRemoteEnabled", true);


/* Buat PDF */

$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);


/*
Ukuran halaman:

54 mm × 85.6 mm

1 mm = 2.83465 point
*/

$width = 54 * 2.83465;

$height = 85.6 * 2.83465;


$dompdf->setPaper(
    [0, 0, $width, $height],
    "portrait"
);


$dompdf->render();


/* Download PDF */

$nama_file =
    "QR_Code_" .
    preg_replace(
        "/[^A-Za-z0-9_-]/",
        "_",
        $kelas["nama_kelas"]
    ) .
    ".pdf";


$dompdf->stream(
    $nama_file,
    [
        "Attachment" => true
    ]
);

exit;
?>
```
