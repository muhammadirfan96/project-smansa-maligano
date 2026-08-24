<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/koneksi.php";
require_once "vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\IOFactory;

$pesan = "";

if (isset($_POST["import"])) {

    if (isset($_FILES["file_excel"]) &&
        $_FILES["file_excel"]["error"] == 0) {

        $file_tmp = $_FILES["file_excel"]["tmp_name"];

        try {

            $spreadsheet = IOFactory::load($file_tmp);

            $sheet = $spreadsheet->getActiveSheet();

            $rows = $sheet->toArray();

            $berhasil = 0;
            $gagal = 0;

            foreach ($rows as $index => $row) {

                /* Lewati baris judul */
                if ($index == 0) {
                    continue;
                }

                $nama_kelas = trim($row[0] ?? "");
                $nisn = trim((string)($row[1] ?? ""));
                $nama = trim($row[2] ?? "");
                $jenis_kelamin = strtoupper(
                    trim($row[3] ?? "")
                );

                /* Lewati baris kosong */
                if (
                    empty($nama_kelas) ||
                    empty($nisn) ||
                    empty($nama)
                ) {
                    continue;
                }

                /* Pastikan hanya L atau P */
                if (
                    $jenis_kelamin != "L" &&
                    $jenis_kelamin != "P"
                ) {
                    $gagal++;
                    continue;
                }

                /* Cari kelas berdasarkan nama kelas */
                $kelas_query = mysqli_query(
                    $conn,
                    "SELECT id FROM kelas
                     WHERE nama_kelas = '" .
                    mysqli_real_escape_string(
                        $conn,
                        $nama_kelas
                    ) .
                    "'"
                );

                $kelas = mysqli_fetch_assoc($kelas_query);

                if (!$kelas) {
                    $gagal++;
                    continue;
                }

                $id_kelas = $kelas["id"];

                /* Cek apakah NISN sudah ada */
                $cek_nisn = mysqli_query(
                    $conn,
                    "SELECT id FROM siswa
                     WHERE nisn = '" .
                    mysqli_real_escape_string(
                        $conn,
                        $nisn
                    ) .
                    "'"
                );

                if (mysqli_num_rows($cek_nisn) > 0) {
                    $gagal++;
                    continue;
                }

                /* Simpan siswa */
                $insert = mysqli_query(
                    $conn,
                    "INSERT INTO siswa
                    (nisn, nama, jenis_kelamin, id_kelas)
                    VALUES (
                        '" . mysqli_real_escape_string(
                            $conn,
                            $nisn
                        ) . "',
                        '" . mysqli_real_escape_string(
                            $conn,
                            $nama
                        ) . "',
                        '" . mysqli_real_escape_string(
                            $conn,
                            $jenis_kelamin
                        ) . "',
                        '$id_kelas'
                    )"
                );

                if ($insert) {
                    $berhasil++;
                } else {
                    $gagal++;
                }
            }

            $pesan =
                "Import selesai. Berhasil: " .
                $berhasil .
                " siswa. Gagal/dilewati: " .
                $gagal .
                " siswa.";

        } catch (Exception $e) {

            $pesan =
                "Terjadi kesalahan membaca file Excel.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <title>
        Import Data Siswa - SMAN 1 Maligano
    </title>

    <style>

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

        input {
            margin: 20px 0;
        }

        button {
            padding: 12px 20px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .pesan {
            margin-top: 20px;
            padding: 15px;
            background: #e0f2fe;
        }

    </style>

</head>

<body>

<div class="container">

    <h2>Import Data Siswa dari Excel</h2>

    <p>
        Format kolom Excel:
        <b>KELAS | NISN | NAMA SISWA | L/P</b>
    </p>

    <form method="POST" enctype="multipart/form-data">

        <input
            type="file"
            name="file_excel"
            accept=".xlsx,.xls"
            required
        >

        <br>

        <button
            type="submit"
            name="import"
        >
            Import Data
        </button>

        <a href="siswa.php">
            Kembali
        </a>

    </form>

    <?php if (!empty($pesan)) { ?>

        <div class="pesan">
            <?php echo $pesan; ?>
        </div>

    <?php } ?>

</div>

</body>

</html>