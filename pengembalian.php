<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/koneksi.php";


$pesan = "";
$error = "";
$data_buku = null;


/* =====================================================
   KONFIRMASI PENGEMBALIAN SATU BUKU
===================================================== */

if (isset($_POST["konfirmasi_pengembalian"])) {

    $id_peminjaman =
        (int) ($_POST["id_peminjaman"] ?? 0);

    $id_buku_detail =
        (int) ($_POST["id_buku_detail"] ?? 0);


    if (
        $id_peminjaman <= 0 ||
        $id_buku_detail <= 0
    ) {

        $error =
            "Data pengembalian tidak valid.";

    } else {

        mysqli_begin_transaction($conn);

        try {

            $query_cek =
                mysqli_query(
                    $conn,
                    "SELECT
                        peminjaman.id,
                        peminjaman.status,
                        peminjaman.id_buku_detail,

                        buku.judul_buku,

                        buku_detail.nomor_buku

                    FROM peminjaman

                    JOIN buku
                        ON peminjaman.id_buku =
                        buku.id

                    JOIN buku_detail
                        ON peminjaman.id_buku_detail =
                        buku_detail.id

                    WHERE peminjaman.id =
                    $id_peminjaman

                    FOR UPDATE"
                );


            $peminjaman =
                mysqli_fetch_assoc($query_cek);


            if (!$peminjaman) {

                throw new Exception(
                    "Data peminjaman tidak ditemukan."
                );

            }


            if (
                $peminjaman["status"] != "Dipinjam"
            ) {

                throw new Exception(
                    "Buku ini sudah dikembalikan."
                );

            }


            if (
                $peminjaman["id_buku_detail"]
                != $id_buku_detail
            ) {

                throw new Exception(
                    "Data buku tidak sesuai."
                );

            }


            /* UBAH STATUS PEMINJAMAN */

            $update_peminjaman =
                mysqli_query(
                    $conn,
                    "UPDATE peminjaman

                    SET status = 'Dikembalikan'

                    WHERE id = $id_peminjaman

                    AND status = 'Dipinjam'"
                );


            if (
                !$update_peminjaman ||
                mysqli_affected_rows($conn) <= 0
            ) {

                throw new Exception(
                    "Gagal mengubah status peminjaman."
                );

            }


            /* BUKU FISIK TERSEDIA */

            $update_buku =
                mysqli_query(
                    $conn,
                    "UPDATE buku_detail

                    SET status = 'Tersedia'

                    WHERE id = $id_buku_detail"
                );


            if (!$update_buku) {

                throw new Exception(
                    "Gagal mengubah status buku."
                );

            }


            mysqli_commit($conn);


            $pesan =
                "Buku "
                . $peminjaman["judul_buku"]
                . " ("
                . $peminjaman["nomor_buku"]
                . ") berhasil dikembalikan.";


        } catch (Exception $e) {

            mysqli_rollback($conn);

            $error = $e->getMessage();

        }

    }

}


/* =====================================================
   KONFIRMASI PENGEMBALIAN PER KELAS
===================================================== */

