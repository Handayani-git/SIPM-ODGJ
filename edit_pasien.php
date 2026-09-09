<?php

session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

require_once "koneksi.php";


/* =========================
   CEK ID PASIEN
========================= */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: data_pasien.php");
    exit;
}

$id_pasien = (int) $_GET['id'];


/* =========================
   PROSES UPDATE
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nomor_registrasi = trim($_POST['nomor_registrasi']);
    $nama_pasien      = trim($_POST['nama_pasien']);
    $nik              = trim($_POST['nik']);
    $jenis_kelamin    = $_POST['jenis_kelamin'];
    $tempat_lahir     = trim($_POST['tempat_lahir']);
    $tanggal_lahir    = $_POST['tanggal_lahir'];
    $alamat           = trim($_POST['alamat']);
    $status_lokasi    = $_POST['status_lokasi'];
    $kondisi          = $_POST['kondisi'];
    $tanggal_masuk    = $_POST['tanggal_masuk'];
    $status_pasien    = $_POST['status_pasien'];


    /* =========================
       CEK NOMOR REGISTRASI
    ========================= */

    $cek = mysqli_prepare(
        $conn,
        "SELECT id_pasien
         FROM pasien
         WHERE nomor_registrasi = ?
         AND id_pasien != ?"
    );

    mysqli_stmt_bind_param(
        $cek,
        "si",
        $nomor_registrasi,
        $id_pasien
    );

    mysqli_stmt_execute($cek);

    $hasil_cek = mysqli_stmt_get_result($cek);


    if (mysqli_num_rows($hasil_cek) > 0) {

        $error = "Nomor registrasi sudah digunakan oleh pasien lain.";

    } else {

        /* =========================
           UPDATE DATA
        ========================= */

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE pasien SET
                nomor_registrasi = ?,
                nama_pasien = ?,
                nik = ?,
                jenis_kelamin = ?,
                tempat_lahir = ?,
                tanggal_lahir = ?,
                alamat = ?,
                status_lokasi = ?,
                kondisi = ?,
                tanggal_masuk = ?,
                status_pasien = ?
             WHERE id_pasien = ?"
        );


        mysqli_stmt_bind_param(
            $stmt,
            "sssssssssssi",
            $nomor_registrasi,
            $nama_pasien,
            $nik,
            $jenis_kelamin,
            $tempat_lahir,
            $tanggal_lahir,
            $alamat,
            $status_lokasi,
            $kondisi,
            $tanggal_masuk,
            $status_pasien,
            $id_pasien
        );


        if (mysqli_stmt_execute($stmt)) {

            header("Location: data_pasien.php?status=updated");
            exit;

        } else {

            $error = "Data pasien gagal diperbarui: " . mysqli_error($conn);

        }

    }

}


/* =========================
   AMBIL DATA PASIEN
========================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT * FROM pasien WHERE id_pasien = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_pasien
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$pasien = mysqli_fetch_assoc($result);


if (!$pasien) {

    die("Data pasien tidak ditemukan.");

}


/* =========================
   DATA USER YANG LOGIN
========================= */

$id_user = (int) $_SESSION['id_user'];

$stmt_user = mysqli_prepare(
    $conn,
    "SELECT nama_lengkap, username, role
     FROM users
     WHERE id_user = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt_user, "i", $id_user);
mysqli_stmt_execute($stmt_user);

$result_user = mysqli_stmt_get_result($stmt_user);
$user = mysqli_fetch_assoc($result_user);

mysqli_stmt_close($stmt_user);

$nama_user = $user['nama_lengkap'] ?? 'Admin';
$username_user = $user['username'] ?? 'admin';
$role_user = $user['role'] ?? 'admin';

