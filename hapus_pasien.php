<?php

session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

require_once "koneksi.php";

/* =========================
   CEK ID PASIEN
========================= */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: data_pasien.php");
    exit;
}

$id_pasien = (int) $_GET['id'];


/* =========================
   CEK DATA PASIEN
========================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT id_pasien FROM pasien WHERE id_pasien = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_pasien
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {

    mysqli_stmt_close($stmt);

    header("Location: data_pasien.php");
    exit;
}

mysqli_stmt_close($stmt);


/* =========================
   HAPUS DATA PASIEN
========================= */

$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM pasien WHERE id_pasien = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_pasien
);


if (mysqli_stmt_execute($stmt)) {

    mysqli_stmt_close($stmt);

    header("Location: data_pasien.php?status=deleted");
    exit;

} else {

    mysqli_stmt_close($stmt);

    die(
        "Data pasien gagal dihapus: "
        . mysqli_error($conn)
    );
}

?>