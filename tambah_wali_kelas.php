<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/koneksi.php";

$error = "";

if (isset($_POST["simpan"])) {

    $nama = mysqli_real_escape_string(
        $conn,
        $_POST["nama"]
    );

    $nip = mysqli_real_escape_string(
        $conn,
        $_POST["nip"]
    );

    $no_hp = mysqli_real_escape_string(
        $conn,
        $_POST["no_hp"]
    );

    $email = mysqli_real_escape_string(
        $conn,
        $_POST["email"]
    );

    $simpan = mysqli_query(
        $conn,
        "INSERT INTO wali_kelas
        (nama, nip, no_hp, email)
        VALUES
        ('$nama', '$nip', '$no_hp', '$email')"
    );

    if ($simpan) {

        header("Location: wali_kelas.php");
        exit;

    } else {

        $error = "Data wali kelas gagal disimpan.";

    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <title>Tambah Wali Kelas - SMAN 1 Maligano</title>

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

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            margin-top: 25px;
            padding: 12px 20px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .batal {
            margin-left: 10px;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 10px;
            margin-top: 15px;
            border-radius: 5px;
        }

    </style>

</head>

<body>

<div class="container">

    <h2>Tambah Wali Kelas</h2>

    <?php if (!empty($error)) { ?>

        <div class="error">
            <?php echo $error; ?>
        </div>

    <?php } ?>

    <form method="POST">

        <label>Nama Wali Kelas</label>

        <input
            type="text"
            name="nama"
            required
        >

        <label>NIP</label>

        <input
            type="text"
            name="nip"
        >

        <label>No. HP</label>

        <input
            type="text"
            name="no_hp"
        >

        <label>Email</label>

        <input
            type="email"
            name="email"
        >

        <button type="submit" name="simpan">
            Simpan Data
        </button>

        <a href="wali_kelas.php" class="batal">
            Batal
        </a>

    </form>

</div>

</body>
</html>