<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/koneksi.php";


/* =========================================
   FILTER
========================================= */

$id_kelas = isset($_GET["kelas"])
    ? (int) $_GET["kelas"]
    : 0;

$id_buku = isset($_GET["buku"])
    ? (int) $_GET["buku"]
    : 0;

$status_filter = isset($_GET["status"])
    ? trim($_GET["status"])
    : "";

$cari = isset($_GET["cari"])
    ? trim($_GET["cari"])
    : "";


/* =========================================
   DATA KELAS
========================================= */

$query_kelas = mysqli_query(
    $conn,
    "SELECT id, nama_kelas
     FROM kelas
     ORDER BY nama_kelas ASC"
);


/* =========================================
   DATA BUKU
========================================= */

$query_buku = mysqli_query(
    $conn,
    "SELECT id, judul_buku
     FROM buku
     ORDER BY judul_buku ASC"
);


/* =========================================
   KONDISI FILTER SQL
========================================= */

$where = " WHERE 1=1 ";


/* FILTER KELAS */

if ($id_kelas > 0) {

    $where .= "
        AND siswa.id_kelas = $id_kelas
    ";
}


/* FILTER BUKU */

if ($id_buku > 0) {

    $where .= "
        AND peminjaman.id_buku = $id_buku
    ";
}


/* FILTER STATUS */

if (
    $status_filter == "Dipinjam" ||
    $status_filter == "Dikembalikan"
) {

    $status_aman =
        mysqli_real_escape_string(
            $conn,
            $status_filter
        );

    $where .= "
        AND peminjaman.status =
        '$status_aman'
    ";
}


/* PENCARIAN SISWA */

if ($cari != "") {

    $cari_aman =
        mysqli_real_escape_string(
            $conn,
            $cari
        );

    $where .= "
        AND (
            siswa.nama LIKE
            '%$cari_aman%'

            OR

            siswa.nisn LIKE
            '%$cari_aman%'
        )
    ";
}


/* =========================================
   TOTAL RIWAYAT
========================================= */

$query_total = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total

     FROM peminjaman

     JOIN siswa
        ON peminjaman.id_siswa =
        siswa.id

     $where"
);

$data_total =
    mysqli_fetch_assoc(
        $query_total
    );

$total_riwayat =
    $data_total["total"] ?? 0;


/* =========================================
   TOTAL MASIH DIPINJAM
========================================= */

$query_dipinjam = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total

     FROM peminjaman

     JOIN siswa
        ON peminjaman.id_siswa =
        siswa.id

     $where

     AND peminjaman.status =
     'Dipinjam'"
);

$data_dipinjam =
    mysqli_fetch_assoc(
        $query_dipinjam
    );

$total_dipinjam =
    $data_dipinjam["total"] ?? 0;


/* =========================================
   TOTAL DIKEMBALIKAN
========================================= */

$query_dikembalikan = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total

     FROM peminjaman

     JOIN siswa
        ON peminjaman.id_siswa =
        siswa.id

     $where

     AND peminjaman.status =
     'Dikembalikan'"
);

$data_dikembalikan =
    mysqli_fetch_assoc(
        $query_dikembalikan
    );

$total_dikembalikan =
    $data_dikembalikan["total"] ?? 0;


/* =========================================
   TOTAL TERLAMBAT

   Terlambat jika:

   Status = Dipinjam
   DAN
   Tanggal hari ini > tanggal kembali
========================================= */

$query_terlambat = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total

     FROM peminjaman

     JOIN siswa
        ON peminjaman.id_siswa =
        siswa.id

     $where

     AND peminjaman.status =
     'Dipinjam'

     AND peminjaman.tanggal_kembali
     < CURDATE()"
);

$data_terlambat =
    mysqli_fetch_assoc(
        $query_terlambat
    );

$total_terlambat =
    $data_terlambat["total"] ?? 0;


/* =========================================
   DATA RIWAYAT
========================================= */

$query_riwayat = mysqli_query(
    $conn,
    "SELECT

        peminjaman.id,

        peminjaman.tanggal_pinjam,

        peminjaman.tanggal_kembali,

        peminjaman.status,

        peminjaman.created_at,

        siswa.nama
            AS nama_siswa,

        siswa.nisn,

        kelas.nama_kelas,

        buku.judul_buku,

        buku_detail.nomor_buku,

        CASE

            WHEN
                peminjaman.status =
                'Dipinjam'

                AND

                peminjaman.tanggal_kembali
                < CURDATE()

            THEN 1

            ELSE 0

        END AS terlambat

     FROM peminjaman

     JOIN siswa
        ON peminjaman.id_siswa =
        siswa.id

     LEFT JOIN kelas
        ON siswa.id_kelas =
        kelas.id

     JOIN buku
        ON peminjaman.id_buku =
        buku.id

     JOIN buku_detail
        ON peminjaman.id_buku_detail =
        buku_detail.id

     $where

     ORDER BY
        peminjaman.created_at DESC,
        peminjaman.id DESC"
);

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
    Riwayat Peminjaman
