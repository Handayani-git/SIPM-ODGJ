<?php

session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

require_once "koneksi.php";


/* =========================================================
   CEK AKSES ADMIN
========================================================= */

if (($_SESSION['role'] ?? '') !== 'admin') {
    die("Anda tidak memiliki akses ke halaman ini.");
}


/* =========================================================
   FUNGSI ESCAPE
========================================================= */

function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}


/* =========================================================
   DATA ADMIN YANG SEDANG LOGIN
   PENTING:
   INI TERPISAH DARI USER YANG SEDANG DIEDIT
========================================================= */

$id_admin = (int) $_SESSION['id_user'];

$query_admin = mysqli_query(
    $conn,
    "SELECT
        id_user,
        nama_lengkap,
        username,
        role,
        status
     FROM users
     WHERE id_user = $id_admin
     LIMIT 1"
);

if (!$query_admin) {
    die("Query admin gagal: " . mysqli_error($conn));
}

$admin = mysqli_fetch_assoc($query_admin);

if (!$admin) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$nama_admin = $admin['nama_lengkap'] ?? 'Admin';
$role_admin = $admin['role'] ?? 'admin';


/* =========================================================
   INISIAL ADMIN
========================================================= */

$inisial_admin = '';

$admin_parts = preg_split(
    '/\s+/',
    trim($nama_admin)
);

foreach (array_slice($admin_parts, 0, 1) as $part) {

    if ($part !== '') {

        $inisial_admin .= strtoupper(
            substr($part, 0, 1)
        );
    }
}

if ($inisial_admin === '') {
    $inisial_admin = 'A';
}


/* =========================================================
   AMBIL ID USER YANG AKAN DIEDIT
========================================================= */

$id_user = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

if ($id_user <= 0) {
    header("Location: data_user.php");
    exit;
}


/* =========================================================
   AMBIL DATA USER YANG AKAN DIEDIT
========================================================= */

$query_user = mysqli_query(
    $conn,
    "SELECT
        id_user,
        nama_lengkap,
        username,
        role,
        status,
        created_at
     FROM users
     WHERE id_user = $id_user
     LIMIT 1"
);

if (!$query_user) {
    die("Query user gagal: " . mysqli_error($conn));
}

$user = mysqli_fetch_assoc($query_user);

if (!$user) {
    header("Location: data_user.php");
    exit;
}


/* =========================================================
   DATA FORM
========================================================= */

$nama_lengkap = $user['nama_lengkap'] ?? '';
$username     = $user['username'] ?? '';
$role         = $user['role'] ?? '';
$status       = $user['status'] ?? '';


