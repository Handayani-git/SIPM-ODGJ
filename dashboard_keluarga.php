<?php

session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

if (($_SESSION['role'] ?? '') !== 'keluarga') {
    die("Anda tidak memiliki akses ke halaman ini.");
}

require_once "koneksi.php";

$id_user = (int) $_SESSION['id_user'];
$nama_user = $_SESSION['nama_lengkap'] ?? 'Keluarga';

function e($value)
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function tanggal_indo($tanggal)
{
    if (!$tanggal || $tanggal === '0000-00-00') {
        return '-';
    }

    $bulan = [
        1 => 'Jan',
        2 => 'Feb',
        3 => 'Mar',
        4 => 'Apr',
        5 => 'Mei',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Agu',
        9 => 'Sep',
        10 => 'Okt',
        11 => 'Nov',
        12 => 'Des'
    ];

    $time = strtotime($tanggal);

    if (!$time) {
        return e($tanggal);
    }

    return date('d', $time) . ' ' .
        $bulan[(int)date('n', $time)] . ' ' .
        date('Y', $time);
}

function tanggal_waktu($tanggal)
{
    if (!$tanggal) {
        return '-';
    }

    $time = strtotime($tanggal);

    if (!$time) {
        return e($tanggal);
    }

    return tanggal_indo($tanggal) . ', ' . date('H:i', $time);
}


/* =========================================================
   PASIEN YANG TERHUBUNG DENGAN AKUN KELUARGA
   ========================================================= */

$pasien = null;

$stmt = mysqli_prepare($conn, "
    SELECT
        p.id_pasien,
        p.nomor_registrasi,
        p.nama_pasien,
        p.nik,
        p.jenis_kelamin,
        p.tempat_lahir,
        p.tanggal_lahir,
        p.alamat,
        p.status_lokasi,
        p.kondisi,
        p.tanggal_masuk,
        p.status_pasien,
        k.nama_keluarga,
        k.hubungan,
        k.nomor_telepon
    FROM keluarga k
    INNER JOIN pasien p ON p.id_pasien = k.id_pasien
    WHERE k.id_user = ?
    ORDER BY k.id_keluarga DESC
    LIMIT 1
");

mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $pasien = mysqli_fetch_assoc($result);
}

mysqli_stmt_close($stmt);


/* =========================================================
   MONITORING PASIEN TERHUBUNG
   ========================================================= */

$monitoring = [];
$total_monitoring = 0;

if ($pasien) {

    $id_pasien = (int)$pasien['id_pasien'];

    $stmt = mysqli_prepare($conn, "
        SELECT
            id_monitoring,
            tanggal_monitoring,
            berat_badan,
            aktivitas_harian,
            perilaku,
            catatan
        FROM monitoring
        WHERE id_pasien = ?
        ORDER BY tanggal_monitoring DESC
        LIMIT 5
    ");

    mysqli_stmt_bind_param($stmt, "i", $id_pasien);
    mysqli_stmt_execute($stmt);

    $result_m = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result_m)) {
        $monitoring[] = $row;
    }

    mysqli_stmt_close($stmt);


    $stmt = mysqli_prepare($conn, "
        SELECT COUNT(*) AS total
        FROM monitoring
        WHERE id_pasien = ?
    ");

    mysqli_stmt_bind_param($stmt, "i", $id_pasien);
    mysqli_stmt_execute($stmt);

    $result_count = mysqli_stmt_get_result($stmt);
    $count_data = mysqli_fetch_assoc($result_count);

    $total_monitoring = (int)($count_data['total'] ?? 0);

    mysqli_stmt_close($stmt);
}


/* =========================================================
   PROFIL / INISIAL
   ========================================================= */

$nama_user = $_SESSION['nama_lengkap'] ?? 'Keluarga';

$inisial_user = 'K';

$nama_parts_user = preg_split('/\s+/', trim($nama_user));

if (!empty($nama_parts_user[0])) {
    $inisial_user = strtoupper(substr($nama_parts_user[0], 0, 1));
}


/* =========================================================
   INISIAL PASIEN
   ========================================================= */

