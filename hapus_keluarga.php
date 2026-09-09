<?php

session_start();


/* =========================
   CEK LOGIN
========================= */

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}


require_once "koneksi.php";


/* =========================
   CEK ID KELUARGA
========================= */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: data_pasien.php");
    exit;
}


$id_keluarga = (int) $_GET['id'];


/* =========================
   AMBIL ID PASIEN
========================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT id_pasien
     FROM keluarga
     WHERE id_keluarga = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_keluarga
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$keluarga = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* =========================
   CEK DATA KELUARGA
========================= */

if (!$keluarga) {
    die("Data keluarga tidak ditemukan.");
}


$id_pasien = (int) $keluarga['id_pasien'];


/* =========================
   HAPUS DATA
========================= */

$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM keluarga
     WHERE id_keluarga = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_keluarga
);


if (mysqli_stmt_execute($stmt)) {

    mysqli_stmt_close($stmt);

    header(
        "Location: detail_pasien.php?id=" .
        $id_pasien .
        "&status=family_deleted"
    );

    exit;

} else {

    mysqli_stmt_close($stmt);

    die(
        "Data keluarga gagal dihapus: " .
        mysqli_error($conn)
    );
}

?>