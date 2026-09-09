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
   KHUSUS PETUGAS
========================================================= */

$role = $_SESSION['role'] ?? '';

if ($role !== 'petugas') {
    die("Anda tidak memiliki akses ke halaman ini.");
}


/* =========================================================
   KONEKSI DATABASE
========================================================= */

require_once "koneksi.php";


/* =========================================================
   DATA USER LOGIN
========================================================= */

$nama_user = $_SESSION['nama_lengkap'] ?? 'Petugas';

$role_label = 'Petugas';


/* =========================================================
   ID PASIEN
========================================================= */

$id_pasien = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;


if ($id_pasien <= 0) {
    header("Location: data_pasien_petugas.php");
    exit;
}


/* =========================================================
   AMBIL DATA PASIEN
========================================================= */

$stmtPasien = mysqli_prepare(
    $conn,
    "SELECT *
     FROM pasien
     WHERE id_pasien = ?"
);


if (!$stmtPasien) {
    die(
        "Query pasien gagal dibuat: "
        . mysqli_error($conn)
    );
}


mysqli_stmt_bind_param(
    $stmtPasien,
    "i",
    $id_pasien
);


mysqli_stmt_execute($stmtPasien);


$resultPasien =
    mysqli_stmt_get_result($stmtPasien);


$pasien =
    mysqli_fetch_assoc($resultPasien);


mysqli_stmt_close($stmtPasien);


if (!$pasien) {
    die("Data pasien tidak ditemukan.");
}


/* =========================================================
   FUNGSI AMBIL NILAI FIELD
========================================================= */

function nilaiField($data, $field, $default = '-')
{
    if (
        isset($data[$field]) &&
        $data[$field] !== null &&
        $data[$field] !== ''
    ) {
        return $data[$field];
    }

    return $default;
}


/* =========================================================
   FUNGSI FORMAT TANGGAL
========================================================= */

function formatTanggalIndonesia($tanggal)
{
    if (
        empty($tanggal) ||
        $tanggal === '0000-00-00'
    ) {
        return '-';
    }

    $timestamp = strtotime($tanggal);

    if (!$timestamp) {
        return $tanggal;
    }

    $bulan = [
        1  => 'Januari',
        2  => 'Februari',
        3  => 'Maret',
        4  => 'April',
        5  => 'Mei',
        6  => 'Juni',
        7  => 'Juli',
        8  => 'Agustus',
        9  => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember'
    ];

    return date('d', $timestamp)
        . ' '
        . $bulan[(int) date('m', $timestamp)]
        . ' '
        . date('Y', $timestamp);
}


/* =========================================================
   DATA DASAR PASIEN
========================================================= */

$nomor_registrasi =
    nilaiField(
        $pasien,
        'nomor_registrasi'
    );

$nama_pasien =
    nilaiField(
        $pasien,
        'nama_pasien'
    );

$nik =
    nilaiField(
        $pasien,
        'nik'
    );

$jenis_kelamin =
    nilaiField(
        $pasien,
        'jenis_kelamin'
    );

$tempat_lahir =
    nilaiField(
        $pasien,
        'tempat_lahir'
    );

$tanggal_lahir =
    formatTanggalIndonesia(
        $pasien['tanggal_lahir'] ?? ''
    );

$alamat =
    nilaiField(
        $pasien,
        'alamat'
    );


/* =========================================================
   STATUS PASIEN
========================================================= */

$status_pasien =
    nilaiField(
        $pasien,
        'status_pasien',
        'Aktif'
    );


/* =========================================================
   STATUS LOKASI / TEMPAT PERAWATAN
========================================================= */

$status_lokasi = 'Dalam Yayasan';

if (
    isset($pasien['status_lokasi']) &&
    $pasien['status_lokasi'] !== ''
) {

    $status_lokasi =
        $pasien['status_lokasi'];

} elseif (
    isset($pasien['lokasi']) &&
    $pasien['lokasi'] !== ''
) {

    $status_lokasi =
        $pasien['lokasi'];

} elseif (
    isset($pasien['status_perawatan']) &&
    $pasien['status_perawatan'] !== ''
) {

    $status_lokasi =
        $pasien['status_perawatan'];
}


/* =========================================================
   STATUS KONDISI
========================================================= */

$status_kondisi = 'Stabil';

if (
    isset($pasien['kondisi']) &&
    $pasien['kondisi'] !== ''
) {

    $status_kondisi =
        $pasien['kondisi'];
}


/* =========================================================
   DATA KELUARGA / WALI
========================================================= */

$nama_keluarga =
    nilaiField(
        $pasien,
        'nama_keluarga'
    );

$hubungan_keluarga =
    nilaiField(
        $pasien,
        'hubungan_keluarga'
    );

$no_hp_keluarga =
    nilaiField(
        $pasien,
        'no_hp_keluarga'
    );

$alamat_keluarga =
    nilaiField(
        $pasien,
        'alamat_keluarga'
    );


/* =========================================================
   DATA WALI - JIKA FIELD TERSEDIA
========================================================= */

if ($nama_keluarga === '-') {

    $nama_keluarga =
        nilaiField(
            $pasien,
            'nama_wali'
        );
}

if ($hubungan_keluarga === '-') {

    $hubungan_keluarga =
        nilaiField(
            $pasien,
            'hubungan_wali'
        );
}

if ($no_hp_keluarga === '-') {

    $no_hp_keluarga =
        nilaiField(
            $pasien,
            'no_hp_wali'
        );
}

