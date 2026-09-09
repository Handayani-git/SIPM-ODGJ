<?php

session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    die("Anda tidak memiliki akses ke halaman ini.");
}

require_once "koneksi.php";


/* =========================================================
   DATA USER KELUARGA
========================================================= */

$query_user = "
    SELECT
        id_user,
        nama_lengkap,
        username
    FROM users
    WHERE role = 'keluarga'
    AND status = 'aktif'
    ORDER BY nama_lengkap ASC
";

$result_user = mysqli_query($conn, $query_user);


/* =========================================================
   DATA PASIEN
========================================================= */

$query_pasien = "
    SELECT
        id_pasien,
        nomor_registrasi,
        nama_pasien
    FROM pasien
    WHERE status_pasien = 'Aktif'
    ORDER BY nama_pasien ASC
";

$result_pasien = mysqli_query($conn, $query_pasien);


/* =========================================================
   PASIEN DARI URL
   Contoh:
   tambah_keluarga.php?id_pasien=2
========================================================= */

$id_pasien_url = isset($_GET['id_pasien'])
    ? (int) $_GET['id_pasien']
    : 0;


/* =========================================================
   NILAI FORM
========================================================= */

$id_user_form       = '';
$id_pasien_form     = $id_pasien_url;
$nama_keluarga_form = '';
$hubungan_form      = '';
$nomor_telepon_form = '';
$alamat_form        = '';

$error = '';


/* =========================================================
   PROSES SIMPAN DATA
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_user       = (int) ($_POST['id_user'] ?? 0);
    $id_pasien     = (int) ($_POST['id_pasien'] ?? 0);
    $nama_keluarga = trim($_POST['nama_keluarga'] ?? '');
    $hubungan      = trim($_POST['hubungan'] ?? '');
    $nomor_telepon = trim($_POST['nomor_telepon'] ?? '');
    $alamat        = trim($_POST['alamat'] ?? '');


    /* Simpan kembali isi form jika terjadi error */

    $id_user_form       = $id_user;
    $id_pasien_form     = $id_pasien;
    $nama_keluarga_form = $nama_keluarga;
    $hubungan_form      = $hubungan;
    $nomor_telepon_form = $nomor_telepon;
    $alamat_form        = $alamat;


    /* =========================
       VALIDASI
    ========================= */

    if (
        $id_user <= 0 ||
        $id_pasien <= 0 ||
        $nama_keluarga === '' ||
        $hubungan === '' ||
        $nomor_telepon === '' ||
        $alamat === ''
    ) {

        $error = "Semua data wajib diisi.";

    } else {

        $query_insert = "
            INSERT INTO keluarga
            (
                id_user,
                id_pasien,
                nama_keluarga,
                hubungan,
                nomor_telepon,
                alamat
            )
            VALUES (?, ?, ?, ?, ?, ?)
        ";

        $stmt = mysqli_prepare($conn, $query_insert);

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "iissss",
                $id_user,
                $id_pasien,
                $nama_keluarga,
                $hubungan,
                $nomor_telepon,
                $alamat
            );

            if (mysqli_stmt_execute($stmt)) {

                mysqli_stmt_close($stmt);

                header(
                    "Location: detail_pasien.php?id=" . $id_pasien
                );

                exit;

            } else {

                $error =
                    "Gagal menyimpan data keluarga: " .
                    mysqli_stmt_error($stmt);

                mysqli_stmt_close($stmt);
            }

        } else {

            $error = "Query tidak dapat diproses.";
        }
    }
}


/* =========================================================
   FUNCTION ESCAPE
========================================================= */

function e($value)
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

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
        Tambah Data Keluarga - SIPM ODGJ
    </title>


    <!-- GOOGLE FONT -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <!-- BOOTSTRAP ICON -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <!-- DASHBOARD CSS -->

    <link
        rel="stylesheet"
        href="/SIPM-ODGJ/assets/css/dashboard.css"
    >


   <style>

/* =========================================================
   RESET BACKGROUND
   Hilangkan gambar/logo besar dari dashboard.css
========================================================= */

html,
body {
    margin: 0;
    padding: 0;
    background: #f6f8fc !important;
    background-image: none !important;
}

body::before,
body::after,
.dashboard-layout::before,
.dashboard-layout::after,
.main-content::before,
.main-content::after,
.page-content::before,
.page-content::after {
    content: none !important;
    display: none !important;
    background-image: none !important;
}

/* =========================================================
   MAIN CONTENT
========================================================= */

.main-content {
    background: #f6f8fc !important;
    background-image: none !important;
    min-height: 100vh;
}

/* =========================================================
   PAGE CONTENT
========================================================= */