if (isset($_POST["konfirmasi_kelas"])) {

    $id_kelas =
        (int) ($_POST["id_kelas"] ?? 0);

    $id_buku =
        (int) ($_POST["id_buku"] ?? 0);

    $peminjaman_terpilih =
        $_POST["peminjaman"] ?? [];


    if ($id_kelas <= 0) {

        $error =
            "Kelas tidak valid.";

    } elseif ($id_buku <= 0) {

        $error =
            "Buku atau mata pelajaran belum dipilih.";

    } elseif (empty($peminjaman_terpilih)) {

        $error =
            "Tidak ada siswa yang dipilih untuk pengembalian.";

    } else {

        $id_peminjaman_valid = [];


        foreach ($peminjaman_terpilih as $id) {

            $id = (int) $id;

            if ($id > 0) {

                $id_peminjaman_valid[] = $id;

            }

        }


        $id_peminjaman_valid =
            array_values(
                array_unique(
                    $id_peminjaman_valid
                )
            );


        if (empty($id_peminjaman_valid)) {

            $error =
                "Data peminjaman tidak valid.";

        } else {

            $daftar_id =
                implode(
                    ",",
                    $id_peminjaman_valid
                );


            mysqli_begin_transaction($conn);


            try {

                /* AMBIL DATA YANG BENAR-BENAR SESUAI
                   DENGAN KELAS DAN BUKU */

                $query_peminjaman =
                    mysqli_query(
                        $conn,
                        "SELECT

                            peminjaman.id,

                            peminjaman.id_buku_detail,

                            buku_detail.nomor_buku

                        FROM peminjaman

                        JOIN siswa
                            ON peminjaman.id_siswa =
                            siswa.id

                        JOIN buku_detail
                            ON peminjaman.id_buku_detail =
                            buku_detail.id

                        WHERE peminjaman.id IN (
                            $daftar_id
                        )

                        AND siswa.id_kelas =
                            $id_kelas

                        AND peminjaman.id_buku =
                            $id_buku

                        AND peminjaman.status =
                            'Dipinjam'

                        FOR UPDATE"
                    );


                if (!$query_peminjaman) {

                    throw new Exception(
                        "Gagal mengambil data peminjaman."
                    );

                }


                $jumlah_dikembalikan = 0;


                while (
                    $data =
                    mysqli_fetch_assoc(
                        $query_peminjaman
                    )
                ) {

                    $id_peminjaman =
                        (int) $data["id"];

                    $id_buku_detail =
                        (int)
                        $data["id_buku_detail"];


                    /* UBAH STATUS PEMINJAMAN */

                    $update_peminjaman =
                        mysqli_query(
                            $conn,
                            "UPDATE peminjaman

                            SET status =
                            'Dikembalikan'

                            WHERE id =
                            $id_peminjaman

                            AND status =
                            'Dipinjam'"
                        );


                    if (
                        !$update_peminjaman ||
                        mysqli_affected_rows($conn) <= 0
                    ) {

                        throw new Exception(
                            "Gagal mengubah status peminjaman."
                        );

                    }


                    /* UBAH STATUS BUKU FISIK */

                    $update_buku =
                        mysqli_query(
                            $conn,
                            "UPDATE buku_detail

                            SET status =
                            'Tersedia'

                            WHERE id =
                            $id_buku_detail"
                        );


                    if (!$update_buku) {

                        throw new Exception(
                            "Gagal mengubah status buku fisik."
                        );

                    }


                    $jumlah_dikembalikan++;

                }


                if ($jumlah_dikembalikan <= 0) {

                    throw new Exception(
                        "Tidak ada buku yang dapat dikembalikan."
                    );

                }


                mysqli_commit($conn);


                $pesan =
                    $jumlah_dikembalikan
                    . " buku berhasil dikonfirmasi sebagai dikembalikan.";


            } catch (Exception $e) {

                mysqli_rollback($conn);

                $error =
                    $e->getMessage();

            }

        }

    }

}


/* =====================================================
   CARI BUKU BERDASARKAN NOMOR BUKU
===================================================== */

if (isset($_POST["cari_buku"])) {

    $nomor_buku =
        mysqli_real_escape_string(
            $conn,
            trim(
                $_POST["nomor_buku"] ?? ""
            )
        );


    if ($nomor_buku == "") {

        $error =
            "Masukkan nomor buku fisik.";

    } else {

        $query_cari =
            mysqli_query(
                $conn,
                "SELECT

                    peminjaman.id
                        AS id_peminjaman,

                    peminjaman.id_buku_detail,

                    peminjaman.tanggal_pinjam,

                    peminjaman.tanggal_kembali,

                    siswa.nama
                        AS nama_siswa,

                    siswa.nisn,

                    kelas.nama_kelas,

                    buku.judul_buku,

                    buku_detail.nomor_buku

                FROM peminjaman

                JOIN siswa
                    ON peminjaman.id_siswa =
                    siswa.id

                LEFT JOIN kelas
                    ON siswa.id_kelas =
                    kelas.id

                JOIN buku
                    ON peminjaman.id_buku =
                    buku.id

                JOIN buku_detail
                    ON peminjaman.id_buku_detail =
                    buku_detail.id

                WHERE buku_detail.nomor_buku =
                    '$nomor_buku'

                AND peminjaman.status =
                    'Dipinjam'

                LIMIT 1"
            );


        $data_buku =
            mysqli_fetch_assoc(
                $query_cari
            );


        if (!$data_buku) {

            $error =
                "Buku dengan nomor "
                . $nomor_buku
                . " tidak sedang dipinjam.";

        }

    }

}


