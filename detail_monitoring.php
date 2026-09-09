<?php

session_start();

/* =========================================================
   PETUGAS JANGAN MASUK KE HALAMAN DETAIL ADMIN
========================================================= */

if (
    isset($_SESSION['role']) &&
    $_SESSION['role'] === 'petugas'
) {

    $id = isset($_GET['id'])
        ? (int) $_GET['id']
        : 0;

    if ($id > 0) {

        header(
            "Location: detail_monitoring_petugas.php?id=" . $id
        );

        exit;
    }
}

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

require_once "koneksi.php";


/* =========================
   CEK ID MONITORING
========================= */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: monitoring.php");
    exit;
}

$id_monitoring = (int) $_GET['id'];


/* =========================
   AMBIL DATA MONITORING
========================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        m.*,
        p.nama_pasien,
        p.nomor_registrasi,
        u.nama_lengkap
     FROM monitoring m
     LEFT JOIN pasien p
        ON m.id_pasien = p.id_pasien
     LEFT JOIN users u
        ON m.id_user = u.id_user
     WHERE m.id_monitoring = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_monitoring
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$monitoring = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* =========================
   CEK DATA
========================= */

if (!$monitoring) {
    die("Data monitoring tidak ditemukan.");
}


/* =========================
   FORMAT KONDISI
========================= */

if ($monitoring['kondisi'] === 'Stabil') {

    $kondisi_class = 'badge-stabil';

} elseif ($monitoring['kondisi'] === 'Perlu Pantauan') {

    $kondisi_class = 'badge-pantauan';

} elseif ($monitoring['kondisi'] === 'Darurat') {

    $kondisi_class = 'badge-darurat';

} else {

    $kondisi_class = 'badge-default';

}


/* =========================
   FORMAT TANGGAL
========================= */