.page-content {
    padding: 28px;
    background: transparent !important;
    background-image: none !important;
}

/* =========================================================
   PAGE HEADER
========================================================= */

.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 24px;
}

.page-title h2 {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
    color: #17243a;
}

.page-title p {
    margin: 6px 0 0;
    font-size: 13px;
    color: #7a8499;
}

/* =========================================================
   BACK BUTTON
========================================================= */

.back-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 15px;
    border: 1px solid #dce3ee;
    border-radius: 8px;
    background: #ffffff;
    color: #526078;
    text-decoration: none;
    font-size: 12px;
    font-weight: 500;
    transition: 0.2s;
}

.back-button:hover {
    border-color: #2864e6;
    color: #2864e6;
    background: #ffffff;
}

/* =========================================================
   FORM CARD
========================================================= */

.family-form-card {
    width: 100%;
    max-width: none;
    box-sizing: border-box;
    background: #ffffff;
    border: 1px solid #e1e7f0;
    border-radius: 12px;
    padding: 20px;
    box-shadow: none;
}

/* =========================================================
   CARD HEADER
========================================================= */

.family-card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding-bottom: 16px;
    margin-bottom: 20px;
    border-bottom: 1px solid #edf0f5;
}

.family-card-icon {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border-radius: 9px;
    background: #eaf1ff;
    color: #2864e6;
    font-size: 17px;
}

.family-card-header h3 {
    margin: 0;
    color: #17243a;
    font-size: 16px;
    font-weight: 600;
}

.family-card-header p {
    margin: 4px 0 0;
    color: #7a8499;
    font-size: 12px;
}

/* =========================================================
   ERROR
========================================================= */

.error-message {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 20px;
    padding: 12px 15px;
    border: 1px solid #ffd2d2;
    border-radius: 8px;
    background: #fff0f0;
    color: #d83b3b;
    font-size: 12px;
}

/* =========================================================
   FORM GRID
========================================================= */

.family-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px 24px;
}

.family-form-group {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.family-form-group.full-width {
    grid-column: 1 / -1;
}

/* =========================================================
   LABEL
========================================================= */

.family-form-group label {
    margin-bottom: 7px;
    color: #344054;
    font-size: 12px;
    font-weight: 500;
}

.required {
    color: #e53935;
}

/* =========================================================
   INPUT / SELECT / TEXTAREA
========================================================= */

.family-form-group input,
.family-form-group select,
.family-form-group textarea {
    width: 100%;
    box-sizing: border-box;
    padding: 11px 13px;
    border: 1px solid #dce3ee;
    border-radius: 8px;
    background: #ffffff;
    color: #26334a;
    font-family: 'Poppins', sans-serif;
    font-size: 12px;
    outline: none;
    transition: 0.2s;
}

.family-form-group input::placeholder,
.family-form-group textarea::placeholder {
    color: #a0a9b8;
}

.family-form-group input:focus,
.family-form-group select:focus,
.family-form-group textarea:focus {
    border-color: #2864e6;
    box-shadow: 0 0 0 3px rgba(40, 100, 230, 0.08);
}

.family-form-group select {
    cursor: pointer;
}

/* =========================================================
   TEXTAREA
========================================================= */

.family-form-group textarea {
    min-height: 100px;
    resize: vertical;
}

/* =========================================================
   FORM FOOTER
========================================================= */

.family-form-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 24px;
    padding-top: 18px;
    border-top: 1px solid #edf0f5;
}

/* =========================================================
   BUTTON
========================================================= */

.family-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 16px;
    border-radius: 8px;
    font-family: 'Poppins', sans-serif;
    font-size: 12px;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: 0.2s;
}

/* BATAL */

.family-btn-cancel {
    border: 1px solid #dce3ee;
    background: #ffffff;
    color: #526078;
}

.family-btn-cancel:hover {
    border-color: #2864e6;
    color: #2864e6;
}

/* SIMPAN */

.family-btn-save {
    border: none;
    background: #2864e6;
    color: #ffffff;
}

.family-btn-save:hover {
    background: #1f56ca;
}

/* =========================================================
   SIDEBAR
========================================================= */

.sidebar {
    background-image: none !important;
}

.sidebar::before,
.sidebar::after {
    content: none !important;
    display: none !important;
    background-image: none !important;
}

/* Logo sidebar tetap kecil */

.sidebar-logo img {
    max-width: 55px !important;
    max-height: 55px !important;
    width: auto !important;
    height: auto !important;
    object-fit: contain;
}

