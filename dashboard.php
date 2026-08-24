<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/koneksi.php";

date_default_timezone_set("Asia/Makassar");


/* =========================================
   DATA LOGIN
========================================= */

$role = $_SESSION["role"] ?? "";

$id_kelas = 0;


/* =========================================
   JIKA WALI KELAS
========================================= */

if ($role == "wali_kelas") {

    $id_kelas = (int) ($_SESSION["id_kelas"] ?? 0);

    if ($id_kelas == 0) {
        echo "Kelas wali kelas belum ditemukan.";
        exit;
    }
}


/* =========================================
   FILTER BERDASARKAN ROLE
========================================= */

$where_siswa = "";
$where_absensi = "";

if ($role == "wali_kelas") {

    $where_siswa = "
        WHERE siswa.id_kelas = $id_kelas
    ";

    $where_absensi = "
        AND siswa.id_kelas = $id_kelas
    ";
}


/* =========================================
   TANGGAL HARI INI
========================================= */

$tanggal = date("Y-m-d");


/* =========================================
   FUNGSI HITUNG TOTAL
========================================= */

function hitungTotal($conn, $sql)
{
    $query = mysqli_query($conn, $sql);

    if (!$query) {
        return 0;
    }

    $data = mysqli_fetch_assoc($query);

    return (int) ($data["total"] ?? 0);
}


/* =========================================
   TOTAL SISWA
========================================= */

$total_siswa = hitungTotal(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM siswa
    $where_siswa
    "
);


/* =========================================
   FUNGSI HITUNG STATUS ABSENSI
========================================= */

function hitungStatus(
    $conn,
    $tanggal,
    $status,
    $where_absensi
) {

    $status = mysqli_real_escape_string(
        $conn,
        $status
    );

    $query = mysqli_query(
        $conn,
        "
        SELECT COUNT(*) AS total

        FROM absensi

        JOIN siswa
            ON absensi.id_siswa = siswa.id

        WHERE absensi.tanggal = '$tanggal'
        AND absensi.status = '$status'

        $where_absensi
        "
    );

    if (!$query) {
        return 0;
    }

    $data = mysqli_fetch_assoc($query);

    return (int) ($data["total"] ?? 0);
}


/* =========================================
   HITUNG STATUS ABSENSI
========================================= */

$hadir = hitungStatus(
    $conn,
    $tanggal,
    "Hadir",
    $where_absensi
);

$terlambat = hitungStatus(
    $conn,
    $tanggal,
    "Terlambat",
    $where_absensi
);

$sakit = hitungStatus(
    $conn,
    $tanggal,
    "Sakit",
    $where_absensi
);

$izin = hitungStatus(
    $conn,
    $tanggal,
    "Izin",
    $where_absensi
);

$alpa = hitungStatus(
    $conn,
    $tanggal,
    "Alpa",
    $where_absensi
);


/* =========================================
   BELUM HADIR
========================================= */

$belum_hadir =
    $total_siswa
    - $hadir
    - $terlambat
    - $sakit
    - $izin
    - $alpa;

if ($belum_hadir < 0) {
    $belum_hadir = 0;
}


/* =========================================
   STATISTIK PERPUSTAKAAN
========================================= */


/* TOTAL KATEGORI BUKU */

$total_kategori = hitungTotal(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM kategori_buku
    "
);


/* TOTAL DATA BUKU */

$total_buku = hitungTotal(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM buku
    "
);


/* BUKU SEDANG DIPINJAM */

$total_dipinjam = hitungTotal(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM peminjaman
    WHERE status = 'Dipinjam'
    "
);


/* BUKU SUDAH DIKEMBALIKAN */

$total_dikembalikan = hitungTotal(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM peminjaman
    WHERE status = 'Dikembalikan'
    "
);


/* TOTAL RIWAYAT PEMINJAMAN */

$total_riwayat = hitungTotal(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM peminjaman
    "
);


/* BUKU BELUM DIKEMBALIKAN */

$total_belum_kembali = hitungTotal(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM peminjaman
    WHERE status = 'Dipinjam'
    "
);


/* =========================================
   JAM SEKARANG
========================================= */

$jam_sekarang = date("H:i:s");

$batas_alpa = "07:31:00";


/* =========================================
   PESAN PROSES ALPA
========================================= */

$pesan_alpa = "";

if (isset($_SESSION["pesan_alpa"])) {

    $pesan_alpa = $_SESSION["pesan_alpa"];

    unset($_SESSION["pesan_alpa"]);
}

?>

<!DOCTYPE html>

<html lang="id">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Dashboard - SMAN 1 Maligano
</title>


<style>

/* =========================================
   DASAR
========================================= */

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f1f5f9;
}


