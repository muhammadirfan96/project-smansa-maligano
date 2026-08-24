<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/koneksi.php";


/* =========================================
   TAMBAH BUKU
========================================= */

$pesan = "";
$error = "";


if (isset($_POST["tambah"])) {

    $kode_buku   = trim($_POST["kode_buku"] ?? "");
    $judul_buku  = trim($_POST["judul_buku"] ?? "");
    $id_kategori = (int) ($_POST["id_kategori"] ?? 0);
    $penulis     = trim($_POST["penulis"] ?? "");
    $penerbit    = trim($_POST["penerbit"] ?? "");
    $tahun_terbit = trim($_POST["tahun_terbit"] ?? "");
    $stok        = (int) ($_POST["stok"] ?? 0);


    /* ==============================
       VALIDASI
    ============================== */

    if (
        $kode_buku == "" ||
        $judul_buku == "" ||
        $id_kategori == 0
    ) {

        $error = "Kode buku, judul buku, dan kategori wajib diisi.";

    } elseif ($stok < 0) {

        $error = "Stok buku tidak boleh kurang dari 0.";

    } else {

        /* ==============================
           AMANKAN DATA
        ============================== */

        $kode_buku = mysqli_real_escape_string(
            $conn,
            $kode_buku
        );


        $judul_buku = mysqli_real_escape_string(
            $conn,
            $judul_buku
        );


        $penulis = mysqli_real_escape_string(
            $conn,
            $penulis
        );


        $penerbit = mysqli_real_escape_string(
            $conn,
            $penerbit
        );


        /* ==============================
           CEK KODE BUKU
        ============================== */

        $cek = mysqli_query(
            $conn,
            "
            SELECT id
            FROM buku
            WHERE kode_buku = '$kode_buku'
            "
        );


        if (mysqli_num_rows($cek) > 0) {

            $error = "Kode buku tersebut sudah digunakan.";

        } else {

            /* ==============================
               TAHUN TERBIT
            ============================== */

            if ($tahun_terbit == "") {

                $tahun_sql = "NULL";

            } else {

                $tahun_terbit = (int) $tahun_terbit;

                $tahun_sql = $tahun_terbit;

            }


            /* ==============================
               MULAI TRANSAKSI
            ============================== */

            mysqli_begin_transaction($conn);


            try {

                /* ==============================
                   SIMPAN DATA BUKU
                ============================== */

                $simpan = mysqli_query(
                    $conn,
                    "
                    INSERT INTO buku
                    (
                        kode_buku,
                        judul_buku,
                        id_kategori,
                        penulis,
                        penerbit,
                        tahun_terbit,
                        stok
                    )
                    VALUES
                    (
                        '$kode_buku',
                        '$judul_buku',
                        $id_kategori,
                        '$penulis',
                        '$penerbit',
                        $tahun_sql,
                        $stok
                    )
                    "
                );


                if (!$simpan) {

                    throw new Exception(
                        mysqli_error($conn)
                    );

                }


                /* ==============================
                   AMBIL ID BUKU BARU
                ============================== */

                $id_buku_baru = mysqli_insert_id(
                    $conn
                );


                /* ==============================
                   BUAT NOMOR BUKU FISIK
                ============================== */

                for (
                    $i = 1;
                    $i <= $stok;
                    $i++
                ) {

                    $nomor_urut = str_pad(
                        $i,
                        3,
                        "0",
                        STR_PAD_LEFT
                    );


                    $nomor_buku =
                        $kode_buku .
                        "-" .
                        $nomor_urut;


                    $nomor_buku =
                        mysqli_real_escape_string(
                            $conn,
                            $nomor_buku
                        );


                    $simpan_detail =
                        mysqli_query(
                            $conn,
                            "
                            INSERT INTO buku_detail
                            (
                                id_buku,
                                nomor_buku,
                                status
                            )
                            VALUES
                            (
                                $id_buku_baru,
                                '$nomor_buku',
                                'Tersedia'
                            )
                            "
                        );


                    if (!$simpan_detail) {

                        throw new Exception(
                            mysqli_error($conn)
                        );

                    }

                }


                /* ==============================
                   SIMPAN PERMANEN
                ============================== */

                mysqli_commit($conn);


                $pesan =
                    "Buku berhasil ditambahkan beserta "
                    . $stok
                    . " nomor buku fisik.";


            } catch (Exception $e) {

                /* ==============================
                   BATALKAN JIKA ERROR
                ============================== */

                mysqli_rollback($conn);


                $error =
                    "Buku gagal ditambahkan: "
                    . $e->getMessage();

            }

        }

    }

}


/* =========================================
   HAPUS BUKU
========================================= */

if (isset($_GET["hapus"])) {

    $id = (int) $_GET["hapus"];


    mysqli_query(
        $conn,
        "
        DELETE FROM buku
        WHERE id = $id
        "
    );


    header("Location: buku.php");
    exit;

}


/* =========================================
   AMBIL KATEGORI
========================================= */

$query_kategori = mysqli_query(
    $conn,
    "
    SELECT *
    FROM kategori_buku
    ORDER BY nama_kategori ASC
    "
);


/* =========================================
   AMBIL DATA BUKU
========================================= */

$query_buku = mysqli_query(
    $conn,
    "
    SELECT
        buku.*,
        kategori_buku.nama_kategori

    FROM buku

    LEFT JOIN kategori_buku
        ON buku.id_kategori = kategori_buku.id

    ORDER BY buku.judul_buku ASC
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
    Data Buku - SMAN 1 Maligano
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

    max-width: 1200px;

    margin: 30px auto;

    background: white;

    padding: 30px;

    border-radius: 10px;

}


h1 {

    margin-top: 0;

}


