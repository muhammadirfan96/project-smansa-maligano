<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/koneksi.php";

/* =========================
   AMBIL DATA PENCARIAN
========================= */

$cari = "";

if (isset($_GET["cari"])) {
    $cari = mysqli_real_escape_string(
        $conn,
        $_GET["cari"]
    );
}

/* =========================
   AMBIL FILTER KELAS
========================= */

$id_kelas = 0;

if (isset($_GET["id_kelas"])) {
    $id_kelas = (int) $_GET["id_kelas"];
}

/* =========================
   AMBIL DAFTAR KELAS
========================= */

$query_kelas = mysqli_query(
    $conn,
    "SELECT * FROM kelas ORDER BY nama_kelas ASC"
);

/* =========================
   QUERY DATA SISWA
========================= */

$sql = "
    SELECT siswa.*, kelas.nama_kelas
    FROM siswa
    JOIN kelas ON siswa.id_kelas = kelas.id
    WHERE 1=1
";

/* Filter pencarian */

if (!empty($cari)) {

    $sql .= "
        AND (
            siswa.nisn LIKE '%$cari%'
            OR siswa.nama LIKE '%$cari%'
            OR kelas.nama_kelas LIKE '%$cari%'
        )
    ";
}

/* Filter kelas */

if ($id_kelas > 0) {

    $sql .= "
        AND siswa.id_kelas = $id_kelas
    ";
}

/* Urutkan berdasarkan nama */

$sql .= "
    ORDER BY siswa.nama ASC
";

$query = mysqli_query($conn, $sql);

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <title>Data Siswa - SMAN 1 Maligano</title>

    <style>

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

        .btn {
            display: inline-block;
            padding: 10px 15px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
            margin-right: 5px;
        }

        .btn-edit {
            display: inline-block;
            padding: 7px 12px;
            background: #f59e0b;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .btn-hapus {
            display: inline-block;
            padding: 7px 12px;
            background: #dc2626;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-left: 5px;
        }
.btn-cetak {
    display: inline-block;
    padding: 7px 12px;
    background: #059669;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    margin-left: 5px;
}
.btn-qr {
    display: inline-block;
    padding: 7px 12px;
    background: #7c3aed;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    margin-left: 5px;
}

        .pencarian {
            margin-bottom: 20px;
        }

        .pencarian input,
        .pencarian select {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .pencarian input {
            width: 250px;
        }

        .pencarian select {
            width: 200px;
            margin-left: 5px;
        }

        .pencarian button {
            padding: 10px 15px;
            background: #16a34a;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-left: 5px;
        }

        .btn-reset {
            display: inline-block;
            padding: 10px 15px;
            background: #64748b;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-left: 5px;
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

    </style>

</head>

<body>

<div class="container">

    <h1>Data Siswa</h1>

    <a href="tambah_siswa.php" class="btn">
        + Tambah Siswa
    </a>

    <a href="import_siswa.php" class="btn">
        ⬆ Import Excel
    </a>

    <a href="dashboard.php" class="btn">
        ← Dashboard
    </a>

    <!-- PENCARIAN DAN FILTER -->

    <form method="GET" class="pencarian">

        <input
            type="text"
            name="cari"
            placeholder="Cari nama atau NISN..."
            value="<?php echo htmlspecialchars($cari); ?>"
        >

        <select name="id_kelas">

            <option value="0">
                Semua Kelas
            </option>

            <?php while ($kelas = mysqli_fetch_assoc($query_kelas)) { ?>

                <option
                    value="<?php echo $kelas["id"]; ?>"

                    <?php
                    if ($id_kelas == $kelas["id"]) {
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

        <button type="submit">
            🔍 Tampilkan
        </button>

        <a href="siswa.php" class="btn-reset">
            Reset
        </a>

    </form>

    <!-- TABEL SISWA -->

    <table>

        <tr>
            <th>No</th>
            <th>NISN</th>
            <th>Nama Siswa</th>
            <th>Jenis Kelamin</th>
            <th>Kelas</th>
            <th>Aksi</th>
        </tr>

        <?php

        $no = 1;

        while ($data = mysqli_fetch_assoc($query)) {

        ?>

            <tr>

                <td>
                    <?php echo $no++; ?>
                </td>

                <td>
                    <?php echo htmlspecialchars($data["nisn"]); ?>
                </td>

                <td>
                    <?php echo htmlspecialchars($data["nama"]); ?>
                </td>

                <td>
                    <?php echo htmlspecialchars(
                        $data["jenis_kelamin"]
                    ); ?>
                </td>

                <td>
                    <?php echo htmlspecialchars(
                        $data["nama_kelas"]
                    ); ?>
                </td>

                <td>

                   <a
    href="edit_siswa.php?id=<?php echo $data["id"]; ?>"
    class="btn-edit"
>
    Edit
</a>

<a
    href="qr_siswa.php?id=<?php echo $data["id"]; ?>"
    class="btn-qr"
    target="_blank"
>
    QR Code
</a>
<a
    href="cetak_qr.php?id=<?php echo $data["id"]; ?>"
    class="btn-cetak"
    target="_blank"
>
    🖨 Cetak QR
</a>
<a
    href="hapus_siswa.php?id=<?php echo $data["id"]; ?>"
    class="btn-hapus"
    onclick="return confirm('Yakin ingin menghapus siswa ini?')"
>
    Hapus
</a>
                </td>

            </tr>

        <?php } ?>

    </table>

</div>

</body>
</html>