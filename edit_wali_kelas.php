<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/koneksi.php";


/* =========================================
   CEK ID
========================================= */

if (!isset($_GET["id"])) {
    header("Location: wali_kelas.php");
    exit;
}


$id = (int) $_GET["id"];


/* =========================================
   AMBIL DATA WALI KELAS
========================================= */

$query = mysqli_query(
    $conn,
    "SELECT * FROM wali_kelas WHERE id = $id"
);


$data = mysqli_fetch_assoc($query);


/* =========================================
   JIKA DATA TIDAK DITEMUKAN
========================================= */

if (!$data) {
    echo "Data wali kelas tidak ditemukan.";
    exit;
}


/* =========================================
   PROSES UPDATE
========================================= */

$pesan = "";


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


    $update = mysqli_query(
        $conn,
        "
        UPDATE wali_kelas
        SET

            nama = '$nama',

            nip = '$nip',

            no_hp = '$no_hp',

            email = '$email'

        WHERE id = $id
        "
    );


    if ($update) {

        header("Location: wali_kelas.php");

        exit;

    } else {

        $pesan = "Data gagal diperbarui: " . mysqli_error($conn);

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
    Edit Wali Kelas - SMAN 1 Maligano
</title>


<style>

* {
    box-sizing: border-box;
}


body {

    font-family: Arial, sans-serif;

    margin: 0;

    background: #f1f5f9;

}


.container {

    max-width: 700px;

    margin: 40px auto;

    background: white;

    padding: 30px;

    border-radius: 10px;

    box-shadow:
        0 4px 15px rgba(0,0,0,0.08);

}


h1 {

    margin-top: 0;

    color: #1e3a8a;

}


.form-group {

    margin-bottom: 18px;

}


label {

    display: block;

    margin-bottom: 7px;

    font-weight: bold;

    color: #334155;

}


input {

    width: 100%;

    padding: 12px;

    border: 1px solid #cbd5e1;

    border-radius: 6px;

    font-size: 15px;

}


input:focus {

    outline: none;

    border-color: #2563eb;

}


.btn {

    display: inline-block;

    padding: 11px 18px;

    border: none;

    border-radius: 6px;

    cursor: pointer;

    font-size: 15px;

    text-decoration: none;

}


.btn-simpan {

    background: #16a34a;

    color: white;

}


.btn-simpan:hover {

    background: #15803d;

}


.btn-kembali {

    background: #64748b;

    color: white;

    margin-left: 8px;

}


.btn-kembali:hover {

    background: #475569;

}


.pesan {

    background: #fee2e2;

    color: #991b1b;

    padding: 12px;

    border-radius: 6px;

    margin-bottom: 20px;

}

</style>

</head>


<body>


<div class="container">


<h1>
    ✏️ Edit Data Wali Kelas
</h1>


<?php if ($pesan != "") { ?>

<div class="pesan">

    <?php echo htmlspecialchars($pesan); ?>

</div>

<?php } ?>


<form method="POST">


<div class="form-group">

<label>
    Nama Wali Kelas
</label>

<input
    type="text"
    name="nama"
    value="<?php echo htmlspecialchars($data["nama"]); ?>"
    required
>

</div>


<div class="form-group">

<label>
    NIP
</label>

<input
    type="text"
    name="nip"
    value="<?php echo htmlspecialchars($data["nip"]); ?>"
>

</div>


<div class="form-group">

<label>
    Nomor HP
</label>

<input
    type="text"
    name="no_hp"
    value="<?php echo htmlspecialchars($data["no_hp"]); ?>"
>

</div>


<div class="form-group">

<label>
    Email
</label>

<input
    type="email"
    name="email"
    value="<?php echo htmlspecialchars($data["email"]); ?>"
>

</div>


<button
    type="submit"
    name="simpan"
    class="btn btn-simpan"
>

💾 Simpan Perubahan

</button>


<a
    href="wali_kelas.php"
    class="btn btn-kembali"
>

← Kembali

</a>


</form>


</div>


</body>

</html>