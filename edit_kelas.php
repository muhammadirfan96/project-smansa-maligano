<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/koneksi.php";

if (!isset($_GET["id"])) {
    header("Location: kelas.php");
    exit;
}

$id = (int) $_GET["id"];

$query = mysqli_query(
    $conn,
    "SELECT * FROM kelas WHERE id = $id"
);

$data = mysqli_fetch_assoc($query);

if (!$data) {
    header("Location: kelas.php");
    exit;
}
$query_wali = mysqli_query(
    $conn,
    "SELECT * FROM wali_kelas ORDER BY nama ASC"
);

if (isset($_POST["update"])) {

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
$id_wali_kelas = (int) $_POST["id_wali_kelas"];

    $update = mysqli_query(
        $conn,
       "UPDATE kelas SET
    nama_kelas = '$nama_kelas',
    tingkat = '$tingkat',
    jurusan = '$jurusan',
    tahun_ajaran = '$tahun_ajaran',
    id_wali_kelas = '$id_wali_kelas'
WHERE id = $id"
    );

    if ($update) {
        header("Location: kelas.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Kelas - SMAN 1 Maligano</title>

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

       input,
select {
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

    <h2>Edit Data Kelas</h2>

    <form method="POST">

        <label>Nama Kelas</label>
        <input
            type="text"
            name="nama_kelas"
            value="<?php echo htmlspecialchars($data["nama_kelas"]); ?>"
            required
        >

        <label>Tingkat</label>
        <input
            type="text"
            name="tingkat"
            value="<?php echo htmlspecialchars($data["tingkat"]); ?>"
            required
        >

        <label>Jurusan</label>
        <input
            type="text"
            name="jurusan"
            value="<?php echo htmlspecialchars($data["jurusan"]); ?>"
        >

        <label>Tahun Ajaran</label>
        <input
            type="text"
            name="tahun_ajaran"
            value="<?php echo htmlspecialchars($data["tahun_ajaran"]); ?>"
            required
        >
<label>Wali Kelas</label>

<select name="id_wali_kelas">

    <option value="0">
        -- Pilih Wali Kelas --
    </option>

    <?php while ($wali = mysqli_fetch_assoc($query_wali)) { ?>

        <option
            value="<?php echo $wali["id"]; ?>"

            <?php
            if ($data["id_wali_kelas"] == $wali["id"]) {
                echo "selected";
            }
            ?>
        >

            <?php echo htmlspecialchars($wali["nama"]); ?>

        </option>

    <?php } ?>

</select>

        <button type="submit" name="update">
            Update Kelas
        </button>

        <a href="kelas.php">Batal</a>

    </form>

</div>

</body>
</html>