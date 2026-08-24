```php
<?php

session_start();

/* =========================================
   BERSIHKAN OUTPUT AGAR ZIP TIDAK RUSAK
========================================= */

while (ob_get_level()) {
    ob_end_clean();
}

ob_start();


/* =========================================
   CEK LOGIN
========================================= */

if (!isset($_SESSION["user_id"])) {

    ob_end_clean();

    header("Location: login.php");
    exit;
}


/* =========================================
   KONEKSI DATABASE DAN AUTOLOAD
========================================= */

require_once "config/koneksi.php";
require_once "vendor/autoload.php";

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;


/* =========================================
   CEK ID KELAS
========================================= */

if (!isset($_GET["id_kelas"])) {

    ob_end_clean();

    die("Kelas belum dipilih.");
}

$id_kelas = (int) $_GET["id_kelas"];


/* =========================================
   AMBIL DATA KELAS
========================================= */

$query_kelas = mysqli_query(
    $conn,
    "SELECT * FROM kelas WHERE id = $id_kelas"
);

$kelas = mysqli_fetch_assoc($query_kelas);

if (!$kelas) {

    ob_end_clean();

    die("Data kelas tidak ditemukan.");
}


/* =========================================
   AMBIL DATA SISWA
========================================= */

$query_siswa = mysqli_query(
    $conn,
    "SELECT * FROM siswa
     WHERE id_kelas = $id_kelas
     ORDER BY nama ASC"
);

if (mysqli_num_rows($query_siswa) == 0) {

    ob_end_clean();

    die("Tidak ada siswa dalam kelas ini.");
}


/* =========================================
   CEK LOGO SEKOLAH
========================================= */

$logo_path = __DIR__ . "/assets/logo.png";

if (!file_exists($logo_path)) {

    ob_end_clean();

    die("Logo tidak ditemukan. Pastikan berada di assets/logo.png");
}


/* =========================================
   NAMA FILE ZIP
========================================= */

$nama_kelas_file = preg_replace(
    "/[^A-Za-z0-9_-]/",
    "_",
    $kelas["nama_kelas"]
);

$nama_download =
    "QR_" .
    $nama_kelas_file .
    ".zip";


/* =========================================
   FOLDER SEMENTARA
========================================= */

$temp_folder = __DIR__ . "/temp_qr";

if (!is_dir($temp_folder)) {

    mkdir(
        $temp_folder,
        0777,
        true
    );
}


/* =========================================
   BUAT FILE ZIP
========================================= */

$zip_path =
    $temp_folder .
    "/download_" .
    time() .
    "_" .
    uniqid() .
    ".zip";


$zip = new ZipArchive();

if (
    $zip->open(
        $zip_path,
        ZipArchive::CREATE | ZipArchive::OVERWRITE
    ) !== TRUE
) {

    ob_end_clean();

    die("Gagal membuat file ZIP.");
}


/* =========================================
   QR CODE WRITER
========================================= */

$writer = new PngWriter();

$jumlah_file = 0;


/* =========================================
   PROSES SETIAP SISWA
========================================= */

while ($siswa = mysqli_fetch_assoc($query_siswa)) {


    /* =====================================
       UKURAN KARTU
    ===================================== */

    $lebar = 600;
    $tinggi = 900;


    /* =====================================
       BUAT KANVAS
    ===================================== */

    $gambar = imagecreatetruecolor(
        $lebar,
        $tinggi
    );


    /* =====================================
       WARNA
    ===================================== */

    $putih = imagecolorallocate(
        $gambar,
        255,
        255,
        255
    );

    $hitam = imagecolorallocate(
        $gambar,
        20,
        20,
        20
    );

    $biru = imagecolorallocate(
        $gambar,
        30,
        99,
        180
    );

    $biru_tua = imagecolorallocate(
        $gambar,
        20,
        65,
        120
    );

    $biru_muda = imagecolorallocate(
        $gambar,
        225,
        240,
        255
    );

    $abu = imagecolorallocate(
        $gambar,
        90,
        90,
        90
    );


    /* =====================================
       BACKGROUND
    ===================================== */

    imagefill(
        $gambar,
        0,
        0,
        $putih
    );


    /* =====================================
       BORDER BIRU
    ===================================== */

    imagefilledrectangle(
        $gambar,
        0,
        0,
        $lebar - 1,
        $tinggi - 1,
        $biru
    );


    /* =====================================
       AREA DALAM PUTIH
    ===================================== */

    imagefilledrectangle(
        $gambar,
        10,
        10,
        $lebar - 11,
        $tinggi - 11,
        $putih
    );


    /* =====================================
       HEADER BIRU
    ===================================== */

    imagefilledrectangle(
        $gambar,
        10,
        10,
        $lebar - 11,
        205,
        $biru
    );


    /* GARIS HEADER */

    imagefilledrectangle(
        $gambar,
        10,
        200,
        $lebar - 11,
        205,
        $biru_tua
    );


    /* =====================================
       MASUKKAN LOGO
    ===================================== */

    $logo = imagecreatefrompng(
        $logo_path
    );

    if ($logo !== false) {

        $logo_lebar = 110;
        $logo_tinggi = 110;

        $logo_x = (int)(
            ($lebar - $logo_lebar) / 2
        );

        $logo_y = 18;


        imagealphablending(
            $gambar,
            true
        );


        imagecopyresampled(
            $gambar,
            $logo,
            $logo_x,
            $logo_y,
            0,
            0,
            $logo_lebar,
            $logo_tinggi,
            imagesx($logo),
            imagesy($logo)
        );


        imagedestroy($logo);
    }


    /* =====================================
       NAMA SEKOLAH
    ===================================== */

    $nama_sekolah = "SMAN 1 MALIGANO";


    $x_sekolah = (int)(
        (
            $lebar -
            imagefontwidth(5) *
            strlen($nama_sekolah)
        ) / 2
    );


    imagestring(
        $gambar,
        5,
        $x_sekolah,
        135,
        $nama_sekolah,
        $putih
    );


    /* =====================================
       JUDUL
    ===================================== */

    $judul = "KARTU ABSENSI SISWA";


    $x_judul = (int)(
        (
            $lebar -
            imagefontwidth(3) *
            strlen($judul)
        ) / 2
    );


    imagestring(
        $gambar,
        3,
        $x_judul,
        170,
        $judul,
        $putih
    );


    /* =====================================
       AREA DATA SISWA
    ===================================== */

    imagefilledrectangle(
        $gambar,
        45,
        230,
        $lebar - 45,
        355,
        $biru_muda
    );


    /* =====================================
       NAMA SISWA
    ===================================== */

    $nama_siswa = strtoupper(
        $siswa["nama"]
    );


    $font_nama = 5;

    if (strlen($nama_siswa) > 30) {
        $font_nama = 4;
    }

    if (strlen($nama_siswa) > 45) {
        $font_nama = 3;
    }


    $x_nama = (int)(
        (
            $lebar -
            imagefontwidth($font_nama) *
            strlen($nama_siswa)
        ) / 2
    );


    if ($x_nama < 20) {
        $x_nama = 20;
    }


    imagestring(
        $gambar,
        $font_nama,
        $x_nama,
        255,
        $nama_siswa,
        $hitam
    );


    /* =====================================
       KELAS
    ===================================== */

    $teks_kelas =
        "KELAS : " .
        strtoupper(
            $kelas["nama_kelas"]
        );


    $x_kelas = (int)(
        (
            $lebar -
            imagefontwidth(4) *
            strlen($teks_kelas)
        ) / 2
    );


    imagestring(
        $gambar,
        4,
        $x_kelas,
        310,
        $teks_kelas,
        $biru_tua
    );


    /* =====================================
       DATA UNTUK QR CODE
    ===================================== */

    $data_qr =
        "SISWA-" .
        $siswa["id"] .
        "-" .
        $siswa["nisn"];


    /* =====================================
       BUAT QR CODE
    ===================================== */

    $qrCode = new QrCode(
        data: $data_qr
    );


    $result = $writer->write(
        $qrCode
    );


    $qr_image = imagecreatefromstring(
        $result->getString()
    );


    /* =====================================
       BINGKAI QR
    ===================================== */

    $ukuran_bingkai = 430;

    $x_bingkai = (int)(
        ($lebar - $ukuran_bingkai) / 2
    );

    $y_bingkai = 385;


    imagefilledrectangle(
        $gambar,
        $x_bingkai,
        $y_bingkai,
        $x_bingkai + $ukuran_bingkai,
        $y_bingkai + $ukuran_bingkai,
        $biru
    );


    /* AREA PUTIH DI DALAM BINGKAI */

    imagefilledrectangle(
        $gambar,
        $x_bingkai + 10,
        $y_bingkai + 10,
        $x_bingkai + $ukuran_bingkai - 10,
        $y_bingkai + $ukuran_bingkai - 10,
        $putih
    );


    /* =====================================
       MASUKKAN QR CODE
    ===================================== */

    $ukuran_qr = 390;

    $x_qr = (int)(
        ($lebar - $ukuran_qr) / 2
    );

    $y_qr = 405;


    imagecopyresampled(
        $gambar,
        $qr_image,
        $x_qr,
        $y_qr,
        0,
        0,
        $ukuran_qr,
        $ukuran_qr,
        imagesx($qr_image),
        imagesy($qr_image)
    );


    /* =====================================
       FOOTER
    ===================================== */

    imagefilledrectangle(
        $gambar,
        10,
        840,
        $lebar - 11,
        $tinggi - 11,
        $biru_tua
    );


    $keterangan =
        "SCAN QR CODE UNTUK ABSENSI";


    $x_keterangan = (int)(
        (
            $lebar -
            imagefontwidth(3) *
            strlen($keterangan)
        ) / 2
    );


    imagestring(
        $gambar,
        3,
        $x_keterangan,
        865,
        $keterangan,
        $putih
    );


    /* =====================================
       UBAH GAMBAR MENJADI JPEG
    ===================================== */

    ob_start();

    imagejpeg(
        $gambar,
        null,
        95
    );

    $jpeg_data = ob_get_clean();


    /* =====================================
       NAMA FILE JPEG
    ===================================== */

    $nama_file = preg_replace(
        "/[^A-Za-z0-9_-]/",
        "_",
        $siswa["nama"]
    );


    $nama_file =
        $nama_file .
        "_" .
        $siswa["id"] .
        ".jpg";


    /* =====================================
       MASUKKAN JPEG KE ZIP
    ===================================== */

    if (
        $zip->addFromString(
            $nama_file,
            $jpeg_data
        )
    ) {
        $jumlah_file++;
    }


    /* =====================================
       BERSIHKAN MEMORY
    ===================================== */

    imagedestroy($gambar);

    imagedestroy($qr_image);
}


/* =========================================
   TUTUP FILE ZIP
========================================= */

$zip->close();


/* =========================================
   CEK HASIL ZIP
========================================= */

if (
    !file_exists($zip_path) ||
    filesize($zip_path) == 0 ||
    $jumlah_file == 0
) {

    ob_end_clean();

    die("Gagal membuat file ZIP.");
}


/* =========================================
   BERSIHKAN OUTPUT
========================================= */

ob_end_clean();


/* =========================================
   DOWNLOAD FILE ZIP
========================================= */

header("Content-Type: application/zip");

header(
    'Content-Disposition: attachment; filename="' .
    $nama_download .
    '"'
);

header(
    "Content-Length: " .
    filesize($zip_path)
);

header(
    "Content-Transfer-Encoding: binary"
);

header(
    "Cache-Control: must-revalidate"
);

header("Pragma: public");


/* KIRIM FILE */

readfile($zip_path);


/* =========================================
   HAPUS FILE SEMENTARA
========================================= */

unlink($zip_path);

exit;

?>
```
