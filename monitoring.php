<?php

session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

require_once "koneksi.php";


/* =========================================================
   DATA USER YANG LOGIN
========================================================= */

$id_user_login = (int) $_SESSION['id_user'];

$query_user = mysqli_query(
    $conn,
    "SELECT
        nama_lengkap,
        username
     FROM users
     WHERE id_user = $id_user_login
     LIMIT 1"
);

$user_login = mysqli_fetch_assoc($query_user);

$nama_user = 'Admin';
$username_user = $user_login['username'] ?? 'admin';


/* =========================================================
   AMBIL INISIAL USER
========================================================= */

$nama_parts = preg_split(
    '/\s+/',
    trim($nama_user)
);

$inisial_user = '';

foreach (array_slice($nama_parts, 0, 2) as $part) {

    if ($part !== '') {
        $inisial_user .= strtoupper(
            substr($part, 0, 1)
        );
    }

}

if ($inisial_user === '') {
    $inisial_user = 'A';
}


/* =========================================================
   AMBIL DATA MONITORING
========================================================= */

$query = "
    SELECT
        m.id_monitoring,
        m.id_pasien,
        m.id_user,
        m.tanggal_monitoring,
        m.berat_badan,
        m.kondisi,
        m.aktivitas_harian,
        m.perilaku,
        m.catatan,
        m.created_at,
        p.nama_pasien,
        p.nomor_registrasi
    FROM monitoring m
    LEFT JOIN pasien p
        ON m.id_pasien = p.id_pasien
    ORDER BY m.tanggal_monitoring DESC
";

$result = mysqli_query($conn, $query);

if (!$result) {
    die(
        "Gagal mengambil data monitoring: "
        . mysqli_error($conn)
    );
}


/* =========================================================
   FORMAT KONDISI
========================================================= */

