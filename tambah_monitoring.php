<?php

session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

/* =====================================================
   HALAMAN KHUSUS PETUGAS
===================================================== */

$role = $_SESSION['role'] ?? '';

if ($role !== 'petugas') {
    die("Anda tidak memiliki akses ke halaman ini.");
}

require_once "koneksi.php";

$nama_user = $_SESSION['nama_lengkap'] ?? 'Petugas';

/* Link khusus petugas */
$dashboard_link = 'dashboard_petugas.php';
$data_pasien_link = 'data_pasien_petugas.php';
$monitoring_link = 'monitoring_petugas.php';
$role_label = 'Petugas';


/* =====================================================
   NOTIFIKASI
===================================================== */

function simpanNotifikasi(
    $conn,
    $id_user,
    $judul,
    $pesan,
    $jenis,
    $id_referensi
) {
    $stmtNotif = mysqli_prepare(
        $conn,
        "INSERT INTO notifikasi
        (
            id_user,
            judul,
            pesan,
            jenis,
            id_referensi,
            status,
            created_at
        )
        VALUES (?, ?, ?, ?, ?, 'belum_dibaca', NOW())"
    );

    if ($stmtNotif) {

        mysqli_stmt_bind_param(
            $stmtNotif,
            "isssi",
            $id_user,
            $judul,
            $pesan,
            $jenis,
            $id_referensi
        );

        mysqli_stmt_execute($stmtNotif);
        mysqli_stmt_close($stmtNotif);
    }
}


/* =====================================================
   PROSES SIMPAN DATA MONITORING
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_pasien = isset($_POST['id_pasien'])
        ? (int) $_POST['id_pasien']
        : 0;

    $tanggal_monitoring =
        $_POST['tanggal_monitoring'] ?? '';

    $berat_badan = (
        isset($_POST['berat_badan']) &&
        $_POST['berat_badan'] !== ''
    )
        ? (float) $_POST['berat_badan']
        : null;

    $kondisi =
        trim($_POST['kondisi'] ?? '');

    $aktivitas_harian =
        trim($_POST['aktivitas_harian'] ?? '');

    $perilaku =
        trim($_POST['perilaku'] ?? '');

    $catatan =
        trim($_POST['catatan'] ?? '');

    $id_user =
        (int) $_SESSION['id_user'];


    /* =================================================
       VALIDASI
    ================================================= */

    if (
        $id_pasien <= 0 ||
        empty($tanggal_monitoring) ||
        empty($kondisi) ||
        empty($aktivitas_harian) ||
        empty($perilaku)
    ) {

        $error = "Data wajib belum lengkap.";

    } else {


        /* =================================================
           SIMPAN KE DATABASE
        ================================================= */

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO monitoring
            (
                id_pasien,
                id_user,
                tanggal_monitoring,
                berat_badan,
                kondisi,
                aktivitas_harian,
                perilaku,
                catatan
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );


        if (!$stmt) {

            $error =
                "Query gagal dibuat: "
                . mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "iisdssss",
                $id_pasien,
                $id_user,
                $tanggal_monitoring,
                $berat_badan,
                $kondisi,
                $aktivitas_harian,
                $perilaku,
                $catatan
            );


            if (mysqli_stmt_execute($stmt)) {

                $id_monitoring_baru =
                    mysqli_insert_id($conn);


                /* =================================================
                   AMBIL NAMA PASIEN
                ================================================= */

                $nama_pasien_notif = "Pasien";

                $stmtPasienNotif = mysqli_prepare(
                    $conn,
                    "SELECT nama_pasien
                     FROM pasien
                     WHERE id_pasien = ?"
                );


                if ($stmtPasienNotif) {

                    mysqli_stmt_bind_param(
                        $stmtPasienNotif,
                        "i",
                        $id_pasien
                    );

                    mysqli_stmt_execute(
                        $stmtPasienNotif
                    );

                    $resultPasienNotif =
                        mysqli_stmt_get_result(
                            $stmtPasienNotif
                        );


                    if (
                        $dataPasienNotif =
                        mysqli_fetch_assoc(
                            $resultPasienNotif
                        )
                    ) {

                        $nama_pasien_notif =
                            $dataPasienNotif['nama_pasien'];
                    }


                    mysqli_stmt_close(
                        $stmtPasienNotif
                    );
                }


                /* =================================================
                   SIMPAN NOTIFIKASI
                ================================================= */

                simpanNotifikasi(
                    $conn,
                    $id_user,
                    "Monitoring pasien ditambahkan",
                    "Monitoring pasien "
                    . $nama_pasien_notif
                    . " berhasil ditambahkan.",
                    "monitoring_tambah",
                    $id_monitoring_baru
                );


                mysqli_stmt_close($stmt);


                /* =================================================
                   KEMBALI KE MONITORING PETUGAS
                ================================================= */

                header(
                    "Location: monitoring_petugas.php?status=added"
                );

                exit;

            } else {

                $error =
                    "Data monitoring gagal disimpan: "
                    . mysqli_stmt_error($stmt);

                mysqli_stmt_close($stmt);
            }
        }
    }
}


