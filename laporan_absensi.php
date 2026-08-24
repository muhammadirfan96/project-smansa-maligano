<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/koneksi.php";

$role = $_SESSION["role"];

/* =========================
   FILTER AWAL
========================= */

$where = "WHERE 1=1";

/* =========================
   FILTER WALI KELAS
========================= */

if ($role == "wali_kelas") {

    if (!isset($_SESSION["id_kelas"])) {
        echo "Kelas wali kelas belum ditemukan.";
        exit;
    }

    $id_kelas = (int) $_SESSION["id_kelas"];

    $where .= "
        AND siswa.id_kelas = $id_kelas
    ";
}


/* =========================
   FILTER KELAS ADMIN
========================= */

$id_kelas_filter = "";

if (isset($_GET["id_kelas"])) {

    $id_kelas_filter = (int) $_GET["id_kelas"];

    if (
        $role == "admin" &&
        $id_kelas_filter > 0
    ) {

        $where .= "
            AND siswa.id_kelas = $id_kelas_filter
        ";
    }
}


/* =========================
   FILTER TANGGAL
========================= */

$tanggal_mulai = "";

$tanggal_selesai = "";

if (isset($_GET["tanggal_mulai"])) {
    $tanggal_mulai = $_GET["tanggal_mulai"];
}

if (isset($_GET["tanggal_selesai"])) {
    $tanggal_selesai = $_GET["tanggal_selesai"];
}


if ($tanggal_mulai != "") {

    $tanggal_mulai =
        mysqli_real_escape_string(
            $conn,
            $tanggal_mulai
        );

    $where .= "
        AND absensi.tanggal >= '$tanggal_mulai'
    ";
}


if ($tanggal_selesai != "") {

    $tanggal_selesai =
        mysqli_real_escape_string(
            $conn,
            $tanggal_selesai
        );

    $where .= "
        AND absensi.tanggal <= '$tanggal_selesai'
    ";
}


/* =========================
   AMBIL DATA ABSENSI
========================= */

$query = mysqli_query(
    $conn,
    "
    SELECT
        absensi.*,
        siswa.nisn,
        siswa.nama AS nama_siswa,
        kelas.nama_kelas

    FROM absensi

    JOIN siswa
        ON absensi.id_siswa = siswa.id

    JOIN kelas
        ON siswa.id_kelas = kelas.id

    $where

    ORDER BY
        absensi.tanggal DESC,
        absensi.jam DESC
    "
);


/* =========================
   DATA KELAS UNTUK ADMIN
========================= */

$query_kelas = null;

if ($role == "admin") {

    $query_kelas = mysqli_query(
        $conn,
        "
        SELECT *
        FROM kelas
        ORDER BY nama_kelas ASC
        "
    );
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
    Laporan Absensi
</title>

<style>

body {
    font-family: Arial, sans-serif;
    background: #f1f5f9;
    margin: 40px;
}

.container {
    background: white;
    padding: 30px;
    border-radius: 10px;
}

h1 {
    margin-top: 0;
}

.btn {
    display: inline-block;
    background: #2563eb;
    color: white;
    padding: 10px 15px;
    text-decoration: none;
    border-radius: 5px;
    margin-bottom: 20px;
}

.filter {
    background: #f8fafc;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 25px;
}

label {
    display: block;
    margin-top: 10px;
    margin-bottom: 5px;
    font-weight: bold;
}

input,
select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
}

button {
    margin-top: 20px;
    padding: 10px 20px;
    background: #2563eb;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.info-kelas {
    background: #e0f2fe;
    color: #075985;
    padding: 12px;
    border-radius: 5px;
    margin-bottom: 20px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

table,
th,
td {
    border: 1px solid #ddd;
}

th,
td {
    padding: 10px;
    text-align: left;
}

th {
    background: #2563eb;
    color: white;
}

.kosong {
    text-align: center;
    padding: 20px;
    color: #64748b;
}

</style>

</head>

<body>

<div class="container">

<h1>Laporan Absensi Siswa</h1>


<?php if ($role == "wali_kelas") { ?>

<div class="info-kelas">

    Menampilkan laporan kelas:

    <strong>
        <?php
        echo htmlspecialchars(
            $_SESSION["nama_kelas"]
        );
        ?>
    </strong>

</div>

<?php } ?>


<a
    href="dashboard.php"
    class="btn"
>
    ← Dashboard
</a>


<div class="filter">

<form method="GET">


<?php if ($role == "admin") { ?>

<label>Pilih Kelas</label>

<select name="id_kelas">

<option value="">
    Semua Kelas
</option>


<?php

while (
    $kelas =
    mysqli_fetch_assoc($query_kelas)
) {

?>

<option
    value="<?php echo $kelas["id"]; ?>"

    <?php

    if (
        $id_kelas_filter ==
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

<?php } ?>


<label>Tanggal Mulai</label>

<input
    type="date"
    name="tanggal_mulai"
    value="<?php
    echo htmlspecialchars(
        $tanggal_mulai
    );
    ?>"
>


<label>Tanggal Selesai</label>

<input
    type="date"
    name="tanggal_selesai"
    value="<?php
    echo htmlspecialchars(
        $tanggal_selesai
    );
    ?>"
>


<button type="submit">

    🔍 Tampilkan Laporan

</button>

</form>

</div>


<table>

<tr>

<th>No</th>

<th>NISN</th>

<th>Nama Siswa</th>

<th>Kelas</th>

<th>Tanggal</th>

<th>Jam</th>

<th>Status</th>

</tr>


<?php

$no = 1;

if (mysqli_num_rows($query) > 0) {

    while (
        $data =
        mysqli_fetch_assoc($query)
    ) {

?>

<tr>

<td>
    <?php echo $no++; ?>
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
        $data["nama_siswa"]
    );
    ?>
</td>

<td>
    <?php
    echo htmlspecialchars(
        $data["nama_kelas"]
    );
    ?>
</td>

<td>
    <?php
    echo htmlspecialchars(
        $data["tanggal"]
    );
    ?>
</td>

<td>
    <?php
    echo htmlspecialchars(
        $data["jam"]
    );
    ?>
</td>

<td>
    <?php
    echo htmlspecialchars(
        $data["status"]
    );
    ?>
</td>

</tr>

<?php

    }

} else {

?>

<tr>

<td
    colspan="7"
    class="kosong"
>

Tidak ada data absensi.

</td>

</tr>

<?php } ?>

</table>

</div>

</body>

</html>