function kondisiClass($kondisi)
{
    if ($kondisi === 'Stabil') {
        return 'badge-stabil';
    }

    if ($kondisi === 'Perlu Pantauan') {
        return 'badge-pantauan';
    }

    if ($kondisi === 'Darurat') {
        return 'badge-darurat';
    }

    return 'badge-default';
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
        Monitoring Pasien - Sistem Informasi ODGJ
    </title>


    <!-- GOOGLE FONT -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
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
           MONITORING PAGE
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
           TOPBAR
        ===================================================== */

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }


        .topbar-right {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 14px;
        }


        /* =====================================================
           NOTIFICATION
        ===================================================== */

        .notification-button {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            background: transparent;
            color: #17243a;
            font-size: 18px;
            cursor: pointer;
            text-decoration: none;
        }


        .notification-button:hover {
            color: #2864e6;
        }


        /* =====================================================
           PROFILE AREA
        ===================================================== */

        .profile-wrapper {
            position: relative;
        }


        .profile-toggle {
            display: flex;
            align-items: center;
            gap: 9px;
            min-width: 122px;
            height: 50px;
            padding: 6px 9px;
            border: none;
            border-radius: 10px;
            background: #f4f7fc;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            text-decoration: none;
            box-sizing: border-box;
        }


        .profile-toggle:hover {
            background: #eef3ff;
        }


        .profile-info {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: center;
            line-height: 1.2;
        }


        .profile-name {
            color: #17243a;
            font-size: 12px;
            font-weight: 600;
        }


        .profile-username {
            margin-top: 2px;
            color: #8a95a8;
            font-size: 9px;
            font-weight: 400;
        }


        /* LINGKARAN BIRU */

        .profile-avatar {
            width: 38px;
            height: 38px;
            min-width: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #2864e6;
            color: #ffffff;
            font-size: 12px;
            font-weight: 600;
            line-height: 1;
        }


        .profile-chevron {
            color: #8a95a8;
            font-size: 11px;
            transition: transform 0.2s ease;
        }


        .profile-wrapper.open .profile-chevron {
            transform: rotate(180deg);
        }


        /* =====================================================
           PROFILE DROPDOWN
        ===================================================== */

        .profile-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: 220px;
            background: #ffffff;
            border: 1px solid #dfe6f0;
            border-radius: 12px;
            padding: 10px;
            box-shadow:
                0 12px 30px rgba(23, 36, 58, 0.12);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-6px);
            transition: all 0.2s ease;
            z-index: 5000;
        }


        .profile-wrapper.open .profile-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }


        .dropdown-user {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px;
        }


        .dropdown-avatar {
            width: 38px;
            height: 38px;
            min-width: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #2864e6;
            color: #ffffff;
            font-size: 12px;
            font-weight: 600;
        }


        .dropdown-user-info {
            min-width: 0;
        }


        .dropdown-user-info strong {
            display: block;
            color: #17243a;
            font-size: 12px;
            font-weight: 600;
        }


        .dropdown-user-info span {
            display: block;
            margin-top: 2px;
            color: #8a95a8;
            font-size: 9px;
        }


        .dropdown-divider {
            height: 1px;
            margin: 5px 0;
            background: #e9edf4;
        }


        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 10px;
            border-radius: 7px;
            color: #526078;
            text-decoration: none;
            font-size: 11px;
            box-sizing: border-box;
            transition: all 0.2s ease;
        }


        .dropdown-item i {
            width: 16px;
            text-align: center;
            font-size: 14px;
        }


        .dropdown-item:hover {
            background: #f4f7fc;
            color: #2864e6;
        }


        .dropdown-item.logout {
            color: #e54848;
        }


        .dropdown-item.logout:hover {
            background: #fff1f1;
            color: #d83b3b;
        }


        /* =====================================================
           BUTTON TAMBAH
        ===================================================== */

        .add-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 18px;
            border-radius: 9px;
            background: #2864e6;
            color: #ffffff;
            text-decoration: none;
            font-family: 'Poppins', sans-serif;
            font-size: 12px;
            font-weight: 600;
            transition: 0.2s;
        }


        .add-button:hover {
            background: #1f55c8;
            color: #ffffff;
        }


        /* =====================================================
           MONITORING CARD
        ===================================================== */

        .monitoring-card {
            background: #ffffff;
            border: 1px solid #e1e7f0;
            border-radius: 12px;
            overflow: hidden;
        }


        .monitoring-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 22px;
            border-bottom: 1px solid #edf0f5;
            gap: 16px;
        }


        .monitoring-card-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #17243a;
        }


        /* =====================================================
           SEARCH MONITORING
        ===================================================== */

        .search-box-monitoring {
            position: relative;
            width: 280px;
        }


        .search-box-monitoring i {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: #9aa5b8;
            font-size: 14px;
        }


        .search-box-monitoring input {
            width: 100%;
            height: 42px;
            padding: 0 14px 0 38px;
            border: 1px solid #dce3ee;
            border-radius: 8px;
            outline: none;
            font-family: 'Poppins', sans-serif;
            font-size: 12px;
            box-sizing: border-box;
        }


        .search-box-monitoring input:focus {
            border-color: #2864e6;
        }


        /* =====================================================
           TABLE
        ===================================================== */

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }


        .monitoring-table {
            width: 100%;
            min-width: 1100px;
            border-collapse: collapse;
        }


        .monitoring-table th {
            padding: 14px 16px;
            text-align: left;
            background: #f8faff;
            color: #69768c;
            font-size: 10px;
            font-weight: 600;
            white-space: nowrap;
            border-bottom: 1px solid #e7ecf4;
        }


        .monitoring-table td {
            padding: 16px;
            color: #26334a;
            font-size: 11px;
            border-bottom: 1px solid #edf0f5;
            vertical-align: middle;
        }


        .monitoring-table tbody tr:hover {
            background: #fafcff;
        }


        /* =====================================================
           PASIEN
        ===================================================== */

        .patient-name {
            font-weight: 600;
            color: #17243a;
        }


        .patient-registration {
            display: block;
            margin-top: 3px;
            color: #8a95a8;
            font-size: 9px;
        }


        .monitoring-date {
            white-space: nowrap;
        }


        .weight {
            font-weight: 600;
            white-space: nowrap;
        }


        .activity-text {
            max-width: 190px;
            line-height: 1.5;
        }


        .behavior-text {
            max-width: 160px;
            line-height: 1.5;
        }


        .note-text {
            max-width: 200px;
            line-height: 1.5;
        }


        /* =====================================================
           BADGE
        ===================================================== */

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 9px;
            font-weight: 500;
            white-space: nowrap;
        }


        .badge-stabil {
            background: #dcf8e8;
            color: #159447;
        }


        .badge-pantauan {
            background: #fff0c9;
            color: #b57b00;
        }


        .badge-darurat {
            background: #ffe1e1;
            color: #d83b3b;
        }


        .badge-default {
            background: #eef1f6;
            color: #68758b;
        }


        /* =====================================================
           AKSI
        ===================================================== */

        .action-buttons {
            display: flex;
            align-items: center;
            gap: 6px;
        }


        .action-button {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #dce3ee;
            border-radius: 7px;
            background: #ffffff;
            color: #526078;
            text-decoration: none;
            font-size: 13px;
            transition: 0.2s;
            cursor: pointer;
        }


        .action-button:hover {
            border-color: #2864e6;
            color: #2864e6;
            background: #f5f8ff;
        }


        /* =====================================================
           DELETE BUTTON
        ===================================================== */

        .action-button.delete {
            background: #ffe5e5;
            border-color: #ffe5e5;
            color: #dc3b3b;
        }


        .action-button.delete:hover {
            background: #dc3b3b;
            border-color: #dc3b3b;
            color: #ffffff;
        }


        /* =====================================================
           EMPTY DATA
        ===================================================== */

        .empty-monitoring {
            padding: 55px 20px;
            text-align: center;
            color: #8a95a8;
        }


        .empty-monitoring i {
            display: block;
            margin-bottom: 12px;
            font-size: 38px;
            color: #b9c4d5;
        }


        .empty-monitoring h4 {
            margin: 0 0 5px;
            color: #526078;
            font-size: 14px;
        }


        .empty-monitoring p {
            margin: 0;
            font-size: 11px;
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
           DELETE MODAL
        ===================================================== */

        .delete-modal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }


        .delete-modal.show {
            display: flex;
        }


        .delete-modal-overlay {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
        }


        .delete-modal-content {
            position: relative;
            width: 390px;
            max-width: calc(100% - 40px);
            padding: 30px;
            background: #ffffff;
            border-radius: 14px;
            text-align: center;
            box-shadow:
                0 20px 50px rgba(15, 23, 42, 0.18);
            animation: deleteModalShow 0.2s ease;
        }


        @keyframes deleteModalShow {

            from {
                opacity: 0;
                transform: scale(0.94);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }

        }


        .delete-icon {
            width: 56px;
            height: 56px;
            margin: 0 auto 17px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #ffe8e8;
            color: #dc3b3b;
            font-size: 23px;
        }


        .delete-modal-content h3 {
            margin: 0 0 10px;
            color: #17243a;
            font-size: 19px;
            font-weight: 700;
        }


        .delete-modal-content p {
            margin: 0 auto 8px;
            color: #69758a;
            font-size: 12px;
            line-height: 1.7;
        }


        .delete-modal-content p strong {
            color: #26334a;
        }


        .delete-warning {
            display: block;
            margin-bottom: 23px;
            color: #dc3b3b;
            font-size: 10px;
        }


        .delete-modal-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
        }


        .cancel-delete,
        .confirm-delete {
            min-width: 100px;
            padding: 11px 18px;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            box-sizing: border-box;
        }


        .cancel-delete {
            background: #ffffff;
            border: 1px solid #dce3ee;
            color: #526078;
        }


        .cancel-delete:hover {
            background: #f7f9fc;
        }


        .confirm-delete {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            background: #dc3b3b;
            border: 1px solid #dc3b3b;
            color: #ffffff;
        }


        .confirm-delete:hover {
            background: #c92f2f;
            color: #ffffff;
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


            .search-box-monitoring {
                width: 100%;
            }


            .monitoring-card-header {
                align-items: flex-start;
                flex-direction: column;
                gap: 15px;
            }


            .profile-info {
                display: none;
            }


            .profile-toggle {
                min-width: auto;
                width: auto;
            }

        }

        /* WARNA SIPM ODGJ */
