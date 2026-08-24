<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/koneksi.php";


/* =========================================
   ZONA WAKTU
========================================= */

date_default_timezone_set("Asia/Makassar");


/* =========================================
   TANGGAL DAN JAM SEKARANG
========================================= */

$tanggal = date("Y-m-d");

$jam_sekarang = date("H:i:s");


/* =========================================
   BATAS PROSES ALPA
========================================= */

$batas_alpa = "07:31:00";


/* =========================================
   CEK WAKTU
========================================= */

if ($jam_sekarang < $batas_alpa) {

    $_SESSION["pesan_alpa"] =
        "Proses Alpa belum dapat dilakukan. "
        . "Alpa otomatis dimulai pukul 07:31.";

    header("Location: dashboard.php");
    exit;
}


/* =========================================
   AMBIL SEMUA SISWA
   YANG BELUM MEMILIKI ABSENSI HARI INI
========================================= */

$query_siswa = mysqli_query(
    $conn,
    "
    SELECT siswa.id

    FROM siswa

    LEFT JOIN absensi
        ON siswa.id = absensi.id_siswa
        AND absensi.tanggal = '$tanggal'

    WHERE absensi.id IS NULL
    "
);


/* =========================================
   MASUKKAN SEBAGAI ALPA
========================================= */

$jumlah_alpa = 0;


while ($siswa = mysqli_fetch_assoc($query_siswa)) {

    $id_siswa = (int) $siswa["id"];


    $simpan = mysqli_query(
        $conn,
        "
        INSERT INTO absensi
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
            '07:31:00',
            'Alpa'
        )
        "
    );


    if ($simpan) {
        $jumlah_alpa++;
    }

}


/* =========================================
   PESAN HASIL
========================================= */

$_SESSION["pesan_alpa"] =
    "Proses Alpa selesai. "
    . $jumlah_alpa
    . " siswa ditandai sebagai Alpa.";


/* =========================================
   KEMBALI KE DASHBOARD
========================================= */

header("Location: dashboard.php");

exit;

?>