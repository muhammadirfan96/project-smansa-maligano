<?php

$host = "db";
$user = "root";
$password = "root";
$database = "db_sman1_maligano";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
