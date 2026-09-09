<?php
session_start();

if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'petugas') {
    die('Anda tidak memiliki akses ke halaman ini.');
}

require_once 'koneksi.php';

$nama_user = $_SESSION['nama_lengkap'] ?? 'Petugas';
$id_user   = $_SESSION['id_user'] ?? '-';


/* =====================================================
   STATISTIK PASIEN
===================================================== */

$total_pasien = 0;
$pasien_dalam = 0;
$pasien_luar = 0;
$total_monitoring = 0;


/* TOTAL PASIEN */

$q = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM pasien"
);

if ($q) {
    $row = mysqli_fetch_assoc($q);
    $total_pasien = (int) $row['total'];
}


/* PASIEN DALAM YAYASAN */

$q = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM pasien
     WHERE status_lokasi = 'Dalam Yayasan'"
);

if ($q) {
    $row = mysqli_fetch_assoc($q);
    $pasien_dalam = (int) $row['total'];
}


/* PASIEN LUAR YAYASAN */

$q = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM pasien
     WHERE status_lokasi = 'Luar Yayasan'"
);

if ($q) {
    $row = mysqli_fetch_assoc($q);
    $pasien_luar = (int) $row['total'];
}


/* TOTAL MONITORING */

$q = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM monitoring"
);

if ($q) {
    $row = mysqli_fetch_assoc($q);
    $total_monitoring = (int) $row['total'];
}


/* =====================================================
   MONITORING TERBARU
===================================================== */

