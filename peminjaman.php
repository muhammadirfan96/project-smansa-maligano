<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/koneksi.php";

$pesan = "";
$error = "";


/* =====================================================
   PEMINJAMAN PER SISWA
===================================================== */

if (isset($_POST["pinjam_siswa"])) {

    $id_siswa = (int) ($_POST["id_siswa"] ?? 0);

    $id_buku_detail =
        (int) ($_POST["id_buku_detail"] ?? 0);

    $tanggal_pinjam =
        $_POST["tanggal_pinjam"] ?? "";

    $tanggal_kembali =
        $_POST["tanggal_kembali"] ?? "";


    if (
        $id_siswa <= 0 ||
        $id_buku_detail <= 0 ||
        $tanggal_pinjam == "" ||
        $tanggal_kembali == ""
    ) {

        $error = "Semua data wajib diisi.";

    } elseif ($tanggal_kembali < $tanggal_pinjam) {

        $error =
            "Tanggal kembali tidak boleh lebih awal dari tanggal pinjam.";

    } else {

        mysqli_begin_transaction($conn);

        try {

            /* =============================================
               CEK SISWA
            ============================================= */

            $cek_siswa = mysqli_query(
                $conn,
                "SELECT id, nama
                 FROM siswa
                 WHERE id = $id_siswa"
            );


            $siswa =
                mysqli_fetch_assoc(
                    $cek_siswa
                );


            if (!$siswa) {

                throw new Exception(
                    "Data siswa tidak ditemukan."
                );

            }


            /* =============================================
               CEK BUKU FISIK

               FOR UPDATE digunakan agar buku yang sama
               tidak dipinjam dua kali pada waktu bersamaan.
            ============================================= */

            $cek_buku_detail =
                mysqli_query(
                    $conn,
                    "SELECT
                        buku_detail.id,
                        buku_detail.id_buku,
                        buku_detail.nomor_buku,
                        buku_detail.status,
                        buku.judul_buku

                     FROM buku_detail

                     JOIN buku
                        ON buku_detail.id_buku = buku.id

                     WHERE buku_detail.id =
                        $id_buku_detail

                     FOR UPDATE"
                );


            $buku_detail =
                mysqli_fetch_assoc(
                    $cek_buku_detail
                );


            if (!$buku_detail) {

                throw new Exception(
                    "Nomor buku fisik tidak ditemukan."
                );

            }


            if (
                $buku_detail["status"]
                != "Tersedia"
            ) {

                throw new Exception(
                    "Buku fisik "
                    . $buku_detail["nomor_buku"]
                    . " sedang tidak tersedia."
                );

            }


            $id_buku =
                (int)
                $buku_detail["id_buku"];


            /* =============================================
               CEK SISWA SUDAH MEMINJAM JUDUL YANG SAMA
            ============================================= */

            $cek_pinjaman_sama =
                mysqli_query(
                    $conn,
                    "SELECT id
                     FROM peminjaman

                     WHERE id_siswa =
                        $id_siswa

                     AND id_buku =
                        $id_buku

                     AND status =
                        'Dipinjam'"
                );


            if (
                mysqli_num_rows(
                    $cek_pinjaman_sama
                ) > 0
            ) {

                throw new Exception(
                    "Siswa tersebut masih meminjam judul buku yang sama."
                );

            }


            /* =============================================
               SIMPAN PEMINJAMAN
            ============================================= */

            $simpan =
                mysqli_query(
                    $conn,
                    "INSERT INTO peminjaman
                    (
                        id_siswa,
                        id_buku,
                        id_buku_detail,
                        tanggal_pinjam,
                        tanggal_kembali,
                        status
                    )
                    VALUES
                    (
                        $id_siswa,
                        $id_buku,
                        $id_buku_detail,
                        '$tanggal_pinjam',
                        '$tanggal_kembali',
                        'Dipinjam'
                    )"
                );


            if (!$simpan) {

                throw new Exception(
                    "Gagal menyimpan peminjaman: "
                    . mysqli_error($conn)
                );

            }


            /* =============================================
               UBAH STATUS BUKU FISIK
            ============================================= */

            $ubah_status =
                mysqli_query(
                    $conn,
                    "UPDATE buku_detail

                     SET status =
                        'Dipinjam'

                     WHERE id =
                        $id_buku_detail

                     AND status =
                        'Tersedia'"
                );


            if (
                !$ubah_status ||
                mysqli_affected_rows($conn) <= 0
            ) {

                throw new Exception(
                    "Gagal mengubah status buku menjadi Dipinjam."
                );

            }


            mysqli_commit($conn);


            $pesan =
                "Buku "
                . $buku_detail["judul_buku"]
                . " ("
                . $buku_detail["nomor_buku"]
                . ") berhasil dipinjam oleh "
                . $siswa["nama"]
                . ".";


        } catch (Exception $e) {

            mysqli_rollback($conn);

            $error =
                $e->getMessage();

        }

    }

}


