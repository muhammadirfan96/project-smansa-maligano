<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/koneksi.php";

$query = mysqli_query(
    $conn,
    "SELECT kelas.*, wali_kelas.nama AS nama_wali_kelas
     FROM kelas
     LEFT JOIN wali_kelas
     ON kelas.id_wali_kelas = wali_kelas.id
     ORDER BY kelas.id DESC"
);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Kelas - SMAN 1 Maligano</title>

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

        h1 {
            margin-top: 0;
        }

        .btn {
            display: inline-block;
            padding: 10px 15px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
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

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
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

    <h1>Data Kelas</h1>

    <a href="tambah_kelas.php" class="btn">
        + Tambah Kelas
    </a>

    <a href="dashboard.php" class="btn">
        ← Dashboard
    </a>

    <table>

        <tr>
            <th>No</th>
            <th>Nama Kelas</th>
            <th>Tingkat</th>
           <th>Jurusan</th>
<th>Tahun Ajaran</th>
<th>Wali Kelas</th>
<th>Aksi</th>
        </tr>

        <?php
        $no = 1;

        while ($data = mysqli_fetch_assoc($query)) {
        ?>

        <tr>
            <td><?php echo $no++; ?></td>

            <td>
                <?php echo htmlspecialchars($data["nama_kelas"]); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($data["tingkat"]); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($data["jurusan"]); ?>
            </td>

            <td>
    <?php echo htmlspecialchars($data["tahun_ajaran"]); ?>
</td>

<td>
    <?php
    if (!empty($data["nama_wali_kelas"])) {
        echo htmlspecialchars($data["nama_wali_kelas"]);
    } else {
        echo "-";
    }
    ?>
</td>

<td>
    <a href="edit_kelas.php?id=<?php echo $data["id"]; ?>" class="btn-edit">
        Edit
    </a>
    <a href="hapus_kelas.php?id=<?php echo $data["id"]; ?>"
       onclick="return confirm('Yakin ingin menghapus kelas ini?')"
       class="btn-hapus">
        Hapus
    </a>
</td>
        </tr>

        <?php } ?>

    </table>

</div>

</body>
</html>