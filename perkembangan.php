<?php
session_start();
require_once 'koneksi.php';

/* =========================================================
   CEK LOGIN KELUARGA
========================================================= */

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

if (($_SESSION['role'] ?? '') !== 'keluarga') {
    die("Anda tidak memiliki akses ke halaman ini.");
}

$id_user = (int) $_SESSION['id_user'];

function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}


/* =========================================================
   DATA USER
========================================================= */

$stmt_user = $conn->prepare("
    SELECT
        id_user,
        username,
        nama_lengkap
    FROM users
    WHERE id_user = ?
    LIMIT 1
");

if (!$stmt_user) {
    die("Query user gagal: " . $conn->error);
}

$stmt_user->bind_param("i", $id_user);
$stmt_user->execute();

$user = $stmt_user->get_result()->fetch_assoc();

$stmt_user->close();

if (!$user) {
    die("Data pengguna tidak ditemukan.");
}

$nama_user = $user['nama_lengkap'] ?? 'Keluarga';


/* =========================================================
   DATA PASIEN YANG TERHUBUNG DENGAN AKUN KELUARGA
========================================================= */

$stmt_pasien = $conn->prepare("
    SELECT
        p.*
    FROM keluarga k
    INNER JOIN pasien p
        ON k.id_pasien = p.id_pasien
    WHERE k.id_user = ?
    LIMIT 1
");

if (!$stmt_pasien) {
    die("Query pasien gagal: " . $conn->error);
}

$stmt_pasien->bind_param("i", $id_user);
$stmt_pasien->execute();

$pasien = $stmt_pasien->get_result()->fetch_assoc();

$stmt_pasien->close();

if (!$pasien) {
    die("Belum ada pasien yang terhubung dengan akun keluarga.");
}


/* =========================================================
   ID PASIEN
========================================================= */

$id_pasien = (int) $pasien['id_pasien'];


/* =========================================================
   DATA MONITORING PASIEN
========================================================= */

$monitoring = [];

$stmt_monitoring = $conn->prepare("
    SELECT
        tanggal_monitoring,
        berat_badan,
        aktivitas_harian,
        perilaku,
        catatan
    FROM monitoring
    WHERE id_pasien = ?
    ORDER BY tanggal_monitoring DESC
");

if (!$stmt_monitoring) {
    die("Query monitoring gagal: " . $conn->error);
}

$stmt_monitoring->bind_param("i", $id_pasien);
$stmt_monitoring->execute();

$result_monitoring = $stmt_monitoring->get_result();

while ($row = $result_monitoring->fetch_assoc()) {
    $monitoring[] = $row;
}

$stmt_monitoring->close();


/* =========================================================
   DATA MONITORING TERBARU
========================================================= */

$terbaru = $monitoring[0] ?? null;

$beratTerbaru =
    $terbaru['berat_badan'] ?? '-';

$aktivitasTerbaru =
    $terbaru['aktivitas_harian'] ?? '-';

$perilakuTerbaru =
    $terbaru['perilaku'] ?? '-';


/* =========================================================
   INISIAL PROFIL
========================================================= */

$inisial = strtoupper(
    substr(
        trim($nama_user),
        0,
        1
    )
);

if ($inisial === '') {
    $inisial = 'K';
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

    <title>Perkembangan - SIPM ODGJ</title>


    <!-- GOOGLE FONT -->
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <!-- FONT AWESOME -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    >


    <!-- BOOTSTRAP ICONS -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


<style>

/* =========================================================
   RESET
========================================================= */

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}


/* =========================================================
   BODY
========================================================= */

body {
    font-family: 'Poppins', sans-serif;
    background: #f5f8fd;
    color: #10213f;
    font-size: 13px;
}


/* =========================================================
   SIDEBAR
========================================================= */

.sidebar {
    position: fixed;
    left: 0;
    top: 0;

    width: 150px;
    height: 100vh;

    background: #edf4ff;
    border-right: 1px solid #d8e3f3;

    display: flex;
    flex-direction: column;

    z-index: 100;
}


.sidebar-logo {
    height: 115px;

    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;

    padding-top: 7px;
}


.sidebar-logo img {
    width: 48px;
    height: 48px;

    object-fit: contain;

    margin-bottom: 3px;
}


.sidebar-logo .brand {
    color: #0755d8;

    font-size: 14px;
    font-weight: 700;

    line-height: 1.2;
}


.sidebar-logo .subtitle {
    color: #7c8ca6;

    font-size: 7px;

    margin-top: 3px;

    text-align: center;
}


/* =========================================================
   MENU
========================================================= */

.menu {
    padding: 7px 8px;
}


.menu a {
    height: 37px;

    display: flex;
    align-items: center;

    gap: 10px;

    padding: 0 11px;

    margin-bottom: 3px;

    border-radius: 8px;

    color: #243b5b;

    text-decoration: none;

    font-size: 11px;

    transition: .2s;
}


.menu a i {
    width: 14px;

    text-align: center;

    font-size: 13px;
}


.menu a:hover {
    background: #e0ecff;
    color: #155bd7;
}


.menu a.active {
    background: #d9e8ff;
    color: #155bd7;

    font-weight: 600;
}


/* =========================================================
   LOGOUT
========================================================= */

.logout {
    margin-top: auto;

    padding: 0 8px 16px;
}


.logout a {
    height: 32px;

    display: flex;
    align-items: center;

    gap: 9px;

    padding: 0 11px;

    color: #f04444;

    text-decoration: none;

    font-size: 10px;
}


/* =========================================================
   MAIN
========================================================= */

.main {
    margin-left: 150px;

    min-height: 100vh;
}


/* =========================================================
   TOP HEADER
========================================================= */

.top-header {
    height: 56px;

    background: #ffffff;

    border-top: none;
    border-bottom: 1px solid #dfe7f1;

    display: flex;
    align-items: center;
    justify-content: space-between;

    padding: 0 22px;
}


.page-title-top {
    color: #145bd7;

    font-size: 16px;
    font-weight: 700;
}


/* =========================================================
   TOP RIGHT
========================================================= */

.top-right {
    display: flex;
    align-items: center;
}


/* =========================================================
   PROFILE BUTTON
========================================================= */

.user-profile {
    position: relative;

    display: flex;
    align-items: center;
}


.profile-button {
    display: flex;
    align-items: center;

    gap: 8px;

    border: 0;

    background: transparent;

    padding: 5px 3px;

    cursor: pointer;

    font-family: 'Poppins', sans-serif;

    border-radius: 8px;

    transition: .2s;
}


.profile-button:hover {
    background: #f4f7fb;
}


/* =========================================================
   PROFILE AVATAR BIRU
========================================================= */

.profile-avatar-small {
    width: 38px;
    height: 38px;

    min-width: 38px;

    border-radius: 50%;

    background: #2864e6;

    color: #ffffff;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 15px;

    font-weight: 600;
}


/* =========================================================
   PROFILE TEXT
========================================================= */

.profile-user-text {
    display: flex;

    flex-direction: column;

    align-items: flex-start;

    justify-content: center;

    line-height: 1.2;
}


.profile-user-name {
    font-size: 10px;

    font-weight: 600;

    color: #172033;
}


.profile-user-role {
    font-size: 8px;

    color: #7b8ba2;

    margin-top: 2px;
}


/* =========================================================
   PROFILE ARROW
========================================================= */

.profile-arrow {
    font-size: 9px;

    color: #7b8ba2;

    margin-left: 2px;

    transition: .2s;
}


.profile-button.active .profile-arrow {
    transform: rotate(180deg);
}


/* =========================================================
   PROFILE DROPDOWN
========================================================= */

.profile-dropdown {
    position: absolute;

    right: 0;

    top: calc(100% + 9px);

    width: 215px;

    background: #ffffff;

    border: 1px solid #e1e8f2;

    border-radius: 11px;

    box-shadow: 0 10px 28px rgba(23,33,51,.12);

    overflow: hidden;

    opacity: 0;

    visibility: hidden;

    transform: translateY(-5px);

    transition: .2s;

    z-index: 3000;
}


.profile-dropdown.show {
    opacity: 1;

    visibility: visible;

    transform: translateY(0);
}


.profile-dropdown-header {
    display: flex;

    align-items: center;

    gap: 10px;

    padding: 14px;
}


/* Avatar dropdown juga biru */

.profile-avatar {
    width: 36px;
    height: 36px;

    min-width: 36px;

    border-radius: 50%;

    background: #2864e6;

    color: #ffffff;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 14px;

    font-weight: 600;
}


.profile-info {
    display: flex;

    flex-direction: column;

    min-width: 0;
}


.profile-info strong {
    font-size: 10px;

    color: #172033;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;
}


.profile-info span {
    font-size: 8px;

    color: #8a95a8;

    margin-top: 2px;
}


.profile-divider {
    height: 1px;

    background: #edf1f5;
}


.profile-menu-item {
    width: 100%;

    display: flex;

    align-items: center;

    gap: 9px;

    padding: 10px 14px;

    border: 0;

    background: #ffffff;

    color: #394b63;

    text-decoration: none;

    font: 500 9px 'Poppins', sans-serif;

    text-align: left;

    cursor: pointer;
}


.profile-menu-item:hover {
    background: #f7faff;

    color: #2864e6;
}


.profile-menu-item i {
    width: 15px;

    text-align: center;

    font-size: 13px;
}


.profile-menu-item.logout-item {
    color: #dc3545;
}


.profile-menu-item.logout-item:hover {
    background: #fff5f5;

    color: #dc3545;
}


/* =========================================================
   CONTENT
========================================================= */

.content {
    padding: 25px 20px 45px;
}


/* =========================================================
   HEADING
========================================================= */

.heading {
    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    margin-bottom: 20px;
}


.heading h1 {
    font-size: 22px;

    font-weight: 700;

    color: #0d1f3c;

    margin-bottom: 4px;
}


.heading p {
    font-size: 10px;

    color: #7890ad;
}


/* =========================================================
   BACK BUTTON
========================================================= */

.back-btn {
    height: 32px;

    padding: 0 13px;

    display: flex;

    align-items: center;

    gap: 7px;

    background: #ffffff;

    border: 1px solid #d6e2f1;

    border-radius: 8px;

    color: #55708f;

    text-decoration: none;

    font-size: 10px;
}


.back-btn:hover {
    border-color: #2864e6;

    color: #2864e6;
}


/* =========================================================
   PATIENT CARD
========================================================= */

.patient-card {
    background: #ffffff;

    border: 1px solid #dce6f2;

    border-radius: 9px;

    min-height: 83px;

    padding: 17px 20px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 16px;
}


.patient-info {
    display: flex;

    align-items: center;

    gap: 13px;
}


.patient-avatar {
    width: 44px;
    height: 44px;

    border-radius: 50%;

    background: #e5efff;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #2864e6;

    font-size: 20px;

    font-weight: 600;
}


.patient-name {
    font-size: 15px;

    font-weight: 700;

    color: #142744;
}


.patient-reg {
    font-size: 9px;

    color: #8495ad;

    margin-top: 2px;
}


.badges {
    display: flex;

    align-items: center;

    gap: 7px;
}


.badge {
    padding: 5px 10px;

    border-radius: 14px;

    font-size: 8px;

    font-weight: 500;
}


.badge-blue {
    background: #e5efff;

    color: #2864e6;
}


.badge-green {
    background: #dcf8e9;

    color: #19945a;
}


/* =========================================================
   SUMMARY CARDS
========================================================= */

.summary-grid {
    display: grid;

    grid-template-columns: repeat(3, 1fr);

    gap: 14px;

    margin-bottom: 16px;
}


.summary-card {
    background: #ffffff;

    border: 1px solid #dce6f2;

    border-radius: 9px;

    min-height: 92px;

    padding: 16px 17px;

    display: flex;

    align-items: center;

    gap: 13px;
}


.summary-icon {
    width: 40px;
    height: 40px;

    border-radius: 9px;

    background: #edf4ff;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #2864e6;

    font-size: 16px;

    flex-shrink: 0;
}


.summary-label {
    color: #8194ad;

    font-size: 9px;

    margin-bottom: 4px;
}


.summary-value {
    color: #10213f;

    font-size: 14px;

    font-weight: 700;

    max-width: 300px;

    overflow: hidden;

    text-overflow: ellipsis;

    white-space: nowrap;
}


.summary-small {
    color: #9aaabd;

    font-size: 8px;

    margin-top: 2px;
}


/* =========================================================
   CARD GENERAL
========================================================= */

.card {
    background: #ffffff;

    border: 1px solid #dce6f2;

    border-radius: 9px;

    margin-bottom: 16px;

    overflow: hidden;
}


.card-header {
    min-height: 61px;

    display: flex;

    align-items: center;

    gap: 11px;

    padding: 13px 17px;

    border-bottom: 1px solid #e2eaf4;
}


.card-header-icon {
    width: 32px;
    height: 32px;

    border-radius: 8px;

    background: #edf4ff;

    color: #2864e6;

    display: flex;

    align-items: center;

    justify-content: center;

    flex-shrink: 0;
}


.card-header h2 {
    font-size: 12px;

    font-weight: 700;

    color: #152844;
}


.card-header p {
    font-size: 8px;

    color: #8da0b7;

    margin-top: 2px;
}


/* =========================================================
   TABLE
========================================================= */

.table-wrapper {
    width: 100%;

    overflow-x: auto;
}


table {
    width: 100%;

    border-collapse: collapse;
}


th {
    background: #f7faff;

    color: #6d829e;

    font-size: 8px;

    font-weight: 600;

    text-align: left;

    padding: 11px 14px;

    border-bottom: 1px solid #e1e9f3;

    white-space: nowrap;
}


td {
    color: #334c6c;

    font-size: 9px;

    padding: 12px 14px;

    border-bottom: 1px solid #e7edf5;

    vertical-align: top;
}


tr:last-child td {
    border-bottom: none;
}


td:first-child {
    color: #162b49;

    font-weight: 600;
}


.empty {
    padding: 40px 20px;

    text-align: center;

    color: #91a1b6;

    font-size: 10px;
}


.empty i {
    font-size: 25px;

    color: #a8bad1;

    margin-bottom: 8px;
}


/* =========================================================
   PROFILE MODAL
========================================================= */

.profile-modal {
    position: fixed;

    inset: 0;

    background: rgba(15,23,42,.35);

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 20px;

    opacity: 0;

    visibility: hidden;

    transition: .2s;

    z-index: 5000;
}


.profile-modal.show {
    opacity: 1;

    visibility: visible;
}


.profile-modal-card {
    width: 100%;

    max-width: 360px;

    background: #ffffff;

    border-radius: 13px;

    box-shadow: 0 18px 45px rgba(0,0,0,.16);

    overflow: hidden;
}


.profile-modal-top {
    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 15px 17px;

    border-bottom: 1px solid #edf1f5;
}


.profile-modal-top h3 {
    margin: 0;

    font-size: 13px;

    color: #17243a;
}


.profile-close {
    width: 28px;
    height: 28px;

    border: 0;

    border-radius: 7px;

    background: #f5f7fb;

    color: #69768c;

    display: flex;

    align-items: center;

    justify-content: center;

    cursor: pointer;
}


.profile-modal-body {
    padding: 20px 18px;
}


.profile-modal-avatar {
    width: 58px;
    height: 58px;

    margin: 0 auto 12px;

    border-radius: 50%;

    background: #2864e6;

    color: #ffffff;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 25px;

    font-weight: 600;
}


.profile-modal-name {
    text-align: center;

    font-size: 15px;

    font-weight: 700;

    color: #17243a;
}


.profile-modal-role {
    text-align: center;

    font-size: 9px;

    color: #8a95a8;

    margin: 3px 0 17px;
}


.profile-detail {
    border: 1px solid #e6ebf2;

    border-radius: 9px;

    overflow: hidden;
}


.profile-detail-row {
    display: flex;

    justify-content: space-between;

    gap: 15px;

    padding: 10px 12px;

    border-bottom: 1px solid #edf1f5;
}


.profile-detail-row:last-child {
    border-bottom: 0;
}


.profile-detail-label {
    font-size: 8px;

    color: #8a95a8;
}


.profile-detail-value {
    font-size: 9px;

    color: #17243a;

    font-weight: 600;

    text-align: right;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 900px) {

    .summary-grid {
        grid-template-columns: 1fr;
    }


    .patient-card {
        align-items: flex-start;

        gap: 15px;

        flex-direction: column;
    }


    .badges {
        align-self: flex-start;
    }

}


@media (max-width: 650px) {

    .sidebar {
        width: 125px;
    }


    .main {
        margin-left: 125px;
    }


    .top-header {
        padding: 0 12px;
    }


    .content {
        padding: 18px 12px;
    }


    .heading h1 {
        font-size: 19px;
    }

}

</style>

</head>


<body>


<!-- =========================================================
     SIDEBAR
========================================================= -->

<aside class="sidebar">

    <div class="sidebar-logo">

        <img
            src="/SIPM-ODGJ/assets/img/logo YCKA.png"
            alt="Logo Yayasan"
        >

        <div class="brand">
            SIPM ODGJ
        </div>

        <div class="subtitle">
            Yayasan Cahaya Kasih Amanah
        </div>

    </div>


    <nav class="menu">

        <a href="dashboard_keluarga.php">

            <i class="fa-solid fa-border-all"></i>

            <span>
                Dashboard
            </span>

        </a>


        <a href="profil_pasien.php">

            <i class="fa-regular fa-id-card"></i>

            <span>
                Profil Pasien
            </span>

        </a>


        <a
            href="perkembangan.php"
            class="active"
        >

            <i class="fa-solid fa-chart-line"></i>

            <span>
                Perkembangan
            </span>

        </a>

    </nav>


    <div class="logout">

        <a href="logout.php">

            <i class="fa-solid fa-right-from-bracket"></i>

            <span>
                Logout
            </span>

        </a>

    </div>

</aside>


<!-- =========================================================
     MAIN
========================================================= -->

<main class="main">


    <!-- =====================================================
         TOP HEADER
    ===================================================== -->

    <header class="top-header">

        <div class="page-title-top">
            Perkembangan
        </div>


        <!-- PROFIL -->
        <!-- SEARCH SUDAH DIHAPUS -->

        <div class="top-right">

            <div
                class="user-profile"
                id="userProfile"
            >

                <button
                    type="button"
                    class="profile-button"
                    id="profileButton"
                >

                    <!-- LINGKARAN BIRU -->
                    <div class="profile-avatar-small">
                        <?= e($inisial); ?>
                    </div>


                    <!-- NAMA + ROLE -->
                    <div class="profile-user-text">

                        <span class="profile-user-name">
                            <?= e($nama_user); ?>
                        </span>

                        <span class="profile-user-role">
                            Keluarga
                        </span>

                    </div>


                    <!-- PANAH -->
                    <i class="bi bi-chevron-down profile-arrow"></i>

                </button>


                <!-- DROPDOWN PROFIL -->

                <div
                    class="profile-dropdown"
                    id="profileDropdown"
                >

                    <div class="profile-dropdown-header">

                        <div class="profile-avatar">
                            <?= e($inisial); ?>
                        </div>


                        <div class="profile-info">

                            <strong>
                                <?= e($nama_user); ?>
                            </strong>

                            <span>
                                Keluarga
                            </span>

                        </div>

                    </div>


                    <div class="profile-divider"></div>


                    <button
                        type="button"
                        class="profile-menu-item"
                        id="profileSaya"
                    >

                        <i class="bi bi-person"></i>

                        <span>
                            Profil Saya
                        </span>

                    </button>


                    <a
                        href="logout.php"
                        class="profile-menu-item logout-item"
                    >

                        <i class="bi bi-box-arrow-left"></i>

                        <span>
                            Logout
                        </span>

                    </a>

                </div>

            </div>

        </div>

    </header>


    <!-- =====================================================
         CONTENT
    ===================================================== -->

    <section class="content">


        <!-- HEADING -->

        <div class="heading">

            <div>

                <h1>
                    Perkembangan Pasien
                </h1>

                <p>
                    Pantau perkembangan dan kondisi pasien dari waktu ke waktu.
                </p>

            </div>


            <a
                href="dashboard_keluarga.php"
                class="back-btn"
            >

                <i class="fa-solid fa-arrow-left"></i>

                Kembali

            </a>

        </div>


        <!-- =================================================
             PATIENT
        ================================================= -->

        <div class="patient-card">


            <div class="patient-info">


                <div class="patient-avatar">

                    <?= strtoupper(
                        substr(
                            $pasien['nama_pasien'],
                            0,
                            1
                        )
                    ); ?>

                </div>


                <div>

                    <div class="patient-name">

                        <?= htmlspecialchars(
                            $pasien['nama_pasien']
                        ); ?>

                    </div>


                    <div class="patient-reg">

                        No. Registrasi:

                        <?= htmlspecialchars(
                            $pasien['nomor_registrasi']
                        ); ?>

                    </div>

                </div>

            </div>


            <div class="badges">


                <span class="badge badge-blue">

                    <?= htmlspecialchars(
                        $pasien['lokasi_pasien']
                        ?? 'Dalam Yayasan'
                    ); ?>

                </span>


                <span class="badge badge-green">

                    <?= htmlspecialchars(
                        $pasien['kondisi']
                        ?? 'Stabil'
                    ); ?>

                </span>


                <span class="badge badge-green">

                    <?= htmlspecialchars(
                        $pasien['status']
                        ?? 'Aktif'
                    ); ?>

                </span>

            </div>

        </div>


        <!-- =================================================
             SUMMARY
        ================================================= -->

        <div class="summary-grid">


            <!-- BERAT -->

            <div class="summary-card">

                <div class="summary-icon">

                    <i class="fa-solid fa-weight-scale"></i>

                </div>


                <div>

                    <div class="summary-label">
                        Berat Badan Terbaru
                    </div>


                    <div class="summary-value">

                        <?= $beratTerbaru !== '-'
                            ? htmlspecialchars($beratTerbaru) . ' kg'
                            : '-';
                        ?>

                    </div>


                    <div class="summary-small">
                        Hasil monitoring terakhir
                    </div>

                </div>

            </div>


            <!-- AKTIVITAS -->

            <div class="summary-card">

                <div class="summary-icon">

                    <i class="fa-solid fa-person-running"></i>

                </div>


                <div>

                    <div class="summary-label">
                        Aktivitas Terakhir
                    </div>


                    <div
                        class="summary-value"
                        title="<?= htmlspecialchars($aktivitasTerbaru); ?>"
                    >

                        <?= htmlspecialchars(
                            $aktivitasTerbaru
                        ); ?>

                    </div>


                    <div class="summary-small">
                        Aktivitas harian pasien
                    </div>

                </div>

            </div>


            <!-- PERILAKU -->

            <div class="summary-card">

                <div class="summary-icon">

                    <i class="fa-solid fa-heart-pulse"></i>

                </div>


                <div>

                    <div class="summary-label">
                        Perilaku Terakhir
                    </div>


                    <div
                        class="summary-value"
                        title="<?= htmlspecialchars($perilakuTerbaru); ?>"
                    >

                        <?= htmlspecialchars(
                            $perilakuTerbaru
                        ); ?>

                    </div>


                    <div class="summary-small">
                        Catatan monitoring terakhir
                    </div>

                </div>

            </div>

        </div>


        <!-- =================================================
             RIWAYAT
        ================================================= -->

        <div class="card">


            <div class="card-header">


                <div class="card-header-icon">

                    <i class="fa-regular fa-clock"></i>

                </div>


                <div>

                    <h2>
                        Riwayat Perkembangan
                    </h2>

                    <p>
                        Riwayat hasil monitoring pasien.
                    </p>

                </div>

            </div>


            <?php if (!empty($monitoring)): ?>


                <div class="table-wrapper">


                    <table>


                        <thead>

                            <tr>

                                <th>
                                    Tanggal Monitoring
                                </th>

                                <th>
                                    Berat Badan
                                </th>

                                <th>
                                    Aktivitas Harian
                                </th>

                                <th>
                                    Perilaku
                                </th>

                                <th>
                                    Catatan
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php foreach ($monitoring as $data): ?>


                                <tr>


                                    <td>

                                        <?= date(
                                            'd M Y, H:i',
                                            strtotime(
                                                $data['tanggal_monitoring']
                                            )
                                        ); ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $data['berat_badan']
                                        ); ?>

                                        kg

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $data['aktivitas_harian']
                                        ); ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $data['perilaku']
                                        ); ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $data['catatan']
                                        ); ?>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        </tbody>


                    </table>

                </div>


            <?php else: ?>


                <div class="empty">

                    <div>

                        <i class="fa-solid fa-database"></i>

                    </div>

                    Belum ada riwayat monitoring untuk pasien ini.

                </div>


            <?php endif; ?>


        </div>


    </section>

