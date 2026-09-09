<?php

session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

require_once "koneksi.php";


/* ===============================
   NOTIFIKASI DASHBOARD
=============================== */

$notifications = [];

$query_notifications = mysqli_query(
    $conn,
    "SELECT
        m.id_monitoring,
        m.created_at,
        m.tanggal_monitoring,
        m.kondisi,
        p.nama_pasien
     FROM monitoring m
     LEFT JOIN pasien p
        ON m.id_pasien = p.id_pasien
     ORDER BY m.created_at DESC
     LIMIT 5"
);

if ($query_notifications) {
    while ($notif = mysqli_fetch_assoc($query_notifications)) {
        $notifications[] = $notif;
    }
}


$query_notif_today = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM monitoring
     WHERE DATE(created_at) = CURDATE()"
);

$notif_today = 0;

if ($query_notif_today) {
    $notif_data = mysqli_fetch_assoc($query_notif_today);
    $notif_today = (int) ($notif_data['total'] ?? 0);
}



/* ===============================
   STATISTIK DASHBOARD
=============================== */


/* Total seluruh pasien */

$query_total_pasien = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM pasien"
);

$data_total_pasien = mysqli_fetch_assoc($query_total_pasien);

$total_pasien = $data_total_pasien['total'];


/* Pasien dalam yayasan */

$query_dalam = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM pasien
     WHERE status_lokasi = 'Dalam Yayasan'"
);

$data_dalam = mysqli_fetch_assoc($query_dalam);

$pasien_dalam = $data_dalam['total'];


/* Pasien / monitoring luar yayasan */

$query_luar = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM pasien
     WHERE status_lokasi = 'Luar Yayasan'"
);

$data_luar = mysqli_fetch_assoc($query_luar);

$monitoring_luar = $data_luar['total'];
/* ===============================
   DATA GRAFIK MONITORING BULANAN
=============================== */

$chart_data = [];

$tahun_sekarang = (int) date("Y");

/* Ambil semua tahun yang memiliki data monitoring, ditambah tahun sekarang */
$tahun_tersedia = [$tahun_sekarang];
$query_tahun = mysqli_query($conn, "SELECT DISTINCT YEAR(tanggal_monitoring) AS tahun FROM monitoring WHERE tanggal_monitoring IS NOT NULL ORDER BY tahun DESC");
if ($query_tahun) {
    while ($row_tahun = mysqli_fetch_assoc($query_tahun)) {
        $tahun_db = (int) $row_tahun['tahun'];
        if ($tahun_db > 0 && !in_array($tahun_db, $tahun_tersedia, true)) {
            $tahun_tersedia[] = $tahun_db;
        }
    }
}
rsort($tahun_tersedia);

/* Data grafik semua tahun yang ada */
foreach ($tahun_tersedia as $tahun) {
    $chart_data[$tahun] = array_fill(0, 12, 0);
}

$query_chart_all = mysqli_query(
    $conn,
    "SELECT YEAR(tanggal_monitoring) AS tahun, MONTH(tanggal_monitoring) AS bulan, COUNT(*) AS total
     FROM monitoring
     WHERE tanggal_monitoring IS NOT NULL
     GROUP BY YEAR(tanggal_monitoring), MONTH(tanggal_monitoring)
     ORDER BY tahun DESC, bulan ASC"
);

if ($query_chart_all) {
    while ($chart_row = mysqli_fetch_assoc($query_chart_all)) {
        $tahun_row = (int) $chart_row['tahun'];
        $bulan_index = (int) $chart_row['bulan'] - 1;
        if (isset($chart_data[$tahun_row]) && $bulan_index >= 0 && $bulan_index < 12) {
            $chart_data[$tahun_row][$bulan_index] = (int) $chart_row['total'];
        }
    }
}

/* ===============================
   MONITORING TERBARU
=============================== */

$query_monitoring_terbaru = mysqli_query(
    $conn,
    "SELECT
        m.id_monitoring,
        m.tanggal_monitoring,
        m.kondisi,
        p.nama_pasien,
        p.status_lokasi
     FROM monitoring m
     LEFT JOIN pasien p
        ON m.id_pasien = p.id_pasien
     ORDER BY m.tanggal_monitoring DESC
     LIMIT 5"
);

$monitoring_terbaru = [];

