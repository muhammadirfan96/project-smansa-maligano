<?php

session_start();

require_once "config/koneksi.php";

$error = "";


if (isset($_POST["login"])) {

    $username = mysqli_real_escape_string(
        $conn,
        $_POST["username"]
    );

    $password = $_POST["password"];


    /* =====================================
       CARI USER
    ===================================== */

    $query = mysqli_query(
        $conn,
        "SELECT * FROM users
         WHERE username = '$username'"
    );


    if (mysqli_num_rows($query) == 1) {

        $user = mysqli_fetch_assoc($query);


        /* =====================================
           CEK PASSWORD
        ===================================== */

        if ($password == $user["password"]) {


            /* =====================================
               SIMPAN DATA SESSION
            ===================================== */

            $_SESSION["user_id"] =
                $user["id"];

            $_SESSION["nama"] =
                $user["nama"];

            $_SESSION["username"] =
                $user["username"];

            $_SESSION["role"] =
                $user["role"];


            /* =====================================
               JIKA WALI KELAS
               CARI KELAS BERDASARKAN
               id_wali_kelas
            ===================================== */

            if ($user["role"] == "wali_kelas") {

                $id_wali_kelas =
                    (int) $user["id_wali_kelas"];


                $query_kelas = mysqli_query(
                    $conn,
                    "SELECT *
                     FROM kelas
                     WHERE id_wali_kelas =
                     $id_wali_kelas"
                );


                $kelas = mysqli_fetch_assoc(
                    $query_kelas
                );


                if ($kelas) {

                    $_SESSION["id_kelas"] =
                        $kelas["id"];

                    $_SESSION["nama_kelas"] =
                        $kelas["nama_kelas"];
                }
            }


            /* =====================================
               LOGIN BERHASIL
            ===================================== */

            header(
                "Location: dashboard.php"
            );

            exit;
        } else {

            $error =
                "Password salah!";
        }
    } else {

        $error =
            "Username tidak ditemukan!";
    }
}

?>

<!DOCTYPE html>

<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">

    <title>
        Login - SMAN 1 Maligano
    </title>


    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .login-box {
            width: 380px;
            background: white;
            padding: 35px;
            border-radius: 12px;
            box-shadow:
                0 10px 30px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            font-size: 24px;
            margin-bottom: 5px;
        }

        h2 {
            text-align: center;
            font-size: 16px;
            font-weight: normal;
            color: #666;
            margin-top: 0;
            margin-bottom: 30px;
        }

        label {
            display: block;
            margin-bottom: 6px;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-bottom: 18px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 6px;
            background: #2563eb;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #1d4ed8;
        }

        .error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 6px;
            text-align: center;
        }
    </style>

</head>

<body>

    <div class="login-box">

        <h1>SMAN 1 MALIGANO</h1>

        <h2>
            Sistem Absensi & Perpustakaan
        </h2>


        <?php if ($error != "") { ?>

            <div class="error">

                <?php
                echo $error;
                ?>

            </div>

        <?php } ?>


        <form method="POST">

            <label>
                Username
            </label>

            <input
                type="text"
                name="username"
                required>


            <label>
                Password
            </label>

            <input
                type="password"
                name="password"
                required>


            <button
                type="submit"
                name="login">
                Login
            </button>

        </form>

    </div>

</body>

</html>