if ($alamat_keluarga === '-') {

    $alamat_keluarga =
        nilaiField(
            $pasien,
            'alamat_wali'
        );
}


/* =========================================================
   AMBIL RIWAYAT MONITORING
========================================================= */

/* =========================================================
   AMBIL RIWAYAT MONITORING
========================================================= */

$monitoringQuery = mysqli_query(
    $conn,
    "SELECT
        m.*,
        u.nama_lengkap
     FROM monitoring m
     LEFT JOIN users u
        ON m.id_user = u.id_user
     WHERE m.id_pasien = $id_pasien
     ORDER BY m.tanggal_monitoring DESC"
);

if (!$monitoringQuery) {
    die(
        "Query monitoring gagal: "
        . mysqli_error($conn)
    );
}


/* =========================================================
   JUMLAH MONITORING
========================================================= */

$jumlah_monitoring = mysqli_num_rows(
    $monitoringQuery
);


/*
   Jika tabel user menggunakan nama tabel users,
   query alternatif akan dicoba.
*/

if (!$monitoringQuery) {

    $monitoringQuery = mysqli_query(
        $conn,
        "SELECT
            m.*,
            u.nama_lengkap
         FROM monitoring m
         LEFT JOIN users u
            ON m.id_user = u.id_user
         WHERE m.id_pasien = $id_pasien
         ORDER BY m.tanggal_monitoring DESC"
    );
}


/* =========================================================
   JUMLAH MONITORING
========================================================= */

$jumlah_monitoring = 0;

if ($monitoringQuery) {

    $jumlah_monitoring =
        mysqli_num_rows(
            $monitoringQuery
        );
}


/* =========================================================
   INISIAL NAMA
========================================================= */

$initial_pasien =
    strtoupper(
        substr(
            trim($nama_pasien),
            0,
            1
        )
    );

