<?php

session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

require_once "koneksi.php";


/* =========================
   DATA USER YANG LOGIN
========================= */

$id_user = (int) $_SESSION['id_user'];

$query_user = mysqli_query(
    $conn,
    "SELECT nama_lengkap, username
     FROM users
     WHERE id_user = $id_user
     LIMIT 1"
);

$user_login = mysqli_fetch_assoc($query_user);

$nama_user = $user_login['nama_lengkap'] ?? 'Admin';
$username_user = $user_login['username'] ?? 'admin';


/* Ambil inisial nama */

$nama_parts = preg_split('/\s+/', trim($nama_user));

$inisial_user = '';

foreach (array_slice($nama_parts, 0, 2) as $part) {

    if ($part !== '') {
        $inisial_user .= strtoupper(substr($part, 0, 1));
    }

}

if ($inisial_user === '') {
    $inisial_user = 'A';
}


/* =========================
   AMBIL DATA PASIEN
========================= */

$query = "SELECT * FROM pasien ORDER BY id_pasien DESC";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Gagal mengambil data pasien: " . mysqli_error($conn));
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
        Data Pasien - Sistem Informasi ODGJ
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

        /* =========================
           DATA PASIEN
        ========================= */

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

        }


        .page-title p {

            margin: 6px 0 0;

            color: #7a8499;

            font-size: 13px;

        }


        /* =========================
           BUTTON TAMBAH
        ========================= */

        .add-button {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            padding: 11px 18px;

            border-radius: 8px;

            background: #2864e6;

            color: white;

            text-decoration: none;

            font-size: 13px;

            font-weight: 600;

        }


        .add-button:hover {

            background: #1f56ca;

        }


        /* =========================
           CARD
        ========================= */

        .data-card {

            background: white;

            border: 1px solid #e1e7f0;

            border-radius: 12px;

            overflow: hidden;

        }


        .data-toolbar {

            display: flex;

            justify-content: space-between;

            align-items: center;

            padding: 18px 20px;

            border-bottom: 1px solid #edf0f5;

        }


        .data-toolbar h3 {

            margin: 0;

            font-size: 16px;

            font-weight: 600;

        }


        /* =========================
           SEARCH DATA PASIEN
        ========================= */

        .search-pasien {

            position: relative;

            width: 260px;

        }


        .search-pasien i {

            position: absolute;

            left: 12px;

            top: 50%;

            transform: translateY(-50%);

            color: #9aa4b5;

        }


        .search-pasien input {

            width: 100%;

            box-sizing: border-box;

            padding: 10px 12px 10px 36px;

            border: 1px solid #dce3ee;

            border-radius: 8px;

            outline: none;

            font-family: 'Poppins', sans-serif;

            font-size: 12px;

        }


        .search-pasien input:focus {

            border-color: #2864e6;

        }


        /* =========================
           TABLE
        ========================= */

        .table-wrapper {

            overflow-x: auto;

        }


        .pasien-table {

            width: 100%;

            border-collapse: collapse;

        }


        .pasien-table th {

            background: #f8faff;

            color: #68758a;

            font-size: 11px;

            font-weight: 600;

            text-align: left;

            padding: 14px 18px;

            border-bottom: 1px solid #e7ebf2;

            white-space: nowrap;

        }


        .pasien-table td {

            padding: 15px 18px;

            border-bottom: 1px solid #edf0f5;

            font-size: 12px;

            color: #26334a;

            white-space: nowrap;

        }


        .pasien-table tbody tr:hover {

            background: #fafcff;

        }


        .pasien-table tbody tr:last-child td {

            border-bottom: none;

        }


        /* =========================
           NAMA PASIEN
        ========================= */

        .patient-name {

            display: flex;

            align-items: center;

            gap: 10px;

        }


        .patient-avatar {

            width: 34px;

            height: 34px;

            border-radius: 50%;

            background: #e5efff;

            color: #2864e6;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 11px;

            font-weight: 600;

            flex-shrink: 0;

        }


        .patient-info strong {

            display: block;

            font-size: 12px;

            font-weight: 600;

        }


        .patient-info span {

            display: block;

            margin-top: 2px;

            color: #8a95a8;

            font-size: 10px;

        }


        /* =========================
           BADGE
        ========================= */

        .badge {

            display: inline-flex;

            align-items: center;

            padding: 5px 10px;

            border-radius: 20px;

            font-size: 10px;

            font-weight: 500;

        }


        /* =========================
           BADGE LOKASI
        ========================= */

        .badge-dalam {

            background: #d9f8ff;

            color: #00a8d6;

            border: 1px solid #4dd9f5;

        }


        .badge-luar {

            background: #ffe5f2;

            color: #f05aa5;

            border: 1px solid #ff9dcc;

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


        /* =========================
           ACTION
        ========================= */

        .action-buttons {

            display: flex;

            gap: 6px;

        }


        .action-button {

            width: 32px;

            height: 32px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            border-radius: 7px;

            border: 1px solid #e0e6ef;

            background: white;

            color: #647188;

            text-decoration: none;

            font-size: 13px;

            cursor: pointer;

        }


        .action-button:hover {

            background: #f4f7fc;

            color: #2864e6;

        }


        /* =====================================================
           TOPBAR
        ===================================================== */

        .topbar-right {

            display: flex;

            align-items: center;

            justify-content: flex-end;

        }


        /* =====================================================
           PROFILE WRAPPER
        ===================================================== */

        .profile-wrapper {

            position: relative;

        }


        /* =====================================================
           PROFILE BUTTON
        ===================================================== */

        .profile-button {

            display: flex;

            align-items: center;

            gap: 9px;

            padding: 6px 8px 6px 10px;

            border: none;

            background: transparent;

            border-radius: 10px;

            cursor: pointer;

            font-family: 'Poppins', sans-serif;

            transition: all 0.2s ease;

        }


        .profile-button:hover {

            background: #f3f6fb;

        }


        .profile-button.active {

            background: #f3f6fb;

        }


        /* =====================================================
           PROFILE TEXT
        ===================================================== */

        .profile-text {

            display: flex;

            flex-direction: column;

            align-items: flex-end;

            line-height: 1.2;

        }


        .profile-name {

            font-size: 12px;

            font-weight: 600;

            color: #17243a;

        }


        .profile-username {

            margin-top: 2px;

            font-size: 9px;

            color: #8a95a8;

        }


        /* =====================================================
           PROFILE AVATAR BIRU
        ===================================================== */

        .profile-avatar {

            width: 38px !important;

            height: 38px !important;

            min-width: 38px !important;

            min-height: 38px !important;

            display: flex !important;

            align-items: center !important;

            justify-content: center !important;

            border-radius: 50% !important;

            background: #2864e6 !important;

            color: #ffffff !important;

            font-family: 'Poppins', sans-serif !important;

            font-size: 12px !important;

            font-weight: 600 !important;

            text-decoration: none !important;

            box-sizing: border-box !important;

            flex-shrink: 0 !important;

        }


        /* Jangan hilangkan huruf saat hover */

        .profile-avatar:hover {

            background: #2864e6 !important;

            color: #ffffff !important;

        }


        /* =====================================================
           CHEVRON
        ===================================================== */

        .profile-chevron {

            font-size: 10px;

            color: #8a95a8;

            transition: transform 0.2s ease;

        }


        .profile-button.active .profile-chevron {

            transform: rotate(180deg);

        }


        /* =====================================================
           PROFILE DROPDOWN
        ===================================================== */

        .profile-dropdown {

            display: none;

            position: absolute;

            top: 50px;

            right: 0;

            width: 220px;

            background: #ffffff;

            border: 1px solid #e1e7f0;

            border-radius: 12px;

            box-shadow:
                0 12px 35px rgba(23, 36, 58, 0.14);

            z-index: 3000;

            overflow: hidden;

        }


        .profile-dropdown.show {

            display: block;

        }


        /* =====================================================
           DROPDOWN HEADER
        ===================================================== */

        .profile-dropdown-header {

            display: flex;

            align-items: center;

            gap: 11px;

            padding: 16px;

            border-bottom: 1px solid #edf0f5;

        }


        .profile-dropdown-avatar {

            width: 38px;

            height: 38px;

            min-width: 38px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #2864e6;

            color: #ffffff;

            font-size: 12px;

            font-weight: 600;

        }


        .profile-dropdown-info {

            min-width: 0;

        }


        .profile-dropdown-info strong {

            display: block;

            font-size: 12px;

            color: #17243a;

        }


        .profile-dropdown-info span {

            display: block;

            margin-top: 3px;

            font-size: 9px;

            color: #8a95a8;

        }


        /* =====================================================
           DROPDOWN MENU
        ===================================================== */

        .profile-dropdown-menu {

            padding: 6px 0;

        }


        .profile-dropdown-item {

            display: flex;

            align-items: center;

            gap: 11px;

            padding: 11px 16px;

            color: #526078;

            text-decoration: none;

            font-size: 11px;

            transition: all 0.15s ease;

        }


        .profile-dropdown-item:hover {

            background: #f7f9fc;

            color: #2864e6;

        }


        .profile-dropdown-item i {

            width: 18px;

            font-size: 14px;

            text-align: center;

            color: #2864e6;

        }


        /* Logout tetap merah */

        .profile-dropdown-item.logout {

            color: #ef4444;

        }


        .profile-dropdown-item.logout i {

            color: #ef4444;

        }


        .profile-dropdown-item.logout:hover {

            background: #fff5f5;

            color: #ef4444;

        }


        /* =========================
           EMPTY
        ========================= */

        .empty-data {

            text-align: center;

            padding: 45px 20px !important;

            color: #8a95a8 !important;

        }


        /* =========================
           SIDEBAR FIX
        ========================= */

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


        .dashboard-layout .sidebar-brand {

            width: 100% !important;

            height: auto !important;

            padding: 22px 10px 15px !important;

            margin: 0 !important;

            text-align: center !important;

            box-sizing: border-box !important;

        }


        .dashboard-layout .sidebar-brand img {

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


        .dashboard-layout .sidebar-brand h2 {

            margin: 0 !important;

            font-size: 17px !important;

            line-height: 1.3 !important;

        }


        .dashboard-layout .sidebar-brand p {

            margin: 3px 0 0 !important;

            font-size: 9px !important;

            line-height: 1.3 !important;

        }


        .dashboard-layout .main-content {

            margin-left: 218px !important;

            width: calc(100% - 218px) !important;

            min-width: 0 !important;

        }


        /* =========================
           MODAL HAPUS
        ========================= */

        .delete-modal {

            position: fixed;

            inset: 0;

            display: flex;

            align-items: center;

            justify-content: center;

            visibility: hidden;

            opacity: 0;

            z-index: 9999;

            transition: all 0.2s ease;

        }


        .delete-modal.show {

            visibility: visible;

            opacity: 1;

        }


        .delete-modal-overlay {

            position: absolute;

            inset: 0;

            background: rgba(15, 27, 48, 0.45);

        }


        .delete-modal-card {

            position: relative;

            width: 400px;

            max-width: calc(100% - 40px);

            background: #ffffff;

            border-radius: 14px;

            padding: 30px;

            text-align: center;

            box-shadow: 0 15px 45px rgba(23, 36, 58, 0.18);

            transform: translateY(10px);

            transition: all 0.2s ease;

        }


        .delete-modal.show .delete-modal-card {

            transform: translateY(0);

        }


        .delete-icon {

            width: 52px;

            height: 52px;

            margin: 0 auto 15px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #ffe8e8;

            color: #d83b3b;

            font-size: 22px;

        }


        .delete-modal-card h3 {

            margin: 0 0 8px;

            font-size: 18px;

            color: #17243a;

        }


        .delete-modal-card p {

            margin: 0 auto;

            max-width: 330px;

            color: #7a8499;

            font-size: 12px;

            line-height: 1.6;

        }


        .delete-modal-actions {

            display: flex;

            justify-content: center;

            gap: 10px;

            margin-top: 24px;

        }


        .btn-cancel-delete,

        .btn-confirm-delete {

            min-width: 90px;

            height: 38px;

            padding: 0 18px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            border-radius: 8px;

            font-family: 'Poppins', sans-serif;

            font-size: 12px;

            font-weight: 600;

            cursor: pointer;

            text-decoration: none;

            box-sizing: border-box;

        }


        .btn-cancel-delete {

            border: 1px solid #dce3ee;

            background: #ffffff;

            color: #526078;

        }


        .btn-cancel-delete:hover {

            background: #f5f7fa;

        }


        .btn-confirm-delete {

            border: 1px solid #d83b3b;

            background: #d83b3b;

            color: #ffffff;

        }


        .btn-confirm-delete:hover {

            background: #c92f2f;

            color: #ffffff;

        }


        body.modal-open {

            overflow: hidden;

        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 800px) {

            .page-content {

                padding: 18px;

            }


            .page-header {

                align-items: flex-start;

                flex-direction: column;

                gap: 15px;

            }


            .data-toolbar {

                align-items: flex-start;

                flex-direction: column;

                gap: 15px;

            }


            .search-pasien {

                width: 220px;

            }


            .delete-modal-card {

                padding: 25px 20px;

            }

        }


        @media (max-width: 600px) {

            .profile-text {

                display: none;

            }

            .profile-button {

                gap: 5px;

            }

            .profile-dropdown {

                right: -5px;

            }

        }

    </style>

</head>


<body>


<div class="dashboard-layout">


    <!-- =========================
         SIDEBAR
    ========================= -->

    <aside class="sidebar">


        <div class="sidebar-brand">

            <img
                src="/SIPM-ODGJ/assets/img/logo YCKA.png"
                alt="Logo Yayasan Cahaya Kasih Amanah"
                class="sidebar-logo"
            >

            <h2>
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

                <i class="bi bi-grid-1x2"></i>

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


    <!-- =========================
         MAIN CONTENT
    ========================= -->

    <main class="main-content">


        <!-- =========================
             TOPBAR
        ========================= -->

        <header class="topbar">


            <h1>
                Data Pasien
            </h1>


            <div class="topbar-right">


                <!-- =========================
                     PROFILE
                ========================= -->

                <div class="profile-wrapper">


                    <button
                        type="button"
                        class="profile-button"
                        id="profileButton"
                        aria-label="Menu Profil"
                    >


                        <!-- NAMA -->

                        <div class="profile-text">

                            <span class="profile-name">
                                <?= htmlspecialchars($nama_user); ?>
                            </span>

                            <span class="profile-username">
                                <?= htmlspecialchars($username_user); ?>
                            </span>

                        </div>


                        <!-- AVATAR BIRU -->

                        <div class="profile-avatar">
                            <?= htmlspecialchars($inisial_user); ?>
                        </div>


                        <!-- CHEVRON -->

                        <i class="bi bi-chevron-down profile-chevron"></i>


                    </button>


                    <!-- =========================
                         DROPDOWN PROFILE
                    ========================= -->

                    <div
                        class="profile-dropdown"
                        id="profileDropdown"
                    >


                        <!-- HEADER DROPDOWN -->

                        <div class="profile-dropdown-header">


                            <div class="profile-dropdown-avatar">
                                <?= htmlspecialchars($inisial_user); ?>
                            </div>


                            <div class="profile-dropdown-info">

                                <strong>
                                    <?= htmlspecialchars($nama_user); ?>
                                </strong>

                                <span>
                                    <?= htmlspecialchars($username_user); ?>
                                </span>

                            </div>


                        </div>


                        <!-- MENU -->

                        <div class="profile-dropdown-menu">


                            <!-- PROFIL -->

                            <a
                                href="profil.php"
                                class="profile-dropdown-item"
                            >

                                <i class="bi bi-person-circle"></i>

                                <span>
                                    Profil Saya
                                </span>

                            </a>


                            <!-- LOGOUT -->

                            <a
                                href="logout.php"
                                class="profile-dropdown-item logout"
                            >

                                <i class="bi bi-box-arrow-left"></i>

                                <span>
                                    Logout
                                </span>

                            </a>


                        </div>


                    </div>


                </div>


            </div>


        </header>


        <!-- =========================
             CONTENT
        ========================= -->

        <div class="page-content">


            <div class="page-header">


                <div class="page-title">

                    <h2>
                        Data Pasien
                    </h2>

                    <p>
                        Kelola data pasien ODGJ Yayasan Cahaya Kasih Amanah
                    </p>

                </div>


                <a
                    href="tambah_pasien.php"
                    class="add-button"
                >

                    <i class="bi bi-plus-lg"></i>

                    Tambah Pasien

                </a>


            </div>


            <!-- =========================
                 DATA CARD
            ========================= -->

            <div class="data-card">


                <div class="data-toolbar">


                    <h3>
                        Daftar Pasien
                    </h3>


                    <!-- SEARCH DATA PASIEN -->

                    <div class="search-pasien">


                        <i class="bi bi-search"></i>


                        <input
                            type="text"
                            id="searchPasien"
                            placeholder="Cari pasien..."
                        >


                    </div>


                </div>


                <div class="table-wrapper">


                    <table class="pasien-table">


                        <thead>

                            <tr>

                                <th>
                                    No
                                </th>

                                <th>
                                    Pasien
                                </th>

                                <th>
                                    Jenis Kelamin
                                </th>

                                <th>
                                    Tempat, Tanggal Lahir
                                </th>

                                <th>
                                    Lokasi
                                </th>

                                <th>
                                    Kondisi
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Aksi
                                </th>

                            </tr>

                        </thead>


                        <tbody id="pasienTable">


                        <?php

                        $no = 1;

                        if (mysqli_num_rows($result) > 0):

                            while ($pasien = mysqli_fetch_assoc($result)):

                                $nama = $pasien['nama_pasien'];

                                $inisial = strtoupper(
                                    substr($nama, 0, 1)
                                );

                        ?>


                            <tr>


                                <!-- NO -->

                                <td>
                                    <?= $no++; ?>
                                </td>


                                <!-- PASIEN -->

                                <td>


                                    <div class="patient-name">


                                        <div class="patient-avatar">

                                            <?= htmlspecialchars($inisial); ?>

                                        </div>


                                        <div class="patient-info">


                                            <strong>

                                                <?= htmlspecialchars($nama); ?>

                                            </strong>


                                            <span>

                                                <?= htmlspecialchars(
                                                    $pasien['nomor_registrasi']
                                                ); ?>

                                            </span>


                                        </div>


                                    </div>


                                </td>


                                <!-- JENIS KELAMIN -->

                                <td>

                                    <?= htmlspecialchars(
                                        $pasien['jenis_kelamin']
                                    ); ?>

                                </td>


                                <!-- TEMPAT TANGGAL LAHIR -->

                                <td>

                                    <?= htmlspecialchars(
                                        $pasien['tempat_lahir']
                                    ); ?>,

                                    <?= date(
                                        'd-m-Y',
                                        strtotime($pasien['tanggal_lahir'])
                                    ); ?>

                                </td>


                                <!-- LOKASI -->

                                <td>


                                    <?php if (
                                        $pasien['status_lokasi'] === 'Dalam Yayasan'
                                    ): ?>


                                        <span class="badge badge-dalam">

                                            Dalam Yayasan

                                        </span>


                                    <?php else: ?>


                                        <span class="badge badge-luar">

                                            Luar Yayasan

                                        </span>


                                    <?php endif; ?>


                                </td>


                                <!-- KONDISI -->

                                <td>


                                    <?php

                                    if ($pasien['kondisi'] === 'Stabil') {

                                        $class = 'badge-stabil';

                                    } elseif (
                                        $pasien['kondisi'] === 'Perlu Pantauan'
                                    ) {

                                        $class = 'badge-pantauan';

                                    } else {

                                        $class = 'badge-darurat';

                                    }

                                    ?>


                                    <span class="badge <?= $class; ?>">

                                        <?= htmlspecialchars(
                                            $pasien['kondisi']
                                        ); ?>

                                    </span>


                                </td>


                                <!-- STATUS -->

                                <td>


                                    <?php if (
                                        $pasien['status_pasien'] === 'Aktif'
                                    ): ?>


                                        <span class="badge badge-stabil">

                                            Aktif

                                        </span>


                                    <?php else: ?>


                                        <span class="badge badge-pantauan">

                                            Tidak Aktif

                                        </span>


                                    <?php endif; ?>


                                </td>


                                <!-- AKSI -->

                                <td>


                                    <div class="action-buttons">


                                        <!-- LIHAT -->

                                        <a
                                            href="detail_pasien.php?id=<?= (int) $pasien['id_pasien']; ?>"
                                            class="action-button"
                                            title="Lihat Detail"
                                        >

                                            <i class="bi bi-eye"></i>

                                        </a>


                                        <!-- EDIT -->

                                        <a
                                            href="edit_pasien.php?id=<?= (int) $pasien['id_pasien']; ?>"
                                            class="action-button"
                                            title="Edit"
                                        >

                                            <i class="bi bi-pencil"></i>

                                        </a>


                                        <!-- HAPUS -->

                                        <button
                                            type="button"
                                            class="action-button"
                                            title="Hapus"
                                            onclick="openDeleteModal(<?= (int) $pasien['id_pasien']; ?>)"
                                        >

                                            <i class="bi bi-trash"></i>

                                        </button>


                                    </div>


                                </td>


                            </tr>


                        <?php

                            endwhile;

                        else:

                        ?>


                            <tr>


                                <td
                                    colspan="8"
                                    class="empty-data"
                                >

                                    Belum ada data pasien.

                                </td>


                            </tr>


                        <?php endif; ?>


                        </tbody>


                    </table>


                </div>


            </div>


        </div>


    </main>


</div>


<!-- =========================
     SEARCH DATA PASIEN
========================= -->

<script>

const searchInput =
    document.getElementById("searchPasien");

const table =
    document.getElementById("pasienTable");


if (searchInput && table) {


    searchInput.addEventListener("keyup", function () {


        const keyword =
            this.value.toLowerCase().trim();


        const rows =
            table.querySelectorAll("tr");


        rows.forEach(function (row) {


            const text =
                row.innerText.toLowerCase();


            row.style.display =
                text.includes(keyword)
                    ? ""
                    : "none";


        });


    });


}

</script>


<!-- =========================
     PROFILE DROPDOWN
========================= -->

<script>

document.addEventListener("DOMContentLoaded", function () {


    const profileButton =
        document.getElementById("profileButton");


    const profileDropdown =
        document.getElementById("profileDropdown");


    if (!profileButton || !profileDropdown) {

        return;

    }


    /* =========================
       KLIK PROFILE
    ========================= */

    profileButton.addEventListener("click", function (event) {

        event.preventDefault();

        event.stopPropagation();


        profileDropdown.classList.toggle("show");

        profileButton.classList.toggle("active");

    });


    /* =========================
       KLIK DI DALAM DROPDOWN
    ========================= */

    profileDropdown.addEventListener("click", function (event) {

        event.stopPropagation();

    });


    /* =========================
       KLIK DI LUAR
    ========================= */

    document.addEventListener("click", function () {

        profileDropdown.classList.remove("show");

        profileButton.classList.remove("active");

    });


    /* =========================
       ESCAPE
    ========================= */

    document.addEventListener("keydown", function (event) {

        if (event.key === "Escape") {

            profileDropdown.classList.remove("show");

            profileButton.classList.remove("active");

        }

    });


});

</script>


<!-- =========================
     MODAL HAPUS PASIEN
========================= -->

<div
    id="deleteModal"
    class="delete-modal"
>


    <div
        class="delete-modal-overlay"
        onclick="closeDeleteModal()"
    >
    </div>


    <div class="delete-modal-card">


        <div class="delete-icon">

            <i class="bi bi-trash3"></i>

        </div>


        <h3>
            Hapus Data Pasien?
        </h3>


        <p>

            Apakah Anda yakin ingin menghapus data pasien ini?
            Data yang sudah dihapus tidak dapat dikembalikan.

        </p>


        <div class="delete-modal-actions">


            <button
                type="button"
                class="btn-cancel-delete"
                onclick="closeDeleteModal()"
            >

                Batal

            </button>


            <a
                href="#"
                id="confirmDeleteButton"
                class="btn-confirm-delete"
            >

                Hapus

            </a>


        </div>


    </div>


</div>


<script>

/* =========================
   MODAL HAPUS PASIEN
========================= */

function openDeleteModal(id) {


    const modal =
        document.getElementById("deleteModal");


    const deleteButton =
        document.getElementById("confirmDeleteButton");


    deleteButton.href =
        "hapus_pasien.php?id=" + id;


    modal.classList.add("show");

    document.body.classList.add("modal-open");

}


function closeDeleteModal() {


    const modal =
        document.getElementById("deleteModal");


    modal.classList.remove("show");

    document.body.classList.remove("modal-open");

}


document.addEventListener("keydown", function(event) {


    if (event.key === "Escape") {

        closeDeleteModal();

    }

});

</script>


</body>

</html>