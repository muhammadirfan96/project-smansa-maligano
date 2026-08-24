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


/* =========================================
   AMBIL KELAS
========================================= */

$id_kelas = 0;


/* JIKA WALI KELAS */

if ($role == "wali_kelas") {

    $id_kelas = $_SESSION["id_kelas"] ?? 0;

}


/* =========================================
   PROSES SIMPAN
========================================= */

$pesan = "";
$error = "";


if (isset($_POST["simpan"])) {

    $id_siswa = (int) ($_POST["id_siswa"] ?? 0);

    $tanggal = $_POST["tanggal"] ?? "";

    $status = $_POST["status"] ?? "";


    /* Validasi */

    if (
        $id_siswa == 0 ||
        empty($tanggal) ||
        !in_array($status, ["Sakit", "Izin"])
    ) {

        $error = "Data belum lengkap.";

    } else {


        /* =========================================
           CEK SISWA
        ========================================= */

        $query_siswa = mysqli_query(
            $conn,
            "
            SELECT *
            FROM siswa
            WHERE id = $id_siswa
            "
        );


        $siswa = mysqli_fetch_assoc($query_siswa);


        if (!$siswa) {

            $error = "Data siswa tidak ditemukan.";

        }


        /* =========================================
           JIKA WALI KELAS
           PASTIKAN SISWA MILIK KELASNYA
        ========================================= */

        elseif (
            $role == "wali_kelas" &&
            $siswa["id_kelas"] != $id_kelas
        ) {

            $error = "Anda tidak memiliki akses ke siswa ini.";

        }


        else {


            /* =========================================
               CEK ABSENSI
            ========================================= */

            $cek_absensi = mysqli_query(
                $conn,
                "
                SELECT id
                FROM absensi
                WHERE id_siswa = $id_siswa
                AND tanggal = '$tanggal'
                "
            );


            if (mysqli_num_rows($cek_absensi) > 0) {

                $error =
                    "Siswa sudah memiliki data absensi pada tanggal tersebut.";

            }


            else {


                /* =========================================
                   SIMPAN
                ========================================= */

                $jam = "00:00:00";


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
                        '$jam',
                        '$status'
                    )
                    "
                );


                if ($simpan) {

                    $pesan =
                        "Status absensi berhasil disimpan.";

                } else {

                    $error =
                        "Gagal menyimpan status absensi.";

                }

            }

        }

    }

}


/* =========================================
   AMBIL DATA SISWA
========================================= */

$where_siswa = "";


/* WALI KELAS HANYA SISWA KELASNYA */

if (
    $role == "wali_kelas" &&
    $id_kelas > 0
) {

    $where_siswa =
        "WHERE siswa.id_kelas = $id_kelas";

}


$query_siswa = mysqli_query(
    $conn,
    "
    SELECT
        siswa.id,
        siswa.nama,
        siswa.nisn,
        kelas.nama_kelas

    FROM siswa

    JOIN kelas
        ON siswa.id_kelas = kelas.id

    $where_siswa

    ORDER BY
        kelas.nama_kelas ASC,
        siswa.nama ASC
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
    Input Sakit / Izin
</title>

<style>

* {
    box-sizing: border-box;
}

body {

    font-family: Arial, sans-serif;

    background: #f1f5f9;

    padding: 40px;

}

.container {

    max-width: 600px;

    margin: auto;

    background: white;

    padding: 30px;

    border-radius: 10px;

}

h1 {

    margin-top: 0;

    text-align: center;

}

label {

    display: block;

    margin-top: 15px;

    margin-bottom: 7px;

    font-weight: bold;

}

select,
input {

    width: 100%;

    padding: 12px;

    border: 1px solid #ccc;

    border-radius: 6px;

}

button {

    width: 100%;

    padding: 12px;

    margin-top: 25px;

    border: none;

    border-radius: 6px;

    background: #2563eb;

    color: white;

    font-size: 16px;

    cursor: pointer;

}

.pesan {

    background: #dcfce7;

    color: #166534;

    padding: 12px;

    margin-bottom: 20px;

    border-radius: 6px;

}

.error {

    background: #fee2e2;

    color: #991b1b;

    padding: 12px;

    margin-bottom: 20px;

    border-radius: 6px;

}

.btn-kembali {

    display: block;

    text-align: center;

    margin-top: 20px;

    text-decoration: none;

}

</style>

</head>


<body>


<div class="container">


<h1>Input Sakit / Izin</h1>


<?php if ($pesan != "") { ?>

<div class="pesan">

<?php echo $pesan; ?>

</div>

<?php } ?>


<?php if ($error != "") { ?>

<div class="error">

<?php echo $error; ?>

</div>

<?php } ?>


<form method="POST">


<label>
Tanggal
</label>

<input
    type="date"
    name="tanggal"
    value="<?php echo date("Y-m-d"); ?>"
    required
>


<label>
Pilih Siswa
</label>

<select
    name="id_siswa"
    required
>

<option value="">

-- Pilih Siswa --

</option>


<?php

while (
    $data_siswa =
    mysqli_fetch_assoc($query_siswa)
) {

?>

<option
    value="<?php
    echo $data_siswa["id"];
    ?>"
>

<?php

echo htmlspecialchars(
    $data_siswa["nama"]
);

?>

-

<?php

echo htmlspecialchars(
    $data_siswa["nama_kelas"]
);

?>

</option>


<?php } ?>


</select>


<label>
Status
</label>

<select
    name="status"
    required
>

<option value="">

-- Pilih Status --

</option>

<option value="Sakit">

Sakit

</option>

<option value="Izin">

Izin

</option>

</select>


<button
    type="submit"
    name="simpan"
>

💾 Simpan Status Absensi

</button>


</form>


<a
    href="absensi.php"
    class="btn-kembali"
>

← Kembali ke Data Absensi

</a>


</div>


</body>

</html>