/* =====================================================
   DATA KELAS
===================================================== */

$query_kelas =
    mysqli_query(
        $conn,
        "SELECT
            id,
            nama_kelas

        FROM kelas

        ORDER BY nama_kelas ASC"
    );


/* =====================================================
   FILTER YANG DIPILIH
===================================================== */

$id_kelas_pilih =
    (int) ($_GET["kelas"] ?? 0);

$id_buku_pilih =
    (int) ($_GET["buku"] ?? 0);


$nama_kelas_pilih = "";
$judul_buku_pilih = "";


/* =====================================================
   DATA BUKU UNTUK FILTER
===================================================== */

$query_buku_filter =
    mysqli_query(
        $conn,
        "SELECT DISTINCT

            buku.id,

            buku.judul_buku

        FROM peminjaman

        JOIN siswa
            ON peminjaman.id_siswa =
            siswa.id

        JOIN buku
            ON peminjaman.id_buku =
            buku.id

        WHERE peminjaman.status =
            'Dipinjam'

        " .
        (
            $id_kelas_pilih > 0
            ?
            "AND siswa.id_kelas = $id_kelas_pilih"
            :
            ""
        )
        .
        "

        ORDER BY buku.judul_buku ASC"
    );


/* =====================================================
   DATA PENGEMBALIAN SESUAI FILTER
===================================================== */

$query_pengembalian_filter = null;


if (
    $id_kelas_pilih > 0 &&
    $id_buku_pilih > 0
) {


    /* AMBIL NAMA KELAS */

    $cek_kelas =
        mysqli_query(
            $conn,
            "SELECT nama_kelas

            FROM kelas

            WHERE id =
            $id_kelas_pilih

            LIMIT 1"
        );


    $data_kelas =
        mysqli_fetch_assoc(
            $cek_kelas
        );


    if ($data_kelas) {

        $nama_kelas_pilih =
            $data_kelas["nama_kelas"];

    }


    /* AMBIL JUDUL BUKU */

    $cek_buku =
        mysqli_query(
            $conn,
            "SELECT judul_buku

            FROM buku

            WHERE id =
            $id_buku_pilih

            LIMIT 1"
        );


    $data_judul_buku =
        mysqli_fetch_assoc(
            $cek_buku
        );


    if ($data_judul_buku) {

        $judul_buku_pilih =
            $data_judul_buku["judul_buku"];

    }


    /* AMBIL SISWA YANG MASIH MEMINJAM */

    $query_pengembalian_filter =
        mysqli_query(
            $conn,
            "SELECT

                peminjaman.id
                    AS id_peminjaman,

                siswa.nama
                    AS nama_siswa,

                siswa.nisn,

                buku.judul_buku,

                buku_detail.nomor_buku,

                peminjaman.tanggal_pinjam,

                peminjaman.tanggal_kembali

            FROM peminjaman

            JOIN siswa
                ON peminjaman.id_siswa =
                siswa.id

            JOIN buku
                ON peminjaman.id_buku =
                buku.id

            JOIN buku_detail
                ON peminjaman.id_buku_detail =
                buku_detail.id

            WHERE siswa.id_kelas =
                $id_kelas_pilih

            AND peminjaman.id_buku =
                $id_buku_pilih

            AND peminjaman.status =
                'Dipinjam'

            ORDER BY siswa.nama ASC"
        );

}


/* =====================================================
   DATA SEMUA BUKU YANG MASIH DIPINJAM
===================================================== */