$inisial_user = strtoupper(substr(trim($nama_user), 0, 1));
if ($inisial_user === '') {
    $inisial_user = 'A';
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
        Edit Pasien - Sistem Informasi ODGJ
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

            align-items: flex-start;

            margin-bottom: 24px;

        }


        .page-title h2 {

            margin: 0;

            font-size: 24px;

            font-weight: 700;

        }


        .page-title p {

            margin: 6px 0 0;

            color: #7a8499;

            font-size: 13px;

        }


        .back-button {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            padding: 10px 16px;

            border: 1px solid #dce3ee;

            border-radius: 8px;

            background: white;

            color: #526078;

            text-decoration: none;

            font-size: 12px;

            font-weight: 500;

        }


        .back-button:hover {

            background: #f7f9fc;

        }


        /* =========================
           FORM CARD
        ========================= */

        .form-card {

            background: white;

            border: 1px solid #e1e7f0;

            border-radius: 12px;

            padding: 28px;

        }


        .form-section {

            margin-bottom: 30px;

        }


        .form-section:last-child {

            margin-bottom: 0;

        }


        .form-section-title {

            display: flex;

            align-items: center;

            gap: 10px;

            margin-bottom: 20px;

            padding-bottom: 12px;

            border-bottom: 1px solid #edf0f5;

        }


        .form-section-title i {

            width: 30px;

            height: 30px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 7px;

            background: #e8f0ff;

            color: #2864e6;

        }


        .form-section-title h3 {

            margin: 0;

            font-size: 15px;

            font-weight: 600;

        }


        /* =========================
           FORM GRID
        ========================= */

        .form-grid {

            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 18px 22px;

        }


        .form-group {

            display: flex;

            flex-direction: column;

        }


        .form-group.full {

            grid-column: 1 / -1;

        }


        .form-group label {

            margin-bottom: 7px;

            font-size: 12px;

            font-weight: 500;

            color: #344054;

        }


        .required {

            color: #e53935;

        }


        .form-group input,
        .form-group select,
        .form-group textarea {

            width: 100%;

            box-sizing: border-box;

            padding: 11px 13px;

            border: 1px solid #dce3ee;

            border-radius: 8px;

            outline: none;

            background: white;

            color: #26334a;

            font-family: 'Poppins', sans-serif;

            font-size: 12px;

        }


        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {

            border-color: #2864e6;

            box-shadow:
                0 0 0 3px rgba(40, 100, 230, .08);

        }


        .form-group textarea {

            min-height: 90px;

            resize: vertical;

        }


        /* =========================
           ERROR
        ========================= */

        .error-message {

            display: flex;

            align-items: center;

            gap: 9px;

            margin-bottom: 20px;

            padding: 12px 15px;

            border: 1px solid #ffd1d1;

            border-radius: 8px;

            background: #fff5f5;

            color: #d93636;

            font-size: 12px;

        }


        /* =========================
           BUTTON
        ========================= */

        .form-actions {

            display: flex;

            justify-content: flex-end;

            gap: 10px;

            margin-top: 30px;

            padding-top: 20px;

            border-top: 1px solid #edf0f5;

        }


        .cancel-button,
        .save-button {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            padding: 11px 20px;

            border-radius: 8px;

            font-family: 'Poppins', sans-serif;

            font-size: 12px;

            font-weight: 600;

            text-decoration: none;

            cursor: pointer;

        }


        .cancel-button {

            border: 1px solid #dce3ee;

            background: white;

            color: #667085;

        }


        .save-button {

            border: none;

            background: #2864e6;

            color: white;

        }


        .save-button:hover {

            background: #1f56ca;

        }


        @media (max-width: 800px) {

            .form-grid {

                grid-template-columns: 1fr;

            }

            .form-group.full {

                grid-column: auto;

            }

        }


        /* =========================
           PROFILE TOPBAR REVISI
        ========================= */

        .topbar .topbar-right {
            display: flex !important;
            align-items: center !important;
            justify-content: flex-end !important;
            gap: 0 !important;
            width: auto !important;
            flex-shrink: 0 !important;
        }

        .profile-top-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .profile-top-button {
            display: flex;
            align-items: center;
            gap: 9px;
            border: 0;
            background: transparent;
            padding: 5px 6px;
            border-radius: 9px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            transition: .2s ease;
        }

        .profile-top-button:hover { background: #f4f7fb; }

        .profile-avatar-top {
            width: 34px;
            height: 34px;
            min-width: 34px;
            border-radius: 50%;
            background: #2864e6;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
        }

        .profile-top-text {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            line-height: 1.2;
        }

        .profile-top-text strong {
            color: #172033;
            font-size: 10px;
            font-weight: 700;
            max-width: 125px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .profile-top-text small {
            color: #8a95a8;
            font-size: 8px;
            margin-top: 2px;
        }

        .profile-top-arrow {
            color: #7b8ba2;
            font-size: 9px;
            transition: transform .2s ease;
        }

        .profile-top-button.active .profile-top-arrow {
            transform: rotate(180deg);
        }

        .profile-dropdown {
            position: absolute;
            right: 0;
            top: calc(100% + 9px);
            width: 220px;
            background: #fff;
            border: 1px solid #e1e8f2;
            border-radius: 11px;
            box-shadow: 0 10px 28px rgba(23,33,51,.12);
            overflow: hidden;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-5px);
            transition: .2s ease;
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
            background: #eaf1ff;
            color: #2864e6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
        }

        .profile-dropdown-info {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .profile-dropdown-info strong {
            color: #172033;
            font-size: 10px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .profile-dropdown-info span {
            color: #8a95a8;
            font-size: 8px;
            margin-top: 2px;
        }

        .profile-dropdown-divider { height: 1px; background: #edf1f5; }

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
            font: 500 9px 'Poppins', sans-serif;
            text-align: left;
            cursor: pointer;
        }

        .profile-menu-item:hover { background: #f7faff; color: #2864e6; }
        .profile-menu-item i { width: 15px; text-align: center; font-size: 13px; }
        .profile-menu-item.logout-item { color: #dc3545; }
        .profile-menu-item.logout-item:hover { background: #fff5f5; color: #dc3545; }

        .profile-modal {
            position: fixed;
            inset: 0;
            background: rgba(15,23,42,.35);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            opacity: 0;
            visibility: hidden;
            transition: .2s ease;
            z-index: 5000;
        }

        .profile-modal.show { opacity: 1; visibility: visible; }

        .profile-modal-card {
            width: 100%;
            max-width: 360px;
            background: #fff;
            border-radius: 13px;
            box-shadow: 0 18px 45px rgba(0,0,0,.16);
            overflow: hidden;
        }

        .profile-modal-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 17px;
            border-bottom: 1px solid #edf1f5;
        }

        .profile-modal-top h3 { margin: 0; font-size: 13px; color: #17243a; }
        .profile-close {
            width: 28px; height: 28px; border: 0; border-radius: 7px;
            background: #f5f7fb; color: #69768c; display: flex;
            align-items: center; justify-content: center; cursor: pointer;
        }
        .profile-modal-body { padding: 20px 18px; }
        .profile-modal-avatar {
            width: 58px; height: 58px; margin: 0 auto 12px; border-radius: 50%;
            background: #eaf1ff; color: #2864e6; display: flex;
            align-items: center; justify-content: center; font-size: 20px; font-weight: 700;
        }
        .profile-modal-name { text-align: center; font-size: 15px; font-weight: 700; color: #17243a; }
        .profile-modal-role { text-align: center; font-size: 9px; color: #8a95a8; margin: 3px 0 17px; }
        .profile-detail { border: 1px solid #e6ebf2; border-radius: 9px; overflow: hidden; }
        .profile-detail-row {
            display: flex; justify-content: space-between; gap: 15px; padding: 10px 12px;
            border-bottom: 1px solid #edf1f5;
        }
        .profile-detail-row:last-child { border-bottom: 0; }
        .profile-detail-label { font-size: 8px; color: #8a95a8; }
        .profile-detail-value { font-size: 9px; color: #17243a; font-weight: 600; text-align: right; }

    </style>

</head>


<body>


<div class="dashboard-layout">


    <!-- SIDEBAR -->

    <aside class="sidebar">


        <div class="sidebar-brand">

            <img
                src="/SIPM-ODGJ/assets/img/logo YCKA.png"
                alt="Logo Yayasan Cahaya Kasih Amanah"
                class="sidebar-logo"
            >

            <h2>SIPM ODGJ</h2>

            <p>
                Yayasan Cahaya Kasih Amanah
            </p>

        </div>


        <nav class="sidebar-menu">


            <a
                href="dashboard.php"
                class="menu-item"
            >

                <i class="bi bi-grid-1x2"></i>

                <span>Dashboard</span>

            </a>


            <a
                href="data_pasien.php"
                class="menu-item active"
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



    <!-- MAIN -->

    <main class="main-content">


        <header class="topbar">

            <h1>
                Edit Pasien
            </h1>


            <div class="topbar-right">

                <div class="profile-top-wrapper" id="profileTopWrapper">

                    <button
                        type="button"
                        class="profile-top-button"
                        id="profileTopButton"
                        aria-label="Profil Saya"
                    >
                        <span class="profile-avatar-top">
                            <?= htmlspecialchars($inisial_user); ?>
                        </span>

                        <span class="profile-top-text">
                            <strong><?= htmlspecialchars($nama_user); ?></strong>
                            <small><?= htmlspecialchars(ucfirst($role_user)); ?></small>
                        </span>

                        <i class="bi bi-chevron-down profile-top-arrow"></i>
                    </button>

                    <div class="profile-dropdown" id="profileDropdown">
                        <div class="profile-dropdown-header">
                            <div class="profile-dropdown-avatar">
                                <?= htmlspecialchars($inisial_user); ?>
                            </div>
                            <div class="profile-dropdown-info">
                                <strong><?= htmlspecialchars($nama_user); ?></strong>
                                <span><?= htmlspecialchars(ucfirst($role_user)); ?></span>
                            </div>
                        </div>

                        <div class="profile-dropdown-divider"></div>

                        <button type="button" class="profile-menu-item" id="profileSayaButton">
                            <i class="bi bi-person"></i>
                            <span>Profil Saya</span>
                        </button>

                        <a href="logout.php" class="profile-menu-item logout-item">
                            <i class="bi bi-box-arrow-left"></i>
                            <span>Logout</span>
                        </a>
                    </div>

                </div>

            </div>

        </header>



        <div class="page-content">


            <!-- PAGE HEADER -->

            <div class="page-header">


                <div class="page-title">

                    <h2>
                        Edit Data Pasien
                    </h2>

                    <p>
                        Perbarui informasi data pasien
                    </p>

                </div>


                <a
                    href="data_pasien.php"
                    class="back-button"
                >

                    <i class="bi bi-arrow-left"></i>

                    Kembali

                </a>


            </div>



            <!-- FORM -->

            <div class="form-card">


                <?php if (isset($error)): ?>

                    <div class="error-message">

                        <i class="bi bi-exclamation-circle"></i>

                        <?= htmlspecialchars($error); ?>

                    </div>

                <?php endif; ?>


                <form
                    action="edit_pasien.php?id=<?= $id_pasien; ?>"
                    method="POST"
                >


                    <!-- IDENTITAS -->

                    <div class="form-section">


                        <div class="form-section-title">

                            <i class="bi bi-person"></i>

                            <h3>
                                Identitas Pasien
                            </h3>

                        </div>


                        <div class="form-grid">


                            <div class="form-group">

                                <label for="nomor_registrasi">

                                    Nomor Registrasi

                                    <span class="required">*</span>

                                </label>

                                <input
                                    type="text"
                                    id="nomor_registrasi"
                                    name="nomor_registrasi"
                                    value="<?= htmlspecialchars($pasien['nomor_registrasi']); ?>"
                                    required
                                >

                            </div>


                            <div class="form-group">

                                <label for="nik">
                                    NIK
                                </label>

                                <input
                                    type="text"
                                    id="nik"
                                    name="nik"
                                    value="<?= htmlspecialchars($pasien['nik'] ?? ''); ?>"
                                >

                            </div>


                            <div class="form-group">

                                <label for="nama_pasien">

                                    Nama Pasien

                                    <span class="required">*</span>

                                </label>

                                <input
                                    type="text"
                                    id="nama_pasien"
                                    name="nama_pasien"
                                    value="<?= htmlspecialchars($pasien['nama_pasien']); ?>"
                                    required
                                >

                            </div>


                            <div class="form-group">

                                <label for="jenis_kelamin">

                                    Jenis Kelamin

                                    <span class="required">*</span>

                                </label>

                                <select
                                    id="jenis_kelamin"
                                    name="jenis_kelamin"
                                    required
                                >

                                    <option value="">
                                        Pilih jenis kelamin
                                    </option>

                                    <option
                                        value="Laki-laki"
                                        <?= $pasien['jenis_kelamin'] === 'Laki-laki' ? 'selected' : ''; ?>
                                    >
                                        Laki-laki
                                    </option>

                                    <option
                                        value="Perempuan"
                                        <?= $pasien['jenis_kelamin'] === 'Perempuan' ? 'selected' : ''; ?>
                                    >
                                        Perempuan
                                    </option>

                                </select>

                            </div>


                            <div class="form-group">

                                <label for="tempat_lahir">
                                    Tempat Lahir
                                </label>

                                <input
                                    type="text"
                                    id="tempat_lahir"
                                    name="tempat_lahir"
                                    value="<?= htmlspecialchars($pasien['tempat_lahir'] ?? ''); ?>"
                                >

                            </div>


                            <div class="form-group">

                                <label for="tanggal_lahir">

                                    Tanggal Lahir

                                    <span class="required">*</span>

                                </label>

                                <input
                                    type="date"
                                    id="tanggal_lahir"
                                    name="tanggal_lahir"
                                    value="<?= htmlspecialchars($pasien['tanggal_lahir']); ?>"
                                    required
                                >

                            </div>


                            <div class="form-group full">

                                <label for="alamat">
                                    Alamat
                                </label>

                                <textarea
                                    id="alamat"
                                    name="alamat"
                                ><?= htmlspecialchars($pasien['alamat'] ?? ''); ?></textarea>

                            </div>


                        </div>

                    </div>



                    <!-- STATUS PERAWATAN -->

                    <div class="form-section">


                        <div class="form-section-title">

                            <i class="bi bi-hospital"></i>

                            <h3>
                                Status Perawatan
                            </h3>

                        </div>


                        <div class="form-grid">


                            <div class="form-group">

                                <label for="status_lokasi">

                                    Lokasi Pasien

                                    <span class="required">*</span>

                                </label>

                                <select
                                    id="status_lokasi"
                                    name="status_lokasi"
                                    required
                                >

                                    <option value="">
                                        Pilih lokasi pasien
                                    </option>

                                    <option
                                        value="Dalam Yayasan"
                                        <?= $pasien['status_lokasi'] === 'Dalam Yayasan' ? 'selected' : ''; ?>
                                    >
                                        Dalam Yayasan
                                    </option>

                                    <option
                                        value="Luar Yayasan"
                                        <?= $pasien['status_lokasi'] === 'Luar Yayasan' ? 'selected' : ''; ?>
                                    >
                                        Luar Yayasan
                                    </option>

                                </select>

                            </div>


                            <div class="form-group">

                                <label for="kondisi">

                                    Kondisi Pasien

                                    <span class="required">*</span>

                                </label>

                                <select
                                    id="kondisi"
                                    name="kondisi"
                                    required
                                >

                                    <option value="">
                                        Pilih kondisi pasien
                                    </option>

                                    <option
                                        value="Stabil"
                                        <?= $pasien['kondisi'] === 'Stabil' ? 'selected' : ''; ?>
                                    >
                                        Stabil
                                    </option>

                                    <option
                                        value="Perlu Pantauan"
                                        <?= $pasien['kondisi'] === 'Perlu Pantauan' ? 'selected' : ''; ?>
                                    >
                                        Perlu Pantauan
                                    </option>

                                    <option
                                        value="Darurat"
                                        <?= $pasien['kondisi'] === 'Darurat' ? 'selected' : ''; ?>
                                    >
                                        Darurat
                                    </option>

                                </select>

                            </div>


                            <div class="form-group">

                                <label for="tanggal_masuk">
                                    Tanggal Masuk
                                </label>

                                <input
                                    type="date"
                                    id="tanggal_masuk"
                                    name="tanggal_masuk"
                                    value="<?= htmlspecialchars($pasien['tanggal_masuk'] ?? ''); ?>"
                                >

                            </div>


                            <div class="form-group">

                                <label for="status_pasien">

                                    Status Pasien

                                    <span class="required">*</span>

                                </label>

                                <select
                                    id="status_pasien"
                                    name="status_pasien"
                                    required
                                >

                                    <option
                                        value="Aktif"
                                        <?= $pasien['status_pasien'] === 'Aktif' ? 'selected' : ''; ?>
                                    >
                                        Aktif
                                    </option>

                                    <option
                                        value="Tidak Aktif"
                                        <?= $pasien['status_pasien'] === 'Tidak Aktif' ? 'selected' : ''; ?>
                                    >
                                        Tidak Aktif
                                    </option>

                                </select>

                            </div>


                        </div>

                    </div>



                    <!-- BUTTON -->

                    <div class="form-actions">


                        <a
                            href="data_pasien.php"
                            class="cancel-button"
                        >

                            Batal

                        </a>


                        <button
                            type="submit"
                            class="save-button"
                        >

                            <i class="bi bi-check-lg"></i>

                            Simpan Perubahan

                        </button>


                    </div>


                </form>


            </div>


        </div>


    </main>


</div>



<!-- PROFILE MODAL -->
<div class="profile-modal" id="profileModal">
    <div class="profile-modal-card">
        <div class="profile-modal-top">
            <h3>Profil Saya</h3>
            <button type="button" class="profile-close" id="closeProfile"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="profile-modal-body">
            <div class="profile-modal-avatar"><?= htmlspecialchars($inisial_user); ?></div>
            <div class="profile-modal-name"><?= htmlspecialchars($nama_user); ?></div>
            <div class="profile-modal-role"><?= htmlspecialchars(ucfirst($role_user)); ?></div>
            <div class="profile-detail">
                <div class="profile-detail-row"><span class="profile-detail-label">Nama Lengkap</span><span class="profile-detail-value"><?= htmlspecialchars($nama_user); ?></span></div>
                <div class="profile-detail-row"><span class="profile-detail-label">Username</span><span class="profile-detail-value">@<?= htmlspecialchars($username_user); ?></span></div>
                <div class="profile-detail-row"><span class="profile-detail-label">Role</span><span class="profile-detail-value"><?= htmlspecialchars(ucfirst($role_user)); ?></span></div>
                <div class="profile-detail-row"><span class="profile-detail-label">ID User</span><span class="profile-detail-value">#<?= (int) $id_user; ?></span></div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const button = document.getElementById("profileTopButton");
    const dropdown = document.getElementById("profileDropdown");
    const wrapper = document.getElementById("profileTopWrapper");
    const profileSaya = document.getElementById("profileSayaButton");
    const modal = document.getElementById("profileModal");
    const closeModal = document.getElementById("closeProfile");

    button?.addEventListener("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        dropdown?.classList.toggle("show");
        button?.classList.toggle("active");
    });

    document.addEventListener("click", function (e) {
        if (wrapper && !wrapper.contains(e.target)) {
            dropdown?.classList.remove("show");
            button?.classList.remove("active");
        }
    });

    profileSaya?.addEventListener("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        dropdown?.classList.remove("show");
        button?.classList.remove("active");
        modal?.classList.add("show");
    });

    closeModal?.addEventListener("click", function () { modal?.classList.remove("show"); });
    modal?.addEventListener("click", function (e) { if (e.target === modal) modal.classList.remove("show"); });
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            modal?.classList.remove("show");
            dropdown?.classList.remove("show");
            button?.classList.remove("active");
        }
    });
});
</script>

</body>

</html>