<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/koneksi.php";

if (isset($_GET["id"])) {

    $id = (int) $_GET["id"];

    /* Periksa apakah wali kelas masih digunakan */
    $cek = mysqli_query(
        $conn,
        "SELECT * FROM kelas WHERE id_wali_kelas = $id"
    );

    if (mysqli_num_rows($cek) > 0) {

        echo "
        <script>
            alert('Wali kelas tidak dapat dihapus karena masih digunakan oleh kelas.');
            window.location='wali_kelas.php';
        </script>
        ";

        exit;

    } else {

        mysqli_query(
            $conn,
            "DELETE FROM wali_kelas WHERE id = $id"
        );

        header("Location: wali_kelas.php");
        exit;
    }
}
?>