/* =====================================================
   PEMINJAMAN PER KELAS
===================================================== */

if (isset($_POST["pinjam_kelas"])) {

    $id_kelas =
        (int) ($_POST["id_kelas"] ?? 0);

    $id_buku =
        (int) ($_POST["id_buku_kelas"] ?? 0);

    $tanggal_pinjam =
        $_POST["tanggal_pinjam_kelas"] ?? "";

    $tanggal_kembali =
        $_POST["tanggal_kembali_kelas"] ?? "";

    $siswa_terpilih =
        $_POST["siswa"] ?? [];


    if (
        $id_kelas <= 0 ||
        $id_buku <= 0 ||
        $tanggal_pinjam == "" ||
        $tanggal_kembali == ""
    ) {

        $error =
            "Semua data peminjaman kelas wajib diisi.";

    } elseif ($tanggal_kembali < $tanggal_pinjam) {

        $error =
            "Tanggal kembali tidak boleh lebih awal dari tanggal pinjam.";

    } elseif (empty($siswa_terpilih)) {

        $error =
            "Pilih minimal satu siswa.";

    } else {

        /* =============================================
           BERSIHKAN ID SISWA
        ============================================= */

        $id_siswa_valid = [];


        foreach ($siswa_terpilih as $id) {

            $id = (int) $id;

            if ($id > 0) {

                $id_siswa_valid[] =
                    $id;

            }

        }


        $id_siswa_valid =
            array_values(
                array_unique(
                    $id_siswa_valid
                )
            );


        $jumlah_siswa =
            count(
                $id_siswa_valid
            );


        if ($jumlah_siswa <= 0) {

            $error =
                "Tidak ada siswa yang valid.";

        } else {

            $daftar_id =
                implode(
                    ",",
                    $id_siswa_valid
                );


            /* =============================================
               PASTIKAN SISWA BERADA DI KELAS TERSEBUT
            ============================================= */

            $cek_siswa_kelas =
                mysqli_query(
                    $conn,
                    "SELECT id

                     FROM siswa

                     WHERE id_kelas =
                        $id_kelas

                     AND id IN (
                        $daftar_id
                     )"
                );


            if (
                mysqli_num_rows(
                    $cek_siswa_kelas
                ) != $jumlah_siswa
            ) {

                $error =
                    "Ada siswa yang tidak sesuai dengan kelas.";

            } else {

                mysqli_begin_transaction(
                    $conn
                );


                try {

                    /* =============================================
                       CEK BUKU
                    ============================================= */

                    $cek_buku =
                        mysqli_query(
                            $conn,
                            "SELECT
                                id,
                                judul_buku

                             FROM buku

                             WHERE id =
                                $id_buku"
                        );


                    $buku =
                        mysqli_fetch_assoc(
                            $cek_buku
                        );


                    if (!$buku) {

                        throw new Exception(
                            "Data buku tidak ditemukan."
                        );

                    }


                    /* =============================================
                       CEK SISWA YANG SUDAH MEMINJAM JUDUL SAMA
                    ============================================= */

                    $cek_duplikat =
                        mysqli_query(
                            $conn,
                            "SELECT
                                id_siswa

                             FROM peminjaman

                             WHERE id_siswa IN (
                                $daftar_id
                             )

                             AND id_buku =
                                $id_buku

                             AND status =
                                'Dipinjam'"
                        );


                    $siswa_duplikat = [];


                    while (
                        $data_duplikat =
                        mysqli_fetch_assoc(
                            $cek_duplikat
                        )
                    ) {

                        $siswa_duplikat[] =
                            (int)
                            $data_duplikat[
                                "id_siswa"
                            ];

                    }


                    /* =============================================
                       SISWA YANG MASIH BOLEH MEMINJAM
                    ============================================= */

                    $siswa_bisa_pinjam =
                        array_values(
                            array_diff(
                                $id_siswa_valid,
                                $siswa_duplikat
                            )
                        );


                    $jumlah_bisa_pinjam =
                        count(
                            $siswa_bisa_pinjam
                        );


                    if (
                        $jumlah_bisa_pinjam <= 0
                    ) {

                        throw new Exception(
                            "Semua siswa yang dipilih sudah meminjam buku ini."
                        );

                    }


                    /* =============================================
                       AMBIL BUKU FISIK TERSEDIA

                       Satu siswa mendapat satu nomor buku.
                    ============================================= */

                    $query_buku_tersedia =
                        mysqli_query(
                            $conn,
                            "SELECT
                                id,
                                nomor_buku

                             FROM buku_detail

                             WHERE id_buku =
                                $id_buku

                             AND status =
                                'Tersedia'

                             ORDER BY nomor_buku ASC

                             LIMIT
                                $jumlah_bisa_pinjam

                             FOR UPDATE"
                        );


                    $buku_fisik = [];


                    while (
                        $detail =
                        mysqli_fetch_assoc(
                            $query_buku_tersedia
                        )
                    ) {

                        $buku_fisik[] =
                            $detail;

                    }


                    $jumlah_buku_fisik =
                        count(
                            $buku_fisik
                        );


                    if (
                        $jumlah_buku_fisik
                        <
                        $jumlah_bisa_pinjam
                    ) {

                        throw new Exception(
                            "Buku fisik yang tersedia tidak mencukupi. "
                            . "Dibutuhkan "
                            . $jumlah_bisa_pinjam
                            . " buku, tersedia "
                            . $jumlah_buku_fisik
                            . "."
                        );

                    }


                    /* =============================================
                       SIMPAN PEMINJAMAN

                       Siswa 1 → Buku fisik 1
                       Siswa 2 → Buku fisik 2
                    ============================================= */

                    for (
                        $i = 0;
                        $i < $jumlah_bisa_pinjam;
                        $i++
                    ) {

                        $id_siswa =
                            (int)
                            $siswa_bisa_pinjam[$i];


                        $id_buku_detail =
                            (int)
                            $buku_fisik[$i]["id"];


                        $simpan =
                            mysqli_query(
                                $conn,
                                "INSERT INTO peminjaman
                                (
                                    id_siswa,
                                    id_buku,
                                    id_buku_detail,
                                    tanggal_pinjam,
                                    tanggal_kembali,
                                    status
                                )
                                VALUES
                                (
                                    $id_siswa,
                                    $id_buku,
                                    $id_buku_detail,
                                    '$tanggal_pinjam',
                                    '$tanggal_kembali',
                                    'Dipinjam'
                                )"
                            );


                        if (!$simpan) {

                            throw new Exception(
                                "Gagal menyimpan peminjaman."
                            );

                        }


                        /* UBAH STATUS BUKU FISIK */

                        $ubah_status =
                            mysqli_query(
                                $conn,
                                "UPDATE buku_detail

                                 SET status =
                                    'Dipinjam'

                                 WHERE id =
                                    $id_buku_detail

                                 AND status =
                                    'Tersedia'"
                            );


                        if (
                            !$ubah_status ||
                            mysqli_affected_rows($conn) <= 0
                        ) {

                            throw new Exception(
                                "Gagal mengubah status buku fisik."
                            );

                        }

                    }


                    mysqli_commit(
                        $conn
                    );


                    $pesan =
                        $jumlah_bisa_pinjam
                        . " siswa berhasil meminjam buku "
                        . $buku["judul_buku"]
                        . ".";


                    $jumlah_duplikat =
                        count(
                            $siswa_duplikat
                        );


                    if (
                        $jumlah_duplikat > 0
                    ) {

                        $pesan .=
                            " "
                            . $jumlah_duplikat
                            . " siswa dilewati karena sudah meminjam judul buku tersebut.";

                    }


                } catch (Exception $e) {

                    mysqli_rollback(
                        $conn
                    );

                    $error =
                        $e->getMessage();

                }

            }

        }

    }

}


