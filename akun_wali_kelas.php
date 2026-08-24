<?php

session_start();


/* =========================================
   CEK LOGIN
========================================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}


/* =========================================
   HANYA ADMIN
========================================= */

if (($_SESSION["role"] ?? "") != "admin") {

    echo "
        <h2>Akses Ditolak</h2>
        <p>Halaman ini hanya dapat diakses oleh Administrator.</p>
        <a href='dashboard.php'>Kembali ke Dashboard</a>
    ";

    exit;
}


require_once "config/koneksi.php";


/* =========================================
   PESAN
========================================= */

$pesan = "";


/* =========================================
   HAPUS AKUN
========================================= */

if (isset($_GET["hapus"])) {

    $id_hapus = (int) $_GET["hapus"];


    if ($id_hapus > 0) {

        $hapus = mysqli_query(
            $conn,
            "
            DELETE FROM users
            WHERE id = $id_hapus
            AND role = 'wali_kelas'
            "
        );


        if ($hapus) {

            header(
                "Location: akun_wali_kelas.php?pesan=hapus"
            );

            exit;

        }

    }

}


/* =========================================
   PROSES TAMBAH AKUN
========================================= */

if (isset($_POST["tambah"])) {


    $id_wali_kelas = (int)
        ($_POST["id_wali_kelas"] ?? 0);


    $username = trim(
        $_POST["username"] ?? ""
    );


    $password = trim(
        $_POST["password"] ?? ""
    );


    if (
        $id_wali_kelas == 0 ||
        $username == "" ||
        $password == ""
    ) {

        $pesan = "Semua data wajib diisi.";

    } else {


        $username_escape =
            mysqli_real_escape_string(
                $conn,
                $username
            );


        $password_escape =
            mysqli_real_escape_string(
                $conn,
                $password
            );


        /* CEK USERNAME */

        $cek_username = mysqli_query(
            $conn,
            "
            SELECT id
            FROM users
            WHERE username = '$username_escape'
            "
        );


        if (mysqli_num_rows($cek_username) > 0) {

            $pesan =
                "Username sudah digunakan.";

        } else {


            /* CEK WALI KELAS SUDAH MEMILIKI AKUN */

            $cek_wali = mysqli_query(
                $conn,
                "
                SELECT id
                FROM users
                WHERE id_wali_kelas = $id_wali_kelas
                AND role = 'wali_kelas'
                "
            );


            if (
                mysqli_num_rows($cek_wali) > 0
            ) {

                $pesan =
                    "Wali kelas ini sudah memiliki akun login.";

            } else {


                /* AMBIL NAMA WALI KELAS */

                $query_wali = mysqli_query(
                    $conn,
                    "
                    SELECT nama
                    FROM wali_kelas
                    WHERE id = $id_wali_kelas
                    "
                );


                $data_wali =
                    mysqli_fetch_assoc(
                        $query_wali
                    );


                if (!$data_wali) {

                    $pesan =
                        "Data wali kelas tidak ditemukan.";

                } else {


                    $nama =
                        mysqli_real_escape_string(
                            $conn,
                            $data_wali["nama"]
                        );


                    /* SIMPAN AKUN */

                    $simpan = mysqli_query(
                        $conn,
                        "
                        INSERT INTO users
                        (
                            nama,
                            username,
                            password,
                            role,
                            id_wali_kelas
                        )

                        VALUES
                        (
                            '$nama',
                            '$username_escape',
                            '$password_escape',
                            'wali_kelas',
                            $id_wali_kelas
                        )
                        "
                    );


                    if ($simpan) {

                        header(
                            "Location: akun_wali_kelas.php?pesan=tambah"
                        );

                        exit;

                    } else {

                        $pesan =
                            "Gagal membuat akun: "
                            . mysqli_error($conn);

                    }

                }

            }

        }

    }

}


/* =========================================
   PROSES EDIT AKUN
========================================= */

if (isset($_POST["simpan_edit"])) {


    $id_user = (int)
        ($_POST["id_user"] ?? 0);


    $username = trim(
        $_POST["username"] ?? ""
    );


    $password = trim(
        $_POST["password"] ?? ""
    );


    if (
        $id_user == 0 ||
        $username == ""
    ) {

        $pesan =
            "Username wajib diisi.";

    } else {


        $username_escape =
            mysqli_real_escape_string(
                $conn,
                $username
            );


        /* CEK USERNAME LAIN */

        $cek_username = mysqli_query(
            $conn,
            "
            SELECT id
            FROM users
            WHERE username = '$username_escape'
            AND id != $id_user
            "
        );


        if (
            mysqli_num_rows(
                $cek_username
            ) > 0
        ) {

            $pesan =
                "Username sudah digunakan akun lain.";

        } else {


            /* JIKA PASSWORD DIISI */

            if ($password != "") {


                $password_escape =
                    mysqli_real_escape_string(
                        $conn,
                        $password
                    );


                $update = mysqli_query(
                    $conn,
                    "
                    UPDATE users

                    SET
                        username = '$username_escape',
                        password = '$password_escape'

                    WHERE id = $id_user
                    AND role = 'wali_kelas'
                    "
                );

            } else {


                $update = mysqli_query(
                    $conn,
                    "
                    UPDATE users

                    SET
                        username = '$username_escape'

                    WHERE id = $id_user
                    AND role = 'wali_kelas'
                    "
                );

            }


            if ($update) {

                header(
                    "Location: akun_wali_kelas.php?pesan=edit"
                );

                exit;

            } else {

                $pesan =
                    "Data gagal diperbarui: "
                    . mysqli_error($conn);

            }

        }

    }

}