.dashboard-layout .sidebar-logo h2.sipm-title {
    color: #2864e6 !important;
    -webkit-text-fill-color: #2864e6 !important;
}

    </style>

</head>


<body>

<div class="dashboard-layout">


    <!-- =====================================================
         SIDEBAR
    ===================================================== -->

    <aside class="sidebar">


        <div class="sidebar-logo">

            <img
                src="/SIPM-ODGJ/assets/img/logo YCKA.png"
                alt="Logo Yayasan"
            >

            <h2 class="sipm-title">
                SIPM ODGJ
            </h2>

            <p>
                Yayasan Cahaya Kasih Amanah
            </p>

        </div>


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
                class="menu-item"
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
                class="menu-item active"
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
                Monitoring
            </h1>


            <div class="topbar-right">


                <!-- NOTIFICATION -->

                <a
                    href="#"
                    class="notification-button"
                    title="Notifikasi"
                    onclick="return false;"
                >

                    <i class="bi bi-bell"></i>

                </a>


                <!-- PROFILE -->

                <div
                    class="profile-wrapper"
                    id="profileWrapper"
                >


                    <button
                        type="button"
                        class="profile-toggle"
                        id="profileToggle"
                    >


                        <!-- NAMA -->

                        <span class="profile-info">

                            <span class="profile-name">

                                <?= htmlspecialchars(
                                    $nama_user
                                ); ?>

                            </span>

                            <span class="profile-username">

                                <?= htmlspecialchars(
                                    $username_user
                                ); ?>

                            </span>

                        </span>


                        <!-- AVATAR BIRU -->

                        <span class="profile-avatar">

                            <?= htmlspecialchars(
                                $inisial_user
                            ); ?>

                        </span>


                        <!-- CHEVRON -->

                        <i
                            class="bi bi-chevron-down profile-chevron"
                        ></i>


                    </button>


                    <!-- DROPDOWN -->

                    <div
                        class="profile-dropdown"
                        id="profileDropdown"
                    >


                        <div class="dropdown-user">


                            <div class="dropdown-avatar">

                                <?= htmlspecialchars(
                                    $inisial_user
                                ); ?>

                            </div>


                            <div class="dropdown-user-info">

                                <strong>

                                    <?= htmlspecialchars(
                                        $nama_user
                                    ); ?>

                                </strong>

                                <span>

                                    <?= htmlspecialchars(
                                        $username_user
                                    ); ?>

                                </span>

                            </div>


                        </div>


                        <div class="dropdown-divider"></div>


                        <a
                            href="profil.php"
                            class="dropdown-item"
                        >

                            <i class="bi bi-person-circle"></i>

                            <span>
                                Profil Saya
                            </span>

                        </a>


                        <a
                            href="logout.php"
                            class="dropdown-item logout"
                        >

                            <i class="bi bi-box-arrow-right"></i>

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
                        Monitoring Pasien
                    </h2>


                    <p>
                        Kelola data monitoring perkembangan pasien ODGJ
                    </p>


                </div>


                <a
                    href="tambah_monitoring.php"
                    class="add-button"
                >

                    <i class="bi bi-plus-lg"></i>

                    Tambah Monitoring

                </a>


            </div>


            <!-- =================================================
                 MONITORING CARD
            ================================================= -->

            <div class="monitoring-card">


                <!-- CARD HEADER -->

                <div class="monitoring-card-header">


                    <h3>
                        Daftar Monitoring
                    </h3>


                    <!-- SEARCH -->

                    <div class="search-box-monitoring">


                        <i class="bi bi-search"></i>


                        <input
                            type="text"
                            id="searchMonitoring"
                            placeholder="Cari monitoring..."
                        >


                    </div>


                </div>


                <!-- =================================================
                     TABLE
                ================================================= -->

                <div class="table-wrapper">


                    <?php if (
                        $result &&
                        mysqli_num_rows($result) > 0
                    ): ?>


                        <table
                            class="monitoring-table"
                            id="monitoringTable"
                        >


                            <thead>

                                <tr>


                                    <th>
                                        No
                                    </th>


                                    <th>
                                        Pasien
                                    </th>


                                    <th>
                                        Tanggal Monitoring
                                    </th>


                                    <th>
                                        Berat Badan
                                    </th>


                                    <th>
                                        Kondisi
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


                                    <th>
                                        Aksi
                                    </th>


                                </tr>

                            </thead>


                            <tbody>


                                <?php

                                $no = 1;

                                while (
                                    $monitoring =
                                    mysqli_fetch_assoc($result)
                                ):

                                ?>


                                    <tr>


                                        <!-- NO -->

                                        <td>

                                            <?= $no++; ?>

                                        </td>


                                        <!-- PASIEN -->

                                        <td>


                                            <span
                                                class="patient-name"
                                            >

                                                <?= htmlspecialchars(
                                                    $monitoring[
                                                        'nama_pasien'
                                                    ] ?? 'Pasien'
                                                ); ?>

                                            </span>


                                            <span
                                                class="patient-registration"
                                            >

                                                <?= htmlspecialchars(
                                                    $monitoring[
                                                        'nomor_registrasi'
                                                    ] ?? '-'
                                                ); ?>

                                            </span>


                                        </td>


                                        <!-- TANGGAL -->

                                        <td
                                            class="monitoring-date"
                                        >

                                            <?=
                                                !empty(
                                                    $monitoring[
                                                        'tanggal_monitoring'
                                                    ]
                                                )

                                                ? date(
                                                    'd-m-Y H:i',
                                                    strtotime(
                                                        $monitoring[
                                                            'tanggal_monitoring'
                                                        ]
                                                    )
                                                )

                                                : '-';

                                            ?>

                                        </td>


                                        <!-- BERAT BADAN -->

                                        <td class="weight">

                                            <?=
                                                $monitoring[
                                                    'berat_badan'
                                                ] !== null

                                                ? htmlspecialchars(
                                                    $monitoring[
                                                        'berat_badan'
                                                    ]
                                                ) . ' kg'

                                                : '-';

                                            ?>

                                        </td>


                                        <!-- KONDISI -->

                                        <td>


                                            <span
                                                class="badge <?= kondisiClass(
                                                    $monitoring['kondisi'] ?? ''
                                                ); ?>"
                                            >

                                                <?= htmlspecialchars(
                                                    $monitoring[
                                                        'kondisi'
                                                    ] ?? '-'
                                                ); ?>

                                            </span>


                                        </td>


                                        <!-- AKTIVITAS -->

                                        <td>


                                            <div
                                                class="activity-text"
                                            >

                                                <?= htmlspecialchars(
                                                    $monitoring[
                                                        'aktivitas_harian'
                                                    ] ?? '-'
                                                ); ?>

                                            </div>


                                        </td>


                                        <!-- PERILAKU -->

                                        <td>


                                            <div
                                                class="behavior-text"
                                            >

                                                <?= htmlspecialchars(
                                                    $monitoring[
                                                        'perilaku'
                                                    ] ?? '-'
                                                ); ?>

                                            </div>


                                        </td>


                                        <!-- CATATAN -->

                                        <td>


                                            <div
                                                class="note-text"
                                            >

                                                <?= htmlspecialchars(
                                                    $monitoring[
                                                        'catatan'
                                                    ] ?? '-'
                                                ); ?>

                                            </div>


                                        </td>


                                        <!-- AKSI -->

                                        <td>


                                            <div
                                                class="action-buttons"
                                            >


                                                <!-- LIHAT -->

                                                <a
                                                    href="detail_monitoring.php?id=<?= (int) $monitoring['id_monitoring']; ?>"
                                                    class="action-button"
                                                    title="Lihat Monitoring"
                                                >

                                                    <i
                                                        class="bi bi-eye"
                                                    ></i>

                                                </a>


                                                <!-- EDIT -->

                                                <a
                                                    href="edit_monitoring.php?id=<?= (int) $monitoring['id_monitoring']; ?>"
                                                    class="action-button"
                                                    title="Edit Monitoring"
                                                >

                                                    <i
                                                        class="bi bi-pencil"
                                                    ></i>

                                                </a>


                                                <!-- HAPUS -->

                                                <button
                                                    type="button"
                                                    class="action-button delete"
                                                    title="Hapus Monitoring"
                                                    onclick="openDeleteModal(
                                                        <?= (int) $monitoring['id_monitoring']; ?>,
                                                        '<?= htmlspecialchars(
                                                            $monitoring['nama_pasien'] ?? 'Pasien',
                                                            ENT_QUOTES
                                                        ); ?>'
                                                    )"
                                                >

                                                    <i
                                                        class="bi bi-trash"
                                                    ></i>

                                                </button>


                                            </div>


                                        </td>


                                    </tr>


                                <?php endwhile; ?>


                            </tbody>


                        </table>


                    <?php else: ?>


                        <div
                            class="empty-monitoring"
                        >

                            <i
                                class="bi bi-clipboard2-pulse"
                            ></i>


                            <h4>
                                Belum Ada Data Monitoring
                            </h4>


                            <p>
                                Data monitoring pasien belum tersedia.
                            </p>


                        </div>


                    <?php endif; ?>


                </div>


            </div>


        </div>


    </main>