$tanggal_monitoring = !empty($monitoring['tanggal_monitoring'])
    ? date(
        'd-m-Y H:i',
        strtotime($monitoring['tanggal_monitoring'])
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
        Detail Monitoring - Sistem Informasi ODGJ
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
           BUTTON
        ========================= */

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

        }


        .back-button {

            background: white;

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

            color: white;

        }


        .edit-button:hover {

            background: #1f55c8;

        }


        /* =========================
           PROFILE CARD
        ========================= */

        .monitoring-profile {

            background: white;

            border: 1px solid #e1e7f0;

            border-radius: 12px;

            overflow: hidden;

            margin-bottom: 20px;

        }


        .monitoring-profile-header {

            display: flex;

            align-items: center;

            gap: 17px;

            padding: 23px;

            border-bottom: 1px solid #edf0f5;

        }


        .patient-avatar {

            width: 58px;

            height: 58px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            flex-shrink: 0;

            background: #e5efff;

            color: #2864e6;

            font-size: 20px;

            font-weight: 600;

        }


        .patient-info h3 {

            margin: 0;

            font-size: 18px;

            font-weight: 700;

            color: #17243a;

        }


        .patient-info p {

            margin: 4px 0 0;

            color: #8a95a8;

            font-size: 11px;

        }


        .monitoring-status {

            margin-left: auto;

        }


        /* =========================
           BADGE
        ========================= */

        .badge {

            display: inline-flex;

            align-items: center;

            padding: 7px 12px;

            border-radius: 20px;

            font-size: 10px;

            font-weight: 500;

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


        /* =========================
           INFORMATION CARD
        ========================= */

        .information-card {

            background: white;

            border: 1px solid #e1e7f0;

            border-radius: 12px;

            padding: 24px;

            margin-bottom: 20px;

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


        /* =========================
           INFO GRID
        ========================= */

        .info-grid {

            display: grid;

            grid-template-columns: repeat(2, 1fr);

            gap: 20px 35px;

        }


        .info-item {

            padding-bottom: 15px;

            border-bottom: 1px solid #edf0f5;

        }


        .info-item.full-width {

            grid-column: 1 / -1;

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

            line-height: 1.6;

            white-space: pre-line;

        }


        .weight-value {

            font-weight: 600;

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

            .header-actions {

                width: 100%;

            }

            .info-grid {

                grid-template-columns: 1fr;

            }

            .info-item.full-width {

                grid-column: auto;

            }

            .monitoring-profile-header {

                align-items: flex-start;

                flex-wrap: wrap;

            }

            .monitoring-status {

                margin-left: 0;

                width: 100%;

            }

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
                class="menu-item"
            >
                <i class="bi bi-person-gear"></i>
                <span>Data User</span>
            </a>


            <a
                href="monitoring.php"
                class="menu-item active"
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
         MAIN
    ========================= -->

    <main class="main-content">


        <!-- TOPBAR -->

        <header class="topbar">

            <h1>
                Detail Monitoring
            </h1>


            <div class="topbar-right">


                <div class="search-box">

                    <i class="bi bi-search"></i>

                    <input
                        type="text"
                        placeholder="Search..."
                    >

                </div>


                <i
                    class="bi bi-bell"
                    style="font-size:20px;color:#26334a;"
                ></i>


                <div class="profile">

                    <strong>
                        Selamat Datang, Admin
                    </strong>

                    <small>
                        Admin Profile
                    </small>

                </div>


                <div class="profile-avatar">
                    A
                </div>


            </div>

        </header>


        <!-- CONTENT -->

        <div class="page-content">


            <div class="page-header">


                <div class="page-title">

                    <h2>
                        Detail Monitoring Pasien
                    </h2>

                    <p>
                        Informasi lengkap hasil monitoring pasien
                    </p>

                </div>


                <div class="header-actions">


                    <a
                        href="monitoring.php"
                        class="back-button"
                    >
                        <i class="bi bi-arrow-left"></i>
                        Kembali
                    </a>


                    <a
                        href="edit_monitoring.php?id=<?= $monitoring['id_monitoring']; ?>"
                        class="edit-button"
                    >
                        <i class="bi bi-pencil"></i>
                        Edit
                    </a>


                </div>


            </div>


            <!-- =========================
                 PROFILE PASIEN
            ========================= -->

            <div class="monitoring-profile">


                <div class="monitoring-profile-header">


                    <div class="patient-avatar">

                        <?= strtoupper(
                            substr(
                                $monitoring['nama_pasien'] ?? 'P',
                                0,
                                1
                            )
                        ); ?>

                    </div>


                    <div class="patient-info">

                        <h3>
                            <?= htmlspecialchars(
                                $monitoring['nama_lengkap'] ?? '-'
                                ); ?>
                        </h3>

                        <p>
                            No. Registrasi:
                            <?= htmlspecialchars(
                                $monitoring['nomor_registrasi'] ?? '-'
                            ); ?>
                        </p>

                    </div>


                    <div class="monitoring-status">

                        <span
                            class="badge <?= $kondisi_class; ?>"
                        >
                            <?= htmlspecialchars(
                                $monitoring['kondisi'] ?? '-'
                            ); ?>
                        </span>

                    </div>


                </div>


            </div>


            <!-- =========================
                 INFORMASI MONITORING
            ========================= -->

            <div class="information-card">


                <div class="section-title">

                    <div class="section-icon">

                        <i class="bi bi-clipboard2-pulse"></i>

                    </div>

                    <h3>
                        Informasi Monitoring
                    </h3>

                </div>


                <div class="info-grid">


                    <div class="info-item">

                        <span class="info-label">
                            Tanggal Monitoring
                        </span>

                        <span class="info-value">
                            <?= htmlspecialchars(
                                $tanggal_monitoring
                            ); ?>
                        </span>

                    </div>


                    <div class="info-item">

                        <span class="info-label">
                            Berat Badan
                        </span>

                        <span class="info-value weight-value">

                            <?=
                                $monitoring['berat_badan'] !== null
                                ? htmlspecialchars(
                                    $monitoring['berat_badan']
                                ) . ' kg'
                                : '-';
                            ?>

                        </span>

                    </div>


                    <div class="info-item full-width">

                        <span class="info-label">
                            Aktivitas Harian
                        </span>

                        <span class="info-value">
                            <?= nl2br(
                                htmlspecialchars(
                                    $monitoring['aktivitas_harian'] ?? '-'
                                )
                            ); ?>
                        </span>

                    </div>


                    <div class="info-item full-width">

                        <span class="info-label">
                            Perilaku
                        </span>

                        <span class="info-value">
                            <?= nl2br(
                                htmlspecialchars(
                                    $monitoring['perilaku'] ?? '-'
                                )
                            ); ?>
                        </span>

                    </div>


                    <div class="info-item full-width">

                        <span class="info-label">
                            Catatan
                        </span>

                        <span class="info-value">
                            <?= nl2br(
                                htmlspecialchars(
                                    $monitoring['catatan'] ?? '-'
                                )
                            ); ?>
                        </span>

                    </div>


                    <div class="info-item">

                        <span class="info-label">
                            Petugas Monitoring
                        </span>

                        <span class="info-value">
                            <?= htmlspecialchars(
                                $monitoring['nama_user'] ?? '-'
                            ); ?>
                        </span>

                    </div>


                    <div class="info-item">

                        <span class="info-label">
                            Dibuat Pada
                        </span>

                        <span class="info-value">
                            <?= !empty($monitoring['created_at'])
                                ? date(
                                    'd-m-Y H:i',
                                    strtotime(
                                        $monitoring['created_at']
                                    )
                                )
                                : '-';
                            ?>
                        </span>

                    </div>


                </div>


            </div>


        </div>


    </main>


</div>


</body>

</html>