/* =========================================
   DATA UNTUK EDIT
========================================= */

$data_edit = null;


if (isset($_GET["edit"])) {


    $id_edit =
        (int) $_GET["edit"];


    $query_edit =
        mysqli_query(
            $conn,
            "
            SELECT *
            FROM users

            WHERE id = $id_edit
            AND role = 'wali_kelas'
            "
        );


    $data_edit =
        mysqli_fetch_assoc(
            $query_edit
        );

}


/* =========================================
   AMBIL DATA WALI KELAS
========================================= */

$query_wali_kelas =
    mysqli_query(
        $conn,
        "
        SELECT *
        FROM wali_kelas
        ORDER BY nama ASC
        "
    );


/* =========================================
   AMBIL DATA AKUN
========================================= */

$query_users =
    mysqli_query(
        $conn,
        "
        SELECT

            users.id,
            users.nama,
            users.username,
            users.role,
            users.id_wali_kelas,
            users.created_at,

            wali_kelas.nama AS nama_wali_kelas

        FROM users

        LEFT JOIN wali_kelas
            ON users.id_wali_kelas = wali_kelas.id

        WHERE users.role = 'wali_kelas'

        ORDER BY users.nama ASC
        "
    );


/* =========================================
   PESAN SUKSES
========================================= */

$pesan_sukses = "";


