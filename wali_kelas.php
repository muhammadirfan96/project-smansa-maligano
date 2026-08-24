<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/koneksi.php";

$query = mysqli_query(
    $conn,
    "SELECT * FROM wali_kelas ORDER BY nama ASC"
);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Wali Kelas - SMAN 1 Maligano</title>

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

    <h1>Data Wali Kelas</h1>

    <a href="tambah_wali_kelas.php" class="btn">
        + Tambah Wali Kelas
    </a>

    <a href="dashboard.php" class="btn">
        ← Dashboard
    </a>

    <table>

        <tr>
            <th>No</th>
            <th>Nama</th>
            <th>NIP</th>
            <th>No. HP</th>
            <th>Email</th>
<th>Aksi</th>
        </tr>

        <?php
        $no = 1;

        while ($data = mysqli_fetch_assoc($query)) {
        ?>

        <tr>
            <td><?php echo $no++; ?></td>

            <td>
                <?php echo htmlspecialchars($data["nama"]); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($data["nip"]); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($data["no_hp"]); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($data["email"]); ?>
            </td>
<td>

    <a
        href="edit_wali_kelas.php?id=<?php echo $data["id"]; ?>"
        class="btn-edit"
    >
        Edit
    </a>

    <a
        href="hapus_wali_kelas.php?id=<?php echo $data["id"]; ?>"
        class="btn-hapus"
        onclick="return confirm('Yakin ingin menghapus wali kelas ini?')"
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