$query_monitoring = mysqli_query(
    $conn,
    "SELECT
        m.id_monitoring,
        m.tanggal_monitoring,
        m.kondisi,
        p.nama_pasien,
        p.status_lokasi
     FROM monitoring m
     INNER JOIN pasien p
        ON p.id_pasien = m.id_pasien
     ORDER BY m.tanggal_monitoring DESC
     LIMIT 5"
);

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
        Dashboard Petugas - SIPM ODGJ
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


    <!-- CSS UTAMA -->

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
            min-height: 100%;
        }


        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f7fb;
        }


        /* =====================================================
           LAYOUT
        ===================================================== */

        .dashboard-layout {
            display: flex !important;
            width: 100% !important;
            min-height: 100vh !important;
        }


        /* =====================================================
           SIDEBAR PETUGAS
        ===================================================== */

        .dashboard-layout .sidebar {

            flex: 0 0 230px !important;

            width: 230px !important;
            min-width: 230px !important;
            max-width: 230px !important;

            height: 100vh !important;

            position: fixed !important;

            top: 0 !important;
            left: 0 !important;

            overflow: hidden !important;

            z-index: 1000 !important;
        }


        .dashboard-layout .sidebar-brand {

            width: 100% !important;

            text-align: center !important;

            padding: 24px 10px 20px !important;

            margin: 0 !important;
        }


        .dashboard-layout .sidebar-brand img {

            display: block !important;

            width: 58px !important;
            height: 58px !important;

            object-fit: contain !important;

            margin: 0 auto 8px !important;
        }


        .dashboard-layout .sidebar-brand h2 {

            margin: 0 !important;

            font-size: 17px !important;

            line-height: 1.3 !important;

            color: #2864e6 !important;
        }


        .dashboard-layout .sidebar-brand p {

            margin: 4px 0 0 !important;

            font-size: 9px !important;

            line-height: 1.4 !important;
        }


        /* =====================================================
           MAIN
        ===================================================== */

        .dashboard-layout .main-content {

            flex: 1 1 auto !important;

            width: calc(100% - 230px) !important;

            min-width: 0 !important;

            margin-left: 230px !important;

            padding: 0 !important;
        }


        /* =====================================================
           TOPBAR
        ===================================================== */

        .dashboard-layout .topbar {

            width: 100% !important;

            min-height: 82px !important;

            display: flex !important;

            align-items: center !important;

            justify-content: space-between !important;

            padding: 0 24px !important;

            margin: 0 !important;

            background: #fff !important;

            border-bottom: 1px solid #e5eaf2 !important;

            overflow: visible !important;

            position: relative !important;

            z-index: 1000 !important;
        }


        .dashboard-layout .topbar h1 {

            margin: 0 !important;

            color: #2864e6 !important;

            font-size: 25px !important;

            font-weight: 700 !important;

            line-height: 1.3 !important;

            white-space: nowrap !important;
        }


        /* =====================================================
           TOPBAR KANAN
        ===================================================== */

        .dashboard-layout .topbar-right {

            display: flex !important;

            align-items: center !important;

            gap: 14px !important;

            margin-left: auto !important;

            position: relative !important;

            z-index: 1001 !important;
        }


        /* =====================================================
           SEARCH
        ===================================================== */

        .dashboard-layout .search-box {

            display: flex !important;

            align-items: center !important;

            gap: 8px !important;

            width: 165px !important;

            height: 38px !important;

            padding: 0 13px !important;

            border: 1px solid #dce3ee !important;

            border-radius: 20px !important;

            background: #fff !important;
        }


        .dashboard-layout .search-box i {

            color: #8c98aa !important;

            font-size: 14px !important;
        }


        .dashboard-layout .search-box input {

            width: 100% !important;

            border: none !important;

            outline: none !important;

            background: transparent !important;

            font-family: 'Poppins', sans-serif !important;

            font-size: 10px !important;

            color: #333 !important;
        }


        .dashboard-layout .search-box input::placeholder {

            color: #9aa4b5 !important;
        }


        /* =====================================================
           NOTIFICATION
        ===================================================== */

        .notification-wrapper {

            position: relative;

            display: flex;

            align-items: center;

            z-index: 5000;
        }


        .notification-button {

            position: relative;

            width: 42px;
            height: 42px;

            display: flex;

            align-items: center;
            justify-content: center;

            border: none;

            background: #f7f9fc;

            border-radius: 11px;

            color: #172033;

            cursor: pointer;

            transition: .2s ease;
        }


        .notification-button:hover {

            background: #eef4ff;

            color: #2864e6;
        }


        .notification-button i {

            font-size: 21px;
        }


        /* =====================================================
           TITIK MERAH
        ===================================================== */

        .notification-dot {

            position: absolute;

            top: 7px;
            right: 7px;

            width: 8px;
            height: 8px;

            background: #e63946;

            border-radius: 50%;

            border: 2px solid #fff;

            display: block;
        }


        .notification-dot.hidden {

            display: none !important;
        }


        /* =====================================================
           DROPDOWN NOTIFIKASI
        ===================================================== */

        .notification-dropdown {

            position: absolute;

            top: calc(100% + 10px);

            right: 0;

            width: 390px;

            background: #fff;

            border: 1px solid #e2e8f0;

            border-radius: 13px;

            box-shadow:
                0 14px 35px
                rgba(23, 32, 51, .14);

            overflow: hidden;

            opacity: 0;

            visibility: hidden;

            transform: translateY(-7px);

            transition:
                opacity .2s ease,
                transform .2s ease,
                visibility .2s ease;

            z-index: 9999;
        }


        .notification-dropdown.show {

            opacity: 1;

            visibility: visible;

            transform: translateY(0);
        }


        /* HEADER */

        .notification-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 17px 20px;

            border-bottom: 1px solid #edf0f5;
        }


        .notification-header strong {

            font-size: 15px;

            color: #172033;

            font-weight: 600;
        }


        .notification-header span {

            color: #2864e6;

            font-size: 10px;

            font-weight: 500;
        }


        /* LIST */

        .notification-list {

            max-height: 330px;

            overflow-y: auto;
        }


        .notification-item {

            display: flex;

            align-items: flex-start;

            gap: 12px;

            padding: 15px 20px;

            border-bottom: 1px solid #edf0f5;

            transition: .2s ease;
        }


        .notification-item:hover {

            background: #f8faff;
        }


        .notification-icon {

            width: 38px;
            height: 38px;

            min-width: 38px;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 10px;

            background: #eef4ff;

            color: #2864e6;

            font-size: 17px;
        }


        .notification-content {

            flex: 1;

            min-width: 0;
        }


        .notification-content strong {

            display: block;

            margin-bottom: 4px;

            color: #172033;

            font-size: 11px;

            font-weight: 600;
        }


        .notification-content p {

            margin: 0 0 5px;

            color: #718096;

            font-size: 10px;

            line-height: 1.45;
        }


        .notification-content small {

            color: #9aa5b7;

            font-size: 9px;
        }


        .notification-footer {

            padding: 12px 18px;

            text-align: center;

            border-top: 1px solid #edf0f5;
        }


        .notification-footer button {

            border: none;

            background: transparent;

            color: #2864e6;

            font-family: 'Poppins', sans-serif;

            font-size: 10px;

            font-weight: 600;

            cursor: pointer;

            padding: 3px 5px;
        }


        .notification-footer button:hover {

            text-decoration: underline;
        }


        /* =====================================================
           PROFILE
        ===================================================== */

        .dashboard-layout .user-profile {

            position: relative !important;

            display: flex !important;

            align-items: center !important;

            z-index: 1002 !important;
        }


        .profile-button {

            position: relative !important;

            display: flex !important;

            align-items: center !important;

            justify-content: flex-end !important;

            gap: 10px !important;

            padding: 5px 8px !important;

            border: none !important;

            background: transparent !important;

            color: #17243a !important;

            font-family: 'Poppins', sans-serif !important;

            cursor: pointer !important;

            border-radius: 12px !important;

            transition: background .2s ease !important;

            z-index: 1003 !important;
        }


        .profile-button:hover,
        .profile-button.active {

            background: #f4f7fc !important;
        }


        .profile-top-text {

            display: flex !important;

            flex-direction: column !important;

            align-items: flex-end !important;

            justify-content: center !important;

            line-height: 1.15 !important;

            min-width: 80px !important;
        }


        .profile-top-text strong {

            margin: 0 !important;

            color: #17243a !important;

            font-size: 14px !important;

            font-weight: 600 !important;

            white-space: nowrap !important;
        }


        .profile-top-text small {

            margin-top: 3px !important;

            color: #8a95a8 !important;

            font-size: 10px !important;

            font-weight: 400 !important;

            text-transform: lowercase !important;
        }


        .profile-top-avatar {

            width: 48px !important;
            height: 48px !important;

            min-width: 48px !important;

            display: flex !important;

            align-items: center !important;
            justify-content: center !important;

            border-radius: 50% !important;

            background: #2864e6 !important;

            color: #fff !important;

            font-size: 18px !important;

            font-weight: 600 !important;

            line-height: 1 !important;
        }


        .profile-arrow {

            font-size: 11px !important;

            color: #7a8499 !important;

            transition: transform .2s ease !important;

            margin-left: 1px !important;
        }


        .profile-button.active .profile-arrow {

            transform: rotate(180deg) !important;
        }


        /* =====================================================
           PROFILE DROPDOWN
        ===================================================== */

        .profile-dropdown {

            position: absolute !important;

            top: calc(100% + 10px) !important;

            right: 0 !important;

            width: 230px !important;

            background: #fff !important;

            border: 1px solid #e5eaf2 !important;

            border-radius: 12px !important;

            box-shadow:
                0 8px 25px
                rgba(0, 0, 0, .10) !important;

            overflow: hidden !important;

            opacity: 0 !important;

            visibility: hidden !important;

            transform: translateY(-6px) !important;

            transition:
                opacity .2s ease,
                transform .2s ease,
                visibility .2s ease !important;

            z-index: 9999 !important;
        }


        .profile-dropdown.show {

            opacity: 1 !important;

            visibility: visible !important;

            transform: translateY(0) !important;
        }


        .profile-dropdown-header {

            display: flex !important;

            align-items: center !important;

            gap: 11px !important;

            padding: 16px !important;
        }


        .profile-avatar {

            width: 40px !important;
            height: 40px !important;

            min-width: 40px !important;

            display: flex !important;

            align-items: center !important;
            justify-content: center !important;

            border-radius: 50% !important;

            background: #eef4ff !important;

            color: #2864e6 !important;

            font-size: 18px !important;
        }


        .profile-info {

            display: flex !important;

            flex-direction: column !important;

            min-width: 0 !important;
        }


        .profile-info strong {

            color: #17243a !important;

            font-size: 12px !important;

            font-weight: 600 !important;

            white-space: nowrap !important;

            overflow: hidden !important;

            text-overflow: ellipsis !important;
        }


        .profile-info span {

            margin-top: 2px !important;

            color: #8a95a8 !important;

            font-size: 10px !important;
        }


        .profile-divider {

            height: 1px !important;

            background: #edf0f5 !important;
        }


        .profile-menu-item {

            display: flex !important;

            align-items: center !important;

            gap: 10px !important;

            width: 100% !important;

            padding: 12px 16px !important;

            color: #374151 !important;

            text-decoration: none !important;

            font-size: 11px !important;

            border: none !important;

            background: transparent !important;

            font-family: 'Poppins', sans-serif !important;

            text-align: left !important;

            cursor: pointer !important;

            transition:
                background .2s ease,
                color .2s ease !important;
        }


        .profile-menu-item:hover {

            background: #f8fafc !important;

            color: #2864e6 !important;
        }


        .profile-menu-item i {

            width: 18px !important;

            text-align: center !important;

            font-size: 15px !important;
        }


        .profile-menu-item.logout-item {

            color: #dc3545 !important;
        }


        .profile-menu-item.logout-item:hover {

            background: #fff5f5 !important;

            color: #dc3545 !important;
        }


        /* =====================================================
           PROFILE MODAL
        ===================================================== */

        .profile-modal {

            position: fixed !important;

            inset: 0 !important;

            background:
                rgba(15, 23, 42, .35) !important;

            display: flex !important;

            align-items: center !important;

            justify-content: center !important;

            padding: 20px !important;

            opacity: 0 !important;

            visibility: hidden !important;

            transition:
                opacity .2s ease,
                visibility .2s ease !important;

            z-index: 20000 !important;
        }


        .profile-modal.show {

            opacity: 1 !important;

            visibility: visible !important;
        }


        .profile-modal-card {

            width: 100% !important;

            max-width: 390px !important;

            background: #fff !important;

            border-radius: 14px !important;

            box-shadow:
                0 15px 40px
                rgba(0, 0, 0, .15) !important;

            overflow: hidden !important;
        }


        .profile-modal-top {

            display: flex !important;

            align-items: center !important;

            justify-content: space-between !important;

            padding: 18px 20px !important;

            border-bottom:
                1px solid #edf0f5 !important;
        }


        .profile-modal-top h3 {

            margin: 0 !important;

            font-size: 15px !important;

            font-weight: 600 !important;

            color: #17243a !important;
        }


        .profile-close {

            width: 30px !important;
            height: 30px !important;

            display: flex !important;

            align-items: center !important;
            justify-content: center !important;

            border: none !important;

            background: #f5f7fb !important;

            color: #69768c !important;

            border-radius: 7px !important;

            cursor: pointer !important;

            font-size: 16px !important;
        }


        .profile-close:hover {

            background: #edf1f7 !important;

            color: #17243a !important;
        }


        .profile-modal-body {

            padding: 24px 20px !important;
        }


        .profile-modal-avatar {

            width: 68px !important;
            height: 68px !important;

            margin: 0 auto 15px !important;

            display: flex !important;

            align-items: center !important;
            justify-content: center !important;

            border-radius: 50% !important;

            background: #eef4ff !important;

            color: #2864e6 !important;

            font-size: 30px !important;
        }


        .profile-modal-name {

            text-align: center !important;

            font-size: 17px !important;

            font-weight: 600 !important;

            color: #17243a !important;

            margin-bottom: 4px !important;
        }


        .profile-modal-role {

            text-align: center !important;

            font-size: 11px !important;

            color: #8a95a8 !important;

            margin-bottom: 22px !important;
        }


        .profile-detail {

            border:
                1px solid #e8edf4 !important;

            border-radius: 9px !important;

            overflow: hidden !important;
        }


        .profile-detail-row {

            display: flex !important;

            align-items: center !important;

            justify-content: space-between !important;

            gap: 20px !important;

            padding: 12px 14px !important;

            border-bottom:
                1px solid #edf0f5 !important;
        }


        .profile-detail-row:last-child {

            border-bottom: none !important;
        }


        .profile-detail-label {

            font-size: 10px !important;

            color: #8a95a8 !important;
        }


        .profile-detail-value {

            font-size: 11px !important;

            color: #17243a !important;

            font-weight: 500 !important;

            text-align: right !important;
        }


        /* =====================================================
           PAGE CONTENT
        ===================================================== */

        .dashboard-layout .page-content {

            width: 100% !important;

            padding: 30px 24px !important;

            margin: 0 !important;
        }


        /* =====================================================
           WELCOME CARD
        ===================================================== */

        .welcome-card,
        .content-card,
        .stat-card {

            background: #fff;

            border-radius: 14px;

            box-shadow:
                0 2px 10px
                rgba(0, 0, 0, .05);
        }


        .welcome-card {

            padding: 25px;

            margin-bottom: 20px;
        }


        .welcome-card h2 {

            margin: 0;

            color: #17243a;

            font-size: 20px;

            font-weight: 700;
        }


        /* =====================================================
           STATISTIK
        ===================================================== */

        .stat-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    4,
                    minmax(0, 1fr)
                );

            gap: 18px;

            margin-bottom: 20px;
        }


        .stat-card {

            min-height: 102px;

            padding: 22px;

            display: flex !important;

            flex-direction: row !important;

            align-items: center !important;

            justify-content: flex-start !important;

            gap: 14px !important;
        }


        /* =====================================================
           STAT CARD BISA DIKLIK
        ===================================================== */

        .stat-card-link {
            text-decoration: none !important;
            color: inherit !important;
            cursor: pointer !important;
            transition:
                transform .2s ease,
                box-shadow .2s ease !important;
        }


        .stat-card-link:hover {
            text-decoration: none !important;
            color: inherit !important;
            transform: translateY(-2px);
            box-shadow:
                0 5px 16px
                rgba(40, 100, 230, .12) !important;
        }


        .stat-card-link:focus {
            outline: 2px solid #2864e6;
            outline-offset: 2px;
        }


        .stat-icon {

            width: 45px;
            height: 45px;

            min-width: 45px;

            border-radius: 10px;

            display: flex;

            align-items: center;
            justify-content: center;

            color: #315da8;

            background: #eef4ff;

            font-size: 22px;
        }


        .stat-card > div:not(.stat-icon) {

            flex: 0 1 auto !important;

            width: auto !important;

            min-width: 0 !important;

            margin: 0 !important;

            padding: 0 !important;

            text-align: left !important;
        }


        .stat-number {

            margin: 0 0 6px !important;

            color: #111827;

            font-size: 25px;

            font-weight: 700;

            line-height: 1;
        }


        .stat-label {

            margin: 0 !important;

            color: #777;

            font-size: 13px;

            white-space: nowrap !important;
        }


        /* =====================================================
           MONITORING TERBARU
        ===================================================== */

        .content-card {

            padding: 25px;

            overflow: hidden;
        }


        .card-title {

            display: flex;

            align-items: center;

            gap: 7px;

            margin: 0 0 18px;

            color: #17243a;

            font-size: 16px;

            font-weight: 600;
        }


        .card-title i {

            color: #315da8;

            font-size: 17px;
        }


        .table-responsive {

            width: 100%;

            overflow-x: auto;
        }


        .data-table {

            width: 100%;

            border-collapse: collapse;

            min-width: 650px;
        }


        .data-table th,
        .data-table td {

            padding: 14px;

            text-align: left;

            border-bottom:
                1px solid #eee;

            font-size: 13px;
        }


        .data-table th {

            color: #666;

            font-weight: 500;

            background: #f8fafc;
        }


        .data-table td {

            color: #374151;
        }


        .data-table tbody tr:last-child td {

            border-bottom: none;
        }


        /* =====================================================
           BADGE
        ===================================================== */

        .badge {

            display: inline-block;

            padding: 6px 11px;

            border-radius: 20px;

            font-size: 12px;

            white-space: nowrap;
        }


        .data-table .badge-dalam {

            background: #e8fbff !important;

            color: #16a9d5 !important;

            border:
                1px solid #24c5e8 !important;
        }


        .data-table .badge-luar {

            background: #fff0f7 !important;

            color: #e83e8c !important;

            border:
                1px solid #ff8bc4 !important;
        }


        .data-table .badge-stabil {

            background: #e7f5ed !important;

            color: #198754 !important;
        }


        .data-table .badge-pantau {

            background: #fff2cc !important;

            color: #9a6700 !important;
        }


        .empty-state {

            text-align: center !important;

            color: #777 !important;

            padding: 30px !important;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 1100px) {

            .stat-grid {

                grid-template-columns:
                    repeat(
                        2,
                        minmax(0, 1fr)
                    );
            }

        }


        @media (max-width: 800px) {

            .dashboard-layout .sidebar {

                flex-basis: 210px !important;

                width: 210px !important;

                min-width: 210px !important;

                max-width: 210px !important;
            }


            .dashboard-layout .main-content {

                width:
                    calc(100% - 210px)
                    !important;

                margin-left:
                    210px !important;
            }


            .dashboard-layout .topbar {

                padding:
                    0 18px !important;
            }


            .dashboard-layout .topbar h1 {

                font-size:
                    21px !important;
            }


            .dashboard-layout .search-box {

                width:
                    130px !important;
            }


            .dashboard-layout .page-content {

                padding:
                    20px 18px !important;
            }


            .notification-dropdown {

                width: 350px;
            }

        }


        @media (max-width: 650px) {

            .stat-grid {

                grid-template-columns:
                    1fr;
            }


            .dashboard-layout .topbar-right {

                gap: 7px !important;
            }


            .dashboard-layout .search-box {

                display:
                    none !important;
            }


            .profile-top-text {

                display: none !important;
            }


            .profile-top-avatar {

                width: 40px !important;
                height: 40px !important;

                min-width: 40px !important;

                font-size: 15px !important;
            }


            .notification-dropdown {

                position: fixed;

                top: 75px;

                right: 12px;

                left: 12px;

                width: auto;
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


            <!-- DASHBOARD -->

            <a
                href="dashboard_petugas.php"
                class="menu-item active"
            >

                <i class="bi bi-grid-1x2"></i>

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
                class="menu-item"
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
                Dashboard
            </h1>


            <div class="topbar-right">


                <!-- =================================================
                     SEARCH
                ================================================= -->

                <div class="search-box">


                    <i class="bi bi-search"></i>


                    <input
                        type="text"
                        placeholder="Search..."
                        id="searchInput"
                        autocomplete="off"
                    >


                </div>


                <!-- =================================================
                     NOTIFICATION
                ================================================= -->

                <div
                    class="notification-wrapper"
                    id="notificationWrapper"
                >


                    <button
                        type="button"
                        class="notification-button"
                        id="notificationButton"
                        aria-label="Notifikasi"
                        aria-expanded="false"
                    >

                        <i class="bi bi-bell"></i>


                        <!-- TITIK MERAH -->

                        <span
                            class="notification-dot"
                            id="notificationDot"
                        ></span>


                    </button>


                    <!-- DROPDOWN NOTIFIKASI -->

                    <div
                        class="notification-dropdown"
                        id="notificationDropdown"
                    >


                        <!-- HEADER -->

                        <div class="notification-header">


                            <strong>
                                Notifikasi
                            </strong>


                            <span>
                                Pemberitahuan terbaru
                            </span>


                        </div>


                        <!-- LIST -->

                        <div class="notification-list">


                            <!-- NOTIFIKASI 1 -->

                            <div class="notification-item">


                                <div class="notification-icon">

                                    <i class="bi bi-clipboard2-pulse"></i>

                                </div>


                                <div class="notification-content">


                                    <strong>
                                        Monitoring baru
                                    </strong>


                                    <p>
                                        Ada data monitoring pasien terbaru yang perlu diperiksa.
                                    </p>


                                    <small>
                                        Baru saja
                                    </small>


                                </div>


                            </div>


                            <!-- NOTIFIKASI 2 -->

                            <div class="notification-item">


                                <div class="notification-icon">

                                    <i class="bi bi-person-check"></i>

                                </div>


                                <div class="notification-content">


                                    <strong>
                                        Data pasien diperbarui
                                    </strong>


                                    <p>
                                        Terdapat pembaruan data pasien di sistem.
                                    </p>


                                    <small>
                                        2 jam lalu
                                    </small>


                                </div>


                            </div>


                        </div>


                        <!-- FOOTER -->

                        <div class="notification-footer">


                            <button
                                type="button"
                                id="markNotificationsRead"
                            >
                                Tandai semua sudah dibaca
                            </button>


                        </div>


                    </div>


                </div>


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


                        <span class="profile-top-text">


                            <strong>
                                <?= htmlspecialchars($nama_user); ?>
                            </strong>


                            <small>
                                petugas
                            </small>


                        </span>


                        <span class="profile-top-avatar">


                            <?= htmlspecialchars(
                                strtoupper(
                                    substr(
                                        trim($nama_user),
                                        0,
                                        1
                                    )
                                )
                            ); ?>


                        </span>


                        <i
                            class="bi bi-chevron-down profile-arrow"
                        ></i>


                    </button>


                    <!-- DROPDOWN PROFILE -->

                    <div
                        class="profile-dropdown"
                        id="profileDropdown"
                    >


                        <div
                            class="profile-dropdown-header"
                        >


                            <div class="profile-avatar">


                                <i class="bi bi-person-fill"></i>


                            </div>


                            <div class="profile-info">


                                <strong>

                                    <?= htmlspecialchars(
                                        $nama_user
                                    ); ?>

                                </strong>


                                <span>
                                    Petugas
                                </span>


                            </div>


                        </div>


                        <div
                            class="profile-divider"
                        ></div>


                        <button
                            type="button"
                            class="profile-menu-item"
                            id="profileSaya"
                        >


                            <i class="bi bi-person"></i>


                            <span>
                                Profil Saya
                            </span>


                        </button>


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
        ================================================= -->

        <div class="page-content">


            <!-- =================================================
                 WELCOME
            ================================================= -->

            <div class="welcome-card">


                <h2>


                    Selamat Datang,

                    <?= htmlspecialchars(
                        $nama_user
                    ); ?>


                    👋


                </h2>


            </div>


            <!-- =================================================
                 STATISTIK
            ================================================= -->

            <div class="stat-grid">


                <!-- TOTAL PASIEN -->

                <a href="data_pasien_petugas.php" class="stat-card stat-card-link">


                    <div class="stat-icon">

                        <i class="bi bi-people"></i>

                    </div>


                    <div>


                        <div class="stat-number">

                            <?= $total_pasien; ?>

                        </div>


                        <div class="stat-label">

                            Total Pasien

                        </div>


                    </div>


                </a>


                <!-- PASIEN DALAM -->

                <a href="data_pasien_petugas.php" class="stat-card stat-card-link">


                    <div class="stat-icon">

                        <i class="bi bi-house-heart"></i>

                    </div>


                    <div>


                        <div class="stat-number">

                            <?= $pasien_dalam; ?>

                        </div>


                        <div class="stat-label">

                            Pasien Dalam Yayasan

                        </div>


                    </div>


                </a>


                <!-- PASIEN LUAR -->

                <a href="data_pasien_petugas.php" class="stat-card stat-card-link">


                    <div class="stat-icon">

                        <i class="bi bi-house"></i>

                    </div>


                    <div>


                        <div class="stat-number">

                            <?= $pasien_luar; ?>

                        </div>


                        <div class="stat-label">

                            Pasien Luar Yayasan

                        </div>


                    </div>


                </a>


                <!-- TOTAL MONITORING -->

                <a href="monitoring_petugas.php" class="stat-card stat-card-link">


                    <div class="stat-icon">

                        <i class="bi bi-clipboard2-pulse"></i>

                    </div>


                    <div>


                        <div class="stat-number">

                            <?= $total_monitoring; ?>

                        </div>


                        <div class="stat-label">

                            Total Monitoring

                        </div>


                    </div>


                </a>


            </div>


            <!-- =================================================
                 MONITORING TERBARU
            ================================================= -->

            <div class="content-card">


                <h3 class="card-title">


                    <i class="bi bi-clock-history"></i>


                    Monitoring Terbaru


                </h3>


                <div class="table-responsive">


                    <table class="data-table">


                        <thead>


                            <tr>


                                <th>
                                    Nama Pasien
                                </th>


                                <th>
                                    Tanggal Monitoring
                                </th>


                                <th>
                                    Kondisi
                                </th>


                                <th>
                                    Lokasi
                                </th>


                            </tr>


                        </thead>


                        <tbody>


                        <?php if (
                            $query_monitoring &&
                            mysqli_num_rows(
                                $query_monitoring
                            ) > 0
                        ): ?>


                            <?php while (
                                $monitor =
                                mysqli_fetch_assoc(
                                    $query_monitoring
                                )
                            ): ?>


                                <tr>


                                    <!-- NAMA PASIEN -->

                                    <td>


                                        <strong>

                                            <?= htmlspecialchars(
                                                $monitor[
                                                    'nama_pasien'
                                                ]
                                            ); ?>

                                        </strong>


                                    </td>


                                    <!-- TANGGAL -->

                                    <td>


                                        <?= htmlspecialchars(
                                            date(
                                                'd-m-Y H:i',
                                                strtotime(
                                                    $monitor[
                                                        'tanggal_monitoring'
                                                    ]
                                                )
                                            )
                                        ); ?>


                                    </td>


                                    <!-- KONDISI -->

                                    <td>


                                        <?php

                                        $kondisi =
                                            strtolower(
                                                $monitor[
                                                    'kondisi'
                                                ] ?? ''
                                            );


                                        $badge_kondisi =
                                            strpos(
                                                $kondisi,
                                                'stabil'
                                            ) !== false

                                                ? 'badge-stabil'

                                                : 'badge-pantau';

                                        ?>


                                        <span
                                            class="badge <?= $badge_kondisi; ?>"
                                        >


                                            <?= htmlspecialchars(
                                                $monitor[
                                                    'kondisi'
                                                ]
                                            ); ?>


                                        </span>


                                    </td>


                                    <!-- LOKASI -->

                                    <td>


                                        <?php

                                        $badge_lokasi =
                                            $monitor[
                                                'status_lokasi'
                                            ] ===
                                            'Dalam Yayasan'

                                                ? 'badge-dalam'

                                                : 'badge-luar';

                                        ?>


                                        <span
                                            class="badge <?= $badge_lokasi; ?>"
                                        >


                                            <?= htmlspecialchars(
                                                $monitor[
                                                    'status_lokasi'
                                                ]
                                            ); ?>


                                        </span>


                                    </td>


                                </tr>


                            <?php endwhile; ?>


                        <?php else: ?>


                            <tr>


                                <td
                                    colspan="4"
                                    class="empty-state"
                                >

                                    Belum ada data monitoring.

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


<!-- =========================================================
     MODAL PROFIL PETUGAS
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


            <div class="profile-modal-avatar">

                <i class="bi bi-person-fill"></i>

            </div>


            <div class="profile-modal-name">

                <?= htmlspecialchars(
                    $nama_user
                ); ?>

            </div>


            <div class="profile-modal-role">

                Petugas

            </div>


            <div class="profile-detail">


                <div class="profile-detail-row">


                    <span class="profile-detail-label">

                        Nama Lengkap

                    </span>


                    <span class="profile-detail-value">

                        <?= htmlspecialchars(
                            $nama_user
                        ); ?>

                    </span>


                </div>


                <div class="profile-detail-row">


                    <span class="profile-detail-label">

                        ID Pengguna

                    </span>


                    <span class="profile-detail-value">

                        <?= htmlspecialchars(
                            (string) $id_user
                        ); ?>

                    </span>


                </div>


                <div class="profile-detail-row">


                    <span class="profile-detail-label">

                        Role

                    </span>


                    <span class="profile-detail-value">

                        Petugas

                    </span>


                </div>


            </div>


        </div>


    </div>


</div>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {


        /* =================================================
           ELEMENT NOTIFIKASI
        ================================================= */

        const notificationButton =
            document.getElementById(
                "notificationButton"
            );


        const notificationDropdown =
            document.getElementById(
                "notificationDropdown"
            );


        const notificationWrapper =
            document.getElementById(
                "notificationWrapper"
            );


        const notificationDot =
            document.getElementById(
                "notificationDot"
            );


        const markNotificationsRead =
            document.getElementById(
                "markNotificationsRead"
            );


        /*
         * KEY STATUS NOTIFIKASI
         *
         * Kalau user sudah menandai semua dibaca,
         * titik merah akan tetap hilang setelah refresh.
         */

        const notificationReadKey =
            "sipm_petugas_notifikasi_dibaca_v1";


        /* CEK STATUS AWAL */

        if (
            localStorage.getItem(
                notificationReadKey
            ) === "1"
        ) {

            if (notificationDot) {

                notificationDot.classList.add(
                    "hidden"
                );

            }

        }


        /* =================================================
           BUKA / TUTUP NOTIFIKASI
        ================================================= */

        if (
            notificationButton &&
            notificationDropdown
        ) {


            notificationButton.addEventListener(
                "click",
                function (event) {


                    event.preventDefault();

                    event.stopPropagation();


                    const isOpen =
                        notificationDropdown
                            .classList
                            .contains("show");


                    if (isOpen) {


                        notificationDropdown
                            .classList
                            .remove("show");


                        notificationButton
                            .setAttribute(
                                "aria-expanded",
                                "false"
                            );


                    } else {


                        notificationDropdown
                            .classList
                            .add("show");


                        notificationButton
                            .setAttribute(
                                "aria-expanded",
                                "true"
                            );

                    }

                }
            );

        }


        /* =================================================
           TANDAI SEMUA SUDAH DIBACA
        ================================================= */

        if (markNotificationsRead) {


            markNotificationsRead.addEventListener(
                "click",
                function (event) {


                    event.preventDefault();

                    event.stopPropagation();


                    /*
                     * HILANGKAN TITIK MERAH
                     */

                    if (notificationDot) {

                        notificationDot.classList.add(
                            "hidden"
                        );

                    }


                    /*
                     * SIMPAN STATUS DIBACA
                     */

                    localStorage.setItem(
                        notificationReadKey,
                        "1"
                    );


                    /*
                     * TUTUP POPUP
                     */

                    if (notificationDropdown) {

                        notificationDropdown
                            .classList
                            .remove("show");

                    }


                    if (notificationButton) {

                        notificationButton
                            .setAttribute(
                                "aria-expanded",
                                "false"
                            );

                    }

                }
            );

        }


        /* =================================================
           KLIK DI LUAR NOTIFIKASI
        ================================================= */

        document.addEventListener(
            "click",
            function (event) {


                if (
                    notificationWrapper &&
                    !notificationWrapper.contains(
                        event.target
                    )
                ) {


                    if (notificationDropdown) {

                        notificationDropdown
                            .classList
                            .remove("show");

                    }


                    if (notificationButton) {

                        notificationButton
                            .setAttribute(
                                "aria-expanded",
                                "false"
                            );

                    }

                }

            }
        );


        /* =================================================
           ELEMENT PROFILE
        ================================================= */

        const profileButton =
            document.getElementById(
                "profileButton"
            );


        const profileDropdown =
            document.getElementById(
                "profileDropdown"
            );


        const profileSaya =
            document.getElementById(
                "profileSaya"
            );


        const profileModal =
            document.getElementById(
                "profileModal"
            );


        const closeProfile =
            document.getElementById(
                "closeProfile"
            );


        const userProfile =
            document.getElementById(
                "userProfile"
            );


        /* =================================================
           PROFILE DROPDOWN
        ================================================= */

        if (
            profileButton &&
            profileDropdown
        ) {


            profileButton.addEventListener(
                "click",
                function (event) {


                    event.preventDefault();

                    event.stopPropagation();


                    const isOpen =
                        profileDropdown
                            .classList
                            .contains("show");


                    if (isOpen) {


                        profileDropdown
                            .classList
                            .remove("show");


                        profileButton
                            .classList
                            .remove("active");


                        profileButton
                            .setAttribute(
                                "aria-expanded",
                                "false"
                            );


                    } else {


                        profileDropdown
                            .classList
                            .add("show");


                        profileButton
                            .classList
                            .add("active");


                        profileButton
                            .setAttribute(
                                "aria-expanded",
                                "true"
                            );

                    }

                }
            );

        }


        /* =================================================
           KLIK DI LUAR PROFILE
        ================================================= */

        document.addEventListener(
            "click",
            function (event) {


                if (
                    userProfile &&
                    !userProfile.contains(
                        event.target
                    )
                ) {


                    if (profileDropdown) {

                        profileDropdown
                            .classList
                            .remove("show");

                    }


                    if (profileButton) {

                        profileButton
                            .classList
                            .remove("active");


                        profileButton
                            .setAttribute(
                                "aria-expanded",
                                "false"
                            );

                    }

                }

            }
        );


        /* =================================================
           PROFIL SAYA
        ================================================= */

        if (
            profileSaya &&
            profileModal
        ) {


            profileSaya.addEventListener(
                "click",
                function (event) {


                    event.preventDefault();

                    event.stopPropagation();


                    if (profileDropdown) {

                        profileDropdown
                            .classList
                            .remove("show");

                    }


                    if (profileButton) {

                        profileButton
                            .classList
                            .remove("active");


                        profileButton
                            .setAttribute(
                                "aria-expanded",
                                "false"
                            );

                    }


                    profileModal
                        .classList
                        .add("show");

                }
            );

        }


        /* =================================================
           TUTUP MODAL
        ================================================= */

        if (
            closeProfile &&
            profileModal
        ) {


            closeProfile.addEventListener(
                "click",
                function () {

                    profileModal
                        .classList
                        .remove("show");

                }
            );

        }


        /* =================================================
           KLIK AREA GELAP MODAL
        ================================================= */

        if (profileModal) {


            profileModal.addEventListener(
                "click",
                function (event) {


                    if (
                        event.target ===
                        profileModal
                    ) {


                        profileModal
                            .classList
                            .remove("show");

                    }

                }
            );

        }


        /* =================================================
           ESC
        ================================================= */

        document.addEventListener(
            "keydown",
            function (event) {


                if (
                    event.key === "Escape"
                ) {


                    if (notificationDropdown) {

                        notificationDropdown
                            .classList
                            .remove("show");

                    }


                    if (notificationButton) {

                        notificationButton
                            .setAttribute(
                                "aria-expanded",
                                "false"
                            );

                    }


                    if (profileModal) {

                        profileModal
                            .classList
                            .remove("show");

                    }


                    if (profileDropdown) {

                        profileDropdown
                            .classList
                            .remove("show");

                    }


                    if (profileButton) {

                        profileButton
                            .classList
                            .remove("active");


                        profileButton
                            .setAttribute(
                                "aria-expanded",
                                "false"
                            );

                    }

                }

            }
        );


        /* =================================================
           SEARCH MONITORING
        ================================================= */

        const searchInput =
            document.getElementById(
                "searchInput"
            );


        if (searchInput) {


            searchInput.addEventListener(
                "keyup",
                function () {


                    const keyword =
                        this.value
                            .toLowerCase()
                            .trim();


                    const rows =
                        document.querySelectorAll(
                            ".data-table tbody tr"
                        );


                    rows.forEach(
                        function (row) {


                            row.style.display =
                                row.innerText
                                    .toLowerCase()
                                    .includes(
                                        keyword
                                    )
                                    ? ""
                                    : "none";

                        }
                    );

                }
            );

        }


    }
);

</script>


</body>

</html>