$initial_user =
    strtoupper(
        substr(
            trim($nama_user),
            0,
            1
        )
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
        Detail Pasien - SIPM ODGJ
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
           RESET / BASE
        ===================================================== */

        * {
            box-sizing: border-box;
        }


        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: #f5f8fd;
            color: #17243a;
        }


        /* =====================================================
           LAYOUT
        ===================================================== */

        .dashboard-layout {
            min-height: 100vh;
        }


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


        .dashboard-layout .main-content {

            margin-left: 218px !important;

            width: calc(100% - 218px) !important;

            min-height: 100vh;

        }


        /* =====================================================
           SIDEBAR LOGO
        ===================================================== */

        .sidebar-logo {

            width: 100% !important;

            padding: 22px 10px 15px !important;

            text-align: center !important;

        }


        .sidebar-logo img {

            display: block !important;

            width: 58px !important;
            height: 58px !important;

            object-fit: contain !important;

            margin: 0 auto 7px !important;

        }


        .sidebar-logo h2 {

            margin: 0 !important;

            font-size: 17px !important;

            line-height: 1.3 !important;

            color: #2864e6 !important;

        }


        .sidebar-logo p {

            margin: 3px 0 0 !important;

            font-size: 9px !important;

            line-height: 1.3 !important;

            color: #687993;

        }


        /* =====================================================
           SIDEBAR MENU
        ===================================================== */

        .sidebar-menu {
            margin-top: 25px;
        }


        .sidebar-menu .menu-item {

            display: flex;

            align-items: center;

            gap: 13px;

            margin: 5px 11px;

            padding: 14px 15px;

            border-radius: 9px;

            text-decoration: none;

            color: #263b59;

            font-size: 13px;

            transition: 0.2s ease;

        }


        .sidebar-menu .menu-item i {

            width: 18px;

            font-size: 17px;

            color: #4e607b;

        }


        .sidebar-menu .menu-item:hover {

            background: #e5efff;

            color: #2864e6;

        }


        .sidebar-menu .menu-item:hover i {

            color: #2864e6;

        }


        .sidebar-menu .menu-item.active {

            background: #d7e7ff;

            color: #1260d6;

            font-weight: 600;

        }


        .sidebar-menu .menu-item.active i {

            color: #1260d6;

        }


        /* =====================================================
           SIDEBAR LOGOUT
        ===================================================== */

        .sidebar-bottom {

            position: absolute;

            left: 0;
            right: 0;

            bottom: 18px;

        }


        .logout-button {

            display: flex;

            align-items: center;

            gap: 12px;

            margin: 0 20px;

            padding: 11px 7px;

            text-decoration: none;

            color: #ef4444;

            font-size: 12px;

        }


        .logout-button:hover {

            color: #dc2626;

        }


        /* =====================================================
           TOPBAR
        ===================================================== */

        .topbar {

            height: 70px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 0 28px 0 34px;

            background: #ffffff;

            border-bottom: 1px solid #e2e8f1;

        }


        .topbar h1 {

            margin: 0;

            color: #2864e6;

            font-size: 24px;

            font-weight: 700;

        }


        .topbar-right {

            display: flex;

            align-items: center;

        }


        /* =====================================================
           PROFILE WRAPPER
        ===================================================== */

        .profile-wrapper {

            position: relative;

        }


        /* =====================================================
           PROFILE TRIGGER
        ===================================================== */

        .profile-trigger {

            display: flex;

            align-items: center;

            gap: 10px;

            padding: 6px 8px;

            border: none;

            border-radius: 10px;

            background: transparent;

            cursor: pointer;

            font-family: 'Poppins', sans-serif;

        }


        .profile-trigger:hover {

            background: #f4f7fc;

        }


        /*
           PENTING:
           Nama profile menggunakan class khusus.
           Tidak menggunakan .profile-info agar tidak
           bentrok dengan isi modal.
        */

        .topbar-profile-info {

            display: flex;

            flex-direction: column;

            align-items: flex-end;

            justify-content: center;

            line-height: 1.2;

        }


        .topbar-profile-name {

            color: #17243a;

            font-size: 12px;

            font-weight: 600;

            white-space: nowrap;

        }


        .topbar-profile-role {

            margin-top: 3px;

            color: #8993a6;

            font-size: 9px;

            white-space: nowrap;

        }


        /* =====================================================
           PROFILE AVATAR
        ===================================================== */

        .profile-avatar {

            width: 38px;

            height: 38px;

            min-width: 38px;

            min-height: 38px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

            background: #2864e6;

            color: #ffffff;

            font-size: 13px;

            font-weight: 600;

        }


        .profile-arrow {

            color: #8993a6;

            font-size: 10px;

            transition: 0.2s ease;

        }


        .profile-trigger.active
        .profile-arrow {

            transform: rotate(180deg);

        }


        /* =====================================================
           PROFILE DROPDOWN
        ===================================================== */

        .profile-dropdown {

            display: none;

            position: absolute;

            top: calc(100% + 9px);

            right: 0;

            width: 220px;

            background: #ffffff;

            border: 1px solid #e1e7f0;

            border-radius: 12px;

            box-shadow:
                0 12px 35px
                rgba(23, 36, 58, 0.14);

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

            padding: 17px;

        }


        .profile-avatar-large {

            width: 40px;

            height: 40px;

            min-width: 40px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

            background: #2864e6;

            color: white;

            font-size: 13px;

            font-weight: 600;

        }


        .profile-dropdown-user {

            display: flex;

            flex-direction: column;

        }


        .profile-dropdown-user strong {

            color: #17243a;

            font-size: 13px;

            font-weight: 600;

        }


        .profile-dropdown-user span {

            margin-top: 3px;

            color: #8993a6;

            font-size: 9px;

        }


        .profile-divider {

            height: 1px;

            background: #edf0f5;

            margin: 0 12px;

        }


        .profile-menu-item {

            display: flex;

            align-items: center;

            gap: 11px;

            padding: 12px 17px;

            text-decoration: none;

            color: #65738a;

            font-size: 11px;

        }


        .profile-menu-item i {

            color: #2864e6;

            font-size: 15px;

        }


        .profile-menu-item:hover {

            background: #f7f9fc;

            color: #2864e6;

        }


        .profile-menu-item.logout {

            color: #ef4444;

            margin-bottom: 5px;

        }


        .profile-menu-item.logout i {

            color: #ef4444;

        }


        .profile-menu-item.logout:hover {

            background: #fff5f5;

            color: #dc2626;

        }


        /* =====================================================
           PAGE CONTENT
        ===================================================== */

        .page-content {

            padding: 28px 38px 40px;

        }


        .page-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 25px;

        }


        .page-title h2 {

            margin: 0;

            color: #17243a;

            font-size: 29px;

            font-weight: 700;

        }


        .page-title p {

            margin: 5px 0 0;

            color: #74839b;

            font-size: 13px;

        }


        /* =====================================================
           BACK BUTTON
        ===================================================== */

        .back-button {

            display: inline-flex;

            align-items: center;

            gap: 9px;

            padding: 11px 17px;

            border: 1px solid #dce3ee;

            border-radius: 8px;

            background: #ffffff;

            color: #526078;

            text-decoration: none;

            font-size: 12px;

        }


        .back-button:hover {

            border-color: #2864e6;

            color: #2864e6;

        }


        /* =====================================================
           PATIENT HEADER CARD
        ===================================================== */

        .patient-header-card {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            padding: 26px 28px;

            margin-bottom: 24px;

            background: #ffffff;

            border: 1px solid #dfe6f0;

            border-radius: 12px;

        }


        .patient-header-left {

            display: flex;

            align-items: center;

            gap: 20px;

            min-width: 0;

        }


        .patient-avatar {

            width: 72px;

            height: 72px;

            min-width: 72px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

            background: #e4efff;

            color: #2864e6;

            font-size: 29px;

            font-weight: 600;

        }


        .patient-header-name {

            min-width: 0;

        }


        .patient-header-name h3 {

            margin: 0;

            color: #17345b;

            font-size: 25px;

            font-weight: 700;

        }


        .patient-header-name p {

            margin: 5px 0 0;

            color: #7890ae;

            font-size: 13px;

        }


        .patient-status {

            display: flex;

            align-items: center;

            justify-content: flex-end;

            flex-wrap: wrap;

            gap: 8px;

        }


        .status-badge {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding: 9px 14px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: 600;

            white-space: nowrap;

        }


        .status-location {

            background: #e7f0ff;

            color: #2864e6;

        }


        .status-stabil {

            background: #dff8eb;

            color: #159447;

        }


        .status-aktif {

            background: #dff8eb;

            color: #159447;

        }


        .status-warning {

            background: #fff1d9;

            color: #c47a00;

        }


        .status-danger {

            background: #ffe3e3;

            color: #dc2626;

        }


        /* =====================================================
           INFORMATION CARD
        ===================================================== */

        .detail-card {

            margin-bottom: 24px;

            padding: 26px 28px;

            background: #ffffff;

            border: 1px solid #dfe6f0;

            border-radius: 12px;

        }


        .detail-card-header {

            display: flex;

            align-items: center;

            gap: 11px;

            margin-bottom: 23px;

        }


        .detail-card-icon {

            width: 38px;

            height: 38px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 9px;

            background: #eaf1ff;

            color: #2864e6;

            font-size: 17px;

        }


        .detail-card-header h3 {

            margin: 0;

            color: #17243a;

            font-size: 18px;

            font-weight: 600;

        }


        /* =====================================================
           DETAIL GRID
        ===================================================== */

        .detail-grid {

            display: grid;

            grid-template-columns: repeat(2, minmax(0, 1fr));

            column-gap: 45px;

        }


        .detail-item {

            min-height: 83px;

            padding: 10px 0 17px;

            border-bottom: 1px solid #edf1f6;

        }


        .detail-label {

            display: block;

            margin-bottom: 6px;

            color: #7890ae;

            font-size: 12px;

        }


        .detail-value {

            display: block;

            color: #17345b;

            font-size: 13px;

            font-weight: 600;

            word-break: break-word;

        }


        .detail-item.full {

            grid-column: 1 / -1;

        }


        /* =====================================================
           MONITORING SUMMARY
        ===================================================== */

        .monitoring-summary {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 20px;

            padding: 17px 18px;

            border-radius: 10px;

            background: #f6f9fe;

        }


        .monitoring-summary-text {

            display: flex;

            align-items: center;

            gap: 12px;

        }


        .monitoring-summary-icon {

            width: 38px;

            height: 38px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 9px;

            background: #eaf1ff;

            color: #2864e6;

        }


        .monitoring-summary-text strong {

            display: block;

            color: #17243a;

            font-size: 13px;

        }


        .monitoring-summary-text span {

            display: block;

            margin-top: 2px;

            color: #8290a6;

            font-size: 10px;

        }


        .monitoring-count {

            color: #2864e6;

            font-size: 20px;

            font-weight: 700;

        }


        /* =====================================================
           MONITORING TABLE
        ===================================================== */

        .table-wrapper {

            width: 100%;

            overflow-x: auto;

        }


        .monitoring-table {

            width: 100%;

            border-collapse: collapse;

            min-width: 850px;

        }


        .monitoring-table th {

            padding: 13px 12px;

            background: #f7f9fc;

            border-bottom: 1px solid #e3e8f0;

            color: #65738a;

            font-size: 10px;

            font-weight: 600;

            text-align: left;

        }


        .monitoring-table td {

            padding: 15px 12px;

            border-bottom: 1px solid #edf0f5;

            color: #4d5e77;

            font-size: 11px;

            vertical-align: top;

        }


        .monitoring-table tbody tr:hover {

            background: #fbfcfe;

        }


        .condition-badge {

            display: inline-flex;

            padding: 6px 10px;

            border-radius: 15px;

            font-size: 9px;

            font-weight: 600;

        }


        .condition-stabil {

            background: #dff8eb;

            color: #159447;

        }


        .condition-pantauan {

            background: #fff1d9;

            color: #c47a00;

        }


        .condition-darurat {

            background: #ffe3e3;

            color: #dc2626;

        }


        .empty-monitoring {

            padding: 35px 20px;

            text-align: center;

            color: #8793a7;

            font-size: 12px;

        }


        .empty-monitoring i {

            display: block;

            margin-bottom: 10px;

            color: #b8c3d3;

            font-size: 30px;

        }


        /* =====================================================
           PROFILE MODAL
        ===================================================== */

        .profile-modal {

            position: fixed;

            inset: 0;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 20px;

            background: rgba(23, 36, 58, 0.28);

            opacity: 0;

            visibility: hidden;

            transition: 0.2s ease;

            z-index: 3000;

        }


        .profile-modal.show {

            opacity: 1;

            visibility: visible;

        }


        .profile-modal-card {

            width: 100%;

            max-width: 370px;

            padding: 22px;

            background: #ffffff;

            border-radius: 14px;

            box-shadow:
                0 18px 45px
                rgba(31, 48, 84, 0.18);

        }


        .profile-modal-head {

            display: flex;

            align-items: center;

            justify-content: space-between;

            margin-bottom: 18px;

        }


        .profile-modal-title {

            margin: 0;

            color: #17243a;

            font-size: 16px;

            font-weight: 700;

        }


        .profile-modal-close {

            width: 32px;

            height: 32px;

            display: flex;

            align-items: center;

            justify-content: center;

            border: 1px solid #e1e7f0;

            border-radius: 8px;

            background: #ffffff;

            color: #667085;

            cursor: pointer;

        }


        .profile-modal-close:hover {

            color: #2864e6;

            border-color: #2864e6;

        }


        .profile-modal-user {

            display: flex;

            align-items: center;

            gap: 12px;

            margin-bottom: 18px;

            padding: 13px;

            border-radius: 10px;

            background: #f6f8fc;

        }


        .profile-modal-user-name {

            margin: 0;

            color: #17243a;

            font-size: 13px;

            font-weight: 600;

        }


        .profile-modal-user-role {

            margin: 3px 0 0;

            color: #7a8499;

            font-size: 10px;

        }


        /*
           PENTING:
           Modal menggunakan class sendiri.
           Tidak menggunakan .profile-info.
        */

        .profile-modal-info {

            display: grid;

            gap: 10px;

        }


        .profile-info-row {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            padding-bottom: 10px;

            border-bottom: 1px solid #edf0f5;

        }


        .profile-info-row:last-child {

            padding-bottom: 0;

            border-bottom: none;

        }


        .profile-info-label {

            color: #8a95a8;

            font-size: 10px;

        }


        .profile-info-value {

            color: #26334a;

            font-size: 11px;

            font-weight: 600;

            text-align: right;

        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 1000px) {

            .page-content {

                padding: 25px;

            }


            .patient-header-card {

                align-items: flex-start;

                flex-direction: column;

            }


            .patient-status {

                justify-content: flex-start;

            }

        }


        @media (max-width: 800px) {

            .dashboard-layout .sidebar {

                width: 190px !important;

                min-width: 190px !important;

                max-width: 190px !important;

            }


            .dashboard-layout .main-content {

                margin-left: 190px !important;

                width: calc(100% - 190px) !important;

            }


            .page-content {

                padding: 20px;

            }


            .page-header {

                align-items: flex-start;

                flex-direction: column;

            }


            .detail-grid {

                grid-template-columns: 1fr;

                column-gap: 0;

            }


            .detail-item.full {

                grid-column: auto;

            }


            .topbar {

                padding: 0 18px;

            }


            .topbar h1 {

                font-size: 20px;

            }


            .topbar-profile-info {

                display: none;

            }

        }


        @media (max-width: 600px) {

            .dashboard-layout .sidebar {

                width: 175px !important;

                min-width: 175px !important;

                max-width: 175px !important;

            }


            .dashboard-layout .main-content {

                margin-left: 175px !important;

                width: calc(100% - 175px) !important;

            }


            .patient-header-left {

                align-items: flex-start;

            }


            .patient-avatar {

                width: 58px;

                height: 58px;

                min-width: 58px;

                font-size: 23px;

            }


            .patient-header-name h3 {

                font-size: 20px;

            }

        }

    
        /* =====================================================
           PERAPIHAN TAMPILAN - CSS SAJA
           Tidak mengubah PHP, database, link, atau fungsi.
        ====================================================== */

        /* Rapikan area logo agar tidak bertabrakan dengan menu */
        .dashboard-layout .sidebar .sidebar-logo {
            min-height: 132px !important;
            padding-top: 18px !important;
            padding-bottom: 8px !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: flex-start !important;
        }

        .dashboard-layout .sidebar .sidebar-logo img {
            width: 56px !important;
            height: 56px !important;
            margin-bottom: 5px !important;
        }

        .dashboard-layout .sidebar .sidebar-logo h2 {
            font-size: 16px !important;
            line-height: 1.2 !important;
        }

        .dashboard-layout .sidebar .sidebar-logo p {
            margin-top: 2px !important;
            font-size: 8.5px !important;
            white-space: nowrap !important;
        }

        /* Jarak menu dibuat lebih seimbang */
        .dashboard-layout .sidebar .sidebar-menu {
            margin-top: 10px !important;
        }

        .dashboard-layout .sidebar .menu-item {
            margin: 4px 11px !important;
            padding: 13px 15px !important;
        }

        /* Rapikan area utama */
        .dashboard-layout .main-content {
            background: #f5f8fd;
        }

        .page-content {
            padding-top: 30px !important;
        }

        .page-header {
            margin-bottom: 24px !important;
        }

        /* Card pasien dibuat lebih proporsional */
        .patient-header-card,
        .detail-card {
            box-shadow: 0 2px 8px rgba(23, 36, 58, 0.025);
        }

        .patient-header-card {
            padding: 24px 28px !important;
        }

        .patient-header-name h3 {
            line-height: 1.3 !important;
        }

        /* Detail informasi lebih rapi */
        .detail-card {
            padding: 25px 28px !important;
        }

        .detail-item {
            min-height: 78px !important;
        }

        /* Tabel tetap nyaman dibaca */
        .monitoring-table th,
        .monitoring-table td {
            line-height: 1.55;
        }

        /* Dropdown profile lebih rapi */
        .profile-dropdown {
            margin-top: 2px;
        }

        /* Sedikit perbaikan tampilan layar kecil */
        @media (max-width: 800px) {
            .dashboard-layout .sidebar .sidebar-logo {
                min-height: 125px !important;
            }

            .page-content {
                padding-top: 22px !important;
            }
        }