$query_dipinjam =
    mysqli_query(
        $conn,
        "SELECT

            siswa.nama
                AS nama_siswa,

            siswa.nisn,

            kelas.nama_kelas,

            buku.judul_buku,

            buku_detail.nomor_buku,

            peminjaman.tanggal_pinjam,

            peminjaman.tanggal_kembali

        FROM peminjaman

        JOIN siswa
            ON peminjaman.id_siswa =
            siswa.id

        LEFT JOIN kelas
            ON siswa.id_kelas =
            kelas.id

        JOIN buku
            ON peminjaman.id_buku =
            buku.id

        JOIN buku_detail
            ON peminjaman.id_buku_detail =
            buku_detail.id

        WHERE peminjaman.status =
            'Dipinjam'

        ORDER BY
            kelas.nama_kelas ASC,
            buku.judul_buku ASC,
            siswa.nama ASC"
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
    Pengembalian Buku
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
    max-width: 1250px;
    margin: 30px auto;
    background: white;
    padding: 30px;
    border-radius: 10px;
}

h1 {
    margin-top: 0;
}

.card {
    background: #f8fafc;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 30px;
}

.form-filter {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: end;
}

.form-group {
    display: flex;
    flex-direction: column;
    min-width: 250px;
}

label {
    font-weight: bold;
    margin-bottom: 8px;
}

input,
select {
    padding: 12px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 15px;
}

button {
    padding: 12px 18px;
    border: none;
    border-radius: 6px;
    background: #2563eb;
    color: white;
    cursor: pointer;
    font-size: 14px;
}

button:hover {
    background: #1d4ed8;
}

.btn-konfirmasi {
    background: #16a34a;
    margin-top: 20px;
}

.btn-konfirmasi:hover {
    background: #15803d;
}

.pesan {
    background: #dcfce7;
    color: #166534;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.error {
    background: #fee2e2;
    color: #991b1b;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.info-filter {
    background: #e0f2fe;
    color: #075985;
    padding: 15px;
    border-radius: 6px;
    margin: 20px 0;
}

