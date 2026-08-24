<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/koneksi.php";


/* =========================================
   HEADER EXCEL
========================================= */

$nama_file = "Data_Absensi_" . date("Y-m-d") . ".xls";

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$nama_file\"");
header("Pragma: no-cache");
header("Expires: 0");


/* =========================================
   AMBIL DATA ABSENSI
========================================= */

$query = mysqli_query(
    $conn,
    "SELECT
        absensi.*,
        siswa.nisn,
        siswa.nama AS nama_siswa,
        kelas.nama_kelas
    FROM absensi
    JOIN siswa ON absensi.id_siswa = siswa.id
    JOIN kelas ON siswa.id_kelas = kelas.id
    ORDER BY absensi.tanggal DESC, absensi.jam DESC"
);

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>

<body>

<h2>DATA ABSENSI SISWA SMAN 1 MALIGANO</h2>

<table border="1">

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

while ($data = mysqli_fetch_assoc($query)) {

?>

    <tr>

        <td><?php echo $no++; ?></td>

        <td>
            <?php echo htmlspecialchars($data["nisn"]); ?>
        </td>

        <td>
            <?php echo htmlspecialchars($data["nama_siswa"]); ?>
        </td>

        <td>
            <?php echo htmlspecialchars($data["nama_kelas"]); ?>
        </td>

        <td>
            <?php echo htmlspecialchars($data["tanggal"]); ?>
        </td>

        <td>
            <?php echo htmlspecialchars($data["jam"]); ?>
        </td>

        <td>
            <?php echo htmlspecialchars($data["status"]); ?>
        </td>

    </tr>

<?php

}

?>

</table>

</body>
</html>