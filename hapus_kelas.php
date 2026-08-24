<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/koneksi.php";

if (isset($_GET["id"])) {

    $id = (int) $_GET["id"];

    mysqli_query(
        $conn,
        "DELETE FROM kelas WHERE id = $id"
    );
}

header("Location: kelas.php");
exit;
?>