</main>


<!-- =========================================================
     MODAL PROFIL
========================================================= -->

<div
    class="profile-modal"
    id="profileModal"
>

    <div class="profile-modal-card">


        <div class="profile-modal-top">

            <h3>
                Profil Saya
            </h3>


            <button
                type="button"
                class="profile-close"
                id="closeProfile"
            >

                <i class="bi bi-x-lg"></i>

            </button>

        </div>


        <div class="profile-modal-body">


            <div class="profile-modal-avatar">

                <?= e($inisial); ?>

            </div>


            <div class="profile-modal-name">

                <?= e($nama_user); ?>

            </div>


            <div class="profile-modal-role">

                Keluarga

            </div>


            <div class="profile-detail">


                <div class="profile-detail-row">

                    <span class="profile-detail-label">
                        Nama Lengkap
                    </span>

                    <span class="profile-detail-value">
                        <?= e($nama_user); ?>
                    </span>

                </div>


                <div class="profile-detail-row">

                    <span class="profile-detail-label">
                        ID Pengguna
                    </span>

                    <span class="profile-detail-value">
                        <?= e((string)$id_user); ?>
                    </span>

                </div>


                <div class="profile-detail-row">

                    <span class="profile-detail-label">
                        Role
                    </span>

                    <span class="profile-detail-value">
                        Keluarga
                    </span>

                </div>


            </div>

        </div>

    </div>

