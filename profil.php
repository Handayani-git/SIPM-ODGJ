<?php

session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

require_once "koneksi.php";


/* ===============================
   AMBIL DATA USER YANG LOGIN
================================ */

$id_user = (int) $_SESSION['id_user'];

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
    die("Query gagal: " . mysqli_error($conn));
}

$user = mysqli_fetch_assoc($query_user);

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}


/* ===============================
   DATA PROFILE
================================ */

$nama_lengkap = $user['nama_lengkap'] ?? '-';
$username     = $user['username'] ?? '-';
$role         = $user['role'] ?? '-';
$status       = $user['status'] ?? '-';
$created_at   = $user['created_at'] ?? '';


/* ===============================
   AMBIL INISIAL NAMA
================================ */

$inisial = '';

$nama_parts = preg_split(
    '/\s+/',
    trim($nama_lengkap)
);

foreach (
    array_slice($nama_parts, 0, 2) as $part
) {
    if ($part !== '') {
        $inisial .= strtoupper(
            substr($part, 0, 1)
        );
    }
}

if ($inisial === '') {
    $inisial = 'A';
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
        Profil Admin - Sistem Informasi ODGJ
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
           RESET & LAYOUT
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
           MAIN CONTENT
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

            gap: 14px !important;

            width: auto !important;
            max-width: none !important;

            flex-shrink: 0 !important;
        }


        /* =====================================================
           PROFILE AVATAR TOPBAR
           BIRU SOLID + HURUF PUTIH
        ===================================================== */

        .topbar .profile-avatar {
            position: relative !important;

            display: flex !important;
            align-items: center !important;
            justify-content: center !important;

            width: 38px !important;
            height: 38px !important;

            min-width: 38px !important;
            max-width: 38px !important;

            flex: 0 0 38px !important;

            box-sizing: border-box !important;

            margin: 0 !important;
            padding: 0 !important;

            border-radius: 50% !important;

            /* BIRU SOLID */
            background: #2864e6 !important;

            /* BORDER SAMA DENGAN BIRU */
            border: 2px solid #2864e6 !important;

            /* HURUF PUTIH */
            color: #ffffff !important;

            font-family: 'Poppins', sans-serif !important;

            font-size: 11px !important;
            font-weight: 700 !important;

            line-height: 1 !important;

            text-decoration: none !important;

            cursor: pointer !important;

            overflow: hidden !important;

            transition:
                background 0.2s ease,
                border-color 0.2s ease,
                color 0.2s ease,
                transform 0.2s ease !important;

            z-index: 5 !important;
        }


        /* HOVER */

        .topbar .profile-avatar:hover {
            background: #1f56cc !important;
            border-color: #1f56cc !important;
            color: #ffffff !important;

            transform: translateY(-1px) !important;
        }


        /* FOCUS */

        .topbar .profile-avatar:focus {
            outline: none !important;

            box-shadow:
                0 0 0 3px rgba(40, 100, 230, 0.12) !important;
        }


        /* =====================================================
           PAGE CONTENT
        ===================================================== */

        .page-content {
            padding: 24px 28px 40px;
        }


        /* =====================================================
           PAGE HEADER
        ===================================================== */

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;

            margin-bottom: 22px;
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

            background: white;

            border: 1px solid #dce3ee;

            color: #526078;

            text-decoration: none;

            font-family: 'Poppins', sans-serif;

            font-size: 11px;
            font-weight: 500;

            transition: all 0.2s ease;
        }

        .back-button:hover {
            background: #f7f9fc;

            color: #2864e6;

            border-color: #cbd8ee;
        }


        /* =====================================================
           PROFILE CARD
        ===================================================== */

        .profile-card {
            background: white;

            border: 1px solid #e1e7f0;

            border-radius: 14px;

            overflow: hidden;

            max-width: 900px;

            margin: 0 auto;
        }


        /* =====================================================
           PROFILE COVER
        ===================================================== */

        .profile-cover {
            height: 125px;

            background:
                linear-gradient(
                    135deg,
                    #eaf1ff 0%,
                    #f4f7ff 55%,
                    #ffffff 100%
                );

            position: relative;
        }


        /* =====================================================
           PROFILE MAIN
        ===================================================== */

        .profile-main {
            padding: 0 30px 30px;

            position: relative;
        }


        /* =====================================================
           BIG AVATAR
        ===================================================== */

        .profile-big-avatar {
            width: 92px;
            height: 92px;

            border-radius: 50%;

            background: #eaf1ff;

            border: 5px solid white;

            color: #2864e6;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 27px;
            font-weight: 600;

            position: relative;

            margin-top: -46px;

            box-shadow:
                0 4px 15px rgba(23, 36, 58, 0.10);
        }


        /* =====================================================
           PROFILE NAME
        ===================================================== */

        .profile-heading {
            margin-top: 13px;

            display: flex;
            align-items: flex-start;
            justify-content: space-between;

            gap: 20px;
        }

        .profile-name h2 {
            margin: 0;

            color: #17243a;

            font-size: 20px;
            font-weight: 700;
        }

        .profile-name p {
            margin: 4px 0 0;

            color: #7a8499;

            font-size: 11px;
        }


        /* =====================================================
           ROLE BADGE
        ===================================================== */

        .role-badge {
            display: inline-flex;
            align-items: center;

            padding: 6px 12px;

            border-radius: 20px;

            background: #e5efff;

            color: #2864e6;

            font-size: 10px;
            font-weight: 600;

            white-space: nowrap;
        }


        /* =====================================================
           PROFILE DIVIDER
        ===================================================== */

        .profile-divider {
            height: 1px;

            background: #edf0f5;

            margin: 25px 0;
        }


        /* =====================================================
           INFORMATION TITLE
        ===================================================== */

        .information-title {
            display: flex;
            align-items: center;

            gap: 9px;

            margin-bottom: 17px;
        }

        .information-icon {
            width: 31px;
            height: 31px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 8px;

            background: #eaf1ff;

            color: #2864e6;

            font-size: 14px;
        }

        .information-title h3 {
            margin: 0;

            color: #17243a;

            font-size: 14px;
            font-weight: 600;
        }


        /* =====================================================
           INFORMATION GRID
        ===================================================== */

        .information-grid {
            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 14px;
        }


        /* =====================================================
           INFORMATION ITEM
        ===================================================== */

        .information-item {
            padding: 15px 17px;

            border: 1px solid #e7ebf2;

            border-radius: 9px;

            background: #fbfcfe;
        }

        .information-item label {
            display: block;

            margin-bottom: 6px;

            color: #8a95a8;

            font-size: 9px;
            font-weight: 500;

            text-transform: uppercase;

            letter-spacing: 0.3px;
        }

        .information-value {
            display: flex;
            align-items: center;

            gap: 7px;

            color: #26334a;

            font-size: 12px;
            font-weight: 600;
        }

        .information-value i {
            color: #2864e6;

            font-size: 13px;
        }


        /* =====================================================
           STATUS
        ===================================================== */

        .status-badge {
            display: inline-flex;
            align-items: center;

            gap: 6px;

            padding: 5px 10px;

            border-radius: 20px;

            background: #dcf8e8;

            color: #159447;

            font-size: 10px;
            font-weight: 600;
        }

        .status-dot {
            width: 6px;
            height: 6px;

            border-radius: 50%;

            background: #159447;
        }


        /* =====================================================
           TANGGAL DIBUAT
        ===================================================== */

        .created-date {
            color: #526078;

            font-size: 11px;

            font-weight: 500;
        }


        /* =====================================================
           PROFILE FOOTER
        ===================================================== */

        .profile-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 15px;

            margin-top: 24px;

            padding-top: 20px;

            border-top: 1px solid #edf0f5;
        }

        .profile-note {
            color: #8993a6;

            font-size: 10px;

            line-height: 1.6;
        }

        .profile-note i {
            color: #2864e6;

            margin-right: 4px;
        }


        /* =====================================================
           EDIT PROFILE BUTTON
        ===================================================== */

        .edit-profile-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            gap: 7px;

            padding: 10px 16px;

            border-radius: 8px;

            background: #2864e6;

            color: white;

            text-decoration: none;

            font-family: 'Poppins', sans-serif;

            font-size: 11px;
            font-weight: 500;

            white-space: nowrap;

            transition: all 0.2s ease;
        }

        .edit-profile-button:hover {
            background: #1f56cc;

            color: white;
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

            .profile-main {
                padding: 0 20px 25px;
            }

            .information-grid {
                grid-template-columns: 1fr;
            }

            .profile-heading {
                flex-direction: column;
            }

            .profile-footer {
                align-items: flex-start;

                flex-direction: column;
            }

        }


        @media (max-width: 450px) {

            .profile-cover {
                height: 100px;
            }

            .profile-big-avatar {
                width: 80px;
                height: 80px;

                margin-top: -40px;

                font-size: 23px;
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


        <!-- SIDEBAR BRAND -->

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


        <!-- SIDEBAR MENU -->

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


        <!-- SIDEBAR BOTTOM -->

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
        ================================================== -->

        <header class="topbar">


            <h1>
                Profil Admin
            </h1>


            <div class="topbar-right">


                <!-- PROFILE -->

                <a
                    href="profil.php"
                    class="profile-avatar"
                    title="Profil Saya"
                    aria-label="Profil Saya"
                >

                    <?= htmlspecialchars($inisial); ?>

                </a>


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
                        Profil Saya
                    </h2>

                    <p>
                        Informasi akun pengguna yang sedang login
                    </p>

                </div>


                <a
                    href="dashboard.php"
                    class="back-button"
                >

                    <i class="bi bi-arrow-left"></i>

                    Kembali

                </a>

            </div>


            <!-- =================================================
                 PROFILE CARD
            ================================================== -->

            <div class="profile-card">


                <!-- COVER -->

                <div class="profile-cover"></div>


                <!-- PROFILE MAIN -->

                <div class="profile-main">


                    <!-- BIG AVATAR -->

                    <div class="profile-big-avatar">

                        <?= htmlspecialchars($inisial); ?>

                    </div>


                    <!-- PROFILE HEADING -->

                    <div class="profile-heading">


                        <div class="profile-name">

                            <h2>

                                <?= htmlspecialchars($nama_lengkap); ?>

                            </h2>

                            <p>

                                @<?= htmlspecialchars($username); ?>

                            </p>

                        </div>


                        <span class="role-badge">

                            <i
                                class="bi bi-shield-check"
                                style="margin-right:5px;"
                            ></i>

                            <?= htmlspecialchars(
                                ucfirst($role)
                            ); ?>

                        </span>


                    </div>


                    <!-- DIVIDER -->

                    <div class="profile-divider"></div>


                    <!-- INFORMATION TITLE -->

                    <div class="information-title">


                        <div class="information-icon">

                            <i class="bi bi-person-vcard"></i>

                        </div>


                        <h3>
                            Informasi Akun
                        </h3>


                    </div>


                    <!-- INFORMATION GRID -->

                    <div class="information-grid">


                        <!-- NAMA -->

                        <div class="information-item">

                            <label>
                                Nama Lengkap
                            </label>

                            <div class="information-value">

                                <i class="bi bi-person"></i>

                                <span>

                                    <?= htmlspecialchars(
                                        $nama_lengkap
                                    ); ?>

                                </span>

                            </div>

                        </div>


                        <!-- USERNAME -->

                        <div class="information-item">

                            <label>
                                Username
                            </label>

                            <div class="information-value">

                                <i class="bi bi-at"></i>

                                <span>

                                    <?= htmlspecialchars(
                                        $username
                                    ); ?>

                                </span>

                            </div>

                        </div>


                        <!-- ROLE -->

                        <div class="information-item">

                            <label>
                                Role
                            </label>

                            <div class="information-value">

                                <i class="bi bi-shield-check"></i>

                                <span>

                                    <?= htmlspecialchars(
                                        ucfirst($role)
                                    ); ?>

                                </span>

                            </div>

                        </div>


                        <!-- STATUS -->

                        <div class="information-item">

                            <label>
                                Status Akun
                            </label>

                            <div class="information-value">

                                <?php if (strtolower($status) === 'aktif'): ?>

                                    <span class="status-badge">

                                        <span class="status-dot"></span>

                                        Aktif

                                    </span>

                                <?php else: ?>

                                    <span
                                        class="status-badge"
                                        style="
                                            background:#ffe5e5;
                                            color:#dc3b3b;
                                        "
                                    >

                                        <span
                                            class="status-dot"
                                            style="
                                                background:#dc3b3b;
                                            "
                                        ></span>

                                        <?= htmlspecialchars(
                                            ucfirst($status)
                                        ); ?>

                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>


                        <!-- DIBUAT -->

                        <div class="information-item">

                            <label>
                                Akun Dibuat
                            </label>

                            <div class="information-value">

                                <i class="bi bi-calendar3"></i>

                                <span class="created-date">

                                    <?= !empty($created_at)
                                        ? date(
                                            'd-m-Y H:i',
                                            strtotime($created_at)
                                        )
                                        : '-';
                                    ?>

                                </span>

                            </div>

                        </div>


                        <!-- ID USER -->

                        <div class="information-item">

                            <label>
                                ID User
                            </label>

                            <div class="information-value">

                                <i class="bi bi-fingerprint"></i>

                                <span>

                                    #<?= (int) $id_user; ?>

                                </span>

                            </div>

                        </div>


                    </div>


                    <!-- PROFILE FOOTER -->

                    <div class="profile-footer">


                        <div class="profile-note">

                            <i class="bi bi-info-circle"></i>

                            Data profil diambil dari akun yang sedang
                            digunakan untuk masuk ke sistem.

                        </div>


                        <a
                            href="edit_profil.php"
                            class="edit-profile-button"
                        >

                            <i class="bi bi-pencil"></i>

                            Edit Profil

                        </a>


                    </div>


                </div>


            </div>


        </div>


    </main>


</div>


</body>

</html>