/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 800px) {

    .page-content {
        padding: 20px;
    }

    .page-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .family-form-grid {
        grid-template-columns: 1fr;
    }

    .family-form-group.full-width {
        grid-column: auto;
    }

    .family-form-footer {
        justify-content: stretch;
    }

    .family-form-footer .family-btn {
        flex: 1;
    }
}



/* ===== SIDEBAR FINAL - SAMA SEPERTI DATA PASIEN ===== */
.sidebar {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    bottom: 0 !important;
    width: 170px !important;
    min-width: 170px !important;
    height: 100vh !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: stretch !important;
    padding: 0 !important;
    margin: 0 !important;
    overflow: hidden !important;
    background: #eef4ff !important;
    background-image: none !important;
    border-right: 1px solid #dce5f3 !important;
    z-index: 1000 !important;
}

.sidebar::before,
.sidebar::after {
    display: none !important;
    content: none !important;
}

.sidebar-logo {
    position: static !important;
    display: flex !important;
    flex: 0 0 125px !important;
    width: 100% !important;
    height: 125px !important;
    min-height: 125px !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: flex-start !important;
    text-align: center !important;
    padding: 20px 8px 8px !important;
    margin: 0 !important;
    box-sizing: border-box !important;
    overflow: hidden !important;
}

.sidebar-logo img {
    position: static !important;
    display: block !important;
    flex: 0 0 auto !important;
    width: 55px !important;
    height: 55px !important;
    max-width: 55px !important;
    max-height: 55px !important;
    margin: 0 auto 5px !important;
    padding: 0 !important;
    object-fit: contain !important;
}

.sidebar-logo h2 {
    display: block !important;
    position: static !important;
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    font-size: 15px !important;
    line-height: 18px !important;
    font-weight: 700 !important;
    text-align: center !important;
    white-space: nowrap !important;
    color: #17325f !important;
}

.sidebar-logo p {
    display: block !important;
    position: static !important;
    width: 100% !important;
    margin: 2px 0 0 !important;
    padding: 0 !important;
    font-size: 8px !important;
    line-height: 11px !important;
    text-align: center !important;
    white-space: nowrap !important;
    color: #6f7f99 !important;
}

.sidebar-menu {
    position: static !important;
    display: flex !important;
    flex: 0 0 auto !important;
    width: 100% !important;
    height: auto !important;
    flex-direction: column !important;
    align-items: stretch !important;
    padding: 5px 8px 0 !important;
    margin: 0 !important;
    box-sizing: border-box !important;
}

.sidebar-menu .menu-item {
    position: static !important;
    display: flex !important;
    width: 100% !important;
    min-height: 36px !important;
    height: 36px !important;
    align-items: center !important;
    gap: 10px !important;
    padding: 0 11px !important;
    margin: 0 0 3px !important;
    border-radius: 7px !important;
    box-sizing: border-box !important;
    white-space: nowrap !important;
}

.sidebar-menu .menu-item i {
    position: static !important;
    display: inline-flex !important;
    width: 14px !important;
    min-width: 14px !important;
    height: 14px !important;
    align-items: center !important;
    justify-content: center !important;
    margin: 0 !important;
    font-size: 14px !important;
}

.sidebar-menu .menu-item span {
    display: inline-block !important;
    position: static !important;
    margin: 0 !important;
    padding: 0 !important;
    line-height: 18px !important;
}

.sidebar-bottom {
    position: absolute !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 18px !important;
    width: 100% !important;
    padding: 0 8px !important;
    box-sizing: border-box !important;
}

.main-content {
    margin-left: 170px !important;
    width: calc(100% - 170px) !important;
    min-height: 100vh !important;
}

.dashboard-layout {
    display: block !important;
    min-height: 100vh !important;
}


</style>


</head>


<body>


