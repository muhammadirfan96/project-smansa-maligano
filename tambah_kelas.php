<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/koneksi.php";

if (isset($_POST["simpan"])) {

    $nama_kelas = mysqli_real_escape_string(
        $conn,
        $_POST["nama_kelas"]
    );

    $tingkat = mysqli_real_escape_string(
        $conn,
        $_POST["tingkat"]
    );

    $jurusan = mysqli_real_escape_string(
        $conn,
        $_POST["jurusan"]
    );

    $tahun_ajaran = mysqli_real_escape_string(
        $conn,
        $_POST["tahun_ajaran"]
    );

    $query = mysqli_query(
        $conn,
        "INSERT INTO kelas
        (nama_kelas, tingkat, jurusan, tahun_ajaran)
        VALUES
        ('$nama_kelas', '$tingkat', '$jurusan', '$tahun_ajaran')"
    );

    if ($query) {
        header("Location: kelas.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Kelas</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            padding: 40px;
        }

        .container {
            max-width: 500px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
        }

        input {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 15px;
            box-sizing: border-box;
        }

        button {
            padding: 12px 20px;
            border: none;
            background: #2563eb;
            color: white;
            cursor: pointer;
            border-radius: 5px;
        }

        a {
            margin-left: 10px;
        }
    </style>
</head>

<body>

<div class="container">

    <h2>Tambah Data Kelas</h2>

    <form method="POST">

        <label>Nama Kelas</label>

        <input
            type="text"
            name="nama_kelas"
            placeholder="Contoh: XI IPA 1"
            required
        >

        <label>Tingkat</label>

        <input
            type="text"
            name="tingkat"
            placeholder="Contoh: XI"
            required
        >

        <label>Jurusan</label>

        <input
            type="text"
            name="jurusan"
            placeholder="Contoh: IPA"
        >

        <label>Tahun Ajaran</label>

        <input
            type="text"
            name="tahun_ajaran"
            placeholder="Contoh: 2026/2027"
            required
        >

        <button type="submit" name="simpan">
            Simpan Kelas
        </button>

        <a href="kelas.php">
            Batal
        </a>

    </form>

</div>

</body>
</html>