/* =========================================================
   PROSES UPDATE DATA
   FUNGSI TETAP UPDATE
========================================================= */

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $username     = trim($_POST['username'] ?? '');
    $password     = $_POST['password'] ?? '';
    $role         = trim($_POST['role'] ?? '');
    $status       = trim($_POST['status'] ?? '');


    /* =====================================================
       VALIDASI
    ===================================================== */

    if (
        $nama_lengkap === '' ||
        $username === '' ||
        $role === '' ||
        $status === ''
    ) {

        $error = "Nama lengkap, username, role, dan status wajib diisi.";

    } else {

        /* =================================================
           CEK USERNAME
        ================================================= */

        $stmt_check = $conn->prepare(
            "SELECT id_user
             FROM users
             WHERE username = ?
             AND id_user != ?
             LIMIT 1"
        );

        if (!$stmt_check) {

            $error = "Query validasi username gagal: " . $conn->error;

        } else {

            $stmt_check->bind_param(
                "si",
                $username,
                $id_user
            );

            $stmt_check->execute();

            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {

                $error = "Username sudah digunakan oleh pengguna lain.";
            }

            $stmt_check->close();
        }
    }


    /* =====================================================
       UPDATE DATA
    ===================================================== */

    if ($error === '') {

        if ($password !== '') {

            $password_hash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt_update = $conn->prepare(
                "UPDATE users
                 SET
                    nama_lengkap = ?,
                    username = ?,
                    password = ?,
                    role = ?,
                    status = ?
                 WHERE id_user = ?"
            );

            if (!$stmt_update) {

                $error = "Query update gagal: " . $conn->error;

            } else {

                $stmt_update->bind_param(
                    "sssssi",
                    $nama_lengkap,
                    $username,
                    $password_hash,
                    $role,
                    $status,
                    $id_user
                );
            }

        } else {

            $stmt_update = $conn->prepare(
                "UPDATE users
                 SET
                    nama_lengkap = ?,
                    username = ?,
                    role = ?,
                    status = ?
                 WHERE id_user = ?"
            );

            if (!$stmt_update) {

                $error = "Query update gagal: " . $conn->error;

            } else {

                $stmt_update->bind_param(
                    "ssssi",
                    $nama_lengkap,
                    $username,
                    $role,
                    $status,
                    $id_user
                );
            }
        }


        /* =================================================
           EKSEKUSI UPDATE
        ================================================= */

        if ($error === '') {

            if ($stmt_update->execute()) {

                header(
                    "Location: detail_user.php?id=" .
                    $id_user .
                    "&status=updated"
                );

                exit;

            } else {

                $error =
                    "Data user gagal diperbarui: " .
                    $stmt_update->error;
            }

            $stmt_update->close();
        }
    }
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
        Edit User - Sistem Informasi ODGJ
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
           RESET
        ===================================================== */

        html,
        body {

            margin: 0 !important;
            padding: 0 !important;

            width: 100%;
            min-height: 100%;
        }


        body {

            overflow-x: hidden;

            font-family: 'Poppins', sans-serif;

            background: #f6f8fc;
        }


        .dashboard-layout {

            display: flex !important;

            width: 100%;
            min-height: 100vh;

            margin: 0 !important;
            padding: 0 !important;
        }


        /* =====================================================
           SIDEBAR
        ===================================================== */

        .sidebar {

            position: fixed !important;

            top: 0 !important;
            left: 0 !important;
            bottom: 0 !important;

            width: 208px !important;
            min-width: 208px !important;

            height: 100vh !important;

            margin: 0 !important;
            padding: 0 !important;

            z-index: 1000 !important;
        }


        /* =====================================================
           MAIN
        ===================================================== */

        .main-content {

            width: calc(100% - 208px) !important;

            margin-left: 208px !important;
            margin-top: 0 !important;

            padding: 0 !important;

            min-height: 100vh !important;

            position: relative !important;
        }


        /* =====================================================
           TOPBAR
        ===================================================== */

        .topbar {

            position: relative !important;

            top: auto !important;
            left: auto !important;

            width: 100% !important;

            height: 76px !important;
            min-height: 76px !important;

            margin: 0 !important;
            padding: 0 32px !important;

            box-sizing: border-box !important;

            display: flex !important;

            align-items: center !important;
            justify-content: space-between !important;

            z-index: 100 !important;

            background: #ffffff;

            border-bottom: 1px solid #e1e7f0;
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

            display: flex !important;

            align-items: center !important;

            justify-content: flex-end !important;

            gap: 10px !important;

            width: auto !important;

            max-width: none !important;

            flex-shrink: 0 !important;
        }


        /* =====================================================
           PROFILE ADMIN TOPBAR
        ===================================================== */

        .user-profile {

            position: relative;

            display: flex;

            align-items: center;
        }


        .profile-button {

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


        .profile-button:hover {

            background: #f4f7fb;
        }


        .profile-avatar-top {

            width: 38px;
            height: 38px;

            min-width: 38px;

            border-radius: 50%;

            background: #2864e6;

            color: #ffffff;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 12px;

            font-weight: 700;
        }


        .profile-text {

            display: flex;

            flex-direction: column;

            align-items: flex-start;

            line-height: 1.2;
        }


        .profile-text strong {

            color: #172033;

            font-size: 10px;

            font-weight: 700;

            max-width: 150px;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        .profile-text small {

            color: #8a95a8;

            font-size: 8px;

            margin-top: 3px;
        }


        .profile-arrow {

            font-size: 9px;

            color: #7b8ba2;

            transition: transform .2s;
        }


        .profile-button.active .profile-arrow {

            transform: rotate(180deg);
        }


        /* =====================================================
           PROFILE DROPDOWN
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
                0 10px 28px rgba(23,33,51,.12);

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


        .profile-dropdown-avatar {

            width: 36px;
            height: 36px;

            min-width: 36px;

            border-radius: 50%;

            background: #2864e6;

            color: #ffffff;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 11px;

            font-weight: 700;
        }


        .profile-info {

            display: flex;

            flex-direction: column;

            min-width: 0;
        }


        .profile-info strong {

            color: #172033;

            font-size: 10px;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        .profile-info span {

            color: #8a95a8;

            font-size: 8px;

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


        /* =====================================================
           PAGE CONTENT
        ===================================================== */

        .page-content {

            padding: 24px 32px 40px;
        }


        /* =====================================================
           PAGE HEADER
        ===================================================== */

        .page-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 24px;
        }


        .page-title h2 {

            margin: 0;

            color: #17243a;

            font-size: 23px;

            font-weight: 700;
        }


        .page-title p {

            margin: 5px 0 0;

            color: #7a8499;

            font-size: 12px;
        }


        /* =====================================================
           BACK BUTTON
        ===================================================== */

        .back-button {

            display: inline-flex;

            align-items: center;

            gap: 7px;

            padding: 9px 14px;

            border-radius: 8px;

            background: #ffffff;

            border: 1px solid #dce3ee;

            color: #526078;

            text-decoration: none;

            font-family: 'Poppins', sans-serif;

            font-size: 11px;

            font-weight: 500;

            transition: all .2s ease;
        }


        .back-button:hover {

            background: #f7f9fc;

            color: #2864e6;

            border-color: #cbd8ee;
        }


        /* =====================================================
           FORM CARD
        ===================================================== */

        .form-card {

            background: #ffffff;

            border: 1px solid #e1e7f0;

            border-radius: 14px;

            overflow: hidden;
        }


        /* =====================================================
           CARD HEADER
        ===================================================== */

        .form-card-header {

            display: flex;

            align-items: center;

            gap: 10px;

            padding: 20px 34px;

            border-bottom: 1px solid #edf0f5;
        }


        .form-card-icon {

            width: 48px;
            height: 48px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 10px;

            background: #eaf1ff;

            color: #2864e6;

            font-size: 19px;
        }


        .form-card-header h3 {

            margin: 0;

            color: #17243a;

            font-size: 17px;

            font-weight: 600;
        }


        /* =====================================================
           FORM BODY
        ===================================================== */

        .form-body {

            padding: 28px 34px 34px;
        }


        .form-grid {

            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 25px 34px;
        }


        .form-group {

            display: flex;

            flex-direction: column;
        }


        .form-group.full {

            grid-column: 1 / -1;
        }


        .form-label {

            margin-bottom: 9px;

            color: #17243a;

            font-size: 12px;

            font-weight: 600;
        }


        .required {

            color: #e74c3c;
        }


        .form-input,
        .form-select {

            width: 100%;

            height: 48px;

            padding: 0 16px;

            border: 1px solid #dce3ee;

            border-radius: 9px;

            outline: none;

            background: #ffffff;

            color: #26334a;

            font-family: 'Poppins', sans-serif;

            font-size: 12px;

            transition: all .2s ease;
        }


        .form-input:focus,
        .form-select:focus {

            border-color: #2864e6;

            box-shadow:
                0 0 0 3px rgba(40,100,230,.07);
        }


        /* =====================================================
           PASSWORD
        ===================================================== */

        .password-wrapper {

            position: relative;
        }


        .password-wrapper .form-input {

            padding-right: 46px;
        }


        .password-toggle {

            position: absolute;

            top: 50%;

            right: 15px;

            transform: translateY(-50%);

            border: 0;

            background: transparent;

            color: #8290a6;

            font-size: 16px;

            cursor: pointer;

            padding: 3px;
        }


        .password-toggle:hover {

            color: #2864e6;
        }


        .form-help {

            margin-top: 7px;

            color: #8a95a8;

            font-size: 10px;
        }


        /* =====================================================
           ALERT
        ===================================================== */

        .alert {

            margin-bottom: 24px;

            padding: 12px 15px;

            border-radius: 8px;

            font-size: 10px;

            display: flex;

            align-items: center;

            gap: 9px;
        }


        .alert-error {

            background: #fff0f0;

            border: 1px solid #ffd5d5;

            color: #d63c3c;
        }


        /* =====================================================
           FORM FOOTER
        ===================================================== */

        .form-footer {

            margin-top: 30px;

            padding-top: 22px;

            border-top: 1px solid #edf0f5;

            display: flex;

            align-items: center;

            justify-content: flex-end;

            gap: 10px;
        }


        .cancel-button {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            height: 42px;

            padding: 0 18px;

            border-radius: 8px;

            background: #ffffff;

            border: 1px solid #dce3ee;

            color: #526078;

            text-decoration: none;

            font-family: 'Poppins', sans-serif;

            font-size: 11px;

            font-weight: 500;
        }


        .cancel-button:hover {

            background: #f7f9fc;

            color: #2864e6;
        }


        /* =====================================================
           EDIT BUTTON
        ===================================================== */

        .update-button {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            height: 42px;

            padding: 0 20px;

            border: 0;

            border-radius: 8px;

            background: #2864e6;

            color: #ffffff;

            font-family: 'Poppins', sans-serif;

            font-size: 11px;

            font-weight: 600;

            cursor: pointer;

            transition: all .2s ease;
        }


        .update-button:hover {

            background: #1f56cc;

            transform: translateY(-1px);
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 900px) {

            .sidebar {

                width: 180px !important;

                min-width: 180px !important;
            }


            .main-content {

                margin-left: 180px !important;

                width: calc(100% - 180px) !important;
            }


            .form-grid {

                grid-template-columns: 1fr;
            }


            .form-group.full {

                grid-column: auto;
            }
        }


        @media (max-width: 700px) {

            .sidebar {

                display: none !important;
            }


            .main-content {

                margin-left: 0 !important;

                width: 100% !important;
            }


            .topbar {

                padding: 0 18px !important;
            }


            .page-content {

                padding: 18px;
            }


            .page-header {

                align-items: flex-start;

                flex-direction: column;
            }


            .form-card-header {

                padding: 18px 20px;
            }


            .form-body {

                padding: 22px 20px 25px;
            }


            .profile-text {

                display: none;
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


        <!-- BRAND -->

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


        <!-- MENU -->

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
         MAIN CONTENT
    ====================================================== -->

    <main class="main-content">


        <!-- =================================================
             TOPBAR
        ================================================== -->

        <header class="topbar">


            <h1>
                Edit User
            </h1>


            <!-- PROFILE ADMIN -->

            <div class="topbar-right">

                <div
                    class="user-profile"
                    id="userProfile"
                >

                    <button
                        type="button"
                        class="profile-button"
                        id="profileButton"
                    >

                        <span class="profile-avatar-top">

                            <?= e($inisial_admin); ?>

                        </span>


                        <span class="profile-text">

                            <strong>

                                <?= e($nama_admin); ?>

                            </strong>

                            <small>

                                <?= e(ucfirst($role_admin)); ?>

                            </small>

                        </span>


                        <i
                            class="bi bi-chevron-down profile-arrow"
                        ></i>

                    </button>



                    <!-- DROPDOWN -->

                    <div
                        class="profile-dropdown"
                        id="profileDropdown"
                    >

                        <div class="profile-dropdown-header">

                            <div class="profile-dropdown-avatar">

                                <?= e($inisial_admin); ?>

                            </div>


                            <div class="profile-info">

                                <strong>

                                    <?= e($nama_admin); ?>

                                </strong>

                                <span>

                                    <?= e(ucfirst($role_admin)); ?>

                                </span>

                            </div>

                        </div>


                        <div class="profile-divider"></div>


                        <a
                            href="profil.php"
                            class="profile-menu-item"
                        >

                            <i class="bi bi-person"></i>

                            <span>
                                Profil Saya
                            </span>

                        </a>


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
             PAGE CONTENT
        ================================================== -->

        <div class="page-content">


            <!-- PAGE HEADER -->

            <div class="page-header">


                <div class="page-title">

                    <h2>
                        Edit Data User
                    </h2>

                    <p>
                        Edit informasi akun pengguna
                    </p>

                </div>


                <a
                    href="detail_user.php?id=<?= (int)$id_user; ?>"
                    class="back-button"
                >

                    <i class="bi bi-arrow-left"></i>

                    Kembali

                </a>


            </div>



            <!-- =================================================
                 FORM CARD
            ================================================== -->

            <div class="form-card">


                <!-- CARD HEADER -->

                <div class="form-card-header">

                    <div class="form-card-icon">

                        <i class="bi bi-person-gear"></i>

                    </div>


                    <h3>
                        Informasi User
                    </h3>

                </div>



                <!-- FORM BODY -->

                <div class="form-body">


                    <?php if ($error !== ''): ?>

                        <div class="alert alert-error">

                            <i class="bi bi-exclamation-circle"></i>

                            <span>

                                <?= e($error); ?>

                            </span>

                        </div>

                    <?php endif; ?>



                    <form
                        method="POST"
                        action=""
                    >


                        <div class="form-grid">


                            <!-- NAMA -->

                            <div class="form-group">

                                <label class="form-label">

                                    Nama Lengkap

                                    <span class="required">
                                        *
                                    </span>

                                </label>


                                <input
                                    type="text"
                                    name="nama_lengkap"
                                    class="form-input"
                                    value="<?= e($nama_lengkap); ?>"
                                    required
                                >

                            </div>



                            <!-- USERNAME -->

                            <div class="form-group">

                                <label class="form-label">

                                    Username

                                    <span class="required">
                                        *
                                    </span>

                                </label>


                                <input
                                    type="text"
                                    name="username"
                                    class="form-input"
                                    value="<?= e($username); ?>"
                                    required
                                >

                            </div>



                            <!-- PASSWORD -->

                            <div class="form-group">

                                <label class="form-label">

                                    Password Baru

                                </label>


                                <div class="password-wrapper">

                                    <input
                                        type="password"
                                        name="password"
                                        id="password"
                                        class="form-input"
                                        placeholder="Kosongkan jika tidak ingin mengubah password"
                                    >


                                    <button
                                        type="button"
                                        class="password-toggle"
                                        id="passwordToggle"
                                    >

                                        <i
                                            class="bi bi-eye"
                                            id="passwordIcon"
                                        ></i>

                                    </button>

                                </div>


                                <div class="form-help">

                                    Kosongkan jika password lama tetap digunakan.

                                </div>

                            </div>



                            <!-- ROLE -->

                            <div class="form-group">

                                <label class="form-label">

                                    Role

                                    <span class="required">
                                        *
                                    </span>

                                </label>


                                <select
                                    name="role"
                                    class="form-select"
                                    required
                                >

                                    <option
                                        value="admin"
                                        <?= strtolower($role) === 'admin' ? 'selected' : ''; ?>
                                    >
                                        Admin
                                    </option>


                                    <option
                                        value="petugas"
                                        <?= strtolower($role) === 'petugas' ? 'selected' : ''; ?>
                                    >
                                        Petugas
                                    </option>


                                    <option
                                        value="keluarga"
                                        <?= strtolower($role) === 'keluarga' ? 'selected' : ''; ?>
                                    >
                                        Keluarga
                                    </option>

                                </select>

                            </div>



                            <!-- STATUS -->

                            <div class="form-group">

                                <label class="form-label">

                                    Status

                                    <span class="required">
                                        *
                                    </span>

                                </label>


                                <select
                                    name="status"
                                    class="form-select"
                                    required
                                >

                                    <option
                                        value="aktif"
                                        <?= strtolower($status) === 'aktif' ? 'selected' : ''; ?>
                                    >
                                        Aktif
                                    </option>


                                    <option
                                        value="nonaktif"
                                        <?= strtolower($status) === 'nonaktif' ? 'selected' : ''; ?>
                                    >
                                        Nonaktif
                                    </option>

                                </select>

                            </div>


                        </div>



                        <!-- FORM FOOTER -->

                        <div class="form-footer">


                            <a
                                href="detail_user.php?id=<?= (int)$id_user; ?>"
                                class="cancel-button"
                            >

                                <i class="bi bi-x-lg"></i>

                                Batal

                            </a>


                            <button
                                type="submit"
                                class="update-button"
                            >

                                <i class="bi bi-check-lg"></i>

                                Edit Data User

                            </button>


                        </div>


                    </form>


                </div>

            </div>


        </div>


    </main>


</div>



<script>


/* =========================================================
   PROFILE DROPDOWN
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {


        const profileButton =
            document.getElementById("profileButton");


        const profileDropdown =
            document.getElementById("profileDropdown");


        const userProfile =
            document.getElementById("userProfile");


        profileButton?.addEventListener(
            "click",
            function (e) {

                e.preventDefault();

                e.stopPropagation();

                profileDropdown?.classList.toggle("show");

                profileButton?.classList.toggle("active");

            }
        );


        document.addEventListener(
            "click",
            function (e) {

                if (
                    userProfile &&
                    !userProfile.contains(e.target)
                ) {

                    profileDropdown?.classList.remove("show");

                    profileButton?.classList.remove("active");
                }

            }
        );


        document.addEventListener(
            "keydown",
            function (e) {

                if (e.key === "Escape") {

                    profileDropdown?.classList.remove("show");

                    profileButton?.classList.remove("active");
                }

            }
        );



        /* =====================================================
           PASSWORD TOGGLE
        ===================================================== */

        const password =
            document.getElementById("password");


        const passwordToggle =
            document.getElementById("passwordToggle");


        const passwordIcon =
            document.getElementById("passwordIcon");


        passwordToggle?.addEventListener(
            "click",
            function () {

                if (!password) {
                    return;
                }


                if (password.type === "password") {

                    password.type = "text";

                    passwordIcon.className =
                        "bi bi-eye-slash";

                } else {

                    password.type = "password";

                    passwordIcon.className =
                        "bi bi-eye";
                }

            }
        );

    }
);

</script>


</body>

</html>