if (
    isset($_GET["pesan"])
) {


    if ($_GET["pesan"] == "tambah") {

        $pesan_sukses =
            "Akun wali kelas berhasil dibuat.";

    }


    if ($_GET["pesan"] == "edit") {

        $pesan_sukses =
            "Akun wali kelas berhasil diperbarui.";

    }


    if ($_GET["pesan"] == "hapus") {

        $pesan_sukses =
            "Akun wali kelas berhasil dihapus.";

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
    Akun Wali Kelas
</title>


<style>


* {
    box-sizing: border-box;
}


body {

    margin: 0;

    font-family:
        Arial,
        sans-serif;

    background:
        #f1f5f9;

}


.header {

    background:
        #1e3a8a;

    color:
        white;

    padding:
        20px 40px;

}


.header h1 {

    margin: 0;

}


.container {

    max-width:
        1200px;

    margin:
        30px auto;

    padding:
        0 20px;

}


.box {

    background:
        white;

    padding:
        25px;

    border-radius:
        12px;

    margin-bottom:
        25px;

    box-shadow:
        0 4px 15px
        rgba(
            0,
            0,
            0,
            0.06
        );

}


h2 {

    margin-top: 0;

    color:
        #1e293b;

}


.form-grid {

    display:
        grid;

    grid-template-columns:
        repeat(
            auto-fit,
            minmax(
                220px,
                1fr
            )
        );

    gap:
        15px;

}


.form-group {

    display:
        flex;

    flex-direction:
        column;

}


label {

    margin-bottom:
        7px;

    font-weight:
        bold;

    color:
        #334155;

}


input,
select {

    width:
        100%;

    padding:
        11px;

    border:
        1px solid
        #cbd5e1;

    border-radius:
        6px;

    font-size:
        15px;

}


input:focus,
select:focus {

    outline:
        none;

    border-color:
        #2563eb;

}


.btn {

    display:
        inline-block;

    padding:
        11px 18px;

    border:
        none;

    border-radius:
        6px;

    text-decoration:
        none;

    cursor:
        pointer;

    font-size:
        15px;

}


.btn-tambah {

    background:
        #16a34a;

    color:
        white;

}


.btn-edit {

    background:
        #f59e0b;

    color:
        white;

    padding:
        8px 12px;

}


.btn-hapus {

    background:
        #dc2626;

    color:
        white;

    padding:
        8px 12px;

}


.btn-dashboard {

    background:
        #2563eb;

    color:
        white;

}


.pesan-sukses {

    background:
        #dcfce7;

    color:
        #166534;

    padding:
        15px;

    border-radius:
        8px;

    margin-bottom:
        20px;

}


.pesan-error {

    background:
        #fee2e2;

    color:
        #991b1b;

    padding:
        15px;

    border-radius:
        8px;

    margin-bottom:
        20px;

}


table {

    width:
        100%;

    border-collapse:
        collapse;

}


th,
td {

    padding:
        12px;

    border:
        1px solid
        #e2e8f0;

    text-align:
        left;

}


th {

    background:
        #2563eb;

    color:
        white;

}


.role-badge {

    display:
        inline-block;

    background:
        #dbeafe;

    color:
        #1e40af;

    padding:
        5px 10px;

    border-radius:
        20px;

    font-size:
        12px;

}


.info-password {

    color:
        #64748b;

    font-size:
        13px;

    margin-top:
        5px;

}


@media
(max-width: 700px) {


    .header {

        padding:
            20px;

    }


    .container {

        padding:
            0 10px;

    }


    .box {

        padding:
            18px;

        overflow-x:
            auto;

    }


    table {

        min-width:
            700px;

    }

}


</style>

</head>


<body>


<div class="header">

    <h1>
        🔐 Manajemen Akun Wali Kelas
    </h1>

    <div>
        SMAN 1 Maligano
    </div>

</div>


<div class="container">


<?php if ($pesan_sukses != "") { ?>


<div class="pesan-sukses">

    <?php
    echo htmlspecialchars(
        $pesan_sukses
    );
    ?>

</div>


<?php } ?>


<?php if ($pesan != "") { ?>


<div class="pesan-error">

    <?php
    echo htmlspecialchars(
        $pesan
    );
    ?>

</div>


<?php } ?>


<!-- =========================================
     FORM TAMBAH
========================================= -->

<?php if ($data_edit == null) { ?>


<div class="box">


<h2>
    ➕ Buat Akun Wali Kelas
</h2>


<form method="POST">


<div class="form-grid">


<div class="form-group">

<label>
    Pilih Wali Kelas
</label>


<select
    name="id_wali_kelas"
    required
>


<option value="">
    -- Pilih Wali Kelas --
</option>


<?php

while (
    $wali =
    mysqli_fetch_assoc(
        $query_wali_kelas
    )
) {

?>


<option
    value="<?php
        echo $wali["id"];
    ?>"
>

<?php

echo htmlspecialchars(
    $wali["nama"]
);

?>

</option>


<?php } ?>


</select>


</div>


<div class="form-group">

<label>
    Username
</label>


<input
    type="text"
    name="username"
    required
    autocomplete="off"
>


</div>


<div class="form-group">

<label>
    Password
</label>


<input
    type="text"
    name="password"
    required
    autocomplete="new-password"
>


</div>


</div>


<br>


<button
    type="submit"
    name="tambah"
    class="btn btn-tambah"
>

➕ Buat Akun

</button>


<a
    href="dashboard.php"
    class="btn btn-dashboard"
>

← Dashboard

</a>


</form>


</div>


<?php } ?>


<!-- =========================================
     FORM EDIT
========================================= -->

<?php if ($data_edit != null) { ?>


<div class="box">


<h2>
    ✏️ Edit Akun Wali Kelas
</h2>


<p>

<strong>
Nama:
</strong>

<?php

echo htmlspecialchars(
    $data_edit["nama"]
);

?>

</p>


<form method="POST">


<input
    type="hidden"
    name="id_user"
    value="<?php
        echo $data_edit["id"];
    ?>"
>


<div class="form-grid">


<div class="form-group">

<label>
    Username
</label>


<input
    type="text"
    name="username"
    value="<?php

        echo htmlspecialchars(
            $data_edit["username"]
        );

    ?>"
    required
>


</div>


<div class="form-group">

<label>
    Password Baru
</label>


<input
    type="text"
    name="password"
    placeholder="Kosongkan jika tidak ingin mengganti password"
>


<div class="info-password">

Kosongkan password jika password lama
tetap digunakan.

</div>


</div>


</div>


<br>


<button
    type="submit"
    name="simpan_edit"
    class="btn btn-tambah"
>

💾 Simpan Perubahan

</button>


<a
    href="akun_wali_kelas.php"
    class="btn btn-dashboard"
>

← Batal

</a>


</form>


</div>


<?php } ?>


<!-- =========================================
     DATA AKUN
========================================= -->

<div class="box">


<h2>
    👨‍🏫 Daftar Akun Wali Kelas
</h2>


<div style="overflow-x:auto;">


<table>


<tr>

<th>
No
</th>

<th>
Nama
</th>

<th>
Username
</th>

<th>
Role
</th>

<th>
Wali Kelas
</th>

<th>
Dibuat
</th>

<th>
Aksi
</th>

</tr>


<?php


$no = 1;


while (
    $user =
    mysqli_fetch_assoc(
        $query_users
    )
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
    $user["nama"]
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $user["username"]
);

?>

</td>


<td>

<span class="role-badge">

<?php

echo htmlspecialchars(
    $user["role"]
);

?>

</span>

</td>


<td>

<?php

echo htmlspecialchars(
    $user["nama_wali_kelas"]
    ?? "-"
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $user["created_at"]
);

?>

</td>


<td>


<a
    href="akun_wali_kelas.php?edit=<?php
        echo $user["id"];
    ?>"
    class="btn btn-edit"
>

✏️ Edit

</a>


<a
    href="akun_wali_kelas.php?hapus=<?php
        echo $user["id"];
    ?>"
    class="btn btn-hapus"
    onclick="
        return confirm(
            'Yakin ingin menghapus akun ini?'
        );
    "
>

🗑️ Hapus

</a>


</td>


</tr>


<?php } ?>


</table>


</div>


<br>


<a
    href="dashboard.php"
    class="btn btn-dashboard"
>

← Kembali ke Dashboard

</a>


</div>


</div>


</body>

</html>