</style>

</head>


<body>


<div class="dashboard-layout">


    <!-- =====================================================
         SIDEBAR PETUGAS
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
                class="menu-item active"
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
    ====================================================== -->

    <main class="main-content">


        <!-- =================================================
             TOPBAR
        ================================================== -->

        <header class="topbar">


            <h1>
                Detail Pasien
            </h1>


            <!-- PROFILE SAJA
                 TANPA SEARCH
                 TANPA NOTIFIKASI
            -->

            <div class="topbar-right">


                <div class="profile-wrapper">


                    <button
                        type="button"
                        class="profile-trigger"
                        id="profileTrigger"
                    >


                        <div class="topbar-profile-info">


                            <span
                                class="topbar-profile-name"
                            >
                                <?= htmlspecialchars(
                                    $nama_user
                                ); ?>
                            </span>


                            <span
                                class="topbar-profile-role"
                            >
                                petugas
                            </span>


                        </div>


                        <div class="profile-avatar">


                            <?= htmlspecialchars(
                                $initial_user
                            ); ?>


                        </div>


                        <i
                            class="bi bi-chevron-down profile-arrow"
                        ></i>


                    </button>


                    <!-- PROFILE DROPDOWN -->

                    <div
                        class="profile-dropdown"
                        id="profileDropdown"
                    >


                        <div
                            class="profile-dropdown-header"
                        >


                            <div
                                class="profile-avatar-large"
                            >

                                <?= htmlspecialchars(
                                    $initial_user
                                ); ?>

                            </div>


                            <div
                                class="profile-dropdown-user"
                            >

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


                        <a
                            href="profil.php"
                            class="profile-menu-item"
                        >

                            <i
                                class="bi bi-person-circle"
                            ></i>

                            <span>
                                Profil Saya
                            </span>

                        </a>


                        <a
                            href="logout.php"
                            class="profile-menu-item logout"
                        >

                            <i
                                class="bi bi-box-arrow-left"
                            ></i>

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


            <!-- =================================================
                 PAGE HEADER
            ================================================== -->

            <div class="page-header">


                <div class="page-title">


                    <h2>
                        Detail Pasien
                    </h2>


                    <p>
                        Informasi lengkap data pasien ODGJ
                    </p>


                </div>


                <a
                    href="data_pasien_petugas.php"
                    class="back-button"
                >

                    <i class="bi bi-arrow-left"></i>

                    Kembali

                </a>


            </div>


            <!-- =================================================
                 PATIENT HEADER
            ================================================== -->

            <div class="patient-header-card">


                <div class="patient-header-left">


                    <div class="patient-avatar">

                        <?= htmlspecialchars(
                            $initial_pasien
                        ); ?>

                    </div>


                    <div class="patient-header-name">


                        <h3>
                            <?= htmlspecialchars(
                                $nama_pasien
                            ); ?>
                        </h3>


                        <p>
                            Nomor Registrasi:
                            <?= htmlspecialchars(
                                $nomor_registrasi
                            ); ?>
                        </p>


                    </div>


                </div>


                <div class="patient-status">


                    <!-- LOKASI -->

                    <span
                        class="status-badge status-location"
                    >

                        <?= htmlspecialchars(
                            $status_lokasi
                        ); ?>

                    </span>


                    <!-- KONDISI -->

                    <?php

                    $class_kondisi =
                        'status-stabil';

                    if (
                        strtolower(
                            $status_kondisi
                        )
                        ===
                        'darurat'
                    ) {

                        $class_kondisi =
                            'status-danger';

                    } elseif (
                        strtolower(
                            $status_kondisi
                        )
                        ===
                        'perlu pantauan'
                    ) {

                        $class_kondisi =
                            'status-warning';
                    }

                    ?>


                    <span
                        class="status-badge <?= $class_kondisi; ?>"
                    >

                        <?= htmlspecialchars(
                            $status_kondisi
                        ); ?>

                    </span>


                    <!-- STATUS PASIEN -->

                    <?php

                    $class_status =
                        'status-aktif';

                    if (
                        strtolower(
                            $status_pasien
                        )
                        !==
                        'aktif'
                    ) {

                        $class_status =
                            'status-warning';
                    }

                    ?>


                    <span
                        class="status-badge <?= $class_status; ?>"
                    >

                        <?= htmlspecialchars(
                            $status_pasien
                        ); ?>

                    </span>


                </div>


            </div>


            <!-- =================================================
                 IDENTITAS PASIEN
            ================================================== -->

            <div class="detail-card">


                <div class="detail-card-header">


                    <div class="detail-card-icon">

                        <i
                            class="bi bi-person"
                        ></i>

                    </div>


                    <h3>
                        Identitas Pasien
                    </h3>


                </div>


                <div class="detail-grid">


                    <!-- NOMOR REGISTRASI -->

                    <div class="detail-item">


                        <span class="detail-label">
                            Nomor Registrasi
                        </span>


                        <span class="detail-value">

                            <?= htmlspecialchars(
                                $nomor_registrasi
                            ); ?>

                        </span>


                    </div>


                    <!-- NIK -->

                    <div class="detail-item">


                        <span class="detail-label">
                            NIK
                        </span>


                        <span class="detail-value">

                            <?= htmlspecialchars(
                                $nik
                            ); ?>

                        </span>


                    </div>


                    <!-- NAMA -->

                    <div class="detail-item">


                        <span class="detail-label">
                            Nama Pasien
                        </span>


                        <span class="detail-value">

                            <?= htmlspecialchars(
                                $nama_pasien
                            ); ?>

                        </span>


                    </div>


                    <!-- JENIS KELAMIN -->

                    <div class="detail-item">


                        <span class="detail-label">
                            Jenis Kelamin
                        </span>


                        <span class="detail-value">

                            <?= htmlspecialchars(
                                $jenis_kelamin
                            ); ?>

                        </span>


                    </div>


                    <!-- TEMPAT LAHIR -->

                    <div class="detail-item">


                        <span class="detail-label">
                            Tempat Lahir
                        </span>


                        <span class="detail-value">

                            <?= htmlspecialchars(
                                $tempat_lahir
                            ); ?>

                        </span>


                    </div>


                    <!-- TANGGAL LAHIR -->

                    <div class="detail-item">


                        <span class="detail-label">
                            Tanggal Lahir
                        </span>


                        <span class="detail-value">

                            <?= htmlspecialchars(
                                $tanggal_lahir
                            ); ?>

                        </span>


                    </div>


                    <!-- ALAMAT -->

                    <div
                        class="detail-item full"
                    >


                        <span class="detail-label">
                            Alamat
                        </span>


                        <span class="detail-value">

                            <?= htmlspecialchars(
                                $alamat
                            ); ?>

                        </span>


                    </div>


                </div>


            </div>


            <!-- =================================================
                 KELUARGA / WALI
            ================================================== -->

            <?php
            /*
               Card ini tetap ditampilkan.
               Jika data belum ada, akan menampilkan "-".
            */
            ?>

            <div class="detail-card">


                <div class="detail-card-header">


                    <div class="detail-card-icon">

                        <i
                            class="bi bi-people"
                        ></i>

                    </div>


                    <h3>
                        Data Keluarga / Wali
                    </h3>


                </div>


                <div class="detail-grid">


                    <div class="detail-item">


                        <span class="detail-label">
                            Nama Keluarga / Wali
                        </span>


                        <span class="detail-value">

                            <?= htmlspecialchars(
                                $nama_keluarga
                            ); ?>

                        </span>


                    </div>


                    <div class="detail-item">


                        <span class="detail-label">
                            Hubungan
                        </span>


                        <span class="detail-value">

                            <?= htmlspecialchars(
                                $hubungan_keluarga
                            ); ?>

                        </span>


                    </div>


                    <div class="detail-item">


                        <span class="detail-label">
                            Nomor HP
                        </span>


                        <span class="detail-value">

                            <?= htmlspecialchars(
                                $no_hp_keluarga
                            ); ?>

                        </span>


                    </div>


                    <div class="detail-item">


                        <span class="detail-label">
                            Alamat Keluarga / Wali
                        </span>


                        <span class="detail-value">

                            <?= htmlspecialchars(
                                $alamat_keluarga
                            ); ?>

                        </span>


                    </div>


                </div>


            </div>


            <!-- =================================================
                 RIWAYAT MONITORING
            ================================================== -->

            <div class="detail-card">


                <div class="detail-card-header">


                    <div class="detail-card-icon">

                        <i
                            class="bi bi-clipboard2-pulse"
                        ></i>

                    </div>


                    <h3>
                        Riwayat Monitoring
                    </h3>


                </div>


                <div class="monitoring-summary">


                    <div
                        class="monitoring-summary-text"
                    >


                        <div
                            class="monitoring-summary-icon"
                        >

                            <i
                                class="bi bi-activity"
                            ></i>

                        </div>


                        <div>

                            <strong>
                                Total Monitoring
                            </strong>

                            <span>
                                Riwayat monitoring pasien
                            </span>

                        </div>


                    </div>


                    <div class="monitoring-count">

                        <?= $jumlah_monitoring; ?>

                    </div>


                </div>


                <?php if (
                    $monitoringQuery &&
                    mysqli_num_rows(
                        $monitoringQuery
                    ) > 0
                ): ?>


                    <div class="table-wrapper">


                        <table
                            class="monitoring-table"
                        >


                            <thead>

                                <tr>

                                    <th>
                                        Tanggal
                                    </th>

                                    <th>
                                        Petugas
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

                                </tr>

                            </thead>


                            <tbody>


                                <?php
                                while (
                                    $monitoring =
                                    mysqli_fetch_assoc(
                                        $monitoringQuery
                                    )
                                ):
                                ?>


                                    <?php

                                    $tanggal_monitoring =
                                        $monitoring[
                                            'tanggal_monitoring'
                                        ]
                                        ??
                                        '';

                                    $petugas_monitoring =
                                        $monitoring[
                                            'nama_lengkap'
                                        ]
                                        ??
                                        'Petugas';

                                    $berat_monitoring =
                                        $monitoring[
                                            'berat_badan'
                                        ]
                                        ??
                                        '';

                                    $kondisi_monitoring =
                                        $monitoring[
                                            'kondisi'
                                        ]
                                        ??
                                        '-';

                                    $aktivitas_monitoring =
                                        $monitoring[
                                            'aktivitas_harian'
                                        ]
                                        ??
                                        '-';

                                    $perilaku_monitoring =
                                        $monitoring[
                                            'perilaku'
                                        ]
                                        ??
                                        '-';

                                    $catatan_monitoring =
                                        $monitoring[
                                            'catatan'
                                        ]
                                        ??
                                        '-';


                                    $class_condition =
                                        'condition-stabil';


                                    if (
                                        strtolower(
                                            $kondisi_monitoring
                                        )
                                        ===
                                        'darurat'
                                    ) {

                                        $class_condition =
                                            'condition-darurat';

                                    } elseif (
                                        strtolower(
                                            $kondisi_monitoring
                                        )
                                        ===
                                        'perlu pantauan'
                                    ) {

                                        $class_condition =
                                            'condition-pantauan';

                                    }

                                    ?>


                                    <tr>


                                        <td>

                                            <?= htmlspecialchars(
                                                formatTanggalIndonesia(
                                                    $tanggal_monitoring
                                                )
                                            ); ?>


                                        </td>


                                        <td>

                                            <?= htmlspecialchars(
                                                $petugas_monitoring
                                            ); ?>


                                        </td>


                                        <td>

                                            <?php if (
                                                $berat_monitoring
                                                !==
                                                ''
                                                &&
                                                $berat_monitoring
                                                !==
                                                null
                                            ): ?>

                                                <?= htmlspecialchars(
                                                    $berat_monitoring
                                                ); ?>
                                                kg

                                            <?php else: ?>

                                                -

                                            <?php endif; ?>


                                        </td>


                                        <td>


                                            <span
                                                class="condition-badge <?= $class_condition; ?>"
                                            >

                                                <?= htmlspecialchars(
                                                    $kondisi_monitoring
                                                ); ?>

                                            </span>


                                        </td>


                                        <td>

                                            <?= nl2br(
                                                htmlspecialchars(
                                                    $aktivitas_monitoring
                                                )
                                            ); ?>


                                        </td>


                                        <td>

                                            <?= nl2br(
                                                htmlspecialchars(
                                                    $perilaku_monitoring
                                                )
                                            ); ?>


                                        </td>


                                        <td>

                                            <?= nl2br(
                                                htmlspecialchars(
                                                    $catatan_monitoring
                                                )
                                            ); ?>


                                        </td>


                                    </tr>


                                <?php endwhile; ?>


                            </tbody>


                        </table>


                    </div>


                <?php else: ?>


                    <div class="empty-monitoring">


                        <i
                            class="bi bi-clipboard-x"
                        ></i>


                        Belum ada riwayat monitoring
                        untuk pasien ini.


                    </div>


                <?php endif; ?>


            </div>


        </div>


    </main>