<div class="dashboard-layout">


    <!-- =========================================================
         SIDEBAR
    ========================================================= -->

    <aside class="sidebar">


        <!-- LOGO -->

        <div class="sidebar-logo">

            <img
                src="/SIPM-ODGJ/assets/img/logo YCKA.png"
                alt="Logo Yayasan Cahaya Kasih Amanah"
            >

            <h2>
                SIPM ODGJ
            </h2>

            <p>
                Yayasan Cahaya Kasih Amanah
            </p>

        </div>


        <!-- MENU -->

        <nav class="sidebar-menu">


            <a
                href="dashboard.php"
                class="menu-item"
            >
                <i class="bi bi-grid"></i>

                <span>
                    Dashboard
                </span>
            </a>


            <a
                href="data_pasien.php"
                class="menu-item active"
            >
                <i class="bi bi-people"></i>

                <span>
                    Data Pasien
                </span>
            </a>


            <a
                href="data_user.php"
                class="menu-item"
            >
                <i class="bi bi-person-gear"></i>

                <span>
                    Data User
                </span>
            </a>


            <a
                href="monitoring.php"
                class="menu-item"
            >
                <i class="bi bi-clipboard2-pulse"></i>

                <span>
                    Monitoring
                </span>
            </a>


            <a
                href="laporan.php"
                class="menu-item"
            >
                <i class="bi bi-file-earmark-bar-graph"></i>

                <span>
                    Laporan
                </span>
            </a>


        </nav>


        <!-- LOGOUT -->

        <div class="sidebar-bottom">

            <a
                href="logout.php"
                class="logout-button"
            >

                <i class="bi bi-box-arrow-left"></i>

                <span>
                    Logout
                </span>

            </a>

        </div>


    </aside>



    <!-- =========================================================
         MAIN CONTENT
    ========================================================= -->

    <main class="main-content">


        <!-- =====================================================
             TOPBAR
        ===================================================== -->

        <header class="topbar">


            <h1>
                Tambah Data Keluarga
            </h1>


            <div class="topbar-right">


                <!-- SEARCH -->

                <div class="search-box">

                    <i class="bi bi-search"></i>

                    <input
                        type="text"
                        placeholder="Search..."
                    >

                </div>


                <!-- NOTIFICATION -->

                <i
                    class="bi bi-bell notification-icon"
                ></i>


                <!-- PROFILE -->

                <div class="profile">

                    <strong>
                        Selamat Datang,
                        <?= e(
                            $_SESSION['nama_lengkap']
                            ?? 'Admin'
                        ); ?>
                    </strong>

                    <small>
                        Admin Profile
                    </small>

                </div>


                <!-- AVATAR -->

                <div class="profile-avatar">
                    A
                </div>


            </div>

        </header>



        <!-- =====================================================
             CONTENT
        ===================================================== -->

        <div class="page-content">


            <!-- PAGE HEADER -->

            <div class="page-header">


                <div class="page-title">

                    <h2>
                        Tambah Data Keluarga
                    </h2>

                    <p>
                        Tambahkan data keluarga atau wali
                        yang terhubung dengan pasien
                    </p>

                </div>


                <a
                    href="<?= $id_pasien_url > 0
                        ? 'detail_pasien.php?id=' . $id_pasien_url
                        : 'data_pasien.php';
                    ?>"
                    class="back-button"
                >

                    <i class="bi bi-arrow-left"></i>

                    Kembali

                </a>


            </div>



            <!-- ERROR -->

            <?php if ($error !== ''): ?>

                <div class="error-message">

                    <i class="bi bi-exclamation-circle"></i>

                    <span>
                        <?= e($error); ?>
                    </span>

                </div>

            <?php endif; ?>



            <!-- =================================================
                 FORM CARD
            ================================================= -->

            <div class="family-form-card">


                <!-- CARD HEADER -->

                <div class="family-card-header">

                    <div class="family-card-icon">

                        <i class="bi bi-people"></i>

                    </div>


                    <div>

                        <h3>
                            Data Keluarga / Wali
                        </h3>

                        <p>
                            Lengkapi informasi keluarga
                            yang terhubung dengan pasien.
                        </p>

                    </div>

                </div>



                <!-- FORM -->

                <form
                    method="POST"
                    action=""
                >


                    <div class="family-form-grid">


                        <!-- =================================================
                             AKUN KELUARGA
                        ================================================= -->

                        <div class="family-form-group">

                            <label for="id_user">

                                Pilih Akun Keluarga

                                <span class="required">
                                    *
                                </span>

                            </label>


                            <select
                                id="id_user"
                                name="id_user"
                                required
                            >

                                <option value="">
                                    Pilih akun keluarga
                                </option>


                                <?php while (
                                    $user =
                                    mysqli_fetch_assoc(
                                        $result_user
                                    )
                                ): ?>

                                    <option
                                        value="<?= $user['id_user']; ?>"
                                        <?= $id_user_form ==
                                            $user['id_user']
                                                ? 'selected'
                                                : '';
                                        ?>
                                    >

                                        <?= e(
                                            $user['nama_lengkap']
                                        ); ?>

                                        -

                                        <?= e(
                                            $user['username']
                                        ); ?>

                                    </option>

                                <?php endwhile; ?>


                            </select>

                        </div>



                        <!-- =================================================
                             PASIEN
                        ================================================= -->

                        <div class="family-form-group">

                            <label for="id_pasien">

                                Pilih Pasien

                                <span class="required">
                                    *
                                </span>

                            </label>


                            <select
                                id="id_pasien"
                                name="id_pasien"
                                required
                            >

                                <option value="">
                                    Pilih pasien
                                </option>


                                <?php while (
                                    $pasien =
                                    mysqli_fetch_assoc(
                                        $result_pasien
                                    )
                                ): ?>

                                    <option
                                        value="<?= $pasien['id_pasien']; ?>"
                                        <?= $id_pasien_form ==
                                            $pasien['id_pasien']
                                                ? 'selected'
                                                : '';
                                        ?>
                                    >

                                        <?= e(
                                            $pasien['nama_pasien']
                                        ); ?>

                                        -

                                        <?= e(
                                            $pasien['nomor_registrasi']
                                        ); ?>

                                    </option>

                                <?php endwhile; ?>


                            </select>

                        </div>



                        <!-- =================================================
                             NAMA KELUARGA
                        ================================================= -->

                        <div
                            class="family-form-group full-width"
                        >

                            <label for="nama_keluarga">

                                Nama Keluarga

                                <span class="required">
                                    *
                                </span>

                            </label>


                            <input
                                type="text"
                                id="nama_keluarga"
                                name="nama_keluarga"
                                placeholder="Masukkan nama keluarga"
                                value="<?= e(
                                    $nama_keluarga_form
                                ); ?>"
                                required
                            >

                        </div>



                        <!-- =================================================
                             HUBUNGAN
                        ================================================= -->

                        <div class="family-form-group">

                            <label for="hubungan">

                                Hubungan dengan Pasien

                                <span class="required">
                                    *
                                </span>

                            </label>


                            <select
                                id="hubungan"
                                name="hubungan"
                                required
                            >

                                <option value="">
                                    Pilih hubungan
                                </option>


                                <option
                                    value="Ayah"
                                    <?= $hubungan_form === 'Ayah'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    Ayah
                                </option>


                                <option
                                    value="Ibu"
                                    <?= $hubungan_form === 'Ibu'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    Ibu
                                </option>


                                <option
                                    value="Suami"
                                    <?= $hubungan_form === 'Suami'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    Suami
                                </option>


                                <option
                                    value="Istri"
                                    <?= $hubungan_form === 'Istri'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    Istri
                                </option>


                                <option
                                    value="Anak"
                                    <?= $hubungan_form === 'Anak'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    Anak
                                </option>


                                <option
                                    value="Kakak"
                                    <?= $hubungan_form === 'Kakak'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    Kakak
                                </option>


                                <option
                                    value="Adik"
                                    <?= $hubungan_form === 'Adik'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    Adik
                                </option>


                                <option
                                    value="Saudara"
                                    <?= $hubungan_form === 'Saudara'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    Saudara
                                </option>


                                <option
                                    value="Wali"
                                    <?= $hubungan_form === 'Wali'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    Wali
                                </option>


                                <option
                                    value="Lainnya"
                                    <?= $hubungan_form === 'Lainnya'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    Lainnya
                                </option>


                            </select>

                        </div>



                        <!-- =================================================
                             NOMOR TELEPON
                        ================================================= -->

                        <div class="family-form-group">

                            <label for="nomor_telepon">

                                Nomor Telepon

                                <span class="required">
                                    *
                                </span>

                            </label>


                            <input
                                type="text"
                                id="nomor_telepon"
                                name="nomor_telepon"
                                placeholder="Masukkan nomor telepon"
                                value="<?= e(
                                    $nomor_telepon_form
                                ); ?>"
                                required
                            >

                        </div>



                        <!-- =================================================
                             ALAMAT
                        ================================================= -->

                        <div
                            class="family-form-group full-width"
                        >

                            <label for="alamat">

                                Alamat

                                <span class="required">
                                    *
                                </span>

                            </label>


                            <textarea
                                id="alamat"
                                name="alamat"
                                placeholder="Masukkan alamat keluarga"
                                required
                            ><?= e(
                                $alamat_form
                            ); ?></textarea>

                        </div>


                    </div>



                    <!-- =================================================
                         BUTTON
                    ================================================= -->

                    <div class="family-form-footer">


                        <a
                            href="<?= $id_pasien_form > 0
                                ? 'detail_pasien.php?id=' .
                                  $id_pasien_form
                                : 'data_pasien.php';
                            ?>"
                            class="family-btn family-btn-cancel"
                        >

                            <i class="bi bi-x-lg"></i>

                            Batal

                        </a>


                        <button
                            type="submit"
                            class="family-btn family-btn-save"
                        >

                            <i class="bi bi-check-lg"></i>

                            Simpan Data Keluarga

                        </button>


                    </div>


                </form>


            </div>


        </div>


    </main>


</div>


</body>

</html>