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

$id_user_login = (int) $_SESSION['id_user'];

$query_user_login = mysqli_query(
    $conn,
    "SELECT nama_lengkap, username
     FROM users
     WHERE id_user = $id_user_login
     LIMIT 1"
);

$user_login = mysqli_fetch_assoc($query_user_login);

$nama_user_login = $user_login['nama_lengkap'] ?? 'Admin';
$username_login  = $user_login['username'] ?? 'admin';


/* =========================
   AMBIL INISIAL USER
========================= */

$nama_parts = preg_split('/\s+/', trim($nama_user_login));

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
   AMBIL DATA USER
========================= */

$query = mysqli_query(
    $conn,
    "SELECT
        id_user,
        nama_lengkap,
        username,
        role,
        status,
        created_at
     FROM users
     ORDER BY id_user DESC"
);

if (!$query) {
    die("Query gagal: " . mysqli_error($conn));
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
        Data User - Sistem Informasi ODGJ
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
           PAGE
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
            color: #17243a;
        }

        .page-title p {
            margin: 6px 0 0;
            color: #7a8499;
            font-size: 13px;
        }


        /* =========================
           TOPBAR PROFILE
        ========================= */

        .topbar-right {
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }

        .profile-wrapper {
            position: relative;
        }

        .profile-trigger {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 5px 8px 5px 10px;
            border: none;
            border-radius: 10px;
            background: transparent;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            transition: all 0.2s ease;
        }

        .profile-trigger:hover {
            background: #f3f6fc;
        }

        .profile-info {
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

        /* AVATAR BIRU */

        .profile-avatar {
            width: 38px;
            height: 38px;
            min-width: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;

            background: #2864e6;
            color: #ffffff;

            font-size: 13px;
            font-weight: 600;

            text-decoration: none;
            box-sizing: border-box;
        }

        .profile-chevron {
            color: #8a95a8;
            font-size: 11px;
            margin-left: 1px;
            transition: transform 0.2s ease;
        }

        .profile-trigger.active .profile-chevron {
            transform: rotate(180deg);
        }


        /* =========================
           PROFILE DROPDOWN
        ========================= */

        .profile-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;

            width: 220px;

            background: #ffffff;
            border: 1px solid #e1e7f0;
            border-radius: 12px;

            box-shadow:
                0 12px 35px rgba(23, 36, 58, 0.12);

            padding: 10px;

            z-index: 9999;

            opacity: 0;
            visibility: hidden;
            transform: translateY(-6px);

            transition:
                opacity 0.2s ease,
                visibility 0.2s ease,
                transform 0.2s ease;
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

            padding: 8px 9px 12px;
        }

        .profile-dropdown-avatar {
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

        .profile-dropdown-user {
            min-width: 0;
        }

        .profile-dropdown-name {
            display: block;

            color: #17243a;
            font-size: 12px;
            font-weight: 600;

            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .profile-dropdown-username {
            display: block;

            margin-top: 2px;

            color: #8a95a8;
            font-size: 9px;
        }

        .profile-divider {
            height: 1px;
            background: #edf0f5;
            margin: 0 0 5px;
        }

        .profile-menu-item {
            display: flex;
            align-items: center;
            gap: 10px;

            width: 100%;
            padding: 10px 9px;

            border-radius: 7px;

            text-decoration: none;

            font-size: 11px;
            font-weight: 400;

            box-sizing: border-box;

            transition: all 0.2s ease;
        }

        .profile-menu-item i {
            width: 16px;
            font-size: 14px;
        }

        .profile-menu-item.profile-link {
            color: #526078;
        }

        .profile-menu-item.profile-link i {
            color: #526078;
        }

        .profile-menu-item.profile-link:hover {
            background: #f4f7fc;
            color: #2864e6;
        }

        .profile-menu-item.profile-link:hover i {
            color: #2864e6;
        }

        .profile-menu-item.logout-link {
            color: #e53939;
        }

        .profile-menu-item.logout-link i {
            color: #e53939;
        }

        .profile-menu-item.logout-link:hover {
            background: #fff1f1;
            color: #d82f2f;
        }


        /* =========================
           DATA CARD
        ========================= */

        .data-card {
            background: white;
            border: 1px solid #e1e7f0;
            border-radius: 12px;
            overflow: hidden;
        }


        /* =========================
           CARD HEADER
        ========================= */

        .card-header {
            display: flex !important;
            width: 100% !important;
            box-sizing: border-box !important;
            justify-content: space-between !important;
            align-items: center !important;
            padding: 20px 24px !important;
            border-bottom: 1px solid #edf0f5 !important;
            gap: 16px !important;
            float: none !important;
            position: relative !important;
        }

        .card-title {
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
            flex: 0 0 auto !important;
            width: auto !important;
            float: none !important;
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

        .card-title h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #17243a;
        }

        .card-actions {
            display: flex !important;
            align-items: center !important;
            justify-content: flex-end !important;
            gap: 12px !important;
            margin-left: auto !important;
            flex: 0 0 auto !important;
            width: 260px !important;
            max-width: 260px !important;
            float: none !important;
        }


        /* =========================
           TAMBAH USER
        ========================= */

        .btn-tambah-user {
            display: inline-flex;
            align-items: center;
            gap: 7px;

            padding: 10px 16px;

            border-radius: 8px;

            background: #2864e6;
            color: white;

            text-decoration: none;

            font-family: 'Poppins', sans-serif;
            font-size: 12px;
            font-weight: 500;

            white-space: nowrap;
        }

        .btn-tambah-user:hover {
            background: #1f56cc;
            color: white;
        }


        /* =========================
           SEARCH TABLE
        ========================= */

        .search-box-table {
            position: relative;
            width: 260px;
        }

        .search-box-table i {
            position: absolute;
            left: 12px;
            top: 50%;

            transform: translateY(-50%);

            color: #8a95a8;
        }

        .search-box-table input {
            width: 100%;
            box-sizing: border-box;

            padding: 10px 12px 10px 36px;

            border: 1px solid #dce3ee;
            border-radius: 8px;

            outline: none;

            font-family: 'Poppins', sans-serif;
            font-size: 12px;
        }

        .search-box-table input:focus {
            border-color: #2864e6;
        }


        /* =========================
           TABLE
        ========================= */

        .table-wrapper {
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;

            margin: 0 !important;
            padding: 0 !important;

            overflow-x: auto !important;

            float: none !important;
            clear: both !important;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            padding: 14px 18px;

            background: #f7f9fc;

            color: #6f7c91;

            text-align: left;

            font-size: 11px;
            font-weight: 600;

            white-space: nowrap;
        }

        td {
            padding: 16px 18px;

            border-top: 1px solid #edf0f5;

            color: #26334a;

            font-size: 12px;

            vertical-align: middle;
        }

        .user-name {
            font-weight: 600;
            color: #17243a;
        }

        .username {
            color: #718096;
        }


        /* =========================
           BADGE ROLE & STATUS
        ========================= */

        .badge {
            display: inline-flex;
            align-items: center;

            padding: 6px 11px;

            border-radius: 20px;

            font-size: 10px;
            font-weight: 500;
        }


        /* ROLE ADMIN */

        .badge-admin {
            background: #e5efff;
            color: #2864e6;
        }


        /* ROLE PETUGAS */

        .badge-petugas {
            background: #fff0dc;
            color: #e58a00;
        }


        /* ROLE KELUARGA */

        .badge-keluarga {
            background: #ffe5ef;
            color: #e84f83;
        }


        /* STATUS AKTIF */

        .badge-aktif {
            background: #dcf8e8;
            color: #159447;
        }


        /* STATUS NONAKTIF */

        .badge-nonaktif {
            background: #ffe1e1;
            color: #d83b3b;
        }


        /* =========================
           ACTION BUTTON
        ========================= */

        .action-buttons {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .action-button {
            width: 34px;
            height: 34px;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            border-radius: 8px;
            border: none;

            text-decoration: none;

            font-size: 14px;

            cursor: pointer;

            transition: all 0.2s ease;
        }


        /* LIHAT */

        .action-button.view {
            background: #eaf1ff;
            color: #2864e6;
        }

        .action-button.view:hover {
            background: #2864e6;
            color: #ffffff;
        }


        /* UPDATE */

        .action-button.edit {
            background: #fff3dc;
            color: #e89a00;
        }

        .action-button.edit:hover {
            background: #e89a00;
            color: #ffffff;
        }


        /* HAPUS */

        .action-button.delete {
            background: #ffe5e5;
            color: #dc3b3b;
        }

        .action-button.delete:hover {
            background: #dc3b3b;
            color: #ffffff;
        }


        /* =========================
           EMPTY DATA
        ========================= */

        .empty-data {
            padding: 40px !important;
            text-align: center;
            color: #8a95a8;
        }

        .empty-data i {
            display: block;
            font-size: 32px;
            margin-bottom: 10px;
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

            object-fit: contain !important;

            margin: 0 auto 7px !important;
        }

        .dashboard-layout .sidebar-logo h2 {
            margin: 0 !important;
            font-size: 17px !important;
        }

        .dashboard-layout .sidebar-logo p {
            margin: 3px 0 0 !important;
            font-size: 9px !important;
        }

        .dashboard-layout .main-content {
            margin-left: 218px !important;

            width: calc(100% - 218px) !important;

            min-width: 0 !important;
        }


        /* =========================
           DELETE MODAL
        ========================= */

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

            background: white;

            border-radius: 14px;

            text-align: center;

            box-shadow:
                0 20px 50px
                rgba(15, 23, 42, 0.18);

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
            background: white;

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

            color: white;
        }

        .confirm-delete:hover {
            background: #c92f2f;
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

            .page-header .btn-tambah-user {
                align-self: flex-start;
            }

            .card-header {
                align-items: flex-start;
                flex-direction: column;
                gap: 15px;
            }

            .card-actions {
                width: 100% !important;
                max-width: 100% !important;

                flex-direction: column;
                align-items: stretch;
            }

            .btn-tambah-user {
                justify-content: center;
            }

            .search-box-table {
                width: 100%;
            }

            .profile-info {
                display: none;
            }

            .profile-trigger {
                padding: 4px;
            }

        }


        /* =========================
           WARNA SIPM ODGJ
        ========================= */

        .dashboard-layout .sidebar .sidebar-logo h2.sipm-title {
            color: #2864e6 !important;
            -webkit-text-fill-color: #2864e6 !important;
        }

    </style>

</head>


<body>


<div class="dashboard-layout">


    <!-- =========================
         SIDEBAR
    ========================= -->

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
                <span>Dashboard</span>
            </a>


            <a
                href="data_pasien.php"
                class="menu-item"
            >
                <i class="bi bi-people"></i>
                <span>Data Pasien</span>
            </a>


            <a
                href="data_user.php"
                class="menu-item active"
            >
                <i class="bi bi-person-gear"></i>
                <span>Data User</span>
            </a>


            <a
                href="monitoring.php"
                class="menu-item"
            >
                <i class="bi bi-clipboard2-pulse"></i>
                <span>Monitoring</span>
            </a>


            <a
                href="laporan.php"
                class="menu-item"
            >
                <i class="bi bi-file-earmark-bar-graph"></i>
                <span>Laporan</span>
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


    <!-- =========================
         MAIN CONTENT
    ========================= -->

    <main class="main-content">


        <!-- =========================
             TOPBAR
        ========================= -->

        <header class="topbar">

            <h1>
                Data User
            </h1>


            <div class="topbar-right">

                <!-- PROFILE -->

                <div class="profile-wrapper">

                    <button
                        type="button"
                        class="profile-trigger"
                        id="profileTrigger"
                    >

                        <!-- NAMA -->

                        <div class="profile-info">

                            <span class="profile-name">
                                <?= htmlspecialchars($nama_user_login); ?>
                            </span>

                            <span class="profile-username">
                                <?= htmlspecialchars($username_login); ?>
                            </span>

                        </div>


                        <!-- AVATAR BIRU -->

                        <div class="profile-avatar">

                            <?= htmlspecialchars($inisial_user); ?>

                        </div>


                        <!-- CHEVRON -->

                        <i
                            class="bi bi-chevron-down profile-chevron"
                        ></i>

                    </button>


                    <!-- =========================
                         DROPDOWN PROFILE
                    ========================= -->

                    <div
                        class="profile-dropdown"
                        id="profileDropdown"
                    >


                        <div class="profile-dropdown-header">


                            <div class="profile-dropdown-avatar">

                                <?= htmlspecialchars($inisial_user); ?>

                            </div>


                            <div class="profile-dropdown-user">

                                <span class="profile-dropdown-name">

                                    <?= htmlspecialchars($nama_user_login); ?>

                                </span>

                                <span class="profile-dropdown-username">

                                    <?= htmlspecialchars($username_login); ?>

                                </span>

                            </div>


                        </div>


                        <div class="profile-divider"></div>


                        <!-- PROFIL SAYA -->

                        <a
                            href="profil.php"
                            class="profile-menu-item profile-link"
                        >

                            <i class="bi bi-person-circle"></i>

                            <span>
                                Profil Saya
                            </span>

                        </a>


                        <!-- LOGOUT -->

                        <a
                            href="logout.php"
                            class="profile-menu-item logout-link"
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


        <!-- =========================
             PAGE CONTENT
        ========================= -->

        <div class="page-content">


            <!-- PAGE HEADER -->

            <div class="page-header">

                <div class="page-title">

                    <h2>
                        Data User
                    </h2>

                    <p>
                        Kelola data pengguna sistem
                    </p>

                </div>


                <a
                    href="tambah_user.php"
                    class="btn-tambah-user"
                >

                    <i class="bi bi-plus-lg"></i>

                    <span>
                        Tambah User
                    </span>

                </a>

            </div>


            <!-- =========================
                 DATA CARD
            ========================= -->

            <div class="data-card">


                <!-- CARD HEADER -->

                <div class="card-header">


                    <div class="card-title">

                        <div class="section-icon">

                            <i class="bi bi-person-gear"></i>

                        </div>

                        <h3>
                            Daftar User
                        </h3>

                    </div>


                    <!-- SEARCH DATA USER -->

                    <div class="card-actions">

                        <div class="search-box-table">

                            <i class="bi bi-search"></i>

                            <input
                                type="text"
                                id="searchUser"
                                placeholder="Cari user..."
                            >

                        </div>

                    </div>

                </div>


                <!-- =========================
                     TABLE
                ========================= -->

                <div class="table-wrapper">

                    <table id="userTable">

                        <thead>

                            <tr>

                                <th>
                                    Nama Lengkap
                                </th>

                                <th>
                                    Username
                                </th>

                                <th>
                                    Role
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Dibuat
                                </th>

                                <th>
                                    Aksi
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php if (mysqli_num_rows($query) > 0): ?>


                            <?php while ($user = mysqli_fetch_assoc($query)): ?>

                                <tr>


                                    <!-- NAMA -->

                                    <td class="user-name">

                                        <?= htmlspecialchars(
                                            $user['nama_lengkap']
                                        ); ?>

                                    </td>


                                    <!-- USERNAME -->

                                    <td class="username">

                                        <?= htmlspecialchars(
                                            $user['username']
                                        ); ?>

                                    </td>


                                    <!-- ROLE -->

                                    <td>

                                        <?php if ($user['role'] === 'admin'): ?>

                                            <span class="badge badge-admin">
                                                Admin
                                            </span>

                                        <?php elseif ($user['role'] === 'petugas'): ?>

                                            <span class="badge badge-petugas">
                                                Petugas
                                            </span>

                                        <?php elseif ($user['role'] === 'keluarga'): ?>

                                            <span class="badge badge-keluarga">
                                                Keluarga
                                            </span>

                                        <?php else: ?>

                                            <span class="badge badge-keluarga">

                                                <?= htmlspecialchars(
                                                    $user['role']
                                                ); ?>

                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <?php if ($user['status'] === 'aktif'): ?>

                                            <span class="badge badge-aktif">
                                                Aktif
                                            </span>

                                        <?php else: ?>

                                            <span class="badge badge-nonaktif">

                                                <?= htmlspecialchars(
                                                    $user['status']
                                                ); ?>

                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- DIBUAT -->

                                    <td>

                                        <?= !empty($user['created_at'])
                                            ? date(
                                                'd-m-Y H:i',
                                                strtotime(
                                                    $user['created_at']
                                                )
                                            )
                                            : '-';
                                        ?>

                                    </td>


                                    <!-- AKSI -->

                                    <td>

                                        <div class="action-buttons">


                                            <!-- LIHAT -->

                                            <a
                                                href="detail_user.php?id=<?= (int) $user['id_user']; ?>"
                                                class="action-button view"
                                                title="Lihat User"
                                            >

                                                <i class="bi bi-eye"></i>

                                            </a>


                                            <!-- UPDATE -->

                                            <a
                                                href="edit_user.php?id=<?= (int) $user['id_user']; ?>"
                                                class="action-button edit"
                                                title="Update User"
                                            >

                                                <i class="bi bi-pencil"></i>

                                            </a>


                                            <!-- HAPUS -->

                                            <button
                                                type="button"
                                                class="action-button delete"
                                                title="Hapus User"

                                                onclick="openDeleteModal(
                                                    <?= (int) $user['id_user']; ?>,
                                                    <?= htmlspecialchars(
                                                        json_encode(
                                                            $user['nama_lengkap'],
                                                            JSON_UNESCAPED_UNICODE
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>
                                                )"
                                            >

                                                <i class="bi bi-trash"></i>

                                            </button>


                                        </div>

                                    </td>


                                </tr>

                            <?php endwhile; ?>


                        <?php else: ?>


                            <tr>

                                <td
                                    colspan="6"
                                    class="empty-data"
                                >

                                    <i class="bi bi-person-x"></i>

                                    Belum ada data user.

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
     SEARCH USER
========================= -->

<script>

const searchInput =
    document.getElementById('searchUser');

const tableRows =
    document.querySelectorAll('#userTable tbody tr');


if (searchInput) {

    searchInput.addEventListener(
        'keyup',
        function () {

            const keyword =
                this.value.toLowerCase().trim();


            tableRows.forEach(
                function (row) {

                    const text =
                        row.innerText.toLowerCase();

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


<!-- =========================
     PROFILE DROPDOWN
========================= -->

<script>

const profileTrigger =
    document.getElementById('profileTrigger');

const profileDropdown =
    document.getElementById('profileDropdown');


if (profileTrigger && profileDropdown) {


    profileTrigger.addEventListener(
        'click',
        function (event) {

            event.stopPropagation();

            profileDropdown.classList.toggle('show');

            profileTrigger.classList.toggle('active');

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

            profileDropdown.classList.remove('show');

            profileTrigger.classList.remove('active');

        }
    );

}

</script>


<!-- =========================
     MODAL KONFIRMASI HAPUS
========================= -->

<div
    id="deleteModal"
    class="delete-modal"
>

    <div class="delete-modal-overlay"></div>


    <div class="delete-modal-content">


        <div class="delete-icon">

            <i class="bi bi-trash3"></i>

        </div>


        <h3>
            Hapus User?
        </h3>


        <p>

            Apakah Anda yakin ingin menghapus user

            <strong id="deleteUserName"></strong>?

        </p>


        <span class="delete-warning">

            Data user yang dihapus tidak dapat dikembalikan.

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


<!-- =========================
     DELETE MODAL SCRIPT
========================= -->

<script>

function openDeleteModal(id, name) {

    const modal =
        document.getElementById('deleteModal');

    const userName =
        document.getElementById('deleteUserName');

    const confirmButton =
        document.getElementById('confirmDeleteButton');


    if (!modal || !userName || !confirmButton) {
        return;
    }


    userName.textContent = name;

    confirmButton.href =
        'hapus_user.php?id=' +
        encodeURIComponent(id);


    modal.classList.add('show');

}


function closeDeleteModal() {

    const modal =
        document.getElementById('deleteModal');

    if (modal) {

        modal.classList.remove('show');

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

                if (event.key === 'Escape') {

                    closeDeleteModal();

                }

            }
        );

    }
);

</script>


</body>

</html>