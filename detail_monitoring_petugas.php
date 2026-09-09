<?php

session_start();

/* =========================================================
   CEK LOGIN
========================================================= */

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}


/* =========================================================
   CEK ROLE PETUGAS
========================================================= */

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'petugas') {
    die("Anda tidak memiliki akses ke halaman ini.");
}


require_once "koneksi.php";


/* =========================================================
   CEK ID MONITORING
========================================================= */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: monitoring_petugas.php");
    exit;
}

$id_monitoring = (int) $_GET['id'];


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
        p.nomor_registrasi,

        u.nama_lengkap AS nama_user

    FROM monitoring m

    LEFT JOIN pasien p
        ON m.id_pasien = p.id_pasien

    LEFT JOIN users u
        ON m.id_user = u.id_user

    WHERE m.id_monitoring = ?
";


$stmt = mysqli_prepare($conn, $query);


if (!$stmt) {
    die("Query gagal diproses.");
}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_monitoring
);


mysqli_stmt_execute($stmt);


$result = mysqli_stmt_get_result($stmt);


$monitoring = mysqli_fetch_assoc($result);


mysqli_stmt_close($stmt);


/* =========================================================
   CEK DATA
========================================================= */

if (!$monitoring) {
    die("Data monitoring tidak ditemukan.");
}


/* =========================================================
   FORMAT KONDISI
========================================================= */

if ($monitoring['kondisi'] === 'Stabil') {

    $kondisi_class = 'badge-stabil';

} elseif ($monitoring['kondisi'] === 'Perlu Pantauan') {

    $kondisi_class = 'badge-pantauan';

} elseif ($monitoring['kondisi'] === 'Darurat') {

    $kondisi_class = 'badge-darurat';

} else {

    $kondisi_class = 'badge-default';

}


/* =========================================================
   FORMAT TANGGAL
========================================================= */

$tanggal_monitoring = !empty($monitoring['tanggal_monitoring'])
    ? date(
        'd-m-Y H:i',
        strtotime($monitoring['tanggal_monitoring'])
    )
    : '-';


/* =========================================================
   NAMA USER LOGIN
========================================================= */

$nama_user_login = $_SESSION['nama_lengkap'] ?? 'Petugas';


/* =========================================================
   INISIAL USER LOGIN
========================================================= */

$inisial_user = strtoupper(
    substr(
        trim($nama_user_login),
        0,
        1
    )
);

