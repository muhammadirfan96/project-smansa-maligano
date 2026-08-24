<?php

session_start();

header("Content-Type: application/json");

if (!isset($_SESSION["user_id"])) {

    echo json_encode([
        "status" => "error",
        "pesan" => "Silakan login terlebih dahulu."
    ]);

    exit;
}

require_once "config/koneksi.php";


/* =========================================
   ATUR ZONA WAKTU INDONESIA
========================================= */

date_default_timezone_set("Asia/Makassar");


/* =========================================
   AMBIL DATA QR
========================================= */

$qr = $_POST["qr"] ?? "";


/* =========================================
   CEK FORMAT QR
   Format: SISWA-ID-NISN
========================================= */

if (!preg_match('/^SISWA-(\d+)-(.+)$/', $qr, $matches)) {

    echo json_encode([
        "status" => "error",
        "pesan" => "Format QR Code tidak valid."
    ]);

    exit;
}


$id_siswa = (int) $matches[1];


/* =========================================
   CARI DATA SISWA
========================================= */

$query_siswa = mysqli_query(
    $conn,
    "SELECT
        siswa.*,
        kelas.nama_kelas

     FROM siswa

     JOIN kelas
        ON siswa.id_kelas = kelas.id

     WHERE siswa.id = $id_siswa"
);


$siswa = mysqli_fetch_assoc($query_siswa);


if (!$siswa) {

    echo json_encode([
        "status" => "error",
        "pesan" => "Data siswa tidak ditemukan."
    ]);

    exit;
}


/* =========================================
   TANGGAL DAN JAM SEKARANG
========================================= */

$tanggal = date("Y-m-d");

$jam = date("H:i:s");


/* =========================================
   ATURAN WAKTU ABSENSI

   00:00 - 07:00  = Hadir
   07:00:01-07:30 = Terlambat
   Setelah 07:30  = Ditutup
========================================= */

$batas_hadir = "07:00:00";

$batas_terlambat = "07:30:00";


/* =========================================
   CEK SUDAH ABSEN ATAU BELUM
========================================= */

$cek_absen = mysqli_query(
    $conn,
    "SELECT id
     FROM absensi

     WHERE id_siswa = $id_siswa
     AND tanggal = '$tanggal'"
);


if (mysqli_num_rows($cek_absen) > 0) {

    echo json_encode([
        "status" => "sudah_absen",
        "pesan" => "Siswa sudah memiliki data absensi hari ini.",
        "nama" => $siswa["nama"],
        "kelas" => $siswa["nama_kelas"]
    ]);

    exit;
}


/* =========================================
   TENTUKAN STATUS BERDASARKAN JAM
========================================= */

if ($jam <= $batas_hadir) {

    $status_absensi = "Hadir";

}

elseif ($jam <= $batas_terlambat) {

    $status_absensi = "Terlambat";

}

else {

    echo json_encode([
        "status" => "ditutup",
        "pesan" => "Kasihan... Absensi QR sudah ditutup. Siswa yang belum memiliki absensi akan diproses sebagai Alpa.",
        "nama" => $siswa["nama"],
        "kelas" => $siswa["nama_kelas"]
    ]);

    exit;
}


/* =========================================
   SIMPAN ABSENSI
========================================= */

$simpan = mysqli_query(
    $conn,
    "INSERT INTO absensi
    (
        id_siswa,
        tanggal,
        jam,
        status
    )

    VALUES
    (
        $id_siswa,
        '$tanggal',
        '$jam',
        '$status_absensi'
    )"
);


/* =========================================
   JIKA BERHASIL
========================================= */

if ($simpan) {

    echo json_encode([

        "status" => "berhasil",

        "pesan" => "Absensi berhasil disimpan.",

        "nama" => $siswa["nama"],

        "kelas" => $siswa["nama_kelas"],

        "jam" => $jam,

        "status_absensi" => $status_absensi

    ]);

} else {

    echo json_encode([

        "status" => "error",

        "pesan" => "Absensi gagal disimpan."

    ]);

}

?>