</div>


<!-- =========================================================
     SEARCH MONITORING
========================================================= -->

<script>

const searchInput =
    document.getElementById(
        'searchMonitoring'
    );


if (searchInput) {

    searchInput.addEventListener(
        'keyup',
        function () {

            const keyword =
                this.value
                    .toLowerCase()
                    .trim();


            const rows =
                document.querySelectorAll(
                    '#monitoringTable tbody tr'
                );


            rows.forEach(
                function (row) {

                    const text =
                        row.innerText
                            .toLowerCase();


                    row.style.display =
                        text.includes(keyword)
                            ? ''
                            : 'none';

                }
            );

        }
    );

}

</script>


<!-- =========================================================
     PROFILE DROPDOWN
========================================================= -->

<script>

const profileWrapper =
    document.getElementById(
        'profileWrapper'
    );

const profileToggle =
    document.getElementById(
        'profileToggle'
    );


if (profileWrapper && profileToggle) {

    profileToggle.addEventListener(
        'click',
        function (event) {

            event.stopPropagation();

            profileWrapper.classList.toggle(
                'open'
            );

        }
    );


    document.addEventListener(
        'click',
        function (event) {

            if (
                !profileWrapper.contains(
                    event.target
                )
            ) {

                profileWrapper.classList.remove(
                    'open'
                );

            }

        }
    );

}

