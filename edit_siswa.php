<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/koneksi.php";

if (!isset($_GET["id"])) {
    header("Location: siswa.php");
    exit;
}

$id = (int) $_GET["id"];

$query_siswa = mysqli_query(
    $conn,
    "SELECT * FROM siswa WHERE id = $id"
);

$data = mysqli_fetch_assoc($query_siswa);

if (!$data) {
    header("Location: siswa.php");
    exit;
}

$query_kelas = mysqli_query(
    $conn,
    "SELECT * FROM kelas ORDER BY nama_kelas ASC"
);

if (isset($_POST["update"])) {

    $nisn = mysqli_real_escape_string(
        $conn,
        $_POST["nisn"]
    );

    $nama = mysqli_real_escape_string(
        $conn,
        $_POST["nama"]
    );

    $jenis_kelamin = mysqli_real_escape_string(
        $conn,
        $_POST["jenis_kelamin"]
    );

    $id_kelas = (int) $_POST["id_kelas"];

    $update = mysqli_query(
        $conn,
        "UPDATE siswa SET
            nisn = '$nisn',
            nama = '$nama',
            jenis_kelamin = '$jenis_kelamin',
            id_kelas = '$id_kelas'
        WHERE id = $id"
    );

    if ($update) {
        header("Location: siswa.php");
        exit;
    }

    $error = "Data siswa gagal diperbarui.";
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <title>Edit Siswa - SMAN 1 Maligano</title>

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
        }

        input,
        select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            box-sizing: border-box;
        }

        button {
            margin-top: 20px;
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
            padding: 10px;
            margin-top: 15px;
        }

    </style>

</head>

<body>

<div class="container">

    <h2>Edit Data Siswa</h2>

    <?php if (isset($error)) { ?>

        <div class="error">
            <?php echo $error; ?>
        </div>

    <?php } ?>

    <form method="POST">

        <label>NISN</label>

        <input
            type="text"
            name="nisn"
            value="<?php echo htmlspecialchars($data["nisn"]); ?>"
            required
        >

        <label>Nama Siswa</label>

        <input
            type="text"
            name="nama"
            value="<?php echo htmlspecialchars($data["nama"]); ?>"
            required
        >

        <label>Jenis Kelamin</label>

        <select name="jenis_kelamin" required>

            <option value="L"
                <?php
                if ($data["jenis_kelamin"] == "L") {
                    echo "selected";
                }
                ?>
            >
                Laki-laki
            </option>

            <option value="P"
                <?php
                if ($data["jenis_kelamin"] == "P") {
                    echo "selected";
                }
                ?>
            >
                Perempuan
            </option>

        </select>

        <label>Kelas</label>

        <select name="id_kelas" required>

            <?php
            while ($kelas = mysqli_fetch_assoc($query_kelas)) {
            ?>

                <option
                    value="<?php echo $kelas["id"]; ?>"

                    <?php
                    if ($data["id_kelas"] == $kelas["id"]) {
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

        <button type="submit" name="update">
            Update Siswa
        </button>

        <a href="siswa.php" class="batal">
            Batal
        </a>

    </form>

</div>

</body>
</html>