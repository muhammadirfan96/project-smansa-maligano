```php
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/koneksi.php";

/* Ambil semua kelas */

$query_kelas = mysqli_query(
    $conn,
    "SELECT * FROM kelas
     ORDER BY nama_kelas ASC"
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

    <title>Download QR Code - SMAN 1 Maligano</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            padding: 40px;
        }

        .container {
            max-width: 500px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
        }

        h1 {
            margin-top: 0;
            text-align: center;
        }

        p {
            text-align: center;
            color: #555;
        }

        label {
            display: block;
            margin-top: 20px;
            margin-bottom: 8px;
            font-weight: bold;
        }

        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            margin-top: 20px;
            padding: 12px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .btn-kembali {
            display: block;
            text-align: center;
            margin-top: 20px;
            text-decoration: none;
        }

    </style>

</head>

<body>

<div class="container">

    <h1>Download QR Code</h1>

    <p>
        Pilih kelas untuk mendownload
        QR Code seluruh siswa.
    </p>

    <form
    action="download_qr_jpeg.php"
    method="GET"
>
        <label>Pilih Kelas</label>

        <select
            name="id_kelas"
            required
        >

            <option value="">
                -- Pilih Kelas --
            </option>

            <?php while ($kelas = mysqli_fetch_assoc($query_kelas)) { ?>

                <option
                    value="<?php echo $kelas["id"]; ?>"
                >

                    <?php
                    echo htmlspecialchars(
                        $kelas["nama_kelas"]
                    );
                    ?>

                </option>

            <?php } ?>

        </select>

        <button type="submit">
            ⬇ Download QR Code JPEG
        </button>

    </form>

    <a
        href="siswa.php"
        class="btn-kembali"
    >
        ← Kembali ke Data Siswa
    </a>

</div>

</body>
</html>
```