$inisial = 'P';

if ($pasien && !empty($pasien['nama_pasien'])) {

    $parts = preg_split('/\s+/', trim($pasien['nama_pasien']));

    if (!empty($parts[0])) {
        $inisial = strtoupper(substr($parts[0], 0, 1));
    }
}


/* =========================================================
   CLASS STATUS
   ========================================================= */

$kondisi_class =
    ($pasien && strtolower(trim($pasien['kondisi'] ?? '')) !== 'stabil')
    ? 'warning'
    : 'success';

$lokasi_class =
    ($pasien && ($pasien['status_lokasi'] ?? '') === 'Luar Yayasan')
    ? 'purple'
    : 'blue';

$status_class =
    ($pasien && strtolower($pasien['status_pasien'] ?? '') === 'aktif')
    ? 'success'
    : 'muted';

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard Keluarga - SIPM ODGJ</title>

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Poppins, Arial, sans-serif;
            background: #f5f8fc;
            color: #172033;
            font-size: 13px;
        }

        a {
            text-decoration: none;
            color: inherit;
        }


        /* =====================================================
           SIDEBAR
           ===================================================== */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 170px;
            height: 100vh;
            background: #eef4ff;
            border-right: 1px solid #dce5f2;
            display: flex;
            flex-direction: column;
            z-index: 100;
        }

        .sidebar-brand {
            text-align: center;
            padding: 18px 8px 15px;
        }

        .sidebar-brand img {
            display: block;
            width: 55px;
            height: 55px;
            object-fit: contain;
            margin: 0 auto 6px;
        }

        .sidebar-brand h2 {
            font-size: 14px;
            line-height: 1.2;
            color: #0757d5;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .sidebar-brand p {
            font-size: 7px;
            line-height: 1.35;
            color: #718096;
        }

        .sidebar-menu {
            padding: 8px;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 38px;
            padding: 9px 10px;
            margin-bottom: 4px;
            border-radius: 8px;
            color: #40516b;
            font-size: 10px;
        }

        .menu-item i {
            width: 16px;
            text-align: center;
            font-size: 14px;
        }

        .menu-item:hover {
            background: #dce9ff;
            color: #0757d5;
        }

        .menu-item.active {
            background: #d7e6ff;
            color: #0757d5;
            font-weight: 600;
        }

        .sidebar-bottom {
            margin-top: auto;
            padding: 10px;
        }

        .logout-button {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 8px 10px;
            color: #ef4444;
            font-size: 10px;
        }


        /* =====================================================
           MAIN
           ===================================================== */

        .main-content {
            margin-left: 170px;
            min-height: 100vh;
        }


        /* =====================================================
           TOPBAR
           ===================================================== */

        .topbar {
            height: 60px;
            padding: 0 22px;
            background: #fff;
            border-bottom: 1px solid #e3e8ef;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .topbar-title {
            color: #2563eb;
            font-size: 17px;
            font-weight: 700;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }


        /* =====================================================
           SEARCH
           ===================================================== */

        .search-box {
            width: 140px;
            height: 30px;
            border: 1px solid #dce3ee;
            border-radius: 18px;
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 0 11px;
            background: #fff;
            color: #9aa6b7;
            transition: .2s;
        }

        .search-box:focus-within {
            border-color: #8bb7ff;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .08);
        }

        .search-box i {
            font-size: 11px;
            color: #7d8da5;
        }

        .search-box input {
            width: 100%;
            border: 0;
            outline: 0;
            background: transparent;
            font-family: Poppins, Arial, sans-serif;
            font-size: 8px;
            color: #263850;
        }

        .search-box input::placeholder {
            color: #9aa6b7;
        }


        /* =====================================================
           NOTIFICATION
           ===================================================== */

        .notification-wrapper {
            position: relative;
        }

        .notification-button {
            position: relative;
            width: 32px;
            height: 32px;
            border: 1px solid #dce3ee;
            border-radius: 50%;
            background: #fff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: .2s;
        }

        .notification-button:hover,
        .notification-button.active {
            background: #edf4ff;
            border-color: #bfd5fa;
        }

        .notification-button i {
            font-size: 15px;
        }

        .notification-dot {
            position: absolute;
            top: 5px;
            right: 5px;
            width: 7px;
            height: 7px;
            background: #ef4444;
            border: 2px solid #fff;
            border-radius: 50%;
        }

        .notification-dropdown {
            position: absolute;
            right: 0;
            top: calc(100% + 10px);
            width: 285px;
            background: #fff;
            border: 1px solid #e1e8f2;
            border-radius: 11px;
            box-shadow: 0 10px 28px rgba(23, 33, 51, .12);
            overflow: hidden;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-5px);
            transition: .2s;
            z-index: 3000;
        }

        .notification-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .notification-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 14px;
            border-bottom: 1px solid #edf1f5;
        }

        .notification-header strong {
            font-size: 10px;
            color: #172033;
        }

        .notification-close {
            width: 24px;
            height: 24px;
            border: 0;
            border-radius: 6px;
            background: #f5f7fb;
            color: #7b8ba2;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .notification-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 13px 14px;
        }

        .notification-icon {
            width: 30px;
            height: 30px;
            min-width: 30px;
            border-radius: 50%;
            background: #e9f7ef;
            color: #16a05d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
        }

        .notification-content strong {
            display: block;
            font-size: 9px;
            color: #172033;
            margin-bottom: 3px;
        }

        .notification-content span {
            display: block;
            color: #8995a8;
            font-size: 8px;
            line-height: 1.5;
        }


        /* =====================================================
           PROFILE TOPBAR - BIRU
           ===================================================== */

        .user-profile {
            position: relative;
            display: flex;
            align-items: center;
        }

        .profile-button {
            display: flex;
            align-items: center;
            gap: 7px;
            border: 0;
            background: transparent;
            color: #172033;
            font-family: Poppins, sans-serif;
            font-size: 9px;
            font-weight: 600;
            padding: 4px 6px;
            border-radius: 8px;
            cursor: pointer;
            transition: .2s;
        }

        .profile-button:hover {
            background: #f4f7fb;
        }

        .profile-avatar-top {
            width: 30px;
            height: 30px;
            min-width: 30px;
            border-radius: 50%;
            background: #2563eb;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
        }

        .profile-name-role {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            line-height: 1.2;
        }

        .profile-name-role strong {
            font-size: 9px;
            color: #172033;
            font-weight: 600;
        }

        .profile-name-role span {
            font-size: 7px;
            color: #8995a8;
            margin-top: 2px;
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
            background: #fff;
            border: 1px solid #e1e8f2;
            border-radius: 11px;
            box-shadow: 0 10px 28px rgba(23, 33, 51, .12);
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

        .profile-avatar {
            width: 36px;
            height: 36px;
            min-width: 36px;
            border-radius: 50%;
            background: #2563eb;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            font-weight: 700;
        }

        .profile-info {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .profile-info strong {
            font-size: 10px;
            color: #172033;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .profile-info span {
            font-size: 8px;
            color: #8a95a8;
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
            background: #fff;
            color: #394b63;
            text-decoration: none;
            font: 500 9px Poppins, sans-serif;
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
           PAGE
           ===================================================== */

        .page-content {
            padding: 25px 22px 40px;
        }

        .page-header {
            margin-bottom: 20px;
        }

        .page-header h1 {
            font-size: 22px;
            margin-bottom: 5px;
            color: #102a43;
        }

        .page-header p {
            color: #718096;
            font-size: 10px;
        }


        /* =====================================================
           CARD
           ===================================================== */

        .card {
            background: #fff;
            border: 1px solid #dfe7f1;
            border-radius: 10px;
            overflow: hidden;
        }

        .empty-card {
            min-height: 190px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 30px;
        }

        .empty-icon {
            width: 42px;
            height: 42px;
            margin: 0 auto 10px;
            border-radius: 50%;
            background: #edf4ff;
            color: #7da3dd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
        }

        .empty-card strong {
            display: block;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .empty-card p {
            max-width: 520px;
            color: #8390a3;
            font-size: 9px;
            line-height: 1.6;
        }


        /* =====================================================
           PATIENT CARD
           ===================================================== */

        .patient-card {
            padding: 18px 20px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        .patient-main {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .patient-avatar {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: #e4efff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 700;
        }

        .patient-name {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .patient-registration {
            color: #8995a8;
            font-size: 9px;
        }

        .badges {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .badge {
            display: inline-flex;
            padding: 5px 8px;
            border-radius: 14px;
            font-size: 8px;
            font-weight: 600;
        }

        .badge.blue {
            background: #e6f0ff;
            color: #2563eb;
        }

        .badge.purple {
            background: #f0e9ff;
            color: #7c3aed;
        }

        .badge.success {
            background: #dcf8e8;
            color: #079447;
        }

        .badge.warning {
            background: #fff0cf;
            color: #b77900;
        }

        .badge.muted {
            background: #eef1f5;
            color: #687589;
        }


        /* =====================================================
           QUICK MENU
           ===================================================== */

        .quick-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }

        .quick-card {
            cursor: pointer;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: .2s;
        }

        .quick-card:hover {
            border-color: #bfd3f2;
            transform: translateY(-1px);
        }

        .quick-card:focus {
            outline: none;
            border-color: #bfd3f2;
        }

        .quick-icon {
            width: 36px;
            height: 36px;
            border-radius: 9px;
            background: #edf4ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .quick-card strong {
            display: block;
            font-size: 10px;
            margin-bottom: 3px;
        }

        .quick-card span {
            color: #8995a8;
            font-size: 8px;
        }


        /* =====================================================
           SECTION
           ===================================================== */

        .section-card {
            margin-bottom: 16px;
        }

        .section-header {
            padding: 15px 18px;
            border-bottom: 1px solid #edf1f5;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title-icon {
            width: 28px;
            height: 28px;
            border-radius: 7px;
            background: #edf4ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
        }

        .section-header h2 {
            font-size: 12px;
        }

        .section-header p {
            margin-top: 2px;
            color: #8a95a8;
            font-size: 8px;
        }


        /* =====================================================
           DETAIL
           ===================================================== */

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        .detail-item {
            padding: 12px 18px;
            border-top: 1px solid #edf1f5;
        }

        .detail-label {
            display: block;
            color: #8995a8;
            font-size: 8px;
            margin-bottom: 4px;
        }

        .detail-value {
            font-size: 9px;
            font-weight: 600;
            color: #263850;
        }


        /* =====================================================
           MONITORING TABLE
           ===================================================== */

        .monitoring-table {
            width: 100%;
            border-collapse: collapse;
        }

        .monitoring-table th {
            background: #f8faff;
            color: #69768c;
            font-size: 8px;
            font-weight: 600;
            text-align: left;
            padding: 10px 14px;
        }

        .monitoring-table td {
            padding: 11px 14px;
            border-top: 1px solid #edf1f5;
            font-size: 8px;
            color: #3d4d64;
            vertical-align: top;
        }

        .monitoring-table td strong {
            color: #172033;
            font-size: 9px;
        }

        .monitoring-empty {
            padding: 28px 15px;
            text-align: center;
            color: #8a95a8;
            font-size: 9px;
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
            background: #fff;
            border-radius: 13px;
            box-shadow: 0 18px 45px rgba(0, 0, 0, .16);
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

        .profile-modal-avatar {
            width: 58px;
            height: 58px;
            margin: 0 auto 12px;
            border-radius: 50%;
            background: #2563eb;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 25px;
            font-weight: 700;
        }

        .profile-modal-name {
            text-align: center;
            font-size: 15px;
            font-weight: 700;
            color: #17243a;
        }

        .profile-modal-role {
            text-align: center;
            font-size: 9px;
            color: #8a95a8;
            margin: 3px 0 17px;
        }

        .profile-detail {
            border: 1px solid #e6ebf2;
            border-radius: 9px;
            overflow: hidden;
        }

        .profile-detail-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 10px 12px;
            border-bottom: 1px solid #edf1f5;
        }

        .profile-detail-row:last-child {
            border-bottom: 0;
        }

        .profile-detail-label {
            font-size: 8px;
            color: #8a95a8;
        }

        .profile-detail-value {
            font-size: 9px;
            color: #17243a;
            font-weight: 600;
            text-align: right;
        }


        /* =====================================================
           RESPONSIVE
           ===================================================== */

        @media (max-width: 800px) {

            .quick-grid {
                grid-template-columns: 1fr;
            }

            .patient-card {
                align-items: flex-start;
                flex-direction: column;
            }

            .badges {
                justify-content: flex-start;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }
        }


        @media (max-width: 600px) {

            .sidebar {
                width: 70px;
            }

            .sidebar-brand h2,
            .sidebar-brand p,
            .menu-item span,
            .logout-button span {
                display: none;
            }

            .sidebar-brand {
                padding: 14px 5px;
            }

            .sidebar-brand img {
                width: 48px;
                height: 48px;
            }

            .menu-item {
                justify-content: center;
            }

            .main-content {
                margin-left: 70px;
            }

            .page-content {
                padding: 18px 12px 30px;
            }

            .search-box {
                display: none;
            }

            .notification-dropdown {
                right: -40px;
                width: 260px;
            }

            .profile-name-role {
                display: none;
            }
        }

    </style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
     ===================================================== -->

<aside class="sidebar">

    <div class="sidebar-brand">

        <img
            src="/SIPM-ODGJ/assets/img/logo YCKA.png"
            alt="Logo Yayasan"
        >

        <h2>SIPM ODGJ</h2>

        <p>Yayasan Cahaya Kasih Amanah</p>

    </div>


    <nav class="sidebar-menu">

        <a
            href="dashboard_keluarga.php"
            class="menu-item active"
        >
            <i class="bi bi-grid"></i>
            <span>Dashboard</span>
        </a>


        <a
            href="profil_pasien.php"
            class="menu-item"
        >
            <i class="bi bi-person-vcard"></i>
            <span>Profil Pasien</span>
        </a>


        <a
            href="perkembangan.php"
            class="menu-item"
        >
            <i class="bi bi-graph-up"></i>
            <span>Perkembangan</span>
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



<!-- =====================================================
     MAIN
     ===================================================== -->

<main class="main-content">


    <!-- =================================================
         TOPBAR
         ================================================= -->

    <header class="topbar">

        <div class="topbar-title">
            Dashboard Keluarga
        </div>


        <div class="topbar-right">


            <!-- SEARCH -->

            <div class="search-box">

                <i class="bi bi-search"></i>

                <input
                    type="text"
                    id="searchDashboard"
                    placeholder="Search..."
                    autocomplete="off"
                >

            </div>



            <!-- NOTIFICATION -->

            <div
                class="notification-wrapper"
                id="notificationWrapper"
            >

                <button
                    type="button"
                    class="notification-button"
                    id="notificationButton"
                    title="Notifikasi"
                >

                    <i class="bi bi-bell"></i>

                    <span class="notification-dot"></span>

                </button>


                <div
                    class="notification-dropdown"
                    id="notificationDropdown"
                >

                    <div class="notification-header">

                        <strong>Notifikasi</strong>

                        <button
                            type="button"
                            class="notification-close"
                            id="closeNotification"
                        >
                            <i class="bi bi-x-lg"></i>
                        </button>

                    </div>


                    <div class="notification-item">

                        <div class="notification-icon">
                            <i class="bi bi-check-lg"></i>
                        </div>


                        <div class="notification-content">

                            <strong>
                                Notifikasi diaktifkan
                            </strong>

                            <span>
                                Anda akan menerima pemberitahuan
                                mengenai informasi dan perkembangan
                                pasien.
                            </span>

                        </div>

                    </div>

                </div>

            </div>



            <!-- PROFILE -->

            <div
                class="user-profile"
                id="userProfile"
            >

                <button
                    type="button"
                    class="profile-button"
                    id="profileButton"
                >

                    <div class="profile-avatar-top">
                        <?= e($inisial_user); ?>
                    </div>


                    <div class="profile-name-role">

                        <strong>
                            <?= e($nama_user); ?>
                        </strong>

                        <span>
                            Keluarga
                        </span>

                    </div>


                    <i class="bi bi-chevron-down profile-arrow"></i>

                </button>



                <!-- PROFILE DROPDOWN -->

                <div
                    class="profile-dropdown"
                    id="profileDropdown"
                >

                    <div class="profile-dropdown-header">

                        <div class="profile-avatar">
                            <?= e($inisial_user); ?>
                        </div>


                        <div class="profile-info">

                            <strong>
                                <?= e($nama_user); ?>
                            </strong>

                            <span>
                                Keluarga
                            </span>

                        </div>

                    </div>


                    <div class="profile-divider"></div>


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
         CONTENT
         ================================================= -->

    <section class="page-content">


        <div class="page-header">

            <h1>
                Dashboard Keluarga
            </h1>

            <p>
                Pantau informasi dan perkembangan anggota keluarga
                yang terhubung dengan akun Anda.
            </p>

        </div>



        <?php if (!$pasien): ?>


            <!-- =================================================
                 EMPTY STATE
                 ================================================= -->

            <div class="card empty-card">

                <div>

                    <div class="empty-icon">
                        <i class="bi bi-person-exclamation"></i>
                    </div>


                    <strong>
                        Belum Ada Pasien Terhubung
                    </strong>


                    <p>
                        Akun keluarga ini belum terhubung dengan data pasien.
                        Silakan hubungi admin yayasan untuk menghubungkan
                        akun dengan pasien yang bersangkutan.
                    </p>

                </div>

            </div>


        <?php else: ?>


            <!-- =================================================
                 PATIENT HEADER
                 ================================================= -->

            <div class="card patient-card">

                <div class="patient-main">

                    <div class="patient-avatar">
                        <?= e($inisial); ?>
                    </div>


                    <div>

                        <div class="patient-name">
                            <?= e($pasien['nama_pasien']); ?>
                        </div>


                        <div class="patient-registration">

                            No. Registrasi:
                            <?= e($pasien['nomor_registrasi']); ?>

                        </div>

                    </div>

                </div>


                <div class="badges">

                    <span class="badge <?= $lokasi_class; ?>">
                        <?= e($pasien['status_lokasi']); ?>
                    </span>


                    <span class="badge <?= $kondisi_class; ?>">
                        <?= e($pasien['kondisi']); ?>
                    </span>


                    <span class="badge <?= $status_class; ?>">
                        <?= e($pasien['status_pasien']); ?>
                    </span>

                </div>

            </div>



            <!-- =================================================
                 QUICK MENU
                 ================================================= -->

            <div class="quick-grid">


                <a
                    href="profil_pasien.php"
                    class="card quick-card"
                >

                    <div class="quick-icon">
                        <i class="bi bi-person-vcard"></i>
                    </div>


                    <div>

                        <strong>
                            Profil Pasien
                        </strong>

                        <span>
                            Lihat informasi lengkap pasien
                        </span>

                    </div>

                </a>






                <a
                    href="perkembangan.php"
                    class="card quick-card"
                >

                    <div class="quick-icon">
                        <i class="bi bi-clipboard2-pulse"></i>
                    </div>


                    <div>

                        <strong>
                            Monitoring
                        </strong>

                        <span>
                            <?= $total_monitoring; ?>
                            data monitoring tercatat
                        </span>

                    </div>

                </a>

            </div>



            <!-- =================================================
                 STATUS PASIEN
                 ================================================= -->

            <div class="card section-card">


                <div class="section-header">

                    <div class="section-title">

                        <div class="section-title-icon">
                            <i class="bi bi-hospital"></i>
                        </div>


                        <div>

                            <h2>
                                Status Pasien
                            </h2>

                            <p>
                                Informasi kondisi pasien saat ini
                            </p>

                        </div>

                    </div>

                </div>



                <div class="detail-grid">


                    <div class="detail-item">

                        <span class="detail-label">
                            Lokasi Pasien
                        </span>

                        <span class="detail-value">
                            <?= e($pasien['status_lokasi']); ?>
                        </span>

                    </div>



                    <div class="detail-item">

                        <span class="detail-label">
                            Kondisi Pasien
                        </span>

                        <span class="detail-value">
                            <?= e($pasien['kondisi']); ?>
                        </span>

                    </div>



                    <div class="detail-item">

                        <span class="detail-label">
                            Tanggal Masuk
                        </span>

                        <span class="detail-value">
                            <?= tanggal_indo($pasien['tanggal_masuk']); ?>
                        </span>

                    </div>



                    <div class="detail-item">

                        <span class="detail-label">
                            Status
                        </span>

                        <span class="detail-value">
                            <?= e($pasien['status_pasien']); ?>
                        </span>

                    </div>

                </div>

            </div>



            <!-- =================================================
                 MONITORING TERBARU
                 ================================================= -->

            <div class="card section-card">


                <div class="section-header">

                    <div class="section-title">

                        <div class="section-title-icon">
                            <i class="bi bi-clipboard2-pulse"></i>
                        </div>


                        <div>

                            <h2>
                                Monitoring Terbaru
                            </h2>

                            <p>
                                Hasil monitoring terakhir pasien
                            </p>

                        </div>

                    </div>


                    <a
                        href="perkembangan.php"
                        style="
                            font-size:8px;
                            color:#2563eb;
                            font-weight:600;
                        "
                    >
                        Lihat Perkembangan →
                    </a>

                </div>



                <?php if (!empty($monitoring)): ?>


                    <div style="overflow-x:auto;">

                        <table class="monitoring-table">

                            <thead>

                                <tr>

                                    <th>
                                        Tanggal Monitoring
                                    </th>

                                    <th>
                                        Berat Badan
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

                                </tr>

                            </thead>


                            <tbody>

                                <?php foreach ($monitoring as $row): ?>

                                    <tr>

                                        <td>

                                            <strong>
                                                <?= tanggal_waktu(
                                                    $row['tanggal_monitoring']
                                                ); ?>
                                            </strong>

                                        </td>


                                        <td>

                                            <?=
                                                ($row['berat_badan'] !== null &&
                                                $row['berat_badan'] !== '')
                                                ? e($row['berat_badan']) . ' kg'
                                                : '-';
                                            ?>

                                        </td>


                                        <td>
                                            <?= e(
                                                $row['aktivitas_harian'] ?: '-'
                                            ); ?>
                                        </td>


                                        <td>
                                            <?= e(
                                                $row['perilaku'] ?: '-'
                                            ); ?>
                                        </td>


                                        <td>
                                            <?= e(
                                                $row['catatan'] ?: '-'
                                            ); ?>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>


                <?php else: ?>


                    <div class="monitoring-empty">

                        Belum ada data monitoring untuk pasien ini.

                    </div>


                <?php endif; ?>

            </div>


        <?php endif; ?>

    </section>

</main>



<!-- =========================================================
     PROFILE MODAL
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
                <?= e($inisial_user); ?>
            </div>


            <div class="profile-modal-name">
                <?= e($nama_user); ?>
            </div>


            <div class="profile-modal-role">
                Keluarga
            </div>



            <div class="profile-detail">


                <div class="profile-detail-row">

                    <span class="profile-detail-label">
                        Nama Lengkap
                    </span>

                    <span class="profile-detail-value">
                        <?= e($nama_user); ?>
                    </span>

                </div>



                <div class="profile-detail-row">

                    <span class="profile-detail-label">
                        ID Pengguna
                    </span>

                    <span class="profile-detail-value">
                        <?= e((string)$id_user); ?>
                    </span>

                </div>



                <div class="profile-detail-row">

                    <span class="profile-detail-label">
                        Role
                    </span>

                    <span class="profile-detail-value">
                        Keluarga
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

document.addEventListener("DOMContentLoaded", function () {


    /* =====================================================
       PROFILE
       ===================================================== */

    const btn =
        document.getElementById("profileButton");

    const dd =
        document.getElementById("profileDropdown");

    const wrap =
        document.getElementById("userProfile");

    const ps =
        document.getElementById("profileSaya");

    const modal =
        document.getElementById("profileModal");

    const close =
        document.getElementById("closeProfile");


    btn?.addEventListener("click", function (e) {

        e.preventDefault();
        e.stopPropagation();

        dd?.classList.toggle("show");
        btn?.classList.toggle("active");

        notificationDropdown?.classList.remove("show");
        notificationButton?.classList.remove("active");

    });


    ps?.addEventListener("click", function (e) {

        e.preventDefault();
        e.stopPropagation();

        dd?.classList.remove("show");
        btn?.classList.remove("active");

        modal?.classList.add("show");

    });


    close?.addEventListener("click", function () {

        modal?.classList.remove("show");

    });


    modal?.addEventListener("click", function (e) {

        if (e.target === modal) {
            modal.classList.remove("show");
        }

    });



    /* =====================================================
       NOTIFICATION
       ===================================================== */

    const notificationButton =
        document.getElementById("notificationButton");

    const notificationDropdown =
        document.getElementById("notificationDropdown");

    const notificationWrapper =
        document.getElementById("notificationWrapper");

    const closeNotification =
        document.getElementById("closeNotification");


    notificationButton?.addEventListener("click", function (e) {

        e.preventDefault();
        e.stopPropagation();

        notificationDropdown?.classList.toggle("show");
        notificationButton?.classList.toggle("active");

        dd?.classList.remove("show");
        btn?.classList.remove("active");

    });


    closeNotification?.addEventListener("click", function (e) {

        e.preventDefault();
        e.stopPropagation();

        notificationDropdown?.classList.remove("show");
        notificationButton?.classList.remove("active");

    });



    /* =====================================================
       CLICK DI LUAR DROPDOWN
       ===================================================== */

    document.addEventListener("click", function (e) {


        if (
            wrap &&
            !wrap.contains(e.target)
        ) {

            dd?.classList.remove("show");
            btn?.classList.remove("active");

        }


        if (
            notificationWrapper &&
            !notificationWrapper.contains(e.target)
        ) {

            notificationDropdown?.classList.remove("show");
            notificationButton?.classList.remove("active");

        }

    });



    /* =====================================================
       ESCAPE
       ===================================================== */

    document.addEventListener("keydown", function (e) {

        if (e.key === "Escape") {

            modal?.classList.remove("show");

            dd?.classList.remove("show");
            btn?.classList.remove("active");

            notificationDropdown?.classList.remove("show");
            notificationButton?.classList.remove("active");

        }

    });



    /* =====================================================
       SEARCH
       ===================================================== */

    const searchInput =
        document.getElementById("searchDashboard");

    searchInput?.addEventListener("input", function () {

        const keyword =
            this.value.toLowerCase().trim();

        const content =
            document.querySelector(".page-content");

        if (!content) {
            return;
        }

        /*
         * Search ini digunakan untuk mencari teks
         * yang tampil pada dashboard.
         *
         * Tidak mengubah data database.
         */

        const text =
            content.innerText.toLowerCase();

        if (keyword !== "" && !text.includes(keyword)) {

            this.setCustomValidity(
                "Data yang dicari tidak ditemukan."
            );

        } else {

            this.setCustomValidity("");

        }

    });


    searchInput?.addEventListener("keydown", function (e) {

        if (e.key === "Enter") {

            e.preventDefault();

            const keyword =
                this.value.toLowerCase().trim();

            if (keyword === "") {
                return;
            }

            const content =
                document.querySelector(".page-content");

            if (!content) {
                return;
            }

            const text =
                content.innerText.toLowerCase();

            if (text.includes(keyword)) {

                const walker =
                    document.createTreeWalker(
                        content,
                        NodeFilter.SHOW_TEXT
                    );

                let node;

                while (node = walker.nextNode()) {

                    if (
                        node.textContent
                            .toLowerCase()
                            .includes(keyword)
                    ) {

                        node.parentElement.scrollIntoView({
                            behavior: "smooth",
                            block: "center"
                        });

                        break;
                    }

                }

            } else {

                alert(
                    "Data \"" +
                    this.value +
                    "\" tidak ditemukan pada dashboard."
                );

            }

        }

    });

});

</script>


</body>
</html>