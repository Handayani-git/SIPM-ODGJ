<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

/* =========================
   CEK LOGIN
========================= */

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
    "SELECT nama_lengkap, role
     FROM users
     WHERE id_user = $id_user
     LIMIT 1"
);

$user_login = mysqli_fetch_assoc($query_user);

$nama_user = $user_login['nama_lengkap'] ?? 'Admin';
$role_user = $user_login['role'] ?? 'Admin';


/* =========================
   INISIAL USER
========================= */

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
   NOTIFIKASI CETAK LAPORAN
========================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['aksi'])
    && $_POST['aksi'] === 'cetak_laporan'
) {

    $id_user_notif = (int) $_SESSION['id_user'];

    $judul = "Laporan monitoring dicetak";

    $pesan = "Admin mencetak laporan monitoring pasien ODGJ.";

    $jenis = "laporan_cetak";

    $id_referensi = null;


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
            $id_user_notif,
            $judul,
            $pesan,
            $jenis,
            $id_referensi
        );

        mysqli_stmt_execute($stmtNotif);

        mysqli_stmt_close($stmtNotif);
    }


    echo "success";
    exit;
}


/* =========================
   FILTER TANGGAL
========================= */

$tanggal_mulai = $_GET['tanggal_mulai'] ?? '';

$tanggal_selesai = $_GET['tanggal_selesai'] ?? '';


/* =========================
   QUERY LAPORAN
========================= */

$sql = "
    SELECT
        m.id_monitoring,
        m.tanggal_monitoring,
        m.berat_badan,
        m.kondisi,
        m.aktivitas_harian,
        m.perilaku,
        m.catatan,
        p.nama_pasien,
        u.nama_lengkap AS nama_user

    FROM monitoring m

    LEFT JOIN pasien p
        ON m.id_pasien = p.id_pasien

    LEFT JOIN users u
        ON m.id_user = u.id_user
";


/* =========================
   FILTER WHERE
========================= */

$where = [];


if (!empty($tanggal_mulai)) {

    $tanggal_mulai_safe = mysqli_real_escape_string(
        $conn,
        $tanggal_mulai
    );

    $where[] =
        "DATE(m.tanggal_monitoring) >= '$tanggal_mulai_safe'";
}


if (!empty($tanggal_selesai)) {

    $tanggal_selesai_safe = mysqli_real_escape_string(
        $conn,
        $tanggal_selesai
    );

    $where[] =
        "DATE(m.tanggal_monitoring) <= '$tanggal_selesai_safe'";
}


if (!empty($where)) {

    $sql .= " WHERE " . implode(" AND ", $where);

}


/* =========================
   URUTKAN DATA
========================= */

$sql .= "
    ORDER BY m.tanggal_monitoring DESC
";


/* =========================
   JALANKAN QUERY
========================= */

$query = mysqli_query($conn, $sql);


if (!$query) {

    die(
        "Query laporan gagal: "
        . mysqli_error($conn)
    );

}


/* =========================
   HITUNG DATA
========================= */

$total_laporan = mysqli_num_rows($query);


/* =========================
   DATA REKAP
========================= */

$data_laporan = [];

$total_berat = 0;

$jumlah_berat = 0;

$kondisi_stabil = 0;

$kondisi_pantauan = 0;

$kondisi_lainnya = 0;


while ($row = mysqli_fetch_assoc($query)) {

    $data_laporan[] = $row;


    /* Berat badan */

    if (
        $row['berat_badan'] !== null
        &&
        $row['berat_badan'] !== ''
    ) {

        $total_berat += (float) $row['berat_badan'];

        $jumlah_berat++;

    }


    /* Kondisi */

    $kondisi = strtolower(
        trim($row['kondisi'] ?? '')
    );


    if ($kondisi === 'stabil') {

        $kondisi_stabil++;

    } elseif (
        strpos($kondisi, 'pantau') !== false
    ) {

        $kondisi_pantauan++;

    } else {

        $kondisi_lainnya++;

    }

}


/* =========================
   RATA-RATA BERAT
========================= */

$rata_rata_berat =
    $jumlah_berat > 0
        ? $total_berat / $jumlah_berat
        : 0;

?>

<!DOCTYPE html>