if ($query_monitoring_terbaru) {

    while (
        $monitoring_row =
            mysqli_fetch_assoc($query_monitoring_terbaru)
    ) {

        $monitoring_terbaru[] =
            $monitoring_row;

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
        Dashboard - Sistem Informasi ODGJ
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


    <!-- BOOTSTRAP ICONS -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <!-- CHART JS -->

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


    <!-- DASHBOARD CSS -->

    <link
        rel="stylesheet"
        href="/SIPM-ODGJ/assets/css/dashboard.css"
    >


    <style>

        /* =====================================================
           FIX LAYOUT DASHBOARD
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

            font-family:
                'Poppins',
                sans-serif;

        }


        .dashboard-layout {

            display: flex !important;

            width: 100%;
            min-height: 100vh;

            margin: 0 !important;
            padding: 0 !important;

        }


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

            z-index: 1000;

        }


        .main-content {

            width:
                calc(100% - 208px) !important;

            margin-left:
                208px !important;

            margin-top: 0 !important;

            padding: 0 !important;

            min-height: 100vh !important;

            position: relative;

        }



        /* =====================================================
           HEADER
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

            box-sizing: border-box;

            display: flex !important;

            align-items: center !important;

            justify-content:
                space-between !important;

            z-index: 100;

        }


        .topbar h1 {

            margin: 0 !important;

        }



        /* =====================================================
           CONTENT
        ===================================================== */

        .content-wrapper {

            position: relative !important;

            width: 100% !important;

            height: auto !important;

            min-height: 0 !important;

            margin: 0 !important;

            padding:
                22px 20px 40px !important;

            box-sizing: border-box;

        }



        /* =====================================================
           STAT CARDS
        ===================================================== */

        .stat-cards {

            margin:
                0 0 16px 0 !important;

            padding: 0 !important;

            display: grid !important;

            grid-template-columns:
                repeat(
                    3,
                    minmax(0, 1fr)
                ) !important;

            gap: 14px !important;

        }


        .stat-card {

            margin: 0 !important;

        }



        /* =====================================================
           ANALYTICS
        ===================================================== */

        .analytics-grid {

            margin:
                0 0 16px 0 !important;

            padding: 0 !important;

            display: grid !important;

            grid-template-columns:
                minmax(0, 1fr)
                330px !important;

            gap: 14px !important;

        }


        .chart-card,
        .activity-card,
        .monitoring-card {

            margin-top: 0 !important;

        }



        /* =====================================================
           PROFILE AVATAR
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

            background:
                #2864e6 !important;

            border: none !important;

            color:
                #ffffff !important;

            font-family:
                'Poppins',
                sans-serif !important;

            font-size: 13px !important;

            font-weight: 600 !important;

            text-decoration:
                none !important;

            cursor: pointer !important;

            box-sizing:
                border-box !important;

            transition:
                all 0.2s ease !important;

        }


        .profile-avatar:hover {

            background:
                #2864e6 !important;

            color:
                #ffffff !important;

            border: none !important;

            transform:
                translateY(-1px);

            box-shadow:
                0 4px 12px
                rgba(
                    40,
                    100,
                    230,
                    0.15
                );

        }



        /* =====================================================
           NOTIFIKASI
        ===================================================== */

        .notification-wrapper {

            position: relative;

        }


        .notification-button {

            position: relative;

            width: 40px;
            height: 40px;

            border:
                1px solid transparent;

            background:
                transparent;

            border-radius: 50%;

            color:
                #26334a;

            cursor: pointer;

            display: flex;

            align-items: center;
            justify-content: center;

            font-size: 19px;

        }


        .notification-button:hover {

            background:
                #f1f5fb;

        }


        .notification-dot {

            position: absolute;

            top: 7px;
            right: 7px;

            width: 8px;
            height: 8px;

            border-radius: 50%;

            background:
                #ef4444;

            border:
                2px solid white;

        }


        .notification-dot.hidden {

            display: none;

        }


        .notification-dropdown {

            display: none;

            position: absolute;

            top: 48px;
            right: 0;

            width: 350px;

            background:
                white;

            border:
                1px solid #e1e7f0;

            border-radius: 12px;

            box-shadow:
                0 12px 35px
                rgba(
                    23,
                    36,
                    58,
                    0.14
                );

            z-index: 2000;

            overflow: hidden;

        }


        .notification-dropdown.show {

            display: block;

        }


        .notification-header {

            display: flex;

            align-items: center;

            justify-content:
                space-between;

            padding:
                15px 17px;

            border-bottom:
                1px solid #edf0f5;

        }


        .notification-header strong {

            font-size: 13px;

        }


        .notification-header span {

            color:
                #2864e6;

            font-size: 10px;

            font-weight: 600;

        }


        .notification-list {

            max-height: 330px;

            overflow-y: auto;

        }


        .notification-item {

            display: flex;

            gap: 11px;

            padding:
                13px 16px;

            border-bottom:
                1px solid #f0f2f6;

        }


        .notification-item:hover {

            background:
                #f8faff;

        }


        .notification-icon {

            width: 34px;
            height: 34px;

            min-width: 34px;

            border-radius: 9px;

            background:
                #eaf1ff;

            color:
                #2864e6;

            display: flex;

            align-items: center;
            justify-content: center;

        }


        .notification-content {

            min-width: 0;

        }


        .notification-content strong {

            display: block;

            font-size: 11px;

            margin-bottom: 3px;

            color:
                #17243a;

        }


        .notification-content p {

            margin:
                0 0 3px;

            font-size: 10px;

            color:
                #65738a;

            line-height: 1.45;

        }


        .notification-content small {

            color:
                #9aa5b7;

            font-size: 9px;

        }


        .notification-empty {

            padding:
                30px 20px;

            text-align: center;

            color:
                #8993a6;

            font-size: 11px;

        }


        .notification-footer {

            padding:
                10px 16px;

            border-top:
                1px solid #edf0f5;

            text-align: center;

        }


        .notification-footer button {

            border: none;

            background:
                transparent;

            color:
                #2864e6;

            font-family:
                inherit;

            font-size: 10px;

            font-weight: 600;

            cursor: pointer;

        }



        /* =====================================================
           WARNA LOKASI PASIEN
        ===================================================== */

        .location-badge.pink-badge {

            background:
                #fce7f3 !important;

            color:
                #be185d !important;

            border:
                1px solid #f9a8d4;

        }


        .location-badge.cyan-badge {

            background:
                #cffafe !important;

            color:
                #0e7490 !important;

            border:
                1px solid #67e8f9;

        }



        /* =====================================================
           WARNA AVATAR LOKASI
        ===================================================== */

        .patient-avatar.pink-avatar {

            background:
                #fce7f3 !important;

            color:
                #be185d !important;

        }


        .patient-avatar.cyan-avatar {

            background:
                #cffafe !important;

            color:
                #0e7490 !important;

        }



        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 1100px) {

            .analytics-grid {

                grid-template-columns:
                    1fr !important;

            }

        }


        @media (max-width: 900px) {

            .sidebar {

                width:
                    180px !important;

                min-width:
                    180px !important;

            }


            .main-content {

                margin-left:
                    180px !important;

                width:
                    calc(100% - 180px)
                    !important;

            }


            .stat-cards {

                grid-template-columns:
                    repeat(
                        2,
                        minmax(0, 1fr)
                    ) !important;

            }

        }


        @media (max-width: 600px) {

            .notification-dropdown {

                position: fixed;

                top: 70px;

                right: 12px;
                left: 12px;

                width: auto;

            }


            .topbar {

                padding:
                    0 18px !important;

            }

        }


        @media (max-width: 700px) {

            .sidebar {

                display:
                    none !important;

            }


            .main-content {

                margin-left:
                    0 !important;

                width:
                    100% !important;

            }


            .content-wrapper {

                padding:
                    15px !important;

            }


            .stat-cards {

                grid-template-columns:
                    1fr !important;

            }

        }



        /* =====================================================
           PROFILE DROPDOWN
        ===================================================== */

        .profile-wrapper {

            position: relative;

        }


        .profile-trigger {

            border: none;

            background:
                transparent;

            display: flex;

            align-items: center;

            gap: 10px;

            padding:
                6px 8px;

            border-radius: 10px;

            cursor: pointer;

            font-family:
                'Poppins',
                sans-serif;

            transition:
                all 0.2s ease;

        }


        .profile-trigger:hover {

            background:
                #f4f7fc;

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

            color:
                #17243a;

        }


        .profile-info span {

            font-size: 9px;

            color:
                #8993a6;

            margin-top: 2px;

        }


        .profile-arrow {

            font-size: 10px;

            color:
                #8993a6;

            transition:
                transform 0.2s ease;

        }


        .profile-trigger.active
        .profile-arrow {

            transform:
                rotate(180deg);

        }


        .profile-avatar.large {

            width:
                40px !important;

            height:
                40px !important;

            min-width:
                40px !important;

            min-height:
                40px !important;

            background:
                #2864e6 !important;

            color:
                #ffffff !important;

        }


        .profile-dropdown {

            display: none;

            position: absolute;

            top:
                calc(100% + 10px);

            right: 0;

            width: 220px;

            background:
                #ffffff;

            border:
                1px solid #e1e7f0;

            border-radius: 12px;

            box-shadow:
                0 12px 35px
                rgba(
                    23,
                    36,
                    58,
                    0.14
                );

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

            padding:
                18px 17px 14px;

        }


        .profile-dropdown-header
        > div:last-child {

            display: flex;

            flex-direction: column;

        }


        .profile-dropdown-header strong {

            font-size: 13px;

            font-weight: 600;

            color:
                #17243a;

        }


        .profile-dropdown-header span {

            font-size: 9px;

            color:
                #8993a6;

            margin-top: 3px;

        }


        .profile-divider {

            height: 1px;

            background:
                #edf0f5;

            margin:
                0 12px;

        }


        .profile-menu-item {

            display: flex;

            align-items: center;

            gap: 11px;

            padding:
                12px 17px;

            text-decoration: none;

            color:
                #65738a;

            font-size: 11px;

            transition:
                all 0.2s ease;

        }


        .profile-menu-item i {

            color:
                #2864e6 !important;

        }


        .profile-menu-item.logout i {

            color:
                #ef4444 !important;

        }


        .profile-menu-item:hover {

            background:
                #f7f9fc;

            color:
                #2864e6;

        }


        .profile-menu-item.logout {

            color:
                #ef4444;

            margin-bottom: 5px;

        }


        .profile-menu-item.logout:hover {

            background:
                #fff5f5;

            color:
                #dc2626;

        }


        /* =====================================================
           WARNA SIPM ODGJ
           Hanya judul SIPM ODGJ yang dibuat biru.
           Warna menu sidebar tetap mengikuti style sebelumnya.
        ===================================================== */

        /* =====================================================
           SIPM ODGJ - BIRU
           Paksa judul logo menjadi biru pada semua variasi class.
        ===================================================== */

        .dashboard-layout .sidebar h2.sipm-title,
        .dashboard-layout .sidebar .sidebar-brand h2.sipm-title,
        .dashboard-layout .sidebar .sidebar-logo h2.sipm-title {
            color: #2864e6 !important;
            -webkit-text-fill-color: #2864e6 !important;
            text-fill-color: #2864e6 !important;
            opacity: 1 !important;
            text-shadow: none !important;
        }

    </style>

</head>



<body>

<div class="dashboard-layout">


    <!-- ================= SIDEBAR ================= -->

    <aside class="sidebar">


        <div class="sidebar-brand">

            <img
                src="/SIPM-ODGJ/assets/img/logo YCKA.png"
                alt="Logo Yayasan Cahaya Kasih Amanah"
                class="sidebar-logo"
            >

            <h2 class="sipm-title" style="color:#2864e6 !important; -webkit-text-fill-color:#2864e6 !important;">
                SIPM ODGJ
            </h2>

            <p>
                Yayasan Cahaya Kasih Amanah
            </p>

        </div>



        <!-- SIDEBAR MENU -->

        <nav class="sidebar-menu">


            <!-- DASHBOARD LANGSUNG, TANPA SUBMENU -->

            <a
                href="dashboard.php"
                class="menu-item active"
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



        <!-- LOGOUT -->

        <div class="sidebar-bottom">

            <a
                href="logout.php"
                class="logout-button"
            >

                <i
                    class="bi bi-box-arrow-left"
                ></i>

                <span>
                    Logout
                </span>

            </a>

        </div>


    </aside>



    <!-- ================= MAIN CONTENT ================= -->

    <main class="main-content">


        <!-- ================= HEADER ================= -->

        <header class="topbar">


            <h1>
                Dashboard
            </h1>


            <div class="topbar-right">


                <!-- SEARCH -->

                <div class="search-box">

                    <i
                        class="bi bi-search"
                    ></i>

                    <input
                        type="text"
                        placeholder="Search..."
                        id="searchInput"
                    >

                </div>



                <!-- NOTIFICATION -->

                <div class="notification-wrapper">


                    <button
                        class="notification-button"
                        type="button"
                        id="notificationButton"
                        aria-label="Notifikasi"
                    >

                        <i
                            class="bi bi-bell"
                        ></i>


                        <span
                            class="
                                notification-dot
                                <?= $notif_today > 0
                                    ? ''
                                    : 'hidden';
                                ?>
                            "
                            id="notificationDot"
                        ></span>

                    </button>



                    <div
                        class="notification-dropdown"
                        id="notificationDropdown"
                    >


                        <div class="notification-header">

                            <strong>
                                Notifikasi
                            </strong>

                            <span>
                                <?= $notif_today; ?>
                                baru hari ini
                            </span>

                        </div>



                        <div
                            class="notification-list"
                        >

                            <?php
                            if (
                                count(
                                    $notifications
                                ) > 0
                            ):
                            ?>

                                <?php
                                foreach (
                                    $notifications
                                    as $notif
                                ):
                                ?>


                                    <div
                                        class="notification-item"
                                    >


                                        <div
                                            class="notification-icon"
                                        >

                                            <i
                                                class="
                                                    bi
                                                    bi-clipboard2-pulse
                                                "
                                            ></i>

                                        </div>



                                        <div
                                            class="
                                                notification-content
                                            "
                                        >

                                            <strong>
                                                Monitoring pasien
                                            </strong>


                                            <p>

                                                Monitoring

                                                <b>

                                                    <?= htmlspecialchars(
                                                        $notif[
                                                            'nama_pasien'
                                                        ]
                                                        ?? 'Pasien'
                                                    ); ?>

                                                </b>

                                                telah tercatat.

                                            </p>


                                            <small>

                                                <?= !empty(
                                                    $notif[
                                                        'created_at'
                                                    ]
                                                )
                                                    ? date(
                                                        'd-m-Y H:i',
                                                        strtotime(
                                                            $notif[
                                                                'created_at'
                                                            ]
                                                        )
                                                    )
                                                    : '-';
                                                ?>

                                                •

                                                <?= htmlspecialchars(
                                                    $notif[
                                                        'kondisi'
                                                    ]
                                                    ?? '-'
                                                ); ?>

                                            </small>

                                        </div>

                                    </div>


                                <?php endforeach; ?>


                            <?php else: ?>


                                <div
                                    class="
                                        notification-empty
                                    "
                                >

                                    <i
                                        class="
                                            bi
                                            bi-bell-slash
                                        "
                                        style="
                                            display:block;
                                            font-size:25px;
                                            margin-bottom:7px;
                                        "
                                    ></i>

                                    Belum ada notifikasi.

                                </div>


                            <?php endif; ?>

                        </div>



                        <div
                            class="notification-footer"
                        >

                            <button
                                type="button"
                                id="markNotificationsRead"
                            >

                                Tandai sudah dibaca

                            </button>

                        </div>


                    </div>


                </div>



                <!-- PROFILE -->

                <div
                    class="profile-wrapper"
                >


                    <button
                        type="button"
                        class="profile-trigger"
                        id="profileTrigger"
                        aria-label="Menu Profil"
                    >

                        <div
                            class="profile-info"
                        >

                            <strong>
                                Admin
                            </strong>

                            <span>
                                admin
                            </span>

                        </div>


                        <div
                            class="profile-avatar"
                        >
                            A
                        </div>


                        <i
                            class="
                                bi
                                bi-chevron-up
                                profile-arrow
                            "
                        ></i>

                    </button>



                    <div
                        class="profile-dropdown"
                        id="profileDropdown"
                    >


                        <div
                            class="
                                profile-dropdown-header
                            "
                        >

                            <div
                                class="
                                    profile-avatar
                                    large
                                "
                            >
                                A
                            </div>


                            <div>

                                <strong>
                                    Admin
                                </strong>

                                <span>
                                    admin
                                </span>

                            </div>

                        </div>



                        <div
                            class="profile-divider"
                        ></div>



                        <a
                            href="profil.php"
                            class="profile-menu-item"
                        >

                            <i
                                class="
                                    bi
                                    bi-person-circle
                                "
                            ></i>

                            <span>
                                Profil Saya
                            </span>

                        </a>



                        <a
                            href="logout.php"
                            class="
                                profile-menu-item
                                logout
                            "
                        >

                            <i
                                class="
                                    bi
                                    bi-box-arrow-left
                                "
                            ></i>

                            <span>
                                Logout
                            </span>

                        </a>


                    </div>


                </div>


            </div>

        </header>



        <!-- ================= CONTENT ================= -->

        <div class="content-wrapper">


            <!-- ================= STAT CARDS ================= -->

            <section class="stat-cards">


                <a
                    href="data_pasien.php"
                    class="stat-card"
                >

                    <div
                        class="stat-information"
                    >

                        <span>
                            Total Pasien
                        </span>

                        <strong>
                            <?= number_format(
                                $total_pasien
                            ); ?>
                        </strong>

                    </div>


                    <div
                        class="stat-icon blue"
                    >

                        <i
                            class="
                                bi
                                bi-people-fill
                            "
                        ></i>

                    </div>

                </a>



                <a
                    href="data_pasien.php?lokasi=dalam"
                    class="stat-card"
                >

                    <div
                        class="stat-information"
                    >

                        <span>

                            Pasien Dalam<br>

                            Yayasan

                        </span>


                        <strong>
                            <?= number_format(
                                $pasien_dalam
                            ); ?>
                        </strong>

                    </div>


                    <div
                        class="
                            stat-icon
                            purple
                        "
                    >

                        <i
                            class="
                                bi
                                bi-building
                            "
                        ></i>

                    </div>

                </a>



                <a
                    href="monitoring.php?lokasi=luar"
                    class="stat-card"
                >

                    <div
                        class="stat-information"
                    >

                        <span>

                            Monitoring Luar<br>

                            Yayasan

                        </span>


                        <strong>
                            <?= number_format(
                                $monitoring_luar
                            ); ?>
                        </strong>

                    </div>


                    <div
                        class="
                            stat-icon
                            green
                        "
                    >

                        <i
                            class="
                                bi
                                bi-house-heart
                            "
                        ></i>

                    </div>

                </a>



                


            </section>



            <!-- ================= ANALYTICS ================= -->

            <section class="analytics-grid">


                <!-- GRAPH -->

                <div class="chart-card">


                    <div class="card-header">


                        <div>

                            <h2>
                                Statistik Monitoring Bulanan
                            </h2>

                            <p>
                                Jumlah aktivitas monitoring
                                selama satu tahun
                            </p>

                        </div>



                        <div class="chart-actions">


                            <input
                                type="number"
                                id="yearSelect"
                                value="<?= $tahun_sekarang; ?>"
                                min="1900"
                                max="<?= $tahun_sekarang; ?>"
                                step="1"
                                inputmode="numeric"
                                aria-label="Tahun grafik"
                            >


                            <a
                                href="laporan.php"
                            >
                                Lihat Detail →
                            </a>

                        </div>


                    </div>



                    <div
                        class="chart-container"
                    >

                        <canvas
                            id="monitoringChart"
                        ></canvas>

                    </div>


                </div>



                <!-- AKTIVITAS TERBARU
                     INI TETAP DI DALAM DASHBOARD -->

                <div class="activity-card">


                    <div
                        class="activity-header"
                    >

                        <h2>
                            Aktivitas Terbaru
                        </h2>

                    </div>



                    <div class="activity-list">


                        <div
                            class="activity-item"
                        >

                            <div
                                class="
                                    activity-point
                                    blue-point
                                "
                            ></div>


                            <div
                                class="
                                    activity-content
                                "
                            >

                                <span
                                    class="activity-time"
                                >
                                    Baru Saja
                                </span>


                                <strong>
                                    Monitoring pasien
                                    ditambahkan
                                </strong>


                                <p>
                                    Oleh Sarah untuk
                                    pasien Ahmad Ridwan.
                                </p>

                            </div>

                        </div>



                        <div
                            class="activity-item"
                        >

                            <div
                                class="
                                    activity-point
                                    gray-point
                                "
                            ></div>


                            <div
                                class="
                                    activity-content
                                "
                            >

                                <span
                                    class="activity-time"
                                >
                                    2 Jam Lalu
                                </span>


                                <strong>
                                    Data pasien diperbarui
                                </strong>


                                <p>
                                    Status Siti Aminah
                                    diubah menjadi
                                    "Perlu Pantauan".
                                </p>

                            </div>

                        </div>



                        <div
                            class="activity-item"
                        >

                            <div
                                class="
                                    activity-point
                                    green-point
                                "
                            ></div>


                            <div
                                class="
                                    activity-content
                                "
                            >

                                <span
                                    class="activity-time"
                                >
                                    Kemarin, 14:30
                                </span>


                                <strong>
                                    Laporan bulanan
                                    diekspor
                                </strong>


                                <p>
                                    Admin mengunduh
                                    laporan aktivitas
                                    bulan September.
                                </p>

                            </div>

                        </div>



                        <div
                            class="activity-item"
                        >

                            <div
                                class="
                                    activity-point
                                    gray-point
                                "
                            ></div>


                            <div
                                class="
                                    activity-content
                                "
                            >

                                <span
                                    class="activity-time"
                                >
                                    10 Okt, 09:00
                                </span>


                                <strong>
                                    Pasien baru
                                    didaftarkan
                                </strong>


                                <p>
                                    Budi Santoso
                                    ditambahkan
                                    ke sistem.
                                </p>

                            </div>

                        </div>


                    </div>



                    <a
                        href="aktivitas.php"
                        class="
                            all-activity-button
                        "
                    >
                        Lihat Semua Aktivitas
                    </a>


                </div>


            </section>



            <!-- ================= MONITORING TERBARU ================= -->

            <section
                class="monitoring-card"
            >


                <div
                    class="monitoring-header"
                >

                    <h2>
                        Monitoring Terbaru
                    </h2>


                    <a
                        href="monitoring.php"
                    >
                        Lihat Semua
                    </a>

                </div>



                <div
                    class="table-wrapper"
                >

                    <table>


                        <thead>

                            <tr>

                                <th>
                                    Nama Pasien
                                </th>

                                <th>
                                    Lokasi Pasien
                                </th>

                                <th>
                                    Kondisi
                                </th>

                                <th>
                                    Tanggal Monitoring
                                </th>

                            </tr>

                        </thead>



                        <tbody>


                        <?php
                        if (
                            count(
                                $monitoring_terbaru
                            ) > 0
                        ):
                        ?>


                            <?php
                            foreach (
                                $monitoring_terbaru
                                as $row
                            ):
                            ?>


                                <?php

                                $nama =
                                    $row[
                                        'nama_pasien'
                                    ]
                                    ?? '-';

                                $inisial = '';

                                $nama_parts =
                                    preg_split(
                                        '/\s+/',
                                        trim($nama)
                                    );


                                foreach (
                                    array_slice(
                                        $nama_parts,
                                        0,
                                        2
                                    )
                                    as $part
                                ) {

                                    if (
                                        $part !== ''
                                    ) {

                                        $inisial .=
                                            strtoupper(
                                                substr(
                                                    $part,
                                                    0,
                                                    1
                                                )
                                            );

                                    }

                                }


                                $lokasi =
                                    $row[
                                        'status_lokasi'
                                    ]
                                    ?? '-';


                                $lokasi_class =
                                    (
                                        $lokasi ===
                                        'Luar Yayasan'
                                    )
                                    ? 'pink-badge'
                                    : 'cyan-badge';


                                $kondisi =
                                    $row[
                                        'kondisi'
                                    ]
                                    ?? '-';


                                $kondisi_lower =
                                    strtolower(
                                        trim(
                                            $kondisi
                                        )
                                    );


                                $kondisi_class =
                                    (
                                        $kondisi_lower ===
                                        'stabil'
                                    )
                                    ? 'stable'
                                    : 'warning';

                                ?>


                                <tr>


                                    <td>

                                        <div
                                            class="
                                                patient-name
                                            "
                                        >

                                            <span
                                                class="
                                                    patient-avatar
                                                    <?= $lokasi ===
                                                        'Luar Yayasan'
                                                        ? 'pink-avatar'
                                                        : 'cyan-avatar';
                                                    ?>
                                                "
                                            >

                                                <?= htmlspecialchars(
                                                    $inisial
                                                    ?: 'P'
                                                ); ?>

                                            </span>


                                            <span>

                                                <?= htmlspecialchars(
                                                    $nama
                                                ); ?>

                                            </span>

                                        </div>

                                    </td>



                                    <td>

                                        <span
                                            class="
                                                location-badge
                                                <?= $lokasi_class; ?>
                                            "
                                        >

                                            <?= htmlspecialchars(
                                                $lokasi
                                            ); ?>

                                        </span>

                                    </td>



                                    <td>

                                        <span
                                            class="
                                                condition-badge
                                                <?= $kondisi_class; ?>
                                            "
                                        >

                                            <?= htmlspecialchars(
                                                $kondisi
                                            ); ?>

                                        </span>

                                    </td>



                                    <td>

                                        <?= !empty(
                                            $row[
                                                'tanggal_monitoring'
                                            ]
                                        )
                                            ? date(
                                                'd M Y, H:i',
                                                strtotime(
                                                    $row[
                                                        'tanggal_monitoring'
                                                    ]
                                                )
                                            )
                                            : '-';
                                        ?>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        <?php else: ?>


                            <tr>

                                <td
                                    colspan="4"
                                    style="
                                        text-align:center;
                                        padding:30px;
                                        color:#8993a6;
                                    "
                                >

                                    Belum ada data monitoring.

                                </td>

                            </tr>


                        <?php endif; ?>


                        </tbody>


                    </table>

                </div>


            </section>


        </div>


    </main>


</div>



<!-- ================= CHART SCRIPT ================= -->

<script>

const chartData =
    <?= json_encode(
        $chart_data,
        JSON_UNESCAPED_UNICODE
    ); ?>;


const months = [

    "Jan",
    "Feb",
    "Mar",
    "Apr",
    "Mei",
    "Jun",
    "Jul",
    "Agu",
    "Sep",
    "Okt",
    "Nov",
    "Des"

];


const ctx =
    document
        .getElementById(
            "monitoringChart"
        )
        .getContext("2d");


let monitoringChart =
    new Chart(
        ctx,
        {

            type: "line",

            data: {

                labels: months,

                datasets: [

                    {

                        label:
                            "Jumlah Monitoring",

                        data:
                            chartData[<?= $tahun_sekarang; ?>],

                        borderColor:
                            "#2563eb",

                        backgroundColor:
                            "rgba(37, 99, 235, 0.08)",

                        borderWidth: 3,

                        pointBackgroundColor:
                            "#2563eb",

                        pointBorderColor:
                            "#ffffff",

                        pointBorderWidth: 2,

                        pointRadius: 5,

                        pointHoverRadius: 7,

                        tension: 0.35,

                        fill: true

                    }

                ]

            },


            options: {

                responsive: true,

                maintainAspectRatio: false,


                interaction: {

                    intersect: false,

                    mode: "index"

                },


                plugins: {

                    legend: {

                        display: false

                    },


                    tooltip: {

                        backgroundColor:
                            "#172033",

                        padding: 12,


                        titleFont: {

                            family:
                                "Poppins",

                            size: 12

                        },


                        bodyFont: {

                            family:
                                "Poppins",

                            size: 12

                        },


                        callbacks: {

                            label:
                                function(context) {

                                    return (
                                        " " +
                                        context.parsed.y +
                                        " monitoring"
                                    );

                                }

                        }

                    }

                },


                scales: {

                    x: {

                        grid: {

                            display: false

                        },


                        ticks: {

                            font: {

                                family:
                                    "Poppins",

                                size: 11

                            },


                            color:
                                "#667085"

                        }

                    },


                    y: {

                        beginAtZero: true,


                        grid: {

                            color:
                                "#eef2f7"

                        },


                        ticks: {

                            font: {

                                family:
                                    "Poppins",

                                size: 11

                            },


                            color:
                                "#667085",

                            precision: 0

                        }

                    }

                }

            }

        }
    );



/* ===============================
   GANTI TAHUN GRAFIK
=============================== */

const yearInput = document.getElementById("yearSelect");

function updateChartByYear() {
    const selectedYear = parseInt(yearInput.value, 10);

    if (!selectedYear || selectedYear < 1900 || selectedYear > <?= $tahun_sekarang; ?>) {
        return;
    }

    /* Tahun yang belum punya data tetap bisa dipilih, grafik akan bernilai 0 */
    const selectedData = chartData[selectedYear] || Array(12).fill(0);
    monitoringChart.data.datasets[0].data = selectedData;
    monitoringChart.data.datasets[0].label = "Jumlah Monitoring " + selectedYear;
    monitoringChart.update();
}

yearInput.addEventListener("change", updateChartByYear);
yearInput.addEventListener("keydown", function(event) {
    if (event.key === "Enter") {
        event.preventDefault();
        updateChartByYear();
        this.blur();
    }
});


/* ===============================
   SEARCH
=============================== */

document
    .getElementById(
        "searchInput"
    )
    .addEventListener(
        "keyup",
        function() {

            const keyword =
                this.value
                    .toLowerCase();


            const rows =
                document.querySelectorAll(
                    ".monitoring-card tbody tr"
                );


            rows.forEach(
                function(row) {

                    row.style.display =
                        row.innerText
                            .toLowerCase()
                            .includes(keyword)
                            ? ""
                            : "none";

                }
            );

        }
    );

</script>



<!-- ===============================
     PROFILE SCRIPT
=============================== -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {


        const profileTrigger =
            document.getElementById(
                "profileTrigger"
            );


        const profileDropdown =
            document.getElementById(
                "profileDropdown"
            );


        if (
            !profileTrigger ||
            !profileDropdown
        ) {

            return;

        }



        /* ===============================
           BUKA / TUTUP PROFILE
        =============================== */

        profileTrigger.addEventListener(
            "click",
            function (event) {

                event.preventDefault();

                event.stopPropagation();


                profileDropdown
                    .classList
                    .toggle("show");


                profileTrigger
                    .classList
                    .toggle("active");

            }
        );



        /* ===============================
           KLIK DI DALAM DROPDOWN
        =============================== */

        profileDropdown.addEventListener(
            "click",
            function (event) {

                event.stopPropagation();

            }
        );



        /* ===============================
           KLIK DI LUAR
        =============================== */

        document.addEventListener(
            "click",
            function () {

                profileDropdown
                    .classList
                    .remove("show");


                profileTrigger
                    .classList
                    .remove("active");

            }
        );


    }
);

</script>



<!-- ===============================
     NOTIFICATION SCRIPT
=============================== -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {


        const button =
            document.getElementById(
                "notificationButton"
            );


        const dropdown =
            document.getElementById(
                "notificationDropdown"
            );


        const dot =
            document.getElementById(
                "notificationDot"
            );


        const markRead =
            document.getElementById(
                "markNotificationsRead"
            );


        if (
            !button ||
            !dropdown
        ) {

            console.warn(
                "Elemen notifikasi tidak ditemukan."
            );

            return;

        }



        button.addEventListener(
            "click",
            function (event) {

                event.preventDefault();

                event.stopPropagation();


                dropdown
                    .classList
                    .toggle("show");

            }
        );



        dropdown.addEventListener(
            "click",
            function (event) {

                event.stopPropagation();

            }
        );



        document.addEventListener(
            "click",
            function () {

                dropdown
                    .classList
                    .remove("show");

            }
        );



        if (markRead) {

            markRead.addEventListener(
                "click",
                function () {


                    if (dot) {

                        dot
                            .classList
                            .add("hidden");

                    }


                    dropdown
                        .classList
                        .remove("show");

                }
            );

        }


    }
);

</script>



</body>

</html>