/* =========================================
   HEADER
========================================= */

.header {
    background: linear-gradient(
        135deg,
        #1e3a8a,
        #2563eb
    );

    color: white;

    padding: 18px 40px;

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 20px;
}


.header-left {
    display: flex;

    align-items: center;

    gap: 18px;
}


.logo-sekolah {
    width: 65px;

    height: 65px;

    object-fit: contain;

    background: white;

    padding: 5px;

    border-radius: 50%;
}


.header h1 {
    margin: 0;

    font-size: 24px;
}


.header-subtitle {
    margin-top: 5px;

    font-size: 13px;

    opacity: 0.9;
}


.user-info {
    text-align: right;
}


.user-role {
    display: inline-block;

    margin-top: 5px;

    padding: 5px 10px;

    background: rgba(255,255,255,0.15);

    border-radius: 20px;

    font-size: 13px;
}


.logout {
    display: inline-block;

    margin-top: 10px;

    color: white;

    text-decoration: none;

    background: #dc2626;

    padding: 9px 15px;

    border-radius: 6px;
}


.logout:hover {
    background: #b91c1c;
}


/* =========================================
   CONTAINER
========================================= */

.container {
    max-width: 1500px;

    margin: auto;

    padding: 20px 40px;
}


/* =========================================
   INFO KELAS
========================================= */

.info-kelas {
    background: #dbeafe;

    color: #1e40af;

    padding: 15px;

    border-radius: 8px;

    margin-bottom: 15px;
}


/* =========================================
   PESAN
========================================= */

.pesan {
    background: #dcfce7;

    color: #166534;

    padding: 15px;

    border-radius: 8px;

    margin-bottom: 20px;
}


/* =========================================
   MENU SECTION
========================================= */

.menu-section {
    margin-bottom: 25px;
}


.menu-title {
    margin-bottom: 12px;

    font-size: 20px;

    color: #1e293b;
}


.menu-grid {
    display: grid;

    grid-template-columns:
        repeat(
            auto-fit,
            minmax(190px, 1fr)
        );

    gap: 15px;
}


/* =========================================
   MENU CARD
========================================= */

.menu-card {

    display: block;

    position: relative;

    background: white;

    padding: 20px;

    text-decoration: none;

    color: #1e293b;

    border-radius: 12px;

    box-shadow:
        0 4px 10px rgba(0,0,0,0.05);

    transition:
        transform 0.2s,
        box-shadow 0.2s;
}


.menu-card:hover {

    transform:
        translateY(-4px);

    box-shadow:
        0 8px 20px rgba(0,0,0,0.12);
}


/* =========================================
   ANGKA PADA MENU PERPUSTAKAAN
========================================= */

.menu-jumlah {

    position: absolute;

    top: 15px;

    right: 18px;

    min-width: 35px;

    padding: 6px 10px;

    background: #2563eb;

    color: white;

    border-radius: 20px;

    text-align: center;

    font-size: 14px;

    font-weight: bold;
}


/* WARNA BERBEDA */

.jumlah-kategori {
    background: #f59e0b;
}

.jumlah-buku {
    background: #2563eb;
}

.jumlah-pinjam {
    background: #7c3aed;
}

.jumlah-kembali {
    background: #16a34a;
}

.jumlah-riwayat {
    background: #0284c7;
}

.jumlah-belum {
    background: #dc2626;
}


/* =========================================
   ICON MENU
========================================= */

.menu-icon {

    font-size: 32px;

    margin-bottom: 10px;
}


.menu-card strong {

    display: block;

    font-size: 16px;
}


.menu-card span {

    display: block;

    margin-top: 6px;

    color: #64748b;

    font-size: 13px;
}


/* =========================================
   STATISTIK ABSENSI
========================================= */

.section-title {

    margin-top: 35px;

    color: #1e293b;
}


.cards {

    display: grid;

    grid-template-columns:
        repeat(
            auto-fit,
            minmax(180px, 1fr)
        );

    gap: 15px;
}


.card {

    background: white;

    padding: 20px;

    border-radius: 10px;

    text-align: center;

    box-shadow:
        0 4px 10px rgba(0,0,0,0.05);
}


.card h3 {

    margin-top: 0;

    color: #64748b;

    font-size: 15px;
}


.card .jumlah {

    font-size: 30px;

    font-weight: bold;
}


/* =========================================
   WARNA STATISTIK
========================================= */

.total {
    border-bottom: 4px solid #2563eb;
}

.hadir {
    border-bottom: 4px solid #16a34a;
}

.terlambat {
    border-bottom: 4px solid #f59e0b;
}

.sakit {
    border-bottom: 4px solid #0284c7;
}

.izin {
    border-bottom: 4px solid #7c3aed;
}