<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <!-- FONT -->

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


    <!-- ICON -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <style>

        * {
            box-sizing: border-box;
        }


        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: #f5f8fd;
            color: #17243a;
        }


        /* =========================
           LAYOUT
        ========================= */

        .dashboard-layout {
            min-height: 100vh;
        }


        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 218px;
            height: 100vh;
            background: #eef4ff;
            border-right: 1px solid #dfe7f4;
            z-index: 1000;
        }


        .sidebar-logo {
            text-align: center;
            padding: 24px 10px 20px;
        }


        .sidebar-logo img {
            width: 58px;
            height: 58px;
            object-fit: contain;
            display: block;
            margin: 0 auto 7px;
        }


        .sidebar-logo h2 {
            margin: 0;
            font-size: 17px;
            font-weight: 700;
        }


        .sidebar-logo p {
            margin: 4px 0 0;
            font-size: 9px;
            color: #65738a;
        }


        .sidebar-menu {
            padding: 5px 10px;
        }


        .menu-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px 12px;
            margin-bottom: 3px;
            border-radius: 8px;
            color: #17243a;
            text-decoration: none;
            font-size: 13px;
        }


        .menu-item i {
            font-size: 16px;
        }


        .menu-item:hover {
            background: #e1ebff;
        }


        .menu-item.active {
            background: #dce9ff;
            color: #2864e6;
            font-weight: 600;
            border-left: 3px solid #2864e6;
        }


        .sidebar-bottom {
            position: absolute;
            left: 10px;
            right: 10px;
            bottom: 18px;
        }


        .logout-button {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 10px 12px;
            color: #e23b3b;
            text-decoration: none;
            font-size: 12px;
        }


        /* =========================
           MAIN
        ========================= */

        .main-content {
            margin-left: 218px;
            width: calc(100% - 218px);
            min-height: 100vh;
        }


        /* =========================
           TOPBAR
        ========================= */

        .topbar {
            height: 82px;
            background: white;
            border-bottom: 1px solid #e2e8f1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
        }


        .topbar h1 {
            margin: 0;
            color: #2864e6;
            font-size: 23px;
            font-weight: 700;
        }


        .topbar-right {
            display: flex;
            align-items: center;
            gap: 17px;
        }


        /* =========================
           PROFILE
        ========================= */

        .profile-wrapper {
            position: relative;
        }


        .profile-button {
            display: flex;
            align-items: center;
            gap: 10px;
            border: none;
            background: transparent;
            padding: 5px 7px;
            border-radius: 10px;
            cursor: pointer;
            font-family: inherit;
            transition: background 0.2s ease;
        }


        .profile-button:hover {
            background: #f2f6fc;
        }


        .profile {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: center;
            line-height: 1.2;
            white-space: nowrap;
        }


        .profile strong {
            font-size: 11px;
            font-weight: 700;
            color: #17243a;
        }


        .profile small {
            margin-top: 3px;
            font-size: 8px;
            color: #7a8499;
        }


        .profile-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #2864e6;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            flex-shrink: 0;
        }


        .profile-chevron {
            font-size: 11px;
            color: #7a8499;
            transition: transform 0.2s ease;
        }


        .profile-button.active .profile-chevron {
            transform: rotate(180deg);
        }


        /* =========================
           DROPDOWN PROFIL
        ========================= */

        .profile-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: 220px;
            background: white;
            border: 1px solid #e1e7f0;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(30, 50, 80, 0.12);
            padding: 8px;
            z-index: 2000;

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
            padding: 10px;
            border-bottom: 1px solid #edf0f5;
            margin-bottom: 5px;
        }


        .profile-dropdown-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #2864e6;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            flex-shrink: 0;
        }


        .profile-dropdown-info {
            min-width: 0;
        }


        .profile-dropdown-info strong {
            display: block;
            color: #17243a;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }


        .profile-dropdown-info span {
            display: inline-block;
            margin-top: 3px;
            color: #7a8499;
            font-size: 9px;
        }


        .profile-menu-item {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 9px 10px;
            border-radius: 8px;
            color: #526078;
            text-decoration: none;
            font-size: 10px;
            transition: background 0.2s ease;
        }


        .profile-menu-item:hover {
            background: #f4f7fb;
        }


        .profile-menu-item i {
            font-size: 14px;
        }


        .profile-menu-item.logout {
            color: #e23b3b;
        }


        /* =========================
           CONTENT
        ========================= */

        .page-content {
            padding: 28px;
        }


        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
        }


        .page-title h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }


        .page-title p {
            margin: 6px 0 0;
            font-size: 12px;
            color: #7a8499;
        }


        .print-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 17px;
            border: none;
            border-radius: 8px;
            background: #2864e6;
            color: white;
            font-family: inherit;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
        }


        .print-button:hover {
            background: #1f55c8;
        }


        /* =========================
           FILTER
        ========================= */

        .filter-card {
            background: white;
            border: 1px solid #e1e7f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }


        .filter-title {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 17px;
            font-size: 14px;
            font-weight: 600;
        }


        .filter-title i {
            color: #2864e6;
        }


        .filter-form {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 12px;
            align-items: end;
        }


        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }


        .filter-group label {
            font-size: 10px;
            color: #65738a;
        }


        .filter-group input {
            height: 40px;
            padding: 0 12px;
            border: 1px solid #dce3ee;
            border-radius: 8px;
            outline: none;
            font-family: inherit;
            font-size: 11px;
            color: #26334a;
        }


        .filter-group input:focus {
            border-color: #2864e6;
        }


        .filter-button {
            height: 40px;
            padding: 0 17px;
            border: none;
            border-radius: 8px;
            background: #2864e6;
            color: white;
            font-family: inherit;
            font-size: 11px;
            cursor: pointer;
        }


        .filter-button:hover {
            background: #1f55c8;
        }


        /* =========================
           SUMMARY
        ========================= */

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }


        .summary-card {
            background: white;
            border: 1px solid #e1e7f0;
            border-radius: 12px;
            padding: 19px;
            display: flex;
            align-items: center;
            gap: 13px;
        }


        .summary-icon {
            width: 43px;
            height: 43px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #eaf1ff;
            color: #2864e6;
            font-size: 18px;
        }


        .summary-label {
            display: block;
            color: #8993a6;
            font-size: 10px;
            margin-bottom: 3px;
        }


        .summary-value {
            font-size: 20px;
            font-weight: 700;
            color: #17243a;
        }


        /* =========================
           TABLE
        ========================= */

        .table-card {
            background: white;
            border: 1px solid #e1e7f0;
            border-radius: 12px;
            overflow: hidden;
        }


        .table-header {
            padding: 20px;
            border-bottom: 1px solid #edf0f5;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }


        .table-header h3 {
            margin: 0;
            font-size: 15px;
            font-weight: 600;
        }


        .table-header span {
            color: #8993a6;
            font-size: 10px;
        }


        .table-wrapper {
            overflow-x: auto;
        }


        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1050px;
        }


        th {
            background: #f8faff;
            color: #526078;
            text-align: left;
            padding: 12px 13px;
            font-size: 10px;
            font-weight: 600;
            white-space: nowrap;
        }


        td {
            padding: 13px;
            border-top: 1px solid #edf0f5;
            color: #26334a;
            font-size: 10px;
            vertical-align: top;
        }


        tbody tr:hover {
            background: #fbfcff;
        }


        .patient-name {
            font-weight: 600;
        }


        .condition {
            display: inline-flex;
            padding: 5px 9px;
            border-radius: 15px;
            font-size: 9px;
            font-weight: 500;
        }


        .condition.stabil {
            background: #dcf8e8;
            color: #159447;
        }


        .condition.pantauan {
            background: #fff1d8;
            color: #b97900;
        }


        .condition.lainnya {
            background: #eee9ff;
            color: #7655d8;
        }


        .empty-data {
            text-align: center;
            padding: 40px !important;
            color: #8993a6;
        }


        .empty-data i {
            display: block;
            font-size: 28px;
            margin-bottom: 8px;
        }


        /* =========================
           PRINT
        ========================= */

        .print-header,
        .print-period,
        .print-footer {
            display: none;
        }


        @media print {

            @page {
                size: A4 landscape;
                margin: 15mm;
            }


            body {
                background: white !important;
                font-family: Arial, sans-serif;
                color: #000;
            }


            .sidebar,
            .topbar,
            .page-header .print-button,
            .filter-card {
                display: none !important;
            }


            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
            }


            .page-content {
                padding: 0 !important;
            }


            .print-header {
                display: block !important;
                text-align: center;
                margin-bottom: 18px;
                padding-bottom: 12px;
                border-bottom: 2px solid #000;
            }


            .print-header {
                position: relative;
            }

            .print-logo {
                width: 62px;
                height: 62px;
                object-fit: contain;
                display: block;
                margin: 0 auto 6px;
            }


            .print-header h2 {
                margin: 4px 0;
                font-size: 15px;
                font-weight: 700;
            }


            .print-header p {
                margin: 2px 0;
                font-size: 10px;
            }


            .print-period {
                display: block !important;
                margin-top: 8px;
                font-size: 10px;
            }


            /* Sembunyikan ringkasan statistik saat dicetak */
            .summary-grid {
                display: none !important;
            }


            .summary-card {
                border: 1px solid #999 !important;
                box-shadow: none !important;
                padding: 10px !important;
            }


            .summary-icon {
                display: none !important;
            }


            .summary-label {
                font-size: 9px !important;
            }


            .summary-value {
                font-size: 14px !important;
            }


            .table-card {
                border: none !important;
                border-radius: 0 !important;
            }


            .table-header {
                padding: 0 0 8px !important;
                border: none !important;
            }


            .table-header h3 {
                font-size: 12px;
            }


            .table-header span {
                font-size: 9px;
            }


            .table-wrapper {
                overflow: visible !important;
            }


            table {
                width: 100% !important;
                min-width: 0 !important;
                border-collapse: collapse !important;
            }


            th {
                background: #eee !important;
                color: #000 !important;
                border: 1px solid #999 !important;
                padding: 6px !important;
                font-size: 8px !important;
            }


            td {
                border: 1px solid #aaa !important;
                padding: 6px !important;
                font-size: 8px !important;
                color: #000 !important;
            }


            .condition {
                background: none !important;
                color: #000 !important;
                padding: 0 !important;
            }


            .empty-data {
                color: #000 !important;
            }


            /* Footer aplikasi tidak ditampilkan saat dicetak */
            .print-footer {
                display: none !important;
            }

        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 900px) {

            .filter-form {
                grid-template-columns: 1fr 1fr;
            }


            .summary-grid {
                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 700px) {

            .sidebar {
                width: 190px;
            }


            .main-content {
                margin-left: 190px;
                width: calc(100% - 190px);
            }


            .topbar {
                padding: 0 15px;
            }


            .topbar-right {
                gap: 8px;
            }


            .page-content {
                padding: 18px;
            }


            .page-header {
                align-items: flex-start;
                flex-direction: column;
                gap: 14px;
            }


            .filter-form {
                grid-template-columns: 1fr;
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


    <!-- =========================
         SIDEBAR
    ========================= -->

    <aside class="sidebar">


        <div class="sidebar-logo">

            <img
                src="assets/img/logo YCKA.png"
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
                class="menu-item"
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
                class="menu-item active"
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


        <!-- TOPBAR -->

        <header class="topbar">

            <h1>
                Laporan
            </h1>


            <div class="topbar-right">

                <div class="profile-wrapper">


                    <button
                        type="button"
                        class="profile-button"
                        id="profileButton"
                        onclick="toggleProfile()"
                    >

                        <div class="profile">

                            <strong>
                                <?= htmlspecialchars($nama_user); ?>
                            </strong>

                            <small>
                                <?= htmlspecialchars($role_user); ?>
                            </small>

                        </div>


                        <div class="profile-avatar">
                            <?= htmlspecialchars($inisial_user); ?>
                        </div>


                        <i class="bi bi-chevron-down profile-chevron"></i>

                    </button>


                    <!-- DROPDOWN PROFIL -->

                    <div
                        class="profile-dropdown"
                        id="profileDropdown"
                    >


                        <div class="profile-dropdown-header">

                            <div class="profile-dropdown-avatar">
                                <?= htmlspecialchars($inisial_user); ?>
                            </div>


                            <div class="profile-dropdown-info">

                                <strong>
                                    <?= htmlspecialchars($nama_user); ?>
                                </strong>

                                <span>
                                    <?= htmlspecialchars($role_user); ?>
                                </span>

                            </div>

                        </div>


                        <a
                            href="profil.php"
                            class="profile-menu-item"
                        >
                            <i class="bi bi-person-circle"></i>

                            <span>
                                Profil Saya
                            </span>

                        </a>


                        <a
                            href="logout.php"
                            class="profile-menu-item logout"
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
             CONTENT
        ========================= -->

        <div class="page-content">


            <!-- PRINT HEADER -->

            <div class="print-header">

                <img
                    src="assets/img/logo YCKA.png"
                    alt="Logo Yayasan Cahaya Kasih Amanah"
                    class="print-logo"
                >

                <h2>
                    LAPORAN MONITORING PASIEN ODGJ
                </h2>

                <p>
                    Sistem Informasi Pendataan dan Monitoring ODGJ
                </p>


                <span class="print-period">


                    <?php if (
                        !empty($tanggal_mulai)
                        &&
                        !empty($tanggal_selesai)
                    ): ?>

                        Periode:

                        <?= date(
                            'd-m-Y',
                            strtotime($tanggal_mulai)
                        ); ?>

                        s/d

                        <?= date(
                            'd-m-Y',
                            strtotime($tanggal_selesai)
                        ); ?>


                    <?php elseif (!empty($tanggal_mulai)): ?>

                        Mulai:

                        <?= date(
                            'd-m-Y',
                            strtotime($tanggal_mulai)
                        ); ?>


                    <?php elseif (!empty($tanggal_selesai)): ?>

                        Sampai:

                        <?= date(
                            'd-m-Y',
                            strtotime($tanggal_selesai)
                        ); ?>


                    <?php else: ?>

                        Periode: Semua Data

                    <?php endif; ?>


                </span>

            </div>


            <!-- HEADER -->

            <div class="page-header">


                <div class="page-title">

                    <h2>
                        Laporan Monitoring
                    </h2>

                    <p>
                        Rekap hasil monitoring pasien ODGJ
                    </p>

                </div>


                <button
                    type="button"
                    class="print-button"
                    onclick="cetakLaporan()"
                >

                    <i class="bi bi-printer"></i>

                    Cetak Laporan

                </button>


            </div>


            <!-- =========================
                 FILTER
            ========================= -->

            <div class="filter-card">


                <div class="filter-title">

                    <i class="bi bi-funnel"></i>

                    Filter Periode Laporan

                </div>


                <form
                    method="GET"
                    action="laporan.php"
                    class="filter-form"
                >


                    <div class="filter-group">

                        <label>
                            Tanggal Mulai
                        </label>

                        <input
                            type="date"
                            name="tanggal_mulai"
                            value="<?= htmlspecialchars($tanggal_mulai); ?>"
                        >

                    </div>


                    <div class="filter-group">

                        <label>
                            Tanggal Selesai
                        </label>

                        <input
                            type="date"
                            name="tanggal_selesai"
                            value="<?= htmlspecialchars($tanggal_selesai); ?>"
                        >

                    </div>


                    <button
                        type="submit"
                        class="filter-button"
                    >

                        <i class="bi bi-search"></i>

                        Tampilkan

                    </button>


                </form>


            </div>


            <!-- =========================
                 SUMMARY
            ========================= -->

            <div class="summary-grid">


                <div class="summary-card">

                    <div class="summary-icon">
                        <i class="bi bi-clipboard2-data"></i>
                    </div>


                    <div>

                        <span class="summary-label">
                            Total Monitoring
                        </span>

                        <span class="summary-value">
                            <?= $total_laporan; ?>
                        </span>

                    </div>

                </div>


                <div class="summary-card">

                    <div class="summary-icon">
                        <i class="bi bi-heart-pulse"></i>
                    </div>


                    <div>

                        <span class="summary-label">
                            Kondisi Stabil
                        </span>

                        <span class="summary-value">
                            <?= $kondisi_stabil; ?>
                        </span>

                    </div>

                </div>


                <div class="summary-card">

                    <div class="summary-icon">
                        <i class="bi bi-speedometer2"></i>
                    </div>


                    <div>

                        <span class="summary-label">
                            Rata-rata Berat Badan
                        </span>

                        <span class="summary-value">

                            <?= number_format(
                                $rata_rata_berat,
                                2,
                                ',',
                                '.'
                            ); ?>

                            kg

                        </span>

                    </div>

                </div>


            </div>


            <!-- =========================
                 TABLE
            ========================= -->

            <div class="table-card">


                <div class="table-header">

                    <div>

                        <h3>
                            Data Laporan Monitoring
                        </h3>

                        <span>
                            <?= $total_laporan; ?>
                            data ditemukan
                        </span>

                    </div>

                </div>


                <div class="table-wrapper">


                    <table>

                        <thead>

                            <tr>

                                <th>
                                    No
                                </th>

                                <th>
                                    Pasien
                                </th>

                                <th>
                                    Tanggal
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
                                    Petugas
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php if (count($data_laporan) > 0): ?>


                                <?php

                                $no = 1;

                                foreach (
                                    $data_laporan
                                    as $row
                                ):

                                ?>


                                    <?php

                                    $kondisi = strtolower(
                                        trim(
                                            $row['kondisi'] ?? ''
                                        )
                                    );


                                    if (
                                        $kondisi === 'stabil'
                                    ) {

                                        $condition_class = 'stabil';

                                    } elseif (
                                        strpos(
                                            $kondisi,
                                            'pantau'
                                        ) !== false
                                    ) {

                                        $condition_class = 'pantauan';

                                    } else {

                                        $condition_class = 'lainnya';

                                    }

                                    ?>


                                    <tr>


                                        <td>
                                            <?= $no++; ?>
                                        </td>


                                        <td>

                                            <span class="patient-name">

                                                <?= htmlspecialchars(
                                                    $row['nama_pasien']
                                                    ?? '-'
                                                ); ?>

                                            </span>

                                        </td>


                                        <td>

                                            <?= !empty(
                                                $row['tanggal_monitoring']
                                            )

                                                ? date(
                                                    'd-m-Y H:i',
                                                    strtotime(
                                                        $row['tanggal_monitoring']
                                                    )
                                                )

                                                : '-';
                                            ?>

                                        </td>


                                        <td>

                                            <?php if (
                                                $row['berat_badan'] !== null
                                                &&
                                                $row['berat_badan'] !== ''
                                            ): ?>

                                                <?= number_format(
                                                    (float) $row['berat_badan'],
                                                    2,
                                                    ',',
                                                    '.'
                                                ); ?>

                                                kg

                                            <?php else: ?>

                                                -

                                            <?php endif; ?>

                                        </td>


                                        <td>

                                            <span
                                                class="condition <?= $condition_class; ?>"
                                            >

                                                <?= htmlspecialchars(
                                                    $row['kondisi']
                                                    ?? '-'
                                                ); ?>

                                            </span>

                                        </td>


                                        <td>

                                            <?= nl2br(
                                                htmlspecialchars(
                                                    $row['aktivitas_harian']
                                                    ?? '-'
                                                )
                                            ); ?>

                                        </td>


                                        <td>

                                            <?= nl2br(
                                                htmlspecialchars(
                                                    $row['perilaku']
                                                    ?? '-'
                                                )
                                            ); ?>

                                        </td>


                                        <td>

                                            <?= nl2br(
                                                htmlspecialchars(
                                                    $row['catatan']
                                                    ?? '-'
                                                )
                                            ); ?>

                                        </td>


                                        <td>

                                            <?= htmlspecialchars(
                                                $row['nama_user']
                                                ?? '-'
                                            ); ?>

                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            <?php else: ?>


                                <tr>

                                    <td
                                        colspan="9"
                                        class="empty-data"
                                    >

                                        <i class="bi bi-file-earmark-x"></i>

                                        Tidak ada data monitoring
                                        pada periode yang dipilih.

                                    </td>

                                </tr>


                            <?php endif; ?>


                        </tbody>


                    </table>


                </div>


                <div class="print-footer">

                    Dicetak pada:

                    <?= date('d-m-Y H:i'); ?>

                </div>


            </div>


        </div>


    </main>


</div>


<script>

/* =========================
   CETAK LAPORAN
========================= */

function cetakLaporan() {

    fetch('laporan.php', {

        method: 'POST',

        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },

        body: 'aksi=cetak_laporan'

    })

    .then(function() {

        window.print();

    })

    .catch(function() {

        window.print();

    });

}


/* =========================
   DROPDOWN PROFILE
========================= */

function toggleProfile() {

    const dropdown =
        document.getElementById('profileDropdown');

    const button =
        document.getElementById('profileButton');


    if (!dropdown || !button) {
        return;
    }


    dropdown.classList.toggle('show');

    button.classList.toggle('active');

}


/* =========================
   KLIK DI LUAR PROFIL
========================= */

document.addEventListener(
    'click',
    function(event) {

        const profileWrapper =
            document.querySelector('.profile-wrapper');

        const dropdown =
            document.getElementById('profileDropdown');

        const button =
            document.getElementById('profileButton');


        if (
            profileWrapper
            &&
            !profileWrapper.contains(event.target)
        ) {

            if (dropdown) {
                dropdown.classList.remove('show');
            }

            if (button) {
                button.classList.remove('active');
            }

        }

    }
);

</script>


</body>

</html>