</div>


<!-- =========================================================
     PROFILE MODAL
========================================================= -->

<div
    class="profile-modal"
    id="profileModal"
    aria-hidden="true"
>


    <div
        class="profile-modal-card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="profileModalTitle"
    >


        <div class="profile-modal-head">


            <h3
                class="profile-modal-title"
                id="profileModalTitle"
            >
                Profil Saya
            </h3>


            <button
                type="button"
                class="profile-modal-close"
                id="profileModalClose"
            >

                <i
                    class="bi bi-x-lg"
                ></i>

            </button>


        </div>


        <div class="profile-modal-user">


            <div class="profile-avatar">

                <?= htmlspecialchars(
                    $initial_user
                ); ?>

            </div>


            <div>


                <p
                    class="profile-modal-user-name"
                >

                    <?= htmlspecialchars(
                        $nama_user
                    ); ?>

                </p>


                <p
                    class="profile-modal-user-role"
                >
                    Petugas
                </p>


            </div>


        </div>


        <!-- =================================================
             MODAL PROFILE INFO
             CLASS KHUSUS, TIDAK BENTROK
        ================================================== -->

        <div class="profile-modal-info">


            <div class="profile-info-row">


                <span class="profile-info-label">
                    Nama
                </span>


                <span class="profile-info-value">

                    <?= htmlspecialchars(
                        $nama_user
                    ); ?>

                </span>


            </div>


            <div class="profile-info-row">


                <span class="profile-info-label">
                    ID Pengguna
                </span>


                <span class="profile-info-value">

                    <?= (int) $_SESSION['id_user']; ?>

                </span>


            </div>


            <div class="profile-info-row">


                <span class="profile-info-label">
                    Role
                </span>


                <span class="profile-info-value">
                    Petugas
                </span>


            </div>


        </div>


    </div>