.alpa {
    border-bottom: 4px solid #dc2626;
}

.belum {
    border-bottom: 4px solid #64748b;
}


/* =========================================
   PROSES ALPA
========================================= */

.proses-alpa {

    display: inline-block;

    margin-top: 25px;

    padding: 12px 18px;

    background: #dc2626;

    color: white;

    text-decoration: none;

    border-radius: 6px;
}


.proses-alpa:hover {
    background: #b91c1c;
}


/* =========================================
   JAM
========================================= */

.jam-info {

    margin-top: 20px;

    color: #64748b;
}


/* =========================================
   RESPONSIVE HP
========================================= */

@media (max-width: 800px) {

    .header {

        padding: 15px 20px;

        flex-direction: column;

        align-items: flex-start;
    }


    .user-info {

        text-align: left;
    }


    .container {

        padding: 15px;
    }


    .logo-sekolah {

        width: 55px;

        height: 55px;
    }


    .header h1 {

        font-size: 20px;
    }


    .menu-grid {

        grid-template-columns:
            repeat(
                2,
                minmax(0, 1fr)
            );
    }


    .menu-card {

        padding: 15px;
    }


    .menu-icon {

        font-size: 27px;
    }


    .menu-card strong {

        font-size: 14px;
    }


    .menu-card span {

        font-size: 11px;
    }
}

</style>

</head>


<body>


<!-- =========================================
     HEADER
========================================= -->

<div class="header">


<div class="header-left">


<img
    src="assets/logo.png"
    alt="Logo SMAN 1 Maligano"
    class="logo-sekolah"
>


<div>


<h1>
    SMAN 1 MALIGANO
</h1>


<div class="header-subtitle">

    Sistem Absensi & Perpustakaan

</div>


</div>


</div>


<div class="user-info">


<strong>

<?php
echo htmlspecialchars(
    $_SESSION["nama"] ?? ""
);
?>

</strong>


<br>


<div class="user-role">

<?php

echo htmlspecialchars(
    strtoupper(
        str_replace(
            "_",
            " ",
            $role
        )
    )
);

?>

</div>


<br>


<a
    href="logout.php"
    class="logout"
>

    🚪 Logout

</a>


</div>


</div>


<!-- =========================================
     CONTAINER
========================================= -->

<div class="container">


<?php if ($role == "wali_kelas") { ?>


<div class="info-kelas">

    Anda sedang melihat data kelas:

    <strong>

    <?php

    echo htmlspecialchars(
        $_SESSION["nama_kelas"] ?? ""
    );

    ?>

    </strong>

</div>


<?php } ?>


<?php if ($pesan_alpa != "") { ?>


<div class="pesan">

<?php
echo htmlspecialchars($pesan_alpa);
?>

</div>


<?php } ?>


<!-- =========================================
     MENU ADMIN
========================================= -->

<?php if ($role == "admin") { ?>


<div class="menu-section">


<h2 class="menu-title">

    ⚙️ Administrasi Sekolah

</h2>


<div class="menu-grid">


<a
    href="kelas.php"
    class="menu-card"
>

<div class="menu-icon">
🏫
</div>

<strong>
Data Kelas
</strong>

<span>
Kelola data kelas
</span>

</a>


<a
    href="siswa.php"
    class="menu-card"
>

<div class="menu-icon">
👨‍🎓
</div>

<strong>
Data Siswa
</strong>

<span>
Kelola data siswa
</span>

</a>


<a
    href="wali_kelas.php"
    class="menu-card"
>

<div class="menu-icon">
👨‍🏫
</div>

<strong>
Wali Kelas
</strong>

<span>
Kelola wali kelas
</span>

</a>


<a
    href="akun_wali_kelas.php"
    class="menu-card"
>

<div class="menu-icon">
🔐
</div>

<strong>
Akun Wali Kelas
</strong>

<span>
Kelola login wali kelas
</span>

</a>


</div>


</div>


<?php } ?>


<!-- =========================================
     MENU ABSENSI
========================================= -->

<div class="menu-section">


<h2 class="menu-title">

    📋 Sistem Absensi

</h2>


<div class="menu-grid">


<a
    href="scan_absensi.php"
    class="menu-card"
>

<div class="menu-icon">
📷
</div>

<strong>
Scan QR
</strong>

<span>
Scan QR siswa
</span>

</a>


<a
    href="absensi.php"
    class="menu-card"
>

<div class="menu-icon">
📋
</div>

<strong>
Data Absensi
</strong>

<span>
Lihat absensi siswa
</span>

</a>


<a
    href="input_status_absensi.php"
    class="menu-card"
>

<div class="menu-icon">
📝
</div>

<strong>
Sakit / Izin
</strong>