</title>


<style>

* {
    box-sizing: border-box;
}


body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f1f5f9;
}


.container {
    max-width: 1400px;
    margin: 30px auto;
    padding: 30px;
    background: white;
    border-radius: 12px;
}


h1 {
    margin-top: 0;
}


.card {
    background: #f8fafc;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 25px;
}


.filter {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: end;
}


.form-group {
    display: flex;
    flex-direction: column;
    min-width: 200px;
    flex: 1;
}


label {
    font-weight: bold;
    margin-bottom: 8px;
}


input,
select {
    padding: 12px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 14px;
}


button {
    padding: 12px 20px;
    background: #2563eb;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
}


button:hover {
    background: #1d4ed8;
}


/* =========================================
   STATISTIK
========================================= */

.statistik {
    display: grid;

    grid-template-columns:
        repeat(
            auto-fit,
            minmax(200px, 1fr)
        );

    gap: 20px;

    margin-bottom: 25px;
}


.stat-card {
    padding: 20px;
    border-radius: 10px;
    color: white;
}


.stat-card h3 {
    margin-top: 0;
    font-size: 15px;
}


.stat-card .angka {
    font-size: 32px;
    font-weight: bold;
}


.total {
    background: #2563eb;
}


.dipinjam {
    background: #f59e0b;
}


.dikembalikan {
    background: #16a34a;
}


.terlambat {
    background: #dc2626;
}


/* =========================================
   TABEL
========================================= */

.table-wrapper {
    overflow-x: auto;
}


table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}


th,
td {
    border: 1px solid #e2e8f0;
    padding: 10px;
    text-align: left;
    white-space: nowrap;
}


th {
    background: #1e3a8a;
    color: white;
}


tr:hover {
    background: #f8fafc;
}


/* =========================================
   STATUS
========================================= */

.badge {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}


.badge-dipinjam {
    background: #fef3c7;
    color: #92400e;
}


.badge-dikembalikan {
    background: #dcfce7;
    color: #166534;
}


.badge-terlambat {
    background: #fee2e2;
    color: #991b1b;
}


.nomor-buku {
    font-weight: bold;
}


.kosong {
    text-align: center;
    padding: 25px;
}


.btn-menu {
    display: inline-block;
    padding: 10px 15px;
    background: #64748b;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    margin-right: 8px;
}


.btn-menu:hover {
    background: #475569;
}


/* =========================================
   RESPONSIVE
========================================= */

@media (max-width: 700px) {

    .container {
        margin: 10px;
        padding: 15px;
    }

    .filter {
        flex-direction: column;
    }

    .form-group {
        width: 100%;
    }

}

</style>

</head>


<body>


<div class="container">


<h1>
    📋 Riwayat Peminjaman Buku
</h1>


<!-- =========================================
     FILTER
========================================= -->

<div class="card">

<h2>
    🔎 Filter Riwayat
</h2>


<form method="GET">


<div class="filter">


<!-- KELAS -->

<div class="form-group">

<label>
    Kelas
</label>


<select name="kelas">

<option value="">

Semua Kelas

</option>


<?php

while (
    $kelas =
    mysqli_fetch_assoc(
        $query_kelas
    )
) {

?>

<option

    value="<?php
    echo $kelas["id"];
    ?>"

    <?php

    if (
        $id_kelas ==
        $kelas["id"]
    ) {

        echo "selected";

    }

    ?>

>

<?php

echo htmlspecialchars(
    $kelas["nama_kelas"]
);

?>

</option>


<?php } ?>


</select>

</div>


<!-- BUKU -->

<div class="form-group">

<label>
    Mata Pelajaran / Buku
</label>


<select name="buku">

<option value="">

Semua Buku

</option>


<?php

while (
    $buku =
    mysqli_fetch_assoc(
        $query_buku
    )
) {

?>

<option

    value="<?php
    echo $buku["id"];
    ?>"

    <?php

    if (
        $id_buku ==
        $buku["id"]
    ) {

        echo "selected";

    }

    ?>

>

<?php

echo htmlspecialchars(
    $buku["judul_buku"]
);

?>

</option>


<?php } ?>