</script>


<!-- =========================================================
     DELETE MODAL
========================================================= -->

<div
    id="deleteModal"
    class="delete-modal"
>


    <div
        class="delete-modal-overlay"
    ></div>


    <div
        class="delete-modal-content"
    >


        <div class="delete-icon">

            <i class="bi bi-trash3"></i>

        </div>


        <h3>
            Hapus Monitoring?
        </h3>


        <p>

            Apakah Anda yakin ingin menghapus
            data monitoring pasien

            <strong
                id="deletePatientName"
            ></strong>?

        </p>


        <span class="delete-warning">

            Data monitoring yang dihapus
            tidak dapat dikembalikan.

        </span>


        <div class="delete-modal-actions">


            <button
                type="button"
                class="cancel-delete"
                onclick="closeDeleteModal()"
            >

                Batal

            </button>


            <a
                href="#"
                id="confirmDeleteButton"
                class="confirm-delete"
            >

                <i class="bi bi-trash3"></i>

                Hapus

            </a>


        </div>


    </div>

</div>


<!-- =========================================================
     DELETE MODAL SCRIPT
========================================================= -->

<script>

function openDeleteModal(
    id,
    patientName
) {

    const modal =
        document.getElementById(
            'deleteModal'
        );


    const patientNameElement =
        document.getElementById(
            'deletePatientName'
        );


    const confirmButton =
        document.getElementById(
            'confirmDeleteButton'
        );


    if (
        !modal ||
        !patientNameElement ||
        !confirmButton
    ) {

        return;

    }


    patientNameElement.textContent =
        patientName;


    confirmButton.href =
        'hapus_monitoring.php?id=' +
        encodeURIComponent(id);


    modal.classList.add(
        'show'
    );

}


function closeDeleteModal() {

    const modal =
        document.getElementById(
            'deleteModal'
        );


    if (modal) {

        modal.classList.remove(
            'show'
        );

    }

}


document.addEventListener(
    'DOMContentLoaded',
    function () {

        const overlay =
            document.querySelector(
                '.delete-modal-overlay'
            );


        if (overlay) {

            overlay.addEventListener(
                'click',
                closeDeleteModal
            );

        }


        document.addEventListener(
            'keydown',
            function (event) {

                if (
                    event.key === 'Escape'
                ) {

                    closeDeleteModal();

                }

            }
        );

    }
);

</script>


</body>

</html>