.checkbox-kembali {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.pilih-semua {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 20px 0;
}

.pilih-semua input {
    width: 20px;
    height: 20px;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
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

.kosong {
    text-align: center;
    padding: 20px;
}

.info-buku {
    background: white;
    padding: 20px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    margin-top: 20px;
}

.info-buku td:first-child {
    font-weight: bold;
    width: 200px;
}

.status-dipinjam {
    color: #dc2626;
    font-weight: bold;
}

.btn-kembali {
    display: inline-block;
    margin-top: 10px;
    margin-right: 5px;
    padding: 10px 15px;
    background: #64748b;
    color: white;
    text-decoration: none;
    border-radius: 6px;
}

@media (max-width: 700px) {

    .container {
        margin: 10px;
        padding: 15px;
    }

    .form-group {
        width: 100%;
    }

    table {
        font-size: 12px;
    }

    th,
    td {
        padding: 7px;
    }

}

</style>

</head>


<body>

<div class="container">


<h1>
    🔄 Pengembalian Buku
</h1>


<?php if ($pesan != "") { ?>

<div class="pesan">

<?php echo htmlspecialchars($pesan); ?>

</div>

<?php } ?>


<?php if ($error != "") { ?>

<div class="error">

<?php echo htmlspecialchars($error); ?>

</div>

<?php } ?>


<!-- =============================================
     PENGEMBALIAN PER KELAS DAN BUKU
============================================= -->

<div class="card">

<h2>
    👥 Pengembalian Per Kelas dan Mata Pelajaran
</h2>

<p>
    Pilih kelas terlebih dahulu, kemudian pilih buku atau mata pelajaran.
</p>


<form method="GET">

<div class="form-filter">


<div class="form-group">

<label>
    Pilih Kelas
</label>

<select
    name="kelas"
    id="filterKelas"
    onchange="this.form.submit()"
>

<option value="">
    -- Pilih Kelas --
</option>


<?php while ($kelas = mysqli_fetch_assoc($query_kelas)) { ?>

<option
    value="<?php echo $kelas["id"]; ?>"

    <?php
    if ($id_kelas_pilih == $kelas["id"]) {
        echo "selected";
    }
    ?>

>

<?php
echo htmlspecialchars(
    $kelas["nama_kelas"]
);
?>

</option>

<?php } ?>

</select>

</div>


<div class="form-group">

<label>
    Pilih Mata Pelajaran / Buku
</label>

<select
    name="buku"
    required
>

<option value="">
    -- Pilih Buku --
</option>


<?php

if ($query_buku_filter) {

    while (
        $buku_filter =
        mysqli_fetch_assoc(
            $query_buku_filter
        )
    ) {

?>

<option
    value="<?php echo $buku_filter["id"]; ?>"

    <?php
    if (
        $id_buku_pilih ==
        $buku_filter["id"]
    ) {
        echo "selected";
    }
    ?>

>

<?php
echo htmlspecialchars(
    $buku_filter["judul_buku"]
);
?>

</option>


<?php

    }

}

?>

</select>

</div>


<button type="submit">

    🔎 Tampilkan

</button>


</div>

</form>


<?php if ($query_pengembalian_filter) { ?>


<?php

$jumlah_dipinjam =
    mysqli_num_rows(
        $query_pengembalian_filter
    );

?>


<div class="info-filter">

<strong>
Kelas:
</strong>

<?php
echo htmlspecialchars(
    $nama_kelas_pilih
);
?>

<br>

<strong>
Buku / Mata Pelajaran:
</strong>

<?php
echo htmlspecialchars(
    $judul_buku_pilih
);
?>

<br><br>

Ditemukan

<strong>

<?php echo $jumlah_dipinjam; ?>

</strong>

buku yang masih dipinjam.

</div>


<form method="POST">

<input
    type="hidden"
    name="id_kelas"
    value="<?php echo $id_kelas_pilih; ?>"
>

<input
    type="hidden"
    name="id_buku"
    value="<?php echo $id_buku_pilih; ?>"
>


<?php if ($jumlah_dipinjam > 0) { ?>


<label class="pilih-semua">

<input
    type="checkbox"
    id="pilihSemua"
    checked
>

<strong>
Pilih Semua / Sudah Dikembalikan
</strong>

</label>


<table>

<tr>

<th>✓</th>
<th>No</th>
<th>NISN</th>
<th>Nama Siswa</th>
<th>Nomor Buku</th>
<th>Tanggal Pinjam</th>
<th>Rencana Kembali</th>

</tr>


<?php

$no = 1;


while (
    $data =
    mysqli_fetch_assoc(
        $query_pengembalian_filter
    )
) {

?>

<tr>


<td>

<input
    type="checkbox"
    name="peminjaman[]"
    value="<?php echo $data["id_peminjaman"]; ?>"
    class="checkbox-kembali"
    checked
>

</td>


<td>

<?php echo $no++; ?>

</td>


<td>

<?php
echo htmlspecialchars(
    $data["nisn"]
);
?>

</td>


<td>

<?php
echo htmlspecialchars(
    $data["nama_siswa"]
);
?>

</td>


<td>

<strong>

<?php
echo htmlspecialchars(
    $data["nomor_buku"]
);
?>

</strong>

</td>


<td>

<?php
echo htmlspecialchars(
    $data["tanggal_pinjam"]
);
?>

</td>


<td>

<?php
echo htmlspecialchars(
    $data["tanggal_kembali"]
);
?>

</td>


</tr>


<?php } ?>


</table>


<button
    type="submit"
    name="konfirmasi_kelas"
    class="btn-konfirmasi"
>

✓ Konfirmasi Pengembalian yang Dicentang

</button>


<?php } else { ?>


<p class="kosong">

Tidak ada siswa yang masih meminjam buku ini.

</p>


<?php } ?>


</form>


<?php } ?>


</div>


<!-- =============================================
     PENGEMBALIAN SATU BUKU
============================================= -->

<div class="card">

<h2>
    🔎 Pengembalian Satu Buku
</h2>


<form method="POST">

<div class="form-group">

<label>
    Nomor Buku Fisik
</label>

<input
    type="text"
    name="nomor_buku"
    placeholder="Contoh: MTK-001"
    required
>

</div>


<br>


<button
    type="submit"
    name="cari_buku"
>

🔎 Cari Buku

</button>


</form>


<?php if ($data_buku) { ?>


<div class="info-buku">

<h3>
    📖 Data Peminjaman
</h3>


<table>

<tr>
<td>Nomor Buku</td>
<td><?php echo htmlspecialchars($data_buku["nomor_buku"]); ?></td>
</tr>

<tr>
<td>Judul Buku</td>
<td><?php echo htmlspecialchars($data_buku["judul_buku"]); ?></td>
</tr>

<tr>
<td>Nama Siswa</td>
<td><?php echo htmlspecialchars($data_buku["nama_siswa"]); ?></td>
</tr>

<tr>
<td>NISN</td>
<td><?php echo htmlspecialchars($data_buku["nisn"]); ?></td>
</tr>

<tr>
<td>Kelas</td>
<td><?php echo htmlspecialchars($data_buku["nama_kelas"] ?? "-"); ?></td>
</tr>

<tr>
<td>Tanggal Pinjam</td>
<td><?php echo htmlspecialchars($data_buku["tanggal_pinjam"]); ?></td>
</tr>

<tr>
<td>Rencana Kembali</td>
<td><?php echo htmlspecialchars($data_buku["tanggal_kembali"]); ?></td>
</tr>

</table>


<form method="POST">

<input
    type="hidden"
    name="id_peminjaman"
    value="<?php echo $data_buku["id_peminjaman"]; ?>"
>

<input
    type="hidden"
    name="id_buku_detail"
    value="<?php echo $data_buku["id_buku_detail"]; ?>"
>


<button
    type="submit"
    name="konfirmasi_pengembalian"
    class="btn-konfirmasi"
>

✓ Konfirmasi Pengembalian

</button>


</form>


</div>


<?php } ?>


</div>


<!-- =============================================
     SEMUA BUKU DIPINJAM
============================================= -->

<div class="card">

<h2>
    📋 Semua Buku yang Sedang Dipinjam
</h2>


<table>

<tr>

<th>No</th>
<th>Nomor Buku</th>
<th>Judul Buku</th>
<th>Nama Siswa</th>
<th>Kelas</th>
<th>Tanggal Pinjam</th>
<th>Rencana Kembali</th>
<th>Status</th>

</tr>


<?php

$no = 1;


if (
    mysqli_num_rows(
        $query_dipinjam
    ) > 0
) {

    while (
        $data =
        mysqli_fetch_assoc(
            $query_dipinjam
        )
    ) {

?>

<tr>

<td>
<?php echo $no++; ?>
</td>

<td>
<strong>
<?php echo htmlspecialchars($data["nomor_buku"]); ?>
</strong>
</td>

<td>
<?php echo htmlspecialchars($data["judul_buku"]); ?>
</td>

<td>
<?php echo htmlspecialchars($data["nama_siswa"]); ?>
</td>

<td>
<?php echo htmlspecialchars($data["nama_kelas"] ?? "-"); ?>
</td>

<td>
<?php echo htmlspecialchars($data["tanggal_pinjam"]); ?>
</td>

<td>
<?php echo htmlspecialchars($data["tanggal_kembali"]); ?>
</td>

<td>

<span class="status-dipinjam">
Dipinjam
</span>

</td>

</tr>


<?php

    }

} else {

?>

<tr>

<td
    colspan="8"
    class="kosong"
>

Tidak ada buku yang sedang dipinjam.

</td>

</tr>

<?php } ?>


</table>

</div>


<a
    href="peminjaman.php"
    class="btn-kembali"
>

← Peminjaman

</a>


<a
    href="buku.php"
    class="btn-kembali"
>

← Data Buku

</a>


<a
    href="dashboard.php"
    class="btn-kembali"
>

← Dashboard

</a>


</div>


<script>


const pilihSemua =
    document.getElementById(
        "pilihSemua"
    );


if (pilihSemua) {

    pilihSemua.addEventListener(
        "change",
        function () {

            const semuaCheckbox =
                document.querySelectorAll(
                    ".checkbox-kembali"
                );


            semuaCheckbox.forEach(
                function (checkbox) {

                    checkbox.checked =
                        pilihSemua.checked;

                }
            );

        }
    );

}


</script>


</body>

</html>