<span>
Input status siswa
</span>

</a>


<a
    href="laporan_absensi.php"
    class="menu-card"
>

<div class="menu-icon">
📊
</div>

<strong>
Laporan
</strong>

<span>
Laporan absensi
</span>

</a>


<a
    href="pilih_download_qr.php"
    class="menu-card"
>

<div class="menu-icon">
⬇️
</div>

<strong>
Download QR
</strong>

<span>
Download QR siswa
</span>

</a>


</div>


</div>


<!-- =========================================
     MENU PERPUSTAKAAN
========================================= -->

<div class="menu-section">


<h2 class="menu-title">

    📚 Sistem Perpustakaan

</h2>


<div class="menu-grid">


<!-- KATEGORI BUKU -->

<a
    href="kategori_buku.php"
    class="menu-card"
>

<div class="menu-jumlah jumlah-kategori">

    <?php echo $total_kategori; ?>

</div>


<div class="menu-icon">
🗂️
</div>

<strong>
Kategori Buku
</strong>

<span>
Kelola kategori buku
</span>

</a>


<!-- DATA BUKU -->

<a
    href="buku.php"
    class="menu-card"
>

<div class="menu-jumlah jumlah-buku">

    <?php echo $total_buku; ?>

</div>


<div class="menu-icon">
📚
</div>

<strong>
Data Buku
</strong>

<span>
Kelola buku perpustakaan
</span>

</a>


<!-- PEMINJAMAN -->

<a
    href="peminjaman.php"
    class="menu-card"
>

<div class="menu-jumlah jumlah-pinjam">

    <?php echo $total_dipinjam; ?>

</div>


<div class="menu-icon">
📤
</div>

<strong>
Peminjaman
</strong>

<span>
Buku sedang dipinjam
</span>

</a>


<!-- PENGEMBALIAN -->

<a
    href="pengembalian.php"
    class="menu-card"
>

<div class="menu-jumlah jumlah-kembali">

    <?php echo $total_dikembalikan; ?>

</div>


<div class="menu-icon">
🔄
</div>

<strong>
Pengembalian
</strong>

<span>
Buku sudah dikembalikan
</span>

</a>


<!-- RIWAYAT -->

<a
    href="riwayat_peminjaman.php"
    class="menu-card"
>

<div class="menu-jumlah jumlah-riwayat">

    <?php echo $total_riwayat; ?>

</div>


<div class="menu-icon">
📋
</div>

<strong>
Riwayat Peminjaman
</strong>

<span>
Total seluruh riwayat
</span>

</a>


<!-- BELUM DIKEMBALIKAN -->

<a
    href="buku_belum_dikembalikan.php"
    class="menu-card"
>

<div class="menu-jumlah jumlah-belum">

    <?php echo $total_belum_kembali; ?>

</div>


<div class="menu-icon">
⚠️
</div>

<strong>
Belum Dikembalikan
</strong>

<span>
Buku yang masih dipinjam
</span>

</a>


</div>


</div>


<!-- =========================================
     STATISTIK ABSENSI
========================================= -->

<h2 class="section-title">

    📊 Statistik Absensi Hari Ini

</h2>


<div class="cards">


<div class="card total">

<h3>
👨‍🎓 Total Siswa
</h3>

<div class="jumlah">

<?php echo $total_siswa; ?>

</div>

</div>


<div class="card hadir">

<h3>
✅ Hadir
</h3>

<div class="jumlah">

<?php echo $hadir; ?>

</div>

</div>


<div class="card terlambat">

<h3>
⏰ Terlambat
</h3>

<div class="jumlah">

<?php echo $terlambat; ?>

</div>

</div>


<div class="card sakit">

<h3>
🤒 Sakit
</h3>

<div class="jumlah">

<?php echo $sakit; ?>

</div>

</div>


<div class="card izin">

<h3>
📝 Izin
</h3>

<div class="jumlah">

<?php echo $izin; ?>

</div>

</div>


<div class="card alpa">

<h3>
❌ Alpa
</h3>

<div class="jumlah">

<?php echo $alpa; ?>

</div>

</div>


<div class="card belum">

<h3>
⚪ Belum Hadir
</h3>

<div class="jumlah">

<?php echo $belum_hadir; ?>

</div>

</div>


</div>


<!-- =========================================
     JAM
========================================= -->

<div class="jam-info">

Jam sekarang:

<strong>

<?php echo $jam_sekarang; ?>

</strong>

</div>


<?php if ($jam_sekarang >= $batas_alpa) { ?>


<a
    href="proses_alpa.php"
    class="proses-alpa"
>

❌ Proses Alpa Otomatis

</a>


<?php } ?>


</div>


</body>

</html>