/* =====================================================
   AMBIL DATA PASIEN
===================================================== */

$pasienQuery = mysqli_query(
    $conn,
    "SELECT
        id_pasien,
        nama_pasien,
        nomor_registrasi
     FROM pasien
     ORDER BY nama_pasien ASC"
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
        Tambah Monitoring - Sistem Informasi ODGJ
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

        /* =====================================================
           PAGE
        ===================================================== */

        .page-content {
            padding: 28px;
        }


        .page-header {

            display: flex;
            justify-content: space-between;
            align-items: center;
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
            color: #7a8499;
            font-size: 13px;

        }


        /* =====================================================
           BACK BUTTON
        ===================================================== */

        .back-button {

            display: inline-flex;
            align-items: center;
            gap: 8px;

            padding: 11px 17px;

            border: 1px solid #dce3ee;
            border-radius: 8px;

            background: white;
            color: #526078;

            text-decoration: none;

            font-size: 12px;
            font-weight: 500;

        }


        .back-button:hover {

            border-color: #2864e6;
            color: #2864e6;

        }


        /* =====================================================
           FORM CARD
        ===================================================== */

        .form-card {

            background: white;

            border: 1px solid #e1e7f0;
            border-radius: 12px;

            padding: 24px;

        }


        .section-title {

            display: flex;
            align-items: center;
            gap: 10px;

            margin-bottom: 22px;

        }


        .section-icon {

            width: 34px;
            height: 34px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 8px;

            background: #eaf1ff;
            color: #2864e6;

        }


        .section-title h3 {

            margin: 0;

            font-size: 16px;
            font-weight: 600;

            color: #17243a;

        }


        /* =====================================================
           FORM GRID
        ===================================================== */

        .form-grid {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 20px 28px;

        }


        .form-group {

            display: flex;
            flex-direction: column;

        }


        .form-group.full-width {

            grid-column: 1 / -1;

        }


        .form-group label {

            margin-bottom: 7px;

            color: #526078;

            font-size: 11px;
            font-weight: 500;

        }


        .required {

            color: #e53935;

        }


        .form-group input,
        .form-group select,
        .form-group textarea {

            width: 100%;
            box-sizing: border-box;

            border: 1px solid #dce3ee;
            border-radius: 8px;

            padding: 11px 13px;

            outline: none;

            background: white;
            color: #26334a;

            font-family: 'Poppins', sans-serif;
            font-size: 12px;

            transition: 0.2s;

        }


        .form-group input,
        .form-group select {

            height: 44px;

        }


        .form-group textarea {

            min-height: 105px;

            resize: vertical;

            line-height: 1.6;

        }


        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {

            border-color: #2864e6;

            box-shadow:
                0 0 0 3px
                rgba(40, 100, 230, 0.08);

        }


        /* =====================================================
           PATIENT INFO
        ===================================================== */

        .patient-option-info {

            margin-top: 7px;

            color: #8a95a8;

            font-size: 10px;

        }


        /* =====================================================
           ERROR
        ===================================================== */

        .alert-error {

            margin-bottom: 20px;

            padding: 12px 15px;

            border: 1px solid #ffd0d0;
            border-radius: 8px;

            background: #fff3f3;
            color: #d83b3b;

            font-size: 11px;

        }


        /* =====================================================
           FORM ACTION
        ===================================================== */

        .form-actions {

            display: flex;

            justify-content: flex-end;

            gap: 10px;

            margin-top: 25px;

            padding-top: 20px;

            border-top: 1px solid #edf0f5;

        }


        .cancel-button {

            display: inline-flex;

            align-items: center;
            justify-content: center;

            padding: 11px 18px;

            border: 1px solid #dce3ee;
            border-radius: 8px;

            background: white;
            color: #526078;

            text-decoration: none;

            font-size: 12px;
            font-weight: 500;

        }


        .save-button {

            display: inline-flex;

            align-items: center;
            justify-content: center;

            gap: 7px;

            padding: 11px 19px;

            border: none;
            border-radius: 8px;

            background: #2864e6;
            color: white;

            font-family: 'Poppins', sans-serif;

            font-size: 12px;
            font-weight: 600;

            cursor: pointer;

        }


        .save-button:hover {

            background: #1f55c8;

        }


        /* =====================================================
           SIDEBAR FIX
        ===================================================== */

        .dashboard-layout .sidebar {

            width: 218px !important;
            min-width: 218px !important;
            max-width: 218px !important;

            height: 100vh !important;

            overflow: hidden !important;

            position: fixed !important;

            left: 0 !important;
            top: 0 !important;

            z-index: 1000 !important;

        }


        .dashboard-layout .sidebar-logo {

            width: 100% !important;

            height: auto !important;

            padding: 22px 10px 15px !important;

            margin: 0 !important;

            text-align: center !important;

            box-sizing: border-box !important;

        }


        .dashboard-layout .sidebar-logo img {

            display: block !important;

            width: 58px !important;
            height: 58px !important;

            max-width: 58px !important;
            max-height: 58px !important;

            min-width: 58px !important;
            min-height: 58px !important;

            object-fit: contain !important;

            margin: 0 auto 7px !important;

        }


        .dashboard-layout .sidebar-logo h2 {

            margin: 0 !important;

            font-size: 17px !important;

            line-height: 1.3 !important;

        }


        .dashboard-layout .sidebar-logo p {

            margin: 3px 0 0 !important;

            font-size: 9px !important;

            line-height: 1.3 !important;

        }


        .dashboard-layout .main-content {

            margin-left: 218px !important;

            width: calc(100% - 218px) !important;

            min-width: 0 !important;

        }


        /* =====================================================
           PROFILE
        ===================================================== */

        .profile-wrapper {

            position: relative;

        }


        .profile-trigger {

            border: none;

            background: transparent;

            display: flex;

            align-items: center;

            gap: 10px;

            padding: 6px 8px;

            border-radius: 10px;

            cursor: pointer;

            font-family: 'Poppins', sans-serif;

            transition: all 0.2s ease;

        }


        .profile-trigger:hover {

            background: #f4f7fc;

        }


        .profile-info {

            display: flex;

            flex-direction: column;

            align-items: flex-end;

            line-height: 1.2;

        }


        .profile-info strong {

            font-size: 12px;

            font-weight: 600;

            color: #17243a;

        }


        .profile-info span {

            font-size: 9px;

            color: #8993a6;

            margin-top: 2px;

        }


        .profile-avatar {

            width: 38px;
            height: 38px;

            min-width: 38px;
            min-height: 38px;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: #2864e6;

            color: #ffffff;

            font-family: 'Poppins', sans-serif;

            font-size: 13px;
            font-weight: 600;

            box-sizing: border-box;

            transition: all 0.2s ease;

        }


        .profile-avatar:hover {

            background: #2864e6;

            color: #ffffff;

            transform: translateY(-1px);

            box-shadow:
                0 4px 12px
                rgba(40, 100, 230, 0.15);

        }


        .profile-arrow {

            font-size: 10px;

            color: #8993a6;

            transition: transform 0.2s ease;

        }


        .profile-trigger.active
        .profile-arrow {

            transform: rotate(180deg);

        }


        .profile-avatar.large {

            width: 40px;
            height: 40px;

            min-width: 40px;
            min-height: 40px;

            background: #2864e6;

            color: #ffffff;

        }


        /* =====================================================
           PROFILE DROPDOWN
        ===================================================== */

        .profile-dropdown {

            display: none;

            position: absolute;

            top: calc(100% + 10px);

            right: 0;

            width: 220px;

            background: #ffffff;

            border: 1px solid #e1e7f0;

            border-radius: 12px;

            box-shadow:
                0 12px 35px
                rgba(23, 36, 58, 0.14);

            z-index: 2000;

            overflow: hidden;

        }


        .profile-dropdown.show {

            display: block;

        }


        .profile-dropdown-header {

            display: flex;

            align-items: center;

            gap: 11px;

            padding: 18px 17px 14px;

        }


        .profile-dropdown-header
        > div:last-child {

            display: flex;

            flex-direction: column;

        }


        .profile-dropdown-header strong {

            font-size: 13px;

            font-weight: 600;

            color: #17243a;

        }


        .profile-dropdown-header span {

            font-size: 9px;

            color: #8993a6;

            margin-top: 3px;

        }


        .profile-divider {

            height: 1px;

            background: #edf0f5;

            margin: 0 12px;

        }


        .profile-menu-item {

            display: flex;

            align-items: center;

            gap: 11px;

            padding: 12px 17px;

            text-decoration: none;

            color: #65738a;

            font-size: 11px;

            transition: all 0.2s ease;

        }


        .profile-menu-item i {

            color: #2864e6 !important;

        }


        .profile-menu-item.logout i {

            color: #ef4444 !important;

        }


        .profile-menu-item:hover {

            background: #f7f9fc;

            color: #2864e6;

        }


        .profile-menu-item.logout {

            color: #ef4444;

            margin-bottom: 5px;

        }


        .profile-menu-item.logout:hover {

            background: #fff5f5;

            color: #dc2626;

        }


        /* =====================================================
           PROFILE MODAL
        ===================================================== */

        .profile-modal {

            position: fixed;

            inset: 0;

            display: flex;

            align-items: center;
            justify-content: center;

            padding: 20px;

            background:
                rgba(23, 36, 58, 0.28);

            opacity: 0;

            visibility: hidden;

            transition: 0.2s ease;

            z-index: 2000;

        }


        .profile-modal.show {

            opacity: 1;

            visibility: visible;

        }


        .profile-modal-card {

            width: 100%;

            max-width: 370px;

            background: #fff;

            border-radius: 14px;

            box-shadow:
                0 18px 45px
                rgba(31, 48, 84, 0.18);

            padding: 22px;

        }


        .profile-modal-head {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            margin-bottom: 18px;

        }


        .profile-modal-title {

            margin: 0;

            color: #17243a;

            font-size: 16px;

            font-weight: 700;

        }


        .profile-modal-close {

            width: 32px;
            height: 32px;

            border: 1px solid #e1e7f0;

            border-radius: 8px;

            background: #fff;

            color: #667085;

            cursor: pointer;

        }


        .profile-modal-user {

            display: flex;

            align-items: center;

            gap: 12px;

            padding: 13px;

            margin-bottom: 16px;

            border-radius: 10px;

            background: #f6f8fc;

        }


        .profile-modal-user .profile-avatar {

            width: 42px;
            height: 42px;

            min-width: 42px;

        }


        .profile-modal-user-name {

            color: #17243a;

            font-size: 13px;

            font-weight: 600;

            margin: 0;

        }


        .profile-modal-user-role {

            color: #7a8499;

            font-size: 10px;

            margin-top: 2px;

        }


        .profile-info {

            display: grid;

            gap: 10px;

        }


        .profile-info-row {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            padding-bottom: 10px;

            border-bottom: 1px solid #edf0f5;

        }


        .profile-info-row:last-child {

            border-bottom: 0;

            padding-bottom: 0;

        }


        .profile-info-label {

            color: #8a95a8;

            font-size: 10px;

        }


        .profile-info-value {

            color: #26334a;

            font-size: 11px;

            font-weight: 600;

            text-align: right;

        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 800px) {

            .page-content {

                padding: 18px;

            }


            .page-header {

                align-items: flex-start;

                flex-direction: column;

                gap: 15px;

            }


            .form-grid {

                grid-template-columns: 1fr;

            }


            .form-group.full-width {

                grid-column: auto;

            }

        }

    </style>

</head>


<body>


<div class="dashboard-layout">


    <!-- =====================================================
         SIDEBAR PETUGAS
    ===================================================== -->

    <aside class="sidebar">


        <div class="sidebar-logo">

            <img
                src="/SIPM-ODGJ/assets/img/logo YCKA.png"
                alt="Logo Yayasan"
            >


            <h2 style="color:#2864e6 !important;">
                SIPM ODGJ
            </h2>


            <p>
                Yayasan Cahaya Kasih Amanah
            </p>

        </div>


        <nav class="sidebar-menu">


            <!-- DASHBOARD -->

            <a
                href="<?= htmlspecialchars($dashboard_link); ?>"
                class="menu-item"
            >

                <i class="bi bi-grid"></i>

                <span>
                    Dashboard
                </span>

            </a>


            <!-- DATA PASIEN -->

            <a
                href="<?= htmlspecialchars($data_pasien_link); ?>"
                class="menu-item"
            >

                <i class="bi bi-people"></i>

                <span>
                    Data Pasien
                </span>

            </a>


            <!-- MONITORING -->

            <a
                href="<?= htmlspecialchars($monitoring_link); ?>"
                class="menu-item active"
            >

                <i class="bi bi-clipboard2-pulse"></i>

                <span>
                    Monitoring
                </span>

            </a>


        </nav>


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


    <!-- =====================================================
         MAIN CONTENT
    ===================================================== -->

    <main class="main-content">


        <!-- =================================================
             TOPBAR
        ================================================= -->

        <header class="topbar">


            <h1>
                Tambah Monitoring
            </h1>


            <!-- PROFILE SAJA
                 TANPA SEARCH & NOTIFIKASI
            -->

            <div class="topbar-right">


                <div class="profile-wrapper">


                    <button
                        type="button"
                        class="profile-trigger"
                        id="profileTrigger"
                        aria-label="Menu Profil"
                    >


                        <div class="profile-info">

                            <strong>
                                <?= htmlspecialchars($nama_user); ?>
                            </strong>

                            <span>
                                petugas
                            </span>

                        </div>


                        <div class="profile-avatar">

                            <?= htmlspecialchars(
                                strtoupper(
                                    substr(
                                        $nama_user,
                                        0,
                                        1
                                    )
                                )
                            ); ?>

                        </div>


                        <i
                            class="bi bi-chevron-up profile-arrow"
                        ></i>


                    </button>


                    <!-- PROFILE DROPDOWN -->

                    <div
                        class="profile-dropdown"
                        id="profileDropdown"
                    >


                        <div
                            class="profile-dropdown-header"
                        >


                            <div class="profile-avatar large">

                                <?= htmlspecialchars(
                                    strtoupper(
                                        substr(
                                            $nama_user,
                                            0,
                                            1
                                        )
                                    )
                                ); ?>

                            </div>


                            <div>

                                <strong>
                                    <?= htmlspecialchars(
                                        $nama_user
                                    ); ?>
                                </strong>

                                <span>
                                    petugas
                                </span>

                            </div>


                        </div>


                        <div class="profile-divider"></div>


                        <a
                            href="profil.php"
                            class="profile-menu-item"
                        >

                            <i
                                class="bi bi-person-circle"
                            ></i>

                            <span>
                                Profil Saya
                            </span>

                        </a>


                        <a
                            href="logout.php"
                            class="profile-menu-item logout"
                        >

                            <i
                                class="bi bi-box-arrow-left"
                            ></i>

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
        ================================================= -->

        <div class="page-content">


            <div class="page-header">


                <div class="page-title">

                    <h2>
                        Tambah Monitoring Pasien
                    </h2>

                    <p>
                        Tambahkan data hasil monitoring pasien ODGJ
                    </p>

                </div>


                <a
                    href="monitoring_petugas.php"
                    class="back-button"
                >

                    <i class="bi bi-arrow-left"></i>

                    Kembali

                </a>


            </div>


            <!-- =================================================
                 FORM CARD
            ================================================= -->

            <div class="form-card">


                <div class="section-title">


                    <div class="section-icon">

                        <i
                            class="bi bi-clipboard2-pulse"
                        ></i>

                    </div>


                    <h3>
                        Data Monitoring
                    </h3>


                </div>


                <!-- ERROR -->

                <?php if (!empty($error)): ?>

                    <div class="alert-error">

                        <i
                            class="bi bi-exclamation-circle"
                        ></i>

                        <?= htmlspecialchars($error); ?>

                    </div>

                <?php endif; ?>


                <!-- FORM -->

                <form
                    method="POST"
                    action=""
                >


                    <div class="form-grid">


                        <!-- =================================================
                             PASIEN
                        ================================================= -->

                        <div class="form-group">


                            <label>

                                Pasien

                                <span class="required">
                                    *
                                </span>

                            </label>


                            <select
                                name="id_pasien"
                                required
                            >


                                <option value="">
                                    Pilih pasien
                                </option>


                                <?php
                                while (
                                    $pasien =
                                    mysqli_fetch_assoc(
                                        $pasienQuery
                                    )
                                ):
                                ?>


                                    <option
                                        value="<?= $pasien['id_pasien']; ?>"
                                        <?= (
                                            isset(
                                                $_POST['id_pasien']
                                            ) &&
                                            $_POST['id_pasien']
                                            ==
                                            $pasien['id_pasien']
                                        )
                                            ? 'selected'
                                            : '';
                                        ?>
                                    >


                                        <?= htmlspecialchars(
                                            $pasien['nama_pasien']
                                        ); ?>

                                        -

                                        <?= htmlspecialchars(
                                            $pasien['nomor_registrasi']
                                        ); ?>


                                    </option>


                                <?php endwhile; ?>


                            </select>


                            <span
                                class="patient-option-info"
                            >
                                Pilih pasien yang akan dimonitor.
                            </span>


                        </div>


                        <!-- =================================================
                             TANGGAL
                        ================================================= -->

                        <div class="form-group">


                            <label>

                                Tanggal Monitoring

                                <span class="required">
                                    *
                                </span>

                            </label>


                            <input
                                type="datetime-local"
                                name="tanggal_monitoring"
                                value="<?= htmlspecialchars(
                                    $_POST['tanggal_monitoring']
                                    ?? date('Y-m-d\TH:i')
                                ); ?>"
                                required
                            >


                        </div>


                        <!-- =================================================
                             BERAT BADAN
                        ================================================= -->

                        <div class="form-group">


                            <label>
                                Berat Badan (kg)
                            </label>


                            <input
                                type="number"
                                name="berat_badan"
                                step="0.01"
                                min="0"
                                placeholder="Contoh: 65.50"
                                value="<?= htmlspecialchars(
                                    $_POST['berat_badan']
                                    ?? ''
                                ); ?>"
                            >


                        </div>


                        <!-- =================================================
                             KONDISI
                        ================================================= -->

                        <div class="form-group">


                            <label>

                                Kondisi

                                <span class="required">
                                    *
                                </span>

                            </label>


                            <select
                                name="kondisi"
                                required
                            >


                                <option value="">
                                    Pilih kondisi
                                </option>


                                <option
                                    value="Stabil"
                                    <?= (
                                        ($_POST['kondisi'] ?? '')
                                        ===
                                        'Stabil'
                                    )
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    Stabil
                                </option>


                                <option
                                    value="Perlu Pantauan"
                                    <?= (
                                        ($_POST['kondisi'] ?? '')
                                        ===
                                        'Perlu Pantauan'
                                    )
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    Perlu Pantauan
                                </option>


                                <option
                                    value="Darurat"
                                    <?= (
                                        ($_POST['kondisi'] ?? '')
                                        ===
                                        'Darurat'
                                    )
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    Darurat
                                </option>


                            </select>


                        </div>


                        <!-- =================================================
                             AKTIVITAS HARIAN
                        ================================================= -->

                        <div class="form-group full-width">


                            <label>

                                Aktivitas Harian

                                <span class="required">
                                    *
                                </span>

                            </label>


                            <textarea
                                name="aktivitas_harian"
                                placeholder="Masukkan aktivitas harian pasien..."
                                required
                            ><?= htmlspecialchars(
                                $_POST['aktivitas_harian']
                                ?? ''
                            ); ?></textarea>


                        </div>


                        <!-- =================================================
                             PERILAKU
                        ================================================= -->

                        <div class="form-group full-width">


                            <label>

                                Perilaku

                                <span class="required">
                                    *
                                </span>

                            </label>


                            <textarea
                                name="perilaku"
                                placeholder="Masukkan kondisi atau perilaku pasien..."
                                required
                            ><?= htmlspecialchars(
                                $_POST['perilaku']
                                ?? ''
                            ); ?></textarea>


                        </div>


                        <!-- =================================================
                             CATATAN
                        ================================================= -->

                        <div class="form-group full-width">


                            <label>
                                Catatan
                            </label>


                            <textarea
                                name="catatan"
                                placeholder="Masukkan catatan tambahan..."
                            ><?= htmlspecialchars(
                                $_POST['catatan']
                                ?? ''
                            ); ?></textarea>


                        </div>


                    </div>


                    <!-- =================================================
                         ACTION
                    ================================================= -->

                    <div class="form-actions">


                        <a
                            href="monitoring_petugas.php"
                            class="cancel-button"
                        >

                            Batal

                        </a>


                        <button
                            type="submit"
                            class="save-button"
                        >

                            <i
                                class="bi bi-check-lg"
                            ></i>

                            Simpan Monitoring

                        </button>


                    </div>


                </form>


            </div>


        </div>


    </main>


