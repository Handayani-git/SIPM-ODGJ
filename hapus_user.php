<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

require_once "koneksi.php";


/* =========================
   CEK ID USER
========================= */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: data_user.php");
    exit;
}

$id_user = (int) $_GET['id'];


/* =========================
   CEGAH HAPUS DIRI SENDIRI
========================= */

if ($id_user === (int) $_SESSION['id_user']) {

    header(
        "Location: data_user.php?status=self_delete"
    );

    exit;
}


/* =========================
   CEK DATA USER
========================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT id_user
     FROM users
     WHERE id_user = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_user
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$user = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$user) {

    header(
        "Location: data_user.php?status=not_found"
    );

    exit;
}


/* =========================
   HAPUS USER
========================= */

$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM users
     WHERE id_user = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_user
);


if (mysqli_stmt_execute($stmt)) {

    mysqli_stmt_close($stmt);

    header(
        "Location: data_user.php?status=deleted"
    );

    exit;

} else {

    $error =
        "User gagal dihapus: "
        . mysqli_stmt_error($stmt);

    mysqli_stmt_close($stmt);

    die($error);
}