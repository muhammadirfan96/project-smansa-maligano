<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/koneksi.php";


/* ==============================
   TAMBAH KATEGORI
============================== */

$pesan = "";

if (isset($_POST["tambah"])) {

    $nama_kategori = trim($_POST["nama_kategori"]);

    if ($nama_kategori != "") {

        $nama_kategori =
            mysqli_real_escape_string(
                $conn,
                $nama_kategori
            );

        $cek = mysqli_query(
            $conn,
            "SELECT id
             FROM kategori_buku
             WHERE nama_kategori = '$nama_kategori'"
        );

        if (mysqli_num_rows($cek) > 0) {

            $pesan = "Kategori buku sudah ada.";

        } else {

            $simpan = mysqli_query(
                $conn,
                "INSERT INTO kategori_buku
                (nama_kategori)
                VALUES
                ('$nama_kategori')"
            );

            if ($simpan) {

                $pesan = "Kategori buku berhasil ditambahkan.";

            } else {

                $pesan = "Kategori buku gagal ditambahkan.";

            }

        }

    } else {

        $pesan = "Nama kategori tidak boleh kosong.";

    }

}


/* ==============================
   HAPUS KATEGORI
============================== */

if (isset($_GET["hapus"])) {

    $id = (int) $_GET["hapus"];

    mysqli_query(
        $conn,
        "DELETE FROM kategori_buku
         WHERE id = $id"
    );

    header(
        "Location: kategori_buku.php"
    );

    exit;
}


/* ==============================
   AMBIL DATA KATEGORI
============================== */

$query = mysqli_query(
    $conn,
    "SELECT *
     FROM kategori_buku
     ORDER BY nama_kategori ASC"
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
    Kategori Buku - SMAN 1 Maligano
</title>


<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f1f5f9;
}

.container {
    max-width: 1000px;
    margin: 40px auto;
    background: white;
    padding: 30px;
    border-radius: 10px;
}

h1 {
    margin-top: 0;
}

.form-tambah {
    display: flex;
    gap: 10px;
    margin-bottom: 25px;
}

.form-tambah input {
    flex: 1;
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 6px;
}

button {
    padding: 12px 20px;
    border: none;
    background: #2563eb;
    color: white;
    border-radius: 6px;
    cursor: pointer;
}

button:hover {
    background: #1d4ed8;
}

.pesan {
    padding: 12px;
    background: #dcfce7;
    color: #166534;
    border-radius: 6px;
    margin-bottom: 20px;
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

.btn-hapus {
    background: #dc2626;
    color: white;
    padding: 8px 12px;
    text-decoration: none;
    border-radius: 5px;
}

.btn-kembali {
    display: inline-block;
    margin-top: 25px;
    padding: 10px 15px;
    background: #64748b;
    color: white;
    text-decoration: none;
    border-radius: 6px;
}

.kosong {
    text-align: center;
    color: #64748b;
}

</style>

</head>

<body>


<div class="container">

    <h1>📚 Kategori Buku</h1>

    <p>
        Kelola kategori buku perpustakaan.
    </p>


    <?php if ($pesan != "") { ?>

        <div class="pesan">

            <?php
            echo htmlspecialchars($pesan);
            ?>

        </div>

    <?php } ?>


    <form
        method="POST"
        class="form-tambah"
    >

        <input
            type="text"
            name="nama_kategori"
            placeholder="Contoh: Matematika"
            required
        >

        <button
            type="submit"
            name="tambah"
        >
            + Tambah Kategori
        </button>

    </form>


    <table>

        <tr>

            <th>No</th>

            <th>Nama Kategori</th>

            <th>Aksi</th>

        </tr>


        <?php

        $no = 1;

        if (mysqli_num_rows($query) > 0) {

            while (
                $data =
                mysqli_fetch_assoc($query)
            ) {

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
                    $data["nama_kategori"]
                );

                ?>

            </td>


            <td>

                <a
                    href="kategori_buku.php?hapus=<?php echo $data["id"]; ?>"
                    class="btn-hapus"
                    onclick="return confirm('Yakin ingin menghapus kategori ini?')"
                >

                    Hapus

                </a>

            </td>

        </tr>


        <?php

            }

        } else {

        ?>

        <tr>

            <td
                colspan="3"
                class="kosong"
            >

                Belum ada kategori buku.

            </td>

        </tr>

        <?php } ?>


    </table>


    <a
        href="dashboard.php"
        class="btn-kembali"
    >

        ← Kembali ke Dashboard

    </a>


</div>


</body>

</html>