.form-buku {

    background: #f8fafc;

    padding: 20px;

    border-radius: 8px;

    margin-bottom: 30px;

}


.form-grid {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 15px;

}


.form-group {

    display: flex;

    flex-direction: column;

}


.form-group.full {

    grid-column: 1 / -1;

}


label {

    font-weight: bold;

    margin-bottom: 6px;

}


input,
select {

    padding: 11px;

    border: 1px solid #cbd5e1;

    border-radius: 6px;

    font-size: 14px;

}


button {

    margin-top: 20px;

    padding: 12px 18px;

    border: none;

    border-radius: 6px;

    background: #2563eb;

    color: white;

    cursor: pointer;

    font-size: 15px;

}


button:hover {

    background: #1d4ed8;

}


.pesan {

    background: #dcfce7;

    color: #166534;

    padding: 12px;

    border-radius: 6px;

    margin-bottom: 20px;

}


.error {

    background: #fee2e2;

    color: #991b1b;

    padding: 12px;

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

    padding: 10px;

    text-align: left;

}


th {

    background: #2563eb;

    color: white;

}


.btn-hapus {

    display: inline-block;

    padding: 7px 10px;

    background: #dc2626;

    color: white;

    text-decoration: none;

    border-radius: 5px;

}


.btn-edit {

    display: inline-block;

    padding: 7px 10px;

    background: #f59e0b;

    color: white;

    text-decoration: none;

    border-radius: 5px;

    margin-right: 4px;

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

    padding: 20px;

}


@media (max-width: 700px) {

    .form-grid {

        grid-template-columns: 1fr;

    }


    .form-group.full {

        grid-column: auto;

    }


    .container {

        margin: 10px;

        padding: 20px;

    }

}

</style>

</head>


<body>


<div class="container">


<h1>
    📚 Data Buku
</h1>


<p>
    Kelola data buku perpustakaan SMAN 1 Maligano.
</p>


<?php if ($pesan != "") { ?>

<div class="pesan">

    <?php
    echo htmlspecialchars($pesan);
    ?>

</div>

<?php } ?>


<?php if ($error != "") { ?>

<div class="error">

    <?php
    echo htmlspecialchars($error);
    ?>

</div>

<?php } ?>


<!-- =====================================
     FORM TAMBAH BUKU
===================================== -->

<div class="form-buku">


<h2>
    ➕ Tambah Buku
</h2>


<form method="POST">


<div class="form-grid">


<div class="form-group">

<label>
    Kode Buku
</label>


<input
    type="text"
    name="kode_buku"
    placeholder="Contoh: BK001"
    required
>

</div>


<div class="form-group">

<label>
    Judul Buku
</label>


<input
    type="text"
    name="judul_buku"
    placeholder="Masukkan judul buku"
    required
>

</div>


<div class="form-group">

<label>
    Kategori
</label>


<select
    name="id_kategori"
    required
>

<option value="">
    -- Pilih Kategori --
</option>


<?php

while (
    $kategori =
    mysqli_fetch_assoc($query_kategori)
) {

?>

<option
    value="<?php
    echo $kategori["id"];
    ?>"
>

<?php

echo htmlspecialchars(
    $kategori["nama_kategori"]
);

?>

</option>


<?php } ?>


</select>

</div>


<div class="form-group">

<label>
    Penulis
</label>


<input
    type="text"
    name="penulis"
    placeholder="Nama penulis"
>

</div>


<div class="form-group">

<label>
    Penerbit
</label>


<input
    type="text"
    name="penerbit"
    placeholder="Nama penerbit"
>

</div>


<div class="form-group">

<label>
    Tahun Terbit
</label>


<input
    type="number"
    name="tahun_terbit"
    min="1900"
    max="<?php echo date("Y"); ?>"
    placeholder="Contoh: 2024"
>

</div>


<div class="form-group">

<label>
    Stok
</label>


<input
    type="number"
    name="stok"
    min="0"
    value="1"
    required
>

</div>


</div>


<button
    type="submit"
    name="tambah"
>

    💾 Simpan Buku

</button>


</form>


</div>


<!-- =====================================
     TABEL DATA BUKU
===================================== -->


<h2>
    📖 Daftar Buku
</h2>


<table>


<tr>

<th>No</th>

<th>Kode</th>

<th>Judul Buku</th>

<th>Kategori</th>

<th>Penulis</th>

<th>Penerbit</th>

<th>Tahun</th>

<th>Stok</th>

<th>Aksi</th>

</tr>


<?php

$no = 1;


if (
    mysqli_num_rows($query_buku) > 0
) {

    while (
        $buku =
        mysqli_fetch_assoc($query_buku)
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
    $buku["kode_buku"]
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $buku["judul_buku"]
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $buku["nama_kategori"]
    ?? "-"
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $buku["penulis"]
    ?? "-"
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $buku["penerbit"]
    ?? "-"
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $buku["tahun_terbit"]
    ?? "-"
);

?>

</td>


<td>

<?php

echo (int) $buku["stok"];

?>

</td>


<td>


<a
    href="edit_buku.php?id=<?php echo $buku["id"]; ?>"
    class="btn-edit"
>

    Edit

</a>


<a
    href="buku.php?hapus=<?php echo $buku["id"]; ?>"
    class="btn-hapus"
    onclick="return confirm('Yakin ingin menghapus buku ini?')"
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
    colspan="9"
    class="kosong"
>

    Belum ada data buku.

</td>

</tr>


<?php

}

?>


</table>


<a
    href="kategori_buku.php"
    class="btn-kembali"
>

    ← Kategori Buku

</a>


<a
    href="dashboard.php"
    class="btn-kembali"
>

    ← Dashboard

</a>


</div>


</body>

</html>