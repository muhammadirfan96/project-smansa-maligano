<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/koneksi.php";


/* =========================================
   AMBIL ID BUKU
========================================= */

$id = (int) ($_GET["id"] ?? 0);

if ($id <= 0) {

    header("Location: buku.php");
    exit;
}


/* =========================================
   AMBIL DATA BUKU
========================================= */

$query_buku = mysqli_query(
    $conn,
    "
    SELECT *
    FROM buku
    WHERE id = $id
    "
);


$buku = mysqli_fetch_assoc($query_buku);


if (!$buku) {

    echo "Data buku tidak ditemukan.";
    exit;
}


/* =========================================
   PROSES UPDATE
========================================= */

$pesan = "";
$error = "";


if (isset($_POST["update"])) {

    $kode_buku   = trim($_POST["kode_buku"] ?? "");
    $judul_buku  = trim($_POST["judul_buku"] ?? "");
    $id_kategori = (int) ($_POST["id_kategori"] ?? 0);
    $penulis     = trim($_POST["penulis"] ?? "");
    $penerbit    = trim($_POST["penerbit"] ?? "");
    $tahun_terbit = trim($_POST["tahun_terbit"] ?? "");
    $stok        = (int) ($_POST["stok"] ?? 0);


    /* =========================================
       VALIDASI
    ========================================= */

    if (
        $kode_buku == "" ||
        $judul_buku == "" ||
        $id_kategori == 0
    ) {

        $error =
            "Kode buku, judul buku, dan kategori wajib diisi.";

    }

    elseif ($stok < 0) {

        $error =
            "Stok buku tidak boleh kurang dari 0.";

    }

    else {


        /* =========================================
           ESCAPE DATA
        ========================================= */

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


        /* =========================================
           CEK KODE BUKU
        ========================================= */

        $cek = mysqli_query(
            $conn,
            "
            SELECT id
            FROM buku
            WHERE kode_buku = '$kode_buku'
            AND id != $id
            "
        );


        if (mysqli_num_rows($cek) > 0) {

            $error =
                "Kode buku tersebut sudah digunakan oleh buku lain.";

        }

        else {


            /* =========================================
               TAHUN TERBIT
            ========================================= */

            if ($tahun_terbit == "") {

                $tahun_sql = "NULL";

            } else {

                $tahun_sql = (int) $tahun_terbit;

            }


            /* =========================================
               UPDATE
            ========================================= */

            $update = mysqli_query(
                $conn,
                "
                UPDATE buku

                SET

                    kode_buku = '$kode_buku',

                    judul_buku = '$judul_buku',

                    id_kategori = $id_kategori,

                    penulis = '$penulis',

                    penerbit = '$penerbit',

                    tahun_terbit = $tahun_sql,

                    stok = $stok

                WHERE id = $id
                "
            );


            if ($update) {

                header("Location: buku.php");
                exit;

            } else {

                $error =
                    "Data buku gagal diperbarui: "
                    . mysqli_error($conn);

            }

        }

    }

}

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
    Edit Buku - SMAN 1 Maligano
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

    max-width: 700px;

    margin: 40px auto;

    background: white;

    padding: 30px;

    border-radius: 10px;

}

h1 {
    margin-top: 0;
}

.form-group {

    margin-bottom: 15px;

}

label {

    display: block;

    font-weight: bold;

    margin-bottom: 6px;

}

input,
select {

    width: 100%;

    padding: 12px;

    border: 1px solid #cbd5e1;

    border-radius: 6px;

    font-size: 15px;

}

button {

    width: 100%;

    margin-top: 10px;

    padding: 12px;

    background: #2563eb;

    color: white;

    border: none;

    border-radius: 6px;

    cursor: pointer;

    font-size: 16px;

}

button:hover {

    background: #1d4ed8;

}

.error {

    background: #fee2e2;

    color: #991b1b;

    padding: 12px;

    border-radius: 6px;

    margin-bottom: 20px;

}

.btn-kembali {

    display: block;

    text-align: center;

    margin-top: 20px;

    padding: 10px;

    background: #64748b;

    color: white;

    text-decoration: none;

    border-radius: 6px;

}

</style>

</head>


<body>


<div class="container">


<h1>
    ✏️ Edit Buku
</h1>


<?php if ($error != "") { ?>

<div class="error">

<?php

echo htmlspecialchars($error);

?>

</div>

<?php } ?>


<form method="POST">


<div class="form-group">

<label>
    Kode Buku
</label>

<input
    type="text"
    name="kode_buku"
    value="<?php
        echo htmlspecialchars(
            $_POST["kode_buku"]
            ?? $buku["kode_buku"]
        );
    ?>"
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
    value="<?php
        echo htmlspecialchars(
            $_POST["judul_buku"]
            ?? $buku["judul_buku"]
        );
    ?>"
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

$query_kategori = mysqli_query(
    $conn,
    "
    SELECT *
    FROM kategori_buku
    ORDER BY nama_kategori ASC
    "
);


$kategori_terpilih =
    $_POST["id_kategori"]
    ?? $buku["id_kategori"];


while (
    $kategori =
    mysqli_fetch_assoc($query_kategori)
) {

?>


<option
    value="<?php
        echo $kategori["id"];
    ?>"

    <?php

    if (
        $kategori_terpilih ==
        $kategori["id"]
    ) {

        echo "selected";

    }

    ?>

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
    value="<?php
        echo htmlspecialchars(
            $_POST["penulis"]
            ?? $buku["penulis"]
            ?? ""
        );
    ?>"
>

</div>


<div class="form-group">

<label>
    Penerbit
</label>

<input
    type="text"
    name="penerbit"
    value="<?php
        echo htmlspecialchars(
            $_POST["penerbit"]
            ?? $buku["penerbit"]
            ?? ""
        );
    ?>"
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
    value="<?php
        echo htmlspecialchars(
            $_POST["tahun_terbit"]
            ?? $buku["tahun_terbit"]
            ?? ""
        );
    ?>"
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
    value="<?php
        echo htmlspecialchars(
            $_POST["stok"]
            ?? $buku["stok"]
        );
    ?>"
    required
>

</div>


<button
    type="submit"
    name="update"
>

    💾 Simpan Perubahan

</button>


</form>


<a
    href="buku.php"
    class="btn-kembali"
>

    ← Kembali ke Data Buku

</a>


</div>


</body>

</html>