</div>


<!-- =========================================================
     PROFILE MODAL
========================================================= -->

<div
    class="profile-modal"
    id="profileModal"
    aria-hidden="true"
>


    <div
        class="profile-modal-card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="profileModalTitle"
    >


        <div class="profile-modal-head">


            <h3
                class="profile-modal-title"
                id="profileModalTitle"
            >
                Profil Saya
            </h3>


            <button
                type="button"
                class="profile-modal-close"
                id="profileModalClose"
                aria-label="Tutup"
            >

                <i class="bi bi-x-lg"></i>

            </button>


        </div>


        <div class="profile-modal-user">


            <span
                class="profile-avatar"
                aria-hidden="true"
            >

                <?= htmlspecialchars(
                    strtoupper(
                        substr(
                            $nama_user,
                            0,
                            1
                        )
                    )
                ); ?>

            </span>


            <div>

                <p class="profile-modal-user-name">

                    <?= htmlspecialchars(
                        $nama_user
                    ); ?>

                </p>


                <p class="profile-modal-user-role">

                    Petugas

                </p>

            </div>


        </div>


        <div class="profile-info">


            <div class="profile-info-row">

                <span class="profile-info-label">
                    Nama
                </span>

                <span class="profile-info-value">

                    <?= htmlspecialchars(
                        $nama_user
                    ); ?>

                </span>

            </div>


            <div class="profile-info-row">

                <span class="profile-info-label">
                    ID Pengguna
                </span>

                <span class="profile-info-value">

                    <?= (int) $_SESSION['id_user']; ?>

                </span>

            </div>


            <div class="profile-info-row">

                <span class="profile-info-label">
                    Role
                </span>

                <span class="profile-info-value">

                    Petugas

                </span>

            </div>


        </div>


    </div>


</div>


<!-- =========================================================
     JAVASCRIPT PROFILE
========================================================= -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const profileWrapper =
            document.querySelector(
                '.profile-wrapper'
            );

        const profileTrigger =
            document.getElementById(
                'profileTrigger'
            );

        const profileDropdown =
            document.getElementById(
                'profileDropdown'
            );


        if (
            profileWrapper &&
            profileTrigger &&
            profileDropdown
        ) {


            profileTrigger.addEventListener(
                'click',
                function (event) {

                    event.stopPropagation();

                    profileDropdown.classList.toggle(
                        'show'
                    );

                    profileTrigger.classList.toggle(
                        'active'
                    );

                }
            );


            profileDropdown.addEventListener(
                'click',
                function (event) {

                    event.stopPropagation();

                }
            );


            document.addEventListener(
                'click',
                function () {

                    profileDropdown.classList.remove(
                        'show'
                    );

                    profileTrigger.classList.remove(
                        'active'
                    );

                }
            );

        }

    }
);

</script>


</body>

</html>