</div>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {


        /* =====================================================
           PROFILE DROPDOWN
        ===================================================== */

        const profileTrigger =
            document.getElementById(
                'profileTrigger'
            );

        const profileDropdown =
            document.getElementById(
                'profileDropdown'
            );


        if (
            profileTrigger &&
            profileDropdown
        ) {


            profileTrigger.addEventListener(
                'click',
                function (event) {

                    event.stopPropagation();

                    profileDropdown.classList.toggle(
                        'show'
                    );

                    profileTrigger.classList.toggle(
                        'active'
                    );

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

                    profileDropdown.classList.remove(
                        'show'
                    );

                    profileTrigger.classList.remove(
                        'active'
                    );

                }
            );

        }


        /* =====================================================
           PROFILE MODAL
        ===================================================== */

        const profileModal =
            document.getElementById(
                'profileModal'
            );

        const profileModalClose =
            document.getElementById(
                'profileModalClose'
            );


        /*
           Saat "Profil Saya" diklik,
           modal akan muncul.
        */

        const profileMenu =
            document.querySelector(
                '.profile-menu-item:not(.logout)'
            );


        if (
            profileMenu &&
            profileModal
        ) {

            profileMenu.addEventListener(
                'click',
                function (event) {

                    /*
                       Jika profil.php memang ingin
                       dibuka sebagai halaman sendiri,
                       hapus bagian preventDefault ini.
                    */

                    event.preventDefault();

                    profileModal.classList.add(
                        'show'
                    );

                    profileModal.setAttribute(
                        'aria-hidden',
                        'false'
                    );

                    profileDropdown.classList.remove(
                        'show'
                    );

                    profileTrigger.classList.remove(
                        'active'
                    );

                }
            );

        }


        /* CLOSE MODAL */

        if (
            profileModalClose &&
            profileModal
        ) {

            profileModalClose.addEventListener(
                'click',
                function () {

                    profileModal.classList.remove(
                        'show'
                    );

                    profileModal.setAttribute(
                        'aria-hidden',
                        'true'
                    );

                }
            );

        }


        /* KLIK DI LUAR MODAL */

        if (profileModal) {

            profileModal.addEventListener(
                'click',
                function (event) {

                    if (
                        event.target ===
                        profileModal
                    ) {

                        profileModal.classList.remove(
                            'show'
                        );

                        profileModal.setAttribute(
                            'aria-hidden',
                            'true'
                        );

                    }

                }
            );

        }


        /* TOMBOL ESC */

        document.addEventListener(
            'keydown',
            function (event) {

                if (
                    event.key === 'Escape' &&
                    profileModal
                ) {

                    profileModal.classList.remove(
                        'show'
                    );

                    profileModal.setAttribute(
                        'aria-hidden',
                        'true'
                    );

                }

            }
        );

    }
);

</script>


</body>

</html>