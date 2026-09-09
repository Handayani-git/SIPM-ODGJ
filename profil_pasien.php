<?php
session_start();

/* =========================================================
   AKSES KHUSUS KELUARGA
========================================================= */
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

if (($_SESSION['role'] ?? '') !== 'keluarga') {
    die("Anda tidak memiliki akses ke halaman ini.");
}

require_once "koneksi.php";

$id_user   = (int) $_SESSION['id_user'];
$nama_user = $_SESSION['nama_lengkap'] ?? 'Keluarga';


/* =========================================================
   AMBIL DATA PASIEN YANG TERHUBUNG
   keluarga.id_user -> users.id_user
   keluarga.id_pasien -> pasien.id_pasien
========================================================= */

$query = "
    SELECT
        p.id_pasien,
        p.nomor_registrasi,
        p.nik,
        p.nama_pasien,
        p.jenis_kelamin,
        p.tempat_lahir,
        p.tanggal_lahir,
        p.alamat,
        p.status_lokasi,
        p.kondisi,
        p.tanggal_masuk,
        p.status_pasien
    FROM keluarga k
    INNER JOIN pasien p
        ON p.id_pasien = k.id_pasien
    WHERE k.id_user = ?
    ORDER BY p.nama_pasien ASC
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $query);

$pasien = null;

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $id_user);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $pasien = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);
}


/* =========================================================
   HELPER
========================================================= */

function e($value): string
{
    return htmlspecialchars(
        (string) ($value ?? '-'),
        ENT_QUOTES,
        'UTF-8'
    );
}

function formatTanggal($tanggal): string
{
    if (empty($tanggal)) {
        return '-';
    }

    return date(
        'd-m-Y',
        strtotime($tanggal)
    );
}


/* =========================================================
   BADGE CLASS
========================================================= */

$lokasi_class = 'badge-blue';

if (($pasien['status_lokasi'] ?? '') === 'Dalam Yayasan') {
    $lokasi_class = 'badge-blue';
} elseif (($pasien['status_lokasi'] ?? '') === 'Luar Yayasan') {
    $lokasi_class = 'badge-orange';
}


$kondisi_class = 'badge-green';

if (($pasien['kondisi'] ?? '') === 'Tidak Stabil') {
    $kondisi_class = 'badge-red';
} elseif (($pasien['kondisi'] ?? '') === 'Perlu Perhatian') {
    $kondisi_class = 'badge-orange';
}


$status_class = 'badge-green';

if (($pasien['status_pasien'] ?? '') !== 'Aktif') {
    $status_class = 'badge-gray';
}


/* =========================================================
   INISIAL PROFIL USER
========================================================= */

