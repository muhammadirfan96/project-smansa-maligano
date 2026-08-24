<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/koneksi.php";


/* =========================================
   DATA LOGIN
========================================= */

$role = $_SESSION["role"] ?? "";

$id_wali_kelas = $_SESSION["id_wali_kelas"] ?? 0;


/* =========================================
   FILTER DATA ABSENSI
========================================= */

$where = "WHERE 1=1";


/* =========================================
   JIKA WALI KELAS
   HANYA MELIHAT KELAS SENDIRI
========================================= */

if ($role == "wali_kelas") {

    $id_kelas = $_SESSION["id_kelas"] ?? 0;

    if ($id_kelas == 0) {

        echo "Kelas wali kelas belum ditemukan.";
        exit;
    }

    $id_kelas = (int) $id_kelas;

    $where .= "
        AND siswa.id_kelas = $id_kelas
    ";
}


/* =========================================
   EXPORT CSV
========================================= */

if (isset($_GET["export"]) && $_GET["export"] == "csv") {

    $query_export = mysqli_query(
        $conn,
        "
        SELECT
            siswa.nisn,
            siswa.nama AS nama_siswa,
            kelas.nama_kelas,
            absensi.tanggal,
            absensi.jam,
            absensi.status

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


    $nama_file = "data_absensi";

    if ($role == "wali_kelas") {
        $nama_file .= "_kelas";
    }

    $nama_file .= "_" . date("Y-m-d") . ".csv";


    header("Content-Type: text/csv; charset=UTF-8");

    header(
        "Content-Disposition: attachment; filename=\"$nama_file\""
    );


    $output = fopen("php://output", "w");


    /* Agar huruf Excel terbaca dengan baik */

    fprintf(
        $output,
        chr(0xEF) .
        chr(0xBB) .
        chr(0xBF)
    );


    fputcsv(
        $output,
        [
            "No",
            "NISN",
            "Nama Siswa",
            "Kelas",
            "Tanggal",
            "Jam",
            "Status"
        ]
    );


    $no = 1;


    while (
        $data =
        mysqli_fetch_assoc($query_export)
    ) {

        fputcsv(
            $output,
            [
                $no++,
                $data["nisn"],
                $data["nama_siswa"],
                $data["nama_kelas"],
                $data["tanggal"],
                $data["jam"],
                $data["status"]
            ]
        );

    }


    fclose($output);

    exit;

}


/* =========================================
   AMBIL DATA ABSENSI
========================================= */

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
    Data Absensi - SMAN 1 Maligano
</title>


<style>

* {
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    margin: 40px;
    background: #f1f5f9;
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

    padding: 10px 15px;

    color: white;

    text-decoration: none;

    border-radius: 5px;

    margin-bottom: 20px;

    margin-right: 5px;

}

.btn-scan {
    background: #2563eb;
}

.btn-dashboard {
    background: #64748b;
}

.btn-export {
    background: #16a34a;
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

    padding: 12px;

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

.status-hadir {
    color: #15803d;
    font-weight: bold;
}

.status-terlambat {
    color: #d97706;
    font-weight: bold;
}

.status-sakit {
    color: #2563eb;
    font-weight: bold;
}

.status-izin {
    color: #7c3aed;
    font-weight: bold;
}

.status-alpa {
    color: #dc2626;
    font-weight: bold;
}

@media print {

    .btn {
        display: none;
    }

}

</style>

</head>


<body>


<div class="container">


<h1>Data Absensi Siswa</h1>


<a
    href="scan_absensi.php"
    class="btn btn-scan"
>
    📷 Scan QR Absensi
</a>

<a
    href="input_status_absensi.php"
    class="btn"
    style="background: #7c3aed;"
>
    📝 Input Sakit / Izin
</a>
<a
    href="absensi.php?export=csv"
    class="btn btn-export"
>
    ⬇ Ekspor Excel / CSV
</a>


<a
    href="dashboard.php"
    class="btn btn-dashboard"
>
    ← Dashboard
</a>



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


        $status_class = "";


        if ($data["status"] == "Hadir") {
            $status_class = "status-hadir";
        }

        elseif ($data["status"] == "Terlambat") {
            $status_class = "status-terlambat";
        }

        elseif ($data["status"] == "Sakit") {
            $status_class = "status-sakit";
        }

        elseif ($data["status"] == "Izin") {
            $status_class = "status-izin";
        }

        elseif ($data["status"] == "Alpa") {
            $status_class = "status-alpa";
        }

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


<td
    class="<?php
    echo $status_class;
    ?>"
>

<?php
echo htmlspecialchars(
    $data["status"]
);
?>

</td>


</tr>


<?php

    }

}

else {

?>


<tr>

<td
    colspan="7"
    class="kosong"
>

Belum ada data absensi.

</td>

</tr>


<?php } ?>


</table>


</div>


</body>

</html>