/* =====================================================
   DATA KELAS
===================================================== */

$query_kelas =
    mysqli_query(
        $conn,
        "SELECT *
         FROM kelas
         ORDER BY nama_kelas ASC"
    );


/* =====================================================
   KELAS YANG DIPILIH
===================================================== */

$id_kelas_pilih =
    (int) ($_GET["kelas"] ?? 0);


$query_siswa_kelas =
    null;


if ($id_kelas_pilih > 0) {

    $query_siswa_kelas =
        mysqli_query(
            $conn,
            "SELECT
                id,
                nisn,
                nama

             FROM siswa

             WHERE id_kelas =
                $id_kelas_pilih

             ORDER BY nama ASC"
        );

}


/* =====================================================
   DATA SEMUA SISWA
===================================================== */

$query_siswa =
    mysqli_query(
        $conn,
        "SELECT
            siswa.id,
            siswa.nisn,
            siswa.nama,
            kelas.nama_kelas

         FROM siswa

         LEFT JOIN kelas
            ON siswa.id_kelas = kelas.id

         ORDER BY siswa.nama ASC"
    );


/* =====================================================
   BUKU FISIK TERSEDIA UNTUK PEMINJAMAN PER SISWA
===================================================== */

$query_buku_detail =
    mysqli_query(
        $conn,
        "SELECT
            buku_detail.id,
            buku_detail.nomor_buku,
            buku.judul_buku

         FROM buku_detail

         JOIN buku
            ON buku_detail.id_buku = buku.id

         WHERE buku_detail.status =
            'Tersedia'

         ORDER BY
            buku.judul_buku ASC,
            buku_detail.nomor_buku ASC"
    );