if ($inisial_user === '') {
    $inisial_user = 'P';
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
        Detail Monitoring - SIPM ODGJ
    </title>


    <!-- GOOGLE FONT -->

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
           LAYOUT
        ===================================================== */

        .dashboard-layout {
            display: flex;
            min-height: 100vh;
        }


        .sidebar {
            width: 218px;
            min-width: 218px;
            max-width: 218px;
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }


        .main-content {
            flex: 1;
            min-width: 0;
            margin-left: 218px;
            width: calc(100% - 218px);
        }


        .page-content {
            padding: 28px;
        }


        /* =====================================================
           SIDEBAR
        ===================================================== */

        .sidebar-brand {
            text-align: center;
            padding: 18px 10px 12px;
        }


        .sidebar-brand img {
            width: 58px;
            height: 58px;
            object-fit: contain;
            display: block;
            margin: 0 auto 7px;
        }


        .sidebar-brand h2 {
            margin: 0;
            font-size: 17px;
            line-height: 1.3;
        }


        .sidebar-brand p {
            margin: 3px 0 0;
            font-size: 9px;
            line-height: 1.3;
        }


        /* =====================================================
           TOPBAR
        ===================================================== */

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 70px;
            padding: 0 24px;
            background: #ffffff;
            border-bottom: 1px solid #e5eaf2;
            position: relative;
            z-index: 1000;
        }


        .topbar h1 {
            margin: 0;
            color: #2864e6;
            font-size: 20px;
            font-weight: 700;
        }


        .topbar-right {
            display: flex;
            align-items: center;
            margin-left: auto;
        }


        /* =====================================================
           PROFILE PETUGAS - BIRU
        ===================================================== */

        .user-profile {
            position: relative;
            display: flex;
            align-items: center;
        }


        .profile-button {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;

            border: none;
            background: transparent;

            padding: 5px 8px;

            border-radius: 12px;

            color: #17243a;

            font-family: 'Poppins', sans-serif;

            cursor: pointer;

            transition: background .2s ease;
        }


        .profile-button:hover {
            background: #f4f7fc;
        }


        /* NAMA + ROLE */

        .profile-top-text {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: center;

            line-height: 1.15;

            min-width: 80px;
        }


        .profile-top-text strong {
            margin: 0;

            color: #17243a;

            font-size: 14px;

            font-weight: 600;

            white-space: nowrap;
        }


        .profile-top-text small {
            margin-top: 3px;

            color: #8a95a8;

            font-size: 10px;

            font-weight: 400;

            text-transform: lowercase;
        }


        /* AVATAR BIRU */

        .profile-top-avatar {
            width: 48px;
            height: 48px;

            min-width: 48px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: #2864e6;

            color: #ffffff;

            font-size: 18px;

            font-weight: 600;

            line-height: 1;
        }


        /* CHEVRON */

        .profile-arrow {
            font-size: 11px;

            color: #7a8499;

            transition: transform .2s ease;

            margin-left: 1px;
        }


        .profile-button.active .profile-arrow {
            transform: rotate(180deg);
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


        .header-actions {
            display: flex;
            align-items: center;
            gap: 9px;
        }


        /* =====================================================
           BUTTON
        ===================================================== */

        .back-button,
        .edit-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;

            padding: 11px 16px;

            border-radius: 8px;

            text-decoration: none;

            font-family: Poppins, sans-serif;

            font-size: 12px;

            font-weight: 500;

            box-sizing: border-box;
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
           PROFILE CARD PASIEN
        ===================================================== */

        .monitoring-profile {
            background: #ffffff;

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
           INFORMATION CARD
        ===================================================== */

        .information-card {
            background: #ffffff;

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


        /* =====================================================
           INFO GRID
        ===================================================== */

        .info-grid {
            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 20px 35px;
        }


        .info-item {
            padding-bottom: 15px;

            border-bottom:
                1px solid #edf0f5;
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


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 800px) {

            .sidebar {
                width: 190px;

                min-width: 190px;

                max-width: 190px;
            }


            .main-content {
                margin-left: 190px;

                width:
                    calc(100% - 190px);
            }


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


        @media (max-width: 600px) {

            .topbar {
                padding: 0 15px;
            }


            .topbar h1 {
                font-size: 17px;
            }


            .profile-top-text {
                display: none;
            }


            .profile-top-avatar {
                width: 40px;
                height: 40px;

                min-width: 40px;

                font-size: 15px;
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


        <div class="sidebar-brand">

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


            <!-- DASHBOARD -->

            <a
                href="dashboard_petugas.php"
                class="menu-item"
            >

                <i class="bi bi-grid"></i>

                <span>
                    Dashboard
                </span>

            </a>


            <!-- DATA PASIEN -->

            <a
                href="data_pasien_petugas.php"
                class="menu-item"
            >

                <i class="bi bi-people"></i>

                <span>
                    Data Pasien
                </span>

            </a>


            <!-- MONITORING -->

            <a
                href="monitoring_petugas.php"
                class="menu-item active"
            >

                <i class="bi bi-clipboard2-pulse"></i>

                <span>
                    Monitoring
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
    ===================================================== -->

    <main class="main-content">


        <!-- =================================================
             TOPBAR
        ================================================= -->

        <header class="topbar">


            <h1>
                Detail Monitoring
            </h1>


            <div class="topbar-right">


                <!-- =================================================
                     PROFILE PETUGAS
                ================================================= -->

                <div
                    class="user-profile"
                    id="userProfile"
                >


                    <button
                        type="button"
                        class="profile-button"
                        id="profileButton"
                        aria-expanded="false"
                    >


                        <!-- NAMA DAN ROLE -->

                        <span class="profile-top-text">


                            <strong>

                                <?= htmlspecialchars(
                                    $nama_user_login
                                ); ?>

                            </strong>


                            <small>
                                petugas
                            </small>


                        </span>


                        <!-- AVATAR BIRU -->

                        <span class="profile-top-avatar">


                            <?= htmlspecialchars(
                                $inisial_user
                            ); ?>


                        </span>


                        <!-- CHEVRON -->

                        <i
                            class="bi bi-chevron-down profile-arrow"
                        ></i>


                    </button>


                </div>


            </div>


        </header>


        <!-- =================================================
             CONTENT
        ================================================= -->

        <div class="page-content">


            <!-- PAGE HEADER -->

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


                    <!-- KEMBALI -->

                    <a
                        href="monitoring_petugas.php"
                        class="back-button"
                    >

                        <i class="bi bi-arrow-left"></i>

                        Kembali

                    </a>


                    <!-- EDIT -->

                    <a
                        href="edit_monitoring_petugas.php?id=<?= (int) $monitoring['id_monitoring']; ?>"
                        class="edit-button"
                    >

                        <i class="bi bi-pencil"></i>

                        Edit

                    </a>


                </div>


            </div>


            <!-- =================================================
                 PROFILE PASIEN
            ================================================= -->

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
                                $monitoring['nama_pasien'] ?? '-'
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


            <!-- =================================================
                 INFORMASI MONITORING
            ================================================= -->

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


                    <!-- TANGGAL MONITORING -->

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


                    <!-- BERAT BADAN -->

                    <div class="info-item">


                        <span class="info-label">
                            Berat Badan
                        </span>


                        <span class="info-value weight-value">


                            <?= $monitoring['berat_badan'] !== null

                                ? htmlspecialchars(
                                    $monitoring['berat_badan']
                                ) . ' kg'

                                : '-';

                            ?>


                        </span>


                    </div>


                    <!-- AKTIVITAS HARIAN -->

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


                    <!-- PERILAKU -->

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


                    <!-- CATATAN -->

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


                    <!-- PETUGAS MONITORING -->

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


                    <!-- DIBUAT PADA -->

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