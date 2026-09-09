<?php

session_start();

require_once __DIR__ . "/koneksi.php";


$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';


if ($username == '' || $password == '') {
    die("Username atau password kosong.");
}


/* MENGAMBIL DATA USER */
$query = "SELECT * FROM users WHERE username = '$username'";

$result = mysqli_query($conn, $query);


if (!$result) {
    die("Query database gagal: " . mysqli_error($conn));
}


if (mysqli_num_rows($result) == 1) {

    $user = mysqli_fetch_assoc($result);


    /* CEK PASSWORD */
    if ($password == $user['password']) {


        /* CEK STATUS AKUN */
        if ($user['status'] != 'aktif') {
            die("Akun tidak aktif.");
        }


        /* SIMPAN DATA KE SESSION */
        $_SESSION['id_user'] = $user['id_user'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];


        /* REDIRECT BERDASARKAN ROLE */
        if ($user['role'] == 'admin') {

            header("Location: dashboard.php");
            exit;

        } elseif ($user['role'] == 'petugas') {

            header("Location: dashboard_petugas.php");
            exit;

        } elseif ($user['role'] == 'keluarga') {

            header("Location: dashboard_keluarga.php");
            exit;

        } else {

            die("Role user tidak dikenali.");

        }


    } else {

        die("Password salah.");

    }


} else {

    die("Username tidak ditemukan.");

}