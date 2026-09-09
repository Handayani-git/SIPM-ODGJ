<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

require_once "koneksi.php";


/* =========================================================
   USER YANG SEDANG LOGIN
   INI KHUSUS UNTUK PROFIL TOPBAR
========================================================= */

$id_user_login = (int) $_SESSION['id_user'];

$stmt_login = mysqli_prepare(
    $conn,
    "SELECT
        id_user,
        nama_lengkap,
        username,
        role,
        status
     FROM users
     WHERE id_user = ?
     LIMIT 1"
);

if (!$stmt_login) {
    die("Query user login gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt_login,
    "i",
    $id_user_login
);

mysqli_stmt_execute($stmt_login);

$result_login = mysqli_stmt_get_result($stmt_login);

$user_login = mysqli_fetch_assoc($result_login);

mysqli_stmt_close($stmt_login);


if (!$user_login) {
    session_destroy();
    header("Location: login.php");
    exit;
}


/* =========================================================
   DATA USER LOGIN UNTUK TOPBAR
========================================================= */

$nama_user_login =
    $user_login['nama_lengkap'] ?? 'Admin';

$username_user_login =
    $user_login['username'] ?? '';

$role_user_login =
    strtolower($user_login['role'] ?? 'admin');


/* =========================================================
   INISIAL USER LOGIN
========================================================= */

$inisial_login = '';

$nama_parts_login = preg_split(
    '/\s+/',
    trim($nama_user_login)
);

foreach (
    array_slice($nama_parts_login, 0, 1) as $part
) {

    if ($part !== '') {

        $inisial_login .= strtoupper(
            substr($part, 0, 1)
        );

    }

}

if ($inisial_login === '') {
    $inisial_login = 'A';
}


/* =========================================================
   CEK ROLE
========================================================= */

if ($role_user_login !== 'admin') {
    die("Anda tidak memiliki akses ke halaman ini.");
}


/* =========================================================
   CEK ID USER YANG AKAN DILIHAT
========================================================= */

if (
    !isset($_GET['id']) ||
    !is_numeric($_GET['id'])
) {

    header("Location: data_user.php");
    exit;

}


/* =========================================================
   ID USER YANG SEDANG DILIHAT
========================================================= */

$id_user_detail = (int) $_GET['id'];


/* =========================================================
   AMBIL DATA USER YANG DILIHAT
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        id_user,
        nama_lengkap,
        username,
        role,
        status,
        created_at
     FROM users
     WHERE id_user = ?
     LIMIT 1"
);

if (!$stmt) {
    die("Query detail user gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_user_detail
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$user = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* =========================================================
   CEK DATA USER
========================================================= */

if (!$user) {
    die("Data user tidak ditemukan.");
}


/* =========================================================
   INISIAL USER YANG DILIHAT
========================================================= */

$inisial = strtoupper(
    substr(
        trim($user['nama_lengkap']),
        0,
        1
    )
);

if ($inisial === '') {
    $inisial = 'U';
}


/* =========================================================
   FORMAT ROLE USER YANG DILIHAT
========================================================= */

$role = strtolower(
    $user['role'] ?? ''
);

if ($role === 'admin') {

    $role_class = 'badge-admin';
    $role_text = 'Admin';

} else {

    $role_class = 'badge-user';
    $role_text = ucfirst(
        $user['role'] ?? '-'
    );

}


/* =========================================================
   FORMAT STATUS USER YANG DILIHAT
========================================================= */

$status = strtolower(
    $user['status'] ?? ''
);

if ($status === 'aktif') {

    $status_class = 'badge-aktif';
    $status_text = 'Aktif';

} else {

    $status_class = 'badge-nonaktif';
    $status_text = ucfirst(
        $user['status'] ?? '-'
    );

}


/* =========================================================
   FORMAT TANGGAL
========================================================= */

$created_at = !empty($user['created_at'])
    ? date(
        'd-m-Y H:i',
        strtotime($user['created_at'])
    )
    : '-';

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
        Detail User - Sistem Informasi ODGJ
    </title>


    <!-- =====================================================
         GOOGLE FONT
    ====================================================== -->

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


    <!-- =====================================================
         BOOTSTRAP ICON
    ====================================================== -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <!-- =====================================================
         DASHBOARD CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="/SIPM-ODGJ/assets/css/dashboard.css"
    >


    <style>

        /* =====================================================
           RESET
        ===================================================== */

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            min-height: 100%;
        }

        body {
            overflow-x: hidden;
            font-family: 'Poppins', sans-serif;
            background: #f6f8fc;
            color: #17243a;
        }


        /* =====================================================
           LAYOUT
        ===================================================== */

        .dashboard-layout {
            display: flex !important;
            width: 100%;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }


        /* =====================================================
           SIDEBAR
        ===================================================== */

        .dashboard-layout .sidebar {
            width: 218px !important;
            min-width: 218px !important;
            max-width: 218px !important;

            height: 100vh !important;

            position: fixed !important;
            left: 0 !important;
            top: 0 !important;

            overflow: hidden !important;

            z-index: 1000 !important;
        }


        .dashboard-layout .sidebar-logo {
            width: 100% !important;

            height: auto !important;

            padding: 22px 10px 15px !important;

            margin: 0 !important;

            text-align: center !important;
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


        /* =====================================================
           MAIN CONTENT
        ===================================================== */

        .dashboard-layout .main-content {
            margin-left: 218px !important;

            width: calc(100% - 218px) !important;

            min-width: 0 !important;

            min-height: 100vh !important;

            position: relative !important;
        }


        /* =====================================================
           TOPBAR
        ===================================================== */

        .topbar {
            width: 100% !important;

            height: 76px !important;
            min-height: 76px !important;

            padding: 0 32px !important;

            margin: 0 !important;

            display: flex !important;

            align-items: center !important;

            justify-content: space-between !important;

            background: #ffffff;

            border-bottom: 1px solid #dfe7f1;

            position: relative !important;

            z-index: 100 !important;
        }


        .topbar h1 {
            margin: 0 !important;

            font-size: 16px !important;

            font-weight: 700 !important;

            color: #2864e6 !important;
        }


        /* =====================================================
           TOPBAR RIGHT
        ===================================================== */

        .topbar-right {
            display: flex;

            align-items: center;

            justify-content: flex-end;

            gap: 10px;

            flex-shrink: 0;
        }


        /* =====================================================
           PROFILE TOPBAR
        ===================================================== */

        .top-user-profile {
            position: relative;

            display: flex;

            align-items: center;
        }


        .top-profile-button {
            display: flex;

            align-items: center;

            gap: 9px;

            border: 0;

            background: transparent;

            padding: 5px 7px;

            border-radius: 9px;

            cursor: pointer;

            font-family: 'Poppins', sans-serif;

            transition: .2s;
        }


        .top-profile-button:hover {
            background: #f4f7fb;
        }


        /* =====================================================
           AVATAR TOPBAR
        ===================================================== */

        .top-profile-avatar {
            width: 34px;

            height: 34px;

            min-width: 34px;

            border-radius: 50%;

            background: #2864e6;

            color: #ffffff;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 12px;

            font-weight: 700;

            line-height: 1;
        }


        /* =====================================================
           TEXT PROFILE TOPBAR
        ===================================================== */

        .top-profile-text {
            display: flex;

            flex-direction: column;

            align-items: flex-start;

            justify-content: center;

            line-height: 1.2;
        }


        .top-profile-text strong {
            display: block;

            max-width: 135px;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;

            color: #172033;

            font-size: 10px;

            font-weight: 700;
        }


        .top-profile-text small {
            display: block;

            margin-top: 2px;

            color: #8a95a8;

            font-size: 8px;

            font-weight: 400;
        }


        /* =====================================================
           CHEVRON
        ===================================================== */

        .top-profile-arrow {
            color: #7b8ba2;

            font-size: 9px;

            transition: transform .2s;
        }


        .top-profile-button.active
        .top-profile-arrow {
            transform: rotate(180deg);
        }


        /* =====================================================
           DROPDOWN
        ===================================================== */

        .top-profile-dropdown {
            position: absolute;

            right: 0;

            top: calc(100% + 9px);

            width: 220px;

            background: #ffffff;

            border: 1px solid #e1e8f2;

            border-radius: 11px;

            box-shadow:
                0 10px 28px rgba(23, 33, 51, .12);

            overflow: hidden;

            opacity: 0;

            visibility: hidden;

            transform: translateY(-5px);

            transition: .2s;

            z-index: 3000;
        }


        .top-profile-dropdown.show {
            opacity: 1;

            visibility: visible;

            transform: translateY(0);
        }


        .top-profile-dropdown-header {
            display: flex;

            align-items: center;

            gap: 10px;

            padding: 14px;
        }


        .dropdown-avatar {
            width: 38px;

            height: 38px;

            min-width: 38px;

            border-radius: 50%;

            background: #2864e6;

            color: #ffffff;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 13px;

            font-weight: 700;
        }


        .dropdown-info {
            min-width: 0;

            display: flex;

            flex-direction: column;
        }


        .dropdown-info strong {
            color: #172033;

            font-size: 10px;

            font-weight: 700;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        .dropdown-info span {
            color: #8a95a8;

            font-size: 8px;

            margin-top: 2px;
        }


        .dropdown-divider {
            height: 1px;

            background: #edf1f5;
        }


        .dropdown-item {
            width: 100%;

            display: flex;

            align-items: center;

            gap: 9px;

            padding: 10px 14px;

            border: 0;

            background: #ffffff;

            color: #394b63;

            text-decoration: none;

            font-family: 'Poppins', sans-serif;

            font-size: 9px;

            font-weight: 500;

            text-align: left;

            cursor: pointer;
        }


        .dropdown-item:hover {
            background: #f7faff;

            color: #2864e6;
        }


        .dropdown-item i {
            width: 15px;

            text-align: center;

            font-size: 13px;
        }


        .dropdown-item.logout {
            color: #dc3545;
        }


        .dropdown-item.logout:hover {
            background: #fff5f5;

            color: #dc3545;
        }


        /* =====================================================
           PROFILE MODAL
        ===================================================== */

        .profile-modal {
            position: fixed;

            inset: 0;

            background: rgba(15, 23, 42, .35);

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

            box-shadow:
                0 18px 45px rgba(0,0,0,.16);

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


        .modal-avatar {
            width: 58px;

            height: 58px;

            margin: 0 auto 12px;

            border-radius: 50%;

            background: #2864e6;

            color: #ffffff;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 22px;

            font-weight: 700;
        }


        .modal-name {
            text-align: center;

            font-size: 15px;

            font-weight: 700;

            color: #17243a;
        }


        .modal-role {
            text-align: center;

            font-size: 9px;

            color: #8a95a8;

            margin: 3px 0 17px;
        }


        .modal-detail {
            border: 1px solid #e6ebf2;

            border-radius: 9px;

            overflow: hidden;
        }


        .modal-detail-row {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            padding: 10px 12px;

            border-bottom: 1px solid #edf1f5;
        }


        .modal-detail-row:last-child {
            border-bottom: 0;
        }


        .modal-detail-label {
            font-size: 8px;

            color: #8a95a8;
        }


        .modal-detail-value {
            font-size: 9px;

            color: #17243a;

            font-weight: 600;

            text-align: right;
        }


        /* =====================================================
           PAGE CONTENT
        ===================================================== */

        .page-content {
            padding: 28px;
        }


        /* =====================================================
           PAGE HEADER
        ===================================================== */

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
           HEADER BUTTONS
        ===================================================== */

        .header-actions {
            display: flex;

            gap: 9px;
        }


        .back-button,
        .edit-button {
            display: inline-flex;

            align-items: center;

            gap: 8px;

            padding: 11px 16px;

            border-radius: 8px;

            text-decoration: none;

            font-size: 12px;

            font-weight: 500;

            transition: .2s;
        }


        .back-button {
            background: #ffffff;

            border: 1px solid #dce3ee;

            color: #526078;
        }


        .back-button:hover {
            border-color: #2864e6;

            color: #2864e6;
        }


        .edit-button {
            background: #2864e6;

            border: 1px solid #2864e6;

            color: #ffffff;
        }


        .edit-button:hover {
            background: #1f55c8;

            color: #ffffff;
        }


        /* =====================================================
           USER PROFILE CARD
        ===================================================== */

        .user-profile-card {
            background: #ffffff;

            border: 1px solid #e1e7f0;

            border-radius: 12px;

            overflow: hidden;

            margin-bottom: 20px;
        }


        .user-profile-header {
            display: flex;

            align-items: center;

            gap: 17px;

            padding: 24px;

            border-bottom: 1px solid #edf0f5;
        }


        .user-avatar {
            width: 62px;

            height: 62px;

            min-width: 62px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #e5efff;

            color: #2864e6;

            font-size: 21px;

            font-weight: 600;
        }


        .user-info {
            min-width: 0;
        }


        .user-info h3 {
            margin: 0;

            font-size: 19px;

            font-weight: 700;

            color: #17243a;
        }


        .user-info p {
            margin: 5px 0 0;

            color: #8a95a8;

            font-size: 11px;
        }


        .user-badges {
            margin-left: auto;

            display: flex;

            align-items: center;

            gap: 8px;
        }


        /* =====================================================
           BADGE
        ===================================================== */

        .badge {
            display: inline-flex;

            align-items: center;

            padding: 7px 12px;

            border-radius: 20px;

            font-size: 10px;

            font-weight: 500;
        }


        .badge-admin {
            background: #e5efff;

            color: #2864e6;
        }


        .badge-user {
            background: #eee9ff;

            color: #7655d8;
        }


        .badge-aktif {
            background: #dcf8e8;

            color: #159447;
        }


        .badge-nonaktif {
            background: #ffe1e1;

            color: #d83b3b;
        }


        /* =====================================================
           INFORMATION CARD
        ===================================================== */

        .information-card {
            background: #ffffff;

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
           INFORMATION GRID
        ===================================================== */

        .info-grid {
            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 20px 35px;
        }


        .info-item {
            padding-bottom: 15px;

            border-bottom: 1px solid #edf0f5;
        }


        .info-label {
            display: block;

            margin-bottom: 6px;

            color: #8a95a8;

            font-size: 11px;
        }


        .info-value {
            color: #26334a;

            font-size: 13px;

            font-weight: 500;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 900px) {

            .dashboard-layout .sidebar {
                width: 180px !important;

                min-width: 180px !important;

                max-width: 180px !important;
            }


            .dashboard-layout .main-content {
                margin-left: 180px !important;

                width: calc(100% - 180px) !important;
            }

        }


        @media (max-width: 800px) {

            .page-content {
                padding: 18px;
            }


            .page-header {
                align-items: flex-start;

                flex-direction: column;

                gap: 15px;
            }


            .header-actions {
                width: 100%;
            }


            .info-grid {
                grid-template-columns: 1fr;
            }


            .user-profile-header {
                align-items: flex-start;

                flex-wrap: wrap;
            }


            .user-badges {
                margin-left: 0;

                width: 100%;
            }

        }


        @media (max-width: 700px) {

            .dashboard-layout .sidebar {
                display: none !important;
            }


            .dashboard-layout .main-content {
                margin-left: 0 !important;

                width: 100% !important;
            }


            .topbar {
                padding: 0 18px !important;
            }


            .page-content {
                padding: 18px;
            }

        }


        @media (max-width: 450px) {

            .top-profile-text {
                display: none;
            }


            .top-profile-button {
                gap: 5px;
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


        <!-- LOGO -->

        <div class="sidebar-logo">

            <img
                src="/SIPM-ODGJ/assets/img/logo YCKA.png"
                alt="Logo Yayasan"
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
                class="menu-item"
            >

                <i class="bi bi-people"></i>

                <span>
                    Data Pasien
                </span>

            </a>


            <a
                href="data_user.php"
                class="menu-item active"
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



    <!-- =====================================================
         MAIN
    ====================================================== -->

    <main class="main-content">


        <!-- =================================================
             TOPBAR
        ================================================== -->

        <header class="topbar">


            <h1>
                Detail User
            </h1>


            <div class="topbar-right">


                <!-- =================================================
                     PROFIL ADMIN YANG LOGIN
                     BUKAN USER YANG SEDANG DILIHAT
                ================================================== -->

                <div
                    class="top-user-profile"
                    id="topUserProfile"
                >


                    <button
                        type="button"
                        class="top-profile-button"
                        id="topProfileButton"
                    >


                        <span class="top-profile-avatar">

                            <?= htmlspecialchars(
                                $inisial_login
                            ); ?>

                        </span>


                        <span class="top-profile-text">

                            <strong>

                                <?= htmlspecialchars(
                                    $nama_user_login
                                ); ?>

                            </strong>

                            <small>
                                Admin
                            </small>

                        </span>


                        <i
                            class="bi bi-chevron-down top-profile-arrow"
                        ></i>


                    </button>


                    <!-- DROPDOWN -->

                    <div
                        class="top-profile-dropdown"
                        id="topProfileDropdown"
                    >


                        <div class="top-profile-dropdown-header">


                            <div class="dropdown-avatar">

                                <?= htmlspecialchars(
                                    $inisial_login
                                ); ?>

                            </div>


                            <div class="dropdown-info">

                                <strong>

                                    <?= htmlspecialchars(
                                        $nama_user_login
                                    ); ?>

                                </strong>

                                <span>
                                    Admin
                                </span>

                            </div>


                        </div>


                        <div class="dropdown-divider"></div>


                        <button
                            type="button"
                            class="dropdown-item"
                            id="openAdminProfile"
                        >

                            <i class="bi bi-person"></i>

                            <span>
                                Profil Saya
                            </span>

                        </button>


                        <a
                            href="logout.php"
                            class="dropdown-item logout"
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

        <div class="page-content">


            <!-- PAGE HEADER -->

            <div class="page-header">


                <div class="page-title">

                    <h2>
                        Detail User
                    </h2>

                    <p>
                        Informasi lengkap pengguna sistem
                    </p>

                </div>


                <div class="header-actions">


                    <a
                        href="data_user.php"
                        class="back-button"
                    >

                        <i class="bi bi-arrow-left"></i>

                        Kembali

                    </a>


                    <a
                        href="edit_user.php?id=<?= (int) $user['id_user']; ?>"
                        class="edit-button"
                    >

                        <i class="bi bi-pencil"></i>

                        Edit

                    </a>


                </div>


            </div>



            <!-- =================================================
                 USER PROFILE YANG DILIHAT
            ================================================== -->

            <div class="user-profile-card">


                <div class="user-profile-header">


                    <div class="user-avatar">

                        <?= htmlspecialchars(
                            $inisial
                        ); ?>

                    </div>


                    <div class="user-info">


                        <h3>

                            <?= htmlspecialchars(
                                $user['nama_lengkap']
                            ); ?>

                        </h3>


                        <p>

                            Username:

                            <?= htmlspecialchars(
                                $user['username']
                            ); ?>

                        </p>


                    </div>


                    <div class="user-badges">


                        <span
                            class="badge <?= $role_class; ?>"
                        >

                            <?= htmlspecialchars(
                                $role_text
                            ); ?>

                        </span>


                        <span
                            class="badge <?= $status_class; ?>"
                        >

                            <?= htmlspecialchars(
                                $status_text
                            ); ?>

                        </span>


                    </div>


                </div>


            </div>



            <!-- =================================================
                 INFORMATION
            ================================================== -->

            <div class="information-card">


                <div class="section-title">


                    <div class="section-icon">

                        <i class="bi bi-person-vcard"></i>

                    </div>


                    <h3>
                        Informasi User
                    </h3>


                </div>



                <div class="info-grid">


                    <!-- NAMA -->

                    <div class="info-item">

                        <span class="info-label">
                            Nama Lengkap
                        </span>

                        <span class="info-value">

                            <?= htmlspecialchars(
                                $user['nama_lengkap']
                            ); ?>

                        </span>

                    </div>


                    <!-- USERNAME -->

                    <div class="info-item">

                        <span class="info-label">
                            Username
                        </span>

                        <span class="info-value">

                            <?= htmlspecialchars(
                                $user['username']
                            ); ?>

                        </span>

                    </div>


                    <!-- ROLE -->

                    <div class="info-item">

                        <span class="info-label">
                            Role
                        </span>

                        <span class="info-value">

                            <?= htmlspecialchars(
                                $role_text
                            ); ?>

                        </span>

                    </div>


                    <!-- STATUS -->

                    <div class="info-item">

                        <span class="info-label">
                            Status
                        </span>

                        <span class="info-value">

                            <?= htmlspecialchars(
                                $status_text
                            ); ?>

                        </span>

                    </div>


                    <!-- DIBUAT -->

                    <div class="info-item">

                        <span class="info-label">
                            Dibuat Pada
                        </span>

                        <span class="info-value">

                            <?= htmlspecialchars(
                                $created_at
                            ); ?>

                        </span>

                    </div>


                </div>


            </div>


        </div>


    </main>


</div>



<!-- =========================================================
     MODAL PROFIL ADMIN
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


            <div class="modal-avatar">

                <?= htmlspecialchars(
                    $inisial_login
                ); ?>

            </div>


            <div class="modal-name">

                <?= htmlspecialchars(
                    $nama_user_login
                ); ?>

            </div>


            <div class="modal-role">
                Admin
            </div>


            <div class="modal-detail">


                <div class="modal-detail-row">

                    <span class="modal-detail-label">
                        Nama Lengkap
                    </span>

                    <span class="modal-detail-value">

                        <?= htmlspecialchars(
                            $nama_user_login
                        ); ?>

                    </span>

                </div>


                <div class="modal-detail-row">

                    <span class="modal-detail-label">
                        Username
                    </span>

                    <span class="modal-detail-value">

                        <?= htmlspecialchars(
                            $username_user_login
                        ); ?>

                    </span>

                </div>


                <div class="modal-detail-row">

                    <span class="modal-detail-label">
                        Role
                    </span>

                    <span class="modal-detail-value">
                        Admin
                    </span>

                </div>


                <div class="modal-detail-row">

                    <span class="modal-detail-label">
                        ID Pengguna
                    </span>

                    <span class="modal-detail-value">

                        #<?= (int) $id_user_login; ?>

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

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const profileButton =
            document.getElementById(
                "topProfileButton"
            );

        const profileDropdown =
            document.getElementById(
                "topProfileDropdown"
            );

        const profileWrapper =
            document.getElementById(
                "topUserProfile"
            );

        const openProfile =
            document.getElementById(
                "openAdminProfile"
            );

        const profileModal =
            document.getElementById(
                "profileModal"
            );

        const closeProfile =
            document.getElementById(
                "closeProfile"
            );


        /* =====================================================
           BUKA / TUTUP DROPDOWN
        ===================================================== */

        profileButton?.addEventListener(
            "click",
            function (event) {

                event.preventDefault();

                event.stopPropagation();

                profileDropdown?.classList.toggle(
                    "show"
                );

                profileButton?.classList.toggle(
                    "active"
                );

            }
        );


        /* =====================================================
           KLIK DI LUAR DROPDOWN
        ===================================================== */

        document.addEventListener(
            "click",
            function (event) {

                if (
                    profileWrapper &&
                    !profileWrapper.contains(
                        event.target
                    )
                ) {

                    profileDropdown?.classList.remove(
                        "show"
                    );

                    profileButton?.classList.remove(
                        "active"
                    );

                }

            }
        );


        /* =====================================================
           PROFIL SAYA
        ===================================================== */

        openProfile?.addEventListener(
            "click",
            function (event) {

                event.preventDefault();

                event.stopPropagation();


                profileDropdown?.classList.remove(
                    "show"
                );

                profileButton?.classList.remove(
                    "active"
                );


                profileModal?.classList.add(
                    "show"
                );

            }
        );


        /* =====================================================
           TUTUP MODAL
        ===================================================== */

        closeProfile?.addEventListener(
            "click",
            function () {

                profileModal?.classList.remove(
                    "show"
                );

            }
        );


        /* =====================================================
           KLIK AREA LUAR MODAL
        ===================================================== */

        profileModal?.addEventListener(
            "click",
            function (event) {

                if (
                    event.target ===
                    profileModal
                ) {

                    profileModal.classList.remove(
                        "show"
                    );

                }

            }
        );


        /* =====================================================
           ESCAPE
        ===================================================== */

        document.addEventListener(
            "keydown",
            function (event) {

                if (event.key === "Escape") {

                    profileModal?.classList.remove(
                        "show"
                    );

                    profileDropdown?.classList.remove(
                        "show"
                    );

                    profileButton?.classList.remove(
                        "active"
                    );

                }

            }
        );

    }
);

</script>


</body>

</html>