</div>


<!-- =========================================================
     JAVASCRIPT PROFIL
========================================================= -->

<script>

document.addEventListener("DOMContentLoaded", function () {

    const btn =
        document.getElementById("profileButton");

    const dd =
        document.getElementById("profileDropdown");

    const wrap =
        document.getElementById("userProfile");

    const ps =
        document.getElementById("profileSaya");

    const modal =
        document.getElementById("profileModal");

    const close =
        document.getElementById("closeProfile");


    /* =====================================================
       BUKA / TUTUP DROPDOWN
    ===================================================== */

    btn?.addEventListener("click", function (e) {

        e.preventDefault();

        e.stopPropagation();

        dd?.classList.toggle("show");

        btn?.classList.toggle("active");

    });


    /* =====================================================
       KLIK DI LUAR DROPDOWN
    ===================================================== */

    document.addEventListener("click", function (e) {

        if (wrap && !wrap.contains(e.target)) {

            dd?.classList.remove("show");

            btn?.classList.remove("active");

        }

    });


    /* =====================================================
       PROFIL SAYA
    ===================================================== */

    ps?.addEventListener("click", function (e) {

        e.preventDefault();

        e.stopPropagation();

        dd?.classList.remove("show");

        btn?.classList.remove("active");

        modal?.classList.add("show");

    });


    /* =====================================================
       TUTUP MODAL
    ===================================================== */

    close?.addEventListener("click", function () {

        modal?.classList.remove("show");

    });


    /* =====================================================
       KLIK LUAR MODAL
    ===================================================== */

    modal?.addEventListener("click", function (e) {

        if (e.target === modal) {

            modal.classList.remove("show");

        }

    });


    /* =====================================================
       ESCAPE
    ===================================================== */

    document.addEventListener("keydown", function (e) {

        if (e.key === "Escape") {

            modal?.classList.remove("show");

            dd?.classList.remove("show");

            btn?.classList.remove("active");

        }

    });

});

</script>


</body>

</html>