$nama_awal = strtoupper(
    substr(
        trim($nama_user),
        0,
        1
    )
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

    <title>Profil Pasien - SIPM ODGJ</title>

    <!-- BOOTSTRAP ICONS -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }


        body {
            font-family: "Poppins", Arial, sans-serif;
            background: #f5f8fc;
            color: #172033;
            font-size: 12px;
        }


        /* =====================================================
           LAYOUT
        ===================================================== */

        .dashboard-layout {
            min-height: 100vh;
        }


        /* =====================================================
           SIDEBAR
        ===================================================== */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;

            width: 150px;
            height: 100vh;

            background: #edf4ff;
            border-right: 1px solid #d9e4f2;

            display: flex;
            flex-direction: column;

            z-index: 100;
        }


        .sidebar-brand {
            text-align: center;
            padding: 18px 10px 16px;
        }


        .sidebar-logo {
            width: 48px;
            height: 48px;

            object-fit: contain;

            display: block;
            margin: 0 auto 7px;
        }


        .sidebar-brand h2 {
            font-size: 13px;
            font-weight: 700;
            color: #1556c0;
            margin-bottom: 2px;
        }


        .sidebar-brand p {
            font-size: 6.5px;
            color: #7a879b;
            line-height: 1.3;
        }


        .sidebar-menu {
            display: flex;
            flex-direction: column;

            padding: 10px 8px;

            gap: 3px;
        }


        .menu-item {
            display: flex;
            align-items: center;

            gap: 10px;

            min-height: 36px;

            padding: 0 12px;

            border-radius: 7px;

            color: #34445c;

            text-decoration: none;

            font-size: 9px;
            font-weight: 500;

            transition: 0.2s;
        }


        .menu-item i {
            width: 15px;

            font-size: 13px;

            text-align: center;
        }


        .menu-item:hover {
            background: #e1edff;
            color: #1556c0;
        }


        .menu-item.active {
            background: #d6e7ff;
            color: #145bd7;

            font-weight: 600;
        }


        .sidebar-bottom {
            margin-top: auto;

            padding: 10px 8px 18px;
        }


        .logout-button {
            display: flex;
            align-items: center;

            gap: 10px;

            min-height: 34px;

            padding: 0 12px;

            border-radius: 7px;

            text-decoration: none;

            color: #e34d4d;

            font-size: 9px;
        }


        .logout-button:hover {
            background: #fff0f0;
        }


        /* =====================================================
           MAIN
        ===================================================== */

        .main-content {
            margin-left: 150px;

            min-height: 100vh;
        }


        /* =====================================================
           TOPBAR
        ===================================================== */

        .topbar {
            height: 56px;

            background: #ffffff;

            border-bottom: 1px solid #dfe7f1;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 22px;
        }


        .topbar-title {
            color: #145bd7;

            font-size: 16px;
            font-weight: 700;
        }


        .topbar-right {
            display: flex;
            align-items: center;

            gap: 8px;
        }


        /* =====================================================
           PROFIL TOPBAR
           MODEL SESUAI GAMBAR
        ===================================================== */

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

            color: #172033;

            font-family: "Poppins", Arial, sans-serif;

            padding: 4px 5px;

            border-radius: 8px;

            cursor: pointer;

            transition: 0.2s;
        }


        .profile-button:hover {
            background: #f4f7fb;
        }


        /* LINGKARAN BIRU */
        .profile-top-avatar {
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


        /* NAMA + ROLE */
        .profile-top-text {
            display: flex;
            flex-direction: column;

            align-items: flex-start;

            line-height: 1.15;

            min-width: 45px;
        }


        .profile-top-name {
            font-size: 9px;

            font-weight: 600;

            color: #172033;
        }


        .profile-top-role {
            font-size: 8px;

            font-weight: 400;

            color: #7f8da2;

            margin-top: 2px;
        }


        .profile-arrow {
            font-size: 9px;

            color: #7b8ba2;

            margin-left: 2px;

            transition: transform 0.2s;
        }


        .profile-button.active .profile-arrow {
            transform: rotate(180deg);
        }


        /* =====================================================
           PROFIL DROPDOWN
        ===================================================== */

        .profile-dropdown {
            position: absolute;

            right: 0;

            top: calc(100% + 9px);

            width: 215px;

            background: #ffffff;

            border: 1px solid #e1e8f2;

            border-radius: 11px;

            box-shadow:
                0 10px 28px rgba(23, 33, 51, 0.12);

            overflow: hidden;

            opacity: 0;

            visibility: hidden;

            transform: translateY(-5px);

            transition: 0.2s;

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

            font:
                500 9px "Poppins",
                Arial,
                sans-serif;

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


        /* =====================================================
           PROFILE MODAL
        ===================================================== */

        .profile-modal {
            position: fixed;

            inset: 0;

            background: rgba(15, 23, 42, 0.35);

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 20px;

            opacity: 0;

            visibility: hidden;

            transition: 0.2s;

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

            box-shadow:
                0 18px 45px rgba(0, 0, 0, 0.16);

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

            font-size: 24px;

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


        /* =====================================================
           CONTENT
        ===================================================== */

        .content {
            padding: 27px 20px 45px;
        }


        .page-heading {
            display: flex;

            align-items: flex-start;

            justify-content: space-between;

            margin-bottom: 20px;
        }


        .page-heading h1 {
            font-size: 20px;

            font-weight: 700;

            color: #172033;

            margin-bottom: 4px;
        }


        .page-heading p {
            font-size: 9px;

            color: #8190a6;
        }


        .back-button {
            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding: 8px 12px;

            border: 1px solid #d8e2ee;

            border-radius: 7px;

            background: #ffffff;

            color: #53647c;

            text-decoration: none;

            font-size: 8px;
        }


        .back-button:hover {
            border-color: #2864e6;

            color: #2864e6;
        }


        /* =====================================================
           PATIENT HEADER
        ===================================================== */

        .patient-profile {
            background: #ffffff;

            border: 1px solid #dce6f2;

            border-radius: 9px;

            overflow: hidden;

            margin-bottom: 18px;
        }


        .patient-profile-header {
            min-height: 100px;

            padding: 20px 22px;

            display: flex;

            align-items: center;

            gap: 15px;

            border-bottom: 1px solid #edf1f6;
        }


        .patient-avatar {
            width: 58px;

            height: 58px;

            flex-shrink: 0;

            border-radius: 50%;

            background: #e5efff;

            color: #2864e6;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 22px;

            font-weight: 700;
        }


        .patient-profile-info {
            flex: 1;
        }


        .patient-profile-info h2 {
            font-size: 16px;

            color: #172033;

            margin-bottom: 4px;
        }


        .patient-profile-info p {
            font-size: 8px;

            color: #8290a5;
        }


        .patient-badges {
            display: flex;

            align-items: center;

            gap: 6px;

            flex-wrap: wrap;
        }


        /* =====================================================
           BADGES
        ===================================================== */

        .badge {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-height: 24px;

            padding: 4px 9px;

            border-radius: 13px;

            font-size: 8px;

            font-weight: 600;
        }


        .badge-blue {
            background: #e4efff;

            color: #2864d8;
        }


        .badge-green {
            background: #dcf7e8;

            color: #16944b;
        }


        .badge-orange {
            background: #fff0d8;

            color: #c98214;
        }


        .badge-red {
            background: #ffe3e3;

            color: #d13f3f;
        }


        .badge-gray {
            background: #edf0f4;

            color: #657184;
        }


        /* =====================================================
           CARD
        ===================================================== */

        .information-card {
            background: #ffffff;

            border: 1px solid #dce6f2;

            border-radius: 9px;

            margin-bottom: 18px;

            overflow: hidden;
        }


        .section-title {
            min-height: 62px;

            display: flex;

            align-items: center;

            gap: 10px;

            padding: 14px 17px;

            border-bottom: 1px solid #edf1f6;
        }


        .section-icon {
            width: 30px;

            height: 30px;

            flex-shrink: 0;

            border-radius: 7px;

            background: #edf4ff;

            color: #2864e6;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 14px;
        }


        .section-title h3 {
            font-size: 11px;

            font-weight: 700;

            color: #172033;
        }


        .section-title p {
            font-size: 7px;

            color: #8794a9;

            margin-top: 2px;
        }


        /* =====================================================
           INFORMATION GRID
        ===================================================== */

        .info-grid {
            display: grid;

            grid-template-columns: repeat(2, 1fr);
        }


        .info-item {
            min-height: 72px;

            padding: 15px 17px;

            border-bottom: 1px solid #edf1f6;
        }


        .info-item:nth-child(odd) {
            border-right: 1px solid #edf1f6;
        }


        .info-item.full-width {
            grid-column: 1 / -1;

            border-right: none;
        }


        .info-label {
            display: block;

            font-size: 8px;

            color: #8290a5;

            margin-bottom: 7px;
        }


        .info-value {
            display: block;

            color: #27364d;

            font-size: 10px;

            font-weight: 600;

            line-height: 1.5;
        }


        .address {
            font-weight: 500;

            white-space: normal;
        }


        /* =====================================================
           EMPTY
        ===================================================== */

        .empty-card {
            background: #ffffff;

            border: 1px solid #dce6f2;

            border-radius: 9px;

            padding: 60px 25px;

            text-align: center;
        }


        .empty-card i {
            font-size: 30px;

            color: #9aabc1;

            margin-bottom: 12px;
        }


        .empty-card h3 {
            font-size: 13px;

            margin-bottom: 6px;
        }


        .empty-card p {
            color: #8996aa;

            font-size: 9px;

            max-width: 480px;

            margin: auto;

            line-height: 1.6;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 850px) {

            .info-grid {
                grid-template-columns: 1fr;
            }

            .info-item:nth-child(odd) {
                border-right: none;
            }

            .patient-profile-header {
                align-items: flex-start;

                flex-direction: column;
            }

            .patient-badges {
                justify-content: flex-start;
            }

        }


        @media (max-width: 650px) {

            .sidebar {
                width: 65px;
            }


            .sidebar-brand h2,
            .sidebar-brand p,
            .menu-item span,
            .logout-button span {
                display: none;
            }


            .sidebar-brand {
                padding: 15px 5px;
            }


            .sidebar-logo {
                width: 43px;

                height: 43px;
            }


            .menu-item {
                justify-content: center;

                padding: 0;
            }


            .logout-button {
                justify-content: center;

                padding: 0;
            }


            .main-content {
                margin-left: 65px;
            }


            .content {
                padding: 20px 12px 30px;
            }


            .profile-top-text {
                display: none;
            }


            .profile-button {
                gap: 4px;
            }

        }

    </style>

</head>


<body>


<div class="dashboard-layout">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <aside class="sidebar">

        <div class="sidebar-brand">

            <img
                src="/SIPM-ODGJ/assets/img/logo YCKA.png"
                alt="Logo Yayasan Cahaya Kasih Amanah"
                class="sidebar-logo"
            >

            <h2>SIPM ODGJ</h2>

            <p>
                Yayasan Cahaya Kasih Amanah
            </p>

        </div>


        <nav class="sidebar-menu">

            <a
                href="dashboard_keluarga.php"
                class="menu-item"
            >
                <i class="bi bi-grid-1x2"></i>
                <span>Dashboard</span>
            </a>


            <a
                href="profil_pasien.php"
                class="menu-item active"
            >
                <i class="bi bi-person-vcard"></i>
                <span>Profil Pasien</span>
            </a>


            <a
                href="perkembangan.php"
                class="menu-item"
            >
                <i class="bi bi-graph-up-arrow"></i>
                <span>Perkembangan</span>
            </a>

        </nav>


        <div class="sidebar-bottom">

            <a
                href="logout.php"
                class="logout-button"
            >
                <i class="bi bi-box-arrow-left"></i>
                <span>Logout</span>
            </a>

        </div>

    </aside>



    <!-- =====================================================
         MAIN
    ====================================================== -->

    <main class="main-content">


        <!-- =================================================
             TOPBAR
        ================================================== -->

        <header class="topbar">

            <div class="topbar-title">
                Profil Pasien
            </div>


            <!-- TANPA SEARCH & TANPA NOTIFIKASI -->

            <div class="topbar-right">


                <!-- PROFIL -->

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

                        <div class="profile-top-avatar">
                            <?= e($nama_awal); ?>
                        </div>


                        <!-- NAMA + ROLE -->

                        <div class="profile-top-text">

                            <span class="profile-top-name">
                                <?= e($nama_user); ?>
                            </span>

                            <span class="profile-top-role">
                                Keluarga
                            </span>

                        </div>


                        <!-- CHEVRON -->

                        <i class="bi bi-chevron-down profile-arrow"></i>

                    </button>


                    <!-- DROPDOWN PROFIL -->

                    <div
                        class="profile-dropdown"
                        id="profileDropdown"
                    >

                        <div class="profile-dropdown-header">

                            <div class="profile-avatar">
                                <?= e($nama_awal); ?>
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



        <!-- =================================================
             CONTENT
        ================================================== -->

        <section class="content">


            <?php if (!$pasien): ?>


                <!-- DATA KOSONG -->

                <div class="page-heading">

                    <div>

                        <h1>
                            Profil Pasien
                        </h1>

                        <p>
                            Informasi lengkap pasien yang terhubung dengan akun Anda.
                        </p>

                    </div>

                </div>


                <div class="empty-card">

                    <i class="bi bi-person-exclamation"></i>

                    <h3>
                        Belum Ada Pasien Terhubung
                    </h3>

                    <p>
                        Akun keluarga ini belum terhubung dengan data pasien.
                        Silakan hubungi admin yayasan untuk menghubungkan
                        akun dengan pasien yang bersangkutan.
                    </p>

                </div>


            <?php else: ?>


                <!-- =================================================
                     PAGE HEADER
                ================================================== -->

                <div class="page-heading">

                    <div>

                        <h1>
                            Profil Pasien
                        </h1>

                        <p>
                            Informasi lengkap pasien yang terhubung dengan akun Anda.
                        </p>

                    </div>


                    <a
                        href="dashboard_keluarga.php"
                        class="back-button"
                    >

                        <i class="bi bi-arrow-left"></i>

                        Kembali

                    </a>

                </div>



                <!-- =================================================
                     PROFILE HEADER
                ================================================== -->

                <div class="patient-profile">

                    <div class="patient-profile-header">


                        <div class="patient-avatar">

                            <?= e(
                                strtoupper(
                                    substr(
                                        $pasien['nama_pasien'],
                                        0,
                                        1
                                    )
                                )
                            ); ?>

                        </div>


                        <div class="patient-profile-info">

                            <h2>
                                <?= e($pasien['nama_pasien']); ?>
                            </h2>

                            <p>
                                No. Registrasi:
                                <?= e($pasien['nomor_registrasi']); ?>
                            </p>

                        </div>


                        <div class="patient-badges">

                            <span class="badge <?= $lokasi_class; ?>">
                                <?= e($pasien['status_lokasi']); ?>
                            </span>


                            <span class="badge <?= $kondisi_class; ?>">
                                <?= e($pasien['kondisi']); ?>
                            </span>


                            <span class="badge <?= $status_class; ?>">
                                <?= e($pasien['status_pasien']); ?>
                            </span>

                        </div>


                    </div>

                </div>



                <!-- =================================================
                     IDENTITAS PASIEN
                ================================================== -->

                <div class="information-card">


                    <div class="section-title">

                        <div class="section-icon">
                            <i class="bi bi-person"></i>
                        </div>

                        <div>

                            <h3>
                                Identitas Pasien
                            </h3>

                            <p>
                                Informasi dasar dan identitas pasien
                            </p>

                        </div>

                    </div>



                    <div class="info-grid">


                        <!-- NOMOR REGISTRASI -->

                        <div class="info-item">

                            <span class="info-label">
                                Nomor Registrasi
                            </span>

                            <span class="info-value">
                                <?= e($pasien['nomor_registrasi']); ?>
                            </span>

                        </div>


                        <!-- NIK -->

                        <div class="info-item">

                            <span class="info-label">
                                NIK
                            </span>

                            <span class="info-value">
                                <?= e($pasien['nik']); ?>
                            </span>

                        </div>


                        <!-- NAMA -->

                        <div class="info-item">

                            <span class="info-label">
                                Nama Pasien
                            </span>

                            <span class="info-value">
                                <?= e($pasien['nama_pasien']); ?>
                            </span>

                        </div>


                        <!-- JENIS KELAMIN -->

                        <div class="info-item">

                            <span class="info-label">
                                Jenis Kelamin
                            </span>

                            <span class="info-value">
                                <?= e($pasien['jenis_kelamin']); ?>
                            </span>

                        </div>


                        <!-- TEMPAT LAHIR -->

                        <div class="info-item">

                            <span class="info-label">
                                Tempat Lahir
                            </span>

                            <span class="info-value">
                                <?= e($pasien['tempat_lahir']); ?>
                            </span>

                        </div>


                        <!-- TANGGAL LAHIR -->

                        <div class="info-item">

                            <span class="info-label">
                                Tanggal Lahir
                            </span>

                            <span class="info-value">
                                <?= formatTanggal($pasien['tanggal_lahir']); ?>
                            </span>

                        </div>


                        <!-- ALAMAT -->

                        <div class="info-item full-width">

                            <span class="info-label">
                                Alamat
                            </span>

                            <span class="info-value address">

                                <?= !empty($pasien['alamat'])
                                    ? nl2br(e($pasien['alamat']))
                                    : '-';
                                ?>

                            </span>

                        </div>


                    </div>

                </div>



                <!-- =================================================
                     STATUS PERAWATAN
                ================================================== -->

                <div class="information-card">


                    <div class="section-title">

                        <div class="section-icon">
                            <i class="bi bi-hospital"></i>
                        </div>

                        <div>

                            <h3>
                                Status Perawatan
                            </h3>

                            <p>
                                Informasi kondisi dan status perawatan pasien
                            </p>

                        </div>

                    </div>



                    <div class="info-grid">


                        <!-- LOKASI -->

                        <div class="info-item">

                            <span class="info-label">
                                Lokasi Pasien
                            </span>

                            <span class="info-value">

                                <span class="badge <?= $lokasi_class; ?>">
                                    <?= e($pasien['status_lokasi']); ?>
                                </span>

                            </span>

                        </div>


                        <!-- KONDISI -->

                        <div class="info-item">

                            <span class="info-label">
                                Kondisi Pasien
                            </span>

                            <span class="info-value">

                                <span class="badge <?= $kondisi_class; ?>">
                                    <?= e($pasien['kondisi']); ?>
                                </span>

                            </span>

                        </div>


                        <!-- TANGGAL MASUK -->

                        <div class="info-item">

                            <span class="info-label">
                                Tanggal Masuk
                            </span>

                            <span class="info-value">
                                <?= formatTanggal($pasien['tanggal_masuk']); ?>
                            </span>

                        </div>


                        <!-- STATUS -->

                        <div class="info-item">

                            <span class="info-label">
                                Status Pasien
                            </span>

                            <span class="info-value">

                                <span class="badge <?= $status_class; ?>">
                                    <?= e($pasien['status_pasien']); ?>
                                </span>

                            </span>

                        </div>


                    </div>

                </div>


            <?php endif; ?>


        </section>

    </main>

</div>



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
                <?= e($nama_awal); ?>
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
                        <?= e((string) $id_user); ?>
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
     JAVASCRIPT
========================================================= -->

<script>

document.addEventListener("DOMContentLoaded", function () {


    const profileButton =
        document.getElementById("profileButton");


    const profileDropdown =
        document.getElementById("profileDropdown");


    const profileWrapper =
        document.getElementById("userProfile");


    const profileSaya =
        document.getElementById("profileSaya");


    const profileModal =
        document.getElementById("profileModal");


    const closeProfile =
        document.getElementById("closeProfile");



    /* =====================================================
       BUKA / TUTUP DROPDOWN PROFIL
    ===================================================== */

    profileButton?.addEventListener(
        "click",
        function (e) {

            e.preventDefault();

            e.stopPropagation();


            profileDropdown?.classList.toggle("show");

            profileButton?.classList.toggle("active");

        }
    );



    /* =====================================================
       KLIK DI LUAR DROPDOWN
    ===================================================== */

    document.addEventListener(
        "click",
        function (e) {

            if (
                profileWrapper &&
                !profileWrapper.contains(e.target)
            ) {

                profileDropdown?.classList.remove("show");

                profileButton?.classList.remove("active");

            }

        }
    );



    /* =====================================================
       PROFIL SAYA
    ===================================================== */

    profileSaya?.addEventListener(
        "click",
        function (e) {

            e.preventDefault();

            e.stopPropagation();


            profileDropdown?.classList.remove("show");

            profileButton?.classList.remove("active");


            profileModal?.classList.add("show");

        }
    );



    /* =====================================================
       TUTUP MODAL
    ===================================================== */

    closeProfile?.addEventListener(
        "click",
        function () {

            profileModal?.classList.remove("show");

        }
    );



    /* =====================================================
       KLIK BACKDROP MODAL
    ===================================================== */

    profileModal?.addEventListener(
        "click",
        function (e) {

            if (e.target === profileModal) {

                profileModal.classList.remove("show");

            }

        }
    );



    /* =====================================================
       ESC
    ===================================================== */

    document.addEventListener(
        "keydown",
        function (e) {

            if (e.key === "Escape") {

                profileModal?.classList.remove("show");

                profileDropdown?.classList.remove("show");

                profileButton?.classList.remove("active");

            }

        }
    );

});

</script>


</body>
</html>