</select>

</div>


<!-- STATUS -->

<div class="form-group">

<label>
    Status
</label>


<select name="status">

<option value="">

Semua Status

</option>


<option

    value="Dipinjam"

    <?php

    if (
        $status_filter ==
        "Dipinjam"
    ) {

        echo "selected";

    }

    ?>

>

Sedang Dipinjam

</option>


<option

    value="Dikembalikan"

    <?php

    if (
        $status_filter ==
        "Dikembalikan"
    ) {

        echo "selected";

    }

    ?>

>

Sudah Dikembalikan

</option>


</select>

</div>


<!-- CARI -->

<div class="form-group">

<label>
    Cari Siswa
</label>


<input

    type="text"

    name="cari"

    placeholder="Nama atau NISN"

    value="<?php
    echo htmlspecialchars(
        $cari
    );
    ?>"

>

</div>


<!-- BUTTON -->

<div>

<button type="submit">

🔎 Tampilkan

</button>

</div>


</div>


</form>


</div>


<!-- =========================================
     STATISTIK
========================================= -->

<div class="statistik">


<div class="stat-card total">

<h3>
📚 Total Riwayat
</h3>

<div class="angka">

<?php
echo $total_riwayat;
?>

</div>

</div>


<div class="stat-card dipinjam">

<h3>
📕 Sedang Dipinjam
</h3>

<div class="angka">

<?php
echo $total_dipinjam;
?>

</div>

</div>


<div class="stat-card dikembalikan">

<h3>
📗 Sudah Dikembalikan
</h3>

<div class="angka">

<?php
echo $total_dikembalikan;
?>

</div>

</div>


<div class="stat-card terlambat">

<h3>
⚠ Terlambat
</h3>

<div class="angka">

<?php
echo $total_terlambat;
?>

</div>

</div>


</div>


<!-- =========================================
     TABEL RIWAYAT
========================================= -->

<div class="card">


<h2>
📖 Daftar Riwayat
</h2>


<div class="table-wrapper">


<table>


<tr>

<th>No</th>

<th>Nama Siswa</th>

<th>NISN</th>

<th>Kelas</th>

<th>Buku</th>

<th>Nomor Buku</th>

<th>Tanggal Pinjam</th>

<th>Tanggal Kembali</th>

<th>Status</th>

<th>Keterangan</th>

</tr>


<?php


$no = 1;


if (
    mysqli_num_rows(
        $query_riwayat
    ) > 0
) {


    while (
        $data =
        mysqli_fetch_assoc(
            $query_riwayat
        )
    ) {

?>


<tr>


<td>

<?php
echo $no++;
?>

</td>


<td>

<?php

echo htmlspecialchars(
    $data["nama_siswa"]
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $data["nisn"]
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $data["nama_kelas"] ?? "-"
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $data["judul_buku"]
);

?>

</td>


<td class="nomor-buku">

<?php

echo htmlspecialchars(
    $data["nomor_buku"]
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $data["tanggal_pinjam"]
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $data["tanggal_kembali"]
);

?>

</td>


<td>


<?php

if (
    $data["status"] ==
    "Dipinjam"
) {

?>

<span
    class="badge badge-dipinjam"
>

Dipinjam

</span>


<?php

} else {

?>


<span
    class="badge badge-dikembalikan"
>

Dikembalikan

</span>


<?php } ?>


</td>


<td>


<?php

if (
    $data["terlambat"] == 1
) {

?>


<span
    class="badge badge-terlambat"
>

⚠ TERLAMBAT

</span>


<?php

} else {

?>


-

<?php } ?>


</td>


</tr>


<?php

    }


} else {

?>


<tr>

<td
    colspan="10"
    class="kosong"
>

Tidak ada data riwayat peminjaman.

</td>

</tr>


<?php } ?>


</table>


</div>


</div>


<!-- =========================================
     MENU
========================================= -->

<a
    href="peminjaman.php"
    class="btn-menu"
>

← Peminjaman

</a>


<a
    href="pengembalian.php"
    class="btn-menu"
>

← Pengembalian

</a>


<a
    href="buku.php"
    class="btn-menu"
>

← Data Buku

</a>


<a
    href="dashboard.php"
    class="btn-menu"
>

🏠 Dashboard

</a>


</div>


</body>

</html>