/* =====================================================
   BUKU UNTUK PEMINJAMAN PER KELAS

   Menampilkan jumlah buku fisik yang benar-benar tersedia.
===================================================== */

$query_buku_kelas =
    mysqli_query(
        $conn,
        "SELECT
            buku.id,
            buku.judul_buku,
            COUNT(
                buku_detail.id
            ) AS tersedia

         FROM buku

         JOIN buku_detail
            ON buku_detail.id_buku =
                buku.id

         WHERE buku_detail.status =
            'Tersedia'

         GROUP BY
            buku.id,
            buku.judul_buku

         HAVING tersedia > 0

         ORDER BY
            buku.judul_buku ASC"
    );


/* =====================================================
   DATA PEMINJAMAN
===================================================== */

$query_peminjaman =
    mysqli_query(
        $conn,
        "SELECT

            peminjaman.*,

            siswa.nisn,

            siswa.nama AS nama_siswa,

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

         LEFT JOIN buku_detail
            ON peminjaman.id_buku_detail =
                buku_detail.id

         ORDER BY
            peminjaman.id DESC"
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
    Peminjaman Buku - SMAN 1 Maligano
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

.card {
    background: #f8fafc;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.form-group {
    display: flex;
    flex-direction: column;
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

.siswa-list {
    margin-top: 20px;
    max-height: 400px;
    overflow-y: auto;
    background: white;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 15px;
}

.siswa-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px;
    border-bottom: 1px solid #e2e8f0;
}

.siswa-item input {
    width: auto;
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

.status-dipinjam {
    color: #dc2626;
    font-weight: bold;
}

.status-dikembalikan {
    color: #16a34a;
    font-weight: bold;
}

.btn-kembali {
    display: inline-block;
    margin-top: 25px;
    padding: 10px 15px;
    background: #64748b;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    margin-right: 5px;
}

.kosong {
    text-align: center;
    padding: 20px;
}

@media (max-width: 700px) {

    .form-grid {
        grid-template-columns: 1fr;
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
    📚 Peminjaman Buku
</h1>


<p>
    Peminjaman buku berdasarkan nomor buku fisik.
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


<!-- =================================================
     PEMINJAMAN PER KELAS
================================================= -->

<div class="card">


<h2>
    👥 Peminjaman Per Kelas
</h2>


<form method="GET">


<label>
    Pilih Kelas
</label>


<select
    name="kelas"
    required
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


<button type="submit">

    Tampilkan Siswa

</button>


</form>


<?php if ($query_siswa_kelas) { ?>


<hr style="margin:30px 0 20px;">


<form method="POST">


<input
    type="hidden"
    name="id_kelas"
    value="<?php echo $id_kelas_pilih; ?>"
>


<div class="form-grid">


<div class="form-group">

<label>
    Pilih Judul Buku
</label>


<select
    name="id_buku_kelas"
    required
>

<option value="">
    -- Pilih Buku --
</option>


<?php while ($buku = mysqli_fetch_assoc($query_buku_kelas)) { ?>

<option
    value="<?php echo $buku["id"]; ?>"
>

<?php
echo htmlspecialchars(
    $buku["judul_buku"]
);
?>

- Tersedia:

<?php
echo $buku["tersedia"];
?>

buku fisik

</option>


<?php } ?>


</select>

</div>


<div class="form-group">

<label>
    Tanggal Pinjam
</label>


<input
    type="date"
    name="tanggal_pinjam_kelas"
    value="<?php echo date("Y-m-d"); ?>"
    required
>

</div>


<div class="form-group">

<label>
    Rencana Kembali
</label>


<input
    type="date"
    name="tanggal_kembali_kelas"
    required
>

</div>


</div>


<label
    style="
        display:block;
        margin-top:20px;
    "
>

<input
    type="checkbox"
    id="pilihSemua"
    checked
>

Pilih Semua Siswa

</label>


<div class="siswa-list">


<?php

if (
    mysqli_num_rows(
        $query_siswa_kelas
    ) > 0
) {

    while (
        $siswa =
        mysqli_fetch_assoc(
            $query_siswa_kelas
        )
    ) {

?>

<label class="siswa-item">

<input
    type="checkbox"
    name="siswa[]"
    value="<?php echo $siswa["id"]; ?>"
    checked
>


<?php
echo htmlspecialchars(
    $siswa["nama"]
);
?>

-

NISN:

<?php
echo htmlspecialchars(
    $siswa["nisn"]
);
?>

</label>


<?php

    }

} else {

?>

<p class="kosong">

    Belum ada siswa dalam kelas ini.

</p>

<?php } ?>


</div>


<button
    type="submit"
    name="pinjam_kelas"
>

    📚 Simpan Peminjaman Kelas

</button>


</form>


<?php } ?>


</div>


<!-- =================================================
     PEMINJAMAN PER SISWA
================================================= -->

<div class="card">


<h2>
    👤 Peminjaman Per Siswa
</h2>


<form method="POST">


<div class="form-grid">


<div class="form-group">

<label>
    Siswa
</label>


<select
    name="id_siswa"
    required
>

<option value="">
    -- Pilih Siswa --
</option>


<?php while ($siswa = mysqli_fetch_assoc($query_siswa)) { ?>

<option
    value="<?php echo $siswa["id"]; ?>"
>

<?php
echo htmlspecialchars(
    $siswa["nama"]
);
?>

-

<?php
echo htmlspecialchars(
    $siswa["nama_kelas"]
    ?? "-"
);
?>

</option>


<?php } ?>


</select>

</div>


<div class="form-group">

<label>
    Nomor Buku Fisik
</label>


<select
    name="id_buku_detail"
    required
>

<option value="">
    -- Pilih Nomor Buku --
</option>


<?php
while (
    $detail =
    mysqli_fetch_assoc(
        $query_buku_detail
    )
) {
?>

<option
    value="<?php echo $detail["id"]; ?>"
>

<?php
echo htmlspecialchars(
    $detail["nomor_buku"]
);
?>

-

<?php
echo htmlspecialchars(
    $detail["judul_buku"]
);
?>

</option>


<?php } ?>


</select>

</div>


<div class="form-group">

<label>
    Tanggal Pinjam
</label>


<input
    type="date"
    name="tanggal_pinjam"
    value="<?php echo date("Y-m-d"); ?>"
    required
>

</div>


<div class="form-group">

<label>
    Rencana Kembali
</label>


<input
    type="date"
    name="tanggal_kembali"
    required
>

</div>


</div>


<button
    type="submit"
    name="pinjam_siswa"
>

    📚 Simpan Peminjaman

</button>


</form>


</div>


<!-- =================================================
     DATA PEMINJAMAN
================================================= -->

<h2>
    📋 Data Peminjaman
</h2>


<table>


<tr>

<th>No</th>
<th>NISN</th>
<th>Nama Siswa</th>
<th>Kelas</th>
<th>Judul Buku</th>
<th>Nomor Buku</th>
<th>Tanggal Pinjam</th>
<th>Rencana Kembali</th>
<th>Status</th>

</tr>


<?php

$no = 1;


if (
    mysqli_num_rows(
        $query_peminjaman
    ) > 0
) {

    while (
        $data =
        mysqli_fetch_assoc(
            $query_peminjaman
        )
    ) {

?>

<tr>


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

<?php
echo htmlspecialchars(
    $data["nama_kelas"]
    ?? "-"
);
?>

</td>


<td>

<?php
echo htmlspecialchars(
    $data["judul_buku"]
);
?>

</td>


<td>

<?php
echo htmlspecialchars(
    $data["nomor_buku"]
    ?? "-"
);
?>

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


<td>

<?php
if (
    $data["status"]
    == "Dipinjam"
) {
?>

<span class="status-dipinjam">

    Dipinjam

</span>

<?php } else { ?>

<span class="status-dikembalikan">

    Dikembalikan

</span>

<?php } ?>

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

    Belum ada data peminjaman.

</td>

</tr>


<?php } ?>


</table>


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

            const daftarSiswa =
                document.querySelectorAll(
                    'input[name="siswa[]"]'
                );


            daftarSiswa.forEach(
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